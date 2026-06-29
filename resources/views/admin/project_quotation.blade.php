<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Materials and Labor Quotation</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">
            <div class="page-header">
                <div>
                    <h1>Project Materials and Labor Quotation</h1>
                    <p>Manage materials and labor costs for all projects.</p>
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
                    <div class="filter-tabs" id="quotationFilterTabs">
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

                <div class="table-wrapper">
                    <table class="data-table" id="materialsTable">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Project Name</th>
                                <th style="text-align:left;">Client</th>
                                <th style="text-align:center;">Current Phase</th>
                                <th style="text-align:center;">Total Materials</th>
                                <th style="text-align:center;">Estimated Project Cost</th>
                                <th style="text-align:center;">Date Created</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projects as $project)
                            @php
                                $matCount    = $project->activeMaterials->count();
                                $payment     = $project->payments->first();
                                $projCost    = $payment ? (float) $payment->contract_amount
                                             : ($project->activeMaterials->sum('total_cost') + $project->activeLabor->sum('total_cost'));
                                $phase     = strtolower($project->current_phase ?? 'planning');
                                $phaseColors = [
                                    'planning'    => ['bg'=>'#FEF3C7','color'=>'#92400E','shadow'=>'rgba(245,158,11,.2)'],
                                    'procurement' => ['bg'=>'#EDE9FE','color'=>'#5B21B6','shadow'=>'rgba(139,92,246,.2)'],
                                    'matl_prep'   => ['bg'=>'#CFFAFE','color'=>'#0E7490','shadow'=>'rgba(6,182,212,.2)'],
                                    'fabrication' => ['bg'=>'#2563EB','color'=>'#fff','shadow'=>'rgba(37,99,235,.3)'],
                                    'inspection'  => ['bg'=>'#EC4899','color'=>'#fff','shadow'=>'rgba(236,72,153,.3)'],
                                    'painting'    => ['bg'=>'#14B8A6','color'=>'#fff','shadow'=>'rgba(20,184,166,.3)'],
                                    'completion'  => ['bg'=>'#10B981','color'=>'#fff','shadow'=>'rgba(16,185,129,.3)'],
                                    'delivery'    => ['bg'=>'#059669','color'=>'#fff','shadow'=>'rgba(5,150,105,.3)'],
                                ];
                                $pc = $phaseColors[$phase] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280','shadow'=>'rgba(0,0,0,.1)'];
                            @endphp
                            <tr data-status="{{ $project->status }}">
                                <td><strong>{{ $project->name }}</strong></td>
                                <td><span class="client-pill">{{ $project->client }}</span></td>
                                <td style="text-align:center;">
                                    <span class="status-badge" style="background:{{ $pc['bg'] }};color:{{ $pc['color'] }};box-shadow:0 0 0 1px {{ $pc['shadow'] }},0 2px 8px {{ $pc['shadow'] }};">
                                        {{ ucwords(str_replace('_',' ',$phase)) }}
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <span style="font-weight:700;">{{ $matCount }}</span>
                                    <span style="color:var(--muted);font-size:13px;"> material{{ $matCount !== 1 ? 's' : '' }}</span>
                                </td>
                                <td style="text-align:center;"><strong>₱{{ number_format($projCost, 2) }}</strong></td>
                                <td style="text-align:center;">{{ $project->created_at->format('M d, Y') }}</td>
                                <td class="action-cell">
                                    <a href="{{ route('admin.project_materials.detail', $project->id) }}"
                                       class="action-btn view" title="View Materials">
                                        <i data-lucide="eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr id="noDataRow">
                                <td colspan="7" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="inbox" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;">No projects found.</div>
                                    <div style="font-size:13px;margin-top:4px;">Add projects via the <strong>Projects</strong> page.</div>
                                </td>
                            </tr>
                            @endforelse
                            {{-- Filter empty state (shown by JS when a tab has no matching rows) --}}
                            <tr id="filterEmptyRow" style="display:none;">
                                <td colspan="7" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="folder-open" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;" id="filterEmptyMsg">No projects in this category.</div>
                                    <div style="font-size:13px;margin-top:4px;color:var(--muted);">Try switching to a different tab or check the <strong>Projects</strong> page.</div>
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
    <script>
        var currentStatusFilter = 'active';

        var filterEmptyMessages = {
            'active':    'No active projects yet.',
            'completed': 'No completed projects yet.',
            'archived':  'No archived projects.'
        };

        function applyFilters() {
            var q        = (document.getElementById('projectSearch').value || '').toLowerCase();
            var emptyRow = document.getElementById('filterEmptyRow');
            var emptyMsg = document.getElementById('filterEmptyMsg');
            var visible  = 0;

            document.querySelectorAll('#materialsTable tbody tr[data-status]').forEach(function(row) {
                var status      = (row.dataset.status || '').toLowerCase();
                var matchSearch = row.textContent.toLowerCase().indexOf(q) !== -1;
                var matchFilter = currentStatusFilter === 'active'
                    ? status !== 'archived' && status !== 'completed'
                    : currentStatusFilter === 'completed'
                    ? status === 'completed'
                    : status === 'archived';
                var show = matchSearch && matchFilter;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            if (emptyRow) {
                emptyRow.style.display = visible === 0 ? '' : 'none';
                if (emptyMsg) emptyMsg.textContent = q
                    ? 'No projects match "' + q + '".'
                    : (filterEmptyMessages[currentStatusFilter] || 'No projects in this category.');
                if (visible === 0 && typeof lucide !== 'undefined') lucide.createIcons();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();

            document.getElementById('projectSearch').addEventListener('keyup', applyFilters);

            document.querySelectorAll('#quotationFilterTabs .filter-tab').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('#quotationFilterTabs .filter-tab').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentStatusFilter = this.dataset.filter;
                    applyFilters();
                });
            });

            applyFilters();
        });
    </script>
</body>
</html>
