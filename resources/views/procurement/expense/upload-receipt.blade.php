{{-- resources/views/procurement/expense/upload-receipt.blade.php --}}
<div class="modal fade" id="expenseUploadReceiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    {{ __('auth._upload') }} — <span id="uploadExpenseNumber">—</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="expenseUploadReceiptForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="expense_id" id="uploadExpenseId">

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">{{ __('pagination.receipt') }}</label>
                        <input type="file" name="receipt" class="form-control"
                               accept="image/*,.pdf,.doc,.docx" required>
                        <small class="text-muted">{{ __('pagination.allowed_formats') }}</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('pagination.description') }}</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>

                    <div id="uploadReceiptCurrent" class="d-none">
                        <label class="form-label">{{ __('pagination.view_current_receipt') }}</label>
                        <div>
                            <a href="#" id="uploadReceiptCurrentLink" target="_blank"
                               class="btn btn-sm btn-light">
                                <i class="bi bi-file-earmark-text me-1"></i>
                                {{ __('pagination.view_current_receipt') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        {{ __('auth._cancel') }}
                    </button>
                    <button type="submit" class="btn btn-primary">
                        {{ __('auth._update') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>