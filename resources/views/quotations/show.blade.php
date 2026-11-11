{{-- The generated quotation on screen — shared by the admin portal (draft preview / sent versions) and the
     client portal (review, download, approve or reject).
     Expects: $doc (snapshot), $backUrl, $backLabel, $pdfUrl (nullable), $versions (collection of
     ['number','label','status','badge','url','current']), and for the client's decision: $decision
     (null or ['version_id','approve_url','revision_url','reject_url']). --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation {{ $doc['quotation_number'] }} | GMD South Phils</title>
    <link href="{{ asset('css/billing_statement.css') }}?v={{ @filemtime(public_path('css/billing_statement.css')) }}" rel="stylesheet">
    {{-- @page can't be scoped to one page inside a shared stylesheet, so it stays here --}}
    <style>@media print { @page { size: A4; margin: 10mm; } }</style>
</head>
<body class="pg-quotations-show">
    @include('partials.submit_guard')

    {{-- ?embed=1: shown inside the in-page viewer (client's quotation card) — laid out like the admin's Review & Send
         modal: a header, the document scrolling in between, and the actions in a bar along the bottom. No "Back"; the
         viewer has its own close button, and a decision reloads the whole page (target="_top") so the client lands
         back on their card --}}
    @php $embedded = request()->boolean('embed'); @endphp
    @if($embedded)
    <div class="qv-m">
        <header class="qv-mhead">
            <span class="qv-mhead-icon"><i data-lucide="file-search"></i></span>
            <div>
                <h1>{{ !empty($decision) ? 'Review Quotation' : 'Quotation ' . $doc['quotation_number'] }}</h1>
                <p>
                    <strong>{{ $doc['quotation_number'] }}</strong> · Total <strong>₱{{ number_format($doc['totals']['total'], 2) }}</strong>
                    @if(!empty($doc['valid_until'])) · Valid until {{ \Carbon\Carbon::parse($doc['valid_until'])->format('F j, Y') }}@endif
                </p>
            </div>
            @if(!empty($decision))<span class="qv-pill pending">Awaiting your decision</span>@endif
            {{-- asks the viewer this page is shown in to close (a message works whatever the frame's address) --}}
            <button type="button" class="qv-mclose" aria-label="Close" title="Close"
                    onclick="if (parent !== window) { parent.postMessage({ rvClose: true }, '*'); } else { history.back(); }">
                <i data-lucide="x"></i>
            </button>
        </header>
        <main class="qv-mbody">
    @else
    <div class="qv-wrap">
        <div class="qv-toolbar">
            <a class="qv-back" href="{{ $backUrl }}"><i data-lucide="arrow-left" style="width:15px;height:15px;"></i> {{ $backLabel }}</a>
            <div class="qv-actions">
                @if($pdfUrl)
                <a class="qv-btn" href="{{ $pdfUrl }}"><i data-lucide="download"></i> Download PDF</a>
                @endif
                <button type="button" class="qv-btn ghost" onclick="window.print()"><i data-lucide="printer"></i> Print</button>
            </div>
        </div>
    @endif

        @if(session('success'))
        <div class="qv-notice ok" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="qv-notice err" role="alert">{{ session('error') }}</div>
        @endif
        @if($errors->any())
        <div class="qv-notice err" role="alert">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        @if(!empty($decision) && !$embedded)
        <div class="qv-panel qv-decision">
            <h2>Your decision</h2>
            <p>Please review quotation <strong>{{ $doc['quotation_number'] }}</strong> — total <strong>₱{{ number_format($doc['totals']['total'], 2) }}</strong>, valid until {{ \Carbon\Carbon::parse($doc['valid_until'])->format('F j, Y') }}. Approve it to let GMD start your project, ask for changes, or reject it.</p>
            <div class="qv-actions">
                <button type="button" class="qv-btn good" onclick="qvOpen('qvApprove')"><i data-lucide="thumbs-up"></i> Approve Quotation</button>
                <button type="button" class="qv-btn ghost" onclick="qvOpen('qvRevision')"><i data-lucide="edit-3"></i> Request Revision</button>
                <button type="button" class="qv-btn bad" onclick="qvOpen('qvReject')"><i data-lucide="x-circle"></i> Reject</button>
            </div>
        </div>
        @endif

        @if(isset($versions) && $versions->count() > 1)
        <div class="qv-panel">
            <h2>Quotation versions</h2>
            <p>Each revision is kept. The newest one is the quotation in effect; earlier versions are for reference only.</p>
            <div class="qv-versions">
                @foreach($versions as $v)
                <a href="{{ $v['url'] }}{{ $embedded ? (str_contains($v['url'], '?') ? '&' : '?') . 'embed=1' : '' }}" class="qv-version {{ $v['current'] ? 'is-current' : '' }}">
                    {{ $v['number'] }} <span class="qv-pill {{ $v['badge'] }}">{{ $v['label'] }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <div class="qv-sheet-scroll">
            <div class="qv-sheet">
                @include('partials.quotation_document', ['doc' => $doc])
            </div>
        </div>
    @if($embedded)
        </main>
        {{-- the actions, like the admin modal's bottom bar: the file on the left, the decision on the right --}}
        <footer class="qv-mfoot">
            <div class="qv-actions">
                @if($pdfUrl)
                <a class="qv-btn ghost" href="{{ $pdfUrl }}"><i data-lucide="download"></i> Download PDF</a>
                @endif
                <button type="button" class="qv-btn ghost" onclick="window.print()"><i data-lucide="printer"></i> Print</button>
            </div>
            @if(!empty($decision))
            <div class="qv-actions">
                <button type="button" class="qv-btn bad" onclick="qvOpen('qvReject')"><i data-lucide="x-circle"></i> Reject</button>
                <button type="button" class="qv-btn ghost" onclick="qvOpen('qvRevision')"><i data-lucide="edit-3"></i> Request Revision</button>
                <button type="button" class="qv-btn good" onclick="qvOpen('qvApprove')"><i data-lucide="thumbs-up"></i> Approve Quotation</button>
            </div>
            @else
            <span class="qv-mfoot-hint">For reference — no decision needed on this version.</span>
            @endif
        </footer>
    </div>
    @else
    </div>
    @endif

    @if(!empty($decision))
    {{-- "Are you sure?" before each decision — nothing is sent until it's confirmed here --}}
    <div class="qv-modal" id="qvApprove" role="dialog" aria-modal="true" aria-labelledby="qvApproveTitle">
        <div class="qv-modal-card">
            <button type="button" class="qv-modal-x" onclick="qvClose('qvApprove')" aria-label="Close"><i data-lucide="x"></i></button>
            <div class="qv-confirm-icon good"><i data-lucide="thumbs-up"></i></div>
            <h3 id="qvApproveTitle">Are you sure you want to approve?</h3>
            <p>You're approving this quotation. Next, you'll send GMD South Phils your Purchase Order — they set up your project and payment schedule once they receive it.</p>
            <div class="qv-confirm-sum">
                <div><span>Quotation</span><strong>{{ $doc['quotation_number'] }}</strong></div>
                <div><span>Total</span><strong>₱{{ number_format($doc['totals']['total'], 2) }}</strong></div>
            </div>
            <form method="POST" action="{{ $decision['approve_url'] }}" @if($embedded) target="_top" @endif>
                @csrf
                <input type="hidden" name="version_id" value="{{ $decision['version_id'] }}">
                <div class="qv-actions">
                    <button type="button" class="qv-btn ghost" onclick="qvClose('qvApprove')">Cancel</button>
                    <button type="submit" class="qv-btn good"><i data-lucide="check"></i> Yes, approve</button>
                </div>
            </form>
        </div>
    </div>
    <div class="qv-modal" id="qvRevision" role="dialog" aria-modal="true" aria-labelledby="qvRevisionTitle">
        <div class="qv-modal-card">
            <button type="button" class="qv-modal-x" onclick="qvClose('qvRevision')" aria-label="Close"><i data-lucide="x"></i></button>
            <div class="qv-confirm-icon"><i data-lucide="edit-3"></i></div>
            <h3 id="qvRevisionTitle">Request a revision?</h3>
            <p>Tell GMD what you would like changed. They'll prepare an updated quotation — this version stays on record.</p>
            <form method="POST" action="{{ $decision['revision_url'] }}" @if($embedded) target="_top" @endif>
                @csrf
                <input type="hidden" name="version_id" value="{{ $decision['version_id'] }}">
                <label class="qv-field-label" for="qvRevisionReason">What should change? <span>*</span></label>
                <textarea id="qvRevisionReason" name="reason" required maxlength="1000" placeholder="e.g. Please quote a 4.5 mm shell instead, or adjust the pick-up date…"></textarea>
                <div class="qv-actions">
                    <button type="button" class="qv-btn ghost" onclick="qvClose('qvRevision')">Cancel</button>
                    <button type="submit" class="qv-btn"><i data-lucide="send"></i> Yes, send request</button>
                </div>
            </form>
        </div>
    </div>
    <div class="qv-modal" id="qvReject" role="dialog" aria-modal="true" aria-labelledby="qvRejectTitle">
        <div class="qv-modal-card">
            <button type="button" class="qv-modal-x" onclick="qvClose('qvReject')" aria-label="Close"><i data-lucide="x"></i></button>
            <div class="qv-confirm-icon bad"><i data-lucide="x-circle"></i></div>
            <h3 id="qvRejectTitle">Are you sure you want to reject?</h3>
            <p>This closes the request — GMD won't prepare another quotation for it. If you only want changes, choose <strong>Request Revision</strong> instead.</p>
            <form method="POST" action="{{ $decision['reject_url'] }}" @if($embedded) target="_top" @endif>
                @csrf
                <input type="hidden" name="version_id" value="{{ $decision['version_id'] }}">
                <label class="qv-field-label" for="qvRejectReason">Reason <em>(optional)</em></label>
                <textarea id="qvRejectReason" name="reason" maxlength="1000" placeholder="Let GMD know why…"></textarea>
                <div class="qv-actions">
                    <button type="button" class="qv-btn ghost" onclick="qvClose('qvReject')">Cancel</button>
                    <button type="submit" class="qv-btn bad qv-btn-danger"><i data-lucide="x-circle"></i> Yes, reject</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
        @if($embedded)
        // this page has its own × in the header — the viewer hides its floating one (also set on the opening link, data-own-close)
        if (parent !== window) parent.postMessage({ rvOwnClose: true }, '*');
        @endif
        function qvOpen(id) { var m = document.getElementById(id); if (m) { m.classList.add('show'); var t = m.querySelector('textarea'); if (t) t.focus(); } }
        function qvClose(id) { var m = document.getElementById(id); if (m) m.classList.remove('show'); }
        document.querySelectorAll('.qv-modal').forEach(function (m) {
            m.addEventListener('click', function (e) { if (e.target === m) m.classList.remove('show'); });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') document.querySelectorAll('.qv-modal.show').forEach(function (m) { m.classList.remove('show'); });
        });
    </script>
</body>
</html>
