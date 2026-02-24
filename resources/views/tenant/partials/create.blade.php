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
                    <div class="card-header">
                        <h2 class="card-title fw-bold">{{ __('payments.create_new_tenant') }}</h2>
                    </div>
                    
                    <form class="form" id="kt_tenant_create_form" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <!-- Basic Information Section -->
                                <div class="col-md-6">
                                    <div class="card card-flush mb-8">
                                        <div class="card-header">
                                            <h3 class="card-title">{{ __('payments.basic_information') }}</h3>
                                        </div>
                                        <div class="card-body">
                                            <!-- Tenant Name -->
                                            <div class="fv-row mb-8">
                                                <label class="required fs-6 fw-semibold mb-2">{{ __('payments.tenant_name') }}</label>
                                                <input type="text" 
                                                       class="form-control form-control-solid" 
                                                       placeholder="{{ __('payments.enter_tenant_name') }}" 
                                                       name="name"
                                                       id="name" />
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                            </div>
                                            
                                            <!-- Subdomain -->
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
                                            
                                            <!-- Status -->
                                            <div class="fv-row mb-8">
                                                <label class="required fs-6 fw-semibold mb-2">{{ __('payments.status') }}</label>
                                                <select class="form-select form-select-solid" name="status" id="status">
                                                    <option value="active">{{ __('payments.active') }}</option>
                                                    <option value="trial" selected>{{ __('payments.trial') }}</option>
                                                    <option value="suspended">{{ __('payments.suspended') }}</option>
                                                </select>
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Configuration Section -->
                                <div class="col-md-6">
                                    <div class="card card-flush mb-8">
                                        <div class="card-header">
                                            <h3 class="card-title">{{ __('payments.configuration') }}</h3>
                                        </div>
                                        <div class="card-body">
                                            <!-- Currency Code -->
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
                                            
                                            <!-- Timezone -->
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
                                            
                                            <!-- Locale -->
                                            <div class="fv-row mb-8">
                                                <label class="required fs-6 fw-semibold mb-2">{{ __('payments.locale') }}</label>
                                                <select class="form-select form-select-solid" name="locale" id="locale">
                                                    <option value="en">{{ __('payments.english') }}</option>
                                                    <option value="fr">{{ __('payments.french') }}</option>
                                                    <option value="es">{{ __('payments.spanish') }}</option>
                                                    <option value="de">{{ __('payments.german') }}</option>
                                                    <option value="it">{{ __('payments.italian') }}</option>
                                                </select>
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                            </div>
                                            
                                            <!-- Fiscal Year Start -->
                                            <div class="fv-row mb-8">
                                                <label class="required fs-6 fw-semibold mb-2">{{ __('payments.fiscal_year_start') }}</label>
                                                <input type="date" 
                                                       class="form-control form-control-solid" 
                                                       name="fiscal_year_start" 
                                                       id="fiscal_year_start"
                                                       value="{{ date('Y') }}-01-01" />
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                            </div>
                                            <!-- In the Configuration Section or create a new Billing Section -->
     
                                            <div class="fv-row mb-8">
                                                <label class="fs-6 fw-semibold mb-2">{{ __('payments.trial_ends_at') }}</label>
                                                <input type="date" 
                                                    class="form-control form-control-solid" 
                                                    name="trial_ends_at" 
                                                    id="trial_ends_at"
                                                    value="{{ old('trial_ends_at', now()->addDays(14)->format('Y-m-d')) }}" />
                                                <div class="form-text text-muted">{{ __('payments.trial_ends_at_help') }}</div>
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                            </div>
                                            
                                            <!-- Tax Calculation Method -->
                                            <div class="fv-row mb-8">
                                                <label class="required fs-6 fw-semibold mb-2">{{ __('payments.tax_calculation_method') }}</label>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6 w-100">
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
                                                        <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6 w-100">
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Plan Selection Section -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card card-flush mb-8">
                                        <div class="card-header">
                                            <h3 class="card-title">{{ __('payments.select_plan') }}</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-5 g-xl-8">
                                                @foreach($plans as $plan)
                                                    <div class="col-xl-3 col-md-6">
                                                        <div class="card card-flush h-100 {{ $plan->plan_code == 'enterprise' ? 'border border-primary' : '' }} plan-card" data-plan-id="{{ $plan->plan_id }}">
                                                            <div class="card-header pt-5">
                                                                <div class="card-title d-flex flex-column">
                                                                    <span class="fs-2hx fw-bold text-dark">{{ $plan->plan_name }}</span>
                                                                    <span class="text-gray-400 pt-1 fw-semibold fs-6">{{ $plan->description }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="card-body pt-0">
                                                                <div class="d-flex flex-column gap-1">
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="fs-3x fw-bold text-dark">
                                                                            @if($plan->monthly_price > 0)
                                                                                ${{ number_format($plan->monthly_price, 2) }}
                                                                            @elseif($plan->onetime_fee > 0)
                                                                                ${{ number_format($plan->onetime_fee, 2) }}
                                                                            @else
                                                                                Free
                                                                            @endif
                                                                        </span>
                                                                        @if($plan->monthly_price > 0)
                                                                            <span class="fs-7 text-gray-400 fw-semibold ms-2">/ month</span>
                                                                        @endif
                                                                    </div>
                                                                    
                                                                    @if($plan->annual_price > 0)
                                                                        <div class="text-gray-400 fw-semibold">
                                                                            ${{ number_format($plan->annual_price, 2) }}/year 
                                                                            (save {{ number_format($plan->getYearlySavingsPercentage(), 0) }}%)
                                                                        </div>
                                                                    @endif
                                                                    
                                                                    @if($plan->trial_days > 0)
                                                                        <div class="badge badge-light-success mt-3 py-2 px-3">
                                                                            {{ $plan->trial_days }} days free trial
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="separator separator-dashed my-6"></div>

                                                                <!-- Key Limits -->
                                                                <div class="mb-5">
                                                                    <h6 class="fw-bold mb-3">{{ __('Includes') }}:</h6>
                                                                    <div class="d-flex flex-column gap-2">
                                                                        <div class="d-flex align-items-center">
                                                                            <i class="ki-duotone ki-check-circle fs-2 text-success me-2"></i>
                                                                            <span>{{ $plan->default_shops >= 999999 ? __('Unlimited') : $plan->default_shops }} {{ __('Shops') }}</span>
                                                                        </div>
                                                                        <div class="d-flex align-items-center">
                                                                            <i class="ki-duotone ki-check-circle fs-2 text-success me-2"></i>
                                                                            <span>{{ $plan->default_locations >= 999999 ? __('Unlimited') : $plan->default_locations }} {{ __('Locations') }}</span>
                                                                        </div>
                                                                        <div class="d-flex align-items-center">
                                                                            <i class="ki-duotone ki-check-circle fs-2 text-success me-2"></i>
                                                                            <span>{{ $plan->default_users >= 999999 ? __('Unlimited') : $plan->default_users }} {{ __('Users') }}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Plan Radio Selection -->
                                                                <div class="form-check form-check-custom form-check-solid plan-selector">
                                                                    <input class="form-check-input" type="radio" name="plan_id" value="{{ $plan->plan_id }}" id="plan_{{ $plan->plan_id }}" {{ $loop->first ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="plan_{{ $plan->plan_id }}">
                                                                        {{ __('Select this plan') }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer text-end py-6">
                            <button type="button" class="btn btn-light me-3" onclick="window.location.href='{{ route('tenant.index') }}'">{{ __('auth._discard') }}</button>
                            <button type="button" class="btn btn-primary" id="submitTenantBtn">
                                <span class="indicator-label">{{ __('payments.create_tenant') }}</span>
                                <span class="indicator-progress" style="display: none;">
                                    {{ __('auth.please_wait') }} 
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
 
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        "use strict";
        
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('kt_tenant_create_form');
            const submitBtn = document.getElementById('submitTenantBtn');
            
            // Highlight selected plan
            const planCards = document.querySelectorAll('.plan-card');
            planCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remove highlight from all cards
                    planCards.forEach(c => c.classList.remove('border', 'border-primary'));
                    // Add highlight to selected card
                    this.classList.add('border', 'border-primary');
                    // Check the radio button
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) radio.checked = true;
                });
            });
            
            // Submit form
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Basic validation
                const name = form.querySelector('[name="name"]').value;
                const subdomain = form.querySelector('[name="subdomain"]').value;
                const planId = form.querySelector('[name="plan_id"]:checked');
                
                if (!name || !subdomain) {
                    Swal.fire({
                        text: 'Please fill in all required fields',
                        icon: 'error',
                        buttonsStyling: false,
                        confirmButtonText: 'OK',
                        customClass: { confirmButton: 'btn btn-danger' }
                    });
                    return;
                }
                
                if (!planId) {
                    Swal.fire({
                        text: 'Please select a plan',
                        icon: 'error',
                        buttonsStyling: false,
                        confirmButtonText: 'OK',
                        customClass: { confirmButton: 'btn btn-danger' }
                    });
                    return;
                }
                
                // Show loading
                submitBtn.querySelector('.indicator-label').style.display = 'none';
                submitBtn.querySelector('.indicator-progress').style.display = 'inline-block';
                submitBtn.disabled = true;
                
                // Submit form
                const formData = new FormData(form);
                
                fetch('{{ route("tenant.store") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            text: data.message,
                            icon: 'success',
                            buttonsStyling: false,
                            confirmButtonText: 'OK',
                            customClass: { confirmButton: 'btn btn-primary' }
                        }).then(() => {
                            window.location.href = data.redirect;
                        });
                    } else {
                        Swal.fire({
                            text: data.message || 'Validation failed',
                            icon: 'error',
                            buttonsStyling: false,
                            confirmButtonText: 'OK',
                            customClass: { confirmButton: 'btn btn-danger' }
                        });
                        
                        submitBtn.querySelector('.indicator-label').style.display = 'inline-block';
                        submitBtn.querySelector('.indicator-progress').style.display = 'none';
                        submitBtn.disabled = false;
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
                    
                    submitBtn.querySelector('.indicator-label').style.display = 'inline-block';
                    submitBtn.querySelector('.indicator-progress').style.display = 'none';
                    submitBtn.disabled = false;
                });
            });
        });
    </script>
    @endpush
    @endsection
</x-app-layout>