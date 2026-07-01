{{-- resources/views/tenant/leave/modals/reject.blade.php --}}
<div class="modal fade" id="kt_modal_reject_leave" tabindex="-1" aria-hidden="true" dir="ltr">
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('payments.reject_leave_request') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="text-align: left;">
                <input type="hidden" id="reject_leave_id">
                
                <div class="mb-3">
                    <label class="form-label required">{{ __('payments.rejection_reason') }}</label>
                    <textarea id="rejection_reason" class="form-control" rows="3" style="direction: ltr;"></textarea>
                    <div id="rejection_reason_error" class="text-danger mt-1 d-none"></div>
                </div>
            </div>
            <div class="modal-footer" style="justify-content: flex-end;">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._cancel') }}</button>
                <button type="button" class="btn btn-danger" onclick="submitReject()">
                    <span class="indicator-label">{{ __('payments.reject') }}</span>
                    <span class="indicator-progress d-none">
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        {{ __('auth.please_wait') }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showRejectModal(id) {
    document.getElementById('reject_leave_id').value = id;
    document.getElementById('rejection_reason').value = '';
    document.getElementById('rejection_reason_error').classList.add('d-none');
    
    new bootstrap.Modal(document.getElementById('kt_modal_reject_leave')).show();
}

function submitReject() {
    const id = document.getElementById('reject_leave_id').value;
    const reason = document.getElementById('rejection_reason').value;
    const errorEl = document.getElementById('rejection_reason_error');
    const btn = event.currentTarget;
    
    if (!reason.trim()) {
        errorEl.textContent = '{{ __("payments.rejection_reason_required") }}';
        errorEl.classList.remove('d-none');
        return;
    }
    
    btn.disabled = true;
    btn.querySelector('.indicator-label').classList.add('d-none');
    btn.querySelector('.indicator-progress').classList.remove('d-none');
    
    fetch('/leave/' + id + '/reject', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ rejection_reason: reason })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_reject_leave')).hide();
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(() => Swal.fire('Error', 'Something went wrong', 'error'))
    .finally(() => {
        btn.disabled = false;
        btn.querySelector('.indicator-label').classList.remove('d-none');
        btn.querySelector('.indicator-progress').classList.add('d-none');
    });
}
</script>