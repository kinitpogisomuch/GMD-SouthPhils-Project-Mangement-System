<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | GMD South Phils</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ @filemtime(public_path('css/login.css')) }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script>
        // entrance animation: hide the parts that animate in only if GSAP loaded (failsafe after 2.5 s)
        (function () {
            if (!window.gsap || (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) return;
            var root = document.documentElement;
            root.classList.add('su-anim');
            setTimeout(function () { root.classList.remove('su-anim'); }, 2500);
        })();
    </script>
</head>
<body class="pg-auth-signup">
    @php
        $hasPhoto = file_exists(public_path('images/login.jpg'));
        $okTick = '<svg class="su-ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';
    @endphp

    <div class="su-page">

        {{-- ── Brand panel: what a client account gives, and how registration works ── --}}
        <aside class="su-aside {{ $hasPhoto ? 'has-bg-photo' : '' }}" @if($hasPhoto) style="--su-photo: url('{{ asset('images/login.jpg') }}')" @endif>
            <div class="su-aside-glow" aria-hidden="true"></div>

            <a href="{{ route('home') }}" class="su-brand">
                <img src="{{ asset('images/gmdlogo-circle.svg') }}" alt="">
                <span class="su-brand-name">GMD <span>South Phils</span></span>
            </a>

            <div class="su-pitch">
                <div class="su-badge"><i data-lucide="user-plus"></i> Client Registration</div>
                <h1>Create your <em>client account</em></h1>
                <p>One portal for your steel tank projects — from your first quotation to pick-up.</p>
                <ul class="su-perks">
                    <li><i data-lucide="file-text"></i> Request quotations online, no calls needed</li>
                    <li><i data-lucide="activity"></i> Follow every fabrication phase with photos</li>
                    <li><i data-lucide="landmark"></i> Pay securely online through your bank</li>
                </ul>
            </div>

            <div class="su-steps">
                <div class="su-steps-title" id="suStepsTitle">How it works</div>
                <ol aria-labelledby="suStepsTitle">
                <li class="is-current">
                    <span class="su-step-dot"><i data-lucide="pencil-line"></i></span>
                    <div><div class="su-step-title">Register</div><div class="su-step-text">Fill out this form — about 2 minutes.</div></div>
                </li>
                <li>
                    <span class="su-step-dot"><i data-lucide="mail-check"></i></span>
                    <div><div class="su-step-title">Verify email</div><div class="su-step-text">Open the link we send you.</div></div>
                </li>
                <li>
                    <span class="su-step-dot"><i data-lucide="shield-check"></i></span>
                    <div><div class="su-step-title">Approval</div><div class="su-step-text">GMD reviews your registration.</div></div>
                </li>
                <li>
                    <span class="su-step-dot"><i data-lucide="key-round"></i></span>
                    <div><div class="su-step-title">Set password</div><div class="su-step-text">Finish setup from the email and log in.</div></div>
                </li>
                </ol>
            </div>
        </aside>

        {{-- ── Form ── --}}
        <main class="su-main">
        <div class="signup-wrap">

            <div class="su-head">
                <h2>Sign up</h2>
                <p>Already have an account? <a href="{{ route('login') }}">Log in</a></p>
            </div>

            {{-- live progress through the required fields (updated by the script below) --}}
            <div class="su-progress" id="suProgress" aria-live="polite">
                <div class="su-progress-track"><div class="su-progress-bar" id="suProgressBar"></div></div>
                <span class="su-progress-label" id="suProgressLabel">0 of 8 done</span>
            </div>

            @if(session('error'))
            <div class="error-message">
                <i data-lucide="circle-alert"></i>
                <span>{{ session('error') }}</span>
            </div>
            @endif

            @if(!empty($quoteTank))
            {{-- Came from a tank card on the landing page --}}
            <div class="su-quote">
                <span class="su-quote-icon"><i data-lucide="file-text"></i></span>
                <div>
                    <strong>Quotation for {{ $quoteTank['type'] }}</strong>
                    <span>We'll remember your choice — after your email is verified and GMD approves your registration, your first login opens this tank's quotation form.</span>
                </div>
            </div>
            @endif

            <form method="POST" action="{{ route('signup.post') }}" id="signupForm" autocomplete="off">
                @csrf

                {{-- ── 1 · Your details ── --}}
                <div class="signup-card" data-su-section>
                    <div class="signup-section-head">
                        <span class="su-num">1</span>
                        <div>
                            <div class="su-sec-title">Your details</div>
                            <div class="su-sec-sub">We use these to contact you about your quotations and projects.</div>
                        </div>
                    </div>
                    <div class="signup-body-pad">

                        <div class="su-form-group">
                            <label for="fullName">Full Name / Company Name <span class="su-req">*</span></label>
                            <div class="su-field">
                                <i data-lucide="user" class="su-lead"></i>
                                <input type="text" name="full_name" id="fullName" required autofocus maxlength="255"
                                       value="{{ old('full_name') }}"
                                       placeholder="e.g. Juan Dela Cruz or ABC Trading"
                                       class="{{ $errors->has('full_name') ? 'input-error' : '' }}">
                                {!! $okTick !!}
                            </div>
                            @error('full_name')<span class="field-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="su-form-row">
                            <div class="su-form-group">
                                <label for="signupEmail">Email Address <span class="su-req">*</span></label>
                                <div class="su-field">
                                    <i data-lucide="mail" class="su-lead"></i>
                                    <input type="email" name="email" id="signupEmail" required
                                           value="{{ old('email') }}"
                                           placeholder="you@example.com"
                                           class="{{ $errors->has('email') ? 'input-error' : '' }}">
                                    {!! $okTick !!}
                                </div>
                                {{-- filled by the server on submit, or live as soon as an already-registered email is typed --}}
                                <span class="field-error" id="emailError" style="{{ $errors->has('email') ? '' : 'display:none;' }}">{{ $errors->first('email') }}</span>
                            </div>
                            <div class="su-form-group">
                                <label for="contactNumber">Mobile Number <span class="su-req">*</span></label>
                                <div class="su-field">
                                    <i data-lucide="smartphone" class="su-lead"></i>
                                    {{-- digits only: PH mobile number 09XXXXXXXXX --}}
                                    <input type="tel" name="contact_number" id="contactNumber" required
                                           inputmode="numeric" maxlength="11" autocomplete="tel"
                                           pattern="09[0-9]{9}"
                                           title="Mobile number — 11 digits starting with 09 (09XXXXXXXXX)"
                                           value="{{ old('contact_number') }}"
                                           placeholder="09XXXXXXXXX"
                                           class="{{ $errors->has('contact_number') ? 'input-error' : '' }}">
                                    {!! $okTick !!}
                                </div>
                                <span class="field-error" id="contactError" style="{{ $errors->has('contact_number') ? '' : 'display:none;' }}">
                                    {{ $errors->first('contact_number') }}
                                </span>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- ── 2 · Address ── --}}
                <div class="signup-card" data-su-section>
                    <div class="signup-section-head">
                        <span class="su-num">2</span>
                        <div>
                            <div class="su-sec-title">Address</div>
                            <div class="su-sec-sub">Choose your region first — the next lists fill in from it.</div>
                        </div>
                    </div>
                    <div class="signup-body-pad">

                        <div class="su-form-row">
                            <div class="su-form-group" id="regionGroup">
                                <label for="regionSelect">Region <span class="su-req">*</span></label>
                                <select name="region" id="regionSelect" required class="{{ $errors->has('region') ? 'input-error' : '' }}">
                                    <option value="" disabled hidden selected>Select Region</option>
                                </select>
                                @error('region')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="su-form-group" id="provinceGroup">
                                <label for="provinceSelect">Province <span class="su-req">*</span></label>
                                <select name="province" id="provinceSelect" required disabled class="{{ $errors->has('province') ? 'input-error' : '' }}">
                                    <option value="" disabled hidden selected>Select Province</option>
                                </select>
                                @error('province')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="su-form-row" style="margin-top:16px;">
                            <div class="su-form-group" id="cityGroup">
                                <label for="citySelect">City / Municipality <span class="su-req">*</span></label>
                                <select name="city" id="citySelect" required disabled class="{{ $errors->has('city') ? 'input-error' : '' }}">
                                    <option value="" disabled hidden selected>Select City / Municipality</option>
                                </select>
                                @error('city')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="su-form-group" id="barangayGroup">
                                <label for="barangaySelect">Barangay <span class="su-req">*</span></label>
                                <select name="barangay" id="barangaySelect" required disabled class="{{ $errors->has('barangay') ? 'input-error' : '' }}">
                                    <option value="" disabled hidden selected>Select Barangay</option>
                                </select>
                                @error('barangay')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="su-form-group" style="margin-top:16px;">
                            <label for="streetAddress">Street / House No. / Building <span class="su-opt">(optional)</span></label>
                            <div class="su-field">
                                <i data-lucide="map-pin" class="su-lead"></i>
                                <input type="text" name="street_address" id="streetAddress" maxlength="500"
                                       value="{{ old('street_address') }}"
                                       placeholder="e.g. 123 East Service Road"
                                       class="{{ $errors->has('street_address') ? 'input-error' : '' }}">
                            </div>
                            @error('street_address')<span class="field-error">{{ $message }}</span>@enderror
                        </div>

                    </div>
                </div>

                {{-- ── 3 · Terms & next steps ── (username and password are set up after the admin approves) --}}
                <div class="signup-card" data-su-section>
                    <div class="signup-section-head">
                        <span class="su-num">3</span>
                        <div>
                            <div class="su-sec-title">Terms &amp; next steps</div>
                            <div class="su-sec-sub">No password yet — you create it after approval.</div>
                        </div>
                    </div>
                    <div class="signup-body-pad">

                        <ol class="su-next-steps">
                            <li><i data-lucide="mail-check"></i><strong>Verify your email</strong>We send a link to the address above.</li>
                            <li><i data-lucide="shield-check"></i><strong>Wait for approval</strong>Our team reviews your registration.</li>
                            <li><i data-lucide="key-round"></i><strong>Set up your account</strong>Create your password from our email. Your username is assigned for you.</li>
                        </ol>

                        <label class="terms-row {{ $errors->has('terms') ? 'input-error' : '' }}" for="termsCheck">
                            <input type="checkbox" name="terms" id="termsCheck" value="1" required {{ old('terms') ? 'checked' : '' }}>
                            <span>
                                I have read and agree to the
                                <button type="button" class="terms-link" id="openTerms">Terms and Conditions</button>
                                of GMD South Phils.
                            </span>
                        </label>
                        {{-- shown by the server (failed submit) or right away by the browser check below --}}
                        <span class="field-error" id="termsError" style="{{ $errors->has('terms') ? '' : 'display:none;' }}">
                            {{ $errors->first('terms') ?: 'Please agree to the Terms and Conditions to create an account.' }}
                        </span>

                    </div>
                </div>

                <button type="submit" class="signup-submit" id="submitBtn">
                    Submit Registration
                    <i data-lucide="arrow-right"></i>
                </button>

                <div class="su-secure"><i data-lucide="lock"></i> Your details are protected under the Data Privacy Act of 2012.</div>

                <div class="signup-footer-link">
                    Already have an account? <a href="{{ route('login') }}">Log In</a>
                </div>

            </form>

        </div>
        </main>
    </div>

    {{-- ── Terms and Conditions modal ── --}}
    <div class="terms-overlay" id="termsModal" role="dialog" aria-modal="true" aria-labelledby="termsTitle">
        <div class="terms-modal">
            <div class="terms-head">
                <div class="terms-head-icon"><i data-lucide="file-text" style="width:20px;height:20px;"></i></div>
                <div class="terms-head-text">
                    <h2 id="termsTitle">Terms and Conditions</h2>
                    <p>GMD South Phils Metal Fabrication Works · Client Portal</p>
                    <span class="terms-updated"><i data-lucide="calendar-check" style="width:12px;height:12px;"></i>Last updated October 11, 2026</span>
                </div>
                <button type="button" class="terms-close" id="closeTerms" aria-label="Close">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
                <div class="terms-progress" aria-hidden="true"><span id="termsProgress"></span></div>
            </div>

            <div class="terms-body" id="termsBody">
                <section class="terms-sec">
                    <span class="terms-num">1</span>
                    <div class="terms-sec-body">
                        <h3>Acceptance of Terms</h3>
                        <p>By creating an account on the GMD South Phils Client Portal, you agree to these Terms and Conditions. If you do not agree, please do not create an account or use the portal.</p>
                    </div>
                </section>

                <section class="terms-sec">
                    <span class="terms-num">2</span>
                    <div class="terms-sec-body">
                        <h3>Registration and Account Approval</h3>
                        <p>You must give accurate and complete information when registering, including your name or company name, mobile number, email address and address. Each email address and mobile number can be used for only one account.</p>
                        <ol class="terms-steps">
                            <li>
                                <span class="terms-step-icon done"><i data-lucide="send" style="width:14px;height:14px;"></i></span>
                                <div>
                                    <div class="terms-step-title">Submit your registration</div>
                                    <div class="terms-step-text">Fill out this form and agree to these terms.</div>
                                </div>
                            </li>
                            <li>
                                <span class="terms-step-icon"><i data-lucide="mail-check" style="width:14px;height:14px;"></i></span>
                                <div>
                                    <div class="terms-step-title">Verify your email <span class="terms-tag">Link expires in 10 minutes</span></div>
                                    <div class="terms-step-text">We send a verification link to your email address. You can request a new link if it expires.</div>
                                </div>
                            </li>
                            <li>
                                <span class="terms-step-icon"><i data-lucide="user-check" style="width:14px;height:14px;"></i></span>
                                <div>
                                    <div class="terms-step-title">Review and approval</div>
                                    <div class="terms-step-text">GMD South Phils reviews your registration. We may decline it if the information is false, incomplete or cannot be confirmed. You are notified of the result by email.</div>
                                </div>
                            </li>
                            <li>
                                <span class="terms-step-icon"><i data-lucide="key-round" style="width:14px;height:14px;"></i></span>
                                <div>
                                    <div class="terms-step-title">Set up your account <span class="terms-tag">Link valid for 48 hours</span></div>
                                    <div class="terms-step-text">Once approved, we email you a setup link. Your username (CGMD-XXXX) is assigned automatically, and you create your own password.</div>
                                </div>
                            </li>
                        </ol>
                        <p>You can log in to the Client Portal only after you finish setting up your account.</p>
                    </div>
                </section>

                <section class="terms-sec">
                    <span class="terms-num">3</span>
                    <div class="terms-sec-body">
                        <h3>Account Security</h3>
                        <ul>
                            <li>You are responsible for keeping your username and password confidential, and for all activity under your account.</li>
                            <li>Do not share your verification or account setup links with anyone. Each link works only for your registration.</li>
                            <li>Tell us right away if you suspect unauthorized access to your account. We may deactivate an account to protect it or to stop misuse of the portal.</li>
                        </ul>
                    </div>
                </section>

                <section class="terms-sec">
                    <span class="terms-num">4</span>
                    <div class="terms-sec-body">
                        <h3>Quotation Requests</h3>
                        <ul>
                            <li>You may request quotations for storage tank fabrication through the portal. A quotation request is not a confirmed order.</li>
                            <li>Quotations are based on the specifications you provide. Prices may change if the specifications, materials or scope change after the quotation is issued.</li>
                            <li>A project starts only after you approve the quotation and the required down payment is received.</li>
                        </ul>
                    </div>
                </section>

                <section class="terms-sec">
                    <span class="terms-num">5</span>
                    <div class="terms-sec-body">
                        <h3>Payment Terms</h3>
                        <p>Payments follow the scheme stated in your approved quotation:</p>
                        <div class="terms-plans">
                            <div class="terms-plan">
                                <div class="terms-plan-name">50 / 50</div>
                                <div class="terms-plan-sub">Two payments</div>
                                <div class="terms-split"><span style="flex:50"></span><span style="flex:50"></span></div>
                                <ul>
                                    <li><span>Down payment, before work starts</span><b>50%</b></li>
                                    <li><span>Final payment, before pick-up</span><b>50%</b></li>
                                </ul>
                            </div>
                            <div class="terms-plan">
                                <div class="terms-plan-name">50 / 30 / 20</div>
                                <div class="terms-plan-sub">Three payments</div>
                                <div class="terms-split"><span style="flex:50"></span><span style="flex:30"></span><span style="flex:20"></span></div>
                                <ul>
                                    <li><span>Down payment, before work starts</span><b>50%</b></li>
                                    <li><span>Progress payment, during fabrication</span><b>30%</b></li>
                                    <li><span>Final payment, before pick-up</span><b>20%</b></li>
                                </ul>
                            </div>
                        </div>
                        <ul>
                            <li>A project phase that requires a payment cannot begin until that payment is recorded. Delayed payments may also delay the project schedule.</li>
                            <li>Proof of payment uploaded through the portal is reviewed and confirmed by GMD South Phils. A payment is considered received only once it is confirmed.</li>
                        </ul>
                    </div>
                </section>

                <section class="terms-sec">
                    <span class="terms-num">6</span>
                    <div class="terms-sec-body">
                        <h3>Project Schedule, Completion and Pick-up</h3>
                        <ul>
                            <li>Start and end dates are planned estimates covering the project from planning to completion. They may be adjusted because of material availability, specification changes, delayed payments, weather or other events outside our control.</li>
                            <li>A project is completed once fabrication, inspection, painting and final output are done. Pick-up is arranged separately after completion and after the final payment is recorded.</li>
                            <li>You can follow your project's phases, updates, photos and pick-up status through the portal.</li>
                        </ul>
                    </div>
                </section>

                <section class="terms-sec">
                    <span class="terms-num">7</span>
                    <div class="terms-sec-body">
                        <h3>Changes and Cancellations</h3>
                        <p>Requests to change specifications or cancel a project must be made in writing through the portal or directly with GMD South Phils. Work already done and materials already purchased for your project may be charged even if the project is cancelled.</p>
                    </div>
                </section>

                <section class="terms-sec">
                    <span class="terms-num">8</span>
                    <div class="terms-sec-body">
                        <h3>Data Privacy</h3>
                        <p>We collect and use your personal information only to review your registration and to manage your account, quotations, projects, payments and communications with you. We do not sell your personal information. You may ask to access or correct the information we hold about you. If your registration is declined, we keep your information only as long as needed for our records.</p>
                        <div class="terms-callout">
                            <i data-lucide="shield-check" style="width:16px;height:16px;"></i>
                            <span>Your information is handled in line with the Data Privacy Act of 2012 (Republic Act No. 10173).</span>
                        </div>
                    </div>
                </section>

                <section class="terms-sec">
                    <span class="terms-num">9</span>
                    <div class="terms-sec-body">
                        <h3>Communications</h3>
                        <p>By registering, you agree to receive emails and portal notifications about your registration status, account setup, quotations, project progress, pick-up and payments.</p>
                    </div>
                </section>

                <section class="terms-sec">
                    <span class="terms-num">10</span>
                    <div class="terms-sec-body">
                        <h3>Use of the Portal</h3>
                        <p>You agree not to misuse the portal, including attempting to access other clients' information, uploading harmful files, or interfering with the system.</p>
                    </div>
                </section>

                <section class="terms-sec">
                    <span class="terms-num">11</span>
                    <div class="terms-sec-body">
                        <h3>Changes to These Terms</h3>
                        <p>GMD South Phils may update these Terms and Conditions from time to time. Continued use of the portal after an update means you accept the updated terms.</p>
                    </div>
                </section>

                <section class="terms-sec">
                    <span class="terms-num">12</span>
                    <div class="terms-sec-body">
                        <h3>Contact Us</h3>
                        <p>For questions about these Terms and Conditions, please contact GMD South Phils Metal Fabrication Works through the portal or our official contact details.</p>
                    </div>
                </section>
            </div>

            <div class="terms-foot">
                <span class="terms-foot-hint"><i data-lucide="info" style="width:14px;height:14px;"></i>Clicking "I Agree" ticks the checkbox for you.</span>
                <div class="terms-foot-btns">
                    <button type="button" class="terms-btn" id="declineTerms">Close</button>
                    <button type="button" class="terms-btn primary" id="agreeTerms"><i data-lucide="check" style="width:16px;height:16px;"></i>I Agree</button>
                </div>
            </div>
        </div>
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


    /* Live "already registered?" check for a sign-up field — asks the server shortly after typing
       stops; only the latest answer counts. Submitting still runs the real check on the server. */
    function liveAvailability(field, onResult) {
        var timer = null, seq = 0;
        return function (value) {
            clearTimeout(timer);
            var mine = ++seq;
            timer = setTimeout(function () {
                fetch(@json(route('signup.check_availability')) + '?field=' + field + '&value=' + encodeURIComponent(value),
                      { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (d) { if (d && mine === seq) onResult(!!d.taken, value); })
                    .catch(function () {});
            }, 400);
        };
    }

    // ── Contact number: digits only, PH mobile number (09XXXXXXXXX), not already registered ──
    (function setupContactNumber() {
        var input = document.getElementById('contactNumber');
        var error = document.getElementById('contactError');
        if (!input) return;
        var MOBILE = /^09\d{9}$/;   // 11 digits
        var TAKEN  = 'This contact number is already registered to another account. Please use a different number.';
        var taken  = {{ $errors->has('contact_number') && str_contains($errors->first('contact_number'), 'already registered') ? 'true' : 'false' }};

        function message(v) {
            if (!v) return '';
            if (!/^09/.test(v)) return 'Mobile numbers start with 09 (09XXXXXXXXX).';
            if (!MOBILE.test(v)) return 'Mobile numbers have 11 digits (09XXXXXXXXX).';
            return taken ? TAKEN : '';
        }
        function show(msg) {
            error.textContent = msg;
            error.style.display = msg ? 'block' : 'none';
            input.classList.toggle('input-error', !!msg);
        }

        var check = liveAvailability('contact_number', function (isTaken, value) {
            if (value !== input.value) return;   // the number changed while we were asking
            taken = isTaken;
            show(isTaken ? TAKEN : '');
        });

        // strip anything that isn't a digit as it's typed or pasted (e.g. "0917-123 4567" → "09171234567")
        input.addEventListener('input', function () {
            var digits = input.value.replace(/\D/g, '').slice(0, 11);
            if (input.value !== digits) input.value = digits;
            taken = false;
            if (MOBILE.test(digits)) { show(''); check(digits); }   // complete number → is it already registered?
        });
        input.addEventListener('blur', function () { show(message(input.value)); });
        input.addEventListener('invalid', function () { show(message(input.value) || 'Please enter your contact number.'); });
    })();

    // ── Email: same live "already registered" check once a complete address is typed ──
    (function setupEmail() {
        var input = document.getElementById('signupEmail');
        var error = document.getElementById('emailError');
        if (!input) return;
        var TAKEN = 'An account with this email address already exists.';
        var LOOKS_COMPLETE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

        function show(msg) {
            error.textContent = msg;
            error.style.display = msg ? 'block' : 'none';
            input.classList.toggle('input-error', !!msg);
        }
        var check = liveAvailability('email', function (isTaken, value) {
            if (value !== input.value.trim()) return;
            show(isTaken ? TAKEN : '');
        });
        input.addEventListener('input', function () {
            var v = input.value.trim();
            if (error.textContent) show('');            // clear the old message as soon as they edit
            if (LOOKS_COMPLETE.test(v)) check(v);
        });
    })();

    // ── Terms and Conditions modal ──
    (function setupTerms() {
        var modal    = document.getElementById('termsModal');
        var checkbox = document.getElementById('termsCheck');
        var errorEl  = document.getElementById('termsError');

        // Required: show a clear inline message (not just the browser's small bubble) and bring it into view
        function showTermsError() {
            checkbox.closest('.terms-row').classList.add('input-error');
            errorEl.style.display = 'block';
            checkbox.closest('.terms-row').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        function clearTermsError() {
            checkbox.closest('.terms-row').classList.remove('input-error');
            errorEl.style.display = 'none';
        }
        checkbox.addEventListener('invalid', showTermsError);

        var body     = document.getElementById('termsBody');
        var progress = document.getElementById('termsProgress');
        function updateProgress() {
            var max = body.scrollHeight - body.clientHeight;
            progress.style.width = (max > 0 ? Math.min(100, body.scrollTop / max * 100) : 100) + '%';
        }
        body.addEventListener('scroll', updateProgress);

        function openTerms(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }   // the link sits inside the checkbox label
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            body.scrollTop = 0;
            updateProgress();
        }
        function closeTerms() {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }

        document.getElementById('openTerms').addEventListener('click', openTerms);
        document.getElementById('closeTerms').addEventListener('click', closeTerms);
        document.getElementById('declineTerms').addEventListener('click', closeTerms);
        document.getElementById('agreeTerms').addEventListener('click', function () {
            checkbox.checked = true;
            clearTermsError();
            closeTerms();
        });
        modal.addEventListener('click', function (e) { if (e.target === modal) closeTerms(); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('show')) closeTerms();
        });
        checkbox.addEventListener('change', function () {
            if (checkbox.checked) clearTermsError();
        });

        // Final guard before the form is sent (also covers browsers that skip the required check)
        document.getElementById('signupForm').addEventListener('submit', function (e) {
            if (!checkbox.checked) {
                e.preventDefault();
                e.stopImmediatePropagation();   // keep the "Create Account" button from switching to its spinner
                showTermsError();
            }
        });
    })();

    document.getElementById('signupForm').addEventListener('submit', function () {
        var btn = document.getElementById('submitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<div class="btn-spinner"></div><span>Submitting registration...</span>';
        }
    });

    // ── Live progress: the 8 required items (name, email, mobile, 4 address levels, terms) ──
    (function setupProgress() {
        var form  = document.getElementById('signupForm');
        var bar   = document.getElementById('suProgressBar');
        var label = document.getElementById('suProgressLabel');
        var wrap  = document.getElementById('suProgress');
        var ids   = ['fullName', 'signupEmail', 'contactNumber', 'regionSelect', 'provinceSelect', 'citySelect', 'barangaySelect', 'termsCheck'];

        function isDone(el) {
            if (!el) return false;
            if (el.type === 'checkbox') return el.checked;
            if (el.classList.contains('input-error')) return false;
            return !!String(el.value || '').trim() && el.checkValidity();
        }
        function update() {
            var done = ids.filter(function (id) { return isDone(document.getElementById(id)); }).length;
            bar.style.width = (done / ids.length * 100) + '%';
            label.textContent = done === ids.length ? 'All set — ready to submit' : done + ' of ' + ids.length + ' done';
            wrap.classList.toggle('is-done', done === ids.length);
            // a section's number turns green once everything required in it is filled
            document.querySelectorAll('[data-su-section]').forEach(function (card) {
                var req = card.querySelectorAll('input[required], select[required]');
                var ok  = req.length && Array.prototype.every.call(req, isDone);
                card.classList.toggle('is-complete', !!ok);
            });
        }
        form.addEventListener('input', update);
        form.addEventListener('change', update);
        // the address lists and the live email/number checks change things without an input event
        new MutationObserver(update).observe(form, { subtree: true, attributes: true, attributeFilter: ['class', 'disabled'] });
        document.getElementById('agreeTerms').addEventListener('click', function () { setTimeout(update, 0); });
        update();
    })();

    // ── Entrance (GSAP): panel content, then the form cards ──
    (function entrance() {
        var root = document.documentElement;
        if (!window.gsap || !root.classList.contains('su-anim')) { root.classList.remove('su-anim'); return; }
        var left  = document.querySelectorAll('.su-pitch > *, .su-steps');
        var right = document.querySelectorAll('.su-head, .su-progress, .su-quote, .signup-card, .signup-submit');
        gsap.set(left, { opacity: 0, x: -24 });
        gsap.set(right, { opacity: 0, y: 22 });
        root.classList.remove('su-anim');
        var tl = gsap.timeline({ defaults: { ease: 'power3.out' } });
        tl.to(left, { opacity: 1, x: 0, duration: 0.7, stagger: 0.08, clearProps: 'transform,opacity' }, 0)
          .to(right, { opacity: 1, y: 0, duration: 0.65, stagger: 0.08, clearProps: 'transform,opacity' }, 0.1)
          .from('.su-steps li', { opacity: 0, y: 8, duration: 0.4, stagger: 0.07, clearProps: 'transform,opacity' }, 0.45);
    })();
    </script>
</body>
</html>
