<div class="modal fade" id="cgEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    {{ __('passwords.customer_group_edit') }} —
                    <span id="cgEditHeaderName">—</span>
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <form id="cgEditForm" class="form">
                    @csrf
                    <input type="hidden" id="cgEditId">

                    <div class="row g-5 mb-4">
                        <div class="col-md-12">
                            <label class="form-label required">Group Name</label>
                            <input type="text" name="name" class="form-control form-control-solid" maxlength="100">
                            <div id="name"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Discount Percentage</label>
                            <div class="input-group input-group-solid">
                                <input type="number" name="discount_percentage" step="0.01" min="0" max="100" class="form-control form-control-solid">
                                <span class="input-group-text">%</span>
                            </div>
                            <div id="discount_percentage"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Group</label>
                            <div class="form-check form-switch form-check-custom form-check-solid mt-3">
                                <input class="form-check-input" type="checkbox" name="is_default" id="cgEdit_is_default">
                                <label class="form-check-label" for="cgEdit_is_default">Mark as default</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Discard</button>
                        <button type="button" id="cgEditSubmit" class="btn btn-primary" style="min-width: 130px;">
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