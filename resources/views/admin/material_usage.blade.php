<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materials | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">
            <div class="page-header">
                <div>
                    <h1>Materials</h1>
                    <p>Track materials consumed during fabrication and manage requests sent to suppliers.</p>
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

            <!-- Materials Tabs -->
            <div class="emp-tabs">
                <button class="emp-tab" data-tab="usage">
                    <i data-lucide="clipboard-list"></i>
                    Usage Log
                </button>
                <button class="emp-tab" data-tab="requests">
                    <i data-lucide="alert-triangle"></i>
                    Material Requests
                    @if($pendingCount > 0)
                    <span style="background:var(--danger);color:#fff;font-size:11px;font-weight:800;padding:2px 7px;border-radius:999px;margin-left:4px;">{{ $pendingCount }}</span>
                    @endif
                </button>
            </div>

            <!-- ===== TAB: USAGE LOG ===== -->
            <div class="emp-tab-content" id="tab-usage">
                <div class="table-card">
                    <div class="table-toolbar">
                        <div class="search-box">
                            <i data-lucide="search"></i>
                            <input type="text" id="projectSearch" placeholder="Search project or client...">
                        </div>
                        <div class="filter-group">
                            <select class="filter-select" id="statusFilter">
                                <option value="all">All Projects</option>
                                <option value="active">Active Only</option>
                                <option value="archived">Archived Only</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table class="data-table" id="usageTable">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Client</th>
                                    <th>Current Phase</th>
                                    <th>Planned Materials</th>
                                    <th>Usage Entries Logged</th>
                                    <th>Total Qty Used</th>
                                    <th>Date Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $project)
                                <tr data-status="{{ $project->status }}">
                                    <td><strong>{{ $project->name }}</strong></td>
                                    <td>{{ $project->client }}</td>
                                    <td>
                                        <span class="status-badge {{ $project->status === 'completed' ? 'completed' : 'ongoing' }}">
                                            {{ ucfirst(str_replace('_', ' ', $project->current_phase ?? 'Planning')) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php $matCount = $project->activeMaterials->count(); @endphp
                                        <span style="font-weight:700;">{{ $matCount }}</span>
                                        <span style="color:var(--muted);font-size:13px;"> material{{ $matCount !== 1 ? 's' : '' }}</span>
                                    </td>
                                    <td>
                                        @php $usageCount = $project->activeMaterialUsages->count(); @endphp
                                        <span style="font-weight:700;">{{ $usageCount }}</span>
                                        <span style="color:var(--muted);font-size:13px;"> entr{{ $usageCount !== 1 ? 'ies' : 'y' }}</span>
                                    </td>
                                    <td>
                                        @php $totalQty = $project->activeMaterialUsages->sum('quantity_used'); @endphp
                                        <strong>{{ number_format($totalQty, 0) }}</strong>
                                    </td>
                                    <td>{{ $project->created_at->format('M d, Y') }}</td>
                                    <td class="action-cell">
                                        <a href="{{ route('admin.material_usage.detail', $project->id) }}"
                                           class="action-btn view" title="View Material Usage">
                                            <i data-lucide="clipboard-list"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" style="text-align:center;padding:40px;color:var(--muted);">
                                        No projects found. Add projects via the <strong>Projects</strong> page.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ===== TAB: MATERIAL REQUESTS ===== -->
            <div class="emp-tab-content" id="tab-requests">
                <!-- Summary row -->
                <div class="page-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
                    <div class="info-card orange">
                        <div class="info-card-icon orange"><i data-lucide="clock"></i></div>
                        <h3>Pending Requests</h3>
                        <div class="value">{{ $pendingCount }}</div>
                        <div class="info-card-sub">Awaiting supplier response</div>
                    </div>
                    <div class="info-card green">
                        <div class="info-card-icon green"><i data-lucide="check-circle"></i></div>
                        <h3>Fulfilled</h3>
                        <div class="value">{{ $fulfilledCount }}</div>
                        <div class="info-card-sub">Materials delivered</div>
                    </div>
                    <div class="info-card red">
                        <div class="info-card-icon red"><i data-lucide="alert-triangle"></i></div>
                        <h3>Shortages Flagged</h3>
                        <div class="value">{{ $shortageCount }}</div>
                        <div class="info-card-sub">Needs immediate restocking</div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-toolbar">
                        <div class="search-box">
                            <i data-lucide="search"></i>
                            <input type="text" id="materialSearch" placeholder="Search material...">
                        </div>
                        <div class="filter-group">
                            <select id="materialStatusFilter" class="filter-select">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="fulfilled">Fulfilled</option>
                                <option value="shortage">Shortage</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table class="data-table" id="materialsTable">
                            <thead>
                                <tr>
                                    <th>Material</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Project</th>
                                    <th>Supplier</th>
                                    <th>Requested Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $req)
                                    @php
                                        $linkedProject = $req->getRelation('project');
                                        $projectName = $linkedProject->name ?? $req->project ?? null;
                                        $suggested = $req->projectMaterial
                                            ? ((float) $req->quantity * (float) $req->projectMaterial->price_per_unit)
                                            : 0;
                                        $fundDescription = 'Material purchase: ' . $req->quantity . ' ' . $req->unit . ' ' . $req->material
                                            . ' for ' . ($projectName ?? 'General');
                                    @endphp
                                    <tr data-status="{{ $req->status }}"
                                        data-search="{{ strtolower($req->material . ' ' . ($projectName ?? '') . ' ' . ($req->supplier ?? '')) }}">
                                        <td>{{ $req->material }}</td>
                                        <td>{{ $req->quantity }}</td>
                                        <td>{{ $req->unit }}</td>
                                        <td>
                                            @if($linkedProject)
                                                <a href="{{ route('admin.project_view', $linkedProject->id) }}" style="color:var(--dark);font-weight:700;text-decoration:none;">
                                                    {{ $linkedProject->name }}
                                                </a>
                                            @elseif($projectName)
                                                {{ $projectName }}
                                            @else
                                                <span style="color:var(--muted);">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $req->supplier ?? '—' }}</td>
                                        <td>{{ $req->requested_date ? $req->requested_date->format('M d, Y') : '—' }}</td>
                                        <td>
                                            @if($req->status === 'fulfilled')
                                                <span class="status-badge completed">Fulfilled</span>
                                            @elseif($req->status === 'shortage')
                                                <span class="status-badge shortage">Shortage</span>
                                            @else
                                                <span class="status-badge pending">Pending</span>
                                            @endif
                                        </td>
                                        <td class="action-cell">
                                            @if($req->status === 'pending' || $req->status === 'shortage')
                                                <button class="action-btn view" type="button" title="Fund from Revolving Fund"
                                                    onclick="openFundModal(this)"
                                                    data-action="{{ route('admin.material_requests.fund', $req->id) }}"
                                                    data-material="{{ $req->material }}"
                                                    data-quantity="{{ $req->quantity }}"
                                                    data-unit="{{ $req->unit }}"
                                                    data-project="{{ $projectName ?? '—' }}"
                                                    data-suggested="{{ number_format($suggested, 2, '.', '') }}"
                                                    data-description="{{ $fundDescription }}">
                                                    <i data-lucide="wallet"></i>
                                                </button>
                                            @endif
                                            @if($req->status === 'shortage')
                                                <form method="POST" action="{{ route('admin.material_requests.rerequest', $req->id) }}" style="display:inline;"
                                                      onsubmit="return confirm('Re-send this request to the supplier?');">
                                                    @csrf
                                                    <button class="action-btn view" type="submit" title="Re-request">
                                                        <i data-lucide="refresh-ccw"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            @if($req->status === 'fulfilled')
                                                <button class="action-btn view" type="button" title="View">
                                                    <i data-lucide="eye"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="emptyMaterialRow">
                                        <td colspan="8" style="text-align:center;padding:40px;color:var(--muted);">
                                            No material requests yet.
                                        </td>
                                    </tr>
                                @endforelse
                                @if($requests->isNotEmpty())
                                <tr id="noMaterialMatchRow" style="display:none;">
                                    <td colspan="8" style="text-align:center;padding:40px;color:var(--muted);">
                                        No requests match your search.
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

    <!-- ==================== FUND FROM REVOLVING FUND MODAL ==================== -->
    <div class="modal-overlay" id="fundMaterialModal">
        <div class="modal-card" style="max-width:480px;">
            <div class="modal-header">
                <div>
                    <h2>Fund from Revolving Fund</h2>
                    <p>Withdraw from the revolving fund to cover this material shortage.</p>
                </div>
                <button class="modal-close" type="button" id="closeFundMaterialModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" id="fundMaterialForm" action="">
                @csrf
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Material</label>
                        <input type="text" id="fundMaterialName" readonly>
                    </div>
                    <div class="form-group">
                        <label>Quantity / Unit</label>
                        <input type="text" id="fundMaterialQty" readonly>
                    </div>
                    <div class="form-group">
                        <label>Project</label>
                        <input type="text" id="fundMaterialProject" readonly>
                    </div>
                    <div class="form-group form-group-full" style="font-size:13px;color:var(--muted);">
                        Available: ₱{{ number_format($fundBalance, 2) }}
                    </div>
                    <div class="form-group form-group-full">
                        <label>Amount (₱) <span style="color:var(--danger);">*</span></label>
                        <input type="number" name="amount" id="fundMaterialAmount" required min="0.01" step="0.01">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Description / Purpose <span style="color:var(--danger);">*</span></label>
                        <textarea name="description" id="fundMaterialDescription" rows="3" required></textarea>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelFundMaterial">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="wallet"></i>
                        Fund Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>const ACTIVE_TAB = "{{ session('active_tab', 'usage') }}";</script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        function openFundModal(btn) {
            document.getElementById('fundMaterialForm').action = btn.dataset.action;
            document.getElementById('fundMaterialName').value = btn.dataset.material;
            document.getElementById('fundMaterialQty').value = btn.dataset.quantity + ' ' + btn.dataset.unit;
            document.getElementById('fundMaterialProject').value = btn.dataset.project;
            document.getElementById('fundMaterialAmount').value = btn.dataset.suggested;
            document.getElementById('fundMaterialDescription').value = btn.dataset.description;

            var modal = document.getElementById('fundMaterialModal');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        var currentStatusFilter = 'all';

        function applyUsageFilters() {
            var q = (document.getElementById('projectSearch').value || '').toLowerCase();
            document.querySelectorAll('#usageTable tbody tr').forEach(function(row) {
                var status = (row.dataset.status || '').toLowerCase();
                var matchSearch = row.textContent.toLowerCase().indexOf(q) !== -1;
                var matchFilter = currentStatusFilter === 'all'
                    || (currentStatusFilter === 'archived' && status === 'archived')
                    || (currentStatusFilter === 'active'   && status !== 'archived');
                row.style.display = (matchSearch && matchFilter) ? '' : 'none';
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();

            // Tabs
            document.querySelectorAll('.emp-tab').forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-tab') === ACTIVE_TAB);
            });
            document.querySelectorAll('.emp-tab-content').forEach(function (pane) {
                pane.classList.toggle('active', pane.id === 'tab-' + ACTIVE_TAB);
            });
            document.querySelectorAll('.emp-tab').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.emp-tab').forEach(b => b.classList.remove('active'));
                    document.querySelectorAll('.emp-tab-content').forEach(p => p.classList.remove('active'));
                    this.classList.add('active');
                    document.getElementById('tab-' + this.getAttribute('data-tab')).classList.add('active');
                });
            });

            // Usage Log search + filter
            var projectSearch = document.getElementById('projectSearch');
            if (projectSearch) projectSearch.addEventListener('keyup', applyUsageFilters);

            var statusFilter = document.getElementById('statusFilter');
            if (statusFilter) {
                statusFilter.addEventListener('change', function() {
                    currentStatusFilter = this.value;
                    applyUsageFilters();
                });
            }

            // Fund from Revolving Fund modal close handlers
            var fundModal = document.getElementById('fundMaterialModal');
            function closeFundModal() {
                fundModal.classList.remove('show');
                document.body.style.overflow = '';
            }
            document.getElementById('closeFundMaterialModal').addEventListener('click', closeFundModal);
            document.getElementById('cancelFundMaterial').addEventListener('click', closeFundModal);
            fundModal.addEventListener('click', function(e) {
                if (e.target === this) closeFundModal();
            });

            // Material Requests search + status filter
            var materialSearch = document.getElementById('materialSearch');
            var materialStatusFilter = document.getElementById('materialStatusFilter');
            var noMatchRow = document.getElementById('noMaterialMatchRow');

            function applyMaterialFilters() {
                var q = (materialSearch.value || '').toLowerCase();
                var status = materialStatusFilter.value;
                var visibleCount = 0;

                document.querySelectorAll('#materialsTable tbody tr[data-status]').forEach(function(row) {
                    var matchSearch = !q || (row.dataset.search || '').indexOf(q) !== -1;
                    var matchStatus = !status || row.dataset.status === status;
                    var visible = matchSearch && matchStatus;
                    row.style.display = visible ? '' : 'none';
                    if (visible) visibleCount++;
                });

                if (noMatchRow) {
                    noMatchRow.style.display = visibleCount === 0 ? '' : 'none';
                }
            }

            if (materialSearch) materialSearch.addEventListener('input', applyMaterialFilters);
            if (materialStatusFilter) materialStatusFilter.addEventListener('change', applyMaterialFilters);

            @if(session('success'))
            closeFundModal();
            @endif
        });
    </script>
</body>
</html>
