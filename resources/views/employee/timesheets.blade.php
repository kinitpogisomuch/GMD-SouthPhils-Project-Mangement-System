<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timesheets | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
</head>
<body>

    @include('partials.employee.header')

    <div class="admin-layout">
        @include('partials.employee.sidebar')

        <main class="admin-content">

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <div class="flex-center gap-12">
                    <button class="btn btn-outline btn-sm" onclick="changeWeek(-1)"><i data-lucide="chevron-left"></i></button>
                    <span style="font-weight:800; font-size:15px;" id="weekRange">Week of April 7 – 13, 2025</span>
                    <button class="btn btn-outline btn-sm" onclick="changeWeek(1)"><i data-lucide="chevron-right"></i></button>
                </div>
                <button class="btn btn-primary btn-sm" onclick="openLogModal()">
                    <i data-lucide="plus"></i> Log Time
                </button>
            </div>

            <div class="stats-grid" style="grid-template-columns:repeat(4, 1fr); margin-bottom:24px;">
                <div class="stat-card">
                    <div class="stat-icon"><i data-lucide="clock"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">38.5h</div>
                        <div class="stat-label">Total This Week</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i data-lucide="check-circle"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">5</div>
                        <div class="stat-label">Days Logged</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i data-lucide="calendar"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">45h</div>
                        <div class="stat-label">Weekly Target</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i data-lucide="trending-up"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">85%</div>
                        <div class="stat-label">Completion Rate</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Weekly Log</span>
                    <span class="status-badge pending">Draft</span>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Project</th>
                                <th>Task / Activity</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Hours</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $logs = [
                                ['Apr 7 (Mon)', 'Storage Tank Fabrication', 'Welding – Section 2A', '7:00 AM', '3:30 PM', '8.0h', 'approved'],
                                ['Apr 8 (Tue)', 'Storage Tank Fabrication', 'Welding – Section 2B', '7:00 AM', '2:30 PM', '7.5h', 'approved'],
                                ['Apr 9 (Wed)', 'Structural Steel Works',   'Steel beam pre-assembly', '7:00 AM', '3:30 PM', '8.0h', 'pending'],
                                ['Apr 10 (Thu)', 'Pipeline Installation',   'Progress documentation', '7:00 AM', '3:30 PM', '8.0h', 'pending'],
                                ['Apr 11 (Fri)', 'Storage Tank Fabrication', 'Weld inspection – 3B', '7:00 AM', '2:00 PM', '7.0h', 'draft'],
                            ];
                            @endphp

                            @foreach($logs as $log)
                            @php
                                $statusClass = $log[6] === 'approved' ? 'completed' : ($log[6] === 'pending' ? 'pending' : 'draft');
                                $statusText  = ucfirst($log[6]);
                            @endphp
                            <tr>
                                <td><strong>{{ $log[0] }}</strong></td>
                                <td>{{ $log[1] }}</td>
                                <td>{{ $log[2] }}</td>
                                <td>{{ $log[3] }}</td>
                                <td>{{ $log[4] }}</td>
                                <td><strong>{{ $log[5] }}</strong></td>
                                <td><span class="status-badge {{ $statusClass }}">{{ $statusText }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:16px 22px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:12px;">
                    <button class="btn btn-outline btn-sm"><i data-lucide="save"></i> Save Draft</button>
                    <button class="btn btn-primary btn-sm"><i data-lucide="send"></i> Submit for Approval</button>
                </div>
            </div>

            <!-- Log Time Modal -->
            <div id="logModal" class="modal-overlay">
                <div class="modal-card">
                    <div class="modal-header">
                        <h3>Log Time Entry</h3>
                        <button class="modal-close" onclick="closeLogModal()"><i data-lucide="x"></i></button>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" id="logDate" class="log-input">
                    </div>
                    <div class="form-group">
                        <label>Project</label>
                        <select id="logProject" class="filter-select">
                            <option>Storage Tank Fabrication – Unit A</option>
                            <option>Pipeline Installation – Zone B</option>
                            <option>Structural Steel Works – Block C</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Task / Activity</label>
                        <input type="text" id="logTask" placeholder="e.g. Welding – Section 3B" class="log-input">
                    </div>
                    <div class="grid-2" style="gap:16px;">
                        <div class="form-group">
                            <label>Time In</label>
                            <input type="time" id="logTimeIn" class="log-input">
                        </div>
                        <div class="form-group">
                            <label>Time Out</label>
                            <input type="time" id="logTimeOut" class="log-input">
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button class="btn btn-outline" onclick="closeLogModal()">Cancel</button>
                        <button class="btn btn-primary" onclick="saveLogEntry()"><i data-lucide="save"></i> Save Entry</button>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        function openLogModal() {
            document.getElementById('logModal').classList.add('show');
        }

        function closeLogModal() {
            document.getElementById('logModal').classList.remove('show');
        }

        function saveLogEntry() {
            alert('Time entry saved successfully!');
            closeLogModal();
        }

        function changeWeek(direction) {
            console.log('Change week:', direction);
        }

        function toggleSidebar() {
            document.querySelector('.employee-sidebar').classList.toggle('open');
        }

        document.getElementById('logModal').addEventListener('click', function(e) {
            if (e.target === this) closeLogModal();
        });

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