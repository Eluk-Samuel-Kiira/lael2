<div class="modal fade" id="advanceRejectModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('payments.reject_advance') }}</h2>
                <button type="button" class="btn btn-icon btn-sm" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">{{ __('payments.confirm_reject_message') }}</p>
                <div class="bg-light-danger rounded p-3 mb-4" id="advanceRejectSummary"></div>

                <label class="form-label required">{{ __('payments.rejection_reason') }}</label>
                <textarea id="advanceRejectReason" class="form-control form-control-solid" rows="4"
                          maxlength="500" placeholder="{{ __('payments.rejection_reason') }}"></textarea>
                <div id="advanceRejectError" class="text-danger mt-2 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._cancel') }}</button>
                <button type="button" id="advanceRejectConfirm" class="btn btn-danger">
                    <span class="indicator-label">{{ __('payments.reject') }}</span>
                    <span class="indicator-progress" style="display:none;">
                        {{ __('auth.please_wait') }}
                        <span class="spinner-border spinner-border-sm ms-2"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>