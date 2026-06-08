<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Employee;

class EmployeeController extends Controller
{
    public function dashboard()
    {
        return view('employee.dashboard');
    }

    public function messages()
    {
        return view('employee.messages');
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

    public function tasks()
    {
        return view('employee.tasks');
    }

    public function timesheets()
    {
        return view('employee.timesheets');
    }

    public function settings()
    {
        $employee = Employee::findOrFail(session('user_id'));

        return view('employee.settings', compact('employee'));
    }
}