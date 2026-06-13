<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project->name }} — Material Usage | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.employee.header')

    <div class="admin-layout">
        @include('partials.employee.sidebar')

        <main class="admin-content">

            {{-- Breadcrumb --}}
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('employee.material_usage') }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    Material Usage
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="color:var(--dark);font-weight:700;">{{ $project->name }}</span>
            </div>

            <div class="pv-page-header">
                <div>
                    <h1>{{ $project->name }}</h1>
                    <p>Log materials consumed during fabrication and track usage against the planned BOM.</p>
                </div>
                <button type="button" class="btn btn-primary" onclick="openModal('logUsageModal')">
                    <i data-lucide="plus"></i>
                    Log Usage
                </button>
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

            {{-- Project Info --}}
            <div class="table-card" style="margin-bottom:24px;">
                <div style="padding:20px 24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px 24px;">
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:4px;">Project</div>
                        <div style="font-weight:800;color:var(--dark);">{{ $project->name }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:4px;">Client</div>
                        <div style="font-weight:700;">{{ $project->client }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:4px;">Current Phase</div>
                        <span class="status-badge {{ $project->current_phase === 'delivery' ? 'completed' : 'ongoing' }}">
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
            <div class="page-grid" style="margin-bottom:24px;">
                <div class="info-card blue">
                    <div class="info-card-icon blue"><i data-lucide="package"></i></div>
                    <h3>Planned Materials</h3>
                    <div class="value">{{ $totalPlanned }}</div>
                    <div class="info-card-sub">From the project BOM</div>
                </div>
                <div class="info-card purple">
                    <div class="info-card-icon purple"><i data-lucide="clipboard-list"></i></div>
                    <h3>Usage Entries Logged</h3>
                    <div class="value">{{ $totalLogged }}</div>
                    <div class="info-card-sub">Active log entries</div>
                </div>
                <div class="info-card green">
                    <div class="info-card-icon green"><i data-lucide="layers"></i></div>
                    <h3>Total Quantity Used</h3>
                    <div class="value">{{ number_format($totalQtyUsed, 0) }}</div>
                    <div class="info-card-sub">Combined units consumed</div>
                </div>
            </div>

            {{-- Materials vs Usage --}}
            <div class="table-card" style="margin-bottom:24px;">
                <div class="table-toolbar" style="padding-bottom:0;">
                    <span style="font-weight:700;font-size:15px;">Materials vs Usage</span>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Material Name</th>
                                <th>Unit</th>
                                <th>Planned Qty</th>
                                <th>Used Qty</th>
                                <th>Remaining</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($materialComparison as $row)
                            @php
                                $statusLabels = [
                                    'pending'   => 'Not Started',
                                    'ongoing'   => 'In Use',
                                    'completed' => 'Fully Used',
                                    'shortage'  => 'Over Used',
                                ];
                            @endphp
                            <tr>
                                <td><strong>{{ $row['material']->material_name }}</strong></td>
                                <td>{{ $row['material']->unit }}</td>
                                <td>{{ number_format($row['material']->quantity, 0) }}</td>
                                <td>{{ number_format($row['usedQty'], 0) }}</td>
                                <td>{{ number_format($row['remaining'], 0) }}</td>
                                <td>
                                    <span class="status-badge {{ $row['statusKey'] }}">
                                        {{ $statusLabels[$row['statusKey']] }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="text-align:center;padding:32px;color:var(--muted);">
                                    No planned materials found for this project.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Usage Log --}}
            <div class="table-card">
                <div class="table-toolbar" style="padding-bottom:0;">
                    <span style="font-weight:700;font-size:15px;">Usage Log</span>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date Used</th>
                                <th>Material</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Used For</th>
                                <th>Notes</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($usageEntries as $entry)
                            <tr>
                                <td>{{ $entry->used_date->format('M d, Y') }}</td>
                                <td><strong>{{ $entry->material_name }}</strong></td>
                                <td>{{ number_format($entry->quantity_used, 0) }}</td>
                                <td>{{ $entry->unit ?? '—' }}</td>
                                <td>{{ $entry->used_for ? ucfirst(str_replace('_', ' ', $entry->used_for)) : '—' }}</td>
                                <td>{{ $entry->notes ?? '—' }}</td>
                                <td>{{ $entry->recorded_by ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">
                                    No usage entries yet. Click <strong>Log Usage</strong> to get started.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    {{-- ===================== LOG USAGE MODAL ===================== --}}
    <div class="modal-overlay" id="logUsageModal">
        <div class="modal-card" style="max-width:520px;">
            <div class="modal-header">
                <div>
                    <h2>Log Material Usage</h2>
                    <p>Record materials consumed for <strong>{{ $project->name }}</strong>.</p>
                </div>
                <button class="modal-close" type="button" onclick="closeModal('logUsageModal')">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('employee.material_usage.store', $project->id) }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Material <span style="color:var(--danger);">*</span></label>
                        <select name="project_material_id" id="usageMaterialSelect" onchange="onUsageMaterialChange()" required>
                            <option value="">-- Select planned material --</option>
                            @foreach($plannedMaterials as $m)
                            <option value="{{ $m->id }}" data-name="{{ $m->material_name }}" data-unit="{{ $m->unit }}">
                                {{ $m->material_name }} ({{ $m->unit }})
                            </option>
                            @endforeach
                            <option value="">Other (not in BOM)</option>
                        </select>
                    </div>
                    <div class="form-group form-group-full" id="customMaterialNameGroup" style="display:none;">
                        <label>Material Name <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="material_name" id="usageMaterialName" placeholder="Enter material name">
                    </div>
                    <div class="form-group">
                        <label>Quantity Used <span style="color:var(--danger);">*</span></label>
                        <input type="number" name="quantity_used" min="0.01" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Unit</label>
                        <input type="text" name="unit" id="usageUnit" placeholder="e.g. pcs, kg, m">
                    </div>
                    <div class="form-group">
                        <label>Date Used <span style="color:var(--danger);">*</span></label>
                        <input type="date" name="used_date" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Used For (Phase)</label>
                        <select name="used_for">
                            <option value="">-- Select phase --</option>
                            @foreach(\App\Models\Project::PHASES as $phase)
                            <option value="{{ $phase }}" {{ $project->current_phase === $phase ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $phase)) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form-group-full">
                        <label>Notes</label>
                        <textarea name="notes" rows="3" placeholder="Optional notes about this usage entry"></textarea>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeModal('logUsageModal')">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="check-circle" style="width:15px;height:15px;"></i>
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/employee.js') }}"></script>
    <script>
        function openModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
        }
        function closeModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
        }

        function onUsageMaterialChange() {
            var sel = document.getElementById('usageMaterialSelect');
            var opt = sel.options[sel.selectedIndex];
            var nameInput = document.getElementById('usageMaterialName');
            var unitInput = document.getElementById('usageUnit');
            var customGroup = document.getElementById('customMaterialNameGroup');

            if (opt.dataset.name) {
                nameInput.value = opt.dataset.name;
                unitInput.value = opt.dataset.unit || '';
                customGroup.style.display = 'none';
                nameInput.removeAttribute('required');
            } else {
                nameInput.value = '';
                unitInput.value = '';
                customGroup.style.display = '';
                nameInput.setAttribute('required', 'required');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
                overlay.addEventListener('click', function(e) {
                    if (e.target === this) closeModal(this.id);
                });
            });

            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>
