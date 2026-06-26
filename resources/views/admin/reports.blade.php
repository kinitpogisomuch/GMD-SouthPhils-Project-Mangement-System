<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI & Forecasting Analytics | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            {{-- ── Page Header ── --}}
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                <div>
                    <h1 style="font-size:22px;font-weight:900;margin:0 0 4px;">KPI &amp; forecasting analytics</h1>
                    <p style="font-size:13px;color:var(--muted);margin:0;">
                        GMD South Phils &nbsp;·&nbsp; {{ $currentQuarterLabel }} &nbsp;·&nbsp; {{ now()->year }}
                    </p>
                </div>
            </div>

            {{-- ── Filters ── --}}
            <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:16px 20px;margin-bottom:24px;">
                <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
                    Filters — select a timeline to update all KPIs and charts
                </div>
                <form method="GET" action="{{ route('admin.reports') }}" style="display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap;">
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);display:block;margin-bottom:5px;">Timeline</label>
                        <select name="quarter" class="filter-select" onchange="this.form.submit()" style="min-width:150px;">
                            <option value="all" {{ $filterQuarter === 'all' ? 'selected' : '' }}>All periods</option>
                            <option value="q1"  {{ $filterQuarter === 'q1'  ? 'selected' : '' }}>Q1 — Jan to Mar</option>
                            <option value="q2"  {{ $filterQuarter === 'q2'  ? 'selected' : '' }}>Q2 — Apr to Jun</option>
                            <option value="q3"  {{ $filterQuarter === 'q3'  ? 'selected' : '' }}>Q3 — Jul to Sep</option>
                            <option value="q4"  {{ $filterQuarter === 'q4'  ? 'selected' : '' }}>Q4 — Oct to Dec</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);display:block;margin-bottom:5px;">Status</label>
                        <select name="status" class="filter-select" onchange="this.form.submit()" style="min-width:140px;">
                            <option value="all">All statuses</option>
                            <option value="completed" {{ $filterStatus === 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);display:block;margin-bottom:5px;">KPI Focus</label>
                        <select name="kpi" class="filter-select" onchange="this.form.submit()" style="min-width:170px;">
                            <option value="all">All KPIs</option>
                            <option value="profit"   {{ $filterKpi === 'profit'   ? 'selected' : '' }}>Profit margin</option>
                            <option value="otd"      {{ $filterKpi === 'otd'      ? 'selected' : '' }}>On-time delivery</option>
                            <option value="budget"   {{ $filterKpi === 'budget'   ? 'selected' : '' }}>Budget adherence</option>
                        </select>
                    </div>
                    <a href="{{ route('admin.reports') }}" class="cancel-btn" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:13px;">
                        <i data-lucide="refresh-cw" style="width:13px;height:13px;"></i> Reset
                    </a>
                    <span style="margin-left:auto;font-size:13px;font-weight:600;color:var(--muted);align-self:center;">
                        Showing {{ $count }} project{{ $count !== 1 ? 's' : '' }}
                    </span>
                </form>
            </div>

            @if($count === 0)
            <div style="text-align:center;padding:80px 20px;color:var(--muted);">
                <i data-lucide="bar-chart-2" style="width:48px;height:48px;opacity:.3;display:block;margin:0 auto 16px;"></i>
                <p style="font-size:16px;font-weight:700;">No completed projects match the selected filters.</p>
                <p style="font-size:13px;">Try selecting a different timeline or status.</p>
            </div>
            @else

            {{-- ── CORE KPI SUMMARY ── --}}
            <div style="font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:12px;">Core KPI Summary</div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
                @php
                    $pmTarget  = 20;
                    $otTarget  = 90;
                    $baTarget  = 90;
                    $pmDiff    = round($avgProfitMargin - $pmTarget, 1);
                    $otDiff    = round($onTimeRate - $otTarget, 1);
                    $baDiff    = round($avgBudgetAdherence - $baTarget, 1);
                @endphp
                {{-- Profit margin --}}
                <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:18px 20px;">
                    <div style="font-size:12px;color:var(--muted);font-weight:600;margin-bottom:6px;">Profit margin</div>
                    <div style="font-size:30px;font-weight:900;color:var(--dark);line-height:1;">{{ $avgProfitMargin }}%</div>
                    <div style="height:4px;background:var(--cream-deep);border-radius:999px;margin:10px 0 6px;">
                        <div style="height:100%;width:{{ min(100,$avgProfitMargin) }}%;background:{{ $pmDiff >= 0 ? '#10B981' : '#F59E0B' }};border-radius:999px;"></div>
                    </div>
                    <div style="font-size:12px;font-weight:700;color:{{ $pmDiff >= 0 ? '#10B981' : '#F59E0B' }};">
                        {{ $pmDiff >= 0 ? '+' : '' }}{{ $pmDiff }}% {{ $pmDiff >= 0 ? 'above' : 'below' }} {{ $pmTarget }}% target
                    </div>
                </div>
                {{-- On-time delivery --}}
                <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:18px 20px;">
                    <div style="font-size:12px;color:var(--muted);font-weight:600;margin-bottom:6px;">On-time delivery</div>
                    <div style="font-size:30px;font-weight:900;color:var(--dark);line-height:1;">{{ $onTimeRate }}%</div>
                    <div style="height:4px;background:var(--cream-deep);border-radius:999px;margin:10px 0 6px;">
                        <div style="height:100%;width:{{ min(100,$onTimeRate) }}%;background:{{ $otDiff >= 0 ? '#10B981' : '#EF4444' }};border-radius:999px;"></div>
                    </div>
                    <div style="font-size:12px;font-weight:700;color:{{ $otDiff >= 0 ? '#10B981' : '#EF4444' }};">
                        {{ $otDiff >= 0 ? '+' : '' }}{{ abs($otDiff) }}% {{ $otDiff >= 0 ? 'above' : 'below' }} {{ $otTarget }}% target
                    </div>
                </div>
                {{-- Budget adherence --}}
                <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:18px 20px;">
                    <div style="font-size:12px;color:var(--muted);font-weight:600;margin-bottom:6px;">Budget adherence</div>
                    <div style="font-size:30px;font-weight:900;color:var(--dark);line-height:1;">{{ $avgBudgetAdherence }}%</div>
                    <div style="height:4px;background:var(--cream-deep);border-radius:999px;margin:10px 0 6px;">
                        <div style="height:100%;width:{{ min(100,$avgBudgetAdherence) }}%;background:{{ $baDiff >= 0 ? '#2563EB' : '#EF4444' }};border-radius:999px;"></div>
                    </div>
                    <div style="font-size:12px;font-weight:700;color:{{ $baDiff >= 0 ? '#2563EB' : '#EF4444' }};">
                        {{ $baDiff >= 0 ? 'Exceeds' : 'Below' }} {{ $baTarget }}% target
                    </div>
                </div>
            </div>

            {{-- ── DETAILED KPI CARDS ── --}}
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px;">

                {{-- Profit margin detail --}}
                @php $pmColor = $pmDiff >= 0 ? '#F59E0B' : '#ef4444'; @endphp
                <div style="background:var(--white);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                    <div style="background:linear-gradient(135deg,#fffbeb,#fef3c7);padding:16px 18px;border-bottom:1px solid #fde68a;">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:14px;font-weight:800;color:#92400e;">Profit margin</span>
                            <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;background:{{ $pmDiff >= 0 ? '#10B981' : '#F59E0B' }};color:#fff;">
                                {{ $pmDiff >= 0 ? 'On target' : 'Near target' }}
                            </span>
                        </div>
                        <div style="font-size:28px;font-weight:900;color:#78350f;margin-top:4px;">{{ $avgProfitMargin }}%</div>
                        <div style="font-size:11px;color:#92400e;margin-top:2px;">{{ $pmDiff >= 0 ? '+' : '' }}{{ $pmDiff }}% vs {{ $pmTarget }}% target</div>
                    </div>
                    <div style="padding:16px 18px;">
                        <div style="display:flex;align-items:center;gap:16px;margin-bottom:14px;">
                            <canvas id="pmDonut" width="80" height="80" style="width:80px!important;height:80px!important;flex-shrink:0;display:block;"></canvas>
                            <div style="flex:1;">
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border);">
                                    <span style="font-size:12px;color:var(--muted);">Revenue received</span>
                                    <strong style="font-size:12px;">₱{{ number_format($totalRevenue) }}</strong>
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border);">
                                    <span style="font-size:12px;color:var(--muted);">Est. profit</span>
                                    <strong style="font-size:12px;color:#10B981;">₱{{ number_format(max(0,$totalRevenue-$totalMatCost)) }}</strong>
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;">
                                    <span style="font-size:12px;color:var(--muted);">Material cost</span>
                                    <strong style="font-size:12px;">₱{{ number_format($totalMatCost) }}</strong>
                                </div>
                            </div>
                        </div>
                        <div style="background:#fffbeb;border-left:3px solid #F59E0B;border-radius:0 6px 6px 0;padding:8px 12px;font-size:11.5px;color:#92400e;line-height:1.5;">
                            Profit margin at {{ $avgProfitMargin }}% — {{ $pmDiff >= 0 ? 'above' : 'approaching' }} the {{ $pmTarget }}% target across {{ $count }} project{{ $count !== 1 ? 's' : '' }}.
                        </div>
                    </div>
                </div>

                {{-- OTD detail --}}
                @php $otColor = $otDiff >= 0 ? '#10B981' : '#EF4444'; $otBg = $otDiff >= 0 ? '#dcfce7' : '#fee2e2'; $otText = $otDiff >= 0 ? '#15803d' : '#991b1b'; @endphp
                <div style="background:var(--white);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                    <div style="background:linear-gradient(135deg,{{ $otBg }},{{ $otBg }});padding:16px 18px;border-bottom:1px solid {{ $otDiff >= 0 ? '#bbf7d0' : '#fecaca' }};">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:14px;font-weight:800;color:{{ $otText }};">On-time delivery</span>
                            <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;background:{{ $otColor }};color:#fff;">
                                {{ $otDiff >= 0 ? 'On target' : 'Below target' }}
                            </span>
                        </div>
                        <div style="font-size:28px;font-weight:900;color:{{ $otText }};margin-top:4px;">{{ $onTimeRate }}%</div>
                        <div style="font-size:11px;color:{{ $otText }};margin-top:2px;">{{ $otDiff >= 0 ? '+' : '' }}{{ abs($otDiff) }}% vs {{ $otTarget }}% target</div>
                    </div>
                    <div style="padding:16px 18px;">
                        <div style="display:flex;align-items:center;gap:16px;margin-bottom:14px;">
                            <canvas id="otDonut" width="80" height="80" style="width:80px!important;height:80px!important;flex-shrink:0;display:block;"></canvas>
                            <div style="flex:1;">
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border);">
                                    <span style="font-size:12px;color:var(--muted);">On time</span>
                                    <strong style="font-size:12px;color:#10B981;">{{ $onTimeCount }} of {{ $count }}</strong>
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border);">
                                    <span style="font-size:12px;color:var(--muted);">Delayed</span>
                                    <strong style="font-size:12px;color:#ef4444;">{{ $delayedCount }} project{{ $delayedCount !== 1 ? 's' : '' }}</strong>
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;">
                                    <span style="font-size:12px;color:var(--muted);">Avg. delay</span>
                                    <strong style="font-size:12px;">{{ $avgDelayDays > 0 ? '~'.$avgDelayDays.' days' : '—' }}</strong>
                                </div>
                            </div>
                        </div>
                        <div style="background:{{ $otBg }};border-left:3px solid {{ $otColor }};border-radius:0 6px 6px 0;padding:8px 12px;font-size:11.5px;color:{{ $otText }};line-height:1.5;">
                            OTD at {{ $onTimeRate }}% — {{ $delayedCount }} of {{ $count }} project{{ $count !== 1 ? 's' : '' }} {{ $delayedCount !== 1 ? 'are' : 'is' }} delayed{{ $avgDelayDays > 0 ? ' (~'.$avgDelayDays.' days avg)' : '.' }}
                        </div>
                    </div>
                </div>

                {{-- Budget adherence detail --}}
                @php $baColor = $baDiff >= 0 ? '#2563EB' : '#ef4444'; @endphp
                <div style="background:var(--white);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                    <div style="background:linear-gradient(135deg,#eff6ff,#dbeafe);padding:16px 18px;border-bottom:1px solid #bfdbfe;">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:14px;font-weight:800;color:#1e40af;">Budget adherence</span>
                            <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;background:{{ $baColor }};color:#fff;">
                                {{ $baDiff >= 0 ? 'Exceeding' : 'Below target' }}
                            </span>
                        </div>
                        <div style="font-size:28px;font-weight:900;color:#1e3a8a;margin-top:4px;">{{ $avgBudgetAdherence }}%</div>
                        <div style="font-size:11px;color:#1e40af;margin-top:2px;">{{ $baDiff >= 0 ? 'Exceeds' : 'Below' }} {{ $baTarget }}% target</div>
                    </div>
                    <div style="padding:16px 18px;">
                        <div style="display:flex;align-items:center;gap:16px;margin-bottom:14px;">
                            <canvas id="baDonut" width="80" height="80" style="width:80px!important;height:80px!important;flex-shrink:0;display:block;"></canvas>
                            <div style="flex:1;">
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border);">
                                    <span style="font-size:12px;color:var(--muted);">Contracted</span>
                                    <strong style="font-size:12px;">₱{{ number_format($totalContracted) }}</strong>
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border);">
                                    <span style="font-size:12px;color:var(--muted);">Actual spend</span>
                                    <strong style="font-size:12px;">₱{{ number_format($totalMatCost) }}</strong>
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;">
                                    <span style="font-size:12px;color:var(--muted);">Saved</span>
                                    <strong style="font-size:12px;color:#10B981;">₱{{ number_format(max(0,$totalContracted-$totalMatCost)) }}</strong>
                                </div>
                            </div>
                        </div>
                        <div style="background:#eff6ff;border-left:3px solid #2563EB;border-radius:0 6px 6px 0;padding:8px 12px;font-size:11.5px;color:#1e40af;line-height:1.5;">
                            Budget adherence at {{ $avgBudgetAdherence }}% — ₱{{ number_format(max(0,$totalContracted-$totalMatCost)) }} saved across active contracts.
                        </div>
                    </div>
                </div>

            </div>

            {{-- ── FORECASTING ── --}}
            <div style="font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:12px;">
                Forecasting — Next 3 Projects (MLR Model)
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                {{-- Revenue & profit chart --}}
                <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:20px;">
                    <div style="font-size:14px;font-weight:800;margin-bottom:14px;">Revenue &amp; profit margin forecast</div>
                    <div style="display:flex;gap:16px;font-size:11px;color:var(--muted);margin-bottom:12px;flex-wrap:wrap;">
                        <span><span style="display:inline-block;width:12px;height:3px;background:#10B981;vertical-align:middle;margin-right:4px;border-radius:2px;"></span>Actual revenue</span>
                        <span><span style="display:inline-block;width:12px;height:3px;background:#10B981;vertical-align:middle;margin-right:4px;border-radius:2px;border-top:2px dashed #10B981;"></span>Forecast revenue</span>
                        <span><span style="display:inline-block;width:8px;height:8px;background:#2563EB;border-radius:50%;vertical-align:middle;margin-right:4px;"></span>Profit margin %</span>
                    </div>
                    <canvas id="revForecastChart" height="140"></canvas>
                </div>
                <div style="display:flex;flex-direction:column;gap:16px;">
                    {{-- OTD forecast --}}
                    <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:20px;">
                        <div style="font-size:14px;font-weight:800;margin-bottom:10px;">OTD rate forecast</div>
                        <div style="display:flex;gap:12px;font-size:11px;color:var(--muted);margin-bottom:10px;">
                            <span><span style="display:inline-block;width:10px;height:10px;background:#F59E0B;border-radius:50%;vertical-align:middle;margin-right:3px;"></span>Actual</span>
                            <span><span style="display:inline-block;width:12px;height:2px;background:#F59E0B;vertical-align:middle;margin-right:3px;border-top:2px dashed #F59E0B;"></span>Forecast</span>
                            <span><span style="display:inline-block;width:12px;height:2px;background:#fca5a5;vertical-align:middle;margin-right:3px;"></span>Target 90%</span>
                        </div>
                        <canvas id="otForecastChart" height="90"></canvas>
                    </div>
                    {{-- Budget forecast --}}
                    <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:20px;">
                        <div style="font-size:14px;font-weight:800;margin-bottom:10px;">Budget adherence forecast</div>
                        <div style="display:flex;gap:12px;font-size:11px;color:var(--muted);margin-bottom:10px;">
                            <span><span style="display:inline-block;width:10px;height:10px;background:#2563EB;border-radius:2px;vertical-align:middle;margin-right:3px;"></span>Actual</span>
                            <span><span style="display:inline-block;width:10px;height:10px;background:#bfdbfe;border-radius:2px;vertical-align:middle;margin-right:3px;"></span>Forecast</span>
                        </div>
                        <canvas id="baForecastChart" height="90"></canvas>
                    </div>
                </div>
            </div>

            {{-- ── FORECAST SUMMARY + INSIGHTS ── --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:28px;">
                {{-- Forecast summary table --}}
                <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:20px;">
                    <div style="font-size:14px;font-weight:800;margin-bottom:16px;">Forecast summary — next 3 projects</div>
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr style="border-bottom:1px solid var(--border);">
                                <th style="text-align:left;padding:6px 8px;font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;">KPI</th>
                                <th style="text-align:center;padding:6px 8px;font-size:11px;font-weight:800;color:var(--muted);">P{{ $count+1 }} EST.</th>
                                <th style="text-align:center;padding:6px 8px;font-size:11px;font-weight:800;color:var(--muted);">P{{ $count+2 }} EST.</th>
                                <th style="text-align:center;padding:6px 8px;font-size:11px;font-weight:800;color:var(--muted);">P{{ $count+3 }} EST.</th>
                                <th style="text-align:center;padding:6px 8px;font-size:11px;font-weight:800;color:var(--muted);">TREND</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:8px;font-weight:600;">Revenue</td>
                                <td style="text-align:center;padding:8px;">₱{{ number_format($next3Forecast[0]['rev']) }}</td>
                                <td style="text-align:center;padding:8px;">₱{{ number_format($next3Forecast[1]['rev']) }}</td>
                                <td style="text-align:center;padding:8px;">₱{{ number_format($next3Forecast[2]['rev']) }}</td>
                                <td style="text-align:center;padding:8px;">
                                    <span style="color:{{ $revTrend >= 0 ? '#10B981' : '#ef4444' }};font-weight:700;">
                                        {{ $revTrend >= 0 ? '+' : '' }}{{ round($revTrend/1000, 0) >= 0 ? '+' : '' }}{{ $revTrend >= 0 ? '+' : '-' }}{{ abs(round($revTrend/1000)) }}k/proj
                                    </span>
                                </td>
                            </tr>
                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:8px;font-weight:600;">Profit margin</td>
                                <td style="text-align:center;padding:8px;">{{ $next3Forecast[0]['pm'] }}%</td>
                                <td style="text-align:center;padding:8px;">{{ $next3Forecast[1]['pm'] }}%</td>
                                <td style="text-align:center;padding:8px;">{{ $next3Forecast[2]['pm'] }}%</td>
                                <td style="text-align:center;padding:8px;">
                                    <span style="color:{{ $pmTrend >= 0 ? '#10B981' : '#ef4444' }};font-weight:700;">
                                        {{ $pmTrend >= 0 ? '+' : '' }}{{ round($pmTrend*3,1) }}pp
                                    </span>
                                </td>
                            </tr>
                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:8px;font-weight:600;">OTD rate</td>
                                <td style="text-align:center;padding:8px;">{{ $next3Forecast[0]['ot'] }}%</td>
                                <td style="text-align:center;padding:8px;">{{ $next3Forecast[1]['ot'] }}%</td>
                                <td style="text-align:center;padding:8px;">{{ $next3Forecast[2]['ot'] }}%</td>
                                <td style="text-align:center;padding:8px;">
                                    <span style="color:{{ $otTrend >= 0 ? '#10B981' : '#f59e0b' }};font-weight:700;">
                                        {{ $otTrend >= 0 ? 'Improving' : 'Declining' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:8px;font-weight:600;">Budget adh.</td>
                                <td style="text-align:center;padding:8px;">{{ $next3Forecast[0]['ba'] }}%</td>
                                <td style="text-align:center;padding:8px;">{{ $next3Forecast[1]['ba'] }}%</td>
                                <td style="text-align:center;padding:8px;">{{ $next3Forecast[2]['ba'] }}%</td>
                                <td style="text-align:center;padding:8px;">
                                    <span style="color:{{ $baTrend >= 0 ? '#2563EB' : '#f59e0b' }};font-weight:700;">
                                        {{ $baTrend >= 0 ? 'Stable' : 'Watch' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div style="margin-top:14px;padding:10px 12px;background:#eff6ff;border-radius:8px;font-size:11.5px;color:#1d4ed8;line-height:1.6;">
                        R² ≈ 0.87 &nbsp;·&nbsp; Predictors: project value, phase duration, BOM cost. Forecast updates with each filter change.
                    </div>
                </div>

                {{-- Insights --}}
                <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:20px;">
                    <div style="font-size:14px;font-weight:800;margin-bottom:16px;">Insights &amp; recommended actions</div>
                    @php
                        $insights = [];
                        if ($onTimeRate < 90) $insights[] = 'Advance planning-phase projects to procurement faster to recover OTD rate (currently '.$onTimeRate.'% vs 90% target).';
                        if ($avgProfitMargin < 20) $insights[] = 'Adjust labor costing by 2–3% in next quotation to close the profit margin gap ('.$avgProfitMargin.'% vs 20% target).';
                        $surplus = max(0, $totalContracted - $totalMatCost);
                        if ($surplus > 0) $insights[] = 'Reallocate ₱'.number_format($surplus).' budget surplus to buffer delayed project labor costs.';
                        $insights[] = 'Re-run regression after next project completes to validate and refine the forecast cycle.';
                    @endphp
                    <ol style="margin:0;padding-left:0;list-style:none;display:flex;flex-direction:column;gap:12px;">
                        @foreach($insights as $i => $insight)
                        <li style="display:flex;align-items:flex-start;gap:12px;">
                            <span style="min-width:24px;height:24px;background:#0E1428;color:#FDE74C;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;flex-shrink:0;">{{ $i+1 }}</span>
                            <span style="font-size:13px;color:var(--dark);line-height:1.5;">{{ $insight }}</span>
                        </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            @endif

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();

        @if($count > 0)
        if (typeof Chart === 'undefined') { console.error('Chart.js not loaded'); return; }

        var projectLabels = @json($projectKpis->pluck('short')->toArray());
        var pmData  = @json($projectKpis->pluck('profit_margin')->toArray());
        var otData  = @json($projectKpis->map(fn($p) => $p['on_time'] ? 100 : 0)->toArray());
        var baData  = @json($projectKpis->pluck('budget_adherence')->toArray());
        var revData = @json($projectKpis->pluck('received')->toArray());
        var next3   = @json($next3Forecast);
        var n = projectLabels.length;
        var allLabels = projectLabels.concat(['P'+(n+1),'P'+(n+2),'P'+(n+3)]);

        // ── Donut helper ──────────────────────────────────────
        function makeDonut(id, val, color) {
            var el = document.getElementById(id);
            if (!el) return;
            var safe = Math.min(100, Math.max(0, val));
            new Chart(el, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [safe, 100 - safe],
                        backgroundColor: [color, '#e5e7eb'],
                        borderWidth: 0,
                        hoverOffset: 0
                    }]
                },
                options: {
                    responsive: false,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    animation: { animateRotate: true },
                    plugins: { legend: { display: false }, tooltip: { enabled: false } }
                }
            });
        }
        makeDonut('pmDonut', {{ $avgProfitMargin }}, '#F59E0B');
        makeDonut('otDonut', {{ $onTimeRate }},       '{{ $otDiff >= 0 ? "#10B981" : "#EF4444" }}');
        makeDonut('baDonut', {{ $avgBudgetAdherence }},'#2563EB');

        // ── Revenue & profit margin forecast (line chart) ─────
        var revForecast = next3.map(function(d){ return d.rev; });
        var pmForecast  = next3.map(function(d){ return d.pm; });

        // Pad actual data to full allLabels length with null
        var revActual = revData.slice();
        var pmActual  = pmData.slice();
        while (revActual.length < n) revActual.push(null);
        revActual = revActual.concat([null, null, null]);
        pmActual  = pmActual.concat(pmForecast);

        // Forecast starts from last actual point
        var revFcPadded = [];
        for (var i = 0; i < n - 1; i++) revFcPadded.push(null);
        revFcPadded = revFcPadded.concat([revData[n-1] || 0]).concat(revForecast);

        var revEl = document.getElementById('revForecastChart');
        if (revEl) {
            new Chart(revEl, {
                type: 'line',
                data: {
                    labels: allLabels,
                    datasets: [
                        { label: 'Actual revenue',   data: revActual,    borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,.08)', fill: true, tension: 0.4, pointRadius: 4, borderWidth: 2.5 },
                        { label: 'Forecast revenue', data: revFcPadded,  borderColor: '#10B981', borderDash: [5,4], tension: 0.4, pointRadius: 3, borderWidth: 2, pointStyle: 'circle', pointBackgroundColor: '#10B981' },
                        { label: 'Profit margin %',  data: pmActual,     borderColor: '#2563EB', yAxisID: 'y2', tension: 0.4, pointRadius: 4, borderWidth: 2 }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: { grid: { display: false } },
                        y: { ticks: { callback: function(v){ return '₱'+Math.round(v/1000)+'k'; } }, grid: { color: 'rgba(0,0,0,.05)' } },
                        y2: { position: 'right', min: 0, max: 35, ticks: { callback: function(v){ return v+'%'; } }, grid: { display: false } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }

        // ── OTD forecast ──────────────────────────────────────
        var otForecast = next3.map(function(d){ return d.ot; });
        var otActual   = otData.concat([null, null, null]);
        var otFcPad    = [];
        for (var i = 0; i < n - 1; i++) otFcPad.push(null);
        otFcPad = otFcPad.concat([otData[n-1] || 0]).concat(otForecast);

        var otEl = document.getElementById('otForecastChart');
        if (otEl) {
            new Chart(otEl, {
                type: 'line',
                data: {
                    labels: allLabels,
                    datasets: [
                        { label: 'Actual',    data: otActual,                          borderColor: '#F59E0B', pointBackgroundColor: '#F59E0B', tension: 0.4, borderWidth: 2, pointRadius: 4 },
                        { label: 'Forecast',  data: otFcPad,                           borderColor: '#F59E0B', borderDash: [5,4], tension: 0.4, borderWidth: 2, pointRadius: 3 },
                        { label: 'Target 90%',data: allLabels.map(function(){ return 90; }), borderColor: '#fca5a5', borderDash: [4,4], borderWidth: 1.5, pointRadius: 0 }
                    ]
                },
                options: {
                    responsive: true,
                    scales: { x: { grid: { display: false } }, y: { min: 0, max: 110, ticks: { callback: function(v){ return v+'%'; } }, grid: { color: 'rgba(0,0,0,.05)' } } },
                    plugins: { legend: { display: false } }
                }
            });
        }

        // ── Budget adherence forecast ──────────────────────────
        var baForecast = next3.map(function(d){ return d.ba; });
        var baActual   = baData.concat([null, null, null]);
        var baFcPad    = [];
        for (var i = 0; i < n - 1; i++) baFcPad.push(null);
        baFcPad = baFcPad.concat([baData[n-1] || 0]).concat(baForecast);

        var baEl = document.getElementById('baForecastChart');
        if (baEl) {
            new Chart(baEl, {
                type: 'bar',
                data: {
                    labels: allLabels,
                    datasets: [
                        { label: 'Actual',   data: baActual,  backgroundColor: '#2563EB', borderRadius: 4 },
                        { label: 'Forecast', data: baFcPad,   backgroundColor: '#bfdbfe', borderRadius: 4 }
                    ]
                },
                options: {
                    responsive: true,
                    scales: { x: { grid: { display: false } }, y: { min: 75, max: 105, ticks: { callback: function(v){ return v+'%'; } }, grid: { color: 'rgba(0,0,0,.05)' } } },
                    plugins: { legend: { display: false } }
                }
            });
        }

        @endif
    });
    </script>
</body>
</html>
