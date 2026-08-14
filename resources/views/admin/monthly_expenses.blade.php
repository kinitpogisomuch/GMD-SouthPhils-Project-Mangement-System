<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Overhead Expenses | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        .me-project-row:hover { border-color:#93c5fd !important; box-shadow:0 2px 8px rgba(37,99,235,.08); }
        #expensesTable tbody tr:hover { background:var(--cream-soft); }
        #expensesTable { min-width: unset; width: 100%; table-layout: fixed; }
        #expenseHistoryTable { min-width: unset; width: 100%; }
        .me-main-grid .table-wrapper { overflow: hidden; }
        #expensesTable tbody tr.me-filler-row:hover { background: transparent; }
        #expensesTable tbody tr.me-filler-row td { color: transparent; user-select: none; }
        #expensesTable tbody tr:last-child td { border-bottom: 1px solid var(--border); }
    </style>
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            {{-- Page Header --}}
            <div class="page-header">
                <div>
                    <h1>Monthly Overhead Expenses</h1>
                    <p>Record monthly operating costs and allocate them across active projects.</p>
                </div>
            </div>

            {{-- Alerts --}}
            @if(session('success'))
            <div class="alert-banner success"><i data-lucide="check-circle"></i> {{ session('success') }}</div>
            @endif
            @if(session('error'))
            <div class="alert-banner error"><i data-lucide="alert-circle"></i> {{ session('error') }}</div>
            @endif

            {{-- Month Selector --}}
            @php $dt = \Carbon\Carbon::createFromFormat('Y-m', $month); @endphp
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:10px;background:var(--white);border:1px solid var(--border);border-radius:14px;padding:10px 18px;box-shadow:0 2px 8px rgba(0,0,0,.03);">
                    <div style="width:30px;height:30px;border-radius:9px;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="calendar" style="width:15px;height:15px;color:#2563eb;"></i>
                    </div>
                    <form method="GET" action="{{ route('admin.monthly-expenses.index') }}" id="monthForm">
                        <input type="month" name="month" value="{{ $month }}"
                               onchange="this.form.submit()"
                               style="border:none;background:transparent;font-size:14px;font-weight:800;color:var(--dark);outline:none;cursor:pointer;">
                    </form>
                </div>
                <div style="font-size:22px;font-weight:900;color:var(--dark);">{{ $dt->format('F Y') }}</div>
            </div>

            {{-- Summary Cards --}}
            <div class="pf-summary-grid">
                <div class="pf-summary-card">
                    <div class="pf-summary-icon" style="background:#fef3c7;color:#d97706;">
                        <i data-lucide="receipt"></i>
                    </div>
                    <div class="pf-summary-body">
                        <div class="pf-summary-label">Total Overhead</div>
                        <div class="pf-summary-value">₱{{ number_format($total, 2) }}</div>
                        <div class="pf-summary-sub">{{ $expenses->count() }} expense item{{ $expenses->count() !== 1 ? 's' : '' }} this month</div>
                    </div>
                </div>

                <div class="pf-summary-card">
                    <div class="pf-summary-icon" style="background:#dbeafe;color:#2563eb;">
                        <i data-lucide="folder-kanban"></i>
                    </div>
                    <div class="pf-summary-body">
                        <div class="pf-summary-label">Projects Allocated</div>
                        <div class="pf-summary-value">{{ count($allocated) }}</div>
                        <div class="pf-summary-sub">Sharing this month's overhead</div>
                    </div>
                </div>

                <div class="pf-summary-card">
                    <div class="pf-summary-icon" style="background:{{ count($allocated) > 0 && $total > 0 ? '#d1fae5' : '#f3f4f6' }};color:{{ count($allocated) > 0 && $total > 0 ? '#16a34a' : '#9ca3af' }};">
                        <i data-lucide="split"></i>
                    </div>
                    <div class="pf-summary-body">
                        <div class="pf-summary-label">Per Project Share</div>
                        <div class="pf-summary-value">₱{{ number_format($perProject, 2) }}</div>
                        <div class="pf-summary-sub">Equal split across allocated projects</div>
                    </div>
                </div>
            </div>

            @php $isAllocLocked = count($allocated) > 0; @endphp

            {{-- Main grid: expenses + allocation --}}
            <div class="me-main-grid" style="display:grid;grid-template-columns:1fr 440px;gap:20px;margin-bottom:28px;align-items:stretch;">

                {{-- LEFT: Expense Line Items --}}
                <div class="table-card" style="display:flex;flex-direction:column;">
                    <div class="table-toolbar">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <i data-lucide="receipt" style="width:16px;height:16px;color:var(--muted);"></i>
                            <span style="font-size:14px;font-weight:800;color:var(--dark);">Expense Items</span>
                            <span style="font-size:12px;color:var(--muted);">{{ $dt->format('F Y') }}</span>
                        </div>
                    </div>

                    <div class="table-wrapper" style="flex:1;">
                        <table class="data-table" id="expensesTable">
                            <colgroup>
                                <col style="width:30%;">
                                <col style="width:40%;">
                                <col style="width:30%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th style="text-align:center;">Amount (₱)</th>
                                    <th style="text-align:center;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $catColors = [
                                        'Electricity'            => ['bg'=>'#fef3c7','color'=>'#92400e'],
                                        'Water'                  => ['bg'=>'#dbeafe','color'=>'#1e40af'],
                                        'Internet'                => ['bg'=>'#ede9fe','color'=>'#5b21b6'],
                                        'Rent'                   => ['bg'=>'#fce7f3','color'=>'#9d174d'],
                                        'Equipment Rental'       => ['bg'=>'#cffafe','color'=>'#0e7490'],
                                        'Fuel & Transportation'  => ['bg'=>'#ffe4e6','color'=>'#be123c'],
                                        'Office Supplies'        => ['bg'=>'#e0e7ff','color'=>'#3730a3'],
                                        'Maintenance & Repair'   => ['bg'=>'#d1fae5','color'=>'#065f46'],
                                        'Other'                  => ['bg'=>'#f3f4f6','color'=>'#374151'],
                                    ];
                                @endphp
                                @forelse($expenses as $exp)
                                @php $cc = $catColors[$exp->category] ?? ['bg'=>'#f3f4f6','color'=>'#374151']; @endphp
                                <tr>
                                    <td>
                                        <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;background:{{ $cc['bg'] }};color:{{ $cc['color'] }};">
                                            {{ $exp->category }}
                                        </span>
                                    </td>
                                    <td style="text-align:center;font-variant-numeric:tabular-nums;"><strong>₱{{ number_format($exp->amount, 2) }}</strong></td>
                                    <td class="action-cell" style="text-align:center;">
                                        <form method="POST" action="{{ route('admin.monthly-expenses.destroy', $exp->id) }}"
                                              onsubmit="return confirm('Delete this expense?');">
                                            @csrf @method('DELETE')
                                            <input type="hidden" name="month_year" value="{{ $month }}">
                                            <button type="submit" class="action-btn" style="color:#dc2626;" title="Delete">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" style="text-align:center;padding:90px 20px;color:var(--muted);border-bottom:none;">
                                        <i data-lucide="receipt" style="width:36px;height:36px;opacity:.3;display:block;margin:0 auto 12px;"></i>
                                        <div style="font-size:15px;font-weight:700;color:var(--dark);">No expenses recorded yet</div>
                                        <div style="font-size:13px;margin-top:4px;">No overhead items have been added for {{ $dt->format('F Y') }} — use the form below to add one.</div>
                                    </td>
                                </tr>
                                @endforelse
                                @php $fillerRows = max(0, 8 - $expenses->count()); @endphp
                                @if($expenses->isNotEmpty())
                                    @for($i = 0; $i < $fillerRows; $i++)
                                    <tr class="me-filler-row">
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                    @endfor
                                @endif
                            </tbody>
                        </table>
                    </div>

                    {{-- Add Expense Form --}}
                    <div style="padding:16px 20px;border-top:2px solid var(--border);background:var(--cream-soft);">
                        <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:12px;">
                            <i data-lucide="plus" style="width:11px;height:11px;"></i> Add Expense Item
                        </div>
                        <form method="POST" action="{{ route('admin.monthly-expenses.store') }}">
                            @csrf
                            <input type="hidden" name="month_year" value="{{ $month }}">
                            <div style="display:grid;grid-template-columns:1fr 1fr 110px;gap:10px;align-items:end;">
                                <div>
                                    <label style="font-size:11px;font-weight:700;color:var(--muted);display:block;margin-bottom:4px;">Category *</label>
                                    <select name="category" id="expenseCategorySelect" required
                                            onchange="toggleExpenseCustomCategory(this)"
                                            style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:8px;font-size:13px;background:var(--white);">
                                        <option value="Electricity">Electricity</option>
                                        <option value="Water">Water</option>
                                        <option value="Rent">Rent</option>
                                        <option value="Maintenance & Repair">Maintenance & Repair</option>
                                        <option value="__other__">Other</option>
                                    </select>
                                    <div id="expenseCategoryCustomWrap" style="display:none;position:relative;margin-top:8px;">
                                        <input type="text" id="expenseCategoryCustom" placeholder="Enter category name" maxlength="100"
                                               style="width:100%;padding:8px 32px 8px 10px;border:1px solid var(--border);border-radius:8px;font-size:13px;">
                                        <button type="button" onclick="resetExpenseCategory()" title="Back to list"
                                                style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);padding:2px;display:flex;">
                                            <i data-lucide="x" style="width:14px;height:14px;"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label style="font-size:11px;font-weight:700;color:var(--muted);display:block;margin-bottom:4px;">Amount (₱) *</label>
                                    <input type="number" name="amount" min="0.01" step="0.01" placeholder="0.00" required
                                           style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-weight:700;">
                                </div>
                                <div>
                                    <button type="submit" class="save-btn" style="width:100%;justify-content:center;height:38px;">
                                        <i data-lucide="plus" style="width:14px;height:14px;"></i> Add
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- RIGHT: Project Allocation --}}
                <div class="table-card" style="overflow:visible;display:flex;flex-direction:column;">
                    <div class="table-toolbar">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <i data-lucide="git-branch" style="width:16px;height:16px;color:var(--muted);"></i>
                            <span style="font-size:14px;font-weight:800;color:var(--dark);">Project Allocation</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.monthly-expenses.allocate') }}" id="allocForm" style="padding:16px;display:flex;flex-direction:column;flex:1;">
                        @csrf
                        <input type="hidden" name="month_year" value="{{ $month }}">

                        {{-- Explanation --}}
                        <div style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid #bfdbfe;border-radius:12px;padding:13px 15px;margin-bottom:18px;font-size:12px;color:#1e40af;line-height:1.65;display:flex;gap:10px;">
                            <i data-lucide="info" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;"></i>
                            <div>
                                <strong>Equal split:</strong> Total overhead ÷ selected projects.<br>
                                Each project's Net Profit = Revenue − Materials − Labor − <strong>Overhead Share</strong>.
                            </div>
                        </div>

                        {{-- Projects --}}
                        <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Select Projects</div>

                        @if($projects->isEmpty())
                        <div style="text-align:center;padding:32px 16px;color:var(--muted);">
                            <i data-lucide="folder-x" style="width:28px;height:28px;opacity:.3;display:block;margin:0 auto 10px;"></i>
                            <div style="font-size:13px;font-weight:600;">No active projects found.</div>
                        </div>
                        @else
                        <div id="allocProjectsList" style="display:flex;flex-direction:column;gap:7px;max-height:250px;overflow-y:auto;margin-bottom:16px;padding-right:2px;">
                            @foreach($projects as $proj)
                            @php
                                $isAlloc = in_array($proj->id, $allocated);
                                $totalQty = $proj->tankItems->isNotEmpty() ? $proj->tankItems->sum('quantity') : 1;
                            @endphp
                            <label class="me-project-row" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;cursor:{{ $isAllocLocked ? 'not-allowed' : 'pointer' }};border:1.5px solid {{ $isAlloc ? '#2563eb' : 'var(--border)' }};background:{{ $isAlloc ? '#eff6ff' : 'var(--white)' }};transition:all .15s;{{ $isAllocLocked ? 'opacity:.65;' : '' }}"
                                   @if(!$isAllocLocked) onclick="updateAllocationPreview()" @endif>
                                <input type="checkbox" name="project_ids[]" value="{{ $proj->id }}"
                                       {{ $isAlloc ? 'checked' : '' }}
                                       {{ $isAllocLocked ? 'disabled' : '' }}
                                       style="width:15px;height:15px;accent-color:#2563eb;flex-shrink:0;"
                                       onchange="updateAllocationPreview();updateCheckStyle(this)">
                                <div style="width:30px;height:30px;border-radius:9px;background:{{ $isAlloc ? '#2563eb' : 'var(--cream-soft)' }};color:{{ $isAlloc ? '#fff' : 'var(--muted)' }};display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0;transition:all .15s;">
                                    {{ strtoupper(substr($proj->client, 0, 2)) }}
                                </div>
                                <div style="min-width:0;flex:1;">
                                    <div style="font-size:12px;font-weight:700;color:var(--dark);white-space:normal;word-break:break-word;line-height:1.4;">{{ $proj->name }}</div>
                                    <div style="font-size:11px;color:var(--muted);margin-top:2px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                        <span>{{ $proj->client }}</span>
                                        <span style="color:var(--border);">&middot;</span>
                                        <span style="display:inline-flex;align-items:center;gap:3px;font-weight:700;color:#2563eb;">
                                            {{ $totalQty }}&times; {{ $proj->tank_type ?? 'Tank' }}
                                        </span>
                                    </div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                        @endif

                        {{-- Live Preview + Save button pinned to bottom of card --}}
                        <div style="margin-top:auto;">
                            <div style="background:var(--cream-soft);border:1px solid var(--border);border-radius:10px;padding:12px 14px;margin-bottom:16px;" id="allocPreview">
                                <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:8px;">Allocation Preview</div>
                                <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
                                    <span style="color:var(--muted);">Total overhead</span>
                                    <strong id="previewTotal">₱{{ number_format($total, 2) }}</strong>
                                </div>
                                <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
                                    <span style="color:var(--muted);">Selected projects</span>
                                    <strong id="previewCount">{{ count($allocated) }}</strong>
                                </div>
                                <div style="border-top:1px solid var(--border);margin:8px 0;"></div>
                                <div style="display:flex;justify-content:space-between;font-size:14px;">
                                    <span style="font-weight:700;color:var(--dark);">Per project</span>
                                    <strong style="color:#2563eb;font-size:16px;" id="previewPer">₱{{ number_format($perProject, 2) }}</strong>
                                </div>
                            </div>

                            @if($isAllocLocked)
                            <div id="allocLockNotice" style="display:flex;align-items:center;gap:8px;background:#fef9c3;border:1px solid #fde68a;border-radius:10px;padding:10px 12px;margin-bottom:12px;font-size:12px;color:#854d0e;">
                                <i data-lucide="lock" style="width:14px;height:14px;flex-shrink:0;"></i>
                                This month's allocation is saved. Click <strong>&nbsp;Edit Allocation&nbsp;</strong> to make changes.
                            </div>
                            @endif

                            <button type="button" class="save-btn" id="allocActionBtn" data-locked="{{ $isAllocLocked ? '1' : '0' }}"
                                    style="width:100%;justify-content:center;" onclick="allocActionClick()">
                                @if($isAllocLocked)
                                    <i data-lucide="pencil" style="width:14px;height:14px;"></i>
                                    Edit Allocation
                                @else
                                    <i data-lucide="save" style="width:14px;height:14px;"></i>
                                    Save Allocation
                                @endif
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- History Table --}}
            @if($history->isNotEmpty())
            <div class="table-card">
                <div class="table-toolbar">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <i data-lucide="history" style="width:16px;height:16px;color:var(--muted);"></i>
                        <span style="font-size:14px;font-weight:800;color:var(--dark);">Expense History</span>
                        <span style="font-size:12px;color:var(--muted);">Last 12 months</span>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table" id="expenseHistoryTable">
                        <colgroup>
                            <col style="width:22%;">
                            <col style="width:15%;">
                            <col style="width:16%;">
                            <col style="width:17%;">
                            <col style="width:15%;">
                            <col style="width:15%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th style="text-align:center;">Expense Items</th>
                                <th style="text-align:center;">Total Overhead</th>
                                <th style="text-align:center;">Allocated Projects</th>
                                <th style="text-align:center;">Per Project</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($history as $h)
                            @php
                                $hDt  = \Carbon\Carbon::createFromFormat('Y-m', $h->month_year);
                                $hAlloc = \Illuminate\Support\Facades\DB::table('monthly_expense_projects')
                                    ->where('month_year', $h->month_year)->count();
                                $hPer = $hAlloc > 0 ? round($h->total / $hAlloc, 2) : 0;
                            @endphp
                            <tr style="{{ $h->month_year === $month ? 'background:#eff6ff;' : '' }}">
                                <td>
                                    <strong>{{ $hDt->format('F Y') }}</strong>
                                    @if($h->month_year === $month)
                                        <span class="status-badge ongoing" style="margin-left:6px;font-size:10px;">Current</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">{{ $h->items }} items</td>
                                <td style="text-align:center;"><strong>₱{{ number_format($h->total, 2) }}</strong></td>
                                <td style="text-align:center;color:{{ $hAlloc > 0 ? '#2563eb' : 'var(--muted)' }};font-weight:700;">
                                    {{ $hAlloc > 0 ? $hAlloc.' project'.($hAlloc!==1?'s':'') : '—' }}
                                </td>
                                <td style="text-align:center;color:#16a34a;font-weight:700;">
                                    {{ $hPer > 0 ? '₱'.number_format($hPer, 2) : '—' }}
                                </td>
                                <td class="action-cell" style="text-align:center;">
                                    <a href="{{ route('admin.monthly-expenses.index', ['month' => $h->month_year]) }}"
                                       class="action-btn view" title="View">
                                        <i data-lucide="eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    var TOTAL_OVERHEAD = {{ $total }};

    function updateAllocationPreview() {
        var checked = document.querySelectorAll('input[name="project_ids[]"]:checked').length;
        var per = checked > 0 && TOTAL_OVERHEAD > 0 ? Math.floor(TOTAL_OVERHEAD * 100 / checked) / 100 : 0;
        document.getElementById('previewCount').textContent = checked;
        document.getElementById('previewPer').textContent   = '₱' + per.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById('previewTotal').textContent = '₱' + TOTAL_OVERHEAD.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
    }

    function allocActionClick() {
        var btn = document.getElementById('allocActionBtn');
        if (btn.dataset.locked === '1') {
            enableAllocationEdit();
        } else {
            document.getElementById('allocForm').submit();
        }
    }

    function enableAllocationEdit() {
        document.querySelectorAll('#allocProjectsList input[name="project_ids[]"]').forEach(function (cb) {
            cb.disabled = false;
            var label = cb.closest('label');
            if (label) {
                label.style.cursor = 'pointer';
                label.style.opacity = '1';
                label.onclick = function () { updateAllocationPreview(); };
            }
        });

        var lockNotice = document.getElementById('allocLockNotice');
        if (lockNotice) lockNotice.style.display = 'none';

        var btn = document.getElementById('allocActionBtn');
        btn.dataset.locked = '0';
        btn.innerHTML = '<i data-lucide="save" style="width:14px;height:14px;"></i> Save Allocation';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function updateCheckStyle(checkbox) {
        var label = checkbox.closest('label');
        if (!label) return;
        label.style.border      = checkbox.checked ? '1.5px solid #2563eb' : '1.5px solid var(--border)';
        label.style.background  = checkbox.checked ? '#eff6ff' : 'var(--white)';

        var avatar = label.querySelector('div');
        if (avatar) {
            avatar.style.background = checkbox.checked ? '#2563eb' : 'var(--cream-soft)';
            avatar.style.color      = checkbox.checked ? '#fff' : 'var(--muted)';
        }
    }

    function toggleExpenseCustomCategory(sel) {
        var wrap   = document.getElementById('expenseCategoryCustomWrap');
        var custom = document.getElementById('expenseCategoryCustom');
        if (sel.value === '__other__') {
            sel.style.display = 'none';
            sel.removeAttribute('name');
            wrap.style.display = '';
            custom.name = 'category';
            custom.required = true;
            custom.focus();
        }
    }

    function resetExpenseCategory() {
        var sel    = document.getElementById('expenseCategorySelect');
        var wrap   = document.getElementById('expenseCategoryCustomWrap');
        var custom = document.getElementById('expenseCategoryCustom');
        sel.style.display = '';
        sel.value = 'Electricity';
        sel.name = 'category';
        wrap.style.display = 'none';
        custom.value = '';
        custom.removeAttribute('name');
        custom.required = false;
    }
    </script>
</body>
</html>
