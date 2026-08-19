<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.client.header')

    <main class="admin-content">

            <!-- Page Header -->
            <div class="page-header" style="margin-bottom:28px;">
                <div>
                    <h1 class="page-title">My Projects</h1>
                    <p class="page-subtitle">View and monitor the progress of your assigned construction projects, including project phases, updates, milestones, and completion status.</p>
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

            @if($projects->isNotEmpty())
            <div class="filter-tabs" style="margin-bottom:20px;width:fit-content;">
                <button type="button" class="filter-tab active" data-filter="all" onclick="filterProjects('all', this)">
                    All Projects
                    <span class="filter-count">{{ $projects->count() }}</span>
                </button>
                <button type="button" class="filter-tab" data-filter="active" onclick="filterProjects('active', this)">
                    Active
                    <span class="filter-count">{{ $projects->where('status', '!=', 'completed')->count() }}</span>
                </button>
                <button type="button" class="filter-tab" data-filter="completed" onclick="filterProjects('completed', this)">
                    Completed
                    <span class="filter-count">{{ $projects->where('status', 'completed')->count() }}</span>
                </button>
            </div>
            @endif

            @if($projects->isNotEmpty())
            <div class="project-scroll-wrap" style="max-height:640px;overflow-y:auto;padding:4px 4px 0;margin:0 -4px;">
            @foreach($projects as $project)
            @php
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
            @endphp
            <div class="card" style="margin-bottom:12px;" data-status="{{ $project->status }}">
                <div class="card-header project-card-header" style="padding:14px 18px 12px;">
                    <div>
                        <div class="card-title" style="font-size:14.5px;font-weight:900;">{{ $project->name }}</div>
                        <div style="font-size:11.5px;color:var(--muted);margin-top:3px;display:flex;gap:14px;flex-wrap:wrap;">
                            <span><strong>Type:</strong> {{ $project->tank_type }}</span>
                            <span><strong>Capacity:</strong> {{ $project->capacity }}</span>
                            <span><strong>Created:</strong> {{ $project->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                    <div class="project-card-actions" style="display:flex;align-items:center;gap:8px;">
                        @if($project->status === 'completed')
                            <span class="status-badge completed">Completed</span>
                        @elseif($project->status === 'ongoing')
                            <span class="status-badge ongoing">In Progress</span>
                        @elseif($project->status === 'archived')
                            <span class="status-badge archived">Archived</span>
                        @else
                            <span class="status-badge planning">Planning</span>
                        @endif
                        <a href="{{ route('client.project_view', $project->id) }}" class="btn btn-outline btn-sm">
                            <i data-lucide="external-link" style="width:13px;height:13px;"></i> View Details
                        </a>
                    </div>
                </div>

                <div class="card-body" style="padding:16px 18px;">
                    <div class="project-progress-container">
                        <div>
                            <div class="progress-wrap" style="margin-bottom:0;">
                                <div class="progress-label">
                                    <span style="font-weight:700;font-size:12px;">Project Progress</span>
                                    <span style="font-weight:900;color:var(--dark);font-size:12px;">{{ $project->progress }}%</span>
                                </div>
                                <div class="progress-bar" style="height:7px;">
                                    <div class="progress-fill"
                                         style="width:{{ $project->progress }}%;
                                         background:{{ $project->status === 'completed' ? 'var(--success)' : 'var(--accent)' }};
                                         border-radius:999px;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="project-progress-info">
                            <div class="project-info-item">
                                <div class="project-info-label">Start Date</div>
                                <div class="project-info-value">{{ $project->start_date->format('M d, Y') }}</div>
                            </div>
                            <div class="project-info-item">
                                <div class="project-info-label">End Date</div>
                                <div class="project-info-value">{{ $project->end_date->format('M d, Y') }}</div>
                            </div>
                            <div class="project-info-item">
                                <div class="project-info-label">Current Phase</div>
                                <div class="project-info-value project-phase-current">
                                    {{ $phaseLabels[$project->current_phase] ?? ucfirst(str_replace('_', ' ', $project->current_phase)) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($project->status === 'completed')
                    @php $review = $reviews->get($project->id); @endphp
                    <div class="project-review-row" style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                        @if($review)
                            <div>
                                <div style="display:flex;gap:2px;margin-bottom:4px;">
                                    @for($i=1;$i<=5;$i++)
                                        <i data-lucide="star" style="width:13px;height:13px;color:{{ $i <= $review->rating ? '#F59E0B' : 'var(--border)' }};{{ $i <= $review->rating ? 'fill:#F59E0B;' : '' }}"></i>
                                    @endfor
                                </div>
                                <p style="font-size:11.5px;color:var(--muted);max-width:480px;margin:0;">{{ $review->comment }}</p>
                            </div>
                        @else
                            <span style="font-size:11.5px;color:var(--muted);">Share your experience with this project.</span>
                        @endif
                        <button type="button" class="btn btn-sm" style="background:#F59E0B;color:#fff;font-weight:700;padding:6px 14px;border-radius:10px;font-size:12px;display:flex;align-items:center;gap:5px;border:none;cursor:pointer;"
                            onclick="openReviewModal({{ $project->id }}, {{ \Illuminate\Support\Js::from($project->name) }}, {{ $review->rating ?? 0 }}, {{ \Illuminate\Support\Js::from($review->comment ?? '') }})">
                            <i data-lucide="star" style="width:13px;height:13px;"></i> {{ $review ? 'Edit Review' : 'Leave a Review' }}
                        </button>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
            </div>
            @else
            <div class="card" style="text-align:center;padding:64px 32px;">
                <div style="margin-bottom:20px;">
                    <i data-lucide="folder-x" style="width:56px;height:56px;color:var(--border);display:inline-block;"></i>
                </div>
                <h2 style="font-size:20px;font-weight:900;color:var(--dark);margin-bottom:10px;">No Projects Available</h2>
                <p style="font-size:14px;color:var(--muted);max-width:420px;margin:0 auto;line-height:1.6;">
                    You currently do not have any assigned projects.<br>
                    Once the administrator creates and assigns a project to your account, it will appear here.
                </p>
            </div>
            @endif

            @if($projects->isNotEmpty())
            <div class="card" id="noFilteredProjects" style="display:none;text-align:center;padding:64px 32px;">
                <div style="margin-bottom:20px;">
                    <i data-lucide="search-x" style="width:56px;height:56px;color:var(--border);display:inline-block;"></i>
                </div>
                <h2 style="font-size:20px;font-weight:900;color:var(--dark);margin-bottom:10px;" id="noFilteredProjectsTitle">No Projects Found</h2>
                <p style="font-size:14px;color:var(--muted);max-width:420px;margin:0 auto;line-height:1.6;" id="noFilteredProjectsMsg">
                    No projects match this filter.
                </p>
            </div>
            @endif

            <style>
                .project-scroll-wrap .card {
                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                }
                .project-scroll-wrap .card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 16px 34px rgba(0,0,0,.10);
                }
                .project-scroll-wrap::-webkit-scrollbar {
                    width: 8px;
                }
                .project-scroll-wrap::-webkit-scrollbar-thumb {
                    background: var(--border);
                    border-radius: 999px;
                }
                .project-scroll-wrap::-webkit-scrollbar-track {
                    background: transparent;
                }
            </style>

    </main>

    {{-- ===================== REVIEW MODAL ===================== --}}
    <div class="modal-overlay" id="reviewModal">
        <div class="modal-card" style="max-width:520px;">
            <div class="modal-header">
                <div>
                    <h2>Leave a Review</h2>
                    <p id="reviewProjectName">Share your experience with this project.</p>
                </div>
                <button class="modal-close" type="button" onclick="closeModal('reviewModal')">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" id="reviewForm" action="">
                @csrf
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Your Rating </label>
                        <div class="star-rating" id="reviewStarRating">
                            @for($i=1;$i<=5;$i++)
                            <button type="button" data-value="{{ $i }}" onclick="setReviewRating({{ $i }})">
                                <i data-lucide="star"></i>
                            </button>
                            @endfor
                        </div>
                        <input type="hidden" name="rating" id="reviewRatingInput" required>
                    </div>
                    <div class="form-group form-group-full">
                        <label>Your Review </label>
                        <textarea name="comment" id="reviewCommentInput" rows="4" maxlength="1000" placeholder="Tell us about your experience with this project..." required></textarea>
                    </div>
                </div>
                <div style="padding:14px 20px;border-top:1px solid rgba(0,0,0,0.06);display:flex;justify-content:flex-end;gap:10px;">
                    <button type="button" class="cancel-btn" onclick="closeModal('reviewModal')">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="check-circle" style="width:15px;height:15px;"></i>
                        Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        function openModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
        }
        function closeModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
        }

        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) closeModal(overlay.id);
            });
        });

        function setReviewRating(value) {
            document.getElementById('reviewRatingInput').value = value;
            document.querySelectorAll('#reviewStarRating button').forEach(function (btn) {
                btn.classList.toggle('filled', parseInt(btn.dataset.value, 10) <= value);
            });
        }

        function openReviewModal(projectId, projectName, rating, comment) {
            document.getElementById('reviewForm').action = '/client/projects/' + projectId + '/review';
            document.getElementById('reviewProjectName').textContent = 'Share your experience with "' + projectName + '".';
            document.getElementById('reviewCommentInput').value = comment || '';
            setReviewRating(rating || 0);
            openModal('reviewModal');
        }

        function filterProjects(filter, btn) {
            document.querySelectorAll('.filter-tab[data-filter]').forEach(function (tab) {
                tab.classList.remove('active');
            });
            btn.classList.add('active');

            var visible = 0;
            document.querySelectorAll('.card[data-status]').forEach(function (card) {
                var status = card.dataset.status;
                var show = filter === 'all'
                    || (filter === 'completed' && status === 'completed')
                    || (filter === 'active' && status !== 'completed');
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            var emptyState = document.getElementById('noFilteredProjects');
            if (emptyState) {
                if (visible === 0) {
                    var titleEl = document.getElementById('noFilteredProjectsTitle');
                    var msgEl   = document.getElementById('noFilteredProjectsMsg');
                    if (filter === 'active') {
                        titleEl.textContent = 'No Active Projects';
                        msgEl.textContent   = 'You don\'t have any active projects right now.';
                    } else if (filter === 'completed') {
                        titleEl.textContent = 'No Completed Projects';
                        msgEl.textContent   = 'None of your projects have been completed yet.';
                    } else {
                        titleEl.textContent = 'No Projects Found';
                        msgEl.textContent   = 'No projects match this filter.';
                    }
                    emptyState.style.display = '';
                    if (window.lucide) lucide.createIcons();
                } else {
                    emptyState.style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>
