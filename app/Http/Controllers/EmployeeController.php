<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Employee;
use App\Models\SalaryRecord;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    public function dashboard()
    {
        return view('employee.dashboard');
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