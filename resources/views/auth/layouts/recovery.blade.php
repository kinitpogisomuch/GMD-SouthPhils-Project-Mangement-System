{{-- Shared shell for the forgot-password flow: email (step 1) → verify code (2) → new password (3).
     Pages set: @section('title'), @section('step') (1|2|3), @section('content'), and @push('scripts'). --}}
@php
    $step     = (int) trim($__env->yieldContent('step', '1'));
    $hasPhoto = file_exists(public_path('images/login.jpg'));
    $steps    = [1 => 'Email', 2 => 'Verify', 3 => 'New password'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | GMD South Phils</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/recovery.css') }}?v={{ @filemtime(public_path('css/recovery.css')) }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script>
        (function () {   // the card animates in only when GSAP is here (and motion is welcome); failsafe after 2.5 s
            if (!window.gsap || (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) return;
            var root = document.documentElement;
            root.classList.add('rc-anim');
            setTimeout(function () { root.classList.remove('rc-anim'); }, 2500);
        })();
    </script>
</head>
<body>
    @include('partials.submit_guard')

    <main class="rc-page {{ $hasPhoto ? 'has-bg-photo' : '' }}" @if($hasPhoto) style="--rc-photo: url('{{ asset('images/login.jpg') }}')" @endif>
        <div class="rc-glow" aria-hidden="true"></div>

        <div class="rc-card">
            <a href="{{ route('home') }}" class="rc-brand">
                <img src="{{ asset('images/gmdlogo-circle.svg') }}" alt="">
                <span>GMD <em>South Phils</em></span>
            </a>

            <ol class="rc-steps" aria-label="Password reset progress">
                @foreach($steps as $n => $label)
                @php $state = $n < $step ? 'is-done' : ($n === $step ? 'is-active' : ''); @endphp
                <li class="{{ $state }}" @if($n === $step) aria-current="step" @endif>
                    <span class="rc-dot">
                        @if($n < $step)<i data-lucide="check"></i>@else{{ $n }}@endif
                    </span>
                    <span class="rc-step-label">{{ $label }}</span>
                </li>
                @endforeach
            </ol>

            @yield('content')

            <div class="rc-secure"><i data-lucide="shield-check"></i> Secure password reset · GMD South Phils</div>
        </div>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        if (window.lucide) lucide.createIcons();

        // Card rises in, then its parts follow
        (function () {
            var root = document.documentElement;
            if (!window.gsap || !root.classList.contains('rc-anim')) { root.classList.remove('rc-anim'); return; }
            var card = document.querySelector('.rc-card');
            gsap.set(card, { opacity: 0, y: 26, scale: 0.98 });
            root.classList.remove('rc-anim');
            gsap.timeline({ defaults: { ease: 'power3.out' } })
                .to(card, { opacity: 1, y: 0, scale: 1, duration: 0.7, clearProps: 'transform,opacity' })
                .from('.rc-steps li', { opacity: 0, y: 8, duration: 0.4, stagger: 0.08, clearProps: 'transform,opacity' }, 0.2)
                .from('.rc-card form, .rc-title, .rc-sub, .rc-email, .rc-notice', { opacity: 0, y: 10, duration: 0.45, stagger: 0.05, clearProps: 'transform,opacity' }, 0.3);
        })();

        // A submit button shows a spinner while its form is being sent
        function rcBusy(btn, text) {
            btn.disabled = true;
            btn.innerHTML = '<svg class="rc-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="17" height="17"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg><span>' + text + '</span>';
        }
    </script>
    @stack('scripts')
</body>
</html>
