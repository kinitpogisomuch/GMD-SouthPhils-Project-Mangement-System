<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Overhead Expenses | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
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
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
                <div style="display:flex;align-items:center;gap:8px;background:var(--white);border:1px solid var(--border);border-radius:12px;padding:10px 16px;">
                    <i data-lucide="calendar" style="width:15px;height:15px;color:var(--muted);"></i>
                    <label style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Month</label>
                    <form method="GET" action="{{ route('admin.monthly-expenses.index') }}" id="monthForm">
                        <input type="month" name="month" value="{{ $month }}"
                               onchange="this.form.submit()"
                               style="border:none;background:transparent;font-size:14px;font-weight:800;color:var(--dark);outline:none;cursor:pointer;">
                    </form>
                </div>

                @php
                    $dt = \Carbon\Carbon::createFromFormat('Y-m', $month);
                @endphp
                <div style="font-size:22px;font-weight:900;color:var(--dark);">{{ $dt->format('F Y') }}</div>

                {{-- Summary chips --}}
                <div style="margin-left:auto;display:flex;gap:10px;">
                    <div style="background:var(--white);border:1px solid var(--border);border-radius:10px;padding:8px 16px;text-align:center;">
                        <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Total Overhead</div>
                        <div style="font-size:18px;font-weight:900;color:var(--dark);">₱{{ number_format($total, 2) }}</div>
                    </div>
                    <div style="background:var(--white);border:1px solid var(--border);border-radius:10px;padding:8px 16px;text-align:center;">
                        <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Projects</div>
                        <div style="font-size:18px;font-weight:900;color:#2563eb;">{{ count($allocated) }}</div>
                    </div>
                    <div style="background:{{ count($allocated) > 0 && $total > 0 ? '#d1fae5' : 'var(--cream-soft)' }};border:1px solid {{ count($allocated) > 0 && $total > 0 ? '#86efac' : 'var(--border)' }};border-radius:10px;padding:8px 16px;text-align:center;">
                        <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Per Project</div>
                        <div style="font-size:18px;font-weight:900;color:#16a34a;">₱{{ number_format($perProject, 2) }}</div>
                    </div>
                </div>
            </div>

            {{-- Main grid: expenses + allocation --}}
            <div style="display:grid;grid-template-columns:1fr 380px;gap:20px;margin-bottom:28px;align-items:start;">

                {{-- LEFT: Expense Line Items --}}
                <div class="table-card">
                    <div class="table-toolbar">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <i data-lucide="receipt" style="width:16px;height:16px;color:var(--muted);"></i>
                            <span style="font-size:14px;font-weight:800;color:var(--dark);">Expense Items</span>
                            <span style="font-size:12px;color:var(--muted);">{{ $dt->format('F Y') }}</span>
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table class="data-table" id="expensesTable">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th class="num-cell">Amount (₱)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenses as $exp)
                                <tr>
                                    <td>
                                        <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;background:var(--cream-soft);color:var(--dark);border:1px solid var(--border);">
                                            {{ $exp->category }}
                                        </span>
                                    </td>
                                    <td style="color:var(--muted);font-size:13px;">{{ $exp->description ?? '—' }}</td>
                                    <td class="num-cell"><strong>₱{{ number_format($exp->amount, 2) }}</strong></td>
                                    <td class="action-cell">
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
                                    <td colspan="4" style="text-align:center;padding:48px 20px;color:var(--muted);">
                                        <i data-lucide="receipt" style="width:32px;height:32px;opacity:.3;display:block;margin:0 auto 10px;"></i>
                                        <div style="font-size:14px;font-weight:700;">No expenses yet for {{ $dt->format('F Y') }}</div>
                                        <div style="font-size:13px;margin-top:4px;">Use the form below to add overhead items.</div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($expenses->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="table-total-label">Total Overhead — {{ $dt->format('F Y') }}</td>
                                    <td class="table-total-value">₱{{ number_format($total, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            @endif
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
                            <div style="display:grid;grid-template-columns:1fr 1.5fr 140px 100px;gap:10px;align-items:end;">
                                <div>
                                    <label style="font-size:11px;font-weight:700;color:var(--muted);display:block;margin-bottom:4px;">Category *</label>
                                    <select name="category" required style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:8px;font-size:13px;background:var(--white);">
                                        <option value="">Select…</option>
                                        @foreach(\App\Models\MonthlyExpense::categories() as $cat)
                                            <option value="{{ $cat }}">{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size:11px;font-weight:700;color:var(--muted);display:block;margin-bottom:4px;">Description</label>
                                    <input type="text" name="description" placeholder="e.g. June electricity bill"
                                           style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:8px;font-size:13px;">
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
                <div class="table-card" style="overflow:visible;">
                    <div class="table-toolbar">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <i data-lucide="git-branch" style="width:16px;height:16px;color:var(--muted);"></i>
                            <span style="font-size:14px;font-weight:800;color:var(--dark);">Project Allocation</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.monthly-expenses.allocate') }}" style="padding:16px;">
                        @csrf
                        <input type="hidden" name="month_year" value="{{ $month }}">

                        {{-- Explanation --}}
                        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:12px;color:#1e40af;line-height:1.6;">
                            <strong>Equal split:</strong> Total overhead ÷ selected projects.<br>
                            Each project's Net Profit = Revenue − Materials − Labor − <strong>Overhead Share</strong>.
                        </div>

                        {{-- Projects --}}
                        <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Select Projects</div>

                        @if($projects->isEmpty())
                        <div style="text-align:center;padding:24px;color:var(--muted);font-size:13px;">
                            No active projects found.
                        </div>
                        @else
                        <div style="display:flex;flex-direction:column;gap:6px;max-height:300px;overflow-y:auto;margin-bottom:16px;">
                            @foreach($projects as $proj)
                            @php $isAlloc = in_array($proj->id, $allocated); @endphp
                            <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;cursor:pointer;border:1.5px solid {{ $isAlloc ? '#2563eb' : 'var(--border)' }};background:{{ $isAlloc ? '#eff6ff' : 'var(--white)' }};transition:all .15s;"
                                   onclick="updateAllocationPreview()">
                                <input type="checkbox" name="project_ids[]" value="{{ $proj->id }}"
                                       {{ $isAlloc ? 'checked' : '' }}
                                       style="width:15px;height:15px;accent-color:#2563eb;flex-shrink:0;"
                                       onchange="updateAllocationPreview();updateCheckStyle(this)">
                                <div style="min-width:0;flex:1;">
                                    <div style="font-size:12px;font-weight:700;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $proj->name }}</div>
                                    <div style="font-size:11px;color:var(--muted);">{{ $proj->client }}</div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                        @endif

                        {{-- Live Preview --}}
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

                        <button type="submit" class="save-btn" style="width:100%;justify-content:center;">
                            <i data-lucide="save" style="width:14px;height:14px;"></i>
                            Save Allocation
                        </button>
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
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="num-cell">Expense Items</th>
                                <th class="num-cell">Total Overhead</th>
                                <th class="num-cell">Allocated Projects</th>
                                <th class="num-cell">Per Project</th>
                                <th>Actions</th>
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
                                <td class="num-cell">{{ $h->items }} items</td>
                                <td class="num-cell"><strong>₱{{ number_format($h->total, 2) }}</strong></td>
                                <td class="num-cell" style="color:{{ $hAlloc > 0 ? '#2563eb' : 'var(--muted)' }};font-weight:700;">
                                    {{ $hAlloc > 0 ? $hAlloc.' project'.($hAlloc!==1?'s':'') : '—' }}
                                </td>
                                <td class="num-cell" style="color:#16a34a;font-weight:700;">
                                    {{ $hPer > 0 ? '₱'.number_format($hPer, 2) : '—' }}
                                </td>
                                <td class="action-cell">
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

    function updateCheckStyle(checkbox) {
        var label = checkbox.closest('label');
        if (!label) return;
        label.style.border      = checkbox.checked ? '1.5px solid #2563eb' : '1.5px solid var(--border)';
        label.style.background  = checkbox.checked ? '#eff6ff' : 'var(--white)';
    }
    </script>
</body>
</html>
