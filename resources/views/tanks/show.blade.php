<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $card['type'] }} | GMD South Phils</title>
    <meta name="description" content="{{ $card['blurb'] }}">
    <link href="{{ asset('css/landing.css') }}" rel="stylesheet">
    <link href="{{ asset('css/ui.css') }}?v={{ @filemtime(public_path('css/ui.css')) }}" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <style>
        /* ── Tank details page (td-): what a tank card opens before the client requests a quotation ── */
        .td-hero { background: linear-gradient(180deg, #333333 0%, #1a1a1a 100%); color: #fff; padding: 104px 52px 64px; }
        .td-inner { max-width: 1200px; margin: 0 auto; }
        .td-crumb { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: rgba(255,255,255,.5); margin-bottom: 28px; }
        .td-crumb a { color: rgba(255,255,255,.5); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .td-crumb a:hover { color: var(--accent); }
        .td-crumb svg, .td-crumb i { width: 14px; height: 14px; }
        .td-grid { display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr); gap: 48px; align-items: start; }

        .td-media { border-radius: 22px; overflow: hidden; border: 1px solid rgba(255,255,255,.1); position: sticky; top: 92px; }
        .td-media .portfolio-media { aspect-ratio: 4 / 3; background: #1f1f1f; }
        .td-media .portfolio-media img { position: relative; z-index: 1; object-fit: contain; }
        .td-media-fill { position: absolute; inset: -30px; background-size: cover; background-position: center;
                         filter: blur(26px) brightness(.6) saturate(1.1); transform: scale(1.1); }

        .td-title { font-size: clamp(28px, 3.6vw, 44px); font-weight: 900; letter-spacing: -1px; line-height: 1.08; margin: 0 0 14px; }
        .td-blurb { font-size: 15.5px; color: rgba(255,255,255,.6); line-height: 1.7; margin: 0 0 30px; max-width: 540px; }

        .td-label { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;
                    color: rgba(255,255,255,.55); margin: 0 0 12px; }
        .td-label svg, .td-label i { width: 15px; height: 15px; color: var(--accent); }
        .td-sizes { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 10px; margin-bottom: 14px; }
        .td-size { position: relative; display: flex; flex-direction: column; gap: 4px; text-align: left; font: inherit; cursor: pointer;
                   padding: 14px 16px; border-radius: 14px; color: #fff; background: rgba(255,255,255,.04); border: 1.5px solid rgba(255,255,255,.12);
                   transition: border-color .2s, background .2s, transform .2s; }
        .td-size:hover { border-color: rgba(255,255,255,.35); transform: translateY(-2px); }
        .td-size strong { font-size: 16px; font-weight: 900; }
        .td-size small { font-size: 12px; color: rgba(255,255,255,.5); font-weight: 600; }
        .td-size .td-tick { position: absolute; top: 12px; right: 12px; width: 20px; height: 20px; border-radius: 50%; border: 1.5px solid rgba(255,255,255,.25);
                            display: flex; align-items: center; justify-content: center; }
        .td-size .td-tick svg { width: 12px; height: 12px; opacity: 0; color: var(--dark-deep); }
        .td-size.is-on { border-color: var(--accent); background: rgba(245,180,0,.08); }
        .td-size.is-on .td-tick { background: var(--accent); border-color: var(--accent); }
        .td-size.is-on .td-tick svg { opacity: 1; }
        .td-size--custom strong { font-size: 14px; }
        .td-choices { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); margin-bottom: 26px; }
        .td-choice { gap: 6px; padding: 16px 18px; }
        .td-choice strong { font-size: 15px; padding-right: 24px; }
        .td-choice small { line-height: 1.5; }
        .td-choice-icon { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
                          background: rgba(255,255,255,.08); margin-bottom: 4px; }
        .td-choice-icon svg, .td-choice-icon i { width: 17px; height: 17px; color: var(--accent); }
        .td-choice.is-on .td-choice-icon { background: var(--accent); }
        .td-choice.is-on .td-choice-icon svg { color: var(--dark-deep); }
        .td-note { display: flex; gap: 10px; align-items: flex-start; font-size: 13.5px; line-height: 1.6; color: rgba(255,255,255,.65);
                   background: rgba(255,255,255,.04); border: 1px dashed rgba(255,255,255,.18); border-radius: 14px; padding: 14px 16px; margin-bottom: 14px; }
        .td-note svg, .td-note i { width: 18px; height: 18px; color: var(--accent); flex-shrink: 0; margin-top: 1px; }

        .td-cta { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin-top: 26px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,.08); }
        .td-cta .btn-gold { background: var(--accent); color: var(--dark-deep); }
        .td-cta .btn-gold:hover { background: #ffc61a; }
        .td-cta-note { font-size: 12.5px; color: rgba(255,255,255,.45); line-height: 1.5; }
        .td-cta-note strong { color: rgba(255,255,255,.8); }

        .td-more { padding: 64px 52px 80px; }
        .td-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
        .td-box { background: #fff; border: 1px solid var(--border); border-radius: 20px; padding: 26px 28px; }
        .td-box h2 { display: flex; align-items: center; gap: 10px; font-size: 17px; font-weight: 900; margin: 0 0 4px; color: var(--dark-deep); }
        .td-box h2 svg, .td-box h2 i { width: 18px; height: 18px; }
        .td-box > p { font-size: 13px; color: var(--muted); margin: 0 0 18px; line-height: 1.6; }
        .td-specs { list-style: none; margin: 0; padding: 0; }
        .td-specs li { display: grid; grid-template-columns: 150px 1fr; gap: 14px; padding: 12px 0; border-top: 1px solid var(--cream-deep); font-size: 13px; line-height: 1.6; }
        .td-specs li:first-child { border-top: none; padding-top: 0; }
        .td-specs b { font-weight: 800; color: var(--dark-deep); }
        .td-specs span { color: var(--muted); }
        .td-asks { display: flex; flex-wrap: wrap; gap: 8px; }
        .td-ask { display: inline-flex; align-items: center; gap: 6px; padding: 7px 12px; border-radius: 999px; font-size: 12.5px; font-weight: 700;
                  background: var(--cream); border: 1px solid var(--border); color: var(--dark); }
        .td-ask.is-req::after { content: '*'; color: #dc2626; }
        .td-steps { list-style: none; margin: 22px 0 0; padding: 18px 0 0; border-top: 1px solid var(--cream-deep); display: grid; gap: 12px; }
        .td-steps li { display: flex; gap: 12px; align-items: flex-start; font-size: 13px; color: var(--muted); line-height: 1.55; }
        .td-steps b { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 24px; height: 24px; border-radius: 50%;
                      background: var(--dark-deep); color: var(--accent); font-size: 11.5px; font-weight: 900; }
        .td-steps strong { color: var(--dark-deep); }

        .td-others { margin-top: 56px; }
        .td-others-head { display: flex; align-items: end; justify-content: space-between; gap: 12px; margin-bottom: 20px; }
        .td-others-head h2 { font-size: 22px; font-weight: 900; margin: 0; color: var(--dark-deep); }
        .td-others-head a { font-size: 13px; font-weight: 700; color: var(--dark); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .td-others-head a svg, .td-others-head a i { width: 15px; height: 15px; }
        .td-others .portfolio-grid { margin-top: 0; }
        .td-others .portfolio-card { background: #2a2a2a; text-decoration: none; color: #fff; display: block; }
        .td-others .portfolio-title { color: #fff; }

        .td-foot { background: #1a1a1a; color: rgba(255,255,255,.45); font-size: 12.5px; text-align: center; padding: 22px 16px; }

        @media (max-width: 960px) {
            .td-grid, .td-cols { grid-template-columns: 1fr; }
            .td-media { position: static; }
        }
        @media (max-width: 640px) {
            .td-hero { padding: 92px 16px 44px; }
            .td-more { padding: 40px 16px 56px; }
            .td-sizes { grid-template-columns: 1fr 1fr; }
            .td-box { padding: 22px 18px; }
            .td-specs li { grid-template-columns: 1fr; gap: 2px; }
            .td-cta .btn-gold { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

    {{-- same top bar as the landing page; its links lead back to the landing sections --}}
    <div x-data="{ menu: false }" @click.outside="menu = false" @keydown.escape.window="menu = false">
    <nav class="lp-nav scrolled">
        <a href="{{ route('home') }}" class="nav-logo">
            <div class="nav-logo-icon"><img src="{{ asset('images/gmdlogo-circle.svg') }}" alt="GMD South Phils logo"></div>
            <span class="nav-logo-text">GMD <span>South Phils</span></span>
        </a>
        <ul class="nav-links">
            <li><a href="{{ route('home') }}#about">About</a></li>
            <li><a href="{{ route('home') }}#tanks">What We Build</a></li>
            <li><a href="{{ route('home') }}#portfolio">Our Work</a></li>
            <li><a href="{{ route('home') }}#contact">Contact</a></li>
        </ul>
        <div class="nav-actions">
            <button class="nav-hamburger" aria-label="Open menu" @click="menu = !menu" :class="{ open: menu }" :aria-expanded="menu.toString()">
                <span></span><span></span><span></span>
            </button>
        </div>
    </nav>
    <div class="mobile-menu" :class="{ open: menu }">
        <a href="{{ route('home') }}#about" class="mm-link">About</a>
        <a href="{{ route('home') }}#tanks" class="mm-link">What We Build</a>
        <a href="{{ route('home') }}#portfolio" class="mm-link">Our Work</a>
        <a href="{{ route('home') }}#contact" class="mm-link">Contact</a>
        <a href="{{ route('login') }}" class="mm-login">Login to Portal</a>
    </div>
    </div>

    @php
        $sizes   = $card['sizes'];
        $isGuest = session('role') !== 'client';
        $noun    = ['unit' => 'tank', 'lot' => 'service', 'set' => 'set'][$card['unit']] ?? 'tank';
        // what the client's quotation form will ask (capacity and quantity always come first)
        $asks = array_merge(
            [['label' => $card['capacity']['label'], 'required' => true], ['label' => 'Quantity', 'required' => true]],
            // a field that only appears for one answer (e.g. Routing for a new pipeline) isn't marked required here
            array_map(fn ($f) => ['label' => $f['label'], 'required' => !empty($f['required']) && empty($f['show_if'])], $card['fields']),
            [['label' => 'Target Date', 'required' => false], ['label' => 'Design / reference files', 'required' => false]]
        );
    @endphp

    {{-- Alpine: the size the client picks rides along to the quotation form (/quote/{tank}?capacity=…);
         visitors get the same log in / sign up prompt as on the landing page --}}
    <main x-data="{
            size: '',
            choice: '',
            open: false,
            base: @js(route('quote.start', $card['slug'])),
            quoteUrl(then) {
                var q = [];
                if (this.size) q.push('capacity=' + encodeURIComponent(this.size));
                if (this.choice) q.push('choice=' + encodeURIComponent(this.choice));
                if (then) q.push('then=' + then);
                return this.base + (q.length ? '?' + q.join('&') : '');
            },
            ask() { this.open = true; document.body.style.overflow = 'hidden'; this.$nextTick(() => this.$refs.login && this.$refs.login.focus()); },
            close() { this.open = false; document.body.style.overflow = ''; }
         }" @keydown.escape.window="open && close()">

        <section class="td-hero">
            <div class="td-inner">
                <div class="td-crumb">
                    <a href="{{ route('home') }}#portfolio"><i data-lucide="arrow-left"></i> Our Work</a>
                    <i data-lucide="chevron-right"></i>
                    <span style="color:#fff;">{{ $card['type'] }}</span>
                </div>

                <div class="td-grid">
                    <div class="td-media">
                        <div class="portfolio-media" @if($card['image']) style="background-image:url('{{ $card['image'] }}')" @endif>
                            @if($card['image'])
                                {{-- the whole picture is shown (not cropped); a blurred copy fills the space around it --}}
                                <div class="td-media-fill" style="background-image:url('{{ $card['image'] }}')" aria-hidden="true"></div>
                                <img src="{{ $card['image'] }}" alt="{{ $card['type'] }}">
                            @else
                                <div class="portfolio-media-icon"><i data-lucide="{{ $card['icon'] }}"></i></div>
                            @endif
                        </div>
                    </div>

                    <div>
                        <h1 class="td-title">{{ $card['type'] }}</h1>
                        <p class="td-blurb">{{ $card['blurb'] }}</p>

                        @if($card['choice'])
                            {{-- a combined card's first question (e.g. installation or re-piping), pre-answered in the form --}}
                            <div class="td-label"><i data-lucide="list-checks"></i> Choose a {{ strtolower($card['choice']['label'] === 'Service Needed' ? 'service' : $card['choice']['label']) }}</div>
                            <div class="td-sizes td-choices" role="radiogroup" aria-label="{{ $card['choice']['label'] }}">
                                @foreach($card['choice']['options'] as $opt)
                                <button type="button" class="td-size td-choice" role="radio" :aria-checked="(choice === @js($opt['value'])).toString()"
                                        :class="{ 'is-on': choice === @js($opt['value']) }" @click="choice = @js($opt['value'])">
                                    <span class="td-tick"><i data-lucide="check"></i></span>
                                    <span class="td-choice-icon"><i data-lucide="{{ $opt['icon'] }}"></i></span>
                                    <strong>{{ $opt['value'] }}</strong>
                                    @if($opt['text'])<small>{{ $opt['text'] }}</small>@endif
                                </button>
                                @endforeach
                            </div>
                        @endif

                        @if(count($sizes))
                            <div class="td-label"><i data-lucide="ruler"></i> Choose a {{ strtolower($card['capacity']['label']) }}</div>
                            <div class="td-sizes" role="radiogroup" aria-label="{{ $card['capacity']['label'] }}">
                                @foreach($sizes as $s)
                                <button type="button" class="td-size" role="radio" :aria-checked="(size === @js($s['capacity'])).toString()"
                                        :class="{ 'is-on': size === @js($s['capacity']) }" @click="size = @js($s['capacity'])">
                                    <span class="td-tick"><i data-lucide="check"></i></span>
                                    <strong>{{ $s['capacity'] }}</strong>
                                    @if($s['dimensions'])<small>{{ $s['dimensions'] }}</small>@endif
                                </button>
                                @endforeach
                                <button type="button" class="td-size td-size--custom" role="radio" :aria-checked="(size === '').toString()"
                                        :class="{ 'is-on': size === '' }" @click="size = ''">
                                    <span class="td-tick"><i data-lucide="check"></i></span>
                                    <strong>Another size</strong>
                                    <small>Type your own in the form</small>
                                </button>
                            </div>
                        @elseif($card['project_based'])
                            <div class="td-label"><i data-lucide="ruler"></i> {{ $card['capacity']['label'] }}</div>
                            <div class="td-note"><i data-lucide="map"></i><span>{{ $card['project_based'] }} Tell us the total length and layout in the form.</span></div>
                        @else
                            <div class="td-label"><i data-lucide="ruler"></i> {{ $card['capacity']['label'] }}</div>
                            <div class="td-note"><i data-lucide="pencil-ruler"></i><span>Built to the size you need — enter your {{ strtolower($card['capacity']['label']) }} in the quotation form.</span></div>
                        @endif

                        <div class="td-cta">
                            <a :href="quoteUrl('')" href="{{ route('quote.start', $card['slug']) }}" class="btn-gold"
                               @if($isGuest) @click.prevent="ask()" @endif>
                                <i data-lucide="file-text"></i> Request a Quotation
                            </a>
                            <span class="td-cta-note" x-show="size || choice" x-cloak>Selected: <strong x-text="[choice, size].filter(Boolean).join(' · ')"></strong></span>
                            <span class="td-cta-note" x-show="!size && !choice">Your quotation is sent to your client portal.</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="td-more">
            <div class="td-inner">
                <div class="td-cols">
                    <div class="td-box">
                        <h2><i data-lucide="badge-check"></i> What's included</h2>
                        <p>GMD's standard build for this {{ $noun }} — your quotation can adjust any of it.</p>
                        @if(count($card['specs']))
                        <ul class="td-specs">
                            @foreach($card['specs'] as $spec)
                            <li><b>{{ $spec['label'] }}</b><span>{{ $spec['value'] }}</span></li>
                            @endforeach
                        </ul>
                        @else
                        <div class="td-note" style="color:var(--muted);background:var(--cream);border-color:var(--border);margin:0;">
                            <i data-lucide="message-square-text"></i><span>Every job is different — describe what you need and the quotation will list exactly what is included.</span>
                        </div>
                        @endif
                    </div>

                    <div class="td-box">
                        <h2><i data-lucide="clipboard-list"></i> What we'll ask you</h2>
                        <p>The quotation form only asks what applies to this {{ $noun }}. <span style="color:#dc2626;">*</span> required.</p>
                        <div class="td-asks">
                            @foreach($asks as $a)
                            <span class="td-ask {{ $a['required'] ? 'is-req' : '' }}">{{ $a['label'] }}</span>
                            @endforeach
                        </div>
                        <ol class="td-steps">
                            <li><b>1</b><span><strong>Send your request</strong> from the client portal — it takes a few minutes.</span></li>
                            <li><b>2</b><span><strong>GMD prepares your quotation</strong> and sends it to the portal.</span></li>
                            <li><b>3</b><span><strong>Approve it online</strong> and your project starts.</span></li>
                        </ol>
                    </div>
                </div>

                @if(count($others))
                <div class="td-others">
                    <div class="td-others-head">
                        <h2>Other tanks we build</h2>
                        <a href="{{ route('home') }}#portfolio">See all <i data-lucide="arrow-right"></i></a>
                    </div>
                    <div class="portfolio-grid">
                        @foreach($others as $o)
                        <a href="{{ route('tanks.show', $o['slug']) }}" class="portfolio-card qt-card">
                            <div class="portfolio-media" @if($o['image']) style="background-image:url('{{ $o['image'] }}')" @endif>
                                @if($o['image'])
                                    <img src="{{ $o['image'] }}" alt="{{ $o['type'] }}" loading="lazy">
                                @else
                                    <div class="portfolio-media-icon"><i data-lucide="{{ $o['icon'] }}"></i></div>
                                @endif
                            </div>
                            <div class="portfolio-body">
                                <div class="portfolio-title">{{ $o['type'] }}</div>
                                <p class="portfolio-desc">{{ $o['blurb'] }}</p>
                                <span class="qt-cta">View Details <i data-lucide="arrow-right"></i></span>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </section>

        @if($isGuest)
        {{-- Log in / sign up prompt (same as the landing page's) — the tank and size are remembered --}}
        <div class="qt-modal" role="dialog" aria-modal="true" aria-labelledby="quoteAuthTitle"
             x-cloak x-show="open" :class="{ show: open }" x-transition.opacity.duration.200ms @click.self="close()">
            <div class="qt-modal-card" x-show="open" x-transition.scale.92.origin.bottom.duration.260ms>
                <button type="button" class="qt-modal-close" aria-label="Close" @click="close()"><i data-lucide="x"></i></button>
                <div class="qt-modal-icon"><i data-lucide="file-text"></i></div>
                <h3 id="quoteAuthTitle">Request a quotation for {{ $card['type'] }}<span x-show="size || choice" x-text="' — ' + [choice, size].filter(Boolean).join(', ')"></span></h3>
                <p>Log in to your GMD client portal to describe what you need — we'll keep your tank{{ count($sizes) ? ' and size' : '' }} selected. New here? Sign up; once your account is approved, your first login takes you straight to this tank's form.</p>
                <div class="qt-modal-actions">
                    <a :href="quoteUrl('login')" class="btn-gold" x-ref="login"><i data-lucide="log-in"></i> Log In</a>
                    <a :href="quoteUrl('signup')" class="btn-ghost qt-signup">Sign Up</a>
                </div>
            </div>
        </div>
        @endif
    </main>

    <footer class="td-foot">&copy; {{ date('Y') }} GMD South Phils. All rights reserved.</footer>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
