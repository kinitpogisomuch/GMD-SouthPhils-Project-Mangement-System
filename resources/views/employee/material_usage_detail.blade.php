<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project->name }} — Material Usage | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
    <style>
        /* ── Materials vs Usage: scroll inside the card, header stays visible ── */
        .mvu-scroll { max-height: 440px; overflow-y: auto; }
        .mvu-scroll thead th { position: sticky; top: 0; z-index: 1; background: var(--cream-soft); }

        /* ── Log Material Usage modal ── */
        .mu-toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; flex-shrink: 0; }
        .mu-toolbar .search-box { max-width: none; flex: 1; height: 44px; }
        .mu-count { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; color: var(--muted);
                    background: var(--accent-soft); border-radius: 999px; padding: 6px 12px; white-space: nowrap; transition: .2s ease; }
        .mu-count svg { width: 13px; height: 13px; }
        .mu-count.has-items { background: var(--dark); color: var(--white); }

        .mu-list { overflow-y: auto; flex: 1; min-height: 140px; border: 1px solid var(--border); border-radius: 16px; background: var(--white); }
        .mu-row { display: flex; align-items: center; gap: 14px; padding: 12px 16px; border-bottom: 1px solid var(--border);
                  border-left: 3px solid transparent; transition: background .15s ease, border-color .15s ease; }
        .mu-row:last-of-type { border-bottom: none; }
        .mu-row:hover { background: var(--cream-soft); }
        .mu-row.is-filled { background: #f3f8f4; border-left-color: var(--success); }
        .mu-info { flex: 1; min-width: 0; }
        .mu-name { font-size: 13.5px; font-weight: 800; color: var(--dark); line-height: 1.3; }
        .mu-remaining { margin-top: 3px; font-size: 11.5px; font-weight: 600; color: var(--muted); }
        .mu-remaining strong { color: var(--dark); }
        .mu-over { display: none; margin-top: 3px; font-size: 11.5px; font-weight: 800; color: var(--danger); }
        .mu-row.is-over { background: #FEF3F2; border-left-color: var(--danger); }
        .mu-row.is-over .mu-qty-wrap { border-color: var(--danger); }
        .mu-row.is-over .mu-over { display: block; }
        .mu-row.is-over .mu-remaining { display: none; }
        .mu-row.is-disabled { opacity: .55; }
        .mu-row.is-disabled:hover { background: transparent; }
        .mu-row.is-disabled .mu-qty-wrap { background: var(--cream-soft); cursor: not-allowed; }
        .mu-qty:disabled { cursor: not-allowed; }

        .mu-qty-wrap { display: flex; align-items: center; flex-shrink: 0; border: 1.5px solid var(--border); border-radius: 12px;
                       background: var(--white); overflow: hidden; transition: border-color .15s ease, box-shadow .15s ease; }
        .mu-qty-wrap:focus-within { border-color: var(--dark); box-shadow: 0 0 0 3px rgba(0,0,0,.08); }
        .mu-row.is-filled .mu-qty-wrap { border-color: var(--success); }
        .mu-qty { width: 84px; height: 40px; border: none !important; outline: none; background: transparent; text-align: right;
                  font-size: 14px; font-weight: 800; color: var(--dark); padding: 0 10px; box-shadow: none !important; -moz-appearance: textfield; }
        .mu-qty::-webkit-outer-spin-button, .mu-qty::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .mu-qty::placeholder { color: var(--muted-light); font-weight: 600; }
        .mu-unit { min-width: 52px; height: 40px; display: flex; align-items: center; justify-content: center; padding: 0 10px;
                   background: var(--cream-soft); border-left: 1px solid var(--border); font-size: 12px; font-weight: 700; color: var(--muted); }

        .mu-empty { display: none; padding: 28px; text-align: center; color: var(--muted); font-size: 13px; }
        .mu-error { display: none; margin-top: 12px; padding: 10px 14px; border-radius: 10px; background: #FEE4E2; color: var(--danger);
                    font-size: 13px; font-weight: 700; }

        @media (max-width: 560px) {
            .mu-row { flex-wrap: wrap; gap: 10px; }
            .mu-qty-wrap { width: 100%; }
            .mu-qty { flex: 1; width: auto; }
        }
    </style>
</head>
<body class="page-enter">

    @include('partials.employee.header')

    <main class="admin-content">

            {{-- Breadcrumb --}}
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('employee.project_materials') }}#usage" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    Project Materials
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="color:var(--dark);font-weight:700;">{{ $project->name }}</span>
            </div>

            <div class="pv-page-header">
                <div>
                    <h1>{{ $project->name }}</h1>
                    <p>Log materials consumed during fabrication and track usage against the planned BOM.</p>
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

            @if($errors->any())
            <div class="alert-banner error">
                <i data-lucide="alert-circle"></i>
                <div>@foreach($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
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

            @php
                // Materials that have been purchased come first — those are the ones that can be logged
                $materialComparison = $materialComparison->sortBy(fn ($r) => $r['purchasedQty'] > 0 ? 0 : 1)->values();
            @endphp

            {{-- Materials vs Usage --}}
            <div class="table-card" style="margin-bottom:24px;">
                <div class="table-toolbar" style="padding-bottom:0;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <span style="font-weight:700;font-size:15px;">Materials vs Usage</span>
                    @if($materialComparison->isNotEmpty())
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;flex:1;justify-content:flex-end;">
                        <div class="search-box" style="height:42px;max-width:300px;">
                            <i data-lucide="search"></i>
                            <input type="search" id="mvuSearch" placeholder="Search materials..." autocomplete="off">
                        </div>
                        <button type="button" class="save-btn" onclick="openLogUsageModal()">
                            <i data-lucide="plus"></i> Log Material Usage
                        </button>
                    </div>
                    @endif
                </div>
                <div class="table-wrapper mvu-scroll">
                    <table class="data-table" id="mvuTable">
                        <thead>
                            <tr>
                                <th>Material Name</th>
                                <th>Purchased Qty</th>
                                <th>Used Qty</th>
                                <th>Remaining</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($materialComparison as $row)
                            @php
                                $statusLabels = [
                                    'not_purchased' => 'Not Purchased',
                                    'pending'       => 'Not Started',
                                    'ongoing'       => 'In Use',
                                    'completed'     => 'Fully Used',
                                ];
                            @endphp
                            <tr class="mvu-row" data-name="{{ strtolower($row['material']->material_name) }}">
                                <td><strong>{{ $row['material']->material_name }}</strong></td>
                                <td>{{ number_format($row['purchasedQty'], 0) }}</td>
                                <td>{{ number_format($row['usedQty'], 0) }}</td>
                                <td>{{ number_format($row['stockRemaining'], 0) }}</td>
                                <td>
                                    @if($row['stockStatusKey'] === 'not_purchased')
                                    <span class="status-badge" style="background:var(--accent-soft);color:var(--muted);">{{ $statusLabels['not_purchased'] }}</span>
                                    @else
                                    <span class="status-badge {{ $row['stockStatusKey'] }}">
                                        {{ $statusLabels[$row['stockStatusKey']] }}
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="text-align:center;padding:32px;color:var(--muted);">
                                    No planned materials found for this project.
                                </td>
                            </tr>
                            @endforelse
                            <tr id="mvuNoMatch" style="display:none;">
                                <td colspan="5" style="text-align:center;padding:32px;color:var(--muted);">No materials match your search.</td>
                            </tr>
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
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="text-align:center;padding:32px;color:var(--muted);">
                                    No usage entries yet. Click <strong>Log Material Usage</strong> above to log what was used.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

    </main>

    {{-- ===================== LOG USAGE MODAL — several materials in one save ===================== --}}
    <div class="modal-overlay" id="logUsageModal">
        <div class="modal-card" style="max-width:640px;max-height:90vh;display:flex;flex-direction:column;">
            <div class="modal-header" style="flex-shrink:0;">
                <div>
                    <h2>Log Material Usage</h2>
                    <p>Enter how much of each material was used on <strong>{{ $project->name }}</strong>. Leave the rest blank.</p>
                </div>
                <button class="modal-close" type="button" onclick="closeModal('logUsageModal')">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('employee.material_usage.store', $project->id) }}" id="logUsageForm" style="display:flex;flex-direction:column;min-height:0;flex:1;">
                @csrf
                <div class="mu-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="search" id="usageSearch" placeholder="Search materials..." autocomplete="off">
                    </div>
                    <span class="mu-count" id="usageCount"><i data-lucide="list-checks"></i><span>0 to log</span></span>
                </div>

                <div class="mu-list" id="usageList">
                    @foreach($materialComparison as $row)
                    @php
                        $fmtQty  = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.');
                        $inStock = (float) $row['stockRemaining'];
                        $unit    = $row['material']->unit;
                    @endphp
                    <div class="mu-row{{ $inStock <= 0 ? ' is-disabled' : '' }}" data-name="{{ strtolower($row['material']->material_name) }}">
                        <div class="mu-info">
                            <div class="mu-name">{{ $row['material']->material_name }}</div>
                            <div class="mu-remaining">
                                @if($row['purchasedQty'] <= 0)
                                    Not purchased yet
                                @elseif($inStock <= 0)
                                    All {{ $fmtQty($row['purchasedQty']) }} {{ $unit }} purchased already used
                                @else
                                    <strong>{{ $fmtQty($inStock) }} {{ $unit }}</strong> in stock &middot; {{ $fmtQty($row['purchasedQty']) }} purchased
                                @endif
                            </div>
                            <div class="mu-over">Only {{ $fmtQty($inStock) }} {{ $unit }} in stock</div>
                        </div>
                        <label class="mu-qty-wrap" title="{{ $inStock > 0 ? 'Quantity used' : 'Nothing in stock to log' }}">
                            <input type="number" name="quantity_used[{{ $row['material']->id }}]" class="mu-qty usage-qty"
                                   data-material-id="{{ $row['material']->id }}" data-max="{{ $inStock }}"
                                   min="0" max="{{ $inStock }}" step="0.01" placeholder="0" onwheel="this.blur()" inputmode="decimal"
                                   value="{{ old('quantity_used.' . $row['material']->id) }}"
                                   {{ $inStock <= 0 ? 'disabled' : '' }}>
                            <span class="mu-unit">{{ $unit ?: 'qty' }}</span>
                        </label>
                    </div>
                    @endforeach
                    <div class="mu-empty" id="usageNoMatch">No materials match your search.</div>
                </div>

                <div class="form-grid" style="margin-top:14px;flex-shrink:0;">
                    <div class="form-group">
                        <label>Date Used <span style="font-weight:400;color:var(--muted);">(set an earlier date for past usage)</span></label>
                        <input type="date" name="used_date" value="{{ old('used_date', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Notes <span style="font-weight:400;color:var(--muted);">(optional, applies to all)</span></label>
                        <input type="text" name="notes" maxlength="1000" value="{{ old('notes') }}" placeholder="e.g. Shell welding, day 2">
                    </div>
                </div>
                <div class="mu-error" id="usageFormError"></div>

                <div class="modal-actions" style="flex-shrink:0;">
                    <button type="button" class="cancel-btn" onclick="closeModal('logUsageModal')">Cancel</button>
                    <button type="submit" class="save-btn" id="usageSaveBtn">
                        <i data-lucide="check-circle" style="width:15px;height:15px;"></i>
                        Save All
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

        // One modal for every material; the row's pencil button jumps straight to that material
        function openLogUsageModal(materialId) {
            document.getElementById('usageSearch').value = '';
            filterUsageRows();
            document.getElementById('usageFormError').style.display = 'none';
            openModal('logUsageModal');
            var target = materialId
                ? document.querySelector('.usage-qty[data-material-id="' + materialId + '"]')
                : document.querySelector('.usage-qty:not(:disabled)');
            if (target) {
                target.scrollIntoView({ block: 'center' });
                setTimeout(function () { target.focus(); }, 50);
            }
        }

        function filterUsageRows() {
            var q = document.getElementById('usageSearch').value.toLowerCase().trim();
            var shown = 0;
            document.querySelectorAll('#usageList .mu-row').forEach(function (row) {
                var match = !q || row.dataset.name.indexOf(q) !== -1;
                row.style.display = match ? 'flex' : 'none';
                if (match) shown++;
            });
            document.getElementById('usageNoMatch').style.display = shown ? 'none' : 'block';
        }

        function updateUsageCount() {
            var n = 0;
            var over = 0;
            document.querySelectorAll('.usage-qty').forEach(function (input) {
                var qty    = parseFloat(input.value);
                var filled = qty > 0;
                var isOver = filled && qty > parseFloat(input.dataset.max) + 0.0001;
                if (filled) n++;
                if (isOver) over++;
                var row = input.closest('.mu-row');
                row.classList.toggle('is-filled', filled && !isOver);
                row.classList.toggle('is-over', isOver);
            });
            window.__usageOverCount = over;
            var chip = document.getElementById('usageCount');
            chip.classList.toggle('has-items', n > 0);
            chip.lastChild.textContent = n + (n === 1 ? ' material' : ' materials') + ' to log';
            var btn = document.getElementById('usageSaveBtn');
            btn.lastChild.textContent = n > 1 ? ' Save All (' + n + ')' : ' Save All';
            return n;
        }

        document.addEventListener('DOMContentLoaded', function() {
            var mvuSearch = document.getElementById('mvuSearch');
            if (mvuSearch) {
                mvuSearch.addEventListener('input', function () {
                    var q = this.value.toLowerCase().trim();
                    var shown = 0;
                    document.querySelectorAll('#mvuTable .mvu-row').forEach(function (row) {
                        var match = !q || row.dataset.name.indexOf(q) !== -1;
                        row.style.display = match ? '' : 'none';
                        if (match) shown++;
                    });
                    document.getElementById('mvuNoMatch').style.display = shown ? 'none' : '';
                });
            }

            var usageForm = document.getElementById('logUsageForm');
            if (usageForm) {
                document.getElementById('usageSearch').addEventListener('input', filterUsageRows);
                usageForm.addEventListener('input', updateUsageCount);
                usageForm.addEventListener('submit', function (e) {
                    var err = document.getElementById('usageFormError');
                    if (updateUsageCount() === 0) {
                        e.preventDefault();
                        err.textContent = 'Enter the quantity used for at least one material.';
                        err.style.display = 'block';
                        return;
                    }
                    if (window.__usageOverCount > 0) {
                        e.preventDefault();
                        err.textContent = 'Some quantities are more than what is in stock — fix the rows marked in red.';
                        err.style.display = 'block';
                        var firstOver = document.querySelector('.mu-row.is-over .usage-qty');
                        if (firstOver) { firstOver.scrollIntoView({ block: 'center' }); firstOver.focus(); }
                        return;
                    }
                    // blank rows aren't sent — only the materials actually used
                    document.querySelectorAll('.usage-qty').forEach(function (input) {
                        if (!(parseFloat(input.value) > 0)) input.disabled = true;
                    });
                    document.getElementById('usageSaveBtn').disabled = true;
                });
                updateUsageCount();
                // came back with a validation error → reopen the list with what was typed
                @if($errors->any()) openModal('logUsageModal'); @endif
            }

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
