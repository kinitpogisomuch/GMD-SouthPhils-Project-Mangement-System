<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $client }} | Projects | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">
            <div class="page-header">
                <div>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <a href="{{ route('admin.projects') }}" style="font-size:13px;font-weight:700;color:var(--muted);text-decoration:none;">Projects</a>
                        <span style="font-size:13px;color:var(--muted);">/</span>
                        <span style="font-size:13px;font-weight:800;color:var(--dark);">{{ $client }}</span>
                    </div>
                    <h1>{{ $client }}'s Projects</h1>
                    <p>Manage this client's storage tank fabrication projects, timelines, and progress.</p>
                </div>
                <a href="{{ route('admin.projects') }}" class="cancel-btn" style="text-decoration:none;">
                    <i data-lucide="arrow-left"></i>
                    All Clients
                </a>
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

            <div class="table-card" style="padding-bottom:0;">
                <div class="table-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="projectSearch" placeholder="Search project...">
                    </div>
                    <div class="filter-tabs" id="projectFilterTabs">
                        <button type="button" class="filter-tab active" data-filter="active">
                            Active
                            <span class="filter-count">{{ $projects->whereNotIn('status', ['completed', 'archived'])->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="completed">
                            Completed Projects
                            <span class="filter-count">{{ $projects->where('status', 'completed')->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="archived">
                            Archived
                            <span class="filter-count">{{ $projects->where('status', 'archived')->count() }}</span>
                        </button>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="projectsTable">
                        <colgroup>
                            <col style="width:19%;">  {{-- Project Name --}}
                            <col style="width:7%;">   {{-- Capacity --}}
                            <col style="width:9%;">  {{-- Start Date --}}
                            <col style="width:9%;">  {{-- End Date --}}
                            <col style="width:11%;">  {{-- Project Status --}}
                            <col id="deliveryStatusCol" style="width:11%;">  {{-- Delivery Status --}}
                            <col style="width:12%;">  {{-- Payment --}}
                            <col style="width:8%;">   {{-- Progress --}}
                            <col style="width:14%;">  {{-- Action --}}
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th style="text-align:center;">Capacity</th>
                                <th style="text-align:center;">Start Date</th>
                                <th style="text-align:center;">End Date</th>
                                <th style="text-align:center;">Project Status</th>
                                <th class="delivery-status-col" style="text-align:center;">Delivery Status</th>
                                <th style="text-align:center;">Payment</th>
                                <th style="text-align:center;">Progress</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projects as $project)
                            @php $needsReview = in_array($project->id, $pendingUpdateProjectIds); @endphp
                            <tr data-status="{{ $project->status }}" data-delivery="{{ $project->deliveryStatus() }}" class="{{ $needsReview ? 'row-needs-action' : '' }}">
                                @php
                                    $namePrefix = '';
                                    $nameMain   = $project->name;
                                    if (preg_match('/^(Fabrication of)\s+(.+)$/i', $project->name, $nm)) {
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
                                        @if($needsReview)
                                        <span title="An employee submitted a progress update for this project — waiting on your review"
                                              style="display:inline-flex;align-items:center;gap:3px;margin-top:4px;font-size:10px;font-weight:800;color:#b45309;background:#fff3cd;border-radius:999px;padding:2px 8px;white-space:nowrap;width:fit-content;">
                                            <i data-lucide="bell" style="width:10px;height:10px;"></i>
                                            Update Pending Review
                                        </span>
                                        @endif
                                    </span>
                                </td>
                                <td style="white-space:nowrap;text-align:center;color:var(--muted);font-weight:700;">{{ $project->capacity ?: '—' }}</td>
                                <td style="white-space:nowrap;text-align:center;">{{ $project->start_date->format('M d, Y') }}</td>
                                <td style="white-space:nowrap;text-align:center;">{{ $project->end_date->format('M d, Y') }}</td>
                                @php
                                    $phase = strtolower($project->current_phase ?? 'planning');
                                    if ($project->status === 'completed') {
                                        $phase = 'completed';
                                    }
                                    $phaseColors = [
                                        'planning'    => ['bg'=>'#FEF3C7','color'=>'#92400E','shadow'=>'rgba(245,158,11,.2)'],
                                        'procurement' => ['bg'=>'#EDE9FE','color'=>'#5B21B6','shadow'=>'rgba(139,92,246,.2)'],
                                        'matl_prep'   => ['bg'=>'#CFFAFE','color'=>'#0E7490','shadow'=>'rgba(6,182,212,.2)'],
                                        'fabrication' => ['bg'=>'#2563EB','color'=>'#fff','shadow'=>'rgba(37,99,235,.3)'],
                                        'inspection'  => ['bg'=>'#EC4899','color'=>'#fff','shadow'=>'rgba(236,72,153,.3)'],
                                        'painting'    => ['bg'=>'#14B8A6','color'=>'#fff','shadow'=>'rgba(20,184,166,.3)'],
                                        'completion'  => ['bg'=>'#10B981','color'=>'#fff','shadow'=>'rgba(16,185,129,.3)'],
                                        'delivery'    => ['bg'=>'#059669','color'=>'#fff','shadow'=>'rgba(5,150,105,.3)'],
                                        'delayed'     => ['bg'=>'#EF4444','color'=>'#fff','shadow'=>'rgba(239,68,68,.3)'],
                                        'completed'   => ['bg'=>'#E7F6EC','color'=>'#207A3A','shadow'=>'rgba(32,122,58,.15)'],
                                        'awaiting_delivery' => ['bg'=>'#FFF4E0','color'=>'#B45309','shadow'=>'rgba(245,158,11,.2)'],
                                        'delivered'   => ['bg'=>'#207A3A','color'=>'#fff','shadow'=>'rgba(32,122,58,.3)'],
                                    ];
                                    $pc = $phaseColors[$phase] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280','shadow'=>'rgba(0,0,0,.1)'];
                                    $phaseLabel = ucwords(str_replace('_', ' ', $phase));
                                @endphp
                                {{-- Project Status: the current phase, or "Completed" once the Completion phase is done --}}
                                <td style="text-align:center;">
                                    <span class="status-badge" style="background:{{ $pc['bg'] }};color:{{ $pc['color'] }};box-shadow:0 0 0 1px {{ $pc['shadow'] }},0 2px 8px {{ $pc['shadow'] }};">
                                        {{ $phaseLabel }}
                                    </span>
                                </td>
                                {{-- Delivery Status: tracked separately from completion, and only shown once the
                                     project is completed (Awaiting Delivery → Delivered) --}}
                                <td class="delivery-status-col" style="text-align:center;">
                                    @if($project->status === 'completed')
                                    @php
                                        $deliveryKey = $project->deliveryStatus();
                                        $dc = $phaseColors[$deliveryKey];
                                    @endphp
                                    <span class="status-badge" style="background:{{ $dc['bg'] }};color:{{ $dc['color'] }};box-shadow:0 0 0 1px {{ $dc['shadow'] }},0 2px 8px {{ $dc['shadow'] }};">
                                        {{ $project->deliveryStatusLabel() }}
                                    </span>
                                    @else
                                    <span style="color:var(--muted);">—</span>
                                    @endif
                                </td>
                                @php
                                    $projPayment = $project->getPaymentRecord();
                                    if ($project->status === 'completed' && !$project->isPaymentStageSettled('final_payment')) {
                                        // Delivered before the final payment came in ("Proceed Anyway") — still owed
                                        [$payLabel, $payClass] = ['Final Payment Pending', 'pending'];
                                    } elseif ($projPayment) {
                                        [$payLabel, $payClass] = [$projPayment->status, \App\Models\Payment::statusBadgeClass($projPayment->status)];
                                    } else {
                                        [$payLabel, $payClass] = [null, null];
                                    }
                                @endphp
                                <td style="text-align:center;">
                                    @if($payLabel)
                                    <a href="{{ $projPayment ? route('admin.payments.show', $projPayment->id) : route('admin.payments') }}" title="Open this project's payments" style="text-decoration:none;">
                                        <span class="status-badge {{ $payClass }}" style="font-size:11px;line-height:1.3;text-align:center;white-space:normal;">
                                            @if($payLabel === 'Final Payment Pending')<i data-lucide="alert-triangle" style="width:11px;height:11px;vertical-align:-1px;"></i> @endif{{ $payLabel }}
                                        </span>
                                    </a>
                                    @else
                                    <span style="color:var(--muted);">—</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    @php
                                        $prog = $project->progress ?? 0;
                                        $progColor = $prog == 0 ? '#9CA3AF'
                                            : ($prog <= 25  ? '#F59E0B'
                                            : ($prog <= 50  ? '#3B82F6'
                                            : ($prog <= 75  ? '#6366F1'
                                            : ($prog < 100  ? '#10B981'
                                            :                 '#059669'))));
                                        $progBg = $prog == 0 ? '#F3F4F6'
                                            : ($prog <= 25  ? '#FEF3C7'
                                            : ($prog <= 50  ? '#DBEAFE'
                                            : ($prog <= 75  ? '#E0E7FF'
                                            : ($prog < 100  ? '#D1FAE5'
                                            :                 '#D1FAE5'))));
                                    @endphp
                                    <span class="status-badge" style="background:{{ $progBg }};color:{{ $progColor }};box-shadow:0 0 0 1px {{ $progColor }}22,0 2px 8px {{ $progColor }}33;font-weight:800;">
                                        {{ $prog }}%
                                    </span>
                                </td>
                                <td class="action-cell" style="text-align:center;">
                                    <button class="action-btn view project-view-btn" type="button" title="View Project"
                                        data-id="{{ $project->id }}">
                                        <i data-lucide="eye"></i>
                                    </button>
                                    @if($project->status !== 'completed')
                                    <button class="action-btn view assign-employee-btn" type="button" title="Assign Employees"
                                        data-id="{{ $project->id }}"
                                        data-name="{{ $project->name }}"
                                        data-assigned="{{ $project->assignedEmployees->pluck('id')->toJson() }}"
                                        data-est-days="{{ $project->estimated_working_days ?? 0 }}">
                                        <i data-lucide="users"></i>
                                    </button>
                                    <button class="action-btn view edit-project-btn" type="button" title="Edit Project"
                                        data-id="{{ $project->id }}"
                                        data-name="{{ $project->name }}"
                                        data-start-date="{{ $project->start_date->format('Y-m-d') }}"
                                        data-end-date="{{ $project->end_date->format('Y-m-d') }}"
                                        data-notes="{{ $project->notes }}"
                                        data-tank-items="{{ $project->tankItems->map(fn($t) => ['tank_type'=>$t->tank_type,'shape'=>$t->shape,'capacity'=>$t->capacity,'dimensions'=>$t->dimensions,'quantity'=>$t->quantity,'notes'=>$t->notes])->toJson() }}">
                                        <i data-lucide="pencil"></i>
                                    </button>
                                    <button class="action-btn view archive-project-btn" type="button"
                                        title="{{ $project->status === 'archived' ? 'Restore Project' : 'Archive Project' }}"
                                        data-id="{{ $project->id }}"
                                        data-name="{{ $project->name }}"
                                        data-archived="{{ $project->status === 'archived' ? '1' : '0' }}">
                                        <i data-lucide="{{ $project->status === 'archived' ? 'archive-restore' : 'archive' }}"></i>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" style="text-align:center; padding:40px; color:var(--muted);">
                                    No projects found for this client.
                                </td>
                            </tr>
                            @endforelse
                            @if($projects->isNotEmpty())
                            <tr id="noProjectsRow" style="display:none;">
                                <td colspan="9" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="folder-open" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;" id="noProjectsMsg">No projects in this category.</div>
                                    <div style="font-size:13px;margin-top:4px;">Try switching to a different tab.</div>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

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

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        @php $projectViewBase = url('/admin/project-view'); @endphp
        const PROJECT_VIEW_URL = "{{ $projectViewBase }}";

        @php $employeeListUrl = route('admin.employee.list'); @endphp
        const EMPLOYEE_LIST_URL = "{{ $employeeListUrl }}";

        function openModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
        }

        function closeModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
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

        var currentArchiveFilter = 'active';

        var projectFilterMessages = {
            'active':    'No active projects yet.',
            'completed': 'No completed projects yet.',
            'archived':  'No archived projects.'
        };

        // Delivery Status <col>, and the <col> right after it (where it's re-inserted)
        var deliveryColEl     = document.getElementById('deliveryStatusCol');
        var deliveryColAnchor = deliveryColEl ? deliveryColEl.nextElementSibling : null;

        function applyProjectFilters() {
            var q            = (document.getElementById('projectSearch').value || '').toLowerCase();
            var visibleCount = 0;

            document.querySelectorAll('#projectsTable tbody tr').forEach(function(row) {
                if (row.id === 'noProjectsRow' || !row.dataset.status) return;
                var status      = (row.dataset.status || '').toLowerCase();
                var matchSearch = row.textContent.toLowerCase().indexOf(q) !== -1;
                // A project moves to Completed Projects as soon as it's completed (Completion phase
                // done); its delivery is then tracked there in the Delivery Status column
                var matchFilter = (currentArchiveFilter === 'archived'  && status === 'archived')
                    || (currentArchiveFilter === 'completed' && status === 'completed')
                    || (currentArchiveFilter === 'active'    && status !== 'archived' && status !== 'completed');
                var show = matchSearch && matchFilter;
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            // The Delivery Status column belongs to the Completed Projects tab (even when it's empty)
            var showDeliveryCol = currentArchiveFilter === 'completed';
            document.querySelectorAll('#projectsTable .delivery-status-col').forEach(function (cell) {
                cell.style.display = showDeliveryCol ? '' : 'none';
            });
            // The column's <col> has to leave the colgroup too — otherwise the cells after it slide
            // into its slot (Payment would land in the hidden Delivery column)
            if (deliveryColEl) {
                if (showDeliveryCol && !deliveryColEl.parentNode) {
                    deliveryColAnchor.parentNode.insertBefore(deliveryColEl, deliveryColAnchor);
                } else if (!showDeliveryCol && deliveryColEl.parentNode) {
                    deliveryColEl.parentNode.removeChild(deliveryColEl);
                }
            }
            document.querySelectorAll('#projectsTable td[colspan]').forEach(function (cell) {
                cell.colSpan = showDeliveryCol ? 9 : 8;
            });

            var noRow = document.getElementById('noProjectsRow');
            var noMsg = document.getElementById('noProjectsMsg');
            if (noRow) {
                noRow.style.display = visibleCount === 0 ? '' : 'none';
                if (noMsg) noMsg.textContent = q
                    ? 'No projects match "' + q + '".'
                    : (projectFilterMessages[currentArchiveFilter] || 'No projects in this category.');
                if (visibleCount === 0 && typeof lucide !== 'undefined') lucide.createIcons();
            }
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
            document.querySelectorAll('.modal-overlay').forEach(function(modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) closeModal(this.id);
                });
            });

            initializeViewButtons();
            initializeSearch();
            initializeArchiveFilter();
            applyProjectFilters();

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

            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>
