<!-- Editable Settings Modal -->
<div class="modal fade" id="modifyTenant{{$tenant->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{__('payments.settings')}} - {{ $tenant->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body px-5 my-7" style="max-height: 70vh; overflow-y: auto;">
                
                <form id="settingsForm{{ $tenant->id }}" class="form" method="POST" action="{{ route('tenant.update-settings', $tenant->id) }}">
                    @csrf
                    @method('PUT')
                    
                    <!-- Status Message Container -->
                    <div id="settingsMessage{{ $tenant->id }}" class="alert d-none" role="alert">
                        <span id="settingsMessageText{{ $tenant->id }}"></span>
                    </div>

                    @php
                        // Get all non-billing settings grouped by category
                        $nonBillingSettings = $tenant->settings
                            ->where('category', '!=', 'billing')
                            ->groupBy('category');
                        
                        // Define category display names and icons
                        $categoryDisplay = [
                            'features' => ['label' => 'Module Features', 'icon' => 'bi-grid-3x3-gap-fill'],
                            'features_legacy' => ['label' => 'Legacy Features', 'icon' => 'bi-clock-history'],
                            'hotel_features' => ['label' => 'Hotel Features', 'icon' => 'bi-building'],
                            'limits' => ['label' => 'Limits & Quotas', 'icon' => 'bi-speedometer2'],
                            'general' => ['label' => 'General Settings', 'icon' => 'bi-gear'],
                        ];
                    @endphp

                    <!-- Current Plan Info -->
                    <div class="alert alert-info d-flex align-items-center mb-5">
                        <i class="bi bi-info-circle fs-2 me-3"></i>
                        <div>
                            <strong>Current Plan:</strong> {{ ucfirst($tenant->settings->where('setting_key', 'billing_plan')->first()->setting_value ?? 'No Plan') }}
                            <span class="text-muted ms-3">| Editing settings allows customization beyond the plan defaults</span>
                        </div>
                    </div>

                    <!-- Settings by Category -->
                    @foreach($nonBillingSettings as $category => $settings)
                        @php
                            $display = $categoryDisplay[$category] ?? ['label' => ucfirst(str_replace('_', ' ', $category)), 'icon' => 'bi-folder'];
                        @endphp
                        <div class="card card-flush mb-6">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="bi {{ $display['icon'] }} me-2 text-primary"></i>
                                    {{ $display['label'] }}
                                </h3>
                                @if($category === 'features' || $category === 'hotel_features' || $category === 'features_legacy')
                                    <div class="card-toolbar">
                                        <button type="button" class="btn btn-sm btn-light-primary" onclick="toggleCategory('{{ $tenant->id }}', '{{ $category }}')">
                                            <i class="bi bi-toggle-on me-1"></i> Toggle All
                                        </button>
                                    </div>
                                @endif
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    @foreach($settings as $setting)
                                        @php
                                            $key = $setting->setting_key;
                                            $dataType = $setting->data_type;
                                            $value = $setting->value;
                                            $categoryKey = $setting->category;
                                            
                                            // Format display name
                                            $displayName = ucfirst(str_replace('_', ' ', str_replace('module_', '', $key)));
                                            
                                            // Determine if it's a boolean for toggle display
                                            $isBoolean = $dataType === 'boolean';
                                            $isEnabled = $isBoolean ? (bool)$value : false;
                                            $color = $isEnabled ? 'success' : 'secondary';
                                        @endphp
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="setting-item p-3 border rounded">
                                                <label class="fw-bold d-block mb-2" for="{{ $key }}_{{ $tenant->id }}">
                                                    {{ $displayName }}
                                                    <span class="badge badge-light-info ms-1">{{ $dataType }}</span>
                                                    <span class="badge badge-light-secondary ms-1">{{ $categoryKey }}</span>
                                                </label>
                                                
                                                @if($isBoolean)
                                                    <!-- Boolean: Switch Toggle -->
                                                    <div class="form-check form-switch form-check-custom form-check-solid">
                                                        <input type="hidden" name="{{ $key }}" value="0">
                                                        <input class="form-check-input module-toggle" 
                                                               type="checkbox" 
                                                               name="{{ $key }}" 
                                                               value="1"
                                                               id="{{ $key }}_{{ $tenant->id }}"
                                                               data-category="{{ $categoryKey }}"
                                                               data-type="boolean"
                                                               {{ $isEnabled ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="{{ $key }}_{{ $tenant->id }}">
                                                            <span class="badge badge-light-{{ $color }} me-1">{{ $isEnabled ? 'ON' : 'OFF' }}</span>
                                                        </label>
                                                    </div>
                                                    
                                                @elseif($dataType === 'integer')
                                                    <!-- Integer: Number Input -->
                                                    <input type="number" 
                                                           class="form-control form-control-solid" 
                                                           name="{{ $key }}"
                                                           id="{{ $key }}_{{ $tenant->id }}"
                                                           value="{{ $value }}"
                                                           min="0"
                                                           step="1">
                                                    
                                                @elseif($dataType === 'decimal')
                                                    <!-- Decimal: Number Input with decimals -->
                                                    <div class="input-group">
                                                        <span class="input-group-text">{{ currency_symbol() }}</span>
                                                        <input type="number" 
                                                               class="form-control form-control-solid" 
                                                               name="{{ $key }}"
                                                               id="{{ $key }}_{{ $tenant->id }}"
                                                               value="{{ $value }}"
                                                               min="0"
                                                               step="0.01">
                                                    </div>
                                                    
                                                @elseif($dataType === 'date')
                                                    <!-- Date: Date Picker -->
                                                    <input type="date" 
                                                           class="form-control form-control-solid" 
                                                           name="{{ $key }}"
                                                           id="{{ $key }}_{{ $tenant->id }}"
                                                           value="{{ $value ? date('Y-m-d', strtotime($value)) : '' }}">
                                                    
                                                @elseif($dataType === 'datetime')
                                                    <!-- DateTime: Datetime Picker -->
                                                    <input type="datetime-local" 
                                                           class="form-control form-control-solid" 
                                                           name="{{ $key }}"
                                                           id="{{ $key }}_{{ $tenant->id }}"
                                                           value="{{ $value ? date('Y-m-d\TH:i', strtotime($value)) : '' }}">
                                                    
                                                @elseif($dataType === 'json')
                                                    <!-- JSON: Textarea -->
                                                    <textarea class="form-control form-control-solid" 
                                                              name="{{ $key }}"
                                                              id="{{ $key }}_{{ $tenant->id }}"
                                                              rows="2">{{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value }}</textarea>
                                                    
                                                @else
                                                    <!-- String: Text Input -->
                                                    <input type="text" 
                                                           class="form-control form-control-solid" 
                                                           name="{{ $key }}"
                                                           id="{{ $key }}_{{ $tenant->id }}"
                                                           value="{{ $value }}">
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="d-flex justify-content-end border-top pt-6">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="saveSettingsBtn{{ $tenant->id }}">
                            <span class="indicator-label">
                                <i class="bi bi-save me-2"></i> Save All Settings
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
    
// Save Settings Form Handler
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[id^="settingsForm"]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const tenantId = this.id.replace('settingsForm', '');
            const submitBtn = document.getElementById('saveSettingsBtn' + tenantId);
            const messageContainer = document.getElementById('settingsMessage' + tenantId);
            const messageText = document.getElementById('settingsMessageText' + tenantId);
            
            // Hide previous messages
            messageContainer.classList.add('d-none');
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.querySelector('.indicator-label').style.display = 'none';
            submitBtn.querySelector('.indicator-progress').style.display = 'inline-flex';
            
            const formData = new FormData(this);
            
            // For checkboxes, ensure we have the correct values
            // Checkboxes with hidden inputs already handle this properly
            
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
                // Hide loading state
                submitBtn.disabled = false;
                submitBtn.querySelector('.indicator-label').style.display = 'inline-flex';
                submitBtn.querySelector('.indicator-progress').style.display = 'none';
                
                messageContainer.classList.remove('d-none');
                
                if (data.success) {
                    messageContainer.className = 'alert alert-success';
                    messageText.textContent = data.message || 'Settings updated successfully!';
                    
                    // Update badge colors for toggles
                    const toggles = document.querySelectorAll(`#settingsForm${tenantId} .module-toggle`);
                    toggles.forEach(toggle => {
                        const label = toggle.closest('.form-check').querySelector('.form-check-label');
                        const badge = label.querySelector('.badge');
                        const isChecked = toggle.checked;
                        if (badge) {
                            badge.className = `badge badge-light-${isChecked ? 'success' : 'secondary'} me-1`;
                            badge.textContent = isChecked ? 'ON' : 'OFF';
                        }
                    });
                    
                    // Show toast notification
                    if (typeof showToast === 'function') {
                        showToast('success', data.message);
                    }
                    
                    // Reload after 1.5 seconds if any updates were made
                    if (data.updated_count > 0) {
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    }
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
                
                if (typeof showToast === 'function') {
                    showToast('error', 'An error occurred. Please try again.');
                }
            });
        });
    });
});

// Toggle all modules in a category (only for boolean settings)
function toggleCategory(tenantId, category) {
    const toggles = document.querySelectorAll(`#settingsForm${tenantId} .module-toggle[data-category="${category}"]`);
    const allChecked = Array.from(toggles).every(t => t.checked);
    
    toggles.forEach(toggle => {
        toggle.checked = !allChecked;
        // Trigger change event to update UI
        toggle.dispatchEvent(new Event('change'));
        // Update badge
        const label = toggle.closest('.form-check').querySelector('.form-check-label');
        const badge = label.querySelector('.badge');
        if (badge) {
            const isChecked = toggle.checked;
            badge.className = `badge badge-light-${isChecked ? 'success' : 'secondary'} me-1`;
            badge.textContent = isChecked ? 'ON' : 'OFF';
        }
    });
}

// Update badge when toggle changes
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('module-toggle')) {
        const label = e.target.closest('.form-check').querySelector('.form-check-label');
        const badge = label.querySelector('.badge');
        if (badge) {
            const isChecked = e.target.checked;
            badge.className = `badge badge-light-${isChecked ? 'success' : 'secondary'} me-1`;
            badge.textContent = isChecked ? 'ON' : 'OFF';
        }
    }
});

// Global toast notification function
function showToast(type, message) {
    // Check if you have a toast library (like SweetAlert2, Toastr, etc.)
    // Example using SweetAlert2
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: type === 'success' ? 'success' : 'error',
            title: type === 'success' ? 'Success!' : 'Error!',
            text: message,
            timer: 3000,
            showConfirmButton: false,
        });
    } else {
        // Fallback to browser alert
        alert(message);
    }
}
</script>