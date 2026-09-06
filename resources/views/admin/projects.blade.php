<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        .mat-combo { position: relative; width: 100%; }
        .mat-combo-dropdown {
            display: none;
            position: fixed;
            max-height: 400px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid rgba(0,0,0,0.12);
            border-radius: 14px;
            box-shadow: 0 12px 32px rgba(0,0,0,.18);
            z-index: 1000;
            padding: 12px;
            columns: 3 180px;
            column-gap: 16px;
        }
        .mat-combo-dropdown.show { display: block; }
        .mat-combo-category {
            break-inside: avoid;
            margin-bottom: 8px;
        }
        .mat-combo-group {
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #fff;
            background: var(--dark);
            padding: 5px 10px;
            border-radius: 6px;
            margin-bottom: 4px;
            display: block;
        }
        .mat-combo-item {
            padding: 6px 10px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 500;
            color: var(--dark);
            cursor: pointer;
            display: block;
        }
        .mat-combo-item:hover {
            background: #f0f4ff;
            color: #2563EB;
            font-weight: 600;
        }
        .mat-combo-item.disabled {
            color: var(--muted);
            cursor: not-allowed;
            opacity: 0.6;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .mat-combo-item.disabled:hover {
            background: none;
        }
        .mat-combo-item-badge {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--danger);
            white-space: nowrap;
        }
        .mat-combo-empty {
            padding: 10px;
            font-size: 12px;
            color: var(--muted);
            text-align: center;
        }
        .mat-combo-warning {
            display: none;
            margin-top: 6px;
            font-size: 12px;
            font-weight: 700;
            color: var(--danger);
        }
        .mat-combo-warning.show {
            display: block;
        }
    </style>
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">
            <div class="page-header">
                <div>
                    <h1>Projects</h1>
                    <p>Manage storage tank fabrication projects, timelines, and progress.</p>
                </div>
                <button class="add-btn" type="button" id="openAddProjectModal">
                    <i data-lucide="plus"></i>
                    Add Project
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

            {{-- ── Projects Financial Summary ── --}}
            <div class="pf-summary-grid">
                <div class="pf-summary-card">
                    <div class="pf-summary-icon" style="background:#d1fae5;color:#059669;">
                        <i data-lucide="banknote"></i>
                    </div>
                    <div class="pf-summary-body">
                        <div class="pf-summary-label">Total Revenue</div>
                        <div class="pf-summary-value">₱{{ number_format($totalRevenue, 2) }}</div>
                        <div class="pf-summary-sub">Contract value of active projects</div>
                    </div>
                </div>

                <div class="pf-summary-card">
                    <div class="pf-summary-icon" style="background:#dbeafe;color:#2563eb;">
                        <i data-lucide="folder-kanban"></i>
                    </div>
                    <div class="pf-summary-body">
                        <div class="pf-summary-label">Active Projects</div>
                        <div class="pf-summary-value">{{ $activeProjectsCount }}</div>
                        <div class="pf-summary-sub">Currently in progress</div>
                    </div>
                </div>

                <div class="pf-summary-card">
                    <div class="pf-summary-icon" style="background:{{ $netProfit < 0 ? '#fee2e2' : '#ede9fe' }};color:{{ $netProfit < 0 ? '#dc2626' : '#7c3aed' }};">
                        <i data-lucide="trending-up"></i>
                    </div>
                    <div class="pf-summary-body">
                        <div class="pf-summary-label">Net Profit</div>
                        <div class="pf-summary-value" style="{{ $netProfit < 0 ? 'color:#dc2626;' : '' }}">
                            {{ $netProfit < 0 ? '-' : '' }}₱{{ number_format(abs($netProfit), 2) }}
                        </div>
                        <div class="pf-summary-sub">Revenue − Material − Labor − Overhead</div>
                    </div>
                </div>
            </div>

            <div class="table-card" id="clientListCard">
                <div class="table-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="clientListSearch" placeholder="Search client...">
                    </div>
                    <select class="filter-select" id="clientSortSelect">
                        <option value="default">Sort: Active Projects First</option>
                        <option value="alpha-asc">Sort: A–Z</option>
                        <option value="alpha-desc">Sort: Z–A</option>
                    </select>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="clientListTable">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th style="text-align:center;">Total Projects</th>
                                <th style="text-align:center;">Active</th>
                                <th style="text-align:center;">Completed</th>
                                <th style="text-align:center;">Archived</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clientGroups as $g)
                            <tr data-search="{{ strtolower($g['client']) }}" onclick="window.location='{{ route('admin.projects.client', $g['client']) }}'" style="cursor:pointer;">
                                <td><span class="client-pill">{{ $g['client'] }}</span></td>
                                <td style="text-align:center;font-weight:800;">{{ $g['total'] }}</td>
                                <td style="text-align:center;color:#2563EB;font-weight:700;">{{ $g['active'] }}</td>
                                <td style="text-align:center;color:#207A3A;font-weight:700;">{{ $g['completed'] }}</td>
                                <td style="text-align:center;color:#6B7280;font-weight:700;">{{ $g['archived'] }}</td>
                                <td class="action-cell" style="text-align:center;">
                                    <a href="{{ route('admin.projects.client', $g['client']) }}" class="action-btn view" title="View Client's Projects" onclick="event.stopPropagation()">
                                        <i data-lucide="eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="inbox" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;">No clients with projects yet.</div>
                                    <div style="font-size:13px;margin-top:4px;">Click <strong>Add Project</strong> to get started.</div>
                                </td>
                            </tr>
                            @endforelse
                            @if($clientGroups->isNotEmpty())
                            <tr id="clientListEmptyRow" style="display:none;">
                                <td colspan="6" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="search-x" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;">No clients match your search.</div>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- ===================== SELECT CLIENT MODAL ===================== -->
    <div class="modal-overlay" id="selectClientModal">
        <div class="modal-card" style="max-width:560px;">
            <div class="modal-header">
                <div>
                    <h2>Select Client</h2>
                    <p>Choose a client before filling in the project details.</p>
                </div>
                <button class="modal-close" type="button" id="closeSelectClientModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="search-box" style="margin:0 auto 14px;max-width:100%;">
                <i data-lucide="search"></i>
                <input type="text" id="clientSelectSearch" placeholder="Search by name, contact, or location...">
            </div>

            <div id="clientSelectList" class="cs-list">
                <p style="text-align:center;color:var(--muted);padding:32px 0;font-size:14px;">Loading clients...</p>
            </div>

            <div class="modal-actions" style="margin-top:16px;">
                <button type="button" class="save-btn" id="continueSelectClient">
                    Continue <i data-lucide="arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ===================== SELECT TEMPLATE MODAL ===================== -->
    <div class="modal-overlay" id="selectTemplateModal">
        <div class="modal-card" style="max-width:560px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;">
            <div class="modal-header" style="flex-shrink:0;">
                <div>
                    <h2>Choose a Starting Point</h2>
                    <p>Reuse a saved template or start with a blank project.</p>
                </div>
                <button class="modal-close" type="button" id="closeSelectTemplateModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <!-- Quotation Request prefill banner (shown only when converting a client's request) -->
            <div id="templatePrefillBanner" class="alert-banner info" style="display:none;margin-bottom:14px;flex-shrink:0;">
                <i data-lucide="info"></i>
                <div>
                    <strong class="qr-client-heading">Converting a client's quotation request.</strong>
                    <div class="qr-summary" style="margin-top:2px;"></div>
                </div>
            </div>

            <div class="search-box" style="margin:0 auto 14px;max-width:100%;flex-shrink:0;">
                <i data-lucide="search"></i>
                <input type="text" id="templateSelectSearch" placeholder="Search templates...">
            </div>

            <div style="overflow-y:auto;flex:1;">
                <div class="client-select-item" id="customTemplateOption" style="border-style:dashed;margin-bottom:14px;">
                    <div class="cs-avatar" style="background:var(--accent-soft);">
                        <i data-lucide="file-plus-2" style="width:20px;height:20px;color:var(--dark);"></i>
                    </div>
                    <div class="cs-info">
                        <div class="cs-name">Start from Scratch</div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;">Build a custom project with no preset tank specs</div>
                    </div>
                    <div class="cs-check">
                        <i data-lucide="check-circle-2" style="width:20px;height:20px;color:var(--dark);"></i>
                    </div>
                </div>

                <div id="templateSelectList" class="cs-list" style="max-height:none;overflow-y:visible;">
                    <!-- rendered by JS -->
                </div>
            </div>

            <div class="modal-actions" style="flex-shrink:0;margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
                <button type="button" class="cancel-btn" id="backSelectTemplate"><i data-lucide="arrow-left"></i> Back</button>
                <button type="button" class="save-btn" id="continueSelectTemplate">
                    Continue <i data-lucide="arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ===================== ADD PROJECT MODAL ===================== -->
    <div class="modal-overlay" id="addProjectModal">
        <div class="modal-card modal-large" style="display:flex;flex-direction:column;overflow:hidden;">
            <div class="modal-header">
                <div>
                    <h2>Add Project</h2>
                    <p>Fill in the project details below.</p>
                </div>
                <button class="modal-close" type="button" id="closeAddProjectModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form id="addProjectForm" method="POST" action="{{ route('admin.project.store') }}" style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
                @csrf

                <input type="hidden" name="status"         value="planning">
                <input type="hidden" name="progress"       value="0">
                <input type="hidden" name="payment_status" value="Pending">
                <input type="hidden" name="client"         id="projClientNameHidden">
                <input type="hidden" name="contact_number" id="projContactHidden">
                <input type="hidden" name="email"          id="projEmailHidden">
                <input type="hidden" name="address"        id="projAddressHidden">
                <input type="hidden" name="capacity"       id="projCapacityHidden">
                <input type="hidden" name="dimensions"     id="projDimensionsHidden">
                <input type="hidden" name="quotation_request_id" id="projQuotationRequestIdHidden">

                {{-- Scrollable body --}}
                <div style="overflow-y:auto;flex:1;padding:0 28px 8px;">

                    <!-- Quotation Request prefill banner (shown only when converting a client's request) -->
                    <div id="quotationPrefillBanner" class="alert-banner info" style="display:none;margin-top:16px;">
                        <i data-lucide="info"></i>
                        <div>
                            <strong class="qr-client-heading">Converting a client's quotation request.</strong>
                            <div class="qr-summary" style="margin-top:2px;"></div>
                        </div>
                    </div>

                    <!-- Project Details -->
                    <div class="form-section-label">Project Details</div>
                    <div class="form-grid">
                        <div class="form-group form-group-full">
                            <label>Project Name</label>
                            <select id="projectNameSelect" required>
                                <option value="" disabled selected hidden>Select project name</option>
                                <option value="Fabrication of Fuel Day Tank">Fabrication of Fuel Day Tank</option>
                                <option value="Fabrication of Cooking Oil Storage Tank">Fabrication of Cooking Oil Storage Tank</option>
                                <option value="Fabrication of Underground Fuel Storage Tanks">Fabrication of Underground Fuel Storage Tanks</option>
                                <option value="Fabrication of Aboveground Fuel Storage Tanks">Fabrication of Aboveground Fuel Storage Tanks</option>
                                <option value="Fabrication of Polymer Tanks">Fabrication of Polymer Tanks</option>
                                <option value="Fabrication of Aboveground Water Storage Tanks">Fabrication of Aboveground Water Storage Tanks</option>
                                <option value="others">Others</option>
                            </select>
                            <input type="hidden" name="name" id="projectNameHidden">
                            <input type="text" id="projectNameOther"
                                   placeholder="Enter custom project name"
                                   style="display:none;margin-top:8px;">
                        </div>
                    </div>

                    <!-- Tank Specifications -->
                    <div style="margin-top:18px;margin-bottom:10px;display:flex;align-items:center;gap:10px;">
                        <div style="background:linear-gradient(180deg,#333 0%,#2a2a2a 100%);color:#fff;font-size:10px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;padding:4px 12px;border-radius:999px;">Project Specifications</div>
                        <div style="flex:1;height:1px;background:linear-gradient(90deg,#333,transparent);"></div>
                    </div>

                    <div id="tankItemsContainer">
                        <!-- Tank rows injected by JS -->
                    </div>
                    <input type="hidden" name="from_existing_template" id="fromExistingTemplateHidden" value="0">

                    <!-- Bill of Materials (only shown when reusing a template) -->
                    <div id="bomSection" style="display:none;">
                        <div style="margin-top:18px;margin-bottom:10px;display:flex;align-items:center;gap:10px;">
                            <div style="background:linear-gradient(180deg,#333 0%,#2a2a2a 100%);color:#fff;font-size:10px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;padding:4px 12px;border-radius:999px;">Bill of Materials</div>
                            <div style="flex:1;height:1px;background:linear-gradient(90deg,#333,transparent);"></div>
                        </div>

                        <p id="materialsEmptyHint" style="font-size:12.5px;color:var(--muted);margin:0 0 10px;">No materials added yet. Materials can also be priced later on the Project Materials page.</p>
                        <div id="materialsTableWrapper" style="display:none;overflow-x:auto;margin-bottom:10px;border:1px solid rgba(0,0,0,0.10);border-radius:12px;">
                            <table style="width:100%;border-collapse:collapse;min-width:520px;">
                                <thead>
                                    <tr style="background:var(--cream-soft,#f5f5f5);">
                                        <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);">Material Name</th>
                                        <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:80px;">Qty</th>
                                        <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:100px;">Unit</th>
                                        <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:120px;">Price</th>
                                        <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:120px;">Total</th>
                                        <th style="width:36px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="materialsContainer">
                                    <!-- Material rows injected by JS -->
                                </tbody>
                            </table>
                        </div>
                        <button type="button" id="addMaterialBtn" class="cancel-btn" style="display:inline-flex;width:auto;margin-bottom:6px;">
                            <i data-lucide="plus"></i> Add Material
                        </button>
                    </div>

                    <!-- Schedule -->
                    <div class="form-section-label" style="margin-top:18px;">Schedule</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" id="addProjectStartDate" required>
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" id="addProjectEndDate" required>
                        </div>
                    </div>

                </div>

                {{-- Fixed action buttons --}}
                <div class="modal-actions" style="flex-shrink:0;border-top:1px solid var(--border);padding-top:16px;margin-top:0;">
                    <button type="button" class="cancel-btn" id="backAddProject"><i data-lucide="arrow-left"></i> Back</button>
                    <button type="submit" class="save-btn" id="saveProjectBtn">
                        <i data-lucide="save"></i>
                        Save Project
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include('admin.partials.material_catalog')

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        @php $clientListUrl = route('admin.client.list'); @endphp
        const CLIENT_LIST_URL = "{{ $clientListUrl }}";

        const PROJECT_CLIENT_GROUPS_URL = "{{ route('admin.projects.client_groups') }}";
        const PROJECT_CLIENT_URL_TEMPLATE = "{{ route('admin.projects.client', '__CLIENT__') }}";

        var allClients = [];

        function fetchClients() {
            return fetch(CLIENT_LIST_URL, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) { allClients = data; return data; })
            .catch(function(err) { console.error('Failed to fetch clients:', err); return []; });
        }

        function openModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
        }

        function closeModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
        }

        var selectedClient = null;
        var selectedTemplateId = 'custom';

        function renderClientSelectList(clients, filter) {
            var list = document.getElementById('clientSelectList');
            if (!list) return;
            var q = (filter || '').toLowerCase();
            var filtered = q
                ? clients.filter(function(c) {
                    return c.name.toLowerCase().indexOf(q) !== -1 ||
                           (c.address && c.address.toLowerCase().indexOf(q) !== -1);
                  })
                : clients;

            list.innerHTML = '';

            if (filtered.length === 0) {
                list.innerHTML = '<p style="text-align:center;color:var(--muted);padding:20px 0;font-size:14px;font-weight:700;">No clients found. Add clients via the <strong>Clients</strong> sidebar link.</p>';
                return;
            }

            filtered.forEach(function(client) {
                var isSelected = selectedClient && selectedClient.id === client.id;
                var item = document.createElement('div');
                item.className = 'client-select-item' + (isSelected ? ' selected' : '');

                // Circle avatar: photo or initial
                var avatarHtml = client.profile_photo
                    ? '<img src="' + client.profile_photo + '" class="cs-avatar-img" alt="' + client.name + '">'
                    : '<span class="cs-avatar-init">' + client.name.charAt(0).toUpperCase() + '</span>';

                var pillStyle = 'display:inline-flex;align-items:center;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;margin-right:4px;margin-top:3px;';
                item.innerHTML =
                    '<div class="cs-avatar">' + avatarHtml + '</div>' +
                    '<div class="cs-info">' +
                        '<div class="cs-name">' + client.name + '</div>' +
                        '<div style="margin-top:4px;">' +
                            '<div style="display:flex;flex-wrap:wrap;gap:4px;">' +
                                (client.contact ? '<span style="' + pillStyle + 'background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;">' + client.contact + '</span>' : '') +
                                (client.email   ? '<span style="' + pillStyle + 'background:#F0FDF4;color:#15803D;border:1px solid #BBF7D0;">' + client.email   + '</span>' : '') +
                            '</div>' +
                            (client.address ? '<div style="margin-top:3px;"><span style="' + pillStyle + 'background:#FEF9C3;color:#A16207;border:1px solid #FDE68A;">' + client.address + '</span></div>' : '') +
                        '</div>' +
                    '</div>' +
                    '<div class="cs-check" style="display:' + (isSelected ? 'flex' : 'none') + ';">' +
                        '<i data-lucide="check-circle-2" style="width:20px;height:20px;color:var(--dark);"></i>' +
                    '</div>';

                item.addEventListener('click', function() {
                    selectedClient = client;
                    document.querySelectorAll('.client-select-item').forEach(function(el) {
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

        function openSelectClientModal() {
            selectedClient = null;
            quotationConversionData = null; // starting a normal flow — discard any prior conversion state
            var si = document.getElementById('clientSelectSearch');
            if (si) si.value = '';
            var list = document.getElementById('clientSelectList');
            list.innerHTML = '<p style="text-align:center;color:var(--muted);padding:20px 0;">Loading clients...</p>';
            openModal('selectClientModal');
            fetchClients().then(function(clients) { renderClientSelectList(clients, ''); });
        }

        function populateClientFields(client) {
            document.getElementById('projClientNameHidden').value = client.name;
            document.getElementById('projContactHidden').value    = client.contact || '';
            document.getElementById('projEmailHidden').value      = client.email || '';
            document.getElementById('projAddressHidden').value    = client.address || '';
        }

        function updateDimensionFields() {
            // Guard: elements were removed in favour of per-tank dynamic rows
            var shape = document.getElementById('tankShape');
            if (!shape) return;
            var isCyl = (shape.value === 'cylindrical');
            var dim1Label = document.getElementById('dim1Label');
            var dim2Label = document.getElementById('dim2Label');
            var dim3Group = document.getElementById('dim3Group');
            if (dim1Label) dim1Label.textContent = isCyl ? 'Diameter (m)' : 'Length (m)';
            if (dim2Label) dim2Label.textContent = isCyl ? 'Height (m)'   : 'Width (m)';
            if (dim3Group) dim3Group.style.display = isCyl ? 'none' : '';
            ['dim1','dim2','dim3'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.value = '';
            });
            var cap = document.getElementById('projCapacityDisplay');
            var capH = document.getElementById('projCapacityHidden');
            var dimH = document.getElementById('projDimensionsHidden');
            if (cap)  cap.value  = '';
            if (capH) capH.value = '';
            if (dimH) dimH.value = '';
        }

        function computeCapacity() {
            var shape = document.getElementById('tankShape').value;
            var capacity = 0, dimsStr = '';
            if (shape === 'cylindrical') {
                var d = parseFloat(document.getElementById('dim1').value) || 0;
                var h = parseFloat(document.getElementById('dim2').value) || 0;
                if (d > 0 && h > 0) {
                    capacity = Math.PI * Math.pow(d / 2, 2) * h * 1000;
                    dimsStr  = 'Cylindrical: Ø' + d + 'm × H' + h + 'm';
                }
            } else {
                var l  = parseFloat(document.getElementById('dim1').value) || 0;
                var w  = parseFloat(document.getElementById('dim2').value) || 0;
                var hr = parseFloat(document.getElementById('dim3').value) || 0;
                if (l > 0 && w > 0 && hr > 0) {
                    capacity = l * w * hr * 1000;
                    dimsStr  = 'Rectangular: ' + l + 'm × ' + w + 'm × ' + hr + 'm';
                }
            }
            if (capacity > 0) {
                var formatted = capacity.toLocaleString('en-PH', {maximumFractionDigits: 0}) + ' L';
                document.getElementById('projCapacityDisplay').value  = formatted;
                document.getElementById('projCapacityHidden').value   = formatted;
                document.getElementById('projDimensionsHidden').value = dimsStr;
            } else {
                document.getElementById('projCapacityDisplay').value  = '';
                document.getElementById('projCapacityHidden').value   = '';
                document.getElementById('projDimensionsHidden').value = '';
            }
        }

        function initializeCustomInputs() {
            var nameSelect = document.getElementById('projectNameSelect');
            var nameOther  = document.getElementById('projectNameOther');
            var nameHidden = document.getElementById('projectNameHidden');
            if (nameSelect) {
                nameSelect.addEventListener('change', function() {
                    if (this.value === 'others') {
                        nameOther.style.display = 'block';
                        nameHidden.value = '';
                    } else {
                        nameOther.style.display = 'none';
                        nameOther.value  = '';
                        nameHidden.value = this.value;
                    }
                });
            }
            if (nameOther) {
                nameOther.addEventListener('input', function() {
                    nameHidden.value = this.value;
                });
            }
        }

        /* ── Tank type options shared by both modals ── */
        var TANK_TYPES = [
            'Underground Fuel Storage Tanks',
            'Cooking Oil Storage Tank',
            'Chemical Tank',
            'Polymer Tank',
            'Aboveground Water Storage Tanks',
            'Tetrapod',
            'Fuel Pipe Line Installation',
            'Re-piping of Fuel Pipe Line',
            'Aboveground Fuel Storage Tanks',
            'Fuel Day Tanks',
            'Others'
        ];

        // Per tank type: allowed shapes and dimension mode
        var TANK_META = {
            'Underground Fuel Storage Tanks':   { shapes: ['Cylindrical'],                       dims: 'cyl-dl' },
            'Cooking Oil Storage Tank':          { shapes: ['Rectangular', 'Modular'],            dims: 'rect' },
            'Chemical Tank':                     { shapes: ['Cylindrical'],                       dims: 'cyl-dl' },
            'Polymer Tank':                      { shapes: ['Rectangular', 'Modular'],            dims: 'rect' },
            'Aboveground Water Storage Tanks':   { shapes: ['Cylindrical'],                       dims: 'cyl-dl' },
            'Tetrapod':                          { shapes: ['3-Legged Pod'],                      dims: 'pod' },
            'Fuel Pipe Line Installation':       { shapes: ['N/A'],                              dims: 'linear' },
            'Re-piping of Fuel Pipe Line':       { shapes: ['N/A'],                              dims: 'linear' },
            'Aboveground Fuel Storage Tanks':    { shapes: ['Cylindrical'],                       dims: 'cyl-dl' },
            'Fuel Day Tanks':                    { shapes: ['Cylindrical', 'Rectangular'],        dims: 'auto' },
            'Others':                            { shapes: ['Cylindrical', 'Rectangular', 'Modular', '3-Legged Pod', 'N/A'], dims: 'auto' },
        };

        function tankTypeOptions(selected) {
            return '<option value="" disabled' + (!selected ? ' selected' : '') + ' hidden>Select tank type</option>' +
                TANK_TYPES.map(function(t) {
                    return '<option value="' + t + '"' + (t === selected ? ' selected' : '') + '>' + t + '</option>';
                }).join('');
        }

        function tankShapeOptions(type, selected) {
            var meta   = TANK_META[type];
            var shapes = meta ? meta.shapes : ['Cylindrical', 'Rectangular'];
            return shapes.map(function(s) {
                return '<option value="' + s + '"' + (s === selected ? ' selected' : '') + '>' + s + '</option>';
            }).join('');
        }

        function onTankTypeChange(sel) {
            var row    = sel.closest('.tank-item-row');
            var type   = sel.value;
            var meta   = TANK_META[type] || { shapes: ['Cylindrical', 'Rectangular'], dims: 'auto' };
            // Show dimensions section
            var dimsSection = row.querySelector('.ti-dims-section');
            if (dimsSection) dimsSection.style.display = type ? '' : 'none';
            // Update shape dropdown
            var shapeSel = row.querySelector('.ti-shape');
            shapeSel.innerHTML = tankShapeOptions(type, meta.shapes[0]);
            shapeSel.value = meta.shapes[0];
            updateDimFields(row, meta.shapes[0], meta.dims);
        }

        function updateDimFields(row, shape, dimMode) {
            var isCyl    = shape === 'Cylindrical';
            var isRect   = shape === 'Rectangular' || shape === 'Modular';
            var isPod    = shape === '3-Legged Pod';
            var isLinear = shape === 'N/A' || dimMode === 'linear';

            // dim mode overrides
            if (dimMode === 'cyl-dl')  { isCyl = true;   isRect = false; isPod = false; isLinear = false; }
            if (dimMode === 'rect')    { isCyl = false;  isRect = true;  isPod = false; isLinear = false; }
            if (dimMode === 'pod')     { isCyl = false;  isRect = false; isPod = true;  isLinear = false; }
            if (dimMode === 'linear')  { isCyl = false;  isRect = false; isPod = false; isLinear = true;  }

            // Update label for cylindrical: Diameter+Length vs Diameter+Height
            var cylLabel2 = row.querySelector('.ti-cyl-label2');
            if (cylLabel2) cylLabel2.textContent = (dimMode === 'cyl-dl' || !isRect) ? 'Length (m)' : 'Height (m)';

            row.querySelectorAll('.ti-cyl').forEach(function(el)    { el.style.display = isCyl    ? '' : 'none'; });
            row.querySelectorAll('.ti-rect').forEach(function(el)   { el.style.display = isRect   ? '' : 'none'; });
            row.querySelectorAll('.ti-pod').forEach(function(el)    { el.style.display = isPod    ? '' : 'none'; });
            row.querySelectorAll('.ti-linear').forEach(function(el) { el.style.display = isLinear ? '' : 'none'; });
            row.querySelectorAll('.ti-dim-input').forEach(function(el) { el.value = ''; });
            computeRowCapacity(row);
        }

        function onTankShapeChange(sel) {
            var row  = sel.closest('.tank-item-row');
            var type = row.querySelector('select[name$="[tank_type]"]').value;
            var meta = TANK_META[type] || { dims: 'auto' };
            updateDimFields(row, sel.value, meta.dims);
        }

        function computeRowCapacity(row) {
            var shape      = row.querySelector('.ti-shape') ? row.querySelector('.ti-shape').value : '';
            var capHidden  = row.querySelector('.ti-cap-hidden');
            var dimHidden  = row.querySelector('.ti-dim-hidden');
            var capacity = 0, dimsStr = '';

            if (shape === 'Cylindrical') {
                var d  = parseFloat(row.querySelector('[data-dim="d"]')  ? row.querySelector('[data-dim="d"]').value  : 0) || 0;
                var h  = parseFloat(row.querySelector('[data-dim="h"]')  ? row.querySelector('[data-dim="h"]').value  : 0) || 0;
                if (d > 0 && h > 0) {
                    capacity = Math.PI * Math.pow(d / 2, 2) * h * 1000;
                    dimsStr  = 'Ø' + d + 'm × L' + h + 'm';
                }
            } else if (shape === 'Rectangular' || shape === 'Modular') {
                var l  = parseFloat(row.querySelector('[data-dim="l"]')  ? row.querySelector('[data-dim="l"]').value  : 0) || 0;
                var w  = parseFloat(row.querySelector('[data-dim="w"]')  ? row.querySelector('[data-dim="w"]').value  : 0) || 0;
                var rh = parseFloat(row.querySelector('[data-dim="rh"]') ? row.querySelector('[data-dim="rh"]').value : 0) || 0;
                if (l > 0 && w > 0 && rh > 0) {
                    capacity = l * w * rh * 1000;
                    dimsStr  = 'L' + l + 'm × W' + w + 'm × H' + rh + 'm';
                }
            } else if (shape === '3-Legged Pod') {
                var ph = parseFloat(row.querySelector('[data-dim="ph"]') ? row.querySelector('[data-dim="ph"]').value : 0) || 0;
                var pw = parseFloat(row.querySelector('[data-dim="pw"]') ? row.querySelector('[data-dim="pw"]').value : 0) || 0;
                if (ph > 0 || pw > 0) dimsStr = 'H' + ph + 'm × W' + pw + 'm';
            } else if (shape === 'N/A') {
                var lm = parseFloat(row.querySelector('[data-dim="lm"]') ? row.querySelector('[data-dim="lm"]').value : 0) || 0;
                if (lm > 0) dimsStr = lm + ' linear m';
            }

            if (dimHidden) dimHidden.value = dimsStr;
            // Capacity is entered manually by the admin — no longer auto-computed from dimensions.
        }

        function buildTankRow(prefix, item, removable) {
            item = item || {};
            var type  = item.tank_type || '';
            var meta  = TANK_META[type] || { shapes: ['Cylindrical', 'Rectangular'], dims: 'auto' };
            var dims  = item.dimensions || '';

            // Parse stored dimensions
            var dVal='', hVal='', lVal='', wVal='', rhVal='', phVal='', pwVal='', lmVal='';
            var cylMatch  = dims.match(/Ø([\d.]+)m × L([\d.]+)m/);
            var rectMatch = dims.match(/L([\d.]+)m × W([\d.]+)m × H([\d.]+)m/);
            var podMatch  = dims.match(/H([\d.]+)m × W([\d.]+)m/);
            var linMatch  = dims.match(/([\d.]+) linear m/);
            if (cylMatch)  { dVal = cylMatch[1]; hVal = cylMatch[2]; }
            if (rectMatch) { lVal = rectMatch[1]; wVal = rectMatch[2]; rhVal = rectMatch[3]; }
            if (podMatch)  { phVal = podMatch[1]; pwVal = podMatch[2]; }
            if (linMatch)  { lmVal = linMatch[1]; }

            // Determine initial shape
            var initShape = meta.shapes[0];
            if (item.tank_shape) initShape = item.tank_shape;
            else if (cylMatch)  initShape = 'Cylindrical';
            else if (rectMatch) initShape = 'Rectangular';
            else if (podMatch)  initShape = '3-Legged Pod';
            else if (linMatch)  initShape = 'N/A';

            var isCyl    = initShape === 'Cylindrical';
            var isRect   = initShape === 'Rectangular' || initShape === 'Modular';
            var isPod    = initShape === '3-Legged Pod';
            var isLinear = initShape === 'N/A';

            var row = document.createElement('div');
            row.className = 'tank-item-row';
            row.style.cssText = 'background:#fff;border:1.5px solid #333;border-radius:14px;padding:16px;margin-bottom:12px;position:relative;box-shadow:0 2px 8px rgba(0,0,0,.10);';

            row.innerHTML =
                (removable ? '<button type="button" onclick="this.closest(\'.tank-item-row\').remove()" title="Remove tank" style="position:absolute;top:12px;right:12px;background:none;border:none;cursor:pointer;color:var(--muted);line-height:1;"><i data-lucide="x" style="width:15px;height:15px;"></i></button>' : '') +

                '<div style="display:flex;gap:12px;align-items:flex-end;">' +
                    '<div class="form-group" style="flex:2;">' +
                        '<label>Project Type</label>' +
                        '<select name="' + prefix + '[tank_type]" required onchange="onTankTypeChange(this)">' + tankTypeOptions(type) + '</select>' +
                    '</div>' +
                    '<div class="form-group" style="flex:1.5;">' +
                        '<label>Project Shape</label>' +
                        '<select class="ti-shape" onchange="onTankShapeChange(this)">' +
                            (!type ? '<option value="" disabled selected hidden>—</option>' : tankShapeOptions(type, initShape)) +
                        '</select>' +
                    '</div>' +
                    '<div class="form-group" style="width:90px;flex-shrink:0;">' +
                        '<label>Quantity</label>' +
                        '<input type="number" name="' + prefix + '[quantity]" value="' + (item.quantity || 1) + '" min="1" onwheel="this.blur()">' +
                    '</div>' +
                '</div>' +

                '<div class="ti-dims-section" style="' + (!type ? 'display:none;' : '') + '">' +
                '<div style="margin-top:12px;margin-bottom:8px;display:flex;align-items:center;gap:8px;">' +
                    '<span style="font-size:10px;font-weight:900;color:#fff;text-transform:uppercase;letter-spacing:.08em;background:linear-gradient(180deg,#333 0%,#2a2a2a 100%);padding:3px 10px;border-radius:999px;">Dimensions</span>' +
                    '<div style="flex:1;height:1px;background:linear-gradient(90deg,#555,transparent);"></div>' +
                '</div>' +
                '<div class="form-grid">' +

                    // Cylindrical: Diameter + Length
                    '<div class="form-group ti-cyl"' + (!isCyl ? ' style="display:none;"' : '') + '>' +
                        '<label>Diameter (m)</label>' +
                        '<input type="number" class="ti-dim-input" data-dim="d" min="0" step="0.01" value="' + dVal + '" placeholder="e.g. 2.00" oninput="computeRowCapacity(this.closest(\'.tank-item-row\'))">' +
                    '</div>' +
                    '<div class="form-group ti-cyl"' + (!isCyl ? ' style="display:none;"' : '') + '>' +
                        '<label class="ti-cyl-label2">Length (m)</label>' +
                        '<input type="number" class="ti-dim-input" data-dim="h" min="0" step="0.01" value="' + hVal + '" placeholder="e.g. 3.00" oninput="computeRowCapacity(this.closest(\'.tank-item-row\'))">' +
                    '</div>' +

                    // Rectangular / Modular: L + W + H
                    '<div class="form-group ti-rect"' + (!isRect ? ' style="display:none;"' : '') + '>' +
                        '<label>Length (m)</label>' +
                        '<input type="number" class="ti-dim-input" data-dim="l" min="0" step="0.01" value="' + lVal + '" placeholder="e.g. 4.00" oninput="computeRowCapacity(this.closest(\'.tank-item-row\'))">' +
                    '</div>' +
                    '<div class="form-group ti-rect"' + (!isRect ? ' style="display:none;"' : '') + '>' +
                        '<label>Width (m)</label>' +
                        '<input type="number" class="ti-dim-input" data-dim="w" min="0" step="0.01" value="' + wVal + '" placeholder="e.g. 2.00" oninput="computeRowCapacity(this.closest(\'.tank-item-row\'))">' +
                    '</div>' +
                    '<div class="form-group ti-rect"' + (!isRect ? ' style="display:none;"' : '') + '>' +
                        '<label>Height (m)</label>' +
                        '<input type="number" class="ti-dim-input" data-dim="rh" min="0" step="0.01" value="' + rhVal + '" placeholder="e.g. 2.00" oninput="computeRowCapacity(this.closest(\'.tank-item-row\'))">' +
                    '</div>' +

                    // 3-Legged Pod: Height + Width
                    '<div class="form-group ti-pod"' + (!isPod ? ' style="display:none;"' : '') + '>' +
                        '<label>Height (m)</label>' +
                        '<input type="number" class="ti-dim-input" data-dim="ph" min="0" step="0.01" value="' + phVal + '" placeholder="e.g. 1.50" oninput="computeRowCapacity(this.closest(\'.tank-item-row\'))">' +
                    '</div>' +
                    '<div class="form-group ti-pod"' + (!isPod ? ' style="display:none;"' : '') + '>' +
                        '<label>Width (m)</label>' +
                        '<input type="number" class="ti-dim-input" data-dim="pw" min="0" step="0.01" value="' + pwVal + '" placeholder="e.g. 0.80" oninput="computeRowCapacity(this.closest(\'.tank-item-row\'))">' +
                    '</div>' +

                    // Pipe: linear meter
                    '<div class="form-group ti-linear"' + (!isLinear ? ' style="display:none;"' : '') + '>' +
                        '<label>Linear Meter (m)</label>' +
                        '<input type="number" class="ti-dim-input" data-dim="lm" min="0" step="0.01" value="' + lmVal + '" placeholder="e.g. 120" oninput="computeRowCapacity(this.closest(\'.tank-item-row\'))">' +
                    '</div>' +

                    // Capacity
                    '<div class="form-group">' +
                        '<label>Capacity</label>' +
                        '<input type="text" name="' + prefix + '[capacity]" class="ti-cap-hidden" placeholder="e.g. 5000 L" value="' + (item.capacity || '') + '">' +
                        '<input type="hidden" name="' + prefix + '[dimensions]" class="ti-dim-hidden" value="' + (item.dimensions || '') + '">' +
                    '</div>' +
                '</div>' +
                '</div>'; // close ti-dims-section

            return row;
        }

        function escAttr(s) {
            return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        // Materials actually recorded on projects' Bill of Materials (Project Quotation module) —
        // shown as a "Previously Used" group above the static catalog in the materials combo box.
        var USED_MATERIALS = @json($usedMaterials);
        var USED_MATERIAL_UNITS = {};
        (USED_MATERIALS || []).forEach(function(m) {
            if (m.unit) USED_MATERIAL_UNITS[m.name] = m.unit;
        });

        function buildMaterialRow(prefix, item) {
            item = item || {};
            var inputStyle = 'width:100%;padding:8px 10px;border:1px solid rgba(0,0,0,0.14);border-radius:8px;font-size:13px;box-sizing:border-box;';

            var tr = document.createElement('tr');
            tr.className = 'material-item-row';
            tr.style.cssText = 'border-bottom:1px solid rgba(0,0,0,0.06);';

            var readonlyStyle = 'width:100%;padding:8px 10px;border:1px solid rgba(0,0,0,0.08);border-radius:8px;font-size:13px;font-weight:800;color:var(--dark);background:rgba(0,0,0,0.03);cursor:default;box-sizing:border-box;';

            tr.innerHTML =
                '<td style="padding:8px 10px;vertical-align:top;">' +
                    '<div class="mat-combo">' +
                        '<input type="text" name="' + prefix + '[material_name]" class="row-mat-name-input" value="' + escAttr(item.material_name) + '" ' +
                            'placeholder="Search or type material..." required autocomplete="off" ' +
                            'oninput="filterMatCombo(this)" onfocus="openMatCombo(this)" style="' + inputStyle + '">' +
                        '<div class="mat-combo-dropdown"></div>' +
                    '</div>' +
                    '<div class="mat-combo-warning">This material is already added.</div>' +
                '</td>' +
                '<td style="padding:8px 10px;vertical-align:top;">' +
                    '<input type="number" name="' + prefix + '[quantity]" class="row-qty" value="' + (item.quantity || 1) + '" min="0.01" step="0.01" onwheel="this.blur()" oninput="updateMaterialRowTotal(this)" style="' + inputStyle + '">' +
                '</td>' +
                '<td style="padding:8px 10px;vertical-align:top;">' +
                    '<input type="text" name="' + prefix + '[unit]" class="row-unit" value="' + escAttr(item.unit) + '" placeholder="pcs, kg, m" style="' + inputStyle + '">' +
                '</td>' +
                '<td style="padding:8px 10px;vertical-align:top;">' +
                    '<input type="number" name="' + prefix + '[price_per_unit]" class="row-price" value="' + (item.price_per_unit || '') + '" min="0" step="0.01" placeholder="0.00" onwheel="this.blur()" oninput="updateMaterialRowTotal(this)" style="' + inputStyle + '">' +
                '</td>' +
                '<td style="padding:8px 10px;vertical-align:top;">' +
                    '<input type="text" class="row-total-display" readonly placeholder="—" style="' + readonlyStyle + '">' +
                '</td>' +
                '<td style="padding:8px 10px;vertical-align:top;text-align:center;">' +
                    '<button type="button" onclick="this.closest(\'tr\').remove(); toggleMaterialsEmptyHint();" title="Remove material" ' +
                        'style="background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;color:var(--danger);display:inline-flex;align-items:center;">' +
                        '<i data-lucide="trash-2" style="width:15px;height:15px;"></i>' +
                    '</button>' +
                '</td>';

            var qty   = parseFloat(item.quantity)       || 0;
            var price = parseFloat(item.price_per_unit)  || 0;
            var total = qty * price;
            tr.querySelector('.row-total-display').value = total > 0 ? formatMaterialCost(total) : '';

            return tr;
        }

        function formatMaterialCost(val) {
            return '₱' + parseFloat(val).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function updateMaterialRowTotal(input) {
            var row   = input.closest('.material-item-row');
            var qty   = parseFloat(row.querySelector('.row-qty').value)   || 0;
            var price = parseFloat(row.querySelector('.row-price').value) || 0;
            var total = qty * price;
            row.querySelector('.row-total-display').value = total > 0 ? formatMaterialCost(total) : '';
        }

        // ---- searchable, categorized material combo box (fed by the shared Project Materials catalog) ----
        function getUsedMaterialNames(excludeInput) {
            var used = [];
            document.querySelectorAll('#materialsContainer .row-mat-name-input').forEach(function(el) {
                if (el === excludeInput) return;
                var val = el.value.trim();
                if (val) used.push(val.toLowerCase());
            });
            return used;
        }

        function renderMatComboList(panel, filterText, onSelect, usedNames) {
            var q = (filterText || '').toLowerCase().trim();
            var html = '';
            var hasMatches = false;
            usedNames = usedNames || [];

            // "Previously Used" — real materials pulled from the Project Quotation / BOM module,
            // shown first so admins reuse actual project history instead of retyping.
            var seenFromHistory = {};
            var recentItems = (USED_MATERIALS || []).filter(function(m) {
                return !q || m.name.toLowerCase().indexOf(q) !== -1;
            });
            if (recentItems.length) {
                hasMatches = true;
                html += '<div class="mat-combo-category">';
                html += '<div class="mat-combo-group" style="background:#7C3AED;">Previously Used</div>';
                recentItems.forEach(function(m) {
                    seenFromHistory[m.name.toLowerCase()] = true;
                    var isUsed = usedNames.indexOf(m.name.toLowerCase()) !== -1;
                    var countLabel = m.count > 1 ? m.count + ' projects' : '1 project';
                    if (isUsed) {
                        html += '<div class="mat-combo-item disabled" data-value="' + escAttr(m.name) + '">' +
                                    '<span>' + escAttr(m.name) + '</span>' +
                                    '<span class="mat-combo-item-badge">Already added</span>' +
                                '</div>';
                    } else {
                        html += '<div class="mat-combo-item" data-value="' + escAttr(m.name) + '" style="display:flex;align-items:center;justify-content:space-between;gap:8px;">' +
                                    '<span>' + escAttr(m.name) + '</span>' +
                                    '<span style="font-size:10px;font-weight:600;color:var(--muted);white-space:nowrap;">' + countLabel + '</span>' +
                                '</div>';
                    }
                });
                html += '</div>';
            }

            Object.keys(MATERIAL_CATALOG).forEach(function(category) {
                var items = MATERIAL_CATALOG[category].filter(function(name) {
                    return (!q || name.toLowerCase().indexOf(q) !== -1) && !seenFromHistory[name.toLowerCase()];
                });
                if (items.length === 0) return;
                hasMatches = true;
                html += '<div class="mat-combo-category">';
                html += '<div class="mat-combo-group">' + escAttr(category) + '</div>';
                items.forEach(function(name) {
                    var isUsed = usedNames.indexOf(name.toLowerCase()) !== -1;
                    if (isUsed) {
                        html += '<div class="mat-combo-item disabled" data-value="' + escAttr(name) + '">' +
                                    '<span>' + escAttr(name) + '</span>' +
                                    '<span class="mat-combo-item-badge">Already added</span>' +
                                '</div>';
                    } else {
                        html += '<div class="mat-combo-item" data-value="' + escAttr(name) + '">' + escAttr(name) + '</div>';
                    }
                });
                html += '</div>';
            });

            if (!hasMatches) {
                html += '<div class="mat-combo-empty">No matches in the catalog — your typed name will be used as-is.</div>';
            }

            panel.innerHTML = html;
            panel.querySelectorAll('.mat-combo-item').forEach(function(item) {
                if (item.classList.contains('disabled')) return;
                item.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    onSelect(item.dataset.value);
                });
            });
        }

        function checkMatDuplicate(input) {
            var wrapper = input.closest('.mat-combo');
            var warning = wrapper.parentNode.querySelector('.mat-combo-warning');
            if (!warning) return;

            var value = input.value.trim().toLowerCase();
            var isDuplicate = value && getUsedMaterialNames(input).indexOf(value) !== -1;

            warning.classList.toggle('show', isDuplicate);
            input.style.outline = isDuplicate ? '2px solid var(--danger)' : '';
            return isDuplicate;
        }

        function positionMatCombo(panel, input) {
            var rect       = input.getBoundingClientRect();
            var maxHeight  = 320;
            var width      = Math.max(rect.width, Math.min(640, window.innerWidth - 32));
            var spaceBelow = window.innerHeight - rect.bottom;
            var spaceAbove = rect.top;

            var left = Math.min(rect.left, window.innerWidth - width - 16);
            left = Math.max(16, left);

            panel.style.left  = left + 'px';
            panel.style.width = width + 'px';

            if (spaceBelow < maxHeight && spaceAbove > spaceBelow) {
                panel.style.top       = '';
                panel.style.bottom    = (window.innerHeight - rect.top + 4) + 'px';
                panel.style.maxHeight = Math.max(120, Math.min(maxHeight, spaceAbove - 12)) + 'px';
            } else {
                panel.style.bottom    = '';
                panel.style.top       = (rect.bottom + 4) + 'px';
                panel.style.maxHeight = Math.max(120, Math.min(maxHeight, spaceBelow - 12)) + 'px';
            }
        }

        function openMatCombo(input) {
            var wrapper = input.closest('.mat-combo');
            var panel   = wrapper.querySelector('.mat-combo-dropdown');
            renderMatComboList(panel, input.value, function(value) {
                input.value = value;
                panel.classList.remove('show');
                checkMatDuplicate(input);
                var row = input.closest('tr');
                if (row) {
                    var unitInput = row.querySelector('.row-unit');
                    var unit = USED_MATERIAL_UNITS[value] || MATERIAL_UNITS[value];
                    if (unitInput && unit) {
                        unitInput.value = unit;
                    }
                }
                input.focus();
            }, getUsedMaterialNames(input));
            positionMatCombo(panel, input);
            panel.classList.add('show');
        }

        function filterMatCombo(input) {
            openMatCombo(input);
            checkMatDuplicate(input);
        }

        function closeAllMatCombos() {
            document.querySelectorAll('.mat-combo-dropdown.show').forEach(function(p) {
                p.classList.remove('show');
            });
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.mat-combo')) closeAllMatCombos();
        });
        window.addEventListener('scroll', function(e) {
            var t = e.target;
            if (t && t.nodeType === 1 && t.closest('.mat-combo-dropdown')) return;
            closeAllMatCombos();
        }, true);

        function toggleMaterialsEmptyHint() {
            var container = document.getElementById('materialsContainer');
            var hint = document.getElementById('materialsEmptyHint');
            var wrapper = document.getElementById('materialsTableWrapper');
            var hasRows = !!(container && container.children.length);
            if (hint)    hint.style.display    = hasRows ? 'none'  : 'block';
            if (wrapper) wrapper.style.display = hasRows ? 'block' : 'none';
        }

        var addMaterialIndex = 0;
        function addMaterialRow(item) {
            var container = document.getElementById('materialsContainer');
            var prefix = 'materials[' + addMaterialIndex + ']';
            container.appendChild(buildMaterialRow(prefix, item));
            addMaterialIndex++;
            toggleMaterialsEmptyHint();
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        var addMaterialBtn = document.getElementById('addMaterialBtn');
        if (addMaterialBtn) {
            addMaterialBtn.addEventListener('click', function() { addMaterialRow(); });
        }

        /* ── Project templates (reusable tank specs + materials) ── */
        @php
            $templateOptionsForJs = $projectTemplates->map(function ($t) {
                return [
                    'id'           => $t->id,
                    'name'         => $t->name,
                    'project_name' => $t->project_name,
                    'tank_items'   => $t->tank_items,
                    'materials'    => $t->materials,
                ];
            })->values();
        @endphp
        var PROJECT_TEMPLATES = @json($templateOptionsForJs);

        function loadTemplateIntoAddForm(templateId) {
            var tpl = PROJECT_TEMPLATES.find(function(t) { return String(t.id) === String(templateId); });
            if (!tpl) return;

            var hasMaterials = !!(tpl.materials && tpl.materials.length);
            document.getElementById('bomSection').style.display = hasMaterials ? 'block' : 'none';

            document.getElementById('tankItemsContainer').innerHTML = '';
            addTankIndex = 0;
            (tpl.tank_items || []).forEach(function(item) { addTankRow(item); });
            if (!tpl.tank_items || !tpl.tank_items.length) addTankRow();

            document.getElementById('materialsContainer').innerHTML = '';
            addMaterialIndex = 0;
            if (hasMaterials) {
                tpl.materials.forEach(function(item) { addMaterialRow(item); });
            }
            toggleMaterialsEmptyHint();

            if (tpl.project_name) {
                var nameSelect = document.getElementById('projectNameSelect');
                var nameOther  = document.getElementById('projectNameOther');
                var nameHidden = document.getElementById('projectNameHidden');
                var matches = Array.prototype.some.call(nameSelect.options, function(o) { return o.value === tpl.project_name; });
                if (matches) {
                    nameSelect.value = tpl.project_name;
                    nameOther.style.display = 'none';
                    nameOther.value = '';
                } else {
                    nameSelect.value = 'others';
                    nameOther.style.display = 'block';
                    nameOther.value = tpl.project_name;
                }
                nameHidden.value = tpl.project_name;
            }
        }

        var addTankIndex = 0;
        function addTankRow(item) {
            var container = document.getElementById('tankItemsContainer');
            var prefix = 'tank_items[' + addTankIndex + ']';
            var removable = container.children.length > 0;
            container.appendChild(buildTankRow(prefix, item, removable));
            addTankIndex++;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        // Init add modal with one empty row
        document.addEventListener('DOMContentLoaded', function() {
            addTankRow();
            toggleMaterialsEmptyHint();
        });

        // Reset add modal tank rows when opened
        var origOpenAddProject = document.getElementById('openAddProjectModal');
        if (origOpenAddProject) {
            origOpenAddProject.addEventListener('click', function() {
                document.getElementById('tankItemsContainer').innerHTML = '';
                addTankIndex = 0;
                addTankRow();

                document.getElementById('materialsContainer').innerHTML = '';
                addMaterialIndex = 0;
                toggleMaterialsEmptyHint();
                document.getElementById('bomSection').style.display = 'none';
            });
        }

        // Holds the client + tank items + summary from a quotation request being
        // converted, from the moment it's fetched until the Add Project form opens.
        // Cleared whenever a normal (non-conversion) Add Project flow is started.
        var quotationConversionData = null;

        function qrEscapeHtml(str) {
            return String(str == null ? '' : str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // Renders each tank item as a row of color-coded chips (type / capacity / timeline)
        // so the specs are easy to tell apart at a glance, plus a notes line if present.
        function formatQuotationSummaryLine(data) {
            var tankItems = (data && data.tank_items) || [];
            var html = tankItems.length
                ? tankItems.map(function (item) {
                    var typeLabel = qrEscapeHtml(item.tank_type || 'Tank');
                    if ((item.quantity || 1) > 1) typeLabel += ' ×' + parseInt(item.quantity, 10);
                    var chips = '<span class="qr-spec-chip qr-chip-type"><i data-lucide="package" style="width:11px;height:11px;"></i>' + typeLabel + '</span>';
                    if (item.capacity) {
                        chips += '<span class="qr-spec-chip qr-chip-capacity"><i data-lucide="droplet" style="width:11px;height:11px;"></i>' + qrEscapeHtml(item.capacity) + '</span>';
                    }
                    if (item.target_timeline) {
                        chips += '<span class="qr-spec-chip qr-chip-timeline"><i data-lucide="clock" style="width:11px;height:11px;"></i>' + qrEscapeHtml(item.target_timeline) + '</span>';
                    }
                    return '<div class="qr-tank-line">' + chips + '</div>';
                }).join('')
                : '<div class="qr-tank-line" style="font-weight:600;font-size:12.5px;">No tank details provided</div>';

            var notes = data && data.summary && data.summary.notes;
            if (notes) {
                html += '<div class="qr-tank-notes">Notes: ' + qrEscapeHtml(notes) + '</div>';
            }
            return html;
        }

        // Kick off a conversion: fetch the quotation's details, lock in the client,
        // then let the admin pick a template (or start from scratch) same as normal —
        // (arrived here via "Convert to Project" on admin/quotation-requests)
        function runQuotationPrefill() {
            var qrId = new URLSearchParams(window.location.search).get('prefill_quotation_request');
            if (!qrId) return;

            fetch('/admin/quotation-requests/' + qrId + '/prefill', { headers: { 'Accept': 'application/json' } })
                .then(function (r) {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(function (data) {
                    quotationConversionData = data;
                    populateClientFields(data.client);
                    document.getElementById('projQuotationRequestIdHidden').value = data.quotation_request_id;
                    openSelectTemplateModal(true);
                })
                .catch(function (err) {
                    console.error('Quotation prefill failed:', err);
                    alert('Could not load this quotation request\'s details. Please try clicking "Convert to Project" again from the Quotation Requests page.');
                });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runQuotationPrefill);
        } else {
            runQuotationPrefill();
        }

        function initializeFormValidation() {
            var form = document.getElementById('addProjectForm');
            if (!form) return;
            form.addEventListener('submit', function(e) {
                if (!document.getElementById('projClientNameHidden').value) {
                    e.preventDefault();
                    alert('Please select a client first.');
                    return;
                }
                var nameHidden = document.getElementById('projectNameHidden');
                if (!nameHidden.value || !nameHidden.value.trim()) {
                    e.preventDefault();
                    var nameSelect = document.getElementById('projectNameSelect');
                    if (nameSelect.value === 'others') {
                        alert('Please enter a custom project name.');
                        document.getElementById('projectNameOther').focus();
                    } else {
                        alert('Please select a project name.');
                        nameSelect.focus();
                    }
                    return;
                }
                // Validate that at least one tank row has a tank type selected
                var tankRows = document.querySelectorAll('#tankItemsContainer .tank-item-row');
                if (!tankRows.length) {
                    e.preventDefault();
                    alert('Please add at least one tank specification.');
                    return;
                }
                var allTankTyped = true;
                tankRows.forEach(function(row) {
                    var sel = row.querySelector('select[name$="[tank_type]"]');
                    if (!sel || !sel.value) allTankTyped = false;
                });
                if (!allTankTyped) {
                    e.preventDefault();
                    alert('Please select a tank type for each tank row.');
                    return;
                }
            });
        }

        function applyClientListFilter() {
            var input   = document.getElementById('clientListSearch');
            var q       = input ? input.value.toLowerCase() : '';
            var visible = 0;
            document.querySelectorAll('#clientListTable tbody tr[data-search]').forEach(function(row) {
                var show = row.dataset.search.indexOf(q) !== -1;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            var emptyRow = document.getElementById('clientListEmptyRow');
            if (emptyRow) emptyRow.style.display = visible === 0 ? '' : 'none';
        }

        function buildClientRow(g) {
            var tr = document.createElement('tr');
            tr.dataset.search = g.client.toLowerCase();
            tr.style.cursor = 'pointer';
            var url = PROJECT_CLIENT_URL_TEMPLATE.replace('__CLIENT__', encodeURIComponent(g.client));
            tr.setAttribute('onclick', "window.location='" + url.replace(/'/g, "\\'") + "'");
            tr.innerHTML =
                '<td><span class="client-pill"></span></td>' +
                '<td style="text-align:center;font-weight:800;">' + g.total + '</td>' +
                '<td style="text-align:center;color:#2563EB;font-weight:700;">' + g.active + '</td>' +
                '<td style="text-align:center;color:#207A3A;font-weight:700;">' + g.completed + '</td>' +
                '<td style="text-align:center;color:#6B7280;font-weight:700;">' + g.archived + '</td>' +
                '<td class="action-cell" style="text-align:center;">' +
                    '<a href="' + url + '" class="action-btn view" title="View Client\'s Projects" onclick="event.stopPropagation()">' +
                        '<i data-lucide="eye"></i>' +
                    '</a>' +
                '</td>';
            tr.querySelector('.client-pill').textContent = g.client; // textContent — never trust client names as HTML
            return tr;
        }

        function refreshClientList() {
            fetch(PROJECT_CLIENT_GROUPS_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function(r) { return r.json(); })
                .then(function(groups) {
                    var tbody = document.querySelector('#clientListTable tbody');
                    var emptyRow = document.getElementById('clientListEmptyRow');
                    tbody.querySelectorAll('tr[data-search]').forEach(function(row) { row.remove(); });
                    groups.forEach(function(g) {
                        tbody.insertBefore(buildClientRow(g), emptyRow || null);
                    });
                    applyClientSortMode(); // server order is "Active Projects First" — reapply A–Z/Z–A if selected
                    applyClientListFilter();
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                })
                .catch(function(err) { console.error('Failed to refresh client list:', err); });
        }

        var currentClientSort = 'default';

        function applyClientSortMode() {
            if (currentClientSort === 'default') return; // rows are already in the server's "Active First" order
            var tbody    = document.querySelector('#clientListTable tbody');
            var emptyRow = document.getElementById('clientListEmptyRow');
            var rows     = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-search]'));
            rows.sort(function(a, b) {
                return currentClientSort === 'alpha-asc'
                    ? a.dataset.search.localeCompare(b.dataset.search)
                    : b.dataset.search.localeCompare(a.dataset.search);
            });
            rows.forEach(function(row) { tbody.insertBefore(row, emptyRow || null); });
        }

        function initializeClientListSearch() {
            var input = document.getElementById('clientListSearch');
            if (!input) return;
            input.addEventListener('keyup', applyClientListFilter);

            var sortSelect = document.getElementById('clientSortSelect');
            if (sortSelect) {
                sortSelect.addEventListener('change', function() {
                    currentClientSort = this.value;
                    if (currentClientSort === 'default') {
                        refreshClientList(); // simplest way back to the true server-computed order
                    } else {
                        applyClientSortMode();
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            var openBtn = document.getElementById('openAddProjectModal');
            if (openBtn) openBtn.addEventListener('click', openSelectClientModal);

            var closeSelectClientBtn = document.getElementById('closeSelectClientModal');
            if (closeSelectClientBtn) closeSelectClientBtn.addEventListener('click', function() { closeModal('selectClientModal'); });

            var continueBtn = document.getElementById('continueSelectClient');
            if (continueBtn) {
                continueBtn.addEventListener('click', function() {
                    if (!selectedClient) { alert('Please select a client to continue.'); return; }
                    closeModal('selectClientModal');
                    populateClientFields(selectedClient);
                    openSelectTemplateModal();
                });
            }

            var cSearch = document.getElementById('clientSelectSearch');
            if (cSearch) {
                cSearch.addEventListener('input', function() {
                    renderClientSelectList(allClients, this.value);
                });
            }

            var closeAddBtn = document.getElementById('closeAddProjectModal');
            if (closeAddBtn) closeAddBtn.addEventListener('click', function() { closeModal('addProjectModal'); });

            var backAddBtn = document.getElementById('backAddProject');
            if (backAddBtn) {
                backAddBtn.addEventListener('click', function() {
                    closeModal('addProjectModal');
                    openSelectTemplateModal(!!quotationConversionData);
                });
            }

            // ── Select Template step (between client selection and the Add Project form) ──
            function inferShapeLabel(dims) {
                dims = dims || '';
                if (/Ø[\d.]+m × L[\d.]+m/.test(dims))          return 'Cylindrical';
                if (/L[\d.]+m × W[\d.]+m × H[\d.]+m/.test(dims)) return 'Rectangular';
                if (/H[\d.]+m × W[\d.]+m/.test(dims))           return '3-Legged Pod';
                if (/[\d.]+ linear m/.test(dims))               return 'Linear';
                return '';
            }

            function templateSummary(tpl) {
                var items = tpl.tank_items || [];
                var pillStyle = 'display:inline-flex;align-items:center;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:600;margin-right:4px;white-space:nowrap;';

                if (!items.length) {
                    return '<span style="font-size:12px;color:var(--muted);">No tank specs saved</span>';
                }

                return items.map(function(it) {
                    var qty   = it.quantity && it.quantity > 1 ? ' ×' + it.quantity : '';
                    var shape = it.shape || inferShapeLabel(it.dimensions);
                    var specParts = [];
                    if (it.dimensions) specParts.push(it.dimensions);
                    if (it.capacity)   specParts.push(it.capacity);

                    var row =
                        '<span style="' + pillStyle + 'background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;">' + (it.tank_type || 'Tank') + qty + '</span>' +
                        (shape ? '<span style="' + pillStyle + 'background:#F0FDF4;color:#15803D;border:1px solid #BBF7D0;">' + shape + '</span>' : '') +
                        (specParts.length ? '<span style="' + pillStyle + 'background:#FEF9C3;color:#A16207;border:1px solid #FDE68A;">' + specParts.join(' · ') + '</span>' : '');
                    return '<div style="display:flex;flex-wrap:nowrap;overflow-x:auto;">' + row + '</div>';
                }).join('');
            }

            function setCustomTemplateSelected(isSelected) {
                var opt = document.getElementById('customTemplateOption');
                opt.classList.toggle('selected', isSelected);
                opt.querySelector('.cs-check').style.display = isSelected ? 'flex' : 'none';
            }

            function renderTemplateSelectList(filter) {
                var list = document.getElementById('templateSelectList');
                if (!list) return;
                var q = (filter || '').toLowerCase();
                var filtered = q
                    ? PROJECT_TEMPLATES.filter(function(t) { return t.name.toLowerCase().indexOf(q) !== -1; })
                    : PROJECT_TEMPLATES;

                list.innerHTML = '';

                if (!filtered.length) {
                    list.innerHTML = '<p style="text-align:center;color:var(--muted);padding:20px 0;font-size:13px;">' +
                        (PROJECT_TEMPLATES.length ? 'No templates match your search.' : 'No saved templates yet — check "Save as template" while adding a project to create one.') +
                        '</p>';
                    return;
                }

                filtered.forEach(function(tpl) {
                    var isSelected = String(selectedTemplateId) === String(tpl.id);
                    var item = document.createElement('div');
                    item.className = 'client-select-item' + (isSelected ? ' selected' : '');
                    item.innerHTML =
                        '<div class="cs-info">' +
                            '<div class="cs-name">' + tpl.name + '</div>' +
                            '<div style="margin-top:4px;">' + templateSummary(tpl) + '</div>' +
                        '</div>' +
                        '<button type="button" class="template-delete-btn" title="Delete template" ' +
                            'style="flex-shrink:0;width:32px;height:32px;border:none;background:none;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;border-radius:8px;">' +
                            '<i data-lucide="trash-2" style="width:15px;height:15px;"></i>' +
                        '</button>' +
                        '<div class="cs-check" style="display:' + (isSelected ? 'flex' : 'none') + ';">' +
                            '<i data-lucide="check-circle-2" style="width:20px;height:20px;color:var(--dark);"></i>' +
                        '</div>';

                    item.addEventListener('click', function() {
                        selectedTemplateId = tpl.id;
                        setCustomTemplateSelected(false);
                        renderTemplateSelectList(document.getElementById('templateSelectSearch').value);
                    });
                    item.querySelector('.template-delete-btn').addEventListener('click', function(e) {
                        e.stopPropagation();
                        if (!confirm('Delete template "' + tpl.name + '"? This cannot be undone.')) return;
                        var token = document.querySelector('#addProjectForm input[name="_token"]').value;
                        fetch('{{ url("admin/project-templates") }}/' + tpl.id, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
                        }).then(function(res) {
                            if (!res.ok) throw new Error('Delete failed');
                            PROJECT_TEMPLATES = PROJECT_TEMPLATES.filter(function(t) { return String(t.id) !== String(tpl.id); });
                            if (String(selectedTemplateId) === String(tpl.id)) {
                                selectedTemplateId = 'custom';
                                setCustomTemplateSelected(true);
                            }
                            renderTemplateSelectList(document.getElementById('templateSelectSearch').value);
                        }).catch(function() {
                            alert('Could not delete template. Please try again.');
                        });
                    });
                    list.appendChild(item);
                });
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }

            function openSelectTemplateModal(isConversion) {
                selectedTemplateId = 'custom';
                setCustomTemplateSelected(true);
                var si = document.getElementById('templateSelectSearch');
                if (si) si.value = '';
                renderTemplateSelectList('');
                // Skipping client selection during a quotation conversion — going further
                // back to "Select Client" would let the admin swap to an unrelated client,
                // breaking the link back to the quotation being converted.
                var backBtn = document.getElementById('backSelectTemplate');
                if (backBtn) backBtn.style.display = isConversion ? 'none' : '';

                var tBanner = document.getElementById('templatePrefillBanner');
                if (tBanner) {
                    if (isConversion && quotationConversionData) {
                        tBanner.style.display = '';
                        tBanner.querySelector('.qr-client-heading').textContent =
                            'Converting ' + (quotationConversionData.client.name || 'a client') + '\'s quotation request.';
                        tBanner.querySelector('.qr-summary').innerHTML = formatQuotationSummaryLine(quotationConversionData);
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    } else {
                        tBanner.style.display = 'none';
                    }
                }

                openModal('selectTemplateModal');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
            window.openSelectTemplateModal = openSelectTemplateModal;

            var customTemplateOption = document.getElementById('customTemplateOption');
            if (customTemplateOption) {
                customTemplateOption.addEventListener('click', function() {
                    selectedTemplateId = 'custom';
                    setCustomTemplateSelected(true);
                    renderTemplateSelectList(document.getElementById('templateSelectSearch').value);
                });
            }

            var tSearch = document.getElementById('templateSelectSearch');
            if (tSearch) {
                tSearch.addEventListener('input', function() {
                    renderTemplateSelectList(this.value);
                });
            }

            var closeSelectTemplateBtn = document.getElementById('closeSelectTemplateModal');
            if (closeSelectTemplateBtn) closeSelectTemplateBtn.addEventListener('click', function() { closeModal('selectTemplateModal'); });

            var backSelectTemplateBtn = document.getElementById('backSelectTemplate');
            if (backSelectTemplateBtn) {
                backSelectTemplateBtn.addEventListener('click', function() {
                    closeModal('selectTemplateModal');
                    openSelectClientModal();
                });
            }

            var continueTemplateBtn = document.getElementById('continueSelectTemplate');
            if (continueTemplateBtn) {
                continueTemplateBtn.addEventListener('click', function() {
                    closeModal('selectTemplateModal');

                    document.getElementById('tankItemsContainer').innerHTML = '';
                    addTankIndex = 0;

                    var usedExistingTemplate = !!(selectedTemplateId && selectedTemplateId !== 'custom');
                    document.getElementById('fromExistingTemplateHidden').value = usedExistingTemplate ? '1' : '0';

                    if (usedExistingTemplate) {
                        loadTemplateIntoAddForm(selectedTemplateId);
                    } else if (quotationConversionData && quotationConversionData.tank_items && quotationConversionData.tank_items.length) {
                        // Starting from scratch while converting — prefill with the tanks
                        // the client actually asked for, instead of one blank row.
                        quotationConversionData.tank_items.forEach(function (item) { addTankRow(item); });
                        document.getElementById('bomSection').style.display = 'none';
                        document.getElementById('materialsContainer').innerHTML = '';
                        addMaterialIndex = 0;
                        toggleMaterialsEmptyHint();
                    } else {
                        document.getElementById('bomSection').style.display = 'none';
                        addTankRow();
                        document.getElementById('materialsContainer').innerHTML = '';
                        addMaterialIndex = 0;
                        toggleMaterialsEmptyHint();
                    }

                    var qBanner = document.getElementById('quotationPrefillBanner');
                    if (quotationConversionData) {
                        if (qBanner) {
                            qBanner.style.display = '';
                            qBanner.querySelector('.qr-client-heading').textContent =
                                'Converting ' + (quotationConversionData.client.name || 'a client') + '\'s quotation request.';
                            qBanner.querySelector('.qr-summary').innerHTML = formatQuotationSummaryLine(quotationConversionData);
                            if (typeof lucide !== 'undefined') lucide.createIcons();
                        }
                        // quotation_request_id hidden field was already set in runQuotationPrefill()
                        // and must survive the template step, so it's left untouched here.
                    } else {
                        // Normal (non-conversion) flow — make sure no stale conversion state lingers.
                        document.getElementById('projQuotationRequestIdHidden').value = '';
                        if (qBanner) qBanner.style.display = 'none';
                    }

                    openModal('addProjectModal');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }

            document.querySelectorAll('.modal-overlay').forEach(function(modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) closeModal(this.id);
                });
            });

            initializeCustomInputs();
            initializeFormValidation();
            initializeClientListSearch();

            // Date validation: no past dates, end date must be >= start date (Add form)
            var addStart = document.getElementById('addProjectStartDate');
            var addEnd   = document.getElementById('addProjectEndDate');
            if (addStart && addEnd) {
                var today = new Date().toISOString().split('T')[0];
                addStart.min = today;
                addEnd.min   = today;

                addStart.addEventListener('change', function() {
                    addEnd.min = this.value || today;
                    if (addEnd.value && addEnd.value < this.value) {
                        addEnd.value = this.value;
                    }
                    addEnd.setCustomValidity('');
                });
                addEnd.addEventListener('change', function() {
                    if (addStart.value && this.value < addStart.value) {
                        this.setCustomValidity('End date must be on or after the start date.');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();
        });

        // ── Real-time: live-resort the client list when any project's status changes ──
        // Public channel (no per-user targeting needed) — reuses the Pusher connection
        // the header partial already opened, if Pusher is configured at all.
        if (window.__pusherClient) {
            window.__pusherClient.subscribe('admin-projects-updates')
                .bind('project.status.changed', function () {
                    refreshClientList();
                });
        }
    </script>
</body>
</html>