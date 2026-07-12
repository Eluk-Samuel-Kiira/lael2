@extends('home.layout')

@php
    $allowedDomains = ['starpos.stardena.org', '127.0.0.1', 'localhost'];
@endphp

@if(!in_array(request()->getHost(), $allowedDomains))
    <script>
        window.location.href = '/login';
    </script>
@endif

<!-- ═══════════════════════════════════════
     HERO
═══════════════════════════════════════ -->
<section class="hero" id="home">
    <div class="hero-bg-grid"></div>
    <div class="hero-glow-1"></div>
    <div class="hero-glow-2"></div>

    <div class="container position-relative">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="hero-badge">
                    <span class="pulse-dot"></span>
                    Now live &amp; trusted worldwide
                </div>

                <h1 class="hero-title">
                    The <span class="highlight">Fastest</span> Point of Sale for Every Business on Earth
                </h1>

                <p class="hero-sub">
                    STARPOSS powers retail shops, restaurants, hotels, and multi-chain enterprises across East Africa and beyond — with lightning-fast checkout, multi-currency, and multi-language support built in.
                </p>

                <div class="hero-cta-group">
                    <a href="#pricing" class="btn-hero-primary">
                        <i class="fas fa-rocket"></i> Start Free Trial
                    </a>
                    <a href="{{ route('login') }}" class="btn-hero-ghost">
                        <i class="fas fa-desktop"></i> View Live Demo
                    </a>
                </div>

                <div class="hero-stats">
                    <div class="hero-stat-item">
                        <div class="hero-stat-num">5,000+</div>
                        <div class="hero-stat-label">Active Businesses</div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="hero-stat-num">30+</div>
                        <div class="hero-stat-label">Countries</div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="hero-stat-num">99.9%</div>
                        <div class="hero-stat-label">Uptime SLA</div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="hero-stat-num">3s</div>
                        <div class="hero-stat-label">Avg Checkout</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="hero-visual">
                    <div class="floating-tag tag-1">
                        <div class="tag-icon" style="background:rgba(6,214,160,0.15); color:var(--brand-green);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <div style="font-size:0.75rem;color:#E2EAF4;font-weight:600;">Sale Complete</div>
                            <div style="font-size:0.7rem;color:var(--brand-green);">KES 4,500 received</div>
                        </div>
                    </div>

                    <div class="hero-screen">
                        <div class="hero-screen-bar">
                            <span class="screen-dot red"></span>
                            <span class="screen-dot yellow"></span>
                            <span class="screen-dot green"></span>
                            <span style="font-size:0.7rem;color:#4A5568;margin-left:8px;">STARPOSS — Cashier View</span>
                        </div>
                        <div class="pos-interface">
                            <div class="pos-top-bar">
                                <div>
                                    <div class="pos-greeting">Today's Sales</div>
                                    <div style="font-size:0.7rem;color:var(--brand-green);">↑ 24% from yesterday</div>
                                </div>
                                <span class="pos-currency-tag">USD / KES / UGX</span>
                            </div>

                            <div class="pos-amount">$1,248.00</div>

                            <div class="pos-items">
                                <div class="pos-item">
                                    <span class="pos-item-name">Samsung Galaxy A25 × 2</span>
                                    <span class="pos-item-price">$380.00</span>
                                </div>
                                <div class="pos-item">
                                    <span class="pos-item-name">Phone Case × 5</span>
                                    <span class="pos-item-price">$25.00</span>
                                </div>
                                <div class="pos-item">
                                    <span class="pos-item-name">Screen Protector × 3</span>
                                    <span class="pos-item-price">$18.00</span>
                                </div>
                                <div class="pos-item" style="border:none;">
                                    <span style="color:var(--brand-orange);font-weight:600;font-size:0.8rem;">Discount 5%</span>
                                    <span style="color:var(--brand-orange);font-weight:600;font-size:0.8rem;">-$21.15</span>
                                </div>
                            </div>

                            <div class="pos-payment-methods">
                                <div class="payment-badge active">
                                    <i class="fas fa-mobile-alt"></i> M-Pesa
                                </div>
                                <div class="payment-badge">
                                    <i class="fas fa-credit-card"></i> Card
                                </div>
                                <div class="payment-badge">
                                    <i class="fas fa-money-bill"></i> Cash
                                </div>
                                <div class="payment-badge">
                                    <i class="fas fa-university"></i> Bank
                                </div>
                            </div>

                            <button class="pos-checkout-btn">
                                <i class="fas fa-bolt"></i> Lightning Checkout
                            </button>
                        </div>
                    </div>

                    <div class="floating-tag tag-2">
                        <div class="tag-icon" style="background:rgba(255,209,102,0.15); color:var(--brand-accent);">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div>
                            <div style="font-size:0.75rem;color:#E2EAF4;font-weight:600;">Multi-Language Active</div>
                            <div style="font-size:0.7rem;color:var(--text-muted-custom);">EN · FR · ES · SW</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TRUST BAR -->
<div class="trust-bar">
    <div class="container">
        <div class="row g-3">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="trust-item"><i class="fas fa-shield-alt"></i> Bank-grade Security</div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="trust-item"><i class="fas fa-wifi-slash"></i> Works Offline</div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="trust-item"><i class="fas fa-globe-africa"></i> East Africa Ready</div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="trust-item"><i class="fas fa-bolt"></i> 3-Second Checkout</div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="trust-item"><i class="fas fa-headset"></i> 24/7 Support</div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="trust-item"><i class="fas fa-sync-alt"></i> Free Updates</div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════
     FEATURES
═══════════════════════════════════════ -->
<section id="features" class="features-section">
    <div class="container">
        <div class="text-center mb-5 animate-on-scroll">
            <span class="section-label">Powerful Features</span>
            <h2 class="section-title">Everything Your Business Needs</h2>
            <p class="section-sub mx-auto">From solo shop owners in Kampala to enterprise chains in Nairobi — STARPOSS scales with you.</p>
        </div>

        <div class="row g-4">
            <!-- Feature 1 -->
            <div class="col-sm-6 col-lg-4 animate-on-scroll delay-1">
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(255,107,44,0.12); color:var(--brand-orange);">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h4>Lightning-Fast Checkout</h4>
                    <p>Close sales in under 3 seconds with smart barcode scanning, quick product search, and one-tap payment selection. No lag, ever.</p>
                </div>
            </div>
            <!-- Feature 2 -->
            <div class="col-sm-6 col-lg-4 animate-on-scroll delay-2">
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(6,214,160,0.12); color:var(--brand-green);">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h4>Smart Inventory Management</h4>
                    <p>Real-time stock tracking, auto low-stock alerts, batch management, and supplier integration keep your shelves perfectly stocked.</p>
                </div>
            </div>
            <!-- Feature 3 -->
            <div class="col-sm-6 col-lg-4 animate-on-scroll delay-3">
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(255,209,102,0.12); color:var(--brand-accent);">
                        <i class="fas fa-coins"></i>
                    </div>
                    <h4>Multi-Currency & Payments</h4>
                    <p>Accept USD, KES, UGX, TZS, EUR and more. Support for Cash, M-Pesa, Airtel Money, Card, Bank Transfer — all in one terminal.</p>
                </div>
            </div>
            <!-- Feature 4 -->
            <div class="col-sm-6 col-lg-4 animate-on-scroll delay-1">
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(17,138,178,0.12); color:var(--brand-blue);">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h4>Multi-Language Support</h4>
                    <p>Interface available in English, French, Spanish, Swahili and more. Your staff works in their language, your business speaks every language.</p>
                </div>
            </div>
            <!-- Feature 5 -->
            <div class="col-sm-6 col-lg-4 animate-on-scroll delay-2">
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(255,107,44,0.12); color:var(--brand-orange);">
                        <i class="fas fa-store-alt"></i>
                    </div>
                    <h4>Multi-Location Management</h4>
                    <p>Control unlimited branches, warehouses, and departments from one dashboard. Real-time inter-branch stock transfers included.</p>
                </div>
            </div>
            <!-- Feature 6 -->
            <div class="col-sm-6 col-lg-4 animate-on-scroll delay-3">
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(6,214,160,0.12); color:var(--brand-green);">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <h4>Complete Accounting</h4>
                    <p>Full double-entry accounting: General Ledger, Chart of Accounts, Trial Balance, Balance Sheet, Income Statement — no separate software needed.</p>
                </div>
            </div>
            <!-- Feature 7 -->
            <div class="col-sm-6 col-lg-4 animate-on-scroll delay-1">
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(255,209,102,0.12); color:var(--brand-accent);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4>Advanced Analytics & Reports</h4>
                    <p>Visual dashboards, custom date-range reports, top-product analysis, profit margins, and exportable financials for informed decisions.</p>
                </div>
            </div>
            <!-- Feature 8 -->
            <div class="col-sm-6 col-lg-4 animate-on-scroll delay-2">
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(17,138,178,0.12); color:var(--brand-blue);">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h4>HR & Employee Management</h4>
                    <p>Staff profiles, role-based access, shift scheduling, attendance tracking, commission calculation, and payroll management in one place.</p>
                </div>
            </div>
            <!-- Feature 9 -->
            <div class="col-sm-6 col-lg-4 animate-on-scroll delay-3">
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(255,107,44,0.12); color:var(--brand-orange);">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <h4>Promotions, Taxes & Discounts</h4>
                    <p>Flexible tax rates per country, discount campaigns, tiered pricing, loyalty programs, and promotional codes to drive sales legally and strategically.</p>
                </div>
            </div>
            <!-- Feature 10 -->
            <div class="col-sm-6 col-lg-4 animate-on-scroll delay-1">
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(6,214,160,0.12); color:var(--brand-green);">
                        <i class="fas fa-truck-loading"></i>
                    </div>
                    <h4>Purchases & Supplier Portal</h4>
                    <p>Issue purchase orders, track supplier invoices, manage balances, and receive stock directly to any location with automated accounting entries.</p>
                </div>
            </div>
            <!-- Feature 11 -->
            <div class="col-sm-6 col-lg-4 animate-on-scroll delay-2">
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(255,209,102,0.12); color:var(--brand-accent);">
                        <i class="fas fa-palette"></i>
                    </div>
                    <h4>Custom Branding</h4>
                    <p>White-label receipts, custom logo, business colors, and branded reports that represent your identity to every customer and stakeholder.</p>
                </div>
            </div>
            <!-- Feature 12 -->
            <div class="col-sm-6 col-lg-4 animate-on-scroll delay-3">
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(17,138,178,0.12); color:var(--brand-blue);">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <h4>Offline Mode</h4>
                    <p>Keep selling when the internet goes down. All transactions are queued and auto-synced the moment connectivity resumes. Perfect for Uganda's network reality.</p>
                </div>
            </div>
        </div>

        <!-- Hotel Coming Soon -->
        <div class="row mt-5 animate-on-scroll">
            <div class="col-12">
                <div class="hotel-card">
                    <div class="coming-soon-badge">
                        <i class="fas fa-clock"></i> Coming Soon
                    </div>
                    <h3 style="font-family:'Sora',sans-serif;font-weight:800;font-size:1.8rem;color:#E2EAF4;margin-bottom:0.75rem;">
                        🏨 Hotel Management Suite
                    </h3>
                    <p style="color:var(--text-muted-custom);max-width:600px;margin:0 auto 1.5rem;font-size:0.95rem;">
                        Front Desk, Housekeeping, Room Management, Reservations, Guest History, Night Audit, Channel Manager, and Booking Engine — fully integrated with your POS.
                    </p>
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <span class="lang-badge"><i class="fas fa-concierge-bell"></i> Front Desk</span>
                        <span class="lang-badge"><i class="fas fa-bed"></i> Reservations</span>
                        <span class="lang-badge"><i class="fas fa-calendar-alt"></i> Events & Banqueting</span>
                        <span class="lang-badge"><i class="fas fa-broom"></i> Housekeeping</span>
                        <span class="lang-badge"><i class="fas fa-user-friends"></i> Guest Management</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════
     GLOBAL REACH
═══════════════════════════════════════ -->
<section id="reach" class="reach-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 animate-on-scroll">
                <span class="section-label">Global Presence</span>
                <h2 class="section-title">Built in East Africa.<br>Deployed Worldwide.</h2>
                <p class="section-sub mb-4">
                    STARPOSS was engineered in Uganda to handle real-world African business challenges — unreliable internet, multiple currencies, and diverse payment methods. Then we made it work everywhere else too.
                </p>

                <div class="mb-4">
                    <p style="font-size:0.8rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:var(--brand-orange);margin-bottom:0.75rem;">East Africa Focus</p>
                    <span class="country-chip">🇺🇬 Uganda</span>
                    <span class="country-chip">🇰🇪 Kenya</span>
                    <span class="country-chip">🇹🇿 Tanzania</span>
                    <span class="country-chip">🇷🇼 Rwanda</span>
                    <span class="country-chip">🇧🇮 Burundi</span>
                    <span class="country-chip">🇸🇸 South Sudan</span>
                    <span class="country-chip">🇪🇹 Ethiopia</span>
                </div>

                <div class="mb-4">
                    <p style="font-size:0.8rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:var(--brand-orange);margin-bottom:0.75rem;">Also Supported Globally</p>
                    <span class="country-chip">🇬🇧 United Kingdom</span>
                    <span class="country-chip">🇫🇷 France</span>
                    <span class="country-chip">🇺🇸 USA</span>
                    <span class="country-chip">🇦🇪 UAE</span>
                    <span class="country-chip">🇮🇳 India</span>
                    <span class="country-chip">🇿🇦 South Africa</span>
                    <span class="country-chip">🌍 + More</span>
                </div>

                <div>
                    <p style="font-size:0.8rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:var(--brand-orange);margin-bottom:0.75rem;">Interface Languages</p>
                    <span class="lang-badge"><i class="fas fa-language"></i> English</span>
                    <span class="lang-badge"><i class="fas fa-language"></i> Français</span>
                    <span class="lang-badge"><i class="fas fa-language"></i> Español</span>
                    <span class="lang-badge"><i class="fas fa-language"></i> Kiswahili</span>
                    <span class="lang-badge"><i class="fas fa-language"></i> + Adding More</span>
                </div>
            </div>

            <div class="col-lg-6 animate-on-scroll delay-2">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="feature-card text-center">
                            <div style="font-size:2.5rem;font-family:'Sora',sans-serif;font-weight:800;color:var(--brand-orange);">30+</div>
                            <div style="font-size:0.85rem;color:var(--text-muted-custom);">Countries Active</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="feature-card text-center">
                            <div style="font-size:2.5rem;font-family:'Sora',sans-serif;font-weight:800;color:var(--brand-green);">15+</div>
                            <div style="font-size:0.85rem;color:var(--text-muted-custom);">Currencies Supported</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="feature-card text-center">
                            <div style="font-size:2.5rem;font-family:'Sora',sans-serif;font-weight:800;color:var(--brand-accent);">4+</div>
                            <div style="font-size:0.85rem;color:var(--text-muted-custom);">Languages Available</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="feature-card text-center">
                            <div style="font-size:2.5rem;font-family:'Sora',sans-serif;font-weight:800;color:var(--brand-blue);">10+</div>
                            <div style="font-size:0.85rem;color:var(--text-muted-custom);">Payment Methods</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════
     PRICING
═══════════════════════════════════════ -->
<section id="pricing" class="pricing-section">
    <div class="container">
        <div class="text-center mb-2 animate-on-scroll">
            <span class="section-label">Transparent Pricing</span>
            <h2 class="section-title">Plans That Grow With You</h2>
            <p class="section-sub mx-auto">No hidden fees. Cancel anytime. All plans include a free trial period.</p>
        </div>

        <div class="text-center mt-4 mb-2 animate-on-scroll">
            <div class="billing-tabs" id="billingTabs">
                <button class="billing-tab active" data-tab="onetime">One-Time</button>
                <button class="billing-tab" data-tab="monthly">Monthly</button>
                <button class="billing-tab" data-tab="yearly">Yearly <span style="background:rgba(6,214,160,0.2);color:var(--brand-green);padding:1px 6px;border-radius:4px;font-size:0.7rem;">Save 15%</span></button>
            </div>
        </div>

        <!-- Pricing Plans will be rendered by JS from data -->
        <div id="pricingContainer" class="row g-4 mt-2"></div>

        <p class="text-center mt-4 animate-on-scroll" style="font-size:0.875rem;color:var(--text-muted-custom);">
            All plans include free updates and basic email support. Need a custom enterprise solution?
            <a href="#contact" style="color:var(--brand-orange);">Contact our team</a>
        </p>
    </div>
</section>

<!-- ═══════════════════════════════════════
     TESTIMONIALS
═══════════════════════════════════════ -->
<section id="testimonials" class="testimonials-section">
    <div class="container">
        <div class="text-center mb-5 animate-on-scroll">
            <span class="section-label">Customer Stories</span>
            <h2 class="section-title">Businesses Love STARPOSS</h2>
            <p class="section-sub mx-auto">From Kampala markets to Nairobi chains — here's what our customers say.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4 animate-on-scroll delay-1">
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <p class="testimonial-text">"We switched from a basic cashbook to STARPOSS and in 3 months our stock losses dropped by 40%. The inventory alerts alone are worth every penny."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar" style="background:linear-gradient(135deg,#FF6B2C,#E8541A);">AO</div>
                        <div>
                            <div class="author-name">Akello Olivia</div>
                            <div class="author-role">Owner, Akello General Store — Kampala, Uganda</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 animate-on-scroll delay-2">
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <p class="testimonial-text">"Running 4 restaurant branches in Nairobi used to mean endless spreadsheets. STARPOSS gives me one live dashboard for all locations. Game changer."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar" style="background:linear-gradient(135deg,#118AB2,#06D6A0);">CK</div>
                        <div>
                            <div class="author-name">Chidi Kamau</div>
                            <div class="author-role">Director, Savanna Eats — Nairobi, Kenya</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 animate-on-scroll delay-3">
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <p class="testimonial-text">"The M-Pesa and Airtel Money integration is seamless. Our customers pay how they want and everything reconciles automatically. Perfect for Tanzania."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar" style="background:linear-gradient(135deg,#FFD166,#FF6B2C);">MA</div>
                        <div>
                            <div class="author-name">Mohammed Ally</div>
                            <div class="author-role">Manager, TechHub Electronics — Dar es Salaam, Tanzania</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 animate-on-scroll delay-1">
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <p class="testimonial-text">"We're a French-speaking pharmacy in Burundi. The French interface and multi-currency made STARPOSS the only POS that actually works for us here."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar" style="background:linear-gradient(135deg,#06D6A0,#118AB2);">JN</div>
                        <div>
                            <div class="author-name">Jean Niyongabo</div>
                            <div class="author-role">Pharmacist — Bujumbura, Burundi</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 animate-on-scroll delay-2">
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <p class="testimonial-text">"The accounting module replaced our separate software entirely. Trial balance, income statement — all auto-generated from sales. Accountant approved."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar" style="background:linear-gradient(135deg,#FF6B2C,#FFD166);">RM</div>
                        <div>
                            <div class="author-name">Rachel Mugisha</div>
                            <div class="author-role">CFO, Mugisha Group — Kigali, Rwanda</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 animate-on-scroll delay-3">
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <p class="testimonial-text">"We were skeptical about moving to cloud POS but the offline mode convinced us. Our shop in Gulu has patchy internet — STARPOSS never misses a sale."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar" style="background:linear-gradient(135deg,#118AB2,#1A2535);">PO</div>
                        <div>
                            <div class="author-name">Patrick Okello</div>
                            <div class="author-role">Retailer — Gulu, Northern Uganda</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════
     FAQ
═══════════════════════════════════════ -->
<section id="faq" class="faq-section">
    <div class="container">
        <div class="text-center mb-5 animate-on-scroll">
            <span class="section-label">FAQ</span>
            <h2 class="section-title">Got Questions? We Have Answers.</h2>
            <p class="section-sub mx-auto">Everything you need to know before getting started with STARPOSS.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div id="faqList"></div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════
     CTA
═══════════════════════════════════════ -->
<section class="cta-section" id="contact">
    <div class="container position-relative">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div style="font-size:0.8rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.7);margin-bottom:0.75rem;">Ready to Get Started?</div>
                <h2 style="font-family:'Sora',sans-serif;font-weight:800;font-size:clamp(1.8rem,3vw,2.8rem);color:#fff;margin-bottom:1rem;">Transform Your Business with STARPOSS Today</h2>
                <p style="color:rgba(255,255,255,0.8);font-size:1rem;margin:0;">Join 5,000+ businesses across Africa and the world already running smarter with STARPOSS.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="#pricing" class="btn btn-light btn-lg px-4 py-3 fw-bold" style="border-radius:12px;font-family:'Sora',sans-serif;">
                    <i class="fas fa-rocket me-2"></i> Start Free Trial
                </a>
            </div>
        </div>
    </div>
</section>

