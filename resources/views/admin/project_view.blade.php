<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project View | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            <!-- Breadcrumb -->
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('admin.projects') }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    Projects
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="color:var(--dark);font-weight:700;">{{ $project->name }}</span>
            </div>

            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1>
                        {{ $project->name }}
                    </h1>
                    <p>
                        <span class="client-pill">{{ $project->client }}</span>
                        @if($project->tankItems->isNotEmpty())
                            @foreach($project->tankItems as $ti)
                            &nbsp;·&nbsp;
                            <strong>{{ $ti->quantity }}×</strong> {{ $ti->tank_type }}@if($ti->capacity) ({{ $ti->capacity }})@endif
                            @endforeach
                        @else
                            &nbsp;·&nbsp; {{ $project->tank_type }} &nbsp;·&nbsp; {{ $project->capacity }}
                        @endif
                    </p>
                </div>
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

            <!-- Financial Overview Card -->
            @php
                $collPct = $contractAmount > 0 ? min(100,round(($budgetReceived/$contractAmount)*100)) : 0;
            @endphp
            <div class="fd-overview" style="margin-bottom:28px;">
                <div class="fd-overview-title">
                    <i data-lucide="bar-chart-2"></i>
                    Project Financial Overview
                </div>
                <div class="fd-overview-grid">
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Contract Value</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Total Project Cost</span>
                        <span class="fd-ov-val">{{ $contractAmount > 0 ? '₱'.number_format($contractAmount,2) : '—' }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Total Received</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">
                            {{ $payment && $payment->status === 'Fully Paid' ? 'Fully Paid' : 'Amount received' }}
                        </span>
                        <span class="fd-ov-val" style="color:#4ade80;">
                            {{ $budgetReceived > 0 ? '₱'.number_format($budgetReceived,2) : '—' }}
                        </span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Est. Materials</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Total BOM cost</span>
                        <span class="fd-ov-val">₱{{ number_format($estMaterialCost, 2) }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Actual Materials</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Material purchases</span>
                        <span class="fd-ov-val">{{ $actMaterialCost > 0 ? '₱'.number_format($actMaterialCost,2) : '—' }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Est. Labor</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">From project quotation</span>
                        <span class="fd-ov-val">₱{{ number_format($estLaborCost, 2) }}</span>
                    </div>
                    <div class="fd-ov-item">
                        <span class="fd-ov-label">Actual Labor</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Total gross &middot; salary module</span>
                        <span class="fd-ov-val">{{ $actLaborCost > 0 ? '₱'.number_format($actLaborCost,2) : '—' }}</span>
                    </div>
                    @php
                        $totalActualSpend = $actMaterialCost + $actLaborCost;
                        $netProfit        = $contractAmount > 0 ? $contractAmount - $totalActualSpend : null;
                    @endphp
                    <div class="fd-ov-item fd-ov-highlight">
                        <span class="fd-ov-label">Total Actual Spend</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Actual materials + labor</span>
                        <span class="fd-ov-val" style="color:{{ $totalActualSpend > 0 ? '#f87171' : 'rgba(255,255,255,0.35)' }};font-size:17px;">
                            {{ $totalActualSpend > 0 ? '₱'.number_format($totalActualSpend,2) : '—' }}
                        </span>
                    </div>
                    <div class="fd-ov-item fd-ov-highlight">
                        <span class="fd-ov-label">Net Profit</span>
                        <span class="fd-ov-label" style="font-size:9px;color:rgba(255,255,255,0.3);">Contract value - actual costs</span>
                        @if($netProfit !== null)
                        <span class="fd-ov-val" style="color:{{ $netProfit >= 0 ? '#4ade80' : '#f87171' }};font-size:17px;">
                            {{ $netProfit >= 0 ? '+' : '' }}₱{{ number_format($netProfit, 2) }}
                        </span>
                        @else
                        <span class="fd-ov-val" style="color:rgba(255,255,255,0.35);">—</span>
                        @endif
                    </div>
                </div>
                <div style="margin-top:16px;padding-top:14px;border-top:1px solid rgba(255,255,255,0.1);">
                    @php
                        $payLabel = ($payment && $payment->status === 'Fully Paid') ? 'Fully paid' : 'Payment received';
                    @endphp
                    <div style="display:flex;justify-content:space-between;font-size:11.5px;color:rgba(255,255,255,0.5);margin-bottom:6px;">
                        <span>{{ $payLabel }}</span>
                        <span>{{ $collPct }}% &middot; {{ $budgetReceived > 0 ? '₱'.number_format($budgetReceived,2) : '—' }} of ₱{{ number_format($contractAmount,2) }}</span>
                    </div>
                    <div style="height:8px;background:rgba(255,255,255,0.12);border-radius:999px;overflow:hidden;">
                        <div style="height:100%;width:{{ $collPct }}%;background:{{ $collPct >= 100 ? '#4ade80' : '#facc15' }};border-radius:999px;transition:width 0.5s;"></div>
                    </div>
                </div>
            </div>
            <!-- Phase Tracker Card -->
            <div class="tracker-card">
                <div class="tracker-card-header">
                    <div class="tracker-title">
                        <i data-lucide="layers"></i>
                        Fabrication Phase Tracker
                    </div>
                    <div class="tracker-progress-wrap">
                        <div class="tracker-progress-bar">
                            <div class="tracker-progress-fill" id="progressFill" style="width:{{ $project->progress }}%"></div>
                        </div>
                        <span class="tracker-progress-badge" id="progressBadge">{{ $project->progress }}%</span>
                    </div>
                </div>
                <div class="phase-steps-scroll">
                    <div class="phase-steps" id="phaseSteps"></div>
                </div>

                @if($project->current_phase === 'planning')
                @php
                    $subPhaseLabels  = [
                        'shop_drawing' => 'Shop Drawing / Tank Design',
                        'quotation'    => 'Project Quotation',
                        'payment'      => 'Payment',
                    ];
                    $subPhaseKeys    = array_keys($subPhaseLabels);
                    $currentSubIndex = array_search($project->current_sub_phase, $subPhaseKeys);
                @endphp
                <div style="margin-top:14px;margin-bottom:6px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);">
                    {{ ucfirst(str_replace('_', ' ', $project->current_phase)) }} Phase &nbsp;·&nbsp; Sub-Phases
                </div>
                <div class="phase-steps-scroll">
                <div class="phase-steps sub-phase-steps" style="margin-top:0;">
                    @foreach($subPhaseLabels as $key => $label)
                        @php
                            $idx = array_search($key, $subPhaseKeys);
                            if ($currentSubIndex === false) {
                                $subStatus = 'done';
                            } elseif ($idx < $currentSubIndex) {
                                $subStatus = 'done';
                            } elseif ($idx === $currentSubIndex) {
                                $subStatus = 'active';
                            } else {
                                $subStatus = 'pending';
                            }
                            $subIcon = $subStatus === 'done' ? 'check' : ($subStatus === 'active' ? 'loader' : 'circle');
                        @endphp
                        <div class="phase-step {{ $subStatus }}">
                            <div class="phase-step-box">
                                <div class="phase-step-icon"><i data-lucide="{{ $subIcon }}"></i></div>
                                <div class="phase-step-label">{{ $label }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                </div>{{-- end phase-steps-scroll --}}
                @endif
            </div>

            <!-- Side by Side: Add Update + Progress History -->
            <div class="pv-update-grid">

                <!-- LEFT: Add Progress Update -->
                <div class="pv-card" style="margin-top:0;display:flex;flex-direction:column;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
                        <i data-lucide="clipboard-list" style="width:18px;height:18px;color:var(--accent);"></i>
                        <h3 class="pv-card-title" style="margin-bottom:0;">
                            Add Progress Update
                            <span style="font-size:12px;font-weight:600;color:var(--accent);margin-left:6px;">
                                — {{ ucfirst(str_replace('_', ' ', $project->current_phase)) }} Phase
                            </span>
                        </h3>
                    </div>

                    @php
                        $payment        = $project->getPaymentRecord();
                        $isBigProject   = $payment && $payment->payment_term_type === 'big_project';
                        $shopDrawing    = $project->phaseData('planning.shop_drawing', []);
                        $sdStatus       = $shopDrawing['status'] ?? 'not_submitted';
                        $subPhase       = $project->current_sub_phase ?? 'shop_drawing';
                        $paymentUrl     = $payment ? route('admin.payments.show', $payment->id) : route('admin.payments');
                        $sparsePhase    = ($project->current_phase === 'planning' && $subPhase === 'payment' && $project->isPaymentStageSettled('down_payment'))
                                          || $project->current_phase === 'procurement'
                                          || $project->current_phase === 'matl_prep'
                                          || $project->current_phase === 'inspection';
                    @endphp

                    @if($project->current_phase === 'delivery' && $project->progress === 100)
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:16px;padding:30px 20px;">
                        <div style="width:72px;height:72px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="check-circle-2" style="width:38px;height:38px;color:#16a34a;"></i>
                        </div>
                        <div>
                            <p style="font-size:17px;font-weight:900;color:var(--dark);margin-bottom:6px;">Project Fully Completed</p>
                            <p style="font-size:13.5px;color:var(--muted);max-width:320px;line-height:1.6;">All phases have been finished. No further progress updates are required.</p>
                        </div>
                    </div>
                    @else
                    <div style="flex:1;display:flex;flex-direction:column;">

                    @php
                        $hideRequestBtn = $project->current_phase === 'planning'
                            || ($project->current_phase === 'fabrication' && $isBigProject && !$project->isPaymentStageSettled('progress_payment'));
                    @endphp
                    @unless($hideRequestBtn)
                    <div style="display:flex;justify-content:flex-end;margin-bottom:14px;">
                        <button type="button" class="cancel-btn" id="openRequestModal" style="font-size:12.5px;padding:8px 14px;">
                            <i data-lucide="send"></i>
                            Request from Employee
                        </button>
                    </div>
                    @endunless

                    @if($project->current_phase === 'planning' && $subPhase === 'shop_drawing' && $sdStatus === 'pending_approval')
                        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:16px;padding:30px 20px;">
                            <div style="width:64px;height:64px;border-radius:50%;background:#EAF0FF;display:flex;align-items:center;justify-content:center;">
                                <i data-lucide="clock" style="width:32px;height:32px;color:#1e40af;"></i>
                            </div>
                            <div>
                                <p style="font-size:16px;font-weight:800;color:var(--dark);margin-bottom:6px;">Awaiting Client Review</p>
                                <p style="font-size:13.5px;color:var(--muted);max-width:320px;line-height:1.6;">Shop drawing and tank design have been sent to the client and are awaiting their review.</p>
                            </div>
                        </div>
                    @elseif($project->current_phase === 'planning' && $subPhase === 'payment' && !$project->isPaymentStageSettled('down_payment'))
                        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:16px;padding:30px 20px;">
                            <div style="width:64px;height:64px;border-radius:50%;background:#FFF3D6;display:flex;align-items:center;justify-content:center;">
                                <i data-lucide="alert-triangle" style="width:32px;height:32px;color:#b45309;"></i>
                            </div>
                            <div>
                                <p style="font-size:16px;font-weight:800;color:var(--dark);margin-bottom:6px;">Payment Pending</p>
                                <p style="font-size:13.5px;color:var(--muted);max-width:320px;line-height:1.6;">Payment must be settled in the Payment Module before proceeding. Waiting for payment settlement.</p>
                            </div>
                            <a href="{{ $paymentUrl }}" class="save-btn" style="display:inline-flex;align-items:center;gap:8px;font-size:13.5px;padding:11px 22px;text-decoration:none;">
                                <i data-lucide="credit-card"></i>
                                {{ $payment ? 'View Payment Status' : 'Set Up Payment' }}
                            </a>
                        </div>
                    @elseif($project->current_phase === 'fabrication' && $isBigProject && !$project->isPaymentStageSettled('progress_payment'))
                        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:16px;padding:30px 20px;">
                            <div style="width:64px;height:64px;border-radius:50%;background:#FFF3D6;display:flex;align-items:center;justify-content:center;">
                                <i data-lucide="alert-triangle" style="width:32px;height:32px;color:#b45309;"></i>
                            </div>
                            <div>
                                <p style="font-size:16px;font-weight:800;color:var(--dark);margin-bottom:6px;">Progress Payment Pending</p>
                                <p style="font-size:13.5px;color:var(--muted);max-width:320px;line-height:1.6;">30% Progress Payment must be settled before proceeding. Waiting for progress payment settlement.</p>
                            </div>
                            <a href="{{ $paymentUrl }}" class="save-btn" style="display:inline-flex;align-items:center;gap:8px;font-size:13.5px;padding:11px 22px;text-decoration:none;">
                                <i data-lucide="credit-card"></i>
                                View Payment Status
                            </a>
                        </div>
                    @elseif($project->current_phase === 'delivery' && !$project->isPaymentStageSettled('final_payment'))
                        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:16px;padding:30px 20px;">
                            <div style="width:64px;height:64px;border-radius:50%;background:#FFF3D6;display:flex;align-items:center;justify-content:center;">
                                <i data-lucide="alert-triangle" style="width:32px;height:32px;color:#b45309;"></i>
                            </div>
                            <div>
                                <p style="font-size:16px;font-weight:800;color:var(--dark);margin-bottom:6px;">Final Payment Pending</p>
                                <p style="font-size:13.5px;color:var(--muted);max-width:320px;line-height:1.6;">{{ $isBigProject ? 'Final 20% Payment' : 'Final 50% Payment' }} must be settled before project delivery. Waiting for final payment settlement.</p>
                            </div>
                            <a href="{{ $paymentUrl }}" class="save-btn" style="display:inline-flex;align-items:center;gap:8px;font-size:13.5px;padding:11px 22px;text-decoration:none;">
                                <i data-lucide="credit-card"></i>
                                View Payment Status
                            </a>
                        </div>
                    @else
                    <form method="POST"
                          action="{{ route('admin.project.add_update', $project->id) }}"
                          enctype="multipart/form-data"
                          id="addUpdateForm"
                          style="flex:1;display:flex;flex-direction:column;">
                        @csrf

                        <div style="flex:1;display:flex;flex-direction:column;{{ $sparsePhase ? 'justify-content:center;gap:18px;' : '' }}">

                        @if($project->current_phase === 'planning' && $subPhase === 'shop_drawing')

                            @if($sdStatus === 'revision_requested')
                            <div class="alert-banner warning">
                                <i data-lucide="rotate-ccw"></i>
                                Client requested a revision: {{ $shopDrawing['revision_notes'] ?? 'No notes provided.' }}
                            </div>
                            @endif

                            <div class="form-group">
                                <label class="log-label">SHOP DRAWING FILES</label>
                                <label class="pv-upload-dropzone" id="shopDrawingDropzone">
                                    <i data-lucide="upload-cloud" style="width:32px;height:32px;color:var(--accent);"></i>
                                    <span style="font-size:14px;font-weight:700;color:var(--text-primary);">Click to upload shop drawings</span>
                                    <span style="font-size:12px;color:var(--muted);">PDF or images, up to 5 files, max 10MB each</span>
                                    <input type="file" name="shop_drawing_files[]" multiple accept=".pdf,image/*"
                                           data-preview-id="shopDrawingPreview" data-dropzone-id="shopDrawingDropzone" data-max="5"
                                           style="display:none;" onchange="previewAdminFiles(this)" required>
                                </label>
                                <div id="shopDrawingPreview" class="pv-file-grid"></div>
                            </div>

                            <div class="form-group" style="margin-top:12px;">
                                <label class="log-label">TANK DESIGN FILES</label>
                                <label class="pv-upload-dropzone" id="tankDesignDropzone">
                                    <i data-lucide="upload-cloud" style="width:32px;height:32px;color:var(--accent);"></i>
                                    <span style="font-size:14px;font-weight:700;color:var(--text-primary);">Click to upload tank design documents</span>
                                    <span style="font-size:12px;color:var(--muted);">PDF or images, up to 5 files, max 10MB each</span>
                                    <input type="file" name="tank_design_files[]" multiple accept=".pdf,image/*"
                                           data-preview-id="tankDesignPreview" data-dropzone-id="tankDesignDropzone" data-max="5"
                                           style="display:none;" onchange="previewAdminFiles(this)" required>
                                </label>
                                <div id="tankDesignPreview" class="pv-file-grid"></div>
                            </div>

                            @php $submitLabel = 'Send to Client for Review'; @endphp

                        @elseif($project->current_phase === 'planning' && $subPhase === 'quotation')

                            <div class="alert-banner info">
                                <i data-lucide="info"></i>
                                Project quotation must be settled before proceeding to the next sub-phase.
                            </div>

                            <div class="form-group">
                                <label class="log-label">QUOTATION FILES *</label>
                                <label class="pv-upload-dropzone" id="quotationDropzone">
                                    <i data-lucide="upload-cloud" style="width:32px;height:32px;color:var(--accent);"></i>
                                    <span style="font-size:14px;font-weight:700;color:var(--text-primary);">Click to upload quotation documents</span>
                                    <span style="font-size:12px;color:var(--muted);">PDF or images, up to 5 files, max 10MB each</span>
                                    <input type="file" name="quotation_files[]" multiple accept=".pdf,image/*"
                                           data-preview-id="quotationPreview" data-dropzone-id="quotationDropzone" data-max="5"
                                           style="display:none;" onchange="previewAdminFiles(this)" required>
                                </label>
                                <div id="quotationPreview" class="pv-file-grid"></div>
                            </div>

                            @php $submitLabel = 'Send Quotation to Client'; @endphp

                        @elseif($project->current_phase === 'planning' && $subPhase === 'payment')

                            <div style="display:flex;flex-direction:column;align-items:center;text-align:center;gap:14px;padding:10px 20px;">
                                <div style="width:64px;height:64px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;">
                                    <i data-lucide="check-circle-2" style="width:32px;height:32px;color:#16a34a;"></i>
                                </div>
                                <div>
                                    <p style="font-size:16px;font-weight:800;color:var(--dark);margin-bottom:6px;">Down Payment Confirmed</p>
                                    <p style="font-size:13.5px;color:var(--muted);max-width:320px;line-height:1.6;">50% down payment has been confirmed. You may now advance this project to the Procurement phase.</p>
                                </div>
                            </div>

                            @php $submitLabel = 'Advance to Procurement'; @endphp

                        @elseif($project->current_phase === 'procurement')

                            <label class="pv-checklist-item pv-checklist-item-lg">
                                <input type="checkbox" name="materials_delivered" value="1" required>
                                <i data-lucide="package-check" class="pv-checklist-icon"></i>
                                <span>Materials Delivered</span>
                            </label>

                            <div class="form-group" style="margin-top:14px;">
                                <label class="log-label">ATTACH FILES <span style="font-weight:500;color:var(--muted);text-transform:none;letter-spacing:0;">(optional — delivery receipts, photos, documents)</span></label>
                                <label class="pv-upload-dropzone" id="procurementDropzone">
                                    <i data-lucide="upload-cloud" style="width:28px;height:28px;color:var(--accent);"></i>
                                    <span style="font-size:13.5px;font-weight:700;color:var(--text-primary);">Click to upload files</span>
                                    <span style="font-size:12px;color:var(--muted);">PDF, images — up to 5 files, max 10MB each</span>
                                    <input type="file" name="progress_photos[]" multiple accept=".pdf,image/*"
                                           data-preview-id="procurementPreview" data-dropzone-id="procurementDropzone" data-max="5"
                                           style="display:none;" onchange="previewAdminFiles(this)">
                                </label>
                                <div id="procurementPreview" class="pv-file-grid"></div>
                            </div>

                            @php $submitLabel = 'Save Progress Update'; @endphp

                        @elseif($project->current_phase === 'matl_prep')

                            <label class="pv-checklist-item pv-checklist-item-lg">
                                <input type="checkbox" name="measuring_completed" value="1" required>
                                <i data-lucide="ruler" class="pv-checklist-icon"></i>
                                <span>Measuring Completed</span>
                            </label>
                            <label class="pv-checklist-item pv-checklist-item-lg">
                                <input type="checkbox" name="marking_completed" value="1" required>
                                <i data-lucide="pencil-ruler" class="pv-checklist-icon"></i>
                                <span>Marking Completed</span>
                            </label>

                            <div class="form-group" style="margin-top:14px;">
                                <label class="log-label">ATTACH FILES <span style="font-weight:500;color:var(--muted);text-transform:none;letter-spacing:0;">(optional — photos, documents)</span></label>
                                <label class="pv-upload-dropzone" id="matlPrepDropzone">
                                    <i data-lucide="upload-cloud" style="width:28px;height:28px;color:var(--accent);"></i>
                                    <span style="font-size:13.5px;font-weight:700;color:var(--text-primary);">Click to upload files</span>
                                    <span style="font-size:12px;color:var(--muted);">PDF, images — up to 5 files, max 10MB each</span>
                                    <input type="file" name="progress_photos[]" multiple accept=".pdf,image/*"
                                           data-preview-id="matlPrepPreview" data-dropzone-id="matlPrepDropzone" data-max="5"
                                           style="display:none;" onchange="previewAdminFiles(this)">
                                </label>
                                <div id="matlPrepPreview" class="pv-file-grid"></div>
                            </div>

                            @php $submitLabel = 'Save Progress Update'; @endphp

                        @elseif($project->current_phase === 'fabrication')

                            <label class="pv-checklist-item">
                                <input type="checkbox" name="cutting_completed" value="1" required>
                                <span>Cutting Completed</span>
                            </label>
                            <label class="pv-checklist-item" style="margin-top:8px;">
                                <input type="checkbox" name="assembly_completed" value="1" required>
                                <span>Assembly Completed</span>
                            </label>
                            <label class="pv-checklist-item" style="margin-top:8px;">
                                <input type="checkbox" name="welding_completed" value="1" required>
                                <span>Welding Completed</span>
                            </label>

                            <div class="form-group" style="margin-top:12px;">
                                <label class="log-label">PROGRESS FILES
                                    <span style="font-weight:400;color:var(--muted);text-transform:none;">(optional)</span>
                                </label>
                                <label class="pv-upload-dropzone" id="fabricationDropzone">
                                    <i data-lucide="upload-cloud" style="width:32px;height:32px;color:var(--accent);"></i>
                                    <span style="font-size:14px;font-weight:700;color:var(--text-primary);">Click to Upload Files</span>
                                    <span style="font-size:12px;color:var(--muted);">Images or documents — up to 10 files, max 10MB each</span>
                                    <input type="file" name="progress_photos[]" multiple
                                           accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp,.pdf,.doc,.docx,.xls,.xlsx"
                                           data-preview-id="fabricationPreview" data-dropzone-id="fabricationDropzone" data-max="10"
                                           style="display:none;" onchange="previewAdminFiles(this)">
                                </label>
                                <div id="fabricationPreview" class="pv-file-grid"></div>
                            </div>

                            @php $submitLabel = 'Save Progress Update'; @endphp

                        @elseif($project->current_phase === 'inspection')

                            <label class="pv-checklist-item pv-checklist-item-lg">
                                <input type="checkbox" name="pressure_test_passed" value="1" required>
                                <i data-lucide="gauge" class="pv-checklist-icon"></i>
                                <span>Pressure Test Passed</span>
                            </label>
                            <label class="pv-checklist-item pv-checklist-item-lg">
                                <input type="checkbox" name="soap_testing_passed" value="1" required>
                                <i data-lucide="droplets" class="pv-checklist-icon"></i>
                                <span>Soap Testing Passed</span>
                            </label>

                            <div class="form-group" style="margin-top:14px;">
                                <label class="log-label">ATTACH FILES <span style="font-weight:500;color:var(--muted);text-transform:none;letter-spacing:0;">(optional — test results, photos)</span></label>
                                <label class="pv-upload-dropzone" id="inspectionDropzone">
                                    <i data-lucide="upload-cloud" style="width:28px;height:28px;color:var(--accent);"></i>
                                    <span style="font-size:13.5px;font-weight:700;color:var(--text-primary);">Click to upload files</span>
                                    <span style="font-size:12px;color:var(--muted);">PDF, images — up to 5 files, max 10MB each</span>
                                    <input type="file" name="progress_photos[]" multiple accept=".pdf,image/*"
                                           data-preview-id="inspectionPreview" data-dropzone-id="inspectionDropzone" data-max="5"
                                           style="display:none;" onchange="previewAdminFiles(this)">
                                </label>
                                <div id="inspectionPreview" class="pv-file-grid"></div>
                            </div>

                            @php $submitLabel = 'Save Progress Update'; @endphp

                        @elseif($project->current_phase === 'painting')

                            <div class="form-group">
                                <label class="log-label">PROJECT PHOTOS *</label>
                                <label class="pv-upload-dropzone" id="paintingDropzone">
                                    <i data-lucide="upload-cloud" style="width:32px;height:32px;color:var(--accent);"></i>
                                    <span style="font-size:14px;font-weight:700;color:var(--text-primary);">Click to upload photos</span>
                                    <span style="font-size:12px;color:var(--muted);">Required — up to 5 photos, JPG/PNG, max 5MB each</span>
                                    <input type="file" name="photos[]" multiple accept="image/*"
                                           data-preview-id="paintingPreview" data-dropzone-id="paintingDropzone" data-max="5"
                                           style="display:none;" onchange="previewAdminFiles(this)" required>
                                </label>
                                <div id="paintingPreview" class="pv-file-grid"></div>
                            </div>

                            <div class="form-group" style="margin-top:14px;flex:1;display:flex;flex-direction:column;">
                                <label class="log-label">REMARKS
                                    <span style="font-weight:400;color:var(--muted);text-transform:none;">(optional)</span>
                                </label>
                                <textarea name="remarks" class="log-textarea" style="flex:1;min-height:120px;resize:vertical;"
                                          placeholder="Any notes about the painting work..."></textarea>
                            </div>

                            @php $submitLabel = 'Save Progress Update'; @endphp

                        @elseif($project->current_phase === 'completion')

                            <div class="form-group">
                                <label class="log-label">FINAL OUTPUT PHOTOS *</label>
                                <label class="pv-upload-dropzone" id="completionDropzone">
                                    <i data-lucide="upload-cloud" style="width:32px;height:32px;color:var(--accent);"></i>
                                    <span style="font-size:14px;font-weight:700;color:var(--text-primary);">Click to upload final output photos</span>
                                    <span style="font-size:12px;color:var(--muted);">Required — up to 5 photos, JPG/PNG, max 5MB each</span>
                                    <input type="file" name="photos[]" multiple accept="image/*"
                                           data-preview-id="completionPreview" data-dropzone-id="completionDropzone" data-max="5"
                                           style="display:none;" onchange="previewAdminFiles(this)" required>
                                </label>
                                <div id="completionPreview" class="pv-file-grid"></div>
                            </div>

                            <div class="form-group" style="margin-top:14px;flex:1;display:flex;flex-direction:column;">
                                <label class="log-label">COMPLETION NOTES
                                    <span style="font-weight:400;color:var(--muted);text-transform:none;">(optional)</span>
                                </label>
                                <textarea name="completion_notes" class="log-textarea" style="flex:1;min-height:120px;resize:vertical;"
                                          placeholder="Any notes about the final output..."></textarea>
                            </div>

                            @php $submitLabel = 'Save Progress Update'; @endphp

                        @elseif($project->current_phase === 'delivery')

                            <div class="form-group">
                                <label class="log-label">DELIVERY PHOTOS *</label>
                                <label class="pv-upload-dropzone" id="deliveryDropzone">
                                    <i data-lucide="upload-cloud" style="width:32px;height:32px;color:var(--accent);"></i>
                                    <span style="font-size:14px;font-weight:700;color:var(--text-primary);">Click to upload delivery photos</span>
                                    <span style="font-size:12px;color:var(--muted);">Required — up to 5 photos, JPG/PNG, max 5MB each</span>
                                    <input type="file" name="photos[]" multiple accept="image/*"
                                           data-preview-id="deliveryPreview" data-dropzone-id="deliveryDropzone" data-max="5"
                                           style="display:none;" onchange="previewAdminFiles(this)" required>
                                </label>
                                <div id="deliveryPreview" class="pv-file-grid"></div>
                            </div>

                            <div class="form-group" style="margin-top:14px;flex:1;display:flex;flex-direction:column;">
                                <label class="log-label">DELIVERY NOTES
                                    <span style="font-weight:400;color:var(--muted);text-transform:none;">(optional)</span>
                                </label>
                                <textarea name="delivery_notes" class="log-textarea" style="flex:1;min-height:120px;resize:vertical;"
                                          placeholder="Any notes about the delivery..."></textarea>
                            </div>

                            @php $submitLabel = 'Save Progress Update'; @endphp

                        @endif

                        @if($errors->any())
                        <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px;margin-top:12px;color:#dc2626;font-size:13px;">
                            @foreach($errors->all() as $error)
                                <div>• {{ $error }}</div>
                            @endforeach
                        </div>
                        @endif

                        </div>

                        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:18px;padding-top:18px;border-top:1px solid var(--border);">
                            <button type="submit" class="save-btn" style="font-size:13.5px;padding:11px 24px;">
                                <i data-lucide="save"></i>
                                {{ $submitLabel ?? 'Save Progress Update' }}
                            </button>
                        </div>
                    </form>
                    @endif
                    </div>
                    @endif
                </div>

                <!-- RIGHT: Progress History -->
                <div class="pv-card" style="margin-top:0;display:flex;flex-direction:column;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <i data-lucide="history" style="width:18px;height:18px;color:var(--accent);"></i>
                            <h3 class="pv-card-title" style="margin-bottom:0;">Progress History</h3>
                        </div>
                        @php $allUpdates = $updates->sortByDesc('created_at'); @endphp
                        <span style="font-size:12px;color:var(--muted);font-weight:600;">
                            {{ $allUpdates->count() }} {{ Str::plural('entry', $allUpdates->count()) }}
                        </span>
                    </div>

                    <div style="flex:1;overflow-y:auto;max-height:520px;display:flex;flex-direction:column;padding-right:4px;">

                        @php
                            $phaseIcons = [
                                'planning'    => 'clipboard-list',
                                'procurement' => 'package-check',
                                'matl_prep'   => 'ruler',
                                'fabrication' => 'hammer',
                                'inspection'  => 'gauge',
                                'painting'    => 'paint-bucket',
                                'completion'  => 'check-circle-2',
                                'delivery'    => 'truck',
                            ];
                            $imageExtRegex = '/\.(jpe?g|png|gif|webp|bmp)(\?.*)?$/i';
                        @endphp

                        @forelse($allUpdates as $update)
                        @php
                            $isPending         = $update->status === 'pending_review';
                            $isPendingApproval = $update->status === 'pending_approval';
                            $isRevision        = $update->status === 'needs_revision';
                            $isSuperseded      = $update->status === 'superseded';
                            $isEmployee   = $update->type === 'employee_submission';

                            if ($isPending) {
                                $cardStyle = 'background:#fffbeb;border:1.5px solid #f59e0b;';
                                $iconBg = '#fef3c7'; $iconColor = '#d97706';
                            } elseif ($isRevision) {
                                $cardStyle = 'background:#fff7ed;border:1.5px solid #fb923c;';
                                $iconBg = '#ffedd5'; $iconColor = '#ea580c';
                            } elseif ($isSuperseded) {
                                $cardStyle = 'background:var(--surface-2);border:1px dashed var(--border);opacity:0.6;';
                                $iconBg = 'var(--white)'; $iconColor = 'var(--muted)';
                            } else {
                                $cardStyle = 'background:var(--surface-2);border:1px solid var(--border);';
                                $iconBg = '#dcfce7'; $iconColor = '#16a34a';
                            }

                            $phaseIcon = $phaseIcons[$update->phase] ?? 'file-text';
                        @endphp

                        <div class="pv-history-item" data-update-id="{{ $update->id }}">
                            <div class="pv-history-rail">
                                <div class="pv-history-icon" style="background:{{ $iconBg }};">
                                    <i data-lucide="{{ $phaseIcon }}" style="color:{{ $iconColor }};"></i>
                                </div>
                                <div class="pv-history-line"></div>
                            </div>

                            <div class="pv-history-card" style="{{ $cardStyle }}" onclick="openUpdateModal({{ $update->id }})">

                                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:6px;">
                                    <span style="font-size:13.5px;font-weight:700;color:var(--text-primary);">
                                        {{ ucfirst(str_replace('_', ' ', $update->phase)) }} Phase
                                        @if($update->update_label === 'revision')
                                        <span style="font-size:10.5px;background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:20px;margin-left:4px;font-weight:700;">
                                            Revision Submission
                                        </span>
                                        @endif
                                        <span class="pv-new-badge">New</span>
                                    </span>
                                    <span style="font-size:11.5px;color:var(--muted);font-weight:600;white-space:nowrap;">
                                        {{ $update->date_of_work->format('M d, Y') }}
                                    </span>
                                </div>

                                <div class="pv-history-meta">
                                    <i data-lucide="{{ $isEmployee ? 'user' : 'shield-check' }}" style="width:12px;height:12px;"></i>
                                    <span>{{ $isEmployee ? $update->submitted_by_name : 'Admin' }}</span>

                                    @if($update->percentage)
                                    <span class="dot"></span>
                                    <span class="pv-history-percentage">
                                        <i data-lucide="trending-up" style="width:10px;height:10px;"></i>
                                        {{ $update->percentage }}%
                                    </span>
                                    @endif

                                    <span class="dot"></span>

                                    @if($isPending)
                                    <span style="display:inline-flex;align-items:center;gap:4px;color:#d97706;">
                                        <i data-lucide="clock" style="width:11px;height:11px;"></i> Pending Review
                                    </span>
                                    @elseif($isRevision)
                                    <span style="display:inline-flex;align-items:center;gap:4px;color:#ea580c;">
                                        <i data-lucide="rotate-ccw" style="width:11px;height:11px;"></i> Needs Revision
                                    </span>
                                    @elseif($isSuperseded)
                                    <span style="display:inline-flex;align-items:center;gap:4px;">
                                        <i data-lucide="copy" style="width:11px;height:11px;"></i> Superseded
                                    </span>
                                    @elseif($isPendingApproval)
                                    <span style="display:inline-flex;align-items:center;gap:4px;color:#b45309;background:#fff3cd;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;box-shadow:0 0 0 1px rgba(180,83,9,.18),0 2px 8px rgba(180,83,9,.12);">
                                        <i data-lucide="clock" style="width:11px;height:11px;"></i> Pending Client Approval
                                    </span>
                                    @else
                                    <span style="display:inline-flex;align-items:center;gap:4px;color:#16a34a;">
                                        <i data-lucide="check" style="width:11px;height:11px;"></i> Approved
                                    </span>
                                    @endif
                                </div>

                                <div style="font-size:13px;color:var(--text-secondary);line-height:1.5;">
                                    {{ Str::limit($update->work_done, 100) }}
                                </div>

                                @if($update->photos && count($update->photos) > 0)
                                <div class="pv-history-attachments">
                                    @foreach(array_slice($update->photos, 0, 4) as $photo)
                                        @if(preg_match($imageExtRegex, $photo))
                                        <img src="{{ $photo }}" class="pv-history-thumb">
                                        @else
                                        <div class="pv-history-thumb-doc">
                                            <i data-lucide="file-text"></i>
                                        </div>
                                        @endif
                                    @endforeach
                                    @if(count($update->photos) > 4)
                                    <div class="pv-history-thumb-more">+{{ count($update->photos) - 4 }}</div>
                                    @endif
                                </div>
                                @endif

                                <div style="display:flex;align-items:center;gap:4px;margin-top:10px;font-size:11.5px;font-weight:700;color:var(--accent);">
                                    View Details <i data-lucide="arrow-right" style="width:12px;height:12px;"></i>
                                </div>

                            </div>
                        </div>

                        @empty
                        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:16px;padding:30px 20px;">
                            <div style="width:64px;height:64px;border-radius:50%;background:var(--surface-2);display:flex;align-items:center;justify-content:center;">
                                <i data-lucide="history" style="width:32px;height:32px;color:var(--muted);"></i>
                            </div>
                            <div>
                                <p style="font-size:15px;font-weight:800;color:var(--dark);margin-bottom:6px;">No Updates Yet</p>
                                <p style="font-size:13px;color:var(--muted);max-width:280px;line-height:1.6;">Save a progress update to start building this project's history.</p>
                            </div>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Project Info Card -->
            <div class="pv-card" style="margin-top:20px;">
                <h3 class="pv-card-title">Project Information</h3>

                <div class="pi-section-label"><i data-lucide="user" style="width:13px;height:13px;"></i> Client Details</div>
                <div class="project-detail-grid" style="margin-top:10px;margin-bottom:20px;">
                    <div class="project-detail-box"><span>Client</span><strong>{{ $project->client }}</strong></div>
                    <div class="project-detail-box"><span>Contact Number</span><strong>{{ $clientContact ?? '—' }}</strong></div>
                    <div class="project-detail-box"><span>Email</span><strong>{{ $clientEmail ?? '—' }}</strong></div>
                    <div class="project-detail-box" style="grid-column:span 3;"><span>Address</span><strong>{{ $clientAddress ?? '—' }}</strong></div>
                </div>

                <div class="pi-section-label">
                    <i data-lucide="package" style="width:13px;height:13px;"></i> Tank Specifications
                    <span style="font-size:11px;font-weight:600;color:var(--muted-light);">({{ $project->tankItems->isNotEmpty() ? $project->tankItems->count() : 1 }} tank{{ $project->tankItems->count() !== 1 ? 's' : '' }})</span>
                </div>
                @if($project->tankItems->isNotEmpty())
                <div style="display:flex;flex-direction:column;gap:10px;margin-top:10px;margin-bottom:20px;">
                    @foreach($project->tankItems as $i => $ti)
                    <div class="pi-tank-row">
                        <div class="pi-tank-grid">
                            <div class="project-detail-box"><span>Tank Type</span><strong>{{ $ti->tank_type }}</strong></div>
                            <div class="project-detail-box"><span>Shape</span><strong>{{ $ti->shape ?? '—' }}</strong></div>
                            <div class="project-detail-box"><span>Capacity</span><strong>{{ $ti->capacity ?? '—' }}</strong></div>
                            <div class="project-detail-box"><span>Dimensions</span><strong>{{ $ti->dimensions ?? '—' }}</strong></div>
                            <div class="project-detail-box"><span>Quantity</span><strong>{{ $ti->quantity }}</strong></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="project-detail-grid" style="margin-top:10px;margin-bottom:20px;">
                    <div class="project-detail-box"><span>Tank Type</span><strong>{{ $project->tank_type }}</strong></div>
                    <div class="project-detail-box"><span>Capacity</span><strong>{{ $project->capacity }}</strong></div>
                    <div class="project-detail-box"><span>Dimensions</span><strong>{{ $project->dimensions ?? '—' }}</strong></div>
                </div>
                @endif

            </div>

            <!-- Revolving Fund Summary — only shown when fund has been used -->
            @if($fundReleased > 0 || $fundReplenished > 0)
            <div class="pv-card" style="margin-top:20px;">
                <h3 class="pv-card-title">Revolving Fund Summary</h3>
                <div class="project-detail-grid" style="margin-bottom:16px;">
                    <div class="project-detail-box"><span>Total Released</span><strong>₱{{ number_format($fundReleased, 2) }}</strong></div>
                    <div class="project-detail-box"><span>Total Replenished</span><strong>₱{{ number_format($fundReplenished, 2) }}</strong></div>
                    <div class="project-detail-box"><span>Outstanding Balance</span><strong>₱{{ number_format($fundOutstanding, 2) }}</strong></div>
                </div>

                @if($fundHistory->isEmpty())
                <p style="color:var(--muted);font-size:13.5px;">No revolving fund activity for this project yet.</p>
                @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Purpose</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fundHistory as $tx)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($tx->date)->format('M d, Y') }}</td>
                                <td>
                                    <span class="status-badge {{ $tx->type === 'release' ? 'shortage' : 'completed' }}">
                                        {{ $tx->type === 'release' ? 'Release' : 'Replenishment' }}
                                    </span>
                                </td>
                                <td>
                                    <strong style="color:{{ $tx->type === 'release' ? 'var(--danger)' : 'var(--success)' }};">
                                        {{ $tx->type === 'release' ? '-' : '+' }}₱{{ number_format($tx->amount, 2) }}
                                    </strong>
                                </td>
                                <td>{{ $tx->purpose ?? $tx->description ?? '—' }}</td>
                                <td>
                                    <span class="status-badge {{ $tx->status === 'Completed' ? 'completed' : 'pending' }}">
                                        {{ $tx->status ?? '—' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            @endif

        </main>
    </div>

    <!-- ===== REQUEST UPDATE MODAL ===== -->
    <div class="modal-overlay" id="requestUpdateModal">
        <div class="modal-card" style="max-width:520px;">
            <div class="modal-header">
                <div>
                    <h2>Request Progress Update</h2>
                    <p>Send a request to employees for a field update.</p>
                </div>
                <button class="modal-close" type="button" id="closeRequestModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.project.request_update', $project->id) }}">
                @csrf
                <div class="form-group">
                    <label>Message to Employees
                        <span style="font-weight:400;color:var(--muted);">(optional)</span>
                    </label>
                    <textarea name="message" class="log-textarea" rows="4"
                              placeholder="e.g. Please submit photos of the current welding progress..."></textarea>
                </div>
                @if($openRequest)
                <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px;margin-top:14px;color:#dc2626;font-size:13px;">
                    ⚠ There is already an open request for this project.
                </div>
                @endif
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelRequestModal">Cancel</button>
                    @if(!$openRequest)
                    <button type="submit" class="save-btn">
                        <i data-lucide="send"></i>
                        Send Request
                    </button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- ===== UPDATE DETAIL MODAL ===== -->
    <div class="modal-overlay" id="updateDetailModal">
        <div class="modal-card" style="max-width:680px;max-height:90vh;overflow-y:auto;">
            <div class="modal-header">
                <div>
                    <h2 id="modalUpdateTitle">Update Details</h2>
                    <p id="modalUpdateSubtitle">Submitted progress update</p>
                </div>
                <button class="modal-close" type="button" onclick="closeUpdateModal()">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:18px;">
                <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:12px;">
                    <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:4px;">Submitted By</div>
                    <div style="font-size:14px;font-weight:700;color:var(--text-primary);" id="modalSubmittedBy">—</div>
                </div>
                <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:12px;">
                    <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:4px;">Date of Work</div>
                    <div style="font-size:14px;font-weight:700;color:var(--text-primary);" id="modalDateOfWork">—</div>
                </div>
                <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:12px;">
                    <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:4px;">Phase</div>
                    <div style="font-size:14px;font-weight:700;color:var(--text-primary);" id="modalPhase">—</div>
                </div>
                <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:12px;">
                    <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:4px;">Update Type</div>
                    <div style="font-size:14px;font-weight:700;color:var(--text-primary);" id="modalType">—</div>
                </div>
            </div>

            <div id="modalStatusBadge" style="margin-bottom:16px;"></div>

            <div id="modalRevisionSection" style="display:none;margin-bottom:16px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:14px;">
                <div style="font-size:11.5px;font-weight:700;color:#9a3412;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">
                    Admin Revision Feedback
                </div>
                <div id="modalRevisionFeedback" style="font-size:13.5px;color:#7c2d12;white-space:pre-wrap;line-height:1.6;"></div>
            </div>

            <div id="modalWorkDoneSection" style="margin-bottom:16px;display:none;">
                <div style="font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">WORK DONE</div>
                <div id="modalWorkDone"
                     style="background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:14px;font-size:13.5px;color:var(--text-primary);line-height:1.6;white-space:pre-wrap;"></div>
            </div>

            <div id="modalIssuesSection" style="margin-bottom:16px;display:none;">
                <div style="font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">ISSUES / OBSERVATIONS</div>
                <div id="modalIssues"
                     style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:14px;font-size:13.5px;color:#92400e;line-height:1.6;white-space:pre-wrap;"></div>
            </div>

            <div id="modalPhotosSection" style="margin-bottom:20px;display:none;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <div style="font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.05em;">ATTACHMENTS</div>
                    <span id="modalPhotoCount" style="font-size:11px;color:var(--muted);font-weight:600;"></span>
                </div>
                <div id="modalPhotos" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;"></div>
            </div>

            <div id="modalActions" style="border-top:1px solid var(--border);padding-top:16px;"></div>
        </div>
    </div>

    <!-- ===== REVISION MODAL ===== -->
    <div class="modal-overlay" id="revisionModal">
        <div class="modal-card" style="max-width:500px;">
            <div class="modal-header">
                <div>
                    <h2>Request Revision</h2>
                    <p>Provide feedback for the employee to resubmit.</p>
                </div>
                <button class="modal-close" type="button" onclick="closeRevisionModal()">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" id="revisionForm">
                @csrf
                <div class="form-group">
                    <label style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:8px;">
                        Revision Comments *
                    </label>
                    <textarea name="revision_comment" id="revisionComment" class="log-textarea" rows="4"
                              placeholder="Describe what needs to be corrected or added..." required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeRevisionModal()">Cancel</button>
                    <button type="submit" class="save-btn" style="background:#f59e0b;">
                        <i data-lucide="rotate-ccw"></i>
                        Send for Revision
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== IMAGE LIGHTBOX ===== -->
    <div class="pv-lightbox-overlay" id="pvLightboxOverlay" onclick="closeFileLightbox()">
        <button type="button" class="pv-lightbox-close" onclick="closeFileLightbox()">
            <i data-lucide="x" style="width:20px;height:20px;"></i>
        </button>
        <img id="pvLightboxImg" src="" alt="Preview" onclick="event.stopPropagation()">
    </div>

    @php
        $progress     = $project->progress;
        $status       = strtolower($project->status);
        $duration     = $project->duration ?? 'N/A';
        $currentPhase = $project->current_phase;

        $updatesData = $updates->map(function($u) {
            // Ensure photos are full URLs, not raw storage paths
            $photos = collect($u->photos ?? [])->map(function($photo) {
                // If already a full URL (starts with http), use as-is
                // Otherwise it's a raw path — return as-is since your
                // storage service already stores full URLs
                return $photo;
            })->values()->toArray();

            return [
                'id'                => $u->id,
                'phase'             => $u->phase,
                'type'              => $u->type,
                'update_label'      => $u->update_label,
                'parent_update_id'  => $u->parent_update_id,
                'revision_feedback' => $u->revision_feedback,
                'status'            => $u->status,
                'work_done'         => $u->work_done,
                'issues'            => $u->issues,
                'photos'            => $photos,
                'date_of_work'      => $u->date_of_work->format('M d, Y'),
                'submitted_at'      => $u->created_at->format('M d, Y h:i A'),
                'submitted_by'      => $u->submittedBy ? $u->submittedBy->full_name : 'Admin',
                'percentage'        => $u->percentage,
            ];
        })->keyBy('id')->toArray();
    @endphp

    <script>
        const PROJECT_PROGRESS      = {{ $progress }};
        const PROJECT_STATUS        = "{{ $status }}";
        const PROJECT_DURATION      = "{{ $duration }}";
        const PROJECT_CURRENT_PHASE = "{{ $currentPhase }}";
        const UPDATES_DATA          = @json($updatesData);
        const APPROVE_BASE_URL      = "{{ url('/admin/project-updates') }}";
        const REVISION_BASE_URL     = "{{ url('/admin/project-updates') }}";
        const PROJECT_ID            = {{ $project->id }};
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        const adminFileMaps = new Map();

        const ADMIN_MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

        function previewAdminFiles(input) {
            const max = parseInt(input.dataset.max || '5', 10);
            let files = adminFileMaps.get(input) || [];
            const rejected = [];
            for (const f of Array.from(input.files)) {
                if (files.length >= max) break;
                if (f.size > ADMIN_MAX_FILE_SIZE) {
                    rejected.push(f.name);
                    continue;
                }
                files.push(f);
            }
            if (files.length > max) files = files.slice(0, max);
            adminFileMaps.set(input, files);
            syncAdminFileInput(input, files);
            renderAdminFilePreviews(input, files);
            if (rejected.length > 0) {
                alert(`The following file(s) exceed the 10MB limit and were not added:\n${rejected.join('\n')}`);
            }
        }

        function removeAdminFile(input, index) {
            const files = adminFileMaps.get(input) || [];
            files.splice(index, 1);
            adminFileMaps.set(input, files);
            syncAdminFileInput(input, files);
            renderAdminFilePreviews(input, files);
        }

        function syncAdminFileInput(input, files) {
            const dt = new DataTransfer();
            files.forEach(f => dt.items.add(f));
            input.files = dt.files;
        }

        function getFileIconName(filename) {
            const ext = (filename.split('.').pop() || '').toLowerCase();
            if (ext === 'pdf' || ext === 'doc' || ext === 'docx') return 'file-text';
            if (ext === 'xls' || ext === 'xlsx') return 'file-spreadsheet';
            return 'file';
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        function openFileLightbox(url) {
            document.getElementById('pvLightboxImg').src = url;
            document.getElementById('pvLightboxOverlay').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeFileLightbox() {
            document.getElementById('pvLightboxOverlay').classList.remove('show');
            document.getElementById('pvLightboxImg').src = '';
            document.body.style.overflow = '';
        }

        function renderAdminFilePreviews(input, files) {
            const preview = document.getElementById(input.dataset.previewId);
            if (!preview) return;
            const max = parseInt(input.dataset.max || '5', 10);
            const dropzone = input.dataset.dropzoneId ? document.getElementById(input.dataset.dropzoneId) : null;

            preview.innerHTML = '';

            if (files.length === 0) {
                preview.style.display = 'none';
                if (dropzone) dropzone.style.display = '';
                return;
            }

            if (dropzone) dropzone.style.display = 'none';
            preview.style.display = 'grid';

            files.forEach((file, i) => {
                const isImage = file.type.startsWith('image/');
                const tile = document.createElement('div');
                tile.className = 'pv-file-tile';

                if (isImage) {
                    const url = URL.createObjectURL(file);
                    const img = document.createElement('img');
                    img.src = url;
                    img.alt = file.name;
                    img.title = 'Click to enlarge';
                    img.onclick = () => openFileLightbox(url);
                    tile.appendChild(img);
                } else {
                    const icon = document.createElement('i');
                    icon.setAttribute('data-lucide', getFileIconName(file.name));
                    icon.className = 'pv-file-icon';
                    tile.appendChild(icon);

                    const name = document.createElement('div');
                    name.className = 'pv-file-name';
                    name.textContent = file.name;
                    tile.appendChild(name);

                    const size = document.createElement('div');
                    size.className = 'pv-file-size';
                    size.textContent = formatFileSize(file.size);
                    tile.appendChild(size);
                }

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'pv-file-remove';
                removeBtn.title = 'Remove';
                removeBtn.innerHTML = '<i data-lucide="x" style="width:12px;height:12px;"></i>';
                removeBtn.onclick = () => removeAdminFile(input, i);
                tile.appendChild(removeBtn);

                preview.appendChild(tile);
            });

            if (files.length < max) {
                const addTile = document.createElement('div');
                addTile.className = 'pv-file-add-tile';
                addTile.innerHTML = '<i data-lucide="plus" style="width:20px;height:20px;"></i><span>Add More</span>';
                addTile.onclick = () => input.click();
                preview.appendChild(addTile);
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        const openRequestModalBtn = document.getElementById('openRequestModal');
        if (openRequestModalBtn) {
            openRequestModalBtn.addEventListener('click', () => {
                document.getElementById('requestUpdateModal').classList.add('show');
                document.body.style.overflow = 'hidden';
            });
        }
        document.getElementById('closeRequestModal').addEventListener('click', () => {
            document.getElementById('requestUpdateModal').classList.remove('show');
            document.body.style.overflow = '';
        });
        document.getElementById('cancelRequestModal').addEventListener('click', () => {
            document.getElementById('requestUpdateModal').classList.remove('show');
            document.body.style.overflow = '';
        });
        document.getElementById('requestUpdateModal').addEventListener('click', function(e) {
            if (e.target === this) { this.classList.remove('show'); document.body.style.overflow = ''; }
        });

        function openUpdateModal(updateId) {
            markUpdateSeen(updateId);

            const u = UPDATES_DATA[updateId];
            if (!u) return;

            const isPending    = u.status === 'pending_review';
            const isNeedsRevision = u.status === 'needs_revision';
            const isEmployee   = u.type === 'employee_submission';

            document.getElementById('modalUpdateTitle').textContent    = ucPhase(u.phase) + ' Phase Update';
            document.getElementById('modalUpdateSubtitle').textContent = u.submitted_at;
            document.getElementById('modalSubmittedBy').textContent    = u.submitted_by;
            document.getElementById('modalDateOfWork').textContent     = u.date_of_work;
            document.getElementById('modalPhase').textContent          = ucPhase(u.phase);
            document.getElementById('modalType').textContent           = isEmployee ? 'Employee Submission' : 'Admin Update';

            let badgeHtml = '';
            if (isPending) {
                badgeHtml = `<span style="display:inline-flex;align-items:center;gap:6px;background:#fffbeb;border:1.5px solid #f59e0b;color:#92400e;font-size:12px;font-weight:700;padding:5px 14px;border-radius:20px;">
                    <span style="width:8px;height:8px;background:#f59e0b;border-radius:50%;display:inline-block;"></span>
                    Pending Review — Requires Admin Approval
                </span>`;
            } else if (isNeedsRevision) {
                badgeHtml = `<span style="display:inline-flex;align-items:center;gap:6px;background:#fff7ed;border:1.5px solid #fb923c;color:#9a3412;font-size:12px;font-weight:700;padding:5px 14px;border-radius:20px;">
                    <span style="width:8px;height:8px;background:#ea580c;border-radius:50%;display:inline-block;"></span>
                    Needs Revision — Awaiting Employee Resubmission
                </span>`;
            } else if (u.status === 'superseded') {
                badgeHtml = `<span style="display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;border:1.5px solid #cbd5e1;color:#64748b;font-size:12px;font-weight:700;padding:5px 14px;border-radius:20px;">
                    Superseded — Replaced by newer revision
                </span>`;
            } else {
                badgeHtml = `<span style="display:inline-flex;align-items:center;gap:6px;background:#dcfce7;border:1.5px solid #86efac;color:#14532d;font-size:12px;font-weight:700;padding:5px 14px;border-radius:20px;">
                    <span style="width:8px;height:8px;background:#16a34a;border-radius:50%;display:inline-block;"></span>
                    Approved
                </span>`;
            }
            document.getElementById('modalStatusBadge').innerHTML = badgeHtml;

            const workDoneSec = document.getElementById('modalWorkDoneSection');
            if (u.work_done && u.work_done.trim() !== '') {
                workDoneSec.style.display = 'block';
                document.getElementById('modalWorkDone').textContent = u.work_done;
            } else {
                workDoneSec.style.display = 'none';
            }

            const issuesSec = document.getElementById('modalIssuesSection');
            if (u.issues) {
                issuesSec.style.display = 'block';
                document.getElementById('modalIssues').textContent = u.issues;
            } else {
                issuesSec.style.display = 'none';
            }

            const photosSec = document.getElementById('modalPhotosSection');
            if (u.photos && u.photos.length > 0) {
                photosSec.style.display = 'block';
                document.getElementById('modalPhotoCount').textContent = u.photos.length + ' file' + (u.photos.length > 1 ? 's' : '');
                document.getElementById('modalPhotos').innerHTML = u.photos.map((p) => {
                    const filename = decodeURIComponent(p.split('/').pop().split('?')[0]);
                    if (/\.(jpe?g|png|gif|webp|bmp)$/i.test(filename)) {
                        return `<a href="javascript:void(0)" onclick="openFileLightbox('${p}')" title="Click to enlarge"
                            style="display:block;border-radius:8px;overflow:hidden;border:1px solid var(--border);aspect-ratio:4/3;background:var(--surface-2);">
                            <img src="${p}"
                                 style="width:100%;height:100%;object-fit:cover;cursor:zoom-in;transition:transform 0.2s,opacity 0.2s;display:block;"
                                 onmouseover="this.style.transform='scale(1.04)';this.style.opacity='0.88';"
                                 onmouseout="this.style.transform='scale(1)';this.style.opacity='1';"
                                 onerror="this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;font-size:11px;color:var(--muted);padding:8px;text-align:center;\'>Image unavailable</div>';">
                        </a>`;
                    }
                    return `<a href="${p}" target="_blank" class="pv-doc-card" title="Open in new tab">
                        <i data-lucide="${getFileIconName(filename)}" class="pv-doc-icon"></i>
                        <div class="pv-doc-info">
                            <div class="pv-doc-name">${filename}</div>
                            <div class="pv-doc-meta">Click to open / download</div>
                        </div>
                        <i data-lucide="external-link" style="width:16px;height:16px;color:var(--muted);flex-shrink:0;"></i>
                    </a>`;
                }).join('');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            } else {
                photosSec.style.display = 'none';
            }

            // Show revision feedback if present
            const revisionSec = document.getElementById('modalRevisionSection');
            if (u.revision_feedback) {
                revisionSec.style.display = 'block';
                document.getElementById('modalRevisionFeedback').textContent = u.revision_feedback;
            } else {
                revisionSec.style.display = 'none';
            }

            const actionsDiv = document.getElementById('modalActions');
            if (isPending && isEmployee) {
                actionsDiv.innerHTML = `
                    <div style="display:flex;justify-content:flex-end;gap:10px;">
                        <button type="button" onclick="openRevisionModal(${updateId})"
                                style="display:flex;align-items:center;gap:8px;padding:9px 18px;border-radius:8px;border:1.5px solid #f59e0b;background:#fff;color:#92400e;font-size:13px;font-weight:700;cursor:pointer;">
                            <i data-lucide="rotate-ccw" style="width:14px;height:14px;"></i>
                            Request Revision
                        </button>
                        <form method="POST" action="${APPROVE_BASE_URL}/${updateId}/approve">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <button type="submit" class="save-btn" style="display:flex;align-items:center;gap:8px;">
                                <i data-lucide="check-circle" style="width:14px;height:14px;"></i>
                                Approve
                            </button>
                        </form>
                    </div>`;
            } else {
                actionsDiv.innerHTML = `
                    <div style="font-size:13px;color:var(--muted);text-align:right;font-style:italic;">
                        ${isNeedsRevision ? 'Revision feedback sent. Awaiting employee resubmission.' : 'This update has been ' + u.status + '.'}
                    </div>`;
            }

            document.getElementById('updateDetailModal').classList.add('show');
            document.body.style.overflow = 'hidden';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function closeUpdateModal() {
            document.getElementById('updateDetailModal').classList.remove('show');
            document.body.style.overflow = '';
        }

        document.getElementById('updateDetailModal').addEventListener('click', function(e) {
            if (e.target === this) closeUpdateModal();
        });

        function openRevisionModal(updateId) {
            document.getElementById('revisionComment').value = '';
            document.getElementById('revisionForm').action = REVISION_BASE_URL + '/' + updateId + '/request-revision';
            closeUpdateModal();
            document.getElementById('revisionModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeRevisionModal() {
            document.getElementById('revisionModal').classList.remove('show');
            document.body.style.overflow = '';
        }

        document.getElementById('revisionModal').addEventListener('click', function(e) {
            if (e.target === this) closeRevisionModal();
        });

        function ucPhase(phase) {
            return phase.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        }

        /* ── "New" update highlight ──────────────────────────────────────── */
        const SEEN_UPDATES_KEY = 'pv_seen_updates_{{ $project->id }}';
        let seenUpdates = [];
        try { seenUpdates = JSON.parse(localStorage.getItem(SEEN_UPDATES_KEY)) || []; } catch (e) { seenUpdates = []; }

        function markUpdateSeen(updateId) {
            const id = String(updateId);
            const item = document.querySelector('.pv-history-item[data-update-id="' + id + '"]');
            if (item) {
                const card = item.querySelector('.pv-history-card');
                if (card) card.classList.remove('is-new');
            }
            if (!seenUpdates.includes(id)) {
                seenUpdates.push(id);
                localStorage.setItem(SEEN_UPDATES_KEY, JSON.stringify(seenUpdates));
            }
        }

        document.querySelectorAll('.pv-history-item[data-update-id]').forEach(function (item) {
            const id = item.getAttribute('data-update-id');
            if (!seenUpdates.includes(id)) {
                const card = item.querySelector('.pv-history-card');
                if (card) card.classList.add('is-new');
            }
        });
    </script>
</body>
</html>