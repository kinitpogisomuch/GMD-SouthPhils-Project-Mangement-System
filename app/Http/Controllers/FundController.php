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

        $currentBalance  = (float) $fund->current_balance;
        $initialBalance  = (float) $fund->initial_balance;
        $totalReleased   = FundTransaction::totalReleased();
        $totalReplenished = FundTransaction::totalReplenished();
        $activeAdvances  = FundTransaction::activeProjectAdvancesCount();

        $transactions = FundTransaction::with('project')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $projects = Project::where('status', '!=', 'archived')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.revolving_fund', compact(
            'currentBalance',
            'initialBalance',
            'totalReleased',
            'totalReplenished',
            'activeAdvances',
            'transactions',
            'projects'
        ));
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
