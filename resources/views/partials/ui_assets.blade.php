{{-- UI enhancement layer for the portals (included by each portal header, like the submit guard).
     ui.css is compiled from resources/scss (npm run css:build). Libraries come from pinned CDN versions,
     the same way the pages already load Lucide and Chart.js, so deployments need no front-end build. --}}
@once
<link rel="stylesheet" href="{{ asset('css/ui.css') }}?v={{ @filemtime(public_path('css/ui.css')) }}">
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script>
    // Hide the parts that animate in only when GSAP is really here (and motion is welcome); ui.js shows
    // them as it animates, and this failsafe shows them regardless after 2.5 s.
    (function () {
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!window.gsap || reduce) return;
        var root = document.documentElement;
        root.classList.add('ui-preload');
        setTimeout(function () { root.classList.remove('ui-preload'); }, 2500);
    })();
    window.GMD_UI = { successAnimation: @json(asset('animations/success-check.json')) };
</script>
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie_light.min.js"></script>
<script defer src="{{ asset('js/ui.js') }}?v={{ @filemtime(public_path('js/ui.js')) }}"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
@endonce
