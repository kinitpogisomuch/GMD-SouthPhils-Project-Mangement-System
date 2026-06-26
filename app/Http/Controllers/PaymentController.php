<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Project;
use App\Models\Client;
use App\Models\FundTransaction;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with('project')->orderBy('created_at', 'desc')->get();

        $totalContractValue = $payments->sum('contract_amount');
        $totalReceived      = $payments->sum(fn($p) => $p->totalPaid());
        $outstanding        = max(0, $totalContractValue - $totalReceived);

        $fullyPaid  = $payments->filter(fn($p) => $p->computeStatus() === 'Fully Paid')->count();
        $inProgress = $payments->filter(fn($p) => in_array($p->computeStatus(), [
            'Down Payment Paid', 'Progress Payment Paid',
        ]))->count();
        $pendingDown = $payments->filter(fn($p) => $p->computeStatus() === 'Pending Down Payment')->count();

        // Projects that do NOT yet have a payment record
        $existingProjectIds = Payment::pluck('project_id')->filter()->all();
        $availableProjects  = Project::whereNotIn('id', $existingProjectIds)
            ->where('status', '!=', 'archived')
            ->orderBy('name')
            ->withSum('activeMaterials as bom_total', 'total_cost')
            ->withSum('activeLabor as labor_total', 'total_cost')
            ->get(['id', 'name', 'client', 'client_type', 'status', 'created_at']);

        return view('admin.payments', compact(
            'payments',
            'totalContractValue',
            'totalReceived',
            'outstanding',
            'fullyPaid',
            'inProgress',
            'pendingDown',
            'availableProjects'
        ));
    }

    public function setup(Request $request)
    {
        $request->validate([
            'project_id'       => 'required|integer|exists:projects,id',
            'contract_amount'  => 'required|numeric|min:1',
            'payment_term_type'=> 'required|in:big_project,small_project',
        ]);

        // Guard: only one payment record per project
        if (Payment::where('project_id', $request->project_id)->exists()) {
            return redirect()->route('admin.payments')
                ->with('error', 'This project already has a payment record.');
        }

        $project        = Project::findOrFail($request->project_id);
        $contractAmount = (float) $request->contract_amount;
        $termType       = $request->payment_term_type;
        $termLabel      = $termType === 'big_project'
            ? '3 Phases (50% / 30% / 20%)'
            : '2 Phases (50% / 50%)';

        Payment::create([
            'project_id'        => $project->id,
            'client'            => $project->client,
            'client_type'       => $project->client_type,
            'contract_amount'   => $contractAmount,
            'down_payment'      => round($contractAmount * 0.5, 2),
            'balance'           => $contractAmount,
            'status'            => 'Pending Down Payment',
            'payment_terms'     => $termLabel,
            'payment_term_type' => $termType,
            'date'              => now()->toDateString(),
        ]);

        return redirect()->route('admin.payments')
            ->with('success', "Payment setup created for \"{$project->name}\".");
    }

    public function show($id)
    {
        $payment = Payment::with(['project', 'transactions'])->findOrFail($id);
        $payment->recalculate();

        $stageAmounts = $payment->stageAmounts();
        $paidStages   = $payment->paidStages();

        $stageTransactions = [];
        foreach ($payment->stages() as $stage) {
            $stageTransactions[$stage] = $payment->transactions
                ->where('payment_stage', $stage)
                ->values();
        }

        return view('admin.payment_detail', compact(
            'payment',
            'stageAmounts',
            'paidStages',
            'stageTransactions'
        ));
    }

    public function recordPayment(Request $request, $id)
    {
        $payment      = Payment::findOrFail($id);
        $stageOptions = $payment->stages();
        $stageIn      = implode(',', $stageOptions);

        $validated = $request->validate([
            'payment_stage'    => "required|string|in:{$stageIn}",
            'amount_paid'      => 'required|numeric|min:0.01',
            'payment_date'     => 'required|date',
            'mode_of_payment'  => 'nullable|string|in:cheque,bank_transfer,cash',
            'reference_number' => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:1000',
        ]);

        PaymentTransaction::create([
            'payment_id'       => $payment->id,
            'payment_stage'    => $validated['payment_stage'],
            'amount_paid'      => $validated['amount_paid'],
            'payment_date'     => $validated['payment_date'],
            'mode_of_payment'  => $validated['mode_of_payment'] ?? null,
            'reference_number' => $validated['reference_number'] ?? null,
            'notes'            => $validated['notes'] ?? null,
            'recorded_by'      => auth()->user()->name ?? 'Admin',
        ]);

        $payment->recalculate();

        FundTransaction::autoReplenish(
            $payment->project,
            (float) $validated['amount_paid'],
            PaymentTransaction::stageLabel($validated['payment_stage'])
        );

        return redirect()->route('admin.payments.show', $payment->id)
            ->with('success', 'Payment recorded successfully.');
    }

    public function clientShow($id)
    {
        $clientEmail = session('email');
        $clientName  = $clientEmail
            ? Client::where('email', $clientEmail)->value('name')
            : null;

        $payment = Payment::with(['project', 'transactions'])->findOrFail($id);

        if (!$clientName || $payment->client !== $clientName) {
            abort(403);
        }

        $stageAmounts = $payment->stageAmounts();
        $paidStages   = $payment->paidStages();

        $stageTransactions = [];
        foreach ($payment->stages() as $stage) {
            $stageTransactions[$stage] = $payment->transactions
                ->where('payment_stage', $stage)
                ->values();
        }

        return view('client.payment_detail', compact(
            'payment',
            'stageAmounts',
            'paidStages',
            'stageTransactions'
        ));
    }
}
