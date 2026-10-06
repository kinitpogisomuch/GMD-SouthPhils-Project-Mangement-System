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
        /* While another period is being fetched: dim the content so the change is visible */
        body.kd-is-loading .kd-panel { opacity: .45; pointer-events: none; transition: opacity .15s ease; }
        body.kd-is-loading #kdPeriodLabel { color: var(--muted); }
        /* Tabs (top-level + modal) reuse .filter-tabs/.filter-tab; header reuses .page-title/.page-subtitle;
           buttons reuse .add-btn/.cancel-btn/.save-btn; modal reuses .modal-overlay/.modal-card/.form-group —
           only the pieces with no existing equivalent (cards, insight box, chips, progress bar) are custom here. */

        .kd-meta { display: flex; align-items: center; flex-wrap: wrap; gap: 8px 10px; margin-top: 8px; }
        .kd-meta-company { font-size: 13px; font-weight: 700; color: var(--muted); margin-right: 4px; }
        .kd-meta-chip { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; line-height: 1.4; border-radius: 999px; padding: 5px 12px; background: var(--white); border: 1px solid var(--border); color: var(--dark); }
        .kd-meta-chip i, .kd-meta-chip svg { width: 13px; height: 13px; flex-shrink: 0; }
        .kd-meta-chip strong { font-weight: 900; }
        .kd-meta-period { background: var(--dark); border-color: var(--dark); color: #fff; }
        .kd-meta-ok { background: #E7F6EC; border-color: #86efac; color: #14532d; }
        .kd-meta-empty { background: var(--cream-soft); color: var(--muted); }
        .kd-header-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; flex-wrap: wrap; }
        .kd-header-actions .cancel-btn,
        .kd-header-actions .add-btn { height: 44px; padding-top: 0; padding-bottom: 0; }
        .kd-header-actions-divider { width: 1px; height: 24px; background: var(--border); flex-shrink: 0; }

        /* On mobile, center the title block and keep the two chips together on their own row
           (the long company name would otherwise leave only enough room for one chip to wrap
           alongside it, pushing the second one down onto a line by itself). */
        @media (max-width: 768px) {
            .kd-header-title-block { width: 100%; text-align: center; }
            .kd-meta { justify-content: center; }
            .kd-meta-company { flex: 1 1 100%; text-align: center; margin-right: 0; }
        }

        /* On mobile the period picker + two buttons used to wrap onto a cramped shared row (each is
           white-space:nowrap, so they can't shrink). Give the picker and "Generate report" an even
           half of the row each, then force "Set targets" onto its own full-width row after them. */
        @media (max-width: 768px) {
            .kd-header-actions { width: 100%; }
            .kd-header-actions-divider { display: none; }
            .kd-period-picker { flex: 1 1 0; min-width: 0; }
            .kd-period-trigger { width: 100%; justify-content: space-between; }
            .kd-header-actions .cancel-btn { flex: 1 1 0; min-width: 0; justify-content: center; }
            .kd-header-actions .add-btn { flex: 1 1 100%; justify-content: center; }
        }

        /* Calendar-style quarter picker — same pill look as the Monthly Expenses
           month picker: icon badge + plain label, no bordered chip / chevron. */
        .kd-period-picker { position: relative; }
        .kd-period-trigger {
            display: flex;
            align-items: center;
            gap: 10px;
            height: 44px;
            border: 1px solid var(--border);
            background: var(--white);
            border-radius: 14px;
            padding: 0 16px 0 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.03);
            cursor: pointer;
            transition: border-color .15s ease;
        }
        .kd-period-trigger:hover { border-color: var(--dark); }
        .kd-period-trigger-icon {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            background: #dbeafe;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .kd-period-trigger-icon i { width: 15px; height: 15px; color: #2563eb; }
        .kd-period-trigger-label { font-size: 14px; font-weight: 800; color: var(--dark); }

        .kd-period-panel {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: 230px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 14px 34px rgba(0,0,0,.14);
            padding: 12px;
            z-index: 60;
        }
        .kd-period-picker.open .kd-period-panel { display: block; }

        .kd-period-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 13.5px;
            font-weight: 800;
            color: var(--dark);
        }
        .kd-period-nav-btn {
            width: 28px;
            height: 28px;
            border: none;
            background: var(--cream-soft);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--dark);
            transition: background .15s ease;
        }
        .kd-period-nav-btn:hover:not(:disabled) { background: var(--border); }
        .kd-period-nav-btn:disabled { opacity: .35; cursor: not-allowed; }
        .kd-period-nav-btn i { width: 15px; height: 15px; }

        /* Level 1: quarters only, 2x2 grid */
        .kd-period-quarters { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .kd-period-quarter-btn {
            height: 40px;
            border: 1px solid var(--border);
            background: var(--cream-soft);
            border-radius: 10px;
            font-size: 13px;
            font-weight: 800;
            color: var(--dark);
            cursor: pointer;
            transition: all .15s ease;
        }
        .kd-period-quarter-btn:hover { border-color: var(--dark); }
        .kd-period-quarter-btn.selected {
            background: var(--dark);
            border-color: var(--dark);
            color: #fff;
        }

        /* Level 2: drilled into one quarter — "view whole quarter" + its 3 months */
        .kd-period-months { display: flex; flex-direction: column; gap: 8px; }
        .kd-period-whole-quarter-btn {
            border: 1px dashed var(--border);
            background: transparent;
            border-radius: 9px;
            padding: 8px 0;
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            cursor: pointer;
            transition: all .15s ease;
        }
        .kd-period-whole-quarter-btn:hover { border-color: var(--dark); color: var(--dark); }
        .kd-period-months-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
        .kd-period-month-btn {
            height: 38px;
            border: 1px solid var(--border);
            background: var(--cream-soft);
            border-radius: 9px;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--dark);
            cursor: pointer;
            transition: all .15s ease;
        }
        .kd-period-month-btn:hover { border-color: var(--dark); background: var(--dark); color: #fff; }

        #kdMonthlyBreakdownTable tbody tr.kd-month-row-highlight { background: #fff3d6; transition: background .3s ease; }

        .kd-panel { display: none; }
        .kd-panel.active { display: block; }

        /* Insight box */
        .kd-insight {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px 20px;
            margin-bottom: 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,.04);
        }
        .kd-insight-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--muted);
            margin-bottom: 10px;
        }
        .kd-insight-label svg { width: 13px; height: 13px; color: var(--accent); }
        .kd-insight-summary {
            font-size: 13.5px;
            line-height: 1.7;
            color: var(--dark);
        }
        .kd-insight-action {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-top: 14px;
            padding: 11px 14px;
            background: #EAF0FF;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            color: #1e3a8a;
            line-height: 1.55;
        }
        .kd-insight-action svg { width: 14px; height: 14px; flex-shrink: 0; margin-top: 2px; }

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

        .kd-target-row { display: flex; justify-content: space-between; align-items: center; margin-top: 32px; font-size: 12.5px; }
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

        .kd-breakdown { margin-top: auto; padding-top: 16px; }

        /* KPI cards: big centered number, a soft goal box, then a labelled breakdown */
        .kd-hero { text-align: center; font-size: 68px; font-weight: 900; letter-spacing: -2px; line-height: 1; color: var(--dark); margin-top: 12px; }
        .kd-hero-caption { text-align: center; font-size: 14.5px; color: var(--muted); margin-top: 10px; }
        /* the breakdown fills the rest of the card — its rows spread evenly, so a card with
           fewer rows has no empty gap above its breakdown */
        .kd-card .kd-breakdown { flex: 1; display: flex; flex-direction: column; justify-content: space-between; margin-top: 0; }
        .kd-card .kd-breakdown-row { margin-bottom: 0; padding: 9px 0; }
        .kd-card .kd-breakdown-row.total { padding-bottom: 0; }
        .kd-goal { margin-top: 20px; padding: 12px 14px 12px; background: var(--cream-soft); border: 1px solid var(--border); border-radius: 14px; }
        .kd-goal-top { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; font-size: 12px; }
        .kd-goal-label { color: var(--muted); font-weight: 700; }
        .kd-goal-value { font-weight: 900; color: var(--dark); font-size: 13px; white-space: nowrap; }
        .kd-goal .kd-progress-track { height: 8px; margin-top: 10px; background: var(--white); border: 1px solid var(--border); }
        .kd-goal-result { display: flex; align-items: center; gap: 6px; margin-top: 9px; font-size: 12px; font-weight: 800; color: var(--muted); }
        .kd-goal-result svg { width: 14px; height: 14px; flex-shrink: 0; }
        .kd-goal-result.good { color: var(--success); }
        .kd-goal-result.bad { color: var(--danger); }
        .kd-goal-result.warn { color: #A16207; }
        .kd-goal-result.info { color: #2A4EAA; }
        .kd-card-empty .kd-empty-body { flex: 1; min-height: 220px; display: flex; flex-direction: column; align-items: center; justify-content: center;
                                        gap: 10px; color: var(--muted-light); font-size: 13px; font-weight: 600; text-align: center; }
        .kd-card-empty .kd-empty-body svg { width: 28px; height: 28px; opacity: .55; }
        .kd-hero.kd-hero-text { font-size: 30px; letter-spacing: -.5px; line-height: 1.15; }
        .kd-card-notarget .kd-breakdown { padding-top: 8px; flex: 0 0 auto; justify-content: flex-start; }
        .kd-breakdown-title { font-size: 10px; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--muted); margin-bottom: 10px; }
        .kd-breakdown-row:has(+ .kd-breakdown-row.total) { border-bottom: none; }
        .kd-breakdown-row.total { border-top: 1px solid var(--dark); padding-top: 9px; margin-top: 2px; font-size: 12.5px; }
        .kd-breakdown-row.total .kd-breakdown-label { color: var(--dark); font-weight: 800; }
        .kd-breakdown-row.total .kd-breakdown-value { font-weight: 900; }
        @media (max-width: 640px) {
            .kd-hero { font-size: 44px; }
        }
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

        /* Set KPI targets modal — monthly inputs + auto-calculated quarterly total */
        .kd-targets-section { margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border); }
        .kd-targets-section:first-of-type { margin-top: 0; padding-top: 0; border-top: none; }
        .kd-targets-section-title { font-size: 14px; font-weight: 800; color: var(--dark); margin-bottom: 4px; }
        .kd-report-presets { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 16px; }
        .kd-report-preset { border: 1px solid var(--border); background: var(--cream-soft); color: var(--dark); font-size: 12px; font-weight: 700;
                            border-radius: 999px; padding: 6px 12px; cursor: pointer; font-family: inherit; }
        .kd-report-preset:hover, .kd-report-preset.active { background: var(--dark); border-color: var(--dark); color: #fff; }
        .kd-report-includes { margin: 4px 0 16px; padding: 12px 14px; background: var(--cream-soft); border: 1px solid var(--border); border-radius: 12px; }
        .kd-report-includes-title { font-size: 10px; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; }
        .kd-report-includes-list { display: flex; flex-wrap: wrap; gap: 6px; }
        .kd-report-includes-list span { font-size: 11px; font-weight: 700; color: var(--dark); background: var(--white); border: 1px solid var(--border); border-radius: 999px; padding: 3px 9px; }
        .kd-band-legend { display: flex; flex-wrap: wrap; gap: 6px; margin: -6px 0 14px; }
        .kd-band { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: var(--dark);
                   background: var(--cream-soft); border: 1px solid var(--border); border-radius: 999px; padding: 3px 9px; }
        .kd-band i { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .kd-targets-section-sub { font-size: 12px; color: var(--muted); line-height: 1.5; margin-bottom: 14px; }

        .kd-month-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
        .kd-month-row label { font-size: 13px; font-weight: 700; color: var(--dark); flex-shrink: 0; width: 84px; }
        .kd-month-input { position: relative; flex: 1; }
        .kd-month-input input {
            width: 100%;
            height: 42px;
            border: 1px solid var(--border);
            background: var(--cream-soft);
            border-radius: 12px;
            padding: 0 14px;
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
            outline: none;
            transition: .2s ease;
        }
        .kd-month-input input:focus { border-color: var(--accent); background: var(--white); }
        .kd-month-input.has-prefix input { padding-left: 28px; }
        .kd-month-input.has-suffix input { padding-right: 66px; }
        .kd-month-prefix, .kd-month-suffix {
            position: absolute; top: 50%; transform: translateY(-50%);
            font-size: 12.5px; font-weight: 700; color: var(--muted); pointer-events: none;
        }
        .kd-month-prefix { left: 14px; }
        .kd-month-suffix { right: 14px; }

        .kd-quarter-total {
            display: flex; align-items: center; justify-content: space-between;
            background: #EAF0FF; border-radius: 12px; padding: 12px 14px; margin-top: 4px;
            font-size: 12.5px; font-weight: 700; color: #1e3a8a;
        }
        .kd-quarter-total strong { font-size: 14px; font-weight: 900; }

        .kd-info-note {
            display: flex; align-items: flex-start; gap: 8px;
            background: var(--cream-soft); border: 1px solid var(--border); border-radius: 12px;
            padding: 11px 14px; font-size: 12px; color: var(--muted); line-height: 1.55; margin-top: 12px;
        }
        .kd-info-note i { width: 14px; height: 14px; flex-shrink: 0; margin-top: 2px; color: var(--muted); }
    </style>
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            <div class="page-header">
                <div class="kd-header-title-block">
                    <h1 class="page-title">KPI dashboard</h1>
                    <p class="page-subtitle" style="margin:4px 0 10px;">Track profit, on-time delivery and budget performance against your targets.</p>
                </div>
                <div class="kd-header-actions">
                    <div class="kd-period-picker" id="kdPeriodPicker">
                        <button type="button" class="kd-period-trigger" id="kdPeriodTrigger">
                            <span class="kd-period-trigger-icon"><i data-lucide="calendar"></i></span>
                            <span class="kd-period-trigger-label" id="kdPeriodLabel">—</span>
                        </button>
                        <div class="kd-period-panel" id="kdPeriodPanel">
                            <div class="kd-period-panel-header">
                                <button type="button" class="kd-period-nav-btn" id="kdPeriodPrevYear" aria-label="Previous year">
                                    <i data-lucide="chevron-left"></i>
                                </button>
                                <span id="kdPeriodPanelYear">—</span>
                                <button type="button" class="kd-period-nav-btn" id="kdPeriodNextYear" aria-label="Next year">
                                    <i data-lucide="chevron-right"></i>
                                </button>
                            </div>
                            <div class="kd-period-quarters" id="kdPeriodQuarters"></div>
                        </div>
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
                <div class="kd-chart-card" id="kdMonthlyBreakdownCard">
                    <div class="kd-chart-title">Monthly breakdown — this quarter</div>
                    <div class="table-wrapper">
                        <table class="data-table" id="kdMonthlyBreakdownTable">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th style="text-align:center;">Completed</th>
                                    <th style="text-align:right;">Net Profit (Actual)</th>
                                    <th style="text-align:right;">Net Profit (Target)</th>
                                    <th style="text-align:center;">On-Time Rate (Actual)</th>
                                    <th style="text-align:center;">On-Time Rate (Target)</th>
                                </tr>
                            </thead>
                            <tbody id="kdMonthlyBreakdownBody"></tbody>
                        </table>
                    </div>
                </div>
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
                        <div class="kd-chart-title">On-time delivery rate trend (%)</div>
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

            <div class="kd-report-presets">
                <button type="button" class="kd-report-preset" data-preset="quarter">This quarter</button>
                <button type="button" class="kd-report-preset" data-preset="year">This year</button>
                <button type="button" class="kd-report-preset" data-preset="last4">Last 4 quarters</button>
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

            <div class="kd-report-includes">
                <div class="kd-report-includes-title">The report includes</div>
                <div class="kd-report-includes-list">
                    <span>Executive summary</span><span>KPI scorecard</span><span>Performance trends</span>
                    <span>Key takeaways</span><span>Cost &amp; revenue</span><span>Quarter detail</span><span>Completed projects</span>
                </div>
            </div>

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
                    <p>Set a target for each month — the quarterly total is calculated automatically.</p>
                </div>
                <button class="modal-close" type="button" id="kdCloseTargetsModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="kd-finalized-note" id="kdFinalizedNote" style="display:none;font-size:12px;font-weight:700;color:var(--warning);background:#FFF3D6;border:1px solid rgba(138,97,0,.2);border-radius:10px;padding:8px 12px;margin-bottom:14px;"></div>

            <div class="form-group">
                <label>Setting targets for</label>
                <select class="filter-select" id="kdModalPeriodSelect" style="width:100%;"></select>
            </div>

            <div class="kd-targets-section">
                <div class="kd-targets-section-title">Profit margin target</div>
                <p class="kd-targets-section-sub">Set a net profit goal for each month based on GMD's expected workload. Status = net profit ÷ target × 100 (higher is better).</p>
                <div class="kd-band-legend"><span class="kd-band"><i style="background:#207A3A;"></i>100%+ Target hit</span><span class="kd-band"><i style="background:#A16207;"></i>80–99.9% Tolerable</span><span class="kd-band"><i style="background:#B42318;"></i>Below 80% Below target</span></div>

                <div class="kd-month-row">
                    <label id="kdProfitMonthLabel1"></label>
                    <div class="kd-month-input has-prefix"><span class="kd-month-prefix">₱</span><input type="text" inputmode="decimal" autocomplete="off" placeholder="0" id="kdInputProfitM1"></div>
                </div>
                <div class="kd-month-row">
                    <label id="kdProfitMonthLabel2"></label>
                    <div class="kd-month-input has-prefix"><span class="kd-month-prefix">₱</span><input type="text" inputmode="decimal" autocomplete="off" placeholder="0" id="kdInputProfitM2"></div>
                </div>
                <div class="kd-month-row">
                    <label id="kdProfitMonthLabel3"></label>
                    <div class="kd-month-input has-prefix"><span class="kd-month-prefix">₱</span><input type="text" inputmode="decimal" autocomplete="off" placeholder="0" id="kdInputProfitM3"></div>
                </div>

                <div class="kd-quarter-total">
                    <span>Quarterly total (auto-calculated)</span>
                    <strong id="kdProfitQuarterTotal">₱0</strong>
                </div>
            </div>

            <div class="kd-targets-section">
                <div class="kd-targets-section-title">On-time delivery target</div>
                <p class="kd-targets-section-sub">Set the share of completed projects that should be delivered on or before their deadline this quarter (it applies to each month too). On-time rate = projects on time ÷ completed projects × 100.</p>
                <div class="kd-band-legend"><span class="kd-band"><i style="background:#207A3A;"></i>At or above target: Target hit</span><span class="kd-band"><i style="background:#A16207;"></i>Up to 10 pts below: Tolerable</span><span class="kd-band"><i style="background:#B42318;"></i>More than 10 pts below: Below target</span></div>

                <div class="kd-month-row">
                    <label for="kdInputOnTimeRate">Target rate</label>
                    <div class="kd-month-input has-suffix"><input type="number" min="0" max="100" step="0.1" id="kdInputOnTimeRate" placeholder="e.g. 90"><span class="kd-month-suffix">%</span></div>
                </div>
                <p id="kdOnTimeRateError" style="display:none;font-size:12px;font-weight:700;color:var(--danger);margin:4px 0 0;">Enter a target rate from 0 to 100%.</p>
            </div>

            <div class="kd-targets-section">
                <div class="kd-targets-section-title">Budget adherence</div>
                <p class="kd-targets-section-sub">No monthly target needed — status = actual spend ÷ estimated cost × 100 (lower is better), judged on these fixed bands:</p>
                <div class="kd-band-legend"><span class="kd-band"><i style="background:#2A4EAA;"></i>Below 90% Well below estimate</span><span class="kd-band"><i style="background:#207A3A;"></i>90–100% Within budget</span><span class="kd-band"><i style="background:#A16207;"></i>100.1–103% Tolerable</span><span class="kd-band"><i style="background:#C2410C;"></i>103.1–107% Over budget</span><span class="kd-band"><i style="background:#B42318;"></i>Above 107% Critical</span></div>
                <div class="kd-info-note">
                    <i data-lucide="info"></i>
                    <span>Whatever you enter per month is used directly — no automatic splitting or guessing. The Month view on the dashboard shows this number as-is, and Quarter/Year views simply add up the relevant months.</span>
                </div>
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
        var KPI_PAGE_URL          = "{{ route('admin.kpi_dashboard') }}";

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

        /* ── Calendar-style quarter picker — quarters first, drill into a quarter to
           pick one of its months ── */
        var periodPicker  = document.getElementById('kdPeriodPicker');
        var periodPanel   = document.getElementById('kdPeriodPanel');
        var pickerYear    = STATE.payload.year; // year currently shown inside the open panel
        var pickerLevel   = 'quarter';          // 'quarter' | 'month'
        var pickerQuarter = null;               // set once a quarter is drilled into

        var MONTH_FULL = ['January','February','March','April','May','June','July','August','September','October','November','December'];

        function periodYearBounds() {
            var years = STATE.payload.availableYears || [STATE.payload.year];
            return { min: Math.min.apply(null, years), max: Math.max.apply(null, years) };
        }

        var MONTH_ABBR = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        var prevBtn = document.getElementById('kdPeriodPrevYear');
        var nextBtn = document.getElementById('kdPeriodNextYear');
        var yearLbl = document.getElementById('kdPeriodPanelYear');
        var list    = document.getElementById('kdPeriodQuarters');

        function renderPeriodPanel() {
            list.innerHTML = '';

            if (pickerLevel === 'quarter') {
                yearLbl.textContent = pickerYear;
                var bounds = periodYearBounds();
                prevBtn.disabled = pickerYear <= bounds.min;
                nextBtn.style.display = '';
                nextBtn.disabled = pickerYear >= bounds.max;
                prevBtn.onclick = function () { if (pickerYear > bounds.min) { pickerYear--; renderPeriodPanel(); } };
                nextBtn.onclick = function () { if (pickerYear < bounds.max) { pickerYear++; renderPeriodPanel(); } };

                list.className = 'kd-period-quarters';
                for (var q = 1; q <= 4; q++) {
                    var qBtn = document.createElement('button');
                    qBtn.type = 'button';
                    qBtn.className = 'kd-period-quarter-btn' +
                        (pickerYear === STATE.payload.year && q === STATE.payload.quarter ? ' selected' : '');
                    qBtn.textContent = 'Q' + q;
                    qBtn.dataset.quarter = q;
                    qBtn.addEventListener('click', function () {
                        pickerQuarter = parseInt(this.dataset.quarter, 10);
                        pickerLevel   = 'month';
                        renderPeriodPanel();
                    });
                    list.appendChild(qBtn);
                }
            } else {
                // Drilled into one quarter — show its 3 months, plus a way to load the
                // whole quarter without picking any single month.
                yearLbl.textContent = 'Q' + pickerQuarter + ' ' + pickerYear;
                prevBtn.disabled = false;
                prevBtn.onclick = function () { pickerLevel = 'quarter'; renderPeriodPanel(); };
                nextBtn.style.display = 'none';

                list.className = 'kd-period-months';

                var wholeBtn = document.createElement('button');
                wholeBtn.type = 'button';
                wholeBtn.className = 'kd-period-whole-quarter-btn';
                wholeBtn.textContent = 'View all of Q' + pickerQuarter;
                wholeBtn.addEventListener('click', function () {
                    goToPeriod(pickerYear, pickerQuarter);
                });
                list.appendChild(wholeBtn);

                var monthsGrid = document.createElement('div');
                monthsGrid.className = 'kd-period-months-grid';
                var startMonth = (pickerQuarter - 1) * 3 + 1;
                for (var i = 0; i < 3; i++) {
                    var monthNum  = startMonth + i;
                    var monthAbbr = MONTH_ABBR[monthNum - 1];
                    var monthFull = MONTH_FULL[monthNum - 1];
                    var mBtn = document.createElement('button');
                    mBtn.type = 'button';
                    mBtn.className = 'kd-period-month-btn';
                    mBtn.textContent = monthAbbr;
                    mBtn.dataset.month      = monthNum;
                    mBtn.dataset.monthLabel = monthAbbr + ' ' + pickerYear;
                    mBtn.addEventListener('click', function () {
                        // The scorecard cards/insight/chart then show this specific month's
                        // actuals vs. its own monthly target — the Monthly Breakdown table
                        // still shows the whole containing quarter, with this month's row flashed.
                        goToPeriod(pickerYear, pickerQuarter, parseInt(this.dataset.month, 10));
                    });
                    monthsGrid.appendChild(mBtn);
                }
                list.appendChild(monthsGrid);
            }
        }

        function openPeriodPanel() {
            pickerYear    = STATE.payload.year;
            pickerLevel   = 'quarter';
            pickerQuarter = null;
            renderPeriodPanel();
            periodPicker.classList.add('open');
        }
        function closePeriodPanel() {
            periodPicker.classList.remove('open');
        }

        document.getElementById('kdPeriodTrigger').addEventListener('click', function (e) {
            e.stopPropagation();
            if (periodPicker.classList.contains('open')) closePeriodPanel();
            else openPeriodPanel();
        });
        periodPanel.addEventListener('click', function (e) { e.stopPropagation(); });
        document.addEventListener('click', closePeriodPanel);

        var QUARTER_MONTH_RANGE = ['Jan–Mar', 'Apr–Jun', 'Jul–Sep', 'Oct–Dec'];

        function renderPeriodOptions() {
            // Trigger label reflects the server's own answer (payload.month) rather than a
            // separately-tracked client flag, so it can never drift out of sync — e.g. after
            // the Set Targets modal's own quarter switcher forces the view back to quarter-level.
            var label = STATE.payload.month
                ? MONTH_FULL[STATE.payload.month - 1] + ' ' + STATE.payload.year + ' (Q' + STATE.payload.quarter + ')'
                : 'Q' + STATE.payload.quarter + ' ' + STATE.payload.year +
                  ' (' + QUARTER_MONTH_RANGE[STATE.payload.quarter - 1] + ')';
            document.getElementById('kdPeriodLabel').textContent = label;
            pickerYear = STATE.payload.year;
        }

        // Picking a period in the picker loads the page for it (like any other page) —
        // the URL then reflects the period, so refresh / back / bookmarks keep it.
        function goToPeriod(year, quarter, month) {
            closePeriodPanel();
            document.getElementById('kdPeriodLabel').textContent = 'Loading…';
            document.body.classList.add('kd-is-loading');
            var url = KPI_PAGE_URL + '?year=' + year + '&quarter=' + quarter + (month ? '&month=' + month : '');
            window.location.href = url;
        }

        // A page loaded for a single month flashes that month's row in the Monthly Breakdown
        if (STATE.payload.month) {
            window.__kdPendingMonthHighlight = MONTH_ABBR[STATE.payload.month - 1] + ' ' + STATE.payload.year;
        }

        // In-place refresh — still used by the Set Targets modal, which stays open while it switches quarter
        function loadPeriod(year, quarter, month) {
            var url = KPI_DATA_URL + '?year=' + year + '&quarter=' + quarter;
            if (month) url += '&month=' + month;

            // Show that the new period is loading — the data is fetched in the background, not by a page reload
            var labelEl = document.getElementById('kdPeriodLabel');
            labelEl.textContent = 'Loading ' + (month ? MONTH_FULL[month - 1] + ' ' + year : 'Q' + quarter + ' ' + year) + '…';
            document.body.classList.add('kd-is-loading');

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function (r) {
                // e.g. an expired session redirects to the login page (HTML), which isn't KPI data
                if (!r.ok || (r.headers.get('content-type') || '').indexOf('application/json') === -1) {
                    throw new Error('Unexpected response ' + r.status);
                }
                return r.json();
            })
            .then(function (payload) {
                STATE.payload = payload;
                renderPeriodOptions();
                renderEverything();
            })
            .catch(function () {
                renderPeriodOptions();   // put the label back to the period still on screen
                alert('Could not load that period. Please refresh the page (your session may have expired) and try again.');
            })
            .finally(function () {
                document.body.classList.remove('kd-is-loading');
            });
        }

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
        /* Profit + on-time status bands: achievement = actual ÷ owner's target × 100
           (higher is better) — 100% or above Target hit · 80%–99.9% Tolerable · below 80% Below target */
        function achievementLevel(actual, target) {
            var pct = target > 0 ? (actual / target) * 100 : (actual > 0 ? 100 : 0);
            if (pct >= 100) return { key: 'hit',       label: 'Target hit',   icon: 'check-circle-2', chip: 'icon-chip-success', color: '#207A3A', tone: 'good', pct: pct };
            if (pct >= 80)  return { key: 'tolerable', label: 'Tolerable',    icon: 'alert-circle',   chip: 'icon-chip-warning', color: '#A16207', tone: 'warn', pct: pct };
            return                 { key: 'below',     label: 'Below target', icon: 'alert-triangle', chip: 'icon-chip-danger',  color: '#B42318', tone: 'bad',  pct: pct };
        }

        /* On-time status (rate vs the owner's target rate, in percentage points):
           at or above the target → Target hit · up to 10 pts below → Tolerable · more → Below target.
           Same names and colours as the other cards. */
        function onTimeLevel(rate, target) {
            var points = rate - target;
            if (points >= 0)   return { key: 'hit',       label: 'Target hit',   icon: 'check-circle-2', chip: 'icon-chip-success', color: '#207A3A', tone: 'good', points: points };
            if (points >= -10) return { key: 'tolerable', label: 'Tolerable',    icon: 'alert-circle',   chip: 'icon-chip-warning', color: '#A16207', tone: 'warn', points: points };
            return                    { key: 'below',     label: 'Below target', icon: 'alert-triangle', chip: 'icon-chip-danger',  color: '#B42318', tone: 'bad',  points: points };
        }
        function fmtPts(v) { return (Math.round(Math.abs(v) * 10) / 10).toFixed(1) + ' pts'; }

        function statusChip(level) {
            var base = 'display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:800;padding:4px 10px;border-radius:999px;white-space:nowrap;';
            if (!level) {
                return '<span class="icon-chip-neutral" style="' + base + '"><i data-lucide="minus-circle" style="width:12px;height:12px;"></i>No target set</span>';
            }
            return '<span class="' + level.chip + '" style="' + base + '"><i data-lucide="' + level.icon + '" style="width:12px;height:12px;"></i>' + level.label + '</span>';
        }

        function scaleBlock(scale) {
            if (!SHOW_INDUSTRY_SCALE) return '';
            return '<div class="kd-scale-divider"></div>' +
                '<div class="kd-scale-label">Industry scale</div>' +
                '<div class="kd-scale-row">' +
                    '<span class="' + toneChipClass(scale.tone) + '" style="font-size:11px;font-weight:800;padding:4px 10px;border-radius:999px;white-space:nowrap;">' + scale.label + ' (' + scale.range + ')</span>' +
                '</div>';
        }

        var CARDS_HAVE_DATA = true;   // set per render: false when the period has no completed projects

        function breakdownBlock(rows) {
            var html = '<div class="kd-breakdown"><div class="kd-breakdown-title">Breakdown</div>';
            rows.forEach(function (r) {
                if (!CARDS_HAVE_DATA) r = { label: r.label, value: '—', total: r.total };
                html += '<div class="kd-breakdown-row' + (r.total ? ' total' : '') + '">' +
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
                { label: 'Overhead cost',    value: fmtPeso(p.overhead_cost) },
                { label: 'Net profit',       value: fmtPeso(p.net_profit), tone: p.net_profit >= 0 ? 'good' : 'bad', total: true },
            ]);
        }

        function onTimeBreakdown(o) {
            return breakdownBlock([
                { label: 'Completed projects',  value: pluralize(o.total_completed, 'project') },
                { label: 'Delivered on time',   value: pluralize(o.on_time_count, 'project'), tone: 'good' },
                { label: 'Delayed',             value: pluralize(o.delayed_count, 'project'), tone: o.delayed_count > 0 ? 'bad' : undefined },
                { label: 'Avg delay (delayed)', value: o.avg_delay_days > 0 ? '~' + o.avg_delay_days + ' days' : '—', tone: o.avg_delay_days > 0 ? 'warn' : undefined },
            ]);
        }

        function budgetBreakdown(b) {
            return breakdownBlock([
                { label: 'Project budget',        value: fmtPeso(b.estimated_budget) },
                { label: 'Actual spend',          value: fmtPeso(b.actual_cost) },
                { label: 'Projects over budget',  value: b.over_budget_count + ' of ' + b.total_completed, tone: b.over_budget_count > 0 ? 'bad' : undefined },
                { label: 'Net savings',           value: fmtPeso(b.net_savings), tone: b.net_savings >= 0 ? 'good' : 'bad', total: true },
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

        /* Budget adherence bands: actual spend ÷ estimated cost × 100 (lower is better).
           Below 90% Well below estimate · 90%–100% Within budget · 100.1%–103% Tolerable ·
           103.1%–107% Over budget · above 107% Critical */
        function budgetRangeStatus(rate) {
            if (rate > 107) return { key: 'critical',  label: 'Critical',            bg: '#FEE4E2', color: '#B42318', tone: 'bad'  };
            if (rate > 103) return { key: 'over',      label: 'Over budget',         bg: '#FFEDD5', color: '#C2410C', tone: 'bad'  };
            if (rate > 100) return { key: 'tolerable', label: 'Tolerable',           bg: '#FEF9C3', color: '#A16207', tone: 'warn' };
            if (rate >= 90) return { key: 'within',    label: 'Within budget',       bg: '#E7F6EC', color: '#207A3A', tone: 'good' };
            return                 { key: 'below',     label: 'Well below estimate', bg: '#EAF0FF', color: '#2A4EAA', tone: 'info' };
        }

        /* Big centered number + a one-line caption saying what it means */
        function heroBlock(value, caption) {
            return '<div class="kd-hero">' + value + '</div>' +
                '<div class="kd-hero-caption">' + caption + '</div>';
        }

        /* The goal box: what the number is measured against, the progress bar, and the
           result in plain words (with an icon so good / short is readable at a glance) */
        function goalBox(label, value, barHtml, resultText, tone) {
            var icon = tone === 'good' ? 'trending-up' : (tone === 'bad' ? 'trending-down' : (tone === 'warn' ? 'alert-circle' : 'info'));
            return '<div class="kd-goal">' +
                '<div class="kd-goal-top"><span class="kd-goal-label">' + label + '</span><span class="kd-goal-value">' + value + '</span></div>' +
                barHtml +
                '<div class="kd-goal-result ' + (tone || '') + '"><i data-lucide="' + icon + '"></i><span>' + resultText + '</span></div>' +
                '</div>';
        }

        /* A card with no owner target for the period keeps its normal layout, but every
           value reads "—": the numbers only mean something once there is a goal to measure against. */
        /* KPI card states
           1. No project data + no target  → empty card: only the KPI name, nothing measured
           2. Project data + no owner target → "No target set" + the breakdown only — no percentage,
              no owner target, nothing that needs a target to calculate
           3. Project data + target set     → the full card
           4. Budget adherence needs no owner target → full card whenever there is project data */
        function emptyCard(name) {
            return '<div class="kd-card kd-card-empty">' +
                '<div class="kd-card-top"><span class="kd-card-name">' + name + '</span></div>' +
                '<div class="kd-empty-body"><i data-lucide="bar-chart-3"></i><span>Nothing to show for this period yet</span></div>' +
                '</div>';
        }

        function emptyCardText(name, text) {
            return '<div class="kd-card kd-card-empty">' +
                '<div class="kd-card-top"><span class="kd-card-name">' + name + '</span></div>' +
                '<div class="kd-empty-body"><i data-lucide="bar-chart-3"></i><span>' + text + '</span></div>' +
                '</div>';
        }

        function noTargetCard(name, breakdownHtml) {
            return '<div class="kd-card kd-card-notarget">' +
                '<div class="kd-card-top"><span class="kd-card-name">' + name + '</span>' + statusChip(null) + '</div>' +
                breakdownHtml +
                '</div>';
        }

        function profitCard(p) {
            if (!p.has_target) return CARDS_HAVE_DATA ? noTargetCard('Project profit margin rate', profitBreakdown(p)) : emptyCard('Project profit margin rate');
            var lvl = achievementLevel(p.net_profit, p.target);
            var resultText = fmtPct(lvl.pct) + ' of target · ' + (p.variance >= 0
                ? '+' + fmtPeso(p.variance) + ' above'
                : fmtPeso(Math.abs(p.variance)) + ' below');
            return '<div class="kd-card">' +
                '<div class="kd-card-top"><span class="kd-card-name">Project profit margin rate</span>' + statusChip(lvl) + '</div>' +
                heroBlock(fmtPct(p.avg_margin), fmtPeso(p.net_profit) + ' net profit this period') +
                goalBox('Owner target (net profit)', fmtPeso(p.target), progressBar(p.progress_pct, null, lvl.color), resultText, lvl.tone) +
                scaleBlock(p.scale) +
                profitBreakdown(p) +
                '</div>';
        }

        /* On-time delivery card — a RATE (projects on time ÷ completed projects × 100):
           · no completed projects → "No completed projects" instead of 0%
           · no target → the rate still shows, with "No target set"
           · target set → rate vs target rate, status by percentage points */
        function onTimeCard(o) {
            var name = 'On-time delivery rate';
            var note = o.on_time_count + ' of ' + pluralize(o.total_completed, 'project') + ' on time';

            if (!o.has_data) {
                if (!o.has_target) return emptyCardText(name, 'No completed projects');
                return '<div class="kd-card">' +
                    '<div class="kd-card-top"><span class="kd-card-name">' + name + '</span></div>' +
                    '<div class="kd-hero kd-hero-text">No completed projects</div>' +
                    '<div class="kd-hero-caption">The rate shows once a project is completed in this period</div>' +
                    goalBox('Owner target (on-time rate)', fmtPct(o.target), progressBar(0, null), 'Not measured yet', null) +
                    '</div>';
            }

            if (!o.has_target) {
                return '<div class="kd-card kd-card-notarget">' +
                    '<div class="kd-card-top"><span class="kd-card-name">' + name + '</span>' + statusChip(null) + '</div>' +
                    heroBlock(fmtPct(o.rate), note) +
                    onTimeBreakdown(o) +
                    '</div>';
            }

            var lvl = onTimeLevel(o.rate, o.target);
            var resultText = lvl.points >= 0
                ? (lvl.points === 0 ? 'Exactly on target' : '+' + fmtPts(lvl.points) + ' above target')
                : fmtPts(lvl.points) + ' below target';
            return '<div class="kd-card">' +
                '<div class="kd-card-top"><span class="kd-card-name">' + name + '</span>' + statusChip(lvl) + '</div>' +
                heroBlock(fmtPct(o.rate), note) +
                goalBox('Owner target (on-time rate)', fmtPct(o.target), progressBar(o.progress_pct, null, lvl.color), resultText, lvl.tone) +
                scaleBlock(o.scale) +
                onTimeBreakdown(o) +
                '</div>';
        }

        function budgetCard(b) {
            // needs no owner target — its healthy range is fixed — so only project data decides
            if (!CARDS_HAVE_DATA) return emptyCard('Budget adherence rate');
            var status = budgetRangeStatus(b.adherence_rate);
            var chip = '<span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:800;padding:4px 10px;border-radius:999px;white-space:nowrap;background:' +
                status.bg + ';color:' + status.color + ';">' + status.label + '</span>';
            var resultText = status.key === 'below'
                ? fmtPeso(b.net_savings) + ' under — check the estimate or unlogged costs'
                : (b.net_savings >= 0
                    ? fmtPeso(b.net_savings) + ' under the project budget'
                    : fmtPeso(Math.abs(b.net_savings)) + ' over the project budget');

            return '<div class="kd-card">' +
                '<div class="kd-card-top"><span class="kd-card-name">Budget adherence rate</span>' + chip + '</div>' +
                heroBlock(fmtPct(b.adherence_rate), fmtPeso(b.actual_cost) + ' spent of ' + fmtPeso(b.estimated_budget) + ' budget') +
                goalBox('Healthy range', '90% – 100%', progressBar(Math.min(100, b.adherence_rate), null, status.color), resultText, status.tone) +
                scaleBlock(b.scale) +
                budgetBreakdown(b) +
                '</div>';
        }

        function renderCards(sc) {
            CARDS_HAVE_DATA = (sc.project_count || 0) > 0;
            document.getElementById('kdCards').innerHTML =
                profitCard(sc.profit) + onTimeCard(sc.on_time) + budgetCard(sc.budget);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        /* ── Monthly Breakdown table — actual vs. the monthly targets set per-month.
           Always sourced from quarter_scorecard so it keeps showing all 3 months of
           the containing quarter even while the main cards above are on one month. ── */
        function renderMonthlyBreakdown() {
            var body = document.getElementById('kdMonthlyBreakdownBody');
            var rows = (STATE.payload.quarter_scorecard || {}).monthly_breakdown || [];

            body.innerHTML = rows.map(function (m) {
                var profitTargetText = m.profit_target === null ? '—' : fmtPeso(m.profit_target);
                var onTimeTargetText = m.on_time_target === null ? '—' : fmtPct(m.on_time_target);
                return '<tr data-month-label="' + m.label + '">' +
                    '<td>' + m.label + '</td>' +
                    // A month with no completed projects has no results yet — show "—", not 0 / ₱0,
                    // so it isn't read as "zero profit" or "nothing delivered on time"
                    '<td style="text-align:center;">' + (m.project_count ? m.project_count : '—') + '</td>' +
                    '<td style="text-align:right;font-weight:800;">' + (m.project_count ? fmtPeso(m.profit_actual) : '—') + '</td>' +
                    '<td style="text-align:right;color:var(--muted);">' + profitTargetText + '</td>' +
                    '<td style="text-align:center;font-weight:800;">' + (m.project_count ? fmtPct(m.on_time_rate) + '<div style="font-size:11px;font-weight:600;color:var(--muted);">' + m.on_time_actual + ' of ' + m.project_count + '</div>' : '—') + '</td>' +
                    '<td style="text-align:center;color:var(--muted);">' + onTimeTargetText + '</td>' +
                '</tr>';
            }).join('');

            // A month click in the period picker sets this before loadPeriod() re-renders —
            // once the new quarter's rows are in, scroll to and briefly flash that month's row.
            if (window.__kdPendingMonthHighlight) {
                var label = window.__kdPendingMonthHighlight;
                window.__kdPendingMonthHighlight = null;
                var row = body.querySelector('[data-month-label="' + label + '"]');
                if (row) {
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    row.classList.add('kd-month-row-highlight');
                    setTimeout(function () { row.classList.remove('kd-month-row-highlight'); }, 2000);
                }
            }
        }

        /* ── Scorecard insight ── */
        function buildInsightHtml(summary, action) {
            var html = '<div class="kd-insight-label"><i data-lucide="lightbulb"></i>Insight</div>' +
                '<div class="kd-insight-summary">' + summary + '</div>';
            if (action) {
                html += '<div class="kd-insight-action"><i data-lucide="arrow-right"></i><span>' + action + '</span></div>';
            }
            return html;
        }

        function budgetActionText(rate) {
            if (rate > 107) return 'Costs are critically above estimates — review BOM pricing and vendor quotes before the next quotation cycle.';
            if (rate > 103) return 'Spending is over budget — tighten BOM estimates and watch material purchases on active projects.';
            if (rate > 100) return 'Spending is slightly above estimate but tolerable — keep an eye on remaining purchases.';
            if (rate < 90)  return 'Actual spend is well below estimate — check whether the estimates are too high or some costs were not logged.';
            return null;
        }

        function budgetSentence(sc) {
            var budgetStatus = budgetRangeStatus(sc.budget.adherence_rate);
            return 'Budget adherence is at ' + fmtPct(sc.budget.adherence_rate) + ' (' +
                fmtPeso(sc.budget.actual_cost) + ' actual vs ' + fmtPeso(sc.budget.estimated_budget) + ' estimated) — ' +
                budgetStatus.label.toLowerCase() + '.';
        }

        function renderScorecardInsight(sc) {
            var box = document.getElementById('kdScorecardInsight');
            var hasData = (sc.project_count || 0) > 0;

            // 1. no project data + no target → no insight at all
            if (!hasData && !sc.profit.has_target) {
                box.innerHTML = '';
                box.style.display = 'none';
                return;
            }
            box.style.display = '';

            // 2. project data but no owner target → only budget adherence can be judged
            if (!sc.profit.has_target) {
                box.innerHTML = buildInsightHtml(budgetSentence(sc), budgetActionText(sc.budget.adherence_rate) ||
                    'Spending is within budget — maintain current cost discipline.');
                if (typeof lucide !== 'undefined') lucide.createIcons();
                return;
            }

            // 3. targets set → the full insight
            var parts = [];
            var profitLvl = achievementLevel(sc.profit.net_profit, sc.profit.target);
            var onTimeLvl = (sc.on_time.has_target && sc.on_time.has_data) ? onTimeLevel(sc.on_time.rate, sc.on_time.target) : null;
            var profitPhrase = profitLvl.key === 'hit' ? 'Profit hit its target' : 'Profit is ' + profitLvl.label.toLowerCase();
            parts.push(profitPhrase + ' (' + fmtPct(profitLvl.pct) + ' of target) with a net profit of ' +
                fmtPeso(sc.profit.net_profit) + ' against a target of ' + fmtPeso(sc.profit.target) + '.');

            if (!sc.on_time.has_data) {
                parts.push('No projects were completed this period, so the on-time delivery rate can\'t be measured yet.');
            } else if (!onTimeLvl) {
                parts.push('On-time delivery rate is ' + fmtPct(sc.on_time.rate) + ' (' + sc.on_time.on_time_count + ' of ' + pluralize(sc.on_time.total_completed, 'project') + ' on time) — no target set.');
            } else if (onTimeLvl.key === 'hit') {
                parts.push('On-time delivery rate hit its target at ' + fmtPct(sc.on_time.rate) + ' against ' + fmtPct(sc.on_time.target) +
                    ' (' + sc.on_time.on_time_count + ' of ' + pluralize(sc.on_time.total_completed, 'project') + ' on time).');
            } else {
                parts.push('On-time delivery rate is ' + onTimeLvl.label.toLowerCase() + ' at ' + fmtPct(sc.on_time.rate) + ', ' +
                    fmtPts(onTimeLvl.points) + ' below the ' + fmtPct(sc.on_time.target) + ' target.');
            }

            if (hasData) parts.push(budgetSentence(sc));

            var action;
            if (onTimeLvl && onTimeLvl.key !== 'hit' && sc.on_time.delayed_projects && sc.on_time.delayed_projects.length) {
                action = 'Review which phases caused delays in ' + sc.on_time.delayed_projects.join(' and ') + '. Adjust duration estimates for similar projects next quarter.';
            } else if (!sc.profit.hit) {
                action = 'Review material and labor costing on recent quotations to recover margin next quarter.';
            } else if (hasData && budgetActionText(sc.budget.adherence_rate)) {
                action = budgetActionText(sc.budget.adherence_rate);
            } else {
                action = 'All targets are on track — maintain current cost discipline and delivery cadence into next quarter.';
            }

            document.getElementById('kdScorecardInsight').innerHTML = buildInsightHtml(parts.join(' '), action);
            if (typeof lucide !== 'undefined') lucide.createIcons();
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
                    labels: ['Profit (₱ thousands)', 'On-time delivery rate (%)', 'Budget adherence (%)'],
                    datasets: [
                        { label: 'Target', data: [sc.profit.target / 1000, sc.on_time.target, null], backgroundColor: '#2A4EAA', borderRadius: 4 },
                        { label: 'Actual', data: [sc.profit.net_profit / 1000, sc.on_time.has_data ? sc.on_time.rate : null, sc.budget.adherence_rate], backgroundColor: '#207A3A', borderRadius: 4 },
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
                            // Actual rate — drawn on top, a gap where a quarter had no completed projects
                            { label: 'Actual rate', data: trend.map(function (t) { return t.on_time.has_data ? t.on_time.rate : null; }), spanGaps: true,
                              borderColor: '#2A4EAA', backgroundColor: 'rgba(42,78,170,.10)', fill: 'origin', tension: 0, borderWidth: 2.5,
                              pointRadius: 6, pointHoverRadius: 7, pointBackgroundColor: '#2A4EAA', pointBorderColor: '#ffffff', pointBorderWidth: 2, order: 0 },
                            // Target rate — one per quarter, so a dashed step line rather than a slope
                            { label: 'Target rate', data: trend.map(function (t) { return t.on_time.target; }), spanGaps: false,
                              borderColor: '#8A6100', backgroundColor: '#8A6100', borderDash: [6, 4], stepped: 'middle', tension: 0, borderWidth: 2,
                              pointRadius: 3, pointStyle: 'rectRot', order: 1 }
                        ]
                    },
                    options: (function () {
                        var o = trendOptions(function (v) { return v + '%'; });
                        o.scales.y.min = 0;
                        o.scales.y.max = 100;
                        o.scales.y.ticks.stepSize = 20;
                        o.plugins.legend = { display: true, position: 'bottom', labels: { boxWidth: 12, boxHeight: 12, usePointStyle: true, font: { size: 11 }, color: '#555' } };
                        o.plugins.tooltip = { callbacks: { label: function (ctx) {
                            var t = trend[ctx.dataIndex];
                            if (ctx.datasetIndex === 0) {
                                return t.on_time.has_data
                                    ? ' Actual: ' + fmtPct(t.on_time.rate) + ' (' + t.on_time.on_time_count + ' of ' + t.on_time.total_completed + ' on time)'
                                    : ' Actual: no completed projects';
                            }
                            return ' Target: ' + (t.on_time.target === null ? 'not set' : fmtPct(t.on_time.target));
                        } } };
                        return o;
                    })()
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
            var budgetVals = trend.map(function (t) { return t.budget.adherence_rate; });

            var profitDir = trendDirection(profitVals);
            var budgetDir = trendDirection(budgetVals);

            // On-time rate only exists for quarters with completed projects — an empty quarter is
            // not a 0% rate, so only measured quarters are compared (change in percentage points)
            var measured = trend.filter(function (t) { return t.on_time.has_data; });
            var onTimeDir, onTimeSentence;
            if (measured.length < 2) {
                onTimeDir = 'not enough data';
                onTimeSentence = measured.length === 1
                    ? 'On-time delivery rate was ' + fmtPct(measured[0].on_time.rate) + ' in ' + measured[0].label + ', the only quarter with completed projects — not enough to show a trend yet. '
                    : 'No quarter in this span had completed projects, so there is no on-time delivery rate to trend yet. ';
            } else {
                var firstM = measured[0], lastM = measured[measured.length - 1];
                var pts = lastM.on_time.rate - firstM.on_time.rate;
                onTimeDir = pts > 3 ? 'improving' : (pts < -3 ? 'declining' : 'holding steady');
                onTimeSentence = 'On-time delivery rate is ' + onTimeDir + ', from ' + fmtPct(firstM.on_time.rate) + ' in ' + firstM.label +
                    ' to ' + fmtPct(lastM.on_time.rate) + ' in ' + lastM.label + '. ';
            }

            var text = 'Net profit has been ' + profitDir + ' from ' + trend[0].label + ' to ' + trend[trend.length - 1].label + '. ' +
                onTimeSentence +
                'Budget adherence is ' + budgetDir + ' quarter over quarter.';

            var action;
            if (onTimeDir === 'declining') {
                action = 'The business is financially ' + (profitDir === 'declining' ? 'under pressure' : 'growing') + '. Focus on improving schedule compliance next quarter.';
            } else if (profitDir === 'declining') {
                action = 'Delivery pace is holding but margins are slipping — review costing on upcoming quotations.';
            } else {
                action = 'Overall trend is healthy — keep the current cost and scheduling discipline going into next quarter.';
            }

            document.getElementById('kdTrendInsight').innerHTML = buildInsightHtml(text, action);
            if (typeof lucide !== 'undefined') lucide.createIcons();
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
                'Project profit margin rate',
                fmtPct(fc.profit.avg_margin),
                fmtPeso(fc.profit.net_profit) + ' net profit',
                directionBadge(fc.profit.vs_current, fmtPeso(Math.abs(fc.profit.vs_current))),
                fc.window_label, fc.sample_size
            );

            var onTimeHtml = forecastCard(
                'On-time delivery rate',
                fmtPct(fc.on_time.rate),
                fc.on_time.on_time + ' of ' + pluralize(fc.on_time.completed, 'project') + ' on time across the window',
                fc.on_time.vs_current === null
                    ? '<div class="kd-variance">No completed projects this quarter to compare</div>'
                    : directionBadge(fc.on_time.vs_current, fmtPts(fc.on_time.vs_current)),
                fc.window_label, fc.sample_size
            );

            var budgetHtml = forecastCard(
                'Budget adherence rate',
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
                el.innerHTML = buildInsightHtml('Not enough historical data to forecast ' + fc.target_label + ' yet — complete at least one project in a recent quarter first.');
                if (typeof lucide !== 'undefined') lucide.createIcons();
                return;
            }

            var text = 'Based on a simple moving average of ' + fc.sample_size + ' quarter' + (fc.sample_size === 1 ? '' : 's') +
                ' with completed projects (' + fc.window_label + '), ' + fc.target_label + ' is forecasted at ' +
                fmtPeso(fc.profit.net_profit) + ' net profit, a ' + fmtPct(fc.on_time.rate) + ' on-time delivery rate' +
                ', and ' + fmtPct(fc.budget.adherence_rate) + ' budget adherence.';

            var action;
            if (fc.profit.vs_current < 0 && fc.budget.vs_current < 0) {
                action = 'Both profit and budget adherence are trending down — review recent quotations before committing to new work next quarter.';
            } else if (fc.profit.vs_current < 0) {
                action = 'Profit is trending down — keep an eye on margins going into next quarter.';
            } else {
                action = 'The forecast looks stable to positive — use it as a planning baseline, not a guarantee.';
            }

            el.innerHTML = buildInsightHtml(text, action);
            if (typeof lucide !== 'undefined') lucide.createIcons();
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
                    labels: ['Profit (₱ thousands)', 'On-time delivery rate (%)', 'Budget adherence (%)'],
                    datasets: [
                        { label: 'Last actual', data: [current.profit.net_profit / 1000, current.on_time.has_data ? current.on_time.rate : null, current.budget.adherence_rate], backgroundColor: '#2A4EAA', borderRadius: 4 },
                        { label: 'SMA forecast', data: [fc.profit.net_profit / 1000, fc.on_time.rate, fc.budget.adherence_rate], backgroundColor: '#207A3A', borderRadius: 4 },
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
            if (typeof lucide !== 'undefined') lucide.createIcons();

            // Text/HTML content renders first and independently of the charts below, so a
            // Chart.js failure (e.g. an unsupported browser) can never blank out the rest of the page.
            safely(function () { renderCards(sc); });
            safely(function () { renderMonthlyBreakdown(); });
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

        var QUARTER_MONTHS = {
            1: ['January', 'February', 'March'],
            2: ['April', 'May', 'June'],
            3: ['July', 'August', 'September'],
            4: ['October', 'November', 'December'],
        };
        var QUARTER_MONTHS_SHORT = {
            1: 'Jan - Mar', 2: 'Apr - Jun', 3: 'Jul - Sep', 4: 'Oct - Dec',
        };

        var PROFIT_MONTH_IDS  = ['kdInputProfitM1', 'kdInputProfitM2', 'kdInputProfitM3'];
        var ONTIME_RATE_ID    = 'kdInputOnTimeRate';

        function setQuarterFieldsReadOnly(readOnly) {
            PROFIT_MONTH_IDS.concat([ONTIME_RATE_ID]).forEach(function (id) {
                document.getElementById(id).disabled = readOnly;
            });
            document.getElementById('kdModalPeriodSelect').disabled = readOnly;
            var saveBtn = document.getElementById('kdSaveQuarterTargets');
            saveBtn.disabled = readOnly;
            saveBtn.style.opacity = readOnly ? '0.5' : '';
            saveBtn.style.cursor = readOnly ? 'not-allowed' : '';
        }

        /* Profit target boxes show thousands separators (40,000) — these turn them back into numbers */
        function moneyInputValue(id) {
            var n = parseFloat(String(document.getElementById(id).value).replace(/,/g, ''));
            return isNaN(n) ? 0 : n;
        }
        function formatMoneyText(raw) {
            var clean = String(raw).replace(/[^0-9.]/g, '');
            var dot = clean.indexOf('.');
            var whole = dot === -1 ? clean : clean.slice(0, dot);
            var dec = dot === -1 ? '' : '.' + clean.slice(dot + 1).replace(/\./g, '').slice(0, 2);
            whole = whole.replace(/^0+(?=\d)/, '');
            return whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + dec;
        }
        // Re-format while typing, keeping the cursor after the same digit
        function attachMoneyFormat(input) {
            input.addEventListener('input', function () {
                var caret = input.selectionStart;
                var digitsBefore = input.value.slice(0, caret).replace(/[^0-9.]/g, '').length;
                input.value = formatMoneyText(input.value);
                var pos = 0, seen = 0;
                while (pos < input.value.length && seen < digitsBefore) {
                    if (/[0-9.]/.test(input.value[pos])) seen++;
                    pos++;
                }
                input.setSelectionRange(pos, pos);
            });
        }

        function recalcQuarterTotals() {
            var profitTotal = PROFIT_MONTH_IDS.reduce(function (sum, id) {
                return sum + moneyInputValue(id);
            }, 0);
            document.getElementById('kdProfitQuarterTotal').textContent = fmtPeso(profitTotal);
        }

        PROFIT_MONTH_IDS.forEach(function (id) {
            attachMoneyFormat(document.getElementById(id));   // runs first, so the total reads the formatted value
            document.getElementById(id).addEventListener('input', recalcQuarterTotals);
        });
        document.getElementById(ONTIME_RATE_ID).addEventListener('input', function () {
            this.classList.remove('is-invalid');
            document.getElementById('kdOnTimeRateError').style.display = 'none';
        });

        /* Populates the modal's own fields + period-select from a given payload, without
           touching the dashboard behind it — used both when opening and when the modal's
           own quarter/year dropdown is changed. */
        function populateModalFromPayload(payload) {
            // Targets are always set per-quarter, regardless of whether the main dashboard
            // behind the modal is currently showing one specific month.
            var sc = payload.quarter_scorecard || payload.scorecard;
            var months = QUARTER_MONTHS[payload.quarter];

            for (var i = 0; i < 3; i++) {
                document.getElementById('kdProfitMonthLabel' + (i + 1)).textContent = months[i];
                document.getElementById(PROFIT_MONTH_IDS[i]).value = sc.profit.target_monthly[i] ? formatMoneyText(sc.profit.target_monthly[i]) : '';
            }
            document.getElementById(ONTIME_RATE_ID).value = (sc.on_time.target === null || sc.on_time.target === undefined) ? '' : sc.on_time.target;
            document.getElementById(ONTIME_RATE_ID).classList.remove('is-invalid');
            document.getElementById('kdOnTimeRateError').style.display = 'none';
            recalcQuarterTotals();

            var note = document.getElementById('kdFinalizedNote');
            if (sc.is_finalized) {
                note.style.display = 'block';
                note.textContent = sc.label + ' has already ended, so its targets are finalized and read-only.';
                setQuarterFieldsReadOnly(true);
            } else {
                note.style.display = 'none';
                setQuarterFieldsReadOnly(false);
            }
        }

        function buildModalPeriodOptions(payload) {
            var sel = document.getElementById('kdModalPeriodSelect');
            sel.innerHTML = '';
            (payload.availableYears || [payload.year]).forEach(function (y) {
                [1, 2, 3, 4].forEach(function (q) {
                    var opt = document.createElement('option');
                    opt.value = y + '-' + q;
                    opt.textContent = 'Q' + q + ' ' + y + ' (' + QUARTER_MONTHS_SHORT[q] + ')';
                    if (y === payload.year && q === payload.quarter) opt.selected = true;
                    sel.appendChild(opt);
                });
            });
        }

        function openTargetsModal() {
            buildModalPeriodOptions(STATE.payload);
            populateModalFromPayload(STATE.payload);
            openModal();
        }

        document.getElementById('kdModalPeriodSelect').addEventListener('change', function () {
            var parts = this.value.split('-');
            var year = parseInt(parts[0], 10);
            var quarter = parseInt(parts[1], 10);
            loadPeriod(year, quarter); // updates STATE.payload + re-renders the dashboard behind the modal
            populateModalFromPayload(STATE.payload);
        });

        document.getElementById('kdOpenTargetsBtn').addEventListener('click', openTargetsModal);
        document.getElementById('kdCloseTargetsModal').addEventListener('click', closeModal);
        document.getElementById('kdCancelQuarterTargets').addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

        document.getElementById('kdSaveQuarterTargets').addEventListener('click', function () {
            var btn = this;
            var rateInput = document.getElementById(ONTIME_RATE_ID);
            var rateVal = rateInput.value.trim();
            if (rateVal === '' || isNaN(Number(rateVal)) || Number(rateVal) < 0 || Number(rateVal) > 100) {
                rateInput.classList.add('is-invalid');
                document.getElementById('kdOnTimeRateError').style.display = 'block';
                rateInput.focus();
                return;
            }
            btn.disabled = true;
            fetch(SAVE_QUARTER_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                body: JSON.stringify({
                    year: STATE.payload.year,
                    quarter: STATE.payload.quarter,
                    profit_target_m1:  moneyInputValue('kdInputProfitM1'),
                    profit_target_m2:  moneyInputValue('kdInputProfitM2'),
                    profit_target_m3:  moneyInputValue('kdInputProfitM3'),
                    on_time_target:    Number(rateVal),
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
            document.querySelectorAll('.kd-report-preset').forEach(function (b) { b.classList.toggle('active', b.dataset.preset === 'quarter'); });
            openReportModal();
        });
        // Quick ranges: this quarter, this calendar year, or the last 4 quarters up to the current one
        document.querySelectorAll('.kd-report-preset').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var y = Number(STATE.payload.year), q = Number(STATE.payload.quarter);
                var fromY = y, fromQ = q, toY = y, toQ = q;
                if (btn.dataset.preset === 'year') { fromQ = 1; toQ = 4; }
                if (btn.dataset.preset === 'last4') { fromQ = q - 3; if (fromQ < 1) { fromQ += 4; fromY = y - 1; } }
                var years = Array.prototype.map.call(document.getElementById('kdReportFromYear').options, function (o) { return Number(o.value); });
                if (years.indexOf(fromY) === -1) { fromY = Math.min.apply(null, years); fromQ = 1; }
                document.getElementById('kdReportFromYear').value    = fromY;
                document.getElementById('kdReportFromQuarter').value = fromQ;
                document.getElementById('kdReportToYear').value      = toY;
                document.getElementById('kdReportToQuarter').value   = toQ;
                document.querySelectorAll('.kd-report-preset').forEach(function (b) { b.classList.toggle('active', b === btn); });
            });
        });
        document.getElementById('kdCloseReportModal').addEventListener('click', closeReportModal);
        document.getElementById('kdCancelReport').addEventListener('click', closeReportModal);
        reportModal.addEventListener('click', function (e) { if (e.target === reportModal) closeReportModal(); });

        function actualTargetCell(actualText, targetText, hasTarget, hit, actual, target) {
            var cls = '';
            if (hasTarget) {
                var lvl = (actual !== undefined) ? achievementLevel(actual, target) : null;
                cls = lvl ? (lvl.key === 'hit' ? 'hit-y' : (lvl.key === 'tolerable' ? 'hit-t' : 'hit-n')) : (hit ? 'hit-y' : 'hit-n');
            }
            return '<span class="' + cls + '">' + actualText + '</span>' +
                ' <span class="muted">/ ' + (hasTarget ? targetText : '—') + '</span>';
        }

        // Project / client names are typed by users — escape them before putting them in the report HTML
        function escapeHtml(str) {
            return String(str == null ? '' : str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        /* Small coloured status pill for the printed report */
        function reportPill(label, color, bg) {
            return '<span class="pill" style="color:' + color + ';background:' + bg + ';">' + label + '</span>';
        }
        var LEVEL_BG = { hit: '#E7F6EC', tolerable: '#FEF9C3', below: '#FEE4E2' };
        function targetPill(hasTarget, actual, target) {
            if (!hasTarget) return reportPill('No target set', '#666', '#F0F0F0');
            var lvl = achievementLevel(actual, target);
            return reportPill(lvl.label, lvl.color, LEVEL_BG[lvl.key]);
        }

        var REPORT_LOGO_URL = @json(asset('images/gmdlogo-circle.svg'));

        function buildReportDocument(data) {
            var rows = data.quarters.map(function (q) {
                var profitCell = actualTargetCell(fmtPeso(q.profit.net_profit), fmtPeso(q.profit.target), q.profit.has_target, q.profit.hit, q.profit.net_profit, q.profit.target) +
                    '<div class="cell-pill">' + targetPill(q.profit.has_target, q.profit.net_profit, q.profit.target) + '</div>';
                var onTimeCell;
                if (!q.project_count) {
                    onTimeCell = '<span class="muted">No completed projects</span>' +
                        (q.on_time.has_target ? ' <span class="muted">/ ' + fmtPct(q.on_time.target) + '</span>' : '');
                } else {
                    var otLvl = q.on_time.has_target ? onTimeLevel(q.on_time.rate, q.on_time.target) : null;
                    onTimeCell = '<span class="' + (otLvl ? (otLvl.key === 'hit' ? 'hit-y' : (otLvl.key === 'tolerable' ? 'hit-t' : 'hit-n')) : '') + '">' + fmtPct(q.on_time.rate) + '</span>' +
                        ' <span class="muted">/ ' + (q.on_time.has_target ? fmtPct(q.on_time.target) : '—') + '</span>' +
                        '<div class="muted small">' + q.on_time.on_time_count + ' of ' + q.project_count + ' on time</div>' +
                        '<div class="cell-pill">' + (otLvl ? reportPill(otLvl.label, otLvl.color, LEVEL_BG[otLvl.key]) : targetPill(false)) + '</div>';
                }
                var budgetStatus = budgetRangeStatus(q.budget.adherence_rate);
                // A quarter with no completed projects has nothing to measure — not "0% / well below estimate"
                var budgetCell = q.project_count
                    ? '<span style="color:' + budgetStatus.color + ';font-weight:800;">' + fmtPct(q.budget.adherence_rate) + '</span>' +
                      '<div class="cell-pill">' + reportPill(budgetStatus.label, budgetStatus.color, budgetStatus.bg) + '</div>'
                    : '<span class="muted">—</span>';

                return '<tr>' +
                    '<td><strong>' + q.label + '</strong></td>' +
                    '<td class="r">' + q.project_count + '</td>' +
                    '<td class="r">' + profitCell + '</td>' +
                    '<td class="r">' + (q.project_count ? fmtPct(q.profit.avg_margin) : '—') + '</td>' +
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
                var okCount = withProjects.filter(function (q) { return q.budget.adherence_rate <= 103; }).length;
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
                var overBudgetCount = withData.filter(function (q) { return q.budget.adherence_rate > 103; }).length;
                takeaways.push(overBudgetCount === 0
                    ? 'No quarter in this range went over budget (every quarter stayed at or below 103% of its estimate).'
                    : overBudgetCount + ' of ' + withData.length + ' quarter' + (withData.length === 1 ? '' : 's') + ' went over budget (above 103% of the estimate).');
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
            var onTimeSeries  = data.quarters.map(function (q) { return q.project_count ? q.on_time.rate : null; });
            var budgetSeries  = data.quarters.map(function (q) { return q.budget.adherence_rate; });

            // ── Cost & Revenue Breakdown: the "why" behind the profit numbers above ──
            var revenueSeries   = data.quarters.map(function (q) { return q.profit.revenue; });
            var matCostSeries   = data.quarters.map(function (q) { return q.profit.mat_cost; });
            var laborCostSeries = data.quarters.map(function (q) { return q.profit.labor_cost; });
            var overheadSeries  = data.quarters.map(function (q) { return q.profit.overhead_cost; });

            var totalRevenueAll  = revenueSeries.reduce(function (s, v) { return s + v; }, 0);
            var totalMatCostAll  = matCostSeries.reduce(function (s, v) { return s + v; }, 0);
            var totalLaborAll    = laborCostSeries.reduce(function (s, v) { return s + v; }, 0);
            var totalOverheadAll = overheadSeries.reduce(function (s, v) { return s + v; }, 0);

            function pctOfRevenue(v) {
                return totalRevenueAll > 0 ? (v / totalRevenueAll * 100).toFixed(1) + '% of revenue' : '—';
            }

            var costRows = data.quarters.map(function (q) {
                var totalCostQ = q.profit.mat_cost + q.profit.labor_cost + q.profit.overhead_cost;
                return '<tr>' +
                    '<td><strong>' + q.label + '</strong></td>' +
                    '<td class="r">' + fmtPeso(q.profit.revenue) + '</td>' +
                    '<td class="r">' + fmtPeso(q.profit.mat_cost) + '</td>' +
                    '<td class="r">' + fmtPeso(q.profit.labor_cost) + '</td>' +
                    '<td class="r">' + fmtPeso(q.profit.overhead_cost) + '</td>' +
                    '<td class="r">' + fmtPeso(totalCostQ) + '</td>' +
                    '<td class="r">' + fmtPeso(q.profit.net_profit) + '</td>' +
                '</tr>';
            }).join('');

            // ── Project-level detail (every project completed in the range) ──
            var projects       = data.projects || [];
            var totalRevenueP  = projects.reduce(function (s, p) { return s + p.revenue; }, 0);
            var overallMargin  = totalRevenueAll > 0 ? (totalProfit / totalRevenueAll * 100) : 0;
            var avgProjectSize = projects.length ? projects.reduce(function (s, p) { return s + p.contract; }, 0) / projects.length : 0;
            var delayed        = projects.filter(function (p) { return !p.on_time; });

            function deliveryCell(p) {
                return p.on_time
                    ? '<span class="hit-y">On time</span>'
                    : '<span class="hit-n">' + (p.delay_days ? p.delay_days + ' day' + (p.delay_days === 1 ? '' : 's') + ' late' : 'Late') + '</span>';
            }
            function adherenceCell(v) {
                if (v === null || v === undefined) return '—';
                var st = budgetRangeStatus(v);
                return '<span style="color:' + st.color + ';font-weight:800;">' + fmtPct(v) + '</span>';
            }

            var projectRows = projects.map(function (p) {
                var totalCost = p.mat_cost + p.labor_cost + p.overhead_cost;
                return '<tr>' +
                    '<td><strong>' + escapeHtml(p.name) + '</strong><div class="muted small">' + escapeHtml(p.client || '') + ' &middot; ' + p.code + '</div></td>' +
                    '<td>' + p.completed_on + '<div class="muted small">Due ' + (p.due_on || '—') + '</div></td>' +
                    '<td>' + deliveryCell(p) + '</td>' +
                    '<td class="r">' + fmtPeso(p.revenue) + '</td>' +
                    '<td class="r">' + fmtPeso(totalCost) + '</td>' +
                    '<td class="r"><strong>' + fmtPeso(p.net_profit) + '</strong></td>' +
                    '<td class="r">' + (p.margin === null ? '—' : fmtPct(p.margin)) + '</td>' +
                    '<td class="r">' + adherenceCell(p.adherence) + '</td>' +
                '</tr>';
            }).join('');

            if (delayed.length) {
                takeaways.push('Delayed ' + (delayed.length === 1 ? 'project' : 'projects') + ': ' + delayed.map(function (p) {
                    return escapeHtml(p.name) + (p.delay_days ? ' (' + p.delay_days + ' day' + (p.delay_days === 1 ? '' : 's') + ' late)' : '');
                }).join('; ') + '.');
            }
            if (projects.length >= 2) {
                var bestP = projects.reduce(function (a, b) { return (b.margin || -Infinity) > (a.margin || -Infinity) ? b : a; });
                if (bestP.margin !== null) takeaways.push('Most profitable project: ' + escapeHtml(bestP.name) + ' at a ' + fmtPct(bestP.margin) + ' margin (' + fmtPeso(bestP.net_profit) + ').');
            }

            var company = data.company || {};
            var contactLine = [company.address, company.phone, company.email].filter(Boolean).map(escapeHtml).join(' &nbsp;·&nbsp; ');

            // ── KPI scorecard: the same three KPIs and statuses as the dashboard, for the whole range ──
            function rangeTarget(pick, actualOf) {
                var targeted = data.quarters.filter(function (q) { return pick(q).has_target; });
                if (!targeted.length) return null;
                return {
                    actual: targeted.reduce(function (sum, q) { return sum + actualOf(q); }, 0),
                    target: targeted.reduce(function (sum, q) { return sum + (pick(q).target || 0); }, 0),
                    count:  targeted.length
                };
            }
            var profitRange = rangeTarget(function (q) { return q.profit; },  function (q) { return q.profit.net_profit; });
            // range target rate = average of the quarter target rates that were set; the actual rate is
            // recomputed from the summed counts (never an average of quarterly rates)
            var onTimeTargets = data.quarters.filter(function (q) { return q.on_time.has_target; }).map(function (q) { return q.on_time.target; });
            var onTimeRangeTarget = onTimeTargets.length ? onTimeTargets.reduce(function (s2, v) { return s2 + v; }, 0) / onTimeTargets.length : null;
            var onTimeRangeLvl = (onTimeRangeTarget !== null && totalProjects > 0) ? onTimeLevel(overallOnTimeRate, onTimeRangeTarget) : null;

            function scoreCard(name, value, sub, pillHtml, foot) {
                return '<div class="kpi">' +
                    '<div class="kpi-top"><span class="kpi-name">' + name + '</span>' + pillHtml + '</div>' +
                    '<div class="kpi-value">' + value + '</div>' +
                    '<div class="kpi-sub">' + sub + '</div>' +
                    (foot ? '<div class="kpi-foot">' + foot + '</div>' : '') +
                '</div>';
            }
            function achievementFoot(range, fmt) {
                if (!range) return 'No owner target was set in this range';
                var lvl = achievementLevel(range.actual, range.target);
                return 'Target ' + fmt(range.target) + ' &middot; ' + fmtPct(lvl.pct) + ' achieved';
            }
            var budgetOverall = budgetRangeStatus(overallAdherence);
            var scorecardHtml = totalProjects === 0 && !profitRange && onTimeRangeTarget === null
                ? '<div class="empty">No completed projects and no KPI targets in this range — nothing to score yet.</div>'
                : '<div class="kpis">' +
                    scoreCard('Project profit margin rate', overallMargin.toFixed(1) + '%', fmtPeso(totalProfit) + ' net profit',
                        profitRange ? targetPill(true, profitRange.actual, profitRange.target) : targetPill(false),
                        achievementFoot(profitRange, fmtPeso)) +
                    scoreCard('On-time delivery rate', totalProjects ? overallOnTimeRate.toFixed(1) + '%' : 'No completed projects',
                        totalProjects ? totalOnTime + ' of ' + totalProjects + ' project' + (totalProjects === 1 ? '' : 's') + ' on time' : 'The rate shows once a project is completed',
                        onTimeRangeLvl ? reportPill(onTimeRangeLvl.label, onTimeRangeLvl.color, LEVEL_BG[onTimeRangeLvl.key]) : (onTimeRangeTarget === null ? targetPill(false) : ''),
                        onTimeRangeTarget === null ? 'No owner target was set in this range'
                            : 'Target rate ' + fmtPct(onTimeRangeTarget) + (onTimeRangeLvl ? ' &middot; ' + (onTimeRangeLvl.points >= 0 ? '+' : '−') + fmtPts(onTimeRangeLvl.points) : '')) +
                    scoreCard('Budget adherence rate', totalProjects ? overallAdherence.toFixed(1) + '%' : '—',
                        totalProjects ? fmtPeso(totalActualCost) + ' spent of ' + fmtPeso(totalEstBudget) + ' budget' : 'No completed projects',
                        totalProjects ? reportPill(budgetOverall.label, budgetOverall.color, budgetOverall.bg) : '',
                        'Healthy range 90% – 100%') +
                  '</div>';

            var periodText = data.from_label + (data.from_label !== data.to_label ? ' – ' + data.to_label : '');
            var sectionNo = 0;
            function section(title) { sectionNo++; return '<div class="section-title"><span>' + sectionNo + '</span>' + title + '</div>'; }

            return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>KPI Report — ' + periodText + '</title>' +
                '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"><\/script>' +
                '<style>' +
                    '*{box-sizing:border-box;}' +
                    'body{font-family:"Segoe UI",Arial,Helvetica,sans-serif;color:#222;margin:0;padding:28px 34px;background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
                    '.toolbar{position:sticky;top:0;z-index:5;display:flex;justify-content:space-between;align-items:center;gap:12px;margin:-28px -34px 22px;padding:12px 34px;background:#222;color:#fff;font-size:13px;}' +
                    '.toolbar button{font:inherit;font-weight:700;border:none;border-radius:999px;padding:8px 16px;cursor:pointer;background:#fff;color:#222;}' +
                    '.report-head{display:flex;justify-content:space-between;align-items:center;gap:20px;border-bottom:3px solid #222;padding-bottom:14px;margin-bottom:22px;}' +
                    '.brand{display:flex;align-items:center;gap:14px;}' +
                    '.brand img{width:52px;height:52px;border-radius:50%;}' +
                    'h1{font-size:19px;margin:0 0 2px;letter-spacing:-.2px;}' +
                    '.report-name{font-size:14px;font-weight:800;color:#555;}' +
                    '.company{font-size:11px;color:#777;margin-top:3px;}' +
                    '.head-meta{text-align:right;font-size:11.5px;color:#666;line-height:1.7;white-space:nowrap;}' +
                    '.head-meta strong{color:#222;}' +
                    '.section{margin-bottom:24px;page-break-inside:avoid;}' +
                    '.section-title{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#222;margin:0 0 10px;}' +
                    '.section-title span{display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#222;color:#fff;font-size:10.5px;letter-spacing:0;}' +
                    '.narrative{background:#f6f6f6;border-left:4px solid #222;border-radius:0 10px 10px 0;padding:13px 16px;font-size:13px;line-height:1.65;}' +
                    '.kpis{display:flex;gap:12px;}' +
                    '.kpi{flex:1;border:1px solid #e2e2e2;border-radius:12px;padding:14px 16px;}' +
                    '.kpi-top{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px;}' +
                    '.kpi-name{font-size:11.5px;font-weight:800;color:#444;}' +
                    '.kpi-value{font-size:30px;font-weight:900;letter-spacing:-.8px;line-height:1.1;}' +
                    '.kpi-sub{font-size:11.5px;color:#666;margin-top:3px;}' +
                    '.kpi-foot{font-size:11px;color:#555;margin-top:10px;padding-top:8px;border-top:1px dashed #ddd;}' +
                    '.pill{display:inline-block;font-size:10px;font-weight:800;padding:3px 9px;border-radius:999px;white-space:nowrap;}' +
                    '.cell-pill{margin-top:4px;}' +
                    '.stats{display:flex;gap:12px;}' +
                    '.stat{flex:1;border:1px solid #e2e2e2;border-radius:12px;padding:12px 14px;}' +
                    '.stat-label{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:#777;font-weight:800;margin-bottom:5px;}' +
                    '.stat-value{font-size:20px;font-weight:900;color:#222;}' +
                    '.stat-sub{font-size:11px;color:#777;margin-top:3px;}' +
                    '.charts{display:flex;gap:12px;}' +
                    '.chart-box{flex:1;border:1px solid #e2e2e2;border-radius:12px;padding:12px 14px;overflow:hidden;}' +
                    '.chart-title{font-size:11.5px;font-weight:800;color:#333;margin-bottom:8px;}' +
                    '.chart-canvas{position:relative;height:170px;width:100%;}' +
                    '.chart-canvas canvas{position:absolute;top:0;left:0;width:100% !important;height:100% !important;}' +
                    '.chart-canvas img{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:contain;}' +
                    '.takeaways{background:#EAF0FF;border:1px solid rgba(42,78,170,.2);border-radius:12px;padding:14px 18px;}' +
                    '.takeaways ul{margin:0 0 10px 18px;padding:0;font-size:12.5px;line-height:1.7;color:#222;}' +
                    '.recommendation{font-size:12.5px;font-weight:800;color:#2A4EAA;padding-top:8px;border-top:1px solid rgba(42,78,170,.2);}' +
                    'table{width:100%;border-collapse:collapse;font-size:12px;}' +
                    'th,td{border-bottom:1px solid #e6e6e6;padding:8px 10px;text-align:left;vertical-align:top;}' +
                    'th{background:#222;color:#fff;font-size:10px;text-transform:uppercase;letter-spacing:.05em;}' +
                    'thead th:first-child{border-radius:8px 0 0 0;} thead th:last-child{border-radius:0 8px 0 0;}' +
                    '.r{text-align:right;} .c{text-align:center;}' +
                    '.hit-y{color:#207A3A;font-weight:800;} .hit-t{color:#A16207;font-weight:800;} .hit-n{color:#B42318;font-weight:800;}' +
                    '.muted{color:#999;font-weight:400;}' +
                    'tbody tr:nth-child(even){background:#fafafa;}' +
                    'tfoot td{font-weight:800;background:#f0f0f0;border-top:2px solid #222;}' +
                    '.small{font-size:10.5px;margin-top:2px;}' +
                    '.empty{padding:18px;text-align:center;color:#999;font-size:12.5px;border:1px dashed #ddd;border-radius:10px;}' +
                    '.report-foot{margin-top:28px;padding-top:10px;border-top:1px solid #ddd;display:flex;justify-content:space-between;font-size:10.5px;color:#999;}' +
                    '@page{margin:12mm;}' +
                    '@media print{body{padding:0;} .toolbar{display:none;} .charts,.stats,.kpis{page-break-inside:avoid;} thead{display:table-header-group;} tr{page-break-inside:avoid;}}' +
                '</style></head><body>' +
                '<div class="toolbar"><span>KPI Report &middot; ' + periodText + '</span><button type="button" onclick="window.print()">Print / Save as PDF</button></div>' +
                '<div class="report-head">' +
                    '<div class="brand">' +
                        '<img src="' + REPORT_LOGO_URL + '" alt="">' +
                        '<div>' +
                            '<h1>GMD South Phils Metal Fabrication Works</h1>' +
                            '<div class="report-name">Quarterly KPI Report</div>' +
                            (contactLine ? '<div class="company">' + contactLine + '</div>' : '') +
                        '</div>' +
                    '</div>' +
                    '<div class="head-meta">' +
                        'Period: <strong>' + periodText + '</strong> (' + data.quarters.length + ' quarter' + (data.quarters.length === 1 ? '' : 's') + ')<br>' +
                        'Prepared by: <strong>' + escapeHtml(data.prepared_by || 'Administrator') + '</strong><br>' +
                        'Generated: ' + data.generated_at +
                    '</div>' +
                '</div>' +

                '<div class="section">' + section('Executive summary') + '<div class="narrative">' + narrative + '</div></div>' +

                '<div class="section">' + section('KPI scorecard') + scorecardHtml + '</div>' +

                '<div class="section">' +
                    '<div class="stats">' +
                        '<div class="stat"><div class="stat-label">Projects completed</div><div class="stat-value">' + totalProjects + '</div><div class="stat-sub">' + delayed.length + ' delivered late</div></div>' +
                        '<div class="stat"><div class="stat-label">Profit target hit</div><div class="stat-value">' + hitSummary(function (q) { return q.profit; }) + '</div><div class="stat-sub">Quarters at 100% or more of target</div></div>' +
                        '<div class="stat"><div class="stat-label">On-time target hit</div><div class="stat-value">' + hitSummary(function (q) { return q.on_time; }) + '</div><div class="stat-sub">Quarters at or above the target rate</div></div>' +
                        '<div class="stat"><div class="stat-label">Average contract value</div><div class="stat-value">' + (projects.length ? fmtPeso(avgProjectSize) : '—') + '</div><div class="stat-sub">Per completed project</div></div>' +
                    '</div>' +
                '</div>' +

                '<div class="section">' + section('Performance trends') +
                    '<div class="charts">' +
                        '<div class="chart-box"><div class="chart-title">Net profit (₱)</div><div class="chart-canvas"><canvas id="repChartProfit"></canvas></div></div>' +
                        '<div class="chart-box"><div class="chart-title">On-time delivery rate (%)</div><div class="chart-canvas"><canvas id="repChartOnTime"></canvas></div></div>' +
                        '<div class="chart-box"><div class="chart-title">Budget adherence (%)</div><div class="chart-canvas"><canvas id="repChartBudget"></canvas></div></div>' +
                    '</div>' +
                '</div>' +

                '<div class="section">' + section('Key takeaways') +
                    '<div class="takeaways">' +
                        '<ul>' + takeaways.map(function (t) { return '<li>' + t + '</li>'; }).join('') + '</ul>' +
                        '<div class="recommendation">→ ' + recommendation + '</div>' +
                    '</div>' +
                '</div>' +

                '<div class="section">' + section('Cost &amp; revenue breakdown') +
                    '<div class="stats" style="margin-bottom:12px;">' +
                        '<div class="stat"><div class="stat-label">Total revenue</div><div class="stat-value">' + fmtPeso(totalRevenueAll) + '</div></div>' +
                        '<div class="stat"><div class="stat-label">Material cost</div><div class="stat-value">' + fmtPeso(totalMatCostAll) + '</div><div class="stat-sub">' + pctOfRevenue(totalMatCostAll) + '</div></div>' +
                        '<div class="stat"><div class="stat-label">Labor cost</div><div class="stat-value">' + fmtPeso(totalLaborAll) + '</div><div class="stat-sub">' + pctOfRevenue(totalLaborAll) + '</div></div>' +
                        '<div class="stat"><div class="stat-label">Overhead cost</div><div class="stat-value">' + fmtPeso(totalOverheadAll) + '</div><div class="stat-sub">' + pctOfRevenue(totalOverheadAll) + '</div></div>' +
                    '</div>' +
                    '<div class="chart-box" style="margin-bottom:12px;"><div class="chart-title">Cost composition vs revenue by quarter</div><div class="chart-canvas" style="height:220px;"><canvas id="repChartCost"></canvas></div></div>' +
                    '<table><thead><tr>' +
                        '<th>Quarter</th><th class="r">Revenue</th><th class="r">Material</th><th class="r">Labor</th><th class="r">Overhead</th><th class="r">Total cost</th><th class="r">Net profit</th>' +
                    '</tr></thead><tbody>' + costRows + '</tbody>' +
                    '<tfoot><tr><td>Total</td>' +
                        '<td class="r">' + fmtPeso(totalRevenueAll) + '</td>' +
                        '<td class="r">' + fmtPeso(totalMatCostAll) + '</td>' +
                        '<td class="r">' + fmtPeso(totalLaborAll) + '</td>' +
                        '<td class="r">' + fmtPeso(totalOverheadAll) + '</td>' +
                        '<td class="r">' + fmtPeso(totalMatCostAll + totalLaborAll + totalOverheadAll) + '</td>' +
                        '<td class="r">' + fmtPeso(totalProfit) + '</td>' +
                    '</tr></tfoot></table>' +
                '</div>' +

                '<div class="section">' + section('Quarter-by-quarter detail') +
                    '<table><thead><tr>' +
                        '<th>Quarter</th><th class="r">Projects</th>' +
                        '<th class="r">Net profit (actual / target)</th>' +
                        '<th class="r">Margin</th>' +
                        '<th class="r">On-time (actual / target)</th>' +
                        '<th class="r">Budget adherence</th>' +
                    '</tr></thead><tbody>' + rows + '</tbody>' +
                    '<tfoot><tr><td>Total / overall</td><td class="r">' + totalProjects + '</td>' +
                        '<td class="r">' + fmtPeso(totalProfit) + '</td>' +
                        '<td class="r">' + overallMargin.toFixed(1) + '%</td>' +
                        '<td class="r">' + totalOnTime + ' (' + overallOnTimeRate.toFixed(1) + '%)</td>' +
                        '<td class="r">' + overallAdherence.toFixed(1) + '%</td>' +
                    '</tr></tfoot></table>' +
                '</div>' +

                '<div class="section">' + section('Completed projects') +
                (projects.length
                    ? '<table><thead><tr>' +
                        '<th>Project / client</th><th>Completed</th><th>Delivery</th><th class="r">Revenue</th><th class="r">Total cost</th><th class="r">Net profit</th><th class="r">Margin</th><th class="r">Budget used</th>' +
                      '</tr></thead><tbody>' + projectRows + '</tbody>' +
                      '<tfoot><tr><td colspan="3">' + projects.length + ' project' + (projects.length === 1 ? '' : 's') + '</td>' +
                        '<td class="r">' + fmtPeso(totalRevenueP) + '</td>' +
                        '<td class="r">' + fmtPeso(totalMatCostAll + totalLaborAll + totalOverheadAll) + '</td>' +
                        '<td class="r">' + fmtPeso(totalProfit) + '</td>' +
                        '<td class="r">' + overallMargin.toFixed(1) + '%</td>' +
                        '<td class="r">' + overallAdherence.toFixed(1) + '%</td>' +
                      '</tr></tfoot></table>'
                    : '<div class="empty">No projects were completed in this period.</div>') +
                '</div>' +

                '<div class="report-foot"><span>GMD South Phils Project Management System &middot; Quarterly KPI Report</span><span>Generated ' + data.generated_at + '</span></div>' +

                '<script>' +
                    'window.addEventListener("load", function () {' +
                        'var labels = ' + JSON.stringify(quarterLabels) + ';' +
                        'var profitData = ' + JSON.stringify(profitSeries) + ';' +
                        'var onTimeData = ' + JSON.stringify(onTimeSeries) + ';' +
                        'var budgetData = ' + JSON.stringify(budgetSeries) + ';' +
                        'var revenueData = ' + JSON.stringify(revenueSeries) + ';' +
                        'var matCostData = ' + JSON.stringify(matCostSeries) + ';' +
                        'var laborCostData = ' + JSON.stringify(laborCostSeries) + ';' +
                        'var overheadData = ' + JSON.stringify(overheadSeries) + ';' +
                        'var peso = function (v) { return "₱" + (Math.abs(v) >= 1000 ? Math.round(v / 1000) + "k" : v); };' +
                        'var tick = { font:{size:11}, color:"#555" };' +
                        // Static, sharp drawings (no animation, 2x resolution) that are then swapped for images,
                        // so printing scales them in proportion instead of stretching a live canvas.
                        'var base = { responsive:true, maintainAspectRatio:false, animation:false, devicePixelRatio:2, layout:{padding:{top:12,right:10,bottom:4,left:4}} };' +
                        'var opts = function (formatter) { return Object.assign({}, base, { plugins:{legend:{display:false}}, ' +
                            'scales:{ x:{ grid:{display:false}, ticks:tick }, y:{ beginAtZero:true, grid:{color:"rgba(0,0,0,.06)"}, ticks:Object.assign({ callback:formatter }, tick) } } }); };' +
                        'var freeze = function (chart) {' +
                            'var img = new Image(); img.src = chart.toBase64Image("image/png", 1); img.alt = "";' +
                            'var canvas = chart.canvas; canvas.parentNode.replaceChild(img, canvas); chart.destroy();' +
                        '};' +
                        'if (window.Chart) {' +
                            'freeze(new Chart(document.getElementById("repChartProfit"), { type:"line", data:{ labels:labels, datasets:[{ data:profitData, borderColor:"#207A3A", backgroundColor:"rgba(32,122,58,.12)", fill:true, tension:0, pointRadius:4, pointBackgroundColor:"#207A3A", borderWidth:2 }] }, options: opts(peso) }));' +
                            'freeze(new Chart(document.getElementById("repChartOnTime"), { type:"line", data:{ labels:labels, datasets:[{ data:onTimeData, borderColor:"#2A4EAA", backgroundColor:"rgba(42,78,170,.12)", fill:true, tension:0, pointRadius:4, pointBackgroundColor:"#2A4EAA", borderWidth:2, spanGaps:true }] }, options: opts(function (v) { return v + "%"; }) }));' +
                            'freeze(new Chart(document.getElementById("repChartBudget"), { type:"line", data:{ labels:labels, datasets:[{ data:budgetData, borderColor:"#8A6100", backgroundColor:"rgba(138,97,0,.12)", fill:true, tension:0, pointRadius:4, pointBackgroundColor:"#8A6100", borderWidth:2 }] }, options: opts(function (v) { return v + "%"; }) }));' +
                            // Cost bars stack per quarter; revenue is its own line (not stacked), drawn on top and centred on each bar
                            'freeze(new Chart(document.getElementById("repChartCost"), { data:{ labels:labels, datasets:[' +
                                '{ type:"line", label:"Revenue", data:revenueData, borderColor:"#207A3A", backgroundColor:"#207A3A", tension:0, pointRadius:5, pointBackgroundColor:"#ffffff", pointBorderWidth:2, borderWidth:2.5, order:0 },' +
                                '{ type:"bar", label:"Material", data:matCostData, backgroundColor:"#2A4EAA", stack:"cost", order:1, maxBarThickness:70 },' +
                                '{ type:"bar", label:"Labor", data:laborCostData, backgroundColor:"#C08A1A", stack:"cost", order:1, maxBarThickness:70 },' +
                                '{ type:"bar", label:"Overhead", data:overheadData, backgroundColor:"#B42318", stack:"cost", order:1, maxBarThickness:70 }' +
                            '] }, options: Object.assign({}, base, { ' +
                                'plugins:{ legend:{ display:true, position:"bottom", labels:{ font:{size:11}, boxWidth:12, padding:14 } } }, ' +
                                'scales:{ x:{ stacked:true, grid:{display:false}, ticks:tick }, y:{ stacked:true, beginAtZero:true, grid:{color:"rgba(0,0,0,.06)"}, ticks:Object.assign({ callback:peso }, tick) } } }) }));' +
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
