<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI & Analytics | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            {{-- ── Page Header ── --}}
            <div class="page-header">
                <div>
                    <h1 class="page-title">KPI &amp; Analytics</h1>
                    <p class="page-subtitle">
                        GMD South Phils &nbsp;·&nbsp; {{ $currentQuarterLabel }} &nbsp;·&nbsp; {{ now()->year }}
                        @if($activeProjects > 0)
                            &nbsp;·&nbsp; <span style="color:var(--info);font-weight:700;">{{ $activeProjects }} project{{ $activeProjects !== 1 ? 's' : '' }} active</span>
                        @endif
                    </p>
                </div>
            </div>

            {{-- ── Filters ── --}}
            <div class="card" style="margin-bottom:24px;">
                <form method="GET" action="{{ route('admin.reports') }}" style="display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;padding:16px 20px;">
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);display:block;margin-bottom:5px;">Timeline</label>
                        <div style="position:relative;">
                            <i data-lucide="calendar" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:var(--muted);pointer-events:none;z-index:1;"></i>
                            <select name="range" class="filter-select" onchange="this.form.submit()" style="min-width:190px;padding-left:32px;">
                                <option value="all" {{ $selectedRange === 'all' ? 'selected' : '' }}>All periods</option>
                                @php $lastYear = null; @endphp
                                @foreach($rangeOptions as $opt)
                                    @if($opt['year'] !== $lastYear)
                                        @if($lastYear !== null)
                                            </optgroup>
                                        @endif
                                        <optgroup label="{{ $opt['year'] }}">
                                        @php $lastYear = $opt['year']; @endphp
                                    @endif
                                    <option value="{{ $opt['value'] }}" {{ $selectedRange === $opt['value'] ? 'selected' : '' }}>
                                        {{ $opt['label'] }}
                                    </option>
                                @endforeach
                                @if($lastYear !== null)
                                    </optgroup>
                                @endif
                            </select>
                        </div>
                    </div>
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);display:block;margin-bottom:5px;">Project</label>
                        <select name="project" class="filter-select" onchange="this.form.submit()" style="min-width:210px;max-width:260px;">
                            <option value="all">All projects</option>
                            @foreach($allCompletedProjects as $proj)
                                <option value="{{ $proj->id }}" {{ $filterProject == $proj->id ? 'selected' : '' }}>
                                    {{ $proj->client }} — {{ Str::limit($proj->name, 32) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('admin.reports') }}" class="cancel-btn" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:13px;">
                        <i data-lucide="refresh-cw" style="width:13px;height:13px;"></i> Reset
                    </a>
                    <span style="margin-left:auto;font-size:13px;font-weight:700;color:var(--dark);align-self:center;background:var(--cream-soft);padding:6px 14px;border-radius:999px;border:1px solid var(--border);">
                        {{ $count }} project{{ $count !== 1 ? 's' : '' }}
                    </span>
                </form>
            </div>

            @if($count === 0)
            <div class="card" style="text-align:center;padding:80px 20px;color:var(--muted);">
                <i data-lucide="bar-chart-2" style="width:48px;height:48px;opacity:.3;display:block;margin:0 auto 16px;"></i>
                <p style="font-size:16px;font-weight:700;color:var(--dark);">No completed projects match the selected filters.</p>
                <p style="font-size:13px;">Try selecting a different timeline or project.</p>
            </div>
            @else

            @php
                /*
                    KPI targets are admin-configurable (Settings > KPI Targets), not hardcoded.
                    Defaults are research-backed - see resources/views/admin/settings.blade.php
                    for the basis/citations shown next to each field:
                    - Profit Margin: PH COA contractor margin benchmark (8-10% of Estimated Direct Cost)
                    - On-Time Delivery: industry benchmark range (80-90% for effective contractors)
                    - Budget Adherence: PMI Cost Performance Index standard (CPI = 1.0 / 100% = on budget)
                */
                $pmTarget  = (float) $kpiTargets->kpi_profit_margin_target;
                $otTarget  = (float) $kpiTargets->kpi_on_time_target;
                $baTarget  = (float) $kpiTargets->kpi_budget_adherence_target;
                $pmDiff    = round($avgProfitMargin - $pmTarget, 1);
                $otDiff    = round($onTimeRate - $otTarget, 1);
                $baDiff    = round($avgBudgetAdherence - $baTarget, 1);
                $totalNetProfit = max(0, $totalRevenue - $totalActualSpend);
                $overallMargin  = $totalRevenue > 0 ? round(($totalNetProfit / $totalRevenue) * 100, 1) : 0;
            @endphp

            {{-- ── FINANCIAL SUMMARY STRIP ── --}}
            <div class="pf-summary-grid" style="grid-template-columns:repeat(5,1fr);gap:12px;">
                @php
                    $strips = [
                        ['label'=>'Total Revenue',     'value'=>'₱'.number_format($totalRevenue),   'icon'=>'trending-up'],
                        ['label'=>'Total Net Profit',  'value'=>'₱'.number_format($totalNetProfit), 'icon'=>'banknote'],
                        ['label'=>'Avg Profit Margin', 'value'=>$overallMargin.'%',                 'icon'=>'percent'],
                        ['label'=>'Completed Projects','value'=>$count.' projects',                 'icon'=>'check-circle-2'],
                        ['label'=>'On-Time Delivery',  'value'=>$onTimeRate.'%',                    'icon'=>'clock'],
                    ];
                @endphp
                @foreach($strips as $s)
                <div class="pf-summary-card" style="padding:14px 16px;gap:12px;background:linear-gradient(135deg, var(--dark) 0%, var(--dark-deep) 100%);border-color:transparent;">
                    <div class="pf-summary-icon" style="width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.14);color:var(--white);">
                        <i data-lucide="{{ $s['icon'] }}" style="width:17px;height:17px;"></i>
                    </div>
                    <div class="pf-summary-body">
                        <div class="pf-summary-label" style="white-space:nowrap;color:rgba(255,255,255,.65);">{{ $s['label'] }}</div>
                        <div class="pf-summary-value" style="font-size:15px;white-space:nowrap;color:var(--white);">{{ $s['value'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- ── NARRATIVE REPORT SUMMARY ── --}}
            <div style="background:linear-gradient(135deg,var(--cream-soft),var(--white));border:1px solid var(--border);border-radius:16px;padding:18px 22px;margin-bottom:24px;display:flex;gap:14px;align-items:flex-start;">
                <div class="pf-summary-icon icon-chip-info" style="width:34px;height:34px;border-radius:10px;">
                    <i data-lucide="file-text" style="width:17px;height:17px;"></i>
                </div>
                <div>
                    <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:6px;">Performance Report — {{ $currentQuarterLabel }}</div>
                    @php
                        $pmStatusWord = $pmDiff < 0 ? 'below' : ($pmDiff <= 5 ? 'in line with' : 'well above');
                        $otStatusWord = $otDiff < 0 ? 'below' : ($otDiff <= 5 ? 'meeting' : 'excelling at');
                        $baStatusWord = $baDiff < 0 ? 'below' : ($baDiff <= 5 ? 'meeting' : 'exceeding');
                        $pmForecastWord = ($count > 0 && isset($next3Forecast[0]))
                            ? ($next3Forecast[0]['pm'] > $avgProfitMargin ? 'expected to improve further' : ($next3Forecast[0]['pm'] < $avgProfitMargin ? 'expected to ease slightly' : 'expected to hold steady'))
                            : null;
                        $narrativeSaved = max(0, $totalContracted - $totalActualSpend);
                    @endphp
                    <p style="font-size:13px;line-height:1.75;color:var(--dark);margin:0;">
                        Across <strong>{{ $count }}</strong> completed project{{ $count !== 1 ? 's' : '' }} for this period, the company generated
                        <strong>₱{{ number_format($totalRevenue) }}</strong> in revenue and retained
                        <strong>₱{{ number_format($totalNetProfit) }}</strong> as net profit, an average margin of
                        <strong>{{ $overallMargin }}%</strong> — {{ $pmStatusWord }} the {{ $pmTarget }}% target.
                        On-time delivery stands at <strong>{{ $onTimeRate }}%</strong>, {{ $otStatusWord }} the {{ $otTarget }}% target
                        @if($delayedCount > 0)
                            ({{ $delayedCount }} project{{ $delayedCount !== 1 ? 's' : '' }} delayed{{ $avgDelayDays > 0 ? ', averaging ~'.$avgDelayDays.' days late' : '' }}),
                        @else
                            with zero delays recorded,
                        @endif
                        while budget adherence is at <strong>{{ $avgBudgetAdherence }}%</strong>, {{ $baStatusWord }} the {{ $baTarget }}% target.
                        @if($pmForecastWord)
                            The WMA forecast model projects profit margin to be <strong>{{ $pmForecastWord }}</strong> on the next project, landing around <strong>{{ $next3Forecast[0]['pm'] }}%</strong>.
                        @endif
                        @if($narrativeSaved > 0)
                            Overall, the company saved <strong>₱{{ number_format($narrativeSaved) }}</strong> against the contracted value across this period.
                        @endif
                    </p>
                </div>
            </div>

            {{-- ── KPI CARDS ── --}}
            <div style="font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:10px;">Core KPIs</div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px;">

                {{-- Profit Margin --}}
                <div class="card" style="margin-bottom:0;overflow:hidden;">
                    <div style="background:var(--cream-soft);padding:16px 18px;border-bottom:1px solid var(--border);">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <span style="font-size:13px;font-weight:800;color:var(--dark);">Profit Margin</span>
                            @php
                                $pmLabel = $pmDiff < 0 ? 'Below target' : ($pmDiff <= 5 ? 'On target' : 'Excellent');
                                $pmChip  = $pmDiff < 0 ? 'icon-chip-warning' : 'icon-chip-success';
                            @endphp
                            <span class="{{ $pmChip }}" style="font-size:11px;font-weight:800;padding:3px 10px;border-radius:999px;">
                                {{ $pmLabel }}
                            </span>
                        </div>
                        <div style="display:flex;align-items:flex-end;gap:12px;">
                            <div>
                                <div style="font-size:36px;font-weight:900;color:var(--dark);line-height:1;">{{ $avgProfitMargin }}%</div>
                                <div style="font-size:11px;color:var(--muted);margin-top:3px;">
                                    {{ $pmDiff >= 0 ? '+' : '' }}{{ $pmDiff }}% vs {{ $pmTarget }}% target
                                </div>
                            </div>
                            <canvas id="pmDonut" width="64" height="64" style="width:64px!important;height:64px!important;flex-shrink:0;margin-bottom:4px;"></canvas>
                        </div>
                    </div>
                    <div style="padding:14px 18px;display:flex;flex-direction:column;gap:8px;">
                        @foreach([
                            ['Revenue received', '₱'.number_format($totalRevenue), null],
                            ['Material cost',    '₱'.number_format($totalMatCost), null],
                            ['Labor cost',       '₱'.number_format($totalLaborCost), null],
                            ['Net profit',       '₱'.number_format($totalNetProfit), 'var(--success)'],
                        ] as $row)
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;padding-bottom:8px;border-bottom:1px solid var(--border);">
                            <span style="color:var(--muted);">{{ $row[0] }}</span>
                            <strong style="color:{{ $row[2] ?? 'var(--dark)' }};">{{ $row[1] }}</strong>
                        </div>
                        @endforeach
                        <div style="height:4px;background:var(--cream-deep);border-radius:999px;margin-top:4px;">
                            <div style="height:100%;width:{{ min(100,$avgProfitMargin) }}%;background:{{ $pmDiff >= 0 ? 'var(--success)' : 'var(--warning)' }};border-radius:999px;"></div>
                        </div>
                    </div>
                </div>

                {{-- On-Time Delivery --}}
                @php $otChip = $otDiff >= 0 ? 'icon-chip-success' : 'icon-chip-danger'; $otColor = $otDiff >= 0 ? 'var(--success)' : 'var(--danger)'; @endphp
                <div class="card" style="margin-bottom:0;overflow:hidden;">
                    <div style="background:var(--cream-soft);padding:16px 18px;border-bottom:1px solid var(--border);">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <span style="font-size:13px;font-weight:800;color:var(--dark);">On-Time Delivery</span>
                            @php
                                $otLabel = $otDiff < 0 ? 'Below target' : ($otDiff <= 5 ? 'On target' : 'Excellent');
                            @endphp
                            <span class="{{ $otChip }}" style="font-size:11px;font-weight:800;padding:3px 10px;border-radius:999px;">
                                {{ $otLabel }}
                            </span>
                        </div>
                        <div style="display:flex;align-items:flex-end;gap:12px;">
                            <div>
                                <div style="font-size:36px;font-weight:900;color:var(--dark);line-height:1;">{{ $onTimeRate }}%</div>
                                <div style="font-size:11px;color:var(--muted);margin-top:3px;">
                                    {{ $otDiff >= 0 ? '+' : '' }}{{ abs($otDiff) }}% vs {{ $otTarget }}% target
                                </div>
                            </div>
                            <canvas id="otDonut" width="64" height="64" style="width:64px!important;height:64px!important;flex-shrink:0;margin-bottom:4px;"></canvas>
                        </div>
                    </div>
                    <div style="padding:14px 18px;display:flex;flex-direction:column;gap:8px;">
                        @foreach([
                            ['Total projects',  $count.' projects', null],
                            ['Delivered on time', $onTimeCount.' projects', 'var(--success)'],
                            ['Delayed',         $delayedCount.' project'.($delayedCount!==1?'s':''), $delayedCount>0?'var(--danger)':null],
                            ['Avg delay (delayed)', $avgDelayDays > 0 ? '~'.$avgDelayDays.' days' : '—', $avgDelayDays>0?'var(--warning)':null],
                        ] as $row)
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;padding-bottom:8px;border-bottom:1px solid var(--border);">
                            <span style="color:var(--muted);">{{ $row[0] }}</span>
                            <strong style="color:{{ $row[2] ?? 'var(--dark)' }};">{{ $row[1] }}</strong>
                        </div>
                        @endforeach
                        <div style="height:4px;background:var(--cream-deep);border-radius:999px;margin-top:4px;">
                            <div style="height:100%;width:{{ min(100,$onTimeRate) }}%;background:{{ $otColor }};border-radius:999px;"></div>
                        </div>
                    </div>
                </div>

                {{-- Budget Adherence --}}
                @php
                    $baLabel = $baDiff < 0 ? 'Below target' : ($baDiff <= 5 ? 'On target' : 'Exceeding');
                    $baChip  = $baDiff < 0 ? 'icon-chip-danger' : ($baDiff <= 5 ? 'icon-chip-success' : 'icon-chip-info');
                    $baColor = $baDiff < 0 ? 'var(--danger)' : ($baDiff <= 5 ? 'var(--success)' : 'var(--info)');
                @endphp
                <div class="card" style="margin-bottom:0;overflow:hidden;">
                    <div style="background:var(--cream-soft);padding:16px 18px;border-bottom:1px solid var(--border);">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <span style="font-size:13px;font-weight:800;color:var(--dark);">Budget Adherence</span>
                            <span class="{{ $baChip }}" style="font-size:11px;font-weight:800;padding:3px 10px;border-radius:999px;">
                                {{ $baLabel }}
                            </span>
                        </div>
                        <div style="display:flex;align-items:flex-end;gap:12px;">
                            <div>
                                <div style="font-size:36px;font-weight:900;color:var(--dark);line-height:1;">{{ $avgBudgetAdherence }}%</div>
                                <div style="font-size:11px;color:var(--muted);margin-top:3px;">
                                    {{ $baDiff < 0 ? 'Below' : ($baDiff <= 5 ? 'Meets' : 'Exceeds') }} {{ $baTarget }}% target
                                </div>
                            </div>
                            <canvas id="baDonut" width="64" height="64" style="width:64px!important;height:64px!important;flex-shrink:0;margin-bottom:4px;"></canvas>
                        </div>
                    </div>
                    <div style="padding:14px 18px;display:flex;flex-direction:column;gap:8px;">
                        @php $saved = max(0,$totalContracted-$totalActualSpend); @endphp
                        @foreach([
                            ['Total contracted',   '₱'.number_format($totalContracted), null],
                            ['Actual spend',       '₱'.number_format($totalActualSpend), null],
                            ['Net savings',        '₱'.number_format($saved), $saved>0?'var(--success)':'var(--danger)'],
                            ['Projects over budget', $projectKpis->where('budget_adherence','<',100)->count().' of '.$count, null],
                        ] as $row)
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;padding-bottom:8px;border-bottom:1px solid var(--border);">
                            <span style="color:var(--muted);">{{ $row[0] }}</span>
                            <strong style="color:{{ $row[2] ?? 'var(--dark)' }};">{{ $row[1] }}</strong>
                        </div>
                        @endforeach
                        <div style="height:4px;background:var(--cream-deep);border-radius:999px;margin-top:4px;">
                            <div style="height:100%;width:{{ min(100,$avgBudgetAdherence) }}%;background:{{ $baColor }};border-radius:999px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── REVENUE vs SPEND (per project bar chart) + TOP CLIENTS ── --}}
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:28px;">

                {{-- Revenue vs Spend chart --}}
                <div class="card" style="margin-bottom:0;overflow:hidden;">
                    {{-- Header --}}
                    <div style="padding:18px 20px 14px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                        <div>
                            <div style="font-size:14px;font-weight:800;color:var(--dark);margin-bottom:2px;">Revenue vs. Actual Spend</div>
                            <div style="font-size:11px;color:var(--muted);">Per project · Showing {{ min($count,15) }} of {{ $count }} completed</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                            @foreach([['var(--success)','Revenue'],['var(--danger)','Actual Spend'],['var(--info)','Net Profit']] as $leg)
                            <div style="display:flex;align-items:center;gap:5px;">
                                <div style="width:10px;height:10px;border-radius:3px;background:{{ $leg[0] }};flex-shrink:0;"></div>
                                <span style="font-size:11px;color:var(--muted);font-weight:600;">{{ $leg[1] }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    {{-- Chart --}}
                    <div style="padding:16px 20px 20px;">
                        <canvas id="revSpendChart" height="190"></canvas>
                    </div>
                </div>

                {{-- Top Clients by Revenue --}}
                @php
                    $clientRevenue = $projectKpis->groupBy('client')->map(function($g) {
                        return [
                            'client'  => $g->first()['client'],
                            'revenue' => $g->sum('received'),
                            'count'   => $g->count(),
                            'profit'  => $g->sum('est_profit'),
                        ];
                    })->sortByDesc('revenue')->values()->take(6);
                    $maxRev = $clientRevenue->max('revenue') ?: 1;
                @endphp
                <div class="card" style="margin-bottom:0;overflow:hidden;display:flex;flex-direction:column;">
                    {{-- Header --}}
                    <div style="padding:18px 20px 14px;border-bottom:1px solid var(--border);">
                        <div style="font-size:14px;font-weight:800;color:var(--dark);margin-bottom:2px;">Top Clients by Revenue</div>
                        <div style="font-size:11px;color:var(--muted);">Ranked by total received across all projects</div>
                    </div>
                    {{-- Client list --}}
                    <div style="padding:12px 20px;display:flex;flex-direction:column;gap:0;flex:1;">
                        @foreach($clientRevenue as $i => $cr)
                        @php $pct = round(($cr['revenue'] / $maxRev) * 100); @endphp
                        <div style="padding:10px 0;{{ !$loop->last ? 'border-bottom:1px solid var(--border);' : '' }}">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px;">
                                <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                                    <div class="icon-chip-info" style="width:26px;height:26px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <span style="font-size:11px;font-weight:900;">{{ $i + 1 }}</span>
                                    </div>
                                    <div style="min-width:0;">
                                        <div style="font-size:12px;font-weight:700;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:110px;">{{ $cr['client'] }}</div>
                                        <div style="font-size:10px;color:var(--muted);">{{ $cr['count'] }} project{{ $cr['count']!==1?'s':'' }}</div>
                                    </div>
                                </div>
                                <div style="text-align:right;flex-shrink:0;">
                                    <div style="font-size:13px;font-weight:900;color:var(--dark);">₱{{ number_format($cr['revenue']) }}</div>
                                    <div style="font-size:10px;color:var(--success);font-weight:700;">+₱{{ number_format($cr['profit']) }} profit</div>
                                </div>
                            </div>
                            <div style="height:5px;background:var(--cream-deep);border-radius:999px;overflow:hidden;">
                                <div style="height:100%;width:{{ $pct }}%;background:var(--info);border-radius:999px;transition:width .6s ease;"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ── FORECASTING ── --}}
            <div style="font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:10px;">
                Forecasting — Next 3 Projects
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:28px;">
                {{-- Revenue & profit margin forecast --}}
                <div class="card" style="margin-bottom:0;padding:20px;grid-column:span 2;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                        <div style="font-size:14px;font-weight:800;color:var(--dark);">Revenue &amp; Profit Margin Forecast</div>
                        <div style="display:flex;gap:12px;font-size:11px;color:var(--muted);">
                            <span><span style="display:inline-block;width:12px;height:3px;background:var(--success);vertical-align:middle;margin-right:3px;border-radius:2px;"></span>Revenue (actual)</span>
                            <span><span style="display:inline-block;width:12px;height:2px;border-top:2px dashed var(--success);vertical-align:middle;margin-right:3px;"></span>Revenue (forecast)</span>
                            <span><span style="display:inline-block;width:8px;height:8px;background:var(--info);border-radius:50%;vertical-align:middle;margin-right:3px;"></span>Margin %</span>
                        </div>
                    </div>
                    <canvas id="revForecastChart" height="120"></canvas>
                </div>

                {{-- Forecast summary table --}}
                <div class="card" style="margin-bottom:0;padding:18px;display:flex;flex-direction:column;">
                    <div style="font-size:13px;font-weight:800;margin-bottom:12px;">Next 3 Projects — Est.</div>
                    <table style="width:100%;border-collapse:collapse;font-size:12px;flex:1;">
                        <thead>
                            <tr style="border-bottom:1px solid var(--border);">
                                <th style="text-align:left;padding:5px 6px;font-size:10px;font-weight:800;color:var(--muted);text-transform:uppercase;">KPI</th>
                                <th style="text-align:center;padding:5px 6px;font-size:10px;font-weight:800;color:var(--muted);">P+1</th>
                                <th style="text-align:center;padding:5px 6px;font-size:10px;font-weight:800;color:var(--muted);">P+2</th>
                                <th style="text-align:center;padding:5px 6px;font-size:10px;font-weight:800;color:var(--muted);">P+3</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach([
                                ['Revenue',       '₱'.number_format(round($next3Forecast[0]['rev']/1000)).'k', '₱'.number_format(round($next3Forecast[1]['rev']/1000)).'k', '₱'.number_format(round($next3Forecast[2]['rev']/1000)).'k'],
                                ['Profit margin', $next3Forecast[0]['pm'].'%', $next3Forecast[1]['pm'].'%', $next3Forecast[2]['pm'].'%'],
                                ['OTD rate',      $next3Forecast[0]['ot'].'%', $next3Forecast[1]['ot'].'%', $next3Forecast[2]['ot'].'%'],
                                ['Budget adh.',   $next3Forecast[0]['ba'].'%', $next3Forecast[1]['ba'].'%', $next3Forecast[2]['ba'].'%'],
                            ] as $fr)
                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:8px 6px;font-weight:600;font-size:11px;color:var(--dark);">{{ $fr[0] }}</td>
                                <td style="text-align:center;padding:8px 6px;font-size:11px;">{{ $fr[1] }}</td>
                                <td style="text-align:center;padding:8px 6px;font-size:11px;">{{ $fr[2] }}</td>
                                <td style="text-align:center;padding:8px 6px;font-size:11px;">{{ $fr[3] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="icon-chip-info" style="margin-top:12px;padding:8px 10px;border-radius:8px;font-size:11px;line-height:1.5;">
                        WMA model &nbsp;·&nbsp; Weighted on last 3 projects &nbsp;·&nbsp; Updates with each filter.
                    </div>
                </div>
            </div>

            {{-- ── OTD & BUDGET FORECAST + INSIGHTS ── --}}
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:28px;">
                <div class="card" style="margin-bottom:0;padding:20px;">
                    <div style="font-size:13px;font-weight:800;color:var(--dark);margin-bottom:8px;">OTD Rate Forecast</div>
                    <div style="display:flex;gap:10px;font-size:11px;color:var(--muted);margin-bottom:10px;">
                        <span><span style="display:inline-block;width:8px;height:8px;background:var(--warning);border-radius:50%;vertical-align:middle;margin-right:3px;"></span>Actual</span>
                        <span><span style="display:inline-block;width:10px;height:2px;border-top:2px dashed var(--warning);vertical-align:middle;margin-right:3px;"></span>Forecast</span>
                        <span><span style="display:inline-block;width:10px;height:2px;background:var(--danger);opacity:.45;vertical-align:middle;margin-right:3px;"></span>90% target</span>
                    </div>
                    <canvas id="otForecastChart" height="120"></canvas>
                </div>
                <div class="card" style="margin-bottom:0;padding:20px;">
                    <div style="font-size:13px;font-weight:800;color:var(--dark);margin-bottom:8px;">Budget Adherence Forecast</div>
                    <div style="display:flex;gap:10px;font-size:11px;color:var(--muted);margin-bottom:10px;">
                        <span><span style="display:inline-block;width:8px;height:8px;background:var(--info);border-radius:2px;vertical-align:middle;margin-right:3px;"></span>Actual</span>
                        <span><span style="display:inline-block;width:8px;height:8px;background:var(--info);opacity:.3;border-radius:2px;vertical-align:middle;margin-right:3px;"></span>Forecast</span>
                    </div>
                    <canvas id="baForecastChart" height="120"></canvas>
                </div>
                <div class="card" style="margin-bottom:0;padding:20px;">
                    <div style="font-size:13px;font-weight:800;color:var(--dark);margin-bottom:14px;">Insights &amp; Actions</div>
                    @php
                        $insights = [];

                        // On-Time Delivery
                        if ($onTimeRate >= $otTarget) {
                            $insights[] = ['kpi'=>'otd', 'icon'=>'✓', 'chip'=>'icon-chip-success', 'text'=>'OTD at '.$onTimeRate.'% — delivery schedule is well-managed.'];
                        } else {
                            $insights[] = ['kpi'=>'otd', 'icon'=>'!', 'chip'=>'icon-chip-danger', 'text'=>'OTD at '.$onTimeRate.'%. '.$delayedCount.' project'.($delayedCount!==1?'s':'').' delayed'.($avgDelayDays>0?' (~'.$avgDelayDays.' days avg)':'').'.'];
                        }

                        // Profit Margin
                        if ($avgProfitMargin >= $pmTarget) {
                            $insights[] = ['kpi'=>'profit', 'icon'=>'✓', 'chip'=>'icon-chip-success', 'text'=>'Profit margin '.$avgProfitMargin.'% is above the '.$pmTarget.'% target.'];
                        } else {
                            $insights[] = ['kpi'=>'profit', 'icon'=>'↑', 'chip'=>'icon-chip-warning', 'text'=>'Margin at '.$avgProfitMargin.'% vs '.$pmTarget.'% target. Review labor costing in next quotation.'];
                        }

                        // Budget Adherence
                        if ($avgBudgetAdherence >= $baTarget) {
                            $insights[] = ['kpi'=>'budget', 'icon'=>'✓', 'chip'=>'icon-chip-success', 'text'=>'Budget adherence at '.$avgBudgetAdherence.'% — spending is within the planned budget.'];
                        } else {
                            $insights[] = ['kpi'=>'budget', 'icon'=>'!', 'chip'=>'icon-chip-danger', 'text'=>'Budget adherence at '.$avgBudgetAdherence.'% vs '.$baTarget.'% target. Actual spend is exceeding the BOM budget.'];
                        }

                        // WMA forecast trend (next project vs current average)
                        if ($count > 0) {
                            $pmDelta = round($next3Forecast[0]['pm'] - $avgProfitMargin, 1);
                            if (abs($pmDelta) >= 1) {
                                $insights[] = [
                                    'kpi'   => 'profit',
                                    'icon'  => $pmDelta > 0 ? '↗' : '↘',
                                    'chip'  => $pmDelta > 0 ? 'icon-chip-success' : 'icon-chip-danger',
                                    'text'  => 'WMA forecast projects margin to '.($pmDelta > 0 ? 'rise' : 'fall').' to '.$next3Forecast[0]['pm'].'% on the next project.',
                                ];
                            }
                        }

                        // Net savings
                        $saved = max(0, $totalContracted - $totalActualSpend);
                        if ($saved > 0) {
                            $insights[] = ['kpi'=>'all', 'icon'=>'₱', 'chip'=>'icon-chip-info', 'text'=>'₱'.number_format($saved).' net savings vs contracted value across '.$count.' project'.($count!==1?'s':'').'.'];
                        }

                        // Avg revenue per project
                        $insights[] = ['kpi'=>'all', 'icon'=>'→', 'chip'=>'icon-chip-neutral', 'text'=>'Avg revenue per project: ₱'.number_format($count > 0 ? round($totalRevenue/$count) : 0).'.'];

                        // When a specific KPI Focus filter is active, surface its insights first
                        if ($filterKpi !== 'all') {
                            usort($insights, fn($a, $b) => ($b['kpi'] === $filterKpi ? 1 : 0) <=> ($a['kpi'] === $filterKpi ? 1 : 0));
                        }

                        $insights = array_slice($insights, 0, 5);
                    @endphp
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        @foreach($insights as $ins)
                        <div style="display:flex;align-items:flex-start;gap:10px;">
                            <span class="{{ $ins['chip'] }}" style="min-width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;flex-shrink:0;">{{ $ins['icon'] }}</span>
                            <span style="font-size:12px;color:var(--dark);line-height:1.5;">{{ $ins['text'] }}</span>
                        </div>
                        @endforeach
                    </div>
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
        if (typeof Chart === 'undefined') return;

        var projectLabels = @json($projectKpis->pluck('short')->toArray());
        var pmData  = @json($projectKpis->pluck('profit_margin')->toArray());
        var otData  = @json($projectKpis->map(fn($p) => $p['on_time'] ? 100 : 0)->toArray());
        var baData  = @json($projectKpis->pluck('budget_adherence')->toArray());
        var revData = @json($projectKpis->pluck('received')->toArray());
        var spendData = @json($projectKpis->pluck('total_spend')->toArray());
        var profitData = @json($projectKpis->map(fn($p) => $p['est_profit'])->toArray());
        var next3   = @json($next3Forecast);
        var n = projectLabels.length;
        var displayN = Math.min(n, 15);
        var allLabels = projectLabels.concat(['P'+(n+1),'P'+(n+2),'P'+(n+3)]);

        // ── Donut helper ──────────────────────────────────────────────────
        function makeDonut(id, val, color) {
            var el = document.getElementById(id);
            if (!el) return;
            var safe = Math.min(100, Math.max(0, val));
            new Chart(el, {
                type: 'doughnut',
                data: { datasets: [{ data: [safe, 100 - safe], backgroundColor: [color, '#e5e7eb'], borderWidth: 0, hoverOffset: 0 }] },
                options: { responsive: false, maintainAspectRatio: false, cutout: '70%', animation: { animateRotate: true }, plugins: { legend: { display: false }, tooltip: { enabled: false } } }
            });
        }
        makeDonut('pmDonut', {{ $avgProfitMargin }},  '{{ $pmDiff >= 0 ? "#207A3A" : "#8A6100" }}');
        makeDonut('otDonut', {{ $onTimeRate }},       '{{ $otDiff >= 0 ? "#207A3A" : "#B42318" }}');
        makeDonut('baDonut', {{ $avgBudgetAdherence }},'{{ $baDiff < 0 ? "#B42318" : ($baDiff <= 5 ? "#207A3A" : "#2A4EAA") }}');

        // ── Revenue vs Spend bar chart (last 15 projects) ─────────────────
        var rsEl = document.getElementById('revSpendChart');
        if (rsEl) {
            new Chart(rsEl, {
                type: 'bar',
                data: {
                    labels: projectLabels.slice(-displayN),
                    datasets: [
                        { label: 'Revenue',      data: revData.slice(-displayN),    backgroundColor: '#207A3A', borderRadius: 3, borderSkipped: false },
                        { label: 'Actual Spend', data: spendData.slice(-displayN),  backgroundColor: '#B42318', borderRadius: 3, borderSkipped: false },
                        { label: 'Net Profit',   data: profitData.slice(-displayN), backgroundColor: '#2A4EAA', borderRadius: 3, borderSkipped: false },
                    ]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                        y: { grid: { color: 'rgba(0,0,0,.05)' }, ticks: { callback: function(v){ return '₱'+Math.round(v/1000)+'k'; }, font: { size: 10 } } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }

        // ── Revenue & profit margin forecast ──────────────────────────────
        var revForecast = next3.map(function(d){ return d.rev; });
        var pmForecast  = next3.map(function(d){ return d.pm; });
        var revActual   = revData.slice().concat([null,null,null]);
        var pmActual    = pmData.slice().concat(pmForecast);
        var revFcPadded = new Array(Math.max(0, n-1)).fill(null).concat([revData[n-1]||0]).concat(revForecast);

        var revEl = document.getElementById('revForecastChart');
        if (revEl) {
            new Chart(revEl, {
                type: 'line',
                data: {
                    labels: allLabels,
                    datasets: [
                        { label: 'Actual revenue',   data: revActual,    borderColor: '#207A3A', backgroundColor: 'rgba(32,122,58,.08)', fill: true, tension: 0.4, pointRadius: 3, borderWidth: 2.5 },
                        { label: 'Forecast revenue', data: revFcPadded,  borderColor: '#207A3A', borderDash: [5,4], tension: 0.4, pointRadius: 3, borderWidth: 2, pointBackgroundColor: '#207A3A' },
                        { label: 'Profit margin %',  data: pmActual,     borderColor: '#2A4EAA', yAxisID: 'y2', tension: 0.4, pointRadius: 3, borderWidth: 2 }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                        y:  { ticks: { callback: function(v){ return '₱'+Math.round(v/1000)+'k'; }, font: { size: 10 } }, grid: { color: 'rgba(0,0,0,.05)' } },
                        y2: { position: 'right', min: 0, max: 100, ticks: { callback: function(v){ return v+'%'; }, font: { size: 10 } }, grid: { display: false } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }

        // ── OTD forecast ──────────────────────────────────────────────────
        var otForecast = next3.map(function(d){ return d.ot; });
        var otActual   = otData.concat([null,null,null]);
        var otFcPad    = new Array(Math.max(0,n-1)).fill(null).concat([otData[n-1]||0]).concat(otForecast);

        var otEl = document.getElementById('otForecastChart');
        if (otEl) {
            new Chart(otEl, {
                type: 'line',
                data: {
                    labels: allLabels,
                    datasets: [
                        { label: 'Actual',     data: otActual,                               borderColor: '#8A6100', pointBackgroundColor: '#8A6100', tension: 0.4, borderWidth: 2, pointRadius: 3 },
                        { label: 'Forecast',   data: otFcPad,                                borderColor: '#8A6100', borderDash: [5,4], tension: 0.4, borderWidth: 2, pointRadius: 2 },
                        { label: 'Target 90%', data: allLabels.map(function(){ return 90; }), borderColor: 'rgba(180,35,24,.45)', borderDash: [4,4], borderWidth: 1.5, pointRadius: 0 }
                    ]
                },
                options: {
                    responsive: true,
                    scales: { x: { grid: { display: false }, ticks: { font:{size:10} } }, y: { min: 0, max: 110, ticks: { callback: function(v){ return v+'%'; }, font:{size:10} }, grid: { color: 'rgba(0,0,0,.05)' } } },
                    plugins: { legend: { display: false } }
                }
            });
        }

        // ── Budget adherence forecast ─────────────────────────────────────
        var baForecast = next3.map(function(d){ return d.ba; });
        var baActual   = baData.concat([null,null,null]);
        var baFcPad    = new Array(Math.max(0,n-1)).fill(null).concat([baData[n-1]||0]).concat(baForecast);

        var baEl = document.getElementById('baForecastChart');
        if (baEl) {
            new Chart(baEl, {
                type: 'bar',
                data: {
                    labels: allLabels,
                    datasets: [
                        { label: 'Actual',   data: baActual, backgroundColor: '#2A4EAA', borderRadius: 3 },
                        { label: 'Forecast', data: baFcPad,  backgroundColor: 'rgba(42,78,170,.3)', borderRadius: 3 }
                    ]
                },
                options: {
                    responsive: true,
                    scales: { x: { grid: { display: false }, ticks: { font:{size:10} } }, y: { min: 0, max: 110, ticks: { callback: function(v){ return v+'%'; }, font:{size:10} }, grid: { color: 'rgba(0,0,0,.05)' } } },
                    plugins: { legend: { display: false } }
                }
            });
        }

        @endif
    });
    </script>
</body>
</html>
