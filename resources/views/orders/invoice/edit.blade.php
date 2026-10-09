{{-- ── EDIT ISSUE DATE MODAL ─────────────────────────────── --}}
<div class="modal fade" id="editIssueDateModal{{ $invoice->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white fw-bold mb-0">
                    <i class="bi bi-calendar-event me-2"></i>
                    {{ __('payments.edit_issue_date') }} — {{ $invoice->invoice_number }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="editIssueDateForm{{ $invoice->id }}">
                @csrf
                <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">

                <div class="modal-body">

                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="bi bi-info-circle fs-4 me-3"></i>
                        <div class="fs-7">
                            {{ __('payments.issue_date_affects_reports') }}
                        </div>
                    </div>

                    {{-- Current dates --}}
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="bg-light rounded-3 p-3 text-center">
                                <div class="fs-8 text-muted fw-semibold text-uppercase mb-1">
                                    {{ __('payments.current_issue_date') }}
                                </div>
                                <div class="fw-bold text-gray-800">
                                    {{ $invoice->issue_date ? $invoice->issue_date->format('d M Y') : '—' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded-3 p-3 text-center">
                                <div class="fs-8 text-muted fw-semibold text-uppercase mb-1">
                                    {{ __('payments.current_due_date') }}
                                </div>
                                <div class="fw-bold text-gray-800">
                                    {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- New issue date --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold required">
                            {{ __('payments.new_issue_date') }}
                        </label>
                        <input type="date"
                               name="issue_date"
                               id="issueDateInput{{ $invoice->id }}"
                               class="form-control"
                               value="{{ $invoice->issue_date ? $invoice->issue_date->format('Y-m-d') : now()->format('Y-m-d') }}"
                               required>
                    </div>

                    {{-- New due date (optional override) --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            {{ __('payments.new_due_date') }}
                            <span class="text-muted fw-normal">({{ __('payments.optional') }})</span>
                        </label>
                        <input type="date"
                               name="due_date"
                               id="dueDateInput{{ $invoice->id }}"
                               class="form-control"
                               value="{{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '' }}">
                        <div class="form-text">
                            <i class="bi bi-lightbulb me-1"></i>
                            {{ __('payments.leave_blank_to_preserve_offset', [
                                'days' => $invoice->issue_date && $invoice->due_date
                                    ? $invoice->issue_date->diffInDays($invoice->due_date)
                                    : 14,
                            ]) }}
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        {{ __('payments.cancel') }}
                    </button>
                    <button type="button"
                            id="editIssueDateButton{{ $invoice->id }}"
                            class="btn btn-primary"
                            onclick="submitIssueDate({{ $invoice->id }})">
                        <span class="indicator-label">
                            <i class="bi bi-check-circle me-1"></i>{{ __('payments.save') }}
                        </span>
                        <span class="indicator-progress">
                            {{ __('payments.processing') }}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>