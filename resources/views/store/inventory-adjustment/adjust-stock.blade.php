<!-- Edit Item Modal for Each Item (Adjustment) -->
<div class="modal fade" id="editItem{{$item->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    {{ __('passwords.adjust_stock_level') }}: 
                    {{ $item->variant->name ?? __('pagination._none') }} - {{__('pagination.at_the')}}
                    {{ ucwords(str_replace('_', ' ', $item->departmentItem->name)) ?? __('pagination._none') }} - 
                    {{ $item->itemLocation->name ?? __('pagination._none')}}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="adjustStockForm{{$item->id}}">
                    @csrf
                    @method('PUT')
                    
                    {{-- Hidden fields --}}
                    <input type="hidden" name="variant_id" value="{{ $item->variant_id }}">
                    
                    {{-- ✅ Error container --}}
                    <div id="adjustError{{$item->id}}" class="alert alert-danger d-none" role="alert"></div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('pagination.overall_quantity') }}</label>
                        <input type="text" class="form-control text-center fs-3 fw-bold text-success" 
                               id="overallQty{{$item->id}}" 
                               name="overal_quantity_at_hand" 
                               value="{{ $item->variant->overal_quantity_at_hand }}" readonly>
                        <small class="text-muted">{{ __('passwords.overall_stock_hint') ?? 'Total stock available at warehouse' }}</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('passwords.current_stock_level') }} - {{__('pagination.at_the')}}
                            {{ ucwords(str_replace('_', ' ', $item->departmentItem->name)) ?? __('pagination._none') }}
                        </label>
                        <input type="text" class="form-control text-center fs-3 fw-bold text-warning" 
                               id="currentStock{{$item->id}}" 
                               name="current_quantity" 
                               value="{{ $item->quantity_allocated }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('passwords.adjustment_amount') }}</label>
                        <input type="number" 
                            class="form-control text-center fs-3 fw-bold text-gray-800 quantity-input" 
                            id="adjustAmount{{$item->id}}" 
                            name="adjust_amount" 
                            value="0" 
                            data-item-id="{{$item->id}}" 
                            data-overall="{{ $item->variant->overal_quantity_at_hand }}" 
                            data-current="{{ $item->quantity_allocated }}"
                            min="-{{ $item->quantity_allocated }}" 
                            max="{{ $item->variant->overal_quantity_at_hand }}">
                        <small class="text-muted">
                            {{ __('passwords.adjustment_hint') ?? 'Positive = add from overall, Negative = return to overall' }}
                            (Min: -{{ $item->quantity_allocated }} | Max: {{ $item->variant->overal_quantity_at_hand }})
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('passwords.new_stock_level') }}</label>
                        <input type="text" class="form-control text-center fs-3 fw-bold text-success" 
                               id="newStock{{$item->id}}" 
                               name="new_quantity" 
                               value="{{ $item->quantity_allocated }}" readonly>
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
