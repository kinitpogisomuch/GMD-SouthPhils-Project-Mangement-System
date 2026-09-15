<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        .pf-client-name { font-size: 14.5px; font-weight: 800; color: var(--dark); }
        .quotation-view-tab {
            border: none; background: transparent; color: var(--muted);
            font-size: 13px; font-weight: 800; padding: 8px 18px; border-radius: 999px;
            cursor: pointer; transition: 0.15s ease; font-family: inherit;
        }
        .quotation-view-tab:hover { color: var(--dark); }
        .quotation-view-tab.active { background: var(--dark); color: var(--white); }
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

            <div style="margin-bottom:16px;">
                <div style="display:inline-flex;background:var(--cream-soft);border:1px solid var(--border);border-radius:999px;padding:4px;gap:2px;">
                    <button type="button" class="quotation-view-tab {{ $activeTab === 'requests' ? 'active' : '' }}" data-view="requests" onclick="switchQuotationView('requests', this)">
                        Quotation Requests
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
                        <input type="text" id="quotationSearch" placeholder="Search client name...">
                    </div>
                    <div class="filter-tabs" id="quotationStatusTabs">
                        <button type="button" class="filter-tab active" data-filter="">
                            All
                            <span class="filter-count">{{ $requests->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="pending">
                            Pending
                            <span class="filter-count">{{ $requests->where('status', 'pending')->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="quotation_sent">
                            Quotation Sent
                            <span class="filter-count">{{ $requests->where('status', 'quotation_sent')->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="approved">
                            Approved
                            <span class="filter-count">{{ $requests->where('status', 'approved')->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="converted">
                            Converted
                            <span class="filter-count">{{ $requests->where('status', 'converted')->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="declined">
                            Declined
                            <span class="filter-count">{{ $requests->where('status', 'declined')->count() }}</span>
                        </button>
                    </div>
                </div>
                <div style="max-height:570px;overflow-y:auto;">
                    <table class="data-table" id="quotationRequestsTable" style="margin:0;">
                        <colgroup>
                            <col style="width:10%;">
                            <col style="width:14%;">
                            <col style="width:15%;">
                            <col style="width:8%;">
                            <col style="width:9%;">
                            <col style="width:18%;">
                            <col style="width:9%;">
                            <col style="width:9%;">
                            <col style="width:8%;">
                        </colgroup>
                        <thead style="position:sticky;top:0;z-index:2;">
                            <tr>
                                <th>Client</th>
                                <th>Contact</th>
                                <th>Tank Type</th>
                                <th>Capacity</th>
                                <th>Target Delivery</th>
                                <th>Location</th>
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
                                data-name="{{ strtolower($qr->client->name ?? '') }}"
                                data-request="{{ json_encode([
                                    'id'                => $qr->id,
                                    'batch_id'          => $qr->batch_id,
                                    'status'            => $qr->status,
                                    'client_name'       => $qr->client->name ?? '—',
                                    'client_contact'    => $qr->client->contact ?? '—',
                                    'client_email'      => $qr->client->email ?? '—',
                                    'tank_items'        => [[
                                        'tank_type'       => $qr->tank_type,
                                        'capacity'        => $qr->capacity,
                                        'quantity'        => $qr->quantity,
                                        'target_timeline' => $qr->target_timeline_display,
                                    ]],
                                    'location'          => $qr->location,
                                    'notes'             => $qr->notes,
                                    'reference_files'   => $qr->reference_files ?? [],
                                    'quotation_files'   => !empty($qr->quotation_files) ? [$qr->quotation_files] : [],
                                    'decline_reason'    => $qr->decline_reason,
                                    'submitted_at'      => $qr->created_at->format('M d, Y \a\t g:i A'),
                                    'build_quotation_url' => route('admin.quotation_requests.batch_detail', $qr->batch_id),
                                    'convert_url'         => route('admin.projects', ['prefill_quotation_batch' => $qr->batch_id]),
                                ]) }}"
                                >
                                <td>{{ $qr->client->name ?? '—' }}</td>
                                <td>
                                    <div>{{ $qr->client->contact ?? '—' }}</div>
                                </td>
                                <td>
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
                                    @if(count($qr->reference_files ?? []) > 1)
                                    <span title="{{ count($qr->reference_files) }} files attached by the client" style="display:inline-flex;align-items:center;gap:3px;margin-left:6px;font-size:10.5px;font-weight:800;color:#6d28d9;background:#ede9fe;border-radius:999px;padding:3px 8px;box-shadow:0 0 0 1px rgba(109,40,217,.18);">
                                        {{ count($qr->reference_files) }}
                                    </span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($qr->capacity))
                                    <span class="qr-spec-chip qr-chip-capacity">
                                        <i data-lucide="droplet" style="width:11px;height:11px;"></i>
                                        {{ $qr->capacity }}
                                    </span>
                                    @else
                                    <span style="color:var(--muted);">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($qr->target_timeline))
                                    <span class="qr-spec-chip qr-chip-timeline">
                                        <i data-lucide="clock" style="width:11px;height:11px;"></i>
                                        {{ $qr->target_timeline_display }}
                                    </span>
                                    @else
                                    <span style="color:var(--muted);">—</span>
                                    @endif
                                </td>
                                <td style="white-space:normal;word-break:break-word;">{{ $qr->location }}</td>
                                <td>{{ $qr->created_at->format('M d, Y') }}</td>
                                <td>
                                    @if($qr->status === 'pending' && $qr->decline_reason)
                                    <span class="status-badge revision" title="{{ $qr->decline_reason }}">Revision Requested</span>
                                    @elseif($qr->status === 'pending')
                                    <span class="status-badge pending">Pending</span>
                                    @elseif($qr->status === 'quotation_sent')
                                    <span class="status-badge ongoing">Quotation Sent</span>
                                    @elseif($qr->status === 'approved')
                                    <span class="status-badge approved">Approved</span>
                                    @elseif($qr->status === 'converted')
                                    <span class="status-badge active">Converted</span>
                                    @else
                                    <span class="status-badge revision">Declined</span>
                                    @endif
                                </td>
                                <td class="action-cell">
                                    <button class="action-btn view view-request-btn" type="button" title="View Request">
                                        <i data-lucide="eye"></i>
                                    </button>
                                    @if(in_array($qr->status, ['pending', 'quotation_sent', 'approved']) && $qr->batch_id)
                                    <a class="action-btn view" type="button" title="Build Quotation"
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
                                    @if(in_array($qr->status, ['pending', 'quotation_sent']))
                                    <button class="action-btn view decline-request-btn" type="button"
                                            title="Decline Request"
                                            data-id="{{ $qr->batch_id }}"
                                            data-name="{{ $qr->client->name ?? '' }}">
                                        <i data-lucide="x"></i>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr id="quotationBladeEmpty">
                                <td colspan="9" style="text-align:center;padding:40px;color:var(--muted);">
                                    No quotation requests yet.
                                </td>
                            </tr>
                            @endforelse
                            <tr id="quotationEmptyState" style="display:none;">
                                <td colspan="9" style="text-align:center;padding:48px 20px;">
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
                            <tr data-search="{{ strtolower($g['client']) }}" onclick="window.location='{{ route('admin.project_materials.client', $g['client']) }}'" style="cursor:pointer;">
                                <td><span class="pf-client-name">{{ $g['client'] }}</span></td>
                                <td style="text-align:center;"><span class="client-pill" style="background-color:#F3F4F6;color:#1F2937;border-color:#D1D5DB;">{{ $g['total'] }}</span></td>
                                <td style="text-align:center;"><span class="client-pill" style="background-color:#EAF0FF;color:#2563EB;border-color:#BFDBFE;">{{ $g['active'] }}</span></td>
                                <td style="text-align:center;"><span class="client-pill" style="background-color:#E7F6EC;color:#207A3A;border-color:#A7E3B8;">{{ $g['completed'] }}</span></td>
                                <td style="text-align:center;"><span class="client-pill" style="background-color:#F3F4F6;color:#6B7280;border-color:#D1D5DB;">{{ $g['archived'] }}</span></td>
                                <td class="action-cell" style="text-align:center;">
                                    <a href="{{ route('admin.project_materials.client', $g['client']) }}" class="action-btn view" title="View Client's Projects" onclick="event.stopPropagation()">
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

                <div id="viewRequestReferenceFilesWrap" style="display:none;margin-bottom:20px;background:#f5f3ff;border:1.5px solid #ddd6fe;border-radius:14px;padding:14px 16px;">
                    <div style="display:flex;align-items:center;gap:7px;font-size:12px;font-weight:800;color:#6d28d9;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:10px;">
                        <i data-lucide="paperclip" style="width:14px;height:14px;"></i>
                        Client Attached Their Own Tank Files
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
                     style="display:none;margin-bottom:16px;background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;">
                    <i data-lucide="circle-alert"></i>
                    <span id="viewRequestDeclineReason"></span>
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
                        Enter materials, labor, and pricing for this quotation, then send it to the client for approval.
                    </div>
                    <div class="modal-actions" style="border-top:none;padding-top:0;">
                        <a id="buildQuotationBtn" href="#" class="save-btn" style="text-decoration:none;">
                            <i data-lucide="calculator"></i> Build Quotation
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
                    <textarea name="reason" class="log-textarea" rows="3"
                              placeholder="Let the client know why this request was declined..."></textarea>
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
                                : '<span style="width:64px;height:64px;border-radius:8px;background:#fff;border:1px solid #e0e3f5;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;flex-shrink:0;">'
                                    + '<i data-lucide="file-text" style="width:20px;height:20px;color:#6d28d9;"></i>'
                                    + '<span style="font-size:9.5px;font-weight:800;color:#6d28d9;text-transform:uppercase;">' + (ext || 'file') + '</span>'
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

                    var isRevisionRequest = r.status === 'pending' && !!r.decline_reason;
                    var meta = isRevisionRequest
                        ? { label: 'Revision Requested', cls: 'revision' }
                        : (statusMeta[r.status] || statusMeta.pending);
                    document.getElementById('viewRequestStatusBadge').innerHTML =
                        '<span class="status-badge ' + meta.cls + '">' + meta.label + '</span>';

                    var declineWrap = document.getElementById('viewRequestDeclineReasonWrap');
                    if (r.status === 'declined' && r.decline_reason) {
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

                    if (r.status === 'pending') {
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
                    openModal('declineRequestModal');
                });
            });
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
