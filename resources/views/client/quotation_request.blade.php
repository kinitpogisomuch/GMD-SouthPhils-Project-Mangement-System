<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
    <style>
        /* Keep the card sized to its content instead of stretching edge-to-edge
           on wide screens — with only 4 compact fields per tank row, a full-width
           card left a large empty gap on the right. */
        #quotationCard, #quotationHeader { max-width:1060px; margin-left:auto; margin-right:auto; }
        #quotationHeader { text-align:center; justify-content:center; }

        .qr-section-label {
            display:flex;align-items:center;gap:8px;
            font-size:13px;font-weight:800;color:var(--dark);margin-bottom:10px;
        }
        .qr-section-label i { width:15px;height:15px;color:var(--muted); }

        .qr-tank-row {
            display:flex; align-items:stretch; position:relative;
            background:#fff;border:1px solid var(--border);border-radius:16px;
            margin-bottom:14px;overflow:hidden;
            box-shadow:0 1px 2px rgba(0,0,0,.03);
            transition:box-shadow .2s ease, border-color .2s ease;
        }
        .qr-tank-row:focus-within { border-color:var(--dark); box-shadow:0 4px 16px rgba(0,0,0,.08); }

        .qr-tank-badge {
            flex-shrink:0; width:76px;
            background:var(--dark); color:#fff;
            display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px;
        }
        .qr-tank-badge-num { font-size:19px; font-weight:900; line-height:1; }
        .qr-tank-badge-label {
            font-size:10.5px; font-weight:800;
            text-transform:uppercase; letter-spacing:.05em;
        }
        .qr-tank-remove {
            position:absolute; top:8px; right:8px; z-index:1;
            background:none;border:none;cursor:pointer;color:var(--muted);
            display:flex;align-items:center;justify-content:center;
            width:26px;height:26px;border-radius:8px;flex-shrink:0;
            transition:background .15s ease, color .15s ease;
        }
        .qr-tank-remove:hover { background:#fee2e2;color:#dc2626; }
        .qr-tank-remove svg { width:14px;height:14px; }

        /* flex (not grid) so fields size to their content instead of stretching
           to fill the row — a grid's fr columns always consume 100% of the row
           width, which left a big empty gap trailing the last field on wide screens. */
        .qr-tank-main { flex:1; min-width:0; display:flex; flex-direction:column; }
        .qr-tank-row-grid {
            flex:1; display:flex; flex-wrap:wrap; gap:16px; align-items:end;
            padding:16px 40px 10px 18px;
        }
        .qr-tank-design { padding:0 40px 14px 18px; }
        .qr-design-btn {
            display:inline-flex; align-items:center; gap:7px; cursor:pointer;
            background:var(--cream-soft); border:1.5px dashed var(--border); border-radius:10px;
            padding:7px 13px; font-size:12px; font-weight:800; color:var(--dark);
            transition:background .15s ease, border-color .15s ease;
        }
        .qr-design-btn:hover { background:var(--accent-soft); border-color:var(--dark); }
        .qr-design-btn i, .qr-design-btn svg { width:13px; height:13px; }
        .qr-design-btn span { font-weight:600; color:var(--muted); }
        .qr-design-list { margin-top:10px; }
        .qr-design-error { margin-top:8px; font-size:12px; font-weight:700; color:#b91c1c; }

        .qr-loc-hint { font-size:12.5px; color:var(--muted); line-height:1.6; margin:0 0 14px; }
        .qr-loc-hint strong { color:var(--dark); }
        .qr-tank-fulfill { padding:0 40px 10px 18px; display:flex; flex-direction:column; gap:10px; }
        .qr-fulfill-opts { display:inline-flex; gap:8px; flex-wrap:wrap; }
        .qr-fulfill-opt {
            display:inline-flex; align-items:center; gap:7px; cursor:pointer; user-select:none;
            border:1.5px solid var(--border); border-radius:999px; background:var(--white);
            padding:7px 14px; font-size:12.5px; font-weight:800; color:var(--muted);
            transition:background .15s ease, border-color .15s ease, color .15s ease;
        }
        .qr-fulfill-opt input { position:absolute; opacity:0; pointer-events:none; }
        .qr-fulfill-opt i, .qr-fulfill-opt svg { width:14px; height:14px; }
        .qr-fulfill-opt:hover { border-color:var(--dark); }
        .qr-fulfill-opt.is-active { background:var(--dark); border-color:var(--dark); color:#fff; }
        .qr-tank-address { max-width:560px; }
        .qr-tank-row-grid .form-group { flex:1 1 190px; max-width:260px; margin-bottom:0; }
        .qr-tank-row-grid .form-group:nth-child(1) { flex:1.5 1 220px; max-width:320px; }
        .qr-tank-row-grid .form-group:nth-child(3) { flex:0 0 90px; max-width:90px; }
        @media (max-width:640px) {
            .qr-tank-row { flex-direction:column; }
            .qr-tank-badge { width:100%; flex-direction:row; padding:8px 0; }
            .qr-tank-row-grid .form-group,
            .qr-tank-row-grid .form-group:nth-child(1),
            .qr-tank-row-grid .form-group:nth-child(3) { flex:1 1 100%; max-width:none; }
        }

        .qr-add-tank-btn {
            display:flex;align-items:center;justify-content:center;gap:8px;
            width:100%;padding:14px;border:1.5px dashed var(--border);border-radius:16px;
            background:var(--cream-soft);color:var(--dark);font-weight:800;font-size:13.5px;
            cursor:pointer;transition:background .2s ease, border-color .2s ease;
        }
        .qr-add-tank-btn:hover { background:var(--accent-soft);border-color:var(--dark); }
        .qr-add-tank-btn svg { width:16px;height:16px; }

        .qr-file-list {
            display:flex; flex-wrap:wrap; gap:10px; margin-top:12px;
        }
        .qr-file-chip {
            display:flex; align-items:center; gap:8px; cursor:pointer;
            background:#fff; border:1px solid var(--border); border-radius:12px;
            padding:6px 8px 6px 6px; max-width:220px;
            transition:border-color .15s ease, box-shadow .15s ease;
        }
        .qr-file-chip:hover { border-color:var(--dark); box-shadow:0 2px 8px rgba(0,0,0,.06); }
        .qr-file-thumb {
            width:34px; height:34px; border-radius:8px; flex-shrink:0;
            background:var(--cream-soft); object-fit:cover;
            display:flex; align-items:center; justify-content:center;
        }
        .qr-file-thumb i { width:16px; height:16px; color:var(--muted); }
        .qr-file-meta { min-width:0; flex:1; }
        .qr-file-name {
            font-size:12px; font-weight:700; color:var(--dark);
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        }
        .qr-file-size { font-size:10.5px; color:var(--muted); font-weight:600; }
        .qr-file-remove {
            background:none; border:none; cursor:pointer; color:var(--muted);
            display:flex; align-items:center; justify-content:center;
            width:22px; height:22px; border-radius:7px; flex-shrink:0;
            transition:background .15s ease, color .15s ease;
        }
        .qr-file-remove:hover { background:#fee2e2; color:#dc2626; }
        .qr-file-remove svg { width:13px; height:13px; }
        .qr-file-add {
            width:48px; height:48px; border-radius:12px; flex-shrink:0;
            border:1.5px dashed var(--border); background:var(--cream-soft);
            display:flex; align-items:center; justify-content:center;
            cursor:pointer; color:var(--dark);
            transition:background .15s ease, border-color .15s ease;
        }
        .qr-file-add:hover { background:var(--accent-soft); border-color:var(--dark); }
        .qr-file-add svg { width:18px; height:18px; }

        .qr-divider {
            height:1px;background:var(--border);margin:24px 0;
        }

        .form-group textarea { resize:none; }

        .qr-submit-row {
            display:flex;align-items:center;justify-content:space-between;gap:16px;
            margin-top:24px;flex-wrap:wrap;
        }
        .qr-submit-hint { font-size:12.5px;color:var(--muted); }
    </style>
</head>
<body class="page-enter">

    @include('partials.client.header')

    <main class="admin-content">

        <div class="page-header" id="quotationHeader">
            <div>
                <h1 class="page-title">Quotation</h1>
                <p class="page-subtitle">Request a new quotation, follow what GMD South Phils is reviewing for you, or look back at past requests.</p>
            </div>
        </div>

        @if(session('success'))
        <div class="alert-banner success" style="max-width:820px;margin:0 auto 18px;">
            <i data-lucide="check-circle"></i>
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="alert-banner" style="background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;max-width:820px;margin:0 auto 18px;">
            <i data-lucide="circle-alert"></i>
            {{ session('error') }}
        </div>
        @endif

        @php
            $pendingBatches = $pendingBatches ?? collect();
            $historyBatches = $historyBatches ?? collect();
            $hasPending     = $pendingBatches->isNotEmpty();
            // Opens on Pending when something is under review, otherwise the form; ?tab= picks one directly
            $activeTab      = in_array(request('tab'), ['new-request', 'pending', 'history'], true)
                ? request('tab')
                : ($hasPending ? 'pending' : 'new-request');
            $batchStatus    = fn ($batch) => $batch->contains(fn ($qr) => $qr->status === 'quotation_sent')
                ? 'quotation_sent'
                : ($batch->contains(fn ($qr) => $qr->status === 'approved') ? 'approved' : 'pending');
        @endphp

        <div style="max-width:820px;margin:0 auto 24px;display:flex;justify-content:center;">
            <div class="emp-tabs">
                <button type="button" class="emp-tab {{ $activeTab === 'new-request' ? 'active' : '' }}" data-tab="new-request" onclick="activateQuotationTab('new-request', this)">
                    <i data-lucide="clipboard-list"></i>
                    New Request
                </button>
                <button type="button" class="emp-tab {{ $activeTab === 'pending' ? 'active' : '' }}" data-tab="pending" onclick="activateQuotationTab('pending', this)">
                    <i data-lucide="clock"></i>
                    Pending
                    @if($hasPending)
                    <span class="filter-count">{{ $pendingBatches->count() }}</span>
                    @endif
                </button>
                <button type="button" class="emp-tab {{ $activeTab === 'history' ? 'active' : '' }}" data-tab="history" onclick="activateQuotationTab('history', this)">
                    <i data-lucide="history"></i>
                    History
                </button>
            </div>
        </div>

        <div class="emp-tab-content {{ $activeTab === 'pending' ? 'active' : '' }}" id="tab-pending" style="max-width:820px;margin:0 auto;">
            @if($hasPending)
                @if($pendingBatches->count() > 1)
                <div class="filter-tabs" style="margin-bottom:20px;width:fit-content;">
                    <button type="button" class="filter-tab active" data-filter="all" onclick="filterQuotationBatches('all', this)">
                        All
                        <span class="filter-count">{{ $pendingBatches->count() }}</span>
                    </button>
                    <button type="button" class="filter-tab" data-filter="pending" onclick="filterQuotationBatches('pending', this)">
                        Under Review
                        <span class="filter-count">{{ $pendingBatches->filter(fn ($b) => $batchStatus($b) === 'pending')->count() }}</span>
                    </button>
                    <button type="button" class="filter-tab" data-filter="quotation_sent" onclick="filterQuotationBatches('quotation_sent', this)">
                        Quotation Ready
                        <span class="filter-count">{{ $pendingBatches->filter(fn ($b) => $batchStatus($b) === 'quotation_sent')->count() }}</span>
                    </button>
                    <button type="button" class="filter-tab" data-filter="approved" onclick="filterQuotationBatches('approved', this)">
                        Approved
                        <span class="filter-count">{{ $pendingBatches->filter(fn ($b) => $batchStatus($b) === 'approved')->count() }}</span>
                    </button>
                </div>
                @endif

                <div style="display:flex;flex-direction:column;gap:20px;">
                    @foreach($pendingBatches as $batch)
                    <div data-quotation-batch data-status="{{ $batchStatus($batch) }}">
                        @include('partials.client.quotation_batch_card', ['batch' => $batch])
                    </div>
                    @endforeach
                </div>
            @else
                <div class="pv-card" style="text-align:center;padding:48px 20px;">
                    <i data-lucide="inbox" style="width:36px;height:36px;color:var(--muted);opacity:.5;display:block;margin:0 auto 12px;"></i>
                    <p style="font-weight:800;color:var(--dark);margin-bottom:6px;">No pending requests.</p>
                    <p style="font-size:13px;color:var(--muted);">Submit a new request from the "New Request" tab and it'll show up here while GMD reviews it.</p>
                </div>
            @endif
        </div>

        <div class="emp-tab-content {{ $activeTab === 'history' ? 'active' : '' }}" id="tab-history" style="max-width:820px;margin:0 auto;">
            <div style="display:flex;flex-direction:column;gap:24px;">
            @forelse($historyBatches as $batch)
                @include('partials.client.quotation_batch_card', ['batch' => $batch])
            @empty
                <div class="pv-card" style="text-align:center;padding:48px 20px;">
                    <i data-lucide="history" style="width:36px;height:36px;color:var(--muted);opacity:.5;display:block;margin:0 auto 12px;"></i>
                    <p style="font-weight:800;color:var(--dark);margin-bottom:6px;">No completed quotations yet.</p>
                    <p style="font-size:13px;color:var(--muted);">Accepted or declined requests will show up here once a decision has been made.</p>
                </div>
            @endforelse
            </div>
        </div>
        <div class="emp-tab-content {{ $activeTab === 'new-request' ? 'active' : '' }}" id="tab-new-request">

        @if($errors->any())
        <div class="alert-banner" style="background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;max-width:1060px;margin-left:auto;margin-right:auto;">
            <i data-lucide="circle-alert"></i>
            <div>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="pv-card" id="quotationCard">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
                <i data-lucide="clipboard-list" style="width:18px;height:18px;color:var(--accent);"></i>
                <h3 class="pv-card-title" style="margin-bottom:0;">Project Details</h3>
            </div>

            <form method="POST" action="{{ route('client.quotation.store') }}" id="quotationRequestForm" enctype="multipart/form-data">
                @csrf

                <div class="alert-banner info" style="margin-bottom:18px;">
                    <i data-lucide="info"></i>
                    Tell us what tank(s) you need below — at least one tank is required. If you already have a tank design or photos of one, you can attach them further down (optional).
                </div>

                <div class="qr-section-label">
                    <i data-lucide="package"></i>
                    Tank Requirements <span style="color:#dc2626;">*</span> <span style="font-size:11px;color:var(--muted);font-weight:400;text-transform:none;">(required)</span>
                </div>
                <p class="qr-loc-hint">
                    For each tank, choose whether it is <strong>for delivery</strong> (then tell us where) or <strong>for pick-up</strong>.
                    If you already have a design for a tank, you can attach it on that tank (optional).
                </p>

                <div id="tankItemsContainer"></div>

                <button type="button" class="qr-add-tank-btn" id="addTankItemBtn">
                    <i data-lucide="plus-circle"></i>
                    <span>Add Another Tank</span>
                </button>

                <div class="qr-divider"></div>

                <div class="form-group">
                    <label>Additional Notes <span style="font-size:11px;color:var(--muted);font-weight:400;">(optional)</span></label>
                    <textarea name="notes" rows="4"
                              placeholder="Any other details that would help us prepare your quotation...">{{ old('notes') }}</textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Submitted Date <span style="font-size:11px;color:var(--muted);font-weight:400;">(optional — leave blank for today)</span></label>
                        <input type="date" name="submitted_date" value="{{ old('submitted_date') }}">
                    </div>
                    <div class="form-group">
                        <label>Submitted Time <span style="font-size:11px;color:var(--muted);font-weight:400;">(optional)</span></label>
                        <input type="time" name="submitted_time" value="{{ old('submitted_time') }}">
                    </div>
                </div>

                <div class="qr-submit-row">
                    <span class="qr-submit-hint">
                        <i data-lucide="info" style="width:12px;height:12px;vertical-align:-1px;"></i>
                        GMD South Phils will review your request and send a quotation shortly.
                    </span>
                    <button type="submit" class="save-btn">
                        <i data-lucide="send"></i>
                        Submit Request
                    </button>
                </div>
            </form>
        </div>

        </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        function openModal(id) { var m = document.getElementById(id); if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; } }
        function closeModal(id) { var m = document.getElementById(id); if (m) { m.classList.remove('show'); document.body.style.overflow = ''; } }
        document.querySelectorAll('.modal-overlay').forEach(function (modal) {
            modal.addEventListener('click', function (e) { if (e.target === this) closeModal(this.id); });
        });

        function activateQuotationTab(name, btn) {
            document.querySelectorAll('.emp-tab[data-tab]').forEach(function (t) { t.classList.remove('active'); });
            btn.classList.add('active');
            document.querySelectorAll('.emp-tab-content').forEach(function (c) { c.classList.remove('active'); });
            document.getElementById('tab-' + name).classList.add('active');
        }

        function filterQuotationBatches(filter, btn) {
            document.querySelectorAll('.filter-tab[data-filter]').forEach(function (tab) {
                tab.classList.remove('active');
            });
            btn.classList.add('active');

            document.querySelectorAll('[data-quotation-batch]').forEach(function (el) {
                var show = filter === 'all' || el.dataset.status === filter;
                el.style.display = show ? '' : 'none';
            });
        }

        const TANK_TYPES      = @json($tankTypes);
        const OLD_TANK_ITEMS  = @json(old('tank_items', []));
        const DEFAULT_ADDRESS = @json($client->address ?? '');
        const MAX_DESIGN_FILES = 5;
        const MAX_DESIGN_BYTES = 10 * 1024 * 1024;

        function esc(s) {
            return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function tankTypeOptions(selected) {
            var html = '<option value="" disabled ' + (selected ? '' : 'selected') + ' hidden>Select tank type</option>';
            TANK_TYPES.forEach(function (t) {
                html += '<option value="' + esc(t) + '"' + (t === selected ? ' selected' : '') + '>' + esc(t) + '</option>';
            });
            return html;
        }

        function asList(v) { return Array.isArray(v) ? v : (v && typeof v === 'object' ? Object.values(v) : []); }

        // ── Tanks: each one is either for delivery (with its own address) or for pick-up ──
        var tankSeq = 0;
        var tankBox = document.getElementById('tankItemsContainer');

        function addTank(item) {
            item = item || {};
            var T = tankSeq++;
            var prefix = 'tank_items[' + T + ']';
            var pickup = item.fulfillment === 'pickup';

            var row = document.createElement('div');
            row.className = 'qr-tank-row';
            row.innerHTML =
                '<div class="qr-tank-badge">' +
                    '<span class="qr-tank-badge-num"></span>' +
                    '<span class="qr-tank-badge-label">Tank</span>' +
                '</div>' +
                '<div class="qr-tank-main">' +
                    '<div class="qr-tank-row-grid">' +
                        '<div class="form-group" style="margin-bottom:0;">' +
                            '<label>Tank Type <span style="color:#dc2626;">*</span></label>' +
                            '<select name="' + prefix + '[tank_type]" required>' + tankTypeOptions(item.tank_type) + '</select>' +
                        '</div>' +
                        '<div class="form-group" style="margin-bottom:0;">' +
                            '<label>Capacity / Size <span style="color:#dc2626;">*</span></label>' +
                            '<input type="text" name="' + prefix + '[capacity]" required placeholder="e.g. 10,000 liters" value="' + esc(item.capacity || '') + '">' +
                        '</div>' +
                        '<div class="form-group" style="margin-bottom:0;">' +
                            '<label>Qty <span style="color:#dc2626;">*</span></label>' +
                            '<input type="number" name="' + prefix + '[quantity]" required min="1" value="' + esc(item.quantity || 1) + '">' +
                        '</div>' +
                        '<div class="form-group" style="margin-bottom:0;">' +
                            '<label>Target Date of Delivery</label>' +
                            '<input type="date" name="' + prefix + '[target_timeline]" value="' + esc(item.target_timeline || '') + '">' +
                        '</div>' +
                    '</div>' +
                    // delivery or pick-up — the address only exists for delivery
                    '<div class="qr-tank-fulfill">' +
                        '<div class="qr-fulfill-opts" role="radiogroup" aria-label="Delivery or pick-up">' +
                            '<label class="qr-fulfill-opt' + (pickup ? '' : ' is-active') + '"><input type="radio" name="' + prefix + '[fulfillment]" value="delivery"' + (pickup ? '' : ' checked') + '><i data-lucide="truck"></i> For Delivery</label>' +
                            '<label class="qr-fulfill-opt' + (pickup ? ' is-active' : '') + '"><input type="radio" name="' + prefix + '[fulfillment]" value="pickup"' + (pickup ? ' checked' : '') + '><i data-lucide="package-check"></i> For Pick-up</label>' +
                        '</div>' +
                        '<div class="form-group qr-tank-address" style="margin-bottom:0;' + (pickup ? 'display:none;' : '') + '">' +
                            '<label>Delivery Address <span style="color:#dc2626;">*</span></label>' +
                            '<textarea name="' + prefix + '[address]" rows="2"' + (pickup ? ' disabled' : ' required') +
                                ' placeholder="Where should this tank be delivered / installed?">' + esc(item.address != null ? item.address : DEFAULT_ADDRESS) + '</textarea>' +
                        '</div>' +
                    '</div>' +
                    // this tank's own (optional) design — belongs to this tank only
                    '<div class="qr-tank-design">' +
                        '<button type="button" class="qr-design-btn"><i data-lucide="paperclip"></i> Attach this tank\'s design <span>(optional — PDF, image or .dwg)</span></button>' +
                        '<input type="file" class="qr-design-input" name="' + prefix + '[design_files][]" accept=".pdf,image/*,.dwg" multiple style="display:none;">' +
                        '<div class="qr-file-list qr-design-list" style="display:none;"></div>' +
                        '<div class="qr-design-error" style="display:none;"></div>' +
                    '</div>' +
                '</div>' +
                '<button type="button" class="qr-tank-remove" onclick="removeTank(this)" title="Remove tank"><i data-lucide="x"></i></button>';
            tankBox.appendChild(row);

            wireFulfillment(row);
            wireDesignPicker(row);
            refreshChrome();
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        // showing the address only for delivery: hidden + disabled for pick-up so it is never required or sent
        function wireFulfillment(row) {
            var wrap = row.querySelector('.qr-tank-address');
            var area = wrap.querySelector('textarea');
            row.querySelectorAll('.qr-fulfill-opt input').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    var delivery = row.querySelector('.qr-fulfill-opt input[value="delivery"]').checked;
                    wrap.style.display = delivery ? '' : 'none';
                    area.disabled = !delivery;
                    area.required = delivery;
                    row.querySelectorAll('.qr-fulfill-opt').forEach(function (opt) {
                        opt.classList.toggle('is-active', opt.querySelector('input').checked);
                    });
                    if (delivery) area.focus();
                });
            });
        }

        function removeTank(btn) {
            if (tankBox.querySelectorAll('.qr-tank-row').length <= 1) return;   // keep at least one tank
            btn.closest('.qr-tank-row').remove();
            refreshChrome();
        }

        // numbering + whether the remove button is available
        function refreshChrome() {
            var rows = tankBox.querySelectorAll('.qr-tank-row');
            rows.forEach(function (row, i) {
                row.querySelector('.qr-tank-badge-num').textContent = i + 1;
                row.querySelector('.qr-tank-remove').style.display = rows.length > 1 ? '' : 'none';
            });
        }

        // ── Per-tank design files ──
        // A native <input type=file> replaces its whole FileList on every pick, so each tank keeps its own
        // running list and writes it back to its input through DataTransfer before submit.
        function wireDesignPicker(row) {
            var input = row.querySelector('.qr-design-input');
            var btn   = row.querySelector('.qr-design-btn');
            var list  = row.querySelector('.qr-design-list');
            var error = row.querySelector('.qr-design-error');
            var files = [];

            function fmt(b) { return b < 1024 ? b + ' B' : (b < 1048576 ? Math.round(b / 1024) + ' KB' : (b / 1048576).toFixed(1) + ' MB'); }
            function sync() { var dt = new DataTransfer(); files.forEach(function (f) { dt.items.add(f); }); input.files = dt.files; }
            function say(msg) { error.textContent = msg; error.style.display = msg ? 'block' : 'none'; }

            function render() {
                list.style.display = files.length ? 'flex' : 'none';
                list.innerHTML = '';
                files.forEach(function (f, i) {
                    var chip = document.createElement('div');
                    chip.className = 'qr-file-chip';
                    chip.title = 'Click to view';
                    var thumb = /^image\//.test(f.type)
                        ? '<img class="qr-file-thumb" src="' + URL.createObjectURL(f) + '" alt="">'
                        : '<span class="qr-file-thumb"><i data-lucide="file-text"></i></span>';
                    chip.innerHTML = thumb +
                        '<div class="qr-file-meta"><div class="qr-file-name" title="' + esc(f.name) + '">' + esc(f.name) + '</div>' +
                        '<div class="qr-file-size">' + fmt(f.size) + '</div></div>' +
                        '<button type="button" class="qr-file-remove" title="Remove"><i data-lucide="x"></i></button>';
                    chip.addEventListener('click', function (e) {
                        if (e.target.closest('.qr-file-remove')) return;
                        window.open(URL.createObjectURL(f), '_blank');
                    });
                    chip.querySelector('.qr-file-remove').addEventListener('click', function () {
                        files.splice(i, 1); sync(); say(''); render();
                    });
                    list.appendChild(chip);
                });
                // Once a file is attached the big button gives way to the files themselves plus a small "+" tile
                // (until the limit is reached); with no files, the button is all there is.
                btn.style.display = files.length ? 'none' : '';
                if (files.length && files.length < MAX_DESIGN_FILES) {
                    var add = document.createElement('button');
                    add.type = 'button';
                    add.className = 'qr-file-add';
                    add.title = 'Add another file';
                    add.innerHTML = '<i data-lucide="plus"></i>';
                    add.addEventListener('click', function () { input.value = ''; input.click(); });
                    list.appendChild(add);
                }
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }

            btn.addEventListener('click', function () { input.value = ''; input.click(); });
            input.addEventListener('change', function () {
                var problems = [];
                Array.from(input.files || []).forEach(function (f) {
                    var dup = files.some(function (x) { return x.name === f.name && x.size === f.size && x.lastModified === f.lastModified; });
                    if (dup) return;
                    if (!/\.(pdf|jpe?g|png|dwg)$/i.test(f.name)) { problems.push(f.name + ' is not a PDF, image or .dwg file.'); return; }
                    if (f.size > MAX_DESIGN_BYTES)               { problems.push(f.name + ' is larger than 10MB.'); return; }
                    if (files.length >= MAX_DESIGN_FILES)        { problems.push('Only ' + MAX_DESIGN_FILES + ' files per tank.'); return; }
                    files.push(f);
                });
                sync();
                say(problems.filter(function (p, i, a) { return a.indexOf(p) === i; }).join(' '));
                render();
            });
        }

        document.getElementById('addTankItemBtn').addEventListener('click', function () { addTank({}); });

        // start with one tank (or restore what was typed before a validation error)
        var oldTanks = asList(OLD_TANK_ITEMS);
        if (oldTanks.length) oldTanks.forEach(function (t) { addTank(t); });
        else addTank({});

        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
    @include('partials.receipt_viewer')

</body>
</html>
