@extends('auth.layouts.recovery')

@section('title', 'Forgot Password')
@section('step', '1')

@section('content')
    <h1 class="rc-title">Forgot your password?</h1>
    <p class="rc-sub">No worries. Enter the email address on your account and we'll send you a 6-digit verification code.</p>

    <form method="POST" action="{{ route('password.email') }}" id="emailForm" novalidate>
        @csrf
        <div class="rc-field">
            <label class="rc-label" for="email">Email Address</label>
            <div class="rc-input-wrap">
                <i data-lucide="at-sign" class="rc-lead"></i>
                <input type="email" name="email" id="email"
                       class="rc-input {{ $errors->has('email') ? 'error' : '' }}"
                       placeholder="you@example.com" value="{{ old('email') }}"
                       autocomplete="email" required autofocus
                       @error('email') aria-invalid="true" aria-describedby="emailError" @enderror>
            </div>
            <div class="rc-error" id="emailError" role="alert" @unless($errors->has('email')) hidden @endunless>
                <i data-lucide="circle-alert"></i><span>{{ $errors->first('email') }}</span>
            </div>
        </div>

        <button type="submit" class="rc-btn" id="submitBtn">
            Send Verification Code
            <i data-lucide="arrow-right"></i>
        </button>
    </form>

    <div class="rc-foot">
        <a href="{{ route('login') }}" class="rc-back"><i data-lucide="arrow-left"></i> Back to Login</a>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var form  = document.getElementById('emailForm');
        var input = document.getElementById('email');
        var error = document.getElementById('emailError');
        var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

        function show(msg) {
            error.querySelector('span').textContent = msg;
            error.hidden = !msg;
            input.classList.toggle('error', !!msg);
        }
        input.addEventListener('input', function () { if (!error.hidden) show(''); });

        // a clear message under the field instead of the browser's bubble; the server checks again
        form.addEventListener('submit', function (e) {
            var v = input.value.trim();
            var msg = !v ? 'Please enter your email address.' : (!EMAIL.test(v) ? 'Please enter a valid email address.' : '');
            if (msg) { e.preventDefault(); show(msg); input.focus(); return; }
            rcBusy(document.getElementById('submitBtn'), 'Sending code…');
        });
    })();
</script>
@endpush
