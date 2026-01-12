<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title')</title>
        <meta charset="utf-8" />
        <meta name="description" content="The most advanced POS System in the world" />
        <meta name="keywords" content="Top 10 most used POS System in the world now" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta property="og:locale" content="en_US" />
        <meta property="og:type" content="LAEL POS SYSTEM" />
        <meta property="og:title" content="The most advanced POS System in the world" />
        <meta property="og:url" content="https://lael.stardena.com" />
        <meta property="og:site_name" content="Lael by Stardena" />
        <link rel="canonical" href="https://lael.stardena.com" />
		<link rel="shortcut icon" href="{{ asset('assets/media/logos/favicon.ico') }}" />
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
                        <h1 class="d-none d-lg-block text-white fs-2qx fw-bolder text-center mb-7">{{__('POINT OF SALE')}}</h1>
                    </div>
                </div>
            </div>
        </div>

		<script>var hostUrl = "{{ asset('assets/') }}";</script>
		<script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
		<script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    </body>
</html>
