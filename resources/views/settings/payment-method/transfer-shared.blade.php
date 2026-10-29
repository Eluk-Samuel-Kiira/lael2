<div class="modal fade" id="pmTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    <i class="ki-duotone ki-arrows-circle fs-2 me-2 text-primary">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    {{ __('payments.transfer_funds') }} —
                    <span id="pmTransferFromName">—</span>
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <form id="pmTransferForm" class="form">
                    @csrf
                    <input type="hidden" name="from_payment_method_id" id="pmTransferFromId">

                    <div class="row g-9 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">From Account</label>
                            <div class="d-flex flex-column bg-light-primary border border-primary border-dashed rounded-3 p-3">
                                <div class="fw-bold text-gray-800" id="pmTransferFromDisplay">—</div>
                                <div class="fs-7 text-muted" id="pmTransferFromBalance">—</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label required">To Account</label>
                            <select name="to_payment_method_id" id="pmTransferToSelect" class="form-select form-select-solid">
                                <option value="">— Select —</option>
                            </select>
                            <div id="to_payment_method_id"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" step="0.01" min="0.01" name="amount" id="pmTransferAmount" class="form-control form-control-solid">
                            </div>
                            <small class="text-muted d-block mt-1" id="pmTransferHint"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control form-control-solid">
                        </div>
                    </div>

                    <div id="pmTransferPreview" class="d-none">
                        <div class="separator separator-dashed my-4"></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card bg-light-danger">
                                    <div class="card-body py-3">
                                        <div class="fw-bold mb-2"><i class="bi bi-arrow-up me-1"></i>Outgoing</div>
                                        <div class="d-flex justify-content-between mb-1 fs-7"><span>Account</span><strong id="pmPreviewFromName">—</strong></div>
                                        <div class="d-flex justify-content-between mb-1 fs-7"><span>Before</span><span id="pmPreviewFromBefore">—</span></div>
                                        <div class="d-flex justify-content-between mb-1 fs-7"><span>Amount</span><span class="text-danger fw-bold" id="pmPreviewFromAmount">—</span></div>
                                        <div class="d-flex justify-content-between fs-7"><span>After</span><strong id="pmPreviewFromAfter">—</strong></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light-success">
                                    <div class="card-body py-3">
                                        <div class="fw-bold mb-2"><i class="bi bi-arrow-down me-1"></i>Incoming</div>
                                        <div class="d-flex justify-content-between mb-1 fs-7"><span>Account</span><strong id="pmPreviewToName">—</strong></div>
                                        <div class="d-flex justify-content-between mb-1 fs-7"><span>Before</span><span id="pmPreviewToBefore">—</span></div>
                                        <div class="d-flex justify-content-between mb-1 fs-7"><span>Amount</span><span class="text-success fw-bold" id="pmPreviewToAmount">—</span></div>
                                        <div class="d-flex justify-content-between fs-7"><span>After</span><strong id="pmPreviewToAfter">—</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-light-primary border border-primary border-dashed mt-3 py-2 mb-0 fs-7">
                            <strong>Reference:</strong> <span id="pmPreviewReference">—</span>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Discard</button>
                <button type="button" class="btn btn-light-primary" id="pmTransferPreviewBtn">Preview</button>
                <button type="button" class="btn btn-primary" id="pmTransferExecuteBtn" disabled>
                    <span class="indicator-label"><i class="bi bi-check-circle me-1"></i>Confirm Transfer</span>
                    <span class="indicator-progress d-none">Please wait… <span class="spinner-border spinner-border-sm ms-2"></span></span>
                </button>
            </div>
        </div>
    </div>
</div>