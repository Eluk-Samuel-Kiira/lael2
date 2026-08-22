@extends('home.layout')

@section('title', 'STARDENA SUITE – Global Point of Sale System')

@section('content')

<!-- Hero Section -->
<section id="hero" class="hero section accent-background">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-6 order-2 order-lg-1 d-flex flex-column justify-content-center">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge" style="background: #fb7339; color: #fff; padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.7rem; font-weight: 600; letter-spacing: 0.5px;">
                        <i class="bi bi-rocket-takeoff me-1"></i> POWERED BY AI
                    </span>
                    <span class="badge" style="background: rgba(255,255,255,0.1); color: #fff; padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.7rem; font-weight: 500;">
                        <i class="bi bi-globe2 me-1"></i> 30+ COUNTRIES
                    </span>
                </div>

                <h1 style="font-size: clamp(2.2rem, 4.5vw, 3.8rem); font-weight: 800; line-height: 1.1; margin-bottom: 1.2rem;">
                    Manage <span style="color: #fb7339;">Multiple Businesses</span><br>
                    From <span style="color: #fb7339;">One Platform</span>
                </h1>

                <p style="font-size: 1.1rem; color: #8899AA; line-height: 1.7; max-width: 560px; margin-bottom: 1.8rem;">
                    The all-in-one business management system that powers restaurants, bars, supermarkets, 
                    hardware stores, pharmacies, manufacturing, and retail — with lightning-fast checkout, 
                    AI-driven insights, and seamless multi-location control.
                </p>

                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="#pricing" class="btn btn-danger px-5 py-3 rounded-pill fw-bold" style="background: #fb7339; border: none; box-shadow: 0 8px 25px rgba(251, 115, 57, 0.35);">
                        <i class="bi bi-calendar-check me-2"></i> Book A Demo
                    </a>
                    <a href="{{ route('login') }}" target="_blank" class="btn btn-outline-light px-5 py-3 rounded-pill fw-bold">
                        <i class="bi bi-play-circle me-2"></i> Live Demo
                    </a>
                </div>

                <!-- Trust Badges -->
                <div class="d-flex flex-wrap align-items-center gap-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill" style="color: #fb7339;"></i>
                        <span style="font-size: 0.8rem; color: #B0C4D8;">AI-Powered Insights</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill" style="color: #fb7339;"></i>
                        <span style="font-size: 0.8rem; color: #B0C4D8;">Free E-Commerce Listing</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill" style="color: #fb7339;"></i>
                        <span style="font-size: 0.8rem; color: #B0C4D8;">Offline Mode</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 order-1 order-lg-2 hero-img">
                <img src="{{ asset('front/img/hero-img.png') }}" class="img-fluid animated" alt="STARDENA SUITE POS Dashboard">
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="about section">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-6 order-1 order-lg-2" data-aos="fade-up" data-aos-delay="100">
                <img src="{{ asset('front/img/about.jpg') }}" class="img-fluid" alt="About STARDENA SUITE">
            </div>
            <div class="col-lg-6 order-2 order-lg-1 content" data-aos="fade-up" data-aos-delay="200">
                <h3>Built for Real-World Business</h3>
                <p class="fst-italic">
                    STARDENA SUITE was engineered to handle the challenges of modern business — unreliable internet, multiple currencies, diverse payment methods, and growing teams.
                </p>
                <ul>
                    <li><i class="bi bi-check-circle"></i> <span>Works offline — keep selling even without internet</span></li>
                    <li><i class="bi bi-check-circle"></i> <span>Multi-currency support — USD, KES, UGX, TZS, EUR & more</span></li>
                    <li><i class="bi bi-check-circle"></i> <span>Multi-language — English, Français, Español, Kiswahili</span></li>
                    <li><i class="bi bi-check-circle"></i> <span>Complete accounting — no separate software needed</span></li>
                </ul>
                <a href="#features" class="read-more"><span>Explore Features</span><i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section id="stats" class="stats section accent-background">
    <div class="container position-relative" data-aos="fade-up" data-aos-delay="100">
        <div class="row gy-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-item text-center w-100 h-100">
                    <span data-purecounter-start="0" data-purecounter-end="5000" data-purecounter-duration="2" class="purecounter"></span>
                    <p>Active Businesses</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-item text-center w-100 h-100">
                    <span data-purecounter-start="0" data-purecounter-end="30" data-purecounter-duration="2" class="purecounter"></span>
                    <p>Countries</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-item text-center w-100 h-100">
                    <span data-purecounter-start="0" data-purecounter-end="15" data-purecounter-duration="2" class="purecounter"></span>
                    <p>Currencies Supported</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-item text-center w-100 h-100">
                    <span data-purecounter-start="0" data-purecounter-end="4" data-purecounter-duration="2" class="purecounter"></span>
                    <p>Languages Available</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services / Features Section -->
<section id="services" class="services section">
    <div class="container section-title" data-aos="fade-up">
        <h2>What's Included</h2>
        <p>Everything you need to run your business efficiently</p>
    </div>

    <div class="container">
        <div class="row gy-4">
            <div class="col-xl-4 col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-briefcase"></i></div>
                    <div>
                        <h4 class="title"><a href="##" class="stretched-link">Complete Accounting</a></h4>
                        <p class="description">Full double-entry accounting — Chart of Accounts, General Ledger, Trial Balance, Income Statement.</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-6" data-aos="fade-up" data-aos-delay="200">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-people"></i></div>
                    <div>
                        <h4 class="title"><a href="##" class="stretched-link">HR & Employee Management</a></h4>
                        <p class="description">Staff profiles, role-based access, shift scheduling, attendance, and payroll management.</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-6" data-aos="fade-up" data-aos-delay="300">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-bar-chart"></i></div>
                    <div>
                        <h4 class="title"><a href="##" class="stretched-link">Advanced Analytics</a></h4>
                        <p class="description">Visual dashboards, custom reports, profit analysis, and exportable financials for smart decisions.</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-6" data-aos="fade-up" data-aos-delay="400">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-shop"></i></div>
                    <div>
                        <h4 class="title"><a href="##" class="stretched-link">Multi-Location Management</a></h4>
                        <p class="description">Control unlimited branches, warehouses, and departments from one central dashboard.</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-6" data-aos="fade-up" data-aos-delay="500">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-truck"></i></div>
                    <div>
                        <h4 class="title"><a href="##" class="stretched-link">Purchases & Suppliers</a></h4>
                        <p class="description">Issue purchase orders, track supplier invoices, manage balances, and receive stock with automated accounting.</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-6" data-aos="fade-up" data-aos-delay="600">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-palette"></i></div>
                    <div>
                        <h4 class="title"><a href="##" class="stretched-link">Custom Branding</a></h4>
                        <p class="description">White-label receipts, custom logo, business colors, and branded reports that represent your identity.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="features section">
    <div class="container section-title" data-aos="fade-up">
        <h2>Core Features</h2>
        <p>Powerful tools that make STARDENA SUITE the best POS for your business</p>
    </div>

    <div class="container">
        <div class="row gy-4 justify-content-between">
            <div class="features-image col-lg-5 order-lg-2 d-flex align-items-center" data-aos="fade-up" data-aos-delay="100">
                <img src="{{ asset('front/img/features.svg') }}" class="img-fluid" alt="Features">
            </div>
            <div class="col-lg-6 d-flex flex-column justify-content-center">
                <div class="features-item d-flex ps-0 ps-lg-3 pt-4 pt-lg-0" data-aos="fade-up" data-aos-delay="200">
                    <i class="bi bi-bolt flex-shrink-0"></i>
                    <div>
                        <h4>Lightning-Fast Checkout</h4>
                        <p>Close sales in under 3 seconds with barcode scanning and one-tap payments.</p>
                    </div>
                </div>

                <div class="features-item d-flex mt-5 ps-0 ps-lg-3" data-aos="fade-up" data-aos-delay="300">
                    <i class="bi bi-boxes flex-shrink-0"></i>
                    <div>
                        <h4>Smart Inventory Management</h4>
                        <p>Real-time stock tracking, low-stock alerts, batch management, and supplier integration.</p>
                    </div>
                </div>

                <div class="features-item d-flex mt-5 ps-0 ps-lg-3" data-aos="fade-up" data-aos-delay="400">
                    <i class="bi bi-coins flex-shrink-0"></i>
                    <div>
                        <h4>Multi-Currency & Payments</h4>
                        <p>Accept USD, KES, UGX, TZS, EUR. Support for Cash, M-Pesa, Card, and more.</p>
                    </div>
                </div>

                <div class="features-item d-flex mt-5 ps-0 ps-lg-3" data-aos="fade-up" data-aos-delay="500">
                    <i class="bi bi-globe flex-shrink-0"></i>
                    <div>
                        <h4>Multi-Language & Offline Mode</h4>
                        <p>Interface in English, French, Spanish, Swahili. Works offline — syncs automatically.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Industries Section -->
<section id="industries" class="industries section">
    <div class="container" data-aos="fade-up">
        <div class="section-title">
            <h2>Built for Every Business Type</h2>
            <p>From hospitality to retail, manufacturing to healthcare — one platform, unlimited possibilities</p>
        </div>

        <div class="row gy-4">
            <div class="col-lg-2 col-md-3 col-6">
                <div class="industry-item text-center p-3" style="background: var(--brand-card); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
                    <i class="bi bi-shop" style="font-size: 2rem; color: #fb7339;"></i>
                    <p style="font-size: 0.75rem; margin-top: 0.5rem; color: #B0C4D8; font-weight: 600;">Retail Stores</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <div class="industry-item text-center p-3" style="background: var(--brand-card); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
                    <i class="bi bi-building" style="font-size: 2rem; color: #fb7339;"></i>
                    <p style="font-size: 0.75rem; margin-top: 0.5rem; color: #B0C4D8; font-weight: 600;">Hotels & Lodges</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <div class="industry-item text-center p-3" style="background: var(--brand-card); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
                    <i class="bi bi-cup-straw" style="font-size: 2rem; color: #fb7339;"></i>
                    <p style="font-size: 0.75rem; margin-top: 0.5rem; color: #B0C4D8; font-weight: 600;">Bars & Restaurants</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <div class="industry-item text-center p-3" style="background: var(--brand-card); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
                    <i class="bi bi-basket" style="font-size: 2rem; color: #fb7339;"></i>
                    <p style="font-size: 0.75rem; margin-top: 0.5rem; color: #B0C4D8; font-weight: 600;">Supermarkets</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <div class="industry-item text-center p-3" style="background: var(--brand-card); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
                    <i class="bi bi-capsule" style="font-size: 2rem; color: #fb7339;"></i>
                    <p style="font-size: 0.75rem; margin-top: 0.5rem; color: #B0C4D8; font-weight: 600;">Pharmacies</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <div class="industry-item text-center p-3" style="background: var(--brand-card); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
                    <i class="bi bi-tools" style="font-size: 2rem; color: #fb7339;"></i>
                    <p style="font-size: 0.75rem; margin-top: 0.5rem; color: #B0C4D8; font-weight: 600;">Hardware Stores</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <div class="industry-item text-center p-3" style="background: var(--brand-card); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
                    <i class="bi bi-gear-wide-connected" style="font-size: 2rem; color: #fb7339;"></i>
                    <p style="font-size: 0.75rem; margin-top: 0.5rem; color: #B0C4D8; font-weight: 600;">Manufacturing</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <div class="industry-item text-center p-3" style="background: var(--brand-card); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
                    <i class="bi bi-bag" style="font-size: 2rem; color: #fb7339;"></i>
                    <p style="font-size: 0.75rem; margin-top: 0.5rem; color: #B0C4D8; font-weight: 600;">Boutiques & Fashion</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <div class="industry-item text-center p-3" style="background: var(--brand-card); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
                    <i class="bi bi-heart-pulse" style="font-size: 2rem; color: #fb7339;"></i>
                    <p style="font-size: 0.75rem; margin-top: 0.5rem; color: #B0C4D8; font-weight: 600;">Clinics & Healthcare</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <div class="industry-item text-center p-3" style="background: var(--brand-card); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
                    <i class="bi bi-book" style="font-size: 2rem; color: #fb7339;"></i>
                    <p style="font-size: 0.75rem; margin-top: 0.5rem; color: #B0C4D8; font-weight: 600;">Schools & Institutions</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <div class="industry-item text-center p-3" style="background: var(--brand-card); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
                    <i class="bi bi-car-front" style="font-size: 2rem; color: #fb7339;"></i>
                    <p style="font-size: 0.75rem; margin-top: 0.5rem; color: #B0C4D8; font-weight: 600;">Auto & Spare Parts</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <div class="industry-item text-center p-3" style="background: var(--brand-card); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
                    <i class="bi bi-box-seam" style="font-size: 2rem; color: #fb7339;"></i>
                    <p style="font-size: 0.75rem; margin-top: 0.5rem; color: #B0C4D8; font-weight: 600;">Warehousing & Distribution</p>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Key Benefits Section -->
<section id="benefits" class="benefits section accent-background">
    <div class="container" data-aos="fade-up">
        <div class="row gy-5">
            <div class="col-lg-6">
                <div class="d-flex flex-column gap-4">
                    <div class="benefit-item d-flex gap-3">
                        <div class="benefit-icon" style="flex-shrink: 0; width: 50px; height: 50px; background: rgba(251, 115, 57, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fb7339; font-size: 1.5rem;">
                            <i class="bi bi-diagram-3"></i>
                        </div>
                        <div>
                            <h4 style="color: #fff; font-weight: 700;">Multiple Businesses, One Platform</h4>
                            <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.6;">Run your restaurant, bar, supermarket, hardware store, and pharmacy from a single dashboard. No more juggling multiple systems.</p>
                        </div>
                    </div>

                    <div class="benefit-item d-flex gap-3">
                        <div class="benefit-icon" style="flex-shrink: 0; width: 50px; height: 50px; background: rgba(251, 115, 57, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fb7339; font-size: 1.5rem;">
                            <i class="bi bi-robot"></i>
                        </div>
                        <div>
                            <h4 style="color: #fff; font-weight: 700;">AI-Powered Insights</h4>
                            <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.6;">Get intelligent recommendations on stock levels, pricing strategies, customer behavior, and growth opportunities — powered by machine learning.</p>
                        </div>
                    </div>

                    <div class="benefit-item d-flex gap-3">
                        <div class="benefit-icon" style="flex-shrink: 0; width: 50px; height: 50px; background: rgba(251, 115, 57, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fb7339; font-size: 1.5rem;">
                            <i class="bi bi-cart4"></i>
                        </div>
                        <div>
                            <h4 style="color: #fff; font-weight: 700;">Free E-Commerce Listing</h4>
                            <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.6;">Every enrolled business gets a free listing on our integrated e-commerce platform. Increase visibility and sales to the public domain via our API.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="d-flex flex-column gap-4">
                    <div class="benefit-item d-flex gap-3">
                        <div class="benefit-icon" style="flex-shrink: 0; width: 50px; height: 50px; background: rgba(251, 115, 57, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fb7339; font-size: 1.5rem;">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div>
                            <h4 style="color: #fff; font-weight: 700;">Detailed Business Intelligence</h4>
                            <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.6;">Make informed decisions with comprehensive reports — sales trends, profit margins, customer analytics, inventory forecasts, and AI-driven recommendations.</p>
                        </div>
                    </div>

                    <div class="benefit-item d-flex gap-3">
                        <div class="benefit-icon" style="flex-shrink: 0; width: 50px; height: 50px; background: rgba(251, 115, 57, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fb7339; font-size: 1.5rem;">
                            <i class="bi bi-lightning-charge"></i>
                        </div>
                        <div>
                            <h4 style="color: #fff; font-weight: 700;">Lightning-Fast Checkout</h4>
                            <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.6;">Process sales in under 3 seconds with barcode scanning, one-tap payments, and intelligent product search. Keep your customers happy and lines moving.</p>
                        </div>
                    </div>

                    <div class="benefit-item d-flex gap-3">
                        <div class="benefit-icon" style="flex-shrink: 0; width: 50px; height: 50px; background: rgba(251, 115, 57, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fb7339; font-size: 1.5rem;">
                            <i class="bi bi-wifi-off"></i>
                        </div>
                        <div>
                            <h4 style="color: #fff; font-weight: 700;">Works Offline</h4>
                            <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.6;">No internet? No problem. Process sales, print receipts, and manage inventory offline. Auto-syncs when you're back online. Perfect for African markets.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section id="pricing" class="pricing section">
    <div class="container section-title" data-aos="fade-up">
        <h2>Pricing</h2>
        <p>Plans that grow with your business — no hidden fees, cancel anytime</p>
    </div>

    <div class="container">
        <div class="row gy-4">
            <!-- Free Plan -->
            <div class="col-lg-3" data-aos="zoom-in" data-aos-delay="100">
                <div class="pricing-item">
                    <h3>Free Trial</h3>
                    <h4><sup>$</sup>0<span> / 65 days</span></h4>
                    <ul>
                        <li><i class="bi bi-check"></i> <span>1 Store Location</span></li>
                        <li><i class="bi bi-check"></i> <span>3 Departments</span></li>
                        <li><i class="bi bi-check"></i> <span>Up to 3 Users</span></li>
                        <li><i class="bi bi-check"></i> <span>100 Products & Customers</span></li>
                        <li class="na"><i class="bi bi-x"></i> <span>Advanced Reports</span></li>
                        <li class="na"><i class="bi bi-x"></i> <span>Multi-Currency</span></li>
                    </ul>
                    <a href="#contact" class="buy-btn">Start Trial</a>
                </div>
            </div>

            <!-- Starter Plan -->
            <div class="col-lg-3" data-aos="zoom-in" data-aos-delay="200">
                <div class="pricing-item">
                    <h3>Starter</h3>
                    <h4><sup>$</sup>29.99<span> / month</span></h4>
                    <ul>
                        <li><i class="bi bi-check"></i> <span>1 Store Location</span></li>
                        <li><i class="bi bi-check"></i> <span>2 Payment Methods</span></li>
                        <li><i class="bi bi-check"></i> <span>Up to 2 Users</span></li>
                        <li><i class="bi bi-check"></i> <span>500 Products & Customers</span></li>
                        <li><i class="bi bi-check"></i> <span>Inventory Management</span></li>
                        <li class="na"><i class="bi bi-x"></i> <span>Multi-Currency</span></li>
                    </ul>
                    <a href="#contact" class="buy-btn">Get Started</a>
                </div>
            </div>

            <!-- Business Plan (Popular) -->
            <div class="col-lg-3" data-aos="zoom-in" data-aos-delay="300">
                <div class="pricing-item featured">
                    <h3>Business</h3>
                    <h4><sup>$</sup>79.99<span> / month</span></h4>
                    <ul>
                        <li><i class="bi bi-check"></i> <span>Up to 3 Store Locations</span></li>
                        <li><i class="bi bi-check"></i> <span>10 Payment Methods</span></li>
                        <li><i class="bi bi-check"></i> <span>Up to 10 Users</span></li>
                        <li><i class="bi bi-check"></i> <span>5,000 Products & Customers</span></li>
                        <li><i class="bi bi-check"></i> <span>Full Accounting Suite</span></li>
                        <li><i class="bi bi-check"></i> <span>Multi-Currency</span></li>
                    </ul>
                    <a href="#contact" class="buy-btn">Get Started</a>
                </div>
            </div>

            <!-- Enterprise Plan -->
            <div class="col-lg-3" data-aos="zoom-in" data-aos-delay="400">
                <div class="pricing-item">
                    <h3>Enterprise</h3>
                    <h4><sup>$</sup>199.99<span> / month</span></h4>
                    <ul>
                        <li><i class="bi bi-check"></i> <span>Unlimited Locations</span></li>
                        <li><i class="bi bi-check"></i> <span>Unlimited Users</span></li>
                        <li><i class="bi bi-check"></i> <span>Unlimited Products</span></li>
                        <li><i class="bi bi-check"></i> <span>Full HR & Payroll</span></li>
                        <li><i class="bi bi-check"></i> <span>API Access</span></li>
                        <li><i class="bi bi-check"></i> <span>Priority 24/7 Support</span></li>
                    </ul>
                    <a href="#contact" class="buy-btn">Contact Us</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Partners Section -->
<section id="partners" class="partners section light-background">
    <div class="container section-title" data-aos="fade-up">
        <h2>Trusted By Businesses Worldwide</h2>
        <p>Join 5,000+ companies across 30+ countries using STARDENA SUITE</p>
    </div>

    <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="swiper init-swiper" data-speed="600" data-delay="3000" data-breakpoints='{"320":{"slidesPerView":2,"spaceBetween":20},"768":{"slidesPerView":3,"spaceBetween":30},"992":{"slidesPerView":4,"spaceBetween":30},"1200":{"slidesPerView":5,"spaceBetween":40}}'>
            <script type="application/json" class="swiper-config">
                {
                    "loop": true,
                    "speed": 600,
                    "autoplay": { "delay": 3000 },
                    "slidesPerView": "auto",
                    "pagination": { "el": ".swiper-pagination", "type": "bullets", "clickable": true },
                    "breakpoints": {
                        "320": { "slidesPerView": 2, "spaceBetween": 20 },
                        "768": { "slidesPerView": 3, "spaceBetween": 30 },
                        "992": { "slidesPerView": 4, "spaceBetween": 30 },
                        "1200": { "slidesPerView": 5, "spaceBetween": 40 }
                    }
                }
            </script>
            <div class="swiper-wrapper">
                <!-- Partner 1 -->
                <div class="swiper-slide">
                    <div class="partner-item text-center p-4" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); transition: all 0.3s ease; min-height: 120px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <img src="{{ asset('partners/gwt.png') }}" alt="Partner Logo" style="max-height: 60px; width: auto; max-width: 120px; object-fit: contain; filter: brightness(0.8) grayscale(0.3); transition: all 0.3s ease;">
                        <p style="font-size: 0.7rem; color: #8899AA; margin-top: 8px; font-weight: 500;">GWT (U) LIMITED</p>
                    </div>
                </div>

                <!-- Partner 2 -->
                <div class="swiper-slide">
                    <div class="partner-item text-center p-4" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); min-height: 120px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <img src="{{ asset('front/img/partners/partner-2.png') }}" alt="Partner Logo" style="max-height: 60px; width: auto; max-width: 120px; object-fit: contain; filter: brightness(0.8) grayscale(0.3);">
                        <p style="font-size: 0.7rem; color: #8899AA; margin-top: 8px; font-weight: 500;">Company Name</p>
                    </div>
                </div>

                <!-- Partner 3 -->
                <div class="swiper-slide">
                    <div class="partner-item text-center p-4" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); min-height: 120px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <img src="{{ asset('front/img/partners/partner-3.png') }}" alt="Partner Logo" style="max-height: 60px; width: auto; max-width: 120px; object-fit: contain; filter: brightness(0.8) grayscale(0.3);">
                        <p style="font-size: 0.7rem; color: #8899AA; margin-top: 8px; font-weight: 500;">Company Name</p>
                    </div>
                </div>

                <!-- Partner 4 -->
                <div class="swiper-slide">
                    <div class="partner-item text-center p-4" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); min-height: 120px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <img src="{{ asset('front/img/partners/partner-4.png') }}" alt="Partner Logo" style="max-height: 60px; width: auto; max-width: 120px; object-fit: contain; filter: brightness(0.8) grayscale(0.3);">
                        <p style="font-size: 0.7rem; color: #8899AA; margin-top: 8px; font-weight: 500;">Company Name</p>
                    </div>
                </div>

                <!-- Partner 5 -->
                <div class="swiper-slide">
                    <div class="partner-item text-center p-4" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); min-height: 120px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <img src="{{ asset('front/img/partners/partner-5.png') }}" alt="Partner Logo" style="max-height: 60px; width: auto; max-width: 120px; object-fit: contain; filter: brightness(0.8) grayscale(0.3);">
                        <p style="font-size: 0.7rem; color: #8899AA; margin-top: 8px; font-weight: 500;">Company Name</p>
                    </div>
                </div>

                <!-- Partner 6 -->
                <div class="swiper-slide">
                    <div class="partner-item text-center p-4" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); min-height: 120px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <img src="{{ asset('front/img/partners/partner-6.png') }}" alt="Partner Logo" style="max-height: 60px; width: auto; max-width: 120px; object-fit: contain; filter: brightness(0.8) grayscale(0.3);">
                        <p style="font-size: 0.7rem; color: #8899AA; margin-top: 8px; font-weight: 500;">Company Name</p>
                    </div>
                </div>

                <!-- Partner 7 -->
                <div class="swiper-slide">
                    <div class="partner-item text-center p-4" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); min-height: 120px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <img src="{{ asset('front/img/partners/partner-7.png') }}" alt="Partner Logo" style="max-height: 60px; width: auto; max-width: 120px; object-fit: contain; filter: brightness(0.8) grayscale(0.3);">
                        <p style="font-size: 0.7rem; color: #8899AA; margin-top: 8px; font-weight: 500;">Company Name</p>
                    </div>
                </div>

                <!-- Partner 8 -->
                <div class="swiper-slide">
                    <div class="partner-item text-center p-4" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); min-height: 120px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <img src="{{ asset('front/img/partners/partner-8.png') }}" alt="Partner Logo" style="max-height: 60px; width: auto; max-width: 120px; object-fit: contain; filter: brightness(0.8) grayscale(0.3);">
                        <p style="font-size: 0.7rem; color: #8899AA; margin-top: 8px; font-weight: 500;">Company Name</p>
                    </div>
                </div>
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="faq section">
    <div class="container section-title" data-aos="fade-up">
        <h2>Frequently Asked Questions</h2>
        <p>Everything you need to know before getting started</p>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10" data-aos="fade-up" data-aos-delay="100">
                <div class="faq-container">
                    <div class="faq-item faq-active">
                        <h3>What types of businesses can use STARDENA SUITE?</h3>
                        <div class="faq-content">
                            <p>STARDENA SUITE is designed for virtually any business: retail shops, supermarkets, restaurants, cafés, pharmacies, electronics stores, clothing boutiques, beauty salons, schools, and more. Our flexible module system means you only activate what your business needs.</p>
                        </div>
                        <i class="faq-toggle bi bi-chevron-right"></i>
                    </div>

                    <div class="faq-item">
                        <h3>Does STARDENA SUITE work without internet connection?</h3>
                        <div class="faq-content">
                            <p>Yes! STARDENA SUITE has a robust offline mode. You can process sales, print receipts, and manage inventory even when internet connectivity is lost. Once your connection is restored, all data syncs automatically to the cloud.</p>
                        </div>
                        <i class="faq-toggle bi bi-chevron-right"></i>
                    </div>

                    <div class="faq-item">
                        <h3>Which payment methods does STARDENA SUITE support?</h3>
                        <div class="faq-content">
                            <p>STARDENA SUITE supports Cash, Card (Visa/Mastercard), Mobile Money (M-Pesa, Airtel Money, MTN MoMo, Tigopesa), Bank Transfers, Cheque, and multiple custom payment accounts.</p>
                        </div>
                        <i class="faq-toggle bi bi-chevron-right"></i>
                    </div>

                    <div class="faq-item">
                        <h3>Can I manage multiple shops or branches?</h3>
                        <div class="faq-content">
                            <p>Absolutely. The Business and Enterprise plans support multiple locations. You can transfer stock between branches, view consolidated reports, set location-specific pricing, and manage all staff from a single account.</p>
                        </div>
                        <i class="faq-toggle bi bi-chevron-right"></i>
                    </div>

                    <div class="faq-item">
                        <h3>Is STARDENA SUITE available in multiple languages?</h3>
                        <div class="faq-content">
                            <p>Yes. The interface is available in English, French (Français), Spanish (Español), and Swahili (Kiswahili), with more languages being added. Each staff member can choose their preferred language independently.</p>
                        </div>
                        <i class="faq-toggle bi bi-chevron-right"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="contact section">
    <div class="container section-title" data-aos="fade-up">
        <h2>Contact Us</h2>
        <p>Get in touch with our team — we're here to help</p>
    </div>

    <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row gy-4">
            <div class="col-lg-6">
                <div class="row gy-4">
                    <div class="col-md-6">
                        <div class="info-item" data-aos="fade" data-aos-delay="200">
                            <i class="bi bi-geo-alt"></i>
                            <h3>Location</h3>
                            <p>Kampala, Uganda</p>
                            <p>East Africa</p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="info-item" data-aos="fade" data-aos-delay="300">
                            <i class="bi bi-envelope"></i>
                            <h3>Email</h3>
                            <p>pos@stardena.org</p>
                            <p>support@stardena.org</p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="info-item" data-aos="fade" data-aos-delay="400">
                            <i class="bi bi-clock"></i>
                            <h3>Support Hours</h3>
                            <p>Monday - Friday</p>
                            <p>8:00 AM - 6:00 PM (EAT)</p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="info-item" data-aos="fade" data-aos-delay="500">
                            <i class="bi bi-globe"></i>
                            <h3>Website</h3>
                            <p>suite.stardena.org</p>
                            <p>stardena.org</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <form action="{{ route('inquiry.send') }}" method="post" class="php-email-form" data-aos="fade-up" data-aos-delay="200">
                    @csrf
                    <div class="row gy-4">
                        <div class="col-md-6">
                            <input type="text" name="name" class="form-control" placeholder="Your Name" required>
                        </div>

                        <div class="col-md-6">
                            <input type="email" class="form-control" name="email" placeholder="Your Email" required>
                        </div>

                        <div class="col-12">
                            <input type="text" class="form-control" name="subject" placeholder="Subject" required>
                        </div>

                        <div class="col-12">
                            <textarea class="form-control" name="message" rows="6" placeholder="Message" required></textarea>
                        </div>

                        <div class="col-12 text-center">
                            <div class="loading d-none">Loading</div>
                            <div class="error-message d-none"></div>
                            <div class="sent-message d-none">Your message has been sent. Thank you!</div>
                            <button type="submit">Send Message</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@endsection