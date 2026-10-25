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
        .cd-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
        .cd-greeting { font-size: 26px; font-weight: 900; color: var(--dark); letter-spacing: -.4px; margin: 0; }
        .cd-sub { font-size: 13.5px; color: var(--muted); margin-top: 4px; }
        .cd-head-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .cd-date { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; font-weight: 700; color: var(--muted); background: var(--white);
                   border: 1px solid var(--border); border-radius: 999px; padding: 8px 14px; }
        .cd-date svg, .cd-date i { width: 14px; height: 14px; }
        .cd-cta { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 12px; background: var(--dark-deep); color: #fff;
                  font-size: 13px; font-weight: 800; text-decoration: none; box-shadow: 0 6px 16px rgba(0,0,0,.14); }
        .cd-cta:hover { background: var(--dark); }
        .cd-cta svg, .cd-cta i { width: 15px; height: 15px; color: #F5B400; }

        /* action needed */
        .cd-actions { display: grid; gap: 10px; margin-bottom: 20px; }
        .cd-action { display: flex; align-items: center; gap: 14px; padding: 14px 18px; border-radius: 16px; background: #FFFBEB; border: 1.5px solid #FCD34D; }
        .cd-action-icon { width: 40px; height: 40px; border-radius: 12px; background: #F5B400; color: #1a1a1a; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .cd-action-icon svg, .cd-action-icon i { width: 19px; height: 19px; }
        .cd-action-text { flex: 1; min-width: 0; }
        .cd-action-text strong { display: block; font-size: 14px; font-weight: 800; color: var(--dark); }
        .cd-action-text span { font-size: 12.5px; color: #92400E; }
        .cd-action-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 10px; background: var(--dark-deep); color: #fff;
                         font-size: 12.5px; font-weight: 800; text-decoration: none; white-space: nowrap; }
        .cd-action-btn:hover { background: var(--dark); }
        .cd-action-btn svg, .cd-action-btn i { width: 14px; height: 14px; }

        /* key figures */
        .cd-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 20px; }
        .cd-kpi { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 18px 20px; box-shadow: 0 1px 2px rgba(0,0,0,.03); min-width: 0; }
        .cd-kpi-label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: var(--muted); }
        .cd-kpi-value { font-size: 28px; font-weight: 900; color: var(--dark); letter-spacing: -.5px; margin-top: 6px; line-height: 1.1; overflow-wrap: anywhere; }
        .cd-kpi-sub { font-size: 12px; color: var(--muted); margin-top: 6px; }
        .cd-kpi-sub strong { color: var(--dark); }
        .cd-kpi.is-dark { background: linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%); border-color: transparent; box-shadow: 0 8px 22px rgba(0,0,0,.16); }
        .cd-kpi.is-dark .cd-kpi-label, .cd-kpi.is-dark .cd-kpi-sub { color: rgba(255,255,255,.55); }
        .cd-kpi.is-dark .cd-kpi-value { color: #4ade80; }
        .cd-kpi.is-dark .cd-kpi-sub strong { color: #fff; }
        .cd-bar { height: 6px; border-radius: 999px; background: var(--cream-deep); overflow: hidden; margin-top: 10px; }
        .cd-bar > span { display: block; height: 100%; border-radius: inherit; background: var(--dark); }
        .cd-kpi.is-dark .cd-bar { background: rgba(255,255,255,.12); }
        .cd-kpi.is-dark .cd-bar > span { background: #4ade80; }

        /* main grid */
        .cd-grid { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr); gap: 18px; align-items: start; }
        .cd-col { display: grid; gap: 18px; min-width: 0; }
        .cd-card { background: var(--white); border: 1px solid var(--border); border-radius: 20px; box-shadow: 0 1px 2px rgba(0,0,0,.03); overflow: hidden; min-width: 0; }
        .cd-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--border); }
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

        /* payments */
        .cd-paysum { padding: 16px 20px; border-bottom: 1px solid var(--border); }
        .cd-paysum-row { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; font-size: 12.5px; color: var(--muted); }
        .cd-paysum-row strong { font-size: 16px; font-weight: 900; color: var(--dark); }
        .cd-paysum .cd-bar > span { background: #22c55e; }
        .cd-paysum-legend { display: flex; justify-content: space-between; margin-top: 7px; font-size: 11.5px; color: var(--muted); }
        .cd-tx { display: flex; align-items: center; gap: 12px; padding: 12px 20px; border-bottom: 1px solid var(--cream-deep); }
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

        @media (max-width: 1100px) {
            .cd-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .cd-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .cd-greeting { font-size: 22px; }
            .cd-head-right { width: 100%; flex-direction: column; align-items: stretch; }
            .cd-date { align-self: flex-start; }
            .cd-cta { justify-content: center; }
            .cd-proj-name { white-space: normal; }
            .cd-proj-top { align-items: flex-start; }
            .cd-kpi { padding: 14px 16px; }
            .cd-kpi-value { font-size: 22px; }
            .cd-action { flex-wrap: wrap; }
            .cd-action-btn { width: 100%; justify-content: center; }
            .cd-proj, .cd-tx, .cd-quote, .cd-card-head, .cd-paysum { padding-left: 16px; padding-right: 16px; }
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
            $phaseIcons = ['planning' => 'clipboard-list', 'procurement' => 'shopping-cart', 'fabrication' => 'hammer', 'inspection' => 'search-check',
                           'testing' => 'gauge', 'completion' => 'badge-check', 'delivery' => 'truck'];
            $quoteStatus = [
                'pending'        => ['Submitted', 'is-prep'],
                'in_preparation' => ['Being prepared', 'is-prep'],
                'quotation_sent' => ['Awaiting your approval', 'is-wait'],
                'approved'       => ['Approved', 'is-ok'],
            ];
        @endphp

        <div class="cd-head">
            <div>
                <h1 class="cd-greeting">{{ $greeting }}, {{ $clientFirstName }}</h1>
                <div class="cd-sub">Here's where your tanks and payments stand today.</div>
            </div>
            <div class="cd-head-right">
                <span class="cd-date"><i data-lucide="calendar-days"></i> {{ now()->timezone('Asia/Manila')->format('l, F j, Y') }}</span>
                <a href="{{ route('client.quotation.create') }}" class="cd-cta"><i data-lucide="plus"></i> Request a Quotation</a>
            </div>
        </div>

        {{-- ── Needs your attention ── --}}
        @if($quotesAwaiting->isNotEmpty() || $quotesNeedPo->isNotEmpty() || $paymentsDue->isNotEmpty())
        <div class="cd-actions">
            @foreach($quotesNeedPo as $q)
            <div class="cd-action">
                <span class="cd-action-icon"><i data-lucide="file-signature"></i></span>
                <div class="cd-action-text">
                    <strong>Send your Purchase Order</strong>
                    <span>{{ $q->quotationBatch?->reference_no ? $q->quotationBatch->reference_no . ' · ' : '' }}approved — your project and down payment start once GMD receives it</span>
                </div>
                <a href="{{ route('client.quotation.create', ['tab' => 'pending']) }}" class="cd-action-btn">Send PO <i data-lucide="arrow-right"></i></a>
            </div>
            @endforeach
            @foreach($quotesAwaiting as $q)
            <div class="cd-action">
                <span class="cd-action-icon"><i data-lucide="file-check-2"></i></span>
                <div class="cd-action-text">
                    <strong>Your quotation is ready to review</strong>
                    <span>{{ $q->quotationBatch?->reference_no ? $q->quotationBatch->reference_no . ' · ' : '' }}{{ $q->tank_type }}{{ $q->capacity ? ' — ' . $q->capacity : '' }}</span>
                </div>
                <a href="{{ route('client.quotation.create', ['tab' => 'pending']) }}" class="cd-action-btn">Review &amp; approve <i data-lucide="arrow-right"></i></a>
            </div>
            @endforeach
            @foreach($paymentsDue as $due)
            <div class="cd-action">
                <span class="cd-action-icon"><i data-lucide="wallet"></i></span>
                <div class="cd-action-text">
                    <strong>{{ $due['stage'] }} due — {{ $peso($due['amount']) }}</strong>
                    <span>{{ $due['payment']->project->name ?? 'Your project' }}</span>
                </div>
                <a href="{{ route('client.payments.show', $due['payment']->id) }}" class="cd-action-btn">Pay now <i data-lucide="arrow-right"></i></a>
            </div>
            @endforeach
        </div>
        @endif

        {{-- ── Key figures ── --}}
        <div class="cd-kpis">
            <div class="cd-kpi">
                <div class="cd-kpi-label">Active Projects</div>
                <div class="cd-kpi-value">{{ $activeProjects->count() }}</div>
                <div class="cd-kpi-sub"><strong>{{ $completedCount }}</strong> completed{{ $activeProjects->count() ? ' · ' . $avgProgress . '% average progress' : '' }}</div>
            </div>
            <div class="cd-kpi">
                <div class="cd-kpi-label">Total Contract Value</div>
                <div class="cd-kpi-value">{{ $peso($contractTotal) }}</div>
                <div class="cd-kpi-sub">Across {{ $payments->count() }} project{{ $payments->count() === 1 ? '' : 's' }}</div>
            </div>
            <div class="cd-kpi is-dark">
                <div class="cd-kpi-label">Amount Paid</div>
                <div class="cd-kpi-value">{{ $peso($paidTotal) }}</div>
                <div class="cd-bar"><span style="width:{{ $paidPct }}%;"></span></div>
                <div class="cd-kpi-sub"><strong>{{ $paidPct }}%</strong> of your total contract</div>
            </div>
            <div class="cd-kpi">
                <div class="cd-kpi-label">Remaining Balance</div>
                <div class="cd-kpi-value">{{ $peso($balanceTotal) }}</div>
                <div class="cd-kpi-sub">{{ $paymentsDue->isNotEmpty() ? $paymentsDue->count() . ' payment' . ($paymentsDue->count() === 1 ? '' : 's') . ' due now' : 'Nothing due right now' }}</div>
            </div>
        </div>

        <div class="cd-grid">
            {{-- ── Your projects ── --}}
            <div class="cd-card">
                <div class="cd-card-head">
                    <div>
                        <div class="cd-card-title">Your Projects</div>
                        <div class="cd-card-sub">Current phase and progress of each tank</div>
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
                    @endphp
                    <a href="{{ route('client.project_view', $project->id) }}" class="cd-proj">
                        <div class="cd-proj-main">
                            <div class="cd-proj-top">
                                <span class="cd-proj-name" title="{{ $project->name }}">{{ $project->name }}</span>
                                <span class="status-badge {{ $badge[0] }}">{{ $badge[1] }}</span>
                            </div>
                            <div class="cd-proj-meta">
                                <span class="cd-phase"><i data-lucide="{{ $phaseIcons[$phase] ?? 'circle-dot' }}"></i> {{ $done ? 'Completed' : ucfirst($phase) . ' phase' }}</span>
                                @if($project->end_date && !$done)<span>· Target {{ \Carbon\Carbon::parse($project->end_date)->format('M j, Y') }}</span>@endif
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

            <div class="cd-col">
                {{-- ── Payments ── --}}
                <div class="cd-card">
                    <div class="cd-card-head">
                        <div>
                            <div class="cd-card-title">Payments</div>
                            <div class="cd-card-sub">What you've paid so far</div>
                        </div>
                        <a href="{{ route('client.payments') }}" class="cd-link">View all <i data-lucide="arrow-right"></i></a>
                    </div>
                    <div class="cd-paysum">
                        <div class="cd-paysum-row"><span>Paid</span><strong>{{ $peso($paidTotal) }} <span style="font-size:12px;font-weight:600;color:var(--muted);">of {{ $peso($contractTotal) }}</span></strong></div>
                        <div class="cd-bar"><span style="width:{{ $paidPct }}%;"></span></div>
                        <div class="cd-paysum-legend"><span>{{ $paidPct }}% paid</span><span>{{ $peso($balanceTotal) }} remaining</span></div>
                    </div>
                    <div class="cd-scroll" style="max-height:250px;">
                        @forelse($recentTransactions as $tx)
                        <div class="cd-tx">
                            <span class="cd-tx-icon"><i data-lucide="check"></i></span>
                            <div class="cd-tx-main">
                                <strong title="{{ $tx['project'] }}">{{ $tx['project'] }}</strong>
                                <span>{{ $tx['stage'] }}{{ $tx['date'] ? ' · ' . \Carbon\Carbon::parse($tx['date'])->format('M j, Y') : '' }}</span>
                            </div>
                            <span class="cd-tx-amt">{{ $peso($tx['amount']) }}</span>
                        </div>
                        @empty
                        <div class="cd-empty">No payments recorded yet.</div>
                        @endforelse
                    </div>
                </div>

                {{-- ── Quotations ── --}}
                <div class="cd-card">
                    <div class="cd-card-head">
                        <div>
                            <div class="cd-card-title">Quotations</div>
                            <div class="cd-card-sub">Requests in progress</div>
                        </div>
                        <a href="{{ route('client.quotation.create', ['tab' => 'pending']) }}" class="cd-link">Open <i data-lucide="arrow-right"></i></a>
                    </div>
                    <div class="cd-scroll" style="max-height:230px;">
                        @forelse($openQuotes as $q)
                        @php [$qLabel, $qClass] = $quoteStatus[$q->status] ?? [ucwords(str_replace('_', ' ', $q->status)), '']; @endphp
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
