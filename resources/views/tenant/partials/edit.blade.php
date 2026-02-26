<x-app-layout>
    @section('title', __('payments.edit_tenant'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('payments.edit_tenant')}}</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('tenant.index') }}" class="text-muted text-hover-primary">
                            {{ __('payments.tenants') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('payments.edit_tenant')}}</li>
                </ul>
            </div>
        </div>
    </div>

    @role('super_admin')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title fw-bold">{{ __('payments.edit_tenant') }}: {{ $tenant->name }}</h2>
                    </div>
                    
                    <form class="form" id="kt_tenant_edit_form" method="POST">
                        @csrf
                        @method('PUT')
                        
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
                                                       name="name"
                                                       value="{{ $tenant->name }}"
                                                       required />
                                            </div>
                                            
                                            <!-- Subdomain (Read-only) -->
                                            <div class="fv-row mb-8">
                                                <label class="fs-6 fw-semibold mb-2">{{ __('payments.subdomain') }}</label>
                                                <div class="input-group">
                                                    <input type="text" 
                                                           class="form-control form-control-solid" 
                                                           value="{{ $tenant->subdomain }}" 
                                                           readonly />
                                                    <span class="input-group-text">.{{ config('app.domain') }}</span>
                                                </div>
                                                <div class="form-text text-muted">{{ __('payments.subdomain_readonly') }}</div>
                                            </div>
                                            
                                            <!-- Status -->
                                            <div class="fv-row mb-8">
                                                <label class="required fs-6 fw-semibold mb-2">{{ __('payments.status') }}</label>
                                                <select class="form-select form-select-solid" name="status">
                                                    <option value="active" {{ $tenant->status == 'active' ? 'selected' : '' }}>{{ __('payments.active') }}</option>
                                                    <option value="trial" {{ $tenant->status == 'trial' ? 'selected' : '' }}>{{ __('payments.trial') }}</option>
                                                    <option value="suspended" {{ $tenant->status == 'suspended' ? 'selected' : '' }}>{{ __('payments.suspended') }}</option>
                                                </select>
                                            </div>
                                            
                                            <!-- Created At -->
                                            <div class="fv-row mb-8">
                                                <label class="fs-6 fw-semibold mb-2">{{ __('auth.created_at') }}</label>
                                                <input type="text" 
                                                       class="form-control form-control-solid" 
                                                       value="{{ $tenant->created_at->format('d M Y, h:i a') }}" 
                                                       readonly />
                                            </div>

                                            @php
                                                $trialEndsAtSetting = $tenant->settings()->where('setting_key', 'trial_ends_at')->first();
                                                $trialEndsAt = $trialEndsAtSetting ? $trialEndsAtSetting->setting_value : null;
                                            @endphp
                                            <!-- Trial Ends At -->
                                            <div class="fv-row mb-8">
                                                <label class="fs-6 fw-semibold mb-2">{{ __('payments.trial_ends_at') }}</label>
                                                <input type="date" 
                                                    class="form-control form-control-solid" 
                                                    name="trial_ends_at" 
                                                    id="trial_ends_at"
                                                    value="{{ old('trial_ends_at', $trialEndsAt ? date('Y-m-d', strtotime($trialEndsAt)) : '') }}" />
                                                <div class="form-text text-muted">{{ __('payments.trial_ends_at_help') }}</div>
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Current Plan Section -->
                                <div class="col-md-6">
                                    <div class="card card-flush mb-8">
                                        <div class="card-header">
                                            <h3 class="card-title">{{ __('payments.current_plan') }}</h3>
                                        </div>
                                        <div class="card-body">
                                            @if($currentPlan)
                                                <div class="bg-light-primary p-5 rounded mb-5">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-box-seam fs-2x text-primary me-3"></i>
                                                        <div>
                                                            <span class="fw-bold fs-4">{{ $currentPlan->plan_name }}</span>
                                                            <div class="text-muted">{{ $currentPlan->description }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-4">
                                                        <div class="col-6">
                                                            <span class="text-muted">{{ __('Monthly Price') }}:</span>
                                                            <span class="fw-bold">${{ number_format($currentPlan->monthly_price, 2) }}</span>
                                                        </div>
                                                        <div class="col-6">
                                                            <span class="text-muted">{{ __('Annual Price') }}:</span>
                                                            <span class="fw-bold">${{ number_format($currentPlan->annual_price, 2) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="alert alert-warning">
                                                    {{ __('payments.plan_not_found') }}
                                                </div>
                                            @endif
                                            
                                            <div class="form-text">
                                                {{ __('payments.plan_change_note') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Plan Change Section (Optional) -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card card-flush mb-8">
                                        <div class="card-header">
                                            <h3 class="card-title">{{ __('payments.change_plan') }}</h3>
                                            <div class="card-toolbar">
                                                <div class="form-check form-switch form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="checkbox" id="change_plan_switch" />
                                                    <label class="form-check-label" for="change_plan_switch">{{ __('Enable plan change') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body" id="plan_selection_section" style="display: none;">
                                            <div class="row g-5 g-xl-8">
                                                @foreach($plans as $plan)
                                                    <div class="col-xl-3 col-md-6">
                                                        <div class="card card-flush h-100 {{ $currentPlan && $currentPlan->plan_id == $plan->plan_id ? 'border border-primary' : '' }} plan-card" data-plan-id="{{ $plan->plan_id }}">
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
                                                                </div>
                                                                
                                                                <div class="separator separator-dashed my-6"></div>
                                                                
                                                                <!-- Plan Radio Selection -->
                                                                <div class="form-check form-check-custom form-check-solid plan-selector">
                                                                    <input class="form-check-input" type="radio" name="plan_id" value="{{ $plan->plan_id }}" id="plan_{{ $plan->plan_id }}" {{ $currentPlan && $currentPlan->plan_id == $plan->plan_id ? 'checked' : '' }}>
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
                            <a href="{{ route('tenant.index') }}" class="btn btn-light me-3">{{ __('auth._discard') }}</a>
                            <button type="button" class="btn btn-primary" id="updateTenantBtn">
                                <span class="indicator-label">{{ __('auth._update') }}</span>
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
    @endrole
 
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        "use strict";
        
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('kt_tenant_edit_form');
            const updateBtn = document.getElementById('updateTenantBtn');
            const changePlanSwitch = document.getElementById('change_plan_switch');
            const planSection = document.getElementById('plan_selection_section');
            
            // Toggle plan selection section
            changePlanSwitch.addEventListener('change', function() {
                if (this.checked) {
                    planSection.style.display = 'block';
                } else {
                    planSection.style.display = 'none';
                    // Uncheck all plan radios when hiding
                    document.querySelectorAll('input[name="plan_id"]').forEach(radio => {
                        radio.checked = false;
                    });
                }
            });
            
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
            
            // Update form submission
            updateBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Basic validation
                const name = form.querySelector('[name="name"]').value;
                
                if (!name) {
                    Swal.fire({
                        text: 'Please fill in all required fields',
                        icon: 'error',
                        buttonsStyling: false,
                        confirmButtonText: 'OK',
                        customClass: { confirmButton: 'btn btn-danger' }
                    });
                    return;
                }
                
                // Show loading
                updateBtn.querySelector('.indicator-label').style.display = 'none';
                updateBtn.querySelector('.indicator-progress').style.display = 'inline-block';
                updateBtn.disabled = true;
                
                // Submit form
                const formData = new FormData(form);
                
                fetch('{{ route("tenant.update", $tenant->id) }}', {
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
                            window.location.href = '{{ route("tenant.index") }}';
                        });
                    } else {
                        Swal.fire({
                            text: data.message || 'Update failed',
                            icon: 'error',
                            buttonsStyling: false,
                            confirmButtonText: 'OK',
                            customClass: { confirmButton: 'btn btn-danger' }
                        });
                        
                        updateBtn.querySelector('.indicator-label').style.display = 'inline-block';
                        updateBtn.querySelector('.indicator-progress').style.display = 'none';
                        updateBtn.disabled = false;
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
                    
                    updateBtn.querySelector('.indicator-label').style.display = 'inline-block';
                    updateBtn.querySelector('.indicator-progress').style.display = 'none';
                    updateBtn.disabled = false;
                });
            });
        });
    </script>
    @endpush
    @endsection
</x-app-layout>