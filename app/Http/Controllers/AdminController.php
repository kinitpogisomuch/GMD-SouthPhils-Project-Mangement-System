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

    public function reports()
    {
        // ── Revenue (monthly, last 12 months) ──
        $monthlyRevenue = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $monthlyRevenue[] = [
                'label'  => $m->format('M Y'),
                'amount' => (float) PaymentTransaction::whereYear('payment_date', $m->year)
                    ->whereMonth('payment_date', $m->month)->sum('amount_paid'),
            ];
        }

        // ── Revenue (this calendar year) ──
        $yearlyRevenue = [];
        for ($m = 1; $m <= 12; $m++) {
            $yearlyRevenue[] = [
                'label'  => \Carbon\Carbon::create(now()->year, $m)->format('M'),
                'amount' => (float) PaymentTransaction::whereYear('payment_date', now()->year)
                    ->whereMonth('payment_date', $m)->sum('amount_paid'),
            ];
        }

        // ── Project KPIs ──
        $totalProjects     = Project::count();
        $activeProjects    = Project::whereNotIn('status', ['completed', 'archived'])->count();
        $completedProjects = Project::where('status', 'completed')->count();
        $archivedProjects  = Project::where('status', 'archived')->count();
        $completionRate    = $totalProjects > 0 ? round(($completedProjects / $totalProjects) * 100) : 0;
        $overdueProjects   = Project::whereNotIn('status', ['completed', 'archived'])
            ->where('end_date', '<', now()->toDateString())
            ->where('progress', '<', 100)->count();
        $avgProgress       = round(Project::whereNotIn('status', ['completed', 'archived'])->avg('progress') ?? 0);

        $projectsByStatus = [
            ['label' => 'Active',    'count' => $activeProjects,    'color' => '#2A4EAA'],
            ['label' => 'Completed', 'count' => $completedProjects, 'color' => '#16a34a'],
            ['label' => 'Archived',  'count' => $archivedProjects,  'color' => '#94a3b8'],
            ['label' => 'Overdue',   'count' => $overdueProjects,   'color' => '#ef4444'],
        ];

        // ── Payment KPIs ──
        $allPayments       = Payment::all();
        $totalContractValue = $allPayments->sum('contract_amount');
        $totalReceived     = PaymentTransaction::sum('amount_paid');
        $outstanding       = max(0, $totalContractValue - $totalReceived);
        $collectionRate    = $totalContractValue > 0 ? min(100, round(($totalReceived / $totalContractValue) * 100)) : 0;
        $fullyPaid         = $allPayments->filter(fn($p) => $p->computeStatus() === 'Fully Paid')->count();
        $partialPaid       = $allPayments->filter(fn($p) => $p->computeStatus() === 'Partially Paid')->count();
        $pendingPayment    = $allPayments->filter(fn($p) => $p->computeStatus() === 'Pending Down Payment')->count();

        // ── Material KPIs ──
        $totalMaterials    = ProjectMaterial::where('status', 'active')->count();
        $totalMatCost      = (float) ProjectMaterial::where('status', 'active')->sum('total_cost');
        $projectsWithMats  = ProjectMaterial::where('status', 'active')->distinct('project_id')->count('project_id');

        // ── Top 5 clients by project count ──
        $topClients = Project::select('client')
            ->selectRaw('COUNT(*) as project_count')
            ->selectRaw('SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) as completed_count')
            ->whereNotNull('client')->where('client', '!=', '')
            ->groupBy('client')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $projects = Project::where('client', $row->client)->pluck('id');
                $payments = Payment::whereIn('project_id', $projects)->get();
                $received = PaymentTransaction::whereIn('payment_id', $payments->pluck('id'))->sum('amount_paid');
                return [
                    'name'          => $row->client,
                    'project_count' => $row->project_count,
                    'completed'     => $row->completed_count,
                    'contract'      => (float) $payments->sum('contract_amount'),
                    'received'      => (float) $received,
                ];
            });

        // ── People ──
        $totalEmployees = Employee::where('status', 'Active')->count();
        $totalClients   = Client::count();

        // ── Projects list for the project KPI selector ──
        $allProjects = Project::orderBy('name')->get(['id', 'name', 'status', 'client']);

        // ── Project Profit Margin ──
        // (Total contract value - total material cost) / total contract value
        $profitMargin = $totalContractValue > 0
            ? round((($totalContractValue - $totalMatCost) / $totalContractValue) * 100, 1)
            : 0;
        $estimatedProfit = max(0, $totalContractValue - $totalMatCost);

        // ── Budget Adherence Rate ──
        // Per project: adherent if its material cost ≤ its contract amount.
        // We use Payment->contract_amount as the budget reference.
        $paymentsForAdherence = Payment::select('project_id', 'contract_amount')->get();
        $adherentCount  = 0;
        $adherenceTotal = 0;
        foreach ($paymentsForAdherence as $pay) {
            if (!$pay->project_id) continue;
            $matCost = (float) ProjectMaterial::where('project_id', $pay->project_id)
                ->where('status', 'active')->sum('total_cost');
            $adherenceTotal++;
            if ($matCost <= (float) $pay->contract_amount) {
                $adherentCount++;
            }
        }
        $budgetAdherenceRate = $adherenceTotal > 0
            ? round(($adherentCount / $adherenceTotal) * 100)
            : 0;

        // ── On-Time Delivery Rate ──
        // Completed projects where updated_at (proxy for completion date) ≤ end_date.
        $completedProjectsList = Project::where('status', 'completed')
            ->whereNotNull('end_date')->get();
        $onTimeCount = $completedProjectsList->filter(function ($p) {
            return $p->updated_at->startOfDay()->lte($p->end_date);
        })->count();
        $onTimeDeliveryRate = $completedProjectsList->count() > 0
            ? round(($onTimeCount / $completedProjectsList->count()) * 100)
            : 0;
        $lateCount = $completedProjectsList->count() - $onTimeCount;

        return view('admin.reports', compact(
            'monthlyRevenue', 'yearlyRevenue',
            'totalProjects', 'activeProjects', 'completedProjects', 'archivedProjects',
            'completionRate', 'overdueProjects', 'avgProgress', 'projectsByStatus',
            'totalContractValue', 'totalReceived', 'outstanding', 'collectionRate',
            'fullyPaid', 'partialPaid', 'pendingPayment',
            'totalMaterials', 'totalMatCost', 'projectsWithMats',
            'topClients', 'totalEmployees', 'totalClients',
            'profitMargin', 'estimatedProfit',
            'budgetAdherenceRate', 'adherentCount', 'adherenceTotal',
            'onTimeDeliveryRate', 'onTimeCount', 'lateCount',
            'allProjects'
        ));
    }

    public function projectKpi($id)
    {
        $project  = Project::findOrFail($id);
        $payment  = Payment::where('project_id', $id)->first();
        $received = $payment
            ? (float) PaymentTransaction::where('payment_id', $payment->id)->sum('amount_paid')
            : 0;

        $matCost  = (float) ProjectMaterial::where('project_id', $id)->where('status', 'active')->sum('total_cost');
        $matCount = ProjectMaterial::where('project_id', $id)->where('status', 'active')->count();

        $contractValue  = $payment ? (float) $payment->contract_amount : 0;
        $outstanding    = max(0, $contractValue - $received);
        $collectionRate = $contractValue > 0 ? min(100, round(($received / $contractValue) * 100)) : 0;
        $profitMargin   = $contractValue > 0
            ? round((($contractValue - $matCost) / $contractValue) * 100, 1)
            : 0;
        $estProfit      = max(0, $contractValue - $matCost);
        $budgetAdherent = $matCost <= $contractValue;

        $daysTotal     = ($project->start_date && $project->end_date)
            ? (int) $project->start_date->diffInDays($project->end_date)
            : 0;
        $daysElapsed   = $project->start_date
            ? (int) min($daysTotal, $project->start_date->diffInDays(now()))
            : 0;
        $daysRemaining = $project->end_date
            ? (int) now()->diffInDays($project->end_date, false)
            : null;
        $isOverdue     = $project->end_date
            && now()->gt($project->end_date)
            && !in_array($project->status, ['completed', 'archived']);
        $timeProgress  = $daysTotal > 0 ? min(100, round(($daysElapsed / $daysTotal) * 100)) : 0;

        $paymentStages = $payment
            ? PaymentTransaction::where('payment_id', $payment->id)
                ->orderBy('payment_date')
                ->get(['payment_stage', 'amount_paid', 'payment_date'])
                ->map(fn($t) => [
                    'stage'  => PaymentTransaction::stageLabel($t->payment_stage),
                    'amount' => (float) $t->amount_paid,
                    'date'   => $t->payment_date?->format('M j, Y'),
                ])
            : [];

        return response()->json([
            'project' => [
                'name'       => $project->name,
                'client'     => $project->client,
                'status'     => ucfirst($project->status ?? 'Planning'),
                'tank_type'  => $project->tank_type,
                'progress'   => $project->progress ?? 0,
                'start_date' => $project->start_date?->format('M j, Y'),
                'end_date'   => $project->end_date?->format('M j, Y'),
            ],
            'payment' => [
                'contract_value'  => $contractValue,
                'received'        => $received,
                'outstanding'     => $outstanding,
                'collection_rate' => $collectionRate,
                'status'          => $payment ? $payment->computeStatus() : 'No Payment Set',
                'stages'          => $paymentStages,
            ],
            'materials' => [
                'count' => $matCount,
                'cost'  => $matCost,
            ],
            'financial' => [
                'profit_margin'    => $profitMargin,
                'estimated_profit' => $estProfit,
                'budget_adherent'  => $budgetAdherent,
            ],
            'timeline' => [
                'days_total'     => $daysTotal,
                'days_elapsed'   => $daysElapsed,
                'days_remaining' => $daysRemaining,
                'time_progress'  => $timeProgress,
                'is_overdue'     => $isOverdue,
            ],
        ]);
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