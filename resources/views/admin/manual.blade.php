<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Manual | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        .manual-layout { display: flex; gap: 20px; align-items: flex-start; }

        .manual-nav {
            width: 270px; flex-shrink: 0;
            background: var(--white); border: 1px solid var(--border); border-radius: 20px;
            padding: 14px; box-shadow: 0 10px 24px var(--shadow);
            position: sticky; top: 20px; max-height: calc(100vh - 40px); overflow-y: auto;
        }
        .manual-nav-search {
            display: flex; align-items: center; gap: 8px;
            background: var(--cream-soft); border: 1px solid var(--border); border-radius: 12px;
            padding: 9px 12px; margin-bottom: 10px;
        }
        .manual-nav-search i { width: 14px; height: 14px; color: var(--muted); flex-shrink: 0; }
        .manual-nav-search input { border: none; background: transparent; outline: none; font-size: 13px; width: 100%; color: var(--dark); }

        .manual-nav-section-label {
            font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .07em;
            color: var(--muted); padding: 12px 10px 6px;
        }
        .manual-nav-section-label:first-of-type { padding-top: 2px; }

        .manual-nav-link {
            width: 100%; display: flex; align-items: center; gap: 10px;
            padding: 9px 10px; border: none; background: none; border-radius: 12px;
            font-size: 13px; font-weight: 700; color: var(--muted); cursor: pointer; text-align: left;
        }
        .manual-nav-link i { width: 16px; height: 16px; flex-shrink: 0; }
        .manual-nav-link:hover { background: var(--cream-deep); color: var(--dark); }
        .manual-nav-link.active { background: var(--dark); color: #fff; }
        .manual-nav-link.sub { padding-left: 30px; font-weight: 600; font-size: 12.5px; }

        .manual-content-wrap { flex: 1; min-width: 0; }
        .manual-panel { display: none; }
        .manual-panel.active { display: block; }

        .manual-topic-header { display: flex; align-items: center; gap: 12px; margin-bottom: 6px; }
        .manual-topic-icon {
            width: 42px; height: 42px; border-radius: 13px; background: var(--cream-soft);
            display: flex; align-items: center; justify-content: center; color: var(--dark); flex-shrink: 0;
        }
        .manual-topic-icon i { width: 20px; height: 20px; }
        .manual-topic-header h2 { font-size: 18px; font-weight: 900; color: var(--dark); margin: 0; }
        .manual-tagline { font-size: 13.5px; color: var(--muted); margin: 0 0 20px; line-height: 1.6; }

        .manual-content h3 { font-size: 14px; font-weight: 800; color: var(--dark); margin: 20px 0 8px; }
        .manual-content h3:first-child { margin-top: 0; }
        .manual-content p { font-size: 13.5px; color: var(--dark); line-height: 1.7; margin: 0 0 10px; }
        .manual-content ol, .manual-content ul {
            margin: 0 0 14px 20px; padding: 0; font-size: 13.5px; line-height: 1.75; color: var(--dark);
        }
        .manual-content li { margin-bottom: 6px; }
        .manual-content strong { color: var(--dark); }

        .manual-content table { width: 100%; border-collapse: collapse; margin: 12px 0 18px; font-size: 12.5px; }
        .manual-content th {
            background: var(--cream-soft); text-align: left; padding: 8px 10px;
            font-weight: 800; text-transform: uppercase; font-size: 10.5px;
            letter-spacing: .05em; color: var(--muted); border-bottom: 2px solid var(--border);
        }
        .manual-content td { padding: 8px 10px; border-bottom: 1px solid var(--border); vertical-align: top; }

        .manual-note { display: flex; align-items: flex-start; gap: 10px; margin: 4px 0 16px; }
        .manual-note i { margin-top: 2px; flex-shrink: 0; }

        /* ── Step-by-step guide look ── */
        .manual-guide-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--cream-soft); border: 1px solid var(--border); border-radius: 999px;
            padding: 4px 12px; font-size: 11px; font-weight: 800; color: var(--muted);
            text-transform: uppercase; letter-spacing: .05em; margin-bottom: 18px;
        }
        .manual-guide-badge i { width: 12px; height: 12px; }

        .manual-steps { position: relative; margin: 0 0 6px; }
        .manual-step { display: flex; gap: 16px; position: relative; padding-bottom: 26px; }
        .manual-step:last-child { padding-bottom: 0; }
        .manual-step:not(:last-child)::before {
            content: ''; position: absolute; left: 17px; top: 38px; bottom: -6px; width: 2px;
            background: var(--border);
        }
        .manual-step-num {
            width: 36px; height: 36px; border-radius: 50%; background: var(--dark); color: #fff;
            display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 15px;
            flex-shrink: 0; z-index: 1;
        }
        .manual-step-body { flex: 1; min-width: 0; padding-top: 6px; }
        .manual-step-body h3 { font-size: 14.5px; font-weight: 800; color: var(--dark); margin: 0 0 8px; }
        .manual-step-body p:last-child,
        .manual-step-body ul:last-child,
        .manual-step-body table:last-child,
        .manual-step-body .manual-note:last-child { margin-bottom: 0; }

        .manual-done-note {
            display: flex; align-items: center; gap: 10px; margin-top: 22px;
            padding: 14px 16px; background: #E7F6EC; border: 1px solid #86efac; border-radius: 14px;
            font-size: 13px; font-weight: 700; color: #14532d;
        }
        .manual-done-note i { width: 18px; height: 18px; flex-shrink: 0; }

        @media (max-width: 900px) {
            .manual-layout { flex-direction: column; }
            .manual-nav { width: 100%; position: static; max-height: none; }
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
                    <h1>User Manual</h1>
                    <p>Simple guides for using every part of the admin panel.</p>
                </div>
            </div>

            <div class="manual-layout">

                {{-- ── NAV ── --}}
                <aside class="manual-nav">
                    <div class="manual-nav-search">
                        <i data-lucide="search"></i>
                        <input type="text" id="manualNavSearch" placeholder="Search topics...">
                    </div>

                    <div class="manual-nav-section-label">Overview</div>
                    <button type="button" class="manual-nav-link active" data-topic="dashboard">
                        <i data-lucide="layout-dashboard"></i><span>Dashboard</span>
                    </button>
                    <button type="button" class="manual-nav-link" data-topic="kpi-dashboard">
                        <i data-lucide="gauge"></i><span>KPI Dashboard</span>
                    </button>
                    <button type="button" class="manual-nav-link sub" data-topic="set-targets">
                        <span>— Set KPI targets</span>
                    </button>
                    <button type="button" class="manual-nav-link sub" data-topic="generate-report">
                        <span>— Generate a report</span>
                    </button>

                    <div class="manual-nav-section-label">Project Management</div>
                    <button type="button" class="manual-nav-link" data-topic="projects">
                        <i data-lucide="folder-kanban"></i><span>Projects</span>
                    </button>
                    <button type="button" class="manual-nav-link sub" data-topic="add-project">
                        <span>— Add a new project</span>
                    </button>
                    <button type="button" class="manual-nav-link sub" data-topic="track-progress">
                        <span>— Track project progress</span>
                    </button>
                    <button type="button" class="manual-nav-link" data-topic="quotation-requests">
                        <i data-lucide="inbox"></i><span>Quotation Requests</span>
                    </button>
                    <button type="button" class="manual-nav-link" data-topic="project-quotations">
                        <i data-lucide="package"></i><span>Project Quotations</span>
                    </button>
                    <button type="button" class="manual-nav-link" data-topic="materials">
                        <i data-lucide="clipboard-list"></i><span>Materials</span>
                    </button>

                    <div class="manual-nav-section-label">Financial Management</div>
                    <button type="button" class="manual-nav-link" data-topic="payments">
                        <i data-lucide="credit-card"></i><span>Payments</span>
                    </button>
                    <button type="button" class="manual-nav-link" data-topic="monthly-expenses">
                        <i data-lucide="receipt"></i><span>Monthly Expenses</span>
                    </button>
                    <button type="button" class="manual-nav-link" data-topic="revolving-fund">
                        <i data-lucide="refresh-cw"></i><span>Revolving Fund</span>
                    </button>

                    <div class="manual-nav-section-label">People</div>
                    <button type="button" class="manual-nav-link" data-topic="employees">
                        <i data-lucide="users"></i><span>Employees</span>
                    </button>
                    <button type="button" class="manual-nav-link" data-topic="clients">
                        <i data-lucide="building-2"></i><span>Clients</span>
                    </button>

                    <div class="manual-nav-section-label">Account</div>
                    <button type="button" class="manual-nav-link" data-topic="settings">
                        <i data-lucide="settings"></i><span>Settings</span>
                    </button>
                </aside>

                {{-- ── CONTENT ── --}}
                <div class="manual-content-wrap pv-card">

                    {{-- DASHBOARD --}}
                    <div class="manual-panel active" data-panel="dashboard">
                        <div class="manual-topic-header">
                            <div class="manual-topic-icon"><i data-lucide="layout-dashboard"></i></div>
                            <h2>Dashboard</h2>
                        </div>
                        <p class="manual-tagline">The first page you see when you log in — a quick snapshot of the whole business.</p>
                        <div class="manual-content">
                            <p>It shows things like revenue, active projects, and a list of things that need your attention — overdue projects, unpaid balances, and unread messages. Use it to see at a glance what's going well and what needs action, then click into the relevant page to handle it.</p>
                        </div>
                    </div>

                    {{-- KPI DASHBOARD --}}
                    <div class="manual-panel" data-panel="kpi-dashboard">
                        <div class="manual-topic-header">
                            <div class="manual-topic-icon"><i data-lucide="gauge"></i></div>
                            <h2>KPI Dashboard</h2>
                        </div>
                        <p class="manual-tagline">Shows how the business is performing — profit, on-time delivery, and budget — compared to the goals you set.</p>
                        <div class="manual-content">
                            <ul>
                                <li>Switch between Month, Quarter, and Year view using the picker at the top.</li>
                                <li>Three tabs: <strong>KPI scorecard</strong> (current numbers vs. targets), <strong>Performance trend</strong> (charts over time), and <strong>SMA forecast</strong> (a simple prediction for next quarter).</li>
                                <li>Use <strong>Set targets</strong> to define your goals, and <strong>Generate report</strong> to create a printable summary — see the two guides below.</li>
                            </ul>
                        </div>
                    </div>

                    {{-- SET TARGETS --}}
                    <div class="manual-panel" data-panel="set-targets">
                        <div class="manual-topic-header">
                            <div class="manual-topic-icon"><i data-lucide="target"></i></div>
                            <h2>Set KPI Targets</h2>
                        </div>
                        <p class="manual-tagline">Tell the system what profit and delivery goals to measure each quarter against.</p>
                        <div class="manual-content">
                            <div class="manual-guide-badge"><i data-lucide="list-ordered"></i> 5-step guide</div>
                            <div class="manual-steps">
                                <div class="manual-step">
                                    <div class="manual-step-num">1</div>
                                    <div class="manual-step-body">
                                        <h3>Open the KPI Dashboard</h3>
                                        <p>Click <strong>KPI Dashboard</strong> in the sidebar.</p>
                                    </div>
                                </div>
                                <div class="manual-step">
                                    <div class="manual-step-num">2</div>
                                    <div class="manual-step-body">
                                        <h3>Click "Set targets"</h3>
                                        <p>It's the button at the top of the page, next to "Generate report."</p>
                                    </div>
                                </div>
                                <div class="manual-step">
                                    <div class="manual-step-num">3</div>
                                    <div class="manual-step-body">
                                        <h3>Pick the quarter</h3>
                                        <p>Use the dropdown to choose which quarter you're setting goals for.</p>
                                        <div class="manual-note alert-banner warning">
                                            <i data-lucide="alert-triangle"></i>
                                            <span>If that quarter already ended, its targets are locked and can't be changed.</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="manual-step">
                                    <div class="manual-step-num">4</div>
                                    <div class="manual-step-body">
                                        <h3>Fill in your monthly goals</h3>
                                        <p>Enter a peso profit goal and an on-time-delivery number for each of the quarter's three months. The totals below add these up for you automatically — you don't need to calculate them yourself.</p>
                                    </div>
                                </div>
                                <div class="manual-step">
                                    <div class="manual-step-num">5</div>
                                    <div class="manual-step-body">
                                        <h3>Save</h3>
                                        <p>Click <strong>Save targets</strong>.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="manual-done-note">
                                <i data-lucide="check-circle-2"></i>
                                Done — the dashboard updates right away.
                            </div>
                        </div>
                    </div>

                    {{-- GENERATE REPORT --}}
                    <div class="manual-panel" data-panel="generate-report">
                        <div class="manual-topic-header">
                            <div class="manual-topic-icon"><i data-lucide="file-text"></i></div>
                            <h2>Generate a KPI Report</h2>
                        </div>
                        <p class="manual-tagline">Turns your KPI data into a printable report you can save as a PDF.</p>
                        <div class="manual-content">
                            <div class="manual-guide-badge"><i data-lucide="list-ordered"></i> 5-step guide</div>
                            <div class="manual-steps">
                                <div class="manual-step">
                                    <div class="manual-step-num">1</div>
                                    <div class="manual-step-body">
                                        <h3>Open the KPI Dashboard</h3>
                                        <p>Click <strong>KPI Dashboard</strong> in the sidebar.</p>
                                    </div>
                                </div>
                                <div class="manual-step">
                                    <div class="manual-step-num">2</div>
                                    <div class="manual-step-body">
                                        <h3>Click "Generate report"</h3>
                                        <p>It's the button at the top of the page.</p>
                                    </div>
                                </div>
                                <div class="manual-step">
                                    <div class="manual-step-num">3</div>
                                    <div class="manual-step-body">
                                        <h3>Pick a date range</h3>
                                        <p>Choose a starting quarter and an ending quarter — that's the period the report will cover.</p>
                                    </div>
                                </div>
                                <div class="manual-step">
                                    <div class="manual-step-num">4</div>
                                    <div class="manual-step-body">
                                        <h3>Click "Generate"</h3>
                                        <p>A new tab opens with the finished report: a plain-language summary, key numbers, trend charts, and a table comparing each quarter's actual results to its targets.</p>
                                    </div>
                                </div>
                                <div class="manual-step">
                                    <div class="manual-step-num">5</div>
                                    <div class="manual-step-body">
                                        <h3>Print or save as PDF</h3>
                                        <p>The print window opens automatically. Choose your printer to print it, or choose "Save as PDF" to keep a digital copy.</p>
                                        <div class="manual-note alert-banner info">
                                            <i data-lucide="info"></i>
                                            <span>If you get an error instead of a report, you probably picked too wide a range — try a shorter one.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- PROJECTS --}}
                    <div class="manual-panel" data-panel="projects">
                        <div class="manual-topic-header">
                            <div class="manual-topic-icon"><i data-lucide="folder-kanban"></i></div>
                            <h2>Projects</h2>
                        </div>
                        <p class="manual-tagline">Every project, grouped by client. This is where projects live.</p>
                        <div class="manual-content">
                            <ul>
                                <li>The main list shows each client and how many active, completed, and archived projects they have.</li>
                                <li>Click a client's row to see their individual projects.</li>
                                <li>Click a project to open its details page — schedule, phase tracker, assigned employees, and progress updates.</li>
                                <li>Use <strong>+ Add Project</strong> at the top to create a new one — see the guide below.</li>
                            </ul>
                        </div>
                    </div>

                    {{-- ADD A NEW PROJECT --}}
                    <div class="manual-panel" data-panel="add-project">
                        <div class="manual-topic-header">
                            <div class="manual-topic-icon"><i data-lucide="plus-circle"></i></div>
                            <h2>Add a New Project</h2>
                        </div>
                        <p class="manual-tagline">Create a new project for a client, step by step.</p>
                        <div class="manual-content">
                            <div class="manual-guide-badge"><i data-lucide="list-ordered"></i> 5-step guide</div>
                            <div class="manual-steps">
                                <div class="manual-step">
                                    <div class="manual-step-num">1</div>
                                    <div class="manual-step-body">
                                        <h3>Go to Projects</h3>
                                        <p>Click <strong>Projects</strong> in the sidebar, then click <strong>+ Add Project</strong>.</p>
                                    </div>
                                </div>
                                <div class="manual-step">
                                    <div class="manual-step-num">2</div>
                                    <div class="manual-step-body">
                                        <h3>Pick the client</h3>
                                        <p>Search for the client and click their card, then click <strong>Continue</strong>.</p>
                                    </div>
                                </div>
                                <div class="manual-step">
                                    <div class="manual-step-num">3</div>
                                    <div class="manual-step-body">
                                        <h3>Pick a starting point</h3>
                                        <p>Choose <strong>Start from Scratch</strong> for a brand-new setup, or pick a saved template to reuse specs from a past project. Click <strong>Continue</strong>.</p>
                                        <div class="manual-note alert-banner info">
                                            <i data-lucide="info"></i>
                                            <span>Every project you build from scratch is automatically saved as a template, so it shows up here for next time — no extra step needed.</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="manual-step">
                                    <div class="manual-step-num">4</div>
                                    <div class="manual-step-body">
                                        <h3>Fill in the details</h3>
                                        <ul>
                                            <li><strong>Project Name</strong> — pick from the list, or choose "Others" to type your own.</li>
                                            <li><strong>Specifications</strong> — type of tank, shape, quantity, dimensions, and capacity.</li>
                                            <li><strong>Materials list</strong> (only if you picked a template) — adjust or add materials; this can also be done later.</li>
                                            <li><strong>Schedule</strong> — start and end dates.</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="manual-step">
                                    <div class="manual-step-num">5</div>
                                    <div class="manual-step-body">
                                        <h3>Save</h3>
                                        <p>Click <strong>Save Project</strong>.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="manual-done-note">
                                <i data-lucide="check-circle-2"></i>
                                Done — you'll land on that client's project list with a confirmation message.
                            </div>
                        </div>
                    </div>

                    {{-- TRACK PROGRESS --}}
                    <div class="manual-panel" data-panel="track-progress">
                        <div class="manual-topic-header">
                            <div class="manual-topic-icon"><i data-lucide="activity"></i></div>
                            <h2>Track Project Progress</h2>
                        </div>
                        <p class="manual-tagline">See where a project stands, and move it forward one step at a time.</p>
                        <div class="manual-content">
                            <div class="manual-guide-badge"><i data-lucide="list-ordered"></i> 5-step guide</div>
                            <div class="manual-steps">
                                <div class="manual-step">
                                    <div class="manual-step-num">1</div>
                                    <div class="manual-step-body">
                                        <h3>Open the project</h3>
                                        <p>Go to <strong>Projects</strong> → click a client → click the eye icon on a project.</p>
                                    </div>
                                </div>
                                <div class="manual-step">
                                    <div class="manual-step-num">2</div>
                                    <div class="manual-step-body">
                                        <h3>Read the tracker</h3>
                                        <p>The tracker at the top shows all 8 stages a project passes through — <strong>Planning → Procurement → Material Prep → Fabrication → Inspection → Painting → Completion → Delivery</strong> — with the current one highlighted, plus a progress bar that fills in on its own as stages are completed.</p>
                                    </div>
                                </div>
                                <div class="manual-step">
                                    <div class="manual-step-num">3</div>
                                    <div class="manual-step-body">
                                        <h3>Log an update to move to the next stage</h3>
                                        <p>The <strong>Add Progress Update</strong> card changes depending on the current stage. Fill in what it asks for and click the button at the bottom:</p>
                                        <table>
                                            <thead><tr><th>Stage</th><th>What to fill in</th><th>Button to click</th></tr></thead>
                                            <tbody>
                                                <tr><td>Planning</td><td>Upload shop drawings, then quotation files, then confirm payment</td><td>Follows on-screen prompts</td></tr>
                                                <tr><td>Procurement</td><td>Nothing — unlocks once materials & labor are recorded</td><td>—</td></tr>
                                                <tr><td>Material Prep</td><td>Check off Measuring & Marking</td><td>"Save Progress Update"</td></tr>
                                                <tr><td>Fabrication</td><td>Check off Cutting, Assembly, Welding</td><td>"Save Progress Update"</td></tr>
                                                <tr><td>Inspection</td><td>Check off Pressure Test & Soap Test</td><td>"Save Progress Update"</td></tr>
                                                <tr><td>Painting</td><td>Upload photos + optional notes</td><td>"Save Progress Update"</td></tr>
                                                <tr><td>Completion</td><td>Upload final photos + optional notes</td><td>"Save Progress Update"</td></tr>
                                                <tr><td>Delivery</td><td>Confirm final payment, upload delivery photos</td><td>"Mark as Delivered & Complete"</td></tr>
                                            </tbody>
                                        </table>
                                        <div class="manual-note alert-banner warning">
                                            <i data-lucide="alert-triangle"></i>
                                            <span>Some stages need a payment settled first (in Payments) before they'll let you continue — you'll see a notice with a link if that's the case.</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="manual-step">
                                    <div class="manual-step-num">4</div>
                                    <div class="manual-step-body">
                                        <h3>Let an employee submit it instead</h3>
                                        <p>Click <strong>Request from Employee</strong> to have someone on your team send the update from their end. It'll wait for your approval in Progress History.</p>
                                    </div>
                                </div>
                                <div class="manual-step">
                                    <div class="manual-step-num">5</div>
                                    <div class="manual-step-body">
                                        <h3>Check Progress History</h3>
                                        <p>On the right, every update ever logged is listed with its status. Click one to see the full details, and approve or request changes on anything submitted by an employee.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- QUOTATION REQUESTS --}}
                    <div class="manual-panel" data-panel="quotation-requests">
                        <div class="manual-topic-header">
                            <div class="manual-topic-icon"><i data-lucide="inbox"></i></div>
                            <h2>Quotation Requests</h2>
                        </div>
                        <p class="manual-tagline">Requests clients send in asking for a quote before they commit to a project.</p>
                        <div class="manual-content">
                            <ul>
                                <li>Open a request to see what the client is asking for.</li>
                                <li><strong>Decline</strong> it if it's not something you'll take on — the client is notified.</li>
                                <li><strong>Convert</strong> an approved request straight into a new project, carrying over the specs the client already gave you.</li>
                            </ul>
                        </div>
                    </div>

                    {{-- PROJECT QUOTATIONS --}}
                    <div class="manual-panel" data-panel="project-quotations">
                        <div class="manual-topic-header">
                            <div class="manual-topic-icon"><i data-lucide="package"></i></div>
                            <h2>Project Quotations</h2>
                        </div>
                        <p class="manual-tagline">Where you cost out a project's materials and labor, and produce the quotation to send the client.</p>
                        <div class="manual-content">
                            <ul>
                                <li>Add materials (with quantity and price) and labor entries (per employee) to a project.</li>
                                <li>A markup factor is applied automatically to materials — adjust it if needed.</li>
                                <li>Click <strong>Generate Project Quotations</strong> to preview, print, or download the quote — you can send the full itemized breakdown, or toggle "Summary only" to send just the bottom-line total.</li>
                            </ul>
                        </div>
                    </div>

                    {{-- MATERIALS --}}
                    <div class="manual-panel" data-panel="materials">
                        <div class="manual-topic-header">
                            <div class="manual-topic-icon"><i data-lucide="clipboard-list"></i></div>
                            <h2>Materials</h2>
                        </div>
                        <p class="manual-tagline">Tracks what was actually bought against what was planned, per project.</p>
                        <div class="manual-content">
                            <ul>
                                <li>Log real material purchases as they happen.</li>
                                <li>Compare actual spend against the planned materials list to catch cost overruns early.</li>
                                <li>Keep a directory of supplier contacts at the top of the page.</li>
                            </ul>
                        </div>
                    </div>

                    {{-- PAYMENTS --}}
                    <div class="manual-panel" data-panel="payments">
                        <div class="manual-topic-header">
                            <div class="manual-topic-icon"><i data-lucide="credit-card"></i></div>
                            <h2>Payments</h2>
                        </div>
                        <p class="manual-tagline">Tracks contract amounts, payment stages, and billing per project.</p>
                        <div class="manual-content">
                            <ul>
                                <li>Set up a project's contract amount and payment terms.</li>
                                <li>Record each payment as the client pays it.</li>
                                <li>Generate billing statements to send the client.</li>
                            </ul>
                        </div>
                    </div>

                    {{-- MONTHLY EXPENSES --}}
                    <div class="manual-panel" data-panel="monthly-expenses">
                        <div class="manual-topic-header">
                            <div class="manual-topic-icon"><i data-lucide="receipt"></i></div>
                            <h2>Monthly Expenses</h2>
                        </div>
                        <p class="manual-tagline">Recurring overhead costs — rent, utilities, and the like.</p>
                        <div class="manual-content">
                            <p>Record what the business spends each month, and it gets automatically split across active projects so each project's true cost includes its fair share of overhead.</p>
                        </div>
                    </div>

                    {{-- REVOLVING FUND --}}
                    <div class="manual-panel" data-panel="revolving-fund">
                        <div class="manual-topic-header">
                            <div class="manual-topic-icon"><i data-lucide="refresh-cw"></i></div>
                            <h2>Revolving Fund</h2>
                        </div>
                        <p class="manual-tagline">The company's shared cash wallet, used across all active projects.</p>
                        <div class="manual-content">
                            <ul>
                                <li>Every time money is spent from the fund, log it as <strong>Fund Used</strong> and tag it to a project — this is how the fund stays traceable.</li>
                                <li>When you pick a project, a window pops up listing only active projects with their client and phase, so it's easy to find the right one.</li>
                                <li>Use <strong>Replenish</strong> to add money back into the fund, and check the ledger below to see every transaction.</li>
                            </ul>
                        </div>
                    </div>

                    {{-- EMPLOYEES --}}
                    <div class="manual-panel" data-panel="employees">
                        <div class="manual-topic-header">
                            <div class="manual-topic-icon"><i data-lucide="users"></i></div>
                            <h2>Employees</h2>
                        </div>
                        <p class="manual-tagline">Manage employee records, contact details, and salary rates.</p>
                        <div class="manual-content">
                            <p>Add new employees, edit their details, set their daily rate, and archive or reactivate an account when someone leaves or returns.</p>
                        </div>
                    </div>

                    {{-- CLIENTS --}}
                    <div class="manual-panel" data-panel="clients">
                        <div class="manual-topic-header">
                            <div class="manual-topic-icon"><i data-lucide="building-2"></i></div>
                            <h2>Clients</h2>
                        </div>
                        <p class="manual-tagline">View and manage client contact records and portal accounts.</p>
                        <div class="manual-content">
                            <p>Add clients manually, or approve/decline client sign-up requests here. Approving a request creates their portal login automatically.</p>
                        </div>
                    </div>

                    {{-- SETTINGS --}}
                    <div class="manual-panel" data-panel="settings">
                        <div class="manual-topic-header">
                            <div class="manual-topic-icon"><i data-lucide="settings"></i></div>
                            <h2>Settings</h2>
                        </div>
                        <p class="manual-tagline">Manage your own profile, password, and the other system accounts.</p>
                        <div class="manual-content">
                            <p>Update your name, photo, and password here. Admins can also manage the landing page's content (portfolio items, client reviews) and see a list of every employee/client account in the system.</p>
                        </div>
                    </div>

                </div>
            </div>

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();

            var navLinks = document.querySelectorAll('.manual-nav-link');
            var panels   = document.querySelectorAll('.manual-panel');

            navLinks.forEach(function(link) {
                link.addEventListener('click', function() {
                    navLinks.forEach(function(l) { l.classList.remove('active'); });
                    panels.forEach(function(p) { p.classList.remove('active'); });
                    this.classList.add('active');
                    var target = document.querySelector('.manual-panel[data-panel="' + this.dataset.topic + '"]');
                    if (target) target.classList.add('active');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });

            var navSearch = document.getElementById('manualNavSearch');
            if (navSearch) {
                navSearch.addEventListener('input', function() {
                    var q = this.value.toLowerCase().trim();
                    navLinks.forEach(function(link) {
                        var text = link.textContent.toLowerCase();
                        link.style.display = (!q || text.indexOf(q) !== -1) ? '' : 'none';
                    });
                    document.querySelectorAll('.manual-nav-section-label').forEach(function(label) {
                        var next = label.nextElementSibling;
                        var hasVisible = false;
                        while (next && next.classList.contains('manual-nav-link')) {
                            if (next.style.display !== 'none') hasVisible = true;
                            next = next.nextElementSibling;
                        }
                        label.style.display = hasVisible ? '' : 'none';
                    });
                });
            }
        });
    </script>
</body>
</html>
