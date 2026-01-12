 
<div class="modal fade" id="editCurrency{{ $currency->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_currency">
                <h2 class="fw-bold">{{__('auth.edit_currency')}} - {{ $currency->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_edit_currency_form{{ $currency->id }}" class="form">
                    @csrf
                    @method('PUT')
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._code')}}</span>
                                </label>
                                <input type="text" value="{{ $currency->code }}" class="form-control form-control-solid" name="code" />
                                <div id="code{{ $currency->id }}"></div>
                            </div>
                        </div>
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._name')}}</span>
                                </label>
                                <input type="text" value="{{ $currency->name }}" class="form-control form-control-solid" name="name" />
                                <div id="name{{ $currency->id }}"></div>
                            </div>
                        </div>
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._symbol')}}</span>
                                </label>
                                <input type="text" value="{{ $currency->symbol }}" class="form-control form-control-solid" name="symbol" />
                                <div id="symbol{{ $currency->id }}"></div>
                            </div>
                        </div>
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._exchange_rate')}}</span>
                                </label>
                                <input type="text" value="{{ $currency->exchange_rate }}" class="form-control form-control-solid" name="exchange_rate" />
                                <div id="exchange_rate{{ $currency->id }}"></div>
                            </div>
                        </div>

                        <button type="reset" class="btn btn-light me-3" id="closeModalEditButton{{ $currency->id }}" data-bs-dismiss="modal">{{__('auth._discard')}}</button>
                        <button onclick="editInstanceLoopCurrency({{$currency->id }})" id="editCurrencyButton{{ $currency->id }}" type="button" class="btn btn-primary" id>
                            <span class="indicator-label">{{__('auth._update')}}</span>
                            <span class="indicator-progress">{{__('auth.please_wait')}}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>  





