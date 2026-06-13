<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Employee;
use App\Models\SalaryRecord;
use App\Models\MaterialUsage;
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

        return view('employee.dashboard', compact(
            'employee',
            'projects',
            'activeProjectsCount',
            'completedProjectsCount',
            'currentRecord',
            'recentUsage'
        ));
    }

    public function projects()
    {
        $projects = Project::orderBy('created_at', 'desc')->get();
        return view('employee.projects', compact('projects'));
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