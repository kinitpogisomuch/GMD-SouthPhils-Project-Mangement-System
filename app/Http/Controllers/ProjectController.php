<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\ProgressRequest;
use App\Models\Client;
use App\Models\Employee;
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
        $project = Project::find($id);
        if (!$project) {
            return redirect()->route('admin.projects')
                ->with('error', 'That project no longer exists.');
        }

        $updates = ProjectUpdate::where('project_id', $id)
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

        // Resolve client address: prefer the stored project address,
        // fall back to the matching client record in the clients table.
        $clientRecord  = Client::where('name', $project->client)->first();
        $clientAddress = $project->address
                         ?? ($clientRecord ? $clientRecord->address : null);

        return view('admin.project_view', compact(
            'project', 'updates', 'openRequest', 'pendingUpdates', 'nextPhase', 'clientAddress'
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
            'tank_type'  => 'required|string',
            'capacity'   => 'required|string',
            'start_date' => 'required|date',
            'end_date'   => 'required|date',
            'client'     => 'required|string',
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

        $project = Project::create([
            'name'           => $name,
            'client'         => $request->client,
            'contact_number' => $request->contact_number,
            'email'          => $request->email,
            'address'        => $request->address,
            'client_type'    => $request->client_type ?? 'Corporate',
            'tank_type'      => $request->tank_type,
            'capacity'       => $request->capacity,
            'dimensions'     => $request->dimensions,
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date,
            'payment_status' => $request->payment_status ?? 'Pending',
            'status'         => 'planning',
            'progress'       => 0,
            'current_phase'  => 'planning',
            'duration'       => $duration,
            'notes'          => $request->notes,
        ]);

        NotificationService::projectCreated($project);

        return redirect()->route('admin.projects')->with('success', 'Project created successfully!');
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
            'tank_type'  => 'required|string',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'notes'      => 'nullable|string',
        ]);

        $project  = Project::findOrFail($id);
        $start    = \Carbon\Carbon::parse($request->start_date);
        $end      = \Carbon\Carbon::parse($request->end_date);
        $duration = $start->diffInDays($end) . ' days';

        $project->update([
            'name'       => $request->name,
            'tank_type'  => $request->tank_type,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'duration'   => $duration,
            'notes'      => $request->notes,
        ]);

        return redirect()->route('admin.projects')->with('success', 'Project updated successfully!');
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
            return redirect()->route('admin.projects')->with('success', 'Project restored successfully.');
        }

        $project->update(['status' => 'archived']);
        return redirect()->route('admin.projects')->with('success', 'Project archived.');
    }

    /*
    |--------------------------------------------------------------------------
    | Add Direct Update (Admin) — auto-advances phase
    |--------------------------------------------------------------------------
    */
    public function addUpdate(Request $request, $id)
    {
        $request->validate([
            'date_of_work' => 'required|date',
            'work_done'    => 'required|string',
            'issues'       => 'nullable|string',
            'photos'       => 'required|array|min:1|max:5',
            'photos.*'     => 'required|image|max:5120',
        ]);

        $project = Project::findOrFail($id);

        $photoUrls = $this->storage->uploadMultiple(
            $request->file('photos'),
            'projects/' . $id
        );

        if (empty($photoUrls)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['photos' => 'Photo upload failed. Please check your connection and try again.']);
        }

        $currentPhase = $project->current_phase;
        $currentIndex = array_search($currentPhase, $this->phases);

        if ($currentIndex !== false && $currentIndex < count($this->phases) - 1) {
            $newPhase    = $this->phases[$currentIndex + 1];
            $newProgress = $this->progressMap[$newPhase];
            $newStatus   = $newPhase === 'delivery' ? 'completed' : 'ongoing';
        } else {
            $newPhase    = 'delivery';
            $newProgress = 100;
            $newStatus   = 'completed';
        }

        $submittedBy = session('user_id')
            ?? \App\Models\User::where('role', 'admin')->value('id')
            ?? 1;

        ProjectUpdate::create([
            'project_id'   => $id,
            'submitted_by' => $submittedBy,
            'type'         => 'admin_direct',
            'update_label' => null,
            'phase'        => $currentPhase,
            'percentage'   => $newProgress,
            'date_of_work' => $request->date_of_work,
            'work_done'    => $request->work_done,
            'issues'       => $request->issues,
            'photos'       => $photoUrls,
            'status'       => 'approved',
        ]);

        $project->update([
            'current_phase' => $newPhase,
            'progress'      => $newProgress,
            'status'        => $newStatus,
        ]);

        return redirect()->route('admin.project_view', $id)
            ->with('success', 'Progress update saved! Project advanced to ' . ucfirst(str_replace('_', ' ', $newPhase)) . ' phase (' . $newProgress . '% complete).');
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