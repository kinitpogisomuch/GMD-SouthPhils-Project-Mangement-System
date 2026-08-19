<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clients | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            <div class="page-header">
                <div>
                    <h1>Manage Clients</h1>
                    <p>View and manage client contact records and portal accounts.</p>
                </div>
                <button class="add-btn" type="button" id="openAddClientModal">
                    <i data-lucide="user-plus"></i>
                    Add Client
                </button>
            </div>

            @if(session('success'))
            <div class="alert-banner success">
                <i data-lucide="check-circle"></i>
                {{ session('success') }}
            </div>
            @endif

            <div class="table-card" style="padding-bottom:0;">
                <div class="table-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="clientSearch" placeholder="Search name or username...">
                    </div>
                    <div class="filter-tabs" id="clientStatusTabs">
                        <button type="button" class="filter-tab active" data-filter="">
                            All
                            <span class="filter-count">{{ $clients->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="Active">
                            Active
                            <span class="filter-count">{{ $clients->where('status', 'Active')->count() }}</span>
                        </button>
                        <button type="button" class="filter-tab" data-filter="Inactive">
                            Archived
                            <span class="filter-count">{{ $clients->where('status', 'Inactive')->count() }}</span>
                        </button>
                    </div>
                </div>
                <div style="max-height:570px;overflow-y:auto;">
                    <table class="data-table" id="clientsTable" style="margin:0;">
                        <thead style="position:sticky;top:0;z-index:2;">
                            <tr>
                                <th>Client Name</th>
                                <th>Username</th>
                                <th>Email Address</th>
                                <th>Contact Number</th>
                                <th>No. of Projects</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clients as $client)
                            <tr
                                data-status="{{ $client->status }}"
                                data-name="{{ strtolower($client->full_name) }}"
                                data-username="{{ strtolower($client->username ?? '') }}">
                                <td>
                                    {{ $client->full_name }}
                                </td>
                                <td>
                                    <span style="font-family:monospace;font-weight:600;color:var(--text-secondary);font-size:13px;">
                                        {{ $client->username ?? '—' }}
                                    </span>
                                </td>
                                <td>{{ $client->email ?? '—' }}</td>
                                <td>{{ $client->contact ?? '—' }}</td>
                                <td style="text-align:center;">
                                    <span style="font-weight:600;color:var(--text-primary);">{{ $client->projects_count ?? 0 }}</span>
                                </td>
                                <td>
                                    @if($client->status === 'Active')
                                    <span class="status-badge active">Active</span>
                                    @else
                                    <span class="status-badge archived">Archived</span>
                                    @endif
                                </td>
                                <td class="action-cell">
                                    <button class="action-btn view edit-client-btn" type="button"
                                            title="Edit Client Information"
                                            data-id="{{ $client->id }}"
                                            data-name="{{ $client->full_name }}"
                                            data-contact="{{ $client->contact }}"
                                            data-email="{{ $client->email }}"
                                            data-region="{{ $client->region }}"
                                            data-province="{{ $client->province }}"
                                            data-city="{{ $client->city }}"
                                            data-barangay="{{ $client->barangay }}"
                                            data-street-address="{{ $client->street_address }}">
                                        <i data-lucide="pencil"></i>
                                    </button>
                                    <button class="action-btn view archive-client-btn" type="button"
                                            title="{{ $client->status === 'Inactive' ? 'Restore Client' : 'Archive Client' }}"
                                            data-id="{{ $client->id }}"
                                            data-name="{{ $client->full_name }}"
                                            data-archived="{{ $client->status === 'Inactive' ? '1' : '0' }}">
                                        <i data-lucide="{{ $client->status === 'Inactive' ? 'archive-restore' : 'archive' }}"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr id="clientBladeEmpty">
                                <td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">
                                    No clients found. Click <strong>Add Client</strong> to get started.
                                </td>
                            </tr>
                            @endforelse
                            <tr id="clientEmptyState" style="display:none;">
                                <td colspan="7" style="text-align:center;padding:48px 20px;">
                                    <div style="display:flex;flex-direction:column;align-items:center;gap:10px;color:var(--muted);">
                                        <i data-lucide="inbox" style="width:36px;height:36px;opacity:0.4;"></i>
                                        <span id="clientEmptyMsg" style="font-size:14px;font-weight:600;">No clients found.</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- ===== CREDENTIALS SUCCESS MODAL ===== -->
    @if(session('new_client_username'))
    <div class="modal-overlay show" id="credentialsSuccessModal">
        <div class="modal-card" style="max-width:500px;">
            <div class="modal-header">
                <div>
                    <h2>Account Created Successfully</h2>
                    <p>{{ session('new_client_name') }} · {{ session('new_client_email') }}</p>
                </div>
                <button class="modal-close" type="button" onclick="closeModal('credentialsSuccessModal')">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div style="text-align:center;padding:8px 0 20px;">
                <div style="width:56px;height:56px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <i data-lucide="check-circle-2" style="width:28px;height:28px;color:#16a34a;"></i>
                </div>
                <p style="font-size:13.5px;color:var(--text-secondary);margin-bottom:20px;">
                    The client account has been created and credentials have been
                    @if(session('email_sent'))
                        <strong style="color:#16a34a;">successfully emailed</strong> to {{ session('new_client_email') }}.
                    @else
                        generated. <strong style="color:#dc2626;">Email delivery failed</strong> — please share credentials manually.
                        @if(session('email_error'))
                        <br><span style="font-size:11px;color:#6b7280;display:block;margin-top:6px;">Error: {{ session('email_error') }}</span>
                        @endif
                    @endif
                </p>
            </div>

            <div style="background:#f8f9ff;border:1.5px solid #dde1f5;border-radius:10px;padding:20px 24px;margin-bottom:20px;">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #eee;">
                    <span style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;">Username</span>
                    <span style="font-size:20px;font-weight:900;color:var(--text-primary);letter-spacing:2px;" id="credUsername">
                        {{ session('new_client_username') }}
                    </span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;">
                    <span style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;">PIN / Password</span>
                    <span style="font-size:20px;font-weight:900;color:var(--text-primary);letter-spacing:4px;" id="credPin">
                        {{ session('new_client_pin') }}
                    </span>
                </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="copyCredentials()" class="cancel-btn" style="display:flex;align-items:center;gap:6px;">
                    <i data-lucide="copy" style="width:14px;height:14px;"></i>
                    Copy Credentials
                </button>
                <button type="button" onclick="closeModal('credentialsSuccessModal')" class="save-btn">
                    Done
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- ===== ADD CLIENT MODAL ===== -->
    <div class="modal-overlay" id="addClientModal">
        <div class="modal-card" style="max-width:560px;">
            <div class="modal-header">
                <div>
                    <h2>Add Client</h2>
                    <p>Enter the client's contact information. If an email is provided, login credentials will be auto-generated and emailed.</p>
                </div>
                <button class="modal-close" type="button" id="closeAddClientModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.client.store') }}" id="addClientForm">
                @csrf
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Full Name / Company Name </label>
                        <input type="text" name="full_name" required
                               placeholder="e.g. Juan Dela Cruz or ABC Construction Co."
                               value="{{ old('full_name') }}">
                    </div>
                    <div class="form-group">
                        <label>Contact Number </label>
                        <input type="text" name="contact" id="addClientContact" required
                               placeholder="e.g. 0930-147-6598" maxlength="13"
                               value="{{ old('contact') }}"
                               oninput="formatClientContact(this)"
                               onkeypress="return /[0-9\-]/.test(event.key)">
                    </div>
                    <div class="form-group">
                        <label>Email Address <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                        <input type="email" name="email" id="clientEmailInput"
                               placeholder="Enter email (optional)"
                               value="{{ old('email') }}">
                    </div>
                </div>

                <div id="credentialsPreview" style="display:none;margin-top:14px;background:#f8f9ff;border:1.5px solid #dde1f5;border-radius:10px;padding:16px 20px;">
                    <div style="font-size:11px;font-weight:800;color:#6366f1;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:12px;display:flex;align-items:center;gap:6px;">
                        <i data-lucide="sparkles" style="width:13px;height:13px;"></i>
                        Auto-Generated Login Credentials
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div style="background:#fff;border:1px solid #e0e3f5;border-radius:8px;padding:12px;">
                            <div style="font-size:10.5px;font-weight:700;color:#888;text-transform:uppercase;margin-bottom:4px;">Username</div>
                            <div id="previewUsername" style="font-size:18px;font-weight:900;color:#1a1a2e;letter-spacing:2px;">CGMD-····</div>
                        </div>
                        <div style="background:#fff;border:1px solid #e0e3f5;border-radius:8px;padding:12px;">
                            <div style="font-size:10.5px;font-weight:700;color:#888;text-transform:uppercase;margin-bottom:4px;">PIN / Password</div>
                            <div id="previewPin" style="font-size:18px;font-weight:900;color:#1a1a2e;letter-spacing:3px;">······</div>
                        </div>
                    </div>
                    <div style="font-size:12px;color:#6b7280;margin-top:10px;display:flex;align-items:center;gap:5px;">
                        <i data-lucide="info" style="width:12px;height:12px;"></i>
                        These credentials will be emailed to the client upon account creation.
                    </div>
                </div>

                @if($errors->hasBag('client'))
                <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px;margin-top:4px;color:#dc2626;font-size:13px;">
                    @foreach($errors->getBag('client')->all() as $error)
                        <div>• {{ $error }}</div>
                    @endforeach
                </div>
                @endif

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelAddClient">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="user-plus"></i>
                        Add Client
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== EDIT CLIENT MODAL ===== -->
    <div class="modal-overlay" id="editClientModal">
        <div class="modal-card" style="max-width:560px;">
            <div class="modal-header">
                <div>
                    <h2>Edit Client Information</h2>
                    <p id="editClientSubtitle">Update client information.</p>
                </div>
                <button class="modal-close" type="button" id="closeEditClientModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" id="editClientForm">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Full Name / Company Name </label>
                        <input type="text" name="full_name" id="editClientFullName" required
                               placeholder="e.g. Juan Dela Cruz or ABC Construction Co.">
                    </div>
                    <div class="form-group">
                        <label>Contact Number </label>
                        <input type="text" name="contact" id="editClientContact" required placeholder="e.g. 0930-147-6598" maxlength="13"
                               oninput="formatClientContact(this)"
                               onkeypress="return /[0-9\-]/.test(event.key)">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" id="editClientEmail" placeholder="Email address">
                    </div>
                    <div class="form-group">
                        <label>Province</label>
                        <input type="text" name="province" id="editClientProvince" placeholder="e.g. Laguna"
                               oninput="clientStripDigits(this); clientCapName(this)">
                    </div>
                    <div class="form-group">
                        <label>City / Municipality</label>
                        <input type="text" name="city" id="editClientCity" placeholder="e.g. Santa Cruz"
                               oninput="clientStripDigits(this); clientCapName(this)">
                    </div>
                    <div class="form-group">
                        <label>Region</label>
                        <input type="text" name="region" id="editClientRegion" placeholder="e.g. Region IV-A"
                               oninput="clientCapName(this)">
                    </div>
                    <div class="form-group">
                        <label>Street Address</label>
                        <input type="text" name="street_address" id="editClientStreetAddress" placeholder="e.g. Poblacion Street"
                               oninput="clientCapName(this)">
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelEditClient">Cancel</button>
                    <button type="submit" class="save-btn"><i data-lucide="save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== ARCHIVE CLIENT MODAL ===== -->
    <div class="modal-overlay" id="archiveClientModal">
        <div class="modal-card" style="max-width:460px;">
            <div class="modal-header">
                <div>
                    <h2 id="archiveClientTitle">Archive Client</h2>
                    <p id="archiveClientSubtitle">This will affect the client's system access.</p>
                </div>
                <button class="modal-close" type="button" id="closeArchiveClientModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="delete-confirm-body">
                <div class="delete-confirm-icon"><i data-lucide="archive" id="archiveClientIcon"></i></div>
                <p id="archiveClientMsg">Are you sure you want to archive this client?</p>
                <p id="archiveClientNote" style="font-size:13px;color:var(--text-secondary);margin-top:8px;line-height:1.5;">
                    Archived clients will no longer be able to access the system, but all records and project information will be preserved.
                </p>
            </div>
            <form method="POST" id="archiveClientForm">
                @csrf
                @method('PATCH')
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelArchiveClient">Cancel</button>
                    <button type="submit" class="save-btn" id="archiveClientConfirmBtn">
                        <i data-lucide="archive"></i> Archive
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== SUCCESS TOAST ===== -->
    <div class="toast" id="successToast">
        <i data-lucide="check-circle"></i>
        <span id="toastMsg">Saved successfully.</span>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if($errors->hasBag('client'))
            openModal('addClientModal');
            @endif

            // ---- Add Client Modal ----
            document.getElementById('openAddClientModal')
                .addEventListener('click', function () { openModal('addClientModal'); });
            document.getElementById('closeAddClientModal')
                .addEventListener('click', function () { closeModal('addClientModal'); });
            document.getElementById('cancelAddClient')
                .addEventListener('click', function () { closeModal('addClientModal'); });

            // ---- Edit Client Modal ----
            document.querySelectorAll('.edit-client-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.getElementById('editClientFullName').value     = this.dataset.name || '';
                    document.getElementById('editClientContact').value      = this.dataset.contact || '';
                    document.getElementById('editClientEmail').value        = this.dataset.email || '';
                    document.getElementById('editClientProvince').value     = this.dataset.province || '';
                    document.getElementById('editClientCity').value         = this.dataset.city || '';
                    document.getElementById('editClientRegion').value       = this.dataset.region || '';
                    document.getElementById('editClientStreetAddress').value = this.dataset.streetAddress || '';
                    document.getElementById('editClientSubtitle').textContent = 'Editing: ' + (this.dataset.name || '');
                    document.getElementById('editClientForm').action = '/admin/clients/' + this.dataset.id;
                    openModal('editClientModal');
                });
            });
            document.getElementById('closeEditClientModal')
                .addEventListener('click', function () { closeModal('editClientModal'); });
            document.getElementById('cancelEditClient')
                .addEventListener('click', function () { closeModal('editClientModal'); });

            // ---- Archive Client Modal ----
            document.querySelectorAll('.archive-client-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var isArchived = this.dataset.archived === '1';
                    var name = this.dataset.name;

                    document.getElementById('archiveClientTitle').textContent =
                        isArchived ? 'Restore Client' : 'Archive Client';
                    document.getElementById('archiveClientSubtitle').textContent =
                        isArchived ? 'This will restore the client\'s system access.' : 'This will affect the client\'s system access.';
                    document.getElementById('archiveClientMsg').textContent = isArchived
                        ? 'Restore "' + name + '"? Their portal access will be re-enabled.'
                        : 'Are you sure you want to archive "' + name + '"?';
                    document.getElementById('archiveClientNote').style.display = isArchived ? 'none' : '';
                    document.getElementById('archiveClientConfirmBtn').innerHTML = isArchived
                        ? '<i data-lucide="archive-restore"></i> Restore'
                        : '<i data-lucide="archive"></i> Archive';
                    document.getElementById('archiveClientForm').action = '/admin/clients/' + this.dataset.id + '/archive';
                    openModal('archiveClientModal');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            });
            document.getElementById('closeArchiveClientModal')
                .addEventListener('click', function () { closeModal('archiveClientModal'); });
            document.getElementById('cancelArchiveClient')
                .addEventListener('click', function () { closeModal('archiveClientModal'); });

            // ---- Overlay click to close ----
            document.querySelectorAll('.modal-overlay').forEach(function (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === this) closeModal(this.id);
                });
            });

            // ---- Search, Filter & Sort ----
            var currentClientStatusFilter = '';

            function filterClients() {
                var q      = document.getElementById('clientSearch').value.toLowerCase();
                var status = currentClientStatusFilter;
                var visible = 0;
                document.querySelectorAll('#clientsTable tbody tr[data-name]').forEach(function (row) {
                    var matchQ      = !q || row.dataset.name.includes(q) || row.dataset.username.includes(q);
                    var matchStatus = !status || row.dataset.status === status;
                    var show = matchQ && matchStatus;
                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
                var bladeEmpty = document.getElementById('clientBladeEmpty');
                if (bladeEmpty) bladeEmpty.style.display = (q || status) ? 'none' : '';

                var emptyRow = document.getElementById('clientEmptyState');
                var emptyMsg = document.getElementById('clientEmptyMsg');
                var filterActive = q || status;
                if (emptyRow) {
                    if (!visible && filterActive) {
                        if (emptyMsg) emptyMsg.textContent =
                            status === 'Inactive' ? 'No archived clients.' :
                            status === 'Active'   ? 'No active clients.' :
                                                   'No clients match your search.';
                        emptyRow.style.display = '';
                        if (window.lucide) lucide.createIcons();
                    } else {
                        emptyRow.style.display = 'none';
                    }
                }
            }

            document.getElementById('clientSearch').addEventListener('keyup', filterClients);
            document.querySelectorAll('#clientStatusTabs .filter-tab').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    document.querySelectorAll('#clientStatusTabs .filter-tab').forEach(function (t) { t.classList.remove('active'); });
                    this.classList.add('active');
                    currentClientStatusFilter = this.dataset.filter;
                    filterClients();
                });
            });
            // Apply default filter (All) on load
            filterClients();

            // ---- Credential preview (Add Client modal) ----
            (function () {
                var emailInput  = document.getElementById('clientEmailInput');
                var preview     = document.getElementById('credentialsPreview');
                var userEl      = document.getElementById('previewUsername');
                var pinEl       = document.getElementById('previewPin');
                var debounce    = null;

                function randomPin() {
                    return String(Math.floor(Math.random() * 1000000)).padStart(6, '0');
                }

                function fetchNextUsername() {
                    return fetch('{{ route("admin.client-account.next-username") }}', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (d) { return d.username || 'CGMD-0001'; })
                    .catch(function () { return 'CGMD-0001'; });
                }

                if (!emailInput) return;

                emailInput.addEventListener('input', function () {
                    var val = this.value.trim();
                    if (!val || !val.includes('@')) {
                        preview.style.display = 'none';
                        return;
                    }
                    clearTimeout(debounce);
                    debounce = setTimeout(function () {
                        pinEl.textContent  = randomPin();
                        userEl.textContent = 'CGMD-····';
                        preview.style.display = 'block';
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                        fetchNextUsername().then(function (u) { userEl.textContent = u; });
                    }, 400);
                });

                ['cancelAddClient', 'closeAddClientModal'].forEach(function (id) {
                    var btn = document.getElementById(id);
                    if (btn) btn.addEventListener('click', function () {
                        preview.style.display = 'none';
                        pinEl.textContent  = '······';
                        userEl.textContent = 'CGMD-····';
                    });
                });
            })();
        });

        function openModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
        }
        function closeModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
        }

        function copyCredentials() {
            var username = document.getElementById('credUsername').textContent.trim();
            var pin      = document.getElementById('credPin').textContent.trim();
            var text     = 'Username: ' + username + '\nPIN: ' + pin;
            navigator.clipboard.writeText(text).then(function () {
                showToast('Credentials copied to clipboard!');
            }).catch(function () {
                var ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                showToast('Credentials copied!');
            });
        }

        function clientStripDigits(input) {
            var pos = input.selectionStart;
            var cleaned = input.value.replace(/[0-9]/g, '');
            if (cleaned !== input.value) {
                input.value = cleaned;
                input.setSelectionRange(Math.max(0, pos - 1), Math.max(0, pos - 1));
            }
        }

        function clientCapName(input) {
            var pos = input.selectionStart;
            input.value = input.value.replace(/(^|[\s'\-])([a-zà-öø-ÿñ])/g, function(_, sep, ch) {
                return sep + ch.toUpperCase();
            });
            input.setSelectionRange(pos, pos);
        }

        function showToast(msg) {
            var toast = document.getElementById('successToast');
            var label = document.getElementById('toastMsg');
            if (!toast) return;
            if (label) label.textContent = msg || 'Saved successfully.';
            toast.classList.add('show');
            setTimeout(function () { toast.classList.remove('show'); }, 3000);
        }

        function formatClientContact(input) {
            // Strip everything except digits
            var digits = input.value.replace(/\D/g, '').substring(0, 11);
            var formatted = digits;
            // Apply 0930-147-6598 format (4-3-4)
            if (digits.length > 4 && digits.length <= 7) {
                formatted = digits.substring(0,4) + '-' + digits.substring(4);
            } else if (digits.length > 7) {
                formatted = digits.substring(0,4) + '-' + digits.substring(4,7) + '-' + digits.substring(7);
            }
            input.value = formatted;
        }
    </script>
</body>
</html>
