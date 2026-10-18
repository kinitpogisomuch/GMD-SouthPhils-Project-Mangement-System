<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $client }} | Projects | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">
            <div class="page-header">
                <div>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <a href="{{ route('admin.projects', ['tab' => $tab]) }}" style="font-size:13px;font-weight:700;color:var(--muted);text-decoration:none;">Projects</a>
                        <span style="font-size:13px;color:var(--muted);">/</span>
                        <span style="font-size:13px;font-weight:800;color:var(--dark);">{{ $client }}</span>
                    </div>
                    <h1>{{ $client }}'s Projects</h1>
                    <p>This client's completed and archived storage tank projects. Active projects are listed on the Projects page.</p>
                </div>
                <a href="{{ route('admin.projects', ['tab' => $tab]) }}" class="cancel-btn" style="text-decoration:none;">
                    <i data-lucide="arrow-left"></i>
                    All Projects
                </a>
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

            <div class="table-card" style="padding-bottom:0;">
                <div class="table-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="projectSearch" placeholder="Search project...">
                    </div>
                    <div class="filter-tabs" id="projectFilterTabs">
                        <button type="button" class="filter-tab {{ $tab === 'completed' ? 'active' : '' }}" data-filter="completed">
                            Completed Projects
                            <span class="filter-count">{{ $projects->where('status', 'completed')->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab {{ $tab === 'archived' ? 'active' : '' }}" data-filter="archived">
                            Archived
                            <span class="filter-count">{{ $projects->where('status', 'archived')->count() }}</span>
                        </button>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="projectsTable">
                        <colgroup>
                            <col style="width:19%;">  {{-- Project Name --}}
                            <col style="width:7%;">   {{-- Capacity --}}
                            <col style="width:9%;">  {{-- Start Date --}}
                            <col style="width:9%;">  {{-- End Date --}}
                            <col style="width:11%;">  {{-- Project Status --}}
                            <col id="deliveryStatusCol" style="width:11%;">  {{-- Delivery Status --}}
                            <col style="width:12%;">  {{-- Payment --}}
                            <col style="width:8%;">   {{-- Progress --}}
                            <col style="width:14%;">  {{-- Action --}}
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th style="text-align:center;">Capacity</th>
                                <th style="text-align:center;">Start Date</th>
                                <th style="text-align:center;">End Date</th>
                                <th style="text-align:center;">Project Status</th>
                                <th class="delivery-status-col" style="text-align:center;">Delivery Status</th>
                                <th style="text-align:center;">Payment</th>
                                <th style="text-align:center;">Progress</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($projects as $project)
                            @php
                                $namePrefix = '';
                                $nameMain   = $project->name;
                                if (preg_match('/^(Fabrication of)\s+(.+)$/i', $project->name, $nm)) {
                                    $namePrefix = $nm[1];
                                    $nameMain   = $nm[2];
                                }
                            @endphp
                            <tr data-status="{{ $project->status }}" data-delivery="{{ $project->deliveryStatus() }}">
                                <td style="overflow:hidden;">
                                    <span style="display:inline-flex;flex-direction:column;max-width:100%;min-width:0;">
                                        @if($namePrefix)
                                            <span style="font-size:9px;font-weight:700;color:var(--muted);letter-spacing:.05em;line-height:1.2;text-transform:uppercase;white-space:nowrap;">{{ $namePrefix }}</span>
                                        @endif
                                        <span style="font-size:12.5px;font-weight:800;color:var(--dark);line-height:1.3;white-space:normal;word-break:break-word;">{{ $nameMain }}</span>
                                    </span>
                                </td>
                                <td style="white-space:nowrap;text-align:center;color:var(--muted);font-weight:700;">{{ $project->capacity ?: '—' }}</td>
                                <td style="white-space:nowrap;text-align:center;">{{ $project->start_date->format('M d, Y') }}</td>
                                <td style="white-space:nowrap;text-align:center;">{{ $project->end_date->format('M d, Y') }}</td>
                                <td style="text-align:center;">@include('partials.admin.project_badge', ['kind' => 'phase'])</td>
                                {{-- Delivery Status: tracked separately from completion (Awaiting Delivery → Delivered) --}}
                                <td class="delivery-status-col" style="text-align:center;">@include('partials.admin.project_badge', ['kind' => 'delivery'])</td>
                                <td style="text-align:center;">@include('partials.admin.project_badge', ['kind' => 'payment'])</td>
                                <td style="text-align:center;">@include('partials.admin.project_badge', ['kind' => 'progress'])</td>
                                <td class="action-cell" style="text-align:center;">
                                    @include('partials.admin.project_action_buttons')
                                </td>
                            </tr>
                            @endforeach
                            <tr id="noProjectsRow" style="display:none;">
                                <td colspan="9" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="folder-open" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;" id="noProjectsMsg">No projects in this category.</div>
                                    <div style="font-size:13px;margin-top:4px;">Try switching to a different tab.</div>
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
        function openModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
        }

        function closeModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        var currentArchiveFilter = @json($tab);

        var projectFilterMessages = {
            'completed': 'No completed projects yet.',
            'archived':  'No archived projects.'
        };

        // Delivery Status <col>, and the <col> right after it (where it's re-inserted)
        var deliveryColEl     = document.getElementById('deliveryStatusCol');
        var deliveryColAnchor = deliveryColEl ? deliveryColEl.nextElementSibling : null;

        function applyProjectFilters() {
            var q            = (document.getElementById('projectSearch').value || '').toLowerCase();
            var visibleCount = 0;

            document.querySelectorAll('#projectsTable tbody tr').forEach(function(row) {
                if (row.id === 'noProjectsRow' || !row.dataset.status) return;
                var status      = (row.dataset.status || '').toLowerCase();
                var matchSearch = row.textContent.toLowerCase().indexOf(q) !== -1;
                var show = matchSearch && status === currentArchiveFilter;
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            // The Delivery Status column belongs to the Completed Projects tab (even when it's empty)
            var showDeliveryCol = currentArchiveFilter === 'completed';
            document.querySelectorAll('#projectsTable .delivery-status-col').forEach(function (cell) {
                cell.style.display = showDeliveryCol ? '' : 'none';
            });
            // The column's <col> has to leave the colgroup too — otherwise the cells after it slide
            // into its slot (Payment would land in the hidden Delivery column)
            if (deliveryColEl) {
                if (showDeliveryCol && !deliveryColEl.parentNode) {
                    deliveryColAnchor.parentNode.insertBefore(deliveryColEl, deliveryColAnchor);
                } else if (!showDeliveryCol && deliveryColEl.parentNode) {
                    deliveryColEl.parentNode.removeChild(deliveryColEl);
                }
            }
            document.querySelectorAll('#projectsTable td[colspan]').forEach(function (cell) {
                cell.colSpan = showDeliveryCol ? 9 : 8;
            });

            var noRow = document.getElementById('noProjectsRow');
            var noMsg = document.getElementById('noProjectsMsg');
            if (noRow) {
                noRow.style.display = visibleCount === 0 ? '' : 'none';
                if (noMsg) noMsg.textContent = q
                    ? 'No projects match "' + q + '".'
                    : (projectFilterMessages[currentArchiveFilter] || 'No projects in this category.');
                if (visibleCount === 0 && typeof lucide !== 'undefined') lucide.createIcons();
            }
        }

        function initializeSearch() {
            var input = document.getElementById('projectSearch');
            if (!input) return;
            input.addEventListener('keyup', applyProjectFilters);
        }

        function initializeArchiveFilter() {
            var tabs = document.querySelectorAll('#projectFilterTabs .filter-tab');
            if (!tabs.length) return;
            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    tabs.forEach(function(t) { t.classList.remove('active'); });
                    this.classList.add('active');
                    currentArchiveFilter = this.dataset.filter;
                    applyProjectFilters();
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.modal-overlay').forEach(function(modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) closeModal(this.id);
                });
            });

            initializeSearch();
            initializeArchiveFilter();
            applyProjectFilters();

            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>

    @include('partials.admin.project_actions')
</body>
</html>
