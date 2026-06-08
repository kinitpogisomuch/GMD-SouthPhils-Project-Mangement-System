<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code | GMD South Phils</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <style>
        .fp-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            padding: 40px 16px;
        }
        .fp-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 440px;
            padding: 40px 36px;
        }
        .fp-brand { font-size: 13px; font-weight: 800; color: #999; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 28px; }
        .fp-steps { display: flex; align-items: center; gap: 0; margin-bottom: 28px; }
        .fp-step { display: flex; align-items: center; gap: 6px; }
        .fp-step-dot { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; }
        .fp-step-dot.active  { background: #333; color: #fff; }
        .fp-step-dot.pending { background: #e8e8e8; color: #999; }
        .fp-step-dot.done    { background: #333; color: #fff; }
        .fp-step-label { font-size: 11.5px; font-weight: 700; color: #999; }
        .fp-step-label.active { color: #333; }
        .fp-step-line { flex: 1; height: 2px; background: #e8e8e8; margin: 0 8px; }
        .fp-step-line.done { background: #333; }
        .fp-title { font-size: 22px; font-weight: 900; color: #111; margin-bottom: 6px; }
        .fp-subtitle { font-size: 13.5px; color: #666; margin-bottom: 8px; line-height: 1.5; }
        .fp-email-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: #f5f5f5; border: 1px solid #ddd; border-radius: 6px;
            padding: 5px 10px; font-size: 13px; font-weight: 700; color: #333;
            margin-bottom: 24px;
        }
        .fp-label { display: block; font-size: 13px; font-weight: 700; color: #333; margin-bottom: 6px; }
        .fp-code-input {
            width: 100%; padding: 14px 16px; border: 2px solid #ddd; border-radius: 8px;
            font-size: 28px; font-weight: 900; color: #111; letter-spacing: 8px;
            text-align: center; outline: none; transition: border-color 0.15s;
            font-family: monospace; box-sizing: border-box;
        }
        .fp-code-input:focus { border-color: #333; }
        .fp-code-input.error { border-color: #ef4444; }
        .fp-error { font-size: 12.5px; color: #ef4444; margin-top: 5px; display: flex; align-items: center; gap: 4px; }
        .fp-success { font-size: 12.5px; color: #16a34a; margin-top: 5px; display: flex; align-items: center; gap: 4px; background: #dcfce7; border: 1px solid #86efac; padding: 8px 12px; border-radius: 6px; margin-bottom: 8px; }
        .fp-expiry { font-size: 12.5px; color: #777; margin-top: 8px; display: flex; align-items: center; gap: 4px; }
        .fp-btn-primary {
            width: 100%; padding: 12px; background: #333; color: #fff; border: none;
            border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer;
            margin-top: 20px; transition: background 0.15s; display: flex;
            align-items: center; justify-content: center; gap: 8px;
        }
        .fp-btn-primary:hover { background: #111; }
        .fp-resend-row { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 16px; }
        .fp-resend-label { font-size: 13px; color: #777; }
        .fp-resend-btn {
            background: none; border: none; cursor: pointer; font-size: 13px;
            font-weight: 700; color: #333; text-decoration: underline; padding: 0;
        }
        .fp-resend-btn:hover { color: #111; }
        .fp-back { display: block; text-align: center; margin-top: 16px; font-size: 13px; color: #777; text-decoration: none; }
        .fp-back:hover { color: #333; }
    </style>
</head>
<body>
<div class="fp-wrap">
    <div class="fp-card">
        <div class="fp-brand">GMD South Phils</div>

        <!-- Step indicators -->
        <div class="fp-steps">
            <div class="fp-step">
                <div class="fp-step-dot done">✓</div>
                <span class="fp-step-label">Email</span>
            </div>
            <div class="fp-step-line done"></div>
            <div class="fp-step">
                <div class="fp-step-dot active">2</div>
                <span class="fp-step-label active">Verify</span>
            </div>
            <div class="fp-step-line"></div>
            <div class="fp-step">
                <div class="fp-step-dot pending">3</div>
                <span class="fp-step-label">Reset</span>
            </div>
        </div>

        <h2 class="fp-title">Verify Your Identity</h2>
        <p class="fp-subtitle">A 6-digit verification code has been sent to:</p>
        <div class="fp-email-badge">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            {{ session('fp_email') }}
        </div>

        @if(session('resent'))
        <div class="fp-success">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            A new verification code has been sent to your email.
        </div>
        @endif

        @if(session('code_sent'))
        <div class="fp-success">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Verification code sent successfully.
        </div>
        @endif

        <form method="POST" action="{{ route('password.verify.post') }}" id="verifyForm">
            @csrf
            <div>
                <label class="fp-label" for="code">Verification Code *</label>
                <input
                    type="text"
                    name="code"
                    id="code"
                    class="fp-code-input {{ $errors->has('code') ? 'error' : '' }}"
                    placeholder="000000"
                    maxlength="6"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    autocomplete="one-time-code"
                    autofocus
                    required>
                @error('code')
                <div class="fp-error">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    {{ $message }}
                </div>
                @enderror
                <div class="fp-expiry">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Code expires in 10 minutes from when it was sent.
                </div>
            </div>

            <button type="submit" class="fp-btn-primary" id="verifyBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Verify Code
            </button>
        </form>

        <div class="fp-resend-row">
            <span class="fp-resend-label">Didn't receive it?</span>
            <form method="POST" action="{{ route('password.resend') }}" style="display:inline;">
                @csrf
                <button type="submit" class="fp-resend-btn" id="resendBtn">Resend Code</button>
            </form>
        </div>

        <a href="{{ route('password.request') }}" class="fp-back">← Use a different email</a>
    </div>
</div>
<script>
    // Only allow digits in code input
    document.getElementById('code').addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    document.getElementById('verifyForm').addEventListener('submit', function () {
        var btn = document.getElementById('verifyBtn');
        btn.disabled = true;
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 0.8s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Verifying...';
    });
</script>
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>
</body>
</html>
