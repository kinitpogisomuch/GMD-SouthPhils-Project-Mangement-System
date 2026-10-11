<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up Your Account | GMD South Phils</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <style>
        body {
            min-height: 100vh; margin: 0;
            display: flex; align-items: center; justify-content: center;
            padding: 32px 16px; box-sizing: border-box;
            background: radial-gradient(circle at 50% 0%, #ffffff 0%, var(--cream) 60%);
        }
        .as-wrap { width: 100%; max-width: 460px; animation: asRise .7s cubic-bezier(.22, 1, .36, 1) both; }
        @keyframes asRise { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: none; } }

        .as-brand { display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 22px; }
        .as-brand img { width: 44px; height: 44px; border-radius: 50%; }
        .as-brand-name { font-size: 19px; font-weight: 900; color: var(--dark); letter-spacing: -.3px; }
        .as-brand-name span { color: var(--muted); }
        .as-brand-sub { font-size: 10.5px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: .14em; margin-top: 2px; }

        .as-card { background: var(--white); border: 1px solid var(--border); border-radius: 24px; box-shadow: 0 18px 40px var(--shadow); padding: 30px 28px 26px; }
        .as-icon { width: 56px; height: 56px; border-radius: 16px; background: #E7F6EC; color: #207A3A; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
        .as-icon svg { width: 26px; height: 26px; }
        .as-title { text-align: center; font-size: 21px; font-weight: 900; color: var(--dark); letter-spacing: -.4px; margin: 0 0 6px; }
        .as-sub { text-align: center; font-size: 13.5px; line-height: 1.6; color: var(--muted); margin: 0 0 22px; }
        .as-sub strong { color: var(--dark); }

        .as-group { margin-bottom: 16px; }
        .as-group label { display: block; font-size: 13px; font-weight: 700; color: var(--dark); margin-bottom: 7px; }
        .as-hint { font-size: 11.5px; color: var(--muted); margin-top: 5px; display: block; }

        /* read-only username — the identifier they'll log in with */
        .as-username {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            height: 48px; padding: 0 14px; border-radius: 14px; box-sizing: border-box;
            background: var(--cream); border: 1px dashed var(--border);
            font-size: 15px; font-weight: 900; color: var(--dark); letter-spacing: 1.5px;
        }
        .as-username .lock { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; color: var(--muted); letter-spacing: 0; }
        .as-username .lock svg { width: 13px; height: 13px; }

        .as-pw { position: relative; }
        .as-pw input {
            width: 100%; height: 48px; padding: 0 46px 0 14px; box-sizing: border-box;
            border: 1px solid var(--border); border-radius: 14px; background: var(--cream);
            font-size: 13.5px; color: var(--dark); outline: none; transition: .2s ease;
        }
        .as-pw input:focus { background: var(--white); border-color: var(--dark); box-shadow: 0 0 0 4px rgba(0, 0, 0, .08); }
        .as-pw input.input-error { border-color: #fca5a5; }
        .as-pw-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--muted); padding: 4px; display: flex; }
        .as-pw-toggle svg { width: 16px; height: 16px; }

        .as-reqs { display: flex; flex-wrap: wrap; gap: 5px; margin: -4px 0 18px; }
        .as-req { font-size: 10.5px; padding: 3px 8px; border-radius: 999px; border: 1px solid var(--border); color: var(--muted); background: var(--cream); font-weight: 600; white-space: nowrap; transition: .2s; }
        .as-req.met  { background: #E7F6EC; border-color: #86efac; color: #207A3A; }
        .as-req.fail { background: var(--danger-bg); border-color: #fca5a5; color: var(--danger); }

        .field-error { font-size: 11px; color: var(--danger); margin-top: 5px; display: block; font-weight: 500; line-height: 1.45; }

        .as-submit {
            width: 100%; height: 50px; border: none; border-radius: 14px; cursor: pointer;
            background: var(--dark); color: var(--white); font-size: 14.5px; font-weight: 900;
            display: flex; align-items: center; justify-content: center; gap: 8px; transition: .2s ease;
        }
        .as-submit:hover { background: var(--dark-soft); transform: translateY(-1px); }
        .as-submit:disabled { background: var(--muted); cursor: not-allowed; transform: none; }
        .as-submit svg { width: 17px; height: 17px; }

        .as-foot { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 18px; font-size: 11.5px; color: var(--muted); }
        .as-foot svg { width: 13px; height: 13px; }
        @media (max-width: 480px) { .as-card { padding: 26px 20px 22px; border-radius: 20px; } }
    </style>
</head>
<body>
    <div class="as-wrap">
        <div class="as-brand">
            <img src="{{ asset('images/gmdlogo-circle.svg') }}" alt="GMD South Phils">
            <div>
                <div class="as-brand-name">GMD <span>South Phils</span></div>
                <div class="as-brand-sub">Client Portal</div>
            </div>
        </div>

        <div class="as-card">
            <div class="as-icon"><i data-lucide="user-check"></i></div>
            <h1 class="as-title">Set Up Your Account</h1>
            <p class="as-sub">Welcome, <strong>{{ $client->name }}</strong>! Your registration has been approved. Create a password to finish setting up your account.</p>

            <form method="POST" action="{{ route('account.setup.complete', ['token' => $token]) }}" id="setupForm" novalidate>
                @csrf

                <div class="as-group">
                    <label>Username</label>
                    <div class="as-username" aria-readonly="true">
                        <span>{{ $client->username }}</span>
                        <span class="lock"><i data-lucide="lock"></i>Assigned</span>
                    </div>
                    <span class="as-hint">This is your username for logging in. It can't be changed.</span>
                </div>

                <div class="as-group">
                    <label for="setupPassword">Password</label>
                    <div class="as-pw">
                        <input type="password" name="password" id="setupPassword" autocomplete="new-password" required
                               placeholder="Create a password" class="{{ $errors->has('password') ? 'input-error' : '' }}">
                        <button type="button" class="as-pw-toggle" data-target="setupPassword" aria-label="Show password"><i data-lucide="eye"></i></button>
                    </div>
                    <span class="field-error" id="passwordError" style="{{ $errors->has('password') ? '' : 'display:none;' }}">{{ $errors->first('password') }}</span>
                </div>

                <div class="as-group">
                    <label for="setupPasswordConfirm">Confirm Password</label>
                    <div class="as-pw">
                        <input type="password" name="password_confirmation" id="setupPasswordConfirm" autocomplete="new-password" required
                               placeholder="Repeat your password">
                        <button type="button" class="as-pw-toggle" data-target="setupPasswordConfirm" aria-label="Show password"><i data-lucide="eye"></i></button>
                    </div>
                    <span class="field-error" id="confirmError" style="display:none;"></span>
                </div>

                <div class="as-reqs">
                    <span class="as-req" id="req-len">Min 8 characters</span>
                    <span class="as-req" id="req-upper">1 uppercase letter</span>
                    <span class="as-req" id="req-lower">1 lowercase letter</span>
                    <span class="as-req" id="req-num">1 number</span>
                    <span class="as-req" id="req-match">Passwords match</span>
                </div>

                <button type="submit" class="as-submit" id="setupSubmit">
                    <i data-lucide="check-circle-2"></i><span>Complete Account Setup</span>
                </button>
            </form>
        </div>

        <div class="as-foot">
            <i data-lucide="shield-check"></i>
            <span>Secured access · GMD South Phils Metal Fabrication Works</span>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();

        (function () {
            var pw      = document.getElementById('setupPassword');
            var confirm = document.getElementById('setupPasswordConfirm');
            var pwErr   = document.getElementById('passwordError');
            var cfErr   = document.getElementById('confirmError');
            var RULES = {
                'req-len':   function (v) { return v.length >= 8; },
                'req-upper': function (v) { return /[A-Z]/.test(v); },
                'req-lower': function (v) { return /[a-z]/.test(v); },
                'req-num':   function (v) { return /[0-9]/.test(v); },
            };

            function setMsg(el, input, msg) {
                el.textContent = msg;
                el.style.display = msg ? 'block' : 'none';
                if (input) input.classList.toggle('input-error', !!msg);
            }
            function refreshReqs() {
                var v = pw.value;
                Object.keys(RULES).forEach(function (id) {
                    var ok = RULES[id](v), el = document.getElementById(id);
                    el.classList.toggle('met', ok);
                    el.classList.toggle('fail', !ok && v.length > 0);
                });
                var m = document.getElementById('req-match');
                m.classList.toggle('met', confirm.value.length > 0 && confirm.value === v);
                m.classList.toggle('fail', confirm.value.length > 0 && confirm.value !== v);
            }
            function passwordProblem() {
                var v = pw.value;
                if (!v) return 'Please create a password.';
                if (!RULES['req-len'](v))   return 'Password must be at least 8 characters.';
                if (!RULES['req-upper'](v)) return 'Password must contain at least one uppercase letter.';
                if (!RULES['req-lower'](v)) return 'Password must contain at least one lowercase letter.';
                if (!RULES['req-num'](v))   return 'Password must contain at least one number.';
                return '';
            }
            function confirmProblem() {
                if (!confirm.value) return 'Please confirm your password.';
                return confirm.value === pw.value ? '' : 'Passwords do not match.';
            }

            pw.addEventListener('input', function () { refreshReqs(); if (pwErr.textContent) setMsg(pwErr, pw, passwordProblem()); });
            confirm.addEventListener('input', function () { refreshReqs(); if (cfErr.textContent) setMsg(cfErr, confirm, confirmProblem()); });
            pw.addEventListener('blur', function () { if (pw.value) setMsg(pwErr, pw, passwordProblem()); });
            confirm.addEventListener('blur', function () { if (confirm.value) setMsg(cfErr, confirm, confirmProblem()); });

            document.querySelectorAll('.as-pw-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var input = document.getElementById(btn.dataset.target);
                    var show  = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                    btn.innerHTML = '<i data-lucide="' + (show ? 'eye-off' : 'eye') + '"></i>';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            });

            // Clear messages for anything left incomplete; the server re-checks everything
            document.getElementById('setupForm').addEventListener('submit', function (e) {
                var p = passwordProblem(), c = confirmProblem();
                setMsg(pwErr, pw, p);
                setMsg(cfErr, confirm, p ? '' : c);
                if (p || c) { e.preventDefault(); (p ? pw : confirm).focus(); return; }
                var btn = document.getElementById('setupSubmit');
                btn.disabled = true;
                btn.innerHTML = '<div class="btn-spinner"></div><span>Setting up your account...</span>';
            });
        })();
    </script>
</body>
</html>
