<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
	<head>
		<title>@yield('title')</title>
		<meta charset="utf-8" />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="The most advanced POS System in the world" />
        <meta name="keywords" content="Top 10 most used POS System in the world now" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta property="og:locale" content="en_US" />
        <meta property="og:type" content="LAEL POS SYSTEM" />
        <meta property="og:type" content="Point Of Sale As A Service" />
        <meta property="og:title" content="The most advanced POS System in the world" />
        <meta property="og:url" content="https://lael.stardena.org" />
        <meta property="og:url" content="https://lael.stardena.com" />
        <meta property="og:site_name" content="Lael by Stardena" />
        <link rel="canonical" href="https://lael.stardena.com" />

		<link rel="icon" type="image/png" sizes="512x512" href="{{ getFaviconImage() }}">
		<link rel="icon" type="image/png" sizes="192x192" href="{{ getFaviconImage() }}">
		<link rel="icon" type="image/png" sizes="32x32" href="{{ getFaviconImage() }}">
		<link rel="icon" type="image/png" sizes="16x16" href="{{ getFaviconImage() }}">

        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
        <link href="{{ asset('assets/plugins/custom/fullcalendar/fullcalendar.bundle.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
		<link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />

        <script>
            if (window.top != window.self) { window.top.location.replace(window.self.location.href); }
        </script>
        {{-- laravel bladeLive.js library --}}
		@include('layouts.liveblade-imports') 
    </head>
	@php
		use Illuminate\Support\Str;
	@endphp
	<body id="kt_app_body" data-kt-app-layout="dark-sidebar" 
		data-kt-app-header-fixed="true" 
		data-kt-app-sidebar-enabled="true" 
		data-kt-app-sidebar-fixed="true" 
		data-kt-app-sidebar-hoverable="true" 
		data-kt-app-sidebar-push-header="true" 
		data-kt-app-sidebar-push-toolbar="true" 
		data-kt-app-sidebar-push-footer="true" 
		data-kt-app-toolbar-enabled="true" class="app-default" 
		@if(Route::is('pos.index')) 
        	data-kt-app-sidebar-minimize="on" 
    	@endif
	>
		
        <div id="loader"></div>		
		<script>var defaultThemeMode = "light"; var themeMode; if ( document.documentElement ) { if ( document.documentElement.hasAttribute("data-bs-theme-mode")) { themeMode = document.documentElement.getAttribute("data-bs-theme-mode"); } else { if ( localStorage.getItem("data-bs-theme") !== null ) { themeMode = localStorage.getItem("data-bs-theme"); } else { themeMode = defaultThemeMode; } } if (themeMode === "system") { themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"; } document.documentElement.setAttribute("data-bs-theme", themeMode); }</script>


		<script>var defaultThemeMode = "light"; var themeMode; if ( document.documentElement ) { if ( document.documentElement.hasAttribute("data-bs-theme-mode")) { themeMode = document.documentElement.getAttribute("data-bs-theme-mode"); } else { if ( localStorage.getItem("data-bs-theme") !== null ) { themeMode = localStorage.getItem("data-bs-theme"); } else { themeMode = defaultThemeMode; } } if (themeMode === "system") { themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"; } document.documentElement.setAttribute("data-bs-theme", themeMode); }</script>
        <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
            <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
				@include('layouts.header')
				<div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                    <div id="kt_app_sidebar" class="app-sidebar flex-column"
                        style="background-color: {{ getMailOptions('menu_nav_color') }};"
                        data-kt-drawer="true"
                        data-kt-drawer-name="app-sidebar"
                        data-kt-drawer-activate="{default: true, lg: false}"
                        data-kt-drawer-overlay="true"
                        data-kt-drawer-width="225px"
                        data-kt-drawer-direction="start"
                        data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
						
                        <!--begin::Logo-->
						<div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
						
							<!--begin::Logo image-->
							<a href="{{ route('dashboard') }}">
								<img 
									alt="Logo" 
									src="{{ getLogoImage() }}" 
									class="h-6 w-auto object-contain app-sidebar-logo-default" 
									style="max-height: 44px;" 
								/>

								<img alt="Logo" src="assets/media/logos/default-small.svg" class="h-20px app-sidebar-logo-minimize" />
							</a>
							{{-- 	
							<h1 class="text-white font-bold text-lg app-sidebar-logo-default" style="font-size: 50px">
								{{ appDefaultName() }}
							</h1>
							--}}
							<!--end::Logo image-->

							<div id="kt_app_sidebar_toggle" class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary h-30px w-30px position-absolute top-50 start-100 translate-middle rotate" data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body" data-kt-toggle-name="app-sidebar-minimize">
								<i class="ki-duotone ki-black-left-line fs-3 rotate-180">
									<span class="path1"></span>
									<span class="path2"></span>
								</i>
							</div>
							
							<!--end::Sidebar toggle-->
						</div>
						<!--end::Logo-->

                        @include('layouts.navigation')
                    </div>
					<div class="app-main flex-column flex-row-fluid" id="kt_app_main">
						<div class="d-flex flex-column flex-column-fluid">
							@yield('content')
						</div>
					</div>
				</div>
			</div>
		</div>
		@stack('scripts')

		<script>
			document.addEventListener("DOMContentLoaded", function() {
				@if(session('toast'))
					var toastType = "{{ session('toast.type') }}";
					var toastMessage = "{{ session('toast.message') }}";

					// Trigger Metronic/Toastr toast
					toastr[toastType](toastMessage); // success, info, warning, error
				@endif
			});
		</script>

        
		<script>var hostUrl = "{{ asset('assets/') }}";</script>
		<script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
		<script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
		<script src="{{ asset('assets/plugins/custom/fullcalendar/fullcalendar.bundle.js') }}"></script>

		<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
		<script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
		<script src="https://cdn.amcharts.com/lib/5/percent.js"></script>
		<script src="https://cdn.amcharts.com/lib/5/radar.js"></script>
		<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
		<script src="https://cdn.amcharts.com/lib/5/map.js"></script>
		<script src="https://cdn.amcharts.com/lib/5/geodata/worldLow.js"></script>
		<script src="https://cdn.amcharts.com/lib/5/geodata/continentsLow.js"></script>
		<script src="https://cdn.amcharts.com/lib/5/geodata/usaLow.js"></script>
		<script src="https://cdn.amcharts.com/lib/5/geodata/worldTimeZonesLow.js"></script>
		<script src="https://cdn.amcharts.com/lib/5/geodata/worldTimeZoneAreasLow.js"></script>

		<script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
        <script src="{{ asset('assets/js/widgets.bundle.js') }}"></script>
		<script src="{{ asset('assets/js/custom/widgets.js') }}"></script>
		<script src="{{ asset('assets/js/custom/apps/chat/chat.js') }}"></script>
		<script src="{{ asset('assets/js/custom/utilities/modals/upgrade-plan.js') }}"></script>
		<script src="{{ asset('assets/js/custom/utilities/modals/create-app.js') }}"></script>
		<script src="{{ asset('assets/js/custom/utilities/modals/new-target.js') }}"></script>
		<script src="{{ asset('assets/js/custom/utilities/modals/users-search.js') }}"></script>

		<script src="{{ asset('assets/js/custom/apps/user-management/users/list/table.js') }}"></script>
		<script src="{{ asset('assets/js/custom/apps/user-management/users/list/export-users.js') }}"></script>
		<script src="{{ asset('assets/js/custom/apps/user-management/users/list/add.js') }}"></script>

		
		<script src="{{ asset('assets/js/custom/pages/general/pos.js') }}"></script>
		<script src="{{ asset('assets/js/widgets.bundle.js') }}"></script>
		<script src="{{ asset('assets/js/custom/widgets.js') }}"></script>
		<script src="{{ asset('assets/js/custom/apps/chat/chat.js') }}"></script>
		<script src="{{ asset('assets/js/custom/utilities/modals/upgrade-plan.js') }}"></script>
		<script src="{{ asset('assets/js/custom/utilities/modals/users-search.js') }}"></script>
        
	</body>
</html>

