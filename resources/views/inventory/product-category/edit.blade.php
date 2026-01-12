<x-app-layout>
    @section('title', __('pagination.product_category_edit'))
    @section('content')

    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('pagination.product_category_edit')}}</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        @php
                            $previousUrl = url()->previous();
                            $previousRouteName = optional(app('router')->getRoutes()->match(request()->create($previousUrl)))->getName();
                            $formattedRouteName = $previousRouteName 
                                ? Str::of($previousRouteName)->replace('.', ' ')->title() 
                                : __('auth._back');
                        @endphp
                        <a href="{{ $previousUrl }}" class="text-muted text-hover-primary">
                            {{ $formattedRouteName }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('pagination.product_category_edit')}}</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <form id="kt_ecommerce_edit_category_form" 
                class="form d-flex flex-column flex-lg-row"
                action="{{ route('product-category.update', $productCategory->id) }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf
                @method('PATCH')

                <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
                    <div class="card card-flush py-4">
                        <div class="card-header">
                            <div class="card-title">
                                <h2>{{__('pagination._thumbnail')}}</h2>
                            </div>
                        </div>
                        <div class="card-body text-center pt-0">
                            <div class="image-input image-input-outline image-input-placeholder mb-3" data-kt-image-input="true">
                                <div 
                                    class="image-input-wrapper w-150px h-150px" 
                                    style="background-image: url({{ productCategoryImage($productCategory->image_url) }})"
                                ></div>

                                <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" 
                                    data-kt-image-input-action="change" 
                                    data-bs-toggle="tooltip" 
                                    title="{{__('auth._change')}}">
                                    <i class="ki-duotone ki-pencil fs-7">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <input type="file" name="photo" accept=".png, .jpg, .jpeg" />
                                </label>

                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" 
                                    data-kt-image-input-action="cancel" 
                                    data-bs-toggle="tooltip" 
                                    title="{{__('auth._cancel')}}">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>

                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" 
                                    data-kt-image-input-action="remove" 
                                    data-bs-toggle="tooltip" 
                                    title="{{__('auth._remove')}}">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                            </div>
                            <div class="text-muted fs-7">{{__('pagination.category_thumbnail')}}</div>
                            @error('photo')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <!--end::Aside column-->

                <!--begin::Main column-->
                <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
                    <div class="card card-flush py-4">
                        <div class="card-header">
                            <div class="card-title">
                                <h2>{{__('pagination.general_product')}}</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="mb-10 fv-row">
                                <label class="required form-label">{{__('pagination._category')}}</label>
                                <input type="text" name="name" class="form-control mb-2" value="{{ old('name', $productCategory->name) }}" />
                                @error('name')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row g-9 mb-8">
                                <div class="mb-10 fv-row col-md-6">
                                    <label class="required form-label">{{__('pagination.parent_category')}}</label>
                                    <select name="parent_category_id" class="form-select" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                        <option></option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ (old('parent_category_id', $productCategory->parent_category_id) == $cat->id) ? 'selected' : '' }}>
                                                {{ $cat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('parent_category_id')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-10 fv-row col-md-6">
                                    <label class="required form-label">{{__('auth._status')}}</label>
                                    <select name="is_active" class="form-select" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                        <option></option>
                                        <option value="1" {{ (old('is_active', $productCategory->is_active) == 1) ? 'selected' : '' }}>{{__('auth._active')}}</option>
                                        <option value="0" {{ (old('is_active', $productCategory->is_active) == 0) ? 'selected' : '' }}>{{__('auth._inactive')}}</option>
                                    </select>
                                    @error('parent_category_id')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label class="form-label">{{__('pagination._description')}}</label>
                                <textarea name="description" class="form-control">{{ old('description', $productCategory->description) }}</textarea>
                                @error('description')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('product-category.index') }}" class="btn btn-light me-5">{{__('auth._cancel')}}</a>
                        <button type="submit" id="submitButton" class="btn btn-primary">
                            <span class="indicator-label">{{__('auth._update')}}</span>
                            <span class="indicator-progress">{{__('auth.please_wait')}}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>




    
    @endsection
</x-app-layout>