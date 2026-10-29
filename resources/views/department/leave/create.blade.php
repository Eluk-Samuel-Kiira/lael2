{{-- resources/views/tenant/leave/modals/create.blade.php --}}
<div class="modal fade" id="kt_modal_add_leave" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('payments.request_leave') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_leave_form" class="form">
                    @csrf
                    
                    <div class="row g-9 mb-8">
                        <!-- Employee -->
                        <div class="col-md-12 fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('payments.employee') }}</label>
                            @php
                                $formattedEmployees = [];
                                foreach($employees as $emp) {
                                    $formattedEmployees[] = (object)[
                                        'id' => $emp->id,
                                        'name' => $emp->first_name . ' ' . $emp->last_name
                                    ];
                                }
                            @endphp
                            <x-typable-select 
                                name="employee_id"
                                :options="$formattedEmployees"
                                selected="{{ $expense->employee_id ?? '' }}"
                                placeholder="Type or select employee..."
                                required="true"
                            />
                        </div>

                        <!-- Leave Type -->
                        <div class="col-md-6 fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('payments.leave_type') }}</label>
                            <select name="leave_type" class="form-select form-select-solid" id="leave_type" required>
                                <option value="">{{ __('payments.select_type') }}</option>
                                <option value="annual">{{ __('payments.annual') }}</option>
                                <option value="sick">{{ __('payments.sick') }}</option>
                                <option value="maternity">{{ __('payments.maternity') }}</option>
                                <option value="paternity">{{ __('payments.paternity') }}</option>
                                <option value="bereavement">{{ __('payments.bereavement') }}</option>
                                <option value="study">{{ __('payments.study') }}</option>
                                <option value="unpaid">{{ __('payments.unpaid') }}</option>
                                <option value="other">{{ __('payments.other') }}</option>
                            </select>
                            <div id="leave_type"></div>
                        </div>

                        <!-- Custom Type (for 'other') -->
                        <div class="col-md-6 fv-row mb-7" id="custom_type_field" style="display: none;">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('payments.custom_type') }}</label>
                            <input type="text" name="custom_type" class="form-control form-control-solid" placeholder="e.g., Sabbatical">
                            <div id="custom_type"></div>
                        </div>

                        <!-- Date Range -->
                        <div class="col-md-6 fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('payments.start_date') }}</label>
                            <input type="date" name="start_date" class="form-control form-control-solid" id="start_date" required>
                            <div id="start_date"></div>
                        </div>

                        <div class="col-md-6 fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('payments.end_date') }}</label>
                            <input type="date" name="end_date" class="form-control form-control-solid" id="end_date" required>
                            <div id="end_date"></div>
                        </div>

                        <!-- Days Preview -->
                        <div class="col-md-12 mb-7">
                            <div class="bg-light-info rounded p-4">
                                <span class="fw-semibold text-gray-600">{{ __('payments.total_days') }}:</span>
                                <span class="fw-bold fs-2 text-info" id="total_days_preview">0</span>
                                <span class="text-gray-600">days</span>
                            </div>
                            <div id="total_days_preview"></div>
                        </div>

                        <!-- Paid/Unpaid -->
                        <div class="col-md-6 fv-row mb-7">
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_paid" id="is_paid" value="1" checked>
                                <label class="form-check-label fw-semibold" for="is_paid">
                                    {{ __('payments.paid_leave') }}
                                </label>
                            </div>
                            <div id="is_paid"></div>
                        </div>

                        <!-- Contact Info -->
                        <div class="col-md-6 fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">{{ __('payments.alternate_contact') }}</label>
                            <input type="text" name="alternate_contact" class="form-control form-control-solid" placeholder="Phone number during leave">
                            <div id="alternate_contact"></div>
                        </div>

                        <div class="col-md-6 fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">{{ __('payments.emergency_contact') }}</label>
                            <input type="text" name="emergency_contact" class="form-control form-control-solid" placeholder="Emergency contact">
                        </div>

                        <!-- Reason -->
                        <div class="col-md-12 fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('payments.reason') }}</label>
                            <textarea name="reason" class="form-control form-control-solid" rows="3" required></textarea>
                        </div>

                        <!-- Handover Notes -->
                        <div class="col-md-12 fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">{{ __('payments.handover_notes') }}</label>
                            <textarea name="handover_notes" class="form-control form-control-solid" rows="2" placeholder="Work handover instructions"></textarea>
                        </div>

                        <!-- Handover To -->
                        <div class="col-md-12 fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">{{ __('payments.handover_to') }}</label>
                            <select name="handover_to[]" class="form-select form-select-solid" data-control="select2" data-placeholder="Select employees" multiple>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Attachments -->
                        <div class="col-md-12 fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">{{ __('payments.attachments') }}</label>
                            <input type="file" name="attachments[]" class="form-control form-control-solid" multiple accept=".pdf,.jpg,.jpeg,.png">
                            <div class="text-muted fs-7">{{ __('payments.attachments_help') }}</div>
                        </div>

                        <!-- Notes -->
                        <div class="col-md-12 fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">{{ __('payments.notes') }}</label>
                            <textarea name="notes" class="form-control form-control-solid" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button type="button" class="btn btn-primary">
                            <span class="indicator-label">{{ __('payments.submit_request') }}</span>
                            <span class="indicator-progress" style="display: none;">
                                {{ __('auth.please_wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
