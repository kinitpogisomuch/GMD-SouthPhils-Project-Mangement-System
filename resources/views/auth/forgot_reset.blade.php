<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | GMD South Phils</title>
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
        .fp-subtitle { font-size: 13.5px; color: #666; margin-bottom: 24px; line-height: 1.5; }
        .fp-label { display: block; font-size: 13px; font-weight: 700; color: #333; margin-bottom: 6px; }
        .fp-field { margin-bottom: 16px; }
        .fp-input-wrap { position: relative; }
        .fp-input {
            width: 100%; padding: 11px 42px 11px 14px; border: 1.5px solid #ddd;
            border-radius: 8px; font-size: 14px; color: #111; outline: none;
            transition: border-color 0.15s; box-sizing: border-box;
        }
        .fp-input:focus { border-color: #333; }
        .fp-input.error { border-color: #ef4444; }
        .fp-eye {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; color: #999; padding: 0;
            display: flex; align-items: center;
        }
        .fp-eye:hover { color: #333; }
        .fp-error { font-size: 12.5px; color: #ef4444; margin-top: 5px; display: flex; align-items: center; gap: 4px; }
        .pw-reqs {
            display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px;
        }
        .pw-req {
            font-size: 11.5px; padding: 3px 9px; border-radius: 99px;
            border: 1px solid #e5e7eb; color: #9ca3af; background: #f9fafb;
            display: flex; align-items: center; gap: 4px; transition: all 0.15s;
        }
        .pw-req.met  { background: #dcfce7; border-color: #86efac; color: #15803d; }
        .pw-req.fail { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }
        .fp-btn-primary {
            width: 100%; padding: 12px; background: #333; color: #fff; border: none;
            border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer;
            margin-top: 20px; transition: background 0.15s; display: flex;
            align-items: center; justify-content: center; gap: 8px;
        }
        .fp-btn-primary:hover:not(:disabled) { background: #111; }
        .fp-btn-primary:disabled { background: #999; cursor: not-allowed; }
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
                <div class="fp-step-dot done">✓</div>
                <span class="fp-step-label">Verify</span>
            </div>
            <div class="fp-step-line done"></div>
            <div class="fp-step">
                <div class="fp-step-dot active">3</div>
                <span class="fp-step-label active">Reset</span>
            </div>
        </div>

        <h2 class="fp-title">Reset Password</h2>
        <p class="fp-subtitle">Create a new password for <strong>{{ session('fp_email') }}</strong>.</p>

        <form method="POST" action="{{ route('password.reset') }}" id="resetForm">
            @csrf

            <!-- New Password -->
            <div class="fp-field">
                <label class="fp-label" for="password">New Password </label>
                <div class="fp-input-wrap">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="fp-input {{ $errors->has('password') ? 'error' : '' }}"
                        placeholder="Enter new password"
                        autocomplete="new-password"
                        required>
                    <button type="button" class="fp-eye" onclick="togglePw('password', 'eye1')">
                        <svg id="eye1" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                @error('password')
                <div class="fp-error">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    {{ $message }}
                </div>
                @enderror
                <div class="pw-reqs">
                    <span class="pw-req" id="req-len">Min 6 characters</span>
                    <span class="pw-req" id="req-upper">Uppercase</span>
                    <span class="pw-req" id="req-lower">Lowercase</span>
                    <span class="pw-req" id="req-num">Number</span>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="fp-field">
                <label class="fp-label" for="password_confirmation">Confirm Password </label>
                <div class="fp-input-wrap">
                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        class="fp-input"
                        placeholder="Re-enter new password"
                        autocomplete="new-password"
                        required>
                    <button type="button" class="fp-eye" onclick="togglePw('password_confirmation', 'eye2')">
                        <svg id="eye2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <div class="pw-req" id="req-match" style="margin-top:8px;display:inline-flex;">Passwords match</div>
                @error('password_confirmation')
                <div class="fp-error">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    {{ $message }}
                </div>
                @enderror
            </div>

            <button type="submit" class="fp-btn-primary" id="resetBtn" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v14a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Update Password
            </button>
        </form>
    </div>
</div>

<script>
    function togglePw(fieldId, iconId) {
        var field = document.getElementById(fieldId);
        var isHidden = field.type === 'password';
        field.type = isHidden ? 'text' : 'password';
        var icon = document.getElementById(iconId);
        icon.innerHTML = isHidden
            ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>'
            : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }

    var pwInput   = document.getElementById('password');
    var confInput = document.getElementById('password_confirmation');
    var resetBtn  = document.getElementById('resetBtn');

    var reqs = {
        len:   { el: document.getElementById('req-len'),   test: function(v) { return v.length >= 6; } },
        upper: { el: document.getElementById('req-upper'), test: function(v) { return /[A-Z]/.test(v); } },
        lower: { el: document.getElementById('req-lower'), test: function(v) { return /[a-z]/.test(v); } },
        num:   { el: document.getElementById('req-num'),   test: function(v) { return /[0-9]/.test(v); } },
    };
    var matchEl = document.getElementById('req-match');

    function checkAll() {
        var val     = pwInput.value;
        var confVal = confInput.value;
        var allMet  = true;

        Object.values(reqs).forEach(function (r) {
            var ok = r.test(val);
            r.el.classList.toggle('met', ok);
            r.el.classList.toggle('fail', val.length > 0 && !ok);
            if (!ok) allMet = false;
        });

        var match = val.length > 0 && val === confVal;
        matchEl.classList.toggle('met', match);
        matchEl.classList.toggle('fail', confVal.length > 0 && !match);
        if (!match) allMet = false;

        resetBtn.disabled = !(allMet && val.length > 0);
    }

    pwInput.addEventListener('input', checkAll);
    confInput.addEventListener('input', checkAll);

    document.getElementById('resetForm').addEventListener('submit', function () {
        resetBtn.disabled = true;
        resetBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 0.8s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Updating...';
    });
</script>
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>
</body>
</html>
