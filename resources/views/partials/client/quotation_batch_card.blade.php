{{-- Renders one submitted quotation batch (a client's visit to the request form) as ONE
     combined quotation — all tanks in the batch share one status, one price, one decision.
     Expects: $batch — a Collection of QuotationRequest models sharing a batch_id. --}}
@php
    $statusMeta = [
        'pending'        => ['label' => 'Under Review', 'icon' => 'clock', 'bg' => '#FFF3D6', 'color' => '#8A6100'],
        'in_preparation' => ['label' => 'Quotation Being Prepared', 'icon' => 'calculator', 'bg' => '#FFF3D6', 'color' => '#8A6100'],
        'quotation_sent' => ['label' => 'Quotation Ready for Review', 'icon' => 'file-text', 'bg' => '#EAF0FF', 'color' => '#1e40af'],
        'approved'       => ['label' => 'Quotation Approved', 'icon' => 'thumbs-up', 'bg' => '#dcfce7', 'color' => '#16a34a'],
        'converted'      => ['label' => 'Accepted — Project Created', 'icon' => 'check-circle-2', 'bg' => '#dcfce7', 'color' => '#16a34a'],
        'rejected'       => ['label' => 'Rejected by You', 'icon' => 'x-circle', 'bg' => '#f3f4f6', 'color' => '#4b5563'],
        'declined'       => ['label' => 'Not Approved', 'icon' => 'x-circle', 'bg' => '#fee2e2', 'color' => '#dc2626'],
    ];
    $first  = $batch->first();
    $status = $first->status;
    $meta   = $statusMeta[$status] ?? $statusMeta['pending'];
    $specifiedCount = $batch->filter(fn ($qr) => $qr->tank_type)->count();
    $quotationBatch = $first->quotationBatch;
    $versions       = $quotationBatch ? $quotationBatch->versions : collect();
    $latestVersion  = $versions->first();
    // after approving, the client sends their Purchase Order — the project (and the down payment) starts from it
    $hasPo = (bool) $quotationBatch?->hasPurchaseOrder();
    if ($status === 'approved') {
        $meta = $hasPo
            ? ['label' => 'Purchase Order Sent', 'icon' => 'file-check-2', 'bg' => '#dcfce7', 'color' => '#16a34a']
            : ['label' => 'Send Your Purchase Order', 'icon' => 'file-signature', 'bg' => '#EAF0FF', 'color' => '#1e40af'];
    }
@endphp
<div class="pv-card">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--border);">
        <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--muted);font-weight:700;flex-wrap:wrap;">
            @if($quotationBatch?->reference_no)
            <span style="color:var(--dark);font-weight:900;letter-spacing:.02em;">{{ $quotationBatch->reference_no }}</span>
            <span aria-hidden="true">·</span>
            @endif
            <i data-lucide="calendar" style="width:14px;height:14px;"></i>
            Submitted {{ $first->created_at->format('M d, Y') }}
        </div>
        <div style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:800;color:{{ $meta['color'] }};background:{{ $meta['bg'] }};border-radius:8px;padding:4px 11px;text-transform:uppercase;letter-spacing:.03em;">
            <i data-lucide="{{ $meta['icon'] }}" style="width:12px;height:12px;"></i>
            {{ $meta['label'] }}
        </div>
    </div>

    @if(!in_array($status, ['declined', 'rejected'], true))
    @php
        $isRevision = in_array($status, ['pending', 'in_preparation'], true) && !empty($first->decline_reason);
        $steps      = ['Submitted', $isRevision ? 'Revising' : 'Being prepared', 'Quotation ready', 'Approved', 'Purchase order', 'Project created'];
        $current    = ['pending' => 1, 'in_preparation' => 1, 'quotation_sent' => 2, 'approved' => $hasPo ? 5 : 4, 'converted' => 5][$status] ?? 1;
        $finished   = $status === 'converted';   // every step is complete
        $reviewHint = $isRevision
            ? ['icon' => 'rotate-ccw', 'tone' => '', 'text' => 'Your requested changes were sent. GMD South Phils is preparing an updated quotation for you.']
            : ['icon' => 'clock', 'tone' => '', 'text' => 'GMD South Phils is reviewing your request and preparing your quotation. We will let you know as soon as it is ready.'];
        $hint = [
            'pending'        => $reviewHint,
            'in_preparation' => $reviewHint,
            'quotation_sent' => ['icon' => 'bell-ring', 'tone' => 'is-action', 'text' => 'Your quotation is ready. Open it to review the details, then approve it, ask for changes, or reject it.'],
            'approved'       => $hasPo
                ? ['icon' => 'file-check-2', 'tone' => 'is-good', 'text' => 'Purchase Order received. GMD South Phils will set up your project — your down payment then opens under Payments.']
                : ['icon' => 'file-signature', 'tone' => 'is-action', 'text' => 'You approved this quotation. Send GMD South Phils your Purchase Order below — your project and down payment start once they receive it.'],
            'converted'      => ['icon' => 'check-circle-2', 'tone' => 'is-good', 'text' => 'Your project has been created. You can follow its progress under My Projects.'],
        ][$status] ?? null;
    @endphp
    <ol class="qc-track" aria-label="Quotation progress">
        @foreach($steps as $i => $label)
        @php $state = $i < $current || ($finished && $i === $current) ? 'is-done' : ($i === $current ? 'is-active' : ''); @endphp
        <li class="{{ $state }}">
            <span class="qc-dot">@if($state === 'is-done')<i data-lucide="check"></i>@else{{ $i + 1 }}@endif</span>
            <span class="qc-step-label">{{ $label }}</span>
        </li>
        @endforeach
    </ol>
    @if($hint)
    <div class="qc-hint {{ $hint['tone'] }}"><i data-lucide="{{ $hint['icon'] }}"></i><span>{{ $hint['text'] }}</span></div>
    @endif
    @endif
    @php
        // Each tank carries its own design files. Requests sent before that change stored the same shared
        // set on every tank — show that once instead of repeating it on each tank.
        $tanksWithFiles = $batch->filter(fn ($qr) => !empty($qr->reference_files));
        $legacyShared   = $batch->count() > 1
            && $tanksWithFiles->count() === $batch->count()
            && $tanksWithFiles->map(fn ($qr) => json_encode($qr->reference_files))->unique()->count() === 1;
    @endphp

    @once
    @endonce

    <div class="qc-section">
        <i data-lucide="clipboard-list"></i> Your request
        <small>{{ $batch->count() }} {{ $batch->count() === 1 ? 'tank' : 'tanks' }}</small>
    </div>

    <div class="qc-tanks">
        @foreach($batch as $qr)
        @php
            $ful   = $qr->fulfillmentInfo();
            $files = (!$legacyShared && !empty($qr->reference_files)) ? $qr->reference_files : [];
        @endphp
        <div class="qc-tank">
            <div class="qc-head">
                <span class="qc-title"><i data-lucide="package"></i> {{ $qr->tank_type ?: 'Tank' }}</span>
                <span class="qc-pill {{ $ful['key'] === 'pickup' ? 'is-pickup' : 'is-delivery' }}">
                    <i data-lucide="{{ $ful['icon'] }}"></i>
                    {{ $ful['label'] }}
                </span>
            </div>

            <div class="qc-facts">
                <div class="qc-fact"><span>{{ \App\Support\TankCatalog::forType($qr->tank_type)['capacity']['label'] }}</span><strong>{{ $qr->capacity ?: '—' }}</strong></div>
                <div class="qc-fact"><span>Quantity</span><strong>{{ $qr->quantity }}</strong></div>
                {{-- the form no longer asks for a target date — shown only on older requests that have one --}}
                @if(!empty($qr->target_timeline))
                <div class="qc-fact"><span>Target Date</span><strong>{{ $qr->target_timeline_display }}</strong></div>
                @endif
                @foreach($qr->specificationLines() as $label => $value)
                <div class="qc-fact {{ mb_strlen($value) > 40 ? 'qc-fact-wide' : '' }}"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
                @endforeach
                @if($ful['address_label'])
                <div class="qc-fact qc-fact-wide"><span>{{ $ful['address_label'] }}</span><strong>{{ $qr->location ?: '—' }}</strong></div>
                @endif
            </div>

            @if($files)
            <div class="qc-files" data-receipt-set>
                <span class="qc-label">Your design</span>
                @foreach($files as $i => $file)
                <a href="{{ $file }}" target="_blank" data-receipt class="qc-file">
                    <i data-lucide="paperclip"></i> {{ count($files) > 1 ? 'Design ' . ($i + 1) : 'View design' }}
                </a>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach

        @if($legacyShared)
        <div class="qc-files" style="border-top:none;padding-top:0;" data-receipt-set>
            <span class="qc-label">Your attached files</span>
            @foreach($first->reference_files as $i => $file)
            <a href="{{ $file }}" target="_blank" data-receipt class="qc-file"><i data-lucide="paperclip"></i> Attachment {{ $i + 1 }}</a>
            @endforeach
        </div>
        @endif
    </div>

    @if($latestVersion)
    @php $snap = $latestVersion->snapshot; @endphp
    <div class="qc-section">
        <i data-lucide="file-text"></i> Quotation
        <small>from GMD South Phils</small>
    </div>
    <div class="qc-quote">
        <div class="qc-head">
            <span class="qc-title" style="font-size:14px;"><i data-lucide="file-check-2"></i> {{ $latestVersion->quotation_number }}</span>
            <span class="qc-pill">{{ $latestVersion->statusLabel() }}</span>
        </div>
        <div class="qc-facts">
            <div class="qc-fact"><span>Date</span><strong>{{ $latestVersion->sent_at?->format('M d, Y') }}</strong></div>
            <div class="qc-fact"><span>Total Amount</span><strong>₱{{ number_format($latestVersion->contract_value, 2) }}</strong></div>
            <div class="qc-fact"><span>Quantity</span><strong>{{ $snap['tank']['quantity'] ?? '—' }}</strong></div>
            <div class="qc-fact"><span>Valid Until</span><strong>{{ isset($snap['valid_until']) ? \Carbon\Carbon::parse($snap['valid_until'])->format('M d, Y') : '—' }}</strong></div>
            <div class="qc-fact qc-fact-wide"><span>Payment Terms</span><strong>{{ $snap['payment_terms'] ?? '—' }}</strong></div>
        </div>
        <div class="qc-files" style="border-top:1px dashed #c7d7fe;">
            {{-- opens in the in-page viewer (ctrl/middle-click still opens a tab) --}}
            <a href="{{ route('client.quotation.show', $first->batch_id) }}" target="_blank" data-receipt="doc" data-own-close class="qc-file"><i data-lucide="eye"></i> View full quotation</a>
            <a href="{{ route('client.quotation.pdf', [$first->batch_id, $latestVersion->version]) }}" class="qc-file"><i data-lucide="download"></i> Download PDF</a>
            @if(!empty($quotationBatch->quotation_files))
                <span data-receipt-set style="display:contents;">
                @foreach($quotationBatch->quotation_files as $i => $file)
                <a href="{{ $file }}" target="_blank" data-receipt class="qc-file"><i data-lucide="paperclip"></i> Attachment {{ $i + 1 }}</a>
                @endforeach
                </span>
            @endif
        </div>
        @if($versions->count() > 1)
        <div style="font-size:12px;color:#1e40af;font-weight:600;">
            Earlier versions:
            @foreach($versions->skip(1) as $old)
            <a href="{{ route('client.quotation.show', [$first->batch_id, 'v' => $old->version]) }}" style="color:inherit;font-weight:800;">{{ $old->quotation_number }}</a> ({{ strtolower($old->statusLabel()) }}){{ !$loop->last ? ',' : '' }}
            @endforeach
        </div>
        @endif
    </div>
    @elseif(!empty($quotationBatch?->quotation_files))
    {{-- Quotations sent before the system generated them were uploaded as files --}}
    <div class="qc-section">
        <i data-lucide="file-text"></i> Quotation
        <small>from GMD South Phils</small>
    </div>
    <div class="qc-quote">
        <div class="qc-files" style="border-top:none;padding-top:0;" data-receipt-set>
            <span class="qc-label">Sent to you</span>
            @foreach($quotationBatch->quotation_files as $i => $file)
            <a href="{{ $file }}" target="_blank" data-receipt class="qc-file">
                <i data-lucide="file-text"></i> {{ count($quotationBatch->quotation_files) > 1 ? 'Quotation ' . ($i + 1) : 'View quotation' }}
            </a>
            @endforeach
        </div>
    </div>
    @endif
    @if($status === 'rejected')
    <div style="margin-bottom:16px;padding:10px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:10px;font-size:12.5px;color:var(--muted);display:flex;align-items:flex-start;gap:8px;">
        <i data-lucide="info" style="width:14px;height:14px;flex-shrink:0;margin-top:1px;"></i>
        <span style="flex:1;min-width:0;overflow-wrap:anywhere;">You rejected this quotation{{ $first->decline_reason ? ': ' . $first->decline_reason : '.' }} Need something else? Start a new request any time.</span>
    </div>
    @elseif($status === 'declined' && $first->decline_reason)
    <div style="margin-bottom:16px;padding:10px 12px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;font-size:12.5px;color:#dc2626;display:flex;align-items:flex-start;gap:8px;">
        <i data-lucide="info" style="width:14px;height:14px;flex-shrink:0;margin-top:1px;"></i>
        <span style="flex:1;min-width:0;overflow-wrap:anywhere;word-break:break-word;"><strong>Reason:</strong> {{ $first->decline_reason }}</span>
    </div>
    @elseif(in_array($status, ['pending', 'in_preparation'], true) && $first->decline_reason)
    <div style="margin-bottom:16px;padding:10px 12px;background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;font-size:12.5px;color:#9a3412;display:flex;align-items:flex-start;gap:8px;">
        <i data-lucide="rotate-ccw" style="width:14px;height:14px;flex-shrink:0;margin-top:1px;"></i>
        <span style="flex:1;min-width:0;overflow-wrap:anywhere;word-break:break-word;"><strong>Your requested changes:</strong> {{ $first->decline_reason }}</span>
    </div>
    @endif

    @if($status === 'quotation_sent' && $latestVersion)
    <div class="qc-section">
        <i data-lucide="gavel"></i> Your decision
    </div>
    <div class="qc-decision qc-highlight">
        <span class="qc-action-flag"><span class="qc-action-dot"></span> Needs your action</span>
        <a href="{{ route('client.quotation.show', $first->batch_id) }}" target="_blank" data-receipt="doc" data-own-close class="save-btn" style="text-decoration:none;">
            <i data-lucide="file-search"></i>
            Review &amp; Decide
        </a>
        <span style="font-size:12px;color:var(--muted);align-self:center;">Open the quotation to approve it, request a revision, or reject it.</span>
    </div>
    @elseif($status === 'quotation_sent')
    {{-- a quotation uploaded as a file before quotations were generated by the system --}}
    <div class="qc-section">
        <i data-lucide="gavel"></i> Your decision
    </div>
    <div class="qc-decision qc-highlight">
        <span class="qc-action-flag"><span class="qc-action-dot"></span> Needs your action</span>
        <button type="button" class="save-btn" onclick="openModal('approveQuotationModal-{{ $first->batch_id }}')">
            <i data-lucide="thumbs-up"></i>
            Approve Quotation
        </button>
        <button type="button" class="cancel-btn" onclick="openModal('rejectQuotationModal-{{ $first->batch_id }}')">
            <i data-lucide="edit-3"></i>
            Request Revision
        </button>
    </div>

    <div class="modal-overlay" id="approveQuotationModal-{{ $first->batch_id }}">
        <div class="modal-card" style="max-width:420px;">
            <div class="modal-header">
                <div>
                    <h2>Approve Quotation?</h2>
                    <p>Next, you'll send GMD South Phils your Purchase Order so they can set up your project.</p>
                </div>
                <button class="modal-close" type="button" onclick="closeModal('approveQuotationModal-{{ $first->batch_id }}')">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('client.quotation.approve', $first->batch_id) }}">
                @csrf
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeModal('approveQuotationModal-{{ $first->batch_id }}')">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="thumbs-up"></i>
                        Approve Quotation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="rejectQuotationModal-{{ $first->batch_id }}">
        <div class="modal-card" style="max-width:420px;">
            <div class="modal-header">
                <div>
                    <h2>Request a Revision?</h2>
                    <p>Let GMD South Phils know what you'd like changed — it helps them prepare a better quotation.</p>
                </div>
                <button class="modal-close" type="button" onclick="closeModal('rejectQuotationModal-{{ $first->batch_id }}')">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('client.quotation.revision', $first->batch_id) }}">
                @csrf
                <div class="form-group" style="margin-bottom:18px;">
                    <label>Reason</label>
                    <textarea name="reason" rows="3" required placeholder="e.g. Price is over budget, changed requirements..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeModal('rejectQuotationModal-{{ $first->batch_id }}')">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="send"></i>
                        Request Revision
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if(in_array($status, ['approved', 'converted'], true) && $quotationBatch && ($status === 'approved' || $hasPo))
    {{-- Purchase Order: sent by the client after approving; GMD creates the project (and the down payment) from it --}}
    @once
    @endonce
    @php $poOpen = !$hasPo || ($errors->any() && old('po_batch') === $first->batch_id); @endphp
    <div class="qc-section">
        <i data-lucide="file-signature"></i> Purchase Order
        @if($hasPo)<small>sent {{ $quotationBatch->po_submitted_at->format('M d, Y') }}</small>@endif
    </div>
    <div x-data="{ editing: @js($poOpen && $hasPo) }">
    @if($hasPo)
    <div class="qc-po is-sent" x-show="!editing">
        <div class="qc-facts">
            <div class="qc-fact"><span>PO Number</span><strong>{{ $quotationBatch->po_number }}</strong></div>
            <div class="qc-fact"><span>Sent</span><strong>{{ $quotationBatch->po_submitted_at->format('M d, Y h:i A') }}</strong></div>
            @if($quotationBatch->po_notes)
            <div class="qc-fact qc-fact-wide"><span>Your note</span><strong>{{ $quotationBatch->po_notes }}</strong></div>
            @endif
        </div>
        <div class="qc-files" style="border-top:1px dashed #86efac;" data-receipt-set>
            @foreach((array) $quotationBatch->po_files as $i => $file)
            <a href="{{ $file }}" target="_blank" data-receipt class="qc-file"><i data-lucide="paperclip"></i> {{ count($quotationBatch->po_files) > 1 ? 'PO file ' . ($i + 1) : 'View Purchase Order' }}</a>
            @endforeach
            @if($status === 'approved')
            <button type="button" class="qc-file" style="cursor:pointer;" @click="editing = true"><i data-lucide="refresh-cw"></i> Replace</button>
            @endif
        </div>
    </div>
    @endif
    @if($status === 'approved')
    <form method="POST" action="{{ route('client.quotation.purchase_order', $first->batch_id) }}" enctype="multipart/form-data"
          class="qc-po{{ $hasPo ? '' : ' qc-highlight' }}" @if($hasPo) x-show="editing" x-cloak @endif data-po-form>
        @unless($hasPo)
        {{-- the PO is what the client still has to send: this form is the part highlighted --}}
        <span class="qc-action-flag"><span class="qc-action-dot"></span> Needs your action</span>
        @endunless
        @csrf
        <input type="hidden" name="po_batch" value="{{ $first->batch_id }}">
        <p class="qc-po-lead">
            @if($hasPo)
            Send an updated Purchase Order — it replaces the one GMD South Phils has now.
            @else
            Issue a Purchase Order for <strong>{{ $latestVersion?->quotation_number ?? $quotationBatch->reference_no }}</strong>
            @if($latestVersion)(₱{{ number_format($latestVersion->contract_value, 2) }})@endif
            and send it here. Once GMD South Phils receives it, they set up your project and your down payment opens.
            @endif
        </p>
        @php $poErr = old('po_batch') === $first->batch_id ? $errors : new \Illuminate\Support\ViewErrorBag; @endphp
        <div class="qc-po-grid">
            <div class="form-group">
                <label>PO Number</label>
                <input type="text" name="po_number" maxlength="80" required placeholder="e.g. PO-2026-0142"
                       value="{{ old('po_batch') === $first->batch_id ? old('po_number') : ($hasPo ? $quotationBatch->po_number : '') }}">
                @if($poErr->has('po_number'))<span class="qc-po-error">{{ $poErr->first('po_number') }}</span>@endif
            </div>
            <div class="form-group">
                <label>Note <span style="font-weight:600;color:var(--muted);">(optional)</span></label>
                <input type="text" name="po_notes" maxlength="1000" placeholder="Anything GMD should know"
                       value="{{ old('po_batch') === $first->batch_id ? old('po_notes') : '' }}">
            </div>
        </div>
        <div>
            <label class="qc-po-drop">
                <input type="file" name="po_files[]" multiple required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" data-po-files>
                <span class="qc-po-drop-icon"><i data-lucide="upload"></i></span>
                <span style="min-width:0;">
                    <strong data-po-files-title>Attach your signed Purchase Order</strong>
                    <small data-po-files-hint>PDF, JPG or PNG · up to 5 files, 10 MB each</small>
                </span>
            </label>
            @if($poErr->has('po_files') || $poErr->has('po_files.*'))
            <span class="qc-po-error">{{ $poErr->first('po_files') ?: $poErr->first('po_files.*') }}</span>
            @endif
        </div>
        <div class="qc-po-actions">
            <button type="submit" class="save-btn" data-po-submit>
                <i data-lucide="send"></i>
                {{ $hasPo ? 'Send Updated PO' : 'Send Purchase Order' }}
            </button>
            @if($hasPo)
            <button type="button" class="cancel-btn" @click="editing = false">Cancel</button>
            @endif
        </div>
    </form>
    @endif
    </div>
    @once
    <script>
        // Show the picked file names in the drop box, and a spinner while the PO uploads
        document.addEventListener('change', function (e) {
            var input = e.target.closest ? e.target.closest('[data-po-files]') : null;
            if (!input) return;
            var box = input.closest('.qc-po-drop'), files = Array.prototype.slice.call(input.files || []);
            box.classList.toggle('has-files', files.length > 0);
            box.querySelector('[data-po-files-title]').textContent = files.length
                ? (files.length === 1 ? '1 file selected' : files.length + ' files selected')
                : 'Attach your signed Purchase Order';
            box.querySelector('[data-po-files-hint]').textContent = files.length
                ? files.map(function (f) { return f.name; }).join(', ')
                : 'PDF, JPG or PNG · up to 5 files, 10 MB each';
        });
        document.addEventListener('submit', function (e) {
            var form = e.target.closest ? e.target.closest('[data-po-form]') : null;
            if (!form) return;
            var btn = form.querySelector('[data-po-submit]');
            if (btn) { btn.disabled = true; btn.innerHTML = '<span class="qc-po-spinner" aria-hidden="true"></span> Sending…'; }
        });
    </script>
    @endonce
    @endif

    @if($first->notes)
    <div class="qc-section">
        <i data-lucide="sticky-note"></i> Your notes
    </div>
    <div class="qc-note">{{ $first->notes }}</div>
    @endif
</div>
