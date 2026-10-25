<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Detail | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
    <style>
        /* Resubmitting a proof of payment */
        .pfm-reason { margin-top: 5px; max-width: 220px; font-size: 11.5px; line-height: 1.4; color: #92400e; white-space: normal; }
        .pfm-action { display: none; }
        .pfm-status-action { margin-top: 8px; }
        .pfm-resubmit-btn { display: inline-flex; align-items: center; gap: 5px; min-height: 34px; padding: 6px 12px; border-radius: 10px; border: 1.5px solid var(--border); background: var(--white); font-size: 12px; font-weight: 800; color: var(--dark); cursor: pointer; }
        .pfm-resubmit-btn:hover { border-color: var(--dark); }
        .pfm-resubmit-btn.is-urgent { background: #b45309; border-color: #b45309; color: #fff; }
        .rs-reason { margin-bottom: 14px; padding: 10px 12px; border-radius: 12px; background: #fffbeb; border: 1px solid #fde68a; font-size: 12.5px; line-height: 1.5; color: #92400e; }
        .rs-fixed { padding: 11px 14px; border-radius: 12px; background: var(--cream-soft, #f6f6f6); border: 1px solid var(--border); font-weight: 800; color: var(--dark); }
        .rs-file-note { margin-top: 6px; font-size: 11.5px; color: var(--muted); }
        @media (max-width: 640px) {
            .pf-proofs-table td.pfm-action { display: block; grid-column: 1 / -1; text-align: left; padding-top: 4px !important; }
            .pfm-status-action { display: none; }
            .pf-proofs-table td.pfm-action.is-empty { display: none !important; }
            .pfm-resubmit-btn { width: 100%; justify-content: center; min-height: 40px; }
            .pfm-reason { max-width: 150px; }
        }

        /* Phones: the cards' 380px minimum is wider than the screen, so they spilled off the right
           edge — stack them full width instead, and keep every field inside the card */
        @media (max-width: 640px) {
            .pd-two-col { grid-template-columns: minmax(0, 1fr) !important; }
            .pd-two-col > .card { min-width: 0; }
            .pd-two-col .form-grid { grid-template-columns: minmax(0, 1fr) !important; }
            .pd-two-col .form-group { min-width: 0; }
            .pd-two-col input, .pd-two-col select, .pd-two-col textarea { width: 100%; max-width: 100%; min-width: 0; }
            .pd-two-col label > span[style*="font-weight:400"] { display: block; font-size: 11.5px; margin-top: 2px; }
            #proofMopGroup { gap: 6px !important; }
            #proofMopGroup .mop-option { padding: 10px 6px; gap: 5px; font-size: 11.5px; justify-content: center; text-align: center; min-width: 0; }

            /* Submitted Proofs + Payment History: each entry is a small card —
               stage (+ status) on top, the amount large, then a thin line and small labelled
               columns split by vertical separators. No sideways scrolling. */
            .pf-proofs-table, .pf-proofs-table tbody,
            .ph-table, .ph-table tbody { display: block; width: 100%; min-width: 0 !important; }
            .pf-proofs-table thead, .ph-table thead { display: none; }
            .pf-proofs-table tr.pfm-row,
            .ph-table tr.ph-row {
                display: grid;
                gap: 4px 0;
                padding: 14px 18px 12px;
                border-bottom: 1px solid var(--border);
            }
            .pf-proofs-table tr.pfm-row { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .ph-table tr.ph-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .pf-proofs-table tr.pfm-row:last-child, .ph-table tr.ph-row:last-child { border-bottom: none; }
            .pf-proofs-table tr.pfm-row td, .ph-table tr.ph-row td { display: block; padding: 0 !important; border: none !important; min-width: 0; white-space: normal; }

            /* top: stage name (+ status badge for proofs) */
            .pf-proofs-table td.pfm-stage { grid-column: 1 / 3; grid-row: 1; align-self: center; }
            .ph-table td.ph-stage { grid-column: 1 / -1; grid-row: 1; align-self: center; }
            .pf-proofs-table td.pfm-stage, .ph-table td.ph-stage { font-size: 11px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
            .pf-proofs-table td.pfm-status { grid-column: 3; grid-row: 1 / 3; justify-self: end; align-self: center; }
            .pf-proofs-table td.pfm-status .status-badge { font-size: 10.5px; padding: 4px 10px; white-space: nowrap; }

            /* the amount, large */
            .pf-proofs-table td.pfm-amount { grid-column: 1 / 3; grid-row: 2; }
            .ph-table td.ph-amount { grid-column: 1 / -1; grid-row: 2; }
            .pf-proofs-table td.pfm-amount strong, .ph-table td.ph-amount strong { font-size: 19px; font-weight: 900; letter-spacing: -.3px; white-space: nowrap; }

            /* bottom: labelled details under a thin line, split by vertical separators */
            .pf-proofs-table td.pfm-date, .pf-proofs-table td.pfm-mode, .pf-proofs-table td.pfm-file,
            .ph-table td.ph-date, .ph-table td.ph-receipt {
                grid-row: 3;
                margin-top: 8px;
                padding: 9px 10px 0 !important;
                border-top: 1px dashed var(--border) !important;
                font-size: 12px; font-weight: 800; color: var(--dark);
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            }
            .pf-proofs-table td.pfm-date, .ph-table td.ph-date { grid-column: 1; padding-left: 0 !important; }
            .pf-proofs-table td.pfm-mode { grid-column: 2; border-left: 1px solid var(--border) !important; }
            .pf-proofs-table td.pfm-file { grid-column: 3; border-left: 1px solid var(--border) !important; }
            .ph-table td.ph-receipt { grid-column: 2; border-left: 1px solid var(--border) !important; }
            .pf-proofs-table td[data-label]::before, .ph-table td[data-label]::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 2px;
                font-size: 9px; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--muted);
            }
            .pf-proofs-table td.pfm-file a, .ph-table td.ph-receipt a { font-size: 12px; }
            .pf-proofs-table td.pfm-file svg, .ph-table td.ph-receipt svg { width: 13px !important; height: 13px !important; }
            .pf-proofs-table .pfm-file-word { display: inline; }
            .pf-proofs-table .pfm-nofile { font-weight: 700; }
            .ph-table td.ph-receipt [data-receipt-set] { flex-direction: row !important; gap: 8px !important; }

            /* payment notes: a soft box at the bottom, only when there are notes */
            .ph-table td.ph-notes { grid-column: 1 / -1; grid-row: 4; margin-top: 10px; font-size: 12px; color: var(--dark);
                                    background: var(--cream-soft); border-radius: 8px; padding: 7px 10px !important; }
            .ph-table td.ph-notes.is-empty { display: none; }

            /* room under the last card so the floating chat button doesn't cover it */
            main { padding-bottom: 92px !important; }

            /* the 4 summary cards sit 2 by 2 — icon on top, then the amount and label */
            .pd-stats { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; gap: 12px !important; }
            .pd-stats .stat-card { flex-direction: column; gap: 10px; padding: 16px 14px 16px 16px; border-radius: 18px; min-width: 0; }
            .pd-stats .stat-card::before { top: 16px; height: 34px; }
            .pd-stats .stat-icon { width: 36px; height: 36px; border-radius: 10px; }
            .pd-stats .stat-icon svg { width: 18px; height: 18px; }
            .pd-stats .stat-info { min-width: 0; width: 100%; }
            .pd-stats .stat-value { font-size: clamp(16px, 5vw, 22px) !important; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .pd-stats .stat-label { font-size: 11.5px; line-height: 1.3; }
        }

        .pfm-file-word { display: none; }

        /* Mode of Payment picker — mirrors the admin Record Payment modal's icon buttons */
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

        /* A missing/invalid field on submit gets a red outline instead of a text message. */
        #proofStageSelect.is-invalid,
        #proofAmountInput.is-invalid,
        #proofDropzone.is-invalid {
            border-color: #dc2626 !important;
        }
        #proofMopGroup.is-invalid .mop-option {
            border-color: #dc2626;
        }
        /* Down and final payments are fixed amounts — shown, but not editable */
        #proofAmountInput[readonly] {
            background: var(--surface-2);
            cursor: not-allowed;
        }

        /* This table sits right above a footer strip of its own (submission count/total), so
           the last row needs its bottom border back — otherwise it looks unclosed against the
           footer's border-top, especially once the card is stretched taller than the table. */
        .pf-proofs-table tbody tr:last-child td {
            border-bottom: 1px solid var(--border);
        }

        /* Matches the system header's own dark gradient + white text */
        .pf-header-dark {
            background: linear-gradient(135deg, var(--dark) 0%, var(--dark-deep) 100%);
            justify-content: center;
            border-radius: 22px 22px 0 0;
        }
        .pf-header-dark .card-title {
            color: var(--white);
        }
    </style>
</head>
<body class="page-enter">

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

            <div class="page-header" style="margin-bottom:24px;align-items:flex-start;">
                <div>
                    <h1 class="page-title" style="margin:0;">{{ $payment->project->name ?? 'Payment Detail' }}</h1>
                    <p class="page-subtitle pay-detail-meta">
                        @if($payment->project && $payment->project->tankItems->isNotEmpty())
                            <span class="pay-detail-meta-item">
                            @foreach($payment->project->tankItems as $ti)
                            <strong>{{ $ti->quantity }}×</strong> {{ $ti->tank_type }}@if($ti->capacity) ({{ $ti->capacity }})@endif{{ !$loop->last ? ', ' : '' }}
                            @endforeach
                            </span><span class="pay-detail-meta-sep"> &nbsp;·&nbsp; </span>
                        @endif
                        <span class="pay-detail-meta-item">{{ $payment->payment_terms }}</span><span class="pay-detail-meta-sep"> &nbsp;·&nbsp; </span><span class="pay-detail-meta-item">Contract signed {{ $payment->date ? \Carbon\Carbon::parse($payment->date)->format('M d, Y') : '—' }}</span>
                    </p>
                </div>
                <span class="status-badge {{ \App\Models\Payment::statusBadgeClass($status) }}" style="font-size:13px;padding:8px 16px;">
                    {{ $status }}
                </span>
            </div>

            <!-- Summary Cards -->
            <div class="stats-grid pd-stats">
                <div class="stat-card teal">
                    <div class="stat-icon teal"><i data-lucide="file-text"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱{{ number_format($payment->contract_amount, 0) }}</div>
                        <div class="stat-label">Contract Amount</div>
                    </div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon green"><i data-lucide="check-circle"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱{{ number_format($totalPaid, 0) }}</div>
                        <div class="stat-label">Total Paid</div>
                    </div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-icon blue"><i data-lucide="wallet"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱{{ number_format($balance, 0) }}</div>
                        <div class="stat-label">Remaining Balance</div>
                    </div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-icon orange"><i data-lucide="badge-check"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $pct }}%</div>
                        <div class="stat-label">Paid of Contract</div>
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
                $onlinePayments = $onlinePayments->whereIn('status', [\App\Models\OnlinePayment::STATUS_PAID, \App\Models\OnlinePayment::STATUS_PENDING])->values();
                // The stage payable now: billed = GMD sent its statement ("Pay Now"); not billed yet = "Pay in Advance"
                $payStage      = $payable['stage'] ?? null;
                $payBilled     = $payStage ? $payment->isStageBilled($payStage) : false;
                $proofInReview = $payStage && $payment->proofs
                    ->where('payment_stage', $payStage)
                    ->where('status', \App\Models\PaymentProof::STATUS_PENDING)
                    ->isNotEmpty();
                $canPayOnline  = $onlineEnabled && $payable && !$proofInReview
                    && $payable['amount'] >= \App\Services\OnlinePayments::MIN_AMOUNT;
                $poMethods     = collect(config('services.paymongo.payment_methods'))
                    ->map(fn ($m) => \App\Models\OnlinePayment::METHOD_LABELS[$m] ?? strtoupper($m));
                // proofs sent back for correction before uploads were retired — they can still be resubmitted
                $needsFixing   = $payment->proofs->filter(fn ($p) => $p->needsCorrection())->count();
            @endphp
            {{-- ===== Make a Payment: online through PayMongo. Cash and cheque are handed to GMD, who records them. ===== --}}
            <div class="card po-card">
                <div class="card-header pf-header-dark">
                    <div class="card-title">Make a Payment</div>
                </div>
                <div class="card-body po-body">

                    {{-- What to pay now (or ahead of time) --}}
                    @if($payable)
                    <div class="po-due {{ $payBilled ? 'is-due' : 'is-upcoming' }}">
                        <div class="po-due-info">
                            <div class="po-label">
                                @if($payBilled)
                                <i data-lucide="file-check-2"></i> Amount due now
                                @else
                                <i data-lucide="calendar-clock"></i> Upcoming bill
                                @endif
                            </div>
                            <div class="po-amount">₱{{ number_format($payable['amount'], 2) }}</div>
                            <div class="po-sub">
                                {{ \App\Models\PaymentTransaction::stageLabel($payStage) }} ·
                                {{ $payBilled ? 'Billed by GMD' : 'Not billed yet — you can settle it early' }}
                            </div>
                        </div>
                        @if($canPayOnline)
                        <form method="POST" action="{{ route('client.payments.online.pay', $payment->id) }}" class="po-form">
                            @csrf
                            <button type="submit" class="save-btn po-btn {{ $payBilled ? '' : 'is-advance' }}">
                                <i data-lucide="{{ $payBilled ? 'landmark' : 'fast-forward' }}"></i>
                                {{ $pendingOnline && $pendingOnline->payment_stage === $payStage ? 'Continue Payment' : ($payBilled ? 'Pay Now' : 'Pay in Advance') }}
                            </button>
                            <span class="po-secure"><i data-lucide="lock"></i> Secured by PayMongo</span>
                        </form>
                        @endif
                    </div>

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
                    @elseif(!$payBilled)
                    <p class="po-note"><i data-lucide="info"></i> Paying in advance is optional. GMD will still send the billing statement for this phase — it will simply show as paid.</p>
                    @endif
                    @else
                    <div class="po-done">
                        <i data-lucide="{{ $payment->currentBalance() <= 0 ? 'party-popper' : 'check-circle-2' }}"></i>
                        <span>{{ $payment->currentBalance() <= 0 ? 'This project is fully paid. Thank you!' : 'Nothing is due right now. The next payment opens when your project reaches its payment stage.' }}</span>
                    </div>
                    @endif

                    @if($pendingOnline)
                    <div class="alert-banner" style="margin:0;background:#fffbeb;border:1px solid #fde68a;color:#92400e;align-items:center;">
                        <i data-lucide="hourglass"></i>
                        <span style="flex:1;">An online payment of <strong>₱{{ number_format($pendingOnline->amount, 2) }}</strong> ({{ $pendingOnline->reference }}) is waiting for your bank's confirmation. If you completed it, it will be recorded automatically.</span>
                        <form method="POST" action="{{ route('client.payments.online.check', $pendingOnline->id) }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="cancel-btn" style="white-space:nowrap;">Check status</button>
                        </form>
                    </div>
                    @endif

                    @if($onlineEnabled)
                    <details class="po-how">
                        <summary><i data-lucide="help-circle"></i> How online payment works <i data-lucide="chevron-down" class="po-how-caret"></i></summary>
                        <div class="po-how-body">
                            <div class="po-methods">
                                @foreach($poMethods as $label)
                                <span class="po-method">{{ $label }}</span>
                                @endforeach
                            </div>
                            <ol class="po-steps">
                                <li>Press <strong>Pay Now</strong> (or <strong>Pay in Advance</strong>) — you'll go to PayMongo's secure payment page.</li>
                                <li>Choose how to pay: <strong>{{ $poMethods->join(', ', ' or ') }}</strong>.</li>
                                <li>Your payment is confirmed directly with the bank and recorded here automatically — no proof upload needed.</li>
                            </ol>
                            <p class="po-note">GMD never sees or stores your online banking login, PIN or OTP — you enter them only on your bank's or PayMongo's page.</p>
                        </div>
                    </details>
                    <p class="po-note"><i data-lucide="banknote"></i> Paying by cash or cheque instead? Hand it to GMD — they'll record it and it will appear in your Payment History. No upload needed.</p>
                    @endif
                </div>
            <style>
                .po-card { margin-bottom:20px; overflow:hidden; }

                /* Tabs inside a card (Make a Payment / Payment Records) */
                .pd-records { overflow:hidden; }
                .pd-tabs { display:flex; gap:4px; padding:6px 16px 0; border-bottom:1px solid var(--border); background:var(--white); overflow-x:auto; scrollbar-width:none; }
                .pd-tabs::-webkit-scrollbar { display:none; }
                .pd-tab { display:inline-flex; align-items:center; gap:7px; padding:13px 14px 12px; margin-bottom:-1px; border:0; border-bottom:2px solid transparent; background:none; font:inherit; font-size:13px; font-weight:800; color:var(--muted); white-space:nowrap; cursor:pointer; transition:color .15s ease, border-color .15s ease; }
                .pd-tab svg { width:15px; height:15px; }
                .pd-tab:hover { color:var(--dark); }
                .pd-tab.is-active { color:var(--dark); border-bottom-color:var(--dark); }
                .pd-count { min-width:20px; padding:1px 7px; border-radius:999px; background:var(--accent-soft); font-size:10.5px; font-weight:800; color:var(--muted); text-align:center; }
                .pd-tab.is-active .pd-count { background:var(--dark); color:#fff; }
                .pd-count.is-alert, .pd-tab.is-active .pd-count.is-alert { background:var(--danger); color:#fff; }
                .pd-panel[hidden] { display:none !important; }
                .pd-panel-foot { padding:12px 20px; border-top:1px solid var(--border); background:var(--surface-2); font-size:12px; font-weight:600; color:var(--muted); display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; }
                .po-body { display:flex; flex-direction:column; gap:16px; }

                /* The pay panel */
                .po-due { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; padding:18px 20px; border-radius:18px; background:var(--cream-soft); border:1px solid var(--border); border-left:4px solid var(--dark); }
                .po-due.is-upcoming { background:#f7f9ff; border-color:#dbe4fb; border-left-color:var(--info); }
                .po-label { display:flex; align-items:center; gap:6px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); }
                .po-due.is-upcoming .po-label { color:var(--info); }
                .po-label svg { width:14px; height:14px; }
                .po-amount { font-size:28px; font-weight:900; color:var(--dark); letter-spacing:-.5px; margin-top:2px; }
                .po-sub { font-size:12.5px; color:var(--muted); font-weight:600; }
                .po-form { display:flex; flex-direction:column; align-items:flex-end; gap:6px; margin:0; }
                .po-btn { padding:13px 28px; font-size:14.5px; }
                .po-btn.is-advance { background:var(--info); border-color:var(--info); }
                .po-btn.is-advance:hover { background:#203d88; }
                .po-secure { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; color:var(--muted); font-weight:700; }
                .po-secure svg { width:12px; height:12px; }
                .po-done { display:flex; align-items:center; gap:12px; padding:16px 18px; border-radius:16px; background:#E7F6EC; color:var(--success); font-size:13px; font-weight:700; }
                .po-done svg { width:22px; height:22px; flex-shrink:0; }
                .po-note { display:flex; align-items:flex-start; gap:6px; margin:0; font-size:12px; line-height:1.5; color:var(--muted); }
                .po-note svg { width:14px; height:14px; flex-shrink:0; margin-top:1px; }

                /* How it works (collapsed) */
                .po-how { border:1px solid var(--border); border-radius:14px; background:var(--white); }
                .po-how summary { display:flex; align-items:center; gap:8px; padding:12px 16px; cursor:pointer; list-style:none; font-size:13px; font-weight:800; color:var(--dark); }
                .po-how summary::-webkit-details-marker { display:none; }
                .po-how summary svg { width:15px; height:15px; }
                .po-how-caret { margin-left:auto; transition:transform .2s ease; }
                .po-how[open] .po-how-caret { transform:rotate(180deg); }
                .po-how-body { display:flex; flex-direction:column; gap:12px; padding:0 16px 16px; }
                .po-steps { margin:0; padding-left:20px; font-size:13px; line-height:1.7; color:var(--dark); }
                .po-methods { display:flex; flex-wrap:wrap; gap:6px; }
                .po-method { padding:5px 11px; border-radius:999px; border:1px solid var(--border); background:var(--cream-soft); font-size:11.5px; font-weight:800; color:var(--dark); }

                .po-section-title { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); margin-bottom:6px; }
                .po-table { width:100%; border-collapse:collapse; font-size:12.5px; }
                .po-table th { text-align:left; font-size:10.5px; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); padding:8px 10px; border-bottom:1px solid var(--border); }
                .po-table td { padding:9px 10px; border-bottom:1px solid var(--border); }

                @media (max-width:640px) {
                    .po-due { padding:16px; }
                    .po-amount { font-size:24px; }
                    .po-form { align-items:stretch; width:100%; }
                    .po-btn { justify-content:center; width:100%; }
                    .po-secure { justify-content:center; }
                    .po-table thead { display:none; }
                    .po-table tr { display:block; padding:8px 0; border-bottom:1px solid var(--border); }
                    .po-table td { display:flex; justify-content:space-between; gap:10px; border:none; padding:3px 0; }
                    .po-table td::before { content:attr(data-label); font-weight:700; color:var(--muted); }
                }
            </style>

            </div>

            {{-- ===== Payment Records: History | Billing Statements | Submitted Proofs | Online Payments ===== --}}
            @php
                $recordTabs = [
                    'history'    => ['Payment History',    'receipt',     $payment->transactions->count()],
                    'statements' => ['Billing Statements', 'file-text',   $payment->billingStatements->count()],
                    'proofs'     => ['Submitted Proofs',   'file-check',  $payment->proofs->count()],
                    'checkouts'  => ['Online Payments',    'credit-card', $onlinePayments->count()],
                ];
                // proof uploads are retired — the tab only stays for projects that still have older submissions
                if ($payment->proofs->isEmpty()) {
                    unset($recordTabs['proofs']);
                }
                // a returned proof is what the client most needs to see
                $recordTab = $needsFixing ? 'proofs' : 'history';
            @endphp
            <div class="card pd-records" data-tabs>
                <div class="card-header pf-header-dark">
                    <div class="card-title">Payment Records</div>
                </div>
                <div class="pd-tabs" role="tablist">
                    @foreach($recordTabs as $key => [$label, $icon, $count])
                    <button type="button" class="pd-tab {{ $recordTab === $key ? 'is-active' : '' }}" data-tab="{{ $key }}" role="tab">
                        <i data-lucide="{{ $icon }}"></i> {{ $label }}
                        <span class="pd-count {{ $key === 'proofs' && $needsFixing ? 'is-alert' : '' }}">{{ $count }}</span>
                    </button>
                    @endforeach
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
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Stage</th>
                                <th>Bill</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payment->billingStatements as $statement)
                            @php
                                $stmtStageLabel = $statement->billing_stage
                                    ? \App\Models\PaymentTransaction::stageLabel($statement->billing_stage)
                                    : 'Full Statement';
                            @endphp
                            <tr>
                                <td>{{ $statement->statement_date->format('M d, Y') }}</td>
                                <td>
                                    <span style="display:inline-flex;align-items:center;font-size:11px;font-weight:700;color:var(--accent);background:var(--accent-soft, rgba(0,0,0,.05));border-radius:20px;padding:3px 10px;">{{ $stmtStageLabel }}</span>
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
                                    <th>Notes</th>
                                    <th>Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payment->transactions->sortByDesc('payment_date') as $tx)
                                    <tr class="ph-row">
                                        <td class="ph-date" data-label="Date Paid">{{ \Carbon\Carbon::parse($tx->payment_date)->format('M d, Y') }}</td>
                                        <td class="ph-stage">{{ \App\Models\PaymentTransaction::stageLabel($tx->payment_stage) }}</td>
                                        <td class="ph-amount"><strong style="color:var(--success);">₱{{ number_format($tx->amount_paid, 2) }}</strong></td>
                                        <td class="ph-notes{{ $tx->notes ? '' : ' is-empty' }}">{{ $tx->notes ?? '—' }}</td>
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
                                            @elseif($tx->isOnline())
                                            {{-- paid online: the system generates the Acknowledgement Receipt --}}
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
                card.querySelectorAll('.pd-tab').forEach(function (t) { t.classList.toggle('is-active', t.dataset.tab === name); });
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
