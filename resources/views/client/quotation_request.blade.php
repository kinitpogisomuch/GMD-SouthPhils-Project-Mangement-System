<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}?v={{ @filemtime(public_path('css/client.css')) }}" rel="stylesheet">
</head>
<body class="page-enter pg-client-quotation-request">

    @include('partials.client.header')

    <main class="admin-content">

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
            // approved quotations still waiting for the client's Purchase Order also need the client
            $needsPo = $pendingBatches->filter(fn ($b) => $batchStatus($b) === 'awaiting_po')->count();
            // The tanks to open with: what a failed submit sent back, else the one picked on the landing page
            $initialItems = collect(old('tank_items', []))
                ->filter(fn ($item) => !empty($item['tank_slug']))
                ->map(fn ($item, $key) => ['key' => (int) $key, 'slug' => $item['tank_slug'], 'item' => $item])
                ->values();
            if ($initialItems->isEmpty() && $selectedTank) {
                $initialItems = collect([['key' => 0, 'slug' => $selectedTank, 'item' => ['capacity' => $selectedCapacity, 'specs' => $selectedSpecs ?? []]]]);
            }
            // server-side errors, by tank and field (shown under each field)
            $itemErrors = [];
            foreach ($errors->getMessages() as $errKey => $errMessages) {
                if (preg_match('/^tank_items\.(\d+)\.(?:specs\.(.+)|(\w+))/', $errKey, $m)) {
                    if (!empty($m[2])) { $itemErrors[$m[1]]['specs'][$m[2]] = $errMessages[0]; }
                    else { $itemErrors[$m[1]][$m[3]] = $errMessages[0]; }
                }
            }
            // what PHP accepts in one request: past these, files are dropped or the whole form is rejected
            $postLimit = (function () {
                $raw  = trim((string) ini_get('post_max_size'));
                $size = (float) $raw;
                return (int) ($size * (['G' => 1073741824, 'M' => 1048576, 'K' => 1024][strtoupper(substr($raw, -1))] ?? 1));
            })();
            $fileLimit = (int) ini_get('max_file_uploads');
        @endphp

        {{-- Alpine.js: which tab is open and which "My Quotations" filter is applied. The server renders the
             starting tab, so the page is correct even before Alpine loads. --}}
        <div x-data="{ tab: @js($activeTab), filter: 'all' }">
        <div class="page-header cd-page-head" id="quotationHeader">
            <div>
                <h1 class="page-title">Quotation</h1>
                <p class="page-subtitle">Request a quotation, review the quotations GMD South Phils prepares for you, and look back at past requests.</p>
            </div>
            <div class="emp-tabs qr-head-tabs" role="tablist">
                <button type="button" role="tab" class="emp-tab {{ $activeTab === 'new-request' ? 'active' : '' }}" data-tab="new-request"
                        @click="tab = 'new-request'" :class="{ active: tab === 'new-request' }" :aria-selected="(tab === 'new-request').toString()">
                    <i data-lucide="clipboard-list"></i>
                    New Request
                </button>
                <button type="button" role="tab" class="emp-tab {{ $activeTab === 'pending' ? 'active' : '' }}" data-tab="pending"
                        @click="tab = 'pending'" :class="{ active: tab === 'pending' }" :aria-selected="(tab === 'pending').toString()">
                    <i data-lucide="clock"></i>
                    My Quotations
                    @if($needsDecision + $needsPo)
                    <span class="filter-count" style="background:#dc2626;color:#fff;" title="Waiting for you">{{ $needsDecision + $needsPo }}</span>
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

        @if(session('error'))
        <div class="alert-banner" style="background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;margin:0 0 18px;">
            <i data-lucide="circle-alert"></i>
            {{ session('error') }}
        </div>
        @endif

        {{-- ===================== MY QUOTATIONS (open requests) ===================== --}}
        <div class="emp-tab-content {{ $activeTab === 'pending' ? 'active' : '' }}" id="tab-pending" role="tabpanel" :class="{ active: tab === 'pending' }">
            @if($hasPending)
                @if($pendingBatches->count() > 1)
                @php
                    $filters = [
                        'all'            => ['All', $pendingBatches->count()],
                        'review'         => ['Under Review', $pendingBatches->filter(fn ($b) => in_array($batchStatus($b), ['pending', 'in_preparation', 'revision_requested']))->count()],
                        'quotation_sent' => ['Ready for Your Review', $needsDecision],
                        'approved'       => ['Approved', $pendingBatches->filter(fn ($b) => $b->first()->status === 'approved')->count()],
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
                    @php $groupKey = in_array($st, ['pending', 'in_preparation', 'revision_requested']) ? 'review' : (in_array($st, ['awaiting_po', 'po_received']) ? 'approved' : $st); @endphp
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
        <div class="emp-tab-content {{ $activeTab === 'history' ? 'active' : '' }}" id="tab-history" role="tabpanel" :class="{ active: tab === 'history' }"
             x-data="{ q: '', shown: {{ $historyBatches->count() }},
                       matches(text) { return !this.q.trim() || text.indexOf(this.q.trim().toLowerCase()) !== -1; },
                       recount() { this.$nextTick(() => { this.shown = [...this.$el.querySelectorAll('[data-history-item]')].filter(el => el.style.display !== 'none').length; }); } }"
             x-init="$watch('q', () => recount())">
            @if($historyBatches->isNotEmpty())
            {{-- search the history: reference / quotation no., tank, capacity, status or date --}}
            <div class="qh-search">
                <i data-lucide="search"></i>
                <input type="search" x-model="q" placeholder="Search by reference, tank, capacity, status or date…" aria-label="Search quotation history">
                <button type="button" class="qh-search-clear" x-show="q" x-cloak @click="q = ''" aria-label="Clear search"><i data-lucide="x"></i></button>
            </div>
            @endif
            <div style="display:flex;flex-direction:column;gap:24px;">
            @forelse($historyBatches as $batch)
                @php
                    // everything the search box matches against for this quotation
                    $hFirst  = $batch->first();
                    $hSearch = strtolower(implode(' ', array_filter([
                        $hFirst->quotationBatch?->reference_no,
                        $hFirst->quotationBatch?->versions?->pluck('quotation_number')->implode(' '),
                        $batch->pluck('tank_type')->filter()->implode(' '),
                        $batch->pluck('capacity')->filter()->implode(' '),
                        $hFirst->status_label ?? $hFirst->status,
                        str_replace('_', ' ', $hFirst->status),
                        $hFirst->created_at?->format('M d, Y'),
                        $hFirst->created_at?->format('F Y'),
                    ])));
                @endphp
                <div data-history-item x-show="matches(@js($hSearch))">
                    @include('partials.client.quotation_batch_card', ['batch' => $batch])
                </div>
            @empty
                <div class="pv-card" style="text-align:center;padding:48px 20px;">
                    @include('partials.empty_tank_art')
                    <p style="font-weight:800;color:var(--dark);margin-bottom:6px;">No completed quotations yet.</p>
                    <p style="font-size:13px;color:var(--muted);">Accepted, rejected or declined requests will show up here.</p>
                </div>
            @endforelse
            @if($historyBatches->isNotEmpty())
                {{-- nothing matches the search --}}
                <div class="pv-card qh-nomatch" x-show="shown === 0" x-cloak>
                    <span class="qh-nomatch-icon"><i data-lucide="search-x"></i></span>
                    <p style="font-weight:800;color:var(--dark);margin:0 0 4px;">No quotations match “<span x-text="q.trim()"></span>”</p>
                    <p style="font-size:13px;color:var(--muted);margin:0 0 12px;">Try a reference number, a tank type or a month.</p>
                    <button type="button" class="qh-nomatch-btn" @click="q = ''">Clear search</button>
                </div>
            @endif
            </div>
        </div>

        {{-- ===================== NEW REQUEST ===================== --}}
        <div class="emp-tab-content {{ $activeTab === 'new-request' ? 'active' : '' }}" id="tab-new-request" role="tabpanel" :class="{ active: tab === 'new-request' }">

            <div class="qr-step">
                <div class="qr-step-head">
                    <span class="qr-step-num">1</span>
                    <div>
                        <div class="qr-step-title">Choose the tanks you need</div>
                        <div class="qr-step-sub">Select one or more. Each tank gets its own quotation.</div>
                    </div>
                </div>
                <div class="qt-cards" role="group" aria-label="Tanks">
                    @foreach($tankCards as $card)
                    <button type="button" class="qt-card" data-slug="{{ $card['slug'] }}" aria-pressed="false"
                            onclick="toggleTank('{{ $card['slug'] }}')">
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
                <div class="qr-picked" id="pickedBar" hidden>
                    <div class="qr-picked-text"><span id="pickedCount"></span><small>Pick more, or continue to fill in the details.</small></div>
                    <button type="button" class="save-btn" onclick="document.getElementById('quotationCard').scrollIntoView({ behavior: 'smooth', block: 'start' })">
                        Continue <i data-lucide="arrow-down"></i>
                    </button>
                </div>
            </div>

            <div class="pv-card" id="quotationCard" @if($initialItems->isEmpty()) hidden @endif>
                <div class="qr-step-head">
                    <span class="qr-step-num">2</span>
                    <div>
                        <div class="qr-step-title">Tell us your requirements</div>
                        <div class="qr-step-sub">Fields marked <span style="color:#dc2626;">*</span> are required.</div>
                    </div>
                    <button type="button" class="qr-change qr-add-tank" onclick="document.querySelector('.qr-step').scrollIntoView({ behavior: 'smooth', block: 'start' })">+ Add another tank</button>
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
                    <div id="tankPanels"></div>

                    <div class="form-group" style="margin-top:18px;">
                        <label for="notesInput">Additional Notes <span style="font-size:11px;color:var(--muted);font-weight:400;">(optional, added to every tank)</span></label>
                        <textarea name="notes" id="notesInput" rows="3" maxlength="2000" placeholder="Anything else that helps us prepare your quotation...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="field-error" id="uploadError" style="display:none;"></div>
                    <div class="qr-submit-row">
                        <span class="qr-submit-hint">
                            <i data-lucide="info" style="width:12px;height:12px;vertical-align:-1px;"></i>
                            <span id="submitHint"></span>
                        </span>
                        <button type="submit" class="save-btn" id="submitRequestBtn">
                            <i data-lucide="send"></i>
                            <span id="submitLabel">Submit Request</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- One selected tank; JS clones this per tank and gives its fields that tank's tank_items[key] names --}}
            <template id="tankPanelTpl">
                <div class="qr-tank-panel">
                    <input type="hidden" data-name="[tank_slug]" data-field="slug">

                    <div class="qr-chosen">
                        <span class="qr-chosen-icon" data-field="icon"></span>
                        <div class="qr-chosen-text">
                            <strong data-field="name"></strong>
                            <span data-field="blurb"></span>
                        </div>
                        <button type="button" class="qr-change" data-field="remove">Remove</button>
                    </div>

                    {{-- ── Service: a combined card's choice (e.g. Fuel Pipeline → installation or re-piping), as cards;
                         they drive the card's own (hidden) select, which is what gets submitted ── --}}
                    <div data-field="choiceSection" hidden>
                        <div class="qr-section-label"><i data-lucide="list-checks"></i> <span data-field="choiceLabel">Service</span> <span style="color:#dc2626;">*</span></div>
                        <div class="qr-sizes" data-field="choiceCards" role="radiogroup"></div>
                        <div data-field="choiceSlot" hidden></div>
                        <div class="qr-divider"></div>
                    </div>

                    {{-- ── Size: a standard size (fixed capacity + dimensions) or the client's own ── --}}
                    <div class="qr-section-label">
                        <i data-lucide="ruler"></i> <span data-field="sizeLabel">Capacity / Size</span> <span style="color:#dc2626;">*</span>
                        <span class="qr-section-sub" data-field="sizeHint" hidden>Choose a standard size, or "Other size" to enter your own.</span>
                    </div>
                    <div class="qr-sizes" data-field="sizeChips" hidden role="radiogroup" aria-label="Standard sizes"></div>
                    {{-- shown for "Other size" (or a tank without standard sizes); the type's Preferred Dimensions field is moved in here --}}
                    <div class="qr-size-fields" data-field="sizeFields">
                        <div class="form-group">
                            <label data-field="capacityLabelWrap"><span data-field="capacityLabel">Capacity / Size</span> <span style="color:#dc2626;">*</span></label>
                            <input type="text" data-name="[capacity]" data-field="capacity" required maxlength="255" autocomplete="off">
                        </div>
                        <div data-field="dimsSlot" style="display:contents;"></div>
                    </div>
                    <div class="field-error" data-error="capacity" hidden></div>

                    <div class="qr-divider"></div>

                    {{-- ── Details: quantity, this tank's own questions, target date ── --}}
                    <div class="qr-section-label"><i data-lucide="sliders-horizontal"></i> Details</div>
                    <div class="qr-fields">
                        <div class="form-group">
                            <label data-field="quantityLabel">Quantity <span style="color:#dc2626;">*</span></label>
                            <input type="number" data-name="[quantity]" data-field="quantity" required min="1" max="1000" value="1">
                            <div class="qr-hint" data-field="quantityHint">How many identical units you need.</div>
                            <div class="field-error" data-error="quantity" hidden></div>
                        </div>
                        {{-- type-specific fields are rendered here --}}
                        <div class="qr-wide" data-field="typeFields" style="display:contents;"></div>
                    </div>

                    {{-- picked up at GMD's shop unless the client asks GMD to assemble the tank on site --}}
                    <label class="qr-toggle" style="margin-top:18px;">
                        <input type="hidden" data-name="[onsite_assembly]" value="0">
                        <input type="checkbox" data-name="[onsite_assembly]" value="1" data-field="onsite">
                        <span class="qr-switch" aria-hidden="true"></span>
                        <span class="qr-toggle-text">
                            <strong>Assembled on site by GMD</strong>
                            <small>GMD builds the tank at your site. Off: the finished tank is picked up at our shop in Calauan, Laguna.</small>
                        </span>
                    </label>
                    <div class="form-group qr-site" data-field="siteGroup" style="margin-top:12px;" hidden>
                        <label data-field="siteLabel">Site Location <span style="color:#dc2626;">*</span></label>
                        <input type="text" data-name="[site_location]" data-field="site" maxlength="500" autocomplete="street-address" disabled
                               placeholder="Where the tank will be assembled — street, barangay, city / municipality, province">
                        <div class="field-error" data-error="site_location" hidden></div>
                    </div>

                    <div class="qr-divider"></div>

                    <div class="qr-section-label"><i data-lucide="paperclip"></i> Design or reference files <span style="font-size:11px;color:var(--muted);font-weight:500;">(optional)</span></div>
                    <button type="button" class="qr-design-btn" data-field="designBtn">
                        <span class="qr-design-icon"><i data-lucide="upload-cloud"></i></span>
                        <span class="qr-design-text"><strong>Attach your design or photos</strong><em>PDF, image or AutoCAD (.dwg) · up to 5 files, 10MB each</em></span>
                    </button>
                    <input type="file" data-name="[design_files][]" data-field="designInput" accept=".pdf,image/*,.dwg" multiple style="display:none;">
                    <div class="qr-file-list" data-field="designList" style="display:none;"></div>
                    <div class="field-error" data-field="designError" style="display:none;"></div>
                </div>
            </template>
        </div>{{-- /x-data tabs --}}

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        function openModal(id) { var m = document.getElementById(id); if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; } }
        function closeModal(id) { var m = document.getElementById(id); if (m) { m.classList.remove('show'); document.body.style.overflow = ''; } }
        document.querySelectorAll('.modal-overlay').forEach(function (modal) {
            modal.addEventListener('click', function (e) { if (e.target === this) closeModal(this.id); });
        });

        var TANKS         = @json(collect($tankCards)->keyBy('slug'));
        var INITIAL_ITEMS = @json($initialItems);
        var ITEM_ERRORS   = @json((object) $itemErrors);
        var POST_LIMIT    = {{ $postLimit }};   // bytes the server accepts in one request
        var FILE_LIMIT    = {{ $fileLimit }};   // files it accepts in one request; any more are silently dropped

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

        function fmtBytes(b) { return b < 1024 ? b + ' B' : (b < 1048576 ? Math.round(b / 1024) + ' KB' : (b / 1048576).toFixed(1) + ' MB'); }

        // ── a tank's own fields (each selected tank has its own panel, so its own tank_items[key] names) ──
        function fieldHtml(f, value, key, errors) {
            var name = 'tank_items[' + key + '][specs][' + f.key + ']';
            var id   = 'spec_' + key + '_' + f.key;
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
            var err  = errors[f.key] ? '<div class="field-error">' + esc(errors[f.key]) + '</div>' : '';
            var cond = f.show_if ? ' data-show-if="' + esc(f.show_if[0]) + '" data-show-value="' + esc(f.show_if[1]) + '"' : '';
            return '<div class="form-group' + wide + '"' + cond + '><label for="' + id + '">' + esc(f.label) + req + '</label>' + input + err + '</div>';
        }

        var panels  = {};   // slug → the selected tank's panel
        var nextKey = 0;

        // Builds one tank's panel: everything in here works on that panel alone
        function addPanel(slug, key, item, errors) {
            var t = TANKS[slug];
            if (!t || panels[slug]) return;
            item = item || {};
            errors = errors || {};
            var specErrors = errors.specs || {};
            var values = Object.assign({}, item.specs || {});

            var node = document.getElementById('tankPanelTpl').content.firstElementChild.cloneNode(true);
            var get  = function (field) { return node.querySelector('[data-field="' + field + '"]'); };
            node.dataset.slug = slug;
            node.querySelectorAll('[data-name]').forEach(function (el) { el.name = 'tank_items[' + key + ']' + el.dataset.name; });

            get('slug').value = slug;
            get('name').textContent  = t.type;
            get('blurb').textContent = t.blurb;
            get('icon').innerHTML    = '<i data-lucide="' + esc(t.icon) + '"></i>';
            get('remove').addEventListener('click', function () { toggleTank(slug); });
            get('capacityLabel').textContent = t.capacity.label;
            get('sizeLabel').textContent     = t.capacity.label;
            get('quantityHint').textContent  = t.unit === 'unit' ? 'How many identical units you need.' : 'Number of ' + t.unit + 's (usually 1).';

            var cap = get('capacity');
            cap.id = 'capacity_' + key;
            get('capacityLabelWrap').htmlFor = cap.id;
            cap.placeholder = t.capacity.placeholder;
            cap.value = item.capacity || '';
            cap.addEventListener('input', function () { formatCapacity(cap); markSize(); });
            var qty = get('quantity');
            qty.id = 'quantity_' + key;
            get('quantityLabel').htmlFor = qty.id;
            if (item.quantity) qty.value = item.quantity;

            node.querySelectorAll('[data-error]').forEach(function (el) {
                if (errors[el.dataset.error]) { el.textContent = errors[el.dataset.error]; el.hidden = false; }
            });

            // on-site assembly: the site location shows (and is required) only when it's switched on
            var onsite = get('onsite'), siteGroup = get('siteGroup'), site = get('site');
            site.id = 'site_' + key;
            get('siteLabel').htmlFor = site.id;
            site.value = item.site_location || '';
            function syncSite() {
                siteGroup.hidden = !onsite.checked;
                site.disabled    = !onsite.checked;
                site.required    = onsite.checked;
            }
            onsite.checked = !!Number(item.onsite_assembly || 0);
            onsite.addEventListener('change', function () { syncSite(); if (onsite.checked) site.focus(); });
            syncSite();

            // the tank's own questions; Preferred Dimensions belongs with the size, so it moves up into the Size section
            var typeFields = get('typeFields');
            typeFields.innerHTML = t.fields.map(function (f) { return fieldHtml(f, values[f.key], key, specErrors); }).join('');
            var dimsInput = typeFields.querySelector('[data-spec="dimensions"]');
            if (dimsInput) {
                var dimsGroup = dimsInput.closest('.form-group');
                dimsGroup.classList.remove('qr-wide');
                get('dimsSlot').appendChild(dimsGroup);
            }
            var dims = function () { return node.querySelector('[data-spec="dimensions"]'); };

            // fields shown only for a particular answer (e.g. tower height for an elevated tank)
            function applyConditions() {
                typeFields.querySelectorAll('[data-show-if]').forEach(function (group) {
                    var source = typeFields.querySelector('[data-spec="' + group.dataset.showIf + '"]');
                    var on = source && source.value === group.dataset.showValue;
                    group.style.display = on ? '' : 'none';
                    group.querySelectorAll('input, select, textarea').forEach(function (el) { el.disabled = !on; });
                });
            }

            // ── Size: picking a standard size fixes Capacity + Preferred Dimensions (shown on the card itself, inputs hidden);
            //    "Other size" opens the two inputs for the client's own. Types without standard sizes just show the inputs. ──
            var sizeMode = '';   // '' = nothing picked yet, 'std' = a standard size, 'other' = typed, 'free' = no standard sizes
            var box = get('sizeChips');
            function pickSize(btn) {
                sizeMode = 'std';
                cap.value = btn.dataset.capacity;
                if (dims()) dims().value = btn.dataset.dimensions;
                syncSizeFields();
            }
            function chooseOther(focus) {
                if (sizeMode === 'std') { cap.value = ''; if (dims()) dims().value = ''; }   // don't leave a standard size behind to edit
                sizeMode = 'other';
                syncSizeFields();
                if (focus) cap.focus();
            }
            // the inputs show only when the client enters their own size; a standard size is shown on its card
            function syncSizeFields() {
                get('sizeFields').hidden = !(sizeMode === 'other' || sizeMode === 'free');
                // a lone capacity input (no sizes, no dimensions) already sits under the section title — no second label
                get('capacityLabelWrap').hidden = sizeMode === 'free' && !dims();
                var msg = box.nextElementSibling;   // "Choose a size…" from a blocked submit
                if (sizeMode && msg && msg.matches('.field-error.js')) msg.remove();
                markSize();
            }
            function markSize() {
                var v = cap.value.trim();
                box.querySelectorAll('.qr-size').forEach(function (b) {
                    var on = b.dataset.other ? sizeMode === 'other' : (sizeMode === 'std' && b.dataset.capacity === v);
                    b.classList.toggle('is-on', on);
                    b.setAttribute('aria-checked', on ? 'true' : 'false');
                });
            }
            function renderSizes() {
                var sizes = t.sizes || [];
                box.innerHTML = sizes.map(function (s) {
                    return '<button type="button" class="qr-size" role="radio" data-capacity="' + esc(s.capacity) + '" data-dimensions="' + esc(s.dimensions) + '">' +
                        '<strong>' + esc(s.capacity) + '</strong>' + (s.dimensions ? '<small>' + esc(s.dimensions) + '</small>' : '') + '</button>';
                }).join('') + (sizes.length ? '<button type="button" class="qr-size qr-size--other" role="radio" data-other="1"><strong>Other size</strong><small>Enter your own</small></button>' : '');
                box.hidden = !sizes.length;
                get('sizeHint').hidden = !sizes.length;
                box.querySelectorAll('.qr-size').forEach(function (b) {
                    b.addEventListener('click', function () { b.dataset.other ? chooseOther(true) : pickSize(b); });
                });
                // start from what's already there: a standard size (from the details page or a failed submit), typed values, or nothing
                var match = [].find.call(box.querySelectorAll('.qr-size[data-capacity]'), function (b) { return b.dataset.capacity === cap.value.trim(); });
                if (!sizes.length) sizeMode = 'free';
                else if (match) return pickSize(match);
                else if (cap.value.trim()) return chooseOther(false);
                syncSizeFields();
            }

            // ── a combined card's choice (Fuel Pipeline → service) as cards; the select it drives moves out of Details ──
            var choiceBox = get('choiceCards'), choiceSelect = t.choice ? typeFields.querySelector('[data-spec="' + t.choice.key + '"]') : null;
            function renderChoice() {
                get('choiceSection').hidden = !choiceSelect;
                if (!choiceSelect) return;
                get('choiceSlot').appendChild(choiceSelect.closest('.form-group'));   // still submitted, just not shown
                get('choiceLabel').textContent = t.choice.label === 'Service Needed' ? 'Service' : t.choice.label;
                choiceBox.innerHTML = t.choice.options.map(function (o) {
                    return '<button type="button" class="qr-size" role="radio" data-value="' + esc(o.value) + '">' +
                        '<strong>' + esc(o.value) + '</strong>' + (o.text ? '<small>' + esc(o.text) + '</small>' : '') + '</button>';
                }).join('');
                if (specErrors[t.choice.key]) {   // a server-side error for the hidden select shows under the cards
                    var err = document.createElement('div');
                    err.className = 'field-error js';
                    err.textContent = specErrors[t.choice.key];
                    choiceBox.insertAdjacentElement('afterend', err);
                }
                var mark = function () {
                    choiceBox.querySelectorAll('.qr-size').forEach(function (b) {
                        var on = b.dataset.value === choiceSelect.value;
                        b.classList.toggle('is-on', on);
                        b.setAttribute('aria-checked', on ? 'true' : 'false');
                    });
                };
                choiceBox.querySelectorAll('.qr-size').forEach(function (b) {
                    b.addEventListener('click', function () {
                        choiceSelect.value = b.dataset.value;
                        choiceSelect.dispatchEvent(new Event('change'));
                        var msg = choiceBox.nextElementSibling;   // "Choose …" from a blocked submit
                        if (msg && msg.matches('.field-error.js')) msg.remove();
                        mark();
                    });
                });
                mark();
            }

            // ── design files: a native file input replaces its list on every pick, so keep a running list ──
            var MAX_FILES = 5, MAX_BYTES = 10 * 1024 * 1024;
            var input = get('designInput'), btn = get('designBtn'), list = get('designList'), error = get('designError');
            var files = [];
            function syncFiles() { var dt = new DataTransfer(); files.forEach(function (f) { dt.items.add(f); }); input.files = dt.files; }
            function say(msg) { error.textContent = msg; error.style.display = msg ? 'block' : 'none'; }
            function renderFiles() {
                list.style.display = files.length ? 'flex' : 'none';
                list.innerHTML = '';
                files.forEach(function (f, i) {
                    var chip = document.createElement('div');
                    chip.className = 'qr-file-chip';
                    var thumb = /^image\//.test(f.type)
                        ? '<img class="qr-file-thumb" src="' + URL.createObjectURL(f) + '" alt="">'
                        : '<span class="qr-file-thumb"><i data-lucide="file-text"></i></span>';
                    chip.innerHTML = thumb + '<div class="qr-file-meta"><div class="qr-file-name" title="' + esc(f.name) + '">' + esc(f.name) + '</div>' +
                        '<div class="qr-file-size">' + fmtBytes(f.size) + '</div></div>' +
                        '<button type="button" class="qr-file-remove" title="Remove" aria-label="Remove ' + esc(f.name) + '"><i data-lucide="x"></i></button>';
                    chip.querySelector('.qr-file-remove').addEventListener('click', function (e) { e.stopPropagation(); files.splice(i, 1); syncFiles(); say(''); renderFiles(); });
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
                syncFiles(); say(problems.filter(function (p, i, a) { return a.indexOf(p) === i; }).join(' ')); renderFiles();
            });

            // ── check this tank's required fields in the browser first (the server checks again) ──
            function validate() {
                var firstBad = null;
                node.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (el) {
                    if (el.disabled || el.offsetParent === null) return;
                    if (!el.checkValidity()) {
                        var msg = document.createElement('div');
                        msg.className = 'field-error js';
                        msg.textContent = el.validity.valueMissing ? 'This field is required.' : el.validationMessage;
                        el.insertAdjacentElement('afterend', msg);
                        firstBad = firstBad || el;
                    }
                });
                // a combined card's choice (e.g. the service) not made yet: say so under its cards
                if (choiceSelect && !choiceSelect.value) {
                    var choiceMsg = document.createElement('div');
                    choiceMsg.className = 'field-error js';
                    choiceMsg.textContent = 'Choose the ' + get('choiceLabel').textContent.toLowerCase() + ' you need.';
                    choiceBox.insertAdjacentElement('afterend', choiceMsg);
                    firstBad = firstBad || choiceBox.querySelector('.qr-size');
                }
                // standard sizes on show but none chosen: say so under the size cards
                if (!box.hidden && sizeMode === '') {
                    var sizeMsg = document.createElement('div');
                    sizeMsg.className = 'field-error js';
                    sizeMsg.textContent = 'Choose a size, or "Other size" to enter your own.';
                    box.insertAdjacentElement('afterend', sizeMsg);
                    firstBad = firstBad || box.querySelector('.qr-size');
                }
                return firstBad;
            }

            typeFields.querySelectorAll('[data-spec]').forEach(function (el) { el.addEventListener('change', applyConditions); });
            applyConditions();
            renderChoice();
            renderSizes();

            document.getElementById('tankPanels').appendChild(node);
            panels[slug] = { node: node, validate: validate, files: function () { return files; } };
        }

        // Cards, the "selected" bar and the submit button all follow the panels
        function syncSelection() {
            var count = Object.keys(panels).length;
            document.querySelectorAll('.qt-card').forEach(function (c) {
                var on = !!panels[c.dataset.slug];
                c.classList.toggle('is-selected', on);
                c.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            document.getElementById('quotationCard').hidden = !count;
            document.getElementById('pickedBar').hidden = !count;
            document.getElementById('pickedCount').textContent = count + (count === 1 ? ' tank selected' : ' tanks selected');
            document.getElementById('submitLabel').textContent = count > 1 ? 'Submit ' + count + ' Requests' : 'Submit Request';
            document.getElementById('submitHint').textContent = count > 1
                ? 'Each tank gets its own reference number and quotation under "My Quotations".'
                : 'You\'ll get a reference number now, and your quotation will appear under "My Quotations".';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        // Clicking a card selects it and goes down to its details, or takes it off the request
        function toggleTank(slug) {
            if (panels[slug]) {
                panels[slug].node.remove();
                delete panels[slug];
                syncSelection();
                return;
            }
            addPanel(slug, nextKey++);
            syncSelection();
            if (panels[slug]) panels[slug].node.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        document.getElementById('quotationRequestForm').addEventListener('submit', function (e) {
            var form = this, firstBad = null;
            form.querySelectorAll('.field-error.js').forEach(function (el) { el.remove(); });
            // panels in page order, so the client is taken to the first problem
            form.querySelectorAll('.qr-tank-panel').forEach(function (node) {
                var bad = panels[node.dataset.slug].validate();
                firstBad = firstBad || bad;
            });

            // past the server's limits, files would be dropped without a word, or the whole form rejected
            var all = Object.keys(panels).reduce(function (list, slug) { return list.concat(panels[slug].files()); }, []);
            var bytes = all.reduce(function (sum, f) { return sum + f.size; }, 0);
            var uploadError = document.getElementById('uploadError');
            var tooMany = FILE_LIMIT > 0 && all.length > FILE_LIMIT, tooBig = POST_LIMIT > 0 && bytes > POST_LIMIT * 0.95;
            uploadError.textContent = tooMany ? 'You attached ' + all.length + ' files, but only ' + FILE_LIMIT + ' can be sent at once. Remove some, or send those tanks as a separate request.'
                : (tooBig ? 'Your files add up to ' + fmtBytes(bytes) + ', but only ' + fmtBytes(POST_LIMIT) + ' can be sent at once. Remove some, or send those tanks as a separate request.' : '');
            uploadError.style.display = uploadError.textContent ? 'block' : 'none';

            if (firstBad || tooMany || tooBig) {
                e.preventDefault();
                var target = firstBad || uploadError;
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (firstBad && firstBad.focus) firstBad.focus({ preventScroll: true });
            }
        });

        INITIAL_ITEMS.forEach(function (i) {
            addPanel(i.slug, i.key, i.item, ITEM_ERRORS[i.key]);
            nextKey = Math.max(nextKey, i.key + 1);
        });
        syncSelection();
        @if($selectedTank && !$errors->any())
        document.getElementById('quotationCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
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
