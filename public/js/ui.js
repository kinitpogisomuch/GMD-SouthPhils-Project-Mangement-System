/*
 * GMD South Phils — shared UI enhancements for the admin, client and employee portals.
 * Loaded (deferred) by resources/views/partials/ui_assets.blade.php, after GSAP and Lottie.
 *
 *  - GSAP: page entrance (header, stat cards, main cards), tab-switch transitions, number count-ups
 *  - Lottie: the success toast's check icon becomes an animated check
 *  - Chart.js: one consistent look for every chart (fonts, tooltips, rounded bars, animation)
 *
 * Every effect is optional: if a library didn't load, or the person prefers reduced motion, the page
 * simply shows as it always did.
 */
(function () {
    'use strict';

    var root   = document.documentElement;
    var config = window.GMD_UI || {};
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var gsap   = window.gsap;

    function reveal() { root.classList.remove('ui-preload'); }

    /* ---------------------------------------------------------------- page entrance (GSAP) */
    function enterPage() {
        var selector = [
            '.admin-content > .page-header',
            '.admin-content .stats-grid > *',
            '.admin-content .fd-overview',
            '.admin-content > .table-card',
            '.admin-content > .pv-card',
            '.admin-content > .card'
        ].join(',');
        var targets = Array.prototype.slice.call(document.querySelectorAll(selector));

        if (!gsap || reduce || !targets.length) { reveal(); return; }

        gsap.set(targets, { opacity: 0, y: 14 });
        reveal();
        gsap.to(targets, {
            opacity: 1, y: 0, duration: 0.55, ease: 'power3.out',
            stagger: { each: 0.05, from: 'start' },
            clearProps: 'opacity,transform'
        });
    }

    /* ---------------------------------------------------------------- tab switches */
    // Any ".emp-tab-content" that becomes active fades its content up, wherever the tab code lives.
    function animateTabs() {
        if (!gsap || reduce || !('MutationObserver' in window)) return;
        var panes = document.querySelectorAll('.emp-tab-content');
        if (!panes.length) return;

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                var el = m.target;
                var wasActive = (m.oldValue || '').split(/\s+/).indexOf('active') !== -1;
                if (el.classList.contains('active') && !wasActive) {
                    gsap.fromTo(el.children, { opacity: 0, y: 10 },
                        { opacity: 1, y: 0, duration: 0.4, ease: 'power2.out', stagger: 0.04, clearProps: 'opacity,transform' });
                }
            });
        });
        panes.forEach(function (pane) {
            observer.observe(pane, { attributes: true, attributeFilter: ['class'], attributeOldValue: true });
        });
    }

    /* ---------------------------------------------------------------- number count-ups */
    // "₱1,234,567", "82.5%", "12 Days" … counted up from zero, keeping the exact formatting.
    // only an optional peso sign before the number; anything else (dates, labels) is left alone
    var NUMBER = /^(\s*(?:₱|PHP)?\s*)(-?\d[\d,]*(?:\.\d+)?)(\s*(?:%|[KkMm]\b|days?\b|Days?\b|tanks?\b)?[^\d]*)$/;

    function countUp() {
        if (!gsap || reduce) return;
        var els = document.querySelectorAll('.stat-value, .fd-ov-val, .pss-value, [data-count-up]');

        els.forEach(function (el) {
            var node = el.firstChild;
            if (!node || node.nodeType !== 3) return;
            var match = node.nodeValue.match(NUMBER);
            if (!match) return;

            var raw      = match[2];
            var target   = parseFloat(raw.replace(/,/g, ''));
            if (!isFinite(target) || target === 0) return;
            var decimals = (raw.split('.')[1] || '').length;
            var grouped  = raw.indexOf(',') !== -1 || Math.abs(target) >= 10000;
            var counter  = { v: 0 };

            function render() {
                var fixed = counter.v.toFixed(decimals);
                if (grouped) {
                    var parts = fixed.split('.');
                    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    fixed = parts.join('.');
                }
                node.nodeValue = match[1] + fixed + match[3];
            }

            render();
            gsap.to(counter, {
                v: target, duration: 1.1, ease: 'power2.out', delay: 0.15,
                onUpdate: render,
                onComplete: function () { node.nodeValue = match[0]; }   // exact original text at the end
            });
        });
    }

    /* ---------------------------------------------------------------- success toasts (Lottie) */
    var CHECK_ICON = 'svg.lucide-check-circle, svg.lucide-circle-check, svg.lucide-circle-check-big, i[data-lucide="check-circle"]';

    function playCheck(toast) {
        if (!window.lottie || !config.successAnimation || toast.dataset.uiLottie) return;
        var icon = toast.querySelector(CHECK_ICON);
        if (!icon) return;
        toast.dataset.uiLottie = '1';

        var holder = document.createElement('span');
        holder.className = 'ui-lottie-icon';
        holder.setAttribute('aria-hidden', 'true');
        icon.replaceWith(holder);

        window.lottie.loadAnimation({
            container: holder, renderer: 'svg', loop: false, autoplay: true, path: config.successAnimation
        });
    }

    function successToasts() {
        if (reduce || !window.lottie) return;
        document.querySelectorAll('.toast').forEach(function (toast) {
            if (toast.classList.contains('show')) { playCheck(toast); return; }
            var observer = new MutationObserver(function () {
                if (toast.classList.contains('show')) { playCheck(toast); observer.disconnect(); }
            });
            observer.observe(toast, { attributes: true, attributeFilter: ['class'] });
        });
    }

    /* ---------------------------------------------------------------- Chart.js look */
    function styleCharts() {
        var Chart = window.Chart;
        if (!Chart || !Chart.defaults) return;
        var d = Chart.defaults;

        d.font.family = "'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif";
        d.color = '#666666';
        d.borderColor = 'rgba(0,0,0,0.06)';
        // tweak the animation defaults in place — replacing the object drops Chart.js's own settings
        if (reduce) {
            d.animation = false;
        } else if (d.animation) {
            d.animation.duration = 900;
            d.animation.easing = 'easeOutQuart';
        }

        var tip = d.plugins.tooltip;
        tip.backgroundColor = 'rgba(26,26,26,0.94)';
        tip.titleColor = '#ffffff';
        tip.bodyColor = '#e5e5e5';
        tip.titleFont = { weight: '800', size: 12.5 };
        tip.bodyFont = { size: 12.5 };
        tip.padding = 12;
        tip.cornerRadius = 10;
        tip.boxPadding = 5;
        tip.caretSize = 6;

        var legend = d.plugins.legend.labels;
        legend.usePointStyle = true;
        legend.pointStyle = 'circle';
        legend.boxWidth = 8;
        legend.padding = 16;

        d.elements.bar.borderRadius = 6;
        d.elements.bar.borderSkipped = false;
        d.elements.point.hoverRadius = 6;
        d.elements.point.hoverBorderWidth = 3;

        // charts drawn before this ran pick the new defaults up on their next update
        Object.values(Chart.instances || {}).forEach(function (chart) {
            if (chart.canvas) chart.canvas.classList.add('ui-chart-ready');
            try { chart.update(); } catch (e) { /* a chart in the middle of being destroyed */ }
        });
    }

    /* ---------------------------------------------------------------- start */
    function start() {
        try { styleCharts(); } catch (e) { /* never block the page */ }
        try { enterPage(); } catch (e) { reveal(); }
        try { animateTabs(); } catch (e) {}
        try { countUp(); } catch (e) {}
        try { successToasts(); } catch (e) {}
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
    // toasts are often shown on window "load"; catch those too
    window.addEventListener('load', function () { try { successToasts(); } catch (e) {} });
})();
