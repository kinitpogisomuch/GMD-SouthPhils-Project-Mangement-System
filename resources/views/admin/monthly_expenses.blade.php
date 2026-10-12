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
    @include('partials.admin.fund_overrun_modal')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            {{-- Page Header --}}
            @php $dt = \Carbon\Carbon::createFromFormat('Y-m', $month); @endphp
            <div class="page-header">
                <div>
                    <h1>Monthly Overhead Expenses</h1>
                    <p>Record monthly operating costs and allocate them across active projects.</p>
                </div>
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
            </div>

            {{-- Alerts --}}
            @if(session('success'))
            {{-- Expense added / deleted / allocation saved: the small dark toast below the header
                 (same as "Salary record saved."), shown for 3 seconds --}}
            <div class="toast" id="expenseToast" role="status">
                <i data-lucide="check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
            <script>
                window.addEventListener('load', function () {
                    var t = document.getElementById('expenseToast');
                    if (!t) return;
                    // the page-enter animation moves the content, which would carry a fixed toast with it —
                    // living directly under <body> keeps it pinned below the header
                    document.body.appendChild(t);
                    requestAnimationFrame(function () { requestAnimationFrame(function () { t.classList.add('show'); }); });
                    setTimeout(function () { t.classList.remove('show'); }, 3000);
                });
            </script>
            @endif
            @if(session('error'))
            <div class="alert-banner error"><i data-lucide="alert-circle"></i> {{ session('error') }}</div>
            @endif

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
                            <span style="font-size:14px;font-weight:800;color:var(--dark);">Expense Items</span>
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

                    {{-- Add expenses — opens a modal where several items can be ticked and given an amount at once --}}
                    <div style="padding:16px 20px;border-top:2px solid var(--border);background:var(--cream-soft);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <span style="font-size:12.5px;color:var(--muted);">Add rent, electricity and other overhead for {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}.</span>
                        <button type="button" class="save-btn" id="openAddExpensesBtn" style="justify-content:center;height:40px;">
                            <i data-lucide="plus" style="width:14px;height:14px;"></i> Add Expense Items
                        </button>
                    </div>
                </div>

                @php
                    $expenseCategories = collect(['Electricity', 'Water', 'Rent', 'Maintenance & Repair'])
                        ->merge($customCategories)->unique(fn ($c) => mb_strtolower($c))->values();
                    $addedThisMonth = $expenses->groupBy(fn ($e) => mb_strtolower($e->category))->map->sum('amount');
                @endphp
                <div class="modal-overlay" id="addExpensesModal" role="dialog" aria-modal="true" aria-labelledby="addExpensesTitle">
                    <div class="modal-card" style="max-width:560px;width:95%;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;">
                        <div class="modal-header" style="flex-shrink:0;">
                            <div>
                                <h2 id="addExpensesTitle">Add Expense Items</h2>
                                <p>Tick the expenses for {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}, then enter each amount.</p>
                            </div>
                            <button class="modal-close" type="button" data-ae-close aria-label="Close"><i data-lucide="x"></i></button>
                        </div>

                        <form method="POST" action="{{ route('admin.monthly-expenses.store') }}" id="addExpensesForm" style="display:flex;flex-direction:column;min-height:0;flex:1;">
                            @csrf
                            <input type="hidden" name="month_year" value="{{ $month }}">

                            <div class="ae-list" id="aeList" style="overflow-y:auto;flex:1;">
                                @foreach($expenseCategories as $i => $cat)
                                @php $already = $addedThisMonth[mb_strtolower($cat)] ?? null; @endphp
                                <div class="ae-row" data-cat="{{ $cat }}">
                                    <label class="ae-pick">
                                        <input type="checkbox" class="ae-check">
                                        <span class="ae-name">{{ $cat }}</span>
                                        @if($already)
                                        <span class="ae-already">Already added · ₱{{ number_format($already, 2) }}</span>
                                        @endif
                                    </label>
                                    <div class="ae-amount">
                                        <span>₱</span>
                                        <input type="text" inputmode="decimal" class="ae-amount-input" placeholder="0.00" disabled aria-label="Amount for {{ $cat }}">
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <div class="ae-other">
                                <input type="text" id="aeOtherName" maxlength="100" placeholder="Other category (e.g. Internet)">
                                <button type="button" class="cancel-btn" id="aeOtherAdd"><i data-lucide="plus" style="width:14px;height:14px;"></i> Add</button>
                            </div>

                            <div class="ae-error" id="aeError" hidden></div>

                            <div class="ae-foot">
                                <div class="ae-total"><span id="aeCount">0 selected</span><strong id="aeTotal">₱0.00</strong></div>
                                <div style="display:flex;gap:10px;">
                                    <button type="button" class="cancel-btn" data-ae-close>Cancel</button>
                                    <button type="submit" class="save-btn" id="aeSave" disabled><i data-lucide="check"></i> <span>Save</span></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <style>
                    .ae-list { border: 1px solid var(--border); border-radius: 14px; }
                    .ae-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 14px; border-bottom: 1px solid var(--border); transition: background .15s; }
                    .ae-row:last-child { border-bottom: none; }
                    .ae-row.is-on { background: #eff6ff; }
                    .ae-pick { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; cursor: pointer; min-width: 0; flex: 1; }
                    .ae-pick input { width: 17px; height: 17px; accent-color: var(--dark); flex-shrink: 0; cursor: pointer; }
                    .ae-name { font-size: 13.5px; font-weight: 700; color: var(--dark); }
                    .ae-already { font-size: 10.5px; font-weight: 800; color: #8A6100; background: #FFF3D6; border-radius: 999px; padding: 2px 8px; }
                    .ae-amount { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
                    .ae-amount span { font-size: 13px; font-weight: 800; color: var(--muted); }
                    .ae-amount input { width: 130px; padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-weight: 700; text-align: right; background: var(--white); color: var(--dark); }
                    .ae-amount input:disabled { background: var(--cream-soft); color: var(--muted); cursor: not-allowed; }
                    .ae-amount input.is-bad { border-color: var(--danger); outline: 2px solid rgba(220,38,38,.15); }
                    .ae-other { display: flex; gap: 8px; margin-top: 12px; }
                    .ae-other input { flex: 1; min-width: 0; padding: 9px 12px; border: 1px solid var(--border); border-radius: 10px; font-size: 13px; }
                    .ae-error { margin-top: 10px; padding: 9px 12px; border-radius: 10px; background: #fee2e2; color: #b91c1c; font-size: 12.5px; font-weight: 600; }
                    .ae-error[hidden] { display: none; }
                    .ae-foot { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border); flex-shrink: 0; }
                    .ae-total span { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
                    .ae-total strong { font-size: 18px; font-weight: 900; color: var(--dark); font-variant-numeric: tabular-nums; }
                    #aeSave:disabled { opacity: .45; cursor: not-allowed; }
                    @media (max-width: 520px) {
                        .ae-row { flex-wrap: wrap; }
                        .ae-amount { width: 100%; }
                        .ae-amount input { flex: 1; width: auto; }
                    }
                </style>

                {{-- RIGHT: Project Allocation --}}
                <div class="table-card" style="overflow:visible;display:flex;flex-direction:column;">
                    <div class="table-toolbar">
                        <div style="display:flex;align-items:center;gap:8px;">
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

                            {{-- Money each selected project has — overhead is paid from what its client has paid so far --}}
                            <div id="meFundsPanel" class="sf-panel" hidden></div>
                            <style>
                                .sf-panel { margin-bottom: 16px; padding: 12px 14px; border-radius: 14px; background: linear-gradient(180deg, #333333 0%, #2a2a2a 100%); display: flex; flex-direction: column; gap: 12px; overflow-y: auto; overscroll-behavior: contain; }
                                .sf-panel[hidden] { display: none; }
                                .sf-panel.is-scrolling { scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.3) transparent; }
                                .sf-panel.is-scrolling::-webkit-scrollbar { width: 6px; }
                                .sf-panel.is-scrolling::-webkit-scrollbar-thumb { background: rgba(255,255,255,.3); border-radius: 999px; }
                                .sf-panel.is-scrolling::-webkit-scrollbar-track { background: transparent; }
                                .sf-proj + .sf-proj { padding-top: 12px; border-top: 1px solid rgba(255,255,255,.1); }
                                .sf-name { display: flex; justify-content: space-between; gap: 10px; font-size: 12px; font-weight: 800; color: rgba(255,255,255,.85); margin-bottom: 8px; }
                                .sf-name span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
                                .sf-name em { font-style: normal; flex-shrink: 0; color: #93c5fd; }
                                .sf-figs { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
                                .sf-fig span { display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: rgba(255,255,255,.45); }
                                .sf-fig strong { display: block; margin-top: 2px; font-size: 13px; font-weight: 900; color: #fff; font-variant-numeric: tabular-nums; }
                                .sf-fig small { display: block; font-size: 10px; color: rgba(255,255,255,.4); }
                                .sf-available strong { color: #4ade80; }
                                .sf-warn { margin-top: 10px; padding: 8px 10px; border-radius: 10px; font-size: 12px; font-weight: 700; background: rgba(248,113,113,.14); color: #fca5a5; }
                                @media (max-width: 520px) { .sf-figs { grid-template-columns: 1fr 1fr; } }
                            </style>

                            @if($isAllocLocked)
                            <div id="allocLockNotice" style="display:flex;align-items:center;gap:8px;background:#fef9c3;border:1px solid #fde68a;border-radius:10px;padding:10px 12px;margin-bottom:12px;font-size:12px;color:#854d0e;">
                                <i data-lucide="lock" style="width:14px;height:14px;flex-shrink:0;"></i>
                                This month's allocation is saved. Click <strong>&nbsp;Edit Allocation&nbsp;</strong> to make changes.
                            </div>
                            @endif

                            <div id="allocError" role="alert" style="display:none;align-items:flex-start;gap:8px;background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;padding:10px 12px;margin-bottom:12px;font-size:12.5px;line-height:1.5;color:#b91c1c;">
                                <i data-lucide="alert-circle" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;"></i>
                                <span>There are no expense items for {{ $dt->format('F Y') }} yet. Log at least one expense item first — there is nothing to allocate until then.</span>
                            </div>

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

    @if(session('fund_overrun') && session('fund_retry'))
    // The overhead split is more than some projects have received — offer the revolving fund, and if
    // chosen, send the same project selection again with use_fund (nothing was saved the first time).
    document.addEventListener('DOMContentLoaded', function () {
        var retry = @json(session('fund_retry'));
        FundOverrun.open(@json(session('fund_overrun')), {
            amountLabel: 'Overhead share',
            onUse: function () {
                var f = document.createElement('form');
                f.method = 'POST';
                f.action = @json(route('admin.monthly-expenses.allocate'));
                var add = function (name, value) {
                    var i = document.createElement('input');
                    i.type = 'hidden'; i.name = name; i.value = value;
                    f.appendChild(i);
                };
                add('_token', @json(csrf_token()));
                add('month_year', retry.month_year);
                add('use_fund', '1');
                retry.project_ids.forEach(function (id) { add('project_ids[]', id); });
                document.body.appendChild(f);
                f.submit();
            }
        });
    });
    @endif

    function updateAllocationPreview() {
        var checked = document.querySelectorAll('input[name="project_ids[]"]:checked').length;
        var per = checked > 0 && TOTAL_OVERHEAD > 0 ? Math.floor(TOTAL_OVERHEAD * 100 / checked) / 100 : 0;
        document.getElementById('previewCount').textContent = checked;
        document.getElementById('previewPer').textContent   = '₱' + per.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById('previewTotal').textContent = '₱' + TOTAL_OVERHEAD.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
        renderOverheadFunds();
    }

    // ---- Money each selected project has (same figures and rule as ProjectFunds on the server) ----
    var ME_PROJECT_FUNDS  = @json($projectFunds);
    var ME_CURRENT_SHARES = @json($currentShares);
    var ME_PROJECT_NAMES  = @json($projects->pluck('name', 'id'));

    function mePeso(n) { return '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    function renderOverheadFunds() {
        var panel = document.getElementById('meFundsPanel');
        if (!panel) return;
        var ids = Array.prototype.map.call(document.querySelectorAll('input[name="project_ids[]"]:checked'), function (cb) { return parseInt(cb.value, 10); });
        var n = ids.length;
        // the same split the server saves: equal shares, leftover centavos on the last project
        var per = n > 0 ? Math.floor(TOTAL_OVERHEAD * 100 / n) / 100 : 0;
        var rem = n > 0 ? Math.round((TOTAL_OVERHEAD - per * n) * 100) / 100 : 0;

        panel.innerHTML = ids.map(function (id, i) {
            var f = ME_PROJECT_FUNDS[id];
            if (!f) return '';
            var share    = per + (i === n - 1 ? rem : 0);
            var increase = Math.round((share - (parseFloat(ME_CURRENT_SHARES[id]) || 0)) * 100) / 100;
            // only the new overhead is ever covered — an older shortfall isn't added on top
            var over = increase > 0 ? Math.round(Math.max(0, Math.min(increase, increase - f.available)) * 100) / 100 : 0;
            return '<div class="sf-proj">'
                + '<div class="sf-name"><span>' + String(ME_PROJECT_NAMES[id] || 'Project').replace(/</g, '&lt;') + '</span><em>Share ' + mePeso(share) + '</em></div>'
                + '<div class="sf-figs">'
                +   '<div class="sf-fig"><span>Total Received</span><strong>' + mePeso(f.received) + '</strong></div>'
                +   '<div class="sf-fig"><span>Already Spent</span><strong>' + mePeso(f.spent) + '</strong><small>Materials, labor &amp; overhead</small></div>'
                +   '<div class="sf-fig sf-available"><span>Available</span><strong>' + mePeso(Math.max(0, f.available)) + '</strong>'
                +     (f.fund_owed > 0 ? '<small>incl. ' + mePeso(f.fund_owed) + ' from the revolving fund</small>' : '') + '</div>'
                + '</div>'
                + (over > 0 ? '<div class="sf-warn">This overhead share is ' + mePeso(over) + ' more than the money available. You can cover it from the revolving fund when you save.</div>' : '')
                + '</div>';
        }).join('');
        panel.hidden = panel.innerHTML === '';

        // Two or more projects: keep the panel one project tall and scroll the rest, with a sliver of
        // the next project showing so it's clear there is more below
        var cards = panel.querySelectorAll('.sf-proj');
        panel.scrollTop = 0;
        panel.classList.toggle('is-scrolling', cards.length > 1);
        panel.style.maxHeight = cards.length > 1 && cards[0].offsetHeight ? (cards[0].offsetHeight + 24 + 28) + 'px' : '';
    }
    document.addEventListener('DOMContentLoaded', renderOverheadFunds);

    // Whether this month has at least one logged expense item (the allocation splits their total)
    var HAS_EXPENSES = {{ $expenses->count() > 0 ? 'true' : 'false' }};

    function allocActionClick() {
        var btn = document.getElementById('allocActionBtn');
        if (btn.dataset.locked === '1') {
            enableAllocationEdit();
            return;
        }

        var error = document.getElementById('allocError');
        if (!HAS_EXPENSES) {
            // nothing to split — refuse to save and say why
            error.style.display = 'flex';
            error.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        error.style.display = 'none';
        document.getElementById('allocForm').submit();
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

    // ---- Add Expense Items modal: tick several expenses, give each an amount, save them together ----
    (function () {
        var modal = document.getElementById('addExpensesModal');
        var form  = document.getElementById('addExpensesForm');
        var list  = document.getElementById('aeList');
        var save  = document.getElementById('aeSave');
        var errEl = document.getElementById('aeError');
        if (!modal) return;

        function amountOf(input) { return parseFloat(String(input.value).replace(/,/g, '')) || 0; }
        function peso(n) { return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

        function refresh() {
            var count = 0, total = 0;
            list.querySelectorAll('.ae-row').forEach(function (row) {
                var on = row.querySelector('.ae-check').checked;
                row.classList.toggle('is-on', on);
                if (on) { count++; total += amountOf(row.querySelector('.ae-amount-input')); }
            });
            document.getElementById('aeCount').textContent = count + ' selected';
            document.getElementById('aeTotal').textContent = peso(total);
            save.disabled = count === 0;
            save.querySelector('span').textContent = count > 1 ? 'Save ' + count + ' Expenses' : 'Save';
        }

        // Live thousand separators on the amount boxes
        function formatAmount(el) {
            var raw = el.value.replace(/[^0-9.]/g, '');
            var parts = raw.split('.');
            var whole = parts[0].replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            el.value = whole + (parts.length > 1 ? '.' + parts.slice(1).join('').slice(0, 2) : '');
        }

        list.addEventListener('change', function (e) {
            if (!e.target.classList.contains('ae-check')) return;
            var input = e.target.closest('.ae-row').querySelector('.ae-amount-input');
            input.disabled = !e.target.checked;
            input.classList.remove('is-bad');
            if (e.target.checked) input.focus(); else input.value = '';
            errEl.hidden = true;
            refresh();
        });
        list.addEventListener('input', function (e) {
            if (!e.target.classList.contains('ae-amount-input')) return;
            formatAmount(e.target);
            e.target.classList.remove('is-bad');
            refresh();
        });

        // A category that isn't on the list: added as a new, already-ticked row
        function addOther() {
            var nameEl = document.getElementById('aeOtherName');
            var name = nameEl.value.trim();
            if (!name) { nameEl.focus(); return; }
            var existing = Array.prototype.find.call(list.querySelectorAll('.ae-row'), function (r) {
                return r.dataset.cat.toLowerCase() === name.toLowerCase();
            });
            var row = existing;
            if (!row) {
                row = document.createElement('div');
                row.className = 'ae-row';
                row.dataset.cat = name;
                row.innerHTML = '<label class="ae-pick"><input type="checkbox" class="ae-check"><span class="ae-name">' + esc(name) + '</span></label>'
                    + '<div class="ae-amount"><span>₱</span><input type="text" inputmode="decimal" class="ae-amount-input" placeholder="0.00" disabled aria-label="Amount for ' + esc(name) + '"></div>';
                list.appendChild(row);
            }
            var check = row.querySelector('.ae-check');
            if (!check.checked) { check.checked = true; check.dispatchEvent(new Event('change', { bubbles: true })); }
            row.scrollIntoView({ block: 'nearest' });
            row.querySelector('.ae-amount-input').focus();
            nameEl.value = '';
        }
        document.getElementById('aeOtherAdd').addEventListener('click', addOther);
        document.getElementById('aeOtherName').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); addOther(); }
        });

        // Every ticked expense needs an amount; the ticked rows are sent as items[]
        form.addEventListener('submit', function (e) {
            form.querySelectorAll('input[name^="items["]').forEach(function (i) { i.remove(); });
            var missing = [], n = 0;
            list.querySelectorAll('.ae-row').forEach(function (row) {
                if (!row.querySelector('.ae-check').checked) return;
                var input = row.querySelector('.ae-amount-input');
                var amt = amountOf(input);
                if (amt <= 0) { missing.push(row.dataset.cat); input.classList.add('is-bad'); return; }
                [['category', row.dataset.cat], ['amount', amt.toFixed(2)]].forEach(function (pair) {
                    var h = document.createElement('input');
                    h.type = 'hidden';
                    h.name = 'items[' + n + '][' + pair[0] + ']';
                    h.value = pair[1];
                    form.appendChild(h);
                });
                n++;
            });
            if (missing.length || n === 0) {
                e.preventDefault();
                form.querySelectorAll('input[name^="items["]').forEach(function (i) { i.remove(); });
                errEl.textContent = n === 0 && !missing.length ? 'Tick at least one expense.' : 'Enter an amount for: ' + missing.join(', ') + '.';
                errEl.hidden = false;
                var bad = list.querySelector('.is-bad');
                if (bad) bad.focus();
            }
        });

        function open() {
            list.querySelectorAll('.ae-check').forEach(function (c) { c.checked = false; });
            list.querySelectorAll('.ae-amount-input').forEach(function (i) { i.value = ''; i.disabled = true; i.classList.remove('is-bad'); });
            errEl.hidden = true;
            refresh();
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
        function close() {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
        document.getElementById('openAddExpensesBtn').addEventListener('click', open);
        modal.querySelectorAll('[data-ae-close]').forEach(function (b) { b.addEventListener('click', close); });
        modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal.classList.contains('show')) close(); });

        @if($errors->has('items') || $errors->has('items.*'))
        // A rejected save comes back here — reopen the modal with the reason
        open();
        errEl.textContent = @json(collect($errors->getMessages())->filter(fn ($m, $k) => str_starts_with($k, 'items'))->flatten()->unique()->implode(' '));
        errEl.hidden = false;
        @endif
    })();
    </script>
</body>
</html>
