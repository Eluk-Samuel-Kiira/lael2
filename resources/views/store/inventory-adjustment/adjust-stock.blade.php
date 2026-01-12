<!-- Edit Item Modal for Each Item -->
<div class="modal fade" id="editItem{{$item->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    {{ __('passwords.adjust_stock_level') }}: 
                    {{ $item->variant->name ?? __('pagination._none') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Form to Adjust Stock -->
                <form id="adjustStockForm{{$item->id}}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">{{ __('pagination.overall_quantity') }}</label>
                        <input type="text" 
                            class="form-control" 
                            id="overallQty{{$item->id}}" name="overal_quantity_at_hand"
                            value="{{ $item->variant->overal_quantity_at_hand }}" 
                            readonly>
                            <div id="overal_quantity_at_hand{{$item->id}}"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('passwords.current_stock_level') }}</label>
                        <input type="text" class="form-control" 
                            id="currentStock{{$item->id}}" name="current_quantity"
                            value="{{ $item->quantity_allocated }}" readonly>
                            <div id="current_quantity{{$item->id}}"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('passwords.adjustment_amount') }}</label>
                        <input type="number"
                            class="form-control text-center fs-3 fw-bold text-gray-800 quantity-input"
                            id="adjustAmount{{$item->id}}"
                            name="adjust_amount"
                            value="0"
                            data-item-id="{{$item->id}}" 
                            data-overall="{{ $item->variant->overal_quantity_at_hand }}"
                            data-current="{{ $item->quantity_allocated }}" />
                            <div id="adjust_amount{{$item->id}}"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('passwords.new_stock_level') }}</label>
                        <input type="text" class="form-control" 
                            id="newStock{{$item->id}}" name="new_quantity"
                            value="{{ $item->quantity_allocated }}" readonly>
                            <div id="new_quantity{{$item->id}}"></div>
                    </div>

                    <button type="reset" class="btn btn-light me-3" id="closeModalEditButton{{ $item->id }}" data-bs-dismiss="modal">{{__('auth._discard')}}</button>
                    <button onclick="updateInventoryAdjustment({{$item->id }})" id="editInvAdjustButton{{ $item->id }}" type="button" class="btn btn-primary">
                        <span class="indicator-label">{{__('auth._update')}}</span>
                        <span class="indicator-progress">{{__('auth.please_wait')}}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>