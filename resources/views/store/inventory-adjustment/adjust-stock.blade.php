<!-- Edit Item Modal for Adjustment -->
<div class="modal fade" id="editItem{{$item->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    {{ __('passwords.adjust_stock_level') }}: 
                    {{ __('passwords.moving') }} 
                    <span class="text-warning">{{ $item->variant->name ?? __('pagination._none') }}</span> 
                    <span class="text-muted">{{ __('passwords.pending_allocation') }}</span> 
                    {{ __('passwords.to') }} 
                    <span class="text-success">{{ ucwords(str_replace('_', ' ', $item->departmentItem->name)) ?? __('pagination._none') }}</span> 
                    {{ __('passwords.department_at') }} 
                    <span class="text-primary">{{ $item->itemLocation->name ?? __('pagination._none') }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="adjustStockForm{{$item->id}}">
                    @csrf
                    @method('PUT')
                    
                    <input type="hidden" name="inventory_item_id" value="{{ $item->id }}">
                    <input type="hidden" name="variant_id" value="{{ $item->variant_id }}">
                    
                    <!-- Error container (LiveBlade will handle this) -->
                    <div id="adjustError{{$item->id}}" class="alert alert-danger d-none" role="alert"></div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('pagination.overall_quantity') }}</label>
                        <input type="text" class="form-control text-center fs-3 fw-bold text-success" 
                               id="overallQty{{$item->id}}" 
                               name="overal_quantity_at_hand" 
                               value="{{ $item->variant->overal_quantity_at_hand ?? 0 }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('passwords.current_stock_level') }}</label>
                        <input type="text" class="form-control text-center fs-3 fw-bold text-warning" 
                               id="currentStock{{$item->id}}" 
                               name="current_quantity" 
                               value="{{ $item->quantity_allocated ?? 0 }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('passwords.adjustment_amount') }}</label>
                        <input type="number" 
                            class="form-control text-center fs-3 fw-bold text-gray-800" 
                            id="adjustAmount{{$item->id}}" 
                            name="adjust_amount" 
                            value="0" 
                            data-item-id="{{$item->id}}" 
                            data-overall="{{ $item->variant->overal_quantity_at_hand ?? 0 }}" 
                            data-current="{{ $item->quantity_allocated ?? 0 }}"
                            min="-{{ $item->quantity_allocated ?? 0 }}" 
                            max="{{ $item->variant->overal_quantity_at_hand ?? 0 }}">
                        <small class="text-muted">
                            Positive = add from overall | Negative = return to overall
                            (Min: -{{ $item->quantity_allocated ?? 0 }} | Max: {{ $item->variant->overal_quantity_at_hand ?? 0 }})
                        </small>
                        <div id="adjustAmountError{{$item->id}}" class="text-danger mt-1" style="display: none;"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('passwords.new_stock_level') }}</label>
                        <input type="text" class="form-control text-center fs-3 fw-bold text-success" 
                               id="newStock{{$item->id}}" 
                               name="new_quantity" 
                               value="{{ $item->quantity_allocated ?? 0 }}" readonly>
                    </div>

                    <div class="alert alert-info d-flex align-items-center mt-3" role="alert">
                        <i class="bi bi-info-circle fs-4 me-2"></i>
                        <div>
                            <span class="fw-bold">{{ __('passwords.adjustment_summary') }}</span><br>
                            <span id="adjustmentSummary{{$item->id}}">
                                {{ __('passwords.no_adjustment_yet') }}
                            </span>
                        </div>
                    </div>

                    <button type="reset" class="btn btn-light me-3" id="closeModalEditButton{{ $item->id }}" data-bs-dismiss="modal">{{__('auth._discard')}}</button>
                    <button onclick="updateInventoryAdjustment({{$item->id}})" id="editInvAdjustButton{{ $item->id }}" type="button" class="btn btn-primary">
                        <span class="indicator-label">{{__('auth._update')}}</span>
                        <span class="indicator-progress">{{__('auth.please_wait')}}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>