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
    public function index()
    {
        $current = $this->currentPeriod();
        $data    = $this->buildPayload($current['year'], $current['quarter']);

        return view('admin.kpi_dashboard', [
            'initialYear'      => $current['year'],
            'initialQuarter'   => $current['quarter'],
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

        return response()->json([
            'from_label'   => $quarters[0]['label'],
            'to_label'     => end($quarters)['label'],
            'quarters'     => $quarters,
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
            'on_time_target_m1'   => 'required|integer|min:0',
            'on_time_target_m2'   => 'required|integer|min:0',
            'on_time_target_m3'   => 'required|integer|min:0',
        ]);

        if ($this->isFinalized((int) $validated['year'], (int) $validated['quarter'])) {
            return response()->json([
                'error' => 'This quarter has already ended and its targets are finalized — they can no longer be changed.',
            ], 422);
        }

        // Budget adherence is no longer an owner-set target (the KPI card now shows a fixed
        // benchmark range instead) — leave that column alone so any pre-existing value survives.
        // profit_target / on_time_target stay as the quarterly totals, auto-summed from the
        // three monthly figures the modal collects — nothing downstream that reads those two
        // columns (cards, charts, trend, forecast) needs to change.
        KpiQuarterTarget::updateOrCreate(
            ['year' => $validated['year'], 'quarter' => $validated['quarter']],
            [
                'profit_target_m1'  => $validated['profit_target_m1'],
                'profit_target_m2'  => $validated['profit_target_m2'],
                'profit_target_m3'  => $validated['profit_target_m3'],
                'profit_target'     => $validated['profit_target_m1'] + $validated['profit_target_m2'] + $validated['profit_target_m3'],
                'on_time_target_m1' => $validated['on_time_target_m1'],
                'on_time_target_m2' => $validated['on_time_target_m2'],
                'on_time_target_m3' => $validated['on_time_target_m3'],
                'on_time_target'    => $validated['on_time_target_m1'] + $validated['on_time_target_m2'] + $validated['on_time_target_m3'],
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

        $earliestYear = (int) (Project::where('status', 'completed')
            ->selectRaw('MIN(EXTRACT(YEAR FROM updated_at)) as y')
            ->value('y') ?? $currentYear);

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
            'on_time'      => [
                'count'      => round($avg(fn ($t) => $t['on_time']['on_time_count']), 1),
                'rate'       => round($avg(fn ($t) => $t['on_time']['rate']), 1),
                'vs_current' => round($avg(fn ($t) => $t['on_time']['on_time_count']) - $current['on_time']['on_time_count'], 1),
            ],
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
    private function computeActualsForRange(Carbon $start, Carbon $end): array
    {
        $projects = Project::where('status', 'completed')
            ->whereBetween('updated_at', [$start, $end])
            ->get();

        $projectIds = $projects->pluck('id');

        $paymentsByProject = Payment::whereIn('project_id', $projectIds)->get()->keyBy('project_id');
        $paymentIds        = $paymentsByProject->pluck('id');

        $receivedByPayment = PaymentTransaction::whereIn('payment_id', $paymentIds)
            ->selectRaw('payment_id, SUM(amount_paid) as total')
            ->groupBy('payment_id')
            ->pluck('total', 'payment_id');

        $matSpendByProject = MaterialPurchase::whereIn('project_id', $projectIds)
            ->selectRaw('project_id, SUM(total_paid) as total')
            ->groupBy('project_id')
            ->pluck('total', 'project_id');

        // Estimated materials — applies the same per-material waste/handling factor
        // Project::estimatedBudget() uses for Financial Overview's "Est. Materials",
        // so the live-estimate fallback below matches what it's standing in for.
        $bomMatCostByProject = ProjectMaterial::whereIn('project_id', $projectIds)
            ->where('status', 'active')
            ->selectRaw('project_id, SUM(total_cost * (1 + COALESCE(factor, 7) / 100.0)) as total')
            ->groupBy('project_id')
            ->pluck('total', 'project_id');

        $laborPivotByProject = \DB::table('salary_record_project')
            ->whereIn('project_id', $projectIds)
            ->selectRaw('project_id, SUM(allocated_pay) as total')
            ->groupBy('project_id')
            ->pluck('total', 'project_id');

        // Active-only, matching Project::estimatedBudget()'s labor() — used both as the
        // actual-cost fallback and as the live estimate's labor component below.
        $activeLaborByProject = ProjectLabor::whereIn('project_id', $projectIds)
            ->where('status', 'active')
            ->selectRaw('project_id, SUM(total_cost) as total')
            ->groupBy('project_id')
            ->pluck('total', 'project_id');

        // Monthly overhead allocated to each project — same source Financial Overview's
        // Net Profit subtracts, so Project Profit Margin reflects it too.
        $overheadByProject = \DB::table('monthly_expense_projects')
            ->whereIn('project_id', $projectIds)
            ->selectRaw('project_id, SUM(allocated_amount) as total')
            ->groupBy('project_id')
            ->pluck('total', 'project_id');

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
            $payment  = $paymentsByProject->get($project->id);
            $received = $payment ? (float) ($receivedByPayment[$payment->id] ?? 0) : 0;

            $actualMatSpend  = (float) ($matSpendByProject[$project->id] ?? 0);
            $actualLaborCost = (float) ($laborPivotByProject[$project->id] ?? 0);
            if ($actualLaborCost == 0) {
                $actualLaborCost = (float) ($activeLaborByProject[$project->id] ?? 0);
            }
            $totalActualSpend = $actualMatSpend + $actualLaborCost;
            $overheadCost     = (float) ($overheadByProject[$project->id] ?? 0);

            $bomMatCost   = (float) ($bomMatCostByProject[$project->id] ?? 0);
            $bomLaborCost = (float) ($activeLaborByProject[$project->id] ?? 0);

            // Budget Adherence measures against the frozen Project Budget locked in at
            // payment setup — the same value Project Financial Overview shows — instead
            // of a live BOM total that would drift every time the BOM changes. Falls
            // back to the live estimate only for payments that predate that field.
            $bomBudget = ($payment && (float) $payment->project_budget > 0)
                ? (float) $payment->project_budget
                : $bomMatCost + $bomLaborCost;

            $onTime = $project->end_date && $project->updated_at->startOfDay()->lte($project->end_date);

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
                    $totalDelayDays += (int) $project->end_date->diffInDays($project->updated_at);
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
        ?int $onTimeTarget,
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
            'on_time' => [
                'on_time_count'   => $a['on_time_count'],
                'total_completed' => $totalCompleted,
                'rate'            => $onTimeRate,
                'delayed_count'   => $delayedCount,
                'avg_delay_days'  => $avgDelayDays,
                'has_target'      => $hasTarget,
                'target'          => $onTimeTarget,
                'target_monthly'  => $onTimeTargetMonthly,
                'variance'        => $hasTarget ? ($a['on_time_count'] - $onTimeTarget) : null,
                'hit'             => $hasTarget ? ($a['on_time_count'] >= $onTimeTarget) : null,
                'progress_pct'    => $hasTarget ? ($onTimeTarget > 0 ? min(100, round(($a['on_time_count'] / $onTimeTarget) * 100, 1)) : ($a['on_time_count'] > 0 ? 100 : 0)) : null,
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
        $target     = KpiQuarterTarget::forPeriod($year, $quarter);
        $hasTarget  = $target !== null;

        $profitTarget = $hasTarget ? (float) $target->profit_target : null;
        $onTimeTarget = $hasTarget ? (int) $target->on_time_target : null;
        $budgetTarget = $hasTarget ? (float) $target->budget_adherence_target : null;

        $profitTargetMonthly = $hasTarget
            ? [(float) $target->profit_target_m1, (float) $target->profit_target_m2, (float) $target->profit_target_m3]
            : [0, 0, 0];
        $onTimeTargetMonthly = $hasTarget
            ? [(int) $target->on_time_target_m1, (int) $target->on_time_target_m2, (int) $target->on_time_target_m3]
            : [0, 0, 0];

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
                    'on_time_target' => $hasTarget ? $onTimeTargetMonthly[$i] : null,
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

        $target    = KpiQuarterTarget::forPeriod($year, $quarter);
        $hasTarget = $target !== null;

        $profitField = 'profit_target_m' . ($monthIndex + 1);
        $onTimeField = 'on_time_target_m' . ($monthIndex + 1);

        $profitTarget = $hasTarget ? (float) $target->$profitField : null;
        $onTimeTarget = $hasTarget ? (int) $target->$onTimeField : null;
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
        ];
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
