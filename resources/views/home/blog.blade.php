@extends('home.layout')

@section('title', 'Blog - STARDENA SUITE')
@section('description', 'Latest news, updates, and tips from the STARDENA SUITE team. Learn how to grow your business with our POS system.')

@section('content')

<!-- Page Header -->
<section class="page-header section accent-background" style="padding: 120px 0 60px;">
    <div class="container" data-aos="fade-up">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 mb-4">
                    <span class="badge" style="background: rgba(251, 115, 57, 0.15); color: #fb7339; padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.7rem; font-weight: 600; letter-spacing: 0.5px;">
                        <i class="bi bi-newspaper me-1"></i> LATEST UPDATES
                    </span>
                </div>
                <h1 class="display-4 fw-bold" style="color: #E2EAF4;">
                    STARDENA <span >Blog</span>
                </h1>
                <p class="lead" style="color: #8899AA; max-width: 600px; margin: 0 auto;">
                    Insights, tips, and stories from the team building the future of POS.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Blog Section -->
<section id="blog" class="blog section">
    <div class="container">
        <div class="row g-4">
            <!-- Featured Post -->
            <div class="col-12" data-aos="fade-up">
                <div class="featured-post" style="background: linear-gradient(135deg, #1E2D40, #1A2535); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 2rem; overflow: hidden;">
                    <div class="row align-items-center g-4">
                        <div class="col-lg-8">
                            <span class="badge mb-2" style="background: rgba(251, 115, 57, 0.15); color: #fb7339; padding: 0.4rem 0.8rem; border-radius: 50px; font-size: 0.7rem; font-weight: 600;">
                                <i class="bi bi-star-fill me-1" style="font-size: 0.6rem;"></i> FEATURED
                            </span>
                            <h2 class="h3 fw-bold buy-btn" style="color: #fb7339;">Getting Started with STARDENA SUITE</h2>
                            <p style="color: #8899AA; font-size: 1rem; line-height: 1.7;">Learn how to set up your store, add products, and start selling in minutes.</p>
                            <div class="d-flex flex-wrap gap-3 mt-3">
                                <span style="color: #8899AA; font-size: 0.85rem;"><i class="bi bi-calendar3 me-1"></i> May 12, 2026</span>
                                <span style="color: #8899AA; font-size: 0.85rem;"><i class="bi bi-clock me-1"></i> 5 min read</span>
                            </div>
                            <a href="/blog/getting-started" class="btn-getstarted text-decoration-none mt-3 d-inline-flex align-items-center" style="padding: 0.6rem 1.5rem; font-size: 0.9rem;">
                                Read More <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                        </div>
                        <div class="col-lg-4 text-center">
                            <div class="featured-icon" style="width: 100px; height: 100px; background: rgba(251, 115, 57, 0.1); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto; border: 1px solid rgba(251, 115, 57, 0.2);">
                                <i class="bi bi-shop" style="font-size: 3rem; color: #fb7339;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Regular Posts -->
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="blog-card" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 1.5rem; transition: all 0.3s ease; height: 100%;">
                    <div class="mb-3">
                        <span class="badge" style="background: rgba(6,214,160,0.15); color: #06D6A0; padding: 0.3rem 0.8rem; border-radius: 50px; font-size: 0.65rem; font-weight: 600;">TUTORIAL</span>
                    </div>
                    <h3 class="buy-btn" >Why Offline Mode Matters for African Businesses</h3>
                    <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.7;">Internet connectivity can be unreliable. Here's how offline POS keeps you selling even when the internet goes down.</p>
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="bi bi-calendar3 me-1"></i> May 10, 2026</span>
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="bi bi-clock me-1"></i> 4 min read</span>
                    </div>
                    <a href="/blog/offline-mode-africa" class="text-decoration-none mt-2 d-inline-block" style="color: #fb7339; font-weight: 500;">
                        Read more <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                <div class="blog-card" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 1.5rem; transition: all 0.3s ease; height: 100%;">
                    <div class="mb-3">
                        <span class="badge" style="background: rgba(17,138,178,0.15); color: #118AB2; padding: 0.3rem 0.8rem; border-radius: 50px; font-size: 0.65rem; font-weight: 600;">FEATURE</span>
                    </div>
                    <h3 class="buy-btn" >Multi-Currency POS: A Game Changer for East African Traders</h3>
                    <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.7;">Accept payments in UGX, KES, TZS, USD, and more with automatic conversion. How cross-border businesses save time and money.</p>
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="bi bi-calendar3 me-1"></i> May 5, 2026</span>
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="bi bi-clock me-1"></i> 6 min read</span>
                    </div>
                    <a href="/blog/multi-currency-pos" class="text-decoration-none mt-2 d-inline-block" style="color: #fb7339; font-weight: 500;">
                        Read more <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
                <div class="blog-card" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 1.5rem; transition: all 0.3s ease; height: 100%;">
                    <div class="mb-3">
                        <span class="badge" style="background: rgba(255,209,102,0.15); color: #FFD166; padding: 0.3rem 0.8rem; border-radius: 50px; font-size: 0.65rem; font-weight: 600;">TIPS</span>
                    </div>
                    <h3 class="buy-btn" >10 Ways to Improve Your Retail Store Efficiency</h3>
                    <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.7;">Practical tips to speed up checkout, reduce errors, and keep customers happy using STARDENA SUITE.</p>
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="bi bi-calendar3 me-1"></i> April 28, 2026</span>
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="bi bi-clock me-1"></i> 7 min read</span>
                    </div>
                    <a href="/blog/retail-efficiency" class="text-decoration-none mt-2 d-inline-block" style="color: #fb7339; font-weight: 500;">
                        Read more <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="400">
                <div class="blog-card" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 1.5rem; transition: all 0.3s ease; height: 100%;">
                    <div class="mb-3">
                        <span class="badge" style="background: rgba(251, 115, 57, 0.15); color: #fb7339; padding: 0.3rem 0.8rem; border-radius: 50px; font-size: 0.65rem; font-weight: 600;">ANNOUNCEMENT</span>
                    </div>
                    <h3 class="buy-btn" >STARDENA SUITE Launches in Rwanda</h3>
                    <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.7;">We're excited to announce our official expansion to Rwanda. Local support and RWF currency now available.</p>
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="bi bi-calendar3 me-1"></i> April 20, 2026</span>
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="bi bi-clock me-1"></i> 3 min read</span>
                    </div>
                    <a href="/blog/launch-rwanda" class="text-decoration-none mt-2 d-inline-block" style="color: #fb7339; font-weight: 500;">
                        Read more <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="500">
                <div class="blog-card" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 1.5rem; transition: all 0.3s ease; height: 100%;">
                    <div class="mb-3">
                        <span class="badge" style="background: rgba(6,214,160,0.15); color: #06D6A0; padding: 0.3rem 0.8rem; border-radius: 50px; font-size: 0.65rem; font-weight: 600;">GUIDE</span>
                    </div>
                    <h3 class="buy-btn" >Inventory Management Best Practices</h3>
                    <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.7;">How to track stock, set reorder points, and avoid dead stock using STARDENA SUITE inventory features.</p>
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="bi bi-calendar3 me-1"></i> April 15, 2026</span>
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="bi bi-clock me-1"></i> 5 min read</span>
                    </div>
                    <a href="/blog/inventory-best-practices" class="text-decoration-none mt-2 d-inline-block" style="color: #fb7339; font-weight: 500;">
                        Read more <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="600">
                <div class="blog-card" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 1.5rem; transition: all 0.3s ease; height: 100%;">
                    <div class="mb-3">
                        <span class="badge" style="background: rgba(251, 115, 57, 0.15); color: #fb7339; padding: 0.3rem 0.8rem; border-radius: 50px; font-size: 0.65rem; font-weight: 600;">UPDATES</span>
                    </div>
                    <h3 class="buy-btn" >STARDENA SUITE v3.0: What's New</h3>
                    <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.7;">Discover the latest features including AI-powered insights, enhanced reporting, and improved performance.</p>
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="bi bi-calendar3 me-1"></i> April 10, 2026</span>
                        <span style="color: #8899AA; font-size: 0.75rem;"><i class="bi bi-clock me-1"></i> 8 min read</span>
                    </div>
                    <a href="/blog/version-3-0" class="text-decoration-none mt-2 d-inline-block" style="color: #fb7339; font-weight: 500;">
                        Read more <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Newsletter CTA -->
        <div class="row mt-5 pt-4" data-aos="fade-up">
            <div class="col-12">
                <div class="newsletter-box text-center" style="background: linear-gradient(135deg, #1E2D40, #1A2535); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 3rem 2rem;">
                    <h3 style="color: #E2EAF4; font-weight: 700; margin-bottom: 0.5rem;">Stay Updated</h3>
                    <p style="color: #8899AA; margin-bottom: 1.5rem;">Subscribe to our newsletter and get the latest posts delivered to your inbox.</p>
                    <a href="#contact" class="btn-getstarted text-decoration-none" style="padding: 0.7rem 2rem; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="bi bi-envelope"></i> Subscribe to Newsletter
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection