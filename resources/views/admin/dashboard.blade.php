<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body>

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">
            <h1>Dashboard</h1>
            <p>Welcome to the GMD South Phils Project Management System.</p>

            <!-- Summary Cards -->
            <div class="page-grid" style="margin-top: 28px;">
                <div class="info-card">
                    <div class="info-card-icon"><i data-lucide="folder-kanban"></i></div>
                    <h3>Total Projects</h3>
                    <div class="value">3</div>
                    <div class="info-card-sub">1 Ongoing &nbsp;·&nbsp; 1 Completed &nbsp;·&nbsp; 1 Pending</div>
                </div>

                <div class="info-card">
                    <div class="info-card-icon"><i data-lucide="users"></i></div>
                    <h3>Employees</h3>
                    <div class="value">8</div>
                    <div class="info-card-sub">Active workforce</div>
                </div>

                <div class="info-card">
                    <div class="info-card-icon"><i data-lucide="clipboard-list"></i></div>
                    <h3>Material Requests</h3>
                    <div class="value">4</div>
                    <div class="info-card-sub">2 Pending &nbsp;·&nbsp; 2 Fulfilled</div>
                </div>

                <div class="info-card">
                    <div class="info-card-icon"><i data-lucide="credit-card"></i></div>
                    <h3>Payments</h3>
                    <div class="value">3</div>
                    <div class="info-card-sub">1 Partial &nbsp;·&nbsp; 1 Paid &nbsp;·&nbsp; 1 Pending</div>
                </div>

                <div class="info-card">
                    <div class="info-card-icon"><i data-lucide="message-square"></i></div>
                    <h3>Messages</h3>
                    <div class="value">5</div>
                    <div class="info-card-sub">Unread messages</div>
                </div>

                <div class="info-card">
                    <div class="info-card-icon"><i data-lucide="alert-triangle"></i></div>
                    <h3>Material Shortages</h3>
                    <div class="value">2</div>
                    <div class="info-card-sub">Needs immediate action</div>
                </div>
            </div>

            <!-- Active Projects -->
            <div class="section-header" style="margin-top: 36px;">
                <h2>Active Projects</h2>
                <a href="{{ route('admin.projects') }}" class="view-all-link">View all <i data-lucide="arrow-right"></i></a>
            </div>

            <div class="table-card" style="margin-top: 14px;">
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Client</th>
                                <th>Tank Type</th>
                                <th>Status</th>
                                <th>Start Date</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Fuel Storage Tank Fabrication</td>
                                <td>ABC Corporation</td>
                                <td>Fuel Tank</td>
                                <td><span class="status-badge ongoing">Ongoing</span></td>
                                <td>May 10, 2026</td>
                                <td>
                                    <div class="progress-bar-wrap">
                                        <div class="progress-bar" style="width: 60%"></div>
                                    </div>
                                    <span class="progress-label">60%</span>
                                </td>
                            </tr>
                            <tr>
                                <td>Water Tank Installation</td>
                                <td>South Plant Inc.</td>
                                <td>Water Tank</td>
                                <td><span class="status-badge completed">Completed</span></td>
                                <td>April 28, 2026</td>
                                <td>
                                    <div class="progress-bar-wrap">
                                        <div class="progress-bar" style="width: 100%"></div>
                                    </div>
                                    <span class="progress-label">100%</span>
                                </td>
                            </tr>
                            <tr>
                                <td>Chemical Tank Repair</td>
                                <td>Prime Chemicals</td>
                                <td>Chemical Tank</td>
                                <td><span class="status-badge pending">Pending</span></td>
                                <td>May 20, 2026</td>
                                <td>
                                    <div class="progress-bar-wrap">
                                        <div class="progress-bar" style="width: 0%"></div>
                                    </div>
                                    <span class="progress-label">0%</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
</body>
</html>