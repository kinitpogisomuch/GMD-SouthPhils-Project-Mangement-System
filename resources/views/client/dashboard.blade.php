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

    <main class="admin-content">

            <div class="page-header" style="margin-bottom:24px;">
                <div>
                    <h1 class="page-title">Welcome back, {{ session('full_name', 'Client') }}</h1>
                    <p class="page-subtitle">Here's an overview of your projects and payments.</p>
                </div>
            </div>

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
                        @php
                            $fillColor = match($project->status) {
                                'completed' => '#207A3A',
                                'delayed'   => '#B42318',
                                default     => 'var(--dark)',
                            };
                        @endphp
                        <a href="{{ route('client.project_view', $project->id) }}" class="dash-project-row">
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
                                    <div class="progress-fill" style="width:{{ $project->progress }}%;background:{{ $fillColor }};"></div>
                                </div>
                            </div>
                        </a>
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
                                    <th style="text-align:right;">Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                <tr>
                                    <td><strong>{{ $payment->project->name ?? '—' }}</strong></td>
                                    <td style="text-align:right;"><strong>₱{{ number_format($payment->contract_amount) }}</strong></td>
                                    <td>
                                        <span class="status-badge {{ \App\Models\Payment::statusBadgeClass($payment->status) }}">
                                            {{ $payment->status }}
                                        </span>
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

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/client.js') }}"></script>
</body>
</html>