<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
    <style>
        /* ── Client dashboard (cd-): what needs the client's attention first, then projects, payments, quotations ── */
        /* a thin line under the header, same as the admin and employee dashboards */
        .cd-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; padding: 4px 4px 24px; margin-bottom: 20px; border-bottom: 1px solid var(--border); }
        .cd-eyebrow { display: inline-flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); }
        .cd-eyebrow::before { content: ''; width: 22px; height: 3px; border-radius: 999px; background: #F5B400; }
        /* same sizes as the admin dashboard header (ad-title / ad-sub) */
        .cd-greeting { font-size: 28px; font-weight: 900; color: var(--dark); letter-spacing: -.5px; margin: 6px 0 4px; }
        .cd-sub { font-size: 13.5px; color: var(--muted); }
        .cd-head-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .cd-date { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; font-weight: 700; color: var(--muted); background: var(--white);
                   border: 1px solid var(--border); border-radius: 999px; padding: 8px 14px; }
        .cd-date svg, .cd-date i { width: 14px; height: 14px; }

        /* key figures */
        .cd-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 20px; }
        .cd-kpi { display: flex; flex-direction: column; background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 16px 18px; box-shadow: 0 1px 2px rgba(0,0,0,.03); min-width: 0;
                  color: inherit; text-decoration: none; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
        .cd-kpi:hover { border-color: var(--dark); box-shadow: 0 8px 20px rgba(0,0,0,.08); transform: translateY(-2px); }
        .cd-kpi:focus-visible { outline: 2px solid var(--dark); outline-offset: 2px; }
        .cd-kpi-head { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .cd-kpi-icon { width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--cream-deep); color: var(--dark); }
        .cd-kpi-icon svg { width: 17px; height: 17px; }
        .cd-kpi-icon.is-blue  { background: #EAF0FF; color: #2A4EAA; }
        .cd-kpi-icon.is-green { background: #E7F6EC; color: #207A3A; }
        .cd-kpi-icon.is-amber { background: #FFF3D6; color: #8A6100; }
        .cd-kpi-label { flex: 1; min-width: 0; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; line-height: 1.3; color: var(--muted); }
        .cd-kpi-go { width: 16px; height: 16px; flex-shrink: 0; color: var(--muted-light); transition: color .15s ease, transform .15s ease; }
        .cd-kpi:hover .cd-kpi-go { color: var(--dark); transform: translateX(2px); }
        .cd-kpi-value { font-size: 26px; font-weight: 900; color: var(--dark); letter-spacing: -.5px; margin-top: 12px; line-height: 1.1; overflow-wrap: anywhere; }
        .cd-kpi-value.is-paid { color: #207A3A; }
        .cd-kpi-sub { font-size: 12px; color: var(--muted); margin-top: auto; padding-top: 6px; }
        .cd-kpi-sub strong { color: var(--dark); }
        .cd-kpi-sub.is-due { color: #8A6100; font-weight: 700; }
        .cd-bar { height: 6px; border-radius: 999px; background: var(--cream-deep); overflow: hidden; margin-top: 10px; }
        .cd-bar > span { display: block; height: 100%; border-radius: inherit; background: #207A3A; }

        /* main grid */
        .cd-grid { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr); gap: 18px; align-items: start; }
        .cd-col { display: grid; gap: 18px; min-width: 0; }
        .cd-card { background: var(--white); border: 1px solid var(--border); border-radius: 20px; box-shadow: 0 1px 2px rgba(0,0,0,.03); overflow: hidden; min-width: 0; }
        .cd-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--border); }
        .cd-card-heading { display: flex; align-items: center; gap: 11px; min-width: 0; }
        .cd-card-icon { width: 36px; height: 36px; border-radius: 11px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--cream-deep); color: var(--dark); }
        .cd-card-icon svg { width: 18px; height: 18px; }
        .cd-card-title { font-size: 15px; font-weight: 900; color: var(--dark); }
        .cd-card-sub { font-size: 12px; color: var(--muted); margin-top: 2px; }
        .cd-link { display: inline-flex; align-items: center; gap: 5px; font-size: 12.5px; font-weight: 800; color: var(--dark); text-decoration: none; white-space: nowrap;
                   padding: 7px 12px; border-radius: 999px; border: 1px solid var(--border); }
        .cd-link:hover { border-color: var(--dark); }
        .cd-link svg, .cd-link i { width: 14px; height: 14px; }
        .cd-scroll { max-height: 420px; overflow-y: auto; scrollbar-width: thin; }
        .cd-empty { padding: 34px 20px; text-align: center; color: var(--muted); font-size: 13px; }
        .cd-empty a { color: var(--dark); font-weight: 800; }

        /* projects */
        .cd-proj { display: flex; align-items: center; gap: 14px; padding: 16px 20px; border-bottom: 1px solid var(--cream-deep); text-decoration: none; color: inherit; transition: background .15s; }
        .cd-proj:last-child { border-bottom: none; }
        .cd-proj:hover { background: var(--cream-soft); }
        .cd-proj-main { flex: 1; min-width: 0; }
        .cd-proj-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .cd-proj-name { font-size: 14px; font-weight: 800; color: var(--dark); line-height: 1.35; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .cd-proj-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 4px; font-size: 12px; color: var(--muted); }
        .cd-phase { display: inline-flex; align-items: center; gap: 5px; font-weight: 700; color: var(--dark); }
        .cd-phase svg, .cd-phase i { width: 13px; height: 13px; color: var(--muted); }
        .cd-proj-progress { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
        .cd-proj-progress .cd-bar { flex: 1; margin-top: 0; }
        .cd-proj-progress strong { font-size: 12.5px; font-weight: 900; color: var(--dark); min-width: 38px; text-align: right; }
        .cd-proj-arrow { color: var(--muted-light); flex-shrink: 0; }
        .cd-proj-arrow svg, .cd-proj-arrow i { width: 18px; height: 18px; }
        .cd-bar.is-done > span { background: #22c55e; }
        .cd-next { color: var(--muted); }

        /* recent updates */
        .cd-upd { display: flex; align-items: flex-start; gap: 12px; padding: 14px 20px; border-bottom: 1px solid var(--cream-deep); text-decoration: none; color: inherit; transition: background .15s; }
        .cd-upd:last-child { border-bottom: none; }
        .cd-upd:hover { background: var(--cream-soft); }
        .cd-upd-icon { width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #EAF0FF; color: #2A4EAA; }
        .cd-upd-icon svg { width: 16px; height: 16px; }
        .cd-upd-main { flex: 1; min-width: 0; }
        .cd-upd-title { font-size: 13px; font-weight: 800; color: var(--dark); }
        .cd-upd-text { margin-top: 2px; font-size: 12.5px; line-height: 1.45; color: var(--dark); overflow-wrap: anywhere; }
        .cd-upd-meta { margin-top: 3px; font-size: 11.5px; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .cd-upd-photos { display: flex; gap: 6px; flex-shrink: 0; }
        .cd-upd-photos img { width: 46px; height: 46px; object-fit: cover; border-radius: 9px; border: 1px solid var(--border); display: block; }

        /* payments */
        .cd-tx { display: flex; align-items: center; gap: 12px; padding: 12px 20px; border-bottom: 1px solid var(--cream-deep); text-decoration: none; color: inherit; transition: background .15s; }
        .cd-tx:hover { background: var(--cream-soft); }
        .cd-tx:last-child { border-bottom: none; }
        .cd-tx-icon { width: 34px; height: 34px; border-radius: 10px; background: #E7F6EC; color: #207A3A; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .cd-tx-icon svg, .cd-tx-icon i { width: 16px; height: 16px; }
        .cd-tx-main { flex: 1; min-width: 0; }
        .cd-tx-main strong { display: block; font-size: 13px; font-weight: 800; color: var(--dark); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .cd-tx-main span { font-size: 11.5px; color: var(--muted); }
        .cd-tx-amt { font-size: 13.5px; font-weight: 900; color: #207A3A; white-space: nowrap; }

        /* quotations */
        .cd-quote { display: flex; align-items: center; gap: 12px; padding: 12px 20px; border-bottom: 1px solid var(--cream-deep); text-decoration: none; color: inherit; }
        .cd-quote:last-child { border-bottom: none; }
        .cd-quote:hover { background: var(--cream-soft); }
        .cd-quote-main { flex: 1; min-width: 0; }
        .cd-quote-main strong { display: block; font-size: 13px; font-weight: 800; color: var(--dark); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .cd-quote-main span { font-size: 11.5px; color: var(--muted); }
        .cd-ref { font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 11px; font-weight: 700; }
        .cd-pill { font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 999px; white-space: nowrap; background: var(--cream-deep); color: var(--dark); }
        .cd-pill.is-wait { background: #FFF3D6; color: #8A6100; }
        .cd-pill.is-prep { background: #EAF0FF; color: #2A4EAA; }
        .cd-pill.is-ok   { background: #E7F6EC; color: #207A3A; }

        /* side by side, the two columns' cards line up at the same height; a longer list scrolls inside its card */
        @media (min-width: 1101px) {
            .cd-col { grid-template-rows: 380px 320px; }
            .cd-col > .cd-card { display: flex; flex-direction: column; min-height: 0; }
            .cd-col > .cd-card > .cd-card-head { flex-shrink: 0; }
            .cd-col > .cd-card > .cd-scroll { flex: 1; min-height: 0; max-height: none; }
        }
        @media (max-width: 1100px) {
            .cd-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .cd-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .cd-greeting { font-size: 22px; }
            .cd-head-right { width: 100%; flex-direction: column; align-items: stretch; }
            .cd-date { align-self: flex-start; }
            .cd-proj-name { white-space: normal; }
            .cd-proj-top { align-items: flex-start; }
            .cd-kpi { padding: 14px 16px; }
            .cd-kpi-value { font-size: 22px; }
            .cd-proj, .cd-tx, .cd-quote, .cd-card-head, .cd-upd { padding-left: 16px; padding-right: 16px; }
            .cd-upd-photos img:nth-child(n+2) { display: none; }
        }
    </style>
</head>
<body class="page-enter">

    @include('partials.client.header')

    <main class="admin-content">
        @php
            $clientFullName = session('full_name', 'Client');
            $clientFirstName = str_contains($clientFullName, ', ') ? trim(explode(', ', $clientFullName, 2)[1]) : trim($clientFullName);
            $clientFirstName = $clientFirstName ? explode(' ', $clientFirstName)[0] : 'Client';
            $hour = (int) now()->timezone('Asia/Manila')->format('G');
            $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

            $paidPct = $contractTotal > 0 ? min(100, round($paidTotal / $contractTotal * 100)) : 0;
            $avgProgress = $activeProjects->count() ? round($activeProjects->avg('progress')) : 0;
            $peso = fn ($n) => '₱' . number_format((float) $n, 0);

            // active projects first (newest first), then finished ones
            $projectList = $activeProjects->concat($projects->where('status', 'completed'))->take(12);
            $phaseIcons = ['planning' => 'clipboard-list', 'procurement' => 'shopping-cart', 'matl_prep' => 'layers', 'fabrication' => 'hammer', 'inspection' => 'search-check',
                           'painting' => 'paintbrush', 'testing' => 'gauge', 'completion' => 'badge-check', 'delivery' => 'package-check'];
            $phaseName  = fn ($phase) => ucwords(\App\Models\Project::phaseLabel($phase));
            $quoteStatus = [
                'pending'        => ['Submitted', 'is-prep'],
                'in_preparation' => ['Being prepared', 'is-prep'],
                'quotation_sent' => ['Awaiting your approval', 'is-wait'],
            ];
        @endphp

        <div class="cd-head">
            <div>
                <div class="cd-eyebrow">Client Dashboard</div>
                <h1 class="cd-greeting">{{ $greeting }}, {{ $clientFirstName }}</h1>
                <div class="cd-sub">Here's where your tanks and payments stand today.</div>
            </div>
            <div class="cd-head-right">
                <span class="cd-date"><i data-lucide="calendar-days"></i> {{ now()->timezone('Asia/Manila')->format('l, F j, Y') }}</span>
            </div>
        </div>

        {{-- ── Key figures ── --}}
        <div class="cd-kpis">
            <a href="{{ route('client.projects') }}" class="cd-kpi">
                <div class="cd-kpi-head">
                    <span class="cd-kpi-icon is-blue"><i data-lucide="folder-kanban"></i></span>
                    <span class="cd-kpi-label">Active Projects</span>
                    <i data-lucide="chevron-right" class="cd-kpi-go"></i>
                </div>
                <div class="cd-kpi-value">{{ $activeProjects->count() }}</div>
                <div class="cd-kpi-sub"><strong>{{ $completedCount }}</strong> completed{{ $activeProjects->count() ? ' · ' . $avgProgress . '% average progress' : '' }}</div>
            </a>
            <a href="{{ route('client.payments') }}" class="cd-kpi">
                <div class="cd-kpi-head">
                    <span class="cd-kpi-icon"><i data-lucide="file-text"></i></span>
                    <span class="cd-kpi-label">Total Contract Value</span>
                    <i data-lucide="chevron-right" class="cd-kpi-go"></i>
                </div>
                <div class="cd-kpi-value">{{ $peso($contractTotal) }}</div>
                <div class="cd-kpi-sub">Across {{ $payments->count() }} project{{ $payments->count() === 1 ? '' : 's' }}</div>
            </a>
            <a href="{{ route('client.payments') }}" class="cd-kpi">
                <div class="cd-kpi-head">
                    <span class="cd-kpi-icon is-green"><i data-lucide="circle-check"></i></span>
                    <span class="cd-kpi-label">Amount Paid</span>
                    <i data-lucide="chevron-right" class="cd-kpi-go"></i>
                </div>
                <div class="cd-kpi-value is-paid">{{ $peso($paidTotal) }}</div>
                <div class="cd-bar"><span style="width:{{ $paidPct }}%;"></span></div>
                <div class="cd-kpi-sub"><strong>{{ $paidPct }}%</strong> of your total contract</div>
            </a>
            {{-- one payment due: straight to it; several or none: the list --}}
            <a href="{{ $paymentsDue->count() === 1 ? route('client.payments.show', $paymentsDue->first()['payment']->id) : route('client.payments') }}" class="cd-kpi">
                <div class="cd-kpi-head">
                    <span class="cd-kpi-icon {{ $paymentsDue->isNotEmpty() ? 'is-amber' : '' }}"><i data-lucide="wallet"></i></span>
                    <span class="cd-kpi-label">Remaining Balance</span>
                    <i data-lucide="chevron-right" class="cd-kpi-go"></i>
                </div>
                <div class="cd-kpi-value">{{ $peso($balanceTotal) }}</div>
                <div class="cd-kpi-sub {{ $paymentsDue->isNotEmpty() ? 'is-due' : '' }}">{{ $paymentsDue->isNotEmpty() ? $paymentsDue->count() . ' payment' . ($paymentsDue->count() === 1 ? '' : 's') . ' due now' : 'Nothing due right now' }}</div>
            </a>
        </div>

        <div class="cd-grid">
            <div class="cd-col">
            {{-- ── Your projects ── --}}
            <div class="cd-card">
                <div class="cd-card-head">
                    <div class="cd-card-heading">
                        <span class="cd-card-icon"><i data-lucide="folder-kanban"></i></span>
                        <div>
                            <div class="cd-card-title">Your Projects</div>
                            <div class="cd-card-sub">Current phase and progress of each tank</div>
                        </div>
                    </div>
                    <a href="{{ route('client.projects') }}" class="cd-link">View all <i data-lucide="arrow-right"></i></a>
                </div>
                <div class="cd-scroll">
                    @forelse($projectList as $project)
                    @php
                        $done  = $project->status === 'completed';
                        $phase = $done ? 'completion' : ($project->current_phase ?: 'planning');
                        $badge = match ($project->status) {
                            'completed' => ['completed', 'Completed'],
                            'delayed'   => ['revision', 'Delayed'],
                            'ongoing'   => ['ongoing', 'In Progress'],
                            default     => ['pending', 'Pending'],
                        };
                        $pct = max(0, min(100, (int) $project->progress));
                        // the phase after this one, for projects still being built
                        $phaseAt = array_search($phase, \App\Models\Project::PHASES, true);
                        $next    = !$done && $phaseAt !== false ? (\App\Models\Project::PHASES[$phaseAt + 1] ?? null) : null;
                    @endphp

                    <a href="{{ route('client.project_view', $project->id) }}" class="cd-proj">
                        <div class="cd-proj-main">
                            <div class="cd-proj-top">
                                <span class="cd-proj-name" title="{{ $project->name }}">{{ $project->name }}</span>
                                <span class="status-badge {{ $badge[0] }}">{{ $badge[1] }}</span>
                            </div>
                            <div class="cd-proj-meta">
                                @if($done)
                                <span class="cd-phase"><i data-lucide="package-check"></i> {{ $project->deliveryStatusLabel() }}</span>
                                @else
                                <span class="cd-phase"><i data-lucide="{{ $phaseIcons[$phase] ?? 'circle-dot' }}"></i> {{ $phaseName($phase) }} phase</span>
                                @if($next)<span class="cd-next">· Next: {{ $phaseName($next) }}</span>@endif
                                @if($project->end_date)<span>· Target {{ \Carbon\Carbon::parse($project->end_date)->format('M j, Y') }}</span>@endif
                                @endif
                            </div>
                            <div class="cd-proj-progress">
                                <div class="cd-bar {{ $done ? 'is-done' : '' }}"><span style="width:{{ $pct }}%;"></span></div>
                                <strong>{{ $pct }}%</strong>
                            </div>
                        </div>
                        <span class="cd-proj-arrow"><i data-lucide="chevron-right"></i></span>
                    </a>
                    @empty
                    <div class="cd-empty">No projects yet. <a href="{{ route('client.quotation.create') }}">Request a quotation</a> to get started.</div>
                    @endforelse
                </div>
            </div>

            {{-- ── Recent updates ── --}}
            <div class="cd-card">
                <div class="cd-card-head">
                    <div class="cd-card-heading">
                        <span class="cd-card-icon"><i data-lucide="activity"></i></span>
                        <div>
                            <div class="cd-card-title">Recent Updates</div>
                            <div class="cd-card-sub">The latest work on your projects</div>
                        </div>
                    </div>
                </div>
                <div class="cd-scroll">
                    @forelse($recentUpdates as $update)
                    @php
                        $updatePhotos = array_slice(array_values(array_filter((array) $update->photos, fn ($p) => preg_match('/\.(jpe?g|png|gif|webp|bmp)(\?.*)?$/i', (string) $p))), 0, 2);
                    @endphp
                    <a href="{{ route('client.project_view', $update->project_id) }}" class="cd-upd">
                        <span class="cd-upd-icon"><i data-lucide="{{ $phaseIcons[$update->phase] ?? 'circle-dot' }}"></i></span>
                        <div class="cd-upd-main">
                            <div class="cd-upd-title">{{ $phaseName($update->phase) }} phase</div>
                            {{-- older entries were saved before tanks became pick-up only --}}
                            <div class="cd-upd-text">{{ Str::limit(str_replace('Project delivered to the client.', 'Project picked up by the client.', (string) $update->work_done), 110) }}</div>
                            <div class="cd-upd-meta">{{ $update->project->name ?? 'Your project' }} · {{ ($update->date_of_work ?? $update->created_at)->format('M j, Y') }}</div>
                        </div>
                        @if($updatePhotos)
                        <div class="cd-upd-photos">
                            @foreach($updatePhotos as $photo)
                            <img src="{{ $photo }}" alt="" loading="lazy">
                            @endforeach
                        </div>
                        @endif
                    </a>
                    @empty
                    <div class="cd-empty">No updates yet. They'll appear here as work on your tank moves forward.</div>
                    @endforelse
                </div>
            </div>
            </div>

            <div class="cd-col">
                {{-- ── Payments ── --}}
                <div class="cd-card">
                    <div class="cd-card-head">
                        <div class="cd-card-heading">
                            <span class="cd-card-icon"><i data-lucide="wallet"></i></span>
                            <div>
                                <div class="cd-card-title">Recent Payments</div>
                                <div class="cd-card-sub">Your latest recorded payments</div>
                            </div>
                        </div>
                        <a href="{{ route('client.payments') }}" class="cd-link">View all <i data-lucide="arrow-right"></i></a>
                    </div>
                    <div class="cd-scroll">
                        @forelse($recentTransactions as $tx)
                        <a href="{{ route('client.payments.show', $tx['payment']->id) }}" class="cd-tx">
                            <span class="cd-tx-icon"><i data-lucide="check"></i></span>
                            <div class="cd-tx-main">
                                <strong title="{{ $tx['project'] }}">{{ $tx['project'] }}</strong>
                                <span>{{ $tx['stage'] }}{{ $tx['date'] ? ' · ' . \Carbon\Carbon::parse($tx['date'])->format('M j, Y') : '' }}</span>
                            </div>
                            <span class="cd-tx-amt">{{ $peso($tx['amount']) }}</span>
                        </a>
                        @empty
                        <div class="cd-empty">No payments recorded yet.</div>
                        @endforelse
                    </div>
                </div>

                {{-- ── Quotations ── --}}
                <div class="cd-card">
                    <div class="cd-card-head">
                        <div class="cd-card-heading">
                            <span class="cd-card-icon"><i data-lucide="clipboard-list"></i></span>
                            <div>
                                <div class="cd-card-title">Quotations</div>
                                <div class="cd-card-sub">Requests in progress</div>
                            </div>
                        </div>
                        <a href="{{ route('client.quotation.create', ['tab' => 'pending']) }}" class="cd-link">Open <i data-lucide="arrow-right"></i></a>
                    </div>
                    <div class="cd-scroll">
                        @forelse($openQuotes as $q)
                        @php
                            [$qLabel, $qClass] = $quoteStatus[$q->status] ?? [ucwords(str_replace('_', ' ', $q->status)), ''];
                            // approved quotations wait for the client's Purchase Order before the project can start
                            if ($q->status === 'approved') {
                                [$qLabel, $qClass] = $q->hasPurchaseOrder() ? ['PO received', 'is-ok'] : ['Send your PO', 'is-wait'];
                            }
                        @endphp
                        <a href="{{ route('client.quotation.create', ['tab' => 'pending']) }}" class="cd-quote">
                            <div class="cd-quote-main">
                                <strong title="{{ $q->tank_type }}">{{ $q->tank_type ?: 'Tank' }}{{ $q->capacity ? ' — ' . $q->capacity : '' }}</strong>
                                <span>@if($q->quotationBatch?->reference_no)<span class="cd-ref">{{ $q->quotationBatch->reference_no }}</span> · @endif{{ $q->created_at->format('M j, Y') }}</span>
                            </div>
                            <span class="cd-pill {{ $qClass }}">{{ $qLabel }}</span>
                        </a>
                        @empty
                        <div class="cd-empty">No open requests. <a href="{{ route('client.quotation.create') }}">Request a quotation</a></div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/client.js') }}"></script>
</body>
</html>
