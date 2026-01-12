 
<div class="modal fade" id="editProduct{{ $product->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_currency">
                <h2 class="fw-bold">{{__('auth._edit')}} {{__('pagination._product')}} - {{ $product->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_product_form{{ $product->id }}" class="form">
                    @csrf
                    @method('PUT')
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._product')}} </span>
                                </label>
                                <input type="text" value="{{ $product->name }}" class="form-control form-control-solid" name="name" />
                                <div id="name{{ $product->id }}"></div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._sku')}} </span>
                                </label>
                                <input type="text" value="{{ $product->sku }}" class="form-control form-control-solid" name="sku" />
                                <div id="sku{{ $product->id }}"></div>
                            </div>
                        </div>

                        
                        <div class="row g-9 mb-8">
                            <div class="mb-10 fv-row col-md-6">
                                <label class="required form-label">{{__('pagination._category')}}</span></label>
                                <select name="category_id" class="form-select" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                    <option></option>
                                    @foreach ($sub_categories as $category)
                                        <option value="{{ $category->id }}" {{ $product->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <div id="category_id{{ $product->id }}"></div>
                            </div>
                            
                            <div class="mb-10 fv-row col-md-6">
                                <label class="required form-label">{{__('pagination._type')}}</span></label>
                                <select name="type" class="form-select" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                    <option></option>
                                    <option value="physical" {{ $product->type == 'physical' ? 'selected' : '' }}>{{__('pagination._physical')}}</option>
                                    <option value="digital" {{ $product->type == 'digital' ? 'selected' : '' }}>{{__('pagination._digital')}}</option>
                                    <option value="service" {{ $product->type == 'service' ? 'selected' : '' }}>{{__('pagination._service')}}</option>
                                    <option value="composite" {{ $product->type == 'composite' ? 'selected' : '' }}>{{__('pagination._composite')}}</option>
                                </select>
                                <div id="type{{ $product->id }}"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._description')}} </span>
                                </label>
                                <textarea class="form-control form-control-solid" name="description" id="">{!! $product->description !!}</textarea>
                                <div id="description{{ $product->id }}"></div>
                            </div>
                        </div>

                        <button type="button" id="closeModalEditButton{{$product->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button onclick="editProductInstanceLoop({{$product->id }})" id="editProductButton{{ $product->id }}" type="button" class="btn btn-primary" id>
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




