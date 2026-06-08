<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\MaterialRequest;
use App\Models\Client;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalProjects     = Project::count();
        $ongoingProjects   = Project::where('status', 'ongoing')->count();
        $completedProjects = Project::where('status', 'completed')->count();
        $pendingProjects   = Project::where('status', 'pending')->count();
        $totalEmployees    = Employee::where('status', 'Active')->count();
        $pendingRequests   = MaterialRequest::where('status', 'pending')->count();
        $fulfilledRequests = MaterialRequest::where('status', 'fulfilled')->count();
        $projects          = Project::orderBy('created_at', 'desc')->take(5)->get();

        return view('admin.dashboard', compact(
            'totalProjects',
            'ongoingProjects',
            'completedProjects',
            'pendingProjects',
            'totalEmployees',
            'pendingRequests',
            'fulfilledRequests',
            'projects'
        ));
    }

    public function projects()
    {
        $projects = Project::orderBy('created_at', 'desc')->get();
        return view('admin.projects', compact('projects'));
    }

    public function employees()
    {
        $employees = Employee::orderBy('created_at', 'desc')->get();
        return view('admin.employees', compact('employees'));
    }

    public function materialRequests()
    {
        $materials      = MaterialRequest::orderBy('created_at', 'desc')->get();
        $pendingCount   = MaterialRequest::where('status', 'pending')->count();
        $fulfilledCount = MaterialRequest::where('status', 'fulfilled')->count();
        $shortageCount  = MaterialRequest::where('status', 'shortage')->count();

        return view('admin.material_requests', compact(
            'materials',
            'pendingCount',
            'fulfilledCount',
            'shortageCount'
        ));
    }

    public function payments()
    {
        $payments     = Payment::orderBy('created_at', 'desc')->get();
        $paidCount    = Payment::where('status', 'Paid')->count();
        $partialCount = Payment::where('status', 'Partial')->count();
        $pendingCount = Payment::where('status', 'Pending')->count();

        return view('admin.payments', compact(
            'payments',
            'paidCount',
            'partialCount',
            'pendingCount'
        ));
    }

    public function messages()
    {
        return view('admin.messages');
    }

    public function settings()
    {
        // Admin profile data
        $admin = \App\Models\User::find(session('user_id'));

        // Split full_name for the form (stored as "LastName, FirstName" or "Name")
        $fullName = $admin->full_name ?? '';
        if (str_contains($fullName, ', ')) {
            [$lastName, $firstName] = explode(', ', $fullName, 2);
        } else {
            $parts     = explode(' ', $fullName, 2);
            $firstName = $parts[0] ?? $fullName;
            $lastName  = $parts[1] ?? '';
        }

        $adminData = (object)[
            'first_name'     => $firstName,
            'last_name'      => $lastName,
            'full_name'      => $fullName,
            'username'       => $admin->username ?? 'admin',
            'email'          => $admin->email ?? '',
            'phone'          => $admin->phone ?? '',
            'region'         => $admin->region ?? '',
            'province'       => $admin->province ?? '',
            'city'           => $admin->city ?? '',
            'street_address' => $admin->street_address ?? '',
            'member_since'   => $admin->created_at?->format('Y') ?? date('Y'),
        ];

        // Managed users list
        $employees = Employee::whereNotNull('username')->orderBy('created_at')->get()
            ->toBase()
            ->map(fn($e) => (object)[
                'role'       => 'employee',
                'full_name'  => $e->full_name,
                'username'   => $e->username,
                'email'      => $e->email,
                'status'     => $e->status,
                'created_at' => $e->created_at,
            ]);

        $clients = Client::whereNotNull('username')->orderBy('created_at')->get()
            ->toBase()
            ->map(fn($c) => (object)[
                'role'       => 'client',
                'full_name'  => $c->full_name,
                'username'   => $c->username,
                'email'      => $c->email,
                'status'     => $c->status,
                'created_at' => $c->created_at,
            ]);

        $users = $employees->merge($clients)->sortBy('created_at')->values();

        return view('admin.settings', compact('adminData', 'users'));
    }

    public function clients()
    {
        $clients = Client::orderBy('created_at', 'desc')->get();

        $projectCounts = \App\Models\Project::selectRaw('client, COUNT(*) as count')
            ->groupBy('client')
            ->pluck('count', 'client');

        $clients->each(function ($client) use ($projectCounts) {
            $client->setAttribute('projects_count', $projectCounts[$client->name] ?? 0);
        });

        return view('admin.clients', compact('clients'));
    }
}