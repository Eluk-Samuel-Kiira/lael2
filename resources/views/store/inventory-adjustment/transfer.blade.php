<!-- Edit Item Modal for Each Item -->
<div class="modal fade" id="stockTransfer{{$item->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    {{ __('passwords.transfer_to_loc_dept') }}: 
                    {{ $item->variant->name ?? __('pagination._none') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Form to Adjust Stock -->
                <form id="stockItemTransfer{{$item->id}}">
                    @csrf
                    @method('PUT')
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{ __('auth._department') }}</span>
                                </label>
                                <select name="department_id" class="form-select" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                    <option value="" disabled {{ is_null($item->department_id) ? 'selected' : '' }}></option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" {{ $department->id == $item->department_id ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $department->name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="department_id{{ $item->id }}"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{ __('pagination._location') }}</span>
                                </label>
                                <select name="location_id" class="form-select" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                    <option value="" disabled {{ is_null($item->location_id) ? 'selected' : '' }}></option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}" {{ $location->id == $item->location_id ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $location->name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="location_id{{ $item->id }}"></div>
                            </div>

                        </div>
                        
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-4">
                                <div class="mb-3">
                                <label class="form-label">{{ __('passwords.current_stock_level') }}</label>
                                <input type="text" class="form-control" 
                                    id="currentStock{{$item->id}}" name="current_quantity"
                                    value="{{ $item->quantity_allocated }}" readonly>
                                    <div id="current_quantity{{$item->id}}"></div>
                            </div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-8">
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
                                        <div id="adjust_amount{{$item->id}}"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="reset" class="btn btn-light me-3" id="closeModalEditButton{{ $item->id }}" data-bs-dismiss="modal">{{__('auth._discard')}}</button>
                    <button onclick="updateInventoryTransfer({{$item->id }})" id="editInvTransferButton{{ $item->id }}" type="button" class="btn btn-primary">
                        <span class="indicator-label">{{__('auth._update')}}</span>
                        <span class="indicator-progress">{{__('auth.please_wait')}}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>