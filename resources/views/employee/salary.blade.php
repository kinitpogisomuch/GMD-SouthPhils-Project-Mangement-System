<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Salary | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
    <style>
        /* ── Page head ── */
        .sal-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px 20px; flex-wrap: wrap; margin-bottom: 18px; }
        .sal-head h1 { margin: 0; font-size: 28px; font-weight: 900; color: var(--dark); letter-spacing: -.5px; }   /* same as the dashboard greeting */
        .sal-head p { margin: 4px 0 0; font-size: 13.5px; color: var(--muted); }
        .sal-week-pill { display: inline-flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; color: var(--muted); background: var(--white); border: 1px solid var(--border); border-radius: 999px; padding: 7px 14px; white-space: nowrap; box-shadow: 0 4px 12px rgba(0,0,0,.05); }
        .sal-week-pill svg { width: 14px; height: 14px; }

        /* ── Summary cards (same look as the Materials / Payments cards) ── */
        .sal-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 20px; }
        .sal-kpi { display: flex; align-items: flex-start; gap: 14px; padding: 16px 18px; background: var(--white); border: 1px solid var(--border); border-radius: 16px; box-shadow: 0 1px 2px rgba(0,0,0,.03); min-width: 0; }
        .sal-kpi-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .sal-kpi-icon svg { width: 20px; height: 20px; }
        .sal-kpi-label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
        .sal-kpi-value { margin-top: 3px; font-size: 20px; font-weight: 900; color: var(--dark); letter-spacing: -.3px; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .sal-kpi-sub { margin-top: 4px; font-size: 11.5px; font-weight: 600; color: var(--muted); }
        .sal-kpi-sub.is-wait { color: #8A6100; }
        .sal-kpi-sub.is-ok { color: #207A3A; }

        /* ── Pay period history ── */
        .sal-card { background: var(--white); border: 1px solid var(--border); border-radius: 20px; box-shadow: 0 10px 24px rgba(0,0,0,.05); overflow: hidden; }
        .sal-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--border); }
        .sal-card-title { display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 900; color: var(--dark); }
        .sal-card-title svg { width: 17px; height: 17px; color: var(--muted); }
        .sal-count { font-size: 11.5px; font-weight: 700; color: var(--muted-light, #9ca3af); background: var(--cream-soft); border: 1px solid var(--border); padding: 3px 10px; border-radius: 999px; white-space: nowrap; }
        .sal-scroll { max-height: 460px; overflow-y: auto; }
        #salaryHistoryTable { width: 100%; border-collapse: collapse; }
        #salaryHistoryTable thead th { position: sticky; top: 0; z-index: 1; background: var(--cream-soft); padding: 10px 16px; border-bottom: 1px solid var(--border); font-size: 11px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; color: var(--muted-light, #9ca3af); text-align: left; white-space: nowrap; }
        #salaryHistoryTable td { padding: 13px 16px; border-bottom: 1px solid var(--border); font-size: 13px; color: var(--dark); vertical-align: middle; }
        #salaryHistoryTable tbody tr:last-child td { border-bottom: none; }
        #salaryHistoryTable tbody tr.sal-row { transition: background .15s ease; }
        #salaryHistoryTable tbody tr.sal-row:hover { background: var(--cream-soft); }
        #salaryHistoryTable th.num, #salaryHistoryTable td.num { text-align: right; font-variant-numeric: tabular-nums; }
        #salaryHistoryTable th.ctr, #salaryHistoryTable td.ctr { text-align: center; }
        .sal-period strong { display: block; font-size: 13.5px; font-weight: 800; }
        .sal-period small { display: block; margin-top: 2px; font-size: 11px; color: var(--muted); font-weight: 600; }
        .sal-this-week { display: inline-flex; margin-left: 6px; padding: 2px 8px; border-radius: 999px; background: #EAF0FF; color: #2A4EAA; font-size: 10px; font-weight: 800; vertical-align: 1px; }
        .sal-paid { display: inline-flex; margin-left: 6px; padding: 1px 7px; border-radius: 999px; background: #E7F6EC; color: #207A3A; font-size: 10px; font-weight: 800; }
        .sal-muted { color: var(--muted); }
        .sal-net { font-weight: 900; color: #207A3A; font-size: 14px; }
        .sal-view { display: inline-flex; align-items: center; gap: 6px; height: 32px; padding: 0 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--white); font: inherit; font-size: 12px; font-weight: 800; color: var(--dark); cursor: pointer; transition: border-color .15s, background .15s; }
        .sal-view:hover { border-color: var(--dark); background: var(--cream-soft); }
        .sal-view svg { width: 14px; height: 14px; }
        /* the current week stands out */
        #salaryHistoryTable tbody tr.salary-row-current { background: #F5F8FF; }
        #salaryHistoryTable tbody tr.salary-row-current td:first-child { box-shadow: inset 3px 0 0 #2A4EAA; }
        .sal-empty { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px; padding: 40px 16px; color: var(--muted); }
        .sal-empty-icon { width: 44px; height: 44px; border-radius: 14px; background: var(--cream-soft); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; margin-bottom: 4px; }
        .sal-empty-icon svg { width: 20px; height: 20px; }
        .sal-empty strong { font-size: 14px; color: var(--dark); }

        @media (max-width: 1100px) {
            .sal-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        /* Phones only: each pay period becomes a small card — dates + details on top,
           Rate / Days / OT as tiles, then gross, deductions and net pay. */
        @media (max-width: 640px) {
            .admin-content { padding-bottom: 92px !important; }
            .sal-head { align-items: flex-start; }
            .sal-head h1 { font-size: 22px; }
            .sal-kpis { gap: 10px; }
            .sal-kpi { padding: 12px; }
            .sal-kpi-icon { display: none; }
            .sal-kpi-value { font-size: 16px; }
            .sal-kpi-label { font-size: 10px; }
            .sal-card-head { padding: 14px 16px; }
            .sal-scroll { max-height: none; }

            #salaryHistoryTable, #salaryHistoryTable tbody { display: block; width: 100%; }
            #salaryHistoryTable thead { display: none; }
            #salaryHistoryTable tr.sal-row { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; padding: 14px 16px; border-bottom: 1px solid var(--border); }
            #salaryHistoryTable tr.sal-row td { display: block; padding: 0; border: none; min-width: 0; text-align: left; }
            #salaryHistoryTable tr.sal-row td.sal-period { grid-column: 1 / 3; grid-row: 1; align-self: center; }
            #salaryHistoryTable tr.sal-row td.sal-action { grid-column: 3; grid-row: 1; display: flex; justify-content: flex-end; align-items: center; }
            #salaryHistoryTable tr.sal-row td.sal-tile { grid-row: 2; background: var(--cream-soft); border-radius: 10px; padding: 7px 8px; text-align: center; font-weight: 800; white-space: nowrap; }
            #salaryHistoryTable tr.sal-row td.sal-gross { grid-column: 1; grid-row: 3; }
            #salaryHistoryTable tr.sal-row td.sal-netcell { grid-column: 2 / -1; grid-row: 3; text-align: right; }
            #salaryHistoryTable tr.sal-row td[data-label]::before { content: attr(data-label); display: block; margin-bottom: 2px; font-size: 9px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--muted); }
            #salaryHistoryTable tr.sal-row.salary-row-current { box-shadow: inset 4px 0 0 #2A4EAA; }
            #salaryHistoryTable tr.sal-row.salary-row-current td:first-child { box-shadow: none; }
            .sal-view span { display: none; }
            .sal-view { width: 34px; padding: 0; justify-content: center; }
        }    </style>
</head>
<body class="page-enter">

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

            var dedSection = deductions > 0
                ? detailRow('Deductions', '- ₱' + fmt(deductions), 'color:#dc2626;')
                : '';
            var pmLabel = r.payment_method === 'gcash' ? 'GCash' : 'Cash';

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
                + detailRow('Payment Method', pmLabel)
                + dedSection
                + '<div style="display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-top:2px solid var(--border);background:var(--cream-soft);">'
                    + '<span style="font-size:14px;font-weight:900;color:var(--dark);">GROSS PAY</span>'
                    + '<strong style="font-size:20px;font-weight:900;color:#16a34a;">₱' + fmt(grossPay) + '</strong>'
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
