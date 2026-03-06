@can('view employee')
<div class="card-body py-4" id="employeeUserIndexTable">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_users .form-check-input" value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{ __('pagination.id') }}</th>
                    <th class="min-w-125px">{{ __('pagination.employee') }}</th>
                    <th class="min-w-125px">{{ __('pagination.department') }}</th>
                    <th class="min-w-125px">{{ __('pagination.email') }}</th>
                    <th class="min-w-125px">{{ __('pagination.phone') }}</th>
                    <th class="min-w-125px">{{ __('pagination.job_title') }}</th>
                    <th class="min-w-125px">{{ __('pagination.salary') }}</th>
                    <th class="min-w-125px">{{ __('pagination.hire_date') }}</th>
                    <th class="min-w-125px">{{ __('pagination.term_date') }}</th>
                    <th class="min-w-125px">{{ __('pagination.status') }}</th>
                    <th class="text-end min-w-100px">{{ __('pagination.actions') }}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($employees) && $employees->count() > 0)
                    @foreach ($employees as $employee)
                        <tr>
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ __('pagination.id') }}-{{ $employee->id }}</div>
                            </td>
                            <td class="d-flex align-items-center">
                                <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                    <a href="#">
                                        <div class="symbol-label">
                                            <img src="{{ employeeProfileImage($employee->user->profile_image) }}" alt="{{ $employee->first_name }}" class="w-100" />
                                        </div>
                                    </a>
                                </div>
                                <div class="d-flex flex-column">
                                    <a href="#" class="text-gray-800 text-hover-primary mb-1">{{ $employee->first_name . ' ' . $employee->last_name }}</a>
                                    <span>{{ $employee->email }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $employee->department->name ?? __('payments.none') }}</div>
                            </td>
                            <td>{{ $employee->email }}</td>
                            <td>{{ $employee->phone ?? 'N/A' }}</td>
                            <td>{{ $employee->job_title ?? 'N/A' }}</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="badge badge-light fw-bold">{{ $employee->salary }} - {{ currency_symbol() }}</span>
                                    @if($employee->salary_type)
                                        <small class="text-muted mt-1">({{ $employee->salary_type }})</small>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $employee->hire_date->format('d M Y') }}</td>
                            <td>
                                @if($employee->termination_date)
                                    {{ $employee->termination_date->format('d M Y') }}
                                @else
                                    <span class="text-muted">- - -</span>
                                @endif
                            </td>
                            <td>
                                <select name="status" class="form-select form-select-solid form-select-sm" onchange="updateUserStatus({{ $employee->id }}, this.value)"
                                @cannot('update employee') disabled @endcannot>
                                    <option value="1" {{ $employee->is_active == 1 ? 'selected' : '' }}>{{__('payments.active') }}</option>
                                    <option value="0" {{ $employee->is_active == 0 ? 'selected' : '' }}>{{__('payments.inactive') }}</option>
                                </select>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('edit employee')
                                        <button 
                                        class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2" 
                                        data-bs-toggle="modal" 
                                            data-bs-target="#editUserModal{{$employee->id}}">
                                            <i class="bi bi-pencil-square me-1 fs-5"></i> <span>{{ __('auth._edit') }}</span>
                                            </button>
                                    @endcan
                                </div>

                                @include('department.employee.edit')
                                
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="11" class="text-center py-4">{{ __('No employees found.') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endcan