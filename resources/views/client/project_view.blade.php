<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project->name }} | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.client.header')

    <div class="admin-layout">
        @include('partials.client.sidebar')

        <main class="admin-content">

            @php
                $phases = ['planning','procurement','matl_prep','fabrication','inspection','painting','completion','delivery'];
                $phaseLabels = [
                    'planning'    => 'Planning',
                    'procurement' => 'Procurement',
                    'matl_prep'   => 'Material Preparation',
                    'fabrication' => 'Fabrication',
                    'inspection'  => 'Inspection',
                    'painting'    => 'Painting',
                    'completion'  => 'Completion',
                    'delivery'    => 'Delivery',
                ];
                $statusClass = match($project->status) {
                    'completed' => 'completed',
                    'ongoing'   => 'ongoing',
                    'archived'  => 'archived',
                    default     => 'planning',
                };
                $statusLabel = match($project->status) {
                    'completed' => 'Completed',
                    'ongoing'   => 'In Progress',
                    'archived'  => 'Archived',
                    default     => 'Planning',
                };
                $statusIcon = match($project->status) {
                    'completed' => 'check-circle',
                    'ongoing'   => 'zap',
                    'archived'  => 'archive',
                    default     => 'clock',
                };
                $currentPhaseLabel = $phaseLabels[$project->current_phase] ?? ucfirst(str_replace('_', ' ', $project->current_phase));
                $payment = $project->getPaymentRecord();
            @endphp

            <!-- Breadcrumb -->
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('client.projects') }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    Projects
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="color:var(--dark);font-weight:700;">{{ $project->name }}</span>
            </div>

            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap;">
                        <h1 class="page-title">{{ $project->name }}</h1>
                        <span class="pv-status-pill {{ $statusClass }}">
                            <i data-lucide="{{ $statusIcon }}" style="width:13px;height:13px;"></i>
                            {{ $statusLabel }}
                        </span>
                    </div>
                    <p class="page-subtitle">
                        {{ $project->client }}&nbsp;·&nbsp;{{ $project->tank_type }}&nbsp;·&nbsp;{{ $project->capacity }}
                    </p>
                </div>
            </div>

            @if(session('success'))
            <div class="alert-banner success">
                <i data-lucide="check-circle"></i>
                {{ session('success') }}
            </div>
            @endif

            <!-- Phase Tracker Card — matches admin style exactly -->
            <div class="tracker-card">
                <div class="tracker-card-header">
                    <div class="tracker-title">
                        <i data-lucide="layers"></i>
                        Fabrication Phase Tracker &nbsp;·&nbsp; {{ $project->capacity }} {{ $project->tank_type }}
                    </div>
                    <span class="tracker-progress-badge">{{ $project->progress }}%</span>
                </div>
                <div class="phase-steps" id="phaseSteps"></div>

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
                <div class="phase-steps" style="margin-top:10px;">
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
                @endif
            </div>

            @if($project->current_phase === 'planning')
            @php
                $shopDrawing   = $project->phaseData('planning.shop_drawing', []);
                $sdStatus      = $shopDrawing['status'] ?? 'not_submitted';
                $quotationData = $project->phaseData('planning.quotation', []);
            @endphp

            <!-- Shop Drawing & Tank Design -->
            <div class="pv-card" style="margin-top:20px;">
                <div class="pv-card-title">
                    <i data-lucide="ruler"></i>
                    Shop Drawing &amp; Tank Design
                </div>

                @if($sdStatus === 'not_submitted')
                    <div class="alert-banner info">
                        <i data-lucide="clock"></i>
                        Awaiting shop drawing and tank design documents from the project owner.
                    </div>
                @else
                    @if($sdStatus === 'pending_approval')
                        <div class="alert-banner warning">
                            <i data-lucide="alert-triangle"></i>
                            Please review the shop drawing and tank design documents below, then approve or request a revision.
                        </div>
                    @elseif($sdStatus === 'approved')
                        <div class="alert-banner success">
                            <i data-lucide="check-circle"></i>
                            You approved the shop drawing and tank design documents.
                        </div>
                    @elseif($sdStatus === 'revision_requested')
                        <div class="alert-banner warning">
                            <i data-lucide="rotate-ccw"></i>
                            You requested a revision: {{ $shopDrawing['revision_notes'] ?? '' }}
                        </div>
                    @endif

                    <div class="pv-stat-row">
                        <div class="pv-stat-box" style="flex:1;">
                            <div class="pv-stat-label">Shop Drawing Files</div>
                            <div style="display:flex;flex-direction:column;gap:8px;">
                                @forelse(($shopDrawing['shop_drawing_files'] ?? []) as $file)
                                    <a href="{{ $file }}" target="_blank" style="display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:700;color:var(--accent);text-decoration:none;">
                                        <i data-lucide="file-text" style="width:14px;height:14px;"></i>
                                        {{ basename(parse_url($file, PHP_URL_PATH)) }}
                                    </a>
                                @empty
                                    <span style="font-size:12.5px;color:var(--muted);">No files uploaded.</span>
                                @endforelse
                            </div>
                        </div>
                        <div class="pv-stat-box" style="flex:1;">
                            <div class="pv-stat-label">Tank Design Files</div>
                            <div style="display:flex;flex-direction:column;gap:8px;">
                                @forelse(($shopDrawing['tank_design_files'] ?? []) as $file)
                                    <a href="{{ $file }}" target="_blank" style="display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:700;color:var(--accent);text-decoration:none;">
                                        <i data-lucide="file-text" style="width:14px;height:14px;"></i>
                                        {{ basename(parse_url($file, PHP_URL_PATH)) }}
                                    </a>
                                @empty
                                    <span style="font-size:12.5px;color:var(--muted);">No files uploaded.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    @if($sdStatus === 'pending_approval')
                    <div style="display:flex;gap:10px;margin-top:14px;align-items:flex-start;flex-wrap:wrap;">
                        <form method="POST" action="{{ route('client.project.shop_drawing.approve', $project->id) }}">
                            @csrf
                            <button type="submit" class="save-btn">
                                <i data-lucide="check-circle"></i>
                                Approve
                            </button>
                        </form>
                        <button type="button" class="cancel-btn" id="openRevisionModalBtn">
                            <i data-lucide="rotate-ccw"></i>
                            Request Revision
                        </button>
                    </div>
                    @endif
                @endif
            </div>

            <!-- Project Quotation -->
            @if(($quotationData['status'] ?? null) === 'sent')
            <div class="pv-card" style="margin-top:20px;">
                <div class="pv-card-title">
                    <i data-lucide="receipt"></i>
                    Project Quotation
                </div>
                <div class="alert-banner info">
                    <i data-lucide="info"></i>
                    The project quotation must be settled before proceeding to the next phase.
                </div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    @forelse(($quotationData['files'] ?? []) as $file)
                        <a href="{{ $file }}" target="_blank" style="display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:700;color:var(--accent);text-decoration:none;">
                            <i data-lucide="file-text" style="width:14px;height:14px;"></i>
                            {{ basename(parse_url($file, PHP_URL_PATH)) }}
                        </a>
                    @empty
                        <span style="font-size:12.5px;color:var(--muted);">No quotation files uploaded.</span>
                    @endforelse
                </div>
            </div>
            @endif
            @endif

            <!-- Payment Status -->
            @if($payment)
            <div class="pv-card" style="margin-top:20px;">
                <div class="pv-card-title">
                    <i data-lucide="credit-card"></i>
                    Payment Status
                </div>
                <div class="pv-stat-row">
                    <div class="pv-stat-box">
                        <div class="pv-stat-label">Status</div>
                        <div class="pv-stat-value">
                            <span class="pv-status-pill {{ \App\Models\Payment::statusBadgeClass($payment->status) }}">
                                {{ $payment->status }}
                            </span>
                        </div>
                    </div>
                    <div class="pv-stat-box">
                        <div class="pv-stat-label">Remaining Balance</div>
                        <div class="pv-stat-value">₱{{ number_format($payment->balance, 2) }}</div>
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <a href="{{ route('client.payments.show', $payment->id) }}" class="cancel-btn" style="display:inline-flex;text-decoration:none;">
                        <i data-lucide="external-link"></i>
                        View Payment Details
                    </a>
                </div>
            </div>
            @endif

            <!-- Two-column layout: Project Info (left) + Progress History (right) -->
            <div class="pv-grid">

                <!-- LEFT: Project Information & Stats -->
                <div class="pv-card">
                    <div class="pv-card-title">
                        <i data-lucide="clipboard-list"></i>
                        Project Information
                    </div>

                    <!-- Progress bar -->
                    <div style="margin-bottom:18px;">
                        <div class="progress-wrap" style="margin-top:0;">
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
                    </div>

                    <!-- Key stats grid -->
                    <div class="pv-stat-row">
                        <div class="pv-stat-box">
                            <div class="pv-stat-label">Current Phase</div>
                            <div class="pv-stat-value accent">{{ $currentPhaseLabel }}</div>
                        </div>
                        <div class="pv-stat-box">
                            <div class="pv-stat-label">Status</div>
                            <div class="pv-stat-value">{{ $statusLabel }}</div>
                        </div>
                    </div>

                    <div class="pv-stat-row">
                        <div class="pv-stat-box">
                            <div class="pv-stat-label">Start Date</div>
                            <div class="pv-stat-value">{{ $project->start_date->format('M d, Y') }}</div>
                        </div>
                        <div class="pv-stat-box">
                            <div class="pv-stat-label">End Date</div>
                            <div class="pv-stat-value">{{ $project->end_date->format('M d, Y') }}</div>
                        </div>
                    </div>

                    <div class="pv-stat-row">
                        <div class="pv-stat-box">
                            <div class="pv-stat-label">Tank Type</div>
                            <div class="pv-stat-value">{{ $project->tank_type }}</div>
                        </div>
                        <div class="pv-stat-box">
                            <div class="pv-stat-label">Capacity</div>
                            <div class="pv-stat-value">{{ $project->capacity }}</div>
                        </div>
                    </div>

                    @if($project->dimensions)
                    <div style="margin-bottom:10px;">
                        <div class="pv-stat-box">
                            <div class="pv-stat-label">Dimensions</div>
                            <div class="pv-stat-value">{{ $project->dimensions }}</div>
                        </div>
                    </div>
                    @endif

                    <div class="pv-stat-row" style="margin-bottom:0;">
                        <div class="pv-stat-box">
                            <div class="pv-stat-label">Date Created</div>
                            <div class="pv-stat-value">{{ $project->created_at->format('M d, Y') }}</div>
                        </div>
                        <div class="pv-stat-box">
                            <div class="pv-stat-label">Duration</div>
                            <div class="pv-stat-value">{{ $project->duration ?? 'N/A' }}</div>
                        </div>
                    </div>

                    @if($project->notes)
                    <div style="margin-top:10px;background:var(--cream-soft);border:1px solid var(--border);border-radius:14px;padding:14px 16px;">
                        <div class="pv-stat-label" style="margin-bottom:6px;">Notes</div>
                        <div style="font-size:13px;color:var(--dark);line-height:1.6;">{{ $project->notes }}</div>
                    </div>
                    @endif
                </div>

                <!-- RIGHT: Progress History -->
                <div class="pv-card">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
                        <div class="pv-card-title" style="margin-bottom:0;">
                            <i data-lucide="history"></i>
                            Progress History
                        </div>
                        <span style="font-size:12px;color:var(--muted);font-weight:700;">
                            {{ $updates->count() }} {{ Str::plural('entry', $updates->count()) }}
                        </span>
                    </div>

                    <div class="pv-history-scroll">
                        @forelse($updates as $update)
                        <div class="pv-history-item" data-update-id="{{ $update->id }}">
                            <!-- Entry header -->
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;gap:8px;">
                                <div>
                                    <div style="font-size:13.5px;font-weight:900;color:var(--dark);margin-bottom:3px;">
                                        {{ $phaseLabels[$update->phase] ?? ucfirst(str_replace('_', ' ', $update->phase)) }} Phase
                                        @if($update->update_label === 'revision')
                                        <span style="font-size:10px;background:#dbeafe;color:#1e40af;padding:2px 7px;border-radius:20px;margin-left:4px;font-weight:700;">Revision</span>
                                        @endif
                                        <span class="pv-new-badge">New</span>
                                    </div>
                                    <div style="font-size:11.5px;color:var(--muted);display:flex;align-items:center;gap:4px;">
                                        <i data-lucide="user" style="width:11px;height:11px;"></i>
                                        {{ $update->submittedBy?->full_name ?? 'Team' }}
                                        &nbsp;·&nbsp;
                                        <i data-lucide="calendar" style="width:11px;height:11px;"></i>
                                        {{ $update->created_at->format('M d, Y') }}
                                    </div>
                                </div>
                                <span style="display:inline-flex;align-items:center;gap:4px;background:#dcfce7;border:1.5px solid #86efac;color:#14532d;font-size:10.5px;font-weight:800;padding:4px 10px;border-radius:20px;white-space:nowrap;flex-shrink:0;">
                                    <i data-lucide="check" style="width:10px;height:10px;"></i>
                                    Approved
                                </span>
                            </div>

                            <!-- Work done -->
                            @if(trim((string) $update->work_done) !== '')
                            @php $workDoneText = trim($update->work_done); @endphp
                            <div style="margin-bottom:10px;">
                                <div style="font-size:10.5px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;">Work Done</div>
                                <div style="font-size:13px;color:var(--dark);line-height:1.55;white-space:pre-line;" class="pv-work-text" data-full="{{ e($workDoneText) }}">@if(strlen($workDoneText) > 160){{ substr($workDoneText, 0, 160) }}<span class="pv-ellipsis">…</span><span class="pv-rest" style="display:none;">{{ substr($workDoneText, 160) }}</span> <button type="button" onclick="toggleWorkText(this)" style="font-size:11px;font-weight:700;color:var(--accent);background:none;border:none;cursor:pointer;padding:0;margin-left:4px;">Read more</button>@else{{ $workDoneText }}@endif</div>
                            </div>
                            @endif

                            <!-- Observations -->
                            @if($update->issues)
                            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 12px;margin-bottom:10px;">
                                <div style="font-size:10.5px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Observations</div>
                                <div style="font-size:12.5px;color:#92400e;line-height:1.5;">{{ Str::limit($update->issues, 120) }}</div>
                            </div>
                            @endif

                            <!-- Photos -->
                            @if($update->photos && count($update->photos) > 0)
                            <div>
                                <div style="font-size:10.5px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">
                                    Site Photos ({{ count($update->photos) }})
                                </div>
                                <div class="pv-photo-grid">
                                    @foreach($update->photos as $photo)
                                    @php
                                        $photoExt = strtolower(pathinfo(parse_url($photo, PHP_URL_PATH), PATHINFO_EXTENSION));
                                        $isImageFile = in_array($photoExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']);
                                    @endphp
                                    <a href="{{ $photo }}" target="_blank" class="pv-photo-thumb {{ $isImageFile ? '' : 'is-file' }}">
                                        @if($isImageFile)
                                            <img src="{{ $photo }}" alt="Progress photo"
                                                 loading="lazy"
                                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                            <span class="pv-photo-fallback" style="display:none;"><i data-lucide="file-text"></i></span>
                                        @else
                                            <span class="pv-photo-fallback"><i data-lucide="file-text"></i></span>
                                        @endif
                                    </a>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                        @empty
                        <div class="empty-state">
                            <i data-lucide="image-off"></i>
                            <p>No approved updates yet.</p>
                            <p style="font-size:12px;font-weight:500;color:var(--muted-light);margin-top:4px;">Updates will appear here once the admin approves them.</p>
                        </div>
                        @endforelse
                    </div>
                </div>

            </div><!-- /.pv-grid -->

        </main>
    </div>

    @if(($sdStatus ?? null) === 'pending_approval')
    <div class="modal-overlay" id="revisionModal">
        <div class="modal-card" style="max-width:480px;">
            <div class="modal-header">
                <div>
                    <h2>Request Revision</h2>
                    <p>Let the project team know what needs to be changed.</p>
                </div>
                <button type="button" class="modal-close" id="closeRevisionModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('client.project.shop_drawing.request_revision', $project->id) }}">
                @csrf
                <div class="form-group">
                    <label>What needs to be revised?</label>
                    <textarea name="revision_notes" rows="4" placeholder="Describe the changes you'd like..." required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelRevisionModal">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="send"></i>
                        Submit Revision Request
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <script>
        const PROJECT_CURRENT_PHASE = "{{ $project->current_phase }}";
        const PROJECT_STATUS = "{{ $project->status }}";
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        /* ── Phase tracker ───────────────────────────────────────────────── */
        const PHASES = [
            { key: "planning",    icon: "pencil-ruler",  shortLabel: "Planning"    },
            { key: "procurement", icon: "shopping-cart", shortLabel: "Procurement" },
            { key: "matl_prep",   icon: "package",       shortLabel: "Matl. Prep"  },
            { key: "fabrication", icon: "hammer",        shortLabel: "Fabrication" },
            { key: "inspection",  icon: "search",        shortLabel: "Inspection"  },
            { key: "painting",    icon: "paintbrush",    shortLabel: "Painting"    },
            { key: "completion",  icon: "check-circle",  shortLabel: "Completion"  },
            { key: "delivery",    icon: "truck",         shortLabel: "Delivery"    }
        ];

        function getPhaseStatuses(currentPhase, isCompleted) {
            const statuses = [];
            let foundActive = false;
            PHASES.forEach(function(phase) {
                if (foundActive)                     statuses.push("pending");
                else if (phase.key === currentPhase) { statuses.push(isCompleted ? "done" : "active"); foundActive = true; }
                else                                 statuses.push("done");
            });
            return statuses;
        }

        function buildPhaseSteps(statuses) {
            const container = document.getElementById("phaseSteps");
            if (!container) return;
            container.innerHTML = "";
            PHASES.forEach(function(phase, index) {
                const status = statuses[index];
                const step   = document.createElement("div");
                step.className = "phase-step " + status;
                let iconHTML = status === "done"   ? '<i data-lucide="check"></i>'
                             : status === "active" ? '<i data-lucide="loader"></i>'
                             :                       '<i data-lucide="' + phase.icon + '"></i>';
                step.innerHTML =
                    '<div class="phase-step-box">' +
                        '<div class="phase-step-icon">' + iconHTML + '</div>' +
                        '<div class="phase-step-label">' + phase.shortLabel + '</div>' +
                    '</div>';
                container.appendChild(step);
            });
            if (typeof lucide !== "undefined") lucide.createIcons();
        }

        buildPhaseSteps(getPhaseStatuses(PROJECT_CURRENT_PHASE, PROJECT_STATUS === 'completed'));

        /* ── Read more / less toggle ─────────────────────────────────────── */
        function toggleWorkText(btn) {
            const rest = btn.previousElementSibling;
            const ellipsis = btn.previousElementSibling.previousElementSibling;
            if (rest.style.display === 'none') {
                rest.style.display = 'inline';
                if (ellipsis) ellipsis.style.display = 'none';
                btn.textContent = 'Show less';
            } else {
                rest.style.display = 'none';
                if (ellipsis) ellipsis.style.display = 'inline';
                btn.textContent = 'Read more';
            }
        }

        /* ── Modal helpers ───────────────────────────────────────────────── */
        function openModal(id) {
            const m = document.getElementById(id);
            if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
        }
        function closeModal(id) {
            const m = document.getElementById(id);
            if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
        }

        (function () {
            const openBtn = document.getElementById('openRevisionModalBtn');
            if (openBtn) openBtn.addEventListener('click', function () { openModal('revisionModal'); });

            ['closeRevisionModal', 'cancelRevisionModal'].forEach(function (id) {
                const btn = document.getElementById(id);
                if (btn) btn.addEventListener('click', function () { closeModal('revisionModal'); });
            });

            document.querySelectorAll('.modal-overlay').forEach(function (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === this) closeModal(this.id);
                });
            });
        })();

        /* ── "New" update highlight ──────────────────────────────────────── */
        (function () {
            const STORAGE_KEY = 'pv_seen_updates_{{ $project->id }}';
            let seen = [];
            try { seen = JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; } catch (e) { seen = []; }

            document.querySelectorAll('.pv-history-item[data-update-id]').forEach(function (item) {
                const id = item.getAttribute('data-update-id');
                if (seen.includes(id)) return;

                item.classList.add('is-new');
                item.addEventListener('click', function () {
                    item.classList.remove('is-new');
                    if (!seen.includes(id)) {
                        seen.push(id);
                        localStorage.setItem(STORAGE_KEY, JSON.stringify(seen));
                    }
                });
            });
        })();

        /* ── Match Progress History height to Project Information ──────────── */
        (function () {
            const infoCard    = document.querySelector('.pv-grid > .pv-card:first-child');
            const historyCard = document.querySelector('.pv-grid > .pv-card:last-child');
            if (!infoCard || !historyCard) return;

            function syncHeight() {
                if (window.matchMedia('(max-width: 900px)').matches) {
                    historyCard.style.height = '';
                    return;
                }
                historyCard.style.height = infoCard.offsetHeight + 'px';
            }

            syncHeight();
            window.addEventListener('resize', syncHeight);
            window.addEventListener('load', syncHeight);
        })();

    </script>
</body>
</html>
