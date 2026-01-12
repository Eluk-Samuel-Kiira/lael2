 
<div class="modal fade" id="kt_modal_add_product" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_department">
                <h2 class="fw-bold">{{__('auth._create')}} {{__('pagination._product')}}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_product_form" class="form">
                    @csrf
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._product')}}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="name" />
                                <div id="name"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._sku')}}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="sku" />
                                <div id="sku"></div>
                            </div>
                        </div>
                        <div class="row g-9 mb-8">
                            <div class="mb-10 fv-row col-md-6">
                                <label class="required form-label">{{__('pagination.is_taxable')}}</span></label>
                                <select name="is_taxable" class="form-select" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                    <option></option>
                                    <option value="1">{{__('pagination._yes')}}</option>
                                    <option value="0">{{__('pagination._no')}}</option>
                                </select>
                                <div id="is_taxable"></div>
                            </div>
                            <div class="mb-10 fv-row col-md-6">
                                <label class="required form-label">{{__('pagination._type')}}</span></label>
                                <select name="type" class="form-select" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                    <option></option>
                                    <option value="physical">{{__('pagination._physical')}}</option>
                                    <option value="digital">{{__('pagination._digital')}}</option>
                                    <option value="service">{{__('pagination._service')}}</option>
                                    <option value="composite">{{__('pagination._composite')}}</option>
                                </select>
                                <div id="type"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="mb-10 fv-row col-md-6">
                                <label class="required form-label">{{__('pagination._category')}}</span></label>
                                <select name="category_id" class="form-select" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                    <option></option>
                                    @foreach ($sub_categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <div id="category_id"></div>
                            </div>

                            <div class="mb-10 fv-row col-md-6">
                                <label class="required form-label">{{__('auth._status')}}</span></label>
                                <select name="is_active" class="form-select" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                    <option></option>
                                    <option value="1">{{__('auth._active')}}</option>
                                    <option value="0">{{__('auth._inactive')}}</option>
                                </select>
                                <div id="is_active"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._description')}}</span>
                                </label>
                                <textarea class="form-control form-control-solid" name="description">Product Is Pure</textarea>
                                <div id="description"></div>
                            </div>
                        </div>

                        <button type="reset" class="btn btn-light me-3" id="discardProductButton" data-bs-dismiss="modal">{{__('auth._discard') }}</button>
                        
                        <button 
                            id="submitProductButton" 
                            type="button" 
                            class="btn btn-primary"
                            onclick="submitProductForm('kt_modal_add_product_form', 'submitProductButton', '{{ route('products.store') }}', 'POST', 'discardProductButton')">
                            
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

