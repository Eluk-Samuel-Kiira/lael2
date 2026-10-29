<div class="modal fade" id="leaveEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('payments.edit_leave') }} — <span id="leaveEditHeaderName">—</span></h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="leaveEditForm" class="form">
                    @csrf
                    <input type="hidden" id="leaveEditId">

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('payments.leave_type') }}</label>
                            <select name="leave_type" class="form-select form-select-solid">
                                <option value="annual">{{ __('payments.annual') }}</option>
                                <option value="sick">{{ __('payments.sick') }}</option>
                                <option value="maternity">{{ __('payments.maternity') }}</option>
                                <option value="paternity">{{ __('payments.paternity') }}</option>
                                <option value="bereavement">{{ __('payments.bereavement') }}</option>
                                <option value="study">{{ __('payments.study') }}</option>
                                <option value="unpaid">{{ __('payments.unpaid') }}</option>
                                <option value="other">{{ __('payments.other') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="leaveCustomTypeField" style="display:none;">
                            <label class="form-label required">{{ __('payments.custom_type') }}</label>
                            <input type="text" name="custom_type" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('payments.start_date') }}</label>
                            <input type="date" name="start_date" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('payments.end_date') }}</label>
                            <input type="date" name="end_date" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_paid" value="1" id="leaveEditIsPaid">
                                <label class="form-check-label" for="leaveEditIsPaid">{{ __('payments.paid_leave') }}</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.alternate_contact') }}</label>
                            <input type="text" name="alternate_contact" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.emergency_contact') }}</label>
                            <input type="text" name="emergency_contact" class="form-control form-control-solid">
                        </div>
                        <div class="col-12">
                            <label class="form-label required">{{ __('payments.reason') }}</label>
                            <textarea name="reason" class="form-control form-control-solid" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('payments.handover_notes') }}</label>
                            <textarea name="handover_notes" class="form-control form-control-solid" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('payments.notes') }}</label>
                            <textarea name="notes" class="form-control form-control-solid" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button type="button" id="leaveEditSubmit" class="btn btn-primary" style="min-width: 130px;">
                            <span class="indicator-label"><i class="bi bi-check2 me-1"></i>{{ __('auth._update') }}</span>
                            <span class="indicator-progress" style="display: none;">{{ __('auth.please_wait') }}<span class="spinner-border spinner-border-sm ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>