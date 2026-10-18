<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Detail | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            @php
                $status    = $payment->computeStatus();
                $totalPaid = $payment->totalPaid();
                $balance   = $payment->currentBalance();

                // The Down Payment is a fixed amount; Progress and Final Payment accept partial payments.
                $stageMeta = [];
                // Stages must be settled in order — only the one currently open is selectable. Once the
                // project reaches its final-payment phase an unpaid Progress Payment is closed and its
                // balance is carried into the Final Payment (see Payment::proofStageStates()).
                $stageStates = $payment->proofStageStates(false);
                $amountsDue  = $payment->stageAmountsDue();
                $finalCarry  = max(0, round(($amountsDue['final_payment'] ?? 0) - ($payment->stageAmounts()['final_payment'] ?? 0), 2));
                foreach ($payment->stages() as $st) {
                    $stageMeta[$st] = [
                        'remaining' => $amountsDue[$st] ?? 0,
                        'partial'   => $st !== 'down_payment',
                        'carry'     => $st === 'final_payment' ? $finalCarry : 0,
                        'isCarried' => $stageStates[$st] === 'carried',
                        'percent'   => match(true) {
                            $st === 'down_payment'     => 50,
                            $st === 'progress_payment' => 30,
                            $st === 'final_payment'    => $payment->payment_term_type === 'big_project' ? 20 : 50,
                            default                    => null,
                        },
                        'isPaid'      => $stageStates[$st] === 'paid',
                        'isSelectable' => $stageStates[$st] === 'open',
                    ];
                }
            @endphp

            <!-- Breadcrumb -->
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('admin.payments') }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    Payments
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                @if(in_array($payment->project?->status, ['completed', 'archived'], true))
                {{-- finished projects' payments are listed on their client's page; active ones on the Payments page itself --}}
                <a href="{{ route('admin.payments.client', ['client' => $payment->project->client_id ?: $payment->client, 'tab' => $payment->project->status]) }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    {{ $payment->client }}
                </a>
                @else
                <span style="color:var(--muted);font-weight:600;">{{ $payment->client }}</span>
                @endif
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="color:var(--dark);font-weight:700;">{{ $payment->project->name ?? 'Payment Detail' }}</span>
            </div>

            <!-- Header -->
            <div class="page-header">
                <div>
                    <h1 style="margin:0;">{{ $payment->project->name ?? 'Payment Detail' }}</h1>
                    <p>
                        <span class="client-pill">{{ $payment->client }}</span>
                        @if($payment->project && $payment->project->tankItems->isNotEmpty())
                            @foreach($payment->project->tankItems as $ti)
                            &nbsp;·&nbsp; <strong>{{ $ti->quantity }}×</strong> {{ $ti->tank_type }}@if($ti->capacity) ({{ $ti->capacity }})@endif
                            @endforeach
                        @endif
                        &nbsp;·&nbsp; {{ $payment->payment_terms }}
                    </p>
                </div>
                <div style="display:flex;gap:10px;align-items:center;">
                    <button class="cancel-btn" type="button" id="generateBillingBtn" style="white-space:nowrap;">
                        <i data-lucide="file-text"></i>
                        Generate Billing Statement
                    </button>
                    @if($status !== 'Fully Paid')
                    <button class="add-btn" type="button" id="openRecordModal">
                        <i data-lucide="plus"></i>
                        Record Payment
                    </button>
                    @endif
                </div>
            </div>

            @if(session('success'))
            {{-- Saved (e.g. payment recorded, billing statement sent): the small dark toast below the
                 header (same as "Salary record saved."), shown for 3 seconds --}}
            <div class="toast" id="paymentToast" role="status">
                <i data-lucide="check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
            <script>
                window.addEventListener('load', function () {
                    var t = document.getElementById('paymentToast');
                    if (!t) return;
                    // the page-enter animation moves the content, which would carry a fixed toast with it —
                    // living directly under <body> keeps it pinned below the header
                    document.body.appendChild(t);
                    requestAnimationFrame(function () { requestAnimationFrame(function () { t.classList.add('show'); }); });
                    setTimeout(function () { t.classList.remove('show'); }, 3000);
                });
            </script>
            @endif
            @if(session('error'))
            <div class="alert-banner error">
                <i data-lucide="alert-circle"></i>
                {{ session('error') }}
            </div>
            @endif

            {{-- A stage is due but the client hasn't been billed for it: they aren't asked to pay (and can't
                 submit proof) until a billing statement is sent --}}
            @php
                $billingPending = $payment->billingPendingStage();
                $billingDraft   = $billingPending ? $payment->unsentStatementFor($billingPending) : null;
            @endphp
            @if($billingPending)
            <div class="pd-billing-pending">
                <div class="pd-billing-icon"><i data-lucide="file-clock"></i></div>
                <div class="pd-billing-body">
                    <div class="pd-billing-title">Billing statement not sent — {{ \App\Models\PaymentTransaction::stageLabel($billingPending) }} (₱{{ number_format($payment->stageAmountsDue()[$billingPending] ?? 0, 2) }})</div>
                    <div class="pd-billing-text">
                        @if($billingDraft)
                        A statement dated {{ $billingDraft->statement_date->format('M d, Y') }} is prepared but hasn't been sent. The client isn't notified and can't submit payment until you send it.
                        @else
                        This payment phase is due, but the client hasn't been billed yet. They aren't notified and can't submit payment until a billing statement is sent.
                        @endif
                    </div>
                </div>
                @if($billingDraft)
                <a href="{{ route('admin.payments.billing_statements.show', [$payment->id, $billingDraft->id]) }}" class="save-btn" style="text-decoration:none;white-space:nowrap;">
                    <i data-lucide="send"></i> Review &amp; Send
                </a>
                @else
                <button type="button" class="save-btn" style="white-space:nowrap;" onclick="document.getElementById('generateBillingBtn').click()">
                    <i data-lucide="file-plus"></i> Prepare Statement
                </button>
                @endif
            </div>
            <style>
                .pd-billing-pending { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; padding: 14px 18px; border-radius: 16px; background: #fffbeb; border: 1px solid #fde68a; }
                .pd-billing-icon { width: 38px; height: 38px; border-radius: 12px; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
                .pd-billing-icon svg { width: 18px; height: 18px; }
                .pd-billing-body { flex: 1; min-width: 0; }
                .pd-billing-title { font-size: 14px; font-weight: 800; color: var(--dark); }
                .pd-billing-text { margin-top: 3px; font-size: 12.5px; color: var(--muted); line-height: 1.5; }
                @media (max-width: 640px) {
                    .pd-billing-pending { flex-wrap: wrap; align-items: flex-start; }
                    .pd-billing-pending .save-btn { width: 100%; justify-content: center; }
                }
            </style>
            @endif

            <!-- Summary Cards -->
            <div class="page-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
                <div class="info-card teal">
                    <div class="info-card-icon teal"><i data-lucide="file-text"></i></div>
                    <h3>Contract Amount</h3>
                    <div class="value">₱{{ number_format(round($payment->contract_amount), 0) }}</div>
                    <div class="info-card-sub">{{ $payment->payment_terms }}</div>
                </div>
                <div class="info-card green">
                    <div class="info-card-icon green"><i data-lucide="check-circle"></i></div>
                    <h3>Total Paid</h3>
                    <div class="value">₱{{ number_format(round($totalPaid), 0) }}</div>
                    <div class="info-card-sub">
                        {{ round($payment->contract_amount > 0 ? ($totalPaid / $payment->contract_amount) * 100 : 0, 1) }}% of contract
                    </div>
                </div>
                <div class="info-card blue">
                    <div class="info-card-icon blue"><i data-lucide="alert-circle"></i></div>
                    <h3>Remaining Balance</h3>
                    <div class="value">₱{{ number_format(round($balance), 0) }}</div>
                    <div class="info-card-sub">Still outstanding</div>
                </div>
                <div class="info-card orange">
                    <div class="info-card-icon orange"><i data-lucide="badge-check"></i></div>
                    <h3>Status</h3>
                    <div style="margin-top:8px;">
                        <span class="status-badge {{ \App\Models\Payment::statusBadgeClass($status) }}" style="font-size:13px;padding:6px 14px;">
                            {{ $status }}
                        </span>
                    </div>
                    <div class="info-card-sub">Payment progress</div>
                </div>
            </div>

            <!-- Client Submissions: proof of payment the client uploaded on their side -->
            <div class="table-card" style="margin-bottom:24px;">
                <div class="table-toolbar" style="padding-bottom:14px;margin-bottom:0;border-bottom:1px solid var(--border);">
                    <div>
                        <div style="font-weight:800;font-size:15px;color:var(--dark);">Client Submissions</div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;">Proof of payment the client uploaded — verify, then record it below</div>
                    </div>
                    <span style="font-size:13px;font-weight:700;color:var(--muted);">{{ $proofs->count() }} submission{{ $proofs->count() !== 1 ? 's' : '' }}</span>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Date</th>
                                <th style="text-align:left;">Stage</th>
                                <th style="text-align:center;">Amount Paid</th>
                                <th style="text-align:center;">Mode</th>
                                <th style="text-align:center;">File</th>
                                <th style="text-align:center;">Notes</th>
                                <th style="text-align:center;">Status</th>
                                <th style="text-align:right;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($proofs as $proof)
                            {{-- "Confirmed" once admin has recorded THIS submission (status), even if that
                                 payment was only a partial and the stage as a whole isn't fully paid yet —
                                 or if the stage happens to be fully settled some other way. --}}
                            @php $proofSettled = $proof->status === 'confirmed' || in_array($proof->payment_stage, $paidStages); @endphp
                            <tr>
                                <td style="white-space:nowrap;color:var(--muted);">{{ $proof->submittedAt()->format('M d, Y') }}</td>
                                <td><strong>{{ \App\Models\PaymentTransaction::stageLabel($proof->payment_stage) }}</strong></td>
                                <td style="text-align:center;"><strong>₱{{ number_format($proof->amount ?? 0, 2) }}</strong></td>
                                <td style="text-align:center;">{{ $proof->modeOfPaymentLabel() }}</td>
                                <td style="text-align:center;">
                                    @if($proof->file_url)
                                    <a href="{{ $proof->file_url }}" target="_blank" data-receipt class="action-btn view" title="View File">
                                        <i data-lucide="file-text"></i>
                                    </a>
                                    @else
                                    <span style="color:var(--muted);">—</span>
                                    @endif
                                </td>
                                <td style="text-align:center;color:var(--muted);">{{ $proof->notes ?: '—' }}</td>
                                <td style="text-align:center;">
                                    @if($proofSettled)
                                        <span class="status-badge completed">Confirmed</span>
                                    @elseif($proof->needsCorrection())
                                        <span class="status-badge" style="background:#fef3c7;color:#92400e;">Needs Correction</span>
                                        <div style="margin-top:4px;font-size:11.5px;color:var(--muted);max-width:220px;margin-left:auto;margin-right:auto;white-space:normal;">“{{ $proof->correction_reason }}”</div>
                                    @else
                                        <span class="status-badge pending">{{ $proof->resubmitted_at ? 'Resubmitted' : 'Pending Review' }}</span>
                                    @endif
                                    @if(!empty($proof->revisions))
                                        <button type="button" class="pd-history-link" data-proof-history="{{ $proof->id }}">
                                            {{ count($proof->revisions) }} earlier version{{ count($proof->revisions) === 1 ? '' : 's' }}
                                        </button>
                                    @endif
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    @if(!$proofSettled && $status !== 'Fully Paid' && !$proof->needsCorrection())
                                    <div style="display:inline-flex;gap:6px;">
                                        <button type="button" class="cancel-btn" style="padding:7px 12px;font-size:12px;"
                                                data-return-proof
                                                data-action="{{ route('admin.payments.proofs.return', [$payment->id, $proof->id]) }}"
                                                data-summary="{{ \App\Models\PaymentTransaction::stageLabel($proof->payment_stage) }} · ₱{{ number_format($proof->amount ?? 0, 2) }} ({{ $proof->modeOfPaymentLabel() }})">
                                            <i data-lucide="corner-up-left" style="width:13px;height:13px;"></i>
                                            Return
                                        </button>
                                        <button type="button" class="save-btn" style="padding:7px 12px;font-size:12px;"
                                                data-record-proof
                                                data-id="{{ $proof->id }}"
                                                data-stage="{{ $proof->payment_stage }}" data-amount="{{ $proof->amount }}" data-mode="{{ $proof->mode_of_payment }}" data-date="{{ $proof->submittedAt()->format('Y-m-d') }}"
                                                data-summary="{{ \App\Models\PaymentTransaction::stageLabel($proof->payment_stage) }} · ₱{{ number_format($proof->amount ?? 0, 2) }} ({{ $proof->modeOfPaymentLabel() }})">
                                            <i data-lucide="check" style="width:13px;height:13px;"></i>
                                            Record Payment
                                        </button>
                                    </div>
                                    @elseif($proof->needsCorrection())
                                    <span style="font-size:12px;font-weight:700;color:var(--muted);">Waiting for the client</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:40px;color:var(--muted);">
                                    <i data-lucide="upload-cloud" style="width:32px;height:32px;opacity:.3;display:block;margin:0 auto 10px;"></i>
                                    No proof of payment submitted by the client yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($onlinePayments->isNotEmpty())
            <!-- Online Payments (payment gateway) -->
            <div class="table-card" style="margin-bottom:20px;">
                <div class="table-toolbar" style="padding-bottom:14px;margin-bottom:0;border-bottom:1px solid var(--border);">
                    <div>
                        <div style="font-weight:800;font-size:15px;color:var(--dark);">Online Payments</div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;">Checkouts the client started through PayMongo. Paid ones are verified with the gateway and recorded in Payment History automatically.</div>
                    </div>
                    <span style="font-size:13px;font-weight:700;color:var(--muted);">{{ $onlinePayments->where('status', 'paid')->count() }} paid · ₱{{ number_format($onlinePayments->where('status', 'paid')->sum('amount'), 2) }}</span>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Started</th>
                                <th style="text-align:left;">Stage</th>
                                <th style="text-align:center;">Amount</th>
                                <th style="text-align:center;">Method</th>
                                <th style="text-align:left;">Reference / Transaction</th>
                                <th style="text-align:center;">Status</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($onlinePayments as $op)
                            <tr>
                                <td style="white-space:nowrap;color:var(--muted);">{{ $op->created_at->format('M d, Y g:i A') }}</td>
                                <td><strong>{{ \App\Models\PaymentTransaction::stageLabel($op->payment_stage) }}</strong></td>
                                <td style="text-align:center;"><strong>₱{{ number_format($op->amount, 2) }}</strong></td>
                                <td style="text-align:center;">{{ $op->methodLabel() }}</td>
                                <td style="font-size:12px;">
                                    <div style="font-family:monospace;">{{ $op->reference }}</div>
                                    @if($op->provider_payment_id)<div style="font-family:monospace;color:var(--muted);">{{ $op->provider_payment_id }}</div>@endif
                                    @if($op->paid_at)<div style="color:var(--muted);">Paid {{ $op->paid_at->format('M d, Y g:i A') }}</div>@endif
                                    @if($op->failure_reason)<div style="color:#b45309;">{{ $op->failure_reason }}</div>@endif
                                </td>
                                <td style="text-align:center;"><span class="status-badge {{ $op->badgeClass() }}">{{ $op->statusLabel() }}</span></td>
                                <td style="text-align:center;">
                                    @if($op->isPending() && $op->checkout_session_id)
                                    <form method="POST" action="{{ route('admin.payments.online.verify', $op->id) }}" style="margin:0;">
                                        @csrf
                                        <button type="submit" class="action-btn view" title="Check with PayMongo now" aria-label="Check status with PayMongo"><i data-lucide="refresh-cw"></i></button>
                                    </form>
                                    @else
                                    <span style="color:var(--muted);">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Payment History -->
            <div class="table-card">
                <div class="table-toolbar" style="padding-bottom:14px;margin-bottom:0;border-bottom:1px solid var(--border);">
                    <div>
                        <div style="font-weight:800;font-size:15px;color:var(--dark);">Payment History</div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;">All recorded transactions for this project</div>
                    </div>
                    <span style="font-size:13px;font-weight:700;color:var(--muted);">{{ $payment->transactions->count() }} transaction{{ $payment->transactions->count() !== 1 ? 's' : '' }}</span>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Date</th>
                                <th style="text-align:left;">Stage</th>
                                <th style="text-align:center;">Amount Paid</th>
                                <th style="text-align:center;">Mode</th>
                                <th style="text-align:center;">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payment->transactions->sortByDesc('payment_date') as $tx)
                            <tr>
                                <td style="white-space:nowrap;color:var(--muted);">{{ \Carbon\Carbon::parse($tx->payment_date)->format('M d, Y') }}</td>
                                <td><strong>{{ \App\Models\PaymentTransaction::stageLabel($tx->payment_stage) }}</strong></td>
                                <td style="text-align:center;"><strong style="color:#16a34a;">₱{{ number_format(round($tx->amount_paid), 0) }}</strong></td>
                                <td style="text-align:center;">
                                    @if($tx->mode_of_payment)
                                    @php
                                        $mopStyle = match($tx->mode_of_payment) {
                                            'bank_transfer' => 'background:#dbeafe;color:#1d4ed8;',
                                            'cheque'        => 'background:#fef3c7;color:#92400e;',
                                            'online_bank'   => 'background:#ede9fe;color:#5b21b6;',
                                            default         => 'background:#dcfce7;color:#15803d;',
                                        };
                                        $mopLabel = \App\Models\PaymentProof::modeLabel($tx->mode_of_payment);
                                    @endphp
                                    <span class="status-badge" style="{{ $mopStyle }}box-shadow:none;font-size:12px;">{{ $mopLabel }}</span>
                                    @else
                                    <span style="color:var(--muted);">—</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    @php $receiptUrls = !empty($tx->receipt_urls) ? $tx->receipt_urls : array_filter([$tx->receipt_url]); @endphp
                                    @if(!empty($receiptUrls))
                                    <div style="display:inline-flex;gap:4px;" data-receipt-set>
                                        @foreach($receiptUrls as $i => $url)
                                        <a href="{{ $url }}" target="_blank" data-receipt class="action-btn view" title="View Receipt {{ count($receiptUrls) > 1 ? $i + 1 : '' }}">
                                            <i data-lucide="receipt"></i>
                                        </a>
                                        @endforeach
                                    </div>
                                    @else
                                    <span style="color:var(--muted);">—</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="text-align:center;padding:40px;color:var(--muted);">
                                    <i data-lucide="receipt" style="width:32px;height:32px;opacity:.3;display:block;margin:0 auto 10px;"></i>
                                    No payment transactions recorded yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Record Payment Modal -->
    <div class="modal-overlay" id="recordPaymentModal">
        <div class="modal-card" style="max-width:520px;">
            <div class="modal-header">
                <div>
                    <h2>Record Payment</h2>
                    <p>Log a payment transaction for <strong>{{ $payment->project->name ?? 'this project' }}</strong></p>
                </div>
                <button class="modal-close" type="button" id="closeRecordModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.payments.record', $payment->id) }}" id="recordPaymentForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="proof_id" id="recordProofIdInput" value="">
                <div id="recordProofLinkNote" style="display:none;margin-bottom:14px;padding:10px 14px;border-radius:12px;background:var(--cream-soft);border:1px solid var(--border);font-size:12.5px;font-weight:700;color:var(--dark);"></div>
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Payment Stage</label>
                        <div class="stage-select" id="stageSelectWrap">
                            <button type="button" class="stage-select-trigger placeholder" id="stageSelectTrigger">
                                <span id="stageSelectLabel">Select stage</span>
                                <i data-lucide="chevron-down"></i>
                            </button>
                            <div class="stage-select-menu" id="stageSelectMenu">
                                @foreach($payment->stages() as $i => $stage)
                                @php
                                    $isPaid       = $stageMeta[$stage]['isPaid'];
                                    $isSelectable = $stageMeta[$stage]['isSelectable'];
                                    $stageLabel   = \App\Models\PaymentTransaction::stageLabel($stage);
                                @endphp
                                <div class="stage-select-option{{ $isSelectable ? '' : ' disabled' }}"
                                     data-value="{{ $stage }}"
                                     data-label="{{ $stageLabel }}"
                                     data-remaining="{{ $stageMeta[$stage]['remaining'] }}"
                                     data-partial="{{ $stageMeta[$stage]['partial'] ? '1' : '0' }}"
                                     data-percent="{{ $stageMeta[$stage]['percent'] }}"
                                     data-carry="{{ $stageMeta[$stage]['carry'] }}"
                                     @if($isSelectable) onclick="selectStage(this)" @endif>
                                    <span>{{ $stageLabel }}</span>
                                    @if($isPaid)
                                        <span class="stage-select-status" style="color:#16a34a;">✓ Paid</span>
                                    @elseif($stageMeta[$stage]['isCarried'])
                                        <span class="stage-select-status" style="color:var(--muted);">
                                            <i data-lucide="lock" style="width:11px;height:11px;vertical-align:-1px;"></i>
                                            Closed — balance added to Final Payment
                                        </span>
                                    @elseif(!$isSelectable)
                                        <span class="stage-select-status" style="color:var(--muted);">
                                            <i data-lucide="lock" style="width:11px;height:11px;vertical-align:-1px;"></i>
                                            Complete previous stage first
                                        </span>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <select name="payment_stage" id="stageSelect" style="display:none;">
                            <option value=""></option>
                            @foreach($payment->stages() as $stage)
                            <option value="{{ $stage }}"></option>
                            @endforeach
                        </select>
                        <span id="stageSelectErr" style="display:none;color:#b91c1c;font-size:12px;font-weight:600;margin-top:6px;">Please select a payment stage.</span>
                    </div>
                    <div class="form-group">
                        <label>Amount Paid (₱)</label>
                        <input type="text" inputmode="decimal" name="amount_paid" id="amountPaidInput"
                               required placeholder="e.g. 425,000"
                               oninput="formatMoneyInput(this)">
                        <span id="amountHint" style="display:block;font-size:11px;color:var(--muted);margin-top:6px;font-weight:600;"></span>
                    </div>
                    <div class="form-group">
                        <label>Payment Date</label>
                        <input type="date" name="payment_date" required
                               value="{{ now()->format('Y-m-d') }}">
                    </div>

                    <div class="form-group form-group-full">
                        <label>Mode of Payment</label>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;" id="mopGroup">
                            <label class="mop-option" for="mop_bank">
                                <input type="radio" name="mode_of_payment" id="mop_bank" value="bank_transfer" required style="display:none;" onchange="highlightMop()">
                                <i data-lucide="building-2" style="width:16px;height:16px;"></i>
                                Bank Transfer
                            </label>
                            <label class="mop-option" for="mop_cheque">
                                <input type="radio" name="mode_of_payment" id="mop_cheque" value="cheque" style="display:none;" onchange="highlightMop()">
                                <i data-lucide="file-text" style="width:16px;height:16px;"></i>
                                Cheque
                            </label>
                            <label class="mop-option" for="mop_cash">
                                <input type="radio" name="mode_of_payment" id="mop_cash" value="cash" style="display:none;" onchange="highlightMop()">
                                <i data-lucide="banknote" style="width:16px;height:16px;"></i>
                                Cash
                            </label>
                        </div>
                    </div>

                    <div class="form-group form-group-full">
                        <label>OR / Serial Number</label>
                        <input type="text" name="reference_number" id="referenceNumberInput" required maxlength="100"
                               placeholder="e.g. OR-2026-00123" autocomplete="off">
                        <small id="referenceNumberErr" style="display:none;margin-top:6px;font-size:12px;font-weight:700;color:#dc2626;">
                            This OR / Serial Number is already used on another payment.
                        </small>
                    </div>

                    <div class="form-group form-group-full">
                        <label>Upload Collection Receipt</label>
                        <label for="receiptFileInput" class="pv-upload-dropzone" id="receiptDropzone">
                            <i data-lucide="upload-cloud" style="width:22px;height:22px;color:var(--accent);"></i>
                            <span style="font-size:13px;font-weight:700;color:var(--text-primary);">Click to upload collection receipt(s)</span>
                            <span style="font-size:11px;color:var(--muted);">PDF or image, up to 5 files, max 10MB each</span>
                        </label>
                        <input type="file" name="receipt_files[]" id="receiptFileInput" accept=".pdf,image/*" multiple style="display:none;">
                        <div id="receiptFilePreview" class="qr-file-list" style="display:none;"></div>
                        <span id="receiptFileErr" style="display:none;color:#b91c1c;font-size:12px;font-weight:600;margin-top:6px;">Please upload at least one collection receipt.</span>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelRecordModal">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="save"></i>
                        Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Generate Billing Statement Modal -->
    <div class="modal-overlay" id="billingStatementModal">
        <div class="modal-card" style="max-width:640px;max-height:90vh;overflow-y:auto;">
            <div class="modal-header">
                <div>
                    <h2>Generate Billing Statement</h2>
                    <p>Fill in the statement details for <strong>{{ $payment->project->name ?? 'this project' }}</strong></p>
                </div>
                <button class="modal-close" type="button" id="closeBillingStatementModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.payments.billing_statements.store', $payment->id) }}" id="billingStatementForm">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label>Attention</label>
                        <input type="text" name="attention" placeholder="e.g. Mr. Juan Dela Cruz">
                    </div>
                    <div class="form-group">
                        <label>Bill To</label>
                        <input type="text" name="bill_to" value="{{ $payment->client }}">
                    </div>
                    <div class="form-group">
                        <label>Statement Date</label>
                        <input type="date" name="statement_date" required value="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label>Reference No.</label>
                        <input type="text" name="reference_no" placeholder="e.g. BS-2026-001">
                    </div>
                    <div class="form-group">
                        <label>Client TIN#</label>
                        <input type="text" name="tin_number" placeholder="e.g. 123-456-789-000">
                    </div>
                    <div class="form-group">
                        <label>Project Title</label>
                        <input type="text" name="project_title" value="{{ $payment->project->name ?? '' }}">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Project Location</label>
                        <input type="text" name="project_location" value="{{ $payment->project->address ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label>P.O. Number</label>
                        <input type="text" name="po_number">
                    </div>
                    <div class="form-group">
                        <label>P.R. Number</label>
                        <input type="text" name="pr_number">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Subject</label>
                        <input type="text" name="subject">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Billing For <span style="font-weight:400;color:var(--muted);">(which payment stage the client should pay now)</span></label>
                        <select name="billing_stage">
                            <option value="">Full Statement — show all stages</option>
                            @foreach($payment->stages() as $stage)
                            <option value="{{ $stage }}" @if(in_array($stage, $paidStages)) disabled @endif>
                                {{ \App\Models\PaymentTransaction::stageLabel($stage) }}@if(in_array($stage, $paidStages)) — Already Paid @endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form-group-full">
                        <label>Deposit Instructions</label>
                        <textarea name="deposit_instructions" rows="2" placeholder="e.g. Bank name, account name, account number..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Prepared By (Name)</label>
                        <input type="text" name="prepared_by_name">
                    </div>
                    <div class="form-group">
                        <label>Prepared By (Role)</label>
                        <input type="text" name="prepared_by_role" value="Accounting Dept.">
                    </div>
                    <div class="form-group">
                        <label>Approved By (Name)</label>
                        <input type="text" name="approved_by_name">
                    </div>
                    <div class="form-group">
                        <label>Approved By (Role)</label>
                        <input type="text" name="approved_by_role" value="Operation Manager">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelBillingStatement">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="file-text"></i>
                        Generate Statement
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
    lucide.createIcons();

    const openBtn    = document.getElementById('openRecordModal');
    const closeBtn   = document.getElementById('closeRecordModal');
    const cancelBtn  = document.getElementById('cancelRecordModal');
    const modal      = document.getElementById('recordPaymentModal');
    const recordForm = document.getElementById('recordPaymentForm');

    const stageWrap    = document.getElementById('stageSelectWrap');
    const stageTrigger = document.getElementById('stageSelectTrigger');
    const stageLabelEl = document.getElementById('stageSelectLabel');
    const stageSelect  = document.getElementById('stageSelect');
    const stageErr     = document.getElementById('stageSelectErr');

    function openModal()  { modal.classList.add('show');    document.body.style.overflow = 'hidden'; }
    function closeModal() { modal.classList.remove('show'); document.body.style.overflow = ''; }

    // openBtn (and the whole Record Payment section) only renders when the
    // payment isn't fully paid yet — guard so a fully-paid payment doesn't
    // throw here and block the rest of this script (billing statement modal included).
    const proofIdInput   = document.getElementById('recordProofIdInput');
    const proofLinkNote  = document.getElementById('recordProofLinkNote');
    function clearProofLink() { proofIdInput.value = ''; proofLinkNote.style.display = 'none'; }

    if (openBtn) {
        openBtn.addEventListener('click', function () { clearProofLink(); resetStageAndAmount(); openModal(); });
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    }

    // ── "Record Payment" on a client submission — opens the same modal, pre-filled
    // with that submission's stage and amount so admin just double-checks and saves. ──
    document.querySelectorAll('[data-record-proof]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal();
            var opt = document.querySelector('.stage-select-option[data-value="' + btn.dataset.stage + '"]');
            // A submission for a stage that has since closed (e.g. an unpaid Progress Payment once the
            // project reached final payment) is recorded against the stage that is open now.
            if (!opt || opt.classList.contains('disabled')) opt = document.querySelector('.stage-select-option:not(.disabled)');
            if (opt) selectStage(opt);
            // The fixed Down Payment already got its locked, correct amount from selectStage()
            // above — a partial-allowed stage (Progress / Final Payment) takes the amount the
            // client actually typed in their submission.
            if (opt && opt.dataset.partial === '1' && btn.dataset.amount) {
                var amountInput = document.getElementById('amountPaidInput');
                amountInput.value = btn.dataset.amount;
                formatMoneyInput(amountInput);
            }
            if (btn.dataset.mode) {
                var mopInput = document.getElementById({ bank_transfer: 'mop_bank', cheque: 'mop_cheque', cash: 'mop_cash' }[btn.dataset.mode]);
                if (mopInput) { mopInput.checked = true; highlightMop(); }
            }
            // Record it on the date the client said they paid (may be a past date)
            var dateInput = document.querySelector('input[name="payment_date"]');
            if (dateInput && btn.dataset.date) dateInput.value = btn.dataset.date;
            proofIdInput.value = btn.dataset.id;
            proofLinkNote.textContent = 'Recording the client\'s submission: ' + btn.dataset.summary;
            proofLinkNote.style.display = 'block';
        });
    });

    // ── Generate Billing Statement modal ──────────────────────────────────
    const billingBtn      = document.getElementById('generateBillingBtn');
    const billingModal    = document.getElementById('billingStatementModal');
    const closeBillingBtn = document.getElementById('closeBillingStatementModal');
    const cancelBillingBtn = document.getElementById('cancelBillingStatement');

    function openBillingModal()  { billingModal.classList.add('show');    document.body.style.overflow = 'hidden'; }
    function closeBillingModal() { billingModal.classList.remove('show'); document.body.style.overflow = ''; }

    if (billingBtn) {
        billingBtn.addEventListener('click', openBillingModal);
        closeBillingBtn.addEventListener('click', closeBillingModal);
        cancelBillingBtn.addEventListener('click', closeBillingModal);
        billingModal.addEventListener('click', e => { if (e.target === billingModal) closeBillingModal(); });
    }

    // ── Collection receipt upload — chip UI (mirrors the client "Request a
    // Quotation" reference-files upload: dropzone until something's picked,
    // then compact chips with a "+" tile to add more, capped at 5) ──
    (function () {
        var input     = document.getElementById('receiptFileInput');
        var dropzone  = document.getElementById('receiptDropzone');
        var list      = document.getElementById('receiptFilePreview');
        if (!input) return;

        var MAX_FILES = 5;
        var selected  = [];

        function formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        function syncInput() {
            var dt = new DataTransfer();
            selected.forEach(function (f) { dt.items.add(f); });
            input.files = dt.files;
        }

        function openPicker() {
            input.value = '';
            input.click();
        }

        function removeFile(index) {
            selected.splice(index, 1);
            syncInput();
            render();
        }

        function viewFile(index) {
            var f = selected[index];
            if (!f) return;
            window.open(URL.createObjectURL(f), '_blank');
        }

        function render() {
            dropzone.style.display = selected.length ? 'none' : '';
            list.style.display     = selected.length ? 'flex' : 'none';

            list.innerHTML = selected.map(function (f, i) {
                var isImage = f.type.indexOf('image/') === 0;
                var thumb   = isImage
                    ? '<img class="qr-file-thumb" src="' + URL.createObjectURL(f) + '" alt="">'
                    : '<span class="qr-file-thumb"><i data-lucide="file-text"></i></span>';
                return '<div class="qr-file-chip" data-index="' + i + '" title="Click to view">'
                    + thumb
                    + '<div class="qr-file-meta">'
                        + '<div class="qr-file-name" title="' + f.name.replace(/"/g, '&quot;') + '">' + f.name + '</div>'
                        + '<div class="qr-file-size">' + formatSize(f.size) + '</div>'
                    + '</div>'
                    + '<button type="button" class="qr-file-remove" data-index="' + i + '" title="Remove">'
                        + '<i data-lucide="x"></i>'
                    + '</button>'
                + '</div>';
            }).join('');

            if (selected.length && selected.length < MAX_FILES) {
                list.innerHTML += '<button type="button" class="qr-file-add" id="receiptFilesAddBtn" title="Add more">'
                    + '<i data-lucide="plus"></i></button>';
            }

            list.querySelectorAll('.qr-file-chip').forEach(function (chip) {
                chip.addEventListener('click', function (e) {
                    if (e.target.closest('.qr-file-remove')) return;
                    viewFile(Number(chip.dataset.index));
                });
            });
            list.querySelectorAll('.qr-file-remove').forEach(function (btn) {
                btn.addEventListener('click', function () { removeFile(Number(btn.dataset.index)); });
            });
            var addBtn = document.getElementById('receiptFilesAddBtn');
            if (addBtn) addBtn.addEventListener('click', openPicker);

            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        input.addEventListener('change', function () {
            var rejected = [];
            Array.from(input.files || []).forEach(function (f) {
                if (f.size > 10 * 1024 * 1024) { rejected.push(f.name); return; }
                var isDuplicate = selected.some(function (sf) {
                    return sf.name === f.name && sf.size === f.size && sf.lastModified === f.lastModified;
                });
                if (!isDuplicate && selected.length < MAX_FILES) selected.push(f);
            });
            syncInput();
            render();
            if (selected.length) {
                var err = document.getElementById('receiptFileErr');
                if (err) err.style.display = 'none';
            }
            if (rejected.length) showFileTooLargeModal(rejected.join(', '), 10);
        });
    })();

    stageTrigger.addEventListener('click', function (e) {
        e.stopPropagation();
        stageWrap.classList.toggle('open');
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#stageSelectWrap')) stageWrap.classList.remove('open');
    });

    function selectStage(el) {
        stageSelect.value = el.dataset.value;
        stageLabelEl.textContent = el.dataset.label;
        stageTrigger.classList.remove('placeholder');
        stageWrap.classList.remove('open');
        stageErr.style.display = 'none';
        applyStageAmountRule(el);
    }

    // ── The Down Payment is a fixed amount — locked to what's due. Progress and Final
    // Payment accept partial payments up to what's due; the Final Payment's due amount
    // includes any Progress Payment balance that was left unpaid. ──
    function applyStageAmountRule(el) {
        var amountInput = document.getElementById('amountPaidInput');
        var hint         = document.getElementById('amountHint');
        var remaining    = parseFloat(el.dataset.remaining || '0');
        var isPartial    = el.dataset.partial === '1';
        var percent      = el.dataset.percent;
        var remainingFmt = remaining.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        if (isPartial) {
            amountInput.readOnly = false;
            amountInput.classList.remove('amount-locked');
            amountInput.value = remaining > 0 ? remaining.toFixed(2) : '';
            formatMoneyInput(amountInput);
            var carry = parseFloat(el.dataset.carry || '0');
            hint.textContent = 'Partial payments allowed — up to ₱' + remainingFmt + ' remaining for this stage'
                + (carry > 0 ? ', including ₱' + carry.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' unpaid progress payment.' : '.')
                + (el.dataset.value === 'progress_payment' ? ' Anything paid above it is deducted from the Final Payment.' : '');
        } else {
            amountInput.readOnly = true;
            amountInput.classList.add('amount-locked');
            amountInput.value = remaining.toFixed(2);
            formatMoneyInput(amountInput);
            hint.textContent = 'Fixed amount' + (percent ? ' — ' + percent + '% of the contract' : '') + '. This stage must be paid in full.';
        }
    }

    function resetStageAndAmount() {
        stageSelect.value = '';
        stageLabelEl.textContent = 'Select stage';
        stageTrigger.classList.add('placeholder');
        var amountInput = document.getElementById('amountPaidInput');
        amountInput.readOnly = false;
        amountInput.classList.remove('amount-locked');
        amountInput.value = '';
        document.getElementById('amountHint').textContent = '';
    }

    // Live thousand-separator formatting for money/quantity text inputs —
    // keeps the raw comma-free value in a data attribute so it's easy to
    // strip back out right before the form actually submits.
    function formatMoneyInput(el) {
        var raw = el.value.replace(/[^0-9.]/g, '');
        var parts = raw.split('.');
        var intPart = parts[0].replace(/^0+(?=\d)/, '');
        var formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        var decPart = parts.length > 1 ? '.' + parts.slice(1).join('').slice(0, 2) : '';
        el.value = formattedInt + decPart;
    }

    function stripCommas(el) {
        if (el) el.value = el.value.replace(/,/g, '');
    }

    const receiptFileInput = document.getElementById('receiptFileInput');
    const receiptFileErr   = document.getElementById('receiptFileErr');
    const receiptDropzone  = document.getElementById('receiptDropzone');

    // OR / Serial Numbers already recorded on any payment (any project) — each one can only be used once.
    // Compared trimmed and case-insensitive, the same way the server checks it.
    var USED_OR_NUMBERS = @json(\App\Models\PaymentTransaction::whereNotNull('reference_number')
        ->pluck('reference_number')->map(fn ($n) => mb_strtolower(trim($n)))->filter()->values());
    var referenceInput = document.getElementById('referenceNumberInput');
    var referenceErr   = document.getElementById('referenceNumberErr');

    function isDuplicateOrNumber() {
        var dup = USED_OR_NUMBERS.indexOf(referenceInput.value.trim().toLowerCase()) !== -1;
        referenceErr.style.display = dup ? 'block' : 'none';
        referenceInput.style.borderColor = dup ? '#dc2626' : '';
        return dup;
    }
    referenceInput.addEventListener('input', isDuplicateOrNumber);

    recordForm.addEventListener('submit', function (e) {
        stripCommas(document.getElementById('amountPaidInput'));

        var invalid = false;

        if (isDuplicateOrNumber()) {
            e.preventDefault();
            referenceInput.scrollIntoView({ block: 'center', behavior: 'smooth' });
            referenceInput.focus();
            return;
        }

        if (!stageSelect.value) {
            invalid = true;
            stageErr.style.display = 'block';
        }

        if (!receiptFileInput.files || !receiptFileInput.files.length) {
            invalid = true;
            receiptFileErr.style.display = 'block';
        } else {
            receiptFileErr.style.display = 'none';
        }

        if (invalid) {
            e.preventDefault();
            (stageSelect.value ? receiptDropzone : stageTrigger).scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    });

    @if(session('success'))
    modal.classList.remove('show');
    @endif

    function highlightMop() {
        document.querySelectorAll('.mop-option').forEach(function(lbl) {
            var inp = lbl.querySelector('input[type=radio]');
            lbl.classList.toggle('mop-selected', inp && inp.checked);
        });
    }
    </script>

    <style>
        .stage-select { position: relative; width: 100%; }
        .stage-select-trigger {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 12px 14px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            background: var(--white);
            font-size: 14px;
            font-weight: 700;
            color: var(--dark);
            cursor: pointer;
            transition: border-color .15s;
            text-align: left;
        }
        .stage-select-trigger.placeholder { color: var(--muted); font-weight: 600; }
        .stage-select-trigger:hover { border-color: var(--dark); }
        .stage-select-trigger i { width: 16px; height: 16px; color: var(--muted); flex-shrink: 0; transition: transform .15s; }
        .stage-select.open .stage-select-trigger i { transform: rotate(180deg); }
        .stage-select-menu {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 12px 32px rgba(0,0,0,.14);
            z-index: 50;
            overflow: hidden;
            padding: 6px;
        }
        .stage-select.open .stage-select-menu { display: block; }
        .stage-select-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 9px;
            font-size: 13.5px;
            font-weight: 700;
            color: var(--dark);
            cursor: pointer;
        }
        .stage-select-option:hover { background: var(--cream-soft); }
        .stage-select-option.disabled { cursor: not-allowed; opacity: .75; }
        .stage-select-option.disabled:hover { background: none; }
        .stage-select-option .stage-select-status { font-size: 11.5px; font-weight: 800; white-space: nowrap; flex-shrink: 0; }

        #amountPaidInput.amount-locked {
            background: var(--cream-soft);
            color: var(--dark);
            cursor: not-allowed;
        }

        .mop-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 14px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            color: var(--muted);
            background: var(--white);
            transition: all .18s ease;
            user-select: none;
        }
        .mop-option:hover {
            border-color: var(--dark);
            color: var(--dark);
        }
        .mop-option.mop-selected {
            background: var(--dark);
            border-color: var(--dark);
            color: #fff;
            box-shadow: 0 4px 12px rgba(14,20,40,.25);
        }
        .pd-history-link { display: block; margin: 5px auto 0; padding: 0; border: none; background: none; font-size: 11.5px; font-weight: 700; color: var(--accent, #2563eb); cursor: pointer; text-decoration: underline; }
        .pd-rev { padding: 10px 12px; border: 1px solid var(--border); border-radius: 12px; margin-bottom: 8px; font-size: 12.5px; line-height: 1.6; color: var(--dark); }
        .pd-rev-head { display: flex; justify-content: space-between; gap: 10px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
        .pd-rev-reason { margin-top: 4px; padding: 6px 9px; border-radius: 8px; background: #fffbeb; color: #92400e; font-size: 12px; }
    </style>

    {{-- ===== RETURN A CLIENT SUBMISSION FOR CORRECTION ===== --}}
    <div class="modal-overlay" id="returnProofModal">
        <div class="modal-card" style="max-width:480px;">
            <div class="modal-header">
                <div>
                    <h2>Return for Correction</h2>
                    <p id="returnProofSummary">The client will be asked to correct and resubmit this payment.</p>
                </div>
                <button class="modal-close" type="button" data-close-return><i data-lucide="x"></i></button>
            </div>
            <form method="POST" id="returnProofForm">
                @csrf
                <div class="form-group">
                    <label>What needs to be corrected? <span style="color:#dc2626;">*</span></label>
                    <textarea name="correction_reason" rows="4" maxlength="1000" required style="resize:vertical;"
                              placeholder="e.g. The amount on the receipt is ₱45,000, not ₱50,000. Please check the amount and upload the correct receipt.">{{ old('correction_reason') }}</textarea>
                    @error('correction_reason')<div style="margin-top:6px;font-size:12px;font-weight:700;color:#b91c1c;">{{ $message }}</div>@enderror
                    <small style="display:block;margin-top:6px;font-size:12px;color:var(--muted);">The client sees this reason and can resubmit the same submission. Nothing is recorded as paid.</small>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" data-close-return>Cancel</button>
                    <button type="submit" class="save-btn" style="background:#b45309;border-color:#b45309;">
                        <i data-lucide="corner-up-left"></i> Return to Client
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== EARLIER VERSIONS OF A SUBMISSION (kept for the record) ===== --}}
    <div class="modal-overlay" id="proofHistoryModal">
        <div class="modal-card" style="max-width:520px;max-height:85vh;display:flex;flex-direction:column;">
            <div class="modal-header">
                <div>
                    <h2>Submission History</h2>
                    <p>Earlier versions the client replaced, oldest first.</p>
                </div>
                <button class="modal-close" type="button" data-close-history><i data-lucide="x"></i></button>
            </div>
            <div id="proofHistoryBody" style="overflow-y:auto;"></div>
        </div>
    </div>

    @php
        // earlier versions of each resubmitted submission, ready to show in the history modal
        $proofHistory = [];
        foreach ($proofs as $p) {
            foreach ($p->revisions ?? [] as $r) {
                $proofHistory[$p->id][] = [
                    'amount'   => '₱' . number_format((float) ($r['amount'] ?? 0), 2),
                    'mode'     => \App\Models\PaymentProof::modeLabel($r['mode_of_payment'] ?? null),
                    'file'     => $r['file_url'] ?? null,
                    'notes'    => $r['notes'] ?? null,
                    'reason'   => $r['correction_reason'] ?? null,
                    'sent'     => !empty($r['submitted_at']) ? \Carbon\Carbon::parse($r['submitted_at'])->format('M d, Y g:i A') : '—',
                    'replaced' => !empty($r['replaced_at']) ? \Carbon\Carbon::parse($r['replaced_at'])->format('M d, Y g:i A') : '—',
                ];
            }
        }
    @endphp
    <script>
    (function () {
        var PROOF_HISTORY = @json($proofHistory);

        function esc(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
        function show(m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
        function hide(m) { m.classList.remove('show'); document.body.style.overflow = ''; }

        var returnModal = document.getElementById('returnProofModal');
        document.querySelectorAll('[data-return-proof]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('returnProofForm').action = btn.dataset.action;
                document.getElementById('returnProofSummary').textContent = 'Returning ' + btn.dataset.summary + ' — the client will correct and resubmit it.';
                show(returnModal);
                returnModal.querySelector('textarea').focus();
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        });
        returnModal.querySelectorAll('[data-close-return]').forEach(function (b) { b.addEventListener('click', function () { hide(returnModal); }); });

        var historyModal = document.getElementById('proofHistoryModal');
        document.querySelectorAll('[data-proof-history]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var rows = PROOF_HISTORY[btn.dataset.proofHistory] || [];
                document.getElementById('proofHistoryBody').innerHTML = rows.map(function (r, i) {
                    return '<div class="pd-rev">' +
                        '<div class="pd-rev-head"><span>Version ' + (i + 1) + ' · sent ' + esc(r.sent) + '</span><span>replaced ' + esc(r.replaced) + '</span></div>' +
                        '<div><strong>' + esc(r.amount) + '</strong> · ' + esc(r.mode) +
                            (r.file ? ' · <a href="' + esc(r.file) + '" target="_blank" data-receipt>View file</a>' : ' · no file') + '</div>' +
                        (r.notes ? '<div style="color:var(--muted);">Note: ' + esc(r.notes) + '</div>' : '') +
                        (r.reason ? '<div class="pd-rev-reason">Returned: ' + esc(r.reason) + '</div>' : '') +
                    '</div>';
                }).join('');
                show(historyModal);
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        });
        historyModal.querySelectorAll('[data-close-history]').forEach(function (b) { b.addEventListener('click', function () { hide(historyModal); }); });

        [returnModal, historyModal].forEach(function (m) { m.addEventListener('click', function (e) { if (e.target === m) hide(m); }); });
    })();
    </script>
    @include('partials.receipt_viewer')

</body>
</html>
