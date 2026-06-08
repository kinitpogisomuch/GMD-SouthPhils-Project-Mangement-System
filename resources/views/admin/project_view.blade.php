<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project View | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body>

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1>{{ $project->name }}</h1>
                    <p>{{ $project->client }} &nbsp;·&nbsp; {{ $project->tank_type }} &nbsp;·&nbsp; {{ $project->capacity }}</p>
                </div>
                <a href="{{ route('admin.projects') }}" class="back-btn">
                    <i data-lucide="arrow-left"></i>
                    Back to Projects
                </a>
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

            <!-- Phase Tracker Card -->
            <div class="tracker-card">
                <div class="tracker-card-header">
                    <div class="tracker-title">
                        <i data-lucide="layers"></i>
                        Fabrication Phase Tracker &nbsp;·&nbsp; {{ $project->capacity }} {{ $project->tank_type }}
                    </div>
                    <span class="tracker-progress-badge" id="progressBadge">{{ $project->progress }}%</span>
                </div>
                <div class="phase-steps" id="phaseSteps"></div>
            </div>

            <!-- Side by Side: Add Update + Progress History -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;">

                <!-- LEFT: Add Progress Update -->
                <div class="pv-card" style="margin-top:0;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
                        <i data-lucide="clipboard-list" style="width:18px;height:18px;color:var(--accent);"></i>
                        <h3 class="pv-card-title" style="margin-bottom:0;">
                            Add Progress Update
                            <span style="font-size:12px;font-weight:600;color:var(--accent);margin-left:6px;">
                                — {{ ucfirst(str_replace('_', ' ', $project->current_phase)) }} Phase
                            </span>
                        </h3>
                    </div>

                    @if($project->current_phase === 'delivery' && $project->progress === 100)
                    <div style="background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:16px;display:flex;align-items:center;gap:10px;font-size:13.5px;font-weight:600;color:#14532d;">
                        <i data-lucide="check-circle-2" style="width:18px;height:18px;flex-shrink:0;"></i>
                        This project is fully completed. All phases have been finished.
                    </div>
                    @else
                    <form method="POST"
                          action="{{ route('admin.project.add_update', $project->id) }}"
                          enctype="multipart/form-data"
                          id="addUpdateForm">
                        @csrf

                        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:12.5px;color:var(--text-secondary);">
                            <i data-lucide="info" style="width:13px;height:13px;display:inline;margin-right:4px;"></i>
                            Saving will advance project from
                            <strong style="color:var(--text-primary);">{{ ucfirst(str_replace('_', ' ', $project->current_phase)) }}</strong>
                            →
                            <strong style="color:var(--accent);">{{ ucfirst(str_replace('_', ' ', $nextPhase ?? 'Completed')) }}</strong>
                        </div>

                        <div class="form-group">
                            <label class="log-label">DATE OF WORK *</label>
                            <input type="date" name="date_of_work" class="log-input" required>
                        </div>

                        <div class="form-group" style="margin-top:12px;">
                            <label class="log-label">WORK DONE *</label>
                            <textarea name="work_done" class="log-textarea" rows="3"
                                      placeholder="Describe what was accomplished..." required></textarea>
                        </div>

                        <div class="form-group" style="margin-top:12px;">
                            <label class="log-label">ISSUES / OBSERVATIONS
                                <span style="font-weight:400;color:var(--muted);text-transform:none;">(optional)</span>
                            </label>
                            <textarea name="issues" class="log-textarea" rows="2"
                                      placeholder="Any problems, delays, or observations..."></textarea>
                        </div>

                        <div class="form-group" style="margin-top:12px;">
                            <label class="log-label">SITE PHOTOS *</label>
                            <label style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:18px;border:2px dashed var(--border-2);border-radius:8px;cursor:pointer;background:var(--surface-2);">
                                <i data-lucide="upload-cloud" style="width:24px;height:24px;color:var(--accent);"></i>
                                <span style="font-size:13px;font-weight:600;color:var(--text-primary);">Click to upload photos</span>
                                <span style="font-size:11.5px;color:var(--muted);">Required — up to 5 photos, JPG/PNG, max 5MB each</span>
                                <input type="file" name="photos[]" multiple accept="image/*"
                                       style="display:none;" onchange="previewAdminPhotos(this)" required>
                            </label>
                            <div id="adminPhotoPreview" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;"></div>
                        </div>

                        @if($errors->any())
                        <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px;margin-top:12px;color:#dc2626;font-size:13px;">
                            @foreach($errors->all() as $error)
                                <div>• {{ $error }}</div>
                            @endforeach
                        </div>
                        @endif

                        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px;">
                            <button type="button" class="cancel-btn" id="openRequestModal"
                                    style="font-size:12.5px;padding:8px 14px;">
                                <i data-lucide="send"></i>
                                Request from Employee
                            </button>
                            <button type="submit" class="save-btn" style="font-size:12.5px;padding:8px 16px;">
                                <i data-lucide="save"></i>
                                Save Progress Update
                            </button>
                        </div>
                    </form>
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

                    <div style="overflow-y:auto;max-height:520px;display:flex;flex-direction:column;gap:10px;padding-right:4px;">

                        @forelse($allUpdates as $update)
                        @php
                            $isPending    = $update->status === 'pending_review';
                            $isRevision   = $update->status === 'needs_revision';
                            $isSuperseded = $update->status === 'superseded';
                            $isEmployee   = $update->type === 'employee_submission';

                            if ($isPending) {
                                $cardStyle = 'background:#fffbeb;border:2px solid #f59e0b;';
                            } elseif ($isRevision) {
                                $cardStyle = 'background:#fff7ed;border:2px solid #fb923c;';
                            } elseif ($isSuperseded) {
                                $cardStyle = 'background:var(--surface-2);border:1px dashed var(--border);opacity:0.6;';
                            } else {
                                $cardStyle = 'background:var(--surface-2);border:1px solid var(--border);';
                            }
                        @endphp

                        <div onclick="openUpdateModal({{ $update->id }})"
                             style="border-radius:8px;padding:14px;cursor:pointer;transition:all 0.15s;{{ $cardStyle }}"
                             onmouseover="this.style.opacity='0.85'"
                             onmouseout="this.style.opacity='{{ $isSuperseded ? '0.6' : '1' }}'">

                            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
                                <div>
                                    <span style="font-size:13.5px;font-weight:700;color:var(--text-primary);">
                                        {{ ucfirst(str_replace('_', ' ', $update->phase)) }} Phase
                                        @if($update->update_label === 'revision')
                                        <span style="font-size:10.5px;background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:20px;margin-left:6px;font-weight:700;">
                                            Revision Submission
                                        </span>
                                        @endif
                                    </span>

                                    @if($isPending)
                                    <span style="display:inline-flex;align-items:center;gap:4px;background:#f59e0b;color:#fff;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:8px;">
                                        <i data-lucide="clock" style="width:10px;height:10px;"></i>
                                        Pending Review
                                    </span>
                                    @elseif($isRevision)
                                    <span style="display:inline-flex;align-items:center;gap:4px;background:#ea580c;color:#fff;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:8px;">
                                        <i data-lucide="rotate-ccw" style="width:10px;height:10px;"></i>
                                        Needs Revision
                                    </span>
                                    @elseif($isSuperseded)
                                    <span style="display:inline-flex;align-items:center;gap:4px;background:#94a3b8;color:#fff;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:8px;">
                                        Superseded
                                    </span>
                                    @else
                                    <span style="display:inline-flex;align-items:center;gap:4px;background:#dcfce7;color:#14532d;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:8px;">
                                        <i data-lucide="check" style="width:10px;height:10px;"></i>
                                        Approved
                                    </span>
                                    @endif
                                </div>
                                <span style="font-size:11.5px;color:var(--muted);white-space:nowrap;">
                                    {{ $update->date_of_work->format('M d, Y') }}
                                </span>
                            </div>

                            <div style="font-size:12px;color:var(--muted);margin-bottom:6px;">
                                {{ $isEmployee ? '👤 Employee Submission' : '🔧 Admin Update' }}
                                @if($isPending)
                                <span style="color:#d97706;font-weight:700;"> · Requires Approval</span>
                                @elseif($isRevision)
                                <span style="color:#ea580c;font-weight:700;"> · Awaiting Employee Revision</span>
                                @endif
                            </div>

                            <div style="font-size:13px;color:var(--text-secondary);line-height:1.4;">
                                {{ Str::limit($update->work_done, 100) }}
                            </div>

                            @if($update->photos && count($update->photos) > 0)
                            <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap;">
                                @foreach(array_slice($update->photos, 0, 3) as $photo)
                                <img src="{{ $photo }}"
                                     style="width:52px;height:40px;object-fit:cover;border-radius:4px;border:1px solid var(--border);">
                                @endforeach
                                @if(count($update->photos) > 3)
                                <div style="width:52px;height:40px;background:var(--border);border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--muted);">
                                    +{{ count($update->photos) - 3 }}
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>

                        @empty
                        <div style="text-align:center;padding:40px 0;color:var(--muted);font-size:13.5px;">
                            No updates yet. Save a progress update to get started.
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Bottom Grid: Phase Details + Timeline -->
            <div class="pv-grid" style="margin-top:20px;">
                <div class="pv-card">
                    <h3 class="pv-card-title">Phase Details</h3>
                    <div class="phase-details-list" id="phaseDetailsList"></div>
                </div>
                <div class="pv-card">
                    <h3 class="pv-card-title">Timeline Overview</h3>
                    <p class="timeline-note">Materials arrival = Day 0 (project clock start)</p>
                    <div class="timeline-table-wrap">
                        <table class="timeline-table" id="timelineTable">
                            <thead>
                                <tr>
                                    <th>Phase</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody id="timelineBody"></tbody>
                        </table>
                    </div>
                    <div class="timeline-total" id="timelineTotal"></div>
                </div>
            </div>

            <!-- Project Info Card -->
            <div class="pv-card" style="margin-top:20px;">
                <h3 class="pv-card-title">Project Information</h3>
                <div class="project-detail-grid">
                    <div class="project-detail-box"><span>Client</span><strong>{{ $project->client }}</strong></div>
                    <div class="project-detail-box"><span>Contact Number</span><strong>{{ $project->contact_number ?? '—' }}</strong></div>
                    <div class="project-detail-box"><span>Email</span><strong>{{ $project->email ?? '—' }}</strong></div>
                    <div class="project-detail-box"><span>Address</span><strong>{{ $clientAddress ?? '—' }}</strong></div>
                    <div class="project-detail-box"><span>Tank Type</span><strong>{{ $project->tank_type }}</strong></div>
                    <div class="project-detail-box"><span>Capacity</span><strong>{{ $project->capacity }}</strong></div>
                    <div class="project-detail-box"><span>Dimensions</span><strong>{{ $project->dimensions ?? '—' }}</strong></div>
                    <div class="project-detail-box"><span>Start Date</span><strong>{{ $project->start_date->format('M d, Y') }}</strong></div>
                    <div class="project-detail-box"><span>End Date</span><strong>{{ $project->end_date->format('M d, Y') }}</strong></div>
                    <div class="project-detail-box"><span>Payment Status</span><strong>{{ $project->payment_status }}</strong></div>
                    <div class="project-detail-box"><span>Current Phase</span><strong>{{ ucfirst(str_replace('_', ' ', $project->current_phase)) }}</strong></div>
                    <div class="project-detail-box"><span>Duration</span><strong>{{ $project->duration ?? 'N/A' }}</strong></div>
                    @if($project->notes)
                    <div class="project-detail-box" style="grid-column:span 3;"><span>Notes</span><strong>{{ $project->notes }}</strong></div>
                    @endif
                </div>
            </div>

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

            <div style="margin-bottom:16px;">
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
                    <div style="font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.05em;">SITE PHOTOS</div>
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

    @php
        $progress     = $project->progress;
        $status       = strtolower($project->status);
        $duration     = $project->duration ?? 'N/A';
        $startDate    = $project->start_date->format('Y-m-d');
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
        const START_DATE_STR        = "{{ $startDate }}";
        const PROJECT_CURRENT_PHASE = "{{ $currentPhase }}";
        const UPDATES_DATA          = @json($updatesData);
        const APPROVE_BASE_URL      = "{{ url('/admin/project-updates') }}";
        const REVISION_BASE_URL     = "{{ url('/admin/project-updates') }}";
        const PROJECT_ID            = {{ $project->id }};
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        let adminPhotoFiles = [];

        function previewAdminPhotos(input) {
            const incoming = Array.from(input.files);
            for (const f of incoming) {
                if (adminPhotoFiles.length >= 5) break;
                adminPhotoFiles.push(f);
            }
            if (adminPhotoFiles.length > 5) adminPhotoFiles = adminPhotoFiles.slice(0, 5);
            syncAdminInput(input);
            renderAdminPreviews(input);
        }

        function removeAdminPhoto(index, input) {
            adminPhotoFiles.splice(index, 1);
            syncAdminInput(input);
            renderAdminPreviews(input);
        }

        function syncAdminInput(input) {
            const dt = new DataTransfer();
            adminPhotoFiles.forEach(f => dt.items.add(f));
            input.files = dt.files;
        }

        function renderAdminPreviews(input) {
            const preview = document.getElementById('adminPhotoPreview');
            if (!preview) return;
            preview.innerHTML = '';
            adminPhotoFiles.forEach((file, i) => {
                const url = URL.createObjectURL(file);
                const div = document.createElement('div');
                div.style.cssText = 'position:relative;width:72px;height:56px;border-radius:6px;overflow:hidden;border:1px solid var(--border);flex-shrink:0;';
                div.innerHTML = `
                    <img src="${url}" style="width:100%;height:100%;object-fit:cover;">
                    <button type="button" onclick="removeAdminPhoto(${i}, this.closest('#adminPhotoPreview').previousElementSibling.querySelector('input[type=file]'))"
                        style="position:absolute;top:2px;right:2px;width:16px;height:16px;border-radius:50%;background:rgba(0,0,0,0.6);color:#fff;border:none;font-size:10px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;">✕</button>`;
                preview.appendChild(div);
            });
            const remaining = 5 - adminPhotoFiles.length;
            if (remaining > 0 && adminPhotoFiles.length > 0) {
                const hint = document.createElement('div');
                hint.style.cssText = 'font-size:11px;color:var(--muted);align-self:center;';
                hint.textContent = `+${remaining} more slot${remaining > 1 ? 's' : ''}`;
                preview.appendChild(hint);
            }
        }

        document.getElementById('openRequestModal').addEventListener('click', () => {
            document.getElementById('requestUpdateModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        });
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

            document.getElementById('modalWorkDone').textContent = u.work_done;

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
                document.getElementById('modalPhotoCount').textContent = u.photos.length + ' photo' + (u.photos.length > 1 ? 's' : '');
                document.getElementById('modalPhotos').innerHTML = u.photos.map((p, i) =>
                    `<a href="${p}" target="_blank" title="Click to view full size"
                        style="display:block;border-radius:8px;overflow:hidden;border:1px solid var(--border);aspect-ratio:4/3;background:var(--surface-2);">
                        <img src="${p}"
                             style="width:100%;height:100%;object-fit:cover;transition:transform 0.2s,opacity 0.2s;display:block;"
                             onmouseover="this.style.transform='scale(1.04)';this.style.opacity='0.88';"
                             onmouseout="this.style.transform='scale(1)';this.style.opacity='1';"
                             onerror="this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;font-size:11px;color:var(--muted);padding:8px;text-align:center;\'>Image unavailable</div>';">
                    </a>`
                ).join('');
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
                                Approve & Advance Phase
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
    </script>
</body>
</html>