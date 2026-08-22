@extends('home.layout')

@section('title', 'Documentation - STARDENA SUITE User Guide')
@section('description', 'Complete documentation for STARDENA SUITE POS system. Setup guides, API references, and troubleshooting.')

@section('content')

<!-- Page Header -->
<section class="page-header section accent-background" style="padding: 120px 0 60px;">
    <div class="container" data-aos="fade-up">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 mb-4">
                    <span class="badge" style="background: rgba(251, 115, 57, 0.15); color: #fb7339; padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.7rem; font-weight: 600; letter-spacing: 0.5px;">
                        <i class="bi bi-book me-1"></i> DOCUMENTATION
                    </span>
                </div>
                <h1 class="display-4 fw-bold" style="color: #E2EAF4;">
                    STARDENA <span style="color: #fb7339;">Docs</span>
                </h1>
                <p class="lead" style="color: #8899AA; max-width: 600px; margin: 0 auto;">
                    Everything you need to know to get started with STARDENA SUITE.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Documentation Section -->
<section id="docs" class="docs section">
    <div class="container">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="docs-sidebar" style="position: sticky; top: 100px;">
                    <div class="list-group" style="background: #1A2535; border-radius: 16px; overflow: hidden; border: 1px solid rgba(255,255,255,0.08);">
                        <a href="#getting-started" class="list-group-item list-group-item-action" style="background: transparent; color: #B0C4D8; border-color: rgba(255,255,255,0.06); padding: 0.8rem 1.2rem; transition: all 0.3s ease; font-weight: 500;">
                            <i class="bi bi-rocket-takeoff me-2" style="color: #fb7339;"></i> Getting Started
                        </a>
                        <a href="#products" class="list-group-item list-group-item-action" style="background: transparent; color: #B0C4D8; border-color: rgba(255,255,255,0.06); padding: 0.8rem 1.2rem; transition: all 0.3s ease; font-weight: 500;">
                            <i class="bi bi-box-seam me-2" style="color: #fb7339;"></i> Products & Inventory
                        </a>
                        <a href="#sales" class="list-group-item list-group-item-action" style="background: transparent; color: #B0C4D8; border-color: rgba(255,255,255,0.06); padding: 0.8rem 1.2rem; transition: all 0.3s ease; font-weight: 500;">
                            <i class="bi bi-cart-check me-2" style="color: #fb7339;"></i> Processing Sales
                        </a>
                        <a href="#reports" class="list-group-item list-group-item-action" style="background: transparent; color: #B0C4D8; border-color: rgba(255,255,255,0.06); padding: 0.8rem 1.2rem; transition: all 0.3s ease; font-weight: 500;">
                            <i class="bi bi-graph-up-arrow me-2" style="color: #fb7339;"></i> Reports & Analytics
                        </a>
                        <a href="#offline" class="list-group-item list-group-item-action" style="background: transparent; color: #B0C4D8; border-color: rgba(255,255,255,0.06); padding: 0.8rem 1.2rem; transition: all 0.3s ease; font-weight: 500;">
                            <i class="bi bi-wifi-off me-2" style="color: #fb7339;"></i> Offline Mode
                        </a>
                        <a href="#multi-currency" class="list-group-item list-group-item-action" style="background: transparent; color: #B0C4D8; border-color: rgba(255,255,255,0.06); padding: 0.8rem 1.2rem; transition: all 0.3s ease; font-weight: 500;">
                            <i class="bi bi-currency-exchange me-2" style="color: #fb7339;"></i> Multi-Currency
                        </a>
                        <a href="#payments" class="list-group-item list-group-item-action" style="background: transparent; color: #B0C4D8; border-color: rgba(255,255,255,0.06); padding: 0.8rem 1.2rem; transition: all 0.3s ease; font-weight: 500;">
                            <i class="bi bi-credit-card me-2" style="color: #fb7339;"></i> Payments
                        </a>
                        <a href="#users" class="list-group-item list-group-item-action" style="background: transparent; color: #B0C4D8; border-color: rgba(255,255,255,0.06); padding: 0.8rem 1.2rem; transition: all 0.3s ease; font-weight: 500;">
                            <i class="bi bi-people me-2" style="color: #fb7339;"></i> User Management
                        </a>
                        <a href="#api" class="list-group-item list-group-item-action" style="background: transparent; color: #B0C4D8; border-color: rgba(255,255,255,0.06); padding: 0.8rem 1.2rem; transition: all 0.3s ease; font-weight: 500;">
                            <i class="bi bi-code-square me-2" style="color: #fb7339;"></i> API Reference
                        </a>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="col-lg-9">
                <div class="docs-content">
                    <!-- Getting Started -->
                    <div id="getting-started" class="docs-section" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 2rem; margin-bottom: 2rem; scroll-margin-top: 120px;">
                        <h2 class="h3 fw-bold" style="color: #fb7339; margin-bottom: 1rem;">Getting Started</h2>
                        <p style="color: #8899AA; line-height: 1.7;">Welcome to STARDENA SUITE! This guide will help you set up your store in minutes.</p>
                        <ol style="color: #8899AA; line-height: 1.7; padding-left: 1.2rem;">
                            <li style="margin-bottom: 0.5rem;">Create your account by clicking "Get Started"</li>
                            <li style="margin-bottom: 0.5rem;">Add your store/business details (name, address, currency)</li>
                            <li style="margin-bottom: 0.5rem;">Set up your payment methods (Cash, Mobile Money, Card)</li>
                            <li style="margin-bottom: 0.5rem;">Add your products or import from a spreadsheet</li>
                            <li style="margin-bottom: 0.5rem;">Start selling! Your POS is ready</li>
                        </ol>
                    </div>

                    <!-- Products & Inventory -->
                    <div id="products" class="docs-section" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 2rem; margin-bottom: 2rem; scroll-margin-top: 120px;">
                        <h2 class="h3 fw-bold" style="color: #fb7339; margin-bottom: 1rem;">Products & Inventory</h2>
                        <p style="color: #8899AA; line-height: 1.7;">Manage your entire product catalog from one dashboard.</p>
                        <ul style="color: #8899AA; line-height: 1.7; padding-left: 1.2rem;">
                            <li style="margin-bottom: 0.5rem;">Add products with name, price, SKU, and images</li>
                            <li style="margin-bottom: 0.5rem;">Track stock levels with automatic updates</li>
                            <li style="margin-bottom: 0.5rem;">Set low-stock alerts to never run out</li>
                            <li style="margin-bottom: 0.5rem;">Bulk import/export using CSV files</li>
                            <li style="margin-bottom: 0.5rem;">Manage multiple locations with separate inventory</li>
                            <li style="margin-bottom: 0.5rem;">Create product categories and variants (size, color, etc.)</li>
                        </ul>
                    </div>

                    <!-- Processing Sales -->
                    <div id="sales" class="docs-section" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 2rem; margin-bottom: 2rem; scroll-margin-top: 120px;">
                        <h2 class="h3 fw-bold" style="color: #fb7339; margin-bottom: 1rem;">Processing Sales</h2>
                        <p style="color: #8899AA; line-height: 1.7;">The POS interface is designed for speed and simplicity.</p>
                        <ul style="color: #8899AA; line-height: 1.7; padding-left: 1.2rem;">
                            <li style="margin-bottom: 0.5rem;">Search products by name or barcode</li>
                            <li style="margin-bottom: 0.5rem;">Apply discounts to items or entire cart</li>
                            <li style="margin-bottom: 0.5rem;">Accept multiple payment methods in one transaction</li>
                            <li style="margin-bottom: 0.5rem;">Email or print receipts automatically</li>
                            <li style="margin-bottom: 0.5rem;">Create and manage customer profiles</li>
                            <li style="margin-bottom: 0.5rem;">Process returns and refunds easily</li>
                        </ul>
                    </div>

                    <!-- Reports & Analytics -->
                    <div id="reports" class="docs-section" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 2rem; margin-bottom: 2rem; scroll-margin-top: 120px;">
                        <h2 class="h3 fw-bold" style="color: #fb7339; margin-bottom: 1rem;">Reports & Analytics</h2>
                        <p style="color: #8899AA; line-height: 1.7;">Make data-driven decisions with real-time reports.</p>
                        <ul style="color: #8899AA; line-height: 1.7; padding-left: 1.2rem;">
                            <li style="margin-bottom: 0.5rem;">Daily, weekly, monthly sales summaries</li>
                            <li style="margin-bottom: 0.5rem;">Profit margin analysis by product or category</li>
                            <li style="margin-bottom: 0.5rem;">Inventory valuation reports</li>
                            <li style="margin-bottom: 0.5rem;">Top-selling products and categories</li>
                            <li style="margin-bottom: 0.5rem;">Customer purchase history and insights</li>
                        </ul>
                    </div>

                    <!-- Offline Mode -->
                    <div id="offline" class="docs-section" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 2rem; margin-bottom: 2rem; scroll-margin-top: 120px;">
                        <h2 class="h3 fw-bold" style="color: #fb7339; margin-bottom: 1rem;">Offline Mode</h2>
                        <p style="color: #8899AA; line-height: 1.7;">STARDENA SUITE works even without internet. When offline, all sales are stored locally and sync automatically when connection is restored.</p>
                        <ul style="color: #8899AA; line-height: 1.7; padding-left: 1.2rem;">
                            <li style="margin-bottom: 0.5rem;">Continue selling during internet outages</li>
                            <li style="margin-bottom: 0.5rem;">Automatic sync when back online</li>
                            <li style="margin-bottom: 0.5rem;">No data loss guaranteed</li>
                            <li style="margin-bottom: 0.5rem;">Works on any device</li>
                        </ul>
                    </div>

                    <!-- Multi-Currency -->
                    <div id="multi-currency" class="docs-section" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 2rem; margin-bottom: 2rem; scroll-margin-top: 120px;">
                        <h2 class="h3 fw-bold" style="color: #fb7339; margin-bottom: 1rem;">Multi-Currency</h2>
                        <p style="color: #8899AA; line-height: 1.7;">Set your base currency and accept payments in other currencies with live or fixed exchange rates.</p>
                        <ul style="color: #8899AA; line-height: 1.7; padding-left: 1.2rem;">
                            <li style="margin-bottom: 0.5rem;">Supported currencies: USD, UGX, KES, TZS, RWF, EUR, GBP, and more</li>
                            <li style="margin-bottom: 0.5rem;">Automatic exchange rate updates</li>
                            <li style="margin-bottom: 0.5rem;">Separate cash registers per currency</li>
                            <li style="margin-bottom: 0.5rem;">Clear currency conversion tracking</li>
                        </ul>
                    </div>

                    <!-- Payments -->
                    <div id="payments" class="docs-section" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 2rem; margin-bottom: 2rem; scroll-margin-top: 120px;">
                        <h2 class="h3 fw-bold" style="color: #fb7339; margin-bottom: 1rem;">Payments</h2>
                        <p style="color: #8899AA; line-height: 1.7;">Configure multiple payment methods for your business.</p>
                        <ul style="color: #8899AA; line-height: 1.7; padding-left: 1.2rem;">
                            <li style="margin-bottom: 0.5rem;">Cash payments with change calculation</li>
                            <li style="margin-bottom: 0.5rem;">Mobile Money (M-Pesa, Airtel Money, MTN MoMo)</li>
                            <li style="margin-bottom: 0.5rem;">Credit/Debit cards via integrated POS</li>
                            <li style="margin-bottom: 0.5rem;">Bank transfers and cheques</li>
                            <li style="margin-bottom: 0.5rem;">Split payments (combine multiple methods)</li>
                        </ul>
                    </div>

                    <!-- User Management -->
                    <div id="users" class="docs-section" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 2rem; margin-bottom: 2rem; scroll-margin-top: 120px;">
                        <h2 class="h3 fw-bold" style="color: #fb7339; margin-bottom: 1rem;">User Management</h2>
                        <p style="color: #8899AA; line-height: 1.7;">Add staff members and control their access.</p>
                        <ul style="color: #8899AA; line-height: 1.7; padding-left: 1.2rem;">
                            <li style="margin-bottom: 0.5rem;">Create unlimited users (based on plan)</li>
                            <li style="margin-bottom: 0.5rem;">Role-based permissions (Admin, Manager, Cashier, Viewer)</li>
                            <li style="margin-bottom: 0.5rem;">Track user activity and sales per staff</li>
                            <li style="margin-bottom: 0.5rem;">Secure login with passwords or PIN codes</li>
                        </ul>
                    </div>

                    <!-- API Reference -->
                    <div id="api" class="docs-section" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 2rem; margin-bottom: 0; scroll-margin-top: 120px;">
                        <h2 class="h3 fw-bold" style="color: #fb7339; margin-bottom: 1rem;">API Reference</h2>
                        <p style="color: #8899AA; line-height: 1.7;">Integrate STARDENA SUITE with your existing systems using our REST API.</p>
                        <ul style="color: #8899AA; line-height: 1.7; padding-left: 1.2rem;">
                            <li style="margin-bottom: 0.5rem;">RESTful endpoints for products, orders, customers</li>
                            <li style="margin-bottom: 0.5rem;">Webhooks for real-time notifications</li>
                            <li style="margin-bottom: 0.5rem;">Secure API key authentication</li>
                            <li style="margin-bottom: 0.5rem;">Comprehensive API documentation available</li>
                        </ul>
                        <p style="color: #8899AA; line-height: 1.7; margin-top: 1rem;">Contact our team at <a href="mailto:api@stardena.org" style="color: #fb7339; text-decoration: none; font-weight: 500;">api@stardena.org</a> to request API access.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection