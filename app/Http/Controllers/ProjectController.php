<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Project;
use App\Models\ProjectTankItem;
use App\Models\ProjectMaterial;
use App\Models\ProjectLabor;
use App\Models\ProjectTemplate;
use App\Models\ProjectUpdate;
use App\Models\ProgressRequest;
use App\Models\Client;
use App\Models\Employee;
use App\Models\FundTransaction;
use App\Models\PaymentTransaction;
use App\Services\SupabaseStorageService;
use App\Services\NotificationService;

class ProjectController extends Controller
{
    protected $storage;

    public function __construct(SupabaseStorageService $storage)
    {
        $this->storage = $storage;
    }

    private $phases = [
        'planning','procurement','matl_prep',
        'fabrication','inspection','painting',
        'completion','delivery',
    ];

    private $progressMap = [
        'planning'    => 5,
        'procurement' => 15,
        'matl_prep'   => 25,
        'fabrication' => 60,
        'inspection'  => 75,
        'painting'    => 85,
        'completion'  => 95,
        'delivery'    => 100,
    ];

    /*
    |--------------------------------------------------------------------------
    | Admin View
    |--------------------------------------------------------------------------
    */
    public function adminView($id)
    {
        $project = Project::with('tankItems')->find($id);
        if (!$project) {
            return redirect()->route('admin.projects')
                ->with('error', 'That project no longer exists.');
        }

        $updates = ProjectUpdate::where('project_id', $id)
                    ->with('submittedBy')
                    ->orderBy('created_at', 'desc')
                    ->get();

        $openRequest = ProgressRequest::where('project_id', $id)
                        ->where('status', 'open')
                        ->first();

        $pendingUpdates = ProjectUpdate::where('project_id', $id)
                            ->where('status', 'pending_review')
                            ->get();

        $currentIndex = array_search($project->current_phase, $this->phases);
        $nextPhase    = ($currentIndex !== false && $currentIndex < count($this->phases) - 1)
                        ? $this->phases[$currentIndex + 1]
                        : null;

        // Resolve client details: prefer fields stored on the project,
        // fall back to the matching record in the clients table.
        $clientRecord   = Client::where('name', $project->client)->first();
        $clientAddress  = $project->address        ?? ($clientRecord?->address  ?? null);
        $clientContact  = $project->contact_number ?? ($clientRecord?->contact  ?? null);
        $clientEmail    = $project->email          ?? ($clientRecord?->email    ?? null);

        // ── Financial data ──────────────────────────────────────────────
        $payment        = $project->getPaymentRecord();

        // Raw estimated costs from BOM and labor records
        $rawMatCost   = (float) $project->activeMaterials()->sum('total_cost');
        $rawLaborCost = (float) $project->activeLabor()->sum('total_cost');
        $rawTotal     = $rawMatCost + $rawLaborCost;

        // Project Quotation total (BOM with markup + labor) — used for BOM/print view
        $projectGrandTotal = $project->activeMaterials()->get()->sum(function ($material) {
            $factor = $material->factor ?? 7;
            return round((float) $material->total_cost * (1 + $factor / 100), 2);
        }) + $rawLaborCost;

        // Contract Value = the agreed total project cost from the payment record
        $contractAmount = $payment ? (float) $payment->contract_amount : $projectGrandTotal;

        // Scale Est. Materials and Est. Labor proportionally so they sum to the Contract Value
        if ($rawTotal > 0 && $contractAmount > 0) {
            $matRatio        = $rawMatCost / $rawTotal;
            $estMaterialCost = round($contractAmount * $matRatio, 2);
            $estLaborCost    = round($contractAmount - $estMaterialCost, 2);
        } else {
            $estMaterialCost = $rawMatCost;
            $estLaborCost    = $rawLaborCost;
        }

        $estTotalCost = $estMaterialCost + $estLaborCost; // always equals $contractAmount

        // Total received across all payment stages
        $totalReceived = $payment
            ? (float) PaymentTransaction::where('payment_id', $payment->id)->sum('amount_paid')
            : 0;

        // Budget = total amount received from client
        $budgetReceived = $totalReceived;

        // Actual material cost = sum of all material purchases for this project
        $actMaterialCost = (float) \App\Models\MaterialPurchase::where('project_id', $project->id)->sum('total_paid');

        // Actual labor cost = sum of allocated_pay from salary_record_project pivot
        $actLaborCost = (float) \DB::table('salary_record_project')
            ->where('project_id', $project->id)
            ->sum('allocated_pay');

        // Monthly overhead share allocated to this project
        $overheadShare = (float) \DB::table('monthly_expense_projects')
            ->where('project_id', $project->id)
            ->sum('allocated_amount');

        // Remaining budget = budget received - actual spending (materials + labor + overhead)
        $remainingBudget = $budgetReceived - ($actMaterialCost + $actLaborCost + $overheadShare);

        // Variance vs actual spending
        $matVariance   = $estMaterialCost - $actMaterialCost;
        $laborVariance = $estLaborCost    - $actLaborCost;

        // Net Profit = Contract Value - Materials - Labor - Overhead Share
        $profit = $contractAmount > 0
            ? $contractAmount - $actMaterialCost - $actLaborCost - $overheadShare
            : null;

        // Legacy aliases (scaled values)
        $materialCost = $estMaterialCost;
        $laborCost    = $estLaborCost;

        // Revolving Fund Summary for this project
        $fundReleased    = FundTransaction::totalReleased($project->id);
        $fundReplenished = FundTransaction::totalReplenished($project->id);
        $fundOutstanding = $fundReleased - $fundReplenished;
        $fundHistory     = FundTransaction::where('project_id', $project->id)
            ->orderByDesc('date')->orderByDesc('id')->get();

        return view('admin.project_view', compact(
            'project', 'updates', 'openRequest', 'pendingUpdates', 'nextPhase',
            'clientAddress', 'clientContact', 'clientEmail',
            // financial
            'payment', 'contractAmount', 'budgetReceived', 'totalReceived', 'remainingBudget',
            'estMaterialCost', 'actMaterialCost', 'matVariance',
            'estLaborCost', 'actLaborCost', 'laborVariance',
            'estTotalCost', 'projectGrandTotal', 'overheadShare',
            // legacy
            'materialCost', 'laborCost', 'profit',
            // fund
            'fundReleased', 'fundReplenished', 'fundOutstanding', 'fundHistory'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Client View
    |--------------------------------------------------------------------------
    */
    public function clientView($id)
    {
        $project = Project::find($id);
        if (!$project) {
            return redirect()->route('client.projects')
                ->with('error', 'That project no longer exists.');
        }

        // Security: ensure this project belongs to the logged-in client
        $clientEmail = session('email');
        $clientName  = $clientEmail
            ? \App\Models\Client::where('email', $clientEmail)->value('name')
            : null;

        if (!$clientName || $project->client !== $clientName) {
            abort(403, 'You do not have permission to view this project.');
        }

        $updates = ProjectUpdate::where('project_id', $id)
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'desc')
                    ->with('submittedBy')
                    ->get();

        return view('client.project_view', compact('project', 'updates'));
    }

    /*
    |--------------------------------------------------------------------------
    | Client Approves Shop Drawing / Tank Design
    |--------------------------------------------------------------------------
    */
    public function approveShopDrawing($id)
    {
        $project = Project::findOrFail($id);

        $clientEmail = session('email');
        $clientName  = $clientEmail ? Client::where('email', $clientEmail)->value('name') : null;

        if (!$clientName || $project->client !== $clientName) {
            abort(403, 'You do not have permission to view this project.');
        }

        if ($project->phaseData('planning.shop_drawing.status') !== 'pending_approval') {
            return redirect()->route('client.project_view', $id)
                ->with('error', 'There is no shop drawing pending your approval.');
        }

        $shopDrawing = $project->phaseData('planning.shop_drawing', []);
        $shopDrawing['status'] = 'approved';
        $shopDrawing['revision_notes'] = null;
        $project->setPhaseData('planning.shop_drawing', $shopDrawing);

        $newProgress = Project::SUBPHASE_PROGRESS['shop_drawing'];

        $project->update([
            'current_sub_phase' => 'quotation',
            'progress'          => $newProgress,
        ]);

        NotificationService::shopDrawingApproved($project);

        return redirect()->route('client.project_view', $id)
            ->with('success', 'Shop drawing and tank design approved!');
    }

    /*
    |--------------------------------------------------------------------------
    | Client Requests Revision of Shop Drawing / Tank Design
    |--------------------------------------------------------------------------
    */
    public function requestShopDrawingRevision(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $clientEmail = session('email');
        $clientName  = $clientEmail ? Client::where('email', $clientEmail)->value('name') : null;

        if (!$clientName || $project->client !== $clientName) {
            abort(403, 'You do not have permission to view this project.');
        }

        $request->validate(['revision_notes' => 'required|string']);

        if ($project->phaseData('planning.shop_drawing.status') !== 'pending_approval') {
            return redirect()->route('client.project_view', $id)
                ->with('error', 'There is no shop drawing pending your approval.');
        }

        $shopDrawing = $project->phaseData('planning.shop_drawing', []);
        $shopDrawing['status'] = 'revision_requested';
        $shopDrawing['revision_notes'] = $request->revision_notes;
        $project->setPhaseData('planning.shop_drawing', $shopDrawing);

        NotificationService::shopDrawingRevisionRequested($project, $request->revision_notes);

        return redirect()->route('client.project_view', $id)
            ->with('success', 'Revision request sent to the project owner.');
    }

    /*
    |--------------------------------------------------------------------------
    | Employee View
    |--------------------------------------------------------------------------
    | Single source of truth: the latest ProgressRequest for this project
    | drives what form (if any) the employee sees.
    |
    | ProgressRequest.status:
    |   'open'               → show Progress Update Form
    |   'revision_requested' → show Revision Form  (links to $revisionUpdate)
    |   'completed'          → show nothing
    |   (none)               → show nothing
    |--------------------------------------------------------------------------
    */
    public function employeeView($id)
    {
        $project = Project::find($id);
        if (!$project) {
            return redirect()->route('employee.projects')
                ->with('error', 'That project no longer exists.');
        }

        // The latest request is the single source of truth for form visibility
        $latestRequest = ProgressRequest::where('project_id', $id)
                            ->latest()
                            ->first();

        $showProgressForm  = false;
        $showRevisionForm  = false;
        $openRequest       = null;
        $revisionUpdate    = null;

        if ($latestRequest) {
            if ($latestRequest->status === 'open') {
                $showProgressForm = true;
                $openRequest      = $latestRequest;
            } elseif ($latestRequest->status === 'revision_requested') {
                $showRevisionForm = true;
                // Get the specific update that needs revision (linked via the request)
                $revisionUpdate = ProjectUpdate::where('project_id', $id)
                                    ->where('status', 'needs_revision')
                                    ->where('type', 'employee_submission')
                                    ->latest()
                                    ->first();
            }
            // 'completed' → both false, no form shown
        }

        // Employee only sees approved updates in history
        $updates = ProjectUpdate::where('project_id', $id)
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'desc')
                    ->get();

        return view('employee.project_view', compact(
            'project', 'openRequest', 'revisionUpdate',
            'showProgressForm', 'showRevisionForm', 'updates'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Store New Project
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'tank_items'           => 'required|array|min:1',
            'tank_items.*.tank_type' => 'required|string',
            'tank_items.*.capacity'  => 'nullable|string',
            'tank_items.*.quantity'  => 'nullable|integer|min:1',
            'materials'                    => 'nullable|array',
            'materials.*.material_name'    => 'required|string|max:255',
            'materials.*.quantity'         => 'nullable|numeric|min:0.01',
            'materials.*.unit'             => 'nullable|string|max:50',
            'materials.*.price_per_unit'   => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'client'     => 'required|string',
            'quotation_request_id' => 'nullable|integer|exists:quotation_requests,id',
        ]);

        $start    = \Carbon\Carbon::parse($request->start_date);
        $end      = \Carbon\Carbon::parse($request->end_date);
        $duration = $start->diffInDays($end) . ' days';

        $name = ($request->name === 'others' || !$request->name)
            ? $request->name_other
            : $request->name;

        if (!$name) {
            return redirect()->back()->with('error', 'Please provide a project name.')->withInput();
        }

        // Use first tank item as the project-level summary fields
        $firstTank = $request->tank_items[0];

        $project = Project::create([
            'name'              => $name,
            'client'            => $request->client,
            'contact_number'    => $request->contact_number,
            'email'             => $request->email,
            'address'           => $request->address,
            'client_type'       => $request->client_type ?? 'Corporate',
            'tank_type'         => $firstTank['tank_type'],
            'capacity'          => $firstTank['capacity'] ?? '',
            'dimensions'        => $firstTank['dimensions'] ?? null,
            'start_date'        => $request->start_date,
            'end_date'          => $request->end_date,
            'payment_status'    => $request->payment_status ?? 'Pending',
            'status'            => 'planning',
            'progress'          => 0,
            'current_phase'     => 'planning',
            'current_sub_phase' => 'shop_drawing',
            'duration'          => $duration,
            'notes'             => $request->notes,
        ]);

        if ($request->filled('quotation_request_id')) {
            \App\Models\QuotationRequest::where('id', $request->quotation_request_id)->update([
                'status'              => 'converted',
                'related_project_id'  => $project->id,
            ]);
        }

        // Save all tank items
        foreach ($request->tank_items as $i => $item) {
            ProjectTankItem::create([
                'project_id'  => $project->id,
                'tank_type'   => $item['tank_type'],
                'shape'       => $item['shape'] ?? null,
                'capacity'    => $item['capacity'] ?? null,
                'dimensions'  => $item['dimensions'] ?? null,
                'quantity'    => $item['quantity'] ?? 1,
                'notes'       => $item['notes'] ?? null,
                'sort_order'  => $i,
            ]);
        }

        // Save the bill of materials (if provided) as the project's initial BOM.
        // Prices can be left blank and filled in later on the Project Materials page.
        foreach ($request->input('materials', []) as $material) {
            if (empty($material['material_name'])) {
                continue;
            }

            $quantity  = $material['quantity'] ?? 1;
            $unitPrice = $material['price_per_unit'] ?? 0;

            ProjectMaterial::create([
                'project_id'     => $project->id,
                'material_name'  => $material['material_name'],
                'quantity'       => $quantity,
                'unit'           => $material['unit'] ?? '',
                'price_per_unit' => $unitPrice,
                'total_cost'     => round($quantity * $unitPrice, 2),
                'factor'         => 7,
                'status'         => 'active',
            ]);
        }

        // Auto-save tank specs (and materials) as a reusable template for custom
        // (not from-template) projects, skipping it when an identical template already exists.
        if (!$request->boolean('from_existing_template')) {
            $normalizedItems = $this->normalizeTankItems($request->tank_items);

            $isDuplicate = ProjectTemplate::all()->contains(
                fn ($tpl) => $this->normalizeTankItems($tpl->tank_items) === $normalizedItems
            );

            if (!$isDuplicate) {
                ProjectTemplate::create([
                    'project_id'   => $project->id,
                    'name'         => $name,
                    'project_name' => $name,
                    'tank_items'   => array_values($request->tank_items),
                    'materials'    => array_values($request->input('materials', [])),
                ]);
            }
        }

        NotificationService::projectCreated($project);

        return redirect()->route('admin.projects.client', $project->client)->with('success', 'Project created successfully!');
    }

    /*
    |--------------------------------------------------------------------------
    | Project Templates (reusable tank specs)
    |--------------------------------------------------------------------------
    */
    public function destroyTemplate($id)
    {
        ProjectTemplate::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Reduce a tank_items array to just the fields that matter for duplicate detection.
     */
    private function normalizeTankItems($items)
    {
        return collect($items)->map(fn ($item) => [
            'tank_type'  => $item['tank_type']  ?? null,
            'capacity'   => $item['capacity']   ?? null,
            'dimensions' => $item['dimensions'] ?? null,
            'quantity'   => (int) ($item['quantity'] ?? 1),
        ])->values()->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Update Project Details
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'notes'      => 'nullable|string',
            'tank_items'             => 'required|array|min:1',
            'tank_items.*.tank_type' => 'required|string',
            'tank_items.*.capacity'  => 'nullable|string',
            'tank_items.*.quantity'  => 'nullable|integer|min:1',
        ]);

        $project  = Project::findOrFail($id);
        $start    = \Carbon\Carbon::parse($request->start_date);
        $end      = \Carbon\Carbon::parse($request->end_date);
        $duration = $start->diffInDays($end) . ' days';

        $firstTank = $request->tank_items[0];

        $project->update([
            'name'       => $request->name,
            'tank_type'  => $firstTank['tank_type'],
            'capacity'   => $firstTank['capacity'] ?? $project->capacity,
            'dimensions' => $firstTank['dimensions'] ?? $project->dimensions,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'duration'   => $duration,
            'notes'      => $request->notes,
        ]);

        // Replace all tank items
        $project->tankItems()->delete();
        foreach ($request->tank_items as $i => $item) {
            ProjectTankItem::create([
                'project_id'  => $project->id,
                'tank_type'   => $item['tank_type'],
                'shape'       => $item['shape'] ?? null,
                'capacity'    => $item['capacity'] ?? null,
                'dimensions'  => $item['dimensions'] ?? null,
                'quantity'    => $item['quantity'] ?? 1,
                'notes'       => $item['notes'] ?? null,
                'sort_order'  => $i,
            ]);
        }

        return redirect()->route('admin.projects.client', $project->client)->with('success', 'Project updated successfully!');
    }

    /*
    |--------------------------------------------------------------------------
    | Archive / Unarchive Project
    |--------------------------------------------------------------------------
    */
    public function archive($id)
    {
        $project = Project::findOrFail($id);

        if ($project->status === 'archived') {
            $project->update(['status' => 'planning']);
            return redirect()->route('admin.projects.client', $project->client)->with('success', 'Project restored successfully.');
        }

        $project->update(['status' => 'archived']);
        return redirect()->route('admin.projects.client', $project->client)->with('success', 'Project archived.');
    }

    /*
    |--------------------------------------------------------------------------
    | Assign Employees to Project
    |--------------------------------------------------------------------------
    */
    public function assignEmployees(Request $request, $id)
    {
        $request->validate([
            'employee_ids'   => 'array',
            'employee_ids.*' => 'integer|exists:employees,id',
        ]);

        $project = Project::findOrFail($id);
        $result  = $project->assignedEmployees()->sync($request->employee_ids ?? []);

        $this->addNewlyAssignedEmployeesToLabor($project, $result['attached']);

        return redirect()->route('admin.projects.client', $project->client)->with('success', "Employee assignments updated for \"{$project->name}\".");
    }

    /**
     * Newly assigned employees automatically get a Labor Cost entry on the Project Materials
     * (Quotation) page, using their real daily rate — skips anyone who already has an active
     * labor entry for this project so re-assigning doesn't create duplicates.
     */
    private function addNewlyAssignedEmployeesToLabor(Project $project, array $newlyAssignedIds): void
    {
        if (empty($newlyAssignedIds)) {
            return;
        }

        $employees = Employee::whereIn('id', $newlyAssignedIds)
            ->where('employee_type', 'Regular')
            ->get();

        if ($employees->isEmpty()) {
            return;
        }

        $existingNames = ProjectLabor::where('project_id', $project->id)
            ->where('status', 'active')
            ->pluck('description')
            ->map(fn ($d) => trim(preg_replace('/\s*\([^)]*\)$/', '', $d)))
            ->all();

        foreach ($employees as $employee) {
            $fullName = trim($employee->first_name . ' ' . $employee->last_name);

            if (in_array($fullName, $existingNames, true)) {
                continue;
            }

            $description = $employee->role ? "{$fullName} ({$employee->role})" : $fullName;
            $dailyRate   = (float) $employee->daily_rate;

            ProjectLabor::create([
                'project_id'  => $project->id,
                'description' => $description,
                'daily_rate'  => $dailyRate,
                'total_cost'  => round($dailyRate * (float) $project->estimated_working_days, 2),
                'status'      => 'active',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Save Progress Update (Admin) — phase-aware dispatcher
    |
    | Each phase (and Planning's sub-phases) has its own requirements that
    | must be satisfied before the project advances. Progress is always
    | derived automatically from Project::PHASE_PROGRESS / SUBPHASE_PROGRESS.
    |--------------------------------------------------------------------------
    */
    public function addUpdate(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        if ($project->current_phase === 'planning') {
            return match ($project->current_sub_phase) {
                'quotation' => $this->handlePlanningQuotation($request, $project),
                'payment'   => $this->handlePlanningPayment($request, $project),
                default     => $this->handlePlanningShopDrawing($request, $project),
            };
        }

        return match ($project->current_phase) {
            'procurement' => $this->handleProcurement($request, $project),
            'matl_prep'   => $this->handleMaterialPrep($request, $project),
            'fabrication' => $this->handleFabrication($request, $project),
            'inspection'  => $this->handleInspection($request, $project),
            'painting'    => $this->handlePainting($request, $project),
            'completion'  => $this->handleCompletion($request, $project),
            'delivery'    => $this->handleDelivery($request, $project),
            default       => redirect()->route('admin.project_view', $id)
                ->with('error', 'No progress update form is available for this phase.'),
        };
    }

    /** Create a ProjectUpdate record for an admin-driven phase advancement */
    private function createAdminUpdate(Project $project, array $overrides = []): ProjectUpdate
    {
        $submittedBy = session('user_id')
            ?? \App\Models\User::where('role', 'admin')->value('id')
            ?? 1;

        return ProjectUpdate::create(array_merge([
            'project_id'   => $project->id,
            'submitted_by' => $submittedBy,
            'type'         => 'admin_direct',
            'update_label' => null,
            'phase'        => $project->current_phase,
            'percentage'   => $project->progress,
            'date_of_work' => now()->toDateString(),
            'issues'       => null,
            'photos'       => [],
            'status'       => 'approved',
        ], $overrides));
    }

    /*
    |--------------------------------------------------------------------------
    | Phase 1.1 — Planning / Shop Drawing & Tank Design
    |--------------------------------------------------------------------------
    */
    private function handlePlanningShopDrawing(Request $request, Project $project)
    {
        if ($request->boolean('skip')) {
            $project->setPhaseData('planning.shop_drawing', [
                'status'       => 'completed',
                'completed_at' => now()->toDateTimeString(),
            ]);

            $newProgress = Project::SUBPHASE_PROGRESS['shop_drawing'];

            $project->update([
                'current_sub_phase' => 'quotation',
                'progress'          => $newProgress,
            ]);

            $this->createAdminUpdate($project, [
                'update_label' => 'shop_drawing',
                'work_done'    => 'Shop drawing and tank design marked as already completed by admin.',
                'percentage'   => $newProgress,
            ]);

            return redirect()->route('admin.project_view', $project->id)
                ->with('success', 'Shop drawing step marked as already completed. Proceed to Project Quotation.');
        }

        $status = $project->phaseData('planning.shop_drawing.status');

        if (in_array($status, ['pending_approval', 'approved'])) {
            return redirect()->route('admin.project_view', $project->id)
                ->with('error', 'The shop drawing has already been submitted and is awaiting client review.');
        }

        $request->validate([
            'shop_drawing_files'   => 'required|array|min:1',
            'shop_drawing_files.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'tank_design_files'    => 'required|array|min:1',
            'tank_design_files.*'  => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $shopDrawingUrls = $this->storage->uploadMultiple($request->file('shop_drawing_files'), 'projects/' . $project->id . '/shop-drawings');
        $tankDesignUrls  = $this->storage->uploadMultiple($request->file('tank_design_files'), 'projects/' . $project->id . '/tank-design');

        if (empty($shopDrawingUrls) || empty($tankDesignUrls)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['shop_drawing_files' => 'File upload failed. Please check your connection and try again.']);
        }

        $project->setPhaseData('planning.shop_drawing', [
            'status'             => 'pending_approval',
            'shop_drawing_files' => $shopDrawingUrls,
            'tank_design_files'  => $tankDesignUrls,
            'revision_notes'     => null,
            'submitted_at'       => now()->toDateTimeString(),
        ]);

        $this->createAdminUpdate($project, [
            'update_label' => 'shop_drawing',
            'work_done'    => 'Shop drawing and tank design documents submitted to the client for review.',
            'photos'       => array_merge($shopDrawingUrls, $tankDesignUrls),
            'status'       => 'pending_approval',
        ]);

        NotificationService::shopDrawingSubmitted($project);

        return redirect()->route('admin.project_view', $project->id)
            ->with('success', 'Shop drawing and tank design sent to the client for review.');
    }

    /*
    |--------------------------------------------------------------------------
    | Phase 1.2 — Planning / Project Quotations
    |--------------------------------------------------------------------------
    */
    private function handlePlanningQuotation(Request $request, Project $project)
    {
        if ($request->boolean('skip')) {
            $project->setPhaseData('planning.quotation', [
                'status'       => 'completed',
                'completed_at' => now()->toDateTimeString(),
            ]);

            $newProgress = Project::SUBPHASE_PROGRESS['quotation'];

            $project->update([
                'current_sub_phase' => 'payment',
                'progress'          => $newProgress,
            ]);

            $this->createAdminUpdate($project, [
                'update_label' => 'quotation',
                'work_done'    => 'Project quotation marked as already completed by admin.',
                'percentage'   => $newProgress,
            ]);

            return redirect()->route('admin.project_view', $project->id)
                ->with('success', 'Quotation step marked as already completed. Waiting for payment settlement.');
        }

        $request->validate([
            'quotation_files'   => 'required|array|min:1',
            'quotation_files.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $quotationUrls = $this->storage->uploadMultiple($request->file('quotation_files'), 'projects/' . $project->id . '/quotations');

        if (empty($quotationUrls)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['quotation_files' => 'File upload failed. Please check your connection and try again.']);
        }

        $project->setPhaseData('planning.quotation', [
            'status'  => 'sent',
            'files'   => $quotationUrls,
            'sent_at' => now()->toDateTimeString(),
        ]);

        $newProgress = Project::SUBPHASE_PROGRESS['quotation'];

        $project->update([
            'current_sub_phase' => 'payment',
            'progress'          => $newProgress,
        ]);

        $this->createAdminUpdate($project, [
            'update_label' => 'quotation',
            'work_done'    => 'Project quotation sent to the client.',
            'percentage'   => $newProgress,
            'photos'       => $quotationUrls,
        ]);

        NotificationService::quotationSent($project);

        return redirect()->route('admin.project_view', $project->id)
            ->with('success', 'Quotation sent to the client. Waiting for payment settlement.');
    }

    /*
    |--------------------------------------------------------------------------
    | Phase 1.3 — Planning / Payment (50% Down Payment)
    |--------------------------------------------------------------------------
    */
    private function handlePlanningPayment(Request $request, Project $project)
    {
        if (!$project->isPaymentStageSettled('down_payment')) {
            return redirect()->route('admin.project_view', $project->id)
                ->with('error', 'Waiting for payment settlement. The 50% down payment must be settled before proceeding.');
        }

        $newProgress = Project::PHASE_PROGRESS['planning'];

        $project->update([
            'current_phase'     => 'procurement',
            'current_sub_phase' => null,
            'progress'          => $newProgress,
            'status'            => 'ongoing',
        ]);

        $this->createAdminUpdate($project, [
            'phase'        => 'planning',
            'update_label' => 'phase_advance',
            'work_done'    => 'Down payment settled. Project advanced to the Procurement phase.',
            'percentage'   => $newProgress,
        ]);

        NotificationService::phaseAdvanced(
            $project,
            'procurement',
            "Your project \"{$project->name}\" has advanced to the Procurement phase."
        );

        return redirect()->route('admin.project_view', $project->id)
            ->with('success', "Payment confirmed! Project advanced to the Procurement phase ({$newProgress}% complete).");
    }

    /*
    |--------------------------------------------------------------------------
    | Phase 2 — Procurement
    |--------------------------------------------------------------------------
    */
    private function handleProcurement(Request $request, Project $project)
    {
        $request->validate([
            'materials_delivered' => 'required|accepted',
        ]);

        $project->setPhaseData('procurement.materials_delivered', true);

        $newProgress = Project::PHASE_PROGRESS['procurement'];

        $project->update([
            'current_phase' => 'matl_prep',
            'progress'      => $newProgress,
        ]);

        $this->createAdminUpdate($project, [
            'phase'      => 'procurement',
            'work_done'  => 'Materials have been delivered and procurement is complete.',
            'percentage' => $newProgress,
        ]);

        NotificationService::phaseAdvanced(
            $project,
            'matl_prep',
            'Materials have been delivered and procurement is complete.'
        );

        return redirect()->route('admin.project_view', $project->id)
            ->with('success', "Procurement complete! Project advanced to Material Preparation ({$newProgress}% complete).");
    }

    /*
    |--------------------------------------------------------------------------
    | Phase 3 — Material Preparation
    |--------------------------------------------------------------------------
    */
    private function handleMaterialPrep(Request $request, Project $project)
    {
        $request->validate([
            'measuring_completed' => 'required|accepted',
            'marking_completed'   => 'required|accepted',
        ]);

        $project->setPhaseData('matl_prep', [
            'measuring_completed' => true,
            'marking_completed'   => true,
        ]);

        $newProgress = Project::PHASE_PROGRESS['matl_prep'];

        $project->update([
            'current_phase' => 'fabrication',
            'progress'      => $newProgress,
        ]);

        $this->createAdminUpdate($project, [
            'phase'      => 'matl_prep',
            'work_done'  => 'Material preparation has been completed.',
            'percentage' => $newProgress,
        ]);

        NotificationService::phaseAdvanced(
            $project,
            'fabrication',
            'Material preparation has been completed.'
        );

        return redirect()->route('admin.project_view', $project->id)
            ->with('success', "Material preparation complete! Project advanced to Fabrication ({$newProgress}% complete).");
    }

    /*
    |--------------------------------------------------------------------------
    | Phase 4 — Fabrication (Big Project requires 30% Progress Payment first)
    |--------------------------------------------------------------------------
    */
    private function handleFabrication(Request $request, Project $project)
    {
        $payment      = $project->getPaymentRecord();
        $isBigProject = $payment && $payment->payment_term_type === 'big_project';

        if ($isBigProject && !$project->isPaymentStageSettled('progress_payment')) {
            return redirect()->route('admin.project_view', $project->id)
                ->with('error', 'Waiting for progress payment settlement. The 30% progress payment must be settled before proceeding.');
        }

        $request->validate([
            'cutting_completed'  => 'required|accepted',
            'assembly_completed' => 'required|accepted',
            'welding_completed'  => 'required|accepted',
            'progress_photos'    => 'nullable|array|max:10',
            'progress_photos.*'  => 'file|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx|max:10240',
        ]);

        $photoUrls = [];
        if ($request->hasFile('progress_photos')) {
            $photoUrls = $this->storage->uploadMultiple($request->file('progress_photos'), 'projects/' . $project->id . '/fabrication');
        }

        $project->setPhaseData('fabrication', [
            'cutting_completed'  => true,
            'assembly_completed' => true,
            'welding_completed'  => true,
            'progress_photos'    => $photoUrls,
        ]);

        $newProgress = Project::PHASE_PROGRESS['fabrication'];

        $project->update([
            'current_phase' => 'inspection',
            'progress'      => $newProgress,
        ]);

        $this->createAdminUpdate($project, [
            'phase'      => 'fabrication',
            'work_done'  => 'Fabrication (cutting, assembly, and welding) has been completed.',
            'percentage' => $newProgress,
            'photos'     => $photoUrls,
        ]);

        NotificationService::phaseAdvanced(
            $project,
            'inspection',
            'Fabrication has been completed. Your project is now ready for inspection.'
        );

        return redirect()->route('admin.project_view', $project->id)
            ->with('success', "Fabrication complete! Project advanced to Inspection ({$newProgress}% complete).");
    }

    /*
    |--------------------------------------------------------------------------
    | Phase 5 — Inspection
    |--------------------------------------------------------------------------
    */
    private function handleInspection(Request $request, Project $project)
    {
        $request->validate([
            'pressure_test_passed' => 'required|accepted',
            'soap_testing_passed'  => 'required|accepted',
        ]);

        $project->setPhaseData('inspection', [
            'pressure_test_passed' => true,
            'soap_testing_passed'  => true,
        ]);

        $newProgress = Project::PHASE_PROGRESS['inspection'];

        $project->update([
            'current_phase' => 'painting',
            'progress'      => $newProgress,
        ]);

        $this->createAdminUpdate($project, [
            'phase'      => 'inspection',
            'work_done'  => 'Inspection completed successfully.',
            'percentage' => $newProgress,
        ]);

        NotificationService::phaseAdvanced(
            $project,
            'painting',
            'Inspection completed successfully.'
        );

        return redirect()->route('admin.project_view', $project->id)
            ->with('success', "Inspection complete! Project advanced to Painting ({$newProgress}% complete).");
    }

    /*
    |--------------------------------------------------------------------------
    | Phase 6 — Painting
    |--------------------------------------------------------------------------
    */
    private function handlePainting(Request $request, Project $project)
    {
        $request->validate([
            'photos'   => 'required|array|min:1',
            'photos.*' => 'required|image|max:5120',
            'remarks'  => 'nullable|string',
        ]);

        $photoUrls = $this->storage->uploadMultiple($request->file('photos'), 'projects/' . $project->id . '/painting');

        if (empty($photoUrls)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['photos' => 'Photo upload failed. Please check your connection and try again.']);
        }

        $project->setPhaseData('painting', [
            'photos'  => $photoUrls,
            'remarks' => $request->remarks,
        ]);

        $newProgress = Project::PHASE_PROGRESS['painting'];

        $project->update([
            'current_phase' => 'completion',
            'progress'      => $newProgress,
        ]);

        $this->createAdminUpdate($project, [
            'phase'      => 'painting',
            'work_done'  => $request->remarks ?: 'Painting has been completed.',
            'percentage' => $newProgress,
            'photos'     => $photoUrls,
        ]);

        NotificationService::phaseAdvanced(
            $project,
            'completion',
            'Painting has been completed.'
        );

        return redirect()->route('admin.project_view', $project->id)
            ->with('success', "Painting complete! Project advanced to Completion ({$newProgress}% complete).");
    }

    /*
    |--------------------------------------------------------------------------
    | Phase 7 — Completion (Final Output)
    |--------------------------------------------------------------------------
    */
    private function handleCompletion(Request $request, Project $project)
    {
        $request->validate([
            'photos'           => 'required|array|min:1',
            'photos.*'         => 'required|image|max:5120',
            'completion_notes' => 'nullable|string',
        ]);

        $photoUrls = $this->storage->uploadMultiple($request->file('photos'), 'projects/' . $project->id . '/completion');

        if (empty($photoUrls)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['photos' => 'Photo upload failed. Please check your connection and try again.']);
        }

        $project->setPhaseData('completion', [
            'photos' => $photoUrls,
            'notes'  => $request->completion_notes,
        ]);

        $newProgress = Project::PHASE_PROGRESS['completion'];

        $project->update([
            'current_phase' => 'delivery',
            'progress'      => $newProgress,
        ]);

        $this->createAdminUpdate($project, [
            'phase'        => 'completion',
            'update_label' => 'completion',
            'work_done'    => $request->completion_notes ?: 'Project completion update.',
            'percentage'   => $newProgress,
            'photos'       => $photoUrls,
        ]);

        NotificationService::phaseAdvanced(
            $project,
            'delivery',
            'Project completion update.'
        );

        return redirect()->route('admin.project_view', $project->id)
            ->with('success', "Final output recorded! Project advanced to Delivery ({$newProgress}% complete).");
    }

    /*
    |--------------------------------------------------------------------------
    | Phase 8 — Delivery (requires Final Payment: 20% Big / 50% Small)
    |--------------------------------------------------------------------------
    */
    private function handleDelivery(Request $request, Project $project)
    {
        if (!$project->isPaymentStageSettled('final_payment')) {
            return redirect()->route('admin.project_view', $project->id)
                ->with('error', 'Waiting for final payment settlement. The final payment must be settled before project delivery.');
        }

        $request->validate([
            'photos'         => 'required|array|min:1',
            'photos.*'       => 'required|image|max:5120',
            'delivery_notes' => 'nullable|string',
        ]);

        $photoUrls = $this->storage->uploadMultiple($request->file('photos'), 'projects/' . $project->id . '/delivery');

        if (empty($photoUrls)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['photos' => 'Photo upload failed. Please check your connection and try again.']);
        }

        $project->setPhaseData('delivery', [
            'photos' => $photoUrls,
            'notes'  => $request->delivery_notes,
        ]);

        $newProgress = Project::PHASE_PROGRESS['delivery'];

        $project->update([
            'progress' => $newProgress,
            'status'   => 'completed',
        ]);

        $this->createAdminUpdate($project, [
            'phase'        => 'delivery',
            'update_label' => 'delivery',
            'work_done'    => $request->delivery_notes ?: 'Project delivered to the client.',
            'percentage'   => $newProgress,
            'photos'       => $photoUrls,
        ]);

        NotificationService::projectCompleted($project);

        return redirect()->route('admin.project_view', $project->id)
            ->with('success', 'Project delivered! Marked as completed (100%).');
    }

    /*
    |--------------------------------------------------------------------------
    | Request Update from Employee (Admin action)
    | Creates a new ProgressRequest with status 'open'
    |--------------------------------------------------------------------------
    */
    public function requestUpdate(Request $request, $id)
    {
        $request->validate(['message' => 'nullable|string']);

        $project = Project::findOrFail($id);

        $existing = ProgressRequest::where('project_id', $id)
                        ->whereIn('status', ['open', 'revision_requested'])
                        ->first();

        if ($existing) {
            return redirect()->route('admin.project_view', $id)
                ->with('error', 'There is already an active request for this project.');
        }

        ProgressRequest::create([
            'project_id'   => $id,
            'requested_by' => session('user_id') ?? 1,
            'message'      => $request->message,
            'phase'        => $project->current_phase,
            'status'       => 'open',
        ]);

        NotificationService::progressRequested($project, $request->message);

        return redirect()->route('admin.project_view', $id)
            ->with('success', 'Progress update request sent to employees!');
    }

    /*
    |--------------------------------------------------------------------------
    | Employee Submits Initial Progress Update
    | Marks the ProgressRequest as 'completed' → form disappears
    |--------------------------------------------------------------------------
    */
    public function submitUpdate(Request $request, $requestId)
    {
        $progressRequest = ProgressRequest::findOrFail($requestId);
        $project         = Project::findOrFail($progressRequest->project_id);

        $validator = Validator::make($request->all(), [
            'date_of_work' => 'required|date',
            'work_done'    => 'required|string',
            'issues'       => 'nullable|string',
            'photos'       => 'required|array|min:1|max:5',
            'photos.*'     => 'required|image|max:5120',
        ]);

        if ($validator->fails()) {
            return redirect()->route('employee.project_view', $project->id)
                ->withErrors($validator)
                ->withInput();
        }

        if ($progressRequest->status !== 'open') {
            return redirect()->route('employee.project_view', $project->id)
                ->with('error', 'This request is no longer active.');
        }

        $photoUrls = $this->storage->uploadMultiple(
            $request->file('photos'),
            'projects/' . $project->id
        );

        if (empty($photoUrls)) {
            return redirect()->route('employee.project_view', $project->id)
                ->withInput()
                ->withErrors(['photos' => 'Photo upload failed. Please check your connection and try again.']);
        }

        $projectUpdate = ProjectUpdate::create([
            'project_id'        => $project->id,
            'submitted_by'      => session('user_id'),
            'submitted_by_type' => 'employee',
            'type'              => 'employee_submission',
            'update_label'      => null,
            'phase'             => $project->current_phase,
            'percentage'        => $project->progress,
            'date_of_work'      => $request->date_of_work,
            'work_done'         => $request->work_done,
            'issues'            => $request->issues,
            'photos'            => $photoUrls,
            'status'            => 'pending_review',
        ]);

        $progressRequest->update([
            'status'       => 'completed',
            'fulfilled_by' => session('user_id'),
            'fulfilled_at' => now(),
        ]);

        $employee = Employee::find(session('user_id'));
        if ($employee) {
            NotificationService::progressSubmitted($project, $employee, $projectUpdate->id);
        }

        return redirect()->route('employee.project_view', $project->id)
            ->with('success', 'Progress update submitted! Admin will review it shortly.');
    }

    /*
    |--------------------------------------------------------------------------
    | Employee Submits Revision
    | Marks the ProgressRequest back to 'completed' → revision form disappears
    |--------------------------------------------------------------------------
    */
    /**
     * Maximum number of revision request/resubmit cycles allowed per submission lineage.
     */
    const MAX_REVISIONS = 2;

    /**
     * Count how many revisions already exist in this update's lineage,
     * walking back through parent_update_id (inclusive of $update itself).
     */
    private function countRevisionChain(?ProjectUpdate $update): int
    {
        $count  = 0;
        $cursor = $update;

        while ($cursor) {
            if ($cursor->update_label === 'revision') {
                $count++;
            }
            $cursor = $cursor->parent_update_id ? ProjectUpdate::find($cursor->parent_update_id) : null;
        }

        return $count;
    }

    public function submitRevision(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'date_of_work'     => 'required|date',
            'work_done'        => 'required|string',
            'issues'           => 'nullable|string',
            'photos'           => 'required|array|min:1|max:5',
            'photos.*'         => 'required|image|max:5120',
            'parent_update_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return redirect()->route('employee.project_view', $id)
                ->withErrors($validator)
                ->withInput();
        }

        // Make sure there's an active revision_requested request
        $activeRequest = ProgressRequest::where('project_id', $id)
                            ->where('status', 'revision_requested')
                            ->latest()
                            ->first();

        if (!$activeRequest) {
            return redirect()->route('employee.project_view', $id)
                ->with('error', 'No active revision request found.');
        }

        // Enforce the maximum number of revision resubmissions for this submission lineage
        $parentForCheck = ProjectUpdate::find($request->parent_update_id);
        if ($this->countRevisionChain($parentForCheck) >= self::MAX_REVISIONS) {
            return redirect()->route('employee.project_view', $id)
                ->with('error', 'Maximum of ' . self::MAX_REVISIONS . ' revision requests already used for this submission. Please contact the admin directly.');
        }

        $photoUrls = $this->storage->uploadMultiple(
            $request->file('photos'),
            'projects/' . $id
        );

        if (empty($photoUrls)) {
            return redirect()->route('employee.project_view', $id)
                ->withInput()
                ->withErrors(['photos' => 'Photo upload failed. Please check your connection and try again.']);
        }

        $parentUpdate = ProjectUpdate::find($request->parent_update_id);

        // Mark the original needs_revision update as superseded
        // so it no longer shows as highlighted in the admin history
        if ($parentUpdate && $parentUpdate->status === 'needs_revision') {
            $parentUpdate->update(['status' => 'superseded']);
        }

        // Create the revision submission
        $revisionUpdate = ProjectUpdate::create([
            'project_id'        => $id,
            'submitted_by'      => session('user_id'),
            'submitted_by_type' => 'employee',
            'type'              => 'employee_submission',
            'update_label'      => 'revision',
            'parent_update_id'  => $request->parent_update_id,
            'revision_feedback' => $parentUpdate->revision_feedback ?? null,
            'phase'             => $project->current_phase,
            'percentage'        => $project->progress,
            'date_of_work'      => $request->date_of_work,
            'work_done'         => $request->work_done,
            'issues'            => $request->issues,
            'photos'            => $photoUrls,
            'status'            => 'pending_review',
        ]);

        // Mark request as completed → revision form disappears
        $activeRequest->update([
            'status'       => 'completed',
            'fulfilled_by' => session('user_id') ?? 1,
            'fulfilled_at' => now(),
        ]);

        $employee = Employee::find(session('user_id'));
        if ($employee) {
            NotificationService::revisionSubmitted($project, $employee, $revisionUpdate->id);
        }

        return redirect()->route('employee.project_view', $id)
            ->with('success', 'Revision submitted successfully! Admin will review it shortly.');
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Requests Revision (single source of truth)
    | Sets the update to needs_revision AND creates/updates ProgressRequest
    | to 'revision_requested' → revision form appears for employee
    |--------------------------------------------------------------------------
    */
    public function requestRevision(Request $request, $updateId)
    {
        $request->validate(['revision_comment' => 'required|string']);

        $update = ProjectUpdate::findOrFail($updateId);

        if ($this->countRevisionChain($update) >= self::MAX_REVISIONS) {
            return redirect()->route('admin.project_view', $update->project_id)
                ->with('error', 'Maximum of ' . self::MAX_REVISIONS . ' revision requests already used for this submission.');
        }

        // Store clean feedback on the update
        $update->update([
            'status'            => 'needs_revision',
            'revision_feedback' => $request->revision_comment,
        ]);

        // Set the latest request to 'revision_requested'
        // This is what drives the employee form visibility
        $latestRequest = ProgressRequest::where('project_id', $update->project_id)
                            ->latest()
                            ->first();

        $project = Project::findOrFail($update->project_id);

        if ($latestRequest) {
            $latestRequest->update([
                'status'       => 'revision_requested',
                'fulfilled_by' => null,
                'fulfilled_at' => null,
            ]);
        } else {
            // Edge case: no request exists yet, create one
            ProgressRequest::create([
                'project_id'   => $update->project_id,
                'requested_by' => session('user_id') ?? 1,
                'message'      => $request->revision_comment,
                'phase'        => $project->current_phase,
                'status'       => 'revision_requested',
            ]);
        }

        NotificationService::revisionRequested($project, $update->submitted_by, $request->revision_comment);

        return redirect()->route('admin.project_view', $update->project_id)
            ->with('success', 'Revision requested. Employee can now resubmit.');
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Approves Update — auto-advances phase
    |--------------------------------------------------------------------------
    */
    public function approveUpdate(Request $request, $updateId)
    {
        $update  = ProjectUpdate::findOrFail($updateId);
        $project = Project::findOrFail($update->project_id);

        $currentIndex = array_search($project->current_phase, $this->phases);

        if ($currentIndex !== false && $currentIndex < count($this->phases) - 1) {
            $nextPhase   = $this->phases[$currentIndex + 1];
            $newProgress = $this->progressMap[$nextPhase];
            $newStatus   = $nextPhase === 'delivery' ? 'completed' : 'ongoing';

            $update->update(['status' => 'approved']);
            $project->update([
                'current_phase' => $nextPhase,
                'progress'      => $newProgress,
                'status'        => $newStatus,
            ]);

            $project->refresh();
            NotificationService::progressApproved($project, $nextPhase, $update->submitted_by, $update->id);

            return redirect()->route('admin.project_view', $project->id)
                ->with('success', 'Update approved! Project advanced to ' . ucfirst(str_replace('_', ' ', $nextPhase)) . ' phase (' . $newProgress . '% complete).');
        }

        $update->update(['status' => 'approved']);
        $project->update(['progress' => 100, 'status' => 'completed']);

        $project->refresh();
        NotificationService::progressApproved($project, 'delivery', $update->submitted_by, $update->id);

        return redirect()->route('admin.project_view', $project->id)
            ->with('success', 'Update approved! Project is now complete.');
    }

    /*
    |--------------------------------------------------------------------------
    | Advance Phase (kept for compatibility)
    |--------------------------------------------------------------------------
    */
    public function advancePhase(Request $request, $id)
    {
        $project      = Project::findOrFail($id);
        $currentIndex = array_search($project->current_phase, $this->phases);

        if ($currentIndex !== false && $currentIndex < count($this->phases) - 1) {
            $nextPhase   = $this->phases[$currentIndex + 1];
            $newProgress = $this->progressMap[$nextPhase];

            $project->update([
                'current_phase' => $nextPhase,
                'progress'      => $newProgress,
                'status'        => $nextPhase === 'delivery' ? 'completed' : 'ongoing',
            ]);

            return redirect()->route('admin.project_view', $id)
                ->with('success', 'Project advanced to ' . ucfirst(str_replace('_', ' ', $nextPhase)) . ' phase!');
        }

        return redirect()->route('admin.project_view', $id)
            ->with('error', 'Project is already at the final phase.');
    }
}