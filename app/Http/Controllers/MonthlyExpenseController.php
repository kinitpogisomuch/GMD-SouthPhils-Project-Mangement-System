<?php

namespace App\Http\Controllers;

use App\Models\MonthlyExpense;
use App\Models\Project;
use App\Services\ProjectFunds;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonthlyExpenseController extends Controller
{
    /** GET /admin/monthly-expenses */
    public function index(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));

        // Came here from a project's "Settle Monthly Expenses" (completion step): remember the project
        // while the admin moves between months and saves, so the page can show what's still unsettled
        // and link straight back to the completion phase.
        if ($request->filled('from_project')) {
            session(['expense_return_project' => (int) $request->query('from_project')]);
        }
        if ($request->boolean('done_settling')) {
            session()->forget('expense_return_project');
        }
        $returnProject = session('expense_return_project')
            ? Project::whereKey(session('expense_return_project'))->where('status', '!=', 'completed')->first()
            : null;
        if (session('expense_return_project') && !$returnProject) {
            session()->forget('expense_return_project');   // completed or removed meanwhile
        }
        $returnProjectMissing = $returnProject ? $returnProject->unsettledExpenseMonths() : [];

        // Expense line items for selected month
        $expenses = MonthlyExpense::where('month_year', $month)
            ->orderBy('category')
            ->get();

        $total = $expenses->sum('amount');

        // Projects currently allocated this month
        $allocated = DB::table('monthly_expense_projects')
            ->where('month_year', $month)
            ->pluck('project_id')
            ->toArray();

        // All non-completed, non-archived projects for selection
        $projects = Project::whereNotIn('status', ['completed', 'archived'])
            ->with('tankItems')
            ->orderBy('name')
            ->get(['id', 'name', 'client', 'tank_type']);

        // Per-project amount for preview
        $perProject = count($allocated) > 0 && $total > 0
            ? round($total / count($allocated), 2)
            : 0;

        // Past months that have expense records
        $months = MonthlyExpense::select('month_year')
            ->distinct()
            ->orderByDesc('month_year')
            ->pluck('month_year');

        // History — last 6 months with totals
        $history = DB::table('monthly_expenses')
            ->select('month_year', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as items'))
            ->groupBy('month_year')
            ->orderByDesc('month_year')
            ->limit(12)
            ->get();

        // Custom categories previously typed in via "Other" — offered as regular
        // dropdown options from now on instead of having to retype them each time.
        $defaultCategories = ['Electricity', 'Water', 'Rent', 'Maintenance & Repair'];
        $customCategories  = MonthlyExpense::select('category')
            ->whereNotIn('category', $defaultCategories)
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        // What each project has received / spent, and what it already carries for this month — the
        // allocation panel warns when a project's new share is more than the money it has left
        $projectFunds = $projects->mapWithKeys(function ($p) {
            $f = ProjectFunds::summary($p->id);
            return [$p->id => ['received' => $f['received'], 'spent' => $f['spent'], 'available' => $f['available'], 'fund_owed' => $f['fund_owed']]];
        });
        $currentShares = DB::table('monthly_expense_projects')->where('month_year', $month)
            ->pluck('allocated_amount', 'project_id')->map(fn ($a) => (float) $a);

        return view('admin.monthly_expenses', compact(
            'month', 'expenses', 'total', 'allocated',
            'projects', 'perProject', 'months', 'history', 'customCategories',
            'projectFunds', 'currentShares', 'returnProject', 'returnProjectMissing'
        ));
    }

    /** POST /admin/monthly-expenses — add a single expense line */
    public function store(Request $request)
    {
        // Several expense items can be added at once from the "Add Expense Items" modal (items[]);
        // a single category/amount pair is still accepted.
        if (!$request->has('items') && $request->filled('category')) {
            $request->merge(['items' => [['category' => $request->input('category'), 'amount' => $request->input('amount'), 'description' => $request->input('description')]]]);
        }

        $v = $request->validate([
            'month_year'          => 'required|regex:/^\d{4}-\d{2}$/',
            'items'               => 'required|array|min:1|max:30',
            'items.*.category'    => 'required|string|max:100',
            'items.*.amount'      => 'required|numeric|min:0.01|max:99999999',
            'items.*.description' => 'nullable|string|max:255',
        ], [
            'items.required'            => 'Select at least one expense.',
            'items.*.category.required' => 'Every expense needs a category.',
            'items.*.amount.required'   => 'Enter an amount for every selected expense.',
            'items.*.amount.min'        => 'Every amount must be more than zero.',
        ]);

        DB::transaction(function () use ($v) {
            foreach ($v['items'] as $item) {
                MonthlyExpense::create([
                    'month_year'  => $v['month_year'],
                    'category'    => trim($item['category']),
                    'description' => $item['description'] ?? null,
                    'amount'      => $item['amount'],
                ]);
            }
        });

        $n = count($v['items']);
        return redirect()->route('admin.monthly-expenses.index', ['month' => $v['month_year']])
            ->with('success', $n === 1 ? 'Expense added.' : "{$n} expenses added.");
    }

    /** DELETE /admin/monthly-expenses/{id} */
    public function destroy(int $id)
    {
        $exp = MonthlyExpense::findOrFail($id);
        $month = $exp->month_year;
        $exp->delete();

        return redirect()->route('admin.monthly-expenses.index', ['month' => $month])
            ->with('success', 'Expense deleted. Click "Save Allocation" to re-split the updated overhead.');
    }

    /** POST /admin/monthly-expenses/allocate — save project selection & recompute split */
    public function allocate(Request $request)
    {
        $v = $request->validate([
            'month_year'  => 'required|regex:/^\d{4}-\d{2}$/',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
        ]);

        $month = $v['month_year'];
        $projectIds = $v['project_ids'] ?? [];

        // An allocation splits the month's logged expenses — with none logged there is nothing to save.
        if (!MonthlyExpense::where('month_year', $month)->exists()) {
            return redirect()->route('admin.monthly-expenses.index', ['month' => $month])
                ->with('error', 'Log at least one expense item for this month before saving the allocation.');
        }

        // The new split: the month's expenses shared equally (any leftover centavos on the last project)
        $shares = [];
        if (count($projectIds) > 0) {
            $total = MonthlyExpense::totalForMonth($month);
            $n = count($projectIds);
            $per = floor($total * 100 / $n) / 100;
            $remainder = round($total - $per * $n, 2);
            foreach (array_values($projectIds) as $i => $pid) {
                $shares[(int) $pid] = round($per + ($i === $n - 1 ? $remainder : 0), 2);
            }
        }

        // Overhead is paid from what each project's client has paid so far. Only the increase over what
        // the project was already given for this month counts; if a project can't afford it, the split is
        // only saved when the admin chose to cover the difference from the revolving fund.
        $before   = DB::table('monthly_expense_projects')->where('month_year', $month)->pluck('allocated_amount', 'project_id')->all();
        $increase = [];
        foreach ($shares as $pid => $share) {
            $increase[$pid] = round($share - (float) ($before[$pid] ?? 0), 2);
        }

        $useFund = $request->boolean('use_fund');
        if (!$useFund && ($overruns = ProjectFunds::overruns($increase))) {
            return redirect()->route('admin.monthly-expenses.index', ['month' => $month])
                ->with('fund_overrun', ProjectFunds::overrunPayload($overruns))
                ->with('fund_retry', ['month_year' => $month, 'project_ids' => array_values(array_map('intval', $projectIds))])
                ->with('error', 'The overhead share is more than the money received for ' . (count($overruns) === 1 ? 'one project' : count($overruns) . ' projects') . '. Nothing was saved yet.');
        }

        try {
            DB::transaction(function () use ($month, $shares, $increase, $useFund) {
                if ($useFund) {
                    $label = \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y');
                    ProjectFunds::coverOverruns($increase, "Overhead over Total Received — {$label}", 'overhead', $month);
                }

                // Replace the previous allocations for this month
                DB::table('monthly_expense_projects')->where('month_year', $month)->delete();
                foreach ($shares as $pid => $share) {
                    DB::table('monthly_expense_projects')->insert([
                        'month_year'       => $month,
                        'project_id'       => $pid,
                        'allocated_amount' => $share,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }
            });
        } catch (\DomainException $e) {
            return redirect()->route('admin.monthly-expenses.index', ['month' => $month])->with('error', $e->getMessage());
        }

        return redirect()->route('admin.monthly-expenses.index', ['month' => $month])
            ->with('success', 'Allocation saved — overhead split across ' . count($projectIds) . ' project(s).'
                . ($useFund ? ' The amount over Total Received was covered by the revolving fund.' : ''));
    }
}
