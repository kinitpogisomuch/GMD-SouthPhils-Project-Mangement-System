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
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                <div>
                    <h1 style="font-size:22px;font-weight:900;margin:0 0 4px;">KPI &amp; Analytics</h1>
                    <p style="font-size:13px;color:var(--muted);margin:0;">
                        GMD South Phils &nbsp;·&nbsp; {{ $currentQuarterLabel }} &nbsp;·&nbsp; {{ now()->year }}
                        @if($activeProjects > 0)
                            &nbsp;·&nbsp; <span style="color:#2563eb;font-weight:700;">{{ $activeProjects }} project{{ $activeProjects !== 1 ? 's' : '' }} active</span>
                        @endif
                    </p>
                </div>
            </div>

            {{-- ── Filters ── --}}
            <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:14px 18px;margin-bottom:24px;">
                <form method="GET" action="{{ route('admin.reports') }}" style="display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;">
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);display:block;margin-bottom:5px;">Timeline</label>
                        <select name="quarter" class="filter-select" onchange="this.form.submit()" style="min-width:145px;">
                            <option value="all" {{ $filterQuarter === 'all' ? 'selected' : '' }}>All periods</option>
                            <option value="q1"  {{ $filterQuarter === 'q1'  ? 'selected' : '' }}>Q1 — Jan to Mar</option>
                            <option value="q2"  {{ $filterQuarter === 'q2'  ? 'selected' : '' }}>Q2 — Apr to Jun</option>
                            <option value="q3"  {{ $filterQuarter === 'q3'  ? 'selected' : '' }}>Q3 — Jul to Sep</option>
                            <option value="q4"  {{ $filterQuarter === 'q4'  ? 'selected' : '' }}>Q4 — Oct to Dec</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);display:block;margin-bottom:5px;">KPI Focus</label>
                        <select name="kpi" class="filter-select" onchange="this.form.submit()" style="min-width:165px;">
                            <option value="all">All KPIs</option>
                            <option value="profit"   {{ $filterKpi === 'profit'   ? 'selected' : '' }}>Profit margin</option>
                            <option value="otd"      {{ $filterKpi === 'otd'      ? 'selected' : '' }}>On-time delivery</option>
                            <option value="budget"   {{ $filterKpi === 'budget'   ? 'selected' : '' }}>Budget adherence</option>
                        </select>
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
            <div style="text-align:center;padding:80px 20px;color:var(--muted);">
                <i data-lucide="bar-chart-2" style="width:48px;height:48px;opacity:.3;display:block;margin:0 auto 16px;"></i>
                <p style="font-size:16px;font-weight:700;">No completed projects match the selected filters.</p>
                <p style="font-size:13px;">Try selecting a different timeline or project.</p>
            </div>
            @else

            @php
                $pmTarget  = 20;
                $otTarget  = 90;
                $baTarget  = 90;
                $pmDiff    = round($avgProfitMargin - $pmTarget, 1);
                $otDiff    = round($onTimeRate - $otTarget, 1);
                $baDiff    = round($avgBudgetAdherence - $baTarget, 1);
                $totalNetProfit = max(0, $totalRevenue - $totalActualSpend);
                $overallMargin  = $totalRevenue > 0 ? round(($totalNetProfit / $totalRevenue) * 100, 1) : 0;
            @endphp

            {{-- ── FINANCIAL SUMMARY STRIP ── --}}
            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:24px;">
                @php
                    $strips = [
                        ['label'=>'Total Revenue',    'value'=>'₱'.number_format($totalRevenue),       'color'=>'#16a34a', 'icon'=>'trending-up'],
                        ['label'=>'Total Net Profit', 'value'=>'₱'.number_format($totalNetProfit),     'color'=>'#2563eb', 'icon'=>'banknote'],
                        ['label'=>'Avg Profit Margin','value'=>$overallMargin.'%',                     'color'=>$overallMargin>=20?'#16a34a':'#f59e0b', 'icon'=>'percent'],
                        ['label'=>'Completed Projects','value'=>$count.' projects',                    'color'=>'#7c3aed', 'icon'=>'check-circle-2'],
                        ['label'=>'On-Time Delivery', 'value'=>$onTimeRate.'%',                        'color'=>$onTimeRate>=90?'#16a34a':'#ef4444', 'icon'=>'clock'],
                    ];
                @endphp
                @foreach($strips as $s)
                <div style="background:var(--white);border:1px solid var(--border);border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px;">
                    <div style="width:36px;height:36px;border-radius:10px;background:{{ $s['color'] }}18;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="{{ $s['icon'] }}" style="width:17px;height:17px;color:{{ $s['color'] }};"></i>
                    </div>
                    <div style="min-width:0;">
                        <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;">{{ $s['label'] }}</div>
                        <div style="font-size:15px;font-weight:900;color:var(--dark);margin-top:2px;white-space:nowrap;">{{ $s['value'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- ── KPI CARDS ── --}}
            <div style="font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:10px;">Core KPIs</div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px;">

                {{-- Profit Margin --}}
                <div style="background:var(--white);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.05);">
                    <div style="background:linear-gradient(135deg,#fffbeb,#fef3c7);padding:16px 18px;border-bottom:1px solid #fde68a;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <span style="font-size:13px;font-weight:800;color:#92400e;">Profit Margin</span>
                            <span style="font-size:11px;font-weight:700;padding:2px 10px;border-radius:999px;background:{{ $pmDiff >= 0 ? '#10B981' : '#F59E0B' }};color:#fff;">
                                {{ $pmDiff >= 0 ? 'On target' : 'Near target' }}
                            </span>
                        </div>
                        <div style="display:flex;align-items:flex-end;gap:12px;">
                            <div>
                                <div style="font-size:36px;font-weight:900;color:#78350f;line-height:1;">{{ $avgProfitMargin }}%</div>
                                <div style="font-size:11px;color:#92400e;margin-top:3px;">
                                    {{ $pmDiff >= 0 ? '+' : '' }}{{ $pmDiff }}% vs {{ $pmTarget }}% target
                                </div>
                            </div>
                            <canvas id="pmDonut" width="64" height="64" style="width:64px!important;height:64px!important;flex-shrink:0;margin-bottom:4px;"></canvas>
                        </div>
                    </div>
                    <div style="padding:14px 18px;display:flex;flex-direction:column;gap:8px;">
                        @foreach([
                            ['Revenue received', '₱'.number_format($totalRevenue), null],
                            ['Material cost',    '₱'.number_format($totalMatCost), '#dc2626'],
                            ['Labor cost',       '₱'.number_format($totalLaborCost), '#7c3aed'],
                            ['Net profit',       '₱'.number_format($totalNetProfit), '#16a34a'],
                        ] as $row)
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;padding-bottom:8px;border-bottom:1px solid var(--border);">
                            <span style="color:var(--muted);">{{ $row[0] }}</span>
                            <strong style="color:{{ $row[2] ?? 'var(--dark)' }};">{{ $row[1] }}</strong>
                        </div>
                        @endforeach
                        <div style="height:4px;background:#fde68a;border-radius:999px;margin-top:4px;">
                            <div style="height:100%;width:{{ min(100,$avgProfitMargin) }}%;background:{{ $pmDiff >= 0 ? '#10B981' : '#F59E0B' }};border-radius:999px;"></div>
                        </div>
                    </div>
                </div>

                {{-- On-Time Delivery --}}
                @php $otColor = $otDiff >= 0 ? '#10B981' : '#EF4444'; $otBg = $otDiff >= 0 ? '#dcfce7' : '#fee2e2'; $otText = $otDiff >= 0 ? '#15803d' : '#991b1b'; @endphp
                <div style="background:var(--white);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.05);">
                    <div style="background:linear-gradient(135deg,{{ $otBg }},{{ $otBg }});padding:16px 18px;border-bottom:1px solid {{ $otDiff >= 0 ? '#bbf7d0' : '#fecaca' }};">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <span style="font-size:13px;font-weight:800;color:{{ $otText }};">On-Time Delivery</span>
                            <span style="font-size:11px;font-weight:700;padding:2px 10px;border-radius:999px;background:{{ $otColor }};color:#fff;">
                                {{ $otDiff >= 0 ? 'On target' : 'Below target' }}
                            </span>
                        </div>
                        <div style="display:flex;align-items:flex-end;gap:12px;">
                            <div>
                                <div style="font-size:36px;font-weight:900;color:{{ $otText }};line-height:1;">{{ $onTimeRate }}%</div>
                                <div style="font-size:11px;color:{{ $otText }};margin-top:3px;">
                                    {{ $otDiff >= 0 ? '+' : '' }}{{ abs($otDiff) }}% vs {{ $otTarget }}% target
                                </div>
                            </div>
                            <canvas id="otDonut" width="64" height="64" style="width:64px!important;height:64px!important;flex-shrink:0;margin-bottom:4px;"></canvas>
                        </div>
                    </div>
                    <div style="padding:14px 18px;display:flex;flex-direction:column;gap:8px;">
                        @foreach([
                            ['Total projects',  $count.' projects', null],
                            ['Delivered on time', $onTimeCount.' projects', '#16a34a'],
                            ['Delayed',         $delayedCount.' project'.($delayedCount!==1?'s':''), $delayedCount>0?'#ef4444':null],
                            ['Avg delay (delayed)', $avgDelayDays > 0 ? '~'.$avgDelayDays.' days' : '—', $avgDelayDays>0?'#f59e0b':null],
                        ] as $row)
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;padding-bottom:8px;border-bottom:1px solid var(--border);">
                            <span style="color:var(--muted);">{{ $row[0] }}</span>
                            <strong style="color:{{ $row[2] ?? 'var(--dark)' }};">{{ $row[1] }}</strong>
                        </div>
                        @endforeach
                        <div style="height:4px;background:{{ $otBg }};border-radius:999px;margin-top:4px;">
                            <div style="height:100%;width:{{ min(100,$onTimeRate) }}%;background:{{ $otColor }};border-radius:999px;"></div>
                        </div>
                    </div>
                </div>

                {{-- Budget Adherence --}}
                @php $baColor = $baDiff >= 0 ? '#2563EB' : '#ef4444'; @endphp
                <div style="background:var(--white);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.05);">
                    <div style="background:linear-gradient(135deg,#eff6ff,#dbeafe);padding:16px 18px;border-bottom:1px solid #bfdbfe;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <span style="font-size:13px;font-weight:800;color:#1e40af;">Budget Adherence</span>
                            <span style="font-size:11px;font-weight:700;padding:2px 10px;border-radius:999px;background:{{ $baColor }};color:#fff;">
                                {{ $baDiff >= 0 ? 'Exceeding' : 'Below target' }}
                            </span>
                        </div>
                        <div style="display:flex;align-items:flex-end;gap:12px;">
                            <div>
                                <div style="font-size:36px;font-weight:900;color:#1e3a8a;line-height:1;">{{ $avgBudgetAdherence }}%</div>
                                <div style="font-size:11px;color:#1e40af;margin-top:3px;">
                                    {{ $baDiff >= 0 ? 'Exceeds' : 'Below' }} {{ $baTarget }}% target
                                </div>
                            </div>
                            <canvas id="baDonut" width="64" height="64" style="width:64px!important;height:64px!important;flex-shrink:0;margin-bottom:4px;"></canvas>
                        </div>
                    </div>
                    <div style="padding:14px 18px;display:flex;flex-direction:column;gap:8px;">
                        @php $saved = max(0,$totalContracted-$totalActualSpend); @endphp
                        @foreach([
                            ['Total contracted',   '₱'.number_format($totalContracted), null],
                            ['Actual spend',       '₱'.number_format($totalActualSpend), '#d97706'],
                            ['Net savings',        '₱'.number_format($saved), $saved>0?'#16a34a':'#ef4444'],
                            ['Projects over budget', $projectKpis->where('budget_adherence','<',100)->count().' of '.$count, null],
                        ] as $row)
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;padding-bottom:8px;border-bottom:1px solid var(--border);">
                            <span style="color:var(--muted);">{{ $row[0] }}</span>
                            <strong style="color:{{ $row[2] ?? 'var(--dark)' }};">{{ $row[1] }}</strong>
                        </div>
                        @endforeach
                        <div style="height:4px;background:#bfdbfe;border-radius:999px;margin-top:4px;">
                            <div style="height:100%;width:{{ min(100,$avgBudgetAdherence) }}%;background:{{ $baColor }};border-radius:999px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── REVENUE vs SPEND (per project bar chart) + TOP CLIENTS ── --}}
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:28px;">

                {{-- Revenue vs Spend chart --}}
                <div style="background:var(--white);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.05);">
                    {{-- Header --}}
                    <div style="padding:18px 20px 14px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                        <div>
                            <div style="font-size:14px;font-weight:800;color:var(--dark);margin-bottom:2px;">Revenue vs. Actual Spend</div>
                            <div style="font-size:11px;color:var(--muted);">Per project · Showing {{ min($count,15) }} of {{ $count }} completed</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                            @foreach([['#10B981','Revenue'],['#f87171','Actual Spend'],['#2563eb','Net Profit']] as $leg)
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
                    $clrPalette = ['#2563eb','#10B981','#f59e0b','#7c3aed','#ef4444','#14b8a6'];
                @endphp
                <div style="background:var(--white);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.05);display:flex;flex-direction:column;">
                    {{-- Header --}}
                    <div style="padding:18px 20px 14px;border-bottom:1px solid var(--border);">
                        <div style="font-size:14px;font-weight:800;color:var(--dark);margin-bottom:2px;">Top Clients by Revenue</div>
                        <div style="font-size:11px;color:var(--muted);">Ranked by total received across all projects</div>
                    </div>
                    {{-- Client list --}}
                    <div style="padding:12px 20px;display:flex;flex-direction:column;gap:0;flex:1;">
                        @foreach($clientRevenue as $i => $cr)
                        @php
                            $pct = round(($cr['revenue'] / $maxRev) * 100);
                            $clr = $clrPalette[$i % count($clrPalette)];
                        @endphp
                        <div style="padding:10px 0;{{ !$loop->last ? 'border-bottom:1px solid var(--border);' : '' }}">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px;">
                                <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                                    <div style="width:26px;height:26px;border-radius:8px;background:{{ $clr }}18;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <span style="font-size:11px;font-weight:900;color:{{ $clr }};">{{ $i + 1 }}</span>
                                    </div>
                                    <div style="min-width:0;">
                                        <div style="font-size:12px;font-weight:700;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:110px;">{{ $cr['client'] }}</div>
                                        <div style="font-size:10px;color:var(--muted);">{{ $cr['count'] }} project{{ $cr['count']!==1?'s':'' }}</div>
                                    </div>
                                </div>
                                <div style="text-align:right;flex-shrink:0;">
                                    <div style="font-size:13px;font-weight:900;color:var(--dark);">₱{{ number_format($cr['revenue']) }}</div>
                                    <div style="font-size:10px;color:#16a34a;font-weight:700;">+₱{{ number_format($cr['profit']) }} profit</div>
                                </div>
                            </div>
                            <div style="height:5px;background:var(--cream-deep);border-radius:999px;overflow:hidden;">
                                <div style="height:100%;width:{{ $pct }}%;background:{{ $clr }};border-radius:999px;transition:width .6s ease;"></div>
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
                <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:20px;grid-column:span 2;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                        <div style="font-size:14px;font-weight:800;">Revenue &amp; Profit Margin Forecast</div>
                        <div style="display:flex;gap:12px;font-size:11px;color:var(--muted);">
                            <span><span style="display:inline-block;width:12px;height:3px;background:#10B981;vertical-align:middle;margin-right:3px;border-radius:2px;"></span>Revenue (actual)</span>
                            <span><span style="display:inline-block;width:12px;height:2px;border-top:2px dashed #10B981;vertical-align:middle;margin-right:3px;"></span>Revenue (forecast)</span>
                            <span><span style="display:inline-block;width:8px;height:8px;background:#2563EB;border-radius:50%;vertical-align:middle;margin-right:3px;"></span>Margin %</span>
                        </div>
                    </div>
                    <canvas id="revForecastChart" height="120"></canvas>
                </div>

                {{-- Forecast summary table --}}
                <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:18px;display:flex;flex-direction:column;">
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
                    <div style="margin-top:12px;padding:8px 10px;background:#eff6ff;border-radius:8px;font-size:11px;color:#1d4ed8;line-height:1.5;">
                        WMA model &nbsp;·&nbsp; Weighted on last 3 projects &nbsp;·&nbsp; Updates with each filter.
                    </div>
                </div>
            </div>

            {{-- ── OTD & BUDGET FORECAST + INSIGHTS ── --}}
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:28px;">
                <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:20px;">
                    <div style="font-size:13px;font-weight:800;margin-bottom:8px;">OTD Rate Forecast</div>
                    <div style="display:flex;gap:10px;font-size:11px;color:var(--muted);margin-bottom:10px;">
                        <span><span style="display:inline-block;width:8px;height:8px;background:#F59E0B;border-radius:50%;vertical-align:middle;margin-right:3px;"></span>Actual</span>
                        <span><span style="display:inline-block;width:10px;height:2px;border-top:2px dashed #F59E0B;vertical-align:middle;margin-right:3px;"></span>Forecast</span>
                        <span><span style="display:inline-block;width:10px;height:2px;background:#fca5a5;vertical-align:middle;margin-right:3px;"></span>90% target</span>
                    </div>
                    <canvas id="otForecastChart" height="120"></canvas>
                </div>
                <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:20px;">
                    <div style="font-size:13px;font-weight:800;margin-bottom:8px;">Budget Adherence Forecast</div>
                    <div style="display:flex;gap:10px;font-size:11px;color:var(--muted);margin-bottom:10px;">
                        <span><span style="display:inline-block;width:8px;height:8px;background:#2563EB;border-radius:2px;vertical-align:middle;margin-right:3px;"></span>Actual</span>
                        <span><span style="display:inline-block;width:8px;height:8px;background:#bfdbfe;border-radius:2px;vertical-align:middle;margin-right:3px;"></span>Forecast</span>
                    </div>
                    <canvas id="baForecastChart" height="120"></canvas>
                </div>
                <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:20px;">
                    <div style="font-size:13px;font-weight:800;margin-bottom:14px;">Insights &amp; Actions</div>
                    @php
                        $insights = [];
                        if ($onTimeRate >= 90) {
                            $insights[] = ['✓', '#16a34a', 'OTD at '.$onTimeRate.'% — delivery schedule is well-managed.'];
                        } else {
                            $insights[] = ['!', '#ef4444', 'OTD at '.$onTimeRate.'%. '.$delayedCount.' project'.($delayedCount!==1?'s':'').' delayed'.($avgDelayDays>0?' (~'.$avgDelayDays.' days avg)':'').'.'];
                        }
                        if ($avgProfitMargin >= $pmTarget) {
                            $insights[] = ['✓', '#16a34a', 'Profit margin '.$avgProfitMargin.'% is above the '.$pmTarget.'% target.'];
                        } else {
                            $insights[] = ['↑', '#f59e0b', 'Margin at '.$avgProfitMargin.'% vs '.$pmTarget.'% target. Review labor costing in next quotation.'];
                        }
                        $saved = max(0,$totalContracted-$totalActualSpend);
                        if ($saved > 0) {
                            $insights[] = ['₱', '#2563eb', '₱'.number_format($saved).' net savings vs contracted value across '.$count.' projects.'];
                        }
                        $insights[] = ['→', '#7c3aed', 'Avg revenue per project: ₱'.number_format($count > 0 ? round($totalRevenue/$count) : 0).'.'];
                    @endphp
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        @foreach($insights as $ins)
                        <div style="display:flex;align-items:flex-start;gap:10px;">
                            <span style="min-width:22px;height:22px;background:{{ $ins[1] }}18;color:{{ $ins[1] }};border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;flex-shrink:0;border:1px solid {{ $ins[1] }}40;">{{ $ins[0] }}</span>
                            <span style="font-size:12px;color:var(--dark);line-height:1.5;">{{ $ins[2] }}</span>
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
        makeDonut('pmDonut', {{ $avgProfitMargin }}, '#F59E0B');
        makeDonut('otDonut', {{ $onTimeRate }},       '{{ $otDiff >= 0 ? "#10B981" : "#EF4444" }}');
        makeDonut('baDonut', {{ $avgBudgetAdherence }},'#2563EB');

        // ── Revenue vs Spend bar chart (last 15 projects) ─────────────────
        var rsEl = document.getElementById('revSpendChart');
        if (rsEl) {
            new Chart(rsEl, {
                type: 'bar',
                data: {
                    labels: projectLabels.slice(-displayN),
                    datasets: [
                        { label: 'Revenue',      data: revData.slice(-displayN),    backgroundColor: '#10B981', borderRadius: 3, borderSkipped: false },
                        { label: 'Actual Spend', data: spendData.slice(-displayN),  backgroundColor: '#f87171', borderRadius: 3, borderSkipped: false },
                        { label: 'Net Profit',   data: profitData.slice(-displayN), backgroundColor: '#2563eb', borderRadius: 3, borderSkipped: false },
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
                        { label: 'Actual revenue',   data: revActual,    borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,.08)', fill: true, tension: 0.4, pointRadius: 3, borderWidth: 2.5 },
                        { label: 'Forecast revenue', data: revFcPadded,  borderColor: '#10B981', borderDash: [5,4], tension: 0.4, pointRadius: 3, borderWidth: 2, pointBackgroundColor: '#10B981' },
                        { label: 'Profit margin %',  data: pmActual,     borderColor: '#2563EB', yAxisID: 'y2', tension: 0.4, pointRadius: 3, borderWidth: 2 }
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
                        { label: 'Actual',     data: otActual,                               borderColor: '#F59E0B', pointBackgroundColor: '#F59E0B', tension: 0.4, borderWidth: 2, pointRadius: 3 },
                        { label: 'Forecast',   data: otFcPad,                                borderColor: '#F59E0B', borderDash: [5,4], tension: 0.4, borderWidth: 2, pointRadius: 2 },
                        { label: 'Target 90%', data: allLabels.map(function(){ return 90; }), borderColor: '#fca5a5', borderDash: [4,4], borderWidth: 1.5, pointRadius: 0 }
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
                        { label: 'Actual',   data: baActual, backgroundColor: '#2563EB', borderRadius: 3 },
                        { label: 'Forecast', data: baFcPad,  backgroundColor: '#bfdbfe', borderRadius: 3 }
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
