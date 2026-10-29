<div class="modal fade" id="taxEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    {{ __('auth._edit') }} {{ __('pagination._tax') }} —
                    <span id="taxEditHeaderName">—</span>
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <form id="taxEditForm" class="form">
                    @csrf
                    <input type="hidden" id="taxEditId">

                    <div class="row g-5 mb-4">
                        <div class="col-md-8">
                            <label class="form-label required">Tax Name</label>
                            <input type="text" name="name" class="form-control form-control-solid">
                            <div id="name"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Code</label>
                            <input type="text" name="code" class="form-control form-control-solid" maxlength="6" minlength="5">
                            <div id="code"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Type</label>
                            <select name="type" class="form-select form-select-solid">
                                <option value="">— Select —</option>
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed</option>
                            </select>
                            <div id="type"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Rate</label>
                            <input type="number" name="rate" class="form-control form-control-solid" step="0.01" min="0">
                            <div id="rate"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-12">
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_withholding_tax" id="taxEdit_is_withholding">
                                <label class="form-check-label fw-semibold" for="taxEdit_is_withholding">
                                    <i class="bi bi-receipt me-1"></i>Withholding Tax
                                </label>
                            </div>
                            <div class="text-muted fs-7 mt-1">Withholding taxes are deducted from the payable amount.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Discard</button>
                        <button type="button" id="taxEditSubmit" class="btn btn-primary" style="min-width: 130px;">
                            <span class="indicator-label"><i class="bi bi-check2 me-1"></i>Update</span>
                            <span class="indicator-progress" style="display: none;">
                                Please wait… <span class="spinner-border spinner-border-sm ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>