<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Detail | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
    <style>
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

            <div class="pd-two-col" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(380px, 1fr));gap:20px;align-items:stretch;margin-bottom:20px;">
            <!-- Upload Proof of Payment -->
            <div class="card">
                <div class="card-header pf-header-dark">
                    <div class="card-title">Upload Proof of Payment</div>
                </div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:20px;">
                    @php
                        $selectableStages = collect($payment->stagesOpenForProof());
                        // Every stage stays listed; only the one currently open can be chosen
                        $stageStates      = $payment->proofStageStates();
                        $stageStateLabels = [
                            'paid'    => ' — paid',
                            'carried' => ' — closed, balance added to Final Payment',
                            'locked'  => ' — locked',
                            'open'    => '',
                        ];
                        $amountsDue       = $payment->stageAmountsDue();
                        // Unpaid progress payment that has rolled into the final payment
                        $finalCarryOver   = max(0, round(($amountsDue['final_payment'] ?? 0) - ($stageAmounts['final_payment'] ?? 0), 2));
                    @endphp

                    @if($selectableStages->isEmpty())
                    <div style="text-align:center;padding:24px 16px;color:var(--muted);">
                        <i data-lucide="check-circle-2" style="width:28px;height:28px;opacity:.4;display:block;margin:0 auto 10px;"></i>
                        <p style="font-size:13.5px;font-weight:700;color:var(--dark);margin-bottom:4px;">All payment stages are settled</p>
                        <p style="font-size:12.5px;">There's nothing left to submit proof for on this project.</p>
                    </div>
                    @else
                    @if($errors->any())
                    <div class="alert-banner error" style="align-items:flex-start;">
                        <i data-lucide="alert-circle" style="margin-top:2px;"></i>
                        <div>@foreach($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
                    </div>
                    @endif
                    <form method="POST" action="{{ route('client.payments.proof.store', $payment->id) }}" enctype="multipart/form-data" id="proofUploadForm" novalidate>
                        @csrf
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Payment Stage <span style="color:#dc2626;">*</span></label>
                                <select name="payment_stage" id="proofStageSelect" required>
                                    @foreach($stageStates as $stage => $state)
                                    <option value="{{ $stage }}" data-due="{{ $amountsDue[$stage] ?? 0 }}" data-max="{{ $stage === 'progress_payment' ? ($amountsDue['final_payment'] ?? 0) : ($amountsDue[$stage] ?? 0) }}" data-carry="{{ $stage === 'final_payment' ? $finalCarryOver : 0 }}" {{ $state === 'open' ? 'selected' : 'disabled' }}>{{ \App\Models\PaymentTransaction::stageLabel($stage) }}{{ $stageStateLabels[$state] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Amount Paid (₱) <span style="color:#dc2626;">*</span></label>
                                <input type="text" inputmode="decimal" name="amount_paid" id="proofAmountInput"
                                       placeholder="e.g. 50,000" value="{{ old('amount_paid') }}">
                                <small id="proofAmountHint" style="display:none;margin-top:6px;font-size:10.5px;line-height:1.45;color:var(--muted);"></small>
                            </div>
                            <div class="form-group form-group-full">
                                <label>Date Paid <span style="color:#dc2626;">*</span>
                                    <span style="font-weight:400;color:var(--muted);">(set an earlier date for a past payment)</span>
                                </label>
                                <input type="date" name="submitted_date" required max="{{ now()->format('Y-m-d') }}"
                                       value="{{ old('submitted_date', now()->format('Y-m-d')) }}">
                            </div>
                            <div class="form-group form-group-full">
                                <label>Mode of Payment <span style="color:#dc2626;">*</span></label>
                                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;" id="proofMopGroup">
                                    <label class="mop-option" for="proofMopBank">
                                        <input type="radio" name="mode_of_payment" id="proofMopBank" value="bank_transfer" {{ old('mode_of_payment') === 'bank_transfer' ? 'checked' : '' }} required style="display:none;">
                                        <i data-lucide="building-2" style="width:16px;height:16px;"></i>
                                        Bank Transfer
                                    </label>
                                    <label class="mop-option" for="proofMopCheque">
                                        <input type="radio" name="mode_of_payment" id="proofMopCheque" value="cheque" {{ old('mode_of_payment') === 'cheque' ? 'checked' : '' }} style="display:none;">
                                        <i data-lucide="file-text" style="width:16px;height:16px;"></i>
                                        Cheque
                                    </label>
                                    <label class="mop-option" for="proofMopCash">
                                        <input type="radio" name="mode_of_payment" id="proofMopCash" value="cash" {{ old('mode_of_payment') === 'cash' ? 'checked' : '' }} style="display:none;">
                                        <i data-lucide="banknote" style="width:16px;height:16px;"></i>
                                        Cash
                                    </label>
                                </div>
                            </div>
                            <div class="form-group form-group-full">
                                <label>Attach Image / File <span id="proofFileReq" style="color:#dc2626;">*</span></label>
                                <label for="proofFileInput" class="qr-upload-dropzone" id="proofDropzone" style="margin-bottom:0;">
                                    <i data-lucide="file-plus" style="width:18px;height:18px;color:var(--accent);"></i>
                                    <span style="font-size:12px;font-weight:700;color:var(--dark);" id="proofDropzoneText">Click to upload receipt/screenshot</span>
                                    <span style="font-size:10.5px;color:var(--muted);">PDF or image, max 10MB</span>
                                </label>
                                <input type="file" name="proof_file" id="proofFileInput" accept=".pdf,image/*" style="display:none;">
                                <div id="proofFilePreview" class="qr-file-list" style="display:none;"></div>
                            </div>
                            <div class="form-group form-group-full">
                                <label>Note <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                                <textarea name="notes" rows="2" placeholder="e.g. reference number" style="resize:none;">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                        <button type="submit" class="save-btn" style="margin-top:14px;">
                            <i data-lucide="send" style="width:14px;height:14px;"></i>
                            Submit Proof
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            @if($payment->proofs->isNotEmpty())
            <!-- Submitted Proofs -->
            <div class="card" style="overflow:hidden;display:flex;flex-direction:column;">
                <div class="card-header pf-header-dark">
                    <div class="card-title">Submitted Proofs</div>
                </div>
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
                                    @else
                                        <span class="status-badge pending">Pending Review</span>
                                    @endif
                                </td>
                                <td class="pfm-date" data-label="Submitted">{{ $proof->created_at->format('M d, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Pinned to the bottom of the card (via margin-top:auto below) so a short list
                     doesn't just trail off into empty space under a table that stopped early. --}}
                <div style="margin-top:auto;padding:12px 20px;border-top:1px solid var(--border);background:var(--surface-2);font-size:12px;font-weight:600;color:var(--muted);display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                    <span>{{ $payment->proofs->count() }} submission{{ $payment->proofs->count() !== 1 ? 's' : '' }}</span>
                    <span>₱{{ number_format($payment->proofs->sum('amount'), 2) }} total submitted</span>
                </div>
            </div>
            @endif
            </div>

            <div class="pd-two-col" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(340px, 1fr));gap:20px;align-items:start;">
            @if($payment->billingStatements->isNotEmpty())
            <!-- Billing Statements -->
            <div class="card" style="overflow:hidden;">
                <div class="card-header pf-header-dark">
                    <div class="card-title">Billing Statements</div>
                </div>
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
                                    <a href="{{ route('client.payments.billing_statements.show', [$payment->id, $statement->id]) }}" style="display:inline-flex;align-items:center;gap:5px;color:var(--accent);font-weight:700;text-decoration:none;">
                                        <i data-lucide="file-text" style="width:14px;height:14px;"></i> View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Payment History -->
            <div class="card" style="overflow:hidden;">
                <div class="card-header pf-header-dark">
                    <div class="card-title">Payment History</div>
                </div>
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
            </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/client.js') }}"></script>
    <script>
        lucide.createIcons();

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
                // A screenshot is the proof for a bank/online transfer; cash and cheque are
                // often a physical handover with nothing to photograph, so it's optional there.
                return selectedMode() !== 'cheque' && selectedMode() !== 'cash';
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
                dropzoneText.textContent = required
                    ? 'Click to upload receipt/screenshot'
                    : 'Click to upload receipt/screenshot (optional for cash/cheque)';
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

</body>
</html>
