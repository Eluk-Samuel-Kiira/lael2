<div class="modal fade" id="advanceEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('payments.edit_advance') }} — <span id="advanceEditHeaderName">—</span></h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7" style="max-height: 70vh;">
                <form id="advanceEditForm" class="form">
                    @csrf
                    <input type="hidden" id="advanceEditId">

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('payments.advance_amount') }}</label>
                            <div class="input-group">
                                <span class="input-group-text advance-currency">—</span>
                                <input type="number" step="0.01" min="0.01" name="advance_amount" class="form-control form-control-solid">
                            </div>
                            <div id="advance_amount"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('payments.advance_date') }}</label>
                            <input type="date" name="advance_date" class="form-control form-control-solid">
                            <div id="advance_date"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('payments.deduction_frequency') }}</label>
                            <select name="deduction_frequency" class="form-select form-select-solid">
                                <option value="one_time">{{ __('payments.one_time') }}</option>
                                <option value="weekly">{{ __('payments.weekly') }}</option>
                                <option value="monthly">{{ __('payments.monthly') }}</option>
                                <option value="yearly">{{ __('payments.yearly') }}</option>
                            </select>
                            <div id="deduction_frequency"></div>
                        </div>
                        <div class="col-md-6" id="advanceInstallmentsField">
                            <label class="form-label">{{ __('payments.installments') }}</label>
                            <input type="number" min="2" max="12" name="installments" class="form-control form-control-solid">
                            <div id="installments"></div>
                        </div>
                        <div class="col-md-6" id="advanceDeductionDayField">
                            <label class="form-label">{{ __('payments.deduction_day') }}</label>
                            <select name="deduction_day" class="form-select form-select-solid">
                                <option value="">—</option>
                                @for($i = 1; $i <= 31; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                            <div id="deduction_day"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.deduction_start_date') }}</label>
                            <input type="date" name="deduction_start_date" class="form-control form-control-solid">
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-12">
                            <label class="form-label">{{ __('payments.applicable_to_salary_types') }}</label>
                            <div class="row">
                                @foreach (['salary','allowance','bonus','overtime'] as $type)
                                    <div class="col-md-3">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox"
                                                   name="applicable_salary_types[]" value="{{ $type }}"
                                                   id="edit_apply_{{ $type }}">
                                            <label class="form-check-label" for="edit_apply_{{ $type }}">{{ __('payments.' . $type) }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.purpose') }}</label>
                            <input type="text" name="purpose" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('payments.reason') }}</label>
                            <textarea name="reason" class="form-control form-control-solid" rows="2"></textarea>
                            <div id="reason"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('payments.notes') }}</label>
                            <textarea name="notes" class="form-control form-control-solid" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button type="button" id="advanceEditSubmit" class="btn btn-primary" style="min-width: 130px;">
                            <span class="indicator-label"><i class="bi bi-check2 me-1"></i>{{ __('auth._update') }}</span>
                            <span class="indicator-progress" style="display: none;">{{ __('auth.please_wait') }}<span class="spinner-border spinner-border-sm ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>