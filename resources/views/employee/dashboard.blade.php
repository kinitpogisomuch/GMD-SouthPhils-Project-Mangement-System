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

    <div class="admin-layout">
        @include('partials.employee.sidebar')

        <main class="admin-content">

            <div class="pv-page-header">
                <div>
                    <h1>Welcome back, {{ $employee->first_name }}</h1>
                    <p>{{ $employee->role }} &nbsp;·&nbsp; Here's what's happening with your work today.</p>
                </div>
            </div>

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

            <div class="card">
                <div class="card-header">
                    <span class="card-title">My Assigned Projects</span>
                    <a href="{{ route('employee.projects') }}" class="view-all-link">View Projects <i data-lucide="arrow-right"></i></a>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
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
                            <tr>
                                <td><strong>{{ $project->name }}</strong></td>
                                <td>{{ $employee->role }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $project->current_phase ?? 'Planning')) }}</td>
                                <td>
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
                                <td>
                                    @if($project->status === 'ongoing')
                                        <span class="status-badge ongoing">In Progress</span>
                                    @elseif($project->status === 'completed')
                                        <span class="status-badge completed">Completed</span>
                                    @else
                                        <span class="status-badge pending">Pending</span>
                                    @endif
                                </td>
                                <td>
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
            </div>

            <div class="card" style="margin-top:24px;">
                <div class="card-header">
                    <span class="card-title">Recent Material Usage</span>
                    <a href="{{ route('employee.project_materials') }}" class="view-all-link">View All <i data-lucide="arrow-right"></i></a>
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
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        function toggleSidebar() {
            document.querySelector('.employee-sidebar').classList.toggle('open');
        }
    </script>
</body>
</html>
