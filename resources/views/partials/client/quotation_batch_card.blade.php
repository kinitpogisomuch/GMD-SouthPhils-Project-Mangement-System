{{-- Renders one submitted quotation batch (a client's visit to the request form) as ONE
     combined quotation — all tanks in the batch share one status, one price, one decision.
     Expects: $batch — a Collection of QuotationRequest models sharing a batch_id. --}}
@php
    $statusMeta = [
        'pending'        => ['label' => 'Under Review', 'icon' => 'clock', 'bg' => '#FFF3D6', 'color' => '#8A6100'],
        'quotation_sent' => ['label' => 'Quotation Ready for Review', 'icon' => 'file-text', 'bg' => '#EAF0FF', 'color' => '#1e40af'],
        'approved'       => ['label' => 'Quotation Approved', 'icon' => 'thumbs-up', 'bg' => '#dcfce7', 'color' => '#16a34a'],
        'converted'      => ['label' => 'Accepted — Project Created', 'icon' => 'check-circle-2', 'bg' => '#dcfce7', 'color' => '#16a34a'],
        'declined'       => ['label' => 'Not Approved', 'icon' => 'x-circle', 'bg' => '#fee2e2', 'color' => '#dc2626'],
    ];
    $first  = $batch->first();
    $status = $first->status;
    $meta   = $statusMeta[$status] ?? $statusMeta['pending'];
    $specifiedCount = $batch->filter(fn ($qr) => $qr->tank_type)->count();
    $quotationBatch = $first->quotationBatch;
@endphp
<div class="pv-card">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--border);">
        <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--muted);font-weight:700;">
            <i data-lucide="calendar" style="width:14px;height:14px;"></i>
            Submitted {{ $first->created_at->format('M d, Y') }}
        </div>
        <div style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:800;color:{{ $meta['color'] }};background:{{ $meta['bg'] }};border-radius:8px;padding:4px 11px;text-transform:uppercase;letter-spacing:.03em;">
            <i data-lucide="{{ $meta['icon'] }}" style="width:12px;height:12px;"></i>
            {{ $meta['label'] }}
        </div>
    </div>

    @if($status !== 'declined')
    @php
        $isRevision = $status === 'pending' && !empty($first->decline_reason);
        $steps      = ['Submitted', $isRevision ? 'Revising' : 'Under review', 'Quotation ready', 'Approved', 'Project created'];
        $current    = ['pending' => 1, 'quotation_sent' => 2, 'approved' => 3, 'converted' => 4][$status] ?? 1;
        $finished   = $status === 'converted';   // every step is complete
        $hint = [
            'pending'        => $isRevision
                ? ['icon' => 'rotate-ccw', 'tone' => '', 'text' => 'Your requested changes were sent. GMD South Phils is preparing an updated quotation for you.']
                : ['icon' => 'clock', 'tone' => '', 'text' => 'GMD South Phils is reviewing your request and preparing your quotation. We will let you know as soon as it is ready.'],
            'quotation_sent' => ['icon' => 'bell-ring', 'tone' => 'is-action', 'text' => 'Your quotation is ready. Review it below, then approve it or ask for changes.'],
            'approved'       => ['icon' => 'thumbs-up', 'tone' => 'is-good', 'text' => 'You approved this quotation. GMD South Phils will turn it into a project shortly.'],
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
    <style>
        /* Progress tracker + what-happens-next message */
        .qc-track { list-style: none; display: flex; align-items: flex-start; margin: 0 0 12px; padding: 0; }
        .qc-track li { flex: 1; min-width: 0; display: flex; flex-direction: column; align-items: center; gap: 6px; position: relative; text-align: center; }
        .qc-track li::before { content: ''; position: absolute; top: 13px; right: 50%; width: 100%; height: 2px; background: var(--border); z-index: 0; }
        .qc-track li:first-child::before { display: none; }
        .qc-track li.is-done::before, .qc-track li.is-active::before { background: #16a34a; }
        .qc-dot { position: relative; z-index: 1; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11.5px; font-weight: 900; background: #fff; border: 2px solid var(--border); color: var(--muted); }
        .qc-dot i, .qc-dot svg { width: 14px; height: 14px; }
        .qc-track li.is-done .qc-dot { background: #16a34a; border-color: #16a34a; color: #fff; }
        .qc-track li.is-active .qc-dot { background: var(--dark); border-color: var(--dark); color: #fff; box-shadow: 0 0 0 4px rgba(0,0,0,.08); }
        .qc-step-label { font-size: 11px; font-weight: 700; color: var(--muted); line-height: 1.3; padding: 0 2px; }
        .qc-track li.is-done .qc-step-label, .qc-track li.is-active .qc-step-label { color: var(--dark); font-weight: 800; }
        .qc-hint { display: flex; align-items: flex-start; gap: 9px; margin-bottom: 4px; padding: 11px 14px; border-radius: 12px; font-size: 12.5px; font-weight: 600; line-height: 1.5; background: var(--cream-soft); border: 1px solid var(--border); color: var(--dark); }
        .qc-hint i, .qc-hint svg { width: 15px; height: 15px; flex-shrink: 0; margin-top: 1px; color: var(--muted); }
        .qc-hint.is-action { background: #EAF0FF; border-color: #c7d7fe; color: #1e40af; }
        .qc-hint.is-action i, .qc-hint.is-action svg { color: #1e40af; }
        .qc-hint.is-good { background: #E7F6EC; border-color: #86efac; color: #14532d; }
        .qc-hint.is-good i, .qc-hint.is-good svg { color: #16a34a; }
        .qc-note { background: var(--cream-soft); border: 1px solid var(--border); border-radius: 12px; padding: 12px 14px; font-size: 13px; line-height: 1.6; color: var(--dark); overflow-wrap: anywhere; white-space: pre-line; }
        .qc-decision { display: flex; flex-wrap: wrap; gap: 10px; }
        @media (max-width: 560px) { .qc-step-label { font-size: 10px; } }

        /* Each part of the card starts with a labelled divider so it is clear what belongs to what */
        .qc-section { display: flex; align-items: center; gap: 12px; margin: 22px 0 12px; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; color: var(--dark); }
        .qc-section i, .qc-section svg { width: 14px; height: 14px; color: var(--muted); flex-shrink: 0; }
        .qc-section::after { content: ''; flex: 1; height: 1px; background: var(--border); }
        .qc-section small { font-size: 11px; font-weight: 700; letter-spacing: 0; text-transform: none; color: var(--muted); }
        .qc-tanks { display: flex; flex-direction: column; gap: 12px; margin-bottom: 4px; }
        .qc-tank { background: var(--cream-soft); border: 1px solid var(--border); border-radius: 16px; padding: 14px 18px; display: flex; flex-direction: column; gap: 12px; }
        .qc-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .qc-title { display: inline-flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 900; color: var(--dark); }
        .qc-title i, .qc-title svg { width: 16px; height: 16px; color: var(--muted); }
        .qc-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 800; border-radius: 999px; padding: 5px 12px; background: #fff; border: 1px solid var(--border); color: var(--dark); }
        .qc-pill.is-delivery { background: var(--dark); border-color: var(--dark); color: #fff; }
        .qc-pill i, .qc-pill svg { width: 13px; height: 13px; }
        .qc-facts { display: flex; flex-wrap: wrap; gap: 12px 44px; }
        .qc-fact { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
        .qc-fact span { font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); }
        .qc-fact strong { font-size: 13.5px; font-weight: 800; color: var(--dark); line-height: 1.4; overflow-wrap: anywhere; }
        .qc-fact-wide { flex: 1 1 100%; }
        .qc-fact-wide strong { font-weight: 700; }
        .qc-files { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding-top: 10px; border-top: 1px dashed var(--border); }
        .qc-label { font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin-right: 2px; }
        .qc-file { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; color: var(--dark); text-decoration: none; background: #fff; border: 1px solid var(--border); border-radius: 999px; padding: 6px 13px; }
        .qc-file:hover { border-color: var(--dark); }
        .qc-file i, .qc-file svg { width: 13px; height: 13px; color: var(--muted); }
        .qc-quote { margin-bottom: 16px; padding: 14px 18px; border-radius: 16px; background: #EAF0FF; border: 1px solid #c7d7fe; display: flex; flex-direction: column; gap: 10px; }
        .qc-quote .qc-label { color: #1e40af; }
        .qc-quote .qc-file { border-color: #c7d7fe; }
    </style>
    @endonce

    <div class="qc-section">
        <i data-lucide="clipboard-list"></i> Your request
        <small>{{ $batch->count() }} {{ $batch->count() === 1 ? 'tank' : 'tanks' }}</small>
    </div>

    <div class="qc-tanks">
        @foreach($batch as $qr)
        @php
            $isPickup = ($qr->fulfillment ?? 'delivery') === 'pickup';
            $files    = (!$legacyShared && !empty($qr->reference_files)) ? $qr->reference_files : [];
        @endphp
        <div class="qc-tank">
            <div class="qc-head">
                <span class="qc-title"><i data-lucide="package"></i> {{ $qr->tank_type ?: 'Tank' }}</span>
                <span class="qc-pill {{ $isPickup ? 'is-pickup' : 'is-delivery' }}">
                    <i data-lucide="{{ $isPickup ? 'package-check' : 'truck' }}"></i>
                    {{ $isPickup ? 'For pick-up' : 'For delivery' }}
                </span>
            </div>

            <div class="qc-facts">
                <div class="qc-fact"><span>Capacity / Size</span><strong>{{ $qr->capacity ?: '—' }}</strong></div>
                <div class="qc-fact"><span>Quantity</span><strong>{{ $qr->quantity }}</strong></div>
                <div class="qc-fact"><span>Target Delivery</span><strong>{{ !empty($qr->target_timeline) ? $qr->target_timeline_display : '—' }}</strong></div>
                @unless($isPickup)
                <div class="qc-fact qc-fact-wide"><span>Delivery Address</span><strong>{{ $qr->location ?: '—' }}</strong></div>
                @endunless
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

    @if(!empty($quotationBatch?->quotation_files))
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
    @if($status === 'declined' && $first->decline_reason)
    <div style="margin-bottom:16px;padding:10px 12px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;font-size:12.5px;color:#dc2626;display:flex;align-items:flex-start;gap:8px;">
        <i data-lucide="info" style="width:14px;height:14px;flex-shrink:0;margin-top:1px;"></i>
        <span style="flex:1;min-width:0;overflow-wrap:anywhere;word-break:break-word;"><strong>Reason:</strong> {{ $first->decline_reason }}</span>
    </div>
    @elseif($status === 'pending' && $first->decline_reason)
    <div style="margin-bottom:16px;padding:10px 12px;background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;font-size:12.5px;color:#9a3412;display:flex;align-items:flex-start;gap:8px;">
        <i data-lucide="rotate-ccw" style="width:14px;height:14px;flex-shrink:0;margin-top:1px;"></i>
        <span style="flex:1;min-width:0;overflow-wrap:anywhere;word-break:break-word;"><strong>Your requested changes:</strong> {{ $first->decline_reason }}</span>
    </div>
    @endif

    @if($status === 'quotation_sent')
    <div class="qc-section">
        <i data-lucide="gavel"></i> Your decision
    </div>
    <div class="qc-decision">
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
                    <p>GMD South Phils will be notified and can convert this into a project.</p>
                </div>
                <button class="modal-close" type="button" onclick="closeModal('approveQuotationModal-{{ $first->batch_id }}')">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('client.quotation.approve', $first->batch_id) }}">
                @csrf
                <div class="form-grid" style="margin-bottom:18px;">
                    <div class="form-group">
                        <label>Approved Date <span style="font-size:11px;color:var(--muted);font-weight:400;">(optional — leave blank for today)</span></label>
                        <input type="date" name="approved_date">
                    </div>
                    <div class="form-group">
                        <label>Approved Time <span style="font-size:11px;color:var(--muted);font-weight:400;">(optional)</span></label>
                        <input type="time" name="approved_time">
                    </div>
                </div>
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
            <form method="POST" action="{{ route('client.quotation.reject', $first->batch_id) }}">
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

    @if($first->notes)
    <div class="qc-section">
        <i data-lucide="sticky-note"></i> Your notes
    </div>
    <div class="qc-note">{{ $first->notes }}</div>
    @endif
</div>
