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
                
                <!-- Display validation errors -->
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Display session messages -->
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title fw-bold">{{ __('payments.edit_tenant') }}: {{ $tenant->name }}</h2>
                    </div>
                    
                    <form class="form" action="{{ route('tenant.update', $tenant->id) }}" method="POST">
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
                                                       class="form-control form-control-solid @error('name') is-invalid @enderror" 
                                                       name="name"
                                                       value="{{ old('name', $tenant->name) }}"
                                                       required />
                                                @error('name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
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
                                                <select class="form-select form-select-solid @error('status') is-invalid @enderror" name="status">
                                                    <option value="active" {{ old('status', $tenant->status) == 'active' ? 'selected' : '' }}>{{ __('payments.active') }}</option>
                                                    <option value="trial" {{ old('status', $tenant->status) == 'trial' ? 'selected' : '' }}>{{ __('payments.trial') }}</option>
                                                    <option value="suspended" {{ old('status', $tenant->status) == 'suspended' ? 'selected' : '' }}>{{ __('payments.suspended') }}</option>
                                                </select>
                                                @error('status')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
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
                                                    class="form-control form-control-solid @error('trial_ends_at') is-invalid @enderror" 
                                                    name="trial_ends_at" 
                                                    id="trial_ends_at"
                                                    value="{{ old('trial_ends_at', $trialEndsAt ? date('Y-m-d', strtotime($trialEndsAt)) : '') }}" />
                                                <div class="form-text text-muted">{{ __('payments.trial_ends_at_help') }}</div>
                                                @error('trial_ends_at')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
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
                            
                            <!-- Plan Selection Section - Always Visible -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card card-flush mb-8">
                                        <div class="card-header">
                                            <h3 class="card-title">{{ __('payments.change_plan') }}</h3>
                                            <div class="card-toolbar">
                                                <span class="badge badge-light-info">{{ __('Select new plan below') }}</span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-5 g-xl-8">
                                                @foreach($plans as $plan)
                                                    <div class="col-xl-3 col-md-6 mb-5">
                                                        <div class="card card-flush h-100 {{ $currentPlan && $currentPlan->plan_id == $plan->plan_id ? 'border border-primary' : '' }} plan-card" data-plan-id="{{ $plan->plan_id }}" style="cursor: pointer;">
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
                            <button type="submit" class="btn btn-primary">
                                <span>{{ __('auth._update') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endrole
 
    @push('scripts')
    <script>
        "use strict";
        
        document.addEventListener('DOMContentLoaded', function() {
            // Plan card selection
            const planCards = document.querySelectorAll('.plan-card');
            
            planCards.forEach(card => {
                // Click on card
                card.addEventListener('click', function(e) {
                    // Don't trigger if clicking on radio directly
                    if (e.target.type === 'radio') return;
                    
                    // Find the radio inside this card
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        
                        // Remove highlight from all cards
                        planCards.forEach(c => c.classList.remove('border', 'border-primary'));
                        // Add highlight to selected card
                        this.classList.add('border', 'border-primary');
                    }
                });

                // Click on radio
                const radio = card.querySelector('input[type="radio"]');
                if (radio) {
                    radio.addEventListener('click', function(e) {
                        e.stopPropagation(); // Prevent event bubbling
                        
                        // Remove highlight from all cards
                        planCards.forEach(c => c.classList.remove('border', 'border-primary'));
                        // Add highlight to parent card
                        this.closest('.plan-card').classList.add('border', 'border-primary');
                    });
                }
            });
        });
    </script>
    @endpush
    @endsection
</x-app-layout>