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
            <div class="page-header cd-page-head">
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
            <div class="filter-tabs project-filter-tabs" style="margin-bottom:20px;width:fit-content;">
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
                    'delivery'    => 'For Pickup',
                ];
            @endphp
            <div class="card" style="margin-bottom:12px;" data-status="{{ $project->status }}">
                <div class="card-header project-card-header" style="padding:14px 18px 12px;">
                    <div>
                        <div class="card-title" style="font-size:14.5px;font-weight:900;">{{ $project->name }}</div>
                        <div class="project-meta-list" style="font-size:11.5px;color:var(--muted);margin-top:3px;display:flex;gap:14px;flex-wrap:wrap;">
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
                            onclick="openReviewModal({{ $project->id }}, {{ \Illuminate\Support\Js::from($project->name) }}, {{ $review->rating ?? 0 }}, {{ \Illuminate\Support\Js::from($review->comment ?? '') }}, {{ $review->is_anonymous ?? false ? 'true' : 'false' }})">
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
        <div class="modal-card rv-card" style="max-width:540px;">
            <div class="modal-header">
                <div class="rv-head">
                    <span class="rv-head-icon"><i data-lucide="star"></i></span>
                    <div>
                        <h2 id="reviewTitle">Leave a Review</h2>
                        <p id="reviewProjectName">Share your experience with this project.</p>
                    </div>
                </div>
                <button class="modal-close" type="button" onclick="closeModal('reviewModal')">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" id="reviewForm" action="" novalidate>
                @csrf
                @if($errors->review->any())
                <div class="review-error" style="margin:0 20px 12px;padding:10px 14px;background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;">
                    @foreach($errors->review->all() as $message)
                    <div>{{ $message }}</div>
                    @endforeach
                </div>
                @endif
                <div class="rv-body">
                    {{-- 1 · Rating: big stars in a box, with a word for the score (hover previews it) --}}
                    <div class="rv-rating-box" id="reviewRatingBox">
                        <div class="rv-label">How would you rate this project? <span class="rv-req">*</span></div>
                        <div class="star-rating rv-stars" id="reviewStarRating">
                            @for($i=1;$i<=5;$i++)
                            <button type="button" data-value="{{ $i }}" onclick="setReviewRating({{ $i }})" aria-label="{{ $i }} star{{ $i > 1 ? 's' : '' }}">
                                <i data-lucide="star"></i>
                            </button>
                            @endfor
                        </div>
                        <div class="rv-rating-word" id="reviewRatingWord">Tap a star to rate</div>
                        <input type="hidden" name="rating" id="reviewRatingInput">
                        <div class="review-error" id="reviewRatingError" hidden>Please choose a star rating.</div>
                    </div>

                    {{-- 2 · The review, with a few one-tap phrases to start from --}}
                    <div>
                        <div class="rv-label">Your Review <span class="rv-req">*</span></div>
                        <div class="rv-chips" aria-label="Quick phrases">
                            @foreach(['Quality workmanship', 'Delivered on time', 'Great communication', 'Professional team', 'Would recommend'] as $phrase)
                            <button type="button" class="rv-chip" data-phrase="{{ $phrase }}"><i data-lucide="plus"></i> {{ $phrase }}</button>
                            @endforeach
                        </div>
                        <textarea name="comment" id="reviewCommentInput" rows="4" maxlength="1000" class="rv-textarea" placeholder="What went well? How was the quality, the timeline and working with the GMD team?"></textarea>
                        <div class="rv-under">
                            <div class="review-error" id="reviewCommentError" hidden>Please write a short review.</div>
                            <span id="reviewCommentCount" class="rv-count">0 / 1000</span>
                        </div>
                    </div>

                    {{-- 3 · Show or hide the name, with a live preview of how it will appear --}}
                    @php
                        $clientDisplayName = session('full_name', 'Client');
                        $maskedPreview = (new \App\Models\Review(['client_name' => $clientDisplayName]))->masked_client_name;
                    @endphp
                    <label class="rv-privacy" for="reviewHideNameInput">
                        <span class="rv-privacy-text">
                            <strong><i data-lucide="eye-off"></i> Hide my name on the public review</strong>
                            <small>Shown as: <span id="reviewNamePreview" data-full="{{ $clientDisplayName }}" data-masked="{{ $maskedPreview }}">{{ $clientDisplayName }}</span></small>
                        </span>
                        <span class="rv-switch">
                            <input type="checkbox" name="hide_name" id="reviewHideNameInput" value="1">
                            <span class="rv-switch-track"></span>
                        </span>
                    </label>
                </div>
                <div class="rv-foot">
                    <button type="button" class="cancel-btn" onclick="closeModal('reviewModal')">Cancel</button>
                    <button type="submit" class="save-btn" id="reviewSubmitBtn">
                        <i data-lucide="send" style="width:15px;height:15px;"></i>
                        <span id="reviewSubmitLabel">Submit Review</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .review-error { font-size: 12.5px; font-weight: 700; color: #b91c1c; }
        .review-error[hidden] { display: none; }

        /* Leave a Review */
        .rv-head { display: flex; align-items: flex-start; gap: 12px; }
        .rv-head-icon { width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #FFF4D6; color: #F59E0B; }
        .rv-head-icon svg { width: 20px; height: 20px; fill: #F59E0B; }
        .rv-body { display: flex; flex-direction: column; gap: 18px; padding: 0 0 4px; }
        .rv-label { margin-bottom: 8px; font-size: 13px; font-weight: 800; color: var(--dark); }
        .rv-req { color: #dc2626; }
        .rv-rating-box { padding: 16px; border: 1px solid var(--border); border-radius: 16px; background: var(--cream-soft); text-align: center; }
        .rv-rating-box .rv-label { margin-bottom: 6px; }
        .rv-stars { justify-content: center; gap: 8px; }
        .rv-stars button { padding: 2px; transition: transform .12s ease; }
        .rv-stars button:hover { transform: scale(1.12); }
        .rv-stars button svg { width: 36px; height: 36px; }
        .rv-stars button.hover svg { color: #FCD34D; fill: #FCD34D; }   /* hover preview */
        .rv-rating-word { margin-top: 6px; min-height: 18px; font-size: 13px; font-weight: 800; color: var(--muted); }
        .rv-rating-word.is-set { color: #b45309; }
        .rv-rating-box.has-error { border-color: #fca5a5; background: #fff5f5; }
        .rv-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }
        .rv-chip { display: inline-flex; align-items: center; gap: 4px; padding: 5px 10px; border: 1px solid var(--border); border-radius: 999px; background: var(--white); font: inherit; font-size: 11.5px; font-weight: 700; color: var(--dark); cursor: pointer; transition: border-color .15s ease, background .15s ease; }
        .rv-chip svg { width: 12px; height: 12px; color: var(--muted); }
        .rv-chip:hover { border-color: var(--dark); }
        .rv-chip.is-used { background: var(--dark); border-color: var(--dark); color: #fff; }
        .rv-chip.is-used svg { color: #fff; }
        /* fixed height, not resizable — longer reviews scroll inside the box */
        .rv-textarea { width: 100%; height: 110px; min-height: 110px; max-height: 110px; padding: 12px 14px; border: 1px solid var(--border); border-radius: 14px; background: var(--white); font: inherit; font-size: 13.5px; line-height: 1.55; resize: none !important; overflow-y: auto; }
        .rv-textarea:focus { outline: none; border-color: var(--dark); }
        .rv-under { display: flex; justify-content: space-between; gap: 10px; margin-top: 6px; }
        .rv-count { margin-left: auto; font-size: 11.5px; font-weight: 700; color: var(--muted); }
        .rv-privacy { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 12px 14px; border: 1px solid var(--border); border-radius: 14px; background: var(--white); cursor: pointer; margin: 0; }
        .rv-privacy-text { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
        .rv-privacy-text strong { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: var(--dark); }
        .rv-privacy-text strong svg { width: 14px; height: 14px; color: var(--muted); }
        .rv-privacy-text small { font-size: 11.5px; color: var(--muted); overflow-wrap: anywhere; }
        .rv-privacy-text small span { font-weight: 800; color: var(--dark); }
        .rv-switch { position: relative; flex-shrink: 0; width: 42px; height: 24px; }
        .rv-switch input { position: absolute; inset: 0; opacity: 0; margin: 0; cursor: pointer; }
        .rv-switch-track { position: absolute; inset: 0; border-radius: 999px; background: #d4d4d4; transition: background .15s ease; pointer-events: none; }
        .rv-switch-track::after { content: ''; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.2); transition: transform .15s ease; }
        .rv-switch input:checked + .rv-switch-track { background: var(--dark); }
        .rv-switch input:checked + .rv-switch-track::after { transform: translateX(18px); }
        .rv-foot { display: flex; justify-content: flex-end; gap: 10px; padding-top: 16px; margin-top: 16px; border-top: 1px solid var(--border); }
        .rv-foot .cancel-btn, .rv-foot .save-btn { display: inline-flex; align-items: center; gap: 7px; }
        @media (max-width: 560px) {
            .rv-stars button svg { width: 32px; height: 32px; }
            .rv-foot > * { flex: 1; justify-content: center; }
        }
    </style>
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

        var REVIEW_WORDS = ['Tap a star to rate', 'Poor', 'Fair', 'Good', 'Very good', 'Excellent'];

        function setReviewRating(value) {
            document.getElementById('reviewRatingInput').value = value > 0 ? value : '';   // empty means "not chosen yet"
            document.getElementById('reviewRatingError').hidden = true;
            document.getElementById('reviewRatingBox').classList.remove('has-error');
            document.querySelectorAll('#reviewStarRating button').forEach(function (btn) {
                btn.classList.toggle('filled', parseInt(btn.dataset.value, 10) <= value);
            });
            var word = document.getElementById('reviewRatingWord');
            word.textContent = REVIEW_WORDS[value] || REVIEW_WORDS[0];
            word.classList.toggle('is-set', value > 0);
        }

        // hovering a star previews that score (and its word); leaving goes back to the chosen one
        (function () {
            var stars = document.querySelectorAll('#reviewStarRating button');
            var word  = document.getElementById('reviewRatingWord');
            stars.forEach(function (star) {
                star.addEventListener('mouseenter', function () {
                    var v = parseInt(star.dataset.value, 10);
                    stars.forEach(function (s) { s.classList.toggle('hover', parseInt(s.dataset.value, 10) <= v); });
                    word.textContent = REVIEW_WORDS[v];
                });
            });
            document.getElementById('reviewStarRating').addEventListener('mouseleave', function () {
                stars.forEach(function (s) { s.classList.remove('hover'); });
                var v = parseInt(document.getElementById('reviewRatingInput').value, 10) || 0;
                word.textContent = REVIEW_WORDS[v];
            });
        })();

        // the name preview follows the switch: full name, or the masked one
        function updateNamePreview() {
            var el = document.getElementById('reviewNamePreview');
            el.textContent = document.getElementById('reviewHideNameInput').checked ? el.dataset.masked : el.dataset.full;
        }
        document.getElementById('reviewHideNameInput').addEventListener('change', updateNamePreview);

        // quick phrases: tap to add one to the review (tap again to take it out)
        function syncReviewChips() {
            var text = document.getElementById('reviewCommentInput').value;
            document.querySelectorAll('.rv-chip').forEach(function (chip) {
                chip.classList.toggle('is-used', text.indexOf(chip.dataset.phrase) !== -1);
            });
        }
        document.querySelectorAll('.rv-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                var box = document.getElementById('reviewCommentInput'), phrase = chip.dataset.phrase;
                if (box.value.indexOf(phrase) !== -1) {
                    box.value = box.value.replace(phrase + '. ', '').replace(phrase + '.', '').replace(phrase, '').trim();
                } else {
                    box.value = (box.value.trim() ? box.value.trim().replace(/[.!]?$/, '.') + ' ' : '') + phrase + '.';
                }
                box.value = box.value.slice(0, 1000);
                box.dispatchEvent(new Event('input'));
                box.focus();
            });
        });

        function openReviewModal(projectId, projectName, rating, comment, isAnonymous) {
            document.getElementById('reviewForm').action = '/client/projects/' + projectId + '/review';
            document.getElementById('reviewProjectName').textContent = 'Share your experience with "' + projectName + '".';
            // editing an existing review (it already has a rating) vs writing a new one
            var editing = (rating || 0) > 0;
            document.getElementById('reviewTitle').textContent = editing ? 'Edit Your Review' : 'Leave a Review';
            document.getElementById('reviewSubmitLabel').textContent = editing ? 'Save Review' : 'Submit Review';
            document.getElementById('reviewCommentInput').value = comment || '';
            document.getElementById('reviewHideNameInput').checked = !!isAnonymous;
            updateNamePreview();
            setReviewRating(rating || 0);
            document.getElementById('reviewCommentError').hidden = true;
            updateReviewCount();
            syncReviewChips();
            openModal('reviewModal');
        }

        function updateReviewCount() {
            var box   = document.getElementById('reviewCommentInput');
            var count = document.getElementById('reviewCommentCount');
            count.textContent = box.value.length + ' / 1000';
            count.style.color = box.value.length >= 1000 ? '#dc2626' : 'var(--muted)';
        }
        document.getElementById('reviewCommentInput').addEventListener('input', function () {
            updateReviewCount();
            syncReviewChips();
            if (this.value.trim()) document.getElementById('reviewCommentError').hidden = true;
        });

        // The rating is a hidden field and can't show the browser's own "required" message, so both fields
        // are checked here and explained under the field itself.
        document.getElementById('reviewForm').addEventListener('submit', function (e) {
            var noRating  = !document.getElementById('reviewRatingInput').value;
            var noComment = !document.getElementById('reviewCommentInput').value.trim();
            document.getElementById('reviewRatingError').hidden  = !noRating;
            document.getElementById('reviewRatingBox').classList.toggle('has-error', noRating);
            document.getElementById('reviewCommentError').hidden = !noComment;
            if (noRating || noComment) {
                e.preventDefault();
                (noRating ? document.getElementById('reviewStarRating') : document.getElementById('reviewCommentInput')).scrollIntoView({ block: 'center' });
            }
        });

        @if($errors->review->any() && session('review_project_id'))
        // the server sent the review back with a problem: reopen the pop-up with what was typed and the message
        (function () {
            var project = {{ \Illuminate\Support\Js::from(optional($projects->firstWhere('id', session('review_project_id')))->name ?? 'this project') }};
            openReviewModal({{ (int) session('review_project_id') }}, project, {{ (int) old('rating', 0) }}, {{ \Illuminate\Support\Js::from(old('comment', '')) }}, {{ old('hide_name') ? 'true' : 'false' }});
        })();
        @endif

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
