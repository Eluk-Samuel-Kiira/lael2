<!-- Cash Payment Modal with Account Selection -->
<div class="modal fade" id="cashModal" tabindex="-1" aria-hidden="true"  data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered mw-750px">
        <div class="modal-content" style="font-size: 1.4rem;">
            <div class="modal-header">
                <h2 class="modal-title fw-bold text-gray-800" style="font-size: 2rem;">
                    {{__('pagination.cash_payment')}}
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        style="transform: scale(1.5);"></button>
            </div>
            <div class="modal-body">
                <div class="mb-8">
                    <h4 class="fw-bold text-gray-600 mb-4" style="font-size: 1.6rem;">
                        {{__('pagination.order_summary')}}
                    </h4>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span style="font-size: 1.5rem;">{{__('pagination.total_amount')}}:</span>
                        <span id="cashTotalAmount" 
                              style="font-size: 2.2rem; font-weight: 700; color: #000;">
                              $0.00
                        </span>
                    </div>
                </div>
                
                <!-- Payment Account Selection Dropdown -->
                <div class="mb-6">
                    <label class="form-label fw-bold" style="font-size: 1.5rem;">
                        {{ __('pagination.select_account') }}
                    </label>
                    <select class="form-select form-select-lg" id="cashAccountSelect" style="font-size: 1.4rem; height: 60px;">
                        <option value="">{{ __('pagination.select_account') }}</option>
                        <!-- Accounts will be populated here -->
                    </select>
                </div>
                
                <!-- Selected Account Information (Hidden by default) -->
                <div class="alert alert-light-primary mb-6 d-none" id="selectedAccountInfoCard">
                    <div class="d-flex align-items-start">
                        <i class="ki-duotone ki-wallet fs-2 text-primary me-3 mt-1"></i>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-2">{{ __('pagination.selected_account') }}</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <small class="text-muted">{{ __('payments.account_name') }}:</small>
                                        <div class="fw-bold" id="selectedAccountName">-</div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">{{ __('payments.account_number') }}:</small>
                                        <div class="fw-bold" id="selectedAccountNumber">-</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <small class="text-muted">{{ __('payments.account_holder') }}:</small>
                                        <div class="fw-bold" id="selectedAccountHolder">-</div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">{{ __('payments.provider') }}:</small>
                                        <div class="fw-bold" id="selectedAccountProvider">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction ID Field (Optional) -->
                <div class="mb-6" id="transactionIdSection">
                    <label class="form-label fw-bold" style="font-size: 1.5rem;">
                        {{ __('pagination.transaction_id') }} <span class="text-muted">({{ __('pagination.optional') }})</span>
                    </label>
                    <input type="text" 
                           class="form-control form-control-lg" 
                           id="transactionIdInput"
                           placeholder="{{ __('pagination.enter_transaction_id') }}"
                           style="font-size: 1.4rem; height: 60px;"
                           maxlength="100">
                    <div class="form-text text-muted" style="font-size: 1.2rem;">
                        {{ __('pagination.transaction_id_help') }}
                    </div>
                </div>
                
                <!-- Cash Details Section -->
                <div class="mb-8">
                    <div class="d-flex justify-content-between mb-3">
                        <span style="font-size: 1.5rem;">{{__('pagination.amount_tendered')}}:</span>
                        <span id="cashAmountTendered" 
                              style="font-size: 2.2rem; font-weight: 700; color: #0d6efd;">
                              $0.00
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-4">
                        <span style="font-size: 1.5rem;">{{__('pagination.change_due')}}:</span>
                        <span id="cashChangeDue" 
                              style="font-size: 2.5rem; font-weight: 800; color: #198754;">
                              $0.00
                        </span>
                    </div>
                    
                    <label class="form-label fw-bold" style="font-size: 1.5rem;">
                        {{ __('pagination.enter_amount_tendered') }}
                    </label>
                    <input type="number" 
                        class="form-control text-center" 
                        id="cashAmountInput"
                        placeholder="0.00" 
                        step="0.01" 
                        min="0" 
                        oninput="calculateChange()"
                        style="font-size: 2.5rem; height: 90px; line-height: 90px;">
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" 
                        class="btn btn-light btn-lg px-5 py-3" 
                        data-bs-dismiss="modal" 
                        style="font-size: 1.4rem;">
                    {{__('pagination.cancel')}}
                </button>
                <button 
                    id="cashCheckout" 
                    type="button" 
                    class="btn btn-primary btn-lg px-5 py-3"
                    style="font-size: 1.4rem;"
                    onclick="completeCashPayment()">
                    
                    <span class="indicator-label">{{__('pagination.complete_payment')}}</span>
                    <span class="indicator-progress">{{__('pagination.complete_checkout')}}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receiptModalLabel">{{ __('pagination.payment_receipt') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Receipt content will be inserted here by JavaScript --}}
                <div id="receiptContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('pagination.close') }}</button>
                <button type="button" class="btn btn-primary" onclick="printReceipt()">
                    <i class="fas fa-print me-1"></i> {{ __('pagination.print_receipt') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Hidden receipt container for printing --}}
<div id="printReceiptContainer" style="display: none;"></div>