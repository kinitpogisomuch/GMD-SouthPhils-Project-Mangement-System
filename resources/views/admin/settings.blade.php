<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            <div class="page-header">
                <div>
                    <h1>Settings</h1>
                    <p>Manage your profile, password, and system user accounts.</p>
                </div>
            </div>

            @if(session('success'))
            <div class="alert-banner success">
                <i data-lucide="check-circle"></i>
                {{ session('success') }}
            </div>
            @endif

            <!-- Settings Tabs -->
            <div class="emp-tabs">
                <button class="emp-tab" data-tab="profile">
                    <i data-lucide="user"></i>
                    My Profile
                </button>
                <button class="emp-tab" data-tab="password">
                    <i data-lucide="lock"></i>
                    Security
                </button>
                <button class="emp-tab" data-tab="users">
                    <i data-lucide="users"></i>
                    Manage Users
                </button>
            </div>

            <!-- ===== TAB: PROFILE ===== -->
            <div class="emp-tab-content" id="tab-profile">
                <div class="settings-layout">

                    <!-- Left: Avatar Card -->
                    <div class="settings-avatar-card">
                        <div class="settings-avatar" id="avatarDisplay">
                            @if(session('profile_photo'))
                                <img src="{{ session('profile_photo') }}" alt="Profile"
                                     style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                            @else
                                <span style="font-size:32px;font-weight:900;color:var(--white);">
                                    {{ strtoupper(substr($adminData->last_name ?: $adminData->first_name, 0, 1)) }}
                                </span>
                            @endif
                        </div>
                        <div class="settings-avatar-name">{{ $adminData->full_name ?: 'Admin' }}</div>
                        <div class="settings-avatar-role">Administrator</div>

                        <form method="POST" action="{{ route('admin.settings.photo') }}"
                              enctype="multipart/form-data" id="photoUploadForm">
                            @csrf
                            <input type="file" name="profile_photo" id="avatarInput"
                                   accept="image/jpeg,image/png,image/webp" style="display:none;">
                        </form>
                        <label class="avatar-change-btn" onclick="document.getElementById('avatarInput').click()" style="cursor:pointer;">
                            <i data-lucide="camera"></i>
                            Change Photo
                        </label>
                        <div class="settings-meta-list">
                            <div class="settings-meta-item">
                                <i data-lucide="shield"></i>
                                <span>Administrator</span>
                            </div>
                            <div class="settings-meta-item">
                                <i data-lucide="calendar"></i>
                                <span>Member since {{ $adminData->member_since }}</span>
                            </div>
                            <div class="settings-meta-item">
                                <i data-lucide="map-pin"></i>
                                <span>GMD South Phils</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Profile Form -->
                    <div style="flex:1;">
                        <div class="pv-card">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                                <h3 class="pv-card-title" style="margin-bottom:0;">Profile Information</h3>
                                <div id="profileActions">
                                    <button type="button" class="save-btn" id="editProfileBtn" onclick="enableEdit()">
                                        <i data-lucide="pencil"></i>
                                        Edit Profile
                                    </button>
                                </div>
                                <div id="profileEditActions" style="display:none;gap:8px;display:none;">
                                    <button type="button" class="cancel-btn" onclick="cancelEdit()">
                                        <i data-lucide="x"></i> Cancel
                                    </button>
                                    <button type="submit" form="profileForm" class="save-btn">
                                        <i data-lucide="save"></i> Save Changes
                                    </button>
                                </div>
                            </div>

                            @if($errors->hasBag('profile') && $errors->getBag('profile')->any())
                            <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px;margin-bottom:16px;color:#dc2626;font-size:13px;">
                                @foreach($errors->getBag('profile')->all() as $error)
                                    <div>• {{ $error }}</div>
                                @endforeach
                            </div>
                            @endif

                            <form method="POST" action="{{ route('admin.settings.profile') }}" id="profileForm">
                                @csrf
                                @method('PUT')
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>First Name</label>
                                        <input type="text" name="first_name" class="profile-field"
                                               value="{{ old('first_name', $adminData->first_name) }}"
                                               placeholder="First name" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Last Name</label>
                                        <input type="text" name="last_name" class="profile-field"
                                               value="{{ old('last_name', $adminData->last_name) }}"
                                               placeholder="Last name" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Username <span style="font-size:11px;color:var(--muted);font-weight:400;">(cannot be changed)</span></label>
                                        <input type="text" value="{{ $adminData->username }}" disabled
                                               style="background:var(--surface-2);color:var(--muted);cursor:not-allowed;">
                                    </div>
                                    <div class="form-group">
                                        <label>Email Address</label>
                                        <input type="email" name="email" class="profile-field"
                                               value="{{ old('email', $adminData->email) }}"
                                               placeholder="Email address" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="text" name="phone" class="profile-field"
                                               value="{{ old('phone', $adminData->phone) }}"
                                               placeholder="e.g. 09XX XXX XXXX" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Position / Role <span style="font-size:11px;color:var(--muted);font-weight:400;">(cannot be changed)</span></label>
                                        <input type="text" value="Administrator" disabled
                                               style="background:var(--surface-2);color:var(--muted);cursor:not-allowed;">
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ===== TAB: SECURITY ===== -->
            <div class="emp-tab-content" id="tab-password">
                <div class="pv-card">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
                        <div>
                            <h3 class="pv-card-title" style="margin-bottom:4px;">Security Settings</h3>
                            <p style="font-size:13px;color:var(--muted);margin:0;">
                                Password must be at least 6 characters with an uppercase letter, lowercase letter, and number.
                            </p>
                        </div>
                    </div>

                    @if($errors->hasBag('password') && $errors->getBag('password')->any())
                    <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px;margin-bottom:16px;color:#dc2626;font-size:13px;">
                        @foreach($errors->getBag('password')->all() as $error)
                            <div>• {{ $error }}</div>
                        @endforeach
                    </div>
                    @endif

                    <form method="POST" action="{{ route('admin.settings.password') }}" id="passwordForm">
                        @csrf
                        @method('PUT')
                        <div class="form-grid" style="grid-template-columns:repeat(3,1fr);">
                            <div class="form-group">
                                <label>Current Password</label>
                                <div class="password-input-wrap">
                                    <input type="password" name="current_password" id="currentPassword"
                                           placeholder="Enter current password">
                                    <button type="button" class="toggle-pw" data-target="currentPassword">
                                        <i data-lucide="eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <div class="password-input-wrap">
                                    <input type="password" name="new_password" id="newPassword"
                                           placeholder="Enter new password">
                                    <button type="button" class="toggle-pw" data-target="newPassword">
                                        <i data-lucide="eye"></i>
                                    </button>
                                </div>
                                <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:8px;">
                                    <span class="pw-req" id="req-len">Min 6 chars</span>
                                    <span class="pw-req" id="req-upper">Uppercase</span>
                                    <span class="pw-req" id="req-lower">Lowercase</span>
                                    <span class="pw-req" id="req-num">Number</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <div class="password-input-wrap">
                                    <input type="password" name="new_password_confirmation" id="confirmPassword"
                                           placeholder="Confirm new password">
                                    <button type="button" class="toggle-pw" data-target="confirmPassword">
                                        <i data-lucide="eye"></i>
                                    </button>
                                </div>
                                <span class="pw-req" id="req-match" style="margin-top:8px;display:inline-flex;">Passwords match</span>
                            </div>
                        </div>
                        <div class="settings-form-actions">
                            <button type="reset" class="cancel-btn"><i data-lucide="x"></i> Clear</button>
                            <button type="submit" class="save-btn"><i data-lucide="lock"></i> Update Password</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ===== TAB: USERS ===== -->
            <div class="emp-tab-content" id="tab-users">
                <div class="table-card">
                    <div class="table-toolbar">
                        <div class="search-box">
                            <i data-lucide="search"></i>
                            <input type="text" id="userSearch" placeholder="Search user...">
                        </div>
                    </div>
                    <div class="table-wrapper">
                        <table class="data-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Date Added</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar-sm" style="background:{{ $user->role === 'employee' ? '#6366f1' : '#e8900a' }}">
                                                {{ strtoupper(substr($user->full_name, 0, 1)) }}
                                            </div>
                                            {{ $user->full_name }}
                                        </div>
                                    </td>
                                    <td><span style="font-family:monospace;font-size:13px;font-weight:600;color:var(--text-secondary);">{{ $user->username }}</span></td>
                                    <td>{{ $user->email ?? '—' }}</td>
                                    <td>
                                        <span class="role-badge" style="{{ $user->role === 'employee' ? 'background:#ede9fe;color:#6d28d9;' : 'background:#fff7ed;color:#c2410c;' }}">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge {{ $user->status === 'Active' ? 'active' : 'archived' }}">
                                            {{ $user->status }}
                                        </span>
                                    </td>
                                    <td style="font-size:13px;color:var(--text-secondary);white-space:nowrap;">{{ $user->created_at->format('M j, Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">
                                        No user accounts yet.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <div class="toast" id="successToast">
        <i data-lucide="check-circle"></i>
        <span id="toastMsg">Saved successfully.</span>
    </div>

    <script>const ACTIVE_TAB = "{{ session('active_tab', 'profile') }}";</script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Activate tab
        document.querySelectorAll('.emp-tab').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === ACTIVE_TAB);
        });
        document.querySelectorAll('.emp-tab-content').forEach(function (pane) {
            pane.classList.toggle('active', pane.id === 'tab-' + ACTIVE_TAB);
        });
        document.querySelectorAll('.emp-tab').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.emp-tab').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.emp-tab-content').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('tab-' + this.getAttribute('data-tab')).classList.add('active');
            });
        });

        // If validation errors exist on profile, open in edit mode
        @if($errors->hasBag('profile') && $errors->getBag('profile')->any())
        enableEdit();
        @endif

        // User search
        document.getElementById('userSearch').addEventListener('keyup', function () {
            var q = this.value.toLowerCase();
            document.querySelectorAll('#usersTable tbody tr').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });

        // Password toggles
        document.querySelectorAll('.toggle-pw').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var field = document.getElementById(this.dataset.target);
                field.type = field.type === 'password' ? 'text' : 'password';
                this.querySelector('i').setAttribute('data-lucide', field.type === 'password' ? 'eye' : 'eye-off');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        });

        // Password strength indicators
        var pwInput   = document.getElementById('newPassword');
        var confInput = document.getElementById('confirmPassword');
        if (pwInput) {
            pwInput.addEventListener('input', checkPw);
            if (confInput) confInput.addEventListener('input', checkPw);
        }
    });

    // --- Profile edit/cancel ---
    var originalValues = {};

    function enableEdit() {
        document.querySelectorAll('#profileForm .profile-field').forEach(function (f) {
            originalValues[f.name] = f.value;
            f.disabled = false;
        });
        document.getElementById('profileActions').style.display = 'none';
        document.getElementById('profileEditActions').style.display = 'flex';
    }

    function cancelEdit() {
        document.querySelectorAll('#profileForm .profile-field').forEach(function (f) {
            f.value = originalValues[f.name] ?? f.value;
            f.disabled = true;
        });
        document.getElementById('profileActions').style.display = '';
        document.getElementById('profileEditActions').style.display = 'none';
    }

    // --- Password requirements ---
    function checkPw() {
        var v = document.getElementById('newPassword').value;
        var c = document.getElementById('confirmPassword').value;
        setReq('req-len',   v.length >= 6);
        setReq('req-upper', /[A-Z]/.test(v));
        setReq('req-lower', /[a-z]/.test(v));
        setReq('req-num',   /[0-9]/.test(v));
        setReq('req-match', v.length > 0 && v === c);
    }
    function setReq(id, met) {
        var el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('met', met);
        el.classList.toggle('fail', !met && (document.getElementById('newPassword').value.length > 0));
    }

    // --- Photo preview + upload ---
    document.getElementById('avatarInput').addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('avatarDisplay').innerHTML =
                '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
        };
        reader.readAsDataURL(file);
        document.getElementById('photoUploadForm').submit();
    });

    </script>

    <style>
        .profile-field:disabled {
            background: var(--surface-2);
            color: var(--text-secondary);
            cursor: default;
            border-color: var(--border);
        }
        select.profile-field {
            appearance: none; -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center; padding-right: 34px;
        }
        select.profile-field:disabled { background-color: var(--surface-2); cursor: not-allowed; }
        .sel-loading { position: relative; }
        .sel-loading::after {
            content: ''; position: absolute; right: 34px; top: 50%; transform: translateY(-50%);
            width: 12px; height: 12px; border: 2px solid #e5e7eb; border-top-color: var(--dark);
            border-radius: 50%; animation: psgcSpin 0.7s linear infinite; pointer-events: none;
        }
        @keyframes psgcSpin { to { transform: translateY(-50%) rotate(360deg); } }
        #profileEditActions { display: none; gap: 8px; }
        .pw-req {
            font-size: 11.5px; padding: 3px 9px; border-radius: 99px;
            border: 1px solid #e5e7eb; color: #9ca3af; background: #f9fafb;
            display: inline-flex; align-items: center; gap: 4px; transition: all 0.15s;
        }
        .pw-req.met  { background: #dcfce7; border-color: #86efac; color: #15803d; }
        .pw-req.fail { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }
    </style>
</body>
</html>
