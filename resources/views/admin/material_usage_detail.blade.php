<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project->name }} — Materials | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}?v={{ @filemtime(public_path('css/admin.css')) }}" rel="stylesheet">
</head>
<body class="page-enter pg-admin-material-usage-detail">

    @include('partials.admin.header')
    @include('partials.admin.fund_overrun_modal')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            <div class="mu-breadcrumb" style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                {{-- active projects are listed directly on Materials; completed / archived sit inside their client --}}
                @php $muClosed = in_array($project->status, ['completed', 'archived']); @endphp
                <a href="{{ route('admin.material_usage', $muClosed ? ['tab' => $project->status] : []) }}" style="color:var(--muted);text-decoration:none;font-weight:600;">Materials</a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                @if($muClosed)
                <a href="{{ route('admin.material_usage.client', urlencode($project->client_id ?: $project->client)) }}?tab={{ $project->status }}" style="color:var(--muted);text-decoration:none;font-weight:600;">{{ $project->live_client_name }}</a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                @endif
                <span style="font-weight:700;color:var(--dark);">{{ $project->name }}</span>
            </div>

            <div class="page-header">
                <div>
                    <h1>
                        {{ $project->name }}
                    </h1>
                    <p class="mu-meta"><span class="client-pill">{{ $project->live_client_name }}</span> @include('partials.admin.project_badge', ['kind' => 'phase'])</p>
                </div>
                <a href="{{ url('/admin/project-view/' . $project->id) }}" class="cancel-btn" style="text-decoration:none;white-space:nowrap;">
                    <i data-lucide="folder-open"></i> Open Project
                </a>
            </div>

            {{-- ── Overview: where the material budget stands, at a glance ── --}}
            @php
                $ovMats   = $plannedMaterials ?? $project->activeMaterials;
                $ovFactor = $materialFactor ?? ($ovMats->first()?->factor ?? 7);
                $ovBudget = $ovMats->sum(fn ($m) => round((float) $m->total_cost * (1 + $ovFactor / 100), 2));
                $ovSpent  = (float) $totalPurchased;
                $ovPct    = $ovBudget > 0 ? round($ovSpent / $ovBudget * 100) : 0;
                $ovLeft   = $ovBudget - $ovSpent;
                $ovTone   = $ovPct > 100 ? 'over' : ($ovPct >= 90 ? 'near' : 'ok');

                // per BOM material: how much of the planned quantity is bought, and whether it went over its budget
                $ovStatus = [];
                foreach ($ovMats as $m) {
                    $rows   = $purchases->where('project_material_id', $m->id);
                    $bought = (float) $rows->sum('qty_bought');
                    $paid   = (float) $rows->sum('total_paid');
                    $budget = round((float) $m->total_cost * (1 + $ovFactor / 100), 2);
                    $ovStatus[$m->material_name] = $paid > $budget + 0.005 && $budget > 0 ? 'over'
                        : ($bought <= 0 ? 'none' : ($bought + 0.0001 >= (float) $m->quantity ? 'complete' : 'partial'));
                }
                $ovCounts = array_count_values($ovStatus) + ['none' => 0, 'partial' => 0, 'complete' => 0, 'over' => 0];
                $ovDone   = $ovCounts['complete'] + $ovCounts['over'];
                $ovTotal  = count($ovStatus);
            @endphp
            @if($ovTotal > 0)
            <div class="mu-overview">
                <div class="mu-kpi">
                    <div class="mu-kpi-icon" style="background:#EAF0FF;color:#2A4EAA;"><i data-lucide="clipboard-list"></i></div>
                    <div>
                        <div class="mu-kpi-label">Material Budget</div>
                        <div class="mu-kpi-value">₱{{ number_format($ovBudget, 2) }}</div>
                        <div class="mu-kpi-sub">BOM incl. +{{ number_format($ovFactor, 0) }}% factor</div>
                    </div>
                </div>
                <div class="mu-kpi">
                    <div class="mu-kpi-icon" style="background:#E7F6EC;color:#207A3A;"><i data-lucide="shopping-cart"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="mu-kpi-label">Spent on Materials</div>
                        <div class="mu-kpi-value">₱{{ number_format($ovSpent, 2) }}</div>
                        <div class="mu-bar mu-bar--{{ $ovTone }}" title="{{ $ovPct }}% of the material budget"><span style="width:{{ min(100, $ovPct) }}%;"></span></div>
                        <div class="mu-kpi-sub"><strong class="mu-tone-{{ $ovTone }}">{{ $ovPct }}%</strong> of budget used</div>
                    </div>
                </div>
                <div class="mu-kpi">
                    <div class="mu-kpi-icon" style="{{ $ovLeft < 0 ? 'background:#FEE2E2;color:#B91C1C;' : 'background:#FFF4E0;color:#B45309;' }}"><i data-lucide="{{ $ovLeft < 0 ? 'trending-up' : 'piggy-bank' }}"></i></div>
                    <div>
                        <div class="mu-kpi-label">{{ $ovLeft < 0 ? 'Over Budget' : 'Remaining Budget' }}</div>
                        <div class="mu-kpi-value" style="{{ $ovLeft < 0 ? 'color:#B91C1C;' : '' }}">₱{{ number_format(abs($ovLeft), 2) }}</div>
                        <div class="mu-kpi-sub">{{ $ovLeft < 0 ? 'Spent more than the BOM planned' : 'Left to spend within the BOM' }}</div>
                    </div>
                </div>
                <div class="mu-kpi">
                    <div class="mu-kpi-icon" style="background:#F3E8FF;color:#7C3AED;"><i data-lucide="package-check"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="mu-kpi-label">Materials Bought</div>
                        <div class="mu-kpi-value">{{ $ovDone }} <span style="font-size:14px;color:var(--muted);font-weight:700;">of {{ $ovTotal }}</span></div>
                        <div class="mu-bar mu-bar--ok"><span style="width:{{ $ovTotal ? round($ovDone / $ovTotal * 100) : 0 }}%;"></span></div>
                        <div class="mu-kpi-sub">
                            @if($ovCounts['partial'])<span>{{ $ovCounts['partial'] }} partial</span> · @endif
                            {{ $ovCounts['none'] }} not bought yet
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if(session('success'))
            {{-- Saved (e.g. purchase recorded, usage logged): the small dark toast below the header
                 (same as "Salary record saved."), shown for 3 seconds --}}
            <div class="toast" id="materialsDetailToast" role="status">
                <i data-lucide="check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
            <script>
                window.addEventListener('load', function () {
                    var t = document.getElementById('materialsDetailToast');
                    if (!t) return;
                    // the page-enter animation moves the content, which would carry a fixed toast with it —
                    // living directly under <body> keeps it pinned below the header
                    document.body.appendChild(t);
                    requestAnimationFrame(function () { requestAnimationFrame(function () { t.classList.add('show'); }); });
                    setTimeout(function () { t.classList.remove('show'); }, 3000);
                });
            </script>
            @endif
            @if(session('error'))
            <div class="alert-banner error"><i data-lucide="alert-circle"></i> {{ session('error') }}</div>
            @endif

            {{-- Tabs --}}
            <div class="pm-tabs">
                <button class="pm-tab active" data-tab="bom">
                    <i data-lucide="clipboard-list" style="width:14px;height:14px;"></i>
                    BOM &mdash; planned materials
                    <span class="pm-tab-count">{{ $ovTotal }}</span>
                </button>
                <button class="pm-tab" data-tab="purchased">
                    <i data-lucide="shopping-cart" style="width:14px;height:14px;"></i>
                    Purchased materials
                    <span class="pm-tab-count" title="Purchase transactions">{{ $purchases->count() }}</span>
                </button>
                <button class="pm-tab" data-tab="usage">
                    <i data-lucide="activity" style="width:14px;height:14px;"></i>
                    Material usage
                    <span class="pm-tab-count" title="Usage entries">{{ $usageEntries->where('status', 'active')->count() }}</span>
                </button>
                <button class="pm-tab" data-tab="variance">
                    <i data-lucide="git-compare" style="width:14px;height:14px;"></i>
                    Variance summary
                    @if($ovCounts['over'] > 0)
                    <span class="pm-tab-count is-alert" title="{{ $ovCounts['over'] }} material{{ $ovCounts['over'] > 1 ? 's' : '' }} over budget">{{ $ovCounts['over'] }} over</span>
                    @endif
                </button>
            </div>

            @php
                $activeMats       = $plannedMaterials ?? $project->activeMaterials;
                $materialFactor   = $materialFactor ?? ($activeMats->first()?->factor ?? 7);
            @endphp

            {{-- TAB 1: BOM --}}
            <div class="pm-tab-content active" id="tab-bom">
                <div class="pm-card">
                    <div class="pm-card-header">
                        <div>
                            <div class="pm-card-title">Bill of Materials &mdash; planned estimate</div>
                            <div class="pm-card-sub">
                                Set during Planning phase &middot; basis for budget adherence KPI
                                &nbsp;&nbsp;
                                <span style="background:#EAF0FF;color:#2A4EAA;font-weight:800;font-size:11.5px;padding:3px 10px;border-radius:999px;">
                                    Factor: +{{ number_format($materialFactor, 0) }}% applied to all materials
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="table-wrapper bom-scroll">
                        <table class="pm-table" style="table-layout:fixed;width:100%;">
                            <colgroup>
                                {{-- Material a little wider for long names; the six value columns share the rest evenly --}}
                                <col style="width:22%;">
                                <col style="width:13%;">
                                <col style="width:13%;">
                                <col style="width:13%;">
                                <col style="width:13%;">
                                <col style="width:13%;">
                                <col style="width:13%;">
                            </colgroup>
                            <thead>
                                <tr style="background:var(--cream-soft);">
                                    <th style="text-align:left;padding:10px 12px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Material</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Unit</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Qty</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Unit Cost (₱)</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Base Total (₱)</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Budgeted Cost (₱)</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $baseSubtotal = 0; $budgetedSubtotal = 0; @endphp
                                @forelse($activeMats as $mat)
                                @php
                                    $base     = (float)$mat->total_cost;
                                    $budgeted = round($base * (1 + $materialFactor / 100), 2);
                                    $baseSubtotal     += $base;
                                    $budgetedSubtotal += $budgeted;
                                    $hasPurchase = isset($purchases) && $purchases->where('project_material_id', $mat->id)->isNotEmpty();
                                @endphp
                                <tr style="border-bottom:1px solid var(--border);">
                                    <td style="padding:12px 12px;"><strong style="font-size:13px;font-weight:700;color:var(--dark);">{{ $mat->material_name }}</strong></td>
                                    <td style="text-align:center;padding:12px 8px;font-size:12px;font-weight:400;color:var(--muted);">{{ $mat->unit ?? '—' }}</td>
                                    <td style="text-align:center;padding:12px 8px;font-size:13px;font-weight:400;color:var(--dark);">{{ number_format($mat->quantity, 0) }}</td>
                                    <td style="text-align:center;padding:12px 8px;font-size:13px;font-weight:400;color:var(--dark);">₱{{ number_format($mat->price_per_unit, 2) }}</td>
                                    <td style="text-align:center;padding:12px 8px;font-size:13px;font-weight:400;color:var(--dark);">₱{{ number_format($base, 2) }}</td>
                                    <td style="text-align:center;padding:12px 8px;font-size:13px;font-weight:700;color:var(--dark);">₱{{ number_format($budgeted, 2) }}</td>
                                    <td style="text-align:center;padding:12px 8px;">
                                        @if($hasPurchase)
                                        <span class="pm-status purchased">Purchased ✓</span>
                                        @else
                                        <span class="pm-status pending">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" style="text-align:center;padding:40px;font-size:13px;font-weight:400;color:var(--muted);">No materials in BOM yet. Add via <strong>Project Quotations</strong>.</td></tr>
                                @endforelse
                            </tbody>
                            @if($activeMats->isNotEmpty())
                            <tfoot>
                                <tr style="background:var(--cream-soft);border-top:2px solid var(--border);">
                                    <td style="padding:12px 12px;color:var(--dark);font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Subtotal</td>
                                    <td></td><td></td><td></td>
                                    <td style="text-align:center;padding:12px 8px;font-size:13px;color:var(--dark);font-weight:900;">₱{{ number_format($baseSubtotal, 2) }}</td>
                                    <td style="text-align:center;padding:12px 8px;font-size:13px;color:var(--dark);font-weight:900;">₱{{ number_format($budgetedSubtotal, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            {{-- TAB 2: PURCHASED --}}
            <div class="pm-tab-content" id="tab-purchased">
                @php
                    $purchasesByName = $purchases->groupBy('material_name');
                    $plannedNames    = $plannedMaterials->pluck('material_name');
                    $extraNames      = $purchasesByName->keys()->diff($plannedNames);
                    $allMaterialRows = $plannedNames->concat($extraNames)->unique()->values();
                @endphp

                {{-- Summary table (full width — the purchase form now lives in a modal). One table: the
                     header stays visible while the rows scroll, the TOTAL row always lines up with the
                     columns, the card is only as tall as its rows, and narrow screens scroll sideways. --}}
                <div class="pm-card pm-purchased-card" style="margin-bottom:16px;min-width:0;overflow:hidden;">
                    <div class="pm-card-header pm-purchased-header">
                        <div>
                            <div class="pm-card-title">Purchased materials &mdash; summary</div>
                            <div class="pm-card-sub">Aggregated totals per material &middot; updates KPI automatically</div>
                        </div>
                        <div class="pm-purchased-header-right" style="display:flex;align-items:center;gap:14px;">
                            <button type="button" class="add-btn" id="openLogPurchaseModal" style="white-space:nowrap;">
                                <i data-lucide="plus"></i>
                                Log New Purchase
                            </button>
                        </div>
                    </div>

                    {{-- Quick filters + search over the rows below --}}
                    @if($allMaterialRows->isNotEmpty())
                    <div class="mu-filterbar">
                        <div class="mu-chips" id="muPurchasedChips">
                            <button type="button" class="mu-chip active" data-filter="">All <span>{{ $allMaterialRows->count() }}</span></button>
                            <button type="button" class="mu-chip" data-filter="none">Not bought <span>{{ $ovCounts['none'] }}</span></button>
                            <button type="button" class="mu-chip" data-filter="partial">Partial <span>{{ $ovCounts['partial'] }}</span></button>
                            <button type="button" class="mu-chip" data-filter="complete">Complete <span>{{ $ovCounts['complete'] }}</span></button>
                            <button type="button" class="mu-chip is-alert" data-filter="over">Over budget <span>{{ $ovCounts['over'] }}</span></button>
                        </div>
                        <div class="mu-search">
                            <i data-lucide="search"></i>
                            <input type="text" id="muPurchasedSearch" placeholder="Search material...">
                        </div>
                    </div>
                    @endif

                    <div class="pm-purchased-scroll">
                        <table class="pm-purchased-table" id="muPurchasedTable">
                            <colgroup>
                                {{-- same split as the Bill of Materials: Material a little wider, the rest even --}}
                                <col style="width:22%;">
                                <col style="width:13%;">
                                <col style="width:13%;">
                                <col style="width:13%;">
                                <col style="width:13%;">
                                <col style="width:13%;">
                                <col style="width:13%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th style="text-align:left;padding-left:14px;">Material</th>
                                    <th>Unit</th>
                                    <th>Planned Qty</th>
                                    <th>Qty Bought</th>
                                    <th>Total Paid (₱)</th>
                                    <th>VS BOM</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allMaterialRows as $matName)
                                @php
                                    $bomMat    = $plannedMaterials->firstWhere('material_name', $matName);
                                    $group     = $purchasesByName->get($matName);
                                    $totalQty  = $group ? $group->sum('qty_bought') : 0;
                                    $totalPaid = $group ? $group->sum('total_paid') : 0;
                                    $planned   = $bomMat->quantity ?? null;
                                    $budgeted  = $bomMat ? round((float)$bomMat->total_cost * (1 + $materialFactor / 100), 2) : 0;
                                    $vsPct     = $budgeted > 0 ? min(150, round(($totalPaid / $budgeted) * 100)) : null;
                                    $vsColor   = $vsPct !== null ? ($vsPct <= 100 ? '#16a34a' : '#ef4444') : 'var(--muted)';
                                    $unit      = $group ? $group->first()->unit : ($bomMat->unit ?? null);
                                    // bought outside the BOM: no plan to compare against
                                    $rowStatus = $ovStatus[$matName] ?? 'extra';
                                    $statusMap = [
                                        'none'     => ['Not bought', 'pending'],
                                        'partial'  => ['Partial', 'partial'],
                                        'complete' => ['Complete', 'purchased'],
                                        'over'     => ['Over budget', 'over'],
                                        'extra'    => ['Not in BOM', 'extra'],
                                    ];
                                @endphp
                                <tr data-status="{{ $rowStatus }}" data-search="{{ strtolower($matName) }}">
                                    <td style="text-align:left;padding-left:14px;">
                                        <strong style="font-size:13px;font-weight:700;color:var(--dark);">{{ $matName }}</strong>
                                    </td>
                                    <td style="font-size:12px;color:var(--muted);">{{ $unit ?: '—' }}</td>
                                    <td>{{ $planned !== null ? number_format($planned, 0) : '—' }}</td>
                                    <td>
                                        <div class="mu-qty">
                                            <span><strong>{{ number_format($totalQty, 0) }}</strong>@if($planned) <em>/ {{ number_format($planned, 0) }}</em>@endif</span>
                                        </div>
                                    </td>
                                    <td style="font-weight:700;">₱{{ number_format($totalPaid, 2) }}</td>
                                    <td>
                                        @if($vsPct !== null)
                                        <div style="display:flex;align-items:center;gap:6px;justify-content:center;">
                                            <div style="width:60px;height:5px;background:var(--cream-deep);border-radius:999px;overflow:hidden;flex-shrink:0;">
                                                <div style="height:100%;width:{{ min(100,$vsPct) }}%;background:{{ $vsColor }};border-radius:999px;"></div>
                                            </div>
                                            <span style="font-weight:800;font-size:12px;color:{{ $vsColor }};min-width:30px;">{{ $vsPct }}%</span>
                                        </div>
                                        @else
                                        <span style="color:var(--muted);">—</span>
                                        @endif
                                    </td>
                                    <td><span class="pm-status {{ $statusMap[$rowStatus][1] }}">{{ $statusMap[$rowStatus][0] }}</span></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" style="padding:48px 20px;color:var(--muted);">
                                        <i data-lucide="shopping-cart" style="width:32px;height:32px;color:var(--border);display:block;margin:0 auto 10px;"></i>
                                        No materials planned or purchased yet.
                                    </td>
                                </tr>
                                @endforelse
                                <tr id="muPurchasedNoMatch" style="display:none;">
                                    <td colspan="7" style="padding:40px 20px;color:var(--muted);">
                                        <i data-lucide="search-x" style="width:28px;height:28px;color:var(--border);display:block;margin:0 auto 8px;"></i>
                                        No materials match this filter.
                                    </td>
                                </tr>
                            </tbody>
                            @if($allMaterialRows->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <td style="text-align:left;padding-left:14px;font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Total</td>
                                    <td></td>
                                    <td style="color:var(--muted);">—</td>
                                    <td style="font-weight:900;">{{ number_format($purchases->sum('qty_bought'), 0) }}</td>
                                    <td style="font-weight:900;">&#x20B1;{{ number_format($totalPurchased, 2) }}</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>{{-- end summary card --}}

                {{-- Log New Purchase modal — supports multiple material rows in one
                     submission, so a whole supplier run can be logged at once instead
                     of one purchase at a time. --}}
                <div class="modal-overlay" id="logPurchaseModal">
                <div class="modal-card" style="max-width:860px;width:95%;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;">
                    <div class="pp-head">
                        <div>
                            <div class="pp-title">Log New Purchase</div>
                            <div class="pp-sub">Record one or more material purchases at once</div>
                        </div>
                        <button type="button" class="modal-close" id="closeLogPurchaseModal" aria-label="Close">
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('admin.material_usage.store_purchase', $project->id) }}" id="logPurchaseForm" class="pr-form">
                        @csrf
                        <div class="pr-body">
                            <div class="pr-details">
                                {{-- General supplier: applies to every row whose Supplier is left on "Default" --}}
                                <div class="pm-add-field">
                                    <label>Supplier (default)</label>
                                    @if(isset($suppliers) && $suppliers->isNotEmpty())
                                    <select id="supplierDropdown" onchange="onSupplierChange(this)">
                                        <option value="" selected>Select supplier...</option>
                                        @foreach($suppliers as $sup)
                                        <option value="{{ $sup->name }}">{{ $sup->name }}{{ $sup->company ? ' — '.$sup->company : '' }}</option>
                                        @endforeach
                                        <option value="__other__">Other (type manually)</option>
                                    </select>
                                    <input type="text" name="supplier" id="supplierCustom"
                                           placeholder="Type supplier name" maxlength="255"
                                           oninput="refreshRowSupplierDefaults()"
                                           style="display:none;margin-top:6px;">
                                    @else
                                    <input type="text" name="supplier" id="supplierCustom" placeholder="Supplier name" maxlength="255"
                                           oninput="refreshRowSupplierDefaults()">
                                    @endif
                                </div>
                                <div class="pm-add-field">
                                    <label>Date</label>
                                    <input type="date" name="purchase_date" required value="{{ now()->format('Y-m-d') }}">
                                </div>
                            </div>

                            <div class="pr-section-head">
                                <div class="pr-section-title">Materials <span class="pr-count" id="purchaseRowCount">0</span></div>
                                <button type="button" class="cancel-btn pr-add" onclick="openPurchasePicker()">
                                    <i data-lucide="plus"></i> Add Material
                                </button>
                            </div>

                            <div id="purchaseRowsContainer"></div>
                            <div id="purchaseRowsEmpty" class="pr-empty">
                                No materials added yet. Click <strong>Add Material</strong> to get started.
                            </div>

                            {{-- Purchases are paid from what the client has paid so far; the Project Budget is only the plan --}}
                            <input type="hidden" name="use_fund" id="purchaseUseFund" value="0">
                            <div class="pf-panel" id="purchaseFundsPanel">
                                <div class="pf-fig"><span>Total Received</span><strong>₱{{ number_format($projectFunds['received'], 2) }}</strong></div>
                                <div class="pf-fig" title="Materials ₱{{ number_format($projectFunds['materials'], 2) }} · Labor ₱{{ number_format($projectFunds['labor'], 2) }} · Overhead ₱{{ number_format($projectFunds['overhead'], 2) }}">
                                    <span>Already Spent</span><strong>₱{{ number_format($projectFunds['spent'], 2) }}</strong>
                                    <small>Materials, labor &amp; overhead</small>
                                </div>
                                <div class="pf-fig pf-available"><span>Available</span><strong id="pfAvailable">₱{{ number_format(max(0, $projectFunds['available']), 2) }}</strong>
                                    @if($projectFunds['fund_owed'] > 0)<small>incl. ₱{{ number_format($projectFunds['fund_owed'], 2) }} from the revolving fund</small>@endif
                                </div>
                                <div class="pf-fig pf-budget"><span>Project Budget</span><strong>₱{{ number_format($projectBudget, 2) }}</strong><small>Planned, for reference</small></div>
                                <div class="pf-status" id="pfStatus" hidden></div>
                            </div>
                        </div>

                        <div class="pr-foot">
                            <div class="pr-grand">
                                <span>Grand Total</span>
                                <input type="text" id="purchaseGrandTotal" readonly tabindex="-1" value="&#x20B1;0.00" aria-label="Grand total">
                            </div>
                            <div class="pp-actions">
                                <button type="button" class="cancel-btn" onclick="closeModal('logPurchaseModal')">Cancel</button>
                                <button type="submit" class="save-btn">
                                    <i data-lucide="check"></i> Save Purchases
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Hidden template for one purchase row — cloned by JS via addPurchaseRow() --}}
                <template id="purchaseRowTemplate">
                    <div class="purchase-row">
                        <div class="pr-row-top">
                            <div class="pm-add-field">
                                <label>Material</label>
                                <select name="project_material_id[]" class="purchase-row-bom-select" onchange="prefillPurchaseRow(this)">
                                    <option value="" disabled selected hidden>Select material...</option>
                                    @foreach($activeMats as $mat)
                                    <option value="{{ $mat->id }}"
                                            data-name="{{ $mat->material_name }}"
                                            data-unit="{{ $mat->unit }}"
                                            data-cost="{{ $mat->price_per_unit }}"
                                            data-planned="{{ $mat->quantity }}"
                                            data-bought="{{ isset($purchases) ? $purchases->where('project_material_id', $mat->id)->sum('qty_bought') : 0 }}">
                                        {{ Str::limit($mat->material_name, 60) }}
                                    </option>
                                    @endforeach
                                    <option value="__other__">Other (type manually)</option>
                                </select>
                                <input type="hidden" name="material_name[]" class="purchase-row-name-hidden">
                                <input type="text" class="purchase-row-name-custom"
                                       placeholder="Type material name" maxlength="255"
                                       style="display:none;">
                            </div>
                            {{-- Per-row override: blank row_supplier[] = use the general supplier --}}
                            <div class="pm-add-field pr-f-supplier">
                                <label>Supplier</label>
                                <select class="purchase-row-supplier-select" onchange="onRowSupplierChange(this)">
                                    <option value="" selected>Default</option>
                                    @foreach($suppliers ?? [] as $sup)
                                    <option value="{{ $sup->name }}">{{ $sup->name }}</option>
                                    @endforeach
                                    <option value="__other__">Other (type manually)</option>
                                </select>
                                <input type="text" name="row_supplier[]" class="purchase-row-supplier"
                                       placeholder="Type supplier name" maxlength="255"
                                       style="display:none;">
                            </div>
                            <button type="button" class="remove-purchase-row-btn" onclick="removePurchaseRow(this)" title="Remove row" aria-label="Remove row">
                                <i data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="pr-row-nums">
                            <div class="pm-add-field">
                                <label>Unit</label>
                                <input type="text" name="unit[]" class="purchase-row-unit" placeholder="pcs" maxlength="50">
                            </div>
                            {{-- BOM quantity for comparison only — no name, so it's never submitted --}}
                            <div class="pm-add-field">
                                <label>Planned</label>
                                <input type="text" class="purchase-row-planned" readonly tabindex="-1" value="—" aria-label="Planned quantity">
                                <span class="pr-note purchase-row-planned-note"></span>
                            </div>
                            <div class="pm-add-field">
                                <label>Qty</label>
                                <input type="text" inputmode="decimal" name="qty_bought[]" class="purchase-row-qty" value="1" required oninput="formatMoneyInput(this); calcAllPurchaseTotals();">
                            </div>
                            <div class="pm-add-field">
                                <label>Unit Cost (₱)</label>
                                <input type="text" inputmode="decimal" name="actual_unit_cost[]" class="purchase-row-cost" value="0" required oninput="formatMoneyInput(this); calcAllPurchaseTotals();">
                            </div>
                            <div class="pm-add-field pr-f-total">
                                <label>Row Total</label>
                                <input type="text" class="purchase-row-total" readonly tabindex="-1" value="&#x20B1;0.00">
                            </div>
                        </div>
                    </div>
                </template>
                </div>{{-- end logPurchaseModal overlay --}}

                {{-- Purchase History — separate card --}}
                @if($purchases->isNotEmpty())
                <div class="pm-card" style="margin-bottom:16px;">
                    <div class="pm-card-header">
                        <div>
                            <div class="pm-card-title" style="display:flex;align-items:center;gap:8px;">
                                <i data-lucide="receipt" style="width:15px;height:15px;color:var(--muted);"></i>
                                Purchase History
                            </div>
                            <div class="pm-card-sub">All individual purchase transactions logged</div>
                        </div>
                        <span style="font-size:12px;font-weight:700;color:var(--muted);">{{ $purchases->count() }} transaction{{ $purchases->count() !== 1 ? 's' : '' }}</span>
                    </div>
                    <div class="table-wrapper">
                        <table class="pm-table">
                            <thead>
                                <tr>
                                    <th style="text-align:left;">Material</th>
                                    <th class="ctr">Qty</th>
                                    <th class="ctr">Unit Cost (&#x20B1;)</th>
                                    <th class="ctr">Total Paid (&#x20B1;)</th>
                                    <th class="ctr">Supplier</th>
                                    <th class="ctr">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchases as $pur)
                                <tr>
                                    <td style="font-size:13px;font-weight:700;color:var(--dark);">
                                        {{ $pur->material_name }}
                                        @if($pur->fund_transaction_id)
                                        <span title="Part of this purchase was more than the money received, and was covered by the revolving fund"
                                              style="display:inline-flex;align-items:center;gap:4px;margin-left:6px;font-size:10.5px;font-weight:800;color:#8A6100;background:#FFF3D6;border-radius:999px;padding:2px 8px;vertical-align:1px;">
                                            <i data-lucide="wallet" style="width:11px;height:11px;"></i> Revolving fund
                                        </span>
                                        @endif
                                    </td>
                                    <td class="ctr" style="font-size:13px;font-weight:700;color:var(--dark);">{{ number_format($pur->qty_bought, 0) }}</td>
                                    <td class="ctr" style="font-size:13px;font-weight:400;color:var(--dark);">&#x20B1;{{ number_format($pur->actual_unit_cost, 2) }}</td>
                                    <td class="ctr"><strong style="font-size:13px;font-weight:700;color:#16a34a;">&#x20B1;{{ number_format($pur->total_paid, 2) }}</strong></td>
                                    <td class="ctr">
                                        @if($pur->supplier)
                                        <span class="client-pill" style="font-size:11px;padding:2px 9px;">{{ $pur->supplier }}</span>
                                        @else
                                        <span style="font-size:13px;color:var(--muted);">—</span>
                                        @endif
                                    </td>
                                    <td class="ctr" style="white-space:nowrap;font-size:12.5px;font-weight:400;color:var(--muted);">{{ $pur->purchase_date->format('M d, Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

            </div>

            {{-- TAB 3: VARIANCE --}}
            <div class="pm-tab-content" id="tab-variance">
                <div class="pm-card">
                    <div class="pm-card-header">
                        <div>
                            <div class="pm-card-title">Variance summary</div>
                            <div class="pm-card-sub">BOM budgeted cost vs actual purchases &middot; auto-updates KPI</div>
                        </div>
                        @if($activeMats->isNotEmpty())
                        <span style="font-size:12px;font-weight:700;color:var(--muted);">{{ $activeMats->count() }} material{{ $activeMats->count() !== 1 ? 's' : '' }}</span>
                        @endif
                    </div>
                    <div class="table-wrapper">
                        <table class="pm-table" style="table-layout:fixed;width:100%;">
                            <colgroup>
                                <col style="width:22%;">
                                <col style="width:15%;">
                                <col style="width:15%;">
                                <col style="width:14%;">
                                <col style="width:15%;">
                                <col style="width:19%;">
                            </colgroup>
                            <thead>
                                <tr style="background:var(--cream-soft);">
                                    <th style="text-align:left;padding:10px 12px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Material</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">BOM Budget (₱)</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Actual Spent (₱)</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Variance (₱)</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Status</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Budget Used</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalBudget = 0; $totalSpent = 0; @endphp
                                @forelse($activeMats as $mat)
                                @php
                                    $budgeted    = round((float)$mat->total_cost * (1 + ((float)($mat->factor ?? 0)) / 100), 2);
                                    $spent       = isset($purchases) ? $purchases->where('project_material_id', $mat->id)->sum('total_paid') : 0;
                                    $variance    = $budgeted - $spent;
                                    $usedPct     = $budgeted > 0 ? min(100, round(($spent / $budgeted) * 100)) : 0;
                                    $totalBudget += $budgeted;
                                    $totalSpent  += $spent;
                                @endphp
                                <tr style="border-bottom:1px solid var(--border);">
                                    <td style="padding:12px 12px;"><strong style="font-size:13px;font-weight:700;color:var(--dark);">{{ $mat->material_name }}</strong></td>
                                    <td style="text-align:center;padding:12px 8px;font-size:13px;font-weight:400;color:var(--dark);">₱{{ number_format($budgeted, 2) }}</td>
                                    <td style="text-align:center;padding:12px 8px;font-size:13px;font-weight:400;color:var(--dark);">@if($spent > 0)₱{{ number_format($spent, 2) }}@else<span style="color:var(--muted);">—</span>@endif</td>
                                    <td style="text-align:center;padding:12px 8px;font-size:13px;color:{{ $variance >= 0 ? '#16a34a' : '#ef4444' }};font-weight:800;">
                                        @if($spent > 0){{ $variance >= 0 ? '+' : '-' }}₱{{ number_format(abs($variance), 2) }}@else<span style="color:var(--muted);">—</span>@endif
                                    </td>
                                    <td style="text-align:center;padding:12px 8px;">
                                        @if($spent == 0)
                                        <span class="pm-status pending">Not purchased</span>
                                        @elseif($variance >= 0)
                                        <span class="pm-status purchased">Under budget</span>
                                        @else
                                        <span class="pm-status over">Over budget</span>
                                        @endif
                                    </td>
                                    <td style="text-align:center;padding:12px 8px;">
                                        <div style="display:flex;align-items:center;gap:6px;justify-content:center;">
                                            <div style="width:60px;height:5px;background:var(--cream-deep);border-radius:999px;overflow:hidden;">
                                                <div style="height:100%;width:{{ $usedPct }}%;background:{{ $usedPct <= 100 ? '#16a34a' : '#ef4444' }};border-radius:999px;"></div>
                                            </div>
                                            <span style="font-size:12px;font-weight:800;min-width:28px;">{{ $usedPct }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" style="text-align:center;padding:40px;font-size:13px;font-weight:400;color:var(--muted);">No BOM materials yet.</td></tr>
                                @endforelse
                            </tbody>
                            @if($activeMats->isNotEmpty())
                            @php $totalVariance = $totalBudget - $totalSpent; @endphp
                            <tfoot>
                                <tr style="background:var(--cream-soft);border-top:2px solid var(--border);">
                                    <td style="padding:12px 14px;color:var(--dark);font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Total</td>
                                    <td style="padding:12px 8px;text-align:center;font-size:13px;color:var(--dark);font-weight:900;">&#x20B1;{{ number_format($totalBudget, 2) }}</td>
                                    <td style="padding:12px 8px;text-align:center;font-size:13px;color:var(--dark);font-weight:900;">&#x20B1;{{ number_format($totalSpent, 2) }}</td>
                                    <td style="padding:12px 8px;text-align:center;font-size:13px;color:{{ $totalVariance >= 0 ? '#16a34a' : '#ef4444' }};font-weight:900;">
                                        {{ ($totalVariance >= 0 ? '+' : '-') }}&#x20B1;{{ number_format(abs($totalVariance), 2) }}
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            {{-- TAB 4: MATERIAL USAGE --}}
            <div class="pm-tab-content" id="tab-usage">
                @php
                    $activeUsageEntries = $usageEntries->where('status', 'active');
                    $usageGrouped       = $activeUsageEntries->groupBy('material_name');
                    $usagePlannedNames  = $plannedMaterials->pluck('material_name');
                    $usageExtraNames    = $usageGrouped->keys()->diff($usagePlannedNames);
                    $allUsageRows       = $usagePlannedNames->concat($usageExtraNames)->unique()->values();
                    // Materials with purchased stock first — the ones actually usable
                    // right now shouldn't be buried under a list of "No Stock" rows.
                    $allUsageRows       = $allUsageRows->sortByDesc(function ($matName) use ($plannedMaterials, $purchases) {
                        $bomMat = $plannedMaterials->firstWhere('material_name', $matName);
                        $bomId  = $bomMat->id ?? null;
                        return $bomId && isset($purchases) ? $purchases->where('project_material_id', $bomId)->sum('qty_bought') : 0;
                    })->values();
                @endphp

                {{-- Summary table (full width; view only — employees log usage from their portal). Same layout as the
                     Purchased materials summary: one table, sticky header and TOTAL row, as tall as its rows. --}}
                <div class="pm-card pm-purchased-card" style="margin-bottom:16px;min-width:0;overflow:hidden;">
                    <div class="pm-card-header pm-purchased-header">
                        <div>
                            <div class="pm-card-title">Material usage &mdash; summary</div>
                            <div class="pm-card-sub">Aggregated usage per material &middot; logged by employees</div>
                        </div>
                    </div>

                    <div class="pm-purchased-scroll">
                        <table class="pm-purchased-table">
                            <colgroup>
                                {{-- same split as the other tables: Material a little wider, the rest even --}}
                                <col style="width:22%;">
                                <col style="width:15.6%;">
                                <col style="width:15.6%;">
                                <col style="width:15.6%;">
                                <col style="width:15.6%;">
                                <col style="width:15.6%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th style="text-align:left;padding-left:14px;">Material</th>
                                    <th>Unit</th>
                                    <th>Stock (Bought)</th>
                                    <th>Qty Used</th>
                                    <th>Remaining</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allUsageRows as $matName)
                                @php
                                    $entries     = $usageGrouped->get($matName);
                                    $bomMat      = $plannedMaterials->firstWhere('material_name', $matName);
                                    $totalUsed   = $entries ? $entries->sum('quantity_used') : 0;
                                    $unit        = $entries ? $entries->first()->unit : ($bomMat->unit ?? null);
                                    $bomId       = $entries ? $entries->first()->project_material_id : ($bomMat->id ?? null);
                                    $stock       = isset($purchases) ? $purchases->where('project_material_id', $bomId)->sum('qty_bought') : 0;
                                    $remaining   = $stock - $totalUsed;
                                    $remainColor = $remaining < 0 ? '#b91c1c' : ($remaining == 0 ? '#92400e' : '#15803d');

                                    $remainPct = $stock > 0 ? max(0, min(100, ($remaining / $stock) * 100)) : null;
                                    if ($stock <= 0) {
                                        $statusLabel = 'No Stock';   $statusColor = '#9ca3af';
                                    } elseif ($remaining <= 0) {
                                        $statusLabel = 'Out of Stock'; $statusColor = '#dc2626';
                                    } elseif ($remainPct <= 25) {
                                        $statusLabel = 'Low Stock';  $statusColor = '#f59e0b';
                                    } else {
                                        $statusLabel = 'In Stock';   $statusColor = '#16a34a';
                                    }
                                @endphp
                                <tr>
                                    <td style="text-align:left;padding-left:14px;">
                                        <strong style="font-size:13px;font-weight:700;color:var(--dark);">{{ $matName }}</strong>
                                    </td>
                                    <td style="font-size:12px;color:var(--muted);">{{ $unit ?: '—' }}</td>
                                    <td>{{ $stock > 0 ? number_format($stock, 0) : '—' }}</td>
                                    <td style="font-weight:700;">{{ number_format($totalUsed, 0) }}</td>
                                    <td style="font-weight:700;color:{{ $remainColor }};">{{ $stock > 0 ? number_format($remaining, 0) : '—' }}</td>
                                    <td>
                                        @if($remainPct !== null)
                                        <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
                                            <div style="display:flex;align-items:center;gap:6px;justify-content:center;">
                                                <div style="width:42px;height:5px;background:var(--cream-deep);border-radius:999px;overflow:hidden;flex-shrink:0;">
                                                    <div style="height:100%;width:{{ $remainPct }}%;background:{{ $statusColor }};border-radius:999px;"></div>
                                                </div>
                                                <span style="font-weight:800;font-size:12px;color:{{ $statusColor }};min-width:30px;text-align:left;">{{ round($remainPct) }}%</span>
                                            </div>
                                            <span style="font-weight:700;font-size:10px;color:{{ $statusColor }};white-space:nowrap;text-transform:uppercase;letter-spacing:.03em;">{{ $statusLabel }}</span>
                                        </div>
                                        @else
                                        <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.03em;background:#f3f4f6;color:#9ca3af;">{{ $statusLabel }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" style="padding:48px 20px;color:var(--muted);">
                                        <i data-lucide="activity" style="width:32px;height:32px;color:var(--border);display:block;margin:0 auto 10px;"></i>
                                        No materials planned or used yet.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($allUsageRows->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <td style="text-align:left;padding-left:14px;font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Total</td>
                                    <td></td>
                                    <td style="font-weight:900;">{{ isset($purchases) && $purchases->isNotEmpty() ? number_format($purchases->sum('qty_bought'), 0) : '—' }}</td>
                                    <td style="font-weight:900;">{{ number_format($activeUsageEntries->sum('quantity_used'), 0) }}</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>{{-- end summary card --}}

                {{-- Usage History — separate card --}}
                @if($usageEntries->isNotEmpty())
                <div class="pm-card" style="margin-bottom:16px;">
                    <div class="pm-card-header">
                        <div>
                            <div class="pm-card-title" style="display:flex;align-items:center;gap:8px;">
                                <i data-lucide="clock" style="width:15px;height:15px;color:var(--muted);"></i>
                                Usage History
                            </div>
                            <div class="pm-card-sub">All individual material usage entries logged by employees</div>
                        </div>
                        <span style="font-size:12px;font-weight:700;color:var(--muted);">{{ $usageEntries->count() }} entr{{ $usageEntries->count() !== 1 ? 'ies' : 'y' }}</span>
                    </div>
                    <div class="table-wrapper">
                        <table class="pm-table">
                            <thead>
                                <tr>
                                    <th style="text-align:left;">Material</th>
                                    <th class="ctr">Qty Used</th>
                                    <th class="ctr">Unit</th>
                                    <th class="ctr">Date</th>
                                    <th class="ctr">Logged By</th>
                                    <th class="ctr">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($usageEntries as $entry)
                                <tr style="{{ $entry->status === 'archived' ? 'opacity:.5;' : '' }}">
                                    <td style="font-size:13px;font-weight:700;color:var(--dark);">
                                        {{ $entry->material_name }}
                                        @if($entry->notes)
                                        <div style="font-size:11.5px;font-weight:400;color:var(--muted);margin-top:2px;">{{ Str::limit($entry->notes, 50) }}</div>
                                        @endif
                                    </td>
                                    <td class="ctr" style="font-size:13px;font-weight:700;color:var(--dark);">{{ number_format($entry->quantity_used, 0) }}</td>
                                    <td class="ctr" style="font-size:12px;font-weight:400;color:var(--muted);">{{ $entry->unit ?? '—' }}</td>
                                    <td class="ctr" style="white-space:nowrap;font-size:12.5px;font-weight:400;color:var(--muted);">
                                        {{ $entry->used_date ? \Carbon\Carbon::parse($entry->used_date)->format('M d, Y') : '—' }}
                                    </td>
                                    <td class="ctr" style="font-size:12.5px;font-weight:400;color:var(--muted);">{{ $entry->recorded_by ?? '—' }}</td>
                                    <td class="ctr">
                                        <span class="pm-status {{ $entry->status === 'archived' ? 'pending' : 'purchased' }}">
                                            {{ ucfirst($entry->status ?? 'active') }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>

        </main>
    </div>

    @php
        // BOM materials offered by the "Add Material" picker, flagged when something was already bought for them
        $purchasePickMats = $activeMats->map(function ($m) use ($purchasesByName) {
            $g = $purchasesByName->get($m->material_name);
            return [
                'id'        => $m->id,
                'name'      => $m->material_name,
                'unit'      => $m->unit,
                'cost'      => $m->price_per_unit,
                'planned'   => $m->quantity,
                'purchased' => $g && $g->sum('qty_bought') > 0,
            ];
        })->values();
    @endphp
    {{-- ===================== PICK PURCHASED MATERIALS (checkbox list, like Build Quotation) ===================== --}}
    <div class="modal-overlay" id="pickPurchaseModal" style="z-index:600;">
        <div class="modal-card" style="max-width:640px;width:95%;max-height:88vh;display:flex;flex-direction:column;overflow:hidden;">
            <div class="pp-head">
                <div>
                    <div class="pp-title">Add Materials</div>
                    <div class="pp-sub">Tick every material you bought — you can set quantities and costs afterwards.</div>
                </div>
                <button type="button" class="modal-close" id="closePickPurchase" aria-label="Close">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="pp-toolbar">
                <div class="pp-search">
                    <i data-lucide="search"></i>
                    <input type="search" id="pickPurchaseSearch" placeholder="Search materials..." autocomplete="off">
                </div>
                <span class="pp-count" id="pickPurchaseCount">0 selected</span>
            </div>

            <div class="pp-list" id="pickPurchaseList"></div>

            <div class="pp-foot">
                <button type="button" class="pp-custom" id="pickPurchaseCustom">
                    <i data-lucide="plus"></i>
                    Not in the list? Add a custom row
                </button>
                <div class="pp-actions">
                    <button type="button" class="cancel-btn" id="cancelPickPurchase">Cancel</button>
                    <button type="button" class="save-btn" id="confirmPickPurchase" disabled>
                        <i data-lucide="check"></i>
                        Add Selected
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        const ACTIVE_TAB = "{{ session('active_tab', 'bom') }}";
        if (typeof lucide !== 'undefined') lucide.createIcons();

        function openModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
        }
        function closeModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
        }

        document.getElementById('openLogPurchaseModal')?.addEventListener('click', function () { openModal('logPurchaseModal'); });
        document.getElementById('closeLogPurchaseModal')?.addEventListener('click', function () { closeModal('logPurchaseModal'); });
        document.getElementById('logPurchaseModal')?.addEventListener('click', function (e) {
            if (e.target === this) closeModal('logPurchaseModal');
        });

        document.querySelectorAll('.pm-tab').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.tab === ACTIVE_TAB);
            btn.addEventListener('click', function() {
                document.querySelectorAll('.pm-tab').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.pm-tab-content').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('tab-' + this.dataset.tab).classList.add('active');
            });
        });
        document.querySelectorAll('.pm-tab-content').forEach(p => {
            p.classList.toggle('active', p.id === 'tab-' + ACTIVE_TAB);
        });

        // ---- Purchased materials: status filter chips + search ----
        (function () {
            var table = document.getElementById('muPurchasedTable');
            var chips = document.querySelectorAll('#muPurchasedChips .mu-chip');
            var input = document.getElementById('muPurchasedSearch');
            if (!table || !chips.length) return;
            var filter = '';
            function apply() {
                var q = (input && input.value || '').toLowerCase().trim(), shown = 0;
                table.querySelectorAll('tbody tr[data-status]').forEach(function (row) {
                    var ok = (!filter || row.dataset.status === filter) && (!q || row.dataset.search.indexOf(q) !== -1);
                    row.style.display = ok ? '' : 'none';
                    if (ok) shown++;
                });
                document.getElementById('muPurchasedNoMatch').style.display = shown ? 'none' : '';
            }
            chips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    chips.forEach(function (c) { c.classList.toggle('active', c === chip); });
                    filter = chip.dataset.filter;
                    apply();
                });
            });
            if (input) input.addEventListener('input', apply);
        })();

        // ---- Multi-row material purchase log ----

        // ---- Add Material: pick several BOM materials with checkboxes (like Build Quotation) ----
        var PURCHASE_PICK_MATS = @json($purchasePickMats);
        var ppSelected = {};

        function ppEsc(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }

        // BOM materials already sitting in the purchase list (each can only be added once per list)
        function ppUsedIds() {
            return Array.prototype.map.call(document.querySelectorAll('#purchaseRowsContainer .purchase-row-bom-select'), function (s) { return String(s.value); })
                .filter(function (v) { return v && v !== '__other__'; });
        }

        function ppSelectedIds() { return Object.keys(ppSelected).filter(function (id) { return ppSelected[id]; }); }

        function ppUpdateCount() {
            var used = ppUsedIds();
            var n = ppSelectedIds().filter(function (id) { return used.indexOf(id) === -1; }).length;   // rows already in the list never count
            document.getElementById('pickPurchaseCount').textContent = n + ' selected';
            var btn = document.getElementById('confirmPickPurchase');
            btn.disabled = n === 0;
            btn.lastChild.textContent = n > 0 ? ' Add ' + n + ' Selected' : ' Add Selected';
        }

        function renderPurchasePicker() {
            var list   = document.getElementById('pickPurchaseList');
            var scroll = list.scrollTop;
            var q      = document.getElementById('pickPurchaseSearch').value.toLowerCase().trim();
            var used   = ppUsedIds();
            var html   = '';

            [['Not purchased yet', false], ['Already purchased — buy more', true]].forEach(function (grp) {
                var items = PURCHASE_PICK_MATS.filter(function (m) { return !!m.purchased === grp[1] && (!q || m.name.toLowerCase().indexOf(q) !== -1); });
                if (!items.length) return;
                var available = items.filter(function (m) { return used.indexOf(String(m.id)) === -1; });
                var checked   = available.filter(function (m) { return ppSelected[m.id]; }).length;

                html += '<div class="pp-group">' +
                    '<label class="pp-cat">' +
                        '<input type="checkbox" class="pp-cat-check"' + (available.length && checked === available.length ? ' checked' : '') + (available.length ? '' : ' disabled') + '>' +
                        '<span>' + ppEsc(grp[0]) + '</span>' +
                        '<em>' + (checked ? checked + ' of ' : '') + available.length + ' available</em>' +
                    '</label>';
                items.forEach(function (m) {
                    var isUsed = used.indexOf(String(m.id)) !== -1;
                    html += '<label class="pp-item' + (isUsed ? ' is-used' : '') + '">' +
                        '<input type="checkbox" class="pp-check" value="' + m.id + '"' + (isUsed ? ' disabled' : (ppSelected[m.id] ? ' checked' : '')) + '>' +
                        '<span class="pp-name">' + ppEsc(m.name) + '</span>' +
                        (isUsed
                            ? '<span class="pp-badge">Already added</span>'
                            : '<span class="pp-meta">' + (m.planned != null ? 'Planned ' + Math.round(m.planned) + ' ' : '') + ppEsc(m.unit || '') + '</span>') +
                    '</label>';
                });
                html += '</div>';
            });

            list.innerHTML = html || '<div class="pp-empty">No materials match. Use “Add a custom row” below to type one.</div>';
            list.scrollTop = scroll;
            ppUpdateCount();
        }

        function openPurchasePicker() {
            ppSelected = {};
            document.getElementById('pickPurchaseSearch').value = '';
            renderPurchasePicker();
            openModal('pickPurchaseModal');
            document.getElementById('pickPurchaseSearch').focus();
        }

        // closing the picker must not unlock the page: the Log New Purchase dialog is still open underneath
        function closePurchasePicker() {
            closeModal('pickPurchaseModal');
            document.body.style.overflow = 'hidden';
        }

        document.getElementById('pickPurchaseList').addEventListener('change', function (e) {
            var t = e.target;
            if (t.classList.contains('pp-check')) {
                ppSelected[t.value] = t.checked;
            } else if (t.classList.contains('pp-cat-check')) {
                t.closest('.pp-group').querySelectorAll('.pp-check:not(:disabled)').forEach(function (box) { ppSelected[box.value] = t.checked; });
            } else { return; }
            renderPurchasePicker();
        });
        document.getElementById('pickPurchaseSearch').addEventListener('input', renderPurchasePicker);
        document.getElementById('closePickPurchase').addEventListener('click', closePurchasePicker);
        document.getElementById('cancelPickPurchase').addEventListener('click', closePurchasePicker);
        document.getElementById('pickPurchaseModal').addEventListener('click', function (e) { if (e.target === this) closePurchasePicker(); });
        document.getElementById('pickPurchaseCustom').addEventListener('click', function () {
            closePurchasePicker();
            addPurchaseRow();
        });
        document.getElementById('confirmPickPurchase').addEventListener('click', function () {
            var used = ppUsedIds();
            var chosen = PURCHASE_PICK_MATS.filter(function (m) { return ppSelected[m.id] && used.indexOf(String(m.id)) === -1; });
            closePurchasePicker();
            chosen.forEach(function (m) { addPurchaseRow({ id: m.id, cost: m.cost }); });
        });
        var PURCHASE_EMPTY_HTML = document.getElementById('purchaseRowsEmpty').innerHTML;

        function addPurchaseRow(prefill) {
            var tpl       = document.getElementById('purchaseRowTemplate');
            var container = document.getElementById('purchaseRowsContainer');
            var row       = tpl.content.cloneNode(true).firstElementChild;
            container.appendChild(row);
            refreshRowSupplierDefaults();

            if (prefill) {
                var sel = row.querySelector('.purchase-row-bom-select');
                sel.value = prefill.id;
                prefillPurchaseRow(sel);
                if (prefill.cost) {
                    var costInput = row.querySelector('.purchase-row-cost');
                    costInput.value = prefill.cost;
                    formatMoneyInput(costInput);
                }
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();
            calcAllPurchaseTotals();
            updatePurchaseEmptyState();
            return row;
        }

        function removePurchaseRow(btn) {
            var row = btn.closest('.purchase-row');
            if (row) row.remove();
            calcAllPurchaseTotals();
            updatePurchaseEmptyState();
        }

        // Nothing but the "Add Material" message until the first row is added.
        function updatePurchaseEmptyState() {
            var hasRows = document.querySelector('#purchaseRowsContainer .purchase-row') !== null;
            document.getElementById('purchaseRowsEmpty').style.display  = hasRows ? 'none' : '';
            document.getElementById('purchaseRowCount').textContent = document.querySelectorAll('#purchaseRowsContainer .purchase-row').length;
            // reset the message (a failed save turns it into a red warning)
            var empty = document.getElementById('purchaseRowsEmpty');
            empty.innerHTML = PURCHASE_EMPTY_HTML;
            empty.style.borderColor = '';
            empty.style.color = '';
        }

        function prefillPurchaseRow(sel) {
            var row      = sel.closest('.purchase-row');
            var opt      = sel.options[sel.selectedIndex];
            var custom   = row.querySelector('.purchase-row-name-custom');
            var hidden   = row.querySelector('.purchase-row-name-hidden');
            var unitInput = row.querySelector('.purchase-row-unit');
            var isOther  = (sel.value === '__other__' || sel.value === '');

            // what the BOM planned for this material, and what earlier purchases already covered
            row.dataset.planned = isOther ? '' : (opt.dataset.planned || '');
            row.dataset.bought  = isOther ? '' : (opt.dataset.bought || '0');
            row.querySelector('.purchase-row-planned').value = row.dataset.planned === '' ? '—' : prNum(row.dataset.planned);

            if (isOther) {
                custom.style.display = '';
                custom.value = '';
                custom.required = true;
                custom.oninput = function () { hidden.value = custom.value; };
                hidden.value = '';
                custom.focus();
                sel.value = '__other__';
            } else {
                custom.style.display = 'none';
                custom.required = false;
                custom.value = '';
                hidden.value = opt.dataset.name || '';
                if (unitInput) unitInput.value = opt.dataset.unit || '';
                if (opt.dataset.cost) {
                    var costInput = row.querySelector('.purchase-row-cost');
                    costInput.value = opt.dataset.cost;
                    formatMoneyInput(costInput);
                }
            }
            calcAllPurchaseTotals();
        }

        function prNum(n) { return Number(n).toLocaleString('en-PH', { maximumFractionDigits: 2 }); }

        // Under Planned: what's already bought, or how far this purchase goes past the plan
        function updatePlannedNote(row, qty) {
            var note = row.querySelector('.purchase-row-planned-note');
            if (row.dataset.planned === undefined || row.dataset.planned === '') { note.textContent = ''; note.classList.remove('is-over'); return; }
            var planned = parseFloat(row.dataset.planned) || 0;
            var bought  = parseFloat(row.dataset.bought) || 0;
            var over    = Math.round((bought + qty - planned) * 100) / 100;
            note.classList.toggle('is-over', over > 0);
            note.textContent = over > 0 ? prNum(over) + ' over plan' : (bought > 0 ? prNum(bought) + ' bought' : '');
        }

        // Live thousand-separator formatting for money/quantity text inputs.
        function formatMoneyInput(el) {
            var raw = el.value.replace(/[^0-9.]/g, '');
            var parts = raw.split('.');
            var intPart = parts[0].replace(/^0+(?=\d)/, '');
            var formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            var decPart = parts.length > 1 ? '.' + parts.slice(1).join('').slice(0, 2) : '';
            el.value = formattedInt + decPart;
        }

        function calcAllPurchaseTotals() {
            var grand = 0;
            document.querySelectorAll('#purchaseRowsContainer .purchase-row').forEach(function (row) {
                var qty   = parseFloat(row.querySelector('.purchase-row-qty').value.replace(/,/g, '')) || 0;
                var cost  = parseFloat(row.querySelector('.purchase-row-cost').value.replace(/,/g, '')) || 0;
                var total = qty * cost;
                grand += total;
                row.querySelector('.purchase-row-total').value = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
                updatePlannedNote(row, qty);
            });
            document.getElementById('purchaseGrandTotal').value = '₱' + grand.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
            updatePurchaseFundsStatus(grand);
        }

        // ---- Total Received check (same rule as ProjectFunds on the server) ----
        var PROJECT_FUNDS = @json($projectFunds);
        var FUND_BALANCE  = {{ (float) $fundBalance }};
        var PROJECT_NAME  = @json($project->name);
        var CLIENT_NAME   = @json($project->live_client_name);

        function purchaseGrand() {
            var grand = 0;
            document.querySelectorAll('#purchaseRowsContainer .purchase-row').forEach(function (row) {
                var qty  = parseFloat(row.querySelector('.purchase-row-qty').value.replace(/,/g, '')) || 0;
                var cost = parseFloat(row.querySelector('.purchase-row-cost').value.replace(/,/g, '')) || 0;
                grand += Math.round(qty * cost * 100) / 100;
            });
            return Math.round(grand * 100) / 100;
        }
        // Only the new purchase is ever covered — an older shortfall isn't added on top
        function purchaseExceeded(grand) {
            if (grand <= 0) return 0;
            return Math.round(Math.max(0, Math.min(grand, grand - PROJECT_FUNDS.available)) * 100) / 100;
        }
        function updatePurchaseFundsStatus(grand) {
            var box = document.getElementById('pfStatus');
            if (!box) return;
            var over = purchaseExceeded(grand);
            box.hidden = over <= 0;
            if (over > 0) box.textContent = 'This purchase is ' + FundOverrun.peso(over) + ' more than the money available. You can cover it from the revolving fund when you save.';
        }

        var logPurchaseForm = document.getElementById('logPurchaseForm');
        if (logPurchaseForm) {
            logPurchaseForm.addEventListener('submit', function (e) {
                var rows = document.querySelectorAll('#purchaseRowsContainer .purchase-row');
                if (!rows.length) {
                    e.preventDefault();
                    var empty = document.getElementById('purchaseRowsEmpty');
                    empty.style.borderColor = '#B42318';
                    empty.style.color = '#B42318';
                    empty.textContent = 'Add at least one material before saving.';
                    return;
                }

                // Costs more than the project has received → ask before saving
                var grand = purchaseGrand();
                var over  = purchaseExceeded(grand);
                var useFund = document.getElementById('purchaseUseFund');
                if (over > 0 && useFund.value !== '1') {
                    e.preventDefault();
                    FundOverrun.open({
                        projects: [{ project: PROJECT_NAME, client: CLIENT_NAME, amount: grand, available: Math.max(0, PROJECT_FUNDS.available), exceeded: over }],
                        total: over,
                        fund_balance: FUND_BALANCE,
                        fund_enough: FUND_BALANCE + 0.005 >= over
                    }, {
                        amountLabel: 'This purchase',
                        onUse: function () { useFund.value = '1'; logPurchaseForm.requestSubmit(); }
                    });
                    return;
                }

                rows.forEach(function (row) {
                    ['purchase-row-qty', 'purchase-row-cost'].forEach(function (cls) {
                        var el = row.querySelector('.' + cls);
                        if (el) el.value = el.value.replace(/,/g, '');
                    });
                });
            });
        }

        // The purchase log starts empty — just the "Add Material" message until a row is added.
        document.addEventListener('DOMContentLoaded', function () {
            updatePurchaseEmptyState();
        });

        // ---- Supplier: one general supplier for the log, overridable per row ----
        // General dropdown mirrors into the supplier input (shown only for "Other").
        function onSupplierChange(sel) {
            var custom = document.getElementById('supplierCustom');
            if (sel.value === '__other__') {
                custom.style.display = '';
                custom.value = '';
                custom.focus();
            } else {
                custom.style.display = 'none';
                custom.value = sel.value;
            }
            refreshRowSupplierDefaults();
        }

        // Row dropdown mirrors into row_supplier[]; blank means "use the general supplier".
        function onRowSupplierChange(sel) {
            var custom = sel.closest('.purchase-row').querySelector('.purchase-row-supplier');
            if (sel.value === '__other__') {
                custom.style.display = '';
                custom.value = '';
                custom.focus();
            } else {
                custom.style.display = 'none';
                custom.value = sel.value;
            }
        }

        // Show which supplier "Default" currently means, e.g. "Default (Emc7 Hardware)".
        function refreshRowSupplierDefaults() {
            var name  = (document.getElementById('supplierCustom').value || '').trim();
            var label = name ? 'Default (' + name + ')' : 'Default';
            document.querySelectorAll('#purchaseRowsContainer .purchase-row-supplier-select').forEach(function (sel) {
                sel.options[0].textContent = label;
            });
        }

    </script>

</body>
</html>
