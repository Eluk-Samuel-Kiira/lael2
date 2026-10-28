<div class="modal fade" id="uomEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    {{ __('auth._edit') }} {{ __('auth._uom') }} —
                    <span id="uomEditHeaderName">—</span>
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <form id="uomEditForm" class="form">
                    @csrf
                    <input type="hidden" id="uomEditId">

                    <div class="row g-5 mb-4">
                        <div class="col-md-8">
                            <label class="form-label required">{{ __('auth._uom') }}</label>
                            <input type="text" name="name" class="form-control form-control-solid">
                            <div id="name"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">{{ __('auth._symbol') }}</label>
                            <input type="text" name="symbol" class="form-control form-control-solid">
                            <div id="symbol"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Discard</button>
                        <button type="button" id="uomEditSubmit" class="btn btn-primary" style="min-width: 130px;">
                            <span class="indicator-label"><i class="bi bi-check2 me-1"></i>Update</span>
                            <span class="indicator-progress" style="display: none;">
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