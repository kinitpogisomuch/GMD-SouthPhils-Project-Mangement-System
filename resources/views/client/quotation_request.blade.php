<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request a Quotation | GMD South Phils</title>
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
        .qr-tank-row-grid {
            flex:1; display:flex; flex-wrap:wrap; gap:16px; align-items:end;
            padding:16px 40px 16px 18px;
        }
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
                <h1 class="page-title">Request a Quotation</h1>
                <p class="page-subtitle">Tell us about the tank(s) you need and our team will prepare a quotation for you.</p>
            </div>
        </div>

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
                    Tell us what tank(s) you need below, attach photos of a tank you already own further down — or both. At least one is required.
                </div>

                <div class="qr-section-label">
                    <i data-lucide="package"></i>
                    Tank Requirements <span style="font-size:11px;color:var(--muted);font-weight:400;text-transform:none;">(skip if you're only sending your own tank below)</span>
                </div>
                <div id="tankItemsContainer"></div>
                <div id="noTankItemsHint" style="display:none;font-size:12.5px;color:var(--muted);font-weight:600;padding:12px 4px;">
                    No tank requirements added — that's fine if you're attaching photos of your own tank below.
                </div>
                <button type="button" class="qr-add-tank-btn" id="addTankItemBtn">
                    <i data-lucide="plus-circle"></i>
                    Add Another Tank
                </button>

                <div class="qr-divider"></div>

                <div class="qr-section-label">
                    <i data-lucide="image-plus"></i>
                    Already Have Your Own Tank Design?
                </div>
                <label for="referenceFilesInput" class="qr-add-tank-btn" id="referenceFilesDropzone" style="flex-direction:column;gap:6px;padding:18px;cursor:pointer;">
                    <span style="display:flex;align-items:center;gap:8px;">
                        <i data-lucide="upload-cloud"></i>
                        Click to attach photos or your tank design file
                    </span>
                    <span style="font-size:11px;color:var(--muted);font-weight:600;">PDF, image, or AutoCAD (.dwg) — up to 5 files, max 10MB each</span>
                </label>
                <input type="file" name="reference_files[]" id="referenceFilesInput"
                       accept=".pdf,image/*,.dwg" multiple style="display:none;">
                <div id="referenceFilesList" class="qr-file-list" style="display:none;"></div>

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
                '<div class="qr-tank-badge">' +
                    '<span class="qr-tank-badge-num"></span>' +
                    '<span class="qr-tank-badge-label">Tank</span>' +
                '</div>' +
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
                '<button type="button" class="qr-tank-remove" onclick="removeTankRow(this)" title="Remove tank">' +
                    '<i data-lucide="x"></i>' +
                '</button>';
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
            // A client may skip tank specs entirely and rely on the "attach your own
            // tank" photos instead, so removing every row down to zero is allowed.
            rows.forEach(function (row, i) {
                row.querySelector('.qr-tank-badge-num').textContent = i + 1;
            });
            document.getElementById('noTankItemsHint').style.display = rows.length ? 'none' : '';
        }

        document.getElementById('addTankItemBtn').addEventListener('click', function () {
            addTankRow();
        });

        if (OLD_TANK_ITEMS && OLD_TANK_ITEMS.length) {
            OLD_TANK_ITEMS.forEach(function (item) { addTankRow(item); });
        } else {
            addTankRow();
        }
        updateTankRowChrome();

        // ── Reference files (existing tank photos/docs) ──
        // Native <input type=file> replaces its whole FileList on every pick, so we
        // keep our own running list, merge new picks into it, and write it back to
        // the input via DataTransfer before submit — that's what lets "+" add to the
        // existing selection instead of replacing it, and lets individual files be removed.
        (function () {
            var input    = document.getElementById('referenceFilesInput');
            var dropzone = document.getElementById('referenceFilesDropzone');
            var list     = document.getElementById('referenceFilesList');
            if (!input) return;

            var MAX_FILES = 5;
            var selected  = [];

            function formatSize(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
                return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
            }

            function syncInput() {
                var dt = new DataTransfer();
                selected.forEach(function (f) { dt.items.add(f); });
                input.files = dt.files;
            }

            function openPicker() {
                input.value = '';
                input.click();
            }

            function removeFile(index) {
                selected.splice(index, 1);
                syncInput();
                render();
            }

            function viewFile(index) {
                var f = selected[index];
                if (!f) return;
                window.open(URL.createObjectURL(f), '_blank');
            }

            function render() {
                dropzone.style.display = selected.length ? 'none' : '';
                list.style.display     = selected.length ? 'flex' : 'none';

                list.innerHTML = selected.map(function (f, i) {
                    var isImage = f.type.indexOf('image/') === 0;
                    var thumb   = isImage
                        ? '<img class="qr-file-thumb" src="' + URL.createObjectURL(f) + '" alt="">'
                        : '<span class="qr-file-thumb"><i data-lucide="file-text"></i></span>';
                    return '<div class="qr-file-chip" data-index="' + i + '" title="Click to view">'
                        + thumb
                        + '<div class="qr-file-meta">'
                            + '<div class="qr-file-name" title="' + f.name.replace(/"/g, '&quot;') + '">' + f.name + '</div>'
                            + '<div class="qr-file-size">' + formatSize(f.size) + '</div>'
                        + '</div>'
                        + '<button type="button" class="qr-file-remove" data-index="' + i + '" title="Remove">'
                            + '<i data-lucide="x"></i>'
                        + '</button>'
                    + '</div>';
                }).join('');

                if (selected.length && selected.length < MAX_FILES) {
                    list.innerHTML += '<button type="button" class="qr-file-add" id="referenceFilesAddBtn" title="Add more">'
                        + '<i data-lucide="plus"></i></button>';
                }

                list.querySelectorAll('.qr-file-chip').forEach(function (chip) {
                    chip.addEventListener('click', function (e) {
                        if (e.target.closest('.qr-file-remove')) return;
                        viewFile(Number(chip.dataset.index));
                    });
                });
                list.querySelectorAll('.qr-file-remove').forEach(function (btn) {
                    btn.addEventListener('click', function () { removeFile(Number(btn.dataset.index)); });
                });
                var addBtn = document.getElementById('referenceFilesAddBtn');
                if (addBtn) addBtn.addEventListener('click', openPicker);

                if (typeof lucide !== 'undefined') lucide.createIcons();
            }

            input.addEventListener('change', function () {
                var rejected = [];
                Array.from(input.files || []).forEach(function (f) {
                    if (f.size > 10 * 1024 * 1024) { rejected.push(f.name); return; }
                    var isDuplicate = selected.some(function (sf) {
                        return sf.name === f.name && sf.size === f.size && sf.lastModified === f.lastModified;
                    });
                    if (!isDuplicate && selected.length < MAX_FILES) selected.push(f);
                });
                syncInput();
                render();
                if (rejected.length) showFileTooLargeModal(rejected.join(', '), 10);
            });
        })();

        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</body>
</html>
