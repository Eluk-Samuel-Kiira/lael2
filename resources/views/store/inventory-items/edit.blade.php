 
<div class="modal fade" id="editItem{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_currency">
                <h2 class="fw-bold">{{__('auth._update')}} {{__('pagination.inventory_item')}} - {{ $item->variant->name ?? __('pagination._none') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_item_form{{ $item->id }}" class="form">
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
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.expiry_date')}} </span>
                                </label>
                                <input 
                                    type="date" 
                                    value="{{ $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('Y-m-d') : '' }}" 
                                    class="form-control form-control-solid" 
                                    name="expiry_date" 
                                />
                                <div id="expiry_date{{ $item->id }}"></div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-4">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.quantity_on_hand')}} </span>
                                </label>
                                <input type="number" value="{{ $item->quantity_on_hand }}" class="form-control form-control-solid" name="quantity_on_hand" readonly/>
                                <div id="quantity_on_hand{{ $item->id }}"></div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-4">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.quantity_allocated')}} </span>
                                </label>
                                <input type="number" value="{{ $item->quantity_allocated }}" class="form-control form-control-solid" name="quantity_allocated" readonly/>
                                <div id="quantity_allocated{{ $item->id }}"></div>
                            </div>
                        </div>              

                        <button type="button" id="closeModalEditButton{{$item->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button onclick="editItemInstanceLoop({{$item->id }})" id="updateItemButton{{ $item->id }}" type="button" class="btn btn-primary" id>
                            <span class="indicator-label">{{__('auth._update')}}</span>
                            <span class="indicator-progress">{{__('auth.please_wait') }}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>  


