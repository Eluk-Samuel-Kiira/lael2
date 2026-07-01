@extends('home.layout')

@section('title', 'Help Center - STARPOSS')
@section('description', 'Find answers to common questions about STARPOSS. Setup guides, troubleshooting, and support resources.')

@section('content')

<div class="help-page">
    <div class="container py-5 mt-5">
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-8">
                <div class="hero-badge d-inline-flex mb-4">
                    <span class="pulse-dot me-2"></span>
                    <span>SUPPORT CENTER</span>
                </div>
                <h1 class="display-4 fw-bold mb-4" style="color: #E2EAF4;">
                    How Can We <span style="color: #FF6B2C;">Help You</span>?
                </h1>
                <p class="lead" style="color: #8899AA;">
                    Search our knowledge base or browse popular topics below.
                </p>
                <div class="mt-4">
                    <div class="input-group" style="max-width: 500px; margin: 0 auto;">
                        <input type="text" class="form-control" placeholder="Search for help..." style="background: #1E2D40; border: none; color: #fff; border-radius: 12px 0 0 12px;">
                        <button class="btn-hero-primary" style="border-radius: 0 12px 12px 0;"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-3">
            <div class="col-md-4">
                <div class="feature-card h-100 p-4 text-center">
                    <i class="fas fa-rocket fa-2x mb-3" style="color: #FF6B2C;"></i>
                    <h3 class="h5 fw-bold">Getting Started</h3>
                    <p style="color: #8899AA;">Learn how to set up your store, add products, and configure payment methods.</p>
                    <a href="/docs#getting-started" class="text-decoration-none" style="color: #FF6B2C;">Learn more →</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card h-100 p-4 text-center">
                    <i class="fas fa-chart-line fa-2x mb-3" style="color: #06D6A0;"></i>
                    <h3 class="h5 fw-bold">Reports & Analytics</h3>
                    <p style="color: #8899AA;">Understand your sales data, profit margins, and customer insights.</p>
                    <a href="/docs#reports" class="text-decoration-none" style="color: #FF6B2C;">Learn more →</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card h-100 p-4 text-center">
                    <i class="fas fa-mobile-alt fa-2x mb-3" style="color: #118AB2;"></i>
                    <h3 class="h5 fw-bold">Mobile & Offline</h3>
                    <p style="color: #8899AA;">Use STARPOSS on any device. Works without internet connection.</p>
                    <a href="/docs#offline" class="text-decoration-none" style="color: #FF6B2C;">Learn more →</a>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-2">
            <div class="col-md-4">
                <div class="feature-card h-100 p-4 text-center">
                    <i class="fas fa-credit-card fa-2x mb-3" style="color: #FFD166;"></i>
                    <h3 class="h5 fw-bold">Payments & Invoicing</h3>
                    <p style="color: #8899AA;">Set up payment methods, process refunds, and manage invoices.</p>
                    <a href="/docs#payments" class="text-decoration-none" style="color: #FF6B2C;">Learn more →</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card h-100 p-4 text-center">
                    <i class="fas fa-users fa-2x mb-3" style="color: #FF6B2C;"></i>
                    <h3 class="h5 fw-bold">User Management</h3>
                    <p style="color: #8899AA;">Add staff, set permissions, and manage roles.</p>
                    <a href="/docs#users" class="text-decoration-none" style="color: #FF6B2C;">Learn more →</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card h-100 p-4 text-center">
                    <i class="fas fa-plug fa-2x mb-3" style="color: #06D6A0;"></i>
                    <h3 class="h5 fw-bold">Integrations & API</h3>
                    <p style="color: #8899AA;">Connect STARPOSS with your existing tools.</p>
                    <a href="/docs#api" class="text-decoration-none" style="color: #FF6B2C;">Learn more →</a>
                </div>
            </div>
        </div>

        <div class="row mt-5 pt-4">
            <div class="col-lg-6 mx-auto text-center">
                <div class="p-4" style="background: #1E2D40; border-radius: 24px;">
                    <i class="fas fa-headset fa-2x mb-3" style="color: #FF6B2C;"></i>
                    <h3 class="h5 fw-bold">Still have questions?</h3>
                    <p style="color: #8899AA;">Our support team is available 24/7 to assist you.</p>
                    <a href="/contact" class="btn-hero-primary text-decoration-none" onclick="openModal(); return false;">
                        <i class="fas fa-envelope me-2"></i> Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection