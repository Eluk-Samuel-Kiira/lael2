<div class="modal fade" id="employeeEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('auth.edit_employee') }} — <span id="employeeEditHeaderName">—</span></h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-lg-5 my-7" style="max-height: 70vh;">
                <form id="employeeEditForm" class="form" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="employeeEditId">

                    {{-- Personal Information --}}
                    <div class="separator separator-dashed my-8"><h3 class="text-dark">{{ __('auth.personal_information') }}</h3></div>
                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('auth.first_name') }}</label>
                            <input type="text" name="first_name" class="form-control form-control-solid" readonly>
                            <div id="first_name"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('auth.last_name') }}</label>
                            <input type="text" name="last_name" class="form-control form-control-solid" readonly>
                            <div id="last_name"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('auth._email') }}</label>
                            <input type="email" name="email" class="form-control form-control-solid" readonly>
                            <div id="email"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth._phone') }}</label>
                            <input type="text" name="phone" class="form-control form-control-solid" readonly>
                            <div id="phone"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.gender') }}</label>
                            <select name="gender" class="form-select form-select-solid">
                                <option value="">{{ __('auth.select_gender') }}</option>
                                <option value="male">{{ __('auth.male') }}</option>
                                <option value="female">{{ __('auth.female') }}</option>
                                <option value="other">{{ __('auth.other') }}</option>
                            </select>
                            <div id="gender"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.date_of_birth') }}</label>
                            <input type="date" name="date_of_birth" class="form-control form-control-solid">
                            <div id="date_of_birth"></div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">{{ __('auth.residence') }}</label>
                            <textarea name="residence" class="form-control form-control-solid" rows="2"></textarea>
                            <div id="residence"></div>
                        </div>
                    </div>

                    {{-- Employment Details --}}
                    <div class="separator separator-dashed my-8"><h3 class="text-dark">{{ __('auth.employment_details') }}</h3></div>
                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.department') }}</label>
                            <select name="department_id" class="form-select form-select-solid" disabled>
                                <option value="">{{ __('auth.select_department') }}</option>
                            </select>
                            <div id="department_id"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.job_title') }}</label>
                            <input type="text" name="job_title" class="form-control form-control-solid">
                            <div id="job_title"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('auth.employee_type') }}</label>
                            <select name="employee_type" class="form-select form-select-solid">
                                <option value="">{{ __('auth.select_employee_type') }}</option>
                                <option value="permanent">{{ __('auth.permanent') }}</option>
                                <option value="contract">{{ __('auth.contract') }}</option>
                                <option value="casual">{{ __('auth.casual') }}</option>
                                <option value="temporary">{{ __('auth.temporary') }}</option>
                                <option value="intern">{{ __('auth.intern') }}</option>
                                <option value="probation">{{ __('auth.probation') }}</option>
                            </select>
                            <div id="employee_type"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('auth.hire_date') }}</label>
                            <input type="date" name="hire_date" class="form-control form-control-solid">
                            <div id="hire_date"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.termination_date') }}</label>
                            <input type="date" name="termination_date" class="form-control form-control-solid">
                            <div id="termination_date"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.status') }}</label>
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input type="checkbox" name="is_active" value="1" id="employeeEditIsActive" class="form-check-input">
                                <label class="form-check-label" for="employeeEditIsActive">Active</label>
                            </div>
                        </div>
                    </div>

                    {{-- Salary --}}
                    <div class="separator separator-dashed my-8"><h3 class="text-dark">{{ __('auth.salary_information') }}</h3></div>
                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('auth.salary') }}</label>
                            <input type="number" step="0.01" name="salary" class="form-control form-control-solid">
                            <div id="salary"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('auth.salary_type') }}</label>
                            <select name="salary_type" class="form-select form-select-solid">
                                <option value="hourly">{{ __('auth.Hourly') }}</option>
                                <option value="weekly">{{ __('auth.Weekly') }}</option>
                                <option value="monthly">{{ __('auth.Monthly') }}</option>
                                <option value="quarterly">{{ __('auth.Quarterly') }}</option>
                                <option value="annual">{{ __('auth.Annual') }}</option>
                            </select>
                            <div id="salary_type"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input type="checkbox" name="is_salary_recurring" value="1" id="employeeEditRecurring" class="form-check-input">
                                <label class="form-check-label" for="employeeEditRecurring">{{ __('auth.recurring_salary') }}</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.recurring_day') }}</label>
                            <input type="number" min="1" max="31" name="recurring_day" class="form-control form-control-solid">
                            <div id="recurring_day"></div>
                        </div>
                    </div>

                    {{-- Tax & Social --}}
                    <div class="separator separator-dashed my-8"><h3 class="text-dark">{{ __('auth.tax_social_security') }}</h3></div>
                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.nssf_number') }}</label>
                            <input type="text" name="nssf_number" class="form-control form-control-solid">
                            <div id="nssf_number"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.tin_number') }}</label>
                            <input type="text" name="tin_number" class="form-control form-control-solid">
                            <div id="tin_number"></div>
                        </div>
                    </div>

                    {{-- Bank --}}
                    <div class="separator separator-dashed my-8"><h3 class="text-dark">{{ __('auth.bank_details') }}</h3></div>
                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.bank_name') }}</label>
                            <input type="text" name="bank_name" class="form-control form-control-solid">
                            <div id="bank_name"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.bank_account') }}</label>
                            <input type="text" name="bank_account_number" class="form-control form-control-solid">
                            <div id="bank_account_number"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.bank_branch') }}</label>
                            <input type="text" name="bank_branch" class="form-control form-control-solid">
                            <div id="bank_branch"></div>
                        </div>
                    </div>

                    {{-- Identification --}}
                    <div class="separator separator-dashed my-8"><h3 class="text-dark">{{ __('auth.identification') }}</h3></div>
                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.id_type') }}</label>
                            <select name="id_type" class="form-select form-select-solid">
                                <option value="">{{ __('auth.select_id_type') }}</option>
                                <option value="national_id">{{ __('auth.national_id') }}</option>
                                <option value="passport">{{ __('auth.passport') }}</option>
                                <option value="drivers_license">{{ __('auth.drivers_license') }}</option>
                                <option value="voters_card">{{ __('auth.voters_card') }}</option>
                                <option value="other">{{ __('auth.other') }}</option>
                            </select>
                            <div id="id_type"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.id_number') }}</label>
                            <input type="text" name="id_number" class="form-control form-control-solid">
                            <div id="id_number"></div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">{{ __('auth.qualification') }}</label>
                            <input type="text" name="qualification" class="form-control form-control-solid">
                            <div id="qualification"></div>
                        </div>
                    </div>

                    {{-- Next of Kin --}}
                    <div class="separator separator-dashed my-8"><h3 class="text-dark">{{ __('auth.next_of_kin') }}</h3></div>
                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.next_of_kin_name') }}</label>
                            <input type="text" name="next_of_kin_name" class="form-control form-control-solid">
                            <div id="next_of_kin_name"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.next_of_kin_contact') }}</label>
                            <input type="text" name="next_of_kin_contact" class="form-control form-control-solid">
                            <div id="next_of_kin_contact"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.next_of_kin_relationship') }}</label>
                            <input type="text" name="next_of_kin_relationship" class="form-control form-control-solid">
                            <div id="next_of_kin_relationship"></div>
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="separator separator-dashed my-8"><h3 class="text-dark">{{ __('auth.notes') }}</h3></div>
                    <div class="row g-5 mb-4">
                        <div class="col-12">
                            <textarea name="notes" class="form-control form-control-solid" rows="3"></textarea>
                            <div id="notes"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button type="button" id="employeeEditSubmit" class="btn btn-primary" style="min-width: 130px;">
                            <span class="indicator-label"><i class="bi bi-check2 me-1"></i>{{ __('auth._update') }}</span>
                            <span class="indicator-progress" style="display: none;">{{ __('auth.please_wait') }}<span class="spinner-border spinner-border-sm ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>