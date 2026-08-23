<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <title>@yield('title', 'STARDENA SUITE - Complete Business Management Platform | POS, Inventory, Manufacturing, HR')</title>
    <meta name="description" content="@yield('description', 'STARDENA SUITE is the all-in-one platform for POS, inventory, manufacturing, HR, payroll, multi-currency, and offline mode. Trusted by 5,000+ businesses in 30+ countries.')">
    <meta name="keywords" content="POS system, inventory management, manufacturing software, HR management, payroll system, multi-currency POS, offline POS, business management platform, Uganda POS, Kenya POS, East Africa POS">
    <meta name="author" content="STARDENA SUITE">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="STARDENA SUITE – All-in-One Business Management & POS Platform">
    <meta property="og:description" content="Manage your entire business with STARDENA SUITE: POS, inventory, manufacturing, HR, payroll, multi-currency, and offline mode. Start your free trial today.">
    <meta property="og:image" content="{{ asset('front/img/og-image.jpg') }}">
    <meta property="og:site_name" content="STARDENA SUITE">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="STARDENA SUITE – POS, Inventory, Manufacturing, HR & Payroll">
    <meta name="twitter:description" content="The complete business management platform with POS, inventory, manufacturing, HR, payroll, and offline mode. Start your free trial today.">
    <meta name="twitter:image" content="{{ asset('front/img/twitter-card.jpg') }}">

    <!-- Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "STARDENA SUITE",
        "description": "Complete business management platform with POS, inventory, manufacturing, HR, payroll, multi-currency, and offline mode.",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Web",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        }
    }
    </script>

    <!-- Geo Tags -->
    <meta name="geo.region" content="UG">
    <meta name="geo.placename" content="Uganda, East Africa">

    <!-- Mobile & Theme -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0F1923">

    <!-- Favicons -->
    <link href="{{ asset('suite.png') }}" rel="icon">
    <link href="{{ asset('suite.png') }}" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="{{ asset('front/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('front/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('front/vendor/aos/aos.css') }}" rel="stylesheet">
    <link href="{{ asset('front/vendor/glightbox/css/glightbox.min.css') }}" rel="stylesheet">
    <link href="{{ asset('front/vendor/swiper/swiper-bundle.min.css') }}" rel="stylesheet">

    <!-- Main CSS File -->
    <link href="{{ asset('front/css/main.css') }}" rel="stylesheet">

    @stack('styles')
    @php
        $allowedHosts = ['suite.stardena.org', '127.0.0.1', 'localhost'];
    @endphp

    @if(!in_array(request()->getHost(), $allowedHosts))
        <script>
            window.location.href = "{{ route('login') }}";
        </script>
    @endif
    
    <style>
        /* Pricing Section Styles */
        .pricing-card {
            background: var(--brand-card);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .pricing-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }

        .pricing-card.popular {
            border-color: #fb7339;
            box-shadow: 0 0 0 1px #fb7339, 0 20px 50px rgba(251, 115, 57, 0.15);
        }

        .popular-ribbon {
            background: linear-gradient(135deg, #fb7339, #e85d1a);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            text-align: center;
            padding: 0.4rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .pricing-header {
            padding: 1.5rem 1.5rem 0.5rem;
        }

        .plan-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: #14427a;
            margin-bottom: 0.25rem;
        }

        .plan-desc {
            font-size: 0.8rem;
            color: var(--text-muted-custom);
            margin-bottom: 1rem;
        }

        .plan-price {
            font-weight: 800;
            font-size: 2.2rem;
            color: #14427a;
            line-height: 1;
        }

        .plan-price sup {
            font-size: 1rem;
            font-weight: 600;
            vertical-align: super;
        }

        .plan-price .period {
            font-size: 0.85rem;
            font-weight: 400;
            color: var(--text-muted-custom);
        }

        .plan-trial {
            font-size: 0.7rem;
            color: #06D6A0;
            margin-top: 0.25rem;
        }

        .pricing-features {
            padding: 1rem 1.5rem;
            flex: 1;
        }

        .feature-list-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 0.5rem;
            font-size: 0.8rem;
            color: #B0C4D8;
        }

        .feature-list-item i.yes {
            color: #06D6A0;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .feature-list-item i.no {
            color: #4A5568;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .feature-list-item.dimmed {
            color: #4A5568;
        }

        .view-more-btn {
            background: none;
            border: none;
            color: #fb7339;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            padding: 0.25rem 0;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s ease;
        }

        .view-more-btn:hover {
            color: #e85d1a;
        }

        .hidden-features {
            display: none;
        }

        .hidden-features.shown {
            display: block;
        }

        .pricing-footer {
            padding: 0 1.5rem 1.5rem;
        }

        .btn-plan {
            width: 100%;
            padding: 0.7rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-plan-outline {
            background: transparent;
            border: 1px solid rgba(251, 115, 57, 0.4);
            color: #fb7339;
        }

        .btn-plan-outline:hover {
            background: rgba(251, 115, 57, 0.1);
            border-color: #fb7339;
        }

        .btn-plan-primary {
            background: linear-gradient(135deg, #fb7339, #e85d1a);
            color: #fff;
            box-shadow: 0 4px 15px rgba(251, 115, 57, 0.3);
        }

        .btn-plan-primary:hover {
            box-shadow: 0 8px 25px rgba(251, 115, 57, 0.5);
            transform: translateY(-1px);
        }

        /* Billing Tabs */
        .billing-tabs {
            display: inline-flex;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 50px;
            padding: 4px;
            gap: 4px;
        }

        .billing-tab {
            border: none;
            background: transparent;
            color: var(--text-muted-custom);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .billing-tab.active {
            background: linear-gradient(135deg, #fb7339, #e85d1a);
            color: #fff;
            box-shadow: 0 4px 12px rgba(251, 115, 57, 0.3);
        }
    </style>

    <style>
        /* Modal Styles - Matching Dark Theme (SOLID, not transparent) */
        .modal-content {
            background-color: #0F1B2E !important;   /* solid fallback, matches your dark theme */
            background: var(--brand-card, #0F1B2E) !important;
            border: 1px solid rgba(255,255,255,0.08) !important;
            border-radius: 16px !important;
            color: #E2EAF4 !important;
            box-shadow: 0 25px 60px rgba(0,0,0,0.6) !important; /* helps it visually "lift" off the page */
        }

        /* Make sure the dark backdrop behind the modal is visible */
        .modal-backdrop {
            background-color: #000 !important;
        }
        .modal-backdrop.show {
            opacity: 0.75 !important;
        }

        .modal-header {
            border-bottom: 1px solid rgba(255,255,255,0.06) !important;
            padding: 1.5rem 2rem !important;
            background: transparent !important; /* inherits solid modal-content bg */
        }

        .modal-header .btn-close {
            filter: invert(1);
            opacity: 0.6;
        }

        .modal-body {
            padding: 2rem !important;
            background: transparent !important; /* inherits solid modal-content bg */
        }

        /* Form Controls - Dark Theme */
        .modal-content .form-control,
        .modal-content .form-select {
            background: rgba(255,255,255,0.05) !important;
            border: 1px solid rgba(255,255,255,0.08) !important;
            border-radius: 10px !important;
            color: #E2EAF4 !important;
            padding: 0.75rem 1rem !important;
            transition: all 0.3s ease;
        }

        .modal-content .form-control:focus,
        .modal-content .form-select:focus {
            background: rgba(255,255,255,0.08) !important;
            border-color: rgba(251, 115, 57, 0.4) !important;
            box-shadow: 0 0 0 3px rgba(251, 115, 57, 0.1) !important;
            color: #E2EAF4 !important;
        }

        .modal-content .form-control::placeholder {
            color: #4A5568 !important;
        }

        .modal-content .form-select option {
            background: #0F1B2E !important;
            color: #E2EAF4 !important;
        }

        .modal-content .form-label {
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            color: #B0C4D8 !important;
            margin-bottom: 0.5rem !important;
        }

        .modal-content .form-label .required {
            color: #fb7339 !important;
        }

        /* Plan Badge in Modal */
        .plan-badge {
            display: inline-block;
            background: rgba(251, 115, 57, 0.12);
            color: #fb7339;
            padding: 0.2rem 0.8rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>

</head>

<body class="index-page">

    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center">

            <a href="/" class="logo d-flex align-items-center me-auto" style="gap: 4px;">
                <img src="{{ asset('suite.png') }}" alt="" style="height: clamp(24px, 3vw, 32px); width: auto;">
                <h1 class="sitename" style="font-size: clamp(1.2rem, 1.8vw, 1.5rem); white-space: nowrap; margin: 0; line-height: 1;">STARDENA <span style="color: #fb7339;">SUITE</span></h1>
            </a>

            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="/#hero" class="active">Home</a></li>
                    <li><a href="/#about">About</a></li>
                    <li class="dropdown"><a href="/#"><span>Solutions</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                        <ul>
                        <li><a href="/#services">Services</a></li>
                        <li><a href="/#features">Features</a></li>
                        <li><a href="/#industries">Industries</a></li>
                        <li><a href="/#benefits">Benefits</a></li>
                        <li><a href="/#partners">Partners</a></li>
                        <li><a href="/#faq">FAQ</a></li>
                        </ul>
                    </li>
                    <li><a href="/#pricing">Pricing</a></li>
                    <li><a href="/#contact">Contact</a></li>
                    <li class="dropdown"><a href="#"><span>Pages</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                        <ul>
                        <li><a href="{{ route('pages.blog') }}">Blog</a></li>
                        <li><a href="{{ route('pages.docs') }}">Documentations</a></li>
                        <li><a href="{{ route('pages.terms') }}">Terms of Service</a></li>
                        <li><a href="{{ route('pages.privacy') }}">Privacy Policy</a></li>
                        </ul>
                    </li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>

            <a class="btn-getstarted" href="{{ route('login') }}" target="_blank">Live Demo</a>

        </div>
    </header>

    <main class="main">
        @yield('content')
    </main>

    <footer id="footer" class="footer accent-background">
        <div class="container footer-top">
            <div class="row gy-4">
                <div class="col-lg-4 col-md-12 footer-about">
                    <a href="/" class="logo d-flex align-items-center">
                        <img src="{{ asset('suite.png') }}" alt="" style="height: clamp(24px, 3vw, 32px); width: auto;">
                        <span class="sitename" style="font-size: clamp(1rem, 2vw, 1.5rem); font-weight: 700; margin-left: 8px;">STARDENA <span style="color: #fb7339;">SUITE</span></span>
                    </a>
                    <p style="margin-top: 1rem; font-size: 0.9rem; line-height: 1.7;">The global point-of-sale platform for modern businesses. Built in East Africa. Used worldwide.</p>
                    <div class="social-links d-flex mt-4">
                        <a href="#" class="me-2" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(255,255,255,0.05); border-radius: 50%; transition: all 0.3s ease; color: #B0C4D8; text-decoration: none;">
                            <i class="bi bi-twitter-x"></i>
                        </a>
                        <a href="#" class="me-2" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(255,255,255,0.05); border-radius: 50%; transition: all 0.3s ease; color: #B0C4D8; text-decoration: none;">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="#" class="me-2" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(255,255,255,0.05); border-radius: 50%; transition: all 0.3s ease; color: #B0C4D8; text-decoration: none;">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="#" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(255,255,255,0.05); border-radius: 50%; transition: all 0.3s ease; color: #B0C4D8; text-decoration: none;">
                            <i class="bi bi-linkedin"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-2 col-6 footer-links">
                    <h4 style="font-size: 0.85rem; font-weight: 700; color: #E2EAF4; margin-bottom: 1.25rem; text-transform: uppercase; letter-spacing: 0.5px;">Product</h4>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 0.6rem;"><a href="#features" style="color: #8899AA; text-decoration: none; transition: all 0.3s ease; font-size: 0.85rem;">Features</a></li>
                        <li style="margin-bottom: 0.6rem;"><a href="#pricing" style="color: #8899AA; text-decoration: none; transition: all 0.3s ease; font-size: 0.85rem;">Pricing</a></li>
                        <li style="margin-bottom: 0.6rem;"><a href="{{ route('pages.docs') }}" style="color: #8899AA; text-decoration: none; transition: all 0.3s ease; font-size: 0.85rem;">Documentation</a></li>
                        <li style="margin-bottom: 0.6rem;"><a href="{{ route('login') }}" style="color: #8899AA; text-decoration: none; transition: all 0.3s ease; font-size: 0.85rem;">Live Demo</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-6 footer-links">
                    <h4 style="font-size: 0.85rem; font-weight: 700; color: #E2EAF4; margin-bottom: 1.25rem; text-transform: uppercase; letter-spacing: 0.5px;">Company</h4>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 0.6rem;"><a href="/#about" style="color: #8899AA; text-decoration: none; transition: all 0.3s ease; font-size: 0.85rem;">About Us</a></li>
                        <li style="margin-bottom: 0.6rem;"><a href="{{ route('pages.blog') }}" style="color: #8899AA; text-decoration: none; transition: all 0.3s ease; font-size: 0.85rem;">Blog</a></li>
                        <li style="margin-bottom: 0.6rem;"><a href="#" style="color: #8899AA; text-decoration: none; transition: all 0.3s ease; font-size: 0.85rem;">Careers</a></li>
                        <li style="margin-bottom: 0.6rem;"><a href="#contact" style="color: #8899AA; text-decoration: none; transition: all 0.3s ease; font-size: 0.85rem;">Contact</a></li>
                    </ul>
                </div>

                <div class="col-lg-4 col-md-12 footer-contact text-center text-md-start">
                    <h4 style="font-size: 0.85rem; font-weight: 700; color: #E2EAF4; margin-bottom: 1.25rem; text-transform: uppercase; letter-spacing: 0.5px;">Contact Us</h4>
                    <p style="color: #8899AA; font-size: 0.85rem; margin-bottom: 0.25rem;">Kampala, Uganda</p>
                    <p style="color: #8899AA; font-size: 0.85rem; margin-bottom: 0.5rem;">East Africa</p>
                    <p style="color: #8899AA; font-size: 0.85rem; margin-bottom: 0.25rem;"><strong style="color: #B0C4D8;">Email:</strong> <span>pos@stardena.org</span></p>
                    <p style="color: #8899AA; font-size: 0.85rem;"><strong style="color: #B0C4D8;">Website:</strong> <span>suite.stardena.org</span></p>
                    <div class="mt-3">
                        <a href="{{ route('pages.privacy') }}" style="color: #8899AA; text-decoration: none; font-size: 0.75rem; margin-right: 1rem; transition: all 0.3s ease;">Privacy Policy</a>
                        <a href="{{ route('pages.terms') }}" style="color: #8899AA; text-decoration: none; font-size: 0.75rem; transition: all 0.3s ease;">Terms of Service</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container copyright text-center mt-4" style="padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.05);">
            <p style="color: #8899AA; font-size: 0.8rem; margin: 0;">
                © <span>Copyright</span> <strong style="color: #E2EAF4;">STARDENA SUITE</strong> <span style="color: #8899AA;">All Rights Reserved</span>
            </p>
            <div class="credits" style="color: #8899AA; font-size: 0.75rem; margin-top: 0.5rem;">
                Made with ❤️ from <a href="https://stardena.org/" target="_blank">Stardena Corp.</a>
            </div>
        </div>
    </footer>

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Preloader -->
    <div id="preloader"></div>

    <!-- WhatsApp Float Button -->
    <a href="https://wa.me/256754428612?text={{ urlencode('Hello! I would like to inquire about STARDENA SUITE.') }}" 
        target="_blank" 
        class="whatsapp-float" 
        style="position: fixed; bottom: 30px; left: 30px; z-index: 9999; display: flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: #25D366; color: #fff; border-radius: 50%; text-decoration: none; box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4); transition: all 0.3s ease;"
        onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 6px 25px rgba(37, 211, 102, 0.6)'"
        onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 15px rgba(37, 211, 102, 0.4)'">
        <i class="bi bi-whatsapp" style="font-size: 2rem;"></i>
    </a>

    <!-- Optional: Small label next to icon -->
    <style>
        .whatsapp-float {
            animation: pulse-whatsapp 2s infinite;
        }
        
        @keyframes pulse-whatsapp {
            0% { box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4); }
            50% { box-shadow: 0 4px 30px rgba(37, 211, 102, 0.7); }
            100% { box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4); }
        }
    </style>


    <!-- Vendor JS Files -->
    <script src="{{ asset('front/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('front/vendor/aos/aos.js') }}"></script>
    <script src="{{ asset('front/vendor/glightbox/js/glightbox.min.js') }}"></script>
    <script src="{{ asset('front/vendor/purecounter/purecounter_vanilla.js') }}"></script>
    <script src="{{ asset('front/vendor/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('front/vendor/isotope-layout/isotope.pkgd.min.js') }}"></script>

    <!-- Main JS File -->
    <script src="{{ asset('front/js/main.js') }}"></script>

    <script>
        // ─── MODAL FUNCTIONS ──────────────────────────────────────────────
        let selectedPlan = '';

        function openModal(planName) {
            selectedPlan = planName;
            const planSelect = document.getElementById('contactPlan');
            if (planSelect) {
                // Map plan names to select options
                const planMap = {
                    'Free Trial': 'free_trial',
                    'Starter': 'starter',
                    'Starter Plan': 'starter',
                    'Business': 'business',
                    'Business Plan': 'business',
                    'Enterprise': 'enterprise',
                    'Enterprise Plan': 'enterprise',
                    'Lifetime License': 'lifetime'
                };
                
                const mappedValue = planMap[planName] || planName.toLowerCase();
                
                for (let i = 0; i < planSelect.options.length; i++) {
                    const option = planSelect.options[i];
                    if (option.value === mappedValue || 
                        option.text.toLowerCase().includes(planName.toLowerCase()) || 
                        planName.toLowerCase().includes(option.text.toLowerCase())) {
                        planSelect.selectedIndex = i;
                        break;
                    }
                }
            }
            
            const modal = new bootstrap.Modal(document.getElementById('contactModal'));
            modal.show();
        }

        function sendInquiry(event) {
            if (event) event.preventDefault();
            
            const name = document.getElementById('contactName')?.value?.trim() || '';
            const email = document.getElementById('contactEmail')?.value?.trim() || '';
            const phone = document.getElementById('contactPhone')?.value?.trim() || '';
            const businessName = document.getElementById('contactBusinessName')?.value?.trim() || '';
            const business = document.getElementById('contactBusiness')?.value || '';
            const planSelect = document.getElementById('contactPlan');
            const plan = planSelect?.value || '';
            const planText = planSelect?.options[planSelect.selectedIndex]?.text || '';
            const message = document.getElementById('contactMessage')?.value?.trim() || '';
            const alertBox = document.getElementById('modalAlert');
            const btn = document.getElementById('sendInquiryBtn');

            // Validation
            if (!name || !email) {
                alertBox.className = 'alert alert-danger rounded-3';
                alertBox.style.background = 'rgba(241, 65, 108, 0.1)';
                alertBox.style.border = '1px solid rgba(241, 65, 108, 0.3)';
                alertBox.style.color = '#F1416C';
                alertBox.textContent = 'Please enter your name and email address.';
                alertBox.classList.remove('d-none');
                return;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alertBox.className = 'alert alert-danger rounded-3';
                alertBox.style.background = 'rgba(241, 65, 108, 0.1)';
                alertBox.style.border = '1px solid rgba(241, 65, 108, 0.3)';
                alertBox.style.color = '#F1416C';
                alertBox.textContent = 'Please enter a valid email address.';
                alertBox.classList.remove('d-none');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Sending...';
            alertBox.classList.add('d-none');

            const payload = { name, email, phone, businessName, business, plan, planText, message };

            fetch('/send-inquiry', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                // Show success
                alertBox.className = 'alert rounded-3 mb-0';
                alertBox.style.background = 'rgba(6, 214, 160, 0.1)';
                alertBox.style.border = '1px solid rgba(6, 214, 160, 0.3)';
                alertBox.style.color = '#06D6A0';
                alertBox.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i><strong>Thank you, ${name}!</strong> Your inquiry has been sent. We'll contact you at <strong>${email}</strong> within 24 hours.`;
                alertBox.classList.remove('d-none');

                btn.innerHTML = '<i class="bi bi-check-lg me-2"></i>Message Sent!';
                btn.style.background = 'linear-gradient(135deg, #06D6A0, #059669)';
                btn.style.boxShadow = '0 4px 15px rgba(6, 214, 160, 0.3)';

                // Reset form after 5 seconds
                setTimeout(() => {
                    document.getElementById('contactName').value = '';
                    document.getElementById('contactEmail').value = '';
                    document.getElementById('contactPhone').value = '';
                    document.getElementById('contactBusinessName').value = '';
                    document.getElementById('contactBusiness').value = '';
                    document.getElementById('contactPlan').value = '';
                    document.getElementById('contactMessage').value = '';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-send me-2"></i> Send Inquiry';
                    btn.style.background = '';
                    btn.style.boxShadow = '';
                    
                    // Close modal after 2 more seconds
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('contactModal'));
                        if (modal) modal.hide();
                    }, 2000);
                }, 5000);
            })
            .catch(err => {
                alertBox.className = 'alert rounded-3 mb-0';
                alertBox.style.background = 'rgba(251, 115, 57, 0.1)';
                alertBox.style.border = '1px solid rgba(251, 115, 57, 0.3)';
                alertBox.style.color = '#fb7339';
                alertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i> Something went wrong. Please email us directly at <strong>pos@stardena.org</strong>';
                alertBox.classList.remove('d-none');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send me-2"></i> Try Again';
            });
        }
    </script>
    <script>
        // ═══════════════════════════════════════
        // PLAN DATA - Updated with All Features
        // ═══════════════════════════════════════
        const plans = [
            {
                id: 1,
                code: 'free',
                name: 'Free Trial',
                desc: '65-day free trial with core features',
                onetime: 0,
                monthly: 0,
                yearly: 0,
                maintenance_yearly: 0,
                popular: false,
                features: [
                    { yes: true, label: '1 Store / Branch Location' },
                    { yes: true, label: '3 Departments' },
                    { yes: true, label: 'Up to 3 Users' },
                    { yes: true, label: '100 Products & Customers' },
                    { yes: true, label: 'POS Selling Interface' },
                    { yes: true, label: 'Basic Inventory Management' },
                    { yes: false, label: 'Batch & Serial Tracking' },
                    { yes: false, label: 'Manufacturing Module' },
                    { yes: false, label: 'Bill of Materials (BOM)' },
                    { yes: false, label: 'Recipes & Formulations' },
                    { yes: false, label: 'Production Cost Calculation' },
                    { yes: false, label: 'Raw Materials Management' },
                    { yes: false, label: 'Finished Goods Tracking' },
                    { yes: false, label: 'Stock Movement Tracking' },
                    { yes: false, label: 'Quotations & Invoices' },
                    { yes: false, label: 'Expenses Tracking' },
                    { yes: false, label: 'Accounting Module' },
                    { yes: false, label: 'HR & Payroll' },
                    { yes: false, label: 'Advanced Reports' },
                    { yes: false, label: 'Multi-Currency' },
                    { yes: false, label: 'API Access' },
                ],
                trial: '65-day free trial',
            },
            {
                id: 2,
                code: 'starter',
                name: 'Starter',
                desc: 'Essential features for small businesses',
                onetime: 299.99,
                monthly: 29.99,
                yearly: 199.99,
                maintenance_yearly: 49.99,
                popular: false,
                features: [
                    { yes: true, label: '1 Store / Branch Location' },
                    { yes: true, label: '5 Departments' },
                    { yes: true, label: 'Up to 5 Users' },
                    { yes: true, label: '500 Products & Customers' },
                    { yes: true, label: 'POS Selling Interface' },
                    { yes: true, label: 'Full Inventory Management' },
                    { yes: true, label: 'Batch & Serial Tracking' },
                    { yes: true, label: 'Expenses Tracking' },
                    { yes: true, label: 'Basic Reports' },
                    { yes: false, label: 'Manufacturing Module' },
                    { yes: false, label: 'Bill of Materials (BOM)' },
                    { yes: false, label: 'Recipes & Formulations' },
                    { yes: false, label: 'Production Cost Calculation' },
                    { yes: false, label: 'Raw Materials Management' },
                    { yes: false, label: 'Finished Goods Tracking' },
                    { yes: false, label: 'Stock Movement Tracking' },
                    { yes: false, label: 'Quotations & Invoices' },
                    { yes: false, label: 'Advanced Accounting' },
                    { yes: false, label: 'Multi-Currency' },
                    { yes: false, label: 'Financial Reports' },
                    { yes: false, label: 'HR & Payroll' },
                    { yes: false, label: 'API Access' },
                    { yes: false, label: 'Custom Branding' },
                ],
                trial: '14-day free trial included',
            },
            {
                id: 3,
                code: 'business',
                name: 'Business',
                desc: 'Advanced features for growing businesses',
                onetime: 799.99,
                monthly: 79.99,
                yearly: 599.99,
                maintenance_yearly: 99.99,
                popular: true,
                features: [
                    { yes: true, label: 'Up to 5 Store / Branch Locations' },
                    { yes: true, label: 'Unlimited Departments' },
                    { yes: true, label: 'Up to 20 Users' },
                    { yes: true, label: '5,000 Products & Customers' },
                    { yes: true, label: 'POS Selling Interface' },
                    { yes: true, label: 'Full Inventory Management' },
                    { yes: true, label: 'Batch & Serial Tracking' },
                    { yes: true, label: 'Manufacturing Module' },
                    { yes: true, label: 'Bill of Materials (BOM)' },
                    { yes: true, label: 'Recipes & Formulations' },
                    { yes: true, label: 'Production Cost Calculation' },
                    { yes: true, label: 'Raw Materials Management' },
                    { yes: true, label: 'Finished Goods Tracking' },
                    { yes: true, label: 'Stock Movement Tracking' },
                    { yes: true, label: 'Quotations & Invoices' },
                    { yes: true, label: 'Full Accounting Suite' },
                    { yes: true, label: 'Multi-Currency' },
                    { yes: true, label: 'Financial Reports' },
                    { yes: true, label: 'CRM Module' },
                    { yes: false, label: 'HR & Payroll' },
                    { yes: false, label: 'API Access' },
                    { yes: false, label: 'Custom Branding' },
                    { yes: false, label: 'Priority Support' },
                ],
                trial: '14-day free trial included',
            },
            {
                id: 4,
                code: 'enterprise',
                name: 'Enterprise',
                desc: 'Full access — unlimited everything',
                onetime: 1999.99,
                monthly: 199.99,
                yearly: 999.99,
                maintenance_yearly: 199.99,
                popular: false,
                features: [
                    { yes: true, label: 'Unlimited Locations' },
                    { yes: true, label: 'Unlimited Departments' },
                    { yes: true, label: 'Unlimited Users' },
                    { yes: true, label: 'Unlimited Products & Customers' },
                    { yes: true, label: 'POS Selling Interface' },
                    { yes: true, label: 'Full Inventory Management' },
                    { yes: true, label: 'Batch & Serial Tracking' },
                    { yes: true, label: 'Manufacturing Module' },
                    { yes: true, label: 'Bill of Materials (BOM)' },
                    { yes: true, label: 'Recipes & Formulations' },
                    { yes: true, label: 'Production Cost Calculation' },
                    { yes: true, label: 'Raw Materials Management' },
                    { yes: true, label: 'Finished Goods Tracking' },
                    { yes: true, label: 'Stock Movement Tracking' },
                    { yes: true, label: 'Quotations & Invoices' },
                    { yes: true, label: 'Full Accounting + HR & Payroll' },
                    { yes: true, label: 'Multi-Currency & Multi-Language' },
                    { yes: true, label: 'Advanced Analytics & Reports' },
                    { yes: true, label: 'API Access' },
                    { yes: true, label: 'Custom Branding' },
                    { yes: true, label: 'Priority 24/7 Support' },
                    { yes: true, label: 'CRM + eCommerce Module' },
                    { yes: true, label: 'Dedicated Account Manager' },
                ],
                trial: '14-day free trial included',
            },
        ];

        const SHOW_INITIALLY = 6;
        let currentTab = 'monthly';

        function renderPricing(tab) {
            currentTab = tab;
            const container = document.getElementById('pricingContainer');
            container.innerHTML = '';

            const cols = plans.map((plan, i) => {
                // Get price based on selected tab
                let priceVal = 0;
                let periodLabel = '';
                let maintenanceLabel = '';
                
                if (tab === 'monthly') {
                    priceVal = plan.monthly;
                    periodLabel = '<span class="period">/mo</span>';
                } else if (tab === 'yearly') {
                    priceVal = plan.yearly;
                    periodLabel = '<span class="period">/yr</span>';
                    // Show savings
                    const monthlyTotal = plan.monthly * 12;
                    const savings = monthlyTotal - plan.yearly;
                    if (savings > 0 && plan.yearly > 0) {
                        maintenanceLabel = `<span style="color: #06D6A0; font-size: 0.7rem; display: block; margin-top: 2px;">Save $${savings.toFixed(2)}/yr</span>`;
                    }
                } else { // onetime
                    priceVal = plan.onetime;
                    periodLabel = '<span class="period">one-time</span>';
                    if (plan.maintenance_yearly > 0) {
                        maintenanceLabel = `<span style="color: #8899AA; font-size: 0.65rem; display: block; margin-top: 2px;">+ $${plan.maintenance_yearly.toFixed(2)}/yr maintenance</span>`;
                    }
                }

                const priceDisplay = priceVal === 0 ? 
                    '<span style="font-size: 2rem; font-weight: 800; color: #0f3f7b;">Free</span>' :
                    `<sup>$</sup>${priceVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

                const visibleFeatures = plan.features.slice(0, SHOW_INITIALLY);
                const hiddenFeatures = plan.features.slice(SHOW_INITIALLY);

                const renderFeature = (f) => `
                    <div class="feature-list-item ${f.yes ? '' : 'dimmed'}">
                        <i class="fas ${f.yes ? 'fa-check-circle yes' : 'fa-times-circle no'}"></i>
                        <span>${f.label}</span>
                    </div>`;

                const hiddenHTML = hiddenFeatures.length > 0 ? `
                    <div class="hidden-features" id="hidden-${plan.code}">
                        ${hiddenFeatures.map(renderFeature).join('')}
                    </div>
                    <button class="view-more-btn" id="viewmore-${plan.code}" onclick="toggleFeatures('${plan.code}')">
                        <i class="fas fa-chevron-down"></i> View ${hiddenFeatures.length} more features
                    </button>` : '';

                const btnClass = plan.popular ? 'btn-plan-primary' : 'btn-plan-outline';
                const btnText = priceVal === 0 ? 
                    '<i class="fas fa-rocket me-2"></i>Start Free Trial' : 
                    '<i class="fas fa-envelope me-2"></i>Get This Plan';
                const btnOnclick = priceVal === 0 ? 
                    `openModal('${plan.name}')` : 
                    `openModal('${plan.name}')`;

                return `
                    <div class="col-sm-6 col-xl-3 animate-on-scroll delay-${(i % 4) + 1}">
                        <div class="pricing-card ${plan.popular ? 'popular' : ''}">
                            ${plan.popular ? '<div class="popular-ribbon">⭐ Most Popular</div>' : ''}
                            <div class="pricing-header">
                                <div class="plan-name">${plan.name}</div>
                                <div class="plan-desc">${plan.desc}</div>
                                <div class="plan-price">${priceDisplay}${periodLabel}</div>
                                ${maintenanceLabel}
                                <div class="plan-trial"><i class="fas fa-check-circle me-1" style="color: #06D6A0;"></i>${plan.trial}</div>
                            </div>
                            <div class="pricing-features">
                                ${visibleFeatures.map(renderFeature).join('')}
                                ${hiddenHTML}
                            </div>
                            <div class="pricing-footer">
                                <button class="btn-plan ${btnClass}" onclick="${btnOnclick}">
                                    ${btnText}
                                </button>
                            </div>
                        </div>
                    </div>`;
            });

            container.innerHTML = cols.join('');
            
            // Re-run scroll animations
            if (typeof triggerScrollAnimations === 'function') {
                triggerScrollAnimations();
            }
        }

        function toggleFeatures(code) {
            const hidden = document.getElementById('hidden-' + code);
            const btn = document.getElementById('viewmore-' + code);
            if (!hidden || !btn) return;
            const isShown = hidden.classList.contains('shown');
            hidden.classList.toggle('shown');
            btn.innerHTML = isShown ?
                '<i class="fas fa-chevron-down"></i> View more features' :
                '<i class="fas fa-chevron-up"></i> Show less';
        }

        // ─── Billing Tabs ─────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize pricing
            renderPricing('monthly');

            // Billing tabs
            document.querySelectorAll('.billing-tab').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.billing-tab').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    renderPricing(this.dataset.tab);
                });
            });
        });
    </script>
    @stack('scripts')
</body>

</html>