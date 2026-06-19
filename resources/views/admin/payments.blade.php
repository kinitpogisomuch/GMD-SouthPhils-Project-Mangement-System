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
                    <div class="filter-group">
                        <select id="paymentStatusFilter" class="filter-select">
                            <option value="">All Status</option>
                            <option value="Fully Paid">Fully Paid</option>
                            <option value="Progress Payment Paid">Progress Payment Paid</option>
                            <option value="Down Payment Paid">Down Payment Paid</option>
                            <option value="Pending Down Payment">Pending Down Payment</option>
                        </select>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="paymentsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Project Name</th>
                                <th>Client</th>
                                <th>Contract Amount</th>
                                <th>Down Payment</th>
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
                                <td>@if($payment->project)<span class="project-code-badge">{{ $payment->project->code }}</span>@endif</td>
                                <td><strong>{{ $payment->project->name ?? '—' }}</strong></td>
                                <td>{{ $payment->client }}</td>
                                <td>₱{{ number_format($payment->contract_amount, 2) }}</td>
                                <td>₱{{ number_format($downExpected, 2) }}</td>
                                <td>₱{{ number_format($balance, 2) }}</td>
                                <td>{{ $payment->payment_terms ?? '—' }}</td>
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
                                <td colspan="8" style="text-align:center;padding:40px;color:var(--muted);">
                                    No payment records yet. Click <strong>+ Record Payment Setup</strong> to get started.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- ==================== STEP 1: SELECT PROJECT MODAL ==================== -->
    <div class="modal-overlay" id="selectProjectModal">
        <div class="modal-card" style="max-width:660px;">
            <div class="modal-header">
                <div>
                    <h2>Select Project</h2>
                    <p>Choose a project before setting up payment details.</p>
                </div>
                <button class="modal-close" type="button" id="closeSelectProjectModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="search-box" style="margin-bottom:16px;">
                <i data-lucide="search"></i>
                <input type="text" id="projectSelectSearch" placeholder="Search project by name or client...">
            </div>

            <div id="projectSelectList"
                 style="display:flex;flex-direction:column;gap:8px;max-height:360px;overflow-y:auto;padding-right:4px;">
                <p style="text-align:center;color:var(--muted);padding:20px 0;">Loading projects...</p>
            </div>

            <div class="modal-actions" style="margin-top:20px;">
                <button type="button" class="cancel-btn" id="cancelSelectProject">Cancel</button>
                <button type="button" class="save-btn" id="continueSelectProject">
                    <i data-lucide="arrow-right"></i>
                    Continue
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
                    <label>Contract Amount (₱) <span style="color:var(--danger);">*</span></label>
                    <input type="number" name="contract_amount" id="setupContractAmount"
                           required min="1" step="0.01" placeholder="e.g. 1000000"
                           style="font-size:16px;font-weight:700;">
                    <p id="setupContractAmountNote" style="display:none;font-size:12px;color:var(--muted);margin-top:6px;line-height:1.5;"></p>
                </div>

                <!-- Payment Terms -->
                <div class="form-section-label">Payment Terms</div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label>Project Type <span style="color:var(--danger);">*</span></label>
                    <select name="payment_term_type" id="setupTermType" required>
                        <option value="">Select payment terms</option>
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
    document.getElementById('paymentSearch').addEventListener('input', applyFilters);
    document.getElementById('paymentStatusFilter').addEventListener('change', applyFilters);

    function applyFilters() {
        var q      = document.getElementById('paymentSearch').value.toLowerCase();
        var status = document.getElementById('paymentStatusFilter').value.toLowerCase();
        document.querySelectorAll('#paymentsTable tbody tr[data-search]').forEach(function(row) {
            var matchSearch = !q || row.dataset.search.includes(q);
            var matchStatus = !status || row.dataset.status.toLowerCase() === status;
            row.style.display = (matchSearch && matchStatus) ? '' : 'none';
        });
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
            var init       = proj.name.charAt(0).toUpperCase();
            var statusCls  = proj.status === 'completed' ? 'completed'
                           : proj.status === 'ongoing'   ? 'ongoing' : 'pending';

            item.innerHTML =
                '<div class="client-select-avatar">' + init + '</div>' +
                '<div class="client-select-info">' +
                    '<div class="client-select-name">' + proj.name + '</div>' +
                    '<div class="client-select-meta">' +
                        '<span>' + proj.client + '</span>' +
                        '<span><span class="status-badge ' + statusCls + '" style="font-size:11px;padding:2px 8px;">' +
                            proj.status.charAt(0).toUpperCase() + proj.status.slice(1) +
                        '</span></span>' +
                    '</div>' +
                '</div>' +
                '<div class="client-select-check" style="display:' + (isSelected ? 'flex' : 'none') + ';align-items:center;">' +
                    '<i data-lucide="check-circle"></i>' +
                '</div>';

            item.addEventListener('click', function() {
                selectedProject = proj;
                document.querySelectorAll('.client-select-item').forEach(function(el) {
                    el.classList.remove('selected');
                    el.querySelector('.client-select-check').style.display = 'none';
                });
                item.classList.add('selected');
                item.querySelector('.client-select-check').style.display = 'flex';
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
