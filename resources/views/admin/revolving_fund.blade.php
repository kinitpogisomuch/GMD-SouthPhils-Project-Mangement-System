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
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button class="add-btn" type="button" id="openDrawdownModal">
                        <i data-lucide="plus"></i> Record Drawdown
                    </button>
                    <button class="cancel-btn" type="button" id="openReplenishModal" style="display:inline-flex;align-items:center;gap:6px;">
                        <i data-lucide="refresh-cw" style="width:15px;height:15px;"></i> Record Replenishment
                    </button>
                    <button class="cancel-btn" type="button" id="openInitialFundModal" style="display:inline-flex;align-items:center;gap:6px;background:var(--cream-deep);">
                        <i data-lucide="settings" style="width:15px;height:15px;"></i>
                    </button>
                </div>
            </div>

            @if(session('success'))
            <div class="alert-banner success"><i data-lucide="check-circle"></i> {{ session('success') }}</div>
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
                    Revolving fund is at ₱{{ number_format($currentBalance, 2) }} —
                    @if($projectOutstandings->isNotEmpty())
                        {{ $projectOutstandings->count() }} active {{ Str::plural('project', $projectOutstandings->count()) }}
                        ({{ $projectOutstandings->map(fn($d) => $d['project']?->name ?? '—')->implode(', ') }})
                        currently {{ $projectOutstandings->count() === 1 ? 'has' : 'have' }} outstanding drawdowns.
                    @else
                        consider replenishing the fund before releasing more advances.
                    @endif
                </span>
            </div>
            @endif

            {{-- Summary Cards --}}
            <div class="page-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
                <div class="info-card teal">
                    <div class="info-card-icon teal"><i data-lucide="wallet"></i></div>
                    <h3>Current Balance</h3>
                    <div class="value">₱{{ number_format($currentBalance, 2) }}</div>
                    <div class="info-card-sub">Available for any active project</div>
                </div>
                <div class="info-card red">
                    <div class="info-card-icon red"><i data-lucide="trending-down"></i></div>
                    <h3>Total Drawn This Month</h3>
                    <div class="value">₱{{ number_format($totalDrawnThisMonth, 2) }}</div>
                    <div class="info-card-sub">Across {{ $activeAdvances }} active {{ Str::plural('project', $activeAdvances) }}</div>
                </div>
                <div class="info-card green">
                    <div class="info-card-icon green"><i data-lucide="trending-up"></i></div>
                    <h3>Total Replenished</h3>
                    <div class="value">₱{{ number_format($totalReplenished, 2) }}</div>
                    <div class="info-card-sub">From progress billings paid</div>
                </div>
                <div class="info-card orange">
                    <div class="info-card-icon orange"><i data-lucide="clock"></i></div>
                    <h3>Pending Replenishment</h3>
                    <div class="value">₱{{ number_format($pendingReplenishment, 2) }}</div>
                    <div class="info-card-sub">Outstanding advances</div>
                </div>
            </div>

            {{-- Fund Balance Tracker --}}
            @if($initialBalance > 0)
            @php
                $barPct   = $initialBalance > 0 ? min(100, round(($currentBalance / $initialBalance) * 100)) : 0;
                $barColor = $barPct >= 60 ? '#10B981' : ($barPct >= 30 ? '#F59E0B' : '#EF4444');
                $barGlow  = $barPct >= 60 ? 'rgba(16,185,129,.4)' : ($barPct >= 30 ? 'rgba(245,158,11,.4)' : 'rgba(239,68,68,.4)');
            @endphp
            <div style="background:linear-gradient(180deg,#333333 0%,#2a2a2a 100%);border-radius:20px;margin-bottom:24px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.2);">
                {{-- Header --}}
                <div style="display:flex;justify-content:space-between;align-items:center;padding:20px 24px 16px;border-bottom:1px solid rgba(255,255,255,.08);">
                    <div>
                        <div style="font-size:15px;font-weight:800;color:#fff;">Fund Balance Tracker</div>
                        <div style="font-size:12px;color:rgba(255,255,255,.4);margin-top:2px;">Replenishes when progress billings are paid</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:22px;font-weight:900;color:{{ $barColor }};">₱{{ number_format($currentBalance, 2) }}</div>
                        <div style="font-size:11px;color:rgba(255,255,255,.4);margin-top:2px;">of ₱{{ number_format($initialBalance, 2) }} total</div>
                    </div>
                </div>

                {{-- Progress bar --}}
                <div style="padding:16px 24px;">
                    <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:700;color:rgba(255,255,255,.35);margin-bottom:8px;">
                        <span>₱0</span>
                        <span style="color:{{ $barColor }};">{{ $barPct }}% remaining{{ $barPct < 30 ? ' — Low' : '' }}</span>
                        <span>₱{{ number_format($initialBalance, 2) }}</span>
                    </div>
                    <div style="height:10px;background:rgba(255,255,255,.1);border-radius:999px;overflow:hidden;">
                        <div style="height:100%;width:{{ $barPct }}%;background:{{ $barColor }};border-radius:999px;transition:width 0.5s;box-shadow:0 0 10px {{ $barGlow }};"></div>
                    </div>
                </div>

                {{-- Per-project outstanding --}}
                @if($projectOutstandings->isNotEmpty())
                <div style="padding:0 24px 20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;">
                    @foreach($projectOutstandings as $pd)
                    <div style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:14px 16px;">
                        <div style="font-size:12px;font-weight:800;color:rgba(255,255,255,.8);margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $pd['project']?->name ?? '—' }}
                        </div>
                        @if($pd['project']?->client)
                        <div style="margin-bottom:8px;">
                            <span class="client-pill" style="font-size:11px;padding:2px 8px;background:rgba(255,255,255,.1);color:rgba(255,255,255,.7);border-color:rgba(255,255,255,.15);">{{ $pd['project']->client }}</span>
                        </div>
                        @endif
                        <div style="font-size:16px;font-weight:900;color:#f87171;">₱{{ number_format($pd['outstanding'], 2) }}</div>
                        <div style="font-size:11px;color:rgba(255,255,255,.35);margin-top:3px;">{{ $pd['latest_tx']?->purpose ?? 'Outstanding advance' }}</div>
                    </div>
                    @endforeach
                </div>
                @else
                <div style="padding:0 24px 20px;font-size:13px;color:rgba(255,255,255,.3);text-align:center;">No outstanding project advances.</div>
                @endif
            </div>
            @endif

            {{-- Ledger + Record Transaction --}}
            <div class="rf-main-grid">

                <!-- Transaction Ledger -->
                <div class="table-card" style="margin-bottom:0;display:flex;flex-direction:column;align-self:start;">
                    <div class="table-toolbar" style="margin-bottom:14px;">
                        <span style="font-size:14px;font-weight:800;color:var(--dark);">Transaction ledger</span>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <div class="search-box" style="min-width:180px;">
                                <i data-lucide="search"></i>
                                <input type="text" id="fundSearch" placeholder="Search...">
                            </div>
                            <select id="fundTypeFilter" class="filter-select" style="height:40px;">
                                <option value="">All</option>
                                <option value="release">Drawdown</option>
                                <option value="replenishment">Replenishment</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-wrapper" style="flex:1;overflow-y:auto;max-height:480px;">
                        <table class="data-table" id="fundLedgerTable">
                            <thead>
                                <tr>
                                    <th style="text-align:left;">Project</th>
                                    <th style="text-align:left;">Purpose</th>
                                    <th style="text-align:center;">Type</th>
                                    <th style="text-align:center;">Date</th>
                                    <th style="text-align:right;padding-right:24px;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $tx)
                                <tr data-type="{{ $tx->type }}"
                                    data-search="{{ strtolower(($tx->purpose ?? $tx->description ?? '') . ' ' . ($tx->project->name ?? '')) }}">
                                    <td>
                                        @if($tx->project)
                                        <span class="client-pill">{{ $tx->project->name }}</span>
                                        @else
                                        <span style="color:var(--muted);">—</span>
                                        @endif
                                    </td>
                                    <td style="font-size:13px;color:var(--muted);white-space:normal;word-break:break-word;max-width:320px;line-height:1.5;">{{ $tx->purpose ?? $tx->description ?? '—' }}</td>
                                    <td style="text-align:center;">
                                        <span class="status-badge {{ $tx->type === 'release' ? 'shortage' : 'completed' }}" style="font-size:11px;">
                                            {{ $tx->type === 'release' ? 'Drawdown' : 'Replenishment' }}
                                        </span>
                                    </td>
                                    <td style="text-align:center;color:var(--muted);white-space:nowrap;font-size:12.5px;">
                                        {{ \Carbon\Carbon::parse($tx->date)->format('M d, Y') }}
                                    </td>
                                    <td style="text-align:right;padding-right:24px;font-weight:700;font-size:13px;color:{{ $tx->type === 'release' ? '#b91c1c' : '#16a34a' }};">
                                        ₱{{ number_format($tx->amount, 2) }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" style="text-align:center;padding:48px 20px;color:var(--muted);">
                                        No transactions recorded yet.
                                    </td>
                                </tr>
                                @endforelse
                                <tr id="noFundMatchRow" style="display:none;">
                                    <td colspan="5" style="text-align:center;padding:32px;color:var(--muted);">No transactions match your search.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Record a transaction -->
                <div style="margin-bottom:0;background:linear-gradient(180deg,#333333 0%,#2a2a2a 100%);border-radius:20px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.25);align-self:start;">

                    {{-- Header --}}
                    <div style="padding:20px 24px 16px;border-bottom:1px solid rgba(255,255,255,.08);">
                        <div style="font-size:15px;font-weight:800;color:#fff;margin-bottom:2px;">Record a transaction</div>
                        <div style="font-size:12px;color:rgba(255,255,255,.45);">Tag to a project for traceability</div>
                    </div>

                    {{-- Type toggle --}}
                    <div style="padding:16px 24px 0;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:20px;">
                            <button type="button" class="rf-type-btn active" data-type="drawdown" onclick="setTxType('drawdown')"
                                style="border-radius:12px;padding:12px 14px;border:1.5px solid rgba(255,255,255,.15);background:rgba(255,255,255,.1);color:#fff;display:flex;align-items:center;gap:10px;cursor:pointer;transition:all .18s;">
                                <i data-lucide="arrow-up-right" style="width:16px;height:16px;flex-shrink:0;"></i>
                                <div style="text-align:left;">
                                    <div style="font-weight:800;font-size:13px;">Drawdown</div>
                                    <div style="font-size:11px;opacity:.6;">Money going out</div>
                                </div>
                            </button>
                            <button type="button" class="rf-type-btn" data-type="replenishment" onclick="setTxType('replenishment')"
                                style="border-radius:12px;padding:12px 14px;border:1.5px solid rgba(255,255,255,.08);background:transparent;color:rgba(255,255,255,.5);display:flex;align-items:center;gap:10px;cursor:pointer;transition:all .18s;">
                                <i data-lucide="arrow-down-left" style="width:16px;height:16px;flex-shrink:0;"></i>
                                <div style="text-align:left;">
                                    <div style="font-weight:800;font-size:13px;">Replenishment</div>
                                    <div style="font-size:11px;opacity:.6;">Money coming in</div>
                                </div>
                            </button>
                        </div>

                        {{-- Drawdown form --}}
                        <form method="POST" action="{{ route('admin.revolving_fund.release') }}" id="drawdownForm">
                            @csrf
                            <div style="display:flex;flex-direction:column;gap:14px;">
                                <div>
                                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.45);display:block;margin-bottom:6px;">Project</label>
                                    <select name="project_id" required style="width:100%;height:44px;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.12);border-radius:10px;color:#fff;padding:0 12px;font-size:13px;font-weight:600;">
                                        <option value="" style="background:#2a2a2a;">Select project</option>
                                        @foreach($projects as $p)
                                        <option value="{{ $p->id }}" style="background:#2a2a2a;">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                    <div>
                                        <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.45);display:block;margin-bottom:6px;">Amount (₱)</label>
                                        <input type="number" name="amount" required min="0.01" step="0.01" placeholder="0.00"
                                            style="width:100%;height:44px;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.12);border-radius:10px;color:#fff;padding:0 12px;font-size:13px;font-weight:600;">
                                    </div>
                                    <div>
                                        <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.45);display:block;margin-bottom:6px;">Date</label>
                                        <input type="date" name="date" value="{{ now()->format('Y-m-d') }}"
                                            style="width:100%;height:44px;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.12);border-radius:10px;color:#fff;padding:0 12px;font-size:13px;font-weight:600;">
                                    </div>
                                </div>
                                <div>
                                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.45);display:block;margin-bottom:6px;">Reason / Purpose</label>
                                    <input type="text" name="purpose" required maxlength="255" placeholder="e.g. Purchase of steel plates"
                                        style="width:100%;height:44px;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.12);border-radius:10px;color:#fff;padding:0 12px;font-size:13px;font-weight:600;">
                                </div>
                            </div>
                            <button type="submit" class="save-btn" style="width:100%;justify-content:center;margin-top:16px;border-radius:12px;height:46px;font-size:14px;">
                                <i data-lucide="save"></i> Save transaction
                            </button>
                        </form>

                        {{-- Replenishment form --}}
                        <form method="POST" action="{{ route('admin.revolving_fund.replenish') }}" id="replenishForm" style="display:none;">
                            @csrf
                            <div style="display:flex;flex-direction:column;gap:14px;">
                                <div>
                                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.45);display:block;margin-bottom:6px;">Project</label>
                                    <select name="project_id" required style="width:100%;height:44px;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.12);border-radius:10px;color:#fff;padding:0 12px;font-size:13px;font-weight:600;">
                                        <option value="" style="background:#2a2a2a;">Select project</option>
                                        @foreach($projects as $p)
                                        <option value="{{ $p->id }}" style="background:#2a2a2a;">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                    <div>
                                        <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.45);display:block;margin-bottom:6px;">Amount (₱)</label>
                                        <input type="number" name="amount" required min="0.01" step="0.01" placeholder="0.00"
                                            style="width:100%;height:44px;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.12);border-radius:10px;color:#fff;padding:0 12px;font-size:13px;font-weight:600;">
                                    </div>
                                    <div>
                                        <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.45);display:block;margin-bottom:6px;">Date</label>
                                        <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}"
                                            style="width:100%;height:44px;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.12);border-radius:10px;color:#fff;padding:0 12px;font-size:13px;font-weight:600;">
                                    </div>
                                </div>
                                <div>
                                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.45);display:block;margin-bottom:6px;">Reason / Purpose</label>
                                    <input type="text" name="purpose" required maxlength="255" placeholder="e.g. Progress billing received"
                                        style="width:100%;height:44px;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.12);border-radius:10px;color:#fff;padding:0 12px;font-size:13px;font-weight:600;">
                                </div>
                            </div>
                            <button type="submit" class="save-btn" style="width:100%;justify-content:center;margin-top:16px;border-radius:12px;height:46px;font-size:14px;background:#10B981;">
                                <i data-lucide="save"></i> Save transaction
                            </button>
                        </form>
                    </div>

                    <div style="padding:14px 24px 20px;margin-top:4px;">
                        <p style="font-size:11px;color:rgba(255,255,255,.3);line-height:1.6;margin:0;">
                            Every transaction is tagged to a specific project so the owner can trace exactly where the fund was used.
                        </p>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <!-- Initial Fund Setup Modal -->
    <div class="modal-overlay" id="initialFundModal">
        <div class="modal-card" style="max-width:420px;">
            <div class="modal-header">
                <div>
                    <h2>Initial Fund Setup</h2>
                    <p>Set the revolving fund's starting balance.</p>
                </div>
                <button class="modal-close" type="button" id="closeInitialFundModal"><i data-lucide="x"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.revolving_fund.setup_initial') }}">
                @csrf
                <div class="form-group">
                    <label>Initial Balance (₱) </label>
                    <input type="number" name="initial_balance" required min="0" step="0.01" value="{{ $initialBalance }}" placeholder="e.g. 100000">
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelInitialFund">Cancel</button>
                    <button type="submit" class="save-btn"><i data-lucide="save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Type toggle
        function setTxType(type) {
            document.getElementById('drawdownForm').style.display    = type === 'drawdown' ? '' : 'none';
            document.getElementById('replenishForm').style.display   = type === 'replenishment' ? '' : 'none';
            document.querySelectorAll('.rf-type-btn').forEach(function(btn) {
                var isActive = btn.dataset.type === type;
                btn.classList.toggle('active', isActive);
                btn.style.background = isActive ? 'rgba(255,255,255,.15)' : 'transparent';
                btn.style.borderColor = isActive ? 'rgba(255,255,255,.3)' : 'rgba(255,255,255,.08)';
                btn.style.color = isActive ? '#fff' : 'rgba(255,255,255,.45)';
            });
        }

        // Initial fund modal
        document.getElementById('openInitialFundModal').addEventListener('click', function() {
            document.getElementById('initialFundModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        });
        ['closeInitialFundModal','cancelInitialFund'].forEach(function(id) {
            document.getElementById(id).addEventListener('click', function() {
                document.getElementById('initialFundModal').classList.remove('show');
                document.body.style.overflow = '';
            });
        });

        // Header shortcut buttons scroll to forms
        document.getElementById('openDrawdownModal').addEventListener('click', function() {
            setTxType('drawdown');
            document.getElementById('drawdownForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
        document.getElementById('openReplenishModal').addEventListener('click', function() {
            setTxType('replenishment');
            document.getElementById('replenishForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
        });

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

        // Match ledger card height to record card
        function matchLedgerHeight() {
            var recordCard = document.querySelector('.rf-main-grid > div:last-child');
            var ledgerWrapper = document.querySelector('.rf-main-grid .table-wrapper');
            var ledgerCard = document.querySelector('.rf-main-grid .table-card');
            if (!recordCard || !ledgerWrapper || !ledgerCard) return;
            var recordH = recordCard.offsetHeight;
            var toolbarH = ledgerCard.querySelector('.table-toolbar')?.offsetHeight || 60;
            ledgerWrapper.style.maxHeight = (recordH - toolbarH - 40) + 'px';
            ledgerWrapper.style.overflowY = 'auto';
        }
        matchLedgerHeight();
        window.addEventListener('resize', matchLedgerHeight);
    </script>

    <style>
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
        .rf-main-grid {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 20px;
            align-items: stretch;
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

        /* Type toggle buttons */
        .rf-type-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 16px;
            background: var(--cream-soft);
            border: none;
            cursor: pointer;
            color: var(--muted);
            transition: background 0.15s, color 0.15s;
            text-align: left;
        }
        .rf-type-btn:first-child { border-right: 1.5px solid var(--border); }
        .rf-type-btn:hover { background: var(--cream-deep); color: var(--dark); }
        .rf-type-btn.active {
            background: var(--dark);
            color: #fff;
        }
        .rf-type-btn i { width: 20px; height: 20px; flex-shrink: 0; }

        /* Responsive */
        @media (max-width: 1024px) {
            .rf-main-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 540px) {
            .rf-type-btn { padding: 10px 12px; gap: 7px; }
        }

        /* Prevent ledger from scrolling horizontally */
        #fundLedgerTable {
            min-width: 0;
            width: 100%;
            table-layout: fixed;
        }
        #fundLedgerTable th { font-size: 12px; font-weight: 800; padding: 10px 10px; color: var(--dark); }
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
