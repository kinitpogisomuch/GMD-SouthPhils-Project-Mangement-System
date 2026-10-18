@extends('auth.layouts.recovery')

@section('title', 'Verify Code')
@section('step', '2')

@section('content')
    <h1 class="rc-title">Check your email</h1>
    <p class="rc-sub">We sent a 6-digit verification code to:</p>
    <div class="rc-email"><i data-lucide="mail"></i>{{ session('fp_email') }}</div>

    @if(session('resent'))
    <div class="rc-notice" role="status"><i data-lucide="circle-check"></i> A new code is on its way — check your inbox.</div>
    @elseif(session('code_sent'))
    <div class="rc-notice" role="status"><i data-lucide="circle-check"></i> Code sent. It can take a minute to arrive — check spam too.</div>
    @endif

    <form method="POST" action="{{ route('password.verify.post') }}" id="verifyForm">
        @csrf
        <div class="rc-field">
            <label class="rc-label" id="codeLabel" for="otp0">Verification Code</label>
            {{-- six boxes for typing; the real field sent to the server is the hidden "code" below --}}
            <div class="rc-otp {{ $errors->has('code') ? 'error shake' : '' }}" id="otpBoxes" role="group" aria-labelledby="codeLabel">
                @for($i = 0; $i < 6; $i++)
                {{-- no maxlength: several digits arriving at once (autofill, some keyboards) are spread across the boxes by the script --}}
                <input type="text" id="otp{{ $i }}" inputmode="numeric"
                       autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}" aria-label="Digit {{ $i + 1 }} of 6"
                       @if($i === 0) autofocus @endif>
                @endfor
            </div>
            <input type="text" name="code" id="code" class="rc-code-hidden" tabindex="-1" aria-hidden="true" maxlength="6">

            <div class="rc-error" id="codeError" role="alert" @unless($errors->has('code')) hidden @endunless>
                <i data-lucide="circle-alert"></i><span>{{ $errors->first('code') }}</span>
            </div>

            <div class="rc-meta">
                <span><i data-lucide="clock"></i>Code expires in <span class="rc-timer" id="expiryTimer">10:00</span></span>
            </div>
        </div>

        <button type="submit" class="rc-btn" id="verifyBtn" disabled>
            Verify Code
            <i data-lucide="arrow-right"></i>
        </button>
    </form>

    <div class="rc-meta" style="justify-content:center;margin-top:16px;">
        <span>Didn't get it?</span>
        <form method="POST" action="{{ route('password.resend') }}" id="resendForm" style="display:inline;">
            @csrf
            <button type="submit" class="rc-link-btn" id="resendBtn">Resend code</button>
        </form>
    </div>

    <div class="rc-foot">
        <a href="{{ route('password.request') }}" class="rc-back"><i data-lucide="arrow-left"></i> Use a different email</a>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var boxes  = Array.prototype.slice.call(document.querySelectorAll('#otpBoxes input'));
        var group  = document.getElementById('otpBoxes');
        var hidden = document.getElementById('code');
        var form   = document.getElementById('verifyForm');
        var btn    = document.getElementById('verifyBtn');
        var error  = document.getElementById('codeError');
        var submitted = false;

        function value() { return boxes.map(function (b) { return b.value; }).join(''); }
        function sync() {
            var v = value();
            hidden.value = v;
            btn.disabled = v.length !== 6;
            boxes.forEach(function (b) { b.classList.toggle('filled', !!b.value); });
            return v;
        }
        function clearError() {
            group.classList.remove('error', 'shake');
            error.hidden = true;
        }
        function fill(from, digits) {
            for (var i = 0; i < digits.length && from + i < boxes.length; i++) boxes[from + i].value = digits[i];
            var next = Math.min(from + digits.length, boxes.length - 1);
            boxes[next].focus();
            boxes[next].select();
            if (sync().length === 6) autoSubmit();
        }
        // the moment all six digits are in, send it (once)
        function autoSubmit() {
            if (submitted) return;
            setTimeout(function () { if (value().length === 6 && !submitted) form.requestSubmit(btn); }, 180);
        }

        boxes.forEach(function (box, i) {
            box.addEventListener('input', function () {
                clearError();
                var digits = box.value.replace(/\D/g, '');
                box.value = '';
                if (digits) fill(i, digits);      // also handles phones that autofill the whole code into one box
                else sync();
            });
            box.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !box.value && i > 0) { boxes[i - 1].value = ''; boxes[i - 1].focus(); sync(); e.preventDefault(); }
                else if (e.key === 'ArrowLeft' && i > 0) { boxes[i - 1].focus(); e.preventDefault(); }
                else if (e.key === 'ArrowRight' && i < boxes.length - 1) { boxes[i + 1].focus(); e.preventDefault(); }
            });
            box.addEventListener('focus', function () { box.select(); });
            box.addEventListener('paste', function (e) {
                var text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                if (!text) return;
                e.preventDefault();
                clearError();
                fill(i === 0 || text.length === 6 ? 0 : i, text);
            });
        });

        form.addEventListener('submit', function (e) {
            if (sync().length !== 6) { e.preventDefault(); boxes[value().length].focus(); return; }
            submitted = true;
            rcBusy(btn, 'Verifying…');
        });

        // a wrong / expired code: start fresh in the first box
        if (!error.hidden) { boxes[0].focus(); }

        // ---- countdowns: code expiry and when "Resend code" becomes available ----
        var expiresIn = {{ (int) $expiresIn }};
        var resendIn  = {{ (int) $resendIn }};
        var timer     = document.getElementById('expiryTimer');
        var resendBtn = document.getElementById('resendBtn');
        var started   = Date.now();
        var expiredShown = false;

        function mmss(s) { return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); }
        function tick() {
            var gone = Math.floor((Date.now() - started) / 1000);
            var exp  = Math.max(0, expiresIn - gone);
            var res  = Math.max(0, resendIn - gone);

            if (exp > 0) {
                timer.textContent = mmss(exp);
                timer.classList.toggle('is-low', exp < 60);
            } else if (!expiredShown) {
                expiredShown = true;
                timer.parentNode.innerHTML = '<span class="rc-timer is-low">This code has expired</span> — request a new one below.';
            }

            resendBtn.disabled = res > 0;
            resendBtn.textContent = res > 0 ? 'Resend in ' + mmss(res) : 'Resend code';
            if (exp > 0 || res > 0) setTimeout(tick, 1000);
        }
        tick();

        document.getElementById('resendForm').addEventListener('submit', function () {
            resendBtn.disabled = true;
            resendBtn.textContent = 'Sending…';
        });
    })();
</script>
@endpush
