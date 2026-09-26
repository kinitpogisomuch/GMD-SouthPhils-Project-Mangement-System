<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project->name }} — Materials | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        .overview-grid-3 { grid-template-columns: repeat(3, 1fr); }
        @media (max-width: 768px) { .overview-grid-3 { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 480px) { .overview-grid-3 { grid-template-columns: 1fr; } }
        .overview-grid-3 .fd-ov-label { font-size: 10px; }
        .overview-grid-3 .fd-ov-label + .fd-ov-label { font-size: 9px !important; }
        .overview-grid-3 .fd-ov-val { font-size: 15px; }
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

        /* ── Quotation Builder: one card holding pricing, materials and labor ── */
        .qb-card { background: var(--white); border: 1px solid var(--border); border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.04); margin-bottom: 24px; }
        .qb-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 22px 26px; border-bottom: 1px solid var(--border); }
        .qb-title { display: flex; align-items: center; gap: 10px; font-size: 18px; font-weight: 900; color: var(--dark); margin: 0; }
        .qb-title i { width: 20px; height: 20px; color: var(--accent); }
        .qb-sub { margin: 4px 0 0; font-size: 13px; color: var(--muted); }
        .qb-dirty { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; color: #92400e; background: #FEF3C7; border: 1px solid #fcd34d; border-radius: 999px; padding: 6px 12px; }
        .qb-dirty[hidden] { display: none; }
        .qb-dirty i { width: 12px; height: 12px; }
        .qb-errors { margin: 16px 26px 0; padding: 12px 16px; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 12px; color: #b91c1c; font-size: 13px; font-weight: 600; line-height: 1.6; }
        .qb-errors[hidden] { display: none; }
        .qb-errors div::before { content: "• "; }

        .qb-section { padding: 24px 26px; border-bottom: 1px solid var(--border); }
        .qb-section-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 18px; }
        .qb-section-title { display: flex; align-items: flex-start; gap: 12px; }
        .qb-section-title h3 { margin: 0; font-size: 15px; font-weight: 800; color: var(--dark); }
        .qb-section-title p { margin: 2px 0 0; font-size: 12.5px; color: var(--muted); }
        .qb-step { width: 28px; height: 28px; flex-shrink: 0; border-radius: 50%; background: var(--dark); color: #fff; font-size: 12.5px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
        .qb-section-tools { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        .qb-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .qb-field label { display: block; font-size: 12px; font-weight: 800; color: var(--muted); margin-bottom: 6px; }
        .qb-field small { display: block; margin-top: 5px; font-size: 11.5px; color: var(--muted); }
        .qb-optional { font-weight: 500; }
        .qb-input { width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid var(--border); border-radius: 10px; background: var(--white); font-size: 13.5px; color: var(--dark); }
        .qb-input:focus { outline: 2px solid var(--dark); outline-offset: -1px; }
        .qb-chip { display: inline-flex; align-items: center; gap: 8px; height: 44px; padding: 0 14px; background: var(--cream-soft); border: 1px solid var(--border); border-radius: 14px; font-size: 12.5px; font-weight: 700; color: var(--dark); white-space: nowrap; cursor: text; }
        .qb-chip i { width: 15px; height: 15px; color: var(--muted); }
        .qb-chip input { width: 64px; padding: 5px 8px; border: 1px solid var(--border); border-radius: 8px; background: var(--white); font-size: 13px; font-weight: 900; color: var(--dark); text-align: right; }

        .qb-table-wrap { overflow-x: auto; border: 1px solid var(--border); border-radius: 14px; }
        .qb-table { width: 100%; border-collapse: collapse; min-width: 760px; }
        .qb-table th { padding: 11px 12px; text-align: left; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); background: var(--cream-soft); border-bottom: 1px solid var(--border); }
        .qb-table td { padding: 8px 10px; vertical-align: top; border-bottom: 1px solid var(--border); }
        .qb-table tbody tr:last-child td { border-bottom: none; }
        .qb-table .qb-num { text-align: right; }
        .qb-table tfoot td { background: var(--cream-soft); border-top: 2px solid var(--border); border-bottom: none; padding: 12px; }
        .qb-table tfoot tr + tr td { border-top: 1px solid var(--border); }
        .qb-total-label { text-align: right; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); }
        .qb-total-value { text-align: right; font-size: 15px; font-weight: 900; color: var(--dark); }
        .qb-row-num { padding-top: 16px !important; font-size: 12px; font-weight: 700; color: var(--muted); }
        .qb-row-new td:first-child { box-shadow: inset 3px 0 0 var(--accent); }
        .qb-cell-input { width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; background: var(--white); font-size: 13px; color: var(--dark); }
        .qb-cell-input:focus { outline: 2px solid var(--dark); outline-offset: -1px; }
        .qb-cell-input[readonly] { background: rgba(0,0,0,.03); cursor: default; }
        .qb-cell-total { width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1px solid transparent; background: transparent; font-size: 13px; font-weight: 800; color: var(--dark); text-align: right; }
        .qb-static { padding: 8px 10px; font-size: 13px; color: var(--dark); line-height: 1.5; }
        .qb-static strong { font-weight: 700; }
        .qb-role-pill { display: inline-block; font-size: 12px; font-weight: 700; background: var(--cream-soft); color: var(--dark); padding: 3px 9px; border-radius: 6px; }
        .qb-icon-btn { background: none; border: none; cursor: pointer; padding: 7px; border-radius: 8px; color: var(--danger); display: inline-flex; align-items: center; }
        .qb-icon-btn:hover { background: rgba(220,38,38,.08); }
        .qb-icon-btn i, .qb-icon-btn svg { width: 15px; height: 15px; }
        .qb-pill-btn { border: 1px solid var(--border); background: var(--white); border-radius: 999px; padding: 5px 12px; font-size: 11.5px; font-weight: 800; color: var(--muted); cursor: pointer; }
        .qb-pill-btn:hover { border-color: var(--dark); color: var(--dark); }
        .qb-table tr.is-archived td:not(:last-child) { opacity: .45; }
        .qb-table tr.is-archived .qb-static strong { text-decoration: line-through; }
        .qb-empty { padding: 34px 20px; text-align: center; font-size: 13px; color: var(--muted); }
        .qb-empty[hidden] { display: none; }

        .qb-foot { display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; padding: 20px 26px; background: var(--cream-soft); border-top: 1px solid var(--border); border-radius: 0 0 20px 20px; }
        .qb-summary { display: flex; align-items: stretch; gap: 12px; flex-wrap: wrap; }
        .qb-sum-item { display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; gap: 3px; min-width: 150px; padding: 12px 18px; background: var(--white); border: 1px solid var(--border); border-radius: 14px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
        .qb-sum-label { font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: var(--muted); }
        .qb-sum-item strong { font-size: 20px; font-weight: 900; letter-spacing: -.3px; color: var(--dark); line-height: 1.2; }
        .qb-sum-item small { font-size: 11.5px; color: var(--muted); }
        .qb-sum-total { min-width: 190px; background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); border-color: transparent; box-shadow: 0 6px 16px rgba(0,0,0,.18); }
        .qb-sum-total .qb-sum-label { color: rgba(255,255,255,.6); }
        .qb-sum-total strong { color: #4ade80; font-size: 26px; }
        .qb-sum-total small { color: rgba(255,255,255,.5); }
        .qb-sum-op { align-self: center; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: var(--white); border: 1px solid var(--border); font-size: 15px; font-weight: 800; color: var(--muted); }
        .qb-foot .save-btn { padding: 14px 32px; font-size: 14.5px; }

        @media (max-width: 900px) { .qb-grid { grid-template-columns: 1fr; } }
        @media (max-width: 600px) {
            .qb-head, .qb-section, .qb-foot { padding-left: 16px; padding-right: 16px; }
            .qb-errors { margin-left: 16px; margin-right: 16px; }
            .qb-foot .save-btn { width: 100%; justify-content: center; }
            .qb-sum-item, .qb-sum-total { min-width: 0; flex: 1 1 100%; }
            .qb-sum-op { display: none; }
        }
        .qb-bad { outline: 2px solid var(--danger) !important; outline-offset: -1px; }
        /* No up/down spinner arrows on the number fields (markup, factor, days, quantity, price, rate) */
        .qb-card input[type="number"] { -moz-appearance: textfield; appearance: textfield; }
        .qb-card input[type="number"]::-webkit-outer-spin-button,
        .qb-card input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .qb-req { color: var(--danger); font-weight: 900; }
        .qb-errors strong { font-weight: 900; }
        .qb-err-link { cursor: pointer; text-decoration: underline; text-decoration-color: rgba(185,28,28,.35); text-underline-offset: 3px; }
        .qb-err-link:hover { text-decoration-color: currentColor; }
        .qb-table-wrap.qb-bad { outline-offset: 2px; }

        /* Add Material picker modal */
        .pm-toolbar { display: flex; align-items: center; gap: 12px; padding: 4px 0 14px; }
        .pm-search { flex: 1; display: flex; align-items: center; gap: 8px; padding: 0 12px; height: 42px; border: 1px solid var(--border); border-radius: 12px; background: var(--cream-soft); }
        .pm-search i, .pm-search svg { width: 16px; height: 16px; color: var(--muted); flex-shrink: 0; }
        .pm-search input { flex: 1; border: none; background: transparent; outline: none; font-size: 13.5px; color: var(--dark); }
        .pm-count { font-size: 12px; font-weight: 800; color: var(--dark); background: var(--cream-soft); border: 1px solid var(--border); border-radius: 999px; padding: 7px 12px; white-space: nowrap; }
        .pm-list { max-height: 48vh; overflow-y: auto; border: 1px solid var(--border); border-radius: 14px; }
        .pm-group + .pm-group { border-top: 1px solid var(--border); }
        .pm-cat { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: var(--cream-soft); position: sticky; top: 0; z-index: 1; cursor: pointer; font-size: 11.5px; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; color: var(--dark); }
        .pm-cat em { margin-left: auto; font-style: normal; font-weight: 700; text-transform: none; letter-spacing: 0; color: var(--muted); }
        .pm-item { display: flex; align-items: center; gap: 10px; padding: 9px 14px 9px 22px; font-size: 13.5px; color: var(--dark); cursor: pointer; border-top: 1px solid var(--border); }
        .pm-item:hover { background: #f5f8ff; }
        .pm-item.is-used { color: var(--muted); cursor: not-allowed; background: transparent; }
        .pm-item input, .pm-cat input { width: 17px; height: 17px; accent-color: var(--dark); flex-shrink: 0; }
        .pm-badge { margin-left: auto; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: var(--danger); }
        .pm-empty { padding: 34px 20px; text-align: center; font-size: 13px; color: var(--muted); }
        .pm-foot { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding-top: 16px; }
        .pm-foot-actions { display: flex; gap: 10px; }
        .pm-custom { display: inline-flex; align-items: center; gap: 6px; background: none; border: 2px dashed rgba(0,0,0,.15); border-radius: 10px; padding: 8px 14px; font-size: 12.5px; font-weight: 700; color: var(--muted); cursor: pointer; }
        .pm-custom:hover { border-color: var(--dark); color: var(--dark); }
        .pm-custom i, .pm-custom svg { width: 14px; height: 14px; }
        .pm-rate { margin-left: auto; font-size: 12px; font-weight: 800; color: var(--muted); white-space: nowrap; }
        #confirmPickMaterials:disabled, #confirmPickLabor:disabled { opacity: .5; cursor: not-allowed; }
        /* View-only mode (completed project): no editing controls, values shown as plain text */
        .qb-readonly .qb-icon-btn, .qb-readonly .qb-pill-btn, .qb-readonly .qb-req,
        .qb-readonly #addMaterialRowBtn, .qb-readonly #addLaborRowBtn, .qb-readonly #qbSaveBtn,
        .qb-readonly .qb-head-right label.qb-chip, .qb-readonly .qb-dirty { display: none !important; }
        .qb-readonly input:disabled, .qb-readonly select:disabled { opacity: 1; cursor: default; color: var(--dark); -webkit-text-fill-color: var(--dark); background: transparent; border-color: transparent; }
        .qb-readonly .qb-chip input:disabled { border-color: var(--border); background: var(--white); }
        .qb-readonly select:disabled { appearance: none; -webkit-appearance: none; }
        .qb-input[readonly], .qb-input:disabled { background: rgba(0,0,0,.03); cursor: default; color: var(--dark); -webkit-text-fill-color: var(--dark); opacity: 1; }
        .qb-card .qb-input.qb-locked, .qb-card .qb-input.qb-locked:disabled { background: rgba(0,0,0,.03); border: 1px solid var(--border); padding: 10px 12px; color: var(--dark); -webkit-text-fill-color: var(--dark); opacity: 1; cursor: default; }
        .qb-value-note strong { color: var(--dark); font-weight: 900; }
        .qb-head-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

    </style>
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            {{-- Breadcrumb --}}
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('admin.quotation_requests', ['tab' => 'projects']) }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    Project Materials
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <a href="{{ route('admin.project_materials.client', urlencode($project->client)) }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    {{ $project->live_client_name }}
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


            @php $readOnly = $project->status === 'completed'; @endphp
            @if($readOnly)
            <div class="alert-banner info" style="margin-bottom:20px;">
                <i data-lucide="lock"></i>
                This project is completed, so its quotation is <strong>view only</strong>.
            </div>
            @endif

            {{-- Project Overview --}}
            <div class="fd-overview" style="margin-bottom:24px;">
                <div class="fd-overview-title">
                    <i data-lucide="layout-dashboard"></i>
                    Project Overview
                </div>
                <div class="fd-overview-grid overview-grid-3">
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Total Materials</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Active material entries</span>
                        <span class="fd-ov-val">{{ $totalMaterials > 0 ? $totalMaterials : '—' }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Total Quantity</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Combined units</span>
                        <span class="fd-ov-val">{{ $totalQuantity > 0 ? number_format($totalQuantity, 0) : '—' }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Material Cost</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Total material cost</span>
                        <span class="fd-ov-val" style="color:{{ $estimatedCost > 0 ? '#4ade80' : 'rgba(255,255,255,0.35)' }};">{{ $estimatedCost > 0 ? '₱' . number_format($estimatedCost, 2) : '—' }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Labor Entries</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Total labor entries</span>
                        <span class="fd-ov-val">{{ $totalLaborEntries > 0 ? $totalLaborEntries : '—' }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Estimated Working Days</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Applies to all employees</span>
                        <span class="fd-ov-val">@if(($project->estimated_working_days ?? 0) > 0){{ number_format($project->estimated_working_days, 0) }} <small style="font-size:13px;">Days</small>@else—@endif</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Labor Cost</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Total labor cost</span>
                        <span class="fd-ov-val" style="color:{{ $totalLaborCost > 0 ? '#4ade80' : 'rgba(255,255,255,0.35)' }};">{{ $totalLaborCost > 0 ? '₱' . number_format($totalLaborCost, 2) : '—' }}</span>
                    </div>
                </div>
            </div>
            {{-- Quotation Builder — pricing, materials and labor in one card with a single Save --}}
            @php
                // Pricing was agreed with the client when the project was created and lives in its Payment record.
                $pay          = $project->getPaymentRecord();
                $payBudget    = $pay ? (float) $pay->project_budget : 0;
                $payMarkup    = $pay ? (float) $pay->markup : 0;
                $payContract  = $pay ? (float) $pay->contract_amount : 0;
                $markupPct    = $payBudget > 0 ? round($payMarkup / $payBudget * 100, 2) : null;
                $termsLocked  = $pay && $pay->transactions()->exists();   // terms freeze once a payment is recorded
                $termsLabel   = ['big_project' => 'Big Project — 3 Phases (50% / 30% / 20%)', 'small_project' => 'Small Project — 2 Phases (50% / 50%)'][$pay->payment_term_type ?? ''] ?? null;
            @endphp
            <form method="POST" action="{{ route('admin.project_materials.save', $project->id) }}" id="quotationForm" class="qb-card{{ $readOnly ? ' qb-readonly' : '' }}" novalidate>
                @csrf

                <div class="qb-head">
                    <div>
                        <h2 class="qb-title"><i data-lucide="calculator"></i> Quotation Builder</h2>
                        <p class="qb-sub">Manage this project's materials and labor below, then save everything at once.</p>
                    </div>
                    <div class="qb-head-right">
                        <span class="qb-dirty" id="qbDirty" hidden><i data-lucide="circle-dot"></i> Unsaved changes</span>
                        <label class="qb-chip" for="entryDateInput" title="Leave blank for today; set an earlier date when backfilling history">
                            Entry Date <span class="qb-optional">(optional)</span>
                            <input type="date" name="entry_date" id="entryDateInput" style="width:auto;text-align:left;">
                        </label>
                    </div>
                </div>

                <div class="qb-errors" id="qbErrors" role="alert" hidden></div>
                @if($errors->any())
                <div class="qb-errors" role="alert">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
                @endif

                {{-- 1 · Pricing — saved to the project's Payment record --}}
                <section class="qb-section">
                    <div class="qb-section-head">
                        <div class="qb-section-title">
                            <span class="qb-step">1</span>
                            <div>
                                <h3>Pricing</h3>
                                <p>
                                    @if($pay)
                                        Markup and payment terms — not shown to the client. Changing them updates this project's contract value.
                                    @else
                                        No payment record exists for this project yet, so pricing can't be set here.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="qb-grid">
                        <div class="qb-field">
                            <label for="markupInput">Markup / Profit (%) @if($pay && !$readOnly)<span class="qb-req">*</span>@endif</label>
                            <input type="number" name="markup_percent" id="markupInput" min="0" max="100" step="0.01" placeholder="e.g. 10"
                                   class="qb-input{{ ($pay && !$readOnly) ? '' : ' qb-locked' }}" {{ ($pay && !$readOnly) ? '' : 'disabled' }}
                                   value="{{ $markupPct !== null ? rtrim(rtrim(number_format($markupPct, 2, '.', ''), '0'), '.') : '' }}">
                            <small class="qb-value-note">Markup value: <strong id="markupValueNote">{{ $pay ? '₱' . number_format($payMarkup, 0) : '—' }}</strong>
                                <span id="markupBudgetNote">@if($pay && $payBudget > 0) of ₱{{ number_format($payBudget, 0) }} Project Budget @endif</span>
                            </small>
                        </div>
                        <div class="qb-field">
                            <label for="paymentTermsInput">Payment Terms @if($pay && !$readOnly && !$termsLocked)<span class="qb-req">*</span>@endif</label>
                            <select name="payment_term_type" id="paymentTermsInput"
                                    class="qb-input{{ ($pay && !$readOnly && !$termsLocked) ? '' : ' qb-locked' }}" {{ ($pay && !$readOnly && !$termsLocked) ? '' : 'disabled' }}>
                                <option value="" disabled hidden {{ $termsLabel ? '' : 'selected' }}>Select payment terms</option>
                                <option value="big_project" {{ ($pay->payment_term_type ?? '') === 'big_project' ? 'selected' : '' }}>Big Project — 3 Phases (50% / 30% / 20%)</option>
                                <option value="small_project" {{ ($pay->payment_term_type ?? '') === 'small_project' ? 'selected' : '' }}>Small Project — 2 Phases (50% / 50%)</option>
                            </select>
                            @if($termsLocked)
                            <small class="qb-value-note">Locked — a payment has already been recorded for this project.</small>
                            @endif
                        </div>
                    </div>
                </section>
                {{-- 2 · Materials --}}
                <section class="qb-section">
                    <div class="qb-section-head">
                        <div class="qb-section-title">
                            <span class="qb-step">2</span>
                            <div>
                                <h3>Materials</h3>
                                <p>Everything the build needs, priced per unit.</p>
                            </div>
                        </div>
                        <div class="qb-section-tools">
                            <label class="qb-chip" for="materialFactorInput">
                                Material Factor <span class="qb-req">*</span>
                                <input type="number" name="factor" id="materialFactorInput" min="0" max="100" step="0.1" value="{{ $materials->isNotEmpty() ? $materialFactor : '' }}" placeholder="0">
                                <span>%</span>
                            </label>
                            <button type="button" class="cancel-btn" id="openMaterialsBOMModal">
                                <i data-lucide="file-text"></i>
                                Generate BOM
                            </button>
                            <button type="button" class="add-btn" id="addMaterialRowBtn">
                                <i data-lucide="plus"></i>
                                Add Material
                            </button>
                        </div>
                    </div>

                    <div class="qb-table-wrap">
                        <table class="qb-table" id="materialsTable">
                            <thead>
                                <tr>
                                    <th style="width:40px;">#</th>
                                    <th>Material Name <span class="qb-req">*</span></th>
                                    <th style="width:100px;">Unit <span class="qb-req">*</span></th>
                                    <th style="width:120px;">Quantity <span class="qb-req">*</span></th>
                                    <th style="width:140px;">Price / Unit <span class="qb-req">*</span></th>
                                    <th style="width:140px;" class="qb-num">Total Cost</th>
                                    <th style="width:48px;"></th>
                                </tr>
                            </thead>
                            <tbody id="materialRowsContainer"></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="qb-total-label">Grand Total (no Material Factor)</td>
                                    <td class="qb-total-value" id="matSubtotal">₱0</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="qb-total-label">Grand Total (with <span id="matFactorLabel">{{ number_format($materialFactor, 1) }}</span>% Material Factor)</td>
                                    <td class="qb-total-value" id="matWithFactor">₱0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        <div class="qb-empty" id="materialsEmpty" hidden>
                            No materials added yet. Click <strong>Add Material</strong> to get started.
                        </div>
                    </div>
                </section>

                {{-- 3 · Labor --}}
                <section class="qb-section">
                    <div class="qb-section-head">
                        <div class="qb-section-title">
                            <span class="qb-step">3</span>
                            <div>
                                <h3>Labor</h3>
                                <p>Crew and daily rates — the working days apply to every row.</p>
                            </div>
                        </div>
                        <div class="qb-section-tools">
                            <label class="qb-chip" for="estDaysInput">
                                Estimated Working Days <span class="qb-req">*</span>
                                <input type="number" name="estimated_working_days" id="estDaysInput" min="0" step="0.01" value="{{ $project->estimated_working_days ?: '' }}" placeholder="0">
                            </label>
                            <button type="button" class="add-btn" id="addLaborRowBtn">
                                <i data-lucide="plus"></i>
                                Add Labor
                            </button>
                        </div>
                    </div>

                    <div class="qb-table-wrap">
                        <table class="qb-table" id="laborTable" style="min-width:680px;">
                            <thead>
                                <tr>
                                    <th style="width:40px;">#</th>
                                    <th>Employee Name <span class="qb-req">*</span></th>
                                    <th style="width:190px;">Role <span class="qb-req">*</span></th>
                                    <th style="width:140px;">Daily Rate <span class="qb-req">*</span></th>
                                    <th style="width:140px;" class="qb-num">Total Cost</th>
                                    <th style="width:96px;"></th>
                                </tr>
                            </thead>
                            <tbody id="laborRowsContainer"></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="qb-total-label">Grand Total</td>
                                    <td class="qb-total-value" id="laborTotal">₱0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        <div class="qb-empty" id="laborEmpty" hidden>
                            No labor entries added yet. Click <strong>Add Labor</strong> to get started.
                        </div>
                    </div>
                </section>


                {{-- Summary + the one Save — the last row of the card --}}
                <div class="qb-foot">
                    <div class="qb-summary">
                        <div class="qb-sum-item">
                            <span class="qb-sum-label">Project Budget</span>
                            <strong id="sumBudget">{{ $pay ? '₱' . number_format($payBudget, 0) : '₱0' }}</strong>
                            <small id="sumBudgetNote">Materials + Labor</small>
                        </div>
                        <div class="qb-sum-op">+</div>
                        <div class="qb-sum-item">
                            <span class="qb-sum-label">Markup</span>
                            <strong id="sumMarkup">{{ $pay ? '₱' . number_format($payMarkup, 0) : '—' }}</strong>
                            <small id="sumMarkupPct">{{ $markupPct !== null ? rtrim(rtrim(number_format($markupPct, 2, '.', ''), '0'), '.') . '% of budget' : ' ' }}</small>
                        </div>
                        <div class="qb-sum-op">=</div>
                        <div class="qb-sum-item qb-sum-total">
                            <span class="qb-sum-label">Contract Value</span>
                            <strong id="sumContract">{{ $pay ? '₱' . number_format($payContract, 0) : '—' }}</strong>
                        </div>
                    </div>
                    <button type="submit" class="save-btn" id="qbSaveBtn">
                        <i data-lucide="save"></i>
                        Save
                    </button>
                </div>
            </form>

        </main>
    </div>

    {{-- ===================== PICK MATERIALS MODAL ===================== --}}
    <div class="modal-overlay" id="pickMaterialsModal">
        <div class="modal-card" style="max-width:720px;width:95%;">
            <div class="modal-header">
                <div>
                    <h2>Add Materials</h2>
                    <p>Tick every material you need. You can set quantities and prices afterwards, then press Save.</p>
                </div>
                <button class="modal-close" type="button" id="closePickMaterials">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="pm-toolbar">
                <div class="pm-search">
                    <i data-lucide="search"></i>
                    <input type="search" id="pickMaterialsSearch" placeholder="Search materials..." autocomplete="off">
                </div>
                <span class="pm-count" id="pickMaterialsCount">0 selected</span>
            </div>

            <div class="pm-list" id="pickMaterialsList"></div>

            <div class="pm-foot">
                <button type="button" class="pm-custom" id="pickCustomRowBtn">
                    <i data-lucide="plus"></i>
                    Can’t find it? Add a custom row
                </button>
                <div class="pm-foot-actions">
                    <button type="button" class="cancel-btn" id="cancelPickMaterials">Cancel</button>
                    <button type="button" class="save-btn" id="confirmPickMaterials" disabled>
                        <i data-lucide="check"></i>
                        Add Selected
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== PICK LABOR MODAL ===================== --}}
    <div class="modal-overlay" id="pickLaborModal">
        <div class="modal-card" style="max-width:720px;width:95%;">
            <div class="modal-header">
                <div>
                    <h2>Add Labor</h2>
                    <p>Tick every employee on this job. Their role and daily rate fill in automatically — then press Save.</p>
                </div>
                <button class="modal-close" type="button" id="closePickLabor">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="pm-toolbar">
                <div class="pm-search">
                    <i data-lucide="search"></i>
                    <input type="search" id="pickLaborSearch" placeholder="Search by name or role..." autocomplete="off">
                </div>
                <span class="pm-count" id="pickLaborCount">0 selected</span>
            </div>

            <div class="pm-list" id="pickLaborList"></div>

            <div class="pm-foot">
                <button type="button" class="pm-custom" id="pickCustomLaborBtn">
                    <i data-lucide="plus"></i>
                    Not listed? Add a custom row
                </button>
                <div class="pm-foot-actions">
                    <button type="button" class="cancel-btn" id="cancelPickLabor">Cancel</button>
                    <button type="button" class="save-btn" id="confirmPickLabor" disabled>
                        <i data-lucide="check"></i>
                        Add Selected
                    </button>
                </div>
            </div>
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
                <label style="display:flex;align-items:center;gap:7px;font-size:12.5px;font-weight:700;color:var(--dark);cursor:pointer;">
                    <input type="checkbox" id="bomSummaryOnlyToggle">
                    Summary only <span style="font-weight:400;color:var(--muted);">(just the grand total)</span>
                </label>
                <div style="display:flex;gap:10px;">
                    @if($project->current_phase === 'planning' && $project->current_sub_phase === 'quotation')
                    <form method="POST" action="{{ route('admin.project.send_quotation', $project->id) }}"
                          onsubmit="return confirm('Send this quotation to the client? The project will advance to the Payment sub-phase.');">
                        @csrf
                        <button type="submit"
                                style="display:flex;align-items:center;gap:7px;background:#2563eb;border:1.5px solid #2563eb;border-radius:10px;padding:8px 18px;font-size:13px;font-weight:700;color:#fff;cursor:pointer;">
                            <i data-lucide="send" style="width:15px;height:15px;"></i>
                            Send Quotation to Client
                        </button>
                    </form>
                    @endif
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

    @include('admin.partials.material_catalog')

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
    lucide.createIcons();

    var BATCH_MATERIALS = @json($materials->where('status', 'active')->values());
    var BATCH_LABOR     = @json($laborEntries->values());   // includes archived, so they can be restored

    var quotationForm = document.getElementById('quotationForm');
    var QB_READONLY   = {{ $readOnly ? 'true' : 'false' }};   // completed project: view only
    var PAY_EXISTS    = {{ $pay ? 'true' : 'false' }};        // pricing can only be edited when a Payment record exists
    var PAY_BUDGET    = {{ (float) $payBudget }};             // the frozen project budget the markup is a percentage of
    var PAY_TERMS_LOCKED = {{ $termsLocked ? 'true' : 'false' }};

    function openModal(id) { var m = document.getElementById(id); if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; } }
    function closeModal(id) { var m = document.getElementById(id); if (m) { m.classList.remove('show'); document.body.style.overflow = ''; } }

    // ---- helpers ----
    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
    function toNum(v) { var n = parseFloat(v); return isNaN(n) ? 0 : n; }
    function peso(n, decimals) {
        decimals = decimals || 0;
        return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    }

    // Scrolling the mouse wheel over a focused number field would otherwise nudge its value.
    // Dropping focus first lets the wheel scroll the page instead, without changing the number.
    quotationForm.addEventListener('wheel', function (e) {
        var t = e.target;
        if (t && t.tagName === 'INPUT' && t.type === 'number' && document.activeElement === t) t.blur();
    }, { passive: true });

    // ---- unsaved-changes tracking ----
    var qbDirty = false;
    var qbSubmitting = false;
    function markDirty() {
        qbDirty = true;
        var pill = document.getElementById('qbDirty');
        if (pill) pill.hidden = false;
    }
    quotationForm.addEventListener('input', markDirty);
    quotationForm.addEventListener('change', markDirty);
    window.addEventListener('beforeunload', function (e) {
        if (qbDirty && !qbSubmitting) { e.preventDefault(); e.returnValue = ''; }
    });

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
            markDirty();
            recalc();
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

    // ---- Materials ----
    var matContainer = document.getElementById('materialRowsContainer');

    function buildMaterialRow(num, data) {
        data = data || {};
        var tr = document.createElement('tr');
        tr.className = 'material-add-row' + (data.id ? '' : ' qb-row-new');

        tr.innerHTML =
            '<td class="qb-row-num mat-row-label">' + num + '</td>' +
            '<td style="min-width:240px;">' +
                '<input type="hidden" name="material_id[]" value="' + (data.id || '') + '">' +
                '<div class="mat-combo">' +
                    '<input type="text" name="material_name[]" class="qb-cell-input row-mat-name-input" autocomplete="off"' +
                           ' value="' + escapeHtml(data.name || '') + '" placeholder="Search or type material..."' +
                           ' oninput="filterMatCombo(this)" onfocus="openMatCombo(this)">' +
                    '<div class="mat-combo-dropdown"></div>' +
                '</div>' +
                '<div class="mat-combo-warning">This material is already added in another row.</div>' +
            '</td>' +
            '<td><input type="text" name="unit[]" class="qb-cell-input row-unit" autocomplete="off" placeholder="pcs" value="' + escapeHtml(data.unit || '') + '"></td>' +
            '<td><input type="number" name="quantity[]" class="qb-cell-input row-qty" min="0.01" step="0.01" placeholder="0" value="' + (data.qty != null ? data.qty : '') + '" oninput="recalc()"></td>' +
            '<td><input type="number" name="price_per_unit[]" class="qb-cell-input row-price" min="0" step="0.01" placeholder="0.00" value="' + (data.price != null ? data.price : '') + '" oninput="recalc()"></td>' +
            '<td><input type="text" class="qb-cell-total row-total-display" readonly tabindex="-1" placeholder="—"></td>' +
            '<td style="text-align:center;">' +
                '<button type="button" class="qb-icon-btn" onclick="removeMaterialRow(this)" title="' + (data.id ? 'Delete material' : 'Remove row') + '">' +
                    '<i data-lucide="trash-2"></i>' +
                '</button>' +
            '</td>';
        return tr;
    }

    function removeMaterialRow(btn) {
        var row = btn.closest('.material-add-row');
        var idInput = row.querySelector('[name="material_id[]"]');
        if (idInput && idInput.value) {
            // existing material — remember to delete it server-side when Save is pressed
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'delete_material_id[]';
            hidden.value = idInput.value;
            quotationForm.appendChild(hidden);
        }
        row.remove();
        markDirty();
        renumberRows();
        updateEmptyStates();
        recalc();
    }

    // Append one row per material; returns the new rows
    function appendMaterialRows(items) {
        var rows = items.map(function (item) {
            var row = buildMaterialRow(matContainer.querySelectorAll('.material-add-row').length + 1, item);
            matContainer.appendChild(row);
            return row;
        });
        if (typeof lucide !== 'undefined') lucide.createIcons();
        updateEmptyStates();
        recalc();
        markDirty();
        return rows;
    }

    function addBlankMaterialRow() {
        appendMaterialRows([{}])[0].querySelector('.row-mat-name-input').focus();
    }

    // ---- Add Material: pick several from the catalog with checkboxes ----
    var pickList      = document.getElementById('pickMaterialsList');
    var pickSearch    = document.getElementById('pickMaterialsSearch');
    var pickConfirm   = document.getElementById('confirmPickMaterials');
    var pickSelected  = {};
    var pickCategories = Object.keys(MATERIAL_CATALOG);

    function pickSelectedNames() {
        return Object.keys(pickSelected).filter(function (n) { return pickSelected[n]; });
    }

    function updatePickCount() {
        var n = pickSelectedNames().length;
        document.getElementById('pickMaterialsCount').textContent = n + ' selected';
        pickConfirm.disabled = n === 0;
        pickConfirm.lastChild.textContent = n > 0 ? ' Add ' + n + ' Selected' : ' Add Selected';
    }

    function renderPickList() {
        var scroll = pickList.scrollTop;
        var q      = pickSearch.value.toLowerCase().trim();
        var used   = getUsedMaterialNames();
        var html   = '';

        pickCategories.forEach(function (category, ci) {
            var items = MATERIAL_CATALOG[category].filter(function (name) {
                return !q || name.toLowerCase().indexOf(q) !== -1;
            });
            if (items.length === 0) return;

            var available = items.filter(function (name) { return used.indexOf(name.toLowerCase()) === -1; });
            var checkedCount = available.filter(function (name) { return pickSelected[name]; }).length;
            var allChecked = available.length > 0 && checkedCount === available.length;

            html += '<div class="pm-group" data-cat="' + ci + '">' +
                '<label class="pm-cat">' +
                    '<input type="checkbox" class="pm-cat-check"' + (allChecked ? ' checked' : '') + (available.length === 0 ? ' disabled' : '') + '>' +
                    '<span>' + escapeHtml(category) + '</span>' +
                    '<em>' + (checkedCount ? checkedCount + ' of ' : '') + available.length + ' available</em>' +
                '</label>';
            items.forEach(function (name) {
                var isUsed = used.indexOf(name.toLowerCase()) !== -1;
                html += '<label class="pm-item' + (isUsed ? ' is-used' : '') + '">' +
                    '<input type="checkbox" class="pm-check" value="' + escapeHtml(name) + '"' +
                        (isUsed ? ' disabled' : (pickSelected[name] ? ' checked' : '')) + '>' +
                    '<span>' + escapeHtml(name) + '</span>' +
                    (isUsed ? '<span class="pm-badge">Already added</span>' : '') +
                '</label>';
            });
            html += '</div>';
        });

        pickList.innerHTML = html || '<div class="pm-empty">No matches in the catalog. Use “Add a custom row” below to type your own.</div>';
        pickList.scrollTop = scroll;
        updatePickCount();
    }

    pickList.addEventListener('change', function (e) {
        var t = e.target;
        if (t.classList.contains('pm-check')) {
            pickSelected[t.value] = t.checked;
        } else if (t.classList.contains('pm-cat-check')) {
            // tick / untick every available material in that category
            t.closest('.pm-group').querySelectorAll('.pm-check:not(:disabled)').forEach(function (box) {
                pickSelected[box.value] = t.checked;
            });
        } else {
            return;
        }
        renderPickList();
    });
    pickSearch.addEventListener('input', renderPickList);

    document.getElementById('addMaterialRowBtn').addEventListener('click', function () {
        pickSelected = {};
        pickSearch.value = '';
        renderPickList();
        openModal('pickMaterialsModal');
        pickSearch.focus();
    });
    document.getElementById('closePickMaterials').addEventListener('click', function () { closeModal('pickMaterialsModal'); });
    document.getElementById('cancelPickMaterials').addEventListener('click', function () { closeModal('pickMaterialsModal'); });
    document.getElementById('pickCustomRowBtn').addEventListener('click', function () {
        closeModal('pickMaterialsModal');
        addBlankMaterialRow();
    });
    pickConfirm.addEventListener('click', function () {
        var used = getUsedMaterialNames();
        var items = pickSelectedNames()
            .filter(function (name) { return used.indexOf(name.toLowerCase()) === -1; })
            .map(function (name) { return { name: name, unit: (typeof MATERIAL_UNITS !== 'undefined' && MATERIAL_UNITS[name]) || '' }; });
        closeModal('pickMaterialsModal');
        if (items.length === 0) return;
        // price and quantity still need filling in — jump to the first empty quantity
        appendMaterialRows(items)[0].querySelector('.row-qty').focus();
    });

    // ---- Labor ----
    var laborContainer = document.getElementById('laborRowsContainer');

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

    // The placeholder is deliberately NOT disabled: a disabled selected option is left out of the
    // submitted form, which would shift role[] out of line with employee_name[] / daily_rate[].
    function buildLaborRoleOptions() {
        var html = '<option value="" selected>Select Role</option>';
        KNOWN_LABOR_ROLES.forEach(function (role) {
            html += '<option value="' + escapeHtml(role) + '">' + escapeHtml(role) + '</option>';
        });
        html += '<option value="other">Other...</option>';
        return html;
    }

    // prevent the same employee being selected in more than one labor row
    function getUsedLaborEmployeeNames(excludeSelect) {
        var used = [];
        laborContainer.querySelectorAll('.row-labor-name-select').forEach(function (sel) {
            if (sel === excludeSelect) return;
            if (sel.value && sel.value !== 'other') used.push(sel.value);
        });
        laborContainer.querySelectorAll('.labor-existing-row').forEach(function (row) {
            if (row.dataset.employeeName) used.push(row.dataset.employeeName);
        });
        return used;
    }

    function refreshAllLaborEmployeeOptions() {
        laborContainer.querySelectorAll('.row-labor-name-select').forEach(function (sel) {
            var currentValue = sel.value;
            sel.innerHTML = buildLaborEmployeeOptions(currentValue, getUsedLaborEmployeeNames(sel));
            sel.value = currentValue;
        });
    }

    // Saved entries: read-only, but can be archived / restored (applied on Save)
    function buildExistingLaborRow(num, entry) {
        var tr = document.createElement('tr');
        tr.className = 'labor-existing-row' + (entry.status === 'archived' ? ' is-archived' : '');

        var desc = entry.description || '';
        var m = desc.match(/^(.+?)\s*\((.+?)\)\s*$/);
        var name = m ? m[1].trim() : desc;
        var role = m ? m[2].trim() : '';
        tr.dataset.id           = entry.id;
        tr.dataset.status       = entry.status;
        tr.dataset.rate         = entry.daily_rate || 0;
        tr.dataset.employeeName = name;

        tr.innerHTML =
            '<td class="qb-row-num labor-row-label">' + num + '</td>' +
            '<td><div class="qb-static"><strong>' + escapeHtml(name) + '</strong></div></td>' +
            '<td><div class="qb-static">' + (role ? '<span class="qb-role-pill">' + escapeHtml(role) + '</span>' : '<span style="color:var(--muted);">—</span>') + '</div></td>' +
            '<td><div class="qb-static">' + peso(toNum(entry.daily_rate), 2) + '</div></td>' +
            '<td><div class="qb-cell-total existing-labor-total"></div></td>' +
            '<td style="text-align:center;"><button type="button" class="qb-pill-btn" onclick="toggleLaborRow(this)">' +
                (entry.status === 'archived' ? 'Restore' : 'Archive') + '</button></td>';
        return tr;
    }

    function toggleLaborRow(btn) {
        var tr   = btn.closest('tr');
        var td   = btn.closest('td');
        var flag = td.querySelector('input[name="labor_toggle_id[]"]');
        if (flag) {
            flag.remove();
        } else {
            var hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = 'labor_toggle_id[]';
            hidden.value = tr.dataset.id;
            td.appendChild(hidden);
        }
        var toggled = !!td.querySelector('input[name="labor_toggle_id[]"]');
        var active  = (tr.dataset.status === 'active') !== toggled;
        tr.classList.toggle('is-archived', !active);
        btn.textContent = active ? 'Archive' : 'Restore';
        markDirty();
        recalc();
    }

    function buildLaborRow(num) {
        var tr = document.createElement('tr');
        tr.className = 'labor-add-row qb-row-new';
        tr.innerHTML =
            '<td class="qb-row-num labor-row-label">' + num + '</td>' +
            '<td style="min-width:180px;">' +
                '<select name="employee_name[]" class="qb-cell-input row-labor-name-select" onchange="onLaborEmployeeChange(this)">' +
                    buildLaborEmployeeOptions('', getUsedLaborEmployeeNames()) +
                '</select>' +
                '<input type="text" class="qb-cell-input row-labor-name-custom" name="_emp_unused" placeholder="Employee Name" style="display:none;margin-top:6px;">' +
            '</td>' +
            '<td>' +
                '<select name="role[]" class="qb-cell-input row-labor-role-select" onchange="toggleRowLaborRole(this)">' +
                    buildLaborRoleOptions() +
                '</select>' +
                '<input type="text" class="qb-cell-input row-labor-role-custom" name="_role_unused" placeholder="Role" style="display:none;margin-top:6px;">' +
            '</td>' +
            '<td><input type="number" name="daily_rate[]" class="qb-cell-input row-labor-rate" min="0" step="0.01" readonly placeholder="0.00" oninput="recalc()"></td>' +
            '<td><input type="text" class="qb-cell-total row-labor-total-display" readonly tabindex="-1" placeholder="—"></td>' +
            '<td style="text-align:center;">' +
                '<button type="button" class="qb-icon-btn" onclick="removeLaborRow(this)" title="Remove row"><i data-lucide="trash-2"></i></button>' +
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
            nameCustom.value = '';
            nameCustom.name = 'employee_name[]';
            sel.removeAttribute('name');

            rateInput.readOnly = false;
            rateInput.value = '';
            recalc();
            refreshAllLaborEmployeeOptions();
            return;
        }

        nameCustom.style.display = 'none';
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
        rateInput.value = rate;
        recalc();
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

    function removeLaborRow(btn) {
        btn.closest('.labor-add-row').remove();
        markDirty();
        renumberRows();
        updateEmptyStates();
        refreshAllLaborEmployeeOptions();
        recalc();
    }

    function addBlankLaborRow() {
        var row = buildLaborRow(laborContainer.querySelectorAll('tr').length + 1);
        laborContainer.appendChild(row);
        if (typeof lucide !== 'undefined') lucide.createIcons();
        updateEmptyStates();
        markDirty();
        row.querySelector('.row-labor-name-select').focus();
    }

    // ---- Add Labor: pick several employees with checkboxes ----
    var laborPickList     = document.getElementById('pickLaborList');
    var laborPickSearch   = document.getElementById('pickLaborSearch');
    var laborPickConfirm  = document.getElementById('confirmPickLabor');
    var laborPickSelected = {};

    // employees grouped by role, e.g. Welder → [...]
    function laborPickGroups() {
        var groups = {};
        LABOR_EMPLOYEES.forEach(function (emp) {
            var key = emp.role || 'No role assigned';
            (groups[key] = groups[key] || []).push(emp);
        });
        return groups;
    }

    function laborPickSelectedNames() {
        return Object.keys(laborPickSelected).filter(function (n) { return laborPickSelected[n]; });
    }

    function updateLaborPickCount() {
        var n = laborPickSelectedNames().length;
        document.getElementById('pickLaborCount').textContent = n + ' selected';
        laborPickConfirm.disabled = n === 0;
        laborPickConfirm.lastChild.textContent = n > 0 ? ' Add ' + n + ' Selected' : ' Add Selected';
    }

    function renderLaborPickList() {
        var scroll = laborPickList.scrollTop;
        var q      = laborPickSearch.value.toLowerCase().trim();
        var used   = getUsedLaborEmployeeNames();
        var groups = laborPickGroups();
        var html   = '';

        Object.keys(groups).forEach(function (role, gi) {
            var people = groups[role].filter(function (emp) {
                return !q || emp.name.toLowerCase().indexOf(q) !== -1 || role.toLowerCase().indexOf(q) !== -1;
            });
            if (people.length === 0) return;

            var available    = people.filter(function (emp) { return used.indexOf(emp.name) === -1; });
            var checkedCount = available.filter(function (emp) { return laborPickSelected[emp.name]; }).length;
            var allChecked   = available.length > 0 && checkedCount === available.length;

            html += '<div class="pm-group">' +
                '<label class="pm-cat">' +
                    '<input type="checkbox" class="pm-cat-check"' + (allChecked ? ' checked' : '') + (available.length === 0 ? ' disabled' : '') + '>' +
                    '<span>' + escapeHtml(role) + '</span>' +
                    '<em>' + (checkedCount ? checkedCount + ' of ' : '') + available.length + ' available</em>' +
                '</label>';
            people.forEach(function (emp) {
                var isUsed = used.indexOf(emp.name) !== -1;
                html += '<label class="pm-item' + (isUsed ? ' is-used' : '') + '">' +
                    '<input type="checkbox" class="pm-check" value="' + escapeHtml(emp.name) + '"' +
                        (isUsed ? ' disabled' : (laborPickSelected[emp.name] ? ' checked' : '')) + '>' +
                    '<span>' + escapeHtml(emp.name) + '</span>' +
                    (isUsed
                        ? '<span class="pm-badge">Already added</span>'
                        : '<span class="pm-rate">' + peso(toNum(emp.daily_rate), 2) + ' / day</span>') +
                '</label>';
            });
            html += '</div>';
        });

        laborPickList.innerHTML = html || '<div class="pm-empty">No employees found. Use “Add a custom row” below to type a name.</div>';
        laborPickList.scrollTop = scroll;
        updateLaborPickCount();
    }

    laborPickList.addEventListener('change', function (e) {
        var t = e.target;
        if (t.classList.contains('pm-check')) {
            laborPickSelected[t.value] = t.checked;
        } else if (t.classList.contains('pm-cat-check')) {
            t.closest('.pm-group').querySelectorAll('.pm-check:not(:disabled)').forEach(function (box) {
                laborPickSelected[box.value] = t.checked;
            });
        } else {
            return;
        }
        renderLaborPickList();
    });
    laborPickSearch.addEventListener('input', renderLaborPickList);

    document.getElementById('addLaborRowBtn').addEventListener('click', function () {
        laborPickSelected = {};
        laborPickSearch.value = '';
        renderLaborPickList();
        openModal('pickLaborModal');
        laborPickSearch.focus();
    });
    document.getElementById('closePickLabor').addEventListener('click', function () { closeModal('pickLaborModal'); });
    document.getElementById('cancelPickLabor').addEventListener('click', function () { closeModal('pickLaborModal'); });
    document.getElementById('pickCustomLaborBtn').addEventListener('click', function () {
        closeModal('pickLaborModal');
        addBlankLaborRow();
    });
    laborPickConfirm.addEventListener('click', function () {
        var used  = getUsedLaborEmployeeNames();
        var names = laborPickSelectedNames().filter(function (n) { return used.indexOf(n) === -1; });
        closeModal('pickLaborModal');
        if (names.length === 0) return;

        names.forEach(function (name) {
            var row = buildLaborRow(laborContainer.querySelectorAll('tr').length + 1);
            laborContainer.appendChild(row);
            // choosing the employee fills in their role and daily rate, exactly like picking them by hand
            var sel = row.querySelector('.row-labor-name-select');
            sel.value = name;
            onLaborEmployeeChange(sel);
        });
        if (typeof lucide !== 'undefined') lucide.createIcons();
        updateEmptyStates();
        recalc();
        markDirty();

        // the shared working days drive every total — prompt for it if it's still empty
        var daysEl = document.getElementById('estDaysInput');
        if (daysEl.value.trim() === '') daysEl.focus();
    });

    // ---- shared: numbering, empty states, live totals ----
    function renumberRows() {
        matContainer.querySelectorAll('.material-add-row').forEach(function (row, i) {
            row.querySelector('.mat-row-label').textContent = i + 1;
        });
        laborContainer.querySelectorAll('tr').forEach(function (row, i) {
            row.querySelector('.labor-row-label').textContent = i + 1;
        });
    }

    function updateEmptyStates() {
        var hasMat   = matContainer.querySelector('.material-add-row') !== null;
        var hasLabor = laborContainer.querySelector('tr') !== null;
        document.getElementById('materialsTable').style.display = hasMat ? '' : 'none';
        document.getElementById('materialsEmpty').hidden = hasMat;
        document.getElementById('laborTable').style.display = hasLabor ? '' : 'none';
        document.getElementById('laborEmpty').hidden = hasLabor;
    }

    function recalc() {
        // Materials
        var subtotal = 0;
        matContainer.querySelectorAll('.material-add-row').forEach(function (row) {
            var total = toNum(row.querySelector('.row-qty').value) * toNum(row.querySelector('.row-price').value);
            subtotal += total;
            row.querySelector('.row-total-display').value = total > 0 ? peso(total, 2) : '';
        });
        var factor     = toNum(document.getElementById('materialFactorInput').value);
        var withFactor = subtotal * (1 + factor / 100);
        document.getElementById('matSubtotal').textContent   = peso(Math.round(subtotal));
        document.getElementById('matWithFactor').textContent = peso(Math.round(withFactor));
        document.getElementById('matFactorLabel').textContent = factor.toFixed(1);

        // Labor — every row's total is its daily rate x the shared working days
        var days = toNum(document.getElementById('estDaysInput').value);
        var laborTotal = 0;
        laborContainer.querySelectorAll('tr').forEach(function (row) {
            if (row.classList.contains('labor-existing-row')) {
                var t = toNum(row.dataset.rate) * days;
                row.querySelector('.existing-labor-total').textContent = peso(t, 2);
                if (!row.classList.contains('is-archived')) laborTotal += t;
            } else {
                var nt = toNum(row.querySelector('.row-labor-rate').value) * days;
                row.querySelector('.row-labor-total-display').value = nt > 0 ? peso(nt, 2) : '';
                laborTotal += nt;
            }
        });
        document.getElementById('laborTotal').textContent = peso(Math.round(laborTotal));

        // Summary bar
        // Pricing: markup is a percentage of the agreed (frozen) budget, so the equation always adds up.
        // The live materials + labor estimate is shown next to it for reference.
        var liveBudget = withFactor + laborTotal;
        if (PAY_EXISTS) {
            var base   = PAY_BUDGET > 0 ? PAY_BUDGET : liveBudget;
            var pct    = toNum(document.getElementById('markupInput').value);
            var markup = base * pct / 100;
            document.getElementById('sumBudget').textContent      = peso(Math.round(base));
            document.getElementById('sumBudgetNote').textContent  = 'Current estimate ' + peso(Math.round(liveBudget));
            document.getElementById('sumMarkup').textContent      = peso(Math.round(markup));
            document.getElementById('sumMarkupPct').textContent   = String(pct) + '% of budget';
            document.getElementById('sumContract').textContent    = peso(Math.round(base + markup));
            document.getElementById('markupValueNote').textContent  = peso(Math.round(markup));
            document.getElementById('markupBudgetNote').textContent = 'of ' + peso(Math.round(base)) + ' Project Budget';
        } else {
            document.getElementById('sumBudget').textContent = peso(Math.round(liveBudget));
        }
    }
    ['markupInput', 'materialFactorInput', 'estDaysInput'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', recalc);
    });

    // ---- validate + submit ----
    // Nothing is saved until the whole quotation is complete: every field marked * is filled,
    // there is at least one material and one labor entry, and every row is fully filled in.
    var qbErrorTargets = [];

    function showQbErrors(items) {
        var box = document.getElementById('qbErrors');
        qbErrorTargets = items.map(function (it) { return it.el; });
        // One short sentence instead of a tall list — each item is still a link that jumps to its field.
        var links = items.map(function (it, i) {
            return '<span class="qb-err-link" role="button" tabindex="0" data-i="' + i + '">' + escapeHtml(it.msg) + '</span>';
        });
        var hasCommas = items.some(function (it) { return it.msg.indexOf(',') !== -1; });   // "Material row 2: unit, price"
        var text;
        if (links.length === 1) text = links[0];
        else if (hasCommas)     text = links.join('; ');
        else                    text = links.slice(0, -1).join(', ') + ', and ' + links[links.length - 1];
        box.innerHTML = '<strong class="qb-err-lead">Please complete:</strong> ' + text + '.';
        box.hidden = false;
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // clicking an item jumps to the field it is about
    document.getElementById('qbErrors').addEventListener('click', function (e) {
        var item = e.target.closest('.qb-err-link');
        var el = item ? qbErrorTargets[+item.dataset.i] : null;
        if (!el) return;
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (el.focus) setTimeout(function () { el.focus({ preventScroll: true }); }, 250);
    });

    // a field stops being flagged as soon as it is edited
    ['input', 'change'].forEach(function (evt) {
        quotationForm.addEventListener(evt, function (e) {
            if (e.target.classList) e.target.classList.remove('qb-bad');
        });
    });

    quotationForm.addEventListener('submit', function (e) {
        var problems = [];
        quotationForm.querySelectorAll('.qb-bad').forEach(function (el) { el.classList.remove('qb-bad'); });
        quotationForm.querySelectorAll('.mat-combo-warning.show').forEach(function (el) { el.classList.remove('show'); });

        function flag(el, msg) {
            if (el) el.classList.add('qb-bad');
            problems.push({ el: el, msg: msg });
        }
        // several missing fields on one row become a single line: "Materials row 2: unit, price"
        function flagRow(section, n, missing) {
            if (missing.length === 0) return;
            problems.push({ el: missing[0].el, msg: section + ' row ' + n + ': ' + missing.map(function (m) { return m.label; }).join(', ') });
            missing.forEach(function (m) { m.el.classList.add('qb-bad'); });
        }

        // Rows left completely blank are just ignored, not treated as mistakes
        matContainer.querySelectorAll('.material-add-row').forEach(function (row) {
            var isNew = !row.querySelector('[name="material_id[]"]').value;
            if (isNew && !row.querySelector('.row-mat-name-input').value.trim() &&
                !row.querySelector('.row-unit').value.trim() &&
                !row.querySelector('.row-qty').value && !row.querySelector('.row-price').value) {
                row.remove();
            }
        });
        laborContainer.querySelectorAll('.labor-add-row').forEach(function (row) {
            var sel = row.querySelector('.row-labor-name-select');
            var custom = row.querySelector('.row-labor-name-custom');
            var hasEmployee = (sel.value && sel.value !== 'other') || (sel.value === 'other' && custom.value.trim());
            if (!hasEmployee && !row.querySelector('.row-labor-rate').value) row.remove();
        });
        renumberRows();
        updateEmptyStates();
        recalc();

        // Pricing (only when it is editable)
        if (PAY_EXISTS && !QB_READONLY) {
            var markupEl = document.getElementById('markupInput');
            var pct = markupEl.value.trim();
            if (pct === '') flag(markupEl, 'Markup / Profit');
            else if (isNaN(pct) || +pct < 0 || +pct > 100) flag(markupEl, 'Markup / Profit (0–100)');

            var termsEl = document.getElementById('paymentTermsInput');
            if (!PAY_TERMS_LOCKED && !termsEl.value) flag(termsEl, 'Payment Terms');
        }

        // Materials
        var factorEl = document.getElementById('materialFactorInput');
        var factor = factorEl.value.trim();
        if (factor === '') flag(factorEl, 'Material Factor');
        else if (isNaN(factor) || +factor < 0 || +factor > 100) flag(factorEl, 'Material Factor (0–100)');

        var matRows = matContainer.querySelectorAll('.material-add-row');
        if (matRows.length === 0) {
            var matWrap = document.getElementById('materialsEmpty').parentNode;
            flag(matWrap, 'at least one material');
        }
        var seen = [];
        matRows.forEach(function (row, i) {
            var nameEl  = row.querySelector('.row-mat-name-input');
            var unitEl  = row.querySelector('.row-unit');
            var qtyEl   = row.querySelector('.row-qty');
            var priceEl = row.querySelector('.row-price');
            var name    = nameEl.value.trim();
            var missing = [];
            if (!name) missing.push({ el: nameEl, label: 'name' });
            if (!unitEl.value.trim()) missing.push({ el: unitEl, label: 'unit' });
            if (!(toNum(qtyEl.value) > 0)) missing.push({ el: qtyEl, label: 'quantity' });
            if (priceEl.value.trim() === '' || toNum(priceEl.value) < 0) missing.push({ el: priceEl, label: 'price' });
            flagRow('Material', i + 1, missing);

            if (name) {
                var lower = name.toLowerCase();
                if (seen.indexOf(lower) !== -1) {
                    flag(nameEl, 'Material row ' + (i + 1) + ': already listed');
                    var warning = row.querySelector('.mat-combo-warning');
                    if (warning) warning.classList.add('show');
                }
                seen.push(lower);
            }
        });

        // Labor
        var daysEl = document.getElementById('estDaysInput');
        if (daysEl.value.trim() === '') flag(daysEl, 'Estimated Working Days');
        else if (!(toNum(daysEl.value) > 0)) flag(daysEl, 'Estimated Working Days (above 0)');

        var newLaborRows = laborContainer.querySelectorAll('.labor-add-row');
        var activeSavedLabor = laborContainer.querySelectorAll('.labor-existing-row:not(.is-archived)').length;
        if (newLaborRows.length + activeSavedLabor === 0) {
            var laborWrap = document.getElementById('laborEmpty').parentNode;
            flag(laborWrap, 'at least one labor entry');
        }
        laborContainer.querySelectorAll('tr').forEach(function (row, i) {
            if (!row.classList.contains('labor-add-row')) return;
            var sel     = row.querySelector('.row-labor-name-select');
            var custom  = row.querySelector('.row-labor-name-custom');
            var roleSel = row.querySelector('.row-labor-role-select');
            var roleCus = row.querySelector('.row-labor-role-custom');
            var rateEl  = row.querySelector('.row-labor-rate');
            var missing = [];
            if (!sel.value) missing.push({ el: sel, label: 'employee' });
            else if (sel.value === 'other' && !custom.value.trim()) missing.push({ el: custom, label: 'employee name' });
            if (!roleSel.value) missing.push({ el: roleSel, label: 'role' });
            else if (roleSel.value === 'other' && !roleCus.value.trim()) missing.push({ el: roleCus, label: 'role' });
            if (rateEl.value.trim() === '' || toNum(rateEl.value) < 0) missing.push({ el: rateEl, label: 'daily rate' });
            flagRow('Labor', i + 1, missing);
        });

        if (problems.length > 0) {
            e.preventDefault();
            showQbErrors(problems);
            return;
        }

        document.getElementById('qbErrors').hidden = true;
        qbSubmitting = true;
        var saveBtn = document.getElementById('qbSaveBtn');
        saveBtn.disabled = true;
        saveBtn.lastChild.textContent = ' Saving…';
    });

    // View-only mode: nothing in the builder can be edited or submitted.
    function lockBuilder() {
        quotationForm.querySelectorAll('input, select, textarea').forEach(function (el) { el.disabled = true; });
        quotationForm.addEventListener('submit', function (e) { e.preventDefault(); }, true);
    }

    // ---- initial render (saved rows come from the server) ----
    BATCH_MATERIALS.forEach(function (mat, i) {
        matContainer.appendChild(buildMaterialRow(i + 1, {
            id: mat.id, name: mat.material_name, qty: mat.quantity, price: mat.price_per_unit, unit: mat.unit
        }));
    });
    BATCH_LABOR.forEach(function (entry, i) {
        laborContainer.appendChild(buildExistingLaborRow(i + 1, entry));
    });
    updateEmptyStates();
    recalc();
    if (QB_READONLY) lockBuilder();
    if (typeof lucide !== 'undefined') lucide.createIcons();
            var ESTIMATED_DAYS = {{ (float) $project->estimated_working_days }};   // saved value — the BOM printouts use what is stored

        // ---- BOM ----
        var BOM_MATERIALS = @json($materials->where('status', 'active')->values());
        var BOM_LABOR     = @json($laborEntries->where('status', 'active')->values());
        var LOGO_LEFT     = @json(asset('images/logo-left.png'));
        var LOGO_RIGHT    = @json(asset('images/logo-right.png'));
        var BS_CSS_URL    = @json(asset('css/billing_statement.css'));

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

            var summaryOnly = !!(document.getElementById('bomSummaryOnlyToggle') && document.getElementById('bomSummaryOnlyToggle').checked);

            if (summaryOnly) {
                var summaryHtml =
                    '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Project Quotation — {{ $project->name }}</title>' +
                    '<link rel="stylesheet" href="' + BS_CSS_URL + '">' +
                    '<style>' +
                        '.pq-total-box{margin-top:28px;border:2px solid #1a1a1a;border-radius:12px;padding:36px 24px;text-align:center;background:#f7f9fc;}' +
                        '.pq-total-label{font-size:12.5px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#555;margin-bottom:10px;}' +
                        '.pq-total-value{font-size:38px;font-weight:900;color:#1a1a1a;}' +
                        '.pq-note{margin-top:22px;font-size:11.5px;color:#777;text-align:center;line-height:1.6;}' +
                        '@media print{.pq-total-box{page-break-inside:avoid;}}' +
                    '</style></head><body>' +
                    '<div class="bs-sheet">' +
                        '<div class="bs-letterhead">' +
                            '<div class="bs-logo-group">' +
                                '<img src="' + LOGO_LEFT + '" alt="GMD South Phils" class="bs-logo">' +
                                '<img src="' + LOGO_RIGHT + '" alt="" class="bs-logo">' +
                            '</div>' +
                            '<div class="bs-company-info">' +
                                '<div class="bs-company-name">GMD South Phils Metal Fabrication Works</div>' +
                                '<div>National Hi-way, Brgy. Masiit, Calauan, Laguna</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="bs-title">PROJECT QUOTATION</div>' +
                        '<table class="bs-fields">' +
                            '<tr>' +
                                '<td class="bs-field-label">Project:</td>' +
                                '<td class="bs-field-value" colspan="3">{{ $project->name }}</td>' +
                            '</tr>' +
                            '<tr>' +
                                '<td class="bs-field-label">Client:</td>' +
                                '<td class="bs-field-value">{{ $project->live_client_name }}</td>' +
                                '<td class="bs-field-label">Date:</td>' +
                                '<td class="bs-field-value">' + today + '</td>' +
                            '</tr>' +
                        '</table>' +
                        '<div class="pq-total-box">' +
                            '<div class="pq-total-label">Project Grand Total</div>' +
                            '<div class="pq-total-value">₱' + fmt(grandAdj) + '</div>' +
                        '</div>' +
                        '<div class="pq-note">After reviewing the project requirements, we have arrived at the total above. Please let us know if you would like any adjustments.</div>' +
                    '</div>' +
                    (withPrintScript ? '<script>window.onload=function(){window.print();}<\/script>' : '') +
                    '</body></html>';
                return summaryHtml;
            }

            var css = 'body{font-family:Arial,sans-serif;font-size:13px;color:#111;margin:32px;}' +
                'h1{font-size:20px;margin:0 0 4px;}h2{font-size:14px;margin:24px 0 8px;padding:6px 0;border-bottom:2px solid #ccc;}' +
                'p.sub{color:#666;margin:0 0 16px;font-size:12px;}' +
                'table{width:100%;border-collapse:collapse;margin-bottom:8px;}' +
                'th{background:#f0f0f0;padding:8px 10px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #ccc;}' +
                'td{padding:8px 10px;border-bottom:1px solid #e5e5e5;}.r{text-align:right;}' +
                '.subtotal td{font-weight:700;background:#f8f8f8;border-top:2px solid #bbb;}' +
                '.grand td{font-size:14px;font-weight:900;background:#111;color:#fff;border:none;}' +
                '@media print{body{margin:16px;} *{-webkit-print-color-adjust:exact;print-color-adjust:exact;}}';

            var body =
                '<h1>Bill of Materials — {{ $project->name }}</h1>' +
                '<p class="sub">Client: {{ $project->live_client_name }} &nbsp;|&nbsp; Generated: ' + today + ' &nbsp;|&nbsp; Material Factor: {{ number_format($materialFactor, 1) }}%</p>' +
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

            var html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>BOM — {{ $project->name }}</title><style>' + css + '</style></head><body>' + body;

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
            var summaryOnly = !!(document.getElementById('bomSummaryOnlyToggle') && document.getElementById('bomSummaryOnlyToggle').checked);
            var blob = new Blob([buildBOMDocument(false)], { type: 'text/html' });
            var url  = URL.createObjectURL(blob);
            var a    = document.createElement('a');
            a.href     = url;
            a.download = (summaryOnly ? 'Quotation Summary - ' : 'Quotation - ') + '{{ str_replace("/", "-", $project->name) }}.html';
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
                '<p class="sub">Client: {{ $project->live_client_name }} &nbsp;|&nbsp; Generated: ' + today + ' &nbsp;|&nbsp; Material Factor: {{ number_format($materialFactor, 1) }}%</p>' +
                '<table><thead><tr><th>#</th><th>Material Name</th><th class="r">Qty</th><th class="r">Price/Unit</th><th class="r">Base Total</th><th class="r">Adjusted Total</th></tr></thead>' +
                '<tbody>' + (matRows || '<tr><td colspan="6">No materials.</td></tr>') + '</tbody>' +
                '</table>' +
                '<table><tr class="grand"><td colspan="5" class="r">Materials Total</td><td class="r">₱' + fmt(matAdjTotal) + '</td></tr></table>' +
                '<script>window.onload=function(){window.print();}<\/script></body></html>');
            win.document.close();
        }
        // ---- end materials-only BOM ----
        // ---- end BOM ----


    // ---- BOM / Generate Project Quotations modals ----
    var openBOMBtn = document.getElementById('openBOMModal');
    if (openBOMBtn) openBOMBtn.addEventListener('click', function () {
        updateBOM();
        var summaryToggle = document.getElementById('bomSummaryOnlyToggle');
        if (summaryToggle) summaryToggle.checked = false;
        openModal('bomModal');
    });
    ['closeBOMModal', 'cancelBOMModal'].forEach(function (id) {
        var btn = document.getElementById(id);
        if (btn) btn.addEventListener('click', function () { closeModal('bomModal'); });
    });
    var printBOMBtn = document.getElementById('printBOMBtn');
    if (printBOMBtn) printBOMBtn.addEventListener('click', printBOM);
    var downloadBOMBtn = document.getElementById('downloadBOMBtn');
    if (downloadBOMBtn) downloadBOMBtn.addEventListener('click', downloadBOM);

    var openMaterialsBOMBtn = document.getElementById('openMaterialsBOMModal');
    if (openMaterialsBOMBtn) openMaterialsBOMBtn.addEventListener('click', function () {
        updateMaterialsBOM();
        openModal('materialsBOMModal');
    });
    ['closeMaterialsBOMModal', 'cancelMaterialsBOMModal'].forEach(function (id) {
        var btn = document.getElementById(id);
        if (btn) btn.addEventListener('click', function () { closeModal('materialsBOMModal'); });
    });
    var printMaterialsBOMBtn = document.getElementById('printMaterialsBOMBtn');
    if (printMaterialsBOMBtn) printMaterialsBOMBtn.addEventListener('click', printMaterialsBOM);

    // clicking a dark backdrop closes any of the modals
    document.querySelectorAll('.modal-overlay').forEach(function (modal) {
        modal.addEventListener('click', function (e) { if (e.target === this) closeModal(this.id); });
    });

    // Auto-open the material picker when redirected from the index with ?openAdd=1
    if (!QB_READONLY && new URLSearchParams(window.location.search).get('openAdd') === '1') {
        document.getElementById('addMaterialRowBtn').click();
        history.replaceState(null, '', window.location.pathname);   // clean the URL without reloading
    }
    </script>
</body>
</html>
