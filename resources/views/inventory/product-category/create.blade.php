<x-app-layout>
    @section('title', __('pagination.product_category_new'))
    @section('content')

    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('pagination.product_category_new')}}</h1>
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
                    <li class="breadcrumb-item text-muted">{{__('pagination.product_category_new')}}</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <form id="kt_ecommerce_add_category_form" class="form d-flex flex-column flex-lg-row">

                <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
                    <div class="card card-flush py-4">
                        <div class="card-header">
                            <div class="card-title">
                                <h2>{{__('pagination._thumbnail')}}</h2>
                            </div>
                        </div>
                        
                        <div class="card-body text-center pt-0">
                            <div class="image-input image-input-empty image-input-outline image-input-placeholder mb-3" data-kt-image-input="true">
                                <div class="image-input-wrapper w-150px h-150px" style="background-image: url({{ asset('assets/media/stock/ecommerce/123.png') }} )"></div>
                                <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="{{__('auth._change')}}">
                                    <i class="ki-duotone ki-pencil fs-7">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    
                                    <input type="file" name="photo" accept=".png, .jpg, .jpeg" />
                                </label>
                                
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="{{__('auth._cancel')}}">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                                
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="{{__('auth._remove')}}">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                            </div>
                            <div class="text-muted fs-7">{{__('pagination.category_thumbnail')}}</div>
                            <div id="photot"></div>
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
                                <input type="text" name="name" class="form-control mb-2" value="Footwear" />
                                <div id="name"></div>
                            </div>

                            <div class="row g-9 mb-8">
                                <div class="mb-10 fv-row col-md-6">
                                    <label class="required form-label">{{__('pagination.parent_category')}}</span></label>
                                    <select name="parent_category_id" class="form-select" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                        <option></option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                <div id="parent_category_id"></div>
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
                            
                            <div>
                                <label class="form-label">{{__('pagination._description')}}</label>
                                <textarea name="description" class="form-control"></textarea>
                                <div id="description"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <a data-link href="javascript:void(0);" onclick="navigateToAppPages('{{ route('product-category.index') }}')" id="kt_ecommerce_add_product_cancel" class="btn btn-light me-5">{{__('auth._cancel')}}</a>
                        <button type="submit" id="submitButton" class="btn btn-primary">
                            <span class="indicator-label">{{__('auth.submit')}}</span>
                            <span class="indicator-progress">{{__('auth.please_wait')}}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
        
    <script>
        const submitFormEntities = (formId, submitButtonId, url, method = 'POST') => {
            document.getElementById(formId).addEventListener('submit', function(e) {
                e.preventDefault();

                // Prepare FormData (supports files)
                const formData = new FormData(this);
                formData.append('_method', method); // only needed if not 'POST'

                const submitButton = document.getElementById(submitButtonId);
                LiveBlade.toggleButtonLoading(submitButton, true);

                fetch(url, {
                    method: 'POST', // always POST for FormData, method override via _method
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    LiveBlade.toggleButtonLoading(submitButton, false);

                    if (data.success) {
                        window.location.href = data.redirect; // go to index route
                    } else {
                        // Clear old errors
                        ['photot', 'description', 'is_active', 'parent_category_id', 'name'].forEach(id => {
                            const el = document.getElementById(id);
                            if (el) el.textContent = ''; // clear text
                        });

                        if (data.errors) {
                            Object.keys(data.errors).forEach(key => {
                                const errorDiv = document.getElementById(key);
                                if (errorDiv) {
                                    errorDiv.style.color = 'red';
                                    errorDiv.textContent = data.errors[key][0]; // First error message
                                }
                            });
                        }

                    }
                })
                .catch(error => {
                    LiveBlade.toggleButtonLoading(submitButton, false);
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops!',
                        text: 'An Error Occurred',
                    });
                    console.error(error);
                });
            });
        };

        submitFormEntities(
            'kt_ecommerce_add_category_form',
            'submitButton',
            '{{ route('product-category.store') }}'
        );
    </script>
    
    @endsection
</x-app-layout>