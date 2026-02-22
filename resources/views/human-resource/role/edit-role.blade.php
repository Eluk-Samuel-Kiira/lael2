<div class="modal fade edit-role-modal" id="edit_role{{ $role->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('auth.edit_role') }}: {{ $role->name }}</h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>
            <div class="modal-body scroll-y mx-lg-5 my-7" style="max-height: 70vh;">
                <div id="status"></div>
                <form id="edit_role_form{{ $role->id }}" class="form">
                    @csrf
                    @method('PUT')
                    
                    <div class="fv-row mb-10">
                        <label class="fs-5 fw-bold form-label mb-2">
                            <span class="required">{{ __('auth._role') }}</span>
                        </label>
                        <input class="form-control form-control-solid" type="text" value="{{ $role->name }}" name="name" required/>
                        <div id="name{{ $role->id }}"></div>
                    </div>
                    
                    <div class="fv-row">
                        <div class="d-flex flex-stack mb-5">
                            <label class="fs-5 fw-bold form-label mb-2">{{ __('auth.role_permission') }}</label>
                            <label class="form-check form-check-custom form-check-solid me-9">
                                <input class="form-check-input" type="checkbox" id="kt_roles_select_all{{ $role->id }}" />
                                <span class="form-check-label">{{ __('auth.select_all') }}</span>
                            </label>
                        </div>
                        
                        <div class="separator separator-dashed mb-8"></div>
                        
                        <!-- Permissions grouped by category -->
                        <div class="permissions-container">
                            @php
                                $groupedPermissions = $permissions->groupBy('category')->sortKeys();
                                $rolePermissionIds = $role->permissions->pluck('id')->toArray();
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
                                                <button type="button" class="btn btn-sm btn-light-primary select-category" data-category="{{ Str::slug($category) }}{{ $role->id }}">
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
                                                                class="form-check-input permission-checkbox category-{{ Str::slug($category) }}{{ $role->id }}" 
                                                                type="checkbox" 
                                                                value="{{ $permission->id }}" 
                                                                id="permission{{ $permission->id }}{{ $role->id }}" 
                                                                name="permissions[]"
                                                                {{ in_array($permission->id, $rolePermissionIds) ? 'checked' : '' }}
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
                                            <button type="button" class="btn btn-sm btn-light-primary select-category" data-category="other-permissions{{ $role->id }}">
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
                                                            class="form-check-input permission-checkbox category-other-permissions{{ $role->id }}" 
                                                            type="checkbox" 
                                                            value="{{ $permission->id }}" 
                                                            id="permission{{ $permission->id }}{{ $role->id }}" 
                                                            name="permissions[]"
                                                            {{ in_array($permission->id, $rolePermissionIds) ? 'checked' : '' }}
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
                            
                            <div id="permissions{{ $role->id }}"></div>
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="reset" class="btn btn-light me-3" id="closeModalEditButton{{ $role->id }}" data-bs-dismiss="modal">{{__('auth._discard') }}</button>
                        <button type="button" onclick="editInstanceLoopRole({{ $role->id }})" class="btn btn-primary" id="submitButton{{ $role->id }}">
                            <span class="indicator-label">{{__('auth.submit') }}</span>
                            <span class="indicator-progress" style="display: none;">
                                {{__('auth.please_wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
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
        // Initialize master checkbox for each role
        initializeRoleCheckboxes({{ $role->id }});
    });

    function initializeRoleCheckbox(roleId) {
        const masterCheckbox = document.getElementById('kt_roles_select_all' + roleId);
        if (!masterCheckbox) return;
        
        const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');
        const categoryCheckboxes = document.querySelectorAll('[class*="category-"][class*="' + roleId + '"]');
        
        // Master checkbox logic
        masterCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            permissionCheckboxes.forEach(checkbox => {
                if (checkbox.id.includes(roleId)) {
                    checkbox.checked = isChecked;
                }
            });
        });

        // Handle Select All for each category
        document.querySelectorAll('.select-category').forEach(button => {
            if (button.dataset.category && button.dataset.category.includes(roleId)) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const category = this.dataset.category;
                    const checkboxes = document.querySelectorAll('.' + category);
                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = !allChecked;
                    });
                    
                    // Update master checkbox state
                    updateMasterCheckboxState(masterCheckbox, permissionCheckboxes, roleId);
                });
            }
        });

        // Update master checkbox based on individual checkboxes
        permissionCheckboxes.forEach(checkbox => {
            if (checkbox.id.includes(roleId)) {
                checkbox.addEventListener('change', function() {
                    updateMasterCheckboxState(masterCheckbox, permissionCheckboxes, roleId);
                });
            }
        });
    }

    function updateMasterCheckboxState(masterCheckbox, allCheckboxes, roleId) {
        const roleCheckboxes = Array.from(allCheckboxes).filter(cb => cb.id.includes(roleId));
        const checkedCheckboxes = roleCheckboxes.filter(cb => cb.checked);
        
        if (roleCheckboxes.length === checkedCheckboxes.length) {
            masterCheckbox.checked = true;
            masterCheckbox.indeterminate = false;
        } else if (checkedCheckboxes.length === 0) {
            masterCheckbox.checked = false;
            masterCheckbox.indeterminate = false;
        } else {
            masterCheckbox.indeterminate = true;
        }
    }
</script>