<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $clientName }} — Payments | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            @php
                $contractTotal = $payments->sum('contract_amount');
                $receivedTotal = $payments->sum(fn($p) => $p->totalPaid());
                $balanceTotal  = max(0, $contractTotal - $receivedTotal);
            @endphp

            <!-- Breadcrumb -->
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('admin.payments', ['tab' => $tab]) }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    Payments
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="color:var(--dark);font-weight:700;">{{ $clientName }}</span>
            </div>

            <div class="page-header">
                <div>
                    <h1>{{ $clientName }}</h1>
                    <p>Payments for this client's completed and archived projects. Active projects' payments are on the Payments page.</p>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="page-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
                <div class="info-card teal">
                    <div class="info-card-icon teal"><i data-lucide="receipt"></i></div>
                    <h3>Contract Value</h3>
                    <div class="value">₱{{ number_format($contractTotal, 0) }}</div>
                    <div class="info-card-sub">Across {{ $payments->count() }} {{ Str::plural('project', $payments->count()) }}</div>
                </div>
                <div class="info-card green">
                    <div class="info-card-icon green"><i data-lucide="trending-up"></i></div>
                    <h3>Received</h3>
                    <div class="value">₱{{ number_format($receivedTotal, 0) }}</div>
                    <div class="info-card-sub">All recorded payments</div>
                </div>
                <div class="info-card red">
                    <div class="info-card-icon red"><i data-lucide="alert-circle"></i></div>
                    <h3>Outstanding Balance</h3>
                    <div class="value">₱{{ number_format($balanceTotal, 0) }}</div>
                    <div class="info-card-sub">Remaining unpaid</div>
                </div>
            </div>

            <!-- Projects Table -->
            <div class="table-card">
                <div class="table-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="clientPaymentSearch" placeholder="Search project...">
                    </div>
                    <div class="filter-tabs" id="clientPaymentTabs">
                        <button type="button" class="filter-tab {{ $tab === 'completed' ? 'active' : '' }}" data-tab="completed">
                            Completed Projects
                            <span class="filter-count">{{ $payments->filter(fn ($p) => $p->project?->status === 'completed')->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab {{ $tab === 'archived' ? 'active' : '' }}" data-tab="archived">
                            Archived
                            <span class="filter-count">{{ $payments->filter(fn ($p) => $p->project?->status === 'archived')->count() }}</span>
                        </button>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="clientPaymentsTable">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Contract Amount</th>
                                <th>Balance</th>
                                <th>Payment Terms</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                            @php
                                $status  = $payment->computeStatus();
                                $balance = $payment->currentBalance();

                                $namePrefix = '';
                                $nameMain   = $payment->project->name ?? '—';
                                if ($payment->project && preg_match('/^(Fabrication of)\s+(.+)$/i', $payment->project->name, $nm)) {
                                    $namePrefix = $nm[1];
                                    $nameMain   = $nm[2];
                                }

                                $phases = '—';
                                if ($payment->payment_terms) {
                                    preg_match('/^(\d+)\s+phase/i', $payment->payment_terms, $pm);
                                    $phases = isset($pm[1]) ? $pm[1].' phases' : $payment->payment_terms;
                                }
                                $awaitingStage  = $payment->project ? $payment->project->awaitingPaymentStage() : null;
                                // due, but no billing statement sent yet — the client hasn't been asked to pay
                                $billingPending = $status !== 'Fully Paid' ? $payment->billingPendingStage() : null;
                            @endphp
                            <tr data-search="{{ strtolower($payment->project->name ?? '') }}" data-status="{{ $payment->project?->status }}"
                                class="{{ $awaitingStage ? 'row-needs-action' : '' }}">
                                <td style="overflow:hidden;">
                                    <span style="display:inline-flex;flex-direction:column;max-width:100%;min-width:0;">
                                        @if($namePrefix)
                                            <span style="font-size:9px;font-weight:700;color:var(--muted);letter-spacing:.05em;line-height:1.2;text-transform:uppercase;white-space:nowrap;">{{ $namePrefix }}</span>
                                        @endif
                                        <span style="font-size:12.5px;font-weight:800;color:var(--dark);line-height:1.3;white-space:normal;word-break:break-word;">{{ $nameMain }}</span>
                                        @if($awaitingStage)
                                        <span title="No payment recorded yet for {{ \App\Models\PaymentTransaction::stageLabel($awaitingStage) }}" style="display:inline-flex;align-items:center;gap:3px;margin-top:3px;font-size:9.5px;font-weight:800;color:#b45309;background:#fff3cd;border-radius:999px;padding:2px 7px;width:fit-content;">
                                            <i data-lucide="alert-triangle" style="width:9px;height:9px;"></i> Needs {{ \App\Models\PaymentTransaction::stageLabel($awaitingStage) }}
                                        </span>
                                        @endif
                                        @if($billingPending)
                                        <span title="{{ \App\Models\PaymentTransaction::stageLabel($billingPending) }} is due, but no billing statement has been sent — the client hasn't been asked to pay" style="display:inline-flex;align-items:center;gap:3px;margin-top:3px;font-size:9.5px;font-weight:800;color:#1e40af;background:#dbeafe;border-radius:999px;padding:2px 7px;width:fit-content;">
                                            <i data-lucide="file-clock" style="width:9px;height:9px;"></i> Billing not sent
                                        </span>
                                        @endif
                                    </span>
                                </td>
                                <td>₱{{ number_format($payment->contract_amount, 0) }}</td>
                                <td>₱{{ number_format($balance, 0) }}</td>
                                <td>{{ $phases }}</td>
                                <td>
                                    <span class="status-badge {{ \App\Models\Payment::statusBadgeClass($status) }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.payments.show', $payment->id) }}"
                                       class="action-btn view" title="View Breakdown &amp; History">
                                        <i data-lucide="eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="inbox" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;">No payment records for this client yet.</div>
                                </td>
                            </tr>
                            @endforelse
                            <tr id="clientPaymentEmptyRow" style="display:none;">
                                <td colspan="6" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="folder-open" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;" id="clientPaymentEmptyMsg">No projects match your search.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        var currentClientPaymentTab = @json($tab);

        function applyClientPaymentFilters() {
            var q = document.getElementById('clientPaymentSearch').value.toLowerCase();
            var visible = 0;
            document.querySelectorAll('#clientPaymentsTable tbody tr[data-search]').forEach(function (row) {
                var show = (!q || row.dataset.search.includes(q)) && row.dataset.status === currentClientPaymentTab;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            var emptyRow = document.getElementById('clientPaymentEmptyRow');
            if (emptyRow) emptyRow.style.display = visible === 0 ? '' : 'none';
            var emptyMsg = document.getElementById('clientPaymentEmptyMsg');
            if (emptyMsg) emptyMsg.textContent = q ? 'No projects match your search.'
                : (currentClientPaymentTab === 'archived' ? 'No archived projects.' : 'No completed projects.');
        }

        document.getElementById('clientPaymentSearch').addEventListener('input', applyClientPaymentFilters);
        document.querySelectorAll('#clientPaymentTabs .filter-tab').forEach(function (btn) {
            btn.addEventListener('click', function () {
                currentClientPaymentTab = this.dataset.tab;
                document.querySelectorAll('#clientPaymentTabs .filter-tab').forEach(function (b) { b.classList.toggle('active', b === btn); });
                applyClientPaymentFilters();
            });
        });
        applyClientPaymentFilters();

    </script>
</body>
</html>
