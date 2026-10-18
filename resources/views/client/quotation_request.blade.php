<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
    <style>
        #quotationHeader { max-width:1060px; margin-left:auto; margin-right:auto; text-align:center; justify-content:center; }

        /* ── Step 1: tank cards (same idea as "Our Work" on the landing page) ── */
        .qr-step { max-width:1060px; margin:0 auto; }
        .qr-step-head { display:flex; align-items:center; gap:10px; margin-bottom:14px; }
        .qr-step-num { width:26px; height:26px; border-radius:50%; background:var(--dark); color:#fff; font-size:12px; font-weight:900; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .qr-step-title { font-size:15px; font-weight:900; color:var(--dark); }
        .qr-step-sub { font-size:12.5px; color:var(--muted); }
        .qt-cards { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:14px; }
        .qt-card {
            display:flex; flex-direction:column; text-align:left; cursor:pointer; padding:0; overflow:hidden;
            background:#fff; border:1.5px solid var(--border); border-radius:18px; font-family:inherit; color:inherit;
            transition:border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }
        .qt-card:hover { border-color:var(--dark); box-shadow:0 8px 24px rgba(0,0,0,.08); transform:translateY(-2px); }
        .qt-card:focus-visible { outline:3px solid var(--dark); outline-offset:2px; }
        .qt-card.is-selected { border-color:var(--dark); box-shadow:0 0 0 3px rgba(0,0,0,.12); }
        /* every card the same size: fixed picture height, 2-line title and description */
        .qt-cards { grid-auto-rows:1fr; }
        .qt-card { height:100%; }
        .qt-media { flex:0 0 auto; height:150px; overflow:hidden; background:linear-gradient(135deg, #4a4a4a 0%, #232323 100%); background-size:cover; background-position:center; display:flex; align-items:center; justify-content:center; position:relative; }
        .qt-media img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:block; }
        .qt-media-icon { width:54px; height:54px; border-radius:14px; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; }
        .qt-media-icon i, .qt-media-icon svg { width:26px; height:26px; color:rgba(255,255,255,.8); }
        .qt-check { position:absolute; z-index:1; top:10px; right:10px; width:26px; height:26px; border-radius:50%; background:#fff; color:var(--dark); display:none; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(0,0,0,.2); }
        .qt-check i, .qt-check svg { width:16px; height:16px; }
        .qt-card.is-selected .qt-check { display:flex; }
        .qt-body { flex:1; padding:12px 14px 14px; display:flex; flex-direction:column; gap:4px; }
        .qt-name { font-size:13.5px; font-weight:900; color:var(--dark); line-height:1.3; min-height:calc(2 * 1.3em);
                   display:-webkit-box; -webkit-line-clamp:2; line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .qt-blurb { font-size:12px; color:var(--muted); line-height:1.5; min-height:calc(2 * 1.5em);
                    display:-webkit-box; -webkit-line-clamp:2; line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }

        /* ── Step 2: the selected tank's form ── */
        #quotationCard { max-width:1060px; margin:22px auto 0; }
        .qr-chosen { display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:14px; background:var(--cream-soft); border:1px solid var(--border); margin-bottom:18px; }
        .qr-chosen-icon { width:40px; height:40px; border-radius:12px; background:var(--dark); color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .qr-chosen-icon i, .qr-chosen-icon svg { width:20px; height:20px; }
        .qr-chosen-text { flex:1; min-width:0; }
        .qr-chosen-text strong { display:block; font-size:14px; color:var(--dark); }
        .qr-chosen-text span { font-size:12px; color:var(--muted); }
        .qr-change { background:none; border:1.5px solid var(--border); border-radius:999px; padding:6px 14px; font-size:12px; font-weight:800; cursor:pointer; color:var(--dark); font-family:inherit; }
        .qr-change:hover { border-color:var(--dark); }

        .qr-section-label { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:800; color:var(--dark); margin:6px 0 12px; }
        .qr-section-label i { width:15px; height:15px; color:var(--muted); }
        .qr-fields { display:grid; grid-template-columns:repeat(auto-fill, minmax(230px, 1fr)); gap:14px 16px; }
        .qr-fields .form-group { margin-bottom:0; }
        .qr-fields .qr-wide { grid-column:1 / -1; }
        .field-error { margin-top:6px; font-size:12px; font-weight:700; color:#b91c1c; }
        .qr-hint { font-size:11.5px; color:var(--muted); margin-top:5px; line-height:1.45; }
        .qr-divider { height:1px; background:var(--border); margin:22px 0; }

        .qr-design-btn {
            display:flex; align-items:center; gap:14px; width:100%; text-align:left; cursor:pointer;
            background:var(--white); border:1.5px dashed #b9b9b9; border-radius:14px; padding:12px 16px; font-family:inherit;
            transition:background .15s ease, border-color .15s ease;
        }
        .qr-design-btn:hover { background:var(--cream-soft); border-color:var(--dark); }
        .qr-design-icon { flex-shrink:0; width:40px; height:40px; border-radius:50%; background:var(--dark); color:#fff; display:flex; align-items:center; justify-content:center; }
        .qr-design-icon i, .qr-design-icon svg { width:20px; height:20px; }
        .qr-design-text { flex:1; min-width:0; display:flex; flex-direction:column; gap:2px; }
        .qr-design-text strong { font-size:13.5px; font-weight:900; color:var(--dark); }
        .qr-design-text em { font-style:normal; font-size:11.5px; font-weight:600; color:var(--muted); line-height:1.4; }
        .qr-file-list { display:flex; flex-wrap:wrap; gap:10px; margin-top:10px; }
        .qr-file-chip { display:flex; align-items:center; gap:8px; cursor:pointer; background:#fff; border:1px solid var(--border); border-radius:12px; padding:6px 8px 6px 6px; max-width:220px; }
        .qr-file-chip:hover { border-color:var(--dark); }
        .qr-file-thumb { width:34px; height:34px; border-radius:8px; flex-shrink:0; background:var(--cream-soft); object-fit:cover; display:flex; align-items:center; justify-content:center; }
        .qr-file-thumb i { width:16px; height:16px; color:var(--muted); }
        .qr-file-meta { min-width:0; flex:1; }
        .qr-file-name { font-size:12px; font-weight:700; color:var(--dark); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .qr-file-size { font-size:10.5px; color:var(--muted); font-weight:600; }
        .qr-file-remove { background:none; border:none; cursor:pointer; color:var(--muted); display:flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:7px; }
        .qr-file-remove:hover { background:#fee2e2; color:#dc2626; }
        .qr-file-remove svg { width:13px; height:13px; }
        .qr-file-add { width:48px; height:48px; border-radius:12px; border:1.5px dashed var(--border); background:var(--cream-soft); display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--dark); }
        .qr-file-add svg { width:18px; height:18px; }

        .form-group textarea { resize:none; }
        .qr-submit-row { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-top:24px; flex-wrap:wrap; }
        .qr-submit-hint { font-size:12.5px; color:var(--muted); }

        @media (max-width:640px) {
            .qt-cards { grid-template-columns:1fr 1fr; gap:10px; }
            .qt-media { height:110px; }
            .qt-blurb { display:none; }
            .qt-body { padding:10px 11px 12px; }
            .qt-name { font-size:12.5px; }
            .qr-fields { grid-template-columns:1fr; }
            .qr-submit-row { flex-direction:column; justify-content:center; text-align:center; }
            .qr-submit-row .save-btn { align-self:stretch; justify-content:center; }
            .qr-chosen { flex-wrap:wrap; }
        }
    </style>
</head>
<body class="page-enter">

    @include('partials.client.header')

    <main class="admin-content">

        <div class="page-header" id="quotationHeader">
            <div>
                <h1 class="page-title">Quotation</h1>
                <p class="page-subtitle">Request a quotation, review the quotations GMD South Phils prepares for you, and look back at past requests.</p>
            </div>
        </div>

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
            // Opens on the form when a tank was picked (landing page / link) or the form had errors;
            // otherwise on Pending when something is under review; ?tab= picks one directly
            $activeTab = in_array(request('tab'), ['new-request', 'pending', 'history'], true)
                ? request('tab')
                : (($selectedTank || $errors->any()) ? 'new-request' : ($hasPending ? 'pending' : 'new-request'));
            $batchStatus = fn ($batch) => $batch->first()->display_status;
            $needsDecision = $pendingBatches->filter(fn ($b) => $b->first()->status === 'quotation_sent')->count();
            $oldItem = old('tank_items.0', []);
            $initialSlug = $oldItem['tank_slug'] ?? $selectedTank;
            // server-side errors for the type-specific fields, keyed by field (shown under each field)
            $specErrors = [];
            foreach ($errors->getMessages() as $errKey => $errMessages) {
                if (str_starts_with($errKey, 'tank_items.0.specs.')) {
                    $specErrors[substr($errKey, strlen('tank_items.0.specs.'))] = $errMessages[0];
                }
            }
        @endphp

        {{-- Alpine.js: which tab is open and which "My Quotations" filter is applied. The server renders the
             starting tab, so the page is correct even before Alpine loads. --}}
        <div x-data="{ tab: @js($activeTab), filter: 'all' }">
        <div style="max-width:820px;margin:0 auto 24px;display:flex;justify-content:center;">
            <div class="emp-tabs" role="tablist">
                <button type="button" role="tab" class="emp-tab {{ $activeTab === 'new-request' ? 'active' : '' }}" data-tab="new-request"
                        @click="tab = 'new-request'" :class="{ active: tab === 'new-request' }" :aria-selected="(tab === 'new-request').toString()">
                    <i data-lucide="clipboard-list"></i>
                    New Request
                </button>
                <button type="button" role="tab" class="emp-tab {{ $activeTab === 'pending' ? 'active' : '' }}" data-tab="pending"
                        @click="tab = 'pending'" :class="{ active: tab === 'pending' }" :aria-selected="(tab === 'pending').toString()">
                    <i data-lucide="clock"></i>
                    My Quotations
                    @if($needsDecision)
                    <span class="filter-count" style="background:#dc2626;color:#fff;" title="Waiting for your decision">{{ $needsDecision }}</span>
                    @elseif($hasPending)
                    <span class="filter-count">{{ $pendingBatches->count() }}</span>
                    @endif
                </button>
                <button type="button" role="tab" class="emp-tab {{ $activeTab === 'history' ? 'active' : '' }}" data-tab="history"
                        @click="tab = 'history'" :class="{ active: tab === 'history' }" :aria-selected="(tab === 'history').toString()">
                    <i data-lucide="history"></i>
                    History
                </button>
            </div>
        </div>

        {{-- ===================== MY QUOTATIONS (open requests) ===================== --}}
        <div class="emp-tab-content {{ $activeTab === 'pending' ? 'active' : '' }}" id="tab-pending" role="tabpanel" :class="{ active: tab === 'pending' }" style="max-width:820px;margin:0 auto;">
            @if($hasPending)
                @if($pendingBatches->count() > 1)
                @php
                    $filters = [
                        'all'            => ['All', $pendingBatches->count()],
                        'review'         => ['Under Review', $pendingBatches->filter(fn ($b) => in_array($batchStatus($b), ['pending', 'in_preparation', 'revision_requested']))->count()],
                        'quotation_sent' => ['Ready for Your Review', $needsDecision],
                        'approved'       => ['Approved', $pendingBatches->filter(fn ($b) => $batchStatus($b) === 'approved')->count()],
                    ];
                @endphp
                <div class="filter-tabs" style="margin-bottom:20px;width:fit-content;max-width:100%;overflow-x:auto;">
                    @foreach($filters as $key => [$label, $count])
                    <button type="button" class="filter-tab {{ $key === 'all' ? 'active' : '' }}" data-filter="{{ $key }}"
                            @click="filter = @js($key)" :class="{ active: filter === @js($key) }">
                        {{ $label }} <span class="filter-count">{{ $count }}</span>
                    </button>
                    @endforeach
                </div>
                @endif

                <div style="display:flex;flex-direction:column;gap:20px;">
                    @foreach($pendingBatches as $batch)
                    @php $st = $batchStatus($batch); @endphp
                    @php $groupKey = in_array($st, ['pending', 'in_preparation', 'revision_requested']) ? 'review' : $st; @endphp
                    <div data-quotation-batch data-status="{{ $groupKey }}"
                         x-show="filter === 'all' || filter === @js($groupKey)" x-transition.opacity.duration.250ms>
                        @include('partials.client.quotation_batch_card', ['batch' => $batch])
                    </div>
                    @endforeach
                </div>
            @else
                <div class="pv-card" style="text-align:center;padding:48px 20px;">
                    @include('partials.empty_tank_art')
                    <p style="font-weight:800;color:var(--dark);margin-bottom:6px;">No open quotation requests.</p>
                    <p style="font-size:13px;color:var(--muted);">Choose a tank under "New Request" — your request and GMD's quotation will show up here.</p>
                </div>
            @endif
        </div>

        {{-- ===================== HISTORY ===================== --}}
        <div class="emp-tab-content {{ $activeTab === 'history' ? 'active' : '' }}" id="tab-history" role="tabpanel" :class="{ active: tab === 'history' }" style="max-width:820px;margin:0 auto;">
            <div style="display:flex;flex-direction:column;gap:24px;">
            @forelse($historyBatches as $batch)
                @include('partials.client.quotation_batch_card', ['batch' => $batch])
            @empty
                <div class="pv-card" style="text-align:center;padding:48px 20px;">
                    @include('partials.empty_tank_art')
                    <p style="font-weight:800;color:var(--dark);margin-bottom:6px;">No completed quotations yet.</p>
                    <p style="font-size:13px;color:var(--muted);">Accepted, rejected or declined requests will show up here.</p>
                </div>
            @endforelse
            </div>
        </div>

        {{-- ===================== NEW REQUEST ===================== --}}
        <div class="emp-tab-content {{ $activeTab === 'new-request' ? 'active' : '' }}" id="tab-new-request" role="tabpanel" :class="{ active: tab === 'new-request' }">

            <div class="qr-step">
                <div class="qr-step-head">
                    <span class="qr-step-num">1</span>
                    <div>
                        <div class="qr-step-title">Choose the tank you need</div>
                        <div class="qr-step-sub">Each tank type asks only for the details that apply to it.</div>
                    </div>
                </div>
                <div class="qt-cards" role="list">
                    @foreach($tankCards as $card)
                    <button type="button" class="qt-card {{ $initialSlug === $card['slug'] ? 'is-selected' : '' }}" role="listitem"
                            data-slug="{{ $card['slug'] }}" aria-pressed="{{ $initialSlug === $card['slug'] ? 'true' : 'false' }}"
                            onclick="chooseTank('{{ $card['slug'] }}', true)">
                        <div class="qt-media" @if($card['image']) style="background-image:url('{{ $card['image'] }}')" @endif>
                            @if($card['image'])
                                <img src="{{ $card['image'] }}" alt="" loading="lazy">
                            @else
                                <div class="qt-media-icon"><i data-lucide="{{ $card['icon'] }}"></i></div>
                            @endif
                            <span class="qt-check"><i data-lucide="check"></i></span>
                        </div>
                        <div class="qt-body">
                            <span class="qt-name">{{ $card['type'] }}</span>
                            <span class="qt-blurb">{{ $card['blurb'] }}</span>
                        </div>
                    </button>
                    @endforeach
                </div>
            </div>

            <div class="pv-card" id="quotationCard" @if(!$initialSlug) hidden @endif>
                <div class="qr-step-head">
                    <span class="qr-step-num">2</span>
                    <div>
                        <div class="qr-step-title">Tell us your requirements</div>
                        <div class="qr-step-sub">Fields marked <span style="color:#dc2626;">*</span> are required.</div>
                    </div>
                </div>

                @if($errors->any())
                <div class="alert-banner" style="background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;" role="alert">
                    <i data-lucide="circle-alert"></i>
                    <div>
                        <strong>Please check the form:</strong>
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
                @endif

                <form method="POST" action="{{ route('client.quotation.store') }}" id="quotationRequestForm" enctype="multipart/form-data" novalidate>
                    @csrf
                    <input type="hidden" name="tank_items[0][tank_slug]" id="tankSlugInput" value="{{ $initialSlug }}">

                    <div class="qr-chosen">
                        <span class="qr-chosen-icon" id="chosenIcon"><i data-lucide="package"></i></span>
                        <div class="qr-chosen-text">
                            <strong id="chosenName">—</strong>
                            <span id="chosenBlurb"></span>
                        </div>
                        <button type="button" class="qr-change" onclick="document.querySelector('.qt-cards').scrollIntoView({behavior:'smooth', block:'start'})">Change</button>
                    </div>

                    <div class="qr-section-label"><i data-lucide="ruler"></i> Specifications</div>
                    <div class="qr-fields">
                        <div class="form-group">
                            <label for="capacityInput"><span id="capacityLabel">Capacity / Size</span> <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="tank_items[0][capacity]" id="capacityInput" required maxlength="255" autocomplete="off"
                                   value="{{ $oldItem['capacity'] ?? '' }}" oninput="formatCapacity(this)">
                            @error('tank_items.0.capacity')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label for="quantityInput">Quantity <span style="color:#dc2626;">*</span></label>
                            <input type="number" name="tank_items[0][quantity]" id="quantityInput" required min="1" max="1000" value="{{ $oldItem['quantity'] ?? 1 }}">
                            <div class="qr-hint" id="quantityHint">How many identical units you need.</div>
                            @error('tank_items.0.quantity')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        {{-- type-specific fields are rendered here --}}
                        <div class="qr-wide" id="typeFields" style="display:contents;"></div>
                        <div class="form-group">
                            <label for="targetDateInput">Target Date</label>
                            <input type="date" name="tank_items[0][target_timeline]" id="targetDateInput" min="{{ now()->toDateString() }}" value="{{ $oldItem['target_timeline'] ?? '' }}">
                            @error('tank_items.0.target_timeline')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="qr-divider"></div>

                    <div class="qr-section-label"><i data-lucide="paperclip"></i> Design or reference files <span style="font-size:11px;color:var(--muted);font-weight:500;">(optional)</span></div>
                    <button type="button" class="qr-design-btn" id="designBtn">
                        <span class="qr-design-icon"><i data-lucide="upload-cloud"></i></span>
                        <span class="qr-design-text"><strong>Attach your design or photos</strong><em>PDF, image or AutoCAD (.dwg) · up to 5 files, 10MB each</em></span>
                    </button>
                    <input type="file" id="designInput" name="tank_items[0][design_files][]" accept=".pdf,image/*,.dwg" multiple style="display:none;">
                    <div class="qr-file-list" id="designList" style="display:none;"></div>
                    <div class="field-error" id="designError" style="display:none;"></div>

                    <div class="form-group" style="margin-top:18px;">
                        <label for="notesInput">Additional Notes <span style="font-size:11px;color:var(--muted);font-weight:400;">(optional)</span></label>
                        <textarea name="notes" id="notesInput" rows="3" maxlength="2000" placeholder="Anything else that helps us prepare your quotation...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="qr-submit-row">
                        <span class="qr-submit-hint">
                            <i data-lucide="info" style="width:12px;height:12px;vertical-align:-1px;"></i>
                            You'll get a reference number now, and your quotation will appear under "My Quotations".
                        </span>
                        <button type="submit" class="save-btn" id="submitRequestBtn">
                            <i data-lucide="send"></i>
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </div>{{-- /x-data tabs --}}

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        function openModal(id) { var m = document.getElementById(id); if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; } }
        function closeModal(id) { var m = document.getElementById(id); if (m) { m.classList.remove('show'); document.body.style.overflow = ''; } }
        document.querySelectorAll('.modal-overlay').forEach(function (modal) {
            modal.addEventListener('click', function (e) { if (e.target === this) closeModal(this.id); });
        });

        var TANKS       = @json(collect($tankCards)->keyBy('slug'));
        var OLD_SPECS   = @json((object) ($oldItem['specs'] ?? []));
        var SPEC_ERRORS = @json((object) $specErrors);

        function esc(s) {
            return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // Thousands separators for the number at the start of Capacity while typing (10000 → 10,000)
        function formatCapacity(input) {
            var v = input.value;
            var m = v.match(/^(\d[\d,]*)(\.\d*)?/);
            if (!m) return;
            var caret  = input.selectionStart;
            var digits = m[1].replace(/,/g, '').replace(/^0+(?=\d)/, '');
            var next   = digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + (m[2] || '') + v.slice(m[0].length);
            if (next === v) return;
            var digitsBeforeCaret = v.slice(0, caret).replace(/,/g, '').length;
            input.value = next;
            var pos = 0, seen = 0;
            while (pos < next.length && seen < digitsBeforeCaret) { if (next[pos] !== ',') seen++; pos++; }
            if (input.setSelectionRange && document.activeElement === input) input.setSelectionRange(pos, pos);
        }

        // ── the selected tank's own fields ──
        function fieldHtml(f, value) {
            var name = 'tank_items[0][specs][' + f.key + ']';
            var id   = 'spec_' + f.key;
            var req  = f.required ? ' <span style="color:#dc2626;">*</span>' : '';
            var attr = (f.required ? ' required' : '') + ' id="' + id + '" name="' + name + '" data-spec="' + esc(f.key) + '"';
            var input;
            if (f.type === 'select') {
                input = '<select' + attr + '><option value="">' + (f.required ? 'Select…' : 'No preference') + '</option>' +
                    f.options.map(function (o) { return '<option value="' + esc(o) + '"' + (o === value ? ' selected' : '') + '>' + esc(o) + '</option>'; }).join('') +
                    '</select>';
            } else if (f.type === 'textarea') {
                input = '<textarea rows="3" maxlength="2000"' + attr + ' placeholder="' + esc(f.placeholder || '') + '">' + esc(value || '') + '</textarea>';
            } else if (f.type === 'number') {
                input = '<input type="number"' + attr + (f.min != null ? ' min="' + f.min + '"' : '') + (f.max != null ? ' max="' + f.max + '"' : '') +
                    ' value="' + esc(value != null && value !== '' ? value : (f['default'] != null ? f['default'] : '')) + '">';
            } else {
                input = '<input type="text" maxlength="255"' + attr + ' placeholder="' + esc(f.placeholder || '') + '" value="' + esc(value || '') + '">';
            }
            var wide = f.type === 'textarea' || (f.placeholder && f.placeholder.length > 40) ? ' qr-wide' : '';
            var err  = SPEC_ERRORS[f.key] ? '<div class="field-error">' + esc(SPEC_ERRORS[f.key]) + '</div>' : '';
            var cond = f.show_if ? ' data-show-if="' + esc(f.show_if[0]) + '" data-show-value="' + esc(f.show_if[1]) + '"' : '';
            return '<div class="form-group' + wide + '"' + cond + '><label for="' + id + '">' + esc(f.label) + req + '</label>' + input + err + '</div>';
        }

        // fields shown only for a particular answer (e.g. tower height for an elevated tank)
        function applyConditions() {
            document.querySelectorAll('#typeFields [data-show-if]').forEach(function (group) {
                var source = document.querySelector('#typeFields [data-spec="' + group.dataset.showIf + '"]');
                var on = source && source.value === group.dataset.showValue;
                group.style.display = on ? '' : 'none';
                group.querySelectorAll('input, select, textarea').forEach(function (el) { el.disabled = !on; });
            });
        }

        function chooseTank(slug, scroll) {
            var t = TANKS[slug];
            if (!t) return;
            document.getElementById('tankSlugInput').value = slug;
            document.querySelectorAll('.qt-card').forEach(function (c) {
                var on = c.dataset.slug === slug;
                c.classList.toggle('is-selected', on);
                c.setAttribute('aria-pressed', on ? 'true' : 'false');
            });

            document.getElementById('chosenName').textContent  = t.type;
            document.getElementById('chosenBlurb').textContent = t.blurb;
            document.getElementById('chosenIcon').innerHTML    = '<i data-lucide="' + esc(t.icon) + '"></i>';
            document.getElementById('capacityLabel').textContent = t.capacity.label;
            var cap = document.getElementById('capacityInput');
            cap.placeholder = t.capacity.placeholder;
            document.getElementById('quantityHint').textContent = t.unit === 'unit'
                ? 'How many identical units you need.'
                : 'Number of ' + t.unit + 's (usually 1).';

            // keep what was typed when switching back and forth between types with the same field
            var kept = {};
            document.querySelectorAll('#typeFields [data-spec]').forEach(function (el) { kept[el.dataset.spec] = el.value; });
            var values = Object.assign({}, kept, OLD_SPECS);
            document.getElementById('typeFields').innerHTML = t.fields.map(function (f) { return fieldHtml(f, values[f.key]); }).join('');
            OLD_SPECS = {};
            document.querySelectorAll('#typeFields [data-spec]').forEach(function (el) { el.addEventListener('change', applyConditions); });
            applyConditions();

            var card = document.getElementById('quotationCard');
            card.hidden = false;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            if (scroll) {
                card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                setTimeout(function () { cap.focus({ preventScroll: true }); }, 350);
            }
        }


        // ── design files: a native file input replaces its list on every pick, so keep a running list ──
        (function () {
            var MAX_FILES = 5, MAX_BYTES = 10 * 1024 * 1024;
            var input = document.getElementById('designInput');
            var btn   = document.getElementById('designBtn');
            var list  = document.getElementById('designList');
            var error = document.getElementById('designError');
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
                    var thumb = /^image\//.test(f.type)
                        ? '<img class="qr-file-thumb" src="' + URL.createObjectURL(f) + '" alt="">'
                        : '<span class="qr-file-thumb"><i data-lucide="file-text"></i></span>';
                    chip.innerHTML = thumb + '<div class="qr-file-meta"><div class="qr-file-name" title="' + esc(f.name) + '">' + esc(f.name) + '</div>' +
                        '<div class="qr-file-size">' + fmt(f.size) + '</div></div>' +
                        '<button type="button" class="qr-file-remove" title="Remove" aria-label="Remove ' + esc(f.name) + '"><i data-lucide="x"></i></button>';
                    chip.querySelector('.qr-file-remove').addEventListener('click', function (e) { e.stopPropagation(); files.splice(i, 1); sync(); say(''); render(); });
                    list.appendChild(chip);
                });
                btn.style.display = files.length ? 'none' : '';
                if (files.length && files.length < MAX_FILES) {
                    var add = document.createElement('button');
                    add.type = 'button'; add.className = 'qr-file-add'; add.title = 'Add another file';
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
                    if (files.some(function (x) { return x.name === f.name && x.size === f.size; })) return;
                    if (!/\.(pdf|jpe?g|png|dwg)$/i.test(f.name)) { problems.push(f.name + ' is not a PDF, image or .dwg file.'); return; }
                    if (f.size > MAX_BYTES)                     { problems.push(f.name + ' is larger than 10MB.'); return; }
                    if (files.length >= MAX_FILES)              { problems.push('Only ' + MAX_FILES + ' files are allowed.'); return; }
                    files.push(f);
                });
                sync(); say(problems.filter(function (p, i, a) { return a.indexOf(p) === i; }).join(' ')); render();
            });
        })();

        // ── check required fields in the browser first (the server checks again) ──
        document.getElementById('quotationRequestForm').addEventListener('submit', function (e) {
            var form = this, firstBad = null;
            form.querySelectorAll('.field-error.js').forEach(function (el) { el.remove(); });
            form.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (el) {
                if (el.disabled || el.offsetParent === null) return;
                if (!el.checkValidity()) {
                    var msg = document.createElement('div');
                    msg.className = 'field-error js';
                    msg.textContent = el.validity.valueMissing ? 'This field is required.' : el.validationMessage;
                    el.insertAdjacentElement('afterend', msg);
                    firstBad = firstBad || el;
                }
            });
            if (firstBad) {
                e.preventDefault();
                firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (firstBad.focus) firstBad.focus({ preventScroll: true });
            }
        });

        @if($initialSlug)
        chooseTank(@json($initialSlug), {{ $selectedTank && !$errors->any() ? 'true' : 'false' }});
        @endif
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
    @include('partials.receipt_viewer')

    @if(session('success'))
    <div class="toast" id="clientQuotationToast" role="status">
        <i data-lucide="check-circle"></i>
        <span>{{ session('success') }}</span>
    </div>
    <script>
        window.addEventListener('load', function () {
            var t = document.getElementById('clientQuotationToast');
            if (!t) return;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            var ms = t.textContent.trim().length > 80 ? 6000 : 3500;
            requestAnimationFrame(function () { requestAnimationFrame(function () { t.classList.add('show'); }); });
            setTimeout(function () { t.classList.remove('show'); }, ms);
        });
    </script>
    @endif
</body>
</html>
