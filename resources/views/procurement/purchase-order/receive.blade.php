<!-- Receive Items Modal -->
<div class="modal fade" id="receiveItemsModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('passwords.receive_items') }} - {{ $order->po_number }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body px-5 py-7">
                <form id="receiveItemsForm{{ $order->id }}" class="form">
                    @csrf
                    
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle me-2"></i>
                        {{ __('passwords.receive_items_instruction') }}
                    </div>

                    <!-- Items to Receive -->
                    <div class="border rounded p-4 mb-4">
                        <h5 class="fw-bold mb-4">{{ __('passwords.items_to_receive') }}</h5>
                        
                        @foreach($order->items as $item)
                        <div class="row g-3 mb-3 pb-3 border-bottom receive-item-row">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">{{ __('passwords.product') }}</label>
                                <div class="form-control bg-light">
                                    {{ $item->product_name }} ({{ $item->sku }})
                                </div>
                                <input type="hidden" name="items[{{ $item->id }}][purchase_order_item_id]" value="{{ $item->id }}">
                                <input type="hidden" name="items[{{ $item->id }}][product_variant_id]" value="{{ $item->product_variant_id }}">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">{{ __('passwords.ordered') }}</label>
                                <div class="form-control bg-light text-center ordered-quantity">
                                    {{ $item->quantity }}
                                </div>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-semibold">{{ __('passwords.received') }}</label>
                                <div class="form-control bg-light text-center received-quantity">
                                    {{ $item->received_quantity }}
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">{{ __('passwords.pending') }}</label>
                                <div class="form-control bg-light text-center">
                                    {{ $item->quantity - $item->received_quantity }}
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label fw-semibold text-primary">{{ __('passwords.receiving_now') }}</label>
                                <input type="number" 
                                       name="items[{{ $item->id }}][quantity_received]" 
                                       class="form-control receiving-quantity" 
                                       min="0" 
                                       max="{{ $item->quantity - $item->received_quantity }}"
                                       value="0"
                                       onchange="updateReceivingTotal({{ $order->id }})">
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Batch Information -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('passwords.batch_number') }} ({{ __('passwords.optional') }})</label>
                            <input type="text" name="batch_number" class="form-control" placeholder="{{ __('passwords.enter_batch_number') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('passwords.expiry_date') }} ({{ __('passwords.optional') }})</label>
                            <input type="date" name="expiry_date" class="form-control" min="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('payments.payment_method') }}</label>
                            <select name="payment_method_id" class="form-select" required>
                                <option value="">{{ __('payments.select_payment_method') }}</option>
                                @foreach($active_payment_methods as $method)
                                    <option value="{{ $method->id }}" 
                                        {{ old('payment_method_id') == $method->id ? 'selected' : '' }}>
                                        {{ $method->name }}
                                        @if($method->is_default)
                                            <span class="text-muted">({{ __('payments._default') }})</span>
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div id="payment_method_id{{ $order->id }}"></div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">{{ __('payments.payment_status') }}</label>
                            <select name="payment_status" class="form-select">
                                <option value="pending">{{ __('payments.pending') }}</option>
                                <option value="partial">{{ __('payments.partial') }}</option>
                                <option value="paid" selected>{{ __('payments.paid') }}</option>
                                <option value="overdue">{{ __('payments.overdue') }}</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">{{ __('payments.payment_date') }}</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <!-- Receiving Summary -->
                    <div class="border rounded p-4 mb-4">
                        <h5 class="fw-bold mb-3">{{ __('passwords.receiving_summary') }}</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td>{{ __('passwords.total_ordered') }}:</td>
                                        <td class="text-end fw-bold">{{ $order->items->sum('quantity') }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ __('passwords.total_received') }}:</td>
                                        <td class="text-end fw-bold text-success">{{ $order->items->sum('received_quantity') }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ __('passwords.total_pending') }}:</td>
                                        <td class="text-end fw-bold text-warning">{{ $order->items->sum('quantity') - $order->items->sum('received_quantity') }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td>{{ __('passwords.receiving_now') }}:</td>
                                        <td class="text-end fw-bold text-primary" id="receivingTotal{{ $order->id }}">0</td>
                                    </tr>
                                    <tr>
                                        <td>{{ __('passwords.remaining_after') }}:</td>
                                        <td class="text-end fw-bold" id="remainingAfter{{ $order->id }}">{{ $order->items->sum('quantity') - $order->items->sum('received_quantity') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="form-label">{{ __('passwords.receiving_notes') }} ({{ __('passwords.optional') }})</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="{{ __('passwords.enter_receiving_notes') }}"></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                {{ __('auth._cancel') }}
                            </button>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-warning" onclick="submitReceiving({{ $order->id }}, 'partially_received')">
                                {{ __('passwords.mark_partially_received') }}
                            </button>
                            <button type="button" class="btn btn-success" onclick="submitReceiving({{ $order->id }}, 'received')">
                                {{ __('passwords.mark_fully_received') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>