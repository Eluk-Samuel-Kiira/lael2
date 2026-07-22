<!-- Admin Users Modal -->
<div class="modal fade" id="adminUsersTenant{{$tenant->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{__('payments.admin_users')}} - {{ $tenant->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                @if($tenant->adminUsers && $tenant->adminUsers->count() > 0)
                    <!-- Summary Cards -->
                    <div class="row g-5 g-xl-8 mb-8">
                        <div class="col-xl-4">
                            <div class="card bg-light-primary">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-50px me-3">
                                            <span class="symbol-label bg-primary bg-opacity-15">
                                                <i class="bi bi-people fs-2x text-primary"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <div class="fs-6 fw-semibold text-gray-500">{{ __('payments.total_admins') }}</div>
                                            <div class="fw-bold fs-2 text-gray-800">{{ $tenant->adminUsers->count() }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4">
                            <div class="card bg-light-success">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-50px me-3">
                                            <span class="symbol-label bg-success bg-opacity-15">
                                                <i class="bi bi-check-circle fs-2x text-success"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <div class="fs-6 fw-semibold text-gray-500">{{ __('payments.active_admins') }}</div>
                                            <div class="fw-bold fs-2 text-gray-800">{{ $tenant->adminUsers->where('status', 'active')->count() }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4">
                            <div class="card bg-light-warning">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-50px me-3">
                                            <span class="symbol-label bg-warning bg-opacity-15">
                                                <i class="bi bi-shield-lock fs-2x text-warning"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <div class="fs-6 fw-semibold text-gray-500">{{ __('payments.super_admins') }}</div>
                                            <div class="fw-bold fs-2 text-gray-800">{{ $tenant->adminUsers->where('role_id', 2)->count() ?? 0 }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Users Table -->
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle gy-4 gs-9">
                            <thead>
                                <tr class="fw-bold fs-6 text-gray-800">
                                    <th>{{ __('payments.profile') }}</th>
                                    <th>{{ __('payments.name') }}</th>
                                    <th>{{ __('payments.email') }}</th>
                                    <th>{{ __('payments.contact') }}</th>
                                    <th>{{ __('payments.job_title') }}</th>
                                    <th>{{ __('payments.status') }}</th>
                                    <th>{{ __('payments.last_active') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tenant->adminUsers as $admin)
                                    <tr>
                                        <td>
                                            <div class="symbol symbol-45px symbol-circle">
                                                @if($admin->profile_image)
                                                    <img src="{{ asset('storage/' . $admin->profile_image) }}" alt="{{ $admin->name }}" />
                                                @else
                                                    <span class="symbol-label bg-light-{{ ['primary', 'success', 'info', 'warning', 'danger'][$loop->index % 5] }} text-{{ ['primary', 'success', 'info', 'warning', 'danger'][$loop->index % 5] }} fw-bold">
                                                        {{ strtoupper(substr($admin->first_name ?? $admin->name, 0, 1)) }}{{ strtoupper(substr($admin->last_name ?? '', 0, 1)) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold">{{ $admin->first_name }} {{ $admin->last_name }}</span>
                                                <span class="text-muted fs-7">{{ $admin->name }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $admin->email }}</td>
                                        <td>{{ $admin->telephone_number ?? 'N/A' }}</td>
                                        <td>{{ $admin->job_title ?? 'N/A' }}</td>
                                        <td>
                                            @if($admin->status == 'active')
                                                <span class="badge badge-light-success">{{ __('payments.active') }}</span>
                                            @elseif($admin->status == 'inactive')
                                                <span class="badge badge-light-danger">{{ __('payments.inactive') }}</span>
                                            @else
                                                <span class="badge badge-light-warning">{{ __('payments.pending') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($admin->last_login_at)
                                                {{ \Carbon\Carbon::parse($admin->last_login_at)->diffForHumans() }}
                                            @else
                                                <span class="text-muted">{{ __('payments.never') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle fs-1 me-2"></i>
                        {{ __('payments.no_admin_users') }}
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('payments.close') }}</button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_admin_{{ $tenant->id }}">
                    <i class="ki-duotone ki-plus fs-2"></i> {{__('payments.add_admin')}}
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Add Admin Modal -->
<div class="modal fade" id="kt_modal_add_admin_{{ $tenant->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{__('payments.add_admin')}} - {{ $tenant->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <form class="form" id="kt_modal_add_admin_form_{{ $tenant->id }}" 
                method="POST" 
                action="{{ route('tenant.add-admin', $tenant->id) }}"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-body px-5 my-7">
                    {{-- ✅ Error Alert Container --}}
                    <div id="admin_form_error_{{ $tenant->id }}" class="alert alert-danger d-none" role="alert">
                        <i class="bi bi-exclamation-circle fs-2 me-2"></i>
                        <span id="admin_form_error_message_{{ $tenant->id }}"></span>
                    </div>

                    {{-- ✅ Success Alert Container --}}
                    <div id="admin_form_success_{{ $tenant->id }}" class="alert alert-success d-none" role="alert">
                        <i class="bi bi-check-circle fs-2 me-2"></i>
                        <span id="admin_form_success_message_{{ $tenant->id }}"></span>
                    </div>

                    <div class="row">
                        <!-- First Name -->
                        <div class="col-md-6 fv-row mb-8">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('payments.first_name') }}</label>
                            <input type="text" 
                                class="form-control form-control-solid" 
                                id="first_name_{{ $tenant->id }}"
                                name="first_name" 
                                value="{{ old('first_name') }}"
                                placeholder="Enter first name" />
                            <div class="invalid-feedback" id="first_name_error_{{ $tenant->id }}"></div>
                        </div>

                        <!-- Last Name -->
                        <div class="col-md-6 fv-row mb-8">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('payments.last_name') }}</label>
                            <input type="text" 
                                class="form-control form-control-solid" 
                                id="last_name_{{ $tenant->id }}"
                                name="last_name" 
                                value="{{ old('last_name') }}"
                                placeholder="Enter last name" />
                            <div class="invalid-feedback" id="last_name_error_{{ $tenant->id }}"></div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Email -->
                        <div class="col-md-6 fv-row mb-8">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('payments.email') }}</label>
                            <input type="email" 
                                class="form-control form-control-solid" 
                                id="email_{{ $tenant->id }}"
                                name="email" 
                                value="{{ old('email') }}"
                                placeholder="Enter email address" />
                            <div class="invalid-feedback" id="email_error_{{ $tenant->id }}"></div>
                        </div>

                        <!-- Telephone -->
                        <div class="col-md-6 fv-row mb-8">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('payments.telephone') }}</label>
                            <input type="text" 
                                class="form-control form-control-solid" 
                                id="telephone_number_{{ $tenant->id }}"
                                name="telephone_number" 
                                value="{{ old('telephone_number') }}"
                                placeholder="Enter telephone number" />
                            <div class="invalid-feedback" id="telephone_number_error_{{ $tenant->id }}"></div>
                        </div>
                    </div>

                    <!-- Job Title -->
                    <div class="fv-row mb-8">
                        <label class="required fs-6 fw-semibold mb-2">{{ __('payments.job_title') }}</label>
                        <input type="text" 
                            class="form-control form-control-solid" 
                            id="job_title_{{ $tenant->id }}"
                            name="job_title" 
                            value="{{ old('job_title') }}"
                            placeholder="Enter job title" />
                        <div class="invalid-feedback" id="job_title_error_{{ $tenant->id }}"></div>
                    </div>

                    <!-- Status -->
                    <div class="fv-row mb-8">
                        <label class="required fs-6 fw-semibold mb-2">{{ __('payments.status') }}</label>
                        <select class="form-select form-select-solid" id="status_{{ $tenant->id }}" name="status">
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>{{ __('payments.active') }}</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>{{ __('payments.inactive') }}</option>
                        </select>
                        <div class="invalid-feedback" id="status_error_{{ $tenant->id }}"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('payments.close') }}</button>
                    <button type="submit" id="submit_admin_btn_{{ $tenant->id }}" class="btn btn-primary">
                        <span class="indicator-label">{{ __('payments.create_admin') }}</span>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle admin form submission with AJAX to display errors in modal
    document.querySelectorAll('[id^="kt_modal_add_admin_form_"]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const tenantId = this.id.replace('kt_modal_add_admin_form_', '');
            const submitBtn = document.getElementById('submit_admin_btn_' + tenantId);
            const errorAlert = document.getElementById('admin_form_error_' + tenantId);
            const successAlert = document.getElementById('admin_form_success_' + tenantId);
            const errorMessage = document.getElementById('admin_form_error_message_' + tenantId);
            const successMessage = document.getElementById('admin_form_success_message_' + tenantId);
            
            // Clear previous errors
            errorAlert.classList.add('d-none');
            successAlert.classList.add('d-none');
            
            // Clear field errors
            const fields = ['first_name', 'last_name', 'email', 'telephone_number', 'job_title', 'status'];
            fields.forEach(field => {
                const errorEl = document.getElementById(field + '_error_' + tenantId);
                const inputEl = document.getElementById(field + '_' + tenantId);
                if (errorEl) errorEl.textContent = '';
                if (inputEl) inputEl.classList.remove('is-invalid');
            });
            
            // Show loading state
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
                // Hide loading state
                submitBtn.disabled = false;
                submitBtn.querySelector('.indicator-label').style.display = 'inline-flex';
                submitBtn.querySelector('.indicator-progress').style.display = 'none';
                
                if (data.success) {
                    // Show success message
                    successAlert.classList.remove('d-none');
                    successMessage.textContent = data.message || 'Admin user created successfully.';
                    
                    // Reset form
                    this.reset();
                    
                    // Close modal after delay and reload
                    setTimeout(function() {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_admin_' + tenantId));
                        if (modal) modal.hide();
                        
                        // Reload the page to show updated admin list
                        location.reload();
                    }, 2000);
                    
                } else {
                    // Show general error
                    if (data.message) {
                        errorAlert.classList.remove('d-none');
                        errorMessage.textContent = data.message;
                    }
                    
                    // Show field-specific errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(function(field) {
                            const errorEl = document.getElementById(field + '_error_' + tenantId);
                            const inputEl = document.getElementById(field + '_' + tenantId);
                            if (errorEl && data.errors[field].length > 0) {
                                errorEl.textContent = data.errors[field][0];
                                if (inputEl) inputEl.classList.add('is-invalid');
                            }
                        });
                    }
                }
            })
            .catch(function(error) {
                // Hide loading state
                submitBtn.disabled = false;
                submitBtn.querySelector('.indicator-label').style.display = 'inline-flex';
                submitBtn.querySelector('.indicator-progress').style.display = 'none';
                
                // Show error message
                errorAlert.classList.remove('d-none');
                errorMessage.textContent = 'An error occurred. Please try again.';
                console.error('Error:', error);
            });
        });
    });
});
</script>