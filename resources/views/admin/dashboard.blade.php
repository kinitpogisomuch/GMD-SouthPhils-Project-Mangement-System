<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}?v={{ @filemtime(public_path('css/admin.css')) }}" rel="stylesheet">
</head>
<body class="page-enter pg-admin-dashboard">

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

                $attnTotal = $overdueCount + $pendingPaymentsCount + $unreadMessages;
            @endphp

            {{-- ── Header bar: greeting, date and the year every figure below is scoped to ── --}}
            <div class="ad-head">
                <div>
                    <div class="ad-eyebrow">Admin Dashboard</div>
                    <h1 class="ad-title">{{ $greeting }}, {{ $adminFirstName }}</h1>
                    <div class="ad-sub">Here's where your projects and payments stand today.</div>
                </div>
                <div class="ad-head-right">
                    <div class="ad-chip"><i data-lucide="calendar-days"></i> {{ now()->format('D, M j, Y') }}</div>
                    <label class="ad-year">
                        <i data-lucide="calendar-range"></i>
                        <span>Year</span>
                        <select id="dbYearFilterSelect" aria-label="Filter by year">
                            @foreach($availableYears as $yr)
                            <option value="{{ $yr }}" {{ $yr == $currentYear ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>

            {{-- ── Quick actions ── --}}
            <div class="ad-actions">
                <a href="{{ route('admin.projects') }}" class="ad-action">
                    <span class="ad-action-icon" style="background:#EAF0FF;color:#2A4EAA;"><i data-lucide="folder-plus"></i></span>
                    <span><strong>Projects</strong><small>{{ $activeProjects }} active</small></span>
                    <i data-lucide="chevron-right" class="ad-action-go"></i>
                </a>
                <a href="{{ route('admin.project_materials') }}" class="ad-action">
                    <span class="ad-action-icon" style="background:#FFF4E0;color:#B45309;"><i data-lucide="file-text"></i></span>
                    <span><strong>Quotations</strong><small>{{ $quotationsToCost > 0 ? $quotationsToCost . ' to cost' : 'All caught up' }}</small></span>
                    @if($quotationsToCost > 0)<span class="ad-badge">{{ $quotationsToCost }}</span>@endif
                    <i data-lucide="chevron-right" class="ad-action-go"></i>
                </a>
                <a href="{{ route('admin.payments') }}" class="ad-action">
                    <span class="ad-action-icon" style="background:#E7F6EC;color:#207A3A;"><i data-lucide="wallet"></i></span>
                    <span><strong>Payments</strong><small>{{ $pendingPaymentsCount }} with a balance</small></span>
                    <i data-lucide="chevron-right" class="ad-action-go"></i>
                </a>
                {{-- new client sign-ups (email verified) waiting for the admin's approval --}}
                <a href="{{ route('admin.clients', ['status' => 'Pending']) }}" class="ad-action">
                    <span class="ad-action-icon" style="background:#F3E8FF;color:#7C3AED;"><i data-lucide="user-check"></i></span>
                    <span><strong>Client Approvals</strong><small>{{ $pendingClientApprovals > 0 ? $pendingClientApprovals . ' waiting for approval' : 'No sign-ups waiting' }}</small></span>
                    @if($pendingClientApprovals > 0)<span class="ad-badge">{{ $pendingClientApprovals }}</span>@endif
                    <i data-lucide="chevron-right" class="ad-action-go"></i>
                </a>
            </div>

            {{-- ── Interactive KPI cards (click one to change the chart) ── --}}
            <div class="db-kpi-row" id="kpiCardRow" style="margin-bottom:18px;">
                @foreach($kpiCards as $i => $card)
                <div class="db-kpi-card db-kpi-card-interactive{{ $i === 0 ? ' selected' : '' }}"
                     data-kpi-key="{{ $card['key'] }}"
                     role="button"
                     tabindex="0"
                     aria-pressed="{{ $i === 0 ? 'true' : 'false' }}"
                     style="--kpi-accent:{{ $card['color'] }};">
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

            {{-- ── Row: chart (2/3) + top client & supplier (1/3) ── --}}
            <div class="ad-grid ad-row-main">
                <div class="ad-card">
                    <div class="ad-card-head">
                        <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                            <div style="min-width:0;">
                                <div id="kpiChartTitle" class="ad-card-title">{{ $kpiCards[0]['chart']['title'] }}</div>
                                <div id="kpiChartSubtitle" class="ad-card-sub">{{ $kpiCards[0]['chart']['subtitle'] }}</div>
                            </div>
                        </div>
                        <span class="ad-hint"><i data-lucide="mouse-pointer-click"></i> Pick a card above to switch</span>
                    </div>
                    {{-- the chart fills the card, which is as tall as the column beside it --}}
                    <div style="padding:18px 22px 20px;flex:1;display:flex;flex-direction:column;">
                        <div id="kpiChartWrap" style="position:relative;flex:1;min-height:300px;transition:opacity .18s ease;">
                            <canvas id="kpiDetailChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="ad-top-pair">
                    {{-- Top Client --}}
                    <div class="ad-top ad-top--client">
                        <div class="ad-top-head">
                            <span class="ad-top-icon"><i data-lucide="crown" style="color:#F5B400;"></i></span>
                            <div style="min-width:0;"><div class="ad-top-eyebrow">Top Client</div><div class="ad-top-by">By number of projects</div></div>
                            <span class="ad-top-year" data-top-year>{{ $currentYear }}</span>
                        </div>
                        <div class="ad-top-id" id="topClientId" style="{{ !$topClient ? 'display:none;' : '' }}">
                            <span class="ad-top-avatar" id="topClientInitial">{{ strtoupper(mb_substr($topClient['name'] ?? '—', 0, 1)) }}</span>
                            <div id="topClientName" class="ad-top-name" title="{{ $topClient['name'] ?? '' }}">{{ $topClient['name'] ?? '—' }}</div>
                        </div>
                        <div id="topClientStats" class="ad-top-stats" style="grid-template-columns:repeat(3,1fr);{{ !$topClient ? 'display:none;' : '' }}">
                            <div><div id="topClientProjects" style="color:#F5B400;">{{ $topClient['projects'] ?? 0 }}</div><span id="topClientProjectsLabel">Projects</span></div>
                            <div><div id="topClientCompleted" style="color:#4ade80;">{{ $topClient['completed'] ?? 0 }}</div><span id="topClientCompletedLabel">Completed</span></div>
                            <div><div id="topClientReceived" style="color:#60a5fa;">₱{{ number_format(($topClient['received'] ?? 0) / 1000, 0) }}k</div><span id="topClientReceivedLabel">Received</span></div>
                        </div>
                        <div id="topClientEmpty" class="ad-top-empty" style="{{ $topClient ? 'display:none;' : '' }}">No client data yet.</div>
                        <a href="{{ route('admin.clients') }}" class="ad-top-link">View all clients <i data-lucide="arrow-right"></i></a>
                    </div>

                    {{-- Top Supplier --}}
                    <div class="ad-top ad-top--supplier">
                        <div class="ad-top-head">
                            <span class="ad-top-icon"><i data-lucide="truck" style="color:#10B981;"></i></span>
                            <div style="min-width:0;"><div class="ad-top-eyebrow">Top Supplier</div><div class="ad-top-by">By total materials purchased</div></div>
                            <span class="ad-top-year" data-top-year>{{ $currentYear }}</span>
                        </div>
                        <div class="ad-top-id" id="topSupplierId" style="{{ !$topSupplier ? 'display:none;' : '' }}">
                            <span class="ad-top-avatar is-green" id="topSupplierInitial">{{ strtoupper(mb_substr($topSupplier['name'] ?? '—', 0, 1)) }}</span>
                            <div id="topSupplierName" class="ad-top-name" title="{{ $topSupplier['name'] ?? '' }}">{{ $topSupplier['name'] ?? '—' }}</div>
                        </div>
                        <div id="topSupplierStats" class="ad-top-stats" style="grid-template-columns:1fr 1.4fr;{{ !$topSupplier ? 'display:none;' : '' }}">
                            <div><div id="topSupplierCount" style="color:#10B981;">{{ $topSupplier['purchase_count'] ?? 0 }}</div><span id="topSupplierCountLabel">Purchases</span></div>
                            <div><div id="topSupplierSpent" style="color:#4ade80;">₱{{ number_format($topSupplier['total_spent'] ?? 0, 2) }}</div><span id="topSupplierSpentLabel">Total Spent</span></div>
                        </div>
                        <div id="topSupplierEmpty" class="ad-top-empty" style="{{ $topSupplier ? 'display:none;' : '' }}">No supplier data yet.</div>
                        <a href="{{ route('admin.material_usage') }}" class="ad-top-link">View suppliers <i data-lucide="arrow-right"></i></a>
                    </div>
                </div>
            </div>

            {{-- ── Row: needs attention (2/3) + operations (1/3) ── --}}
            <div class="ad-grid ad-row-mid">
                {{-- Needs attention --}}
                <div class="ad-card ad-attn-card">
                    <div class="ad-card-head">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="ad-card-icon" style="background:#FFF3D6;color:#8A6100;"><i data-lucide="alert-triangle" style="width:18px;height:18px;"></i></div>
                            <div>
                                <div class="ad-card-title">Needs Attention</div>
                                <div class="ad-card-sub">{{ $attnTotal > 0 ? $attnTotal . ' item' . ($attnTotal === 1 ? '' : 's') : 'All caught up' }}</div>
                            </div>
                        </div>
                    </div>

                    @if($attnTotal === 0)
                    <div class="ad-empty ad-scroll">
                        <i data-lucide="check-circle-2"></i>
                        <strong>All caught up</strong>
                        <span>No overdue projects, unpaid balances, or unread messages.</span>
                    </div>
                    @else
                    <div class="ad-attn ad-scroll">
                        <div class="ad-attn-group">
                            <div class="ad-attn-label">Overdue projects <span class="ad-pill is-red">{{ $overdueCount }}</span></div>
                            @forelse($overdueProjectsList as $project)
                            <a href="{{ route('admin.project_view', $project->id) }}" class="ad-attn-row">
                                <span class="ad-attn-main"><strong>{{ $project->name }}</strong><small>{{ $project->live_client_name }}</small></span>
                                <span class="ad-attn-val is-red">{{ (int) $project->end_date->diffInDays(now(), true) }}d late</span>
                            </a>
                            @empty
                            <div class="ad-attn-none">No overdue projects.</div>
                            @endforelse
                        </div>
                        <div class="ad-attn-group">
                            <div class="ad-attn-label">Unpaid balances <span class="ad-pill is-amber">{{ $pendingPaymentsCount }}</span></div>
                            @forelse($pendingPaymentsList as $payment)
                            <a href="{{ route('admin.payments.show', $payment->id) }}" class="ad-attn-row">
                                <span class="ad-attn-main"><strong>{{ $payment->client }}</strong><small>{{ $payment->project->name ?? 'Unknown project' }}</small></span>
                                <span class="ad-attn-val is-amber">₱{{ number_format($payment->currentBalance(), 0) }}</span>
                            </a>
                            @empty
                            <div class="ad-attn-none">No unpaid balances.</div>
                            @endforelse
                        </div>
                        @if($unreadMessages > 0)
                        <a href="{{ route('admin.messages') }}" class="ad-attn-msg">
                            <i data-lucide="message-square"></i>
                            {{ $unreadMessages }} unread message{{ $unreadMessages !== 1 ? 's' : '' }}
                            <i data-lucide="arrow-right" style="margin-left:auto;"></i>
                        </a>
                        @endif
                    </div>
                    @endif
                </div>

                {{-- Operations snapshot --}}
                <div class="ad-card">
                    <div class="ad-card-head">
                        <div>
                            <div class="ad-card-title">Operations</div>
                            <div class="ad-card-sub">Across the whole company</div>
                        </div>
                    </div>
                    <div class="ad-ops">
                        <a href="{{ route('admin.projects') }}" class="ad-op"><i data-lucide="hammer"></i><strong>{{ $activeProjects }}</strong><span>Active projects</span></a>
                        <a href="{{ route('admin.employees') }}" class="ad-op"><i data-lucide="hard-hat"></i><strong>{{ $totalEmployees }}</strong><span>Active employees</span></a>
                        <a href="{{ route('admin.clients') }}" class="ad-op"><i data-lucide="building-2"></i><strong>{{ $totalClients }}</strong><span>Clients</span></a>
                        <a href="{{ route('admin.projects', ['tab' => 'completed']) }}" class="ad-op"><i data-lucide="badge-check"></i><strong>{{ $completedProjects }}</strong><span>Completed Projects</span></a>
                    </div>
                </div>
            </div>

            {{-- ── Row: recent projects (2/3) + recent activity (1/3) ── --}}
            <div class="ad-grid ad-row-low" style="margin-bottom:24px;">
                <div class="db-project-card ad-card" style="margin:0;">
                    <div class="db-project-card-head">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <i data-lucide="folder-kanban" style="width:16px;height:16px;color:var(--muted);"></i>
                            <span class="db-section-title">Recent Projects</span>
                        </div>
                        <span class="db-project-count">{{ $projects->count() }} shown</span>
                    </div>

                    <div class="db-proj-table-head">
                        <span></span>
                        <span>Project</span>
                        <span>Client</span>
                        <span>Date</span>
                        <span>Status</span>
                        <span>Progress</span>
                    </div>

                    <div class="db-project-list ad-scroll">
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
                            <div class="db-row-accent" style="background:{{ $badge['color'] }};"></div>
                            <div class="db-project-info">
                                <span class="db-project-name">{{ $project->name }}</span>
                            </div>
                            <div class="db-project-client">{{ $project->live_client_name }}</div>
                            <div class="db-project-date">{{ $project->start_date->format('M j, Y') }}</div>
                            <span class="status-badge {{ $badge['css'] }}" style="justify-self:start;">{{ $badge['label'] }}</span>
                            <div class="db-progress-wrap">
                                <div class="db-progress-bar">
                                    <div class="db-progress-fill" data-width="{{ $progress }}" data-color="{{ $progressColor }}"></div>
                                </div>
                                <span class="db-progress-label">{{ $progress }}%</span>
                            </div>
                        </a>
                        @empty
                        <div style="text-align:center;padding:40px 20px;color:var(--muted);font-size:14px;">
                            No projects yet.
                        </div>
                        @endforelse
                    </div>

                    <a href="{{ route('admin.projects') }}" class="db-project-card-footer">
                        <span>View all projects</span>
                        <i data-lucide="arrow-right"></i>
                    </a>
                </div>

                {{-- Recent activity --}}
                <div class="ad-card">
                    <div class="ad-card-head">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="ad-card-icon" style="background:#F1F5F9;color:#334155;"><i data-lucide="activity" style="width:18px;height:18px;"></i></div>
                            <div>
                                <div class="ad-card-title">Recent Activity</div>
                                <div class="ad-card-sub">Quotations &amp; online payments</div>
                            </div>
                        </div>
                    </div>
                    <div class="ad-feed ad-scroll">
                        @forelse($recentActivity as $log)
                        @php
                            $feedUrl = match ($log->auditable_type) {
                                'quotation' => route('admin.quotation_requests.batch_detail', $log->auditable_id),
                                'payment'   => route('admin.payments.show', $log->auditable_id),
                                default     => null,
                            };
                            $tone = str_contains($log->action, 'approved') || str_contains($log->action, 'paid') || str_contains($log->action, 'converted') ? 'is-green'
                                  : (str_contains($log->action, 'rejected') || str_contains($log->action, 'failed') || str_contains($log->action, 'declined') ? 'is-red'
                                  : (str_contains($log->action, 'sent') ? 'is-violet' : ''));
                        @endphp
                        <a @if($feedUrl) href="{{ $feedUrl }}" @endif class="ad-feed-row {{ $feedUrl ? '' : 'is-static' }}">
                            <span class="ad-feed-dot {{ $tone }}"></span>
                            <span class="ad-feed-main">
                                <strong>{{ $log->label }}</strong>
                                <small>{{ $log->created_at?->diffForHumans() }}{{ $log->actor_name ? ' · ' . $log->actor_name : '' }}</small>
                            </span>
                        </a>
                        @empty
                        <div class="ad-attn-none" style="padding:16px 20px;">No activity yet.</div>
                        @endforelse
                    </div>
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

            function fmtCompact(prefix, v) {
                var a = Math.abs(v), s;
                if (a >= 1e6)      s = (v / 1e6).toFixed(a % 1e6 === 0 ? 0 : 1).replace(/\.0$/, '') + 'M';
                else if (a >= 1e3) s = Math.round(v / 1e3) + 'k';
                else               s = String(Math.round(v));
                return (prefix === '₱' ? '₱' : '') + s;
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
                        // passes through every point without overshooting (no false peaks or dips below ₱0)
                        cubicInterpolationMode: 'monotone',
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
                            x: { grid: { display: false }, ticks: { color: '#666666', font: { size: 11 }, maxRotation: 0, autoSkip: true, autoSkipPadding: 8 } },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0,0,0,.05)' },
                                border: { display: false },
                                ticks: {
                                    color: '#666666',
                                    font: { size: 11 },
                                    maxTicksLimit: 6,
                                    // short axis labels (₱1.6M, ₱400k); the tooltip still shows the exact amount
                                    callback: function(v) { return fmtCompact(cfg.prefix, v); }
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
                if (iconWrap) {   // the chart title no longer has an icon; kept safe if one is added back
                    iconWrap.style.background = card.bg;
                    iconWrap.style.color      = card.color;
                    iconWrap.innerHTML = '<i data-lucide="' + card.icon + '" style="width:18px;height:18px;"></i>';
                    if (window.lucide) lucide.createIcons();
                }

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


        var currentYear = {{ now()->year }};

        // Per-year top client and top supplier — name changes per year, not just stats
        var topClientByYear  = @json($topClientByYear);
        var topSupplierByYear = @json($topSupplierByYear);

        // ── Top client / supplier cards: name, avatar initial, and stats ──
        function setText(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; }
        function setShown(id, show, disp) { var el = document.getElementById(id); if (el) el.style.display = show ? (disp || '') : 'none'; }
        function shortPeso(n) {
            n = Number(n) || 0;
            return n >= 1e6 ? '₱' + (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M' : '₱' + Math.round(n / 1000).toLocaleString() + 'k';
        }
        function initialOf(name) { return (String(name || '').trim().charAt(0) || '—').toUpperCase(); }

        function updateTopClient(year) {
            var data = topClientByYear[year];
            document.querySelectorAll('.ad-top--client [data-top-year]').forEach(function (el) { el.textContent = year; });
            setShown('topClientId', !!data, 'flex');
            setShown('topClientStats', !!data, 'grid');
            setShown('topClientEmpty', !data);
            if (!data) { setText('topClientEmpty', 'No client data for ' + year + '.'); return; }

            setText('topClientName', data.name);
            var nameEl = document.getElementById('topClientName'); if (nameEl) nameEl.title = data.name;
            setText('topClientInitial', initialOf(data.name));
            setText('topClientProjects', data.projects);
            setText('topClientProjectsLabel', 'Projects');
            setText('topClientCompleted', data.completed);
            setText('topClientCompletedLabel', 'Completed');
            setText('topClientReceived', shortPeso(data.received));
            setText('topClientReceivedLabel', 'Received');
        }
        updateTopClient(currentYear);

        function updateTopSupplier(year) {
            var data = topSupplierByYear[year];
            document.querySelectorAll('.ad-top--supplier [data-top-year]').forEach(function (el) { el.textContent = year; });
            setShown('topSupplierId', !!data, 'flex');
            setShown('topSupplierStats', !!data, 'grid');
            setShown('topSupplierEmpty', !data);
            if (!data) { setText('topSupplierEmpty', 'No supplier data for ' + year + '.'); return; }

            setText('topSupplierName', data.name);
            var nameEl = document.getElementById('topSupplierName'); if (nameEl) nameEl.title = data.name;
            setText('topSupplierInitial', initialOf(data.name));
            setText('topSupplierCount', data.purchase_count);
            setText('topSupplierCountLabel', 'Purchases');
            setText('topSupplierSpent', '₱' + parseFloat(data.total_spent).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}));
            setText('topSupplierSpentLabel', 'Total Spent');
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


</body>
</html>
