<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project->name }} — Materials | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.employee.header')

    <main class="admin-content">

            {{-- Breadcrumb --}}
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('employee.project_materials') }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    Project Materials
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="color:var(--dark);font-weight:700;">{{ $project->name }}</span>
            </div>

            <div class="pv-page-header">
                <div>
                    <h1>{{ $project->name }}</h1>
                    <p>Materials required for this project — view only.</p>
                </div>
            </div>


            @if(session('error'))
            <div class="alert-banner error">
                <i data-lucide="alert-circle"></i>
                {{ session('error') }}
            </div>
            @endif

            {{-- Project Info --}}
            <div class="table-card" style="margin-bottom:24px;">
                <div style="padding:20px 24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px 24px;">
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:4px;">Project</div>
                        <div style="font-weight:800;color:var(--dark);">{{ $project->name }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:4px;">Client</div>
                        <div style="font-weight:700;">{{ $project->live_client_name }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:4px;">Current Phase</div>
                        <span class="status-badge {{ $project->status === 'completed' ? 'completed' : 'ongoing' }}">
                            {{ ucfirst(str_replace('_', ' ', $project->current_phase ?? 'Planning')) }}
                        </span>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:4px;">Status</div>
                        <span class="status-badge {{ strtolower($project->status ?? 'planning') }}">
                            {{ ucfirst($project->status ?? 'Planning') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Summary Cards --}}
            <div class="page-grid emp-compact-stats" style="margin-bottom:24px;">
                <div class="info-card blue">
                    <div class="info-card-icon blue"><i data-lucide="package"></i></div>
                    <h3>Total Materials</h3>
                    <div class="value">{{ $totalMaterials }}</div>
                    <div class="info-card-sub">Active entries</div>
                </div>
                <div class="info-card purple">
                    <div class="info-card-icon purple"><i data-lucide="layers"></i></div>
                    <h3>Total Quantity</h3>
                    <div class="value">{{ number_format($totalQuantity, 0) }}</div>
                    <div class="info-card-sub">Combined units</div>
                </div>
                <div class="info-card green">
                    <div class="info-card-icon green"><i data-lucide="banknote"></i></div>
                    <h3>Estimated Cost</h3>
                    <div class="value" style="font-size:1.4rem;">₱{{ number_format($estimatedCost, 2) }}</div>
                    <div class="info-card-sub">Active materials</div>
                </div>
            </div>

            {{-- Planned materials for this project (view only) --}}
            <div class="table-card">
                <div class="table-toolbar">
                    <span class="card-title" style="font-size:15px;font-weight:800;color:var(--dark);">Materials</span>
                    <div class="search-box" style="min-width:200px;">
                        <i data-lucide="search"></i>
                        <input type="text" id="materialSearch" placeholder="Search materials...">
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table emp-mat-table" id="materialsTable">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($materials as $mat)
                            <tr>
                                <td class="emp-mat-name"><strong>{{ $mat->material_name }}</strong></td>
                                <td class="emp-mat-qty">{{ rtrim(rtrim(number_format($mat->quantity, 2), '0'), '.') }}</td>
                                <td class="emp-mat-unit">{{ $mat->unit ?: '—' }}</td>
                                <td class="emp-mat-notes {{ $mat->notes ? '' : 'is-empty' }}">{{ $mat->notes ? Str::limit($mat->notes, 60) : '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" style="text-align:center;padding:40px;color:var(--muted);">
                                    No materials have been listed for this project yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/employee.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var searchInput = document.getElementById('materialSearch');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    var q = this.value.toLowerCase();
                    document.querySelectorAll('#materialsTable tbody tr').forEach(function(row) {
                        row.style.display = row.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
                    });
                });
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
    @if(session('success'))
    {{-- Saved (e.g. material usage logged): small dark toast centered below
         the header. Longer messages stay a little longer. --}}
    <div class="toast" id="empMaterialsDetailToast" role="status">
        <i data-lucide="check-circle"></i>
        <span>{{ session('success') }}</span>
    </div>
    <script>
        window.addEventListener('load', function () {
            var t = document.getElementById('empMaterialsDetailToast');
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
