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
use App\Services\NotificationService;
use App\Http\Controllers\Concerns\BackdatesRecords;

class PaymentController extends Controller
{
    use BackdatesRecords;

    protected $storage;

    public function __construct(SupabaseStorageService $storage)
    {
        $this->storage = $storage;
    }

    /** GET /admin/payments/pending-count — powers the sidebar nav badge */
    public function pendingSettlementCount()
    {
        $count = Project::whereNotIn('status', ['completed', 'archived'])
            ->get()
            ->filter(fn (Project $p) => $p->awaitingPaymentStage() !== null)
            ->count();

        return response()->json(['count' => $count]);
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

        // Projects currently stalled waiting on a payment stage, grouped by
        // client, so the list can flag exactly who admin needs to chase.
        $activeProjectsByClient = Project::whereNotIn('status', ['completed', 'archived'])
            ->get()
            ->groupBy('client');

        $clientGroups = $projectCountsByClient->keys()->map(function ($clientName) use ($paymentsByClient, $projectCountsByClient, $activeProjectsByClient) {
            $group = $paymentsByClient->get($clientName, collect());

            $contractTotal = $group->sum('contract_amount');
            $receivedTotal = $group->sum(fn($p) => $p->totalPaid());
            $statuses      = $group->map(fn($p) => $p->computeStatus());

            $needsSettlement = $activeProjectsByClient->get($clientName, collect())
                ->contains(fn (Project $p) => $p->awaitingPaymentStage() !== null);

            return [
                'client'           => $clientName,
                'project_count'    => $projectCountsByClient[$clientName],
                'contract_total'   => $contractTotal,
                'received_total'   => $receivedTotal,
                'balance_total'    => max(0, $contractTotal - $receivedTotal),
                'has_payments'     => $group->isNotEmpty(),
                'has_pending'      => $statuses->contains('Pending Down Payment'),
                'has_in_progress'  => $statuses->contains(fn($s) => in_array($s, ['Down Payment Paid', 'Progress Payment Paid'])),
                'all_fully_paid'   => $statuses->isNotEmpty() && $statuses->every(fn($s) => $s === 'Fully Paid'),
                'needs_settlement' => $needsSettlement,
            ];
        })->sortBy(fn ($group) => ($group['needs_settlement'] ? '0_' : '1_') . strtolower($group['client']))->values();

        $receipts = PaymentTransaction::with('payment.project')
            ->whereNotNull('reference_number')
            ->where('reference_number', '!=', '')
            ->orderByDesc('payment_date')
            ->get()
            ->map(function (PaymentTransaction $tx) {
                $receiptUrls = !empty($tx->receipt_urls) ? $tx->receipt_urls : array_filter([$tx->receipt_url]);

                return [
                    'or_number'    => $tx->reference_number,
                    'client'       => $tx->payment->client ?? '—',
                    'project'      => $tx->payment->project->name ?? '—',
                    'stage'        => PaymentTransaction::stageLabel($tx->payment_stage),
                    'amount'       => (float) $tx->amount_paid,
                    'date_issued'  => $tx->payment_date,
                    'receipt_urls' => array_values($receiptUrls),
                ];
            })
            ->values();

        return view('admin.payments', compact(
            'clientGroups',
            'totalContractValue',
            'totalReceived',
            'outstanding',
            'fullyPaid',
            'inProgress',
            'pendingDown',
            'receipts'
        ));
    }

    public function clientPayments($client)
    {
        $clientName = urldecode($client);

        $payments = Payment::with(['project', 'transactions'])
            ->where('client', $clientName)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.payments_client', compact('payments', 'clientName'));
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
            'reference_number' => 'required|string|max:100',
            'notes'            => 'nullable|string|max:1000',
            'receipt_files'    => 'required|array|min:1|max:5',
            'receipt_files.*'  => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
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

        $remaining = $payment->stageRemaining($validated['payment_stage']);
        if ($validated['amount_paid'] > $remaining) {
            return back()
                ->withErrors(['amount_paid' => 'Amount exceeds the remaining balance for this stage (₱' . number_format($remaining, 2) . ').'])
                ->withInput();
        }

        $receiptUrls = $this->storage->uploadMultiple(
            $request->file('receipt_files', []),
            'payments/' . $payment->id . '/receipts'
        );

        PaymentTransaction::create([
            'payment_id'       => $payment->id,
            'payment_stage'    => $validated['payment_stage'],
            'amount_paid'      => $validated['amount_paid'],
            'payment_date'     => $validated['payment_date'],
            'mode_of_payment'  => $validated['mode_of_payment'] ?? null,
            'reference_number' => $validated['reference_number'] ?? null,
            'receipt_url'      => $receiptUrls[0] ?? null,
            'receipt_urls'     => !empty($receiptUrls) ? $receiptUrls : null,
            'notes'            => $validated['notes'] ?? null,
            'recorded_by'      => auth()->user()->name ?? 'Admin',
        ]);

        $payment->recalculate();

        FundTransaction::autoReplenish(
            $payment->project,
            (float) $validated['amount_paid'],
            PaymentTransaction::stageLabel($validated['payment_stage']),
            $validated['payment_date']
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
            'billing_stage'        => 'nullable|string|in:' . implode(',', array_diff($payment->stages(), $payment->paidStages())),
            'deposit_instructions' => 'nullable|string|max:1000',
            'prepared_by_name'     => 'nullable|string|max:255',
            'prepared_by_role'     => 'nullable|string|max:255',
            'approved_by_name'     => 'nullable|string|max:255',
            'approved_by_role'     => 'nullable|string|max:255',
        ]);

        $statement = $payment->billingStatements()->create($validated);

        return redirect()->route('admin.payments.billing_statements.show', [$payment->id, $statement->id]);
    }

    public function showBillingStatement($id, $statementId)
    {
        $payment   = Payment::with(['project', 'transactions'])->findOrFail($id);
        $statement = $payment->billingStatements()->findOrFail($statementId);

        return view('admin.billing_statement', compact('payment', 'statement'));
    }

    public function sendBillingStatement($id, $statementId)
    {
        $payment   = Payment::with('project')->findOrFail($id);
        $statement = $payment->billingStatements()->findOrFail($statementId);

        $statement->sent_at = now();
        $statement->save();

        if ($payment->project) {
            NotificationService::billingStatementSent($payment->project, $payment->id, $statement->id);
        }

        return redirect()->route('admin.payments.billing_statements.show', [$payment->id, $statement->id])
            ->with('success', 'Billing statement sent to the client.');
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

        // The stage currently shown to the client — the earliest unpaid stage, but
        // only once GMD has actually billed it. Null if nothing's billed yet.
        $currentStage = $payment->currentBilledStage();

        return view('client.payment_detail', compact(
            'payment',
            'stageAmounts',
            'paidStages',
            'stageTransactions',
            'currentStage'
        ));
    }

    /**
     * Unread-badge count for the client header's Payments nav link — how many of
     * this client's projects currently have a stage billed and awaiting their
     * proof-of-payment upload. Reflects real actionable state (not notification
     * read/unread), so it stays accurate even if the client dismissed the alert
     * without actually uploading anything yet.
     */
    public function pendingProofCount()
    {
        $clientEmail = session('email');
        $clientName  = $clientEmail
            ? Client::where('email', $clientEmail)->value('name')
            : null;

        if (!$clientName) {
            return response()->json(['count' => 0]);
        }

        $count = Payment::with('transactions', 'billingStatements', 'proofs')
            ->where('client', $clientName)
            ->get()
            ->filter(fn ($p) => $p->needsClientAction())
            ->count();

        return response()->json(['count' => $count]);
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
            'payment_stage'   => "required|string|in:{$stageIn}",
            'proof_files'     => 'required|array|min:1|max:5',
            'proof_files.*'   => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes'           => 'nullable|string|max:1000',
            'submitted_date'  => 'nullable|date',
        ]);

        $fileUrls = $this->storage->uploadMultiple(
            $request->file('proof_files', []),
            'payments/' . $payment->id . '/proofs'
        );

        if (empty($fileUrls)) {
            return back()->with('error', 'Upload failed. Please check your connection and try again.');
        }

        foreach ($fileUrls as $fileUrl) {
            $proof = $payment->proofs()->make([
                'payment_stage' => $validated['payment_stage'],
                'file_url'      => $fileUrl,
                'notes'         => $validated['notes'] ?? null,
            ]);
            $this->applyBackdate($proof, $validated['submitted_date'] ?? null);
            $payment->proofs()->save($proof);
        }

        return redirect()->route('client.payments.show', $payment->id)
            ->with('success', 'Proof of payment submitted. Our team will verify it shortly.');
    }
}
