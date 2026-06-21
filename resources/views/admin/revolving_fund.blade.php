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
            <div class="table-card" style="margin-bottom:24px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
                    <span style="font-size:14px;font-weight:800;color:var(--dark);">Fund balance tracker</span>
                    <span style="font-size:12px;color:var(--muted);font-weight:600;">Replenishes when progress billings are paid</span>
                </div>

                @php
                    $barPct = $initialBalance > 0 ? min(100, round(($currentBalance / $initialBalance) * 100)) : 0;
                    $barColor = $barPct >= 60 ? '#16a34a' : ($barPct >= 30 ? '#e8900a' : '#ef4444');
                @endphp

                <!-- Balance bar -->
                <div style="display:flex;justify-content:space-between;font-size:11.5px;font-weight:700;color:var(--muted);margin-bottom:6px;">
                    <span>₱0</span>
                    <span style="color:{{ $barColor }};">{{ $barPct < 30 ? 'Low — ' : '' }}₱{{ number_format($currentBalance, 2) }} remaining</span>
                    <span>₱{{ number_format($initialBalance, 2) }}</span>
                </div>
                <div style="height:12px;background:var(--cream-deep);border-radius:999px;overflow:hidden;margin-bottom:20px;">
                    <div style="height:100%;width:{{ $barPct }}%;background:{{ $barColor }};border-radius:999px;transition:width 0.5s;"></div>
                </div>

                {{-- Per-project outstanding --}}
                @if($projectOutstandings->isNotEmpty())
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
                    @foreach($projectOutstandings as $pd)
                    <div style="background:var(--cream-soft);border:1px solid var(--border);border-radius:12px;padding:14px 16px;">
                        <div style="font-size:13px;font-weight:800;color:var(--dark);margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $pd['project']?->name ?? '—' }}
                            @if($pd['project']?->client)
                                <span style="color:var(--muted);font-weight:600;"> — {{ $pd['project']->client }}</span>
                            @endif
                        </div>
                        <div style="font-size:15px;font-weight:900;color:var(--danger);">₱{{ number_format($pd['outstanding'], 2) }} drawn</div>
                        <div style="font-size:11.5px;color:var(--muted);margin-top:3px;">
                            {{ $pd['latest_tx']?->purpose ?? 'Outstanding advance' }}
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p style="font-size:13px;color:var(--muted);text-align:center;padding:8px 0;">No outstanding project advances.</p>
                @endif
            </div>
            @endif

            {{-- Ledger + Record Transaction --}}
            <div class="rf-main-grid">

                <!-- Transaction Ledger -->
                <div class="table-card" style="margin-bottom:0;">
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

                    <div style="display:flex;flex-direction:column;gap:0;max-height:520px;overflow-y:auto;">
                        @forelse($transactions as $tx)
                        <div class="rf-tx-row" data-type="{{ $tx->type }}"
                             data-search="{{ strtolower(($tx->purpose ?? $tx->description ?? '') . ' ' . ($tx->project->name ?? '')) }}">
                            <div class="rf-tx-dot {{ $tx->type === 'release' ? 'red' : 'green' }}"></div>
                            <div class="rf-tx-body">
                                <div class="rf-tx-title">
                                    {{ $tx->purpose ?? $tx->description ?? '—' }}
                                    @if($tx->project)
                                    <span style="color:var(--muted);font-weight:600;"> — {{ $tx->project->name }}</span>
                                    @endif
                                </div>
                                <div class="rf-tx-meta">
                                    <span class="status-badge {{ $tx->type === 'release' ? 'shortage' : 'completed' }}" style="font-size:10.5px;padding:2px 8px;">
                                        {{ $tx->type === 'release' ? 'Drawdown' : 'Replenishment' }}
                                    </span>
                                    <span>{{ \Carbon\Carbon::parse($tx->date)->format('M d, Y') }}</span>
                                </div>
                            </div>
                            <div class="rf-tx-amount {{ $tx->type === 'release' ? 'red' : 'green' }}">
                                {{ $tx->type === 'release' ? '-' : '+' }}₱{{ number_format($tx->amount, 2) }}
                            </div>
                        </div>
                        @empty
                        <div style="text-align:center;padding:48px 20px;color:var(--muted);font-size:14px;">
                            No transactions recorded yet.
                        </div>
                        @endforelse
                        <div id="noFundMatchRow" style="display:none;text-align:center;padding:32px;color:var(--muted);font-size:14px;">
                            No transactions match your search.
                        </div>
                    </div>
                </div>

                <!-- Record a transaction -->
                <div class="table-card" style="margin-bottom:0;align-self:start;">
                    <div style="font-size:14px;font-weight:800;color:var(--dark);margin-bottom:16px;">Record a transaction</div>

                    {{-- Type toggle --}}
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;border:1.5px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:18px;">
                        <button type="button" class="rf-type-btn active" data-type="drawdown" onclick="setTxType('drawdown')">
                            <i data-lucide="arrow-up-right"></i>
                            <div>
                                <div style="font-weight:800;font-size:13px;">Drawdown</div>
                                <div style="font-size:11px;opacity:.7;">Money going out</div>
                            </div>
                        </button>
                        <button type="button" class="rf-type-btn" data-type="replenishment" onclick="setTxType('replenishment')">
                            <i data-lucide="arrow-down-left"></i>
                            <div>
                                <div style="font-weight:800;font-size:13px;">Replenishment</div>
                                <div style="font-size:11px;opacity:.7;">Money coming in</div>
                            </div>
                        </button>
                    </div>

                    {{-- Drawdown form --}}
                    <form method="POST" action="{{ route('admin.revolving_fund.release') }}" id="drawdownForm">
                        @csrf
                        <div class="form-grid">
                            <div class="form-group form-group-full">
                                <label>Project <span style="color:var(--danger);">*</span></label>
                                <select name="project_id" required>
                                    <option value="">Select project</option>
                                    @foreach($projects as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Amount (₱) <span style="color:var(--danger);">*</span></label>
                                <input type="number" name="amount" required min="0.01" step="0.01" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label>Date</label>
                                <input type="date" name="date" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="form-group form-group-full">
                                <label>Reason / Purpose <span style="color:var(--danger);">*</span></label>
                                <input type="text" name="purpose" required maxlength="255" placeholder="e.g. Purchase of steel plates">
                            </div>
                        </div>
                        <button type="submit" class="save-btn" style="width:100%;justify-content:center;margin-top:4px;">
                            <i data-lucide="save"></i> Save transaction
                        </button>
                    </form>

                    {{-- Replenishment form --}}
                    <form method="POST" action="{{ route('admin.revolving_fund.replenish') }}" id="replenishForm" style="display:none;">
                        @csrf
                        <div class="form-grid">
                            <div class="form-group form-group-full">
                                <label>Project <span style="color:var(--danger);">*</span></label>
                                <select name="project_id" required>
                                    <option value="">Select project</option>
                                    @foreach($projects as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Amount (₱) <span style="color:var(--danger);">*</span></label>
                                <input type="number" name="amount" required min="0.01" step="0.01" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label>Date <span style="color:var(--danger);">*</span></label>
                                <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="form-group form-group-full">
                                <label>Reason / Purpose <span style="color:var(--danger);">*</span></label>
                                <input type="text" name="purpose" required maxlength="255" placeholder="e.g. Progress billing received">
                            </div>
                        </div>
                        <button type="submit" class="save-btn" style="width:100%;justify-content:center;margin-top:4px;background:#16a34a;">
                            <i data-lucide="save"></i> Save transaction
                        </button>
                    </form>

                    <p style="font-size:11.5px;color:var(--muted);margin-top:14px;line-height:1.6;border-top:1px solid var(--border);padding-top:12px;">
                        Every transaction is tagged to a specific project so the owner can trace exactly where the fund was used, even though the fund itself is shared across all projects.
                    </p>
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
                    <label>Initial Balance (₱) <span style="color:var(--danger);">*</span></label>
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
                btn.classList.toggle('active', btn.dataset.type === type);
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
            document.querySelectorAll('.rf-tx-row').forEach(function(row) {
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
            align-items: start;
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
    </style>
</body>
</html>
