<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | GMD South Phils</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <style>
        /* ===== Layout: brand panel (left) + sign-in card (right) — a sibling of the sign-up page ===== */
        body { min-height: 100vh; background: var(--cream); }
        .lg-page { display: grid; grid-template-columns: 1.05fr 0.95fr; min-height: 100vh; }

        .lg-aside {
            position: relative; overflow: hidden; color: #fff;
            display: flex; flex-direction: column; justify-content: center; gap: 40px;
            padding: 32px 56px 48px;
            background: linear-gradient(180deg, #333333 0%, #1a1a1a 100%);
        }
        .lg-aside.has-bg-photo {
            background-image: linear-gradient(180deg, rgba(26,26,26,.62) 0%, rgba(26,26,26,.92) 100%), var(--lg-photo);
            background-size: cover; background-position: center;
        }
        .lg-aside::after {          /* hazard stripe */
            content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 6px;
            background: repeating-linear-gradient(135deg, var(--accent) 0 14px, rgba(0,0,0,.55) 14px 28px);
        }
        .lg-aside-glow {
            position: absolute; top: -160px; right: -140px; width: 460px; height: 460px; pointer-events: none;
            background: radial-gradient(circle, rgba(245,180,0,.16) 0%, transparent 70%);
        }

        .lg-pitch { position: relative; }
        .lg-badge {
            display: inline-flex; align-items: center; gap: 8px; margin-bottom: 18px;
            padding: 6px 14px; border-radius: 999px; font-size: 11px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase;
            background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.16); color: rgba(255,255,255,.85);
        }
        .lg-badge svg { width: 13px; height: 13px; color: var(--accent); }
        .lg-pitch h1 { font-size: clamp(30px, 2.9vw, 42px); font-weight: 900; line-height: 1.1; letter-spacing: -1px; margin: 0 0 14px; max-width: 560px; }
        .lg-pitch h1 em { font-style: normal; color: var(--accent); }
        .lg-pitch p { font-size: 15px; line-height: 1.7; color: rgba(255,255,255,.68); max-width: 480px; margin: 0; }

        /* tank blueprint */
        .lg-blueprint {     /* viewBox is cropped to the drawing so it sits centred; height-capped so short screens don't scroll */
            position: relative; display: block; align-self: center; width: 100%; max-width: 560px; height: auto; max-height: 34vh;
            color: rgba(255,255,255,.78);
        }
        .lg-blueprint .bp-dim { color: rgba(255,255,255,.38); }
        .lg-blueprint .bp-accent { color: var(--accent); }
        .lg-blueprint .bp-label { fill: rgba(255,255,255,.62); stroke: none; font: 700 15px var(--mono); letter-spacing: .04em; }

        /* ===== Sign-in column ===== */
        .lg-main {
            position: relative; display: flex; align-items: center; justify-content: center; padding: 24px 32px;
            background:
                radial-gradient(circle at 85% 8%, rgba(245,180,0,.10) 0%, transparent 38%),
                radial-gradient(circle at 10% 95%, rgba(0,0,0,.04) 0%, transparent 40%),
                var(--cream);
        }
        .lg-card {
            width: 100%; max-width: 430px; padding: 36px 34px 28px; border-radius: 28px;
            background: var(--white); border: 1px solid var(--border);
            box-shadow: 0 1px 2px rgba(0,0,0,.04), 0 24px 60px rgba(0,0,0,.08);
        }
        .lg-head h2 { font-size: 28px; font-weight: 900; letter-spacing: -.6px; color: var(--dark); margin: 0 0 6px; }
        .lg-head p { font-size: 13.5px; line-height: 1.6; color: var(--muted); margin: 0 0 24px; }

        /* notices (error / info / success) */
        .lg-notice {
            display: flex; align-items: flex-start; gap: 10px; margin-bottom: 18px; padding: 12px 14px;
            border-radius: 14px; font-size: 13px; font-weight: 600; line-height: 1.5; border: 1px solid;
        }
        .lg-notice svg { width: 17px; height: 17px; flex-shrink: 0; margin-top: 1px; }
        .lg-notice a { color: inherit; font-weight: 800; }
        .lg-notice.is-error   { background: #FEF3F2; border-color: #FECDCA; color: var(--danger); animation: errorShake .4s ease; }
        .lg-notice.is-info    { background: #EAF0FF; border-color: rgba(42,78,170,.25); color: #2A4EAA; }
        .lg-notice.is-success { background: #E7F6EC; border-color: #86efac; color: #14532d; }
        .lg-notice.is-success svg { color: #207A3A; }
        .lg-resend { margin: -8px 0 18px; text-align: center; }

        /* fields */
        .lg-field { margin-bottom: 16px; }
        .lg-label-row { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin-bottom: 7px; }
        .lg-label { font-size: 12.5px; font-weight: 800; color: var(--dark); }
        .lg-forgot-row { display: flex; justify-content: flex-end; margin-top: 8px; }
        .lg-forgot { font-size: 12.5px; font-weight: 700; color: var(--muted); text-decoration: none; transition: color .2s; }
        .lg-forgot:hover { color: var(--dark); text-decoration: underline; text-decoration-color: var(--accent); text-decoration-thickness: 2px; text-underline-offset: 3px; }
        .lg-input-wrap { position: relative; }
        .lg-input-wrap .lg-lead { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 17px; height: 17px; color: #9a9a9a; pointer-events: none; transition: color .2s; }
        .lg-input-wrap:focus-within .lg-lead { color: var(--dark); }
        .lg-input {
            width: 100%; height: 50px; box-sizing: border-box; padding: 0 44px 0 42px;
            border: 1.5px solid var(--border); border-radius: 14px; background: #fafafa;
            font-size: 14px; color: var(--dark); font-family: inherit; outline: none;
            transition: border-color .2s, background-color .2s, box-shadow .2s;
        }
        .lg-input::placeholder { color: #a3a3a3; }
        .lg-input:hover { border-color: #cfcfcf; }
        .lg-input:focus { background: #fff; border-color: var(--dark); box-shadow: 0 0 0 4px rgba(0,0,0,.07); }
        .lg-input.error { border-color: #f87171; background: #fff7f7; }
        .lg-eye {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            width: 34px; height: 34px; border-radius: 10px; border: none; background: transparent; cursor: pointer;
            display: flex; align-items: center; justify-content: center; color: #9a9a9a; transition: background .2s, color .2s;
        }
        .lg-eye:hover { background: #f0f0f0; color: var(--dark); }
        .lg-eye svg { width: 17px; height: 17px; }

        /* buttons */
        .lg-btn {
            position: relative; overflow: hidden; width: 100%; height: 54px; margin-top: 22px;
            border: none; border-radius: 16px; cursor: pointer; font-family: inherit;
            background: linear-gradient(135deg, #333 0%, #1a1a1a 100%); color: #fff;
            font-size: 15px; font-weight: 900;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 10px 24px rgba(0,0,0,.16); transition: transform .2s, box-shadow .2s, opacity .2s;
        }
        .lg-btn::after {
            content: ''; position: absolute; top: 0; bottom: 0; left: 0; width: 40%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.18), transparent);
            transform: translateX(-130%) skewX(-18deg);
        }
        .lg-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 16px 30px rgba(0,0,0,.22); }
        .lg-btn:hover:not(:disabled)::after { animation: lgSheen .8s cubic-bezier(.16,.84,.44,1); }
        .lg-btn:active:not(:disabled) { transform: translateY(0) scale(.99); }
        .lg-btn:disabled { opacity: .75; cursor: not-allowed; box-shadow: none; }
        .lg-btn svg { width: 18px; height: 18px; transition: transform .2s; }
        .lg-btn:hover:not(:disabled) svg { transform: translateX(3px); }
        @keyframes lgSheen { from { transform: translateX(-130%) skewX(-18deg); } to { transform: translateX(260%) skewX(-18deg); } }

        .lg-divider { display: flex; align-items: center; gap: 12px; margin: 22px 0 14px; font-size: 12px; font-weight: 700; color: #9a9a9a; }
        .lg-divider::before, .lg-divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }
        .lg-btn-ghost {
            width: 100%; height: 50px; border-radius: 16px; box-sizing: border-box;
            display: flex; align-items: center; justify-content: center; gap: 9px;
            border: 1.5px solid var(--border); background: #fff; color: var(--dark);
            font-size: 14px; font-weight: 800; text-decoration: none;
            transition: border-color .2s, background .2s, transform .2s;
        }
        .lg-btn-ghost:hover { border-color: var(--dark); background: #fafafa; transform: translateY(-1px); }
        .lg-btn-ghost svg { width: 17px; height: 17px; color: var(--accent-dark); }
        .lg-link-btn {
            background: none; border: none; padding: 0; cursor: pointer; font: inherit; font-size: 12.5px; font-weight: 800; color: var(--dark);
            text-decoration: underline; text-decoration-color: var(--accent); text-decoration-thickness: 2px; text-underline-offset: 3px;
        }

        .lg-secure { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 18px; padding-top: 16px; border-top: 1px solid rgba(0,0,0,.07); font-size: 11.5px; color: #8a8a8a; text-align: center; }
        .lg-secure svg { width: 13px; height: 13px; color: var(--accent-dark); flex-shrink: 0; }
        .lg-home { display: inline-flex; align-items: center; gap: 6px; margin-top: 14px; font-size: 12.5px; font-weight: 700; color: var(--muted); text-decoration: none; transition: color .2s, gap .2s; }
        .lg-home:hover { color: var(--dark); gap: 9px; }
        .lg-home svg { width: 14px; height: 14px; }
        .lg-home-row { text-align: center; }

        :focus-visible { outline: none; }
        .lg-btn:focus-visible, .lg-btn-ghost:focus-visible, .lg-forgot:focus-visible, .lg-eye:focus-visible,
        .lg-link-btn:focus-visible, .lg-home:focus-visible {
            box-shadow: 0 0 0 3px #fff, 0 0 0 5px rgba(42,78,170,.45); border-radius: 10px;
        }

        /* GSAP entrance: pre-hidden only when GSAP actually loaded (html.lg-anim), with a failsafe */
        html.lg-anim .lg-pitch > *, html.lg-anim .lg-blueprint, html.lg-anim .lg-card { opacity: 0; }

        /* ===== Tablet / phone: the panel becomes a compact header ===== */
        @media (max-width: 960px) {
            .lg-page { grid-template-columns: 1fr; }
            .lg-aside { padding: 26px 24px 34px; gap: 22px; }
            .lg-pitch h1 { font-size: 28px; }
            .lg-pitch p { font-size: 13.5px; }
            .lg-blueprint { display: none; }
            .lg-main { padding: 28px 16px 44px; align-items: flex-start; }
        }
        @media (max-width: 520px) {
            .lg-aside { padding: 22px 20px 30px; }
            .lg-badge { margin-bottom: 14px; }
            .lg-pitch h1 { font-size: 24px; }
            .lg-card { padding: 28px 20px 22px; border-radius: 24px; }
            .lg-head h2 { font-size: 24px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .lg-btn, .lg-btn svg, .lg-btn-ghost, .lg-home { transition: none; }
            .lg-btn:hover::after, .lg-notice.is-error { animation: none; }
        }
    </style>
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
<body>

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
