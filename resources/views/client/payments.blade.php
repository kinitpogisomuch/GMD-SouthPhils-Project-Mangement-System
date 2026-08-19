<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.client.header')

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

            <div class="fd-overview" style="margin-bottom:24px;">
                <div class="fd-overview-title">
                    <i data-lucide="bar-chart-2"></i>
                    Payments Overview
                </div>
                <div class="fd-overview-grid">
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Contract Value</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Across all projects</span>
                        <span class="fd-ov-val">₱{{ number_format($totalContractValue, 2) }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Total Paid</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Amount received</span>
                        <span class="fd-ov-val" style="color:#4ade80;">₱{{ number_format($totalReceived, 2) }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Remaining Balance</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Still due</span>
                        <span class="fd-ov-val" style="color:{{ $totalBalance > 0 ? '#facc15' : 'rgba(255,255,255,0.35)' }};">
                            {{ $totalBalance > 0 ? '₱'.number_format($totalBalance, 2) : '—' }}
                        </span>
                    </div>
                    <div class="fd-ov-item fd-ov-highlight">
                        <span class="fd-ov-label">Total Projects</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">With payment records</span>
                        <span class="fd-ov-val">{{ $payments->count() }}</span>
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
                    $isBigProject   = $payment->payment_term_type === 'big_project';
                    $phasePercents  = $isBigProject ? ['50%', '30%', '20%'] : ['50%', '50%'];
                    $phaseTermsText = count($phasePercents) . ' Phases (' . implode(' / ', $phasePercents) . ')';
                @endphp
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">{{ $payment->project->name ?? '—' }}</div>
                            <div style="font-size:12.5px;color:var(--muted);margin-top:4px;">
                                {{ $phaseTermsText }}
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span class="status-badge {{ \App\Models\Payment::statusBadgeClass($status) }}">
                                {{ $status }}
                            </span>
                            <a href="{{ route('client.payments.show', $payment->id) }}" class="btn btn-outline btn-sm">
                                <i data-lucide="external-link"></i> View Details
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="display:flex;flex-wrap:wrap;align-items:center;">
                            <div class="project-info-item" style="padding-right:24px;">
                                <div class="project-info-label">Contract Amount</div>
                                <div class="project-info-value">₱{{ number_format($payment->contract_amount, 2) }}</div>
                            </div>
                            <div class="project-info-item" style="border-left:1px solid var(--border);padding:0 24px;">
                                <div class="project-info-label">Total Paid</div>
                                <div class="project-info-value" style="color:var(--success);">₱{{ number_format($totalPaid, 2) }}</div>
                            </div>
                            <div class="project-info-item" style="border-left:1px solid var(--border);padding:0 24px;">
                                <div class="project-info-label">Remaining Balance</div>
                                <div class="project-info-value" style="color:var(--danger);">₱{{ number_format($balance, 2) }}</div>
                            </div>
                            <div class="project-info-item" style="border-left:1px solid var(--border);padding:0 24px;">
                                <div class="project-info-label">Payment Terms</div>
                                <div class="project-info-value">{{ $phaseTermsText }}</div>
                            </div>
                            <div style="flex:1;min-width:180px;display:flex;align-items:center;gap:14px;margin-left:auto;border-left:1px solid var(--border);padding-left:24px;">
                                <span style="font-weight:700;font-size:13px;white-space:nowrap;">Payment Progress</span>
                                <div class="progress-bar" style="height:10px;flex:1;">
                                    <div class="progress-fill"
                                         style="width:{{ $pct }}%;
                                         background:{{ $status === 'Fully Paid' ? 'var(--success)' : 'var(--accent)' }};"></div>
                                </div>
                                <span style="font-weight:900;color:var(--dark);white-space:nowrap;">{{ $pct }}%</span>
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

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/client.js') }}"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
