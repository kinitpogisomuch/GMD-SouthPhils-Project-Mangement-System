<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Materials | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.employee.header')

    <div class="admin-layout">
        @include('partials.employee.sidebar')

        <main class="admin-content">
            <div class="pv-page-header">
                <div>
                    <h1>Project Materials</h1>
                    <p>View materials, cost breakdown, and usage logs for all active projects.</p>
                </div>
            </div>

            <div class="page-grid" style="margin-bottom:24px;">
                <div class="info-card blue">
                    <div class="info-card-icon blue"><i data-lucide="folder-kanban"></i></div>
                    <h3>Active Projects</h3>
                    <div class="value">{{ $totalProjects }}</div>
                    <div class="info-card-sub">Currently in progress</div>
                </div>
                <div class="info-card orange">
                    <div class="info-card-icon orange"><i data-lucide="package"></i></div>
                    <h3>Total Materials</h3>
                    <div class="value">{{ $totalMaterials }}</div>
                    <div class="info-card-sub">Across all projects</div>
                </div>
                <div class="info-card green">
                    <div class="info-card-icon green"><i data-lucide="banknote"></i></div>
                    <h3>Estimated Material Cost</h3>
                    <div class="value" style="font-size:1.4rem;">₱{{ number_format($totalEstimatedCost, 2) }}</div>
                    <div class="info-card-sub">Combined active materials</div>
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

            <div class="emp-tabs">
                <button class="emp-tab active" data-tab="materials">
                    <i data-lucide="package"></i>
                    Materials
                </button>
                <button class="emp-tab" data-tab="usage">
                    <i data-lucide="clipboard-list"></i>
                    Usage Log
                </button>
            </div>

            <div class="emp-tab-content active" id="tab-materials">
                <div class="table-card">
                    <div class="table-toolbar">
                        <div class="search-box">
                            <i data-lucide="search"></i>
                            <input type="text" id="materialsSearch" placeholder="Search project or client...">
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table class="data-table" id="materialsTable">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Client</th>
                                    <th>Current Phase</th>
                                    <th>Total Materials</th>
                                    <th>Estimated Material Cost</th>
                                    <th>Date Created</th>
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
                                        @php $matCount = $project->activeMaterials->count(); @endphp
                                        <span style="font-weight:700;">{{ $matCount }}</span>
                                        <span style="color:var(--muted);font-size:13px;"> material{{ $matCount !== 1 ? 's' : '' }}</span>
                                    </td>
                                    <td><strong>₱{{ number_format($project->activeMaterials->sum('total_cost'), 2) }}</strong></td>
                                    <td>{{ $project->created_at->format('M d, Y') }}</td>
                                    <td class="action-cell">
                                        <a href="{{ route('employee.project_materials.detail', $project->id) }}"
                                           class="action-btn view" title="View Materials">
                                            <i data-lucide="package-open"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">
                                        No active projects found.
                                    </td>
                                </tr>
                                @endforelse
                                @if($projects->isNotEmpty())
                                <tr id="noMaterialsRow" style="display:none;">
                                    <td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">
                                        No projects match your search.
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="emp-tab-content" id="tab-usage">
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
                                    <th>Planned Materials</th>
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
                                    <td class="action-cell">
                                        <a href="{{ route('employee.material_usage.detail', $project->id) }}"
                                           class="action-btn view" title="View Material Usage">
                                            <i data-lucide="clipboard-list"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">
                                        No active projects found.
                                    </td>
                                </tr>
                                @endforelse
                                @if($projects->isNotEmpty())
                                <tr id="noUsageRow" style="display:none;">
                                    <td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">
                                        No projects match your search.
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/employee.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching
            function activateTab(name) {
                document.querySelectorAll('.emp-tab').forEach(function (b) {
                    b.classList.toggle('active', b.getAttribute('data-tab') === name);
                });
                document.querySelectorAll('.emp-tab-content').forEach(function (p) {
                    p.classList.toggle('active', p.id === 'tab-' + name);
                });
            }

            document.querySelectorAll('.emp-tab').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    activateTab(this.getAttribute('data-tab'));
                });
            });

            if (window.location.hash === '#usage') {
                activateTab('usage');
            }

            function setupSearch(inputId, tableId, noRowId) {
                var searchInput = document.getElementById(inputId);
                if (!searchInput) return;
                searchInput.addEventListener('keyup', function() {
                    var q = this.value.toLowerCase();
                    var visibleCount = 0;
                    document.querySelectorAll('#' + tableId + ' tbody tr').forEach(function(row) {
                        if (row.id === noRowId) return;
                        var visible = row.textContent.toLowerCase().indexOf(q) !== -1;
                        row.style.display = visible ? '' : 'none';
                        if (visible) visibleCount++;
                    });
                    var noRow = document.getElementById(noRowId);
                    if (noRow) noRow.style.display = visibleCount === 0 ? '' : 'none';
                });
            }

            setupSearch('materialsSearch', 'materialsTable', 'noMaterialsRow');
            setupSearch('usageSearch', 'usageTable', 'noUsageRow');

            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>
