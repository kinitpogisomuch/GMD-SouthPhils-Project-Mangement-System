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

        // Pending replenishment = what each project still owes the fund, added up per project
        // so one project's repayments can never hide another project's outstanding amount.
        $pendingReplenishment = (float) $projectOutstandings->sum('outstanding');

        // Low balance threshold: < 20% of initial balance
        $isLowBalance = $initialBalance > 0 && ($currentBalance / $initialBalance) < 0.20;

        $transactions = FundTransaction::with('project')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $projects = Project::whereNotIn('status', ['completed', 'archived'])
            ->orderBy('client')
            ->orderBy('name')
            ->get(['id', 'name', 'client', 'capacity', 'current_phase', 'status']);

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

        // A project can only pay back what was actually released to it.
        $outstanding = FundTransaction::outstandingForProject((int) $validated['project_id']);
        if ((float) $validated['amount'] > $outstanding + 0.005) {
            return redirect()->route('admin.revolving_fund')
                ->with('error', $outstanding > 0
                    ? 'That is more than the ₱' . number_format($outstanding, 2) . ' this project still owes the fund.'
                    : 'This project has no outstanding fund balance to replenish.');
        }

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

        FundTransaction::syncReleaseStatuses((int) $validated['project_id']);

        $project = Project::find($validated['project_id']);
        NotificationService::revolvingFundReplenished($project, (float) $validated['amount']);

        return redirect()->route('admin.revolving_fund')
            ->with('success', 'Replenishment recorded successfully.');
    }

    public function setupInitial(Request $request)
    {
        $validated = $request->validate([
            'initial_balance' => 'required|numeric|min:0.01',
        ], [
            'initial_balance.required' => 'Please enter the starting balance.',
            'initial_balance.min'      => 'The starting balance must be more than zero.',
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
            'date'       => 'required|date',
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
            'date'          => $validated['date'],
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
