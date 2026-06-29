<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\ProjectMaterial;
use App\Models\MaterialPurchase;
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

        // Top supplier by total amount purchased
        $topSupplierData = MaterialPurchase::select('supplier')
            ->selectRaw('SUM(total_paid) as total_spent')
            ->selectRaw('COUNT(*) as purchase_count')
            ->whereNotNull('supplier')
            ->where('supplier', '!=', '')
            ->groupBy('supplier')
            ->orderByRaw('SUM(total_paid) DESC')
            ->first();

        $topSupplier = null;
        if ($topSupplierData) {
            $topSupplier = [
                'name'           => $topSupplierData->supplier,
                'total_spent'    => (float) $topSupplierData->total_spent,
                'purchase_count' => (int) $topSupplierData->purchase_count,
            ];
        }

        // Peak months — full year monthly revenue for line chart (last 2 years for year filter)
        $peakMonthsData = [];
        for ($y = now()->year - 1; $y <= now()->year; $y++) {
            $months = [];
            for ($m = 1; $m <= 12; $m++) {
                $months[] = [
                    'label'  => \Carbon\Carbon::create($y, $m)->format('M'),
                    'amount' => (float) PaymentTransaction::whereYear('payment_date', $y)
                        ->whereMonth('payment_date', $m)->sum('amount_paid'),
                ];
            }
            $peakMonthsData[$y] = $months;
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
            'projectStatusChart',
            'topSupplier',
            'peakMonthsData'
        ));
    }

    public function projects()
    {
        $projects = Project::with('assignedEmployees', 'tankItems')->orderBy('created_at', 'desc')->get();
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
            'description'    => 'nullable|string|max:1000',
        ]);

        SiteSetting::instance()->update($validated);

        return redirect()->route('admin.settings')
            ->with('success', 'Contact information updated successfully.')
            ->with('active_tab', 'landing');
    }

    public function reports(Request $request)
    {
        $filterQuarter = $request->input('quarter', 'all');
        $filterStatus  = $request->input('status', 'all');
        $filterKpi     = $request->input('kpi', 'all');
        $filterProject = $request->input('project', 'all');

        // Map quarter to months
        $quarterMonths = [
            'q1' => [1,2,3], 'q2' => [4,5,6],
            'q3' => [7,8,9], 'q4' => [10,11,12],
        ];
        $filterYear  = now()->year;
        $filterMonth = null;

        // All completed projects for the dropdown (unfiltered)
        $allCompletedProjects = Project::where('status', 'completed')
            ->orderBy('name')
            ->get(['id', 'name', 'client']);

        // ── Per-project KPIs (completed projects only, ordered by completion) ──
        $query = Project::where('status', 'completed')->orderBy('updated_at');
        if ($filterQuarter !== 'all' && isset($quarterMonths[$filterQuarter])) {
            $months = $quarterMonths[$filterQuarter];
            $query->whereYear('updated_at', $filterYear)
                  ->whereIn(\DB::raw('EXTRACT(MONTH FROM updated_at)'), $months);
        }
        if ($filterProject !== 'all' && is_numeric($filterProject)) {
            $query->where('id', (int) $filterProject);
        }
        $completedList = $query->get();

        $projectKpis = $completedList->values()->map(function ($project, $index) {
            $payment  = Payment::where('project_id', $project->id)->first();
            $received = $payment
                ? (float) PaymentTransaction::where('payment_id', $payment->id)->sum('amount_paid')
                : 0;
            $contractValue = $payment ? (float) $payment->contract_amount : 0;

            // ── Actual material spend (from real purchases, not BOM estimate) ──
            $actualMatSpend = (float) \App\Models\MaterialPurchase::where('project_id', $project->id)
                ->sum('total_paid');

            // ── BOM estimated material cost (budgeted) ──
            $bomMatCost = (float) ProjectMaterial::where('project_id', $project->id)
                ->where('status', 'active')->sum('total_cost');

            // ── Actual labor cost = allocated_pay from salary_record_project pivot (equal split) ──
            $actualLaborCost = (float) \DB::table('salary_record_project')
                ->where('project_id', $project->id)
                ->sum('allocated_pay');
            // Fallback to project_labor total if no salary pivot records exist
            if ($actualLaborCost == 0) {
                $actualLaborCost = (float) \App\Models\ProjectLabor::where('project_id', $project->id)
                    ->where('status', 'active')->sum('total_cost');
            }

            // ── Total actual spend (materials + labor) ──
            $totalActualSpend = $actualMatSpend + $actualLaborCost;

            // ── BOM total budget (materials + labor estimate) ──
            $bomLaborCost = (float) \App\Models\ProjectLabor::where('project_id', $project->id)
                ->sum('total_cost');
            $bomTotalBudget = $bomMatCost + $bomLaborCost;

            // ── Profit Margin = (Revenue received - Actual total spend) / Revenue ──
            $profitMargin = $received > 0
                ? round((($received - $totalActualSpend) / $received) * 100, 1)
                : 0;

            // ── Budget Adherence = BOM Budget / Actual Spend (higher = better, capped 100) ──
            // If actual spend ≤ BOM budget = 100%, else show % of budget used
            $budgetAdherence = $bomTotalBudget > 0
                ? min(100, round(($bomTotalBudget / max(1, $totalActualSpend ?: $bomTotalBudget)) * 100, 1))
                : ($contractValue > 0 ? min(100, round(($received / $contractValue) * 100, 1)) : 0);

            // ── On-time delivery ──
            $onTime = $project->end_date
                && $project->updated_at->startOfDay()->lte($project->end_date);

            $daysDelay = 0;
            if (!$onTime && $project->end_date) {
                $daysDelay = (int) $project->end_date->diffInDays($project->updated_at);
            }

            $estProfit = max(0, $received - $totalActualSpend);
            $saved     = max(0, $bomTotalBudget - $totalActualSpend);

            return [
                'code'             => $project->code,
                'label'            => $project->code,
                'short'            => $project->code,
                'full_name'        => $project->name,
                'client'           => $project->client,
                'profit_margin'    => $profitMargin,
                'on_time'          => $onTime,
                'budget_adherence' => $budgetAdherence,
                'contract_value'   => $contractValue,
                'received'         => $received,
                'mat_cost'         => $actualMatSpend,       // actual, not BOM
                'bom_mat_cost'     => $bomMatCost,
                'labor_cost'       => $actualLaborCost,
                'total_spend'      => $totalActualSpend,
                'est_profit'       => $estProfit,
                'saved'            => $saved,
                'days_delay'       => $daysDelay,
            ];
        });

        $count = $projectKpis->count();

        // ── Overall averages ──
        $avgProfitMargin    = $count > 0 ? round($projectKpis->avg('profit_margin'), 1) : 0;
        $onTimeCount        = $projectKpis->where('on_time', true)->count();
        $onTimeRate         = $count > 0 ? round(($onTimeCount / $count) * 100)          : 0;
        $avgBudgetAdherence = $count > 0 ? round($projectKpis->avg('budget_adherence'), 1): 0;

        // ── WMA Forecast (weights 3-2-1 on last 3 projects) ──
        $last3 = $projectKpis->take(-3)->values();

        $wmaCalc = function (array $vals) {
            $n = count($vals);
            if ($n === 0) return 0;
            if ($n === 1) return round($vals[0], 1);
            if ($n === 2) return round(($vals[1] * 2 + $vals[0] * 1) / 3, 1);
            return round(($vals[2] * 3 + $vals[1] * 2 + $vals[0] * 1) / 6, 1);
        };

        $pmVals  = $last3->pluck('profit_margin')->values()->toArray();
        $baVals  = $last3->pluck('budget_adherence')->values()->toArray();
        $otVals  = $last3->map(fn($p) => $p['on_time'] ? 100 : 0)->values()->toArray();

        $wmaForecast = [
            'profit_margin'    => $wmaCalc($pmVals),
            'on_time'          => $wmaCalc($otVals),
            'budget_adherence' => $wmaCalc($baVals),
        ];

        $last3Avg = [
            'profit_margin'    => $last3->count() > 0 ? round($last3->avg('profit_margin'), 1)                              : 0,
            'on_time'          => $last3->count() > 0 ? round($last3->map(fn($p) => $p['on_time'] ? 100 : 0)->avg(), 0)    : 0,
            'budget_adherence' => $last3->count() > 0 ? round($last3->avg('budget_adherence'), 1)                          : 0,
        ];

        // Last 5 for forecast chart
        $forecastChart = $projectKpis->take(-5)->values();

        // Extra summary data — using actual spend
        $totalRevenue    = $projectKpis->sum('received');
        $totalMatCost    = $projectKpis->sum('mat_cost');       // actual material purchases
        $totalLaborCost  = $projectKpis->sum('labor_cost');     // actual labor
        $totalActualSpend = $projectKpis->sum('total_spend');   // mat + labor actual
        $totalBomMatCost = $projectKpis->sum('bom_mat_cost');   // BOM estimate
        $totalSaved      = $projectKpis->sum('saved');
        $totalContracted = $projectKpis->sum('contract_value');
        $delayedCount   = $projectKpis->where('on_time', false)->count();
        $avgDelayDays   = $delayedCount > 0
            ? round($projectKpis->where('on_time', false)->avg('days_delay'))
            : 0;
        $activeProjects = Project::whereNotIn('status', ['completed','archived'])->count();

        // Quarter label
        $quarterLabels = [
            'q1' => 'Q1 — Jan to Mar', 'q2' => 'Q2 — Apr to Jun',
            'q3' => 'Q3 — Jul to Sep', 'q4' => 'Q4 — Oct to Dec',
            'all' => 'All periods',
        ];
        $currentQuarterLabel = $quarterLabels[$filterQuarter] ?? 'All periods';

        // Next 3 forecast (MLR-style projection using WMA trend)
        $pmTrend  = count($pmVals) >= 2 ? ($pmVals[count($pmVals)-1] - $pmVals[0]) / max(1, count($pmVals)-1) : 0;
        $baTrend  = count($baVals) >= 2 ? ($baVals[count($baVals)-1] - $baVals[0]) / max(1, count($baVals)-1) : 0;
        $otTrend  = count($otVals) >= 2 ? ($otVals[count($otVals)-1] - $otVals[0]) / max(1, count($otVals)-1) : 0;
        $lastPm   = count($pmVals) > 0 ? end($pmVals) : $avgProfitMargin;
        $lastBa   = count($baVals) > 0 ? end($baVals) : $avgBudgetAdherence;
        $lastOt   = count($otVals) > 0 ? end($otVals) : $onTimeRate;
        $lastRev  = $count > 0 ? $projectKpis->last()['received'] : 0;
        $revTrend = $count >= 2 ? ($projectKpis->last()['received'] - $projectKpis->first()['received']) / max(1, $count-1) : 0;

        $next3Forecast = [
            ['pm' => min(100, round($lastPm + $pmTrend, 1)),    'ba' => min(100, round($lastBa + $baTrend, 1)),    'ot' => min(100, round($lastOt + $otTrend)),    'rev' => max(0, round($lastRev + $revTrend))],
            ['pm' => min(100, round($lastPm + $pmTrend*2, 1)),  'ba' => min(100, round($lastBa + $baTrend*2, 1)),  'ot' => min(100, round($lastOt + $otTrend*2)),  'rev' => max(0, round($lastRev + $revTrend*2))],
            ['pm' => min(100, round($lastPm + $pmTrend*3, 1)),  'ba' => min(100, round($lastBa + $baTrend*3, 1)),  'ot' => min(100, round($lastOt + $otTrend*3)),  'rev' => max(0, round($lastRev + $revTrend*3))],
        ];

        return view('admin.reports', compact(
            'projectKpis', 'count',
            'avgProfitMargin', 'onTimeCount', 'onTimeRate', 'avgBudgetAdherence',
            'wmaForecast', 'forecastChart', 'last3Avg', 'pmVals', 'last3',
            'filterYear', 'filterMonth', 'filterQuarter', 'filterStatus', 'filterKpi', 'filterProject',
            'allCompletedProjects',
            'totalRevenue', 'totalMatCost', 'totalLaborCost', 'totalActualSpend',
            'totalBomMatCost', 'totalSaved', 'totalContracted',
            'delayedCount', 'avgDelayDays', 'activeProjects',
            'currentQuarterLabel', 'next3Forecast', 'revTrend', 'pmTrend', 'baTrend', 'otTrend'
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