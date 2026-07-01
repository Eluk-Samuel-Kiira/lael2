 
<div class="modal fade" id="editDepartment{{ $uom->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_currency">
                <h2 class="fw-bold">{{__('auth._edit')}} {{__('auth._uom')}}- {{ $uom->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_edit_uom_form{{ $uom->id }}" class="form">
                    @csrf
                    @method('PUT')
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._uom')}} </span>
                                </label>
                                <input type="text" value="{{ $uom->name }}" class="form-control form-control-solid" name="name" />
                                <div id="name{{ $uom->id }}"></div>
                            </div>
                        </div>
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._symbol')}}</span>
                                </label>
                                <input type="text" value="{{ $uom->symbol }}" class="form-control form-control-solid" name="symbol" />
                                <div id="symbol{{ $uom->id }}"></div>
                            </div>
                        </div>

                        <button type="reset" class="btn btn-light me-3" id="closeModalEditButton{{ $uom->id }}" data-bs-dismiss="modal">
                        <button type="button" id="closeDeleteModal{{$uom->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button onclick="editInstanceLoopUOM({{$uom->id }})" id="editUOMButton{{ $uom->id }}" type="button" class="btn btn-primary" id>
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






