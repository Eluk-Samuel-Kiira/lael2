<x-app-layout>
    @section('title', __('auth.setting_index'))
    @section('content')
        
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('auth.general_setting')}}</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        @php
                            $previousUrl = url()->previous();
                            $previousRouteName = optional(app('router')->getRoutes()->match(request()->create($previousUrl)))->getName();
                            $formattedRouteName = $previousRouteName 
                                ? Str::of($previousRouteName)->replace('.', ' ')->title() 
                                : __('Back');
                        @endphp
                        <a href="{{ $previousUrl }}" class="text-muted text-hover-primary">
                            {{ $formattedRouteName }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('auth.basic_setting')}}</li>
                </ul>
            </div>
        </div>
    </div>

    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                
                <!--begin::Content-->
                <div id="kt_app_content" class="app-content flex-column-fluid">
                    <div id="kt_app_content_container" class="app-container container-xxl">
                        <div class="card card-flush">
                            <div class="card-body">
                                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-transparent fs-4 fw-semibold mb-15">
                                    <li class="nav-item">
                                        <a class="nav-link text-active-primary d-flex align-items-center pb-5 active" data-bs-toggle="tab" href="#lael_logo_favicon">
                                        <i class="ki-duotone ki-home fs-2 me-2"></i>{{__('auth.logo_favicon') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link text-active-primary d-flex align-items-center pb-5" data-bs-toggle="tab" href="#lael_app_info">
                                        <i class="ki-duotone ki-shop fs-2 me-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                            <span class="path5"></span>
                                        </i>{{__('auth.app_info') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link text-active-primary d-flex align-items-center pb-5" data-bs-toggle="tab" href="#lael_smtp_info">
                                        <i class="ki-duotone ki-compass fs-2 me-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>{{__('auth.smtp_info') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link text-active-primary d-flex align-items-center pb-5" data-bs-toggle="tab" href="#lael_meta_info">
                                        <i class="ki-duotone ki-package fs-2 me-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>{{__('auth.meta_info') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link text-active-primary d-flex align-items-center pb-5" data-bs-toggle="tab" href="#lael_themes">
                                        <i class="ki-duotone ki-people fs-2 me-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                            <span class="path5"></span>
                                        </i>{{__('auth.app_themes') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link text-active-primary d-flex align-items-center pb-5" data-bs-toggle="tab" href="#lael_locale">
                                        <i class="ki-duotone ki-airplane fs-2 me-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                            <span class="path5"></span>
                                        </i>{{__('auth.app_locale') }}</a>
                                    </li>
                                </ul>
                                <div class="tab-content" id="myTabContent">

                                    <!-- Logo & Favicon-->
                                    <div class="tab-pane fade show active" id="lael_logo_favicon" role="tabpanel">
                                        <form id="kt_ecommerce_settings_general_form" class="form" action="#">
                                            <div class="row mb-7">
                                                <div class="col-md-9 offset-md-3">
                                                    <h2>{{__('auth.logo_favicon') }}</h2>
                                                </div>
                                            </div>
                                            @include('settings.partials.logo-favicon')
                                        </form>
                                    </div>

                                    <!-- App Info -->
                                    <div class="tab-pane fade" id="lael_app_info" role="tabpanel">
                                        <div class="row mb-7">
                                            <div class="col-md-9 offset-md-3">
                                                <h2>{{__('auth.app_info') }}</h2>
                                            </div>
                                        </div>
                                        @include('settings.partials.app-info')
                                    </div>

                                    <!-- SMTP Info -->
                                    <div class="tab-pane fade" id="lael_smtp_info" role="tabpanel">
                                        <div class="row mb-7">
                                            <div class="col-md-9 offset-md-3">
                                                <h2>{{__('auth.smtp_info') }}</h2>
                                            </div>
                                        </div>
                                        @include('settings.partials.smtp-info')
                                    </div>
                                    
                                    <!-- Meta Info -->
                                    <div class="tab-pane fade" id="lael_meta_info" role="tabpanel">
                                        <div class="row mb-7">
                                            <div class="col-md-9 offset-md-3">
                                                <h2>{{__('auth.meta_info') }}</h2>
                                            </div>
                                        </div>
                                        @include('settings.partials.meta-info')
                                    </div>

                                    <!-- Themes-->
                                    <div class="tab-pane fade" id="lael_themes" role="tabpanel">
                                        <div class="row mb-7">
                                            <div class="col-md-9 offset-md-3">
                                                <h2>{{__('auth.app_themes') }}</h2>
                                            </div>
                                        </div>
                                        @include('settings.partials.themes')
                                    </div>

                                    <!-- Locale-->
                                    <div class="tab-pane fade" id="lael_locale" role="tabpanel">
                                        <div class="row mb-7">
                                            <div class="col-md-9 offset-md-3">
                                                <h2>{{__('auth.select_lang') }}</h2>
                                            </div>
                                        </div>
                                        @include('settings.partials.locale')
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @endsection
</x-app-layout>
