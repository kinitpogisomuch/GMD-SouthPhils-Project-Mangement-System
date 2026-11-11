<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Setup | GMD South Phils</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ @filemtime(public_path('css/login.css')) }}">
</head>
<body class="pg-auth-account-setup-result">
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
                <div class="vf-brand-sub">Client Side</div>
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
