<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revolving Fund | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @php
        // Peso amounts without the .00 on whole numbers — centavos are shown only when there are some
        $rfMoney = function ($n) { $s = number_format((float) $n, 2); return str_ends_with($s, '.00') ? substr($s, 0, -3) : $s; };
    @endphp
    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1>Revolving Fund</h1>
                    <p>General business wallet — shared across all active projects.</p>
                </div>
            </div>

            @if(session('success'))
            {{-- Saved release / replenishment / initial balance: the small dark toast below the header
                 (same as "Salary record saved."), shown for 3 seconds --}}
            <div class="toast" id="fundToast" role="status">
                <i data-lucide="check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
            <script>
                window.addEventListener('load', function () {
                    var t = document.getElementById('fundToast');
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

            {{-- Low balance warning --}}
            @if($isLowBalance && $initialBalance > 0)
            <div class="rf-warning-banner">
                <i data-lucide="alert-triangle" style="width:16px;height:16px;flex-shrink:0;"></i>
                <span>
                    <strong>Low balance warning:</strong>
                    Revolving fund is at ₱{{ $rfMoney($currentBalance) }} —
                    @if($projectOutstandings->isNotEmpty())
                        {{ $projectOutstandings->count() }} active {{ Str::plural('project', $projectOutstandings->count()) }}
                        ({{ $projectOutstandings->map(fn($d) => $d['project']?->name ?? '—')->implode(', ') }})
                        currently {{ $projectOutstandings->count() === 1 ? 'has' : 'have' }} outstanding fund used.
                    @else
                        consider replenishing the fund before releasing more advances.
                    @endif
                </span>
            </div>
            @endif

            @if(!$isInitialized)
            {{-- Not set up yet: no balance figures until there is a starting capital --}}
            <div class="rf-setup-prompt">
                <div class="rf-setup-icon"><i data-lucide="wallet"></i></div>
                <div style="flex:1;min-width:0;">
                    <h2>Set up the revolving fund</h2>
                    <p>Enter the fund's starting capital to begin. Projects can then draw from it when their spending is more than the money received, and it is paid back from their client payments. You can add more capital later.</p>
                </div>
                <button class="add-btn" type="button" data-open-setup><i data-lucide="settings"></i> Set Up Fund</button>
            </div>
            @else
            {{-- Fund overview: Total Balance (with how much of the invested money is left), Initial Balance and
                 Additional Capital beside it, then one slim activity strip and — only when a project owes the
                 fund — the list of who owes what. --}}
            @php
                $barPct   = $totalCapital > 0 ? max(0, min(100, round(($currentBalance / $totalCapital) * 100))) : 0;
                $barTone  = $barPct >= 60 ? 'is-good' : ($barPct >= 30 ? 'is-mid' : 'is-low');
                $owingCount = $projectOutstandings->count();
            @endphp
            <div class="rf-overview">
                <div class="rf-total">
                    <div class="rf-total-label"><i data-lucide="wallet"></i> Total Balance</div>
                    <div class="rf-total-value">₱{{ $rfMoney($currentBalance) }}</div>
                    <div class="rf-total-sub">
                        {{ $barPct }}% of the ₱{{ $rfMoney($totalCapital) }} invested is still available{{ $barPct < 30 ? ' — running low' : '' }}
                    </div>
                    <div class="rf-bar {{ $barTone }}" role="img" aria-label="{{ $barPct }}% of the fund available"><span style="width:{{ $barPct }}%;"></span></div>
                    <div class="rf-breakdown">
                        <div class="rf-bd-item">
                            <div class="rf-bd-label">Initial</div>
                            <div class="rf-bd-value">₱{{ $rfMoney($initialBalance) }}</div>
                        </div>
                        <div class="rf-bd-op">+</div>
                        <div class="rf-bd-item">
                            <div class="rf-bd-label">Added capital</div>
                            <div class="rf-bd-value">₱{{ $rfMoney($totalTopUps) }}</div>
                        </div>
                        <div class="rf-bd-op">−</div>
                        <div class="rf-bd-item">
                            <div class="rf-bd-label">Fund used</div>
                            <div class="rf-bd-value is-out">₱{{ $rfMoney($totalReleased) }}</div>
                        </div>
                        <div class="rf-bd-op">+</div>
                        <div class="rf-bd-item">
                            <div class="rf-bd-label">Replenished</div>
                            <div class="rf-bd-value is-in">₱{{ $rfMoney($totalReplenished) }}</div>
                        </div>
                    </div>
                </div>

                <div class="rf-side">
                    <div class="rf-side-card">
                        <div class="rf-side-head">
                            <span class="rf-side-icon"><i data-lucide="landmark"></i></span>
                            <span class="rf-side-title">Initial Balance</span>
                        </div>
                        <div class="rf-side-value">₱{{ $rfMoney($initialBalance) }}</div>
                        <div class="rf-side-sub">
                            Starting capital{{ $initialSetAt ? ' · set ' . $initialSetAt->format('M d, Y') : '' }}
                            @if($canEditInitial)
                            · <button type="button" class="rf-link" data-open-setup>Edit</button>
                            @endif
                        </div>
                    </div>
                    <div class="rf-side-card">
                        <div class="rf-side-head">
                            <span class="rf-side-icon is-green"><i data-lucide="circle-plus"></i></span>
                            <span class="rf-side-title">Additional Capital</span>
                            <button type="button" class="rf-topup-btn" data-open-topup><i data-lucide="plus"></i> Add Capital</button>
                        </div>
                        <div class="rf-side-value">₱{{ $rfMoney($totalTopUps) }}</div>
                        <div class="rf-side-sub">
                            @if($topUpCount)
                            {{ $topUpCount }} capital addition{{ $topUpCount === 1 ? '' : 's' }} · last {{ $lastTopUp->created_at->format('M d, Y g:i A') }}
                            @else
                            No additional capital yet
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Activity at a glance — one slim strip instead of three large cards --}}
            <div class="rf-strip">
                <div class="rf-stat">
                    <span class="rf-stat-dot is-red"></span>
                    <div>
                        <div class="rf-stat-label">Total Fund Used This Month</div>
                        <div class="rf-stat-value">₱{{ $rfMoney($totalDrawnThisMonth) }}</div>
                    </div>
                </div>
                <div class="rf-stat">
                    <span class="rf-stat-dot is-green"></span>
                    <div>
                        <div class="rf-stat-label">Total Replenished</div>
                        <div class="rf-stat-value">₱{{ $rfMoney($totalReplenished) }}</div>
                    </div>
                </div>
                <div class="rf-stat">
                    <span class="rf-stat-dot is-amber"></span>
                    <div>
                        <div class="rf-stat-label">Pending Replenishment</div>
                        <div class="rf-stat-value">₱{{ $rfMoney($pendingReplenishment) }}</div>
                    </div>
                </div>
                <div class="rf-strip-note">Replenishes when progress billings are paid</div>
            </div>

            {{-- Who owes the fund — only shown when someone does --}}
            @if($owingCount)
            <div class="rf-owed">
                <div class="rf-owed-head">
                    <span>Owed by projects</span>
                    <em>{{ $owingCount }} project{{ $owingCount === 1 ? '' : 's' }}</em>
                </div>
                @foreach($projectOutstandings as $pd)
                <div class="rf-owed-row">
                    <div style="min-width:0;">
                        <div class="rf-owed-name">{{ $pd['project']?->name ?? '—' }}</div>
                        <div class="rf-owed-meta">{{ $pd['project']?->client ? $pd['project']->client . ' · ' : '' }}{{ $pd['latest_tx']?->purpose ?? 'Outstanding advance' }}</div>
                    </div>
                    <div class="rf-owed-amount">₱{{ $rfMoney($pd['outstanding']) }}</div>
                </div>
                @endforeach
            </div>
            @endif
            @endif

            {{-- Fund history (every release, repayment and capital addition) --}}
            <div class="rf-main-grid">

                <!-- Transaction Ledger -->
                <div class="table-card" style="margin-bottom:0;display:flex;flex-direction:column;">
                    <div class="table-toolbar" style="margin-bottom:14px;">
                        <span style="font-size:14px;font-weight:800;color:var(--dark);">Transaction ledger</span>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <div class="search-box" style="min-width:180px;">
                                <i data-lucide="search"></i>
                                <input type="text" id="fundSearch" placeholder="Search...">
                            </div>
                            <select id="fundTypeFilter" class="filter-select" style="height:40px;">
                                <option value="">All</option>
                                <option value="release">Fund Used</option>
                                <option value="replenishment">Replenishment</option>
                                <option value="topup">Capital Added</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-wrapper" style="flex:1;overflow-y:auto;max-height:480px;">
                        <table class="data-table" id="fundLedgerTable" style="height:100%;">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Purpose</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $tx)
                                <tr data-type="{{ $tx->type }}"
                                    data-search="{{ strtolower(($tx->purpose ?? $tx->description ?? '') . ' ' . ($tx->project->name ?? '')) }}">
                                    <td style="overflow:hidden;">
                                        @if($tx->project)
                                        @php
                                            $namePrefix = '';
                                            $nameMain   = $tx->project->name;
                                            if (preg_match('/^(Fabrication of)\s+(.+)$/i', $tx->project->name, $nm)) {
                                                $namePrefix = $nm[1];
                                                $nameMain   = $nm[2];
                                            }
                                        @endphp
                                        <span style="display:inline-flex;flex-direction:column;max-width:100%;min-width:0;">
                                            @if($namePrefix)
                                                <span style="font-size:9px;font-weight:700;color:var(--muted);letter-spacing:.05em;line-height:1.2;text-transform:uppercase;white-space:nowrap;">{{ $namePrefix }}</span>
                                            @endif
                                            <span style="font-size:12.5px;font-weight:800;color:var(--dark);line-height:1.3;white-space:normal;word-break:break-word;">{{ $nameMain }}</span>
                                        </span>
                                        @elseif($tx->type === 'topup')
                                        <span style="font-size:12.5px;font-weight:800;color:var(--dark);">Fund capital</span>
                                        @else
                                        <span style="color:var(--muted);">—</span>
                                        @endif
                                    </td>
                                    <td style="font-size:13px;color:var(--muted);white-space:normal;word-break:break-word;max-width:320px;line-height:1.5;">
                                        {{ $tx->purpose ?? $tx->description ?? '—' }}
                                        @if($tx->type === 'topup' && $tx->recorded_by)
                                        <div style="font-size:11px;">by {{ $tx->recorded_by }}</div>
                                        @endif
                                    </td>
                                    <td style="text-align:center;">
                                        @if($tx->type === 'topup')
                                        <span class="status-badge ongoing" style="font-size:11px;">Capital Added</span>
                                        @else
                                        <span class="status-badge {{ $tx->type === 'release' ? 'shortage' : 'completed' }}" style="font-size:11px;">
                                            {{ $tx->type === 'release' ? 'Fund Used' : 'Replenishment' }}
                                        </span>
                                        @endif
                                    </td>
                                    <td style="text-align:center;color:var(--muted);white-space:nowrap;font-size:12.5px;">
                                        {{ \Carbon\Carbon::parse($tx->date)->format('M d, Y') }}
                                        @if($tx->type === 'topup' && $tx->created_at)
                                        <div style="font-size:11px;">{{ $tx->created_at->format('g:i A') }}</div>
                                        @endif
                                    </td>
                                    <td style="text-align:right;padding-right:24px;font-weight:700;font-size:13px;color:{{ $tx->type === 'release' ? '#b91c1c' : '#16a34a' }};">
                                        {{ $tx->type === 'release' ? '' : '+' }}₱{{ $rfMoney($tx->amount) }}
                                    </td>
                                </tr>
                                @empty
                                <tr style="height:100%;">
                                    <td colspan="5" style="text-align:center;vertical-align:middle;padding:48px 20px;color:var(--muted);border-bottom:none;">
                                        <i data-lucide="inbox" style="width:32px;height:32px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                        <div style="font-size:14px;font-weight:700;color:var(--dark);margin-bottom:4px;">No transactions recorded yet.</div>
                                        <div style="font-size:12.5px;">Capital additions, fund releases and replenishments will show up here.</div>
                                    </td>
                                </tr>
                                @endforelse
                                <tr id="noFundMatchRow" style="display:none;">
                                    <td colspan="5" style="text-align:center;padding:32px;color:var(--muted);border-bottom:none;">No transactions match your search.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </main>
    </div>

    @if(!$isInitialized || $canEditInitial)
    <!-- Initial Fund Setup Modal — only while the fund has no history yet -->
    <div class="modal-overlay" id="initialFundModal" role="dialog" aria-modal="true" aria-labelledby="initialFundTitle">
        <div class="modal-card" style="max-width:440px;">
            <div class="modal-header">
                <div>
                    <h2 id="initialFundTitle">{{ $isInitialized ? 'Edit Initial Balance' : 'Set Up the Fund' }}</h2>
                    <p>The fund's starting capital. It stays as a fixed record — add more later with Add Capital.</p>
                </div>
                <button class="modal-close" type="button" data-close-modal aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.revolving_fund.setup_initial') }}">
                @csrf
                <div class="form-group">
                    <label for="initialBalanceInput">Initial Balance (₱)</label>
                    <input type="number" id="initialBalanceInput" name="initial_balance" required min="0.01" step="0.01" value="{{ $initialBalance ?: '' }}" placeholder="e.g. 200000">
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" data-close-modal>Cancel</button>
                    <button type="submit" class="save-btn"><i data-lucide="save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if($isInitialized)
    <!-- Add Capital Modal -->
    <div class="modal-overlay" id="topUpModal" role="dialog" aria-modal="true" aria-labelledby="topUpTitle">
        <div class="modal-card" style="max-width:440px;">
            <div class="modal-header">
                <div>
                    <h2 id="topUpTitle">Add Capital</h2>
                    <p>Invest additional capital in the revolving fund. It is recorded in the fund history.</p>
                </div>
                <button class="modal-close" type="button" data-close-modal aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.revolving_fund.top_up') }}" id="topUpForm" novalidate>
                @csrf
                <div class="form-group">
                    <label for="topUpAmount">Amount (₱)</label>
                    <input type="text" inputmode="decimal" id="topUpAmount" name="amount" placeholder="0.00" autocomplete="off" required>
                    <div class="rf-field-error" id="topUpError" hidden></div>
                </div>
                <div class="rf-topup-preview">
                    <div><span>Current balance</span><strong>₱{{ $rfMoney($currentBalance) }}</strong></div>
                    <div><span>Additional capital</span><strong id="topUpAdding">₱0</strong></div>
                    <div class="is-total"><span>New balance</span><strong id="topUpNewBalance">₱{{ $rfMoney($currentBalance) }}</strong></div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" data-close-modal>Cancel</button>
                    <button type="submit" class="save-btn" id="topUpSubmit"><i data-lucide="check"></i> Add Capital</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // ---- Set Up / Add Capital modals ----
        function rfOpen(id, focusId) {
            var m = document.getElementById(id);
            if (!m) return;
            m.classList.add('show');
            document.body.style.overflow = 'hidden';
            if (focusId) setTimeout(function () { var f = document.getElementById(focusId); if (f) f.focus(); }, 50);
        }
        function rfClose(m) {
            m.classList.remove('show');
            if (!document.querySelector('.modal-overlay.show')) document.body.style.overflow = '';
        }
        document.querySelectorAll('[data-open-setup]').forEach(function (b) { b.addEventListener('click', function () { rfOpen('initialFundModal', 'initialBalanceInput'); }); });
        document.querySelectorAll('[data-open-topup]').forEach(function (b) {
            b.addEventListener('click', function () {
                var f = document.getElementById('topUpForm');
                if (f) { f.reset(); document.getElementById('topUpError').hidden = true; updateTopUpPreview(); }
                rfOpen('topUpModal', 'topUpAmount');
            });
        });
        document.querySelectorAll('[data-close-modal]').forEach(function (b) { b.addEventListener('click', function () { rfClose(b.closest('.modal-overlay')); }); });
        ['initialFundModal', 'topUpModal'].forEach(function (id) {
            var m = document.getElementById(id);
            if (m) m.addEventListener('click', function (e) { if (e.target === m) rfClose(m); });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            document.querySelectorAll('#initialFundModal.show, #topUpModal.show').forEach(rfClose);
        });

        // Additional capital amount: thousand separators, live "new balance", and checked before it is sent
        var RF_BALANCE = {{ (float) $currentBalance }};
        function rfPeso(n) { var whole = Math.round(n * 100) % 100 === 0; return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: whole ? 0 : 2, maximumFractionDigits: 2 }); }
        function topUpValue() { var el = document.getElementById('topUpAmount'); return el ? parseFloat(el.value.replace(/,/g, '')) || 0 : 0; }
        function updateTopUpPreview() {
            var amt = topUpValue();
            var add = document.getElementById('topUpAdding');
            if (!add) return;
            add.textContent = '+' + rfPeso(amt);
            document.getElementById('topUpNewBalance').textContent = rfPeso(RF_BALANCE + amt);
        }
        var topUpAmount = document.getElementById('topUpAmount');
        if (topUpAmount) {
            topUpAmount.addEventListener('input', function () {
                var raw = this.value.replace(/[^0-9.]/g, '');
                var parts = raw.split('.');
                this.value = parts[0].replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, ',') + (parts.length > 1 ? '.' + parts.slice(1).join('').slice(0, 2) : '');
                document.getElementById('topUpError').hidden = true;
                this.classList.remove('input-error');
                updateTopUpPreview();
            });
            document.getElementById('topUpForm').addEventListener('submit', function (e) {
                var amt = topUpValue();
                var err = document.getElementById('topUpError');
                var msg = amt <= 0 ? 'Enter an amount more than ₱0.' : (amt > 999999999 ? 'That amount is too large.' : '');
                if (msg) {
                    e.preventDefault();
                    err.textContent = msg;
                    err.hidden = false;
                    topUpAmount.classList.add('input-error');
                    topUpAmount.focus();
                    return;
                }
                topUpAmount.value = amt.toFixed(2);   // sent without separators
            });
        }
        @if($errors->has('amount'))
        // A rejected capital addition comes back here — reopen the modal with the reason
        rfOpen('topUpModal', 'topUpAmount');
        document.getElementById('topUpError').textContent = @json($errors->first('amount'));
        document.getElementById('topUpError').hidden = false;
        @endif

        // Search + filter
        var searchInput = document.getElementById('fundSearch');
        var typeFilter  = document.getElementById('fundTypeFilter');
        var noMatchRow  = document.getElementById('noFundMatchRow');

        function applyFundFilters() {
            var q    = (searchInput.value || '').toLowerCase();
            var type = typeFilter.value;
            var visible = 0;
            document.querySelectorAll('#fundLedgerTable tbody tr[data-type]').forEach(function(row) {
                var ms = !q || (row.dataset.search || '').includes(q);
                var mt = !type || row.dataset.type === type;
                row.style.display = (ms && mt) ? '' : 'none';
                if (ms && mt) visible++;
            });
            noMatchRow.style.display = visible === 0 ? '' : 'none';
        }

        if (searchInput) searchInput.addEventListener('input', applyFundFilters);
        if (typeFilter) typeFilter.addEventListener('change', applyFundFilters);
    </script>

    <style>
        /* ── Fund overview: Total Balance (largest), Initial Balance and Additional Capital beside it ── */
        .rf-overview { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); gap: 16px; margin-bottom: 20px; }
        .rf-total { background: linear-gradient(180deg, #333333 0%, #1f1f1f 100%); border-radius: 20px; padding: 22px 24px; color: #fff; box-shadow: 0 8px 24px rgba(0,0,0,.2); display: flex; flex-direction: column; }
        .rf-total-label { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: rgba(255,255,255,.55); }
        .rf-total-label i, .rf-total-label svg { width: 16px; height: 16px; }
        .rf-total-value { margin-top: 8px; font-size: 40px; font-weight: 900; letter-spacing: -1px; color: #4ade80; line-height: 1.1; font-variant-numeric: tabular-nums; word-break: break-word; }
        .rf-total-sub { margin-top: 4px; font-size: 13px; color: rgba(255,255,255,.5); }
        .rf-formula { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 8px; margin-top: auto; padding-top: 18px; font-size: 12px; color: rgba(255,255,255,.75); }
        .rf-formula span { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.1); border-radius: 999px; padding: 4px 10px; font-weight: 800; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .rf-formula em { font-style: normal; font-weight: 600; color: rgba(255,255,255,.5); margin-right: 4px; }
        .rf-formula b { color: rgba(255,255,255,.4); font-weight: 800; }

        /* how the total is made up — sits at the bottom of the Total Balance card */
        .rf-breakdown { display: flex; align-items: stretch; gap: 8px; margin-top: auto; padding-top: 18px; }
        .rf-bd-item { flex: 1 1 0; min-width: 0; padding: 12px 14px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.08); border-radius: 14px; }
        .rf-bd-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: rgba(255,255,255,.45); line-height: 1.3; }
        .rf-bd-value { margin-top: 4px; font-size: 17px; font-weight: 900; color: #fff; font-variant-numeric: tabular-nums; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .rf-bd-value.is-out { color: #fca5a5; }
        .rf-bd-value.is-in  { color: #86efac; }
        .rf-bd-op { align-self: center; flex-shrink: 0; font-size: 15px; font-weight: 800; color: rgba(255,255,255,.3); }
        @media (max-width: 760px) {
            .rf-breakdown { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
            .rf-bd-op { display: none; }
            .rf-bd-item { padding: 10px 12px; }
            .rf-bd-value { font-size: 15px; }
        }
        .rf-side { display: grid; grid-template-rows: 1fr 1fr; gap: 16px; }
        .rf-side-card { background: var(--white); border: 1px solid var(--border); border-radius: 20px; padding: 16px 18px; display: flex; flex-direction: column; justify-content: center; }
        .rf-side-head { display: flex; align-items: center; gap: 10px; }
        .rf-side-icon { width: 34px; height: 34px; border-radius: 10px; background: var(--cream-soft); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--dark); flex-shrink: 0; }
        .rf-side-icon.is-green { background: #E7F6EC; border-color: #bbf7d0; color: #207A3A; }
        .rf-side-icon i, .rf-side-icon svg { width: 16px; height: 16px; }
        .rf-side-title { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
        .rf-side-value { margin-top: 10px; font-size: 22px; font-weight: 900; color: var(--dark); font-variant-numeric: tabular-nums; }
        .rf-side-sub { margin-top: 2px; font-size: 12px; color: var(--muted); }
        .rf-link { background: none; border: none; padding: 0; font: inherit; font-weight: 800; color: var(--dark); text-decoration: underline; cursor: pointer; }
        .rf-topup-btn { margin-left: auto; display: inline-flex; align-items: center; gap: 5px; height: 32px; padding: 0 12px; border-radius: 10px; border: none; background: var(--dark); color: #fff; font-size: 12px; font-weight: 800; cursor: pointer; }
        .rf-topup-btn:hover { background: #1a1a1a; }
        .rf-topup-btn i, .rf-topup-btn svg { width: 13px; height: 13px; }

        /* how much of the invested money is still in the fund */
        .rf-bar { height: 8px; margin-top: 12px; border-radius: 999px; background: rgba(255,255,255,.1); overflow: hidden; }
        .rf-bar span { display: block; height: 100%; border-radius: 999px; transition: width .5s; }
        .rf-bar.is-good span { background: #4ade80; }
        .rf-bar.is-mid span  { background: #fbbf24; }
        .rf-bar.is-low span  { background: #f87171; }

        /* activity strip — three small figures in one card */
        .rf-strip { display: flex; align-items: center; flex-wrap: wrap; gap: 12px 0; margin-bottom: 20px; padding: 14px 6px; background: var(--white); border: 1px solid var(--border); border-radius: 16px; }
        .rf-stat { display: flex; align-items: center; gap: 10px; padding: 0 20px; border-right: 1px solid var(--border); }
        .rf-stat:nth-child(3) { border-right: none; }
        .rf-stat-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .rf-stat-dot.is-red   { background: #ef4444; }
        .rf-stat-dot.is-green { background: #16a34a; }
        .rf-stat-dot.is-amber { background: #f59e0b; }
        .rf-stat-label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
        .rf-stat-value { margin-top: 1px; font-size: 16px; font-weight: 900; color: var(--dark); font-variant-numeric: tabular-nums; }
        .rf-strip-note { margin-left: auto; padding: 0 18px; font-size: 12px; color: var(--muted); }

        /* projects that still owe the fund */
        .rf-owed { margin-bottom: 20px; background: var(--white); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
        .rf-owed-head { display: flex; justify-content: space-between; align-items: center; padding: 12px 18px; background: var(--cream-soft); border-bottom: 1px solid var(--border); font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
        .rf-owed-head em { font-style: normal; text-transform: none; letter-spacing: 0; font-weight: 700; }
        .rf-owed-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 12px 18px; border-bottom: 1px solid var(--border); }
        .rf-owed-row:last-child { border-bottom: none; }
        .rf-owed-name { font-size: 13.5px; font-weight: 800; color: var(--dark); }
        .rf-owed-meta { margin-top: 2px; font-size: 12px; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .rf-owed-amount { flex-shrink: 0; font-size: 14px; font-weight: 900; color: #b91c1c; font-variant-numeric: tabular-nums; }

        @media (max-width: 760px) {
            .rf-stat { flex: 1 1 100%; border-right: none; padding: 0 14px; }
            .rf-strip-note { margin-left: 0; padding: 0 14px; }
        }

        .rf-setup-prompt { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; padding: 22px 24px; margin-bottom: 20px; background: var(--white); border: 1.5px dashed var(--border-2, #d0d0d0); border-radius: 20px; }
        .rf-setup-icon { width: 52px; height: 52px; border-radius: 16px; background: var(--cream-soft); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--dark); flex-shrink: 0; }
        .rf-setup-icon i, .rf-setup-icon svg { width: 24px; height: 24px; }
        .rf-setup-prompt h2 { margin: 0 0 4px; font-size: 17px; font-weight: 900; color: var(--dark); }
        .rf-setup-prompt p { margin: 0; font-size: 13px; color: var(--muted); line-height: 1.6; max-width: 640px; }

        .rf-topup-preview { border: 1px solid var(--border); border-radius: 12px; overflow: hidden; margin-bottom: 16px; }
        .rf-topup-preview > div { display: flex; justify-content: space-between; padding: 9px 14px; font-size: 13px; border-bottom: 1px solid var(--border); }
        .rf-topup-preview > div:last-child { border-bottom: none; }
        .rf-topup-preview span { color: var(--muted); }
        .rf-topup-preview strong { font-variant-numeric: tabular-nums; color: var(--dark); }
        .rf-topup-preview .is-total { background: var(--cream-soft); }
        .rf-topup-preview .is-total strong { color: #16a34a; font-size: 15px; }
        .rf-field-error { margin-top: 6px; font-size: 12px; font-weight: 600; color: var(--danger); }
        .rf-field-error[hidden] { display: none; }
        #topUpForm input.input-error { border-color: var(--danger); }

        @media (max-width: 900px) {
            .rf-overview { grid-template-columns: 1fr; }
            .rf-side { grid-template-rows: none; grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 560px) {
            .rf-side { grid-template-columns: 1fr; }
            .rf-total-value { font-size: 32px; }
            .page-grid[style*="repeat(3,1fr)"] { grid-template-columns: 1fr !important; }
        }

        /* Warning banner */
        .rf-warning-banner {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 10px;
            padding: 14px 16px;
            font-size: 13px;
            color: #92400e;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        /* Main grid */
        /* Fund history uses the full width — releases are recorded automatically from purchases,
           salaries and overhead, so there is no manual "Record Fund Used" panel beside it */
        .rf-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 20px;
        }

        /* Transaction row */
        .rf-tx-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 4px;
            border-bottom: 1px solid var(--border);
        }
        .rf-tx-row:last-child { border-bottom: none; }

        .rf-tx-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .rf-tx-dot.red   { background: #ef4444; }
        .rf-tx-dot.green { background: #16a34a; }

        .rf-tx-body { flex: 1; min-width: 0; }
        .rf-tx-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .rf-tx-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
            font-size: 11.5px;
            color: var(--muted);
            font-weight: 500;
        }

        .rf-tx-amount {
            font-size: 13.5px;
            font-weight: 800;
            flex-shrink: 0;
        }
        .rf-tx-amount.red   { color: #ef4444; }
        .rf-tx-amount.green { color: #16a34a; }

        /* Responsive */
        @media (max-width: 1024px) {
            .rf-main-grid { grid-template-columns: 1fr; }
        }

        /* Prevent ledger from scrolling horizontally */
        #fundLedgerTable {
            min-width: 0;
            width: 100%;
            table-layout: fixed;
        }
        #fundLedgerTable th { font-size: 12px; font-weight: 800; padding: 10px 10px; }
        #fundLedgerTable td { font-size: 12px; padding: 10px 10px; }
        #fundLedgerTable th:nth-child(1),
        #fundLedgerTable td:nth-child(1) { width: 26%; }
        #fundLedgerTable th:nth-child(2) { width: 28%; }
        #fundLedgerTable td:nth-child(2) { width: 28%; white-space: normal; word-break: break-word; line-height: 1.5; font-weight: 400; font-size: 12px; color: var(--muted); }
        #fundLedgerTable th:nth-child(3),
        #fundLedgerTable td:nth-child(3) { width: 16%; text-align: center; }
        #fundLedgerTable th:nth-child(4),
        #fundLedgerTable td:nth-child(4) { width: 16%; text-align: center; }
        #fundLedgerTable th:nth-child(5),
        #fundLedgerTable td:nth-child(5) { width: 14%; text-align: right; padding-right: 20px; font-weight: 700; }

        /* client-pill inside ledger — wraps long names */
        #fundLedgerTable .client-pill {
            font-size: 10.5px;
            padding: 2px 8px;
            white-space: normal;
            word-break: break-word;
            display: inline-flex;
            max-width: 100%;
        }

        .rf-main-grid .table-wrapper { overflow-x: hidden; }
    </style>
</body>
</html>
