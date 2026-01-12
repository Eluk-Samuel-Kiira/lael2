<div class="card-body py-4" id="reloadEmployeeComponent">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_users .form-check-input" value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('auth.employee_id')}}</th>
                    <th class="min-w-125px">{{__('auth._name')}}</th>
                    <th class="min-w-125px">{{__('auth._role')}}</th>
                    <th class="min-w-125px">{{__('auth._phone')}}</th>
                    <th class="min-w-125px">{{__('auth._department')}}</th>
                    <th class="min-w-125px">{{__('pagination._location')}}</th>
                    <th class="min-w-125px">{{__('auth.job_title')}}</th>
                    <th class="min-w-125px">{{__('auth.created_at')}}</th>
                    <th class="min-w-125px">{{__('auth._status')}}</th>
                    <th class="min-w-125px text-end">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($all_employees) && $all_employees->count() > 0)
                    @foreach ($all_employees as $employee)
                        <tr data-role="{{ strtolower($employee->role) }}">
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{__('auth._empl')}}{{ $employee->id }}</div>
                            </td>
                            <td class="d-flex align-items-center">
                                <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                    <a href="#">
                                        <div class="symbol-label">
                                            <img src="{{ employeeProfileImage($employee->profile_image) }}" alt="{{ $employee->first_name }}" class="w-100" />
                                        </div>
                                    </a>
                                </div>
                                <div class="d-flex flex-column">
                                    <a href="#" class="text-gray-800 text-hover-primary mb-1">{{ $employee->first_name . ' ' . $employee->last_name }}</a>
                                    <span>{{ $employee->email }}</span>
                                </div>
                            </td>
                            <td>{{ ucwords(str_replace('_', ' ', $employee->userRole->name)) ?? 'None'}}</td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $employee->telephone_number }}</div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $employee->userDepartment->name ?? 'None'}}</div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $employee->userLocation->name ?? 'None'}}</div>
                            </td>
                            <td>{{ $employee->job_title }}</td>
                            <td>{{ $employee->created_at->format('d M Y, h:i a') }}</td>
                            <td>
                                <select name="status" class="form-select form-select-solid form-select-sm" onchange="updateEmployeeStatus({{ $employee->id }}, this.value)"
                                @cannot('update user') disabled @endcannot>
                                    <option value="active" {{ $employee->status === 'active' ? 'selected' : '' }}><span>{{__('auth._active')}}</span></option>
                                    <option value="inactive" {{ $employee->status === 'inactive' ? 'selected' : '' }}>{{__('auth._inactive')}}</option>
                                </select>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('update user')
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2"  
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editUserDeptModal{{$employee->id}}">
                                            <i class="bi bi-building me-1 fs-5"></i> <span>{{ __('pagination._allocation') }}</span>
                                        </button>
                                    @endcan
                                    @can('edit user')
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2"  
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editUserModal{{$employee->id}}">
                                            <i class="bi bi-pencil-square me-1 fs-5"></i> <span>{{ __('auth._edit') }}</span>
                                        </button>
                                    @endcan
                                    {{--
                                    @can('delete user')
                                        <button type="button" 
                                            class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteUserModal{{$employee->id}}">
                                            <i class="bi bi-trash me-1 fs-5"></i> <span>{{ __('auth._delete') }}</span>                                
                                        </button>
                                    @endcan 
                                    --}}
                                </div>

                                <!-- Delete User Modal -->
                                <div class="modal fade" id="deleteUserModal{{$employee->id}}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">{{ __('auth.confirm_deletion') }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>{{ __('auth.are_you_sure') }}</p>
                                                <p>{{ __('auth.action_cannot') }}</p>
                                            </div>
                                            <div class="modal-footer">
                                                <!-- Discard Button -->
                                                <button type="button" id="closeDeleteModal{{$employee->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                                                <!-- Confirm Button -->
                                                <button type="button" id="deleteButton{{$employee->id}}" class="btn btn-danger" 
                                                    data-item-url="{{ route('employee.destroy', $employee->id) }}" 
                                                    data-item-id="{{ $employee->id }}"
                                                    onclick="deleteItem(this)">
                                                    <span class="indicator-label">{{ __('auth._confirm') }}</span>
                                                    <span class="indicator-progress" style="display: none;">
                                                        {{__('auth.please_wait') }}
                                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                                    </span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @include('human-resource.partial.edit-user')
                                @include('human-resource.partial.user-dept')

                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

