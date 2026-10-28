{{-- procurement/expense-category/edit-category.blade.php --}}
<div class="modal fade" id="expCatEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    {{ __('auth._edit') }} {{ __('passwords.expense_category') }} —
                    <span id="expCatEditHeaderName">—</span>
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <form id="expCatEditForm" class="form">
                    @csrf
                    <input type="hidden" id="expCatEditId">

                    <div class="row g-5 mb-4">
                        <div class="col-md-8">
                            <label class="form-label required">Name</label>
                            <input type="text" name="name" class="form-control form-control-solid">
                            <div id="name"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Code</label>
                            <input type="text" name="code" class="form-control form-control-solid">
                            <div id="code"></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control form-control-solid" rows="3"></textarea>
                        <div id="description"></div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">GL Account ID</label>
                            <input type="number" name="gl_account_id" class="form-control form-control-solid">
                            <div id="gl_account_id"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch form-check-custom form-check-solid mt-3">
                                <input class="form-check-input" type="checkbox" name="is_active" id="expCatEdit_is_active">
                                <label class="form-check-label fw-semibold" for="expCatEdit_is_active">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Monthly Budget</label>
                            <input type="number" name="budget_monthly" class="form-control form-control-solid" step="0.01" min="0">
                            <div id="budget_monthly"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Annual Budget</label>
                            <input type="number" name="budget_annual" class="form-control form-control-solid" step="0.01" min="0">
                            <div id="budget_annual"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="requires_receipt" id="expCatEdit_requires_receipt">
                                <label class="form-check-label fw-semibold" for="expCatEdit_requires_receipt">
                                    Requires Receipt
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="requires_approval" id="expCatEdit_requires_approval">
                                <label class="form-check-label fw-semibold" for="expCatEdit_requires_approval">
                                    Requires Approval
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Discard</button>
                        <button type="button" id="expCatEditSubmit" class="btn btn-primary" style="min-width: 130px;">
                            <span class="indicator-label"><i class="bi bi-check2 me-1"></i>Update</span>
                            <span class="indicator-progress">
                                Please wait…
                                <span class="spinner-border spinner-border-sm ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>