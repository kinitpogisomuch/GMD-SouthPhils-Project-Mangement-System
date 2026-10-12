{{-- "Exceeds Total Received" modal — shared by Log New Purchase, Salary Records and Monthly Expenses.
     Open it with FundOverrun.open(payload, { onUse: function () { ... } }) where payload is
     ProjectFunds::overrunPayload(): { projects: [{project, client, amount, available, exceeded}], total, fund_balance, fund_enough }.
     The page passes what "Use Revolving Fund" should do (normally: resend the same save with use_fund=1). --}}
<div class="modal-overlay" id="fundOverrunModal" role="dialog" aria-modal="true" aria-labelledby="fundOverrunTitle" style="z-index:1300;">
    <div class="modal-card" style="max-width:520px;width:95%;">
        <div class="modal-header">
            <div>
                <h2 id="fundOverrunTitle">Exceeds Total Received</h2>
                <p id="fundOverrunSub">This costs more than the money received so far.</p>
            </div>
            <button class="modal-close" type="button" data-fo-close aria-label="Close"><i data-lucide="x"></i></button>
        </div>

        <div id="fundOverrunRows" class="fo-rows"></div>

        <div class="fo-total">
            <span>Exceeded by</span>
            <strong id="fundOverrunTotal">₱0</strong>
        </div>

        <div class="fo-fund" id="fundOverrunFund"></div>

        <div class="modal-actions">
            <button type="button" class="cancel-btn" data-fo-close>Cancel</button>
            <button type="button" class="save-btn" id="fundOverrunUse">
                <i data-lucide="wallet"></i> <span>Use Revolving Fund</span>
            </button>
        </div>
    </div>
</div>
<style>
    .fo-rows { border: 1px solid var(--border); border-radius: 14px; overflow: hidden; margin-bottom: 12px; }
    .fo-row { padding: 12px 14px; border-bottom: 1px solid var(--border); }
    .fo-row:last-child { border-bottom: none; }
    .fo-row-name { font-size: 13.5px; font-weight: 800; color: var(--dark); }
    .fo-row-client { font-size: 11.5px; color: var(--muted); }
    .fo-row-figs { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin-top: 8px; }
    .fo-fig span { display: block; font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
    .fo-fig strong { font-size: 13.5px; font-weight: 900; color: var(--dark); font-variant-numeric: tabular-nums; }
    .fo-fig.is-over strong { color: var(--danger); }
    .fo-total { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; border-radius: 12px; background: #FEE4E2; color: #B42318; margin-bottom: 12px; }
    .fo-total span { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; }
    .fo-total strong { font-size: 20px; font-weight: 900; font-variant-numeric: tabular-nums; }
    .fo-fund { font-size: 12.5px; line-height: 1.6; color: var(--muted); margin-bottom: 4px; }
    .fo-fund strong { color: var(--dark); }
    .fo-fund.is-short { color: #B42318; }
    .fo-fund.is-short strong { color: #B42318; }
    #fundOverrunUse:disabled { opacity: .45; cursor: not-allowed; }
    @media (max-width: 520px) { .fo-row-figs { grid-template-columns: 1fr 1fr; } }
</style>
<script>
window.FundOverrun = (function () {
    var modal = document.getElementById('fundOverrunModal');
    var useBtn = document.getElementById('fundOverrunUse');
    var onUse = null;

    function peso(n) { return '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

    function close() {
        modal.classList.remove('show');
        // only unlock the page if no other dialog (e.g. Log New Purchase) is still open underneath
        if (!document.querySelector('.modal-overlay.show')) document.body.style.overflow = '';
    }

    function open(payload, opts) {
        opts = opts || {};
        onUse = opts.onUse || null;
        var many = payload.projects.length > 1;
        document.getElementById('fundOverrunSub').textContent = many
            ? 'These projects would spend more than the money received from their clients.'
            : 'This costs more than the money received from the client so far.';
        document.getElementById('fundOverrunRows').innerHTML = payload.projects.map(function (p) {
            return '<div class="fo-row">' +
                '<div class="fo-row-name">' + esc(p.project) + '</div>' +
                (p.client ? '<div class="fo-row-client">' + esc(p.client) + '</div>' : '') +
                '<div class="fo-row-figs">' +
                    '<div class="fo-fig"><span>' + esc(opts.amountLabel || 'This expense') + '</span><strong>' + peso(p.amount) + '</strong></div>' +
                    '<div class="fo-fig"><span>Available</span><strong>' + peso(p.available) + '</strong></div>' +
                    '<div class="fo-fig is-over"><span>Exceeded by</span><strong>' + peso(p.exceeded) + '</strong></div>' +
                '</div>' +
            '</div>';
        }).join('');
        document.getElementById('fundOverrunTotal').textContent = peso(payload.total);

        var fund = document.getElementById('fundOverrunFund');
        fund.classList.toggle('is-short', !payload.fund_enough);
        fund.innerHTML = payload.fund_enough
            ? 'The revolving fund has <strong>' + peso(payload.fund_balance) + '</strong>. Using it records a release of <strong>' + peso(payload.total) + '</strong>' +
              (many ? ' (one per project)' : '') + ' on the Revolving Fund page, paid back automatically from the client\'s next payments.'
            : 'The revolving fund only has <strong>' + peso(payload.fund_balance) + '</strong>, not enough to cover <strong>' + peso(payload.total) + '</strong>. Top up the fund on the Revolving Fund page, or change the amounts.';
        useBtn.disabled = !payload.fund_enough || !onUse;
        useBtn.querySelector('span').textContent = 'Use Revolving Fund (' + peso(payload.total) + ')';

        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        if (typeof lucide !== 'undefined') lucide.createIcons();
        (payload.fund_enough ? useBtn : modal.querySelector('[data-fo-close]')).focus();
    }

    modal.querySelectorAll('[data-fo-close]').forEach(function (b) { b.addEventListener('click', close); });
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal.classList.contains('show')) close(); });
    useBtn.addEventListener('click', function () {
        if (useBtn.disabled || !onUse) return;
        useBtn.disabled = true;
        useBtn.querySelector('span').textContent = 'Saving…';
        onUse();
    });

    return { open: open, close: close, peso: peso };
})();
</script>
