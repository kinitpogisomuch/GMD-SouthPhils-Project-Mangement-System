{{-- Double-submit guard for every form on the page (included by each portal's header).
     A form posted the normal way gets a one-time token (checked by PreventDuplicateSubmission) and its
     submit buttons are disabled with a spinner, so a double click can't send it twice. Forms a page sends
     by script (its own handler calls preventDefault) are left alone. Opt out with data-no-submit-guard. --}}
<style>
    .is-submitting { position: relative; cursor: progress !important; opacity: .8; }
    .is-submitting::before {
        content: ''; display: inline-block; width: 13px; height: 13px; margin-right: 7px; vertical-align: -2px;
        border: 2px solid currentColor; border-right-color: transparent; border-radius: 50%;
        animation: submitGuardSpin .7s linear infinite;
    }
    @keyframes submitGuardSpin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) { .is-submitting::before { animation-duration: 2s; } }
</style>
<script>
(function () {
    if (window.__submitGuard) return;
    window.__submitGuard = true;

    // If nothing happened after this long (no new page), the buttons come back so the form can be retried.
    // The retry keeps the same token: if the first request did reach the server, the retry is refused.
    var RETRY_AFTER_MS = 20000;

    function newToken() {
        if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
        return 't' + Date.now().toString(36) + Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2);
    }
    function guarded(form) {
        return form instanceof HTMLFormElement
            && (form.getAttribute('method') || 'get').toLowerCase() === 'post'
            && !form.hasAttribute('data-no-submit-guard');
    }
    function submitButtons(form) {
        var list = Array.prototype.slice.call(form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]'));
        if (form.id) {
            list = list.concat(Array.prototype.slice.call(document.querySelectorAll('[form="' + form.id + '"]')));
        }
        return list;
    }
    function release(form) {
        delete form.dataset.submitting;
        submitButtons(form).forEach(function (b) {
            if (b.dataset.guardDisabled) { b.disabled = false; delete b.dataset.guardDisabled; }
            b.classList.remove('is-submitting');
        });
    }

    // Capture phase: a second submit of a form that is already on its way is stopped before the page's
    // own handlers run at all.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (guarded(form) && form.dataset.submitting === '1') {
            e.preventDefault();
            e.stopPropagation();
        }
    }, true);

    // Bubble phase: runs after the page's own handlers — if one of them stopped the submit (its own
    // validation, a confirmation step, or sending by script), nothing is done here.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!guarded(form) || e.defaultPrevented) return;

        form.dataset.submitting = '1';
        var token = form.querySelector('input[name="_submission_token"]');
        if (!token) {
            token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_submission_token';
            form.appendChild(token);
        }
        if (!token.value) token.value = newToken();

        var submitter = e.submitter;
        // Disabled a moment later: the browser has already collected the form's values by then
        // (a button disabled before that would drop its own name/value from the request).
        setTimeout(function () {
            submitButtons(form).forEach(function (b) {
                if (!b.disabled) { b.disabled = true; b.dataset.guardDisabled = '1'; }
            });
            // Buttons that show their own "Saving…" state keep it; the rest get a small spinner
            if (submitter && submitter.dataset.guardDisabled && !submitter.querySelector('.btn-spinner')) {
                submitter.classList.add('is-submitting');
            }
        }, 0);
        setTimeout(function () { if (document.body.contains(form)) release(form); }, RETRY_AFTER_MS);
    });

    // Coming back to the page with the browser's Back button starts fresh: buttons enabled, new tokens.
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        document.querySelectorAll('form[data-submitting]').forEach(release);
        document.querySelectorAll('input[name="_submission_token"]').forEach(function (t) { t.value = ''; });
    });
})();
</script>
