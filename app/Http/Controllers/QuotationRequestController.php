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

        return view('client.quotation_request', [
            'client'         => $client,
            'tankTypes'      => ProjectTankItem::TANK_TYPES,
            'pendingBatches' => $pendingBatches,
        ]);
    }

    public function store(Request $request)
    {
        $client = Client::findOrFail(session('user_id'));

        $request->validate([
            'tank_items'                    => 'nullable|array',
            'tank_items.*.tank_type'        => 'required|string|in:' . implode(',', ProjectTankItem::TANK_TYPES),
            'tank_items.*.capacity'         => 'nullable|string|max:255',
            'tank_items.*.quantity'         => 'nullable|integer|min:1',
            'tank_items.*.target_timeline'  => 'nullable|date|after_or_equal:today',
            'location'                      => 'required|string|max:1000',
            'notes'                         => 'nullable|string|max:2000',
            'reference_files'               => 'nullable|array|max:5',
            // "extensions" (not "mimes") because CAD tools like AutoCAD don't produce a
            // MIME type PHP's file-info can reliably sniff — Laravel's "mimes" rule would
            // reject valid .dwg uploads, so we trust the file's extension instead.
            'reference_files.*'             => 'file|extensions:pdf,jpg,jpeg,png,dwg|max:10240',
        ]);

        $tankItems = $request->input('tank_items', []);

        // A client can fill in tank specs, attach photos of a tank they already
        // own, or both — but the request needs at least one of the two.
        if (empty($tankItems) && !$request->hasFile('reference_files')) {
            return redirect()->back()
                ->withErrors(['tank_items' => 'Please add at least one tank requirement, or attach a photo of your existing tank.'])
                ->withInput();
        }

        // Each tank the client adds becomes its own independent quotation request —
        // its own status, its own quotation file, its own approve/decline — tagged
        // with a shared batch_id purely so the UI can show "submitted together".
        $batchId = (string) Str::uuid();

        // Optional photos/files of a tank the client already owns — shared across
        // the whole batch, not per tank, so it's uploaded once and copied to each row.
        $referenceUrls = $this->storage->uploadMultiple(
            $request->file('reference_files', []),
            'quotation-requests/' . $batchId . '/reference'
        );

        // No tank specs at all means the client is only sending their own tank —
        // still create one row so the reference photos have somewhere to live.
        if (empty($tankItems)) {
            $tankItems = [['tank_type' => null, 'capacity' => null, 'quantity' => 1, 'target_timeline' => null]];
        }

        $created = collect($tankItems)->map(function ($item) use ($client, $batchId, $request, $referenceUrls) {
            return QuotationRequest::create([
                'client_id'       => $client->id,
                'batch_id'        => $batchId,
                'tank_type'       => $item['tank_type'] ?? null,
                'capacity'        => $item['capacity'] ?? null,
                'quantity'        => $item['quantity'] ?? 1,
                'target_timeline' => $item['target_timeline'] ?? null,
                'location'        => $request->location,
                'notes'           => $request->notes,
                'reference_files' => !empty($referenceUrls) ? $referenceUrls : null,
                'status'          => 'pending',
            ]);
        });

        $created->each(fn ($qr) => NotificationService::quotationRequestSubmitted($qr));

        $message = $created->count() > 1
            ? 'Your ' . $created->count() . ' quotation requests have been submitted! Our team will review them shortly.'
            : 'Your request has been submitted! Our team will review it shortly.';

        return redirect()->route('client.quotation.create')->with('success', $message);
    }

    public function status()
    {
        $client = Client::findOrFail(session('user_id'));

        // History is for finished business only — active/in-review requests live on
        // the "Request Quotation" page instead, so they aren't shown twice.
        $requests = QuotationRequest::where('client_id', $client->id)
            ->whereIn('status', ['converted', 'declined'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn ($r) => $r->batch_id ?: ('single-' . $r->id));

        return view('client.quotation_status', compact('requests'));
    }

    /** Approves every tank request in the batch together — the client reviews and decides on one combined quotation. */
    public function approveQuotation($batchId)
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

        NotificationService::quotationRequestApproved($requests->first());

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
            'reason' => 'nullable|string|max:1000',
        ]);

        QuotationRequest::where('batch_id', $batchId)->update([
            'status'         => 'declined',
            'decline_reason' => $request->input('reason'),
        ]);

        NotificationService::quotationRequestRejected($requests->first()->fresh());

        return redirect()->route('client.quotation.create')
            ->with('success', 'Quotation rejected. Our team will follow up if needed.');
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
        $clientGroups = $projects->groupBy('client')->map(function ($group, $client) {
            return [
                'client'    => $client,
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
     * Creates the Project from an approved batch in one step — tank items come from
     * the QuotationRequest rows (admin only adds shape/dimensions here), and the
     * materials/labor already entered during Build Quotation are RE-PARENTED
     * (quotation_batch_id -> project_id) rather than re-entered. The Payment record
     * is created immediately too, using the markup/contract value already agreed
     * with the client at Send-to-Client time.
     */
    public function convertToProject(Request $request, $batchId)
    {
        $requests = QuotationRequest::where('batch_id', $batchId)->orderBy('created_at')->get();

        if ($requests->isEmpty()) {
            abort(404);
        }
        if ($requests->first()->status !== 'approved') {
            return redirect()->route('admin.quotation_requests')
                ->with('error', 'This quotation must be approved by the client before it can be converted to a project.');
        }

        $batch = $this->resolveBatch($batchId);
        $batch->load('client');
        $client = $batch->client;

        $request->validate([
            'name'              => 'required|string|max:255',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after_or_equal:start_date',
            'payment_term_type' => 'required|in:big_project,small_project',
            'shape'             => 'required|array|size:' . $requests->count(),
            'shape.*'           => 'required|string|max:100',
            'dimensions'        => 'required|array|size:' . $requests->count(),
            'dimensions.*'      => 'required|string|max:255',
        ]);

        $start    = \Carbon\Carbon::parse($request->start_date);
        $end      = \Carbon\Carbon::parse($request->end_date);
        $duration = $start->diffInDays($end) . ' days';
        $firstTank = $requests->first();

        $project = Project::create([
            'name'                    => $request->name,
            'client'                  => $client->name,
            'contact_number'          => $client->contact,
            'email'                   => $client->email,
            'address'                 => $client->address,
            'client_type'             => 'Corporate',
            'tank_type'               => $firstTank->tank_type,
            'capacity'                => $firstTank->capacity ?? '',
            'dimensions'              => $request->dimensions[0] ?? null,
            'start_date'              => $request->start_date,
            'end_date'                => $request->end_date,
            'payment_status'          => 'Pending',
            'status'                  => 'planning',
            'progress'                => 0,
            'current_phase'           => 'planning',
            'current_sub_phase'       => 'shop_drawing',
            'duration'                => $duration,
            'estimated_working_days'  => $batch->estimated_working_days,
        ]);

        foreach ($requests as $i => $qr) {
            ProjectTankItem::create([
                'project_id' => $project->id,
                'tank_type'  => $qr->tank_type,
                'shape'      => $request->shape[$i] ?? '',
                'capacity'   => $qr->capacity,
                'dimensions' => $request->dimensions[$i] ?? '',
                'quantity'   => $qr->quantity,
                'sort_order' => $i,
            ]);
        }

        // Carry the quotation-stage BOM over instead of re-entering it.
        ProjectMaterial::where('quotation_batch_id', $batchId)->update([
            'project_id'         => $project->id,
            'quotation_batch_id' => null,
        ]);
        ProjectLabor::where('quotation_batch_id', $batchId)->update([
            'project_id'         => $project->id,
            'quotation_batch_id' => null,
        ]);

        $contractAmount = (float) ($batch->contract_value ?? 0);
        $termLabel = $request->payment_term_type === 'big_project'
            ? '3 Phases (50% / 30% / 20%)'
            : '2 Phases (50% / 50%)';

        \App\Models\Payment::create([
            'project_id'        => $project->id,
            'client'            => $client->name,
            'client_type'       => 'Corporate',
            'contract_amount'   => $contractAmount,
            'project_budget'    => $batch->project_budget,
            'markup'            => $batch->markup,
            'down_payment'      => round($contractAmount * 0.5, 2),
            'balance'           => $contractAmount,
            'status'            => 'Pending Down Payment',
            'payment_terms'     => $termLabel,
            'payment_term_type' => $request->payment_term_type,
            'date'              => now()->toDateString(),
        ]);

        QuotationRequest::where('batch_id', $batchId)->update([
            'status'             => 'converted',
            'related_project_id' => $project->id,
        ]);

        return redirect()->route('admin.project_view', $project->id)
            ->with('success', "\"{$project->name}\" was created from the approved quotation.");
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
        $materialFactor  = $materials->first()->factor ?? 7;

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

    public function sendBatchQuotation(Request $request, $batchId)
    {
        $batch = $this->resolveBatch($batchId);

        $request->validate([
            'markup'             => 'required|numeric|min:0',
            'quotation_files'    => 'nullable|array|max:5',
            'quotation_files.*'  => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        // Snapshotted here, never recomputed live afterward — see migration comment.
        $projectBudget = $batch->estimatedBudget()['total'];
        $markup        = (float) $request->input('markup');
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

        $batch->update([
            'project_budget'  => $projectBudget,
            'markup'          => $markup,
            'contract_value'  => $contractValue,
            'quotation_files' => $quotationUrls,
        ]);

        QuotationRequest::where('batch_id', $batchId)->update([
            'status'            => 'quotation_sent',
            'quotation_sent_at' => now(),
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
        ]);

        $ids       = $request->input('material_id', []);
        $deleteIds = $request->input('delete_material_id', []);
        $names     = $request->input('material_name');
        $qtys      = $request->input('quantity');
        $prices    = $request->input('price_per_unit');
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
            $qty   = (float) $qtys[$i];
            $price = (float) $prices[$i];
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

            ProjectMaterial::create([
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

            $createdCount++;
        }

        // The Material Factor applies to the whole quotation — keep every material's factor in sync.
        ProjectMaterial::where('quotation_batch_id', $batchId)->update(['factor' => $factor]);

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
        ]);

        $batch->update(['estimated_working_days' => $request->input('estimated_working_days')]);

        $names = $request->input('employee_name');
        $roles = $request->input('role', []);
        $rates = $request->input('daily_rate');

        foreach ($names as $i => $name) {
            $rate        = (float) $rates[$i];
            $role        = trim($roles[$i] ?? '');
            $description = $role ? "{$name} ({$role})" : $name;

            ProjectLabor::create([
                'quotation_batch_id' => $batchId,
                'description'        => $description,
                'daily_rate'         => $rate,
                'total_cost'         => round($rate * $batch->estimated_working_days, 2),
                'status'             => 'active',
            ]);
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
