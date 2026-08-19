<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.employee.header')

    <main class="admin-content">

            <div class="page-header">
                <div>
                    <h1>Settings</h1>
                    <p>Manage your profile and account security.</p>
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
                <button class="emp-tab active" data-tab="profile">
                    <i data-lucide="user"></i>
                    My Profile
                </button>
                <button class="emp-tab" data-tab="password">
                    <i data-lucide="lock"></i>
                    Security
                </button>
            </div>

            <!-- ===== TAB: PROFILE ===== -->
            <div class="emp-tab-content active" id="tab-profile">
                <div class="settings-layout">

                    <!-- Left: Avatar Card -->
                    <div class="settings-avatar-card">
                        <div class="settings-avatar" id="avatarDisplay">
                            @if(session('profile_photo'))
                                <img src="{{ session('profile_photo') }}" alt="Profile"
                                     style="width:100%;height:100%;object-fit:cover;">
                            @else
                                <span style="font-size:32px;font-weight:900;color:var(--white);">
                                    {{ strtoupper(substr($employee->last_name ?: $employee->first_name, 0, 1)) }}
                                </span>
                            @endif
                        </div>
                        <div class="settings-avatar-name">{{ $employee->full_name }}</div>
                        <div class="settings-avatar-role">Employee</div>

                        <form method="POST" action="{{ route('employee.settings.photo') }}"
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
                                <i data-lucide="hard-hat"></i>
                                <span>{{ $employee->role ?? 'Employee' }}</span>
                            </div>
                            <div class="settings-meta-item">
                                <i data-lucide="calendar"></i>
                                <span>Member since {{ $employee->created_at?->format('Y') }}</span>
                            </div>
                            @if($employee->province || $employee->city)
                            <div class="settings-meta-item">
                                <i data-lucide="map-pin"></i>
                                <span>{{ implode(', ', array_filter([$employee->province, $employee->city])) }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Right: Profile Form -->
                    <div style="flex:1;">
                        <div class="pv-card">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                                <h3 class="pv-card-title" style="margin-bottom:0;">Profile Information</h3>
                                <div id="profileActions">
                                    <button type="button" class="save-btn" onclick="enableEdit()">
                                        <i data-lucide="pencil"></i>
                                        Edit Profile
                                    </button>
                                </div>
                                <div id="profileEditActions" style="display:none;gap:8px;">
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

                            <form method="POST" action="{{ route('employee.settings.profile') }}" id="profileForm">
                                @csrf
                                @method('PUT')
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>First Name</label>
                                        <input type="text" name="first_name" class="profile-field"
                                               value="{{ old('first_name', $employee->first_name) }}"
                                               placeholder="First name" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Last Name</label>
                                        <input type="text" name="last_name" class="profile-field"
                                               value="{{ old('last_name', $employee->last_name) }}"
                                               placeholder="Last name" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Username <span style="font-size:11px;color:var(--muted);font-weight:400;">(cannot be changed)</span></label>
                                        <input type="text" value="{{ $employee->username }}" disabled
                                               style="background:var(--surface-2);color:var(--muted);cursor:not-allowed;">
                                    </div>
                                    <div class="form-group">
                                        <label>Email Address</label>
                                        <input type="email" name="email" class="profile-field"
                                               value="{{ old('email', $employee->email) }}"
                                               placeholder="Email address" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="text" name="contact" class="profile-field"
                                               value="{{ old('contact', $employee->contact) }}"
                                               placeholder="e.g. 09XX XXX XXXX" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Role <span style="font-size:11px;color:var(--muted);font-weight:400;">(cannot be changed)</span></label>
                                        <input type="text" value="{{ $employee->role ?? 'Employee' }}" disabled
                                               style="background:var(--surface-2);color:var(--muted);cursor:not-allowed;">
                                    </div>
                                </div>

                                <div style="border-top:1px solid var(--border);margin:18px 0 16px;"></div>
                                <div style="font-size:12px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:0.07em;margin-bottom:14px;">Address Information</div>
                                <div class="form-grid">
                                    <div class="form-group" id="regionGroup">
                                        <label>Region</label>
                                        <select name="region" id="regionSelect" class="profile-field" disabled>
                                            @if(old('region', $employee->region))
                                            <option value="{{ old('region', $employee->region) }}">{{ old('region', $employee->region) }}</option>
                                            @else
                                            <option value="">— Not set —</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="form-group" id="provinceGroup">
                                        <label>Province</label>
                                        <select name="province" id="provinceSelect" class="profile-field" disabled>
                                            @if(old('province', $employee->province))
                                            <option value="{{ old('province', $employee->province) }}">{{ old('province', $employee->province) }}</option>
                                            @else
                                            <option value="">— Not set —</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="form-group" id="cityGroup">
                                        <label>City / Municipality</label>
                                        <select name="city" id="citySelect" class="profile-field" disabled>
                                            @if(old('city', $employee->city))
                                            <option value="{{ old('city', $employee->city) }}">{{ old('city', $employee->city) }}</option>
                                            @else
                                            <option value="">— Not set —</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Street Address</label>
                                        <input type="text" name="street_address" class="profile-field"
                                               value="{{ old('street_address', $employee->street_address) }}"
                                               placeholder="e.g. Poblacion Street" disabled>
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

                    <form method="POST" action="{{ route('employee.settings.password') }}" id="passwordForm">
                        @csrf
                        @method('PUT')
                        <div class="form-grid" style="grid-template-columns:repeat(3,1fr);">
                            <div class="form-group">
                                <label>Current Password</label>
                                <div class="password-input-wrap">
                                    <input type="password" name="current_password" id="currentPassword" placeholder="Enter current password">
                                    <button type="button" class="toggle-pw" data-target="currentPassword"><i data-lucide="eye"></i></button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <div class="password-input-wrap">
                                    <input type="password" name="new_password" id="newPassword" placeholder="Enter new password">
                                    <button type="button" class="toggle-pw" data-target="newPassword"><i data-lucide="eye"></i></button>
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
                                    <input type="password" name="new_password_confirmation" id="confirmPassword" placeholder="Confirm new password">
                                    <button type="button" class="toggle-pw" data-target="confirmPassword"><i data-lucide="eye"></i></button>
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

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/employee.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Tab switching
        @if($errors->hasBag('password') && $errors->getBag('password')->any())
        activateTab('password');
        @endif

        document.querySelectorAll('.emp-tab').forEach(function (btn) {
            btn.addEventListener('click', function () {
                activateTab(this.getAttribute('data-tab'));
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

        // Password strength
        var pwInput = document.getElementById('newPassword');
        var cfInput = document.getElementById('confirmPassword');
        if (pwInput) { pwInput.addEventListener('input', checkPw); cfInput.addEventListener('input', checkPw); }

        // If profile errors, open in edit mode
        @if($errors->hasBag('profile') && $errors->getBag('profile')->any())
        enableEdit();
        @endif

        // Avatar preview
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
    });

    function activateTab(name) {
        document.querySelectorAll('.emp-tab').forEach(function (b) {
            b.classList.toggle('active', b.getAttribute('data-tab') === name);
        });
        document.querySelectorAll('.emp-tab-content').forEach(function (p) {
            p.classList.toggle('active', p.id === 'tab-' + name);
        });
    }

    var originalValues = {};
    var psgcLoaded = false;

    const SAVED_REGION   = @json($employee->region ?? '');
    const SAVED_PROVINCE = @json($employee->province ?? '');
    const SAVED_CITY     = @json($employee->city ?? '');
    const PSGC_BASE      = 'https://psgc.cloud/api';

    function enableEdit() {
        document.querySelectorAll('#profileForm .profile-field').forEach(function (f) {
            originalValues[f.name] = f.value;
            if (f.tagName !== 'SELECT') f.disabled = false;
        });
        document.getElementById('profileActions').style.display = 'none';
        document.getElementById('profileEditActions').style.display = 'flex';
        if (!psgcLoaded) { psgcLoaded = true; psgcLoadRegions(true); }
        else { ['regionSelect','provinceSelect','citySelect'].forEach(function(id){ var s=document.getElementById(id); if(s)s.disabled=false; }); }
    }

    function cancelEdit() {
        document.querySelectorAll('#profileForm .profile-field').forEach(function (f) {
            if (f.tagName !== 'SELECT') { f.value = originalValues[f.name] ?? f.value; f.disabled = true; }
        });
        psgcResetToSaved('regionSelect',   SAVED_REGION);
        psgcResetToSaved('provinceSelect', SAVED_PROVINCE);
        psgcResetToSaved('citySelect',     SAVED_CITY);
        psgcLoaded = false;
        document.getElementById('profileActions').style.display = '';
        document.getElementById('profileEditActions').style.display = 'none';
    }

    function psgcFetch(url) { return fetch(url).then(function(r){ if(!r.ok) throw new Error(r.status); return r.json(); }); }

    function psgcBuildOptions(sel, items, val) {
        sel.innerHTML = '<option value="">-- Select --</option>';
        items.slice().sort(function(a,b){return a.name.localeCompare(b.name);}).forEach(function(item){
            var o = document.createElement('option');
            o.value = item.name; o.dataset.code = item.code; o.textContent = item.name;
            if (item.name === val) o.selected = true;
            sel.appendChild(o);
        });
        sel.disabled = false;
    }

    function psgcSetLoading(groupId, on) { var g=document.getElementById(groupId); if(g) g.classList.toggle('sel-loading', on); }
    function psgcReset(id, ph) { var s=document.getElementById(id); if(!s)return; s.innerHTML='<option value="">'+ph+'</option>'; s.disabled=true; }
    function psgcResetToSaved(id, val) {
        var s = document.getElementById(id); if(!s) return;
        s.innerHTML = val ? '<option value="'+val+'">'+val+'</option>' : '<option value="">— Not set —</option>';
        s.disabled = true;
    }

    async function psgcLoadRegions(restore) {
        var sel = document.getElementById('regionSelect');
        psgcSetLoading('regionGroup', true);
        try {
            var data = await psgcFetch(PSGC_BASE+'/regions');
            psgcBuildOptions(sel, data, SAVED_REGION);
            if (restore && SAVED_REGION) {
                var match = Array.from(sel.options).find(function(o){return o.value===SAVED_REGION;});
                if (match && match.dataset.code) await psgcLoadProvinces(match.dataset.code, true);
            }
        } catch(e) { sel.innerHTML='<option value="">Failed to load</option>'; sel.disabled=false; }
        psgcSetLoading('regionGroup', false);
    }

    async function psgcLoadProvinces(regionCode, restore) {
        var sel = document.getElementById('provinceSelect');
        psgcReset('citySelect','Select province first');
        psgcSetLoading('provinceGroup', true);
        sel.innerHTML='<option value="">Loading…</option>'; sel.disabled=true;
        try {
            var data = await psgcFetch(PSGC_BASE+'/regions/'+regionCode+'/provinces');
            if (data.length === 0) {
                await psgcLoadCitiesFromRegion(regionCode, restore);
                sel.innerHTML='<option value="NCR / No Province">NCR / No Province</option>';
                sel.value='NCR / No Province'; sel.disabled=false;
            } else {
                psgcBuildOptions(sel, data, SAVED_PROVINCE);
                if (restore && SAVED_PROVINCE) {
                    var match = Array.from(sel.options).find(function(o){return o.value===SAVED_PROVINCE;});
                    if (match && match.dataset.code) await psgcLoadCities(match.dataset.code, true);
                }
            }
        } catch(e) { sel.innerHTML='<option value="">Failed to load</option>'; sel.disabled=false; }
        psgcSetLoading('provinceGroup', false);
    }

    async function psgcLoadCitiesFromRegion(regionCode) {
        var sel = document.getElementById('citySelect');
        psgcSetLoading('cityGroup', true); sel.innerHTML='<option value="">Loading…</option>'; sel.disabled=true;
        try { var data = await psgcFetch(PSGC_BASE+'/regions/'+regionCode+'/cities-municipalities'); psgcBuildOptions(sel, data, SAVED_CITY); }
        catch(e) { sel.innerHTML='<option value="">Failed to load</option>'; sel.disabled=false; }
        psgcSetLoading('cityGroup', false);
    }

    async function psgcLoadCities(provinceCode) {
        var sel = document.getElementById('citySelect');
        psgcSetLoading('cityGroup', true); sel.innerHTML='<option value="">Loading…</option>'; sel.disabled=true;
        try { var data = await psgcFetch(PSGC_BASE+'/provinces/'+provinceCode+'/cities-municipalities'); psgcBuildOptions(sel, data, SAVED_CITY); }
        catch(e) { sel.innerHTML='<option value="">Failed to load</option>'; sel.disabled=false; }
        psgcSetLoading('cityGroup', false);
    }

    document.getElementById('regionSelect').addEventListener('change', function(){
        var code = this.options[this.selectedIndex]?.dataset?.code;
        if (code) psgcLoadProvinces(code, false);
        else { psgcReset('provinceSelect','Select region first'); psgcReset('citySelect','Select province first'); }
    });
    document.getElementById('provinceSelect').addEventListener('change', function(){
        var val = this.value, code = this.options[this.selectedIndex]?.dataset?.code;
        if (val === 'NCR / No Province') return;
        if (code) psgcLoadCities(code, false);
        else psgcReset('citySelect','Select province first');
    });

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
        el.classList.toggle('fail', !met && document.getElementById('newPassword').value.length > 0);
    }
    </script>

    <style>
        .profile-field:disabled { background: var(--surface-2); color: var(--text-secondary); cursor: default; border-color: var(--border); }
        select.profile-field { appearance:none;-webkit-appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; padding-right:34px; }
        select.profile-field:disabled { background-color:var(--surface-2); cursor:not-allowed; }
        .sel-loading { position:relative; }
        .sel-loading::after { content:''; position:absolute; right:34px; top:50%; transform:translateY(-50%); width:12px; height:12px; border:2px solid #e5e7eb; border-top-color:var(--dark); border-radius:50%; animation:psgcSpin 0.7s linear infinite; pointer-events:none; }
        @keyframes psgcSpin { to { transform:translateY(-50%) rotate(360deg); } }
        .pw-req { font-size:11.5px;padding:3px 9px;border-radius:99px;border:1px solid #e5e7eb;color:#9ca3af;background:#f9fafb;display:inline-flex;align-items:center;gap:4px;transition:all 0.15s; }
        .pw-req.met  { background:#dcfce7;border-color:#86efac;color:#15803d; }
        .pw-req.fail { background:#fee2e2;border-color:#fca5a5;color:#dc2626; }
    </style>
</body>
</html>
