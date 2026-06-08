<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Setup | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        body {
            background: #f1f3f9;
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 40px 16px 60px;
            font-family: 'Inter', sans-serif;
        }
        .setup-wrap { width: 100%; max-width: 680px; }
        .setup-brand {
            text-align: center;
            margin-bottom: 24px;
            font-size: 20px;
            font-weight: 900;
            color: #1a1a2e;
            letter-spacing: -0.5px;
        }
        .setup-brand span { color: #e8900a; }
        .setup-notice {
            background: #fff7ed;
            border: 1.5px solid #fed7aa;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 24px;
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }
        .setup-notice-icon {
            flex-shrink: 0;
            width: 36px; height: 36px;
            background: #e8900a;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
        }
        .setup-notice h3 { font-size: 15px; font-weight: 800; color: #1a1a2e; margin-bottom: 4px; }
        .setup-notice p  { font-size: 13px; color: #78350f; line-height: 1.55; margin: 0; }
        .setup-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            overflow: hidden;
            margin-bottom: 16px;
        }
        .setup-section-head {
            padding: 16px 24px;
            background: #f8f9ff;
            border-bottom: 1px solid #e8eaf5;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6366f1;
            display: flex; align-items: center; gap: 8px;
        }
        .setup-body-pad { padding: 24px; }
        .username-display {
            background: #f8f9ff;
            border: 1.5px solid #dde1f5;
            border-radius: 10px;
            padding: 14px 18px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .username-display .label { font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 0.06em; }
        .username-display .value { font-size: 22px; font-weight: 900; color: #1a1a2e; letter-spacing: 2px; }
        .username-lock-note { margin-top: 8px; font-size: 12px; color: #6b7280; display: flex; align-items: center; gap: 5px; }
        .pw-req-list { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 6px; }
        .pw-req {
            font-size: 11.5px; padding: 3px 9px; border-radius: 99px;
            border: 1px solid #e5e7eb; color: #9ca3af; background: #f9fafb;
            display: flex; align-items: center; gap: 4px; transition: all 0.2s;
        }
        .pw-req.met  { background: #dcfce7; border-color: #86efac; color: #15803d; }
        .pw-req.fail { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }
        .setup-submit {
            background: #1a1a2e; color: #fff; border: none;
            border-radius: 10px; padding: 14px 28px; font-size: 14px;
            font-weight: 700; cursor: pointer; width: 100%;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: background 0.2s;
        }
        .setup-submit:hover { background: #e8900a; }
        .setup-submit:disabled { background: #9ca3af; cursor: not-allowed; }
        .form-group label { font-size: 13px; font-weight: 600; color: #374151; display: block; margin-bottom: 6px; }
        .form-group input,
        .form-group select {
            width: 100%; padding: 10px 12px;
            border: 1.5px solid #e5e7eb; border-radius: 8px;
            font-size: 13.5px; color: #1a1a2e; background: #fff;
            outline: none; transition: border-color 0.2s, background 0.2s;
            box-sizing: border-box; appearance: none; -webkit-appearance: none;
        }
        .form-group select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }
        .form-group input:focus, .form-group select:focus { border-color: #6366f1; }
        .form-group input.input-error, .form-group select.input-error { border-color: #fca5a5; }
        .form-group select:disabled { background-color: #f9fafb; color: #9ca3af; cursor: not-allowed; }
        .field-error { font-size: 12px; color: #dc2626; margin-top: 4px; display: block; }
        .form-row { display: grid; gap: 16px; grid-template-columns: 1fr 1fr; }
        @media (max-width: 520px) { .form-row { grid-template-columns: 1fr; } }
        .pw-wrap { position: relative; }
        .pw-toggle {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; color: #9ca3af; padding: 4px;
        }
        .sel-loading { position: relative; }
        .sel-loading::after {
            content: '';
            position: absolute; right: 34px; top: 50%; transform: translateY(-50%);
            width: 12px; height: 12px;
            border: 2px solid #e5e7eb; border-top-color: #6366f1;
            border-radius: 50%; animation: spin 0.7s linear infinite;
            pointer-events: none;
        }
        @keyframes spin { to { transform: translateY(-50%) rotate(360deg); } }
    </style>
</head>
<body>

    <div class="setup-wrap">

        <div class="setup-brand">GMD <span>South Phils</span></div>

        <div class="setup-notice">
            <div class="setup-notice-icon">
                <i data-lucide="shield-alert" style="width:18px;height:18px;"></i>
            </div>
            <div>
                <h3>Update Your Account Credentials</h3>
                <p>Your account was created using temporary system-generated credentials. Complete the setup below before you can access the system. This form cannot be skipped.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('setup.credentials.submit') }}" id="setupForm" autocomplete="off">
            @csrf

            {{-- ── Address Information ── --}}
            <div class="setup-card">
                <div class="setup-section-head">
                    <i data-lucide="user" style="width:14px;height:14px;"></i>
                    Personal &amp; Address Information
                </div>
                <div class="setup-body-pad">

                    <div class="form-group" style="margin-bottom:16px;">
                        <label>Email Address *</label>
                        <input type="email" name="email" required
                               value="{{ old('email', $user->email) }}"
                               placeholder="your@email.com"
                               class="{{ $errors->has('email') ? 'input-error' : '' }}">
                        @error('email')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-row" style="margin-bottom:16px;">
                        <div class="form-group" id="regionGroup">
                            <label>Region *</label>
                            <select name="region" id="regionSelect" required class="{{ $errors->has('region') ? 'input-error' : '' }}">
                                <option value="">Loading regions…</option>
                            </select>
                            @error('region')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group" id="provinceGroup">
                            <label>Province *</label>
                            <select name="province" id="provinceSelect" required disabled class="{{ $errors->has('province') ? 'input-error' : '' }}">
                                <option value="">Select region first</option>
                            </select>
                            @error('province')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="form-row" style="margin-bottom:16px;">
                        <div class="form-group" id="cityGroup">
                            <label>City / Municipality *</label>
                            <select name="city" id="citySelect" required disabled class="{{ $errors->has('city') ? 'input-error' : '' }}">
                                <option value="">Select province first</option>
                            </select>
                            @error('city')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group" id="barangayGroup">
                            <label>Barangay *</label>
                            <select name="barangay" id="barangaySelect" required disabled class="{{ $errors->has('barangay') ? 'input-error' : '' }}">
                                <option value="">Select city first</option>
                            </select>
                            @error('barangay')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Street / House No. / Building *</label>
                        <input type="text" name="street_address"
                               value="{{ old('street_address', $user->street_address) }}"
                               placeholder="e.g. 123 East Service Road"
                               class="{{ $errors->has('street_address') ? 'input-error' : '' }}" required>
                        @error('street_address')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                </div>
            </div>

            {{-- ── Account Information ── --}}
            <div class="setup-card">
                <div class="setup-section-head">
                    <i data-lucide="id-card" style="width:14px;height:14px;"></i>
                    Account Information
                </div>
                <div class="setup-body-pad">
                    <div class="username-display">
                        <div class="label">Username (permanent — cannot be changed)</div>
                        <div class="value">{{ $user->username }}</div>
                    </div>
                    <div class="username-lock-note">
                        <i data-lucide="lock" style="width:12px;height:12px;"></i>
                        Your system-generated username is your permanent login identifier.
                    </div>
                </div>
            </div>

            {{-- ── Security Credentials ── --}}
            <div class="setup-card">
                <div class="setup-section-head">
                    <i data-lucide="lock" style="width:14px;height:14px;"></i>
                    Security Credentials
                </div>
                <div class="setup-body-pad">

                    <div class="form-group" style="margin-bottom:20px;">
                        <label>Current PIN <span style="font-size:12px;font-weight:400;color:#6b7280;">(the temporary PIN you received)</span></label>
                        <div class="pw-wrap">
                            <input type="password" name="current_pin" id="currentPin"
                                   placeholder="Enter your current PIN"
                                   class="{{ $errors->has('current_pin') ? 'input-error' : '' }}" required>
                            <button type="button" class="pw-toggle" onclick="togglePw('currentPin',this)">
                                <i data-lucide="eye" style="width:15px;height:15px;"></i>
                            </button>
                        </div>
                        @error('current_pin')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>New PIN / Password *</label>
                            <div class="pw-wrap">
                                <input type="password" name="new_password" id="newPassword"
                                       placeholder="Min 6 chars"
                                       class="{{ $errors->has('new_password') ? 'input-error' : '' }}"
                                       oninput="checkPwReqs()" required>
                                <button type="button" class="pw-toggle" onclick="togglePw('newPassword',this)">
                                    <i data-lucide="eye" style="width:15px;height:15px;"></i>
                                </button>
                            </div>
                            @error('new_password')
                            <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Confirm PIN / Password *</label>
                            <div class="pw-wrap">
                                <input type="password" name="new_password_confirmation" id="confirmPassword"
                                       placeholder="Repeat password"
                                       class="{{ $errors->has('new_password_confirmation') ? 'input-error' : '' }}"
                                       oninput="checkConfirm()" required>
                                <button type="button" class="pw-toggle" onclick="togglePw('confirmPassword',this)">
                                    <i data-lucide="eye" style="width:15px;height:15px;"></i>
                                </button>
                            </div>
                            @error('new_password_confirmation')
                            <span class="field-error">{{ $message }}</span>
                            @enderror
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

            <button type="submit" class="setup-submit" id="submitBtn">
                <i data-lucide="check-circle" style="width:18px;height:18px;"></i>
                Complete Setup &amp; Access System
            </button>

        </form>

    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // ── Restore old values after validation error ──
    const OLD_REGION   = @json(old('region',   $user->region   ?? ''));
    const OLD_PROVINCE = @json(old('province', $user->province ?? ''));
    const OLD_CITY     = @json(old('city',     $user->city     ?? ''));
    const OLD_BARANGAY = @json(old('barangay', $user->barangay ?? ''));

    const PSGC = 'https://psgc.cloud/api';

    async function psgcFetch(url) {
        const res = await fetch(url);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    }

    function setLoading(groupId, loading) {
        const g = document.getElementById(groupId);
        if (g) g.classList.toggle('sel-loading', loading);
    }

    function buildOptions(sel, items, oldVal) {
        sel.innerHTML = '';
        const blank = document.createElement('option');
        blank.value = '';
        blank.textContent = '-- Select --';
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
        sel.innerHTML = '<option value="">' + placeholder + '</option>';
        sel.disabled  = true;
    }

    // ── Load Regions ──
    async function loadRegions() {
        const sel = document.getElementById('regionSelect');
        setLoading('regionGroup', true);
        try {
            const data = await psgcFetch(PSGC + '/regions');
            buildOptions(sel, data, OLD_REGION);
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

    // ── Load Provinces ──
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
                // NCR / regions without provinces — load cities directly from region
                await loadCitiesFromRegion(regionCode, restoring);
                // Show a fixed "N/A" for province
                sel.innerHTML = '<option value="NCR / No Province">NCR / No Province</option>';
                sel.value    = 'NCR / No Province';
                sel.disabled  = false;
            } else {
                buildOptions(sel, data, OLD_PROVINCE);
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

    // ── Load Cities from Region (NCR fallback) ──
    async function loadCitiesFromRegion(regionCode, restoring) {
        const sel = document.getElementById('citySelect');
        setLoading('cityGroup', true);
        sel.innerHTML = '<option value="">Loading…</option>';
        sel.disabled  = true;
        try {
            const data = await psgcFetch(PSGC + '/regions/' + regionCode + '/cities-municipalities');
            buildOptions(sel, data, OLD_CITY);
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

    // ── Load Cities from Province ──
    async function loadCities(provinceCode, restoring) {
        const sel = document.getElementById('citySelect');
        resetSelect('barangaySelect', 'Select city first');
        setLoading('cityGroup', true);
        sel.innerHTML = '<option value="">Loading…</option>';
        sel.disabled  = true;
        try {
            const data = await psgcFetch(PSGC + '/provinces/' + provinceCode + '/cities-municipalities');
            buildOptions(sel, data, OLD_CITY);
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

    // ── Load Barangays ──
    async function loadBarangays(cityCode, restoring) {
        const sel = document.getElementById('barangaySelect');
        setLoading('barangayGroup', true);
        sel.innerHTML = '<option value="">Loading…</option>';
        sel.disabled  = true;
        try {
            const data = await psgcFetch(PSGC + '/cities-municipalities/' + cityCode + '/barangays');
            buildOptions(sel, data, OLD_BARANGAY);
        } catch (e) {
            sel.innerHTML = '<option value="">Failed to load</option>';
            sel.disabled  = false;
        }
        setLoading('barangayGroup', false);
    }

    // ── Cascade event listeners ──
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
        if (val === 'NCR / No Province') return; // cities already loaded
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

    // ── Password helpers ──
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
        var val = document.getElementById('newPassword').value;
        if (!el) return;
        el.classList.toggle('met',  met);
        el.classList.toggle('fail', !met && val.length > 0);
    }

    function checkPwReqs() {
        var val = document.getElementById('newPassword').value;
        setReq('req-len',   val.length >= 6);
        setReq('req-upper', /[A-Z]/.test(val));
        setReq('req-lower', /[a-z]/.test(val));
        setReq('req-num',   /[0-9]/.test(val));
        checkConfirm();
    }

    function checkConfirm() {
        var pw   = document.getElementById('newPassword').value;
        var conf = document.getElementById('confirmPassword').value;
        var el   = document.getElementById('req-match');
        if (!el) return;
        if (!conf.length) { el.classList.remove('met','fail'); return; }
        el.classList.toggle('met',  pw === conf);
        el.classList.toggle('fail', pw !== conf);
    }

    // ── Prevent double submit ──
    document.getElementById('setupForm').addEventListener('submit', function () {
        var btn = document.getElementById('submitBtn');
        if (btn) { btn.textContent = 'Saving…'; btn.disabled = true; }
    });

    // ── Boot ──
    loadRegions();
    </script>
</body>
</html>
