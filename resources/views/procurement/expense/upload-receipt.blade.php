<!-- resources/views/procurement/expense/edit-expense.blade.php -->
<div class="modal fade" id="uploadReceiptModal{{$expense->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('auth.edit_expense') }} - {{ $expense->expense_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('auth.close') }}"></button>
            </div>
            <!-- Update form action to point to the receipt update route -->
            <form id="editExpenseForm{{$expense->id}}" method="POST" 
                  action="{{ route('expenses.update-receipt', $expense->id) }}"
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-4">
                        
                        <!-- Receipt Information -->
                        <div class="col-12">
                            <label class="form-label">{{ __('pagination.receipt') }} *</label>
                            @if($expense->receipt_url)
                                <div class="mb-2">
                                    <a href="{{ Storage::url($expense->receipt_url) }}" target="_blank" class="btn btn-sm btn-light">
                                        <i class="bi bi-file-earmark-text me-1"></i> {{ __('pagination.view_current_receipt') }}
                                    </a>
                                </div>
                            @endif
                            <input type="file" name="receipt" class="form-control" accept="image/*,.pdf,.doc,.docx" required>
                            <small class="text-muted">{{ __('pagination.allowed_formats') }}</small>
                            @error('receipt')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description Field (for the receipt) -->
                        <div class="col-12">
                            <label class="form-label">{{ __('pagination.description') }} <span class="text-muted">({{ __('auth.optional') }})</span></label>
                            <textarea name="description" class="form-control" rows="3" 
                                      placeholder="{{ __('pagination.receipt_description_placeholder') }}">{{ old('description', $expense->description) }}</textarea>
                            @error('description')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Approval Information -->
                        @if($expense->approved_at)
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
                                        <div>
                                            <strong>{{ __('pagination.note') }}:</strong> {{ __('pagination.receipt_reupload_warning') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        {{ __('auth._update') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>