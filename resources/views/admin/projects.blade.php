<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
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

            <div class="table-card">
                <div class="table-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="projectSearch" placeholder="Search project or client...">
                    </div>
                    <div class="filter-tabs" id="projectFilterTabs">
                        <button type="button" class="filter-tab active" data-filter="active">Active</button>
                        <button type="button" class="filter-tab" data-filter="completed">Completed</button>
                        <button type="button" class="filter-tab" data-filter="archived">Archived</button>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="projectsTable">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Client</th>
                                <th>Tank Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projects as $project)
                            <tr data-status="{{ $project->status }}">
                                <td><strong>{{ $project->name }}</strong></td>
                                <td>{{ $project->client }}</td>
                                <td>{{ $project->tank_type }}</td>
                                <td>{{ $project->start_date->format('M d, Y') }}</td>
                                <td>{{ $project->end_date->format('M d, Y') }}</td>
                                <td>
                                    <span class="status-badge {{ strtolower($project->status ?? 'planning') }}">
                                        {{ ucfirst($project->status ?? 'Planning') }}
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;min-width:110px;">
                                        <div class="progress-bar-wrap" style="flex:1;max-width:80px;">
                                            <div class="progress-bar" style="width:{{ $project->progress ?? 0 }}%"></div>
                                        </div>
                                        <span class="progress-label">{{ $project->progress ?? 0 }}%</span>
                                    </div>
                                </td>
                                <td class="action-cell">
                                    <button class="action-btn view project-view-btn" type="button" title="View Project"
                                        data-id="{{ $project->id }}">
                                        <i data-lucide="eye"></i>
                                    </button>
                                    <button class="action-btn view assign-employee-btn" type="button" title="Assign Employees"
                                        data-id="{{ $project->id }}"
                                        data-name="{{ $project->name }}"
                                        data-assigned="{{ $project->assignedEmployees->pluck('id')->toJson() }}">
                                        <i data-lucide="users"></i>
                                    </button>
                                    <button class="action-btn view edit-project-btn" type="button" title="Edit Project"
                                        data-id="{{ $project->id }}"
                                        data-name="{{ $project->name }}"
                                        data-tank-type="{{ $project->tank_type }}"
                                        data-start-date="{{ $project->start_date->format('Y-m-d') }}"
                                        data-end-date="{{ $project->end_date->format('Y-m-d') }}"
                                        data-notes="{{ $project->notes }}">
                                        <i data-lucide="pencil"></i>
                                    </button>
                                    <button class="action-btn view archive-project-btn" type="button"
                                        title="{{ $project->status === 'archived' ? 'Restore Project' : 'Archive Project' }}"
                                        data-id="{{ $project->id }}"
                                        data-name="{{ $project->name }}"
                                        data-archived="{{ $project->status === 'archived' ? '1' : '0' }}">
                                        <i data-lucide="{{ $project->status === 'archived' ? 'archive-restore' : 'archive' }}"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" style="text-align:center; padding:40px; color:var(--muted);">
                                    No projects found. Click <strong>Add Project</strong> to get started.
                                </td>
                            </tr>
                            @endforelse
                            @if($projects->isNotEmpty())
                            <tr id="noProjectsRow" style="display:none;">
                                <td colspan="8" style="text-align:center; padding:40px; color:var(--muted);">
                                    No projects match this filter.
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
        <div class="modal-card" style="max-width:660px;">
            <div class="modal-header">
                <div>
                    <h2>Select Client</h2>
                    <p>Choose a client before filling in the project details.</p>
                </div>
                <button class="modal-close" type="button" id="closeSelectClientModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="search-box" style="margin-bottom:16px;">
                <i data-lucide="search"></i>
                <input type="text" id="clientSelectSearch" placeholder="Search client by name or address...">
            </div>

            <div id="clientSelectList"
                 style="display:flex;flex-direction:column;gap:8px;max-height:360px;overflow-y:auto;padding-right:4px;">
                <p style="text-align:center;color:var(--muted);padding:20px 0;">Loading clients...</p>
            </div>

            <div class="modal-actions" style="margin-top:20px;">
                <button type="button" class="cancel-btn" id="cancelSelectClient">Cancel</button>
                <button type="button" class="save-btn" id="continueSelectClient">
                    <i data-lucide="arrow-right"></i>
                    Continue
                </button>
            </div>
        </div>
    </div>

    <!-- ===================== ADD PROJECT MODAL ===================== -->
    <div class="modal-overlay" id="addProjectModal">
        <div class="modal-card modal-large">
            <div class="modal-header">
                <div>
                    <h2>Add Project</h2>
                    <p>Client information has been auto-filled. Complete the project details below.</p>
                </div>
                <button class="modal-close" type="button" id="closeAddProjectModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form id="addProjectForm" method="POST" action="{{ route('admin.project.store') }}">
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

                <!-- Project Details -->
                <div class="form-section-label">Project Details</div>
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Project Name</label>
                        <select id="projectNameSelect" required>
                            <option value="">Select project name</option>
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

                <!-- Client Information -->
                <div class="form-section-label" style="margin-top:18px;">
                    Client Information
                    <span style="font-size:12px;font-weight:700;color:var(--accent);text-transform:none;letter-spacing:0;">&nbsp;— Auto-filled from selection</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Client Name</label>
                        <input type="text" id="projClientName" readonly
                               style="background-color:var(--cream-soft);cursor:default;font-weight:800;">
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" id="projClientContact" readonly
                               style="background-color:var(--cream-soft);cursor:default;">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" id="projClientEmail" readonly
                               style="background-color:var(--cream-soft);cursor:default;">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" id="projClientAddress" readonly
                               style="background-color:var(--cream-soft);cursor:default;">
                    </div>
                </div>

                <!-- Tank Specifications -->
                <div class="form-section-label" style="margin-top:18px;">Tank Specifications</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Tank Type</label>
                        <select name="tank_type" required>
                            <option value="">Select tank type</option>
                            <option value="Fuel Day Tank">Fuel Day Tank</option>
                            <option value="Cooking Oil Storage Tank">Cooking Oil Storage Tank</option>
                            <option value="Underground Fuel Tank">Underground Fuel Tank</option>
                            <option value="Aboveground Fuel Tank">Aboveground Fuel Tank</option>
                            <option value="Polymer Tank">Polymer Tank</option>
                            <option value="Aboveground Water Tank">Aboveground Water Tank</option>
                            <option value="Chemical Tank">Chemical Tank</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tank Shape</label>
                        <select id="tankShape" onchange="updateDimensionFields()">
                            <option value="cylindrical">Cylindrical</option>
                            <option value="rectangular">Rectangular</option>
                        </select>
                    </div>
                </div>

                <!-- Dimensions -->
                <div class="form-section-label" style="margin-top:18px;">Dimensions</div>
                <div class="form-grid">
                    <div class="form-group" id="dimDiameterGroup">
                        <label id="dim1Label">Diameter (m)</label>
                        <input type="number" id="dim1" min="0" step="0.01"
                               placeholder="e.g. 2.00" oninput="computeCapacity()">
                    </div>
                    <div class="form-group" id="dimHeightGroup">
                        <label id="dim2Label">Height (m)</label>
                        <input type="number" id="dim2" min="0" step="0.01"
                               placeholder="e.g. 3.00" oninput="computeCapacity()">
                    </div>
                    <div class="form-group" id="dim3Group" style="display:none;">
                        <label>Height (m)</label>
                        <input type="number" id="dim3" min="0" step="0.01"
                               placeholder="e.g. 2.00" oninput="computeCapacity()">
                    </div>
                    <div class="form-group">
                        <label>Capacity <span style="font-weight:700;color:var(--accent);">(Auto-computed)</span></label>
                        <input type="text" id="projCapacityDisplay" readonly
                               placeholder="Enter dimensions above"
                               style="background-color:var(--cream-soft);cursor:default;font-weight:900;color:var(--dark);">
                    </div>
                </div>

                <!-- Schedule -->
                <div class="form-section-label" style="margin-top:18px;">Schedule</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" required>
                    </div>
                    <div class="form-group form-group-full">
                        <label>Notes</label>
                        <textarea name="notes" placeholder="Additional project notes..." rows="3"></textarea>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelAddProject">Cancel</button>
                    <button type="submit" class="save-btn" id="saveProjectBtn">
                        <i data-lucide="save"></i>
                        Save Project
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== EDIT PROJECT MODAL ===================== -->
    <div class="modal-overlay" id="editProjectModal">
        <div class="modal-card" style="max-width:560px;">
            <div class="modal-header">
                <div>
                    <h2>Edit Project</h2>
                    <p id="editProjectSubtitle">Update project details.</p>
                </div>
                <button class="modal-close" type="button" id="closeEditProjectModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" id="editProjectForm">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Project Name *</label>
                        <input type="text" name="name" id="editProjectName" required placeholder="Project name">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Tank Type *</label>
                        <select name="tank_type" id="editProjectTankType" required>
                            <option value="">Select tank type</option>
                            <option value="Fuel Day Tank">Fuel Day Tank</option>
                            <option value="Cooking Oil Storage Tank">Cooking Oil Storage Tank</option>
                            <option value="Underground Fuel Tank">Underground Fuel Tank</option>
                            <option value="Aboveground Fuel Tank">Aboveground Fuel Tank</option>
                            <option value="Polymer Tank">Polymer Tank</option>
                            <option value="Aboveground Water Tank">Aboveground Water Tank</option>
                            <option value="Chemical Tank">Chemical Tank</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Start Date *</label>
                        <input type="date" name="start_date" id="editProjectStartDate" required>
                    </div>
                    <div class="form-group">
                        <label>End Date *</label>
                        <input type="date" name="end_date" id="editProjectEndDate" required>
                    </div>
                    <div class="form-group form-group-full">
                        <label>Notes</label>
                        <textarea name="notes" id="editProjectNotes" rows="3" placeholder="Additional project notes..."></textarea>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelEditProject">Cancel</button>
                    <button type="submit" class="save-btn"><i data-lucide="save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== ARCHIVE PROJECT MODAL ===================== -->
    <div class="modal-overlay" id="archiveProjectModal">
        <div class="modal-card" style="max-width:420px;">
            <div class="modal-header">
                <div>
                    <h2 id="archiveProjectTitle">Archive Project</h2>
                    <p>This will change the project's status.</p>
                </div>
                <button class="modal-close" type="button" id="closeArchiveProjectModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="delete-confirm-body">
                <div class="delete-confirm-icon"><i data-lucide="archive"></i></div>
                <p id="archiveProjectMsg">Are you sure you want to archive this project?</p>
            </div>
            <form method="POST" id="archiveProjectForm">
                @csrf
                @method('PATCH')
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelArchiveProject">Cancel</button>
                    <button type="submit" class="save-btn" id="archiveProjectConfirmBtn">
                        <i data-lucide="archive"></i> Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== ASSIGN EMPLOYEES MODAL ===================== -->
    <div class="modal-overlay" id="assignEmployeesModal">
        <div class="modal-card" style="max-width:660px;">
            <div class="modal-header">
                <div>
                    <h2>Assign Employees</h2>
                    <p id="assignEmployeesSubtitle">Select employees to assign to this project.</p>
                </div>
                <button class="modal-close" type="button" id="closeAssignEmployeesModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="search-box" style="margin-bottom:16px;">
                <i data-lucide="search"></i>
                <input type="text" id="employeeSelectSearch" placeholder="Search employee by name or role...">
            </div>

            <form id="assignEmployeesForm" method="POST" action="">
                @csrf
                <div id="employeeSelectList"
                     style="display:flex;flex-direction:column;gap:8px;max-height:360px;overflow-y:auto;padding-right:4px;">
                    <p style="text-align:center;color:var(--muted);padding:20px 0;">Loading employees...</p>
                </div>

                <div class="modal-actions" style="margin-top:20px;">
                    <button type="button" class="cancel-btn" id="cancelAssignEmployees">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="check"></i>
                        Save Assignments
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        @php $projectViewBase = url('/admin/project-view'); @endphp
        const PROJECT_VIEW_URL = "{{ $projectViewBase }}";

        @php $clientListUrl = route('admin.client.list'); @endphp
        const CLIENT_LIST_URL = "{{ $clientListUrl }}";

        @php $employeeListUrl = route('admin.employee.list'); @endphp
        const EMPLOYEE_LIST_URL = "{{ $employeeListUrl }}";

        var allClients = [];

        function fetchClients() {
            return fetch(CLIENT_LIST_URL, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) { allClients = data; return data; })
            .catch(function(err) { console.error('Failed to fetch clients:', err); return []; });
        }

        var allEmployees = [];

        function fetchEmployees() {
            return fetch(EMPLOYEE_LIST_URL, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) { allEmployees = data; return data; })
            .catch(function(err) { console.error('Failed to fetch employees:', err); return []; });
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
                var item       = document.createElement('div');
                item.className = 'client-select-item' + (isSelected ? ' selected' : '');
                var init       = client.name.charAt(0).toUpperCase();
                item.innerHTML =
                    '<div class="client-select-avatar">' + init + '</div>' +
                    '<div class="client-select-info">' +
                        '<div class="client-select-name">' + client.name + '</div>' +
                        '<div class="client-select-meta">' +
                            '<span>' + (client.contact || '—') + '</span>' +
                            '<span>' + (client.address || '—') + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="client-select-check" style="display:' + (isSelected ? 'flex' : 'none') + ';align-items:center;">' +
                        '<i data-lucide="check-circle"></i>' +
                    '</div>';

                item.addEventListener('click', function() {
                    selectedClient = client;
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

        function openSelectClientModal() {
            selectedClient = null;
            var si = document.getElementById('clientSelectSearch');
            if (si) si.value = '';
            var list = document.getElementById('clientSelectList');
            list.innerHTML = '<p style="text-align:center;color:var(--muted);padding:20px 0;">Loading clients...</p>';
            openModal('selectClientModal');
            fetchClients().then(function(clients) { renderClientSelectList(clients, ''); });
        }

        var selectedEmployeeIds = [];

        function renderEmployeeAssignList(employees, filter) {
            var list = document.getElementById('employeeSelectList');
            if (!list) return;
            var q = (filter || '').toLowerCase();
            var filtered = q
                ? employees.filter(function(e) {
                    return e.name.toLowerCase().indexOf(q) !== -1 ||
                           (e.role && e.role.toLowerCase().indexOf(q) !== -1);
                  })
                : employees;

            list.innerHTML = '';

            if (filtered.length === 0) {
                list.innerHTML = '<p style="text-align:center;color:var(--muted);padding:20px 0;font-size:14px;font-weight:700;">No active employees found.</p>';
                return;
            }

            filtered.forEach(function(employee) {
                var isSelected = selectedEmployeeIds.indexOf(employee.id) !== -1;
                var item       = document.createElement('div');
                item.className = 'client-select-item' + (isSelected ? ' selected' : '');
                var init       = employee.name.charAt(0).toUpperCase();
                item.innerHTML =
                    '<div class="client-select-avatar">' + init + '</div>' +
                    '<div class="client-select-info">' +
                        '<div class="client-select-name">' + employee.name + '</div>' +
                        '<div class="client-select-meta">' +
                            '<span>' + (employee.role || '—') + '</span>' +
                            '<span>' + (employee.type || '—') + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="client-select-check" style="display:' + (isSelected ? 'flex' : 'none') + ';align-items:center;">' +
                        '<i data-lucide="check-circle"></i>' +
                    '</div>';

                item.addEventListener('click', function() {
                    var idx = selectedEmployeeIds.indexOf(employee.id);
                    if (idx === -1) {
                        selectedEmployeeIds.push(employee.id);
                        item.classList.add('selected');
                        item.querySelector('.client-select-check').style.display = 'flex';
                    } else {
                        selectedEmployeeIds.splice(idx, 1);
                        item.classList.remove('selected');
                        item.querySelector('.client-select-check').style.display = 'none';
                    }
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
                list.appendChild(item);
            });
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function openAssignEmployeesModal(projectId, projectName, assignedIds) {
            selectedEmployeeIds = assignedIds.slice();
            var si = document.getElementById('employeeSelectSearch');
            if (si) si.value = '';
            document.getElementById('assignEmployeesSubtitle').textContent = 'Select employees to assign to "' + projectName + '".';
            document.getElementById('assignEmployeesForm').action = '/admin/projects/' + projectId + '/assign-employees';
            var list = document.getElementById('employeeSelectList');
            list.innerHTML = '<p style="text-align:center;color:var(--muted);padding:20px 0;">Loading employees...</p>';
            openModal('assignEmployeesModal');
            fetchEmployees().then(function(employees) { renderEmployeeAssignList(employees, ''); });
        }

        function populateClientFields(client) {
            document.getElementById('projClientName').value       = client.name;
            document.getElementById('projClientContact').value    = client.contact || '';
            document.getElementById('projClientEmail').value      = client.email || '';
            document.getElementById('projClientAddress').value    = client.address || '';
            document.getElementById('projClientNameHidden').value = client.name;
            document.getElementById('projContactHidden').value    = client.contact || '';
            document.getElementById('projEmailHidden').value      = client.email || '';
            document.getElementById('projAddressHidden').value    = client.address || '';
        }

        function updateDimensionFields() {
            var shape = document.getElementById('tankShape').value;
            var isCyl = (shape === 'cylindrical');
            document.getElementById('dim1Label').textContent      = isCyl ? 'Diameter (m)' : 'Length (m)';
            document.getElementById('dim2Label').textContent      = isCyl ? 'Height (m)'   : 'Width (m)';
            document.getElementById('dim3Group').style.display    = isCyl ? 'none' : '';
            ['dim1','dim2','dim3'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.value = '';
            });
            document.getElementById('projCapacityDisplay').value  = '';
            document.getElementById('projCapacityHidden').value   = '';
            document.getElementById('projDimensionsHidden').value = '';
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

        function viewProject(btn) {
            window.location.href = PROJECT_VIEW_URL + '/' + btn.getAttribute('data-id');
        }

        function initializeViewButtons() {
            document.querySelectorAll('.project-view-btn').forEach(function(btn) {
                btn.removeEventListener('click', btn._handler);
                btn._handler = function() { viewProject(btn); };
                btn.addEventListener('click', btn._handler);
            });
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
                if (!document.getElementById('projCapacityHidden').value) {
                    e.preventDefault();
                    alert('Please enter the tank dimensions to auto-compute capacity before saving.');
                    document.getElementById('dim1').focus();
                    return;
                }
            });
        }

        var currentArchiveFilter = 'active';

        function applyProjectFilters() {
            var q = (document.getElementById('projectSearch').value || '').toLowerCase();
            var visibleCount = 0;
            document.querySelectorAll('#projectsTable tbody tr').forEach(function(row) {
                if (row.id === 'noProjectsRow' || !row.dataset.status) return;
                var status = (row.dataset.status || '').toLowerCase();
                var matchSearch = row.textContent.toLowerCase().indexOf(q) !== -1;
                var matchFilter = (currentArchiveFilter === 'archived'  && status === 'archived')
                    || (currentArchiveFilter === 'completed' && status === 'completed')
                    || (currentArchiveFilter === 'active'    && status !== 'archived' && status !== 'completed');
                var visible = matchSearch && matchFilter;
                row.style.display = visible ? '' : 'none';
                if (visible) visibleCount++;
            });

            var noRow = document.getElementById('noProjectsRow');
            if (noRow) noRow.style.display = visibleCount === 0 ? '' : 'none';
        }

        function initializeSearch() {
            var input = document.getElementById('projectSearch');
            if (!input) return;
            input.addEventListener('keyup', applyProjectFilters);
        }

        function initializeArchiveFilter() {
            var tabs = document.querySelectorAll('#projectFilterTabs .filter-tab');
            if (!tabs.length) return;
            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    tabs.forEach(function(t) { t.classList.remove('active'); });
                    this.classList.add('active');
                    currentArchiveFilter = this.dataset.filter;
                    applyProjectFilters();
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            var openBtn = document.getElementById('openAddProjectModal');
            if (openBtn) openBtn.addEventListener('click', openSelectClientModal);

            ['closeSelectClientModal', 'cancelSelectClient'].forEach(function(id) {
                var btn = document.getElementById(id);
                if (btn) btn.addEventListener('click', function() { closeModal('selectClientModal'); });
            });

            var continueBtn = document.getElementById('continueSelectClient');
            if (continueBtn) {
                continueBtn.addEventListener('click', function() {
                    if (!selectedClient) { alert('Please select a client to continue.'); return; }
                    closeModal('selectClientModal');
                    populateClientFields(selectedClient);
                    updateDimensionFields();
                    openModal('addProjectModal');
                });
            }

            var cSearch = document.getElementById('clientSelectSearch');
            if (cSearch) {
                cSearch.addEventListener('input', function() {
                    renderClientSelectList(allClients, this.value);
                });
            }

            ['closeAddProjectModal', 'cancelAddProject'].forEach(function(id) {
                var btn = document.getElementById(id);
                if (btn) btn.addEventListener('click', function() { closeModal('addProjectModal'); });
            });

            document.querySelectorAll('.modal-overlay').forEach(function(modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) closeModal(this.id);
                });
            });

            initializeCustomInputs();
            initializeViewButtons();
            initializeSearch();
            initializeArchiveFilter();
            initializeFormValidation();
            applyProjectFilters();

            // Edit Project Modal
            document.querySelectorAll('.edit-project-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.getElementById('editProjectName').value      = this.dataset.name;
                    document.getElementById('editProjectNotes').value     = this.dataset.notes || '';
                    document.getElementById('editProjectStartDate').value = this.dataset.startDate;
                    document.getElementById('editProjectEndDate').value   = this.dataset.endDate;
                    document.getElementById('editProjectSubtitle').textContent = 'Editing: ' + this.dataset.name;
                    document.getElementById('editProjectForm').action = '/admin/projects/' + this.dataset.id;
                    var sel = document.getElementById('editProjectTankType');
                    sel.value = this.dataset.tankType;
                    if (!sel.value) {
                        var opt = document.createElement('option');
                        opt.value = this.dataset.tankType;
                        opt.textContent = this.dataset.tankType;
                        sel.appendChild(opt);
                        sel.value = this.dataset.tankType;
                    }
                    openModal('editProjectModal');
                });
            });
            ['closeEditProjectModal', 'cancelEditProject'].forEach(function(id) {
                var btn = document.getElementById(id);
                if (btn) btn.addEventListener('click', function() { closeModal('editProjectModal'); });
            });

            // Archive Project Modal
            document.querySelectorAll('.archive-project-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var isArchived = this.dataset.archived === '1';
                    var name = this.dataset.name;
                    document.getElementById('archiveProjectTitle').textContent = isArchived ? 'Restore Project' : 'Archive Project';
                    document.getElementById('archiveProjectMsg').textContent   = isArchived
                        ? 'Restore "' + name + '"? It will be moved back to active projects.'
                        : 'Archive "' + name + '"? It will be marked as archived but remain in the system.';
                    var confirmBtn = document.getElementById('archiveProjectConfirmBtn');
                    confirmBtn.innerHTML = isArchived
                        ? '<i data-lucide="archive-restore"></i> Restore'
                        : '<i data-lucide="archive"></i> Archive';
                    document.getElementById('archiveProjectForm').action = '/admin/projects/' + this.dataset.id + '/archive';
                    openModal('archiveProjectModal');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            });
            ['closeArchiveProjectModal', 'cancelArchiveProject'].forEach(function(id) {
                var btn = document.getElementById(id);
                if (btn) btn.addEventListener('click', function() { closeModal('archiveProjectModal'); });
            });

            // Assign Employees Modal
            document.querySelectorAll('.assign-employee-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var assignedIds = [];
                    try { assignedIds = JSON.parse(this.dataset.assigned || '[]'); } catch (e) {}
                    openAssignEmployeesModal(this.dataset.id, this.dataset.name, assignedIds);
                });
            });
            ['closeAssignEmployeesModal', 'cancelAssignEmployees'].forEach(function(id) {
                var btn = document.getElementById(id);
                if (btn) btn.addEventListener('click', function() { closeModal('assignEmployeesModal'); });
            });
            var eSearch = document.getElementById('employeeSelectSearch');
            if (eSearch) {
                eSearch.addEventListener('input', function() {
                    renderEmployeeAssignList(allEmployees, this.value);
                });
            }
            var assignForm = document.getElementById('assignEmployeesForm');
            if (assignForm) {
                assignForm.addEventListener('submit', function() {
                    assignForm.querySelectorAll('input[name="employee_ids[]"]').forEach(function(el) { el.remove(); });
                    selectedEmployeeIds.forEach(function(id) {
                        var input = document.createElement('input');
                        input.type  = 'hidden';
                        input.name  = 'employee_ids[]';
                        input.value = id;
                        assignForm.appendChild(input);
                    });
                });
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>