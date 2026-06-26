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
                    <p>Track BOM planned materials, actual purchases, and cost variance per project.</p>
                </div>
            </div>

            @if(session('success'))
            <div class="alert-banner success"><i data-lucide="check-circle"></i> {{ session('success') }}</div>
            @endif
            @if(session('error'))
            <div class="alert-banner error"><i data-lucide="alert-circle"></i> {{ session('error') }}</div>
            @endif

            {{-- ── Supplier Contacts Section ─────────────────────────────── --}}
            <div class="table-card" style="margin-bottom:20px;">
                <div class="table-toolbar" style="border-bottom:1px solid var(--border);padding-bottom:14px;margin-bottom:16px;">
                    <div>
                        <h2 style="font-size:15px;font-weight:700;margin:0 0 2px;">Supplier Contacts</h2>
                        <p style="font-size:13px;color:var(--muted);margin:0;">Manually manage your supplier directory.</p>
                    </div>
                    <button type="button" id="openAddSupplierBtn"
                        style="display:inline-flex;align-items:center;gap:7px;background:var(--dark);color:#fff;border:none;border-radius:10px;padding:9px 16px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;transition:opacity .15s;"
                        onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                        <i data-lucide="plus" style="width:15px;height:15px;"></i> Add Supplier
                    </button>
                </div>

                @if($suppliers->isEmpty())
                <div style="text-align:center;padding:32px 20px;color:var(--muted);">
                    <i data-lucide="users" style="width:32px;height:32px;margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;opacity:.4;"></i>
                    <p style="font-size:13px;margin:0;">No supplier contacts yet. Add your first one.</p>
                </div>
                @else
                <div class="table-wrapper">
                    <table class="data-table" id="suppliersTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Company</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($suppliers as $sup)
                            <tr>
                                <td><strong>{{ $sup->name }}</strong></td>
                                <td>{{ $sup->company ?? '—' }}</td>
                                <td>{{ $sup->phone ?? '—' }}</td>
                                <td>{{ $sup->email ?? '—' }}</td>
                                <td>{{ $sup->address ?? '—' }}</td>
                                <td class="action-cell">
                                    <button class="action-btn edit" title="Edit"
                                        onclick="openEditSupplier({{ $sup->id }}, {{ json_encode($sup->name) }}, {{ json_encode($sup->company) }}, {{ json_encode($sup->phone) }}, {{ json_encode($sup->email) }}, {{ json_encode($sup->address) }})">
                                        <i data-lucide="pencil"></i>
                                    </button>
                                    <button class="action-btn delete" title="Delete"
                                        onclick="confirmDeleteSupplier({{ $sup->id }}, {{ json_encode($sup->name) }})">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

            {{-- ── Projects Table ────────────────────────────────────────── --}}
            <div class="table-card">
                <div class="table-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="projectSearch" placeholder="Search project or client...">
                    </div>
                    <div class="filter-tabs" id="usageFilterTabs">
                        <button type="button" class="filter-tab active" data-filter="active">
                            Active
                            <span class="filter-count">{{ $projects->whereNotIn('status', ['completed', 'archived'])->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="completed">
                            Completed
                            <span class="filter-count">{{ $projects->where('status', 'completed')->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="archived">
                            Archived
                            <span class="filter-count">{{ $projects->where('status', 'archived')->count() }}</span>
                        </button>
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
                                       class="action-btn view" title="View Materials">
                                        <i data-lucide="eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" style="text-align:center;padding:40px;color:var(--muted);">
                                    No projects found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    {{-- Add Supplier Modal --}}
    <div class="modal-overlay" id="addSupplierModal">
        <div class="modal-card" style="max-width:560px;">
            <div class="modal-header">
                <div>
                    <h2>Add Supplier Contact</h2>
                    <p>Fill in the supplier details below.</p>
                </div>
                <button class="modal-close" type="button" id="closeAddSupplierBtn">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.supplier_contacts.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" placeholder="e.g. Juan dela Cruz" required>
                    </div>
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" name="company" placeholder="e.g. Steel Supply Co.">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" placeholder="e.g. 09XX-XXX-XXXX">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="supplier@example.com">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Address</label>
                        <input type="text" name="address" placeholder="Street, City, Province">
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelAddSupplierBtn">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="user-plus"></i>
                        Save Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Supplier Modal --}}
    <div class="modal-overlay" id="editSupplierModal">
        <div class="modal-card" style="max-width:560px;">
            <div class="modal-header">
                <div>
                    <h2>Edit Supplier Contact</h2>
                    <p>Update the supplier details below.</p>
                </div>
                <button class="modal-close" type="button" id="closeEditSupplierBtn">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" id="editSupplierForm">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" id="editSupName" required>
                    </div>
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" name="company" id="editSupCompany">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" id="editSupPhone">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="editSupEmail">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Address</label>
                        <input type="text" name="address" id="editSupAddress">
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelEditSupplierBtn">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="save"></i>
                        Update Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Hidden delete form --}}
    <form method="POST" id="deleteSupplierForm" style="display:none;">
        @csrf
        @method('DELETE')
    </form>

    <script>const ACTIVE_TAB = "{{ session('active_tab', 'usage') }}";</script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        function openModal(id) {
            var el = document.getElementById(id);
            if (el) el.classList.add('show');
        }
        function closeModal(id) {
            var el = document.getElementById(id);
            if (el) { el.classList.remove('show'); var f = el.querySelector('form'); if (f) f.reset(); }
        }

        var supplierBaseUrl = "{{ url('admin/supplier-contacts') }}";

        document.getElementById('openAddSupplierBtn').addEventListener('click', function() {
            openModal('addSupplierModal');
        });
        document.getElementById('closeAddSupplierBtn').addEventListener('click', function() {
            closeModal('addSupplierModal');
        });
        document.getElementById('cancelAddSupplierBtn').addEventListener('click', function() {
            closeModal('addSupplierModal');
        });
        document.getElementById('closeEditSupplierBtn').addEventListener('click', function() {
            closeModal('editSupplierModal');
        });
        document.getElementById('cancelEditSupplierBtn').addEventListener('click', function() {
            closeModal('editSupplierModal');
        });

        function openEditSupplier(id, name, company, phone, email, address) {
            document.getElementById('editSupplierForm').action = supplierBaseUrl + '/' + id;
            document.getElementById('editSupName').value    = name    || '';
            document.getElementById('editSupCompany').value = company || '';
            document.getElementById('editSupPhone').value   = phone   || '';
            document.getElementById('editSupEmail').value   = email   || '';
            document.getElementById('editSupAddress').value = address || '';
            openModal('editSupplierModal');
        }

        function confirmDeleteSupplier(id, name) {
            if (!confirm('Delete supplier "' + name + '"? This cannot be undone.')) return;
            var form = document.getElementById('deleteSupplierForm');
            form.action = supplierBaseUrl + '/' + id;
            form.submit();
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();

            var currentStatusFilter = 'active';

            function applyUsageFilters() {
                var q = (document.getElementById('projectSearch').value || '').toLowerCase();
                document.querySelectorAll('#usageTable tbody tr').forEach(function(row) {
                    var status = (row.dataset.status || '').toLowerCase();
                    var matchSearch = row.textContent.toLowerCase().indexOf(q) !== -1;
                    var matchFilter = currentStatusFilter === 'active'
                        ? status !== 'archived' && status !== 'completed'
                        : currentStatusFilter === 'completed'
                        ? status === 'completed'
                        : status === 'archived';
                    row.style.display = (matchSearch && matchFilter) ? '' : 'none';
                });
            }

            document.getElementById('projectSearch')?.addEventListener('keyup', applyUsageFilters);

            document.querySelectorAll('#usageFilterTabs .filter-tab').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('#usageFilterTabs .filter-tab').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentStatusFilter = this.dataset.filter;
                    applyUsageFilters();
                });
            });

            applyUsageFilters();
        });
    </script>
</body>
</html>
