<?php

namespace App\Http\Controllers;

use App\Models\MonthlyExpense;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonthlyExpenseController extends Controller
{
    /** GET /admin/monthly-expenses */
    public function index(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));

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
            ->orderBy('name')
            ->get(['id', 'name', 'client']);

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

        return view('admin.monthly_expenses', compact(
            'month', 'expenses', 'total', 'allocated',
            'projects', 'perProject', 'months', 'history'
        ));
    }

    /** POST /admin/monthly-expenses — add a single expense line */
    public function store(Request $request)
    {
        $v = $request->validate([
            'month_year'  => 'required|regex:/^\d{4}-\d{2}$/',
            'category'    => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'amount'      => 'required|numeric|min:0.01',
        ]);

        MonthlyExpense::create($v);

        return redirect()->route('admin.monthly-expenses.index', ['month' => $v['month_year']])
            ->with('success', 'Expense added.');
    }

    /** DELETE /admin/monthly-expenses/{id} */
    public function destroy(int $id)
    {
        $exp = MonthlyExpense::findOrFail($id);
        $month = $exp->month_year;
        $exp->delete();

        // Recompute allocations for that month
        $this->recomputeAllocations($month);

        return redirect()->route('admin.monthly-expenses.index', ['month' => $month])
            ->with('success', 'Expense deleted.');
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

        // Remove previous allocations for this month
        DB::table('monthly_expense_projects')->where('month_year', $month)->delete();

        if (count($projectIds) > 0) {
            $total = MonthlyExpense::totalForMonth($month);
            $n = count($projectIds);
            $per = floor($total * 100 / $n) / 100;
            $remainder = round($total - $per * $n, 2);

            foreach ($projectIds as $i => $pid) {
                DB::table('monthly_expense_projects')->insert([
                    'month_year'       => $month,
                    'project_id'       => $pid,
                    'allocated_amount' => $per + ($i === $n - 1 ? $remainder : 0),
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }

        return redirect()->route('admin.monthly-expenses.index', ['month' => $month])
            ->with('success', 'Allocation saved — overhead split across ' . count($projectIds) . ' project(s).');
    }

    private function recomputeAllocations(string $month): void
    {
        $existing = DB::table('monthly_expense_projects')
            ->where('month_year', $month)
            ->pluck('project_id')
            ->toArray();

        if (empty($existing)) return;

        $total = MonthlyExpense::totalForMonth($month);
        $n = count($existing);
        $per = floor($total * 100 / $n) / 100;
        $remainder = round($total - $per * $n, 2);

        foreach ($existing as $i => $pid) {
            DB::table('monthly_expense_projects')
                ->where('month_year', $month)
                ->where('project_id', $pid)
                ->update([
                    'allocated_amount' => $per + ($i === $n - 1 ? $remainder : 0),
                    'updated_at'       => now(),
                ]);
        }
    }
}
