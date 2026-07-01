 
<div class="modal fade" id="editCategory{{ $category->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_currency">
                <h2 class="fw-bold">{{__('auth._edit')}} {{__('pagination._category')}}- {{ $category->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_edit_uom_form{{ $category->id }}" class="form">
                    @csrf
                    @method('PUT')
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._category')}} </span>
                                </label>
                                <input type="text" value="{{ $category->name }}" class="form-control form-control-solid" name="name" />
                                <div id="name{{ $category->id }}"></div>
                            </div>
                        </div>
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._description')}} </span>
                                </label>
                                <textarea class="form-control form-control-solid" name="description" id="">{!! $category->description !!}</textarea>
                                <div id="description{{ $category->id }}"></div>
                            </div>
                        </div>

                        <button type="button" id="closeModalEditButton{{$category->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button onclick="editCategoryInstanceLoop({{$category->id }})" id="editUOMButton{{ $category->id }}" type="button" class="btn btn-primary" id>
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

