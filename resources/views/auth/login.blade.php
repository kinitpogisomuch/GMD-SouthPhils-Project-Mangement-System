<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | GMD South Phils</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>

    @php
    $loginBgPath = public_path('images/login.jpg');
    $hasLoginBg = file_exists($loginBgPath);
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
                <div class="login-header">
                    <h2>Welcome Back</h2>
                    <p>Login to continue to your portal.</p>
                </div>

                @if(session('error'))
                <div class="error-message">
                    <i data-lucide="circle-alert"></i>
                    <span>{{ session('error') }}</span>
                </div>
                @endif

                @if(session('success'))
                <div class="success-message">
                    <i data-lucide="check-circle"></i>
                    <span>{{ session('success') }}</span>
                </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf
                    <div class="form-group">
                        <label>Email / Username</label>
                        <div class="input-wrapper">
                            <i data-lucide="user"></i>
                            <input type="text" name="email" required placeholder="Enter your email or username"
                                   value="{{ old('email') }}" autofocus>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <i data-lucide="lock"></i>
                            <input type="password" name="password" id="loginPassword" required placeholder="Enter your password">
                            <button type="button" id="toggleLoginPw" class="pw-toggle-btn" aria-label="Show password">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                        <div class="form-extra">
                            <a href="{{ route('password.request') }}" class="forgot-link">
                                Forgot Password?
                            </a>
                        </div>
                    </div>

                    <button type="submit" class="login-btn">
                        <span>Login</span>
                    </button>
                </form>

                <div class="form-extra" style="text-align:center;margin-top:16px;">
                    <span style="color:var(--muted);font-size:12.5px;">New client? </span>
                    <a href="{{ route('signup') }}" class="forgot-link" style="color:var(--dark);">Sign Up</a>
                </div>

                <div class="login-footer">
                    <i data-lucide="shield-check"></i>
                    <span>Secured access · GMD South Phils Metal Fabrication Works</span>
                </div>
            </div>
        </div>

        <div class="login-left-hazard"></div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        if (typeof lucide !== "undefined") lucide.createIcons();

        document.getElementById('toggleLoginPw').addEventListener('click', function () {
            var input = document.getElementById('loginPassword');
            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            this.innerHTML = '<i data-lucide="' + (isHidden ? 'eye-off' : 'eye') + '"></i>';
            this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });

        // Login button loading state on submit
        document.querySelector('form').addEventListener('submit', function (e) {
            var btn = document.querySelector('.login-btn');
            btn.disabled = true;
            btn.innerHTML = '<div class="btn-spinner"></div><span>Logging in...</span>';
        });
    </script>
</body>
</html>