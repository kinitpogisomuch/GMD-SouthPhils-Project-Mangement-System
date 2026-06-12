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
                <div class="stat-card blue">
                    <div class="stat-icon blue"><i data-lucide="folder-open"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">3</div>
                        <div class="stat-label">Active Projects</div>
                        <div class="stat-change up"><i data-lucide="trending-up"></i> Assigned projects</div>
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
                                <th>Progress</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Storage Tank Fabrication – Unit A</strong></td>
                                <td>Lead Welder</td>
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

        function toggleSidebar() {
            document.querySelector('.employee-sidebar').classList.toggle('open');
        }
    </script>
</body>
</html>