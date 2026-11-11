<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revolving Fund | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}?v={{ @filemtime(public_path('css/admin.css')) }}" rel="stylesheet">
</head>
<body class="page-enter pg-admin-revolving-fund">

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
            {{-- Not set up yet: no balance figures until there is an initial balance --}}
            <div class="rf-setup-prompt">
                <div class="rf-setup-icon"><i data-lucide="wallet"></i></div>
                <div style="flex:1;min-width:0;">
                    <h2>Set up the revolving fund</h2>
                    <p>Enter the fund's initial balance to begin. Projects can then draw from it when their spending is more than the money received, and it is paid back from their client payments. You can add more funds later.</p>
                </div>
                <button class="add-btn" type="button" data-open-setup><i data-lucide="settings"></i> Set Up Fund</button>
            </div>
            @else
            {{-- Fund overview: Total Balance (with how much of the invested money is left), Initial Balance and
                 Additional Fund beside it, then — only when a project owes the fund — the list of who owes what. --}}
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
                            <div class="rf-bd-label">Added fund</div>
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
                    </div>
                    <div class="rf-side-card">
                        <div class="rf-side-head">
                            <span class="rf-side-icon is-green"><i data-lucide="circle-plus"></i></span>
                            <span class="rf-side-title">Additional Fund</span>
                            <button type="button" class="rf-topup-btn" data-open-topup><i data-lucide="plus"></i> Add Fund</button>
                        </div>
                        <div class="rf-side-value">₱{{ $rfMoney($totalTopUps) }}</div>
                        <div class="rf-side-sub">
                            @if($topUpCount)
                            {{ $topUpCount }} fund addition{{ $topUpCount === 1 ? '' : 's' }} · last {{ $lastTopUp->created_at->format('M d, Y g:i A') }}
                            @else
                            No additional fund yet
                            @endif
                        </div>
                    </div>
                </div>
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

            {{-- Fund history (every release, repayment and fund addition) --}}
            <div class="rf-main-grid">

                <!-- Transaction Ledger -->
                <div class="table-card" style="margin-bottom:0;display:flex;flex-direction:column;">
                    @php
                        // header summary: entries, money in (replenished + added) and money out (used)
                        $ledgerIn  = $transactions->whereIn('type', ['replenishment', 'topup'])->sum('amount');
                        $ledgerOut = $transactions->where('type', 'release')->sum('amount');
                        $ledgerTabs = [
                            ''              => 'All',
                            'release'       => 'Fund Used',
                            'replenishment' => 'Replenishment',
                            'topup'         => 'Fund Added',
                        ];
                    @endphp
                    <div class="rf-ledger-head">
                        <div class="rf-ledger-heading">
                            <span class="rf-ledger-icon"><i data-lucide="book-open-text"></i></span>
                            <div style="min-width:0;">
                                <div class="rf-ledger-title">Transaction Ledger</div>
                                <div class="rf-ledger-chips">
                                    <span class="rf-chip">{{ $transactions->count() }} entr{{ $transactions->count() === 1 ? 'y' : 'ies' }}</span>
                                    <span class="rf-chip is-in"><i data-lucide="arrow-down-left"></i> In ₱{{ number_format($ledgerIn, 2) }}</span>
                                    <span class="rf-chip is-out"><i data-lucide="arrow-up-right"></i> Out ₱{{ number_format($ledgerOut, 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="search-box rf-ledger-search">
                            <i data-lucide="search"></i>
                            <input type="text" id="fundSearch" placeholder="Search project or purpose...">
                        </div>
                    </div>
                    {{-- filter by type — the tabs replace the old dropdown --}}
                    <div class="rf-ledger-tabs" id="fundTypeTabs" role="tablist">
                        @foreach($ledgerTabs as $tKey => $tLabel)
                        <button type="button" class="rf-ledger-tab {{ $tKey === '' ? 'is-active' : '' }}" data-type="{{ $tKey }}" role="tab">
                            {{ $tLabel }}
                            <span class="rf-ledger-count">{{ $tKey === '' ? $transactions->count() : $transactions->where('type', $tKey)->count() }}</span>
                        </button>
                        @endforeach
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
                                    data-search="{{ strtolower(str_replace('Additional capital', 'Additional fund', $tx->purpose ?? $tx->description ?? '') . ' ' . ($tx->project->name ?? '')) }}">
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
                                        <span style="font-size:12.5px;font-weight:800;color:var(--dark);">Revolving fund</span>
                                        @else
                                        <span style="color:var(--muted);">—</span>
                                        @endif
                                    </td>
                                    <td style="font-size:13px;color:var(--muted);white-space:normal;word-break:break-word;line-height:1.5;">
                                        {{ str_replace('Additional capital', 'Additional fund', $tx->purpose ?? $tx->description ?? '—') }}
                                        @if($tx->type === 'topup' && $tx->recorded_by)
                                        <div style="font-size:11px;">by {{ $tx->recorded_by }}</div>
                                        @endif
                                    </td>
                                    <td style="text-align:center;">
                                        @if($tx->type === 'topup')
                                        <span class="status-badge ongoing" style="font-size:11px;">Fund Added</span>
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
                                    <td style="font-weight:700;font-size:13px;color:{{ $tx->type === 'release' ? '#b91c1c' : '#16a34a' }};">
                                        {{ $tx->type === 'release' ? '' : '+' }}₱{{ $rfMoney($tx->amount) }}
                                    </td>
                                </tr>
                                @empty
                                <tr style="height:100%;">
                                    <td colspan="5" style="text-align:center;vertical-align:middle;padding:48px 20px;color:var(--muted);border-bottom:none;">
                                        <i data-lucide="inbox" style="width:32px;height:32px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                        <div style="font-size:14px;font-weight:700;color:var(--dark);margin-bottom:4px;">No transactions recorded yet.</div>
                                        <div style="font-size:12.5px;">Fund additions, fund releases and replenishments will show up here.</div>
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

    @if(!$isInitialized)
    <!-- Initial Fund Setup Modal — only at the first setup -->
    <div class="modal-overlay" id="initialFundModal" role="dialog" aria-modal="true" aria-labelledby="initialFundTitle">
        <div class="modal-card" style="max-width:440px;">
            <div class="modal-header">
                <div>
                    <h2 id="initialFundTitle">Set Up the Fund</h2>
                    <p>The fund's initial balance. Check the amount first: it can't be edited after saving. Add more later with Add Fund.</p>
                </div>
                <button class="modal-close" type="button" data-close-modal aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.revolving_fund.setup_initial') }}">
                @csrf
                <div class="form-group">
                    <label for="initialBalanceInput">Initial Balance (₱)</label>
                    <input type="number" id="initialBalanceInput" name="initial_balance" required min="0.01" step="0.01" placeholder="e.g. 200000">
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
    <!-- Add Fund Modal -->
    <div class="modal-overlay" id="topUpModal" role="dialog" aria-modal="true" aria-labelledby="topUpTitle">
        <div class="modal-card" style="max-width:440px;">
            <div class="modal-header">
                <div>
                    <h2 id="topUpTitle">Add Fund</h2>
                    <p>Add more money to the revolving fund. It is recorded in the fund history.</p>
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
                    <div><span>Additional fund</span><strong id="topUpAdding">₱0</strong></div>
                    <div class="is-total"><span>New balance</span><strong id="topUpNewBalance">₱{{ $rfMoney($currentBalance) }}</strong></div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" data-close-modal>Cancel</button>
                    <button type="submit" class="save-btn" id="topUpSubmit"><i data-lucide="check"></i> Add Fund</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // ---- Set Up / Add Fund modals ----
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

        // Additional fund amount: thousand separators, live "new balance", and checked before it is sent
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
        // A rejected fund addition comes back here — reopen the modal with the reason
        rfOpen('topUpModal', 'topUpAmount');
        document.getElementById('topUpError').textContent = @json($errors->first('amount'));
        document.getElementById('topUpError').hidden = false;
        @endif

        // Search + filter
        var searchInput = document.getElementById('fundSearch');
        var noMatchRow  = document.getElementById('noFundMatchRow');
        var fundType    = '';   // set by the type tabs ('' = all)

        function applyFundFilters() {
            var q    = (searchInput.value || '').toLowerCase();
            var type = fundType;
            var rows = document.querySelectorAll('#fundLedgerTable tbody tr[data-type]');
            var visible = 0;
            rows.forEach(function(row) {
                var ms = !q || (row.dataset.search || '').includes(q);
                var mt = !type || row.dataset.type === type;
                row.style.display = (ms && mt) ? '' : 'none';
                if (ms && mt) visible++;
            });
            // "no match" only when there are transactions to filter — an empty ledger already has its own message
            if (noMatchRow) noMatchRow.style.display = rows.length && visible === 0 ? '' : 'none';
        }

        if (searchInput) searchInput.addEventListener('input', applyFundFilters);
        document.querySelectorAll('#fundTypeTabs .rf-ledger-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                document.querySelectorAll('#fundTypeTabs .rf-ledger-tab').forEach(function (t) { t.classList.toggle('is-active', t === tab); });
                fundType = tab.dataset.type;
                applyFundFilters();
            });
        });
    </script>

</body>
</html>
