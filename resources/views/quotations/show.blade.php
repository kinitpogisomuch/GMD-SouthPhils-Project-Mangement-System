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
    <link href="{{ asset('css/billing_statement.css') }}" rel="stylesheet">
    <style>
        .qv-wrap { max-width: 900px; margin: 0 auto; padding: 0 16px 40px; }
        .qv-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 16px 0; }
        .qv-toolbar a.qv-back { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; color: #444; text-decoration: none; }
        .qv-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .qv-btn { display: inline-flex; align-items: center; gap: 7px; border-radius: 10px; padding: 10px 16px; font-size: 13px; font-weight: 800; cursor: pointer; text-decoration: none; border: 1px solid #1a1a1a; background: #1a1a1a; color: #fff; font-family: inherit; }
        .qv-btn.ghost { background: #fff; color: #1a1a1a; border-color: #ccc; }
        .qv-btn.good { background: #207A3A; border-color: #207A3A; }
        .qv-btn.bad { background: #fff; color: #B42318; border-color: #f3b4ad; }
        .qv-btn svg, .qv-btn i { width: 15px; height: 15px; }
        .qv-btn[disabled] { opacity: .6; cursor: progress; }
        .qv-panel { background: #fff; border: 1px solid #ddd; border-radius: 14px; padding: 14px 16px; margin-bottom: 14px; }
        .qv-panel h2 { margin: 0 0 4px; font-size: 15px; }
        .qv-panel p { margin: 0; font-size: 12.5px; color: #555; line-height: 1.55; }
        .qv-decision { border-color: #c7d7fe; background: #f5f8ff; }
        .qv-decision .qv-actions { margin-top: 12px; }
        .qv-versions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
        .qv-version { display: inline-flex; align-items: center; gap: 6px; border: 1px solid #ddd; border-radius: 999px; padding: 5px 12px; font-size: 12px; font-weight: 700; color: #333; text-decoration: none; background: #fff; }
        .qv-version.is-current { border-color: #1a1a1a; background: #1a1a1a; color: #fff; }
        .qv-pill { font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; padding: 2px 7px; border-radius: 999px; background: #f3f4f6; color: #6b7280; }
        .qv-pill.ongoing { background: #EAF0FF; color: #2A4EAA; }
        .qv-pill.completed { background: #E7F6EC; color: #207A3A; }
        .qv-pill.pending { background: #FFF3D6; color: #8A6100; }
        .qv-pill.revision { background: #FEE4E2; color: #B42318; }
        .qv-version.is-current .qv-pill { background: rgba(255,255,255,.18); color: #fff; }
        .qv-notice { padding: 10px 14px; border-radius: 12px; font-size: 12.5px; font-weight: 600; margin-bottom: 14px; }
        .qv-notice.ok { background: #E7F6EC; color: #14532d; border: 1px solid #86efac; }
        .qv-notice.err { background: #FEE4E2; color: #B42318; border: 1px solid #fca5a5; }
        .qv-sheet-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 4px; }
        .qv-sheet { min-width: 640px; background: #fff; border: 1px solid #ccc; padding: 30px 36px; box-shadow: 0 10px 30px rgba(0,0,0,.12); }
        /* decision dialogs */
        .qv-modal { position: fixed; inset: 0; background: rgba(0,0,0,.5); display: none; align-items: center; justify-content: center; padding: 16px; z-index: 50; }
        .qv-modal.show { display: flex; animation: qvFade .15s ease; }
        .qv-modal-card { position: relative; background: #fff; border-radius: 22px; max-width: 440px; width: 100%; padding: 26px 24px 22px; text-align: center;
                         box-shadow: 0 24px 60px rgba(0,0,0,.25); animation: qvPop .18s ease; }
        @keyframes qvFade { from { opacity: 0; } }
        @keyframes qvPop { from { transform: translateY(8px) scale(.97); opacity: 0; } }
        .qv-modal-x { position: absolute; top: 14px; right: 14px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; padding: 0;
                      border: 1px solid #e0e0e0; border-radius: 10px; background: #fff; color: #1a1a1a; cursor: pointer; }
        .qv-modal-x:hover { background: #f3f3f3; }
        .qv-modal-x svg { width: 15px; height: 15px; }
        .qv-confirm-icon { width: 56px; height: 56px; margin: 0 auto 12px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #f1f1f1; color: #1a1a1a; }
        .qv-confirm-icon svg { width: 26px; height: 26px; }
        .qv-confirm-icon.good { background: #E7F6EC; color: #207A3A; }
        .qv-confirm-icon.bad  { background: #FEE4E2; color: #B42318; }
        .qv-modal-card h3 { margin: 0 0 6px; font-size: 18px; font-weight: 900; color: #1a1a1a; }
        .qv-modal-card p { margin: 0 0 14px; font-size: 13px; color: #555; line-height: 1.55; }
        .qv-confirm-sum { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 0 0 4px; }
        .qv-confirm-sum div { padding: 10px 12px; border: 1px solid #e0e0e0; border-radius: 12px; background: #fafafa; text-align: left; }
        .qv-confirm-sum span { display: block; font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #777; }
        .qv-confirm-sum strong { display: block; margin-top: 2px; font-size: 14px; color: #1a1a1a; }
        .qv-field-label { display: block; text-align: left; margin: 0 0 6px; font-size: 12.5px; font-weight: 800; color: #1a1a1a; }
        .qv-field-label span { color: #dc2626; }
        .qv-field-label em { font-weight: 600; color: #777; }
        .qv-modal-card textarea { width: 100%; border: 1px solid #ccc; border-radius: 12px; padding: 10px 12px; font-family: inherit; font-size: 13px; resize: vertical; min-height: 90px; }
        .qv-modal-card textarea:focus { outline: none; border-color: #1a1a1a; }
        .qv-modal-card .qv-actions { justify-content: center; margin-top: 16px; }
        .qv-modal-card .qv-actions .qv-btn { flex: 1 1 0; justify-content: center; border-radius: 999px; padding: 11px 16px; }
        .qv-btn.qv-btn-danger { background: #B42318; color: #fff; border-color: #B42318; }
        @media (max-width: 640px) {
            .qv-sheet { padding: 18px; }
            .qv-actions .qv-btn { flex: 1 1 auto; justify-content: center; }
        }
        @media print {
            .qv-toolbar, .qv-panel, .qv-notice, .qv-modal { display: none !important; }
            .qv-wrap { max-width: none; padding: 0; }
            .qv-sheet { border: none; box-shadow: none; padding: 0; min-width: 0; }
            .qd-annex { page-break-before: always; border-top: none !important; }
            @page { size: A4; margin: 10mm; }
        }
    </style>
</head>
<body>
    @include('partials.submit_guard')

    {{-- ?embed=1: shown inside the in-page viewer (client's quotation card) — laid out like the admin's Review & Send
         modal: a header, the document scrolling in between, and the actions in a bar along the bottom. No "Back"; the
         viewer has its own close button, and a decision reloads the whole page (target="_top") so the client lands
         back on their card --}}
    @php $embedded = request()->boolean('embed'); @endphp
    @if($embedded)
    <style>
        html, body { height: 100%; margin: 0; }
        body { background: #fff; font-family: 'Inter', Arial, Helvetica, sans-serif; }
        .qv-m { display: flex; flex-direction: column; height: 100%; }
        .qv-mhead { display: flex; align-items: flex-start; gap: 12px; padding: 20px 24px 14px; border-bottom: 1px solid #e0e0e0; }
        .qv-mhead-icon { width: 38px; height: 38px; border-radius: 11px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #333; color: #F5B400; }
        .qv-mhead-icon svg { width: 18px; height: 18px; }
        .qv-mhead h1 { margin: 0; font-size: 20px; font-weight: 900; color: #1a1a1a; letter-spacing: -.2px; }
        .qv-mhead p { margin: 3px 0 0; font-size: 13px; color: #666; line-height: 1.5; }
        .qv-mhead p strong { color: #1a1a1a; }
        .qv-mhead .qv-pill { margin-left: auto; align-self: center; white-space: nowrap; }
        .qv-mclose { align-self: center; flex-shrink: 0; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; padding: 0;
                     border: 1px solid #e0e0e0; border-radius: 12px; background: #fff; color: #1a1a1a; cursor: pointer; transition: background .15s ease, border-color .15s ease; }
        .qv-mclose:hover { background: #f3f3f3; border-color: #ccc; }
        .qv-mclose svg { width: 17px; height: 17px; }
        .qv-pill + .qv-mclose { margin-left: 4px; }
        .qv-mhead > div + .qv-mclose { margin-left: auto; }   /* no tag beside it: pushed to the right on its own */
        .qv-mbody { flex: 1; min-height: 0; overflow-y: auto; padding: 16px 24px; background: #f3f3f3; }
        .qv-mbody .qv-sheet { box-shadow: 0 1px 3px rgba(0,0,0,.08); border-color: #e0e0e0; }
        .qv-mfoot { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 14px 24px; border-top: 1px solid #e0e0e0; background: #fff; }
        .qv-mfoot .qv-btn { border-radius: 999px; padding: 11px 18px; }
        .qv-mfoot-hint { font-size: 12px; color: #666; }
        @media (max-width: 640px) {
            .qv-mhead { padding: 16px 16px 12px; }
            .qv-mhead .qv-pill { display: none; }
            .qv-mbody { padding: 12px; }
            .qv-mfoot { padding: 12px 16px; }
            .qv-mfoot > .qv-actions { width: 100%; }
        }
        @media print { .qv-mhead, .qv-mfoot { display: none !important; } .qv-mbody { overflow: visible; padding: 0; background: #fff; } }
    </style>
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
