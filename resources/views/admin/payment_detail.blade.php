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
                @if($payment->project?->status === 'archived' || ($payment->project?->status === 'completed' && round($payment->currentBalance(), 2) <= 0))
                {{-- finished, fully paid projects' payments are listed on their client's page; active ones (and completed ones with a balance) on the Payments page itself --}}
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
                </div>
                <div style="display:flex;gap:10px;align-items:center;">
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

            {{-- The next phase to collect hasn't been billed yet: the client can't pay it (or upload proof)
                 until a billing statement is sent --}}
            @php
                $billingPending = $payment->billingPendingStage();
                $billingDraft   = $billingPending ? $payment->unsentStatementFor($billingPending) : null;
            @endphp
            @if($billingPending)
            <div class="pd-billing-pending">
                <div class="pd-billing-icon"><i data-lucide="file-clock"></i></div>
                <div class="pd-billing-body">
                    <div class="pd-billing-title">Next bill not sent yet — {{ \App\Models\PaymentTransaction::stageLabel($billingPending) }} (₱{{ number_format($payment->stageAmountsDue()[$billingPending] ?? 0, 2) }})</div>
                    <div class="pd-billing-text">
                        Send the billing statement when it's time to collect this phase — the client is notified and can pay only once it's sent.
                        @if($billingDraft)
                        <strong>A statement dated {{ $billingDraft->statement_date->format('M d, Y') }} is generated but not sent yet</strong> — review it, then press Send.
                        @endif
                    </div>
                </div>
                @if($billingDraft)
                {{-- generated, waiting to be sent: review it in the modal and press Send there
                     (ctrl/middle-click still opens the full statement page) --}}
                <a href="{{ route('admin.payments.billing_statements.show', [$payment->id, $billingDraft->id]) }}" class="save-btn" style="text-decoration:none;white-space:nowrap;"
                   @include('partials.admin.review_statement_attrs', ['statement' => $billingDraft])>
                    <i data-lucide="send"></i> Review &amp; Send
                </a>
                @else
                {{-- Opens the statement form on this phase; generating it opens the statement, where it's sent --}}
                <button type="button" class="save-btn" id="generateBillingBtn" style="white-space:nowrap;" onclick="var s = document.getElementById('billingStageSelect'); s.value = '{{ $billingPending }}'; s.dispatchEvent(new Event('change'));">
                    <i data-lucide="file-plus"></i> Generate Statement
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

            <!-- Summary Cards (same style as the Materials overview) -->
            <div class="pk-overview">
                <div class="pk-kpi">
                    <div class="pk-kpi-icon" style="background:#EAF0FF;color:#2A4EAA;"><i data-lucide="file-text"></i></div>
                    <div>
                        <div class="pk-kpi-label">Contract Amount</div>
                        <div class="pk-kpi-value">₱{{ number_format($payment->contract_amount, 2) }}</div>
                        <div class="pk-kpi-sub">{{ $payment->payment_terms }}</div>
                    </div>
                </div>
                <div class="pk-kpi">
                    <div class="pk-kpi-icon" style="background:#E7F6EC;color:#207A3A;"><i data-lucide="check-circle"></i></div>
                    <div>
                        <div class="pk-kpi-label">Total Paid</div>
                        <div class="pk-kpi-value">₱{{ number_format($totalPaid, 2) }}</div>
                        <div class="pk-kpi-sub"><strong class="pk-tone-ok">{{ round($payment->contract_amount > 0 ? ($totalPaid / $payment->contract_amount) * 100 : 0, 1) }}%</strong> of contract</div>
                    </div>
                </div>
                <div class="pk-kpi">
                    <div class="pk-kpi-icon" style="background:#FEE2E2;color:#B91C1C;"><i data-lucide="alert-circle"></i></div>
                    <div>
                        <div class="pk-kpi-label">Remaining Balance</div>
                        <div class="pk-kpi-value">₱{{ number_format($balance, 2) }}</div>
                        <div class="pk-kpi-sub">Still outstanding</div>
                    </div>
                </div>
                <div class="pk-kpi">
                    <div class="pk-kpi-icon" style="background:#F3E8FF;color:#7C3AED;"><i data-lucide="badge-check"></i></div>
                    <div style="min-width:0;">
                        <div class="pk-kpi-label">Status</div>
                        <div style="margin-top:5px;">
                            <span class="status-badge {{ \App\Models\Payment::statusBadgeClass($status) }}">{{ $status }}</span>
                        </div>
                        <div class="pk-kpi-sub">Payment progress</div>
                    </div>
                </div>
            </div>
            <style>
                /* ── Summary cards: same look as the Materials overview cards ── */
                .pk-overview { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:14px; margin:4px 0 24px; }
                .pk-kpi { display:flex; align-items:flex-start; gap:14px; padding:16px 18px; background:var(--white); border:1px solid var(--border); border-radius:16px; box-shadow:0 1px 2px rgba(0,0,0,.03); }
                .pk-kpi-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
                .pk-kpi-icon svg { width:20px; height:20px; }
                .pk-kpi-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); }
                .pk-kpi-value { margin-top:3px; font-size:20px; font-weight:900; color:var(--dark); letter-spacing:-.3px; font-variant-numeric:tabular-nums; white-space:nowrap; }
                .pk-kpi-sub { margin-top:4px; font-size:11.5px; font-weight:600; color:var(--muted); }
                .pk-tone-ok { color:#16a34a; }
                @media (max-width: 1100px) { .pk-overview { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
                @media (max-width: 640px) {
                    .pk-overview { gap:10px; }
                    .pk-kpi { padding:12px; }
                    .pk-kpi-icon { display:none; }
                    .pk-kpi-value { font-size:16px; }
                    .pk-kpi-label { font-size:10px; }
                }
            </style>

            @php
                // Online: only money that really came in — paid, or paid but flagged for review. Checkouts still
                // open, cancelled, expired or declined aren't payments, so they're left out.
                $onlinePayments = $onlinePayments->filter(fn ($op) => $op->status === 'paid'
                    || ($op->status === 'failed' && str_contains((string) $op->failure_reason, 'manual review')));
                // Proofs still waiting on GMD (not recorded, not sent back to the client)
                $proofsToReview = $proofs->filter(fn ($p) => $p->status !== 'confirmed'
                    && !in_array($p->payment_stage, $paidStages) && !$p->needsCorrection())->count();
                $onlineToCheck  = $onlinePayments->where('status', '!=', 'paid')->count();
                // open on whatever needs attention; otherwise on whichever list has entries
                // clients no longer upload proofs — that tab only stays for projects with older submissions
                $showProofTab = $proofs->isNotEmpty();
                $cpTab = $proofsToReview ? 'proofs'
                    : ($onlineToCheck || !$showProofTab ? 'online' : 'proofs');
            @endphp
            <!-- Client Payments: proof uploads (verified by GMD) | online payments (verified by PayMongo) -->
            <div class="table-card cp-card" style="margin-bottom:24px;" data-cp-tabs>
                <div class="table-toolbar" style="padding-bottom:0;margin-bottom:0;border-bottom:1px solid var(--border);flex-wrap:wrap;">
                    <div>
                        <div style="font-weight:800;font-size:15px;color:var(--dark);">Client Payments</div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;">Payments the client made online through PayMongo — verified automatically. Record cash and cheque payments with Record Payment.</div>
                    </div>
                    <div class="cp-tabs" role="tablist">
                        <button type="button" class="cp-tab {{ $cpTab === 'proofs' ? 'is-active' : '' }}" data-cp-tab="proofs" role="tab" @unless($showProofTab) hidden @endunless>
                            <i data-lucide="upload-cloud"></i> Proof Uploads
                            <span class="cp-count {{ $proofsToReview ? 'is-alert' : '' }}" title="{{ $proofsToReview ? $proofsToReview . ' waiting for your review' : '' }}">{{ $proofsToReview ?: $proofs->count() }}</span>
                        </button>
                        <button type="button" class="cp-tab {{ $cpTab === 'online' ? 'is-active' : '' }}" data-cp-tab="online" role="tab">
                            <i data-lucide="landmark"></i> Online (PayMongo)
                            <span class="cp-count {{ $onlineToCheck ? 'is-alert' : '' }}">{{ $onlinePayments->count() }}</span>
                        </button>
                    </div>
                </div>

                {{-- Proof uploads: the client paid outside the system and uploaded a receipt — verify, then record it --}}
                <div class="cp-panel" data-cp-panel="proofs" @if($cpTab !== 'proofs') hidden @endif>
                <div class="cp-panel-note">Proof of payment the client uploaded (bank transfer, cheque, cash) — verify it, then record it or return it for correction. {{ $proofs->count() }} submission{{ $proofs->count() !== 1 ? 's' : '' }}.</div>
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

                {{-- Online payments: paid through PayMongo — confirmed with the bank and recorded automatically --}}
                <div class="cp-panel" data-cp-panel="online" @if($cpTab !== 'online') hidden @endif>
                <div class="cp-panel-note">Paid through PayMongo — confirmed with the bank and recorded in Payment History automatically, nothing to verify. {{ $onlinePayments->where('status', 'paid')->count() }} paid · ₱{{ number_format($onlinePayments->where('status', 'paid')->sum('amount'), 2) }}.</div>
                @if($onlinePayments->isEmpty())
                <div style="text-align:center;padding:40px;color:var(--muted);">
                    <i data-lucide="landmark" style="width:32px;height:32px;opacity:.3;display:block;margin:0 auto 10px;"></i>
                    No online payments from the client yet.
                </div>
                @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Date</th>
                                <th style="text-align:left;">Stage</th>
                                <th style="text-align:center;">Amount</th>
                                <th style="text-align:center;">Method</th>
                                <th style="text-align:left;">Reference / Transaction</th>
                                <th style="text-align:center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($onlinePayments as $op)
                            <tr>
                                <td style="white-space:nowrap;color:var(--muted);">{{ ($op->paid_at ?? $op->created_at)->format('M d, Y') }}</td>
                                <td><strong>{{ \App\Models\PaymentTransaction::stageLabel($op->payment_stage) }}</strong></td>
                                <td style="text-align:center;"><strong>₱{{ number_format($op->amount, 2) }}</strong></td>
                                <td style="text-align:center;">{{ $op->methodLabel() }}</td>
                                <td style="font-size:12px;">
                                    <div style="font-family:monospace;">{{ $op->reference }}</div>
                                    @if($op->provider_payment_id)<div style="font-family:monospace;color:var(--muted);">{{ $op->provider_payment_id }}</div>@endif
                                    @if($op->failure_reason)<div style="color:#b45309;">{{ $op->failure_reason }}</div>@endif
                                </td>
                                <td style="text-align:center;"><span class="status-badge {{ $op->badgeClass() }}">{{ $op->statusLabel() }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
                </div>
            </div>
            <style>
                .cp-tabs { display:flex; gap:4px; align-self:flex-end; overflow-x:auto; scrollbar-width:none; }
                .cp-tabs::-webkit-scrollbar { display:none; }
                .cp-tab { display:inline-flex; align-items:center; gap:7px; padding:12px 14px 11px; margin-bottom:-1px; border:0; border-bottom:2px solid transparent; background:none; font:inherit; font-size:13px; font-weight:800; color:var(--muted); white-space:nowrap; cursor:pointer; }
                .cp-tab svg { width:15px; height:15px; }
                .cp-tab:hover { color:var(--dark); }
                .cp-tab.is-active { color:var(--dark); border-bottom-color:var(--dark); }
                .cp-count { min-width:20px; padding:1px 7px; border-radius:999px; background:var(--accent-soft, #f0f0f0); font-size:10.5px; font-weight:800; color:var(--muted); text-align:center; }
                .cp-tab.is-active .cp-count { background:var(--dark); color:#fff; }
                .cp-count.is-alert, .cp-tab.is-active .cp-count.is-alert { background:var(--danger, #B42318); color:#fff; }
                .cp-panel[hidden], .cp-tab[hidden] { display:none !important; }
                .cp-panel-note { padding:10px 20px; font-size:12px; color:var(--muted); background:var(--surface-2, #fafafa); border-bottom:1px solid var(--border); }
            </style>
            <script>
                // Client Payments tabs
                document.querySelectorAll('[data-cp-tabs]').forEach(function (card) {
                    card.querySelectorAll('.cp-tab').forEach(function (tab) {
                        tab.addEventListener('click', function () {
                            card.querySelectorAll('.cp-tab').forEach(function (t) { t.classList.toggle('is-active', t === tab); });
                            card.querySelectorAll('.cp-panel').forEach(function (p) { p.hidden = p.dataset.cpPanel !== tab.dataset.cpTab; });
                        });
                    });
                });
            </script>

            <!-- Payment History — switch between the recorded payments and the billing statements -->
            @php $statements = $payment->billingStatements()->get(); @endphp
            <div class="table-card" id="historyCard">
                {{-- same underlined tabs as the Client Payments card above --}}
                <div class="table-toolbar" style="padding-bottom:0;margin-bottom:0;border-bottom:1px solid var(--border);flex-wrap:wrap;">
                    <div>
                        <div style="font-weight:800;font-size:15px;color:var(--dark);" id="historyTitle">Payment History</div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;" id="historySub">All recorded transactions for this project</div>
                    </div>
                    <div class="cp-tabs" id="historyTabs" role="tablist">
                        <button type="button" class="cp-tab is-active" data-history="payments" role="tab">
                            <i data-lucide="receipt"></i> Payments
                            <span class="cp-count">{{ $payment->transactions->count() }}</span>
                        </button>
                        <button type="button" class="cp-tab" data-history="statements" role="tab">
                            <i data-lucide="file-text"></i> Billing Statements
                            <span class="cp-count">{{ $statements->count() }}</span>
                        </button>
                    </div>
                </div>
                <div class="table-wrapper" data-history-panel="payments">
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
                                    @elseif($tx->hasGeneratedReceipt())
                                    {{-- the system generates the Acknowledgement Receipt --}}
                                    <a href="{{ route('admin.payments.receipts.show', [$payment->id, $tx->id]) }}" target="_blank" data-receipt="page" class="action-btn view" title="Acknowledgement Receipt {{ $tx->receiptNumber() }}">
                                        <i data-lucide="receipt"></i>
                                    </a>
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

                {{-- Billing statements, newest first — each opens in the Review & Send modal (edit / print / send) --}}
                <div class="table-wrapper" data-history-panel="statements" hidden>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Date</th>
                                <th style="text-align:left;">Billing For</th>
                                <th style="text-align:left;">Subject / Ref.</th>
                                <th style="text-align:center;">Status</th>
                                <th style="text-align:center;">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($statements as $st)
                            <tr>
                                <td style="white-space:nowrap;color:var(--muted);">{{ $st->statement_date?->format('M d, Y') }}</td>
                                <td><strong>{{ $st->billing_stage ? \App\Models\PaymentTransaction::stageLabel($st->billing_stage) : 'Full Statement' }}</strong></td>
                                <td>
                                    <div style="font-weight:700;color:var(--dark);">{{ $st->subject ?: '—' }}</div>
                                    @if($st->reference_no)<div style="font-size:11.5px;color:var(--muted);">{{ $st->reference_no }}</div>@endif
                                </td>
                                <td style="text-align:center;">
                                    @if($st->sent_at)
                                    <span class="status-badge completed" title="Sent {{ $st->sent_at->format('M d, Y g:i A') }}">Sent {{ $st->sent_at->format('M d') }}</span>
                                    @else
                                    <span class="status-badge pending">Not sent yet</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    <a href="{{ route('admin.payments.billing_statements.show', [$payment->id, $st->id]) }}" class="action-btn view" title="Review statement"
                                       @include('partials.admin.review_statement_attrs', ['statement' => $st])>
                                        <i data-lucide="file-text"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="text-align:center;padding:40px;color:var(--muted);">
                                    <i data-lucide="file-text" style="width:32px;height:32px;opacity:.3;display:block;margin:0 auto 10px;"></i>
                                    No billing statements yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <style>[data-history-panel][hidden] { display: none !important; }</style>
            <script>
            // Payment History: Payments | Billing Statements
            (function () {
                var subs = { payments: 'All recorded transactions for this project', statements: 'Every billing statement for this project — open one to review, print or send it' };
                var titles = { payments: 'Payment History', statements: 'Billing Statements' };
                document.querySelectorAll('#historyTabs [data-history]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var key = btn.dataset.history;
                        document.querySelectorAll('#historyTabs [data-history]').forEach(function (b) { b.classList.toggle('is-active', b === btn); });
                        document.querySelectorAll('[data-history-panel]').forEach(function (p) { p.hidden = p.dataset.historyPanel !== key; });
                        document.getElementById('historyTitle').textContent = titles[key];
                        document.getElementById('historySub').textContent = subs[key];
                    });
                });
            })();
            </script>

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

            <form method="POST" action="{{ route('admin.payments.record', $payment->id) }}" id="recordPaymentForm">
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
                                {{-- Pays off the whole remaining contract balance at once — settles every unpaid stage --}}
                                <div class="stage-select-option{{ $balance > 0 ? '' : ' disabled' }}"
                                     data-value="full_payment"
                                     data-label="Full Payment"
                                     data-remaining="{{ $balance }}"
                                     data-partial="0"
                                     data-full="1"
                                     data-carry="0"
                                     @if($balance > 0) onclick="selectStage(this)" @endif
                                     style="border-top:1px solid var(--border);">
                                    <span>Full Payment</span>
                                    @if($balance > 0)
                                        <span class="stage-select-status" style="color:var(--muted);">₱{{ number_format($balance, 2) }}</span>
                                    @else
                                        <span class="stage-select-status" style="color:#16a34a;">✓ Paid</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <select name="payment_stage" id="stageSelect" style="display:none;">
                            <option value=""></option>
                            @foreach($payment->stages() as $stage)
                            <option value="{{ $stage }}"></option>
                            @endforeach
                            <option value="full_payment"></option>
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
                        {{-- bank payments go through Pay Online (PayMongo); GMD records what's handed over: cheque or cash --}}
                        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;" id="mopGroup">
                            <label class="mop-option" for="mop_cheque">
                                <input type="radio" name="mode_of_payment" id="mop_cheque" value="cheque" required style="display:none;" onchange="highlightMop()">
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
                        <label>OR / Serial Number <span style="font-weight:400;color:var(--muted);">(auto-generated)</span></label>
                        {{-- a random, unique number made by the system — not typed in --}}
                        <input type="text" name="reference_number" id="referenceNumberInput" required maxlength="100" readonly
                               value="{{ \App\Models\PaymentTransaction::generateOrNumber() }}"
                               style="font-family:monospace;font-weight:800;letter-spacing:.04em;background:var(--surface-2);cursor:default;" autocomplete="off">
                        <small id="referenceNumberErr" style="display:none;margin-top:6px;font-size:12px;font-weight:700;color:#dc2626;">
                            This OR / Serial Number is already used on another payment.
                        </small>
                    </div>

                    <div class="form-group form-group-full" style="display:flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600;color:var(--muted);">
                        <i data-lucide="receipt" style="width:15px;height:15px;flex-shrink:0;"></i>
                        A receipt is generated automatically when you record this payment.
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

    <!-- Generate / Edit Billing Statement Modal -->
    @php
        // ?edit_statement={id} (the statement page's Edit button) opens this form on that statement — any
        // statement, sent or not. Otherwise it generates a new one, starting from this project's last statement.
        $lastStatement = $payment->billingStatements()->latest('id')->first();
        $editStatement = request('edit_statement') ? $payment->billingStatements()->find(request('edit_statement')) : null;
        $bsSource      = $editStatement ?? $lastStatement;   // where carried-over details and signatures come from
        $bsValue       = fn (string $field, $default = '') => $editStatement ? ($editStatement->{$field} ?? '') : $default;
        // Signatories (name, role, e-signature) aren't per project: a new statement starts from the latest ones
        // saved on ANY statement, so an upload or change carries into every billing statement after it.
        $bsSignatory   = [
            'prepared_by' => $editStatement ?? \App\Models\BillingStatement::latestSignatory('prepared_by') ?? $lastStatement,
            'approved_by' => $editStatement ?? \App\Models\BillingStatement::latestSignatory('approved_by') ?? $lastStatement,
        ];
    @endphp
    <div class="modal-overlay" id="billingStatementModal" @if($editStatement) data-autoopen="1" @endif>
        <div class="modal-card" style="max-width:640px;max-height:90vh;overflow-y:auto;">
            <div class="modal-header">
                <div>
                    @if($editStatement)
                    <h2>Edit Billing Statement</h2>
                    <p>Update the statement details for <strong>{{ $payment->project->name ?? 'this project' }}</strong>{{ $editStatement->sent_at ? ' — it was already sent, so the client will see the updated version' : '' }}.</p>
                    @else
                    <h2>Generate Billing Statement</h2>
                    <p>Fill in the statement details for <strong>{{ $payment->project->name ?? 'this project' }}</strong> — you can review it and send it to the client next.</p>
                    @endif
                </div>
                <button class="modal-close" type="button" id="closeBillingStatementModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" id="billingStatementForm" enctype="multipart/form-data"
                  action="{{ $editStatement ? route('admin.payments.billing_statements.update', [$payment->id, $editStatement->id]) : route('admin.payments.billing_statements.store', $payment->id) }}">
                @csrf
                @if($editStatement) @method('PUT') @endif
                {{-- details that stay the same from bill to bill come from this project's last statement --}}
                <div class="form-grid">
                    <div class="form-group">
                        <label>Attention</label>
                        <input type="text" name="attention" placeholder="e.g. Mr. Juan Dela Cruz" value="{{ $bsSource->attention ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label>Bill To</label>
                        <input type="text" name="bill_to" value="{{ $bsSource?->bill_to ?: $payment->client }}">
                    </div>
                    <div class="form-group">
                        <label>Statement Date</label>
                        <input type="date" name="statement_date" required value="{{ $editStatement?->statement_date?->format('Y-m-d') ?? now()->format('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label>Reference No.</label>
                        <input type="text" name="reference_no" placeholder="e.g. BS-2026-001" value="{{ $bsValue('reference_no') }}">
                    </div>
                    <div class="form-group">
                        <label>Client TIN#</label>
                        <input type="text" name="tin_number" placeholder="e.g. 123-456-789-000" value="{{ $bsSource->tin_number ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label>Project Title</label>
                        <input type="text" name="project_title" value="{{ $bsValue('project_title', $payment->project->name ?? '') }}">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Project Location</label>
                        <input type="text" name="project_location" value="{{ $bsValue('project_location', $payment->project->address ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label>P.O. Number</label>
                        {{-- the Purchase Order the client sent with their approved quotation --}}
                        <input type="text" name="po_number" value="{{ $bsValue('po_number', $payment->project_id ? \App\Models\QuotationBatch::where('project_id', $payment->project_id)->whereNotNull('po_number')->latest('id')->value('po_number') : '') }}">
                    </div>
                    <div class="form-group">
                        <label>P.R. Number</label>
                        <input type="text" name="pr_number" value="{{ $bsValue('pr_number') }}">
                    </div>
                    <div class="form-group form-group-full">
                        {{-- Billing For starts on the phase that's due (the one in the "not sent" banner), and the
                             subject follows it — e.g. "Request for Final Billing" --}}
                        @php
                            $billingDefaultStage = $editStatement ? $editStatement->billing_stage : ($billingPending ?? $payment->openStage());
                            $billingSubjects = [
                                ''                 => 'Request for Billing',
                                'down_payment'     => 'Request for Down Payment Billing',
                                'progress_payment' => 'Request for Progress Billing',
                                'final_payment'    => 'Request for Final Billing',
                            ];
                        @endphp
                        <label>Subject</label>
                        <input type="text" name="subject" id="billingSubjectInput" value="{{ $bsValue('subject', $billingSubjects[$billingDefaultStage ?? ''] ?? $billingSubjects['']) }}" data-subjects='@json($billingSubjects)'>
                    </div>
                    <div class="form-group form-group-full">
                        <label>Billing For <span style="font-weight:400;color:var(--muted);">(which payment stage the client should pay now)</span></label>
                        <select name="billing_stage" id="billingStageSelect">
                            <option value="">Full Statement — show all stages</option>
                            @foreach($payment->stages() as $stage)
                            {{-- a paid stage can't be billed — except the one an edited statement already bills --}}
                            @php $stageLocked = in_array($stage, $paidStages) && $stage !== $editStatement?->billing_stage; @endphp
                            <option value="{{ $stage }}" @if($stageLocked) disabled @elseif($stage === $billingDefaultStage) selected @endif>
                                {{ \App\Models\PaymentTransaction::stageLabel($stage) }}@if(in_array($stage, $paidStages)) — Already Paid @endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form-group-full">
                        <label>Deposit Instructions</label>
                        <textarea name="deposit_instructions" rows="2" placeholder="e.g. Bank name, account name, account number...">{{ $bsSource->deposit_instructions ?? '' }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Prepared By (Name)</label>
                        <input type="text" name="prepared_by_name" value="{{ $bsSignatory['prepared_by']?->prepared_by_name ?: 'Bernabeth Maceda' }}">
                    </div>
                    <div class="form-group">
                        <label>Prepared By (Role)</label>
                        <input type="text" name="prepared_by_role" value="{{ $bsSignatory['prepared_by']?->prepared_by_role ?: 'Accounting Dept.' }}">
                    </div>
                    <div class="form-group">
                        <label>Approved By (Name)</label>
                        <input type="text" name="approved_by_name" value="{{ $bsSignatory['approved_by']?->approved_by_name ?: 'Vince Duetiz' }}">
                    </div>
                    <div class="form-group">
                        <label>Approved By (Role)</label>
                        <input type="text" name="approved_by_role" value="{{ $bsSignatory['approved_by']?->approved_by_role ?: 'Operation Manager' }}">
                    </div>
                    {{-- E-signatures (required): an image shown above each name on the statement. The current one —
                         or for a new statement, the latest one saved on any statement — is kept unless replaced. --}}
                    @foreach(['prepared_by' => 'Prepared By', 'approved_by' => 'Approved By'] as $who => $whoLabel)
                    {{-- editing: the statement's own signature; new: the most recently uploaded one --}}
                    @php $prevSig = ($editStatement?->{$who . '_signature'}) ?: \App\Models\BillingStatement::latestSignature($who); @endphp
                    <div class="form-group">
                        <label>{{ $whoLabel }} (E-Signature) <span style="color:#dc2626;">*</span></label>
                        <div class="sig-upload" data-sig="{{ $who }}">
                            <input type="hidden" name="keep_{{ $who }}_signature" value="{{ $prevSig ? 1 : 0 }}" class="sig-keep">
                            <input type="file" name="{{ $who }}_signature_file" accept="image/png,image/jpeg,image/webp" class="sig-file" id="sigFile_{{ $who }}" hidden>
                            <div class="sig-preview" @if(!$prevSig) style="display:none;" @endif>
                                <img src="{{ $prevSig }}" alt="{{ $whoLabel }} signature">
                                <button type="button" class="sig-remove" title="Remove signature"><i data-lucide="x"></i></button>
                            </div>
                            <label for="sigFile_{{ $who }}" class="sig-drop" @if($prevSig) style="display:none;" @endif>
                                <i data-lucide="pen-line"></i>
                                <span>Upload signature</span>
                                <small>PNG with a transparent background works best · max 2 MB</small>
                            </label>
                            <small class="sig-err">Please upload the {{ $whoLabel }} signature.</small>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelBillingStatement">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="{{ $editStatement ? 'save' : 'file-text' }}"></i>
                        {{ $editStatement ? 'Save Changes' : 'Generate Statement' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Review & Send Billing Statement Modal — the statement itself (embedded), with Edit / Print / Send -->
    <div class="modal-overlay" id="reviewStatementModal">
        <div class="modal-card rs-card">
            <div class="modal-header">
                <div>
                    <h2>Review Billing Statement</h2>
                    <p id="rsSubtitle"></p>
                </div>
                <button class="modal-close" type="button" id="closeReviewStatement">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="rs-frame-wrap">
                <iframe id="rsFrame" title="Billing statement"></iframe>
            </div>
            <div class="rs-actions">
                <a id="rsEdit" href="#" class="cancel-btn rs-btn"><i data-lucide="pencil"></i> Edit</a>
                <button type="button" id="rsPrint" class="cancel-btn rs-btn"><i data-lucide="printer"></i> Print / PDF</button>
                <span id="rsSent" class="rs-sent" style="display:none;"><i data-lucide="check-circle-2"></i> <span></span></span>
                <form method="POST" id="rsSendForm" style="display:none;">
                    @csrf
                    <input type="hidden" name="from" value="payment">
                    <button type="submit" class="save-btn rs-btn" id="rsSendBtn"><i data-lucide="send"></i> Send to Client</button>
                </form>
            </div>
        </div>
    </div>
    {{-- just generated / edited (?review_statement=id): open it in the modal straight away --}}
    @php $reviewStatement = request('review_statement') ? $payment->billingStatements()->find(request('review_statement')) : null; @endphp
    @if($reviewStatement)
    <span hidden data-review-autoopen @include('partials.admin.review_statement_attrs', ['statement' => $reviewStatement])></span>
    @endif
    <style>
        .rs-card { max-width: 960px; height: 92vh; max-height: 92vh; display: flex; flex-direction: column; overflow: hidden; }
        .rs-frame-wrap { flex: 1; min-height: 0; border: 1px solid var(--border); border-radius: 14px; overflow: hidden; background: #f3f3f3; }
        .rs-frame-wrap iframe { width: 100%; height: 100%; border: 0; display: block; background: #fff; }
        .rs-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; flex-wrap: wrap; padding-top: 14px; }
        .rs-actions form { margin: 0; }
        .rs-btn { text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .rs-sent { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 800; color: #16a34a; }
        .rs-sent svg { width: 15px; height: 15px; }
        .rs-actions .po-btn-spinner { display: inline-block; width: 14px; height: 14px; border-radius: 50%; border: 2px solid rgba(255,255,255,.35); border-top-color: #fff; animation: rsSpin .8s linear infinite; }
        @keyframes rsSpin { to { transform: rotate(360deg); } }
        @media (max-width: 768px) {
            .rs-card { height: 95vh; max-height: 95vh; }
            .rs-actions .rs-btn, .rs-actions form, .rs-sent { width: 100%; justify-content: center; }
        }
    </style>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
    lucide.createIcons();

    // ── Review & Send modal ──────────────────────────────────────────────
    (function () {
        var modal    = document.getElementById('reviewStatementModal');
        var frame    = document.getElementById('rsFrame');
        var sendForm = document.getElementById('rsSendForm');
        var sendBtn  = document.getElementById('rsSendBtn');
        var sent     = document.getElementById('rsSent');

        function open(el) {
            frame.src = el.dataset.reviewUrl;
            document.getElementById('rsSubtitle').textContent = el.dataset.title || '';
            document.getElementById('rsEdit').href = el.dataset.editUrl;
            // already sent: show when instead of the Send button
            sendForm.style.display = el.dataset.sent ? 'none' : '';
            sendForm.action = el.dataset.sendUrl;
            sent.style.display = el.dataset.sent ? '' : 'none';
            sent.querySelector('span').textContent = el.dataset.sent ? 'Sent ' + el.dataset.sent : '';
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        function close() {
            modal.classList.remove('show');
            document.body.style.overflow = '';
            frame.src = 'about:blank';
        }

        document.addEventListener('click', function (e) {
            var el = e.target.closest('[data-review-statement]');
            if (!el || e.ctrlKey || e.metaKey || e.shiftKey || e.button === 1) return;
            e.preventDefault();
            open(el);
        });
        document.getElementById('closeReviewStatement').addEventListener('click', close);
        modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal.classList.contains('show')) close(); });
        document.getElementById('rsPrint').addEventListener('click', function () {
            try { frame.contentWindow.focus(); frame.contentWindow.print(); } catch (err) { window.open(frame.src.replace(/[?&]embed=1/, ''), '_blank'); }
        });
        // one click sends once — the button locks while it goes
        sendForm.addEventListener('submit', function () {
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<span class="po-btn-spinner"></span> Sending…';
        });

        var auto = document.querySelector('[data-review-autoopen]');
        if (auto) {
            open(auto);
            // drop ?review_statement from the address so a refresh doesn't reopen it
            if (window.history.replaceState) {
                var url = new URL(window.location.href);
                url.searchParams.delete('review_statement');
                window.history.replaceState(null, '', url.toString());
            }
        }
    })();

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
        openBtn.addEventListener('click', function () {
            clearProofLink();
            resetStageAndAmount();
            // start on the stage that's open now, with its amount due filled in
            var openStage = document.querySelector('.stage-select-option:not(.disabled)');
            if (openStage) selectStage(openStage);
            openModal();
        });
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

    // Subject follows Billing For — unless the admin typed their own subject
    const billingStageSelect = document.getElementById('billingStageSelect');
    const billingSubject     = document.getElementById('billingSubjectInput');
    if (billingStageSelect && billingSubject) {
        const subjects = JSON.parse(billingSubject.dataset.subjects || '{}');
        const isAuto   = () => billingSubject.value.trim() === '' || Object.values(subjects).includes(billingSubject.value);
        billingStageSelect.addEventListener('change', () => {
            if (isAuto()) billingSubject.value = subjects[billingStageSelect.value] || subjects[''];
        });
    }

    // E-signature uploads: preview the picked image; removing it also stops the last statement's one being reused
    document.querySelectorAll('.sig-upload').forEach(function (box) {
        var file    = box.querySelector('.sig-file');
        var keep    = box.querySelector('.sig-keep');
        var preview = box.querySelector('.sig-preview');
        var drop    = box.querySelector('.sig-drop');
        var img     = preview.querySelector('img');

        function show(src) {
            img.src = src || '';
            preview.style.display = src ? '' : 'none';
            drop.style.display    = src ? 'none' : '';
            if (src) box.classList.remove('is-missing');
        }
        function pick(f) {
            if (!f || !/^image\/(png|jpe?g|webp)$/.test(f.type)) { alert('Please choose a PNG, JPG or WEBP image.'); file.value = ''; return; }
            if (f.size > 2 * 1024 * 1024) { alert('The signature image must be 2 MB or smaller.'); file.value = ''; return; }
            show(URL.createObjectURL(f));
        }

        file.addEventListener('change', function () { pick(file.files[0]); });
        box.querySelector('.sig-remove').addEventListener('click', function () {
            file.value = '';
            keep.value = '0';
            show('');
        });
        // drag & drop an image onto the box
        ['dragover', 'dragenter'].forEach(function (ev) {
            drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            drop.addEventListener(ev, function () { drop.classList.remove('is-over'); });
        });
        drop.addEventListener('drop', function (e) {
            e.preventDefault();
            var f = e.dataTransfer.files[0];
            if (!f) return;
            var dt = new DataTransfer();
            dt.items.add(f);
            file.files = dt.files;
            pick(f);
        });
    });

    // both e-signatures are required — flag an empty box instead of submitting
    document.getElementById('billingStatementForm').addEventListener('submit', function (e) {
        var missing = Array.prototype.filter.call(this.querySelectorAll('.sig-upload'), function (box) {
            var has = box.querySelector('.sig-file').files.length || box.querySelector('.sig-keep').value === '1';
            box.classList.toggle('is-missing', !has);
            return !has;
        });
        if (missing.length) {
            e.preventDefault();
            missing[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    if (billingBtn) billingBtn.addEventListener('click', openBillingModal);
    // the same form edits a statement (opened from its Edit button) — even when there's no Generate button
    closeBillingBtn.addEventListener('click', closeBillingModal);
    cancelBillingBtn.addEventListener('click', closeBillingModal);
    billingModal.addEventListener('click', e => { if (e.target === billingModal) closeBillingModal(); });
    if (billingModal.dataset.autoopen) openBillingModal();

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
            hint.textContent = el.dataset.full === '1'
                ? 'Fixed amount — the whole remaining contract balance. Settles every unpaid stage at once.'
                : 'Fixed amount' + (percent ? ' — ' + percent + '% of the contract' : '') + '. This stage must be paid in full.';
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

        if (isDuplicateOrNumber()) {
            e.preventDefault();
            referenceInput.scrollIntoView({ block: 'center', behavior: 'smooth' });
            referenceInput.focus();
            return;
        }

        if (!stageSelect.value) {
            e.preventDefault();
            stageErr.style.display = 'block';
            stageTrigger.scrollIntoView({ block: 'center', behavior: 'smooth' });
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

        /* Billing statement e-signature upload */
        /* .form-group .sig-drop — the drop box is a <label>, so it has to out-rank the form's own label style */
        .form-group .sig-drop { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; height: 96px; margin: 0; padding: 10px; border: 1.5px dashed var(--border); border-radius: 12px; background: var(--cream-soft); color: var(--dark); text-align: center; cursor: pointer; transition: border-color .15s ease; }
        .form-group .sig-drop:hover, .form-group .sig-drop.is-over { border-color: var(--dark); }
        .sig-drop svg { width: 18px; height: 18px; color: var(--muted); }
        .sig-drop span { font-size: 12.5px; font-weight: 800; }
        .sig-drop small { font-size: 10.5px; font-weight: 600; color: var(--muted); }
        .sig-upload.is-missing .sig-drop { border-color: #dc2626; background: #fef2f2; }
        .sig-err { display: none; margin-top: 6px; font-size: 12px; font-weight: 700; color: #dc2626; }
        .sig-upload.is-missing .sig-err { display: block; }
        .sig-preview { position: relative; display: flex; align-items: center; justify-content: center; height: 96px; padding: 8px; border: 1px solid var(--border); border-radius: 12px; background: #fff; }
        .sig-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .sig-remove { position: absolute; top: 6px; right: 6px; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border: none; border-radius: 50%; background: var(--dark); color: #fff; cursor: pointer; }
        .sig-remove svg { width: 13px; height: 13px; }
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
