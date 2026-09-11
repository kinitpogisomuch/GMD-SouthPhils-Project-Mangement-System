<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Materials and Labor Quotation</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        .pf-client-name { font-size: 14.5px; font-weight: 800; color: var(--dark); }
    </style>
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

            <div class="table-card" id="clientListCard">
                <div class="table-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="clientListSearch" placeholder="Search client...">
                    </div>
                    <select class="filter-select" id="clientSortSelect">
                        <option value="default">Sort: Active Projects First</option>
                        <option value="alpha-asc">Sort: A–Z</option>
                        <option value="alpha-desc">Sort: Z–A</option>
                    </select>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="clientListTable">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th style="text-align:center;">Total Projects</th>
                                <th style="text-align:center;">Active</th>
                                <th style="text-align:center;">Completed</th>
                                <th style="text-align:center;">Archived</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clientGroups as $g)
                            <tr data-search="{{ strtolower($g['client']) }}" onclick="window.location='{{ route('admin.project_materials.client', $g['client']) }}'" style="cursor:pointer;">
                                <td><span class="pf-client-name">{{ $g['client'] }}</span></td>
                                <td style="text-align:center;"><span class="client-pill" style="background-color:#F3F4F6;color:#1F2937;border-color:#D1D5DB;">{{ $g['total'] }}</span></td>
                                <td style="text-align:center;"><span class="client-pill" style="background-color:#EAF0FF;color:#2563EB;border-color:#BFDBFE;">{{ $g['active'] }}</span></td>
                                <td style="text-align:center;"><span class="client-pill" style="background-color:#E7F6EC;color:#207A3A;border-color:#A7E3B8;">{{ $g['completed'] }}</span></td>
                                <td style="text-align:center;"><span class="client-pill" style="background-color:#F3F4F6;color:#6B7280;border-color:#D1D5DB;">{{ $g['archived'] }}</span></td>
                                <td class="action-cell" style="text-align:center;">
                                    <a href="{{ route('admin.project_materials.client', $g['client']) }}" class="action-btn view" title="View Client's Projects" onclick="event.stopPropagation()">
                                        <i data-lucide="eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="inbox" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;">No clients with projects yet.</div>
                                    <div style="font-size:13px;margin-top:4px;">Add projects via the <strong>Projects</strong> page.</div>
                                </td>
                            </tr>
                            @endforelse
                            @if($clientGroups->isNotEmpty())
                            <tr id="clientListEmptyRow" style="display:none;">
                                <td colspan="6" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="search-x" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;">No clients match your search.</div>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        function applyClientListFilters() {
            var q = (document.getElementById('clientListSearch').value || '').toLowerCase();
            var visibleCount = 0;

            document.querySelectorAll('#clientListTable tbody tr[data-search]').forEach(function(row) {
                var show = !q || row.dataset.search.indexOf(q) !== -1;
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            var emptyRow = document.getElementById('clientListEmptyRow');
            if (emptyRow) {
                emptyRow.style.display = visibleCount === 0 ? '' : 'none';
                if (visibleCount === 0 && typeof lucide !== 'undefined') lucide.createIcons();
            }
        }

        function applyClientListSort() {
            var sortMode = document.getElementById('clientSortSelect').value;
            var tbody = document.querySelector('#clientListTable tbody');
            var rows  = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-search]'));

            rows.sort(function(a, b) {
                if (sortMode === 'alpha-asc')  return a.dataset.search.localeCompare(b.dataset.search);
                if (sortMode === 'alpha-desc') return b.dataset.search.localeCompare(a.dataset.search);
                return 0; // 'default' order is server-rendered
            });

            rows.forEach(function(row) { tbody.appendChild(row); });
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();

            var searchInput = document.getElementById('clientListSearch');
            if (searchInput) searchInput.addEventListener('keyup', applyClientListFilters);

            var sortSelect = document.getElementById('clientSortSelect');
            if (sortSelect) sortSelect.addEventListener('change', applyClientListSort);
        });
    </script>
</body>
</html>
