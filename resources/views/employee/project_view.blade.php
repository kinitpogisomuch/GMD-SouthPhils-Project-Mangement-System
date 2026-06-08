<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project View | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
</head>
<body>

    @include('partials.employee.header')

    <div class="admin-layout">
        @include('partials.employee.sidebar')

        <main class="admin-content">

            <!-- Page Header -->
            <div class="pv-page-header">
                <div>
                    <h1>{{ $project->name }}</h1>
                    <p>{{ $project->client }} &nbsp;·&nbsp; {{ $project->tank_type }} &nbsp;·&nbsp; {{ $project->capacity }}</p>
                </div>
                <a href="{{ route('employee.projects') }}" class="pv-back-link">
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
                            <label class="log-label">DATE OF WORK *</label>
                            <input type="date" name="date_of_work" class="log-input"
                                   value="{{ old('date_of_work') }}" required>
                        </div>

                        <div class="form-group" style="margin-top:12px;">
                            <label class="log-label">WORK DONE *</label>
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
                            <label class="log-label">SITE PHOTOS *</label>
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
                        <label class="log-label">DATE OF WORK *</label>
                        <input type="date" name="date_of_work" class="log-input"
                               value="{{ old('date_of_work') }}" required>
                    </div>

                    <div class="form-group" style="margin-top:14px;">
                        <label class="log-label">WORK DONE *</label>
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
                        <label class="log-label">SITE PHOTOS *</label>
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

            <!-- Progress History (approved only) -->
            @if($updates->count() > 0)
            <div class="emp-pv-card" style="margin-top:20px;">
                <h3 class="emp-pv-card-title">
                    <i data-lucide="history"></i>
                    Progress History
                </h3>
                @foreach($updates as $update)
                <div style="border-bottom:1px solid var(--border);padding:16px 0;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <div style="font-size:13.5px;font-weight:700;color:var(--text-primary);">
                            {{ ucfirst(str_replace('_', ' ', $update->phase)) }} Phase
                            @if($update->update_label === 'revision')
                            <span style="font-size:11px;background:#dcfce7;color:#14532d;padding:2px 8px;border-radius:20px;margin-left:6px;font-weight:700;">
                                Revision Submission
                            </span>
                            @endif
                        </div>
                        <div style="font-size:12px;color:var(--text-muted);">
                            {{ $update->date_of_work->format('M d, Y') }}
                        </div>
                    </div>
                    <div style="font-size:13px;color:var(--text-secondary);margin-bottom:8px;">
                        {{ $update->work_done }}
                    </div>
                    @if($update->issues)
                    <div style="font-size:12.5px;color:var(--warning);margin-bottom:8px;">⚠ {{ $update->issues }}</div>
                    @endif
                    @if($update->photos && count($update->photos) > 0)
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
                        @foreach($update->photos as $photo)
                        <a href="{{ $photo }}" target="_blank">
                            <img src="{{ $photo }}" style="width:80px;height:60px;object-fit:cover;border-radius:6px;border:1px solid var(--border);">
                        </a>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            <!-- Bottom Grid -->
            <div class="pv-grid-2" style="margin-top:20px;">
                <div class="emp-pv-card">
                    <h3 class="emp-pv-card-title">
                        <i data-lucide="layers"></i>
                        Phase Details
                    </h3>
                    <div id="empPhaseDetailsList"></div>
                </div>
                <div class="emp-pv-card">
                    <h3 class="emp-pv-card-title">
                        <i data-lucide="calendar-range"></i>
                        Timeline Overview
                    </h3>
                    <p style="font-size:12.5px;color:var(--text-secondary);font-weight:600;margin-bottom:14px;">
                        Materials arrival = Day 0 (project clock start)
                    </p>
                    <div style="overflow-x:auto;">
                        <table class="emp-timeline-table">
                            <thead>
                                <tr><th>Phase</th><th>Start</th><th>End</th><th>Duration</th></tr>
                            </thead>
                            <tbody id="empTimelineBody"></tbody>
                        </table>
                    </div>
                    <div id="empTimelineTotal"></div>
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
    </div>

    @php
        $progress     = $project->progress;
        $status       = strtolower($project->status);
        $duration     = $project->duration ?? 'N/A';
        $startDate    = $project->start_date->format('Y-m-d');
        $currentPhase = $project->current_phase;
    @endphp

    <script>
        const PROJECT_PROGRESS      = {{ $progress }};
        const PROJECT_STATUS        = "{{ $status }}";
        const PROJECT_DURATION      = "{{ $duration }}";
        const START_DATE_STR        = "{{ $startDate }}";
        const PROJECT_CURRENT_PHASE = "{{ $currentPhase }}";
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

        function toggleSidebar() {
            document.querySelector('.employee-sidebar').classList.toggle('open');
        }

        function initializeEmployeeDropdown() {
            const dropdown = document.querySelector(".employee-dropdown");
            const button   = document.getElementById("employeeDropdownBtn");
            if (!dropdown || !button) return;
            button.addEventListener("click", function(event) {
                event.stopPropagation();
                const notificationDropdown = document.querySelector(".notification-dropdown");
                if (notificationDropdown) notificationDropdown.classList.remove("open");
                dropdown.classList.toggle("open");
            });
        }

        function initializeNotificationDropdown() {
            const dropdown = document.querySelector(".notification-dropdown");
            const button   = document.getElementById("notificationDropdownBtn");
            if (!dropdown || !button) return;
            button.addEventListener("click", function(event) {
                event.stopPropagation();
                const employeeDropdown = document.querySelector(".employee-dropdown");
                if (employeeDropdown) employeeDropdown.classList.remove("open");
                dropdown.classList.toggle("open");
            });
        }

        function closeDropdownsOnOutsideClick() {
            document.addEventListener("click", function(event) {
                if (event.target.closest('a') || event.target.closest('button')) return;
                const employeeDropdown     = document.querySelector(".employee-dropdown");
                const notificationDropdown = document.querySelector(".notification-dropdown");
                if (employeeDropdown)     employeeDropdown.classList.remove("open");
                if (notificationDropdown) notificationDropdown.classList.remove("open");
            });
        }

        initializeEmployeeDropdown();
        initializeNotificationDropdown();
        closeDropdownsOnOutsideClick();
    </script>
</body>
</html>