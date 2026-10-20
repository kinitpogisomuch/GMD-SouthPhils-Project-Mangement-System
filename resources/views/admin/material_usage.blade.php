<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materials | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        /* Project Material Usage: seven columns — tighter cells so Actions stays in view without scrolling sideways */
        #usageActiveTable th, #usageActiveTable td,
        #usageGroupTable th, #usageGroupTable td { padding-left: 14px; padding-right: 14px; }
        #usageActiveTable th:first-child, #usageActiveTable td:first-child,
        #usageGroupTable th:first-child, #usageGroupTable td:first-child { padding-left: 24px; }
        #usageActiveTable td:nth-child(1) { min-width: 170px; max-width: 220px; white-space: normal; }
        #usageActiveTable td:nth-child(2) { max-width: 230px; white-space: normal; }
        #usageActiveTable .client-pill { white-space: normal; line-height: 1.3; }
        /* every column after the name sits centered under its header */
        #usageActiveTable th:not(:first-child), #usageActiveTable td:not(:first-child),
        #usageGroupTable th:not(:first-child), #usageGroupTable td:not(:first-child) { text-align: center; }
        #usageActiveTable td:nth-child(2) .client-pill { display: inline-block; }
        #usageActiveTable .action-cell, #usageGroupTable .action-cell { justify-content: center; }
    </style>
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
            {{-- Saved (e.g. purchase recorded, usage logged): the small dark toast below the header
                 (same as "Salary record saved."), shown for 3 seconds --}}
            <div class="toast" id="materialsToast" role="status">
                <i data-lucide="check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
            <script>
                window.addEventListener('load', function () {
                    var t = document.getElementById('materialsToast');
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
            <div class="alert-banner error"><i data-lucide="alert-circle"></i> {{ session('error') }}</div>
            @endif

            {{-- ── Supplier Contacts Section ─────────────────────────────── --}}
            <div class="table-card" style="margin-bottom:20px;">
                <div style="display:flex;align-items:center;gap:16px;border-bottom:1px solid var(--border);padding:0 20px 14px;margin-bottom:0;">
                    <div style="flex-shrink:0;">
                        <div style="font-size:15px;font-weight:700;color:var(--dark);">Supplier Contacts</div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;">Manually manage your supplier directory.</div>
                    </div>
                    <div style="flex:1;display:flex;justify-content:center;">
                        <div style="display:flex;align-items:center;gap:8px;background:#f5f6f8;border-radius:999px;padding:0 18px;border:1px solid var(--border);width:280px;height:42px;">
                            <i data-lucide="search" style="width:15px;height:15px;color:#888;flex-shrink:0;"></i>
                            <input type="text" id="supplierSearch" placeholder="Search..."
                                style="border:none;background:transparent;font-size:13px;outline:none;width:100%;color:var(--dark);height:100%;"
                                oninput="filterSuppliers(this.value)">
                        </div>
                    </div>
                    <button type="button" id="openAddSupplierBtn"
                        style="display:inline-flex;align-items:center;gap:7px;background:var(--dark);color:#fff;border:none;border-radius:10px;padding:0 18px;height:42px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;flex-shrink:0;transition:opacity .15s;"
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
                <div style="max-height:285px;overflow-y:auto;">
                    <table class="data-table" id="suppliersTable" style="margin:0;">
                        <thead style="position:sticky;top:0;z-index:2;">
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="supplierTbody">
                            @foreach($suppliers as $sup)
                            <tr data-sup-name="{{ strtolower($sup->name) }}" data-sup-phone="{{ $sup->phone }}" data-sup-addr="{{ strtolower($sup->address ?? '') }}">
                                <td style="font-size:14px;font-weight:700;color:var(--dark);">{{ $sup->name }}</td>
                                <td style="font-size:14px;font-weight:700;color:var(--dark);">{{ $sup->phone ?? '—' }}</td>
                                <td style="font-size:14px;font-weight:700;color:var(--dark);">{{ $sup->email ?? '—' }}</td>
                                <td style="font-size:14px;font-weight:700;color:var(--dark);">{{ $sup->address ?? '—' }}</td>
                                <td class="action-cell">
                                    <button class="action-btn edit" title="Edit"
                                        onclick="openEditSupplier({{ $sup->id }}, {{ json_encode($sup->name) }}, {{ json_encode($sup->phone) }}, {{ json_encode($sup->email) }}, {{ json_encode($sup->address) }})">
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

            {{-- ── Project Material Usage: active projects listed directly; completed / archived grouped by client ── --}}
            <div class="table-card">
                <div style="display:flex;align-items:center;gap:16px;padding:0 20px 14px;border-bottom:1px solid var(--border);margin-bottom:0;">
                    <div style="flex-shrink:0;">
                        <div style="font-size:15px;font-weight:700;color:var(--dark);">Project Material Usage</div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;">Track BOM planned materials, actual purchases, and cost variance per project.</div>
                    </div>
                    <div style="flex:1;display:flex;justify-content:center;">
                        <div style="display:flex;align-items:center;gap:8px;background:#f5f6f8;border-radius:999px;padding:0 18px;border:1px solid var(--border);width:280px;height:42px;">
                            <i data-lucide="search" style="width:15px;height:15px;color:#888;flex-shrink:0;"></i>
                            <input type="text" id="projectSearch" placeholder="{{ $tab === 'active' ? 'Search project or client...' : 'Search client...' }}"
                                style="border:none;background:transparent;font-size:13px;outline:none;width:100%;color:var(--dark);height:100%;">
                        </div>
                    </div>
                    <div class="filter-tabs" id="usageFilterTabs" style="flex-shrink:0;">
                        <button type="button" class="filter-tab {{ $tab === 'active' ? 'active' : '' }}" data-filter="active">
                            Active
                            <span class="filter-count">{{ $activeProjects->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab {{ $tab === 'completed' ? 'active' : '' }}" data-filter="completed">
                            Completed
                            <span class="filter-count">{{ $completedGroups->sum('project_count') }}</span>
                        </button>
                        <button type="button" class="filter-tab {{ $tab === 'archived' ? 'active' : '' }}" data-filter="archived">
                            Archived
                            <span class="filter-count">{{ $archivedGroups->sum('project_count') }}</span>
                        </button>
                    </div>
                </div>

                {{-- Active: one row per project --}}
                <div class="usage-pane" data-pane="active" style="max-height:399px;overflow-y:auto;overflow-x:auto;{{ $tab === 'active' ? '' : 'display:none;' }}">
                    <table class="data-table" id="usageActiveTable" style="margin:0;">
                        <thead style="position:sticky;top:0;z-index:2;">
                            <tr>
                                <th>Project Name</th>
                                <th>Client</th>
                                <th>Current Phase</th>
                                <th>Planned Materials</th>
                                <th>Usage Entries Logged</th>
                                <th>Total Qty Used</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeProjects as $project)
                            @php
                                $namePrefix = '';
                                $nameMain   = $project->name;
                                if (preg_match('/^(Fabrication of)\s+(.+)$/i', $project->name, $nm)) {
                                    $namePrefix = $nm[1];
                                    $nameMain   = $nm[2];
                                }
                                $matCount   = $project->activeMaterials->count();
                                $usageCount = $project->activeMaterialUsages->count();
                            @endphp
                            <tr data-search="{{ strtolower($project->name . ' ' . $project->live_client_name) }}">
                                <td style="overflow:hidden;">
                                    <span style="display:inline-flex;flex-direction:column;max-width:100%;min-width:0;">
                                        @if($namePrefix)
                                            <span style="font-size:9px;font-weight:700;color:var(--muted);letter-spacing:.05em;line-height:1.2;text-transform:uppercase;white-space:nowrap;">{{ $namePrefix }}</span>
                                        @endif
                                        <span style="font-size:12.5px;font-weight:800;color:var(--dark);line-height:1.3;white-space:normal;word-break:break-word;">{{ $nameMain }}</span>
                                    </span>
                                </td>
                                <td><span class="client-pill">{{ $project->live_client_name }}</span></td>
                                <td>
                                    <span class="status-badge ongoing">{{ ucfirst(str_replace('_', ' ', $project->current_phase ?? 'Planning')) }}</span>
                                </td>
                                <td>
                                    <span style="font-size:14px;font-weight:700;color:var(--dark);">{{ $matCount }}</span>
                                    <span style="font-size:13px;font-weight:400;color:var(--muted);"> material{{ $matCount !== 1 ? 's' : '' }}</span>
                                </td>
                                <td>
                                    <span style="font-size:14px;font-weight:700;color:var(--dark);">{{ $usageCount }}</span>
                                    <span style="font-size:13px;font-weight:400;color:var(--muted);"> entr{{ $usageCount !== 1 ? 'ies' : 'y' }}</span>
                                </td>
                                <td><strong style="font-size:14px;font-weight:700;color:var(--dark);">{{ number_format($project->activeMaterialUsages->sum('quantity_used'), 0) }}</strong></td>
                                <td class="action-cell">
                                    <a href="{{ route('admin.material_usage.detail', $project->id) }}" class="action-btn view" title="View Materials">
                                        <i data-lucide="eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Completed / Archived: one row per client --}}
                <div class="usage-pane" data-pane="groups" style="max-height:399px;overflow-y:auto;overflow-x:auto;{{ $tab === 'active' ? 'display:none;' : '' }}">
                    <table class="data-table" id="usageGroupTable" style="margin:0;">
                        <thead style="position:sticky;top:0;z-index:2;">
                            <tr>
                                <th>Client</th>
                                <th>No. of Projects</th>
                                <th>Planned Materials</th>
                                <th>Usage Entries Logged</th>
                                <th>Total Qty Used</th>
                                <th>Last Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(['completed' => $completedGroups, 'archived' => $archivedGroups] as $groupStatus => $groups)
                            @foreach($groups as $group)
                            <tr data-status="{{ $groupStatus }}" data-search="{{ strtolower($group['client']) }}" style="{{ $tab === $groupStatus ? '' : 'display:none;' }}">
                                <td><span class="client-pill">{{ $group['client'] }}</span></td>
                                <td style="text-align:center;">{{ $group['project_count'] }}</td>
                                <td>
                                    <span style="font-size:14px;font-weight:700;color:var(--dark);">{{ $group['materials_count'] }}</span>
                                    <span style="font-size:13px;font-weight:400;color:var(--muted);"> material{{ $group['materials_count'] !== 1 ? 's' : '' }}</span>
                                </td>
                                <td>
                                    <span style="font-size:14px;font-weight:700;color:var(--dark);">{{ $group['usage_count'] }}</span>
                                    <span style="font-size:13px;font-weight:400;color:var(--muted);"> entr{{ $group['usage_count'] !== 1 ? 'ies' : 'y' }}</span>
                                </td>
                                <td><strong style="font-size:14px;font-weight:700;color:var(--dark);">{{ number_format($group['total_qty_used'], 0) }}</strong></td>
                                <td style="font-size:14px;font-weight:700;color:var(--dark);">{{ $group['last_created_at']?->format('M d, Y') ?? '—' }}</td>
                                <td class="action-cell">
                                    <a href="{{ route('admin.material_usage.client', urlencode($group['client_key'])) }}?tab={{ $groupStatus }}"
                                       class="action-btn view" title="View Client's Projects">
                                        <i data-lucide="eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                            @endforeach
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
                        <label>Supplier Name</label>
                        <input type="text" name="name" placeholder="e.g. Steel Supply Co." required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" id="addSupPhone"
                               placeholder="e.g. 0917-123-4567"
                               maxlength="13"
                               oninput="formatSupPhone(this)"
                               onblur="validateSupPhone(this)">
                        <span class="emp-field-err" id="addSupPhoneErr" style="display:none;color:#dc2626;font-size:11.5px;margin-top:4px;"></span>
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
                        <label>Supplier Name</label>
                        <input type="text" name="name" id="editSupName" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" id="editSupPhone"
                               placeholder="e.g. 0917-123-4567"
                               maxlength="13"
                               oninput="formatSupPhone(this)"
                               onblur="validateSupPhone(this)">
                        <span class="emp-field-err" id="editSupPhoneErr" style="display:none;color:#dc2626;font-size:11.5px;margin-top:4px;"></span>
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

        function filterSuppliers(q) {
            q = (q || '').toLowerCase().trim();
            var tbody = document.getElementById('supplierTbody');
            var noMatch = document.getElementById('supplierNoMatch');
            var visibleCount = 0;

            document.querySelectorAll('#supplierTbody tr:not(#supplierNoMatch)').forEach(function(row) {
                var name  = row.dataset.supName  || '';
                var phone = row.dataset.supPhone || '';
                var addr  = row.dataset.supAddr  || '';
                var show  = !q || name.includes(q) || phone.includes(q) || addr.includes(q);
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            if (!noMatch) {
                noMatch = document.createElement('tr');
                noMatch.id = 'supplierNoMatch';
                noMatch.innerHTML = '<td colspan="5" style="text-align:center;padding:40px 20px;color:var(--muted);font-size:13px;">No suppliers match your search.</td>';
                tbody.appendChild(noMatch);
            }
            noMatch.style.display = visibleCount === 0 ? '' : 'none';
        }

        function openEditSupplier(id, name, phone, email, address) {
            document.getElementById('editSupplierForm').action = supplierBaseUrl + '/' + id;
            document.getElementById('editSupName').value    = name    || '';
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

            var currentStatusFilter = @json($tab);

            var usageFilterMessages = {
                'active':    'No active projects yet.',
                'completed': 'No completed projects yet.',
                'archived':  'No archived projects.'
            };

            // Active shows the project list; Completed / Archived show the client groups for that status
            function applyUsageFilters() {
                var q          = (document.getElementById('projectSearch').value || '').toLowerCase().trim();
                var isActive   = currentStatusFilter === 'active';
                var tableId    = isActive ? 'usageActiveTable' : 'usageGroupTable';
                var tbody      = document.querySelector('#' + tableId + ' tbody');
                var noMatchRow = tbody.querySelector('.usage-no-match');
                var visibleCount = 0;

                document.querySelectorAll('.usage-pane').forEach(function (pane) {
                    pane.style.display = (pane.dataset.pane === 'active') === isActive ? '' : 'none';
                });
                document.getElementById('projectSearch').placeholder = isActive ? 'Search project or client...' : 'Search client...';

                tbody.querySelectorAll('tr:not(.usage-no-match)').forEach(function(row) {
                    var matchSearch = !q || (row.dataset.search || '').indexOf(q) !== -1;
                    var matchFilter = isActive || row.dataset.status === currentStatusFilter;
                    var show = matchSearch && matchFilter;
                    row.style.display = show ? '' : 'none';
                    if (show) visibleCount++;
                });

                // Build / update styled empty state row
                if (!noMatchRow) {
                    noMatchRow = document.createElement('tr');
                    noMatchRow.className = 'usage-no-match';
                    tbody.appendChild(noMatchRow);
                }
                var msg = q
                    ? 'No projects match &ldquo;' + q + '&rdquo;.'
                    : (usageFilterMessages[currentStatusFilter] || 'No projects in this category.');
                noMatchRow.innerHTML =
                    '<td colspan="7" style="text-align:center;padding:60px 20px;color:var(--muted);">' +
                    '<i data-lucide="folder-open" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>' +
                    '<div style="font-size:14px;font-weight:700;">' + msg + '</div>' +
                    '<div style="font-size:13px;margin-top:4px;">Try switching to a different tab or add a new project.</div>' +
                    '</td>';
                noMatchRow.style.display = visibleCount === 0 ? '' : 'none';
                if (visibleCount === 0 && typeof lucide !== 'undefined') lucide.createIcons();
            }

            document.getElementById('projectSearch')?.addEventListener('keyup', applyUsageFilters);

            document.querySelectorAll('#usageFilterTabs .filter-tab').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('#usageFilterTabs .filter-tab').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentStatusFilter = this.dataset.filter;
                    applyUsageFilters();
                    try {
                        var url = new URL(location.href);
                        url.searchParams.set('tab', currentStatusFilter);
                        history.replaceState(null, '', url);
                    } catch (e) {}
                });
            });

            applyUsageFilters();
        });
        // ── Supplier phone helpers — format: 09XX-XXX-XXXX ───────────────
        function formatSupPhone(input) {
            var digits = input.value.replace(/\D/g, '').substring(0, 11);
            var formatted = digits;
            if (digits.length > 4) {
                formatted = digits.substring(0, 4) + '-' + digits.substring(4);
            }
            if (digits.length > 7) {
                formatted = digits.substring(0, 4) + '-' + digits.substring(4, 7) + '-' + digits.substring(7);
            }
            input.value = formatted;
        }

        function validateSupPhone(input) {
            var errId = input.id + 'Err';
            var err   = document.getElementById(errId);
            var val   = input.value.trim();
            var msg   = '';
            if (val && !/^09\d{2}-\d{3}-\d{4}$/.test(val)) {
                msg = 'Must be in format 09XX-XXX-XXXX (11 digits starting with 09).';
            }
            if (err) {
                err.textContent   = msg;
                err.style.display = msg ? 'block' : 'none';
            }
            input.style.borderColor = msg ? '#dc2626' : '';
            return !msg;
        }
    </script>
</body>
</html>
