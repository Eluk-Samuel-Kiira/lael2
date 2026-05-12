@extends('home.layout')

@section('title', 'Blog - STARPOSS')
@section('description', 'Latest news, updates, and tips from the STARPOSS team. Learn how to grow your business with our POS system.')

@section('content')

<div class="blog-page">
    <div class="container py-5 mt-5">
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-8">
                <div class="hero-badge d-inline-flex mb-4">
                    <span class="pulse-dot me-2"></span>
                    <span>LATEST UPDATES</span>
                </div>
                <h1 class="display-4 fw-bold mb-4" style="color: #E2EAF4;">
                    STARPOSS <span style="color: #FF6B2C;">Blog</span>
                </h1>
                <p class="lead" style="color: #8899AA;">
                    Insights, tips, and stories from the team building the future of POS.
                </p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Featured Post -->
            <div class="col-12 mb-4">
                <div class="feature-card p-4" style="background: linear-gradient(135deg, #1E2D40, #1A2535);">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <span class="badge mb-2" style="background: rgba(255,107,44,0.15); color: #FF6B2C;">FEATURED</span>
                            <h2 class="h3 fw-bold mb-2">Getting Started with STARPOSS</h2>
                            <p style="color: #8899AA;">Learn how to set up your store, add products, and start selling in minutes.</p>
                            <div class="d-flex gap-3 mt-3">
                                <span style="color: #8899AA; font-size: 0.85rem;"><i class="fas fa-calendar-alt me-1"></i> May 12, 2026</span>
                                <span style="color: #8899AA; font-size: 0.85rem;"><i class="fas fa-clock me-1"></i> 5 min read</span>
                            </div>
                            <a href="/blog/getting-started" class="btn-hero-primary text-decoration-none mt-3 d-inline-flex" style="padding: 0.5rem 1.2rem;">
                                Read More <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                        <div class="col-md-4 text-center mt-3 mt-md-0">
                            <i class="fas fa-store fa-4x" style="color: #FF6B2C; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Regular Posts -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100 p-4">
                    <div class="mb-2">
                        <span class="badge" style="background: rgba(6,214,160,0.15); color: #06D6A0;">TUTORIAL</span>
                    </div>
                    <h3 class="h5 fw-bold mb-2">Why Offline Mode Matters for African Businesses</h3>
                    <p style="color: #8899AA;">Internet connectivity can be unreliable. Here's how offline POS keeps you selling even when the internet goes down.</p>
                    <div class="d-flex gap-3 mt-2">
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="fas fa-calendar-alt me-1"></i> May 10, 2026</span>
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="fas fa-clock me-1"></i> 4 min read</span>
                    </div>
                    <a href="/blog/offline-mode-africa" class="text-decoration-none mt-2 d-inline-block" style="color: #FF6B2C;">Read more →</a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100 p-4">
                    <div class="mb-2">
                        <span class="badge" style="background: rgba(17,138,178,0.15); color: #118AB2;">FEATURE</span>
                    </div>
                    <h3 class="h5 fw-bold mb-2">Multi-Currency POS: A Game Changer for East African Traders</h3>
                    <p style="color: #8899AA;">Accept payments in UGX, KES, TZS, USD, and more with automatic conversion. How cross-border businesses save time and money.</p>
                    <div class="d-flex gap-3 mt-2">
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="fas fa-calendar-alt me-1"></i> May 5, 2026</span>
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="fas fa-clock me-1"></i> 6 min read</span>
                    </div>
                    <a href="/blog/multi-currency-pos" class="text-decoration-none mt-2 d-inline-block" style="color: #FF6B2C;">Read more →</a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100 p-4">
                    <div class="mb-2">
                        <span class="badge" style="background: rgba(255,209,102,0.15); color: #FFD166;">TIPS</span>
                    </div>
                    <h3 class="h5 fw-bold mb-2">10 Ways to Improve Your Retail Store Efficiency</h3>
                    <p style="color: #8899AA;">Practical tips to speed up checkout, reduce errors, and keep customers happy using STARPOSS.</p>
                    <div class="d-flex gap-3 mt-2">
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="fas fa-calendar-alt me-1"></i> April 28, 2026</span>
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="fas fa-clock me-1"></i> 7 min read</span>
                    </div>
                    <a href="/blog/retail-efficiency" class="text-decoration-none mt-2 d-inline-block" style="color: #FF6B2C;">Read more →</a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100 p-4">
                    <div class="mb-2">
                        <span class="badge" style="background: rgba(255,107,44,0.15); color: #FF6B2C;">ANNOUNCEMENT</span>
                    </div>
                    <h3 class="h5 fw-bold mb-2">STARPOSS Launches in Rwanda</h3>
                    <p style="color: #8899AA;">We're excited to announce our official expansion to Rwanda. Local support and RWF currency now available.</p>
                    <div class="d-flex gap-3 mt-2">
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="fas fa-calendar-alt me-1"></i> April 20, 2026</span>
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="fas fa-clock me-1"></i> 3 min read</span>
                    </div>
                    <a href="/blog/launch-rwanda" class="text-decoration-none mt-2 d-inline-block" style="color: #FF6B2C;">Read more →</a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100 p-4">
                    <div class="mb-2">
                        <span class="badge" style="background: rgba(6,214,160,0.15); color: #06D6A0;">GUIDE</span>
                    </div>
                    <h3 class="h5 fw-bold mb-2">Inventory Management Best Practices</h3>
                    <p style="color: #8899AA;">How to track stock, set reorder points, and avoid dead stock using STARPOSS inventory features.</p>
                    <div class="d-flex gap-3 mt-2">
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="fas fa-calendar-alt me-1"></i> April 15, 2026</span>
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="fas fa-clock me-1"></i> 5 min read</span>
                    </div>
                    <a href="/blog/inventory-best-practices" class="text-decoration-none mt-2 d-inline-block" style="color: #FF6B2C;">Read more →</a>
                </div>
            </div>
        </div>

        <div class="text-center mt-5 pt-4">
            <a href="/contact" class="btn-hero-ghost text-decoration-none" onclick="openModal(); return false;">
                <i class="fas fa-envelope me-2"></i> Subscribe to Newsletter
            </a>
        </div>
    </div>
</div>

@endsection