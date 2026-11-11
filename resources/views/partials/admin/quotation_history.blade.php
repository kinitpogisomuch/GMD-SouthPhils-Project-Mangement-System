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
