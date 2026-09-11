<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        #paymentsTableWrapper {
            max-height: 800px;
            overflow-y: auto;
        }
        #paymentsTableWrapper thead th {
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .payments-view-tab {
            border: none;
            background: transparent;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
            padding: 8px 18px;
            border-radius: 999px;
            cursor: pointer;
            transition: 0.15s ease;
            font-family: inherit;
        }
        .payments-view-tab:hover { color: var(--dark); }
        .payments-view-tab.active { background: var(--dark); color: var(--white); }
    </style>
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">
            <div class="page-header">
                <div>
                    <h1>Payments</h1>
                    <p>Track contract amounts, payment stages, and billing per project.</p>
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
                    <div class="info-card-icon teal"><i data-lucide="receipt"></i></div>
                    <h3>Total Contract Value</h3>
                    <div class="value">₱{{ number_format($totalContractValue, 2) }}</div>
                    <div class="info-card-sub">Across all projects</div>
                </div>
                <div class="info-card green">
                    <div class="info-card-icon green"><i data-lucide="trending-up"></i></div>
                    <h3>Total Received</h3>
                    <div class="value">₱{{ number_format($totalReceived, 2) }}</div>
                    <div class="info-card-sub">All recorded payments</div>
                </div>
                <div class="info-card red">
                    <div class="info-card-icon red"><i data-lucide="alert-circle"></i></div>
                    <h3>Outstanding Balance</h3>
                    <div class="value">₱{{ number_format($outstanding, 2) }}</div>
                    <div class="info-card-sub">Remaining unpaid</div>
                </div>
                <div class="info-card purple">
                    <div class="info-card-icon purple"><i data-lucide="check-circle"></i></div>
                    <h3>Fully Paid</h3>
                    <div class="value">{{ $fullyPaid }}</div>
                    <div class="info-card-sub">{{ $inProgress }} in progress · {{ $pendingDown }} pending</div>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="table-card">
                <div style="padding:8px 20px 8px;">
                    <div style="display:inline-flex;background:var(--cream-soft);border:1px solid var(--border);border-radius:999px;padding:4px;gap:2px;">
                        <button type="button" class="payments-view-tab active" data-view="clients" onclick="switchPaymentsView('clients', this)">
                            Clients
                        </button>
                        <button type="button" class="payments-view-tab" data-view="receipts" onclick="switchPaymentsView('receipts', this)">
                            Receipts
                        </button>
                    </div>
                </div>

                <div id="clientsViewContent">
                <div class="table-toolbar" style="margin-top:12px;margin-bottom:14px;">
                    <div class="search-box" style="flex:1;max-width:none;">
                        <i data-lucide="search"></i>
                        <input type="text" id="paymentSearch" placeholder="Search client...">
                    </div>
                    <div class="filter-tabs" id="paymentFilterTabs">
                        <button type="button" class="filter-tab active" data-filter="">
                            All
                            <span class="filter-count">{{ $clientGroups->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="no_setup">
                            No Setup
                            <span class="filter-count">{{ $clientGroups->where('has_payments', false)->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="pending">
                            Pending
                            <span class="filter-count">{{ $clientGroups->where('has_pending', true)->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="in_progress">
                            In Progress
                            <span class="filter-count">{{ $clientGroups->where('has_in_progress', true)->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="fully_paid">
                            Fully Paid
                            <span class="filter-count">{{ $clientGroups->where('all_fully_paid', true)->count() }}</span>
                        </button>
                    </div>
                </div>

                <div class="table-wrapper" id="paymentsTableWrapper">
                    <table class="data-table" id="paymentsTable">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>No. of Projects</th>
                                <th>Contract Value</th>
                                <th>Received</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clientGroups as $group)
                            @php
                                $filterKey = !$group['has_payments'] ? 'no_setup' : ($group['all_fully_paid'] ? 'fully_paid' : ($group['has_pending'] ? 'pending' : ($group['has_in_progress'] ? 'in_progress' : '')));
                            @endphp
                            <tr data-status="{{ $filterKey }}"
                                data-search="{{ strtolower($group['client']) }}"
                                class="{{ $group['needs_settlement'] ? 'row-needs-action' : '' }}">
                                <td>
                                    <span class="client-pill">{{ $group['client'] }}</span>
                                    @if($group['needs_settlement'])
                                    <span title="A project for this client is waiting on a payment stage before it can proceed" style="display:inline-flex;align-items:center;gap:3px;margin-left:6px;font-size:10px;font-weight:800;color:#b45309;background:#fff3cd;border-radius:999px;padding:2px 8px;">
                                        <i data-lucide="alert-triangle" style="width:10px;height:10px;"></i> Needs Settlement
                                    </span>
                                    @endif
                                </td>
                                <td style="text-align:center;">{{ $group['project_count'] }}</td>
                                <td>₱{{ number_format($group['contract_total'], 2) }}</td>
                                <td>₱{{ number_format($group['received_total'], 2) }}</td>
                                <td>₱{{ number_format($group['balance_total'], 2) }}</td>
                                <td>
                                    @if(!$group['has_payments'])
                                    <span class="status-badge archived">No Setup</span>
                                    @elseif($group['all_fully_paid'])
                                    <span class="status-badge completed">Fully Paid</span>
                                    @elseif($group['has_pending'])
                                    <span class="status-badge pending">Pending</span>
                                    @elseif($group['has_in_progress'])
                                    <span class="status-badge ongoing">In Progress</span>
                                    @else
                                    <span class="status-badge pending">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.payments.client', urlencode($group['client'])) }}"
                                       class="action-btn view" title="View Client's Payments">
                                        <i data-lucide="eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="inbox" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;">No clients with active projects yet.</div>
                                    <div style="font-size:13px;margin-top:4px;">Clients appear here as soon as they have a project.</div>
                                </td>
                            </tr>
                            @endforelse
                            {{-- Filter empty state (shown by JS) --}}
                            @if($clientGroups->isNotEmpty())
                            <tr id="paymentEmptyRow" style="display:none;">
                                <td colspan="7" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="folder-open" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;" id="paymentEmptyMsg">No clients in this category.</div>
                                    <div style="font-size:13px;margin-top:4px;">Try switching to a different filter or check payment records.</div>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                </div>

                <!-- ===== RECEIPTS VIEW (static — real data/functionality coming next) ===== -->
                <div id="receiptsViewContent" style="display:none;">
                    <div class="table-toolbar" style="margin-top:12px;margin-bottom:14px;">
                        <div class="search-box" style="flex:1;max-width:none;">
                            <i data-lucide="hash"></i>
                            <input type="text" id="receiptSearch" placeholder="OR-2026-00">
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table class="data-table" id="receiptsTable">
                            <thead>
                                <tr>
                                    <th>OR Number</th>
                                    <th>Client</th>
                                    <th>Project</th>
                                    <th>Stage</th>
                                    <th>Amount</th>
                                    <th>Date Issued</th>
                                    <th>Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr data-search="or-2026-0044 alvin magtibay fuel storage tank">
                                    <td>OR-2026-0044</td>
                                    <td><span class="client-pill">Alvin Magtibay</span></td>
                                    <td>Fuel Storage Tank</td>
                                    <td>Final Payment</td>
                                    <td>₱82,000.00</td>
                                    <td>Oct 08, 2026</td>
                                    <td>
                                        <button type="button" class="action-btn view" title="View Receipt" onclick="alert('Receipt preview coming soon.')">
                                            <i data-lucide="eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr data-search="or-2026-0031 innovative agro storage silo repair">
                                    <td>OR-2026-0031</td>
                                    <td><span class="client-pill">Innovative Agro</span></td>
                                    <td>Storage Silo Repair</td>
                                    <td>Down Payment</td>
                                    <td>₱120,000.00</td>
                                    <td>Sep 22, 2026</td>
                                    <td>
                                        <button type="button" class="action-btn view" title="View Receipt" onclick="alert('Receipt preview coming soon.')">
                                            <i data-lucide="eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr data-search="or-2026-0018 tank inumin mo water tank coating">
                                    <td>OR-2026-0018</td>
                                    <td><span class="client-pill">Tank Inumin Mo</span></td>
                                    <td>Water Tank Coating</td>
                                    <td>Down Payment</td>
                                    <td>₱11,846.00</td>
                                    <td>Aug 03, 2026</td>
                                    <td>
                                        <button type="button" class="action-btn view" title="View Receipt" onclick="alert('Receipt preview coming soon.')">
                                            <i data-lucide="eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr data-search="or-2026-0006 innovative agro fabrication of polymer tanks">
                                    <td>OR-2026-0006</td>
                                    <td><span class="client-pill">Innovative Agro</span></td>
                                    <td>Fabrication of Polymer Tanks</td>
                                    <td>Down Payment</td>
                                    <td>₱175,000.00</td>
                                    <td>Jun 14, 2026</td>
                                    <td>
                                        <button type="button" class="action-btn view" title="View Receipt" onclick="alert('Receipt preview coming soon.')">
                                            <i data-lucide="eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr id="receiptEmptyRow" style="display:none;">
                                    <td colspan="7" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                        <i data-lucide="receipt" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                        <div style="font-size:14px;font-weight:700;">No receipts match your search.</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
    lucide.createIcons();

    // ── Clients / Receipts view switch ──────────────────────────────────────
    function switchPaymentsView(view, btn) {
        document.querySelectorAll('.payments-view-tab').forEach(function (t) { t.classList.remove('active'); });
        btn.classList.add('active');
        document.getElementById('clientsViewContent').style.display  = view === 'clients'  ? '' : 'none';
        document.getElementById('receiptsViewContent').style.display = view === 'receipts' ? '' : 'none';
    }

    // ── Receipts search (static demo data for now) ──────────────────────────
    var receiptSearchInput = document.getElementById('receiptSearch');
    if (receiptSearchInput) {
        receiptSearchInput.addEventListener('input', function () {
            var q = this.value.toLowerCase();
            var visibleCount = 0;
            document.querySelectorAll('#receiptsTable tbody tr[data-search]').forEach(function (row) {
                var show = !q || row.dataset.search.includes(q);
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
            var emptyRow = document.getElementById('receiptEmptyRow');
            if (emptyRow) emptyRow.style.display = visibleCount === 0 ? '' : 'none';
        });
    }

    // ── Table search / filter ──────────────────────────────────────────────
    var currentPaymentFilter = '';

    document.getElementById('paymentSearch').addEventListener('input', applyFilters);

    document.querySelectorAll('#paymentFilterTabs .filter-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#paymentFilterTabs .filter-tab').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentPaymentFilter = this.dataset.filter;
            applyFilters();
        });
    });

    var paymentFilterMessages = {
        '':            'No clients found.',
        'no_setup':    'Every client already has a payment setup.',
        'pending':     'No clients pending down payment.',
        'in_progress': 'No clients with payments in progress.',
        'fully_paid':  'No clients fully paid.'
    };

    function applyFilters() {
        var q            = document.getElementById('paymentSearch').value.toLowerCase();
        var status       = currentPaymentFilter.toLowerCase();
        var visibleCount = 0;

        document.querySelectorAll('#paymentsTable tbody tr[data-search]').forEach(function(row) {
            var matchSearch = !q || row.dataset.search.includes(q);
            var matchStatus = !status || row.dataset.status.toLowerCase() === status;
            var show = matchSearch && matchStatus;
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        var emptyRow = document.getElementById('paymentEmptyRow');
        var emptyMsg = document.getElementById('paymentEmptyMsg');
        if (emptyRow) {
            emptyRow.style.display = visibleCount === 0 ? '' : 'none';
            if (emptyMsg) emptyMsg.textContent = q
                ? 'No clients match "' + q + '".'
                : (paymentFilterMessages[status] || 'No clients in this category.');
            if (visibleCount === 0 && typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    </script>
</body>
</html>
