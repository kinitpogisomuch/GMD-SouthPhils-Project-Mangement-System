<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | GMD South Phils</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>

    <div class="login-page">
        <div class="login-left">
            <div class="brand-box">
                <div class="brand-icon">
                    <i data-lucide="factory"></i>
                </div>
                <h1>GMD South Phils</h1>
                <p>Project Management System for storage tank fabrication, project tracking, employee coordination, and supplier transactions.</p>
            </div>

            <div class="feature-list">
                <div class="feature-item">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Project Dashboard</span>
                </div>
                <div class="feature-item">
                    <i data-lucide="folder-kanban"></i>
                    <span>Admin Management</span>
                </div>
                <div class="feature-item">
                    <i data-lucide="truck"></i>
                    <span>Supplier Portal</span>
                </div>
                <div class="feature-item">
                    <i data-lucide="users"></i>
                    <span>Client Portal</span>
                </div>
                <div class="feature-item">
                    <i data-lucide="hard-hat"></i>
                    <span>Employee Portal</span>
                </div>
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

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf
                    <div class="form-group">
                        <label>Email / Username</label>
                        <div class="input-wrapper">
                            <i data-lucide="user"></i>
                            <input type="text" name="email" required placeholder="Enter your username"
                                   value="{{ old('email') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <i data-lucide="lock"></i>
                            <input type="password" name="password" id="loginPassword" required placeholder="Enter your password">
                            <button type="button" id="toggleLoginPw"
                                style="background:none;border:none;cursor:pointer;padding:0;display:flex;align-items:center;color:var(--muted);flex-shrink:0;">
                                <i data-lucide="eye" style="width:18px;height:18px;"></i>
                            </button>
                        </div>
                        <div style="text-align:right;margin-top:6px;">
                            <a href="{{ route('password.request') }}"
                               style="font-size:12.5px;font-weight:600;color:var(--muted);text-decoration:none;"
                               onmouseover="this.style.color='var(--dark)'" onmouseout="this.style.color='var(--muted)'">
                                Forgot Password?
                            </a>
                        </div>
                    </div>

                    <button type="submit" class="login-btn">
                        <span>Login</span>
                        <i data-lucide="arrow-right"></i>
                    </button>
                </form>

            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        if (typeof lucide !== "undefined") lucide.createIcons();

        document.getElementById('toggleLoginPw').addEventListener('click', function () {
            var input = document.getElementById('loginPassword');
            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            this.innerHTML = '<i data-lucide="' + (isHidden ? 'eye-off' : 'eye') + '" style="width:18px;height:18px;"></i>';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });

        // Login button loading state on submit
        document.querySelector('form').addEventListener('submit', function () {
            var btn = document.querySelector('.login-btn');
            btn.disabled = true;
            btn.innerHTML = '<div class="btn-spinner"></div><span>Logging in...</span>';
        });
    </script>
</body>
</html>