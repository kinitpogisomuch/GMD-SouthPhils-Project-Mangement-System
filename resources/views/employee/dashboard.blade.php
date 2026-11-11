<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}?v={{ @filemtime(public_path('css/employee.css')) }}" rel="stylesheet">
</head>
<body class="page-enter pg-employee-dashboard">

    @include('partials.employee.header')

    <main class="admin-content">
        @php
            $hour = (int) now()->timezone('Asia/Manila')->format('G');
            $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
            $activeProjects = $projects->where('status', '!=', 'completed')->values();
            $requestProjectIds = $openRequests->pluck('project_id')->all();
            $phaseColors = [
                'planning'    => '#475569', 'procurement' => '#1E3A5F', 'matl_prep'  => '#7C5A10',
                'fabrication' => '#2A4EAA', 'inspection'  => '#9A3412', 'painting'   => '#4D5B2A',
                'completion'  => '#207A3A', 'delivery'    => '#14532D', 'delayed'    => '#B42318',
            ];
            $updateState = fn ($status) => match ($status) {
                'approved'                                    => ['is-approved', 'Approved'],
                'pending_review', 'pending'                   => ['is-pending', 'Waiting for review'],
                'revision_requested', 'revision_required'     => ['is-revision', 'Revision requested'],
                default                                       => ['', ucwords(str_replace('_', ' ', (string) $status))],
            };
            // "Material preparation" instead of the stored "matl_prep"
            $phaseLabelOf = fn ($p) => $p === 'matl_prep' ? 'Material preparation' : \App\Models\Project::phaseLabel($p);
        @endphp

        <!-- ── Hero greeting ── -->
        <div class="db-hero">
            <div class="db-hero-left">
                <div>
                    <div class="db-eyebrow">Employee Dashboard</div>
                    <div class="db-greeting">{{ $greeting }}, {{ $employee->first_name }}</div>
                    <div class="db-subgreeting">
                        @if($openRequests->count())
                            {{ $openRequests->count() }} progress {{ \Illuminate\Support\Str::plural('update', $openRequests->count()) }} {{ $openRequests->count() === 1 ? 'is' : 'are' }} waiting for you.
                        @else
                            You're all caught up. Here's what's happening with your work today.
                        @endif
                    </div>
                </div>
            </div>
            <div class="db-hero-meta">
                <div class="db-hero-date">
                    <i data-lucide="calendar-days"></i>
                    {{ now()->format('l, F j, Y') }}
                </div>
            </div>
        </div>

        @php
            $weekStart = now()->startOfWeek(\Carbon\Carbon::MONDAY);
            $weekText  = $weekStart->format("M j") . " – " . $weekStart->copy()->addDays(6)->format("M j");
        @endphp
        <div class="emp-kpis db-kpis4">
            <a href="{{ route('employee.projects') }}" class="emp-kpi">
                <div class="emp-kpi-icon blue"><i data-lucide="folder-open"></i></div>
                <div>
                    <div class="emp-kpi-label">Active Projects</div>
                    <div class="emp-kpi-value">{{ $activeProjectsCount }}</div>
                    <div class="emp-kpi-sub">Assigned to you</div>
                </div>
                <i data-lucide="chevron-right" class="emp-kpi-go"></i>
            </a>
            <a href="{{ route('employee.projects', ['filter' => 'completed']) }}" class="emp-kpi">
                <div class="emp-kpi-icon green"><i data-lucide="check-circle"></i></div>
                <div>
                    <div class="emp-kpi-label">Completed Projects</div>
                    <div class="emp-kpi-value">{{ $completedProjectsCount }}</div>
                    <div class="emp-kpi-sub">Finished so far</div>
                </div>
                <i data-lucide="chevron-right" class="emp-kpi-go"></i>
            </a>
            <a href="{{ route('employee.salary') }}" class="emp-kpi">
                <div class="emp-kpi-icon purple"><i data-lucide="calendar-check"></i></div>
                <div>
                    <div class="emp-kpi-label">Days Worked</div>
                    <div class="emp-kpi-value">{{ rtrim(rtrim(number_format($currentRecord->days_worked ?? 0, 1), '0'), '.') }} <span style="font-size:14px;color:var(--muted);font-weight:700;">days</span></div>
                    <div class="emp-kpi-sub">This week · {{ $weekText }}</div>
                </div>
                <i data-lucide="chevron-right" class="emp-kpi-go"></i>
            </a>
            <a href="{{ route('employee.salary') }}" class="emp-kpi">
                <div class="emp-kpi-icon" style="background:#CCFBF1;color:#0D9488;"><i data-lucide="wallet"></i></div>
                <div>
                    <div class="emp-kpi-label">Net Pay This Week</div>
                    <div class="emp-kpi-value">₱{{ number_format($currentRecord->net_pay ?? 0, 2) }}</div>
                    @if(!$currentRecord)
                        <div class="emp-kpi-sub is-wait">Not recorded yet</div>
                    @elseif($currentRecord->status === 'Paid')
                        <div class="emp-kpi-sub is-ok">Paid</div>
                    @else
                        <div class="emp-kpi-sub">Recorded · not paid yet</div>
                    @endif
                </div>
                <i data-lucide="chevron-right" class="emp-kpi-go"></i>
            </a>
        </div>

        {{-- ── Needs your attention: only when the admin is waiting on a progress update ── --}}
        @if($openRequests->isNotEmpty())
        <div class="db-attn">
            <div class="db-attn-head">
                <i data-lucide="bell-ring"></i> Needs your attention
                <span class="db-attn-count">{{ $openRequests->count() }}</span>
            </div>
            @foreach($openRequests as $req)
            <div class="db-req">
                <div class="db-req-info">
                    <div class="db-req-title">{{ $req->project->name ?? 'Project' }}</div>
                    <div class="db-req-meta">
                        @if($req->status === 'revision_requested')
                            <span class="is-revision">Revision requested</span>
                        @else
                            Progress update requested
                        @endif
                        @if($req->phase) · {{ \App\Models\Project::phaseLabel($req->phase) }} phase @endif
                        · {{ $req->created_at->diffForHumans() }}
                        @if($req->message) · “{{ \Illuminate\Support\Str::limit($req->message, 80) }}” @endif
                    </div>
                </div>
                <a href="{{ route('employee.project_view', $req->project_id) }}" class="btn btn-primary btn-sm">
                    <i data-lucide="send"></i> {{ $req->status === 'revision_requested' ? 'Revise Update' : 'Submit Update' }}
                </a>
            </div>
            @endforeach
        </div>
        @endif

        <div class="db-grid">
            {{-- ── Left: the work ── --}}
            <div class="db-col">
                {{-- same layout as the admin dashboard's Recent Projects --}}
                <div class="db-card">
                    <div class="db-card-head">
                        <span class="db-card-title"><i data-lucide="folder-kanban"></i> My Active Projects</span>
                        <span class="db-count">{{ $activeProjects->count() }} active</span>
                    </div>
                    @if($activeProjects->isNotEmpty())
                    <div class="db-ptable-head">
                        <span></span>
                        <span>Project</span>
                        <span>Phase</span>
                        <span>Target Date</span>
                        <span>Progress</span>
                    </div>
                    @endif
                    <div class="db-scroll">
                        @forelse($activeProjects as $project)
                        @php
                            $phase    = strtolower($project->current_phase ?? 'planning');
                            $progress = (int) $project->progress;
                            $late     = $project->end_date && $project->end_date->isPast();
                            $asked    = in_array($project->id, $requestProjectIds);
                            // strip: red when past its target date, amber when an update is requested, blue otherwise
                            $accent   = $late ? '#dc2626' : ($asked ? '#F5B400' : '#2A4EAA');
                            $barColor = $progress >= 100 ? '#16a34a' : ($progress >= 50 ? '#2A4EAA' : '#e8900a');
                        @endphp
                        <a href="{{ route('employee.project_view', $project->id) }}" class="db-prow">
                            <span class="db-prow-accent" style="background:{{ $accent }};"></span>
                            <div class="db-prow-info">
                                <span class="db-prow-name" title="{{ $project->name }}">{{ $project->name }}</span>
                                @if($asked || $late)
                                <span class="db-prow-flags">
                                    @if($asked)<span class="is-req"><i data-lucide="bell-ring"></i> Update requested</span>@endif
                                    @if($late)<span class="is-late"><i data-lucide="alarm-clock"></i> Past target date</span>@endif
                                </span>
                                @endif
                            </div>
                            <div class="db-prow-phase">
                                <span class="status-badge" style="background:{{ $phaseColors[$phase] ?? '#6B7280' }};color:#fff;">{{ \App\Models\Project::phaseLabel($phase) }}</span>
                            </div>
                            <div class="db-prow-date {{ $late ? 'is-late' : '' }}" data-label="Target">{{ $project->end_date ? $project->end_date->format('M j, Y') : '—' }}</div>
                            <div class="db-prow-progress">
                                <div class="db-prow-bar"><span style="width:{{ min(100, $progress) }}%;background:{{ $barColor }};"></span></div>
                                <span class="db-prow-pct">{{ $progress }}%</span>
                            </div>
                        </a>
                        @empty
                        <div class="db-empty">
                            <span class="db-empty-icon"><i data-lucide="folder-open"></i></span>
                            <div class="db-empty-title">No active projects</div>
                            <div class="db-empty-sub">Projects you're assigned to will show up here.</div>
                        </div>
                        @endforelse
                    </div>
                    <a href="{{ route('employee.projects') }}" class="db-card-foot">
                        <span>View all projects</span>
                        <i data-lucide="arrow-right"></i>
                    </a>
                </div>

                <div class="db-card">
                    <div class="db-card-head">
                        <span class="db-card-title"><i data-lucide="package"></i> Recent Material Usage</span>
                        <a href="{{ route('employee.project_materials') }}" class="db-link">Log usage <i data-lucide="arrow-right"></i></a>
                    </div>
                    @forelse($recentUsage as $entry)
                    <div class="db-use">
                        <span class="db-use-icon"><i data-lucide="package-minus"></i></span>
                        <div class="db-use-info">
                            <div class="db-use-name">{{ $entry->material_name }}</div>
                            <div class="db-use-meta">{{ $entry->project->name ?? '—' }} · {{ $entry->used_date->format('M d, Y') }}</div>
                        </div>
                        <span class="db-use-qty">{{ rtrim(rtrim(number_format($entry->quantity_used, 2), '0'), '.') }} {{ $entry->unit }}</span>
                    </div>
                    @empty
                    <div class="db-empty">
                        <span class="db-empty-icon"><i data-lucide="package-open"></i></span>
                        <div class="db-empty-title">No material usage logged yet</div>
                        <div class="db-empty-sub">Usage you log on a project will show up here.</div>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- ── Right: recent activity (as tall as the left column on desktop; scrolls inside) ── --}}
            <div class="db-col db-col-feed">
                <div class="db-card db-feed-card">
                    <div class="db-card-head">
                        <span class="db-card-title"><i data-lucide="activity"></i> Recent Activity</span>
                    </div>
                    <div class="db-feed">
                    @forelse($activity as $act)
                    @php
                        $phaseName = !empty($act['phase']) ? $phaseLabelOf($act['phase']) : null;   // material usage has no phase
                        if ($act['kind'] === 'update') {
                            [$tone, $state] = $updateState($act['status']);
                            [$icon, $title, $meta] = ['send', 'You sent a progress update', trim(($phaseName ? $phaseName . ' · ' : '') . $state)];
                        } elseif ($act['kind'] === 'request') {
                            $done = $act['status'] === 'completed';
                            $tone = $done ? 'is-approved' : ($act['status'] === 'revision_requested' ? 'is-revision' : 'is-pending');
                            [$icon, $title, $meta] = ['bell-ring', $act['status'] === 'revision_requested' ? 'Admin asked for a revision' : 'Admin requested a progress update',
                                                      trim(($phaseName ? $phaseName . ' · ' : '') . ($done ? 'Done' : 'Waiting for you'))];
                        } else {
                            $tone = 'is-usage';
                            $qty  = rtrim(rtrim(number_format($act['qty'], 2), '0'), '.');
                            [$icon, $title, $meta] = ['package-minus', 'You logged material usage', "{$act['material']} · {$qty} {$act['unit']}"];
                        }
                    @endphp
                    <a href="{{ route('employee.project_view', $act['project_id']) }}" class="db-act">
                        <span class="db-act-icon {{ $tone }}"><i data-lucide="{{ $icon }}"></i></span>
                        <div class="db-act-info">
                            <div class="db-act-title">{{ $title }}</div>
                            <div class="db-act-project">{{ $act['project']->name ?? 'Project' }}</div>
                            <div class="db-act-meta">{{ $meta }}</div>
                        </div>
                        <span class="db-act-time" title="{{ $act['at']->format('M d, Y g:i A') }}">{{ $act['at']->diffForHumans(null, true) }}</span>
                    </a>
                    @empty
                    <div class="db-empty">
                        <span class="db-empty-icon"><i data-lucide="activity"></i></span>
                        <div class="db-empty-title">No activity yet</div>
                        <div class="db-empty-sub">Updates you send, requests from the admin and materials you log will show up here.</div>
                    </div>
                    @endforelse
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
