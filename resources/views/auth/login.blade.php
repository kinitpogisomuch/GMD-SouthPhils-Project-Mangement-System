<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | GMD South Phils</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ @filemtime(public_path('css/login.css')) }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script>
        (function () {   // pre-hide for the entrance only when GSAP is here (and motion is welcome); failsafe after 2.5 s
            if (!window.gsap || (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) return;
            var root = document.documentElement;
            root.classList.add('lg-anim');
            setTimeout(function () { root.classList.remove('lg-anim'); }, 2500);
        })();
    </script>
</head>
<body class="pg-auth-login">

    @php
    $hasLoginBg = file_exists(public_path('images/login.jpg'));
    @endphp
    <div class="lg-page">

        <aside class="lg-aside {{ $hasLoginBg ? 'has-bg-photo' : '' }}" @if($hasLoginBg) style="--lg-photo: url('{{ asset('images/login.jpg') }}')" @endif>
            <div class="lg-aside-glow" aria-hidden="true"></div>

            <div class="lg-pitch">
                <div class="lg-badge"><i data-lucide="shield-check"></i> Tank Fabrication Portal</div>
                <h1>Built strong. <em>Tracked</em> with precision.</h1>
                <p>From quotation to completion, follow every phase of your storage tank fabrication project in one place.</p>
            </div>

            {{-- Blueprint of a horizontal fuel tank, drawn line by line on load (decorative) --}}
            <svg class="lg-blueprint" viewBox="55 30 1000 390" aria-hidden="true" focusable="false"
                 fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                {{-- centre line --}}
                <path class="bp-dim" d="M110 220 H890" stroke-dasharray="10 8" stroke-width="1"/>
                {{-- shell with elliptical heads --}}
                <path class="bp-draw" d="M200 140 H800 A60 80 0 0 1 800 300 H200 A60 80 0 0 1 200 140 Z" stroke-width="2.2"/>
                {{-- weld seams --}}
                <path class="bp-draw bp-dim" d="M350 140 V300 M500 140 V300 M650 140 V300"/>
                {{-- manhole + nozzles on top --}}
                <path class="bp-draw" d="M470 140 V114 H530 V140 M462 114 H538"/>
                <path class="bp-draw" d="M292 140 V124 H308 V140 M288 124 H312 M692 140 V124 H708 V140 M688 124 H712"/>
                {{-- saddles and ground --}}
                <path class="bp-draw" d="M262 296 L248 352 H352 L338 296 M662 296 L648 352 H752 L738 296"/>
                <path class="bp-dim" d="M120 356 H880" stroke-dasharray="3 7" stroke-width="1"/>
                {{-- length dimension --}}
                <path class="bp-draw bp-dim" d="M140 398 H860 M140 388 V408 M860 388 V408"/>
                {{-- diameter dimension --}}
                <path class="bp-draw bp-dim" d="M912 140 V300 M902 140 H922 M902 300 H922"/>
                {{-- callout on the manhole --}}
                <path class="bp-draw bp-accent" d="M500 114 L560 62 H680"/>
                <circle class="bp-draw bp-accent" cx="500" cy="114" r="5"/>
                <text class="bp-label" x="500" y="392" text-anchor="middle">L = 4,850 mm</text>
                <text class="bp-label" x="934" y="224" text-anchor="start">Ø 2,200</text>
                <text class="bp-label" x="566" y="54">600 mm MANHOLE</text>
                <text class="bp-label" x="200" y="104">GMD-UFST · 18,000 L</text>
            </svg>
        </aside>

        <main class="lg-main">
            <div class="lg-card">
                <div class="lg-head">
                    <h2>Welcome back</h2>
                    <p>Log in with your username to continue to your portal.</p>
                </div>

                @if(session('error'))
                <div class="lg-notice is-error" role="alert">
                    <i data-lucide="circle-alert"></i>
                    <span>{{ session('error') }}</span>
                </div>
                @endif

                @if(session('resend_verification_email'))
                {{-- Unverified sign-up: let them get a fresh verification link right here --}}
                <form method="POST" action="{{ route('signup.resend_verification') }}" class="lg-resend">
                    @csrf
                    <input type="hidden" name="email" value="{{ session('resend_verification_email') }}">
                    <button type="submit" class="lg-link-btn">Resend verification email</button>
                </form>
                @endif

                @if(session('info'))
                <div class="lg-notice is-info" role="status">
                    <i data-lucide="hourglass"></i>
                    <span>{{ session('info') }}</span>
                </div>
                @endif

                @if(session('success'))
                <div class="lg-notice is-success" role="status">
                    <i data-lucide="circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}" id="loginForm">
                    @csrf
                    <div class="lg-field">
                        <div class="lg-label-row"><label class="lg-label" for="loginUsername">Username</label></div>
                        <div class="lg-input-wrap">
                            <i data-lucide="user" class="lg-lead"></i>
                            <input type="text" name="email" id="loginUsername" required placeholder="Enter your username"
                                   class="lg-input {{ session('error') ? 'error' : '' }}"
                                   value="{{ old('email') }}" autocomplete="username" autocapitalize="none" spellcheck="false"
                                   @unless(old('email')) autofocus @endunless>
                        </div>
                    </div>

                    <div class="lg-field">
                        <div class="lg-label-row">
                            <label class="lg-label" for="loginPassword">Password</label>
                        </div>
                        <div class="lg-input-wrap">
                            <i data-lucide="lock" class="lg-lead"></i>
                            <input type="password" name="password" id="loginPassword" required placeholder="Enter your password"
                                   class="lg-input {{ session('error') ? 'error' : '' }}" autocomplete="current-password"
                                   @if(old('email')) autofocus @endif>
                            <button type="button" id="toggleLoginPw" class="lg-eye" aria-label="Show password">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                        <div class="lg-forgot-row">
                            <a href="{{ route('password.request') }}" class="lg-forgot">Forgot password?</a>
                        </div>
                    </div>

                    <button type="submit" class="lg-btn" id="loginBtn">
                        <span>Log in</span>
                        <i data-lucide="arrow-right"></i>
                    </button>
                </form>

                <div class="lg-divider">New client?</div>
                <a href="{{ route('signup') }}" class="lg-btn-ghost"><i data-lucide="user-plus"></i> Create a client account</a>

                <div class="lg-secure">
                    <i data-lucide="shield-check"></i>
                    <span>Secured access · GMD South Phils Metal Fabrication Works</span>
                </div>
                <div class="lg-home-row">
                    <a href="{{ route('home') }}" class="lg-home"><i data-lucide="arrow-left"></i> Back to home</a>
                </div>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        if (typeof lucide !== "undefined") lucide.createIcons();

        var pwInput = document.getElementById('loginPassword');

        document.getElementById('toggleLoginPw').addEventListener('click', function () {
            var isHidden = pwInput.type === 'password';
            pwInput.type = isHidden ? 'text' : 'password';
            this.innerHTML = '<i data-lucide="' + (isHidden ? 'eye-off' : 'eye') + '"></i>';
            this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            if (typeof lucide !== 'undefined') lucide.createIcons();
            pwInput.focus();
        });

        // A wrong-credentials error clears its red outline as soon as the user edits a field
        document.querySelectorAll('.lg-input.error').forEach(function (input) {
            input.addEventListener('input', function () {
                document.querySelectorAll('.lg-input.error').forEach(function (i) { i.classList.remove('error'); });
            }, { once: true });
        });

        // Login button loading state on submit
        document.getElementById('loginForm').addEventListener('submit', function () {
            var btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.innerHTML = '<div class="btn-spinner"></div><span>Logging in...</span>';
        });

        // Entrance: the card slides up, the panel copy rises in, then the tank blueprint draws itself line by line
        (function () {
            var root = document.documentElement;
            if (!window.gsap || !root.classList.contains('lg-anim')) { root.classList.remove('lg-anim'); return; }
            var hidden = document.querySelectorAll('.lg-pitch > *, .lg-card');
            gsap.set(hidden, { opacity: 0 });
            root.classList.remove('lg-anim');
            var tl = gsap.timeline({ defaults: { ease: 'power3.out' } })
                .fromTo('.lg-card', { opacity: 0, y: 26, scale: 0.98 }, { opacity: 1, y: 0, scale: 1, duration: 0.7, clearProps: 'transform,opacity' }, 0)
                .fromTo('.lg-pitch > *', { opacity: 0, y: 16 }, { opacity: 1, y: 0, duration: 0.6, stagger: 0.1, clearProps: 'transform,opacity' }, 0.1);

            var bp = document.querySelector('.lg-blueprint');
            if (!bp || getComputedStyle(bp).display === 'none') return;   // hidden on phones
            var lines = bp.querySelectorAll('.bp-draw');
            lines.forEach(function (el) {
                var len = el.getTotalLength();
                gsap.set(el, { strokeDasharray: len, strokeDashoffset: len });
            });
            gsap.set(bp.querySelectorAll('.bp-label, path:not(.bp-draw)'), { opacity: 0 });
            tl.set(bp, { opacity: 1 }, 0.3)
              .to(lines, { strokeDashoffset: 0, duration: 1.1, stagger: 0.12, ease: 'power2.inOut',
                           onComplete: function () { gsap.set(lines, { clearProps: 'strokeDasharray,strokeDashoffset' }); } }, 0.3)
              .to(bp.querySelectorAll('path:not(.bp-draw)'), { opacity: 1, duration: 0.6 }, 0.5)
              .to(bp.querySelectorAll('.bp-label'), { opacity: 1, duration: 0.5, stagger: 0.1 }, 1.5);
        })();
    </script>
</body>
</html>
