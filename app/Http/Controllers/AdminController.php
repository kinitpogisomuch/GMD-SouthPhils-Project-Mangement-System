<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\ProjectMaterial;
use App\Models\Client;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalProjects          = Project::count();
        $activeProjects         = Project::whereNotIn('status', ['completed', 'archived'])->count();
        $ongoingProjects        = Project::where('status', 'ongoing')->count();
        $completedProjects      = Project::where('status', 'completed')->count();
        $pendingProjects        = Project::where('status', 'planning')->count();
        $totalEmployees         = Employee::where('status', 'Active')->count();
        $totalClients           = Client::count();
        $totalMaterialEntries   = ProjectMaterial::where('status', 'active')->count();
        $totalMaterialCost      = ProjectMaterial::where('status', 'active')->sum('total_cost');
        $projectsWithMaterials  = ProjectMaterial::where('status', 'active')
            ->distinct('project_id')->count('project_id');
        $projects               = Project::whereNotIn('status', ['archived'])
            ->orderBy('created_at', 'desc')->take(6)->get();

        // Payment stats
        $allPayments            = Payment::all();
        $totalContractValue     = $allPayments->sum('contract_amount');
        $totalReceived          = PaymentTransaction::sum('amount_paid');
        $fullyPaidPayments      = $allPayments->filter(fn($p) => $p->computeStatus() === 'Fully Paid')->count();
        $pendingPayments        = $allPayments->filter(fn($p) => $p->computeStatus() === 'Pending Down Payment')->count();

        // Monthly revenue for the last 12 months (used by all chart filters)
        $monthlyRevenue = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyRevenue[] = [
                'label'  => $month->format('M Y'),
                'amount' => (float) PaymentTransaction::whereYear('payment_date', $month->year)
                    ->whereMonth('payment_date', $month->month)
                    ->sum('amount_paid'),
            ];
        }

        // Revenue for the current calendar year (Jan–Dec)
        $yearlyRevenue = [];
        for ($m = 1; $m <= 12; $m++) {
            $yearlyRevenue[] = [
                'label'  => \Carbon\Carbon::create(now()->year, $m)->format('M Y'),
                'amount' => (float) PaymentTransaction::whereYear('payment_date', now()->year)
                    ->whereMonth('payment_date', $m)
                    ->sum('amount_paid'),
            ];
        }

        // Weekly breakdown for the current month
        $weeklyRevenue   = [];
        $weekStart       = now()->startOfMonth()->copy();
        $monthEnd        = now()->endOfMonth()->copy();
        $weekNum         = 1;
        while ($weekStart->lte(now())) {
            $weekEnd = $weekStart->copy()->addDays(6)->min($monthEnd)->min(now());
            $weeklyRevenue[] = [
                'label'  => 'Wk ' . $weekNum
                            . ' (' . $weekStart->format('M j')
                            . '–' . $weekEnd->format('j') . ')',
                'amount' => (float) PaymentTransaction::whereBetween(
                    'payment_date',
                    [$weekStart->toDateString(), $weekEnd->toDateString()]
                )->sum('amount_paid'),
            ];
            $weekStart = $weekEnd->copy()->addDay();
            $weekNum++;
        }

        // Top client by project count + contract value
        $topClientData = Project::select('client')
            ->selectRaw('COUNT(*) as project_count')
            ->whereNotNull('client')
            ->where('client', '!=', '')
            ->groupBy('client')
            ->orderByRaw('COUNT(*) DESC')
            ->first();

        $topClient = null;
        if ($topClientData) {
            $topClientProjects = Project::where('client', $topClientData->client)->get();
            $topClientPayments = Payment::whereIn('project_id', $topClientProjects->pluck('id'))->get();
            $topClientReceived = PaymentTransaction::whereIn('payment_id', $topClientPayments->pluck('id'))->sum('amount_paid');
            $topClient = [
                'name'          => $topClientData->client,
                'project_count' => $topClientData->project_count,
                'contract_value'=> $topClientPayments->sum('contract_amount'),
                'received'      => $topClientReceived,
                'completed'     => $topClientProjects->where('status', 'completed')->count(),
            ];
        }

        // Unread messages
        $unreadMessages = \App\Models\Message::where('recipient_type', 'admin')
            ->where('recipient_id', session('user_id'))
            ->unread()
            ->count();

        // Project status donut chart data
        $overdueCount  = Project::whereNotIn('status', ['completed', 'archived'])
            ->whereNotNull('end_date')
            ->where('end_date', '<', now()->toDateString())
            ->where('progress', '<', 100)
            ->count();
        $planningCount = Project::where('current_phase', 'planning')
            ->whereNotIn('status', ['archived', 'completed'])
            ->count();
        $projectStatusChart = [
            ['label' => 'Completed', 'count' => $completedProjects, 'color' => '#16a34a'],
            ['label' => 'Planning',  'count' => $planningCount,     'color' => '#f59e0b'],
            ['label' => 'Overdue',   'count' => $overdueCount,      'color' => '#ef4444'],
        ];

        return view('admin.dashboard', compact(
            'totalProjects',
            'activeProjects',
            'ongoingProjects',
            'completedProjects',
            'pendingProjects',
            'totalEmployees',
            'totalClients',
            'totalMaterialEntries',
            'totalMaterialCost',
            'projectsWithMaterials',
            'projects',
            'totalContractValue',
            'totalReceived',
            'fullyPaidPayments',
            'pendingPayments',
            'unreadMessages',
            'topClient',
            'monthlyRevenue',
            'yearlyRevenue',
            'weeklyRevenue',
            'projectStatusChart'
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

        $contactInfo = SiteSetting::instance();

        return view('admin.settings', compact('adminData', 'users', 'portfolioItems', 'reviews', 'contactInfo'));
    }

    public function updateContactInfo(Request $request)
    {
        $validated = $request->validate([
            'phone'          => 'nullable|string|max:20',
            'mobile'         => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:255',
            'address'        => 'nullable|string|max:500',
            'facebook'       => 'nullable|string|max:255',
            'business_hours' => 'nullable|string|max:255',
        ]);

        SiteSetting::instance()->update($validated);

        return redirect()->route('admin.settings')
            ->with('success', 'Contact information updated successfully.')
            ->with('active_tab', 'landing');
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

    public function weeklyRevenue(Request $request)
    {
        $year  = (int) $request->query('year',  now()->year);
        $month = (int) $request->query('month', now()->month);

        $start = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();
        $today = now();

        $weeks     = [];
        $weekStart = $start->copy();
        $weekNum   = 1;

        while ($weekStart->lte($end) && $weekStart->lte($today)) {
            $weekEnd   = $weekStart->copy()->addDays(6)->min($end)->min($today);
            $weeks[]   = [
                'label'  => 'Wk ' . $weekNum
                            . ' (' . $weekStart->format('M j')
                            . '–' . $weekEnd->format('j') . ')',
                'amount' => (float) PaymentTransaction::whereBetween('payment_date', [
                    $weekStart->toDateString(),
                    $weekEnd->toDateString(),
                ])->sum('amount_paid'),
            ];
            $weekStart = $weekEnd->copy()->addDay();
            $weekNum++;
        }

        return response()->json([
            'month'   => $start->format('F Y'),
            'labels'  => array_column($weeks, 'label'),
            'amounts' => array_column($weeks, 'amount'),
        ]);
    }
}