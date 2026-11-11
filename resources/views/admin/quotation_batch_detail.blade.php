<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $step === 'document' ? 'Quotation Document' : 'Costing' }} — {{ $batch->client->name ?? 'Client' }} | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}?v={{ @filemtime(public_path('css/admin.css')) }}" rel="stylesheet">
</head>
<body class="page-enter pg-admin-quotation-batch-detail">

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
                <span style="color:var(--dark);font-weight:700;">{{ $step === 'document' ? 'Quotation Document' : 'Costing' }}</span>
            </div>

            @php
                $requestMeta  = $tankItems->first();
                $latestSent   = $versions->first();
            @endphp
            <div class="page-header qb-pagehead" style="flex-wrap:wrap;gap:12px;">
                <div class="qb-head-text">
                    <h1>{{ $batch->client->name ?? 'Client' }}</h1>
                    <p>
                        <strong style="color:var(--dark);">{{ $batch->reference_no }}</strong>
                        @if($requestMeta)
                        · <span class="status-badge {{ $requestMeta->status_class }}" style="vertical-align:1px;">{{ $requestMeta->status_label }}</span>
                        @endif
                        · @if($step === 'document')
                            {{ $isLocked ? 'The quotation document sent to the client (view only).' : 'Set what the quotation document says, then send it to the client.' }}
                          @else
                            {{ $isLocked ? 'Materials, labor, and costs of this quotation (view only).' : 'Enter the pricing, materials and labor — then generate the quotation document.' }}
                          @endif
                    </p>
                </div>
                <div class="qb-head-actions">
                    @if($standardSizeOffer)
                    <button class="cancel-btn" type="button" id="openStandardSizeModal" style="display:inline-flex;align-items:center;gap:6px;">
                        <i data-lucide="bookmark-plus"></i>
                        Save as Standard Size
                    </button>
                    @endif
                    @if(in_array($batchStatus, ['pending', 'in_preparation'], true))
                        @if($step === 'costing')
                        {{-- step 1 → 2: only once the costing is complete AND saved (checked live in the page as well) --}}
                        <a class="add-btn" id="generateDocBtn" data-ready="{{ $costingReady ? '1' : '0' }}"
                           href="{{ route('admin.quotation_requests.batch_detail', ['batchId' => $batch->id, 'step' => 'document']) }}"
                           style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;"
                           title="{{ $costingReady ? '' : 'Complete and save the costing first: ' . implode(', ', $costingGaps) }}"
                           @unless($costingReady) aria-disabled="true" @endunless>
                            <i data-lucide="file-text"></i>
                            Generate Quotation Document
                        </a>
                        @else
                        <a class="cancel-btn" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;" href="{{ route('admin.quotation_requests.batch_detail', $batch->id) }}">
                            <i data-lucide="arrow-left"></i>
                            Back to Costing
                        </a>
                        <button class="add-btn" type="button" id="openSendQuotationModal">
                            <i data-lucide="send"></i>
                            {{ $latestSent ? 'Send Revised Quotation' : 'Send to Client' }}
                        </button>
                        @endif
                    @elseif($batchStatus === 'quotation_sent')
                    <form method="POST" action="{{ route('admin.quotation_requests.batch_withdraw', $batch->id) }}" onsubmit="return confirm('Take this quotation back from the client to revise it? They will no longer be able to approve this version.');">
                        @csrf
                        <button type="submit" class="cancel-btn" style="display:inline-flex;align-items:center;gap:6px;">
                            <i data-lucide="rotate-ccw"></i>
                            Withdraw &amp; Revise
                        </button>
                    </form>
                    @elseif($batchStatus === 'approved' && $batch->hasPurchaseOrder())
                    <a class="add-btn" style="text-decoration:none;" href="{{ route('admin.projects', ['prefill_quotation_batch' => $batch->id]) }}">
                        <i data-lucide="folder-plus"></i>
                        Convert to Project
                    </a>
                    @elseif($batchStatus === 'approved')
                    {{-- the project (and its down payment) starts from the client's Purchase Order --}}
                    <span class="add-btn" aria-disabled="true" title="Waiting for the client's Purchase Order"
                          style="opacity:.5;cursor:not-allowed;display:inline-flex;align-items:center;gap:6px;">
                        <i data-lucide="folder-plus"></i>
                        Convert to Project
                    </span>
                    @elseif($batchStatus === 'converted' && $batch->project_id)
                    <a class="add-btn" style="text-decoration:none;" href="{{ route('admin.project_view', $batch->project_id) }}">
                        <i data-lucide="folder-open"></i>
                        Open Project
                    </a>
                    @endif
                    @if($latestSent)
                    <a class="cancel-btn" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;" href="{{ route('admin.quotation_requests.version', [$batch->id, $latestSent->version]) }}">
                        <i data-lucide="file-text"></i>
                        {{ $latestSent->quotation_number }}
                    </a>
                    @endif
                </div>
            </div>

            @if(session('success'))
            {{-- Saved (e.g. quotation sent, request declined, materials saved): the small dark toast below the
                 header (same as "Salary record saved."), shown for 3 seconds --}}
            <div class="toast" id="quotationBatchToast" role="status">
                <i data-lucide="check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
            <script>
                window.addEventListener('load', function () {
                    var t = document.getElementById('quotationBatchToast');
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
            <div class="alert-banner error">
                <i data-lucide="alert-circle"></i>
                {{ session('error') }}
            </div>
            @endif

            @if(in_array($batchStatus, ['pending', 'in_preparation'], true) && optional($tankItems->first())->decline_reason)
            <div class="alert-banner" style="align-items:flex-start;margin-bottom:20px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;">
                <i data-lucide="rotate-ccw"></i>
                <span style="flex:1;min-width:0;overflow-wrap:anywhere;word-break:break-word;"><strong>Client requested a revision{{ $latestSent ? ' of ' . $latestSent->quotation_number : '' }}:</strong> {{ $tankItems->first()->decline_reason }}</span>
            </div>
            @endif

            @if($batchStatus === 'quotation_sent')
            <div class="alert-banner info" style="margin-bottom:20px;">
                <i data-lucide="clock"></i>
                <span>{{ $latestSent?->quotation_number ?? 'The quotation' }} was sent to the client on {{ optional($latestSent?->sent_at ?? $batch->updated_at)->format('M d, Y') }} — Contract Value ₱{{ number_format($batch->contract_value, 2) }}. Waiting for their decision. The builder is locked while they review it; use <strong>Withdraw &amp; Revise</strong> to change it.</span>
            </div>
            @elseif($batchStatus === 'approved' && !$batch->hasPurchaseOrder())
            <div class="alert-banner info" style="margin-bottom:20px;">
                <i data-lucide="file-signature"></i>
                <span>The client approved {{ $latestSent?->quotation_number ?? 'this quotation' }}{{ $latestSent?->responded_at ? ' on ' . $latestSent->responded_at->format('M d, Y') : '' }} — Contract Value ₱{{ number_format($batch->contract_value, 2) }}. <strong>Waiting for their Purchase Order</strong>: once they send it you can convert this into a project, which starts the down payment.</span>
            </div>
            @elseif($batchStatus === 'approved')
            <div class="alert-banner success" style="margin-bottom:20px;">
                <i data-lucide="check-circle"></i>
                <span>The client approved {{ $latestSent?->quotation_number ?? 'this quotation' }} and sent Purchase Order <strong>{{ $batch->po_number }}</strong> on {{ $batch->po_submitted_at->format('M d, Y') }} — Contract Value ₱{{ number_format($batch->contract_value, 2) }}. Click <strong>Convert to Project</strong> above: its tank, specifications, materials, labor, costs and payment terms carry over automatically, and the down payment opens for the client.</span>
            </div>
            @elseif($batchStatus === 'rejected')
            <div class="alert-banner error" style="margin-bottom:20px;">
                <i data-lucide="x-circle"></i>
                <span>The client rejected {{ $latestSent?->quotation_number ?? 'this quotation' }}{{ $tankItems->first()?->decline_reason ? ': ' . $tankItems->first()->decline_reason : '.' }}</span>
            </div>
            @elseif($batchStatus === 'declined')
            <div class="alert-banner error" style="margin-bottom:20px;">
                <i data-lucide="x-circle"></i>
                This request was declined.
            </div>
            @endif

            @if($batch->hasPurchaseOrder() && in_array($batchStatus, ['approved', 'converted'], true))
            {{-- The client's Purchase Order --}}
            <div class="table-card" style="padding:18px 20px;margin-bottom:20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                        <span style="width:40px;height:40px;border-radius:12px;background:#E7F6EC;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="file-signature" style="width:19px;height:19px;color:#16a34a;"></i>
                        </span>
                        <div style="min-width:0;">
                            <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);">Client's Purchase Order</div>
                            <div style="font-size:15px;font-weight:900;color:var(--dark);overflow-wrap:anywhere;">{{ $batch->po_number }}</div>
                            <div style="font-size:12px;color:var(--muted);font-weight:600;">Received {{ $batch->po_submitted_at->format('M d, Y h:i A') }}</div>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;" data-receipt-set>
                        @foreach((array) $batch->po_files as $i => $file)
                        <a href="{{ $file }}" target="_blank" data-receipt class="cancel-btn" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                            <i data-lucide="paperclip"></i>
                            {{ count($batch->po_files) > 1 ? 'PO file ' . ($i + 1) : 'View PO' }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @if($batch->po_notes)
                <div style="margin-top:12px;padding:10px 12px;border-radius:10px;background:var(--cream-soft);border:1px solid var(--border);font-size:13px;color:var(--dark);overflow-wrap:anywhere;">
                    <strong>Client's note:</strong> {{ $batch->po_notes }}
                </div>
                @endif
            </div>
            @endif

            {{-- The request and costing summary are for the Costing step; the document step keeps the form + preview in view --}}
            @if($step === 'costing')
            {{-- The client's design files (the tank type, size and quantity they asked for are in the Costing's Tank section) --}}
            @php
                // Older requests stored the same shared set on every tank — list it once
                $designFiles = $tankItems->flatMap(fn ($t) => (array) ($t->reference_files ?? []))->filter()->unique()->values();
            @endphp
            @if($designFiles->isNotEmpty())
            <div class="rt-files" style="margin-bottom:20px;" data-receipt-set>
                <span class="rt-files-label">Client's design files</span>
                @foreach($designFiles as $i => $file)
                <a href="{{ $file }}" target="_blank" data-receipt class="rt-file" title="Open design file {{ $i + 1 }}">
                    <i data-lucide="paperclip"></i> {{ $designFiles->count() > 1 ? 'Design ' . ($i + 1) : 'View design' }}
                </a>
                @endforeach
            </div>
            @endif
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
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Per tank, before factor</span>
                        <span class="fd-ov-val">{{ $estimatedCost > 0 ? '₱' . number_format($estimatedCost, 2) : '—' }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Labor Entries</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Total entries</span>
                        <span class="fd-ov-val">{{ $totalLaborEntries > 0 ? $totalLaborEntries : '—' }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Estimated Working Days</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Per tank, all labor rows</span>
                        <span class="fd-ov-val">@if(($batch->estimated_working_days ?? 0) > 0){{ number_format($batch->estimated_working_days, 0) }} <small style="font-size:13px;">Days</small>@else—@endif</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Labor Cost</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Per tank</span>
                        <span class="fd-ov-val">{{ $totalLaborCost > 0 ? '₱' . number_format($totalLaborCost, 2) : '—' }}</span>
                    </div>
                    <div class="fd-ov-item fd-ov-highlight">
                        <span class="fd-ov-label">Project Budget</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Per tank × {{ $estimatedBudget['quantity'] }} tank{{ $estimatedBudget['quantity'] === 1 ? '' : 's' }}</span>
                        <span class="fd-ov-val" style="color:{{ $estimatedBudget['total'] > 0 ? '#4ade80' : 'rgba(255,255,255,0.35)' }};font-size:17px;">{{ $estimatedBudget['total'] > 0 ? '₱' . number_format(round($estimatedBudget['total']), 0) : '—' }}</span>
                    </div>
                </div>
            </div>
            @endif

            {{-- Quotation Document step: the form on the left, a live preview of the document on the right --}}
            @if($step === 'document')<div class="qd-layout">@endif

            {{-- Costing + Quotation Document — one form with a single Save; each step shows its own sections --}}
            <form method="POST" action="{{ route('admin.quotation_requests.batch_save', $batch->id) }}" id="quotationForm" class="qb-card{{ $isLocked ? ' qb-locked' : '' }}" data-step="{{ $step }}" autocomplete="off">{{-- no browser-restored values after a reload: the form always opens as saved --}}
                @csrf
                <input type="hidden" name="open_send_modal" id="openSendModalFlag" value="0">
                <input type="hidden" name="step" value="{{ $step }}">

                <div class="qb-head">
                    <div>
                        @if($step === 'document')
                        <h2 class="qb-title"><i data-lucide="file-text"></i> Quotation Document</h2>
                        <p class="qb-sub">{{ $isLocked ? 'What the client received — view only.' : 'Edit the details here — the preview beside it follows as you type. Save, then send it to the client.' }}</p>
                        @else
                        <h2 class="qb-title"><i data-lucide="calculator"></i> Costing</h2>
                        <p class="qb-sub">{{ $isLocked ? ($batchStatus === 'quotation_sent' ? 'The quotation is with the client, so it is view-only until they respond or you withdraw it.' : 'This quotation is final, so it can no longer be edited.') : 'Set the pricing, materials and labor below, then save. Next: Generate Quotation Document.' }}</p>
                        @endif
                    </div>
                    <div class="qb-head-right">
                        @if($isLocked)
                        <span class="qb-viewonly"><i data-lucide="lock"></i> View only</span>
                        @elseif($step === 'document')
                        {{-- the standard costing fills in the costing — not offered on the document step --}}
                        @else
                        {{-- shown once the admin changes anything: discards every unsaved change (tank, pricing, materials, labor) --}}
                        <button type="button" class="qb-chip" id="applyStdCostingBtn" style="cursor:pointer;" hidden
                                title="Discard everything you changed since opening this page">
                            <i data-lucide="undo-2"></i> Undo all changes
                        </button>
                        @endif
                        <span class="qb-dirty" id="qbDirty" hidden><i data-lucide="circle-dot"></i> Unsaved changes</span>
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

                {{-- 1 · Tank — what is being priced; it also decides which templates are offered --}}
                <section class="qb-section">
                    <div class="qb-section-head">
                        <div class="qb-section-title">
                            <span class="qb-step">1</span>
                            <div>
                                <h3>Tank</h3>
                                <p>The tank the client requested — the costing below is for this tank.</p>
                            </div>
                        </div>
                    </div>
                    @php
                        // The fields show what the client requested and are locked to it (still submitted with Save) —
                        // they stay editable only if the request is missing a tank type or capacity.
                        $tankFixed = $builderTank['tank_type'] && $builderTank['capacity'];
                    @endphp
                    <div class="qb-grid qb-grid-tank {{ $tankFixed ? 'is-fixed' : '' }}">
                        <div class="qb-field">
                            <label for="tankTypeInput">Tank Type <span class="qb-req">*</span></label>
                            <select name="tank_type" id="tankTypeInput" class="qb-input" required @if($tankFixed) tabindex="-1" aria-readonly="true" @endif>
                                <option value="" disabled hidden {{ !$builderTank['tank_type'] ? 'selected' : '' }}>Select tank type</option>
                                @foreach(array_keys($tankTypeOptions) as $type)
                                <option value="{{ $type }}" {{ $builderTank['tank_type'] === $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="qb-field">
                            <label for="capacitySelect">Capacity <span class="qb-req">*</span></label>
                            {{-- the capacities built before for the chosen type, or "Other" to type one --}}
                            <select id="capacitySelect" class="qb-input" @if($tankFixed) tabindex="-1" aria-readonly="true" @endif></select>
                            <input type="text" id="capacityCustom" class="qb-input qb-capacity-custom" maxlength="255" placeholder="e.g. 18,000 liters" autocomplete="off" hidden @if($tankFixed) readonly tabindex="-1" @endif>
                            <input type="hidden" name="capacity" id="capacityInput" value="{{ $builderTank['capacity'] }}">
                            <small id="capacityHint"></small>
                        </div>
                        <div class="qb-field">
                            <label for="tankQtyInput">Tank Quantity <span class="qb-req">*</span></label>
                            <input type="number" name="tank_quantity" id="tankQtyInput" class="qb-input" min="1" step="1" inputmode="numeric"
                                   value="{{ $builderTank['quantity'] }}" required @if($tankFixed) readonly tabindex="-1" @endif>
                            <small>Identical tanks</small>
                        </div>
                    </div>
                    <div class="qb-tank-note">
                        <i data-lucide="info"></i>
                        <span>Enter the materials and labor for <strong>one tank</strong>. The quotation total is that unit cost × the tank quantity — prices per unit are never multiplied.</span>
                    </div>
                </section>

                {{-- 2 · Pricing --}}
                <section class="qb-section">
                    <div class="qb-section-head">
                        <div class="qb-section-title">
                            <span class="qb-step">2</span>
                            <div>
                                <h3>Pricing</h3>
                                <p>Markup, contingency and payment terms — not shown to the client.</p>
                            </div>
                        </div>
                    </div>
                    <div class="qb-grid qb-grid-3">
                        <div class="qb-field">
                            <label for="markupInput">Markup / Profit (%) <span class="qb-req">*</span></label>
                            <input type="number" name="markup_percent" id="markupInput" class="qb-input"
                                   min="{{ \App\Models\QuotationBatch::MARKUP_MIN }}" max="{{ \App\Models\QuotationBatch::MARKUP_MAX }}" step="0.01"
                                   value="{{ $batch->markup_percent ?? ($costingIsNew ? \App\Models\QuotationBatch::DEFAULT_MARKUP : '') }}" placeholder="{{ \App\Models\QuotationBatch::MARKUP_MIN }}–{{ \App\Models\QuotationBatch::MARKUP_MAX }}" required>
                            <small class="qb-value-note">Between {{ \App\Models\QuotationBatch::MARKUP_MIN }}% and {{ \App\Models\QuotationBatch::MARKUP_MAX }}% of the Project Budget · <strong id="markupValueNote">₱0</strong> <span id="markupBudgetNote">of ₱0 Project Budget</span></small>
                        </div>
                        <div class="qb-field">
                            <label for="contingencyInput">Contingency (%) <span class="qb-optional">(optional)</span></label>
                            <input type="number" name="contingency_percent" id="contingencyInput" class="qb-input"
                                   min="0" max="{{ \App\Models\QuotationBatch::CONTINGENCY_MAX }}" step="0.01"
                                   value="{{ $batch->contingency_percent ?? ($costingIsNew ? \App\Models\QuotationBatch::DEFAULT_CONTINGENCY : '') }}" placeholder="0">
                            <small class="qb-value-note">For the unforeseen — % of materials + labor + other costs · <strong id="contingencyValueNote">₱0</strong></small>
                        </div>
                        <div class="qb-field">
                            <label for="paymentTermsInput">Payment Terms <span class="qb-req">*</span></label>
                            {{-- not chosen yet: follows the contract value (Big at ₱{{ number_format(\App\Models\QuotationBatch::BIG_PROJECT_THRESHOLD) }}+) until the admin picks one --}}
                            <select name="payment_term_type" id="paymentTermsInput" class="qb-input" required data-auto="{{ $batch->payment_term_type ? '0' : '1' }}">
                                <option value="" disabled hidden {{ !$batch->payment_term_type ? 'selected' : '' }}>Select payment terms</option>
                                <option value="big_project" {{ $batch->payment_term_type === 'big_project' ? 'selected' : '' }}>Big Project — 3 Phases (50% / 30% / 20%)</option>
                                <option value="small_project" {{ $batch->payment_term_type === 'small_project' ? 'selected' : '' }}>Small Project — 2 Phases (50% / 50%)</option>
                            </select>
                            <small class="qb-value-note" id="paymentAutoNote">Big Project at ₱{{ number_format(\App\Models\QuotationBatch::BIG_PROJECT_THRESHOLD) }} and up, Small below — <span id="paymentAutoState">{{ $batch->payment_term_type ? 'chosen' : 'set from the contract value' }}</span>.</small>
                        </div>
                    </div>
                </section>

                {{-- 3 · Materials --}}
                <section class="qb-section">
                    <div class="qb-section-head">
                        <div class="qb-section-title">
                            <span class="qb-step">3</span>
                            <div>
                                <h3>Materials <span class="qb-per-tank">per tank</span></h3>
                                <p>Everything one tank needs, priced per unit.</p>
                            </div>
                        </div>
                        <div class="qb-section-tools">
                            <label class="qb-chip" for="materialFactorInput" title="Waste and handling allowance added on top of the material cost (standard: {{ \App\Models\ProjectMaterial::DEFAULT_FACTOR }}%)">
                                Material Factor <span class="qb-req">*</span>
                                <input type="number" name="factor" id="materialFactorInput" min="0" max="100" step="0.1" value="{{ $materialFactor }}" placeholder="{{ \App\Models\ProjectMaterial::DEFAULT_FACTOR }}" required>
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
                                    <td colspan="5" class="qb-total-label">Grand Total (no Material Factor)<span class="qb-qty-tag" data-qty-tag></span></td>
                                    <td class="qb-total-value" id="matSubtotal">₱0</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="qb-total-label">Grand Total (with <span id="matFactorLabel">{{ number_format($materialFactor, 1) }}</span>% Material Factor)<span class="qb-qty-tag" data-qty-tag></span></td>
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

                {{-- 4 · Labor --}}
                <section class="qb-section">
                    <div class="qb-section-head">
                        <div class="qb-section-title">
                            <span class="qb-step">4</span>
                            <div>
                                <h3>Labor <span class="qb-per-tank">per tank</span></h3>
                                <p>Crew and daily rates — the working days (for one tank) apply to every row.</p>
                            </div>
                        </div>
                        <div class="qb-section-tools">
                            <label class="qb-chip" for="estDaysInput">
                                Estimated Working Days <span class="qb-req">*</span>
                                <input type="number" name="estimated_working_days" id="estDaysInput" min="0" step="0.01" value="{{ $batch->estimated_working_days ?? '' }}" placeholder="0" required>
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
                                    <td colspan="4" class="qb-total-label">Grand Total<span class="qb-qty-tag" data-qty-tag></span></td>
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

                {{-- 5 · Other costs — only when GMD assembles the tank on site; for the whole job, not per tank --}}
                @php
                    $otherRows = $batch->other_costs ?: array_map(fn ($l) => ['label' => $l, 'amount' => null], \App\Models\QuotationBatch::DEFAULT_OTHER_COSTS);
                    // the client asked for on-site assembly: the switch starts on until the costing is first saved
                    $onsiteRequest = $tankItems->first(fn ($i) => $i->isOnsite());
                    $onsiteOn      = $batch->onsite_assembly || ($onsiteRequest && $batch->markup_percent === null);
                @endphp
                <section class="qb-section">
                    <div class="qb-section-head">
                        <div class="qb-section-title">
                            <span class="qb-step">5</span>
                            <div>
                                <h3>Other Costs</h3>
                                <p>Mobilization, lodging, PPE and the like when GMD assembles the tank on site — not multiplied by the tank quantity.</p>
                            </div>
                        </div>
                    </div>
                    <label class="qd-toggle">
                        <input type="hidden" name="onsite_assembly" value="0">
                        <input type="checkbox" name="onsite_assembly" value="1" id="onsiteToggle" {{ $onsiteOn ? 'checked' : '' }}>
                        <span class="qd-switch" aria-hidden="true"></span>
                        <span class="qd-toggle-text">
                            <strong>Assembled on site by GMD</strong>
                            <small>Adds the job's other costs to the Project Budget. Off: the tank is built at the shop and picked up.</small>
                        </span>
                    </label>
                    @if($onsiteRequest)
                    <div class="qb-tank-note" style="margin-top:10px;">
                        <i data-lucide="hard-hat"></i>
                        <span>The client asked for <strong>on-site assembly</strong> at: {{ $onsiteRequest->location ?: '—' }}</span>
                    </div>
                    @endif
                    <div class="qb-others-body" id="othersBody" {{ $onsiteOn ? '' : 'hidden' }}>
                        <div class="qb-table-wrap">
                            <table class="qb-table" id="othersTable">
                                <thead>
                                    <tr><th>Item</th><th style="width:180px;" class="qb-num">Amount (₱)</th><th style="width:48px;"></th></tr>
                                </thead>
                                <tbody id="othersRows">
                                    @foreach($otherRows as $row)
                                    <tr>
                                        <td><input type="text" name="other_label[]" class="qb-cell-input" maxlength="120" value="{{ $row['label'] }}" aria-label="Item"></td>
                                        <td><input type="text" name="other_amount[]" class="qb-cell-input row-other-amount" inputmode="decimal" style="text-align:right;"
                                                   value="{{ isset($row['amount']) && $row['amount'] !== null ? number_format((float) $row['amount'], 2) : '' }}" placeholder="0.00" aria-label="Amount"></td>
                                        <td><button type="button" class="qb-icon-btn other-remove" title="Remove item" aria-label="Remove item"><i data-lucide="trash-2"></i></button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td class="qb-total-label">Total other costs</td>
                                        <td class="qb-total-value" id="othersTotal">₱0</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </section>

                {{-- Step 2 · Quotation Document — what the generated quotation prints, besides the costing figures --}}
                <section class="qb-section qb-doc-section">
                    {{-- Project --}}
                    <div class="qd-group">
                        <div class="qd-group-head"><span class="qd-group-icon"><i data-lucide="briefcase"></i></span><div><h4>Project</h4><p>How the job is named on the quotation.</p></div></div>
                        <div class="qd-fields">
                            <div class="qb-field qd-span-2">
                                <label for="docTitle">Project Title</label>
                                <input type="text" name="doc[project_title]" id="docTitle" class="qb-input" maxlength="255" value="{{ $docSettings['project_title'] }}">
                            </div>
                            <div class="qb-field qd-span-2">
                                <label for="docLocation">Project Location</label>
                                <input type="text" name="doc[project_location]" id="docLocation" class="qb-input" maxlength="500" value="{{ $docSettings['project_location'] }}">
                            </div>
                            <div class="qb-field">
                                <label for="docRfq">RFQ Ref No. <span class="qb-optional">(optional)</span></label>
                                <input type="text" name="doc[rfq_ref]" id="docRfq" class="qb-input" maxlength="100" value="{{ $docSettings['rfq_ref'] }}" placeholder="Client's own reference">
                            </div>
                            <div class="qb-field">
                                <label for="docValidity">Valid For</label>
                                <div class="qd-suffix-wrap">
                                    <input type="number" name="doc[validity_days]" id="docValidity" class="qb-input" min="1" max="365" value="{{ $docSettings['validity_days'] }}">
                                    <span class="qd-suffix">days</span>
                                </div>
                                <small>Counted from the day it is sent.</small>
                            </div>
                        </div>
                    </div>

                    {{-- Specifications --}}
                    <div class="qd-group">
                        <div class="qd-group-head">
                            <span class="qd-group-icon"><i data-lucide="list-checks"></i></span>
                            <div><h4>Specification Lines <span class="qd-count" id="specCount">{{ count($specLines) }}</span></h4><p>Printed under the tank in the quotation, in this order.</p></div>
                        </div>
                        <div class="qb-table-wrap qd-spec-wrap">
                            <table class="qb-table">
                                <thead><tr><th style="width:36%;">Item</th><th>Specification</th><th style="width:44px;"></th></tr></thead>
                                <tbody id="specRows">
                                    @foreach($specLines as $line)
                                    <tr>
                                        <td><input type="text" name="spec_label[]" class="qb-cell-input" maxlength="120" value="{{ $line['label'] }}" aria-label="Specification item"></td>
                                        <td><textarea name="spec_value[]" class="qb-cell-input qd-spec-value" rows="3" maxlength="1000" aria-label="Specification">{{ $line['value'] }}</textarea></td>
                                        <td><button type="button" class="qb-icon-btn spec-remove" title="Remove line" aria-label="Remove specification line"><i data-lucide="trash-2"></i></button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @unless($isLocked)
                        <button type="button" class="qd-add-line" id="addSpecRow"><i data-lucide="plus"></i> Add specification line</button>
                        @endunless
                    </div>

                    {{-- Terms & notes --}}
                    <div class="qd-group">
                        <div class="qd-group-head"><span class="qd-group-icon"><i data-lucide="scroll-text"></i></span><div><h4>Terms &amp; Notes</h4><p>Listed under "General notes", after the validity date.</p></div></div>
                        <div class="qd-fields">
                            <div class="qb-field qd-span-2">
                                <label for="docWarranty">Warranty</label>
                                <textarea name="doc[warranty]" id="docWarranty" class="qb-input" rows="2" maxlength="1000">{{ $docSettings['warranty'] }}</textarea>
                            </div>
                            <div class="qb-field qd-span-2">
                                <label for="docNotes">General Notes <span class="qb-optional">(one per line)</span></label>
                                <textarea name="doc[notes]" id="docNotes" class="qb-input" rows="4" maxlength="4000">{{ $docSettings['notes'] }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Signatory --}}
                    <div class="qd-group">
                        <div class="qd-group-head"><span class="qd-group-icon"><i data-lucide="pen-line"></i></span><div><h4>Signatory</h4><p>Who signs the quotation for GMD.</p></div></div>
                        <div class="qd-fields">
                            <div class="qb-field">
                                <label for="docSignName">Signed By</label>
                                <input type="text" name="doc[signatory_name]" id="docSignName" class="qb-input" maxlength="120" value="{{ $docSettings['signatory_name'] }}">
                            </div>
                            <div class="qb-field">
                                <label for="docSignTitle">Position</label>
                                <input type="text" name="doc[signatory_title]" id="docSignTitle" class="qb-input" maxlength="120" value="{{ $docSettings['signatory_title'] }}">
                            </div>
                            {{-- E-signature (required): uploaded the moment it's picked; its URL rides in doc[signatory_signature]
                                 and is saved with the quotation. Defaults to the latest one saved on any quotation. --}}
                            <div class="qb-field qd-span-2">
                                <label>E-Signature <span style="color:#dc2626;">*</span></label>
                                <div class="qd-sig" id="docSigBox">
                                    <input type="hidden" name="doc[signatory_signature]" id="docSignSignature" value="{{ $docSettings['signatory_signature'] }}">
                                    <input type="file" id="docSigFile" accept="image/png,image/jpeg,image/webp" hidden>
                                    <div class="qd-sig-preview" @if(!$docSettings['signatory_signature']) hidden @endif>
                                        <img id="docSigImg" src="{{ $docSettings['signatory_signature'] }}" alt="Signature">
                                        @unless($isLocked)
                                        <div class="qd-sig-tools">
                                            <label for="docSigFile" class="qd-sig-tool" title="Replace signature"><i data-lucide="refresh-cw"></i></label>
                                            <button type="button" class="qd-sig-tool" id="docSigRemove" title="Remove signature"><i data-lucide="x"></i></button>
                                        </div>
                                        @endunless
                                    </div>
                                    <label for="docSigFile" class="qd-sig-drop" @if($docSettings['signatory_signature']) hidden @endif>
                                        <i data-lucide="pen-line"></i>
                                        <span>Upload signature</span>
                                        <small>PNG with a transparent background works best · max 2 MB</small>
                                    </label>
                                    <div class="qd-sig-busy" hidden><span class="qd-sig-spin"></span> Uploading…</div>
                                </div>
                                <small id="docSigErr" class="qd-sig-err" hidden></small>
                            </div>
                        </div>
                    </div>

                    <div class="qb-tank-note" style="margin-top:4px;">
                        <i data-lucide="info"></i>
                        <span>The validity date, payment terms, totals and quotation number are filled in automatically when you send it. The client sees exactly what the preview shows.</span>
                    </div>
                </section>


                {{-- Summary + the one Save — the last row of the card --}}
                <div class="qb-foot">
                    <div class="qb-summary">
                        <div class="qb-sum-item">
                            <span class="qb-sum-label">Unit Price</span>
                            <strong id="sumUnitPrice">₱0</strong>
                            <small>per tank, incl. markup</small>
                        </div>
                        <div class="qb-sum-op">×</div>
                        <div class="qb-sum-item">
                            <span class="qb-sum-label">Quantity</span>
                            <strong id="sumQty">1</strong>
                            <small id="sumQtyLabel">tank</small>
                        </div>
                        <div class="qb-sum-op">=</div>
                        <div class="qb-sum-item qb-sum-total">
                            <span class="qb-sum-label">Contract Value</span>
                            <strong id="sumContract">₱0</strong>
                            <small>Budget <span id="sumBudget">₱0</span> + Markup <span id="sumMarkup">₱0</span> (<span id="sumMarkupPct">0%</span>)</small>
                        </div>
                    </div>
                    {{-- Edit first: a saved quotation opens read-only; Edit unlocks it, then Cancel or Save --}}
                    @unless($isLocked)
                    <div class="qb-edit-actions">
                        <span class="qb-view-hint"><i data-lucide="lock"></i> Read-only — press Edit to make changes</span>
                        <button type="button" class="save-btn" id="qbEditBtn"><i data-lucide="pencil"></i> Edit</button>
                        <button type="button" class="cancel-btn" id="qbCancelEditBtn"><i data-lucide="x"></i> Cancel</button>
                        <button type="submit" class="save-btn" id="qbSaveBtn">
                            <i data-lucide="save"></i>
                            Save
                        </button>
                    </div>
                    @endunless
                </div>
            </form>

            @if($step === 'document')
              {{-- right column: the preview, then the versions + activity under it (fills the space beside the longer form) --}}
              <div class="qd-side">
                <aside class="qd-preview" aria-label="Live preview of the quotation document">
                    <div class="qd-preview-head">
                        <span class="qd-preview-icon"><i data-lucide="file-text"></i></span>
                        <div style="min-width:0;">
                            <div class="qd-preview-title">Live Preview <span>· {{ $latestSent ? 'next version of ' . $latestSent->quotation_number : $batch->reference_no }}</span></div>
                            <div class="qd-preview-sub" id="qdPreviewStatus"><span class="qd-dot"></span> <span class="qd-status-text">Loading the document…</span></div>
                        </div>
                        <a href="{{ route('admin.quotation_requests.batch_preview', $batch->id) }}" target="_blank" rel="noopener" class="qd-preview-open" title="Open the saved version full size">
                            <i data-lucide="maximize-2"></i>
                        </a>
                    </div>
                    <div class="qd-preview-frame-wrap">
                        <iframe id="qdPreviewFrame" title="Quotation document preview" sandbox="allow-same-origin"></iframe>
                        <div class="qd-preview-loading" id="qdPreviewLoading"><span></span></div>
                    </div>
                </aside>
                @include('partials.admin.quotation_history')
              </div>{{-- /.qd-side --}}
            </div>{{-- /.qd-layout --}}
            @else
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:20px;margin-top:24px;">
                @include('partials.admin.quotation_history')
            </div>
            @endif


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

    @unless($isLocked)
    {{-- ===================== SAVE CONFIRMATION MODAL ===================== --}}
    {{-- Cancel while editing with changes made: discard them (reload as saved) or keep editing --}}
    <div class="modal-overlay" id="discardChangesModal">
        <div class="modal-card" style="max-width:440px;">
            <div class="modal-header">
                <div>
                    <h2>Discard Changes?</h2>
                    <p>You've edited this quotation but haven't saved it.</p>
                </div>
                <button class="modal-close" type="button" id="closeDiscardChanges">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="delete-confirm-body">
                <div class="delete-confirm-icon"><i data-lucide="undo-2"></i></div>
                <p>Discard the changes you made? The quotation goes back to how it was last saved.</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="cancel-btn" id="keepEditingBtn">Keep Editing</button>
                <button type="button" class="save-btn" id="confirmDiscardBtn" style="background:#dc2626;">
                    <i data-lucide="trash-2"></i> Discard Changes
                </button>
            </div>
        </div>
    </div>

    @endunless

    @if($standardSizeOffer)
    {{-- ===================== SAVE AS STANDARD SIZE MODAL ===================== --}}
    <div class="modal-overlay" id="standardSizeModal">
        <div class="modal-card" style="max-width:500px;width:95%;">
            <div class="modal-header">
                <div>
                    <h2>Save as Standard Size</h2>
                    <p>Adds this size to <strong>{{ $standardSizeOffer['type'] }}</strong>. Clients will see it on the tank's page, and new quotations for it start from this costing.</p>
                </div>
                <button class="modal-close" type="button" data-close-standard-size aria-label="Close">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.quotation_requests.batch_standard_size', $batch->id) }}">
                @csrf
                <div class="form-group" style="margin-bottom:14px;">
                    <label for="standardSizeCapacity">Capacity</label>
                    <input type="text" name="capacity" id="standardSizeCapacity" required maxlength="60" value="{{ $standardSizeOffer['capacity'] }}">
                </div>
                <div class="form-group" style="margin-bottom:14px;">
                    <label for="standardSizeDimensions">Dimensions <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                    <input type="text" name="dimensions" id="standardSizeDimensions" maxlength="80" value="{{ $standardSizeOffer['dimensions'] }}" placeholder="e.g. 2.4 m Ø × 2.4 m L">
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 14px;margin-bottom:12px;border-radius:12px;background:var(--cream-soft);border:1px solid var(--border);">
                    <span style="font-size:12.5px;font-weight:700;color:var(--muted);">Estimated price per tank</span>
                    <strong style="font-size:15px;color:var(--dark);">₱{{ number_format($standardSizeOffer['price'], 2) }}</strong>
                </div>
                <p style="margin:0;font-size:12px;line-height:1.5;color:var(--muted);">Uses the last saved costing for one tank. On-site assembly costs stay with this job only. Materials, labor and pricing stay internal.</p>
                <p id="standardSizeDirty" hidden style="margin:8px 0 0;font-size:12px;font-weight:700;color:#B45309;">You have unsaved changes. Save the costing first to include them.</p>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" data-close-standard-size>Cancel</button>
                    <button type="submit" class="save-btn"><i data-lucide="check"></i> Save Standard Size</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ===================== SEND TO CLIENT MODAL ===================== --}}
    <div class="modal-overlay" id="sendQuotationModal">
        <div class="modal-card" style="max-width:560px;width:95%;">
            <div class="modal-header">
                <div>
                    <h2>Send Quotation to Client</h2>
                    <p>The system generates quotation <strong>{{ $batch->quotationNumberFor($batch->current_version + 1) }}</strong> from the saved builder and puts it in the client's portal.</p>
                </div>
                <button class="modal-close" type="button" id="closeSendQuotationModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.quotation_requests.batch_send_quotation', $batch->id) }}" enctype="multipart/form-data">
                @csrf

                @php
                    $sendQty      = $estimatedBudget['quantity'];
                    $sendContract = $estimatedBudget['total'] + (float) ($batch->markup ?? 0);
                @endphp
                <div style="border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--border);">
                        <div>
                            <span style="display:block;font-size:13px;color:var(--muted);">Tank</span>
                            <span style="display:block;margin-top:3px;font-size:12px;color:var(--muted);">{{ $batch->tank_type ?: '—' }}{{ $batch->capacity ? ' · ' . $batch->capacity : '' }}</span>
                        </div>
                        <strong style="font-size:13px;">× {{ $sendQty }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--border);">
                        <div>
                            <span style="display:block;font-size:13px;color:var(--muted);">Project Budget</span>
                            <span style="display:block;margin-top:3px;font-size:12px;color:var(--muted);">(Est. Materials ₱{{ number_format($estimatedBudget['unit_materials'], 0) }} + Est. Labor ₱{{ number_format($estimatedBudget['unit_labor'], 0) }}) × {{ $sendQty }}</span>
                        </div>
                        <strong style="font-size:13px;">₱{{ number_format($estimatedBudget['total'], 0) }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;">
                        <span style="font-size:13px;color:var(--muted);">Markup / Profit</span>
                        <strong style="font-size:13px;">₱{{ number_format($batch->markup ?? 0, 0) }}</strong>
                    </div>
                </div>

                <div style="background:var(--dark);border-radius:10px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;">
                    <span style="font-size:12px;font-weight:700;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.05em;">Contract Value
                        <span style="display:block;margin-top:3px;font-size:11.5px;font-weight:600;text-transform:none;letter-spacing:0;color:rgba(255,255,255,.45);">₱{{ number_format($sendContract / max(1, $sendQty), 0) }} per tank × {{ $sendQty }}</span>
                    </span>
                    <span style="font-size:20px;font-weight:900;color:#fff;">₱{{ number_format($sendContract, 0) }}</span>
                </div>

                <a href="{{ route('admin.quotation_requests.batch_preview', $batch->id) }}" target="_blank" rel="noopener"
                   style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:1px solid var(--border);border-radius:12px;margin-bottom:16px;text-decoration:none;color:var(--dark);font-weight:800;font-size:13px;">
                    <i data-lucide="file-search" style="width:18px;height:18px;"></i>
                    Preview the generated quotation
                    <i data-lucide="external-link" style="width:14px;height:14px;margin-left:auto;color:var(--muted);"></i>
                </a>

                <div class="form-group" style="margin-bottom:20px;">
                    <label class="log-label">SUPPORTING FILES <span style="font-weight:500;text-transform:none;letter-spacing:0;color:var(--muted);">(optional — e.g. a shop drawing)</span></label>
                    <label class="pv-upload-dropzone" id="quotationFilesDropzone">
                        <i data-lucide="paperclip" style="width:24px;height:24px;color:var(--accent);"></i>
                        <span style="font-size:13px;font-weight:700;color:var(--text-primary);">Attach files to send along with the quotation</span>
                        <span style="font-size:12px;color:var(--muted);">PDF or image (JPG / PNG) — up to 5 files, max 10MB each</span>
                        <input type="file" name="quotation_files[]" id="quotationFilesInput" multiple accept=".pdf,.jpg,.jpeg,.png,image/jpeg,image/png"
                               style="display:none;">
                    </label>
                    <div id="quotationFilesPreview" class="pv-file-grid"></div>
                    <div id="quotationFilesError" role="alert" style="display:none;margin-top:8px;font-size:12.5px;font-weight:700;color:#b91c1c;"></div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelSendQuotation">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="send"></i>
                        Generate &amp; Send
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
    var MARKUP_MIN      = {{ \App\Models\QuotationBatch::MARKUP_MIN }};
    var MARKUP_MAX      = {{ \App\Models\QuotationBatch::MARKUP_MAX }};

    var quotationForm = document.getElementById('quotationForm');

    function openModal(id) { var m = document.getElementById(id); if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; } }
    function closeModal(id) { var m = document.getElementById(id); if (m) { m.classList.remove('show'); document.body.style.overflow = ''; } }

    // ---- Send to Client ----
    var openSendQBtn = document.getElementById('openSendQuotationModal');
    if (openSendQBtn) openSendQBtn.addEventListener('click', function () {
        // Save everything on the page first (through the normal validated submit), then
        // reload and auto-open the modal so it always shows what is actually persisted.
        qbSetViewing(false);   // read-only fields are disabled, and disabled fields aren't submitted
        document.getElementById('openSendModalFlag').value = '1';
        quotationForm.requestSubmit();
    });
    var closeSendQBtn = document.getElementById('closeSendQuotationModal');
    if (closeSendQBtn) closeSendQBtn.addEventListener('click', function () { closeModal('sendQuotationModal'); });
    var cancelSendQBtn = document.getElementById('cancelSendQuotation');
    if (cancelSendQBtn) cancelSendQBtn.addEventListener('click', function () { closeModal('sendQuotationModal'); });

    @if(request('open_send') && !$isLocked)
    openModal('sendQuotationModal');
    @endif

    // ---- Save as Standard Size ----
    var openStandardSizeBtn = document.getElementById('openStandardSizeModal');
    if (openStandardSizeBtn) openStandardSizeBtn.addEventListener('click', function () {
        document.getElementById('standardSizeDirty').hidden = !(typeof qbDirty !== 'undefined' && qbDirty);
        openModal('standardSizeModal');
    });
    document.querySelectorAll('[data-close-standard-size]').forEach(function (btn) {
        btn.addEventListener('click', function () { closeModal('standardSizeModal'); });
    });

    // ---- helpers ----
    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
    // Amounts may be typed or shown with thousands separators ("15,000.00") — ignore them when reading
    function toNum(v) { var n = parseFloat(String(v == null ? '' : v).replace(/,/g, '')); return isNaN(n) ? 0 : n; }
    function peso(n, decimals) {
        decimals = decimals || 0;
        return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    }

    // ---- Price / Unit and Daily Rate: shown as 15,000.00, typed as a plain number ----
    // The field holds the formatted text while it isn't being edited; focusing it shows the bare number
    // for editing, and the separators are taken out again right before the form is submitted.
    function fmtMoney(v) {
        var raw = String(v == null ? '' : v).replace(/,/g, '').trim();
        if (raw === '' || isNaN(raw)) return raw;
        return Number(raw).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function unfmtMoney(v) { return String(v == null ? '' : v).replace(/,/g, '').trim(); }
    var MONEY_FIELDS = '.row-price, .row-labor-rate';
    quotationForm.addEventListener('focusin', function (e) {
        if (e.target.matches && e.target.matches(MONEY_FIELDS) && !e.target.readOnly) e.target.value = unfmtMoney(e.target.value);
    });
    quotationForm.addEventListener('focusout', function (e) {
        if (e.target.matches && e.target.matches(MONEY_FIELDS)) e.target.value = fmtMoney(e.target.value);
    });
    // only digits and one decimal point can be typed into a money field
    quotationForm.addEventListener('input', function (e) {
        if (!e.target.matches || !e.target.matches(MONEY_FIELDS) || e.target.readOnly) return;
        var cleaned = e.target.value.replace(/[^\d.]/g, '').replace(/(\..*)\./g, '$1');
        if (cleaned !== e.target.value) e.target.value = cleaned;
    }, true);
    function unformatMoneyFields() {
        quotationForm.querySelectorAll(MONEY_FIELDS).forEach(function (el) { el.value = unfmtMoney(el.value); });
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
        // the "Unsaved changes" pill follows whether the form differs from how it opened (see syncStdCostingButton)
        if (typeof syncStdCostingButton === 'function') {
            syncStdCostingButton();
        } else {
            var pill = document.getElementById('qbDirty');
            if (pill) pill.hidden = false;
        }
        updateSendState();
    }
    quotationForm.addEventListener('input', markDirty);
    quotationForm.addEventListener('change', markDirty);
    window.addEventListener('beforeunload', function (e) {
        // warn only when the admin actually changed something (not for a costing the page filled in by itself)
        var changed = typeof hasUnsavedChanges === 'function' ? hasUnsavedChanges() : qbDirty;
        if (changed && !qbSubmitting) { e.preventDefault(); e.returnValue = ''; }
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
            '<td><input type="text" name="unit[]" class="qb-cell-input row-unit" autocomplete="off" placeholder="pcs" value="' + escapeHtml(data.unit || '') + '" oninput="recalc()"></td>' +
            // Saved: the quantity for ONE tank (hidden). The box shows it for the whole order (× the tank quantity);
            // typing there sets the per-tank amount, rounded up to whole units per tank when leaving the box.
            '<td><input type="hidden" name="quantity[]" class="row-qty" value="' + (data.qty != null && data.qty !== '' ? +data.qty : '') + '">' +
                '<input type="number" class="qb-cell-input row-qty-view" min="1" step="1" inputmode="numeric" placeholder="0" value="' + (data.qty != null && data.qty !== '' ? +data.qty : '') + '" oninput="qtyViewInput(this)" onblur="qtyViewBlur(this)"></td>' +
            '<td><input type="text" inputmode="decimal" name="price_per_unit[]" class="qb-cell-input row-price qb-num-input" autocomplete="off" placeholder="0.00" value="' + (data.price != null && data.price !== '' ? fmtMoney(data.price) : '') + '" oninput="recalc()"></td>' +
            '<td><input type="text" class="qb-cell-total row-total-display" readonly tabindex="-1" placeholder="—"></td>' +
            '<td style="text-align:center;">' +
                '<button type="button" class="qb-icon-btn" onclick="removeMaterialRow(this)" title="' + (data.id ? 'Delete material' : 'Remove row') + '">' +
                    '<i data-lucide="trash-2"></i>' +
                '</button>' +
            '</td>';
        return tr;
    }

    // The Quantity box is for the whole order; the saved (hidden) quantity is per tank = box ÷ tanks.
    function qtyViewInput(view) {
        var perTank = view.closest('td').querySelector('.row-qty');
        perTank.value = view.value.trim() === '' ? '' : toNum(view.value) / tankQuantity();
        recalc();
    }
    // Leaving the box: whole units for each tank — 15 for 2 tanks becomes 16 (8 per tank); 2.5 becomes 3
    function qtyViewBlur(view) {
        if (view.value.trim() === '') return;
        var tanks   = tankQuantity();
        var perTank = view.closest('td').querySelector('.row-qty');
        var per     = toNum(view.value) > 0 ? Math.ceil(toNum(view.value) / tanks - 1e-9) : 0;
        perTank.value = per;
        view.value    = per * tanks;
        recalc();
    }
    // Every Quantity box shows the per-tank amount × the tank quantity (the one being typed in is left alone)
    function syncQtyViews(tanks) {
        matContainer.querySelectorAll('.material-add-row').forEach(function (row) {
            var view = row.querySelector('.row-qty-view');
            var per  = row.querySelector('.row-qty').value;
            if (!view || document.activeElement === view) return;
            view.value = per === '' ? '' : toNum(per) * tanks;
            view.title = tanks > 1 && per !== '' ? toNum(per) + ' per tank × ' + tanks + ' tanks' : '';
        });
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
        appendMaterialRows(items)[0].querySelector('.row-qty-view').focus();
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
            '<td><input type="text" inputmode="decimal" name="daily_rate[]" class="qb-cell-input row-labor-rate qb-num-input" autocomplete="off" readonly placeholder="0.00" oninput="recalc()"></td>' +
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
        rateInput.value = fmtMoney(rate);
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

    @unless($isLocked)
    // ---- Standard costing: every standard tank size has a default costing (materials, labor, pricing) worked out
    //      from GMD's estimated price. A blank costing for a standard size starts from it; changing the tank type or
    //      capacity swaps it for the new size's (only what it filled in — rows the admin added stay). ----
    var STANDARD_COSTINGS = @json($standardCostings);

    function standardCostingForTank() {
        var type = document.getElementById('tankTypeInput').value;
        var cap  = document.getElementById('capacityInput').value;
        if (!type || !cap) return null;
        var n = capacityNumber(cap);
        for (var key in STANDARD_COSTINGS) {
            var c = STANDARD_COSTINGS[key];
            if (c.tank_type === type && capacityNumber(c.capacity) === n) return c;
        }
        return null;
    }

    function setFieldValue(id, value, allowBlank) {
        var el = document.getElementById(id);
        if (!el || value === null || value === undefined || (value === '' && !allowBlank)) return;
        el.value = value;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // What the standard costing filled in, so a different size (or clearing) can take it out again
    var appliedQuoteTpl   = null;
    var PRICING_FIELD_IDS = ['markupInput', 'contingencyInput', 'materialFactorInput', 'estDaysInput'];
    var pricingBeforeTpl  = null;   // pricing values as they were before the first standard costing was applied
    var dirtyBeforeTpl    = false;

    // Removes every row the standard costing added and puts the pricing fields back as they were.
    // Saved rows, and rows the admin added by hand, are left alone.
    function clearAppliedQuoteTemplate() {
        if (!appliedQuoteTpl) return;
        document.querySelectorAll('#materialRowsContainer [data-from-template], #laborRowsContainer [data-from-template]')
            .forEach(function (row) { row.remove(); });
        PRICING_FIELD_IDS.forEach(function (id) { setFieldValue(id, pricingBeforeTpl[id], true); });
        appliedQuoteTpl = null;

        renumberRows();
        updateEmptyStates();
        refreshAllLaborEmployeeOptions();
        recalc();

        // back to exactly how the page loaded → no "Unsaved changes" warning
        var otherNewRows = document.querySelector('#materialRowsContainer .qb-row-new, #laborRowsContainer .qb-row-new');
        if (!dirtyBeforeTpl && !otherNewRows) {
            qbDirty = false;
            var pill = document.getElementById('qbDirty');
            if (pill) pill.hidden = true;
            updateSendState();
        }
    }

    // Copies the standard costing in as new, unsaved rows — nothing is stored until the admin presses Save.
    // Materials or crew already in the quotation are skipped, so nothing is added twice.
    function applyQuoteTemplate(tpl) {
        if (!pricingBeforeTpl) {
            pricingBeforeTpl = {};
            PRICING_FIELD_IDS.forEach(function (id) { pricingBeforeTpl[id] = document.getElementById(id).value; });
            dirtyBeforeTpl = qbDirty;
        }
        appliedQuoteTpl = tpl;

        var usedMats = getUsedMaterialNames();
        var mats = tpl.materials.filter(function (m) {
            return m.name && usedMats.indexOf(m.name.toLowerCase()) === -1;
        });
        if (mats.length) {
            appendMaterialRows(mats).forEach(function (row) { row.dataset.fromTemplate = '1'; });
        }

        // crew already on the costing (archived saved rows don't count)
        var activeLabor = [];
        laborContainer.querySelectorAll('tr').forEach(function (row) {
            if (row.classList.contains('labor-existing-row')) {
                if (!row.classList.contains('is-archived')) activeLabor.push(row.dataset.employeeName);
                return;
            }
            var sel = row.querySelector('.row-labor-name-select');
            var custom = row.querySelector('.row-labor-name-custom');
            activeLabor.push(sel && sel.value && sel.value !== 'other' ? sel.value : (custom ? custom.value.trim() : ''));
        });
        tpl.labor.forEach(function (l) {
            if (!l.name || activeLabor.indexOf(l.name) !== -1) return;
            var row = buildLaborRow(laborContainer.querySelectorAll('tr').length + 1);
            row.dataset.fromTemplate = '1';
            laborContainer.appendChild(row);
            var sel = row.querySelector('.row-labor-name-select');
            // the default crew are GMD employees: pick them from the list (fills their role and rate); anyone
            // not on the list (e.g. no longer a regular employee) is typed in with the standard role and rate
            var isEmployee = LABOR_EMPLOYEES.some(function (emp) { return emp.name === l.name; });
            if (isEmployee) {
                sel.value = l.name;
                onLaborEmployeeChange(sel);
                row.querySelector('.row-labor-rate').value = fmtMoney(l.rate);
                return;
            }
            sel.value = 'other';
            onLaborEmployeeChange(sel);
            row.querySelector('.row-labor-name-custom').value = l.name;
            var roleSelect = row.querySelector('.row-labor-role-select');
            if (KNOWN_LABOR_ROLES.indexOf(l.role) !== -1) {
                roleSelect.value = l.role;
            } else if (l.role) {
                roleSelect.value = 'other';
                toggleRowLaborRole(roleSelect);
                row.querySelector('.row-labor-role-custom').value = l.role;
            }
            row.querySelector('.row-labor-rate').value = fmtMoney(l.rate);
        });

        setFieldValue('markupInput', tpl.markup_percent);
        setFieldValue('contingencyInput', tpl.contingency_percent);
        setFieldValue('materialFactorInput', tpl.factor);
        setFieldValue('estDaysInput', tpl.estimated_working_days);

        if (typeof lucide !== 'undefined') lucide.createIcons();
        renumberRows();
        updateEmptyStates();
        refreshAllLaborEmployeeOptions();
        recalc();
        markDirty();
        syncStdCostingButton();
    }

    function costingIsEmpty() {
        return !document.querySelector('#materialRowsContainer .material-add-row, #laborRowsContainer tr');
    }

    // "Unsaved changes" and "Undo all changes" show only while the form differs from how it opened — the snapshot is
    // taken once the page has filled itself in (e.g. a new quotation's standard costing), so nothing shows until the
    // admin changes something, and both hide again if it's changed back. Numbers are compared by value, so the
    // page re-formatting "4474" as "4,474.00" isn't a change.
    var openedSnapshot = null;
    function formSnapshot() {
        var parts = [];
        // fields disabled only because the form is read-only (Edit first) still count — FormData skips disabled ones,
        // so they're switched on just for this read; otherwise the snapshot taken while read-only would never match
        var viewOnly = quotationForm.querySelectorAll('[data-qb-view]');
        viewOnly.forEach(function (el) { el.disabled = false; });
        var data = new FormData(quotationForm);
        viewOnly.forEach(function (el) { el.disabled = true; });
        data.forEach(function (v, k) {
            if (k === '_token') return;
            v = v instanceof File ? v.name : String(v).trim();
            if (/^-?[\d,]*\.?\d+$/.test(v)) v = String(parseFloat(v.replace(/,/g, '')));
            parts.push(k + '=' + v);
        });
        return parts.join('&');
    }
    function hasUnsavedChanges() {
        return openedSnapshot !== null && formSnapshot() !== openedSnapshot;
    }
    function syncStdCostingButton() {
        var changed = hasUnsavedChanges();
        var btn  = document.getElementById('applyStdCostingBtn');
        var pill = document.getElementById('qbDirty');
        if (btn)  btn.hidden  = !changed;
        if (pill) pill.hidden = !changed;
    }

    // Is the costing exactly the standard one? Materials (name, unit, quantity, price), the active crew
    // (name and daily rate) and the pricing (markup, contingency, factor, working days) must all match.
    function costingMatchesStandard(std) {
        var key  = function (s) { return String(s == null ? '' : s).trim().toLowerCase(); };
        var same = function (a, b) { return Math.abs(toNum(a) - toNum(b)) < 0.005; };

        var mats = [];
        matContainer.querySelectorAll('.material-add-row').forEach(function (row) {
            mats.push([key(row.querySelector('.row-mat-name-input').value), key(row.querySelector('.row-unit').value),
                       toNum(row.querySelector('.row-qty').value), toNum(row.querySelector('.row-price').value)]);
        });
        if (mats.length !== std.materials.length) return false;
        var wanted = std.materials.map(function (m) { return [key(m.name), key(m.unit), +m.qty, +m.price]; });
        var matsOk = wanted.every(function (w) {
            return mats.some(function (m) { return m[0] === w[0] && m[1] === w[1] && same(m[2], w[2]) && same(m[3], w[3]); });
        });
        if (!matsOk) return false;

        var crew = [];
        laborContainer.querySelectorAll('tr').forEach(function (row) {
            if (row.classList.contains('labor-existing-row')) {
                if (!row.classList.contains('is-archived')) crew.push([key(row.dataset.employeeName), toNum(row.dataset.rate)]);
                return;
            }
            var sel = row.querySelector('.row-labor-name-select');
            var custom = row.querySelector('.row-labor-name-custom');
            var name = sel && sel.value && sel.value !== 'other' ? sel.value : (custom ? custom.value : '');
            crew.push([key(name), toNum((row.querySelector('.row-labor-rate') || {}).value)]);
        });
        if (crew.length !== std.labor.length) return false;
        var crewOk = std.labor.every(function (l) {
            return crew.some(function (c) { return c[0] === key(l.name) && same(c[1], l.rate); });
        });
        if (!crewOk) return false;

        return same(document.getElementById('markupInput').value, std.markup_percent)
            && same(document.getElementById('contingencyInput').value, std.contingency_percent)
            && same(document.getElementById('materialFactorInput').value, std.factor)
            && same(document.getElementById('estDaysInput').value, std.estimated_working_days);
    }

    // any edit in the costing (typing, adding / removing rows) re-checks whether it still matches the standard
    var stdSyncQueued = false;
    function queueStdSync() {
        if (stdSyncQueued) return;
        stdSyncQueued = true;
        requestAnimationFrame(function () { stdSyncQueued = false; syncStdCostingButton(); });
    }
    ['input', 'change', 'click'].forEach(function (ev) { quotationForm.addEventListener(ev, queueStdSync); });
    new MutationObserver(queueStdSync).observe(matContainer, { childList: true });
    new MutationObserver(queueStdSync).observe(laborContainer, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
    var stdBtn = document.getElementById('applyStdCostingBtn');
    // Discards every unsaved change — tank, quantity, pricing, payment terms, materials and labor — by opening the page
    // fresh: a saved costing comes back as saved, a new one with its standard costing. (A fresh navigation, not a
    // reload, so the browser can't restore the edited values.)
    if (stdBtn) stdBtn.addEventListener('click', function () {
        if (!confirm('Undo all changes?\n\nEverything you changed since opening this page goes back to how it was. Nothing is saved.')) return;
        qbSubmitting = true;   // no "leave this page?" warning
        window.location.replace(window.location.href.split('#')[0]);
    });

    // A different tank type or capacity: the standard costing of the old size no longer applies — take it out, and
    // fill in the new size's when the costing has nothing else in it.
    function onTankChangedForStandard() {
        var std = standardCostingForTank();
        if (appliedQuoteTpl && (!std || appliedQuoteTpl.id !== std.id)) clearAppliedQuoteTemplate();
        if (std && !appliedQuoteTpl && costingIsEmpty()) applyQuoteTemplate(std);
        syncStdCostingButton();
    }

    // Opening a blank costing for a standard size starts from its standard costing — after the rest of this
    // script has run (the tank, quantity and totals code below must be ready first)
    setTimeout(function () {
        @if($materials->isEmpty() && $laborEntries->isEmpty())
        var std = standardCostingForTank();
        if (std) applyQuoteTemplate(std);
        @endif
        // the form as it opened (after filling itself in, and after any number formatting has run) — changes are measured from here
        setTimeout(function () {
            openedSnapshot = formSnapshot();
            syncStdCostingButton();
        }, 300);
    }, 0);
    @endunless

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

    // The rows are saved for ONE tank. With more than one tank, the Quantity boxes and every total show the whole
    // order (× the tank quantity) — the per-tank figures are in each box's tooltip.
    function recalc() {
        var tanks = tankQuantity();
        var multi = tanks > 1;
        var forTanks = ' for ' + tanks + ' tanks';
        syncQtyViews(tanks);

        // Materials
        var subtotal = 0;
        matContainer.querySelectorAll('.material-add-row').forEach(function (row) {
            var qty   = toNum(row.querySelector('.row-qty').value);
            var total = qty * toNum(row.querySelector('.row-price').value);
            subtotal += total;
            var shown = row.querySelector('.row-total-display');
            shown.value = total > 0 ? peso(total * tanks, 2) : '';
            shown.title = multi && total > 0 ? peso(total, 2) + ' per tank' : '';
        });
        var factor     = toNum(document.getElementById('materialFactorInput').value);
        var withFactor = subtotal * (1 + factor / 100);
        document.getElementById('matSubtotal').textContent   = peso(Math.round(subtotal * tanks));
        document.getElementById('matWithFactor').textContent = peso(Math.round(withFactor * tanks));
        document.getElementById('matFactorLabel').textContent = factor.toFixed(1);

        // Labor — every row's total is its daily rate x the shared working days
        var days = toNum(document.getElementById('estDaysInput').value);
        var laborTotal = 0;
        laborContainer.querySelectorAll('tr').forEach(function (row) {
            if (row.classList.contains('labor-existing-row')) {
                var t = toNum(row.dataset.rate) * days;
                var cell = row.querySelector('.existing-labor-total');
                cell.textContent = peso(t * tanks, 2);
                cell.title = multi && t > 0 ? peso(t, 2) + ' per tank' : '';
                if (!row.classList.contains('is-archived')) laborTotal += t;
            } else {
                var nt = toNum(row.querySelector('.row-labor-rate').value) * days;
                var box = row.querySelector('.row-labor-total-display');
                box.value = nt > 0 ? peso(nt * tanks, 2) : '';
                box.title = multi && nt > 0 ? peso(nt, 2) + ' per tank' : '';
                laborTotal += nt;
            }
        });
        document.getElementById('laborTotal').textContent = peso(Math.round(laborTotal * tanks));
        document.querySelectorAll('[data-qty-tag]').forEach(function (el) { el.textContent = multi ? ' —' + forTanks : ''; });
        document.querySelectorAll('.qb-per-tank').forEach(function (el) { el.textContent = multi ? 'per tank × ' + tanks : 'per tank'; });

        // Other costs (on-site assembly) — for the whole job, only when the switch is on
        var onsite = document.getElementById('onsiteToggle').checked;
        var others = 0;
        document.querySelectorAll('#othersRows .row-other-amount').forEach(function (el) { others += toNum(el.value); });
        document.getElementById('othersTotal').textContent = peso(Math.round(others));
        if (!onsite) others = 0;

        // Summary bar — the rows above are for ONE tank; the quantity multiplies them, once.
        // Project Budget = (materials + labor) × qty + other costs, plus contingency on all of that; markup on the budget.
        var qty         = tankQuantity();
        var pct         = toNum(document.getElementById('markupInput').value);
        var contPct     = toNum(document.getElementById('contingencyInput').value);
        var direct      = (withFactor + laborTotal) * qty + others;
        var contingency = direct * contPct / 100;
        var budget      = direct + contingency;
        var markup      = budget * pct / 100;
        // the standard costing, left as it is, is priced at exactly GMD's estimated price (× the tanks) — the markup takes
        // up the rounding; other costs are added with the usual contingency and markup (same as QuotationBatch::markupAmount)
        var std = typeof standardCostingForTank === 'function' ? standardCostingForTank() : null;
        if (std && typeof costingMatchesStandard === 'function' && costingMatchesStandard(std)) {
            var exact = std.estimated_price * qty + others * (1 + contPct / 100) * (1 + pct / 100);
            markup = exact - budget;
        }
        var unitPrice   = (budget + markup) / qty;
        document.getElementById('contingencyValueNote').textContent = peso(Math.round(contingency));
        document.getElementById('sumUnitPrice').textContent = peso(Math.round(unitPrice));
        document.getElementById('sumQty').textContent       = qty;
        document.getElementById('sumQtyLabel').textContent  = qty === 1 ? 'tank' : 'identical tanks';
        document.getElementById('sumBudget').textContent    = peso(Math.round(budget));
        document.getElementById('sumMarkup').textContent    = peso(Math.round(markup));
        document.getElementById('sumMarkupPct').textContent = pct + '%';
        // the same value, spelled out under the Markup / Profit field
        document.getElementById('markupValueNote').textContent  = peso(Math.round(markup));
        document.getElementById('markupBudgetNote').textContent = 'of ' + peso(Math.round(budget)) + ' Project Budget' + (qty > 1 ? ' (' + qty + ' tanks)' : '');
        document.getElementById('sumContract').textContent = peso(Math.round(budget + markup));

        var estimate = estimateFor(document.getElementById('tankTypeInput').value, document.getElementById('capacityInput').value);
        // before any costs are in, the estimate decides
        if (budget + markup > 0) autoPaymentTerms(budget + markup, 'contract value');
        else autoPaymentTerms(estimate ? estimate.amount * qty : 0, 'estimated price');
    }

    // ---- GMD's estimated price for the tank being quoted (standard size, or rate × meters) ----
    var PRICE_GUIDE = @json($priceGuide);
    function capacityNumber(s) {
        var m = String(s || '').replace(/(\d),(\d)/g, '$1$2').match(/\d+(\.\d+)?/);
        return m ? parseFloat(m[0]) : null;
    }
    function estimateFor(type, capacity) {
        var g = PRICE_GUIDE[type], n = capacityNumber(capacity);
        if (!g || n === null) return null;
        for (var i = 0; i < g.sizes.length; i++) {
            if (capacityNumber(g.sizes[i].capacity) === n) return { amount: +g.sizes[i].price, basis: g.sizes[i].capacity + ' standard size' };
        }
        if (g.rate && n > 0) return { amount: Math.round(g.rate[0] * n * 100) / 100, basis: n + ' × ' + peso(g.rate[0]) + ' per ' + g.rate[1] };
        return null;
    }

    // ---- Payment terms follow the size of the job until the admin picks them ----
    var BIG_PROJECT_THRESHOLD = {{ \App\Models\QuotationBatch::BIG_PROJECT_THRESHOLD }};
    function autoPaymentTerms(value, basis) {
        var sel = document.getElementById('paymentTermsInput');
        if (sel.dataset.auto !== '1' || sel.disabled) return;
        if (value > 0) sel.value = value >= BIG_PROJECT_THRESHOLD ? 'big_project' : 'small_project';
        document.getElementById('paymentAutoState').textContent = value > 0
            ? 'set from the ' + peso(Math.round(value)) + ' ' + basis
            : 'set from the contract value';
    }
    document.getElementById('paymentTermsInput').addEventListener('change', function () {
        this.dataset.auto = '0';   // the admin chose — leave it alone from now on
        document.getElementById('paymentAutoState').textContent = 'chosen';
    });
    // a different tank type / capacity means a different estimate — recompute after their own handlers update the value
    ['tankTypeInput', 'capacitySelect', 'capacityCustom'].forEach(function (id) {
        ['change', 'input'].forEach(function (ev) {
            document.getElementById(id).addEventListener(ev, function () { setTimeout(recalc, 0); });
        });
    });

    ['markupInput', 'materialFactorInput', 'estDaysInput', 'tankQtyInput', 'contingencyInput'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', recalc);
    });

    // ---- 5 · Other costs (on-site assembly): the switch shows the list; rows can be added and removed ----
    (function () {
        var toggle = document.getElementById('onsiteToggle');
        var body   = document.getElementById('othersBody');
        var rows   = document.getElementById('othersRows');
        function bindRow(tr) {
            var amount = tr.querySelector('.row-other-amount');
            amount.addEventListener('input', recalc);
            // shown with thousands separators like the other peso fields; the server strips them
            amount.addEventListener('blur', function () {
                var v = toNum(amount.value);
                amount.value = amount.value.trim() === '' ? '' : v.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            });
            tr.querySelector('.other-remove').addEventListener('click', function () { tr.remove(); recalc(); });
        }
        rows.querySelectorAll('tr').forEach(bindRow);
        toggle.addEventListener('change', function () { body.hidden = !toggle.checked; recalc(); });
    })();

    // ---- Tank: type → capacities (and templates); quantity multiplies the totals ----
    var TANK_OPTIONS    = @json($tankTypeOptions);   // type => capacities GMD has built for it
    var tankTypeInput   = document.getElementById('tankTypeInput');
    var capacitySelect  = document.getElementById('capacitySelect');
    var capacityCustom  = document.getElementById('capacityCustom');
    var capacityInput   = document.getElementById('capacityInput');
    var tankQtyInput    = document.getElementById('tankQtyInput');
    var CAPACITY_OTHER  = '__other';

    // a positive whole number; anything else counts as 1 until it is corrected
    function tankQuantity() {
        var v = String(tankQtyInput.value).trim();
        return /^\d+$/.test(v) && +v >= 1 ? +v : 1;
    }
    tankQtyInput.addEventListener('blur', function () {
        var v = String(tankQtyInput.value).trim();
        if (v !== '' && !/^\d+$/.test(v)) tankQtyInput.value = Math.max(1, Math.round(toNum(v)) || 1);
        recalc();
    });

    function capacitiesForType() { return TANK_OPTIONS[tankTypeInput.value] || []; }

    // Only the standard sizes of the chosen type are offered, plus "Other" to type a new one.
    // A type with no standard sizes (e.g. pipe lines) goes straight to the text box.
    function renderCapacityOptions() {
        var caps    = capacitiesForType();
        var current = capacityInput.value;
        var hint    = document.getElementById('capacityHint');

        if (!tankTypeInput.value) {
            capacitySelect.innerHTML = '<option value="" selected disabled hidden>Select a tank type first</option>';
            capacitySelect.disabled = true;
            capacitySelect.hidden = false;
            capacityCustom.hidden = true;
            hint.textContent = '';
            return;
        }
        capacitySelect.disabled = false;

        if (!caps.length) {
            capacitySelect.hidden = true;
            capacityCustom.hidden = false;
            capacityCustom.value  = current;
            hint.textContent = 'No standard sizes for ' + tankTypeInput.value + ' — type the capacity (e.g. total pipe length).';
            return;
        }

        var inList = caps.indexOf(current) !== -1;
        capacitySelect.innerHTML =
            '<option value="" disabled hidden' + (current ? '' : ' selected') + '>Select capacity</option>' +
            caps.map(function (c) { return '<option value="' + escapeHtml(c) + '"' + (c === current ? ' selected' : '') + '>' + escapeHtml(c) + '</option>'; }).join('') +
            '<option value="' + CAPACITY_OTHER + '"' + (current && !inList ? ' selected' : '') + '>Other capacity…</option>';
        capacitySelect.hidden = false;
        capacityCustom.hidden = !(current && !inList);
        if (current && !inList) capacityCustom.value = current;
        hint.textContent = 'Standard sizes of ' + tankTypeInput.value + ' — each comes with a standard costing.';
    }

    capacitySelect.addEventListener('change', function () {
        if (capacitySelect.value === CAPACITY_OTHER) {
            capacityCustom.hidden = false;
            capacityInput.value   = capacityCustom.value.trim();
            capacityCustom.focus();
        } else {
            capacityCustom.hidden = true;
            capacityInput.value   = capacitySelect.value;
        }
        if (typeof onTankChangedForStandard === 'function') onTankChangedForStandard();
    });
    capacityCustom.addEventListener('input', function () {
        capacityInput.value = capacityCustom.value.trim();
        if (typeof onTankChangedForStandard === 'function') onTankChangedForStandard();
    });

    tankTypeInput.addEventListener('change', function () {
        // a capacity that belongs to another tank type is cleared, never carried over
        if (capacityInput.value && capacitiesForType().length && capacitiesForType().indexOf(capacityInput.value) === -1) {
            capacityInput.value = '';
            capacityCustom.value = '';
        }
        renderCapacityOptions();
        if (typeof onTankChangedForStandard === 'function') onTankChangedForStandard();
    });
    renderCapacityOptions();

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

    // ---- Live preview of the quotation document exactly as typed (nothing saved) ----
    var LIVE_PREVIEW_URL = @json(route('admin.quotation_requests.batch_live_preview', $batch->id));
    // only what the document step edits: the details + specification lines (+ the CSRF token)
    function docPayload() {
        var all = new FormData(quotationForm), data = new FormData();
        all.forEach(function (v, k) {
            if (k === '_token' || k.indexOf('doc[') === 0 || k === 'spec_label[]' || k === 'spec_value[]') data.append(k, v);
        });
        return data;
    }
    function fetchDocPreview() {
        // token sent as a header too: on a view-only quotation every form field (the token included) is disabled
        return fetch(LIVE_PREVIEW_URL, { method: 'POST', body: docPayload(), headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html', 'X-CSRF-TOKEN': @json(csrf_token()) } })
            .then(function (r) { if (!r.ok) throw new Error(r.status === 422 ? 'invalid' : 'http'); return r.text(); });
    }

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

        // a Quantity box still being typed in (e.g. Enter pressed) is rounded to whole units per tank first
        matContainer.querySelectorAll('.row-qty-view').forEach(function (view) {
            if (view.value.trim() !== '') qtyViewBlur(view);
        });

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

        // 1 · Tank
        if (!tankTypeInput.value) flag(tankTypeInput, 'Tank Type');
        if (!capacityInput.value.trim()) flag(capacitySelect.hidden || capacitySelect.value === CAPACITY_OTHER ? capacityCustom : capacitySelect, 'Capacity');
        var qtyRaw = String(tankQtyInput.value).trim();
        if (qtyRaw === '') flag(tankQtyInput, 'Tank Quantity');
        else if (!/^\d+$/.test(qtyRaw) || +qtyRaw < 1) flag(tankQtyInput, 'Tank Quantity (a whole number, 1 or more)');

        // 2 · Pricing
        var markupEl = document.getElementById('markupInput');
        var pct = markupEl.value.trim();
        if (pct === '') flag(markupEl, 'Markup / Profit');
        else if (isNaN(pct) || +pct < MARKUP_MIN || +pct > MARKUP_MAX) flag(markupEl, 'Markup / Profit (' + MARKUP_MIN + '–' + MARKUP_MAX + '%)');

        var termsEl = document.getElementById('paymentTermsInput');
        if (!termsEl.value) flag(termsEl, 'Payment Terms');

        // 3 · Materials
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
            if (!(toNum(qtyEl.value) > 0)) missing.push({ el: qtyView, label: 'quantity' });
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

        // 4 · Labor
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

        // 5 · Quotation Document — the signatory's e-signature is required
        if (!document.getElementById('docSignSignature').value) flag(document.getElementById('docSigBox'), 'E-Signature (Quotation Document step)');

        if (problems.length > 0) {
            e.preventDefault();
            document.getElementById('openSendModalFlag').value = '0';
            showQbErrors(problems);
            return;
        }

        document.getElementById('qbErrors').hidden = true;

        // the server gets plain numbers — "15,000.00" goes out as 15000.00
        unformatMoneyFields();
        qbSubmitting = true;
        var saveBtn = document.getElementById('qbSaveBtn');
        saveBtn.disabled = true;
        saveBtn.lastChild.textContent = ' Saving…';
    });

    // ---- Send to Client is only available once the whole builder is complete ----
    // Same rules as the Save validation, checked silently (no red outlines, blank new rows ignored).
    function builderIsComplete() {
        var val = function (id) { return document.getElementById(id).value.trim(); };

        if (!val('tankTypeInput') || !val('capacityInput')) return false;
        if (!/^\d+$/.test(val('tankQtyInput')) || +val('tankQtyInput') < 1) return false;
        var pct = val('markupInput');
        if (pct === '' || isNaN(pct) || +pct < MARKUP_MIN || +pct > MARKUP_MAX) return false;
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
        // Step 1 → 2: "Generate Quotation Document" opens once the costing is complete AND saved — whether or not
        // Edit is pressed. Only a real unsaved change (the form differs from how it opened) greys it out until Save.
        var gen = document.getElementById('generateDocBtn');
        if (gen) {
            var unsaved  = typeof hasUnsavedChanges === 'function' ? hasUnsavedChanges() : qbDirty;
            var genReady = gen.dataset.ready === '1' && !unsaved && builderIsComplete();
            gen.setAttribute('aria-disabled', genReady ? 'false' : 'true');
            gen.title = genReady ? ''
                : (unsaved ? 'Save the costing first, then generate the quotation document.'
                           : 'Complete and save the costing first — every required field, at least one material and one labor entry.');
        }

        var btn = document.getElementById('openSendQuotationModal');
        if (!btn) return;
        // sending also needs the signatory's e-signature (set on the Quotation Document step)
        var hasSignature = !!(document.getElementById('docSignSignature') || {}).value;
        var ready = builderIsComplete() && hasSignature;
        btn.disabled = !ready;
        btn.setAttribute('aria-disabled', ready ? 'false' : 'true');
        btn.title = ready ? '' : (hasSignature
            ? 'Complete the Costing first — every required field, at least one material and one labor entry.'
            : 'Upload the signatory\'s e-signature first.');
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

        // supporting files are optional — the quotation itself is generated by the system
    })();

    // ---- Quotation Document: specification lines ----
    (function () {
        var body = document.getElementById('specRows');
        var add  = document.getElementById('addSpecRow');
        if (!body) return;
        function markDirty() { if (typeof quotationForm !== 'undefined') quotationForm.dispatchEvent(new Event('input', { bubbles: true })); }
        body.addEventListener('click', function (e) {
            var btn = e.target.closest('.spec-remove');
            if (!btn || btn.disabled) return;
            btn.closest('tr').remove();
            markDirty();
        });
        if (add) add.addEventListener('click', function () {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td><input type="text" name="spec_label[]" class="qb-cell-input" maxlength="120" placeholder="e.g. Accessories" aria-label="Specification item"></td>' +
                '<td><textarea name="spec_value[]" class="qb-cell-input qd-spec-value" rows="3" maxlength="1000" aria-label="Specification"></textarea></td>' +
                '<td><button type="button" class="qb-icon-btn spec-remove" title="Remove line" aria-label="Remove specification line"><i data-lucide="trash-2"></i></button></td>';
            body.appendChild(tr);
            if (typeof lucide !== 'undefined') lucide.createIcons();
            tr.querySelector('input').focus();
            markDirty();
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

    // ---- Signatory e-signature: uploaded as soon as it's picked (or dropped); the URL goes in doc[signatory_signature] ----
    (function () {
        var box = document.getElementById('docSigBox');
        if (!box) return;
        var file    = document.getElementById('docSigFile');
        var hidden  = document.getElementById('docSignSignature');
        var preview = box.querySelector('.qd-sig-preview');
        var drop    = box.querySelector('.qd-sig-drop');
        var busy    = box.querySelector('.qd-sig-busy');
        var img     = document.getElementById('docSigImg');
        var err     = document.getElementById('docSigErr');
        var uploadUrl = @json(route('admin.quotation_requests.batch_signature', $batch->id));

        function set(url) {
            hidden.value = url || '';
            if (url) img.src = url;
            preview.hidden = !url;
            drop.hidden    = !!url;
            if (url) box.classList.remove('qb-bad');
            // let the live preview (and Send's readiness check) pick up the change
            quotationForm.dispatchEvent(new Event('input', { bubbles: true }));
        }
        function showErr(msg) { err.textContent = msg || ''; err.hidden = !msg; }

        function upload(f) {
            showErr('');
            if (!f) return;
            if (!/^image\/(png|jpe?g|webp)$/.test(f.type)) { showErr('Please choose a PNG, JPG or WEBP image.'); return; }
            if (f.size > 2 * 1024 * 1024) { showErr('The signature image must be 2 MB or smaller.'); return; }
            var data = new FormData();
            data.append('signature', f);
            busy.hidden = false;
            fetch(uploadUrl, { method: 'POST', body: data, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) } })
                .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error((j.errors && j.errors.signature && j.errors.signature[0]) || j.message || 'Upload failed.'); return j; }); })
                .then(function (j) { set(j.url); })
                .catch(function (e) { showErr(e.message || 'The signature upload failed. Please try again.'); })
                .finally(function () { busy.hidden = true; file.value = ''; });
        }

        file.addEventListener('change', function () { upload(file.files[0]); });
        var removeBtn = document.getElementById('docSigRemove');
        if (removeBtn) removeBtn.addEventListener('click', function () { set(''); });
        ['dragover', 'dragenter'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); }); });
        ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.remove('is-over'); }); });
        drop.addEventListener('drop', function (e) {
            e.preventDefault();
            if (quotationForm.classList.contains('qb-viewing') || file.disabled) return;   // read-only: press Edit first
            upload(e.dataTransfer.files[0]);
        });
    })();

    // ---- Quotation Document step: live preview of the document as typed (nothing is saved) ----
    (function () {
        var frame = document.getElementById('qdPreviewFrame');
        if (!frame) return;
        var status  = document.getElementById('qdPreviewStatus');
        var loading = document.getElementById('qdPreviewLoading');
        var timer = null, seq = 0, keepScroll = 0, loaded = false;

        // Plain text fields are written straight into the preview as you type — no round trip at all
        var LIVE = { docTitle: 'project_title', docLocation: 'project_location', docRfq: 'rfq_ref',
                     docWarranty: 'warranty', docSignName: 'signatory_name', docSignTitle: 'signatory_title' };
        function frameDoc() { try { return frame.contentDocument; } catch (e) { return null; } }
        function applyLive(id) {
            var d = frameDoc(), input = document.getElementById(id);
            if (!d || !input) return;
            d.querySelectorAll('[data-live="' + LIVE[id] + '"]').forEach(function (el) {
                var v = input.value.trim();
                el.textContent = v || el.getAttribute('data-live-empty') || '';
            });
        }
        // Specification lines and general notes are simple too — redrawn in the preview straight away
        function applySpecs() {
            var d = frameDoc(), table = d && d.querySelector('[data-live-specs]');
            if (!table) return;
            var rows = [];
            document.querySelectorAll('#specRows tr').forEach(function (tr) {
                var l = tr.querySelector('[name="spec_label[]"]'), v = tr.querySelector('[name="spec_value[]"]');
                var label = l ? l.value.trim() : '', value = v ? v.value.trim() : '';
                if (label || value) rows.push([label, value]);
            });
            if (!rows.length) return;            // none left → the server fills in the tank type's defaults
            var body = table.tBodies[0] || table;
            body.innerHTML = '';
            rows.forEach(function (r) {
                var tr = d.createElement('tr'), k = d.createElement('td'), val = d.createElement('td');
                k.className = 'qd-spec-k';
                k.textContent = r[0] + (r[0] !== '' ? ':' : '');
                val.textContent = r[1];
                tr.appendChild(k); tr.appendChild(val); body.appendChild(tr);
            });
        }
        function applyNotes() {
            var d = frameDoc(), list = d && d.querySelector('.qd-notes'), box = document.getElementById('docNotes');
            if (!list || !box) return;
            list.querySelectorAll('[data-live-note]').forEach(function (li) { li.remove(); });
            box.value.split(/\r\n|\r|\n/).map(function (s) { return s.trim(); }).filter(Boolean).forEach(function (note) {
                var li = d.createElement('li');
                li.setAttribute('data-live-note', '');
                li.textContent = note;
                list.appendChild(li);
            });
        }
        // the e-signature over the name — straight from the uploaded image's URL
        function applySignature() {
            var d = frameDoc(), url = document.getElementById('docSignSignature').value;
            if (!d) return;
            d.querySelectorAll('[data-live-signature]').forEach(function (img) {
                if (url) img.src = url;
                img.style.display = url ? '' : 'none';
            });
        }
        function applyAllLive() { Object.keys(LIVE).forEach(applyLive); applySpecs(); applyNotes(); applySignature(); fitFrame(); updateSpecCount(); }

        // Full length: the frame is as tall as the document, so the whole quotation is visible and the page scrolls
        function fitFrame() {
            var d = frameDoc();
            if (!d || !d.documentElement) return;
            var h = Math.max(d.documentElement.scrollHeight, d.body ? d.body.scrollHeight : 0);
            if (h > 0) frame.style.height = h + 'px';
        }
        function updateSpecCount() {
            var c = document.getElementById('specCount');
            if (c) c.textContent = document.querySelectorAll('#specRows tr').length;
        }
        function setStatus(kind, text) {   // kind: '', 'is-busy' or 'is-error'
            status.classList.remove('is-busy', 'is-error');
            if (kind) status.classList.add(kind);
            status.querySelector('.qd-status-text').textContent = text;
        }

        // Everything else (validity date, Annex A, default spec lines) comes from the server — but only the
        // document part is swapped in place, so the frame never reloads its styles or logos
        function swapSheet(html) {
            var d = frameDoc(), sheet = d && d.querySelector('.lp-sheet');
            if (!loaded || !sheet) { frame.srcdoc = html; return; }
            var fresh = new DOMParser().parseFromString(html, 'text/html').querySelector('.lp-sheet');
            if (!fresh) { frame.srcdoc = html; return; }
            sheet.innerHTML = fresh.innerHTML;
            applyAllLive();   // keep anything typed while the request was on its way
            // logos are cached, but measure again once any image settles
            sheet.querySelectorAll('img').forEach(function (img) { if (!img.complete) img.addEventListener('load', fitFrame, { once: true }); });
        }

        function refresh() {
            var mine = ++seq;
            loading.classList.add('show');
            setStatus('is-busy', 'Updating…');
            fetchDocPreview()
                .then(function (html) {
                    if (mine !== seq) return;          // a newer refresh is on its way
                    swapSheet(html);
                    setStatus('', @json($isLocked ? 'As sent to the client · view only' : 'In sync · not saved until you press Save'));
                })
                .catch(function (err) {
                    if (mine !== seq) return;
                    setStatus('is-error', err.message === 'invalid'
                        ? 'Preview paused — a field is too long or out of range.'
                        : 'Preview could not refresh. It will try again on your next change.');
                })
                .finally(function () { if (mine === seq) loading.classList.remove('show'); });
        }

        frame.addEventListener('load', function () { loaded = true; applyAllLive(); });
        window.addEventListener('resize', function () { clearTimeout(fitFrame._t); fitFrame._t = setTimeout(fitFrame, 120); });

        function schedule() { clearTimeout(timer); timer = setTimeout(refresh, 250); }
        quotationForm.addEventListener('input', function () {
            applyAllLive();   // instant: text fields, specification lines, notes
            schedule();       // and the full document (validity date, Annex A, defaults) shortly after
        });
        quotationForm.addEventListener('change', schedule);
        refresh();
    })();

    // ---- Edit first: a saved quotation opens read-only until Edit is pressed ----
    // Every field is disabled while viewing (tracked with data-qb-view so fields disabled for other reasons stay so).
    function qbSetViewing(on) {
        if (!document.getElementById('qbEditBtn')) return;   // view-only (locked) quotations have no Edit
        quotationForm.classList.toggle('qb-viewing', on);
        if (on) {
            quotationForm.querySelectorAll('input:not([type="hidden"]), select, textarea, button').forEach(function (el) {
                if (el.id === 'qbEditBtn' || el.disabled) return;
                el.disabled = true;
                el.setAttribute('data-qb-view', '');
            });
        } else {
            quotationForm.querySelectorAll('[data-qb-view]').forEach(function (el) {
                el.disabled = false;
                el.removeAttribute('data-qb-view');
            });
        }
    }
    (function () {
        var editBtn = document.getElementById('qbEditBtn');
        if (!editBtn) return;

        editBtn.addEventListener('click', function () {
            qbSetViewing(false);
            var first = document.getElementById(quotationForm.dataset.step === 'document' ? 'docTitle' : 'tankQtyInput');
            if (first && first.offsetParent) first.focus();
        });
        // Cancel: nothing changed → just back to read-only; otherwise discard the changes (reload as saved)
        document.getElementById('qbCancelEditBtn').addEventListener('click', function () {
            var changed = typeof hasUnsavedChanges === 'function' ? hasUnsavedChanges() : qbDirty;
            if (!changed) { qbSetViewing(true); return; }
            openModal('discardChangesModal');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
        document.getElementById('confirmDiscardBtn').addEventListener('click', function () {
            this.disabled = true;
            qbSubmitting = true;   // no "leave this page?" warning
            window.location.reload();
        });
        ['keepEditingBtn', 'closeDiscardChanges'].forEach(function (id) {
            document.getElementById(id).addEventListener('click', function () { closeModal('discardChangesModal'); });
        });
        document.getElementById('discardChangesModal').addEventListener('click', function (e) {
            if (e.target === this) closeModal('discardChangesModal');
        });

        // Opens read-only once the costing has been saved complete; a new / incomplete quotation, or one that
        // came back with errors, opens ready to edit.
        @if($costingReady && !$errors->any() && !session()->hasOldInput())
        qbSetViewing(true);
        @endif
    })();

    @if($isLocked)
    // View-only: the client has approved this quotation, so every field in the builder is frozen.
    quotationForm.querySelectorAll('input, select, textarea, button').forEach(function (el) { el.disabled = true; });
    @endif
    </script>
    @include('partials.receipt_viewer')

</body>
</html>
