<div class="modal fade delete-user-modal" id="editUserModal{{$employee->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-750px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('auth.edit_employee') }}</h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>
            <div class="modal-body scroll-y mx-lg-5 my-7">
                <div id="status"></div>
                <form id="edit_user_form{{ $employee->id }}" class="form">
                    @csrf
                    @method('PUT')
                    
                    <!-- Employee ID Display -->
                    <div class="row mb-8">
                        <div class="col-12">
                            <div class="alert alert-primary d-flex align-items-center p-5">
                                <i class="ki-duotone ki-information fs-2hx me-4"></i>
                                <div class="d-flex flex-column">
                                    <h4 class="mb-1 text-primary">{{ __('auth.employee_info') }}</h4>
                                    <span>{{ __('auth.employee_id') }}: <strong>ID-{{ $employee->id }}</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <!-- Email -->
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._email')}}</span>
                                </label>
                                <input type="email" class="form-control form-control-solid" name="email" value="{{ $employee->email }}" />
                                <div id="email{{ $employee->id }}"></div>
                            </div>

                            <!-- Phone -->
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._phone')}}</span>
                                </label>
                                <input type="number" class="form-control form-control-solid" name="phone" value="{{ $employee->phone }}" />
                                <div id="phone{{ $employee->id }}"></div>
                            </div>

                            <!-- Salary -->
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth.salary')}}</span>
                                </label>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="salary" value="{{ $employee->salary }}" placeholder="0.00" />
                                <div id="salary{{ $employee->id }}"></div>
                            </div>

                            <!-- Salary Type -->
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth.salary_type')}}</span>
                                </label>
                                <select class="form-select form-select-solid" name="salary_type">
                                    <option value="">Select Salary Type</option>
                                    <option value="hourly" {{ $employee->salary_type == 'hourly' ? 'selected' : '' }}>{{__('auth.Hourly')}}</option>
                                    <option value="weekly" {{ $employee->salary_type == 'weekly' ? 'selected' : '' }}>{{__('auth.Weekly')}}</option>
                                    <option value="monthly" {{ $employee->salary_type == 'monthly' ? 'selected' : '' }}>{{__('auth.Monthly')}}</option>
                                    <option value="annual" {{ $employee->salary_type == 'annual' ? 'selected' : '' }}>{{__('auth.Annual')}}</option>
                                </select>
                                <div id="salary_type{{ $employee->id }}"></div>
                            </div>

                            <!-- Hire Date -->
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth.hire_date')}}</span>
                                </label>
                                <input type="date" class="form-control form-control-solid" name="hire_date" value="{{ $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '' }}" />
                                <div id="hire_date{{ $employee->id }}"></div>
                            </div>

                            <!-- Termination Date -->
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{__('auth.termination_date')}}</span>
                                </label>
                                <input type="date" class="form-control form-control-solid" name="termination_date" value="{{ $employee->termination_date ? $employee->termination_date->format('Y-m-d') : '' }}" />
                                <div id="termination_date{{ $employee->id }}"></div>
                            </div>

                            <!-- Job Title -->
                            <div class="d-flex flex-column mb-8 fv-row col-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{__('auth.job_title')}}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="job_title" value="{{ $employee->job_title }}" placeholder="Job Title" />
                                <div id="job_title{{ $employee->id }}"></div>
                            </div>

                            <!-- Status
                            <div class="d-flex flex-column mb-8 fv-row col-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{__('auth.status')}}</span>
                                </label>
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active{{ $employee->id }}" {{ $employee->is_active ? 'checked' : '' }} />
                                    <label class="form-check-label" for="is_active{{ $employee->id }}">
                                        {{ $employee->is_active ? 'Active' : 'Inactive' }}
                                    </label>
                                </div>
                            </div> -->
                            
                        </div>
                    </div>
                        
                    <div class="text-center pt-15">
                        <button type="reset" class="btn btn-light me-3" id="closeModalEditButton{{ $employee->id }}" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button type="button" onclick="editUserInstanceLoop({{$employee->id }})" class="btn btn-primary" id="submitEmplButton{{ $employee->id }}">
                            <span class="indicator-label">{{ __('auth._update') }}</span>
                            <span class="indicator-progress" style="display: none;">
                                {{__('auth.please_wait')}}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>