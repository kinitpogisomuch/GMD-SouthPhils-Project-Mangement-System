<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.client.header')

    <div class="admin-layout">
        @include('partials.client.sidebar')

        <main class="admin-content">

            <div class="stats-grid">
                <div class="stat-card teal">
                    <div class="stat-icon teal"><i data-lucide="folder-open"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $projects->count() }}</div>
                        <div class="stat-label">Active Projects</div>
                        <div class="stat-change up"><i data-lucide="trending-up"></i> Your projects</div>
                    </div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-icon blue"><i data-lucide="check-circle"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $projects->avg('progress') ? round($projects->avg('progress')) : 0 }}%</div>
                        <div class="stat-label">Overall Progress</div>
                        <div class="stat-change up"><i data-lucide="trending-up"></i> Average completion</div>
                    </div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-icon orange"><i data-lucide="credit-card"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱{{ number_format($payments->sum('contract_amount')) }}</div>
                        <div class="stat-label">Total Contract Value</div>
                        <div class="stat-change up"><i data-lucide="trending-up"></i> All projects</div>
                    </div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon green"><i data-lucide="file-text"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $payments->count() }}</div>
                        <div class="stat-label">Payment Records</div>
                        <div class="stat-change up"><i data-lucide="trending-up"></i> Total invoices</div>
                    </div>
                </div>
            </div>

            <div class="grid-2">
                <!-- Active Projects -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Active Projects</span>
                        <a href="{{ url('/client/projects') }}" class="btn btn-outline btn-sm">
                            <i data-lucide="arrow-right"></i> View All
                        </a>
                    </div>
                    <div class="card-body">
                        @forelse($projects as $project)
                        <div style="margin-bottom:22px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                <div>
                                    <div style="font-weight:600; font-size:14px;">{{ $project->name }}</div>
                                    <div style="font-size:12px; color:var(--text-secondary);">
                                        Started: {{ $project->start_date->format('M d, Y') }}
                                    </div>
                                </div>
                                @if($project->status === 'ongoing')
                                    <span class="badge badge-info">In Progress</span>
                                @elseif($project->status === 'completed')
                                    <span class="badge badge-success">Completed</span>
                                @else
                                    <span class="badge badge-warning">Pending</span>
                                @endif
                            </div>
                            <div class="progress-wrap">
                                <div class="progress-label">
                                    <span>Overall completion</span>
                                    <span>{{ $project->progress }}%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width:{{ $project->progress }}%"></div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <p style="color:var(--text-secondary);font-size:13.5px;">No projects found.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Payments -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Recent Payments</span>
                        <a href="{{ route('client.payments') }}" class="btn btn-outline btn-sm">
                            <i data-lucide="arrow-right"></i> View All
                        </a>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                <tr>
                                    <td><strong>{{ $payment->client }}</strong></td>
                                    <td><strong>₱{{ number_format($payment->contract_amount) }}</strong></td>
                                    <td>
                                        @if($payment->status === 'Paid')
                                            <span class="badge badge-success">Paid</span>
                                        @elseif($payment->status === 'Partial')
                                            <span class="badge badge-info">Partial</span>
                                        @else
                                            <span class="badge badge-warning">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" style="text-align:center;color:var(--text-secondary);">No payments found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Documents -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Recent Documents</span>
                    <a href="{{ route('client.documents') }}" class="btn btn-outline btn-sm">
                        <i data-lucide="arrow-right"></i> View All
                    </a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Project</th>
                                <th>Type</th>
                                <th>Uploaded</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <i data-lucide="file-text" style="width:16px;height:16px;color:var(--accent)"></i>
                                        Contract Agreement – Unit A
                                    </div>
                                </td>
                                <td>Storage Tank Fabrication</td>
                                <td><span class="badge badge-teal">Contract</span></td>
                                <td>Jan 15, 2025</td>
                                <td><a href="#" class="btn btn-outline btn-sm"><i data-lucide="download"></i> Download</a></td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <i data-lucide="file-text" style="width:16px;height:16px;color:var(--accent)"></i>
                                        Technical Drawings Rev.2
                                    </div>
                                </td>
                                <td>Pipeline Installation</td>
                                <td><span class="badge badge-info">Drawing</span></td>
                                <td>Feb 20, 2025</td>
                                <td><a href="#" class="btn btn-outline btn-sm"><i data-lucide="download"></i> Download</a></td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <i data-lucide="file-text" style="width:16px;height:16px;color:var(--accent)"></i>
                                        Inspection Report – Q1
                                    </div>
                                </td>
                                <td>Structural Steel Works</td>
                                <td><span class="badge badge-gray">Report</span></td>
                                <td>Mar 31, 2025</td>
                                <td><a href="#" class="btn btn-outline btn-sm"><i data-lucide="download"></i> Download</a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/client.js') }}"></script>
</body>
</html>