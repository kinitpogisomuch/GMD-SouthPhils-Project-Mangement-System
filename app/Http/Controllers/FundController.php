<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FundTransaction;
use App\Models\FundSetting;

class FundController extends Controller
{
    public function index()
    {
        $fund = FundSetting::instance();

        // Total Balance always comes from the records: initial + additional capital − released + repaid
        $currentBalance   = FundSetting::syncBalance();
        $initialBalance   = (float) $fund->initial_balance;
        $isInitialized    = $initialBalance > 0;
        $totalTopUps      = FundSetting::totalTopUps();
        $topUpCount       = FundTransaction::where('type', 'topup')->count();
        $lastTopUp        = FundTransaction::where('type', 'topup')->latest('id')->first();
        $totalCapital     = $initialBalance + $totalTopUps;   // all money ever put into the fund
        // The initial balance can only be corrected before the fund has any history
        $canEditInitial   = !FundTransaction::exists();
        $initialSetAt     = $fund->updated_at;
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
        // Low balance = less than 20% of all the money put into the fund (initial + additional capital)
        $isLowBalance = $totalCapital > 0 && ($currentBalance / $totalCapital) < 0.20;

        $transactions = FundTransaction::with('project')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return view('admin.revolving_fund', compact(
            'currentBalance', 'initialBalance',
            'totalReleased', 'totalReplenished',
            'totalDrawnThisMonth', 'pendingReplenishment',
            'activeAdvances', 'projectOutstandings',
            'isLowBalance', 'transactions',
            'isInitialized', 'totalTopUps', 'topUpCount', 'lastTopUp', 'totalCapital', 'canEditInitial', 'initialSetAt'
        ));
    }

    /** POST — invest additional capital in the fund after the initial setup; recorded in the fund history. */
    public function topUp(Request $request)
    {
        if ($request->has('amount')) {
            $request->merge(['amount' => str_replace(',', '', (string) $request->input('amount'))]);
        }
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:999999999',
            'note'   => 'nullable|string|max:255',
        ], [
            'amount.required' => 'Enter the capital amount.',
            'amount.numeric'  => 'Enter the amount as a number.',
            'amount.min'      => 'The capital amount must be more than zero.',
            'amount.max'      => 'That amount is too large.',
        ]);

        try {
            $entry = FundSetting::topUp((float) $validated['amount'], trim((string) ($validated['note'] ?? '')) ?: null, auth()->user()->name ?? 'Admin');
        } catch (\DomainException $e) {
            return redirect()->route('admin.revolving_fund')->with('error', $e->getMessage());
        }

        // same money format as the page: no .00 on whole amounts
        $peso = fn ($n) => '₱' . preg_replace('/\.00$/', '', number_format((float) $n, 2));

        return redirect()->route('admin.revolving_fund')
            ->with('success', $peso($entry->amount) . ' additional capital added to the revolving fund. New balance: ' . $peso($entry->balance_after) . '.');
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
}
