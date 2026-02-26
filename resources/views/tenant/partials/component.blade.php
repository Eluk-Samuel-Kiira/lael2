@role('super_admin')
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
                                <select name="status" class="form-select form-select-solid form-select-sm" onchange="updateStatusTenant({{ $tenant->id }}, this.value)" disabled>
                                    <option value="active" {{ $tenant->status == 'active' ? 'selected' : '' }}>{{__('payments.active')}}</option>
                                    <option value="suspended" {{ $tenant->status == 'suspended' ? 'selected' : '' }}>{{__('payments.suspended')}}</option>
                                    <option value="trial" {{ $tenant->status == 'trial' ? 'selected' : '' }}>{{__('payments.trial')}}</option>
                                </select>
                            </td>
                            <td>
                                <div class="d-flex gap-2 justify-content-end">
                                    @role('super_admin')
                                        <!-- Admin Users Button -->
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-dark d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#adminUsersTenant{{$tenant->id}}"
                                            title="{{ __('payments.admin_users') }}">
                                            <i class="bi bi-people-fill me-1 fs-5"></i> <span>{{ __('payments.admins') }}</span>
                                        </button>

                                        <!-- Edit Tenant Button -->
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-warning d-flex align-items-center px-3 py-2" 
                                            onclick="window.location.href='{{ route('tenant.edit', $tenant->id) }}'"
                                            title="{{ __('auth._edit') }}">
                                            <i class="bi bi-pencil-square me-1 fs-5"></i> <span>{{ __('auth._edit') }}</span>
                                        </button>

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

                                        <!-- <button type="button" 
                                            class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteTenantModal{{$tenant->id}}">
                                            <i class="bi bi-trash me-1 fs-5"></i> <span>{{ __('auth._delete') }}</span>
                                        </button> -->
                                    @endrole
                                </div>

                                @include('tenant.partials.modals.configuration-modal')
                                @include('tenant.partials.modals.admins-modal')
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
@endrole
