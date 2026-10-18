{{-- Edit / Archive / Assign Employees modals + the handlers for the row buttons from
     partials.admin.project_action_buttons. Shared by the Projects page (Active tab) and a client's page.
     The including page provides openModal(), closeModal() and escapeHtml(). --}}
    <!-- ===================== EDIT PROJECT MODAL ===================== -->
    <div class="modal-overlay" id="editProjectModal">
        <div class="modal-card modal-large" style="display:flex;flex-direction:column;overflow:hidden;">
            <div class="modal-header">
                <div>
                    <h2>Edit Project</h2>
                    <p id="editProjectSubtitle">Update project details.</p>
                </div>
                <button class="modal-close" type="button" id="closeEditProjectModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" id="editProjectForm" style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
                @csrf
                @method('PUT')

                {{-- Scrollable body --}}
                <div style="overflow-y:auto;flex:1;padding:0 28px 8px;">
                    <div class="form-section-label">Project Details</div>
                    <div class="form-grid">
                        <div class="form-group form-group-full">
                            <label>Project Name</label>
                            <input type="text" name="name" id="editProjectName" required placeholder="Project name">
                        </div>
                    </div>

                    {{-- Tank specifications are not editable here: the approved quotation is their source of truth --}}
                    <div id="editSpecsNote" style="margin-top:16px;padding:12px 14px;border:1px solid var(--border);border-radius:12px;background:var(--cream-soft);">
                        <div style="display:flex;align-items:center;gap:6px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);">
                            <i data-lucide="lock" style="width:13px;height:13px;"></i> Tank Specifications
                        </div>
                        <div id="editSpecsList" style="margin-top:6px;font-size:13px;font-weight:700;color:var(--dark);line-height:1.6;"></div>
                        <div style="margin-top:4px;font-size:12px;color:var(--muted);line-height:1.5;">Set by the approved quotation — tank type, capacity, dimensions and quantity can't be changed here.</div>
                    </div>

                    <div class="form-section-label" style="margin-top:18px;">Schedule</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" id="editProjectStartDate" required>
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" id="editProjectEndDate" required>
                        </div>
                    </div>
                    <p style="display:flex;align-items:flex-start;gap:6px;margin:8px 0 0;font-size:12px;color:var(--muted);line-height:1.5;">
                        <i data-lucide="info" style="width:13px;height:13px;flex-shrink:0;margin-top:2px;"></i>
                        <span>This schedule covers the project from <strong>Planning to Completion</strong> — delivery isn't included. The End Date is the planned completion date used for the On-Time Completion Rate.</span>
                    </p>
                </div>

                {{-- Fixed action buttons --}}
                <div class="modal-actions" style="flex-shrink:0;border-top:1px solid var(--border);padding-top:16px;margin-top:0;">
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
        <div class="modal-card" style="max-width:560px;">
            <div class="modal-header">
                <div>
                    <h2>Assign Employees</h2>
                    <p id="assignEmployeesSubtitle">Select employees to assign to this project.</p>
                </div>
                <button class="modal-close" type="button" id="closeAssignEmployeesModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="search-box" style="margin:0 auto 14px;max-width:100%;">
                <i data-lucide="search"></i>
                <input type="text" id="employeeSelectSearch" placeholder="Search employee by name or role...">
            </div>

            <form id="assignEmployeesForm" method="POST" action="">
                @csrf
                <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding-bottom:14px;margin-bottom:14px;border-bottom:1px solid var(--border);">
                    <div style="display:flex;align-items:center;gap:18px;">
                        <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:var(--dark);cursor:pointer;">
                            <input type="checkbox" id="employeeSelectAllCheckbox">
                            Select All
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:var(--dark);cursor:pointer;">
                            <input type="checkbox" id="employeeRegularOnlyCheckbox">
                            Regular only
                        </label>
                    </div>
                    <label style="display:flex;align-items:center;gap:8px;font-size:12.5px;font-weight:700;color:var(--muted);white-space:nowrap;">
                        Est. Working Days
                        <input type="number" name="estimated_working_days" id="assignEstWorkingDays" min="0" step="0.01" placeholder="e.g. 14"
                               onwheel="this.blur()"
                               style="width:76px;padding:7px 8px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-weight:700;color:var(--dark);text-align:center;">
                    </label>
                </div>
                <div id="employeeSelectList" class="cs-list">
                    <p style="text-align:center;color:var(--muted);padding:32px 0;font-size:14px;">Loading employees...</p>
                </div>

                <div class="modal-actions" style="margin-top:16px;">
                    <button type="button" class="cancel-btn" id="cancelAssignEmployees">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="check"></i>
                        Save Assignments
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        @php $projectViewBase = url('/admin/project-view'); @endphp
        const PROJECT_VIEW_URL = "{{ $projectViewBase }}";

        @php $employeeListUrl = route('admin.employee.list'); @endphp
        const EMPLOYEE_LIST_URL = "{{ $employeeListUrl }}";

        var allEmployees = [];

        function fetchEmployees() {
            return fetch(EMPLOYEE_LIST_URL, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) { allEmployees = data; return data; })
            .catch(function(err) { console.error('Failed to fetch employees:', err); return []; });
        }

        var selectedEmployeeIds = [];
        var originalAssignedIds = [];
        var regularOnlyFilter   = false;

        function getFilteredEmployees(employees, filter) {
            var q = (filter || '').toLowerCase();
            return employees.filter(function(e) {
                var matchesSearch = !q || e.name.toLowerCase().indexOf(q) !== -1 ||
                                     (e.role && e.role.toLowerCase().indexOf(q) !== -1);
                var matchesType   = !regularOnlyFilter || (e.type && e.type.toLowerCase() === 'regular');
                return matchesSearch && matchesType;
            });
        }

        function updateSelectAllCheckbox(filtered) {
            var cb = document.getElementById('employeeSelectAllCheckbox');
            if (!cb) return;
            cb.checked = filtered.length > 0 && filtered.every(function(e) {
                return selectedEmployeeIds.indexOf(e.id) !== -1;
            });
        }

        function renderEmployeeAssignList(employees, filter) {
            var list = document.getElementById('employeeSelectList');
            if (!list) return;
            var filtered = getFilteredEmployees(employees, filter);

            list.innerHTML = '';

            if (filtered.length === 0) {
                list.innerHTML = '<p style="text-align:center;color:var(--muted);padding:20px 0;font-size:14px;font-weight:700;">No active employees found.</p>';
                updateSelectAllCheckbox(filtered);
                return;
            }

            filtered.forEach(function(employee, idx) {
                var isSelected         = selectedEmployeeIds.indexOf(employee.id) !== -1;
                var wasAlreadyAssigned = originalAssignedIds.indexOf(employee.id) !== -1;
                var item       = document.createElement('div');
                item.className = 'client-select-item' + (isSelected ? ' selected' : '');
                var init    = employee.name.charAt(0).toUpperCase();
                var roleStr = [employee.role, employee.type].filter(Boolean).join(' · ');
                var alreadyAddedBadge = wasAlreadyAssigned
                    ? '<span style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#15803D;background:#F0FDF4;border:1px solid #BBF7D0;padding:1px 7px;border-radius:999px;white-space:nowrap;">Already added</span>'
                    : '';
                item.innerHTML =
                    '<div class="cs-avatar"><span class="cs-avatar-init">' + init + '</span></div>' +
                    '<div class="cs-info">' +
                        '<div class="cs-name">' + employee.name + '</div>' +
                        '<div class="cs-meta" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">' +
                            '<span>' + (roleStr || '—') + '</span>' + alreadyAddedBadge +
                        '</div>' +
                    '</div>' +
                    '<div class="client-select-check" style="display:' + (isSelected ? 'flex' : 'none') + ';align-items:center;margin-left:auto;">' +
                        '<i data-lucide="check-circle" style="width:20px;height:20px;"></i>' +
                    '</div>';

                item.addEventListener('click', function() {
                    var i = selectedEmployeeIds.indexOf(employee.id);
                    if (i === -1) {
                        selectedEmployeeIds.push(employee.id);
                        item.classList.add('selected');
                        item.querySelector('.client-select-check').style.display = 'flex';
                    } else {
                        selectedEmployeeIds.splice(i, 1);
                        item.classList.remove('selected');
                        item.querySelector('.client-select-check').style.display = 'none';
                    }
                    updateSelectAllCheckbox(filtered);
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
                list.appendChild(item);
            });
            updateSelectAllCheckbox(filtered);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function openAssignEmployeesModal(projectId, projectName, assignedIds, estDays) {
            selectedEmployeeIds = assignedIds.slice();
            originalAssignedIds = assignedIds.slice();
            regularOnlyFilter   = false;
            var si = document.getElementById('employeeSelectSearch');
            if (si) si.value = '';
            var regularOnlyCb = document.getElementById('employeeRegularOnlyCheckbox');
            if (regularOnlyCb) regularOnlyCb.checked = false;
            var shortName = projectName.length > 35 ? projectName.substring(0, 35) + '…' : projectName;
            document.getElementById('assignEmployeesSubtitle').textContent = shortName;
            document.getElementById('assignEmployeesForm').action = '/admin/projects/' + projectId + '/assign-employees';
            var estDaysInput = document.getElementById('assignEstWorkingDays');
            if (estDaysInput) estDaysInput.value = parseFloat(estDays) || 0;
            var list = document.getElementById('employeeSelectList');
            list.innerHTML = '<p style="text-align:center;color:var(--muted);padding:20px 0;">Loading employees...</p>';
            openModal('assignEmployeesModal');
            fetchEmployees().then(function(employees) { renderEmployeeAssignList(employees, ''); });
        }

        var employeeSelectAllCb = document.getElementById('employeeSelectAllCheckbox');
        if (employeeSelectAllCb) {
            employeeSelectAllCb.addEventListener('change', function () {
                var filtered = getFilteredEmployees(allEmployees, document.getElementById('employeeSelectSearch').value);
                if (this.checked) {
                    filtered.forEach(function (e) {
                        if (selectedEmployeeIds.indexOf(e.id) === -1) selectedEmployeeIds.push(e.id);
                    });
                } else {
                    filtered.forEach(function (e) {
                        var i = selectedEmployeeIds.indexOf(e.id);
                        if (i !== -1) selectedEmployeeIds.splice(i, 1);
                    });
                }
                renderEmployeeAssignList(allEmployees, document.getElementById('employeeSelectSearch').value);
            });
        }

        var employeeRegularOnlyCb = document.getElementById('employeeRegularOnlyCheckbox');
        if (employeeRegularOnlyCb) {
            employeeRegularOnlyCb.addEventListener('change', function () {
                regularOnlyFilter = this.checked;
                var searchText = document.getElementById('employeeSelectSearch').value;
                if (this.checked) {
                    getFilteredEmployees(allEmployees, searchText).forEach(function (e) {
                        if (selectedEmployeeIds.indexOf(e.id) === -1) selectedEmployeeIds.push(e.id);
                    });
                }
                renderEmployeeAssignList(allEmployees, searchText);
            });
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

        document.addEventListener('DOMContentLoaded', function() {
            initializeViewButtons();

            // Edit Project Modal
            document.querySelectorAll('.edit-project-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.getElementById('editProjectName').value      = this.dataset.name;
                    document.getElementById('editProjectStartDate').value = this.dataset.startDate;
                    document.getElementById('editProjectEndDate').value   = this.dataset.endDate;
                    document.getElementById('editProjectEndDate').min     = this.dataset.startDate;
                    document.getElementById('editProjectSubtitle').textContent = 'Editing: ' + this.dataset.name;
                    document.getElementById('editProjectForm').action = '/admin/projects/' + this.dataset.id;

                    // Tank specifications — shown read-only (they come from the approved quotation)
                    var items = [];
                    try { items = JSON.parse(this.dataset.tankItems || '[]'); } catch(e) {}
                    document.getElementById('editSpecsList').innerHTML = items.length
                        ? items.map(function (item) {
                            return escapeHtml((item.quantity > 1 ? item.quantity + '× ' : '') + (item.tank_type || 'Tank')) +
                                [item.capacity, item.dimensions].filter(Boolean).map(function (v) { return ' · ' + escapeHtml(v); }).join('');
                        }).join('<br>')
                        : '<span style="color:var(--muted);font-weight:600;">No tank specifications on record.</span>';

                    openModal('editProjectModal');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            });
            ['closeEditProjectModal', 'cancelEditProject'].forEach(function(id) {
                var btn = document.getElementById(id);
                if (btn) btn.addEventListener('click', function() { closeModal('editProjectModal'); });
            });

            // Date validation: end date must be >= start date (Edit form)
            var editStart = document.getElementById('editProjectStartDate');
            var editEnd   = document.getElementById('editProjectEndDate');
            if (editStart && editEnd) {
                editStart.addEventListener('change', function() {
                    editEnd.min = this.value;
                    if (editEnd.value && editEnd.value < this.value) {
                        editEnd.value = this.value;
                    }
                });
                editEnd.addEventListener('change', function() {
                    if (editStart.value && this.value < editStart.value) {
                        this.setCustomValidity('End date must be on or after the start date.');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }

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
                    openAssignEmployeesModal(this.dataset.id, this.dataset.name, assignedIds, this.dataset.estDays);
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
        });
    </script>
