@extends('auth.layouts.recovery')

@section('title', 'Reset Password')
@section('step', '3')

@section('content')
    <h1 class="rc-title">Create a new password</h1>
    <p class="rc-sub">Choose a strong password for <strong>{{ session('fp_email') }}</strong>. You'll use it the next time you log in.</p>

    <form method="POST" action="{{ route('password.reset') }}" id="resetForm">
        @csrf

        <div class="rc-field">
            <label class="rc-label" for="password">New Password</label>
            <div class="rc-input-wrap">
                <i data-lucide="lock" class="rc-lead"></i>
                <input type="password" name="password" id="password"
                       class="rc-input {{ $errors->has('password') ? 'error' : '' }}"
                       placeholder="Enter a new password" autocomplete="new-password" required autofocus
                       aria-describedby="pwRules">
                <button type="button" class="rc-eye" data-toggle="password" aria-label="Show password"><i data-lucide="eye"></i></button>
            </div>
            <div class="rc-strength" id="pwStrength" data-score="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
            <div class="pw-reqs" id="pwRules">
                <span class="pw-req" id="req-len">8+ characters</span>
                <span class="pw-req" id="req-upper">Uppercase</span>
                <span class="pw-req" id="req-lower">Lowercase</span>
                <span class="pw-req" id="req-num">Number</span>
            </div>
            @error('password')
            <div class="rc-error" role="alert"><i data-lucide="circle-alert"></i><span>{{ $message }}</span></div>
            @enderror
        </div>

        <div class="rc-field">
            <label class="rc-label" for="password_confirmation">Confirm Password</label>
            <div class="rc-input-wrap">
                <i data-lucide="lock-keyhole" class="rc-lead"></i>
                <input type="password" name="password_confirmation" id="password_confirmation"
                       class="rc-input {{ $errors->has('password_confirmation') ? 'error' : '' }}"
                       placeholder="Re-enter the new password" autocomplete="new-password" required>
                <button type="button" class="rc-eye" data-toggle="password_confirmation" aria-label="Show password"><i data-lucide="eye"></i></button>
            </div>
            <div class="pw-reqs"><span class="pw-req" id="req-match">Passwords match</span></div>
            @error('password_confirmation')
            <div class="rc-error" role="alert"><i data-lucide="circle-alert"></i><span>{{ $message }}</span></div>
            @enderror
        </div>

        <button type="submit" class="rc-btn" id="resetBtn" disabled>
            <i data-lucide="check-circle-2"></i>
            Update Password
        </button>
    </form>

    <div class="rc-foot">
        <a href="{{ route('login') }}" class="rc-back"><i data-lucide="arrow-left"></i> Back to Login</a>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        // show / hide each password
        document.querySelectorAll('.rc-eye').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var field = document.getElementById(btn.dataset.toggle);
                var show  = field.type === 'password';
                field.type = show ? 'text' : 'password';
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                btn.innerHTML = '<i data-lucide="' + (show ? 'eye-off' : 'eye') + '"></i>';
                if (window.lucide) lucide.createIcons();
                field.focus();
            });
        });

        var pw       = document.getElementById('password');
        var conf     = document.getElementById('password_confirmation');
        var btn      = document.getElementById('resetBtn');
        var strength = document.getElementById('pwStrength');
        var matchEl  = document.getElementById('req-match');
        // the same four rules the server checks
        var rules = [
            { el: document.getElementById('req-len'),   ok: function (v) { return v.length >= 8; } },
            { el: document.getElementById('req-upper'), ok: function (v) { return /[A-Z]/.test(v); } },
            { el: document.getElementById('req-lower'), ok: function (v) { return /[a-z]/.test(v); } },
            { el: document.getElementById('req-num'),   ok: function (v) { return /[0-9]/.test(v); } }
        ];

        function check() {
            var v = pw.value, c = conf.value, score = 0;
            rules.forEach(function (r) {
                var ok = r.ok(v);
                if (ok) score++;
                r.el.classList.toggle('met', ok);
                r.el.classList.toggle('fail', v.length > 0 && !ok);
            });
            strength.dataset.score = v.length ? String(score) : '0';
            var match = v.length > 0 && v === c;
            matchEl.classList.toggle('met', match);
            matchEl.classList.toggle('fail', c.length > 0 && !match);
            conf.classList.toggle('error', c.length > 0 && !match);
            btn.disabled = !(score === 4 && match);
        }
        pw.addEventListener('input', check);
        conf.addEventListener('input', check);
        check();

        document.getElementById('resetForm').addEventListener('submit', function (e) {
            if (btn.disabled) { e.preventDefault(); return; }
            rcBusy(btn, 'Updating password…');
        });
    })();
</script>
@endpush
