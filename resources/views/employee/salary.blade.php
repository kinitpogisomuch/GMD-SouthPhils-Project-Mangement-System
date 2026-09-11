<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Salary | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
    <style>
        #salaryPageHeader {
            justify-content: center;
            text-align: center;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        #salaryStatsGrid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>
<body class="page-enter">

    @include('partials.employee.header')

    <main class="admin-content">
            <div class="pv-page-header" id="salaryPageHeader">
                <div>
                    <h1>My Salary</h1>
                    <p>View your weekly pay records and history.</p>
                </div>
            </div>

            <div class="stats-grid" id="salaryStatsGrid">
                <div class="stat-card green">
                    <div class="stat-icon green"><i data-lucide="banknote"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱{{ number_format($employee->daily_rate ?? 0, 2) }}</div>
                        <div class="stat-label">Daily Rate</div>
                    </div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-icon blue"><i data-lucide="calendar-check"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ number_format($currentRecord->days_worked ?? 0, 0) }}</div>
                        <div class="stat-label">Days Worked This Week</div>
                    </div>
                </div>
                <div class="stat-card purple">
                    <div class="stat-icon purple"><i data-lucide="wallet"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱{{ number_format($currentRecord->net_pay ?? 0, 2) }}</div>
                        <div class="stat-label">
                            Net Pay This Week
                            @if($currentRecord)
                                <span class="status-badge completed">Recorded</span>
                            @else
                                <span class="status-badge pending">Pending</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Pay Period History</span>
                </div>
                <div class="table-wrap" style="max-height:420px;overflow-y:auto;">
                    <table class="data-table">
                        <thead style="position:sticky;top:0;z-index:1;">
                            <tr>
                                <th>Pay Period</th>
                                <th>Daily Rate</th>
                                <th>Full Days</th>
                                <th>Half Days</th>
                                <th>OT Hours</th>
                                <th>Gross Pay</th>
                                <th>Net Pay</th>
                                <th>Salary Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $record)
                                @php
                                    $weekStart  = \Carbon\Carbon::parse($record->pay_period);
                                    $weekEnd    = $weekStart->copy()->addDays(6);
                                    $fullDays   = floor($record->days_worked);
                                    $halfDays   = ($record->days_worked - $fullDays) >= 0.5 ? 1 : 0;
                                    $periodText = $weekStart->format('M d') . ' – ' . $weekEnd->format('M d, Y');
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $periodText }}</strong>
                                        @if($record->pay_period === $payPeriod)
                                            <span class="status-badge ongoing">This Week</span>
                                        @endif
                                    </td>
                                    <td>₱{{ number_format($record->daily_rate, 2) }}</td>
                                    <td>{{ number_format($fullDays, 0) }}</td>
                                    <td>{{ $halfDays }}</td>
                                    <td>{{ number_format($record->overtime_hours, 0) }}</td>
                                    <td>₱{{ number_format($record->gross_pay, 2) }}</td>
                                    <td><strong>₱{{ number_format($record->net_pay, 2) }}</strong></td>
                                    <td class="action-cell">
                                        <button type="button" class="action-btn view"
                                                title="View Salary Details"
                                                onclick='openSalaryDetail({{ json_encode([
                                                    "period"          => $periodText,
                                                    "daily_rate"      => (float) $record->daily_rate,
                                                    "days_worked"     => (float) $record->days_worked,
                                                    "full_days"       => (float) $fullDays,
                                                    "half_days"       => (float) $halfDays,
                                                    "overtime_hours"  => (float) $record->overtime_hours,
                                                    "gross_pay"       => (float) $record->gross_pay,
                                                    "total_deductions"=> (float) $record->total_deductions,
                                                    "net_pay"         => (float) $record->net_pay,
                                                ]) }})'>
                                            <i data-lucide="eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="text-align:center;color:var(--muted);padding:32px 0;">
                                        No salary records yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
    </main>

    <!-- ===== SALARY DETAILS MODAL ===== -->
    <div class="modal-overlay" id="salaryDetailModal">
        <div class="modal-card" style="max-width:520px;">
            <div class="modal-header">
                <div>
                    <h2>Salary Details</h2>
                    <p>{{ $employee->full_name }} · {{ $employee->role ?? 'Employee' }}</p>
                </div>
                <button class="modal-close" type="button" onclick="closeSalaryDetailModal()">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div id="salaryDetailBody"></div>
            <div class="modal-actions" style="margin-top:16px;">
                <button type="button" class="cancel-btn" onclick="closeSalaryDetailModal()">Close</button>
                <button type="button" class="save-btn" onclick="printSalarySlip()">
                    <i data-lucide="printer"></i>
                    Print Slip
                </button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        var EMPLOYEE_NAME = @json($employee->full_name);
        var EMPLOYEE_ROLE = @json($employee->role ?? 'Employee');
        var LOGO_LEFT     = @json(asset('images/logo-left.png'));
        var LOGO_RIGHT    = @json(asset('images/logo-right.png'));
        var BS_CSS_URL    = @json(asset('css/billing_statement.css'));

        var CURRENT_SALARY_RECORD = null;

        function fmt(n) { return Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        function fmtDays(n) { return (n % 1 === 0) ? n.toFixed(0) : n.toFixed(1); }

        function detailRow(label, value, valueStyle) {
            return '<div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid var(--border);">'
                + '<span style="font-size:13px;color:var(--muted);">' + label + '</span>'
                + '<strong style="font-size:13px;' + (valueStyle || '') + '">' + value + '</strong>'
                + '</div>';
        }

        function openSalaryDetail(r) {
            CURRENT_SALARY_RECORD = r;

            var dailyRate  = parseFloat(r.daily_rate)     || 0;
            var daysWorked = parseFloat(r.days_worked)    || 0;
            var fullDays   = r.full_days != null ? parseFloat(r.full_days) : Math.floor(daysWorked);
            var halfDays   = r.half_days != null ? parseFloat(r.half_days) : (daysWorked - fullDays >= 0.5 ? 1 : 0);
            var otHours    = parseFloat(r.overtime_hours) || 0;
            var otPay      = otHours * dailyRate / 8;
            var basicPay   = dailyRate * daysWorked;
            var grossPay   = parseFloat(r.gross_pay)         || 0;
            var deductions = parseFloat(r.total_deductions)  || 0;
            var netPay     = parseFloat(r.net_pay)           || 0;

            var dedSection = deductions > 0
                ? detailRow('Deductions', '- ₱' + fmt(deductions), 'color:#dc2626;')
                : '';

            document.getElementById('salaryDetailBody').innerHTML =
                '<div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">'
                + r.period
                + '</div>'
                + '<div style="border:1px solid var(--border);border-radius:12px;overflow:hidden;">'
                + detailRow('Daily Rate', '₱' + fmt(dailyRate))
                + detailRow('Full Days', fullDays + ' day' + (fullDays !== 1 ? 's' : ''))
                + detailRow('Half Day', halfDays + ' day' + (halfDays !== 1 ? 's' : ''))
                + detailRow('Basic Pay', '₱' + fmt(basicPay))
                + detailRow('Overtime (' + otHours + ' hrs)', otHours > 0 ? '₱' + fmt(otPay) : '0.00', otHours > 0 ? 'color:#2563eb;' : 'color:var(--muted);')
                + detailRow('Gross Pay', '₱' + fmt(grossPay))
                + dedSection
                + '<div style="display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-top:2px solid var(--border);background:var(--cream-soft);">'
                    + '<span style="font-size:14px;font-weight:900;color:var(--dark);">NET PAY</span>'
                    + '<strong style="font-size:20px;font-weight:900;color:#16a34a;">₱' + fmt(netPay) + '</strong>'
                + '</div>'
                + '</div>';

            document.getElementById('salaryDetailModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeSalaryDetailModal() {
            document.getElementById('salaryDetailModal').classList.remove('show');
            document.body.style.overflow = '';
        }

        function payslipRow(label, value, valueStyle) {
            return '<tr><td>' + label + '</td><td style="text-align:right;' + (valueStyle || '') + '">' + value + '</td></tr>';
        }

        function printSalarySlip() {
            var r = CURRENT_SALARY_RECORD;
            if (!r) return;

            var dailyRate  = parseFloat(r.daily_rate)     || 0;
            var daysWorked = parseFloat(r.days_worked)    || 0;
            var fullDays   = r.full_days != null ? parseFloat(r.full_days) : Math.floor(daysWorked);
            var halfDays   = r.half_days != null ? parseFloat(r.half_days) : (daysWorked - fullDays >= 0.5 ? 1 : 0);
            var otHours    = parseFloat(r.overtime_hours) || 0;
            var otPay      = otHours * dailyRate / 8;
            var basicPay   = dailyRate * daysWorked;
            var grossPay   = parseFloat(r.gross_pay)        || 0;
            var deductions = parseFloat(r.total_deductions) || 0;
            var netPay     = parseFloat(r.net_pay)          || 0;

            var printedOn = new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });

            var particulars =
                payslipRow('Daily Rate', '₱' + fmt(dailyRate)) +
                payslipRow('Full Days', fmtDays(fullDays) + ' day' + (fullDays !== 1 ? 's' : '')) +
                payslipRow('Half Day', fmtDays(halfDays) + ' day' + (halfDays !== 1 ? 's' : '')) +
                payslipRow('Basic Pay', '₱' + fmt(basicPay)) +
                payslipRow('Overtime (' + fmtDays(otHours) + ' hrs)', otHours > 0 ? '₱' + fmt(otPay) : '0.00') +
                '<tr class="bs-subtotal-row">' + '<td>Gross Pay</td><td style="text-align:right;">₱' + fmt(grossPay) + '</td></tr>' +
                (deductions > 0 ? payslipRow('Deductions', '- ₱' + fmt(deductions), 'color:#dc2626;') : '') +
                '<tr class="bs-total-row"><td style="font-size:14px;">NET PAY</td><td style="text-align:right;font-size:17px;color:#16a34a;">₱' + fmt(netPay) + '</td></tr>';

            var html =
                '<html><head><title>Salary Slip – ' + EMPLOYEE_NAME + '</title>' +
                '<meta charset="UTF-8">' +
                '<link rel="stylesheet" href="' + BS_CSS_URL + '">' +
                '<style>' +
                    '.bs-particulars th:last-child, .bs-particulars td:last-child { text-align:right; }' +
                '</style>' +
                '</head><body>' +
                '<div class="bs-sheet">' +
                    '<div class="bs-letterhead">' +
                        '<div class="bs-logo-group">' +
                            '<img src="' + LOGO_LEFT + '" alt="GMD South Phils" class="bs-logo">' +
                            '<img src="' + LOGO_RIGHT + '" alt="" class="bs-logo">' +
                        '</div>' +
                        '<div class="bs-company-info">' +
                            '<div class="bs-company-name">GMD South Phils Metal Fabrication Works</div>' +
                            '<div>National Hi-way, Brgy. Masiit, Calauan, Laguna</div>' +
                        '</div>' +
                    '</div>' +

                    '<div class="bs-title">SALARY SLIP</div>' +

                    '<table class="bs-fields">' +
                        '<tr>' +
                            '<td class="bs-field-label">Employee:</td>' +
                            '<td class="bs-field-value">' + EMPLOYEE_NAME + '</td>' +
                            '<td class="bs-field-label">Role:</td>' +
                            '<td class="bs-field-value">' + EMPLOYEE_ROLE + '</td>' +
                        '</tr>' +
                        '<tr>' +
                            '<td class="bs-field-label">Pay Period:</td>' +
                            '<td class="bs-field-value">' + r.period + '</td>' +
                            '<td class="bs-field-label">Date Printed:</td>' +
                            '<td class="bs-field-value">' + printedOn + '</td>' +
                        '</tr>' +
                    '</table>' +

                    '<table class="bs-particulars">' +
                        '<thead><tr><th>Particulars</th><th>Amount</th></tr></thead>' +
                        '<tbody>' + particulars + '</tbody>' +
                    '</table>' +
                '</div>' +
                '</body></html>';

            var win = window.open('', '_blank');
            win.document.open();
            win.document.write(html);
            win.document.close();
            win.focus();

            // Give the linked stylesheet a moment to load before invoking print,
            // otherwise the preview can render unstyled on the first paint.
            setTimeout(function () { win.print(); }, 400);
        }
    </script>
</body>
</html>
