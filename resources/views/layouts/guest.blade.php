<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'STARDENA SUITE – Complete Business Management Platform | POS, Inventory, Manufacturing, HR')</title>
        <meta charset="utf-8" />
        <meta name="description" content="@yield('description', 'STARDENA SUITE is the all-in-one business management platform with POS, inventory, manufacturing, HR & payroll, multi-currency, and offline mode. Trusted by 5,000+ businesses in 30+ countries.')" />
        <meta name="keywords" content="STARDENA SUITE, POS system, business management platform, inventory management, manufacturing software, HR management, payroll system, multi-currency POS, offline POS, Uganda POS, Kenya POS, East Africa POS, restaurant POS, retail POS, pharmacy POS, hotel management system, supermarket POS, batch tracking, serial number tracking, production management, BOM software, supplier management, expense tracking, employee management, leave management, payroll software, accounting software, invoicing system, quotations software, multi-location POS, cloud POS, mobile POS" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <!-- Open Graph -->
        <meta property="og:locale" content="en_US" />
        <meta property="og:type" content="website" />
        <meta property="og:title" content="@yield('og_title', 'STARDENA SUITE – All-in-One Business Management & POS Platform')" />
        <meta property="og:description" content="@yield('og_description', 'Manage your entire business with STARDENA SUITE: POS, inventory, manufacturing, HR, payroll, multi-currency, and offline mode. Start your free trial today.')" />
        <meta property="og:url" content="{{ url()->current() }}" />
        <meta property="og:site_name" content="STARDENA SUITE" />
        <meta property="og:image" content="{{ getFaviconImage() }}" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="@yield('twitter_title', 'STARDENA SUITE – POS, Inventory, Manufacturing, HR & Payroll')" />
        <meta name="twitter:description" content="@yield('twitter_description', 'The complete business management platform with POS, inventory, manufacturing, HR, payroll, and offline mode. Start your free trial today.')" />
        <meta name="twitter:image" content="{{ asset('front/img/twitter-card.jpg') }}" />

        <!-- Favicon -->
        <link rel="icon" type="image/png" sizes="512x512" href="{{ getFaviconImage() }}" />
        <link rel="icon" type="image/png" sizes="192x192" href="{{ getFaviconImage() }}" />
        <link rel="icon" type="image/png" sizes="32x32" href="{{ getFaviconImage() }}" />
        <link rel="icon" type="image/png" sizes="16x16" href="{{ getFaviconImage() }}" />
        <link rel="shortcut icon" href="{{ getFaviconImage() }}" />

        <!-- Canonical -->
        <link rel="canonical" href="{{ url()->current() }}" />

        <!-- Author & Publisher -->
        <meta name="author" content="STARDENA SUITE" />
        <meta name="publisher" content="Stardena" />
        <meta name="robots" content="index, follow" />

        <!-- Geo Tags -->
        <meta name="geo.region" content="UG" />
        <meta name="geo.placename" content="Uganda, East Africa" />


		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
		<link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
		<link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
		<script>
            if (window.top != window.self) { window.top.location.replace(window.self.location.href); }
        </script>
        
        {{-- laravel bladeLive.js library --}}
        @include('layouts.liveblade-imports')
        
    </head>
	<body id="kt_body" class="app-blank">
        <div id="loader"></div>		
		<script>var defaultThemeMode = "light"; var themeMode; if ( document.documentElement ) { if ( document.documentElement.hasAttribute("data-bs-theme-mode")) { themeMode = document.documentElement.getAttribute("data-bs-theme-mode"); } else { if ( localStorage.getItem("data-bs-theme") !== null ) { themeMode = localStorage.getItem("data-bs-theme"); } else { themeMode = defaultThemeMode; } } if (themeMode === "system") { themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"; } document.documentElement.setAttribute("data-bs-theme", themeMode); }</script>

        <div class="d-flex flex-column flex-root" id="kt_app_root">
            <div class="d-flex flex-column flex-lg-row flex-column-fluid">
                <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1">
                    <div class="d-flex flex-center flex-column flex-lg-row-fluid">
                        <div class="w-lg-500px p-10">
                            @yield('content')
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-lg-row-fluid w-lg-50 bgi-size-cover bgi-position-center order-1 order-lg-2" style="background-image: url({{ asset('assets/media/misc/auth-bg.png') }} )">
                    <div class="d-flex flex-column flex-center py-7 py-lg-15 px-5 px-md-15 w-100">
                        {{-- <a href="{{ Route::currentRouteName() }}" class="mb-0 mb-lg-12">
                            <img alt="Logo" src="{{ asset('assets/media/logos/custom-1.png') }}" class="h-60px h-lg-75px" />
                        </a> --}}
                        <a href="//" data-link onclick="navigateToGuestPage('/')" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; color: white;">
                            {{--<img src="{{ getLogoImage() }}" style="height: 25px; display: inline-block;" class="app-sidebar-logo-default" /> --}}
                            <span style="font-weight: bold; font-size: 3rem; line-height: 1.2; margin-top: 8px; white-space: nowrap; color: white;">
                                {{ appDefaultName() }}
                            </span>
                        </a>

                        <img class="d-none d-lg-block mx-auto w-275px w-md-50 w-xl-500px mb-10 mb-lg-20" src="{{ asset('assets/media/misc/auth-screens.png') }}" alt="" />
                        <h1 class="d-none d-lg-block text-white fs-2qx fw-bolder text-center mb-7">{{__('auth._slogan')}}</h1>
                    </div>
                </div>
            </div>
        </div>

		<script>var hostUrl = "{{ asset('assets/') }}";</script>
		<script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
		<script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    </body>
</html>
