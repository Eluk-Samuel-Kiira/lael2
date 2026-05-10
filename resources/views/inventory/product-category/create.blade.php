<div class="modal fade" id="kt_modal_add_product_category" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_department">
                <h2 class="fw-bold">{{__('pagination.product_category_new')}}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_ecommerce_add_category_form" class="form">
                    @csrf
                    <div class="text-center pt-10">
                        <div class="card-header">
                            <div class="card-title">
                                <h2>{{__('pagination.general_product')}}</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            
                            <div class="row g-9 mb-8">
                                <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                    <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                        <span class="required">{{__('pagination._category')}}</span>
                                    </label>
                                    <input type="text" class="form-control form-control-solid" name="name" value="Footwear"/>
                                    <div id="name"></div>
                                </div>
                            </div>

                            <div class="row g-9 mb-8">
                                <div class="mb-10 fv-row col-md-6">
                                    <!-- Parent Category (Typable) -->
                                    <x-typable-select 
                                        name="parent_category_id"
                                        label="pagination.parent_category"
                                        :options="$categories"
                                        placeholder="Type or select parent category..."
                                    />
                                    <div id="parent_category_id"></div>
                                </div>

                                <div class="mb-10 fv-row col-md-6">
                                    <label class="required form-label">{{__('auth._status')}}</span></label>
                                    <select name="is_active" class="form-select">
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
                                    <textarea class="form-control form-control-solid" name="description">{{__('pagination._description')}}</textarea>
                                    <div id="description"></div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="reset" class="btn btn-light me-3" id="discardParentCategoryButton" data-bs-dismiss="modal">{{__('auth._discard') }}</button>
                        
                        <button 
                            id="submitProductCategoryButton" 
                            type="button" 
                            class="btn btn-primary"
                            onclick="submitProductCategoryForm('kt_ecommerce_add_category_form', 'submitProductCategoryButton', '{{ route('product-category.store') }}', 'POST', 'discardParentCategoryButton')">
                            
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


 
  