<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Usage | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">
            <div class="page-header">
                <div>
                    <h1>Material Usage</h1>
                    <p>Track actual materials consumed during fabrication for each project.</p>
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

            <div class="table-card">
                <div class="table-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="projectSearch" placeholder="Search project or client...">
                    </div>
                    <div class="filter-group">
                        <select class="filter-select" id="statusFilter">
                            <option value="all">All Projects</option>
                            <option value="active">Active Only</option>
                            <option value="archived">Archived Only</option>
                        </select>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="usageTable">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Client</th>
                                <th>Current Phase</th>
                                <th>Planned Materials</th>
                                <th>Usage Entries Logged</th>
                                <th>Total Qty Used</th>
                                <th>Date Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projects as $project)
                            <tr data-status="{{ $project->status }}">
                                <td><strong>{{ $project->name }}</strong></td>
                                <td>{{ $project->client }}</td>
                                <td>
                                    <span class="status-badge {{ $project->current_phase === 'delivery' ? 'completed' : 'ongoing' }}">
                                        {{ ucfirst(str_replace('_', ' ', $project->current_phase ?? 'Planning')) }}
                                    </span>
                                </td>
                                <td>
                                    @php $matCount = $project->activeMaterials->count(); @endphp
                                    <span style="font-weight:700;">{{ $matCount }}</span>
                                    <span style="color:var(--muted);font-size:13px;"> material{{ $matCount !== 1 ? 's' : '' }}</span>
                                </td>
                                <td>
                                    @php $usageCount = $project->activeMaterialUsages->count(); @endphp
                                    <span style="font-weight:700;">{{ $usageCount }}</span>
                                    <span style="color:var(--muted);font-size:13px;"> entr{{ $usageCount !== 1 ? 'ies' : 'y' }}</span>
                                </td>
                                <td>
                                    @php $totalQty = $project->activeMaterialUsages->sum('quantity_used'); @endphp
                                    <strong>{{ number_format($totalQty, 0) }}</strong>
                                </td>
                                <td>{{ $project->created_at->format('M d, Y') }}</td>
                                <td class="action-cell">
                                    <a href="{{ route('admin.material_usage.detail', $project->id) }}"
                                       class="action-btn view" title="View Material Usage">
                                        <i data-lucide="clipboard-list"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:40px;color:var(--muted);">
                                    No projects found. Add projects via the <strong>Projects</strong> page.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        var currentStatusFilter = 'all';

        function applyFilters() {
            var q = (document.getElementById('projectSearch').value || '').toLowerCase();
            document.querySelectorAll('#usageTable tbody tr').forEach(function(row) {
                var status = (row.dataset.status || '').toLowerCase();
                var matchSearch = row.textContent.toLowerCase().indexOf(q) !== -1;
                var matchFilter = currentStatusFilter === 'all'
                    || (currentStatusFilter === 'archived' && status === 'archived')
                    || (currentStatusFilter === 'active'   && status !== 'archived');
                row.style.display = (matchSearch && matchFilter) ? '' : 'none';
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            var searchInput = document.getElementById('projectSearch');
            if (searchInput) searchInput.addEventListener('keyup', applyFilters);

            var statusFilter = document.getElementById('statusFilter');
            if (statusFilter) {
                statusFilter.addEventListener('change', function() {
                    currentStatusFilter = this.value;
                    applyFilters();
                });
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>
