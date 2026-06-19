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

            <div class="page-header" style="flex-wrap:wrap;gap:14px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <i data-lucide="layout-dashboard" style="width:22px;height:22px;color:var(--muted);"></i>
                    <h1 style="margin:0;">KPI Dashboard &amp; Forecast</h1>
                </div>

                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    {{-- Year / Month filters --}}
                    <form method="GET" action="{{ route('admin.reports') }}" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <select name="year" class="filter-select" onchange="this.form.submit()" style="min-width:100px;">
                            <option value="">All Years</option>
                            @for($y = now()->year; $y >= 2020; $y--)
                                <option value="{{ $y }}" {{ $filterYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>

                        <select name="month" class="filter-select" onchange="this.form.submit()" style="min-width:120px;">
                            <option value="">All Months</option>
                            @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $mi => $mn)
                                <option value="{{ $mi + 1 }}" {{ $filterMonth == $mi + 1 ? 'selected' : '' }}>{{ $mn }}</option>
                            @endforeach
                        </select>

                        @if($filterYear || $filterMonth)
                        <a href="{{ route('admin.reports') }}" style="font-size:12px;font-weight:700;color:var(--muted);text-decoration:none;padding:8px 12px;border:1px solid var(--border);border-radius:8px;white-space:nowrap;">
                            Clear
                        </a>
                        @endif
                    </form>

                    {{-- Generate Reports --}}
                    <button onclick="window.print()" class="save-btn" style="font-size:13px;padding:10px 18px;display:inline-flex;align-items:center;gap:7px;">
                        <i data-lucide="file-text" style="width:14px;height:14px;"></i>
                        Generate Report
                    </button>
                </div>
            </div>

            @if($count === 0)
            <div class="alert-banner" style="background:#fff7ed;border:1px solid #fed7aa;color:#92400e;border-radius:10px;padding:14px 18px;display:flex;align-items:center;gap:10px;margin-bottom:20px;">
                <i data-lucide="info"></i>
                No completed projects yet. KPIs are computed from completed projects only.
            </div>
            @endif

            <!-- ── Tabs ── -->
            <div class="kpi-tab-bar">
                <button class="kpi-tab active" data-tab="per-project">Per-Project KPI</button>
                <button class="kpi-tab" data-tab="overall">Overall Business KPI</button>
                <button class="kpi-tab" data-tab="forecast">Forecast (Weighted Moving Average)</button>
            </div>

            @php
            function kpiRating(float $val, string $type): array {
                if ($type === 'pm') {
                    if ($val >= 20) return ['Good',     'kpi-good'];
                    if ($val >= 12) return ['Fair',     'kpi-fair'];
                    return              ['Poor',     'kpi-poor'];
                }
                if ($type === 'ba') {
                    if ($val >= 85) return ['Good',     'kpi-good'];
                    if ($val >= 70) return ['Fair',     'kpi-fair'];
                    return              ['Poor',     'kpi-poor'];
                }
                if ($type === 'ot') {
                    if ($val >= 80) return ['Good',     'kpi-good'];
                    if ($val >= 60) return ['Fair',     'kpi-fair'];
                    return              ['Watch out','kpi-poor'];
                }
                return ['—', ''];
            }
            @endphp

            <!-- ════════════════════════════════════════
                 TAB 1 — PER-PROJECT KPI
            ════════════════════════════════════════ -->
            <div class="kpi-tab-content active" id="tab-per-project">
                <div class="kpi-card">
                    <div class="kpi-card-head">
                        <span class="kpi-card-title">KPI per completed project</span>
                        <span class="kpi-card-meta">Computed at project completion</span>
                    </div>
                    <div class="table-wrapper">
                        <table class="data-table kpi-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Project</th>
                                    <th>Client</th>
                                    <th>Profit Margin</th>
                                    <th>On-Time</th>
                                    <th>Budget Adherence</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projectKpis as $kpi)
                                @php
                                    [$pmLabel, $pmCss] = kpiRating($kpi['profit_margin'], 'pm');
                                    [$baLabel, $baCss] = kpiRating($kpi['budget_adherence'], 'ba');
                                @endphp
                                <tr>
                                    <td><span class="project-code-badge">{{ $kpi['code'] }}</span></td>
                                    <td>
                                        <div style="font-weight:600;color:var(--dark);font-size:13px;">{{ $kpi['label'] }}</div>
                                        <div style="font-size:11px;color:var(--muted-light);margin-top:2px;">{{ $kpi['full_name'] }}</div>
                                    </td>
                                    <td style="color:var(--muted);font-size:13px;">{{ $kpi['client'] }}</td>
                                    <td>
                                        <span style="font-weight:700;font-size:13px;margin-right:6px;">{{ $kpi['profit_margin'] }}%</span>
                                        <span class="kpi-badge {{ $pmCss }}">{{ $pmLabel }}</span>
                                    </td>
                                    <td>
                                        <span class="kpi-badge {{ $kpi['on_time'] ? 'kpi-good' : 'kpi-poor' }}">
                                            {{ $kpi['on_time'] ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-weight:700;font-size:13px;margin-right:6px;">{{ $kpi['budget_adherence'] }}%</span>
                                        <span class="kpi-badge {{ $baCss }}">{{ $baLabel }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" style="text-align:center;padding:48px;color:var(--muted);">
                                        No completed projects yet.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="kpi-footnote">
                        Each KPI is computed individually once a project is marked completed. This is the descriptive layer — what already happened on this specific project.
                    </div>
                </div>
            </div>

            <!-- ════════════════════════════════════════
                 TAB 2 — OVERALL BUSINESS KPI
            ════════════════════════════════════════ -->
            <div class="kpi-tab-content" id="tab-overall">

                <!-- Summary cards -->
                <div class="kpi-summary-row">
                    @php
                        [$pmOvLabel, $pmOvCss] = kpiRating($avgProfitMargin, 'pm');
                        [$otOvLabel, $otOvCss] = kpiRating($onTimeRate, 'ot');
                        [$baOvLabel, $baOvCss] = kpiRating($avgBudgetAdherence, 'ba');
                    @endphp
                    <div class="kpi-summary-card">
                        <div class="kpi-summary-label">Average profit margin</div>
                        <div class="kpi-summary-val">{{ $avgProfitMargin }}%</div>
                        <div style="display:flex;align-items:center;gap:8px;margin-top:6px;">
                            <span class="kpi-badge {{ $pmOvCss }}">{{ $pmOvLabel }}</span>
                            <span style="font-size:12px;color:var(--muted);">across {{ $count }} project{{ $count !== 1 ? 's' : '' }}</span>
                        </div>
                    </div>
                    <div class="kpi-summary-card">
                        <div class="kpi-summary-label">Overall on-time rate</div>
                        <div class="kpi-summary-val">{{ $onTimeRate }}%</div>
                        <div style="display:flex;align-items:center;gap:8px;margin-top:6px;">
                            <span class="kpi-badge {{ $otOvLabel === 'Good' ? 'kpi-good' : ($otOvLabel === 'Fair' ? 'kpi-fair' : 'kpi-poor') }}">{{ $otOvLabel }}</span>
                            <span style="font-size:12px;color:var(--muted);">{{ $onTimeCount }} of {{ $count }} on time</span>
                        </div>
                    </div>
                    <div class="kpi-summary-card">
                        <div class="kpi-summary-label">Average budget adherence</div>
                        <div class="kpi-summary-val">{{ $avgBudgetAdherence }}%</div>
                        <div style="display:flex;align-items:center;gap:8px;margin-top:6px;">
                            <span class="kpi-badge {{ $baOvCss }}">{{ $baOvLabel }}</span>
                            <span style="font-size:12px;color:var(--muted);">across {{ $count }} project{{ $count !== 1 ? 's' : '' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Trend chart -->
                <div class="kpi-card">
                    <div class="kpi-card-head" style="margin-bottom:16px;">
                        <span class="kpi-card-title">KPI trend — per completed project</span>
                    </div>
                    <div class="kpi-legend-row">
                        <div class="kpi-legend-item"><span style="background:#16a34a;"></span> Profit margin</div>
                        <div class="kpi-legend-item"><span style="background:#2A4EAA;"></span> On-time rate</div>
                        <div class="kpi-legend-item"><span style="background:#e8900a;"></span> Budget adherence</div>
                    </div>
                    <div class="kpi-chart-wrap" style="position:relative;margin-top:10px;">
                        <canvas id="overallTrendChart"></canvas>
                    </div>
                    <div class="kpi-footnote" style="margin-top:16px;">
                        This view aggregates all completed projects into overall business performance — the general health of GMD as a whole, rather than any single project.
                    </div>
                </div>

            </div>

            <!-- ════════════════════════════════════════
                 TAB 3 — FORECAST
            ════════════════════════════════════════ -->
            <div class="kpi-tab-content" id="tab-forecast">

                <!-- Forecast chart — profit margin -->
                <div class="kpi-card">
                    <div class="kpi-card-head" style="margin-bottom:16px;">
                        <span class="kpi-card-title">Forecast calculation — profit margin</span>
                    </div>
                    <div class="kpi-chart-wrap" style="position:relative;">
                        <canvas id="forecastPmChart"></canvas>
                    </div>
                    @if(count($pmVals) >= 3)
                    @php
                        $pn  = $pmVals[count($pmVals)-1];
                        $pn1 = $pmVals[count($pmVals)-2];
                        $pn2 = $pmVals[count($pmVals)-3];
                    @endphp
                    <div class="kpi-formula-result">
                        <div class="kpi-formula-step">
                            <span class="kfr-label">Formula</span>
                            <span class="kfr-val">({{ $pn }}×3 + {{ $pn1 }}×2 + {{ $pn2 }}×1) ÷ 6</span>
                        </div>
                        <div class="kfi-arrow">↓</div>
                        <div class="kpi-formula-step">
                            <span class="kfr-label">Result</span>
                            <span class="kfr-val kfr-answer">{{ $wmaForecast['profit_margin'] }}%</span>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Forecast summary -->
                @php
                    [$fPmLabel, $fPmCss] = kpiRating($wmaForecast['profit_margin'], 'pm');
                    [$fOtLabel, $fOtCss] = kpiRating($wmaForecast['on_time'], 'ot');
                    [$fBaLabel, $fBaCss] = kpiRating($wmaForecast['budget_adherence'], 'ba');
                @endphp
                <div class="kpi-card">
                    <div class="kpi-card-head" style="margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--border);">
                        <span class="kpi-card-title">Forecast summary — next project</span>
                    </div>

                    <div class="table-wrapper">
                        <table class="data-table kpi-table" style="margin-top:0;min-width:420px;">
                            <thead>
                                <tr>
                                    <th>KPI</th>
                                    <th>Last 3 avg</th>
                                    <th></th>
                                    <th>WMA forecast</th>
                                    <th>Interpretation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="font-weight:600;">Profit margin</td>
                                    <td style="font-weight:700;">{{ $last3Avg['profit_margin'] }}%</td>
                                    <td style="color:var(--muted);">→</td>
                                    <td style="font-weight:800;color:#e8900a;font-size:14px;">{{ $wmaForecast['profit_margin'] }}%</td>
                                    <td><span class="kpi-badge {{ $fPmCss }}">{{ $fPmLabel }}</span></td>
                                </tr>
                                <tr>
                                    <td style="font-weight:600;">On-time delivery</td>
                                    <td style="font-weight:700;">{{ $last3Avg['on_time'] }}%</td>
                                    <td style="color:var(--muted);">→</td>
                                    <td style="font-weight:800;color:#e8900a;font-size:14px;">{{ $wmaForecast['on_time'] }}%</td>
                                    <td><span class="kpi-badge {{ $fOtCss }}">{{ $fOtLabel }}</span></td>
                                </tr>
                                <tr>
                                    <td style="font-weight:600;">Budget adherence</td>
                                    <td style="font-weight:700;">{{ $last3Avg['budget_adherence'] }}%</td>
                                    <td style="color:var(--muted);">→</td>
                                    <td style="font-weight:800;color:#e8900a;font-size:14px;">{{ $wmaForecast['budget_adherence'] }}%</td>
                                    <td><span class="kpi-badge {{ $fBaCss }}">{{ $fBaLabel }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="kpi-footnote">
                        The weighted moving average gives the most recent project 3× the weight of the third-most-recent — a sudden recent dip immediately pulls the forecast down, alerting the owner early.
                    </div>
                </div>

            </div>

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        // ── Tab switching ──
        document.querySelectorAll('.kpi-tab').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.kpi-tab').forEach(function(b) { b.classList.remove('active'); });
                document.querySelectorAll('.kpi-tab-content').forEach(function(p) { p.classList.remove('active'); });
                this.classList.add('active');
                document.getElementById('tab-' + this.dataset.tab).classList.add('active');
                lucide.createIcons();
            });
        });

        // ── Overall trend chart ──
        (function() {
            var kpis = @json($projectKpis->values());
            if (!kpis.length) return;

            var labels   = kpis.map(function(k) { return k.short; });
            var pm       = kpis.map(function(k) { return k.profit_margin; });
            var ot       = kpis.map(function(k) { return k.on_time ? 100 : 0; });
            var ba       = kpis.map(function(k) { return k.budget_adherence; });

            var ctx = document.getElementById('overallTrendChart').getContext('2d');

            var gradGreen = ctx.createLinearGradient(0, 0, 0, 260);
            gradGreen.addColorStop(0, 'rgba(22,163,74,0.18)');
            gradGreen.addColorStop(1, 'rgba(22,163,74,0.01)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Profit margin',
                            data: pm,
                            borderColor: '#16a34a',
                            backgroundColor: gradGreen,
                            borderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#16a34a',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            tension: 0.3,
                            fill: true,
                        },
                        {
                            label: 'On-time rate',
                            data: ot,
                            borderColor: '#2A4EAA',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#2A4EAA',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            tension: 0.3,
                        },
                        {
                            label: 'Budget adherence',
                            data: ba,
                            borderColor: '#e8900a',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#e8900a',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            tension: 0.3,
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
                                label: function(c) { return '  ' + c.dataset.label + ': ' + c.parsed.y + '%'; }
                            },
                            backgroundColor: '#1a1a1a', padding: 12, cornerRadius: 8,
                            titleFont: { weight: '700' }, bodyFont: { weight: '600' },
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: { color: '#999', font: { size: 11, weight: '600' } }
                        },
                        y: {
                            min: 0, max: 110,
                            border: { display: false, dash: [4, 4] },
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            ticks: {
                                color: '#999',
                                font: { size: 11, weight: '600' },
                                callback: function(v) { return v + '%'; },
                                stepSize: 10,
                            }
                        }
                    }
                }
            });
        })();

        // ── Forecast profit margin chart ──
        (function() {
            var chart5 = @json($forecastChart->values());
            var wma    = {{ $wmaForecast['profit_margin'] }};
            if (!chart5.length) return;

            var solidLabels  = chart5.map(function(k) { return k.short; });
            var solidVals    = chart5.map(function(k) { return k.profit_margin; });

            // Append forecast "Next" point as null (solid line) + defined (dashed)
            var allLabels = solidLabels.concat(['Next']);
            var lastVal   = solidVals[solidVals.length - 1];

            var ctx = document.getElementById('forecastPmChart').getContext('2d');

            var gradG = ctx.createLinearGradient(0, 0, 0, 220);
            gradG.addColorStop(0, 'rgba(22,163,74,0.18)');
            gradG.addColorStop(1, 'rgba(22,163,74,0.01)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: allLabels,
                    datasets: [
                        {
                            label: 'Historical',
                            data: solidVals.concat([null]),
                            borderColor: '#16a34a',
                            backgroundColor: gradG,
                            borderWidth: 2,
                            pointRadius: 4, pointHoverRadius: 6,
                            pointBackgroundColor: '#16a34a', pointBorderColor: '#fff', pointBorderWidth: 2,
                            tension: 0.35, fill: true,
                        },
                        {
                            label: 'Forecast',
                            data: solidVals.slice(0, -1).map(function() { return null; }).concat([lastVal, wma]),
                            borderColor: '#e8900a',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [7, 5],
                            pointRadius: [0, 0, 0, 0, 6],
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#e8900a', pointBorderColor: '#fff', pointBorderWidth: 2,
                            tension: 0,
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
                                    if (c.parsed.y === null) return null;
                                    return '  ' + c.dataset.label + ': ' + c.parsed.y + '%';
                                }
                            },
                            backgroundColor: '#1a1a1a', padding: 10, cornerRadius: 8,
                            titleFont: { weight: '700' }, bodyFont: { weight: '600' },
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, border: { display: false }, ticks: { color: '#999', font: { size: 11, weight: '600' } } },
                        y: {
                            border: { display: false, dash: [4, 4] },
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            ticks: { color: '#999', font: { size: 11 }, callback: function(v) { return v + '%'; } }
                        }
                    }
                }
            });
        })();
    </script>

    <style>
        /* ── Tab bar ── */
        .kpi-tab-bar {
            display: flex;
            gap: 0;
            border-bottom: 2px solid var(--border);
            margin-bottom: 20px;
        }
        .kpi-tab {
            background: none;
            border: none;
            padding: 12px 20px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: color 0.15s, border-color 0.15s;
            white-space: nowrap;
        }
        .kpi-tab:hover { color: var(--dark); }
        .kpi-tab.active { color: var(--dark); font-weight: 800; border-bottom-color: var(--dark); }

        /* ── Tab content ── */
        .kpi-tab-content { display: none; }
        .kpi-tab-content.active { display: block; }

        /* ── Cards ── */
        .kpi-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 16px;
        }
        .kpi-card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }
        .kpi-card-title { font-size: 14px; font-weight: 800; color: var(--dark); }
        .kpi-card-meta  { font-size: 12px; color: var(--muted-light); }

        /* ── Badges ── */
        .kpi-badge {
            display: inline-flex;
            align-items: center;
            font-size: 11.5px;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 999px;
        }
        .kpi-good     { background: #dcfce7; color: #15803d; }
        .kpi-fair     { background: #fef3c7; color: #92400e; }
        .kpi-poor     { background: #fee2e2; color: #b91c1c; }

        /* ── Table ── */
        .kpi-table thead th { font-size: 11px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase; }

        /* ── Footnote ── */
        .kpi-footnote {
            background: var(--cream-soft);
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 12.5px;
            color: var(--muted);
            line-height: 1.65;
            margin-top: 16px;
        }

        /* ── Summary row (tab 2) ── */
        .kpi-summary-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 16px;
        }
        .kpi-summary-card {
            background: var(--cream-soft);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px 20px;
        }
        .kpi-summary-label { font-size: 12px; color: var(--muted); font-weight: 600; margin-bottom: 4px; }
        .kpi-summary-val   { font-size: 32px; font-weight: 900; color: var(--dark); letter-spacing: -1px; line-height: 1; }

        /* ── Legend ── */
        .kpi-legend-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .kpi-legend-item {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--muted);
        }
        .kpi-legend-item span {
            width: 12px; height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        /* ── Formula result ── */
        .kpi-formula-result {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 14px;
        }
        .kpi-formula-step {
            background: var(--cream-soft);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 16px;
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }
        .kfr-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--muted-light);
        }
        .kfr-val {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
            word-break: break-all;
        }
        .kfr-answer {
            font-size: 20px;
            font-weight: 900;
            color: #e8900a;
            font-family: inherit;
        }
        .kfi-arrow {
            font-size: 18px;
            color: var(--muted-light);
            flex-shrink: 0;
        }


        /* ── Chart wrapper ── */
        .kpi-chart-wrap {
            height: 250px;
        }

        /* ── Formula box ── */
        .kpi-formula-box {
            background: var(--cream-soft);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 16px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: var(--dark);
            font-weight: 600;
        }

        /* ── Print styles ── */
        @media print {
            .admin-sidebar, .admin-header, .page-header button,
            .page-header form, .kpi-tab-bar { display: none !important; }
            .admin-content { margin: 0 !important; padding: 16px !important; }
            .kpi-tab-content { display: block !important; }
            .kpi-card { break-inside: avoid; margin-bottom: 12px; }
            body { background: #fff !important; }
        }

        /* ── Responsive ── */

        /* Tablet */
        @media (max-width: 900px) {
            .kpi-summary-row { grid-template-columns: 1fr 1fr; }
        }

        /* Small tablet */
        @media (max-width: 768px) {
            /* Scrollable tab bar so long tab names don't wrap or break */
            .kpi-tab-bar {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                flex-wrap: nowrap;
                gap: 0;
                padding-bottom: 2px;
            }
            .kpi-tab {
                padding: 11px 14px;
                font-size: 12.5px;
                white-space: nowrap;
                flex-shrink: 0;
            }

            .kpi-summary-row { grid-template-columns: 1fr 1fr; gap: 10px; }
            .kpi-summary-val  { font-size: 26px; }

            .kpi-card { padding: 16px; }
            .kpi-card-head { flex-wrap: wrap; gap: 8px; }
            .kpi-card-meta { width: 100%; }

            .kpi-formula-box {
                font-size: 12px;
                overflow-x: auto;
                white-space: pre-wrap;
                word-break: break-word;
            }

            .kpi-legend-row { gap: 12px; }
        }

        /* Mobile */
        @media (max-width: 540px) {
            .kpi-chart-wrap { height: 190px; }
            .kpi-tab { padding: 10px 12px; font-size: 12px; }

            .kpi-summary-row { grid-template-columns: 1fr; gap: 10px; }
            .kpi-summary-val  { font-size: 28px; }
            .kpi-summary-card { padding: 14px 16px; }

            .kpi-card { padding: 14px; margin-bottom: 12px; }

            /* Formula result — stack vertically */
            .kpi-formula-result { flex-direction: column; gap: 8px; }
            .kfi-arrow { align-self: flex-start; }
            .kpi-formula-step { width: 100%; }
            .kfr-val { font-size: 11.5px; }

        }

        /* Very small */
        @media (max-width: 380px) {
            .kpi-tab { padding: 9px 10px; font-size: 11.5px; }
            .kpi-summary-val { font-size: 24px; }
            .kpi-card { padding: 12px; }
            .kpi-fc-body { gap: 8px; }
            .kpi-fc-num { font-size: 13px; }
        }
    </style>

</body>
</html>
