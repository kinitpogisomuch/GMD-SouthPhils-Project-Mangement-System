<?php

namespace App\Services;

use App\Models\ProjectLabor;
use App\Models\ProjectMaterial;
use App\Models\QuotationBatch;
use App\Models\QuotationRequest;
use App\Models\QuotationVersion;
use App\Models\SiteSetting;
use App\Support\TankCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Generates GMD's quotation document from the quotation's structured data — never from an uploaded
 * file. build() assembles a snapshot (client, tank, specifications, itemized costs, totals, terms) from
 * the live Quotation Builder; the same snapshot renders the on-screen preview, the client's view and the
 * PDF, and is frozen into a QuotationVersion when the quotation is sent, so a sent quotation never changes
 * afterwards even if the builder does.
 */
class QuotationDocument
{
    private const PDF_DISK = 'local';

    /** Bump when the PDF's look changes, so stored PDFs of sent versions are rendered again (see pdfFor) */
    private const PDF_LAYOUT = 2;

    /** Default document settings for a batch, merged with whatever the admin saved in the builder */
    public static function settings(QuotationBatch $batch): array
    {
        $request = self::firstRequest($batch);
        $type    = $batch->tank_type ?: $request?->tank_type;
        $def     = TankCatalog::forType($type);
        $saved   = $batch->document ?? [];

        $capacity = $batch->capacity ?: $request?->capacity;
        $qty      = $batch->tankQuantity();
        $title    = trim('Proposed supply of ' . ($capacity ? $capacity . ' ' : '') . ($type ?: 'tank') . ($qty > 1 ? " ({$qty} {$def['unit']}s)" : ''));

        $isPickup = ($request?->fulfillment ?? 'delivery') === 'pickup';
        $location = match (true) {
            $isPickup                     => 'For pick-up at ' . config('quotation.company.shop'),
            (bool) $request?->isOnsite()  => 'On site at ' . ($request->location ?: ($batch->client->address ?? '')),
            default                       => $request?->location ?: ($batch->client->address ?? ''),
        };

        return [
            'project_title'    => $saved['project_title'] ?? $title,
            'project_location' => $saved['project_location'] ?? $location,
            'rfq_ref'          => $saved['rfq_ref'] ?? '',
            'validity_days'    => (int) ($saved['validity_days'] ?? config('quotation.validity_days', 30)),
            'warranty'         => $saved['warranty'] ?? ($def['warranty'] ?? config('quotation.warranty')),
            'notes'            => $saved['notes'] ?? implode("\n", config('quotation.notes', [])),
            // the cost breakdown (Annex A) is no longer offered — quotations go out without it
            // (versions sent earlier keep whatever they were sent with)
            'show_breakdown'   => false,
            'signatory_name'   => $saved['signatory_name'] ?? config('quotation.signatory.name'),
            'signatory_title'  => $saved['signatory_title'] ?? config('quotation.signatory.title'),
            // e-signature image: this quotation's own, else the latest one saved on any quotation
            'signatory_signature' => ($saved['signatory_signature'] ?? null) ?: self::latestSignature(),
        ];
    }

    /**
     * The default e-signature: the most recently uploaded one (an upload becomes the default straight away),
     * else — for signatures from before uploads were recorded — the latest one saved on any quotation.
     */
    public static function latestSignature(): ?string
    {
        if ($uploaded = \App\Models\ESignature::latestUrl(\App\Models\ESignature::QUOTATION)) {
            return $uploaded;
        }

        return QuotationBatch::whereNotNull('document->signatory_signature')
            ->latest('updated_at')
            ->get(['document'])
            ->map(fn ($b) => $b->document['signatory_signature'] ?? null)
            ->first(fn ($url) => !empty($url));
    }

    /** The specification lines the document prints — saved in the builder, or the type's defaults */
    public static function specifications(QuotationBatch $batch): array
    {
        if (!empty($batch->specifications)) {
            return array_values(array_filter($batch->specifications, fn ($l) => trim((string) ($l['label'] ?? '')) !== '' || trim((string) ($l['value'] ?? '')) !== ''));
        }

        $request = self::firstRequest($batch);

        return TankCatalog::defaultSpecLines(
            $batch->tank_type ?: $request?->tank_type,
            $batch->capacity ?: $request?->capacity,
            $request?->specifications ?? []
        );
    }

    /**
     * Assemble the document from the quotation's current data.
     *
     * @param int|null $version the version number this will be sent as (null = draft preview)
     */
    public static function build(QuotationBatch $batch, ?int $version = null, $date = null): array
    {
        $batch->loadMissing('client');
        $date     = $date ? \Carbon\Carbon::parse($date) : now();
        $settings = self::settings($batch);
        $request  = self::firstRequest($batch);
        $client   = $batch->client;
        $type     = $batch->tank_type ?: ($request?->tank_type ?? 'Tank');
        $def      = TankCatalog::forType($type);
        $qty      = $batch->tankQuantity();
        $unitWord = $def['unit'];

        // ---- costs: the builder's rows are for ONE tank (see QuotationBatch::estimatedBudget) ----
        $budget    = $batch->estimatedBudget();
        $markupPct = (float) ($batch->markup_percent ?? 0);
        $markup    = (float) ($batch->markup ?? round($budget['total'] * $markupPct / 100, 2));
        $contract  = round($budget['total'] + $markup, 2);
        $unitPrice = round($contract / $qty, 2);
        $days      = (float) ($batch->estimated_working_days ?? 0);

        $materials = ProjectMaterial::where('quotation_batch_id', $batch->id)->where('status', 'active')->orderBy('id')->get()
            ->map(function (ProjectMaterial $m) {
                $factor = (float) ($m->factor ?? ProjectMaterial::DEFAULT_FACTOR);
                $price  = round((float) $m->price_per_unit * (1 + $factor / 100), 2);

                return [
                    'name'       => $m->material_name,
                    'quantity'   => (float) $m->quantity,
                    'unit'       => $m->unit,
                    'unit_price' => $price,
                    'amount'     => round((float) $m->total_cost * (1 + $factor / 100), 2),
                ];
            })->values()->all();

        $labor = ProjectLabor::where('quotation_batch_id', $batch->id)->where('status', 'active')->orderBy('id')->get()
            ->map(function (ProjectLabor $l) use ($days) {
                // "Juan Dela Cruz (Welder)" → the role is what the client needs to see
                $description = trim((string) $l->description);
                $role = preg_match('/\(([^()]*)\)\s*$/', $description, $m) ? trim($m[1]) : $description;

                return [
                    'description' => $role ?: 'Labor',
                    'days'        => $days,
                    'rate'        => (float) $l->daily_rate,
                    'amount'      => round((float) $l->total_cost, 2),
                ];
            })->values()->all();

        $materialsSubtotal = round(array_sum(array_column($materials, 'amount')), 2);
        $laborSubtotal     = round(array_sum(array_column($labor, 'amount')), 2);
        // on-site assembly costs and contingency are for the whole job — the per-tank summary shows each tank's share
        $others            = $batch->otherCostItems();
        $othersPerUnit     = round($budget['others'] / $qty, 2);
        $contingencyPerUnit = round($budget['contingency'] / $qty, 2);
        // whatever is left of the per-tank price after the costs above — so the breakdown always adds up
        $overhead          = round($unitPrice - $materialsSubtotal - $laborSubtotal - $othersPerUnit - $contingencyPerUnit, 2);

        $isPickup  = ($request?->fulfillment ?? 'delivery') === 'pickup';
        $specLines = self::specifications($batch);

        $items = [[
            'no'          => 1,
            'title'       => 'Supply of labor and materials, tools, equipment and supervision for the ' . $def['scope'] . ':',
            'specs'       => $specLines,
            'quantity'    => $qty,
            'unit'        => $qty === 1 ? $unitWord : $unitWord . 's',
            'unit_price'  => $unitPrice,
            'amount'      => $contract,
        ], [
            'no'          => 2,
            'title'       => match (true) {
                $isPickup                    => '*Pick up only at ' . config('quotation.company.shop') . '.',
                (bool) $request?->isOnsite() => '*Assembled on site by GMD South Phils at: ' . ($request->location ?: $settings['project_location']),
                default                      => '*For delivery to: ' . ($request?->location ?: $settings['project_location']),
            },
            'specs'       => [],
            'quantity'    => null,
            'unit'        => null,
            'unit_price'  => null,
            'amount'      => null,
        ]];

        $validUntil = $date->copy()->addDays(max(1, $settings['validity_days']));
        $notes      = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $settings['notes']))));

        $site = SiteSetting::query()->first();

        return [
            'is_draft'         => $version === null,
            'version'          => $version,
            'reference_no'     => $batch->reference_no,
            'quotation_number' => $version ? $batch->quotationNumberFor($version) : ($batch->reference_no . ' (draft)'),
            'date'             => $date->toDateString(),
            'valid_until'      => $validUntil->toDateString(),
            'company'          => array_merge(config('quotation.company'), array_filter([
                'email' => $site->email ?? null,
                'phone' => $site->mobile ?? ($site->phone ?? null),
            ])),
            'client'           => [
                'company_name'   => $client->name ?? '—',
                'address'        => $client->address ?? '',
                'contact_person' => $client->name ?? '—',
                'contact'        => $client->contact ?? '',
                'email'          => $client->email ?? '',
            ],
            'project'          => [
                'title'    => $settings['project_title'],
                'location' => $settings['project_location'],
                'rfq_ref'  => $settings['rfq_ref'],
            ],
            'tank'             => [
                'type'        => $type,
                'slug'        => $def['slug'],
                'capacity'    => $batch->capacity ?: $request?->capacity,
                'quantity'    => $qty,
                'unit'        => $unitWord,
                'fulfillment' => $isPickup ? 'pickup' : ($request?->isOnsite() ? 'onsite' : 'delivery'),
                'address'     => $isPickup ? null : $request?->location,
                'target_date' => $request?->target_timeline_display,
            ],
            'items'            => $items,
            'breakdown'        => [
                'show'               => $settings['show_breakdown'],
                'materials'          => $materials,
                'materials_subtotal' => $materialsSubtotal,
                'labor'              => $labor,
                'labor_subtotal'     => $laborSubtotal,
                'working_days'       => $days,
                'others'             => $others,
                'others_total'       => $budget['others'],
                'others_per_unit'    => $othersPerUnit,
                'contingency_percent' => $budget['contingency_percent'],
                'contingency'        => $budget['contingency'],
                'contingency_per_unit' => $contingencyPerUnit,
                'overhead'           => $overhead,
                'overhead_percent'   => $markupPct,
                'unit_price'         => $unitPrice,
                'quantity'           => $qty,
                'total'              => $contract,
            ],
            'totals'           => [
                'others'         => $budget['others'],
                'contingency'    => $budget['contingency'],
                'project_budget' => round($budget['total'], 2),
                'markup'         => round($markup, 2),
                'total'          => $contract,
            ],
            'payment_term_type' => $batch->payment_term_type,
            'payment_terms'     => config('quotation.payment_terms.' . $batch->payment_term_type, 'To be agreed.'),
            'validity'          => 'Validity of quotation: ' . $validUntil->format('F j, Y') . '. Prices may change without prior notice.',
            'warranty'          => $settings['warranty'],
            'notes'             => $notes,
            'closing'           => config('quotation.closing'),
            'signatory'         => ['name' => $settings['signatory_name'], 'title' => $settings['signatory_title'], 'signature' => $settings['signatory_signature'] ?? null],
            'estimated_days'    => ['per_unit' => $days, 'total' => $days * $qty],
        ];
    }

    /**
     * An uploaded image (e.g. the e-signature) as a data: URI for the PDF — dompdf runs with remote loading off,
     * so a local upload is read from disk and a cloud one downloaded. Null when it can't be read (the PDF then
     * just goes without it) or GD isn't available to decode it.
     */
    public static function embeddableImage(?string $url): ?string
    {
        if (!$url || !extension_loaded('gd')) {
            return null;
        }

        try {
            $local = asset('storage') . '/';
            $bytes = str_starts_with($url, $local)
                ? Storage::disk('public')->get(substr($url, strlen($local)))
                : \Illuminate\Support\Facades\Http::timeout(8)->get($url)->throw()->body();
        } catch (\Throwable $e) {
            return null;
        }
        if (!$bytes) {
            return null;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);

        return in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)
            ? 'data:' . $mime . ';base64,' . base64_encode($bytes)
            : null;
    }

    /** The HTML document (shared partial) for a snapshot */
    public static function html(array $doc, bool $forPdf = false): string
    {
        return view('partials.quotation_document', ['doc' => $doc, 'forPdf' => $forPdf])->render();
    }

    /**
     * Render a snapshot to PDF bytes — on ONE page, like the on-screen preview. If the quotation runs long (many
     * specification lines, notes, logos + signature) it is rendered again slightly smaller (a higher dpi scales the
     * whole page down evenly) until it fits. Versions sent with the cost-breakdown annex keep it on its own page.
     */
    public static function pdfBytes(array $doc): string
    {
        $bd       = $doc['breakdown'] ?? [];
        $hasAnnex = !empty($bd['show']) && (count($bd['materials'] ?? []) || count($bd['labor'] ?? []));

        foreach ([96, 104, 112, 120, 130, 140] as $dpi) {
            $pdf = Pdf::loadView('quotations.pdf', ['doc' => $doc])
                ->setPaper('a4')
                ->setOption(['isRemoteEnabled' => false, 'chroot' => public_path(), 'defaultFont' => 'DejaVu Sans', 'isFontSubsettingEnabled' => true, 'dpi' => $dpi]);
            $pdf->render();

            if ($hasAnnex || $pdf->getDomPDF()->getCanvas()->get_page_count() <= 1) {
                break;   // fits (or, with the annex, page count isn't the goal) — otherwise try a little smaller
            }
        }

        return $pdf->output();
    }

    /**
     * The stored PDF for a sent version — generated from its frozen snapshot the first time it is needed
     * (or again if the file is gone), so the PDF always matches exactly what the client was sent.
     * Kept on the private disk; it is only served through routes that check who is asking.
     */
    public static function pdfFor(QuotationVersion $version): string
    {
        $disk = Storage::disk(self::PDF_DISK);
        // stored under the PDF layout's version: a file made with an older layout is simply rendered again (same
        // snapshot, so the same content) — e.g. layout 2 keeps the quotation on one page with logos + e-signature
        $path = 'quotations/' . $version->quotation_batch_id . '/layout-' . self::PDF_LAYOUT . '/' . $version->pdfFilename();

        if ($version->pdf_path === $path && $disk->exists($path)) {
            return $disk->get($path);
        }

        $bytes = self::pdfBytes($version->snapshot);

        try {
            $disk->put($path, $bytes);
            $version->forceFill(['pdf_path' => $path])->save();
        } catch (\Throwable $e) {
            report($e);   // the download still works; it is just regenerated next time
        }

        return $bytes;
    }

    private static function firstRequest(QuotationBatch $batch): ?QuotationRequest
    {
        return QuotationRequest::where('batch_id', $batch->id)->orderBy('created_at')->first();
    }
}
