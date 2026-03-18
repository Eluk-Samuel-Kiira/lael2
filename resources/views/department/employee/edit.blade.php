<div class="modal fade delete-user-modal" id="editUserModal{{$employee->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('auth.edit_employee') }}</h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>
            <div class="modal-body scroll-y mx-lg-5 my-7" style="max-height: 70vh;">
                <div id="status"></div>
                <form id="edit_user_form{{ $employee->id }}" class="form" enctype="multipart/form-data">
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

                    <!-- Personal Information Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('auth.personal_information') }}</h3>
                    </div>
                    
                    <div class="row g-9 mb-8">
                        <!-- First Name -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">{{__('auth.first_name')}}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" name="first_name" value="{{ $employee->first_name }}" readonly/>
                            <div id="first_name{{ $employee->id }}"></div>
                        </div>

                        <!-- Last Name -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">{{__('auth.last_name')}}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" name="last_name" value="{{ $employee->last_name }}" readonly/>
                            <div id="last_name{{ $employee->id }}"></div>
                        </div>

                        <!-- Email -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">{{__('auth._email')}}</span>
                            </label>
                            <input type="email" class="form-control form-control-solid" name="email" value="{{ $employee->email }}" readonly/>
                            <div id="email{{ $employee->id }}"></div>
                        </div>

                        <!-- Phone -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">{{__('auth._phone')}}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" name="phone" value="{{ $employee->phone }}" readonly/>
                            <div id="phone{{ $employee->id }}"></div>
                        </div>

                        <!-- Gender -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.gender')}}</span>
                            </label>
                            <select class="form-select form-select-solid" name="gender">
                                <option value="">{{ __('auth.select_gender') }}</option>
                                <option value="male" {{ $employee->gender == 'male' ? 'selected' : '' }}>{{__('auth.male')}}</option>
                                <option value="female" {{ $employee->gender == 'female' ? 'selected' : '' }}>{{__('auth.female')}}</option>
                                <option value="other" {{ $employee->gender == 'other' ? 'selected' : '' }}>{{__('auth.other')}}</option>
                            </select>
                            <div id="gender{{ $employee->id }}"></div>
                        </div>

                        <!-- Date of Birth -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.date_of_birth')}}</span>
                            </label>
                            <input type="date" class="form-control form-control-solid" name="date_of_birth" value="{{ $employee->date_of_birth ? $employee->date_of_birth->format('Y-m-d') : '' }}" />
                            <div id="date_of_birth{{ $employee->id }}"></div>
                        </div>

                        <!-- Residence -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-12">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.residence')}}</span>
                            </label>
                            <textarea class="form-control form-control-solid" name="residence" rows="2">{{ $employee->residence }}</textarea>
                            <div id="residence{{ $employee->id }}"></div>
                        </div>
                    </div>

                    <!-- Employment Details Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('auth.employment_details') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Department -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.department')}}</span>
                            </label>
                            <select class="form-select form-select-solid" name="department_id" readonly>
                                <option value="">{{ __('auth.select_department') }}</option>
                                @foreach($departments ?? [] as $department)
                                    <option value="{{ $department->id }}" {{ $employee->department_id == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                                @endforeach
                            </select>
                            <div id="department_id{{ $employee->id }}"></div>
                        </div>

                        <!-- Job Title -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.job_title')}}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" name="job_title" value="{{ $employee->job_title }}" placeholder="Job Title" />
                            <div id="job_title{{ $employee->id }}"></div>
                        </div>

                        <!-- Employee Type -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">{{__('auth.employee_type')}}</span>
                            </label>
                            <select class="form-select form-select-solid" name="employee_type">
                                <option value="">{{ __('auth.select_employee_type') }}</option>
                                <option value="permanent" {{ $employee->employee_type == 'permanent' ? 'selected' : '' }}>{{__('auth.permanent')}}</option>
                                <option value="contract" {{ $employee->employee_type == 'contract' ? 'selected' : '' }}>{{__('auth.contract')}}</option>
                                <option value="casual" {{ $employee->employee_type == 'casual' ? 'selected' : '' }}>{{__('auth.casual')}}</option>
                                <option value="temporary" {{ $employee->employee_type == 'temporary' ? 'selected' : '' }}>{{__('auth.temporary')}}</option>
                                <option value="intern" {{ $employee->employee_type == 'intern' ? 'selected' : '' }}>{{__('auth.intern')}}</option>
                                <option value="probation" {{ $employee->employee_type == 'probation' ? 'selected' : '' }}>{{__('auth.probation')}}</option>
                            </select>
                            <div id="employee_type{{ $employee->id }}"></div>
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

                        <!-- Status -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.status')}}</span>
                            </label>
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active{{ $employee->id }}" {{ $employee->is_active ? 'checked' : '' }} />
                                <label class="form-check-label" for="is_active{{ $employee->id }}">
                                    {{ $employee->is_active ? 'Active' : 'Inactive' }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Salary Information Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('auth.salary_information') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
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
                                <option value="hourly" {{ $employee->salary_type == 'hourly' ? 'selected' : '' }}>{{__('auth.Hourly')}}</option>
                                <option value="weekly" {{ $employee->salary_type == 'weekly' ? 'selected' : '' }}>{{__('auth.Weekly')}}</option>
                                <option value="monthly" {{ $employee->salary_type == 'monthly' ? 'selected' : '' }}>{{__('auth.Monthly')}}</option>
                                <option value="quarterly" {{ $employee->salary_type == 'quarterly' ? 'selected' : '' }}>{{__('auth.Quarterly')}}</option>
                                <option value="annual" {{ $employee->salary_type == 'annual' ? 'selected' : '' }}>{{__('auth.Annual')}}</option>
                            </select>
                        </div>
                        <div id="salary_type{{ $employee->id }}"></div>

                        <!-- Recurring Salary -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_salary_recurring" value="1" id="is_salary_recurring{{ $employee->id }}" {{ $employee->is_salary_recurring ? 'checked' : '' }} />
                                <label class="form-check-label" for="is_salary_recurring{{ $employee->id }}">
                                    {{ __('auth.recurring_salary') }}
                                </label>
                            </div>
                            <div id="is_salary_recurring{{ $employee->id }}"></div>
                        </div>

                        <!-- Recurring Day -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.recurring_day')}}</span>
                            </label>
                            <input type="number" min="1" max="31" class="form-control form-control-solid" name="recurring_day" value="{{ $employee->recurring_day }}" placeholder="1-31" />
                            <div id="recurring_day{{ $employee->id }}"></div>
                        </div>
                    </div>

                    <!-- Tax & Social Security Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('auth.tax_social_security') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- NSSF Number -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.nssf_number')}}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" name="nssf_number" value="{{ $employee->nssf_number }}" placeholder="NSSF-XXXX-XXXX" />
                            <div id="nssf_number{{ $employee->id }}"></div>
                        </div>

                        <!-- TIN Number -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.tin_number')}}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" name="tin_number" value="{{ $employee->tin_number }}" placeholder="TIN-XXXX-XXXX-XXXX" />
                            <div id="tin_number{{ $employee->id }}"></div>
                        </div>
                    </div>

                    <!-- Bank Details Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('auth.bank_details') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Bank Name -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.bank_name')}}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" name="bank_name" value="{{ $employee->bank_name }}" />
                            <div id="bank_name{{ $employee->id }}"></div>
                        </div>

                        <!-- Bank Account Number -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.bank_account')}}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" name="bank_account_number" value="{{ $employee->bank_account_number }}" />
                            <div id="bank_account_number{{ $employee->id }}"></div>
                        </div>

                        <!-- Bank Branch -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.bank_branch')}}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" name="bank_branch" value="{{ $employee->bank_branch }}" />
                            <div id="bank_branch{{ $employee->id }}"></div>
                        </div>
                    </div>

                    <!-- Identification Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('auth.identification') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- ID Type -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.id_type')}}</span>
                            </label>
                            <select class="form-select form-select-solid" name="id_type">
                                <option value="">{{ __('auth.select_id_type') }}</option>
                                <option value="national_id" {{ $employee->id_type == 'national_id' ? 'selected' : '' }}>{{__('auth.national_id')}}</option>
                                <option value="passport" {{ $employee->id_type == 'passport' ? 'selected' : '' }}>{{__('auth.passport')}}</option>
                                <option value="drivers_license" {{ $employee->id_type == 'drivers_license' ? 'selected' : '' }}>{{__('auth.drivers_license')}}</option>
                                <option value="voters_card" {{ $employee->id_type == 'voters_card' ? 'selected' : '' }}>{{__('auth.voters_card')}}</option>
                                <option value="other" {{ $employee->id_type == 'other' ? 'selected' : '' }}>{{__('auth.other')}}</option>
                            </select>
                            <div id="id_type{{ $employee->id }}"></div>
                        </div>

                        <!-- ID Number -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.id_number')}}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" name="id_number" value="{{ $employee->id_number }}" />
                            <div id="id_number{{ $employee->id }}"></div>
                        </div>

                        <!-- Qualification -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-12">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.qualification')}}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" name="qualification" value="{{ $employee->qualification }}" placeholder="Highest Qualification" />
                            <div id="qualification{{ $employee->id }}"></div>
                        </div>
                    </div>

                    <!-- Next of Kin Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('auth.next_of_kin') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Next of Kin Name -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.next_of_kin_name')}}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" name="next_of_kin_name" value="{{ $employee->next_of_kin_name }}" />
                            <div id="next_of_kin_name{{ $employee->id }}"></div>
                        </div>

                        <!-- Next of Kin Contact -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.next_of_kin_contact')}}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" name="next_of_kin_contact" value="{{ $employee->next_of_kin_contact }}" />
                            <div id="next_of_kin_contact{{ $employee->id }}"></div>
                        </div>

                        <!-- Next of Kin Relationship -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.next_of_kin_relationship')}}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" name="next_of_kin_relationship" value="{{ $employee->next_of_kin_relationship }}" />
                            <div id="next_of_kin_relationship{{ $employee->id }}"></div>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('auth.notes') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-12">
                            <textarea class="form-control form-control-solid" name="notes" rows="3" placeholder="{{ __('auth.additional_notes') }}">{{ $employee->notes }}</textarea>
                            <div id="notes{{ $employee->id }}"></div>
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