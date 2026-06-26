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

            {{-- ── Year filter ── --}}
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
                <span style="font-size:13px;font-weight:700;color:var(--muted);">Filter by year:</span>
                @php $currentYear = now()->year; @endphp
                @foreach([$currentYear-1, $currentYear] as $yr)
                <button type="button" class="db-year-btn {{ $yr == $currentYear ? 'active' : '' }}" data-year="{{ $yr }}">
                    {{ $yr }}
                </button>
                @endforeach
            </div>

            {{-- ── Top Client + Top Supplier ── --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

                {{-- Top Client --}}
                <div style="background:linear-gradient(135deg,#0E1428 0%,#1a2340 100%);border-radius:18px;padding:24px;box-shadow:0 8px 24px rgba(14,20,40,.25);">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;border-bottom:1px solid rgba(255,255,255,.08);padding-bottom:14px;">
                        <div style="width:36px;height:36px;background:rgba(255,255,255,.1);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="crown" style="width:18px;height:18px;color:#FDE74C;"></i>
                        </div>
                        <div>
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:rgba(255,255,255,.4);">Top Client</div>
                            <div style="font-size:13px;font-weight:800;color:#fff;">By number of projects</div>
                        </div>
                    </div>
                    @if($topClient)
                    <div style="font-size:22px;font-weight:900;color:#fff;margin-bottom:12px;">{{ $topClient['name'] }}</div>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
                        <div style="background:rgba(255,255,255,.06);border-radius:10px;padding:10px 12px;text-align:center;">
                            <div style="font-size:18px;font-weight:900;color:#FDE74C;">{{ $topClient['project_count'] }}</div>
                            <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;margin-top:2px;">Projects</div>
                        </div>
                        <div style="background:rgba(255,255,255,.06);border-radius:10px;padding:10px 12px;text-align:center;">
                            <div style="font-size:18px;font-weight:900;color:#4ade80;">{{ $topClient['completed'] }}</div>
                            <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;margin-top:2px;">Completed</div>
                        </div>
                        <div style="background:rgba(255,255,255,.06);border-radius:10px;padding:10px 12px;text-align:center;">
                            <div style="font-size:14px;font-weight:900;color:#60a5fa;">₱{{ number_format($topClient['received'] / 1000, 0) }}k</div>
                            <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;margin-top:2px;">Received</div>
                        </div>
                    </div>
                    @else
                    <div style="color:rgba(255,255,255,.3);font-size:13px;text-align:center;padding:20px 0;">No client data yet.</div>
                    @endif
                </div>

                {{-- Top Supplier --}}
                <div style="background:linear-gradient(135deg,#333333 0%,#2a2a2a 100%);border-radius:18px;padding:24px;box-shadow:0 8px 24px rgba(0,0,0,.2);">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;border-bottom:1px solid rgba(255,255,255,.08);padding-bottom:14px;">
                        <div style="width:36px;height:36px;background:rgba(255,255,255,.1);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="truck" style="width:18px;height:18px;color:#10B981;"></i>
                        </div>
                        <div>
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:rgba(255,255,255,.4);">Top Supplier</div>
                            <div style="font-size:13px;font-weight:800;color:#fff;">By total materials purchased</div>
                        </div>
                    </div>
                    @if($topSupplier)
                    <div style="font-size:22px;font-weight:900;color:#fff;margin-bottom:12px;">{{ $topSupplier['name'] }}</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div style="background:rgba(255,255,255,.06);border-radius:10px;padding:10px 12px;text-align:center;">
                            <div style="font-size:18px;font-weight:900;color:#10B981;">{{ $topSupplier['purchase_count'] }}</div>
                            <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;margin-top:2px;">Purchases</div>
                        </div>
                        <div style="background:rgba(255,255,255,.06);border-radius:10px;padding:10px 12px;text-align:center;">
                            <div style="font-size:14px;font-weight:900;color:#4ade80;">₱{{ number_format($topSupplier['total_spent'], 2) }}</div>
                            <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;margin-top:2px;">Total Spent</div>
                        </div>
                    </div>
                    @else
                    <div style="color:rgba(255,255,255,.3);font-size:13px;text-align:center;padding:20px 0;">No supplier data yet.</div>
                    @endif
                </div>
            </div>

            {{-- ── Peak Months Line Chart ── --}}
            <div class="table-card" style="margin-bottom:24px;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px 16px;border-bottom:1px solid var(--border);">
                    <div>
                        <div style="font-size:15px;font-weight:800;color:var(--dark);">Peak Months</div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;">Monthly revenue trend — payments received</div>
                    </div>
                    <span id="peakYearLabel" style="font-size:13px;font-weight:700;color:var(--muted);"></span>
                </div>
                <div style="padding:20px 24px;">
                    <canvas id="peakMonthsChart" height="90"></canvas>
                </div>
            </div>

            {{-- sections removed per user request --}}
            <div class="db-outer-grid" style="display:none;">

            <!-- Left: KPI stack -->
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

                <!-- ── System Stats card ── -->
                @php $estimatedProfit = max(0, $totalContractValue - $totalMaterialCost); @endphp
                <div class="db-kpi-card db-kpi-stats" style="flex-direction:column;align-items:stretch;gap:0;padding:18px;cursor:default;transform:none !important;box-shadow:none !important;">
                    <div style="font-size:10px;font-weight:700;color:var(--muted-light);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;display:flex;align-items:center;gap:5px;">
                        <i data-lucide="bar-chart-2" style="width:12px;height:12px;"></i>
                        System Stats
                    </div>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:11px;color:var(--muted);">Contract Value</span>
                            <span style="font-size:12px;font-weight:700;color:var(--dark);">₱{{ number_format($totalContractValue, 0) }}</span>
                        </div>
                        <div style="height:1px;background:var(--border);"></div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:11px;color:var(--muted);">Est. Profit</span>
                            <span style="font-size:12px;font-weight:700;color:#16a34a;">₱{{ number_format($estimatedProfit, 0) }}</span>
                        </div>
                        <div style="height:1px;background:var(--border);"></div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:11px;color:var(--muted);">Material Cost</span>
                            <span style="font-size:12px;font-weight:700;color:var(--dark);">₱{{ number_format($totalMaterialCost, 0) }}</span>
                        </div>
                        <div style="height:1px;background:var(--border);"></div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:11px;color:var(--muted);">Total Received</span>
                            <span style="font-size:12px;font-weight:700;color:#16a34a;">₱{{ number_format($totalReceived, 0) }}</span>
                        </div>
                    </div>
                </div>

            </div><!-- end db-kpi-row -->

            <!-- Right: Three-panel column -->
            <div>

            <!-- ── Three-card horizontal row moved here ── -->
            <div class="db-chart-panels-grid">

            <div class="db-chart-card" style="margin-bottom:0;">
                <div class="db-chart-head">
                    <div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div class="db-section-title">Revenue Trend</div>
                            <button id="chartBackBtn" style="display:none;font-size:11px;font-weight:600;color:var(--muted);background:none;border:1px solid var(--border);border-radius:6px;padding:2px 8px;cursor:pointer;">← Back</button>
                        </div>
                        <div id="chartSubtitle" style="font-size:12px;color:var(--muted-light);font-weight:500;margin-top:2px;">Monthly payments received — last 6 months</div>
                        <div id="chartDrillHint" style="font-size:11px;color:var(--muted-light);margin-top:2px;">Click a bar to see weekly breakdown</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <!-- Filter buttons -->
                        <div class="db-chart-filters">
                            <button class="db-chart-filter-btn" data-range="1">1M</button>
                            <button class="db-chart-filter-btn" data-range="3">3M</button>
                            <button class="db-chart-filter-btn active" data-range="6">6M</button>
                            <button class="db-chart-filter-btn" data-range="12">12M</button>
                            <button class="db-chart-filter-btn" data-range="year">This Year</button>
                        </div>
                        <div class="db-chart-total">
                            <span style="font-size:11px;font-weight:700;color:var(--muted-light);text-transform:uppercase;letter-spacing:0.4px;" id="chartTotalLabel">6-Month Total</span>
                            <span style="font-size:18px;font-weight:900;color:var(--dark);" id="chartTotalValue">
                                ₱{{ number_format(array_sum(array_column(array_slice($monthlyRevenue, -6), 'amount')), 0) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="db-chart-wrap">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Donut chart: Project Status Breakdown -->
            <div class="db-panel-card" style="display:flex;flex-direction:column;">
                <div class="db-panel-title">
                    <i data-lucide="pie-chart"></i>
                    Project Status Breakdown
                </div>
                <div style="display:flex;align-items:center;justify-content:center;flex:1;gap:20px;flex-wrap:wrap;">
                    <div style="position:relative;width:140px;height:140px;flex-shrink:0;">
                        <canvas id="statusDonutChart"></canvas>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        @foreach($projectStatusChart as $item)
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;min-width:110px;">
                            <div style="display:flex;align-items:center;gap:7px;">
                                <span style="width:9px;height:9px;border-radius:50%;background:{{ $item['color'] }};display:inline-block;flex-shrink:0;"></span>
                                <span style="font-size:12px;color:var(--muted);">{{ $item['label'] }}</span>
                            </div>
                            <span style="font-size:13px;font-weight:800;color:var(--dark);">{{ $item['count'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            </div><!-- end grid wrapper -->

            <!-- ── Three-card horizontal row ── -->
            <div class="db-three-panel-row">

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

            </div><!-- end db-three-panel-row -->

            </div><!-- end right column -->
            </div><!-- end kpi + three-panel wrapper -->

            <!-- ── Main content: projects (full width) ── -->
            <div class="db-main-grid" style="display:none;">

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
                        <span style="flex:1;padding-left:17px;">Project</span>
                        <span style="margin-right:auto;">Status</span>
                        <span style="padding-right:20px;">Progress</span>
                    </div>

                    <!-- Rows -->
                    <div class="db-project-list">
                        @forelse($projects as $project)
                        @php
                            $progress = $project->progress ?? 0;
                            $status   = strtolower($project->status ?? 'planning');
                            $badgeMap = [
                                'completed' => ['label' => 'Completed', 'css' => 'completed',  'color' => '#16a34a'],
                                'ongoing'   => ['label' => 'Ongoing',   'css' => 'ongoing',    'color' => '#2A4EAA'],
                                'archived'  => ['label' => 'Archived',  'css' => 'archived',   'color' => '#94a3b8'],
                            ];
                            $badge = $badgeMap[$status] ?? ['label' => ucfirst($status), 'css' => 'pending', 'color' => '#e8900a'];
                            $progressColor = $progress >= 100 ? '#16a34a' : ($progress >= 50 ? '#2A4EAA' : '#e8900a');
                        @endphp
                        <a href="{{ route('admin.project_view', $project->id) }}" class="db-project-row" style="text-decoration:none;color:inherit;">
                            <!-- Status accent strip -->
                            <div class="db-row-accent" style="background:{{ $badge['color'] }};"></div>

                            <div class="db-project-info" style="flex:1;min-width:0;">
                                <div class="db-project-name-row">
                                    <span class="project-code-badge db-code-sm">{{ $project->code }}</span>
                                    <span class="db-project-name">{{ $project->name }}</span>
                                </div>
                                <div class="db-project-meta">
                                    {{ $project->client }}
                                    <span class="db-meta-sep">·</span>
                                    {{ $project->start_date->format('M j, Y') }}
                                </div>
                            </div>

                            <div class="db-row-right">
                                <span class="status-badge {{ $badge['css'] }}">{{ $badge['label'] }}</span>
                                <div class="db-progress-wrap">
                                    <div class="db-progress-bar">
                                        <div class="db-progress-fill" data-width="{{ $progress }}" data-color="{{ $progressColor }}"></div>
                                    </div>
                                    <span class="db-progress-label">{{ $progress }}%</span>
                                </div>
                            </div>
                        </a>
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

            </div><!-- end db-main-grid -->

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

        // Revenue chart with filters
        (function() {
            // All data sets
            var all12Labels   = @json(array_column($monthlyRevenue, 'label'));
            var all12Amounts  = @json(array_column($monthlyRevenue, 'amount'));
            var yearLabels    = @json(array_column($yearlyRevenue, 'label'));
            var yearAmounts   = @json(array_column($yearlyRevenue, 'amount'));
            var weeklyLabels  = @json(array_column($weeklyRevenue, 'label'));
            var weeklyAmounts = @json(array_column($weeklyRevenue, 'amount'));

            var ctx         = document.getElementById('revenueChart').getContext('2d');
            var subtitleEl  = document.getElementById('chartSubtitle');
            var totalLblEl  = document.getElementById('chartTotalLabel');
            var totalValEl  = document.getElementById('chartTotalValue');
            var backBtn     = document.getElementById('chartBackBtn');
            var drillHint   = document.getElementById('chartDrillHint');
            var WEEKLY_URL  = '{{ route("admin.revenue.weekly") }}';
            var CSRF        = '{{ csrf_token() }}';

            // Track drill-down state
            var drillActive    = false;
            var lastRange      = '6';
            var lastFilterMeta = null;

            function makeColors(data, highlight) {
                return data.map(function(v, i) {
                    if (highlight !== undefined) return i === highlight ? '#333333' : 'rgba(51,51,51,0.12)';
                    return i === data.length - 1 ? '#333333' : 'rgba(51,51,51,0.15)';
                });
            }

            function formatPHP(v) {
                return '₱' + Math.round(v).toLocaleString();
            }

            function updateChart(labels, amounts, subtitle, totalLabel) {
                chart.data.labels                      = labels;
                chart.data.datasets[0].data            = amounts;
                chart.data.datasets[0].backgroundColor = makeColors(amounts);
                chart.update();
                subtitleEl.textContent = subtitle;
                totalLblEl.textContent = totalLabel;
                var total = amounts.reduce(function(s, v) { return s + v; }, 0);
                totalValEl.textContent = formatPHP(total);
            }

            function enterDrill(monthLabel, labels, amounts) {
                drillActive = true;
                backBtn.style.display  = '';
                drillHint.style.display = 'none';
                document.querySelectorAll('.db-chart-filter-btn').forEach(function(b) { b.disabled = true; b.style.opacity = '.4'; });
                updateChart(labels, amounts, 'Weekly breakdown — ' + monthLabel, monthLabel + ' Total');
                chart.data.datasets[0].backgroundColor = makeColors(amounts);
                chart.update();
            }

            function exitDrill() {
                drillActive = false;
                backBtn.style.display   = 'none';
                drillHint.style.display = '';
                document.querySelectorAll('.db-chart-filter-btn').forEach(function(b) { b.disabled = false; b.style.opacity = ''; });
                if (lastFilterMeta) updateChart(lastFilterMeta.labels, lastFilterMeta.amounts, lastFilterMeta.subtitle, lastFilterMeta.totalLabel);
            }

            var chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: all12Labels.slice(-6),
                    datasets: [{
                        label: 'Received (₱)',
                        data: all12Amounts.slice(-6),
                        backgroundColor: makeColors(all12Amounts.slice(-6)),
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 52,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cursor: 'pointer',
                    onClick: function(e, elements) {
                        // Only drill down when viewing monthly data (not already drilled)
                        if (drillActive || lastRange === '1') return;
                        if (!elements || elements.length === 0) return;

                        var idx       = elements[0].index;
                        var label     = chart.data.labels[idx]; // e.g. "Jun 2026"
                        var parts     = label.split(' ');        // ["Jun", "2026"]
                        if (parts.length < 2) return;

                        var monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                        var monthNum   = monthNames.indexOf(parts[0]) + 1;
                        var year       = parseInt(parts[1]);
                        if (!monthNum || !year) return;

                        // Highlight clicked bar while loading
                        chart.data.datasets[0].backgroundColor = makeColors(chart.data.datasets[0].data, idx);
                        chart.update();

                        fetch(WEEKLY_URL + '?year=' + year + '&month=' + monthNum, {
                            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            enterDrill(data.month, data.labels, data.amounts);
                        });
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(c) { return ' ₱' + c.parsed.y.toLocaleString(); },
                                afterTitle: function() { return drillActive ? '' : 'Click to see weekly'; }
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

            // Back button
            backBtn.addEventListener('click', exitDrill);

            var filterMeta = {
                '1':    { labels: weeklyLabels, amounts: weeklyAmounts, subtitle: 'Weekly payments received — {{ now()->format("F Y") }}', totalLabel: '{{ now()->format("M Y") }} Total' },
                '3':    { labels: all12Labels.slice(-3),  amounts: all12Amounts.slice(-3),  subtitle: 'Monthly payments received — last 3 months',  totalLabel: '3-Month Total' },
                '6':    { labels: all12Labels.slice(-6),  amounts: all12Amounts.slice(-6),  subtitle: 'Monthly payments received — last 6 months',  totalLabel: '6-Month Total' },
                '12':   { labels: all12Labels.slice(-12), amounts: all12Amounts.slice(-12), subtitle: 'Monthly payments received — last 12 months', totalLabel: '12-Month Total' },
                'year': { labels: yearLabels,             amounts: yearAmounts,             subtitle: 'Monthly payments received — {{ now()->year }}', totalLabel: '{{ now()->year }} Total' },
            };

            document.querySelectorAll('.db-chart-filter-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var range = this.dataset.range;
                    var meta  = filterMeta[range];
                    if (!meta) return;

                    lastRange      = range;
                    lastFilterMeta = meta;
                    drillActive    = false;
                    backBtn.style.display   = 'none';
                    drillHint.style.display = range === '1' ? 'none' : '';

                    document.querySelectorAll('.db-chart-filter-btn').forEach(function(b) { b.classList.remove('active'); });
                    this.classList.add('active');

                    updateChart(meta.labels, meta.amounts, meta.subtitle, meta.totalLabel);
                });
            });

            // Set initial state
            lastFilterMeta = filterMeta['6'];
        })();

        // ── Project Status Donut ──
        (function () {
            var data   = @json($projectStatusChart);
            var labels = data.map(function(d){ return d.label; });
            var counts = data.map(function(d){ return d.count; });
            var colors = data.map(function(d){ return d.color; });

            new Chart(document.getElementById('statusDonutChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: counts,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(c) {
                                    return '  ' + c.label + ': ' + c.parsed + ' project' + (c.parsed !== 1 ? 's' : '');
                                }
                            },
                            backgroundColor: '#1a1a1a',
                            padding: 10,
                            cornerRadius: 8,
                            titleFont: { weight: '700' },
                            bodyFont:  { weight: '500' },
                        }
                    }
                }
            });
        })();

        // ── Peak Months Line Chart ────────────────────────────────────────
        var peakData = @json($peakMonthsData);
        var currentYear = {{ now()->year }};
        var peakChart;

        function buildPeakChart(year) {
            var data = peakData[year] || [];
            var labels  = data.map(function(d) { return d.label; });
            var amounts = data.map(function(d) { return d.amount; });

            document.getElementById('peakYearLabel').textContent = year;

            var ctx = document.getElementById('peakMonthsChart').getContext('2d');
            if (peakChart) peakChart.destroy();

            peakChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: amounts,
                        fill: true,
                        backgroundColor: 'rgba(16,185,129,.08)',
                        borderColor: '#10B981',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#10B981',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) { return '₱' + ctx.parsed.y.toLocaleString('en-PH', {minimumFractionDigits:2}); }
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 12, weight: '700' } } },
                        y: {
                            grid: { color: 'rgba(0,0,0,.05)' },
                            ticks: {
                                callback: function(v) { return v >= 1000 ? '₱' + (v/1000).toFixed(0) + 'k' : '₱' + v; },
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });
        }

        buildPeakChart(currentYear);

        // Year filter buttons
        document.querySelectorAll('.db-year-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.db-year-btn').forEach(function(b) { b.classList.remove('active'); });
                this.classList.add('active');
                buildPeakChart(parseInt(this.dataset.year));
            });
        });
        // ─────────────────────────────────────────────────────────────────
    </script>

    <style>
        .db-year-btn {
            padding: 6px 16px;
            border-radius: 999px;
            border: 1.5px solid var(--border);
            background: var(--white);
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all .18s;
        }
        .db-year-btn:hover { border-color: var(--dark); color: var(--dark); }
        .db-year-btn.active {
            background: var(--dark);
            border-color: var(--dark);
            color: #fff;
        }

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
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 0;
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

        /* ── Main grid (recent projects full width) ── */
        .db-main-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        /* ── Three-card horizontal row ── */
        .db-three-panel-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            align-items: stretch;
        }
        .db-three-panel-row .db-panel-card,
        .db-three-panel-row .db-top-client-card {
            height: 100%;
            box-sizing: border-box;
        }

        /* ── Recent projects grid (below three-card row) ── */
        .db-main-grid {
            margin-top: 20px;
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
            gap: 0;
            border-bottom: 1px solid var(--border);
            transition: background 0.15s;
            overflow: hidden;
            cursor: pointer;
        }
        .db-project-row:last-child { border-bottom: none; }
        .db-project-row:hover { background: var(--cream-soft); }

        /* Colored left accent strip */
        .db-row-accent {
            width: 3px;
            align-self: stretch;
            flex-shrink: 0;
            border-radius: 0;
            opacity: 0.7;
        }

        /* Info section */
        .db-project-info { padding: 14px 16px 14px 14px; }

        .db-project-name-row {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 4px;
        }
        .db-code-sm {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            flex-shrink: 0;
        }
        .db-project-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .db-project-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            color: var(--muted-light);
            font-weight: 500;
        }
        .db-meta-sep {
            color: var(--border);
            font-size: 13px;
        }

        /* Right section: status + progress */
        .db-row-right {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 20px 14px 0;
            flex-shrink: 0;
        }
        .db-progress-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .db-progress-bar {
            width: 90px;
            height: 6px;
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
        .db-chart-filters {
            display: flex;
            gap: 4px;
            background: #e8e8e8;
            border-radius: 10px;
            padding: 3px;
        }
        .db-chart-filter-btn {
            background: transparent;
            border: none;
            border-radius: 7px;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 600;
            color: #888;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }
        .db-chart-filter-btn:hover {
            color: #333;
        }
        .db-chart-filter-btn.active {
            background: #fff;
            color: #111;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        }
        .db-chart-wrap {
            height: 220px;
            position: relative;
        }

        /* ── Outer layout grids (must be classes — inline styles block @media) ── */
        .db-outer-grid {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 20px;
            margin-bottom: 20px;
            align-items: start;
        }
        .db-chart-panels-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        /* Grid children must shrink, not overflow */
        .db-outer-grid > *,
        .db-chart-panels-grid > *,
        .db-three-panel-row > *,
        .db-kpi-row > * { min-width: 0; }

        /* Prevent page-level horizontal bleed */
        .admin-content { overflow-x: hidden; }

        /* ── Responsive ── */

        /* Tablet (≤1100px): collapse KPI sidebar, stack chart+donut, 3-panel → 2-col */
        @media (max-width: 1100px) {
            .db-outer-grid         { grid-template-columns: 1fr; }
            .db-kpi-row            { grid-template-columns: repeat(2, 1fr); }
            .db-kpi-stats          { grid-column: 1 / -1; }
            .db-chart-panels-grid  { grid-template-columns: 1fr; }
            .db-three-panel-row    { grid-template-columns: repeat(2, 1fr); }
            .db-chart-head         { flex-wrap: wrap; gap: 10px; }
            .db-chart-total        { align-items: flex-start; }
            .db-chart-filters      { order: 3; flex: 1 1 100%; }
        }

        /* Small tablet (≤768px): hero stacks, 3-panel → 1-col, hide table columns */
        @media (max-width: 768px) {
            .db-hero               { flex-direction: column; align-items: flex-start; padding: 18px 20px; gap: 12px; }
            .db-greeting           { font-size: 20px; }
            .db-three-panel-row    { grid-template-columns: 1fr; }
            .db-proj-table-head    { display: none; }
            .db-chart-filters      { overflow-x: auto; flex-wrap: nowrap; -webkit-overflow-scrolling: touch; }
            .db-chart-wrap         { height: 190px; }
            .db-row-right          { flex-direction: column; align-items: flex-end; gap: 6px; }
            .db-progress-bar       { width: 70px; }
        }

        /* Mobile (≤540px): KPIs 2-col, System Stats full-width, tighter everything */
        @media (max-width: 540px) {
            .db-kpi-row            { grid-template-columns: 1fr 1fr; }
            .db-kpi-stats          { grid-column: 1 / -1; }
            .db-hero               { padding: 16px; border-radius: 14px; }
            .db-hero-date          { font-size: 11px; padding: 5px 10px; }
            .db-greeting           { font-size: 18px; }
            .db-subgreeting        { font-size: 12px; }
            .db-kpi-value          { font-size: 22px; }
            .db-chart-card         { padding: 14px 14px 18px; }
            .db-chart-wrap         { height: 170px; }
            .db-panel-card         { padding: 16px; }
            .db-mat-stat-val       { font-size: 20px; }
            .db-pay-big            { font-size: 24px; }
        }

        /* Very small (≤380px): KPIs → 1-col */
        @media (max-width: 380px) {
            .db-kpi-row            { grid-template-columns: 1fr; }
        }
    </style>

</body>
</html>
