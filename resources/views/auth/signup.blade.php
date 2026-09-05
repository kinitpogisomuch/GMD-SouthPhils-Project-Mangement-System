<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | GMD South Phils</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 48px 16px 60px;
        }

        @keyframes slideUpFadeIn {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .signup-wrap {
            width: 100%; max-width: 620px;
            animation: slideUpFadeIn 0.9s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .signup-brand {
            display: flex; align-items: center; justify-content: center; gap: 14px;
            margin-bottom: 28px;
        }
        .signup-brand-logo {
            width: 52px; height: 52px; border-radius: 50%; object-fit: cover; flex-shrink: 0;
        }
        .signup-brand-name { font-size: 22px; font-weight: 900; color: var(--dark); letter-spacing: -0.4px; }
        .signup-brand-name span { color: var(--muted); }
        .signup-brand-sub {
            font-size: 11px; font-weight: 800; color: var(--muted);
            text-transform: uppercase; letter-spacing: 0.14em; margin-top: 4px;
        }

        .signup-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 12px 32px var(--shadow);
            overflow: hidden;
            margin-bottom: 18px;
        }
        .signup-section-head {
            padding: 16px 24px;
            background: var(--cream);
            border-bottom: 1px solid var(--border);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--dark);
            display: flex; align-items: center; gap: 8px;
        }
        .signup-section-head i { color: var(--accent-dark); }
        .signup-body-pad { padding: 24px; }

        .su-form-group { margin-bottom: 16px; }
        .su-form-group:last-child { margin-bottom: 0; }
        .su-form-group label { font-size: 13px; font-weight: 700; color: var(--dark); display: block; margin-bottom: 8px; }
        .su-form-group input {
            width: 100%; height: 48px; padding: 0 14px;
            border: 1px solid var(--border); border-radius: 14px;
            font-size: 13.5px; color: var(--dark); background: var(--cream);
            outline: none; transition: 0.22s ease;
            box-sizing: border-box;
        }
        .su-form-group input:focus {
            background: var(--white); border-color: var(--dark);
            box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.08);
        }
        .su-form-group input.input-error { border-color: #fca5a5; }
        .field-error { font-size: 12px; color: var(--danger); margin-top: 6px; display: block; font-weight: 600; }

        .su-form-group select {
            width: 100%; height: 48px; padding: 0 40px 0 14px;
            border: 1px solid var(--border); border-radius: 14px;
            font-size: 13.5px; color: var(--dark); background-color: var(--cream);
            outline: none; transition: 0.22s ease;
            box-sizing: border-box; appearance: none; -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23666666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }
        .su-form-group select:focus {
            background-color: var(--white); border-color: var(--dark);
            box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.08);
        }
        .su-form-group select.input-error { border-color: #fca5a5; }
        .su-form-group select:disabled { background-color: var(--cream); color: var(--muted); cursor: not-allowed; }
        .sel-loading { position: relative; }
        .sel-loading::after {
            content: '';
            position: absolute; right: 40px; top: 38px;
            width: 14px; height: 14px;
            border: 2px solid var(--border); border-top-color: var(--dark);
            border-radius: 50%; animation: spin 0.7s linear infinite;
            pointer-events: none;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .username-group {
            display: flex; align-items: stretch;
            border: 1px solid var(--border); border-radius: 14px;
            background: var(--cream); overflow: hidden;
            transition: 0.22s ease;
        }
        .username-group:focus-within {
            background: var(--white); border-color: var(--dark);
            box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.08);
        }
        .username-group.input-error { border-color: #fca5a5; }
        .username-prefix {
            display: flex; align-items: center;
            padding: 0 4px 0 14px;
            font-size: 13.5px; font-weight: 800; color: var(--muted);
            background: var(--cream-deep);
            border-right: 1px solid var(--border);
            letter-spacing: 0.5px;
            user-select: none;
        }
        .username-group input {
            flex: 1; min-width: 0; height: 48px; padding: 0 14px;
            border: none; outline: none; background: transparent;
            font-size: 13.5px; font-weight: 700; color: var(--dark);
            letter-spacing: 1px;
        }
        .su-form-row { display: grid; gap: 16px; grid-template-columns: 1fr 1fr; }
        @media (max-width: 560px) { .su-form-row { grid-template-columns: 1fr; } }

        .pw-wrap { position: relative; }
        .pw-wrap input { padding-right: 46px; }
        .pw-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; color: var(--muted); padding: 4px;
            display: flex; align-items: center;
        }

        .pw-req-list { margin-top: 12px; display: flex; flex-wrap: wrap; gap: 6px; }
        .pw-req {
            font-size: 11.5px; padding: 4px 10px; border-radius: 999px;
            border: 1px solid var(--border); color: var(--muted); background: var(--cream);
            display: flex; align-items: center; gap: 4px; transition: all 0.2s; font-weight: 600;
        }
        .pw-req.met  { background: #E7F6EC; border-color: #86efac; color: #207A3A; }
        .pw-req.fail { background: var(--danger-bg); border-color: #fca5a5; color: var(--danger); }

        .signup-submit {
            width: 100%; height: 54px; border: none;
            background: var(--dark); color: var(--white);
            border-radius: 16px; font-size: 15px;
            font-weight: 900; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: 0.22s ease;
        }
        .signup-submit:hover { background: var(--dark-soft); transform: translateY(-2px); box-shadow: 0 14px 26px rgba(0, 0, 0, 0.18); }
        .signup-submit:disabled { background: var(--muted); cursor: not-allowed; transform: none; box-shadow: none; }

        .signup-footer-link {
            text-align: center; margin-top: 6px; font-size: 13px; color: var(--muted);
        }
        .signup-footer-link a { color: var(--dark); font-weight: 800; text-decoration: none; }
        .signup-footer-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="signup-wrap">

        <div class="signup-brand">
            <img src="{{ asset('images/gmdlogo-circle.svg') }}" alt="GMD South Phils" class="signup-brand-logo">
            <div>
                <div class="signup-brand-name">GMD <span>South Phils</span></div>
                <div class="signup-brand-sub">Client Sign Up</div>
            </div>
        </div>

        @if(session('error'))
        <div class="error-message">
            <i data-lucide="circle-alert"></i>
            <span>{{ session('error') }}</span>
        </div>
        @endif

        <form method="POST" action="{{ route('signup.post') }}" id="signupForm" autocomplete="off">
            @csrf

            {{-- ── Personal Information ── --}}
            <div class="signup-card">
                <div class="signup-section-head">
                    <i data-lucide="user" style="width:14px;height:14px;"></i>
                    Personal Information
                </div>
                <div class="signup-body-pad">

                    <div class="su-form-group">
                        <label>Full Name / Company Name</label>
                        <input type="text" name="full_name" required autofocus
                               value="{{ old('full_name') }}"
                               placeholder="Enter your full name or company name"
                               class="{{ $errors->has('full_name') ? 'input-error' : '' }}">
                        @error('full_name')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="su-form-row" style="margin-top:16px;">
                        <div class="su-form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" required
                                   value="{{ old('email') }}"
                                   placeholder="your@email.com"
                                   class="{{ $errors->has('email') ? 'input-error' : '' }}">
                            @error('email')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="su-form-group">
                            <label>Contact Number</label>
                            <input type="text" name="contact_number" required
                                   value="{{ old('contact_number') }}"
                                   placeholder="09XX XXX XXXX"
                                   class="{{ $errors->has('contact_number') ? 'input-error' : '' }}">
                            @error('contact_number')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="su-form-row" style="margin-top:16px;">
                        <div class="su-form-group" id="regionGroup">
                            <label>Region</label>
                            <select name="region" id="regionSelect" required class="{{ $errors->has('region') ? 'input-error' : '' }}">
                                <option value="" disabled hidden selected>Select Region</option>
                            </select>
                            @error('region')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="su-form-group" id="provinceGroup">
                            <label>Province</label>
                            <select name="province" id="provinceSelect" required disabled class="{{ $errors->has('province') ? 'input-error' : '' }}">
                                <option value="" disabled hidden selected>Select Province</option>
                            </select>
                            @error('province')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="su-form-row" style="margin-top:16px;">
                        <div class="su-form-group" id="cityGroup">
                            <label>City / Municipality</label>
                            <select name="city" id="citySelect" required disabled class="{{ $errors->has('city') ? 'input-error' : '' }}">
                                <option value="" disabled hidden selected>Select City / Municipality</option>
                            </select>
                            @error('city')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="su-form-group" id="barangayGroup">
                            <label>Barangay</label>
                            <select name="barangay" id="barangaySelect" required disabled class="{{ $errors->has('barangay') ? 'input-error' : '' }}">
                                <option value="" disabled hidden selected>Select Barangay</option>
                            </select>
                            @error('barangay')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="su-form-group" style="margin-top:16px;">
                        <label>Street / House No. / Building</label>
                        <input type="text" name="street_address" required
                               value="{{ old('street_address') }}"
                               placeholder="e.g. 123 East Service Road"
                               class="{{ $errors->has('street_address') ? 'input-error' : '' }}">
                        @error('street_address')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                </div>
            </div>

            {{-- ── Account Credentials ── --}}
            <div class="signup-card">
                <div class="signup-section-head">
                    <i data-lucide="lock" style="width:14px;height:14px;"></i>
                    Account Credentials
                </div>
                <div class="signup-body-pad">

                    <div class="su-form-group">
                        <label>Username</label>
                        <div class="username-group {{ $errors->has('username') ? 'input-error' : '' }}">
                            <span class="username-prefix">CGMD-</span>
                            <input type="text" id="usernameSuffix" required
                                   inputmode="numeric" placeholder="0000" maxlength="4">
                        </div>
                        <input type="hidden" name="username" id="usernameInput" value="{{ old('username') }}">
                        <span style="font-size:12px;color:var(--muted);display:block;margin-top:6px;">
                            We've suggested a number — feel free to change it.
                        </span>
                        @error('username')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="su-form-row" style="margin-top:16px;">
                        <div class="su-form-group">
                            <label>Password</label>
                            <div class="pw-wrap">
                                <input type="password" name="password" id="signupPassword"
                                       placeholder="Min 6 characters"
                                       class="{{ $errors->has('password') ? 'input-error' : '' }}"
                                       oninput="checkPwReqs()" required>
                                <button type="button" class="pw-toggle" onclick="togglePw('signupPassword',this)">
                                    <i data-lucide="eye" style="width:15px;height:15px;"></i>
                                </button>
                            </div>
                            @error('password')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="su-form-group">
                            <label>Confirm Password</label>
                            <div class="pw-wrap">
                                <input type="password" name="password_confirmation" id="signupPasswordConfirm"
                                       placeholder="Repeat password"
                                       oninput="checkConfirm()" required>
                                <button type="button" class="pw-toggle" onclick="togglePw('signupPasswordConfirm',this)">
                                    <i data-lucide="eye" style="width:15px;height:15px;"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="pw-req-list" id="pwReqList">
                        <span class="pw-req" id="req-len">Min 6 characters</span>
                        <span class="pw-req" id="req-upper">1 uppercase letter</span>
                        <span class="pw-req" id="req-lower">1 lowercase letter</span>
                        <span class="pw-req" id="req-num">1 number</span>
                        <span class="pw-req" id="req-match">Passwords match</span>
                    </div>

                </div>
            </div>

            <button type="submit" class="signup-submit" id="submitBtn">
                <i data-lucide="user-plus" style="width:18px;height:18px;"></i>
                Create Account
            </button>

            <div class="signup-footer-link">
                Already have an account? <a href="{{ route('login') }}">Log In</a>
            </div>

        </form>

    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // ── Region / Province / City / Barangay cascading dropdowns (PSGC) ──
    const OLD_REGION   = @json(old('region', ''));
    const OLD_PROVINCE = @json(old('province', ''));
    const OLD_CITY     = @json(old('city', ''));
    const OLD_BARANGAY = @json(old('barangay', ''));

    const PSGC = 'https://psgc.cloud/api';

    // The PSGC API's own data has some names double UTF-8-encoded (e.g. "BiÃ±an" instead
    // of "Biñan"); this reverses that specific mis-encoding.
    function fixMojibake(str) {
        try { return decodeURIComponent(escape(str)); } catch (e) { return str; }
    }

    async function psgcFetch(url) {
        const res = await fetch(url);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        return Array.isArray(data) ? data.map(function (item) {
            return Object.assign({}, item, { name: fixMojibake(item.name) });
        }) : data;
    }

    function setLoading(groupId, loading) {
        const g = document.getElementById(groupId);
        if (g) g.classList.toggle('sel-loading', loading);
    }

    function buildOptions(sel, items, oldVal, placeholder) {
        sel.innerHTML = '';
        const blank = document.createElement('option');
        blank.value = '';
        blank.textContent = placeholder || '';
        blank.disabled = true;
        blank.hidden = true;
        blank.selected = true;
        sel.appendChild(blank);
        items
            .slice()
            .sort((a, b) => a.name.localeCompare(b.name))
            .forEach(item => {
                const opt = document.createElement('option');
                opt.value       = item.name;
                opt.dataset.code = item.code;
                opt.textContent  = item.name;
                if (item.name === oldVal) opt.selected = true;
                sel.appendChild(opt);
            });
        sel.disabled = false;
    }

    function resetSelect(id, placeholder) {
        const sel = document.getElementById(id);
        sel.innerHTML = '<option value="" disabled hidden selected>' + placeholder + '</option>';
        sel.disabled  = true;
    }

    async function loadRegions() {
        const sel = document.getElementById('regionSelect');
        setLoading('regionGroup', true);
        try {
            const data = await psgcFetch(PSGC + '/regions');
            buildOptions(sel, data, OLD_REGION, 'Select Region');
            if (OLD_REGION) {
                const match = [...sel.options].find(o => o.value === OLD_REGION);
                if (match?.dataset.code) await loadProvinces(match.dataset.code, true);
            }
        } catch (e) {
            sel.innerHTML = '<option value="">Failed to load — refresh page</option>';
            sel.disabled  = false;
        }
        setLoading('regionGroup', false);
    }

    async function loadProvinces(regionCode, restoring) {
        const sel = document.getElementById('provinceSelect');
        resetSelect('citySelect', 'Select province first');
        resetSelect('barangaySelect', 'Select city first');
        setLoading('provinceGroup', true);
        sel.innerHTML = '<option value="">Loading…</option>';
        sel.disabled  = true;
        try {
            const data = await psgcFetch(PSGC + '/regions/' + regionCode + '/provinces');
            if (data.length === 0) {
                await loadCitiesFromRegion(regionCode, restoring);
                sel.innerHTML = '<option value="NCR / No Province">NCR / No Province</option>';
                sel.value    = 'NCR / No Province';
                sel.disabled  = false;
            } else {
                buildOptions(sel, data, OLD_PROVINCE, 'Select Province');
                if (restoring && OLD_PROVINCE) {
                    const match = [...sel.options].find(o => o.value === OLD_PROVINCE);
                    if (match?.dataset.code) await loadCities(match.dataset.code, true);
                }
            }
        } catch (e) {
            sel.innerHTML = '<option value="">Failed to load</option>';
            sel.disabled  = false;
        }
        setLoading('provinceGroup', false);
    }

    async function loadCitiesFromRegion(regionCode, restoring) {
        const sel = document.getElementById('citySelect');
        setLoading('cityGroup', true);
        sel.innerHTML = '<option value="">Loading…</option>';
        sel.disabled  = true;
        try {
            const data = await psgcFetch(PSGC + '/regions/' + regionCode + '/cities-municipalities');
            buildOptions(sel, data, OLD_CITY, 'Select City / Municipality');
            if (restoring && OLD_CITY) {
                const match = [...sel.options].find(o => o.value === OLD_CITY);
                if (match?.dataset.code) await loadBarangays(match.dataset.code, true);
            }
        } catch (e) {
            sel.innerHTML = '<option value="">Failed to load</option>';
            sel.disabled  = false;
        }
        setLoading('cityGroup', false);
    }

    async function loadCities(provinceCode, restoring) {
        const sel = document.getElementById('citySelect');
        resetSelect('barangaySelect', 'Select city first');
        setLoading('cityGroup', true);
        sel.innerHTML = '<option value="">Loading…</option>';
        sel.disabled  = true;
        try {
            const data = await psgcFetch(PSGC + '/provinces/' + provinceCode + '/cities-municipalities');
            buildOptions(sel, data, OLD_CITY, 'Select City / Municipality');
            if (restoring && OLD_CITY) {
                const match = [...sel.options].find(o => o.value === OLD_CITY);
                if (match?.dataset.code) await loadBarangays(match.dataset.code, true);
            }
        } catch (e) {
            sel.innerHTML = '<option value="">Failed to load</option>';
            sel.disabled  = false;
        }
        setLoading('cityGroup', false);
    }

    async function loadBarangays(cityCode, restoring) {
        const sel = document.getElementById('barangaySelect');
        setLoading('barangayGroup', true);
        sel.innerHTML = '<option value="">Loading…</option>';
        sel.disabled  = true;
        try {
            const data = await psgcFetch(PSGC + '/cities-municipalities/' + cityCode + '/barangays');
            buildOptions(sel, data, OLD_BARANGAY, 'Select Barangay');
        } catch (e) {
            sel.innerHTML = '<option value="">Failed to load</option>';
            sel.disabled  = false;
        }
        setLoading('barangayGroup', false);
    }

    document.getElementById('regionSelect').addEventListener('change', function () {
        const code = this.options[this.selectedIndex]?.dataset?.code;
        if (code) {
            loadProvinces(code, false);
        } else {
            resetSelect('provinceSelect', 'Select region first');
            resetSelect('citySelect',     'Select province first');
            resetSelect('barangaySelect', 'Select city first');
        }
    });

    document.getElementById('provinceSelect').addEventListener('change', function () {
        const val  = this.value;
        const code = this.options[this.selectedIndex]?.dataset?.code;
        if (val === 'NCR / No Province') return;
        if (code) {
            loadCities(code, false);
        } else {
            resetSelect('citySelect',     'Select province first');
            resetSelect('barangaySelect', 'Select city first');
        }
    });

    document.getElementById('citySelect').addEventListener('change', function () {
        const code = this.options[this.selectedIndex]?.dataset?.code;
        if (code) {
            loadBarangays(code, false);
        } else {
            resetSelect('barangaySelect', 'Select city first');
        }
    });

    loadRegions();

    // ── Username: fixed "CGMD-" prefix, only the number is editable ──
    (function setupUsername() {
        var suffixInput = document.getElementById('usernameSuffix');
        var hiddenInput = document.getElementById('usernameInput');
        if (!suffixInput || !hiddenInput) return;

        function syncHidden() {
            var digits = suffixInput.value.replace(/[^0-9]/g, '');
            hiddenInput.value = digits ? ('CGMD-' + digits) : '';
        }

        function extractDigits(fullUsername) {
            var m = /^CGMD-(\d+)$/.exec(fullUsername || '');
            return m ? m[1].slice(0, 4) : '';
        }

        suffixInput.addEventListener('input', function () {
            suffixInput.value = suffixInput.value.replace(/[^0-9]/g, '').slice(0, 4);
            syncHidden();
        });

        var form = document.getElementById('signupForm');
        if (form) form.addEventListener('submit', syncHidden);

        var oldDigits = extractDigits(hiddenInput.value);
        if (oldDigits) {
            suffixInput.value = oldDigits;
            syncHidden();
            return;
        }

        fetch('{{ route('signup.next_username') }}')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var digits = extractDigits(data.username);
                if (digits) {
                    suffixInput.value = digits;
                    syncHidden();
                }
            })
            .catch(function () {});
    })();

    function togglePw(id, btn) {
        var input = document.getElementById(id);
        if (!input) return;
        var isText = input.type === 'text';
        input.type = isText ? 'password' : 'text';
        btn.innerHTML = '<i data-lucide="' + (isText ? 'eye' : 'eye-off') + '" style="width:15px;height:15px;"></i>';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function setReq(id, met) {
        var el  = document.getElementById(id);
        var val = document.getElementById('signupPassword').value;
        if (!el) return;
        el.classList.toggle('met',  met);
        el.classList.toggle('fail', !met && val.length > 0);
    }

    function checkPwReqs() {
        var val = document.getElementById('signupPassword').value;
        setReq('req-len',   val.length >= 6);
        setReq('req-upper', /[A-Z]/.test(val));
        setReq('req-lower', /[a-z]/.test(val));
        setReq('req-num',   /[0-9]/.test(val));
        checkConfirm();
    }

    function checkConfirm() {
        var pw   = document.getElementById('signupPassword').value;
        var conf = document.getElementById('signupPasswordConfirm').value;
        var el   = document.getElementById('req-match');
        if (!el) return;
        if (!conf.length) { el.classList.remove('met','fail'); return; }
        el.classList.toggle('met',  pw === conf);
        el.classList.toggle('fail', pw !== conf);
    }

    document.getElementById('signupForm').addEventListener('submit', function () {
        var btn = document.getElementById('submitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<div class="btn-spinner"></div><span>Creating account...</span>';
        }
    });
    </script>
</body>
</html>
