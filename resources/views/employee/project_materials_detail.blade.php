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

    <div class="admin-layout">
        @include('partials.employee.sidebar')

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
                <button type="button" class="btn btn-warning" id="openRequestMaterialModal">
                    <i data-lucide="alert-triangle"></i>
                    Report Material Shortage
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

            {{-- Materials Table --}}
            <div class="table-card">
                <div class="table-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="materialSearch" placeholder="Search material...">
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="materialsTable">
                        <thead>
                            <tr>
                                <th>Material Name</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Price Per Unit</th>
                                <th>Total Cost</th>
                                <th>Date Added</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($materials as $material)
                            <tr>
                                <td>
                                    <strong>{{ $material->material_name }}</strong>
                                    @if($material->notes)
                                    <div style="font-size:12px;color:var(--muted);margin-top:2px;">{{ Str::limit($material->notes, 60) }}</div>
                                    @endif
                                </td>
                                <td>{{ $material->category ?: '—' }}</td>
                                <td>{{ number_format($material->quantity, 0) }}</td>
                                <td>₱{{ number_format($material->price_per_unit, 2) }}</td>
                                <td><strong>₱{{ number_format($material->total_cost, 2) }}</strong></td>
                                <td>{{ $material->created_at->format('M d, Y') }}</td>
                                <td>
                                    <button type="button" class="btn btn-outline btn-sm request-material-btn"
                                        data-id="{{ $material->id }}"
                                        data-name="{{ $material->material_name }}"
                                        data-unit="{{ $material->unit }}">
                                        <i data-lucide="alert-triangle"></i>
                                        Request
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">
                                    No materials have been added to this project yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- My Material Requests --}}
            <div class="table-card" style="margin-top:24px;">
                <div class="table-toolbar">
                    <span class="card-title">My Material Requests</span>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th>Quantity Needed</th>
                                <th>Unit</th>
                                <th>Notes</th>
                                <th>Date Requested</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($myRequests as $req)
                            <tr>
                                <td><strong>{{ $req->material }}</strong></td>
                                <td>{{ number_format($req->quantity, 0) }}</td>
                                <td>{{ $req->unit ?: '—' }}</td>
                                <td>{{ $req->notes ? Str::limit($req->notes, 60) : '—' }}</td>
                                <td>{{ $req->requested_date->format('M d, Y') }}</td>
                                <td>
                                    <span class="status-badge {{ $req->status === 'fulfilled' ? 'completed' : $req->status }}">
                                        {{ ucfirst($req->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">
                                    You haven't reported any material shortages for this project yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    {{-- ===================== REQUEST MATERIAL MODAL ===================== --}}
    <div class="modal-overlay" id="requestMaterialModal">
        <div class="modal-card" style="max-width:480px;">
            <div class="modal-header">
                <div>
                    <h2>Report Material Shortage</h2>
                    <p>Let the admin team know a material is running short.</p>
                </div>
                <button class="modal-close" type="button" id="closeRequestMaterialModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('employee.project_materials.request', $project->id) }}">
                @csrf
                <input type="hidden" name="project_material_id" id="reqMaterialId">

                <div class="form-group">
                    <label>Material Name</label>
                    <input type="text" name="material_name" id="reqMaterialName" required placeholder="e.g. Welding Electrodes">
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Quantity Needed</label>
                        <input type="number" name="quantity" id="reqQuantity" required min="1" step="1" placeholder="e.g. 50">
                    </div>
                    <div class="form-group">
                        <label>Unit</label>
                        <input type="text" name="unit" id="reqUnit" placeholder="e.g. kg, pcs">
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="3" placeholder="Describe the shortage or why more is needed..."></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelRequestMaterial">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="send"></i>
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>

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

            var requestModal = document.getElementById('requestMaterialModal');

            function openRequestModal() {
                if (requestModal) { requestModal.classList.add('show'); document.body.style.overflow = 'hidden'; }
            }

            function closeRequestModal() {
                if (requestModal) { requestModal.classList.remove('show'); document.body.style.overflow = ''; }
                document.getElementById('reqMaterialId').value = '';
                document.getElementById('reqMaterialName').value = '';
                document.getElementById('reqMaterialName').readOnly = false;
                document.getElementById('reqQuantity').value = '';
                document.getElementById('reqUnit').value = '';
            }

            var openBtn = document.getElementById('openRequestMaterialModal');
            if (openBtn) openBtn.addEventListener('click', function() { closeRequestModal(); openRequestModal(); });

            document.querySelectorAll('.request-material-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    closeRequestModal();
                    document.getElementById('reqMaterialId').value = this.dataset.id;
                    document.getElementById('reqMaterialName').value = this.dataset.name;
                    document.getElementById('reqMaterialName').readOnly = true;
                    document.getElementById('reqUnit').value = this.dataset.unit || '';
                    openRequestModal();
                });
            });

            var closeBtn = document.getElementById('closeRequestMaterialModal');
            if (closeBtn) closeBtn.addEventListener('click', closeRequestModal);

            var cancelBtn = document.getElementById('cancelRequestMaterial');
            if (cancelBtn) cancelBtn.addEventListener('click', closeRequestModal);

            if (requestModal) {
                requestModal.addEventListener('click', function(e) {
                    if (e.target === this) closeRequestModal();
                });
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>
