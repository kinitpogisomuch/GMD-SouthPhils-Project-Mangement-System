<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
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
                <button class="add-btn" type="button" id="openSelectProjectModal">
                    <i data-lucide="plus"></i>
                    Record Payment Setup
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
                <div class="table-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="paymentSearch" placeholder="Search project or client...">
                    </div>
                    <div class="filter-tabs" id="paymentFilterTabs">
                        <button type="button" class="filter-tab active" data-filter="">
                            All
                            <span class="filter-count">{{ $payments->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="Pending Down Payment">
                            Pending
                            <span class="filter-count">{{ $payments->filter(fn($p) => $p->computeStatus() === 'Pending Down Payment')->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="Progress Payment Paid">
                            In Progress
                            <span class="filter-count">{{ $payments->filter(fn($p) => $p->computeStatus() === 'Progress Payment Paid')->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="Fully Paid">
                            Fully Paid
                            <span class="filter-count">{{ $payments->filter(fn($p) => $p->computeStatus() === 'Fully Paid')->count() }}</span>
                        </button>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="paymentsTable">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Client</th>
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
                                $status       = $payment->computeStatus();
                                $balance      = $payment->currentBalance();
                                $stageAmounts = $payment->stageAmounts();
                                $downExpected = $stageAmounts['down_payment'] ?? 0;
                            @endphp
                            <tr data-status="{{ $status }}"
                                data-search="{{ strtolower(($payment->project->name ?? '') . ' ' . $payment->client) }}">
                                @php
                                    $namePrefix = '';
                                    $nameMain   = $payment->project->name ?? '—';
                                    if ($payment->project && preg_match('/^(Fabrication of)\s+(.+)$/i', $payment->project->name, $nm)) {
                                        $namePrefix = $nm[1];
                                        $nameMain   = $nm[2];
                                    }
                                @endphp
                                <td style="overflow:hidden;">
                                    <span style="display:inline-flex;flex-direction:column;max-width:100%;min-width:0;">
                                        @if($namePrefix)
                                            <span style="font-size:9px;font-weight:700;color:var(--muted);letter-spacing:.05em;line-height:1.2;text-transform:uppercase;white-space:nowrap;">{{ $namePrefix }}</span>
                                        @endif
                                        <span style="font-size:12.5px;font-weight:800;color:var(--dark);line-height:1.3;white-space:normal;word-break:break-word;">{{ $nameMain }}</span>
                                    </span>
                                </td>
                                <td>{{ $payment->client }}</td>
                                <td>₱{{ number_format($payment->contract_amount, 2) }}</td>
                                <td>₱{{ number_format($balance, 2) }}</td>
                                @php
                                    $phases = '—';
                                    if ($payment->payment_terms) {
                                        preg_match('/^(\d+)\s+phase/i', $payment->payment_terms, $pm);
                                        $phases = isset($pm[1]) ? $pm[1].' phases' : $payment->payment_terms;
                                    }
                                @endphp
                                <td>{{ $phases }}</td>
                                <td>
                                    <span class="status-badge {{ \App\Models\Payment::statusBadgeClass($status) }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.payments.show', $payment->id) }}"
                                       class="action-btn view" title="View Details">
                                        <i data-lucide="eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="inbox" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;">No payment records yet.</div>
                                    <div style="font-size:13px;margin-top:4px;">Click <strong>+ Record Payment Setup</strong> to get started.</div>
                                </td>
                            </tr>
                            @endforelse
                            {{-- Filter empty state (shown by JS) --}}
                            @if($payments->isNotEmpty())
                            <tr id="paymentEmptyRow" style="display:none;">
                                <td colspan="8" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="folder-open" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;" id="paymentEmptyMsg">No payments in this category.</div>
                                    <div style="font-size:13px;margin-top:4px;">Try switching to a different filter or check payment records.</div>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- ==================== STEP 1: SELECT PROJECT MODAL ==================== -->
    <div class="modal-overlay" id="selectProjectModal">
        <div class="modal-card" style="max-width:560px;">
            <div class="modal-header">
                <div>
                    <h2>Select Project</h2>
                    <p>Choose a project before setting up payment details.</p>
                </div>
                <button class="modal-close" type="button" id="closeSelectProjectModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="search-box" style="margin-bottom:14px;">
                <i data-lucide="search"></i>
                <input type="text" id="projectSelectSearch" placeholder="Search by project name or client...">
            </div>

            <div id="projectSelectList" class="cs-list">
                <p style="text-align:center;color:var(--muted);padding:32px 0;font-size:14px;">Loading projects...</p>
            </div>

            <div class="modal-actions" style="margin-top:16px;">
                <button type="button" class="cancel-btn" id="cancelSelectProject">Cancel</button>
                <button type="button" class="save-btn" id="continueSelectProject">
                    Continue <i data-lucide="arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== STEP 2: PAYMENT SETUP MODAL ==================== -->
    <div class="modal-overlay" id="paymentSetupModal">
        <div class="modal-card" style="max-width:580px;">
            <div class="modal-header">
                <div>
                    <h2>Payment Setup</h2>
                    <p>Configure the contract and payment terms for the selected project.</p>
                </div>
                <button class="modal-close" type="button" id="closePaymentSetupModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.payments.setup') }}" id="paymentSetupForm">
                @csrf
                <input type="hidden" name="project_id" id="setupProjectId">

                <!-- Project Info (read-only) -->
                <div class="form-section-label">Project Information</div>
                <div style="background:var(--cream-soft);border-radius:10px;padding:14px 16px;margin-bottom:20px;display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;">
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:3px;">Project Name</div>
                        <div id="setupInfoName" style="font-weight:800;color:var(--dark);font-size:14px;">—</div>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:3px;">Client</div>
                        <div id="setupInfoClient" style="font-weight:600;color:var(--dark);font-size:14px;">—</div>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:3px;">Date Created</div>
                        <div id="setupInfoCreated" style="color:var(--dark);font-size:14px;">—</div>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:3px;">Project Status</div>
                        <div id="setupInfoStatus" style="font-size:14px;">—</div>
                    </div>
                </div>

                <!-- Contract Amount -->
                <div class="form-section-label">Contract Amount</div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label>Contract Amount (₱) </label>
                    <input type="number" name="contract_amount" id="setupContractAmount"
                           required min="1" step="0.01" placeholder="e.g. 1000000"
                           style="font-size:16px;font-weight:700;">
                    <p id="setupContractAmountNote" style="display:none;font-size:12px;color:var(--muted);margin-top:6px;line-height:1.5;"></p>
                </div>

                <!-- Payment Terms -->
                <div class="form-section-label">Payment Terms</div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label>Project Type </label>
                    <select name="payment_term_type" id="setupTermType" required>
                        <option value="" disabled selected hidden>Select payment terms</option>
                        <option value="big_project">Big Project — 3 Phases (50% / 30% / 20%)</option>
                        <option value="small_project">Small Project — 2 Phases (50% / 50%)</option>
                    </select>
                </div>

                <!-- Live Schedule Preview -->
                <div id="setupSchedulePreview" style="display:none;background:#fff;border:1.5px solid var(--accent);border-radius:10px;padding:14px 16px;margin-bottom:20px;">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--accent);margin-bottom:10px;">Generated Payment Schedule</div>
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr style="border-bottom:2px solid var(--cream-soft);">
                                <th style="text-align:left;padding:6px 0;color:var(--muted);font-weight:600;">Stage</th>
                                <th style="text-align:center;padding:6px 0;color:var(--muted);font-weight:600;">%</th>
                                <th style="text-align:right;padding:6px 0;color:var(--muted);font-weight:600;">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="setupPreviewRows"></tbody>
                        <tfoot>
                            <tr style="border-top:2px solid var(--dark);background:var(--dark);">
                                <td style="padding:8px 12px;font-weight:700;color:#fff;border-radius:0 0 0 8px;">Total</td>
                                <td style="text-align:center;padding:8px 0;font-weight:700;color:#fff;">100%</td>
                                <td style="text-align:right;padding:8px 12px;font-weight:700;color:#fff;border-radius:0 0 8px 0;" id="setupPreviewTotal">₱0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelPaymentSetup">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="save"></i>
                        Create Payment Record
                    </button>
                </div>
            </form>
        </div>
    </div>

    @php
    $availableProjectsJson = $availableProjects->map(function($p) {
        $bomTotal   = (float) ($p->bom_total ?? 0);
        $laborTotal = (float) ($p->labor_total ?? 0);

        return [
            'id'                        => $p->id,
            'name'                      => $p->name,
            'client'                    => $p->client,
            'client_type'               => $p->client_type,
            'status'                    => $p->status,
            'created_at'                => $p->created_at->format('M d, Y'),
            'bom_total'                 => $bomTotal,
            'labor_total'               => $laborTotal,
            'suggested_contract_amount' => round($bomTotal + $laborTotal, 2),
        ];
    })->values();
    @endphp

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
    lucide.createIcons();

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
        '':                      'No payment records found.',
        'pending down payment':  'No payments pending down payment.',
        'progress payment paid': 'No payments at progress payment stage.',
        'fully paid':            'No fully paid payments.'
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
                ? 'No payments match "' + q + '".'
                : (paymentFilterMessages[status] || 'No payments in this category.');
            if (visibleCount === 0 && typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    // ── Modal helpers ──────────────────────────────────────────────────────
    function openModal(id) {
        var m = document.getElementById(id);
        if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
    }
    function closeModal(id) {
        var m = document.getElementById(id);
        if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
    }

    // ── Available projects (from server) ──────────────────────────────────
    var AVAILABLE_PROJECTS = @json($availableProjectsJson);

    var selectedProject = null;

    // ── Step 1: Select Project Modal ───────────────────────────────────────
    document.getElementById('openSelectProjectModal').addEventListener('click', function() {
        selectedProject = null;
        document.getElementById('projectSelectSearch').value = '';
        openModal('selectProjectModal');
        renderProjectList(AVAILABLE_PROJECTS, '');
    });

    ['closeSelectProjectModal', 'cancelSelectProject'].forEach(function(id) {
        var btn = document.getElementById(id);
        if (btn) btn.addEventListener('click', function() { closeModal('selectProjectModal'); });
    });

    document.getElementById('selectProjectModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal('selectProjectModal');
    });

    document.getElementById('projectSelectSearch').addEventListener('input', function() {
        renderProjectList(AVAILABLE_PROJECTS, this.value);
    });

    function renderProjectList(projects, filter) {
        var list = document.getElementById('projectSelectList');
        var q    = (filter || '').toLowerCase();
        var filtered = q
            ? projects.filter(function(p) {
                return p.name.toLowerCase().indexOf(q) !== -1 ||
                       p.client.toLowerCase().indexOf(q) !== -1;
              })
            : projects;

        list.innerHTML = '';

        if (filtered.length === 0) {
            list.innerHTML = '<p style="text-align:center;color:var(--muted);padding:20px 0;font-size:14px;">' +
                (projects.length === 0
                    ? 'All existing projects already have payment records.'
                    : 'No projects match your search.') +
                '</p>';
            return;
        }

        filtered.forEach(function(proj) {
            var isSelected = selectedProject && selectedProject.id === proj.id;
            var item       = document.createElement('div');
            item.className = 'client-select-item' + (isSelected ? ' selected' : '');
            var statusCls  = proj.status === 'completed' ? 'completed'
                           : proj.status === 'ongoing'   ? 'ongoing' : 'pending';

            item.innerHTML =
                '<div class="cs-avatar"><span class="cs-avatar-init">' + proj.name.charAt(0).toUpperCase() + '</span></div>' +
                '<div class="cs-info">' +
                    '<div class="cs-name">' + proj.name + '</div>' +
                    '<div class="cs-meta">' +
                        '<span><i data-lucide="building-2" style="width:11px;height:11px;flex-shrink:0;"></i>' + proj.client + '</span>' +
                        '<span><span class="status-badge ' + statusCls + '" style="font-size:10.5px;padding:2px 8px;">' +
                            proj.status.charAt(0).toUpperCase() + proj.status.slice(1) +
                        '</span></span>' +
                    '</div>' +
                '</div>' +
                '<div class="cs-check" style="display:' + (isSelected ? 'flex' : 'none') + ';">' +
                    '<i data-lucide="check-circle-2" style="width:20px;height:20px;color:var(--dark);"></i>' +
                '</div>';

            item.addEventListener('click', function() {
                selectedProject = proj;
                document.querySelectorAll('#projectSelectList .client-select-item').forEach(function(el) {
                    el.classList.remove('selected');
                    el.querySelector('.cs-check').style.display = 'none';
                });
                item.classList.add('selected');
                item.querySelector('.cs-check').style.display = 'flex';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });

            list.appendChild(item);
        });

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    document.getElementById('continueSelectProject').addEventListener('click', function() {
        if (!selectedProject) {
            alert('Please select a project to continue.');
            return;
        }
        closeModal('selectProjectModal');
        populateSetupModal(selectedProject);
        openModal('paymentSetupModal');
    });

    // ── Step 2: Payment Setup Modal ────────────────────────────────────────
    ['closePaymentSetupModal', 'cancelPaymentSetup'].forEach(function(id) {
        var btn = document.getElementById(id);
        if (btn) btn.addEventListener('click', function() {
            closeModal('paymentSetupModal');
            resetSetupForm();
        });
    });

    document.getElementById('paymentSetupModal').addEventListener('click', function(e) {
        if (e.target === this) { closeModal('paymentSetupModal'); resetSetupForm(); }
    });

    function populateSetupModal(proj) {
        document.getElementById('setupProjectId').value   = proj.id;
        document.getElementById('setupInfoName').textContent    = proj.name;
        document.getElementById('setupInfoClient').textContent  = proj.client;
        document.getElementById('setupInfoCreated').textContent = proj.created_at;

        var statusCls = proj.status === 'completed' ? 'completed'
                      : proj.status === 'ongoing'   ? 'ongoing' : 'pending';
        document.getElementById('setupInfoStatus').innerHTML =
            '<span class="status-badge ' + statusCls + '">' +
            proj.status.charAt(0).toUpperCase() + proj.status.slice(1) + '</span>';

        var suggested = proj.suggested_contract_amount || 0;
        var note      = document.getElementById('setupContractAmountNote');

        if (suggested > 0) {
            document.getElementById('setupContractAmount').value = suggested.toFixed(2);
            note.innerHTML = 'Auto-filled from Bill of Materials (' + fmt(proj.bom_total) +
                ') + Labor Cost (' + fmt(proj.labor_total) + '). You may adjust this amount if needed.';
            note.style.display = 'block';
        } else {
            document.getElementById('setupContractAmount').value = '';
            note.innerHTML = 'No BOM or labor cost data found for this project yet — enter the contract amount manually.';
            note.style.display = 'block';
        }

        document.getElementById('setupTermType').value       = '';
        document.getElementById('setupSchedulePreview').style.display = 'none';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function resetSetupForm() {
        document.getElementById('paymentSetupForm').reset();
        document.getElementById('setupSchedulePreview').style.display = 'none';
    }

    document.getElementById('setupContractAmount').addEventListener('input', updatePreview);
    document.getElementById('setupTermType').addEventListener('change', updatePreview);

    function fmt(n) {
        return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function updatePreview() {
        var amount   = parseFloat(document.getElementById('setupContractAmount').value) || 0;
        var termType = document.getElementById('setupTermType').value;
        var preview  = document.getElementById('setupSchedulePreview');

        if (!amount || !termType) { preview.style.display = 'none'; return; }

        var stages = termType === 'big_project'
            ? [
                { label: 'Down Payment',     pct: 50, amt: amount * 0.50 },
                { label: 'Progress Payment', pct: 30, amt: amount * 0.30 },
                { label: 'Final Payment',    pct: 20, amt: amount * 0.20 },
              ]
            : [
                { label: 'Down Payment',  pct: 50, amt: amount * 0.50 },
                { label: 'Final Payment', pct: 50, amt: amount * 0.50 },
              ];

        document.getElementById('setupPreviewRows').innerHTML = stages.map(function(s) {
            return '<tr style="border-bottom:1px solid var(--cream-soft);">' +
                '<td style="padding:7px 0;font-weight:600;color:var(--dark);">' + s.label + '</td>' +
                '<td style="text-align:center;padding:7px 0;color:var(--muted);">' + s.pct + '%</td>' +
                '<td style="text-align:right;padding:7px 0;font-weight:700;color:var(--dark);">' + fmt(s.amt) + '</td>' +
                '</tr>';
        }).join('');

        document.getElementById('setupPreviewTotal').textContent = fmt(amount);
        preview.style.display = 'block';
    }
    </script>
</body>
</html>
