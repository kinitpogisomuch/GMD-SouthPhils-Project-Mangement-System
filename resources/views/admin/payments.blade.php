<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        .pay-panel {
            max-height: 800px;
            overflow-y: auto;
        }
        .pay-panel thead th {
            position: sticky;
            top: 0;
            z-index: 1;
        }
        /* long client names wrap instead of pushing the Active table sideways */
        .pay-panel[data-panel="active"] .client-pill { white-space: normal; line-height: 1.35; }
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

        /* ── Receipts table: fits the card (no sideways scroll), long names wrap ── */
        #receiptsTableWrapper { overflow-x: hidden; max-height: 800px; overflow-y: auto; }
        #receiptsTableWrapper thead th { position: sticky; top: 0; z-index: 1; }
        #receiptsTable { table-layout: fixed; min-width: 0; width: 100%; }
        #receiptsTable td { vertical-align: middle; }
        #receiptsTable .client-pill { white-space: normal; line-height: 1.35; max-width: 100%; }
        .rc-or { display: inline-flex; align-items: center; gap: 6px; font-weight: 900; color: var(--dark); font-variant-numeric: tabular-nums; }
        .rc-or-tag { font-size: 9.5px; font-weight: 800; letter-spacing: .05em; color: var(--muted); background: var(--accent-soft); border-radius: 6px; padding: 2px 6px; }
        .rc-project { display: flex; flex-direction: column; min-width: 0; }
        .rc-project-prefix { font-size: 9px; font-weight: 700; color: var(--muted); letter-spacing: .05em; text-transform: uppercase; line-height: 1.2; }
        .rc-project-name { font-size: 12.5px; font-weight: 800; color: var(--dark); line-height: 1.3; word-break: break-word; }
        .rc-stage { display: inline-flex; align-items: center; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 999px; white-space: nowrap; }
        .rc-stage.down     { background: #EAF0FF; color: #2A4EAA; }
        .rc-stage.progress { background: #FEF3C7; color: #8A6100; }
        .rc-stage.final    { background: #E7F6EC; color: #207A3A; }
        .rc-amount { font-weight: 900; color: var(--dark); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .rc-date { color: var(--muted); font-weight: 700; white-space: nowrap; font-size: 12.5px; }
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
            {{-- Saved (e.g. payment recorded, billing statement sent): the small dark toast below the
                 header (same as "Salary record saved."), shown for 3 seconds --}}
            <div class="toast" id="paymentsToast" role="status">
                <i data-lucide="check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
            <script>
                window.addEventListener('load', function () {
                    var t = document.getElementById('paymentsToast');
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

            <!-- Summary Cards -->
            <div class="page-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
                <div class="info-card teal">
                    <div class="info-card-icon teal"><i data-lucide="receipt"></i></div>
                    <h3>Total Contract Value</h3>
                    <div class="value">₱{{ number_format($totalContractValue, 0) }}</div>
                    <div class="info-card-sub">Across all projects</div>
                </div>
                <div class="info-card green">
                    <div class="info-card-icon green"><i data-lucide="trending-up"></i></div>
                    <h3>Total Received</h3>
                    <div class="value">₱{{ number_format($totalReceived, 0) }}</div>
                    <div class="info-card-sub">All recorded payments</div>
                </div>
                <div class="info-card red">
                    <div class="info-card-icon red"><i data-lucide="alert-circle"></i></div>
                    <h3>Outstanding Balance</h3>
                    <div class="value">₱{{ number_format($outstanding, 0) }}</div>
                    <div class="info-card-sub">Remaining unpaid</div>
                </div>
                <div class="info-card purple">
                    <div class="info-card-icon purple"><i data-lucide="check-circle"></i></div>
                    <h3>Fully Paid</h3>
                    <div class="value">{{ $fullyPaid }}</div>
                    <div class="info-card-sub">{{ $inProgress }} in progress · {{ $pendingDown }} pending</div>
                </div>
            </div>

            <!-- Projects / Receipts switch (above the table card) -->
            <div style="margin-bottom:14px;">
                <div style="display:inline-flex;background:var(--white);border:1px solid var(--border);border-radius:999px;padding:4px;gap:2px;box-shadow:0 1px 2px rgba(0,0,0,.04);">
                    <button type="button" class="payments-view-tab active" data-view="clients" onclick="switchPaymentsView('clients', this)">
                        Projects
                    </button>
                    <button type="button" class="payments-view-tab" data-view="receipts" onclick="switchPaymentsView('receipts', this)">
                        Receipts
                    </button>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="table-card">

                {{-- Active: one row per in-progress project. Completed / Archived: grouped by client —
                     a client's page then lists those projects' payments. --}}
                <div id="clientsViewContent">
                <div class="table-toolbar" style="margin-bottom:14px;">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="paymentSearch" placeholder="{{ $tab === 'active' ? 'Search project or client...' : 'Search client...' }}">
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <select class="filter-select" id="paymentStatusSelect" title="Payment status" @if($tab !== 'active') style="display:none;" @endif>
                            <option value="">All statuses</option>
                            <option value="no_setup">No Setup ({{ $activeRows->where('filter', 'no_setup')->count() }})</option>
                            <option value="pending">Pending ({{ $activeRows->where('filter', 'pending')->count() }})</option>
                            <option value="in_progress">In Progress ({{ $activeRows->where('filter', 'in_progress')->count() }})</option>
                            <option value="fully_paid">Fully Paid ({{ $activeRows->where('filter', 'fully_paid')->count() }})</option>
                        </select>
                        <div class="filter-tabs" id="paymentListTabs">
                            <button type="button" class="filter-tab {{ $tab === 'active' ? 'active' : '' }}" data-tab="active">
                                Active
                                <span class="filter-count">{{ $activeRows->count() }}</span>
                            </button>
                            <button type="button" class="filter-tab {{ $tab === 'completed' ? 'active' : '' }}" data-tab="completed">
                                Completed
                                <span class="filter-count">{{ $completedGroups->sum('project_count') }}</span>
                            </button>
                            <button type="button" class="filter-tab {{ $tab === 'archived' ? 'active' : '' }}" data-tab="archived">
                                Archived
                                <span class="filter-count">{{ $archivedGroups->sum('project_count') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ── Active projects ── --}}
                <div class="table-wrapper pay-panel" data-panel="active" @if($tab !== 'active') style="display:none;" @endif>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Client</th>
                                <th>Contract Value</th>
                                <th>Received</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeRows as $row)
                            @php
                                $project    = $row['project'];
                                $namePrefix = '';
                                $nameMain   = $project->name;
                                if (preg_match('/^(Fabrication of)\s+(.+)$/i', $project->name, $nm)) {
                                    $namePrefix = $nm[1];
                                    $nameMain   = $nm[2];
                                }
                                // a payment record opens its breakdown; no setup yet → the project
                                $rowUrl = $row['payment'] ? route('admin.payments.show', $row['payment']->id) : url('/admin/project-view/' . $project->id);
                            @endphp
                            <tr data-search="{{ strtolower($project->name . ' ' . $project->live_client_name) }}"
                                data-status="{{ $row['filter'] }}"
                                data-href="{{ $rowUrl }}"
                                class="pay-row {{ $row['awaiting_stage'] ? 'row-needs-action' : '' }}">
                                <td class="td-wrap" style="min-width:200px;">
                                    <span style="display:inline-flex;flex-direction:column;max-width:100%;min-width:0;">
                                        @if($namePrefix)
                                            <span style="font-size:9px;font-weight:700;color:var(--muted);letter-spacing:.05em;line-height:1.2;text-transform:uppercase;white-space:nowrap;">{{ $namePrefix }}</span>
                                        @endif
                                        <span style="font-size:13px;font-weight:800;color:var(--dark);line-height:1.3;white-space:normal;word-break:break-word;">{{ $nameMain }}</span>
                                        @if($row['awaiting_stage'])
                                        <span title="No payment recorded yet for {{ \App\Models\PaymentTransaction::stageLabel($row['awaiting_stage']) }}" style="display:inline-flex;align-items:center;gap:3px;margin-top:4px;font-size:10px;font-weight:800;color:#b45309;background:#fff3cd;border-radius:999px;padding:2px 8px;width:fit-content;">
                                            <i data-lucide="alert-triangle" style="width:10px;height:10px;"></i> Needs {{ \App\Models\PaymentTransaction::stageLabel($row['awaiting_stage']) }}
                                        </span>
                                        @endif
                                        @if($row['billing_pending'])
                                        <span title="{{ \App\Models\PaymentTransaction::stageLabel($row['billing_pending']) }} is due, but no billing statement has been sent — the client hasn't been asked to pay" style="display:inline-flex;align-items:center;gap:3px;margin-top:4px;font-size:10px;font-weight:800;color:#1e40af;background:#dbeafe;border-radius:999px;padding:2px 8px;width:fit-content;">
                                            <i data-lucide="file-clock" style="width:10px;height:10px;"></i> Billing not sent
                                        </span>
                                        @endif
                                    </span>
                                </td>
                                <td class="td-wrap" style="min-width:150px;"><span class="client-pill">{{ $project->live_client_name }}</span></td>
                                @if($row['payment'])
                                <td>₱{{ number_format($row['contract'], 0) }}</td>
                                <td>₱{{ number_format($row['received'], 0) }}</td>
                                <td>₱{{ number_format($row['balance'], 0) }}</td>
                                <td><span class="status-badge {{ \App\Models\Payment::statusBadgeClass($row['status']) }}">{{ $row['status'] }}</span></td>
                                @else
                                <td style="color:var(--muted);">—</td>
                                <td style="color:var(--muted);">—</td>
                                <td style="color:var(--muted);">—</td>
                                <td><span class="status-badge archived">No Setup</span></td>
                                @endif
                                <td style="text-align:center;">
                                    <a href="{{ $rowUrl }}" class="action-btn view" title="{{ $row['payment'] ? 'View Breakdown & History' : 'Open Project (no payment setup yet)' }}">
                                        <i data-lucide="{{ $row['payment'] ? 'eye' : 'folder-open' }}"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="inbox" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;">No active projects.</div>
                                    <div style="font-size:13px;margin-top:4px;">A project's payments appear here as soon as it's created.</div>
                                </td>
                            </tr>
                            @endforelse
                            <tr class="pay-no-match" style="display:none;">
                                <td colspan="7" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="folder-open" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;" class="pay-no-match-msg">No projects match.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- ── Completed / Archived: one row per client ── --}}
                @foreach(['completed' => $completedGroups, 'archived' => $archivedGroups] as $panel => $groups)
                <div class="table-wrapper pay-panel" data-panel="{{ $panel }}" @if($tab !== $panel) style="display:none;" @endif>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th style="text-align:center;">Projects</th>
                                <th>Contract Value</th>
                                <th>Received</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($groups as $group)
                            @php $clientUrl = route('admin.payments.client', ['client' => $group['client_key'], 'tab' => $panel]); @endphp
                            <tr data-search="{{ strtolower($group['client']) }}" data-href="{{ $clientUrl }}"
                                class="pay-row {{ $group['needs_settlement'] ? 'row-needs-action' : '' }}">
                                <td>
                                    <span class="client-pill">{{ $group['client'] }}</span>
                                    @if($group['needs_settlement'])
                                    <span title="A completed project for this client is still waiting on a payment stage" style="display:inline-flex;align-items:center;gap:3px;margin-left:6px;font-size:10px;font-weight:800;color:#b45309;background:#fff3cd;border-radius:999px;padding:2px 8px;">
                                        <i data-lucide="alert-triangle" style="width:10px;height:10px;"></i> Needs Settlement
                                    </span>
                                    @endif
                                </td>
                                <td style="text-align:center;">{{ $group['project_count'] }}</td>
                                <td>₱{{ number_format($group['contract_total'], 0) }}</td>
                                <td>₱{{ number_format($group['received_total'], 0) }}</td>
                                <td>₱{{ number_format($group['balance_total'], 0) }}</td>
                                <td>
                                    @if($group['all_fully_paid'])
                                    <span class="status-badge completed">Fully Paid</span>
                                    @elseif($group['balance_total'] > 0)
                                    <span class="status-badge pending">Balance Due</span>
                                    @else
                                    <span class="status-badge archived">No Setup</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    <a href="{{ $clientUrl }}" class="action-btn view" title="View Client's Payments">
                                        <i data-lucide="eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="{{ $panel === 'completed' ? 'circle-check' : 'archive' }}" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;">{{ $panel === 'completed' ? 'No completed projects yet.' : 'No archived projects.' }}</div>
                                </td>
                            </tr>
                            @endforelse
                            <tr class="pay-no-match" style="display:none;">
                                <td colspan="7" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="search-x" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;" class="pay-no-match-msg">No clients match your search.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @endforeach
                </div>

                <!-- ===== RECEIPTS VIEW ===== -->
                <div id="receiptsViewContent" style="display:none;">
                    <div class="table-toolbar" style="margin-bottom:14px;">
                        <div class="search-box" style="flex:1;max-width:none;">
                            <i data-lucide="hash"></i>
                            <input type="text" id="receiptSearch" placeholder="OR-2026-00">
                        </div>
                    </div>

                    <div class="table-wrapper" id="receiptsTableWrapper">
                        <table class="data-table" id="receiptsTable">
                            <colgroup>
                                <col style="width:11%;">  {{-- OR Number --}}
                                <col style="width:20%;">  {{-- Client --}}
                                <col style="width:24%;">  {{-- Project --}}
                                <col style="width:13%;">  {{-- Stage --}}
                                <col style="width:11%;">  {{-- Amount --}}
                                <col style="width:12%;">  {{-- Date Issued --}}
                                <col style="width:9%;">   {{-- Receipt --}}
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>OR Number</th>
                                    <th>Client</th>
                                    <th>Project</th>
                                    <th>Stage</th>
                                    <th style="text-align:right;">Amount</th>
                                    <th style="text-align:center;">Date Issued</th>
                                    <th style="text-align:center;">Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($receipts as $r)
                                <tr data-search="{{ strtolower($r['or_number'] . ' ' . $r['client'] . ' ' . $r['project']) }}">
                                    @php
                                        // "Fabrication of X" → small prefix + X, like the Projects tables
                                        $rcPrefix = '';
                                        $rcName   = $r['project'];
                                        if (preg_match('/^(Fabrication of)\s+(.+)$/i', (string) $r['project'], $rcm)) {
                                            [$rcPrefix, $rcName] = [$rcm[1], $rcm[2]];
                                        }
                                        $rcStageClass = str_contains(strtolower($r['stage']), 'down') ? 'down'
                                            : (str_contains(strtolower($r['stage']), 'progress') ? 'progress' : 'final');
                                    @endphp
                                    <td>
                                        @if($r['or_number'])
                                        <span class="rc-or"><span class="rc-or-tag">{{ $r['or_tag'] ?? 'OR' }}</span>{{ $r['or_number'] }}</span>
                                        @else
                                        {{-- historical payment — no OR number on record --}}
                                        <span style="color:var(--muted);">—</span>
                                        @endif
                                    </td>
                                    <td><span class="client-pill">{{ $r['client'] }}</span></td>
                                    <td>
                                        <div class="rc-project">
                                            @if($rcPrefix)<span class="rc-project-prefix">{{ $rcPrefix }}</span>@endif
                                            <span class="rc-project-name">{{ $rcName }}</span>
                                        </div>
                                    </td>
                                    <td><span class="rc-stage {{ $rcStageClass }}">{{ $r['stage'] }}</span></td>
                                    <td style="text-align:right;"><span class="rc-amount">₱{{ number_format($r['amount'], 0) }}</span></td>
                                    <td style="text-align:center;"><span class="rc-date">{{ \Carbon\Carbon::parse($r['date_issued'])->format('M d, Y') }}</span></td>
                                    <td style="text-align:center;">
                                        @if(!empty($r['receipt_urls']))
                                        <div style="display:inline-flex;gap:4px;" data-receipt-set>
                                            @foreach($r['receipt_urls'] as $i => $url)
                                            <a href="{{ $url }}" target="_blank" data-receipt class="action-btn view" title="View Receipt {{ count($r['receipt_urls']) > 1 ? $i + 1 : '' }}">
                                                <i data-lucide="eye"></i>
                                            </a>
                                            @endforeach
                                        </div>
                                        @elseif(!empty($r['ar_url']))
                                        <a href="{{ $r['ar_url'] }}" target="_blank" data-receipt="page" class="action-btn view" title="Acknowledgement Receipt">
                                            <i data-lucide="eye"></i>
                                        </a>
                                        @else
                                        <span style="color:var(--muted);">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                        <i data-lucide="receipt" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                        <div style="font-size:14px;font-weight:700;">No receipts recorded yet.</div>
                                        <div style="font-size:13px;margin-top:4px;">Receipts appear here once a payment is recorded with an OR / Serial Number.</div>
                                    </td>
                                </tr>
                                @endforelse
                                @if($receipts->isNotEmpty())
                                <tr id="receiptEmptyRow" style="display:none;">
                                    <td colspan="7" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                        <i data-lucide="receipt" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                        <div style="font-size:14px;font-weight:700;">No receipts match your search.</div>
                                    </td>
                                </tr>
                                @endif
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

    // ── Active / Completed / Archived tabs, status filter (Active), and search ──
    var currentPaymentTab    = @json($tab);
    var currentPaymentFilter = '';

    var paymentFilterMessages = {
        '':            'No active projects found.',
        'no_setup':    'Every active project already has a payment setup.',
        'pending':     'No active projects pending down payment.',
        'in_progress': 'No active projects with payments in progress.',
        'fully_paid':  'No active projects fully paid.'
    };

    function applyFilters() {
        var q     = document.getElementById('paymentSearch').value.toLowerCase().trim();
        var panel = document.querySelector('.pay-panel[data-panel="' + currentPaymentTab + '"]');
        if (!panel) return;
        var status  = currentPaymentTab === 'active' ? currentPaymentFilter : '';
        var rows    = panel.querySelectorAll('tbody tr[data-search]');
        var visible = 0;

        rows.forEach(function(row) {
            var matchSearch = !q || row.dataset.search.includes(q);
            var matchStatus = !status || row.dataset.status === status;
            var show = matchSearch && matchStatus;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        var emptyRow = panel.querySelector('.pay-no-match');
        if (emptyRow) {
            emptyRow.style.display = rows.length && visible === 0 ? '' : 'none';
            var msg = emptyRow.querySelector('.pay-no-match-msg');
            if (msg) msg.textContent = q
                ? 'Nothing matches "' + q + '".'
                : (currentPaymentTab === 'active' ? (paymentFilterMessages[status] || 'No projects in this category.') : 'No clients match your search.');
            if (visible === 0 && typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    function showPaymentTab(tab) {
        currentPaymentTab = tab;
        document.querySelectorAll('#paymentListTabs .filter-tab').forEach(function(b) {
            b.classList.toggle('active', b.dataset.tab === tab);
        });
        document.querySelectorAll('.pay-panel').forEach(function(p) {
            p.style.display = p.dataset.panel === tab ? '' : 'none';
        });
        document.getElementById('paymentStatusSelect').style.display = tab === 'active' ? '' : 'none';
        document.getElementById('paymentSearch').placeholder = tab === 'active' ? 'Search project or client...' : 'Search client...';
        applyFilters();
        // keep the tab on refresh / when coming back from a payment or client page
        try {
            var url = new URL(window.location.href);
            if (tab === 'active') url.searchParams.delete('tab'); else url.searchParams.set('tab', tab);
            history.replaceState(null, '', url);
        } catch (e) {}
    }

    document.getElementById('paymentSearch').addEventListener('input', applyFilters);
    document.getElementById('paymentStatusSelect').addEventListener('change', function() {
        currentPaymentFilter = this.value;
        applyFilters();
    });
    document.querySelectorAll('#paymentListTabs .filter-tab').forEach(function(btn) {
        btn.addEventListener('click', function() { showPaymentTab(this.dataset.tab); });
    });

    // A row opens its payment / project (Active) or client (Completed / Archived) — except clicks on its links
    document.querySelectorAll('.pay-panel tr.pay-row').forEach(function(row) {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function(e) {
            if (e.target.closest('a, button')) return;
            window.location.href = row.dataset.href;
        });
    });

    </script>
    @include('partials.receipt_viewer')

</body>
</html>
