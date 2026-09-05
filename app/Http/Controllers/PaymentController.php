<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Project;
use App\Models\Client;
use App\Models\FundTransaction;
use App\Models\BillingStatement;
use App\Services\SupabaseStorageService;

class PaymentController extends Controller
{
    protected $storage;

    public function __construct(SupabaseStorageService $storage)
    {
        $this->storage = $storage;
    }

    public function index()
    {
        $payments = Payment::with(['project', 'transactions'])->orderBy('created_at', 'desc')->get();

        $totalContractValue = $payments->sum('contract_amount');
        $totalReceived      = $payments->sum(fn($p) => $p->totalPaid());
        $outstanding        = max(0, $totalContractValue - $totalReceived);

        $fullyPaid  = $payments->filter(fn($p) => $p->computeStatus() === 'Fully Paid')->count();
        $inProgress = $payments->filter(fn($p) => in_array($p->computeStatus(), [
            'Down Payment Paid', 'Progress Payment Paid',
        ]))->count();
        $pendingDown = $payments->filter(fn($p) => $p->computeStatus() === 'Pending Down Payment')->count();

        // Every client with at least one active project shows up here — not
        // just clients who already have a payment record — so admin can spot
        // who still needs a payment setup done, right from this list.
        $paymentsByClient = $payments->groupBy('client');

        $projectCountsByClient = Project::where('status', '!=', 'archived')
            ->selectRaw('client, count(*) as cnt')
            ->groupBy('client')
            ->pluck('cnt', 'client');

        $clientGroups = $projectCountsByClient->keys()->map(function ($clientName) use ($paymentsByClient, $projectCountsByClient) {
            $group = $paymentsByClient->get($clientName, collect());

            $contractTotal = $group->sum('contract_amount');
            $receivedTotal = $group->sum(fn($p) => $p->totalPaid());
            $statuses      = $group->map(fn($p) => $p->computeStatus());

            return [
                'client'          => $clientName,
                'project_count'   => $projectCountsByClient[$clientName],
                'contract_total'  => $contractTotal,
                'received_total'  => $receivedTotal,
                'balance_total'   => max(0, $contractTotal - $receivedTotal),
                'has_payments'    => $group->isNotEmpty(),
                'has_pending'     => $statuses->contains('Pending Down Payment'),
                'has_in_progress' => $statuses->contains(fn($s) => in_array($s, ['Down Payment Paid', 'Progress Payment Paid'])),
                'all_fully_paid'  => $statuses->isNotEmpty() && $statuses->every(fn($s) => $s === 'Fully Paid'),
            ];
        })->sortBy('client')->values();

        return view('admin.payments', compact(
            'clientGroups',
            'totalContractValue',
            'totalReceived',
            'outstanding',
            'fullyPaid',
            'inProgress',
            'pendingDown'
        ));
    }

    public function clientPayments($client)
    {
        $clientName = urldecode($client);

        $payments = Payment::with(['project', 'transactions'])
            ->where('client', $clientName)
            ->orderBy('created_at', 'desc')
            ->get();

        // This client's projects that do NOT yet have a payment record
        $existingProjectIds = Payment::pluck('project_id')->filter()->all();
        $availableProjects  = Project::whereNotIn('id', $existingProjectIds)
            ->where('client', $clientName)
            ->where('status', '!=', 'archived')
            ->orderBy('name')
            ->withSum('activeMaterials as bom_total', 'total_cost')
            ->withSum('activeLabor as labor_total', 'total_cost')
            ->get(['id', 'name', 'client', 'client_type', 'status', 'created_at']);

        return view('admin.payments_client', compact('payments', 'clientName', 'availableProjects'));
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
            'payment_date'     => 'required|date|after_or_equal:today',
            'mode_of_payment'  => 'nullable|string|in:cheque,bank_transfer,cash',
            'reference_number' => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:1000',
            'receipt_file'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $paidStages  = $payment->paidStages();
        $stageIndex  = array_search($validated['payment_stage'], $stageOptions);
        $priorStages = array_slice($stageOptions, 0, $stageIndex);

        if (in_array($validated['payment_stage'], $paidStages)) {
            return back()->withErrors(['payment_stage' => 'This stage has already been paid.']);
        }
        if (array_diff($priorStages, $paidStages)) {
            return back()->withErrors(['payment_stage' => 'Earlier payment stages must be recorded first.']);
        }

        $receiptUrl = null;
        if ($request->hasFile('receipt_file')) {
            $receiptUrl = $this->storage->upload(
                $request->file('receipt_file'),
                'payments/' . $payment->id . '/receipts'
            );
        }

        PaymentTransaction::create([
            'payment_id'       => $payment->id,
            'payment_stage'    => $validated['payment_stage'],
            'amount_paid'      => $validated['amount_paid'],
            'payment_date'     => $validated['payment_date'],
            'mode_of_payment'  => $validated['mode_of_payment'] ?? null,
            'reference_number' => $validated['reference_number'] ?? null,
            'receipt_url'      => $receiptUrl,
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

    public function storeBillingStatement(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        $validated = $request->validate([
            'attention'            => 'nullable|string|max:255',
            'bill_to'              => 'nullable|string|max:255',
            'statement_date'       => 'required|date',
            'reference_no'         => 'nullable|string|max:100',
            'tin_number'           => 'nullable|string|max:100',
            'project_title'        => 'nullable|string|max:255',
            'project_location'     => 'nullable|string|max:500',
            'po_number'            => 'nullable|string|max:100',
            'pr_number'            => 'nullable|string|max:100',
            'subject'              => 'nullable|string|max:255',
            'deposit_instructions' => 'nullable|string|max:1000',
            'prepared_by_name'     => 'nullable|string|max:255',
            'prepared_by_role'     => 'nullable|string|max:255',
            'approved_by_name'     => 'nullable|string|max:255',
            'approved_by_role'     => 'nullable|string|max:255',
        ]);

        $statement = $payment->billingStatements()->create($validated);

        return redirect()->route('admin.payments.billing_statements.show', [$payment->id, $statement->id])
            ->with('success', 'Billing statement generated.');
    }

    public function showBillingStatement($id, $statementId)
    {
        $payment   = Payment::with(['project', 'transactions'])->findOrFail($id);
        $statement = $payment->billingStatements()->findOrFail($statementId);

        return view('admin.billing_statement', compact('payment', 'statement'));
    }

    public function clientShowBillingStatement($id, $statementId)
    {
        $clientEmail = session('email');
        $clientName  = $clientEmail
            ? Client::where('email', $clientEmail)->value('name')
            : null;

        $payment = Payment::with(['project', 'transactions'])->findOrFail($id);

        if (!$clientName || $payment->client !== $clientName) {
            abort(403);
        }

        $statement = $payment->billingStatements()->findOrFail($statementId);

        return view('client.billing_statement', compact('payment', 'statement'));
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

    public function uploadProof(Request $request, $id)
    {
        $clientEmail = session('email');
        $clientName  = $clientEmail
            ? Client::where('email', $clientEmail)->value('name')
            : null;

        $payment = Payment::findOrFail($id);

        if (!$clientName || $payment->client !== $clientName) {
            abort(403);
        }

        $stageIn = implode(',', $payment->stages());

        $validated = $request->validate([
            'payment_stage' => "required|string|in:{$stageIn}",
            'proof_file'    => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $fileUrl = $this->storage->upload(
            $request->file('proof_file'),
            'payments/' . $payment->id . '/proofs'
        );

        if (!$fileUrl) {
            return back()->with('error', 'Upload failed. Please check your connection and try again.');
        }

        $payment->proofs()->create([
            'payment_stage' => $validated['payment_stage'],
            'file_url'      => $fileUrl,
            'notes'         => $validated['notes'] ?? null,
        ]);

        return redirect()->route('client.payments.show', $payment->id)
            ->with('success', 'Proof of payment submitted. Our team will verify it shortly.');
    }
}
