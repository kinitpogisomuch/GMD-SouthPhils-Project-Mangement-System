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
use App\Models\QuotationBatch;
use App\Models\QuotationRequest;
use App\Services\NotificationService;
use App\Services\SupabaseStorageService;

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
    public function create()
    {
        $client = Client::findOrFail(session('user_id'));

        // Clients can submit a new request any time, even with one still under review —
        // the page shows both a "New Request" form and a "Pending" tab listing whatever
        // is currently awaiting GMD's review, so neither ever has to hide the other.
        $pendingBatches = QuotationRequest::where('client_id', $client->id)
            ->unresolved()
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn ($r) => $r->batch_id ?: ('single-' . $r->id));

        // Finished business (accepted or declined) is the module's third tab, so the client never
        // has to leave the Quotation page to look back at earlier requests.
        $historyBatches = QuotationRequest::where('client_id', $client->id)
            ->whereIn('status', ['converted', 'declined'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn ($r) => $r->batch_id ?: ('single-' . $r->id));

        return view('client.quotation_request', [
            'client'         => $client,
            'tankTypes'      => ProjectTankItem::TANK_TYPES,
            'pendingBatches' => $pendingBatches,
            'historyBatches' => $historyBatches,
        ]);
    }

    public function store(Request $request)
    {
        $client = Client::findOrFail(session('user_id'));

        $request->validate([
            // Tank requirements are mandatory (at least one tank). Each tank is either for delivery
            // (which needs an address) or for pick-up (which doesn't); its own design upload is optional.
            'tank_items'                        => 'required|array|min:1',
            'tank_items.*.tank_type'            => 'required|string|in:' . implode(',', ProjectTankItem::TANK_TYPES),
            // "Others" = the client names their own tank type
            'tank_items.*.tank_type_other'      => 'required_if:tank_items.*.tank_type,Others|nullable|string|max:100',
            'tank_items.*.capacity'             => 'required|string|max:255',
            'tank_items.*.quantity'             => 'required|integer|min:1',
            'tank_items.*.target_timeline'      => 'nullable|date',
            'tank_items.*.fulfillment'          => 'required|in:delivery,pickup',
            'tank_items.*.address'              => 'required_if:tank_items.*.fulfillment,delivery|nullable|string|max:1000',
            'tank_items.*.design_files'         => 'nullable|array|max:5',
            // "extensions" (not "mimes") because CAD tools like AutoCAD don't produce a
            // MIME type PHP's file-info can reliably sniff — Laravel's "mimes" rule would
            // reject valid .dwg uploads, so we trust the file's extension instead.
            'tank_items.*.design_files.*'       => 'file|extensions:pdf,jpg,jpeg,png,dwg|max:10240',
            'notes'                             => 'nullable|string|max:2000',
        ], [
            'tank_items.required'                     => 'Please add at least one tank requirement.',
            'tank_items.min'                          => 'Please add at least one tank requirement.',
            'tank_items.*.tank_type.required'         => 'Choose a tank type for every tank.',
            'tank_items.*.tank_type_other.required_if' => 'Type your tank type for every tank set to "Others".',
            'tank_items.*.capacity.required'          => 'Enter the capacity / size for every tank.',
            'tank_items.*.quantity.required'          => 'Enter a quantity for every tank.',
            'tank_items.*.fulfillment.required'       => 'Choose delivery or pick-up for every tank.',
            'tank_items.*.address.required_if'        => 'Enter the delivery address for every tank that is for delivery.',
        ]);

        // Every tank is its own request — its own batch, so its own quotation, its own approval, project and
        // payments. The client approves each quotation separately, so tanks in one submission (which may go to
        // different addresses, or be picked up) must never share a quotation.
        $created = collect();
        $tankNo  = 0;

        foreach ($request->input('tank_items', []) as $tankKey => $item) {
            $tankNo++;
            $batchId    = (string) Str::uuid();
            $delivery   = ($item['fulfillment'] ?? 'delivery') === 'delivery';
            $files      = $request->file("tank_items.$tankKey.design_files", []);
            $designUrls = $files
                ? $this->storage->uploadMultiple($files, 'quotation-requests/' . $batchId . '/reference')
                : [];

            $quotationRequest = new QuotationRequest([
                'client_id'       => $client->id,
                'batch_id'        => $batchId,
                // "Others" is stored as the client's own wording, so every screen shows what they actually asked for
                'tank_type'       => $item['tank_type'] === 'Others' ? trim($item['tank_type_other']) : $item['tank_type'],
                'capacity'        => $item['capacity'],
                'quantity'        => $item['quantity'],
                'target_timeline' => $item['target_timeline'] ?? null,
                'fulfillment'     => $delivery ? 'delivery' : 'pickup',
                'location'        => $delivery ? trim($item['address']) : null,
                'notes'           => $request->notes,
                'reference_files' => !empty($designUrls) ? $designUrls : null,
                'status'          => 'pending',
            ]);
            $quotationRequest->save();

            $created->push($quotationRequest);
        }

        $created->each(fn ($qr) => NotificationService::quotationRequestSubmitted($qr));

        $message = $created->count() > 1
            ? 'Your ' . $created->count() . ' tanks were sent as ' . $created->count() . ' separate quotation requests — each one gets its own quotation and approval. Our team will review them shortly.'
            : 'Your request has been submitted! Our team will review it shortly.';

        return redirect()->route('client.quotation.create')->with('success', $message);
    }

    /** Quotation History used to be its own page; it is now the History tab of the Quotation module. */
    public function status()
    {
        return redirect()->route('client.quotation.create', ['tab' => 'history']);
    }

    /** Approves every tank request in the batch together — the client reviews and decides on one combined quotation. */
    public function approveQuotation(Request $request, $batchId)
    {
        $client = Client::findOrFail(session('user_id'));

        $requests = QuotationRequest::where('batch_id', $batchId)
            ->where('client_id', $client->id)
            ->get();

        if ($requests->isEmpty()) {
            abort(404);
        }

        if ($requests->first()->status !== 'quotation_sent') {
            return redirect()->route('client.quotation.create')
                ->with('error', 'There is no quotation currently awaiting your approval.');
        }

        QuotationRequest::where('batch_id', $batchId)->update([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);

        NotificationService::quotationRequestApproved($requests->first()->fresh());

        return redirect()->route('client.quotation.create')
            ->with('success', 'Quotation approved! Our team will proceed with your project shortly.');
    }

    /** Client rejects the sent quotation — a batch is one combined quotation, so this declines every tank in it. */
    public function rejectQuotation(Request $request, $batchId)
    {
        $client = Client::findOrFail(session('user_id'));

        $requests = QuotationRequest::where('batch_id', $batchId)
            ->where('client_id', $client->id)
            ->get();

        if ($requests->isEmpty()) {
            abort(404);
        }

        if ($requests->first()->status !== 'quotation_sent') {
            return redirect()->route('client.quotation.create')
                ->with('error', 'There is no quotation currently awaiting your decision.');
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        // Unlike a decline, this reopens the request instead of ending it — it goes
        // back to "pending" (with the client's reason attached) so it lands back in
        // the admin's queue and the same Build Quotation page/materials/labor stay
        // intact for the admin to revise, rather than starting over from scratch.
        QuotationRequest::where('batch_id', $batchId)->update([
            'status'         => 'pending',
            'decline_reason' => $request->input('reason'),
        ]);

        NotificationService::quotationRequestRejected($requests->first()->fresh());

        return redirect()->route('client.quotation.create')
            ->with('success', 'Revision requested. Our team will follow up with an updated quotation.');
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
        $requests = QuotationRequest::with('client')->orderBy('created_at', 'desc')->get();

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

        QuotationRequest::where('batch_id', $batchId)->update([
            'status'         => 'declined',
            'decline_reason' => $validated['reason'] ?? null,
        ]);

        NotificationService::quotationRequestDeclined($quotationRequest);

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
                'message' => 'Please set Payment Terms on the Build Quotation page before converting.',
            ], 422);
        }

        $referenceFiles = $requests
            ->flatMap(fn ($qr) => $qr->reference_files ?? [])
            ->unique()
            ->values();

        $tankItem = fn (?string $type, $quantity, ?string $capacity, $timeline) => [
            'tank_type'        => in_array($type, ProjectTankItem::TANK_TYPES, true) ? $type : ($type ? 'Others' : null),
            'tank_type_custom' => in_array($type, ProjectTankItem::TANK_TYPES, true) ? null : $type,
            'quantity'         => $quantity,
            'capacity'         => $capacity,
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
                    $requests->first()->notes,
                    $requests->filter(fn ($qr) => $qr->tank_type && !in_array($qr->tank_type, ProjectTankItem::TANK_TYPES, true))
                        ->map(fn ($qr) => 'Tank type (Others): ' . $qr->tank_type)->unique()->implode('; '),
                ]))),
            ],
        ]);
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

    /** Once the client has approved the quotation it is final — the Quotation Builder becomes view-only. */
    private function isLocked(string $batchId): bool
    {
        return QuotationRequest::where('batch_id', $batchId)
            ->whereIn('status', ['approved', 'converted'])
            ->exists();
    }

    private function lockedRedirect(string $batchId)
    {
        return redirect()
            ->route('admin.quotation_requests.batch_detail', $batchId)
            ->with('error', 'The client has already approved this quotation, so it can no longer be edited.');
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
        $isLocked        = in_array($batchStatus, ['approved', 'converted'], true);

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

        return view('admin.quotation_batch_detail', compact(
            'batch', 'tankItems', 'batchStatus', 'isLocked',
            'materials', 'totalMaterials', 'estimatedCost', 'materialFactor',
            'laborEntries', 'totalLaborEntries', 'totalLaborCost',
            'estimatedBudget', 'regularEmployees', 'quotationTemplates',
            'builderTank', 'tankTypeOptions'
        ));
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
                ->with('error', 'Complete the Quotation Builder before sending to the client: ' . implode(', ', $gaps) . '.');
        }

        $request->validate([
            'quotation_files'    => 'required|array|min:1|max:5',
            'quotation_files.*'  => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        // Snapshotted here, never recomputed live afterward — see migration comment.
        // Markup was already saved separately (outside this modal) before sending.
        $projectBudget = $batch->estimatedBudget()['total'];
        $markup        = (float) ($batch->markup ?? 0);
        $contractValue = round($projectBudget + $markup, 2);

        $quotationUrls = $batch->quotation_files;
        if ($request->hasFile('quotation_files')) {
            $uploaded = $this->storage->uploadMultiple(
                $request->file('quotation_files', []),
                'quotation-requests/' . $batchId . '/quotations'
            );
            if (!empty($uploaded)) {
                $quotationUrls = $uploaded;
            }
        }

        $sentAt = now();

        $batch->update([
            'project_budget'  => $projectBudget,
            'markup'          => $markup,
            'contract_value'  => $contractValue,
            'quotation_files' => $quotationUrls,
        ]);

        QuotationRequest::where('batch_id', $batchId)->update([
            'status'            => 'quotation_sent',
            'quotation_sent_at' => $sentAt,
            // Clear any earlier revision-request reason — this fresh send addresses it.
            'decline_reason'    => null,
        ]);

        $firstRequest = QuotationRequest::where('batch_id', $batchId)->first();
        if ($firstRequest) {
            NotificationService::quotationRequestQuotationSent($firstRequest);
        }

        return redirect()
            ->route('admin.quotation_requests.batch_detail', $batchId)
            ->with('success', 'Quotation sent to the client. Waiting for their approval.');
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
        });

        // "Send to Client" saves first so the modal always shows what is persisted.
        $routeParams = ['batchId' => $batchId];
        if ($request->boolean('open_send_modal')) {
            $routeParams['open_send'] = 1;
        }

        return redirect()
            ->route('admin.quotation_requests.batch_detail', $routeParams)
            ->with('success', $summary ? 'Quotation saved — ' . implode(', ', $summary) . '.' : 'Quotation saved.');
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
