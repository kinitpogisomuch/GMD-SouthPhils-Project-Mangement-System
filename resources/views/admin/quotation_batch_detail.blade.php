<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Build Quotation — {{ $batch->client->name ?? 'Client' }} | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        #materialsTable th.num-cell, #materialsTable td.num-cell,
        #laborTable th.num-cell, #laborTable td.num-cell { text-align: right; }
        #materialsTable tfoot td, #laborTable tfoot td {
            border-bottom: none; border-top: 2px solid var(--border);
            padding-top: 14px; padding-bottom: 14px;
        }
        .table-total-label {
            text-align: right; font-weight: 800; color: var(--muted);
            text-transform: uppercase; font-size: 11px; letter-spacing: .06em;
        }
        .table-total-value { text-align: right; font-weight: 900; color: var(--dark); font-size: 15px; }
        .tank-item-chip {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--cream-soft); border: 1px solid var(--border);
            border-radius: 999px; padding: 6px 14px; font-size: 13px; font-weight: 700; color: var(--dark);
        }
        .mat-combo { position: relative; width: 100%; }
        .mat-combo-dropdown {
            display: none;
            position: fixed;
            max-height: 400px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid rgba(0,0,0,0.12);
            border-radius: 14px;
            box-shadow: 0 12px 32px rgba(0,0,0,.18);
            z-index: 1000;
            padding: 12px;
            columns: 3 180px;
            column-gap: 16px;
        }
        .mat-combo-dropdown.show { display: block; }
        .mat-combo-category {
            break-inside: avoid;
            margin-bottom: 8px;
        }
        .mat-combo-group {
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #fff;
            background: var(--dark);
            padding: 5px 10px;
            border-radius: 6px;
            margin-bottom: 4px;
            display: block;
        }
        .mat-combo-item {
            padding: 6px 10px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 500;
            color: var(--dark);
            cursor: pointer;
            display: block;
        }
        .mat-combo-item:hover {
            background: #f0f4ff;
            color: #2563EB;
            font-weight: 600;
        }
        .mat-combo-item.disabled {
            color: var(--muted);
            cursor: not-allowed;
            opacity: 0.6;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .mat-combo-item.disabled:hover {
            background: none;
        }
        .mat-combo-item-badge {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--danger);
            white-space: nowrap;
        }
        .mat-combo-empty {
            padding: 10px;
            font-size: 12px;
            color: var(--muted);
            text-align: center;
        }
        .mat-combo-warning {
            display: none;
            margin-top: 6px;
            font-size: 12px;
            font-weight: 700;
            color: var(--danger);
        }
        .mat-combo-warning.show {
            display: block;
        }
    </style>
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            {{-- Breadcrumb --}}
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('admin.quotation_requests') }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    Quotation Requests
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="color:var(--dark);font-weight:700;">Build Quotation</span>
            </div>

            <div class="page-header">
                <div>
                    <h1>{{ $batch->client->name ?? 'Client' }}</h1>
                    <p>Enter materials, labor, and costs for this quotation.</p>
                </div>
                @if($batchStatus === 'pending')
                <button class="add-btn" type="button" id="openSendQuotationModal">
                    <i data-lucide="send"></i>
                    Send to Client
                </button>
                @elseif($batchStatus === 'approved')
                <a class="add-btn" style="text-decoration:none;" href="{{ route('admin.projects', ['prefill_quotation_batch' => $batch->id]) }}">
                    <i data-lucide="folder-plus"></i>
                    Convert to Project
                </a>
                @endif
            </div>

            @if(session('success'))
            <div class="alert-banner success">
                <i data-lucide="check-circle"></i>
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="alert-banner error">
                <i data-lucide="alert-circle"></i>
                {{ session('error') }}
            </div>
            @endif

            @if($batchStatus === 'pending' && optional($tankItems->first())->decline_reason)
            <div class="alert-banner" style="margin-bottom:20px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;">
                <i data-lucide="rotate-ccw"></i>
                <strong>Client requested a revision:</strong> {{ $tankItems->first()->decline_reason }}
            </div>
            @endif

            @if($batchStatus === 'quotation_sent')
            <div class="alert-banner info" style="margin-bottom:20px;">
                <i data-lucide="clock"></i>
                Sent to the client on {{ optional($batch->updated_at)->format('M d, Y') }} — Contract Value ₱{{ number_format($batch->contract_value, 2) }}. Waiting for their approval.
            </div>
            @elseif($batchStatus === 'approved')
            <div class="alert-banner success" style="margin-bottom:20px;">
                <i data-lucide="check-circle"></i>
                The client approved this quotation — Contract Value ₱{{ number_format($batch->contract_value, 2) }}. Click <strong>Convert to Project</strong> above to create the project.
            </div>
            @elseif($batchStatus === 'declined')
            <div class="alert-banner error" style="margin-bottom:20px;">
                <i data-lucide="x-circle"></i>
                This quotation was declined.
            </div>
            @endif

            {{-- Tank Items (from the client's quotation request — read-only here) --}}
            <div class="table-card" style="padding:18px 20px;margin-bottom:20px;">
                <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:10px;">
                    Requested Tank(s)
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:10px;">
                    @forelse($tankItems as $item)
                    <span class="tank-item-chip">
                        <i data-lucide="package" style="width:14px;height:14px;"></i>
                        {{ $item->tank_type ?: 'Tank' }} &middot; {{ $item->capacity ?: '—' }} &middot; {{ $item->quantity }}x
                    </span>
                    @empty
                    <span style="color:var(--muted);font-size:13px;">No tank items on this request.</span>
                    @endforelse
                </div>
            </div>

            {{-- Overview --}}
            <div class="fd-overview" style="margin-bottom:24px;">
                <div class="fd-overview-title">
                    <i data-lucide="layout-dashboard"></i>
                    Quotation Overview
                </div>
                <div class="fd-overview-grid">
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Total Materials</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Active entries</span>
                        <span class="fd-ov-val">{{ $totalMaterials }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Material Cost</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Total material cost</span>
                        <span class="fd-ov-val">₱{{ number_format($estimatedCost, 2) }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Labor Entries</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Total entries</span>
                        <span class="fd-ov-val">{{ $totalLaborEntries }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Estimated Working Days</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Applies to all labor rows</span>
                        <span class="fd-ov-val">{{ number_format($batch->estimated_working_days ?? 0, 0) }} <small style="font-size:13px;">Days</small></span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Labor Cost</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Total labor cost</span>
                        <span class="fd-ov-val">₱{{ number_format($totalLaborCost, 2) }}</span>
                    </div>
                    <div class="fd-ov-item fd-ov-highlight">
                        <span class="fd-ov-label">Project Budget</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Est. materials + labor</span>
                        <span class="fd-ov-val" style="color:#4ade80;font-size:17px;">₱{{ number_format($estimatedBudget['total'], 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Markup --}}
            <div class="table-card" style="padding:18px 20px;margin-bottom:24px;">
                <div style="display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap;">
                    <form method="POST" action="{{ route('admin.quotation_requests.batch_markup', $batch->id) }}" id="markupForm" style="display:flex;align-items:flex-end;gap:10px;">
                        @csrf
                        <input type="hidden" name="open_send_modal" id="openSendModalFlag" value="0">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Markup / Profit (%)</label>
                            <input type="number" name="markup_percent" id="markupInput" min="0" max="100" step="0.01" required
                                   value="{{ $batch->markup_percent ?? 0 }}" placeholder="e.g. 10"
                                   oninput="updateMarkupPreview()" style="width:200px;">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Payment Terms</label>
                            <select name="payment_term_type" style="width:230px;">
                                <option value="" disabled {{ !$batch->payment_term_type ? 'selected' : '' }}>Select payment terms</option>
                                <option value="big_project" {{ $batch->payment_term_type === 'big_project' ? 'selected' : '' }}>Big Project — 3 Phases (50% / 30% / 20%)</option>
                                <option value="small_project" {{ $batch->payment_term_type === 'small_project' ? 'selected' : '' }}>Small Project — 2 Phases (50% / 50%)</option>
                            </select>
                        </div>
                        <button type="submit" class="save-btn">
                            <i data-lucide="save"></i>
                            Save
                        </button>
                    </form>
                    <div style="font-size:13px;color:var(--muted);padding-bottom:11px;">
                        Contract Value: <strong id="markupContractValuePreview" style="color:var(--dark);">₱{{ number_format($estimatedBudget['total'] + (float) ($batch->markup ?? 0), 2) }}</strong>
                        <span style="display:block;font-size:11.5px;margin-top:2px;">Added on top of the Project Budget — not shown to the client.</span>
                    </div>
                </div>
            </div>

            {{-- Materials --}}
            <div class="table-card" style="margin-bottom:24px;">
                <div class="table-toolbar">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <div style="display:flex;align-items:center;gap:8px;padding:0 16px;height:48px;background-color:var(--cream-soft);border:1px solid var(--border);border-radius:16px;font-size:13px;font-weight:700;color:var(--dark);white-space:nowrap;">
                            <i data-lucide="percent" style="width:16px;height:16px;color:var(--muted);"></i>
                            Material Factor: {{ number_format($materialFactor, 1) }}%
                        </div>
                    </div>
                    <button type="button" class="add-btn" id="openAddMaterialModal">
                        <i data-lucide="plus"></i>
                        Add Material
                    </button>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="materialsTable">
                        <thead>
                            <tr>
                                <th>Material Name</th>
                                <th>Unit</th>
                                <th class="num-cell">Quantity</th>
                                <th class="num-cell">Price/Unit</th>
                                <th class="num-cell">Total Cost</th>
                                <th>Date Added</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($materials->where('status', 'active') as $material)
                            <tr>
                                <td style="font-weight:700;">{{ $material->material_name }}</td>
                                <td>{{ $material->unit ?: '—' }}</td>
                                <td class="num-cell">{{ rtrim(rtrim(number_format($material->quantity, 2), '0'), '.') }}</td>
                                <td class="num-cell">₱{{ number_format($material->price_per_unit, 2) }}</td>
                                <td class="num-cell" style="font-weight:800;">₱{{ number_format($material->total_cost, 2) }}</td>
                                <td>{{ $material->created_at->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">
                                    No materials added yet. Click <strong>Add Material</strong> to get started.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($materials->where('status', 'active')->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="4" class="table-total-label">Grand Total (no Material Factor)</td>
                                <td class="table-total-value">₱{{ number_format($estimatedCost, 2) }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="table-total-label">Grand Total (with {{ number_format($materialFactor, 1) }}% Material Factor)</td>
                                <td class="table-total-value">₱{{ number_format($estimatedCost * (1 + $materialFactor / 100), 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Labor --}}
            <div class="table-card">
                <div class="table-toolbar">
                    <div style="display:flex;align-items:center;gap:8px;padding:0 16px;height:48px;background-color:var(--cream-soft);border:1px solid var(--border);border-radius:16px;font-size:13px;font-weight:700;color:var(--dark);white-space:nowrap;">
                        <i data-lucide="calendar" style="width:16px;height:16px;color:var(--muted);"></i>
                        Estimated Working Days: {{ number_format($batch->estimated_working_days ?? 0, 0) }}
                    </div>
                    <button type="button" class="add-btn" id="openAddLaborModal">
                        <i data-lucide="plus"></i>
                        Add Labor
                    </button>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="laborTable">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Role</th>
                                <th class="num-cell">Daily Rate</th>
                                <th class="num-cell">Total Cost</th>
                                <th style="width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($laborEntries as $entry)
                            @php
                                $empName = $entry->description;
                                $empRole = '';
                                if (preg_match('/^(.+?)\s*\((.+?)\)$/', $entry->description, $m)) {
                                    $empName = trim($m[1]);
                                    $empRole = trim($m[2]);
                                }
                            @endphp
                            <tr style="{{ $entry->status === 'archived' ? 'opacity:.5;' : '' }}">
                                <td style="font-weight:700;">{{ $empName }}</td>
                                <td>
                                    @if($empRole)
                                        <span style="font-size:12px;font-weight:700;background:var(--cream-soft);color:var(--dark);padding:3px 9px;border-radius:6px;white-space:nowrap;">{{ $empRole }}</span>
                                    @else
                                        <span style="color:var(--muted);">—</span>
                                    @endif
                                </td>
                                <td class="num-cell">₱{{ number_format($entry->daily_rate, 2) }}</td>
                                <td class="num-cell" style="font-weight:800;">₱{{ number_format($entry->total_cost, 2) }}</td>
                                <td class="action-cell">
                                    <form method="POST" action="{{ route('admin.quotation_requests.batch_labor_archive', [$batch->id, $entry->id]) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="action-btn" title="{{ $entry->status === 'archived' ? 'Restore' : 'Archive' }}" style="color:{{ $entry->status === 'archived' ? '#16a34a' : '#dc2626' }};">
                                            <i data-lucide="{{ $entry->status === 'archived' ? 'rotate-ccw' : 'archive' }}"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="text-align:center;padding:40px;color:var(--muted);">
                                    No labor entries added yet. Click <strong>Add Labor</strong> to get started.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($laborEntries->where('status', 'active')->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="3" class="table-total-label">Grand Total</td>
                                <td class="table-total-value">₱{{ number_format($totalLaborCost, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

        </main>
    </div>

    {{-- ===================== ADD MATERIAL MODAL ===================== --}}
    <div class="modal-overlay" id="addMaterialModal">
        <div class="modal-card" style="max-width:900px;width:95%;">
            <div class="modal-header">
                <div>
                    <h2>Add Materials</h2>
                    <p>Fill in the rows below — add as many materials as needed.</p>
                </div>
                <button class="modal-close" type="button" id="closeAddMaterialModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.quotation_requests.batch_materials', $batch->id) }}" id="addMaterialForm">
                @csrf

                {{-- STEP 1: entry table --}}
                <div id="addStep1">
                    <div style="padding:14px 20px;border-bottom:1px solid rgba(0,0,0,0.07);display:flex;align-items:center;gap:10px;">
                        <label for="addMaterialFactor" style="font-size:12px;font-weight:700;color:var(--muted);white-space:nowrap;">Material Factor</label>
                        <input type="number" name="factor" id="addMaterialFactor" min="0" max="100" step="0.1" value="{{ $materialFactor }}" required oninput="updateAddGrandTotal()"
                               style="width:80px;padding:6px 10px;border:1px solid rgba(0,0,0,0.14);border-radius:6px;font-size:13px;font-weight:900;color:var(--dark);text-align:right;">
                        <span style="font-size:13px;font-weight:700;color:var(--muted);">%</span>
                        <span style="font-size:12px;color:var(--muted);">— applied to all materials in this quotation</span>
                        <label for="addMaterialEntryDate" style="font-size:12px;font-weight:700;color:var(--muted);white-space:nowrap;margin-left:16px;">Entry Date</label>
                        <input type="date" name="entry_date" id="addMaterialEntryDate"
                               style="padding:6px 10px;border:1px solid rgba(0,0,0,0.14);border-radius:6px;font-size:13px;font-weight:600;color:var(--dark);">
                        <span style="font-size:12px;color:var(--muted);">— leave blank for today; set an earlier date when backfilling history</span>
                    </div>
                    <div style="overflow-x:auto;max-height:420px;overflow-y:auto;">
                        <table style="width:100%;border-collapse:collapse;min-width:760px;">
                            <thead style="position:sticky;top:0;z-index:1;">
                                <tr style="background:var(--cream-soft,#f5f5f5);">
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:36px;">#</th>
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);">Material Name <span style="color:var(--danger);">*</span></th>
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:90px;">Unit</th>
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:110px;">Quantity <span style="color:var(--danger);">*</span></th>
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:130px;">Price Per Unit</th>
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:120px;">Total Cost</th>
                                    <th style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="materialRowsContainer"></tbody>
                            <tfoot>
                                <tr style="border-top:2px solid rgba(0,0,0,0.1);background:var(--cream-soft,#f5f5f5);">
                                    <td colspan="5" style="padding:12px;text-align:right;font-size:13px;font-weight:700;color:var(--dark);">Grand Total</td>
                                    <td style="padding:12px;font-size:15px;font-weight:900;color:var(--dark);" id="addGrandTotal">₱0.00</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div style="padding:14px 20px;border-top:1px solid rgba(0,0,0,0.06);display:flex;justify-content:space-between;align-items:center;gap:12px;">
                        <button type="button" id="addMaterialRowBtn"
                                style="display:flex;align-items:center;gap:6px;background:none;border:2px dashed rgba(0,0,0,0.15);border-radius:10px;padding:8px 16px;font-size:13px;font-weight:700;color:var(--muted);cursor:pointer;transition:border-color .2s,color .2s;"
                                onmouseover="this.style.borderColor='var(--dark)';this.style.color='var(--dark)';"
                                onmouseout="this.style.borderColor='rgba(0,0,0,0.15)';this.style.color='var(--muted)';">
                            <i data-lucide="plus" style="width:14px;height:14px;"></i>
                            Add Row
                        </button>
                        <div style="display:flex;gap:10px;">
                            <button type="button" class="cancel-btn" id="cancelAddMaterial">Cancel</button>
                            <button type="button" class="save-btn" id="reviewMaterialsBtn">
                                Review
                                <i data-lucide="arrow-right" style="width:14px;height:14px;margin-left:4px;"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- STEP 2: review summary --}}
                <div id="addStep2" style="display:none;">
                    <div style="padding:18px 20px 12px;display:flex;align-items:center;gap:10px;border-bottom:1px solid rgba(0,0,0,0.07);">
                        <div style="width:34px;height:34px;border-radius:50%;background:var(--dark);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="clipboard-check" style="width:17px;height:17px;color:#fff;"></i>
                        </div>
                        <div>
                            <div style="font-weight:800;font-size:14px;color:var(--dark);">Review Before Saving</div>
                            <div style="font-size:12px;color:var(--muted);">Verify the details below, then confirm to save.</div>
                        </div>
                    </div>

                    <div id="addSummaryContent" style="max-height:400px;overflow-y:auto;">
                        {{-- populated by JS --}}
                    </div>

                    <div style="padding:14px 20px;border-top:1px solid rgba(0,0,0,0.06);display:flex;justify-content:flex-end;gap:10px;">
                        <button type="button" class="cancel-btn" id="backToAddFormBtn">
                            <i data-lucide="arrow-left" style="width:14px;height:14px;margin-right:4px;"></i>
                            Back to Edit
                        </button>
                        <button type="submit" class="save-btn">
                            <i data-lucide="check-circle" style="width:15px;height:15px;"></i>
                            Confirm &amp; Save
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== ADD LABOR MODAL ===================== --}}
    <div class="modal-overlay" id="addLaborModal">
        <div class="modal-card" style="max-width:900px;width:95%;">
            <div class="modal-header">
                <div>
                    <h2>Add Labor</h2>
                    <p>Fill in the rows below — add as many labor entries as needed.</p>
                </div>
                <button class="modal-close" type="button" id="closeAddLaborModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.quotation_requests.batch_labor', $batch->id) }}" id="addLaborForm">
                @csrf

                <div style="padding:16px 20px;display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;">
                    <div class="form-group" style="max-width:200px;margin-bottom:0;">
                        <label>Estimated Working Days <span style="color:var(--danger);">*</span></label>
                        <input type="number" name="estimated_working_days" id="addLaborEstDays" required min="0" step="0.01"
                               value="{{ $batch->estimated_working_days ?? '' }}" oninput="updateAllLaborRowTotals()">
                    </div>
                    <div class="form-group" style="max-width:200px;margin-bottom:0;">
                        <label>Entry Date</label>
                        <input type="date" name="entry_date">
                    </div>
                    <div style="font-size:12px;color:var(--muted);padding-bottom:11px;">Applies to every labor row in this quotation. Leave the date blank for today, or set an earlier one when backfilling history.</div>
                </div>

                <div style="overflow-x:auto;max-height:420px;overflow-y:auto;">
                    <table style="width:100%;border-collapse:collapse;min-width:680px;">
                        <thead style="position:sticky;top:0;z-index:1;">
                            <tr style="background:var(--cream-soft,#f5f5f5);">
                                <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:36px;">#</th>
                                <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);">Employee Name <span style="color:var(--danger);">*</span></th>
                                <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:170px;">Role</th>
                                <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:130px;">Daily Rate <span style="color:var(--danger);">*</span></th>
                                <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:130px;">Total Cost</th>
                                <th style="width:40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="laborRowsContainer">
                            {{-- rows injected by JS --}}
                        </tbody>
                        <tfoot>
                            <tr style="border-top:2px solid rgba(0,0,0,0.1);background:var(--cream-soft,#f5f5f5);">
                                <td colspan="4" style="padding:12px;text-align:right;font-size:13px;font-weight:700;color:var(--dark);">Grand Total</td>
                                <td style="padding:12px;font-size:15px;font-weight:900;color:var(--dark);" id="laborGrandTotal">₱0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div style="padding:14px 20px;border-top:1px solid rgba(0,0,0,0.06);display:flex;justify-content:space-between;align-items:center;gap:12px;">
                    <button type="button" id="addAnotherLaborRowBtn"
                            style="display:flex;align-items:center;gap:6px;background:none;border:2px dashed rgba(0,0,0,0.15);border-radius:10px;padding:8px 16px;font-size:13px;font-weight:700;color:var(--muted);cursor:pointer;transition:border-color .2s,color .2s;"
                            onmouseover="this.style.borderColor='var(--dark)';this.style.color='var(--dark)';"
                            onmouseout="this.style.borderColor='rgba(0,0,0,0.15)';this.style.color='var(--muted)';">
                        <i data-lucide="plus" style="width:14px;height:14px;"></i>
                        Add Row
                    </button>
                    <div style="display:flex;gap:10px;">
                        <button type="button" class="cancel-btn" id="cancelAddLabor">Cancel</button>
                        <button type="submit" class="save-btn">
                            <i data-lucide="check-circle" style="width:15px;height:15px;"></i>
                            Save
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== SEND TO CLIENT MODAL ===================== --}}
    <div class="modal-overlay" id="sendQuotationModal">
        <div class="modal-card" style="max-width:560px;width:95%;">
            <div class="modal-header">
                <div>
                    <h2>Send Quotation to Client</h2>
                    <p>Review the totals below, then send.</p>
                </div>
                <button class="modal-close" type="button" id="closeSendQuotationModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.quotation_requests.batch_send_quotation', $batch->id) }}" enctype="multipart/form-data">
                @csrf

                <div style="border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid var(--border);">
                        <span style="font-size:13px;color:var(--muted);">Project Budget</span>
                        <strong style="font-size:13px;">₱{{ number_format($estimatedBudget['total'], 2) }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid var(--border);">
                        <span style="font-size:12px;color:var(--muted);">— Est. Materials ₱{{ number_format($estimatedBudget['materials'], 2) }} + Est. Labor ₱{{ number_format($estimatedBudget['labor'], 2) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;">
                        <span style="font-size:13px;color:var(--muted);">Markup / Profit</span>
                        <strong style="font-size:13px;">₱{{ number_format($batch->markup ?? 0, 2) }}</strong>
                    </div>
                </div>

                <div style="background:var(--dark);border-radius:10px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <span style="font-size:12px;font-weight:700;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.05em;">Contract Value</span>
                    <span style="font-size:20px;font-weight:900;color:#fff;">₱{{ number_format($estimatedBudget['total'] + (float) ($batch->markup ?? 0), 2) }}</span>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label>Quotation File(s) <span style="color:var(--danger);">*</span></label>
                    <input type="file" name="quotation_files[]" multiple accept=".pdf,image/*" required style="width:100%;">
                    <p style="font-size:12px;color:var(--muted);margin-top:6px;">PDF or image, up to 5 files, max 10MB each — this is what the client will see as the quotation.</p>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelSendQuotation">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="send"></i>
                        Send to Client
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include('admin.partials.material_catalog')

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
    lucide.createIcons();

    var BATCH_MATERIALS = @json($materials->where('status', 'active')->values());
    var BATCH_LABOR     = @json($laborEntries->where('status', 'active')->values());

    function openModal(id) { var m = document.getElementById(id); if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; } }
    function closeModal(id) { var m = document.getElementById(id); if (m) { m.classList.remove('show'); document.body.style.overflow = ''; } }

    // ---- Send to Client ----
    var openSendQBtn = document.getElementById('openSendQuotationModal');
    if (openSendQBtn) openSendQBtn.addEventListener('click', function () {
        // Save whatever's currently in the Markup field first (it may not have been
        // saved yet), then reload and auto-open the modal so it always shows the
        // number that's actually persisted — never a stale or unsaved value.
        document.getElementById('openSendModalFlag').value = '1';
        document.getElementById('markupForm').submit();
    });
    var closeSendQBtn = document.getElementById('closeSendQuotationModal');
    if (closeSendQBtn) closeSendQBtn.addEventListener('click', function () { closeModal('sendQuotationModal'); });
    var cancelSendQBtn = document.getElementById('cancelSendQuotation');
    if (cancelSendQBtn) cancelSendQBtn.addEventListener('click', function () { closeModal('sendQuotationModal'); });

    @if(request('open_send'))
    openModal('sendQuotationModal');
    @endif

    // ---- Markup (standalone container, outside any modal) ----
    var MARKUP_BUDGET = {{ $estimatedBudget['total'] }};
    function updateMarkupPreview() {
        var pct    = parseFloat(document.getElementById('markupInput').value) || 0;
        var markup = MARKUP_BUDGET * pct / 100;
        var total  = MARKUP_BUDGET + markup;
        document.getElementById('markupContractValuePreview').textContent =
            '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // ---- Add Material rows (two-step: entry table -> review; prefilled with existing rows) ----
    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatCurrency(n) {
        if (isNaN(n) || n === 0) return '';
        return '₱' + parseFloat(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // ---- searchable, categorized material combo box ----
    function getUsedMaterialNames(excludeInput) {
        var used = [];
        document.querySelectorAll('#materialRowsContainer .row-mat-name-input').forEach(function (el) {
            if (el === excludeInput) return;
            var val = el.value.trim();
            if (val) used.push(val.toLowerCase());
        });
        return used;
    }

    function renderMatComboList(panel, filterText, onSelect, usedNames) {
        var q = (filterText || '').toLowerCase().trim();
        var html = '';
        var hasMatches = false;
        usedNames = usedNames || [];

        Object.keys(MATERIAL_CATALOG).forEach(function (category) {
            var items = MATERIAL_CATALOG[category].filter(function (name) {
                return !q || name.toLowerCase().indexOf(q) !== -1;
            });
            if (items.length === 0) return;
            hasMatches = true;
            html += '<div class="mat-combo-category">';
            html += '<div class="mat-combo-group">' + escapeHtml(category) + '</div>';
            items.forEach(function (name) {
                var isUsed = usedNames.indexOf(name.toLowerCase()) !== -1;
                if (isUsed) {
                    html += '<div class="mat-combo-item disabled" data-value="' + escapeHtml(name) + '">' +
                                '<span>' + escapeHtml(name) + '</span>' +
                                '<span class="mat-combo-item-badge">Already added</span>' +
                            '</div>';
                } else {
                    html += '<div class="mat-combo-item" data-value="' + escapeHtml(name) + '">' + escapeHtml(name) + '</div>';
                }
            });
            html += '</div>';
        });

        if (!hasMatches) {
            html += '<div class="mat-combo-empty">No matches in the catalog — your typed name will be used as-is.</div>';
        }

        panel.innerHTML = html;
        panel.querySelectorAll('.mat-combo-item').forEach(function (item) {
            if (item.classList.contains('disabled')) return;
            item.addEventListener('mousedown', function (e) {
                e.preventDefault();
                onSelect(item.dataset.value);
            });
        });
    }

    function checkMatDuplicate(input) {
        var wrapper = input.closest('.mat-combo');
        var warning = wrapper.parentNode.querySelector('.mat-combo-warning');
        if (!warning) return;

        var value = input.value.trim().toLowerCase();
        var isDuplicate = value && getUsedMaterialNames(input).indexOf(value) !== -1;

        warning.classList.toggle('show', isDuplicate);
        input.style.outline = isDuplicate ? '2px solid var(--danger)' : '';
        return isDuplicate;
    }

    function positionMatCombo(panel, input) {
        var rect       = input.getBoundingClientRect();
        var maxHeight  = 320;
        var width      = Math.max(rect.width, Math.min(640, window.innerWidth - 32));
        var spaceBelow = window.innerHeight - rect.bottom;
        var spaceAbove = rect.top;

        var left = Math.min(rect.left, window.innerWidth - width - 16);
        left = Math.max(16, left);

        panel.style.left  = left + 'px';
        panel.style.width = width + 'px';

        if (spaceBelow < maxHeight && spaceAbove > spaceBelow) {
            panel.style.top       = '';
            panel.style.bottom    = (window.innerHeight - rect.top + 4) + 'px';
            panel.style.maxHeight = Math.max(120, Math.min(maxHeight, spaceAbove - 12)) + 'px';
        } else {
            panel.style.bottom    = '';
            panel.style.top       = (rect.bottom + 4) + 'px';
            panel.style.maxHeight = Math.max(120, Math.min(maxHeight, spaceBelow - 12)) + 'px';
        }
    }

    function openMatCombo(input) {
        var wrapper = input.closest('.mat-combo');
        var panel   = wrapper.querySelector('.mat-combo-dropdown');
        renderMatComboList(panel, input.value, function (value) {
            input.value = value;
            panel.classList.remove('show');
            checkMatDuplicate(input);
            var row = input.closest('tr');
            if (row) {
                var unitInput = row.querySelector('input[name="unit[]"]');
                if (unitInput && MATERIAL_UNITS[value]) {
                    unitInput.value = MATERIAL_UNITS[value];
                }
            }
            input.focus();
        }, getUsedMaterialNames(input));
        positionMatCombo(panel, input);
        panel.classList.add('show');
    }

    function filterMatCombo(input) {
        openMatCombo(input);
        checkMatDuplicate(input);
    }

    function closeAllMatCombos() {
        document.querySelectorAll('.mat-combo-dropdown.show').forEach(function (p) { p.classList.remove('show'); });
    }

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.mat-combo')) closeAllMatCombos();
    });
    window.addEventListener('scroll', function (e) {
        var t = e.target;
        if (t && t.nodeType === 1 && t.closest('.mat-combo-dropdown')) return;
        closeAllMatCombos();
    }, true);
    window.addEventListener('resize', closeAllMatCombos);
    // ---- end material combo box ----

    function buildMaterialRow(num, data) {
        data = data || {};
        var isExisting = !!data.id;
        var tr = document.createElement('tr');
        tr.className = 'material-add-row';
        tr.style.cssText = 'border-bottom:1px solid rgba(0,0,0,0.06);' + (isExisting ? 'background:var(--cream-soft,#f8f8f6);' : '');

        var removeCell =
            '<div style="display:flex;flex-direction:column;align-items:center;gap:4px;">' +
                (isExisting
                    ? '<span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);">Existing</span>'
                    : '<span style="font-size:10px;visibility:hidden;">Existing</span>') +
                '<button type="button" onclick="removeAddRow(this)" title="' + (isExisting ? 'Delete material' : 'Remove row') + '"' +
                       ' style="background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;color:var(--danger);display:flex;align-items:center;">' +
                    '<i data-lucide="trash-2" style="width:15px;height:15px;"></i>' +
                '</button>' +
            '</div>';

        tr.innerHTML =
            '<input type="hidden" name="material_id[]" value="' + (data.id || '') + '">' +
            '<td style="padding:10px 12px;font-size:12px;font-weight:700;color:var(--muted);vertical-align:top;padding-top:16px;" class="mat-row-label">' + num + '</td>' +
            '<td style="padding:8px 10px;vertical-align:top;min-width:220px;">' +
                '<div class="mat-combo">' +
                    '<input type="text" name="material_name[]" class="row-mat-name-input" required autocomplete="off"' +
                           ' value="' + escapeHtml(data.name || '') + '"' +
                           ' placeholder="Search or type material..."' +
                           ' oninput="filterMatCombo(this)" onfocus="openMatCombo(this)"' +
                           ' style="width:100%;padding:8px 10px;border:1px solid rgba(0,0,0,0.14);border-radius:8px;font-size:13px;box-sizing:border-box;">' +
                    '<div class="mat-combo-dropdown"></div>' +
                '</div>' +
                '<div class="mat-combo-warning">This material is already added in another row.</div>' +
            '</td>' +
            '<td style="padding:8px 10px;vertical-align:top;">' +
                '<input type="text" name="unit[]" class="row-unit" autocomplete="off"' +
                       ' value="' + escapeHtml(data.unit || '') + '"' +
                       ' placeholder="pcs"' +
                       ' style="width:100%;padding:8px 10px;border:1px solid rgba(0,0,0,0.14);border-radius:8px;font-size:13px;">' +
            '</td>' +
            '<td style="padding:8px 10px;vertical-align:top;">' +
                '<input type="number" name="quantity[]" class="row-qty" required min="0.01" step="0.01"' +
                       ' value="' + (data.qty != null ? data.qty : '') + '"' +
                       ' placeholder="0" oninput="updateRowTotal(this)"' +
                       ' style="width:100%;padding:8px 10px;border:1px solid rgba(0,0,0,0.14);border-radius:8px;font-size:13px;">' +
            '</td>' +
            '<td style="padding:8px 10px;vertical-align:top;">' +
                '<input type="number" name="price_per_unit[]" class="row-price" required min="0" step="0.01"' +
                       ' value="' + (data.price != null ? data.price : '') + '"' +
                       ' placeholder="0.00" oninput="updateRowTotal(this)"' +
                       ' style="width:100%;padding:8px 10px;border:1px solid rgba(0,0,0,0.14);border-radius:8px;font-size:13px;">' +
            '</td>' +
            '<td style="padding:8px 10px;vertical-align:top;">' +
                '<input type="text" class="row-total-display" readonly placeholder="—"' +
                       ' style="width:100%;padding:8px 10px;border:1px solid rgba(0,0,0,0.08);border-radius:8px;font-size:13px;font-weight:800;color:var(--dark);background:rgba(0,0,0,0.03);cursor:default;">' +
            '</td>' +
            '<td style="padding:8px 10px;vertical-align:top;text-align:center;">' + removeCell + '</td>';

        var qty   = parseFloat(data.qty)   || 0;
        var price = parseFloat(data.price) || 0;
        var total = qty * price;
        tr.querySelector('.row-total-display').value = total > 0 ? formatCurrency(total) : '';

        return tr;
    }

    function updateRowTotal(input) {
        var row   = input.closest('.material-add-row');
        var qty   = parseFloat(row.querySelector('.row-qty').value)   || 0;
        var price = parseFloat(row.querySelector('.row-price').value) || 0;
        var total = qty * price;
        row.querySelector('.row-total-display').value = total > 0 ? formatCurrency(total) : '';
        updateAddGrandTotal();
    }

    function updateAddGrandTotal() {
        var grand = 0;
        document.querySelectorAll('#materialRowsContainer .material-add-row').forEach(function (row) {
            var qty   = parseFloat(row.querySelector('.row-qty').value)   || 0;
            var price = parseFloat(row.querySelector('.row-price').value) || 0;
            grand += qty * price;
        });
        var factorEl = document.getElementById('addMaterialFactor');
        var factor = parseFloat(factorEl ? factorEl.value : '');
        if (isNaN(factor)) factor = 0;
        grand = grand * (1 + factor / 100);
        var el = document.getElementById('addGrandTotal');
        if (el) el.textContent = grand > 0 ? formatCurrency(grand) : '₱0.00';
    }

    function removeAddRow(btn) {
        var container = document.getElementById('materialRowsContainer');
        if (container.querySelectorAll('.material-add-row').length <= 1) return;
        var row = btn.closest('.material-add-row');
        var idInput = row.querySelector('[name="material_id[]"]');
        if (idInput && idInput.value) {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'delete_material_id[]';
            hidden.value = idInput.value;
            document.getElementById('addMaterialForm').appendChild(hidden);
        }
        row.remove();
        renumberAddRows();
        updateAddGrandTotal();
    }

    function renumberAddRows() {
        document.querySelectorAll('#materialRowsContainer .material-add-row').forEach(function (row, i) {
            var lbl = row.querySelector('.mat-row-label');
            if (lbl) lbl.textContent = i + 1;
        });
    }

    document.getElementById('addMaterialRowBtn').addEventListener('click', function () {
        var container = document.getElementById('materialRowsContainer');
        container.appendChild(buildMaterialRow(container.querySelectorAll('.material-add-row').length + 1));
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    document.getElementById('openAddMaterialModal').addEventListener('click', function () {
        document.getElementById('addStep1').style.display = '';
        document.getElementById('addStep2').style.display = 'none';
        document.querySelectorAll('#addMaterialForm input[name="delete_material_id[]"]').forEach(function (el) { el.remove(); });

        var container = document.getElementById('materialRowsContainer');
        container.innerHTML = '';
        var rowNum = 1;
        BATCH_MATERIALS.forEach(function (mat) {
            container.appendChild(buildMaterialRow(rowNum++, {
                id: mat.id,
                name: mat.material_name,
                qty: mat.quantity,
                price: mat.price_per_unit,
                unit: mat.unit
            }));
        });
        container.appendChild(buildMaterialRow(rowNum++));
        if (typeof lucide !== 'undefined') lucide.createIcons();
        updateAddGrandTotal();

        var factorEl = document.getElementById('addMaterialFactor');
        if (factorEl) {
            var currentFactor = BATCH_MATERIALS.length > 0 ? parseFloat(BATCH_MATERIALS[0].factor) : NaN;
            factorEl.value = isNaN(currentFactor) ? 7 : currentFactor;
        }
        openModal('addMaterialModal');
    });
    document.getElementById('closeAddMaterialModal').addEventListener('click', function () { closeModal('addMaterialModal'); });
    document.getElementById('cancelAddMaterial').addEventListener('click', function () { closeModal('addMaterialModal'); });

    function showAddReview() {
        var rows = document.querySelectorAll('#materialRowsContainer .material-add-row');
        var items = [];
        var valid = true;

        rows.forEach(function (row) {
            row.querySelectorAll('input').forEach(function (el) { el.style.outline = ''; });
            row.querySelectorAll('.mat-combo-warning').forEach(function (el) { el.classList.remove('show'); });
        });

        var seenNames = [];

        var factorEl = document.getElementById('addMaterialFactor');
        if (factorEl) factorEl.style.outline = '';
        var factor = parseFloat(factorEl ? factorEl.value : '');
        if (factorEl && (factorEl.value.trim() === '' || isNaN(factor))) {
            valid = false;
            factorEl.style.outline = '2px solid var(--danger)';
            factor = 0;
        }

        rows.forEach(function (row, i) {
            var idInput = row.querySelector('[name="material_id[]"]');
            var isExisting = !!(idInput && idInput.value);
            var nameInput = row.querySelector('[name="material_name[]"]');
            var name = nameInput ? nameInput.value.trim() : '';
            var qtyEl = row.querySelector('.row-qty');
            var priceEl = row.querySelector('.row-price');
            var unitEl = row.querySelector('.row-unit');
            var qty = parseFloat(qtyEl ? qtyEl.value : '') || 0;
            var price = parseFloat(priceEl ? priceEl.value : '') || 0;
            var unit = unitEl ? unitEl.value.trim() : '';

            if (!name) { valid = false; if (nameInput) nameInput.style.outline = '2px solid var(--danger)'; }
            if (qty <= 0) { valid = false; if (qtyEl) qtyEl.style.outline = '2px solid var(--danger)'; }

            if (name) {
                var lower = name.toLowerCase();
                if (seenNames.indexOf(lower) !== -1) {
                    valid = false;
                    if (nameInput) nameInput.style.outline = '2px solid var(--danger)';
                    var warning = row.querySelector('.mat-combo-warning');
                    if (warning) warning.classList.add('show');
                }
                seenNames.push(lower);
            }

            if (name && qty > 0) {
                items.push({ num: i + 1, name: name, unit: unit, qty: qty, price: price, total: qty * price, existing: isExisting });
            }
        });

        if (!valid || items.length === 0) return;

        var grandTotal = items.reduce(function (sum, it) { return sum + it.total; }, 0) * (1 + factor / 100);
        var newCount = items.filter(function (it) { return !it.existing; }).length;
        var updatedCount = items.length - newCount;

        var thStyle = 'padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);background:var(--cream-soft,#f5f5f5);';
        var html = '<div style="padding:14px 20px 0;font-size:13px;font-weight:700;color:var(--dark);">Material Factor: ' + factor.toFixed(1) + '% <span style="font-size:12px;font-weight:400;color:var(--muted);">(applied to all materials in this quotation)</span></div>';
        html += '<div style="overflow-x:auto;">';
        html += '<table style="width:100%;border-collapse:collapse;min-width:560px;">';
        html += '<thead><tr>';
        html += '<th style="' + thStyle + 'width:36px;">#</th>';
        html += '<th style="' + thStyle + '">Material</th>';
        html += '<th style="' + thStyle + 'width:70px;">Unit</th>';
        html += '<th style="' + thStyle + 'width:100px;">Quantity</th>';
        html += '<th style="' + thStyle + 'width:120px;">Price / Unit</th>';
        html += '<th style="' + thStyle + 'width:120px;">Total Cost</th>';
        html += '<th style="' + thStyle + 'width:80px;">Status</th>';
        html += '</tr></thead><tbody>';

        items.forEach(function (item) {
            var badge = item.existing
                ? '<span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:rgba(0,0,0,0.06);color:var(--muted);">Existing</span>'
                : '<span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:var(--accent-soft,#e6f4ea);color:var(--success,#2e7d32);">New</span>';
            html += '<tr style="border-bottom:1px solid rgba(0,0,0,0.06);">';
            html += '<td style="padding:10px 12px;font-size:12px;font-weight:700;color:var(--muted);">' + item.num + '</td>';
            html += '<td style="padding:10px 12px;"><div style="font-weight:700;font-size:13px;color:var(--dark);">' + escapeHtml(item.name) + '</div></td>';
            html += '<td style="padding:10px 12px;font-size:12.5px;color:var(--muted);">' + (item.unit ? escapeHtml(item.unit) : '—') + '</td>';
            html += '<td style="padding:10px 12px;font-size:13px;">' + item.qty.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>';
            html += '<td style="padding:10px 12px;font-size:13px;">₱' + item.price.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>';
            html += '<td style="padding:10px 12px;font-size:13px;font-weight:800;color:var(--dark);">₱' + item.total.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>';
            html += '<td style="padding:10px 12px;">' + badge + '</td>';
            html += '</tr>';
        });

        var summaryLabel = 'Grand Total';
        if (newCount > 0 && updatedCount > 0) {
            summaryLabel += ' (' + newCount + ' new, ' + updatedCount + ' updated)';
        } else if (newCount > 0) {
            summaryLabel += ' (' + newCount + ' new material' + (newCount !== 1 ? 's' : '') + ')';
        } else {
            summaryLabel += ' (' + updatedCount + ' material' + (updatedCount !== 1 ? 's' : '') + ' updated)';
        }

        html += '<tr style="border-top:2px solid rgba(0,0,0,0.1);background:var(--cream-soft,#f5f5f5);">';
        html += '<td colspan="4" style="padding:12px;font-size:13px;font-weight:700;color:var(--dark);text-align:right;">' + summaryLabel + '</td>';
        html += '<td style="padding:12px;font-size:15px;font-weight:900;color:var(--dark);" colspan="2">₱' + grandTotal.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>';
        html += '</tr></tbody></table></div>';

        var deleteCount = document.querySelectorAll('#addMaterialForm input[name="delete_material_id[]"]').length;
        if (deleteCount > 0) {
            html += '<div style="padding:12px 20px;font-size:12px;font-weight:700;color:var(--danger);">' +
                deleteCount + ' material' + (deleteCount !== 1 ? 's' : '') + ' will be permanently deleted from this quotation.' +
            '</div>';
        }

        document.getElementById('addSummaryContent').innerHTML = '<div style="padding:0 0 4px;">' + html + '</div>';
        document.getElementById('addStep1').style.display = 'none';
        document.getElementById('addStep2').style.display = '';
    }

    function backToAddForm() {
        document.getElementById('addStep2').style.display = 'none';
        document.getElementById('addStep1').style.display = '';
    }

    document.getElementById('reviewMaterialsBtn').addEventListener('click', showAddReview);
    document.getElementById('backToAddFormBtn').addEventListener('click', backToAddForm);

    // ---- Add Labor rows ----
    // ---- Add Labor rows (existing entries shown read-only for context, new rows pick a real employee) ----
    var LABOR_EMPLOYEES  = @json($regularEmployees->map(function ($emp) {
        return ['name' => $emp->name, 'role' => $emp->role, 'daily_rate' => $emp->daily_rate ?? 0];
    })->values());
    var KNOWN_LABOR_ROLES = ['Fabricator', 'Welder', 'Helper/Labor', 'Outsourced'];

    function buildLaborEmployeeOptions(selectedValue, usedNames) {
        usedNames = usedNames || [];
        selectedValue = selectedValue || '';
        var html = '<option value="" disabled' + (selectedValue ? '' : ' selected') + ' hidden>Select Employee</option>';
        LABOR_EMPLOYEES.forEach(function (emp) {
            var isUsed = usedNames.indexOf(emp.name) !== -1 && emp.name !== selectedValue;
            html += '<option value="' + escapeHtml(emp.name) + '"' +
                        ' data-role="' + escapeHtml(emp.role || '') + '" data-rate="' + (emp.daily_rate || 0) + '"' +
                        (emp.name === selectedValue ? ' selected' : '') +
                        (isUsed ? ' disabled' : '') + '>' +
                        escapeHtml(emp.name) + (isUsed ? ' — Already added' : '') +
                    '</option>';
        });
        html += '<option value="other">Other (type manually)...</option>';
        return html;
    }

    function buildLaborRoleOptions() {
        var html = '<option value="" disabled selected hidden>Select Role</option>';
        KNOWN_LABOR_ROLES.forEach(function (role) {
            html += '<option value="' + escapeHtml(role) + '">' + escapeHtml(role) + '</option>';
        });
        html += '<option value="other">Other...</option>';
        return html;
    }

    // ---- prevent the same employee being selected in more than one labor row ----
    function getUsedLaborEmployeeNames(excludeSelect) {
        var used = [];
        document.querySelectorAll('#laborRowsContainer .row-labor-name-select').forEach(function (sel) {
            if (sel === excludeSelect) return;
            if (sel.value && sel.value !== 'other') used.push(sel.value);
        });
        document.querySelectorAll('#laborRowsContainer .labor-existing-row').forEach(function (row) {
            if (row.dataset.employeeName) used.push(row.dataset.employeeName);
        });
        return used;
    }

    function refreshAllLaborEmployeeOptions() {
        document.querySelectorAll('#laborRowsContainer .row-labor-name-select').forEach(function (sel) {
            var currentValue = sel.value;
            var used = getUsedLaborEmployeeNames(sel);
            sel.innerHTML = buildLaborEmployeeOptions(currentValue, used);
            sel.value = currentValue;
        });
    }

    function buildExistingLaborRow(num, entry) {
        var tr = document.createElement('tr');
        tr.className = 'labor-existing-row';
        tr.style.cssText = 'border-bottom:1px solid rgba(0,0,0,0.06);background:var(--cream-soft,#f8f8f6);';

        var desc = entry.description || '';
        var m = desc.match(/^(.+?)\s*\((.+?)\)\s*$/);
        var name = m ? m[1].trim() : desc;
        var role = m ? m[2].trim() : '';
        tr.dataset.employeeName = name;

        tr.innerHTML =
            '<td style="padding:10px 12px;font-size:12px;font-weight:700;color:var(--muted);vertical-align:top;padding-top:16px;" class="labor-row-label">' + num + '</td>' +
            '<td style="padding:10px 12px;vertical-align:top;"><div style="font-weight:700;font-size:13px;color:var(--dark);">' + escapeHtml(name) + '</div></td>' +
            '<td style="padding:10px 12px;vertical-align:top;font-size:13px;color:var(--muted);">' + (role ? escapeHtml(role) : '—') + '</td>' +
            '<td style="padding:10px 12px;vertical-align:top;font-size:13px;color:var(--dark);">₱' + parseFloat(entry.daily_rate || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>' +
            '<td style="padding:10px 12px;vertical-align:top;font-size:13px;font-weight:800;color:var(--dark);">₱' + parseFloat(entry.total_cost || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>' +
            '<td style="padding:10px 12px;vertical-align:top;text-align:center;">' +
                '<span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:rgba(0,0,0,0.06);color:var(--muted);">Existing</span>' +
            '</td>';
        return tr;
    }

    function buildLaborRow(num) {
        var tr = document.createElement('tr');
        tr.className = 'labor-add-row';
        tr.style.cssText = 'border-bottom:1px solid rgba(0,0,0,0.06);';
        tr.innerHTML =
            '<td style="padding:10px 12px;font-size:12px;font-weight:700;color:var(--muted);vertical-align:top;padding-top:16px;" class="labor-row-label">' + num + '</td>' +
            '<td style="padding:8px 10px;vertical-align:top;min-width:180px;">' +
                '<select name="employee_name[]" class="row-labor-name-select" required onchange="onLaborEmployeeChange(this)"' +
                    ' style="width:100%;padding:8px 10px;border:1px solid rgba(0,0,0,0.14);border-radius:8px;font-size:13px;background:#fff;">' +
                    buildLaborEmployeeOptions('', getUsedLaborEmployeeNames()) +
                '</select>' +
                '<input type="text" class="row-labor-name-custom" name="_emp_unused"' +
                       ' placeholder="Employee Name"' +
                       ' style="display:none;margin-top:6px;width:100%;padding:8px 10px;border:1px solid rgba(0,0,0,0.14);border-radius:8px;font-size:13px;box-sizing:border-box;">' +
            '</td>' +
            '<td style="padding:8px 10px;vertical-align:top;">' +
                '<select name="role[]" class="row-labor-role-select" onchange="toggleRowLaborRole(this)"' +
                    ' style="width:100%;padding:8px 10px;border:1px solid rgba(0,0,0,0.14);border-radius:8px;font-size:13px;background:#fff;">' +
                    buildLaborRoleOptions() +
                '</select>' +
                '<input type="text" class="row-labor-role-custom" name="_role_unused"' +
                       ' placeholder="Role"' +
                       ' style="display:none;margin-top:6px;width:100%;padding:8px 10px;border:1px solid rgba(0,0,0,0.14);border-radius:8px;font-size:13px;box-sizing:border-box;">' +
            '</td>' +
            '<td style="padding:8px 10px;vertical-align:top;">' +
                '<input type="number" name="daily_rate[]" class="row-labor-rate" required min="0" step="0.01" readonly' +
                       ' placeholder="0.00" oninput="updateLaborRowTotal(this)"' +
                       ' style="width:100%;padding:8px 10px;border:1px solid rgba(0,0,0,0.08);border-radius:8px;font-size:13px;box-sizing:border-box;background:rgba(0,0,0,0.03);cursor:default;">' +
            '</td>' +
            '<td style="padding:8px 10px;vertical-align:top;">' +
                '<input type="text" class="row-labor-total-display" readonly placeholder="—"' +
                       ' style="width:100%;padding:8px 10px;border:1px solid rgba(0,0,0,0.08);border-radius:8px;font-size:13px;font-weight:800;color:var(--dark);background:rgba(0,0,0,0.03);cursor:default;box-sizing:border-box;">' +
            '</td>' +
            '<td style="padding:8px 10px;vertical-align:top;text-align:center;">' +
                '<button type="button" onclick="removeLaborRow(this)" title="Remove row"' +
                       ' style="background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;color:var(--danger);display:flex;align-items:center;">' +
                    '<i data-lucide="trash-2" style="width:15px;height:15px;"></i>' +
                '</button>' +
            '</td>';
        return tr;
    }

    function onLaborEmployeeChange(sel) {
        var row        = sel.closest('.labor-add-row');
        var nameCustom = row.querySelector('.row-labor-name-custom');
        var roleSelect = row.querySelector('.row-labor-role-select');
        var roleCustom = row.querySelector('.row-labor-role-custom');
        var rateInput  = row.querySelector('.row-labor-rate');

        if (sel.value === 'other') {
            nameCustom.style.display = 'block';
            nameCustom.required = true;
            nameCustom.value = '';
            nameCustom.name = 'employee_name[]';
            sel.removeAttribute('name');

            rateInput.readOnly = false;
            rateInput.style.background = '#fff';
            rateInput.style.cursor = 'text';
            rateInput.value = '';
            updateLaborRowTotal(rateInput);
            refreshAllLaborEmployeeOptions();
            return;
        }

        nameCustom.style.display = 'none';
        nameCustom.required = false;
        nameCustom.name = '_emp_unused';
        sel.name = 'employee_name[]';

        var opt  = sel.options[sel.selectedIndex];
        var role = opt.dataset.role || '';
        var rate = opt.dataset.rate || 0;

        if (KNOWN_LABOR_ROLES.indexOf(role) !== -1) {
            roleSelect.value = role;
            roleCustom.style.display = 'none';
            roleCustom.name = '_role_unused';
            roleSelect.name = 'role[]';
        } else if (role) {
            roleSelect.value = 'other';
            roleCustom.style.display = 'block';
            roleCustom.value = role;
            roleCustom.name = 'role[]';
            roleSelect.removeAttribute('name');
        } else {
            roleSelect.value = '';
            roleCustom.style.display = 'none';
            roleCustom.name = '_role_unused';
            roleSelect.name = 'role[]';
        }

        rateInput.readOnly = true;
        rateInput.style.background = 'rgba(0,0,0,0.03)';
        rateInput.style.cursor = 'default';
        rateInput.value = rate;
        updateLaborRowTotal(rateInput);
        refreshAllLaborEmployeeOptions();
    }

    function toggleRowLaborRole(sel) {
        var row    = sel.closest('.labor-add-row');
        var custom = row.querySelector('.row-labor-role-custom');
        if (sel.value === 'other') {
            custom.style.display = 'block';
            custom.value = '';
            custom.name = 'role[]';
            sel.removeAttribute('name');
        } else {
            custom.style.display = 'none';
            custom.name = '_role_unused';
            sel.name = 'role[]';
        }
    }

    function getAddLaborEstDays() {
        var el = document.getElementById('addLaborEstDays');
        return el ? (parseFloat(el.value) || 0) : 0;
    }

    function updateLaborRowTotal(input) {
        var row   = input.closest('.labor-add-row');
        var rate  = parseFloat(row.querySelector('.row-labor-rate').value) || 0;
        var total = rate * getAddLaborEstDays();
        row.querySelector('.row-labor-total-display').value = total > 0 ? formatCurrency(total) : '';
        updateLaborGrandTotal();
    }

    function updateLaborGrandTotal() {
        var grand = 0;
        var days = getAddLaborEstDays();
        document.querySelectorAll('#laborRowsContainer .labor-add-row').forEach(function (row) {
            var rate = parseFloat(row.querySelector('.row-labor-rate').value) || 0;
            grand += rate * days;
        });
        var el = document.getElementById('laborGrandTotal');
        if (el) el.textContent = grand > 0 ? formatCurrency(grand) : '₱0.00';
    }

    function updateAllLaborRowTotals() {
        document.querySelectorAll('#laborRowsContainer .labor-add-row .row-labor-rate').forEach(function (rateInput) {
            updateLaborRowTotal(rateInput);
        });
        updateLaborGrandTotal();
    }

    function removeLaborRow(btn) {
        var container = document.getElementById('laborRowsContainer');
        if (container.querySelectorAll('.labor-add-row').length <= 1) return;
        btn.closest('.labor-add-row').remove();
        renumberLaborRows();
        updateLaborGrandTotal();
        refreshAllLaborEmployeeOptions();
    }

    function renumberLaborRows() {
        document.querySelectorAll('#laborRowsContainer tr').forEach(function (row, i) {
            var lbl = row.querySelector('.labor-row-label');
            if (lbl) lbl.textContent = i + 1;
        });
    }

    document.getElementById('addAnotherLaborRowBtn').addEventListener('click', function () {
        var container = document.getElementById('laborRowsContainer');
        var newRow = buildLaborRow(container.querySelectorAll('tr').length + 1);
        container.appendChild(newRow);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    document.getElementById('openAddLaborModal').addEventListener('click', function () {
        var container = document.getElementById('laborRowsContainer');
        container.innerHTML = '';

        var rowNum = 1;
        BATCH_LABOR.forEach(function (entry) {
            container.appendChild(buildExistingLaborRow(rowNum++, entry));
        });
        container.appendChild(buildLaborRow(rowNum++));

        if (typeof lucide !== 'undefined') lucide.createIcons();
        var estDaysEl = document.getElementById('addLaborEstDays');
        if (estDaysEl) estDaysEl.value = {{ (float) ($batch->estimated_working_days ?? 0) }};
        var grandEl = document.getElementById('laborGrandTotal');
        if (grandEl) grandEl.textContent = '₱0.00';
        openModal('addLaborModal');
    });
    document.getElementById('closeAddLaborModal').addEventListener('click', function () { closeModal('addLaborModal'); });
    document.getElementById('cancelAddLabor').addEventListener('click', function () { closeModal('addLaborModal'); });
    </script>
</body>
</html>
