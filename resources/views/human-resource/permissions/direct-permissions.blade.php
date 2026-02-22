<div class="modal fade edit-role-modal" id="direct_permissions{{ $user->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('auth.edit_permission') }} - {{ ucwords(str_replace('_', ' ', $user->first_name . ' ' . $user->last_name)) }}</h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>
            <div class="modal-body scroll-y mx-lg-5 my-7" style="max-height: 70vh;">
                <div id="status"></div>
                <form id="direct_permissions_form{{ $user->id }}" class="form">
                    @csrf
                    @method('PUT')
                    
                    <input type="hidden" name="user_id" value="{{ $user->id }}" />
                    
                    <div class="fv-row">
                        <div class="d-flex flex-stack mb-5">
                            <label class="fs-5 fw-bold form-label mb-2">{{ __('auth.role_permission') }}</label>
                            <label class="form-check form-check-custom form-check-solid me-9">
                                <input class="form-check-input" type="checkbox" id="kt_roles_select_all{{ $user->id }}" />
                                <span class="form-check-label">{{ __('auth.select_all') }}</span>
                            </label>
                        </div>
                        
                        <div class="separator separator-dashed mb-8"></div>
                        
                        <!-- Permissions grouped by category -->
                        <div class="permissions-container">
                            @php
                                $groupedPermissions = $permissions->groupBy('category')->sortKeys();
                                $userPermissionIds = $user->permissions->pluck('id')->toArray();
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
                                                <button type="button" class="btn btn-sm btn-light-primary select-category" data-category="{{ Str::slug($category) }}{{ $user->id }}">
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
                                                                class="form-check-input permission-checkbox category-{{ Str::slug($category) }}{{ $user->id }}" 
                                                                type="checkbox" 
                                                                value="{{ $permission->id }}" 
                                                                id="permission{{ $permission->id }}{{ $user->id }}" 
                                                                name="permissions[]"
                                                                {{ in_array($permission->id, $userPermissionIds) ? 'checked' : '' }}
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
                                            <button type="button" class="btn btn-sm btn-light-primary select-category" data-category="other-permissions{{ $user->id }}">
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
                                                            class="form-check-input permission-checkbox category-other-permissions{{ $user->id }}" 
                                                            type="checkbox" 
                                                            value="{{ $permission->id }}" 
                                                            id="permission{{ $permission->id }}{{ $user->id }}" 
                                                            name="permissions[]"
                                                            {{ in_array($permission->id, $userPermissionIds) ? 'checked' : '' }}
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
                            
                            <div id="permissions{{ $user->id }}"></div>
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="reset" class="btn btn-light me-3" id="closeModalEditButton{{ $user->id }}" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button type="button" onclick="editInstanceLoopPermissions({{ $user->id }})" class="btn btn-primary" id="submitButton{{ $user->id }}">
                            <span class="indicator-label">{{ __('auth._update') }}</span>
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
    function getCheckedValues(uniqueId) {
        const checkedValues = Array.from(document.querySelectorAll(`#direct_permissions_form${uniqueId} .permission-checkbox:checked`))
                                .map(cb => cb.value);
        return checkedValues;
    }
    
    // Function to initialize each modal
    function initializeModalPermission(userId) {
        console.log('Initializing modal for user:', userId);
        
        const selectAllCheckbox = document.getElementById(`kt_roles_select_all${userId}`);
        if (!selectAllCheckbox) {
            console.log('Select all checkbox not found');
            return;
        }
        
        const permissionCheckboxes = document.querySelectorAll(`#direct_permissions_form${userId} .permission-checkbox`);
        console.log('Found permission checkboxes:', permissionCheckboxes.length);

        // Function to update "Select All" checkbox based on the individual checkboxes' state
        function updateSelectAllCheckbox() {
            const allChecked = Array.from(permissionCheckboxes).every(checkbox => checkbox.checked);
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = !allChecked && Array.from(permissionCheckboxes).some(checkbox => checkbox.checked);
            console.log('Select all updated:', selectAllCheckbox.checked, 'indeterminate:', selectAllCheckbox.indeterminate);
        }

        // Handle "Select All" checkbox click event to toggle all permissions
        selectAllCheckbox.addEventListener('change', function () {
            console.log('Select all clicked:', this.checked);
            permissionCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Handle individual permission checkbox change event
        permissionCheckboxes.forEach(checkbox => {
            checkbox.removeEventListener('change', updateSelectAllCheckbox);
            checkbox.addEventListener('change', updateSelectAllCheckbox);
        });

        // Handle Select All for each category
        const categoryButtons = document.querySelectorAll(`#direct_permissions${userId} .select-category`);
        console.log('Found category buttons:', categoryButtons.length);
        
        categoryButtons.forEach(button => {
            // Remove existing listeners
            button.removeEventListener('click', button.clickHandler);
            
            // Create new handler
            button.clickHandler = function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const category = this.dataset.category;
                console.log('Category button clicked:', category);
                
                // Debug: Log all classes in the form to see what's available
                const form = document.getElementById(`direct_permissions_form${userId}`);
                console.log('Form classes:', Array.from(form.querySelectorAll('[class]')).map(el => el.className));
                
                // Find all checkboxes in this category using a more flexible selector
                // Try multiple selector strategies
                let categoryCheckboxes = [];
                
                // Strategy 1: Exact class match
                categoryCheckboxes = document.querySelectorAll(`#direct_permissions_form${userId} .${category}`);
                console.log('Strategy 1 - Exact class match:', categoryCheckboxes.length);
                
                // Strategy 2: Class contains the category slug
                if (categoryCheckboxes.length === 0) {
                    const slugPart = category.replace(userId, '');
                    categoryCheckboxes = document.querySelectorAll(`#direct_permissions_form${userId} [class*="${slugPart}"]`);
                    console.log('Strategy 2 - Class contains slug:', categoryCheckboxes.length);
                }
                
                // Strategy 3: Find by parent card
                if (categoryCheckboxes.length === 0) {
                    const card = button.closest('.card');
                    if (card) {
                        categoryCheckboxes = card.querySelectorAll('.permission-checkbox');
                        console.log('Strategy 3 - Card scope:', categoryCheckboxes.length);
                    }
                }
                
                if (categoryCheckboxes.length > 0) {
                    const allChecked = Array.from(categoryCheckboxes).every(cb => cb.checked);
                    console.log('All checked:', allChecked);
                    
                    categoryCheckboxes.forEach(checkbox => {
                        checkbox.checked = !allChecked;
                    });
                    
                    // Trigger change event on each checkbox to update the select all state
                    categoryCheckboxes.forEach(checkbox => {
                        const event = new Event('change', { bubbles: true });
                        checkbox.dispatchEvent(event);
                    });
                    
                    updateSelectAllCheckbox();
                } else {
                    console.log('No checkboxes found for category:', category);
                }
            };
            
            button.addEventListener('click', button.clickHandler);
        });

        // Initial update of "Select All" checkbox on modal load
        updateSelectAllCheckbox();
    }

    // Initialize modal only when it is opened
    function initializeModalOnClick(userId) {
        console.log('initializeModalOnClick called for user:', userId);
        
        const modal = document.getElementById(`direct_permissions${userId}`);
        
        if (!modal) {
            console.log('Modal not found for user:', userId);
            return;
        }
        
        // Remove existing listener to prevent duplicates
        modal.removeEventListener('shown.bs.modal', window[`modalHandler${userId}`]);
        
        // Create new handler
        window[`modalHandler${userId}`] = function() {
            console.log('Modal shown for user:', userId);
            // Small delay to ensure DOM is ready
            setTimeout(() => {
                initializeModalPermission(userId);
            }, 100);
        };
        
        modal.addEventListener('shown.bs.modal', window[`modalHandler${userId}`]);
    }

    // Also initialize when modal is hidden to clean up
    document.addEventListener('hidden.bs.modal', function(event) {
        if (event.target.id && event.target.id.startsWith('direct_permissions')) {
            const userId = event.target.id.replace('direct_permissions', '');
            console.log('Modal hidden for user:', userId);
        }
    });

    function showAllPermissions(userId) {
        var modal = new bootstrap.Modal(document.getElementById('permissionsModal' + userId));
        modal.show();
    }
    
    function editInstanceLoopPermissions(uniqueId) {
        const submitButton = document.getElementById('submitButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Select the form and create FormData from it
        var form = document.getElementById('direct_permissions_form' + uniqueId);
        var formData = new FormData(form);

        // Get the checked permission values as an array
        var permissions = getCheckedValues(uniqueId);
        permissions.forEach(permissionId => formData.append('permissions[]', permissionId));
        var data = Object.fromEntries(formData.entries());

        // Confirm permissions as an array in final data object
        data.permissions = permissions;

        // Set up the URL dynamically
        var updateUrl = @json(route('permission.update', ['id' => ':id'])).replace(':id', uniqueId);
        // console.log(updateUrl)

        // Submit form data asynchronously
        
        handleEditResponse(data, updateUrl, uniqueId, submitButton);

    }
</script>