<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.client.header')

    <main class="admin-content">
            @php
                $clientFullName = session('full_name', 'Client');
                if (str_contains($clientFullName, ', ')) {
                    $clientFirstName = trim(explode(', ', $clientFullName, 2)[1]);
                } else {
                    $clientFirstName = trim($clientFullName);
                }
                $clientFirstName = $clientFirstName ? explode(' ', $clientFirstName)[0] : 'Client';

                $hour = (int) now()->timezone('Asia/Manila')->format('G');
                $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
            @endphp

            <!-- ── Hero greeting ── -->
            <div class="db-hero">
                <div class="db-hero-left">
                    <div>
                        <div class="db-greeting">{{ $greeting }}, {{ $clientFirstName }}</div>
                        <div class="db-subgreeting">Here's an overview of your projects and payments.</div>
                    </div>
                </div>
                <div class="db-hero-meta">
                    <div class="db-hero-date">
                        <i data-lucide="calendar-days"></i>
                        {{ now()->format('l, F j, Y') }}
                    </div>
                </div>
            </div>

            <style>
                .db-hero {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 4px 4px 24px;
                    margin-bottom: 20px;
                    border-bottom: 1px solid var(--border);
                    gap: 16px;
                }
                .db-hero-left {
                    display: flex;
                    align-items: center;
                    gap: 16px;
                }
                .db-greeting {
                    font-size: 24px;
                    font-weight: 900;
                    color: var(--dark);
                    letter-spacing: -0.3px;
                }
                .db-subgreeting {
                    font-size: 13px;
                    color: var(--muted);
                    margin-top: 4px;
                    font-weight: 500;
                }
                .db-hero-date {
                    display: flex;
                    align-items: center;
                    gap: 7px;
                    font-size: 13px;
                    font-weight: 600;
                    color: var(--muted);
                    background: var(--white);
                    border: 1px solid var(--border);
                    border-radius: 999px;
                    padding: 7px 14px;
                    white-space: nowrap;
                    box-shadow: 0 4px 12px rgba(0,0,0,.05);
                }
                .db-hero-date i { width: 14px; height: 14px; }
                @media (max-width: 768px) {
                    .db-hero { flex-direction: column; align-items: flex-start; padding: 4px 4px 20px; gap: 12px; }
                }

                /* Recent Projects / Recent Payments: show about 3 rows, scroll for the rest
                   instead of the card growing tall once there's more than a few entries. */
                .dash-recent-scroll {
                    max-height: 260px;
                    overflow-y: auto;
                }
                .dash-recent-scroll thead th {
                    position: sticky;
                    top: 0;
                    z-index: 1;
                }
                .dash-mini-bar { display: none; }

                /* Phones: each row becomes a small stacked card — full project name on top,
                   progress / amount and status side by side underneath. Desktop keeps the table. */
                @media (max-width: 640px) {
                    .dash-recent-scroll { max-height: 340px; }
                    .dash-recent-table, .dash-recent-table tbody { display: block; width: 100%; }
                    .dash-recent-table colgroup, .dash-recent-table thead { display: none; }
                    .dash-recent-table tr {
                        display: grid;
                        grid-template-columns: minmax(0, 1fr) auto;
                        align-items: center;
                        gap: 10px 12px;
                        padding: 14px 18px;
                        border-bottom: 1px solid var(--border);
                    }
                    .dash-recent-table tr:last-child { border-bottom: none; }
                    .dash-recent-table td { display: block; padding: 0 !important; border: none !important; min-width: 0; }
                    .dash-recent-table td:first-child { grid-column: 1 / -1; }
                    .dash-recent-table td:first-child strong { font-size: 14px; line-height: 1.35; word-break: normal; overflow-wrap: anywhere; }
                    .dash-recent-table td[data-label]:not([data-label="Status"]) {
                        text-align: left !important;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        padding-left: 46px !important;   /* lines up under the project name, past the icon */
                    }
                    .dash-recent-table td[data-label]:not([data-label="Status"])::before {
                        content: attr(data-label);
                        font-size: 10px;
                        font-weight: 800;
                        letter-spacing: .06em;
                        text-transform: uppercase;
                        color: var(--muted);
                    }
                    .dash-recent-table td[data-label="Status"] { text-align: right !important; }
                    .dash-recent-table td[data-label="Status"] .status-badge { white-space: nowrap; }
                    .dash-recent-table td[data-label="Progress"] strong { font-size: 13px; }
                    .dash-mini-bar {
                        display: block;
                        flex: 1;
                        max-width: 90px;
                        height: 6px;
                        border-radius: 999px;
                        background: var(--border);
                        overflow: hidden;
                    }
                    .dash-mini-bar span { display: block; height: 100%; border-radius: 999px; background: var(--dark); }
                    .dash-recent-table td[colspan] { grid-column: 1 / -1; text-align: center !important; padding: 8px 0 !important; }
                }
            </style>

            <div class="stats-grid">
                <div class="stat-card teal">
                    <div class="stat-icon teal"><i data-lucide="folder-open"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $projects->count() }}</div>
                        <div class="stat-label">Active Projects</div>
                        <div class="stat-change up"><i data-lucide="trending-up"></i> Your projects</div>
                    </div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-icon blue"><i data-lucide="check-circle"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $projects->avg('progress') ? round($projects->avg('progress')) : 0 }}%</div>
                        <div class="stat-label">Overall Progress</div>
                        <div class="stat-change up"><i data-lucide="trending-up"></i> Average completion</div>
                    </div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-icon orange"><i data-lucide="credit-card"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱{{ number_format($payments->sum('contract_amount')) }}</div>
                        <div class="stat-label">Total Contract Value</div>
                        <div class="stat-change up"><i data-lucide="trending-up"></i> All projects</div>
                    </div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon green"><i data-lucide="file-text"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $payments->count() }}</div>
                        <div class="stat-label">Payment Records</div>
                        <div class="stat-change up"><i data-lucide="trending-up"></i> Total invoices</div>
                    </div>
                </div>
            </div>

            <div class="grid-2">
                <!-- Active Projects -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Recent Projects</span>
                        <a href="{{ url('/client/projects') }}" class="btn btn-outline btn-sm">
                            <i data-lucide="arrow-right"></i> View All
                        </a>
                    </div>
                    <div class="table-wrap dash-recent-scroll">
                        <table class="dash-recent-table" style="table-layout:fixed;">
                            <colgroup>
                                <col style="width:34%;">
                                <col style="width:33%;">
                                <col style="width:33%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th style="text-align:center;">Progress</th>
                                    <th style="text-align:center;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $project)
                                @php
                                    [$iconBg, $iconColor, $icon] = match($project->status) {
                                        'completed' => ['#E7F6EC', '#207A3A', 'check-circle'],
                                        'delayed'   => ['#FEE4E2', '#B42318', 'alert-triangle'],
                                        'ongoing'   => ['#EAF0FF', '#2A4EAA', 'loader'],
                                        default     => ['#FFF3D6', '#8A6100', 'clock'],
                                    };
                                @endphp
                                <tr onclick="window.location='{{ route('client.project_view', $project->id) }}'" style="cursor:pointer;">
                                    <td>
                                        <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                                            <div class="dash-row-icon" style="width:34px;height:34px;border-radius:50%;background:{{ $iconBg }};color:{{ $iconColor }};flex-shrink:0;">
                                                <i data-lucide="{{ $icon }}" style="width:16px;height:16px;"></i>
                                            </div>
                                            <strong style="word-break:break-word;">{{ $project->name }}</strong>
                                        </div>
                                    </td>
                                    <td style="text-align:center;" data-label="Progress">
                                        <strong>{{ $project->progress }}%</strong>
                                        <span class="dash-mini-bar" aria-hidden="true"><span style="width:{{ max(0, min(100, (int) $project->progress)) }}%;"></span></span>
                                    </td>
                                    <td style="text-align:center;" data-label="Status">
                                        @if($project->status === 'ongoing')
                                            <span class="status-badge ongoing">In Progress</span>
                                        @elseif($project->status === 'completed')
                                            <span class="status-badge completed">Completed</span>
                                        @elseif($project->status === 'delayed')
                                            <span class="status-badge revision">Delayed</span>
                                        @else
                                            <span class="status-badge pending">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" style="text-align:center;color:var(--text-secondary);">No projects found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Payments -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Recent Payments</span>
                        <a href="{{ route('client.payments') }}" class="btn btn-outline btn-sm">
                            <i data-lucide="arrow-right"></i> View All
                        </a>
                    </div>
                    <div class="table-wrap dash-recent-scroll">
                        <table class="dash-recent-table" style="table-layout:fixed;">
                            <colgroup>
                                <col style="width:34%;">
                                <col style="width:33%;">
                                <col style="width:33%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th style="text-align:center;">Amount</th>
                                    <th style="text-align:center;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                @php
                                    $payBadgeClass = \App\Models\Payment::statusBadgeClass($payment->status);
                                    [$payIconBg, $payIconColor, $payIcon] = match($payBadgeClass) {
                                        'completed' => ['#E7F6EC', '#207A3A', 'check-circle'],
                                        'ongoing'   => ['#EAF0FF', '#2A4EAA', 'loader'],
                                        default     => ['#FFF3D6', '#8A6100', 'clock'],
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                                            <div class="dash-row-icon" style="width:34px;height:34px;border-radius:50%;background:{{ $payIconBg }};color:{{ $payIconColor }};flex-shrink:0;">
                                                <i data-lucide="{{ $payIcon }}" style="width:16px;height:16px;"></i>
                                            </div>
                                            <strong style="word-break:break-word;">{{ $payment->project->name ?? '—' }}</strong>
                                        </div>
                                    </td>
                                    <td style="text-align:center;" data-label="Contract">
                                        <strong>₱{{ number_format($payment->contract_amount) }}</strong>
                                    </td>
                                    <td style="text-align:center;" data-label="Status">
                                        <span class="status-badge {{ $payBadgeClass }}">
                                            {{ $payment->status }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" style="text-align:center;color:var(--text-secondary);">No payments found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/client.js') }}"></script>
</body>
</html>