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

    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
        @foreach($batch as $qr)
            @if($qr->tank_type)
            <span class="qr-spec-chip qr-chip-type">
                <i data-lucide="package" style="width:11px;height:11px;"></i>
                {{ $qr->tank_type }}{{ $qr->quantity > 1 ? ' ×' . $qr->quantity : '' }}
                @if(!empty($qr->capacity)) &middot; {{ $qr->capacity }} @endif
            </span>
            @else
            <span class="qr-spec-chip qr-chip-type">
                <i data-lucide="image" style="width:11px;height:11px;"></i>
                Your Own Tank Design
            </span>
            @endif
        @endforeach
    </div>

    @if(!empty($first->reference_files))
    <div style="margin-bottom:16px;padding-top:12px;border-top:1px dashed var(--border);">
        <div style="font-size:10.5px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">
            Your Attached Files
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            @foreach($first->reference_files as $i => $file)
            <a href="{{ $file }}" target="_blank" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:var(--accent);text-decoration:none;background:#fff;border:1px solid var(--border);border-radius:20px;padding:6px 13px;">
                <i data-lucide="paperclip" style="width:12px;height:12px;"></i>
                Attachment {{ $i + 1 }}
            </a>
            @endforeach
        </div>
    </div>
    @endif

    @if(!empty($quotationBatch?->quotation_files))
    <div style="margin-bottom:16px;">
        <div style="font-size:10.5px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">
            Attached Drawing / Reference
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            @foreach($quotationBatch->quotation_files as $i => $file)
            <a href="{{ $file }}" target="_blank" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:var(--accent);text-decoration:none;background:#fff;border:1px solid var(--border);border-radius:20px;padding:6px 13px;">
                <i data-lucide="file-text" style="width:12px;height:12px;"></i>
                File {{ $i + 1 }}
            </a>
            @endforeach
        </div>
    </div>
    @endif

    @if($status === 'declined' && $first->decline_reason)
    <div style="margin-bottom:16px;padding:10px 12px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;font-size:12.5px;color:#dc2626;display:flex;align-items:flex-start;gap:8px;">
        <i data-lucide="info" style="width:14px;height:14px;flex-shrink:0;margin-top:1px;"></i>
        <span><strong>Reason:</strong> {{ $first->decline_reason }}</span>
    </div>
    @elseif($status === 'pending' && $first->decline_reason)
    <div style="margin-bottom:16px;padding:10px 12px;background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;font-size:12.5px;color:#9a3412;display:flex;align-items:flex-start;gap:8px;">
        <i data-lucide="rotate-ccw" style="width:14px;height:14px;flex-shrink:0;margin-top:1px;"></i>
        <span><strong>Your requested changes:</strong> {{ $first->decline_reason }}</span>
    </div>
    @endif

    @if($status === 'quotation_sent')
    <div style="display:flex;gap:10px;">
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

    <div class="form-grid" style="margin-top:18px;">
        <div class="form-group form-group-full">
            <label>Project / Delivery Location</label>
            <textarea disabled rows="2">{{ $first->location }}</textarea>
        </div>
        @if($first->notes)
        <div class="form-group form-group-full">
            <label>Additional Notes</label>
            <textarea disabled rows="2">{{ $first->notes }}</textarea>
        </div>
        @endif
    </div>
</div>
