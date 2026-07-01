@extends('home.layout')

@section('title', 'Documentation - STARPOSS User Guide')
@section('description', 'Complete documentation for STARPOSS POS system. Setup guides, API references, and troubleshooting.')

@section('content')

<div class="docs-page">
    <div class="container py-5 mt-5">
        <div class="row">
            <div class="col-lg-3 mb-4">
                <div class="sticky-top" style="top: 100px;">
                    <div class="list-group" style="background: #1E2D40; border-radius: 16px; overflow: hidden;">
                        <a href="#getting-started" class="list-group-item list-group-item-action" style="background: transparent; color: #E2EAF4; border-color: rgba(255,255,255,0.1);">Getting Started</a>
                        <a href="#products" class="list-group-item list-group-item-action" style="background: transparent; color: #E2EAF4; border-color: rgba(255,255,255,0.1);">Products & Inventory</a>
                        <a href="#sales" class="list-group-item list-group-item-action" style="background: transparent; color: #E2EAF4; border-color: rgba(255,255,255,0.1);">Processing Sales</a>
                        <a href="#reports" class="list-group-item list-group-item-action" style="background: transparent; color: #E2EAF4; border-color: rgba(255,255,255,0.1);">Reports & Analytics</a>
                        <a href="#offline" class="list-group-item list-group-item-action" style="background: transparent; color: #E2EAF4; border-color: rgba(255,255,255,0.1);">Offline Mode</a>
                        <a href="#multi-currency" class="list-group-item list-group-item-action" style="background: transparent; color: #E2EAF4; border-color: rgba(255,255,255,0.1);">Multi-Currency</a>
                        <a href="#payments" class="list-group-item list-group-item-action" style="background: transparent; color: #E2EAF4; border-color: rgba(255,255,255,0.1);">Payments</a>
                        <a href="#users" class="list-group-item list-group-item-action" style="background: transparent; color: #E2EAF4; border-color: rgba(255,255,255,0.1);">User Management</a>
                        <a href="#api" class="list-group-item list-group-item-action" style="background: transparent; color: #E2EAF4; border-color: rgba(255,255,255,0.1);">API Reference</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="docs-content">
                    <h1 id="getting-started" class="display-5 fw-bold mb-4" style="color: #FF6B2C;">Getting Started</h1>
                    <p style="color: #8899AA; line-height: 1.7;">Welcome to STARPOSS! This guide will help you set up your store in minutes.</p>
                    <ol style="color: #8899AA; line-height: 1.7;">
                        <li>Create your account by clicking "Get Started"</li>
                        <li>Add your store/business details (name, address, currency)</li>
                        <li>Set up your payment methods (Cash, Mobile Money, Card)</li>
                        <li>Add your products or import from a spreadsheet</li>
                        <li>Start selling! Your POS is ready</li>
                    </ol>
                    
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 2rem 0;">
                    
                    <h1 id="products" class="display-5 fw-bold mb-4" style="color: #FF6B2C;">Products & Inventory</h1>
                    <p style="color: #8899AA; line-height: 1.7;">Manage your entire product catalog from one dashboard.</p>
                    <ul style="color: #8899AA; line-height: 1.7;">
                        <li>Add products with name, price, SKU, and images</li>
                        <li>Track stock levels with automatic updates</li>
                        <li>Set low-stock alerts to never run out</li>
                        <li>Bulk import/export using CSV files</li>
                        <li>Manage multiple locations with separate inventory</li>
                        <li>Create product categories and variants (size, color, etc.)</li>
                    </ul>
                    
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 2rem 0;">
                    
                    <h1 id="sales" class="display-5 fw-bold mb-4" style="color: #FF6B2C;">Processing Sales</h1>
                    <p style="color: #8899AA; line-height: 1.7;">The POS interface is designed for speed and simplicity.</p>
                    <ul style="color: #8899AA; line-height: 1.7;">
                        <li>Search products by name or barcode</li>
                        <li>Apply discounts to items or entire cart</li>
                        <li>Accept multiple payment methods in one transaction</li>
                        <li>Email or print receipts automatically</li>
                        <li>Create and manage customer profiles</li>
                        <li>Process returns and refunds easily</li>
                    </ul>
                    
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 2rem 0;">
                    
                    <h1 id="reports" class="display-5 fw-bold mb-4" style="color: #FF6B2C;">Reports & Analytics</h1>
                    <p style="color: #8899AA; line-height: 1.7;">Make data-driven decisions with real-time reports.</p>
                    <ul style="color: #8899AA; line-height: 1.7;">
                        <li>Daily, weekly, monthly sales summaries</li>
                        <li>Profit margin analysis by product or category</li>
                        <li>Inventory valuation reports</li>
                        <li>Top-selling products and categories</li>
                        <li>Customer purchase history and insights</li>
                    </ul>
                    
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 2rem 0;">
                    
                    <h1 id="offline" class="display-5 fw-bold mb-4" style="color: #FF6B2C;">Offline Mode</h1>
                    <p style="color: #8899AA; line-height: 1.7;">STARPOSS works even without internet. When offline, all sales are stored locally and sync automatically when connection is restored.</p>
                    <ul style="color: #8899AA; line-height: 1.7;">
                        <li>Continue selling during internet outages</li>
                        <li>Automatic sync when back online</li>
                        <li>No data loss guaranteed</li>
                        <li>Works on any device</li>
                    </ul>
                    
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 2rem 0;">
                    
                    <h1 id="multi-currency" class="display-5 fw-bold mb-4" style="color: #FF6B2C;">Multi-Currency</h1>
                    <p style="color: #8899AA; line-height: 1.7;">Set your base currency and accept payments in other currencies with live or fixed exchange rates.</p>
                    <ul style="color: #8899AA; line-height: 1.7;">
                        <li>Supported currencies: USD, UGX, KES, TZS, RWF, EUR, GBP, and more</li>
                        <li>Automatic exchange rate updates</li>
                        <li>Separate cash registers per currency</li>
                        <li>Clear currency conversion tracking</li>
                    </ul>
                    
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 2rem 0;">
                    
                    <h1 id="payments" class="display-5 fw-bold mb-4" style="color: #FF6B2C;">Payments</h1>
                    <p style="color: #8899AA; line-height: 1.7;">Configure multiple payment methods for your business.</p>
                    <ul style="color: #8899AA; line-height: 1.7;">
                        <li>Cash payments with change calculation</li>
                        <li>Mobile Money (M-Pesa, Airtel Money, MTN MoMo)</li>
                        <li>Credit/Debit cards via integrated POS</li>
                        <li>Bank transfers and cheques</li>
                        <li>Split payments (combine multiple methods)</li>
                    </ul>
                    
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 2rem 0;">
                    
                    <h1 id="users" class="display-5 fw-bold mb-4" style="color: #FF6B2C;">User Management</h1>
                    <p style="color: #8899AA; line-height: 1.7;">Add staff members and control their access.</p>
                    <ul style="color: #8899AA; line-height: 1.7;">
                        <li>Create unlimited users (based on plan)</li>
                        <li>Role-based permissions (Admin, Manager, Cashier, Viewer)</li>
                        <li>Track user activity and sales per staff</li>
                        <li>Secure login with passwords or PIN codes</li>
                    </ul>
                    
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 2rem 0;">
                    
                    <h1 id="api" class="display-5 fw-bold mb-4" style="color: #FF6B2C;">API Reference</h1>
                    <p style="color: #8899AA; line-height: 1.7;">Integrate STARPOSS with your existing systems using our REST API.</p>
                    <ul style="color: #8899AA; line-height: 1.7;">
                        <li>RESTful endpoints for products, orders, customers</li>
                        <li>Webhooks for real-time notifications</li>
                        <li>Secure API key authentication</li>
                        <li>Comprehensive API documentation available</li>
                    </ul>
                    <p style="color: #8899AA; line-height: 1.7; margin-top: 1rem;">Contact our team at <a href="mailto:api@starposs.com" style="color: #FF6B2C;">api@starposs.com</a> to request API access.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection