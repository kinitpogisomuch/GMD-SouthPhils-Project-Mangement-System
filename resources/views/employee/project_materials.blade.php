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
                            @php
                                // phones show "Fabrication of" as a small label above the tank name
                                $ulPrefix = '';
                                $ulName   = $project->name;
                                if (preg_match('/^(Fabrication of)\s+(.+)$/i', (string) $project->name, $ulM)) {
                                    [$ulPrefix, $ulName] = [$ulM[1], $ulM[2]];
                                }
                            @endphp
                            <tr class="ul-row">
                                <td class="ul-name">
                                    <strong class="ul-name-full">{{ $project->name }}</strong>
                                    <span class="ul-name-split">
                                        @if($ulPrefix)<span class="ul-prefix">{{ $ulPrefix }}</span>@endif
                                        <strong>{{ $ulName }}</strong>
                                    </span>
                                </td>
                                <td class="ul-client">{{ $project->live_client_name }}</td>
                                <td class="ul-phase">
                                    <span class="status-badge {{ $project->status === 'completed' ? 'completed' : 'ongoing' }}">
                                        {{ ucfirst(str_replace('_', ' ', $project->current_phase ?? 'Planning')) }}
                                    </span>
                                </td>
                                <td class="ul-tile" data-label="Entries Logged">
                                    @php $usageCount = $project->activeMaterialUsages->count(); @endphp
                                    <span style="font-weight:700;">{{ $usageCount }}</span>
                                    <span style="color:var(--muted);font-size:13px;"> entr{{ $usageCount !== 1 ? 'ies' : 'y' }}</span>
                                </td>
                                <td class="ul-tile" data-label="Total Qty Used">
                                    @php $totalQty = $project->activeMaterialUsages->sum('quantity_used'); @endphp
                                    <strong>{{ number_format($totalQty, 0) }}</strong>
                                </td>
                                <td class="action-cell ul-action">
                                    <a href="{{ route('employee.material_usage.detail', $project->id) }}"
                                       class="action-btn view" title="View Usage Log">
                                        <i data-lucide="clipboard-list"></i><span class="ul-action-text">View Usage Log</span>
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
    @if(session('success'))
    {{-- Saved (e.g. material usage logged, material requested): small dark toast centered below
         the header. Longer messages stay a little longer. --}}
    <div class="toast" id="empMaterialsToast" role="status">
        <i data-lucide="check-circle"></i>
        <span>{{ session('success') }}</span>
    </div>
    <script>
        window.addEventListener('load', function () {
            var t = document.getElementById('empMaterialsToast');
            if (!t) return;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            var ms = t.textContent.trim().length > 80 ? 5000 : 3000;
            requestAnimationFrame(function () { requestAnimationFrame(function () { t.classList.add('show'); }); });
            setTimeout(function () { t.classList.remove('show'); }, ms);
        });
    </script>
    @endif
</body>
</html>
