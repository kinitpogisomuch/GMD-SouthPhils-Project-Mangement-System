<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Salary | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}?v={{ @filemtime(public_path('css/employee.css')) }}" rel="stylesheet">
</head>
<body class="page-enter pg-employee-salary">

    @include('partials.employee.header')

    <main class="admin-content">
            @php
                $weekStart     = \Carbon\Carbon::parse($payPeriod);
                $year          = now()->year;
                $yearRecords   = $records->filter(fn ($r) => \Carbon\Carbon::parse($r->pay_period)->year === $year);
                $latest        = $records->first();
                $daysLabel     = fn ($d) => rtrim(rtrim(number_format((float) $d, 1), '0'), '.');
            @endphp

            <div class="sal-head">
                <div>
                    <div class="page-accent" aria-hidden="true"></div>
                    <h1>My Salary</h1>
                    <p>Your weekly pay records and history.</p>
                </div>
                <div class="sal-week-pill">
                    <i data-lucide="calendar-range"></i>
                    This week: {{ $weekStart->format('M d') }} – {{ $weekStart->copy()->addDays(6)->format('M d, Y') }}
                </div>
            </div>

            <div class="sal-kpis">
                <div class="sal-kpi">
                    <div class="sal-kpi-icon" style="background:#E7F6EC;color:#207A3A;"><i data-lucide="banknote"></i></div>
                    <div>
                        <div class="sal-kpi-label">Daily Rate</div>
                        <div class="sal-kpi-value">₱{{ number_format($employee->daily_rate ?? 0, 2) }}</div>
                        <div class="sal-kpi-sub">Per full day of work</div>
                    </div>
                </div>
                <div class="sal-kpi">
                    <div class="sal-kpi-icon" style="background:#EAF0FF;color:#2A4EAA;"><i data-lucide="calendar-check"></i></div>
                    <div>
                        <div class="sal-kpi-label">This Week</div>
                        <div class="sal-kpi-value">{{ $daysLabel($currentRecord->days_worked ?? 0) }} <span style="font-size:14px;color:var(--muted);font-weight:700;">days</span></div>
                        @if($currentRecord)
                            <div class="sal-kpi-sub is-ok">₱{{ number_format($currentRecord->gross_pay, 2) }} gross · recorded</div>
                        @else
                            <div class="sal-kpi-sub is-wait">Not recorded yet</div>
                        @endif
                    </div>
                </div>
                <div class="sal-kpi">
                    <div class="sal-kpi-icon" style="background:#F3E8FF;color:#7C3AED;"><i data-lucide="wallet"></i></div>
                    <div style="min-width:0;">
                        <div class="sal-kpi-label">Latest Net Pay</div>
                        <div class="sal-kpi-value">₱{{ number_format($latest->net_pay ?? 0, 2) }}</div>
                        <div class="sal-kpi-sub">
                            @if($latest)
                                Week of {{ \Carbon\Carbon::parse($latest->pay_period)->format('M d, Y') }}
                            @else
                                No pay records yet
                            @endif
                        </div>
                    </div>
                </div>
                <div class="sal-kpi">
                    <div class="sal-kpi-icon" style="background:#FFF3D6;color:#8A6100;"><i data-lucide="piggy-bank"></i></div>
                    <div>
                        <div class="sal-kpi-label">Earned in {{ $year }}</div>
                        <div class="sal-kpi-value">₱{{ number_format($yearRecords->sum('net_pay'), 2) }}</div>
                        <div class="sal-kpi-sub">{{ $yearRecords->count() }} pay period{{ $yearRecords->count() === 1 ? '' : 's' }} · {{ $daysLabel($yearRecords->sum('days_worked')) }} days worked</div>
                    </div>
                </div>
            </div>

            <div class="sal-card">
                <div class="sal-card-head">
                    <span class="sal-card-title"><i data-lucide="history"></i> Pay Period History</span>
                    <span class="sal-count">{{ $records->count() }} pay period{{ $records->count() !== 1 ? 's' : '' }}</span>
                </div>
                @if($records->isEmpty())
                    <div class="sal-empty">
                        <span class="sal-empty-icon"><i data-lucide="wallet"></i></span>
                        <strong>No salary records yet</strong>
                        <span style="font-size:12.5px;">Your weekly pay will show up here once it's recorded.</span>
                    </div>
                @else
                <div class="sal-scroll">
                    <table id="salaryHistoryTable">
                        <thead>
                            <tr>
                                <th>Pay Period</th>
                                <th class="num">Daily Rate</th>
                                <th class="ctr">Days</th>
                                <th class="ctr">OT Hours</th>
                                <th class="num">Gross Pay</th>
                                <th class="num">Net Pay</th>
                                <th class="ctr">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $record)
                                @php
                                    $recStart   = \Carbon\Carbon::parse($record->pay_period);
                                    $recEnd     = $recStart->copy()->addDays(6);
                                    $fullDays   = floor($record->days_worked);
                                    $halfDays   = ($record->days_worked - $fullDays) >= 0.5 ? 1 : 0;
                                    $periodText = $recStart->format('M d') . ' – ' . $recEnd->format('M d, Y');
                                    $isCurrent  = $record->pay_period === $payPeriod;
                                @endphp
                                <tr class="sal-row {{ $isCurrent ? 'salary-row-current' : '' }}">
                                    <td class="sal-period">
                                        <strong>{{ $periodText }}@if($isCurrent)<span class="sal-this-week">This week</span>@endif</strong>
                                        <small>{{ $record->payment_method === 'gcash' ? 'GCash' : 'Cash' }}@if($record->status === 'Paid')<span class="sal-paid">Paid</span>@endif</small>
                                    </td>
                                    <td class="num sal-tile" data-label="Rate">₱{{ number_format($record->daily_rate, 2) }}</td>
                                    <td class="ctr sal-tile" data-label="Days">
                                        {{ number_format($fullDays, 0) }}@if($halfDays)<span class="sal-muted"> + ½</span>@endif
                                    </td>
                                    <td class="ctr sal-tile {{ (float) $record->overtime_hours > 0 ? '' : 'sal-muted' }}" data-label="OT Hrs">{{ $daysLabel($record->overtime_hours) }}</td>
                                    <td class="num sal-gross" data-label="Gross">₱{{ number_format($record->gross_pay, 2) }}</td>
                                    <td class="num sal-netcell" data-label="Net Pay"><span class="sal-net">₱{{ number_format($record->net_pay, 2) }}</span></td>
                                    <td class="ctr sal-action">
                                        <button type="button" class="sal-view"
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
                                                    "payment_method"  => $record->payment_method ?? 'cash',
                                                    "paid"            => $record->status === 'Paid',
                                                ]) }})'>
                                            <i data-lucide="eye"></i><span>View</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
    </main>

    <!-- ===== SALARY DETAILS MODAL ===== -->
    <div class="modal-overlay" id="salaryDetailModal">
        <div class="modal-card sd-card" style="max-width:520px;">
            <div class="modal-header">
                <div class="sd-head">
                    <span class="sd-head-icon"><i data-lucide="wallet"></i></span>
                    <div>
                        <h2>Salary Details</h2>
                        <p>{{ $employee->full_name }} · {{ $employee->role ?? 'Employee' }}</p>
                    </div>
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

            var netPay  = parseFloat(r.net_pay) || (grossPay - deductions);
            var pmLabel = r.payment_method === 'gcash' ? 'GCash' : 'Cash';
            var pmIcon  = r.payment_method === 'gcash' ? 'smartphone' : 'banknote';
            var plural  = function (n, w) { return fmtDays(n) + ' ' + w + (n !== 1 ? 's' : ''); };
            // one line of the breakdown: label (+ how it's worked out) on the left, amount on the right
            var line = function (label, how, amount, cls) {
                return '<div class="sd-line ' + (cls || '') + '"><span><span class="sd-line-label">' + label + '</span>'
                    + (how ? '<small>' + how + '</small>' : '') + '</span><strong>' + amount + '</strong></div>';
            };

            document.getElementById('salaryDetailBody').innerHTML =
                // the take-home amount up front, with the week and how it's paid
                '<div class="sd-hero">'
                    + '<div class="sd-hero-top"><span class="sd-period"><i data-lucide="calendar-days"></i> ' + r.period + '</span>'
                    // "Paid" only marks a GCash payout confirmed by PayMongo — cash weeks are handed over and
                    // never change status, so no tag is shown for them (same as the salary table)
                    + (r.paid ? '<span class="sd-tag is-paid"><i data-lucide="check"></i> Paid</span>' : '') + '</div>'
                    + '<div class="sd-hero-label">' + (deductions > 0 ? 'Net Pay' : 'Total Pay') + '</div>'
                    + '<div class="sd-hero-amount">₱' + fmt(netPay) + '</div>'
                    + '<div class="sd-hero-method"><i data-lucide="' + pmIcon + '"></i> Paid via ' + pmLabel + '</div>'
                + '</div>'
                // attendance at a glance
                + '<div class="sd-section">Attendance</div>'
                + '<div class="sd-tiles">'
                    + '<div class="sd-tile"><span>Full Days</span><strong>' + fmtDays(fullDays) + '</strong></div>'
                    + '<div class="sd-tile"><span>Half Days</span><strong>' + fmtDays(halfDays) + '</strong></div>'
                    + '<div class="sd-tile"><span>Overtime</span><strong>' + fmtDays(otHours) + ' <small>hrs</small></strong></div>'
                + '</div>'
                // how the pay adds up
                + '<div class="sd-section">Earnings</div>'
                + '<div class="sd-lines">'
                    + line('Daily Rate', '', '₱' + fmt(dailyRate))
                    + line('Basic Pay', '₱' + fmt(dailyRate) + ' × ' + plural(daysWorked, 'day'), '₱' + fmt(basicPay))
                    + line('Overtime', otHours > 0 ? plural(otHours, 'hr') + ' × ₱' + fmt(dailyRate / 8) + '/hr' : 'No overtime this week', otHours > 0 ? '₱' + fmt(otPay) : '—', otHours > 0 ? 'is-ot' : 'is-muted')
                    + line('Gross Pay', '', '₱' + fmt(grossPay), 'is-sub')
                    + (deductions > 0 ? line('Deductions', '', '− ₱' + fmt(deductions), 'is-ded') : '')
                    + '<div class="sd-total"><span>' + (deductions > 0 ? 'Net Pay' : 'Total Pay') + '</span><strong>₱' + fmt(netPay) + '</strong></div>'
                + '</div>';
            if (typeof lucide !== 'undefined') lucide.createIcons();

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

            var printedOn = new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
            var pmLabel   = r.payment_method === 'gcash' ? 'GCash' : 'Cash';

            var particulars =
                payslipRow('Daily Rate', '₱' + fmt(dailyRate)) +
                payslipRow('Full Days', fmtDays(fullDays) + ' day' + (fullDays !== 1 ? 's' : '')) +
                payslipRow('Half Day', fmtDays(halfDays) + ' day' + (halfDays !== 1 ? 's' : '')) +
                payslipRow('Basic Pay', '₱' + fmt(basicPay)) +
                payslipRow('Overtime (' + fmtDays(otHours) + ' hrs)', otHours > 0 ? '₱' + fmt(otPay) : '0.00') +
                (deductions > 0 ? payslipRow('Deductions', '- ₱' + fmt(deductions), 'color:#dc2626;') : '') +
                payslipRow('Payment Method', pmLabel) +
                '<tr class="bs-total-row"><td style="font-size:14px;">TOTAL PAY</td><td style="text-align:right;font-size:17px;color:#16a34a;">₱' + fmt(grossPay) + '</td></tr>';

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
