<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
</head>
<body>

    @include('partials.employee.header')

    <div class="admin-layout">
        @include('partials.employee.sidebar')

        <main class="admin-content">

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i data-lucide="check-square"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">8</div>
                        <div class="stat-label">Tasks Assigned</div>
                        <div class="stat-change up"><i data-lucide="trending-up"></i> 2 due today</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i data-lucide="check-circle"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">5</div>
                        <div class="stat-label">Tasks Completed</div>
                        <div class="stat-change up"><i data-lucide="trending-up"></i> This week</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i data-lucide="clock"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">38.5h</div>
                        <div class="stat-label">Hours This Week</div>
                        <div class="stat-change up"><i data-lucide="trending-up"></i> +2.5h from last week</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i data-lucide="folder-open"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">3</div>
                        <div class="stat-label">Active Projects</div>
                        <div class="stat-change up"><i data-lucide="trending-up"></i> Assigned projects</div>
                    </div>
                </div>
            </div>

            <div class="grid-2">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Today's Tasks</span>
                        <a href="{{ route('employee.tasks') }}" class="view-all-link">All Tasks <i data-lucide="arrow-right"></i></a>
                    </div>
                    <div class="card-body">
                        <div class="task-card">
                            <div class="task-check"></div>
                            <div class="task-info">
                                <div class="task-title">Weld inspection – Tank Section 3B</div>
                                <div class="task-meta">
                                    <span><i data-lucide="folder"></i> Storage Tank Unit A</span>
                                    <span><i data-lucide="clock"></i> Due: 5:00 PM</span>
                                    <span class="task-priority priority-high">High</span>
                                </div>
                            </div>
                        </div>
                        <div class="task-card">
                            <div class="task-check"></div>
                            <div class="task-info">
                                <div class="task-title">Update progress photos – Zone B pipeline</div>
                                <div class="task-meta">
                                    <span><i data-lucide="folder"></i> Pipeline Installation</span>
                                    <span><i data-lucide="clock"></i> Due: 3:00 PM</span>
                                    <span class="task-priority priority-medium">Medium</span>
                                </div>
                            </div>
                        </div>
                        <div class="task-card">
                            <div class="task-check done"></div>
                            <div class="task-info">
                                <div class="task-title completed">Submit daily timesheet</div>
                                <div class="task-meta">
                                    <span><i data-lucide="clock"></i> Completed 8:30 AM</span>
                                    <span class="task-priority priority-low">Low</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">This Week's Hours</span>
                        <a href="{{ route('employee.timesheets') }}" class="view-all-link">Timesheets <i data-lucide="arrow-right"></i></a>
                    </div>
                    <div class="card-body">
                        @php
                            $days  = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
                            $hours = [8.0, 7.5, 8.0, 8.0, 7.0];
                        @endphp
                        @foreach($days as $i => $day)
                        @php $pct = ($hours[$i] / 9) * 100; @endphp
                        <div class="hours-bar-row">
                            <div class="hours-bar-label-row">
                                <span class="hours-bar-label">{{ $day }}</span>
                                <span class="hours-bar-value">{{ $hours[$i] }}h</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: {{ $pct }}%; background: {{ $hours[$i] >= 8 ? 'var(--success)' : 'var(--accent)' }};"></div>
                            </div>
                        </div>
                        @endforeach
                        <div class="hours-total-row">
                            <span class="hours-total-label">Total this week</span>
                            <strong>38.5 / 45 hrs</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">My Assigned Projects</span>
                    <a href="{{ route('employee.projects') }}" class="view-all-link">View Projects <i data-lucide="arrow-right"></i></a>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>My Role</th>
                                <th>Open Tasks</th>
                                <th>Progress</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Storage Tank Fabrication – Unit A</strong></td>
                                <td>Lead Welder</td>
                                <td>3 tasks</td>
                                <td>
                                    <div class="flex-center gap-8">
                                        <div class="progress-bar width-100px"><div class="progress-fill" style="width: 72%;"></div></div>
                                        <span class="font-12 color-muted font-w700">72%</span>
                                    </div>
                                </td>
                                <td><span class="status-badge ongoing">In Progress</span></td>
                            </tr>
                            <tr>
                                <td><strong>Pipeline Installation – Zone B</strong></td>
                                <td>Pipefitter</td>
                                <td>2 tasks</td>
                                <td>
                                    <div class="flex-center gap-8">
                                        <div class="progress-bar width-100px"><div class="progress-fill" style="width: 45%; background: var(--warning);"></div></div>
                                        <span class="font-12 color-muted font-w700">45%</span>
                                    </div>
                                </td>
                                <td><span class="status-badge pending">On Hold</span></td>
                            </tr>
                            <tr>
                                <td><strong>Structural Steel Works – Block C</strong></td>
                                <td>Steel Fabricator</td>
                                <td>1 task</td>
                                <td>
                                    <div class="flex-center gap-8">
                                        <div class="progress-bar width-100px"><div class="progress-fill" style="width: 88%; background: var(--success);"></div></div>
                                        <span class="font-12 color-muted font-w700">88%</span>
                                    </div>
                                </td>
                                <td><span class="status-badge completed">On Track</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        function updateDateTime() {
            var dateEl = document.getElementById("headerDate");
            var timeEl = document.getElementById("headerTime");
            if (!dateEl || !timeEl) return;
            var now = new Date();
            dateEl.textContent = now.toLocaleDateString("en-US", { weekday: "long", year: "numeric", month: "long", day: "numeric" });
            timeEl.textContent = now.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit", second: "2-digit", hour12: true });
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);

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

        function toggleSidebar() {
            document.querySelector('.employee-sidebar').classList.toggle('open');
        }
    </script>
</body>
</html>