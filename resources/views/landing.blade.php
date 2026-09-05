<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GMD South Phils</title>
    <meta name="description" content="GMD South Phils specializes in custom steel storage tank fabrication — fuel, water, chemical, and oil tanks — delivered with precision across South Philippines.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ asset('css/landing.css') }}" rel="stylesheet">
</head>
<body>

    <!-- ============================================================
         NAVIGATION
    ============================================================ -->
    <nav class="lp-nav" id="lpNav">
        <a href="#" class="nav-logo">
            <div class="nav-logo-icon">
                <img src="{{ asset('images/gmdlogo-circle.svg') }}" alt="GMD South Phils logo">
            </div>
            <span class="nav-logo-text">GMD <span>South Phils</span></span>
        </a>

        <ul class="nav-links">
            <li class="nav-pill" id="navPill" aria-hidden="true"></li>
            <li><a href="#about">About</a></li>
            <li><a href="#tanks">What We Build</a></li>
            <li><a href="#portfolio">Our Work</a></li>
            @if($reviews->isNotEmpty())
            <li><a href="#reviews">Client Feedback</a></li>
            @endif
            <li><a href="#contact">Contact</a></li>
        </ul>

        <div class="nav-actions">
            <a href="{{ route('login') }}" class="nav-login">Login</a>
            <button class="nav-hamburger" id="navHamburger" aria-label="Open menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </nav>

    <!-- Mobile menu drawer -->
    <div class="mobile-menu" id="mobileMenu">
        <a href="#about"     class="mm-link">About</a>
        <a href="#tanks"     class="mm-link">What We Build</a>
        <a href="#portfolio" class="mm-link">Our Work</a>
        @if($reviews->isNotEmpty())
        <a href="#reviews"   class="mm-link">Client Feedback</a>
        @endif
        <a href="#contact"   class="mm-link">Contact</a>
        <a href="{{ route('login') }}" class="mm-login">Login to Portal</a>
    </div>


    <!-- ============================================================
         HERO
    ============================================================ -->
    @php
    $hasHeroBg = file_exists(public_path('images/login.jpg'));
    @endphp
    <section class="lp-hero @if($hasHeroBg) has-bg-photo @endif" id="home"
             @if($hasHeroBg) style="--hero-bg-photo: url('{{ asset('images/login.jpg') }}')" @endif>
        <div class="hero-glow"></div>

        <div class="hero-content">
            <div class="hero-badge">
                <i data-lucide="shield-check"></i>
                Trusted Tank Fabrication — South Philippines
            </div>

            <h1>
                Built Strong.<br>
                Delivered with <span class="gold">Precision.</span>
            </h1>

            <p class="hero-sub">
                GMD South Phils designs and fabricates custom steel storage tanks for industrial, commercial, and agricultural clients — from fuel and chemical tanks to water and oil storage systems.
            </p>

            <div class="hero-btns">
                <a href="#tanks" class="btn-gold">
                    <i data-lucide="layers"></i>
                    View Our Work
                </a>
                <a href="{{ route('login') }}" class="btn-ghost">Login</a>
            </div>
        </div>

        <div class="hero-scroll-hint">
            <span>Scroll to explore</span>
            <div class="scroll-pip"></div>
        </div>
    </section>

    <div class="hazard-strip"></div>

    <!-- ============================================================
         STATS BAR
    ============================================================ -->
    <div class="stats-bar">
        <div class="stat-box reveal">
            <div class="stat-num">7<sup>+</sup></div>
            <div class="stat-label">Years of Experience</div>
        </div>
        <div class="stat-box reveal">
            <div class="stat-num">10<sup>+</sup></div>
            <div class="stat-label">Product Types</div>
        </div>
        <div class="stat-box reveal">
            <div class="stat-num">100%</div>
            <div class="stat-label">Quality Inspected</div>
        </div>
        <div class="stat-box reveal">
            <div class="stat-num">On<span class="stat-slash">-</span>Time</div>
            <div class="stat-label">Delivery Commitment</div>
        </div>
    </div>


    <!-- ============================================================
         ABOUT
    ============================================================ -->
    <section class="about-wrap" id="about">
        <div class="about-inner">

            <div class="about-body reveal-left">
                <div>
                    <div class="section-tag">Who We Are</div>
                    <h2 class="section-title">Your Partner in Industrial Tank Fabrication</h2>
                </div>
                <p class="about-text">
                    GMD South Phils is a fabrication company based in the Southern Philippines, specializing in the design, construction, and delivery of custom steel storage tanks. We serve clients across industries — from fuel distribution and chemical processing to food manufacturing and water infrastructure.
                </p>
                <p class="about-text">
                    With a skilled team of welders, fabricators, and project managers, we handle every stage of the tank lifecycle — from initial engineering consultation to final inspection, painting, and on-site delivery.
                </p>

                <div class="about-highlights">
                    <div class="about-hl reveal">
                        <i data-lucide="hammer"></i>
                        <span>Skilled Fabricators</span>
                    </div>
                    <div class="about-hl reveal">
                        <i data-lucide="badge-check"></i>
                        <span>Quality Assured</span>
                    </div>
                    <div class="about-hl reveal">
                        <i data-lucide="clock"></i>
                        <span>On-Time Delivery</span>
                    </div>
                    <div class="about-hl reveal">
                        <i data-lucide="settings"></i>
                        <span>Custom Specifications</span>
                    </div>
                </div>
            </div>

            <div class="about-visual reveal-right">
                <div class="about-visual-title">Our Commitment to Quality</div>
                <div class="about-phase-list">
                    @php
                    $strengths = [
                        ['Precision Engineering',   'Every tank is designed to exact client specifications.'],
                        ['Certified Welding',       'Skilled welders trained in structural steel fabrication.'],
                        ['Pressure Tested',         'All tanks undergo rigorous pressure and leak testing.'],
                        ['Anti-Corrosion Coating',  'Industrial-grade paint systems for lasting protection.'],
                        ['On-Site Delivery',        'Safe transport and installation support at your location.'],
                        ['Post-Delivery Support',   'Our team remains available after handover.'],
                    ];
                    @endphp
                    @foreach($strengths as $strength)
                    <div class="about-phase-item">
                        <span class="about-phase-dot"></span>
                        <div>
                            <span style="font-weight:800;color:var(--white);">{{ $strength[0] }}</span>
                            <span style="color:rgba(255,255,255,0.5);font-size:12px;display:block;margin-top:1px;">{{ $strength[1] }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </section>






    <!-- ============================================================
         TANK TYPES
    ============================================================ -->
    <section class="tanks-wrap" id="tanks">
        <div class="tanks-inner">
            <div class="section-tag reveal">Our Specialties</div>
            <h2 class="section-title reveal">What We Build</h2>
            <p class="section-sub reveal">We build a wide range of industrial storage tanks engineered for different applications and industries.</p>
        </div>

        {{-- Infinite scroll marquee --}}
        <div class="tanks-marquee-wrap">
            <div class="tanks-marquee-track">
                @php
                $tankItems = [
                    ['icon'=>'droplets',        'name'=>'Water Storage',     'desc'=>'Industrial and commercial water tanks for construction, agriculture, and municipal supply.'],
                    ['icon'=>'fuel',            'name'=>'Oil Storage',        'desc'=>'Heavy-duty tanks for crude oil, lubricants, and petroleum derivatives.'],
                    ['icon'=>'pipe',            'name'=>'Pipe Line',          'desc'=>'Fabricated steel pipe systems designed for fluid transfer across industrial sites.'],
                    ['icon'=>'triangle',        'name'=>'Tetrapod',           'desc'=>'Concrete and steel tetrapod structures for coastal protection and erosion control.'],
                    ['icon'=>'flame',           'name'=>'Fuel Storage Tank',  'desc'=>'Safe and durable tanks for diesel, gasoline, and other flammable fuel storage.'],
                    ['icon'=>'container',       'name'=>'Cistern Tank',       'desc'=>'Underground and above-ground cisterns for water collection and holding applications.'],
                ];
                @endphp
                @foreach(array_merge($tankItems, $tankItems) as $t)
                <div class="tanks-marquee-card">
                    <div class="tmc-icon">
                        @if($t['name'] === 'Pipe Line')
                            <img src="https://cdn-icons-png.flaticon.com/512/3769/3769198.png"
                                 alt="Pipeline" style="width:32px;height:32px;object-fit:contain;filter:opacity(.75);">
                        @else
                            <i data-lucide="{{ $t['icon'] }}"></i>
                        @endif
                    </div>
                    <div class="tmc-name">{{ $t['name'] }}</div>
                    <p class="tmc-desc">{{ $t['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>


    <!-- ============================================================
         PORTFOLIO / OUR WORK
    ============================================================ -->
    <section class="portfolio-wrap" id="portfolio">
        <div class="portfolio-inner">
            <div class="section-tag reveal">Our Work</div>
            <h2 class="section-title reveal">Projects We've Built</h2>
            <p class="section-sub reveal">A showcase of our completed storage tank projects — each fabricated to client specifications, pressure-tested, and delivered on schedule.</p>

            <div class="portfolio-grid" id="portfolioGrid">
                @foreach($portfolioItems as $i => $item)
                @php
                    $src = $item->image_url && !str_starts_with($item->image_url, 'http')
                        ? asset($item->image_url)
                        : $item->image_url;
                @endphp
                <div class="portfolio-card reveal" data-portfolio-index="{{ $i }}">
                    <div class="portfolio-media"
                         @if($src) style="background-image:url('{{ $src }}')" @endif>
                        <span class="portfolio-spec">{{ $item->spec }}</span>
                        @if($src)
                            <img src="{{ $src }}" alt="{{ $item->title }}">
                        @else
                            <div class="portfolio-media-icon"><i data-lucide="{{ $item->icon }}"></i></div>
                        @endif
                    </div>
                    <div class="portfolio-body">
                        <div class="portfolio-tag">
                            <i data-lucide="tag" style="width:11px;height:11px;"></i>
                            {{ $item->tag }}
                        </div>
                        <div class="portfolio-title">{{ $item->title }}</div>
                        <p class="portfolio-desc">{{ $item->description }}</p>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Pagination controls --}}
            <div id="portfolioPagination" style="display:flex;align-items:center;justify-content:center;gap:12px;margin-top:36px;">
                <button id="portPrev" onclick="portfolioPage(-1)"
                    style="width:40px;height:40px;border-radius:50%;border:2px solid #fff;background:transparent;color:#fff;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s;"
                    onmouseover="this.style.background='rgba(255,255,255,.15)'" onmouseout="this.style.background='transparent'">
                    &#8592;
                </button>
                <span id="portPageInfo" style="font-size:13px;font-weight:700;color:rgba(255,255,255,.7);min-width:80px;text-align:center;"></span>
                <button id="portNext" onclick="portfolioPage(1)"
                    style="width:40px;height:40px;border-radius:50%;border:2px solid #fff;background:transparent;color:#fff;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s;"
                    onmouseover="this.style.background='rgba(255,255,255,.15)'" onmouseout="this.style.background='transparent'">
                    &#8594;
                </button>
            </div>
            <script>
            (function(){
                var PER_PAGE = 6;
                var currentPage = 0;
                var cards = document.querySelectorAll('#portfolioGrid .portfolio-card');
                var total = cards.length;

                function render() {
                    var totalPages = Math.ceil(total / PER_PAGE);
                    var start = currentPage * PER_PAGE;
                    cards.forEach(function(c, i) {
                        c.style.display = (i >= start && i < start + PER_PAGE) ? '' : 'none';
                    });
                    document.getElementById('portPageInfo').textContent = totalPages > 1 ? 'Page ' + (currentPage+1) + ' of ' + totalPages : '';
                    document.getElementById('portPrev').style.opacity = currentPage === 0 ? '0.3' : '1';
                    document.getElementById('portNext').style.opacity = currentPage >= totalPages-1 ? '0.3' : '1';
                    document.getElementById('portfolioPagination').style.display = totalPages > 1 ? 'flex' : 'none';
                }

                window.portfolioPage = function(dir) {
                    var totalPages = Math.ceil(total / PER_PAGE);
                    currentPage = Math.max(0, Math.min(totalPages-1, currentPage + dir));
                    render();
                    document.getElementById('portfolio').scrollIntoView({behavior:'smooth', block:'start'});
                };

                render();
            })();
            </script>

            <div class="portfolio-foot">
                <p>Each project is engineered to the client's exact specifications — pressure tested, quality inspected, and delivered ready for operation.</p>
            </div>
        </div>
    </section>


    <!-- ============================================================
         WHY GMD
    ============================================================ -->
    <section class="why-wrap">
        <div class="why-inner">
            <div class="section-tag reveal">Why Choose Us</div>
            <h2 class="section-title reveal">What Sets GMD Apart</h2>
            <p class="section-sub reveal">We combine skilled craftsmanship, structured workflows, and transparent communication to deliver tanks that last.</p>

            <div class="why-grid">

                <div class="why-card reveal">
                    <div class="wc-icon"><i data-lucide="users"></i></div>
                    <div class="wc-title">Experienced Team</div>
                    <p class="wc-desc">Our welders, fabricators, and project managers bring deep hands-on experience in industrial tank construction.</p>
                </div>

                <div class="why-card reveal">
                    <div class="wc-icon"><i data-lucide="bar-chart-3"></i></div>
                    <div class="wc-title">Transparent Progress</div>
                    <p class="wc-desc">Clients receive live project updates through our portal — always knowing exactly where their tank is in the process.</p>
                </div>

                <div class="why-card reveal">
                    <div class="wc-icon"><i data-lucide="badge-check"></i></div>
                    <div class="wc-title">Uncompromising Quality</div>
                    <p class="wc-desc">Multiple inspection checkpoints throughout fabrication ensure structural integrity, weld quality, and finish standards are fully met.</p>
                </div>

                <div class="why-card reveal">
                    <div class="wc-icon"><i data-lucide="ruler"></i></div>
                    <div class="wc-title">Custom Specifications</div>
                    <p class="wc-desc">No two projects are the same. We engineer each tank to match your exact capacity, dimension, and application requirements.</p>
                </div>

                <div class="why-card reveal">
                    <div class="wc-icon"><i data-lucide="clock-3"></i></div>
                    <div class="wc-title">On-Time Delivery</div>
                    <p class="wc-desc">We commit to agreed timelines and manage every project milestone to ensure your tank arrives when you need it.</p>
                </div>

                <div class="why-card reveal">
                    <div class="wc-icon"><i data-lucide="handshake"></i></div>
                    <div class="wc-title">Post-Delivery Support</div>
                    <p class="wc-desc">We don't just deliver and leave. Our team supports on-site installation and is available for after-delivery concerns.</p>
                </div>

            </div>
        </div>
    </section>


    <!-- ============================================================
         CLIENT REVIEWS
    ============================================================ -->
    <section class="reviews-wrap" id="reviews">
        <div class="reviews-inner">
            <div class="section-tag reveal">Client Feedback</div>
            <h2 class="section-title reveal">What Our Clients Say</h2>
            <p class="section-sub reveal">Real feedback from clients after their completed tank fabrication projects.</p>

            @if($reviews->isNotEmpty())
            <div class="reviews-marquee-wrap reveal">
                <div class="reviews-marquee-track">
                    @for($pass = 0; $pass < 2; $pass++)
                        @foreach($reviews as $review)
                        @php
                            $reviewProject = $review->project->tank_type ?? $review->project->name;
                            $reviewDate = $review->project && $review->project->end_date
                                ? \Carbon\Carbon::parse($review->project->end_date)->format('M d, Y')
                                : $review->created_at->format('M d, Y');
                        @endphp
                        <div class="review-card"
                             data-rating="{{ $review->rating }}"
                             data-name="{{ $review->masked_client_name }}"
                             data-project="{{ $reviewProject }}"
                             data-date="{{ $reviewDate }}"
                             data-comment="{{ $review->comment }}">
                            <div class="review-stars">
                                @for($i=1;$i<=5;$i++)
                                    <i data-lucide="star" style="width:16px;height:16px;color:{{ $i <= $review->rating ? 'var(--accent)' : 'var(--border)' }};{{ $i <= $review->rating ? 'fill:var(--accent);' : '' }}"></i>
                                @endfor
                            </div>
                            <p class="review-text">"{{ $review->comment }}"</p>
                            <div class="review-author">
                                <div class="review-avatar">{{ strtoupper(substr($review->client_name, 0, 1)) }}</div>
                                <div>
                                    <div class="review-name">{{ $review->masked_client_name }}</div>
                                    <div class="review-project">{{ $reviewProject }}</div>
                                </div>
                                <div style="margin-left:auto;font-size:11px;color:var(--muted);white-space:nowrap;">
                                    {{ $reviewDate }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @endfor
                </div>
            </div>

            <div class="reviews-see-all-wrap reveal">
                <button type="button" class="reviews-see-all" id="seeAllReviewsBtn">
                    See all reviews ({{ $allReviews->count() }})
                    <i data-lucide="arrow-right"></i>
                </button>
            </div>

            <div class="review-modal-overlay" id="reviewModalOverlay">
                <div class="review-modal-card" role="dialog" aria-modal="true" aria-labelledby="reviewModalName">
                    <button type="button" class="review-modal-close" id="reviewModalClose" aria-label="Close">
                        <i data-lucide="x"></i>
                    </button>
                    <div class="review-stars" id="reviewModalStars"></div>
                    <p class="review-modal-text" id="reviewModalText"></p>
                    <div class="review-author">
                        <div class="review-avatar" id="reviewModalAvatar"></div>
                        <div>
                            <div class="review-name" id="reviewModalName"></div>
                            <div class="review-project" id="reviewModalProject"></div>
                        </div>
                        <div style="margin-left:auto;font-size:12px;color:var(--muted);white-space:nowrap;" id="reviewModalDate"></div>
                    </div>
                </div>
            </div>

            <div class="review-modal-overlay" id="allReviewsModalOverlay">
                <div class="review-modal-card all-reviews-card" role="dialog" aria-modal="true">
                    <button type="button" class="review-modal-close" id="allReviewsModalClose" aria-label="Close">
                        <i data-lucide="x"></i>
                    </button>
                    <div class="all-reviews-header">
                        <h3>All Reviews</h3>
                        <span>{{ $allReviews->count() }} {{ $allReviews->count() === 1 ? 'review' : 'reviews' }}</span>
                    </div>

                    @php $arRatingCounts = $allReviews->countBy('rating'); @endphp
                    <div class="all-reviews-filters">
                        <button type="button" class="all-reviews-filter active" data-filter-rating="all">
                            All <span>({{ $allReviews->count() }})</span>
                        </button>
                        @for($r = 5; $r >= 1; $r--)
                            @if($arRatingCounts->get($r, 0) > 0)
                            <button type="button" class="all-reviews-filter" data-filter-rating="{{ $r }}">
                                <i data-lucide="star" style="width:12px;height:12px;color:var(--accent);fill:var(--accent);"></i>
                                {{ $r }} <span>({{ $arRatingCounts->get($r) }})</span>
                            </button>
                            @endif
                        @endfor
                    </div>

                    <div class="all-reviews-grid" id="allReviewsGrid">
                        @foreach($allReviews as $review)
                        @php
                            $arProject = $review->project->tank_type ?? $review->project->name;
                            $arDate = $review->project && $review->project->end_date
                                ? \Carbon\Carbon::parse($review->project->end_date)->format('M d, Y')
                                : $review->created_at->format('M d, Y');
                        @endphp
                        <div class="review-card" data-rating="{{ $review->rating }}">
                            <div class="review-stars">
                                @for($i=1;$i<=5;$i++)
                                    <i data-lucide="star" style="width:16px;height:16px;color:{{ $i <= $review->rating ? 'var(--accent)' : 'var(--border)' }};{{ $i <= $review->rating ? 'fill:var(--accent);' : '' }}"></i>
                                @endfor
                            </div>
                            <p class="review-text">"{{ $review->comment }}"</p>
                            <div class="review-author">
                                <div class="review-avatar">{{ strtoupper(substr($review->client_name, 0, 1)) }}</div>
                                <div>
                                    <div class="review-name">{{ $review->masked_client_name }}</div>
                                    <div class="review-project">{{ $arProject }}</div>
                                </div>
                                <div style="margin-left:auto;font-size:11px;color:var(--muted);white-space:nowrap;">
                                    {{ $arDate }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="all-reviews-empty" id="allReviewsEmpty">
                        <i data-lucide="star-off"></i>
                        <p>No reviews with this rating yet.</p>
                    </div>
                </div>
            </div>
            @else
            <p class="section-sub reveal" style="margin-top:40px;">No reviews yet — check back soon!</p>
            @endif
        </div>
    </section>
    <script>
    (function() {
        var wrap = document.querySelector('.reviews-marquee-wrap');
        if (!wrap) return;

        // Locking scroll via overflow:hidden removes the scrollbar, which widens
        // the viewport and shifts the whole page sideways. Pad the body by the
        // scrollbar's own width to hold the layout still while it's locked.
        function lockScroll() {
            var scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
            document.body.style.overflow = 'hidden';
            if (scrollbarWidth > 0) document.body.style.paddingRight = scrollbarWidth + 'px';
        }
        function unlockScroll() {
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }

        var overlay      = document.getElementById('reviewModalOverlay');
        var modalStars   = document.getElementById('reviewModalStars');
        var modalText    = document.getElementById('reviewModalText');
        var modalAvatar  = document.getElementById('reviewModalAvatar');
        var modalName    = document.getElementById('reviewModalName');
        var modalProject = document.getElementById('reviewModalProject');
        var modalDate    = document.getElementById('reviewModalDate');
        var closeBtn     = document.getElementById('reviewModalClose');
        if (!overlay) return;

        function openReviewModal(card) {
            var rating = parseInt(card.dataset.rating, 10) || 0;
            modalStars.innerHTML = '';
            for (var i = 1; i <= 5; i++) {
                var star = document.createElement('i');
                star.setAttribute('data-lucide', 'star');
                star.style.width = '18px';
                star.style.height = '18px';
                star.style.color = i <= rating ? 'var(--accent)' : 'var(--border)';
                if (i <= rating) star.style.fill = 'var(--accent)';
                modalStars.appendChild(star);
            }

            modalText.textContent    = '"' + card.dataset.comment + '"';
            modalAvatar.textContent  = card.dataset.name.charAt(0).toUpperCase();
            modalName.textContent    = card.dataset.name;
            modalProject.textContent = card.dataset.project;
            modalDate.textContent    = card.dataset.date;

            if (window.lucide) lucide.createIcons();

            overlay.classList.add('show');
            wrap.classList.add('is-paused');
            lockScroll();
        }

        function closeReviewModal() {
            overlay.classList.remove('show');
            wrap.classList.remove('is-paused');
            unlockScroll();
        }

        wrap.addEventListener('click', function(e) {
            var card = e.target.closest('.review-card');
            if (!card) return;
            openReviewModal(card);
        });

        closeBtn.addEventListener('click', closeReviewModal);

        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) closeReviewModal();
        });

        // ── "See all reviews" grid modal ──────────────────────────────
        var seeAllBtn      = document.getElementById('seeAllReviewsBtn');
        var allOverlay     = document.getElementById('allReviewsModalOverlay');
        var allCloseBtn    = document.getElementById('allReviewsModalClose');

        function openAllReviewsModal() {
            allOverlay.classList.add('show');
            wrap.classList.add('is-paused');
            lockScroll();
        }

        function closeAllReviewsModal() {
            allOverlay.classList.remove('show');
            wrap.classList.remove('is-paused');
            unlockScroll();
        }

        if (seeAllBtn && allOverlay) {
            seeAllBtn.addEventListener('click', openAllReviewsModal);
            allCloseBtn.addEventListener('click', closeAllReviewsModal);
            allOverlay.addEventListener('click', function(e) {
                if (e.target === allOverlay) closeAllReviewsModal();
            });
        }

        // ── All-reviews rating filter ──────────────────────────────────
        var filterBtns  = document.querySelectorAll('.all-reviews-filter');
        var filterCards = document.querySelectorAll('#allReviewsGrid .review-card');
        var filterEmpty = document.getElementById('allReviewsEmpty');

        filterBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                filterBtns.forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');

                var rating = btn.dataset.filterRating;
                var visible = 0;
                filterCards.forEach(function(card) {
                    var match = rating === 'all' || card.dataset.rating === rating;
                    card.style.display = match ? '' : 'none';
                    if (match) visible++;
                });

                if (filterEmpty) filterEmpty.classList.toggle('show', visible === 0);
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Escape') return;
            if (overlay.classList.contains('show')) closeReviewModal();
            if (allOverlay && allOverlay.classList.contains('show')) closeAllReviewsModal();
        });
    })();
    </script>




    <!-- ============================================================
         CONTACT
    ============================================================ -->
    <section class="contact-wrap" id="contact">
        <div class="contact-inner">

            <div class="reveal-left">
                <div class="section-tag">Get in Touch</div>
                <h2 class="section-title">Start Your Project Today</h2>
                <p class="section-sub">Have a project in mind? Reach out and our team will get back to you with a consultation and timeline estimate.</p>

                @php
                    $ciAddress = $contactInfo->address ?: 'Brgy. Masiit, Calauan, Philippines, 4012';
                    $ciPhone   = $contactInfo->phone ?: '0917 652 5201';
                    $ciPhoneDigits = ltrim(preg_replace('/\D/', '', $ciPhone), '0');
                    $ciEmail   = $contactInfo->email ?: 'gmdsouthphils@gmail.com';
                    $ciHours   = $contactInfo->business_hours ?: 'Monday – Saturday, 8:00 AM – 5:00 PM';
                    $ciFbUrl   = $contactInfo->facebook ?: 'https://www.facebook.com/gmdsouthphils';
                    $ciFbLabel = preg_replace('#^https?://(www\.)?#', '', rtrim($ciFbUrl, '/'));
                @endphp
                <div class="contact-items">
                    <div class="ci-row">
                        <div class="ci-icon"><i data-lucide="map-pin"></i></div>
                        <div>
                            <div class="ci-label">Location</div>
                            <div class="ci-value">{{ $ciAddress }}</div>
                        </div>
                    </div>
                    <div class="ci-row">
                        <div class="ci-icon"><i data-lucide="phone"></i></div>
                        <div>
                            <div class="ci-label">Phone</div>
                            <div class="ci-value">
                                <a href="tel:+63{{ $ciPhoneDigits }}">{{ $ciPhone }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="ci-row">
                        <div class="ci-icon"><i data-lucide="mail"></i></div>
                        <div>
                            <div class="ci-label">Email</div>
                            <div class="ci-value">
                                <a href="mailto:{{ $ciEmail }}">{{ $ciEmail }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="ci-row">
                        <div class="ci-icon">
                            <img src="{{ asset('images/facebook.png') }}" alt="Facebook" style="width:28px;height:28px;object-fit:contain;">
                        </div>
                        <div>
                            <div class="ci-label">Facebook</div>
                            <div class="ci-value">
                                <a href="{{ $ciFbUrl }}" target="_blank" rel="noopener">{{ $ciFbLabel }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="ci-row">
                        <div class="ci-icon"><i data-lucide="clock"></i></div>
                        <div>
                            <div class="ci-label">Business Hours</div>
                            <div class="ci-value">{{ $ciHours }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contact-right reveal-right">
                <div class="contact-card">
                    <div class="cc-icon"><i data-lucide="message-circle"></i></div>
                    <div>
                        <div class="cc-title">Request a Quotation</div>
                        <p class="cc-body">Tell us your tank type, capacity, and intended use — we'll prepare a shop drawing for your approval, then send a formal quotation.</p>
                    </div>
                </div>
                <div class="contact-card">
                    <div class="cc-icon"><i data-lucide="file-text"></i></div>
                    <div>
                        <div class="cc-title">Technical Consultation</div>
                        <p class="cc-body">Not sure what tank you need? Our engineers will assess your application and recommend the right tank type, capacity, and material specification.</p>
                    </div>
                </div>
                <div class="contact-card">
                    <div class="cc-icon"><i data-lucide="monitor-check"></i></div>
                    <div>
                        <div class="cc-title">Existing Clients</div>
                        <p class="cc-body">Already working with us? Log into the Client Portal to monitor your project, view photo updates, and track progress.</p>
                    </div>
                </div>
            </div>

        </div>
    </section>


    <!-- ============================================================
         FOOTER
    ============================================================ -->
    <footer class="lp-footer">

        <div class="footer-top">
            <div class="footer-brand-col">
                <div class="footer-brand">
                    <div class="footer-brand-logo"><i data-lucide="container"></i></div>
                    <span class="footer-brand-name">GMD <span>South Phils</span></span>
                </div>
                <p class="footer-tagline">
                    Precision steel storage tank fabrication for industrial, commercial, and agricultural clients — fuel, water, chemical, and oil tanks delivered across South Philippines.
                </p>
            </div>

            <div class="footer-col">
                <div class="footer-col-title">Quick Links</div>
                <ul class="footer-links">
                    <li><a href="#about">About</a></li>
                            <li><a href="#tanks">What We Build</a></li>
                    <li><a href="#portfolio">Our Work</a></li>
                    @if($reviews->isNotEmpty())
                    <li><a href="#reviews">Client Feedback</a></li>
                    @endif
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <div class="footer-col-title">Client Access</div>
                <p class="footer-col-desc">
                    Already working with us? Log in to the Client Portal to track your project progress, view photo updates, and manage documents.
                </p>
                <a href="{{ route('login') }}" class="footer-login">
                    <i data-lucide="log-in"></i>
                    Login to Portal
                </a>
            </div>
        </div>

        <div class="footer-bottom">
            <span class="footer-copy">&copy; {{ date('Y') }} GMD South Phils. All rights reserved.</span>
            <div class="footer-bottom-links">
                <a href="#home">
                    <i data-lucide="arrow-up"></i>
                    Back to top
                </a>
            </div>
        </div>
    </footer>


    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        if (window.lucide) lucide.createIcons();

        // Mobile hamburger menu
        (function() {
            var btn  = document.getElementById('navHamburger');
            var menu = document.getElementById('mobileMenu');
            if (!btn || !menu) return;
            btn.addEventListener('click', function() {
                var open = menu.classList.toggle('open');
                btn.classList.toggle('open', open);
            });
            menu.querySelectorAll('.mm-link').forEach(function(a) {
                a.addEventListener('click', function() {
                    menu.classList.remove('open');
                    btn.classList.remove('open');
                });
            });
            // Close on outside click
            document.addEventListener('click', function(e) {
                if (!menu.contains(e.target) && !btn.contains(e.target)) {
                    menu.classList.remove('open');
                    btn.classList.remove('open');
                }
            });
        })();

        // Sticky nav shadow on scroll
        window.addEventListener('scroll', function () {
            var nav = document.getElementById('lpNav');
            if (window.scrollY > 20) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });

        // Highlight the nav link for the section currently in view, with a sliding pill
        (function () {
            var navList = document.querySelector('.nav-links');
            var navLinks = document.querySelectorAll('.nav-links a');
            var navPill = document.getElementById('navPill');
            var sections = document.querySelectorAll('section[id]');

            if (!('IntersectionObserver' in window) || !sections.length) return;

            function movePill(link) {
                var listRect = navList.getBoundingClientRect();
                var linkRect = link.getBoundingClientRect();
                navPill.style.left = (linkRect.left - listRect.left) + 'px';
                navPill.style.width = linkRect.width + 'px';
                navPill.classList.add('visible');
            }

            var spyObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    var id = entry.target.getAttribute('id');
                    var activeLink = null;
                    navLinks.forEach(function (link) {
                        var isActive = link.getAttribute('href') === '#' + id;
                        link.classList.toggle('active', isActive);
                        if (isActive) activeLink = link;
                    });
                    if (activeLink) {
                        movePill(activeLink);
                    } else {
                        navPill.classList.remove('visible');
                    }
                });
            }, { rootMargin: '-40% 0px -55% 0px' });

            sections.forEach(function (section) { spyObserver.observe(section); });

            window.addEventListener('resize', function () {
                var current = document.querySelector('.nav-links a.active');
                if (current) movePill(current);
            });
        })();

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
            anchor.addEventListener('click', function (e) {
                var target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Scroll reveal animations
        (function () {
            var revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');
            var staggerGroups = ['process-grid', 'portfolio-grid', 'why-grid', 'reviews-grid', 'stats-bar', 'about-highlights'];

            revealEls.forEach(function (el) {
                var parent = el.parentElement;
                if (parent && staggerGroups.some(function (cls) { return parent.classList.contains(cls); })) {
                    var index = Array.prototype.indexOf.call(parent.children, el);
                    el.style.transitionDelay = (index * 0.08) + 's';
                }
            });

            if (!('IntersectionObserver' in window)) {
                revealEls.forEach(function (el) { el.classList.add('is-visible'); });
                return;
            }

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    entry.target.classList.toggle('is-visible', entry.isIntersecting);
                });
            }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });

            revealEls.forEach(function (el) { observer.observe(el); });
        })();
    </script>
</body>
</html>
