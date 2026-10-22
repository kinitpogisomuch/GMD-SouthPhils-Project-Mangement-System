<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectLabor;
use App\Models\ProjectMaterial;
use App\Models\ProjectTankItem;
use App\Models\AuditLog;
use App\Models\QuotationBatch;
use App\Models\QuotationRequest;
use App\Models\QuotationVersion;
use App\Services\AuditTrail;
use App\Services\NotificationService;
use App\Services\QuotationDocument;
use App\Services\SupabaseStorageService;
use App\Support\TankCatalog;
use Illuminate\Support\Facades\DB;

class QuotationRequestController extends Controller
{
    protected $storage;

    public function __construct(SupabaseStorageService $storage)
    {
        $this->storage = $storage;
    }

    /*
    |--------------------------------------------------------------------------
    | Client — Request a Quotation
    |--------------------------------------------------------------------------
    */
    public function create(Request $request)
    {
        $client = Client::findOrFail(session('user_id'));

        // Clients can submit a new request any time, even with one still under review —
        // the page shows both a "New Request" form and a "Pending" tab listing whatever
        // is currently awaiting GMD's review, so neither ever has to hide the other.
        $pendingBatches = QuotationRequest::with('quotationBatch.versions')
            ->where('client_id', $client->id)
            ->unresolved()
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn ($r) => $r->batch_id ?: ('single-' . $r->id));

        // Finished business (accepted, rejected or declined) is the module's third tab, so the client never
        // has to leave the Quotation page to look back at earlier requests.
        $historyBatches = QuotationRequest::with('quotationBatch.versions')
            ->where('client_id', $client->id)
            ->whereIn('status', ['converted', 'declined', 'rejected'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn ($r) => $r->batch_id ?: ('single-' . $r->id));

        // The tank picked on the landing page ("Our Work") — passed in the link, or remembered through
        // login / sign-up — opens straight to that tank's form. Used once, then forgotten.
        $requestable  = TankCatalog::requestableSlugs();
        $asked        = fn ($s) => TankCatalog::mergedInto($s) ?? $s;   // older links to a now-combined type
        $selectedTank = in_array($asked($request->query('tank')), $requestable, true)
            ? $asked($request->query('tank'))
            : (in_array($asked(session('quote_intent')), $requestable, true) ? $asked(session('quote_intent')) : null);
        // …and the standard size picked on the tank's details page, if any
        $selectedCapacity = $selectedTank
            ? TankCatalog::standardCapacity($selectedTank, $request->query('capacity') ?: session('quote_capacity'))
            : null;
        // …and the option picked on a combined card (e.g. Fuel Pipeline → Re-piping), pre-answered in the form
        $selectedChoice = $selectedTank
            ? TankCatalog::validChoice($selectedTank, $request->query('choice') ?: session('quote_choice'))
            : null;
        if (session()->has('quote_intent') || session()->has('quote_capacity') || session()->has('quote_choice') || $client->pending_quote_tank) {
            session()->forget(['quote_intent', 'quote_capacity', 'quote_choice']);
            if ($client->pending_quote_tank) {
                $client->forceFill(['pending_quote_tank' => null])->save();
            }
        }

        return view('client.quotation_request', [
            'client'         => $client,
            'tankTypes'      => ProjectTankItem::TANK_TYPES,
            'tankCards'      => TankCatalog::cards(),
            'selectedTank'     => $selectedTank,
            'selectedCapacity' => $selectedCapacity,
            // pre-answers the card's first question, e.g. ['service' => 'Re-piping (replace existing lines)']
            'selectedSpecs'    => $selectedChoice ? [TankCatalog::card($selectedTank)['choice']['key'] => $selectedChoice] : [],
            'pendingBatches' => $pendingBatches,
            'historyBatches' => $historyBatches,
        ]);
    }

    public function store(Request $request)
    {
        $client = Client::findOrFail(session('user_id'));

        // The tank card the client picked decides the tank type and which extra fields apply
        $items = (array) $request->input('tank_items', []);
        $specRules = [];
        $specNames = [];
        foreach ($items as $key => $item) {
            $slug = $item['tank_slug'] ?? TankCatalog::slugFor($item['tank_type'] ?? null);
            [$rules, $names] = TankCatalog::rules((string) $slug, "tank_items.$key.specs", (array) ($item['specs'] ?? []));
            $specRules += $rules;
            $specNames += $names;
        }

        $request->validate(array_merge([
            // Tank requirements are mandatory (at least one tank). Each tank is either for delivery
            // (which needs an address) or for pick-up (which doesn't); its own design upload is optional.
            'tank_items'                        => 'required|array|min:1|max:10',
            'tank_items.*.tank_slug'            => ['required', \Illuminate\Validation\Rule::in(TankCatalog::requestableSlugs())],
            'tank_items.*.specs'                => 'nullable|array',
            'tank_items.*.capacity'             => 'required|string|max:255',
            'tank_items.*.quantity'             => 'required|integer|min:1|max:1000',
            'tank_items.*.target_timeline'      => 'nullable|date|after_or_equal:today',
            'tank_items.*.design_files'         => 'nullable|array|max:5',
            // "extensions" (not "mimes") because CAD tools like AutoCAD don't produce a
            // MIME type PHP's file-info can reliably sniff — Laravel's "mimes" rule would
            // reject valid .dwg uploads, so we trust the file's extension instead.
            'tank_items.*.design_files.*'       => 'file|extensions:pdf,jpg,jpeg,png,dwg|max:10240',
            'notes'                             => 'nullable|string|max:2000',
        ], $specRules), [
            'tank_items.required'                     => 'Please choose the tank you need.',
            'tank_items.min'                          => 'Please choose the tank you need.',
            'tank_items.*.tank_slug.required'         => 'Choose a tank type.',
            'tank_items.*.tank_slug.in'               => 'Choose one of the tank types shown.',
            'tank_items.*.capacity.required'          => 'Enter the capacity / size.',
            'tank_items.*.quantity.required'          => 'Enter the quantity.',
            'tank_items.*.target_timeline.after_or_equal' => 'The target date cannot be in the past.',
        ], $specNames);

        // A second, identical request seconds after the first (double click that slipped past the browser
        // guard, a refresh, or a retried slow upload) is the same request — don't create it twice.
        $first = reset($items);
        $recentDuplicate = QuotationRequest::where('client_id', $client->id)
            ->where('created_at', '>=', now()->subMinutes(2))
            ->where('tank_type', TankCatalog::find(TankCatalog::storedSlug($first['tank_slug'], (array) ($first['specs'] ?? [])))['type'] ?? null)
            ->where('capacity', ProjectTankItem::normalizeCapacity($first['capacity'] ?? null) ?? ($first['capacity'] ?? null))
            ->where('quantity', (int) ($first['quantity'] ?? 0))
            ->exists();
        if ($recentDuplicate && count($items) === 1) {
            return redirect()->route('client.quotation.create', ['tab' => 'pending'])
                ->with('success', 'This request was already submitted a moment ago — it is listed below.');
        }

        // Every tank is its own request — its own batch, so its own quotation, its own approval, project and
        // payments. The client approves each quotation separately, so tanks in one submission (which may go to
        // different addresses, or be picked up) must never share a quotation.
        $prepared = [];
        foreach ($items as $tankKey => $item) {
            $batchId    = (string) Str::uuid();
            $files      = $request->file("tank_items.$tankKey.design_files", []);
            // uploads happen before the database work, so a slow upload never holds a transaction open
            $designUrls = $files
                ? $this->storage->uploadMultiple($files, 'quotation-requests/' . $batchId . '/reference')
                : [];
            $prepared[] = [$batchId, $item, $designUrls];
        }

        $created = DB::transaction(function () use ($prepared, $client, $request) {
            $created = collect();
            foreach ($prepared as [$batchId, $item, $designUrls]) {
                // a combined card (Fuel Pipeline) is saved as the service the client picked
                $def      = TankCatalog::find(TankCatalog::storedSlug($item['tank_slug'], (array) ($item['specs'] ?? [])));

                // The quotation (and its GMDSP reference number) exists from the moment the request is made
                QuotationBatch::create([
                    'id'            => $batchId,
                    'client_id'     => $client->id,
                ]);

                $created->push(QuotationRequest::create([
                    'client_id'       => $client->id,
                    'batch_id'        => $batchId,
                    'tank_type'       => $def['type'],
                    'capacity'        => ProjectTankItem::normalizeCapacity($item['capacity']) ?? $item['capacity'],
                    'quantity'        => (int) $item['quantity'],
                    'target_timeline' => $item['target_timeline'] ?? null,
                    // GMD's tanks are picked up at the Calauan, Laguna shop — there is no delivery option
                    'fulfillment'     => 'pickup',
                    'location'        => null,
                    'notes'           => $request->notes,
                    'reference_files' => !empty($designUrls) ? $designUrls : null,
                    'specifications'  => TankCatalog::cleanAnswers($item['tank_slug'], (array) ($item['specs'] ?? [])) ?: null,
                    'status'          => QuotationRequest::STATUS_PENDING,
                ]));
            }

            return $created;
        });

        $created->each(function (QuotationRequest $qr) {
            AuditTrail::record('quotation', $qr->batch_id, 'quotation.requested', [
                'tank' => $qr->tank_type, 'capacity' => $qr->capacity, 'quantity' => $qr->quantity,
            ]);
            NotificationService::quotationRequestSubmitted($qr);
        });

        $refs    = QuotationBatch::whereIn('id', $created->pluck('batch_id'))->pluck('reference_no')->implode(', ');
        $message = $created->count() > 1
            ? 'Your ' . $created->count() . ' tanks were sent as separate quotation requests (' . $refs . '). Our team will review them shortly.'
            : "Request {$refs} submitted! Our team will review it and send your quotation here.";

        return redirect()->route('client.quotation.create', ['tab' => 'pending'])->with('success', $message);
    }

    /** Quotation History used to be its own page; it is now the History tab of the Quotation module. */
    public function status()
    {
        return redirect()->route('client.quotation.create', ['tab' => 'history']);
    }

    /** The session client's batch, or 404 — a client can only ever reach their own quotations */
    private function clientBatch(string $batchId): QuotationBatch
    {
        $batch = QuotationBatch::where('id', $batchId)->where('client_id', session('user_id'))->first();
        abort_unless($batch, 404);

        return $batch;
    }

    /**
     * Runs a client decision on the quotation version they were looking at. Under row locks, so a double
     * click or two open tabs can't decide twice, and only the latest version still awaiting a decision
     * can be decided — an older version, or one GMD has since replaced, is refused.
     */
    private function decide(Request $request, string $batchId, callable $apply)
    {
        $batch = $this->clientBatch($batchId);
        $request->validate(['version_id' => 'nullable|integer']);

        $result = DB::transaction(function () use ($request, $batch, $apply) {
            QuotationBatch::whereKey($batch->id)->lockForUpdate()->first();
            $version = QuotationVersion::where('quotation_batch_id', $batch->id)->lockForUpdate()->orderByDesc('version')->first();
            $status  = QuotationRequest::where('batch_id', $batch->id)->value('status');

            if ($status !== QuotationRequest::STATUS_SENT || ($version && !$version->isAwaitingDecision())) {
                return 'You already responded to this quotation.';
            }
            if (!$version) {
                // a quotation uploaded as a file before quotations were generated — no version to tie it to
                $version = new QuotationVersion([
                    'quotation_batch_id' => $batch->id, 'version' => 0,
                    'quotation_number'   => $batch->reference_no, 'contract_value' => (float) $batch->contract_value,
                ]);
                $apply(null);

                return $version;
            }
            if ((int) $version->id !== (int) $request->input('version_id')) {
                return 'This is not the latest quotation — please review the newest version before deciding.';
            }

            $apply($version);

            return $version;
        });

        if (is_string($result)) {
            $back = QuotationVersion::where('quotation_batch_id', $batch->id)->exists()
                ? redirect()->route('client.quotation.show', $batch->id)
                : redirect()->route('client.quotation.create', ['tab' => 'pending']);

            return [null, $back->with('error', $result)];
        }

        return [$result, null];
    }

    /** Client approves the latest quotation version */
    public function approveQuotation(Request $request, $batchId)
    {
        [$version, $refused] = $this->decide($request, $batchId, function (?QuotationVersion $version) use ($batchId) {
            $version?->update(['status' => QuotationVersion::STATUS_APPROVED, 'responded_at' => now()]);
            QuotationRequest::where('batch_id', $batchId)->update([
                'status'      => QuotationRequest::STATUS_APPROVED,
                'approved_at' => now(),
            ]);
        });
        if ($refused) {
            return $refused;
        }

        AuditTrail::record('quotation', $batchId, 'quotation.approved', [
            'version' => $version->version, 'quotation_number' => $version->quotation_number, 'amount' => $version->contract_value,
        ]);
        NotificationService::quotationRequestApproved(QuotationRequest::where('batch_id', $batchId)->first());

        return redirect()->route('client.quotation.create', ['tab' => 'pending'])
            ->with('success', "Quotation {$version->quotation_number} approved! GMD South Phils will set up your project shortly.");
    }

    /**
     * Client asks for changes. Unlike a rejection this reopens the request: it goes back to "pending"
     * (with the client's reason) so it lands in the admin's queue with the same builder data, and the
     * version they saw is kept as "revision requested".
     */
    public function requestRevision(Request $request, $batchId)
    {
        $request->validate(['reason' => 'required|string|max:1000'], ['reason.required' => 'Tell GMD what you would like changed.']);
        $reason = trim($request->input('reason'));

        [$version, $refused] = $this->decide($request, $batchId, function (?QuotationVersion $version) use ($batchId, $reason) {
            $version?->update(['status' => QuotationVersion::STATUS_REVISION_REQUESTED, 'responded_at' => now(), 'response_reason' => $reason]);
            QuotationRequest::where('batch_id', $batchId)->update([
                'status'         => QuotationRequest::STATUS_PENDING,
                'decline_reason' => $reason,
            ]);
        });
        if ($refused) {
            return $refused;
        }

        AuditTrail::record('quotation', $batchId, 'quotation.revision_requested', ['version' => $version->version, 'reason' => $reason]);
        NotificationService::quotationRequestRejected(QuotationRequest::where('batch_id', $batchId)->first());

        return redirect()->route('client.quotation.create', ['tab' => 'pending'])
            ->with('success', 'Revision requested. GMD South Phils will send you an updated quotation.');
    }

    /** Client rejects the quotation for good — the request is closed */
    public function rejectQuotation(Request $request, $batchId)
    {
        $request->validate(['reason' => 'nullable|string|max:1000']);
        $reason = trim((string) $request->input('reason')) ?: null;

        [$version, $refused] = $this->decide($request, $batchId, function (?QuotationVersion $version) use ($batchId, $reason) {
            $version?->update(['status' => QuotationVersion::STATUS_REJECTED, 'responded_at' => now(), 'response_reason' => $reason]);
            QuotationRequest::where('batch_id', $batchId)->update([
                'status'         => QuotationRequest::STATUS_REJECTED,
                'decline_reason' => $reason,
            ]);
        });
        if ($refused) {
            return $refused;
        }

        AuditTrail::record('quotation', $batchId, 'quotation.rejected', ['version' => $version->version, 'reason' => $reason]);
        NotificationService::quotationRejectedByClient(QuotationRequest::where('batch_id', $batchId)->first());

        return redirect()->route('client.quotation.create', ['tab' => 'history'])
            ->with('success', "Quotation {$version->quotation_number} rejected. Thank you for letting us know.");
    }

    /** Client: the generated quotation (latest version, or ?v=N), with the decision buttons when it awaits one */
    public function clientShow(Request $request, $batchId)
    {
        $batch    = $this->clientBatch($batchId);
        $versions = $batch->versions()->get();
        abort_if($versions->isEmpty(), 404);

        $version = $request->filled('v') ? $versions->firstWhere('version', (int) $request->query('v')) : $versions->first();
        abort_unless($version, 404);

        $isLatest = $version->id === $versions->first()->id;
        $status   = QuotationRequest::where('batch_id', $batch->id)->value('status');

        return view('quotations.show', [
            'doc'       => $version->snapshot,
            'backUrl'   => route('client.quotation.create', ['tab' => in_array($status, ['converted', 'declined', 'rejected'], true) ? 'history' : 'pending']),
            'backLabel' => 'Back to Quotations',
            'pdfUrl'    => route('client.quotation.pdf', [$batch->id, $version->version]),
            'versions'  => $this->versionLinks($versions, $version, fn ($v) => route('client.quotation.show', [$batch->id, 'v' => $v->version])),
            'decision'  => $isLatest && $version->isAwaitingDecision() && $status === QuotationRequest::STATUS_SENT ? [
                'version_id'   => $version->id,
                'approve_url'  => route('client.quotation.approve', $batch->id),
                'revision_url' => route('client.quotation.revision', $batch->id),
                'reject_url'   => route('client.quotation.reject', $batch->id),
            ] : null,
        ]);
    }

    public function clientPdf($batchId, $version)
    {
        $batch   = $this->clientBatch($batchId);
        $version = $batch->versions()->where('version', (int) $version)->firstOrFail();

        return $this->pdfResponse($version);
    }

    private function pdfResponse(QuotationVersion $version)
    {
        return response(QuotationDocument::pdfFor($version), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $version->pdfFilename() . '"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }

    private function versionLinks($versions, QuotationVersion $current, callable $url)
    {
        return $versions->map(fn (QuotationVersion $v) => [
            'number'  => $v->quotation_number,
            'label'   => $v->statusLabel(),
            'badge'   => $v->badgeClass(),
            'url'     => $url($v),
            'current' => $v->id === $current->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin — Review Quotation Requests
    |--------------------------------------------------------------------------
    */
    /**
     * Combined "Quotation" module — Quotation Requests (client asks) and Project
     * Quotations (materials/labor for already-created projects) live on one page,
     * switched with a tab, instead of two separate sidebar entries.
     */
    public function adminIndex(Request $request)
    {
        $requests = QuotationRequest::with(['client', 'quotationBatch'])->orderBy('created_at', 'desc')->get();

        $projects     = Project::orderBy('created_at', 'desc')->get();
        // Group by client_id when a project is linked (so a client rename or
        // relink is reflected immediately); fall back to the raw name string
        // for the rare project that predates the client_id link.
        $clientGroups = $projects->groupBy(fn ($p) => $p->client_id ? 'id:' . $p->client_id : 'name:' . $p->client)
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'client'    => $first->live_client_name,
                    'client_key'=> $first->client_id ?: $first->client,
                    'total'     => $group->count(),
                    'active'    => $group->whereNotIn('status', ['completed', 'archived'])->count(),
                    'completed' => $group->where('status', 'completed')->count(),
                    'archived'  => $group->where('status', 'archived')->count(),
                ];
            })->sortBy(fn ($g) => strtolower($g['client']))->values();

        $activeTab = $request->query('tab') === 'projects' ? 'projects' : 'requests';

        return view('admin.quotation', compact('requests', 'clientGroups', 'activeTab'));
    }

    /** GET /admin/quotation-requests/pending-count — powers the sidebar nav badge (counts batches, not raw tank rows) */
    public function pendingCount()
    {
        $count = QuotationRequest::where('status', 'pending')
            ->get(['id', 'batch_id'])
            ->groupBy(fn ($r) => $r->batch_id ?: ('single-' . $r->id))
            ->count();

        return response()->json(compact('count'));
    }

    /** Declines every tank request in the batch together — a batch is one combined quotation. */
    public function decline(Request $request, $batchId)
    {
        $quotationRequest = QuotationRequest::where('batch_id', $batchId)->firstOrFail();

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ], [
            'reason.max' => 'The decline reason can be at most 500 characters.',
        ]);

        // Only a request that is still open can be declined (an approved quotation is converted, not declined)
        $updated = QuotationRequest::where('batch_id', $batchId)
            ->whereIn('status', ['pending', 'in_preparation', 'quotation_sent'])
            ->update([
                'status'         => 'declined',
                'decline_reason' => $validated['reason'] ?? null,
            ]);
        if (!$updated) {
            return redirect()->route('admin.quotation_requests')
                ->with('error', 'This request can no longer be declined — it was already decided.');
        }

        QuotationVersion::where('quotation_batch_id', $batchId)->where('status', QuotationVersion::STATUS_SENT)
            ->update(['status' => QuotationVersion::STATUS_SUPERSEDED]);
        AuditTrail::record('quotation', $batchId, 'quotation.declined', ['reason' => $validated['reason'] ?? null]);
        NotificationService::quotationRequestDeclined($quotationRequest->fresh());

        return redirect()->route('admin.quotation_requests')
            ->with('success', 'Quotation request declined.');
    }

    /**
     * Feeds the admin Projects module's "Add Project" modal so converting an approved
     * quotation reuses that full flow (template picker, shape/dimension calculator,
     * schedule) instead of a separate dedicated form. See ProjectController::store()
     * for how a submitted `quotation_batch_id` re-parents the quotation's materials/
     * labor onto the new project instead of re-entering them.
     */
    public function prefillBatch($batchId)
    {
        $requests = QuotationRequest::where('batch_id', $batchId)->orderBy('created_at')->get();

        if ($requests->isEmpty()) {
            abort(404);
        }
        if ($requests->first()->status !== 'approved') {
            return response()->json([
                'message' => 'This quotation must be approved by the client before it can be converted to a project.',
            ], 422);
        }

        $batch = $this->resolveBatch($batchId);
        $batch->load('client');
        $client = $batch->client;

        if (!$batch->payment_term_type) {
            return response()->json([
                'message' => 'Please set Payment Terms on the Costing page before converting.',
            ], 422);
        }

        $referenceFiles = $requests
            ->flatMap(fn ($qr) => $qr->reference_files ?? [])
            ->unique()
            ->values();

        // Dimensions the client (or GMD, in the builder's specification lines) already gave carry over,
        // so the Add Project form starts with them instead of asking again.
        $specLines  = QuotationDocument::specifications($batch);
        $dimensions = $this->projectDimensions(
            collect($specLines)->first(fn ($l) => stripos((string) ($l['label'] ?? ''), 'dimension') !== false)['value']
                ?? ($requests->first()->specifications['dimensions'] ?? null)
        );

        $tankItem = fn (?string $type, $quantity, ?string $capacity, $timeline) => [
            'tank_type'        => in_array($type, ProjectTankItem::TANK_TYPES, true) ? $type : ($type ? 'Others' : null),
            'tank_type_custom' => in_array($type, ProjectTankItem::TANK_TYPES, true) ? null : $type,
            'quantity'         => $quantity,
            'capacity'         => $capacity,
            'dimensions'       => $dimensions,
            'target_timeline'  => $timeline,
        ];

        // A quotation priced in the builder per tank is the source of truth for the tank type,
        // capacity and number of tanks; older quotations fall back to what the client requested.
        $tankItems = $batch->tank_type
            ? collect([$tankItem($batch->tank_type, $batch->tankQuantity(), $batch->capacity, $requests->first()->target_timeline_display)])
            : $requests->map(fn ($qr) => $tankItem($qr->tank_type, $qr->quantity, $qr->capacity, $qr->target_timeline_display));

        return response()->json([
            'quotation_batch_id'      => $batchId,
            // the builder's working days are for one tank — the project builds all of them
            'estimated_working_days'  => $batch->estimated_working_days ? $batch->estimated_working_days * $batch->tankQuantity() : null,
            'tank_quantity'           => $batch->tank_type ? $batch->tankQuantity() : null,
            'client' => [
                'name'    => $client->name,
                'contact' => $client->contact,
                'email'   => $client->email,
                'address' => $client->address,
            ],
            'tank_items' => $tankItems->values(),
            'reference_files' => $referenceFiles,
            'summary' => [
                'notes' => trim(implode("\n", array_filter([
                    'Quotation ' . ($batch->latestVersion()?->quotation_number ?? $batch->reference_no) . ' (approved by the client).',
                    $requests->first()->notes,
                    $requests->filter(fn ($qr) => $qr->tank_type && !in_array($qr->tank_type, ProjectTankItem::TANK_TYPES, true))
                        ->map(fn ($qr) => 'Tank type (Others): ' . $qr->tank_type)->unique()->implode('; '),
                    // the approved specifications travel with the project
                    collect($specLines)->map(fn ($l) => trim(($l['label'] ?? '') . ': ' . ($l['value'] ?? ''), ': '))->implode("\n"),
                ]))),
            ],
        ]);
    }

    /**
     * "2,200 mm Ø × 4,850 mm L" / "2.2 m dia x 4.85 m" / "4 x 2 x 2 m" → the Add Project form's own format
     * ("Ø2.2m × L4.85m", "L4m × W2m × H2m"), so its dimension fields fill in. Anything it can't read is
     * left for the admin (null).
     */
    private function projectDimensions(?string $text): ?string
    {
        if (!$text) {
            return null;
        }
        $t = mb_strtolower(str_replace(',', '', $text));
        $toM = fn (string $value, ?string $unit) => round((float) $value / (($unit ?? 'm') === 'mm' ? 1000 : (($unit ?? 'm') === 'cm' ? 100 : 1)), 3);
        $num = '([\d.]+)\s*(mm|cm|m)?';

        if (preg_match("/{$num}\s*(?:ø|dia\.?|diameter|⌀)\s*[x×*]\s*{$num}/u", $t, $m)
            || preg_match("/(?:ø|dia\.?|diameter|⌀)\s*{$num}\s*[x×*]\s*(?:l\s*)?{$num}/u", $t, $m)) {
            $m = array_pad($m, 7, '');   // trailing optional groups that didn't match are left out
            $unit2 = $m[4] ?: ($m[2] ?: 'm');
            return 'Ø' . $toM($m[1], $m[2] ?: $unit2) . 'm × L' . $toM($m[3], $unit2) . 'm';
        }
        if (preg_match("/{$num}\s*[x×*]\s*{$num}\s*[x×*]\s*{$num}/u", $t, $m)) {
            $m = array_pad($m, 7, '');
            $unit = $m[6] ?: ($m[4] ?: ($m[2] ?: 'm'));
            return 'L' . $toM($m[1], $m[2] ?: $unit) . 'm × W' . $toM($m[3], $m[4] ?: $unit) . 'm × H' . $toM($m[5], $unit) . 'm';
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Admin — Build Quotation (materials + labor against a quotation batch,
    | before any Project exists — see quotation_batches / ProjectMaterial/
    | ProjectLabor.quotation_batch_id)
    |--------------------------------------------------------------------------
    */

    /** Find or lazily create the QuotationBatch row for a batch_id shared by quotation_requests. */
    private function resolveBatch(string $batchId): QuotationBatch
    {
        $batch = QuotationBatch::find($batchId);
        if ($batch) {
            return $batch;
        }

        $firstRequest = QuotationRequest::where('batch_id', $batchId)->firstOrFail();

        return QuotationBatch::create([
            'id'        => $batchId,
            'client_id' => $firstRequest->client_id,
        ]);
    }

    /**
     * Once the client has decided (approved / rejected) or GMD declined the request, the quotation is final —
     * the Quotation Builder becomes view-only. While a quotation is out with the client it is also frozen,
     * so what they are reviewing can't change under them; a revision request reopens it.
     */
    private const LOCKED_STATUSES = ['quotation_sent', 'approved', 'converted', 'rejected', 'declined'];

    private function isLocked(string $batchId): bool
    {
        return QuotationRequest::where('batch_id', $batchId)
            ->whereIn('status', self::LOCKED_STATUSES)
            ->exists();
    }

    private function lockedRedirect(string $batchId)
    {
        $status = QuotationRequest::where('batch_id', $batchId)->value('status');
        $why = match ($status) {
            'quotation_sent' => 'The quotation is with the client for review. It can be edited again if they request a revision.',
            'rejected'       => 'The client rejected this quotation, so it can no longer be edited.',
            'declined'       => 'This request was declined, so its quotation can no longer be edited.',
            default          => 'The client has already approved this quotation, so it can no longer be edited.',
        };

        return redirect()
            ->route('admin.quotation_requests.batch_detail', $batchId)
            ->with('error', $why);
    }

    public function batchDetail($batchId)
    {
        $batch = $this->resolveBatch($batchId);
        $batch->load('client');

        $tankItems = QuotationRequest::where('batch_id', $batchId)
            ->orderBy('created_at')
            ->get();

        $materials       = ProjectMaterial::where('quotation_batch_id', $batchId)
            ->orderBy('created_at', 'desc')
            ->get();
        $activeMaterials = $materials->where('status', 'active');
        $totalMaterials  = $activeMaterials->count();
        $estimatedCost   = $activeMaterials->sum('total_cost');
        $materialFactor  = $materials->first()->factor ?? ProjectMaterial::DEFAULT_FACTOR;   // a new quotation starts at the standard 10%

        $laborEntries      = ProjectLabor::where('quotation_batch_id', $batchId)
            ->orderBy('created_at')
            ->get();
        $activeLabor       = $laborEntries->where('status', 'active');
        $totalLaborEntries = $activeLabor->count();
        $totalLaborCost    = $activeLabor->sum('total_cost');

        $estimatedBudget = $batch->estimatedBudget();
        $batchStatus     = $tankItems->first()->status ?? 'pending';
        $isLocked        = in_array($batchStatus, self::LOCKED_STATUSES, true);

        // The generated document's content: specification lines and print settings, plus every version sent
        $specLines   = QuotationDocument::specifications($batch);
        $docSettings = QuotationDocument::settings($batch);
        $versions    = $batch->versions()->get();
        $activity    = AuditLog::for('quotation', $batch->id)->latest('id')->limit(30)->get();

        $regularEmployees = Employee::where('status', 'Active')
            ->where('employee_type', 'Regular')
            ->orderBy('last_name')
            ->get();

        // "Choose a Starting Point" — past projects whose materials, labor and pricing can be
        // copied into this quotation. Only offered while the quotation can still be edited.
        $quotationTemplates = $isLocked ? collect() : $this->quotationTemplates();

        // The tank this quotation prices. Until it is saved it starts from the client's request; the
        // quantity only starts from the request while the builder is still empty — a quotation that
        // was already priced for the whole job keeps a quantity of 1 so its totals don't change.
        $requested       = $tankItems->first();
        $isBlank         = $materials->isEmpty() && $laborEntries->isEmpty();
        $builderTank     = [
            'tank_type' => $batch->tank_type ?: ProjectTankItem::canonicalType($requested->tank_type ?? null),
            'capacity'  => $batch->capacity ?: ProjectTankItem::normalizeCapacity($requested->capacity ?? null),
            'quantity'  => $batch->tank_type || !$isBlank ? $batch->tankQuantity() : max(1, (int) ($requested->quantity ?? 1)),
        ];
        $tankTypeOptions = $this->builderTankOptions($quotationTemplates, $builderTank);

        // Two steps: 1 · Costing (tank, pricing, materials, labor) → 2 · Quotation Document (what the
        // client receives). The document step opens once the costing is complete and saved.
        $costingGaps  = $this->quotationGaps($batch);
        $costingReady = empty($costingGaps);
        $step         = request('step') === 'document' && ($costingReady || $isLocked) ? 'document' : 'costing';

        return view('admin.quotation_batch_detail', compact(
            'batch', 'tankItems', 'batchStatus', 'isLocked',
            'materials', 'totalMaterials', 'estimatedCost', 'materialFactor',
            'laborEntries', 'totalLaborEntries', 'totalLaborCost',
            'estimatedBudget', 'regularEmployees', 'quotationTemplates',
            'builderTank', 'tankTypeOptions',
            'specLines', 'docSettings', 'versions', 'activity',
            'costingReady', 'costingGaps', 'step'
        ));
    }

    /**
     * Admin: the Quotation Document step's live preview — the document as it would be generated with the
     * details exactly as typed (not yet saved). The typed values are applied to an in-memory copy of the
     * quotation only; nothing is written. Returns a small standalone page shown in the preview frame.
     */
    public function livePreview(Request $request, $batchId)
    {
        $batch = $this->resolveBatch($batchId);

        $request->validate([
            'spec_label'           => 'nullable|array|max:40',
            'spec_label.*'         => 'nullable|string|max:120',
            'spec_value'           => 'nullable|array|max:40',
            'spec_value.*'         => 'nullable|string|max:1000',
            'doc'                  => 'nullable|array',
            'doc.project_title'    => 'nullable|string|max:255',
            'doc.project_location' => 'nullable|string|max:500',
            'doc.rfq_ref'          => 'nullable|string|max:100',
            'doc.validity_days'    => 'nullable|integer|min:1|max:365',
            'doc.warranty'         => 'nullable|string|max:1000',
            'doc.notes'            => 'nullable|string|max:4000',
            'doc.signatory_name'   => 'nullable|string|max:120',
            'doc.signatory_title'  => 'nullable|string|max:120',
        ]);

        // same shaping as the builder's Save (saveBatch), applied without saving
        if ($request->has('spec_label')) {
            $values = $request->input('spec_value', []);
            $lines  = [];
            foreach ($request->input('spec_label', []) as $i => $label) {
                $label = trim((string) $label);
                $value = trim((string) ($values[$i] ?? ''));
                if ($label !== '' || $value !== '') {
                    $lines[] = ['label' => $label, 'value' => $value];
                }
            }
            $batch->specifications = $lines;
        }
        if ($request->has('doc')) {
            $doc = collect($request->input('doc', []))
                ->only(['project_title', 'project_location', 'rfq_ref', 'validity_days', 'warranty', 'notes', 'signatory_name', 'signatory_title'])
                ->map(fn ($v) => is_string($v) ? trim($v) : $v)
                ->all();
            $doc['show_breakdown'] = $request->boolean('doc.show_breakdown');
            $batch->document = $doc;
        }

        return response()->view('quotations.live_preview', ['doc' => QuotationDocument::build($batch)])
            ->header('Cache-Control', 'no-store');
    }

    /** Admin: the quotation as it would be generated right now, before sending (nothing is saved) */
    public function preview($batchId)
    {
        $batch = $this->resolveBatch($batchId);

        return view('quotations.show', [
            'doc'       => QuotationDocument::build($batch),
            'backUrl'   => route('admin.quotation_requests.batch_detail', ['batchId' => $batch->id, 'step' => 'document']),
            'backLabel' => 'Back to Quotation Document',
            'pdfUrl'    => route('admin.quotation_requests.batch_preview_pdf', $batch->id),
            'versions'  => collect(),
            'decision'  => null,
        ]);
    }

    public function previewPdf($batchId)
    {
        $batch = $this->resolveBatch($batchId);

        return response(QuotationDocument::pdfBytes(QuotationDocument::build($batch)), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="quotation-' . $batch->reference_no . '-draft.pdf"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }

    /** Admin: a quotation version exactly as it was sent */
    public function adminVersion($batchId, $version)
    {
        $batch    = $this->resolveBatch($batchId);
        $versions = $batch->versions()->get();
        $current  = $versions->firstWhere('version', (int) $version);
        abort_unless($current, 404);

        return view('quotations.show', [
            'doc'       => $current->snapshot,
            'backUrl'   => route('admin.quotation_requests.batch_detail', ['batchId' => $batch->id, 'step' => 'document']),
            'backLabel' => 'Back to Quotation Document',
            'pdfUrl'    => route('admin.quotation_requests.version_pdf', [$batch->id, $current->version]),
            'versions'  => $this->versionLinks($versions, $current, fn ($v) => route('admin.quotation_requests.version', [$batch->id, $v->version])),
            'decision'  => null,
        ]);
    }

    public function adminVersionPdf($batchId, $version)
    {
        $batch = $this->resolveBatch($batchId);

        return $this->pdfResponse($batch->versions()->where('version', (int) $version)->firstOrFail());
    }

    /**
     * Admin takes back a quotation that is with the client but not yet decided (e.g. to fix a mistake):
     * the sent version is marked superseded and the builder opens for editing; the next send is a revision.
     */
    public function withdraw($batchId)
    {
        $done = DB::transaction(function () use ($batchId) {
            QuotationBatch::whereKey($batchId)->lockForUpdate()->first();
            $updated = QuotationRequest::where('batch_id', $batchId)->where('status', QuotationRequest::STATUS_SENT)
                ->update(['status' => QuotationRequest::STATUS_IN_PREPARATION]);
            if ($updated) {
                QuotationVersion::where('quotation_batch_id', $batchId)->where('status', QuotationVersion::STATUS_SENT)
                    ->update(['status' => QuotationVersion::STATUS_SUPERSEDED]);
            }
            return $updated > 0;
        });

        if (!$done) {
            return redirect()->route('admin.quotation_requests.batch_detail', $batchId)
                ->with('error', 'Only a quotation still waiting for the client\'s decision can be withdrawn.');
        }

        AuditTrail::record('quotation', $batchId, 'quotation.in_preparation', ['note' => 'Withdrawn from the client for revision']);

        return redirect()->route('admin.quotation_requests.batch_detail', $batchId)
            ->with('success', 'Quotation withdrawn. Make your changes and send the revised quotation.');
    }

    /**
     * Tank types for the builder's picker, each with the capacities GMD has built for it (from the
     * templates) — the standard list first, then any other type a template or this quotation uses.
     *
     * @return array<string, string[]> type => capacities
     */
    private function builderTankOptions($templates, array $builderTank): array
    {
        $options = [];
        foreach (ProjectTankItem::TANK_TYPES as $type) {
            if ($type !== 'Others') {
                $options[$type] = [];
            }
        }

        $add = function (?string $type, ?string $capacity) use (&$options) {
            if (!$type) {
                return;
            }
            $options[$type] = $options[$type] ?? [];
            if ($capacity && !in_array($capacity, $options[$type], true)) {
                $options[$type][] = $capacity;
            }
        };

        foreach ($templates as $tpl) {
            $add($tpl['tank_type'], $tpl['capacity']);
        }
        $add($builderTank['tank_type'], $builderTank['capacity']);

        // smallest to largest capacity
        foreach ($options as &$capacities) {
            usort($capacities, fn ($a, $b) => (float) str_replace(',', '', $a) <=> (float) str_replace(',', '', $b));
        }

        return $options;
    }

    /**
     * Every completed project that has a bill of materials, newest first, shaped for the Quotation
     * Builder's template picker: its tank type and capacity (the picker only offers templates of the
     * tank being quoted), its active materials and labor, plus the material factor, working days,
     * markup % and payment terms it was quoted with.
     *
     * A project's BOM covers all of its tanks, but the builder prices ONE tank — so material
     * quantities and working days are divided by the project's number of tanks. Prices and daily
     * rates are per unit / per day and stay as they were.
     */
    private function quotationTemplates()
    {
        $materials = ProjectMaterial::whereNotNull('project_id')->where('status', 'active')
            ->orderBy('id')->get()->groupBy('project_id');
        $labor     = ProjectLabor::whereNotNull('project_id')->where('status', 'active')
            ->orderBy('id')->get()->groupBy('project_id');
        $payments  = \App\Models\Payment::whereIn('project_id', $materials->keys())->get()->keyBy('project_id');
        $tanks     = ProjectTankItem::whereIn('project_id', $materials->keys())->orderBy('sort_order')->get()->groupBy('project_id');

        return Project::whereIn('id', $materials->keys())
            ->where('status', 'completed')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Project $project) use ($materials, $labor, $payments, $tanks) {
                $projectMaterials = $materials->get($project->id, collect());
                $payment          = $payments->get($project->id);
                $projectTanks     = $tanks->get($project->id, collect());
                $firstTank        = $projectTanks->first();
                $tankQty          = max(1, (int) $projectTanks->sum('quantity'));
                $perTank          = fn ($v) => round((float) $v / $tankQty, 2);
                $markupPct        = $payment && (float) $payment->project_budget > 0
                    ? round((float) $payment->markup / (float) $payment->project_budget * 100, 2)
                    : null;

                return [
                    'id'                     => $project->id,
                    'name'                   => $project->name,
                    'client'                 => $project->client,
                    'status'                 => $project->status,
                    'tank_type'              => ProjectTankItem::canonicalType($firstTank->tank_type ?? $project->tank_type),
                    'capacity'               => ProjectTankItem::normalizeCapacity($firstTank->capacity ?? $project->capacity),
                    'tank_qty'               => $tankQty,
                    'factor'                 => (float) ($projectMaterials->first()->factor ?? ProjectMaterial::DEFAULT_FACTOR),
                    'estimated_working_days' => $project->estimated_working_days ? max(1, $perTank($project->estimated_working_days)) : null,
                    // the builder only accepts a markup within the allowed range
                    'markup_percent'         => $markupPct === null ? null
                        : min(QuotationBatch::MARKUP_MAX, max(QuotationBatch::MARKUP_MIN, $markupPct)),
                    'payment_term_type'      => $payment->payment_term_type ?? null,
                    'materials'              => $projectMaterials->map(fn ($m) => [
                        'name'  => $m->material_name,
                        'unit'  => $m->unit,
                        'qty'   => max(0.01, $perTank($m->quantity)),
                        'price' => (float) $m->price_per_unit,
                    ])->values(),
                    // Labor is saved as "Name (Role)" — split it back so the builder can match the employee
                    'labor'                  => $labor->get($project->id, collect())->map(function ($l) {
                        $name = trim((string) $l->description);
                        $role = '';
                        if (preg_match('/^(.*?)\s*\(([^()]*)\)\s*$/', $name, $m)) {
                            [$name, $role] = [trim($m[1]), trim($m[2])];
                        }
                        return ['name' => $name, 'role' => $role, 'rate' => (float) $l->daily_rate];
                    })->values(),
                ];
            })
            // One template per kind of project and capacity — when the same one was completed more
            // than once, only the most recently completed one is offered.
            ->unique(fn (array $tpl) => mb_strtolower(trim($tpl['name'])) . '|' . mb_strtolower((string) $tpl['tank_type']) . '|' . $tpl['capacity'])
            ->values();
    }

    /**
     * Markup and Payment Terms are set independently on the page (outside the
     * Send-to-Client modal), before sending. Payment Terms are picked here, ahead
     * of conversion, so ProjectController::store() can auto-create the Payment
     * record on "Convert to Project" instead of a separate manual setup step.
     */
    public function updateMarkup(Request $request, $batchId)
    {
        if ($this->isLocked($batchId)) {
            return $this->lockedRedirect($batchId);
        }

        $batch = $this->resolveBatch($batchId);

        $validated = $request->validate([
            'markup_percent'     => 'required|numeric|min:' . QuotationBatch::MARKUP_MIN . '|max:' . QuotationBatch::MARKUP_MAX,
            'payment_term_type'  => 'nullable|in:big_project,small_project',
        ], [
            'markup_percent.min' => 'Markup must be between ' . QuotationBatch::MARKUP_MIN . '% and ' . QuotationBatch::MARKUP_MAX . '%.',
            'markup_percent.max' => 'Markup must be between ' . QuotationBatch::MARKUP_MIN . '% and ' . QuotationBatch::MARKUP_MAX . '%.',
        ]);

        $projectBudget = $batch->estimatedBudget()['total'];
        $markup        = round($projectBudget * $validated['markup_percent'] / 100, 2);

        $batch->update([
            'markup'             => $markup,
            'markup_percent'     => $validated['markup_percent'],
            'payment_term_type'  => $validated['payment_term_type'] ?? $batch->payment_term_type,
        ]);

        // "Send to Client" submits this form first (so whatever's currently typed is
        // actually saved) before the modal opens — this flag tells the redirected
        // page to open it automatically, showing the value that was just persisted.
        $routeParams = ['batchId' => $batchId];
        if ($request->boolean('open_send_modal')) {
            $routeParams['open_send'] = 1;
        }

        return redirect()
            ->route('admin.quotation_requests.batch_detail', $routeParams)
            ->with('success', 'Markup updated.');
    }

    /**
     * What is still missing from the saved quotation before it can go to the client.
     * An empty array means the Quotation Builder is complete.
     */
    private function quotationGaps(QuotationBatch $batch): array
    {
        $gaps = [];

        if (empty($batch->tank_type))                 { $gaps[] = 'Tank Type'; }
        if (empty($batch->capacity))                  { $gaps[] = 'Capacity'; }
        if ($batch->markup_percent === null)          { $gaps[] = 'Markup / Profit'; }
        if (empty($batch->payment_term_type))         { $gaps[] = 'Payment Terms'; }
        if ((float) $batch->estimated_working_days <= 0) { $gaps[] = 'Estimated Working Days'; }

        $materials = $batch->activeMaterials()->get();
        if ($materials->isEmpty()) {
            $gaps[] = 'at least one material';
        } elseif ($materials->contains(fn ($m) => trim((string) $m->unit) === '' || (float) $m->quantity <= 0)) {
            $gaps[] = 'a unit and quantity on every material';
        }

        if (!$batch->activeLabor()->exists()) {
            $gaps[] = 'at least one labor entry';
        }

        return $gaps;
    }

    public function sendBatchQuotation(Request $request, $batchId)
    {
        if ($this->isLocked($batchId)) {
            return $this->lockedRedirect($batchId);
        }

        $batch = $this->resolveBatch($batchId);

        // Only a finished Quotation Builder can be sent — the button is disabled until then, this is the backstop.
        if ($gaps = $this->quotationGaps($batch)) {
            return redirect()
                ->route('admin.quotation_requests.batch_detail', $batchId)
                ->with('error', 'Complete the Costing before sending to the client: ' . implode(', ', $gaps) . '.');
        }

        // The quotation document is generated by the system — supporting files (e.g. a shop drawing) are optional extras
        $request->validate([
            'quotation_files'    => 'nullable|array|max:5',
            'quotation_files.*'  => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $attachments = $request->hasFile('quotation_files')
            ? $this->storage->uploadMultiple($request->file('quotation_files', []), 'quotation-requests/' . $batchId . '/attachments')
            : null;

        // Under a lock on the quotation: a double click or two admins sending at once produce ONE version
        $version = DB::transaction(function () use ($batchId, $attachments) {
            $batch  = QuotationBatch::whereKey($batchId)->lockForUpdate()->first();
            $status = QuotationRequest::where('batch_id', $batchId)->value('status');
            if (!in_array($status, QuotationRequest::EDITABLE_STATUSES, true)) {
                return null;
            }

            // Snapshotted here, never recomputed live afterward — the version and the batch carry the sent figures
            $projectBudget = round($batch->estimatedBudget()['total'], 2);
            $markup        = (float) ($batch->markup ?? 0);
            $contractValue = round($projectBudget + $markup, 2);
            $number        = (int) $batch->current_version + 1;
            $sentAt        = now();

            $batch->update([
                'project_budget'  => $projectBudget,
                'markup'          => $markup,
                'contract_value'  => $contractValue,
                'current_version' => $number,
                'quotation_files' => $attachments ?: $batch->quotation_files,
            ]);

            // The previous version (if the client asked for a revision) stays on record, marked as replaced
            QuotationVersion::where('quotation_batch_id', $batchId)->where('status', QuotationVersion::STATUS_SENT)
                ->update(['status' => QuotationVersion::STATUS_SUPERSEDED]);

            $version = QuotationVersion::create([
                'quotation_batch_id' => $batchId,
                'version'            => $number,
                'quotation_number'   => $batch->quotationNumberFor($number),
                'status'             => QuotationVersion::STATUS_SENT,
                'snapshot'           => QuotationDocument::build($batch->fresh(), $number, $sentAt),
                'contract_value'     => $contractValue,
                'sent_at'            => $sentAt,
                'sent_by'            => session('name') ?: 'Admin',
            ]);

            QuotationRequest::where('batch_id', $batchId)->update([
                'status'            => QuotationRequest::STATUS_SENT,
                'quotation_sent_at' => $sentAt,
                // Clear any earlier revision-request reason — this fresh send addresses it (it stays on the old version).
                'decline_reason'    => null,
            ]);

            return $version;
        });

        if (!$version) {
            return redirect()->route('admin.quotation_requests.batch_detail', $batchId)
                ->with('success', 'This quotation was already sent to the client.');
        }

        // Generate and store the PDF now so the client's first download is instant (a failure here is not fatal —
        // the PDF is rebuilt from the frozen snapshot on demand)
        try {
            QuotationDocument::pdfFor($version);
        } catch (\Throwable $e) {
            report($e);
        }

        AuditTrail::record('quotation', $batchId, 'quotation.sent', [
            'version' => $version->version, 'quotation_number' => $version->quotation_number, 'amount' => $version->contract_value,
        ]);
        NotificationService::quotationVersionSent(QuotationRequest::where('batch_id', $batchId)->first(), $version);

        return redirect()
            ->route('admin.quotation_requests.batch_detail', $batchId)
            ->with('success', "Quotation {$version->quotation_number} generated and sent to the client's portal. Waiting for their decision.");
    }

    public function storeMaterials(Request $request, $batchId)
    {
        if ($this->isLocked($batchId)) {
            return $this->lockedRedirect($batchId);
        }

        $this->resolveBatch($batchId);

        $request->validate([
            'material_id'        => 'nullable|array',
            'delete_material_id' => 'nullable|array',
            'material_name'      => 'required|array|min:1',
            'material_name.*'    => 'required|string|max:255',
            'quantity'           => 'required|array|min:1',
            'quantity.*'         => 'required|numeric|min:0.01',
            'price_per_unit'     => 'required|array|min:1',
            'price_per_unit.*'   => 'required|numeric|min:0',
            'unit'               => 'nullable|array',
            'unit.*'             => 'nullable|string|max:50',
            'factor'             => 'required|numeric|min:0|max:100',
            'notes'              => 'nullable|array',
            'notes.*'            => 'nullable|string',
        ]);

        [$createdCount, $updatedCount, $deletedCount] = $this->syncMaterials($request, $batchId);

        $messages = [];
        if ($createdCount > 0) {
            $messages[] = $createdCount === 1 ? "1 material added" : "{$createdCount} materials added";
        }
        if ($updatedCount > 0) {
            $messages[] = $updatedCount === 1 ? "1 material updated" : "{$updatedCount} materials updated";
        }
        if ($deletedCount > 0) {
            $messages[] = $deletedCount === 1 ? "1 material deleted" : "{$deletedCount} materials deleted";
        }
        $message = $messages ? implode(', ', $messages) . '.' : 'No changes were made.';

        return redirect()
            ->route('admin.quotation_requests.batch_detail', $batchId)
            ->with('success', $message);
    }

    public function storeLabor(Request $request, $batchId)
    {
        if ($this->isLocked($batchId)) {
            return $this->lockedRedirect($batchId);
        }

        $batch = $this->resolveBatch($batchId);

        $request->validate([
            'estimated_working_days' => 'required|numeric|min:0',
            'employee_name'          => 'required|array|min:1',
            'employee_name.*'        => 'required|string|max:255',
            'role'                   => 'nullable|array',
            'role.*'                 => 'nullable|string|max:255',
            'daily_rate'             => 'required|array|min:1',
            'daily_rate.*'           => 'required|numeric|min:0',
        ]);

        $batch->update(['estimated_working_days' => $request->input('estimated_working_days')]);

        $names = $request->input('employee_name');
        $roles = $request->input('role', []);
        $rates = $request->input('daily_rate');

        foreach ($names as $i => $name) {
            $rate        = (float) $rates[$i];
            $role        = trim($roles[$i] ?? '');
            $description = $role ? "{$name} ({$role})" : $name;

            $labor = new ProjectLabor([
                'quotation_batch_id' => $batchId,
                'description'        => $description,
                'daily_rate'         => $rate,
                'total_cost'         => round($rate * $batch->estimated_working_days, 2),
                'status'             => 'active',
            ]);
            $labor->save();
        }

        // Keep all existing entries' totals in sync with the (possibly updated) estimated working days
        \DB::statement(
            'UPDATE project_labor SET total_cost = ROUND((daily_rate * ?)::numeric, 2) WHERE quotation_batch_id = ?',
            [$batch->estimated_working_days, $batchId]
        );

        $count = count($names);
        $label = $count === 1 ? "1 labor entry" : "{$count} labor entries";

        return redirect()
            ->route('admin.quotation_requests.batch_detail', $batchId)
            ->with('success', "Successfully added {$label} to the quotation.");
    }

    /**
     * Apply the material rows/deletions in the request to a quotation batch and
     * keep every material's factor in sync. Rows without a name are skipped so a
     * blank row left in the form never fails the whole save.
     *
     * @return array{0:int,1:int,2:int} created, updated, deleted counts
     */
    private function syncMaterials(Request $request, string $batchId): array
    {
        $ids       = $request->input('material_id', []);
        $deleteIds = $request->input('delete_material_id', []);
        $names     = $request->input('material_name', []);
        $qtys      = $request->input('quantity', []);
        $prices    = $request->input('price_per_unit', []);
        $units     = $request->input('unit', []);
        $notes     = $request->input('notes', []);
        $factor    = (float) $request->input('factor');

        $createdCount = 0;
        $updatedCount = 0;
        $deletedCount = 0;

        foreach ($deleteIds as $deleteId) {
            if (empty($deleteId)) {
                continue;
            }

            $material = ProjectMaterial::where('quotation_batch_id', $batchId)->find((int) $deleteId);

            if ($material) {
                $material->delete();
                $deletedCount++;
            }
        }

        foreach ($names as $i => $name) {
            if (trim((string) $name) === '') {
                continue;
            }

            $qty   = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            $id    = !empty($ids[$i]) ? (int) $ids[$i] : null;

            if ($id) {
                $material = ProjectMaterial::where('quotation_batch_id', $batchId)->find($id);

                if ($material) {
                    $material->update([
                        'material_name'  => $name,
                        'quantity'       => $qty,
                        'unit'           => $units[$i] ?? $material->unit,
                        'price_per_unit' => $price,
                        'total_cost'     => round($qty * $price, 2),
                        'notes'          => $notes[$i] ?? null,
                    ]);

                    $updatedCount++;
                    continue;
                }
            }

            $material = new ProjectMaterial([
                'quotation_batch_id' => $batchId,
                'material_name'      => $name,
                'quantity'           => $qty,
                'unit'               => $units[$i] ?? '',
                'price_per_unit'     => $price,
                'total_cost'         => round($qty * $price, 2),
                'factor'             => $factor,
                'notes'              => $notes[$i] ?? null,
                'status'             => 'active',
            ]);
            $material->save();

            $createdCount++;
        }

        // The Material Factor applies to the whole quotation — keep every material's factor in sync.
        ProjectMaterial::where('quotation_batch_id', $batchId)->update(['factor' => $factor]);

        return [$createdCount, $updatedCount, $deletedCount];
    }

    /**
     * The Build Quotation page's single Save: markup, payment terms, materials
     * (add / edit / delete) and labor (add / archive-restore) are all submitted
     * together and applied in one transaction, so a failure never leaves the
     * quotation half-saved.
     */
    /** "15,000.50" → "15000.50" for each listed field (arrays included), before validation. */
    private function stripThousandsSeparators(Request $request, array $fields): void
    {
        $clean = fn ($v) => is_string($v) ? str_replace([',', ' '], '', $v) : $v;
        foreach ($fields as $field) {
            if (!$request->has($field)) {
                continue;
            }
            $value = $request->input($field);
            $request->merge([$field => is_array($value) ? array_map($clean, $value) : $clean($value)]);
        }
    }

    public function saveAll(Request $request, $batchId)
    {
        if ($this->isLocked($batchId)) {
            return $this->lockedRedirect($batchId);
        }

        $batch = $this->resolveBatch($batchId);

        // Price / Unit (and the daily rates) are shown with thousands separators — "15,000.00" is 15000
        $this->stripThousandsSeparators($request, ['price_per_unit', 'daily_rate', 'quantity']);

        $request->validate([
            'tank_type'              => 'required|string|max:255',
            'capacity'               => 'required|string|max:255',
            'tank_quantity'          => 'required|integer|min:1|max:1000',
            'markup_percent'         => 'required|numeric|min:' . QuotationBatch::MARKUP_MIN . '|max:' . QuotationBatch::MARKUP_MAX,
            'payment_term_type'      => 'required|in:big_project,small_project',
            'factor'                 => 'required|numeric|min:0|max:100',
            'estimated_working_days' => 'required|numeric|gt:0',

            'material_id'            => 'nullable|array',
            'delete_material_id'     => 'nullable|array',
            'material_name'          => 'nullable|array',
            'material_name.*'        => 'nullable|string|max:255',
            'quantity'               => 'nullable|array',
            'quantity.*'             => 'nullable|numeric|min:0',
            'price_per_unit'         => 'nullable|array',
            'price_per_unit.*'       => 'nullable|numeric|min:0',
            'unit'                   => 'nullable|array',
            'unit.*'                 => 'nullable|string|max:50',
            'notes'                  => 'nullable|array',
            'notes.*'                => 'nullable|string',

            'employee_name'          => 'nullable|array',
            'employee_name.*'        => 'required|string|max:255',
            'role'                   => 'nullable|array',
            'role.*'                 => 'nullable|string|max:255',
            'daily_rate'             => 'nullable|array',
            'daily_rate.*'           => 'nullable|numeric|min:0',
            'labor_toggle_id'        => 'nullable|array',
            'labor_toggle_id.*'      => 'integer',

            // what the generated quotation document prints
            'spec_label'             => 'nullable|array|max:40',
            'spec_label.*'           => 'nullable|string|max:120',
            'spec_value'             => 'nullable|array|max:40',
            'spec_value.*'           => 'nullable|string|max:1000',
            'doc'                    => 'nullable|array',
            'doc.project_title'      => 'nullable|string|max:255',
            'doc.project_location'   => 'nullable|string|max:500',
            'doc.rfq_ref'            => 'nullable|string|max:100',
            'doc.validity_days'      => 'nullable|integer|min:1|max:365',
            'doc.warranty'           => 'nullable|string|max:1000',
            'doc.notes'              => 'nullable|string|max:4000',
            'doc.show_breakdown'     => 'nullable|boolean',
            'doc.signatory_name'     => 'nullable|string|max:120',
            'doc.signatory_title'    => 'nullable|string|max:120',
        ], [
            'markup_percent.min'     => 'Markup must be between ' . QuotationBatch::MARKUP_MIN . '% and ' . QuotationBatch::MARKUP_MAX . '%.',
            'markup_percent.max'     => 'Markup must be between ' . QuotationBatch::MARKUP_MIN . '% and ' . QuotationBatch::MARKUP_MAX . '%.',
            'tank_quantity.integer'  => 'Tank quantity must be a whole number.',
            'tank_quantity.min'      => 'Tank quantity must be at least 1.',
        ], [
            'tank_type'              => 'tank type',
            'tank_quantity'          => 'tank quantity',
            'markup_percent'         => 'markup',
            'payment_term_type'      => 'payment terms',
            'factor'                 => 'material factor',
            'estimated_working_days' => 'estimated working days',
        ]);

        // A quotation is only saved when it is complete: every material row fully filled in
        // (blank-named rows are ignored), and every new labor row with an employee, role and rate.
        $incomplete = [];
        foreach ($request->input('material_name', []) as $i => $name) {
            if (trim((string) $name) === '') {
                continue;
            }
            $row = $i + 1;
            if (trim((string) $request->input("unit.$i")) === '') {
                $incomplete["unit.$i"] = "Materials row {$row}: enter a unit.";
            }
            if ((float) ($request->input("quantity.$i") ?? 0) <= 0) {
                $incomplete["quantity.$i"] = "Materials row {$row}: quantity must be greater than 0.";
            }
            if (trim((string) $request->input("price_per_unit.$i")) === '') {
                $incomplete["price_per_unit.$i"] = "Materials row {$row}: enter the price per unit.";
            }
        }
        foreach ($request->input('employee_name', []) as $i => $name) {
            $row = $i + 1;
            if (trim((string) $request->input("role.$i")) === '') {
                $incomplete["role.$i"] = "Labor row {$row}: choose a role.";
            }
            if (trim((string) $request->input("daily_rate.$i")) === '') {
                $incomplete["daily_rate.$i"] = "Labor row {$row}: enter the daily rate.";
            }
        }
        if ($incomplete) {
            throw \Illuminate\Validation\ValidationException::withMessages($incomplete);
        }

        $summary = [];

        \DB::transaction(function () use ($request, $batch, $batchId, &$summary) {
            // Materials
            [$created, $updated, $deleted] = $this->syncMaterials($request, $batchId);
            if ($created) { $summary[] = $created === 1 ? '1 material added' : "{$created} materials added"; }
            if ($updated) { $summary[] = $updated === 1 ? '1 material updated' : "{$updated} materials updated"; }
            if ($deleted) { $summary[] = $deleted === 1 ? '1 material deleted' : "{$deleted} materials deleted"; }

            // Labor — days first, so new rows and existing totals both use the final value
            if ($request->filled('estimated_working_days')) {
                $batch->update(['estimated_working_days' => $request->input('estimated_working_days')]);
            }
            $days = (float) ($batch->estimated_working_days ?? 0);

            $roles = $request->input('role', []);
            $rates = $request->input('daily_rate', []);
            $added = 0;
            foreach ($request->input('employee_name', []) as $i => $name) {
                $rate        = (float) ($rates[$i] ?? 0);
                $role        = trim($roles[$i] ?? '');
                $description = $role ? "{$name} ({$role})" : $name;

                $labor = new ProjectLabor([
                    'quotation_batch_id' => $batchId,
                    'description'        => $description,
                    'daily_rate'         => $rate,
                    'total_cost'         => round($rate * $days, 2),
                    'status'             => 'active',
                ]);
                $labor->save();
                $added++;
            }
            if ($added) { $summary[] = $added === 1 ? '1 labor entry added' : "{$added} labor entries added"; }

            foreach ((array) $request->input('labor_toggle_id', []) as $laborId) {
                $entry = ProjectLabor::where('quotation_batch_id', $batchId)->find((int) $laborId);
                if ($entry) {
                    $entry->status = $entry->status === 'archived' ? 'active' : 'archived';
                    $entry->save();
                }
            }

            \DB::statement(
                'UPDATE project_labor SET total_cost = ROUND((daily_rate * ?)::numeric, 2) WHERE quotation_batch_id = ?',
                [$days, $batchId]
            );

            // The finished quotation must contain at least one material and one labor entry.
            // Throwing here rolls the whole transaction back, so nothing is half-saved.
            if (!ProjectMaterial::where('quotation_batch_id', $batchId)->where('status', 'active')->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['material_name' => 'Add at least one material before saving.']);
            }
            if (!ProjectLabor::where('quotation_batch_id', $batchId)->where('status', 'active')->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['employee_name' => 'Add at least one labor entry before saving.']);
            }

            // The tank being priced: the rows above are for ONE tank, the quantity multiplies them
            $batch->update([
                'tank_type'     => ProjectTankItem::canonicalType($request->input('tank_type')),
                'capacity'      => ProjectTankItem::normalizeCapacity($request->input('capacity')),
                'tank_quantity' => (int) $request->input('tank_quantity'),
            ]);

            // Markup last — it is a percentage of the Project Budget, which the rows above just changed
            $projectBudget = $batch->fresh()->estimatedBudget()['total'];
            $batch->update([
                'markup'            => round($projectBudget * (float) $request->input('markup_percent') / 100, 2),
                'markup_percent'    => $request->input('markup_percent'),
                'payment_term_type' => $request->input('payment_term_type') ?: $batch->payment_term_type,
            ]);

            // The quotation document: specification lines (in the order shown) and its print settings
            if ($request->has('spec_label')) {
                $values = $request->input('spec_value', []);
                $lines  = [];
                foreach ($request->input('spec_label', []) as $i => $label) {
                    $label = trim((string) $label);
                    $value = trim((string) ($values[$i] ?? ''));
                    if ($label !== '' || $value !== '') {
                        $lines[] = ['label' => $label, 'value' => $value];
                    }
                }
                $batch->update(['specifications' => $lines]);
            }
            if ($request->has('doc')) {
                $doc = collect($request->input('doc', []))
                    ->only(['project_title', 'project_location', 'rfq_ref', 'validity_days', 'warranty', 'notes', 'signatory_name', 'signatory_title'])
                    ->map(fn ($v) => is_string($v) ? trim($v) : $v)
                    ->all();
                $doc['show_breakdown'] = $request->boolean('doc.show_breakdown');
                $batch->update(['document' => $doc]);
            }

            // GMD has started on it: Pending → In Preparation
            QuotationRequest::where('batch_id', $batchId)->where('status', QuotationRequest::STATUS_PENDING)
                ->update(['status' => QuotationRequest::STATUS_IN_PREPARATION]);
        });

        AuditTrail::record('quotation', $batchId, 'quotation.saved', array_filter([
            'changes'  => $summary ? implode(', ', $summary) : null,
            'quantity' => (int) $request->input('tank_quantity'),
            'markup'   => (float) $request->input('markup_percent'),
        ]));

        // "Send to Client" saves first so the modal always shows what is persisted.
        $routeParams = ['batchId' => $batchId];
        if ($request->input('step') === 'document') {
            $routeParams['step'] = 'document';   // back to the step the admin saved from
        }
        if ($request->boolean('open_send_modal')) {
            $routeParams['open_send'] = 1;
            $routeParams['step']      = 'document';
        }

        $what = $request->input('step') === 'document' ? 'Quotation document saved' : 'Costing saved';
        return redirect()
            ->route('admin.quotation_requests.batch_detail', $routeParams)
            ->with('success', $summary ? $what . ' — ' . implode(', ', $summary) . '.' : $what . '.');
    }


    public function deleteMaterial($batchId, $materialId)
    {
        if ($this->isLocked($batchId)) {
            return $this->lockedRedirect($batchId);
        }

        $material = ProjectMaterial::where('quotation_batch_id', $batchId)->findOrFail($materialId);
        $name     = $material->material_name;
        $material->delete();

        return redirect()
            ->route('admin.quotation_requests.batch_detail', $batchId)
            ->with('success', "Material \"{$name}\" deleted.");
    }

    public function updateEstimatedDays(Request $request, $batchId)
    {
        if ($this->isLocked($batchId)) {
            return $this->lockedRedirect($batchId);
        }

        $batch = $this->resolveBatch($batchId);

        $validated = $request->validate([
            'estimated_working_days' => 'required|numeric|min:0',
        ]);

        $batch->update($validated);

        \DB::statement(
            'UPDATE project_labor SET total_cost = ROUND((daily_rate * ?)::numeric, 2) WHERE quotation_batch_id = ?',
            [$batch->estimated_working_days, $batchId]
        );

        return redirect()
            ->route('admin.quotation_requests.batch_detail', $batchId)
            ->with('success', 'Estimated working days updated successfully.');
    }

    public function archiveLabor($batchId, $laborId)
    {
        if ($this->isLocked($batchId)) {
            return $this->lockedRedirect($batchId);
        }

        $entry         = ProjectLabor::where('quotation_batch_id', $batchId)->findOrFail($laborId);
        $entry->status = $entry->status === 'archived' ? 'active' : 'archived';
        $entry->save();

        $label = $entry->status === 'archived' ? 'archived' : 'restored';

        return redirect()
            ->route('admin.quotation_requests.batch_detail', $batchId)
            ->with('success', "Labor entry \"{$entry->description}\" {$label} successfully.");
    }
}
