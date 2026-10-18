<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\PaymentTransaction;
use App\Models\Project;
use App\Models\Client;
use App\Models\FundTransaction;
use App\Models\BillingStatement;
use App\Services\SupabaseStorageService;
use App\Services\NotificationService;

class PaymentController extends Controller
{
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
        // Completed projects stay in: a completed project can still be awaiting delivery,
        // and its final payment gate (see Project::awaitingPaymentStage()) applies until then.
        $count = Project::where('status', '!=', 'archived')
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
            'Down Payment Paid', 'Progress Payment Partially Paid', 'Progress Payment Paid',
        ]))->count();
        $pendingDown = $payments->filter(fn($p) => $p->computeStatus() === 'Pending Down Payment')->count();

        // Same split as the Projects page. Active: one row per in-progress project — including ones with
        // no payment setup yet, so admin can spot them. Completed / Archived: grouped by client, and the
        // client's page lists those projects' payments.
        $projects        = Project::orderBy('created_at', 'desc')->get();
        $paymentByProject = $payments->filter(fn (Payment $p) => $p->project_id)->keyBy('project_id');

        $activeRows = $projects->whereNotIn('status', ['completed', 'archived'])
            ->map(function (Project $project) use ($paymentByProject) {
                $payment = $paymentByProject->get($project->id);
                $status  = $payment?->computeStatus();

                return [
                    'project'         => $project,
                    'payment'         => $payment,
                    'status'          => $status,
                    'filter'          => self::paymentFilterKey($status),
                    'contract'        => $payment ? (float) $payment->contract_amount : 0,
                    'received'        => $payment ? $payment->totalPaid() : 0,
                    'balance'         => $payment ? $payment->currentBalance() : 0,
                    'awaiting_stage'  => $project->awaitingPaymentStage(),
                    // due, but no billing statement sent yet — the client hasn't been asked to pay
                    'billing_pending' => $payment && $status !== 'Fully Paid' ? $payment->billingPendingStage() : null,
                ];
            })
            // projects held up by a payment stage first, then newest
            ->sortBy(fn ($row) => $row['awaiting_stage'] ? 0 : 1)
            ->values();

        $completedGroups = $this->paymentClientGroups($projects->where('status', 'completed'), $paymentByProject);
        $archivedGroups  = $this->paymentClientGroups($projects->where('status', 'archived'), $paymentByProject);

        $tab = in_array(request('tab'), ['active', 'completed', 'archived'], true) ? request('tab') : 'active';

        // Payments with an OR number, plus historical (imported) payments, which have none
        $receipts = PaymentTransaction::with('payment.project')
            ->where(fn ($q) => $q->where(fn ($q) => $q->whereNotNull('reference_number')->where('reference_number', '!=', ''))
                ->orWhere('notes', 'like', 'Historical record%'))   // tag set by HistoricalProjectSeeder
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
            'activeRows',
            'completedGroups',
            'archivedGroups',
            'tab',
            'totalContractValue',
            'totalReceived',
            'outstanding',
            'fullyPaid',
            'inProgress',
            'pendingDown',
            'receipts'
        ));
    }

    /** The Active tab's status filter a payment belongs to ('no_setup' when the project has none yet) */
    private static function paymentFilterKey(?string $status): string
    {
        return match (true) {
            $status === null                   => 'no_setup',
            $status === 'Fully Paid'           => 'fully_paid',
            $status === 'Pending Down Payment' => 'pending',
            default                            => 'in_progress',
        };
    }

    /**
     * Per-client payment rollup of finished (completed or archived) projects for the Payments page's
     * Completed / Archived tabs. Clients still owed a payment stage (e.g. a final payment after
     * "Proceed Anyway") come first, then the rest by name.
     */
    private function paymentClientGroups($projects, $paymentByProject)
    {
        // Grouped by client_id when a project is linked (so a client rename or relink is reflected
        // immediately); falls back to the raw name for the rare project that predates the link.
        return $projects->groupBy(fn (Project $p) => $p->client_id ? 'id:' . $p->client_id : 'name:' . $p->client)
            ->map(function ($group) use ($paymentByProject) {
                $first    = $group->first();
                $payments = $group->map(fn (Project $p) => $paymentByProject->get($p->id))->filter();
                $contract = $payments->sum('contract_amount');
                $received = $payments->sum(fn (Payment $p) => $p->totalPaid());

                return [
                    'client'           => $first->live_client_name,
                    'client_key'       => $first->client_id ?: $first->client,
                    'project_count'    => $group->count(),
                    'contract_total'   => $contract,
                    'received_total'   => $received,
                    'balance_total'    => max(0, $contract - $received),
                    'all_fully_paid'   => $payments->isNotEmpty() && $payments->every(fn (Payment $p) => $p->computeStatus() === 'Fully Paid'),
                    'needs_settlement' => $group->contains(fn (Project $p) => $p->status !== 'archived' && $p->awaitingPaymentStage() !== null),
                ];
            })
            ->sortBy(fn ($g) => ($g['needs_settlement'] ? '0_' : '1_') . strtolower($g['client']))
            ->values();
    }

    public function clientPayments($client)
    {
        $decoded = urldecode($client);

        // Active projects' payments live on the Payments page itself; a client's page holds the
        // finished (completed / archived) projects' payments
        $finished = fn ($q) => $q->whereIn('status', ['completed', 'archived']);

        if (ctype_digit($decoded)) {
            $clientId   = (int) $decoded;
            $clientName = Client::find($clientId)?->full_name ?? 'Unknown Client';

            $payments = Payment::with(['project', 'transactions'])
                ->whereHas('project', fn ($q) => $finished($q->where('client_id', $clientId)))
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $clientName = $decoded;

            $payments = Payment::with(['project', 'transactions'])
                ->where('client', $clientName)
                ->whereHas('project', $finished)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($payments->isEmpty()) {
            return redirect()->route('admin.payments');
        }

        $tab = request('tab') === 'archived' ? 'archived' : 'completed';

        return view('admin.payments_client', compact('payments', 'clientName', 'tab'));
    }

    public function show($id)
    {
        $payment = Payment::with(['project', 'transactions', 'proofs'])->findOrFail($id);
        $payment->recalculate();

        $paidStages     = $payment->paidStages();
        $proofs         = $payment->proofs;
        $onlinePayments = $payment->onlinePayments()->get();

        return view('admin.payment_detail', compact(
            'payment',
            'paidStages',
            'proofs',
            'onlinePayments'
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
            'proof_id'         => 'nullable|integer',
        ]);

        // Each OR / Serial Number can only be used once across all payments (trimmed, case-insensitive)
        $validated['reference_number'] = trim($validated['reference_number']);
        $orTaken = PaymentTransaction::whereRaw('LOWER(TRIM(reference_number)) = ?', [mb_strtolower($validated['reference_number'])])->exists();
        if ($orTaken) {
            return back()->withInput()->with('error', 'OR / Serial Number "' . $validated['reference_number'] . '" is already used on another payment. Please enter a different one.');
        }

        // Stages are settled in order, so only the one currently open can be recorded. The Down
        // Payment is a fixed amount and always settled in full; Progress and Final Payment accept
        // partial payments, never beyond what is due. Once the project reaches its final-payment
        // phase an unpaid Progress Payment is closed and its balance is due with the Final Payment.
        $stage = $validated['payment_stage'];

        if (($payment->proofStageStates(false)[$stage] ?? null) !== 'open') {
            return back()->withInput()->with('error', 'That payment stage cannot be recorded right now — it is already paid, closed, or waiting on an earlier stage.');
        }

        $isPartialStage = $stage !== 'down_payment';
        $amountsDue     = $payment->stageAmountsDue();
        $stageRemaining = $amountsDue[$stage] ?? 0;
        // A progress payment may go above the progress amount (the extra comes off the final
        // payment), but never above what is left on the whole contract.
        $stageCap       = $stage === 'progress_payment' ? $amountsDue['final_payment'] : $stageRemaining;

        if ($stageRemaining <= 0) {
            return back()->withInput()->with('error', 'That payment stage is already fully paid.');
        }

        if ($isPartialStage) {
            if ((float) $validated['amount_paid'] > $stageCap + 0.01) {
                return back()->withInput()->with('error', 'Amount exceeds the ₱' . number_format($stageCap, 2) . ' that can still be recorded for this stage.');
            }
        } else {
            $validated['amount_paid'] = $stageRemaining;
        }

        $receiptUrls = $this->storage->uploadMultiple(
            $request->file('receipt_files', []),
            'payments/' . $payment->id . '/receipts'
        );

        // Recorded under a lock on this payment: two requests arriving together (a double click, or two
        // admins) are handled one after the other, and the second sees what the first saved — so the
        // stage, amount and OR number are checked again here before anything is written.
        try {
            $transaction = \DB::transaction(function () use ($payment, $validated, $receiptUrls) {
                $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();
                $locked->load('project', 'transactions', 'proofs');
                $stage = $validated['payment_stage'];

                $orTaken = PaymentTransaction::whereRaw('LOWER(TRIM(reference_number)) = ?', [mb_strtolower($validated['reference_number'])])->exists();
                if ($orTaken) {
                    throw new \DomainException('OR / Serial Number "' . $validated['reference_number'] . '" is already used on another payment. If you just clicked Save twice, the payment was already recorded.');
                }
                if (($locked->proofStageStates(false)[$stage] ?? null) !== 'open') {
                    throw new \DomainException('That payment stage cannot be recorded right now — it is already paid, closed, or waiting on an earlier stage.');
                }
                $amountsDue = $locked->stageAmountsDue();
                $cap        = $stage === 'progress_payment' ? $amountsDue['final_payment'] : ($amountsDue[$stage] ?? 0);
                $amount     = $stage === 'down_payment' ? ($amountsDue['down_payment'] ?? 0) : (float) $validated['amount_paid'];
                if ($amount <= 0 || $amount > $cap + 0.01) {
                    throw new \DomainException('That payment stage is already fully paid, or the amount is more than what is still due.');
                }

                $transaction = PaymentTransaction::create([
                    'payment_id'       => $locked->id,
                    'payment_stage'    => $stage,
                    'amount_paid'      => $amount,
                    'payment_date'     => $validated['payment_date'],
                    'mode_of_payment'  => $validated['mode_of_payment'] ?? null,
                    'reference_number' => $validated['reference_number'] ?? null,
                    'receipt_url'      => $receiptUrls[0] ?? null,
                    'receipt_urls'     => !empty($receiptUrls) ? $receiptUrls : null,
                    'notes'            => $validated['notes'] ?? null,
                    'recorded_by'      => auth()->user()->name ?? 'Admin',
                ]);

                // The client's submission this was recorded from is now dealt with — mark it confirmed
                // regardless of whether this payment alone fully settles the stage (it may be a partial).
                if (!empty($validated['proof_id'])) {
                    $locked->proofs()->whereKey($validated['proof_id'])->update(['status' => 'confirmed']);
                }

                $locked->load('transactions');
                $locked->recalculate();

                FundTransaction::autoReplenish(
                    $locked->project,
                    (float) $amount,
                    PaymentTransaction::stageLabel($stage),
                    $validated['payment_date']
                );

                return $transaction;
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return back()->withInput()->with('error', 'OR / Serial Number "' . $validated['reference_number'] . '" is already used on another payment. If you just clicked Save twice, the payment was already recorded.');
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // Tell the client (once per payment), and if the next stage is already billed, that it is now due
        $payment->refresh();
        NotificationService::paymentRecorded($payment, $transaction);
        NotificationService::syncPaymentDue($payment);

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

        // Sending again (a double click, or "Send" pressed later) keeps the first sent date and
        // doesn't notify the client a second time.
        if ($statement->sent_at) {
            return redirect()->route('admin.payments.billing_statements.show', [$payment->id, $statement->id])
                ->with('success', 'This billing statement was already sent to the client on ' . $statement->sent_at->format('M d, Y') . '.');
        }

        $statement->sent_at = now();
        $statement->save();

        if ($payment->project) {
            // A statement that bills a new stage tells the client which phase is due and how much;
            // otherwise (e.g. a reminder for a stage already announced) the client gets the usual
            // "billing statement sent" notice. Either way, once.
            if (!NotificationService::syncPaymentDue($payment)) {
                NotificationService::billingStatementSent($payment->project, $payment->id, $statement->id);
            }
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

        // Online bank payment: what can be paid right now, and every checkout started for this payment
        $onlineEnabled  = app(\App\Services\OnlinePayments::class)->enabled();
        $payable        = $payment->onlinePayableStage();
        $onlinePayments = $payment->onlinePayments()->get();

        return view('client.payment_detail', compact(
            'payment',
            'stageAmounts',
            'paidStages',
            'stageTransactions',
            'currentStage',
            'onlineEnabled',
            'payable',
            'onlinePayments'
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

    /**
     * The amount a proof of payment is accepted at. Down and final payments are fixed amounts (the final
     * one is whatever is still owed on the contract). Only the progress payment is typed in — it may go
     * above the progress amount, which then comes off the final payment, but never above the remaining
     * balance.
     */
    private function checkedProofAmount(Payment $payment, string $stage, float $typed, string $field = 'amount_paid'): float
    {
        $amountsDue = $payment->stageAmountsDue();
        $amountDue  = $amountsDue[$stage] ?? 0;
        if ($amountDue <= 0) {
            return $typed;
        }
        if ($stage !== 'progress_payment') {
            return (float) $amountDue;
        }
        if ($typed > $amountsDue['final_payment'] + 0.01) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => 'The amount cannot be more than your remaining balance of ₱' . number_format($amountsDue['final_payment'], 2) . '.',
            ]);
        }

        return $typed;
    }

    /**
     * Admin: send a client's proof of payment back for correction, with the reason. Only a submission
     * that hasn't been recorded yet can be returned; the client then fixes and resubmits the same proof.
     */
    public function returnProof(Request $request, $id, $proofId)
    {
        $payment = Payment::with('project', 'transactions')->findOrFail($id);
        $proof   = $payment->proofs()->findOrFail($proofId);

        $validated = $request->validate([
            'correction_reason' => 'required|string|max:1000',
        ], [
            'correction_reason.required' => 'Tell the client what needs to be corrected.',
        ]);

        if ($proof->status === PaymentProof::STATUS_CONFIRMED || in_array($proof->payment_stage, $payment->paidStages(), true)) {
            return redirect()->route('admin.payments.show', $payment->id)
                ->with('error', 'This submission was already recorded, so it can no longer be returned for correction.');
        }
        // A second click on "Return" (or a refresh) doesn't return it — or notify the client — again
        if ($proof->status === PaymentProof::STATUS_NEEDS_CORRECTION) {
            return redirect()->route('admin.payments.show', $payment->id)
                ->with('success', 'This submission is already waiting for the client\'s correction.');
        }

        $proof->update([
            'status'            => PaymentProof::STATUS_NEEDS_CORRECTION,
            'correction_reason' => trim($validated['correction_reason']),
            'returned_at'       => now(),
        ]);

        NotificationService::paymentProofReturned($payment, $proof->fresh());

        return redirect()->route('admin.payments.show', $payment->id)
            ->with('success', 'Submission returned to the client for correction.');
    }

    /**
     * Client: correct and resubmit a proof of payment — the same record is updated (no new payment or
     * proof is created) and the version it replaces is kept in its revision history. Allowed until the
     * submission is recorded as a payment.
     */
    public function resubmitProof(Request $request, $id, $proofId)
    {
        $payment = Payment::with('project', 'transactions')->findOrFail($id);
        if (!$this->paymentBelongsToSessionClient($payment)) {
            abort(403);
        }
        $proof = $payment->proofs()->findOrFail($proofId);

        if (!$proof->canBeResubmitted()) {
            return redirect()->route('client.payments.show', $payment->id)
                ->with('error', 'This submission has already been recorded by GMD, so it can no longer be changed.');
        }

        if ($request->has('amount_paid')) {
            $request->merge(['amount_paid' => str_replace(',', '', (string) $request->input('amount_paid'))]);
        }

        // keeps an existing file when no new one is attached; a bank transfer needs one either way
        $needsFile = $request->input('mode_of_payment') === 'bank_transfer' && !$proof->file_url;
        $validated = $request->validateWithBag('resubmit', [
            'amount_paid'     => 'required|numeric|min:0.01',
            'mode_of_payment' => 'required|string|in:bank_transfer,cheque,cash',
            'proof_file'      => ($needsFile ? 'required' : 'nullable') . '|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes'           => 'nullable|string|max:1000',
        ], [
            'amount_paid.required'     => 'Please enter the amount you paid.',
            'amount_paid.numeric'      => 'Enter the amount as a number.',
            'mode_of_payment.required' => 'Please select how you paid.',
            'proof_file.required'      => 'Please attach an image or file as proof of your bank transfer.',
        ]);

        try {
            $amount = $this->checkedProofAmount($payment, $proof->payment_stage, (float) $validated['amount_paid']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages($e->errors())->errorBag('resubmit');
        }

        $fileUrl = $proof->file_url;
        if ($request->hasFile('proof_file')) {
            $uploaded = $this->storage->uploadMultiple([$request->file('proof_file')], 'payments/' . $payment->id . '/proofs');
            if (empty($uploaded)) {
                return back()->withInput()->with('error', 'Upload failed. Please check your connection and try again.');
            }
            $fileUrl = $uploaded[0];
        }

        // Under a lock: a double click, or GMD recording it at the same moment, is handled one at a time
        $saved = \DB::transaction(function () use ($payment, $proofId, $amount, $validated, $fileUrl) {
            Payment::whereKey($payment->id)->lockForUpdate()->first();
            $locked = PaymentProof::whereKey($proofId)->lockForUpdate()->first();
            if (!$locked || !$locked->canBeResubmitted()) {
                return null;
            }

            $revisions   = $locked->revisions ?? [];
            $revisions[] = [
                'amount'            => (float) $locked->amount,
                'mode_of_payment'   => $locked->mode_of_payment,
                'file_url'          => $locked->file_url,
                'notes'             => $locked->notes,
                'status'            => $locked->status,
                'correction_reason' => $locked->correction_reason,
                'submitted_at'      => $locked->submittedAt()?->toDateTimeString(),
                'replaced_at'       => now()->toDateTimeString(),
            ];

            $locked->update([
                'amount'            => $amount,
                'mode_of_payment'   => $validated['mode_of_payment'],
                'file_url'          => $fileUrl,
                'notes'             => $validated['notes'] ?? null,
                'status'            => PaymentProof::STATUS_PENDING,   // waits for GMD's review again
                'correction_reason' => null,
                'resubmitted_at'    => now(),
                'revisions'         => $revisions,
            ]);

            return $locked;
        });

        if (!$saved) {
            return redirect()->route('client.payments.show', $payment->id)
                ->with('error', 'This submission has already been recorded by GMD, so it can no longer be changed.');
        }

        NotificationService::paymentProofResubmitted($payment, $saved);

        return redirect()->route('client.payments.show', $payment->id)
            ->with('success', 'Corrected proof of payment submitted. Our team will review it again.');
    }

    public function uploadProof(Request $request, $id)
    {
        $payment = Payment::with('project')->findOrFail($id);

        if (!$this->paymentBelongsToSessionClient($payment)) {
            abort(403);
        }

        // Only stages that aren't already settled, and don't already have a confirmed
        // proof, are offered in the stage picker.
        // Stages are paid in order, so only the earliest open one is unlocked — and only once GMD has
        // sent its billing statement (until then the client hasn't been asked to pay it).
        $selectableStages = array_values(array_filter($payment->stagesOpenForProof(), fn ($s) => $payment->isStageBilled($s)));
        $stageIn          = $selectableStages[0] ?? '';
        if ($stageIn === '') {
            return redirect()->route('client.payments.show', $payment->id)
                ->with('error', 'There is no billed payment to submit proof for yet — you\'ll be notified when GMD sends the billing statement.');
        }

        // The amount box shows thousand separators.
        if ($request->has('amount_paid')) {
            $request->merge(['amount_paid' => str_replace(',', '', (string) $request->input('amount_paid'))]);
        }

        $validated = $request->validate([
            'payment_stage'   => "required|string|in:{$stageIn}",
            'amount_paid'     => 'required|numeric|min:0.01',
            'mode_of_payment' => 'required|string|in:bank_transfer,cheque,cash',
            // A screenshot is the proof for a bank/online transfer; cash and cheque often
            // have none (a physical handover), so the file is optional for those two.
            'proof_file'      => 'required_if:mode_of_payment,bank_transfer|nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes'           => 'nullable|string|max:1000',
        ], [
            'payment_stage.required'    => 'Please select which payment stage this is for.',
            'payment_stage.in'         => 'That payment stage is not available yet — it is locked until the earlier stage is paid, or it is already fully paid.',
            'amount_paid.required'     => 'Please enter the amount you paid.',
            'amount_paid.numeric'      => 'Enter the amount as a number.',
            'mode_of_payment.required' => 'Please select how you paid.',
            'proof_file.required_if'   => 'Please attach an image or file as proof of payment.',
        ]);

        $validated['amount_paid'] = $this->checkedProofAmount($payment, $validated['payment_stage'], (float) $validated['amount_paid']);

        $fileUrl = null;
        if ($request->hasFile('proof_file')) {
            $fileUrls = $this->storage->uploadMultiple(
                [$request->file('proof_file')],
                'payments/' . $payment->id . '/proofs'
            );

            if (empty($fileUrls)) {
                return back()->withInput()->with('error', 'Upload failed. Please check your connection and try again.');
            }

            $fileUrl = $fileUrls[0];
        }

        // Saved under a lock on this payment so simultaneous submissions are handled one at a time, and
        // the stage is checked again in case the first one (or an admin) just settled it.
        $proof = \DB::transaction(function () use ($payment, $validated, $fileUrl) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();
            $locked->load('project', 'transactions', 'proofs');
            if (!in_array($validated['payment_stage'], $locked->stagesOpenForProof(), true)) {
                return null;
            }

            $proof = $locked->proofs()->make([
                'payment_stage'   => $validated['payment_stage'],
                'amount'          => $validated['amount_paid'],
                'mode_of_payment' => $validated['mode_of_payment'],
                'file_url'        => $fileUrl,
                'notes'           => $validated['notes'] ?? null,
            ]);
            $locked->proofs()->save($proof);

            return $proof;
        });

        if (!$proof) {
            return redirect()->route('client.payments.show', $payment->id)
                ->with('error', 'That payment stage is no longer open for proof of payment — it may have just been settled.');
        }

        NotificationService::paymentProofSubmitted($payment, $proof);

        return redirect()->route('client.payments.show', $payment->id)
            ->with('success', 'Proof of payment submitted. Our team will verify it shortly.');
    }
}
