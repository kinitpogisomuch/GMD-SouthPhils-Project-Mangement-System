<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | GMD South Phils</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <style>
        /* ===== Layout: brand panel (left, sticky) + form (right) — a sibling of the login page ===== */
        body { min-height: 100vh; background: var(--cream); }
        .su-page { display: grid; grid-template-columns: minmax(360px, 0.9fr) 1.1fr; min-height: 100vh; }

        .su-aside {
            position: sticky; top: 0; height: 100vh; overflow: hidden;
            display: flex; flex-direction: column; justify-content: space-between; gap: 24px;
            padding: 32px 48px 56px; color: #fff;     /* extra bottom room keeps "How it works" clear of the stripe */
            background: linear-gradient(180deg, #333333 0%, #1a1a1a 100%);
        }
        .su-aside.has-bg-photo {
            background-image: linear-gradient(180deg, rgba(26,26,26,.72) 0%, rgba(26,26,26,.94) 100%), var(--su-photo);
            background-size: cover; background-position: center;
        }
        .su-aside::after {          /* hazard stripe, like the login page */
            content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 6px;
            background: repeating-linear-gradient(135deg, var(--accent) 0 14px, rgba(0,0,0,.55) 14px 28px);
        }
        .su-aside-glow {
            position: absolute; top: -160px; right: -140px; width: 460px; height: 460px; pointer-events: none;
            background: radial-gradient(circle, rgba(245,180,0,.16) 0%, transparent 70%);
        }
        .su-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: #fff; position: relative; }
        .su-brand img { width: 44px; height: 44px; border-radius: 50%; border: 1.5px solid rgba(255,255,255,.25); }
        .su-brand-name { font-size: 18px; font-weight: 900; letter-spacing: -.3px; }
        .su-brand-name span { color: rgba(255,255,255,.55); }

        .su-pitch { position: relative; }
        .su-badge {
            display: inline-flex; align-items: center; gap: 8px; margin-bottom: 18px;
            padding: 6px 14px; border-radius: 999px; font-size: 11px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase;
            background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.16); color: rgba(255,255,255,.85);
        }
        .su-badge svg { width: 13px; height: 13px; color: var(--accent); }
        .su-pitch h1 { font-size: clamp(28px, 2.6vw, 38px); font-weight: 900; line-height: 1.12; letter-spacing: -1px; margin: 0 0 12px; }
        .su-pitch h1 em { font-style: normal; color: var(--accent); }
        .su-pitch p { font-size: 14.5px; line-height: 1.7; color: rgba(255,255,255,.68); max-width: 440px; margin: 0; }

        .su-perks { list-style: none; margin: 22px 0 0; padding: 0; display: grid; gap: 10px; }
        .su-perks li { display: flex; align-items: center; gap: 10px; font-size: 13.5px; font-weight: 600; color: rgba(255,255,255,.85); }
        .su-perks svg { width: 16px; height: 16px; color: var(--accent); flex-shrink: 0; }

        /* registration timeline */
        .su-steps ol { list-style: none; margin: 0; padding: 0; }
        .su-steps { margin: 0; padding: 18px; position: relative; border-radius: 18px;
                    background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.10); backdrop-filter: blur(6px); }
        .su-steps-title { font-size: 11px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: rgba(255,255,255,.5); margin-bottom: 12px; }
        .su-steps li { position: relative; display: flex; gap: 12px; padding-bottom: 14px; }
        .su-steps li:last-child { padding-bottom: 0; }
        .su-steps li::before { content: ''; position: absolute; left: 14px; top: 30px; bottom: 2px; width: 2px; background: rgba(255,255,255,.14); }
        .su-steps li:last-child::before { display: none; }
        .su-step-dot {
            width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
            border: 1.5px solid rgba(255,255,255,.28); color: rgba(255,255,255,.75); background: rgba(26,26,26,.6); position: relative; z-index: 1;
        }
        .su-step-dot svg { width: 14px; height: 14px; }
        .su-steps li.is-current .su-step-dot { background: var(--accent); border-color: var(--accent); color: #1a1a1a; box-shadow: 0 0 0 5px rgba(245,180,0,.18); }
        .su-step-title { font-size: 13.5px; font-weight: 800; color: #fff; }
        .su-step-text { font-size: 12px; line-height: 1.5; color: rgba(255,255,255,.55); margin-top: 2px; }

        /* ===== Form column ===== */
        .su-main { display: flex; justify-content: center; padding: 48px 32px 56px; }
        .signup-wrap { width: 100%; max-width: 640px; }

        .su-head { margin-bottom: 18px; }
        .su-head h2 { font-size: 28px; font-weight: 900; letter-spacing: -.6px; color: var(--dark); margin: 0 0 6px; }
        .su-head p { font-size: 13.5px; color: var(--muted); margin: 0; }
        .su-head p a { color: var(--dark); font-weight: 800; text-decoration: none; }
        .su-head p a:hover { text-decoration: underline; }

        /* live progress */
        .su-progress { display: flex; align-items: center; gap: 12px; margin: 18px 0 22px; }
        .su-progress-track { flex: 1; height: 8px; border-radius: 999px; background: var(--cream-deep); overflow: hidden; }
        .su-progress-bar { height: 100%; width: 0; border-radius: inherit; background: linear-gradient(90deg, var(--dark) 0%, #555 100%); transition: width .45s cubic-bezier(.22,1,.36,1), background .3s; }
        .su-progress.is-done .su-progress-bar { background: linear-gradient(90deg, #207A3A 0%, #2f9e50 100%); }
        .su-progress-label { font-size: 12px; font-weight: 800; color: var(--muted); white-space: nowrap; font-variant-numeric: tabular-nums; }
        .su-progress.is-done .su-progress-label { color: #207A3A; }

        /* the tank chosen on the landing page */
        .su-quote {
            display: flex; align-items: center; gap: 14px; margin-bottom: 18px; padding: 14px 16px;
            border-radius: 18px; background: #fffbeb; border: 1px solid #fde68a;
        }
        .su-quote-icon { width: 42px; height: 42px; border-radius: 12px; background: var(--accent); color: #1a1a1a; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .su-quote-icon svg { width: 20px; height: 20px; }
        .su-quote strong { display: block; font-size: 14px; color: var(--dark); }
        .su-quote span { font-size: 12.5px; color: #78350f; line-height: 1.5; }

        .signup-card {
            background: var(--white); border: 1px solid var(--border); border-radius: 24px;
            box-shadow: 0 1px 2px rgba(0,0,0,.04), 0 14px 34px rgba(0,0,0,.06);
            margin-bottom: 16px; overflow: hidden;
            transition: box-shadow .25s ease, border-color .25s ease;
        }
        .signup-card:focus-within { border-color: #cfcfcf; box-shadow: 0 1px 2px rgba(0,0,0,.04), 0 18px 40px rgba(0,0,0,.09); }
        .signup-section-head { display: flex; align-items: center; gap: 12px; padding: 18px 24px 4px; }
        .su-num {
            width: 28px; height: 28px; border-radius: 9px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
            background: var(--dark); color: #fff; font-size: 12.5px; font-weight: 900; transition: background .3s;
        }
        .signup-card.is-complete .su-num { background: #207A3A; }
        .su-sec-title { font-size: 15px; font-weight: 900; color: var(--dark); letter-spacing: -.2px; }
        .su-sec-sub { font-size: 12px; color: var(--muted); margin-top: 1px; }
        .signup-body-pad { padding: 16px 24px 24px; }

        .su-form-group { margin-bottom: 16px; position: relative; }
        .su-form-group:last-child { margin-bottom: 0; }
        .su-form-group label { font-size: 12.5px; font-weight: 800; color: var(--dark); display: block; margin-bottom: 7px; }
        .su-req { color: #dc2626; }
        .su-opt { font-size: 11px; color: var(--muted); font-weight: 500; }
        .su-form-row { display: grid; gap: 16px; grid-template-columns: 1fr 1fr; }
        .su-form-row > .su-form-group { margin-bottom: 0; }   /* the row's gap does the spacing */
        .su-form-group + .su-form-row { margin-top: 16px; }

        /* inputs with a leading icon and a "looks good" tick */
        .su-field { position: relative; }
        .su-field .su-lead { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 17px; height: 17px; color: #9a9a9a; pointer-events: none; transition: color .2s; }
        .su-field:focus-within .su-lead { color: var(--dark); }
        .su-field .su-ok { position: absolute; right: 14px; top: 50%; width: 18px; height: 18px; margin-top: -9px; color: #207A3A; opacity: 0; transform: scale(.6); transition: opacity .2s, transform .25s cubic-bezier(.34,1.56,.64,1); pointer-events: none; }
        .su-field input:not(:placeholder-shown):valid:not(.input-error) ~ .su-ok { opacity: 1; transform: scale(1); }

        .su-form-group input,
        .su-form-group select {
            width: 100%; height: 50px; border: 1.5px solid var(--border); border-radius: 14px;
            font-size: 14px; color: var(--dark); background-color: var(--cream-soft, #fafafa);
            outline: none; box-sizing: border-box; font-family: inherit;
            transition: border-color .2s, background-color .2s, box-shadow .2s;
        }
        .su-form-group input { padding: 0 42px 0 42px; }
        .su-form-group input::placeholder { color: #a3a3a3; }
        .su-form-group input:hover, .su-form-group select:not(:disabled):hover { border-color: #cfcfcf; }
        .su-form-group input:focus, .su-form-group select:focus {
            background-color: var(--white); border-color: var(--dark); box-shadow: 0 0 0 4px rgba(0,0,0,.07);
        }
        .su-form-group input.input-error, .su-form-group select.input-error { border-color: #f87171; background-color: #fff7f7; }
        .su-form-group input.input-error:focus { box-shadow: 0 0 0 4px rgba(220,38,38,.12); }
        .field-error { font-size: 11.5px; color: var(--danger); margin-top: 6px; display: block; font-weight: 600; line-height: 1.45; }

        .su-form-group select {
            padding: 0 40px 0 14px; appearance: none; -webkit-appearance: none; cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23666666' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 15px center;
        }
        .su-form-group select:disabled { color: #a3a3a3; cursor: not-allowed; background-color: var(--cream); }
        .su-form-group select:valid:not(:disabled) { color: var(--dark); }
        .sel-loading::after {
            content: ''; position: absolute; right: 40px; top: 43px; width: 14px; height: 14px;
            border: 2px solid var(--border); border-top-color: var(--dark); border-radius: 50%;
            animation: spin .7s linear infinite; pointer-events: none;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* what happens next */
        .su-next-steps { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .su-next-steps li { padding: 12px; border-radius: 14px; background: var(--cream-soft, #fafafa); border: 1px solid var(--border); font-size: 12px; line-height: 1.5; color: var(--muted); }
        .su-next-steps svg { width: 18px; height: 18px; color: var(--dark); display: block; margin-bottom: 6px; }
        .su-next-steps strong { display: block; color: var(--dark); font-size: 12.5px; margin-bottom: 2px; }

        .terms-row {
            display: flex; align-items: flex-start; gap: 12px; margin-top: 16px; padding: 14px 16px;
            border-radius: 14px; border: 1.5px solid var(--border); background: var(--white);
            font-size: 13px; color: var(--dark); line-height: 1.5; cursor: pointer; transition: border-color .2s, background .2s;
        }
        .terms-row:hover { border-color: #cfcfcf; }
        .terms-row:has(input:checked) { border-color: #86efac; background: #f0fdf4; }
        .terms-row input[type="checkbox"] { width: 18px; height: 18px; margin: 1px 0 0; flex-shrink: 0; accent-color: #207A3A; cursor: pointer; }
        .terms-link { background: none; border: none; padding: 0; font: inherit; cursor: pointer; color: var(--dark); font-weight: 800; text-decoration: underline; text-underline-offset: 2px; text-decoration-color: var(--accent); text-decoration-thickness: 2px; }
        .terms-row.input-error { border-color: #f87171; background: #fff7f7; color: var(--danger); }

        .signup-submit {
            position: relative; overflow: hidden; width: 100%; height: 56px; border: none; margin-top: 6px;
            background: linear-gradient(135deg, #333 0%, #1a1a1a 100%); color: var(--white);
            border-radius: 16px; font-size: 15px; font-weight: 900; cursor: pointer; font-family: inherit;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 10px 24px rgba(0,0,0,.16); transition: transform .2s, box-shadow .2s;
        }
        .signup-submit::after {
            content: ''; position: absolute; top: 0; bottom: 0; left: 0; width: 40%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.18), transparent);
            transform: translateX(-130%) skewX(-18deg);
        }
        .signup-submit:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 16px 30px rgba(0,0,0,.22); }
        .signup-submit:hover:not(:disabled)::after { animation: suSheen .8s cubic-bezier(.16,.84,.44,1); }
        .signup-submit:active:not(:disabled) { transform: translateY(0) scale(.99); }
        .signup-submit:disabled { background: var(--muted); cursor: not-allowed; transform: none; box-shadow: none; }
        .signup-submit svg { width: 18px; height: 18px; transition: transform .2s; }
        .signup-submit:hover:not(:disabled) svg { transform: translateX(3px); }
        @keyframes suSheen { from { transform: translateX(-130%) skewX(-18deg); } to { transform: translateX(260%) skewX(-18deg); } }

        .signup-footer-link { text-align: center; margin-top: 14px; font-size: 13px; color: var(--muted); }
        .signup-footer-link a { color: var(--dark); font-weight: 800; text-decoration: none; }
        .signup-footer-link a:hover { text-decoration: underline; }
        .su-secure { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 10px; font-size: 11.5px; color: #8a8a8a; }
        .su-secure svg { width: 13px; height: 13px; color: var(--accent-dark); }

        :focus-visible { outline: none; }
        .terms-link:focus-visible, .signup-submit:focus-visible, .su-head a:focus-visible, .signup-footer-link a:focus-visible { box-shadow: 0 0 0 3px #fff, 0 0 0 5px rgba(42,78,170,.4); border-radius: 8px; }

        /* GSAP entrance: pre-hidden only when GSAP actually loaded (html.su-anim), with a failsafe */
        html.su-anim .su-pitch > *, html.su-anim .su-steps, html.su-anim .su-head, html.su-anim .su-progress,
        html.su-anim .su-quote, html.su-anim .signup-card, html.su-anim .signup-submit { opacity: 0; }

        /* ===== Tablet / phone: the panel becomes a compact header ===== */
        @media (max-width: 960px) {
            .su-page { grid-template-columns: 1fr; }
            .su-aside { position: relative; height: auto; padding: 28px 24px 30px; gap: 20px; }
            .su-pitch h1 { font-size: 26px; }
            .su-pitch p { font-size: 13.5px; }
            .su-perks { display: none; }
            .su-steps { padding: 12px; }
            .su-steps ol { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
            .su-steps-title { margin-bottom: 8px; }
            .su-steps li { flex-direction: column; align-items: center; text-align: center; gap: 6px; padding: 0; }
            .su-steps li::before { display: none; }
            .su-step-text { display: none; }
            .su-step-title { font-size: 11px; }
            .su-main { padding: 28px 16px 44px; }
        }
        @media (max-width: 560px) {
            .su-form-row { grid-template-columns: 1fr; }
            .su-next-steps { grid-template-columns: 1fr; }
            .signup-section-head { padding: 16px 18px 2px; }
            .signup-body-pad { padding: 14px 18px 20px; }
            .su-head h2 { font-size: 24px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .su-progress-bar, .su-field .su-ok, .signup-submit, .signup-submit svg { transition: none; }
            .signup-submit:hover::after { animation: none; }
        }

        /* Terms and Conditions modal */
        .terms-overlay {
            position: fixed; inset: 0; z-index: 1000; background: rgba(0, 0, 0, 0.45);
            display: none; align-items: center; justify-content: center; padding: 16px;
        }
        .terms-overlay.show { display: flex; }
        .terms-modal {
            width: 100%; max-width: 680px; max-height: 88vh; background: var(--white);
            border-radius: 24px; box-shadow: 0 24px 60px rgba(0, 0, 0, 0.25);
            display: flex; flex-direction: column; overflow: hidden;
            animation: slideUpFadeIn 0.35s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .terms-head {
            position: relative;
            display: flex; align-items: center; gap: 14px;
            padding: 20px 24px; border-bottom: 1px solid var(--border); flex-shrink: 0;
        }
        .terms-head-icon {
            width: 44px; height: 44px; border-radius: 14px; flex-shrink: 0;
            background: var(--dark); color: var(--white);
            display: flex; align-items: center; justify-content: center;
        }
        .terms-head-text { flex: 1; min-width: 0; }
        .terms-head h2 { font-size: 18px; font-weight: 900; color: var(--dark); margin: 0; letter-spacing: -0.2px; }
        .terms-head p { font-size: 12px; color: var(--muted); margin: 3px 0 0; }
        .terms-updated {
            display: inline-flex; align-items: center; gap: 5px; margin-top: 8px;
            font-size: 11px; font-weight: 700; color: var(--muted);
            background: var(--cream); border: 1px solid var(--border); border-radius: 999px; padding: 3px 9px;
        }
        .terms-close {
            width: 36px; height: 36px; border-radius: 12px; border: 1px solid var(--border); background: var(--cream);
            display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--muted); flex-shrink: 0;
            align-self: flex-start; transition: 0.18s ease;
        }
        .terms-close:hover { background: var(--dark); border-color: var(--dark); color: var(--white); }
        /* reading progress along the bottom edge of the header */
        .terms-progress { position: absolute; left: 0; right: 0; bottom: -1px; height: 3px; background: transparent; }
        .terms-progress span { display: block; height: 100%; width: 0; background: var(--dark); transition: width 0.12s linear; }

        .terms-body { padding: 8px 24px 20px; overflow-y: auto; font-size: 13px; line-height: 1.65; color: #444; }
        .terms-sec { display: grid; grid-template-columns: 30px 1fr; gap: 0 12px; padding: 16px 0; border-bottom: 1px dashed var(--border); }
        .terms-sec:last-child { border-bottom: 0; }
        .terms-num {
            width: 30px; height: 30px; border-radius: 10px; background: var(--cream); border: 1px solid var(--border);
            font-size: 12px; font-weight: 900; color: var(--dark);
            display: flex; align-items: center; justify-content: center;
        }
        .terms-sec-body { min-width: 0; }
        .terms-sec h3 { font-size: 14px; font-weight: 900; color: var(--dark); margin: 4px 0 8px; }
        .terms-body p { margin: 0 0 8px; }
        .terms-body p:last-child { margin-bottom: 0; }
        .terms-body ul { margin: 0; padding-left: 18px; }
        .terms-body li { margin-bottom: 5px; }
        .terms-body li:last-child { margin-bottom: 0; }
        .terms-body li::marker { color: var(--muted-light, #999); }
        .terms-body strong { color: var(--dark); }

        /* registration steps as a small timeline */
        .terms-steps { list-style: none; margin: 10px 0 !important; padding: 0 !important; }
        .terms-steps li { position: relative; display: flex; gap: 12px; padding-bottom: 12px; margin: 0 !important; }
        .terms-steps li:last-child { padding-bottom: 0; }
        .terms-steps li::before {
            content: ''; position: absolute; left: 15px; top: 32px; bottom: 0; width: 2px; background: var(--border);
        }
        .terms-steps li:last-child::before { display: none; }
        .terms-step-icon {
            width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0; position: relative; z-index: 1;
            background: var(--white); border: 2px solid var(--dark); color: var(--dark);
            display: flex; align-items: center; justify-content: center;
        }
        .terms-step-icon.done { background: var(--dark); color: var(--white); }
        .terms-step-title { font-weight: 800; color: var(--dark); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .terms-tag {
            font-size: 10.5px; font-weight: 800; padding: 1px 8px; border-radius: 999px;
            background: var(--cream-deep, #ebebeb); color: var(--dark); white-space: nowrap;
        }
        .terms-step-text { font-size: 12.5px; color: var(--muted); margin-top: 2px; }

        /* payment schemes */
        .terms-plans { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 4px 0 12px; }
        .terms-plan { border: 1px solid var(--border); border-radius: 14px; padding: 12px; background: var(--cream-soft, #fafafa); }
        .terms-plan-name { font-size: 15px; font-weight: 900; color: var(--dark); letter-spacing: -0.2px; }
        .terms-plan-sub { font-size: 11px; color: var(--muted); font-weight: 700; }
        .terms-split { display: flex; gap: 3px; height: 8px; margin: 10px 0 8px; }
        .terms-split span { border-radius: 999px; background: var(--dark); }
        .terms-split span:nth-child(2) { background: #777; }
        .terms-split span:nth-child(3) { background: #b5b5b5; }
        .terms-plan ul { list-style: none; padding: 0 !important; font-size: 12px; }
        .terms-plan li { display: flex; justify-content: space-between; gap: 8px; margin-bottom: 3px !important; }
        .terms-plan li b { color: var(--dark); font-weight: 900; font-variant-numeric: tabular-nums; }

        .terms-callout {
            display: flex; gap: 10px; align-items: flex-start; margin-top: 10px;
            background: var(--cream); border: 1px solid var(--border); border-radius: 12px; padding: 10px 12px;
            font-size: 12.5px; color: var(--dark);
        }
        .terms-callout i { flex-shrink: 0; margin-top: 2px; }

        .terms-foot {
            display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;
            padding: 14px 24px; border-top: 1px solid var(--border); background: var(--cream); flex-shrink: 0;
        }
        .terms-foot-hint { font-size: 12px; color: var(--muted); display: flex; align-items: center; gap: 6px; }
        .terms-foot-btns { display: flex; gap: 10px; }
        .terms-btn {
            height: 42px; padding: 0 20px; border-radius: 12px; font-size: 13.5px; font-weight: 800; cursor: pointer;
            border: 1px solid var(--border); background: var(--white); color: var(--dark);
            display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: 0.18s ease;
        }
        .terms-btn:hover { border-color: var(--dark); }
        .terms-btn.primary { background: var(--dark); border-color: var(--dark); color: var(--white); }
        .terms-btn.primary:hover { background: var(--dark-deep, #1a1a1a); }
        .terms-btn:focus-visible, .terms-close:focus-visible { outline: none; box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.12); }

        @media (max-width: 560px) {
            .terms-overlay { align-items: flex-end; padding: 0; }
            .terms-modal { max-height: 92vh; border-radius: 22px 22px 0 0; }
            .terms-head { padding: 16px 18px; gap: 12px; }
            .terms-head-icon { width: 38px; height: 38px; border-radius: 12px; }
            .terms-head h2 { font-size: 16px; }
            .terms-body { padding: 4px 18px 16px; }
            .terms-sec { grid-template-columns: 1fr; }
            .terms-num { margin-bottom: 6px; }
            .terms-plans { grid-template-columns: 1fr; }
            .terms-foot { padding: 12px 18px calc(12px + env(safe-area-inset-bottom, 0px)); }
            .terms-foot-hint { display: none; }
            .terms-foot-btns { width: 100%; }
            .terms-foot .terms-btn { flex: 1; }
        }

    </style>
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
<body>
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
                <p>One portal for your steel tank projects — from your first quotation to delivery.</p>
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
                                    <li><span>Final payment, before delivery</span><b>50%</b></li>
                                </ul>
                            </div>
                            <div class="terms-plan">
                                <div class="terms-plan-name">50 / 30 / 20</div>
                                <div class="terms-plan-sub">Three payments</div>
                                <div class="terms-split"><span style="flex:50"></span><span style="flex:30"></span><span style="flex:20"></span></div>
                                <ul>
                                    <li><span>Down payment, before work starts</span><b>50%</b></li>
                                    <li><span>Progress payment, during fabrication</span><b>30%</b></li>
                                    <li><span>Final payment, before delivery</span><b>20%</b></li>
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
                        <h3>Project Schedule, Completion and Delivery</h3>
                        <ul>
                            <li>Start and end dates are planned estimates covering the project from planning to completion. They may be adjusted because of material availability, specification changes, delayed payments, weather or other events outside our control.</li>
                            <li>A project is completed once fabrication, inspection, painting and final output are done. Delivery or pick-up is arranged separately after completion and after the final payment is recorded.</li>
                            <li>You can follow your project's phases, updates, photos and delivery status through the portal.</li>
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
                        <p>By registering, you agree to receive emails and portal notifications about your registration status, account setup, quotations, project progress, delivery and payments.</p>
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
