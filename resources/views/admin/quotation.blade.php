<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        /* Quotation Requests table — roomier cells, stacked details instead of nine cramped columns */
        #quotationRequestsTable th { padding: 12px 16px; }
        #quotationRequestsTable td { padding: 14px 16px; vertical-align: middle; }
        #quotationRequestsTable .qr-cell-title { font-size: 13px; font-weight: 800; color: var(--dark); line-height: 1.35; }
        #quotationRequestsTable .qr-cell-sub { display: flex; align-items: center; gap: 5px; margin-top: 3px; font-size: 11.5px; color: var(--muted); }
        #quotationRequestsTable .qr-cell-sub svg, #quotationRequestsTable .qr-cell-sub i { flex-shrink: 0; }
        #quotationRequestsTable .qr-stack { display: flex; flex-direction: column; align-items: flex-start; gap: 6px; }
        #quotationRequestsTable .qr-cell-loc { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: 12px; line-height: 1.45; color: var(--dark); }
        #quotationRequestsTable .qr-actions { display: flex; align-items: center; gap: 6px; flex-wrap: nowrap; }
        #quotationRequestsTable .action-cell { padding-right: 12px; }
        #quotationRequestsTable .status-badge { white-space: nowrap; }
        .pf-client-name { font-size: 14.5px; font-weight: 800; color: var(--dark); }
        .quotation-view-tab {
            border: none; background: transparent; color: var(--muted);
            font-size: 13px; font-weight: 800; padding: 8px 18px; border-radius: 999px;
            cursor: pointer; transition: 0.15s ease; font-family: inherit;
        }
        .quotation-view-tab:hover { color: var(--dark); }
        .quotation-view-tab.active { background: var(--dark); color: var(--white); }
        .quotation-view-tab { display: inline-flex; align-items: center; gap: 7px; }
        .qv-count { display: inline-flex; align-items: center; justify-content: center; min-width: 19px; height: 19px; padding: 0 6px; border-radius: 999px; background: #F5B400; color: #1a1a1a; font-size: 10.5px; font-weight: 900; }

        /* ── Requests table polish ── */
        #quotationRequestsTable { table-layout: fixed; width: 100%; min-width: 980px; }   /* narrower screens scroll sideways in the card */
        .qr-table-scroll { overflow-x: auto; }
        #quotationRequestsTable .qr-client { display: flex; align-items: center; gap: 11px; min-width: 0; }
        #quotationRequestsTable .qr-ref { display: inline-flex; align-items: center; padding: 1px 7px; border-radius: 6px; background: var(--cream-deep, #ebebeb); color: var(--dark);
                                           font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 10.5px; font-weight: 700; letter-spacing: .02em; }
        #quotationRequestsTable .qr-date { font-size: 13px; font-weight: 700; color: var(--dark); white-space: nowrap; }
        #quotationRequestsTable .qr-ago { font-size: 11px; font-weight: 600; color: var(--muted); margin-top: 2px; white-space: nowrap; }
        #quotationRequestsTable .status-badge { display: inline-flex; align-items: center; gap: 6px; max-width: 100%; }
        #quotationRequestsTable .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex-shrink: 0; opacity: .85; }
        #quotationRequestsTable tbody tr:not([id]) { transition: background .15s ease; }

        /* search and the status tabs share one row (tabs made a little smaller to fit) */
        #requestsViewContent .table-toolbar { flex-wrap: nowrap; align-items: center; gap: 12px; }
        #requestsViewContent .table-toolbar .search-box { flex: 0 1 260px; min-width: 180px; max-width: 260px; }
        #quotationStatusTabs { flex: 0 1 auto; min-width: 0; padding: 3px; margin-left: auto; }
        #quotationStatusTabs .filter-tab { padding: 6px 10px; font-size: 12px; gap: 5px; white-space: nowrap; }
        #quotationStatusTabs .filter-count { min-width: 17px; height: 17px; font-size: 10px; padding: 0 5px; }

        @media (max-width: 1180px) {
            #requestsViewContent .table-toolbar { flex-wrap: wrap; }
            #requestsViewContent .table-toolbar .search-box { flex: 1 1 100%; max-width: 100%; }
            #quotationStatusTabs { margin-left: 0; }
        }
        /* phones: the shared stylesheet pins narrow columns (Client 150px) — give Client and Status room
           and let the table scroll sideways inside the card instead of breaking names mid-word */
        @media (max-width: 768px) {
            html #quotationRequestsTable { min-width: 860px !important; }
            html #quotationRequestsTable colgroup col:nth-child(1) { width: 210px !important; }   /* Client */
            html #quotationRequestsTable colgroup col:nth-child(2) { width: 200px !important; }   /* Tank + target */
            html #quotationRequestsTable colgroup col:nth-child(3) { width: 120px !important; }   /* Submitted */
            html #quotationRequestsTable colgroup col:nth-child(4) { width: 175px !important; }   /* Status */
            html #quotationRequestsTable colgroup col:nth-child(5) { width: 150px !important; }   /* Actions */
            html #quotationRequestsTable td { word-break: normal; }
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
                    <h1 class="page-title">Quotation</h1>
                    <p class="page-subtitle">Review client quotation requests and manage materials/labor costs for projects.</p>
                </div>
            </div>

            @if(session('success'))
            {{-- Saved (e.g. quotation sent, request declined, materials saved): the small dark toast below the
                 header (same as "Salary record saved."), shown for 3 seconds --}}
            <div class="toast" id="quotationToast" role="status">
                <i data-lucide="check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
            <script>
                window.addEventListener('load', function () {
                    var t = document.getElementById('quotationToast');
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
            <div class="alert-banner error">
                <i data-lucide="alert-circle"></i>
                {{ session('error') }}
            </div>
            @endif

            @php
                $qoAction = $requests->whereIn('status', ['pending', 'in_preparation'])->count();
            @endphp
            <div style="margin-bottom:16px;">
                <div style="display:inline-flex;background:var(--white);border:1px solid var(--border);border-radius:999px;padding:4px;gap:2px;box-shadow:0 1px 2px rgba(0,0,0,.04);">
                    <button type="button" class="quotation-view-tab {{ $activeTab === 'requests' ? 'active' : '' }}" data-view="requests" onclick="switchQuotationView('requests', this)">
                        Quotation Requests
                        @if($qoAction > 0)<span class="qv-count" title="{{ $qoAction }} waiting on you">{{ $qoAction }}</span>@endif
                    </button>
                    <button type="button" class="quotation-view-tab {{ $activeTab === 'projects' ? 'active' : '' }}" data-view="projects" onclick="switchQuotationView('projects', this)">
                        Project Quotations
                    </button>
                </div>
            </div>

            {{-- ===================== QUOTATION REQUESTS ===================== --}}
            <div id="requestsViewContent" style="{{ $activeTab === 'requests' ? '' : 'display:none;' }}">
            <div class="table-card" style="padding-bottom:0;">
                <div class="table-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="quotationSearch" placeholder="Search client, ref. or tank...">
                    </div>
                    <div class="filter-tabs" id="quotationStatusTabs" style="overflow-x:auto;max-width:100%;">
                        <button type="button" class="filter-tab active" data-filter="">
                            All
                            <span class="filter-count">{{ $requests->count() }}</span>
                        </button>
                        @foreach([
                            'pending'        => 'Pending',
                            'in_preparation' => 'In Preparation',
                            'quotation_sent' => 'Sent to Client',
                            'approved'       => 'Approved',
                            'rejected'       => 'Rejected',
                            'converted'      => 'Converted',
                            'declined'       => 'Declined',
                        ] as $statusKey => $statusLabel)
                        <button type="button" class="filter-tab" data-filter="{{ $statusKey }}">
                            {{ $statusLabel }}
                            <span class="filter-count">{{ $requests->where('status', $statusKey)->count() }}</span>
                        </button>
                        @endforeach
                    </div>
                </div>
                <div class="qr-table-scroll" style="max-height:570px;overflow-y:auto;">
                    <table class="data-table" id="quotationRequestsTable" style="margin:0;">
                        <colgroup>
                            <col style="width:36%;">
                            <col style="width:38%;">
                            <col style="width:130px;">
                            <col style="width:190px;">
                            <col style="width:150px;">
                        </colgroup>
                        <thead style="position:sticky;top:0;z-index:2;">
                            <tr>
                                <th>Client</th>
                                <th>Tank</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $qr)
                            <tr
                                class="{{ $qr->status === 'pending' ? 'row-needs-action' : '' }}"
                                data-status="{{ $qr->status }}"
                                data-name="{{ strtolower(($qr->client->name ?? '') . ' ' . ($qr->quotationBatch->reference_no ?? '') . ' ' . $qr->tank_type) }}"
                                data-request="{{ json_encode([
                                    'id'                => $qr->id,
                                    'batch_id'          => $qr->batch_id,
                                    'reference_no'      => $qr->quotationBatch->reference_no ?? null,
                                    'status'            => $qr->status,
                                    'status_label'      => $qr->status_label,
                                    'status_class'      => $qr->status_class,
                                    'specs'             => $qr->specificationLines(),
                                    'client_name'       => $qr->client->name ?? '—',
                                    'client_contact'    => $qr->client->contact ?? '—',
                                    'client_email'      => $qr->client->email ?? '—',
                                    'tank_items'        => [[
                                        'tank_type'       => $qr->tank_type,
                                        'capacity'        => $qr->capacity,
                                        'quantity'        => $qr->quantity,
                                        'target_timeline' => $qr->target_timeline_display,
                                    ]],
                                    'location'          => (($qr->fulfillment ?? 'delivery') === 'pickup') ? 'For pick-up (no delivery address)' : $qr->location,
                                    'notes'             => $qr->notes,
                                    'reference_files'   => $qr->reference_files ?? [],
                                    'quotation_files'   => !empty($qr->quotation_files) ? [$qr->quotation_files] : [],
                                    'decline_reason'    => $qr->decline_reason,
                                    'submitted_at'      => $qr->created_at->format('M d, Y'),
                                    'build_quotation_url' => route('admin.quotation_requests.batch_detail', $qr->batch_id),
                                    'convert_url'         => route('admin.projects', ['prefill_quotation_batch' => $qr->batch_id]),
                                ]) }}"
                                >
                                <td>
                                    <div class="qr-client">
                                        <div style="min-width:0;">
                                            <div class="qr-cell-title">{{ $qr->client->name ?? '—' }}</div>
                                            <div class="qr-cell-sub" style="flex-wrap:wrap;">
                                                @if($qr->quotationBatch?->reference_no)<span class="qr-ref">{{ $qr->quotationBatch->reference_no }}</span>@endif
                                                <span>{{ $qr->client->contact ?? '—' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="qr-stack">
                                        @if($qr->tank_type)
                                        <span class="qr-spec-chip qr-chip-type">
                                            <i data-lucide="package" style="width:11px;height:11px;"></i>
                                            {{ $qr->tank_type }}{{ $qr->quantity > 1 ? ' ×' . $qr->quantity : '' }}
                                        </span>
                                        @else
                                        <span class="qr-spec-chip qr-chip-type" title="No tank spec — see attached reference photos">
                                            <i data-lucide="image" style="width:11px;height:11px;"></i>
                                            Client's Own Tank
                                        </span>
                                        @endif
                                        <span style="display:flex;flex-wrap:wrap;align-items:center;gap:6px;">
                                            @if(!empty($qr->capacity))
                                            <span class="qr-spec-chip qr-chip-capacity">
                                                <i data-lucide="droplet" style="width:11px;height:11px;"></i>
                                                {{ $qr->capacity }}
                                            </span>
                                            @endif
                                            {{-- every request is pick-up now, so only the client's target date is worth showing;
                                                 an older delivery request still shows where it goes (in the tooltip) --}}
                                            @if(!empty($qr->target_timeline))
                                            <span class="qr-cell-sub" style="margin-top:0;" @if(($qr->fulfillment ?? 'delivery') !== 'pickup' && $qr->location) title="Delivery: {{ $qr->location }}" @endif>
                                                <i data-lucide="clock" style="width:11px;height:11px;"></i> Target {{ $qr->target_timeline_display }}
                                            </span>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="qr-date">{{ $qr->created_at->format('M d, Y') }}</div>
                                    <div class="qr-ago">{{ $qr->created_at->diffForHumans() }}</div>
                                </td>
                                <td>
                                    <span class="status-badge {{ $qr->status_class }}" @if($qr->decline_reason) title="{{ $qr->decline_reason }}" @endif>{{ $qr->status_label }}</span>
                                </td>
                                <td class="action-cell">
                                    <div class="qr-actions">
                                    <button class="action-btn view view-request-btn" type="button" title="View Request" aria-label="View request">
                                        <i data-lucide="eye"></i>
                                    </button>
                                    @if($qr->batch_id && $qr->status !== 'declined')
                                    <a class="action-btn view" title="{{ in_array($qr->status, ['pending', 'in_preparation']) ? 'Costing' : 'Open Quotation' }}"
                                       aria-label="Open quotation builder"
                                       href="{{ route('admin.quotation_requests.batch_detail', $qr->batch_id) }}">
                                        <i data-lucide="calculator"></i>
                                    </a>
                                    @endif
                                    @if($qr->status === 'approved')
                                    <a class="action-btn view" type="button" title="Convert to Project"
                                       href="{{ route('admin.projects', ['prefill_quotation_batch' => $qr->batch_id]) }}">
                                        <i data-lucide="folder-plus"></i>
                                    </a>
                                    @endif
                                    @if(in_array($qr->status, ['pending', 'in_preparation', 'quotation_sent']))
                                    <button class="action-btn view decline-request-btn" type="button"
                                            title="Decline Request"
                                            data-id="{{ $qr->batch_id }}"
                                            data-name="{{ $qr->client->name ?? '' }}">
                                        <i data-lucide="x"></i>
                                    </button>
                                    @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr id="quotationBladeEmpty">
                                <td colspan="5" style="text-align:center;padding:40px;color:var(--muted);">
                                    No quotation requests yet.
                                </td>
                            </tr>
                            @endforelse
                            <tr id="quotationEmptyState" style="display:none;">
                                <td colspan="5" style="text-align:center;padding:48px 20px;">
                                    <div style="display:flex;flex-direction:column;align-items:center;gap:10px;color:var(--muted);">
                                        <i data-lucide="inbox" style="width:36px;height:36px;opacity:0.4;"></i>
                                        <span id="quotationEmptyMsg">No requests match your search.</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>

            {{-- ===================== PROJECT QUOTATIONS ===================== --}}
            <div id="projectsViewContent" style="{{ $activeTab === 'projects' ? '' : 'display:none;' }}">
            <div class="table-card" id="clientListCard">
                <div class="table-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="clientListSearch" placeholder="Search client...">
                    </div>
                    <select class="filter-select" id="clientSortSelect">
                        <option value="default">Sort: Active Projects First</option>
                        <option value="alpha-asc">Sort: A–Z</option>
                        <option value="alpha-desc">Sort: Z–A</option>
                    </select>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="clientListTable">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th style="text-align:center;">Total Projects</th>
                                <th style="text-align:center;">Active</th>
                                <th style="text-align:center;">Completed</th>
                                <th style="text-align:center;">Archived</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clientGroups as $g)
                            <tr data-search="{{ strtolower($g['client']) }}" onclick="window.location='{{ route('admin.project_materials.client', $g['client_key']) }}'" style="cursor:pointer;">
                                <td><span class="pf-client-name">{{ $g['client'] }}</span></td>
                                <td style="text-align:center;"><span class="client-pill" style="background-color:#F3F4F6;color:#1F2937;border-color:#D1D5DB;">{{ $g['total'] }}</span></td>
                                <td style="text-align:center;"><span class="client-pill" style="background-color:#EAF0FF;color:#2563EB;border-color:#BFDBFE;">{{ $g['active'] }}</span></td>
                                <td style="text-align:center;"><span class="client-pill" style="background-color:#E7F6EC;color:#207A3A;border-color:#A7E3B8;">{{ $g['completed'] }}</span></td>
                                <td style="text-align:center;"><span class="client-pill" style="background-color:#F3F4F6;color:#6B7280;border-color:#D1D5DB;">{{ $g['archived'] }}</span></td>
                                <td class="action-cell" style="text-align:center;">
                                    <a href="{{ route('admin.project_materials.client', $g['client_key']) }}" class="action-btn view" title="View Client's Projects" onclick="event.stopPropagation()">
                                        <i data-lucide="eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="inbox" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;">No clients with projects yet.</div>
                                    <div style="font-size:13px;margin-top:4px;">Add projects via the <strong>Projects</strong> page.</div>
                                </td>
                            </tr>
                            @endforelse
                            @if($clientGroups->isNotEmpty())
                            <tr id="clientListEmptyRow" style="display:none;">
                                <td colspan="6" style="text-align:center;padding:60px 20px;color:var(--muted);">
                                    <i data-lucide="search-x" style="width:36px;height:36px;opacity:.35;display:block;margin:0 auto 12px;"></i>
                                    <div style="font-size:14px;font-weight:700;">No clients match your search.</div>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            </div>

        </main>
    </div>

    <!-- ===== VIEW REQUEST MODAL ===== -->
    <div class="modal-overlay" id="viewRequestModal">
        <div class="modal-card" style="max-width:600px;max-height:90vh;overflow-y:auto;">
            <div class="modal-header">
                <div>
                    <h2 id="viewRequestTitle">Quotation Request</h2>
                    <p id="viewRequestSubtitle">Submitted request details</p>
                </div>
                <button class="modal-close" type="button" id="closeViewRequestModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div style="padding:0 28px 4px;">
                <div id="viewRequestStatusBadge" style="margin-bottom:16px;"></div>

                <div id="viewRequestReferenceFilesWrap" style="display:none;margin-bottom:20px;background:var(--cream-soft);border:1px solid var(--border);border-radius:14px;padding:14px 16px;">
                    <div style="display:flex;align-items:center;gap:7px;font-size:12px;font-weight:800;color:var(--dark);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:10px;">
                        <i data-lucide="paperclip" style="width:14px;height:14px;color:var(--muted);"></i>
                        Client Attached Design Files
                    </div>
                    <div id="viewRequestReferenceFiles" style="display:flex;flex-wrap:wrap;gap:10px;"></div>
                </div>

                <label style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:8px;">
                    Tank Requirements
                </label>
                <div id="viewRequestTankItems" style="display:flex;flex-direction:column;gap:8px;margin-bottom:20px;"></div>

                <div class="form-grid" style="margin-bottom:6px;">
                    <div class="form-group">
                        <label>Client</label>
                        <input type="text" id="viewRequestClientName" disabled>
                    </div>
                    <div class="form-group">
                        <label>Contact</label>
                        <input type="text" id="viewRequestClientContact" disabled>
                    </div>
                    <div class="form-group form-group-full">
                        <label>Email</label>
                        <input type="text" id="viewRequestClientEmail" disabled>
                    </div>
                    <div class="form-group form-group-full">
                        <label>Location</label>
                        <textarea id="viewRequestLocation" rows="2" disabled style="resize:none;"></textarea>
                    </div>
                    <div class="form-group form-group-full" id="viewRequestNotesGroup" style="display:none;">
                        <label>Notes</label>
                        <textarea id="viewRequestNotes" rows="3" disabled style="resize:none;"></textarea>
                    </div>
                </div>

                <div id="viewRequestDeclineReasonWrap" class="alert-banner"
                     style="display:none;align-items:flex-start;margin-bottom:16px;background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;">
                    <i data-lucide="circle-alert"></i>
                    <span id="viewRequestDeclineReason" style="flex:1;min-width:0;overflow-wrap:anywhere;word-break:break-word;"></span>
                </div>

                <div id="viewRequestFilesWrap" style="display:none;margin-bottom:16px;">
                    <label style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:8px;">
                        Quotation Files Sent
                    </label>
                    <div id="viewRequestFiles" style="display:flex;flex-direction:column;gap:6px;"></div>
                </div>

                <!-- Build & Send Quotation (pending only) — happens on the Build Quotation page,
                     which covers the whole batch (all tanks in this request) at once. -->
                <div id="sendQuotationSection" style="display:none;">
                    <div class="alert-banner info" style="margin-bottom:14px;">
                        <i data-lucide="info"></i>
                        Do the costing (materials, labor and pricing), generate the quotation document, then send it to the client for approval.
                    </div>
                    <div class="modal-actions" style="border-top:none;padding-top:0;">
                        <a id="buildQuotationBtn" href="#" class="save-btn" style="text-decoration:none;">
                            <i data-lucide="calculator"></i> Start Costing
                        </a>
                    </div>
                </div>

                <!-- Awaiting client approval -->
                <div id="awaitingApprovalSection" style="display:none;" class="alert-banner info">
                    <i data-lucide="clock"></i>
                    Waiting for the client to review and approve this quotation.
                </div>

                <!-- Approved — ready to convert -->
                <div id="readyToConvertSection" style="display:none;">
                    <div class="alert-banner success" style="margin-bottom:14px;">
                        <i data-lucide="check-circle"></i>
                        The client approved this quotation. You may now convert it into a project.
                    </div>
                    <div class="modal-actions" style="border-top:none;padding-top:0;">
                        <a id="readyToConvertBtn" href="#" class="save-btn" style="text-decoration:none;">
                            <i data-lucide="folder-plus"></i> Convert to Project
                        </a>
                    </div>
                </div>

                <div style="height:20px;"></div>
            </div>
        </div>
    </div>

    <!-- ===== DECLINE REQUEST MODAL ===== -->
    <div class="modal-overlay" id="declineRequestModal">
        <div class="modal-card" style="max-width:460px;">
            <div class="modal-header">
                <div>
                    <h2>Decline Request</h2>
                    <p>This will notify the client that their request was not approved.</p>
                </div>
                <button class="modal-close" type="button" id="closeDeclineRequestModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="delete-confirm-body">
                <div class="delete-confirm-icon"><i data-lucide="x-circle"></i></div>
                <p id="declineRequestMsg">Are you sure you want to decline this request?</p>
            </div>
            <form method="POST" id="declineRequestForm">
                @csrf
                @method('PATCH')
                <div class="form-group" style="text-align:left;padding:0 24px 8px;">
                    <label style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:8px;">
                        Reason <span style="font-weight:400;text-transform:none;">(optional)</span>
                    </label>
                    <textarea name="reason" id="declineReasonInput" class="log-textarea" rows="3" maxlength="500"
                              placeholder="Let the client know why this request was declined..."></textarea>
                    <div id="declineReasonCount" style="margin-top:6px;text-align:right;font-size:11.5px;font-weight:700;color:var(--muted);">0 / 500</div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelDeclineRequest">Cancel</button>
                    <button type="submit" class="save-btn" style="background:#dc2626;">
                        <i data-lucide="x"></i> Decline
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        function openModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
        }
        function closeModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        // ── Requests / Projects view switch ─────────────────────────────────────
        function switchQuotationView(view, btn) {
            document.querySelectorAll('.quotation-view-tab').forEach(function (t) { t.classList.remove('active'); });
            btn.classList.add('active');
            document.getElementById('requestsViewContent').style.display = view === 'requests' ? '' : 'none';
            document.getElementById('projectsViewContent').style.display = view === 'projects' ? '' : 'none';
        }

        document.addEventListener('DOMContentLoaded', function () {

            // ---- View Request Modal ----
            var statusMeta = {
                pending:         { label: 'Pending Review',  cls: 'pending'  },
                quotation_sent:  { label: 'Quotation Sent',  cls: 'ongoing'  },
                approved:        { label: 'Approved',        cls: 'approved' },
                converted:       { label: 'Converted',       cls: 'active'   },
                declined:        { label: 'Declined',        cls: 'revision' },
            };

            document.querySelectorAll('.view-request-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var row = this.closest('tr');
                    var r   = JSON.parse(row.dataset.request);

                    document.getElementById('viewRequestTitle').textContent = r.reference_no ? 'Quotation Request ' + r.reference_no : 'Quotation Request';
                    document.getElementById('viewRequestSubtitle').textContent = 'Submitted ' + r.submitted_at;
                    document.getElementById('viewRequestClientName').value    = r.client_name;
                    document.getElementById('viewRequestClientContact').value = r.client_contact;
                    document.getElementById('viewRequestClientEmail').value   = r.client_email;
                    document.getElementById('viewRequestLocation').value      = r.location;

                    var tankItemsWrap = document.getElementById('viewRequestTankItems');
                    tankItemsWrap.innerHTML = (r.tank_items || []).map(function (item) {
                        var qty = item.quantity || 1;
                        var hasType = !!item.tank_type;
                        var typeLabel = hasType ? (item.tank_type + (qty > 1 ? ' ×' + qty : '')) : "Client's Own Tank";
                        var typeIcon = hasType ? 'package' : 'image';
                        return '<div class="qr-tank-row" style="background:var(--surface-2);border:1px solid var(--border);border-radius:10px;padding:12px 14px;">'
                            + '<span class="qr-spec-chip qr-chip-type"><i data-lucide="' + typeIcon + '" style="width:11px;height:11px;"></i>' + escapeHtml(typeLabel) + '</span>'
                            + (item.capacity ? '<span class="qr-spec-chip qr-chip-capacity"><i data-lucide="droplet" style="width:11px;height:11px;"></i>Capacity: ' + escapeHtml(item.capacity) + '</span>' : '')
                            + (item.target_timeline ? '<span class="qr-spec-chip qr-chip-timeline"><i data-lucide="clock" style="width:11px;height:11px;"></i>Target delivery: ' + escapeHtml(item.target_timeline) + '</span>' : '')
                            + '</div>';
                    }).join('') || '<span style="font-size:12.5px;color:var(--muted);">No tank items.</span>';

                    // the client's answers to the tank type's own questions
                    var specs = r.specs || {};
                    var specKeys = Object.keys(specs);
                    if (specKeys.length) {
                        tankItemsWrap.innerHTML += '<div style="border:1px solid var(--border);border-radius:10px;padding:10px 14px;display:grid;grid-template-columns:minmax(120px,40%) 1fr;gap:6px 12px;font-size:12.5px;">'
                            + specKeys.map(function (k) {
                                return '<span style="color:var(--muted);font-weight:700;">' + escapeHtml(k) + '</span><span style="color:var(--dark);font-weight:700;overflow-wrap:anywhere;">' + escapeHtml(specs[k]) + '</span>';
                            }).join('')
                            + '</div>';
                    }

                    var notesGroup = document.getElementById('viewRequestNotesGroup');
                    if (r.notes) {
                        notesGroup.style.display = '';
                        document.getElementById('viewRequestNotes').value = r.notes;
                    } else {
                        notesGroup.style.display = 'none';
                    }

                    var refFilesWrap = document.getElementById('viewRequestReferenceFilesWrap');
                    var refFilesList = document.getElementById('viewRequestReferenceFiles');
                    var refFiles = r.reference_files || [];
                    if (refFiles.length) {
                        refFilesWrap.style.display = '';
                        refFilesList.innerHTML = refFiles.map(function (url, i) {
                            var ext      = (url.split('.').pop() || '').split('?')[0].toLowerCase();
                            var isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].indexOf(ext) !== -1;
                            var thumb   = isImage
                                ? '<img src="' + url + '" alt="" style="width:64px;height:64px;border-radius:8px;object-fit:cover;flex-shrink:0;">'
                                : '<span style="width:64px;height:64px;border-radius:8px;background:#fff;border:1px solid var(--border);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;flex-shrink:0;">'
                                    + '<i data-lucide="file-text" style="width:20px;height:20px;color:var(--muted);"></i>'
                                    + '<span style="font-size:9.5px;font-weight:800;color:var(--muted);text-transform:uppercase;">' + (ext || 'file') + '</span>'
                                + '</span>';
                            return '<a href="' + url + '" target="_blank" title="Open Reference File ' + (i + 1) + '" '
                                + 'style="display:flex;flex-direction:column;align-items:center;gap:5px;text-decoration:none;width:64px;">'
                                + thumb
                                + '<span style="font-size:10.5px;font-weight:700;color:var(--dark);">File ' + (i + 1) + '</span>'
                            + '</a>';
                        }).join('');
                    } else {
                        refFilesWrap.style.display = 'none';
                        refFilesList.innerHTML = '';
                    }

                    var isRevisionRequest = (r.status === 'pending' || r.status === 'in_preparation') && !!r.decline_reason;
                    document.getElementById('viewRequestStatusBadge').innerHTML =
                        '<span class="status-badge ' + escapeHtml(r.status_class) + '">' + escapeHtml(r.status_label) + '</span>';

                    var declineWrap = document.getElementById('viewRequestDeclineReasonWrap');
                    if ((r.status === 'declined' || r.status === 'rejected') && r.decline_reason) {
                        declineWrap.style.display = '';
                        declineWrap.style.background = '#fee2e2';
                        declineWrap.style.borderColor = '#fca5a5';
                        declineWrap.style.color = '#dc2626';
                        document.getElementById('viewRequestDeclineReason').textContent = r.decline_reason;
                    } else if (isRevisionRequest) {
                        declineWrap.style.display = '';
                        declineWrap.style.background = '#fff7ed';
                        declineWrap.style.borderColor = '#fed7aa';
                        declineWrap.style.color = '#9a3412';
                        document.getElementById('viewRequestDeclineReason').textContent = 'Client requested a revision: ' + r.decline_reason;
                    } else {
                        declineWrap.style.display = 'none';
                    }

                    var filesWrap = document.getElementById('viewRequestFilesWrap');
                    var filesList = document.getElementById('viewRequestFiles');
                    var qFiles = r.quotation_files;
                    var hasFiles = qFiles && (Array.isArray(qFiles) ? qFiles.length : Object.keys(qFiles).length);
                    if (hasFiles) {
                        filesWrap.style.display = '';
                        // Older requests stored one flat list of URLs for the whole request;
                        // newer ones store an array-of-arrays keyed by tank index.
                        var isLegacyFlat = Array.isArray(qFiles) && typeof qFiles[0] === 'string';
                        if (isLegacyFlat) {
                            filesList.innerHTML = qFiles.map(function (url, i) {
                                return '<a href="' + url + '" target="_blank" style="display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:700;color:var(--accent);text-decoration:none;">'
                                    + '<i data-lucide="file-text" style="width:14px;height:14px;"></i> Quotation File ' + (i + 1) + '</a>';
                            }).join('');
                        } else {
                            filesList.innerHTML = (r.tank_items || []).map(function (item, i) {
                                var files = qFiles[i] || [];
                                if (!files.length) return '';
                                var qty = item.quantity || 1;
                                var label = (item.tank_type || 'Tank ' + (i + 1)) + (qty > 1 ? ' (' + qty + 'x)' : '');
                                var links = files.map(function (url, fi) {
                                    return '<a href="' + url + '" target="_blank" style="display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:700;color:var(--accent);text-decoration:none;">'
                                        + '<i data-lucide="file-text" style="width:13px;height:13px;"></i> File ' + (fi + 1) + '</a>';
                                }).join('');
                                return '<div style="border:1px solid var(--border);border-radius:10px;padding:10px 12px;">'
                                    + '<div style="font-size:11.5px;font-weight:800;color:var(--dark);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">' + label + '</div>'
                                    + '<div style="display:flex;flex-direction:column;gap:4px;">' + links + '</div>'
                                    + '</div>';
                            }).join('');
                        }
                    } else {
                        filesWrap.style.display = 'none';
                        filesList.innerHTML = '';
                    }

                    // Reset action sections
                    document.getElementById('sendQuotationSection').style.display    = 'none';
                    document.getElementById('awaitingApprovalSection').style.display = 'none';
                    document.getElementById('readyToConvertSection').style.display   = 'none';

                    if (r.status === 'pending' || r.status === 'in_preparation') {
                        document.getElementById('sendQuotationSection').style.display = '';
                        document.getElementById('buildQuotationBtn').href = r.build_quotation_url;
                    } else if (r.status === 'quotation_sent') {
                        document.getElementById('awaitingApprovalSection').style.display = '';
                    } else if (r.status === 'approved') {
                        document.getElementById('readyToConvertSection').style.display = '';
                        document.getElementById('readyToConvertBtn').href = r.convert_url;
                    }

                    openModal('viewRequestModal');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            });
            document.getElementById('closeViewRequestModal')
                .addEventListener('click', function () { closeModal('viewRequestModal'); });

            // ---- Decline Request Modal ----
            document.querySelectorAll('.decline-request-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.getElementById('declineRequestMsg').textContent =
                        'Are you sure you want to decline the request from "' + this.dataset.name + '"?';
                    document.getElementById('declineRequestForm').action = '/admin/quotation-requests/batch/' + this.dataset.id + '/decline';
                    document.getElementById('declineReasonInput').value = '';      // a fresh box every time
                    updateDeclineCount();
                    openModal('declineRequestModal');
                });
            });
            // the reason is capped at 500 characters: the counter turns red as it nears the limit
            function updateDeclineCount() {
                var len = document.getElementById('declineReasonInput').value.length;
                var el  = document.getElementById('declineReasonCount');
                el.textContent = len + ' / 500';
                el.style.color = len >= 500 ? '#dc2626' : (len >= 450 ? '#b45309' : 'var(--muted)');
            }
            document.getElementById('declineReasonInput').addEventListener('input', updateDeclineCount);

            document.getElementById('closeDeclineRequestModal')
                .addEventListener('click', function () { closeModal('declineRequestModal'); });
            document.getElementById('cancelDeclineRequest')
                .addEventListener('click', function () { closeModal('declineRequestModal'); });

            document.querySelectorAll('.modal-overlay').forEach(function (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === this) closeModal(this.id);
                });
            });

            // ---- Requests: Search & Filter ----
            var currentStatusFilter = '';

            function filterRequests() {
                var q      = document.getElementById('quotationSearch').value.toLowerCase();
                var status = currentStatusFilter;
                var visible = 0;
                document.querySelectorAll('#quotationRequestsTable tbody tr[data-name]').forEach(function (row) {
                    var matchQ      = !q || row.dataset.name.includes(q);
                    var matchStatus = !status || row.dataset.status === status;
                    var show = matchQ && matchStatus;
                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
                var bladeEmpty = document.getElementById('quotationBladeEmpty');
                if (bladeEmpty) bladeEmpty.style.display = (q || status) ? 'none' : '';

                var emptyRow = document.getElementById('quotationEmptyState');
                var emptyMsg = document.getElementById('quotationEmptyMsg');
                var filterActive = q || status;
                if (emptyRow) {
                    if (!visible && filterActive) {
                        if (emptyMsg) emptyMsg.textContent =
                            status === 'pending'        ? 'No pending requests.' :
                            status === 'quotation_sent' ? 'No requests awaiting client approval.' :
                            status === 'approved'       ? 'No approved requests.' :
                            status === 'converted'      ? 'No converted requests.' :
                            status === 'declined'       ? 'No declined requests.' :
                                                           'No requests match your search.';
                        emptyRow.style.display = '';
                    } else {
                        emptyRow.style.display = 'none';
                    }
                }
            }

            document.getElementById('quotationSearch').addEventListener('keyup', filterRequests);
            document.querySelectorAll('#quotationStatusTabs .filter-tab').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    document.querySelectorAll('#quotationStatusTabs .filter-tab').forEach(function (t) { t.classList.remove('active'); });
                    this.classList.add('active');
                    currentStatusFilter = this.dataset.filter;
                    filterRequests();
                });
            });

            // ---- Project Quotations: Search & Sort ----
            function applyClientListFilters() {
                var q = (document.getElementById('clientListSearch').value || '').toLowerCase();
                var visibleCount = 0;

                document.querySelectorAll('#clientListTable tbody tr[data-search]').forEach(function(row) {
                    var show = !q || row.dataset.search.indexOf(q) !== -1;
                    row.style.display = show ? '' : 'none';
                    if (show) visibleCount++;
                });

                var emptyRow = document.getElementById('clientListEmptyRow');
                if (emptyRow) {
                    emptyRow.style.display = visibleCount === 0 ? '' : 'none';
                    if (visibleCount === 0 && typeof lucide !== 'undefined') lucide.createIcons();
                }
            }

            function applyClientListSort() {
                var sortMode = document.getElementById('clientSortSelect').value;
                var tbody = document.querySelector('#clientListTable tbody');
                var rows  = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-search]'));

                rows.sort(function(a, b) {
                    if (sortMode === 'alpha-asc')  return a.dataset.search.localeCompare(b.dataset.search);
                    if (sortMode === 'alpha-desc') return b.dataset.search.localeCompare(a.dataset.search);
                    return 0; // 'default' order is server-rendered
                });

                rows.forEach(function(row) { tbody.appendChild(row); });
            }

            var clientListSearchInput = document.getElementById('clientListSearch');
            if (clientListSearchInput) clientListSearchInput.addEventListener('keyup', applyClientListFilters);

            var clientSortSelect = document.getElementById('clientSortSelect');
            if (clientSortSelect) clientSortSelect.addEventListener('change', applyClientListSort);

            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>
