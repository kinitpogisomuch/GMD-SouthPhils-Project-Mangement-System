<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.employee.header')

    <div class="admin-layout">
        @include('partials.employee.sidebar')

        <main class="admin-content">

            <div class="pv-page-header">
                <div>
                    <h1>Projects</h1>
                    <p>Projects you are currently assigned to.</p>
                </div>
            </div>

            <div class="filter-tabs" id="projectFilterTabs" style="margin-bottom:20px;margin-left:auto;width:fit-content;">
                <button type="button" class="filter-tab active" data-filter="active">Active</button>
                <button type="button" class="filter-tab" data-filter="completed">Completed</button>
            </div>

            <div class="project-list" id="projectList">

                @forelse($projects as $project)
                <div class="card project-card" data-status="{{ $project->status }}">
                    <div class="card-header project-card-header">
                        <div class="project-info">
                            <div class="project-title">{{ $project->name }}</div>
                            <div class="project-meta">
                                {{ $project->client }} &nbsp;·&nbsp;
                                Tank Type: <strong class="project-meta-strong">{{ $project->tank_type }}</strong>
                                &nbsp;·&nbsp; Capacity: <strong class="project-meta-strong">{{ $project->capacity }}</strong>
                            </div>
                        </div>
                        <div class="project-actions">
                            @if($project->status === 'ongoing')
                                <span class="status-badge ongoing">In Progress</span>
                            @elseif($project->status === 'completed')
                                <span class="status-badge completed">Completed</span>
                            @else
                                <span class="status-badge pending">Pending</span>
                            @endif
                            <a href="{{ url('/employee/project-view/' . $project->id) }}"
                               class="btn btn-outline btn-sm">
                                <i data-lucide="external-link" style="width:13px;height:13px;"></i> View
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="project-progress-container">
                            <div>
                                <div class="progress-wrap">
                                    <div class="progress-label">
                                        <span>Project Progress</span>
                                        <span style="font-weight:800;color:var(--dark);">{{ $project->progress }}%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill"
                                             style="width:{{ $project->progress }}%;
                                             background: {{ $project->status === 'completed' ? 'var(--success)' : ($project->status === 'pending' ? 'var(--warning)' : '') }}">
                                        </div>
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
                                        {{ ucfirst(str_replace('_', ' ', $project->current_phase)) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div style="text-align:center;padding:40px;color:var(--muted);">
                    No projects assigned yet.
                </div>
                @endforelse

            </div>

            <div id="noFilteredProjects" style="display:none;text-align:center;padding:40px;color:var(--muted);">
                No projects found in this filter.
            </div>

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        function toggleSidebar() {
            document.querySelector('.employee-sidebar').classList.toggle('open');
        }

        (function() {
            var tabs = document.querySelectorAll('#projectFilterTabs .filter-tab');
            var cards = document.querySelectorAll('#projectList .project-card');
            var noResults = document.getElementById('noFilteredProjects');
            if (!tabs.length) return;

            function applyFilter(filter) {
                var visibleCount = 0;
                cards.forEach(function(card) {
                    var status = (card.dataset.status || '').toLowerCase();
                    var visible = (filter === 'completed' && status === 'completed')
                        || (filter === 'active' && status !== 'completed');
                    card.style.display = visible ? '' : 'none';
                    if (visible) visibleCount++;
                });
                if (noResults) noResults.style.display = visibleCount === 0 ? '' : 'none';
            }

            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    tabs.forEach(function(t) { t.classList.remove('active'); });
                    this.classList.add('active');
                    applyFilter(this.dataset.filter);
                });
            });

            applyFilter('active');
        })();

    </script>
</body>
</html>