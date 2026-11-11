<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Detail | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}?v={{ @filemtime(public_path('css/client.css')) }}" rel="stylesheet">
</head>
<body class="page-enter pg-client-payment-detail">

    @include('partials.client.header')

    <main class="admin-content">

            @php
                $status    = $payment->computeStatus();
                $totalPaid = $payment->totalPaid();
                $balance   = $payment->currentBalance();
                $pct       = $payment->contract_amount > 0
                    ? round(($totalPaid / $payment->contract_amount) * 100, 1)
                    : 0;
            @endphp

            <!-- Breadcrumb -->
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('client.payments') }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    Payments
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="color:var(--dark);font-weight:700;">{{ $payment->project->name ?? 'Payment Detail' }}</span>
            </div>

            @php
                // the quotation this project came from, and when the project started — for the reference line
                $hdBatch  = $payment->project_id ? \App\Models\QuotationBatch::where('project_id', $payment->project_id)->latest('id')->first() : null;
                $hdQuote  = $hdBatch ? ($hdBatch->versions()->value('quotation_number') ?: $hdBatch->reference_no) : null;
                $hdStart  = $payment->project?->start_date;
                // where the client stands, in one sentence: what's paid, and what (if anything) they need to do next
                $hdPaid   = collect($paidStages ?? [])->map(fn ($s) => \App\Models\PaymentTransaction::stageLabel($s))->implode(', ');
                $hdNext   = $payment->openStage();
                if ($balance <= 0) {
                    $hdLine = ['tone' => 'is-good', 'icon' => 'party-popper', 'html' => 'All payments complete — thank you!'];
                } elseif (!empty($payable)) {
                    $hdLine = ['tone' => 'is-due', 'icon' => 'bell-ring', 'html' => ($hdPaid ? e($hdPaid) . ' paid · ' : '')
                        . '<strong>' . e(\App\Models\PaymentTransaction::stageLabel($payable['stage'])) . ' of ₱' . number_format($payable['amount'], 2) . ' is due now</strong>'];
                } else {
                    $hdLine = ['tone' => '', 'icon' => 'clock', 'html' => ($hdPaid ? e($hdPaid) . ' paid · ' : '')
                        . ($hdNext ? 'the ' . e(\App\Models\PaymentTransaction::stageLabel($hdNext)) . ' opens when GMD sends you the billing statement' : 'nothing is due right now')];
                }
            @endphp
            <div class="page-header" style="margin-bottom:24px;align-items:flex-start;">
                <div>
                    <h1 class="page-title" style="margin:0;">{{ $payment->project->name ?? 'Payment Detail' }}</h1>
                    @if($hdQuote || $hdStart)
                    <div class="pd-ref">
                        @if($hdQuote)<span class="pd-ref-code">{{ $hdQuote }}</span>@endif
                        @if($hdStart)<span><i data-lucide="calendar"></i> Started {{ \Carbon\Carbon::parse($hdStart)->format('M d, Y') }}</span>@endif
                    </div>
                    @endif
                    <div class="pd-standing {{ $hdLine['tone'] }}"><i data-lucide="{{ $hdLine['icon'] }}"></i><span>{!! $hdLine['html'] !!}</span></div>
                </div>
                <span class="status-badge {{ \App\Models\Payment::statusBadgeClass($status) }}" style="font-size:13px;padding:8px 16px;">
                    {{ $status }}
                </span>
            </div>

            <!-- Payment Overview — the same dark panel as the Payments pages -->
            <div class="fd-overview" style="margin:4px 0 24px;">
                <div class="fd-overview-title">
                    <i data-lucide="bar-chart-2"></i>
                    Payment Overview
                </div>
                <div class="fd-overview-grid">
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Contract Amount</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">{{ $payment->payment_terms }}</span>
                        <span class="fd-ov-val">₱{{ number_format($payment->contract_amount, 2) }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Total Paid</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Amount paid</span>
                        <span class="fd-ov-val" style="color:#4ade80;">₱{{ number_format($totalPaid, 2) }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Remaining Balance</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Still outstanding</span>
                        <span class="fd-ov-val" style="color:{{ $balance > 0 ? '#facc15' : 'rgba(255,255,255,0.35)' }};">
                            {{ $balance > 0 ? '₱' . number_format($balance, 2) : '—' }}
                        </span>
                    </div>
                    <div class="fd-ov-item fd-ov-highlight">
                        <span class="fd-ov-label">Paid of Contract</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Payment progress</span>
                        <span class="fd-ov-val">{{ $pct }}%</span>
                    </div>
                </div>
            </div>

            @if(session('error'))
            <div class="alert-banner error" role="alert" style="margin-bottom:16px;">
                <i data-lucide="alert-circle"></i>
                <span>{{ session('error') }}</span>
            </div>
            @endif

            <!-- Pay Online (payment gateway) -->
            @php
                $pendingOnline = $onlinePayments->firstWhere('status', \App\Models\OnlinePayment::STATUS_PENDING);
                // Records list only real payments — cancelled / expired / declined checkouts moved no money
                $onlinePayments = $onlinePayments->where('status', \App\Models\OnlinePayment::STATUS_PAID)->values();
                // The stage payable now: GMD has sent its billing statement
                $payStage      = $payable['stage'] ?? null;
                $proofInReview = $payStage && $payment->proofs
                    ->where('payment_stage', $payStage)
                    ->where('status', \App\Models\PaymentProof::STATUS_PENDING)
                    ->isNotEmpty();
                $canPayOnline  = $onlineEnabled && $payable && !$proofInReview
                    && $payable['amount'] >= \App\Services\OnlinePayments::MIN_AMOUNT;
                // Once a stage is billed the client may also settle the whole remaining contract in one go —
                // offered only when that is more than the stage due now
                $fullBalance   = round($payment->currentBalance(), 2);
                $canPayFull    = $onlineEnabled && $payable && !$proofInReview
                    && $fullBalance > $payable['amount'] + 0.01;
                $poMethods     = collect(config('services.paymongo.payment_methods'))
                    ->map(fn ($m) => \App\Models\OnlinePayment::METHOD_LABELS[$m] ?? strtoupper($m));
                // proofs sent back for correction before uploads were retired — they can still be resubmitted
                $needsFixing   = $payment->proofs->filter(fn ($p) => $p->needsCorrection())->count();
            @endphp
            {{-- ===== Make a Payment: online through PayMongo. Cash and cheque are handed to GMD, who records them. ===== --}}
            {{-- a billed amount is due (and no proof is already being reviewed): highlighted in yellow until it's paid --}}
            @php $paymentNeedsAction = $payable && !$proofInReview; @endphp
            <div class="card po-card{{ $paymentNeedsAction ? ' po-needs-action' : '' }}">
                @if($paymentNeedsAction)
                <span class="po-action-flag"><span class="po-action-dot"></span> Payment due</span>
                @endif
                {{-- white header, same as the Payment Records card below --}}
                <div class="pd-rec-head">
                    <div class="pd-rec-titles">
                        <div class="pd-rec-title">Make a Payment</div>
                        <div class="pd-rec-sub">{{ $payable ? 'Pay the amount GMD billed you — online, or by cash or cheque handed to GMD' : 'Nothing to pay right now' }}</div>
                    </div>
                </div>
                <div class="card-body po-body">

                    {{-- What to pay now: the billed stage, and (when it's more) the whole remaining balance — side by side --}}
                    @if($payable)
                    <div class="po-options{{ $canPayFull ? ' has-two' : '' }}">
                        <div class="po-opt is-due">
                            <div class="po-opt-top">
                                <span class="po-opt-icon"><i data-lucide="file-check-2"></i></span>
                                <div class="po-opt-head">
                                    <div class="po-opt-kicker">Amount due now</div>
                                    <div class="po-opt-name">{{ \App\Models\PaymentTransaction::stageLabel($payStage) }}</div>
                                </div>
                                <span class="po-opt-tag">Billed by GMD</span>
                            </div>
                            <div class="po-opt-amount">₱{{ number_format($payable['amount'], 2) }}</div>
                            <ul class="po-opt-facts">
                                <li><span>Balance after this</span><strong>₱{{ number_format(max(0, $fullBalance - $payable['amount']), 2) }}</strong></li>
                            </ul>
                            @if($canPayOnline)
                            <form method="POST" action="{{ route('client.payments.online.pay', $payment->id) }}" class="po-opt-form">
                                @csrf
                                <button type="submit" class="save-btn po-opt-btn">
                                    <i data-lucide="landmark"></i>
                                    {{ $pendingOnline && $pendingOnline->payment_stage === $payStage ? 'Continue Payment' : 'Pay ₱' . number_format($payable['amount'], 2) }}
                                </button>
                            </form>
                            @endif
                        </div>

                        @if($canPayFull)
                        <div class="po-opt is-full">
                            <div class="po-opt-top">
                                <span class="po-opt-icon"><i data-lucide="badge-check"></i></span>
                                <div class="po-opt-head">
                                    <div class="po-opt-kicker">Or pay in full</div>
                                    <div class="po-opt-name">Full Payment</div>
                                </div>
                                <span class="po-opt-tag is-good">Settles everything</span>
                            </div>
                            <div class="po-opt-amount">₱{{ number_format($fullBalance, 2) }}</div>
                            <ul class="po-opt-facts">
                                <li><span>Covers</span><strong>Every remaining stage</strong></li>
                                <li><span>Balance after this</span><strong class="is-zero">₱0.00</strong></li>
                            </ul>
                            <form method="POST" action="{{ route('client.payments.online.pay', $payment->id) }}" class="po-opt-form">
                                @csrf
                                <input type="hidden" name="full" value="1">
                                <button type="submit" class="cancel-btn po-opt-btn">
                                    <i data-lucide="landmark"></i>
                                    {{ $pendingOnline && $pendingOnline->payment_stage === 'full_payment' ? 'Continue Payment' : 'Pay in Full' }}
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                    @if($canPayOnline)
                    {{-- one line for both options: who handles the payment and how it can be paid --}}
                    <div class="po-trust">
                        <span class="po-secure"><i data-lucide="shield-check"></i> Secured by PayMongo</span>
                        <span class="po-trust-methods">
                            @foreach($poMethods as $label)<span class="po-method">{{ $label }}</span>@endforeach
                        </span>
                    </div>
                    @endif

                    @if($proofInReview)
                    <div class="alert-banner info" style="margin:0;">
                        <i data-lucide="hourglass"></i>
                        <span>Your proof of payment for the {{ \App\Models\PaymentTransaction::stageLabel($payStage) }} is being reviewed by GMD — no need to pay it again.</span>
                    </div>
                    @elseif(!$onlineEnabled)
                    <div class="alert-banner info" style="margin:0;">
                        <i data-lucide="info"></i>
                        <span>Online payment isn't available right now. Please pay GMD by cash or cheque — GMD records it and it appears in your Payment History.</span>
                    </div>
                    @endif
                    @else
                    <div class="po-done">
                        <i data-lucide="{{ $payment->currentBalance() <= 0 ? 'party-popper' : 'check-circle-2' }}"></i>
                        <span>{{ $payment->currentBalance() <= 0 ? 'This project is fully paid. Thank you!' : 'Nothing is due right now. The next payment opens when your project reaches its payment stage, or when GMD sends you a billing statement asking you to pay.' }}</span>
                    </div>
                    @endif

                    @if($onlineEnabled)
                    <details class="po-how">
                        <summary><i data-lucide="help-circle"></i> How online payment works <i data-lucide="chevron-down" class="po-how-caret"></i></summary>
                        <div class="po-how-body">
                            <ol class="po-steps">
                                <li>Press <strong>Pay</strong> for the amount due (or <strong>Pay in Full</strong>) — you'll go to PayMongo's secure payment page.</li>
                                <li>Choose how to pay: <strong>{{ $poMethods->join(', ', ' or ') }}</strong>.</li>
                                <li>Your payment is confirmed directly with the bank and recorded here automatically — no proof upload needed.</li>
                            </ol>
                            <p class="po-note">GMD never sees or stores your online banking login, PIN or OTP — you enter them only on your bank's or PayMongo's page.</p>
                        </div>
                    </details>
                    <p class="po-note"><i data-lucide="banknote"></i> Paying by cash or cheque instead? Hand it to GMD — they'll record it and it will appear in your Payment History. No upload needed.</p>
                    @endif
                </div>

            </div>

            {{-- ===== Payment Records: History | Billing Statements | Submitted Proofs | Online Payments ===== --}}
            @php
                $recordTabs = [
                    'history'    => ['Payment History',    'receipt',     $payment->transactions->count()],
                    'statements' => ['Billing Statements', 'file-text',   $payment->billingStatements->count()],
                    'proofs'     => ['Submitted Proofs',   'file-check',  $payment->proofs->count()],
                    'checkouts'  => ['Online Payments',    'credit-card', $onlinePayments->count()],
                ];
                // the card's subtitle follows the open tab (like the admin's Payment History card)
                $recordSubs = [
                    'history'    => 'All payments recorded for this project',
                    'statements' => 'Billing statements GMD sent you — open one to view or print it',
                    'proofs'     => 'Proof of payment you sent to GMD',
                    'checkouts'  => 'Payments you made online through PayMongo',
                ];
                // proof uploads are retired — the tab only stays for projects that still have older submissions
                if ($payment->proofs->isEmpty()) {
                    unset($recordTabs['proofs']);
                }
                // a returned proof is what the client most needs to see
                $recordTab = $needsFixing ? 'proofs' : 'history';
            @endphp
            <div class="card pd-records" data-tabs>
                {{-- white header like the admin's Payment History card: title + subtitle on the left (they follow the
                     open tab), the underlined tabs on the right, sitting on the divider line --}}
                <div class="pd-rec-head">
                    <div class="pd-rec-titles">
                        <div class="pd-rec-title" data-tab-title>{{ $recordTabs[$recordTab][0] }}</div>
                        <div class="pd-rec-sub" data-tab-sub>{{ $recordSubs[$recordTab] }}</div>
                    </div>
                    <div class="pd-tabs" role="tablist">
                        @foreach($recordTabs as $key => [$label, $icon, $count])
                        <button type="button" class="pd-tab {{ $recordTab === $key ? 'is-active' : '' }}" data-tab="{{ $key }}" data-title="{{ $label }}" data-sub="{{ $recordSubs[$key] }}" role="tab">
                            <i data-lucide="{{ $icon }}"></i> {{ $label }}
                            <span class="pd-count {{ $key === 'proofs' && $needsFixing ? 'is-alert' : '' }}">{{ $count }}</span>
                        </button>
                        @endforeach
                    </div>
                </div>

            <!-- Submitted Proofs -->
            <div class="pd-panel" data-panel="proofs" @if($recordTab !== 'proofs') hidden @endif>
                @if($payment->proofs->isEmpty())
                <div class="empty-state">
                    <i data-lucide="file-check" style="display:block;margin:0 auto;"></i>
                    <p>No proof of payment submitted yet.</p>
                </div>
                @else
                <div class="table-wrap">
                    <table class="pf-proofs-table">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Stage</th>
                                <th>Amount Paid</th>
                                <th>Mode</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payment->proofs as $proof)
                            @php $proofSettled = $proof->status === 'confirmed' || in_array($proof->payment_stage, $paidStages); @endphp
                            <tr class="pfm-row">
                                <td class="pfm-file" data-label="File">
                                    @if($proof->file_url)
                                    <a href="{{ $proof->file_url }}" target="_blank" data-receipt style="display:inline-flex;align-items:center;gap:6px;color:var(--accent);font-weight:700;text-decoration:none;">
                                        <i data-lucide="file-text" style="width:14px;height:14px;"></i> View
                                    </a>
                                    @else
                                    <span class="pfm-nofile" style="color:var(--muted);">—<span class="pfm-file-word">&nbsp;None</span></span>
                                    @endif
                                </td>
                                <td class="pfm-stage">{{ \App\Models\PaymentTransaction::stageLabel($proof->payment_stage) }}</td>
                                <td class="pfm-amount"><strong>₱{{ number_format($proof->amount ?? 0, 2) }}</strong></td>
                                <td class="pfm-mode" data-label="Mode">{{ $proof->modeOfPaymentLabel() }}</td>
                                <td class="pfm-status">
                                    @if($proofSettled)
                                        <span class="status-badge completed">Confirmed</span>
                                    @elseif($proof->needsCorrection())
                                        <span class="status-badge" style="background:#fef3c7;color:#92400e;">Needs Correction</span>
                                        <div class="pfm-reason">{{ $proof->correction_reason }}</div>
                                    @else
                                        <span class="status-badge pending">{{ $proof->resubmitted_at ? 'Resubmitted' : 'Pending Review' }}</span>
                                    @endif
                                    @php $canResubmit = !$proofSettled && $proof->canBeResubmitted(); @endphp
                                    {{-- desktop: under the status (phones get a full-width button below the card instead) --}}
                                    @if($canResubmit)
                                    <div class="pfm-status-action">@include('partials.client.resubmit_proof_button')</div>
                                    @endif
                                </td>
                                <td class="pfm-date" data-label="Submitted">{{ $proof->submittedAt()->format('M d, Y') }}</td>
                                <td class="pfm-action {{ $canResubmit ? '' : 'is-empty' }}">
                                    @if($canResubmit)
                                    @include('partials.client.resubmit_proof_button')
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="pd-panel-foot">
                    <span>{{ $payment->proofs->count() }} submission{{ $payment->proofs->count() !== 1 ? 's' : '' }}</span>
                    <span>₱{{ number_format($payment->proofs->sum('amount'), 2) }} total submitted</span>
                </div>
                @endif
            </div>

            <!-- Billing Statements -->
            <div class="pd-panel" data-panel="statements" @if($recordTab !== 'statements') hidden @endif>
                @if($payment->billingStatements->isEmpty())
                <div class="empty-state">
                    <i data-lucide="file-text" style="display:block;margin:0 auto;"></i>
                    <p>No billing statements yet. GMD sends one when a payment phase is due.</p>
                </div>
                @else
                <div class="table-wrap">
                    <table class="pd-stmt-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reference No.</th>
                                <th>Stage</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Bill</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $stmtStageAmounts = $payment->stageAmounts();
                                $stmtPaidStages   = $payment->paidStages();
                            @endphp
                            @foreach($payment->billingStatements as $statement)
                            @php
                                $stmtStageLabel = $statement->billing_stage
                                    ? \App\Models\PaymentTransaction::stageLabel($statement->billing_stage)
                                    : 'Full Statement';
                                // what the bill asks for, and whether it's been paid (a full statement covers the whole contract)
                                $stmtAmount = $statement->billing_stage
                                    ? (float) ($stmtStageAmounts[$statement->billing_stage] ?? 0)
                                    : (float) $payment->contract_amount;
                                $stmtPaid = $statement->billing_stage
                                    ? in_array($statement->billing_stage, $stmtPaidStages, true)
                                    : $payment->currentBalance() <= 0;
                            @endphp
                            <tr>
                                <td>{{ $statement->statement_date->format('M d, Y') }}</td>
                                <td style="font-family:monospace;font-size:12px;">{{ $statement->reference_no ?: '—' }}</td>
                                <td>
                                    <span style="display:inline-flex;align-items:center;font-size:11px;font-weight:700;color:var(--accent);background:var(--accent-soft, rgba(0,0,0,.05));border-radius:20px;padding:3px 10px;">{{ $stmtStageLabel }}</span>
                                </td>
                                <td style="font-weight:800;color:var(--dark);white-space:nowrap;">₱{{ number_format($stmtAmount, 2) }}</td>
                                <td>
                                    <span class="status-badge {{ $stmtPaid ? 'completed' : 'pending' }}">{{ $stmtPaid ? 'Paid' : 'Unpaid' }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('client.payments.billing_statements.show', [$payment->id, $statement->id]) }}" target="_blank" data-receipt="doc" style="display:inline-flex;align-items:center;gap:5px;color:var(--accent);font-weight:700;text-decoration:none;">
                                        <i data-lucide="file-text" style="width:14px;height:14px;"></i> View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

            <!-- Payment History -->
            <div class="pd-panel" data-panel="history" @if($recordTab !== 'history') hidden @endif>
                @if($payment->transactions->isEmpty())
                    <div class="empty-state">
                        <i data-lucide="receipt" style="display:block;margin:0 auto;"></i>
                        <p>No payment transactions recorded yet.</p>
                    </div>
                @else
                    <div class="table-wrap">
                        <table class="ph-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Stage</th>
                                    <th>Amount Paid</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payment->transactions->sortByDesc('payment_date') as $tx)
                                    @php $txOnline = $onlinePayments->firstWhere('payment_transaction_id', $tx->id); @endphp
                                    <tr class="ph-row">
                                        <td class="ph-date" data-label="Date Paid">{{ \Carbon\Carbon::parse($tx->payment_date)->format('M d, Y') }}</td>
                                        <td class="ph-stage">{{ \App\Models\PaymentTransaction::stageLabel($tx->payment_stage) }}</td>
                                        <td class="ph-amount"><strong style="color:var(--success);">₱{{ number_format($tx->amount_paid, 2) }}</strong></td>
                                        <td class="ph-method" data-label="Method">{{ $txOnline ? $txOnline->methodLabel() : \App\Models\PaymentProof::modeLabel($tx->mode_of_payment) }}</td>
                                        <td class="ph-ref" data-label="Reference" style="font-family:monospace;font-size:11.5px;">{{ $txOnline->reference ?? $tx->reference_number ?: '—' }}</td>
                                        <td class="ph-receipt" data-label="Receipt">
                                            @php $receiptUrls = !empty($tx->receipt_urls) ? $tx->receipt_urls : array_filter([$tx->receipt_url]); @endphp
                                            @if(!empty($receiptUrls))
                                            <div style="display:flex;flex-direction:column;gap:3px;" data-receipt-set>
                                                @foreach($receiptUrls as $i => $url)
                                                <a href="{{ $url }}" target="_blank" data-receipt style="display:inline-flex;align-items:center;gap:5px;color:var(--accent);font-weight:700;text-decoration:none;">
                                                    <i data-lucide="receipt" style="width:14px;height:14px;"></i> View{{ count($receiptUrls) > 1 ? ' ' . ($i + 1) : '' }}
                                                </a>
                                                @endforeach
                                            </div>
                                            @elseif($tx->hasGeneratedReceipt())
                                            {{-- the system generates the Acknowledgement Receipt --}}
                                            <a href="{{ route('client.payments.receipts.show', [$payment->id, $tx->id]) }}" target="_blank" data-receipt="page" style="display:inline-flex;align-items:center;gap:5px;color:var(--accent);font-weight:700;text-decoration:none;">
                                                <i data-lucide="receipt" style="width:14px;height:14px;"></i> View
                                            </a>
                                            @else
                                            —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Online Payments (PayMongo checkouts) -->
            <div class="pd-panel" data-panel="checkouts" @if($recordTab !== 'checkouts') hidden @endif>
                @if($onlinePayments->isEmpty())
                <div class="empty-state">
                    <i data-lucide="credit-card" style="display:block;margin:0 auto;"></i>
                    <p>No online payments yet.</p>
                </div>
                @else
                <div class="table-wrap">
                    <table class="po-table">
                        <thead>
                            <tr><th>Date</th><th>Stage</th><th>Amount</th><th>Method</th><th>Reference</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach($onlinePayments as $op)
                            <tr>
                                <td data-label="Date">{{ ($op->paid_at ?? $op->created_at)->format('M d, Y') }}</td>
                                <td data-label="Stage">{{ \App\Models\PaymentTransaction::stageLabel($op->payment_stage) }}</td>
                                <td data-label="Amount"><strong>₱{{ number_format($op->amount, 2) }}</strong></td>
                                <td data-label="Method">{{ $op->methodLabel() }}</td>
                                <td data-label="Reference" style="font-family:monospace;font-size:11.5px;">{{ $op->provider_payment_id ?: $op->reference }}</td>
                                <td data-label="Status"><span class="status-badge {{ $op->badgeClass() }}">{{ $op->statusLabel() }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            </div>

    </main>

    {{-- ===== RESUBMIT A PROOF OF PAYMENT — corrects the same submission (no new payment record) ===== --}}
    <div class="modal-overlay" id="resubmitProofModal" role="dialog" aria-modal="true" aria-labelledby="resubmitProofTitle">
        <div class="modal-card" style="max-width:500px;max-height:92vh;overflow-y:auto;">
            <div class="modal-header">
                <div>
                    <h2 id="resubmitProofTitle">Resubmit Proof of Payment</h2>
                    <p id="resubmitProofStage">Correct the details below and send them again.</p>
                </div>
                <button class="modal-close" type="button" data-close-resubmit><i data-lucide="x"></i></button>
            </div>
            <form method="POST" id="resubmitProofForm" enctype="multipart/form-data" action="{{ old('_resubmit_action') }}">
                @csrf
                <input type="hidden" name="_resubmit_action" id="resubmitActionInput" value="{{ old('_resubmit_action') }}">
                <div class="rs-reason" id="resubmitReason" hidden></div>

                @if($errors->resubmit->any())
                <div class="alert-banner error" style="align-items:flex-start;margin-bottom:12px;">
                    <i data-lucide="alert-circle" style="margin-top:2px;"></i>
                    <div>@foreach($errors->resubmit->all() as $message)<div>{{ $message }}</div>@endforeach</div>
                </div>
                @endif

                <div class="form-group">
                    <label>Amount Paid (₱) <span style="color:#dc2626;">*</span></label>
                    <div class="rs-fixed" id="resubmitFixedAmount" hidden></div>
                    <input type="text" inputmode="decimal" name="amount_paid" id="resubmitAmount" placeholder="e.g. 50,000" value="{{ old('amount_paid') }}" autocomplete="off">
                    <small id="resubmitAmountHint" style="display:block;margin-top:6px;font-size:11.5px;color:var(--muted);"></small>
                </div>
                <div class="form-group">
                    <label>Mode of Payment <span style="color:#dc2626;">*</span></label>
                    <select name="mode_of_payment" id="resubmitMode" required>
                        <option value="bank_transfer" {{ old('mode_of_payment') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="cheque" {{ old('mode_of_payment') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                        <option value="cash" {{ old('mode_of_payment') === 'cash' ? 'selected' : '' }}>Cash</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Replace Image / File <span id="resubmitFileReq" style="color:#dc2626;" hidden>*</span></label>
                    <input type="file" name="proof_file" id="resubmitFile" accept=".pdf,.jpg,.jpeg,.png,image/jpeg,image/png">
                    <div class="rs-file-note" id="resubmitFileNote"></div>
                </div>
                <div class="form-group">
                    <label>Note <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                    <textarea name="notes" id="resubmitNotes" rows="2" style="resize:none;" placeholder="e.g. corrected reference number">{{ old('notes') }}</textarea>
                </div>
                <p style="font-size:12px;color:var(--muted);margin:0 0 14px;line-height:1.5;">Your earlier submission is kept on record. GMD reviews the corrected one before it counts as paid.</p>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" data-close-resubmit>Cancel</button>
                    <button type="submit" class="save-btn"><i data-lucide="send" style="width:14px;height:14px;"></i> Resubmit</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/client.js') }}"></script>
    <script>
    (function () {
        var modal  = document.getElementById('resubmitProofModal');
        var form   = document.getElementById('resubmitProofForm');
        if (!modal || !form) return;
        var amount = document.getElementById('resubmitAmount');
        var fixed  = document.getElementById('resubmitFixedAmount');
        var hint   = document.getElementById('resubmitAmountHint');
        var mode   = document.getElementById('resubmitMode');
        var file   = document.getElementById('resubmitFile');
        var hasFile = false;

        function peso(n) { return '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        function show() { modal.classList.add('show'); document.body.style.overflow = 'hidden'; if (typeof lucide !== 'undefined') lucide.createIcons(); }
        function hide() { modal.classList.remove('show'); document.body.style.overflow = ''; }

        // a bank transfer needs a screenshot — required only when this submission has no file yet
        function syncFileRule() {
            var needs = mode.value !== 'cash' && !hasFile;
            document.getElementById('resubmitFileReq').hidden = !needs;
            document.getElementById('resubmitFileNote').textContent = hasFile
                ? 'Leave empty to keep the file you sent before.'
                : (needs
                    ? (mode.value === 'cheque' ? 'Attach a photo of the cheque (PDF, JPG or PNG, max 10MB).' : 'Attach the transfer screenshot or receipt (PDF, JPG or PNG, max 10MB).')
                    : 'Optional for cash.');
        }
        mode.addEventListener('change', syncFileRule);

        // the amount shows thousands separators while typing
        amount.addEventListener('input', function () {
            var raw = amount.value.replace(/[^\d.]/g, '').replace(/(\..*)\./g, '$1');
            var parts = raw.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            amount.value = parts.join('.');
        });

        function open(btn, keepTyped) {
            var d = btn.dataset;
            form.action = d.action;
            document.getElementById('resubmitActionInput').value = d.action;
            document.getElementById('resubmitProofStage').textContent = d.stageLabel + ' — correct the details below and send them again.';
            var reason = document.getElementById('resubmitReason');
            reason.hidden = !d.reason;
            reason.textContent = d.reason ? 'GMD asked you to correct this: ' + d.reason : '';

            // Down and final payments are fixed amounts; only the progress payment is typed in
            if (d.fixed) {
                fixed.hidden = false; fixed.textContent = peso(d.fixed);
                amount.type = 'hidden'; amount.value = d.fixed;
                hint.textContent = 'This payment phase has a fixed amount.';
            } else {
                fixed.hidden = true; amount.type = 'text';
                if (!keepTyped) amount.value = Number(d.amount || 0).toLocaleString('en-US', { maximumFractionDigits: 2 });
                hint.textContent = 'Up to ' + peso(d.max) + ' (your remaining balance).';
            }
            if (!keepTyped) {
                mode.value = d.mode || 'bank_transfer';
                document.getElementById('resubmitNotes').value = d.notes || '';
            }
            file.value = '';
            hasFile = d.hasFile === '1';
            syncFileRule();
            show();
        }

        document.querySelectorAll('[data-resubmit]').forEach(function (btn) {
            btn.addEventListener('click', function () { open(btn, false); });
        });
        modal.querySelectorAll('[data-close-resubmit]').forEach(function (b) { b.addEventListener('click', hide); });
        modal.addEventListener('click', function (e) { if (e.target === modal) hide(); });

        // separators out before sending
        form.addEventListener('submit', function () { amount.value = amount.value.replace(/,/g, ''); });

        // the server sent the correction back with errors — reopen it with what was typed
        @if($errors->resubmit->any() && old('_resubmit_action'))
        var again = document.querySelector('[data-resubmit][data-action="{{ old('_resubmit_action') }}"]');
        if (again) open(again, true);
        @endif
    })();
    </script>
    <script>
        lucide.createIcons();

        // ── Card tabs: each [data-tabs] card switches its own panels; [data-goto-tab] links jump to a tab ──
        (function () {
            function activate(card, name) {
                card.querySelectorAll('.pd-tab').forEach(function (t) {
                    var on = t.dataset.tab === name;
                    t.classList.toggle('is-active', on);
                    // the header's title + subtitle follow the open tab
                    if (on && t.dataset.title) {
                        var title = card.querySelector('[data-tab-title]'), sub = card.querySelector('[data-tab-sub]');
                        if (title) title.textContent = t.dataset.title;
                        if (sub) sub.textContent = t.dataset.sub || '';
                    }
                });
                card.querySelectorAll('.pd-panel').forEach(function (p) { p.hidden = p.dataset.panel !== name; });
            }
            document.querySelectorAll('[data-tabs]').forEach(function (card) {
                card.querySelectorAll('.pd-tab').forEach(function (tab) {
                    tab.addEventListener('click', function () { activate(card, tab.dataset.tab); });
                });
            });
            document.querySelectorAll('[data-goto-tab]').forEach(function (link) {
                link.addEventListener('click', function (e) {
                    var tab  = document.querySelector('.pd-tab[data-tab="' + link.dataset.gotoTab + '"]');
                    if (!tab) return;
                    e.preventDefault();
                    var card = tab.closest('[data-tabs]');
                    activate(card, link.dataset.gotoTab);
                    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });
        })();

        // ── Proof-of-payment upload — one form covering every open stage. The dropzone
        // swaps for a single preview chip once a file is picked (mirrors the "Request a
        // Quotation" reference-files upload, just capped at one file per submission). ──
        (function () {
            var form     = document.getElementById('proofUploadForm');
            if (!form) return;

            var input    = document.getElementById('proofFileInput');
            var dropzone = document.getElementById('proofDropzone');
            var preview  = document.getElementById('proofFilePreview');
            var amount   = document.getElementById('proofAmountInput');
            var stage    = document.getElementById('proofStageSelect');
            var mopGroup = document.getElementById('proofMopGroup');
            var fileReq  = document.getElementById('proofFileReq');
            var dropzoneText = document.getElementById('proofDropzoneText');
            var selected = null;

            function selectedMode() {
                var checked = mopGroup && mopGroup.querySelector('input[type="radio"]:checked');
                return checked ? checked.value : null;
            }
            function fileIsRequired() {
                // A bank transfer needs its screenshot and a cheque a photo of the cheque; cash is often
                // a physical handover with nothing to photograph, so it's optional only for cash.
                return selectedMode() !== 'cash';
            }

            function formatSize(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
                return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
            }
            function syncInput() {
                if (!selected) { input.value = ''; return; }
                var dt = new DataTransfer();
                dt.items.add(selected);
                input.files = dt.files;
            }
            function removeFile() {
                selected = null;
                syncInput();
                render();
            }
            function render() {
                dropzone.style.display = selected ? 'none' : '';
                preview.style.display  = selected ? 'flex' : 'none';
                if (!selected) { preview.innerHTML = ''; return; }

                var isImage = selected.type.indexOf('image/') === 0;
                var thumb   = isImage
                    ? '<img class="qr-file-thumb" src="' + URL.createObjectURL(selected) + '" alt="">'
                    : '<span class="qr-file-thumb"><i data-lucide="file-text"></i></span>';
                preview.innerHTML = '<div class="qr-file-chip" title="Click to view">'
                    + thumb
                    + '<div class="qr-file-meta">'
                        + '<div class="qr-file-name" title="' + selected.name.replace(/"/g, '&quot;') + '">' + selected.name + '</div>'
                        + '<div class="qr-file-size">' + formatSize(selected.size) + '</div>'
                    + '</div>'
                    + '<button type="button" class="qr-file-remove" title="Remove"><i data-lucide="x"></i></button>'
                + '</div>';

                preview.querySelector('.qr-file-chip').addEventListener('click', function (e) {
                    if (e.target.closest('.qr-file-remove')) return;
                    window.open(URL.createObjectURL(selected), '_blank');
                });
                preview.querySelector('.qr-file-remove').addEventListener('click', removeFile);

                if (typeof lucide !== 'undefined') lucide.createIcons();
            }

            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (!file) return;
                if (file.size > 10 * 1024 * 1024) {
                    input.value = '';
                    if (typeof showFileTooLargeModal === 'function') showFileTooLargeModal(file.name, 10);
                    return;
                }
                selected = file;
                render();
                dropzone.classList.remove('is-invalid');
            });

            amount.addEventListener('input', function () {
                var raw   = amount.value.replace(/[^0-9.]/g, '');
                var parts = raw.split('.');
                var whole = parts[0].replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                amount.value = whole + (parts.length > 1 ? '.' + parts.slice(1).join('').slice(0, 2) : '');
                amount.classList.remove('is-invalid');
            });
            // ── Amount per stage: only the progress payment is typed in. The down payment is fixed,
            // and the final payment is fixed at everything still owed — more if the progress payment
            // was left unpaid, less if the client paid extra on it. ──
            var amountHint = document.getElementById('proofAmountHint');
            function money(n) {
                return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            function stageDue() {
                var opt = stage.options[stage.selectedIndex];
                return opt && opt.value ? (parseFloat(opt.dataset.due) || 0) : 0;
            }
            // the most that can be submitted — for the progress payment that is the whole remaining balance
            function stageMax() {
                var opt = stage.options[stage.selectedIndex];
                return opt && opt.value ? (parseFloat(opt.dataset.max) || 0) : 0;
            }
            function syncAmountForStage(keepTyped) {
                var opt   = stage.options[stage.selectedIndex];
                var due   = stageDue();
                var carry = opt ? (parseFloat(opt.dataset.carry) || 0) : 0;
                var fixed = stage.value !== 'progress_payment' && due > 0;
                var hint  = '';

                amount.readOnly = fixed;
                if (fixed && stage.value === 'final_payment') {
                    amount.value = money(due);
                    hint = carry > 0
                        ? 'Fixed balance due, including ₱' + money(carry) + ' unpaid progress payment.'
                        : 'Fixed amount — the remaining balance of your contract.';
                } else if (fixed) {
                    amount.value = money(due);
                    hint = 'The down payment is a fixed amount.';
                } else if (stage.value === 'progress_payment' && due > 0) {
                    if (!keepTyped) amount.value = '';
                    hint = '₱' + money(due) + ' remaining. You can pay this in several payments — anything you pay above it is deducted from your final payment, and anything still unpaid when the project reaches final payment is added to it.';
                }
                amountHint.textContent = hint;
                amountHint.style.display = hint ? 'block' : 'none';
                amount.classList.remove('is-invalid');
            }
            stage.addEventListener('change', function () {
                stage.classList.remove('is-invalid');
                syncAmountForStage(false);
            });
            if (stage.value) syncAmountForStage(true); // stage restored after a failed submit

            function syncFileRequirement() {
                var required = fileIsRequired();
                fileReq.style.display = required ? '' : 'none';
                dropzoneText.textContent = selectedMode() === 'cheque'
                    ? 'Click to upload a photo of the cheque'
                    : (required ? 'Click to upload receipt/screenshot' : 'Click to upload a receipt (optional for cash)');
                if (!required) dropzone.classList.remove('is-invalid');
            }

            if (mopGroup) {
                var syncMopHighlight = function () {
                    mopGroup.querySelectorAll('.mop-option').forEach(function (lbl) {
                        lbl.classList.toggle('mop-selected', lbl.querySelector('input').checked);
                    });
                };
                mopGroup.querySelectorAll('input[type="radio"]').forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        syncMopHighlight();
                        syncFileRequirement();
                        mopGroup.classList.remove('is-invalid');
                    });
                });
                syncMopHighlight(); // reflects a pre-checked option after a failed resubmit
                syncFileRequirement();
            }

            form.addEventListener('submit', function (e) {
                var invalid = false;

                stage.classList.toggle('is-invalid', !stage.value);
                if (!stage.value) invalid = true;

                var typedAmount = parseFloat(amount.value.replace(/,/g, ''));
                var validAmount = typedAmount > 0 && (stageMax() <= 0 || typedAmount <= stageMax() + 0.01);
                amount.classList.toggle('is-invalid', !validAmount);
                if (!validAmount) invalid = true;

                if (mopGroup) {
                    var hasMode = !!mopGroup.querySelector('input[type="radio"]:checked');
                    mopGroup.classList.toggle('is-invalid', !hasMode);
                    if (!hasMode) invalid = true;
                }

                var needsFile = fileIsRequired();
                dropzone.classList.toggle('is-invalid', needsFile && !selected);
                if (needsFile && !selected) invalid = true;

                if (invalid) {
                    e.preventDefault();
                    return;
                }
                amount.value = amount.value.replace(/,/g, '');
            });
        })();
    </script>
    @include('partials.receipt_viewer')

    @if(session('success'))
    {{-- Saved (e.g. proof of payment submitted): small dark toast centered below the header, 3 seconds --}}
    <div class="toast" id="clientPaymentToast" role="status">
        <i data-lucide="check-circle"></i>
        <span>{{ session('success') }}</span>
    </div>
    <script>
        window.addEventListener('load', function () {
            var t = document.getElementById('clientPaymentToast');
            if (!t) return;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            requestAnimationFrame(function () { requestAnimationFrame(function () { t.classList.add('show'); }); });
            setTimeout(function () { t.classList.remove('show'); }, 3000);
        });
    </script>
    @endif
</body>
</html>
