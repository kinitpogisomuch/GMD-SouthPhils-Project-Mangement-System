<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">
            @php
                $adminFullName = session('name', '');
                if (str_contains($adminFullName, ', ')) {
                    $adminFirstName = trim(explode(', ', $adminFullName, 2)[1]);
                } else {
                    $adminFirstName = trim($adminFullName);
                }
                $adminFirstName = $adminFirstName ? explode(' ', $adminFirstName)[0] : 'Admin';

                $hour = (int) now()->format('G');
                $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
            @endphp

            <!-- ── Hero greeting ── -->
            <div class="db-hero">
                <div class="db-hero-left">
                    <div class="db-greeting">{{ $greeting }}, {{ $adminFirstName }}</div>
                    <div class="db-subgreeting">Here's what's happening with your projects today.</div>
                </div>
                <div class="db-hero-meta">
                    <div class="db-hero-date">
                        <i data-lucide="calendar-days"></i>
                        {{ now()->format('l, F j, Y') }}
                    </div>
                </div>
            </div>

            <!-- ── KPI strip ── -->
            <div class="db-kpi-row">

                <a href="{{ route('admin.projects') }}" class="db-kpi-card" style="text-decoration:none;">
                    <div class="db-kpi-icon" style="background:#EAF0FF;color:#2A4EAA;">
                        <i data-lucide="folder-kanban"></i>
                    </div>
                    <div class="db-kpi-body">
                        <div class="db-kpi-value">{{ $totalProjects }}</div>
                        <div class="db-kpi-label">Total Projects</div>
                        <div class="db-kpi-sub">
                            <span style="color:#16a34a;">{{ $activeProjects }} active</span>
                            &nbsp;·&nbsp;
                            <span style="color:#6b7280;">{{ $completedProjects }} done</span>
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.employees') }}" class="db-kpi-card" style="text-decoration:none;">
                    <div class="db-kpi-icon" style="background:#EDE9FE;color:#6D28D9;">
                        <i data-lucide="users"></i>
                    </div>
                    <div class="db-kpi-body">
                        <div class="db-kpi-value">{{ $totalEmployees }}</div>
                        <div class="db-kpi-label">Employees</div>
                        <div class="db-kpi-sub">{{ $totalClients }} clients registered</div>
                    </div>
                </a>

                <a href="{{ route('admin.payments') }}" class="db-kpi-card" style="text-decoration:none;">
                    <div class="db-kpi-icon" style="background:#E7F6EC;color:#207A3A;">
                        <i data-lucide="credit-card"></i>
                    </div>
                    <div class="db-kpi-body">
                        <div class="db-kpi-value">₱{{ number_format($totalReceived, 0) }}</div>
                        <div class="db-kpi-label">Total Received</div>
                        <div class="db-kpi-sub">
                            <span style="color:#16a34a;">{{ $fullyPaidPayments }} fully paid</span>
                            &nbsp;·&nbsp;
                            <span style="color:#e8900a;">{{ $pendingPayments }} pending</span>
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.messages') }}" class="db-kpi-card" style="text-decoration:none;">
                    <div class="db-kpi-icon" style="background:#CCFBF1;color:#0D9488;">
                        <i data-lucide="message-square"></i>
                    </div>
                    <div class="db-kpi-body">
                        <div class="db-kpi-value">{{ $unreadMessages }}</div>
                        <div class="db-kpi-label">Unread Messages</div>
                        <div class="db-kpi-sub">
                            @if($unreadMessages > 0)
                                <span style="color:#e8900a;">Needs your attention</span>
                            @else
                                <span style="color:#16a34a;">All caught up</span>
                            @endif
                        </div>
                    </div>
                </a>

            </div>

            <!-- ── Revenue chart ── -->
            <div class="db-chart-card">
                <div class="db-chart-head">
                    <div>
                        <div class="db-section-title">Revenue Trend</div>
                        <div style="font-size:12px;color:var(--muted-light);font-weight:500;margin-top:2px;">Monthly payments received — last 6 months</div>
                    </div>
                    <div class="db-chart-total">
                        <span style="font-size:11px;font-weight:700;color:var(--muted-light);text-transform:uppercase;letter-spacing:0.4px;">6-Month Total</span>
                        <span style="font-size:18px;font-weight:900;color:var(--dark);">
                            ₱{{ number_format(array_sum(array_column($monthlyRevenue, 'amount')), 0) }}
                        </span>
                    </div>
                </div>
                <div class="db-chart-wrap">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- ── Main content: projects + right panel ── -->
            <div class="db-main-grid">

                <!-- Recent Projects -->
                <div class="db-project-card">
                    <!-- Card header -->
                    <div class="db-project-card-head">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <i data-lucide="folder-kanban" style="width:16px;height:16px;color:var(--muted);"></i>
                            <span class="db-section-title">Recent Projects</span>
                        </div>
                        <span class="db-project-count">{{ $projects->count() }} shown</span>
                    </div>

                    <!-- Table header -->
                    <div class="db-proj-table-head">
                        <span style="flex:1;">Project</span>
                        <span style="width:110px;text-align:center;">Status</span>
                        <span style="width:150px;text-align:right;">Progress</span>
                    </div>

                    <!-- Rows -->
                    <div class="db-project-list">
                        @forelse($projects as $project)
                        @php
                            $progress = $project->progress ?? 0;
                            $status   = strtolower($project->status ?? 'planning');
                            $badgeMap = [
                                'completed' => ['label' => 'Completed', 'css' => 'completed'],
                                'ongoing'   => ['label' => 'Ongoing',   'css' => 'ongoing'],
                                'archived'  => ['label' => 'Archived',  'css' => 'archived'],
                            ];
                            $badge = $badgeMap[$status] ?? ['label' => ucfirst($status), 'css' => 'pending'];
                            $progressColor = $progress >= 100 ? '#16a34a' : ($progress >= 50 ? '#2A4EAA' : 'var(--dark)');
                        @endphp
                        <div class="db-project-row">
                            <div class="db-project-info" style="flex:1;">
                                <div class="db-project-name">{{ $project->name }}</div>
                                <div class="db-project-meta">
                                    <i data-lucide="building-2"></i> {{ $project->client }}
                                    &nbsp;·&nbsp;
                                    <i data-lucide="calendar"></i> {{ $project->start_date->format('M j, Y') }}
                                </div>
                            </div>
                            <div style="width:110px;display:flex;justify-content:center;">
                                <span class="status-badge {{ $badge['css'] }}">{{ $badge['label'] }}</span>
                            </div>
                            <div class="db-progress-wrap" style="width:150px;justify-content:flex-end;">
                                <div class="db-progress-bar">
                                    <div class="db-progress-fill" data-width="{{ $progress }}" data-color="{{ $progressColor }}"></div>
                                </div>
                                <span class="db-progress-label">{{ $progress }}%</span>
                            </div>
                        </div>
                        @empty
                        <div style="text-align:center;padding:40px 20px;color:var(--muted);font-size:14px;">
                            No projects yet.
                        </div>
                        @endforelse
                    </div>

                    <!-- Card footer -->
                    <a href="{{ route('admin.projects') }}" class="db-project-card-footer">
                        <span>View all projects</span>
                        <i data-lucide="arrow-right"></i>
                    </a>
                </div>

                <!-- Right panel -->
                <div class="db-right-panel">

                    <!-- Payment Overview -->
                    <div class="db-panel-card">
                        <div class="db-panel-title">
                            <i data-lucide="bar-chart-2"></i>
                            Payment Overview
                        </div>
                        @php
                            $pct = $totalContractValue > 0
                                ? min(100, round(($totalReceived / $totalContractValue) * 100))
                                : 0;
                        @endphp

                        <!-- Big received number -->
                        <div class="db-pay-big">₱{{ number_format($totalReceived, 0) }}</div>
                        <div class="db-pay-big-label">Total Received</div>

                        <!-- Progress bar -->
                        <div class="db-pay-bar-wrap" style="margin-top:14px;">
                            <div class="db-pay-bar-track">
                                <div class="db-pay-bar-fill" data-width="{{ $pct }}"></div>
                            </div>
                            <span class="db-pay-pct">{{ $pct }}%</span>
                        </div>

                        <!-- Contract total row -->
                        <div class="db-pay-contract-row">
                            <span>Contract Total</span>
                            <span>₱{{ number_format($totalContractValue, 0) }}</span>
                        </div>

                        <!-- Divider -->
                        <div class="db-panel-divider"></div>

                        <!-- Pills -->
                        <div style="display:flex;gap:8px;">
                            <div class="db-pay-pill green">
                                <i data-lucide="check-circle"></i>
                                {{ $fullyPaidPayments }} Fully Paid
                            </div>
                            <div class="db-pay-pill orange">
                                <i data-lucide="clock"></i>
                                {{ $pendingPayments }} Pending
                            </div>
                        </div>

                        <a href="{{ route('admin.payments') }}" class="db-panel-link" style="margin-top:14px;">
                            View payments <i data-lucide="arrow-right"></i>
                        </a>
                    </div>

                    <!-- Materials -->
                    <div class="db-panel-card">
                        <div class="db-panel-title">
                            <i data-lucide="package"></i>
                            Materials
                        </div>

                        <!-- Stat grid -->
                        <div class="db-mat-grid">
                            <div class="db-mat-stat">
                                <div class="db-mat-stat-val">{{ $totalMaterialEntries }}</div>
                                <div class="db-mat-stat-label">Total Entries</div>
                            </div>
                            <div class="db-mat-stat">
                                <div class="db-mat-stat-val">{{ $projectsWithMaterials }}</div>
                                <div class="db-mat-stat-label">Projects</div>
                            </div>
                        </div>

                        <div class="db-panel-divider"></div>

                        <div class="db-mat-cost-row">
                            <span class="db-pay-label">Estimated Cost</span>
                            <span class="db-mat-cost-val">₱{{ number_format($totalMaterialCost, 0) }}</span>
                        </div>

                        <a href="{{ route('admin.material_usage') }}" class="db-panel-link" style="margin-top:14px;">
                            View material usage <i data-lucide="arrow-right"></i>
                        </a>
                    </div>

                    <!-- Top Client -->
                    @if($topClient)
                    <div class="db-panel-card db-top-client-card">
                        <div class="db-panel-title">
                            <i data-lucide="trophy"></i>
                            Top Client
                        </div>

                        <div class="db-tc-identity">
                            <div class="db-tc-avatar">
                                {{ strtoupper(substr($topClient['name'], 0, 1)) }}
                            </div>
                            <div>
                                <div class="db-tc-name">{{ $topClient['name'] }}</div>
                                <div class="db-tc-sub">{{ $topClient['project_count'] }} project{{ $topClient['project_count'] !== 1 ? 's' : '' }} &nbsp;·&nbsp; {{ $topClient['completed'] }} completed</div>
                            </div>
                        </div>

                        <div class="db-panel-divider"></div>

                        <div class="db-mat-grid">
                            <div class="db-mat-stat">
                                <div class="db-mat-stat-val">₱{{ number_format($topClient['contract_value'], 0) }}</div>
                                <div class="db-mat-stat-label">Contract</div>
                            </div>
                            <div class="db-mat-stat">
                                <div class="db-mat-stat-val" style="color:#16a34a;">₱{{ number_format($topClient['received'], 0) }}</div>
                                <div class="db-mat-stat-label">Received</div>
                            </div>
                        </div>

                        <a href="{{ route('admin.clients') }}" class="db-panel-link" style="margin-top:14px;">
                            View all clients <i data-lucide="arrow-right"></i>
                        </a>
                    </div>
                    @endif

                </div>
            </div>

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        document.querySelectorAll('.db-progress-fill[data-width]').forEach(function(el) {
            el.style.width = el.dataset.width + '%';
            if (el.dataset.color) el.style.background = el.dataset.color;
        });
        document.querySelectorAll('.db-pay-bar-fill[data-width]').forEach(function(el) {
            el.style.width = el.dataset.width + '%';
        });

        // Revenue chart
        (function() {
            var labels  = @json(array_column($monthlyRevenue, 'label'));
            var amounts = @json(array_column($monthlyRevenue, 'amount'));

            var ctx = document.getElementById('revenueChart').getContext('2d');

            var gradient = ctx.createLinearGradient(0, 0, 0, 220);
            gradient.addColorStop(0,   'rgba(51,51,51,0.18)');
            gradient.addColorStop(1,   'rgba(51,51,51,0.01)');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Received (₱)',
                        data: amounts,
                        backgroundColor: amounts.map(function(v, i) {
                            return i === amounts.length - 1 ? '#333333' : 'rgba(51,51,51,0.15)';
                        }),
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 52,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    return ' ₱' + ctx.parsed.y.toLocaleString();
                                }
                            },
                            backgroundColor: '#1a1a1a',
                            padding: 10,
                            cornerRadius: 8,
                            titleFont: { weight: '700' },
                            bodyFont:  { weight: '600' },
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: { color: '#999', font: { size: 12, weight: '600' } }
                        },
                        y: {
                            grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false },
                            border: { display: false, dash: [4, 4] },
                            ticks: {
                                color: '#999',
                                font: { size: 12, weight: '600' },
                                callback: function(v) {
                                    return v >= 1000 ? '₱' + (v/1000).toFixed(0) + 'k' : '₱' + v;
                                }
                            }
                        }
                    }
                }
            });
        })();
    </script>

    <style>
        /* ── Hero ── */
        .db-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, var(--dark) 0%, #4a4a4a 100%);
            border-radius: 20px;
            padding: 28px 32px;
            margin-bottom: 24px;
            gap: 16px;
        }
        .db-greeting {
            font-size: 24px;
            font-weight: 900;
            color: #fff;
            letter-spacing: -0.3px;
        }
        .db-subgreeting {
            font-size: 13px;
            color: rgba(255,255,255,0.6);
            margin-top: 4px;
            font-weight: 500;
        }
        .db-hero-date {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 600;
            color: rgba(255,255,255,0.7);
            background: rgba(255,255,255,0.10);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 999px;
            padding: 7px 14px;
            white-space: nowrap;
        }
        .db-hero-date i { width: 14px; height: 14px; }

        /* ── KPI strip ── */
        .db-kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .db-kpi-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            transition: transform 0.18s, box-shadow 0.18s, border-color 0.18s;
            cursor: pointer;
            color: inherit;
        }
        .db-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.09);
            border-color: #d0d0d0;
        }
        .db-kpi-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .db-kpi-icon i { width: 20px; height: 20px; }
        .db-kpi-value {
            font-size: 26px;
            font-weight: 900;
            color: var(--dark);
            letter-spacing: -0.5px;
            line-height: 1;
        }
        .db-kpi-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .db-kpi-sub {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--muted-light);
            margin-top: 5px;
        }

        /* ── Main grid ── */
        .db-main-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
            align-items: start;
        }

        /* ── Section header ── */
        .db-section-title {
            font-size: 15px;
            font-weight: 800;
            color: var(--dark);
        }

        /* ── Project card wrapper ── */
        .db-project-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }
        .db-project-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
        }
        .db-project-count {
            font-size: 11.5px;
            font-weight: 700;
            color: var(--muted-light);
            background: var(--cream-soft);
            border: 1px solid var(--border);
            padding: 3px 10px;
            border-radius: 999px;
        }
        .db-proj-table-head {
            display: flex;
            align-items: center;
            padding: 9px 20px;
            background: var(--cream-soft);
            border-bottom: 1px solid var(--border);
            font-size: 11px;
            font-weight: 800;
            color: var(--muted-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            gap: 16px;
        }

        /* ── Project list rows ── */
        .db-project-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            transition: background 0.15s;
        }
        .db-project-row:last-child { border-bottom: none; }
        .db-project-row:hover { background: var(--cream-soft); }
        .db-project-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 3px;
        }
        .db-project-meta {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 11.5px;
            color: var(--muted-light);
            font-weight: 500;
        }
        .db-project-meta i { width: 11px; height: 11px; }
        .db-progress-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .db-progress-bar {
            width: 90px;
            height: 5px;
            background: var(--cream-deep);
            border-radius: 999px;
            overflow: hidden;
        }
        .db-progress-fill {
            height: 100%;
            border-radius: 999px;
            transition: width 0.4s ease;
        }
        .db-progress-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            width: 30px;
            text-align: right;
        }

        /* ── Card footer ── */
        .db-project-card-footer {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 13px 20px;
            font-size: 13px;
            font-weight: 700;
            color: var(--muted);
            text-decoration: none;
            border-top: 1px solid var(--border);
            background: var(--cream-soft);
            transition: background 0.15s, color 0.15s;
        }
        .db-project-card-footer:hover {
            background: var(--cream-deep);
            color: var(--dark);
        }
        .db-project-card-footer i { width: 14px; height: 14px; }

        /* ── Right panel ── */
        .db-right-panel {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .db-panel-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
        }

        /* Payment overview */
        .db-pay-big {
            font-size: 30px;
            font-weight: 900;
            color: #16a34a;
            letter-spacing: -0.5px;
            line-height: 1;
            margin-top: 6px;
        }
        .db-pay-big-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--muted-light);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-top: 3px;
        }
        .db-pay-contract-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            margin-top: 6px;
        }
        .db-panel-divider {
            height: 1px;
            background: var(--border);
            margin: 14px 0;
        }

        /* Materials */
        .db-mat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 6px;
        }
        .db-mat-stat {
            background: var(--cream-soft);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 14px;
        }
        .db-mat-stat-val {
            font-size: 24px;
            font-weight: 900;
            color: var(--dark);
            letter-spacing: -0.5px;
            line-height: 1;
        }
        .db-mat-stat-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--muted-light);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-top: 4px;
        }
        .db-mat-cost-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .db-mat-cost-val {
            font-size: 16px;
            font-weight: 800;
            color: var(--dark);
        }

        /* Top Client */
        .db-top-client-card {
            background: linear-gradient(160deg, #1a1a1a 0%, #3a3a3a 100%);
            border-color: transparent;
        }
        .db-top-client-card .db-panel-title {
            color: rgba(255,255,255,0.6);
        }
        .db-top-client-card .db-panel-title i {
            color: #f59e0b;
        }
        .db-top-client-card .db-panel-divider {
            background: rgba(255,255,255,0.1);
        }
        .db-top-client-card .db-mat-stat {
            background: rgba(255,255,255,0.08);
            border-color: rgba(255,255,255,0.10);
        }
        .db-top-client-card .db-mat-stat-val {
            color: #fff;
        }
        .db-top-client-card .db-mat-stat-label {
            color: rgba(255,255,255,0.45);
        }
        .db-top-client-card .db-panel-link {
            color: rgba(255,255,255,0.5);
        }
        .db-top-client-card .db-panel-link:hover {
            color: #fff;
        }
        .db-tc-identity {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 4px;
        }
        .db-tc-avatar {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(255,255,255,0.15);
            color: #fff;
            font-size: 18px;
            font-weight: 900;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .db-tc-name {
            font-size: 15px;
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
        }
        .db-tc-sub {
            font-size: 11.5px;
            font-weight: 600;
            color: rgba(255,255,255,0.45);
            margin-top: 3px;
        }
        .db-panel-title {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 14px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .db-panel-title i { width: 15px; height: 15px; color: var(--muted); }
        .db-payment-values {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .db-pay-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--muted-light);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 2px;
        }
        .db-pay-amount {
            font-size: 17px;
            font-weight: 900;
            color: var(--dark);
            letter-spacing: -0.3px;
        }
        .db-pay-bar-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .db-pay-bar-track {
            flex: 1;
            height: 7px;
            background: var(--cream-deep);
            border-radius: 999px;
            overflow: hidden;
        }
        .db-pay-bar-fill {
            height: 100%;
            background: #16a34a;
            border-radius: 999px;
            transition: width 0.5s ease;
        }
        .db-pay-pct {
            font-size: 12px;
            font-weight: 800;
            color: var(--muted);
            width: 34px;
            text-align: right;
        }
        .db-pay-pill {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            font-size: 11.5px;
            font-weight: 700;
            padding: 7px 10px;
            border-radius: 10px;
        }
        .db-pay-pill i { width: 13px; height: 13px; }
        .db-pay-pill.green  { background: #dcfce7; color: #15803d; }
        .db-pay-pill.orange { background: #fff7ed; color: #c2410c; }
        .db-panel-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            text-decoration: none;
            margin-top: 14px;
            transition: color 0.15s;
        }
        .db-panel-link:hover { color: var(--dark); }
        .db-panel-link i { width: 13px; height: 13px; }


        /* ── Revenue chart ── */
        .db-chart-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px 24px 24px;
            margin-bottom: 20px;
        }
        .db-chart-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 16px;
        }
        .db-chart-total {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 2px;
        }
        .db-chart-wrap {
            height: 220px;
            position: relative;
        }

        /* ── Responsive ── */
        @media (max-width: 1100px) {
            .db-kpi-row { grid-template-columns: repeat(2, 1fr); }
            .db-main-grid { grid-template-columns: 1fr; }
            .db-right-panel { display: grid; grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 640px) {
            .db-hero { flex-direction: column; align-items: flex-start; }
            .db-kpi-row { grid-template-columns: 1fr 1fr; }
            .db-right-panel { grid-template-columns: 1fr; }
            .db-project-right { display: none; }
        }
    </style>

</body>
</html>
