<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Dashboard | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* Tabs (top-level + modal) reuse .filter-tabs/.filter-tab; header reuses .page-title/.page-subtitle;
           buttons reuse .add-btn/.cancel-btn/.save-btn; modal reuses .modal-overlay/.modal-card/.form-group —
           only the pieces with no existing equivalent (cards, insight box, chips, progress bar) are custom here. */

        .kd-header-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; flex-wrap: wrap; }
        .kd-header-actions .cancel-btn,
        .kd-header-actions .add-btn { height: 44px; padding-top: 0; padding-bottom: 0; }
        .kd-header-actions-divider { width: 1px; height: 24px; background: var(--border); flex-shrink: 0; }

        /* Quarter + year merged into one bordered control */
        .kd-period-picker {
            display: flex;
            align-items: center;
            height: 44px;
            border: 1px solid var(--border);
            background: var(--cream-soft);
            border-radius: 14px;
            overflow: hidden;
        }
        .kd-period-select {
            height: 100%;
            border: none;
            background: transparent;
            color: var(--dark);
            padding: 0 14px;
            font-size: 13px;
            font-weight: 700;
            outline: none;
            cursor: pointer;
        }
        .kd-period-divider { width: 1px; height: 22px; background: var(--border); flex-shrink: 0; }

        .kd-panel { display: none; }
        .kd-panel.active { display: block; }

        /* Insight box */
        .kd-insight {
            background: linear-gradient(135deg, var(--cream-soft), var(--white));
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 22px;
            font-size: 13.5px;
            line-height: 1.75;
            color: var(--dark);
        }
        .kd-insight .kd-insight-action { color: var(--info); font-weight: 700; margin-top: 6px; display: block; }

        /* KPI Cards */
        .kd-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 22px; }
        .kd-card {
            background: linear-gradient(180deg, var(--white) 0%, #fafafa 100%);
            border: 1px solid var(--border);
            border-radius: 22px;
            box-shadow: 0 14px 30px var(--shadow);
            padding: 20px 22px;
            display: flex; flex-direction: column;
        }
        .kd-card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; gap: 8px; }
        .kd-card-name { font-size: 14px; font-weight: 800; color: var(--dark); }

        .kd-primary { font-size: 32px; font-weight: 900; color: var(--dark); line-height: 1.1; }
        .kd-secondary { font-size: 12.5px; color: var(--muted); margin-top: 5px; }

        .kd-target-row { display: flex; justify-content: space-between; align-items: center; margin-top: 16px; font-size: 12.5px; }
        .kd-target-label { color: var(--muted); }
        .kd-target-value { font-weight: 800; color: var(--dark); }

        .kd-variance { font-size: 12.5px; font-weight: 700; margin-top: 6px; }
        .kd-variance.good { color: var(--success); }
        .kd-variance.bad { color: var(--warning); }

        .kd-progress-track { height: 6px; background: var(--cream-deep); border-radius: 999px; margin-top: 10px; overflow: hidden; }
        .kd-progress-fill { height: 100%; border-radius: 999px; transition: width .5s ease; }

        .kd-scale-divider { height: 1px; background: var(--border); margin: 16px 0 12px; }
        .kd-scale-label { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin-bottom: 8px; }
        .kd-scale-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        .kd-breakdown { margin-top: auto; padding-top: 12px; }
        .kd-breakdown-row { display: flex; justify-content: space-between; align-items: center; font-size: 12px; padding-bottom: 8px; margin-bottom: 8px; border-bottom: 1px solid var(--border); }
        .kd-breakdown-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .kd-breakdown-label { color: var(--muted); }
        .kd-breakdown-value { font-weight: 700; color: var(--dark); }
        .kd-breakdown-value.good { color: var(--success); }
        .kd-breakdown-value.bad { color: var(--danger); }
        .kd-breakdown-value.warn { color: var(--warning); }

        /* Chart panels */
        .kd-chart-card {
            background: linear-gradient(180deg, var(--white) 0%, #fafafa 100%);
            border: 1px solid var(--border);
            border-radius: 22px;
            box-shadow: 0 14px 30px var(--shadow);
            padding: 20px 22px;
            margin-bottom: 16px;
        }
        .kd-chart-title { font-size: 14px; font-weight: 800; color: var(--dark); margin-bottom: 4px; }
        .kd-chart-legend { display: flex; gap: 14px; font-size: 11.5px; color: var(--muted); margin-bottom: 12px; }
        .kd-chart-legend span { display: inline-flex; align-items: center; gap: 5px; }
        .kd-legend-swatch { width: 10px; height: 10px; border-radius: 3px; display: inline-block; }

        .kd-trend-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        @media (max-width: 900px) { .kd-trend-grid { grid-template-columns: 1fr; } }

        .kd-placeholder { text-align: center; padding: 80px 20px; color: var(--muted); }
        .kd-placeholder i { width: 48px; height: 48px; opacity: .3; display: block; margin: 0 auto 16px; }

        @media (max-width: 900px) {
            .kd-cards { grid-template-columns: 1fr; }
        }

        #kdTargetsModal .modal-header { margin-bottom: 18px; }
        #kdTargetsModal .kd-modal-form-panel .form-group { margin-bottom: 10px; }
    </style>
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            <div class="page-header">
                <div>
                    <h1 class="page-title">KPI dashboard</h1>
                    <p class="page-subtitle kd-subtitle">GMD South Phils Metal Fabrication Works</p>
                </div>
                <div class="kd-header-actions">
                    <div class="kd-period-picker">
                        <select class="kd-period-select" id="kdQuarterSelect" style="min-width:60px;">
                            <option value="1">Q1</option>
                            <option value="2">Q2</option>
                            <option value="3">Q3</option>
                            <option value="4">Q4</option>
                        </select>
                        <div class="kd-period-divider"></div>
                        <select class="kd-period-select" id="kdYearSelect" style="min-width:80px;"></select>
                    </div>
                    <div class="kd-header-actions-divider"></div>
                    <button type="button" class="cancel-btn" id="kdOpenReportBtn">
                        <i data-lucide="file-text"></i> Generate report
                    </button>
                    <button type="button" class="add-btn" id="kdOpenTargetsBtn">
                        <i data-lucide="settings-2"></i> Set targets
                    </button>
                </div>
            </div>

            <div class="filter-tabs" style="width:fit-content;margin-bottom:20px;">
                <button type="button" class="filter-tab active" data-tab="scorecard">KPI scorecard</button>
                <button type="button" class="filter-tab" data-tab="trend">Performance trend</button>
                <button type="button" class="filter-tab" data-tab="sma-forecast">SMA forecast</button>
            </div>

            {{-- ── KPI SCORECARD ── --}}
            <div class="kd-panel active" data-panel="scorecard">
                <div class="kd-insight" id="kdScorecardInsight"></div>
                <div class="kd-cards" id="kdCards"></div>
                <div class="kd-chart-card">
                    <div class="kd-chart-title">KPI target vs actual — comparative view</div>
                    <div class="kd-chart-legend">
                        <span><span class="kd-legend-swatch" style="background:#2A4EAA;"></span>Target</span>
                        <span><span class="kd-legend-swatch" style="background:#207A3A;"></span>Actual</span>
                    </div>
                    <canvas id="kdComparativeChart" height="90"></canvas>
                </div>
            </div>

            {{-- ── PERFORMANCE TREND ── --}}
            <div class="kd-panel" data-panel="trend">
                <div class="kd-insight" id="kdTrendInsight"></div>
                <div class="kd-trend-grid">
                    <div class="kd-chart-card" style="margin-bottom:0;">
                        <div class="kd-chart-title">Net profit trend (₱)</div>
                        <canvas id="kdProfitTrendChart" height="200"></canvas>
                    </div>
                    <div class="kd-chart-card" style="margin-bottom:0;">
                        <div class="kd-chart-title">On-time delivery trend (projects)</div>
                        <canvas id="kdOnTimeTrendChart" height="200"></canvas>
                    </div>
                </div>
                <div class="kd-chart-card">
                    <div class="kd-chart-title">Budget adherence trend (%)</div>
                    <canvas id="kdBudgetTrendChart" height="110"></canvas>
                </div>
            </div>

            {{-- ── SMA FORECAST ── --}}
            <div class="kd-panel" data-panel="sma-forecast">
                <div class="kd-insight" id="kdForecastInsight"></div>
                <div class="kd-cards" id="kdForecastCards"></div>
                <div class="kd-chart-card" id="kdForecastChartCard">
                    <div class="kd-chart-title">Last actual quarter vs. next quarter forecast</div>
                    <div class="kd-chart-legend">
                        <span><span class="kd-legend-swatch" style="background:#2A4EAA;"></span>Last actual</span>
                        <span><span class="kd-legend-swatch" style="background:#207A3A;"></span>SMA forecast</span>
                    </div>
                    <canvas id="kdForecastChart" height="90"></canvas>
                </div>
            </div>

        </main>
    </div>

    {{-- ── GENERATE REPORT MODAL (placed outside .admin-content — see note on the modal below) ── --}}
    <div class="modal-overlay" id="kdReportModal">
        <div class="modal-card" style="max-width:460px;">
            <div class="modal-header">
                <div>
                    <h2>Generate report</h2>
                    <p>Pick a quarter range. Opens a printable report you can save as PDF.</p>
                </div>
                <button class="modal-close" type="button" id="kdCloseReportModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="form-group">
                <label>From</label>
                <div style="display:flex;gap:10px;">
                    <select class="filter-select" id="kdReportFromQuarter" style="flex:1;">
                        <option value="1">Q1</option>
                        <option value="2">Q2</option>
                        <option value="3">Q3</option>
                        <option value="4">Q4</option>
                    </select>
                    <select class="filter-select" id="kdReportFromYear" style="flex:1;"></select>
                </div>
            </div>
            <div class="form-group">
                <label>To</label>
                <div style="display:flex;gap:10px;">
                    <select class="filter-select" id="kdReportToQuarter" style="flex:1;">
                        <option value="1">Q1</option>
                        <option value="2">Q2</option>
                        <option value="3">Q3</option>
                        <option value="4">Q4</option>
                    </select>
                    <select class="filter-select" id="kdReportToYear" style="flex:1;"></select>
                </div>
            </div>
            <p id="kdReportError" style="display:none;font-size:12.5px;font-weight:700;color:var(--danger);margin:-6px 0 14px;"></p>

            <div class="modal-actions">
                <button type="button" class="cancel-btn" id="kdCancelReport">Cancel</button>
                <button type="button" class="save-btn" id="kdGenerateReportBtn"><i data-lucide="file-text"></i> Generate</button>
            </div>
        </div>
    </div>

    {{-- ── SET TARGETS MODAL ──
         Placed outside .admin-content on purpose: that element carries a page-load entrance
         animation (transform-based) which, per CSS spec, would otherwise become the containing
         block for this modal's position:fixed overlay instead of the real viewport — clipping it
         on shorter screens. Every other modal in this app is placed here for the same reason. --}}
    <div class="modal-overlay" id="kdTargetsModal">
        <div class="modal-card" style="max-width:460px;">
            <div class="modal-header">
                <div>
                    <h2>Set KPI targets</h2>
                    <p>Set targets for this quarter. The system measures actual performance against these targets.</p>
                </div>
                <button class="modal-close" type="button" id="kdCloseTargetsModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="kd-finalized-note" id="kdFinalizedNote" style="display:none;font-size:12px;font-weight:700;color:var(--warning);background:#FFF3D6;border:1px solid rgba(138,97,0,.2);border-radius:10px;padding:8px 12px;margin-bottom:14px;"></div>
            <div class="form-group">
                <label id="kdModalPeriodLabel">Profit target per quarter (₱)</label>
                <input type="number" min="0" step="1" id="kdInputProfitTarget">
            </div>
            <div class="form-group">
                <label>On-time delivery target (projects)</label>
                <input type="number" min="0" step="1" id="kdInputOnTimeTarget">
            </div>
            <div class="modal-actions">
                <button type="button" class="cancel-btn" id="kdCancelQuarterTargets">Cancel</button>
                <button type="button" class="save-btn" id="kdSaveQuarterTargets"><i data-lucide="save"></i> Save targets</button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
    (function () {
        var KPI_DATA_URL          = "{{ route('admin.kpi_dashboard.data') }}";
        var REPORT_RANGE_URL      = "{{ route('admin.kpi_dashboard.report_range') }}";
        var SAVE_QUARTER_URL      = "{{ route('admin.kpi_dashboard.save_quarter_targets') }}";
        var CSRF_TOKEN            = "{{ csrf_token() }}";

        var STATE = {
            payload: @json($initialData),
        };

        var charts = {};

        // Industry scale benchmarks are hidden for now until the sourcing is confirmed —
        // flip this back to true to bring the "Industry scale" chip back on each KPI card.
        var SHOW_INDUSTRY_SCALE = false;

        function fmtPeso(n) {
            n = Number(n) || 0;
            var sign = n < 0 ? '-' : '';
            return sign + '₱' + Math.abs(Math.round(n)).toLocaleString('en-PH');
        }
        function fmtPct(n) {
            return (Number(n) || 0).toFixed(1) + '%';
        }
        function pluralize(n, word) {
            return n + ' ' + word + (n === 1 ? '' : 's');
        }
        function toneChipClass(tone) {
            if (tone === 'success' || tone === 'info') return 'icon-chip-info';
            if (tone === 'warning') return 'icon-chip-warning';
            if (tone === 'danger') return 'icon-chip-danger';
            return 'icon-chip-neutral';
        }

        /* ── Year / Quarter dropdowns ── */
        function renderPeriodOptions() {
            var yearSel = document.getElementById('kdYearSelect');
            yearSel.innerHTML = '';
            (STATE.payload.availableYears || [STATE.payload.year]).forEach(function (y) {
                var opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                if (y === STATE.payload.year) opt.selected = true;
                yearSel.appendChild(opt);
            });

            document.getElementById('kdQuarterSelect').value = STATE.payload.quarter;
        }

        function loadPeriod(year, quarter) {
            fetch(KPI_DATA_URL + '?year=' + year + '&quarter=' + quarter, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function (r) { return r.json(); })
            .then(function (payload) {
                STATE.payload = payload;
                renderPeriodOptions();
                renderEverything();
            });
        }

        function currentSelectedPeriod() {
            return {
                year: parseInt(document.getElementById('kdYearSelect').value, 10),
                quarter: parseInt(document.getElementById('kdQuarterSelect').value, 10)
            };
        }

        document.getElementById('kdYearSelect').addEventListener('change', function () {
            var p = currentSelectedPeriod();
            loadPeriod(p.year, p.quarter);
        });
        document.getElementById('kdQuarterSelect').addEventListener('change', function () {
            var p = currentSelectedPeriod();
            loadPeriod(p.year, p.quarter);
        });

        /* ── Top-level tabs ── */
        document.querySelectorAll('.filter-tab[data-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.filter-tab[data-tab]').forEach(function (b) { b.classList.remove('active'); });
                document.querySelectorAll('.kd-panel').forEach(function (p) { p.classList.remove('active'); });
                this.classList.add('active');
                document.querySelector('.kd-panel[data-panel="' + this.dataset.tab + '"]').classList.add('active');
            });
        });

        /* ── KPI Cards ── */
        function statusChip(hit) {
            if (hit === null || hit === undefined) {
                return '<span class="icon-chip-neutral" style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:800;padding:4px 10px;border-radius:999px;white-space:nowrap;"><i data-lucide="minus-circle" style="width:12px;height:12px;"></i>No target set</span>';
            }
            return hit
                ? '<span class="icon-chip-success" style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:800;padding:4px 10px;border-radius:999px;white-space:nowrap;"><i data-lucide="check-circle-2" style="width:12px;height:12px;"></i>Target hit</span>'
                : '<span class="icon-chip-warning" style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:800;padding:4px 10px;border-radius:999px;white-space:nowrap;"><i data-lucide="alert-triangle" style="width:12px;height:12px;"></i>Below target</span>';
        }

        function scaleBlock(scale) {
            if (!SHOW_INDUSTRY_SCALE) return '';
            return '<div class="kd-scale-divider"></div>' +
                '<div class="kd-scale-label">Industry scale</div>' +
                '<div class="kd-scale-row">' +
                    '<span class="' + toneChipClass(scale.tone) + '" style="font-size:11px;font-weight:800;padding:4px 10px;border-radius:999px;white-space:nowrap;">' + scale.label + ' (' + scale.range + ')</span>' +
                '</div>';
        }

        function breakdownBlock(rows) {
            var html = '<div class="kd-scale-divider"></div><div class="kd-breakdown">';
            rows.forEach(function (r) {
                html += '<div class="kd-breakdown-row">' +
                    '<span class="kd-breakdown-label">' + r.label + '</span>' +
                    '<span class="kd-breakdown-value' + (r.tone ? ' ' + r.tone : '') + '">' + r.value + '</span>' +
                '</div>';
            });
            return html + '</div>';
        }

        function profitBreakdown(p) {
            return breakdownBlock([
                { label: 'Revenue received', value: fmtPeso(p.revenue) },
                { label: 'Material cost',    value: fmtPeso(p.mat_cost) },
                { label: 'Labor cost',       value: fmtPeso(p.labor_cost) },
                { label: 'Net profit',       value: fmtPeso(p.net_profit), tone: 'good' },
            ]);
        }

        function onTimeBreakdown(o) {
            return breakdownBlock([
                { label: 'Total projects',      value: pluralize(o.total_completed, 'project') },
                { label: 'Delivered on time',   value: pluralize(o.on_time_count, 'project'), tone: 'good' },
                { label: 'Delayed',             value: pluralize(o.delayed_count, 'project'), tone: o.delayed_count > 0 ? 'bad' : undefined },
                { label: 'Avg delay (delayed)', value: o.avg_delay_days > 0 ? '~' + o.avg_delay_days + ' days' : '—', tone: o.avg_delay_days > 0 ? 'warn' : undefined },
            ]);
        }

        function budgetBreakdown(b) {
            return breakdownBlock([
                { label: 'Total contracted',      value: fmtPeso(b.total_contracted) },
                { label: 'Actual spend',          value: fmtPeso(b.actual_cost) },
                { label: 'Net savings',           value: fmtPeso(b.net_savings), tone: b.net_savings >= 0 ? 'good' : 'bad' },
                { label: 'Projects over budget',  value: b.over_budget_count + ' of ' + b.total_completed },
            ]);
        }

        function progressBar(pct, hit, explicitColor) {
            if (explicitColor) {
                return '<div class="kd-progress-track"><div class="kd-progress-fill" style="width:' + Math.max(0, Math.min(100, pct)) + '%;background:' + explicitColor + ';"></div></div>';
            }
            if (hit === null || hit === undefined) {
                return '<div class="kd-progress-track"></div>';
            }
            var color = hit ? '#207A3A' : '#8A6100';
            return '<div class="kd-progress-track"><div class="kd-progress-fill" style="width:' + Math.max(0, Math.min(100, pct)) + '%;background:' + color + ';"></div></div>';
        }

        /* Fixed benchmark range for the Budget Adherence status chip — no longer tied to an
           owner-set target. Ranges per spec: >110% over, 101-110% slightly over, 90-100% within,
           80-89% under, <80% significantly under. */
        function budgetRangeStatus(rate) {
            if (rate > 110) return { label: 'Over budget',               bg: '#FEE4E2', color: '#B42318' };
            if (rate > 100) return { label: 'Slightly over budget',      bg: '#FFEDD5', color: '#C2410C' };
            if (rate >= 90) return { label: 'Within budget',             bg: '#E7F6EC', color: '#207A3A' };
            if (rate >= 80) return { label: 'Under budget',              bg: '#FEF9C3', color: '#A16207' };
            return                 { label: 'Significantly under budget', bg: '#EAF0FF', color: '#2A4EAA' };
        }

        function targetRow(label, value) {
            return '<div class="kd-target-row"><span class="kd-target-label">Owner target</span><span class="kd-target-value">' + (value === null ? 'Not set' : value) + '</span></div>';
        }

        function profitCard(p) {
            if (!p.has_target) {
                return '<div class="kd-card">' +
                    '<div class="kd-card-top"><span class="kd-card-name">Project profit margin</span>' + statusChip(null) + '</div>' +
                    '<div class="kd-primary">' + fmtPeso(p.net_profit) + '</div>' +
                    '<div class="kd-secondary">' + fmtPct(p.avg_margin) + ' avg profit margin</div>' +
                    targetRow('Owner target', null) +
                    '<div class="kd-variance">No target set for this quarter yet.</div>' +
                    progressBar(0, null) +
                    scaleBlock(p.scale) +
                    profitBreakdown(p) +
                    '</div>';
            }
            var varianceText = p.variance >= 0
                ? '+' + fmtPeso(p.variance) + ' above target'
                : fmtPeso(Math.abs(p.variance)) + ' below target';
            return '<div class="kd-card">' +
                '<div class="kd-card-top"><span class="kd-card-name">Project profit margin</span>' + statusChip(p.hit) + '</div>' +
                '<div class="kd-primary">' + fmtPeso(p.net_profit) + '</div>' +
                '<div class="kd-secondary">' + fmtPct(p.avg_margin) + ' avg profit margin</div>' +
                targetRow('Owner target', fmtPeso(p.target)) +
                '<div class="kd-variance ' + (p.hit ? 'good' : 'bad') + '">' + varianceText + '</div>' +
                progressBar(p.progress_pct, p.hit) +
                scaleBlock(p.scale) +
                profitBreakdown(p) +
                '</div>';
        }

        function onTimeCard(o) {
            if (!o.has_target) {
                return '<div class="kd-card">' +
                    '<div class="kd-card-top"><span class="kd-card-name">On-time delivery</span>' + statusChip(null) + '</div>' +
                    '<div class="kd-primary">' + o.on_time_count + ' of ' + o.total_completed + ' projects</div>' +
                    '<div class="kd-secondary">' + fmtPct(o.rate) + ' on-time delivery rate</div>' +
                    targetRow('Owner target', null) +
                    '<div class="kd-variance">No target set for this quarter yet.</div>' +
                    progressBar(0, null) +
                    scaleBlock(o.scale) +
                    onTimeBreakdown(o) +
                    '</div>';
            }
            var varianceText;
            if (o.variance >= 0) {
                varianceText = o.variance === 0 ? 'Meets target exactly' : '+' + pluralize(o.variance, 'project') + ' above target';
            } else {
                varianceText = pluralize(Math.abs(o.variance), 'project') + ' short of target';
            }
            return '<div class="kd-card">' +
                '<div class="kd-card-top"><span class="kd-card-name">On-time delivery</span>' + statusChip(o.hit) + '</div>' +
                '<div class="kd-primary">' + o.on_time_count + ' of ' + o.target + ' projects</div>' +
                '<div class="kd-secondary">' + fmtPct(o.rate) + ' on-time delivery rate</div>' +
                targetRow('Owner target', pluralize(o.target, 'project')) +
                '<div class="kd-variance ' + (o.hit ? 'good' : 'bad') + '">' + varianceText + '</div>' +
                progressBar(o.progress_pct, o.hit) +
                scaleBlock(o.scale) +
                onTimeBreakdown(o) +
                '</div>';
        }

        function budgetCard(b) {
            var status = budgetRangeStatus(b.adherence_rate);
            var chip = '<span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:800;padding:4px 10px;border-radius:999px;white-space:nowrap;background:' +
                status.bg + ';color:' + status.color + ';">' + status.label + '</span>';

            return '<div class="kd-card">' +
                '<div class="kd-card-top"><span class="kd-card-name">Budget adherence</span>' + chip + '</div>' +
                '<div class="kd-primary">' + fmtPct(b.adherence_rate) + '</div>' +
                '<div class="kd-secondary">' + fmtPeso(b.actual_cost) + ' actual vs ' + fmtPeso(b.estimated_budget) + ' estimated</div>' +
                progressBar(Math.min(100, b.adherence_rate), null, status.color) +
                scaleBlock(b.scale) +
                budgetBreakdown(b) +
                '</div>';
        }

        function renderCards(sc) {
            document.getElementById('kdCards').innerHTML =
                profitCard(sc.profit) + onTimeCard(sc.on_time) + budgetCard(sc.budget);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        /* ── Scorecard insight ── */
        function renderScorecardInsight(sc) {
            if (!sc.profit.has_target) {
                document.getElementById('kdScorecardInsight').innerHTML =
                    '<div>No KPI targets have been set for ' + sc.label + ' yet, so performance can\'t be measured against a goal.</div>' +
                    '<span class="kd-insight-action">→ Click "Set targets" above to define a profit and on-time delivery goal for this quarter.</span>';
                return;
            }

            var parts = [];
            parts.push('Your profit target was ' + (sc.profit.hit ? 'hit' : 'missed') + ' this quarter with a net profit of ' +
                fmtPeso(sc.profit.net_profit) + ' against a target of ' + fmtPeso(sc.profit.target) + '.');

            if (sc.on_time.hit) {
                parts.push('On-time delivery met the target at ' + sc.on_time.on_time_count + ' of ' + sc.on_time.target + ' projects.');
            } else {
                var short = sc.on_time.target - sc.on_time.on_time_count;
                parts.push('On-time delivery fell short by ' + pluralize(short, 'project') + '.');
            }

            var budgetStatus = budgetRangeStatus(sc.budget.adherence_rate);
            parts.push('Budget adherence is at ' + fmtPct(sc.budget.adherence_rate) + ' (' +
                fmtPeso(sc.budget.actual_cost) + ' actual vs ' + fmtPeso(sc.budget.estimated_budget) + ' estimated) — ' +
                budgetStatus.label.toLowerCase() + '.');

            var action;
            if (!sc.on_time.hit && sc.on_time.delayed_projects && sc.on_time.delayed_projects.length) {
                action = 'Review which phases caused delays in ' + sc.on_time.delayed_projects.join(' and ') + '. Adjust duration estimates for similar projects next quarter.';
            } else if (!sc.profit.hit) {
                action = 'Review material and labor costing on recent quotations to recover margin next quarter.';
            } else if (sc.budget.adherence_rate > 110) {
                action = 'Costs are significantly exceeding estimates — review BOM pricing and vendor quotes before the next quotation cycle.';
            } else if (sc.budget.adherence_rate > 100) {
                action = 'Tighten BOM estimates — actual spend is running ahead of the estimated budget.';
            } else if (sc.budget.adherence_rate < 80) {
                action = 'Actual spend is running well under estimate — review whether quotations are being priced too conservatively.';
            } else {
                action = 'All targets are on track — maintain current cost discipline and delivery cadence into next quarter.';
            }

            document.getElementById('kdScorecardInsight').innerHTML =
                '<div>' + parts.join(' ') + '</div><span class="kd-insight-action">→ ' + action + '</span>';
        }

        /* ── Comparative bar chart (scorecard tab) ── */
        function renderComparativeChart(sc) {
            var el = document.getElementById('kdComparativeChart');
            var wrapper = el ? el.closest('.kd-chart-card') : null;
            if (!el || typeof Chart === 'undefined') return;
            if (charts.comparative) { charts.comparative.destroy(); charts.comparative = null; }

            if (!sc.profit.has_target) {
                el.style.display = 'none';
                if (!document.getElementById('kdComparativeNoTarget') && wrapper) {
                    var msg = document.createElement('p');
                    msg.id = 'kdComparativeNoTarget';
                    msg.style.cssText = 'text-align:center;color:var(--muted);font-size:13px;padding:40px 0;margin:0;';
                    msg.textContent = 'No targets set for ' + sc.label + ' — nothing to compare yet.';
                    wrapper.appendChild(msg);
                }
                return;
            }

            var existingMsg = document.getElementById('kdComparativeNoTarget');
            if (existingMsg) existingMsg.remove();
            el.style.display = '';

            charts.comparative = new Chart(el, {
                type: 'bar',
                data: {
                    labels: ['Profit (₱ thousands)', 'On-time delivery (projects)', 'Budget adherence (%)'],
                    datasets: [
                        { label: 'Target', data: [sc.profit.target / 1000, sc.on_time.target, null], backgroundColor: '#2A4EAA', borderRadius: 4 },
                        { label: 'Actual', data: [sc.profit.net_profit / 1000, sc.on_time.on_time_count, sc.budget.adherence_rate], backgroundColor: '#207A3A', borderRadius: 4 },
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#666666', font: { size: 11 } } },
                        y: { grid: { color: 'rgba(0,0,0,.05)' }, ticks: { color: '#666666', font: { size: 11 } } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }

        /* ── Performance Trend ── */
        function renderTrendCharts(trend) {
            var labels = trend.map(function (t) { return t.label; });

            ['profitTrend', 'onTimeTrend', 'budgetTrend'].forEach(function (key) {
                if (charts[key]) charts[key].destroy();
            });

            var profitEl = document.getElementById('kdProfitTrendChart');
            if (profitEl && typeof Chart !== 'undefined') {
                charts.profitTrend = new Chart(profitEl, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            { label: 'Actual', data: trend.map(function (t) { return t.profit.net_profit; }), borderColor: '#207A3A', backgroundColor: 'rgba(32,122,58,.10)', fill: true, tension: 0.35, pointRadius: 4, borderWidth: 2.5 },
                            { label: 'Target', data: trend.map(function (t) { return t.profit.target; }), borderColor: '#2A4EAA', borderDash: [6, 4], tension: 0.35, pointRadius: 3, borderWidth: 2 }
                        ]
                    },
                    options: trendOptions(function (v) { return '₱' + Math.round(v / 1000) + 'k'; })
                });
            }

            var onTimeEl = document.getElementById('kdOnTimeTrendChart');
            if (onTimeEl && typeof Chart !== 'undefined') {
                charts.onTimeTrend = new Chart(onTimeEl, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            { label: 'Actual', data: trend.map(function (t) { return t.on_time.on_time_count; }), borderColor: '#2A4EAA', backgroundColor: 'rgba(42,78,170,.10)', fill: true, tension: 0.35, pointRadius: 4, borderWidth: 2.5 },
                            { label: 'Target', data: trend.map(function (t) { return t.on_time.target; }), borderColor: '#8A6100', borderDash: [6, 4], tension: 0.35, pointRadius: 3, borderWidth: 2 }
                        ]
                    },
                    options: trendOptions(function (v) { return v; })
                });
            }

            var budgetEl = document.getElementById('kdBudgetTrendChart');
            if (budgetEl && typeof Chart !== 'undefined') {
                charts.budgetTrend = new Chart(budgetEl, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            { label: 'Actual', data: trend.map(function (t) { return t.budget.adherence_rate; }), borderColor: '#8A6100', backgroundColor: 'rgba(138,97,0,.10)', fill: true, tension: 0.35, pointRadius: 4, borderWidth: 2.5 }
                        ]
                    },
                    options: trendOptions(function (v) { return v + '%'; })
                });
            }
        }

        function trendOptions(yTickFormatter) {
            return {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#666666', font: { size: 10 } } },
                    y: { grid: { color: 'rgba(0,0,0,.05)' }, ticks: { color: '#666666', font: { size: 10 }, callback: yTickFormatter } }
                },
                plugins: { legend: { display: false } }
            };
        }

        function trendDirection(vals) {
            var first = vals[0], last = vals[vals.length - 1];
            if (last > first * 1.03) return 'growing';
            if (last < first * 0.97) return 'declining';
            return 'holding steady';
        }

        function renderTrendInsight(trend) {
            var profitVals = trend.map(function (t) { return t.profit.net_profit; });
            var onTimeVals = trend.map(function (t) { return t.on_time.rate; });
            var budgetVals = trend.map(function (t) { return t.budget.adherence_rate; });

            var profitDir = trendDirection(profitVals);
            var onTimeDir = trendDirection(onTimeVals);
            var budgetDir = trendDirection(budgetVals);

            var text = 'Net profit has been ' + profitDir + ' from ' + trend[0].label + ' to ' + trend[trend.length - 1].label + '. ' +
                'On-time delivery is ' + onTimeDir + ' over the same span. ' +
                'Budget adherence is ' + budgetDir + ' quarter over quarter.';

            var action;
            if (onTimeDir === 'declining') {
                action = 'The business is financially ' + (profitDir === 'declining' ? 'under pressure' : 'growing') + '. Focus on improving schedule compliance next quarter.';
            } else if (profitDir === 'declining') {
                action = 'Delivery pace is holding but margins are slipping — review costing on upcoming quotations.';
            } else {
                action = 'Overall trend is healthy — keep the current cost and scheduling discipline going into next quarter.';
            }

            document.getElementById('kdTrendInsight').innerHTML =
                '<div>' + text + '</div><span class="kd-insight-action">→ ' + action + '</span>';
        }

        /* ── SMA Forecast ── */
        function directionBadge(delta, formattedAbsDelta) {
            if (!delta) return '<div class="kd-variance">Flat vs this quarter</div>';
            var tone  = delta > 0 ? 'good' : 'bad';
            var arrow = delta > 0 ? '↑' : '↓';
            return '<div class="kd-variance ' + tone + '">' + arrow + ' ' + formattedAbsDelta + ' ' + (delta > 0 ? 'above' : 'below') + ' this quarter</div>';
        }

        function forecastCard(name, primary, secondary, varianceHtml, windowLabel, sampleSize) {
            return '<div class="kd-card">' +
                '<div class="kd-card-top"><span class="kd-card-name">' + name + '</span>' +
                    '<span class="icon-chip-neutral" style="font-size:11px;font-weight:800;padding:4px 10px;border-radius:999px;white-space:nowrap;">SMA forecast</span>' +
                '</div>' +
                '<div class="kd-primary">' + primary + '</div>' +
                '<div class="kd-secondary">' + secondary + '</div>' +
                varianceHtml +
                '<div class="kd-scale-divider"></div>' +
                '<div class="kd-scale-label">Forecast basis</div>' +
                '<div style="font-size:12px;color:var(--muted);line-height:1.5;">Average of ' + sampleSize + ' quarter' + (sampleSize === 1 ? '' : 's') + ' with completed projects: ' + windowLabel + '</div>' +
                '</div>';
        }

        function forecastNoDataCard() {
            return '<div class="card" style="grid-column:1/-1;margin-bottom:0;">' +
                '<div class="kd-placeholder">' +
                    '<i data-lucide="trending-up"></i>' +
                    '<p style="font-weight:700;color:var(--dark);margin:0 0 4px;">Not enough data yet</p>' +
                    '<p style="font-size:13px;margin:0;">No completed projects in the last 4 quarters to base a forecast on.</p>' +
                '</div></div>';
        }

        function renderForecastCards(fc) {
            var el = document.getElementById('kdForecastCards');
            var chartCard = document.getElementById('kdForecastChartCard');

            if (!fc.has_data) {
                el.innerHTML = forecastNoDataCard();
                chartCard.style.display = 'none';
                if (typeof lucide !== 'undefined') lucide.createIcons();
                return;
            }
            chartCard.style.display = '';

            var profitHtml = forecastCard(
                'Project profit margin',
                fmtPeso(fc.profit.net_profit),
                fmtPct(fc.profit.avg_margin) + ' avg profit margin',
                directionBadge(fc.profit.vs_current, fmtPeso(Math.abs(fc.profit.vs_current))),
                fc.window_label, fc.sample_size
            );

            var onTimeHtml = forecastCard(
                'On-time delivery',
                fc.on_time.count.toFixed(1) + ' projects',
                fmtPct(fc.on_time.rate) + ' on-time delivery rate',
                directionBadge(fc.on_time.vs_current, Math.abs(fc.on_time.vs_current).toFixed(1) + ' projects'),
                fc.window_label, fc.sample_size
            );

            var budgetHtml = forecastCard(
                'Budget adherence',
                fmtPct(fc.budget.adherence_rate),
                'Forecast for ' + fc.target_label,
                directionBadge(fc.budget.vs_current, fmtPct(Math.abs(fc.budget.vs_current))),
                fc.window_label, fc.sample_size
            );

            el.innerHTML = profitHtml + onTimeHtml + budgetHtml;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function renderForecastInsight(fc) {
            var el = document.getElementById('kdForecastInsight');
            if (!fc.has_data) {
                el.innerHTML = '<div>Not enough historical data to forecast ' + fc.target_label + ' yet — complete at least one project in a recent quarter first.</div>';
                return;
            }

            var text = 'Based on a simple moving average of ' + fc.sample_size + ' quarter' + (fc.sample_size === 1 ? '' : 's') +
                ' with completed projects (' + fc.window_label + '), ' + fc.target_label + ' is forecasted at ' +
                fmtPeso(fc.profit.net_profit) + ' net profit, ' + fc.on_time.count.toFixed(1) + ' on-time project' + (Math.abs(fc.on_time.count - 1) < 0.05 ? '' : 's') +
                ', and ' + fmtPct(fc.budget.adherence_rate) + ' budget adherence.';

            var action;
            if (fc.profit.vs_current < 0 && fc.budget.vs_current < 0) {
                action = 'Both profit and budget adherence are trending down — review recent quotations before committing to new work next quarter.';
            } else if (fc.profit.vs_current < 0) {
                action = 'Profit is trending down — keep an eye on margins going into next quarter.';
            } else {
                action = 'The forecast looks stable to positive — use it as a planning baseline, not a guarantee.';
            }

            el.innerHTML = '<div>' + text + '</div><span class="kd-insight-action">→ ' + action + '</span>';
        }

        function renderForecastChart(fc) {
            var el = document.getElementById('kdForecastChart');
            if (!el || typeof Chart === 'undefined') return;
            if (charts.forecast) { charts.forecast.destroy(); charts.forecast = null; }
            if (!fc.has_data) return;

            var trend   = STATE.payload.trend;
            var current = trend[trend.length - 1]; // the selected/most recent quarter

            charts.forecast = new Chart(el, {
                type: 'bar',
                data: {
                    labels: ['Profit (₱ thousands)', 'On-time delivery (projects)', 'Budget adherence (%)'],
                    datasets: [
                        { label: 'Last actual', data: [current.profit.net_profit / 1000, current.on_time.on_time_count, current.budget.adherence_rate], backgroundColor: '#2A4EAA', borderRadius: 4 },
                        { label: 'SMA forecast', data: [fc.profit.net_profit / 1000, fc.on_time.count, fc.budget.adherence_rate], backgroundColor: '#207A3A', borderRadius: 4 },
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#666666', font: { size: 11 } } },
                        y: { grid: { color: 'rgba(0,0,0,.05)' }, ticks: { color: '#666666', font: { size: 11 } } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }

        /* ── Full re-render ── */
        function safely(fn) {
            try { fn(); } catch (e) { if (window.console) console.error('KPI dashboard render step failed:', e); }
        }

        function renderEverything() {
            var sc = STATE.payload.scorecard;
            document.querySelector('.kd-subtitle').innerHTML =
                'GMD South Phils Metal Fabrication Works · ' +
                '<strong style="color:var(--info);font-weight:800;">' + sc.label + '</strong> · ' +
                '<strong style="color:var(--dark);font-weight:800;">' + sc.project_count + ' completed project' + (sc.project_count === 1 ? '' : 's') + '</strong>';

            // Text/HTML content renders first and independently of the charts below, so a
            // Chart.js failure (e.g. an unsupported browser) can never blank out the rest of the page.
            safely(function () { renderCards(sc); });
            safely(function () { renderScorecardInsight(sc); });
            safely(function () { renderTrendInsight(STATE.payload.trend); });
            safely(function () { renderComparativeChart(sc); });
            safely(function () { renderTrendCharts(STATE.payload.trend); });
            safely(function () { renderForecastCards(STATE.payload.forecast); });
            safely(function () { renderForecastInsight(STATE.payload.forecast); });
            safely(function () { renderForecastChart(STATE.payload.forecast); });
        }

        /* ── Set Targets modal ── */
        var modal = document.getElementById('kdTargetsModal');
        function openModal() { modal.classList.add('show'); }
        function closeModal() { modal.classList.remove('show'); }

        function setQuarterFieldsReadOnly(readOnly) {
            ['kdInputProfitTarget', 'kdInputOnTimeTarget'].forEach(function (id) {
                document.getElementById(id).disabled = readOnly;
            });
            var saveBtn = document.getElementById('kdSaveQuarterTargets');
            saveBtn.disabled = readOnly;
            saveBtn.style.opacity = readOnly ? '0.5' : '';
            saveBtn.style.cursor = readOnly ? 'not-allowed' : '';
        }

        function openTargetsModal() {
            var sc = STATE.payload.scorecard;
            document.getElementById('kdModalPeriodLabel').textContent = 'Profit target for ' + sc.label + ' (₱)';
            document.getElementById('kdInputProfitTarget').value  = sc.profit.target   === null ? '' : sc.profit.target;
            document.getElementById('kdInputOnTimeTarget').value  = sc.on_time.target  === null ? '' : sc.on_time.target;

            var note = document.getElementById('kdFinalizedNote');
            if (sc.is_finalized) {
                note.style.display = 'block';
                note.textContent = sc.label + ' has already ended, so its targets are finalized and read-only.';
                setQuarterFieldsReadOnly(true);
            } else {
                note.style.display = 'none';
                setQuarterFieldsReadOnly(false);
            }

            openModal();
        }

        document.getElementById('kdOpenTargetsBtn').addEventListener('click', openTargetsModal);
        document.getElementById('kdCloseTargetsModal').addEventListener('click', closeModal);
        document.getElementById('kdCancelQuarterTargets').addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

        document.getElementById('kdSaveQuarterTargets').addEventListener('click', function () {
            var btn = this;
            btn.disabled = true;
            fetch(SAVE_QUARTER_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                body: JSON.stringify({
                    year: STATE.payload.year,
                    quarter: STATE.payload.quarter,
                    profit_target: document.getElementById('kdInputProfitTarget').value || 0,
                    on_time_target: document.getElementById('kdInputOnTimeTarget').value || 0,
                })
            })
            .then(function (r) { return r.json().then(function (body) { return { ok: r.ok, body: body }; }); })
            .then(function (result) {
                if (!result.ok) {
                    alert(result.body.error || 'Could not save targets.');
                    return;
                }
                STATE.payload = result.body;
                renderPeriodOptions();
                renderEverything();
                closeModal();
            })
            .finally(function () { btn.disabled = false; });
        });

        /* ── Generate Report ── */
        var reportModal = document.getElementById('kdReportModal');
        function openReportModal() { reportModal.classList.add('show'); }
        function closeReportModal() { reportModal.classList.remove('show'); }

        function populateReportYearSelects() {
            var years = STATE.payload.availableYears || [STATE.payload.year];
            ['kdReportFromYear', 'kdReportToYear'].forEach(function (id) {
                var sel = document.getElementById(id);
                sel.innerHTML = '';
                years.forEach(function (y) {
                    var opt = document.createElement('option');
                    opt.value = y;
                    opt.textContent = y;
                    sel.appendChild(opt);
                });
            });
        }

        document.getElementById('kdOpenReportBtn').addEventListener('click', function () {
            populateReportYearSelects();
            document.getElementById('kdReportFromYear').value    = STATE.payload.year;
            document.getElementById('kdReportFromQuarter').value = STATE.payload.quarter;
            document.getElementById('kdReportToYear').value      = STATE.payload.year;
            document.getElementById('kdReportToQuarter').value   = STATE.payload.quarter;
            document.getElementById('kdReportError').style.display = 'none';
            openReportModal();
        });
        document.getElementById('kdCloseReportModal').addEventListener('click', closeReportModal);
        document.getElementById('kdCancelReport').addEventListener('click', closeReportModal);
        reportModal.addEventListener('click', function (e) { if (e.target === reportModal) closeReportModal(); });

        function actualTargetCell(actualText, targetText, hasTarget, hit) {
            var cls = hasTarget ? (hit ? 'hit-y' : 'hit-n') : '';
            return '<span class="' + cls + '">' + actualText + '</span>' +
                ' <span class="muted">/ ' + (hasTarget ? targetText : '—') + '</span>';
        }

        function buildReportDocument(data) {
            var rows = data.quarters.map(function (q) {
                var profitCell = actualTargetCell(fmtPeso(q.profit.net_profit), fmtPeso(q.profit.target), q.profit.has_target, q.profit.hit);
                var onTimeCell = actualTargetCell(q.on_time.on_time_count, q.on_time.target, q.on_time.has_target, q.on_time.hit);
                var budgetStatus = budgetRangeStatus(q.budget.adherence_rate);
                var budgetCell = '<span style="color:' + budgetStatus.color + ';font-weight:800;">' + fmtPct(q.budget.adherence_rate) + '</span> <span class="muted">' + budgetStatus.label + '</span>';

                return '<tr>' +
                    '<td><strong>' + q.label + '</strong></td>' +
                    '<td class="r">' + q.project_count + '</td>' +
                    '<td class="r">' + profitCell + '</td>' +
                    '<td class="r">' + onTimeCell + '</td>' +
                    '<td class="r">' + budgetCell + '</td>' +
                '</tr>';
            }).join('');

            var totalProjects   = data.quarters.reduce(function (s, q) { return s + q.project_count; }, 0);
            var totalProfit     = data.quarters.reduce(function (s, q) { return s + q.profit.net_profit; }, 0);
            var totalOnTime     = data.quarters.reduce(function (s, q) { return s + q.on_time.on_time_count; }, 0);
            var totalActualCost = data.quarters.reduce(function (s, q) { return s + q.budget.actual_cost; }, 0);
            var totalEstBudget  = data.quarters.reduce(function (s, q) { return s + q.budget.estimated_budget; }, 0);
            var overallOnTimeRate  = totalProjects > 0 ? (totalOnTime / totalProjects * 100) : 0;
            var overallAdherence   = totalEstBudget > 0 ? (totalActualCost / totalEstBudget * 100) : 0;

            function hitSummary(pick) {
                var targeted = data.quarters.filter(function (q) { return pick(q).has_target; });
                if (!targeted.length) return '—';
                var hitCount = targeted.filter(function (q) { return pick(q).hit; }).length;
                return hitCount + ' / ' + targeted.length + ' quarters';
            }

            function budgetOkSummary() {
                var withProjects = data.quarters.filter(function (q) { return q.project_count > 0; });
                if (!withProjects.length) return '—';
                var okCount = withProjects.filter(function (q) { return q.budget.adherence_rate <= 100; }).length;
                return okCount + ' / ' + withProjects.length + ' quarters';
            }

            var narrative = 'Across ' + data.quarters.length + ' quarter' + (data.quarters.length === 1 ? '' : 's') +
                ' (' + data.from_label + ' to ' + data.to_label + '), the business generated ' + fmtPeso(totalProfit) +
                ' in total net profit across ' + totalProjects + ' completed project' + (totalProjects === 1 ? '' : 's') +
                ', with a ' + overallOnTimeRate.toFixed(1) + '% overall on-time delivery rate and ' +
                overallAdherence.toFixed(1) + '% aggregate budget adherence.';

            // ── Key Takeaways: interpretation, not just numbers ──
            var withData = data.quarters.filter(function (q) { return q.project_count > 0; });
            var takeaways = [];

            if (withData.length >= 2) {
                var first = withData[0], last = withData[withData.length - 1];
                var profitDelta = last.profit.net_profit - first.profit.net_profit;
                var onTimeDelta = last.on_time.rate - first.on_time.rate;

                takeaways.push('Net profit ' + (profitDelta > 0 ? 'grew' : (profitDelta < 0 ? 'declined' : 'held steady')) +
                    ' from ' + fmtPeso(first.profit.net_profit) + ' in ' + first.label + ' to ' + fmtPeso(last.profit.net_profit) + ' in ' + last.label + '.');
                takeaways.push('On-time delivery rate ' + (onTimeDelta > 0 ? 'improved' : (onTimeDelta < 0 ? 'declined' : 'stayed flat')) +
                    ' from ' + first.on_time.rate.toFixed(1) + '% to ' + last.on_time.rate.toFixed(1) + '% over the same span.');
            } else if (withData.length === 1) {
                takeaways.push('Only one quarter in this range (' + withData[0].label + ') had completed projects — not enough history yet to show a trend.');
            }

            if (withData.length >= 2) {
                var best  = withData.reduce(function (a, b) { return b.profit.net_profit > a.profit.net_profit ? b : a; });
                var worst = withData.reduce(function (a, b) { return b.profit.net_profit < a.profit.net_profit ? b : a; });
                if (best.label !== worst.label) {
                    takeaways.push(best.label + ' was the strongest quarter by net profit (' + fmtPeso(best.profit.net_profit) +
                        '), while ' + worst.label + ' was the weakest (' + fmtPeso(worst.profit.net_profit) + ').');
                }
            }

            var targetedQuarters = data.quarters.filter(function (q) { return q.profit.has_target || q.on_time.has_target; });
            if (targetedQuarters.length) {
                var allHitCount = targetedQuarters.filter(function (q) {
                    var checks = [];
                    if (q.profit.has_target)   checks.push(q.profit.hit);
                    if (q.on_time.has_target)  checks.push(q.on_time.hit);
                    return checks.length > 0 && checks.every(function (v) { return v; });
                }).length;
                takeaways.push(allHitCount + ' of ' + targetedQuarters.length + ' quarter' + (targetedQuarters.length === 1 ? '' : 's') +
                    ' with a target set met every target that was set for it.');
            } else {
                takeaways.push('No KPI targets were set for any quarter in this range — set targets on the dashboard to start tracking performance against goals.');
            }

            if (withData.length) {
                var overBudgetCount = withData.filter(function (q) { return q.budget.adherence_rate > 100; }).length;
                takeaways.push(overBudgetCount === 0
                    ? 'No quarter in this range exceeded its estimated budget.'
                    : overBudgetCount + ' of ' + withData.length + ' quarter' + (withData.length === 1 ? '' : 's') + ' ran over the estimated budget.');
            }

            var idleQuarters = data.quarters.filter(function (q) { return q.project_count === 0; });
            if (idleQuarters.length) {
                takeaways.push(idleQuarters.length + ' of ' + data.quarters.length + ' quarter' + (data.quarters.length === 1 ? '' : 's') +
                    ' had no completed projects (' + idleQuarters.map(function (q) { return q.label; }).join(', ') + ').');
            }

            var recommendation;
            if (withData.length < 2) {
                recommendation = 'Complete more projects across additional quarters to unlock deeper trend analysis.';
            } else {
                var profitDown = last.profit.net_profit < first.profit.net_profit;
                var onTimeDown = last.on_time.rate < first.on_time.rate;
                if (profitDown && onTimeDown) {
                    recommendation = 'Both profitability and delivery speed are trending down — review recent project costing and scheduling before committing to new work.';
                } else if (profitDown) {
                    recommendation = 'Profitability is trending down even though delivery has held up — review material and labor costing on upcoming quotations.';
                } else if (onTimeDown) {
                    recommendation = 'Profit is holding up but on-time delivery is slipping — review which project phases are causing delays.';
                } else {
                    recommendation = 'Performance is trending positively across this range — use it as a baseline and keep the current cost and scheduling discipline going forward.';
                }
            }

            var quarterLabels = data.quarters.map(function (q) { return q.label; });
            var profitSeries  = data.quarters.map(function (q) { return q.profit.net_profit; });
            var onTimeSeries  = data.quarters.map(function (q) { return q.on_time.on_time_count; });
            var budgetSeries  = data.quarters.map(function (q) { return q.budget.adherence_rate; });

            return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>KPI Report — ' + data.from_label + ' to ' + data.to_label + '</title>' +
                '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"><\/script>' +
                '<style>' +
                    'body{font-family:Arial,Helvetica,sans-serif;color:#222;padding:32px;}' +
                    'h1{font-size:20px;margin:0 0 4px;}' +
                    '.sub{color:#666;font-size:13px;margin-bottom:18px;}' +
                    '.narrative{background:#f5f5f5;border:1px solid #e0e0e0;border-radius:10px;padding:14px 16px;font-size:13px;line-height:1.6;margin-bottom:22px;}' +
                    '.stats{display:flex;gap:14px;margin-bottom:24px;}' +
                    '.stat{flex:1;border:1px solid #e0e0e0;border-radius:10px;padding:14px 16px;}' +
                    '.stat-label{font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:#666;font-weight:700;margin-bottom:6px;}' +
                    '.stat-value{font-size:22px;font-weight:900;color:#222;}' +
                    '.stat-sub{font-size:11.5px;color:#666;margin-top:4px;}' +
                    '.charts{display:flex;gap:14px;margin-bottom:26px;}' +
                    '.chart-box{flex:1;border:1px solid #e0e0e0;border-radius:10px;padding:12px 14px;}' +
                    '.chart-title{font-size:11.5px;font-weight:700;color:#333;margin-bottom:8px;}' +
                    'canvas{max-height:170px;}' +
                    '.section-title{font-size:11.5px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#666;margin-bottom:10px;}' +
                    '.takeaways{background:#EAF0FF;border:1px solid rgba(42,78,170,.2);border-radius:10px;padding:16px 18px;margin-bottom:26px;}' +
                    '.takeaways ul{margin:0 0 12px 18px;padding:0;font-size:13px;line-height:1.7;color:#222;}' +
                    '.takeaways li{margin-bottom:3px;}' +
                    '.recommendation{font-size:13px;font-weight:700;color:#2A4EAA;}' +
                    'table{width:100%;border-collapse:collapse;font-size:12.5px;}' +
                    'th,td{border:1px solid #ddd;padding:8px 10px;text-align:left;}' +
                    'th{background:#f5f5f5;font-size:10.5px;text-transform:uppercase;letter-spacing:.04em;color:#666;}' +
                    '.r{text-align:right;} .c{text-align:center;}' +
                    '.hit-y{color:#207A3A;font-weight:800;} .hit-n{color:#B42318;font-weight:800;}' +
                    '.muted{color:#999;font-weight:400;}' +
                    'tbody tr:nth-child(even){background:#fafafa;}' +
                    'tfoot td{font-weight:700;background:#f0f0f0;}' +
                    '@media print{body{padding:0;} .charts,.stats{page-break-inside:avoid;}}' +
                '</style></head><body>' +
                '<h1>GMD South Phils — Quarterly KPI Report</h1>' +
                '<div class="sub">' + data.from_label + ' to ' + data.to_label + ' &nbsp;·&nbsp; Generated ' + data.generated_at + '</div>' +
                '<div class="narrative">' + narrative + '</div>' +
                '<div class="stats">' +
                    '<div class="stat"><div class="stat-label">Total Net Profit</div><div class="stat-value">' + fmtPeso(totalProfit) + '</div><div class="stat-sub">Profit target hit: ' + hitSummary(function (q) { return q.profit; }) + '</div></div>' +
                    '<div class="stat"><div class="stat-label">On-Time Delivery</div><div class="stat-value">' + overallOnTimeRate.toFixed(1) + '%</div><div class="stat-sub">On-time target hit: ' + hitSummary(function (q) { return q.on_time; }) + '</div></div>' +
                    '<div class="stat"><div class="stat-label">Budget Adherence</div><div class="stat-value">' + overallAdherence.toFixed(1) + '%</div><div class="stat-sub">Not over budget: ' + budgetOkSummary() + '</div></div>' +
                '</div>' +
                '<div class="charts">' +
                    '<div class="chart-box"><div class="chart-title">Net Profit (₱)</div><canvas id="repChartProfit"></canvas></div>' +
                    '<div class="chart-box"><div class="chart-title">On-Time Delivery (projects)</div><canvas id="repChartOnTime"></canvas></div>' +
                    '<div class="chart-box"><div class="chart-title">Budget Adherence (%)</div><canvas id="repChartBudget"></canvas></div>' +
                '</div>' +
                '<div class="section-title">Key Takeaways</div>' +
                '<div class="takeaways">' +
                    '<ul>' + takeaways.map(function (t) { return '<li>' + t + '</li>'; }).join('') + '</ul>' +
                    '<div class="recommendation">→ ' + recommendation + '</div>' +
                '</div>' +
                '<div class="section-title">Quarter-by-Quarter Detail</div>' +
                '<table><thead><tr>' +
                    '<th>Quarter</th><th class="r">Projects</th>' +
                    '<th class="r">Net Profit (Actual / Target)</th>' +
                    '<th class="r">On-Time (Actual / Target)</th>' +
                    '<th class="r">Budget Adherence</th>' +
                '</tr></thead><tbody>' + rows + '</tbody>' +
                '<tfoot><tr><td>Total / Overall</td><td class="r">' + totalProjects + '</td>' +
                    '<td class="r">' + fmtPeso(totalProfit) + '</td>' +
                    '<td class="r">' + totalOnTime + ' (' + overallOnTimeRate.toFixed(1) + '%)</td>' +
                    '<td class="r">' + overallAdherence.toFixed(1) + '%</td>' +
                '</tr></tfoot></table>' +
                '<script>' +
                    'window.addEventListener("load", function () {' +
                        'var labels = ' + JSON.stringify(quarterLabels) + ';' +
                        'var profitData = ' + JSON.stringify(profitSeries) + ';' +
                        'var onTimeData = ' + JSON.stringify(onTimeSeries) + ';' +
                        'var budgetData = ' + JSON.stringify(budgetSeries) + ';' +
                        'var opts = function (formatter) { return { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, ' +
                            'scales:{ x:{ grid:{display:false}, ticks:{font:{size:9},color:"#666"} }, y:{ grid:{color:"rgba(0,0,0,.06)"}, ticks:{font:{size:9},color:"#666",callback:formatter} } } }; };' +
                        'if (window.Chart) {' +
                            'new Chart(document.getElementById("repChartProfit"), { type:"line", data:{ labels:labels, datasets:[{ data:profitData, borderColor:"#207A3A", backgroundColor:"rgba(32,122,58,.12)", fill:true, tension:.3, pointRadius:3, borderWidth:2 }] }, options: opts(function(v){ return "₱"+Math.round(v/1000)+"k"; }) });' +
                            'new Chart(document.getElementById("repChartOnTime"), { type:"line", data:{ labels:labels, datasets:[{ data:onTimeData, borderColor:"#2A4EAA", backgroundColor:"rgba(42,78,170,.12)", fill:true, tension:.3, pointRadius:3, borderWidth:2 }] }, options: opts(function(v){ return v; }) });' +
                            'new Chart(document.getElementById("repChartBudget"), { type:"line", data:{ labels:labels, datasets:[{ data:budgetData, borderColor:"#8A6100", backgroundColor:"rgba(138,97,0,.12)", fill:true, tension:.3, pointRadius:3, borderWidth:2 }] }, options: opts(function(v){ return v+"%"; }) });' +
                        '}' +
                        'setTimeout(function () { window.print(); }, 350);' +
                    '});' +
                '<\/script>' +
                '</body></html>';
        }

        document.getElementById('kdGenerateReportBtn').addEventListener('click', function () {
            var btn = this;
            var errEl = document.getElementById('kdReportError');
            errEl.style.display = 'none';
            btn.disabled = true;

            var qs = 'from_year=' + document.getElementById('kdReportFromYear').value +
                '&from_quarter=' + document.getElementById('kdReportFromQuarter').value +
                '&to_year=' + document.getElementById('kdReportToYear').value +
                '&to_quarter=' + document.getElementById('kdReportToQuarter').value;

            fetch(REPORT_RANGE_URL + '?' + qs, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json().then(function (body) { return { ok: r.ok, body: body }; }); })
                .then(function (result) {
                    if (!result.ok) {
                        errEl.textContent = result.body.error || 'Could not generate the report.';
                        errEl.style.display = 'block';
                        return;
                    }
                    var win = window.open('', '_blank');
                    win.document.write(buildReportDocument(result.body));
                    win.document.close();
                    closeReportModal();
                })
                .catch(function () {
                    errEl.textContent = 'Something went wrong generating the report.';
                    errEl.style.display = 'block';
                })
                .finally(function () { btn.disabled = false; });
        });

        /* ── Init ── */
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') lucide.createIcons();
            renderPeriodOptions();
            renderEverything();
        });
    })();
    </script>
</body>
</html>
