<div class="modal fade" id="currencyEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    {{ __('auth.edit_currency') }} —
                    <span id="currencyEditHeaderName">—</span>
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <form id="currencyEditForm" class="form">
                    @csrf
                    <input type="hidden" id="currencyEditId">

                    <div class="row g-5 mb-4">
                        <div class="col-md-4">
                            <label class="form-label required">Code</label>
                            <input type="text" name="code" class="form-control form-control-solid" maxlength="3" minlength="3" style="text-transform:uppercase;">
                            <div id="code"></div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label required">Name</label>
                            <input type="text" name="name" class="form-control form-control-solid">
                            <div id="name"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-4">
                            <label class="form-label required">Symbol</label>
                            <input type="text" name="symbol" class="form-control form-control-solid" maxlength="10">
                            <div id="symbol"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Symbol Position</label>
                            <select name="symbol_position" class="form-select form-select-solid">
                                <option value="before">Before ($100)</option>
                                <option value="after">After (100$)</option>
                            </select>
                            <div id="symbol_position"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Decimal Places</label>
                            <input type="number" name="decimal_places" class="form-control form-control-solid" min="0" max="4">
                            <div id="decimal_places"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-12">
                            <label class="form-label required">Exchange Rate <span class="text-muted fw-normal">{{ tenant_currency() }}</span></label>
                            <input type="number" step="0.00000001" min="0.00000001" name="exchange_rate" class="form-control form-control-solid">
                            <div id="exchange_rate"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Discard</button>
                        <button type="button" id="currencyEditSubmit" class="btn btn-primary" style="min-width: 130px;">
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