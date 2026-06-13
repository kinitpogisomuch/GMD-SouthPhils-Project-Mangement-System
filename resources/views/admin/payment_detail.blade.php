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

            <!-- Breadcrumb -->
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('admin.payments') }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    Payments
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="color:var(--dark);font-weight:700;">{{ $payment->project->name ?? 'Payment Detail' }}</span>
            </div>

            <!-- Header -->
            <div class="page-header">
                <div>
                    <h1>{{ $payment->project->name ?? 'Payment Detail' }}</h1>
                    <p>Client: <strong>{{ $payment->client }}</strong> &nbsp;·&nbsp; {{ $payment->payment_terms }}</p>
                </div>
                <button class="add-btn" type="button" id="openRecordModal">
                    <i data-lucide="plus"></i>
                    Record Payment
                </button>
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

            @php
                $status    = $payment->computeStatus();
                $totalPaid = $payment->totalPaid();
                $balance   = $payment->currentBalance();
            @endphp

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
            <div class="table-card" style="margin-bottom:24px;">
                <div class="table-toolbar" style="padding-bottom:0;">
                    <span style="font-weight:700;font-size:15px;">Payment Breakdown by Stage</span>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Stage</th>
                                <th>Expected Amount</th>
                                <th>Total Paid</th>
                                <th>Remaining</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payment->stages() as $stage)
                            @php
                                $expected   = $stageAmounts[$stage] ?? 0;
                                $stagePaid  = isset($stageTransactions[$stage]) ? $stageTransactions[$stage]->sum('amount_paid') : 0;
                                $stageLeft  = max(0, $expected - $stagePaid);
                                $isPaid     = in_array($stage, $paidStages);
                                $stageLabel = \App\Models\PaymentTransaction::stageLabel($stage);
                            @endphp
                            <tr>
                                <td><strong>{{ $stageLabel }}</strong></td>
                                <td>₱{{ number_format($expected, 2) }}</td>
                                <td>₱{{ number_format($stagePaid, 2) }}</td>
                                <td>₱{{ number_format($stageLeft, 2) }}</td>
                                <td>
                                    @if($isPaid)
                                        <span class="status-badge completed">Paid</span>
                                    @elseif($stagePaid > 0)
                                        <span class="status-badge ongoing">Partial</span>
                                    @else
                                        <span class="status-badge pending">Unpaid</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            <!-- Grand Total Row -->
                            <tr style="background:var(--dark);color:#fff;font-weight:700;">
                                <td>TOTAL</td>
                                <td>₱{{ number_format($payment->contract_amount, 2) }}</td>
                                <td>₱{{ number_format($totalPaid, 2) }}</td>
                                <td>₱{{ number_format($balance, 2) }}</td>
                                <td>
                                    <span class="status-badge {{ \App\Models\Payment::statusBadgeClass($status) }}">
                                        {{ $status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment History -->
            <div class="table-card">
                <div class="table-toolbar" style="padding-bottom:0;">
                    <span style="font-weight:700;font-size:15px;">Payment History</span>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Stage</th>
                                <th>Amount Paid</th>
                                <th>Reference #</th>
                                <th>Notes</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payment->transactions->sortByDesc('payment_date') as $tx)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($tx->payment_date)->format('M d, Y') }}</td>
                                <td>{{ \App\Models\PaymentTransaction::stageLabel($tx->payment_stage) }}</td>
                                <td><strong>₱{{ number_format($tx->amount_paid, 2) }}</strong></td>
                                <td>{{ $tx->reference_number ?? '—' }}</td>
                                <td>{{ $tx->notes ?? '—' }}</td>
                                <td>{{ $tx->recorded_by ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="text-align:center;padding:32px;color:var(--muted);">
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

            <form method="POST" action="{{ route('admin.payments.record', $payment->id) }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Payment Stage *</label>
                        <select name="payment_stage" required id="stageSelect">
                            <option value="">Select stage</option>
                            @foreach($payment->stages() as $stage)
                            <option value="{{ $stage }}"
                                data-expected="{{ $stageAmounts[$stage] ?? 0 }}"
                                {{ in_array($stage, $paidStages) ? 'disabled' : '' }}>
                                {{ \App\Models\PaymentTransaction::stageLabel($stage) }}
                                (₱{{ number_format($stageAmounts[$stage] ?? 0, 2) }})
                                {{ in_array($stage, $paidStages) ? '✓ Paid' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount Paid (₱) *</label>
                        <input type="number" name="amount_paid" id="amountPaidInput"
                               required min="0.01" step="0.01"
                               placeholder="e.g. 425000">
                    </div>
                    <div class="form-group">
                        <label>Payment Date *</label>
                        <input type="date" name="payment_date" required
                               value="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label>Reference Number</label>
                        <input type="text" name="reference_number"
                               placeholder="OR#, Check#, Transfer ref...">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Notes</label>
                        <textarea name="notes" rows="2"
                                  placeholder="Optional payment notes..."></textarea>
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

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
    lucide.createIcons();

    const openBtn    = document.getElementById('openRecordModal');
    const closeBtn   = document.getElementById('closeRecordModal');
    const cancelBtn  = document.getElementById('cancelRecordModal');
    const modal      = document.getElementById('recordPaymentModal');
    const stageSelect = document.getElementById('stageSelect');
    const amountInput = document.getElementById('amountPaidInput');
    const expectedEl  = document.getElementById('expectedAmount');

    openBtn.addEventListener('click', () => modal.classList.add('show'));
    closeBtn.addEventListener('click', () => modal.classList.remove('show'));
    cancelBtn.addEventListener('click', () => modal.classList.remove('show'));
    modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('show'); });

    stageSelect.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        const expected = parseFloat(opt.dataset.expected || 0);
        if (expected > 0) {
            expectedEl.textContent = '₱' + expected.toLocaleString('en-PH', { minimumFractionDigits: 2 });
            amountInput.value = expected.toFixed(2);
        } else {
            expectedEl.textContent = '—';
            amountInput.value = '';
        }
    });

    @if(session('success'))
    modal.classList.remove('show');
    @endif
    </script>
</body>
</html>
