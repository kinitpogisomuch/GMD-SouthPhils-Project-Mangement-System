<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body>

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1>Employees</h1>
                    <p>Manage employee records, contact details, and salary.</p>
                </div>
                <div style="display:flex;gap:10px;">
                    <button class="add-btn" type="button" id="openAddEmployeeModal">
                        <i data-lucide="plus"></i>
                        Add Employee
                    </button>
                    <button class="add-btn" type="button" id="openRecordPaymentModal" style="background:var(--dark-soft);">
                        <i data-lucide="banknote"></i>
                        Record Salary
                    </button>
                </div>
            </div>

            @if(session('success'))
            <div class="alert-banner success">
                <i data-lucide="check-circle"></i>
                {{ session('success') }}
            </div>
            @endif

            @if(session('account_error'))
            <div class="alert-banner error">
                <i data-lucide="alert-circle"></i>
                {{ session('account_error') }}
            </div>
            @endif

            <!-- Summary Cards -->
            <div class="page-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
                <div class="info-card">
                    <div class="info-card-icon"><i data-lucide="users"></i></div>
                    <h3>Total Employees</h3>
                    <div class="value" id="totalEmpCount">{{ $employees->where('status', 'Active')->count() }}</div>
                    <div class="info-card-sub">Active workforce</div>
                </div>
                <div class="info-card">
                    <div class="info-card-icon"><i data-lucide="banknote"></i></div>
                    <h3>Monthly Payroll</h3>
                    <div class="value" id="totalPayroll">₱ 0</div>
                    <div class="info-card-sub">Total salary expense</div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="emp-tabs">
                <button class="emp-tab active" data-tab="employees">
                    <i data-lucide="users"></i>
                    Employee List
                </button>
                <button class="emp-tab" data-tab="salary">
                    <i data-lucide="banknote"></i>
                    Salary Management
                </button>
            </div>

            <!-- ===== TAB: EMPLOYEES ===== -->
            <div class="emp-tab-content active" id="tab-employees">
                <div class="table-card">
                    <div class="table-toolbar">
                        <div class="search-box">
                            <i data-lucide="search"></i>
                            <input type="text" id="employeeSearch" placeholder="Search name or username...">
                        </div>
                        <div class="filter-group">
                            <select id="empTypeFilter" class="filter-select">
                                <option value="">All Types</option>
                                <option value="Regular">Regular</option>
                                <option value="Outsourced">Outsourced</option>
                            </select>
                            <select id="empStatusFilter" class="filter-select">
                                <option value="">All</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Archived</option>
                            </select>
                            <select id="empSortFilter" class="filter-select">
                                <option value="date-desc">Date Added (Newest First)</option>
                                <option value="date-asc">Date Added (Oldest First)</option>
                                <option value="name-asc">Name (A-Z)</option>
                                <option value="name-desc">Name (Z-A)</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table class="data-table" id="employeesTable">
                            <thead>
                                <tr>
                                    <th>Employee Name</th>
                                    <th>Username</th>
                                    <th>Contact Number</th>
                                    <th>Email</th>
                                    <th>Address</th>
                                    <th>Role</th>
                                    <th>Employee Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employees as $employee)
                                <tr
                                    data-status="{{ $employee->status }}"
                                    data-type="{{ $employee->employee_type ?? 'Regular' }}"
                                    data-name="{{ $employee->full_name }}"
                                    data-username="{{ strtolower($employee->username ?? '') }}"
                                    data-contact="{{ $employee->contact }}"
                                    data-role="{{ $employee->role }}"
                                    data-rate="{{ $employee->daily_rate ?? 0 }}"
                                    data-monthly="{{ ($employee->daily_rate ?? 0) * 26 }}"
                                    data-days="26"
                                    data-created="{{ $employee->created_at->timestamp }}">
                                    <td>
                                        {{ $employee->full_name }}
                                    </td>
                                    <td>
                                        <span style="font-family:monospace;font-weight:600;color:var(--text-secondary);font-size:13px;">
                                            {{ $employee->username ?? '—' }}
                                        </span>
                                    </td>
                                    <td>{{ $employee->contact ?? '—' }}</td>
                                    <td>{{ $employee->email ?? '—' }}</td>
                                    <td style="font-size:13px;color:var(--text-secondary);max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                                        title="{{ $employee->address ?? '' }}">
                                        @if($employee->province && $employee->city)
                                            {{ $employee->province }}, {{ $employee->city }}
                                        @else
                                            {{ $employee->address ?? '—' }}
                                        @endif
                                    </td>
                                    <td>{{ $employee->role }}</td>
                                    <td>
                                        <span class="role-badge" style="{{ ($employee->employee_type ?? 'Regular') === 'Regular' ? 'background:#ede9fe;color:#6d28d9;' : 'background:#fef3c7;color:#92400e;' }}">
                                            {{ $employee->employee_type ?? 'Regular' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($employee->status === 'Active')
                                        <span class="status-badge active">Active</span>
                                        @else
                                        <span class="status-badge archived">Archived</span>
                                        @endif
                                    </td>
                                    <td class="action-cell">
                                        <button class="action-btn view edit-emp-btn" type="button" title="Edit Employee"
                                            data-id="{{ $employee->id }}"
                                            data-first-name="{{ $employee->first_name }}"
                                            data-last-name="{{ $employee->last_name }}"
                                            data-contact="{{ $employee->contact }}"
                                            data-email="{{ $employee->email }}"
                                            data-province="{{ $employee->province }}"
                                            data-city="{{ $employee->city }}"
                                            data-region="{{ $employee->region }}"
                                            data-street-address="{{ $employee->street_address }}"
                                            data-role="{{ $employee->role }}"
                                            data-type="{{ $employee->employee_type ?? 'Regular' }}">
                                            <i data-lucide="pencil"></i>
                                        </button>
                                        <button class="action-btn view archive-emp-btn" type="button"
                                            title="{{ $employee->status === 'Inactive' ? 'Restore Employee' : 'Archive Employee' }}"
                                            data-id="{{ $employee->id }}"
                                            data-name="{{ $employee->full_name }}"
                                            data-archived="{{ $employee->status === 'Inactive' ? '1' : '0' }}">
                                            <i data-lucide="{{ $employee->status === 'Inactive' ? 'archive-restore' : 'archive' }}"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr id="empBladeEmpty">
                                    <td colspan="10" style="text-align:center;padding:40px;color:var(--muted);">
                                        No employees found. Click <strong>Add Employee</strong> to get started.
                                    </td>
                                </tr>
                                @endforelse
                                <tr id="empEmptyState" style="display:none;">
                                    <td colspan="9" style="text-align:center;padding:48px 20px;">
                                        <div style="display:flex;flex-direction:column;align-items:center;gap:10px;color:var(--muted);">
                                            <i data-lucide="inbox" style="width:36px;height:36px;opacity:0.4;"></i>
                                            <span id="empEmptyMsg" style="font-size:14px;font-weight:600;">No employees found.</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ===== TAB: SALARY ===== -->
            <div class="emp-tab-content" id="tab-salary">
                <div class="table-card">
                    <div class="table-toolbar">
                        <div class="search-box">
                            <i data-lucide="search"></i>
                            <input type="text" id="salarySearch" placeholder="Search employee...">
                        </div>
                        <div class="filter-group">
                            <select id="salaryMonthFilter" class="filter-select">
                                <!-- Populated by JS -->
                            </select>
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table class="data-table" id="salaryTable">
                            <thead>
                                <tr>
                                    <th>Employee Name</th>
                                    <th>Role</th>
                                    <th>Daily Rate</th>
                                    <th>Days Worked</th>
                                    <th>Overtime (hrs)</th>
                                    <th>Gross Pay</th>
                                    <th>Deductions</th>
                                    <th>Net Pay</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="salaryTableBody">
                                <tr id="salaryLoadingRow">
                                    <td colspan="10" style="text-align:center;padding:48px 20px;color:var(--muted);">
                                        <div style="display:flex;flex-direction:column;align-items:center;gap:10px;">
                                            <i data-lucide="loader" style="width:28px;height:28px;opacity:0.4;"></i>
                                            <span style="font-size:14px;font-weight:600;">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Payroll Summary -->
                    <div class="payroll-summary" id="payrollSummary">
                        <div class="payroll-summary-item">
                            <span>Total Gross</span>
                            <strong id="summaryGross">—</strong>
                        </div>
                        <div class="payroll-summary-item">
                            <span>Total Deductions</span>
                            <strong id="summaryDeductions">—</strong>
                        </div>
                        <div class="payroll-summary-item highlight">
                            <span>Total Net Pay</span>
                            <strong id="summaryNet">—</strong>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- ===== ADD EMPLOYEE MODAL ===== -->
    <div class="modal-overlay" id="addEmployeeModal">
        <div class="modal-card" style="max-width:560px;">
            <div class="modal-header">
                <div>
                    <h2>Add Employee</h2>
                    <p>An EGMD-XXXX username and 6-digit PIN will be auto-generated.</p>
                </div>
                <button class="modal-close" type="button" id="closeAddEmployeeModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.employee-account.store') }}" id="addEmpAccountForm">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required
                               placeholder="e.g. Kenneth"
                               value="{{ old('first_name') }}">
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required
                               placeholder="e.g. Nadera"
                               value="{{ old('last_name') }}">
                    </div>
                    <div class="form-group">
                        <label>Contact Number *</label>
                        <input type="text" name="contact" required
                               placeholder="e.g. 09XX XXX XXXX"
                               value="{{ old('contact') }}">
                    </div>
                    <div class="form-group">
                        <label>Email Address <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                        <input type="email" name="email"
                               placeholder="Optional"
                               value="{{ old('email') }}">
                    </div>
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" required>
                            <option value="">Select role</option>
                            <option value="Fabricator" {{ old('role') === 'Fabricator' ? 'selected' : '' }}>Fabricator</option>
                            <option value="Welder" {{ old('role') === 'Welder' ? 'selected' : '' }}>Welder</option>
                            <option value="Helper/Labor" {{ old('role') === 'Helper/Labor' ? 'selected' : '' }}>Helper/Labor</option>
                            <option value="Outsourced" {{ old('role') === 'Outsourced' ? 'selected' : '' }}>Outsourced</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Employee Type *</label>
                        <select name="employee_type" required>
                            <option value="Regular" {{ old('employee_type', 'Regular') === 'Regular' ? 'selected' : '' }}>Regular</option>
                            <option value="Outsourced" {{ old('employee_type') === 'Outsourced' ? 'selected' : '' }}>Outsourced</option>
                        </select>
                    </div>
                </div>

                <div class="form-section-label" style="margin-top:18px;">User Account Information</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Username</label>
                        <div style="display:flex;align-items:center;gap:8px;height:44px;background:var(--cream-soft);border:1px solid var(--border);border-radius:10px;padding:0 12px;">
                            <i data-lucide="user" style="width:15px;height:15px;color:var(--muted);flex-shrink:0;"></i>
                            <span style="font-size:13px;font-weight:700;color:var(--muted);font-family:monospace;">EGMD-XXXX</span>
                            <span style="font-size:11px;color:var(--muted-light);margin-left:auto;">Auto-generated</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>PIN / Password</label>
                        <div style="display:flex;align-items:center;gap:8px;height:44px;background:var(--cream-soft);border:1px solid var(--border);border-radius:10px;padding:0 12px;">
                            <i data-lucide="key-round" style="width:15px;height:15px;color:var(--muted);flex-shrink:0;"></i>
                            <span style="font-size:13px;font-weight:700;color:var(--muted);letter-spacing:4px;">••••••</span>
                            <span style="font-size:11px;color:var(--muted-light);margin-left:auto;">6-digit PIN</span>
                        </div>
                    </div>
                </div>
                <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:10px 14px;margin-top:8px;font-size:12.5px;color:#0369a1;display:flex;align-items:center;gap:6px;">
                    <i data-lucide="info" style="width:13px;height:13px;flex-shrink:0;"></i>
                    Credentials are auto-generated and shown once after saving. No email will be sent — share directly with the employee.
                </div>

                @if($errors->hasBag('employee_account') && $errors->getBag('employee_account')->any())
                <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px 14px;margin-top:12px;color:#991b1b;font-size:13px;display:flex;flex-direction:column;gap:4px;">
                    @foreach($errors->getBag('employee_account')->all() as $error)
                        <div style="display:flex;align-items:center;gap:6px;"><i data-lucide="alert-circle" style="width:13px;height:13px;flex-shrink:0;"></i> {{ $error }}</div>
                    @endforeach
                </div>
                @endif

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelAddEmployee">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="user-plus"></i>
                        Create Employee Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== EMPLOYEE CREDENTIALS SUCCESS MODAL ===== -->
    @if(session('new_emp_username'))
    <div class="modal-overlay show" id="empCredentialsModal">
        <div class="modal-card" style="max-width:480px;">
            <div class="modal-header">
                <div>
                    <h2>Employee Account Created</h2>
                    <p>{{ session('new_emp_name') }}</p>
                </div>
                <button class="modal-close" type="button" onclick="closeEmpModal('empCredentialsModal')">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div style="text-align:center;padding:8px 0 20px;">
                <div style="width:56px;height:56px;background:#ede9fe;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <i data-lucide="user-check" style="width:28px;height:28px;color:#6d28d9;"></i>
                </div>
                <p style="font-size:13.5px;color:var(--text-secondary);margin-bottom:20px;">
                    Account created. Share these credentials directly with the employee.
                </p>
            </div>

            <div style="background:#f8f9ff;border:1.5px solid #dde1f5;border-radius:10px;padding:20px 24px;margin-bottom:20px;">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #eee;">
                    <span style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;">Username</span>
                    <span style="font-size:20px;font-weight:900;color:var(--text-primary);letter-spacing:2px;" id="empCredUsername">
                        {{ session('new_emp_username') }}
                    </span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;">
                    <span style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;">PIN / Password</span>
                    <span style="font-size:20px;font-weight:900;color:var(--text-primary);letter-spacing:4px;" id="empCredPin">
                        {{ session('new_emp_pin') }}
                    </span>
                </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="copyEmpCredentials()" class="cancel-btn" style="display:flex;align-items:center;gap:6px;">
                    <i data-lucide="copy" style="width:14px;height:14px;"></i>
                    Copy All Credentials
                </button>
                <button type="button" onclick="closeEmpModal('empCredentialsModal')" class="save-btn">
                    Done
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- ===== EDIT EMPLOYEE MODAL ===== -->
    <div class="modal-overlay" id="editEmpModal">
        <div class="modal-card" style="max-width:560px;">
            <div class="modal-header">
                <div>
                    <h2>Edit Employee</h2>
                    <p id="editEmpSubtitle">Update employee information.</p>
                </div>
                <button class="modal-close" type="button" id="closeEditEmpModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" id="editEmpForm">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" id="editEmpFirstName" required placeholder="e.g. Kenneth">
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" id="editEmpLastName" required placeholder="e.g. Nadera">
                    </div>
                    <div class="form-group">
                        <label>Contact Number *</label>
                        <input type="text" name="contact" id="editEmpContact" required placeholder="Contact number">
                    </div>
                    <div class="form-group">
                        <label>Email Address <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                        <input type="email" name="email" id="editEmpEmail" placeholder="Email (optional)">
                    </div>
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" id="editEmpRole" required>
                            <option value="Fabricator">Fabricator</option>
                            <option value="Welder">Welder</option>
                            <option value="Helper/Labor">Helper/Labor</option>
                            <option value="Outsourced">Outsourced</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Employee Type *</label>
                        <select name="employee_type" id="editEmpType" required>
                            <option value="Regular">Regular</option>
                            <option value="Outsourced">Outsourced</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Province *</label>
                        <input type="text" name="province" id="editEmpProvince" required placeholder="e.g. Laguna">
                    </div>
                    <div class="form-group">
                        <label>City / Municipality *</label>
                        <input type="text" name="city" id="editEmpCity" required placeholder="e.g. Santa Cruz">
                    </div>
                    <div class="form-group">
                        <label>Region *</label>
                        <input type="text" name="region" id="editEmpRegion" required placeholder="e.g. Region IV-A">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Street Address *</label>
                        <input type="text" name="street_address" id="editEmpStreetAddress" required placeholder="e.g. Poblacion Street">
                    </div>
                </div>

                @if($errors->hasBag('employee_edit') && $errors->getBag('employee_edit')->any())
                <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px;margin-top:4px;color:#dc2626;font-size:13px;">
                    @foreach($errors->getBag('employee_edit')->all() as $error)
                        <div>• {{ $error }}</div>
                    @endforeach
                </div>
                @endif

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelEditEmp">Cancel</button>
                    <button type="submit" class="save-btn"><i data-lucide="save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== ARCHIVE EMPLOYEE MODAL ===== -->
    <div class="modal-overlay" id="archiveEmpModal">
        <div class="modal-card" style="max-width:420px;">
            <div class="modal-header">
                <div>
                    <h2 id="archiveEmpTitle">Archive Employee</h2>
                    <p>This will affect the employee's system access.</p>
                </div>
                <button class="modal-close" type="button" id="closeArchiveEmpModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="delete-confirm-body">
                <div class="delete-confirm-icon"><i data-lucide="archive"></i></div>
                <p id="archiveEmpMsg">Are you sure you want to archive this employee?</p>
            </div>
            <form method="POST" id="archiveEmpForm">
                @csrf
                @method('PATCH')
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelArchiveEmp">Cancel</button>
                    <button type="submit" class="save-btn" id="archiveEmpConfirmBtn">
                        <i data-lucide="archive"></i> Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== SALARY DETAIL MODAL ===== -->
    <div class="modal-overlay" id="salaryDetailModal">
        <div class="modal-card" style="max-width: 520px;">
            <div class="modal-header">
                <div>
                    <h2>Salary Details</h2>
                    <p id="salaryDetailName"></p>
                </div>
                <button class="modal-close" type="button" id="closeSalaryDetailModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div id="salaryDetailBody"></div>
            <div class="modal-actions" style="margin-top: 16px;">
                <button type="button" class="cancel-btn" id="cancelSalaryDetail">Close</button>
                <button type="button" class="save-btn" onclick="document.getElementById('salaryDetailModal').classList.remove('show')">
                    <i data-lucide="printer"></i>
                    Print Slip
                </button>
            </div>
        </div>
    </div>

    <!-- ===== RECORD SALARY MODAL ===== -->
    <div class="modal-overlay" id="recordPaymentModal">
        <div class="modal-card" style="max-width:560px;">
            <div class="modal-header">
                <div>
                    <h2 id="recordPaymentTitle">Record Salary</h2>
                    <p id="recordPaymentSubtitle">Log a salary entry for the selected pay period.</p>
                </div>
                <button class="modal-close" type="button" id="closeRecordPaymentModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form id="recordPaymentForm">
                <input type="hidden" id="rpRecordId">
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Employee *</label>
                        <select id="rpEmployee" required>
                            <option value="">Select employee</option>
                            @foreach($employees->where('status', 'Active') as $emp)
                            <option value="{{ $emp->id }}"
                                    data-rate="{{ $emp->daily_rate ?? 0 }}"
                                    data-sss="{{ $emp->sss ?? 0 }}"
                                    data-philhealth="{{ $emp->philhealth ?? 0 }}"
                                    data-pagibig="{{ $emp->pagibig ?? 0 }}"
                                    data-other="{{ $emp->other_deductions ?? 0 }}">
                                {{ $emp->full_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pay Period</label>
                        <input type="hidden" id="rpPeriod">
                        <div id="rpPeriodDisplay" style="padding:10px 12px;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;font-size:13px;font-weight:600;color:var(--text-primary);"></div>
                    </div>
                    <div class="form-group">
                        <label>Payment Status</label>
                        <select id="rpStatus">
                            <option value="Pending">Pending</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Days Worked * <span style="font-weight:400;color:var(--muted);">(max 5)</span></label>
                        <input type="number" id="rpDays" required placeholder="e.g. 5" min="0" max="5" step="0.5">
                    </div>
                    <div class="form-group">
                        <label>Overtime Hours</label>
                        <input type="number" id="rpOT" placeholder="e.g. 2" min="0" step="0.5" value="0">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Extra Deductions (₱) <span style="font-weight:400;color:var(--muted);">— one-time, e.g. cash advance</span></label>
                        <input type="number" id="rpExtraDed" placeholder="0" min="0" step="0.01" value="0">
                    </div>
                    <div class="form-group form-group-full">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600;">
                            <input type="checkbox" id="rpApplyDeductions" style="width:16px;height:16px;cursor:pointer;">
                            Apply monthly government deductions this week
                            <span style="font-weight:400;font-size:12px;color:var(--muted);">(SSS, PhilHealth, Pag-IBIG)</span>
                        </label>
                    </div>
                </div>

                <!-- Live pay preview -->
                <div id="rpPreview" style="background:var(--surface-2);border:1px solid var(--border);border-radius:10px;padding:14px 18px;margin-top:4px;display:none;">
                    <div style="font-size:11px;font-weight:800;color:var(--primary);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Pay Preview</div>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;font-size:13px;">
                        <div>
                            <div style="color:var(--muted);font-size:11px;font-weight:700;margin-bottom:2px;">Daily Rate</div>
                            <div style="font-weight:700;" id="previewRate">₱0.00</div>
                        </div>
                        <div>
                            <div style="color:var(--muted);font-size:11px;font-weight:700;margin-bottom:2px;">Gross Pay</div>
                            <div style="font-weight:700;color:#16a34a;" id="previewGross">₱0.00</div>
                        </div>
                        <div>
                            <div style="color:var(--muted);font-size:11px;font-weight:700;margin-bottom:2px;">Deductions</div>
                            <div style="font-weight:700;color:#dc2626;" id="previewDeductions">₱0.00</div>
                        </div>
                        <div style="grid-column:1/-1;border-top:1px solid var(--border);padding-top:10px;">
                            <div style="color:var(--muted);font-size:11px;font-weight:700;margin-bottom:2px;">Net Pay</div>
                            <div style="font-size:18px;font-weight:900;color:var(--dark);" id="previewNet">₱0.00</div>
                        </div>
                    </div>
                </div>

                <div id="rpError" style="display:none;background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;margin-top:10px;color:#991b1b;font-size:13px;"></div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelRecordPayment">Cancel</button>
                    <button type="submit" class="save-btn" id="recordPaymentSubmitBtn">
                        <i data-lucide="save"></i>
                        Save Record
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== TOAST ===== -->
    <div class="toast" id="empToast">
        <i data-lucide="check-circle"></i>
        <span id="empToastMsg">Copied!</span>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
    (function () {
        // ---- Tab restoration from session ----
        var activeTab = "{{ session('active_tab', 'employees') }}";

        document.addEventListener('DOMContentLoaded', function () {
            // Restore active tab
            document.querySelectorAll('.emp-tab').forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-tab') === activeTab);
            });
            document.querySelectorAll('.emp-tab-content').forEach(function (pane) {
                pane.classList.toggle('active', pane.id === 'tab-' + activeTab);
            });
            // Show/hide header buttons based on active tab
            var addEmpBtn    = document.getElementById('openAddEmployeeModal');
            var recSalaryBtn = document.getElementById('openRecordPaymentModal');
            if (addEmpBtn)    addEmpBtn.style.display    = (activeTab === 'salary')    ? 'none' : '';
            if (recSalaryBtn) recSalaryBtn.style.display = (activeTab === 'employees') ? 'none' : '';

            // Re-open modal on validation error
            @if($errors->hasBag('employee_account'))
            openEmpModal('addEmployeeModal');
            @endif
            @if($errors->hasBag('employee_edit'))
            openEmpModal('editEmpModal');
            @endif

            // ---- Add Employee Modal ----
            var openAddBtn = document.getElementById('openAddEmployeeModal');
            if (openAddBtn) openAddBtn.addEventListener('click', function () { openEmpModal('addEmployeeModal'); if (typeof lucide !== 'undefined') lucide.createIcons(); });
            var closeAddBtn = document.getElementById('closeAddEmployeeModal');
            if (closeAddBtn) closeAddBtn.addEventListener('click', function () { closeEmpModal('addEmployeeModal'); document.getElementById('addEmpAccountForm').reset(); });
            var cancelAddBtn = document.getElementById('cancelAddEmployee');
            if (cancelAddBtn) cancelAddBtn.addEventListener('click', function () { closeEmpModal('addEmployeeModal'); document.getElementById('addEmpAccountForm').reset(); });

            // ---- Edit Employee Modal ----
            document.querySelectorAll('.edit-emp-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.getElementById('editEmpFirstName').value     = this.dataset.firstName || '';
                    document.getElementById('editEmpLastName').value      = this.dataset.lastName || '';
                    document.getElementById('editEmpContact').value       = this.dataset.contact || '';
                    document.getElementById('editEmpEmail').value         = this.dataset.email || '';
                    document.getElementById('editEmpProvince').value      = this.dataset.province || '';
                    document.getElementById('editEmpCity').value          = this.dataset.city || '';
                    document.getElementById('editEmpRegion').value        = this.dataset.region || '';
                    document.getElementById('editEmpStreetAddress').value = this.dataset.streetAddress || '';
                    document.getElementById('editEmpRole').value          = this.dataset.role;
                    document.getElementById('editEmpType').value          = this.dataset.type;
                    document.getElementById('editEmpSubtitle').textContent = 'Editing: ' + this.dataset.lastName + ', ' + this.dataset.firstName;
                    document.getElementById('editEmpForm').action = '/admin/employees/' + this.dataset.id;
                    openEmpModal('editEmpModal');
                });
            });
            document.getElementById('closeEditEmpModal').addEventListener('click', function () { closeEmpModal('editEmpModal'); });
            document.getElementById('cancelEditEmp').addEventListener('click', function () { closeEmpModal('editEmpModal'); });

            // ---- Archive Employee Modal ----
            document.querySelectorAll('.archive-emp-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var isArchived = this.dataset.archived === '1';
                    var name       = this.dataset.name;
                    document.getElementById('archiveEmpTitle').textContent = isArchived ? 'Restore Employee' : 'Archive Employee';
                    document.getElementById('archiveEmpMsg').textContent   = isArchived
                        ? 'Restore "' + name + '"? Their system access will be re-enabled.'
                        : 'Archive "' + name + '"? Their login access will be disabled.';
                    var confirmBtn = document.getElementById('archiveEmpConfirmBtn');
                    confirmBtn.innerHTML = isArchived
                        ? '<i data-lucide="archive-restore"></i> Restore'
                        : '<i data-lucide="archive"></i> Archive';
                    document.getElementById('archiveEmpForm').action = '/admin/employees/' + this.dataset.id + '/archive';
                    openEmpModal('archiveEmpModal');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            });
            document.getElementById('closeArchiveEmpModal').addEventListener('click', function () { closeEmpModal('archiveEmpModal'); });
            document.getElementById('cancelArchiveEmp').addEventListener('click', function () { closeEmpModal('archiveEmpModal'); });

            // ---- Overlay click to close ----
            document.querySelectorAll('.modal-overlay').forEach(function (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === this) closeEmpModal(this.id);
                });
            });

            // ---- Employee List Filtering & Sorting ----
            function filterEmployees() {
                var q      = document.getElementById('employeeSearch').value.toLowerCase();
                var type   = document.getElementById('empTypeFilter').value;
                var status = document.getElementById('empStatusFilter').value;
                var visible = 0;
                document.querySelectorAll('#employeesTable tbody tr[data-name]').forEach(function (row) {
                    var matchQ      = !q || row.dataset.name.toLowerCase().includes(q) || row.dataset.username.includes(q);
                    var matchType   = !type || row.dataset.type === type;
                    var matchStatus = !status || row.dataset.status === status;
                    var show = matchQ && matchType && matchStatus;
                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
                var bladeEmpty = document.getElementById('empBladeEmpty');
                if (bladeEmpty) bladeEmpty.style.display = (q || type || status) ? 'none' : '';

                var emptyRow = document.getElementById('empEmptyState');
                var emptyMsg = document.getElementById('empEmptyMsg');
                if (emptyRow) {
                    if (!visible && (q || type || status)) {
                        if (emptyMsg) {
                            var typeLabel   = type === 'Regular' ? 'regular' : type === 'Outsourced' ? 'outsourced' : '';
                            var statusLabel = status === 'Inactive' ? 'archived' : status === 'Active' ? 'active' : '';
                            var parts = ['No'];
                            if (statusLabel) parts.push(statusLabel);
                            if (typeLabel)   parts.push(typeLabel);
                            parts.push('employees');
                            if (!statusLabel && !typeLabel) parts.push('match your search');
                            emptyMsg.textContent = parts.join(' ') + '.';
                        }
                        emptyRow.style.display = '';
                        if (window.lucide) lucide.createIcons();
                    } else {
                        emptyRow.style.display = 'none';
                    }
                }
            }

            function sortEmployees() {
                var sort  = document.getElementById('empSortFilter').value;
                var tbody = document.querySelector('#employeesTable tbody');
                var rows  = Array.from(tbody.querySelectorAll('tr[data-name]'));
                rows.sort(function (a, b) {
                    if (sort === 'date-desc') return Number(b.dataset.created) - Number(a.dataset.created);
                    if (sort === 'date-asc')  return Number(a.dataset.created) - Number(b.dataset.created);
                    if (sort === 'name-asc')  return a.dataset.name.localeCompare(b.dataset.name);
                    if (sort === 'name-desc') return b.dataset.name.localeCompare(a.dataset.name);
                    return 0;
                });
                rows.forEach(function (row) { tbody.appendChild(row); });
            }

            document.getElementById('employeeSearch').addEventListener('keyup', filterEmployees);
            document.getElementById('empTypeFilter').addEventListener('change', filterEmployees);
            document.getElementById('empStatusFilter').addEventListener('change', filterEmployees);
            document.getElementById('empSortFilter').addEventListener('change', function () {
                sortEmployees();
                filterEmployees();
            });
            // Apply default sort then filter (Active only) on load
            sortEmployees();
            filterEmployees();

            // ---- Record Salary button (header) ----
            var openRecSalBtn = document.getElementById('openRecordPaymentModal');
            if (openRecSalBtn) openRecSalBtn.addEventListener('click', function () { openSalaryModal(); });

            // ---- Salary Tab: populate period filter & load on tab switch ----
            populatePeriodFilter();

            document.querySelectorAll('.emp-tab').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var tab = btn.getAttribute('data-tab');
                    document.querySelectorAll('.emp-tab').forEach(function (b) { b.classList.remove('active'); });
                    document.querySelectorAll('.emp-tab-content').forEach(function (p) { p.classList.remove('active'); });
                    btn.classList.add('active');
                    document.getElementById('tab-' + tab).classList.add('active');

                    var addEmpBtn    = document.getElementById('openAddEmployeeModal');
                    var recSalaryBtn = document.getElementById('openRecordPaymentModal');
                    if (addEmpBtn)    addEmpBtn.style.display    = (tab === 'salary')    ? 'none' : '';
                    if (recSalaryBtn) recSalaryBtn.style.display = (tab === 'employees') ? 'none' : '';

                    if (tab === 'salary') loadSalaryRecords();
                });
            });

            document.getElementById('salaryMonthFilter').addEventListener('change', loadSalaryRecords);
            document.getElementById('salarySearch').addEventListener('keyup', filterSalaryTable);
        });
    })();

    // =================== SALARY MODULE ===================

    var SALARY_INDEX_URL  = '{{ route("admin.salary.index") }}';
    var SALARY_STORE_URL  = '{{ route("admin.salary.store") }}';
    var CSRF              = '{{ csrf_token() }}';

    // Returns the Monday of the week containing `date`
    function weekMonday(date) {
        var d   = new Date(date);
        var day = d.getDay(); // 0=Sun
        var diff = (day === 0) ? -6 : 1 - day;
        d.setDate(d.getDate() + diff);
        d.setHours(0, 0, 0, 0);
        return d;
    }

    function isoDate(d) {
        return d.getFullYear() + '-'
            + String(d.getMonth() + 1).padStart(2, '0') + '-'
            + String(d.getDate()).padStart(2, '0');
    }

    function weekLabel(monday) {
        var friday = new Date(monday);
        friday.setDate(monday.getDate() + 4);
        var opts = { month: 'short', day: 'numeric' };
        return monday.toLocaleDateString('en-US', opts)
             + ' – '
             + friday.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function populatePeriodFilter() {
        var sel    = document.getElementById('salaryMonthFilter');
        var monday = weekMonday(new Date());
        for (var i = 0; i < 16; i++) {
            var wk  = new Date(monday);
            wk.setDate(monday.getDate() - i * 7);
            var opt = document.createElement('option');
            opt.value       = isoDate(wk);
            opt.textContent = weekLabel(wk);
            sel.appendChild(opt);
        }
    }

    function loadSalaryRecords() {
        var period = document.getElementById('salaryMonthFilter').value;
        var tbody  = document.getElementById('salaryTableBody');
        tbody.innerHTML = '<tr id="salaryLoadingRow"><td colspan="10" style="text-align:center;padding:48px 20px;color:var(--muted);"><div style="display:flex;flex-direction:column;align-items:center;gap:10px;"><i data-lucide="loader" style="width:28px;height:28px;opacity:0.4;"></i><span style="font-size:14px;font-weight:600;">Loading...</span></div></td></tr>';
        if (window.lucide) lucide.createIcons();

        fetch(SALARY_INDEX_URL + '?period=' + period, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            renderSalaryTable(data.records, data.summary);
        })
        .catch(function () {
            tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:var(--muted);">Failed to load records.</td></tr>';
        });
    }

    function renderSalaryTable(records, summary) {
        var tbody = document.getElementById('salaryTableBody');
        if (!records || records.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:48px 20px;"><div style="display:flex;flex-direction:column;align-items:center;gap:10px;color:var(--muted);"><i data-lucide="inbox" style="width:36px;height:36px;opacity:0.4;"></i><span style="font-size:14px;font-weight:600;">No salary records for this period.</span></div></td></tr>';
            if (window.lucide) lucide.createIcons();
            document.getElementById('summaryGross').textContent       = '₱0.00';
            document.getElementById('summaryDeductions').textContent  = '₱0.00';
            document.getElementById('summaryNet').textContent         = '₱0.00';
            return;
        }

        tbody.innerHTML = records.map(function (r) {
            var statusBadge = r.status === 'Paid'
                ? '<span class="status-badge active">Paid</span>'
                : '<span class="status-badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;">Pending</span>';
            return '<tr data-id="' + r.id + '" data-name="' + r.employee_name.toLowerCase() + '">'
                + '<td style="font-weight:600;">' + escHtml(r.employee_name) + '</td>'
                + '<td>' + escHtml(r.role) + '</td>'
                + '<td>₱' + fmt(r.daily_rate) + '</td>'
                + '<td>' + r.days_worked + '</td>'
                + '<td>' + r.overtime_hours + '</td>'
                + '<td style="font-weight:700;color:#16a34a;">₱' + fmt(r.gross_pay) + '</td>'
                + '<td style="color:#dc2626;">₱' + fmt(r.total_deductions) + '</td>'
                + '<td style="font-weight:900;">₱' + fmt(r.net_pay) + '</td>'
                + '<td>' + statusBadge + '</td>'
                + '<td class="action-cell">'
                +   '<button class="action-btn view" title="Edit" onclick="openSalaryModal(' + r.id + ')"><i data-lucide="pencil"></i></button>'
                +   '<button class="action-btn view" title="' + (r.status === 'Paid' ? 'Mark Pending' : 'Mark Paid') + '" onclick="toggleSalaryStatus(' + r.id + ', this)">'
                +     '<i data-lucide="' + (r.status === 'Paid' ? 'x-circle' : 'check-circle') + '"></i>'
                +   '</button>'
                +   '<button class="action-btn" title="Delete" style="color:#dc2626;" onclick="deleteSalaryRecord(' + r.id + ')"><i data-lucide="trash-2"></i></button>'
                + '</td>'
                + '</tr>';
        }).join('');

        if (window.lucide) lucide.createIcons();

        document.getElementById('summaryGross').textContent      = '₱' + fmt(summary.gross);
        document.getElementById('summaryDeductions').textContent = '₱' + fmt(summary.deductions);
        document.getElementById('summaryNet').textContent        = '₱' + fmt(summary.net);

        filterSalaryTable();
    }

    function filterSalaryTable() {
        var q = document.getElementById('salarySearch').value.toLowerCase();
        document.querySelectorAll('#salaryTableBody tr[data-name]').forEach(function (row) {
            row.style.display = (!q || row.dataset.name.includes(q)) ? '' : 'none';
        });
    }

    // Open modal for new record or edit
    function openSalaryModal(recordId) {
        var isEdit  = !!recordId;
        var period  = document.getElementById('salaryMonthFilter').value;

        document.getElementById('recordPaymentTitle').textContent    = isEdit ? 'Edit Salary Record' : 'Record Salary';
        document.getElementById('recordPaymentSubtitle').textContent = isEdit ? 'Update the salary entry.' : 'Log a salary entry for the selected pay period.';
        document.getElementById('rpRecordId').value                  = recordId || '';
        document.getElementById('rpError').style.display             = 'none';
        document.getElementById('rpPreview').style.display           = 'none';

        // Always set the period from the filter (hidden field + display label)
        document.getElementById('rpPeriod').value = period;
        var monday = new Date(period + 'T00:00:00');
        document.getElementById('rpPeriodDisplay').textContent = weekLabel(monday);

        if (!isEdit) {
            document.getElementById('rpEmployee').value          = '';
            document.getElementById('rpDays').value              = '';
            document.getElementById('rpOT').value                = '0';
            document.getElementById('rpExtraDed').value          = '0';
            document.getElementById('rpStatus').value            = 'Pending';
            document.getElementById('rpApplyDeductions').checked = false;
            document.getElementById('rpPreview').style.display   = 'none';
        } else {
            fetch(SALARY_INDEX_URL + '?period=' + period, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var rec = data.records.find(function (r) { return r.id == recordId; });
                if (!rec) return;
                document.getElementById('rpEmployee').value           = rec.employee_id;
                document.getElementById('rpDays').value               = rec.days_worked;
                document.getElementById('rpOT').value                 = rec.overtime_hours;
                document.getElementById('rpExtraDed').value           = rec.extra_deductions;
                document.getElementById('rpStatus').value             = rec.status;
                document.getElementById('rpApplyDeductions').checked  = rec.apply_deductions;
                updatePayPreview();
            });
        }

        openEmpModal('recordPaymentModal');
    }

    function updatePayPreview() {
        var sel  = document.getElementById('rpEmployee');
        var opt  = sel.options[sel.selectedIndex];
        if (!opt || !opt.value) { document.getElementById('rpPreview').style.display = 'none'; return; }

        var rate           = parseFloat(opt.dataset.rate)        || 0;
        var sss            = parseFloat(opt.dataset.sss)         || 0;
        var phi            = parseFloat(opt.dataset.philhealth)  || 0;
        var pag            = parseFloat(opt.dataset.pagibig)     || 0;
        var other          = parseFloat(opt.dataset.other)       || 0;
        var days           = parseFloat(document.getElementById('rpDays').value)          || 0;
        var ot             = parseFloat(document.getElementById('rpOT').value)            || 0;
        var extra          = parseFloat(document.getElementById('rpExtraDed').value)      || 0;
        var applyGovDed    = document.getElementById('rpApplyDeductions').checked;

        var gross      = (rate * days) + (ot * (rate / 8));
        var govDed     = applyGovDed ? (sss + phi + pag + other) : 0;
        var deductions = govDed + extra;
        var net        = gross - deductions;

        document.getElementById('previewRate').textContent       = '₱' + fmt(rate);
        document.getElementById('previewGross').textContent      = '₱' + fmt(gross);
        document.getElementById('previewDeductions').textContent = '₱' + fmt(deductions);
        document.getElementById('previewNet').textContent        = '₱' + fmt(net);
        document.getElementById('rpPreview').style.display       = 'block';
    }

    document.addEventListener('DOMContentLoaded', function () {
        ['rpDays', 'rpOT', 'rpExtraDed'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('input', updatePayPreview);
        });
        document.getElementById('rpEmployee').addEventListener('change', updatePayPreview);
        document.getElementById('rpApplyDeductions').addEventListener('change', updatePayPreview);

        document.getElementById('closeRecordPaymentModal').addEventListener('click', function () { closeEmpModal('recordPaymentModal'); });
        document.getElementById('cancelRecordPayment').addEventListener('click',     function () { closeEmpModal('recordPaymentModal'); });

        document.getElementById('recordPaymentForm').addEventListener('submit', function (e) {
            e.preventDefault();
            var errEl   = document.getElementById('rpError');
            var submitBtn = document.getElementById('recordPaymentSubmitBtn');
            var recordId  = document.getElementById('rpRecordId').value;
            var isEdit    = !!recordId;

            errEl.style.display = 'none';
            submitBtn.disabled  = true;

            var payload = {
                employee_id:      document.getElementById('rpEmployee').value,
                pay_period:       document.getElementById('rpPeriod').value,
                days_worked:      document.getElementById('rpDays').value,
                overtime_hours:   document.getElementById('rpOT').value,
                extra_deductions: document.getElementById('rpExtraDed').value,
                apply_deductions: document.getElementById('rpApplyDeductions').checked ? 1 : 0,
                status:           document.getElementById('rpStatus').value,
            };

            var url    = isEdit ? '/admin/salary-records/' + recordId : SALARY_STORE_URL;
            var method = isEdit ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                submitBtn.disabled = false;
                if (data.errors) {
                    var msgs = Object.values(data.errors).flat().join(' ');
                    errEl.textContent    = msgs;
                    errEl.style.display  = 'block';
                    return;
                }
                closeEmpModal('recordPaymentModal');
                loadSalaryRecords();
                showEmpToast(isEdit ? 'Salary record updated.' : 'Salary record saved.');
            })
            .catch(function () {
                submitBtn.disabled   = false;
                errEl.textContent    = 'Something went wrong. Please try again.';
                errEl.style.display  = 'block';
            });
        });
    });

    function toggleSalaryStatus(id, btn) {
        fetch('/admin/salary-records/' + id + '/status', {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            loadSalaryRecords();
            showEmpToast('Marked as ' + data.status + '.');
        });
    }

    function deleteSalaryRecord(id) {
        if (!confirm('Delete this salary record? This cannot be undone.')) return;
        fetch('/admin/salary-records/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(function () {
            loadSalaryRecords();
            showEmpToast('Record deleted.');
        });
    }

    function fmt(n) { return Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function escHtml(s) { return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    // =================== END SALARY MODULE ===================

    function openEmpModal(id) {
        var m = document.getElementById(id);
        if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
    }
    function closeEmpModal(id) {
        var m = document.getElementById(id);
        if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
    }

    function copyEmpCredentials() {
        var username = document.getElementById('empCredUsername').textContent.trim();
        var pin      = document.getElementById('empCredPin').textContent.trim();
        var text     = 'Username: ' + username + '\nPIN: ' + pin;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function () { showEmpToast('Credentials copied!'); });
        } else {
            var ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            showEmpToast('Credentials copied!');
        }
    }

    function showEmpToast(msg) {
        var toast = document.getElementById('empToast');
        var label = document.getElementById('empToastMsg');
        if (!toast) return;
        if (label) label.textContent = msg;
        toast.classList.add('show');
        setTimeout(function () { toast.classList.remove('show'); }, 3000);
    }
    </script>
</body>
</html>
