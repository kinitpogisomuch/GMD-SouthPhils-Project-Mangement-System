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

                $qNew      = $quotationPipeline[0]['count'] + $quotationPipeline[1]['count'];
                $attnTotal = $overdueCount + $pendingPaymentsCount + $unreadMessages;
            @endphp

            {{-- ── Header bar: greeting, date and the year every figure below is scoped to ── --}}
            <div class="ad-head">
                <div>
                    <div class="ad-eyebrow">Admin Dashboard</div>
                    <h1 class="ad-title">{{ $greeting }}, {{ $adminFirstName }}</h1>
                    <p class="ad-sub">
                        @if($attnTotal > 0)
                            <strong>{{ $attnTotal }}</strong> {{ $attnTotal === 1 ? 'thing needs' : 'things need' }} your attention today.
                        @else
                            Everything is on track today.
                        @endif
                    </p>
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
                    <span><strong>Quotations</strong><small>{{ $qNew > 0 ? $qNew . ' to cost' : 'All caught up' }}</small></span>
                    @if($qNew > 0)<span class="ad-badge">{{ $qNew }}</span>@endif
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

            {{-- ── Row: chart (2/3) + quotation pipeline & needs attention (1/3) ── --}}
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

                <div class="ad-stack">
                    {{-- Quotation pipeline --}}
                    <div class="ad-card">
                        <div class="ad-card-head">
                            <div>
                                <div class="ad-card-title">Quotation Pipeline</div>
                                <div class="ad-card-sub">Client requests, right now</div>
                            </div>
                            <a href="{{ route('admin.project_materials') }}" class="ad-link">Open <i data-lucide="arrow-right"></i></a>
                        </div>
                        <div class="ad-pipe ad-scroll">
                            @foreach($quotationPipeline as $stage)
                            <a href="{{ route('admin.project_materials') }}" class="ad-pipe-row">
                                <span class="ad-pipe-icon" style="background:{{ $stage['bg'] }};color:{{ $stage['color'] }};"><i data-lucide="{{ $stage['icon'] }}"></i></span>
                                <span class="ad-pipe-text"><strong>{{ $stage['label'] }}</strong><small>{{ $stage['sub'] }}</small></span>
                                <span class="ad-pipe-count" style="{{ $stage['count'] > 0 ? 'color:' . $stage['color'] . ';' : '' }}">{{ $stage['count'] }}</span>
                            </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Needs attention --}}
                    <div class="ad-card">
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
                </div>
            </div>

            {{-- ── Row: top client & supplier (2/3) + operations (1/3) ── --}}
            <div class="ad-grid ad-row-mid">
                <div class="ad-top-pair">
                    {{-- Top Client --}}
                    <div class="ad-top ad-top--client">
                        <i data-lucide="crown" class="ad-top-bg" aria-hidden="true"></i>
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
                        <i data-lucide="truck" class="ad-top-bg" aria-hidden="true"></i>
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
                        <a href="{{ route('admin.projects', ['tab' => 'completed']) }}" class="ad-op"><i data-lucide="badge-check"></i><strong>{{ $completedProjects }}</strong><span>Completed</span></a>
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
        /* Grid columns shared by the header row and every project row, so
           every column lines up regardless of how short/long each cell's
           content is — avoids the old flex:1 layout where a short project
           name left a big dead gap before the right-pinned status/progress. */
        .db-proj-table-head,
        .db-project-row {
            display: grid;
            grid-template-columns: 3px minmax(0, 1.8fr) minmax(0, 1fr) 100px 112px 160px;
            column-gap: 16px;
            align-items: center;
        }
        .db-proj-table-head {
            padding: 9px 20px 9px 0;
            background: var(--cream-soft);
            border-bottom: 1px solid var(--border);
            font-size: 11px;
            font-weight: 800;
            color: var(--muted-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .db-proj-table-head span:nth-child(2) { padding-left: 14px; }

        /* ── Project list rows ── */
        .db-project-row {
            padding: 14px 20px 14px 0;
            border-bottom: 1px solid var(--border);
            transition: background 0.15s;
            cursor: pointer;
        }
        .db-project-row:last-child { border-bottom: none; }
        .db-project-row:hover { background: var(--cream-soft); }

        /* Colored left accent strip */
        .db-row-accent {
            align-self: stretch;
            border-radius: 0;
            opacity: 0.7;
        }

        /* Info section */
        .db-project-info { min-width: 0; padding-left: 14px; }

        .db-project-name {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .db-project-client,
        .db-project-date {
            min-width: 0;
            font-size: 12.5px;
            color: var(--muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .db-project-date { color: var(--muted-light); }

        /* Right section: status + progress */
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

        /* ── Needs Attention (full width) ── */
        .db-attn-row { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; margin-bottom: 20px; }
        .db-attn-row > * { min-width: 0; }

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
            .db-attn-row           { grid-template-columns: 1fr; }
        }

        /* Small tablet (≤768px): hero stacks, 3-panel → 1-col, hide table columns */
        @media (max-width: 768px) {
            .db-hero               { flex-direction: column; align-items: flex-start; padding: 18px 20px; gap: 12px; }
            .db-greeting           { font-size: 20px; }
            .db-three-panel-row    { grid-template-columns: 1fr; }
            .db-proj-table-head    { display: none; }
            .db-chart-filters      { overflow-x: auto; flex-wrap: nowrap; -webkit-overflow-scrolling: touch; }
            .db-chart-wrap         { height: 190px; }
            /* Project rows: fixed-width desktop grid columns don't fit a narrow
               screen, so stack everything into one column instead. */
            .db-project-row {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
                padding: 12px 16px 12px 14px;
                position: relative;
            }
            .db-row-accent         { position: absolute; left: 0; top: 0; bottom: 0; width: 3px; }
            .db-project-info,
            .db-project-client,
            .db-project-date       { padding-left: 0; }
            .db-project-row .status-badge { margin-top: 2px; }
            .db-progress-wrap      { width: 100%; margin-top: 2px; }
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
            /* two cards per row: icon above the figure so a ₱ amount stays on one line */
            .db-kpi-card           { flex-direction: column; gap: 10px; padding: 14px; }
            .db-kpi-icon           { width: 34px; height: 34px; border-radius: 10px; }
            .db-kpi-icon i         { width: 17px; height: 17px; }
            .db-kpi-body           { width: 100%; }
            .db-kpi-value          { font-size: clamp(17px, 5.4vw, 22px); white-space: nowrap; overflow-wrap: normal; }
            .db-kpi-label          { font-size: 10.5px; }
            .db-kpi-sub            { font-size: 11px; }
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

        /* ══════════ Admin dashboard layout (ad-*) ══════════ */
        .ad-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
        .ad-eyebrow { display: inline-flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); }
        .ad-eyebrow::before { content: ''; width: 22px; height: 3px; border-radius: 999px; background: #F5B400; }
        .ad-title { margin: 6px 0 4px; font-size: 28px; font-weight: 900; letter-spacing: -.5px; color: var(--dark); }
        .ad-sub { margin: 0; font-size: 13.5px; color: var(--muted); }
        .ad-sub strong { color: #B45309; }
        .ad-head-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .ad-chip, .ad-year { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 14px; border-radius: 12px; border: 1px solid var(--border);
                             background: var(--white); font-size: 13px; font-weight: 700; color: var(--dark); }
        .ad-chip svg, .ad-year svg { width: 15px; height: 15px; color: var(--muted); }
        .ad-year span { color: var(--muted); font-weight: 700; }
        .ad-year select { border: none; outline: none; background: transparent; font: inherit; font-weight: 900; color: var(--dark); cursor: pointer; padding-right: 2px; }
        .ad-year:focus-within { border-color: var(--dark); box-shadow: 0 0 0 3px rgba(245,180,0,.25); }

        .ad-actions { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px; }
        .ad-action { position: relative; display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--border);
                     background: var(--white); color: var(--dark); text-decoration: none; transition: border-color .15s, box-shadow .15s, transform .15s; }
        .ad-action:hover { border-color: #cfcfcf; box-shadow: 0 8px 20px rgba(0,0,0,.06); transform: translateY(-1px); }
        .ad-action-icon { width: 38px; height: 38px; border-radius: 11px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
        .ad-action-icon svg { width: 18px; height: 18px; }
        .ad-action strong { display: block; font-size: 13.5px; font-weight: 800; }
        .ad-action small { display: block; margin-top: 1px; font-size: 11.5px; font-weight: 600; color: var(--muted); }
        .ad-action-go { width: 16px; height: 16px; margin-left: auto; color: #c4c4c4; flex-shrink: 0; transition: transform .15s, color .15s; }
        .ad-action:hover .ad-action-go { color: var(--dark); transform: translateX(2px); }
        .ad-badge { position: absolute; top: -7px; right: -7px; min-width: 22px; height: 22px; padding: 0 6px; border-radius: 999px; background: #F5B400; color: #1a1a1a;
                    font-size: 11px; font-weight: 900; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 0 3px var(--cream, #f5f5f5); }


        .ad-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 18px; margin-bottom: 18px; align-items: stretch; }
        .ad-stack { display: flex; flex-direction: column; gap: 18px; min-width: 0; }
        .ad-card { background: var(--white); border: 1px solid var(--border); border-radius: 18px; min-width: 0; overflow: hidden;
                   box-shadow: 0 1px 2px rgba(0,0,0,.03); display: flex; flex-direction: column; }
        .ad-card-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 16px 20px; border-bottom: 1px solid var(--border); }
        .ad-card-icon { width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
        .ad-card-title { font-size: 14.5px; font-weight: 800; color: var(--dark); }
        .ad-card-sub { margin-top: 2px; font-size: 12px; color: var(--muted); }
        .ad-hint { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; color: var(--muted); white-space: nowrap; }
        .ad-hint svg { width: 13px; height: 13px; }
        .ad-link { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 800; color: var(--dark); text-decoration: none; }
        .ad-link svg { width: 13px; height: 13px; transition: transform .15s; }
        .ad-link:hover svg { transform: translateX(2px); }

        .ad-pipe { padding: 6px 10px 10px; }
        .ad-pipe-row { display: flex; align-items: center; gap: 12px; padding: 9px 10px; border-radius: 12px; text-decoration: none; color: var(--dark); transition: background .15s; }
        .ad-pipe-row:hover { background: var(--cream-soft, #fafafa); }
        .ad-pipe-icon { width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
        .ad-pipe-icon svg { width: 16px; height: 16px; }
        .ad-pipe-text { flex: 1; min-width: 0; }
        .ad-pipe-text strong { display: block; font-size: 13px; font-weight: 800; }
        .ad-pipe-text small { display: block; font-size: 11.5px; color: var(--muted); }
        .ad-pipe-count { font-size: 20px; font-weight: 900; color: #c4c4c4; font-variant-numeric: tabular-nums; }

        .ad-ops { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 14px; }
        .ad-op { display: flex; flex-direction: column; gap: 2px; padding: 12px 14px; border-radius: 14px; background: var(--cream-soft, #fafafa); border: 1px solid var(--border);
                 text-decoration: none; color: var(--dark); transition: border-color .15s, background .15s; }
        .ad-op:hover { border-color: #cfcfcf; background: var(--white); }
        .ad-op svg { width: 16px; height: 16px; color: var(--muted); margin-bottom: 4px; }
        .ad-op strong { font-size: 22px; font-weight: 900; line-height: 1.1; font-variant-numeric: tabular-nums; }
        .ad-op span { font-size: 11.5px; font-weight: 600; color: var(--muted); }
        /* beside Recent Projects the card is tall — the four tiles share the height evenly */
        .ad-grid > .ad-card > .ad-ops { flex: 1; grid-auto-rows: 1fr; }
        .ad-grid > .ad-card > .ad-ops .ad-op { justify-content: center; }
        /* beside the top cards the row is short — icon moves to the tile's corner so all four fit */
        .ad-row-mid .ad-ops { gap: 8px; padding: 10px 14px 14px; }
        .ad-row-mid .ad-op { position: relative; padding: 8px 14px; }
        .ad-row-mid .ad-op svg { position: absolute; top: 10px; right: 12px; margin: 0; }

        .ad-grid > .db-project-card { display: flex; flex-direction: column; }
        .ad-grid > .db-project-card .db-project-card-footer { margin-top: auto; }

        .ad-empty { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 4px; padding: 36px 20px; color: var(--muted); font-size: 12.5px; }
        .ad-empty svg { width: 32px; height: 32px; color: #16a34a; margin-bottom: 6px; }
        .ad-empty strong { color: var(--dark); font-size: 13.5px; }
        .ad-attn { padding: 6px 0 4px; flex: 1; }
        .ad-attn-group { padding: 10px 20px; }
        .ad-attn-group + .ad-attn-group { border-top: 1px dashed var(--border); }
        .ad-attn-label { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
        .ad-pill { font-size: 11px; font-weight: 900; padding: 2px 9px; border-radius: 999px; letter-spacing: 0; }
        .ad-pill.is-red { background: #FEE2E2; color: #B91C1C; }
        .ad-pill.is-amber { background: #FFF4E0; color: #B45309; }
        .ad-attn-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 7px 8px; margin: 0 -8px; border-radius: 10px; text-decoration: none; color: inherit; }
        .ad-attn-row:hover { background: var(--cream-soft, #fafafa); }
        .ad-attn-main { min-width: 0; }
        .ad-attn-main strong { display: block; font-size: 12.5px; font-weight: 700; color: var(--dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ad-attn-main small { display: block; font-size: 11px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ad-attn-val { font-size: 12px; font-weight: 900; white-space: nowrap; flex-shrink: 0; }
        .ad-attn-val.is-red { color: #B42318; }
        .ad-attn-val.is-amber { color: #8A6100; }
        .ad-attn-none { font-size: 12px; color: var(--muted); padding: 4px 0; }
        .ad-attn-msg { display: flex; align-items: center; gap: 8px; margin: 6px 20px 10px; padding: 10px 12px; border-radius: 12px; background: #F3E8FF; color: #6D28D9;
                       font-size: 12.5px; font-weight: 800; text-decoration: none; }
        .ad-attn-msg svg { width: 14px; height: 14px; }

        .ad-top-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; min-width: 0; }
        .ad-top { display: flex; flex-direction: column; padding: 20px; border-radius: 18px; color: #fff; min-width: 0;
                  background: linear-gradient(135deg, #333 0%, #1a1a1a 100%); box-shadow: 0 8px 24px rgba(0,0,0,.18); position: relative; overflow: hidden; }
        .ad-top::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 4px; background: repeating-linear-gradient(135deg, #F5B400 0 10px, rgba(0,0,0,.5) 10px 20px); opacity: .9; }
        .ad-top > *:not(.ad-top-bg) { position: relative; z-index: 1; }
        .ad-top-bg { position: absolute; right: -26px; top: -22px; width: 150px; height: 150px; color: #fff; opacity: .045; stroke-width: 1.4; pointer-events: none; transform: rotate(-12deg); }
        .ad-top--client  { background: radial-gradient(120% 90% at 100% 0%, rgba(245,180,0,.16) 0%, transparent 55%), linear-gradient(135deg, #333 0%, #1a1a1a 100%); }
        .ad-top--supplier { background: radial-gradient(120% 90% at 100% 0%, rgba(16,185,129,.15) 0%, transparent 55%), linear-gradient(135deg, #333 0%, #1a1a1a 100%); }
        .ad-top-head { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
        .ad-top-icon { width: 34px; height: 34px; border-radius: 10px; background: rgba(255,255,255,.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .ad-top-icon svg { width: 17px; height: 17px; }
        .ad-top-eyebrow { font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: rgba(255,255,255,.45); }
        .ad-top-by { font-size: 12.5px; font-weight: 800; color: #fff; }
        .ad-top-year { margin-left: auto; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; color: #F5B400; background: rgba(245,180,0,.12); border: 1px solid rgba(245,180,0,.28); }
        .ad-top--supplier .ad-top-year { color: #34D399; background: rgba(16,185,129,.12); border-color: rgba(16,185,129,.3); }
        .ad-top-id { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; min-width: 0; }
        .ad-top-avatar { width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
                         font-size: 18px; font-weight: 900; color: #1a1a1a; background: linear-gradient(135deg, #FFD54A, #F5B400); box-shadow: 0 4px 12px rgba(245,180,0,.3); }
        .ad-top-avatar.is-green { color: #fff; background: linear-gradient(135deg, #34D399, #059669); box-shadow: 0 4px 12px rgba(16,185,129,.3); }
        .ad-top-name { font-size: 18px; font-weight: 900; line-height: 1.25; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ad-top-stats { display: grid; gap: 0; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.07); border-radius: 12px; overflow: hidden; }
        .ad-top-stats > div { padding: 10px 8px; text-align: center; min-width: 0; }
        .ad-top-stats > div + div { border-left: 1px solid rgba(255,255,255,.08); }
        .ad-top-stats > div > div { font-size: 16px; font-weight: 900; overflow-wrap: anywhere; }
        .ad-top-stats span { display: block; margin-top: 2px; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: rgba(255,255,255,.45); }
        .ad-top-bar { height: 6px; border-radius: 999px; background: rgba(255,255,255,.08); overflow: hidden; }
        .ad-top-bar > span { display: block; height: 100%; border-radius: inherit; transition: width .6s ease; }
        .ad-top-empty { color: rgba(255,255,255,.35); font-size: 13px; text-align: center; padding: 16px 0; }
        .ad-top-link { display: inline-flex; align-items: center; gap: 5px; margin-top: auto; padding-top: 12px; font-size: 12px; font-weight: 700; color: rgba(255,255,255,.55); text-decoration: none; align-self: flex-start; }
        .ad-top-link:hover { color: #F5B400; }
        .ad-top-link svg { width: 13px; height: 13px; }

        .ad-feed { padding: 8px 10px 10px; }
        .ad-feed-row { display: flex; align-items: flex-start; gap: 11px; padding: 8px 10px; border-radius: 10px; text-decoration: none; color: inherit; position: relative; }
        a.ad-feed-row[href]:hover { background: var(--cream-soft, #fafafa); }
        .ad-feed-row.is-static { cursor: default; }
        .ad-feed-row:not(:last-child)::after { content: ''; position: absolute; left: 14px; top: 22px; bottom: -8px; width: 2px; background: var(--border); }
        .ad-feed-dot { position: relative; z-index: 1; width: 10px; height: 10px; margin-top: 4px; border-radius: 50%; flex-shrink: 0; background: #F5B400; box-shadow: 0 0 0 3px #fff; }
        .ad-feed-dot.is-green { background: #16a34a; }
        .ad-feed-dot.is-red { background: #dc2626; }
        .ad-feed-dot.is-violet { background: #7c3aed; }
        .ad-feed-main strong { display: block; font-size: 12.5px; font-weight: 700; color: var(--dark); }
        .ad-feed-main small { display: block; margin-top: 1px; font-size: 11px; color: var(--muted); }

        /* ── Fixed-size cards: each row has a set height; a list that is too long scrolls inside its card ── */
        .ad-scroll { flex: 1 1 auto; min-height: 0; overflow-y: auto; overscroll-behavior: contain;
                     scrollbar-width: thin; scrollbar-color: #d4d4d4 transparent; }
        .ad-scroll::-webkit-scrollbar { width: 8px; }
        .ad-scroll::-webkit-scrollbar-thumb { background: #d4d4d4; border-radius: 999px; border: 2px solid #fff; }
        .ad-scroll::-webkit-scrollbar-thumb:hover { background: #b5b5b5; }
        .ad-scroll::-webkit-scrollbar-track { background: transparent; }
        .ad-grid > * { min-height: 0; }

        .ad-row-main { height: 560px; }
        .ad-row-main .ad-pipe { padding: 4px 10px 6px; }
        .ad-row-main .ad-pipe-row { padding: 6px 10px; }
        .ad-row-main > .ad-card, .ad-row-main > .ad-stack { height: 100%; }
        .ad-row-main .ad-stack > .ad-card:first-child { flex: 0 0 auto; }        /* pipeline: its four rows */
        .ad-row-main .ad-stack > .ad-card:last-child  { flex: 1 1 auto; min-height: 0; }   /* needs attention: the rest */

        .ad-row-mid { height: 250px; }
        .ad-row-mid > * { height: 100%; }
        .ad-row-mid .ad-top-pair > .ad-top { height: 100%; }

        .ad-row-low { height: 470px; }
        .ad-row-low > .ad-card { height: 100%; }
        .ad-row-low .db-project-card-head, .ad-row-low .db-proj-table-head, .ad-row-low .db-project-card-footer { flex-shrink: 0; }

        @media (max-width: 1200px) {
            .ad-grid { grid-template-columns: 1fr; }
            .ad-stack { display: grid; grid-template-columns: 1fr 1fr; }
            /* stacked: each card keeps its own fixed height instead of the row's */
            .ad-row-main, .ad-row-mid, .ad-row-low { height: auto; }
            .ad-row-main > .ad-card { height: 400px; }
            .ad-row-main > .ad-stack { height: auto; }
            .ad-row-main .ad-stack > .ad-card { height: 330px; }
            .ad-row-mid > .ad-top-pair, .ad-row-mid .ad-top-pair > .ad-top, .ad-row-mid > .ad-card { height: 250px; }
            .ad-row-low > .ad-card { height: 460px; }
        }
        @media (max-width: 900px) {
            .ad-actions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .ad-stack, .ad-top-pair { grid-template-columns: 1fr; }
            .ad-row-mid > .ad-top-pair { height: auto; }   /* the two dark cards stack — each keeps its 250px */
        }
        @media (max-width: 640px) {
            .ad-title { font-size: 22px; }
            .ad-head-right { width: 100%; }
            .ad-chip { display: none; }
            .ad-year { flex: 1; }
            .ad-hint { display: none; }
            .ad-action small { display: none; }
            .ad-action { padding: 10px 12px; }
        }
    </style>

</body>
</html>
