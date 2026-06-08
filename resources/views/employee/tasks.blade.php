<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
</head>
<body>

    @include('partials.employee.header')

    <div class="admin-layout">
        @include('partials.employee.sidebar')

        <main class="admin-content">

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <div style="display:flex; gap:8px;">
                    <button class="btn btn-primary btn-sm" onclick="filterTasks('all')" id="filter-all">All</button>
                    <button class="btn btn-outline btn-sm" onclick="filterTasks('pending')" id="filter-pending">Pending</button>
                    <button class="btn btn-outline btn-sm" onclick="filterTasks('done')" id="filter-done">Completed</button>
                </div>
                <select class="filter-select" onchange="filterByProject(this.value)">
                    <option value="all">All Projects</option>
                    <option value="Storage Tank Fabrication">Storage Tank Fabrication</option>
                    <option value="Pipeline Installation">Pipeline Installation</option>
                    <option value="Structural Steel Works">Structural Steel Works</option>
                </select>
            </div>

            <div id="task-list">

                <div class="task-item" data-status="pending" data-project="Storage Tank Fabrication">
                    <div class="task-card">
                        <div class="task-check" onclick="toggleTask(this)"></div>
                        <div class="task-info">
                            <div class="task-info-flex">
                                <div>
                                    <div class="task-title">Weld inspection – Tank Section 3B</div>
                                    <div class="task-meta mt-5">
                                        <span><i data-lucide="folder"></i> Storage Tank Fabrication</span>
                                        <span><i data-lucide="user"></i> Assigned by: Engr. Santos</span>
                                        <span><i data-lucide="calendar"></i> Due: Apr 10, 2025 – 5:00 PM</span>
                                    </div>
                                    <div class="mt-8 font-12-5 color-muted">
                                        Perform visual and dimensional inspection on section 3B welds. Document findings with photos and submit report.
                                    </div>
                                </div>
                                <span class="task-priority priority-high flex-shrink-0">High</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="task-item" data-status="pending" data-project="Pipeline Installation">
                    <div class="task-card">
                        <div class="task-check" onclick="toggleTask(this)"></div>
                        <div class="task-info">
                            <div class="task-info-flex">
                                <div>
                                    <div class="task-title">Update progress photos – Zone B pipeline</div>
                                    <div class="task-meta mt-5">
                                        <span><i data-lucide="folder"></i> Pipeline Installation</span>
                                        <span><i data-lucide="calendar"></i> Due: Apr 10, 2025 – 3:00 PM</span>
                                    </div>
                                    <div class="mt-8 font-12-5 color-muted">
                                        Take and upload progress photos of Zone B pipeline work for client report submission.
                                    </div>
                                </div>
                                <span class="task-priority priority-medium flex-shrink-0">Medium</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="task-item" data-status="pending" data-project="Structural Steel Works">
                    <div class="task-card">
                        <div class="task-check" onclick="toggleTask(this)"></div>
                        <div class="task-info">
                            <div class="task-info-flex">
                                <div>
                                    <div class="task-title">Pre-assembly check – Block C steel beams</div>
                                    <div class="task-meta mt-5">
                                        <span><i data-lucide="folder"></i> Structural Steel Works</span>
                                        <span><i data-lucide="calendar"></i> Due: Apr 12, 2025</span>
                                    </div>
                                </div>
                                <span class="task-priority priority-low flex-shrink-0">Low</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="task-item" data-status="done" data-project="Storage Tank Fabrication">
                    <div class="task-card opacity-70">
                        <div class="task-check done" onclick="toggleTask(this)"></div>
                        <div class="task-info">
                            <div class="task-info-flex">
                                <div>
                                    <div class="task-title completed">Submit daily timesheet – Apr 9</div>
                                    <div class="task-meta mt-5">
                                        <span><i data-lucide="check"></i> Completed: Apr 9, 8:30 AM</span>
                                    </div>
                                </div>
                                <span class="task-priority priority-low flex-shrink-0">Low</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="task-item" data-status="done" data-project="Storage Tank Fabrication">
                    <div class="task-card opacity-70">
                        <div class="task-check done" onclick="toggleTask(this)"></div>
                        <div class="task-info">
                            <div class="task-info-flex">
                                <div>
                                    <div class="task-title completed">Welding of Tank Section 2A</div>
                                    <div class="task-meta mt-5">
                                        <span><i data-lucide="folder"></i> Storage Tank Fabrication</span>
                                        <span><i data-lucide="check"></i> Completed: Apr 8, 4:45 PM</span>
                                    </div>
                                </div>
                                <span class="task-priority priority-high flex-shrink-0">High</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        function toggleTask(element) {
            element.classList.toggle('done');
            const taskCard = element.closest('.task-card');
            const taskTitle = taskCard.querySelector('.task-title');
            if (element.classList.contains('done')) {
                taskTitle.style.textDecoration = 'line-through';
                taskTitle.style.color = 'var(--muted)';
                taskCard.style.opacity = '0.7';
            } else {
                taskTitle.style.textDecoration = 'none';
                taskTitle.style.color = '';
                taskCard.style.opacity = '1';
            }
        }

        function filterTasks(status) {
            document.querySelectorAll('[id^="filter-"]').forEach(btn => {
                btn.className = 'btn btn-outline btn-sm';
            });
            document.getElementById('filter-' + status).className = 'btn btn-primary btn-sm';
            document.querySelectorAll('.task-item').forEach(item => {
                item.style.display = status === 'all' ? '' : item.dataset.status === status ? '' : 'none';
            });
        }

        function filterByProject(project) {
            document.querySelectorAll('.task-item').forEach(item => {
                item.style.display = project === 'all' ? '' : item.dataset.project === project ? '' : 'none';
            });
        }

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