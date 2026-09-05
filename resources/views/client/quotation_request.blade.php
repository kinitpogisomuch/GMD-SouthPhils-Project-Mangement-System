<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request a Quotation | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
    <style>
        .qr-section-label {
            display:flex;align-items:center;gap:8px;
            font-size:13px;font-weight:800;color:var(--dark);margin-bottom:10px;
        }
        .qr-section-label i { width:15px;height:15px;color:var(--muted); }
        .qr-section-label .qr-required { color:#dc2626; }

        .qr-tank-row {
            background:#fff;border:1px solid var(--border);border-radius:18px;
            margin-bottom:14px;overflow:hidden;
            box-shadow:0 1px 2px rgba(0,0,0,.03);
            transition:box-shadow .2s ease, border-color .2s ease;
        }
        .qr-tank-row:focus-within { border-color:var(--dark); box-shadow:0 4px 16px rgba(0,0,0,.08); }

        .qr-tank-row-header {
            display:flex;align-items:center;justify-content:space-between;gap:10px;
            padding:11px 16px;background:var(--cream-soft);border-bottom:1px solid var(--border);
        }
        .qr-tank-badge { display:flex;align-items:center;gap:9px; }
        .qr-tank-badge-num {
            width:24px;height:24px;border-radius:50%;flex-shrink:0;
            background:var(--dark);color:#fff;
            display:flex;align-items:center;justify-content:center;
            font-size:11px;font-weight:800;
        }
        .qr-tank-badge-label {
            font-size:11.5px;font-weight:800;color:var(--dark);
            text-transform:uppercase;letter-spacing:.06em;
        }
        .qr-tank-remove {
            background:none;border:none;cursor:pointer;color:var(--muted);
            display:flex;align-items:center;justify-content:center;
            width:28px;height:28px;border-radius:9px;flex-shrink:0;
            transition:background .15s ease, color .15s ease;
        }
        .qr-tank-remove:hover { background:#fee2e2;color:#dc2626; }
        .qr-tank-remove svg { width:15px;height:15px; }

        .qr-tank-row-body { padding:18px 20px; }
        .qr-tank-row-grid {
            display:grid;grid-template-columns:1.8fr 1.2fr 100px 1.2fr;gap:16px;align-items:end;
        }
        @media (max-width:900px) { .qr-tank-row-grid { grid-template-columns:1fr 1fr; } }
        @media (max-width:520px) { .qr-tank-row-grid { grid-template-columns:1fr; } }

        .qr-add-tank-btn {
            display:flex;align-items:center;justify-content:center;gap:8px;
            width:100%;padding:14px;border:1.5px dashed var(--border);border-radius:16px;
            background:var(--cream-soft);color:var(--dark);font-weight:800;font-size:13.5px;
            cursor:pointer;transition:background .2s ease, border-color .2s ease;
        }
        .qr-add-tank-btn:hover { background:var(--accent-soft);border-color:var(--dark); }
        .qr-add-tank-btn svg { width:16px;height:16px; }

        .qr-divider {
            height:1px;background:var(--border);margin:24px 0;
        }

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

        <div class="page-header">
            <div>
                <h1 class="page-title">Request a Quotation</h1>
                <p class="page-subtitle">Tell us about the tank(s) you need and our team will prepare a quotation for you.</p>
            </div>
        </div>

        @if($errors->any())
        <div class="alert-banner" style="background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;">
            <i data-lucide="circle-alert"></i>
            <div>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="pv-card">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
                <i data-lucide="clipboard-list" style="width:18px;height:18px;color:var(--accent);"></i>
                <h3 class="pv-card-title" style="margin-bottom:0;">Project Details</h3>
            </div>

            <form method="POST" action="{{ route('client.quotation.store') }}" id="quotationRequestForm">
                @csrf

                <div class="qr-section-label">
                    <i data-lucide="package"></i>
                    Tank Requirements <span class="qr-required">*</span>
                </div>
                <div id="tankItemsContainer"></div>
                <button type="button" class="qr-add-tank-btn" id="addTankItemBtn">
                    <i data-lucide="plus-circle"></i>
                    Add Another Tank
                </button>

                <div class="qr-divider"></div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Project / Delivery Location </label>
                        <textarea name="location" required rows="5"
                                  placeholder="Where should this project be delivered / installed?">{{ old('location', $client->address) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Additional Notes <span style="font-size:11px;color:var(--muted);font-weight:400;">(optional)</span></label>
                        <textarea name="notes" rows="5"
                                  placeholder="Any other details that would help us prepare your quotation...">{{ old('notes') }}</textarea>
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

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        const TANK_TYPES = @json($tankTypes);
        const OLD_TANK_ITEMS = @json(old('tank_items', []));

        function tankTypeOptions(selected) {
            return '<option value="" disabled' + (!selected ? ' selected' : '') + ' hidden>Select tank type</option>' +
                TANK_TYPES.map(function (t) {
                    return '<option value="' + t + '"' + (t === selected ? ' selected' : '') + '>' + t + '</option>';
                }).join('');
        }

        var tankIndex = 0;
        function addTankRow(item) {
            item = item || {};
            var container = document.getElementById('tankItemsContainer');
            var idx        = tankIndex++;
            var prefix     = 'tank_items[' + idx + ']';
            var row = document.createElement('div');
            row.className = 'qr-tank-row';
            row.innerHTML =
                '<div class="qr-tank-row-header">' +
                    '<div class="qr-tank-badge">' +
                        '<span class="qr-tank-badge-num"></span>' +
                        '<span class="qr-tank-badge-label"></span>' +
                    '</div>' +
                    '<button type="button" class="qr-tank-remove" onclick="removeTankRow(this)" title="Remove tank">' +
                        '<i data-lucide="trash-2"></i>' +
                    '</button>' +
                '</div>' +
                '<div class="qr-tank-row-body">' +
                    '<div class="qr-tank-row-grid">' +
                        '<div class="form-group" style="margin-bottom:0;">' +
                            '<label>Tank Type</label>' +
                            '<select name="' + prefix + '[tank_type]" required>' + tankTypeOptions(item.tank_type) + '</select>' +
                        '</div>' +
                        '<div class="form-group" style="margin-bottom:0;">' +
                            '<label>Capacity / Size</label>' +
                            '<input type="text" name="' + prefix + '[capacity]" placeholder="e.g. 10,000 liters" value="' + (item.capacity ? item.capacity.replace(/"/g, '&quot;') : '') + '">' +
                        '</div>' +
                        '<div class="form-group" style="margin-bottom:0;">' +
                            '<label>Qty</label>' +
                            '<input type="number" name="' + prefix + '[quantity]" min="1" value="' + (item.quantity || 1) + '">' +
                        '</div>' +
                        '<div class="form-group" style="margin-bottom:0;">' +
                            '<label>Target Timeline</label>' +
                            '<input type="text" name="' + prefix + '[target_timeline]" placeholder="e.g. Needed within 2 months" value="' + (item.target_timeline ? item.target_timeline.replace(/"/g, '&quot;') : '') + '">' +
                        '</div>' +
                    '</div>' +
                '</div>';
            container.appendChild(row);
            updateTankRowChrome();
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function removeTankRow(btn) {
            btn.closest('.qr-tank-row').remove();
            updateTankRowChrome();
        }

        function updateTankRowChrome() {
            var rows = document.querySelectorAll('#tankItemsContainer .qr-tank-row');
            rows.forEach(function (row, i) {
                row.querySelector('.qr-tank-badge-num').textContent   = i + 1;
                row.querySelector('.qr-tank-badge-label').textContent = 'Tank ' + (i + 1);
                var removeBtn = row.querySelector('.qr-tank-remove');
                removeBtn.style.display = rows.length > 1 ? '' : 'none';
            });
        }

        document.getElementById('addTankItemBtn').addEventListener('click', function () {
            addTankRow();
        });

        if (OLD_TANK_ITEMS && OLD_TANK_ITEMS.length) {
            OLD_TANK_ITEMS.forEach(function (item) { addTankRow(item); });
        } else {
            addTankRow();
        }

        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</body>
</html>
