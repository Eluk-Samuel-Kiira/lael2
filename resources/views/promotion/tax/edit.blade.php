 
<div class="modal fade" id="editTax{{ $tax->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_currency">
                <h2 class="fw-bold">{{__('auth._edit')}} {{__('pagination._tax')}} - {{ $tax->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_tax_form{{ $tax->id }}" class="form">
                    @csrf
                    @method('PUT')
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._tax')}} </span>
                                </label>
                                <input type="text" value="{{ $tax->name }}" class="form-control form-control-solid" name="name" />
                                <div id="name{{ $tax->id }}"></div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._code')}} </span>
                                </label>
                                <input type="text" value="{{ $tax->code }}" class="form-control form-control-solid" name="code" />
                                <div id="code{{ $tax->id }}"></div>
                            </div>
                        </div>

                        
                        <div class="row g-9 mb-8">
                            <div class="mb-10 fv-row col-md-6">
                                <label class="required form-label">{{__('pagination._type')}}</span></label>
                                <select name="type" class="form-select" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                    <option></option>
                                    <option value="percentage" {{ $tax->type == 'percentage' ? 'selected' : '' }}>{{__('pagination._percentage')}}</option>
                                    <option value="fixed" {{ $tax->type == 'fixed' ? 'selected' : '' }}>{{__('pagination._fixed')}}</option>
                                </select>
                                <div id="type{{ $tax->id }}"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._rate')}} </span>
                                </label>
                                <input type="text" value="{{ $tax->rate }}" class="form-control form-control-solid" name="rate" />
                                <div id="rate{{ $tax->id }}"></div>
                            </div>
                        </div>

                        <button type="button" id="closeModalEditButton{{$tax->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button onclick="editTaxInstanceLoop({{$tax->id }})" id="editTaxButton{{ $tax->id }}" type="button" class="btn btn-primary" id>
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




