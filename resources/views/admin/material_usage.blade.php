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

            <!-- ===== SUPPLIER CONTACTS ===== -->
            <div class="table-card" style="margin-bottom:24px;">
                <div class="table-toolbar" style="padding-bottom:0;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i data-lucide="users" style="color:var(--primary);width:20px;height:20px;"></i>
                        <span style="font-size:15px;font-weight:700;color:var(--dark);">Supplier Contacts</span>
                        <span style="background:var(--light);color:var(--muted);font-size:12px;font-weight:700;padding:2px 8px;border-radius:999px;">{{ $supplierContacts->count() }}</span>
                    </div>
                    <button class="save-btn" type="button" onclick="openAddSupplierModal()" style="font-size:13px;padding:8px 16px;">
                        <i data-lucide="plus"></i>
                        Add Contact
                    </button>
                </div>

                @if($supplierContacts->isEmpty())
                <div style="text-align:center;padding:32px 20px;color:var(--muted);font-size:14px;">
                    No supplier contacts yet. Click <strong>Add Contact</strong> to add one.
                </div>
                @else
                <div class="table-wrapper">
                    <table class="data-table" id="supplierTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Company</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($supplierContacts as $contact)
                            <tr>
                                <td><strong>{{ $contact->name }}</strong></td>
                                <td>{{ $contact->company ?? '—' }}</td>
                                <td>{{ $contact->phone ?? '—' }}</td>
                                <td>{{ $contact->email ?? '—' }}</td>
                                <td>{{ $contact->address ?? '—' }}</td>
                                <td style="max-width:200px;white-space:normal;font-size:13px;color:var(--muted);">{{ $contact->notes ?? '—' }}</td>
                                <td class="action-cell">
                                    <button class="action-btn view" type="button" title="Edit"
                                        onclick="openEditSupplierModal(this)"
                                        data-id="{{ $contact->id }}"
                                        data-name="{{ $contact->name }}"
                                        data-company="{{ $contact->company }}"
                                        data-phone="{{ $contact->phone }}"
                                        data-email="{{ $contact->email }}"
                                        data-address="{{ $contact->address }}"
                                        data-notes="{{ $contact->notes }}">
                                        <i data-lucide="pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('admin.supplier_contacts.destroy', $contact->id) }}" style="display:inline;"
                                          onsubmit="return confirm('Delete this supplier contact?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="action-btn delete" type="submit" title="Delete">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

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

    <!-- ==================== ADD SUPPLIER CONTACT MODAL ==================== -->
    <div class="modal-overlay" id="addSupplierModal">
        <div class="modal-card" style="max-width:520px;">
            <div class="modal-header">
                <div>
                    <h2>Add Supplier Contact</h2>
                    <p>Enter the supplier's contact details.</p>
                </div>
                <button class="modal-close" type="button" onclick="closeModal('addSupplierModal')">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.supplier_contacts.store') }}" id="addSupplierForm">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label>Supplier Name <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="name" required maxlength="255" placeholder="e.g. Juan Dela Cruz"
                               oninput="supplierCapFirst(this)">
                    </div>
                    <div class="form-group">
                        <label>Company <span style="font-weight:400;color:var(--muted);font-size:11px;">(optional)</span></label>
                        <input type="text" name="company" maxlength="255" placeholder="e.g. ABC Supplies Inc."
                               oninput="supplierCapFirst(this)">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" id="addSupplierPhone" maxlength="11"
                               placeholder="e.g. 09171234567"
                               oninput="supplierPhoneInput(this, 'addSupplierPhoneErr')">
                        <span class="supplier-field-err" id="addSupplierPhoneErr"></span>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="addSupplierEmail" maxlength="255"
                               placeholder="e.g. supplier@email.com"
                               oninput="supplierEmailValidate(this, 'addSupplierEmailErr')">
                        <span class="supplier-field-err" id="addSupplierEmailErr"></span>
                    </div>
                    <div class="form-group form-group-full">
                        <label>Address</label>
                        <input type="text" name="address" maxlength="500" placeholder="e.g. 123 Main St, Davao City"
                               oninput="supplierCapFirst(this)">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Notes</label>
                        <textarea name="notes" rows="3" maxlength="1000" placeholder="Additional notes about this supplier..."></textarea>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeModal('addSupplierModal')">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="plus"></i>
                        Add Contact
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== EDIT SUPPLIER CONTACT MODAL ==================== -->
    <div class="modal-overlay" id="editSupplierModal">
        <div class="modal-card" style="max-width:520px;">
            <div class="modal-header">
                <div>
                    <h2>Edit Supplier Contact</h2>
                    <p>Update the supplier's contact details.</p>
                </div>
                <button class="modal-close" type="button" onclick="closeModal('editSupplierModal')">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" id="editSupplierForm" action="">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-group">
                        <label>Name <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="name" id="editSupplierName" required maxlength="255"
                               oninput="supplierCapFirst(this)">
                    </div>
                    <div class="form-group">
                        <label>Company <span style="font-weight:400;color:var(--muted);font-size:11px;">(optional)</span></label>
                        <input type="text" name="company" id="editSupplierCompany" maxlength="255"
                               oninput="supplierCapFirst(this)">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" id="editSupplierPhone" maxlength="11"
                               oninput="supplierPhoneInput(this, 'editSupplierPhoneErr')">
                        <span class="supplier-field-err" id="editSupplierPhoneErr"></span>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="editSupplierEmail" maxlength="255"
                               oninput="supplierEmailValidate(this, 'editSupplierEmailErr')">
                        <span class="supplier-field-err" id="editSupplierEmailErr"></span>
                    </div>
                    <div class="form-group form-group-full">
                        <label>Address</label>
                        <input type="text" name="address" id="editSupplierAddress" maxlength="500"
                               oninput="supplierCapFirst(this)">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Notes</label>
                        <textarea name="notes" id="editSupplierNotes" rows="3" maxlength="1000"></textarea>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeModal('editSupplierModal')">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="save"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
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
        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
            document.body.style.overflow = '';
        }

        function openAddSupplierModal() {
            document.getElementById('addSupplierModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function openEditSupplierModal(btn) {
            document.getElementById('editSupplierForm').action = '{{ url("admin/supplier-contacts") }}/' + btn.dataset.id;
            document.getElementById('editSupplierName').value    = btn.dataset.name    || '';
            document.getElementById('editSupplierCompany').value = btn.dataset.company || '';
            document.getElementById('editSupplierPhone').value   = btn.dataset.phone   || '';
            document.getElementById('editSupplierEmail').value   = btn.dataset.email   || '';
            document.getElementById('editSupplierAddress').value = btn.dataset.address || '';
            document.getElementById('editSupplierNotes').value   = btn.dataset.notes   || '';
            document.getElementById('editSupplierModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

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

            // Supplier modal backdrop close
            ['addSupplierModal', 'editSupplierModal'].forEach(function(id) {
                var el = document.getElementById(id);
                el.addEventListener('click', function(e) {
                    if (e.target === this) closeModal(id);
                });
            });

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

            // Block supplier form submission on invalid phone/email
            var addSupplierForm = document.getElementById('addSupplierForm');
            if (addSupplierForm) {
                addSupplierForm.addEventListener('submit', function (e) {
                    if (!supplierFormValid('addSupplierPhone', 'addSupplierPhoneErr', 'addSupplierEmail', 'addSupplierEmailErr')) {
                        e.preventDefault();
                    }
                });
            }
            var editSupplierForm = document.getElementById('editSupplierForm');
            if (editSupplierForm) {
                editSupplierForm.addEventListener('submit', function (e) {
                    if (!supplierFormValid('editSupplierPhone', 'editSupplierPhoneErr', 'editSupplierEmail', 'editSupplierEmailErr')) {
                        e.preventDefault();
                    }
                });
            }
        });

    // ---- Supplier field helpers ----
    function supplierCapFirst(input) {
        var pos = input.selectionStart;
        input.value = input.value.replace(/(^|[\s\-])(\S)/g, function(_, sep, ch) {
            return sep + ch.toUpperCase();
        });
        input.setSelectionRange(pos, pos);
    }

    function supplierSetErr(errId, msg) {
        var el = document.getElementById(errId);
        if (!el) return;
        el.textContent = msg;
        el.style.display = msg ? 'block' : 'none';
        var input = el.previousElementSibling;
        if (input) input.style.borderColor = msg ? '#dc2626' : '';
    }

    function supplierPhoneInput(input, errId) {
        var pos     = input.selectionStart;
        var digits  = input.value.replace(/[^0-9]/g, '').slice(0, 11);
        input.value = digits;
        input.setSelectionRange(Math.min(pos, digits.length), Math.min(pos, digits.length));
        var len = digits.length;
        if (len === 0) {
            supplierSetErr(errId, '');
        } else if (len < 11) {
            supplierSetErr(errId, 'Phone number must be exactly 11 digits.');
        } else {
            supplierSetErr(errId, '');
        }
    }

    function supplierEmailValidate(input, errId) {
        var val = input.value.trim();
        var ok  = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
        supplierSetErr(errId, val && !ok ? 'Enter a valid email address (e.g. supplier@email.com).' : '');
    }

    function supplierFormValid(phoneId, phoneErrId, emailId, emailErrId) {
        var valid = true;
        var phone = document.getElementById(phoneId);
        if (phone && phone.value) {
            var digits = phone.value.replace(/[^0-9]/g, '');
            if (digits.length !== 11) {
                supplierSetErr(phoneErrId, 'Phone number must be exactly 11 digits.');
                valid = false;
            }
        }
        var email = document.getElementById(emailId);
        if (email && email.value.trim()) {
            var ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim());
            if (!ok) {
                supplierSetErr(emailErrId, 'Enter a valid email address (e.g. supplier@email.com).');
                valid = false;
            }
        }
        return valid;
    }
    </script>

    <style>
    .supplier-field-err {
        display: none;
        color: #dc2626;
        font-size: 11.5px;
        margin-top: 4px;
        line-height: 1.4;
    }
    </style>
</body>
</html>
