<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project->name }} — Materials | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('admin.material_usage') }}" style="color:var(--muted);text-decoration:none;font-weight:600;">Materials</a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="font-weight:700;color:var(--dark);">{{ $project->name }}</span>
            </div>

            <div class="page-header">
                <div>
                    <h1>
                        {{ $project->name }}
                    </h1>
                    <p><span class="client-pill">{{ $project->client }}</span> &nbsp;&middot;&nbsp; {{ ucfirst(str_replace('_',' ',$project->current_phase ?? 'Planning')) }} phase</p>
                </div>
            </div>

            @if(session('success'))
            <div class="alert-banner success"><i data-lucide="check-circle"></i> {{ session('success') }}</div>
            @endif
            @if(session('error'))
            <div class="alert-banner error"><i data-lucide="alert-circle"></i> {{ session('error') }}</div>
            @endif

            {{-- Tabs --}}
            <div class="pm-tabs">
                <button class="pm-tab active" data-tab="bom">
                    <i data-lucide="clipboard-list" style="width:14px;height:14px;"></i>
                    BOM &mdash; planned materials
                </button>
                <button class="pm-tab" data-tab="purchased">
                    <i data-lucide="shopping-cart" style="width:14px;height:14px;"></i>
                    Purchased materials
                </button>
                <button class="pm-tab" data-tab="usage">
                    <i data-lucide="activity" style="width:14px;height:14px;"></i>
                    Material usage
                </button>
                <button class="pm-tab" data-tab="variance">
                    <i data-lucide="git-compare" style="width:14px;height:14px;"></i>
                    Variance summary
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
                    <div class="table-wrapper">
                        <table class="pm-table" style="table-layout:fixed;width:100%;">
                            <colgroup>
                                <col style="width:26%;">
                                <col style="width:8%;">
                                <col style="width:9%;">
                                <col style="width:14%;">
                                <col style="width:14%;">
                                <col style="width:16%;">
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
                                    <td style="padding:12px 12px;"><strong style="font-size:13px;color:var(--dark);">{{ $mat->material_name }}</strong></td>
                                    <td style="text-align:center;padding:12px 8px;color:var(--muted);font-size:12px;">{{ $mat->unit ?? '—' }}</td>
                                    <td style="text-align:center;padding:12px 8px;color:var(--dark);">{{ number_format($mat->quantity, 0) }}</td>
                                    <td style="text-align:center;padding:12px 8px;color:var(--dark);">₱{{ number_format($mat->price_per_unit, 2) }}</td>
                                    <td style="text-align:center;padding:12px 8px;color:var(--dark);">₱{{ number_format($base, 2) }}</td>
                                    <td style="text-align:center;padding:12px 8px;color:var(--dark);font-weight:700;">₱{{ number_format($budgeted, 2) }}</td>
                                    <td style="text-align:center;padding:12px 8px;">
                                        @if($hasPurchase)
                                        <span class="pm-status purchased">Purchased ✓</span>
                                        @else
                                        <span class="pm-status pending">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">No materials in BOM yet. Add via <strong>Project Quotations</strong>.</td></tr>
                                @endforelse
                            </tbody>
                            @if($activeMats->isNotEmpty())
                            <tfoot>
                                <tr style="background:var(--cream-soft);border-top:2px solid var(--border);">
                                    <td style="padding:12px 12px;color:var(--dark);font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Subtotal</td>
                                    <td></td><td></td><td></td>
                                    <td style="text-align:center;padding:12px 8px;color:var(--dark);font-weight:700;">₱{{ number_format($baseSubtotal, 2) }}</td>
                                    <td style="text-align:center;padding:12px 8px;color:var(--dark);font-weight:900;">₱{{ number_format($budgetedSubtotal, 2) }}</td>
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
                @php $grouped = $purchases->groupBy('material_name'); @endphp

                {{-- Summary + Form side by side --}}
                <div style="display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:16px;align-items:start;margin-bottom:16px;">

                {{-- Left: Summary table --}}
                <div class="pm-card" style="margin-bottom:0;display:flex;flex-direction:column;min-width:0;height:500px;overflow:hidden;">
                    <div class="pm-card-header">
                        <div>
                            <div class="pm-card-title">Purchased materials &mdash; summary</div>
                            <div class="pm-card-sub">Aggregated totals per material &middot; updates KPI automatically</div>
                        </div>
                        @if($purchases->isNotEmpty())
                        <span style="font-size:12px;font-weight:700;color:var(--muted);">{{ $grouped->count() }} material{{ $grouped->count() !== 1 ? 's' : '' }} &nbsp;·&nbsp; Total: <strong style="color:#16a34a;">&#x20B1;{{ number_format($totalPurchased,2) }}</strong></span>
                        @endif
                    </div>

                    @php $pcols = 'table-layout:fixed;border-collapse:collapse;width:100%;'; @endphp
                    <div style="flex:1;display:flex;flex-direction:column;min-height:0;overflow:hidden;">
                        <table style="{{ $pcols }}">
                            <colgroup>
                                <col style="width:34%;">
                                <col style="width:13%;">
                                <col style="width:16%;">
                                <col style="width:17%;">
                                <col style="width:20%;">
                            </colgroup>
                            <thead>
                                <tr style="background:var(--cream-soft);border-bottom:1px solid var(--border);">
                                    <th style="text-align:left;padding:10px 12px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Material</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Planned Qty</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Qty Bought</th>
                                    <th style="text-align:right;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Total Paid (₱)</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">VS BOM</th>
                                </tr>
                            </thead>
                        </table>
                        <div style="flex:1;overflow-y:scroll;min-height:0;">
                        <table style="{{ $pcols }}">
                            <colgroup>
                                <col style="width:34%;">
                                <col style="width:13%;">
                                <col style="width:16%;">
                                <col style="width:17%;">
                                <col style="width:20%;">
                            </colgroup>
                            <tbody>
                                @forelse($grouped as $matName => $group)
                                @php
                                    $totalQty  = $group->sum('qty_bought');
                                    $totalPaid = $group->sum('total_paid');
                                    $bomMat    = $group->first()->projectMaterial;
                                    $planned   = $bomMat?->quantity ?? null;
                                    $budgeted  = $bomMat ? round((float)$bomMat->total_cost * (1 + $materialFactor / 100), 2) : 0;
                                    $vsPct     = $budgeted > 0 ? min(150, round(($totalPaid / $budgeted) * 100)) : null;
                                    $vsColor   = $vsPct !== null ? ($vsPct <= 100 ? '#16a34a' : '#ef4444') : 'var(--muted)';
                                    $unit      = $group->first()->unit;
                                @endphp
                                <tr style="border-bottom:1px solid var(--border);">
                                    <td style="padding:12px 12px;">
                                        <strong style="font-size:13px;color:var(--dark);">{{ $matName }}</strong>
                                        @if($unit)<div style="font-size:11.5px;color:var(--muted);margin-top:2px;">{{ $unit }}</div>@endif
                                    </td>
                                    <td style="text-align:center;padding:12px 8px;color:var(--dark);">{{ $planned !== null ? number_format($planned, 0) : '—' }}</td>
                                    <td style="text-align:center;padding:12px 8px;color:var(--dark);">{{ number_format($totalQty, 0) }}</td>
                                    <td style="text-align:right;padding:12px 8px;color:var(--dark);font-weight:700;">₱{{ number_format($totalPaid, 2) }}</td>
                                    <td style="text-align:center;padding:12px 8px;">
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
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" style="text-align:center;padding:48px 20px;color:var(--muted);">
                                        <i data-lucide="shopping-cart" style="width:32px;height:32px;color:var(--border);display:block;margin:0 auto 10px;"></i>
                                        No purchases logged yet.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                    @if($purchases->isNotEmpty())
                    <div style="background:var(--cream-soft);display:grid;grid-template-columns:34fr 13fr 16fr 17fr 20fr;border-top:2px solid var(--border);">
                        <div style="padding:12px 14px;color:var(--dark);font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Total</div>
                        <div style="padding:12px 8px;text-align:center;color:var(--muted);">—</div>
                        <div style="padding:12px 8px;text-align:center;color:var(--dark);font-weight:700;">{{ number_format($purchases->sum('qty_bought'), 0) }}</div>
                        <div style="padding:12px 8px;text-align:right;color:var(--dark);font-weight:900;">&#x20B1;{{ number_format($totalPurchased, 2) }}</div>
                        <div style="padding:12px 8px;"></div>
                    </div>
                    @endif

                </div>{{-- end summary card --}}

                {{-- Right: Log New Purchase form as sidebar card --}}
                <div class="pm-card" style="margin-bottom:0;background:linear-gradient(180deg,#333333 0%,#2a2a2a 100%);border-color:transparent;display:flex;flex-direction:column;height:500px;overflow:hidden;">
                    <div class="pm-card-header" style="padding-bottom:12px;border-bottom-color:rgba(255,255,255,.1);flex-shrink:0;">
                        <div>
                            <div class="pm-card-title" style="font-size:13px;color:#fff;">Log New Purchase</div>
                            <div class="pm-card-sub" style="color:rgba(255,255,255,.45);">Record a material purchase</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.material_usage.store_purchase', $project->id) }}" style="padding:10px 16px 16px;flex:1;overflow-y:auto;" class="dark-form">
                        @csrf
                        <input type="hidden" name="material_name" id="purchaseNameHidden">
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <div class="pm-add-field">
                                <label>Material</label>
                                <select name="project_material_id" id="purchaseBomSelect" onchange="prefillPurchase(this)">
                                    <option value="" disabled selected>Select material...</option>
                                    @foreach($activeMats as $mat)
                                    <option value="{{ $mat->id }}"
                                            data-name="{{ $mat->material_name }}"
                                            data-unit="{{ $mat->unit }}"
                                            data-cost="{{ $mat->price_per_unit }}">
                                        {{ Str::limit($mat->material_name, 32) }}
                                    </option>
                                    @endforeach
                                    <option value="__other__">Other (type manually)</option>
                                </select>
                                <input type="text" id="purchaseNameCustom"
                                       placeholder="Type material name" maxlength="255"
                                       style="display:none;margin-top:6px;"
                                       oninput="document.getElementById('purchaseNameHidden').value=this.value">
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                <div class="pm-add-field">
                                    <label>Qty</label>
                                    <input type="number" name="qty_bought" id="purchaseQty" min="0.01" step="0.01" value="1" required oninput="calcPurchaseTotal()">
                                </div>
                                <div class="pm-add-field">
                                    <label>Unit Cost (&#x20B1;)</label>
                                    <input type="number" name="actual_unit_cost" id="purchaseUnitCost" min="0" step="0.01" value="0" required oninput="calcPurchaseTotal()">
                                </div>
                            </div>
                            <div class="pm-add-field">
                                <label>Total (Auto)</label>
                                <input type="text" id="purchaseTotalDisplay" readonly
                                       style="background:var(--cream-soft);cursor:default;color:#16a34a;font-weight:800;"
                                       value="&#x20B1;0.00">
                            </div>
                            <div class="pm-add-field">
                                <label>Supplier</label>
                                @if(isset($suppliers) && $suppliers->isNotEmpty())
                                <select id="supplierDropdown" onchange="onSupplierChange(this)">
                                    <option value="" disabled selected>Select supplier...</option>
                                    @foreach($suppliers as $sup)
                                    <option value="{{ $sup->name }}">{{ $sup->name }}{{ $sup->company ? ' — '.$sup->company : '' }}</option>
                                    @endforeach
                                    <option value="__other__">Other (type manually)</option>
                                </select>
                                <input type="text" name="supplier" id="supplierCustom"
                                       placeholder="Type supplier name" maxlength="255"
                                       style="display:none;margin-top:6px;">
                                @else
                                <input type="text" name="supplier" placeholder="Supplier name" maxlength="255">
                                @endif
                            </div>
                            <div class="pm-add-field">
                                <label>Date</label>
                                <input type="date" name="purchase_date" required value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <button type="submit" class="save-btn" style="width:100%;justify-content:center;height:44px;">
                                <i data-lucide="check"></i> Save Purchase
                            </button>
                        </div>
                    </form>
                </div>

                </div>{{-- end side-by-side grid --}}

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
                                    <td style="font-weight:700;">{{ $pur->material_name }}</td>
                                    <td class="ctr" style="font-weight:700;color:var(--dark);">{{ number_format($pur->qty_bought, 0) }}</td>
                                    <td class="ctr" style="color:var(--muted);">&#x20B1;{{ number_format($pur->actual_unit_cost, 2) }}</td>
                                    <td class="ctr"><strong style="color:#16a34a;">&#x20B1;{{ number_format($pur->total_paid, 2) }}</strong></td>
                                    <td class="ctr">
                                        @if($pur->supplier)
                                        <span class="client-pill" style="font-size:11px;padding:2px 9px;">{{ $pur->supplier }}</span>
                                        @else
                                        <span style="color:var(--muted-light);">—</span>
                                        @endif
                                    </td>
                                    <td class="ctr" style="white-space:nowrap;color:var(--muted);font-size:12.5px;">{{ $pur->purchase_date->format('M d, Y') }}</td>
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
                                    <td style="padding:12px 12px;"><strong style="font-size:13px;color:var(--dark);">{{ $mat->material_name }}</strong></td>
                                    <td style="text-align:center;padding:12px 8px;color:var(--dark);">₱{{ number_format($budgeted, 2) }}</td>
                                    <td style="text-align:center;padding:12px 8px;color:var(--dark);">@if($spent > 0)₱{{ number_format($spent, 2) }}@else—@endif</td>
                                    <td style="text-align:center;padding:12px 8px;color:{{ $variance >= 0 ? '#16a34a' : '#ef4444' }};font-weight:800;">
                                        @if($spent > 0){{ $variance >= 0 ? '+' : '-' }}₱{{ number_format(abs($variance), 2) }}@else—@endif
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
                                            <span style="font-size:12px;font-weight:700;min-width:28px;">{{ $usedPct }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">No BOM materials yet.</td></tr>
                                @endforelse
                            </tbody>
                            @if($activeMats->isNotEmpty())
                            @php $totalVariance = $totalBudget - $totalSpent; @endphp
                            <tfoot>
                                <tr style="background:var(--cream-soft);border-top:2px solid var(--border);">
                                    <td style="padding:12px 14px;color:var(--dark);font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Total</td>
                                    <td style="padding:12px 8px;text-align:center;color:var(--dark);font-weight:700;">&#x20B1;{{ number_format($totalBudget, 2) }}</td>
                                    <td style="padding:12px 8px;text-align:center;color:var(--dark);font-weight:700;">&#x20B1;{{ number_format($totalSpent, 2) }}</td>
                                    <td style="padding:12px 8px;text-align:center;color:{{ $totalVariance >= 0 ? '#16a34a' : '#ef4444' }};font-weight:900;">
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
                @endphp

                {{-- Summary + Form side by side --}}
                <div style="display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:16px;align-items:start;margin-bottom:16px;">

                {{-- Left: Summary table --}}
                <div class="pm-card" style="margin-bottom:0;display:flex;flex-direction:column;min-width:0;height:500px;overflow:hidden;">
                    <div class="pm-card-header">
                        <div>
                            <div class="pm-card-title">Material usage &mdash; summary</div>
                            <div class="pm-card-sub">Aggregated usage per material &middot; logged by employees</div>
                        </div>
                        @if($activeUsageEntries->isNotEmpty())
                        <span style="font-size:12px;font-weight:700;color:var(--muted);">{{ $usageGrouped->count() }} material{{ $usageGrouped->count() !== 1 ? 's' : '' }} &nbsp;·&nbsp; Total: <strong style="color:var(--dark);">{{ number_format($activeUsageEntries->sum('quantity_used'), 0) }}</strong></span>
                        @endif
                    </div>
                    @php $ucols = 'table-layout:fixed;border-collapse:collapse;width:100%;'; @endphp
                    <div style="flex:1;display:flex;flex-direction:column;min-height:0;overflow:hidden;">
                        <table style="{{ $ucols }}">
                            <colgroup>
                                <col style="width:40%;">
                                <col style="width:20%;">
                                <col style="width:20%;">
                                <col style="width:20%;">
                            </colgroup>
                            <thead>
                                <tr style="background:var(--cream-soft);border-bottom:1px solid var(--border);">
                                    <th style="text-align:left;padding:10px 12px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Material</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Stock (Bought)</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Qty Used</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Remaining</th>
                                </tr>
                            </thead>
                        </table>
                        <div style="flex:1;overflow-y:scroll;min-height:0;">
                        <table style="{{ $ucols }}">
                            <colgroup>
                                <col style="width:40%;">
                                <col style="width:20%;">
                                <col style="width:20%;">
                                <col style="width:20%;">
                            </colgroup>
                            <tbody>
                                @forelse($usageGrouped as $matName => $entries)
                                @php
                                    $totalUsed   = $entries->sum('quantity_used');
                                    $unit        = $entries->first()->unit;
                                    $bomId       = $entries->first()->project_material_id;
                                    $stock       = isset($purchases) ? $purchases->where('project_material_id', $bomId)->sum('qty_bought') : 0;
                                    $remaining   = $stock - $totalUsed;
                                    $remainColor = $remaining < 0 ? '#b91c1c' : ($remaining == 0 ? '#92400e' : '#15803d');
                                @endphp
                                <tr style="border-bottom:1px solid var(--border);">
                                    <td style="padding:12px 12px;">
                                        <strong style="font-size:13px;color:var(--dark);">{{ $matName }}</strong>
                                        @if($unit)<div style="font-size:11.5px;color:var(--muted);margin-top:2px;">{{ $unit }}</div>@endif
                                    </td>
                                    <td style="text-align:center;padding:12px 8px;color:var(--dark);">{{ $stock > 0 ? number_format($stock, 0) : '—' }}</td>
                                    <td style="text-align:center;padding:12px 8px;font-weight:700;color:var(--dark);">{{ number_format($totalUsed, 0) }}</td>
                                    <td style="text-align:center;padding:12px 8px;font-weight:700;color:{{ $remainColor }};">
                                        {{ $stock > 0 ? number_format($remaining, 0) : '—' }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" style="text-align:center;padding:48px 20px;color:var(--muted);">
                                        <i data-lucide="activity" style="width:32px;height:32px;color:var(--border);display:block;margin:0 auto 10px;"></i>
                                        No usage entries logged yet.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                    @if($activeUsageEntries->isNotEmpty())
                    <div style="background:var(--cream-soft);display:grid;grid-template-columns:40fr 20fr 20fr 20fr;border-top:2px solid var(--border);">
                        <div style="padding:12px 14px;color:var(--dark);font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Total</div>
                        <div style="padding:12px 8px;text-align:center;color:var(--dark);font-weight:700;">{{ isset($purchases) && $purchases->isNotEmpty() ? number_format($purchases->sum('qty_bought'), 0) : '—' }}</div>
                        <div style="padding:12px 8px;text-align:center;color:var(--dark);font-weight:900;">{{ number_format($activeUsageEntries->sum('quantity_used'), 0) }}</div>
                        <div style="padding:12px 8px;"></div>
                    </div>
                    @endif
                </div>{{-- end summary card --}}

                {{-- Right: Log Usage form as sidebar card --}}
                <div class="pm-card" style="margin-bottom:0;display:flex;flex-direction:column;background:linear-gradient(180deg,#333333 0%,#2a2a2a 100%);border-color:transparent;height:500px;overflow:hidden;">
                    <div class="pm-card-header" style="padding-bottom:12px;border-bottom-color:rgba(255,255,255,.1);flex-shrink:0;">
                        <div>
                            <div class="pm-card-title" style="font-size:13px;color:#fff;">Log Material Usage</div>
                            <div class="pm-card-sub" style="color:rgba(255,255,255,.45);">Record consumed material</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.material_usage.store', $project->id) }}" style="padding:10px 16px 16px;flex:1;overflow-y:auto;" class="dark-form">
                        @csrf
                        <div style="display:flex;flex-direction:column;gap:12px;flex:1;">
                            @php
                                // Only show materials that have purchases, with remaining stock
                                $purchasedMatIds = isset($purchases) ? $purchases->pluck('project_material_id')->filter()->unique() : collect();
                                $matsWithStock = $activeMats->filter(fn($m) => $purchasedMatIds->contains($m->id));
                                $stockMap = [];
                                foreach($matsWithStock as $m) {
                                    $bought = isset($purchases) ? $purchases->where('project_material_id', $m->id)->sum('qty_bought') : 0;
                                    $alreadyUsed = $usageEntries->where('status','active')->where('project_material_id', $m->id)->sum('quantity_used');
                                    $stockMap[$m->id] = max(0, $bought - $alreadyUsed);
                                }
                            @endphp
                            <div class="pm-add-field">
                                <label>Material</label>
                                <select name="project_material_id" id="usageBomSelect" onchange="prefillUsage(this)">
                                    <option value="" disabled selected>Select material...</option>
                                    @foreach($matsWithStock as $mat)
                                    <option value="{{ $mat->id }}"
                                            data-name="{{ $mat->material_name }}"
                                            data-unit="{{ $mat->unit }}"
                                            data-max="{{ $stockMap[$mat->id] ?? 0 }}">
                                        {{ Str::limit($mat->material_name, 28) }} ({{ $stockMap[$mat->id] ?? 0 }} remaining)
                                    </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="material_name" id="usageNameHidden">
                                <span id="usageStockHint" style="font-size:11px;color:rgba(255,255,255,.4);margin-top:4px;display:none;"></span>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                <div class="pm-add-field">
                                    <label>Qty Used</label>
                                    <input type="number" name="quantity_used" id="usageQtyInput" min="0.01" step="0.01" value="1" required>
                                </div>
                                <div class="pm-add-field">
                                    <label>Unit</label>
                                    <input type="text" name="unit" id="usageUnit" placeholder="pcs" maxlength="50">
                                </div>
                            </div>
                            <div class="pm-add-field">
                                <label>Date</label>
                                <input type="date" name="used_date" required value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div style="flex:1;"></div>
                            <button type="submit" class="save-btn" style="width:100%;justify-content:center;height:44px;margin-top:0;">
                                <i data-lucide="check"></i> Log Usage
                            </button>
                        </div>
                    </form>
                </div>

                </div>{{-- end side-by-side grid --}}

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
                                    <td style="font-weight:700;">
                                        {{ $entry->material_name }}
                                        @if($entry->notes)
                                        <div style="font-size:11.5px;color:var(--muted-light);margin-top:2px;">{{ Str::limit($entry->notes, 50) }}</div>
                                        @endif
                                    </td>
                                    <td class="ctr" style="font-weight:800;color:var(--dark);">{{ number_format($entry->quantity_used, 0) }}</td>
                                    <td class="ctr" style="color:var(--muted);">{{ $entry->unit ?? '—' }}</td>
                                    <td class="ctr" style="white-space:nowrap;color:var(--muted);font-size:12.5px;">
                                        {{ $entry->used_date ? \Carbon\Carbon::parse($entry->used_date)->format('M d, Y') : '—' }}
                                    </td>
                                    <td class="ctr" style="font-size:12.5px;color:var(--muted);">{{ $entry->recorded_by ?? '—' }}</td>
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

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        const ACTIVE_TAB = "{{ session('active_tab', 'bom') }}";
        if (typeof lucide !== 'undefined') lucide.createIcons();

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

        function prefillPurchase(sel) {
            var opt      = sel.options[sel.selectedIndex];
            var custom   = document.getElementById('purchaseNameCustom');
            var hidden   = document.getElementById('purchaseNameHidden');
            var isOther  = (sel.value === '__other__' || sel.value === '');

            if (isOther) {
                custom.style.display = '';
                custom.value = '';
                custom.required = true;
                hidden.value = '';
                custom.focus();
                // clear BOM project_material_id
                sel.value = '__other__';
            } else {
                custom.style.display = 'none';
                custom.required = false;
                custom.value = '';
                hidden.value = opt.dataset.name || '';
                if (opt.dataset.cost) {
                    document.getElementById('purchaseUnitCost').value = opt.dataset.cost;
                    calcPurchaseTotal();
                }
            }
        }

        // init on load: custom input hidden until "Other" is chosen
        document.addEventListener('DOMContentLoaded', function() {
            var custom = document.getElementById('purchaseNameCustom');
            if (custom) custom.style.display = 'none';
        });

        function calcPurchaseTotal() {
            var qty  = parseFloat(document.getElementById('purchaseQty').value) || 0;
            var cost = parseFloat(document.getElementById('purchaseUnitCost').value) || 0;
            var total = qty * cost;
            document.getElementById('purchaseTotalDisplay').value = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
        }

        function prefillUsage(sel) {
            var opt     = sel.options[sel.selectedIndex];
            var hidden  = document.getElementById('usageNameHidden');
            var unitInp = document.getElementById('usageUnit');
            var qtyInp  = document.getElementById('usageQtyInput');
            var hint    = document.getElementById('usageStockHint');

            hidden.value = opt.dataset.name || '';
            if (unitInp && opt.dataset.unit) unitInp.value = opt.dataset.unit;

            var maxStock = parseFloat(opt.dataset.max) || 0;
            if (qtyInp && maxStock > 0) {
                qtyInp.max = maxStock;
                qtyInp.value = Math.min(parseFloat(qtyInp.value) || 1, maxStock);
                if (hint) {
                    hint.textContent = 'Max: ' + maxStock + ' units remaining in stock';
                    hint.style.display = 'block';
                }
            } else if (qtyInp) {
                qtyInp.removeAttribute('max');
                if (hint) hint.style.display = 'none';
            }
        }

        function onSupplierChange(sel) {
            var custom = document.getElementById('supplierCustom');
            if (!custom) return;
            if (sel.value === '__other__') {
                custom.style.display = '';
                custom.value = '';
                custom.focus();
            } else {
                custom.style.display = 'none';
                custom.value = sel.value;
            }
        }

        // On page load, sync hidden supplier input if a dropdown exists
        document.addEventListener('DOMContentLoaded', function() {
            var dd = document.getElementById('supplierDropdown');
            if (dd) onSupplierChange(dd);
        });
    </script>

    <style>
        .pm-tabs { display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:12px; }
        .pm-tab  { display:inline-flex;align-items:center;gap:7px;background:none;border:none;padding:11px 18px;font-size:13px;font-weight:600;color:var(--muted);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s; }
        .pm-tab:hover { color:var(--dark); }
        .pm-tab.active { color:var(--dark);font-weight:800;border-bottom-color:var(--dark); }
        .pm-tab-content { display:none; }
        .pm-tab-content.active { display:block; }
        .pm-card { background:var(--white);border:1px solid var(--border);border-radius:14px;overflow:visible; }
        .pm-card .table-wrapper { border-radius:0 0 14px 14px;overflow:auto; }
        .pm-card-header { display:flex;justify-content:space-between;align-items:flex-start;padding:16px 20px;border-bottom:1px solid var(--border); }
        .pm-card-title { font-size:14px;font-weight:800;color:var(--dark); }
        .pm-card-sub   { font-size:12px;color:var(--muted-light);margin-top:2px;font-weight:500; }
        .pm-table { width:100%;border-collapse:collapse;min-width:700px; }
        .pm-table thead th { padding:10px 14px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--muted-light);border-bottom:1px solid var(--border);background:var(--cream-soft);white-space:nowrap; }
        .pm-table thead th.num { text-align:right; }
        .pm-table thead th.ctr { text-align:center; }
        .pm-table tbody td { padding:14px 14px;font-size:13px;color:var(--dark);border-bottom:1px solid var(--border);vertical-align:middle; }
        .pm-table tbody td.num { text-align:right; }
        .pm-table tbody td.ctr { text-align:center; }
        .pm-table tbody tr:last-child td { border-bottom:none; }
        .pm-table tbody tr:hover { background:var(--cream-soft); }
        .pm-subtotal td { padding:12px 14px;font-size:13px;font-weight:800;color:var(--dark);background:var(--cream-soft);border-top:1px solid var(--border); }
        .pm-subtotal td.num { text-align:right; }
        .pm-status { display:inline-flex;align-items:center;gap:4px;font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px; }
        .pm-status.purchased { background:#dcfce7;color:#15803d; }
        .pm-status.pending   { background:var(--cream-deep);color:var(--muted); }
        .pm-status.over      { background:#fee2e2;color:#b91c1c; }
        .pm-add-row  { display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;padding:16px 20px;border-top:1px solid var(--border);background:var(--cream-soft); }
        .pm-add-field { display:flex;flex-direction:column;gap:5px;flex:1;min-width:80px; }
        .pm-add-field label { font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--muted-light); }
        .pm-add-field input,.pm-add-field select { height:40px;border:1px solid var(--border);border-radius:8px;padding:0 10px;font-size:13px;font-weight:600;background:var(--white);color:var(--dark);font-family:inherit;width:100%; }
        .pm-add-field input:focus,.pm-add-field select:focus { outline:none;border-color:var(--dark); }
        .pm-log-header { padding:10px 20px 0;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:var(--muted-light); }

        /* Dark sidebar form inputs */
        .dark-form .pm-add-field label { color:rgba(255,255,255,.45); }
        .dark-form .pm-add-field input,
        .dark-form .pm-add-field select {
            background:rgba(255,255,255,.08);
            border-color:rgba(255,255,255,.12);
            color:#fff;
        }
        .dark-form .pm-add-field input:focus,
        .dark-form .pm-add-field select:focus { border-color:rgba(255,255,255,.35);outline:none; }
        .dark-form .pm-add-field input::placeholder { color:rgba(255,255,255,.3); }
        .dark-form select option { background:#2a2a2a;color:#fff; }
    </style>
</body>
</html>
