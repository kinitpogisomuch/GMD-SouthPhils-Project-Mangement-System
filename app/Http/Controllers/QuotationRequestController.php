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
use App\Http\Controllers\Concerns\BackdatesRecords;

class QuotationRequestController extends Controller
{
    use BackdatesRecords;

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
            'submitted_date'                    => 'nullable|date',
            'submitted_time'                    => 'nullable|date_format:H:i',
        ], [
            'tank_items.required'                     => 'Please add at least one tank requirement.',
            'tank_items.min'                          => 'Please add at least one tank requirement.',
            'tank_items.*.tank_type.required'         => 'Choose a tank type for every tank.',
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
                'tank_type'       => $item['tank_type'],
                'capacity'        => $item['capacity'],
                'quantity'        => $item['quantity'],
                'target_timeline' => $item['target_timeline'] ?? null,
                'fulfillment'     => $delivery ? 'delivery' : 'pickup',
                'location'        => $delivery ? trim($item['address']) : null,
                'notes'           => $request->notes,
                'reference_files' => !empty($designUrls) ? $designUrls : null,
                'status'          => 'pending',
            ]);
            $this->applyBackdate($quotationRequest, $request->submitted_date, $request->submitted_time);
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

        $request->validate([
            'approved_date' => 'nullable|date',
            'approved_time' => 'nullable|date_format:H:i',
        ]);

        // Backdated when logging an approval that actually happened in the past.
        $approvedAt = $request->filled('approved_date')
            ? \Carbon\Carbon::parse($request->approved_date . ' ' . ($request->approved_time ?: '00:00'))
            : now();

        QuotationRequest::where('batch_id', $batchId)->update([
            'status'      => 'approved',
            'approved_at' => $approvedAt,
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

        QuotationRequest::where('batch_id', $batchId)->update([
            'status'         => 'declined',
            'decline_reason' => $request->input('reason'),
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

        return response()->json([
            'quotation_batch_id'      => $batchId,
            'estimated_working_days'  => $batch->estimated_working_days,
            'client' => [
                'name'    => $client->name,
                'contact' => $client->contact,
                'email'   => $client->email,
                'address' => $client->address,
            ],
            'tank_items' => $requests->map(fn ($qr) => [
                'tank_type'       => $qr->tank_type,
                'quantity'        => $qr->quantity,
                'capacity'        => $qr->capacity,
                'target_timeline' => $qr->target_timeline_display,
            ])->values(),
            'reference_files' => $referenceFiles,
            'summary' => [
                'notes' => $requests->first()->notes,
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
        $materialFactor  = $materials->first()->factor ?? 0;   // new quotations start at 0% until a factor is set

        $laborEntries      = ProjectLabor::where('quotation_batch_id', $batchId)
            ->orderBy('created_at')
            ->get();
        $activeLabor       = $laborEntries->where('status', 'active');
        $totalLaborEntries = $activeLabor->count();
        $totalLaborCost    = $activeLabor->sum('total_cost');

        $estimatedBudget = $batch->estimatedBudget();
        $batchStatus     = $tankItems->first()->status ?? 'pending';

        $regularEmployees = Employee::where('status', 'Active')
            ->where('employee_type', 'Regular')
            ->orderBy('last_name')
            ->get();

        return view('admin.quotation_batch_detail', compact(
            'batch', 'tankItems', 'batchStatus',
            'materials', 'totalMaterials', 'estimatedCost', 'materialFactor',
            'laborEntries', 'totalLaborEntries', 'totalLaborCost',
            'estimatedBudget', 'regularEmployees'
        ));
    }

    /**
     * Markup and Payment Terms are set independently on the page (outside the
     * Send-to-Client modal), before sending. Payment Terms are picked here, ahead
     * of conversion, so ProjectController::store() can auto-create the Payment
     * record on "Convert to Project" instead of a separate manual setup step.
     */
    public function updateMarkup(Request $request, $batchId)
    {
        $batch = $this->resolveBatch($batchId);

        $validated = $request->validate([
            'markup_percent'     => 'required|numeric|min:0|max:100',
            'payment_term_type'  => 'nullable|in:big_project,small_project',
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
            'sent_date'          => 'nullable|date',
            'sent_time'          => 'nullable|date_format:H:i',
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

        // Backdated when logging a quotation that was actually sent in the past.
        $sentAt = $request->filled('sent_date')
            ? \Carbon\Carbon::parse($request->sent_date . ' ' . ($request->sent_time ?: '00:00'))
            : now();

        $batch->updated_at = $sentAt;
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
            'entry_date'         => 'nullable|date',
            'entry_time'         => 'nullable|date_format:H:i',
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
        $batch = $this->resolveBatch($batchId);

        $request->validate([
            'estimated_working_days' => 'required|numeric|min:0',
            'employee_name'          => 'required|array|min:1',
            'employee_name.*'        => 'required|string|max:255',
            'role'                   => 'nullable|array',
            'role.*'                 => 'nullable|string|max:255',
            'daily_rate'             => 'required|array|min:1',
            'daily_rate.*'           => 'required|numeric|min:0',
            'entry_date'             => 'nullable|date',
            'entry_time'             => 'nullable|date_format:H:i',
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
            $this->applyBackdate($labor, $request->entry_date, $request->entry_time);
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
            $this->applyBackdate($material, $request->entry_date, $request->entry_time);
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
    public function saveAll(Request $request, $batchId)
    {
        $batch = $this->resolveBatch($batchId);

        $request->validate([
            'markup_percent'         => 'required|numeric|min:0|max:100',
            'payment_term_type'      => 'required|in:big_project,small_project',
            'factor'                 => 'required|numeric|min:0|max:100',
            'estimated_working_days' => 'required|numeric|gt:0',
            'entry_date'             => 'nullable|date',
            'entry_time'             => 'nullable|date_format:H:i',

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
        ], [], [
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
                $this->applyBackdate($labor, $request->entry_date, $request->entry_time);
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
        $material = ProjectMaterial::where('quotation_batch_id', $batchId)->findOrFail($materialId);
        $name     = $material->material_name;
        $material->delete();

        return redirect()
            ->route('admin.quotation_requests.batch_detail', $batchId)
            ->with('success', "Material \"{$name}\" deleted.");
    }

    public function updateEstimatedDays(Request $request, $batchId)
    {
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
        $entry         = ProjectLabor::where('quotation_batch_id', $batchId)->findOrFail($laborId);
        $entry->status = $entry->status === 'archived' ? 'active' : 'archived';
        $entry->save();

        $label = $entry->status === 'archived' ? 'archived' : 'restored';

        return redirect()
            ->route('admin.quotation_requests.batch_detail', $batchId)
            ->with('success', "Labor entry \"{$entry->description}\" {$label} successfully.");
    }
}
