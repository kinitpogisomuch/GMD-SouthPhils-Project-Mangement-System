<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\ProjectMaterial;
use App\Models\Client;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalProjects          = Project::count();
        $ongoingProjects        = Project::where('status', 'ongoing')->count();
        $completedProjects      = Project::where('status', 'completed')->count();
        $pendingProjects        = Project::where('status', 'pending')->count();
        $totalEmployees         = Employee::where('status', 'Active')->count();
        $totalMaterialEntries   = ProjectMaterial::where('status', 'active')->count();
        $totalMaterialCost      = ProjectMaterial::where('status', 'active')->sum('total_cost');
        $projectsWithMaterials  = ProjectMaterial::where('status', 'active')
            ->distinct('project_id')->count('project_id');
        $projects               = Project::orderBy('created_at', 'desc')->take(5)->get();

        // Payment stats
        $allPayments            = Payment::all();
        $totalContractValue     = $allPayments->sum('contract_amount');
        $totalReceived          = PaymentTransaction::sum('amount_paid');
        $fullyPaidPayments      = $allPayments->filter(fn($p) => $p->computeStatus() === 'Fully Paid')->count();
        $pendingPayments        = $allPayments->filter(fn($p) => $p->computeStatus() === 'Pending Down Payment')->count();

        return view('admin.dashboard', compact(
            'totalProjects',
            'ongoingProjects',
            'completedProjects',
            'pendingProjects',
            'totalEmployees',
            'totalMaterialEntries',
            'totalMaterialCost',
            'projectsWithMaterials',
            'projects',
            'totalContractValue',
            'totalReceived',
            'fullyPaidPayments',
            'pendingPayments'
        ));
    }

    public function projects()
    {
        $projects = Project::with('assignedEmployees')->orderBy('created_at', 'desc')->get();
        return view('admin.projects', compact('projects'));
    }

    public function employees()
    {
        $employees = Employee::orderBy('created_at', 'desc')->get();
        return view('admin.employees', compact('employees'));
    }

    public function projectMaterials()
    {
        // Handled by ProjectMaterialController::adminIndex
        return redirect()->route('admin.project_materials');
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

        $portfolioItems = \App\Models\PortfolioItem::orderBy('sort_order')->get();
        $reviews = \App\Models\Review::with('project')->orderBy('created_at', 'desc')->get();

        return view('admin.settings', compact('adminData', 'users', 'portfolioItems', 'reviews'));
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