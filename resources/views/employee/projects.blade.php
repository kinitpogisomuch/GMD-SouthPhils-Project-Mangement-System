<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
</head>
<body>

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

            <div class="project-list">

                @forelse($projects as $project)
                <div class="card project-card">
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

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        function toggleSidebar() {
            document.querySelector('.employee-sidebar').classList.toggle('open');
        }

        function initializeEmployeeDropdown() {
            const dropdown = document.querySelector(".employee-dropdown");
            const button = document.getElementById("employeeDropdownBtn");
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
            const button = document.getElementById("notificationDropdownBtn");
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
                const employeeDropdown = document.querySelector(".employee-dropdown");
                const notificationDropdown = document.querySelector(".notification-dropdown");
                if (employeeDropdown) employeeDropdown.classList.remove("open");
                if (notificationDropdown) notificationDropdown.classList.remove("open");
            });
        }

        initializeEmployeeDropdown();
        initializeNotificationDropdown();
        closeDropdownsOnOutsideClick();
    </script>
</body>
</html>