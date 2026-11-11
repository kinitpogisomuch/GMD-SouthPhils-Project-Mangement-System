<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Setup | GMD South Phils</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ @filemtime(public_path('css/login.css')) }}">
</head>
<body class="pg-auth-first-login-setup">

    <div class="setup-wrap">

        <div class="setup-brand">
            <img src="{{ asset('images/gmdlogo-circle.svg') }}" alt="GMD South Phils" class="setup-brand-logo">
            <div>
                <div class="setup-brand-name">GMD <span>South Phils</span></div>
                <div class="setup-brand-sub">Account Setup</div>
            </div>
        </div>

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
                        <label>Email Address </label>
                        <input type="email" name="email" required
                               value="{{ old('email', $user->email) }}"
                               placeholder="your@email.com"
                               class="{{ $errors->has('email') ? 'input-error' : '' }}">
                        @error('email')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-row" style="margin-bottom:16px;">
                        <div class="form-group" id="regionGroup">
                            <label>Region </label>
                            <select name="region" id="regionSelect" required class="{{ $errors->has('region') ? 'input-error' : '' }}">
                                <option value="" disabled hidden selected>Select Region</option>
                            </select>
                            @error('region')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group" id="provinceGroup">
                            <label>Province </label>
                            <select name="province" id="provinceSelect" required disabled class="{{ $errors->has('province') ? 'input-error' : '' }}">
                                <option value="" disabled hidden selected>Select Province</option>
                            </select>
                            @error('province')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="form-row" style="margin-bottom:16px;">
                        <div class="form-group" id="cityGroup">
                            <label>City / Municipality </label>
                            <select name="city" id="citySelect" required disabled class="{{ $errors->has('city') ? 'input-error' : '' }}">
                                <option value="" disabled hidden selected>Select City / Municipality</option>
                            </select>
                            @error('city')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group" id="barangayGroup">
                            <label>Barangay </label>
                            <select name="barangay" id="barangaySelect" required disabled class="{{ $errors->has('barangay') ? 'input-error' : '' }}">
                                <option value="" disabled hidden selected>Select Barangay</option>
                            </select>
                            @error('barangay')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Street / House No. / Building <span style="font-size:11px;color:var(--muted);font-weight:400;">(optional)</span></label>
                        <input type="text" name="street_address"
                               value="{{ old('street_address', $user->street_address) }}"
                               placeholder="e.g. 123 East Service Road"
                               class="{{ $errors->has('street_address') ? 'input-error' : '' }}">
                        @error('street_address')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                </div>
            </div>

            {{-- ── Account Credentials ── --}}
            <div class="setup-card">
                <div class="setup-section-head">
                    <i data-lucide="lock" style="width:14px;height:14px;"></i>
                    Account Credentials
                </div>
                <div class="setup-body-pad">

                    <div class="form-group">
                        <label>Username</label>
                        @if($usernamePrefix)
                        <div class="username-group {{ $errors->has('username') ? 'input-error' : '' }}">
                            <span class="username-prefix">{{ $usernamePrefix }}</span>
                            <input type="text" id="usernameSuffix" required
                                   inputmode="numeric" placeholder="0000" maxlength="4"
                                   value="{{ $usernameSuffix }}">
                        </div>
                        <input type="hidden" name="username" id="usernameInput" value="{{ old('username', $user->username) }}">
                        @else
                        <input type="text" name="username" value="{{ old('username', $user->username) }}"
                               class="{{ $errors->has('username') ? 'input-error' : '' }}">
                        @endif
                        <span style="font-size:12px;color:var(--muted);display:block;margin-top:6px;">
                            This is your login identifier — feel free to change it.
                        </span>
                        @error('username')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group" style="margin-top:20px;margin-bottom:20px;">
                        <label>Current PIN <span style="font-size:12px;font-weight:400;color:var(--muted);">(the temporary PIN you received)</span></label>
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
                            <label>New PIN / Password </label>
                            <div class="pw-wrap">
                                <input type="password" name="new_password" id="newPassword"
                                       placeholder="Min 8 chars"
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
                            <label>Confirm PIN / Password </label>
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
                        <span class="pw-req" id="req-len">Min 8 characters</span>
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

    // ── Load Regions ──
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

    // ── Load Cities from Region (NCR fallback) ──
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

    // ── Load Cities from Province ──
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

    // ── Load Barangays ──
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

    // ── Username: fixed role prefix, only the number is editable ──
    (function setupUsername() {
        var prefix      = @json($usernamePrefix);
        var suffixInput = document.getElementById('usernameSuffix');
        var hiddenInput = document.getElementById('usernameInput');
        if (!prefix || !suffixInput || !hiddenInput) return;

        function syncHidden() {
            var digits = suffixInput.value.replace(/[^0-9]/g, '');
            hiddenInput.value = digits ? (prefix + digits) : '';
        }

        suffixInput.addEventListener('input', function () {
            suffixInput.value = suffixInput.value.replace(/[^0-9]/g, '').slice(0, 4);
            syncHidden();
        });

        var form = document.getElementById('setupForm');
        if (form) form.addEventListener('submit', syncHidden);

        syncHidden();
    })();

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
        setReq('req-len',   val.length >= 8);
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
