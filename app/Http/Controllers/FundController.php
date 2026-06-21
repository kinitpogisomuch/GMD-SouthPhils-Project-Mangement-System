<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FundTransaction;
use App\Models\FundSetting;
use App\Models\Project;
use App\Services\NotificationService;

class FundController extends Controller
{
    public function index()
    {
        $fund = FundSetting::instance();

        $currentBalance   = (float) $fund->current_balance;
        $initialBalance   = (float) $fund->initial_balance;
        $totalReleased    = FundTransaction::totalReleased();
        $totalReplenished = FundTransaction::totalReplenished();
        $activeAdvances   = FundTransaction::activeProjectAdvancesCount();

        // Drawn this month
        $totalDrawnThisMonth = (float) FundTransaction::where('type', 'release')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        // Pending replenishment = total released - total replenished (outstanding)
        $pendingReplenishment = max(0, $totalReleased - $totalReplenished);

        // Per-project outstanding for the balance tracker
        $projectOutstandings = FundTransaction::where('type', 'release')
            ->with('project:id,name,client')
            ->get()
            ->groupBy('project_id')
            ->map(function ($txs) {
                $project    = $txs->first()->project;
                $released   = $txs->sum('amount');
                $replenished = FundTransaction::where('type', 'replenishment')
                    ->where('project_id', $txs->first()->project_id)
                    ->sum('amount');
                $outstanding = max(0, $released - $replenished);
                return [
                    'project'     => $project,
                    'released'    => (float) $released,
                    'replenished' => (float) $replenished,
                    'outstanding' => (float) $outstanding,
                    'latest_tx'   => $txs->sortByDesc('date')->first(),
                ];
            })
            ->filter(fn($d) => $d['outstanding'] > 0)
            ->values();

        // Low balance threshold: < 20% of initial balance
        $isLowBalance = $initialBalance > 0 && ($currentBalance / $initialBalance) < 0.20;

        $transactions = FundTransaction::with('project')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $projects = Project::where('status', '!=', 'archived')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.revolving_fund', compact(
            'currentBalance', 'initialBalance',
            'totalReleased', 'totalReplenished',
            'totalDrawnThisMonth', 'pendingReplenishment',
            'activeAdvances', 'projectOutstandings',
            'isLowBalance', 'transactions', 'projects'
        ));
    }

    public function replenish(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'amount'     => 'required|numeric|min:0.01',
            'purpose'    => 'required|string|max:255',
            'date'       => 'required|date',
        ]);

        $newBalance = FundSetting::adjustBalance((float) $validated['amount']);

        FundTransaction::create([
            'type'          => 'replenishment',
            'amount'        => $validated['amount'],
            'date'          => $validated['date'],
            'project_id'    => $validated['project_id'],
            'purpose'       => $validated['purpose'],
            'description'   => $validated['purpose'],
            'status'        => 'Completed',
            'balance_after' => $newBalance,
            'recorded_by'   => auth()->user()->name ?? 'Admin',
        ]);

        $project = Project::find($validated['project_id']);
        NotificationService::revolvingFundReplenished($project, (float) $validated['amount']);

        return redirect()->route('admin.revolving_fund')
            ->with('success', 'Replenishment recorded successfully.');
    }

    public function setupInitial(Request $request)
    {
        $validated = $request->validate([
            'initial_balance' => 'required|numeric|min:0',
        ]);

        $result = FundSetting::setInitialBalance((float) $validated['initial_balance']);

        if (!$result['ok']) {
            return redirect()->route('admin.revolving_fund')
                ->with('error', $result['message']);
        }

        return redirect()->route('admin.revolving_fund')
            ->with('success', 'Initial revolving fund balance updated successfully.');
    }

    public function release(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'amount'     => 'required|numeric|min:0.01',
            'purpose'    => 'required|string|max:255',
            'remarks'    => 'nullable|string|max:1000',
        ]);

        $currentBalance = FundSetting::getCurrentBalance();

        if ($validated['amount'] > $currentBalance) {
            return redirect()->route('admin.revolving_fund')
                ->with('error', 'Insufficient Revolving Fund Balance.');
        }

        $newBalance = FundSetting::adjustBalance(-$validated['amount']);

        FundTransaction::create([
            'type'          => 'release',
            'amount'        => $validated['amount'],
            'date'          => now()->format('Y-m-d'),
            'project_id'    => $validated['project_id'],
            'purpose'       => $validated['purpose'],
            'description'   => $validated['purpose'],
            'remarks'       => $validated['remarks'] ?? null,
            'status'        => 'Pending Replenishment',
            'balance_after' => $newBalance,
            'recorded_by'   => auth()->user()->name ?? 'Admin',
        ]);

        $project = Project::find($validated['project_id']);
        NotificationService::revolvingFundReleased($project, (float) $validated['amount'], $validated['purpose']);

        return redirect()->route('admin.revolving_fund')
            ->with('success', 'Revolving fund released successfully.');
    }
}
