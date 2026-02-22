<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-5 g-xl-9" id="reloadRoleComponent">
    @foreach ($all_roles as $role)
        <div class="col-md-4 role-card" data-role="{{ $role->name }}">
            <div class="card card-flush h-md-100">
                <div class="card-header">
                    <div class="card-title">
                        <h2>{{ ucwords(str_replace('_', ' ', $role->name)) }}</h2>
                    </div>
                </div>
                <div class="card-body pt-1">
                    <div class="fw-bold text-gray-600 mb-5">
                        {{__('payments.users_with_role')}}
                        <span class="badge badge-{{ $role->users_count > 0 ? 'light-primary' : 'light-secondary' }} ms-2">
                            {{ $role->users_count }}
                        </span>
                    </div>
                    <div class="d-flex flex-column text-gray-600">
                        @foreach ($role->permissions->take(7) as $permission)
                            <div class="d-flex align-items-center py-2">
                                <span class="bullet bg-primary me-3"></span>
                                {{ $permission->name }}
                            </div>
                        @endforeach
                        @if ($role->permissions->count() > 7)
                            <div class="d-flex align-items-center py-2">
                                <span class="bullet bg-primary me-3"></span>
                                <a href="javascript:void(0);" onclick="showAllPermissions({{ $role->id }})">
                                    <em>and {{ $role->permissions->count() - 7 }} more...</em>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card-footer flex-wrap pt-0">
                    @can('edit role')
                    <button type="button" class="btn btn-light btn-active-light-primary my-1" data-bs-toggle="modal" data-bs-target="#edit_role{{ $role->id }}" onclick="initializeModalOnClick({{ $role->id }})">
                        {{__('Edit')}}
                    </button>
                    @endcan
                    @include('human-resource.role.edit-role')

                    @can('delete role')
                        <button type="button" class="btn btn-light btn-active-light-danger my-1" data-bs-toggle="modal" data-bs-target="#delete_role{{ $role->id }}">
                            {{ __('Delete') }}
                        </button>
                    @endcan
                    @include('human-resource.role.delete')
                </div>
            </div>
        </div>

        <!-- Modal for displaying all permissions -->
        <div class="modal fade" id="permissionsModal{{ $role->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="fw-bold">{{ ucwords(str_replace('_', ' ', $role->name)) }} - {{ __('Permissions') }}</h2>
                        <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                            <i class="ki-duotone ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body scroll-y mx-lg-5 my-7" style="max-height: 70vh;">
                        @php
                            $groupedPermissions = $role->permissions->groupBy('category')->sortKeys();
                        @endphp
                        
                        @if($groupedPermissions->isNotEmpty())
                            <!-- Permissions grouped by category -->
                            @foreach($groupedPermissions as $category => $categoryPermissions)
                                <div class="card card-flush mb-6">
                                    <div class="card-header py-4">
                                        <div class="card-title">
                                            <h3 class="fw-bold fs-4">{{ $category ?? 'Other Permissions' }}</h3>
                                            <span class="badge badge-light-primary ms-3">{{ $categoryPermissions->count() }} permissions</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach($categoryPermissions as $permission)
                                                <div class="col-md-4 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <span class="bullet bg-{{ ['primary', 'success', 'info', 'warning', 'danger'][$loop->index % 5] }} me-3" style="width: 8px; height: 8px; border-radius: 50%;"></span>
                                                        <span class="text-gray-800 fw-semibold">
                                                            {{ Str::title(str_replace('_', ' ', $permission->name)) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <!-- If permissions aren't categorized, show in columns -->
                            <div class="row">
                                @php
                                    $permissions = $role->permissions;
                                    $chunkSize = ceil($permissions->count() / 3);
                                @endphp
                                
                                @for($i = 0; $i < 3; $i++)
                                    <div class="col-md-4">
                                        <div class="card card-flush mb-6">
                                            <div class="card-body">
                                                @foreach($permissions->slice($i * $chunkSize, $chunkSize) as $permission)
                                                    <div class="d-flex align-items-center py-2 border-bottom border-gray-300 border-bottom-dashed">
                                                        <span class="bullet bg-{{ ['primary', 'success', 'info', 'warning', 'danger'][$loop->index % 5] }} me-3" style="width: 8px; height: 8px; border-radius: 50%;"></span>
                                                        <span class="text-gray-800 fw-semibold">
                                                            {{ Str::title(str_replace('_', ' ', $permission->name)) }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        @endif
                        
                        <!-- Summary -->
                        <div class="card card-flush mb-6 bg-light-primary">
                            <div class="card-body">
                                <div class="d-flex flex-stack">
                                    <div>
                                        <span class="fw-bold text-primary fs-3">{{ $role->permissions->count() }}</span>
                                        <span class="text-gray-600 fw-semibold ms-2">Total Permissions</span>
                                    </div>
                                    <div class="d-flex gap-5">
                                        <div>
                                            <span class="badge badge-light-primary">{{ $groupedPermissions->count() }}</span>
                                            <span class="text-gray-600 fw-semibold ms-2">Categories</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    </div>
                </div>
            </div>
        </div>

    @endforeach
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    setupRoleCardSearch('searchInput', 'roleFilter', '.role-card', '.card-title h2');
});

function setupRoleCardSearch(searchInputId, filterSelectId, cardSelector, titleSelector) {
    const searchInput = document.getElementById(searchInputId);
    const filterSelect = document.getElementById(filterSelectId);
    const cards = document.querySelectorAll(cardSelector);

    function filterCards() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedRole = filterSelect.value.toLowerCase();

        cards.forEach(card => {
            const titleElement = card.querySelector(titleSelector);
            const titleText = titleElement ? titleElement.textContent.toLowerCase() : '';
            const dataRole = card.getAttribute('data-role')?.toLowerCase() || '';
            
            // Also check the permissions listed inside the card
            const permissionsText = Array.from(card.querySelectorAll('.card-body .d-flex'))
                                        .map(el => el.textContent.toLowerCase())
                                        .join(' ');

            const matchesSearch = searchTerm === '' || 
                                  titleText.includes(searchTerm) || 
                                  dataRole.includes(searchTerm) || 
                                  permissionsText.includes(searchTerm);

            const matchesFilter = selectedRole === '' || dataRole === selectedRole;

            if (matchesSearch && matchesFilter) {
                card.style.display = ''; // show
            } else {
                card.style.display = 'none'; // hide
            }
        });
    }

    searchInput.addEventListener('input', filterCards);
    filterSelect.addEventListener('change', filterCards);
}
</script>



<script>
    
    // // Reusable function to filter cards based on search input
    // function setupCardSearch(inputId, cardSelector, attributeName, titleSelector) {
    //     LiveBlade.searchCardItems(inputId, cardSelector, attributeName, titleSelector)
    // }

    // // Set up the event listener after the DOM is fully loaded
    // document.addEventListener('DOMContentLoaded', function() {
    //     setupCardSearch('roleSearchBar', '.role-card', 'data-role', '.card-title h2'); // Call the function with parameters
    // });

    // Function to initialize each modal
    function initializeModal(roleId) {
        const selectAllCheckbox = document.getElementById(`kt_roles_select_all${roleId}`);
        const permissionCheckboxes = document.querySelectorAll(`#edit_role_form${roleId} .permission-checkbox`);

        // Function to update "Select All" checkbox based on individual checkboxes' state
        function updateSelectAllCheckbox() {
            const allChecked = Array.from(permissionCheckboxes).every(checkbox => checkbox.checked);
            selectAllCheckbox.checked = allChecked;
        }

        // Handle "Select All" checkbox click event to toggle all permissions
        selectAllCheckbox.addEventListener('change', function () {
            const isChecked = selectAllCheckbox.checked;
            permissionCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        });

        // Handle individual permission checkbox change event
        permissionCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectAllCheckbox);
        });

        // Initial update of "Select All" checkbox on modal load
        updateSelectAllCheckbox();
    }

    // Initialize all modals only once when the modal is triggered by the "Edit" button
    function initializeModalOnClick(roleId) {
        const modal = document.getElementById(`edit_role${roleId}`);
        
        // Initialize modal when it's opened
        modal.addEventListener('shown.bs.modal', function () {
            initializeModal(roleId);  // Call the modal initialization function
        });
    }



    function getCheckedValues(uniqueId) {
        // Fetch all checked checkboxes and map to their values, confirming it's an array
        const checkedValues = Array.from(document.querySelectorAll(`#edit_role_form${uniqueId} .permission-checkbox:checked`))
                                .map(cb => cb.value);
        return checkedValues;
    }

    
    function filterRole () {
        const roleFilter = document.getElementById('roleFilter');
        const roleCards = document.querySelectorAll('.role-card');

        // Listen for changes to the dropdown
        roleFilter.addEventListener('change', function () {
            const selectedRole = roleFilter.value.toLowerCase(); // Get the selected role value and convert it to lowercase

            // Loop through all the cards and filter them based on the selected role
            roleCards.forEach(card => {
                const cardRole = card.getAttribute('data-role').toLowerCase(); // Get the role from the card’s data attribute

                // If the selected role is empty, show all cards. Otherwise, filter based on the selected role
                if (selectedRole === "" || cardRole.includes(selectedRole)) {
                    card.style.display = 'block'; // Show matching card
                } else {
                    card.style.display = 'none'; // Hide non-matching card
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        filterRole();
    });
   
    
    function showAllPermissions(roleId) {
        const modalId = `#permissionsModal${roleId}`;
        const modal = new bootstrap.Modal(document.querySelector(modalId));
        modal.show();
    }
</script>


