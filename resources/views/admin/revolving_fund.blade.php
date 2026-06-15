<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revolving Fund | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        #fundTable th.num-cell,
        #fundTable td.num-cell {
            text-align: right;
        }
    </style>
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">
            <div class="page-header">
                <div>
                    <h1>Revolving Fund</h1>
                    <p>Track the company's revolving fund balance, project advances, and automatic replenishments from client payments.</p>
                </div>
                <div style="display:flex;gap:10px;">
                    <button class="add-btn" type="button" id="openInitialFundModal" style="background:var(--dark-soft);">
                        <i data-lucide="settings"></i>
                        Initial Fund Setup
                    </button>
                    <button class="add-btn" type="button" id="openReleaseFundModal">
                        <i data-lucide="send"></i>
                        Release Fund
                    </button>
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

            <!-- Summary Cards -->
            <div class="page-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
                <div class="info-card teal">
                    <div class="info-card-icon teal"><i data-lucide="wallet"></i></div>
                    <h3>Current Fund Balance</h3>
                    <div class="value">₱{{ number_format($currentBalance, 2) }}</div>
                    <div class="info-card-sub">Available revolving fund</div>
                </div>
                <div class="info-card red">
                    <div class="info-card-icon red"><i data-lucide="trending-down"></i></div>
                    <h3>Total Released</h3>
                    <div class="value">₱{{ number_format($totalReleased, 2) }}</div>
                    <div class="info-card-sub">All-time project advances</div>
                </div>
                <div class="info-card green">
                    <div class="info-card-icon green"><i data-lucide="trending-up"></i></div>
                    <h3>Total Replenished</h3>
                    <div class="value">₱{{ number_format($totalReplenished, 2) }}</div>
                    <div class="info-card-sub">All-time replenishments</div>
                </div>
                <div class="info-card orange">
                    <div class="info-card-icon orange"><i data-lucide="folder-kanban"></i></div>
                    <h3>Active Project Advances</h3>
                    <div class="value">{{ $activeAdvances }} {{ $activeAdvances == 1 ? 'Project' : 'Projects' }}</div>
                    <div class="info-card-sub">Projects with outstanding advances</div>
                </div>
            </div>

            <!-- Fund Ledger -->
            <div class="table-card">
                <div class="table-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="fundSearch" placeholder="Search project or purpose...">
                    </div>
                    <div class="filter-group">
                        <select id="fundTypeFilter" class="filter-select">
                            <option value="">All Types</option>
                            <option value="release">Release</option>
                            <option value="replenishment">Replenishment</option>
                        </select>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="fundTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Project</th>
                                <th>Type</th>
                                <th>Purpose</th>
                                <th class="num-cell">Amount</th>
                                <th class="num-cell">Balance After Transaction</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $tx)
                            <tr data-type="{{ $tx->type }}"
                                data-search="{{ strtolower(($tx->purpose ?? $tx->description ?? '') . ' ' . ($tx->project->name ?? '')) }}">
                                <td>{{ \Carbon\Carbon::parse($tx->date)->format('M d, Y') }}</td>
                                <td>
                                    @if($tx->project)
                                        <a href="{{ route('admin.project_view', $tx->project->id) }}" style="color:var(--dark);font-weight:700;text-decoration:none;">
                                            {{ $tx->project->name }}
                                        </a>
                                    @else
                                        <span style="color:var(--muted);">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge {{ $tx->type === 'release' ? 'shortage' : 'completed' }}">
                                        {{ $tx->type === 'release' ? 'Release' : 'Replenishment' }}
                                    </span>
                                </td>
                                <td>{{ $tx->purpose ?? $tx->description ?? '—' }}</td>
                                <td class="num-cell">
                                    <strong style="color:{{ $tx->type === 'release' ? 'var(--danger)' : 'var(--success)' }};">
                                        {{ $tx->type === 'release' ? '-' : '+' }}₱{{ number_format($tx->amount, 2) }}
                                    </strong>
                                </td>
                                <td class="num-cell">{{ $tx->balance_after !== null ? '₱' . number_format($tx->balance_after, 2) : '—' }}</td>
                                <td>
                                    <span class="status-badge {{ $tx->status === 'Completed' ? 'completed' : 'pending' }}">
                                        {{ $tx->status ?? '—' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr id="emptyFundRow">
                                <td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">
                                    No fund transactions recorded yet. Click <strong>Release Fund</strong> to get started.
                                </td>
                            </tr>
                            @endforelse
                            @if($transactions->isNotEmpty())
                            <tr id="noFundMatchRow" style="display:none;">
                                <td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">
                                    No transactions match your search.
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- ==================== RELEASE FUND MODAL ==================== -->
    <div class="modal-overlay" id="releaseFundModal">
        <div class="modal-card" style="max-width:520px;">
            <div class="modal-header">
                <div>
                    <h2>Release Fund</h2>
                    <p>Release revolving fund money to a project. Currently available: ₱{{ number_format($currentBalance, 2) }}</p>
                </div>
                <button class="modal-close" type="button" id="closeReleaseFundModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.revolving_fund.release') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Project <span style="color:var(--danger);">*</span></label>
                        <select name="project_id" required>
                            <option value="">Select project</option>
                            @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form-group-full">
                        <label>Amount (₱) <span style="color:var(--danger);">*</span></label>
                        <input type="number" name="amount" required min="0.01" step="0.01" placeholder="e.g. 50000">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Purpose <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="purpose" required maxlength="255" placeholder="e.g. Purchase of Additional Steel Plates">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Remarks</label>
                        <textarea name="remarks" rows="3" placeholder="Optional notes about this release"></textarea>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelReleaseFund">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="send"></i>
                        Release Fund
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== INITIAL FUND SETUP MODAL ==================== -->
    <div class="modal-overlay" id="initialFundModal">
        <div class="modal-card" style="max-width:420px;">
            <div class="modal-header">
                <div>
                    <h2>Initial Fund Setup</h2>
                    <p>Set the revolving fund's starting balance. Changing this also adjusts the current balance by the difference.</p>
                </div>
                <button class="modal-close" type="button" id="closeInitialFundModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.revolving_fund.setup_initial') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Initial Revolving Fund (₱) <span style="color:var(--danger);">*</span></label>
                        <input type="number" name="initial_balance" required min="0" step="0.01" value="{{ $initialBalance }}" placeholder="e.g. 200000">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelInitialFund">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="save"></i>
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();

            // Release Fund modal
            var releaseModal = document.getElementById('releaseFundModal');

            function openReleaseModal() {
                releaseModal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
            function closeReleaseModal() {
                releaseModal.classList.remove('show');
                document.body.style.overflow = '';
            }

            document.getElementById('openReleaseFundModal').addEventListener('click', openReleaseModal);
            document.getElementById('closeReleaseFundModal').addEventListener('click', closeReleaseModal);
            document.getElementById('cancelReleaseFund').addEventListener('click', closeReleaseModal);
            releaseModal.addEventListener('click', function(e) {
                if (e.target === this) closeReleaseModal();
            });

            // Initial Fund Setup modal
            var initialModal = document.getElementById('initialFundModal');

            function openInitialModal() {
                initialModal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
            function closeInitialModal() {
                initialModal.classList.remove('show');
                document.body.style.overflow = '';
            }

            document.getElementById('openInitialFundModal').addEventListener('click', openInitialModal);
            document.getElementById('closeInitialFundModal').addEventListener('click', closeInitialModal);
            document.getElementById('cancelInitialFund').addEventListener('click', closeInitialModal);
            initialModal.addEventListener('click', function(e) {
                if (e.target === this) closeInitialModal();
            });

            // Search + type filter
            var searchInput = document.getElementById('fundSearch');
            var typeFilter   = document.getElementById('fundTypeFilter');
            var noMatchRow   = document.getElementById('noFundMatchRow');

            function applyFundFilters() {
                var q    = (searchInput.value || '').toLowerCase();
                var type = typeFilter.value;
                var visibleCount = 0;

                document.querySelectorAll('#fundTable tbody tr[data-type]').forEach(function(row) {
                    var matchSearch = !q || (row.dataset.search || '').indexOf(q) !== -1;
                    var matchType   = !type || row.dataset.type === type;
                    var visible     = matchSearch && matchType;
                    row.style.display = visible ? '' : 'none';
                    if (visible) visibleCount++;
                });

                if (noMatchRow) {
                    noMatchRow.style.display = visibleCount === 0 ? '' : 'none';
                }
            }

            if (searchInput) searchInput.addEventListener('input', applyFundFilters);
            if (typeFilter) typeFilter.addEventListener('change', applyFundFilters);

            @if(session('success'))
            closeReleaseModal();
            closeInitialModal();
            @endif
        });
    </script>
</body>
</html>
