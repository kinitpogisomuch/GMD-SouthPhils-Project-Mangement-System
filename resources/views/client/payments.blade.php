<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
</head>
<body>

    @include('partials.client.header')

    <div class="admin-layout">
        @include('partials.client.sidebar')

        <main class="admin-content">

            <div class="page-header" style="margin-bottom:24px;">
                <div>
                    <h1 class="page-title">My Payments</h1>
                    <p class="page-subtitle">View your project payment status, balances, and transaction history.</p>
                </div>
            </div>

            @php
                $totalContractValue = $payments->sum('contract_amount');
                $totalReceived      = $payments->sum(fn($p) => $p->totalPaid());
                $totalBalance       = $payments->sum(fn($p) => $p->currentBalance());
            @endphp

            <div class="stats-grid">
                <div class="stat-card teal">
                    <div class="stat-icon teal"><i data-lucide="receipt"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱{{ number_format($totalContractValue, 0) }}</div>
                        <div class="stat-label">Total Contract Value</div>
                    </div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon green"><i data-lucide="check-circle"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱{{ number_format($totalReceived, 0) }}</div>
                        <div class="stat-label">Total Paid</div>
                    </div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-icon blue"><i data-lucide="wallet"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱{{ number_format($totalBalance, 0) }}</div>
                        <div class="stat-label">Remaining Balance</div>
                    </div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-icon orange"><i data-lucide="layers"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $payments->count() }}</div>
                        <div class="stat-label">Total Projects</div>
                    </div>
                </div>
            </div>

            @forelse($payments as $payment)
                @php
                    $status    = $payment->computeStatus();
                    $totalPaid = $payment->totalPaid();
                    $balance   = $payment->currentBalance();
                    $pct       = $payment->contract_amount > 0
                        ? round(($totalPaid / $payment->contract_amount) * 100, 1)
                        : 0;
                @endphp
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">{{ $payment->project->name ?? '—' }}</div>
                            <div style="font-size:12.5px;color:var(--muted);margin-top:4px;">
                                {{ $payment->payment_terms ?? '—' }}
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span class="status-badge {{ \App\Models\Payment::statusBadgeClass($status) }}">
                                {{ $status }}
                            </span>
                            <a href="{{ route('client.payments.show', $payment->id) }}" class="btn btn-sm btn-primary">
                                <i data-lucide="eye"></i> View Details
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="progress-wrap" style="margin-bottom:20px;">
                            <div class="progress-label">
                                <span>Payment Progress</span>
                                <span style="font-weight:900;color:var(--dark);">{{ $pct }}%</span>
                            </div>
                            <div class="progress-bar" style="height:10px;">
                                <div class="progress-fill"
                                     style="width:{{ $pct }}%;
                                     background:{{ $status === 'Fully Paid' ? 'var(--success)' : 'var(--accent)' }};"></div>
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(150px, 1fr));gap:12px;">
                            <div class="info-mini">
                                <div class="info-mini-label">Contract Amount</div>
                                <div class="info-mini-value">₱{{ number_format($payment->contract_amount, 2) }}</div>
                            </div>
                            <div class="info-mini">
                                <div class="info-mini-label">Total Paid</div>
                                <div class="info-mini-value" style="color:var(--success);">₱{{ number_format($totalPaid, 2) }}</div>
                            </div>
                            <div class="info-mini">
                                <div class="info-mini-label">Remaining Balance</div>
                                <div class="info-mini-value" style="color:var(--danger);">₱{{ number_format($balance, 2) }}</div>
                            </div>
                            <div class="info-mini">
                                <div class="info-mini-label">Payment Terms</div>
                                <div class="info-mini-value">{{ $payment->payment_terms ?? '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card" style="text-align:center;padding:64px 32px;">
                    <div style="margin-bottom:20px;">
                        <i data-lucide="receipt" style="width:56px;height:56px;color:var(--border);display:inline-block;"></i>
                    </div>
                    <h2 style="font-size:20px;font-weight:900;color:var(--dark);margin-bottom:10px;">No Payment Records</h2>
                    <p style="font-size:14px;color:var(--muted);max-width:420px;margin:0 auto;line-height:1.6;">
                        No payment records have been set up for your projects yet. Once your project payment terms are configured, they will appear here.
                    </p>
                </div>
            @endforelse

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/client.js') }}"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
