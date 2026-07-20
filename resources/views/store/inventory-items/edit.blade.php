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
                    
                    {{-- Hidden fields --}}
                    <input type="hidden" name="variant_id" value="{{ $item->variant_id }}">
                    <input type="hidden" name="quantity_allocated" id="quantity_allocated_{{ $item->id }}" value="{{ $item->quantity_allocated }}">
                    
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <x-liveblade-dependent-dropdown 
                                id="location_department_{{ $item->id }}"
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
                        
                        {{-- ⚠️ LOW STOCK ALERT --}}
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
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
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.preferred_stock_level')}} </span>
                                </label>
                                <input 
                                    type="number" 
                                    value="{{ $item->preferred_stock_level ?? 5 }}" 
                                    class="form-control form-control-solid" 
                                    name="preferred_stock_level"
                                    min="0"
                                    placeholder="e.g., 5"
                                />
                                <div class="form-text text-muted">{{ __('pagination.preferred_stock_level_hint') ?? 'Alert when stock falls below this number' }}</div>
                                <div id="preferred_stock_level{{ $item->id }}"></div>
                            </div>
                        </div>

                        {{-- 📦 STOCK LEVELS --}}
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-4">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.available_stock')}} </span>
                                </label>
                                <input 
                                    type="number" 
                                    value="{{ $item->variant->overal_quantity_at_hand }}" 
                                    class="form-control form-control-solid" 
                                    name="available_stock" 
                                    readonly
                                    id="available_stock_{{ $item->id }}"
                                />
                                <div class="form-text text-muted">{{ __('pagination.available_stock_hint') ?? 'Total stock not yet allocated to stores' }}</div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-4">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.current_allocated')}} </span>
                                </label>
                                <input 
                                    type="number" 
                                    value="{{ $item->quantity_allocated }}" 
                                    class="form-control form-control-solid" 
                                    name="current_allocated"
                                    id="current_allocated_{{ $item->id }}"
                                    readonly
                                />
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-4">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.new_quantity')}} </span>
                                </label>
                                <input 
                                    type="number" 
                                    value="{{ $item->quantity_allocated }}" 
                                    class="form-control form-control-solid" 
                                    name="new_quantity"
                                    id="new_quantity_{{ $item->id }}"
                                    min="0"
                                    oninput="calculateAllocation(this, {{ $item->id }}, {{ $item->variant->overal_quantity_at_hand }}, {{ $item->quantity_allocated }})"
                                />
                                <div class="form-text text-muted">{{ __('pagination.new_quantity_hint') ?? 'New quantity to allocate to this store' }}</div>
                                <div id="new_quantity_error_{{ $item->id }}" class="text-danger mt-1" style="display: none;"></div>
                            </div>
                        </div>

                        {{-- 📊 LIVE CALCULATION SUMMARY --}}
                        <div class="row g-9 mb-8">
                            <div class="col-12">
                                <div class="alert alert-light-primary d-flex align-items-center p-4" role="alert">
                                    <div class="d-flex flex-wrap w-100 justify-content-between">
                                        <div>
                                            <span class="fw-bold">{{ __('pagination.available_stock') }}:</span>
                                            <span id="display_available_{{ $item->id }}" class="badge badge-light-success fs-6 ms-2">{{ $item->variant->overal_quantity_at_hand }}</span>
                                        </div>
                                        <div>
                                            <span class="fw-bold">{{ __('pagination.current_allocated') }}:</span>
                                            <span id="display_current_allocated_{{ $item->id }}" class="badge badge-light-warning fs-6 ms-2">{{ $item->quantity_allocated }}</span>
                                        </div>
                                        <div>
                                            <span class="fw-bold">{{ __('pagination.new_allocated') }}:</span>
                                            <span id="display_new_allocated_{{ $item->id }}" class="badge badge-light-primary fs-6 ms-2">{{ $item->quantity_allocated }}</span>
                                        </div>
                                        <div>
                                            <span class="fw-bold">{{ __('pagination.remaining_stock') }}:</span>
                                            <span id="display_remaining_{{ $item->id }}" class="badge badge-light-info fs-6 ms-2">{{ $item->variant->overal_quantity_at_hand }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" id="closeModalEditButton{{$item->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button onclick="editItemInstanceLoop({{$item->id }})" id="updateItemButton{{ $item->id }}" type="button" class="btn btn-primary">
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