<!-- Send to Supplier Modal Content -->
<div class="modal fade" id="sendToSupplierModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    <i class="bi bi-send me-2"></i>
                    {{ __('passwords.send_to_supplier') }} - {{ $order->po_number }}
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="sendToSupplierForm{{ $order->id }}">
                    @csrf
                    
                    {{-- ✅ Define variables before using them --}}
                    @php
                        $totalAmount = $order->total ?? 0;
                        $totalPaid = $order->total_paid ?? 0;
                        $balance = $totalAmount - $totalPaid;
                        $isFullyPaid = $balance <= 0;
                        $isSent = $order->status === 'sent';
                        $isReceived = $order->status === 'received' || $order->status === 'partially_received';
                    @endphp
                    
                    {{-- ✅ Hidden fields for tracking --}}
                    <input type="hidden" id="total_amount_{{ $order->id }}" value="{{ $totalAmount }}">
                    <input type="hidden" id="total_paid_{{ $order->id }}" value="{{ $totalPaid }}">
                    
                    <div class="alert alert-info d-flex align-items-center mb-5">
                        <i class="bi bi-info-circle fs-2 me-3"></i>
                        <div>
                            @if($isReceived)
                                {{ __('passwords.po_already_received') }}
                            @elseif($isSent)
                                {{ __('passwords.send_supplier_instruction_sent') }}
                            @else
                                {{ __('passwords.send_supplier_instruction') }}
                            @endif
                        </div>
                    </div>

                    {{-- ═══════════════════════════════════════════════════════════
                        🟢 ORDER SUMMARY
                        ═══════════════════════════════════════════════════════════ --}}
                    <div class="card card-flush bg-light-primary mb-6">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">{{ __('passwords.po_number') }}:</span>
                                        <span class="fw-bold">{{ $order->po_number }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <span class="text-muted">{{ __('passwords.supplier') }}:</span>
                                        <span class="fw-bold">{{ $order->supplier->name ?? '—' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <span class="text-muted">{{ __('passwords.status') }}:</span>
                                        <span class="fw-bold">
                                            <span class="badge badge-{{ $order->status_badge }}">
                                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                            </span>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">{{ __('passwords.total_amount') }}:</span>
                                        <span class="fw-bold text-primary fs-5">{{ number_format($totalAmount, 2) }} {{ currency_symbol() }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <span class="text-muted">{{ __('passwords.total_paid') }}:</span>
                                        <span class="fw-bold text-success">{{ number_format($totalPaid, 2) }} {{ currency_symbol() }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <span class="text-muted">{{ __('passwords.balance_remaining') }}:</span>
                                        <span class="fw-bold text-warning">{{ number_format($balance, 2) }} {{ currency_symbol() }}</span>
                                    </div>
                                    @if($isFullyPaid)
                                        <div class="mt-2">
                                            <span class="badge badge-success">✅ {{ __('passwords.fully_paid') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @if($isSent && $isFullyPaid)
                                <div class="mt-3 alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
                                    {{ __('passwords.po_fully_paid_and_sent') }}
                                </div>
                            @endif
                            @if($isReceived)
                                <div class="mt-3 alert alert-info">
                                    <i class="bi bi-box-seam me-2"></i>
                                    {{ __('passwords.po_already_received_message') }}
                                </div>
                            @endif
                        </div>
                    </div>
                    {{-- ════════════════ END OF ORDER SUMMARY ════════════════ --}}

                    <!-- Supplier Email -->
                    <div class="mb-4">
                        <label class="form-label required">{{ __('passwords.supplier_email') }}</label>
                        <input type="email" 
                               name="supplier_email" 
                               class="form-control" 
                               value="{{ $order->supplier->email ?? '' }}"
                               placeholder="{{ __('passwords.enter_supplier_email') }}"
                               {{ $isReceived ? 'disabled' : '' }}
                               required>
                        <div class="form-text text-muted">{{ __('passwords.supplier_email_hint') }}</div>
                    </div>

                    <!-- Payment Information -->
                    <div class="card card-flush bg-light-warning mb-4">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-credit-card me-2"></i>
                                {{ __('payments.payment_information') }}
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label required">{{ __('payments.payment_method') }}</label>
                                <select name="payment_method_id" class="form-select" {{ $isReceived ? 'disabled' : '' }} required>
                                    <option value="">{{ __('payments.select_payment_method') }}</option>
                                    @foreach($active_payment_methods as $method)
                                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Payment Amount -->
                            <div class="mb-3">
                                <label class="form-label required">{{ __('passwords.payment_amount') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ currency_symbol() }}</span>
                                    <input type="number" 
                                           name="payment_amount" 
                                           id="payment_amount_{{ $order->id }}"
                                           class="form-control" 
                                           step="0.01" 
                                           min="0.01"
                                           max="{{ $balance }}"
                                           value="{{ $balance > 0 ? $balance : 0 }}"
                                           oninput="updatePaymentSummary({{ $order->id }})"
                                           {{ $isFullyPaid || $isReceived ? 'disabled' : '' }}
                                           required>
                                </div>
                                <div class="form-text text-muted">
                                    {{ __('passwords.max_payment') }}: {{ number_format($balance, 2) }} {{ currency_symbol() }}
                                </div>
                            </div>

                            <!-- Payment Summary -->
                            <div id="payment_summary_{{ $order->id }}" class="alert alert-info">
                                <div class="d-flex justify-content-between">
                                    <span>{{ __('passwords.total_amount') }}:</span>
                                    <span class="fw-bold">{{ number_format($totalAmount, 2) }} {{ currency_symbol() }}</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span>{{ __('passwords.total_paid') }}:</span>
                                    <span class="fw-bold text-success">{{ number_format($totalPaid, 2) }} {{ currency_symbol() }}</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span>{{ __('passwords.amount_paying') }}:</span>
                                    <span class="fw-bold text-primary" id="amount_paying_display_{{ $order->id }}">{{ number_format($balance > 0 ? $balance : 0, 2) }} {{ currency_symbol() }}</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1 border-top pt-1">
                                    <span>{{ __('passwords.new_balance') }}:</span>
                                    <span class="fw-bold text-success" id="new_balance_display_{{ $order->id }}">{{ number_format($balance > 0 ? 0 : $balance, 2) }} {{ currency_symbol() }}</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span>{{ __('passwords.payment_status') }}:</span>
                                    <span class="fw-bold" id="payment_status_display_{{ $order->id }}">{{ $isFullyPaid ? 'Fully Paid' : 'Partial Payment' }}</span>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('payments.payment_status') }}</label>
                                    <select name="payment_status" id="payment_status_{{ $order->id }}" class="form-select" {{ $isReceived ? 'disabled' : '' }}>
                                        <option value="paid" {{ $isFullyPaid ? 'selected' : '' }}>{{ __('payments.paid') }}</option>
                                        <option value="partial" {{ !$isFullyPaid ? 'selected' : '' }}>{{ __('payments.partial') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('payments.payment_date') }}</label>
                                    <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" {{ $isReceived ? 'disabled' : '' }}>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
                                    {{ __('passwords.payment_will_be_processed') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="form-label">{{ __('passwords.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('passwords.optional_notes') }}" {{ $isReceived ? 'disabled' : '' }}></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between align-items-center border-top pt-6">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-2"></i>{{ __('auth._cancel') }}
                        </button>
                        <button type="button" class="btn btn-primary" onclick="sendToSupplierWithPayment({{ $order->id }})" 
                                {{ $isFullyPaid || $isReceived ? 'disabled' : '' }}>
                            <i class="bi bi-send me-2"></i>
                            <span class="indicator-label">{{ $isFullyPaid ? __('passwords.fully_paid') : __('passwords.send_and_pay') }}</span>
                            <span class="indicator-progress">{{ __('passwords.please_wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>