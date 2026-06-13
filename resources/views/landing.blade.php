<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GMD South Phils — Precision Storage Tank Fabrication</title>
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
            <li><a href="#about">About</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#process">Our Process</a></li>
            <li><a href="#tanks">Tank Types</a></li>
            <li><a href="#portfolio">Our Work</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>

        <div class="nav-actions">
            <a href="{{ route('login') }}" class="nav-login">Login</a>
        </div>
    </nav>


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
                <a href="#services" class="btn-gold">
                    <i data-lucide="layers"></i>
                    Explore Our Services
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
            <div class="stat-num">8</div>
            <div class="stat-label">Fabrication Phases</div>
        </div>
        <div class="stat-box reveal">
            <div class="stat-num">4<sup>+</sup></div>
            <div class="stat-label">Tank Types Handled</div>
        </div>
        <div class="stat-box reveal">
            <div class="stat-num">100%</div>
            <div class="stat-label">Quality Inspection</div>
        </div>
        <div class="stat-box reveal">
            <div class="stat-num">24<span class="stat-slash">/</span>7</div>
            <div class="stat-label">Project Monitoring</div>
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
                <div class="about-visual-title">Fabrication Process Overview</div>
                <div class="about-phase-list">
                    @php
                    $phases = [
                        ['Planning',              'Engineering design and client requirements'],
                        ['Procurement',           'Material and component sourcing'],
                        ['Material Preparation',  'Steel cutting and component preparation'],
                        ['Fabrication',           'Expert welding and structural assembly'],
                        ['Inspection',            'Quality control and pressure testing'],
                        ['Painting',              'Surface preparation and protective coating'],
                        ['Completion',            'Final checks and client sign-off'],
                        ['Delivery',              'Tank transport and installation support'],
                    ];
                    @endphp
                    @foreach($phases as $i => $phase)
                    <div class="about-phase-item">
                        <span class="about-phase-num">{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="about-phase-dot"></span>
                        <span>{{ $phase[0] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </section>


    <!-- ============================================================
         SERVICES
    ============================================================ -->
    <section class="services-wrap" id="services">
        <div class="services-inner">
            <div class="section-tag reveal">What We Offer</div>
            <h2 class="section-title reveal">End-to-End Tank Fabrication Services</h2>
            <p class="section-sub reveal">From concept to completion, we provide everything your project needs — under one roof.</p>

            <div class="services-grid">

                <div class="service-card reveal">
                    <div class="sc-icon"><i data-lucide="drafting-compass"></i></div>
                    <div class="sc-name">Custom Tank Design</div>
                    <p class="sc-desc">We design tanks to your exact specifications — capacity, dimensions, material grade, and industry-specific requirements.</p>
                </div>

                <div class="service-card reveal">
                    <div class="sc-icon"><i data-lucide="wrench"></i></div>
                    <div class="sc-name">Steel Fabrication</div>
                    <p class="sc-desc">Our team of experienced welders and fabricators construct durable steel tanks using quality materials and proven techniques.</p>
                </div>

                <div class="service-card reveal">
                    <div class="sc-icon"><i data-lucide="package-search"></i></div>
                    <div class="sc-name">Material Procurement</div>
                    <p class="sc-desc">We source and procure all structural steel, fittings, and components — ensuring material quality at every stage.</p>
                </div>

                <div class="service-card reveal">
                    <div class="sc-icon"><i data-lucide="shield-check"></i></div>
                    <div class="sc-name">Quality Inspection</div>
                    <p class="sc-desc">Rigorous multi-point inspection and pressure testing ensures every tank meets safety and performance standards before leaving our facility.</p>
                </div>

                <div class="service-card reveal">
                    <div class="sc-icon"><i data-lucide="paintbrush"></i></div>
                    <div class="sc-name">Painting & Coating</div>
                    <p class="sc-desc">Professional surface preparation and anti-corrosion coating systems extend tank lifespan and meet client or regulatory specifications.</p>
                </div>

                <div class="service-card reveal">
                    <div class="sc-icon"><i data-lucide="truck"></i></div>
                    <div class="sc-name">Delivery & Installation</div>
                    <p class="sc-desc">Safe transport and on-site placement support ensures your tank arrives on schedule and in perfect condition — wherever you are.</p>
                </div>

            </div>
        </div>
    </section>


    <!-- ============================================================
         PROCESS
    ============================================================ -->
    <section class="process-wrap" id="process">
        <div class="process-inner">
            <div class="section-tag reveal">How We Work</div>
            <h2 class="section-title reveal">Our 8-Phase Fabrication Process</h2>
            <p class="section-sub reveal">Every project follows a structured workflow — tracked live through our project management system.</p>

            <div class="process-grid">

                <div class="process-step reveal">
                    <div class="ps-num">Phase 01</div>
                    <div class="ps-icon"><i data-lucide="clipboard-list"></i></div>
                    <div class="ps-name">Planning</div>
                    <p class="ps-desc">Requirements gathering, engineering design, and project timeline development.</p>
                </div>

                <div class="process-step reveal">
                    <div class="ps-num">Phase 02</div>
                    <div class="ps-icon"><i data-lucide="shopping-cart"></i></div>
                    <div class="ps-name">Procurement</div>
                    <p class="ps-desc">Material ordering and component sourcing from quality-verified suppliers.</p>
                </div>

                <div class="process-step reveal">
                    <div class="ps-num">Phase 03</div>
                    <div class="ps-icon"><i data-lucide="scissors"></i></div>
                    <div class="ps-name">Material Preparation</div>
                    <p class="ps-desc">Steel cutting, forming, and preparation of all structural components.</p>
                </div>

                <div class="process-step reveal">
                    <div class="ps-num">Phase 04</div>
                    <div class="ps-icon"><i data-lucide="hammer"></i></div>
                    <div class="ps-name">Fabrication</div>
                    <p class="ps-desc">Expert welding and structural assembly by our skilled fabrication team.</p>
                </div>

                <div class="process-step reveal">
                    <div class="ps-num">Phase 05</div>
                    <div class="ps-icon"><i data-lucide="search-check"></i></div>
                    <div class="ps-name">Inspection</div>
                    <p class="ps-desc">Comprehensive quality control, weld inspection, and pressure testing.</p>
                </div>

                <div class="process-step reveal">
                    <div class="ps-num">Phase 06</div>
                    <div class="ps-icon"><i data-lucide="paintbrush-2"></i></div>
                    <div class="ps-name">Painting</div>
                    <p class="ps-desc">Surface blasting and application of protective anti-corrosion coatings.</p>
                </div>

                <div class="process-step reveal">
                    <div class="ps-num">Phase 07</div>
                    <div class="ps-icon"><i data-lucide="check-circle-2"></i></div>
                    <div class="ps-name">Completion</div>
                    <p class="ps-desc">Final walkthrough, documentation, and client sign-off before dispatch.</p>
                </div>

                <div class="process-step reveal">
                    <div class="ps-num">Phase 08</div>
                    <div class="ps-icon"><i data-lucide="truck"></i></div>
                    <div class="ps-name">Delivery</div>
                    <p class="ps-desc">Safe transport to site with installation coordination and handover support.</p>
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
            <h2 class="section-title reveal">Tank Types We Fabricate</h2>
            <p class="section-sub reveal">We build a wide range of industrial storage tanks engineered for different applications and industries.</p>

            <div class="tanks-grid">

                <div class="tank-card reveal">
                    <div class="tank-icon"><i data-lucide="flame"></i></div>
                    <div class="tank-name">Fuel Storage Tanks</div>
                    <p class="tank-desc">Diesel, gasoline, and petroleum product tanks built to handle flammable storage safely and efficiently.</p>
                </div>

                <div class="tank-card reveal">
                    <div class="tank-icon"><i data-lucide="droplets"></i></div>
                    <div class="tank-name">Water Storage Tanks</div>
                    <p class="tank-desc">Industrial and commercial water tanks for construction sites, agriculture, and community water supply.</p>
                </div>

                <div class="tank-card reveal">
                    <div class="tank-icon"><i data-lucide="wheat"></i></div>
                    <div class="tank-name">Cooking Oil Tanks</div>
                    <p class="tank-desc">Food-grade stainless or carbon steel tanks designed for edible oil storage with hygiene-compliant interiors.</p>
                </div>

                <div class="tank-card reveal">
                    <div class="tank-icon"><i data-lucide="flask-conical"></i></div>
                    <div class="tank-name">Chemical Storage Tanks</div>
                    <p class="tank-desc">Corrosion-resistant tanks engineered for safe storage of industrial chemicals, acids, and solvents.</p>
                </div>

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
            <p class="section-sub reveal">A look at the kind of storage tanks and fabrication work we deliver — built to spec, tested, and ready for the field.</p>

            <div class="portfolio-grid">
                @php
                $portfolio = [
                    [
                        'image' => 'images/portfolio/fuel-tank-1.jpg', 'icon' => 'flame',
                        'spec'  => '10,000 L',
                        'tag'   => 'Fuel Storage',
                        'title' => 'Diesel Storage Tank — Distribution Depot',
                        'desc'  => 'Cylindrical carbon steel tank with internal baffles, fitted with vents, gauges, and dike containment for a fuel distribution site.',
                    ],
                    [
                        'image' => null, 'icon' => 'droplets',
                        'spec'  => '5,000 L',
                        'tag'   => 'Water Storage',
                        'title' => 'Elevated Water Tank — Municipal Supply',
                        'desc'  => 'Steel elevated water reservoir with support tower, designed for consistent pressure delivery to a community water system.',
                    ],
                    [
                        'image' => null, 'icon' => 'wheat',
                        'spec'  => 'Food-grade',
                        'tag'   => 'Cooking Oil Tank',
                        'title' => 'Stainless Storage Vessel — Food Plant',
                        'desc'  => 'Hygiene-compliant stainless interior with polished welds, built for edible oil storage in a food manufacturing facility.',
                    ],
                    [
                        'image' => null, 'icon' => 'flask-conical',
                        'spec'  => 'Corrosion-resist.',
                        'tag'   => 'Chemical Storage',
                        'title' => 'Acid-Resistant Tank — Chemical Plant',
                        'desc'  => 'Reinforced shell with corrosion-resistant lining and secondary containment, fabricated for industrial chemical storage.',
                    ],
                    [
                        'image' => null, 'icon' => 'flame-kindling',
                        'spec'  => 'Pressurized',
                        'tag'   => 'LPG / Gas Vessel',
                        'title' => 'Pressure Vessel — LPG Storage',
                        'desc'  => 'ASME-style pressure vessel with full weld inspection and pneumatic pressure testing prior to client handover.',
                    ],
                    [
                        'image' => null, 'icon' => 'container',
                        'spec'  => 'Custom',
                        'tag'   => 'Custom Fabrication',
                        'title' => 'Custom Tank — On-Site Installation',
                        'desc'  => 'Engineered to a client\'s exact dimensions and capacity, fabricated in our shop and delivered with on-site installation support.',
                    ],
                ];
                @endphp

                @foreach($portfolio as $item)
                <div class="portfolio-card reveal">
                    <div class="portfolio-media"
                         @if($item['image']) style="background-image:url('{{ asset($item['image']) }}')" @endif>
                        <span class="portfolio-spec">{{ $item['spec'] }}</span>
                        @if($item['image'])
                            <img src="{{ asset($item['image']) }}" alt="{{ $item['title'] }}">
                        @else
                            <div class="portfolio-media-icon"><i data-lucide="{{ $item['icon'] }}"></i></div>
                        @endif
                    </div>
                    <div class="portfolio-body">
                        <div class="portfolio-tag">
                            <i data-lucide="tag" style="width:11px;height:11px;"></i>
                            {{ $item['tag'] }}
                        </div>
                        <div class="portfolio-title">{{ $item['title'] }}</div>
                        <p class="portfolio-desc">{{ $item['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="portfolio-foot">
                <p>Every project follows our structured 8-phase workflow — from planning and procurement to final inspection and delivery, with photo documentation captured at each stage.</p>
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
                    <div class="wc-title">Quality at Every Phase</div>
                    <p class="wc-desc">Inspection checkpoints throughout fabrication ensure structural integrity, weld quality, and finish standards are met.</p>
                </div>

                <div class="why-card reveal">
                    <div class="wc-icon"><i data-lucide="ruler"></i></div>
                    <div class="wc-title">Custom Specifications</div>
                    <p class="wc-desc">No two projects are the same. We engineer each tank to match your exact capacity, dimension, and application requirements.</p>
                </div>

                <div class="why-card reveal">
                    <div class="wc-icon"><i data-lucide="clock-3"></i></div>
                    <div class="wc-title">On-Time Delivery</div>
                    <p class="wc-desc">Our structured 8-phase workflow keeps projects on track and delivers tanks on the agreed schedule.</p>
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
         PORTAL CTA
    ============================================================ -->
    <section class="portal-cta">
        <div class="portal-glow"></div>
        <div class="portal-cta-inner">

            <div class="portal-left reveal-left">
                <div class="section-tag">Project Monitoring</div>
                <h2 class="section-title">Track Your Project Live</h2>
                <p class="section-sub">Our project management portal gives clients, employees, and administrators live visibility into every phase of fabrication — with photo updates, progress tracking, and instant notifications.</p>

                <div class="portal-features">
                    <div class="pf-item">
                        <div class="pf-icon"><i data-lucide="layers"></i></div>
                        <span class="pf-text">Phase-by-phase progress tracker</span>
                    </div>
                    <div class="pf-item">
                        <div class="pf-icon"><i data-lucide="camera"></i></div>
                        <span class="pf-text">Photo documentation per update</span>
                    </div>
                    <div class="pf-item">
                        <div class="pf-icon"><i data-lucide="bell"></i></div>
                        <span class="pf-text">Instant notifications on project events</span>
                    </div>
                    <div class="pf-item">
                        <div class="pf-icon"><i data-lucide="lock"></i></div>
                        <span class="pf-text">Secure role-based access</span>
                    </div>
                </div>
            </div>

            <div class="portal-card reveal-right">
                <div class="portal-card-header">
                    <div class="portal-card-ico"><i data-lucide="layout-dashboard"></i></div>
                    <div>
                        <div class="portal-card-title">GMD Project Portal</div>
                        <div class="portal-card-sub">Secure login for all stakeholders</div>
                    </div>
                </div>

                <div class="portal-divider"></div>

                <div class="portal-roles">
                    <div class="role-chip">
                        <i data-lucide="shield"></i>
                        Admin — Full project control
                        <div class="role-dot"></div>
                    </div>
                    <div class="role-chip">
                        <i data-lucide="user"></i>
                        Client — View project progress
                        <div class="role-dot"></div>
                    </div>
                    <div class="role-chip">
                        <i data-lucide="hard-hat"></i>
                        Employee — Submit field updates
                        <div class="role-dot"></div>
                    </div>
                </div>

                <a href="{{ route('login') }}" class="btn-portal">Login to Portal</a>
            </div>

        </div>
    </section>


    <!-- ============================================================
         CONTACT
    ============================================================ -->
    <section class="contact-wrap" id="contact">
        <div class="contact-inner">

            <div class="reveal-left">
                <div class="section-tag">Get in Touch</div>
                <h2 class="section-title">Start Your Project Today</h2>
                <p class="section-sub">Have a project in mind? Reach out and our team will get back to you with a consultation and timeline estimate.</p>

                <div class="contact-items">
                    <div class="ci-row">
                        <div class="ci-icon"><i data-lucide="map-pin"></i></div>
                        <div>
                            <div class="ci-label">Location</div>
                            <div class="ci-value">Brgy. Masiit, Calauan, Philippines, 4012</div>
                        </div>
                    </div>
                    <div class="ci-row">
                        <div class="ci-icon"><i data-lucide="phone"></i></div>
                        <div>
                            <div class="ci-label">Phone</div>
                            <div class="ci-value">
                                <a href="tel:+639176525201">0917 652 5201</a>
                            </div>
                        </div>
                    </div>
                    <div class="ci-row">
                        <div class="ci-icon"><i data-lucide="mail"></i></div>
                        <div>
                            <div class="ci-label">Email</div>
                            <div class="ci-value">
                                <a href="mailto:gmdsouthphils@gmail.com">gmdsouthphils@gmail.com</a>
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
                                <a href="https://www.facebook.com/gmdsouthphils" target="_blank" rel="noopener">facebook.com/gmdsouthphils</a>
                            </div>
                        </div>
                    </div>
                    <div class="ci-row">
                        <div class="ci-icon"><i data-lucide="clock"></i></div>
                        <div>
                            <div class="ci-label">Business Hours</div>
                            <div class="ci-value">Monday – Saturday, 8:00 AM – 5:00 PM</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contact-right reveal-right">
                <div class="contact-card">
                    <div class="cc-icon"><i data-lucide="message-circle"></i></div>
                    <div>
                        <div class="cc-title">Request a Quotation</div>
                        <p class="cc-body">Tell us your tank type, capacity, and intended use — we'll provide a detailed project estimate and timeline.</p>
                    </div>
                </div>
                <div class="contact-card">
                    <div class="cc-icon"><i data-lucide="file-text"></i></div>
                    <div>
                        <div class="cc-title">Technical Consultation</div>
                        <p class="cc-body">Not sure what tank you need? Our engineers can recommend the right design and material specification for your application.</p>
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
                    <li><a href="#services">Services</a></li>
                    <li><a href="#process">Our Process</a></li>
                    <li><a href="#tanks">Tank Types</a></li>
                    <li><a href="#portfolio">Our Work</a></li>
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
        lucide.createIcons();

        // Sticky nav shadow on scroll
        window.addEventListener('scroll', function () {
            var nav = document.getElementById('lpNav');
            if (window.scrollY > 20) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });

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
            var staggerGroups = ['services-grid', 'tanks-grid', 'process-grid', 'portfolio-grid', 'why-grid', 'stats-bar', 'about-highlights'];

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
