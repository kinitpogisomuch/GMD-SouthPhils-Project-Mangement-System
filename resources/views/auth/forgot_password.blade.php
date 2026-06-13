<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | GMD South Phils</title>
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
        .fp-brand {
            font-size: 13px;
            font-weight: 800;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 28px;
        }
        .fp-steps {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 28px;
        }
        .fp-step {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .fp-step-dot {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
        }
        .fp-step-dot.active  { background: #333; color: #fff; }
        .fp-step-dot.pending { background: #e8e8e8; color: #999; }
        .fp-step-dot.done    { background: #333; color: #fff; }
        .fp-step-label {
            font-size: 11.5px;
            font-weight: 700;
            color: #999;
        }
        .fp-step-label.active { color: #333; }
        .fp-step-line {
            flex: 1;
            height: 2px;
            background: #e8e8e8;
            margin: 0 8px;
        }
        .fp-title { font-size: 22px; font-weight: 900; color: #111; margin-bottom: 6px; }
        .fp-subtitle { font-size: 13.5px; color: #666; margin-bottom: 28px; line-height: 1.5; }
        .fp-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #333;
            margin-bottom: 6px;
        }
        .fp-input {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            color: #111;
            outline: none;
            transition: border-color 0.15s;
            box-sizing: border-box;
        }
        .fp-input:focus { border-color: #333; }
        .fp-input.error { border-color: #ef4444; }
        .fp-error {
            font-size: 12.5px;
            color: #ef4444;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .fp-btn-primary {
            width: 100%;
            padding: 12px;
            background: #333;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .fp-btn-primary:hover { background: #111; }
        .fp-back {
            display: block;
            text-align: center;
            margin-top: 18px;
            font-size: 13px;
            color: #777;
            text-decoration: none;
        }
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
                <div class="fp-step-dot active">1</div>
                <span class="fp-step-label active">Email</span>
            </div>
            <div class="fp-step-line"></div>
            <div class="fp-step">
                <div class="fp-step-dot pending">2</div>
                <span class="fp-step-label">Verify</span>
            </div>
            <div class="fp-step-line"></div>
            <div class="fp-step">
                <div class="fp-step-dot pending">3</div>
                <span class="fp-step-label">Reset</span>
            </div>
        </div>

        <h2 class="fp-title">Forgot Password</h2>
        <p class="fp-subtitle">Enter the email address associated with your account and we'll send you a verification code.</p>

        <form method="POST" action="{{ route('password.email') }}" id="emailForm">
            @csrf
            <div>
                <label class="fp-label" for="email">Email Address *</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    class="fp-input {{ $errors->has('email') ? 'error' : '' }}"
                    placeholder="Enter your registered email"
                    value="{{ old('email') }}"
                    required
                    autofocus>
                @error('email')
                <div class="fp-error">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    {{ $message }}
                </div>
                @enderror
            </div>

            <button type="submit" class="fp-btn-primary" id="submitBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Send Verification Code
            </button>
        </form>

        <a href="{{ route('login') }}" class="fp-back">← Back to Login</a>
    </div>
</div>
<script>
    document.getElementById('emailForm').addEventListener('submit', function () {
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 0.8s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Sending...';
    });
</script>
<style>
    @keyframes spin { to { transform: rotate(360deg); } }
</style>
</body>
</html>
