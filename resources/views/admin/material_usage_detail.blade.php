<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project->name }} — Material Usage | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            {{-- Breadcrumb --}}
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('admin.material_usage') }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    Material Usage
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="color:var(--dark);font-weight:700;">{{ $project->name }}</span>
            </div>

            <div class="page-header">
                <div>
                    <h1>{{ $project->name }}</h1>
                    <p>Log materials actually consumed during fabrication and track usage against the planned BOM.</p>
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

            {{-- Project Info --}}
            <div class="table-card" style="margin-bottom:24px;">
                <div style="padding:20px 24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px 24px;">
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
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:4px;">Date Created</div>
                        <div style="font-weight:600;">{{ $project->created_at->format('M d, Y') }}</div>
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
                                <th>Planned Qty</th>
                                <th>Used Qty</th>
                                <th>Remaining</th>
                                <th>Status</th>
                                <th>Actions</th>
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
                                <td>{{ number_format($row['material']->quantity, 0) }}</td>
                                <td>{{ number_format($row['usedQty'], 0) }}</td>
                                <td>{{ number_format($row['remaining'], 0) }}</td>
                                <td>
                                    <span class="status-badge {{ $row['statusKey'] }}">
                                        {{ $statusLabels[$row['statusKey']] }}
                                    </span>
                                </td>
                                <td class="action-cell">
                                    <button class="action-btn view" type="button" onclick="openLogUsageModal({{ $row['material']->id }}, {{ \Illuminate\Support\Js::from($row['material']->material_name) }}, {{ \Illuminate\Support\Js::from($row['material']->unit) }})" title="Log usage for this material">
                                        <i data-lucide="pencil"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="text-align:center;padding:32px;color:var(--muted);">
                                    No planned materials found for this project. Add materials via <strong>Project Quotations</strong>.
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
                                <th>Used For</th>
                                <th>Notes</th>
                                <th>Recorded By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($usageEntries as $entry)
                            <tr>
                                <td>{{ $entry->used_date->format('M d, Y') }}</td>
                                <td><strong>{{ $entry->material_name }}</strong></td>
                                <td>{{ number_format($entry->quantity_used, 0) }}</td>
                                <td>{{ $entry->used_for ? ucfirst(str_replace('_', ' ', $entry->used_for)) : '—' }}</td>
                                <td>{{ $entry->notes ?? '—' }}</td>
                                <td>{{ $entry->recorded_by ?? '—' }}</td>
                                <td class="action-cell">
                                    <form method="POST" action="{{ route('admin.material_usage.archive', [$project->id, $entry->id]) }}" style="display:inline;"
                                          onsubmit="return confirm('{{ $entry->status === 'archived' ? 'Restore this usage entry?' : 'Archive this usage entry?' }}');">
                                        @csrf
                                        @method('PATCH')
                                        <button class="action-btn view" type="submit" title="{{ $entry->status === 'archived' ? 'Restore' : 'Archive' }}">
                                            <i data-lucide="{{ $entry->status === 'archived' ? 'archive-restore' : 'archive' }}"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">
                                    No usage entries yet. Use the edit icon in <strong>Materials vs Usage</strong> to log usage.
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

            <form method="POST" action="{{ route('admin.material_usage.store', $project->id) }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Material</label>
                        <div class="form-static" id="usageMaterialDisplay"></div>
                        <input type="hidden" name="project_material_id" id="usageMaterialIdInput">
                        <input type="hidden" name="material_name" id="usageMaterialNameInput">
                        <input type="hidden" name="unit" id="usageUnitInput">
                    </div>
                    <div class="form-group">
                        <label>Quantity Used <span style="color:var(--danger);">*</span></label>
                        <input type="number" name="quantity_used" min="0.01" step="0.01" onwheel="this.blur()" required>
                    </div>
                    <div class="form-group">
                        <label>Date Used <span style="color:var(--danger);">*</span></label>
                        <input type="date" name="used_date" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="form-group form-group-full">
                        <label>Notes</label>
                        <textarea name="notes" rows="3" placeholder="Optional notes about this usage entry"></textarea>
                    </div>
                </div>
                <div style="padding:14px 20px;border-top:1px solid rgba(0,0,0,0.06);display:flex;justify-content:flex-end;gap:10px;">
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
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        function openModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
        }
        function closeModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
        }

        function openLogUsageModal(materialId, materialName, unit) {
            document.getElementById('usageMaterialIdInput').value = materialId;
            document.getElementById('usageMaterialNameInput').value = materialName;
            document.getElementById('usageUnitInput').value = unit;
            document.getElementById('usageMaterialDisplay').textContent = unit ? (materialName + ' (' + unit + ')') : materialName;
            openModal('logUsageModal');
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
