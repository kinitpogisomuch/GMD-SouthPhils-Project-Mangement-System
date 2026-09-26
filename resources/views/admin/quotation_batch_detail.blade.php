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
        /* Requested tank cards */
        .rt-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 14px; }
        .rt-card { background: var(--cream-soft); border: 1px solid var(--border); border-radius: 16px; padding: 14px 18px; display: flex; flex-direction: column; gap: 12px; }
        .rt-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .rt-title { display: inline-flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 900; color: var(--dark); }
        .rt-title i, .rt-title svg { width: 16px; height: 16px; color: var(--muted); }
        .rt-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 800; border-radius: 999px; padding: 5px 12px; background: var(--white); border: 1px solid var(--border); color: var(--dark); }
        .rt-pill i, .rt-pill svg { width: 13px; height: 13px; }
        .rt-pill.is-delivery { background: var(--dark); border-color: var(--dark); color: #fff; }
        .rt-facts { display: flex; flex-wrap: wrap; gap: 14px 48px; }
        .rt-fact { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
        .rt-fact span { font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); }
        .rt-fact strong { font-size: 13.5px; font-weight: 800; color: var(--dark); line-height: 1.4; word-break: break-word; }
        .rt-fact-wide { flex: 1 1 100%; }
        .rt-fact-wide strong { font-weight: 700; }
        .rt-files { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding-top: 10px; border-top: 1px dashed var(--border); }
        .rt-files-label { font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin-right: 2px; }
        .rt-file { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; color: var(--dark); text-decoration: none; background: var(--white); border: 1px solid var(--border); border-radius: 999px; padding: 6px 13px; }
        .rt-file:hover { border-color: var(--dark); }
        .rt-file i, .rt-file svg { width: 13px; height: 13px; color: var(--muted); }
        @media (max-width: 640px) { .rt-list { grid-template-columns: 1fr; } .rt-facts { gap: 12px 28px; } }
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

        .qb-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .qb-head-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .qb-field label { display: block; font-size: 12px; font-weight: 800; color: var(--muted); margin-bottom: 6px; }
        .qb-value-note strong { color: var(--dark); font-weight: 900; }
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
        /* Send to Client stays greyed out until the Quotation Builder is complete */
        #openSendQuotationModal:disabled { opacity: .45; cursor: not-allowed; box-shadow: none; transform: none; }
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

            {{-- Requested tank(s) — what the client asked for, read-only here. One card per tank. --}}
            @php
                // Each tank carries its own design files. Older requests stored the same shared set on every
                // tank — list that once (below the cards) instead of repeating it on each one.
                $tanksWithFiles = $tankItems->filter(fn ($t) => !empty($t->reference_files));
                $legacyShared   = $tankItems->count() > 1
                    && $tanksWithFiles->count() === $tankItems->count()
                    && $tanksWithFiles->map(fn ($t) => json_encode($t->reference_files))->unique()->count() === 1;
            @endphp
            <div class="table-card" style="padding:18px 20px;margin-bottom:20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;">
                    <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);">
                        Requested Tank{{ $tankItems->count() > 1 ? 's' : '' }}
                    </div>
                    <span style="font-size:11.5px;font-weight:700;color:var(--muted);">Submitted by the client</span>
                </div>

                <div class="rt-list">
                    @forelse($tankItems as $item)
                    @php
                        $isPickup = ($item->fulfillment ?? 'delivery') === 'pickup';
                        $files    = (!$legacyShared && !empty($item->reference_files)) ? $item->reference_files : [];
                    @endphp
                    <div class="rt-card">
                        <div class="rt-head">
                            <span class="rt-title"><i data-lucide="package"></i> {{ $item->tank_type ?: 'Tank' }}</span>
                            <span class="rt-pill {{ $isPickup ? 'is-pickup' : 'is-delivery' }}">
                                <i data-lucide="{{ $isPickup ? 'package-check' : 'truck' }}"></i>
                                {{ $isPickup ? 'For pick-up' : 'For delivery' }}
                            </span>
                        </div>

                        <div class="rt-facts">
                            <div class="rt-fact"><span>Capacity / Size</span><strong>{{ $item->capacity ?: '—' }}</strong></div>
                            <div class="rt-fact"><span>Quantity</span><strong>{{ $item->quantity }}</strong></div>
                            <div class="rt-fact"><span>Target Delivery</span><strong>{{ !empty($item->target_timeline) ? $item->target_timeline_display : '—' }}</strong></div>
                            @unless($isPickup)
                            <div class="rt-fact rt-fact-wide">
                                <span>Delivery Address</span>
                                <strong>{{ $item->location ?: '—' }}</strong>
                            </div>
                            @endunless
                        </div>

                        @if($files)
                        <div class="rt-files" data-receipt-set>
                            <span class="rt-files-label">Client's design</span>
                            @foreach($files as $i => $file)
                            <a href="{{ $file }}" target="_blank" data-receipt class="rt-file" title="Open design file {{ $i + 1 }}">
                                <i data-lucide="paperclip"></i> {{ count($files) > 1 ? 'Design ' . ($i + 1) : 'View design' }}
                            </a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @empty
                    <span style="color:var(--muted);font-size:13px;">No tank items on this request.</span>
                    @endforelse
                </div>

                @if($legacyShared)
                <div class="rt-files" style="margin-top:12px;" data-receipt-set>
                    <span class="rt-files-label">Client's attached files</span>
                    @foreach($tankItems->first()->reference_files as $i => $file)
                    <a href="{{ $file }}" target="_blank" data-receipt class="rt-file">
                        <i data-lucide="paperclip"></i> File {{ $i + 1 }}
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
            {{-- Overview --}}
            <div class="fd-overview" style="margin-bottom:24px;">
                <div class="fd-overview-title">
                    <i data-lucide="layout-dashboard"></i>
                    Quotation Overview
                </div>
                <div class="fd-overview-grid overview-grid-3">
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Total Materials</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Active entries</span>
                        <span class="fd-ov-val">{{ $totalMaterials > 0 ? $totalMaterials : '—' }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Material Cost</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Total material cost</span>
                        <span class="fd-ov-val">{{ $estimatedCost > 0 ? '₱' . number_format($estimatedCost, 2) : '—' }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Labor Entries</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Total entries</span>
                        <span class="fd-ov-val">{{ $totalLaborEntries > 0 ? $totalLaborEntries : '—' }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Estimated Working Days</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Applies to all labor rows</span>
                        <span class="fd-ov-val">@if(($batch->estimated_working_days ?? 0) > 0){{ number_format($batch->estimated_working_days, 0) }} <small style="font-size:13px;">Days</small>@else—@endif</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Labor Cost</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Total labor cost</span>
                        <span class="fd-ov-val">{{ $totalLaborCost > 0 ? '₱' . number_format($totalLaborCost, 2) : '—' }}</span>
                    </div>
                    <div class="fd-ov-item fd-ov-highlight">
                        <span class="fd-ov-label">Project Budget</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Est. materials + labor</span>
                        <span class="fd-ov-val" style="color:{{ $estimatedBudget['total'] > 0 ? '#4ade80' : 'rgba(255,255,255,0.35)' }};font-size:17px;">{{ $estimatedBudget['total'] > 0 ? '₱' . number_format(round($estimatedBudget['total']), 0) : '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- Quotation Builder — pricing, materials and labor in one card with a single Save --}}
            <form method="POST" action="{{ route('admin.quotation_requests.batch_save', $batch->id) }}" id="quotationForm" class="qb-card" novalidate>
                @csrf
                <input type="hidden" name="open_send_modal" id="openSendModalFlag" value="0">

                <div class="qb-head">
                    <div>
                        <h2 class="qb-title"><i data-lucide="calculator"></i> Quotation Builder</h2>
                        <p class="qb-sub">Set the pricing, materials and labor below, then save everything at once.</p>
                    </div>
                    <div class="qb-head-right">
                        <span class="qb-dirty" id="qbDirty" hidden><i data-lucide="circle-dot"></i> Unsaved changes</span>
                        <label class="qb-chip" for="entryDateInput" title="Applies to everything added when you save. Leave blank for today; set an earlier date when backfilling history.">
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

                {{-- 1 · Pricing --}}
                <section class="qb-section">
                    <div class="qb-section-head">
                        <div class="qb-section-title">
                            <span class="qb-step">1</span>
                            <div>
                                <h3>Pricing</h3>
                                <p>Markup and payment terms — not shown to the client.</p>
                            </div>
                        </div>
                    </div>
                    <div class="qb-grid">
                        <div class="qb-field">
                            <label for="markupInput">Markup / Profit (%) <span class="qb-req">*</span></label>
                            <input type="number" name="markup_percent" id="markupInput" class="qb-input" min="0" max="100" step="0.01"
                                   value="{{ $batch->markup_percent ?? '' }}" placeholder="e.g. 10">
                            <small class="qb-value-note">Markup value: <strong id="markupValueNote">₱0</strong> <span id="markupBudgetNote">of ₱0 Project Budget</span></small>
                        </div>
                        <div class="qb-field">
                            <label for="paymentTermsInput">Payment Terms <span class="qb-req">*</span></label>
                            <select name="payment_term_type" id="paymentTermsInput" class="qb-input">
                                <option value="" disabled hidden {{ !$batch->payment_term_type ? 'selected' : '' }}>Select payment terms</option>
                                <option value="big_project" {{ $batch->payment_term_type === 'big_project' ? 'selected' : '' }}>Big Project — 3 Phases (50% / 30% / 20%)</option>
                                <option value="small_project" {{ $batch->payment_term_type === 'small_project' ? 'selected' : '' }}>Small Project — 2 Phases (50% / 50%)</option>
                            </select>
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
                                <input type="number" name="estimated_working_days" id="estDaysInput" min="0" step="0.01" value="{{ $batch->estimated_working_days ?? '' }}" placeholder="0">
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
                            <strong id="sumBudget">₱0</strong>
                            <small>Materials + Labor</small>
                        </div>
                        <div class="qb-sum-op">+</div>
                        <div class="qb-sum-item">
                            <span class="qb-sum-label">Markup</span>
                            <strong id="sumMarkup">₱0</strong>
                            <small id="sumMarkupPct">0% of budget</small>
                        </div>
                        <div class="qb-sum-op">=</div>
                        <div class="qb-sum-item qb-sum-total">
                            <span class="qb-sum-label">Contract Value</span>
                            <strong id="sumContract">₱0</strong>
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
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--border);">
                        <div>
                            <span style="display:block;font-size:13px;color:var(--muted);">Project Budget</span>
                            <span style="display:block;margin-top:3px;font-size:12px;color:var(--muted);">Est. Materials ₱{{ number_format($estimatedBudget['materials'], 0) }} + Est. Labor ₱{{ number_format($estimatedBudget['labor'], 0) }}</span>
                        </div>
                        <strong style="font-size:13px;">₱{{ number_format($estimatedBudget['total'], 0) }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;">
                        <span style="font-size:13px;color:var(--muted);">Markup / Profit</span>
                        <strong style="font-size:13px;">₱{{ number_format($batch->markup ?? 0, 0) }}</strong>
                    </div>
                </div>

                <div style="background:var(--dark);border-radius:10px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <span style="font-size:12px;font-weight:700;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.05em;">Contract Value</span>
                    <span style="font-size:20px;font-weight:900;color:#fff;">₱{{ number_format($estimatedBudget['total'] + (float) ($batch->markup ?? 0), 0) }}</span>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label class="log-label">QUOTATION FILE(S) <span style="color:var(--danger);">*</span></label>
                    <label class="pv-upload-dropzone" id="quotationFilesDropzone">
                        <i data-lucide="upload-cloud" style="width:28px;height:28px;color:var(--accent);"></i>
                        <span style="font-size:13.5px;font-weight:700;color:var(--text-primary);">Click to upload the quotation</span>
                        <span style="font-size:12px;color:var(--muted);">PDF or image (JPG / PNG) — up to 5 files, max 10MB each</span>
                        <input type="file" name="quotation_files[]" id="quotationFilesInput" multiple accept=".pdf,.jpg,.jpeg,.png,image/jpeg,image/png"
                               style="display:none;">
                    </label>
                    <div id="quotationFilesPreview" class="pv-file-grid"></div>
                    <div id="quotationFilesError" role="alert" style="display:none;margin-top:8px;font-size:12.5px;font-weight:700;color:#b91c1c;"></div>
                    <p style="font-size:12px;color:var(--muted);margin-top:8px;">This is what the client will see as the quotation.</p>
                </div>

                <div class="form-grid" style="margin-bottom:20px;">
                    <div class="form-group">
                        <label>Sent Date <span style="font-size:11px;color:var(--muted);font-weight:400;">(optional — leave blank for today)</span></label>
                        <input type="date" name="sent_date">
                    </div>
                    <div class="form-group">
                        <label>Sent Time <span style="font-size:11px;color:var(--muted);font-weight:400;">(optional)</span></label>
                        <input type="time" name="sent_time">
                    </div>
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
    var BATCH_LABOR     = @json($laborEntries->values());

    var quotationForm = document.getElementById('quotationForm');

    function openModal(id) { var m = document.getElementById(id); if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; } }
    function closeModal(id) { var m = document.getElementById(id); if (m) { m.classList.remove('show'); document.body.style.overflow = ''; } }

    // ---- Send to Client ----
    var openSendQBtn = document.getElementById('openSendQuotationModal');
    if (openSendQBtn) openSendQBtn.addEventListener('click', function () {
        // Save everything on the page first (through the normal validated submit), then
        // reload and auto-open the modal so it always shows what is actually persisted.
        document.getElementById('openSendModalFlag').value = '1';
        quotationForm.requestSubmit();
    });
    var closeSendQBtn = document.getElementById('closeSendQuotationModal');
    if (closeSendQBtn) closeSendQBtn.addEventListener('click', function () { closeModal('sendQuotationModal'); });
    var cancelSendQBtn = document.getElementById('cancelSendQuotation');
    if (cancelSendQBtn) cancelSendQBtn.addEventListener('click', function () { closeModal('sendQuotationModal'); });

    @if(request('open_send'))
    openModal('sendQuotationModal');
    @endif

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
        updateSendState();
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
        var budget   = withFactor + laborTotal;
        var markup   = budget * toNum(document.getElementById('markupInput').value) / 100;
        document.getElementById('sumBudget').textContent   = peso(Math.round(budget));
        document.getElementById('sumMarkup').textContent   = peso(Math.round(markup));
        document.getElementById('sumMarkupPct').textContent = String(toNum(document.getElementById('markupInput').value)) + '% of budget';
        // the same value, spelled out under the Markup / Profit field
        document.getElementById('markupValueNote').textContent  = peso(Math.round(markup));
        document.getElementById('markupBudgetNote').textContent = 'of ' + peso(Math.round(budget)) + ' Project Budget';
        document.getElementById('sumContract').textContent = peso(Math.round(budget + markup));
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

        // 1 · Pricing
        var markupEl = document.getElementById('markupInput');
        var pct = markupEl.value.trim();
        if (pct === '') flag(markupEl, 'Markup / Profit');
        else if (isNaN(pct) || +pct < 0 || +pct > 100) flag(markupEl, 'Markup / Profit (0–100)');

        var termsEl = document.getElementById('paymentTermsInput');
        if (!termsEl.value) flag(termsEl, 'Payment Terms');

        // 2 · Materials
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

        // 3 · Labor
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
            document.getElementById('openSendModalFlag').value = '0';
            showQbErrors(problems);
            return;
        }

        document.getElementById('qbErrors').hidden = true;
        qbSubmitting = true;
        var saveBtn = document.getElementById('qbSaveBtn');
        saveBtn.disabled = true;
        saveBtn.lastChild.textContent = ' Saving…';
    });

    // ---- Send to Client is only available once the whole builder is complete ----
    // Same rules as the Save validation, checked silently (no red outlines, blank new rows ignored).
    function builderIsComplete() {
        var val = function (id) { return document.getElementById(id).value.trim(); };

        var pct = val('markupInput');
        if (pct === '' || isNaN(pct) || +pct < 0 || +pct > 100) return false;
        if (!document.getElementById('paymentTermsInput').value) return false;
        var factor = val('materialFactorInput');
        if (factor === '' || isNaN(factor) || +factor < 0 || +factor > 100) return false;
        if (!(toNum(val('estDaysInput')) > 0)) return false;

        var ok = true, materials = 0, seen = [];
        matContainer.querySelectorAll('.material-add-row').forEach(function (row) {
            var isNew = !row.querySelector('[name="material_id[]"]').value;
            var name  = row.querySelector('.row-mat-name-input').value.trim();
            var unit  = row.querySelector('.row-unit').value.trim();
            var qty   = row.querySelector('.row-qty').value.trim();
            var price = row.querySelector('.row-price').value.trim();
            if (isNew && !name && !unit && !qty && !price) return;          // untouched blank row
            if (!name || !unit || !(toNum(qty) > 0) || price === '' || toNum(price) < 0) ok = false;
            if (name) { if (seen.indexOf(name.toLowerCase()) !== -1) ok = false; seen.push(name.toLowerCase()); }
            materials++;
        });
        if (!ok || materials === 0) return false;

        var labor = laborContainer.querySelectorAll('.labor-existing-row:not(.is-archived)').length;
        laborContainer.querySelectorAll('.labor-add-row').forEach(function (row) {
            var sel     = row.querySelector('.row-labor-name-select');
            var custom  = row.querySelector('.row-labor-name-custom');
            var roleSel = row.querySelector('.row-labor-role-select');
            var roleCus = row.querySelector('.row-labor-role-custom');
            var rate    = row.querySelector('.row-labor-rate').value.trim();
            var hasEmployee = (sel.value && sel.value !== 'other') || (sel.value === 'other' && custom.value.trim());
            if (!hasEmployee && !rate) return;                              // untouched blank row
            if (!hasEmployee || rate === '' || toNum(rate) < 0) ok = false;
            if (!roleSel.value || (roleSel.value === 'other' && !roleCus.value.trim())) ok = false;
            labor++;
        });
        return ok && labor > 0;
    }

    function updateSendState() {
        var btn = document.getElementById('openSendQuotationModal');
        if (!btn) return;
        var ready = builderIsComplete();
        btn.disabled = !ready;
        btn.setAttribute('aria-disabled', ready ? 'false' : 'true');
        btn.title = ready ? '' : 'Complete the Quotation Builder first — every required field, at least one material and one labor entry.';
    }

    // ---- Send to Client: quotation file picker (dropzone + thumbnails, like the project page uploads) ----
    (function () {
        var input    = document.getElementById('quotationFilesInput');
        var dropzone = document.getElementById('quotationFilesDropzone');
        var preview  = document.getElementById('quotationFilesPreview');
        var errorBox = document.getElementById('quotationFilesError');
        if (!input) return;

        var MAX_FILES = 5, MAX_BYTES = 10 * 1024 * 1024;
        var files = [];

        function isImage(f) { return /^image\//.test(f.type); }
        function isAllowed(f) { return /\.(pdf|jpe?g|png)$/i.test(f.name); }
        function fmtSize(b) {
            if (b < 1024) return b + ' B';
            if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
            return (b / (1024 * 1024)).toFixed(1) + ' MB';
        }
        function showError(msg) { errorBox.textContent = msg; errorBox.style.display = msg ? 'block' : 'none'; }

        // the native input only holds what we put in it, so write our running list back before submit
        function sync() {
            var dt = new DataTransfer();
            files.forEach(function (f) { dt.items.add(f); });
            input.files = dt.files;
        }

        function render() {
            preview.innerHTML = '';
            if (files.length === 0) {
                preview.style.display = 'none';
                dropzone.style.display = '';
                return;
            }
            dropzone.style.display = 'none';
            preview.style.display = 'grid';

            files.forEach(function (file, i) {
                var tile = document.createElement('div');
                tile.className = 'pv-file-tile';

                if (isImage(file)) {
                    var img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.alt = file.name;
                    tile.appendChild(img);
                } else {
                    var icon = document.createElement('i');
                    icon.setAttribute('data-lucide', 'file-text');
                    icon.className = 'pv-file-icon';
                    tile.appendChild(icon);
                    var name = document.createElement('div');
                    name.className = 'pv-file-name';
                    name.textContent = file.name;
                    tile.appendChild(name);
                    var size = document.createElement('div');
                    size.className = 'pv-file-size';
                    size.textContent = fmtSize(file.size);
                    tile.appendChild(size);
                }

                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'pv-file-remove';
                remove.title = 'Remove';
                remove.innerHTML = '<i data-lucide="x" style="width:12px;height:12px;"></i>';
                remove.onclick = function () { files.splice(i, 1); sync(); showError(''); render(); };
                tile.appendChild(remove);

                preview.appendChild(tile);
            });

            if (files.length < MAX_FILES) {
                var add = document.createElement('div');
                add.className = 'pv-file-add-tile';
                add.innerHTML = '<i data-lucide="plus" style="width:20px;height:20px;"></i><span>Add More</span>';
                add.onclick = function () { input.click(); };
                preview.appendChild(add);
            }
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        input.addEventListener('change', function () {
            var problems = [];
            Array.prototype.forEach.call(input.files, function (f) {
                if (files.length >= MAX_FILES) { problems.push('Only ' + MAX_FILES + ' files are allowed.'); return; }
                if (!isAllowed(f))            { problems.push(f.name + ' is not a PDF, JPG or PNG.'); return; }
                if (f.size > MAX_BYTES)       { problems.push(f.name + ' is larger than 10MB.'); return; }
                files.push(f);
            });
            sync();
            showError(problems.filter(function (p, i, a) { return a.indexOf(p) === i; }).join(' '));
            render();
        });

        // the file input is hidden behind the dropzone, so the browser can't show its own "required" bubble
        var form = input.closest('form');
        form.addEventListener('submit', function (e) {
            if (files.length === 0) {
                e.preventDefault();
                dropzone.style.borderColor = '#dc2626';
                showError('Please upload the quotation file before sending.');
            }
        });
    })();
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
    updateSendState();
    if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
    @include('partials.receipt_viewer')

</body>
</html>
