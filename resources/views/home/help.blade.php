@extends('home.layout')

@section('title', 'Help Center - STARDENA SUITE')
@section('description', 'Find answers to common questions about STARDENA SUITE. Setup guides, troubleshooting, and support resources.')

@section('content')

<!-- Page Header -->
<section class="page-header section accent-background" style="padding: 120px 0 60px;">
    <div class="container" data-aos="fade-up">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 mb-4">
                    <span class="badge" style="background: rgba(251, 115, 57, 0.15); color: #fb7339; padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.7rem; font-weight: 600; letter-spacing: 0.5px;">
                        <i class="bi bi-headset me-1"></i> SUPPORT CENTER
                    </span>
                </div>
                <h1 class="display-4 fw-bold" >
                    How Can We <span style="color: #fb7339;">Help You</span>?
                </h1>
                <p class="lead" style="color: #8899AA; max-width: 600px; margin: 0 auto;">
                    Search our knowledge base or browse popular topics below.
                </p>
                <div class="mt-4">
                    <div class="input-group" style="max-width: 500px; margin: 0 auto; background: var(--brand-card); border-radius: 50px; overflow: hidden; border: 1px solid rgba(255,255,255,0.06);">
                        <input type="text" class="form-control" placeholder="Search for help..." style="background: transparent; border: none; color: #E2EAF4; padding: 0.8rem 1.5rem; font-size: 0.9rem;">
                        <button class="btn" style="background: linear-gradient(135deg, #fb7339, #e85d1a); color: #fff; border: none; padding: 0.8rem 1.5rem; border-radius: 0 50px 50px 0;">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Help Topics Section -->
<section id="help" class="help section">
    <div class="container">
        <div class="row g-4">
            <!-- Getting Started -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="help-card" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 2rem; text-align: center; transition: all 0.3s ease; height: 100%;">
                    <div style="width: 60px; height: 60px; background: rgba(251, 115, 57, 0.12); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                        <i class="bi bi-rocket-takeoff" style="font-size: 1.8rem; color: #fb7339;"></i>
                    </div>
                    <h3 class="buy-btn" >Getting Started</h3>
                    <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.7;">Learn how to set up your store, add products, and configure payment methods.</p>
                    <a href="/docs#getting-started" class="text-decoration-none d-inline-block mt-2" style="color: #fb7339; font-weight: 500; transition: all 0.3s ease;">
                        Learn more <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Reports & Analytics -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="help-card" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 2rem; text-align: center; transition: all 0.3s ease; height: 100%;">
                    <div style="width: 60px; height: 60px; background: rgba(6,214,160,0.12); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                        <i class="bi bi-graph-up-arrow" style="font-size: 1.8rem; color: #06D6A0;"></i>
                    </div>
                    <h3 class="buy-btn" >Reports & Analytics</h3>
                    <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.7;">Understand your sales data, profit margins, and customer insights.</p>
                    <a href="/docs#reports" class="text-decoration-none d-inline-block mt-2" style="color: #fb7339; font-weight: 500; transition: all 0.3s ease;">
                        Learn more <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Mobile & Offline -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="help-card" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 2rem; text-align: center; transition: all 0.3s ease; height: 100%;">
                    <div style="width: 60px; height: 60px; background: rgba(17,138,178,0.12); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                        <i class="bi bi-phone" style="font-size: 1.8rem; color: #118AB2;"></i>
                    </div>
                    <h3 class="buy-btn" >Mobile & Offline</h3>
                    <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.7;">Use STARDENA SUITE on any device. Works without internet connection.</p>
                    <a href="/docs#offline" class="text-decoration-none d-inline-block mt-2" style="color: #fb7339; font-weight: 500; transition: all 0.3s ease;">
                        Learn more <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Payments & Invoicing -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="400">
                <div class="help-card" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 2rem; text-align: center; transition: all 0.3s ease; height: 100%;">
                    <div style="width: 60px; height: 60px; background: rgba(255,209,102,0.12); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                        <i class="bi bi-credit-card" style="font-size: 1.8rem; color: #FFD166;"></i>
                    </div>
                    <h3 class="buy-btn" >Payments & Invoicing</h3>
                    <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.7;">Set up payment methods, process refunds, and manage invoices.</p>
                    <a href="/docs#payments" class="text-decoration-none d-inline-block mt-2" style="color: #fb7339; font-weight: 500; transition: all 0.3s ease;">
                        Learn more <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- User Management -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="500">
                <div class="help-card" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 2rem; text-align: center; transition: all 0.3s ease; height: 100%;">
                    <div style="width: 60px; height: 60px; background: rgba(251, 115, 57, 0.12); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                        <i class="bi bi-people" style="font-size: 1.8rem; color: #fb7339;"></i>
                    </div>
                    <h3 class="buy-btn" >User Management</h3>
                    <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.7;">Add staff, set permissions, and manage roles.</p>
                    <a href="/docs#users" class="text-decoration-none d-inline-block mt-2" style="color: #fb7339; font-weight: 500; transition: all 0.3s ease;">
                        Learn more <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Integrations & API -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="600">
                <div class="help-card" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 2rem; text-align: center; transition: all 0.3s ease; height: 100%;">
                    <div style="width: 60px; height: 60px; background: rgba(6,214,160,0.12); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                        <i class="bi bi-plug" style="font-size: 1.8rem; color: #06D6A0;"></i>
                    </div>
                    <h3 class="buy-btn" >Integrations & API</h3>
                    <p style="color: #8899AA; font-size: 0.9rem; line-height: 1.7;">Connect STARDENA SUITE with your existing tools.</p>
                    <a href="/docs#api" class="text-decoration-none d-inline-block mt-2" style="color: #fb7339; font-weight: 500; transition: all 0.3s ease;">
                        Learn more <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Contact Support CTA -->
        <div class="row mt-5 pt-4" data-aos="fade-up">
            <div class="col-12">
                <div class="support-cta text-center" style="background: linear-gradient(135deg, #1E2D40, #1A2535); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 3rem 2rem; transition: all 0.3s ease;">
                    <div style="width: 60px; height: 60px; background: rgba(251, 115, 57, 0.12); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                        <i class="bi bi-headset" style="font-size: 1.8rem; color: #fb7339;"></i>
                    </div>
                    <h3 class="h4 fw-bold" style="color: #E2EAF4; margin-bottom: 0.5rem;">Still have questions?</h3>
                    <p style="color: #8899AA; margin-bottom: 1.5rem;">Our support team is available 24/7 to assist you.</p>
                    <a href="#contact" class="btn-hero-primary text-decoration-none" style="padding: 0.7rem 2rem; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; border-radius: 50px; background: linear-gradient(135deg, #fb7339, #e85d1a); color: #fff; border: none; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(251, 115, 57, 0.3);">
                        <i class="bi bi-envelope"></i> Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection