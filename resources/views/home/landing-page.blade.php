@extends('home.layout')

@section('title', 'STARDENA SUITE – Complete Business Management Platform | POS, Inventory, Manufacturing, HR')
@section('description', 'STARDENA SUITE is the all-in-one business management platform with POS, inventory management, manufacturing, HR & payroll, multi-currency, and offline mode. Trusted by 5,000+ businesses in 30+ countries.')
@section('og_title', 'STARDENA SUITE – All-in-One Business Management & POS Platform')
@section('og_description', 'Manage your entire business with STARDENA SUITE: POS, inventory, manufacturing, HR, payroll, multi-currency, and offline mode. Used by 5,000+ businesses worldwide.')
@section('twitter_title', 'STARDENA SUITE – POS, Inventory, Manufacturing, HR & Payroll')
@section('twitter_description', 'The complete business management platform with POS, inventory, manufacturing, HR, payroll, and offline mode. Start your free trial today.')

@section('content')

<!-- Hero Section -->
<section id="hero" class="hero section accent-background">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-6 order-2 order-lg-1 d-flex flex-column justify-content-center">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge" style="background: #fb7339; color: #fff; padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.7rem; font-weight: 600; letter-spacing: 0.5px;">
                        <i class="bi bi-rocket-takeoff me-1"></i> INSIGHTS BY AI
                    </span>
                    <span class="badge" style="background: rgba(255,255,255,0.1); color: #fff; padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.7rem; font-weight: 500;">
                        <i class="bi bi-globe2 me-1"></i> 4+ COUNTRIES
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
                    <a href="#contact" class="btn btn-danger px-5 py-3 rounded-pill fw-bold" style="background: #fb7339; border: none; box-shadow: 0 8px 25px rgba(251, 115, 57, 0.35);">
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
                    <span data-purecounter-start="0" data-purecounter-end="200" data-purecounter-duration="2" class="purecounter"></span>
                    <p>Active Businesses</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-item text-center w-100 h-100">
                    <span data-purecounter-start="0" data-purecounter-end="5" data-purecounter-duration="2" class="purecounter"></span>
                    <p>Countries</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-item text-center w-100 h-100">
                    <span data-purecounter-start="0" data-purecounter-end="15" data-purecounter-duration="2" class="purecounter"></span>
                    <p>Currencies Supported (Flexible)</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-item text-center w-100 h-100">
                    <span data-purecounter-start="0" data-purecounter-end="4" data-purecounter-duration="2" class="purecounter"></span>
                    <p>Languages Available (Flexible)</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services / Features Section -->
<section id="services" class="services section">
    <div class="container section-title" data-aos="fade-up">
        <h2>Complete Business Management Platform</h2>
        <p>Everything you need to run your business from raw materials to finished sales</p>
    </div>

    <div class="container">
        
        <!-- ─── MANUFACTURING & PRODUCTION ─── -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="fw-bold" style="color: #fb7339; border-bottom: 2px solid rgba(251, 115, 57, 0.2); padding-bottom: 0.5rem;">
                    <i class="bi bi-building-factory me-2"></i> Manufacturing & Production
                </h3>
            </div>
        </div>
        <div class="row gy-4 mb-5">
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-box-seam"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Raw Materials Management</a></h4>
                        <p class="description">Track raw material inventory, usage, reorder levels, and supplier management.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="150">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-diagram-3"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Bill of Materials (BOM)</a></h4>
                        <p class="description">Multi-level product structures, component management, and material requirements planning.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="200">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-calculator"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Production Costing</a></h4>
                        <p class="description">Accurate cost tracking with labor, overhead allocation, and real-time cost analysis.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="250">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-box"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Finished Goods Tracking</a></h4>
                        <p class="description">Track completed products, production yield, and quality assurance metrics.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── INVENTORY MANAGEMENT ─── -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="fw-bold" style="color: #06D6A0; border-bottom: 2px solid rgba(6, 214, 160, 0.2); padding-bottom: 0.5rem;">
                    <i class="bi bi-boxes me-2"></i> Advanced Inventory Management
                </h3>
            </div>
        </div>
        <div class="row gy-4 mb-5">
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-bucket"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Batch Sales & Tracking</a></h4>
                        <p class="description">Track inventory by batch numbers, expiry dates, and full traceability for recalls.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="150">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-qr-code"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Serial Number Tracking</a></h4>
                        <p class="description">Individual tracking of high-value items with unique serials and lifecycle history.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="200">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-cup-hot"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Recipes & Formulations</a></h4>
                        <p class="description">Create and manage product recipes, ingredient lists, and production formulas.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="250">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-arrows-exchange"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Stock Movement & Transfers</a></h4>
                        <p class="description">Complete visibility of stock movement with audit trail. Transfer stock between locations and departments.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── POS & ORDER MANAGEMENT ─── -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="fw-bold" style="color: #FFD166; border-bottom: 2px solid rgba(255, 209, 102, 0.2); padding-bottom: 0.5rem;">
                    <i class="bi bi-cart-check me-2"></i> POS & Order Management
                </h3>
            </div>
        </div>
        <div class="row gy-4 mb-5">
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-lightning-charge"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Lightning-Fast POS</a></h4>
                        <p class="description">3-second checkout with barcode scanning, one-tap payments, and intelligent product search.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="150">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-pause-circle"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Pause & Resume Orders</a></h4>
                        <p class="description">Pause a pending order, save it, and resume exactly where the customer left off — never lose a sale.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="200">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-list-check"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Order Management</a></h4>
                        <p class="description">Complete order lifecycle management from creation to fulfillment, with real-time status tracking.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="250">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-receipt"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Quotations & Invoicing</a></h4>
                        <p class="description">Generate professional quotes, convert to invoices, track payments, and automated billing reminders.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── PURCHASING & SUPPLIERS ─── -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="fw-bold" style="color: #118AB2; border-bottom: 2px solid rgba(17, 138, 178, 0.2); padding-bottom: 0.5rem;">
                    <i class="bi bi-truck me-2"></i> Purchasing & Supplier Management
                </h3>
            </div>
        </div>
        <div class="row gy-4 mb-5">
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-file-earmark-text"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Purchase Orders</a></h4>
                        <p class="description">Create and manage purchase orders, track order status, and receive stock with automated accounting.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="150">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-people"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Supplier Management</a></h4>
                        <p class="description">Manage supplier profiles, track supplier invoices, balances, and purchase history.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="200">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-receipt-cutoff"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Purchase Receipts</a></h4>
                        <p class="description">Record goods received, quality checks, and update inventory automatically with full traceability.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="250">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-cash-stack"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Supplier Payments</a></h4>
                        <p class="description">Track supplier payments, manage credit limits, and reconcile supplier accounts.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── HUMAN RESOURCE MANAGEMENT ─── -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="fw-bold" style="color: #FF6B8A; border-bottom: 2px solid rgba(255, 107, 138, 0.2); padding-bottom: 0.5rem;">
                    <i class="bi bi-people-circle me-2"></i> Human Resource Management
                </h3>
            </div>
        </div>
        <div class="row gy-4 mb-5">
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-person-badge"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Employee Management</a></h4>
                        <p class="description">Staff profiles, document management, role assignments, and performance tracking.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="150">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-cash"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Payroll Management</a></h4>
                        <p class="description">Automated payroll processing with deductions, benefits, and direct deposit or cash payments.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="200">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-wallet2"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Advances & Loans</a></h4>
                        <p class="description">Manage employee advances, loans, and deductions with automated repayment tracking.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="250">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-calendar-week"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Leave & Attendance</a></h4>
                        <p class="description">Manage leave requests, attendance tracking, shift scheduling, and overtime calculations.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── EXPENSES & FINANCIALS ─── -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="fw-bold" style="color: #F1416C; border-bottom: 2px solid rgba(241, 65, 108, 0.2); padding-bottom: 0.5rem;">
                    <i class="bi bi-wallet me-2"></i> Expenses & Financial Management
                </h3>
            </div>
        </div>
        <div class="row gy-4 mb-5">
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-receipt"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Expense Tracking</a></h4>
                        <p class="description">Track all business expenses, categorize by type, and attach receipts for audit trails.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="150">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-bar-chart-line"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Expense Reports</a></h4>
                        <p class="description">Visual expense reports, budget tracking, and cost analysis for better financial decisions.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="200">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-diagram-2"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Chart of Accounts</a></h4>
                        <p class="description">Full double-entry accounting with customizable chart of accounts and general ledger.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="250">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-file-spreadsheet"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Financial Reports</a></h4>
                        <p class="description">Income statements, balance sheets, trial balance, and customizable financial reports.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── SYSTEM SETTINGS & CONFIGURATION ─── -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="fw-bold" style="color: #7239EA; border-bottom: 2px solid rgba(114, 57, 234, 0.2); padding-bottom: 0.5rem;">
                    <i class="bi bi-gear me-2"></i> System Settings & Configuration
                </h3>
            </div>
        </div>
        <div class="row gy-4 mb-5">
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-person-lock"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Roles & Permissions</a></h4>
                        <p class="description">Customizable user roles with granular permissions — Admin, Manager, Cashier, Viewer.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="150">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-palette"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Custom Branding</a></h4>
                        <p class="description">White-label receipts, custom logo, business colors, and branded reports.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="200">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-sliders2"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">General Settings</a></h4>
                        <p class="description">Business preferences, currency settings, language options, and system configurations.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="250">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-shield-lock"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Security & Audit Logs</a></h4>
                        <p class="description">Complete audit trails, user activity logs, and security monitoring for compliance.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── TAXES & PROMOTIONS ─── -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="fw-bold" style="color: #FFC700; border-bottom: 2px solid rgba(255, 199, 0, 0.2); padding-bottom: 0.5rem;">
                    <i class="bi bi-percent me-2"></i> Taxes & Promotions
                </h3>
            </div>
        </div>
        <div class="row gy-4">
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-receipt-tax"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Tax Management</a></h4>
                        <p class="description">Multiple tax rates, tax categories, VAT/GST handling, and automated tax calculations.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="150">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-gift"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Promotions & Discounts</a></h4>
                        <p class="description">Create promotional campaigns, discount codes, loyalty programs, and seasonal offers.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="200">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-tags"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Loyalty Programs</a></h4>
                        <p class="description">Customer loyalty points, rewards, and personalized offers to drive repeat business.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6" data-aos="fade-up" data-aos-delay="250">
                <div class="service-item d-flex">
                    <div class="icon flex-shrink-0"><i class="bi bi-currency-exchange"></i></div>
                    <div>
                        <h4 class="title"><a href="#" class="stretched-link">Multi-Currency</a></h4>
                        <p class="description">Accept payments in multiple currencies with automatic conversion and reporting.</p>
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
        <h2>Choose Your Plan</h2>
        <p>Flexible pricing for every business — from startups to enterprises</p>
    </div>

    <div class="container">
        <!-- Billing Tabs -->
        <div class="text-center mt-4 mb-5" data-aos="fade-up">
            <div class="billing-tabs" id="billingTabs">
                <button class="billing-tab active" data-tab="monthly">Monthly</button>
                <button class="billing-tab" data-tab="yearly">Yearly <span style="background: rgba(6,214,160,0.2); color: #06D6A0; padding: 2px 8px; border-radius: 4px; font-size: 0.65rem; font-weight: 700;">Save 20%</span></button>
                <button class="billing-tab" data-tab="onetime">One-Time <span style="background: rgba(251, 115, 57, 0.15); color: #14427a; padding: 2px 8px; border-radius: 4px; font-size: 0.65rem; font-weight: 700;">Lifetime</span></button>
            </div>
        </div>

        <!-- Pricing Plans Container -->
        <div id="pricingContainer" class="row g-4"></div>

        <p class="text-center mt-4" style="color: var(--text-muted-custom); font-size: 0.85rem;">
            All plans include free updates and basic email support. 
            <a href="#contact" style="color: #fb7339; text-decoration: none; font-weight: 500;">Contact our team</a> for custom enterprise solutions.
        </p>
    </div>

    <!-- ═══════════════════════════════════════
        CONTACT / GET STARTED MODAL
    ═══════════════════════════════════════ -->
    <div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="background: var(--brand-card); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; color: #E2EAF4;">
                <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.06); padding: 1.5rem 2rem;">
                    <h5 class="modal-title d-flex align-items-center gap-2" style="font-weight: 700;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(251, 115, 57, 0.12); border-radius: 50%; color: #fb7339;">
                            <i class="bi bi-envelope-paper"></i>
                        </span>
                        Get Started with STARDENA SUITE
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(1); opacity: 0.6;"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;">
                    <p style="font-size: 0.9rem; color: #8899AA; margin-bottom: 1.5rem; line-height: 1.7;">
                        Tell us about your business and our team will get in touch within 24 hours.
                    </p>
                    <div id="modalFormArea">
                        <form id="contactForm" onsubmit="sendInquiry(event)">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: #B0C4D8;">Your Name <span style="color: #fb7339;">*</span></label>
                                    <input type="text" class="form-control" id="contactName" placeholder="e.g. John Mukasa" required style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; color: #E2EAF4; padding: 0.75rem 1rem;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: #B0C4D8;">Email Address <span style="color: #fb7339;">*</span></label>
                                    <input type="email" class="form-control" id="contactEmail" placeholder="you@company.com" required style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; color: #E2EAF4; padding: 0.75rem 1rem;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: #B0C4D8;">Phone / WhatsApp</label>
                                    <input type="tel" class="form-control" id="contactPhone" placeholder="+256 700 000 000" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; color: #E2EAF4; padding: 0.75rem 1rem;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: #B0C4D8;">Business Name</label>
                                    <input type="text" class="form-control" id="contactBusinessName" placeholder="Your business name" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; color: #E2EAF4; padding: 0.75rem 1rem;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: #B0C4D8;">Business Type</label>
                                    <select class="form-select" id="contactBusiness" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; color: #E2EAF4; padding: 0.75rem 1rem;">
                                        <option value="" style="background: var(--brand-card);">Select your business type</option>
                                        <option value="retail" style="background: var(--brand-card);">Retail Shop</option>
                                        <option value="restaurant" style="background: var(--brand-card);">Restaurant / Café</option>
                                        <option value="supermarket" style="background: var(--brand-card);">Supermarket / Grocery</option>
                                        <option value="hotel" style="background: var(--brand-card);">Hotel / Lodge</option>
                                        <option value="pharmacy" style="background: var(--brand-card);">Pharmacy</option>
                                        <option value="electronics" style="background: var(--brand-card);">Electronics Store</option>
                                        <option value="fashion" style="background: var(--brand-card);">Clothing / Fashion</option>
                                        <option value="hardware" style="background: var(--brand-card);">Hardware Store</option>
                                        <option value="manufacturing" style="background: var(--brand-card);">Manufacturing</option>
                                        <option value="school" style="background: var(--brand-card);">School / Institution</option>
                                        <option value="healthcare" style="background: var(--brand-card);">Healthcare</option>
                                        <option value="other" style="background: var(--brand-card);">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: #B0C4D8;">Interested Plan</label>
                                    <select class="form-select" id="contactPlan" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; color: #E2EAF4; padding: 0.75rem 1rem;">
                                        <option value="" style="background: var(--brand-card);">Select a plan</option>
                                        <option value="free_trial" style="background: var(--brand-card);">Free Trial</option>
                                        <option value="starter" style="background: var(--brand-card);">Starter Plan</option>
                                        <option value="business" style="background: var(--brand-card);">Business Plan</option>
                                        <option value="enterprise" style="background: var(--brand-card);">Enterprise Plan</option>
                                        <option value="lifetime" style="background: var(--brand-card);">Lifetime License</option>
                                        <option value="not_sure" style="background: var(--brand-card);">Not sure — need advice</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: #B0C4D8;">Message</label>
                                    <textarea class="form-control" id="contactMessage" rows="4" placeholder="Tell us more about your needs..." style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; color: #E2EAF4; padding: 0.75rem 1rem;"></textarea>
                                </div>
                            </div>

                            <div id="modalAlert" class="d-none mt-3"></div>

                            <div class="mt-4 d-flex gap-3">
                                <button type="submit" class="btn-plan btn-plan-primary" id="sendInquiryBtn" 
                                    style="flex: 1; width: auto; padding: 0.8rem 2rem; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                    <i class="bi bi-send"></i> Send Inquiry
                                </button>
                                <button type="button" class="btn-plan btn-plan-outline" data-bs-dismiss="modal" 
                                    style="flex: 1; width: auto; padding: 0.8rem 2rem; font-size: 0.95rem; display: flex; align-items: center; justify-content: center;">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
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