/*
 * Landing page motion (GSAP + ScrollTrigger). Loaded deferred by landing.blade.php.
 *
 *  - Hero: the headline builds in and the gold underline sweeps under "Precision."
 *  - Scroll reveals: every .reveal / .reveal-left / .reveal-right / .reveal-scale element, in batches
 *  - Stats bar: numbers count up as it scrolls into view
 *  - Tank cards: 3D tilt toward the cursor; hero buttons lean toward the cursor (mouse devices only)
 *
 * Without GSAP (or with reduced motion) the page's original CSS reveals take over unchanged.
 */
(function () {
    'use strict';

    var gsap   = window.gsap;
    var ST     = window.ScrollTrigger;
    var root   = document.documentElement;
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

    function reveal() { root.classList.remove('ui-preload'); }

    if (!gsap || !ST || reduce) {
        root.classList.remove('ui-gsap');   // hand the reveals back to the CSS version
        reveal();
        return;
    }
    gsap.registerPlugin(ST);

    /* ------------------------------------------------------------ hero */
    function hero() {
        var content = document.querySelectorAll('.lp-hero .hero-content > *');
        var title   = document.querySelector('.lp-hero h1');
        var gold    = document.querySelector('.lp-hero h1 .gold');

        var tl = gsap.timeline({ defaults: { ease: 'power3.out' } });

        gsap.set(content, { opacity: 0, y: 24 });
        reveal();
        tl.to(content, { opacity: 1, y: 0, duration: 0.9, stagger: 0.12, clearProps: 'transform' }, 0.15);

        if (gold) {
            gold.style.setProperty('--ui-underline', 0);
            tl.to(gold, { '--ui-underline': 1, duration: 0.8, ease: 'power2.inOut' }, 0.85);
        }
        if (title) {
            // the headline settles in with a slight letter-spacing ease
            tl.from(title, { letterSpacing: '0px', duration: 1.1, ease: 'power2.out' }, 0.15);
        }
    }

    /* ------------------------------------------------------------ scroll reveals */
    function reveals() {
        var kinds = [
            { sel: '.reveal',       from: { opacity: 0, y: 40 } },
            { sel: '.reveal-left',  from: { opacity: 0, x: -50 } },
            { sel: '.reveal-right', from: { opacity: 0, x: 50 } },
            { sel: '.reveal-scale', from: { opacity: 0, scale: 0.92 } }
        ];

        kinds.forEach(function (kind) {
            var els = document.querySelectorAll(kind.sel);
            if (!els.length) return;
            gsap.set(els, kind.from);
            ST.batch(els, {
                start: 'top 88%',
                once: true,
                onEnter: function (batch) {
                    gsap.to(batch, {
                        opacity: 1, x: 0, y: 0, scale: 1,
                        duration: 0.8, ease: 'power3.out', stagger: 0.08,
                        clearProps: 'transform,opacity'
                    });
                }
            });
        });
    }

    /* ------------------------------------------------------------ stats bar */
    function stats() {
        var boxes = document.querySelectorAll('.stats-bar .stat-box');
        if (!boxes.length) return;

        boxes.forEach(function (box) { box.style.setProperty('--ui-progress', 0); });

        ST.create({
            trigger: '.stats-bar',
            start: 'top 85%',
            once: true,
            onEnter: function () {
                boxes.forEach(function (box, i) {
                    gsap.to(box, { '--ui-progress': 1, duration: 1.2, delay: 0.1 * i, ease: 'power2.inOut' });

                    var num  = box.querySelector('.stat-num');
                    var node = num && num.firstChild;
                    if (!node || node.nodeType !== 3) return;
                    var target = parseInt(node.nodeValue, 10);
                    var suffix = node.nodeValue.replace(/^\s*\d+/, '');
                    if (!isFinite(target)) return;
                    var counter = { v: 0 };
                    gsap.to(counter, {
                        v: target, duration: 1.4, delay: 0.1 * i, ease: 'power2.out',
                        onUpdate: function () { node.nodeValue = Math.round(counter.v) + suffix; }
                    });
                });
            }
        });
    }

    /* ------------------------------------------------------------ tank cards: 3D tilt */
    function tilt() {
        if (!finePointer) return;
        document.querySelectorAll('.qt-card').forEach(function (card) {
            gsap.set(card, { transformPerspective: 900 });
            var rx = gsap.quickTo(card, 'rotationX', { duration: 0.4, ease: 'power3.out' });
            var ry = gsap.quickTo(card, 'rotationY', { duration: 0.4, ease: 'power3.out' });

            card.addEventListener('pointermove', function (e) {
                var r = card.getBoundingClientRect();
                var px = (e.clientX - r.left) / r.width - 0.5;
                var py = (e.clientY - r.top) / r.height - 0.5;
                ry(px * 10);
                rx(-py * 8);
            });
            card.addEventListener('pointerenter', function () {
                gsap.to(card, { y: -8, duration: 0.35, ease: 'power3.out' });
            });
            card.addEventListener('pointerleave', function () {
                rx(0); ry(0);
                gsap.to(card, { y: 0, duration: 0.45, ease: 'power3.out' });
            });
        });
    }

    /* ------------------------------------------------------------ hero buttons lean toward the cursor */
    function magnetic() {
        if (!finePointer) return;
        document.querySelectorAll('.lp-hero .btn-gold, .lp-hero .btn-ghost').forEach(function (btn) {
            var mx = gsap.quickTo(btn, 'x', { duration: 0.35, ease: 'power3.out' });
            var my = gsap.quickTo(btn, 'y', { duration: 0.35, ease: 'power3.out' });
            btn.addEventListener('pointermove', function (e) {
                var r = btn.getBoundingClientRect();
                mx(((e.clientX - r.left) / r.width - 0.5) * 10);
                my(((e.clientY - r.top) / r.height - 0.5) * 8);
            });
            btn.addEventListener('pointerleave', function () { mx(0); my(0); });
        });
    }

    function start() {
        try { hero(); } catch (e) { reveal(); }
        try { reveals(); } catch (e) { root.classList.remove('ui-gsap'); }
        try { stats(); } catch (e) {}
        try { tilt(); } catch (e) {}
        try { magnetic(); } catch (e) {}
        // images loading later change the page height — keep trigger points accurate
        window.addEventListener('load', function () { ST.refresh(); });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
