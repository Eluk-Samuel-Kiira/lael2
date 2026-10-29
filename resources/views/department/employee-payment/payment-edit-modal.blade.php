<div class="modal fade" id="paymentEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('payments.edit_payment') }} — <span id="paymentEditHeaderName">—</span></h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7" style="max-height: 70vh;">
                <form id="paymentEditForm" class="form">
                    @csrf
                    <input type="hidden" id="paymentEditId">
                    <input type="hidden" name="employee_id" id="paymentEditEmployeeId">
                    <input type="hidden" name="_recalc" value="1">

                    {{-- ── Basic fields ─────────────────────────────── --}}
                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('payments.payment_date') }}</label>
                            <input type="date" name="payment_date" class="form-control form-control-solid">
                            <div id="payment_date"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('payments.payment_type') }}</label>
                            <select name="payment_type" class="form-select form-select-solid">
                                <option value="salary">{{ __('payments.salary') }}</option>
                                <option value="allowance">{{ __('payments.allowance') }}</option>
                                <option value="bonus">{{ __('payments.bonus') }}</option>
                                <option value="overtime">{{ __('payments.overtime') }}</option>
                                <option value="other">{{ __('payments.other') }}</option>
                            </select>
                            <div id="payment_type"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('payments.payment_method') }}</label>
                            <select name="payment_method_id" class="form-select form-select-solid">
                                <option value="">— {{ __('payments.select_payment_method') }} —</option>
                            </select>
                            <div id="payment_method_id"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('payments.gross_amount') }}</label>
                            <input type="number" step="0.01" min="0.01" name="gross_amount" class="form-control form-control-solid">
                            <div id="gross_amount"></div>
                        </div>
                        <div class="col-md-6" id="paymentHoursField">
                            <label class="form-label">{{ __('payments.hours_worked') }}</label>
                            <input type="number" step="0.01" min="0" name="hours_worked" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-6" id="paymentRateField">
                            <label class="form-label">{{ __('payments.hourly_rate') }}</label>
                            <input type="number" step="0.01" min="0" name="hourly_rate" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('payments.status') }}</label>
                            <select name="status" class="form-select form-select-solid">
                                <option value="pending">{{ __('payments.pending') }}</option>
                                <option value="failed">{{ __('payments.failed') }}</option>
                                <option value="cancelled">{{ __('payments.cancelled') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.reference_number') }}</label>
                            <input type="text" name="reference_number" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.pay_period_start') }}</label>
                            <input type="date" name="pay_period_start" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.pay_period_end') }}</label>
                            <input type="date" name="pay_period_end" class="form-control form-control-solid">
                        </div>
                        <div class="col-12">
                            <label class="form-label required">{{ __('payments.description') }}</label>
                            <textarea name="description" class="form-control form-control-solid" rows="2"></textarea>
                            <div id="description"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('payments.notes') }}</label>
                            <textarea name="notes" class="form-control form-control-solid" rows="2"></textarea>
                        </div>
                    </div>

                    {{-- ── TAXES ─────────────────────────────────────── --}}
                    <div class="card card-flush bg-light mb-4">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calculator text-primary me-2"></i>
                                {{ __('payments.applicable_taxes') }}
                            </h3>
                            <div class="card-toolbar">
                                <button type="button" class="btn btn-sm btn-primary" id="btnEditPreviewTaxes">
                                    <i class="fas fa-chart-pie me-2"></i>
                                    {{ __('payments.preview_calculation') }}
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="editTaxesList" class="row mb-3">
                                <div class="col-12 text-center text-muted py-4">
                                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                    <span>Loading taxes…</span>
                                </div>
                            </div>

                            <div id="editTaxPreview" class="p-4 bg-white rounded d-none">
                                <h5 class="mb-3">
                                    <i class="fas fa-file-invoice text-primary me-2"></i>
                                    {{ __('payments.calculation_summary') }}
                                </h5>
                                <div class="row g-4 mb-4">
                                    <div class="col-md-4"><div class="card card-dashed"><div class="card-body p-4">
                                        <span class="text-muted fw-bold d-block">{{ __('payments.gross_amount') }}</span>
                                        <span class="text-dark fw-bolder fs-2" id="editPreviewGross">0.00</span>
                                    </div></div></div>
                                    <div class="col-md-4"><div class="card card-dashed"><div class="card-body p-4">
                                        <span class="text-muted fw-bold d-block">{{ __('payments.total_tax') }}</span>
                                        <span class="text-danger fw-bolder fs-2" id="editPreviewTax">0.00</span>
                                    </div></div></div>
                                    <div class="col-md-4"><div class="card card-dashed"><div class="card-body p-4">
                                        <span class="text-muted fw-bold d-block">{{ __('payments.net_amount') }}</span>
                                        <span class="text-success fw-bolder fs-2" id="editPreviewNet">0.00</span>
                                    </div></div></div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-row-bordered align-middle">
                                        <thead>
                                            <tr class="fw-bold text-muted">
                                                <th>{{ __('payments.tax_name') }}</th>
                                                <th>{{ __('payments.rate') }}</th>
                                                <th class="text-end">{{ __('payments.amount') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="editTaxBreakdownBody"></tbody>
                                    </table>
                                </div>
                            </div>

                            <input type="hidden" name="total_tax_amount" id="editTotalTaxAmount" value="0">
                        </div>
                    </div>

                    {{-- ── ADVANCES ──────────────────────────────────── --}}
                    <div class="card card-flush bg-light-warning mb-4">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-hand-holding-usd text-warning me-2"></i>
                                {{ __('payments.advance_deductions') }}
                            </h3>
                            <div class="card-toolbar">
                                <button type="button" class="btn btn-sm btn-warning" id="btnEditLoadAdvances">
                                    <i class="fas fa-sync me-2"></i>
                                    {{ __('payments.load_advances') }}
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="editAdvanceDeductionsContainer" class="mb-3">
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-arrow-up fs-2 mb-3 d-block"></i>
                                    <span>{{ __('payments.select_employee_to_view_advances') }}</span>
                                </div>
                            </div>

                            <div id="editAdvancePreview" class="mt-4 p-4 bg-white rounded d-none">
                                <h5 class="mb-3">{{ __('payments.advance_deduction_summary') }}</h5>
                                <div class="row g-3">
                                    <div class="col-md-4"><div class="card card-dashed"><div class="card-body p-3">
                                        <span class="text-muted fw-bold d-block">{{ __('payments.selected_advances') }}</span>
                                        <span class="text-warning fw-bolder fs-3" data-preview-advance-count>0</span>
                                    </div></div></div>
                                    <div class="col-md-4"><div class="card card-dashed"><div class="card-body p-3">
                                        <span class="text-muted fw-bold d-block">{{ __('payments.total_deduction') }}</span>
                                        <span class="text-danger fw-bolder fs-3" data-preview-advance-total>0.00</span>
                                    </div></div></div>
                                    <div class="col-md-4"><div class="card card-dashed"><div class="card-body p-3">
                                        <span class="text-muted fw-bold d-block">{{ __('payments.amount_after_deduction') }}</span>
                                        <span class="text-success fw-bolder fs-3" data-preview-advance-after>0.00</span>
                                    </div></div></div>
                                </div>
                            </div>

                            <input type="hidden" name="advance_deductions" value="[]">
                            <input type="hidden" name="total_advance_deduction" value="0">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button type="button" id="paymentEditSubmit" class="btn btn-primary" style="min-width: 130px;">
                            <span class="indicator-label"><i class="bi bi-check2 me-1"></i>{{ __('auth._update') }}</span>
                            <span class="indicator-progress" style="display: none;">{{ __('auth.please_wait') }}<span class="spinner-border spinner-border-sm ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>