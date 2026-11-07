<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
    <style>
        /* ── Hero greeting ── */
        .db-hero { display: flex; align-items: center; justify-content: space-between; padding: 4px 4px 24px; margin-bottom: 20px; border-bottom: 1px solid var(--border); gap: 16px; }
        .db-hero-left { display: flex; align-items: center; gap: 16px; }
        /* same label as the admin and client dashboards: a short yellow line, then the name */
        .db-eyebrow { display: inline-flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); }
        .db-eyebrow::before { content: ''; width: 22px; height: 3px; border-radius: 999px; background: #F5B400; }
        /* same sizes as the admin dashboard header (ad-title / ad-sub) */
        .db-greeting { margin: 6px 0 4px; font-size: 28px; font-weight: 900; color: var(--dark); letter-spacing: -.5px; }
        .db-subgreeting { font-size: 13.5px; color: var(--muted); }
        .db-hero-date { display: flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; color: var(--muted); background: var(--white); border: 1px solid var(--border); border-radius: 999px; padding: 7px 14px; white-space: nowrap; box-shadow: 0 4px 12px rgba(0,0,0,.05); }
        .db-hero-date i { width: 14px; height: 14px; }

        /* ── Summary cards: same look as My Salary (emp-kpi in employee.css); each opens the page behind its number ── */
        @media (min-width: 1101px) { .db-kpis4 { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        @media (max-width: 1100px) and (min-width: 641px) { .db-kpis4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        a.emp-kpi { position: relative; color: inherit; text-decoration: none; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
        a.emp-kpi:hover { border-color: var(--dark); box-shadow: 0 8px 20px rgba(0,0,0,.08); transform: translateY(-2px); }
        a.emp-kpi:focus-visible { outline: 2px solid var(--dark); outline-offset: 2px; }
        a.emp-kpi > div:nth-child(2) { min-width: 0; padding-right: 14px; }   /* room for the arrow */
        .emp-kpi-go { position: absolute; top: 14px; right: 14px; width: 16px; height: 16px; color: var(--muted-light, #b5b5b5); transition: color .15s ease, transform .15s ease; }
        a.emp-kpi:hover .emp-kpi-go { color: var(--dark); transform: translateX(2px); }
        .emp-kpi-sub.is-wait { color: #8A6100; }
        .emp-kpi-sub.is-ok { color: #207A3A; }

        /* ── Needs your attention: progress updates the admin asked for ── */
        .db-attn { display: flex; flex-direction: column; gap: 10px; margin: 0 0 20px; padding: 16px 18px; border-radius: 18px; background: #FFFBEB; border: 1px solid #FDE68A; }
        .db-attn-head { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 900; color: #8A6100; }
        .db-attn-head svg { width: 18px; height: 18px; }
        .db-attn-count { min-width: 22px; padding: 1px 8px; border-radius: 999px; background: #F5B400; color: #1a1a1a; font-size: 11.5px; text-align: center; }
        .db-req { display: flex; align-items: center; gap: 14px; padding: 12px 14px; border-radius: 14px; background: var(--white); border: 1px solid #FCE7B2; }
        .db-req-info { flex: 1; min-width: 0; }
        .db-req-title { font-size: 13.5px; font-weight: 800; color: var(--dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .db-req-meta { margin-top: 2px; font-size: 12px; color: var(--muted); }
        .db-req-meta .is-revision { color: #B42318; font-weight: 800; }
        .db-req .btn { flex-shrink: 0; white-space: nowrap; }

        /* ── Two columns: work on the left, pay and updates on the right ── */
        .db-grid { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr); gap: 20px; align-items: start; margin-top: 4px; }
        .db-col { display: grid; gap: 20px; min-width: 0; }
        .db-card { background: var(--white); border: 1px solid var(--border); border-radius: 20px; box-shadow: 0 10px 24px rgba(0,0,0,.05); overflow: hidden; min-width: 0; }
        .db-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--border); }
        .db-card-title { display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 900; color: var(--dark); }
        .db-card-title svg { width: 17px; height: 17px; color: var(--muted); }
        .db-link { display: inline-flex; align-items: center; gap: 4px; font-size: 12.5px; font-weight: 800; color: var(--dark); text-decoration: none; white-space: nowrap; }
        .db-link svg { width: 14px; height: 14px; transition: transform .15s ease; }
        .db-link:hover svg { transform: translateX(2px); }
        .db-scroll { max-height: 360px; overflow-y: auto; }
        .db-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 6px; padding: 30px 16px; color: var(--muted);
                    width: 100%; box-sizing: border-box; white-space: normal; }
        .db-empty-sub { max-width: 300px; overflow-wrap: anywhere; }
        /* an empty feed: the message sits in the middle of the card, up and down too */
        .db-feed:has(> .db-empty) { display: flex; align-items: center; justify-content: center; }
        .db-empty-icon { width: 44px; height: 44px; border-radius: 14px; background: var(--cream-soft); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; margin-bottom: 4px; }
        .db-empty-icon svg { width: 20px; height: 20px; }
        .db-empty-title { font-size: 14px; font-weight: 800; color: var(--dark); }
        .db-empty-sub { font-size: 12.5px; }

        /* active projects: the admin's Recent Projects layout — header row, colored strip, aligned columns */
        .db-count { font-size: 11.5px; font-weight: 700; color: var(--muted-light, #9ca3af); background: var(--cream-soft); border: 1px solid var(--border); padding: 3px 10px; border-radius: 999px; white-space: nowrap; }
        .db-ptable-head, .db-prow { display: grid; grid-template-columns: 3px minmax(0, 1.8fr) 116px 104px 150px; column-gap: 16px; align-items: center; }
        .db-ptable-head { padding: 9px 20px 9px 0; background: var(--cream-soft); border-bottom: 1px solid var(--border); font-size: 11px; font-weight: 800; color: var(--muted-light, #9ca3af); text-transform: uppercase; letter-spacing: .5px; }
        .db-ptable-head span:nth-child(2) { padding-left: 14px; }
        .db-prow { padding: 14px 20px 14px 0; border-bottom: 1px solid var(--border); color: inherit; text-decoration: none; transition: background .15s ease; }
        .db-prow:last-child { border-bottom: none; }
        .db-prow:hover { background: var(--cream-soft); }
        .db-prow-accent { align-self: stretch; opacity: .75; }
        .db-prow-info { min-width: 0; padding-left: 14px; }
        .db-prow-name { display: block; font-size: 13px; font-weight: 700; color: var(--dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .db-prow-flags { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 3px; font-size: 11px; font-weight: 800; }
        .db-prow-flags span { display: inline-flex; align-items: center; gap: 4px; }
        .db-prow-flags svg { width: 12px; height: 12px; }
        .db-prow-flags .is-req { color: #8A6100; }
        .db-prow-flags .is-late { color: #B42318; }
        .db-prow-phase .status-badge { font-size: 10.5px; padding: 3px 9px; white-space: nowrap; }
        .db-prow-date { font-size: 12.5px; color: var(--muted); white-space: nowrap; }
        .db-prow-date.is-late { color: #B42318; font-weight: 700; }
        .db-prow-progress { display: flex; align-items: center; gap: 8px; }
        .db-prow-bar { flex: 1; height: 6px; border-radius: 999px; background: var(--cream-deep, #ebebeb); overflow: hidden; }
        .db-prow-bar span { display: block; height: 100%; border-radius: inherit; }
        .db-prow-pct { width: 34px; text-align: right; font-size: 12px; font-weight: 700; color: var(--muted); }
        .db-card-foot { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 13px 20px; font-size: 13px; font-weight: 700; color: var(--muted); text-decoration: none; border-top: 1px solid var(--border); background: var(--cream-soft); transition: background .15s, color .15s; }
        .db-card-foot:hover { background: var(--cream-deep, #ebebeb); color: var(--dark); }
        .db-card-foot svg { width: 14px; height: 14px; }

        /* material usage */
        .db-use { display: flex; align-items: center; gap: 14px; padding: 12px 20px; border-bottom: 1px solid var(--border); }
        .db-use:last-child { border-bottom: none; }
        .db-use-icon { width: 34px; height: 34px; border-radius: 10px; background: #EAF0FF; color: #2A4EAA; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .db-use-icon svg { width: 16px; height: 16px; }
        .db-use-info { flex: 1; min-width: 0; }
        .db-use-name { font-size: 13.5px; font-weight: 800; color: var(--dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .db-use-meta { font-size: 12px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .db-use-qty { flex-shrink: 0; font-size: 12.5px; font-weight: 800; color: var(--dark); background: var(--cream-soft); border: 1px solid var(--border); border-radius: 999px; padding: 4px 11px; white-space: nowrap; }

        /* recent activity */
        .db-feed-card { display: flex; flex-direction: column; }
        .db-feed { max-height: 420px; overflow-y: auto; }
        .db-act { display: flex; align-items: flex-start; gap: 12px; padding: 12px 20px; border-bottom: 1px solid var(--border); color: inherit; text-decoration: none; transition: background .15s ease; }
        .db-act:last-child { border-bottom: none; }
        .db-act:hover { background: var(--cream-soft); }
        .db-act-icon { width: 32px; height: 32px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #F1F5F9; color: #334155; }
        .db-act-icon svg { width: 15px; height: 15px; }
        .db-act-icon.is-approved { background: #E7F6EC; color: #207A3A; }
        .db-act-icon.is-pending  { background: #FFF3D6; color: #8A6100; }
        .db-act-icon.is-revision { background: #FEE4E2; color: #B42318; }
        .db-act-icon.is-usage    { background: #EAF0FF; color: #2A4EAA; }
        .db-act-info { flex: 1; min-width: 0; }
        .db-act-title { font-size: 13px; font-weight: 800; color: var(--dark); }
        .db-act-project { margin-top: 1px; font-size: 12px; font-weight: 600; color: var(--dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; opacity: .8; }
        .db-act-meta { margin-top: 1px; font-size: 11.5px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .db-act-time { flex-shrink: 0; font-size: 11px; font-weight: 700; color: var(--muted-light, #9ca3af); white-space: nowrap; margin-top: 2px; }

        /* desktop: the activity card is exactly as tall as the left column and scrolls inside */
        @media (min-width: 1101px) {
            .db-grid { align-items: stretch; }
            .db-col-feed { position: relative; min-height: 320px; }
            .db-col-feed > .db-feed-card { position: absolute; inset: 0; }
            .db-col-feed .db-feed { flex: 1; min-height: 0; max-height: none; }
        }
        @media (max-width: 1100px) {
            .db-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .db-hero { flex-direction: column; align-items: flex-start; padding: 4px 4px 20px; gap: 12px; }
        }
        @media (max-width: 640px) {
            /* room under the last card so the floating chat button doesn't cover it */
            .admin-content { padding-bottom: 92px !important; }
            /* greeting, subtitle and date pill centered */
            .db-hero { align-items: center; text-align: center; }
            .db-hero-left { justify-content: center; }
            .db-greeting { font-size: 22px; }
            .db-attn { padding: 14px; }
            .db-req { flex-wrap: wrap; }
            .db-req .btn { width: 100%; justify-content: center; }
            .db-grid, .db-col { gap: 16px; }
            .db-card-head { padding: 14px 16px; }
            /* project rows: name on top, phase + target date, then a full-width progress bar */
            .db-ptable-head { display: none; }
            .db-prow { grid-template-columns: 3px minmax(0, 1fr) auto; column-gap: 0; row-gap: 8px; padding: 12px 16px 12px 0; }
            .db-prow-accent { grid-column: 1; grid-row: 1 / 4; }
            .db-prow-info { grid-column: 2 / -1; grid-row: 1; }
            .db-prow-phase { grid-column: 2; grid-row: 2; padding-left: 14px; }
            .db-prow-date { grid-column: 3; grid-row: 2; text-align: right; font-size: 12px; }
            .db-prow-date::before { content: attr(data-label) " "; font-size: 9.5px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
            .db-prow-progress { grid-column: 2 / -1; grid-row: 3; padding-left: 14px; }
            .db-use, .db-act { padding-left: 16px; padding-right: 16px; }
        }
    </style>
</head>
<body class="page-enter">

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
