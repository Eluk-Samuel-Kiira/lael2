<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>@yield('title', 'STARDENA SUITE – Global Point of Sale System')</title>
    <meta name="description" content="@yield('description', 'The world\'s fastest, most flexible POS system for retail, restaurants, hotels & more. Multi-currency, multi-language, multi-location.')">
    <meta name="keywords" content="POS system, point of sale, Uganda POS, Kenya POS, multi-currency POS, restaurant POS, retail POS">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://suite.stardena.org/">
    <meta property="og:title" content="STARDENA SUITE – Global POS System">
    <meta property="og:description" content="Lightning-fast checkout. Multi-currency. Multi-language. The complete point-of-sale platform.">

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
                    <li><a href="#hero" class="active">Home</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#industries">Industries</a></li>
                    <li><a href="#benefits">Benefits</a></li>
                    <li><a href="#pricing">Pricing</a></li>
                    <li><a href="#partners">Partners</a></li>
                    <li><a href="#faq">FAQ</a></li>
                    <li><a href="#contact">Contact</a></li>
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

    @stack('scripts')
</body>

</html>