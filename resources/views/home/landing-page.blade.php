@extends('home.layout')
@section('content')
    <!-- Header Section -->
    <header class="sticky-top bg-white shadow-sm">
        <nav class="navbar navbar-expand-lg navbar-light py-3">
            <div class="container">
                <a class="navbar-brand" href="/">
                    <svg xmlns="http://www.w3.org/2000/svg" width="200" height="60" viewBox="0 0 200 60">
                        <defs>
                            <linearGradient id="lael-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#0046B8"/>
                                <stop offset="100%" stop-color="#007BFF"/>
                            </linearGradient>
                        </defs>
                        <!-- Gradient blue circle background -->
                        <circle cx="30" cy="30" r="20" fill="url(#lael-grad)" />
                        <!-- Minimal exchange symbol /= in white -->
                        <text x="20" y="38" font-family="Poppins, sans-serif" font-size="24" fill="#FFFFFF" font-weight="700">/=</text>
                        <!-- Product name -->
                        <text x="65" y="37" font-family="Poppins, sans-serif" font-size="26" fill="url(#lael-grad)" font-weight="600">LAEL</text>
                    </svg>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#features">Features</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#pricing">Pricing</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#testimonials">Testimonials</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#faq">FAQ</a>
                        </li>
                        <li class="nav-item ms-lg-3">
                            <a class="btn btn-outline-primary" href="#pricing">Get Started</a>
                        </li>
                        <li class="nav-item ms-lg-3">
                            <a class="btn btn-primary" href="{{ route('login') }}">Demo</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title">Streamline Your Business with LAEL POS</h1>
                    <p class="hero-subtitle">The all-in-one point of sale solution designed for all businesses. Manage sales, inventory, employees, accounting and reporting in one powerful platform.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="#pricing" class="btn btn-primary-custom">Get Started Today</a>
                        <a href="#features" class="btn btn-outline-light">Learn More</a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="POS System" class="img-fluid rounded shadow-lg">
                </div>
            </div>
        </div>
    </section>

    <!-- Clients Section -->
    <section class="clients-section">
        <div class="container">
            <h2 class="section-title">Trusted by Businesses Across Africa</h2>
            <p class="section-subtitle">Join thousands of retailers, restaurants, and service providers who rely on LAEL POS</p>
            <div class="row align-items-center justify-content-center">
                <div class="col-6 col-md-3 col-lg-2 text-center mb-4">
                    <img src="https://via.placeholder.com/120x60?text=Client+1" alt="Client 1" class="img-fluid client-logo">
                </div>
                <div class="col-6 col-md-3 col-lg-2 text-center mb-4">
                    <img src="https://via.placeholder.com/120x60?text=Client+2" alt="Client 2" class="img-fluid client-logo">
                </div>
                <div class="col-6 col-md-3 col-lg-2 text-center mb-4">
                    <img src="https://via.placeholder.com/120x60?text=Client+3" alt="Client 3" class="img-fluid client-logo">
                </div>
                <div class="col-6 col-md-3 col-lg-2 text-center mb-4">
                    <img src="https://via.placeholder.com/120x60?text=Client+4" alt="Client 4" class="img-fluid client-logo">
                </div>
                <div class="col-6 col-md-3 col-lg-2 text-center mb-4">
                    <img src="https://via.placeholder.com/120x60?text=Client+5" alt="Client 5" class="img-fluid client-logo">
                </div>
                <div class="col-6 col-md-3 col-lg-2 text-center mb-4">
                    <img src="https://via.placeholder.com/120x60?text=Client+6" alt="Client 6" class="img-fluid client-logo">
                </div>
            </div>
        </div>
    </section>

    
    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <h2 class="section-title">Powerful Features for Your Business</h2>
            <p class="section-subtitle">Everything you need to manage and grow your business efficiently</p>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-cash-register"></i>
                        </div>
                        <h3 class="feature-title">Selling Interface</h3>
                        <p class="feature-description">Intuitive point of sale with quick product search, barcode scanning, and multiple payment options.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <h3 class="feature-title">Inventory Management</h3>
                        <p class="feature-description">Track stock levels, set low stock alerts, and manage suppliers all in one place.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3 class="feature-title">Detailed Reporting</h3>
                        <p class="feature-description">Comprehensive sales, inventory, and financial reports to help you make data-driven decisions.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="feature-title">Employee Management</h3>
                        <p class="feature-description">Manage staff schedules, track performance, and control access with role-based permissions.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <h3 class="feature-title">Multi-Store Support</h3>
                        <p class="feature-description">Manage multiple locations from a single dashboard with centralized control and reporting.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <h3 class="feature-title">Multi-Currency & Language</h3>
                        <p class="feature-description">Support for multiple currencies and payment gateways and languages to serve diverse customer bases across the world.</p>
                    </div>
                </div>

                <!-- ✅ New Feature: Taxes, Promotions & Discounts -->
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <h3 class="feature-title">Taxes, Promotions & Discounts</h3>
                        <p class="feature-description">Flexible configuration for tax rates, discount campaigns, and promotional pricing to boost sales and compliance.</p>
                    </div>
                </div>

                <!-- ✅ New Feature: Purchases & Suppliers -->
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-truck-loading"></i>
                        </div>
                        <h3 class="feature-title">Purchases & Supplier Management</h3>
                        <p class="feature-description">Easily record purchases, track supplier balances, and manage purchase orders with real-time stock updates.</p>
                    </div>
                </div>

                <!-- Accounting -->
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <h3 class="feature-title">Complete Accounting</h3>
                        <p class="feature-description">Advanced accounting with general ledger, journals, chart of accounts, trial balance, balance sheet, and income statement for full financial control.</p>                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Pricing Section -->
    <section id="pricing" class="pricing-section">
        <div class="container">
            <h2 class="section-title">Simple, Transparent Pricing</h2>
            <p class="section-subtitle">Choose the plan that works best for your business. All plans include our core features.</p>
            
            <!-- Pricing Tabs -->
            <ul class="nav nav-tabs justify-content-center mb-5" id="pricingTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="one-time-tab" data-bs-toggle="tab" data-bs-target="#one-time" type="button" role="tab">One-Time Payment</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly" type="button" role="tab">Monthly</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="yearly-tab" data-bs-toggle="tab" data-bs-target="#yearly" type="button" role="tab">Yearly</button>
                </li>
            </ul>
            
            <div class="tab-content" id="pricingTabsContent">
                <!-- One-Time Payment Tab -->
                <div class="tab-pane fade show active" id="one-time" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="pricing-card">
                                <div class="pricing-header">
                                    <h3 class="pricing-title">Starter</h3>
                                    <div class="pricing-price">$299</div>
                                    <div class="pricing-period">One-time payment</div>
                                    <p class="text-muted">Perfect for small businesses</p>
                                </div>
                                <div class="pricing-features">
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>1 Store Location</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Basic Inventory Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Sales Reporting</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>2 User Accounts</span>
                                    </div>
                                    <div class="pricing-feature disabled">
                                        <i class="fas fa-times"></i>
                                        <span>Supplier Management</span>
                                    </div>
                                    <div class="pricing-feature disabled">
                                        <i class="fas fa-times"></i>
                                        <span>Employee Management</span>
                                    </div>
                                    <div class="pricing-feature disabled">
                                        <i class="fas fa-times"></i>
                                        <span>Multi-Currency</span>
                                    </div>
                                </div>
                                <div class="pricing-footer">
                                    <button class="btn btn-outline-primary w-100">Get Started</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="pricing-card popular">
                                <div class="popular-badge">Most Popular</div>
                                <div class="pricing-header">
                                    <h3 class="pricing-title">Business</h3>
                                    <div class="pricing-price">$699</div>
                                    <div class="pricing-period">One-time payment</div>
                                    <p class="text-muted">Ideal for growing businesses</p>
                                </div>
                                <div class="pricing-features">
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Up to 3 Store Locations</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Advanced Inventory Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Detailed Reporting</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>5 User Accounts</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Supplier Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Employee Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Multi-Currency</span>
                                    </div>
                                </div>
                                <div class="pricing-footer">
                                    <button class="btn btn-primary-custom w-100">Get Started</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="pricing-card">
                                <div class="pricing-header">
                                    <h3 class="pricing-title">Enterprise</h3>
                                    <div class="pricing-price">$1,299</div>
                                    <div class="pricing-period">One-time payment</div>
                                    <p class="text-muted">For large businesses</p>
                                </div>
                                <div class="pricing-features">
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Unlimited Store Locations</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Advanced Inventory Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Advanced Analytics & Reporting</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Unlimited User Accounts</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Supplier Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Advanced Employee Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Multi-Currency & Multi-Language</span>
                                    </div>
                                </div>
                                <div class="pricing-footer">
                                    <button class="btn btn-outline-primary w-100">Get Started</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Monthly Payment Tab -->
                <div class="tab-pane fade" id="monthly" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="pricing-card">
                                <div class="pricing-header">
                                    <h3 class="pricing-title">Starter</h3>
                                    <div class="pricing-price">$29<span class="fs-6">/month</span></div>
                                    <div class="pricing-period">Monthly billing</div>
                                    <p class="text-muted">Perfect for small businesses</p>
                                </div>
                                <div class="pricing-features">
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>1 Store Location</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Basic Inventory Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Sales Reporting</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>2 User Accounts</span>
                                    </div>
                                    <div class="pricing-feature disabled">
                                        <i class="fas fa-times"></i>
                                        <span>Supplier Management</span>
                                    </div>
                                    <div class="pricing-feature disabled">
                                        <i class="fas fa-times"></i>
                                        <span>Employee Management</span>
                                    </div>
                                    <div class="pricing-feature disabled">
                                        <i class="fas fa-times"></i>
                                        <span>Multi-Currency</span>
                                    </div>
                                </div>
                                <div class="pricing-footer">
                                    <button class="btn btn-outline-primary w-100">Get Started</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="pricing-card popular">
                                <div class="popular-badge">Most Popular</div>
                                <div class="pricing-header">
                                    <h3 class="pricing-title">Business</h3>
                                    <div class="pricing-price">$69<span class="fs-6">/month</span></div>
                                    <div class="pricing-period">Monthly billing</div>
                                    <p class="text-muted">Ideal for growing businesses</p>
                                </div>
                                <div class="pricing-features">
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Up to 3 Store Locations</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Advanced Inventory Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Detailed Reporting</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>5 User Accounts</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Supplier Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Employee Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Multi-Currency</span>
                                    </div>
                                </div>
                                <div class="pricing-footer">
                                    <button class="btn btn-primary-custom w-100">Get Started</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="pricing-card">
                                <div class="pricing-header">
                                    <h3 class="pricing-title">Enterprise</h3>
                                    <div class="pricing-price">$129<span class="fs-6">/month</span></div>
                                    <div class="pricing-period">Monthly billing</div>
                                    <p class="text-muted">For large businesses</p>
                                </div>
                                <div class="pricing-features">
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Unlimited Store Locations</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Advanced Inventory Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Advanced Analytics & Reporting</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Unlimited User Accounts</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Supplier Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Advanced Employee Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Multi-Currency & Multi-Language</span>
                                    </div>
                                </div>
                                <div class="pricing-footer">
                                    <button class="btn btn-outline-primary w-100">Get Started</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Yearly Payment Tab -->
                <div class="tab-pane fade" id="yearly" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="pricing-card">
                                <div class="pricing-header">
                                    <h3 class="pricing-title">Starter</h3>
                                    <div class="pricing-price">$299<span class="fs-6">/year</span></div>
                                    <div class="pricing-period">Save 15% with yearly billing</div>
                                    <p class="text-muted">Perfect for small businesses</p>
                                </div>
                                <div class="pricing-features">
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>1 Store Location</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Basic Inventory Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Sales Reporting</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>2 User Accounts</span>
                                    </div>
                                    <div class="pricing-feature disabled">
                                        <i class="fas fa-times"></i>
                                        <span>Supplier Management</span>
                                    </div>
                                    <div class="pricing-feature disabled">
                                        <i class="fas fa-times"></i>
                                        <span>Employee Management</span>
                                    </div>
                                    <div class="pricing-feature disabled">
                                        <i class="fas fa-times"></i>
                                        <span>Multi-Currency</span>
                                    </div>
                                </div>
                                <div class="pricing-footer">
                                    <button class="btn btn-outline-primary w-100">Get Started</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="pricing-card popular">
                                <div class="popular-badge">Most Popular</div>
                                <div class="pricing-header">
                                    <h3 class="pricing-title">Business</h3>
                                    <div class="pricing-price">$699<span class="fs-6">/year</span></div>
                                    <div class="pricing-period">Save 15% with yearly billing</div>
                                    <p class="text-muted">Ideal for growing businesses</p>
                                </div>
                                <div class="pricing-features">
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Up to 3 Store Locations</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Advanced Inventory Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Detailed Reporting</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>5 User Accounts</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Supplier Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Employee Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Multi-Currency</span>
                                    </div>
                                </div>
                                <div class="pricing-footer">
                                    <button class="btn btn-primary-custom w-100">Get Started</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="pricing-card">
                                <div class="pricing-header">
                                    <h3 class="pricing-title">Enterprise</h3>
                                    <div class="pricing-price">$1,299<span class="fs-6">/year</span></div>
                                    <div class="pricing-period">Save 15% with yearly billing</div>
                                    <p class="text-muted">For large businesses</p>
                                </div>
                                <div class="pricing-features">
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Unlimited Store Locations</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Advanced Inventory Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Advanced Analytics & Reporting</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Unlimited User Accounts</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Supplier Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Advanced Employee Management</span>
                                    </div>
                                    <div class="pricing-feature">
                                        <i class="fas fa-check"></i>
                                        <span>Multi-Currency & Multi-Language</span>
                                    </div>
                                </div>
                                <div class="pricing-footer">
                                    <button class="btn btn-outline-primary w-100">Get Started</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <p class="text-muted">All plans include free updates and basic support. Need a custom solution? <a href="#contact">Contact us</a></p>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonial-section">
        <div class="container">
            <h2 class="section-title text-white">What Our Customers Say</h2>
            <p class="section-subtitle text-white-50">Hear from businesses that have transformed their operations with LAEL POS</p>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="testimonial-card">
                        <p class="testimonial-text">"LAEL POS has revolutionized how we manage our retail stores. The inventory management alone has saved us countless hours each week."</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">AO</div>
                            <div>
                                <h5 class="mb-0">Adebayo Ojo</h5>
                                <p class="text-white-50 mb-0">Manager, Fashion Hub</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="testimonial-card">
                        <p class="testimonial-text">"The multi-store feature has been a game-changer for our restaurant chain. We can now monitor all locations in real-time from one dashboard."</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">CK</div>
                            <div>
                                <h5 class="mb-0">Chidinma Kalu</h5>
                                <p class="text-white-50 mb-0">Owner, Taste of Africa Restaurants</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="testimonial-card">
                        <p class="testimonial-text">"As a small business owner, the affordable pricing and robust features of LAEL POS have helped us compete with larger retailers."</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">MA</div>
                            <div>
                                <h5 class="mb-0">Mohammed Abubakar</h5>
                                <p class="text-white-50 mb-0">Owner, City Electronics</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="faq-section">
        <div class="container">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <p class="section-subtitle">Find answers to common questions about LAEL POS</p>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Is LAEL POS suitable for my business type?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes! LAEL POS is designed to work for various business types including retail stores, restaurants, service providers, and more. Our flexible system can be customized to meet your specific needs.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Do I need technical knowledge to use LAEL POS?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Not at all! LAEL POS is designed with user-friendliness in mind. Our intuitive interface makes it easy for anyone to learn, and we provide comprehensive training materials and customer support.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Can I use LAEL POS offline?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, LAEL POS has offline capabilities. You can continue processing sales even when internet connectivity is unstable. Once connection is restored, all data will sync automatically.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    What payment methods does LAEL POS support?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    LAEL POS supports multiple payment methods including cash, card payments, mobile money (across various African providers), bank transfers, and more. We continuously add new payment options based on market needs.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    Is my data secure with LAEL POS?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Absolutely. We take data security seriously. All data is encrypted, and we implement regular backups and security updates to protect your business information.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5" style="background-color: var(--primary-color);">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 text-white">
                    <h2 class="mb-3">Ready to Transform Your Business?</h2>
                    <p class="mb-0">Join thousands of businesses across Africa using LAEL POS to streamline operations and drive growth.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="#pricing" class="btn btn-light btn-lg">Get Started Now</a>
                </div>
            </div>
        </div>
    </section>

@endsection