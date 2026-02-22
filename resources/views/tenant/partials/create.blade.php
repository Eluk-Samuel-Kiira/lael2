<x-app-layout>
    @section('title', __('payments.new_tenant'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('payments.new_tenant')}}</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        @php
                            $previousUrl = url()->previous();
                            $previousRouteName = optional(app('router')->getRoutes()->match(request()->create($previousUrl)))->getName();
                            $formattedRouteName = $previousRouteName 
                                ? Str::of($previousRouteName)->replace('.', ' ')->title() 
                                : __('auth._back');
                        @endphp
                        <a href="{{ $previousUrl }}" class="text-muted text-hover-primary">
                            {{ $formattedRouteName }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('payments.new_tenant')}}</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                <div class="card">
                    <!--begin::Stepper-->
                    <div class="stepper stepper-links d-flex flex-column" id="kt_tenant_create_stepper">
                        <!--begin::Container-->
                        <div class="container">
                            <!--begin::Nav-->
                            <div class="stepper-nav justify-content-center py-2">
                                <!--begin::Step 1-->
                                <div class="stepper-item me-5 me-md-15 current" data-kt-stepper-element="nav">
                                    <h3 class="stepper-title">{{ __('payments.basic_information') }}</h3>
                                </div>
                                <!--end::Step 1-->
                                <!--begin::Step 2-->
                                <div class="stepper-item me-5 me-md-15" data-kt-stepper-element="nav">
                                    <h3 class="stepper-title">{{ __('payments.configuration') }}</h3>
                                </div>
                                <!--end::Step 2-->
                                <!--begin::Step 3-->
                                <div class="stepper-item me-5 me-md-15" data-kt-stepper-element="nav">
                                    <h3 class="stepper-title">{{ __('payments.settings') }}</h3>
                                </div>
                                <!--end::Step 3-->
                                <!--begin::Step 4-->
                                <div class="stepper-item" data-kt-stepper-element="nav">
                                    <h3 class="stepper-title">{{ __('payments.completed') }}</h3>
                                </div>
                                <!--end::Step 4-->
                            </div>
                            <!--end::Nav-->

                            <!--begin::Form-->
                            <form class="mx-auto w-100 mw-600px pt-15 pb-10" novalidate="novalidate" id="kt_tenant_create_form">
                                @csrf
                                
                                <!--begin::Step 1 - Basic Information-->
                                <div class="current" data-kt-stepper-element="content">
                                    <div class="w-100">
                                        <div class="pb-7 pb-lg-12">
                                            <h1 class="fw-bold text-gray-900">{{ __('payments.basic_information') }}</h1>
                                            <div class="text-muted fw-semibold fs-4">{{ __('payments.enter_basic_tenant_info') }}</div>
                                        </div>
                                        
                                        <div class="fv-row mb-8">
                                            <label class="required fs-6 fw-semibold mb-2">{{ __('payments.tenant_name') }}</label>
                                            <input type="text" 
                                                   class="form-control form-control-solid" 
                                                   placeholder="{{ __('payments.enter_tenant_name') }}" 
                                                   name="name"
                                                   id="name" />
                                            <div class="fv-plugins-message-container invalid-feedback"></div>
                                        </div>
                                        
                                        <div class="fv-row mb-8">
                                            <label class="required fs-6 fw-semibold mb-2">{{ __('payments.subdomain') }}</label>
                                            <div class="input-group">
                                                <input type="text" 
                                                       class="form-control form-control-solid" 
                                                       placeholder="{{ __('payments.enter_subdomain') }}" 
                                                       name="subdomain"
                                                       id="subdomain" />
                                                <span class="input-group-text">.{{ config('app.domain') }}</span>
                                            </div>
                                            <div class="fv-plugins-message-container invalid-feedback"></div>
                                        </div>
                                        
                                        <div class="fv-row mb-8">
                                            <label class="required fs-6 fw-semibold mb-2">{{ __('payments.status') }}</label>
                                            <select class="form-select form-select-solid" name="status" id="status">
                                                <option value="active">{{ __('payments.active') }}</option>
                                                <option value="trial" selected>{{ __('payments.trial') }}</option>
                                                <option value="suspended">{{ __('payments.suspended') }}</option>
                                            </select>
                                            <div class="fv-plugins-message-container invalid-feedback"></div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-lg btn-primary" id="step1_next">
                                                <span class="indicator-label">{{ __('payments.next') }} - {{ __('payments.configuration') }}</span>
                                                <span class="indicator-progress" style="display: none;">
                                                    {{ __('auth.please_wait') }} 
                                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Step 1-->

                                <!--begin::Step 2 - Configuration-->
                                <div data-kt-stepper-element="content">
                                    <div class="w-100">
                                        <div class="pb-12">
                                            <h1 class="fw-bold text-gray-900">{{ __('payments.configuration') }}</h1>
                                            <div class="text-muted fw-semibold fs-4">{{ __('payments.enter_configuration_details') }}</div>
                                        </div>
                                        
                                        <div class="fv-row mb-8">
                                            <label class="required fs-6 fw-semibold mb-2">{{ __('payments.currency_code') }}</label>
                                            <select class="form-select form-select-solid" name="currency_code" id="currency_code" data-control="select2" data-placeholder="Select a currency">
                                                <option value="">{{ __('payments.select_currency') }}</option>
                                                @foreach(config('currencies.currencies') as $code => $details)
                                                    <option value="{{ $code }}" {{ old('currency_code', 'USD') == $code ? 'selected' : '' }}>
                                                        {{ $code }} - {{ $details['name'] }} ({{ $details['symbol'] }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="fv-plugins-message-container invalid-feedback"></div>
                                        </div>
                                        
                                        <div class="fv-row mb-8">
                                            <label class="required fs-6 fw-semibold mb-2">{{ __('payments.timezone') }}</label>
                                            <select class="form-select form-select-solid" name="timezone" id="timezone">
                                                @php
                                                    $timezones = DateTimeZone::listIdentifiers();
                                                @endphp
                                                @foreach($timezones as $timezone)
                                                    <option value="{{ $timezone }}" {{ $timezone == 'Africa/Kampala' ? 'selected' : '' }}>{{ str_replace('_', ' ', $timezone) }}</option>
                                                @endforeach
                                            </select>
                                            <div class="fv-plugins-message-container invalid-feedback"></div>
                                        </div>
                                        
                                        <div class="fv-row mb-8">
                                            <label class="required fs-6 fw-semibold mb-2">{{ __('payments.locale') }}</label>
                                            <select class="form-select form-select-solid" name="locale" id="locale">
                                                <option value="en">English</option>
                                                <option value="fr">French</option>
                                                <option value="es">Spanish</option>
                                                <option value="de">German</option>
                                                <option value="it">Italian</option>
                                            </select>
                                            <div class="fv-plugins-message-container invalid-feedback"></div>
                                        </div>
                                        
                                        <div class="fv-row mb-8">
                                            <label class="required fs-6 fw-semibold mb-2">{{ __('payments.fiscal_year_start') }}</label>
                                            <input type="date" 
                                                   class="form-control form-control-solid" 
                                                   name="fiscal_year_start" 
                                                   id="fiscal_year_start"
                                                   value="{{ date('Y') }}-01-01" />
                                            <div class="fv-plugins-message-container invalid-feedback"></div>
                                        </div>
                                        
                                        <div class="fv-row mb-15">
                                            <label class="required fs-6 fw-semibold mb-2">{{ __('payments.tax_calculation_method') }}</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6">
                                                        <input class="btn-check" type="radio" name="tax_calculation_method" value="exclusive" checked />
                                                        <span class="d-flex">
                                                            <span class="ms-4">
                                                                <span class="fs-4 fw-bold text-gray-800 mb-2 d-block">{{ __('payments.exclusive') }}</span>
                                                                <span class="fw-semibold fs-7 text-gray-600">{{ __('payments.tax_exclusive_desc') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6">
                                                        <input class="btn-check" type="radio" name="tax_calculation_method" value="inclusive" />
                                                        <span class="d-flex">
                                                            <span class="ms-4">
                                                                <span class="fs-4 fw-bold text-gray-800 mb-2 d-block">{{ __('payments.inclusive') }}</span>
                                                                <span class="fw-semibold fs-7 text-gray-600">{{ __('payments.tax_inclusive_desc') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="fv-plugins-message-container invalid-feedback"></div>
                                        </div>
                                        
                                        <div class="d-flex flex-stack">
                                            <button type="button" class="btn btn-lg btn-light me-3" id="step2_previous">
                                                {{ __('payments.previous') }} - {{ __('payments.basic_information') }}
                                            </button>
                                            <button type="button" class="btn btn-lg btn-primary" id="step2_next">
                                                <span class="indicator-label">{{ __('payments.next') }} - {{ __('payments.settings') }}</span>
                                                <span class="indicator-progress" style="display: none;">
                                                    {{ __('auth.please_wait') }} 
                                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Step 2-->

                                <!--begin::Step 3 - Settings-->
                                <div data-kt-stepper-element="content">
                                    <div class="w-100">
                                        <div class="pb-12">
                                            <h1 class="fw-bold text-gray-900">{{ __('payments.settings') }}</h1>
                                            <div class="text-muted fw-semibold fs-4">{{ __('payments.configure_tenant_settings') }}</div>
                                        </div>
                                        
                                        <div class="row">
                                            <!-- Limits Section -->
                                            <div class="col-md-6">
                                                <div class="card card-flush mb-8">
                                                    <div class="card-header">
                                                        <h3 class="card-title">{{ __('payments.limits') }}</h3>
                                                    </div>
                                                    <div class="card-body">
                                                        <!-- Max Users -->
                                                        <div class="fv-row mb-8">
                                                            <label class="fs-6 fw-semibold mb-2">{{ __('payments.max_users') }}</label>
                                                            <input type="number" 
                                                                class="form-control form-control-solid" 
                                                                name="settings[0][value]" 
                                                                value="10" 
                                                                min="1" />
                                                            <input type="hidden" name="settings[0][key]" value="max_users" />
                                                            <input type="hidden" name="settings[0][data_type]" value="integer" />
                                                            <input type="hidden" name="settings[0][category]" value="limits" />
                                                        </div>
                                                        
                                                        <!-- Max Products -->
                                                        <div class="fv-row mb-8">
                                                            <label class="fs-6 fw-semibold mb-2">{{ __('payments.max_products') }}</label>
                                                            <input type="number" 
                                                                class="form-control form-control-solid" 
                                                                name="settings[1][value]" 
                                                                value="1000" 
                                                                min="1" />
                                                            <input type="hidden" name="settings[1][key]" value="max_products" />
                                                            <input type="hidden" name="settings[1][data_type]" value="integer" />
                                                            <input type="hidden" name="settings[1][category]" value="limits" />
                                                        </div>
                                                        
                                                        <!-- Max Departments -->
                                                        <div class="fv-row mb-8">
                                                            <label class="fs-6 fw-semibold mb-2">{{ __('payments.max_departments') }}</label>
                                                            <input type="number" 
                                                                class="form-control form-control-solid" 
                                                                name="settings[2][value]" 
                                                                value="5" 
                                                                min="1" />
                                                            <input type="hidden" name="settings[2][key]" value="max_departments" />
                                                            <input type="hidden" name="settings[2][data_type]" value="integer" />
                                                            <input type="hidden" name="settings[2][category]" value="limits" />
                                                        </div>
                                                        
                                                        <!-- Max Locations -->
                                                        <div class="fv-row mb-8">
                                                            <label class="fs-6 fw-semibold mb-2">{{ __('payments.max_locations') }}</label>
                                                            <input type="number" 
                                                                class="form-control form-control-solid" 
                                                                name="settings[3][value]" 
                                                                value="3" 
                                                                min="1" />
                                                            <input type="hidden" name="settings[3][key]" value="max_locations" />
                                                            <input type="hidden" name="settings[3][data_type]" value="integer" />
                                                            <input type="hidden" name="settings[3][category]" value="limits" />
                                                        </div>
                                                        
                                                        <!-- Max Employees -->
                                                        <div class="fv-row mb-8">
                                                            <label class="fs-6 fw-semibold mb-2">{{ __('payments.max_employees') }}</label>
                                                            <input type="number" 
                                                                class="form-control form-control-solid" 
                                                                name="settings[4][value]" 
                                                                value="20" 
                                                                min="1" />
                                                            <input type="hidden" name="settings[4][key]" value="max_employees" />
                                                            <input type="hidden" name="settings[4][data_type]" value="integer" />
                                                            <input type="hidden" name="settings[4][category]" value="limits" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Features Section -->
                                            <div class="col-md-6">
                                                <div class="card card-flush mb-8">
                                                    <div class="card-header">
                                                        <h3 class="card-title">{{ __('payments.features') }}</h3>
                                                    </div>
                                                    <div class="card-body">
                                                        <!-- Enable Inventory -->
                                                        <div class="fv-row mb-8">
                                                            <label class="fs-6 fw-semibold mb-2">{{ __('payments.enable_inventory') }}</label>
                                                            <select class="form-control form-control-solid" name="settings[5][value]">
                                                                <option value="1" selected>{{ __('payments.yes') }}</option>
                                                                <option value="0">{{ __('payments.no') }}</option>
                                                            </select>
                                                            <input type="hidden" name="settings[5][key]" value="enable_inventory" />
                                                            <input type="hidden" name="settings[5][data_type]" value="boolean" />
                                                            <input type="hidden" name="settings[5][category]" value="features" />
                                                        </div>
                                                        
                                                        <!-- Enable Multi Location -->
                                                        <div class="fv-row mb-8">
                                                            <label class="fs-6 fw-semibold mb-2">{{ __('payments.enable_multi_location') }}</label>
                                                            <select class="form-control form-control-solid" name="settings[6][value]">
                                                                <option value="1">{{ __('payments.yes') }}</option>
                                                                <option value="0" selected>{{ __('payments.no') }}</option>
                                                            </select>
                                                            <input type="hidden" name="settings[6][key]" value="enable_multi_location" />
                                                            <input type="hidden" name="settings[6][data_type]" value="boolean" />
                                                            <input type="hidden" name="settings[6][category]" value="features" />
                                                        </div>
                                                        
                                                        <!-- Enable Reports -->
                                                        <div class="fv-row mb-8">
                                                            <label class="fs-6 fw-semibold mb-2">{{ __('payments.enable_reports') }}</label>
                                                            <select class="form-control form-control-solid" name="settings[7][value]">
                                                                <option value="1" selected>{{ __('payments.yes') }}</option>
                                                                <option value="0">{{ __('payments.no') }}</option>
                                                            </select>
                                                            <input type="hidden" name="settings[7][key]" value="enable_reports" />
                                                            <input type="hidden" name="settings[7][data_type]" value="boolean" />
                                                            <input type="hidden" name="settings[7][category]" value="features" />
                                                        </div>
                                                        
                                                        <!-- Enable API -->
                                                        <div class="fv-row mb-8">
                                                            <label class="fs-6 fw-semibold mb-2">{{ __('payments.enable_api') }}</label>
                                                            <select class="form-control form-control-solid" name="settings[8][value]">
                                                                <option value="1" selected>{{ __('payments.yes') }}</option>
                                                                <option value="0">{{ __('payments.no') }}</option>
                                                            </select>
                                                            <input type="hidden" name="settings[8][key]" value="enable_api" />
                                                            <input type="hidden" name="settings[8][data_type]" value="boolean" />
                                                            <input type="hidden" name="settings[8][category]" value="features" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Billing Section -->
                                            <div class="col-md-12">
                                                <div class="card card-flush mb-8">
                                                    <div class="card-header">
                                                        <h3 class="card-title">{{ __('payments.billing') }}</h3>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <!-- Billing Plan -->
                                                                <div class="fv-row mb-8">
                                                                    <label class="fs-6 fw-semibold mb-2">{{ __('payments.billing_plan') }}</label>
                                                                    <select class="form-control form-control-solid" name="settings[9][value]">
                                                                        <option value="starter" selected>Starter</option>
                                                                        <option value="professional">Professional</option>
                                                                        <option value="enterprise">Enterprise</option>
                                                                    </select>
                                                                    <input type="hidden" name="settings[9][key]" value="billing_plan" />
                                                                    <input type="hidden" name="settings[9][data_type]" value="string" />
                                                                    <input type="hidden" name="settings[9][category]" value="billing" />
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <!-- Subscription Status -->
                                                                <div class="fv-row mb-8">
                                                                    <label class="fs-6 fw-semibold mb-2">{{ __('payments.subscription_status') }}</label>
                                                                    <select class="form-control form-control-solid" name="settings[10][value]">
                                                                        <option value="trial" selected>Trial</option>
                                                                        <option value="active">Active</option>
                                                                        <option value="suspended">Suspended</option>
                                                                    </select>
                                                                    <input type="hidden" name="settings[10][key]" value="subscription_status" />
                                                                    <input type="hidden" name="settings[10][data_type]" value="string" />
                                                                    <input type="hidden" name="settings[10][category]" value="billing" />
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <!-- Trial Ends At -->
                                                                <div class="fv-row mb-8">
                                                                    <label class="fs-6 fw-semibold mb-2">{{ __('payments.trial_ends_at') }}</label>
                                                                    <input type="date" 
                                                                        class="form-control form-control-solid" 
                                                                        name="settings[11][value]" 
                                                                        value="{{ now()->addDays(14)->format('Y-m-d') }}" />
                                                                    <input type="hidden" name="settings[11][key]" value="trial_ends_at" />
                                                                    <input type="hidden" name="settings[11][data_type]" value="string" />
                                                                    <input type="hidden" name="settings[11][category]" value="billing" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex flex-stack mt-15">
                                            <button type="button" class="btn btn-lg btn-light me-3" id="step3_previous">
                                                {{ __('payments.previous') }} - {{ __('payments.configuration') }}
                                            </button>
                                            <button type="button" class="btn btn-lg btn-primary" id="step3_submit">
                                                <span class="indicator-label">{{ __('payments.create_tenant') }}</span>
                                                <span class="indicator-progress" style="display: none;">
                                                    {{ __('auth.please_wait') }} 
                                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Step 3-->

                                <!--begin::Step 4 - Completed-->
                                <div data-kt-stepper-element="content">
                                    <div class="w-100">
                                        <div class="pb-12 text-center">
                                            <h1 class="fw-bold text-gray-900">{{ __('payments.tenant_created') }}</h1>
                                            <div class="text-muted fw-semibold fs-4">{{ __('payments.tenant_created_success') }}</div>
                                        </div>
                                        
                                        <div class="d-flex flex-center pb-20">
                                            <a href="{{ route('tenant.create') }}" class="btn btn-lg btn-light me-3">
                                                {{ __('payments.create_new_tenant') }}
                                            </a>
                                            <a href="{{ route('tenant.index') }}" class="btn btn-lg btn-primary">
                                                {{ __('payments.view_tenants') }}
                                            </a>
                                        </div>
                                        
                                        <div class="text-center px-4">
                                            <img src="{{ asset('assets/media/illustrations/sketchy-1/9.png') }}" alt="" class="mw-100 mh-350px" />
                                        </div>
                                    </div>
                                </div>
                                <!--end::Step 4-->
                            </form>
                            <!--end::Form-->
                        </div>
                        <!--end::Container-->
                    </div>
                    <!--end::Stepper-->
                </div>
            </div>
        </div>
    </div>
 
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        "use strict";
        
        document.addEventListener('DOMContentLoaded', function() {
            // Elements
            const form = document.querySelector('#kt_tenant_create_form');
            const stepperElement = document.querySelector('#kt_tenant_create_stepper');
            
            // Buttons
            const step1Next = document.querySelector('#step1_next');
            const step2Previous = document.querySelector('#step2_previous');
            const step2Next = document.querySelector('#step2_next');
            const step3Previous = document.querySelector('#step3_previous');
            const step3Submit = document.querySelector('#step3_submit');
            
            // Content sections
            const stepContents = document.querySelectorAll('[data-kt-stepper-element="content"]');
            const stepNavs = document.querySelectorAll('[data-kt-stepper-element="nav"]');
            
            let tenantId = null;
            let currentStep = 0;

            // Check for existing tenant session
            function checkExistingSession() {
                fetch('{{ route("tenant.current-step") }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.has_session) {
                            tenantId = data.tenant_id;
                            
                            // Show resume dialog
                            Swal.fire({
                                title: 'Resume Tenant Creation?',
                                text: 'You have an incomplete tenant creation. Would you like to continue?',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: 'Yes, continue',
                                cancelButtonText: 'No, start over',
                                buttonsStyling: false,
                                customClass: {
                                    confirmButton: 'btn btn-primary',
                                    cancelButton: 'btn btn-light'
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Resume from where they left off
                                    if (data.step > 1) {
                                        // Pre-fill step 1 data
                                        if (data.data && data.data.tenant) {
                                            form.querySelector('[name="name"]').value = data.data.tenant.name || '';
                                            form.querySelector('[name="subdomain"]').value = data.data.tenant.subdomain || '';
                                            form.querySelector('[name="status"]').value = data.data.tenant.status || 'trial';
                                        }
                                        
                                        // Pre-fill step 2 data if available
                                        if (data.data && data.data.configuration) {
                                            form.querySelector('[name="currency_code"]').value = data.data.configuration.currency_code || 'USD';
                                            form.querySelector('[name="timezone"]').value = data.data.configuration.timezone || 'Africa/Kampala';
                                            form.querySelector('[name="locale"]').value = data.data.configuration.locale || 'en';
                                            form.querySelector('[name="fiscal_year_start"]').value = data.data.configuration.fiscal_year_start || '{{ date("Y") }}-01-01';
                                            
                                            const taxMethod = data.data.configuration.tax_calculation_method;
                                            if (taxMethod) {
                                                const radio = form.querySelector(`[name="tax_calculation_method"][value="${taxMethod}"]`);
                                                if (radio) radio.checked = true;
                                            }
                                        }
                                        
                                        // Go to the appropriate step
                                        window.tenantStepper.goTo(data.step - 1);
                                    }
                                } else {
                                    // Start over - reset session
                                    fetch('{{ route("tenant.reset-step") }}', {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Content-Type': 'application/json'
                                        }
                                    });
                                }
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error checking session:', error);
                    });
            }

            
            // Initialize - show only first step
            function showStep(stepIndex) {
                // Hide all content sections
                stepContents.forEach((content, index) => {
                    if (index === stepIndex) {
                        content.classList.add('current');
                        content.style.display = 'block';
                    } else {
                        content.classList.remove('current');
                        content.style.display = 'none';
                    }
                });
                
                // Update nav items
                stepNavs.forEach((nav, index) => {
                    if (index === stepIndex) {
                        nav.classList.add('current');
                    } else {
                        nav.classList.remove('current');
                    }
                });
                
                currentStep = stepIndex;
            }
            
            // Initialize KTStepper but we'll control it manually
            if (typeof KTStepper !== 'undefined') {
                const stepper = new KTStepper(stepperElement);
                
                // Override KTStepper methods to use our showStep
                stepper.goNext = function() {
                    if (currentStep < stepContents.length - 1) {
                        showStep(currentStep + 1);
                    }
                };
                
                stepper.goPrevious = function() {
                    if (currentStep > 0) {
                        showStep(currentStep - 1);
                    }
                };
                
                stepper.goTo = function(index) {
                    if (index >= 0 && index < stepContents.length) {
                        showStep(index);
                    }
                };
                
                // Store stepper in a variable we can use
                window.tenantStepper = stepper;
            } else {
                console.error('KTStepper not found');
                return;
            }
            
            // Show first step initially
            showStep(0);
            
            // Step 1 Next
            step1Next.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Show loading
                step1Next.querySelector('.indicator-label').style.display = 'none';
                step1Next.querySelector('.indicator-progress').style.display = 'inline-block';
                step1Next.disabled = true;
                
                // Submit step 1
                const formData = new FormData();
                formData.append('name', form.querySelector('[name="name"]').value);
                formData.append('subdomain', form.querySelector('[name="subdomain"]').value);
                formData.append('status', form.querySelector('[name="status"]').value);
                formData.append('_token', '{{ csrf_token() }}');
                
                fetch('{{ route("tenant.step1") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        tenantId = data.tenant_id;
                        window.tenantStepper.goNext();
                    } else {
                        Swal.fire({
                            text: data.message || 'Validation failed',
                            icon: 'error',
                            buttonsStyling: false,
                            confirmButtonText: 'OK',
                            customClass: { confirmButton: 'btn btn-danger' }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        text: 'An error occurred',
                        icon: 'error',
                        buttonsStyling: false,
                        confirmButtonText: 'OK',
                        customClass: { confirmButton: 'btn btn-danger' }
                    });
                })
                .finally(() => {
                    step1Next.querySelector('.indicator-label').style.display = 'inline-block';
                    step1Next.querySelector('.indicator-progress').style.display = 'none';
                    step1Next.disabled = false;
                });
            });
            
            // Step 2 Previous
            step2Previous.addEventListener('click', function(e) {
                e.preventDefault();
                window.tenantStepper.goPrevious();
            });
            
            // Step 2 Next
            step2Next.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Show loading
                step2Next.querySelector('.indicator-label').style.display = 'none';
                step2Next.querySelector('.indicator-progress').style.display = 'inline-block';
                step2Next.disabled = true;
                
                // Submit step 2
                const formData = new FormData();
                formData.append('currency_code', form.querySelector('[name="currency_code"]').value);
                formData.append('timezone', form.querySelector('[name="timezone"]').value);
                formData.append('locale', form.querySelector('[name="locale"]').value);
                formData.append('fiscal_year_start', form.querySelector('[name="fiscal_year_start"]').value);
                formData.append('tax_calculation_method', form.querySelector('[name="tax_calculation_method"]:checked')?.value || 'exclusive');
                formData.append('_token', '{{ csrf_token() }}');
                
                fetch('{{ route("tenant.step2") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.tenantStepper.goNext();
                    } else {
                        Swal.fire({
                            text: data.message || 'Validation failed',
                            icon: 'error',
                            buttonsStyling: false,
                            confirmButtonText: 'OK',
                            customClass: { confirmButton: 'btn btn-danger' }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        text: 'An error occurred',
                        icon: 'error',
                        buttonsStyling: false,
                        confirmButtonText: 'OK',
                        customClass: { confirmButton: 'btn btn-danger' }
                    });
                })
                .finally(() => {
                    step2Next.querySelector('.indicator-label').style.display = 'inline-block';
                    step2Next.querySelector('.indicator-progress').style.display = 'none';
                    step2Next.disabled = false;
                });
            });
            
            
            // Step 3 Previous
            step3Previous.addEventListener('click', function(e) {
                e.preventDefault();
                window.tenantStepper.goPrevious();
            });
            
            // Step 3 Submit
            step3Submit.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Collect settings
                const settings = [];
                const settingInputs = form.querySelectorAll('[name^="settings["]');
                const settingsData = {};
                
                for (let i = 0; i < settingInputs.length; i++) {
                    const input = settingInputs[i];
                    const name = input.getAttribute('name');
                    
                    if (name.match(/settings\[\d+\]\[value\]/)) {
                        const index = name.match(/\d+/)[0];
                        if (!settingsData[index]) settingsData[index] = {};
                        settingsData[index].value = input.value;
                    }
                    
                    if (name.match(/settings\[\d+\]\[key\]/)) {
                        const index = name.match(/\d+/)[0];
                        if (!settingsData[index]) settingsData[index] = {};
                        settingsData[index].key = input.value;
                    }
                    
                    if (name.match(/settings\[\d+\]\[data_type\]/)) {
                        const index = name.match(/\d+/)[0];
                        if (!settingsData[index]) settingsData[index] = {};
                        settingsData[index].data_type = input.value;
                    }
                    
                    if (name.match(/settings\[\d+\]\[category\]/)) {
                        const index = name.match(/\d+/)[0];
                        if (!settingsData[index]) settingsData[index] = {};
                        settingsData[index].category = input.value;
                    }
                }
                
                // Convert to array
                for (let key in settingsData) {
                    settings.push(settingsData[key]);
                }
                
                // Show loading
                step3Submit.querySelector('.indicator-label').style.display = 'none';
                step3Submit.querySelector('.indicator-progress').style.display = 'inline-block';
                step3Submit.disabled = true;
                
                // Submit step 3
                fetch('{{ route("tenant.step3") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ settings: settings })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.tenantStepper.goNext();
                        if (data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 3000);
                        }
                    } else {
                        Swal.fire({
                            text: data.message || 'Failed to save settings',
                            icon: 'error',
                            buttonsStyling: false,
                            confirmButtonText: 'OK',
                            customClass: { confirmButton: 'btn btn-danger' }
                        });
                        
                        step3Submit.querySelector('.indicator-label').style.display = 'inline-block';
                        step3Submit.querySelector('.indicator-progress').style.display = 'none';
                        step3Submit.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        text: 'An error occurred',
                        icon: 'error',
                        buttonsStyling: false,
                        confirmButtonText: 'OK',
                        customClass: { confirmButton: 'btn btn-danger' }
                    });
                    
                    step3Submit.querySelector('.indicator-label').style.display = 'inline-block';
                    step3Submit.querySelector('.indicator-progress').style.display = 'none';
                    step3Submit.disabled = false;
                });
            });
        });
        
        checkExistingSession();
    </script>
    @endpush
    @endsection
</x-app-layout>