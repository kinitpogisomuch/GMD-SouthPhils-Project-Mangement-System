{{-- Every quotation sent for this request (revisions keep the earlier versions) + who did what.
     Costing step: two across below the builder. Quotation Document step: stacked under the preview. --}}
@php
    // icon + colour per kind of activity
    $qhTone = [
        'quotation.requested'          => ['inbox',            'blue'],
        'quotation.in_preparation'     => ['calculator',       'muted'],
        'quotation.saved'              => ['save',             'muted'],
        'quotation.sent'               => ['send',             'amber'],
        'quotation.approved'           => ['check-circle-2',   'green'],
        'quotation.revision_requested' => ['message-square-warning', 'orange'],
        'quotation.rejected'           => ['x-circle',         'red'],
        'quotation.declined'           => ['x-circle',         'red'],
        'quotation.converted'          => ['folder-plus',      'green'],
        'quotation.saved_as_standard'  => ['bookmark',         'blue'],
    ];
@endphp
<style>
    .qh-card { padding: 18px 20px; }
    .qh-head { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .qh-icon { width: 30px; height: 30px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: #333; color: #F5B400; }
    .qh-icon svg { width: 15px; height: 15px; }
    .qh-title { font-size: 13.5px; font-weight: 800; color: var(--dark); }
    .qh-count { margin-left: auto; min-width: 22px; height: 22px; padding: 0 7px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
                background: var(--cream-soft); border: 1px solid var(--border); font-size: 11px; font-weight: 800; color: var(--muted); }

    .qh-empty { display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px dashed var(--border); border-radius: 14px; background: var(--cream-soft); }
    .qh-empty-icon { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--white); border: 1px solid var(--border); color: var(--muted); }
    .qh-empty-icon svg { width: 16px; height: 16px; }
    .qh-empty p { margin: 0; font-size: 12.5px; line-height: 1.55; color: var(--muted); }
    .qh-empty strong { color: var(--dark); }

    .qh-versions { display: flex; flex-direction: column; gap: 8px; }
    .qh-version { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 14px; background: var(--white); transition: border-color .15s ease; }
    .qh-version:hover { border-color: var(--border-2, #d0d0d0); }
    .qh-version.is-latest { border-color: var(--dark); }
    .qh-version-main { flex: 1; min-width: 0; }
    .qh-version-no { font-size: 13px; font-weight: 900; color: var(--dark); text-decoration: none; }
    .qh-version-no:hover { text-decoration: underline; }
    .qh-version-meta { margin-top: 2px; font-size: 11.5px; color: var(--muted); }
    .qh-version-amount { font-weight: 800; color: var(--dark); }
    .qh-version-reason { margin-top: 6px; padding: 6px 9px; border-radius: 8px; background: #fffbeb; color: #92400e; font-size: 11.5px; font-style: italic; overflow-wrap: anywhere; }
    .qh-version-side { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

    .qh-timeline { position: relative; max-height: 340px; overflow-y: auto; padding-right: 4px; }
    .qh-event { position: relative; display: flex; gap: 12px; padding-bottom: 14px; }
    .qh-event:last-child { padding-bottom: 0; }
    .qh-event:not(:last-child)::before { content: ''; position: absolute; left: 13px; top: 28px; bottom: 2px; width: 2px; background: var(--border); }   /* the line joining the dots */
    .qh-dot { position: relative; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .qh-dot svg { width: 13px; height: 13px; }
    .qh-dot.muted  { background: #f1f1f1; color: #555; }
    .qh-dot.blue   { background: #e0edff; color: #1d4ed8; }
    .qh-dot.amber  { background: #fff4d6; color: #8a6100; }
    .qh-dot.green  { background: #dcfce7; color: #15803d; }
    .qh-dot.orange { background: #ffedd5; color: #c2410c; }
    .qh-dot.red    { background: #fee2e2; color: #b91c1c; }
    .qh-event-body { flex: 1; min-width: 0; padding-top: 4px; }
    .qh-event-top { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; }
    .qh-event-label { font-size: 12.5px; font-weight: 800; color: var(--dark); }
    .qh-event-ref { font-weight: 700; color: var(--muted); }
    .qh-event-when { font-size: 11px; font-weight: 600; color: var(--muted); white-space: nowrap; }
    .qh-event-detail { margin-top: 2px; font-size: 12px; color: var(--muted); overflow-wrap: anywhere; }
    .qh-event-by { margin-top: 3px; display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; color: var(--muted); }
    .qh-event-by svg { width: 11px; height: 11px; }
    @media (max-width: 600px) {
        .qh-version { flex-wrap: wrap; }
        .qh-version-side { width: 100%; justify-content: space-between; }
    }
</style>

{{-- Quotation Versions --}}
<div class="table-card qh-card">
    <div class="qh-head">
        <span class="qh-icon"><i data-lucide="files"></i></span>
        <span class="qh-title">Quotation Versions</span>
        <span class="qh-count">{{ $versions->count() }}</span>
    </div>
    @if($versions->isEmpty())
    <div class="qh-empty">
        <span class="qh-empty-icon"><i data-lucide="file-clock"></i></span>
        <p>Nothing sent yet. When you press <strong>Send to Client</strong>, the system generates the quotation document from this builder and it appears here.</p>
    </div>
    @else
    <div class="qh-versions">
        @foreach($versions as $v)
        <div class="qh-version {{ $loop->first ? 'is-latest' : '' }}">
            <div class="qh-version-main">
                <a class="qh-version-no" href="{{ route('admin.quotation_requests.version', [$batch->id, $v->version]) }}">{{ $v->quotation_number }}</a>
                <div class="qh-version-meta">
                    <span class="qh-version-amount">₱{{ number_format($v->contract_value, 2) }}</span>
                    · Sent {{ $v->sent_at?->format('M d, Y g:i A') }}{{ $v->sent_by ? ' by ' . $v->sent_by : '' }}
                </div>
                @if($v->response_reason)
                <div class="qh-version-reason">“{{ $v->response_reason }}”</div>
                @endif
            </div>
            <div class="qh-version-side">
                <span class="status-badge {{ $v->badgeClass() }}">{{ $v->statusLabel() }}</span>
                <a class="action-btn view" href="{{ route('admin.quotation_requests.version_pdf', [$batch->id, $v->version]) }}" title="Download PDF" aria-label="Download {{ $v->quotation_number }} PDF"><i data-lucide="download"></i></a>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- Activity — newest first, as a timeline --}}
<div class="table-card qh-card">
    <div class="qh-head">
        <span class="qh-icon"><i data-lucide="history"></i></span>
        <span class="qh-title">Activity</span>
        <span class="qh-count">{{ $activity->count() }}</span>
    </div>
    @if($activity->isEmpty())
    <div class="qh-empty">
        <span class="qh-empty-icon"><i data-lucide="activity"></i></span>
        <p>No activity recorded yet.</p>
    </div>
    @else
    <div class="qh-timeline">
        @foreach($activity as $log)
        @php [$icon, $tone] = $qhTone[$log->action] ?? ['circle-dot', 'muted']; @endphp
        <div class="qh-event">
            <span class="qh-dot {{ $tone }}"><i data-lucide="{{ $icon }}"></i></span>
            <div class="qh-event-body">
                <div class="qh-event-top">
                    <span class="qh-event-label">
                        {{ $log->label }}
                        @if(!empty($log->details['quotation_number'])) <span class="qh-event-ref">· {{ $log->details['quotation_number'] }}</span> @endif
                    </span>
                    <span class="qh-event-when" title="{{ $log->created_at?->format('M d, Y g:i A') }}">{{ $log->created_at?->diffForHumans() }}</span>
                </div>
                @if(!empty($log->details['changes']))<div class="qh-event-detail">{{ $log->details['changes'] }}</div>@endif
                @if(!empty($log->details['reason']))<div class="qh-event-detail">“{{ $log->details['reason'] }}”</div>@endif
                <div class="qh-event-by">
                    <i data-lucide="user-round"></i>{{ $log->actor_name ?: 'System' }} · {{ $log->created_at?->format('M d, Y g:i A') }}
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
