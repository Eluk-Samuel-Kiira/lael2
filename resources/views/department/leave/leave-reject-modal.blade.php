<div class="modal fade" id="leaveRejectModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('payments.reject_leave_request') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body">
                <label class="form-label required">{{ __('payments.rejection_reason') }}</label>
                <textarea id="leaveRejectReason" class="form-control form-control-solid" rows="4"></textarea>
                <div id="leaveRejectError" class="text-danger mt-2 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._cancel') }}</button>
                <button type="button" id="leaveRejectConfirm" class="btn btn-danger">
                    <span class="indicator-label">{{ __('payments.reject') }}</span>
                    <span class="indicator-progress" style="display: none;">{{ __('auth.please_wait') }}<span class="spinner-border spinner-border-sm ms-2"></span></span>
                </button>
            </div>
        </div>
    </div>
</div>