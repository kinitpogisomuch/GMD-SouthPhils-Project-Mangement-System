<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Reports | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            <div class="page-header">
                <div>
                    <h1>KPI Reports</h1>
                    <p>Key performance indicators across projects, payments, materials, and people.</p>
                </div>
                <div style="font-size:13px;color:var(--muted);font-weight:600;">
                    As of {{ now()->format('F j, Y') }}
                </div>
            </div>

            @if(session('success'))
            <div class="alert-banner success"><i data-lucide="check-circle"></i> {{ session('success') }}</div>
            @endif

            <!-- Tabs -->
            <div class="emp-tabs">
                <button class="emp-tab active" data-tab="general">
                    <i data-lucide="bar-chart-2"></i>
                    General KPIs
                </button>
                <button class="emp-tab" data-tab="project">
                    <i data-lucide="folder-kanban"></i>
                    Project KPIs
                </button>
            </div>

            <!-- ===== TAB: GENERAL KPIs ===== -->
            <div class="emp-tab-content active" id="tab-general">

            <!-- ── Summary KPI strip ── -->
            <div class="rpt-kpi-grid">
                <div class="rpt-kpi-card">
                    <div class="rpt-kpi-icon" style="background:#EAF0FF;color:#2A4EAA;"><i data-lucide="folder-kanban"></i></div>
                    <div>
                        <div class="rpt-kpi-val">{{ $totalProjects }}</div>
                        <div class="rpt-kpi-label">Total Projects</div>
                        <div class="rpt-kpi-sub">{{ $completionRate }}% completion rate</div>
                    </div>
                </div>
                <div class="rpt-kpi-card">
                    <div class="rpt-kpi-icon" style="background:#E7F6EC;color:#207A3A;"><i data-lucide="credit-card"></i></div>
                    <div>
                        <div class="rpt-kpi-val">₱{{ number_format($totalReceived, 0) }}</div>
                        <div class="rpt-kpi-label">Total Received</div>
                        <div class="rpt-kpi-sub">{{ $collectionRate }}% collection rate</div>
                    </div>
                </div>
                <div class="rpt-kpi-card">
                    <div class="rpt-kpi-icon" style="background:#FFF3D6;color:#8A6100;"><i data-lucide="package"></i></div>
                    <div>
                        <div class="rpt-kpi-val">₱{{ number_format($totalMatCost, 0) }}</div>
                        <div class="rpt-kpi-label">Material Cost</div>
                        <div class="rpt-kpi-sub">{{ $totalMaterials }} entries · {{ $projectsWithMats }} projects</div>
                    </div>
                </div>
                <div class="rpt-kpi-card">
                    <div class="rpt-kpi-icon" style="background:#EDE9FE;color:#6D28D9;"><i data-lucide="users"></i></div>
                    <div>
                        <div class="rpt-kpi-val">{{ $totalEmployees }}</div>
                        <div class="rpt-kpi-label">Active Employees</div>
                        <div class="rpt-kpi-sub">{{ $totalClients }} registered clients</div>
                    </div>
                </div>
            </div>

            <!-- ── Performance Metrics ── -->
            <div class="rpt-metrics-row">

                <!-- Profit Margin -->
                <div class="rpt-metric-card">
                    <div class="rpt-metric-header">
                        <div class="rpt-metric-icon" style="background:#E7F6EC;color:#207A3A;">
                            <i data-lucide="trending-up"></i>
                        </div>
                        <div>
                            <div class="rpt-metric-title">Project Profit Margin</div>
                            <div class="rpt-metric-sub">Revenue minus material cost</div>
                        </div>
                    </div>
                    <div class="rpt-metric-big" style="color:{{ $profitMargin >= 50 ? '#16a34a' : ($profitMargin >= 20 ? '#e8900a' : '#ef4444') }};">
                        {{ $profitMargin }}%
                    </div>
                    <div class="rpt-metric-bar-wrap">
                        <div class="rpt-metric-bar-track">
                            <div class="rpt-metric-bar-fill"
                                 style="background:{{ $profitMargin >= 50 ? '#16a34a' : ($profitMargin >= 20 ? '#e8900a' : '#ef4444') }};"
                                 data-width="{{ min(100, $profitMargin) }}"></div>
                        </div>
                    </div>
                    <div class="rpt-metric-detail-row">
                        <div class="rpt-metric-detail-item">
                            <span class="rpt-metric-detail-label">Contract Value</span>
                            <span class="rpt-metric-detail-val">₱{{ number_format($totalContractValue, 0) }}</span>
                        </div>
                        <div class="rpt-metric-detail-item">
                            <span class="rpt-metric-detail-label">Material Cost</span>
                            <span class="rpt-metric-detail-val">₱{{ number_format($totalMatCost, 0) }}</span>
                        </div>
                        <div class="rpt-metric-detail-item">
                            <span class="rpt-metric-detail-label">Est. Profit</span>
                            <span class="rpt-metric-detail-val" style="color:#16a34a;">₱{{ number_format($estimatedProfit, 0) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Budget Adherence Rate -->
                <div class="rpt-metric-card">
                    <div class="rpt-metric-header">
                        <div class="rpt-metric-icon" style="background:#EAF0FF;color:#2A4EAA;">
                            <i data-lucide="shield-check"></i>
                        </div>
                        <div>
                            <div class="rpt-metric-title">Budget Adherence Rate</div>
                            <div class="rpt-metric-sub">Projects within contract budget</div>
                        </div>
                    </div>
                    <div class="rpt-metric-big" style="color:{{ $budgetAdherenceRate >= 80 ? '#16a34a' : ($budgetAdherenceRate >= 60 ? '#e8900a' : '#ef4444') }};">
                        {{ $budgetAdherenceRate }}%
                    </div>
                    <div class="rpt-metric-bar-wrap">
                        <div class="rpt-metric-bar-track">
                            <div class="rpt-metric-bar-fill"
                                 style="background:{{ $budgetAdherenceRate >= 80 ? '#16a34a' : ($budgetAdherenceRate >= 60 ? '#e8900a' : '#ef4444') }};"
                                 data-width="{{ $budgetAdherenceRate }}"></div>
                        </div>
                    </div>
                    <div class="rpt-metric-detail-row">
                        <div class="rpt-metric-detail-item">
                            <span class="rpt-metric-detail-label">Within Budget</span>
                            <span class="rpt-metric-detail-val" style="color:#16a34a;">{{ $adherentCount }} projects</span>
                        </div>
                        <div class="rpt-metric-detail-item">
                            <span class="rpt-metric-detail-label">Over Budget</span>
                            <span class="rpt-metric-detail-val" style="color:#ef4444;">{{ $adherenceTotal - $adherentCount }} projects</span>
                        </div>
                        <div class="rpt-metric-detail-item">
                            <span class="rpt-metric-detail-label">Tracked</span>
                            <span class="rpt-metric-detail-val">{{ $adherenceTotal }} total</span>
                        </div>
                    </div>
                </div>

                <!-- On-Time Delivery Rate -->
                <div class="rpt-metric-card">
                    <div class="rpt-metric-header">
                        <div class="rpt-metric-icon" style="background:#CCFBF1;color:#0D9488;">
                            <i data-lucide="clock-check"></i>
                        </div>
                        <div>
                            <div class="rpt-metric-title">On-Time Delivery Rate</div>
                            <div class="rpt-metric-sub">Completed before or on deadline</div>
                        </div>
                    </div>
                    <div class="rpt-metric-big" style="color:{{ $onTimeDeliveryRate >= 80 ? '#16a34a' : ($onTimeDeliveryRate >= 60 ? '#e8900a' : '#ef4444') }};">
                        {{ $onTimeDeliveryRate }}%
                    </div>
                    <div class="rpt-metric-bar-wrap">
                        <div class="rpt-metric-bar-track">
                            <div class="rpt-metric-bar-fill"
                                 style="background:{{ $onTimeDeliveryRate >= 80 ? '#16a34a' : ($onTimeDeliveryRate >= 60 ? '#e8900a' : '#ef4444') }};"
                                 data-width="{{ $onTimeDeliveryRate }}"></div>
                        </div>
                    </div>
                    <div class="rpt-metric-detail-row">
                        <div class="rpt-metric-detail-item">
                            <span class="rpt-metric-detail-label">On Time</span>
                            <span class="rpt-metric-detail-val" style="color:#16a34a;">{{ $onTimeCount }} projects</span>
                        </div>
                        <div class="rpt-metric-detail-item">
                            <span class="rpt-metric-detail-label">Late</span>
                            <span class="rpt-metric-detail-val" style="color:#ef4444;">{{ $lateCount }} projects</span>
                        </div>
                        <div class="rpt-metric-detail-item">
                            <span class="rpt-metric-detail-label">Completed</span>
                            <span class="rpt-metric-detail-val">{{ $completedProjects }} total</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ── Revenue + Project Breakdown ── -->
            <div class="rpt-row-2">

                <!-- Revenue chart -->
                <div class="rpt-card">
                    <div class="rpt-card-head">
                        <div>
                            <div class="rpt-card-title">Revenue Trend</div>
                            <div class="rpt-card-sub">Monthly payments received</div>
                        </div>
                        <div class="rpt-chart-filters">
                            <button class="rpt-filter-btn active" data-range="6">6M</button>
                            <button class="rpt-filter-btn" data-range="12">12M</button>
                            <button class="rpt-filter-btn" data-range="year">{{ now()->year }}</button>
                        </div>
                    </div>
                    <div style="height:220px;position:relative;">
                        <canvas id="rptRevenueChart"></canvas>
                    </div>
                </div>

                <!-- Project status donut -->
                <div class="rpt-card rpt-card-center">
                    <div class="rpt-card-head">
                        <div>
                            <div class="rpt-card-title">Project Status</div>
                            <div class="rpt-card-sub">Distribution by status</div>
                        </div>
                    </div>
                    <div style="position:relative;width:150px;height:150px;margin:0 auto 20px;">
                        <canvas id="rptStatusDonut"></canvas>
                        <div class="rpt-donut-center">
                            <div style="font-size:26px;font-weight:900;color:var(--dark);">{{ $totalProjects }}</div>
                            <div style="font-size:10px;font-weight:700;color:var(--muted-light);text-transform:uppercase;">Total</div>
                        </div>
                    </div>
                    <div class="rpt-legend">
                        @foreach($projectsByStatus as $item)
                        <div class="rpt-legend-row">
                            <span class="rpt-legend-dot" style="background:{{ $item['color'] }};"></span>
                            <span class="rpt-legend-label">{{ $item['label'] }}</span>
                            <span class="rpt-legend-val">{{ $item['count'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>

            <!-- ── Collections Line Chart ── -->
            <div class="rpt-card" style="margin-bottom:16px;">
                <div class="rpt-card-head">
                    <div>
                        <div class="rpt-card-title">Collections Over Time</div>
                        <div class="rpt-card-sub">Monthly received vs cumulative total — last 12 months</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                        <div style="display:flex;align-items:center;gap:14px;">
                            <div style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:var(--muted);">
                                <span style="width:24px;height:3px;background:#2A4EAA;border-radius:2px;display:inline-block;"></span>
                                Monthly
                            </div>
                            <div style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:var(--muted);">
                                <span style="width:24px;height:2px;background:#16a34a;border-radius:2px;display:inline-block;border-top:2px dashed #16a34a;"></span>
                                Cumulative
                            </div>
                        </div>
                    </div>
                </div>
                <div style="height:240px;position:relative;">
                    <canvas id="rptLineChart"></canvas>
                </div>
            </div>

            <!-- ── Payment Breakdown + Project Performance ── -->
            <div class="rpt-row-3">

                <!-- Payment summary -->
                <div class="rpt-card">
                    <div class="rpt-card-head">
                        <div class="rpt-card-title">Payment Breakdown</div>
                    </div>
                    <div class="rpt-stat-list">
                        <div class="rpt-stat-row">
                            <span class="rpt-stat-label">Contract Value</span>
                            <span class="rpt-stat-val">₱{{ number_format($totalContractValue, 0) }}</span>
                        </div>
                        <div class="rpt-stat-divider"></div>
                        <div class="rpt-stat-row">
                            <span class="rpt-stat-label">Total Received</span>
                            <span class="rpt-stat-val" style="color:#16a34a;">₱{{ number_format($totalReceived, 0) }}</span>
                        </div>
                        <div class="rpt-stat-divider"></div>
                        <div class="rpt-stat-row">
                            <span class="rpt-stat-label">Outstanding</span>
                            <span class="rpt-stat-val" style="color:#e8900a;">₱{{ number_format($outstanding, 0) }}</span>
                        </div>
                        <div class="rpt-stat-divider"></div>
                        <div class="rpt-stat-row">
                            <span class="rpt-stat-label">Collection Rate</span>
                            <span class="rpt-stat-val">{{ $collectionRate }}%</span>
                        </div>
                    </div>
                    <div style="height:8px;background:var(--cream-deep);border-radius:999px;overflow:hidden;margin-top:16px;">
                        <div style="height:100%;background:#16a34a;border-radius:999px;" data-width="{{ $collectionRate }}"></div>
                    </div>
                    <div class="rpt-pay-pills">
                        <div class="rpt-pill green"><i data-lucide="check-circle"></i> {{ $fullyPaid }} Fully Paid</div>
                        <div class="rpt-pill yellow"><i data-lucide="minus-circle"></i> {{ $partialPaid }} Partial</div>
                        <div class="rpt-pill orange"><i data-lucide="clock"></i> {{ $pendingPayment }} Pending</div>
                    </div>
                </div>

                <!-- Project performance -->
                <div class="rpt-card">
                    <div class="rpt-card-head">
                        <div class="rpt-card-title">Project Performance</div>
                    </div>
                    <div class="rpt-perf-grid">
                        <div class="rpt-perf-stat">
                            <div class="rpt-perf-val">{{ $activeProjects }}</div>
                            <div class="rpt-perf-label">Active</div>
                        </div>
                        <div class="rpt-perf-stat">
                            <div class="rpt-perf-val" style="color:#16a34a;">{{ $completedProjects }}</div>
                            <div class="rpt-perf-label">Completed</div>
                        </div>
                        <div class="rpt-perf-stat">
                            <div class="rpt-perf-val" style="color:#ef4444;">{{ $overdueProjects }}</div>
                            <div class="rpt-perf-label">Overdue</div>
                        </div>
                        <div class="rpt-perf-stat">
                            <div class="rpt-perf-val">{{ $avgProgress }}%</div>
                            <div class="rpt-perf-label">Avg Progress</div>
                        </div>
                    </div>
                    <div class="rpt-stat-divider" style="margin:16px 0;"></div>
                    <div class="rpt-stat-row">
                        <span class="rpt-stat-label">Completion Rate</span>
                        <span class="rpt-stat-val">{{ $completionRate }}%</span>
                    </div>
                    <div style="height:8px;background:var(--cream-deep);border-radius:999px;overflow:hidden;margin-top:8px;">
                        <div style="height:100%;background:#2A4EAA;border-radius:999px;" data-width="{{ $completionRate }}"></div>
                    </div>
                </div>

                <!-- Materials -->
                <div class="rpt-card">
                    <div class="rpt-card-head">
                        <div class="rpt-card-title">Materials</div>
                    </div>
                    <div class="rpt-perf-grid" style="grid-template-columns:1fr 1fr;">
                        <div class="rpt-perf-stat">
                            <div class="rpt-perf-val">{{ $totalMaterials }}</div>
                            <div class="rpt-perf-label">Total Entries</div>
                        </div>
                        <div class="rpt-perf-stat">
                            <div class="rpt-perf-val">{{ $projectsWithMats }}</div>
                            <div class="rpt-perf-label">Projects</div>
                        </div>
                    </div>
                    <div class="rpt-stat-divider" style="margin:16px 0;"></div>
                    <div class="rpt-stat-row">
                        <span class="rpt-stat-label">Estimated Cost</span>
                        <span class="rpt-stat-val">₱{{ number_format($totalMatCost, 0) }}</span>
                    </div>
                    <div class="rpt-stat-divider" style="margin:12px 0;"></div>
                    <div class="rpt-stat-row">
                        <span class="rpt-stat-label">Est. Profit Margin</span>
                        @php $margin = $totalContractValue > 0 ? round((($totalContractValue - $totalMatCost) / $totalContractValue) * 100) : 0; @endphp
                        <span class="rpt-stat-val" style="color:#16a34a;">{{ $margin }}%</span>
                    </div>
                </div>

            </div>

            <!-- ── Top Clients table ── -->
            <div class="rpt-card" style="margin-top:0;">
                <div class="rpt-card-head" style="margin-bottom:0;padding-bottom:14px;border-bottom:1px solid var(--border);">
                    <div>
                        <div class="rpt-card-title">Top Clients</div>
                        <div class="rpt-card-sub">Ranked by number of projects</div>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Client</th>
                                <th>Projects</th>
                                <th>Completed</th>
                                <th>Contract Value</th>
                                <th>Amount Received</th>
                                <th>Collection</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topClients as $i => $client)
                            <tr>
                                <td>
                                    <span class="rpt-rank rpt-rank-{{ $i + 1 }}">{{ $i + 1 }}</span>
                                </td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div class="rpt-client-avatar">{{ strtoupper(substr($client['name'], 0, 1)) }}</div>
                                        <strong>{{ $client['name'] }}</strong>
                                    </div>
                                </td>
                                <td><strong>{{ $client['project_count'] }}</strong></td>
                                <td>
                                    <span class="status-badge completed">{{ $client['completed'] }}</span>
                                </td>
                                <td>₱{{ number_format($client['contract'], 0) }}</td>
                                <td style="color:#16a34a;font-weight:700;">₱{{ number_format($client['received'], 0) }}</td>
                                <td>
                                    @php $cr = $client['contract'] > 0 ? min(100, round(($client['received'] / $client['contract']) * 100)) : 0; @endphp
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div style="flex:1;height:5px;background:var(--cream-deep);border-radius:999px;overflow:hidden;min-width:60px;">
                                            <div style="height:100%;background:#16a34a;border-radius:999px;" data-width="{{ $cr }}"></div>
                                        </div>
                                        <span style="font-size:12px;font-weight:700;color:var(--muted);width:32px;">{{ $cr }}%</span>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">No client data yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            </div><!-- end tab-general -->

            <!-- ===== TAB: PROJECT KPIs ===== -->
            <div class="emp-tab-content" id="tab-project">

                <!-- Project selector -->
                <div class="rpt-card" style="margin-bottom:16px;">
                    <div class="rpt-card-head" style="margin-bottom:12px;">
                        <div>
                            <div class="rpt-card-title">Select a Project</div>
                            <div class="rpt-card-sub">Choose a project to view its individual KPIs</div>
                        </div>
                    </div>
                    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                        <select id="projectKpiSelect" class="filter-select" style="flex:1;min-width:200px;max-width:480px;">
                            <option value="">— Select a project —</option>
                            @foreach($allProjects as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->client }}) — {{ ucfirst($p->status) }}</option>
                            @endforeach
                        </select>
                        <div id="projectKpiLoading" style="display:none;font-size:13px;color:var(--muted);">Loading…</div>
                    </div>
                </div>

                <!-- Empty state -->
                <div id="projectKpiEmpty" class="rpt-card" style="text-align:center;padding:60px 20px;">
                    <i data-lucide="folder-open" style="width:40px;height:40px;color:var(--muted-light);margin-bottom:12px;"></i>
                    <div style="font-size:15px;font-weight:700;color:var(--muted);">No project selected</div>
                    <div style="font-size:13px;color:var(--muted-light);margin-top:4px;">Pick a project from the dropdown above to view its KPIs.</div>
                </div>

                <!-- Project KPI content (hidden until loaded) -->
                <div id="projectKpiContent" style="display:none;">

                    <!-- Project header -->
                    <div class="rpt-proj-header" id="pkiHeader"></div>

                    <!-- Top row: progress + timeline -->
                    <div class="rpt-row-2" style="margin-bottom:16px;">
                        <div class="rpt-card" id="pkiProgressCard"></div>
                        <div class="rpt-card" id="pkiTimelineCard"></div>
                    </div>

                    <!-- Middle row: financial + payment + materials -->
                    <div class="rpt-row-3" style="margin-bottom:16px;">
                        <div class="rpt-card" id="pkiFinancialCard"></div>
                        <div class="rpt-card" id="pkiPaymentCard"></div>
                        <div class="rpt-card" id="pkiMaterialCard"></div>
                    </div>

                    <!-- Payment stages -->
                    <div class="rpt-card" id="pkiStagesCard" style="margin-bottom:0;"></div>

                </div>

            </div><!-- end tab-project -->

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        // Apply data-width progress bars
        document.querySelectorAll('[data-width]').forEach(function(el) {
            el.style.width = el.dataset.width + '%';
        });

        // Revenue chart
        var all12Labels  = @json(array_column($monthlyRevenue, 'label'));
        var all12Amounts = @json(array_column($monthlyRevenue, 'amount'));
        var yearLabels   = @json(array_column($yearlyRevenue, 'label'));
        var yearAmounts  = @json(array_column($yearlyRevenue, 'amount'));

        var rptCtx = document.getElementById('rptRevenueChart').getContext('2d');

        function makeBarColors(data) {
            return data.map(function(v, i) {
                return i === data.length - 1 ? '#333333' : 'rgba(51,51,51,0.15)';
            });
        }

        var rptChart = new Chart(rptCtx, {
            type: 'bar',
            data: {
                labels: all12Labels.slice(-6),
                datasets: [{
                    data: all12Amounts.slice(-6),
                    backgroundColor: makeBarColors(all12Amounts.slice(-6)),
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 48,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: { label: function(c) { return ' ₱' + c.parsed.y.toLocaleString(); } },
                        backgroundColor: '#1a1a1a',
                        padding: 10,
                        cornerRadius: 8,
                        titleFont: { weight: '700' },
                        bodyFont: { weight: '600' },
                    }
                },
                scales: {
                    x: { grid: { display: false }, border: { display: false }, ticks: { color: '#999', font: { size: 11, weight: '600' } } },
                    y: {
                        border: { display: false, dash: [4, 4] },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: {
                            color: '#999', font: { size: 11, weight: '600' },
                            callback: function(v) { return v >= 1000 ? '₱' + (v / 1000).toFixed(0) + 'k' : '₱' + v; }
                        }
                    }
                }
            }
        });

        // Revenue filter buttons
        var filterSets = {
            '6':    { labels: all12Labels.slice(-6),  amounts: all12Amounts.slice(-6)  },
            '12':   { labels: all12Labels,            amounts: all12Amounts            },
            'year': { labels: yearLabels,             amounts: yearAmounts             },
        };
        document.querySelectorAll('.rpt-filter-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var set = filterSets[this.dataset.range];
                if (!set) return;
                rptChart.data.labels = set.labels;
                rptChart.data.datasets[0].data = set.amounts;
                rptChart.data.datasets[0].backgroundColor = makeBarColors(set.amounts);
                rptChart.update();
                document.querySelectorAll('.rpt-filter-btn').forEach(function(b) { b.classList.remove('active'); });
                this.classList.add('active');
            });
        });

        // ── Collections line chart ──
        (function() {
            var labels  = all12Labels;
            var monthly = all12Amounts;

            // Build cumulative series
            var cumulative = [];
            var running = 0;
            monthly.forEach(function(v) { running += v; cumulative.push(running); });

            var lCtx = document.getElementById('rptLineChart').getContext('2d');

            // Gradient fill for monthly line
            var gradBlue = lCtx.createLinearGradient(0, 0, 0, 240);
            gradBlue.addColorStop(0,   'rgba(42,78,170,0.18)');
            gradBlue.addColorStop(1,   'rgba(42,78,170,0.01)');

            var gradGreen = lCtx.createLinearGradient(0, 0, 0, 240);
            gradGreen.addColorStop(0,  'rgba(22,163,74,0.12)');
            gradGreen.addColorStop(1,  'rgba(22,163,74,0.01)');

            new Chart(lCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Monthly',
                            data: monthly,
                            borderColor: '#2A4EAA',
                            backgroundColor: gradBlue,
                            borderWidth: 2.5,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#2A4EAA',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            tension: 0.4,
                            fill: true,
                        },
                        {
                            label: 'Cumulative',
                            data: cumulative,
                            borderColor: '#16a34a',
                            backgroundColor: gradGreen,
                            borderWidth: 2,
                            borderDash: [6, 4],
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            pointBackgroundColor: '#16a34a',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            tension: 0.4,
                            fill: true,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(c) {
                                    return '  ' + c.dataset.label + ': ₱' + Math.round(c.parsed.y).toLocaleString();
                                }
                            },
                            backgroundColor: '#1a1a1a',
                            padding: 12,
                            cornerRadius: 8,
                            titleFont: { weight: '700' },
                            bodyFont: { weight: '600' },
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: { color: '#999', font: { size: 11, weight: '600' } }
                        },
                        y: {
                            border: { display: false, dash: [4, 4] },
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: {
                                color: '#999',
                                font: { size: 11, weight: '600' },
                                callback: function(v) {
                                    return v >= 1000000 ? '₱' + (v/1000000).toFixed(1) + 'M'
                                         : v >= 1000    ? '₱' + (v/1000).toFixed(0) + 'k'
                                         : '₱' + v;
                                }
                            }
                        }
                    }
                }
            });
        })();

        // Project status donut
        var statusData = @json($projectsByStatus);
        new Chart(document.getElementById('rptStatusDonut').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: statusData.map(function(d) { return d.label; }),
                datasets: [{
                    data: statusData.map(function(d) { return d.count; }),
                    backgroundColor: statusData.map(function(d) { return d.color; }),
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: { label: function(c) { return '  ' + c.label + ': ' + c.parsed; } },
                        backgroundColor: '#1a1a1a',
                        padding: 10,
                        cornerRadius: 8,
                    }
                }
            }
        });

        // ── Tab switching ──
        document.querySelectorAll('.emp-tab').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.emp-tab').forEach(function(b) { b.classList.remove('active'); });
                document.querySelectorAll('.emp-tab-content').forEach(function(p) { p.classList.remove('active'); });
                this.classList.add('active');
                document.getElementById('tab-' + this.dataset.tab).classList.add('active');
                lucide.createIcons();
            });
        });

        // ── Project KPI loader ──
        var PROJECT_KPI_URL = '{{ route("admin.reports.project", ["id" => "__ID__"]) }}';

        function php(v) { return '₱' + Math.round(v).toLocaleString('en-PH'); }
        function pct(v, color) {
            color = color || '#333';
            return '<div style="height:7px;background:#ebebeb;border-radius:999px;overflow:hidden;margin-top:6px;">'
                + '<div style="height:100%;background:' + color + ';border-radius:999px;width:' + v + '%;"></div></div>';
        }
        function statusColor(v) { return v >= 80 ? '#16a34a' : v >= 50 ? '#e8900a' : '#ef4444'; }
        function badge(label, css) {
            return '<span class="status-badge ' + css + '">' + label + '</span>';
        }

        document.getElementById('projectKpiSelect').addEventListener('change', function() {
            var id = this.value;
            if (!id) {
                document.getElementById('projectKpiEmpty').style.display = '';
                document.getElementById('projectKpiContent').style.display = 'none';
                return;
            }

            document.getElementById('projectKpiLoading').style.display = '';
            document.getElementById('projectKpiEmpty').style.display = 'none';
            document.getElementById('projectKpiContent').style.display = 'none';

            fetch(PROJECT_KPI_URL.replace('__ID__', id), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                document.getElementById('projectKpiLoading').style.display = 'none';

                var p = d.project, pay = d.payment, fin = d.financial, tl = d.timeline, mat = d.materials;

                // Header
                var statusCss = { 'Completed': 'completed', 'Ongoing': 'ongoing', 'Archived': 'archived' };
                document.getElementById('pkiHeader').innerHTML =
                    '<div class="rpt-proj-header-inner">'
                    + '<div class="rpt-proj-title">' + p.name + '</div>'
                    + '<div class="rpt-proj-meta">'
                    + '<span>' + badge(p.status, statusCss[p.status] || 'pending') + '</span>'
                    + '<span style="color:var(--muted);font-size:13px;"><i data-lucide="building-2" style="width:12px;height:12px;"></i> ' + p.client + '</span>'
                    + '<span style="color:var(--muted);font-size:13px;"><i data-lucide="package" style="width:12px;height:12px;"></i> ' + (p.tank_type || '—') + '</span>'
                    + '</div></div>';

                // Progress card
                var progColor = p.progress >= 100 ? '#16a34a' : p.progress >= 50 ? '#2A4EAA' : '#e8900a';
                document.getElementById('pkiProgressCard').innerHTML =
                    '<div class="rpt-card-head"><div class="rpt-card-title">Project Progress</div></div>'
                    + '<div style="font-size:52px;font-weight:900;color:' + progColor + ';letter-spacing:-2px;line-height:1;">' + p.progress + '%</div>'
                    + '<div style="height:10px;background:#ebebeb;border-radius:999px;overflow:hidden;margin-top:14px;">'
                    + '<div style="height:100%;background:' + progColor + ';border-radius:999px;width:' + p.progress + '%;"></div></div>'
                    + '<div style="display:flex;justify-content:space-between;font-size:11px;color:var(--muted-light);font-weight:600;margin-top:6px;">'
                    + '<span>Start: ' + (p.start_date || '—') + '</span><span>End: ' + (p.end_date || '—') + '</span></div>';

                // Timeline card
                var tlColor = tl.is_overdue ? '#ef4444' : tl.time_progress > p.progress ? '#e8900a' : '#16a34a';
                var tlStatus = tl.is_overdue
                    ? '<span style="color:#ef4444;font-weight:700;">Overdue by ' + Math.abs(tl.days_remaining) + ' days</span>'
                    : (tl.days_remaining !== null ? '<span style="color:#16a34a;font-weight:700;">' + tl.days_remaining + ' days remaining</span>' : '—');
                document.getElementById('pkiTimelineCard').innerHTML =
                    '<div class="rpt-card-head"><div class="rpt-card-title">Timeline</div></div>'
                    + '<div class="rpt-stat-list">'
                    + '<div class="rpt-stat-row"><span class="rpt-stat-label">Total Duration</span><span class="rpt-stat-val">' + tl.days_total + ' days</span></div>'
                    + '<div class="rpt-stat-divider"></div>'
                    + '<div class="rpt-stat-row"><span class="rpt-stat-label">Days Elapsed</span><span class="rpt-stat-val">' + tl.days_elapsed + ' days</span></div>'
                    + '<div class="rpt-stat-divider"></div>'
                    + '<div class="rpt-stat-row"><span class="rpt-stat-label">Status</span><span class="rpt-stat-val">' + tlStatus + '</span></div>'
                    + '<div class="rpt-stat-divider"></div>'
                    + '<div class="rpt-stat-row"><span class="rpt-stat-label">Time Progress</span><span class="rpt-stat-val">' + tl.time_progress + '%</span></div>'
                    + '</div>'
                    + pct(tl.time_progress, tlColor);

                // Financial card
                var pmColor = statusColor(fin.profit_margin);
                document.getElementById('pkiFinancialCard').innerHTML =
                    '<div class="rpt-card-head"><div class="rpt-card-title">Financials</div></div>'
                    + '<div style="font-size:34px;font-weight:900;color:' + pmColor + ';letter-spacing:-1px;line-height:1;">' + fin.profit_margin + '%</div>'
                    + '<div style="font-size:11px;font-weight:700;color:var(--muted-light);text-transform:uppercase;margin-top:3px;">Profit Margin</div>'
                    + pct(Math.min(100, fin.profit_margin), pmColor)
                    + '<div class="rpt-stat-divider" style="margin:14px 0;"></div>'
                    + '<div class="rpt-stat-row"><span class="rpt-stat-label">Contract Value</span><span class="rpt-stat-val">' + php(pay.contract_value) + '</span></div>'
                    + '<div class="rpt-stat-divider"></div>'
                    + '<div class="rpt-stat-row"><span class="rpt-stat-label">Material Cost</span><span class="rpt-stat-val">' + php(mat.cost) + '</span></div>'
                    + '<div class="rpt-stat-divider"></div>'
                    + '<div class="rpt-stat-row"><span class="rpt-stat-label">Est. Profit</span><span class="rpt-stat-val" style="color:#16a34a;">' + php(fin.estimated_profit) + '</span></div>'
                    + '<div class="rpt-stat-divider"></div>'
                    + '<div class="rpt-stat-row"><span class="rpt-stat-label">Budget</span><span class="rpt-stat-val" style="color:' + (fin.budget_adherent ? '#16a34a' : '#ef4444') + ';">' + (fin.budget_adherent ? '✓ Within Budget' : '✗ Over Budget') + '</span></div>';

                // Payment card
                var crColor = statusColor(pay.collection_rate);
                document.getElementById('pkiPaymentCard').innerHTML =
                    '<div class="rpt-card-head"><div class="rpt-card-title">Payments</div></div>'
                    + '<div style="font-size:34px;font-weight:900;color:' + crColor + ';letter-spacing:-1px;line-height:1;">' + pay.collection_rate + '%</div>'
                    + '<div style="font-size:11px;font-weight:700;color:var(--muted-light);text-transform:uppercase;margin-top:3px;">Collection Rate</div>'
                    + pct(pay.collection_rate, crColor)
                    + '<div class="rpt-stat-divider" style="margin:14px 0;"></div>'
                    + '<div class="rpt-stat-row"><span class="rpt-stat-label">Received</span><span class="rpt-stat-val" style="color:#16a34a;">' + php(pay.received) + '</span></div>'
                    + '<div class="rpt-stat-divider"></div>'
                    + '<div class="rpt-stat-row"><span class="rpt-stat-label">Outstanding</span><span class="rpt-stat-val" style="color:#e8900a;">' + php(pay.outstanding) + '</span></div>'
                    + '<div class="rpt-stat-divider"></div>'
                    + '<div class="rpt-stat-row"><span class="rpt-stat-label">Status</span><span class="rpt-stat-val">' + pay.status + '</span></div>';

                // Materials card
                document.getElementById('pkiMaterialCard').innerHTML =
                    '<div class="rpt-card-head"><div class="rpt-card-title">Materials</div></div>'
                    + '<div class="rpt-perf-grid" style="grid-template-columns:1fr 1fr;margin-bottom:16px;">'
                    + '<div class="rpt-perf-stat"><div class="rpt-perf-val">' + mat.count + '</div><div class="rpt-perf-label">Entries</div></div>'
                    + '<div class="rpt-perf-stat"><div class="rpt-perf-val" style="color:#8A6100;">' + php(mat.cost) + '</div><div class="rpt-perf-label">Total Cost</div></div>'
                    + '</div>';

                // Payment stages
                var stagesHtml = '<div class="rpt-card-head" style="margin-bottom:0;padding-bottom:14px;border-bottom:1px solid var(--border);">'
                    + '<div><div class="rpt-card-title">Payment Stages</div><div class="rpt-card-sub">Transaction history for this project</div></div></div>';
                if (pay.stages && pay.stages.length) {
                    stagesHtml += '<div class="table-wrapper"><table class="data-table"><thead><tr>'
                        + '<th>Stage</th><th>Amount</th><th>Date</th></tr></thead><tbody>';
                    pay.stages.forEach(function(s) {
                        stagesHtml += '<tr><td>' + s.stage + '</td><td style="color:#16a34a;font-weight:700;">' + php(s.amount) + '</td><td>' + (s.date || '—') + '</td></tr>';
                    });
                    stagesHtml += '</tbody></table></div>';
                } else {
                    stagesHtml += '<div style="text-align:center;padding:30px;color:var(--muted);font-size:14px;">No payment transactions recorded yet.</div>';
                }
                document.getElementById('pkiStagesCard').innerHTML = stagesHtml;

                document.getElementById('projectKpiContent').style.display = '';
                lucide.createIcons();
            });
        });
    </script>

    <style>
        /* ── Project KPI header ── */
        .rpt-proj-header {
            background: linear-gradient(135deg, var(--dark) 0%, #4a4a4a 100%);
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 16px;
        }
        .rpt-proj-header-inner { display: flex; flex-direction: column; gap: 8px; }
        .rpt-proj-title { font-size: 20px; font-weight: 900; color: #fff; letter-spacing: -0.3px; }
        .rpt-proj-meta  { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .rpt-proj-meta i { vertical-align: middle; }

    </style>

    <style>
        /* ── KPI strip ── */
        .rpt-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        .rpt-kpi-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px 20px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }
        .rpt-kpi-icon {
            width: 40px; height: 40px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .rpt-kpi-icon i { width: 18px; height: 18px; }
        .rpt-kpi-val  { font-size: 22px; font-weight: 900; color: var(--dark); letter-spacing: -0.5px; line-height: 1; }
        .rpt-kpi-label{ font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.4px; margin-top: 4px; }
        .rpt-kpi-sub  { font-size: 11px; font-weight: 600; color: var(--muted-light); margin-top: 4px; }

        /* ── Cards ── */
        .rpt-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
        }
        .rpt-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 18px;
            gap: 12px;
        }
        .rpt-card-title { font-size: 14px; font-weight: 800; color: var(--dark); }
        .rpt-card-sub   { font-size: 12px; color: var(--muted-light); font-weight: 500; margin-top: 2px; }

        /* ── Row layouts ── */
        .rpt-row-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        .rpt-row-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }
        .rpt-row-3 > *, .rpt-row-2 > * { min-width: 0; }

        /* ── Chart filters ── */
        .rpt-chart-filters {
            display: flex;
            gap: 3px;
            background: #ebebeb;
            border-radius: 8px;
            padding: 3px;
            flex-shrink: 0;
        }
        .rpt-filter-btn {
            background: transparent; border: none;
            border-radius: 6px; padding: 4px 10px;
            font-size: 12px; font-weight: 600;
            color: #888; cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }
        .rpt-filter-btn.active { background: #fff; color: #111; box-shadow: 0 1px 3px rgba(0,0,0,0.12); }
        .rpt-filter-btn:hover { color: #333; }

        /* ── Donut center ── */
        .rpt-card-center { display: flex; flex-direction: column; }
        .rpt-donut-center {
            position: absolute; inset: 0;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            pointer-events: none;
        }

        /* ── Legend ── */
        .rpt-legend { display: flex; flex-direction: column; gap: 8px; }
        .rpt-legend-row {
            display: flex; align-items: center; gap: 8px;
        }
        .rpt-legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .rpt-legend-label { font-size: 12px; color: var(--muted); flex: 1; }
        .rpt-legend-val { font-size: 13px; font-weight: 800; color: var(--dark); }

        /* ── Stat list ── */
        .rpt-stat-list { display: flex; flex-direction: column; }
        .rpt-stat-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; }
        .rpt-stat-label { font-size: 12px; color: var(--muted); font-weight: 600; }
        .rpt-stat-val   { font-size: 13px; font-weight: 800; color: var(--dark); }
        .rpt-stat-divider { height: 1px; background: var(--border); }

        /* ── Pills ── */
        .rpt-pay-pills { display: flex; gap: 6px; margin-top: 14px; flex-wrap: wrap; }
        .rpt-pill {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 11.5px; font-weight: 700;
            padding: 5px 10px; border-radius: 999px;
        }
        .rpt-pill i { width: 12px; height: 12px; }
        .rpt-pill.green  { background: #dcfce7; color: #15803d; }
        .rpt-pill.yellow { background: #fef9c3; color: #854d0e; }
        .rpt-pill.orange { background: #fff7ed; color: #c2410c; }

        /* ── Performance grid ── */
        .rpt-perf-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .rpt-perf-stat {
            background: var(--cream-soft);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 10px;
            text-align: center;
        }
        .rpt-perf-val   { font-size: 22px; font-weight: 900; color: var(--dark); letter-spacing: -0.5px; line-height: 1; }
        .rpt-perf-label { font-size: 10px; font-weight: 700; color: var(--muted-light); text-transform: uppercase; letter-spacing: 0.4px; margin-top: 4px; }

        /* ── Top clients table ── */
        .rpt-rank {
            display: inline-flex; align-items: center; justify-content: center;
            width: 24px; height: 24px; border-radius: 50%;
            font-size: 11px; font-weight: 800;
            background: var(--cream-deep); color: var(--muted);
        }
        .rpt-rank-1 { background: #fef3c7; color: #92400e; }
        .rpt-rank-2 { background: #f1f5f9; color: #475569; }
        .rpt-rank-3 { background: #fce7f3; color: #9d174d; }
        .rpt-client-avatar {
            width: 32px; height: 32px; border-radius: 8px;
            background: var(--dark); color: #fff;
            font-size: 13px; font-weight: 900;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        /* ── Performance Metrics row ── */
        .rpt-metrics-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }
        .rpt-metrics-row > * { min-width: 0; }
        .rpt-metric-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
        }
        .rpt-metric-header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
        }
        .rpt-metric-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .rpt-metric-icon i { width: 18px; height: 18px; }
        .rpt-metric-title {
            font-size: 13px;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }
        .rpt-metric-sub {
            font-size: 11.5px;
            color: var(--muted-light);
            font-weight: 500;
            margin-top: 2px;
        }
        .rpt-metric-big {
            font-size: 40px;
            font-weight: 900;
            letter-spacing: -1.5px;
            line-height: 1;
            margin-bottom: 12px;
        }
        .rpt-metric-bar-wrap { margin-bottom: 16px; }
        .rpt-metric-bar-track {
            height: 7px;
            background: var(--cream-deep);
            border-radius: 999px;
            overflow: hidden;
        }
        .rpt-metric-bar-fill {
            height: 100%;
            border-radius: 999px;
            transition: width 0.5s ease;
        }
        .rpt-metric-detail-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            padding-top: 14px;
            border-top: 1px solid var(--border);
        }
        .rpt-metric-detail-item { display: flex; flex-direction: column; gap: 2px; }
        .rpt-metric-detail-label {
            font-size: 10px;
            font-weight: 700;
            color: var(--muted-light);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .rpt-metric-detail-val {
            font-size: 12px;
            font-weight: 800;
            color: var(--dark);
        }

        /* ── Responsive ── */
        @media (max-width: 1100px) {
            .rpt-kpi-grid     { grid-template-columns: repeat(2, 1fr); }
            .rpt-metrics-row  { grid-template-columns: 1fr 1fr; }
            .rpt-row-3        { grid-template-columns: 1fr 1fr; }
            .rpt-perf-grid    { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .rpt-row-2        { grid-template-columns: 1fr; }
            .rpt-row-3        { grid-template-columns: 1fr; }
            .rpt-metrics-row  { grid-template-columns: 1fr; }
        }
        @media (max-width: 540px) {
            .rpt-kpi-grid     { grid-template-columns: 1fr 1fr; }
            .rpt-metric-big   { font-size: 32px; }
        }
    </style>
</body>
</html>
