<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.employee.header')

    <main class="admin-content">
            @php
                $hour = (int) now()->timezone('Asia/Manila')->format('G');
                $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
            @endphp

            <!-- ── Hero greeting ── -->
            <div class="db-hero">
                <div class="db-hero-left">
                    <div>
                        <div class="db-greeting">{{ $greeting }}, {{ $employee->first_name }}</div>
                        <div class="db-subgreeting">Here's what's happening with your work today.</div>
                    </div>
                </div>
                <div class="db-hero-meta">
                    <div class="db-hero-date">
                        <i data-lucide="calendar-days"></i>
                        {{ now()->format('l, F j, Y') }}
                    </div>
                </div>
            </div>

            <style>
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
                @media (max-width: 768px) {
                    .db-hero { flex-direction: column; align-items: flex-start; padding: 4px 4px 20px; gap: 12px; }
                }
                /* Phones: greeting, subtitle and date pill centered */
                @media (max-width: 640px) {
                    .db-hero { align-items: center; text-align: center; }
                    .db-hero-left { justify-content: center; }
                }

                /* Stat card icons + side stripes in dark shades to match the dark theme
                   (darkest → lightest across the four cards), white icon on top */
                .db-stats .stat-card:nth-child(1) { --db-shade: #1a1a1a; }
                .db-stats .stat-card:nth-child(2) { --db-shade: #333333; }
                .db-stats .stat-card:nth-child(3) { --db-shade: #4d4d4d; }
                .db-stats .stat-card:nth-child(4) { --db-shade: #666666; }
                .db-stats .stat-card::before { background-color: var(--db-shade) !important; }
                .db-stats .stat-icon { background: var(--db-shade) !important; color: #fff !important; box-shadow: 0 4px 10px rgba(0, 0, 0, .14); }
                .db-stats .stat-icon svg { color: #fff !important; stroke: #fff; }

                /* Phones: the 4 stat cards sit 2 by 2 — icon on top, then the number and label */
                @media (max-width: 640px) {
                    .db-stats { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; gap: 12px; margin-top: 16px; }
                    .db-stats .stat-card { flex-direction: column; gap: 10px; padding: 16px 14px 16px 16px; border-radius: 18px; }
                    .db-stats .stat-card::before { top: 16px; height: 34px; }
                    .db-stats .stat-icon { width: 36px; height: 36px; border-radius: 10px; }
                    .db-stats .stat-icon svg { width: 18px; height: 18px; }
                    .db-stats .stat-info { min-width: 0; width: 100%; }
                    .db-stats .stat-value { font-size: clamp(16px, 5vw, 22px); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                    .db-stats .stat-label { font-size: 11.5px; line-height: 1.3; }
                }
            </style>

            <div class="stats-grid db-stats">
                <div class="stat-card blue">
                    <div class="stat-icon blue"><i data-lucide="folder-open"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $activeProjectsCount }}</div>
                        <div class="stat-label">Active Projects</div>
                    </div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon green"><i data-lucide="check-circle"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $completedProjectsCount }}</div>
                        <div class="stat-label">Completed Projects</div>
                    </div>
                </div>
                <div class="stat-card purple">
                    <div class="stat-icon purple"><i data-lucide="calendar-check"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ number_format($currentRecord->days_worked ?? 0, 0) }}</div>
                        <div class="stat-label">Days Worked This Week</div>
                    </div>
                </div>
                <div class="stat-card teal">
                    <div class="stat-icon teal"><i data-lucide="wallet"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱{{ number_format($currentRecord->net_pay ?? 0, 2) }}</div>
                        <div class="stat-label">Net Pay This Week</div>
                    </div>
                </div>
            </div>

            <style>
                .dbp-date { white-space: nowrap; }

                /* Phones only: each assigned project becomes a small card — name on top, phase +
                   status, Start / End Date tiles, a full-width progress bar, then a full-width View button. */
                .dbp-name-split { display: none; }

                @media (max-width: 640px) {
                    /* room under the last card so the floating chat button doesn't cover it */
                    .admin-content { padding-bottom: 92px !important; }

                    .dbp-card .card-header { gap: 10px; padding: 14px 16px; }
                    .dbp-card .card-title { font-size: 14px; white-space: nowrap; }
                    .dbp-card .card-header .btn { white-space: nowrap; flex-shrink: 0; height: 30px; padding: 0 10px; font-size: 11.5px; }
                    .dbp-card .card-header .btn svg { width: 13px; height: 13px; }
                    .dbp-card .table-wrap { max-height: 520px !important; overflow-x: hidden; }

                    #dbProjectsTable, #dbProjectsTable tbody { display: block; width: 100%; min-width: 0; }
                    #dbProjectsTable thead { display: none; }
                    #dbProjectsTable tr.dbp-row {
                        display: grid;
                        grid-template-columns: repeat(2, minmax(0, 1fr));
                        gap: 8px;
                        padding: 12px 16px 14px;
                        border-bottom: 1px solid var(--border);
                    }
                    #dbProjectsTable tr.dbp-row td { display: block; padding: 0 !important; border: none !important; min-width: 0; }

                    /* name: small "FABRICATION OF" label + tank name on one line */
                    #dbProjectsTable tr.dbp-row td.dbp-name { grid-column: 1 / -1; grid-row: 1; }
                    #dbProjectsTable .dbp-name-full { display: none; }
                    #dbProjectsTable .dbp-name-split { display: flex; flex-direction: column; min-width: 0; }
                    #dbProjectsTable .dbp-prefix { font-size: 9px; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--muted); }
                    #dbProjectsTable .dbp-name-split strong { font-size: 13.5px; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

                    #dbProjectsTable tr.dbp-row td.dbp-phase { grid-column: 1; grid-row: 2; align-self: center; }
                    #dbProjectsTable tr.dbp-row td.dbp-status { grid-column: 2; grid-row: 2; align-self: center; justify-self: end; }
                    #dbProjectsTable tr.dbp-row td.dbp-phase .status-badge,
                    #dbProjectsTable tr.dbp-row td.dbp-status .status-badge { font-size: 9.5px; padding: 3px 8px; white-space: nowrap; }

                    /* start / end dates: soft tiles, no heavy borders */
                    #dbProjectsTable tr.dbp-row td.dbp-date {
                        grid-row: 3;
                        background: var(--cream-soft);
                        border-radius: 10px;
                        padding: 7px 8px !important;
                        font-size: 12px;
                        font-weight: 800;
                        color: var(--dark);
                    }
                    #dbProjectsTable tr.dbp-row td.dbp-start { grid-column: 1; }
                    #dbProjectsTable tr.dbp-row td.dbp-end { grid-column: 2; }
                    #dbProjectsTable tr.dbp-row td.dbp-date::before {
                        content: attr(data-label);
                        display: block;
                        font-size: 8.5px;
                        font-weight: 800;
                        letter-spacing: .07em;
                        text-transform: uppercase;
                        color: var(--muted);
                        margin-bottom: 2px;
                    }

                    #dbProjectsTable tr.dbp-row td.dbp-progress { grid-column: 1 / -1; grid-row: 4; margin-top: 2px; }
                    #dbProjectsTable tr.dbp-row td.dbp-progress .progress-bar { flex: 1; width: auto !important; height: 6px; }
                    #dbProjectsTable tr.dbp-row td.dbp-progress .font-12 { font-size: 11px; min-width: 30px; text-align: right; }

                    #dbProjectsTable tr.dbp-row td.dbp-action { grid-column: 1 / -1; grid-row: 5; }
                    #dbProjectsTable tr.dbp-row td.dbp-action .btn { width: 100%; height: 34px; justify-content: center; border-radius: 10px; font-size: 12px; }
                    #dbProjectsTable tr:not(.dbp-row), #dbProjectsTable tr:not(.dbp-row) td { display: block; }
                }
            </style>

            <div class="card dbp-card">
                <div class="card-header">
                    <span class="card-title">My Assigned Projects</span>
                    <a href="{{ route('employee.projects') }}" class="btn btn-outline btn-sm">
                        <i data-lucide="arrow-right"></i> View Projects
                    </a>
                </div>
                <div style="position:relative;">
                <div class="table-wrap" style="max-height:340px;overflow-y:auto;">
                    <table class="data-table" id="dbProjectsTable">
                        <thead style="position:sticky;top:0;z-index:2;">
                            <tr>
                                <th>Project</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Current Phase</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projects as $project)
                            @php
                                $phase = strtolower($project->current_phase ?? 'planning');
                                $phaseColors = [
                                    'planning'    => ['bg'=>'#FEF3C7','color'=>'#92400E'],
                                    'procurement' => ['bg'=>'#EDE9FE','color'=>'#5B21B6'],
                                    'matl_prep'   => ['bg'=>'#CFFAFE','color'=>'#0E7490'],
                                    'fabrication' => ['bg'=>'#2563EB','color'=>'#fff'],
                                    'inspection'  => ['bg'=>'#EC4899','color'=>'#fff'],
                                    'painting'    => ['bg'=>'#14B8A6','color'=>'#fff'],
                                    'completion'  => ['bg'=>'#10B981','color'=>'#fff'],
                                    'delivery'    => ['bg'=>'#059669','color'=>'#fff'],
                                    'delayed'     => ['bg'=>'#EF4444','color'=>'#fff'],
                                ];
                                $pc = $phaseColors[$phase] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280'];

                                // phones show "Fabrication of" as a small label above the tank name
                                $dbpPrefix = '';
                                $dbpName   = $project->name;
                                if (preg_match('/^(Fabrication of)\s+(.+)$/i', (string) $project->name, $dbpM)) {
                                    [$dbpPrefix, $dbpName] = [$dbpM[1], $dbpM[2]];
                                }
                            @endphp
                            <tr class="dbp-row">
                                <td class="dbp-name">
                                    <strong class="dbp-name-full">{{ $project->name }}</strong>
                                    <span class="dbp-name-split">
                                        @if($dbpPrefix)<span class="dbp-prefix">{{ $dbpPrefix }}</span>@endif
                                        <strong title="{{ $project->name }}">{{ $dbpName }}</strong>
                                    </span>
                                </td>
                                <td class="dbp-date dbp-start" data-label="Start Date">{{ $project->start_date ? $project->start_date->format('M d, Y') : '—' }}</td>
                                <td class="dbp-date dbp-end" data-label="End Date">{{ $project->end_date ? $project->end_date->format('M d, Y') : '—' }}</td>
                                <td class="dbp-phase">
                                    <span class="status-badge" style="background:{{ $pc['bg'] }};color:{{ $pc['color'] }};">
                                        {{ ucfirst(str_replace('_', ' ', $phase)) }}
                                    </span>
                                </td>
                                <td class="dbp-progress">
                                    <div class="flex-center gap-8">
                                        <div class="progress-bar width-100px">
                                            <div class="progress-fill"
                                                 style="width: {{ $project->progress }}%;
                                                 background: {{ $project->status === 'completed' ? 'var(--success)' : ($project->status === 'pending' ? 'var(--warning)' : '') }}">
                                            </div>
                                        </div>
                                        <span class="font-12 color-muted font-w700">{{ $project->progress }}%</span>
                                    </div>
                                </td>
                                <td class="dbp-status">
                                    @if($project->status === 'ongoing')
                                        <span class="status-badge ongoing">In Progress</span>
                                    @elseif($project->status === 'completed')
                                        <span class="status-badge completed">Completed</span>
                                    @else
                                        <span class="status-badge pending">Pending</span>
                                    @endif
                                </td>
                                <td class="dbp-action">
                                    <a href="{{ route('employee.project_view', $project->id) }}" class="btn btn-outline btn-sm">
                                        <i data-lucide="external-link" style="width:13px;height:13px;"></i> View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" style="text-align:center;color:var(--muted);padding:32px 0;">
                                    No projects assigned yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div style="position:absolute;bottom:0;left:0;right:0;height:28px;background:linear-gradient(180deg, rgba(255,255,255,0) 0%, rgba(255,255,255,.9) 100%);pointer-events:none;border-radius:0 0 22px 22px;"></div>
                </div>
            </div>

            <div class="card dbp-card dbu-card" style="margin-top:24px;">
                <div class="card-header">
                    <span class="card-title">Recent Material Usage</span>
                    <a href="{{ route('employee.project_materials') }}" class="btn btn-outline btn-sm">
                        <i data-lucide="arrow-right"></i> View All
                    </a>
                </div>
                <div class="card-body">
                    @forelse($recentUsage as $entry)
                        <div class="activity-row dbu-row">
                            <div class="dbu-info">
                                <div class="dbu-name" style="font-weight:700;color:var(--dark);">{{ $entry->material_name }}</div>
                                <div class="dbu-meta" style="font-size:12px;color:var(--muted);">
                                    {{ $entry->project->name ?? '—' }} &nbsp;·&nbsp; {{ $entry->used_date->format('M d, Y') }}
                                </div>
                            </div>
                            <div class="dbu-qty" style="font-weight:800;color:var(--dark);">
                                {{ number_format($entry->quantity_used, 0) }} {{ $entry->unit }}
                            </div>
                        </div>
                    @empty
                        <div class="dbu-empty">
                            <span class="dbu-empty-icon"><i data-lucide="package-open"></i></span>
                            <div class="dbu-empty-title">No material usage logged yet</div>
                            <div class="dbu-empty-sub">Usage you log on a project will show up here.</div>
                        </div>
                    @endforelse
                </div>
            </div>

            <style>
                .dbu-empty { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px; padding: 32px 16px; color: var(--muted); }
                .dbu-empty-icon { width: 44px; height: 44px; border-radius: 14px; background: var(--cream-soft); border: 1px solid var(--border);
                                  display: flex; align-items: center; justify-content: center; color: var(--muted); margin-bottom: 4px; }
                .dbu-empty-icon svg { width: 20px; height: 20px; }
                .dbu-empty-title { font-size: 14px; font-weight: 800; color: var(--dark); }
                .dbu-empty-sub { font-size: 12.5px; }

                /* Phones: compact rows — name + project/date on the left, quantity pill on the right */
                @media (max-width: 640px) {
                    .dbu-card .card-body { padding: 4px 16px 8px; }
                    .dbu-row { gap: 12px; padding: 11px 0; align-items: center; }
                    .dbu-info { min-width: 0; flex: 1; }
                    .dbu-name { font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                    .dbu-meta { font-size: 11px !important; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                    .dbu-qty { flex-shrink: 0; font-size: 12px; background: var(--cream-soft); border: 1px solid var(--border); border-radius: 999px; padding: 4px 10px; white-space: nowrap; }
                    .dbu-empty { padding: 26px 12px; }
                    .dbu-empty-icon { width: 40px; height: 40px; border-radius: 12px; }
                    .dbu-empty-title { font-size: 13px; }
                    .dbu-empty-sub { font-size: 11.5px; }
                }
            </style>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
