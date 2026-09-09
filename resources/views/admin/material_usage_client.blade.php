<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $clientName }} — Materials | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('admin.material_usage') }}" style="color:var(--muted);text-decoration:none;font-weight:600;">Materials</a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="font-weight:700;color:var(--dark);">{{ $clientName }}</span>
            </div>

            @php $activeProjectCount = $projects->whereNotIn('status', ['completed', 'archived'])->count(); @endphp
            <div class="page-header">
                <div>
                    <h1>{{ $clientName }}</h1>
                    <p>{{ $activeProjectCount }} active project{{ $activeProjectCount !== 1 ? 's' : '' }} for this client.</p>
                </div>
            </div>

            @if(session('success'))
            <div class="alert-banner success"><i data-lucide="check-circle"></i> {{ session('success') }}</div>
            @endif
            @if(session('error'))
            <div class="alert-banner error"><i data-lucide="alert-circle"></i> {{ session('error') }}</div>
            @endif

            <div class="table-card">
                <div style="display:flex;align-items:center;gap:16px;padding:0 20px 14px;border-bottom:1px solid var(--border);margin-bottom:0;">
                    <div style="flex-shrink:0;">
                        <div style="font-size:15px;font-weight:700;color:var(--dark);">Project Material Usage</div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;">Track BOM planned materials, actual purchases, and cost variance per project.</div>
                    </div>
                    <div style="flex:1;display:flex;justify-content:center;">
                        <div style="display:flex;align-items:center;gap:8px;background:#f5f6f8;border-radius:999px;padding:0 18px;border:1px solid var(--border);width:280px;height:42px;">
                            <i data-lucide="search" style="width:15px;height:15px;color:#888;flex-shrink:0;"></i>
                            <input type="text" id="projectSearch" placeholder="Search project..."
                                style="border:none;background:transparent;font-size:13px;outline:none;width:100%;color:var(--dark);height:100%;">
                        </div>
                    </div>
                    <div class="filter-tabs" id="usageFilterTabs" style="flex-shrink:0;">
                        <button type="button" class="filter-tab active" data-filter="active">
                            Active
                            <span class="filter-count">{{ $projects->whereNotIn('status', ['completed', 'archived'])->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="completed">
                            Completed
                            <span class="filter-count">{{ $projects->where('status', 'completed')->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="archived">
                            Archived
                            <span class="filter-count">{{ $projects->where('status', 'archived')->count() }}</span>
                        </button>
                    </div>
                </div>

                <div style="max-height:520px;overflow-y:auto;overflow-x:auto;">
                    <table class="data-table" id="usageTable" style="margin:0;">
                        <thead style="position:sticky;top:0;z-index:2;">
                            <tr>
                                <th>Project Name</th>
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
                            <tr data-status="{{ $project->status }}" data-search="{{ strtolower($project->name) }}">
                                @php
                                    $namePrefix = '';
                                    $nameMain   = $project->name;
                                    if (preg_match('/^(Fabrication of)\s+(.+)$/i', $project->name, $nm)) {
                                        $namePrefix = $nm[1];
                                        $nameMain   = $nm[2];
                                    }
                                @endphp
                                <td style="overflow:hidden;">
                                    <span style="display:inline-flex;flex-direction:column;max-width:100%;min-width:0;">
                                        @if($namePrefix)
                                            <span style="font-size:9px;font-weight:700;color:var(--muted);letter-spacing:.05em;line-height:1.2;text-transform:uppercase;white-space:nowrap;">{{ $namePrefix }}</span>
                                        @endif
                                        <span style="font-size:12.5px;font-weight:800;color:var(--dark);line-height:1.3;white-space:normal;word-break:break-word;">{{ $nameMain }}</span>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge {{ $project->status === 'completed' ? 'completed' : 'ongoing' }}">
                                        {{ ucfirst(str_replace('_', ' ', $project->current_phase ?? 'Planning')) }}
                                    </span>
                                </td>
                                <td>
                                    @php $matCount = $project->activeMaterials->count(); @endphp
                                    <span style="font-size:14px;font-weight:700;color:var(--dark);">{{ $matCount }}</span>
                                    <span style="font-size:13px;font-weight:400;color:var(--muted);"> material{{ $matCount !== 1 ? 's' : '' }}</span>
                                </td>
                                <td>
                                    @php $usageCount = $project->activeMaterialUsages->count(); @endphp
                                    <span style="font-size:14px;font-weight:700;color:var(--dark);">{{ $usageCount }}</span>
                                    <span style="font-size:13px;font-weight:400;color:var(--muted);"> entr{{ $usageCount !== 1 ? 'ies' : 'y' }}</span>
                                </td>
                                <td>
                                    @php $totalQty = $project->activeMaterialUsages->sum('quantity_used'); @endphp
                                    <strong style="font-size:14px;font-weight:700;color:var(--dark);">{{ number_format($totalQty, 0) }}</strong>
                                </td>
                                <td style="font-size:14px;font-weight:700;color:var(--dark);">{{ $project->created_at->format('M d, Y') }}</td>
                                <td class="action-cell">
                                    <a href="{{ route('admin.material_usage.detail', $project->id) }}"
                                       class="action-btn view" title="View Materials">
                                        <i data-lucide="eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="inbox" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;">No projects found for this client.</div>
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
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();

            var currentStatusFilter = 'active';

            var usageFilterMessages = {
                'active':    'No active projects yet.',
                'completed': 'No completed projects yet.',
                'archived':  'No archived projects.'
            };

            function applyUsageFilters() {
                var q            = (document.getElementById('projectSearch').value || '').toLowerCase();
                var tbody        = document.querySelector('#usageTable tbody');
                var noMatchRow   = document.getElementById('usageNoMatch');
                var visibleCount = 0;

                document.querySelectorAll('#usageTable tbody tr:not(#usageNoMatch)').forEach(function(row) {
                    var status      = (row.dataset.status || '').toLowerCase();
                    var matchSearch = !q || (row.dataset.search || '').indexOf(q) !== -1;
                    var matchFilter = currentStatusFilter === 'active'
                        ? status !== 'archived' && status !== 'completed'
                        : currentStatusFilter === 'completed'
                        ? status === 'completed'
                        : status === 'archived';
                    var show = matchSearch && matchFilter;
                    row.style.display = show ? '' : 'none';
                    if (show) visibleCount++;
                });

                if (!noMatchRow) {
                    noMatchRow = document.createElement('tr');
                    noMatchRow.id = 'usageNoMatch';
                    tbody.appendChild(noMatchRow);
                }
                var msg = q
                    ? 'No projects match &ldquo;' + q + '&rdquo;.'
                    : (usageFilterMessages[currentStatusFilter] || 'No projects in this category.');
                noMatchRow.innerHTML =
                    '<td colspan="7" style="text-align:center;padding:60px 20px;color:var(--muted);">' +
                    '<i data-lucide="folder-open" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>' +
                    '<div style="font-size:14px;font-weight:700;">' + msg + '</div>' +
                    '</td>';
                noMatchRow.style.display = visibleCount === 0 ? '' : 'none';
                if (visibleCount === 0 && typeof lucide !== 'undefined') lucide.createIcons();
            }

            document.getElementById('projectSearch')?.addEventListener('keyup', applyUsageFilters);

            document.querySelectorAll('#usageFilterTabs .filter-tab').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('#usageFilterTabs .filter-tab').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentStatusFilter = this.dataset.filter;
                    applyUsageFilters();
                });
            });

            applyUsageFilters();
        });
    </script>
</body>
</html>
