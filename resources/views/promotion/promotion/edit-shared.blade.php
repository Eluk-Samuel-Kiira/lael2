<div class="modal fade" id="promoEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-750px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    {{ __('auth._edit') }} {{ __('pagination._promotion') }} —
                    <span id="promoEditHeaderName">—</span>
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <form id="promoEditForm" class="form">
                    @csrf
                    <input type="hidden" id="promoEditId">

                    <div class="row g-5 mb-4">
                        <div class="col-md-12">
                            <label class="form-label required">Promotion Name</label>
                            <input type="text" name="name" class="form-control form-control-solid">
                            <div id="name"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Discount Type</label>
                            <select name="discount_type" class="form-select form-select-solid">
                                <option value="">— Select —</option>
                                <option value="percentage">Percentage</option>
                                <option value="fixed_amount">Fixed Amount</option>
                                <option value="buy_x_get_y">Buy X Get Y</option>
                            </select>
                            <div id="discount_type"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Discount Value</label>
                            <input type="text" name="discount_value" class="form-control form-control-solid">
                            <div id="discount_value"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Start Date</label>
                            <input type="date" name="start_date" class="form-control form-control-solid">
                            <div id="start_date"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">End Date</label>
                            <input type="date" name="end_date" class="form-control form-control-solid">
                            <div id="end_date"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Discard</button>
                        <button type="button" id="promoEditSubmit" class="btn btn-primary" style="min-width: 130px;">
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