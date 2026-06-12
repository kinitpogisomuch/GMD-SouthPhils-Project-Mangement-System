<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Salary | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
</head>
<body>

    @include('partials.employee.header')

    <div class="admin-layout">
        @include('partials.employee.sidebar')

        <main class="admin-content">
            <div class="pv-page-header">
                <div>
                    <h1>My Salary</h1>
                    <p>View your weekly pay records and history.</p>
                </div>
            </div>

            <div class="stats-grid">
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
                        <div class="stat-value">{{ number_format($currentRecord->days_worked ?? 0, 1) }}</div>
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
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Pay Period</th>
                                <th>Daily Rate</th>
                                <th>Days Worked</th>
                                <th>Gross Pay</th>
                                <th>Deductions</th>
                                <th>Net Pay</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $record)
                                @php
                                    $weekStart = \Carbon\Carbon::parse($record->pay_period);
                                    $weekEnd   = $weekStart->copy()->addDays(6);
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $weekStart->format('M d') }} – {{ $weekEnd->format('M d, Y') }}</strong>
                                        @if($record->pay_period === $payPeriod)
                                            <span class="status-badge ongoing">This Week</span>
                                        @endif
                                    </td>
                                    <td>₱{{ number_format($record->daily_rate, 2) }}</td>
                                    <td>{{ number_format($record->days_worked, 1) }}</td>
                                    <td>₱{{ number_format($record->gross_pay, 2) }}</td>
                                    <td>₱{{ number_format($record->total_deductions, 2) }}</td>
                                    <td><strong>₱{{ number_format($record->net_pay, 2) }}</strong></td>
                                    <td>{{ $record->notes ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align:center;color:var(--muted);padding:32px 0;">
                                        No salary records yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
