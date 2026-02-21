<div class="card-body py-4" id="reloadtenantComponent">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input"
                                type="checkbox"
                                data-kt-check="true"
                                data-kt-check-target="#kt_table_users .row-checkbox"
                                value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('pagination._id')}}</th>
                    <th class="min-w-125px">{{__('payments.name')}}</th> 
                    <th class="min-w-125px">{{__('payments.subdomain')}}</th> 
                    <th class="min-w-125px">{{__('auth.created_at')}}</th>
                    <th class="min-w-125px">{{__('auth._status')}}</th>
                    <th class="min-w-300px text-end">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($tenants) && $tenants->count() > 0)
                    @foreach ($tenants as $tenant)
                        <tr data-role="{{ strtolower($tenant->name) }}">
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input row-checkbox" type="checkbox" value="{{ $tenant->id }}" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{__('ID-')}}{{ $tenant->id }}</div>
                            </td>
                            <td>{{ $tenant->name }}</td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $tenant->subdomain }}</div>
                            </td>
                            <td>{{ $tenant->created_at->format('d M Y, h:i a') }}</td>
                            <td>
                                <select name="status" class="form-select form-select-solid form-select-sm" onchange="updateStatusTenant({{ $tenant->id }}, this.value)"
                                @cannot('update uom') disabled @endcannot>
                                    <option value="active" {{ $tenant->status == 'active' ? 'selected' : '' }}>{{__('payments.active')}}</option>
                                    <option value="suspended" {{ $tenant->status == 'suspended' ? 'selected' : '' }}>{{__('payments.suspended')}}</option>
                                    <option value="trial" {{ $tenant->status == 'trial' ? 'selected' : '' }}>{{__('payments.trial')}}</option>
                                </select>
                            </td>
                            <td>
                                <div class="d-flex gap-2 justify-content-end">
                                    @can('view tenant')
                                        <!-- Configuration Button -->
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-info d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#configTenant{{$tenant->id}}">
                                            <i class="bi bi-gear me-1 fs-5"></i> <span>{{ __('payments.config') }}</span>
                                        </button>

                                        <!-- Settings Button -->
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#settingsTenant{{$tenant->id}}">
                                            <i class="bi bi-sliders2 me-1 fs-5"></i> <span>{{ __('payments.settings') }}</span>
                                        </button>

                                        <!-- Usage Button -->
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-success d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#usageTenant{{$tenant->id}}">
                                            <i class="bi bi-graph-up me-1 fs-5"></i> <span>{{ __('payments.usage') }}</span>
                                        </button>

                                        <!-- App Settings Button -->
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-warning d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#appSettingsTenant{{$tenant->id}}">
                                            <i class="bi bi-app-indicator me-1 fs-5"></i> <span>{{ __('payments.app') }}</span>
                                        </button>
                                    @endcan

                                    @can('delete uom')
                                        <button type="button" 
                                            class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteTenantModal{{$tenant->id}}">
                                            <i class="bi bi-trash me-1 fs-5"></i> <span>{{ __('auth._delete') }}</span>
                                        </button>
                                    @endcan
                                </div>

                                @include('tenant.partials.modals.configuration-modal')
                                @include('tenant.partials.modals.settings-modal')
                                @include('tenant.partials.modals.usage-modal')
                                @include('tenant.partials.modals.app-settings-modal')
                                @include('tenant.partials.modals.delete-modal')

                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

<script>
function updateStatusTenant(tenantId, status) {
    // Add your status update logic here
    console.log('Update tenant', tenantId, 'to status', status);
}

function deleteItem(button) {
    const btn = $(button);
    const url = btn.data('item-url');
    const id = btn.data('item-id');
    
    // Show loading state
    btn.find('.indicator-label').hide();
    btn.find('.indicator-progress').show();
    btn.prop('disabled', true);
    
    // Perform delete
    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal and refresh
            $(`#deleteTenantModal${id}`).modal('hide');
            // Option 1: Reload the page
            location.reload();
            // Option 2: Remove the row from table (if you prefer)
            // $(`#deleteTenantModal${id}`).closest('tr').remove();
        } else {
            // Reset button state
            btn.find('.indicator-label').show();
            btn.find('.indicator-progress').hide();
            btn.prop('disabled', false);
            
            // Show error message
            alert(data.message || 'Error deleting tenant');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Reset button state
        btn.find('.indicator-label').show();
        btn.find('.indicator-progress').hide();
        btn.prop('disabled', false);
        
        alert('Error deleting tenant');
    });
}
</script>