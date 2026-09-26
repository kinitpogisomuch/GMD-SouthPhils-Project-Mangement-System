{{-- File lightbox — shows an uploaded file (payment receipt, quotation drawing, …; image or PDF) over the page,
     in place, instead of a new tab. (Named for its first use, payment receipts.) Same look as the project page's photo lightbox: dark backdrop, the file centred,
     a round close button. Include once per page. Any link with `data-receipt` inside a wrapper
     carrying `data-receipt-set` opens here; a payment with several files gets side arrows.
     The href stays a normal URL, so ctrl/middle-click still opens a new tab. --}}
<style>
    .rv-overlay { position: fixed; inset: 0; z-index: 1000; display: none; align-items: center; justify-content: center; padding: 40px; background: rgba(0,0,0,.8); }
    .rv-overlay.show { display: flex; }
    .rv-stage { display: flex; align-items: center; justify-content: center; max-width: 100%; max-height: 100%; }
    .rv-stage img { display: block; max-width: 90vw; max-height: 90vh; border-radius: 8px; box-shadow: 0 24px 60px rgba(0,0,0,.4); }
    .rv-stage iframe { width: min(900px, 90vw); height: 90vh; border: 0; border-radius: 8px; background: #fff; box-shadow: 0 24px 60px rgba(0,0,0,.4); }
    .rv-note { max-width: 360px; padding: 28px 24px; border-radius: 12px; background: #fff; text-align: center; font-size: 14px; line-height: 1.6; color: #444; }
    .rv-note a { display: inline-block; margin-top: 10px; font-weight: 700; color: #1a1a1a; }
    .rv-round { position: absolute; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border: none; border-radius: 50%; background: rgba(255,255,255,.12); color: #fff; font-size: 20px; line-height: 1; cursor: pointer; }
    .rv-round:hover { background: rgba(255,255,255,.22); }
    .rv-round[disabled] { opacity: .3; cursor: default; }
    .rv-close { top: 24px; right: 28px; }
    .rv-prev  { left: 24px;  top: 50%; transform: translateY(-50%); }
    .rv-next  { right: 24px; top: 50%; transform: translateY(-50%); }
    .rv-count { position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); padding: 5px 12px; border-radius: 999px; background: rgba(255,255,255,.14); color: #fff; font-size: 12px; font-weight: 700; }
    @media (max-width: 600px) { .rv-overlay { padding: 16px; } .rv-close { top: 12px; right: 12px; } .rv-prev { left: 8px; } .rv-next { right: 8px; } }
</style>

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
    var urls = [], index = 0;

    function kind(url) {
        var path = String(url).split('?')[0].split('#')[0].toLowerCase();
        if (/\.(png|jpe?g|gif|webp|bmp|svg)$/.test(path)) return 'image';
        if (/\.pdf$/.test(path)) return 'pdf';
        return 'other';
    }

    function render() {
        var url = urls[index];
        var k = kind(url);
        stage.innerHTML = '';

        if (k === 'image') {
            var img = document.createElement('img');
            img.src = url; img.alt = 'Attached file';
            stage.appendChild(img);
        } else if (k === 'pdf') {
            var frame = document.createElement('iframe');
            frame.src = url; frame.title = 'Attached file';
            stage.appendChild(frame);
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

    function open(list, start) {
        urls = list; index = start;
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
        open(links.map(function (a) { return a.getAttribute('href'); }), Math.max(0, links.indexOf(link)));
    });

    document.getElementById('receiptViewerClose').addEventListener('click', close);
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
