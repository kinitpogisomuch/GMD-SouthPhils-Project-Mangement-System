{{-- File lightbox — shows an uploaded file (payment receipt, quotation drawing, …; image or PDF) over the page,
     in place, instead of a new tab. (Named for its first use, payment receipts.) Same look as the project page's photo lightbox: dark backdrop, the file centred,
     a round close button. Include once per page. Any link with `data-receipt` inside a wrapper
     carrying `data-receipt-set` opens here; a payment with several files gets side arrows.
     The href stays a normal URL, so ctrl/middle-click still opens a new tab. --}}

<div class="rv-overlay" id="receiptViewer" role="dialog" aria-modal="true" aria-label="File preview">
    <button type="button" class="rv-round rv-close" id="receiptViewerClose" aria-label="Close">&times;</button>
    <button type="button" class="rv-round rv-prev" id="receiptViewerPrev" aria-label="Previous file" style="display:none;">&#8249;</button>
    <div class="rv-stage" id="receiptViewerStage"></div>
    <button type="button" class="rv-round rv-next" id="receiptViewerNext" aria-label="Next file" style="display:none;">&#8250;</button>
    <div class="rv-count" id="receiptViewerCount" style="display:none;"></div>
</div>

<script>
(function () {
    var overlay = document.getElementById('receiptViewer');
    var stage   = document.getElementById('receiptViewerStage');
    var prevBtn = document.getElementById('receiptViewerPrev');
    var nextBtn = document.getElementById('receiptViewerNext');
    var count   = document.getElementById('receiptViewerCount');
    var urls = [], pages = [], ownClose = [], index = 0;

    function kind(url) {
        // a system-generated document: data-receipt="page" (landscape, e.g. an Acknowledgement Receipt)
        // or data-receipt="doc" (portrait, e.g. a Billing Statement)
        if (pages[index]) return 'page';
        // blob: URLs (local, not-yet-uploaded file previews) carry no extension — in every
        // place that hands this viewer a blob: URL it's an image picked via accept="image/*".
        if (/^blob:/i.test(String(url))) return 'image';
        var path = String(url).split('?')[0].split('#')[0].toLowerCase();
        if (/\.(png|jpe?g|gif|webp|bmp|svg)$/.test(path)) return 'image';
        if (/\.pdf$/.test(path)) return 'pdf';
        return 'other';
    }

    function render() {
        var url = urls[index];
        var k = kind(url);
        stage.innerHTML = '';
        // a page with its own close button (link marked data-own-close, e.g. the quotation review) hides the floating one
        document.getElementById('receiptViewerClose').style.display = ownClose[index] ? 'none' : '';

        if (k === 'image') {
            var img = document.createElement('img');
            img.src = url; img.alt = 'Attached file';
            stage.appendChild(img);
        } else if (k === 'pdf') {
            var frame = document.createElement('iframe');
            frame.src = url; frame.title = 'Attached file';
            stage.appendChild(frame);
        } else if (k === 'page') {
            var page = document.createElement('iframe');
            page.src = url + (url.indexOf('?') === -1 ? '?' : '&') + 'embed=1';
            page.title = 'Document'; page.className = pages[index] === 'doc' ? 'rv-doc' : 'rv-page';
            stage.appendChild(page);
        } else {
            var note = document.createElement('div');
            note.className = 'rv-note';
            note.textContent = 'This file type can’t be previewed here.';
            var link = document.createElement('a');
            link.href = url; link.target = '_blank'; link.rel = 'noopener';
            link.textContent = 'Open in new tab';
            note.appendChild(document.createElement('br'));
            note.appendChild(link);
            stage.appendChild(note);
        }

        var multiple = urls.length > 1;
        prevBtn.style.display = nextBtn.style.display = count.style.display = multiple ? '' : 'none';
        prevBtn.disabled = index === 0;
        nextBtn.disabled = index === urls.length - 1;
        count.textContent = (index + 1) + ' / ' + urls.length;
    }

    function open(list, start, pageFlags, closeFlags) {
        urls = list; pages = pageFlags || []; ownClose = closeFlags || []; index = start;
        render();
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function close() {
        overlay.classList.remove('show');
        stage.innerHTML = '';           // stops any PDF / image loading
        document.body.style.overflow = '';
    }

    function step(delta) {
        var next = index + delta;
        if (next < 0 || next > urls.length - 1) return;
        index = next;
        render();
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[data-receipt]');
        if (!link) return;
        // let ctrl/cmd/shift/middle-click still open a new tab
        if (e.ctrlKey || e.metaKey || e.shiftKey || e.button === 1) return;
        e.preventDefault();

        var set = link.closest('[data-receipt-set]');
        var links = set ? Array.prototype.slice.call(set.querySelectorAll('a[data-receipt]')) : [link];
        open(links.map(function (a) { return a.getAttribute('href'); }), Math.max(0, links.indexOf(link)),
             links.map(function (a) { var t = a.getAttribute('data-receipt'); return (t === 'page' || t === 'doc') ? t : false; }),
             links.map(function (a) { return a.hasAttribute('data-own-close'); }));
    });

    document.getElementById('receiptViewerClose').addEventListener('click', close);
    // a page shown in the viewer that has its own × (the quotation review): it asks to close, and hides the floating ×
    window.addEventListener('message', function (e) {
        var frame = stage.querySelector('iframe');
        if (!frame || e.source !== frame.contentWindow || !e.data) return;   // only the page currently shown
        if (e.data.rvClose) close();
        if (e.data.rvOwnClose) document.getElementById('receiptViewerClose').style.display = 'none';
    });
    prevBtn.addEventListener('click', function (e) { e.stopPropagation(); step(-1); });
    nextBtn.addEventListener('click', function (e) { e.stopPropagation(); step(1); });
    // clicking the dark backdrop (not the file itself) closes it
    overlay.addEventListener('click', function (e) { if (e.target === overlay || e.target === stage) close(); });
    document.addEventListener('keydown', function (e) {
        if (!overlay.classList.contains('show')) return;
        if (e.key === 'Escape') close();
        else if (e.key === 'ArrowLeft') step(-1);
        else if (e.key === 'ArrowRight') step(1);
    });
})();
</script>
