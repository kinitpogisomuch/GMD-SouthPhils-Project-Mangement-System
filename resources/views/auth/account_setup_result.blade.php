<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Setup | GMD South Phils</title>
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

        .vf-card { background: var(--white); border: 1px solid var(--border); border-radius: 24px; box-shadow: 0 18px 40px var(--shadow); padding: 34px 30px 26px; text-align: center; }

        /* spinner, then the result badge draws itself in (same motion as the email verification page) */
        .vf-anim { position: relative; width: 104px; height: 104px; margin: 0 auto 22px; }
        .vf-loader { position: absolute; inset: 6px; border-radius: 50%; border: 4px solid var(--cream-deep); border-top-color: var(--dark);
                     animation: vfSpin .8s linear infinite, vfHide .3s ease 1.1s forwards; }
        @keyframes vfSpin { to { transform: rotate(360deg); } }
        @keyframes vfHide { to { opacity: 0; transform: scale(.6); } }
        .vf-badge { position: absolute; inset: 0; border-radius: 50%; display: flex; align-items: center; justify-content: center;
                    opacity: 0; transform: scale(.5); animation: vfPop .5s cubic-bezier(.34, 1.56, .64, 1) 1.25s forwards; }
        @keyframes vfPop { to { opacity: 1; transform: scale(1); } }
        .vf-badge svg { width: 104px; height: 104px; }
        .vf-badge .ring { fill: none; stroke-width: 3; stroke-dasharray: 302; stroke-dashoffset: 302; animation: vfDraw .6s ease 1.35s forwards; }
        .vf-badge .mark { fill: none; stroke-width: 5; stroke-linecap: round; stroke-linejoin: round; stroke-dasharray: 80; stroke-dashoffset: 80; animation: vfDraw .45s ease 1.85s forwards; }
        @keyframes vfDraw { to { stroke-dashoffset: 0; } }
        .vf-badge.success { background: #E7F6EC; } .vf-badge.success .ring, .vf-badge.success .mark { stroke: #207A3A; }
        .vf-badge.info    { background: #EAF0FF; } .vf-badge.info .ring,    .vf-badge.info .mark    { stroke: #2A4EAA; }
        .vf-badge.warn    { background: #FFF3D6; } .vf-badge.warn .ring,    .vf-badge.warn .mark    { stroke: #A16207; }
        .vf-badge.error   { background: #FEE4E2; } .vf-badge.error .ring,   .vf-badge.error .mark   { stroke: #B42318; }
        .vf-badge.success { animation: vfPop .5s cubic-bezier(.34, 1.56, .64, 1) 1.25s forwards, vfGlow 1.6s ease 2.2s 2; }
        @keyframes vfGlow { 0%, 100% { box-shadow: 0 0 0 0 rgba(32, 122, 58, .25); } 50% { box-shadow: 0 0 0 12px rgba(32, 122, 58, 0); } }

        .vf-stage { position: relative; }
        .vf-working { position: absolute; left: 0; right: 0; top: 0; font-size: 14px; font-weight: 700; color: var(--muted); animation: vfHide .3s ease 1.1s forwards; }
        .vf-result { opacity: 0; transform: translateY(8px); animation: vfShow .5s ease 1.4s forwards; }
        @keyframes vfShow { to { opacity: 1; transform: none; } }

        .vf-title { font-size: 22px; font-weight: 900; color: var(--dark); letter-spacing: -.4px; margin: 0 0 8px; }
        .vf-desc { font-size: 14px; line-height: 1.6; color: var(--muted); margin: 0 0 20px; }

        .vf-cred { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin: 0 0 20px; padding: 14px 16px;
                   border: 1px dashed var(--border); border-radius: 14px; background: #fafafa; text-align: left; }
        .vf-cred span { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }
        .vf-cred strong { font-size: 18px; font-weight: 900; color: var(--dark); letter-spacing: 2px; }

        .vf-resend { margin: 4px 0 14px; padding-top: 16px; border-top: 1px solid var(--border); text-align: left; }
        .vf-resend p { font-size: 12.5px; color: var(--muted); margin: 0 0 10px; text-align: center; }

        .vf-btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; height: 48px; border-radius: 14px;
                  font-size: 14px; font-weight: 800; text-decoration: none; box-sizing: border-box; cursor: pointer;
                  background: var(--dark); color: var(--white); border: 1px solid var(--dark); transition: .2s ease; }
        .vf-btn:hover { background: var(--dark-soft); transform: translateY(-1px); }
        .vf-btn svg { width: 16px; height: 16px; }

        .vf-foot { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 18px; font-size: 11.5px; color: var(--muted); }
        .vf-foot svg { width: 13px; height: 13px; }
        @media (max-width: 480px) { .vf-card { padding: 28px 20px 22px; border-radius: 20px; } .vf-title { font-size: 20px; } }
        @media (prefers-reduced-motion: reduce) {
            .vf-wrap, .vf-badge, .vf-result, .vf-badge .ring, .vf-badge .mark { animation: none !important; opacity: 1; transform: none; stroke-dashoffset: 0; }
            .vf-loader, .vf-working { display: none; }
        }
    </style>
</head>
<body>
    @php
        $CHECK = 'M33 53 l12 12 l24 -26';
        $view = match ($state) {
            'completed' => ['tone' => 'success', 'mark' => $CHECK, 'working' => 'Setting up your account…',
                'title' => 'Your GMD Account Is Ready!',
                'desc'  => 'Your password has been saved and your account is now active. Log in with your username and the password you just created.'],
            'already_done' => ['tone' => 'info', 'mark' => $CHECK, 'working' => 'Checking your link…',
                'title' => 'Account Already Set Up',
                'desc'  => 'This account has already been set up, so the link can\'t be used again. Log in with your username and password.'],
            'expired' => ['tone' => 'warn', 'mark' => 'M52 34 v20 l11 8', 'working' => 'Checking your link…',
                'title' => 'Setup Link Expired',
                'desc'  => 'This account setup link has expired. Request a new one below and we\'ll email it to you.'],
            'resent' => ['tone' => 'info', 'mark' => $CHECK, 'working' => 'Sending…',
                'title' => 'Check Your Email',
                'desc'  => 'If that registration is approved and waiting for setup, we\'ve emailed a new Set Up Account link. It expires in ' . \App\Http\Controllers\AccountSetupController::LINK_HOURS . ' hours.'],
            'not_allowed' => ['tone' => 'error', 'mark' => 'M38 38 l28 28 M66 38 l-28 28', 'working' => 'Checking your link…',
                'title' => 'Account Setup Not Available',
                'desc'  => 'This registration isn\'t approved for account setup. If you think this is a mistake, please contact GMD South Phils.'],
            default => ['tone' => 'error', 'mark' => 'M38 38 l28 28 M66 38 l-28 28', 'working' => 'Checking your link…',
                'title' => 'Invalid Setup Link',
                'desc'  => 'This account setup link is invalid or incomplete. Please use the Set Up Account button in your approval email.'],
        };
        $showLogin = in_array($state, ['completed', 'already_done']);
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
                    <svg viewBox="0 0 104 104"><circle class="ring" cx="52" cy="52" r="48"/><path class="mark" d="{{ $view['mark'] }}"/></svg>
                </div>
            </div>

            <div class="vf-stage">
                <div class="vf-working" aria-hidden="true">{{ $view['working'] }}</div>
                <div class="vf-result">
                    <h1 class="vf-title">{{ $view['title'] }}</h1>
                    <p class="vf-desc">{{ $view['desc'] }}</p>

                    @if($username)
                    <div class="vf-cred"><span>Your username</span><strong>{{ $username }}</strong></div>
                    @endif

                    @if($state === 'expired')
                    <div class="vf-resend">
                        <p>Enter your email address to get a new setup link.</p>
                        <form method="POST" action="{{ route('account.setup.resend') }}">
                            @csrf
                            <div class="form-group">
                                <div class="input-wrapper">
                                    <i data-lucide="mail"></i>
                                    <input type="text" name="email" required placeholder="Your email address" value="{{ $email }}">
                                </div>
                            </div>
                            <button type="submit" class="vf-btn"><i data-lucide="send"></i><span>Send a new setup link</span></button>
                        </form>
                    </div>
                    @endif

                    @if($showLogin)
                    <a href="{{ route('login') }}" class="vf-btn"><i data-lucide="log-in"></i><span>Go to Login</span></a>
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
    <script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</body>
</html>
