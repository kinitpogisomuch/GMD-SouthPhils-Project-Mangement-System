<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | GMD South Phils</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <style>
        body {
            min-height: 100vh; margin: 0;
            display: flex; align-items: center; justify-content: center;
            padding: 32px 16px; box-sizing: border-box;
            background: radial-gradient(circle at 50% 0%, #ffffff 0%, var(--cream) 60%);
        }

        .vf-wrap { width: 100%; max-width: 460px; animation: vfRise .7s cubic-bezier(.22, 1, .36, 1) both; }
        @keyframes vfRise { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: none; } }

        .vf-brand { display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 22px; }
        .vf-brand img { width: 44px; height: 44px; border-radius: 50%; }
        .vf-brand-name { font-size: 19px; font-weight: 900; color: var(--dark); letter-spacing: -.3px; }
        .vf-brand-name span { color: var(--muted); }
        .vf-brand-sub { font-size: 10.5px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: .14em; margin-top: 2px; }

        .vf-card {
            background: var(--white); border: 1px solid var(--border); border-radius: 24px;
            box-shadow: 0 18px 40px var(--shadow); padding: 34px 30px 26px; text-align: center;
        }

        /* ── Animation: a "verifying" spinner, then the result badge draws itself in ── */
        .vf-anim { position: relative; width: 104px; height: 104px; margin: 0 auto 22px; }
        .vf-loader {
            position: absolute; inset: 6px; border-radius: 50%;
            border: 4px solid var(--cream-deep); border-top-color: var(--dark);
            animation: vfSpin .8s linear infinite, vfHide .3s ease 1.3s forwards;
        }
        @keyframes vfSpin { to { transform: rotate(360deg); } }
        @keyframes vfHide { to { opacity: 0; transform: scale(.6); } }

        .vf-badge {
            position: absolute; inset: 0; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transform: scale(.5);
            animation: vfPop .5s cubic-bezier(.34, 1.56, .64, 1) 1.45s forwards;
        }
        @keyframes vfPop { to { opacity: 1; transform: scale(1); } }
        .vf-badge svg { width: 104px; height: 104px; }
        .vf-badge .ring { fill: none; stroke-width: 3; stroke-dasharray: 302; stroke-dashoffset: 302; animation: vfDraw .6s ease 1.55s forwards; }
        .vf-badge .mark { fill: none; stroke-width: 5; stroke-linecap: round; stroke-linejoin: round; stroke-dasharray: 80; stroke-dashoffset: 80; animation: vfDraw .45s ease 2.05s forwards; }
        @keyframes vfDraw { to { stroke-dashoffset: 0; } }

        .vf-badge.success { background: #E7F6EC; } .vf-badge.success .ring, .vf-badge.success .mark { stroke: #207A3A; }
        .vf-badge.info    { background: #EAF0FF; } .vf-badge.info .ring,    .vf-badge.info .mark    { stroke: #2A4EAA; }
        .vf-badge.warn    { background: #FFF3D6; } .vf-badge.warn .ring,    .vf-badge.warn .mark    { stroke: #A16207; }
        .vf-badge.error   { background: #FEE4E2; } .vf-badge.error .ring,   .vf-badge.error .mark   { stroke: #B42318; }
        /* soft pulse once the result is in */
        .vf-badge.success { animation: vfPop .5s cubic-bezier(.34, 1.56, .64, 1) 1.45s forwards, vfGlow 1.6s ease 2.4s 2; }
        @keyframes vfGlow { 0%, 100% { box-shadow: 0 0 0 0 rgba(32, 122, 58, .25); } 50% { box-shadow: 0 0 0 12px rgba(32, 122, 58, 0); } }

        /* "Verifying…" line during the spinner, then the result text fades in */
        .vf-stage { position: relative; }
        .vf-verifying { font-size: 14px; font-weight: 700; color: var(--muted); animation: vfHide .3s ease 1.3s forwards; }
        .vf-result { opacity: 0; transform: translateY(8px); animation: vfShow .5s ease 1.6s forwards; }
        @keyframes vfShow { to { opacity: 1; transform: none; } }
        .vf-stage .vf-verifying { position: absolute; left: 0; right: 0; top: 0; }

        .vf-title { font-size: 22px; font-weight: 900; color: var(--dark); letter-spacing: -.4px; margin: 0 0 8px; }
        .vf-desc { font-size: 14px; line-height: 1.6; color: var(--muted); margin: 0 0 20px; }
        .vf-desc strong { color: var(--dark); }

        .vf-steps { list-style: none; margin: 0 0 20px; padding: 12px 16px; border: 1px solid var(--border); border-radius: 14px; background: #fafafa; text-align: left; }
        .vf-steps li { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 700; color: var(--muted); padding: 5px 0; }
        .vf-steps .dot { width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
                         font-size: 11px; font-weight: 900; background: #eee; color: #888; }
        .vf-steps li.done { color: #207A3A; } .vf-steps li.done .dot { background: #207A3A; color: #fff; }
        .vf-steps li.current { color: var(--dark); } .vf-steps li.current .dot { background: var(--dark); color: #fff; }
        .vf-steps .dot svg { width: 12px; height: 12px; }

        .vf-resend { margin: 4px 0 14px; padding-top: 16px; border-top: 1px solid var(--border); text-align: left; }
        .vf-resend p { font-size: 12.5px; color: var(--muted); margin: 0 0 10px; text-align: center; }

        .vf-btn {
            display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; height: 48px;
            border-radius: 14px; font-size: 14px; font-weight: 800; text-decoration: none; box-sizing: border-box;
            background: var(--dark); color: var(--white); border: 1px solid var(--dark); transition: .2s ease;
        }
        .vf-btn:hover { background: var(--dark-soft); transform: translateY(-1px); }
        .vf-btn.ghost { background: transparent; color: var(--dark); border-color: var(--border); }
        .vf-btn.ghost:hover { background: var(--cream); }
        .vf-btn svg { width: 16px; height: 16px; }

        .vf-foot { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 18px; font-size: 11.5px; color: var(--muted); }
        .vf-foot svg { width: 13px; height: 13px; }

        @media (max-width: 480px) {
            .vf-card { padding: 28px 20px 22px; border-radius: 20px; }
            .vf-title { font-size: 20px; }
        }
        /* Skip the animation for people who prefer reduced motion — show the result straight away */
        @media (prefers-reduced-motion: reduce) {
            .vf-wrap, .vf-badge, .vf-result, .vf-badge .ring, .vf-badge .mark { animation: none !important; opacity: 1; transform: none; stroke-dashoffset: 0; }
            .vf-loader, .vf-verifying { display: none; }
        }
    </style>
</head>
<body>
    @php
        // Result → badge colour, icon path (drawn inside the ring), title, text
        $view = match ($state) {
            'verified' => [
                'tone'  => 'success',
                'mark'  => 'M33 53 l12 12 l24 -26',                                    // checkmark
                'title' => 'Email Verified Successfully!',
                'desc'  => 'Your email address has been verified. Your account is now awaiting administrator approval.',
            ],
            'already_verified' => [
                'tone'  => 'info',
                'mark'  => 'M33 53 l12 12 l24 -26',
                'title' => 'Email Already Verified',
                'desc'  => $active
                    ? 'This email address was already verified and your account is set up. You can log in to the client portal.'
                    : ($approved
                        ? 'This email address was already verified and your registration has been approved. Use the "Set Up Account" link we emailed you to create your password.'
                        : 'This email address was already verified. Your registration is awaiting administrator approval — we\'ll email you once it\'s approved.'),
            ],
            'expired' => [
                'tone'  => 'warn',
                'mark'  => 'M52 34 v20 l11 8',                                          // clock hands
                'title' => 'Verification Link Expired',
                'desc'  => 'This verification link has expired. Request a new verification email below to finish setting up your account.',
            ],
            default => [
                'tone'  => 'error',
                'mark'  => 'M38 38 l28 28 M66 38 l-28 28',                              // ×
                'title' => 'Invalid Verification Link',
                'desc'  => 'This verification link is invalid or incomplete. Request a new verification email below, or log in to check your account status.',
            ],
        };
        $isVerified = in_array($state, ['verified', 'already_verified']);
    @endphp

    <div class="vf-wrap">
        <div class="vf-brand">
            <img src="{{ asset('images/gmdlogo-circle.svg') }}" alt="GMD South Phils">
            <div>
                <div class="vf-brand-name">GMD <span>South Phils</span></div>
                <div class="vf-brand-sub">Client Portal</div>
            </div>
        </div>

        <div class="vf-card" role="status" aria-live="polite">
            <div class="vf-anim" aria-hidden="true">
                <div class="vf-loader"></div>
                <div class="vf-badge {{ $view['tone'] }}">
                    <svg viewBox="0 0 104 104">
                        <circle class="ring" cx="52" cy="52" r="48"/>
                        <path class="mark" d="{{ $view['mark'] }}"/>
                    </svg>
                </div>
            </div>

            <div class="vf-stage">
                <div class="vf-verifying" aria-hidden="true">Verifying your email…</div>
                <div class="vf-result">
                    <h1 class="vf-title">{{ $view['title'] }}</h1>
                    <p class="vf-desc">
                        {{ $view['desc'] }}
                        @if($isVerified && $email && !$approved)
                            <br>We'll email you at <strong>{{ $email }}</strong> once it's approved.
                        @endif
                    </p>

                    @if($isVerified)
                    {{-- Sign Up → Verify Email → Admin Approval → Set up account & log in --}}
                    <ul class="vf-steps">
                        <li class="done"><span class="dot"><i data-lucide="check"></i></span>Sign up</li>
                        <li class="done"><span class="dot"><i data-lucide="check"></i></span>Verify your email</li>
                        <li class="{{ $approved ? 'done' : 'current' }}"><span class="dot">@if($approved)<i data-lucide="check"></i>@else 3 @endif</span>Administrator approval</li>
                        <li class="{{ $active ? 'done' : ($approved ? 'current' : '') }}"><span class="dot">@if($active)<i data-lucide="check"></i>@else 4 @endif</span>Set up your account from the approval email, then log in</li>
                    </ul>
                    @else
                    <div class="vf-resend">
                        <p>Enter your email address and we'll send you a new verification link.</p>
                        <form method="POST" action="{{ route('signup.resend_verification') }}">
                            @csrf
                            <div class="form-group">
                                <div class="input-wrapper">
                                    <i data-lucide="mail"></i>
                                    <input type="text" name="email" required placeholder="Your email address" value="{{ $email }}">
                                </div>
                            </div>
                            <button type="submit" class="vf-btn"><i data-lucide="send"></i><span>Resend verification email</span></button>
                        </form>
                    </div>
                    @endif

                    {{-- Logging in only works once the account is set up (Active), so the button appears only then --}}
                    @if($active)
                    <a href="{{ route('login') }}" class="vf-btn">
                        <i data-lucide="log-in"></i><span>Log In</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="vf-foot">
            <i data-lucide="shield-check"></i>
            <span>Secured access · GMD South Phils Metal Fabrication Works</span>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
        @if($isVerified)
        // Tell the sign-up tab still showing "Check your email" (same browser) to reload right away
        try { localStorage.setItem('gmd_email_verified', String(Date.now())); } catch (e) {}
        @endif
    </script>
</body>
</html>
