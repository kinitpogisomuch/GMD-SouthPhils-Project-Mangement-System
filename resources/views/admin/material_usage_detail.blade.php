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
                <a href="{{ route('admin.material_usage.client', urlencode($project->client)) }}" style="color:var(--muted);text-decoration:none;font-weight:600;">{{ $project->live_client_name }}</a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="font-weight:700;color:var(--dark);">{{ $project->name }}</span>
            </div>

            <div class="page-header">
                <div>
                    <h1>
                        {{ $project->name }}
                    </h1>
                    <p><span class="client-pill">{{ $project->live_client_name }}</span> &nbsp;&middot;&nbsp; {{ ucfirst(str_replace('_',' ',$project->current_phase ?? 'Planning')) }} phase</p>
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
                    // BOM materials that don't have a single unit purchased yet —
                    // powers the "Add All Un-Purchased" shortcut on the log form.
                    $unpurchasedMats = $plannedMaterials->filter(function ($m) use ($purchasesByName) {
                        $group = $purchasesByName->get($m->material_name);
                        return !$group || $group->sum('qty_bought') <= 0;
                    })->values();
                    $unpurchasedMatsJs = $unpurchasedMats->map(function ($m) {
                        return ['id' => $m->id, 'name' => $m->material_name, 'unit' => $m->unit, 'cost' => $m->price_per_unit];
                    })->values();
                @endphp

                {{-- Summary table (full width — the purchase form now lives in a modal) --}}
                <div class="pm-card" style="margin-bottom:16px;display:flex;flex-direction:column;min-width:0;height:500px;overflow:hidden;">
                    <div class="pm-card-header">
                        <div>
                            <div class="pm-card-title">Purchased materials &mdash; summary</div>
                            <div class="pm-card-sub">Aggregated totals per material &middot; updates KPI automatically</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:14px;">
                            @if($allMaterialRows->isNotEmpty())
                            <span style="font-size:12px;font-weight:700;color:var(--muted);">{{ $allMaterialRows->count() }} material{{ $allMaterialRows->count() !== 1 ? 's' : '' }} &nbsp;·&nbsp; Total: <strong style="color:#16a34a;">&#x20B1;{{ number_format($totalPurchased,2) }}</strong></span>
                            @endif
                            <button type="button" class="add-btn" id="openLogPurchaseModal" style="white-space:nowrap;">
                                <i data-lucide="plus"></i>
                                Log New Purchase
                            </button>
                        </div>
                    </div>

                    @php $pcols = 'table-layout:fixed;border-collapse:collapse;width:100%;'; @endphp
                    <div style="flex:1;display:flex;flex-direction:column;min-height:0;overflow:hidden;">
                        <table style="{{ $pcols }}">
                            <colgroup>
                                <col style="width:28%;">
                                <col style="width:11%;">
                                <col style="width:13%;">
                                <col style="width:15%;">
                                <col style="width:15%;">
                                <col style="width:18%;">
                            </colgroup>
                            <thead>
                                <tr style="background:var(--cream-soft);border-bottom:1px solid var(--border);">
                                    <th style="text-align:left;padding:10px 12px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Material</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Unit</th>
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
                                <col style="width:28%;">
                                <col style="width:11%;">
                                <col style="width:13%;">
                                <col style="width:15%;">
                                <col style="width:15%;">
                                <col style="width:18%;">
                            </colgroup>
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
                                @endphp
                                <tr style="border-bottom:1px solid var(--border);">
                                    <td style="padding:12px 12px;">
                                        <strong style="font-size:13px;font-weight:700;color:var(--dark);">{{ $matName }}</strong>
                                    </td>
                                    <td style="text-align:center;padding:12px 8px;font-size:12px;font-weight:400;color:var(--muted);">{{ $unit ?: '—' }}</td>
                                    <td style="text-align:center;padding:12px 8px;font-size:13px;font-weight:400;color:var(--dark);">{{ $planned !== null ? number_format($planned, 0) : '—' }}</td>
                                    <td style="text-align:center;padding:12px 8px;font-size:13px;font-weight:400;color:var(--dark);">{{ number_format($totalQty, 0) }}</td>
                                    <td style="text-align:right;padding:12px 8px;font-size:13px;color:var(--dark);font-weight:700;">₱{{ number_format($totalPaid, 2) }}</td>
                                    <td style="text-align:center;padding:12px 8px;">
                                        @if($vsPct !== null)
                                        <div style="display:flex;align-items:center;gap:6px;justify-content:center;">
                                            <div style="width:60px;height:5px;background:var(--cream-deep);border-radius:999px;overflow:hidden;flex-shrink:0;">
                                                <div style="height:100%;width:{{ min(100,$vsPct) }}%;background:{{ $vsColor }};border-radius:999px;"></div>
                                            </div>
                                            <span style="font-weight:800;font-size:12px;color:{{ $vsColor }};min-width:30px;">{{ $vsPct }}%</span>
                                        </div>
                                        @else
                                        <span style="font-size:13px;color:var(--muted);">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" style="text-align:center;padding:48px 20px;font-size:13px;color:var(--muted);">
                                        <i data-lucide="shopping-cart" style="width:32px;height:32px;color:var(--border);display:block;margin:0 auto 10px;"></i>
                                        No materials planned or purchased yet.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                    @if($allMaterialRows->isNotEmpty())
                    <div style="background:var(--cream-soft);display:grid;grid-template-columns:28fr 11fr 13fr 15fr 15fr 18fr;border-top:2px solid var(--border);">
                        <div style="padding:12px 14px;color:var(--dark);font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Total</div>
                        <div style="padding:12px 8px;"></div>
                        <div style="padding:12px 8px;text-align:center;font-size:13px;color:var(--muted);">—</div>
                        <div style="padding:12px 8px;text-align:center;font-size:13px;color:var(--dark);font-weight:900;">{{ number_format($purchases->sum('qty_bought'), 0) }}</div>
                        <div style="padding:12px 8px;text-align:right;font-size:13px;color:var(--dark);font-weight:900;">&#x20B1;{{ number_format($totalPurchased, 2) }}</div>
                        <div style="padding:12px 8px;"></div>
                    </div>
                    @endif

                </div>{{-- end summary card --}}

                {{-- Log New Purchase modal — supports multiple material rows in one
                     submission, so a whole supplier run can be logged at once instead
                     of one purchase at a time. --}}
                <div class="modal-overlay" id="logPurchaseModal">
                <div class="modal-card pm-card" style="max-width:760px;width:95%;max-height:88vh;margin-bottom:0;background:linear-gradient(180deg,#333333 0%,#2a2a2a 100%);border-color:transparent;display:flex;flex-direction:column;overflow:hidden;">
                    <div class="pm-card-header" style="padding-bottom:12px;border-bottom-color:rgba(255,255,255,.1);flex-shrink:0;">
                        <div>
                            <div class="pm-card-title" style="font-size:14px;color:#fff;">Log New Purchase</div>
                            <div class="pm-card-sub" style="color:rgba(255,255,255,.45);">Record one or more material purchases at once</div>
                        </div>
                        <button type="button" class="modal-close" id="closeLogPurchaseModal" style="background-color:rgba(255,255,255,.08);border-color:rgba(255,255,255,.14);color:rgba(255,255,255,.75);">
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('admin.material_usage.store_purchase', $project->id) }}" id="logPurchaseForm" style="padding:10px 20px 20px;flex:1;overflow-y:auto;" class="dark-form">
                        @csrf
                        <div style="display:flex;gap:8px;margin-bottom:12px;">
                            <button type="button" class="cancel-btn" style="justify-content:center;font-size:12.5px;padding:9px 16px;" onclick="addPurchaseRow()">
                                <i data-lucide="plus" style="width:14px;height:14px;"></i> Add Material
                            </button>
                            @if($unpurchasedMats->isNotEmpty())
                            <button type="button" class="cancel-btn" style="justify-content:center;font-size:12.5px;padding:9px 16px;" onclick="addAllUnpurchasedRows()">
                                <i data-lucide="list-plus" style="width:14px;height:14px;"></i> Add All Un-Purchased ({{ $unpurchasedMats->count() }})
                            </button>
                            @endif
                        </div>

                        <div style="display:grid;grid-template-columns:2.2fr 0.9fr 0.8fr 1fr 1fr 26px;gap:8px;padding:0 4px 6px;margin-bottom:2px;">
                            <span style="font-size:10.5px;font-weight:800;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.04em;">Material</span>
                            <span style="font-size:10.5px;font-weight:800;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.04em;">Unit</span>
                            <span style="font-size:10.5px;font-weight:800;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.04em;">Qty</span>
                            <span style="font-size:10.5px;font-weight:800;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.04em;">Unit Cost (₱)</span>
                            <span style="font-size:10.5px;font-weight:800;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.04em;">Row Total</span>
                            <span></span>
                        </div>

                        <div id="purchaseRowsContainer"></div>

                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:12px;align-items:end;">
                            <div class="pm-add-field">
                                <label>Supplier</label>
                                @if(isset($suppliers) && $suppliers->isNotEmpty())
                                <select id="supplierDropdown" onchange="onSupplierChange(this)">
                                    <option value="" disabled selected hidden>Select supplier...</option>
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
                            <div class="pm-add-field">
                                <label>Grand Total (Auto)</label>
                                <input type="text" id="purchaseGrandTotal" readonly
                                       style="background:rgba(255,255,255,.06);cursor:default;color:#4ade80;font-weight:800;border-color:rgba(255,255,255,.14);"
                                       value="&#x20B1;0.00">
                            </div>
                        </div>

                        <button type="submit" class="save-btn" style="width:100%;justify-content:center;height:44px;margin-top:16px;">
                            <i data-lucide="check"></i> Save Purchases
                        </button>
                    </form>
                </div>

                {{-- Hidden template for one purchase row — cloned by JS via addPurchaseRow() --}}
                <template id="purchaseRowTemplate">
                    <div class="purchase-row" style="display:grid;grid-template-columns:2.2fr 0.9fr 0.8fr 1fr 1fr 26px;gap:8px;align-items:start;padding:6px 4px;margin-bottom:6px;">
                        <div class="pm-add-field">
                            <select name="project_material_id[]" class="purchase-row-bom-select" onchange="prefillPurchaseRow(this)">
                                <option value="" disabled selected hidden>Select material...</option>
                                @foreach($activeMats as $mat)
                                <option value="{{ $mat->id }}"
                                        data-name="{{ $mat->material_name }}"
                                        data-unit="{{ $mat->unit }}"
                                        data-cost="{{ $mat->price_per_unit }}">
                                    {{ Str::limit($mat->material_name, 40) }}
                                </option>
                                @endforeach
                                <option value="__other__">Other (type manually)</option>
                            </select>
                            <input type="hidden" name="material_name[]" class="purchase-row-name-hidden">
                            <input type="text" class="purchase-row-name-custom"
                                   placeholder="Type material name" maxlength="255"
                                   style="display:none;margin-top:6px;">
                        </div>
                        <div class="pm-add-field">
                            <input type="text" name="unit[]" class="purchase-row-unit" placeholder="pcs" maxlength="50">
                        </div>
                        <div class="pm-add-field">
                            <input type="text" inputmode="decimal" name="qty_bought[]" class="purchase-row-qty" value="1" required oninput="formatMoneyInput(this); calcAllPurchaseTotals();">
                        </div>
                        <div class="pm-add-field">
                            <input type="text" inputmode="decimal" name="actual_unit_cost[]" class="purchase-row-cost" value="0" required oninput="formatMoneyInput(this); calcAllPurchaseTotals();">
                        </div>
                        <div class="pm-add-field">
                            <input type="text" class="purchase-row-total" readonly value="&#x20B1;0.00"
                                   style="background:rgba(255,255,255,.06);cursor:default;color:#4ade80;font-weight:800;border-color:rgba(255,255,255,.14);">
                        </div>
                        <button type="button" class="remove-purchase-row-btn" onclick="removePurchaseRow(this)" title="Remove row"
                                style="background:none;border:none;color:rgba(255,255,255,.45);cursor:pointer;padding:0;height:40px;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="x" style="width:14px;height:14px;"></i>
                        </button>
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
                                    <td style="font-size:13px;font-weight:700;color:var(--dark);">{{ $pur->material_name }}</td>
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

                    // Every BOM material is selectable (matching the Purchase modal) —
                    // remaining stock is shown as a hint per option, not a filter, so
                    // usage can still be logged even for materials not purchased yet.
                    $stockMap = [];
                    foreach ($activeMats as $m) {
                        $bought      = isset($purchases) ? $purchases->where('project_material_id', $m->id)->sum('qty_bought') : 0;
                        $alreadyUsed = $usageEntries->where('status', 'active')->where('project_material_id', $m->id)->sum('quantity_used');
                        $stockMap[$m->id] = max(0, $bought - $alreadyUsed);
                    }
                    // "Add All In-Stock" shortcut still only targets materials that
                    // actually have remaining purchased stock.
                    $matsInStock = $activeMats->filter(fn($m) => ($stockMap[$m->id] ?? 0) > 0)->values();
                    $inStockMatsJs = $matsInStock->map(function ($m) use ($stockMap) {
                        return ['id' => $m->id, 'name' => $m->material_name, 'unit' => $m->unit, 'max' => $stockMap[$m->id] ?? 0];
                    })->values();
                @endphp

                {{-- Summary table (full width — the usage form now lives in a modal) --}}
                <div class="pm-card" style="margin-bottom:16px;display:flex;flex-direction:column;min-width:0;height:500px;overflow:hidden;">
                    <div class="pm-card-header">
                        <div>
                            <div class="pm-card-title">Material usage &mdash; summary</div>
                            <div class="pm-card-sub">Aggregated usage per material &middot; logged by employees</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:14px;">
                            @if($allUsageRows->isNotEmpty())
                            <span style="font-size:12px;font-weight:700;color:var(--muted);">{{ $allUsageRows->count() }} material{{ $allUsageRows->count() !== 1 ? 's' : '' }} &nbsp;·&nbsp; Total: <strong style="color:var(--dark);">{{ number_format($activeUsageEntries->sum('quantity_used'), 0) }}</strong></span>
                            @endif
                            <button type="button" class="add-btn" id="openLogUsageModal" style="white-space:nowrap;">
                                <i data-lucide="plus"></i>
                                Log Material Usage
                            </button>
                        </div>
                    </div>
                    @php $ucols = 'table-layout:fixed;border-collapse:collapse;width:100%;'; @endphp
                    <div style="flex:1;display:flex;flex-direction:column;min-height:0;overflow:hidden;">
                        <table style="{{ $ucols }}">
                            <colgroup>
                                <col style="width:25%;">
                                <col style="width:11%;">
                                <col style="width:14%;">
                                <col style="width:14%;">
                                <col style="width:12%;">
                                <col style="width:24%;">
                            </colgroup>
                            <thead>
                                <tr style="background:var(--cream-soft);border-bottom:1px solid var(--border);">
                                    <th style="text-align:left;padding:10px 12px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Material</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Unit</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Stock (Bought)</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Qty Used</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Remaining</th>
                                    <th style="text-align:center;padding:10px 8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Status</th>
                                </tr>
                            </thead>
                        </table>
                        <div style="flex:1;overflow-y:scroll;min-height:0;">
                        <table style="{{ $ucols }}">
                            <colgroup>
                                <col style="width:25%;">
                                <col style="width:11%;">
                                <col style="width:14%;">
                                <col style="width:14%;">
                                <col style="width:12%;">
                                <col style="width:24%;">
                            </colgroup>
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
                                <tr style="border-bottom:1px solid var(--border);">
                                    <td style="padding:12px 12px;">
                                        <strong style="font-size:13px;font-weight:700;color:var(--dark);">{{ $matName }}</strong>
                                    </td>
                                    <td style="text-align:center;padding:12px 8px;font-size:12px;font-weight:400;color:var(--muted);">{{ $unit ?: '—' }}</td>
                                    <td style="text-align:center;padding:12px 8px;font-size:13px;font-weight:400;color:var(--dark);">{{ $stock > 0 ? number_format($stock, 0) : '—' }}</td>
                                    <td style="text-align:center;padding:12px 8px;font-size:13px;font-weight:700;color:var(--dark);">{{ number_format($totalUsed, 0) }}</td>
                                    <td style="text-align:center;padding:12px 8px;font-size:13px;font-weight:700;color:{{ $remainColor }};">
                                        {{ $stock > 0 ? number_format($remaining, 0) : '—' }}
                                    </td>
                                    <td style="text-align:center;padding:12px 8px;">
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
                                    <td colspan="6" style="text-align:center;padding:48px 20px;font-size:13px;color:var(--muted);">
                                        <i data-lucide="activity" style="width:32px;height:32px;color:var(--border);display:block;margin:0 auto 10px;"></i>
                                        No materials planned or used yet.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                    @if($allUsageRows->isNotEmpty())
                    <div style="background:var(--cream-soft);display:grid;grid-template-columns:25fr 11fr 14fr 14fr 12fr 24fr;border-top:2px solid var(--border);">
                        <div style="padding:12px 14px;color:var(--dark);font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Total</div>
                        <div style="padding:12px 8px;"></div>
                        <div style="padding:12px 8px;text-align:center;font-size:13px;color:var(--dark);font-weight:900;">{{ isset($purchases) && $purchases->isNotEmpty() ? number_format($purchases->sum('qty_bought'), 0) : '—' }}</div>
                        <div style="padding:12px 8px;text-align:center;font-size:13px;color:var(--dark);font-weight:900;">{{ number_format($activeUsageEntries->sum('quantity_used'), 0) }}</div>
                        <div style="padding:12px 8px;"></div>
                        <div style="padding:12px 8px;"></div>
                    </div>
                    @endif
                </div>{{-- end summary card --}}

                {{-- Log Material Usage modal — supports multiple material rows in
                     one submission, mirroring the Log New Purchase modal. --}}
                <div class="modal-overlay" id="logUsageModal">
                <div class="modal-card pm-card" style="max-width:700px;width:95%;max-height:88vh;margin-bottom:0;background:linear-gradient(180deg,#333333 0%,#2a2a2a 100%);border-color:transparent;display:flex;flex-direction:column;overflow:hidden;">
                    <div class="pm-card-header" style="padding-bottom:12px;border-bottom-color:rgba(255,255,255,.1);flex-shrink:0;">
                        <div>
                            <div class="pm-card-title" style="font-size:14px;color:#fff;">Log Material Usage</div>
                            <div class="pm-card-sub" style="color:rgba(255,255,255,.45);">Record one or more consumed materials at once</div>
                        </div>
                        <button type="button" class="modal-close" id="closeLogUsageModal" style="background-color:rgba(255,255,255,.08);border-color:rgba(255,255,255,.14);color:rgba(255,255,255,.75);">
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('admin.material_usage.store', $project->id) }}" id="logUsageForm" style="padding:10px 20px 20px;flex:1;overflow-y:auto;" class="dark-form">
                        @csrf
                        <div style="display:flex;gap:8px;margin-bottom:12px;">
                            <button type="button" class="cancel-btn" style="justify-content:center;font-size:12.5px;padding:9px 16px;" onclick="addUsageRow()">
                                <i data-lucide="plus" style="width:14px;height:14px;"></i> Add Material
                            </button>
                            @if($matsInStock->isNotEmpty())
                            <button type="button" class="cancel-btn" style="justify-content:center;font-size:12.5px;padding:9px 16px;" onclick="addAllInStockRows()">
                                <i data-lucide="list-plus" style="width:14px;height:14px;"></i> Add All In-Stock ({{ $matsInStock->count() }})
                            </button>
                            @endif
                        </div>

                        <div style="display:grid;grid-template-columns:2.4fr 1fr 0.9fr 26px;gap:8px;padding:0 4px 6px;margin-bottom:2px;">
                            <span style="font-size:10.5px;font-weight:800;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.04em;">Material</span>
                            <span style="font-size:10.5px;font-weight:800;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.04em;">Unit</span>
                            <span style="font-size:10.5px;font-weight:800;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.04em;">Qty Used</span>
                            <span></span>
                        </div>

                        <div id="usageRowsContainer"></div>

                        <div class="pm-add-field" style="max-width:220px;margin-top:12px;">
                            <label>Date</label>
                            <input type="date" name="used_date" required value="{{ now()->format('Y-m-d') }}">
                        </div>

                        <button type="submit" class="save-btn" style="width:100%;justify-content:center;height:44px;margin-top:16px;">
                            <i data-lucide="check"></i> Log Usage
                        </button>
                    </form>
                </div>

                {{-- Hidden template for one usage row — cloned by JS via addUsageRow() --}}
                <template id="usageRowTemplate">
                    <div class="usage-row" style="display:grid;grid-template-columns:2.4fr 1fr 0.9fr 26px;gap:8px;align-items:start;padding:6px 4px;margin-bottom:6px;">
                        <div class="pm-add-field">
                            <select name="project_material_id[]" class="usage-row-bom-select" onchange="prefillUsageRow(this)">
                                <option value="" disabled selected hidden>Select material...</option>
                                @foreach($activeMats as $mat)
                                @php $matStock = $stockMap[$mat->id] ?? 0; @endphp
                                <option value="{{ $mat->id }}"
                                        data-name="{{ $mat->material_name }}"
                                        data-unit="{{ $mat->unit }}"
                                        data-max="{{ $matStock }}"
                                        @if($matStock <= 0) disabled @endif>
                                    {{ Str::limit($mat->material_name, 32) }} — {{ $matStock > 0 ? $matStock . ' in stock' : 'No stock (purchase first)' }}
                                </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="material_name[]" class="usage-row-name-hidden">
                            <span class="usage-row-stock-hint" style="font-size:10.5px;color:rgba(255,255,255,.4);margin-top:4px;display:none;"></span>
                        </div>
                        <div class="pm-add-field">
                            <input type="text" name="unit[]" class="usage-row-unit" placeholder="pcs" maxlength="50">
                        </div>
                        <div class="pm-add-field">
                            <input type="text" inputmode="decimal" name="quantity_used[]" class="usage-row-qty" value="1" required oninput="formatMoneyInput(this);">
                        </div>
                        <button type="button" class="remove-usage-row-btn" onclick="removeUsageRow(this)" title="Remove row"
                                style="background:none;border:none;color:rgba(255,255,255,.45);cursor:pointer;padding:0;height:40px;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="x" style="width:14px;height:14px;"></i>
                        </button>
                    </div>
                </template>
                </div>{{-- end logUsageModal overlay --}}

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

        document.getElementById('openLogUsageModal')?.addEventListener('click', function () { openModal('logUsageModal'); });
        document.getElementById('closeLogUsageModal')?.addEventListener('click', function () { closeModal('logUsageModal'); });
        document.getElementById('logUsageModal')?.addEventListener('click', function (e) {
            if (e.target === this) closeModal('logUsageModal');
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

        // ---- Multi-row material purchase log ----
        var UNPURCHASED_MATS = @json($unpurchasedMatsJs);

        function addPurchaseRow(prefill) {
            var tpl       = document.getElementById('purchaseRowTemplate');
            var container = document.getElementById('purchaseRowsContainer');
            var row       = tpl.content.cloneNode(true).firstElementChild;
            container.appendChild(row);

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
            return row;
        }

        function removePurchaseRow(btn) {
            var row = btn.closest('.purchase-row');
            if (row) row.remove();
            calcAllPurchaseTotals();
        }

        function addAllUnpurchasedRows() {
            UNPURCHASED_MATS.forEach(function (m) { addPurchaseRow(m); });
        }

        function prefillPurchaseRow(sel) {
            var row      = sel.closest('.purchase-row');
            var opt      = sel.options[sel.selectedIndex];
            var custom   = row.querySelector('.purchase-row-name-custom');
            var hidden   = row.querySelector('.purchase-row-name-hidden');
            var unitInput = row.querySelector('.purchase-row-unit');
            var isOther  = (sel.value === '__other__' || sel.value === '');

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
            });
            document.getElementById('purchaseGrandTotal').value = '₱' + grand.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
        }

        var logPurchaseForm = document.getElementById('logPurchaseForm');
        if (logPurchaseForm) {
            logPurchaseForm.addEventListener('submit', function (e) {
                var rows = document.querySelectorAll('#purchaseRowsContainer .purchase-row');
                if (!rows.length) {
                    e.preventDefault();
                    alert('Add at least one material before saving.');
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

        var logUsageForm = document.getElementById('logUsageForm');
        if (logUsageForm) {
            logUsageForm.addEventListener('submit', function (e) {
                var rows = document.querySelectorAll('#usageRowsContainer .usage-row');
                if (!rows.length) {
                    e.preventDefault();
                    alert('Add at least one material before saving.');
                    return;
                }
                rows.forEach(function (row) {
                    var qtyEl = row.querySelector('.usage-row-qty');
                    if (qtyEl) qtyEl.value = qtyEl.value.replace(/,/g, '');
                });
            });
        }

        // Start with one empty row on page load.
        document.addEventListener('DOMContentLoaded', function () {
            addPurchaseRow();
            addUsageRow();
        });

        // ---- Multi-row material usage log ----
        var IN_STOCK_MATS = @json($inStockMatsJs);

        function addUsageRow(prefill) {
            var tpl       = document.getElementById('usageRowTemplate');
            var container = document.getElementById('usageRowsContainer');
            var row       = tpl.content.cloneNode(true).firstElementChild;
            container.appendChild(row);

            if (prefill) {
                var sel = row.querySelector('.usage-row-bom-select');
                sel.value = prefill.id;
                prefillUsageRow(sel);
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();
            return row;
        }

        function removeUsageRow(btn) {
            var row = btn.closest('.usage-row');
            if (row) row.remove();
        }

        function addAllInStockRows() {
            IN_STOCK_MATS.forEach(function (m) { addUsageRow(m); });
        }

        function prefillUsageRow(sel) {
            var row     = sel.closest('.usage-row');
            var opt     = sel.options[sel.selectedIndex];
            var hidden  = row.querySelector('.usage-row-name-hidden');
            var unitInp = row.querySelector('.usage-row-unit');
            var hint    = row.querySelector('.usage-row-stock-hint');

            hidden.value = opt.dataset.name || '';
            if (unitInp && opt.dataset.unit) unitInp.value = opt.dataset.unit;

            var maxStock = parseFloat(opt.dataset.max) || 0;
            if (hint) {
                hint.textContent = maxStock + ' in stock right now';
                hint.style.display = 'block';
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
