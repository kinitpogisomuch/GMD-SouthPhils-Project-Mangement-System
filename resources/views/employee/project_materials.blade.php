<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usage Log | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.employee.header')

    <main class="admin-content">
            <div class="pv-page-header">
                <div>
                    <h1>Usage Log</h1>
                    <p>Track material usage across all active projects.</p>
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
                        <input type="text" id="usageSearch" placeholder="Search project or client...">
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="usageTable">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Client</th>
                                <th>Current Phase</th>
                                <th>Usage Entries Logged</th>
                                <th>Total Qty Used</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projects as $project)
                            <tr>
                                <td><strong>{{ $project->name }}</strong></td>
                                <td>{{ $project->client }}</td>
                                <td>
                                    <span class="status-badge {{ $project->status === 'completed' ? 'completed' : 'ongoing' }}">
                                        {{ ucfirst(str_replace('_', ' ', $project->current_phase ?? 'Planning')) }}
                                    </span>
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
                                <td class="action-cell">
                                    <a href="{{ route('employee.material_usage.detail', $project->id) }}"
                                       class="action-btn view" title="View Usage Log">
                                        <i data-lucide="clipboard-list"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">
                                    No active projects found.
                                </td>
                            </tr>
                            @endforelse
                            @if($projects->isNotEmpty())
                            <tr id="noUsageRow" style="display:none;">
                                <td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">
                                    No projects match your search.
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/employee.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var searchInput = document.getElementById('usageSearch');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    var q = this.value.toLowerCase();
                    var visibleCount = 0;
                    document.querySelectorAll('#usageTable tbody tr').forEach(function(row) {
                        if (row.id === 'noUsageRow') return;
                        var visible = row.textContent.toLowerCase().indexOf(q) !== -1;
                        row.style.display = visible ? '' : 'none';
                        if (visible) visibleCount++;
                    });
                    var noRow = document.getElementById('noUsageRow');
                    if (noRow) noRow.style.display = visibleCount === 0 ? '' : 'none';
                });
            }
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>
