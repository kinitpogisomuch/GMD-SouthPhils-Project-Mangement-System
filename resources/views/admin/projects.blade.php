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

            <div class="table-card" style="padding-bottom:0;">
                <div class="table-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="projectSearch" placeholder="Search project or client...">
                    </div>
                    <div class="filter-tabs" id="projectFilterTabs">
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
                    <table class="data-table" id="projectsTable">
                        <colgroup>
                            <col style="width:28%;">
                            <col style="width:13%;">
                            <col style="width:11%;">
                            <col style="width:11%;">
                            <col style="width:11%;">
                            <col style="width:10%;">
                            <col style="width:16%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Client</th>
                                <th style="text-align:center;">Start Date</th>
                                <th style="text-align:center;">End Date</th>
                                <th style="text-align:center;">Status</th>
                                <th style="text-align:center;">Progress</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projects as $project)
                            <tr data-status="{{ $project->status }}">
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
                                    </span>
                                </td>
                                <td><span class="client-pill">{{ $project->client }}</span></td>
                                <td style="white-space:nowrap;text-align:center;">{{ $project->start_date->format('M d, Y') }}</td>
                                <td style="white-space:nowrap;text-align:center;">{{ $project->end_date->format('M d, Y') }}</td>
                                @php
                                    $phase = strtolower($project->current_phase ?? 'planning');
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
                                    ];
                                    $pc = $phaseColors[$phase] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280','shadow'=>'rgba(0,0,0,.1)'];
                                    $phaseLabel = ucwords(str_replace('_', ' ', $phase));
                                @endphp
                                <td style="text-align:center;">
                                    <span class="status-badge" style="background:{{ $pc['bg'] }};color:{{ $pc['color'] }};box-shadow:0 0 0 1px {{ $pc['shadow'] }},0 2px 8px {{ $pc['shadow'] }};">
                                        {{ $phaseLabel }}
                                    </span>
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
                                        data-assigned="{{ $project->assignedEmployees->pluck('id')->toJson() }}">
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
                                <td colspan="7" style="text-align:center; padding:40px; color:var(--muted);">
                                    No projects found. Click <strong>Add Project</strong> to get started.
                                </td>
                            </tr>
                            @endforelse
                            @if($projects->isNotEmpty())
                            <tr id="noProjectsRow" style="display:none;">
                                <td colspan="7" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="folder-open" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;" id="noProjectsMsg">No projects in this category.</div>
                                    <div style="font-size:13px;margin-top:4px;">Try switching to a different tab or add a new project.</div>
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
        <div class="modal-card" style="max-width:560px;">
            <div class="modal-header">
                <div>
                    <h2>Choose a Starting Point</h2>
                    <p>Reuse a saved template or start with a blank project.</p>
                </div>
                <button class="modal-close" type="button" id="closeSelectTemplateModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="search-box" style="margin:0 auto 14px;max-width:100%;">
                <i data-lucide="search"></i>
                <input type="text" id="templateSelectSearch" placeholder="Search templates...">
            </div>

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

            <div id="templateSelectList" class="cs-list">
                <!-- rendered by JS -->
            </div>

            <div class="modal-actions" style="margin-top:16px;">
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

                {{-- Scrollable body --}}
                <div style="overflow-y:auto;flex:1;padding:0 28px 8px;">

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

                    <div style="margin-top:18px;margin-bottom:10px;display:flex;align-items:center;gap:10px;">
                        <div style="background:linear-gradient(180deg,#333 0%,#2a2a2a 100%);color:#fff;font-size:10px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;padding:4px 12px;border-radius:999px;">Project Specifications</div>
                        <div style="flex:1;height:1px;background:linear-gradient(90deg,#333,transparent);"></div>
                    </div>
                    <div id="editTankItemsContainer"></div>

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

            filtered.forEach(function(employee, idx) {
                var isSelected = selectedEmployeeIds.indexOf(employee.id) !== -1;
                var item       = document.createElement('div');
                item.className = 'client-select-item' + (isSelected ? ' selected' : '');
                var init    = employee.name.charAt(0).toUpperCase();
                var roleStr = [employee.role, employee.type].filter(Boolean).join(' · ');
                item.innerHTML =
                    '<div class="cs-avatar"><span class="cs-avatar-init">' + init + '</span></div>' +
                    '<div class="cs-info">' +
                        '<div class="cs-name">' + employee.name + '</div>' +
                        '<div class="cs-meta" style="display:block;">' + (roleStr || '—') + '</div>' +
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
            var shortName = projectName.length > 35 ? projectName.substring(0, 35) + '…' : projectName;
            document.getElementById('assignEmployeesSubtitle').textContent = shortName;
            document.getElementById('assignEmployeesForm').action = '/admin/projects/' + projectId + '/assign-employees';
            var list = document.getElementById('employeeSelectList');
            list.innerHTML = '<p style="text-align:center;color:var(--muted);padding:20px 0;">Loading employees...</p>';
            openModal('assignEmployeesModal');
            fetchEmployees().then(function(employees) { renderEmployeeAssignList(employees, ''); });
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

        /* ── Project templates (reusable tank specs) ── */
        @php
            $templateOptionsForJs = $projectTemplates->map(function ($t) {
                return [
                    'id'           => $t->id,
                    'name'         => $t->name,
                    'project_name' => $t->project_name,
                    'tank_items'   => $t->tank_items,
                ];
            })->values();
        @endphp
        var PROJECT_TEMPLATES = @json($templateOptionsForJs);

        function loadTemplateIntoAddForm(templateId) {
            var tpl = PROJECT_TEMPLATES.find(function(t) { return String(t.id) === String(templateId); });
            if (!tpl) return;

            document.getElementById('tankItemsContainer').innerHTML = '';
            addTankIndex = 0;
            (tpl.tank_items || []).forEach(function(item) { addTankRow(item); });
            if (!tpl.tank_items || !tpl.tank_items.length) addTankRow();

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

        var editTankIndex = 0;
        function addEditTankRow(item) {
            var container = document.getElementById('editTankItemsContainer');
            var prefix = 'tank_items[' + editTankIndex + ']';
            var removable = container.children.length > 0;
            container.appendChild(buildTankRow(prefix, item, removable));
            editTankIndex++;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        // Init add modal with one empty row
        document.addEventListener('DOMContentLoaded', function() {
            addTankRow();
        });

        // Reset add modal tank rows when opened
        var origOpenAddProject = document.getElementById('openAddProjectModal');
        if (origOpenAddProject) {
            origOpenAddProject.addEventListener('click', function() {
                document.getElementById('tankItemsContainer').innerHTML = '';
                addTankIndex = 0;
                addTankRow();
            });
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

        var currentArchiveFilter = 'active';

        var projectFilterMessages = {
            'active':    'No active projects yet.',
            'completed': 'No completed projects yet.',
            'archived':  'No archived projects.'
        };

        function applyProjectFilters() {
            var q            = (document.getElementById('projectSearch').value || '').toLowerCase();
            var visibleCount = 0;

            document.querySelectorAll('#projectsTable tbody tr').forEach(function(row) {
                if (row.id === 'noProjectsRow' || !row.dataset.status) return;
                var status      = (row.dataset.status || '').toLowerCase();
                var matchSearch = row.textContent.toLowerCase().indexOf(q) !== -1;
                var matchFilter = (currentArchiveFilter === 'archived'  && status === 'archived')
                    || (currentArchiveFilter === 'completed' && status === 'completed')
                    || (currentArchiveFilter === 'active'    && status !== 'archived' && status !== 'completed');
                var show = matchSearch && matchFilter;
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
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
                    openSelectTemplateModal();
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
                if (!items.length) return '<span style="font-size:12px;color:var(--muted);">No tank specs saved</span>';
                var pillStyle = 'display:inline-flex;align-items:center;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:600;margin-right:4px;white-space:nowrap;';
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

            function openSelectTemplateModal() {
                selectedTemplateId = 'custom';
                setCustomTemplateSelected(true);
                var si = document.getElementById('templateSelectSearch');
                if (si) si.value = '';
                renderTemplateSelectList('');
                openModal('selectTemplateModal');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }

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
                    } else {
                        addTankRow();
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
            initializeViewButtons();
            initializeSearch();
            initializeArchiveFilter();
            initializeFormValidation();
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

                    // Load tank items
                    var container = document.getElementById('editTankItemsContainer');
                    container.innerHTML = '';
                    var items = [];
                    try { items = JSON.parse(this.dataset.tankItems || '[]'); } catch(e) {}
                    if (!items.length) items = [{ tank_type: '', capacity: '', dimensions: '', quantity: 1 }];
                    items.forEach(function(item) { addEditTankRow(item); });

                    openModal('editProjectModal');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            });
            ['closeEditProjectModal', 'cancelEditProject'].forEach(function(id) {
                var btn = document.getElementById(id);
                if (btn) btn.addEventListener('click', function() { closeModal('editProjectModal'); });
            });

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