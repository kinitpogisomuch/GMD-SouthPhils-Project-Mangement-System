<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project View | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
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
                    <p>{{ $project->client }} &nbsp;·&nbsp; {{ $project->tank_type }} &nbsp;·&nbsp; {{ $project->capacity }}</p>
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
                        Fabrication Phase Tracker &nbsp;·&nbsp; {{ $project->capacity }} {{ $project->tank_type }}
                    </div>
                    <span class="pv-progress-badge" id="empProgressBadge">{{ $project->progress }}%</span>
                </div>
                <div class="emp-phase-steps" id="empPhaseSteps"></div>

                @if($project->current_phase === 'planning')
                @php
                    $empSubPhaseLabels  = [
                        'shop_drawing' => 'Shop Drawing / Tank Design',
                        'quotation'    => 'Project Quotation',
                        'payment'      => 'Payment',
                    ];
                    $empSubPhaseKeys    = array_keys($empSubPhaseLabels);
                    $empCurrentSubIndex = array_search($project->current_sub_phase, $empSubPhaseKeys);
                @endphp
                <div style="margin-top:14px;margin-bottom:6px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);">
                    {{ ucfirst(str_replace('_', ' ', $project->current_phase)) }} Phase &nbsp;·&nbsp; Sub-Phases
                </div>
                <div class="emp-phase-steps sub-phase-steps" style="margin-top:0;">
                    @foreach($empSubPhaseLabels as $key => $label)
                        @php
                            $idx = array_search($key, $empSubPhaseKeys);
                            if ($empCurrentSubIndex === false) {
                                $subStatus = 'done';
                            } elseif ($idx < $empCurrentSubIndex) {
                                $subStatus = 'done';
                            } elseif ($idx === $empCurrentSubIndex) {
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
                @endif
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
                    <div style="font-size:13.5px;color:#7c2d12;white-space:pre-wrap;line-height:1.7;font-weight:600;">
                        {{ $revisionUpdate->revision_feedback ?? 'Please review and resubmit your update.' }}
                    </div>
                </div>

                <div style="display:flex;align-items:flex-start;gap:8px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:12px;margin-bottom:20px;">
                    <span style="font-size:16px;">⚠</span>
                    <span style="font-size:13px;color:#92400e;">Please review the feedback and submit the required revisions to continue the approval process.</span>
                </div>

                <div style="border-top:1px solid #fed7aa;padding-top:16px;">
                    <div style="font-size:13.5px;font-weight:800;color:#9a3412;margin-bottom:14px;">Submit Revision</div>

                    <form method="POST"
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
                            <label style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:18px;border:2px dashed #fed7aa;border-radius:8px;cursor:pointer;background:#fff7ed;">
                                <i data-lucide="upload-cloud" style="width:24px;height:24px;color:#ea580c;"></i>
                                <span style="font-size:13px;font-weight:600;color:#9a3412;">Click to upload photos</span>
                                <span style="font-size:11.5px;color:#c2410c;">Required — JPG, PNG up to 5MB each</span>
                                <input type="file" name="photos[]" multiple accept="image/*"
                                       style="display:none;" onchange="previewRevisionPhotos(this)" required>
                            </label>
                            <div id="revisionPhotoPreview" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;"></div>
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
            <div class="emp-pv-card" style="margin-top:20px;background:#fef9c3;border:1px solid #fde68a;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                    <i data-lucide="bell" style="color:#d97706;width:18px;height:18px;"></i>
                    <h3 class="emp-pv-card-title" style="margin:0;color:#92400e;">Progress Update Requested</h3>
                </div>
                <p style="font-size:13.5px;color:#78350f;margin-bottom:16px;">
                    {{ $openRequest->message ?? 'The admin is requesting a progress update for this project.' }}
                </p>

                <form method="POST"
                      action="{{ route('employee.project.submit_update', $openRequest->id) }}"
                      enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label class="log-label">DATE OF WORK </label>
                        <input type="date" name="date_of_work" class="log-input"
                               value="{{ old('date_of_work') }}" required>
                    </div>

                    <div class="form-group" style="margin-top:14px;">
                        <label class="log-label">WORK DONE </label>
                        <textarea name="work_done" class="log-textarea" rows="4"
                                  placeholder="Describe what was accomplished..." required>{{ old('work_done') }}</textarea>
                    </div>

                    <div class="form-group" style="margin-top:14px;">
                        <label class="log-label">ISSUES / OBSERVATIONS
                            <span style="font-weight:400;color:var(--text-muted);text-transform:none;">(optional)</span>
                        </label>
                        <textarea name="issues" class="log-textarea" rows="3"
                                  placeholder="Any problems, delays, or observations...">{{ old('issues') }}</textarea>
                    </div>

                    <div class="form-group" style="margin-top:14px;">
                        <label class="log-label">SITE PHOTOS </label>
                        <label class="log-upload-label">
                            <i data-lucide="upload-cloud"></i>
                            Click to upload photos — up to 5, max 5MB each
                            <input type="file" name="photos[]" multiple accept="image/*"
                                   style="display:none;" onchange="previewPhotos(this)" required>
                        </label>
                        <div id="photoPreview" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;"></div>
                    </div>

                    @if($errors->any())
                    <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px;margin-top:14px;color:#dc2626;font-size:13px;">
                        @foreach($errors->all() as $error)<div>• {{ $error }}</div>@endforeach
                    </div>
                    @endif

                    <div style="display:flex;justify-content:flex-end;margin-top:16px;">
                        <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
                            <i data-lucide="send" style="width:14px;height:14px;"></i>
                            Submit Update
                        </button>
                    </div>
                </form>
            </div>
            @endif
            {{-- No form shown when status is 'completed' or no request exists --}}

            <!-- Progress History -->
            <div class="pv-card" style="margin-top:20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i data-lucide="history" style="width:18px;height:18px;color:var(--accent);"></i>
                        <h3 class="pv-card-title" style="margin-bottom:0;">Progress History</h3>
                    </div>
                    <span style="font-size:12px;color:var(--muted);font-weight:600;">
                        {{ $updates->count() }} {{ Str::plural('entry', $updates->count()) }}
                    </span>
                </div>

                <div style="display:flex;flex-direction:column;">

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

                    @forelse($updates as $update)
                    @php
                        $isEmployee = $update->type === 'employee_submission';
                        $phaseIcon  = $phaseIcons[$update->phase] ?? 'file-text';
                    @endphp

                    <div class="pv-history-item" data-update-id="{{ $update->id }}">
                        <div class="pv-history-rail">
                            <div class="pv-history-icon" style="background:#dcfce7;">
                                <i data-lucide="{{ $phaseIcon }}" style="color:#16a34a;"></i>
                            </div>
                            <div class="pv-history-line"></div>
                        </div>

                        <div class="pv-history-card" style="background:var(--surface-2);border:1px solid var(--border);" onclick="openUpdateModal({{ $update->id }})">

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
                                <span style="display:inline-flex;align-items:center;gap:4px;color:#16a34a;">
                                    <i data-lucide="check" style="width:11px;height:11px;"></i> Approved
                                </span>
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

            <!-- Project Info -->
            <div class="emp-pv-card" style="margin-top:20px;">
                <h3 class="emp-pv-card-title">
                    <i data-lucide="clipboard-list"></i>
                    Project Information
                </h3>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
                    <div style="background:#FDFBF8;border:1px solid var(--border);border-radius:12px;padding:14px;">
                        <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px;">CLIENT</span>
                        <strong style="font-size:14px;color:var(--dark);">{{ $project->client }}</strong>
                    </div>
                    <div style="background:#FDFBF8;border:1px solid var(--border);border-radius:12px;padding:14px;">
                        <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px;">TANK TYPE</span>
                        <strong style="font-size:14px;color:var(--dark);">{{ $project->tank_type }}</strong>
                    </div>
                    <div style="background:#FDFBF8;border:1px solid var(--border);border-radius:12px;padding:14px;">
                        <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px;">CAPACITY</span>
                        <strong style="font-size:14px;color:var(--dark);">{{ $project->capacity }}</strong>
                    </div>
                    <div style="background:#FDFBF8;border:1px solid var(--border);border-radius:12px;padding:14px;">
                        <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px;">START DATE</span>
                        <strong style="font-size:14px;color:var(--dark);">{{ $project->start_date->format('M d, Y') }}</strong>
                    </div>
                    <div style="background:#FDFBF8;border:1px solid var(--border);border-radius:12px;padding:14px;">
                        <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px;">END DATE</span>
                        <strong style="font-size:14px;color:var(--dark);">{{ $project->end_date->format('M d, Y') }}</strong>
                    </div>
                    <div style="background:#FDFBF8;border:1px solid var(--border);border-radius:12px;padding:14px;">
                        <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px;">CURRENT PHASE</span>
                        <strong style="font-size:14px;color:var(--accent);">{{ ucfirst(str_replace('_', ' ', $project->current_phase)) }}</strong>
                    </div>
                    @if($project->notes)
                    <div style="background:#FDFBF8;border:1px solid var(--border);border-radius:12px;padding:14px;grid-column:span 3;">
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

        $updatesData = $updates->map(function($u) {
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
                'submitted_by'      => $u->submittedBy ? $u->submittedBy->full_name : 'Admin',
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
                div.innerHTML = `
                    <img src="${url}" style="width:100%;height:100%;object-fit:cover;">
                    <button type="button" onclick="${removeCallback}(${i})"
                        style="position:absolute;top:2px;right:2px;width:16px;height:16px;border-radius:50%;background:rgba(0,0,0,0.6);color:#fff;border:none;font-size:10px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;">✕</button>`;
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

        function previewPhotos(input) {
            for (const f of Array.from(input.files)) {
                if (empPhotoFiles.length >= 5) break;
                empPhotoFiles.push(f);
            }
            syncInput(input, empPhotoFiles);
            buildPhotoPreview(empPhotoFiles, 'photoPreview', 'removeEmpPhoto');
        }

        function removeEmpPhoto(index) {
            empPhotoFiles.splice(index, 1);
            const input = document.querySelector('#photoPreview').closest('.form-group').querySelector('input[type=file]');
            syncInput(input, empPhotoFiles);
            buildPhotoPreview(empPhotoFiles, 'photoPreview', 'removeEmpPhoto');
        }

        function previewRevisionPhotos(input) {
            for (const f of Array.from(input.files)) {
                if (empRevisionFiles.length >= 5) break;
                empRevisionFiles.push(f);
            }
            syncInput(input, empRevisionFiles);
            buildPhotoPreview(empRevisionFiles, 'revisionPhotoPreview', 'removeRevisionPhoto');
        }

        function removeRevisionPhoto(index) {
            empRevisionFiles.splice(index, 1);
            const input = document.querySelector('#revisionPhotoPreview').closest('.form-group').querySelector('input[type=file]');
            syncInput(input, empRevisionFiles);
            buildPhotoPreview(empRevisionFiles, 'revisionPhotoPreview', 'removeRevisionPhoto');
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
            document.getElementById('modalSubmittedBy').textContent    = u.submitted_by;
            document.getElementById('modalDateOfWork').textContent     = u.date_of_work;
            document.getElementById('modalPhase').textContent          = ucPhase(u.phase);
            document.getElementById('modalType').textContent           = isEmployee ? 'Employee Submission' : 'Admin Update';

            document.getElementById('modalStatusBadge').innerHTML = `
                <span style="display:inline-flex;align-items:center;gap:6px;background:#dcfce7;border:1.5px solid #86efac;color:#14532d;font-size:12px;font-weight:700;padding:5px 14px;border-radius:20px;">
                    <span style="width:8px;height:8px;background:#16a34a;border-radius:50%;display:inline-block;"></span>
                    Approved
                </span>`;

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
</body>
</html>