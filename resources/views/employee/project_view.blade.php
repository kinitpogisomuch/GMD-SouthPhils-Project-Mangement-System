<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project View | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
    <style>
        /* Project Information / Progress History: same fixed height, a bit taller than
           either card's natural content — the taller one no longer stretches the other,
           and each scrolls internally instead of growing the card. */
        .pv-grid-2-card {
            height: 560px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .pv-grid-2-card-scroll {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
        }
        .pv-grid-2-card-scroll-center {
            justify-content: center;
        }
        /* entries keep their natural height — the list scrolls instead of squashing them on top of each other */
        .pv-grid-2-card-scroll > * {
            flex-shrink: 0;
        }
        .pv-grid-2-card-scroll {
            padding-right: 4px;
        }
    </style>
</head>
<body class="page-enter">

    @include('partials.employee.header')

    <main class="admin-content">

            <!-- Breadcrumb -->
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('employee.projects') }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    Projects
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="color:var(--dark);font-weight:700;">{{ $project->name }}</span>
            </div>

            <!-- Page Header -->
            <div class="pv-page-header">
                <div>
                    <h1>{{ $project->name }}</h1>
                    <p>{{ $project->live_client_name }} &nbsp;·&nbsp; {{ $project->tank_type }} &nbsp;·&nbsp; {{ $project->capacity }}</p>
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

            <!-- Phase Tracker (read-only) -->
            <div class="emp-tracker">
                <div class="emp-tracker-header">
                    <div class="emp-tracker-title">
                        <i data-lucide="layers"></i>
                        <span class="tracker-title-text"><span>Fabrication Phase Tracker</span><span class="tracker-title-sep"> &nbsp;·&nbsp; </span><span class="tracker-title-sub">{{ $project->capacity }} {{ $project->tank_type }}</span></span>
                    </div>
                    {{-- The bar only shows on phones (same as the admin tracker); desktop keeps just the badge --}}
                    <div class="tracker-mobile-progress">
                        <div class="tracker-mobile-bar"><span style="width:{{ max(0, min(100, (int) $project->progress)) }}%;"></span></div>
                        <span class="pv-progress-badge" id="empProgressBadge">{{ $project->progress }}%</span>
                    </div>
                </div>
                <div class="emp-phase-steps" id="empPhaseSteps"></div>
            </div>

            {{--
                FORM VISIBILITY RULES (driven by $showRevisionForm / $showProgressForm
                passed from employeeView() — based solely on ProgressRequest.status):

                revision_requested → $showRevisionForm = true  → Revision Form
                open               → $showProgressForm = true  → Progress Form
                completed / null   → both false                → nothing shown
            --}}

            @if($showRevisionForm && $revisionUpdate)
            {{-- ============================================================ --}}
            {{-- REVISION FORM: shown when admin set request to revision_requested --}}
            {{-- ============================================================ --}}
            <div class="emp-pv-card" style="margin-top:20px;background:#fff7ed;border:2px solid #fb923c;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <i data-lucide="alert-triangle" style="color:#ea580c;width:20px;height:20px;flex-shrink:0;"></i>
                    <h3 style="margin:0;font-size:16px;font-weight:800;color:#9a3412;">Revision Required</h3>
                </div>

                <p style="font-size:13.5px;color:#7c2d12;font-weight:600;margin-bottom:14px;">
                    The admin has reviewed your submission and is requesting a revision.
                </p>

                <div style="background:#fff;border:1px solid #fed7aa;border-radius:8px;padding:16px;margin-bottom:20px;">
                    <div style="font-size:11px;font-weight:800;color:#9a3412;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:10px;">
                        Admin Feedback
                    </div>
                    <div style="font-size:13.5px;color:#7c2d12;white-space:pre-wrap;line-height:1.7;font-weight:600;">{{ $revisionUpdate->revision_feedback ?? 'Please review and resubmit your update.' }}</div>
                </div>

                <div style="display:flex;align-items:flex-start;gap:8px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:12px;margin-bottom:20px;">
                    <span style="font-size:16px;">⚠</span>
                    <span style="font-size:13px;color:#92400e;">Please review the feedback and submit the required revisions to continue the approval process.</span>
                </div>

                <div style="border-top:1px solid #fed7aa;padding-top:16px;">
                    <div style="font-size:13.5px;font-weight:800;color:#9a3412;margin-bottom:14px;">Submit Revision</div>

                    <form method="POST"
                          id="revisionUpdateForm"
                          action="{{ route('employee.project.submit_revision', $project->id) }}"
                          enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="parent_update_id" value="{{ $revisionUpdate->id }}">

                        <div class="form-group">
                            <label class="log-label">DATE OF WORK </label>
                            <input type="date" name="date_of_work" class="log-input"
                                   value="{{ old('date_of_work') }}" required>
                        </div>

                        <div class="form-group" style="margin-top:12px;">
                            <label class="log-label">WORK DONE </label>
                            <textarea name="work_done" class="log-textarea" rows="4"
                                      placeholder="Describe the revised/corrected work..." required>{{ old('work_done') }}</textarea>
                        </div>

                        <div class="form-group" style="margin-top:12px;">
                            <label class="log-label">ISSUES / OBSERVATIONS
                                <span style="font-weight:400;color:var(--text-muted);text-transform:none;">(optional)</span>
                            </label>
                            <textarea name="issues" class="log-textarea" rows="3"
                                      placeholder="Any additional notes or observations...">{{ old('issues') }}</textarea>
                        </div>

                        <div class="form-group" style="margin-top:12px;">
                            <label class="log-label">SITE PHOTOS </label>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                                <label for="revisionPhotoFileInput" style="display:flex;flex-direction:column;align-items:center;gap:4px;padding:14px;border:2px dashed #fed7aa;border-radius:8px;cursor:pointer;background:#fff7ed;">
                                    <i data-lucide="upload-cloud" style="width:20px;height:20px;color:#ea580c;"></i>
                                    <span style="font-size:12px;font-weight:600;color:#9a3412;">Upload Photos</span>
                                </label>
                                <label for="revisionPhotoCameraInput" style="display:flex;flex-direction:column;align-items:center;gap:4px;padding:14px;border:2px dashed #fed7aa;border-radius:8px;cursor:pointer;background:#fff7ed;">
                                    <i data-lucide="camera" style="width:20px;height:20px;color:#ea580c;"></i>
                                    <span style="font-size:12px;font-weight:600;color:#9a3412;">Take Photo</span>
                                </label>
                            </div>
                            <span style="display:block;font-size:11.5px;color:#c2410c;margin-top:6px;">Required — JPG, PNG up to 10MB each</span>
                            {{-- Validated in JS on submit instead of native `required` — see note on the
                                 progress-update form's photo input above. --}}
                            <input type="file" name="photos[]" id="revisionPhotoFileInput" multiple accept="image/*"
                                   style="display:none;" onchange="previewRevisionPhotos(this)">
                            <input type="file" id="revisionPhotoCameraInput" accept="image/*" capture="environment"
                                   style="display:none;" onchange="previewRevisionPhotos(this)">
                            <span id="revisionPhotoRequiredErr" style="display:none;color:#dc2626;font-size:11.5px;font-weight:700;margin-top:6px;">Please add at least one site photo.</span>
                            <div id="revisionPhotoPreview" data-receipt-set style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;"></div>
                        </div>

                        @if($errors->any())
                        <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px;margin-top:12px;color:#dc2626;font-size:13px;">
                            @foreach($errors->all() as $error)<div>• {{ $error }}</div>@endforeach
                        </div>
                        @endif

                        <div style="display:flex;justify-content:flex-end;margin-top:16px;">
                            <button type="submit"
                                    style="display:flex;align-items:center;gap:8px;padding:10px 24px;background:#ea580c;color:#fff;border:none;border-radius:8px;font-size:13.5px;font-weight:700;cursor:pointer;">
                                <i data-lucide="send" style="width:14px;height:14px;"></i>
                                Submit Revision
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @elseif($showProgressForm && $openRequest)
            {{-- ============================================================ --}}
            {{-- PROGRESS FORM: shown when admin created a new progress request --}}
            {{-- ============================================================ --}}
            <div class="emp-pv-card" style="margin-top:20px;background:#fffdf5;border:1px solid #fde68a;position:relative;overflow:hidden;">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                    <div style="width:42px;height:42px;border-radius:50%;background:#fde68a;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 10px rgba(251,191,36,0.35);">
                        <i data-lucide="bell" style="width:19px;height:19px;color:#92400e;"></i>
                    </div>
                    <div>
                        <h3 class="emp-pv-card-title" style="margin:0;color:#78350f;">Progress Update Requested</h3>
                        <p style="margin:2px 0 0;font-size:12.5px;color:#92400e;">The admin needs a progress update for this project.</p>
                    </div>
                </div>

                @if($openRequest->message)
                <div style="background:#fff;border:1px solid #fde68a;border-radius:12px;padding:14px 16px;margin-bottom:22px;display:flex;gap:10px;">
                    <i data-lucide="message-square-quote" style="width:16px;height:16px;color:#d97706;flex-shrink:0;margin-top:2px;"></i>
                    <div>
                        <div style="font-size:11px;font-weight:800;color:#92400e;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Admin's Note</div>
                        <div style="font-size:13.5px;color:#78350f;white-space:pre-wrap;line-height:1.6;">{{ $openRequest->message }}</div>
                    </div>
                </div>
                @endif

                <form method="POST"
                      id="progressUpdateForm"
                      action="{{ route('employee.project.submit_update', $openRequest->id) }}"
                      enctype="multipart/form-data">
                    @csrf

                    <div style="background:#fff;border:1px solid #fef3c7;border-radius:14px;padding:18px;">
                        <div class="form-group">
                            <label class="log-label">Date of Work</label>
                            <input type="date" name="date_of_work" class="log-input"
                                   value="{{ old('date_of_work') }}" required>
                        </div>

                        <div style="height:1px;background:#fef3c7;margin:16px 0;"></div>

                        <div class="form-group">
                            <label class="log-label">Work Done
                                <span style="font-weight:400;color:var(--text-muted);text-transform:none;">(optional)</span>
                            </label>
                            <textarea name="work_done" class="log-textarea" rows="4"
                                      placeholder="Describe what was accomplished...">{{ old('work_done') }}</textarea>
                        </div>

                        <div style="height:1px;background:#fef3c7;margin:16px 0;"></div>

                        <div class="form-group">
                            <label class="log-label">Issues / Observations
                                <span style="font-weight:400;color:var(--text-muted);text-transform:none;">(optional)</span>
                            </label>
                            <textarea name="issues" class="log-textarea" rows="3"
                                      placeholder="Any problems, delays, or observations...">{{ old('issues') }}</textarea>
                        </div>

                        <div style="height:1px;background:#fef3c7;margin:16px 0;"></div>

                        <div class="form-group">
                            <label class="log-label">Site Photos
                                <span style="font-weight:400;color:#dc2626;text-transform:none;">(required)</span>
                            </label>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                <label for="photoFileInput" style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:20px 14px;border:2px dashed #fbbf24;border-radius:12px;cursor:pointer;background:#fffbeb;transition:.2s;">
                                    <div style="width:36px;height:36px;border-radius:50%;background:#fde68a;display:flex;align-items:center;justify-content:center;">
                                        <i data-lucide="upload-cloud" style="width:16px;height:16px;color:#92400e;"></i>
                                    </div>
                                    <span style="font-size:12.5px;font-weight:700;color:#78350f;text-align:center;">Upload Photos</span>
                                    <span style="font-size:10.5px;color:#b45309;text-align:center;">From gallery</span>
                                </label>
                                <label for="photoCameraInput" style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:20px 14px;border:2px dashed #fbbf24;border-radius:12px;cursor:pointer;background:#fffbeb;transition:.2s;">
                                    <div style="width:36px;height:36px;border-radius:50%;background:#fde68a;display:flex;align-items:center;justify-content:center;">
                                        <i data-lucide="camera" style="width:16px;height:16px;color:#92400e;"></i>
                                    </div>
                                    <span style="font-size:12.5px;font-weight:700;color:#78350f;text-align:center;">Take Photo</span>
                                    <span style="font-size:10.5px;color:#b45309;text-align:center;">Use camera</span>
                                </label>
                            </div>
                            <span style="display:block;font-size:11.5px;color:#b45309;margin-top:8px;">Up to 5 photos &middot; JPG/PNG &middot; max 10MB each</span>
                            {{-- No native `required` here — a display:none file input can't be focused to
                                 show the browser's validation bubble, which silently blocks submission
                                 with no visible feedback. Validated in JS on submit instead (below). --}}
                            <input type="file" name="photos[]" id="photoFileInput" multiple accept="image/*"
                                   style="display:none;" onchange="previewPhotos(this)">
                            <input type="file" id="photoCameraInput" accept="image/*" capture="environment"
                                   style="display:none;" onchange="previewPhotos(this)">
                            <span id="photoRequiredErr" style="display:none;color:#dc2626;font-size:11.5px;font-weight:700;margin-top:8px;">Please add at least one site photo.</span>
                            <div id="photoPreview" data-receipt-set style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;"></div>
                        </div>
                    </div>

                    @if($errors->any())
                    <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;padding:12px 14px;margin-top:16px;color:#dc2626;font-size:13px;">
                        @foreach($errors->all() as $error)<div>• {{ $error }}</div>@endforeach
                    </div>
                    @endif

                    <div style="display:flex;justify-content:flex-end;margin-top:18px;">
                        <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
                            <i data-lucide="send" style="width:14px;height:14px;"></i>
                            Submit Update
                        </button>
                    </div>
                </form>
            </div>

            @elseif($pendingSubmission)
            {{-- ============================================================ --}}
            {{-- AWAITING APPROVAL: employee already submitted this phase's update.
                 Same two-level structure as admin's "Awaiting Your Approval" panel —
                 an outer amber section (icon + title + subtitle) wrapping an inner
                 white card (phase title + submitter chip + status badge header, plain
                 labeled sections below) — read-only, showing exactly what was sent.
                 Stays up until the admin actually approves it. --}}
            {{-- ============================================================ --}}
            @php
                $psRole    = $pendingSubmission->submitter_role_label; // 'Focal Person' | 'Employee' | 'Admin'
                $psIsFocal = $psRole === 'Focal Person';
                $psName    = $pendingSubmission->submitted_by_name;
                $psInitials = strtoupper(collect(preg_split('/\s+/', trim($psName)))
                                ->filter()->map(fn($p) => mb_substr($p, 0, 1))->take(2)->join('')) ?: '?';
            @endphp
            <div class="emp-pv-card" style="margin-top:20px;border:1px solid #fde68a;background:#fffdf5;">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                    <div style="width:42px;height:42px;border-radius:50%;background:#fde68a;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 10px rgba(251,191,36,0.35);">
                        <i data-lucide="clock" style="width:19px;height:19px;color:#92400e;"></i>
                    </div>
                    <div>
                        <h3 class="emp-pv-card-title" style="margin:0;color:#78350f;">Update Submitted</h3>
                        <p style="margin:2px 0 0;font-size:12.5px;color:#92400e;">Awaiting admin approval — you'll be notified once it's reviewed.</p>
                    </div>
                </div>

                <div class="pv-history-item" style="margin:0;">
                    <div class="pv-history-card" style="background:#fff;cursor:default;">
                        <div class="pv-history-header">
                            <div class="pv-history-title-col">
                                <div class="pv-history-phase-title">
                                    {{ ucfirst(str_replace('_', ' ', $pendingSubmission->phase)) }} Phase
                                </div>
                                <div class="pv-submitter-row">
                                    <span class="pv-submitter-avatar{{ $psIsFocal ? ' is-focal' : '' }}">{{ $psInitials }}</span>
                                    <span class="pv-submitter-info">
                                        <span class="pv-submitter-role{{ $psIsFocal ? ' is-focal' : '' }}">{{ $psRole }}</span>
                                        <span class="pv-submitter-name">{{ $psName }}</span>
                                    </span>
                                </div>
                            </div>
                            <span class="pv-history-status-badge status-pending">
                                <i data-lucide="clock"></i>
                                Pending Review
                            </span>
                        </div>

                        <div class="form-group" style="margin-top:14px;">
                            <label class="log-label">Date of Work</label>
                            <div style="background:var(--surface-2);border-radius:10px;padding:10px 14px;font-size:13px;font-weight:700;color:var(--dark);">
                                {{ $pendingSubmission->date_of_work->format('M d, Y') }}
                            </div>
                        </div>

                        <div style="height:1px;background:var(--border);margin:14px 0;"></div>

                        <div class="form-group">
                            <label class="log-label">Work Done</label>
                            <div style="background:var(--surface-2);border-radius:10px;padding:10px 14px;font-size:13px;color:var(--dark);white-space:pre-wrap;">{{ $pendingSubmission->work_done ?: 'No additional notes provided.' }}</div>
                        </div>

                        <div style="height:1px;background:var(--border);margin:14px 0;"></div>

                        <div class="form-group">
                            <label class="log-label">Issues / Observations</label>
                            <div style="background:var(--surface-2);border-radius:10px;padding:10px 14px;font-size:13px;color:var(--dark);white-space:pre-wrap;">{{ $pendingSubmission->issues ?: 'No issues reported.' }}</div>
                        </div>

                        @if($pendingSubmission->photos && count($pendingSubmission->photos) > 0)
                        <div style="height:1px;background:var(--border);margin:14px 0;"></div>

                        <div class="form-group" style="margin-bottom:0;">
                            <label class="log-label">Site Photos</label>
                            <div class="pv-history-attachments" data-receipt-set>
                                @foreach($pendingSubmission->photos as $photo)
                                    @if(preg_match('/\.(jpe?g|png|gif|webp|bmp)(\?.*)?$/i', $photo))
                                    <a href="{{ $photo }}" data-receipt title="Click to preview">
                                        <img src="{{ $photo }}" class="pv-history-thumb pv-history-thumb-lg">
                                    </a>
                                    @else
                                    <div class="pv-history-thumb-doc pv-history-thumb-lg"><i data-lucide="file-text"></i></div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            {{-- No form shown once the submission is approved, or when there's no request at all --}}

            <!-- Progress History + Request Update History side by side -->
            <div class="pv-grid-2">

            <!-- Progress History -->
            <div class="pv-card pv-grid-2-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i data-lucide="history" style="width:18px;height:18px;color:var(--accent);"></i>
                        <h3 class="pv-card-title" style="margin-bottom:0;">Progress History</h3>
                    </div>
                    <span style="font-size:12px;color:var(--muted);font-weight:600;">
                        {{ $updates->count() }} {{ Str::plural('entry', $updates->count()) }}
                    </span>
                </div>

                <div class="pv-grid-2-card-scroll{{ $updates->isEmpty() ? ' pv-grid-2-card-scroll-center' : '' }}" style="display:flex;flex-direction:column;">

                    @php
                        $imageExtRegex = '/\.(jpe?g|png|gif|webp|bmp)(\?.*)?$/i';
                    @endphp

                    @forelse($updates as $update)
                    @php
                        $isEmployee        = $update->type === 'employee_submission';
                        $isPending         = $update->status === 'pending_review';
                        $isPendingApproval = $update->status === 'pending_approval';
                        $isRevision        = $update->status === 'needs_revision';
                        $isSuperseded      = $update->status === 'superseded';

                        if ($isPending) {
                            $statusKey = 'pending'; $statusIcon = 'clock'; $statusLabel = 'Pending Review';
                        } elseif ($isRevision) {
                            $statusKey = 'revision'; $statusIcon = 'rotate-ccw'; $statusLabel = 'Needs Revision';
                        } elseif ($isSuperseded) {
                            $statusKey = 'superseded'; $statusIcon = 'copy'; $statusLabel = 'Superseded';
                        } elseif ($isPendingApproval) {
                            $statusKey = 'pending-client'; $statusIcon = 'clock'; $statusLabel = 'Pending Client Approval';
                        } else {
                            $statusKey = 'approved'; $statusIcon = 'check'; $statusLabel = 'Approved';
                        }
                    @endphp

                    <div class="pv-history-item" data-update-id="{{ $update->id }}">
                        <div class="pv-history-card" onclick="openUpdateModal({{ $update->id }})">
                            <div class="pv-history-header">
                                <div class="pv-history-title-col">
                                    <div class="pv-history-phase-title">
                                        {{ ucfirst(str_replace('_', ' ', $update->phase)) }} Phase
                                        @if($update->update_label === 'revision')
                                        <span class="pv-history-revision-badge">Revision</span>
                                        @endif
                                        <span class="pv-new-badge">New</span>
                                    </div>
                                    <div class="pv-history-meta-row">
                                        <i data-lucide="{{ $isEmployee ? 'user' : 'shield-check' }}"></i>
                                        @if($isEmployee)
                                            <span style="font-weight:800;{{ $update->submitter_role_label === 'Focal Person' ? 'color:#b45309;' : '' }}">{{ $update->submitter_role_label }}:</span>
                                            {{ $update->submitted_by_name }}
                                        @else
                                            Admin
                                        @endif
                                        <span class="pv-history-meta-dot"></span>
                                        <i data-lucide="calendar"></i>
                                        {{ $update->date_of_work->format('M d, Y') }}
                                        @if($update->percentage)
                                        <span class="pv-history-meta-dot"></span>
                                        <span class="pv-history-percentage">
                                            <i data-lucide="trending-up"></i>
                                            {{ $update->percentage }}%
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                <span class="pv-history-status-badge status-{{ $statusKey }}">
                                    <i data-lucide="{{ $statusIcon }}"></i>
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <div class="pv-history-body">
                                <div>
                                    <div class="pv-history-section-label">Work Done</div>
                                    <div class="pv-work-text">{{ Str::limit($update->work_done, 150) ?: 'No additional notes provided.' }}</div>
                                </div>

                                @if($update->photos && count($update->photos) > 0)
                                <div>
                                    <div class="pv-history-section-label">Site Photos</div>
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
                                </div>
                                @endif
                            </div>

                            <div style="display:flex;align-items:center;gap:4px;margin-top:10px;font-size:11.5px;font-weight:700;color:var(--accent);">
                                View Details <i data-lucide="arrow-right" style="width:12px;height:12px;"></i>
                            </div>
                        </div>
                    </div>

                    @empty
                    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:16px;padding:30px 20px;">
                        <div style="width:64px;height:64px;border-radius:50%;background:var(--surface-2);display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="history" style="width:32px;height:32px;color:var(--muted);"></i>
                        </div>
                        <div>
                            <p style="font-size:15px;font-weight:800;color:var(--dark);margin-bottom:6px;">No Updates Yet</p>
                            <p style="font-size:13px;color:var(--muted);max-width:280px;line-height:1.6;">Approved progress updates for this project will appear here.</p>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Request Update History: every update request the admin has sent you for
                 this project, so you can look back at what was asked and when — not just
                 the one currently open above. -->
            <div class="pv-card pv-grid-2-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i data-lucide="send" style="width:18px;height:18px;color:var(--accent);"></i>
                        <h3 class="pv-card-title" style="margin-bottom:0;">Request Update History</h3>
                    </div>
                    <span style="font-size:12px;color:var(--muted);font-weight:600;">
                        {{ $progressRequests->count() }} {{ Str::plural('request', $progressRequests->count()) }}
                    </span>
                </div>

                @php
                    $requestStatusMap = [
                        'open'               => ['key' => 'pending',  'icon' => 'clock',      'label' => 'Awaiting Your Submission'],
                        'revision_requested' => ['key' => 'revision',  'icon' => 'rotate-ccw', 'label' => 'Revision Requested'],
                        'completed'          => ['key' => 'approved',  'icon' => 'check',      'label' => 'Fulfilled'],
                    ];
                @endphp

                <div class="pv-grid-2-card-scroll{{ $progressRequests->isEmpty() ? ' pv-grid-2-card-scroll-center' : '' }}" style="display:flex;flex-direction:column;gap:10px;">
                    @forelse($progressRequests as $req)
                    @php $rs = $requestStatusMap[$req->status] ?? ['key' => 'superseded', 'icon' => 'help-circle', 'label' => ucfirst($req->status)]; @endphp
                    <div class="pv-history-item" style="margin:0;">
                        <div class="pv-history-card" style="cursor:default;">
                            <div class="pv-history-header">
                                <div class="pv-history-title-col">
                                    <div class="pv-history-phase-title">
                                        {{ ucfirst(str_replace('_', ' ', $req->phase)) }} Phase
                                    </div>
                                    <div class="pv-history-meta-row">
                                        <i data-lucide="calendar"></i>
                                        Requested {{ $req->created_at->format('M d, Y') }}
                                        @if($req->status === 'completed' && $req->fulfilled_at)
                                        <span class="pv-history-meta-dot"></span>
                                        <i data-lucide="check"></i>
                                        Submitted {{ $req->fulfilled_at->format('M d, Y') }}
                                        @endif
                                    </div>
                                </div>
                                <span class="pv-history-status-badge status-{{ $rs['key'] }}">
                                    <i data-lucide="{{ $rs['icon'] }}"></i>
                                    {{ $rs['label'] }}
                                </span>
                            </div>

                            @php $reqPhotos = $req->projectUpdate->photos ?? []; @endphp
                            @if($req->message || count($reqPhotos) > 0)
                            <div class="pv-history-body">
                                @if($req->message)
                                <div>
                                    <div class="pv-history-section-label">Admin's Note</div>
                                    <div class="pv-work-text">{{ $req->message }}</div>
                                </div>
                                @endif
                                @if(count($reqPhotos) > 0)
                                <div>
                                    <div class="pv-history-section-label">Site Photos</div>
                                    <div class="pv-history-attachments" data-receipt-set>
                                        @foreach(array_slice($reqPhotos, 0, 4) as $photo)
                                            @if(preg_match('/\.(jpe?g|png|gif|webp|bmp)(\?.*)?$/i', $photo))
                                            <a href="{{ $photo }}" data-receipt title="Click to preview">
                                                <img src="{{ $photo }}" class="pv-history-thumb">
                                            </a>
                                            @else
                                            <div class="pv-history-thumb-doc"><i data-lucide="file-text"></i></div>
                                            @endif
                                        @endforeach
                                        @if(count($reqPhotos) > 4)
                                        <div class="pv-history-thumb-more">+{{ count($reqPhotos) - 4 }}</div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:16px;padding:30px 20px;">
                        <div style="width:64px;height:64px;border-radius:50%;background:var(--surface-2);display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="send" style="width:32px;height:32px;color:var(--muted);"></i>
                        </div>
                        <div>
                            <p style="font-size:15px;font-weight:800;color:var(--dark);margin-bottom:6px;">No Requests Yet</p>
                            <p style="font-size:13px;color:var(--muted);max-width:280px;line-height:1.6;">Update requests the admin sends you for this project will appear here.</p>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            </div>
            <!-- end Progress History / Request Update History grid -->

            <!-- Project Information -->
            <div class="emp-pv-card" style="margin-top:20px;">
                <h3 class="emp-pv-card-title">
                    <i data-lucide="clipboard-list"></i>
                    Project Information
                </h3>

                <div class="progress-wrap" style="margin-top:0;margin-bottom:18px;">
                    <div class="progress-label">
                        <span style="font-weight:700;font-size:13px;">Overall Progress</span>
                        <span style="font-weight:900;color:var(--dark);">{{ $project->progress }}%</span>
                    </div>
                    <div class="progress-bar" style="height:10px;border-radius:999px;">
                        <div class="progress-fill"
                             style="width:{{ $project->progress }}%;border-radius:999px;
                             background:{{ $project->status === 'completed' ? '#207A3A' : '' }};"></div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:18px;align-content:start;">
                    <div style="background:#FDFBF8;border:1px solid var(--border);border-radius:12px;padding:20px;">
                        <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px;">CLIENT</span>
                        <strong style="font-size:14px;color:var(--dark);">{{ $project->live_client_name }}</strong>
                    </div>
                    <div style="background:#FDFBF8;border:1px solid var(--border);border-radius:12px;padding:20px;">
                        <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px;">TANK TYPE</span>
                        <strong style="font-size:14px;color:var(--dark);">{{ $project->tank_type }}</strong>
                    </div>
                    <div style="background:#FDFBF8;border:1px solid var(--border);border-radius:12px;padding:20px;">
                        <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px;">CAPACITY</span>
                        <strong style="font-size:14px;color:var(--dark);">{{ $project->capacity }}</strong>
                    </div>
                    <div style="background:#FDFBF8;border:1px solid var(--border);border-radius:12px;padding:20px;">
                        <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px;">START DATE</span>
                        <strong style="font-size:14px;color:var(--dark);">{{ $project->start_date->format('M d, Y') }}</strong>
                    </div>
                    <div style="background:#FDFBF8;border:1px solid var(--border);border-radius:12px;padding:20px;">
                        <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px;">END DATE</span>
                        <strong style="font-size:14px;color:var(--dark);">{{ $project->end_date->format('M d, Y') }}</strong>
                    </div>
                    <div style="background:#FDFBF8;border:1px solid var(--border);border-radius:12px;padding:20px;">
                        <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px;">CURRENT PHASE</span>
                        <strong style="font-size:14px;color:var(--accent);">{{ ucfirst(str_replace('_', ' ', $project->current_phase)) }}</strong>
                    </div>
                    @if($project->notes)
                    <div style="background:#FDFBF8;border:1px solid var(--border);border-radius:12px;padding:20px;grid-column:span 2;">
                        <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px;">NOTES</span>
                        <strong style="font-size:14px;color:var(--dark);">{{ $project->notes }}</strong>
                    </div>
                    @endif
                </div>
            </div>

    </main>

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
        $startDate    = $project->start_date->format('Y-m-d');
        $currentPhase = $project->current_phase;

        // Include the employee's own pending submission too (if any) so its "View
        // Details" click above finds a matching entry — it isn't part of $updates
        // itself (that's the official approved history), just looked up here.
        $updatesForModal = $pendingSubmission ? $updates->concat([$pendingSubmission]) : $updates;

        $updatesData = $updatesForModal->map(function($u) {
            return [
                'id'                => $u->id,
                'phase'             => $u->phase,
                'type'              => $u->type,
                'update_label'      => $u->update_label,
                'revision_feedback' => $u->revision_feedback,
                'status'            => $u->status,
                'work_done'         => $u->work_done,
                'issues'            => $u->issues,
                'photos'            => $u->photos ?? [],
                'date_of_work'      => $u->date_of_work->format('M d, Y'),
                'submitted_at'      => $u->created_at->format('M d, Y h:i A'),
                'submitted_by'      => $u->submitted_by_name,
                'submitter_role'    => $u->submitter_role_label, // 'Focal Person' | 'Employee' | 'Admin'
                'percentage'        => $u->percentage,
            ];
        })->keyBy('id')->toArray();
    @endphp

    <script>
        const PROJECT_PROGRESS      = {{ $progress }};
        const PROJECT_STATUS        = "{{ $status }}";
        const PROJECT_DURATION      = "{{ $duration }}";
        const START_DATE_STR        = "{{ $startDate }}";
        const PROJECT_CURRENT_PHASE = "{{ $currentPhase }}";
        const UPDATES_DATA          = @json($updatesData);
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/employee.js') }}"></script>
    <script>
        let empPhotoFiles = [];
        let empRevisionFiles = [];

        function buildPhotoPreview(files, previewId, removeCallback) {
            const preview = document.getElementById(previewId);
            if (!preview) return;
            preview.innerHTML = '';
            files.forEach((file, i) => {
                const url = URL.createObjectURL(file);
                const div = document.createElement('div');
                div.style.cssText = 'position:relative;width:80px;height:60px;border-radius:6px;overflow:hidden;border:1px solid var(--border);flex-shrink:0;';
                // The thumb is a data-receipt link (opens the shared lightbox to preview it full-size,
                // reusing the same viewer as payment receipts elsewhere) — the remove button sits
                // OUTSIDE that link, as a sibling, so tapping it doesn't also trigger the preview.
                div.innerHTML = `
                    <a href="${url}" data-receipt title="Click to preview" style="display:block;width:100%;height:100%;">
                        <img src="${url}" style="width:100%;height:100%;object-fit:cover;display:block;">
                    </a>
                    <button type="button" onclick="${removeCallback}(${i})"
                        style="position:absolute;top:2px;right:2px;width:16px;height:16px;border-radius:50%;background:rgba(0,0,0,0.6);color:#fff;border:none;font-size:10px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;z-index:2;">✕</button>`;
                preview.appendChild(div);
            });
            const remaining = 5 - files.length;
            if (remaining > 0 && files.length > 0) {
                const hint = document.createElement('div');
                hint.style.cssText = 'font-size:11px;color:var(--muted);align-self:center;';
                hint.textContent = `+${remaining} more slot${remaining > 1 ? 's' : ''}`;
                preview.appendChild(hint);
            }
        }

        function syncInput(input, files) {
            const dt = new DataTransfer();
            files.forEach(f => dt.items.add(f));
            input.files = dt.files;
        }

        // Both the "Upload Photos" (gallery) and "Take Photo" (camera) inputs feed the
        // same tracked file list, which is always synced back onto the one *named* input
        // (photoFileInput / revisionPhotoFileInput) so the form actually submits every
        // photo regardless of which control the employee used to add it.
        function previewPhotos(input) {
            const rejected = [];
            for (const f of Array.from(input.files)) {
                if (empPhotoFiles.length >= 5) break;
                if (f.size > 10 * 1024 * 1024) { rejected.push(f.name); continue; }
                empPhotoFiles.push(f);
            }
            // Reset the input that just fired BEFORE re-syncing — when that's the
            // named gallery input itself, resetting after the sync would wipe out
            // the very files we just wrote onto it (and the required file input,
            // being display:none, then blocks submit with no visible error at all).
            input.value = '';
            syncInput(document.getElementById('photoFileInput'), empPhotoFiles);
            buildPhotoPreview(empPhotoFiles, 'photoPreview', 'removeEmpPhoto');
            if (rejected.length) showFileTooLargeModal(rejected.join(', '), 10);
        }

        function removeEmpPhoto(index) {
            empPhotoFiles.splice(index, 1);
            syncInput(document.getElementById('photoFileInput'), empPhotoFiles);
            buildPhotoPreview(empPhotoFiles, 'photoPreview', 'removeEmpPhoto');
        }

        function previewRevisionPhotos(input) {
            const rejected = [];
            for (const f of Array.from(input.files)) {
                if (empRevisionFiles.length >= 5) break;
                if (f.size > 10 * 1024 * 1024) { rejected.push(f.name); continue; }
                empRevisionFiles.push(f);
            }
            // Same ordering fix as previewPhotos() — reset before re-sync, never after.
            input.value = '';
            syncInput(document.getElementById('revisionPhotoFileInput'), empRevisionFiles);
            buildPhotoPreview(empRevisionFiles, 'revisionPhotoPreview', 'removeRevisionPhoto');
            if (rejected.length) showFileTooLargeModal(rejected.join(', '), 10);
        }

        function removeRevisionPhoto(index) {
            empRevisionFiles.splice(index, 1);
            syncInput(document.getElementById('revisionPhotoFileInput'), empRevisionFiles);
            buildPhotoPreview(empRevisionFiles, 'revisionPhotoPreview', 'removeRevisionPhoto');
        }

        // Site Photos is required, but the file input backing it is display:none (needed for
        // the custom Upload/Camera buttons), so the browser can't show its own validation
        // bubble for it — check it here instead, with a visible error message.
        var progressUpdateForm = document.getElementById('progressUpdateForm');
        if (progressUpdateForm) {
            progressUpdateForm.addEventListener('submit', function (e) {
                var err = document.getElementById('photoRequiredErr');
                if (empPhotoFiles.length === 0) {
                    e.preventDefault();
                    if (err) {
                        err.style.display = 'block';
                        err.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    }
                } else if (err) {
                    err.style.display = 'none';
                }
            });
        }

        var revisionUpdateForm = document.getElementById('revisionUpdateForm');
        if (revisionUpdateForm) {
            revisionUpdateForm.addEventListener('submit', function (e) {
                var err = document.getElementById('revisionPhotoRequiredErr');
                if (empRevisionFiles.length === 0) {
                    e.preventDefault();
                    if (err) {
                        err.style.display = 'block';
                        err.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    }
                } else if (err) {
                    err.style.display = 'none';
                }
            });
        }

        function getFileIconName(filename) {
            const ext = (filename.split('.').pop() || '').toLowerCase();
            if (ext === 'pdf' || ext === 'doc' || ext === 'docx') return 'file-text';
            if (ext === 'xls' || ext === 'xlsx') return 'file-spreadsheet';
            return 'file';
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

        function ucPhase(phase) {
            return phase.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        }

        function openUpdateModal(updateId) {
            markUpdateSeen(updateId);

            const u = UPDATES_DATA[updateId];
            if (!u) return;

            const isEmployee = u.type === 'employee_submission';

            document.getElementById('modalUpdateTitle').textContent    = ucPhase(u.phase) + ' Phase Update';
            document.getElementById('modalUpdateSubtitle').textContent = u.submitted_at;
            document.getElementById('modalSubmittedBy').textContent    = (u.submitter_role ? u.submitter_role + ' — ' : '') + u.submitted_by;
            document.getElementById('modalDateOfWork').textContent     = u.date_of_work;
            document.getElementById('modalPhase').textContent          = ucPhase(u.phase);
            document.getElementById('modalType').textContent           = isEmployee ? 'Employee Submission' : 'Admin Update';

            // Status badge — dynamic now that this modal can also open a submission
            // that's still pending review (not just already-approved history entries).
            let badgeHtml = '';
            if (u.status === 'pending_review') {
                badgeHtml = `<span style="display:inline-flex;align-items:center;gap:6px;background:#fffbeb;border:1.5px solid #f59e0b;color:#92400e;font-size:12px;font-weight:700;padding:5px 14px;border-radius:20px;">
                    <span style="width:8px;height:8px;background:#f59e0b;border-radius:50%;display:inline-block;"></span>
                    Pending Review — Awaiting Admin Approval
                </span>`;
            } else if (u.status === 'needs_revision') {
                badgeHtml = `<span style="display:inline-flex;align-items:center;gap:6px;background:#fff7ed;border:1.5px solid #fb923c;color:#9a3412;font-size:12px;font-weight:700;padding:5px 14px;border-radius:20px;">
                    <span style="width:8px;height:8px;background:#ea580c;border-radius:50%;display:inline-block;"></span>
                    Needs Revision
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

            const revisionSec = document.getElementById('modalRevisionSection');
            if (u.revision_feedback) {
                revisionSec.style.display = 'block';
                document.getElementById('modalRevisionFeedback').textContent = u.revision_feedback;
            } else {
                revisionSec.style.display = 'none';
            }

            document.getElementById('modalActions').innerHTML = `
                <div style="font-size:13px;color:var(--muted);text-align:right;font-style:italic;">
                    This update has been approved.
                </div>`;

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
    @include('partials.receipt_viewer')
</body>
</html>