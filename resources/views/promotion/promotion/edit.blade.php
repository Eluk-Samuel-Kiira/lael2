 
<div class="modal fade" id="editTax{{ $promotion->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_currency">
                <h2 class="fw-bold">{{__('auth._edit')}} {{__('pagination._promotion')}} - {{ $promotion->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_promotion_form{{ $promotion->id }}" class="form">
                    @csrf
                    @method('PUT')
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._promotion')}} </span>
                                </label>
                                <input type="text" value="{{ $promotion->name }}" class="form-control form-control-solid" name="name" />
                                <div id="name{{ $promotion->id }}"></div>
                            </div>
                        </div>

                        
                        <div class="row g-9 mb-8">
                            <div class="mb-10 fv-row col-md-6">
                                <label class="required form-label">{{__('pagination.discount_type')}}</span></label>
                                <select name="discount_type" class="form-select" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                    <option></option>
                                    <option value="percentage" {{ $promotion->discount_type == 'percentage' ? 'selected' : '' }}>{{__('pagination._percentage')}}</option>
                                    <option value="fixed_amount" {{ $promotion->discount_type == 'fixed_amount' ? 'selected' : '' }}>{{__('pagination._fixed')}}</option>
                                    <option value="buy_x_get_y" {{ $promotion->discount_type == 'buy_x_get_y' ? 'selected' : '' }}>{{__('pagination.buy_x_get_y')}}</option>
                                </select>
                                <div id="discount_type{{ $promotion->id }}"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.discount_value')}} </span>
                                </label>
                                <input type="text" value="{{ $promotion->discount_value }}" class="form-control form-control-solid" name="discount_value" />
                                <div id="discount_value{{ $promotion->id }}"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.start_date')}} </span>
                                </label>
                                <input type="date"
                                    value="{{ \Carbon\Carbon::parse($promotion->start_date)->format('Y-m-d') }}"
                                    class="form-control form-control-solid"
                                    name="start_date" />
                                <div id="start_date{{ $promotion->id }}"></div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.end_date')}} </span>
                                </label>               
                                <input type="date"
                                    value="{{ \Carbon\Carbon::parse($promotion->end_date)->format('Y-m-d') }}"
                                    class="form-control form-control-solid"
                                    name="end_date" />
                                <div id="end_date{{ $promotion->id }}"></div>
                            </div>
                        </div>

                        <button type="button" id="closeModalEditButton{{$promotion->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button onclick="editPromotionInstanceLoop({{$promotion->id }})" id="editPromotionButton{{ $promotion->id }}" type="button" class="btn btn-primary">
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




