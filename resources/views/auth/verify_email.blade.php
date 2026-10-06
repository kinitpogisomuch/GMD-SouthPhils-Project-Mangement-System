<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email | GMD South Phils</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <style>
        .ve-icon { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
        .ve-icon svg { width: 30px; height: 30px; }
        .ve-icon.mail     { background: #EAF0FF; color: #2A4EAA; }
        .ve-icon.verified { background: #E7F6EC; color: #207A3A; }
        .ve-icon.warn     { background: #FFF3D6; color: #8A6100; }
        .ve-icon.error    { background: #FEE4E2; color: #B42318; }
        .ve-text { text-align: center; font-size: 14px; line-height: 1.6; color: var(--muted); margin: 0 0 18px; }
        .ve-text strong { color: var(--dark); }
        .ve-steps { list-style: none; margin: 0 0 20px; padding: 14px 16px; border: 1px solid var(--border); border-radius: 14px; background: #fafafa; }
        .ve-steps li { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 700; color: var(--muted); padding: 5px 0; }
        .ve-steps li .dot { width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
                            font-size: 11px; font-weight: 900; background: #eee; color: #888; }
        .ve-steps li.done { color: #207A3A; }
        .ve-steps li.done .dot { background: #207A3A; color: #fff; }
        .ve-steps li.current { color: var(--dark); }
        .ve-steps li.current .dot { background: var(--dark); color: #fff; }
        .ve-resend { margin-top: 6px; padding-top: 16px; border-top: 1px solid var(--border); }
        .ve-resend p { font-size: 12.5px; color: var(--muted); margin: 0 0 10px; text-align: center; }
        .ve-link-btn { display: flex; align-items: center; justify-content: center; text-decoration: none; }
    </style>
</head>
<body>
    @php
        $loginBgPath = public_path('images/login.jpg');
        $hasLoginBg  = file_exists($loginBgPath);
        // Sign Up → Verify Email → Admin Approval → Log in
        $step = $state === 'verified' ? 3 : 2;
    @endphp
    <div class="login-page @if($hasLoginBg) has-bg-photo @endif"
         @if($hasLoginBg) style="--login-bg-photo: url('{{ asset('images/login.jpg') }}')" @endif>

        <div class="login-left-glow"></div>

        <div class="login-left">
            <div class="brand-box">
                <div class="brand-badge">
                    <i data-lucide="shield-check"></i>
                    Tank Fabrication Portal
                </div>
                <h1>GMD South Phils Metal Fabrication Works</h1>
                <p>From planning to delivery, manage every phase of your storage tank fabrication projects — built strong, tracked with precision.</p>
            </div>
        </div>

        <div class="login-right">
            <div class="login-card">

                @if($state === 'verified')
                    <div class="ve-icon verified"><i data-lucide="badge-check"></i></div>
                    <div class="login-header" style="text-align:center;">
                        <h2>Email Verified</h2>
                    </div>
                    <p class="ve-text">
                        Your email has been verified. Your account is currently <strong>waiting for admin approval</strong>.
                        We'll email you at <strong>{{ $email }}</strong> once it's approved so you can log in.
                    </p>

                @elseif($state === 'expired')
                    <div class="ve-icon warn"><i data-lucide="clock-alert"></i></div>
                    <div class="login-header" style="text-align:center;">
                        <h2>Link Expired</h2>
                    </div>
                    <p class="ve-text">This verification link has expired. Send yourself a new one below.</p>

                @elseif($state === 'invalid')
                    <div class="ve-icon error"><i data-lucide="link-2-off"></i></div>
                    <div class="login-header" style="text-align:center;">
                        <h2>Link Not Valid</h2>
                    </div>
                    <p class="ve-text">This verification link is invalid or has already been used. If your email is already verified, your account may be waiting for admin approval — try logging in to check its status.</p>

                @else
                    <div class="ve-icon mail"><i data-lucide="mail-check"></i></div>
                    <div class="login-header" style="text-align:center;">
                        <h2>Check Your Email</h2>
                    </div>
                    @if(!$sent)
                    <div class="error-message">
                        <i data-lucide="circle-alert"></i>
                        <span>We couldn't send the verification email right now. Please use "Resend verification email" below.</span>
                    </div>
                    @elseif(session('verify_resent'))
                    <div class="success-message">
                        <i data-lucide="check-circle"></i>
                        <span>If that email is waiting for verification, a new link has been sent.</span>
                    </div>
                    @endif
                    <p class="ve-text">
                        We sent a verification link to <strong>{{ $email ?: 'your email address' }}</strong>.
                        Open it and click <strong>Verify Email</strong> to continue. Check your spam folder if you don't see it.
                    </p>
                @endif

                <ul class="ve-steps">
                    <li class="done"><span class="dot"><i data-lucide="check" style="width:12px;height:12px;"></i></span>Sign up</li>
                    <li class="{{ $step > 2 ? 'done' : 'current' }}"><span class="dot">@if($step > 2)<i data-lucide="check" style="width:12px;height:12px;"></i>@else 2 @endif</span>Verify your email</li>
                    <li class="{{ $step === 3 ? 'current' : '' }}"><span class="dot">3</span>Wait for admin approval</li>
                    <li><span class="dot">4</span>Log in to the client portal</li>
                </ul>

                @if($state !== 'verified')
                <div class="ve-resend">
                    <p>Didn't get the email or the link stopped working?</p>
                    <form method="POST" action="{{ route('signup.resend_verification') }}">
                        @csrf
                        <div class="form-group">
                            <div class="input-wrapper">
                                <i data-lucide="mail"></i>
                                <input type="text" name="email" required placeholder="Your email address" value="{{ $email }}">
                            </div>
                            @error('email')<div style="font-size:12px;color:#B42318;margin-top:6px;font-weight:600;">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="login-btn"><span>Resend verification email</span></button>
                    </form>
                </div>
                @endif

                <a href="{{ route('login') }}" class="login-btn ve-link-btn" style="margin-top:12px;{{ $state !== 'verified' ? 'background:transparent;color:var(--dark);border:1px solid var(--border);box-shadow:none;' : '' }}">
                    <span>Back to Login</span>
                </a>

                <div class="login-footer">
                    <i data-lucide="shield-check"></i>
                    <span>Secured access · GMD South Phils Metal Fabrication Works</span>
                </div>
            </div>
        </div>

        <div class="login-left-hazard"></div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>if (typeof lucide !== "undefined") lucide.createIcons();</script>
</body>
</html>
