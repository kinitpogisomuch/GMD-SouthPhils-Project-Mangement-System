<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

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
                        <i data-lucide="user-plus"></i>
                        Add Outsourced Worker
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
                                    <th>Daily Rate</th>
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
                                        <span style="font-family:monospace;font-weight:600;color:var(--text-secondary);font-size:12px;">
                                            {{ $employee->username ?? '—' }}
                                        </span>
                                    </td>
                                    <td>{{ $employee->contact ?? '—' }}</td>
                                    <td>{{ $employee->email ?? '—' }}</td>
                                    <td style="font-size:12px;color:var(--text-secondary);max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
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
                                    <td>₱{{ number_format($employee->daily_rate ?? 0, 2) }}</td>
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
                                            data-type="{{ $employee->employee_type ?? 'Regular' }}"
                                            data-rate="{{ $employee->daily_rate ?? 0 }}">
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
                                    <td colspan="11" style="text-align:center;padding:40px;color:var(--muted);">
                                        No employees found. Click <strong>Add Employee</strong> to get started.
                                    </td>
                                </tr>
                                @endforelse
                                <tr id="empEmptyState" style="display:none;">
                                    <td colspan="10" style="text-align:center;padding:48px 20px;">
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
                        <div class="pay-period-nav" style="position:relative;">
                            <button type="button" class="pay-period-btn" id="salaryPrevWeek" title="Previous week">
                                <i data-lucide="chevron-left"></i>
                            </button>
                            <div class="pay-period-label">
                                <i data-lucide="calendar"></i>
                                <span id="salaryPeriodLabel">Week of —</span>
                            </div>
                            <button type="button" class="pay-period-btn" id="salaryNextWeek" title="Next week">
                                <i data-lucide="chevron-right"></i>
                            </button>
                            <button type="button" class="pay-period-btn" id="salaryJumpToDate" title="Jump to a specific week">
                                <i data-lucide="calendar-days"></i>
                            </button>
                            <div class="pay-period-jump-panel" id="salaryJumpPanel">
                                <div class="pay-period-jump-row">
                                    <label for="salaryJumpYear">Year</label>
                                    <select id="salaryJumpYear" class="filter-select"></select>
                                </div>
                                <div class="pay-period-jump-row">
                                    <label for="salaryJumpMonth">Month</label>
                                    <select id="salaryJumpMonth" class="filter-select"></select>
                                </div>
                                <div class="pay-period-jump-row">
                                    <label for="salaryJumpWeek">Week</label>
                                    <select id="salaryJumpWeek" class="filter-select"></select>
                                </div>
                                <button type="button" class="save-btn" id="salaryJumpGo">Go</button>
                            </div>
                            <button type="button" class="filter-select" id="salaryThisWeek" style="display:none;">
                                This Week
                            </button>
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
                                    <th>OT Hours</th>
                                    <th>OT Pay</th>
                                    <th>Gross Pay</th>
                                    <th>Net Pay</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="salaryTableBody">
                                <tr id="salaryLoadingRow">
                                    <td colspan="9" style="text-align:center;padding:48px 20px;color:var(--muted);">
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
                        <label>First Name </label>
                        <input type="text" name="first_name" required minlength="2" maxlength="50"
                               placeholder="e.g. Kenneth"
                               value="{{ old('first_name') }}"
                               oninput="empCapName(this); empFieldError(this,'addEmpFirstNameErr')">
                        <span class="emp-field-err" id="addEmpFirstNameErr"></span>
                    </div>
                    <div class="form-group">
                        <label>Last Name </label>
                        <input type="text" name="last_name" required minlength="2" maxlength="50"
                               placeholder="e.g. Nadera"
                               value="{{ old('last_name') }}"
                               oninput="empCapName(this); empFieldError(this,'addEmpLastNameErr')">
                        <span class="emp-field-err" id="addEmpLastNameErr"></span>
                    </div>
                    <div class="form-group">
                        <label>Contact Number </label>
                        <input type="text" name="contact" required maxlength="13"
                               placeholder="e.g. 0930-147-6598"
                               value="{{ old('contact') }}"
                               oninput="formatEmpContact(this);empFieldError(this,'addEmpContactErr')"
                               onkeypress="return /[0-9\-]/.test(event.key)">
                        <span class="emp-field-err" id="addEmpContactErr"></span>
                    </div>
                    <div class="form-group">
                        <label>Email Address </label>
                        <input type="email" name="email" required maxlength="255"
                               placeholder="e.g. juan@gmail.com"
                               value="{{ old('email') }}"
                               oninput="empFieldError(this,'addEmpEmailErr')">
                        <span class="emp-field-err" id="addEmpEmailErr"></span>
                    </div>
                    <div class="form-group">
                        <label>Role </label>
                        <select name="role" required onchange="empFieldError(this,'addEmpRoleErr')">
                            <option value="">Select role</option>
                            <option value="Fabricator" {{ old('role') === 'Fabricator' ? 'selected' : '' }}>Fabricator</option>
                            <option value="Welder" {{ old('role') === 'Welder' ? 'selected' : '' }}>Welder</option>
                            <option value="Helper/Labor" {{ old('role') === 'Helper/Labor' ? 'selected' : '' }}>Helper/Labor</option>
                            <option value="Outsourced" {{ old('role') === 'Outsourced' ? 'selected' : '' }}>Outsourced</option>
                        </select>
                        <span class="emp-field-err" id="addEmpRoleErr"></span>
                    </div>
                    <div class="form-group">
                        <label>Employee Type </label>
                        <select name="employee_type" required onchange="empFieldError(this,'addEmpTypeErr')">
                            <option value="Regular" {{ old('employee_type', 'Regular') === 'Regular' ? 'selected' : '' }}>Regular</option>
                            <option value="Outsourced" {{ old('employee_type') === 'Outsourced' ? 'selected' : '' }}>Outsourced</option>
                        </select>
                        <span class="emp-field-err" id="addEmpTypeErr"></span>
                    </div>
                    <div class="form-group">
                        <label>Daily Rate (₱) </label>
                        <input type="number" name="daily_rate" required min="1" step="0.01"
                               placeholder="e.g. 500"
                               value="{{ old('daily_rate') }}"
                               oninput="empFieldError(this,'addEmpRateErr')">
                        <span class="emp-field-err" id="addEmpRateErr"></span>
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
                        <label>First Name </label>
                        <input type="text" name="first_name" id="editEmpFirstName" required maxlength="100"
                               placeholder="e.g. Kenneth"
                               oninput="empCapName(this); empFieldError(this,'editEmpFirstNameErr')">
                        <span class="emp-field-err" id="editEmpFirstNameErr"></span>
                    </div>
                    <div class="form-group">
                        <label>Last Name </label>
                        <input type="text" name="last_name" id="editEmpLastName" required maxlength="100"
                               placeholder="e.g. Nadera"
                               oninput="empCapName(this); empFieldError(this,'editEmpLastNameErr')">
                        <span class="emp-field-err" id="editEmpLastNameErr"></span>
                    </div>
                    <div class="form-group">
                        <label>Contact Number </label>
                        <input type="text" name="contact" id="editEmpContact" required maxlength="13"
                               placeholder="e.g. 0930-147-6598"
                               oninput="formatEmpContact(this);empFieldError(this,'editEmpContactErr')"
                               onkeypress="return /[0-9\-]/.test(event.key)">
                        <span class="emp-field-err" id="editEmpContactErr"></span>
                    </div>
                    <div class="form-group">
                        <label>Email Address <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                        <input type="email" name="email" id="editEmpEmail"
                               placeholder="Email (optional)"
                               oninput="empFieldError(this,'editEmpEmailErr')">
                        <span class="emp-field-err" id="editEmpEmailErr"></span>
                    </div>
                    <div class="form-group">
                        <label>Role </label>
                        <select name="role" id="editEmpRole" required
                                onchange="empFieldError(this,'editEmpRoleErr')">
                            <option value="Fabricator">Fabricator</option>
                            <option value="Welder">Welder</option>
                            <option value="Helper/Labor">Helper/Labor</option>
                            <option value="Outsourced">Outsourced</option>
                        </select>
                        <span class="emp-field-err" id="editEmpRoleErr"></span>
                    </div>
                    <div class="form-group">
                        <label>Employee Type </label>
                        <select name="employee_type" id="editEmpType" required
                                onchange="empFieldError(this,'editEmpTypeErr')">
                            <option value="Regular">Regular</option>
                            <option value="Outsourced">Outsourced</option>
                        </select>
                        <span class="emp-field-err" id="editEmpTypeErr"></span>
                    </div>
                    <div class="form-group">
                        <label>Daily Rate (₱) </label>
                        <input type="number" name="daily_rate" id="editEmpDailyRate" required min="1" step="0.01"
                               placeholder="e.g. 500"
                               oninput="empFieldError(this,'editEmpRateErr')">
                        <span class="emp-field-err" id="editEmpRateErr"></span>
                    </div>
                    <div class="form-group">
                        <label>Province </label>
                        <input type="text" name="province" id="editEmpProvince" required maxlength="255"
                               placeholder="e.g. Laguna"
                               oninput="empCapName(this); empFieldError(this,'editEmpProvinceErr')">
                        <span class="emp-field-err" id="editEmpProvinceErr"></span>
                    </div>
                    <div class="form-group">
                        <label>City / Municipality </label>
                        <input type="text" name="city" id="editEmpCity" required maxlength="255"
                               placeholder="e.g. Santa Cruz"
                               oninput="empCapName(this); empFieldError(this,'editEmpCityErr')">
                        <span class="emp-field-err" id="editEmpCityErr"></span>
                    </div>
                    <div class="form-group">
                        <label>Region </label>
                        <input type="text" name="region" id="editEmpRegion" required maxlength="255"
                               placeholder="e.g. Region IV-A"
                               oninput="empCapName(this); empFieldError(this,'editEmpRegionErr')">
                        <span class="emp-field-err" id="editEmpRegionErr"></span>
                    </div>
                    <div class="form-group form-group-full">
                        <label>Street Address </label>
                        <input type="text" name="street_address" id="editEmpStreetAddress" required maxlength="500"
                               placeholder="e.g. Poblacion Street"
                               oninput="empCapName(this); empFieldError(this,'editEmpStreetErr')">
                        <span class="emp-field-err" id="editEmpStreetErr"></span>
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
                <button class="modal-close" type="button" onclick="closeEmpModal('salaryDetailModal')">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div id="salaryDetailBody"></div>
            <div class="modal-actions" style="margin-top: 16px;">
                <button type="button" class="cancel-btn" onclick="closeEmpModal('salaryDetailModal')">Close</button>
                <button type="button" class="save-btn" onclick="printSalarySlip()">
                    <i data-lucide="printer"></i>
                    Print Slip
                </button>
            </div>
        </div>
    </div>

    <!-- ===== RECORD SALARY MODAL ===== -->
    <div class="modal-overlay" id="recordPaymentModal">
        <div class="modal-card" style="max-width:620px;">
            <div class="modal-header">
                <div>
                    <h2 id="recordPaymentTitle">Record Salary</h2>
                    <p id="recordPaymentSubtitle">Select an employee to record their weekly salary.</p>
                </div>
                <button class="modal-close" type="button" id="closeRecordPaymentModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <!-- STEP 1: Employee Picker -->
            <div id="salaryStep1">
                <div class="search-box" style="margin-bottom:16px;">
                    <i data-lucide="search"></i>
                    <input type="text" id="salaryEmpPickerSearch" placeholder="Search outsourced worker by name or role...">
                </div>

                <div id="salaryEmpPickerList"
                     style="display:flex;flex-direction:column;gap:8px;max-height:360px;overflow-y:auto;padding-right:4px;">
                </div>

                <div class="modal-actions" style="margin-top:20px;">
                    <button type="button" class="cancel-btn" id="cancelSalaryStep1">Cancel</button>
                    <button type="button" class="save-btn" id="continueSalaryStep1">
                        <i data-lucide="arrow-right"></i>
                        Continue
                    </button>
                </div>
            </div>

            <!-- STEP 2: Salary Form (redesigned) -->
            <form id="recordPaymentForm" style="display:none;max-height:82vh;overflow-y:auto;padding-right:2px;">
                <input type="hidden" id="rpRecordId">
                <input type="hidden" id="rpEmployee">
                <input type="hidden" id="rpPeriod">
                <input type="hidden" id="rpDays">

                {{-- Employee banner --}}
                <div style="display:flex;align-items:center;gap:14px;background:linear-gradient(135deg,#0E1428,#1a2340);border-radius:12px;padding:14px 16px;margin-bottom:16px;">
                    <div class="cs-avatar" style="width:44px;height:44px;min-width:44px;font-size:16px;" id="salarySelectedAvatar">?</div>
                    <div>
                        <div style="font-weight:800;font-size:15px;color:#fff;" id="salarySelectedName">—</div>
                        <div style="font-size:12px;color:rgba(255,255,255,.5);" id="salarySelectedRole">—</div>
                    </div>
                </div>

                {{-- ── Projects ── --}}
                <div style="margin-bottom:16px;">
                    <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                        <i data-lucide="folder" style="width:12px;height:12px;"></i> Project(s)
                        <span style="font-weight:500;text-transform:none;letter-spacing:0;">(optional — select all that apply)</span>
                    </div>
                    <div id="rpProjectList" style="display:flex;flex-direction:column;gap:8px;max-height:220px;overflow-y:auto;padding-right:2px;">
                        <div style="font-size:12px;color:var(--muted);text-align:center;padding:16px 0;">Loading projects…</div>
                    </div>
                </div>

                {{-- ── Attendance ── --}}
                <div style="background:var(--cream-soft);border:1px solid var(--border);border-radius:12px;padding:14px;margin-bottom:14px;">
                    <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:12px;">Attendance</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:10px;">
                        <div>
                            <label style="font-size:11px;font-weight:700;color:var(--muted);display:block;margin-bottom:4px;">Daily Rate (₱)</label>
                            <input type="number" id="rpDailyRate" readonly min="0" step="0.01"
                                   style="width:100%;background:#fff;border:1px solid var(--border);border-radius:8px;padding:8px 10px;font-size:13px;font-weight:800;color:var(--dark);cursor:default;">
                        </div>
                        <div>
                            <label style="font-size:11px;font-weight:700;color:var(--muted);display:block;margin-bottom:4px;">Full Days</label>
                            <input type="number" id="rpFullDays" placeholder="0" min="0" max="7" step="1"
                                   oninput="updatePayPreview()"
                                   style="width:100%;border:1px solid var(--border);border-radius:8px;padding:8px 10px;font-size:13px;font-weight:700;background:#fff;">
                            <span style="font-size:10px;color:var(--muted);">max 7</span>
                        </div>
                        <div>
                            <label style="font-size:11px;font-weight:700;color:var(--muted);display:block;margin-bottom:4px;">Half Days</label>
                            <input type="number" id="rpHalfDays" placeholder="0" min="0" max="7" step="1"
                                   oninput="updatePayPreview()"
                                   style="width:100%;border:1px solid var(--border);border-radius:8px;padding:8px 10px;font-size:13px;background:#fff;">
                            <span style="font-size:10px;color:var(--muted);">× 0.5 each</span>
                        </div>
                        <div>
                            <label style="font-size:11px;font-weight:700;color:var(--muted);display:block;margin-bottom:4px;">OT Hours</label>
                            <input type="number" id="rpOvertimeHours" placeholder="0" min="0" max="24" step="0.5"
                                   oninput="updatePayPreview()"
                                   style="width:100%;border:1px solid var(--border);border-radius:8px;padding:8px 10px;font-size:13px;background:#fff;">
                        </div>
                    </div>
                </div>

                {{-- ── Pay Breakdown ── --}}
                <div id="rpPayBreakdown" style="display:none;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px;">
                    <div style="background:var(--dark);padding:10px 14px;">
                        <span style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.5);">Pay Computation</span>
                    </div>
                    <div style="padding:12px 14px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:0;border-bottom:1px solid var(--border);">
                        <div style="padding:8px;border-right:1px solid var(--border);">
                            <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:4px;">Regular Pay</div>
                            <div style="font-size:15px;font-weight:800;color:var(--dark);" id="pbRegular">₱0.00</div>
                        </div>
                        <div style="padding:8px;border-right:1px solid var(--border);">
                            <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:4px;">Half Day Pay</div>
                            <div style="font-size:15px;font-weight:800;color:var(--dark);" id="pbHalf">₱0.00</div>
                        </div>
                        <div style="padding:8px;">
                            <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:4px;">OT Pay</div>
                            <div style="font-size:15px;font-weight:800;color:#2563eb;" id="pbOT">₱0.00</div>
                        </div>
                    </div>
                    <div style="padding:12px 14px;display:flex;justify-content:space-between;align-items:center;background:var(--cream-soft);">
                        <span style="font-size:12px;font-weight:700;color:var(--dark);">Gross Labor Cost</span>
                        <span style="font-size:20px;font-weight:900;color:#16a34a;" id="pbGross">₱0.00</span>
                    </div>
                </div>

                {{-- ── Project Cost Allocation ── --}}
                <div id="rpAllocationPreview" style="display:none;border:1px solid #bfdbfe;border-radius:12px;overflow:hidden;margin-bottom:14px;">
                    <div style="background:#1e40af;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.7);">Project Cost Allocation</span>
                        <span style="font-size:11px;font-weight:700;color:rgba(255,255,255,.5);">Split equally</span>
                    </div>
                    <div id="rpAllocRows" style="padding:10px 14px;display:flex;flex-direction:column;gap:6px;"></div>
                    <div style="padding:10px 14px;background:#eff6ff;border-top:1px solid #bfdbfe;display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:12px;color:#1e40af;font-weight:700;">Total Allocated</span>
                        <span style="font-size:14px;font-weight:900;color:#1e40af;" id="rpAllocTotal">₱0.00</span>
                    </div>
                </div>

                <div id="rpError" style="display:none;background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;margin-bottom:10px;color:#991b1b;font-size:13px;"></div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="backSalaryStep2">
                        <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Back
                    </button>
                    <button type="button" class="cancel-btn" id="cancelRecordPayment">Cancel</button>
                    <button type="submit" class="save-btn" id="recordPaymentSubmitBtn">
                        <i data-lucide="save"></i> Save Record
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
        // Force reload if restored from bfcache
        window.addEventListener('pageshow', function(e) {
            if (e.persisted) window.location.reload();
        });

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
                    document.getElementById('editEmpDailyRate').value     = this.dataset.rate || 0;
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

            document.getElementById('employeeSearch').addEventListener('keyup', filterEmployees);
            document.getElementById('empTypeFilter').addEventListener('change', filterEmployees);
            document.getElementById('empStatusFilter').addEventListener('change', filterEmployees);
            filterEmployees();

            // ---- Record Salary button (header) ----
            var openRecSalBtn = document.getElementById('openRecordPaymentModal');
            if (openRecSalBtn) openRecSalBtn.addEventListener('click', function () { openAddOutsourcedModal(); });

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

                    if (tab === 'salary') loadSalaryRecords(currentSalaryPeriod);
                });
            });

            document.getElementById('salarySearch').addEventListener('keyup', filterSalaryTable);

            document.getElementById('salaryPrevWeek').addEventListener('click', function () { shiftSalaryPeriod(-7); });
            document.getElementById('salaryNextWeek').addEventListener('click', function () { shiftSalaryPeriod(7); });
            document.getElementById('salaryThisWeek').addEventListener('click', function () { loadSalaryRecords(TODAY_PAY_PERIOD); });

            populateSalaryJumpYears();
            populateSalaryJumpMonths();

            document.getElementById('salaryJumpToDate').addEventListener('click', function (e) {
                e.stopPropagation();
                var panel = document.getElementById('salaryJumpPanel');
                if (panel.classList.contains('show')) {
                    panel.classList.remove('show');
                } else {
                    openSalaryJumpPanel();
                }
            });
            document.getElementById('salaryJumpPanel').addEventListener('click', function (e) {
                e.stopPropagation();
            });
            document.addEventListener('click', function () {
                document.getElementById('salaryJumpPanel').classList.remove('show');
            });

            document.getElementById('salaryJumpYear').addEventListener('change', refreshSalaryJumpWeeks);
            document.getElementById('salaryJumpMonth').addEventListener('change', refreshSalaryJumpWeeks);

            document.getElementById('salaryJumpGo').addEventListener('click', function () {
                var week = document.getElementById('salaryJumpWeek').value;
                if (week) loadSalaryRecords(week);
                document.getElementById('salaryJumpPanel').classList.remove('show');
            });

            if (activeTab === 'salary') loadSalaryRecords(null);
        });
    })();

    // =================== SALARY MODULE ===================

    var SALARY_INDEX_URL    = '{{ route("admin.salary.index") }}';
    var SALARY_STORE_URL    = '{{ route("admin.salary.store") }}';
    var SALARY_PROJECTS_URL = '{{ route("admin.salary.projects") }}';
    var CSRF                = '{{ csrf_token() }}';
    var SALARY_PROJECTS_CACHE = null;

    function loadSalaryProjects(selectedIds) {
        selectedIds = selectedIds || [];
        var list = document.getElementById('rpProjectList');
        if (!list) return;

        function render(projects) {
            if (!projects.length) {
                list.innerHTML = '<div style="font-size:12px;color:var(--muted);text-align:center;padding:16px 0;">No active projects</div>';
                return;
            }
            list.innerHTML = projects.map(function(p) {
                var sel = selectedIds.indexOf(p.id) !== -1;
                return '<div data-proj-id="' + p.id + '" data-proj-name="' + (p.name||'').replace(/"/g,'&quot;') + '" data-proj-client="' + (p.client||'') + '" data-proj-spec="' + (p.spec||'') + '"'
                    + ' onclick="toggleProjectCard(this)" style="cursor:pointer;border-radius:12px;padding:12px 14px;border:2px solid ' + (sel ? '#2563eb' : 'var(--border)') + ';background:' + (sel ? '#eff6ff' : '#fff') + ';transition:all .15s;position:relative;">'
                    // Check icon
                    + '<div style="position:absolute;top:10px;right:10px;width:20px;height:20px;border-radius:50%;background:' + (sel ? '#2563eb' : 'var(--cream-deep)') + ';display:flex;align-items:center;justify-content:center;transition:all .15s;">'
                    +   '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="' + (sel ? '#fff' : '#aaa') + '" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>'
                    + '</div>'
                    // Project name
                    + '<div style="font-size:13px;font-weight:700;color:var(--dark);padding-right:28px;margin-bottom:8px;">' + p.name + '</div>'
                    // Meta row
                    + '<div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">'
                    + (p.client ? '<span style="font-size:11px;font-weight:600;color:#475569;background:#f1f5f9;padding:2px 8px;border-radius:999px;border:1px solid #cbd5e1;">' + p.client + '</span>' : '')
                    + (p.spec   ? '<span style="font-size:11px;font-weight:600;color:#1d4ed8;background:#eff6ff;padding:2px 8px;border-radius:999px;border:1px solid #bfdbfe;">' + p.spec + '</span>' : '')
                    + '</div>'
                    // Allocated cost (live preview)
                    + '<div class="proj-alloc-line" style="margin-top:8px;font-size:11px;color:' + (sel ? '#1d4ed8' : 'var(--muted)') + ';font-weight:700;display:' + (sel ? 'block' : 'none') + ';">Allocated: <span class="proj-alloc-amt">₱0.00</span></div>'
                    + '</div>';
            }).join('');
            updatePayPreview();
        }

        if (SALARY_PROJECTS_CACHE) { render(SALARY_PROJECTS_CACHE); return; }
        list.innerHTML = '<div style="font-size:12px;color:var(--muted);text-align:center;padding:16px 0;">Loading…</div>';
        fetch(SALARY_PROJECTS_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r){ return r.json(); })
            .then(function(data){ SALARY_PROJECTS_CACHE = data; render(data); });
    }

    function toggleProjectCard(card) {
        var sel = card.style.borderColor === 'rgb(37, 99, 235)' || card.getAttribute('data-selected') === '1';
        sel = !sel;
        card.setAttribute('data-selected', sel ? '1' : '0');
        card.style.border     = '2px solid ' + (sel ? '#2563eb' : 'var(--border)');
        card.style.background = sel ? '#eff6ff' : '#fff';
        var icon  = card.querySelector('div[style*="position:absolute"] div, div[style*="position:absolute"]');
        var check = card.querySelector('div[style*="position:absolute"]');
        if (check) {
            check.style.background = sel ? '#2563eb' : 'var(--cream-deep)';
            var svg = check.querySelector('svg polyline');
            if (svg) svg.setAttribute('stroke', sel ? '#fff' : '#aaa');
        }
        var allocLine = card.querySelector('.proj-alloc-line');
        if (allocLine) allocLine.style.display = sel ? 'block' : 'none';
        updatePayPreview();
    }

    function getSelectedProjectIds() {
        return Array.from(document.querySelectorAll('#rpProjectList [data-proj-id][data-selected="1"]'))
            .map(function(c){ return parseInt(c.dataset.projId); });
    }

    // Legacy stub (no longer used but kept for safety)
    function updateProjectAllocationUI() { updatePayPreview(); }
    function updateProjHighlight() {}

    @php
    $employeesForSalaryPicker = $employees->where('status', 'Active')
        ->where('employee_type', 'Outsourced')
        ->map(function ($e) {
            return [
                'id'         => $e->id,
                'name'       => $e->full_name,
                'role'       => $e->role ?? 'Employee',
                'daily_rate' => (float) ($e->daily_rate ?? 0),
            ];
        })->values();
    @endphp
    var SALARY_EMPLOYEES   = @json($employeesForSalaryPicker);
    var pickedSalaryEmployee = null;
    var CURRENT_SALARY_RECORDS = [];
    var currentSalaryPeriod = null;

    // Returns the Monday of the week containing `date`, formatted as "YYYY-MM-DD"
    function currentPayPeriod() {
        var d   = new Date();
        var day = d.getDay(); // 0=Sun
        var diff = (day === 0) ? -6 : 1 - day;
        d.setDate(d.getDate() + diff);
        return d.getFullYear() + '-'
            + String(d.getMonth() + 1).padStart(2, '0') + '-'
            + String(d.getDate()).padStart(2, '0');
    }

    var TODAY_PAY_PERIOD = currentPayPeriod();

    // Parses a "YYYY-MM-DD" string as a local Date (avoids UTC timezone shift)
    function parsePayPeriod(s) {
        var parts = s.split('-');
        return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
    }

    function formatPayPeriodDate(d) {
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
    }

    function updateSalaryPeriodLabel() {
        var label       = document.getElementById('salaryPeriodLabel');
        var thisWeekBtn = document.getElementById('salaryThisWeek');
        if (!currentSalaryPeriod) return;

        var start = parsePayPeriod(currentSalaryPeriod);
        var end   = new Date(start);
        end.setDate(end.getDate() + 6);

        label.textContent = 'Week of ' + formatPayPeriodDate(start) + ' – ' + formatPayPeriodDate(end);
        thisWeekBtn.style.display = (currentSalaryPeriod === TODAY_PAY_PERIOD) ? 'none' : '';
    }

    // Shift the displayed pay period by `days` (e.g. -7 / +7 for prev/next week)
    function shiftSalaryPeriod(days) {
        var d = parsePayPeriod(currentSalaryPeriod || TODAY_PAY_PERIOD);
        d.setDate(d.getDate() + days);
        var newPeriod = d.getFullYear() + '-'
            + String(d.getMonth() + 1).padStart(2, '0') + '-'
            + String(d.getDate()).padStart(2, '0');
        loadSalaryRecords(newPeriod);
    }

    // ---- Year / Month / Week jump panel ----
    function fmtYMD(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function mondayOf(date) {
        var d   = new Date(date);
        var day = d.getDay(); // 0=Sun
        var diff = (day === 0) ? -6 : 1 - day;
        d.setDate(d.getDate() + diff);
        return d;
    }

    function populateSalaryJumpYears() {
        var sel = document.getElementById('salaryJumpYear');
        var thisYear = new Date().getFullYear();
        sel.innerHTML = '';
        for (var y = thisYear + 1; y >= thisYear - 5; y--) {
            var opt = document.createElement('option');
            opt.value = y;
            opt.textContent = y;
            sel.appendChild(opt);
        }
    }

    function populateSalaryJumpMonths() {
        var sel    = document.getElementById('salaryJumpMonth');
        var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        sel.innerHTML = '';
        months.forEach(function (name, idx) {
            var opt = document.createElement('option');
            opt.value = idx;
            opt.textContent = name;
            sel.appendChild(opt);
        });
    }

    // Populates the Week select with every Monday-start week overlapping the given year/month.
    // Selects `preferredPeriod` ("YYYY-MM-DD" Monday) if it's among the generated weeks.
    function populateSalaryJumpWeeks(year, month, preferredPeriod) {
        var sel = document.getElementById('salaryJumpWeek');
        sel.innerHTML = '';

        var firstDay = new Date(year, month, 1);
        var lastDay  = new Date(year, month + 1, 0);
        var monday   = mondayOf(firstDay);
        var weekNum  = 1;

        while (monday <= lastDay) {
            var sunday = new Date(monday);
            sunday.setDate(sunday.getDate() + 6);

            var opt = document.createElement('option');
            opt.value = fmtYMD(monday);
            opt.textContent = 'Week ' + weekNum + ' (' + formatPayPeriodDate(monday) + ' – ' + formatPayPeriodDate(sunday) + ')';
            sel.appendChild(opt);

            monday = new Date(monday);
            monday.setDate(monday.getDate() + 7);
            weekNum++;
        }

        if (preferredPeriod) {
            var match = sel.querySelector('option[value="' + preferredPeriod + '"]');
            if (match) sel.value = preferredPeriod;
        }
    }

    function refreshSalaryJumpWeeks() {
        var year  = parseInt(document.getElementById('salaryJumpYear').value, 10);
        var month = parseInt(document.getElementById('salaryJumpMonth').value, 10);
        populateSalaryJumpWeeks(year, month);
    }

    function openSalaryJumpPanel() {
        var period = currentSalaryPeriod || TODAY_PAY_PERIOD;
        var start  = parsePayPeriod(period);

        document.getElementById('salaryJumpYear').value  = start.getFullYear();
        document.getElementById('salaryJumpMonth').value = start.getMonth();
        populateSalaryJumpWeeks(start.getFullYear(), start.getMonth(), period);

        document.getElementById('salaryJumpPanel').classList.add('show');
    }

    function loadSalaryRecords(payPeriod) {
        var tbody  = document.getElementById('salaryTableBody');
        tbody.innerHTML = '<tr id="salaryLoadingRow"><td colspan="6" style="text-align:center;padding:48px 20px;color:var(--muted);"><div style="display:flex;flex-direction:column;align-items:center;gap:10px;"><i data-lucide="loader" style="width:28px;height:28px;opacity:0.4;"></i><span style="font-size:14px;font-weight:600;">Loading...</span></div></td></tr>';
        if (window.lucide) lucide.createIcons();

        var url = SALARY_INDEX_URL + (payPeriod ? ('?pay_period=' + encodeURIComponent(payPeriod)) : '') + '&_=' + Date.now();

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            currentSalaryPeriod = data.payPeriod;
            updateSalaryPeriodLabel();
            renderSalaryTable(data.records, data.summary);
        })
        .catch(function () {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">Failed to load records.</td></tr>';
        });
    }

    function renderSalaryTable(records, summary) {
        var tbody = document.getElementById('salaryTableBody');
        CURRENT_SALARY_RECORDS = records || [];

        if (!records || records.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:48px 20px;"><div style="display:flex;flex-direction:column;align-items:center;gap:10px;color:var(--muted);"><i data-lucide="inbox" style="width:36px;height:36px;opacity:0.4;"></i><span style="font-size:14px;font-weight:600;">No salary records yet.</span></div></td></tr>';
            if (window.lucide) lucide.createIcons();
            document.getElementById('summaryGross').textContent = '₱0.00';
            return;
        }

        tbody.innerHTML = records.map(function (r, idx) {
            var hasRecord  = !!r.id;
            var dailyRate  = parseFloat(r.daily_rate)       || 0;
            var daysWorked = parseFloat(r.days_worked)      || 0;
            var otHours    = parseFloat(r.overtime_hours)   || 0;
            var otPay      = otHours * (dailyRate / 8);
            var grossPay   = (dailyRate * daysWorked) + otPay;
            var netPay     = parseFloat(r.net_pay)          || 0;

            var iconStyle = 'style="width:16px;height:16px;"';
            var viewBtn = hasRecord
                ? '<button class="action-btn view" title="View Payslip" onclick="openSalaryDetail(' + idx + ')" style="color:#2563eb;"><i data-lucide="receipt-text" ' + iconStyle + '></i></button>'
                : '<button class="action-btn" title="No record yet" disabled style="opacity:.3;cursor:default;"><i data-lucide="receipt-text" ' + iconStyle + '></i></button>';
            var editBtn = '<button class="action-btn view" title="Edit" onclick="openSalaryModal(' + idx + ')"><i data-lucide="pencil" ' + iconStyle + '></i></button>';
            var delBtn  = (r.employee_type === 'Outsourced' && hasRecord)
                ? '<button class="action-btn" title="Delete" style="color:#dc2626;" onclick="deleteSalaryRecord(' + r.id + ')"><i data-lucide="trash-2"></i></button>'
                : '';

            return '<tr data-id="' + (r.id || '') + '" data-name="' + r.employee_name.toLowerCase() + '">'
                + '<td style="font-weight:600;">'                                          + escHtml(r.employee_name) + '</td>'
                + '<td>'                                                                   + escHtml(r.role || '—') + '</td>'
                + '<td>₱'                                                                  + fmt(dailyRate) + '</td>'
                + '<td>'                                                                   + (hasRecord ? daysWorked : '—') + '</td>'
                + '<td style="color:' + (otHours > 0 ? '#2563eb' : 'var(--muted)') + ';">'+ (otHours > 0 ? otHours + ' hrs' : '—') + '</td>'
                + '<td style="color:' + (otPay   > 0 ? '#2563eb' : 'var(--muted)') + ';">'+ (otPay   > 0 ? '₱' + fmt(otPay) : '—') + '</td>'
                + '<td>'                                                                   + (hasRecord ? '₱' + fmt(grossPay) : '—') + '</td>'
                + '<td style="font-weight:900;color:#16a34a;">'                           + (hasRecord ? '₱' + fmt(netPay) : '—') + '</td>'
                + '<td class="action-cell">' + editBtn + viewBtn + delBtn + '</td>'
                + '</tr>';
        }).join('');

        if (window.lucide) lucide.createIcons();

        document.getElementById('summaryGross').textContent = '₱' + fmt(summary.gross);

        filterSalaryTable();
    }

    function filterSalaryTable() {
        var q = document.getElementById('salarySearch').value.toLowerCase();
        document.querySelectorAll('#salaryTableBody tr[data-name]').forEach(function (row) {
            row.style.display = (!q || row.dataset.name.includes(q)) ? '' : 'none';
        });
    }

    // Show step 1 (employee picker) or step 2 (salary form) of the modal
    function showSalaryStep(step) {
        document.getElementById('salaryStep1').style.display      = (step === 1) ? '' : 'none';
        document.getElementById('recordPaymentForm').style.display = (step === 2) ? '' : 'none';
    }

    function setSalarySelectedEmployee(name, role) {
        document.getElementById('salarySelectedName').textContent = name;
        document.getElementById('salarySelectedRole').textContent = role || 'Employee';
        document.getElementById('salarySelectedAvatar').textContent = (name || '?').charAt(0).toUpperCase();
    }

    function renderSalaryEmployeePicker(filter) {
        var list = document.getElementById('salaryEmpPickerList');
        var q    = (filter || '').toLowerCase();
        var filtered = q
            ? SALARY_EMPLOYEES.filter(function (e) {
                return e.name.toLowerCase().indexOf(q) !== -1 || (e.role || '').toLowerCase().indexOf(q) !== -1;
              })
            : SALARY_EMPLOYEES;

        list.innerHTML = '';

        if (filtered.length === 0) {
            list.innerHTML = '<p style="text-align:center;color:var(--muted);padding:20px 0;font-size:14px;">No outsourced workers found.</p>';
            return;
        }

        filtered.forEach(function (emp) {
            var isSelected = pickedSalaryEmployee && pickedSalaryEmployee.id === emp.id;
            var item       = document.createElement('div');
            item.className = 'client-select-item' + (isSelected ? ' selected' : '');
            var init       = emp.name.charAt(0).toUpperCase();

            item.innerHTML =
                '<div class="client-select-avatar">' + init + '</div>' +
                '<div class="client-select-info">' +
                    '<div class="client-select-name">' + escHtml(emp.name) + '</div>' +
                    '<div class="client-select-meta"><span>' + escHtml(emp.role) + '</span></div>' +
                '</div>' +
                '<div class="client-select-check" style="display:' + (isSelected ? 'flex' : 'none') + ';align-items:center;">' +
                    '<i data-lucide="check-circle"></i>' +
                '</div>';

            item.addEventListener('click', function () {
                pickedSalaryEmployee = emp;
                document.querySelectorAll('#salaryEmpPickerList .client-select-item').forEach(function (el) {
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

    // Edit the salary entry for a row already shown in the table (regular employees
    // are listed automatically; outsourced rows appear once added)
    function openSalaryModal(idx) {
        var rec = CURRENT_SALARY_RECORDS[idx];
        if (!rec) return;

        document.getElementById('rpRecordId').value       = rec.id || '';
        document.getElementById('rpEmployee').value       = rec.employee_id;
        document.getElementById('rpPeriod').value         = rec.pay_period;
        document.getElementById('rpError').style.display  = 'none';

        var pb = document.getElementById('rpPayBreakdown');
        var ap = document.getElementById('rpAllocationPreview');
        if (pb) pb.style.display = 'none';
        if (ap) ap.style.display = 'none';

        pickedSalaryEmployee = null;

        document.getElementById('recordPaymentTitle').textContent    = rec.id ? 'Edit Salary Record' : 'Record Salary';
        document.getElementById('recordPaymentSubtitle').textContent = 'Update the weekly salary for ' + rec.employee_name + '.';

        setSalarySelectedEmployee(rec.employee_name, rec.role);
        document.getElementById('rpDailyRate').value      = rec.daily_rate;
        var storedDays = parseFloat(rec.days_worked) || 0;
        var fullPart   = Math.floor(storedDays);
        var halfPart   = Math.round((storedDays - fullPart) / 0.5);
        document.getElementById('rpFullDays').value       = fullPart || '';
        document.getElementById('rpHalfDays').value       = halfPart || '';
        document.getElementById('rpDays').value           = storedDays;
        document.getElementById('rpOvertimeHours').value  = rec.overtime_hours || '';
        loadSalaryProjects(rec.project_ids || (rec.project_id ? [rec.project_id] : []));

        document.getElementById('backSalaryStep2').style.display = 'none';
        showSalaryStep(2);
        updatePayPreview();

        openEmpModal('recordPaymentModal');
    }

    // Add an outsourced worker's salary entry for the current pay period
    function openAddOutsourcedModal() {
        document.getElementById('rpRecordId').value       = '';
        document.getElementById('rpEmployee').value       = '';
        document.getElementById('rpPeriod').value         = currentSalaryPeriod || TODAY_PAY_PERIOD;
        document.getElementById('rpError').style.display  = 'none';
        document.getElementById('rpDailyRate').value      = '';
        document.getElementById('rpFullDays').value       = '';
        document.getElementById('rpHalfDays').value       = '';
        document.getElementById('rpDays').value           = '';
        document.getElementById('rpOvertimeHours').value  = '';
        var pb = document.getElementById('rpPayBreakdown');
        var ap = document.getElementById('rpAllocationPreview');
        if (pb) pb.style.display = 'none';
        if (ap) ap.style.display = 'none';

        document.getElementById('recordPaymentTitle').textContent    = 'Add Outsourced Worker';
        document.getElementById('recordPaymentSubtitle').textContent = 'Select an outsourced worker to record their weekly salary.';

        loadSalaryProjects([]);
        pickedSalaryEmployee = null;
        document.getElementById('salaryEmpPickerSearch').value = '';
        renderSalaryEmployeePicker('');
        showSalaryStep(1);
        openEmpModal('recordPaymentModal');
    }

    function updatePayPreview() {
        var rate   = parseFloat(document.getElementById('rpDailyRate').value)      || 0;
        var fullD  = parseFloat(document.getElementById('rpFullDays').value)       || 0;
        var halfD  = parseFloat(document.getElementById('rpHalfDays').value)       || 0;
        var otHrs  = parseFloat(document.getElementById('rpOvertimeHours').value)  || 0;
        var hourly = rate / 8;

        // Compute pay components
        var regPay  = rate * fullD;
        var halfPay = rate * 0.5 * halfD;
        var otPay   = otHrs * hourly;
        var gross   = regPay + halfPay + otPay;
        var totalDays = fullD + halfD * 0.5;

        // ── Pay breakdown panel ──
        var breakdown = document.getElementById('rpPayBreakdown');
        if (breakdown) {
            breakdown.style.display = (gross > 0 || fullD > 0 || halfD > 0 || otHrs > 0) ? '' : 'none';
            document.getElementById('pbRegular').textContent = '₱' + fmt(regPay);
            document.getElementById('pbHalf').textContent    = '₱' + fmt(halfPay);
            document.getElementById('pbOT').textContent      = '₱' + fmt(otPay);
            document.getElementById('pbGross').textContent   = '₱' + fmt(gross);
        }

        // ── Allocation preview ──
        var selectedCards = Array.from(document.querySelectorAll('#rpProjectList [data-proj-id][data-selected="1"]'));
        var allocEl  = document.getElementById('rpAllocationPreview');
        var rowsEl   = document.getElementById('rpAllocRows');
        var totalEl  = document.getElementById('rpAllocTotal');

        if (allocEl && selectedCards.length > 0 && gross > 0) {
            allocEl.style.display = '';
            var n        = selectedCards.length;
            var perProj  = Math.floor(gross * 100 / n) / 100;
            var remainder= Math.round((gross - perProj * n) * 100) / 100;

            var html = '';
            selectedCards.forEach(function(card, i) {
                var name  = card.dataset.projName || 'Project';
                var amt   = perProj + (i === n - 1 ? remainder : 0);
                html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #dbeafe;">'
                    + '<div style="min-width:0;">'
                    +   '<div style="font-size:12px;font-weight:700;color:#1e40af;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:260px;">✓ ' + name + '</div>'
                    + '</div>'
                    + '<div style="font-size:13px;font-weight:800;color:#1e40af;flex-shrink:0;margin-left:12px;">₱' + fmt(amt) + '</div>'
                    + '</div>';
                // Update allocated cost line on the card itself
                var line = card.querySelector('.proj-alloc-line');
                var amtEl = card.querySelector('.proj-alloc-amt');
                if (line)  line.style.display = 'block';
                if (amtEl) amtEl.textContent  = '₱' + fmt(amt);
            });
            rowsEl.innerHTML = html;
            totalEl.textContent = '₱' + fmt(gross);
        } else {
            if (allocEl) allocEl.style.display = 'none';
            // Clear allocated lines on unselected cards
            document.querySelectorAll('#rpProjectList [data-proj-id]').forEach(function(card) {
                var line = card.querySelector('.proj-alloc-line');
                if (line && card.getAttribute('data-selected') !== '1') line.style.display = 'none';
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        ['rpDailyRate', 'rpDays', 'rpOvertimeHours'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('input', updatePayPreview);
        });

        document.getElementById('salaryEmpPickerSearch').addEventListener('input', function () {
            renderSalaryEmployeePicker(this.value);
        });

        document.getElementById('continueSalaryStep1').addEventListener('click', function () {
            if (!pickedSalaryEmployee) { alert('Please select an outsourced worker to continue.'); return; }
            document.getElementById('rpEmployee').value  = pickedSalaryEmployee.id;
            document.getElementById('rpDailyRate').value = pickedSalaryEmployee.daily_rate || '';
            setSalarySelectedEmployee(pickedSalaryEmployee.name, pickedSalaryEmployee.role);
            document.getElementById('backSalaryStep2').style.display = '';
            showSalaryStep(2);
            updatePayPreview();
        });

        document.getElementById('backSalaryStep2').addEventListener('click', function () {
            showSalaryStep(1);
        });

        document.getElementById('cancelSalaryStep1').addEventListener('click', function () { closeEmpModal('recordPaymentModal'); });
        document.getElementById('closeRecordPaymentModal').addEventListener('click', function () { closeEmpModal('recordPaymentModal'); });
        document.getElementById('cancelRecordPayment').addEventListener('click',     function () { closeEmpModal('recordPaymentModal'); });

        document.getElementById('recordPaymentForm').addEventListener('submit', function (e) {
            e.preventDefault();
            var errEl     = document.getElementById('rpError');
            var submitBtn = document.getElementById('recordPaymentSubmitBtn');
            var recordId  = document.getElementById('rpRecordId').value;
            var isEdit    = !!recordId;

            errEl.style.display = 'none';
            submitBtn.disabled  = true;

            // Single attendance fields
            var fullD     = parseFloat(document.getElementById('rpFullDays').value)      || 0;
            var halfD     = parseFloat(document.getElementById('rpHalfDays').value)      || 0;
            var totalDays = fullD + halfD * 0.5;
            var totalOT   = parseFloat(document.getElementById('rpOvertimeHours').value) || 0;

            // Build equal-split allocations for selected projects
            var projIds   = getSelectedProjectIds();
            var rate      = parseFloat(document.getElementById('rpDailyRate').value) || 0;
            var hourly    = rate / 8;
            var gross     = rate * totalDays + totalOT * hourly;
            var n         = projIds.length;
            var allocations = [];
            if (n > 0 && gross > 0) {
                var perProj   = Math.floor(gross * 100 / n) / 100;
                var remainder = Math.round((gross - perProj * n) * 100) / 100;
                projIds.forEach(function(pid, i) {
                    allocations.push({
                        project_id:     pid,
                        days_worked:    totalDays,
                        overtime_hours: totalOT,
                        allocated_pay:  perProj + (i === n - 1 ? remainder : 0)
                    });
                });
            }

            var payload = {
                employee_id:    document.getElementById('rpEmployee').value,
                project_ids:    projIds,
                pay_period:     document.getElementById('rpPeriod').value,
                days_worked:    totalDays,
                overtime_hours: totalOT,
                allocations:    allocations,
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
                loadSalaryRecords(currentSalaryPeriod);
                showEmpToast(isEdit ? 'Salary record updated.' : 'Salary record saved.');
            })
            .catch(function () {
                submitBtn.disabled   = false;
                errEl.textContent    = 'Something went wrong. Please try again.';
                errEl.style.display  = 'block';
            });
        });
    });

    function deleteSalaryRecord(id) {
        if (!confirm('Delete this salary record? This cannot be undone.')) return;
        fetch('/admin/salary-records/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(function () {
            loadSalaryRecords(currentSalaryPeriod);
            showEmpToast('Record deleted.');
        });
    }

    function fmt(n) { return Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function escHtml(s) { return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function openSalaryDetail(idx) {
        var r = CURRENT_SALARY_RECORDS[idx];
        if (!r || !r.id) return;

        var dailyRate  = parseFloat(r.daily_rate)        || 0;
        var daysWorked = parseFloat(r.days_worked)       || 0;
        var otHours    = parseFloat(r.overtime_hours)    || 0;
        var otPay      = parseFloat(r.overtime_pay)      || (otHours * dailyRate / 8);
        var basicPay   = dailyRate * daysWorked;
        var grossPay   = parseFloat(r.gross_pay)         || 0;
        var deductions = parseFloat(r.total_deductions)  || 0;
        var netPay     = parseFloat(r.net_pay)           || 0;

        var periodStart = parsePayPeriod(r.pay_period);
        var periodEnd   = new Date(periodStart);
        periodEnd.setDate(periodEnd.getDate() + 6);
        var period = formatPayPeriodDate(periodStart) + ' – ' + formatPayPeriodDate(periodEnd);

        document.getElementById('salaryDetailName').textContent = r.employee_name + ' · ' + (r.role || 'Employee');

        function detailRow(label, value, valueStyle) {
            return '<div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid var(--border);">'
                + '<span style="font-size:13px;color:var(--muted);">' + label + '</span>'
                + '<strong style="font-size:13px;' + (valueStyle || '') + '">' + value + '</strong>'
                + '</div>';
        }

        var dedSection = deductions > 0
            ? detailRow('Deductions', '- ₱' + fmt(deductions), 'color:#dc2626;')
            : '';

        document.getElementById('salaryDetailBody').innerHTML =
            '<div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">'
            + period
            + '</div>'
            + '<div style="border:1px solid var(--border);border-radius:12px;overflow:hidden;">'
            + detailRow('Daily Rate', '₱' + fmt(dailyRate))
            + detailRow('Days Worked', daysWorked + ' days')
            + detailRow('Basic Pay', '₱' + fmt(basicPay))
            + detailRow('Overtime (' + otHours + ' hrs)', otHours > 0 ? '₱' + fmt(otPay) : '0.00', otHours > 0 ? 'color:#2563eb;' : 'color:var(--muted);')
            + detailRow('Gross Pay', '₱' + fmt(grossPay))
            + dedSection
            + '<div style="display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-top:2px solid var(--border);background:var(--cream-soft);">'
                + '<span style="font-size:14px;font-weight:900;color:var(--dark);">NET PAY</span>'
                + '<strong style="font-size:20px;font-weight:900;color:#16a34a;">₱' + fmt(netPay) + '</strong>'
            + '</div>'
            + '</div>';

        openEmpModal('salaryDetailModal');
    }

    function printSalarySlip() {
        var body    = document.getElementById('salaryDetailBody');
        var name    = document.getElementById('salaryDetailName').textContent;
        var win     = window.open('', '_blank', 'width=480,height=600');
        win.document.write(
            '<html><head><title>Salary Slip – ' + escHtml(name) + '</title>'
            + '<style>body{font-family:sans-serif;padding:32px;color:#111;}h2{margin:0 0 4px;}p{margin:0 0 20px;color:#666;font-size:13px;}'
            + '.row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #eee;font-size:13px;}'
            + '.row strong{font-size:13px;}.net{display:flex;justify-content:space-between;padding:14px 0;margin-top:4px;border-top:2px solid #111;}'
            + '.net span{font-size:15px;font-weight:900;}.net strong{font-size:20px;font-weight:900;color:#16a34a;}'
            + '</style></head><body>'
            + '<h2>Salary Slip</h2><p>' + escHtml(name) + '</p>'
            + body.innerHTML
            + '</body></html>'
        );
        win.document.close();
        win.print();
    }

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

    // ---- Employee form validation ----
    function empCapName(input) {
        var pos = input.selectionStart;
        input.value = input.value.replace(/(^|[\s'\-])([a-zà-öø-ÿñ])/g, function(_, sep, ch) {
            return sep + ch.toUpperCase();
        });
        input.setSelectionRange(pos, pos);
    }

    var NAME_RE    = /^[A-Za-zÀ-ÖØ-öø-ÿÑñ\s'\-]+$/;
    var CONTACT_RE = /^(09|\+639)\d{9}$/;
    var EMAIL_RE   = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function empValidateField(el) {
        var name     = el.name;
        var val      = el.value.trim();
        var required = el.required;

        if (name === 'first_name' || name === 'last_name') {
            var label = name === 'first_name' ? 'First name' : 'Last name';
            if (!val)              return label + ' is required.';
            if (val.length < 2)   return label + ' must be at least 2 characters.';
            if (val.length > 50)  return label + ' must not exceed 50 characters.';
            if (!NAME_RE.test(val)) return label + ' must contain letters only (hyphens and apostrophes allowed).';
        }
        if (name === 'contact') {
            if (!val) return 'Contact number is required.';
            var stripped = val.replace(/\s/g, '');
            if (/[^0-9+]/.test(stripped)) return 'Contact number must contain digits only.';
            if (!CONTACT_RE.test(stripped)) return 'Must be a valid PH mobile number (e.g. 09171234567).';
        }
        if (name === 'email') {
            if (required && !val) return 'Email address is required.';
            if (val && val.length > 255) return 'Email must not exceed 255 characters.';
            if (val && !EMAIL_RE.test(val)) return 'Enter a valid email address.';
        }
        if (name === 'role' && !val) return 'Please select a role.';
        if (name === 'employee_type' && !val) return 'Please select an employee type.';
        if (name === 'daily_rate') {
            if (!val) return 'Daily rate is required.';
            if (parseFloat(val) <= 0) return 'Daily rate must be greater than zero.';
        }
        if (['province','city','region'].includes(name) && !val) return 'This field is required.';
        if (name === 'street_address' && !val) return 'Street address is required.';
        return '';
    }

    function formatEmpContact(input) {
        var digits = input.value.replace(/\D/g, '').substring(0, 11);
        var formatted = digits;
        if (digits.length > 4 && digits.length <= 7) {
            formatted = digits.substring(0,4) + '-' + digits.substring(4);
        } else if (digits.length > 7) {
            formatted = digits.substring(0,4) + '-' + digits.substring(4,7) + '-' + digits.substring(7);
        }
        input.value = formatted;
    }

    function empFieldError(el, errId) {
        var msg  = empValidateField(el);
        var span = document.getElementById(errId);
        if (!span) return;
        span.textContent = msg;
        span.style.display = msg ? 'block' : 'none';
        el.style.borderColor = msg ? '#dc2626' : '';
    }

    function empValidateForm(formEl) {
        var valid = true;
        formEl.querySelectorAll('input[name], select[name]').forEach(function (el) {
            var errId = el.nextElementSibling && el.nextElementSibling.classList.contains('emp-field-err')
                ? el.nextElementSibling.id : null;
            if (!errId) return;
            var msg = empValidateField(el);
            var span = document.getElementById(errId);
            if (span) {
                span.textContent = msg;
                span.style.display = msg ? 'block' : 'none';
            }
            el.style.borderColor = msg ? '#dc2626' : '';
            if (msg) valid = false;
        });
        if (!valid) {
            var firstErr = formEl.querySelector('input[style*="dc2626"], select[style*="dc2626"]');
            if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return valid;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var editForm = document.getElementById('editEmpForm');
        if (editForm) {
            editForm.addEventListener('submit', function (e) {
                if (!empValidateForm(editForm)) e.preventDefault();
            });
        }
        var addForm = document.getElementById('addEmpAccountForm');
        if (addForm) {
            addForm.addEventListener('submit', function (e) {
                if (!empValidateForm(addForm)) e.preventDefault();
            });
        }
    });
    </script>

<style>
.emp-field-err {
    display: none;
    color: #dc2626;
    font-size: 11.5px;
    margin-top: 4px;
    line-height: 1.4;
}
</style>
</body>
</html>
