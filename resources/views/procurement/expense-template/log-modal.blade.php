{{-- resources/views/procurement/expense-template/log-modal.blade.php --}}
<div class="modal fade" id="logTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold" id="logTemplateTitle">
                    {{ __('auth.log_expense_again') }}
                </h2>
                <button type="button" class="btn btn-icon btn-sm" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>

            <form id="logTemplateForm">
                @csrf
                <input type="hidden" id="logTemplateId" name="template_id">

                <div class="modal-body">

                    {{-- Context — read only --}}
                    <div class="rounded bg-light p-4 mb-5">
                        <div class="row g-2 fs-7">
                            <div class="col-6">
                                <span class="text-muted">{{ __('pagination.category') }}:</span>
                                <span class="fw-bold" id="logCategoryName">—</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted">{{ __('auth.supplier') }}:</span>
                                <span class="fw-bold" id="logSupplierName">—</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted">{{ __('auth.default_location') }}:</span>
                                <span class="fw-bold" id="logLocationName">—</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted">{{ __('auth.default_department') }}:</span>
                                <span class="fw-bold" id="logDepartmentName">—</span>
                            </div>
                        </div>
                    </div>

                    {{-- Amount --}}
                    <div class="mb-4">
                        <label class="required fs-6 fw-semibold mb-2">
                            {{ __('pagination.gross_amount') }}
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">{{ currency_symbol() }}</span>
                            <input type="number" step="0.01" min="0.01"
                                   name="gross_amount" id="logGrossAmount"
                                   class="form-control form-control-solid" required>
                        </div>
                        <small class="text-muted fs-8" id="logAmountHint"></small>
                        <div class="invalid-feedback d-block" id="gross_amount_error"></div>
                    </div>

                    {{-- Date --}}
                    <div class="mb-4">
                        <label class="required fs-6 fw-semibold mb-2">
                            {{ __('pagination.date') }}
                        </label>
                        <input type="date" name="date" id="logDate"
                               class="form-control form-control-solid"
                               value="{{ date('Y-m-d') }}" required>
                        <div class="invalid-feedback d-block" id="date_error"></div>
                    </div>

                    {{-- Payment method --}}
                    <div class="mb-4">
                        <label class="required fs-6 fw-semibold mb-2">
                            {{ __('pagination.payment_method') }}
                        </label>
                        <select name="payment_method_id" id="logPaymentMethod"
                            class="form-select form-select-solid"
                            data-control="select2"
                            data-dropdown-parent="body">
                            <option value="">{{ __('payments.select_payment_method') }}</option>
                        </select>
                        <div class="invalid-feedback d-block" id="payment_method_id_error"></div>
                    </div>

                    {{-- Location (overridable) --}}
                    <div class="mb-4">
                        <label class="fs-6 fw-semibold mb-2">
                            {{ __('auth.location') }}
                        </label>
                        <select name="location_id" id="logLocationId"
                            class="form-select form-select-solid"
                            data-control="select2"
                            data-allow-clear="true"
                            data-dropdown-parent="body">
                            <option value="">{{ __('auth.use_default') }}</option>
                        </select>
                    </div>

                    {{-- Department (overridable) --}}
                    <div class="mb-4">
                        <label class="fs-6 fw-semibold mb-2">
                            {{ __('auth.department') }}
                        </label>
                        <select name="department_id" id="logDepartmentId"
                            class="form-select form-select-solid"
                            data-control="select2"
                            data-allow-clear="true"
                            data-dropdown-parent="body">
                            <option value="">{{ __('auth.use_default') }}</option>
                        </select>
                    </div>

                    {{-- Status --}}
                    <div class="mb-4">
                        <label class="required fs-6 fw-semibold mb-2">
                            {{ __('pagination.payment_status') }}
                        </label>
                        <select name="payment_status" id="logPaymentStatus"
                                class="form-select form-select-solid" required>
                            <option value="pending" selected>{{ __('pagination.pending') }}</option>
                            <option value="paid">{{ __('pagination.paid') }}</option>
                        </select>
                    </div>

                    {{-- Notes --}}
                    <div class="mb-4">
                        <label class="fs-6 fw-semibold mb-2">
                            {{ __('pagination.notes') }}
                        </label>
                        <input type="text" name="notes" id="logNotes"
                               class="form-control form-control-solid"
                               placeholder="{{ __('auth.optional') }}">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        {{ __('auth._cancel') }}
                    </button>
                    <button type="submit" id="logTemplateSubmit" class="btn btn-primary">
                        <span class="indicator-label">
                            <i class="ki-duotone ki-check fs-3 me-1"></i>
                            {{ __('auth._save') }}
                        </span>
                        <span class="indicator-progress" style="display:none;">
                            {{ __('auth.please_wait') }}
                            <span class="spinner-border spinner-border-sm ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Fix Select2 dropdown z-index and click handling inside Bootstrap modals */
.select2-container--open {
    z-index: 9999 !important;
}
body.modal-open .select2-container--open .select2-dropdown {
    z-index: 10000 !important;
}
</style>