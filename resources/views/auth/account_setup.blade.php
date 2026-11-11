<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up Your Account | GMD South Phils</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ @filemtime(public_path('css/login.css')) }}">
</head>
<body class="pg-auth-account-setup">
    <div class="as-wrap">
        <div class="as-brand">
            <img src="{{ asset('images/gmdlogo-circle.svg') }}" alt="GMD South Phils">
            <div>
                <div class="as-brand-name">GMD <span>South Phils</span></div>
                <div class="as-brand-sub">Client Side</div>
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
