<!-- Billing Settings Modal -->
<div class="modal fade" id="billingTenant{{$tenant->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    <i class="bi bi-credit-card me-2"></i>
                    {{__('payments.billing_settings')}} - {{ $tenant->name }}
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body px-5 my-7" style="max-height: 70vh; overflow-y: auto;">
                
                <form id="billingForm{{ $tenant->id }}" class="form" method="POST" action="{{ route('tenant.update-billing', $tenant->id) }}">
                    @csrf
                    @method('PUT')
                    
                    <!-- Status Message Container -->
                    <div id="billingMessage{{ $tenant->id }}" class="alert d-none" role="alert">
                        <span id="billingMessageText{{ $tenant->id }}"></span>
                    </div>

                    @php
                        // Get billing settings
                        $billingSettings = $tenant->settings->where('category', 'billing');
                        $billingPlan = $billingSettings->where('setting_key', 'billing_plan')->first();
                        $planName = $billingPlan ? $billingPlan->setting_value : 'No Plan';
                        $subscriptionStatus = $billingSettings->where('setting_key', 'subscription_status')->first();
                        $status = $subscriptionStatus ? $subscriptionStatus->setting_value : 'inactive';
                        $isLifetime = $billingSettings->where('setting_key', 'is_lifetime')->first();
                        $isLifetimeValue = $isLifetime ? (bool)$isLifetime->setting_value : false;
                        
                        // Get dates
                        $trialEndsAt = $billingSettings->where('setting_key', 'trial_ends_at')->first();
                        $trialEndsAtValue = $trialEndsAt ? $trialEndsAt->setting_value : null;
                        
                        $nextBillingDate = $billingSettings->where('setting_key', 'next_billing_date')->first();
                        $nextBillingDateValue = $nextBillingDate ? $nextBillingDate->setting_value : null;
                        
                        $lifetimePurchaseDate = $billingSettings->where('setting_key', 'lifetime_purchase_date')->first();
                        $lifetimePurchaseDateValue = $lifetimePurchaseDate ? $lifetimePurchaseDate->setting_value : null;
                        
                        $billingCycle = $billingSettings->where('setting_key', 'billing_cycle')->first();
                        $billingCycleValue = $billingCycle ? $billingCycle->setting_value : '30_days';
                        
                        $billingCycleDays = $billingSettings->where('setting_key', 'billing_cycle_days')->first();
                        $billingCycleDaysValue = $billingCycleDays ? (int)$billingCycleDays->setting_value : 30;
                        
                        $trialDays = $billingSettings->where('setting_key', 'trial_days')->first();
                        $trialDaysValue = $trialDays ? (int)$trialDays->setting_value : 14;
                        
                        $onetimeFee = $billingSettings->where('setting_key', 'onetime_fee')->first();
                        $onetimeFeeValue = $onetimeFee ? (float)$onetimeFee->setting_value : 0;
                        
                        $setupFee = $billingSettings->where('setting_key', 'setup_fee')->first();
                        $setupFeeValue = $setupFee ? (float)$setupFee->setting_value : 0;
                        
                        $monthlyPrice = $billingSettings->where('setting_key', 'plan_monthly_price')->first();
                        $monthlyPriceValue = $monthlyPrice ? (float)$monthlyPrice->setting_value : 0;
                        
                        $annualPrice = $billingSettings->where('setting_key', 'plan_annual_price')->first();
                        $annualPriceValue = $annualPrice ? (float)$annualPrice->setting_value : 0;
                        
                        $currency = $billingSettings->where('setting_key', 'plan_currency')->first();
                        $currencyValue = $currency ? $currency->setting_value : 'USD';
                    @endphp

                    <!-- Current Status Banner -->
                    <div class="alert alert-{{ $status === 'active' ? 'success' : 'danger' }} d-flex align-items-center mb-5">
                        <i class="bi bi-info-circle fs-2 me-3"></i>
                        <div>
                            <strong>Current Status:</strong> 
                            <span class="badge badge-light-{{ $status === 'active' ? 'success' : 'danger' }} fs-6">
                                {{ ucfirst($status) }}
                            </span>
                            @if($isLifetimeValue)
                                <span class="badge badge-light-primary ms-2">Lifetime License</span>
                            @endif
                        </div>
                    </div>

                    <div class="row g-6">
                        <!-- Basic Billing Info -->
                        <div class="col-md-6">
                            <div class="card card-flush bg-light">
                                <div class="card-header">
                                    <h4 class="card-title">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Basic Information
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Plan Name</label>
                                        <input type="text" 
                                               class="form-control form-control-solid" 
                                               name="plan_name"
                                               value="{{ $planName }}"
                                               placeholder="Enter plan name">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Subscription Status</label>
                                        <select class="form-select form-select-solid" name="subscription_status">
                                            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            <option value="trial" {{ $status === 'trial' ? 'selected' : '' }}>Trial</option>
                                            <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>Expired</option>
                                            <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                        </select>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label fw-bold">Currency</label>
                                        <select class="form-select form-select-solid" name="plan_currency" disabled>
                                            <option value="USD" {{ $currencyValue === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                            <option value="UGX" {{ $currencyValue === 'UGX' ? 'selected' : '' }}>UGX - Ugandan Shilling</option>
                                            <option value="EUR" {{ $currencyValue === 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                            <option value="GBP" {{ $currencyValue === 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing & Cycle -->
                        <div class="col-md-6">
                            <div class="card card-flush bg-light">
                                <div class="card-header">
                                    <h4 class="card-title">
                                        <i class="bi bi-cash me-2"></i>
                                        Pricing & Cycle
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Billing Cycle</label>
                                        <select class="form-select form-select-solid" name="billing_cycle" id="billing_cycle_{{ $tenant->id }}">
                                            <option value="30_days" {{ $billingCycleValue === '30_days' ? 'selected' : '' }}>Monthly (30 days)</option>
                                            <option value="90_days" {{ $billingCycleValue === '90_days' ? 'selected' : '' }}>Quarterly (90 days)</option>
                                            <option value="365_days" {{ $billingCycleValue === '365_days' ? 'selected' : '' }}>Annual (365 days)</option>
                                            <option value="one-time" {{ $billingCycleValue === 'one-time' ? 'selected' : '' }}>One-Time / Lifetime</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="cycle_days_container_{{ $tenant->id }}">
                                        <label class="form-label fw-bold">Cycle Days</label>
                                        <input type="number" 
                                               class="form-control form-control-solid" 
                                               name="billing_cycle_days"
                                               id="billing_cycle_days_{{ $tenant->id }}"
                                               value="{{ $billingCycleDaysValue }}"
                                               min="1"
                                               step="1">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Trial Days</label>
                                        <input type="number" 
                                               class="form-control form-control-solid" 
                                               name="trial_days"
                                               value="{{ $trialDaysValue }}"
                                               min="0"
                                               step="1">
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label fw-bold">Is Lifetime License</label>
                                        <div class="form-check form-switch form-check-custom form-check-solid">
                                            <input type="hidden" name="is_lifetime" value="0">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="is_lifetime" 
                                                   value="1"
                                                   id="is_lifetime_{{ $tenant->id }}"
                                                   {{ $isLifetimeValue ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_lifetime_{{ $tenant->id }}">
                                                <span class="badge badge-light-{{ $isLifetimeValue ? 'primary' : 'secondary' }} me-1">
                                                    {{ $isLifetimeValue ? 'Lifetime' : 'Subscription' }}
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="col-12">
                            <div class="card card-flush bg-light">
                                <div class="card-header">
                                    <h4 class="card-title">
                                        <i class="bi bi-calendar3 me-2"></i>
                                        Important Dates
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Trial Ends At</label>
                                            <input type="datetime-local" 
                                                   class="form-control form-control-solid" 
                                                   name="trial_ends_at"
                                                   value="{{ $trialEndsAtValue ? date('Y-m-d\TH:i', strtotime($trialEndsAtValue)) : '' }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Next Billing Date</label>
                                            <input type="datetime-local" 
                                                   class="form-control form-control-solid" 
                                                   name="next_billing_date"
                                                   id="next_billing_date_{{ $tenant->id }}"
                                                   value="{{ $nextBillingDateValue ? date('Y-m-d\TH:i', strtotime($nextBillingDateValue)) : '' }}">
                                            <small class="text-muted">Auto-calculated based on cycle</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Lifetime Purchase Date</label>
                                            <input type="datetime-local" 
                                                   class="form-control form-control-solid" 
                                                   name="lifetime_purchase_date"
                                                   value="{{ $lifetimePurchaseDateValue ? date('Y-m-d\TH:i', strtotime($lifetimePurchaseDateValue)) : '' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing Details -->
                        <div class="col-12">
                            <div class="card card-flush bg-light">
                                <div class="card-header">
                                    <h4 class="card-title">
                                        <i class="bi bi-tag me-2"></i>
                                        Pricing Details
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Monthly Price</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ $currencyValue }}</span>
                                                <input type="number" 
                                                       class="form-control form-control-solid" 
                                                       name="plan_monthly_price"
                                                       value="{{ $monthlyPriceValue }}"
                                                       min="0"
                                                       step="0.01">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Annual Price</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ $currencyValue }}</span>
                                                <input type="number" 
                                                       class="form-control form-control-solid" 
                                                       name="plan_annual_price"
                                                       value="{{ $annualPriceValue }}"
                                                       min="0"
                                                       step="0.01">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">One-Time Fee</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ $currencyValue }}</span>
                                                <input type="number" 
                                                       class="form-control form-control-solid" 
                                                       name="onetime_fee"
                                                       value="{{ $onetimeFeeValue }}"
                                                       min="0"
                                                       step="0.01">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Setup Fee</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ $currencyValue }}</span>
                                                <input type="number" 
                                                       class="form-control form-control-solid" 
                                                       name="setup_fee"
                                                       value="{{ $setupFeeValue }}"
                                                       min="0"
                                                       step="0.01">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end border-top pt-6 mt-6">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="saveBillingBtn{{ $tenant->id }}">
                            <span class="indicator-label">
                                <i class="bi bi-save me-2"></i> Save Billing Settings
                            </span>
                            <span class="indicator-progress" style="display: none;">
                                Saving... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle billing form submission
    document.querySelectorAll('[id^="billingForm"]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const tenantId = this.id.replace('billingForm', '');
            const submitBtn = document.getElementById('saveBillingBtn' + tenantId);
            const messageContainer = document.getElementById('billingMessage' + tenantId);
            const messageText = document.getElementById('billingMessageText' + tenantId);
            
            messageContainer.classList.add('d-none');
            
            submitBtn.disabled = true;
            submitBtn.querySelector('.indicator-label').style.display = 'none';
            submitBtn.querySelector('.indicator-progress').style.display = 'inline-flex';
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.querySelector('.indicator-label').style.display = 'inline-flex';
                submitBtn.querySelector('.indicator-progress').style.display = 'none';
                
                messageContainer.classList.remove('d-none');
                
                if (data.success) {
                    messageContainer.className = 'alert alert-success';
                    messageText.textContent = data.message || 'Billing settings updated successfully!';
                    
                    if (typeof showToast === 'function') {
                        showToast('success', data.message);
                    }
                    
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    messageContainer.className = 'alert alert-danger';
                    messageText.textContent = data.message || 'An error occurred. Please try again.';
                    
                    if (typeof showToast === 'function') {
                        showToast('error', data.message);
                    }
                }
            })
            .catch(function(error) {
                submitBtn.disabled = false;
                submitBtn.querySelector('.indicator-label').style.display = 'inline-flex';
                submitBtn.querySelector('.indicator-progress').style.display = 'none';
                
                messageContainer.classList.remove('d-none');
                messageContainer.className = 'alert alert-danger';
                messageText.textContent = 'An error occurred. Please try again.';
                console.error('Error:', error);
            });
        });
    });
});

// Auto-calculate next billing date based on cycle
document.addEventListener('change', function(e) {
    if (e.target.id && e.target.id.startsWith('billing_cycle_')) {
        const tenantId = e.target.id.replace('billing_cycle_', '');
        const cycleValue = e.target.value;
        let days = 30;
        
        switch(cycleValue) {
            case '30_days': days = 30; break;
            case '90_days': days = 90; break;
            case '365_days': days = 365; break;
            case 'one-time': days = 0; break;
            default: days = 30;
        }
        
        // Update cycle days input
        const daysInput = document.getElementById('billing_cycle_days_' + tenantId);
        if (daysInput) {
            daysInput.value = days;
            daysInput.disabled = cycleValue === 'one-time';
        }
    }
});
</script>