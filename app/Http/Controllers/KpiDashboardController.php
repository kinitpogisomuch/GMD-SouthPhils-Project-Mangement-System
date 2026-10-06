<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Project;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\MaterialPurchase;
use App\Models\ProjectMaterial;
use App\Models\ProjectLabor;
use App\Models\KpiQuarterTarget;
use App\Models\KpiProjectTarget;

class KpiDashboardController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Page
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        // The period picker loads the page as ?year=&quarter=(&month=) — anything missing or
        // out of range falls back to the current quarter.
        $current = $this->currentPeriod();
        $year    = (int) $request->input('year', $current['year']);
        $quarter = (int) $request->input('quarter', $current['quarter']);
        if ($year < 2000 || $year > 2100 || $quarter < 1 || $quarter > 4) {
            [$year, $quarter] = [$current['year'], $current['quarter']];
        }
        $month = $request->filled('month') ? (int) $request->input('month') : null;
        if ($month !== null && (int) ceil($month / 3) !== $quarter) {
            $month = null; // a month must belong to the chosen quarter
        }

        $data = $this->buildPayload($year, $quarter, $month);

        return view('admin.kpi_dashboard', [
            'initialYear'      => $year,
            'initialQuarter'   => $quarter,
            'initialData'      => $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | JSON: KPI data for a given period (scorecard + 4-quarter trend)
    |--------------------------------------------------------------------------
    */
    public function data(Request $request)
    {
        $year    = (int) $request->input('year', now()->year);
        $quarter = (int) $request->input('quarter', ceil(now()->month / 3));
        $month   = $request->filled('month') ? (int) $request->input('month') : null;

        return response()->json($this->buildPayload($year, $quarter, $month));
    }

    /*
    |--------------------------------------------------------------------------
    | JSON: KPI data for every quarter in a From–To range (for Generate Report)
    |--------------------------------------------------------------------------
    */
    public function reportRange(Request $request)
    {
        $validated = $request->validate([
            'from_year'    => 'required|integer|min:2000|max:2100',
            'from_quarter' => 'required|integer|min:1|max:4',
            'to_year'      => 'required|integer|min:2000|max:2100',
            'to_quarter'   => 'required|integer|min:1|max:4',
        ]);

        $fromKey = $this->quarterKey($validated['from_year'], $validated['from_quarter']);
        $toKey   = $this->quarterKey($validated['to_year'], $validated['to_quarter']);

        if ($fromKey > $toKey) {
            [$fromKey, $toKey] = [$toKey, $fromKey];
        }

        if (($toKey - $fromKey + 1) > 40) {
            return response()->json(['error' => 'That range is too large — please pick 40 quarters (10 years) or fewer.'], 422);
        }

        $quarters = [];
        for ($k = $fromKey; $k <= $toKey; $k++) {
            $p = $this->quarterFromKey($k);
            $quarters[] = $this->computeQuarterKpis($p['year'], $p['quarter'], false);
        }

        // Every project completed inside the range, for the report's project-level tables
        $from       = $this->quarterFromKey($fromKey);
        $to         = $this->quarterFromKey($toKey);
        $rangeStart = Carbon::create($from['year'], ($from['quarter'] - 1) * 3 + 1, 1)->startOfDay();
        $rangeEnd   = Carbon::create($to['year'], $to['quarter'] * 3, 1)->endOfMonth();
        $projects   = $this->loadCompletedData()['projects']
            ->filter(fn (Project $p) => $p->completed_at->between($rangeStart, $rangeEnd))
            ->sortBy('completed_at')
            ->values()
            ->map(function (Project $p) {
                $f         = $this->projectFigures($p);
                $netProfit = $f['received'] - $f['totalActualSpend'] - $f['overheadCost'];
                $delayDays = (!$f['onTime'] && $p->end_date) ? (int) $p->end_date->diffInDays($p->completed_at->copy()->startOfDay()) : 0;
                return [
                    'code'          => $p->code,
                    'name'          => $p->name,
                    'client'        => $p->live_client_name ?? $p->client,
                    'quarter'       => 'Q' . (int) ceil($p->completed_at->month / 3) . ' ' . $p->completed_at->year,
                    'completed_on'  => $p->completed_at->format('M d, Y'),
                    'due_on'        => $p->end_date?->format('M d, Y'),
                    'on_time'       => $f['onTime'],
                    'delay_days'    => $delayDays,
                    'contract'      => round((float) ($f['payment']->contract_amount ?? 0), 2),
                    'revenue'       => round($f['received'], 2),
                    'mat_cost'      => round($f['actualMatSpend'], 2),
                    'labor_cost'    => round($f['actualLaborCost'], 2),
                    'overhead_cost' => round($f['overheadCost'], 2),
                    'net_profit'    => round($netProfit, 2),
                    'margin'        => $f['received'] > 0 ? round($netProfit / $f['received'] * 100, 1) : null,
                    'budget'        => round($f['bomBudget'], 2),
                    'adherence'     => $f['bomBudget'] > 0 ? round($f['totalActualSpend'] / $f['bomBudget'] * 100, 1) : null,
                ];
            });

        $site = \App\Models\SiteSetting::instance();

        return response()->json([
            'from_label'   => $quarters[0]['label'],
            'to_label'     => end($quarters)['label'],
            'quarters'     => $quarters,
            'projects'     => $projects,
            'company'      => [
                'address' => $site->address,
                'phone'   => $site->phone ?: $site->mobile,
                'email'   => $site->email,
            ],
            'prepared_by'  => session('full_name') ?: session('name') ?: 'Administrator',
            'generated_at' => now()->format('M j, Y g:i A'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Save targets
    |--------------------------------------------------------------------------
    */
    public function saveQuarterTargets(Request $request)
    {
        $validated = $request->validate([
            'year'                => 'required|integer|min:2000|max:2100',
            'quarter'             => 'required|integer|min:1|max:4',
            'profit_target_m1'    => 'required|numeric|min:0',
            'profit_target_m2'    => 'required|numeric|min:0',
            'profit_target_m3'    => 'required|numeric|min:0',
            // On-time delivery target is a RATE in percent (e.g. 90), one for the whole quarter
            'on_time_target'      => 'required|numeric|min:0|max:100',
        ]);

        if ($this->isFinalized((int) $validated['year'], (int) $validated['quarter'])) {
            return response()->json([
                'error' => 'This quarter has already ended and its targets are finalized — they can no longer be changed.',
            ], 422);
        }

        // Budget adherence is no longer an owner-set target (the KPI card now shows a fixed
        // benchmark range instead) — leave that column alone so any pre-existing value survives.
        // profit_target stays the quarterly total, auto-summed from the three monthly figures.
        // on_time_target is a rate, so it is never summed: the quarter's rate is also stored
        // on each month, so a month view is judged against the same rate.
        $this->quarterTargets = null;
        KpiQuarterTarget::updateOrCreate(
            ['year' => $validated['year'], 'quarter' => $validated['quarter']],
            [
                'profit_target_m1'  => $validated['profit_target_m1'],
                'profit_target_m2'  => $validated['profit_target_m2'],
                'profit_target_m3'  => $validated['profit_target_m3'],
                'profit_target'     => $validated['profit_target_m1'] + $validated['profit_target_m2'] + $validated['profit_target_m3'],
                'on_time_target'    => round((float) $validated['on_time_target'], 2),
                'on_time_target_m1' => round((float) $validated['on_time_target'], 2),
                'on_time_target_m2' => round((float) $validated['on_time_target'], 2),
                'on_time_target_m3' => round((float) $validated['on_time_target'], 2),
            ]
        );

        return response()->json($this->buildPayload((int) $validated['year'], (int) $validated['quarter']));
    }

    public function saveProjectTargets(Request $request)
    {
        $validated = $request->validate([
            'min_profit_per_project'  => 'required|numeric|min:0',
            'max_duration_days'       => 'required|integer|min:0',
            'budget_adherence_target' => 'required|numeric|min:0|max:1000',
        ]);

        $target = KpiProjectTarget::instance();
        $target->update($validated);

        return response()->json(['success' => true, 'projectTargets' => $target->fresh()]);
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    /** The real, live "today" quarter — the only quarter (alongside future ones) that's still editable. */
    private function currentPeriod(): array
    {
        return ['year' => (int) now()->year, 'quarter' => (int) ceil(now()->month / 3)];
    }

    /** A single sortable/comparable integer for a (year, quarter) pair. */
    private function quarterKey(int $year, int $quarter): int
    {
        return $year * 4 + $quarter;
    }

    private function quarterFromKey(int $key): array
    {
        $year    = intdiv($key - 1, 4);
        $quarter = $key - $year * 4;

        return ['year' => $year, 'quarter' => $quarter];
    }

    /** A quarter is finalized (read-only) once it has fully ended — i.e. it's chronologically before the current quarter. */
    private function isFinalized(int $year, int $quarter): bool
    {
        $current = $this->currentPeriod();

        return ($year * 4 + $quarter) < ($current['year'] * 4 + $current['quarter']);
    }

    /** Same idea as isFinalized(), at month granularity. */
    private function isMonthFinalized(int $year, int $month): bool
    {
        $now = Carbon::now();

        return ($year * 12 + $month) < ($now->year * 12 + $now->month);
    }

    /** Selectable years — from the earliest year with completed-project data through the current year. */
    private function availableYears(): array
    {
        $currentYear = (int) now()->year;

        // Earliest completion year, taken from the completed projects already loaded for this page
        $earliestYear = (int) ($this->loadCompletedData()['projects']->min(fn ($p) => $p->completed_at->year) ?? $currentYear);

        $minYear = min($earliestYear, $currentYear);

        return range($currentYear, $minYear); // descending — PHP's range() counts down when start > end
    }

    private function quarterRange(int $year, int $quarter): array
    {
        $startMonth = ($quarter - 1) * 3 + 1;
        $start = Carbon::create($year, $startMonth, 1)->startOfDay();
        $end   = (clone $start)->addMonths(2)->endOfMonth()->endOfDay();

        return [$start, $end];
    }

    /**
     * Full scorecard + trend + target payload for one period. When $month is given,
     * the main "scorecard" reflects that single month instead of the whole quarter
     * (targets are set per month, so admins expect the cards to move at that same
     * granularity) — "quarter_scorecard" is always the containing quarter's full
     * object regardless, since the trend/forecast/Set-Targets-modal/Monthly-Breakdown
     * table are all inherently quarter-scoped and must never silently swap to a
     * single month's numbers.
     */
    private function buildPayload(int $year, int $quarter, ?int $month = null): array
    {
        $quarterScorecard = $this->computeQuarterKpis($year, $quarter, true);
        $scorecard = $month ? $this->computeMonthKpis($year, $month) : $quarterScorecard;

        $trend = [];
        for ($i = 3; $i >= 0; $i--) {
            $offset = $quarter - 1 - $i; // 0-based quarter index offset
            $y = $year + intdiv($offset, 4);
            $q = $offset % 4;
            if ($q < 0) { $q += 4; $y -= 1; }
            $q += 1;

            // The trailing 4-quarter window always ends on the selected quarter — reuse
            // the scorecard already computed above instead of paying for it twice, and
            // skip the per-month breakdown for the other 3 (trend/forecast never show it).
            $trend[] = ($y === $year && $q === $quarter)
                ? $quarterScorecard
                : $this->computeQuarterKpis($y, $q, false);
        }

        return [
            'year'              => $year,
            'quarter'           => $quarter,
            'month'             => $month,
            'availableYears'    => $this->availableYears(),
            'scorecard'         => $scorecard,
            'quarter_scorecard' => $quarterScorecard,
            'trend'             => $trend,
            'forecast'          => $this->computeForecast($year, $quarter, $trend),
        ];
    }

    /**
     * Simple Moving Average forecast for the quarter right after the selected one.
     * Averages the trailing 4 quarters shown in the Performance Trend tab, skipping any
     * quarter that had zero completed projects so an idle quarter doesn't drag the average
     * down to a false zero.
     */
    private function computeForecast(int $year, int $quarter, array $trend): array
    {
        $nextYear    = $year;
        $nextQuarter = $quarter + 1;
        if ($nextQuarter > 4) {
            $nextQuarter = 1;
            $nextYear++;
        }
        $targetLabel = 'Q' . $nextQuarter . ' ' . $nextYear;

        $qualifying = array_values(array_filter($trend, fn ($t) => $t['project_count'] > 0));
        $n = count($qualifying);

        if ($n === 0) {
            return [
                'has_data'     => false,
                'target_label' => $targetLabel,
            ];
        }

        $avg = fn (callable $pick) => array_sum(array_map($pick, $qualifying)) / $n;

        $current = end($trend); // the selected quarter itself — the most recent point in the window

        return [
            'has_data'     => true,
            'target_label' => $targetLabel,
            'window_label' => implode(', ', array_map(fn ($t) => $t['label'], $qualifying)),
            'sample_size'  => $n,
            'profit'       => [
                'net_profit' => round($avg(fn ($t) => $t['profit']['net_profit']), 2),
                'avg_margin' => round($avg(fn ($t) => $t['profit']['avg_margin']), 1),
                'vs_current' => round($avg(fn ($t) => $t['profit']['net_profit']) - $current['profit']['net_profit'], 2),
            ],
            'on_time'      => (function () use ($qualifying, $current) {
                // total on-time ÷ total completed across the window — not an average of quarterly rates
                $onTime    = array_sum(array_map(fn ($t) => $t['on_time']['on_time_count'], $qualifying));
                $completed = array_sum(array_map(fn ($t) => $t['on_time']['total_completed'], $qualifying));
                $rate      = $completed > 0 ? round(($onTime / $completed) * 100, 1) : 0.0;

                return [
                    'rate'       => $rate,
                    'on_time'    => $onTime,
                    'completed'  => $completed,
                    'vs_current' => $current['on_time']['has_data'] ? round($rate - $current['on_time']['rate'], 1) : null,   // percentage points
                ];
            })(),
            'budget'       => [
                'adherence_rate' => round($avg(fn ($t) => $t['budget']['adherence_rate']), 1),
                'vs_current'     => round($avg(fn ($t) => $t['budget']['adherence_rate']) - $current['budget']['adherence_rate'], 1),
            ],
        ];
    }

    /**
     * Raw actuals (revenue, costs, on-time count, etc.) for any date range — the
     * shared core both the quarter and month scorecards are built from, so the two
     * granularities can never silently drift apart in how a number is computed.
     */
    /** All saved quarter targets keyed "year-quarter", loaded once per request. */
    private ?\Illuminate\Support\Collection $quarterTargets = null;

    private function quarterTarget(int $year, int $quarter): ?KpiQuarterTarget
    {
        $this->quarterTargets ??= KpiQuarterTarget::all()->keyBy(fn ($t) => $t->year . '-' . $t->quarter);

        return $this->quarterTargets->get($year . '-' . $quarter);
    }

    /** Every completed project and its money figures, loaded once per request (see loadCompletedData()). */
    private ?array $completedData = null;

    /**
     * One page shows many periods (the selected quarter or month, the 4-quarter trend, the
     * forecast, the monthly breakdown). Instead of re-running the same queries for each of
     * them — every query is a round trip to the remote database — all completed projects
     * and their totals are fetched once here, and each period is then cut from it in memory.
     */
    private function loadCompletedData(): array
    {
        if ($this->completedData !== null) {
            return $this->completedData;
        }

        $projects   = Project::where('status', 'completed')->whereNotNull('completed_at')->get();
        $projectIds = $projects->pluck('id');

        $paymentsByProject = Payment::whereIn('project_id', $projectIds)->get()->keyBy('project_id');

        return $this->completedData = [
            'projects'          => $projects,
            'paymentsByProject' => $paymentsByProject,
            'receivedByPayment' => PaymentTransaction::whereIn('payment_id', $paymentsByProject->pluck('id'))
                ->selectRaw('payment_id, SUM(amount_paid) as total')
                ->groupBy('payment_id')
                ->pluck('total', 'payment_id'),
            'matSpendByProject' => MaterialPurchase::whereIn('project_id', $projectIds)
                ->selectRaw('project_id, SUM(total_paid) as total')
                ->groupBy('project_id')
                ->pluck('total', 'project_id'),
            // Estimated materials — applies the same per-material waste/handling factor
            // Project::estimatedBudget() uses for Financial Overview's "Est. Materials",
            // so the live-estimate fallback below matches what it's standing in for.
            'bomMatCostByProject' => ProjectMaterial::whereIn('project_id', $projectIds)
                ->where('status', 'active')
                ->selectRaw('project_id, SUM(total_cost * (1 + COALESCE(factor, 7) / 100.0)) as total')
                ->groupBy('project_id')
                ->pluck('total', 'project_id'),
            'laborPivotByProject' => \DB::table('salary_record_project')
                ->whereIn('project_id', $projectIds)
                ->selectRaw('project_id, SUM(allocated_pay) as total')
                ->groupBy('project_id')
                ->pluck('total', 'project_id'),
            // Active-only, matching Project::estimatedBudget()'s labor() — used both as the
            // actual-cost fallback and as the live estimate's labor component below.
            'activeLaborByProject' => ProjectLabor::whereIn('project_id', $projectIds)
                ->where('status', 'active')
                ->selectRaw('project_id, SUM(total_cost) as total')
                ->groupBy('project_id')
                ->pluck('total', 'project_id'),
            // Monthly overhead allocated to each project — same source Financial Overview's
            // Net Profit subtracts, so Project Profit Margin reflects it too.
            'overheadByProject' => \DB::table('monthly_expense_projects')
                ->whereIn('project_id', $projectIds)
                ->selectRaw('project_id, SUM(allocated_amount) as total')
                ->groupBy('project_id')
                ->pluck('total', 'project_id'),
        ];
    }

    /**
     * One completed project's money and delivery figures — the single place they're worked out,
     * shared by the period totals and the per-project table in the generated report.
     */
    private function projectFigures(Project $project): array
    {
        $data    = $this->loadCompletedData();
        $payment = $data['paymentsByProject']->get($project->id);

        $received = $payment ? (float) ($data['receivedByPayment'][$payment->id] ?? 0) : 0;

        $actualMatSpend  = (float) ($data['matSpendByProject'][$project->id] ?? 0);
        $actualLaborCost = (float) ($data['laborPivotByProject'][$project->id] ?? 0);
        if ($actualLaborCost == 0) {
            $actualLaborCost = (float) ($data['activeLaborByProject'][$project->id] ?? 0);
        }
        $totalActualSpend = $actualMatSpend + $actualLaborCost;
        $overheadCost     = (float) ($data['overheadByProject'][$project->id] ?? 0);

        $bomMatCost   = (float) ($data['bomMatCostByProject'][$project->id] ?? 0);
        $bomLaborCost = (float) ($data['activeLaborByProject'][$project->id] ?? 0);

        // Budget Adherence measures against the frozen Project Budget locked in at
        // payment setup — the same value Project Financial Overview shows — instead
        // of a live BOM total that would drift every time the BOM changes. Falls
        // back to the live estimate only for payments that predate that field.
        $bomBudget = ($payment && (float) $payment->project_budget > 0)
            ? (float) $payment->project_budget
            : $bomMatCost + $bomLaborCost;

        $onTime = $project->end_date && $project->completed_at->copy()->startOfDay()->lte($project->end_date);

        return compact('payment', 'received', 'actualMatSpend', 'actualLaborCost', 'totalActualSpend', 'overheadCost', 'bomBudget', 'onTime');
    }

    private function computeActualsForRange(Carbon $start, Carbon $end): array
    {
        $data = $this->loadCompletedData();

        $projects = $data['projects']->filter(fn (Project $p) => $p->completed_at->between($start, $end))->values();

        [
            'paymentsByProject' => $paymentsByProject, 'receivedByPayment' => $receivedByPayment,
            'matSpendByProject' => $matSpendByProject, 'bomMatCostByProject' => $bomMatCostByProject,
            'laborPivotByProject' => $laborPivotByProject, 'activeLaborByProject' => $activeLaborByProject,
            'overheadByProject' => $overheadByProject,
        ] = $data;


        $totalRevenue        = 0.0;
        $totalActualCost     = 0.0;
        $totalOverhead       = 0.0;
        $totalEstBudget      = 0.0;
        $totalMatSpend       = 0.0;
        $totalLaborSpend     = 0.0;
        $totalContracted     = 0.0;
        $totalDelayDays      = 0;
        $overBudgetCount     = 0;
        $onTimeCount         = 0;
        $delayedProjectCodes = [];

        foreach ($projects as $project) {
            [
                'payment' => $payment, 'received' => $received, 'actualMatSpend' => $actualMatSpend,
                'actualLaborCost' => $actualLaborCost, 'totalActualSpend' => $totalActualSpend,
                'overheadCost' => $overheadCost, 'bomBudget' => $bomBudget, 'onTime' => $onTime,
            ] = $this->projectFigures($project);

            $totalRevenue    += $received;
            $totalActualCost += $totalActualSpend;
            $totalOverhead   += $overheadCost;
            $totalEstBudget  += $bomBudget;
            $totalMatSpend   += $actualMatSpend;
            $totalLaborSpend += $actualLaborCost;
            $totalContracted += $payment ? (float) $payment->contract_amount : 0;

            if ($bomBudget > 0 && $totalActualSpend > $bomBudget) {
                $overBudgetCount++;
            }

            if ($onTime) {
                $onTimeCount++;
            } else {
                $delayedProjectCodes[] = $project->code;
                if ($project->end_date) {
                    $totalDelayDays += (int) $project->end_date->diffInDays($project->completed_at);
                }
            }
        }

        return [
            'total_completed'       => $projects->count(),
            'total_revenue'         => $totalRevenue,
            'total_actual_cost'     => $totalActualCost,
            'total_overhead'        => $totalOverhead,
            'total_est_budget'      => $totalEstBudget,
            'total_mat_spend'       => $totalMatSpend,
            'total_labor_spend'     => $totalLaborSpend,
            'total_contracted'      => $totalContracted,
            'total_delay_days'      => $totalDelayDays,
            'over_budget_count'     => $overBudgetCount,
            'on_time_count'         => $onTimeCount,
            'delayed_project_codes' => $delayedProjectCodes,
        ];
    }

    /**
     * Builds the profit/on_time/budget scorecard shape from a set of actuals
     * (see computeActualsForRange()) and the target values to compare against —
     * shared by both the quarter and month scorecards.
     */
    private function formatScorecard(
        array $a,
        ?float $profitTarget,
        ?float $onTimeTarget,
        ?float $budgetTarget,
        bool $hasTarget,
        array $profitTargetMonthly,
        array $onTimeTargetMonthly
    ): array {
        $totalCompleted = $a['total_completed'];
        $netProfit      = $a['total_revenue'] - $a['total_actual_cost'] - $a['total_overhead'];
        $avgMargin      = $a['total_revenue'] > 0 ? round(($netProfit / $a['total_revenue']) * 100, 1) : 0.0;
        $onTimeRate     = $totalCompleted > 0 ? round(($a['on_time_count'] / $totalCompleted) * 100, 1) : 0.0;
        $adherenceRate  = $a['total_est_budget'] > 0 ? round(($a['total_actual_cost'] / $a['total_est_budget']) * 100, 1) : 0.0;
        $delayedCount   = $totalCompleted - $a['on_time_count'];
        $avgDelayDays   = $delayedCount > 0 ? (int) round($a['total_delay_days'] / $delayedCount) : 0;
        $netSavings     = $a['total_est_budget'] - $a['total_actual_cost'];
        // a rate can only be judged against its target once the period has completed projects
        $onTimeHasResult = $onTimeTarget !== null && $totalCompleted > 0;

        return [
            'profit' => [
                'net_profit'   => round($netProfit, 2),
                'avg_margin'   => $avgMargin,
                'revenue'      => round($a['total_revenue'], 2),
                'mat_cost'     => round($a['total_mat_spend'], 2),
                'labor_cost'   => round($a['total_labor_spend'], 2),
                'overhead_cost'=> round($a['total_overhead'], 2),
                'has_target'   => $hasTarget,
                'target'       => $profitTarget,
                'target_monthly' => $profitTargetMonthly,
                'variance'     => $hasTarget ? round($netProfit - $profitTarget, 2) : null,
                'hit'          => $hasTarget ? ($netProfit >= $profitTarget) : null,
                'progress_pct' => $hasTarget ? ($profitTarget > 0 ? min(100, round(($netProfit / $profitTarget) * 100, 1)) : ($netProfit > 0 ? 100 : 0)) : null,
                'scale'        => $this->profitMarginScale($avgMargin),
            ],
            // On-time delivery RATE = projects finished on or before the deadline ÷ completed
            // projects × 100, recomputed from the period's own counts (never an average of
            // monthly rates). Judged against the owner's target rate in percentage points.
            'on_time' => [
                'on_time_count'   => $a['on_time_count'],
                'total_completed' => $totalCompleted,
                'has_data'        => $totalCompleted > 0,
                'rate'            => $onTimeRate,
                'delayed_count'   => $delayedCount,
                'avg_delay_days'  => $avgDelayDays,
                'has_target'      => $onTimeTarget !== null,
                'target'          => $onTimeTarget,
                'target_monthly'  => $onTimeTargetMonthly,
                'variance'        => $onTimeHasResult ? round($onTimeRate - $onTimeTarget, 1) : null,   // percentage points
                'level'           => $onTimeHasResult ? $this->onTimeLevel($onTimeRate, $onTimeTarget) : null,
                'hit'             => $onTimeHasResult ? ($onTimeRate >= $onTimeTarget) : null,
                'progress_pct'    => $onTimeHasResult ? ($onTimeTarget > 0 ? min(100, round(($onTimeRate / $onTimeTarget) * 100, 1)) : 100) : null,
                'scale'           => $this->onTimeScale($onTimeRate),
                'delayed_projects'=> $a['delayed_project_codes'],
            ],
            'budget' => [
                'adherence_rate'    => $adherenceRate,
                'actual_cost'       => round($a['total_actual_cost'], 2),
                'estimated_budget'  => round($a['total_est_budget'], 2),
                'total_contracted'  => round($a['total_contracted'], 2),
                'net_savings'       => round($netSavings, 2),
                'over_budget_count' => $a['over_budget_count'],
                'total_completed'   => $totalCompleted,
                'has_target'        => $hasTarget,
                'target'            => $budgetTarget,
                'variance'          => $hasTarget ? round($adherenceRate - $budgetTarget, 1) : null,
                'hit'               => $hasTarget ? ($adherenceRate >= $budgetTarget) : null,
                'progress_pct'      => $hasTarget ? ($budgetTarget > 0 ? min(100, round(($adherenceRate / $budgetTarget) * 100, 1)) : ($adherenceRate > 0 ? 100 : 0)) : null,
                'scale'             => $this->budgetAdherenceScale($adherenceRate),
            ],
        ];
    }

    /**
     * Compute the 3 KPIs (+ industry scale + target comparison) for one quarter.
     * $includeMonthlyBreakdown skips the 3 extra per-month computations when the
     * caller only needs the quarter totals (trend/forecast/report quarters never
     * display the monthly breakdown, only the main scorecard does).
     */
    private function computeQuarterKpis(int $year, int $quarter, bool $includeMonthlyBreakdown = true): array
    {
        [$start, $end] = $this->quarterRange($year, $quarter);
        $a = $this->computeActualsForRange($start, $end);

        // Targets are strictly per-quarter — a quarter with no target explicitly saved for it
        // has no target at all (never borrowed from another quarter).
        $target     = $this->quarterTarget($year, $quarter);
        $hasTarget  = $target !== null;

        $profitTarget = $hasTarget ? (float) $target->profit_target : null;
        $onTimeTarget = ($hasTarget && $target->on_time_target !== null) ? (float) $target->on_time_target : null;
        $budgetTarget = $hasTarget ? (float) $target->budget_adherence_target : null;

        $profitTargetMonthly = $hasTarget
            ? [(float) $target->profit_target_m1, (float) $target->profit_target_m2, (float) $target->profit_target_m3]
            : [0, 0, 0];
        $onTimeTargetMonthly = [$onTimeTarget, $onTimeTarget, $onTimeTarget];   // one rate for the whole quarter

        $current = $this->currentPeriod();

        // Per-month actuals within this quarter, paired with the monthly targets set
        // in "Set KPI targets" — powers the Monthly Breakdown table. Targets are set
        // per month, so admins expect to see progress at that same granularity, not
        // just the quarter as a whole.
        $monthlyBreakdown = [];
        if ($includeMonthlyBreakdown) {
            $startMonth = ($quarter - 1) * 3 + 1;
            for ($i = 0; $i < 3; $i++) {
                $monthActual = $this->computeMonthActuals($year, $startMonth + $i);
                $monthlyBreakdown[] = [
                    'label'          => $monthActual['label'],
                    'project_count'  => $monthActual['project_count'],
                    'profit_actual'  => $monthActual['net_profit'],
                    'profit_target'  => $hasTarget ? $profitTargetMonthly[$i] : null,
                    'on_time_actual' => $monthActual['on_time_count'],
                    'on_time_rate'   => $monthActual['on_time_rate'],
                    'on_time_target' => $onTimeTarget,
                ];
            }
        }

        $scorecard = $this->formatScorecard($a, $profitTarget, $onTimeTarget, $budgetTarget, $hasTarget, $profitTargetMonthly, $onTimeTargetMonthly);

        return array_merge([
            'year'              => $year,
            'quarter'           => $quarter,
            'month'             => null,
            'label'             => 'Q' . $quarter . ' ' . $year,
            'project_count'     => $a['total_completed'],
            'is_current'        => ($year === $current['year'] && $quarter === $current['quarter']),
            'is_finalized'      => $this->isFinalized($year, $quarter),
            'monthly_breakdown' => $monthlyBreakdown,
        ], $scorecard);
    }

    /**
     * Compute the full scorecard (profit/on_time/budget + target comparison) for a
     * single calendar month, using that month's own target — the one entered as
     * m1/m2/m3 on the containing quarter's "Set KPI targets". Budget Adherence has
     * no monthly-specific benchmark (it's a fixed tolerance band, not something
     * split across months), so it reuses the quarter's.
     */
    private function computeMonthKpis(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = (clone $start)->endOfMonth()->endOfDay();
        $a = $this->computeActualsForRange($start, $end);

        $quarter    = (int) ceil($month / 3);
        $monthIndex = ($month - 1) % 3; // 0, 1, or 2 within that quarter

        $target    = $this->quarterTarget($year, $quarter);
        $hasTarget = $target !== null;

        $profitField = 'profit_target_m' . ($monthIndex + 1);

        $profitTarget = $hasTarget ? (float) $target->$profitField : null;
        $onTimeTarget = ($hasTarget && $target->on_time_target !== null) ? (float) $target->on_time_target : null;   // the quarter's rate
        $budgetTarget = $hasTarget ? (float) $target->budget_adherence_target : null;

        // target_monthly only matters for the quarter-level scorecard (it's what
        // "Set KPI targets" reads to prefill its 3 monthly inputs) — unused here.
        $scorecard = $this->formatScorecard($a, $profitTarget, $onTimeTarget, $budgetTarget, $hasTarget, [0, 0, 0], [0, 0, 0]);

        $now = Carbon::now();

        return array_merge([
            'year'              => $year,
            'quarter'           => $quarter,
            'month'             => $month,
            'label'             => $start->format('F Y'),
            'project_count'     => $a['total_completed'],
            'is_current'        => ($year === $now->year && $month === $now->month),
            'is_finalized'      => $this->isMonthFinalized($year, $month),
            'monthly_breakdown' => [],
        ], $scorecard);
    }

    /**
     * Net profit + on-time delivery actuals for a single calendar month — a lighter
     * subset of computeMonthKpis() (no target comparison, no budget), used only to
     * populate the Monthly Breakdown table's rows.
     */
    private function computeMonthActuals(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = (clone $start)->endOfMonth()->endOfDay();
        $a = $this->computeActualsForRange($start, $end);

        return [
            'label'         => $start->format('M Y'),
            'project_count' => $a['total_completed'],
            'net_profit'    => round($a['total_revenue'] - $a['total_actual_cost'] - $a['total_overhead'], 2),
            'on_time_count' => $a['on_time_count'],
            // recomputed from this month's own counts
            'on_time_rate'  => $a['total_completed'] > 0 ? round(($a['on_time_count'] / $a['total_completed']) * 100, 1) : null,
        ];
    }

    /**
     * On-time status: Target hit when the rate is at or above the target rate; Tolerable when it
     * is below but within 10 percentage points; Below target when more than 10 points below.
     */
    private function onTimeLevel(float $rate, float $target): string
    {
        if ($rate >= $target) {
            return 'hit';
        }

        return ($target - $rate) <= 10 ? 'tolerable' : 'below';
    }

    /** Source: Tangle Research 2026 FMA Survey. */
    private function profitMarginScale(float $margin): array
    {
        return match (true) {
            $margin < 6.2  => ['label' => 'Below industry average', 'tone' => 'danger',  'range' => 'Below 6.2%'],
            $margin < 10.0 => ['label' => 'Industry average',       'tone' => 'neutral',  'range' => '6.2%–10%'],
            $margin < 25.0 => ['label' => 'Above average',          'tone' => 'info',     'range' => '10%–25%'],
            $margin < 35.0 => ['label' => 'Top quartile',           'tone' => 'success',  'range' => '25%–35%'],
            default        => ['label' => 'Exceeding',              'tone' => 'success',  'range' => 'Above 35%'],
        } + ['source' => 'Tangle Research 2026 FMA Survey'];
    }

    /** Source: Tangle Research 2026 FMA Survey. */
    private function onTimeScale(float $rate): array
    {
        return match (true) {
            $rate < 84.0  => ['label' => 'Below industry average', 'tone' => 'danger',  'range' => 'Below 84%'],
            $rate < 90.0  => ['label' => 'Industry average',       'tone' => 'neutral', 'range' => '84%–89%'],
            $rate < 100.0 => ['label' => 'Top quartile',           'tone' => 'success', 'range' => '90%–99%'],
            default       => ['label' => 'Exceeding',              'tone' => 'success', 'range' => '100%'],
        } + ['source' => 'Tangle Research 2026 FMA Survey'];
    }

    /** Source: KPI Depot 2024. */
    private function budgetAdherenceScale(float $rate): array
    {
        return match (true) {
            $rate > 110.0 => ['label' => 'Severely over budget',  'tone' => 'danger',  'range' => 'Above 110%'],
            $rate > 100.0 => ['label' => 'Over budget',           'tone' => 'warning', 'range' => '101%–110%'],
            $rate >= 90.0 => ['label' => 'On target',             'tone' => 'success', 'range' => '90%–100%'],
            $rate >= 80.0 => ['label' => 'Under budget',          'tone' => 'info',    'range' => '80%–89%'],
            default       => ['label' => 'Significantly under budget', 'tone' => 'info', 'range' => 'Below 80%'],
        } + ['source' => 'KPI Depot 2024'];
    }
}
