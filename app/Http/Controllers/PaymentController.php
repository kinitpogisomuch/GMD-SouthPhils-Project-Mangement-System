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

    /**
     * Whether the currently logged-in client session owns this payment.
     * Compares by the linked project's client_id (stable identity) so a
     * client renaming or updating their profile can never lock them out of
     * their own payment. Falls back to the old name-matching for the rare
     * payment whose project predates the client_id link.
     */
    private function paymentBelongsToSessionClient(Payment $payment): bool
    {
        if ($payment->project && $payment->project->client_id) {
            return (int) $payment->project->client_id === (int) session('user_id');
        }

        $clientEmail = session('email');
        $clientName  = $clientEmail ? Client::where('email', $clientEmail)->value('name') : null;

        return $clientName && $payment->client === $clientName;
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
        //
        // Grouped by client_id when a project is linked (so a client rename
        // or relink is reflected immediately); falls back to the raw name
        // string for the rare project that predates the client_id link.
        $groupKey = fn (Project $p) => $p->client_id ? 'id:' . $p->client_id : 'name:' . $p->client;

        $paymentsByClient = $payments->groupBy(function (Payment $p) use ($groupKey) {
            return $p->project ? $groupKey($p->project) : 'name:' . $p->client;
        });

        $nonArchivedProjects = Project::where('status', '!=', 'archived')->get();
        $projectsByClient    = $nonArchivedProjects->groupBy($groupKey);

        // Projects currently stalled waiting on a payment stage, grouped by
        // client, so the list can flag exactly who admin needs to chase.
        $activeProjectsByClient = Project::whereNotIn('status', ['completed', 'archived'])
            ->get()
            ->groupBy($groupKey);

        $clientGroups = $projectsByClient->map(function ($group, $key) use ($paymentsByClient, $activeProjectsByClient) {
            $first      = $group->first();
            $clientName = $first->live_client_name;
            $clientKey  = $first->client_id ?: $first->client;

            $paymentGroup = $paymentsByClient->get($key, collect());

            $contractTotal = $paymentGroup->sum('contract_amount');
            $receivedTotal = $paymentGroup->sum(fn($p) => $p->totalPaid());
            $statuses      = $paymentGroup->map(fn($p) => $p->computeStatus());

            $needsSettlement = $activeProjectsByClient->get($key, collect())
                ->contains(fn (Project $p) => $p->awaitingPaymentStage() !== null);

            return [
                'client'           => $clientName,
                'client_key'       => $clientKey,
                'project_count'    => $group->count(),
                'contract_total'   => $contractTotal,
                'received_total'   => $receivedTotal,
                'balance_total'    => max(0, $contractTotal - $receivedTotal),
                'has_payments'     => $paymentGroup->isNotEmpty(),
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
                    'client'       => $tx->payment->project?->live_client_name ?? $tx->payment->client ?? '—',
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
        $decoded = urldecode($client);

        if (ctype_digit($decoded)) {
            $clientId   = (int) $decoded;
            $clientName = Client::find($clientId)?->full_name ?? 'Unknown Client';

            $payments = Payment::with(['project', 'transactions'])
                ->whereHas('project', fn ($q) => $q->where('client_id', $clientId))
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $clientName = $decoded;

            $payments = Payment::with(['project', 'transactions'])
                ->where('client', $clientName)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('admin.payments_client', compact('payments', 'clientName'));
    }

    public function show($id)
    {
        $payment = Payment::with(['project', 'transactions'])->findOrFail($id);
        $payment->recalculate();

        $paidStages = $payment->paidStages();

        return view('admin.payment_detail', compact(
            'payment',
            'paidStages'
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
        $payment = Payment::with(['project', 'transactions'])->findOrFail($id);

        if (!$this->paymentBelongsToSessionClient($payment)) {
            abort(403);
        }

        $statement = $payment->billingStatements()->findOrFail($statementId);

        return view('client.billing_statement', compact('payment', 'statement'));
    }

    public function clientShow($id)
    {
        $payment = Payment::with(['project', 'transactions'])->findOrFail($id);

        if (!$this->paymentBelongsToSessionClient($payment)) {
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
        $clientId = session('user_id');

        if (!$clientId) {
            return response()->json(['count' => 0]);
        }

        $count = Payment::with('transactions', 'billingStatements', 'proofs')
            ->whereHas('project', fn ($q) => $q->where('client_id', $clientId))
            ->get()
            ->filter(fn ($p) => $p->needsClientAction())
            ->count();

        return response()->json(['count' => $count]);
    }

    public function uploadProof(Request $request, $id)
    {
        $payment = Payment::with('project')->findOrFail($id);

        if (!$this->paymentBelongsToSessionClient($payment)) {
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
