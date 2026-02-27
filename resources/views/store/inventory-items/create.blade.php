
<div class="modal fade" id="kt_modal_add_inventory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_inventory_header">
                <h2 class="fw-bold">{{ __('pagination.create_item') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_inventory_form" class="form">
                    @csrf
                        <div class="row g-9 mb-8">
                            <!-- Variant -->
                            <div class="col-md-6 fv-row">
                                <label class="fs-6 fw-semibold mb-2 required">{{ __('pagination.product_variant') }}</label>
                                <select name="variant_id" class="form-select" id="variant-select" data-control="select2" data-placeholder="{{__('auth._select')}}">
                                    <option></option>
                                    @foreach ($variants as $variant)
                                        <option value="{{ $variant->id }}" data-quantity="{{ $variant->overal_quantity_at_hand }}">
                                            {{ $variant->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="variant_id"></div>
                            </div>
                            
                            <!-- Quantity on hand -->
                            <div class="col-md-6 fv-row">
                                <label class="fs-6 fw-semibold mb-2 required">{{ __('pagination.quantity_on_hand') }}</label>
                                <input type="number" class="form-control form-control-solid" name="quantity_on_hand" min="0" value="0" readonly />
                                <div id="quantity_on_hand"></div>
                            </div>
                        </div>
                        

                        <div class="row g-9 mb-8">
                            <!-- Allocated -->
                            <div class="col-md-6 fv-row">
                                <label class="fs-6 fw-semibold mb-2">{{ __('pagination.quantity_allocated') }}</label>
                                <input type="number" class="form-control form-control-solid" name="quantity_allocated" min="0" value="0" />
                                <div id="quantity_allocated"></div>
                            </div>
                            <!-- Preferred Stock -->
                            <div class="col-md-6 fv-row">
                                <label class="fs-6 fw-semibold mb-2">{{ __('pagination.preferred_stock_level') }}</label>
                                <input type="number" class="form-control form-control-solid" name="preferred_stock_level" min="0" value="0"/>
                                <div id="preferred_stock_level"></div>
                            </div>

                        </div>

                        <div class="row g-9 mb-8">
                            <!-- Batch -->
                            <div class="col-md-6 fv-row">
                                <label class="fs-6 fw-semibold mb-2">{{ __('pagination.batch_number') }}</label>
                                <input type="text" class="form-control form-control-solid" name="batch_number" maxlength="50" />
                                <div id="batch_number"></div>
                            </div>

                            <!-- Expiry -->
                            <div class="col-md-6 fv-row">
                                <label class="fs-6 fw-semibold mb-2">{{ __('pagination.expiry_date') }}</label>
                                <input type="date" class="form-control form-control-solid" name="expiry_date" />
                                <div id="expiry_date"></div>
                            </div>
                        </div>

                        
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._location')}}</span>
                                </label>
                                <select name="location_id" class="form-select form-select" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                    <option></option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                                <div id="location_id"></div>
                            </div> 
                            
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._department')}}</span>
                                </label>
                                <div class="position-relative">
                                    <select name="department_id" class="form-select form-select" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                        <option></option>
                                    </select>
                                </div>
                                <div id="department_id"></div>
                            </div>
                        </div>

                        <button type="reset" id="discardButton" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        
                        <button 
                            id="submitButton" 
                            type="button" 
                            class="btn btn-primary"
                            onclick="submitItemForm('kt_modal_add_inventory_form', 'submitButton', '{{ route('items.store') }}', 'POST', 'discardButton')">
                            
                            <span class="indicator-label">{{__('auth.submit')}}</span>
                            <span class="indicator-progress">{{__('auth.please_wait')}}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

