<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Detail | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
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
                    <h1 class="page-title">{{ $payment->project->name ?? 'Payment Detail' }}</h1>
                    <p class="page-subtitle">{{ $payment->payment_terms }} &nbsp;·&nbsp; Contract signed {{ $payment->date ? \Carbon\Carbon::parse($payment->date)->format('M d, Y') : '—' }}</p>
                </div>
                <span class="status-badge {{ \App\Models\Payment::statusBadgeClass($status) }}" style="font-size:13px;padding:8px 16px;">
                    {{ $status }}
                </span>
            </div>

            <!-- Summary Cards -->
            <div class="stats-grid">
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

            <!-- Overall Progress -->
            <div class="card">
                <div class="card-body">
                    <div class="progress-wrap" style="margin-top:0;">
                        <div class="progress-label">
                            <span>Overall Payment Progress</span>
                            <span style="font-weight:900;color:var(--dark);">{{ $pct }}% Paid</span>
                        </div>
                        <div class="progress-bar" style="height:12px;">
                            <div class="progress-fill"
                                 style="width:{{ $pct }}%;
                                 background:{{ $status === 'Fully Paid' ? 'var(--success)' : 'var(--accent)' }};"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stage Breakdown -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Payment Breakdown by Stage</div>
                </div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:14px;">
                    @foreach($payment->stages() as $stage)
                        @php
                            $expected   = $stageAmounts[$stage] ?? 0;
                            $stagePaid  = isset($stageTransactions[$stage]) ? $stageTransactions[$stage]->sum('amount_paid') : 0;
                            $stageLeft  = max(0, $expected - $stagePaid);
                            $isPaid     = in_array($stage, $paidStages);
                            $stageLabel = \App\Models\PaymentTransaction::stageLabel($stage);
                            $stagePct   = $expected > 0 ? min(100, round(($stagePaid / $expected) * 100, 1)) : 0;
                        @endphp
                        <div style="border:1px solid var(--border);border-radius:16px;padding:18px 20px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px;flex-wrap:wrap;">
                                <div style="font-weight:900;font-size:15px;color:var(--dark);">{{ $stageLabel }}</div>
                                @if($isPaid)
                                    <span class="status-badge completed">Paid</span>
                                @elseif($stagePaid > 0)
                                    <span class="status-badge ongoing">Partial</span>
                                @else
                                    <span class="status-badge pending">Unpaid</span>
                                @endif
                            </div>
                            <div class="progress-bar" style="height:8px;margin-bottom:14px;">
                                <div class="progress-fill"
                                     style="width:{{ $stagePct }}%;
                                     background:{{ $isPaid ? 'var(--success)' : 'var(--accent)' }};"></div>
                            </div>
                            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(130px, 1fr));gap:10px;">
                                <div class="info-mini">
                                    <div class="info-mini-label">Expected</div>
                                    <div class="info-mini-value">₱{{ number_format($expected, 2) }}</div>
                                </div>
                                <div class="info-mini">
                                    <div class="info-mini-label">Paid</div>
                                    <div class="info-mini-value" style="color:var(--success);">₱{{ number_format($stagePaid, 2) }}</div>
                                </div>
                                <div class="info-mini">
                                    <div class="info-mini-label">Remaining</div>
                                    <div class="info-mini-value" style="color:var(--danger);">₱{{ number_format($stageLeft, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Payment History -->
            <div class="card" style="overflow:hidden;">
                <div class="card-header">
                    <div class="card-title">Payment History</div>
                </div>
                @if($payment->transactions->isEmpty())
                    <div class="empty-state">
                        <i data-lucide="receipt" style="display:block;margin:0 auto;"></i>
                        <p>No payment transactions recorded yet.</p>
                    </div>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Stage</th>
                                    <th>Amount Paid</th>
                                    <th>Reference #</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payment->transactions->sortByDesc('payment_date') as $tx)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($tx->payment_date)->format('M d, Y') }}</td>
                                        <td>{{ \App\Models\PaymentTransaction::stageLabel($tx->payment_stage) }}</td>
                                        <td><strong style="color:var(--success);">₱{{ number_format($tx->amount_paid, 2) }}</strong></td>
                                        <td>{{ $tx->reference_number ?? '—' }}</td>
                                        <td>{{ $tx->notes ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/client.js') }}"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
