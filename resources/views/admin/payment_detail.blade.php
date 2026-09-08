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
            @endphp

            <!-- Breadcrumb -->
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('admin.payments') }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    Payments
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <a href="{{ route('admin.payments.client', urlencode($payment->client)) }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    {{ $payment->client }}
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="color:var(--dark);font-weight:700;">{{ $payment->project->name ?? 'Payment Detail' }}</span>
            </div>

            <!-- Header -->
            <div class="page-header">
                <div>
                    <h1>{{ $payment->project->name ?? 'Payment Detail' }}</h1>
                    <p><span class="client-pill">{{ $payment->client }}</span> &nbsp;·&nbsp; {{ $payment->payment_terms }}</p>
                </div>
                <div style="display:flex;gap:10px;align-items:center;">
                    <button class="cancel-btn" type="button" id="generateBillingBtn">
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
            <div class="alert-banner success">
                <i data-lucide="check-circle"></i>
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="alert-banner error">
                <i data-lucide="alert-circle"></i>
                {{ session('error') }}
            </div>
            @endif

            <!-- Summary Cards -->
            <div class="page-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
                <div class="info-card teal">
                    <div class="info-card-icon teal"><i data-lucide="file-text"></i></div>
                    <h3>Contract Amount</h3>
                    <div class="value">₱{{ number_format($payment->contract_amount, 2) }}</div>
                    <div class="info-card-sub">{{ $payment->payment_terms }}</div>
                </div>
                <div class="info-card green">
                    <div class="info-card-icon green"><i data-lucide="check-circle"></i></div>
                    <h3>Total Paid</h3>
                    <div class="value">₱{{ number_format($totalPaid, 2) }}</div>
                    <div class="info-card-sub">
                        {{ round($payment->contract_amount > 0 ? ($totalPaid / $payment->contract_amount) * 100 : 0, 1) }}% of contract
                    </div>
                </div>
                <div class="info-card blue">
                    <div class="info-card-icon blue"><i data-lucide="alert-circle"></i></div>
                    <h3>Remaining Balance</h3>
                    <div class="value">₱{{ number_format($balance, 2) }}</div>
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

            <!-- Payment Stages Breakdown -->
            <div class="table-card" style="margin-bottom:24px;padding-bottom:0;">
                <div class="table-toolbar" style="padding-bottom:14px;margin-bottom:0;border-bottom:1px solid var(--border);">
                    <div>
                        <div style="font-weight:800;font-size:15px;color:var(--dark);">Payment Breakdown by Stage</div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;">Expected vs actual payments per stage</div>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Stage</th>
                                <th style="text-align:right;">Expected Amount</th>
                                <th style="text-align:right;">Total Paid</th>
                                <th style="text-align:right;">Remaining</th>
                                <th style="text-align:center;">Status</th>
                                <th style="text-align:center;">Client Proof</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payment->stages() as $stage)
                            @php
                                $expected    = $stageAmounts[$stage] ?? 0;
                                $stagePaid   = isset($stageTransactions[$stage]) ? $stageTransactions[$stage]->sum('amount_paid') : 0;
                                $stageLeft   = max(0, $expected - $stagePaid);
                                $isPaid      = in_array($stage, $paidStages);
                                $stageLabel  = \App\Models\PaymentTransaction::stageLabel($stage);
                                $stageProofs = $payment->proofs->where('payment_stage', $stage);
                                $stagePct    = $expected > 0 ? min(100, round(($stagePaid / $expected) * 100)) : 0;
                            @endphp
                            <tr>
                                <td><strong>{{ $stageLabel }}</strong></td>
                                <td style="text-align:right;font-weight:600;color:var(--muted);">₱{{ number_format($expected, 2) }}</td>
                                <td style="text-align:right;color:#16a34a;font-weight:800;">₱{{ number_format($stagePaid, 2) }}</td>
                                <td style="text-align:right;color:{{ $stageLeft > 0 ? '#b91c1c' : 'var(--muted)' }};font-weight:800;">₱{{ number_format($stageLeft, 2) }}</td>
                                <td style="text-align:center;">
                                    @if($isPaid)
                                        <span class="status-badge completed">Paid</span>
                                    @elseif($stagePaid > 0)
                                        <span class="status-badge partial">Partial</span>
                                    @else
                                        <span class="status-badge pending">Unpaid</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    @if($stageProofs->isNotEmpty())
                                    <div style="display:flex;flex-direction:column;gap:4px;align-items:center;">
                                        @foreach($stageProofs as $proof)
                                        <a href="{{ $proof->file_url }}" target="_blank" title="{{ $proof->notes ?? 'View submitted proof' }}"
                                           style="display:inline-flex;align-items:center;gap:4px;font-size:11.5px;font-weight:700;color:var(--accent);background:var(--accent-soft);border-radius:20px;padding:3px 10px;text-decoration:none;">
                                            <i data-lucide="file-check" style="width:12px;height:12px;"></i>
                                            {{ $proof->created_at->format('M d, Y') }}
                                        </a>
                                        @endforeach
                                    </div>
                                    @else
                                    <span style="color:var(--muted);">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background:linear-gradient(180deg,#333333 0%,#2a2a2a 100%)">
                                <td style="padding:16px 24px;font-weight:800;color:#fff;font-size:13.5px;">Total</td>
                                <td style="padding:16px 14px;text-align:right;color:rgba(255,255,255,.7);font-weight:700;">₱{{ number_format($payment->contract_amount, 2) }}</td>
                                <td style="padding:16px 14px;text-align:right;color:#4ade80;font-weight:800;">₱{{ number_format($totalPaid, 2) }}</td>
                                <td style="padding:16px 14px;text-align:right;color:{{ $balance > 0 ? '#f87171' : '#4ade80' }};font-weight:800;">₱{{ number_format($balance, 2) }}</td>
                                <td style="padding:16px 14px;text-align:center;">
                                    <span class="status-badge {{ \App\Models\Payment::statusBadgeClass($status) }}">{{ $status }}</span>
                                </td>
                                <td style="padding:16px 24px;"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

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
                                <td style="text-align:center;"><strong style="color:#16a34a;">₱{{ number_format($tx->amount_paid, 2) }}</strong></td>
                                <td style="text-align:center;">
                                    @if($tx->mode_of_payment)
                                    @php
                                        $mopStyle = match($tx->mode_of_payment) {
                                            'bank_transfer' => 'background:#dbeafe;color:#1d4ed8;',
                                            'cheque'        => 'background:#fef3c7;color:#92400e;',
                                            default         => 'background:#dcfce7;color:#15803d;',
                                        };
                                        $mopLabel = $tx->mode_of_payment === 'bank_transfer' ? 'Bank Transfer' : ucfirst($tx->mode_of_payment);
                                    @endphp
                                    <span class="status-badge" style="{{ $mopStyle }}box-shadow:none;font-size:12px;">{{ $mopLabel }}</span>
                                    @else
                                    <span style="color:var(--muted);">—</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    @php $receiptUrls = !empty($tx->receipt_urls) ? $tx->receipt_urls : array_filter([$tx->receipt_url]); @endphp
                                    @if(!empty($receiptUrls))
                                    <div style="display:inline-flex;gap:4px;">
                                        @foreach($receiptUrls as $i => $url)
                                        <a href="{{ $url }}" target="_blank" class="action-btn view" title="View Receipt {{ count($receiptUrls) > 1 ? $i + 1 : '' }}">
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
                                    $isPaid      = in_array($stage, $paidStages);
                                    $priorStages = array_slice($payment->stages(), 0, $i);
                                    $priorDone   = empty(array_diff($priorStages, $paidStages));
                                    $isLocked    = !$isPaid && !$priorDone;
                                    $isDisabled  = $isPaid || $isLocked;
                                    $stageLabel  = \App\Models\PaymentTransaction::stageLabel($stage) . ' (₱' . number_format($stageAmounts[$stage] ?? 0, 2) . ')';
                                @endphp
                                <div class="stage-select-option{{ $isDisabled ? ' disabled' : '' }}"
                                     data-value="{{ $stage }}"
                                     data-expected="{{ $stageAmounts[$stage] ?? 0 }}"
                                     data-label="{{ $stageLabel }}"
                                     @if(!$isDisabled) onclick="selectStage(this)" @endif>
                                    <span>{{ $stageLabel }}</span>
                                    @if($isPaid)
                                        <span class="stage-select-status" style="color:#16a34a;">✓ Paid</span>
                                    @elseif($isLocked)
                                        <span class="stage-select-status" style="color:#b91c1c;">Pay prior stage first</span>
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
                        <input type="number" name="amount_paid" id="amountPaidInput"
                               required min="0.01" step="0.01"
                               placeholder="e.g. 425000">
                    </div>
                    <div class="form-group">
                        <label>Payment Date</label>
                        <input type="date" name="payment_date" required
                               min="{{ now()->format('Y-m-d') }}"
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
                        <label>Upload Collection Receipt <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                        <label for="receiptFileInput" class="pv-upload-dropzone" id="receiptDropzone">
                            <i data-lucide="upload-cloud" style="width:22px;height:22px;color:var(--accent);"></i>
                            <span style="font-size:13px;font-weight:700;color:var(--text-primary);">Click to upload collection receipt(s)</span>
                            <span style="font-size:11px;color:var(--muted);">PDF or image, up to 5 files, max 10MB each</span>
                        </label>
                        <input type="file" name="receipt_files[]" id="receiptFileInput" accept=".pdf,image/*" multiple style="display:none;">
                        <div id="receiptFilePreview" class="qr-file-list" style="display:none;"></div>
                    </div>
                </div>

                <div style="background:var(--cream-soft);border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:13px;color:var(--muted);">
                    <strong style="color:var(--dark);">Expected for selected stage:</strong>
                    <span id="expectedAmount" style="font-weight:700;color:var(--accent);">—</span>
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
                                {{ \App\Models\PaymentTransaction::stageLabel($stage) }} (₱{{ number_format($stageAmounts[$stage] ?? 0, 2) }})@if(in_array($stage, $paidStages)) — Already Paid @endif
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
    const amountInput = document.getElementById('amountPaidInput');
    const expectedEl  = document.getElementById('expectedAmount');

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
    if (openBtn) {
        openBtn.addEventListener('click', openModal);
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    }

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
        const expected = parseFloat(el.dataset.expected || 0);

        stageSelect.value = el.dataset.value;
        stageLabelEl.textContent = el.dataset.label;
        stageTrigger.classList.remove('placeholder');
        stageWrap.classList.remove('open');
        stageErr.style.display = 'none';

        if (expected > 0) {
            expectedEl.textContent = '₱' + expected.toLocaleString('en-PH', { minimumFractionDigits: 2 });
            amountInput.value = expected.toFixed(2);
        } else {
            expectedEl.textContent = '—';
            amountInput.value = '';
        }
    }

    recordForm.addEventListener('submit', function (e) {
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
    </style>
</body>
</html>
