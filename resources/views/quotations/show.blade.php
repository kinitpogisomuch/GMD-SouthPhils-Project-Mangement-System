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
        .qv-modal { position: fixed; inset: 0; background: rgba(0,0,0,.45); display: none; align-items: center; justify-content: center; padding: 16px; z-index: 50; }
        .qv-modal.show { display: flex; }
        .qv-modal-card { background: #fff; border-radius: 16px; max-width: 440px; width: 100%; padding: 20px; }
        .qv-modal-card h3 { margin: 0 0 6px; font-size: 16px; }
        .qv-modal-card p { margin: 0 0 12px; font-size: 13px; color: #555; line-height: 1.5; }
        .qv-modal-card textarea { width: 100%; border: 1px solid #ccc; border-radius: 10px; padding: 10px; font-family: inherit; font-size: 13px; resize: vertical; min-height: 84px; }
        .qv-modal-card .qv-actions { justify-content: flex-end; margin-top: 14px; }
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

        @if(session('success'))
        <div class="qv-notice ok" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="qv-notice err" role="alert">{{ session('error') }}</div>
        @endif
        @if($errors->any())
        <div class="qv-notice err" role="alert">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        @if(!empty($decision))
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
                <a href="{{ $v['url'] }}" class="qv-version {{ $v['current'] ? 'is-current' : '' }}">
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
    </div>

    @if(!empty($decision))
    <div class="qv-modal" id="qvApprove" role="dialog" aria-modal="true" aria-labelledby="qvApproveTitle">
        <div class="qv-modal-card">
            <h3 id="qvApproveTitle">Approve this quotation?</h3>
            <p>You are approving <strong>{{ $doc['quotation_number'] }}</strong> for <strong>₱{{ number_format($doc['totals']['total'], 2) }}</strong>. GMD South Phils will be notified and will set up your project and payment schedule.</p>
            <form method="POST" action="{{ $decision['approve_url'] }}">
                @csrf
                <input type="hidden" name="version_id" value="{{ $decision['version_id'] }}">
                <div class="qv-actions">
                    <button type="button" class="qv-btn ghost" onclick="qvClose('qvApprove')">Cancel</button>
                    <button type="submit" class="qv-btn good"><i data-lucide="thumbs-up"></i> Yes, approve</button>
                </div>
            </form>
        </div>
    </div>
    <div class="qv-modal" id="qvRevision" role="dialog" aria-modal="true" aria-labelledby="qvRevisionTitle">
        <div class="qv-modal-card">
            <h3 id="qvRevisionTitle">Request a revision</h3>
            <p>Tell GMD what you would like changed. They will prepare an updated quotation; this version stays on record.</p>
            <form method="POST" action="{{ $decision['revision_url'] }}">
                @csrf
                <input type="hidden" name="version_id" value="{{ $decision['version_id'] }}">
                <textarea name="reason" required maxlength="1000" placeholder="e.g. Please quote a 4.5 mm shell instead, or adjust the delivery date…"></textarea>
                <div class="qv-actions">
                    <button type="button" class="qv-btn ghost" onclick="qvClose('qvRevision')">Cancel</button>
                    <button type="submit" class="qv-btn"><i data-lucide="send"></i> Send request</button>
                </div>
            </form>
        </div>
    </div>
    <div class="qv-modal" id="qvReject" role="dialog" aria-modal="true" aria-labelledby="qvRejectTitle">
        <div class="qv-modal-card">
            <h3 id="qvRejectTitle">Reject this quotation?</h3>
            <p>This closes the request — GMD will not prepare another quotation for it. If you only want changes, choose <strong>Request Revision</strong> instead.</p>
            <form method="POST" action="{{ $decision['reject_url'] }}">
                @csrf
                <input type="hidden" name="version_id" value="{{ $decision['version_id'] }}">
                <textarea name="reason" maxlength="1000" placeholder="Reason (optional)"></textarea>
                <div class="qv-actions">
                    <button type="button" class="qv-btn ghost" onclick="qvClose('qvReject')">Cancel</button>
                    <button type="submit" class="qv-btn bad" style="background:#B42318;color:#fff;border-color:#B42318;"><i data-lucide="x-circle"></i> Reject quotation</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
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
