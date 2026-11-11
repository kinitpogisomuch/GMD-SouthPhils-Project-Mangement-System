<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}?v={{ @filemtime(public_path('css/client.css')) }}" rel="stylesheet">
</head>
<body class="page-enter pg-client-dashboard">

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
                    <div class="cd-empty">
                        <span class="cd-empty-icon"><i data-lucide="folder-open"></i></span>
                        <div class="cd-empty-title">No projects yet</div>
                        <div class="cd-empty-text">Your projects show up here once a quotation is approved and GMD starts the work.</div>
                        <a href="{{ route('client.quotation.create') }}" class="cd-empty-btn"><i data-lucide="plus"></i> Request a quotation</a>
                    </div>
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
                    <div class="cd-empty">
                        <span class="cd-empty-icon"><i data-lucide="activity"></i></span>
                        <div class="cd-empty-title">No updates yet</div>
                        <div class="cd-empty-text">They'll appear here as work on your tank moves forward.</div>
                    </div>
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
                        <div class="cd-empty">
                            <span class="cd-empty-icon"><i data-lucide="wallet"></i></span>
                            <div class="cd-empty-title">No payments yet</div>
                            <div class="cd-empty-text">Payments you make — online, by cash or cheque — will be listed here.</div>
                        </div>
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
                        <div class="cd-empty">
                            <span class="cd-empty-icon"><i data-lucide="clipboard-list"></i></span>
                            <div class="cd-empty-title">No open requests</div>
                            <div class="cd-empty-text">Need a tank? Send a request and GMD will prepare a quotation for you.</div>
                            <a href="{{ route('client.quotation.create') }}" class="cd-empty-btn"><i data-lucide="plus"></i> Request a quotation</a>
                        </div>
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
