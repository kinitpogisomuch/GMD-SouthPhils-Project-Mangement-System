<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project->name }} — Material Usage | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
    <style>
        /* ── Material Stock & Usage header: icon, title and a stock summary on the left; search + action on the right ── */
        .mvu-toolbar { padding-bottom: 16px !important; border-bottom: 1px solid var(--border); }
        .mvu-heading { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .mvu-heading-icon { width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #333; color: #fff; }
        .mvu-heading-icon svg { width: 19px; height: 19px; }
        .mvu-title { font-size: 15.5px; font-weight: 800; color: var(--dark); }
        .mvu-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 5px; }
        .mvu-chip { display: inline-flex; align-items: center; gap: 5px; padding: 2px 9px; border-radius: 999px; background: var(--cream-soft); border: 1px solid var(--border); font-size: 11px; font-weight: 700; color: var(--muted); white-space: nowrap; }
        .mvu-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .mvu-chip.is-ok   { background: #E7F6EC; border-color: #bbf7d0; color: #15803d; }
        .mvu-chip.is-out  { background: #FEE4E2; border-color: #fecaca; color: #b91c1c; }
        .mvu-chip.is-wait { background: #FFF8E1; border-color: #fde68a; color: #92400e; }
        .mvu-tools .search-box { border-radius: 999px; }
        .mvu-tools .save-btn { border-radius: 999px; height: 42px; display: inline-flex; align-items: center; gap: 7px; }

        /* Desktop: both tables run the full width of their card (edge to edge), like the other tables. The card's
           padding moves onto the header row; each table keeps its own scroll area and scrollbar. */
        @media (min-width: 641px) {
            .mu-flush { padding: 0 !important; overflow: hidden; }
            .mu-flush > .mvu-toolbar { padding: 20px 20px 16px !important; margin-bottom: 0; }
            .mu-flush .data-table th:first-child, .mu-flush .data-table td:first-child { padding-left: 24px; }
            .mu-flush .data-table th:last-child, .mu-flush .data-table td:last-child { padding-right: 24px; }
        }

        /* ── Material Stock & Usage: scroll inside the card, header stays visible ── */
        .mvu-scroll { max-height: 440px; overflow-y: auto; }
        .mvu-scroll thead th { position: sticky; top: 0; z-index: 1; background: var(--cream-soft); }
        /* material name on the left; Purchased / Used / Remaining / Status centered under their headers (desktop — phones use cards) */
        @media (min-width: 641px) {
            #mvuTable th:not(:first-child), #mvuTable td:not(:first-child) { text-align: center; }
        }

        /* Phones only: full-width search and button; each material row becomes a small card
           (name + status on top, Purchased / Used / Remaining underneath). Desktop unchanged. */
        @media (max-width: 640px) {
            .mvu-toolbar { flex-direction: column; align-items: stretch !important; gap: 10px !important; }
            .mvu-title { font-size: 16px !important; }
            .mvu-tools { flex-direction: column; align-items: stretch !important; gap: 8px !important; }
            .mvu-tools .search-box { max-width: none !important; width: 100%; }
            .mvu-tools .save-btn { width: 100%; justify-content: center; }

            .mvu-scroll { max-height: 520px; }
            /* no sideways scrolling and no visible scrollbar on phones (still scrolls up/down by swiping) */
            .mvu-scroll,
            .ulog-scroll { overflow-x: hidden !important; scrollbar-width: none; -ms-overflow-style: none; }
            .mvu-scroll::-webkit-scrollbar,
            .ulog-scroll::-webkit-scrollbar { display: none; }
            #mvuTable, #mvuTable tbody { display: block; width: 100%; min-width: 0; }
            #mvuTable thead { display: none; }
            #mvuTable tr.mvu-row {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 8px;
                padding: 14px 16px;
                border-bottom: 1px solid var(--border);
            }
            #mvuTable tr.mvu-row td { display: block; padding: 0 !important; border: none !important; min-width: 0; }
            #mvuTable td.mvu-name { grid-column: 1 / 3; align-self: center; }
            /* one line: the size follows the screen width; anything still too long ends in … */
            #mvuTable td.mvu-name strong {
                display: block;
                font-size: clamp(11px, 3.3vw, 13.5px);
                line-height: 1.3;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            #mvuTable tr.mvu-row { width: 100%; box-sizing: border-box; }
            #mvuTable td.mvu-status { grid-column: 3; grid-row: 1; text-align: right; align-self: center; }
            /* smaller pill so the longest status ("Not Purchased") fits its third of the row without cropping */
            #mvuTable td.mvu-status .status-badge {
                white-space: nowrap;
                font-size: 9.5px;
                padding: 3px 7px;
                letter-spacing: 0;
                max-width: 100%;
            }
            /* (tr.mvu-row td.mvu-num so it outranks the "no border / no padding" cell reset above) */
            #mvuTable tr.mvu-row td.mvu-num {
                background: var(--white);
                border: 1px solid var(--border) !important;
                border-radius: 12px;
                padding: 9px 6px !important;
                text-align: center;
                font-weight: 900;
                font-size: 17px;
                line-height: 1.1;
                color: var(--dark);
                font-variant-numeric: tabular-nums;
            }
            #mvuTable tr.mvu-row td.mvu-num::before {
                content: attr(data-label);
                display: block;
                font-size: 9.5px;
                font-weight: 800;
                letter-spacing: .06em;
                text-transform: uppercase;
                color: var(--muted);
                margin-bottom: 4px;
            }
            /* nothing left in stock reads in red */
            #mvuTable tr.mvu-row td.mvu-num[data-label="Remaining"].is-zero { color: var(--danger); }
            #mvuTable tr:not(.mvu-row) { display: block; }
            #mvuTable tr:not(.mvu-row) td { display: block; }
        }

        /* ── Usage Log: desktop keeps the table; the unit only shows in the phone layout ── */
        .ulog-unit { display: none; }
        /* Desktop: the log scrolls inside its card (about 8 rows), header row stays visible */
        .ulog-scroll { max-height: 440px; overflow-y: auto; }
        .ulog-scroll thead th { position: sticky; top: 0; z-index: 1; background: var(--cream-soft); }

        /* Phones only: each entry becomes a small card in the same style as Material Stock & Usage —
           material name on top, then Date Used / Quantity / Recorded By as three tiles,
           with Used For underneath only when it has something in it. */
        @media (max-width: 640px) {
            .ulog-scroll { max-height: 520px; overflow-y: auto; }
            #usageLogTable, #usageLogTable tbody { display: block; width: 100%; min-width: 0; }
            #usageLogTable thead { display: none; }
            #usageLogTable tr.ulog-row {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 8px;
                padding: 14px 16px;
                border-bottom: 1px solid var(--border);
            }
            #usageLogTable tr.ulog-row td { display: block; padding: 0 !important; border: none !important; min-width: 0; }
            #usageLogTable tr.ulog-row td.ulog-material { grid-column: 1 / -1; grid-row: 1; }
            #usageLogTable tr.ulog-row td.ulog-material strong { font-size: 13.5px; line-height: 1.3; }

            #usageLogTable tr.ulog-row td.ulog-date,
            #usageLogTable tr.ulog-row td.ulog-qty,
            #usageLogTable tr.ulog-row td.ulog-by {
                grid-row: 2;
                background: var(--white);
                border: 1px solid var(--border) !important;
                border-radius: 12px;
                padding: 9px 6px !important;
                text-align: center;
                font-weight: 800;
                font-size: 13px;
                line-height: 1.2;
                color: var(--dark);
                overflow-wrap: anywhere;
            }
            #usageLogTable tr.ulog-row td.ulog-qty { font-size: 17px; font-weight: 900; font-variant-numeric: tabular-nums; }
            #usageLogTable tr.ulog-row td.ulog-date::before,
            #usageLogTable tr.ulog-row td.ulog-qty::before,
            #usageLogTable tr.ulog-row td.ulog-by::before {
                content: attr(data-label);
                display: block;
                font-size: 9.5px;
                font-weight: 800;
                letter-spacing: .06em;
                text-transform: uppercase;
                color: var(--muted);
                margin-bottom: 4px;
            }
            #usageLogTable .ulog-unit { display: inline; font-size: 11px; font-weight: 700; color: var(--muted); }

            #usageLogTable tr.ulog-row td.ulog-for.is-empty { display: none; }
            #usageLogTable tr.ulog-row td.ulog-for {
                grid-column: 1 / -1; font-size: 12px; color: var(--dark);
                background: var(--cream-soft); border-radius: 8px; padding: 6px 10px !important;
            }
            #usageLogTable tr:not(.ulog-row), #usageLogTable tr:not(.ulog-row) td { display: block; }
        }

        /* ── Log Material Usage modal ── */
        .mu-toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; flex-shrink: 0; }
        .mu-toolbar .search-box { max-width: none; flex: 1; height: 44px; }
        .mu-count { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; color: var(--muted);
                    background: var(--accent-soft); border-radius: 999px; padding: 6px 12px; white-space: nowrap; transition: .2s ease; }
        .mu-count svg { width: 13px; height: 13px; }
        .mu-count.has-items { background: var(--dark); color: var(--white); }

        .mu-list { overflow-y: auto; flex: 1; min-height: 140px; border: 1px solid var(--border); border-radius: 16px; background: var(--white); }
        .mu-row { display: flex; align-items: center; gap: 14px; padding: 12px 16px; border-bottom: 1px solid var(--border);
                  border-left: 3px solid transparent; transition: background .15s ease, border-color .15s ease; }
        .mu-row:last-of-type { border-bottom: none; }
        .mu-row:hover { background: var(--cream-soft); }
        .mu-row.is-filled { background: #f3f8f4; border-left-color: var(--success); }
        .mu-info { flex: 1; min-width: 0; }
        .mu-name { font-size: 13.5px; font-weight: 800; color: var(--dark); line-height: 1.3; }
        .mu-remaining { margin-top: 3px; font-size: 11.5px; font-weight: 600; color: var(--muted); }
        .mu-remaining strong { color: var(--dark); }
        .mu-over { display: none; margin-top: 3px; font-size: 11.5px; font-weight: 800; color: var(--danger); }
        .mu-row.is-over { background: #FEF3F2; border-left-color: var(--danger); }
        .mu-row.is-over .mu-qty-wrap { border-color: var(--danger); }
        .mu-row.is-over .mu-over { display: block; }
        .mu-row.is-over .mu-remaining { display: none; }
        .mu-row.is-disabled { opacity: .55; }
        .mu-row.is-disabled:hover { background: transparent; }
        .mu-row.is-disabled .mu-qty-wrap { background: var(--cream-soft); cursor: not-allowed; }
        .mu-qty:disabled { cursor: not-allowed; }

        .mu-qty-wrap { display: flex; align-items: center; flex-shrink: 0; border: 1.5px solid var(--border); border-radius: 12px;
                       background: var(--white); overflow: hidden; transition: border-color .15s ease, box-shadow .15s ease; }
        .mu-qty-wrap:focus-within { border-color: var(--dark); box-shadow: 0 0 0 3px rgba(0,0,0,.08); }
        .mu-row.is-filled .mu-qty-wrap { border-color: var(--success); }
        .mu-qty { width: 84px; height: 40px; border: none !important; outline: none; background: transparent; text-align: right;
                  font-size: 14px; font-weight: 800; color: var(--dark); padding: 0 10px; box-shadow: none !important; -moz-appearance: textfield; }
        .mu-qty::-webkit-outer-spin-button, .mu-qty::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .mu-qty::placeholder { color: var(--muted-light); font-weight: 600; }
        .mu-unit { min-width: 52px; height: 40px; display: flex; align-items: center; justify-content: center; padding: 0 10px;
                   background: var(--cream-soft); border-left: 1px solid var(--border); font-size: 12px; font-weight: 700; color: var(--muted); }

        .mu-empty { display: none; padding: 28px; text-align: center; color: var(--muted); font-size: 13px; }
        .mu-error { display: none; margin-top: 12px; padding: 10px 14px; border-radius: 10px; background: #FEE4E2; color: var(--danger);
                    font-size: 13px; font-weight: 700; }

        @media (max-width: 640px) {
            /* search box gets the full width; the "materials to log" counter sits centered under it */
            .mu-toolbar { flex-direction: column; align-items: stretch; gap: 8px; }
            .mu-toolbar .search-box { width: 100%; min-height: 44px; }
            .mu-count { align-self: center; }

            /* smaller title/description so the close button keeps its full size */
            #logUsageModal .modal-header { gap: 12px; align-items: flex-start; }
            #logUsageModal .modal-header h2 { font-size: 20px; }
            #logUsageModal .modal-header p { font-size: 12.5px; line-height: 1.45; }
            #logUsageModal .modal-close { flex-shrink: 0; width: 36px; height: 36px; }

            /* nothing squeezes on top of anything: the material list has its own scroll height,
               and if the screen is still too short the whole popup scrolls */
            #logUsageModal .modal-card { max-height: 92vh !important; max-height: 92dvh !important; overflow-y: auto; }
            #logUsageModal #logUsageForm { flex: none !important; }
            #logUsageModal #logUsageForm > * { flex-shrink: 0; }
            #logUsageModal .mu-list { flex: none; height: clamp(200px, 42vh, 360px); min-height: 0; }
        }
        @media (max-width: 560px) {
            .mu-row { flex-wrap: wrap; gap: 10px; }
            .mu-qty-wrap { width: 100%; }
            .mu-qty { flex: 1; width: auto; }
        }
    </style>
</head>
<body class="page-enter">

    @include('partials.employee.header')

    <main class="admin-content">

            {{-- Breadcrumb --}}
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('employee.project_materials') }}#usage" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    Project Materials
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="color:var(--dark);font-weight:700;">{{ $project->name }}</span>
            </div>

            <div class="pv-page-header">
                <div>
                    <h1>{{ $project->name }}</h1>
                    <p>Log materials consumed during fabrication and track usage against the planned BOM.</p>
                </div>
            </div>


            @if(session('error'))
            <div class="alert-banner error">
                <i data-lucide="alert-circle"></i>
                {{ session('error') }}
            </div>
            @endif

            @if($errors->any())
            <div class="alert-banner error">
                <i data-lucide="alert-circle"></i>
                <div>@foreach($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
            </div>
            @endif

            {{-- Summary Cards --}}
            <div class="emp-kpis">
                <div class="emp-kpi">
                    <div class="emp-kpi-icon blue"><i data-lucide="package"></i></div>
                    <div>
                        <div class="emp-kpi-label">Planned Materials</div>
                        <div class="emp-kpi-value">{{ $totalPlanned }}</div>
                        <div class="emp-kpi-sub">From the project BOM</div>
                    </div>
                </div>
                <div class="emp-kpi">
                    <div class="emp-kpi-icon purple"><i data-lucide="clipboard-list"></i></div>
                    <div>
                        <div class="emp-kpi-label">Usage Entries Logged</div>
                        <div class="emp-kpi-value">{{ $totalLogged }}</div>
                        <div class="emp-kpi-sub">Active log entries</div>
                    </div>
                </div>
                <div class="emp-kpi">
                    <div class="emp-kpi-icon green"><i data-lucide="layers"></i></div>
                    <div>
                        <div class="emp-kpi-label">Total Quantity Used</div>
                        <div class="emp-kpi-value">{{ number_format($totalQtyUsed, 0) }}</div>
                        <div class="emp-kpi-sub">Combined units consumed</div>
                    </div>
                </div>
            </div>

            @php
                // Materials that have been purchased come first — those are the ones that can be logged
                $materialComparison = $materialComparison->sortBy(fn ($r) => $r['purchasedQty'] > 0 ? 0 : 1)->values();
            @endphp

            {{-- Material Stock & Usage --}}
            <div class="table-card mu-flush" style="margin-bottom:24px;">
                @php
                    // the header's at-a-glance summary of the stock
                    $mvuInStock  = $materialComparison->filter(fn ($r) => $r['purchasedQty'] > 0 && $r['stockRemaining'] > 0)->count();
                    $mvuUsedUp   = $materialComparison->filter(fn ($r) => $r['purchasedQty'] > 0 && $r['stockRemaining'] <= 0)->count();
                    $mvuNotYet   = $materialComparison->filter(fn ($r) => $r['purchasedQty'] <= 0)->count();
                @endphp
                <div class="table-toolbar mvu-toolbar" style="padding-bottom:0;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <div class="mvu-heading">
                        <span class="mvu-heading-icon"><i data-lucide="package"></i></span>
                        <div style="min-width:0;">
                            <div class="mvu-title">Material Stock &amp; Usage</div>
                            <div class="mvu-chips">
                                <span class="mvu-chip">{{ $materialComparison->count() }} material{{ $materialComparison->count() === 1 ? '' : 's' }}</span>
                                @if($mvuInStock)<span class="mvu-chip is-ok"><span class="mvu-dot"></span>{{ $mvuInStock }} in stock</span>@endif
                                @if($mvuUsedUp)<span class="mvu-chip is-out"><span class="mvu-dot"></span>{{ $mvuUsedUp }} used up</span>@endif
                                @if($mvuNotYet)<span class="mvu-chip is-wait"><span class="mvu-dot"></span>{{ $mvuNotYet }} not purchased</span>@endif
                            </div>
                        </div>
                    </div>
                    @if($materialComparison->isNotEmpty())
                    <div class="mvu-tools" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;flex:1;justify-content:flex-end;">
                        <div class="search-box" style="height:42px;max-width:300px;">
                            <i data-lucide="search"></i>
                            <input type="search" id="mvuSearch" placeholder="Search materials..." autocomplete="off">
                        </div>
                        <button type="button" class="save-btn" onclick="openLogUsageModal()">
                            <i data-lucide="plus"></i> Log Material Usage
                        </button>
                    </div>
                    @endif
                </div>
                <div class="table-wrapper mvu-scroll">
                    <table class="data-table" id="mvuTable">
                        <thead>
                            <tr>
                                <th>Material Name</th>
                                <th>Purchased Qty</th>
                                <th>Used Qty</th>
                                <th>Remaining</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($materialComparison as $row)
                            @php
                                $statusLabels = [
                                    'not_purchased' => 'Not Purchased',
                                    'pending'       => 'Not Started',
                                    'ongoing'       => 'In Use',
                                    'completed'     => 'Fully Used',
                                ];
                            @endphp
                            <tr class="mvu-row" data-name="{{ strtolower($row['material']->material_name) }}">
                                <td class="mvu-name"><strong>{{ $row['material']->material_name }}</strong></td>
                                <td class="mvu-num" data-label="Purchased">{{ number_format($row['purchasedQty'], 0) }}</td>
                                <td class="mvu-num" data-label="Used">{{ number_format($row['usedQty'], 0) }}</td>
                                <td class="mvu-num{{ $row['purchasedQty'] > 0 && $row['stockRemaining'] <= 0 ? ' is-zero' : '' }}" data-label="Remaining">{{ number_format($row['stockRemaining'], 0) }}</td>
                                <td class="mvu-status">
                                    @if($row['stockStatusKey'] === 'not_purchased')
                                    <span class="status-badge" style="background:var(--accent-soft);color:var(--muted);">{{ $statusLabels['not_purchased'] }}</span>
                                    @else
                                    <span class="status-badge {{ $row['stockStatusKey'] }}">
                                        {{ $statusLabels[$row['stockStatusKey']] }}
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="text-align:center;padding:32px;color:var(--muted);">
                                    No planned materials found for this project.
                                </td>
                            </tr>
                            @endforelse
                            <tr id="mvuNoMatch" style="display:none;">
                                <td colspan="5" style="text-align:center;padding:32px;color:var(--muted);">No materials match your search.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Usage Log --}}
            @php
                // header summary: how many entries, how many today, and when the last one was logged
                $ulogToday  = $usageEntries->filter(fn ($e) => $e->used_date?->isToday())->count();
                $ulogLatest = $usageEntries->max('used_date');
            @endphp
            <div class="table-card mu-flush">
                <div class="table-toolbar mvu-toolbar" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <div class="mvu-heading">
                        <span class="mvu-heading-icon"><i data-lucide="history"></i></span>
                        <div style="min-width:0;">
                            <div class="mvu-title">Usage Log</div>
                            <div class="mvu-chips">
                                <span class="mvu-chip">{{ $usageEntries->count() }} entr{{ $usageEntries->count() === 1 ? 'y' : 'ies' }}</span>
                                @if($ulogToday)<span class="mvu-chip is-ok"><span class="mvu-dot"></span>{{ $ulogToday }} today</span>@endif
                                @if($ulogLatest)<span class="mvu-chip"><i data-lucide="clock" style="width:11px;height:11px;"></i> Last logged {{ \Carbon\Carbon::parse($ulogLatest)->format('M d, Y') }}</span>@endif
                            </div>
                        </div>
                    </div>
                    @if($usageEntries->isNotEmpty())
                    <div class="mvu-tools" style="display:flex;align-items:center;gap:10px;flex:1;justify-content:flex-end;">
                        <div class="search-box" style="height:42px;max-width:300px;">
                            <i data-lucide="search"></i>
                            <input type="search" id="ulogSearch" placeholder="Search material, purpose or person..." autocomplete="off">
                        </div>
                    </div>
                    @endif
                </div>
                <div class="table-wrapper ulog-scroll">
                    <table class="data-table" id="usageLogTable">
                        <thead>
                            <tr>
                                <th>Date Used</th>
                                <th>Material</th>
                                <th>Quantity</th>
                                <th>Used For</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($usageEntries as $entry)
                            <tr class="ulog-row" data-search="{{ strtolower($entry->material_name . ' ' . str_replace('_', ' ', (string) $entry->used_for) . ' ' . $entry->recorded_by . ' ' . $entry->used_date?->format('M d, Y')) }}">
                                <td class="ulog-date" data-label="Date Used">{{ $entry->used_date->format('M d, Y') }}</td>
                                <td class="ulog-material"><strong>{{ $entry->material_name }}</strong></td>
                                <td class="ulog-qty" data-label="Quantity">{{ number_format($entry->quantity_used, 0) }}<span class="ulog-unit"> {{ $entry->unit }}</span></td>
                                <td class="ulog-for{{ $entry->used_for ? '' : ' is-empty' }}">{{ $entry->used_for ? ucfirst(str_replace('_', ' ', $entry->used_for)) : '—' }}</td>
                                <td class="ulog-by" data-label="Recorded By">{{ $entry->recorded_by ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="text-align:center;padding:32px;color:var(--muted);">
                                    No usage entries yet. Click <strong>Log Material Usage</strong> above to log what was used.
                                </td>
                            </tr>
                            @endforelse
                            @if($usageEntries->isNotEmpty())
                            <tr id="ulogNoMatch" style="display:none;">
                                <td colspan="5" style="text-align:center;padding:32px;color:var(--muted);">
                                    No entries match your search.
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

    </main>

    {{-- ===================== LOG USAGE MODAL — several materials in one save ===================== --}}
    <div class="modal-overlay" id="logUsageModal">
        <div class="modal-card" style="max-width:640px;max-height:90vh;display:flex;flex-direction:column;">
            <div class="modal-header" style="flex-shrink:0;">
                <div>
                    <h2>Log Material Usage</h2>
                    <p>Enter how much of each material was used on <strong>{{ $project->name }}</strong>. Leave the rest blank.</p>
                </div>
                <button class="modal-close" type="button" onclick="closeModal('logUsageModal')">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('employee.material_usage.store', $project->id) }}" id="logUsageForm" style="display:flex;flex-direction:column;min-height:0;flex:1;">
                @csrf
                <div class="mu-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="search" id="usageSearch" placeholder="Search materials..." autocomplete="off">
                    </div>
                    <span class="mu-count" id="usageCount"><i data-lucide="list-checks"></i><span>0 to log</span></span>
                </div>

                <div class="mu-list" id="usageList">
                    @foreach($materialComparison as $row)
                    @php
                        $fmtQty  = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.');
                        $inStock = (float) $row['stockRemaining'];
                        $unit    = $row['material']->unit;
                    @endphp
                    <div class="mu-row{{ $inStock <= 0 ? ' is-disabled' : '' }}" data-name="{{ strtolower($row['material']->material_name) }}">
                        <div class="mu-info">
                            <div class="mu-name">{{ $row['material']->material_name }}</div>
                            <div class="mu-remaining">
                                @if($row['purchasedQty'] <= 0)
                                    Not purchased yet
                                @elseif($inStock <= 0)
                                    All {{ $fmtQty($row['purchasedQty']) }} {{ $unit }} purchased already used
                                @else
                                    <strong>{{ $fmtQty($inStock) }} {{ $unit }}</strong> in stock &middot; {{ $fmtQty($row['purchasedQty']) }} purchased
                                @endif
                            </div>
                            <div class="mu-over">Only {{ $fmtQty($inStock) }} {{ $unit }} in stock</div>
                        </div>
                        <label class="mu-qty-wrap" title="{{ $inStock > 0 ? 'Quantity used' : 'Nothing in stock to log' }}">
                            <input type="number" name="quantity_used[{{ $row['material']->id }}]" class="mu-qty usage-qty"
                                   data-material-id="{{ $row['material']->id }}" data-max="{{ $inStock }}"
                                   min="0" max="{{ $inStock }}" step="0.01" placeholder="0" onwheel="this.blur()" inputmode="decimal"
                                   value="{{ old('quantity_used.' . $row['material']->id) }}"
                                   {{ $inStock <= 0 ? 'disabled' : '' }}>
                            <span class="mu-unit">{{ $unit ?: 'qty' }}</span>
                        </label>
                    </div>
                    @endforeach
                    <div class="mu-empty" id="usageNoMatch">No materials match your search.</div>
                </div>

                {{-- no date to pick: usage is logged as of today (set on the server) --}}
                <div class="mu-error" id="usageFormError"></div>

                <div class="modal-actions" style="flex-shrink:0;">
                    <button type="button" class="cancel-btn" onclick="closeModal('logUsageModal')">Cancel</button>
                    <button type="submit" class="save-btn" id="usageSaveBtn">
                        <i data-lucide="check-circle" style="width:15px;height:15px;"></i>
                        Save All
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/employee.js') }}"></script>
    <script>
        function openModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
        }
        function closeModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
        }

        // One modal for every material; the row's pencil button jumps straight to that material
        function openLogUsageModal(materialId) {
            document.getElementById('usageSearch').value = '';
            filterUsageRows();
            document.getElementById('usageFormError').style.display = 'none';
            openModal('logUsageModal');
            var target = materialId
                ? document.querySelector('.usage-qty[data-material-id="' + materialId + '"]')
                : document.querySelector('.usage-qty:not(:disabled)');
            if (target) {
                target.scrollIntoView({ block: 'center' });
                setTimeout(function () { target.focus(); }, 50);
            }
        }

        function filterUsageRows() {
            var q = document.getElementById('usageSearch').value.toLowerCase().trim();
            var shown = 0;
            document.querySelectorAll('#usageList .mu-row').forEach(function (row) {
                var match = !q || row.dataset.name.indexOf(q) !== -1;
                row.style.display = match ? 'flex' : 'none';
                if (match) shown++;
            });
            document.getElementById('usageNoMatch').style.display = shown ? 'none' : 'block';
        }

        function updateUsageCount() {
            var n = 0;
            var over = 0;
            document.querySelectorAll('.usage-qty').forEach(function (input) {
                var qty    = parseFloat(input.value);
                var filled = qty > 0;
                var isOver = filled && qty > parseFloat(input.dataset.max) + 0.0001;
                if (filled) n++;
                if (isOver) over++;
                var row = input.closest('.mu-row');
                row.classList.toggle('is-filled', filled && !isOver);
                row.classList.toggle('is-over', isOver);
            });
            window.__usageOverCount = over;
            var chip = document.getElementById('usageCount');
            chip.classList.toggle('has-items', n > 0);
            chip.lastChild.textContent = n + (n === 1 ? ' material' : ' materials') + ' to log';
            var btn = document.getElementById('usageSaveBtn');
            btn.lastChild.textContent = n > 1 ? ' Save All (' + n + ')' : ' Save All';
            return n;
        }

        document.addEventListener('DOMContentLoaded', function() {
            var mvuSearch = document.getElementById('mvuSearch');
            if (mvuSearch) {
                mvuSearch.addEventListener('input', function () {
                    var q = this.value.toLowerCase().trim();
                    var shown = 0;
                    document.querySelectorAll('#mvuTable .mvu-row').forEach(function (row) {
                        var match = !q || row.dataset.name.indexOf(q) !== -1;
                        row.style.display = match ? '' : 'none';
                        if (match) shown++;
                    });
                    document.getElementById('mvuNoMatch').style.display = shown ? 'none' : '';
                });
            }

            // Usage Log search: material, purpose, who recorded it, or the date
            var ulogSearch = document.getElementById('ulogSearch');
            if (ulogSearch) {
                ulogSearch.addEventListener('input', function () {
                    var q = this.value.toLowerCase().trim();
                    var shown = 0;
                    document.querySelectorAll('#usageLogTable .ulog-row').forEach(function (row) {
                        var match = !q || row.dataset.search.indexOf(q) !== -1;
                        row.style.display = match ? '' : 'none';
                        if (match) shown++;
                    });
                    document.getElementById('ulogNoMatch').style.display = shown ? 'none' : '';
                });
            }

            var usageForm = document.getElementById('logUsageForm');
            if (usageForm) {
                document.getElementById('usageSearch').addEventListener('input', filterUsageRows);
                usageForm.addEventListener('input', updateUsageCount);
                usageForm.addEventListener('submit', function (e) {
                    var err = document.getElementById('usageFormError');
                    if (updateUsageCount() === 0) {
                        e.preventDefault();
                        err.textContent = 'Enter the quantity used for at least one material.';
                        err.style.display = 'block';
                        return;
                    }
                    if (window.__usageOverCount > 0) {
                        e.preventDefault();
                        err.textContent = 'Some quantities are more than what is in stock — fix the rows marked in red.';
                        err.style.display = 'block';
                        var firstOver = document.querySelector('.mu-row.is-over .usage-qty');
                        if (firstOver) { firstOver.scrollIntoView({ block: 'center' }); firstOver.focus(); }
                        return;
                    }
                    // blank rows aren't sent — only the materials actually used
                    document.querySelectorAll('.usage-qty').forEach(function (input) {
                        if (!(parseFloat(input.value) > 0)) input.disabled = true;
                    });
                    document.getElementById('usageSaveBtn').disabled = true;
                });
                updateUsageCount();
                // came back with a validation error → reopen the list with what was typed
                @if($errors->any()) openModal('logUsageModal'); @endif
            }

            document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
                overlay.addEventListener('click', function(e) {
                    if (e.target === this) closeModal(this.id);
                });
            });

            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
    @if(session('success'))
    {{-- Saved (e.g. material usage logged): small dark toast centered below
         the header. Longer messages stay a little longer. --}}
    <div class="toast" id="empUsageToast" role="status">
        <i data-lucide="check-circle"></i>
        <span>{{ session('success') }}</span>
    </div>
    <script>
        window.addEventListener('load', function () {
            var t = document.getElementById('empUsageToast');
            if (!t) return;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            var ms = t.textContent.trim().length > 80 ? 5000 : 3000;
            requestAnimationFrame(function () { requestAnimationFrame(function () { t.classList.add('show'); }); });
            setTimeout(function () { t.classList.remove('show'); }, ms);
        });
    </script>
    @endif
</body>
</html>
