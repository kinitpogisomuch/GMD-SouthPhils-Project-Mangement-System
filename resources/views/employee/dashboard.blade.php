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
                        <div class="db-subgreeting">{{ $employee->role }} &nbsp;·&nbsp; Here's what's happening with your work today.</div>
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
            </style>

            <div class="stats-grid">
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
                .dbp-label { display: none; }

                /* Phones only: each assigned project becomes a small card — name + phase on top,
                   role + status, a full-width progress bar, then a full-width View button. */
                @media (max-width: 640px) {
                    .dbp-card .card-header { gap: 10px; }
                    .dbp-card .card-title { font-size: 15px; }
                    .dbp-card .card-header .btn { white-space: nowrap; flex-shrink: 0; }
                    .dbp-card .table-wrap { max-height: 520px !important; overflow-x: hidden; }

                    #dbProjectsTable, #dbProjectsTable tbody { display: block; width: 100%; min-width: 0; }
                    #dbProjectsTable thead { display: none; }
                    #dbProjectsTable tr.dbp-row {
                        display: grid;
                        grid-template-columns: minmax(0, 1fr) auto;
                        gap: 10px 12px;
                        padding: 14px 16px;
                        border-bottom: 1px solid var(--border);
                    }
                    #dbProjectsTable tr.dbp-row td { display: block; padding: 0 !important; border: none !important; min-width: 0; }
                    #dbProjectsTable tr.dbp-row td.dbp-name { grid-column: 1; grid-row: 1; font-size: 14px; line-height: 1.3; }
                    #dbProjectsTable tr.dbp-row td.dbp-phase { grid-column: 2; grid-row: 1; align-self: start; }
                    #dbProjectsTable tr.dbp-row td.dbp-phase .status-badge,
                    #dbProjectsTable tr.dbp-row td.dbp-status .status-badge { font-size: 10.5px; padding: 4px 9px; white-space: nowrap; }
                    #dbProjectsTable tr.dbp-row td.dbp-role { grid-column: 1; grid-row: 2; align-self: center; font-size: 13px; font-weight: 700; color: var(--dark); }
                    #dbProjectsTable .dbp-label { display: inline; font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); margin-right: 6px; }
                    #dbProjectsTable tr.dbp-row td.dbp-status { grid-column: 2; grid-row: 2; align-self: center; justify-self: end; }
                    #dbProjectsTable tr.dbp-row td.dbp-progress { grid-column: 1 / -1; grid-row: 3; }
                    #dbProjectsTable tr.dbp-row td.dbp-progress .progress-bar { flex: 1; width: auto !important; }
                    #dbProjectsTable tr.dbp-row td.dbp-action { grid-column: 1 / -1; grid-row: 4; }
                    #dbProjectsTable tr.dbp-row td.dbp-action .btn { width: 100%; height: 40px; justify-content: center; border-radius: 12px; font-size: 13px; }
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
                                <th>My Role</th>
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
                            @endphp
                            <tr class="dbp-row">
                                <td class="dbp-name"><strong>{{ $project->name }}</strong></td>
                                <td class="dbp-role"><span class="dbp-label">My Role</span>{{ $employee->role }}</td>
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
                                <td colspan="6" style="text-align:center;color:var(--muted);padding:32px 0;">
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

            <div class="card" style="margin-top:24px;">
                <div class="card-header">
                    <span class="card-title">Recent Material Usage</span>
                    <a href="{{ route('employee.project_materials') }}" class="btn btn-outline btn-sm">
                        <i data-lucide="arrow-right"></i> View All
                    </a>
                </div>
                <div class="card-body">
                    @forelse($recentUsage as $entry)
                        <div class="activity-row">
                            <div>
                                <div style="font-weight:700;color:var(--dark);">{{ $entry->material_name }}</div>
                                <div style="font-size:12px;color:var(--muted);">
                                    {{ $entry->project->name ?? '—' }} &nbsp;·&nbsp; {{ $entry->used_date->format('M d, Y') }}
                                </div>
                            </div>
                            <div style="font-weight:800;color:var(--dark);">
                                {{ number_format($entry->quantity_used, 0) }} {{ $entry->unit }}
                            </div>
                        </div>
                    @empty
                        <div style="text-align:center;color:var(--muted);padding:32px 0;">
                            No material usage logged yet.
                        </div>
                    @endforelse
                </div>
            </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
