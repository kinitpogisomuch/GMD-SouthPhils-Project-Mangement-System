<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $card['type'] }} | GMD South Phils</title>
    <meta name="description" content="{{ $card['blurb'] }}">
    <link href="{{ asset('css/landing.css') }}?v={{ @filemtime(public_path('css/landing.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/ui.css') }}?v={{ @filemtime(public_path('css/ui.css')) }}" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
</head>
<body class="pg-tanks-show">

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
