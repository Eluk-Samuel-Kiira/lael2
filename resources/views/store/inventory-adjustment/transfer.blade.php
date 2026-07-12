<!-- Edit Item Modal for Each Item (Transfer Modal) -->
<div class="modal fade" id="stockTransfer{{$item->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    {{ __('passwords.transfer_to_loc_dept') }}
                    {{ $item->departmentItem->name ?? __('pagination._none') }} : 
                    {{ $item->variant->name ?? __('pagination._none') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="stockItemTransfer{{$item->id}}">
                    @csrf
                    @method('PUT')
                    <div class="text-center pt-10">
                        <!-- Location & Department (Dependent Dropdown) -->
                        <div class="row g-9 mb-8">
                            <x-liveblade-dependent-dropdown 
                                id="location_department_transfer_{{ $item->id }}"
                                parentName="location_id"
                                childName="department_id"
                                parentLabel="pagination._location"
                                childLabel="auth._department"
                                :parentOptions="$locations"
                                :childOptions="$departments"
                                route="{{ route('get.departments') }}"
                                selectedParent="{{ $item->location_id }}"
                                selectedChild="{{ $item->department_id }}"
                                skipAjax="true"
                            />
                            <div id="location_id{{ $item->id }}"></div>
                            <div id="department_id{{ $item->id }}"></div>
                        </div>
                        
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-5">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('passwords.current_stock_level') }}</label>
                                    <input type="text" class="form-control" 
                                        id="currentStock{{$item->id}}" name="current_quantity"
                                        value="{{ $item->quantity_allocated }}" readonly>
                                </div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-7">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('passwords.transfer_quantity') }}</label>
                                    <input type="number"
                                        class="form-control text-center fs-3 fw-bold text-gray-800 quantity-input"
                                        id="adjustAmount{{$item->id}}"
                                        name="adjust_amount"
                                        value="0"
                                        data-item-id="{{$item->id}}" 
                                        data-overall="{{ $item->variant->overal_quantity_at_hand }}"
                                        data-current="{{ $item->quantity_allocated }}" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- UNIQUE CLOSE BUTTON ID FOR TRANSFER -->
                    <button type="reset" class="btn btn-light me-3" id="closeTransferModalButton{{ $item->id }}" data-bs-dismiss="modal">{{__('auth._discard')}}</button>
                    @can('transfer stock')
                    <button onclick="updateInventoryTransfer({{$item->id}})" id="editInvTransferButton{{ $item->id }}" type="button" class="btn btn-primary">
                        <span class="indicator-label">{{__('auth._update')}}</span>
                        <span class="indicator-progress">{{__('auth.please_wait')}}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                    @endcan
                </form>
            </div>
        </div>
    </div>
</div>