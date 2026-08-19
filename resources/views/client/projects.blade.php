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
            <div class="emp-tabs" style="margin-bottom:20px;">
                <button class="emp-tab active" data-filter="all" onclick="filterProjects('all', this)">All Projects</button>
                <button class="emp-tab" data-filter="active" onclick="filterProjects('active', this)">Active</button>
                <button class="emp-tab" data-filter="completed" onclick="filterProjects('completed', this)">Completed</button>
            </div>
            @endif

            @forelse($projects as $project)
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
                $currentIndex = array_search($project->current_phase, $phases);
            @endphp
            <div class="card" style="margin-bottom:20px;" data-status="{{ $project->status }}">
                <div class="card-header project-card-header">
                    <div>
                        <div class="card-title" style="font-size:17px;font-weight:900;">{{ $project->name }}</div>
                        <div style="font-size:12.5px;color:var(--muted);margin-top:4px;display:flex;gap:16px;flex-wrap:wrap;">
                            <span><strong>Type:</strong> {{ $project->tank_type }}</span>
                            <span><strong>Capacity:</strong> {{ $project->capacity }}</span>
                            <span><strong>Created:</strong> {{ $project->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                    <div class="project-card-actions" style="display:flex;align-items:center;gap:10px;">
                        @if($project->status === 'completed')
                            <span class="status-badge completed">Completed</span>
                        @elseif($project->status === 'ongoing')
                            <span class="status-badge ongoing">In Progress</span>
                        @elseif($project->status === 'archived')
                            <span class="status-badge archived">Archived</span>
                        @else
                            <span class="status-badge planning">Planning</span>
                        @endif
                        <a href="{{ route('client.project_view', $project->id) }}" class="btn btn-sm" style="background:var(--dark);color:var(--white);font-weight:700;padding:8px 18px;border-radius:12px;font-size:13px;display:flex;align-items:center;gap:6px;text-decoration:none;">
                            <i data-lucide="eye" style="width:14px;height:14px;"></i> View Details
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="project-card-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">
                        <!-- Progress -->
                        <div>
                            <div class="progress-wrap" style="margin-bottom:0;">
                                <div class="progress-label">
                                    <span style="font-weight:700;font-size:13px;">Project Progress</span>
                                    <span style="font-weight:900;color:var(--dark);">{{ $project->progress }}%</span>
                                </div>
                                <div class="progress-bar" style="height:10px;">
                                    <div class="progress-fill"
                                         style="width:{{ $project->progress }}%;
                                         background:{{ $project->status === 'completed' ? 'var(--success)' : 'var(--accent)' }};
                                         border-radius:999px;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Info grid -->
                        <div class="project-info-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div style="background:var(--cream-soft);border-radius:12px;padding:12px 14px;">
                                <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Current Phase</div>
                                <div style="font-size:13px;font-weight:800;color:var(--accent);">{{ $phaseLabels[$project->current_phase] ?? ucfirst(str_replace('_', ' ', $project->current_phase)) }}</div>
                            </div>
                            <div style="background:var(--cream-soft);border-radius:12px;padding:12px 14px;">
                                <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Timeline</div>
                                <div style="font-size:12px;font-weight:700;color:var(--dark);">
                                    {{ $project->start_date->format('M d, Y') }} → {{ $project->end_date->format('M d, Y') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mini phase tracker -->
                    <div class="phase-tracker" style="margin-top:20px;display:flex;gap:0;overflow:hidden;border-radius:12px;border:1px solid var(--border);">
                        @foreach($phases as $i => $phase)
                        @php
                            $isDone    = $i < $currentIndex;
                            $isCurrent = $i === $currentIndex;
                            $bg    = $isDone ? 'var(--success)' : ($isCurrent ? 'var(--accent)' : 'var(--cream-soft)');
                            $color = ($isDone || $isCurrent) ? '#fff' : 'var(--muted)';
                            $fw    = $isCurrent ? '800' : '600';
                        @endphp
                        <div class="phase-tracker-item" style="flex:1;text-align:center;padding:8px 4px;background:{{ $bg }};color:{{ $color }};font-size:10px;font-weight:{{ $fw }};border-right:{{ $i < count($phases)-1 ? '1px solid rgba(0,0,0,.06)' : 'none' }};">
                            {{ $phaseLabels[$phase] ?? $phase }}
                        </div>
                        @endforeach
                    </div>

                    @if($project->status === 'completed')
                    @php $review = $reviews->get($project->id); @endphp
                    <div class="project-review-row" style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                        @if($review)
                            <div>
                                <div style="display:flex;gap:2px;margin-bottom:4px;">
                                    @for($i=1;$i<=5;$i++)
                                        <i data-lucide="star" style="width:14px;height:14px;color:{{ $i <= $review->rating ? 'var(--accent)' : 'var(--border)' }};{{ $i <= $review->rating ? 'fill:var(--accent);' : '' }}"></i>
                                    @endfor
                                </div>
                                <p style="font-size:12.5px;color:var(--muted);max-width:480px;margin:0;">{{ $review->comment }}</p>
                            </div>
                        @else
                            <span style="font-size:12.5px;color:var(--muted);">Share your experience with this project.</span>
                        @endif
                        <button type="button" class="btn btn-sm" style="background:var(--accent);color:#fff;font-weight:700;padding:8px 18px;border-radius:12px;font-size:13px;display:flex;align-items:center;gap:6px;border:none;cursor:pointer;"
                            onclick="openReviewModal({{ $project->id }}, {{ \Illuminate\Support\Js::from($project->name) }}, {{ $review->rating ?? 0 }}, {{ \Illuminate\Support\Js::from($review->comment ?? '') }})">
                            <i data-lucide="star" style="width:14px;height:14px;"></i> {{ $review ? 'Edit Review' : 'Leave a Review' }}
                        </button>
                    </div>
                    @endif
                </div>
            </div>
            @empty
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
            @endforelse

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
            document.querySelectorAll('.emp-tab[data-filter]').forEach(function (tab) {
                tab.classList.remove('active');
            });
            btn.classList.add('active');

            document.querySelectorAll('.card[data-status]').forEach(function (card) {
                var status = card.dataset.status;
                var show = filter === 'all'
                    || (filter === 'completed' && status === 'completed')
                    || (filter === 'active' && status !== 'completed');
                card.style.display = show ? '' : 'none';
            });
        }
    </script>
</body>
</html>
