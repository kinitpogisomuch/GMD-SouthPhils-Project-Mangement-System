<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Employee;
use App\Models\SalaryRecord;
use App\Models\MaterialUsage;
use App\Models\ProgressRequest;
use App\Models\ProjectUpdate;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    public function dashboard()
    {
        $employee = Employee::findOrFail(session('user_id'));

        $projects = $employee->assignedProjects()
            ->orderByDesc('projects.created_at')
            ->get();

        $activeProjectsCount    = $projects->where('status', '!=', 'completed')->count();
        $completedProjectsCount = $projects->where('status', 'completed')->count();

        $payPeriod = now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $currentRecord = SalaryRecord::where('employee_id', $employee->id)
            ->where('pay_period', $payPeriod)
            ->first();

        $recentUsage = MaterialUsage::with('project')
            ->where('recorded_by', $employee->full_name)
            ->active()
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        // progress updates the admin asked this employee for, still waiting on them
        $openRequests = ProgressRequest::with('project')
            ->where('target_employee_id', $employee->id)
            ->whereIn('status', ['open', 'revision_requested'])
            ->latest()
            ->get();

        // Recent activity: updates this employee sent, update requests the admin sent them,
        // and materials they logged — newest first
        $activity = collect()
            ->concat(ProjectUpdate::with('project')
                ->where('submitted_by_type', 'employee')
                ->where('submitted_by', $employee->id)
                ->latest()->take(10)->get()
                ->map(fn ($u) => ['kind' => 'update', 'at' => $u->created_at, 'project' => $u->project, 'project_id' => $u->project_id, 'phase' => $u->phase, 'status' => $u->status]))
            ->concat(ProgressRequest::with('project')
                ->where('target_employee_id', $employee->id)
                ->latest()->take(10)->get()
                ->map(fn ($r) => ['kind' => 'request', 'at' => $r->created_at, 'project' => $r->project, 'project_id' => $r->project_id, 'phase' => $r->phase, 'status' => $r->status]))
            ->concat(MaterialUsage::with('project')
                ->where('recorded_by', $employee->full_name)
                ->active()
                ->latest()->take(10)->get()
                ->map(fn ($m) => ['kind' => 'usage', 'at' => $m->created_at, 'project' => $m->project, 'project_id' => $m->project_id,
                                  'phase' => null,   // usage isn't tied to a phase — the key is still there for the feed
                                  'material' => $m->material_name, 'qty' => (float) $m->quantity_used, 'unit' => $m->unit]))
            ->filter(fn ($a) => $a['at'])
            ->sortByDesc('at')
            ->take(12)
            ->values();

        return view('employee.dashboard', compact(
            'employee',
            'projects',
            'activeProjectsCount',
            'completedProjectsCount',
            'currentRecord',
            'recentUsage',
            'openRequests',
            'activity'
        ));
    }

    public function projects()
    {
        $employee = Employee::findOrFail(session('user_id'));

        $projects = $employee->assignedProjects()
            ->orderByDesc('projects.created_at')
            ->get();

        // Which of these projects currently have a progress-update request
        // waiting on this employee — highlighted on the card below.
        $pendingRequestProjectIds = ProgressRequest::where('target_employee_id', $employee->id)
            ->whereIn('status', ['open', 'revision_requested'])
            ->pluck('project_id')
            ->unique()
            ->all();

        return view('employee.projects', compact('projects', 'pendingRequestProjectIds'));
    }

    public function projectView()
    {
        return view('employee.project_view');
    }

    public function settings()
    {
        $employee = Employee::findOrFail(session('user_id'));

        return view('employee.settings', compact('employee'));
    }

    public function salary()
    {
        $employee = Employee::findOrFail(session('user_id'));

        $payPeriod = now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        $records = SalaryRecord::where('employee_id', $employee->id)
            ->orderBy('pay_period', 'desc')
            ->get();

        $currentRecord = $records->firstWhere('pay_period', $payPeriod);

        return view('employee.salary', compact('employee', 'records', 'currentRecord', 'payPeriod'));
    }
}