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

                $hour = (int) now()->timezone('Asia/Manila')->format('G');
                $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
            @endphp

            <!-- ── Hero greeting ── -->
            <div class="db-hero">
                <div class="db-hero-left">
                    <div>
                        <div class="db-greeting">{{ $greeting }}, {{ $adminFirstName }}</div>
                        <div class="db-subgreeting">Here's what's happening with your projects today.</div>
                    </div>
                </div>
                <div class="db-hero-meta">
                    <div class="db-hero-date">
                        <i data-lucide="calendar-days"></i>
                        {{ now()->format('l, F j, Y') }}
                    </div>
                </div>
            </div>

            {{-- ── Year filter ── --}}
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
                <span style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:var(--muted);">
                    <i data-lucide="calendar-range" style="width:14px;height:14px;"></i>
                    Filter by year:
                </span>
                <select id="dbYearFilterSelect" class="filter-select">
                    @foreach($availableYears as $yr)
                    <option value="{{ $yr }}" {{ $yr == $currentYear ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>

            {{-- ── Interactive KPI cards ── --}}
            <div class="db-kpi-row" id="kpiCardRow" style="margin-bottom:16px;">
                @foreach($kpiCards as $i => $card)
                <div class="db-kpi-card db-kpi-card-interactive{{ $i === 0 ? ' selected' : '' }}"
                     data-kpi-key="{{ $card['key'] }}"
                     role="button"
                     tabindex="0"
                     aria-pressed="{{ $i === 0 ? 'true' : 'false' }}"
                     style="--kpi-accent:{{ $card['color'] }};">
                    <div class="db-kpi-icon" style="background:{{ $card['bg'] }};color:{{ $card['color'] }};">
                        <i data-lucide="{{ $card['icon'] }}"></i>
                    </div>
                    <div class="db-kpi-body">
                        <div class="db-kpi-value">{{ $card['valueText'] }}</div>
                        <div class="db-kpi-label">{{ $card['name'] }}</div>
                        <div class="db-kpi-sub">
                            @if($card['trendText'])
                                @if($card['trendPct'] !== null)
                                    <span class="{{ $card['trendGood'] ? 'db-kpi-trend-up' : 'db-kpi-trend-down' }}">
                                        {{ $card['trendPct'] >= 0 ? '▲' : '▼' }} {{ number_format(abs($card['trendPct']), 1) }}%
                                    </span>
                                    {{ $card['trendText'] }}
                                @else
                                    {{ $card['trendText'] }}
                                @endif
                            @else
                                {{ $card['unitText'] }}
                            @endif
                        </div>
                    </div>
                    <div class="db-kpi-select-check"><i data-lucide="check"></i></div>
                </div>
                @endforeach
            </div>

            {{-- ── KPI detail chart (updates based on selected card above) ── --}}
            <div class="table-card" style="margin-bottom:20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px 16px;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:10px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div id="kpiChartIconWrap" style="width:36px;height:36px;background:{{ $kpiCards[0]['bg'] }};color:{{ $kpiCards[0]['color'] }};border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="{{ $kpiCards[0]['icon'] }}" style="width:18px;height:18px;"></i>
                        </div>
                        <div>
                            <div id="kpiChartTitle" style="font-size:15px;font-weight:800;color:var(--dark);">{{ $kpiCards[0]['chart']['title'] }}</div>
                            <div id="kpiChartSubtitle" style="font-size:12px;color:var(--muted);margin-top:2px;">{{ $kpiCards[0]['chart']['subtitle'] }}</div>
                        </div>
                    </div>
                </div>
                <div style="padding:20px 24px;">
                    <div id="kpiChartWrap" style="position:relative;height:280px;transition:opacity .18s ease;">
                        <canvas id="kpiDetailChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- ── Needs Attention ── --}}
            <div class="table-card" style="margin-bottom:20px;">
                <div style="display:flex;align-items:center;gap:10px;padding:20px 24px 16px;border-bottom:1px solid var(--border);">
                    <div style="width:36px;height:36px;background:#FFF3D6;color:#8A6100;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="alert-triangle" style="width:18px;height:18px;"></i>
                    </div>
                    <div>
                        <div style="font-size:15px;font-weight:800;color:var(--dark);">Needs Attention</div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;">Overdue projects, unpaid balances, and unread messages</div>
                    </div>
                </div>

                @if($overdueProjectsList->isEmpty() && $pendingPaymentsList->isEmpty() && $unreadMessages == 0)
                <div style="text-align:center;padding:36px 20px;color:var(--muted);">
                    <i data-lucide="check-circle-2" style="width:32px;height:32px;color:#16a34a;display:block;margin:0 auto 10px;"></i>
                    <div style="font-size:13.5px;font-weight:700;color:var(--dark);">All caught up</div>
                    <div style="font-size:12px;margin-top:2px;">No overdue projects, unpaid balances, or unread messages.</div>
                </div>
                @else
                <div class="db-attention-grid" style="display:grid;grid-template-columns:1fr 1fr;">
                    {{-- Overdue projects --}}
                    <div style="padding:18px 24px;border-right:1px solid var(--border);min-width:0;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <span style="font-size:11px;font-weight:800;color:var(--muted-light);text-transform:uppercase;letter-spacing:.4px;">Overdue Projects</span>
                            <span class="icon-chip-danger" style="font-size:11px;font-weight:800;padding:3px 9px;border-radius:999px;">{{ $overdueCount }}</span>
                        </div>
                        @forelse($overdueProjectsList as $project)
                        <a href="{{ route('admin.project_view', $project->id) }}" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 0;text-decoration:none;color:inherit;border-bottom:1px solid var(--border);">
                            <div style="min-width:0;">
                                <div style="font-size:12.5px;font-weight:700;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $project->name }}</div>
                                <div style="font-size:11px;color:var(--muted-light);margin-top:1px;">{{ $project->client }}</div>
                            </div>
                            <span style="font-size:11px;font-weight:800;color:#B42318;white-space:nowrap;flex-shrink:0;">{{ (int) $project->end_date->diffInDays(now(), true) }}d late</span>
                        </a>
                        @empty
                        <div style="font-size:12px;color:var(--muted-light);padding:6px 0;">No overdue projects.</div>
                        @endforelse
                    </div>

                    {{-- Pending payments --}}
                    <div style="padding:18px 24px;min-width:0;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <span style="font-size:11px;font-weight:800;color:var(--muted-light);text-transform:uppercase;letter-spacing:.4px;">Pending Payments</span>
                            <span class="icon-chip-warning" style="font-size:11px;font-weight:800;padding:3px 9px;border-radius:999px;">{{ $pendingPaymentsCount }}</span>
                        </div>
                        @forelse($pendingPaymentsList as $payment)
                        <a href="{{ route('admin.payments') }}" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 0;text-decoration:none;color:inherit;border-bottom:1px solid var(--border);">
                            <div style="min-width:0;">
                                <div style="font-size:12.5px;font-weight:700;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $payment->client }}</div>
                                <div style="font-size:11px;color:var(--muted-light);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $payment->project->name ?? 'Unknown project' }}</div>
                            </div>
                            <span style="font-size:11px;font-weight:800;color:#8A6100;white-space:nowrap;flex-shrink:0;">₱{{ number_format($payment->currentBalance(), 0) }}</span>
                        </a>
                        @empty
                        <div style="font-size:12px;color:var(--muted-light);padding:6px 0;">No pending payments.</div>
                        @endforelse
                    </div>
                </div>

                @if($unreadMessages > 0)
                <a href="{{ route('admin.messages') }}" style="display:flex;align-items:center;gap:8px;padding:12px 24px;border-top:1px solid var(--border);background:var(--cream-soft);text-decoration:none;color:var(--dark);font-size:12.5px;font-weight:700;">
                    <i data-lucide="message-square" style="width:14px;height:14px;color:#8A6100;"></i>
                    {{ $unreadMessages }} unread message{{ $unreadMessages !== 1 ? 's' : '' }} waiting for a reply
                    <i data-lucide="arrow-right" style="width:13px;height:13px;margin-left:auto;"></i>
                </a>
                @endif
                @endif
            </div>

            {{-- ── Project Status Snapshot ── --}}
            <div class="table-card" style="margin:0 auto 20px;padding:16px 24px;display:flex;align-items:center;flex-wrap:wrap;gap:18px;width:fit-content;max-width:100%;">
                <div style="display:flex;align-items:center;gap:8px;font-size:11px;font-weight:800;color:var(--muted-light);text-transform:uppercase;letter-spacing:.4px;flex-shrink:0;">
                    <i data-lucide="layers" style="width:14px;height:14px;"></i>
                    Project Status
                </div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    @foreach($statusSnapshot as $item)
                    <a href="{{ route('admin.projects') }}" style="display:flex;align-items:center;gap:7px;text-decoration:none;">
                        <span class="status-badge {{ $item['badge'] }}">{{ $item['label'] }}</span>
                        <span style="font-size:13px;font-weight:800;color:var(--dark);">{{ $item['count'] }}</span>
                    </a>
                    @endforeach
                </div>
            </div>

            {{-- ── Top Client + Top Supplier ── --}}
            <div class="db-top-cards-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

                {{-- Top Client --}}
                <div class="db-top-card" style="background:linear-gradient(135deg,var(--dark) 0%,var(--dark-deep) 100%);border-radius:18px;padding:24px;box-shadow:0 8px 24px rgba(14,20,40,.25);">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;border-bottom:1px solid rgba(255,255,255,.08);padding-bottom:14px;">
                        <div style="width:36px;height:36px;background:rgba(255,255,255,.1);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="crown" style="width:18px;height:18px;color:#FDE74C;"></i>
                        </div>
                        <div>
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:rgba(255,255,255,.4);">Top Client</div>
                            <div style="font-size:13px;font-weight:800;color:#fff;">By number of projects</div>
                        </div>
                    </div>
                    <div id="topClientName" style="font-size:22px;font-weight:900;color:#fff;margin-bottom:12px;">{{ $topClient['name'] ?? '—' }}</div>
                    <div id="topClientStats" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;" {{ !$topClient ? 'style="display:none;"' : '' }}>
                        <div style="background:rgba(255,255,255,.06);border-radius:10px;padding:10px 12px;text-align:center;">
                            <div id="topClientProjects" style="font-size:18px;font-weight:900;color:#FDE74C;">{{ $topClient['projects'] ?? 0 }}</div>
                            <div id="topClientProjectsLabel" style="font-size:10px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;margin-top:2px;">Projects</div>
                        </div>
                        <div style="background:rgba(255,255,255,.06);border-radius:10px;padding:10px 12px;text-align:center;">
                            <div id="topClientCompleted" style="font-size:18px;font-weight:900;color:#4ade80;">{{ $topClient['completed'] ?? 0 }}</div>
                            <div id="topClientCompletedLabel" style="font-size:10px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;margin-top:2px;">Completed</div>
                        </div>
                        <div style="background:rgba(255,255,255,.06);border-radius:10px;padding:10px 12px;text-align:center;">
                            <div id="topClientReceived" style="font-size:14px;font-weight:900;color:#60a5fa;">₱{{ number_format(($topClient['received'] ?? 0) / 1000, 0) }}k</div>
                            <div id="topClientReceivedLabel" style="font-size:10px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;margin-top:2px;">Received</div>
                        </div>
                    </div>
                    <div id="topClientEmpty" style="color:rgba(255,255,255,.3);font-size:13px;text-align:center;padding:20px 0;{{ $topClient ? 'display:none;' : '' }}">No client data yet.</div>
                    <a href="{{ route('admin.clients') }}" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;color:rgba(255,255,255,.5);text-decoration:none;margin-top:16px;">
                        View all clients <i data-lucide="arrow-right" style="width:13px;height:13px;"></i>
                    </a>
                </div>

                {{-- Top Supplier --}}
                <div class="db-top-card" style="background:linear-gradient(135deg,#333333 0%,#2a2a2a 100%);border-radius:18px;padding:24px;box-shadow:0 8px 24px rgba(0,0,0,.2);">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;border-bottom:1px solid rgba(255,255,255,.08);padding-bottom:14px;">
                        <div style="width:36px;height:36px;background:rgba(255,255,255,.1);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="truck" style="width:18px;height:18px;color:#10B981;"></i>
                        </div>
                        <div>
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:rgba(255,255,255,.4);">Top Supplier</div>
                            <div style="font-size:13px;font-weight:800;color:#fff;">By total materials purchased</div>
                        </div>
                    </div>
                    <div id="topSupplierName" style="font-size:22px;font-weight:900;color:#fff;margin-bottom:12px;">{{ $topSupplier['name'] ?? '—' }}</div>
                    <div id="topSupplierStats" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;" {{ !$topSupplier ? 'style="display:none;"' : '' }}>
                        <div style="background:rgba(255,255,255,.06);border-radius:10px;padding:10px 12px;text-align:center;">
                            <div id="topSupplierCount" style="font-size:18px;font-weight:900;color:#10B981;">{{ $topSupplier['purchase_count'] ?? 0 }}</div>
                            <div id="topSupplierCountLabel" style="font-size:10px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;margin-top:2px;">Purchases</div>
                        </div>
                        <div style="background:rgba(255,255,255,.06);border-radius:10px;padding:10px 12px;text-align:center;">
                            <div id="topSupplierSpent" style="font-size:14px;font-weight:900;color:#4ade80;">₱{{ number_format($topSupplier['total_spent'] ?? 0, 2) }}</div>
                            <div id="topSupplierSpentLabel" style="font-size:10px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;margin-top:2px;">Total Spent</div>
                        </div>
                    </div>
                    <div id="topSupplierEmpty" style="color:rgba(255,255,255,.3);font-size:13px;text-align:center;padding:20px 0;{{ $topSupplier ? 'display:none;' : '' }}">No supplier data yet.</div>
                    <a href="{{ route('admin.material_usage') }}" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;color:rgba(255,255,255,.5);text-decoration:none;margin-top:16px;">
                        View suppliers <i data-lucide="arrow-right" style="width:13px;height:13px;"></i>
                    </a>
                </div>
            </div>

            {{-- sections removed per user request --}}
            <div class="db-outer-grid" style="display:none;">

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
                                <div class="db-tc-sub">{{ $topClient['projects'] }} project{{ $topClient['projects'] !== 1 ? 's' : '' }} &nbsp;·&nbsp; {{ $topClient['completed'] }} completed</div>
                            </div>
                        </div>

                        <div class="db-panel-divider"></div>

                        <div class="db-mat-grid">
                            <div class="db-mat-stat">
                                <div class="db-mat-stat-val">{{ $topClient['projects'] }}</div>
                                <div class="db-mat-stat-label">Projects</div>
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
            <div class="db-main-grid" style="margin-bottom:24px;">

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

        // ── Interactive KPI cards → detail chart ──
        (function() {
            var KPI_CARDS_BY_YEAR = @json($kpiCardsByYear);
            var KPI_CARDS         = @json($kpiCards);
            var cardEls    = document.querySelectorAll('.db-kpi-card-interactive');
            var chartEl    = document.getElementById('kpiDetailChart');
            var wrapEl     = document.getElementById('kpiChartWrap');
            var titleEl    = document.getElementById('kpiChartTitle');
            var subtitleEl = document.getElementById('kpiChartSubtitle');
            var iconWrap   = document.getElementById('kpiChartIconWrap');
            var chart      = null;
            var activeKey  = null;

            if (!cardEls.length || !chartEl || typeof Chart === 'undefined') return;

            function findCard(key) {
                for (var i = 0; i < KPI_CARDS.length; i++) {
                    if (KPI_CARDS[i].key === key) return KPI_CARDS[i];
                }
                return null;
            }

            function fmtValue(prefix, v) {
                v = Math.round(v);
                return prefix === '₱' ? ('₱' + v.toLocaleString()) : v.toLocaleString();
            }

            function hexToRgba(hex, alpha) {
                var r = parseInt(hex.slice(1, 3), 16);
                var g = parseInt(hex.slice(3, 5), 16);
                var b = parseInt(hex.slice(5, 7), 16);
                return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
            }

            function buildChart(card) {
                var cfg = card.chart;
                if (chart) { chart.destroy(); chart = null; }

                var dataset = (cfg.type === 'line')
                    ? {
                        label: card.name,
                        data: cfg.data,
                        borderColor: card.color,
                        backgroundColor: hexToRgba(card.color, 0.10),
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointBackgroundColor: card.color,
                        borderWidth: 2.5,
                    }
                    : {
                        label: card.name,
                        data: cfg.data,
                        backgroundColor: cfg.colors || card.color,
                        borderRadius: 6,
                        maxBarThickness: 70,
                    };

                chart = new Chart(chartEl, {
                    type: cfg.type,
                    data: { labels: cfg.labels, datasets: [dataset] },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 650, easing: 'easeOutQuart' },
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1a1a1a',
                                padding: 10,
                                cornerRadius: 8,
                                titleFont: { weight: '700' },
                                bodyFont: { weight: '500' },
                                callbacks: {
                                    label: function(c) {
                                        var v = (c.parsed && c.parsed.y !== undefined) ? c.parsed.y : c.parsed;
                                        return ' ' + fmtValue(cfg.prefix, v);
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: '#666666', font: { size: 11 } } },
                            y: {
                                grid: { color: 'rgba(0,0,0,.05)' },
                                ticks: {
                                    color: '#666666',
                                    font: { size: 11 },
                                    callback: function(v) { return fmtValue(cfg.prefix, v); }
                                }
                            }
                        }
                    }
                });
            }

            function renderSubHtml(card) {
                if (card.trendText) {
                    if (card.trendPct !== null) {
                        var cls   = card.trendGood ? 'db-kpi-trend-up' : 'db-kpi-trend-down';
                        var arrow = card.trendPct >= 0 ? '▲' : '▼';
                        var pct   = Math.abs(card.trendPct).toFixed(1);
                        return '<span class="' + cls + '">' + arrow + ' ' + pct + '%</span> ' + card.trendText;
                    }
                    return card.trendText;
                }
                return card.unitText;
            }

            function renderCardValues() {
                cardEls.forEach(function(el) {
                    var card = findCard(el.dataset.kpiKey);
                    if (!card) return;
                    var valueEl = el.querySelector('.db-kpi-value');
                    var subEl   = el.querySelector('.db-kpi-sub');
                    if (valueEl) valueEl.textContent = card.valueText;
                    if (subEl)   subEl.innerHTML     = renderSubHtml(card);
                });
            }

            function selectCard(key, animate) {
                var card = findCard(key);
                if (!card) return;
                activeKey = key;

                cardEls.forEach(function(el) {
                    var isSel = el.dataset.kpiKey === key;
                    el.classList.toggle('selected', isSel);
                    el.setAttribute('aria-pressed', isSel ? 'true' : 'false');
                });

                titleEl.textContent    = card.chart.title;
                subtitleEl.textContent = card.chart.subtitle;
                iconWrap.style.background = card.bg;
                iconWrap.style.color      = card.color;
                iconWrap.innerHTML = '<i data-lucide="' + card.icon + '" style="width:18px;height:18px;"></i>';
                if (window.lucide) lucide.createIcons();

                if (animate) {
                    wrapEl.style.opacity = '0.25';
                    setTimeout(function() {
                        buildChart(card);
                        wrapEl.style.opacity = '1';
                    }, 160);
                } else {
                    buildChart(card);
                }
            }

            cardEls.forEach(function(el) {
                el.addEventListener('click', function() { selectCard(el.dataset.kpiKey, true); });
                el.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        selectCard(el.dataset.kpiKey, true);
                    }
                });
            });

            selectCard(KPI_CARDS[0].key, false);

            // Bridge for the "Filter by year" dropdown (wired up further below)
            window.updateKpiCardsForYear = function(year) {
                var yearCards = KPI_CARDS_BY_YEAR[year];
                if (!yearCards) return;
                KPI_CARDS = yearCards;
                renderCardValues();
                selectCard(findCard(activeKey) ? activeKey : KPI_CARDS[0].key, true);
            };
        })();

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

        var currentYear = {{ now()->year }};

        // Per-year top client and top supplier — name changes per year, not just stats
        var topClientByYear  = @json($topClientByYear);
        var topSupplierByYear = @json($topSupplierByYear);

        function updateTopClient(year) {
            var data = topClientByYear[year];
            var nameEl = document.getElementById('topClientName');
            var wrapEl = document.getElementById('topClientStats');
            var emptyEl = document.getElementById('topClientEmpty');
            if (!data) {
                if (nameEl)  nameEl.textContent = '—';
                if (wrapEl)  wrapEl.style.display = 'none';
                if (emptyEl) { emptyEl.style.display = ''; emptyEl.textContent = 'No client data for ' + year + '.'; }
                return;
            }
            if (nameEl)  nameEl.textContent = data.name;
            if (wrapEl)  wrapEl.style.display = 'grid';
            if (emptyEl) emptyEl.style.display = 'none';
            var fmt = function(n) { return '₱' + Math.round(n / 1000).toLocaleString() + 'k'; };
            var el;
            el = document.getElementById('topClientProjects');      if(el) el.textContent = data.projects;
            el = document.getElementById('topClientProjectsLabel'); if(el) el.textContent = year + ' Projects';
            el = document.getElementById('topClientCompleted');     if(el) el.textContent = data.completed;
            el = document.getElementById('topClientCompletedLabel');if(el) el.textContent = year + ' Completed';
            el = document.getElementById('topClientReceived');      if(el) el.textContent = fmt(data.received);
            el = document.getElementById('topClientReceivedLabel'); if(el) el.textContent = year + ' Received';
        }
        updateTopClient(currentYear);

        function updateTopSupplier(year) {
            var data = topSupplierByYear[year];
            var nameEl  = document.getElementById('topSupplierName');
            var wrapEl  = document.getElementById('topSupplierStats');
            var emptyEl = document.getElementById('topSupplierEmpty');
            if (!data) {
                if (nameEl)  nameEl.textContent = '—';
                if (wrapEl)  wrapEl.style.display = 'none';
                if (emptyEl) { emptyEl.style.display = ''; emptyEl.textContent = 'No supplier data for ' + year + '.'; }
                return;
            }
            if (nameEl)  nameEl.textContent = data.name;
            if (wrapEl)  wrapEl.style.display = 'grid';
            if (emptyEl) emptyEl.style.display = 'none';
            var el;
            el = document.getElementById('topSupplierCount');       if(el) el.textContent = data.purchase_count;
            el = document.getElementById('topSupplierCountLabel');  if(el) el.textContent = year + ' Purchases';
            el = document.getElementById('topSupplierSpent');       if(el) el.textContent = '₱' + parseFloat(data.total_spent).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
            el = document.getElementById('topSupplierSpentLabel');  if(el) el.textContent = year + ' Total Spent';
        }
        updateTopSupplier(currentYear);

        // Year filter dropdown — update chart AND both cards (name + stats)
        var yearSelect = document.getElementById('dbYearFilterSelect');
        if (yearSelect) {
            yearSelect.addEventListener('change', function() {
                var year = parseInt(this.value);
                updateTopClient(year);
                updateTopSupplier(year);
                if (window.updateKpiCardsForYear) window.updateKpiCardsForYear(year);
            });
        }
        // ─────────────────────────────────────────────────────────────────
    </script>

    <style>

        /* ── Hero ── */
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

        /* ── Top Client / Top Supplier cards ── */
        .db-top-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .db-top-card:hover {
            transform: translateY(-3px);
        }
        .db-top-card a:hover {
            color: #fff !important;
        }

        /* ── KPI strip ── */
        .db-kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
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
        .db-kpi-body { min-width: 0; }
        .db-kpi-value {
            font-size: 26px;
            font-weight: 900;
            color: var(--dark);
            letter-spacing: -0.5px;
            line-height: 1.15;
            overflow-wrap: break-word;
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

        /* ── Interactive KPI cards ── */
        .db-kpi-card-interactive {
            position: relative;
            border: 1px solid var(--border);
            transition: transform 0.18s, box-shadow 0.18s, border-color 0.18s, background 0.18s;
        }
        .db-kpi-card-interactive:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(42,78,170,.22);
        }
        .db-kpi-card-interactive.selected {
            border-color: var(--kpi-accent, var(--dark));
            box-shadow: 0 0 0 1px var(--kpi-accent, var(--dark)), 0 10px 24px rgba(0,0,0,.08);
        }
        .db-kpi-select-check {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--kpi-accent, var(--dark));
            color: #fff;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .db-kpi-select-check i { width: 11px; height: 11px; }
        .db-kpi-card-interactive.selected .db-kpi-select-check { display: flex; }
        .db-kpi-trend-up   { color: #16a34a; font-weight: 800; }
        .db-kpi-trend-down { color: #dc2626; font-weight: 800; }

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
            .db-top-cards-grid     { grid-template-columns: 1fr !important; }
            .db-attention-grid     { grid-template-columns: 1fr !important; }
            .db-attention-grid > div:first-child { border-right: none !important; border-bottom: 1px solid var(--border); }
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
