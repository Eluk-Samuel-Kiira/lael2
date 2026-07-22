<!-- Transfer Modal -->
<div class="modal fade" id="stockTransfer{{$item->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    {{ __('passwords.transfer_stock') }}: 
                    <span class="text-warning">{{ $item->variant->name ?? __('pagination._none') }}</span>
                    {{ __('passwords.from') }} 
                    <span class="text-primary">{{ $item->departmentItem->name ?? __('pagination._none') }}</span>
                    {{ __('passwords.to') }} 
                    <span class="text-success">{{ __('passwords.select_department') }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="stockTransferForm{{$item->id}}">
                    @csrf
                    @method('PUT')
                    
                    <input type="hidden" name="inventory_item_id" value="{{ $item->id }}">
                    <input type="hidden" name="variant_id" value="{{ $item->variant_id }}">
                    
                    <!-- Error container (LiveBlade will handle this) -->
                    <div id="transferError{{$item->id}}" class="alert alert-danger d-none" role="alert"></div>
                    
                    

                    <x-liveblade-dependent-dropdown 
                        id="location_department_transfer_{{ $item->id }}"
                        parentName="location_id"
                        childName="department_id"
                        parentLabel="pagination._location"
                        childLabel="auth._department"
                        :parentOptions="$locations"
                        :childOptions="$departments"
                        route="{{ route('get.departments') }}"
                        {{-- selectedParent="{{ $item->location_id }}" --}}
                        {{-- selectedChild="{{ $item->department_id }}" --}}
                        skipAjax="true"
                        parentValueKey="id"
                        childValueKey="id"
                    />
                    <div id="location_id{{ $item->id }}"></div>
                    <div id="department_id{{ $item->id }}"></div>
                    
                    <div class="row g-9 mb-8">
                        <div class="col-md-5">
                            <label class="form-label fw-bold">{{ __('passwords.current_stock_level') }}</label>
                            <input type="text" class="form-control text-center fs-3 fw-bold text-warning" 
                                   id="currentStockTransfer{{$item->id}}" 
                                   name="current_quantity"
                                   value="{{ $item->quantity_allocated }}" readonly>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-bold">{{ __('passwords.transfer_quantity') }}</label>
                            <input type="number"
                                   class="form-control text-center fs-3 fw-bold text-gray-800"
                                   id="transferAmount{{$item->id}}"
                                   name="transfer_amount"
                                   value="0"
                                   data-item-id="{{$item->id}}" 
                                   data-current="{{ $item->quantity_allocated }}"
                                   min="0"
                                   max="{{ $item->quantity_allocated }}">
                            <small class="text-muted">{{ __('passwords.transfer_hint') ?? 'Enter quantity to transfer' }}</small>
                        </div>
                    </div>

                    <div class="alert alert-primary d-flex align-items-center mt-3" role="alert">
                        <i class="bi bi-arrow-left-right fs-4 me-2"></i>
                        <div>
                            <span class="fw-bold">{{ __('passwords.transfer_summary') }}</span><br>
                            <span id="transferSummary{{$item->id}}">
                                {{ __('passwords.no_transfer_yet') }}
                            </span>
                        </div>
                    </div>
                    
                    <button type="reset" class="btn btn-light me-3" id="closeTransferModalButton{{ $item->id }}" data-bs-dismiss="modal">{{__('auth._discard')}}</button>
                    @can('transfer stock')
                    <button onclick="updateInventoryTransfer({{$item->id}})" 
                            id="editInvTransferButton{{ $item->id }}" 
                            type="button" 
                            class="btn btn-primary">
                        <span class="indicator-label">{{__('auth._transfer')}}</span>
                        <span class="indicator-progress" style="display: none;">{{__('auth.please_wait')}}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                    @endcan
                </form>
            </div>
        </div>
    </div>
</div>