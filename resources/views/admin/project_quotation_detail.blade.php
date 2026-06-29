<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project->name }} — Materials | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
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
            background: #0E1428;
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
        #materialsTable th.num-cell,
        #materialsTable td.num-cell,
        #laborTable th.num-cell,
        #laborTable td.num-cell {
            text-align: right;
        }
        #materialsTable tfoot td,
        #laborTable tfoot td {
            border-bottom: none;
            border-top: 2px solid var(--border);
            padding-top: 14px;
            padding-bottom: 14px;
        }
        .table-total-label {
            text-align: right;
            font-weight: 800;
            color: var(--muted);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: .06em;
        }
        .table-total-value {
            text-align: right;
            font-weight: 900;
            color: var(--dark);
            font-size: 15px;
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
                <a href="{{ route('admin.project_materials') }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    Project Materials
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="color:var(--dark);font-weight:700;">{{ $project->name }}</span>
            </div>

            <div class="page-header">
                <div>
                    <h1>{{ $project->name }}</h1>
                    <p>Bill of Materials — manage all materials and costs for this project.</p>
                </div>
                <button class="add-btn" type="button" id="openBOMModal">
                    <i data-lucide="file-text"></i>
                    Generate Project Quotations
                </button>
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


            {{-- Summary Cards --}}
            <div class="page-grid" style="margin-bottom:16px;">
                <div class="info-card blue">
                    <div class="info-card-icon blue"><i data-lucide="package"></i></div>
                    <h3>Total Materials</h3>
                    <div class="value">{{ $totalMaterials }}</div>
                    <div class="info-card-sub">Active material entries</div>
                </div>
                <div class="info-card purple">
                    <div class="info-card-icon purple"><i data-lucide="layers"></i></div>
                    <h3>Total Quantity</h3>
                    <div class="value">{{ number_format($totalQuantity, 0) }}</div>
                    <div class="info-card-sub">Combined units</div>
                </div>
                <div class="info-card green">
                    <div class="info-card-icon green"><i data-lucide="banknote"></i></div>
                    <h3>Material Cost</h3>
                    <div class="value" style="font-size:1.4rem;">₱{{ number_format($estimatedCost, 2) }}</div>
                    <div class="info-card-sub">Total material cost</div>
                </div>
            </div>

            {{-- Labor Summary Cards --}}
            <div class="page-grid" style="margin-bottom:24px;">
                <div class="info-card orange">
                    <div class="info-card-icon orange"><i data-lucide="hard-hat"></i></div>
                    <h3>Labor Entries</h3>
                    <div class="value">{{ $totalLaborEntries }}</div>
                    <div class="info-card-sub">Total labor entries</div>
                </div>
                <div class="info-card teal">
                    <div class="info-card-icon teal"><i data-lucide="calendar-days"></i></div>
                    <h3>Estimated Working Days</h3>
                    <div class="value">{{ number_format($project->estimated_working_days, 0) }} Days</div>
                    <div class="info-card-sub">Applies to all employees</div>
                </div>
                <div class="info-card pink">
                    <div class="info-card-icon pink"><i data-lucide="wallet"></i></div>
                    <h3>Labor Cost</h3>
                    <div class="value" style="font-size:1.4rem;">₱{{ number_format($totalLaborCost, 2) }}</div>
                    <div class="info-card-sub">Total labor cost</div>
                </div>
            </div>

            {{-- Materials Table --}}
            <div class="table-card">
                <div class="table-toolbar">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:nowrap;">
                        <div class="search-box" style="width:auto;flex:0 1 320px;">
                            <i data-lucide="search"></i>
                            <input type="text" id="materialSearch" placeholder="Search material name...">
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;padding:0 16px;height:48px;background-color:var(--cream-soft);border:1px solid var(--border);border-radius:16px;font-size:13px;font-weight:700;color:var(--dark);white-space:nowrap;flex:0 0 auto;">
                            <i data-lucide="percent" style="width:16px;height:16px;color:var(--muted);"></i>
                            Material Factor: {{ number_format($materialFactor, 1) }}%
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <button type="button" id="openMaterialsBOMModal"
                                style="display:flex;align-items:center;gap:7px;background:none;border:1.5px solid rgba(0,0,0,0.18);border-radius:10px;padding:8px 16px;font-size:13px;font-weight:700;color:var(--dark);cursor:pointer;white-space:nowrap;">
                            <i data-lucide="file-text" style="width:15px;height:15px;"></i>
                            Generate BOM
                        </button>
                        <button type="button" id="openAddMaterialModal"
                                style="display:flex;align-items:center;gap:7px;background:var(--dark);color:#fff;border:none;border-radius:10px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;">
                            <i data-lucide="plus" style="width:15px;height:15px;"></i>
                            Add Material
                        </button>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="materialsTable">
                        <thead>
                            <tr>
                                <th>Material Name</th>
                                <th class="num-cell">Quantity</th>
                                <th class="num-cell">Price Per Unit</th>
                                <th class="num-cell">Total Cost</th>
                                <th>Date Added</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($materials as $material)
                            <tr data-mat-status="{{ $material->status }}">
                                <td><strong>{{ $material->material_name }}</strong>
                                    @if($material->notes)
                                    <div style="font-size:12px;color:var(--muted);margin-top:2px;">{{ Str::limit($material->notes, 60) }}</div>
                                    @endif
                                </td>
                                <td class="num-cell">{{ number_format($material->quantity, 0) }}</td>
                                <td class="num-cell">₱{{ number_format($material->price_per_unit, 2) }}</td>
                                <td class="num-cell"><strong>₱{{ number_format($material->total_cost, 2) }}</strong></td>
                                <td>{{ $material->created_at->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr id="emptyRow">
                                <td colspan="5" style="text-align:center;padding:40px;color:var(--muted);">
                                    No materials added yet. Click <strong>Add Material</strong> to get started.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($materials->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="3" class="table-total-label">Grand Total</td>
                                <td class="table-total-value">₱{{ number_format($estimatedCost, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Labor Costs Table --}}
            <div class="table-card" style="margin-top:24px;">
                <div class="table-toolbar">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <i data-lucide="hard-hat" style="width:16px;height:16px;color:var(--muted);"></i>
                        <span style="font-size:14px;font-weight:800;color:var(--dark);">Labor Costs</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div class="search-box">
                            <i data-lucide="search"></i>
                            <input type="text" id="laborSearch" placeholder="Search labor...">
                        </div>
                        <button type="button" id="openAddLaborModalBtn"
                                style="display:flex;align-items:center;gap:7px;background:var(--dark);color:#fff;border:none;border-radius:10px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;">
                            <i data-lucide="plus" style="width:15px;height:15px;"></i>
                            Add Labor
                        </button>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="laborTable">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Role</th>
                                <th class="num-cell">Daily Rate</th>
                                <th class="num-cell">Estimated Working Days</th>
                                <th class="num-cell">Total Cost</th>
                                <th>Date Added</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($laborEntries as $entry)
                            @php
                                // Parse "Name (Role)" → separate name and role
                                $empName = $entry->description;
                                $empRole = '';
                                if (preg_match('/^(.+?)\s*\((.+?)\)$/', $entry->description, $m)) {
                                    $empName = trim($m[1]);
                                    $empRole = trim($m[2]);
                                }
                            @endphp
                            <tr data-labor-status="{{ $entry->status }}">
                                <td>
                                    <strong>{{ $empName }}</strong>
                                    @if($entry->notes)
                                    <div style="font-size:12px;color:var(--muted);margin-top:2px;">{{ Str::limit($entry->notes, 60) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($empRole)
                                        <span style="font-size:12px;font-weight:700;background:var(--cream-soft);color:var(--dark);padding:3px 9px;border-radius:6px;white-space:nowrap;">
                                            {{ $empRole }}
                                        </span>
                                    @else
                                        <span style="color:var(--muted);">—</span>
                                    @endif
                                </td>
                                <td class="num-cell">₱{{ number_format($entry->daily_rate, 2) }}</td>
                                <td class="num-cell">{{ number_format($project->estimated_working_days, 0) }}</td>
                                <td class="num-cell"><strong>₱{{ number_format($entry->total_cost, 2) }}</strong></td>
                                <td>{{ $entry->created_at->format('M d, Y') }}</td>
                                <td class="action-cell">
                                    @if($entry->status === 'active')
                                    <button class="action-btn view edit-labor-btn" type="button"
                                        title="Edit"
                                        data-id="{{ $entry->id }}"
                                        data-description="{{ $entry->description }}"
                                        data-daily-rate="{{ $entry->daily_rate ?? ($entry->rate_per_hour * 8) }}"
                                        data-notes="{{ $entry->notes }}">
                                        <i data-lucide="pencil"></i>
                                    </button>
                                    @endif
                                    <button class="action-btn view archive-labor-btn" type="button"
                                        title="{{ $entry->status === 'archived' ? 'Restore' : 'Archive' }}"
                                        data-id="{{ $entry->id }}"
                                        data-description="{{ $entry->description }}"
                                        data-archived="{{ $entry->status === 'archived' ? '1' : '0' }}">
                                        <i data-lucide="{{ $entry->status === 'archived' ? 'archive-restore' : 'archive' }}"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">
                                    No labor entries yet. Click <strong>Add Labor</strong> to get started.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($laborEntries->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="4" class="table-total-label">Grand Total</td>
                                <td class="table-total-value">₱{{ number_format($totalLaborCost, 2) }}</td>
                                <td colspan="2"></td>
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

            <form method="POST" action="{{ route('admin.project_materials.store', $project->id) }}" id="addMaterialForm">
                @csrf

                {{-- STEP 1: entry table --}}
                <div id="addStep1">
                    <div style="padding:14px 20px;border-bottom:1px solid rgba(0,0,0,0.07);display:flex;align-items:center;gap:10px;">
                        <label for="addMaterialFactor" style="font-size:12px;font-weight:700;color:var(--muted);white-space:nowrap;">Material Factor</label>
                        <input type="number" name="factor" id="addMaterialFactor" min="0" max="100" step="0.1" value="7" oninput="updateAddGrandTotal()"
                               style="width:80px;padding:6px 10px;border:1px solid rgba(0,0,0,0.14);border-radius:6px;font-size:13px;font-weight:900;color:var(--dark);text-align:right;">
                        <span style="font-size:13px;font-weight:700;color:var(--muted);">%</span>
                        <span style="font-size:12px;color:var(--muted);">— applied to all materials in this project</span>
                    </div>
                    <div style="overflow-x:auto;max-height:420px;overflow-y:auto;">
                        <table style="width:100%;border-collapse:collapse;min-width:680px;">
                            <thead style="position:sticky;top:0;z-index:1;">
                                <tr style="background:var(--cream-soft,#f5f5f5);">
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:36px;">#</th>
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);">Material Name <span style="color:var(--danger);">*</span></th>
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:110px;">Quantity <span style="color:var(--danger);">*</span></th>
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:130px;">Price Per Unit</th>
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:120px;">Total Cost</th>
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);">Notes</th>
                                    <th style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="materialRowsContainer">
                                {{-- rows injected by JS --}}
                            </tbody>
                            <tfoot>
                                <tr style="border-top:2px solid rgba(0,0,0,0.1);background:var(--cream-soft,#f5f5f5);">
                                    <td colspan="4" style="padding:12px;text-align:right;font-size:13px;font-weight:700;color:var(--dark);">Grand Total</td>
                                    <td style="padding:12px;font-size:15px;font-weight:900;color:var(--dark);" id="addGrandTotal">₱0.00</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div style="padding:14px 20px;border-top:1px solid rgba(0,0,0,0.06);display:flex;justify-content:space-between;align-items:center;gap:12px;">
                        <button type="button" id="addAnotherRowBtn"
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

    {{-- ===================== GENERATE BOM MODAL ===================== --}}
    <div class="modal-overlay" id="bomModal">
        <div class="modal-card" style="max-width:980px;width:95%;">
            <div class="modal-header">
                <div>
                    <h2>Generate Project Quotations</h2>
                    <p>Adjust factors for materials and labor, then print or send to the client.</p>
                    <div style="display:inline-flex;align-items:center;gap:6px;margin-top:8px;padding:5px 12px;background:var(--cream-soft,#f5f5f5);border:1px solid var(--border);border-radius:999px;font-size:12px;font-weight:700;color:var(--dark);">
                        <i data-lucide="percent" style="width:13px;height:13px;color:var(--muted);"></i>
                        Material Factor: {{ number_format($materialFactor, 1) }}%
                    </div>
                </div>
                <button class="modal-close" type="button" id="closeBOMModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            {{-- Scrollable tables --}}
            <div style="max-height:440px;overflow-y:auto;overflow-x:auto;">
                {{-- Materials section --}}
                <div style="padding:10px 16px 4px;background:var(--cream-soft,#f5f5f5);border-bottom:1px solid rgba(0,0,0,0.07);display:flex;align-items:center;gap:8px;">
                    <i data-lucide="package" style="width:14px;height:14px;color:var(--muted);"></i>
                    <span style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);">Materials</span>
                    <span style="margin-left:auto;font-size:12px;font-weight:700;color:var(--dark);">Subtotal: <strong id="bomMatSubtotal">₱0.00</strong></span>
                </div>
                <table style="width:100%;border-collapse:collapse;min-width:680px;">
                    <thead>
                        <tr style="background:var(--cream-soft,#f5f5f5);">
                            <th style="padding:9px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:36px;">#</th>
                            <th style="padding:9px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);">Material Name</th>
                            <th style="padding:9px 12px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:80px;">Qty</th>
                            <th style="padding:9px 12px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:110px;">Price/Unit</th>
                            <th style="padding:9px 12px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:110px;">Base Total</th>
                            <th style="padding:9px 12px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:80px;">Factor</th>
                            <th style="padding:9px 12px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--dark);width:120px;">Adjusted Total</th>
                        </tr>
                    </thead>
                    <tbody id="bomTableBody"></tbody>
                </table>

                {{-- Labor section --}}
                <div style="padding:10px 16px 4px;background:var(--cream-soft,#f5f5f5);border-top:2px solid rgba(0,0,0,0.08);border-bottom:1px solid rgba(0,0,0,0.07);display:flex;align-items:center;gap:8px;">
                    <i data-lucide="hard-hat" style="width:14px;height:14px;color:var(--muted);"></i>
                    <span style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);">Labor</span>
                    <span style="margin-left:auto;font-size:12px;font-weight:700;color:var(--dark);">Subtotal: <strong id="bomLaborSubtotal">₱0.00</strong></span>
                </div>
                <table style="width:100%;border-collapse:collapse;min-width:680px;">
                    <thead>
                        <tr style="background:var(--cream-soft,#f5f5f5);">
                            <th style="padding:9px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:36px;">#</th>
                            <th style="padding:9px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);">Role / Description</th>
                            <th style="padding:9px 12px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:90px;">Daily Rate</th>
                            <th style="padding:9px 12px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:110px;">Est. Days</th>
                            <th style="padding:9px 12px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--dark);width:110px;">Total</th>
                        </tr>
                    </thead>
                    <tbody id="bomLaborTableBody"></tbody>
                </table>

                {{-- Grand Total row --}}
                <div style="padding:14px 16px;background:var(--dark);display:flex;justify-content:flex-end;align-items:center;gap:16px;">
                    <span style="font-size:13px;font-weight:700;color:rgba(255,255,255,0.7);">Project Grand Total</span>
                    <span id="bomGrandTotal" style="font-size:18px;font-weight:900;color:#fff;">₱0.00</span>
                </div>
            </div>

            {{-- Actions --}}
            <div style="padding:14px 20px;border-top:1px solid rgba(0,0,0,0.07);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                <button type="button" class="cancel-btn" id="cancelBOMModal">Close</button>
                <div style="display:flex;gap:10px;">
                    <button type="button" id="printBOMBtn"
                            style="display:flex;align-items:center;gap:7px;background:none;border:1.5px solid rgba(0,0,0,0.18);border-radius:10px;padding:8px 18px;font-size:13px;font-weight:700;color:var(--dark);cursor:pointer;">
                        <i data-lucide="printer" style="width:15px;height:15px;"></i>
                        Print / Save PDF
                    </button>
                    <button type="button" id="downloadBOMBtn" class="save-btn">
                        <i data-lucide="download" style="width:15px;height:15px;"></i>
                        Download
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== GENERATE BOM (MATERIALS ONLY) MODAL ===================== --}}
    <div class="modal-overlay" id="materialsBOMModal">
        <div class="modal-card" style="max-width:900px;width:95%;">
            <div class="modal-header">
                <div>
                    <h2>Generate BOM</h2>
                    <p>Bill of Materials — materials only. Print or save as PDF.</p>
                    <div style="display:inline-flex;align-items:center;gap:6px;margin-top:8px;padding:5px 12px;background:var(--cream-soft,#f5f5f5);border:1px solid var(--border);border-radius:999px;font-size:12px;font-weight:700;color:var(--dark);">
                        <i data-lucide="percent" style="width:13px;height:13px;color:var(--muted);"></i>
                        Material Factor: {{ number_format($materialFactor, 1) }}%
                    </div>
                </div>
                <button class="modal-close" type="button" id="closeMaterialsBOMModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            {{-- Scrollable table --}}
            <div style="max-height:440px;overflow-y:auto;overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;min-width:680px;">
                    <thead>
                        <tr style="background:var(--cream-soft,#f5f5f5);">
                            <th style="padding:9px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:36px;">#</th>
                            <th style="padding:9px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);">Material Name</th>
                            <th style="padding:9px 12px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:80px;">Qty</th>
                            <th style="padding:9px 12px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:110px;">Price/Unit</th>
                            <th style="padding:9px 12px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:110px;">Base Total</th>
                            <th style="padding:9px 12px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--dark);width:120px;">Adjusted Total</th>
                        </tr>
                    </thead>
                    <tbody id="matBomTableBody"></tbody>
                </table>

                {{-- Grand Total row --}}
                <div style="padding:14px 16px;background:var(--dark);display:flex;justify-content:flex-end;align-items:center;gap:16px;">
                    <span style="font-size:13px;font-weight:700;color:rgba(255,255,255,0.7);">Materials Total</span>
                    <span id="matBomGrandTotal" style="font-size:18px;font-weight:900;color:#fff;">₱0.00</span>
                </div>
            </div>

            {{-- Actions --}}
            <div style="padding:14px 20px;border-top:1px solid rgba(0,0,0,0.07);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                <button type="button" class="cancel-btn" id="cancelMaterialsBOMModal">Close</button>
                <button type="button" id="printMaterialsBOMBtn"
                        style="display:flex;align-items:center;gap:7px;background:none;border:1.5px solid rgba(0,0,0,0.18);border-radius:10px;padding:8px 18px;font-size:13px;font-weight:700;color:var(--dark);cursor:pointer;">
                    <i data-lucide="printer" style="width:15px;height:15px;"></i>
                    Print / Save PDF
                </button>
            </div>
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

            <form method="POST" action="{{ route('admin.project_materials.store_labor', $project->id) }}" id="addLaborForm">
                @csrf

                <div style="padding:16px 20px;display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;">
                    <div class="form-group" style="max-width:200px;margin-bottom:0;">
                        <label>Estimated Working Days <span style="color:var(--danger);">*</span></label>
                        <input type="number" name="estimated_working_days" id="addLaborEstDays" required min="0" step="0.01"
                               value="{{ $project->estimated_working_days }}" oninput="updateAllLaborRowTotals()">
                    </div>
                    <div style="font-size:12px;color:var(--muted);padding-bottom:11px;">Applies to all employees on this project — not entered per employee.</div>
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
                                <td colspan="3" style="padding:12px;text-align:right;font-size:13px;font-weight:700;color:var(--dark);">Grand Total</td>
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

    {{-- ===================== EDIT LABOR MODAL ===================== --}}
    <div class="modal-overlay" id="editLaborModal">
        <div class="modal-card" style="max-width:480px;">
            <div class="modal-header">
                <div>
                    <h2>Edit Labor Entry</h2>
                    <p id="editLaborSubtitle">Update labor details.</p>
                </div>
                <button class="modal-close" type="button" id="closeEditLaborModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" id="editLaborForm">
                @csrf
                @method('PUT')
                <div class="form-section-label">Daily Rate</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Daily Rate <span style="color:var(--danger);">*</span></label>
                        <input type="number" name="daily_rate" id="editLaborDailyRate" required min="0" step="0.01"
                               oninput="updateEditLaborTotal()">
                    </div>
                    <div class="form-group">
                        <label>Total Cost <span style="font-size:12px;font-weight:600;color:var(--accent);">(Auto-computed)</span></label>
                        <input type="text" id="editLaborTotalDisplay" readonly
                               style="background:var(--cream-soft);cursor:default;font-weight:900;color:var(--dark);">
                    </div>
                </div>
                <div class="form-section-label" style="margin-top:16px;">Additional Info</div>
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Notes</label>
                        <textarea name="notes" id="editLaborNotes" rows="3" placeholder="Additional information..."></textarea>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelEditLabor">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== ESTIMATED WORKING DAYS MODAL ===================== --}}
    <div class="modal-overlay" id="estimatedDaysModal">
        <div class="modal-card" style="max-width:420px;">
            <div class="modal-header">
                <div>
                    <h2>Estimated Working Days</h2>
                    <p>Applies to all employees on this project.</p>
                </div>
                <button class="modal-close" type="button" id="closeEstimatedDaysModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.project_materials.update_estimated_days', $project->id) }}">
                @csrf
                @method('PATCH')
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Estimated Working Days <span style="color:var(--danger);">*</span></label>
                        <input type="number" name="estimated_working_days" required min="0" step="0.01"
                               value="{{ $project->estimated_working_days }}">
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelEstimatedDays">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="save"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== ARCHIVE LABOR MODAL ===================== --}}
    <div class="modal-overlay" id="archiveLaborModal">
        <div class="modal-card" style="max-width:420px;">
            <div class="modal-header">
                <div>
                    <h2 id="archiveLaborTitle">Archive Labor Entry</h2>
                    <p>This will change the entry's status.</p>
                </div>
                <button class="modal-close" type="button" id="closeArchiveLaborModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="delete-confirm-body">
                <div class="delete-confirm-icon"><i data-lucide="archive"></i></div>
                <p id="archiveLaborMsg">Are you sure you want to archive this labor entry?</p>
            </div>
            <form method="POST" id="archiveLaborForm">
                @csrf
                @method('PATCH')
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelArchiveLabor">Cancel</button>
                    <button type="submit" class="save-btn" id="archiveLaborConfirmBtn">
                        <i data-lucide="archive"></i> Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        @php
            $laborBaseUrl = url('/admin/project-materials/' . $project->id . '/labor');
        @endphp
        var LABOR_BASE_URL = "{{ $laborBaseUrl }}";
        var ESTIMATED_DAYS = {{ $project->estimated_working_days }};

        function openModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
        }
        function closeModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
        }

        function formatCurrency(val) {
            if (isNaN(val) || val === 0) return '';
            return '₱' + parseFloat(val).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
        }

        // ---- multi-row add ----
        var MATERIAL_CATALOG = {
            'Steel & Metal Stock':  ['MS Plates','Angular Bar'],
            'Welding Supplies':     ['Electrode (6011)','Electrode (7018)','Welding Gloves'],
            'Cutting & Grinding':   ['Grinding Disc #4','Grinding Disc #5','Grinding Disc #7','Cutting Disc #4','Cutting Disc #5','Cutting Disc #7'],
            'Gas & Fuel':           ['Industrial Oxygen','Acetylene'],
            'Paint & Coating':      ['Epoxy Primer Gray','QDE Medium Gray','Lacquer Thinner','Paint Thinner','Polituff Putty'],
            'Brushes & Tools':      ['Paint Brush','Roller Brush'],
            'Safety & PPE':         ['Dark Glass #11','Clear Glass','Cotton Gloves'],
            'Abrasives':            ['Sanding Paper #60','Sanding Paper #100'],
            'Inspection & Testing': ['Penetrant Dye Spray','Pressure Test Kits','Pressure Gauge 60PSI']
        };

        var MATERIAL_UNITS = {
            'MS Plates': 'pcs', 'Angular Bar': 'pcs',
            'Electrode (6011)': 'kilos', 'Electrode (7018)': 'kilos', 'Welding Gloves': 'pcs',
            'Grinding Disc #4': 'pcs', 'Grinding Disc #5': 'pcs', 'Grinding Disc #7': 'pcs',
            'Cutting Disc #4': 'pcs', 'Cutting Disc #5': 'pcs', 'Cutting Disc #7': 'pcs',
            'Industrial Oxygen': 'cylinders', 'Acetylene': 'cylinders',
            'Epoxy Primer Gray': 'galons', 'QDE Medium Gray': 'galons',
            'Lacquer Thinner': 'galons', 'Paint Thinner': 'galons', 'Polituff Putty': 'galons',
            'Paint Brush': 'pcs', 'Roller Brush': 'pcs',
            'Dark Glass #11': 'pcs', 'Clear Glass': 'pcs', 'Cotton Gloves': 'pcs',
            'Sanding Paper #60': 'pcs', 'Sanding Paper #100': 'pcs',
            'Penetrant Dye Spray': 'pairs', 'Pressure Test Kits': 'set', 'Pressure Gauge 60PSI': 'pcs'
        };

        // ---- searchable, categorized material combo box ----
        function getUsedMaterialNames(excludeInput) {
            var used = [];
            document.querySelectorAll('#materialRowsContainer .row-mat-name-input').forEach(function(el) {
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

            Object.keys(MATERIAL_CATALOG).forEach(function(category) {
                var items = MATERIAL_CATALOG[category].filter(function(name) {
                    return !q || name.toLowerCase().indexOf(q) !== -1;
                });
                if (items.length === 0) return;
                hasMatches = true;
                html += '<div class="mat-combo-category">';
                html += '<div class="mat-combo-group">' + escapeHtml(category) + '</div>';
                items.forEach(function(name) {
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
            panel.querySelectorAll('.mat-combo-item').forEach(function(item) {
                if (item.classList.contains('disabled')) return;
                item.addEventListener('mousedown', function(e) {
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
                // not enough room below — open upward instead
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
            renderMatComboList(panel, input.value, function(value) {
                input.value = value;
                panel.classList.remove('show');
                checkMatDuplicate(input);
                // Auto-fill unit field if present in the same row
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

        function setMatComboValue(inputId, value) {
            var input = document.getElementById(inputId);
            if (input) input.value = value || '';
        }

        function closeAllMatCombos() {
            document.querySelectorAll('.mat-combo-dropdown.show').forEach(function(p) {
                p.classList.remove('show');
            });
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.mat-combo')) closeAllMatCombos();
        });
        window.addEventListener('scroll', function(e) {
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
            tr.className = 'material-add-row' + (isExisting ? ' material-existing-row' : '');
            tr.style.cssText = 'border-bottom:1px solid rgba(0,0,0,0.06);' + (isExisting ? 'background:var(--cream-soft,#f8f8f6);' : '');

            var removeBtn = '<button type="button" onclick="removeAddRow(this)" title="' + (isExisting ? 'Delete material' : 'Remove row') + '"' +
                       ' style="background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;color:var(--danger);display:flex;align-items:center;">' +
                      '<i data-lucide="trash-2" style="width:15px;height:15px;"></i>' +
                  '</button>';

            var removeCell = isExisting
                ? '<div style="display:flex;flex-direction:column;align-items:center;gap:4px;">' +
                      '<span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);">Existing</span>' +
                      removeBtn +
                  '</div>'
                : removeBtn;

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
                '<td style="padding:8px 10px;vertical-align:top;">' +
                    '<input type="text" name="notes[]" placeholder="Optional notes..."' +
                           ' value="' + escapeHtml(data.notes || '') + '"' +
                           ' style="width:100%;padding:8px 10px;border:1px solid rgba(0,0,0,0.14);border-radius:8px;font-size:13px;">' +
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
            document.querySelectorAll('#materialRowsContainer .material-add-row').forEach(function(row) {
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
            document.querySelectorAll('#materialRowsContainer .material-add-row').forEach(function(row, i) {
                var lbl = row.querySelector('.mat-row-label');
                if (lbl) lbl.textContent = i + 1;
            });
        }

        function openAddModal() {
            document.getElementById('addStep1').style.display = '';
            document.getElementById('addStep2').style.display = 'none';
            document.querySelectorAll('#addMaterialForm input[name="delete_material_id[]"]').forEach(function(el) { el.remove(); });
            var container = document.getElementById('materialRowsContainer');
            container.innerHTML = '';
            var rowNum = 1;
            BOM_MATERIALS.forEach(function(mat) {
                container.appendChild(buildMaterialRow(rowNum++, {
                    id: mat.id,
                    name: mat.material_name,
                    qty: mat.quantity,
                    price: mat.price_per_unit,
                    notes: mat.notes
                }));
            });
            container.appendChild(buildMaterialRow(rowNum++));
            if (typeof lucide !== 'undefined') lucide.createIcons();
            updateAddGrandTotal();
            var factorEl = document.getElementById('addMaterialFactor');
            if (factorEl) {
                var currentFactor = BOM_MATERIALS.length > 0 ? parseFloat(BOM_MATERIALS[0].factor) : NaN;
                factorEl.value = isNaN(currentFactor) ? 7 : currentFactor;
            }
            openModal('addMaterialModal');
        }
        // ---- end multi-row add ----

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function showAddReview() {
            var rows = document.querySelectorAll('#materialRowsContainer .material-add-row');
            var items = [];
            var valid = true;

            rows.forEach(function(row) {
                // Clear previous outline errors
                row.querySelectorAll('select,input').forEach(function(el) { el.style.outline = ''; });
                row.querySelectorAll('.mat-combo-warning').forEach(function(el) { el.classList.remove('show'); });
            });

            var seenNames = [];

            var factorEl = document.getElementById('addMaterialFactor');
            var factor = parseFloat(factorEl ? factorEl.value : '');
            if (isNaN(factor)) factor = 7;

            rows.forEach(function(row, i) {
                var idInput = row.querySelector('[name="material_id[]"]');
                var isExisting = !!(idInput && idInput.value);
                var nameInput = row.querySelector('[name="material_name[]"]');
                var name = nameInput ? nameInput.value.trim() : '';
                var qtyEl = row.querySelector('.row-qty');
                var priceEl = row.querySelector('.row-price');
                var qty = parseFloat(qtyEl ? qtyEl.value : '') || 0;
                var price = parseFloat(priceEl ? priceEl.value : '') || 0;
                var notesInput = row.querySelector('[name="notes[]"]');
                var notes = notesInput ? notesInput.value.trim() : '';

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
                    items.push({ num: i + 1, name: name, qty: qty, price: price, total: qty * price, notes: notes, existing: isExisting });
                }
            });

            if (!valid || items.length === 0) return;

            var grandTotal = items.reduce(function(sum, it) { return sum + it.total; }, 0) * (1 + factor / 100);
            var newCount = items.filter(function(it) { return !it.existing; }).length;
            var updatedCount = items.length - newCount;

            var thStyle = 'padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);background:var(--cream-soft,#f5f5f5);';
            var html = '<div style="padding:14px 20px 0;font-size:13px;font-weight:700;color:var(--dark);">Material Factor: ' + factor.toFixed(1) + '% <span style="font-size:12px;font-weight:400;color:var(--muted);">(applied to all materials in this project)</span></div>';
            html += '<div style="overflow-x:auto;">';
            html += '<table style="width:100%;border-collapse:collapse;min-width:560px;">';
            html += '<thead><tr>';
            html += '<th style="' + thStyle + 'width:36px;">#</th>';
            html += '<th style="' + thStyle + '">Material</th>';
            html += '<th style="' + thStyle + 'width:100px;">Quantity</th>';
            html += '<th style="' + thStyle + 'width:120px;">Price / Unit</th>';
            html += '<th style="' + thStyle + 'width:120px;">Total Cost</th>';
            html += '<th style="' + thStyle + 'width:80px;">Status</th>';
            html += '</tr></thead><tbody>';

            items.forEach(function(item) {
                var badge = item.existing
                    ? '<span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:rgba(0,0,0,0.06);color:var(--muted);">Existing</span>'
                    : '<span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:var(--accent-soft,#e6f4ea);color:var(--success,#2e7d32);">New</span>';
                html += '<tr style="border-bottom:1px solid rgba(0,0,0,0.06);">';
                html += '<td style="padding:10px 12px;font-size:12px;font-weight:700;color:var(--muted);">' + item.num + '</td>';
                html += '<td style="padding:10px 12px;">';
                html += '<div style="font-weight:700;font-size:13px;color:var(--dark);">' + escapeHtml(item.name) + '</div>';
                if (item.notes) {
                    html += '<div style="font-size:11px;color:var(--muted);margin-top:2px;">' + escapeHtml(item.notes) + '</div>';
                }
                html += '</td>';
                html += '<td style="padding:10px 12px;font-size:13px;">' + parseFloat(item.qty).toLocaleString('en-PH', {minimumFractionDigits:2,maximumFractionDigits:2}) + '</td>';
                html += '<td style="padding:10px 12px;font-size:13px;">₱' + parseFloat(item.price).toLocaleString('en-PH', {minimumFractionDigits:2,maximumFractionDigits:2}) + '</td>';
                html += '<td style="padding:10px 12px;font-size:13px;font-weight:800;color:var(--dark);">₱' + parseFloat(item.total).toLocaleString('en-PH', {minimumFractionDigits:2,maximumFractionDigits:2}) + '</td>';
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
            html += '<td style="padding:12px;font-size:15px;font-weight:900;color:var(--dark);" colspan="2">₱' + parseFloat(grandTotal).toLocaleString('en-PH', {minimumFractionDigits:2,maximumFractionDigits:2}) + '</td>';
            html += '</tr></tbody></table></div>';

            var deleteCount = document.querySelectorAll('#addMaterialForm input[name="delete_material_id[]"]').length;
            if (deleteCount > 0) {
                html += '<div style="padding:12px 20px;font-size:12px;font-weight:700;color:var(--danger);">' +
                    deleteCount + ' material' + (deleteCount !== 1 ? 's' : '') + ' will be permanently deleted from this project.' +
                '</div>';
            }

            document.getElementById('addSummaryContent').innerHTML =
                '<div style="padding:0 0 4px;">' + html + '</div>';
            document.getElementById('addStep1').style.display = 'none';
            document.getElementById('addStep2').style.display = '';
        }

        function backToAddForm() {
            document.getElementById('addStep2').style.display = 'none';
            document.getElementById('addStep1').style.display = '';
        }

        // ---- BOM ----
        var BOM_MATERIALS = @json($materials->where('status', 'active')->values());
        var BOM_LABOR     = @json($laborEntries->where('status', 'active')->values());

        function fmt(n) {
            return parseFloat(n).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
        }

        function matRowFactor(mat) {
            var factor = parseFloat(mat.factor);
            if (isNaN(factor)) factor = 7;
            return factor;
        }

        function updateBOM() {
            // Materials rows
            var matBase = 0;
            var matAdjTotal = 0;
            var matRows = '';
            BOM_MATERIALS.forEach(function(mat, i) {
                var base = parseFloat(mat.total_cost) || 0;
                var rowFactor = matRowFactor(mat);
                var adj = base * (1 + rowFactor/100);
                matBase += base;
                matAdjTotal += adj;
                matRows +=
                    '<tr style="border-bottom:1px solid rgba(0,0,0,0.06);">' +
                    '<td style="padding:10px 12px;font-size:12px;font-weight:700;color:var(--muted);">' + (i+1) + '</td>' +
                    '<td style="padding:10px 12px;"><div style="font-weight:700;font-size:13px;color:var(--dark);">' + escapeHtml(mat.material_name) + '</div>' +
                    (mat.notes ? '<div style="font-size:11px;color:var(--muted);margin-top:2px;">' + escapeHtml(mat.notes) + '</div>' : '') + '</td>' +
                    '<td style="padding:10px 12px;text-align:right;font-size:13px;">' + fmt(mat.quantity) + '</td>' +
                    '<td style="padding:10px 12px;text-align:right;font-size:13px;">₱' + fmt(mat.price_per_unit) + '</td>' +
                    '<td style="padding:10px 12px;text-align:right;font-size:13px;">₱' + fmt(base) + '</td>' +
                    '<td style="padding:10px 12px;text-align:right;font-size:13px;">' + rowFactor.toFixed(1) + '%</td>' +
                    '<td style="padding:10px 12px;text-align:right;font-size:13px;font-weight:800;color:var(--dark);">₱' + fmt(adj) + '</td>' +
                    '</tr>';
            });
            document.getElementById('bomTableBody').innerHTML = matRows ||
                '<tr><td colspan="7" style="padding:24px;text-align:center;color:var(--muted);">No active materials.</td></tr>';

            // Labor rows
            var laborBase = 0;
            var laborRows = '';
            BOM_LABOR.forEach(function(entry, i) {
                var base = parseFloat(entry.total_cost) || 0;
                laborBase += base;
                laborRows +=
                    '<tr style="border-bottom:1px solid rgba(0,0,0,0.06);">' +
                    '<td style="padding:10px 12px;font-size:12px;font-weight:700;color:var(--muted);">' + (i+1) + '</td>' +
                    '<td style="padding:10px 12px;"><div style="font-weight:700;font-size:13px;color:var(--dark);">' + escapeHtml(entry.description) + '</div>' +
                    (entry.notes ? '<div style="font-size:11px;color:var(--muted);margin-top:2px;">' + escapeHtml(entry.notes) + '</div>' : '') + '</td>' +
                    '<td style="padding:10px 12px;text-align:right;font-size:13px;">₱' + fmt(entry.daily_rate) + '</td>' +
                    '<td style="padding:10px 12px;text-align:right;font-size:13px;">' + fmt(ESTIMATED_DAYS) + '</td>' +
                    '<td style="padding:10px 12px;text-align:right;font-size:13px;font-weight:800;color:var(--dark);">₱' + fmt(base) + '</td>' +
                    '</tr>';
            });
            document.getElementById('bomLaborTableBody').innerHTML = laborRows ||
                '<tr><td colspan="5" style="padding:24px;text-align:center;color:var(--muted);">No active labor entries.</td></tr>';

            var matAdj   = matAdjTotal;
            var laborAdj = laborBase;
            var grandAdj = matAdj + laborAdj;

            document.getElementById('bomMatSubtotal').textContent   = '₱' + fmt(matAdj);
            document.getElementById('bomLaborSubtotal').textContent = '₱' + fmt(laborAdj);
            document.getElementById('bomGrandTotal').textContent    = '₱' + fmt(grandAdj);
        }

        function buildBOMDocument(withPrintScript) {
            var today = new Date().toLocaleDateString('en-PH', {year:'numeric',month:'long',day:'numeric'});

            var matBase = 0, matAdjTotal = 0, matRows = '';
            BOM_MATERIALS.forEach(function(mat, i) {
                var base = parseFloat(mat.total_cost) || 0;
                var rowFactor = matRowFactor(mat);
                var adj = base * (1 + rowFactor/100);
                matBase += base; matAdjTotal += adj;
                matRows += '<tr><td>' + (i+1) + '</td><td>' + mat.material_name + (mat.notes ? '<br><small>' + mat.notes + '</small>' : '') + '</td>' +
                    '<td class="r">' + fmt(mat.quantity) + '</td><td class="r">₱' + fmt(mat.price_per_unit) + '</td>' +
                    '<td class="r">₱' + fmt(base) + '</td><td class="r"><strong>₱' + fmt(adj) + '</strong></td></tr>';
            });

            var laborBase = 0, laborRows = '';
            BOM_LABOR.forEach(function(entry, i) {
                var base = parseFloat(entry.total_cost) || 0; laborBase += base;
                var match = /^(.*)\s+\(([^)]+)\)\s*$/.exec(entry.description || '');
                var empName = match ? match[1] : (entry.description || '');
                var empRole = match ? match[2] : '';
                laborRows += '<tr><td>' + (i+1) + '</td><td>' + empName + (entry.notes ? '<br><small>' + entry.notes + '</small>' : '') + '</td>' +
                    '<td>' + empRole + '</td>' +
                    '<td class="r">₱' + fmt(entry.daily_rate) + '</td><td class="r">' + fmt(ESTIMATED_DAYS) + '</td>' +
                    '<td class="r"><strong>₱' + fmt(base) + '</strong></td></tr>';
            });

            var matAdj = matAdjTotal, laborAdj = laborBase;
            var grandAdj = matAdj + laborAdj;

            var css = 'body{font-family:Arial,sans-serif;font-size:13px;color:#111;margin:32px;}' +
                'h1{font-size:20px;margin:0 0 4px;}h2{font-size:14px;margin:24px 0 8px;padding:6px 0;border-bottom:2px solid #ccc;}' +
                'p.sub{color:#666;margin:0 0 16px;font-size:12px;}' +
                'table{width:100%;border-collapse:collapse;margin-bottom:8px;}' +
                'th{background:#f0f0f0;padding:8px 10px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #ccc;}' +
                'td{padding:8px 10px;border-bottom:1px solid #e5e5e5;}.r{text-align:right;}' +
                '.subtotal td{font-weight:700;background:#f8f8f8;border-top:2px solid #bbb;}' +
                '.grand td{font-size:14px;font-weight:900;background:#111;color:#fff;border:none;}' +
                '@media print{body{margin:16px;}}';

            var html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>BOM — {{ $project->name }}</title><style>' + css + '</style></head><body>' +
                '<h1>Bill of Materials — {{ $project->name }}</h1>' +
                '<p class="sub">Client: {{ $project->client }} &nbsp;|&nbsp; Generated: ' + today + ' &nbsp;|&nbsp; Material Factor: {{ number_format($materialFactor, 1) }}%</p>' +
                '<h2>Materials</h2>' +
                '<table><thead><tr><th>#</th><th>Material Name</th><th class="r">Qty</th><th class="r">Price/Unit</th><th class="r">Base Total</th><th class="r">Adjusted Total</th></tr></thead>' +
                '<tbody>' + (matRows || '<tr><td colspan="6">No materials.</td></tr>') + '</tbody>' +
                '<tr class="subtotal"><td colspan="5" class="r">Materials Subtotal (incl. factor)</td><td class="r">₱' + fmt(matAdj) + '</td></tr>' +
                '</table>' +
                '<h2>Labor</h2>' +
                '<table><thead><tr><th>#</th><th>Employee Name</th><th>Role</th><th class="r">Daily Rate</th><th class="r">Est. Days</th><th class="r">Total</th></tr></thead>' +
                '<tbody>' + (laborRows || '<tr><td colspan="6">No labor entries.</td></tr>') + '</tbody>' +
                '<tr class="subtotal"><td colspan="5" class="r">Labor Subtotal</td><td class="r">₱' + fmt(laborAdj) + '</td></tr>' +
                '</table>' +
                '<table><tr class="grand"><td colspan="5" class="r">Project Grand Total</td><td class="r">₱' + fmt(grandAdj) + '</td></tr></table>';

            if (withPrintScript) {
                html += '<script>window.onload=function(){window.print();}<\/script>';
            }

            html += '</body></html>';
            return html;
        }

        function printBOM() {
            var win = window.open('', '_blank');
            win.document.write(buildBOMDocument(true));
            win.document.close();
        }

        function downloadBOM() {
            var blob = new Blob([buildBOMDocument(false)], { type: 'text/html' });
            var url  = URL.createObjectURL(blob);
            var a    = document.createElement('a');
            a.href     = url;
            a.download = 'Quotation - {{ str_replace("/", "-", $project->name) }}.html';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        // ---- materials-only BOM ----
        function updateMaterialsBOM() {
            var matAdjTotal = 0;
            var matRows = '';
            BOM_MATERIALS.forEach(function(mat, i) {
                var base = parseFloat(mat.total_cost) || 0;
                var rowFactor = matRowFactor(mat);
                var adj = base * (1 + rowFactor/100);
                matAdjTotal += adj;
                matRows +=
                    '<tr style="border-bottom:1px solid rgba(0,0,0,0.06);">' +
                    '<td style="padding:10px 12px;font-size:12px;font-weight:700;color:var(--muted);">' + (i+1) + '</td>' +
                    '<td style="padding:10px 12px;"><div style="font-weight:700;font-size:13px;color:var(--dark);">' + escapeHtml(mat.material_name) + '</div>' +
                    (mat.notes ? '<div style="font-size:11px;color:var(--muted);margin-top:2px;">' + escapeHtml(mat.notes) + '</div>' : '') + '</td>' +
                    '<td style="padding:10px 12px;text-align:right;font-size:13px;">' + fmt(mat.quantity) + '</td>' +
                    '<td style="padding:10px 12px;text-align:right;font-size:13px;">₱' + fmt(mat.price_per_unit) + '</td>' +
                    '<td style="padding:10px 12px;text-align:right;font-size:13px;">₱' + fmt(base) + '</td>' +
                    '<td style="padding:10px 12px;text-align:right;font-size:13px;font-weight:800;color:var(--dark);">₱' + fmt(adj) + '</td>' +
                    '</tr>';
            });
            document.getElementById('matBomTableBody').innerHTML = matRows ||
                '<tr><td colspan="6" style="padding:24px;text-align:center;color:var(--muted);">No active materials.</td></tr>';

            document.getElementById('matBomGrandTotal').textContent = '₱' + fmt(matAdjTotal);
        }

        function printMaterialsBOM() {
            var today = new Date().toLocaleDateString('en-PH', {year:'numeric',month:'long',day:'numeric'});

            var matAdjTotal = 0, matRows = '';
            BOM_MATERIALS.forEach(function(mat, i) {
                var base = parseFloat(mat.total_cost) || 0;
                var rowFactor = matRowFactor(mat);
                var adj = base * (1 + rowFactor/100);
                matAdjTotal += adj;
                matRows += '<tr><td>' + (i+1) + '</td><td>' + mat.material_name + (mat.notes ? '<br><small>' + mat.notes + '</small>' : '') + '</td>' +
                    '<td class="r">' + fmt(mat.quantity) + '</td><td class="r">₱' + fmt(mat.price_per_unit) + '</td>' +
                    '<td class="r">₱' + fmt(base) + '</td><td class="r"><strong>₱' + fmt(adj) + '</strong></td></tr>';
            });

            var win = window.open('', '_blank');
            var css = 'body{font-family:Arial,sans-serif;font-size:13px;color:#111;margin:32px;}' +
                'h1{font-size:20px;margin:0 0 4px;}h2{font-size:14px;margin:24px 0 8px;padding:6px 0;border-bottom:2px solid #ccc;}' +
                'p.sub{color:#666;margin:0 0 16px;font-size:12px;}' +
                'table{width:100%;border-collapse:collapse;margin-bottom:8px;}' +
                'th{background:#f0f0f0;padding:8px 10px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #ccc;}' +
                'td{padding:8px 10px;border-bottom:1px solid #e5e5e5;}.r{text-align:right;}' +
                '.subtotal td{font-weight:700;background:#f8f8f8;border-top:2px solid #bbb;}' +
                '.grand td{font-size:14px;font-weight:900;background:#111;color:#fff;border:none;}' +
                '@media print{body{margin:16px;}}';
            win.document.write('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>BOM — {{ $project->name }}</title><style>' + css + '</style></head><body>' +
                '<h1>Bill of Materials — {{ $project->name }}</h1>' +
                '<p class="sub">Client: {{ $project->client }} &nbsp;|&nbsp; Generated: ' + today + ' &nbsp;|&nbsp; Material Factor: {{ number_format($materialFactor, 1) }}%</p>' +
                '<table><thead><tr><th>#</th><th>Material Name</th><th class="r">Qty</th><th class="r">Price/Unit</th><th class="r">Base Total</th><th class="r">Adjusted Total</th></tr></thead>' +
                '<tbody>' + (matRows || '<tr><td colspan="6">No materials.</td></tr>') + '</tbody>' +
                '</table>' +
                '<table><tr class="grand"><td colspan="5" class="r">Materials Total</td><td class="r">₱' + fmt(matAdjTotal) + '</td></tr></table>' +
                '<script>window.onload=function(){window.print();}<\/script></body></html>');
            win.document.close();
        }
        // ---- end materials-only BOM ----
        // ---- end BOM ----

        // ---- labor ----
        var LABOR_EMPLOYEES = @json($regularEmployees->map(function($emp) {
            return ['name' => $emp->name, 'role' => $emp->role, 'daily_rate' => $emp->daily_rate ?? 0];
        })->values());

        var KNOWN_LABOR_ROLES = ['Fabricator', 'Welder', 'Helper/Labor', 'Outsourced'];

        function buildLaborEmployeeOptions() {
            var html = '<option value="">Select employee</option>';
            LABOR_EMPLOYEES.forEach(function(emp) {
                html += '<option value="' + escapeHtml(emp.name) + '" data-role="' + escapeHtml(emp.role || '') + '" data-rate="' + (emp.daily_rate || 0) + '">' + escapeHtml(emp.name) + '</option>';
            });
            html += '<option value="other">Other (type manually)...</option>';
            return html;
        }

        function buildLaborRoleOptions() {
            var html = '<option value="">Select role</option>';
            KNOWN_LABOR_ROLES.forEach(function(role) {
                html += '<option value="' + escapeHtml(role) + '">' + escapeHtml(role) + '</option>';
            });
            html += '<option value="other">Other...</option>';
            return html;
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
                        buildLaborEmployeeOptions() +
                    '</select>' +
                    '<input type="text" class="row-labor-name-custom" name="_emp_unused"' +
                           ' placeholder="Employee name"' +
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
            return el ? (parseFloat(el.value) || 0) : ESTIMATED_DAYS;
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
            document.querySelectorAll('#laborRowsContainer .labor-add-row').forEach(function(row) {
                var rate = parseFloat(row.querySelector('.row-labor-rate').value) || 0;
                grand += rate * days;
            });
            var el = document.getElementById('laborGrandTotal');
            if (el) el.textContent = grand > 0 ? formatCurrency(grand) : '₱0.00';
        }

        function updateAllLaborRowTotals() {
            document.querySelectorAll('#laborRowsContainer .labor-add-row .row-labor-rate').forEach(function(rateInput) {
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
        }

        function renumberLaborRows() {
            document.querySelectorAll('#laborRowsContainer .labor-add-row').forEach(function(row, i) {
                var lbl = row.querySelector('.labor-row-label');
                if (lbl) lbl.textContent = i + 1;
            });
        }

        function openAddLaborModal() {
            var container = document.getElementById('laborRowsContainer');
            container.innerHTML = '';
            container.appendChild(buildLaborRow(1));
            if (typeof lucide !== 'undefined') lucide.createIcons();
            var estDaysEl = document.getElementById('addLaborEstDays');
            if (estDaysEl) estDaysEl.value = ESTIMATED_DAYS;
            var grandEl = document.getElementById('laborGrandTotal');
            if (grandEl) grandEl.textContent = '₱0.00';
            openModal('addLaborModal');
        }

        function updateEditLaborTotal() {
            var dailyRate = parseFloat(document.getElementById('editLaborDailyRate').value) || 0;
            var total = dailyRate * ESTIMATED_DAYS;
            document.getElementById('editLaborTotalDisplay').value = total > 0 ? formatCurrency(total) : '';
        }

        var currentLaborStatusFilter = 'all';

        function applyLaborFilters() {
            var q = (document.getElementById('laborSearch').value || '').toLowerCase();
            document.querySelectorAll('#laborTable tbody tr[data-labor-status]').forEach(function(row) {
                var status = (row.dataset.laborStatus || '').toLowerCase();
                var matchSearch = row.textContent.toLowerCase().indexOf(q) !== -1;
                var matchFilter = currentLaborStatusFilter === 'all' || currentLaborStatusFilter === status;
                row.style.display = (matchSearch && matchFilter) ? '' : 'none';
            });
        }
        // ---- end labor ----

        var currentStatusFilter = 'all';

        function applyMaterialFilters() {
            var q = (document.getElementById('materialSearch').value || '').toLowerCase();
            var rows = document.querySelectorAll('#materialsTable tbody tr[data-mat-status]');
            rows.forEach(function(row) {
                var status = (row.dataset.matStatus || '').toLowerCase();
                var matchSearch = row.textContent.toLowerCase().indexOf(q) !== -1;
                var matchFilter = currentStatusFilter === 'all'
                    || currentStatusFilter === status;
                row.style.display = (matchSearch && matchFilter) ? '' : 'none';
            });
        }

        document.addEventListener('DOMContentLoaded', function() {

            // BOM modal
            var openBOMBtn = document.getElementById('openBOMModal');
            if (openBOMBtn) openBOMBtn.addEventListener('click', function() {
                updateBOM();
                openModal('bomModal');
            });
            ['closeBOMModal', 'cancelBOMModal'].forEach(function(id) {
                var btn = document.getElementById(id);
                if (btn) btn.addEventListener('click', function() { closeModal('bomModal'); });
            });
            var printBOMBtn = document.getElementById('printBOMBtn');
            if (printBOMBtn) printBOMBtn.addEventListener('click', printBOM);
            var downloadBOMBtn = document.getElementById('downloadBOMBtn');
            if (downloadBOMBtn) downloadBOMBtn.addEventListener('click', downloadBOM);

            // Generate BOM (materials only) modal
            var openMaterialsBOMBtn = document.getElementById('openMaterialsBOMModal');
            if (openMaterialsBOMBtn) openMaterialsBOMBtn.addEventListener('click', function() {
                updateMaterialsBOM();
                openModal('materialsBOMModal');
            });
            ['closeMaterialsBOMModal', 'cancelMaterialsBOMModal'].forEach(function(id) {
                var btn = document.getElementById(id);
                if (btn) btn.addEventListener('click', function() { closeModal('materialsBOMModal'); });
            });
            var printMaterialsBOMBtn = document.getElementById('printMaterialsBOMBtn');
            if (printMaterialsBOMBtn) printMaterialsBOMBtn.addEventListener('click', printMaterialsBOM);

            // Add modal
            var openAddBtn = document.getElementById('openAddMaterialModal');
            if (openAddBtn) openAddBtn.addEventListener('click', openAddModal);

            var addAnotherBtn = document.getElementById('addAnotherRowBtn');
            if (addAnotherBtn) addAnotherBtn.addEventListener('click', function() {
                var container = document.getElementById('materialRowsContainer');
                var count = container.querySelectorAll('.material-add-row').length + 1;
                var newRow = buildMaterialRow(count);
                container.appendChild(newRow);
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });

            var reviewBtn = document.getElementById('reviewMaterialsBtn');
            if (reviewBtn) reviewBtn.addEventListener('click', showAddReview);

            var backBtn = document.getElementById('backToAddFormBtn');
            if (backBtn) backBtn.addEventListener('click', backToAddForm);

            ['closeAddMaterialModal', 'cancelAddMaterial'].forEach(function(id) {
                var btn = document.getElementById(id);
                if (btn) btn.addEventListener('click', function() { closeModal('addMaterialModal'); });
            });

            // Backdrop close
            document.querySelectorAll('.modal-overlay').forEach(function(modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) closeModal(this.id);
                });
            });

            // Search & filter
            var searchInput = document.getElementById('materialSearch');
            if (searchInput) searchInput.addEventListener('keyup', applyMaterialFilters);

            applyMaterialFilters();

            // Labor — Add modal
            var openAddLaborBtn = document.getElementById('openAddLaborModalBtn');
            if (openAddLaborBtn) openAddLaborBtn.addEventListener('click', openAddLaborModal);

            var addAnotherLaborRowBtn = document.getElementById('addAnotherLaborRowBtn');
            if (addAnotherLaborRowBtn) addAnotherLaborRowBtn.addEventListener('click', function() {
                var container = document.getElementById('laborRowsContainer');
                var count = container.querySelectorAll('.labor-add-row').length + 1;
                var newRow = buildLaborRow(count);
                container.appendChild(newRow);
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });

            ['closeAddLaborModal', 'cancelAddLabor'].forEach(function(id) {
                var btn = document.getElementById(id);
                if (btn) btn.addEventListener('click', function() { closeModal('addLaborModal'); });
            });

            // Labor — Edit modal
            document.querySelectorAll('.edit-labor-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.getElementById('editLaborDailyRate').value = this.dataset.dailyRate;
                    document.getElementById('editLaborNotes').value     = this.dataset.notes || '';
                    updateEditLaborTotal();
                    document.getElementById('editLaborSubtitle').textContent = 'Editing: ' + this.dataset.description;
                    document.getElementById('editLaborForm').action = LABOR_BASE_URL + '/' + this.dataset.id;
                    openModal('editLaborModal');
                });
            });
            ['closeEditLaborModal', 'cancelEditLabor'].forEach(function(id) {
                var btn = document.getElementById(id);
                if (btn) btn.addEventListener('click', function() { closeModal('editLaborModal'); });
            });

            // Estimated Working Days modal
            var openEstDaysBtn = document.getElementById('openEstimatedDaysModal');
            if (openEstDaysBtn) openEstDaysBtn.addEventListener('click', function() { openModal('estimatedDaysModal'); });

            ['closeEstimatedDaysModal', 'cancelEstimatedDays'].forEach(function(id) {
                var btn = document.getElementById(id);
                if (btn) btn.addEventListener('click', function() { closeModal('estimatedDaysModal'); });
            });

            // Labor — Archive modal
            document.querySelectorAll('.archive-labor-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var isArchived = this.dataset.archived === '1';
                    var desc = this.dataset.description;
                    document.getElementById('archiveLaborTitle').textContent = isArchived ? 'Restore Labor Entry' : 'Archive Labor Entry';
                    document.getElementById('archiveLaborMsg').textContent = isArchived
                        ? 'Restore "' + desc + '"? It will be included in labor cost calculations.'
                        : 'Archive "' + desc + '"? It will be excluded from labor cost calculations.';
                    var confirmBtn = document.getElementById('archiveLaborConfirmBtn');
                    confirmBtn.innerHTML = isArchived
                        ? '<i data-lucide="archive-restore"></i> Restore'
                        : '<i data-lucide="archive"></i> Archive';
                    document.getElementById('archiveLaborForm').action = LABOR_BASE_URL + '/' + this.dataset.id + '/archive';
                    openModal('archiveLaborModal');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            });
            ['closeArchiveLaborModal', 'cancelArchiveLabor'].forEach(function(id) {
                var btn = document.getElementById(id);
                if (btn) btn.addEventListener('click', function() { closeModal('archiveLaborModal'); });
            });

            // Labor search & filter
            var laborSearch = document.getElementById('laborSearch');
            if (laborSearch) laborSearch.addEventListener('keyup', applyLaborFilters);

            applyLaborFilters();

            if (typeof lucide !== 'undefined') lucide.createIcons();

            // Auto-open Add Material modal when redirected from index with ?openAdd=1
            if (new URLSearchParams(window.location.search).get('openAdd') === '1') {
                var openBtn = document.getElementById('openAddMaterialModal');
                if (openBtn) openBtn.click();
                // Clean up the URL without reloading
                history.replaceState(null, '', window.location.pathname);
            }
        });
    </script>
</body>
</html>
