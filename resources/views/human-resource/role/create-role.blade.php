<div class="modal fade" id="kt_modal_add_role" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{__('auth.new_role')}}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-lg-5 my-7" style="max-height: 70vh;">
                <div id="status"></div>
                <form id="kt_modal_add_role_form" class="form">
                    @csrf
                    <div class="fv-row mb-10">
                        <label class="fs-5 fw-bold form-label mb-2">
                            <span class="required">{{__('auth._role')}}</span>
                        </label>
                        <input class="form-control form-control-solid" type="text" placeholder="Enter a role name" name="name" required/>
                        <div id="name"></div>
                    </div>
                    
                    <div class="fv-row">
                        <div class="d-flex flex-stack mb-5">
                            <label class="fs-5 fw-bold form-label mb-2">{{__('auth.role_permission')}}</label>
                            <label class="form-check form-check-custom form-check-solid me-9">
                                <input class="form-check-input" type="checkbox" id="kt_roles_select_all" />
                                <span class="form-check-label" for="kt_roles_select_all">{{__('auth.select_all')}}</span>
                            </label>
                        </div>
                        
                        <div class="separator separator-dashed mb-8"></div>
                        
                        <!-- Permissions grouped by category -->
                        <div class="permissions-container">
                            @php
                                $groupedPermissions = $permissions->groupBy('category')->sortKeys();
                            @endphp
                            
                            @foreach($groupedPermissions as $category => $categoryPermissions)
                                @if($category)
                                    <div class="card card-flush mb-6">
                                        <div class="card-header py-4">
                                            <div class="card-title">
                                                <h3 class="fw-bold fs-4">{{ $category }}</h3>
                                                <span class="badge badge-light-primary ms-3">{{ $categoryPermissions->count() }} permissions</span>
                                            </div>
                                            <div class="card-toolbar">
                                                <button type="button" class="btn btn-sm btn-light-primary select-category" data-category="{{ Str::slug($category) }}">
                                                    <i class="ki-duotone ki-check-square fs-6 me-1"></i> {{ __('Select All') }}
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                @foreach($categoryPermissions as $permission)
                                                    <div class="col-md-4 mb-4">
                                                        <label class="form-check form-check-sm form-check-custom form-check-solid">
                                                            <input 
                                                                class="form-check-input permission-checkbox category-{{ Str::slug($category) }}" 
                                                                type="checkbox" 
                                                                value="{{ $permission->id }}" 
                                                                id="permission{{ $permission->id }}" 
                                                                name="permissions[]"
                                                            />
                                                            <span class="form-check-label">
                                                                {{ Str::title(str_replace('_', ' ', $permission->name)) }}
                                                            </span>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                            
                            <!-- Permissions without category (fallback) -->
                            @php
                                $uncategorizedPermissions = $permissions->whereNull('category');
                            @endphp
                            
                            @if($uncategorizedPermissions->isNotEmpty())
                                <div class="card card-flush mb-6">
                                    <div class="card-header py-4">
                                        <div class="card-title">
                                            <h3 class="fw-bold fs-4">{{ __('Other Permissions') }}</h3>
                                            <span class="badge badge-light-primary ms-3">{{ $uncategorizedPermissions->count() }} permissions</span>
                                        </div>
                                        <div class="card-toolbar">
                                            <button type="button" class="btn btn-sm btn-light-primary select-category" data-category="other-permissions">
                                                <i class="ki-duotone ki-check-square fs-6 me-1"></i> {{ __('Select All') }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach($uncategorizedPermissions as $permission)
                                                <div class="col-md-4 mb-4">
                                                    <label class="form-check form-check-sm form-check-custom form-check-solid">
                                                        <input 
                                                            class="form-check-input permission-checkbox category-other-permissions" 
                                                            type="checkbox" 
                                                            value="{{ $permission->id }}" 
                                                            id="permission{{ $permission->id }}" 
                                                            name="permissions[]"
                                                        />
                                                        <span class="form-check-label">
                                                            {{ Str::title(str_replace('_', ' ', $permission->name)) }}
                                                        </span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <div id="permissions"></div>
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button id="discardButton" type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">{{__('auth._discard') }}</button>
                        
                        <button 
                            id="submitRoleButton" 
                            type="button" 
                            class="btn btn-primary"
                            onclick="submitRoleForm('kt_modal_add_role_form', 'submitRoleButton', '{{ route('role.store') }}', 'POST', 'discardButton')">
                            
                            <span class="indicator-label">{{__('auth.submit')}}</span>
                            <span class="indicator-progress">{{__('auth.please_wait')}}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- No inline JavaScript - all JS is external -->
 <script>
    // role-permissions.js
document.addEventListener('DOMContentLoaded', function() {
    // Initialize role permissions modal functionality
    initRolePermissions();
});

function initRolePermissions() {
    const masterCheckbox = document.getElementById('kt_roles_select_all');
    if (!masterCheckbox) return;
    
    const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');
    
    // Master checkbox logic
    masterCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        permissionCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
    });

    // Handle Select All for each category
    document.querySelectorAll('.select-category').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const category = this.dataset.category;
            const checkboxes = document.querySelectorAll('.category-' + category);
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
            
            // Update master checkbox state
            updateMasterCheckboxState(masterCheckbox, permissionCheckboxes);
        });
    });

    // Update master checkbox based on individual checkboxes
    permissionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateMasterCheckboxState(masterCheckbox, permissionCheckboxes);
        });
    });
}

function updateMasterCheckboxState(masterCheckbox, allCheckboxes) {
    const checkedCheckboxes = document.querySelectorAll('.permission-checkbox:checked');
    
    if (allCheckboxes.length === checkedCheckboxes.length) {
        masterCheckbox.checked = true;
        masterCheckbox.indeterminate = false;
    } else if (checkedCheckboxes.length === 0) {
        masterCheckbox.checked = false;
        masterCheckbox.indeterminate = false;
    } else {
        masterCheckbox.indeterminate = true;
    }
}

// Global submit function
window.submitRoleForm = function(formId, buttonId, url, method, discardButtonId) {
    const form = document.getElementById(formId);
    const submitButton = document.getElementById(buttonId);
    const discardButton = document.getElementById(discardButtonId);
    
    if (!form || !submitButton) return;
    
    // Show loading state
    submitButton.querySelector('.indicator-label').style.display = 'none';
    submitButton.querySelector('.indicator-progress').style.display = 'inline-block';
    submitButton.disabled = true;
    if (discardButton) discardButton.disabled = true;

    // Collect form data
    const formData = new FormData(form);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}');
    formData.append('_method', method);

    // Submit via fetch
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            const modalElement = document.getElementById('kt_modal_add_role');
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) modal.hide();
            
            // Show success message
            Swal.fire({
                text: data.message || 'Role created successfully',
                icon: 'success',
                buttonsStyling: false,
                confirmButtonText: 'OK',
                customClass: { confirmButton: 'btn btn-primary' }
            }).then(() => {
                // Reload or update table
                if (data.reload) {
                    location.reload();
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                }
            });
        } else {
            // Show error message
            Swal.fire({
                text: data.message || 'Failed to create role',
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
        // Reset loading state
        submitButton.querySelector('.indicator-label').style.display = 'inline-block';
        submitButton.querySelector('.indicator-progress').style.display = 'none';
        submitButton.disabled = false;
        if (discardButton) discardButton.disabled = false;
    });
};
 </script>