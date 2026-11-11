<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | GMD South Phils</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ @filemtime(public_path('css/login.css')) }}">
</head>
<body class="pg-auth-email-verified">
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
                <div class="vf-brand-sub">Client Side</div>
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

                    @unless($isVerified)
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
                    @endunless

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
