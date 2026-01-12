<div class="row g-9 mb-8">
    <div class="d-flex col-md-4 position-relative thumbnail-col">
        <div class="card card-flush py-4 w-100">
            <button type="button" class="btn btn-sm btn-icon btn-danger position-absolute top-0 end-0 m-2 remove-thumbnail-btn" title="Remove Thumbnail">
                &times;
            </button>
            <div class="card-header">
                <div class="card-title">
                    <h2>{{__('pagination._thumbnail')}}</h2>
                </div>
            </div>
            <div class="card-body text-center pt-0">
                <div class="image-input image-input-outline image-input-placeholder mb-3" data-kt-image-input="true">
                    <div 
                        class="image-input-wrapper w-150px h-150px" id="product-img-preview"
                        style="background-image: url({{ productImage($product_variants->image_url) }})"
                    ></div>

                    <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" 
                        data-kt-image-input-action="change" 
                        data-bs-toggle="tooltip" 
                        title="{{__('auth._change')}}">
                        <i class="ki-duotone ki-pencil fs-7">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <input type="file" name="photo" accept=".png, .jpg, .jpeg" onchange="productPhoto(event, {{ $product_variants->id }})" />
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
            </div>
        </div>
    </div>    

    <div class="card card-flush col-md-8" id="variations-col">
        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
            <div class="card-title">
                <span class="page-heading d-flex text-gray-600">{{__('pagination._variations_of')}} {{ $product_variants->name }}</span>
            </div>
        </div>
        <div class="card-body py-4" id="reloadVariantComponent">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_users .form-check-input" value="1" />
                                </div>
                            </th>
                            <th class="min-w-125px">{{__('pagination.variant_id')}}</th>
                            <th class="min-w-125px">{{__('auth._name')}}</th> 
                            <th class="min-w-125px">{{__('pagination._sku')}}</th> 
                            <th class="min-w-125px">{{__('pagination._barcode')}}</th>
                            <th class="min-w-125px">{{__('pagination.overall_quantity')}}</th>
                            <th class="min-w-125px">{{__('pagination._price')}}</th> 
                            <th class="min-w-125px">{{__('pagination.cost_price')}}</th>
                            <th class="min-w-125px">{{__('pagination._weight')}}</th> 
                            <th class="min-w-125px">{{__('pagination.weight_unit')}}</th>
                            <th class="min-w-125px">{{__('pagination.is_taxable')}}</th>  
                            <th class="min-w-125px">{{__('auth._creater')}}</th> 
                            <th class="min-w-125px">{{__('auth.created_at')}}</th>
                            <th class="min-w-125px">{{__('auth._status')}}</th>
                            <th class="min-w-100px text-end">{{__('auth._actions')}}</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                        @if (!empty($product_variants->variants) && $product_variants->variants->count() > 0)
                            @foreach ($product_variants->variants as $product)
                                <tr data-role="{{ strtolower($product->name) }}">
                                    <td>
                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" value="1" />
                                        </div>
                                    </td>
                                    <td>
                                        <div class="badge badge-light fw-bold">{{ $product->id }}</div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0)" class="symbol symbol-50px" onclick="triggerFileInput(this, {{ $product->id }})">
                                                <img src="{{ productVariantImage($product->image_url) }}" alt="" class="symbol-label" id="variantImagePreview_{{ $product->id  }}">
                                            </a>
                                            <!-- Hidden file input -->
                                            <input type="file" id="variantImageInput_{{ $product->id ?? 0 }}" accept="image/*" style="display: none;" onchange="handleImageChange(event, {{ $product->id }})">

                                            <div class="ms-5">
                                                <a href="#" class="text-gray-800 text-hover-primary fs-5 fw-bold" data-kt-ecommerce-product-filter="product_name">{{ $product->name }}</a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="badge badge-light fw-bold">{{ $product->sku }}</div>
                                    </td>
                                    <td>{{ $product->barcode }}</td>
                                    <td class="fw-bold text-warning ms-3">{{ $product->overal_quantity_at_hand }}</td>
                                    <td class="fw-bold text-primary ms-3"> {{ displayFormatedCurrency($product->price) }} {{ currencySymbol() }}</td>
                                    <td>
                                        <div class="badge badge-light fw-bold text-success">{{ displayFormatedCurrency($product->cost_price) }} {{ currencySymbol() }}</div>
                                    </td>
                                    <td>{{ $product->weight }}</td>
                                    <td>
                                        <div class="badge badge-light fw-bold">{{ $product->unitMeasure->name ?? __('pagination._none') }}</div>
                                    </td>
                                    <td>
                                        <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                            <input type="checkbox"
                                                class="form-check-input variant-tax-switch"
                                                onchange="updateVariantTaxStatus({{ $product->id }}, this.checked ? 1 : 0)"
                                                {{ $product->is_taxable ? 'checked' : '' }}
                                                @cannot('update product') disabled @endcannot>

                                            <span id="variant-tax-label-{{ $product->id }}"
                                                class="form-check-label ms-2 fw-bold fs-6 text-gray-700">
                                                {{ $product->is_taxable ? __('pagination._yes') : __('pagination._no') }}
                                            </span>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="badge badge-light fw-bold">{{ $product->variantCreater->name ?? __('pagination._none')}}</div>
                                    </td>
                                    <td>{{ $product->created_at->format('d M Y, h:i a') }}</td>
                                    <td>
                                        <select name="status" class="form-select form-select-solid form-select-sm" onchange="updateVariantStatus({{ $product->id }}, this.value)"
                                            @cannot('update variant') disabled @endcannot>
                                            <option value="1" {{ $product->is_active == 1 ? 'selected' : '' }}>{{__('auth._active')}}</option>
                                            <option value="0" {{ $product->is_active == 0 ? 'selected' : '' }}>{{__('auth._inactive')}}</option>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            @can('update product')
                                                <button 
                                                    class="btn btn-sm btn-light btn-active-color-success d-flex align-items-center px-3 py-2" 
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#variantAssign{{$product->id}}">
                                                    <i class="bi bi-building me-1 fs-5"></i> <span>{{ __('pagination._allocation') }}</span>
                                                </button>
                                            @endcan
                                            @can('edit product')
                                                <button 
                                                    class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2" 
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editProduct{{$product->id}}">
                                                    <i class="bi bi-pencil-square me-1 fs-5"></i> <span>{{ __('auth._edit') }}</span>
                                                </button>
                                            @endcan
                                            {{--
                                            @can('delete product')
                                                <button type="button" 
                                                    class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deletecategoryModal{{$product->id}}">
                                                    <i class="bi bi-trash me-1 fs-5"></i> <span>{{ __('auth._delete') }}</span>
                                                </button>
                                            @endcan 
                                            --}}
                                        </div>

                                        <!-- Delete User Modal -->
                                        <div class="modal fade" id="deletecategoryModal{{$product->id}}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">{{ __('auth.confirm_deletion') }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>{{ __('auth.are_you_sure') }}</p>
                                                        <p>{{ __('auth.action_cannot') }}</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <!-- Discard Button -->
                                                        <button type="button" id="closeDeleteModal{{$product->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                                                        <!-- Confirm Button -->
                                                        <button type="button" id="deleteButton{{$product->id}}" class="btn btn-danger" 
                                                            data-item-url="{{ route('variants.destroy', $product->id) }}" 
                                                            data-item-id="{{ $product->id }}"
                                                            onclick="deleteItem(this)">
                                                            <span class="indicator-label">{{ __('auth._confirm') }}</span>
                                                            <span class="indicator-progress" style="display: none;">
                                                                {{__('auth.please_wait') }}
                                                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                                            </span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @include('inventory.product-variant.edit')
                                        @include('inventory.product-variant.variant-assignt')
                                        
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </diiv>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.remove-thumbnail-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const thumbnailCol = btn.closest('.thumbnail-col');
                if (thumbnailCol) {
                    // Completely remove the column from DOM
                    thumbnailCol.remove();
                    
                    // Expand variations column
                    const variationsCol = document.getElementById('variations-col');
                    if (variationsCol) {
                        variationsCol.classList.remove('col-md-8');
                        variationsCol.classList.add('col-md-12');
                    }
                }
            });
        });
    });
</script>


<script>
    // Trigger hidden file input when image is clicked
    function triggerFileInput(el, id) {
        document.getElementById(`variantImageInput_${id}`).click();
    }

    // Handle file selection
    function handleImageChange(event, id) {
        const file = event.target.files[0];
        if (!file) return;

        // Update the image preview
        const imgPreview = document.getElementById(`variantImagePreview_${id}`);
        imgPreview.src = URL.createObjectURL(file);

        // Call custom JS function with file and ID
        onVariantImageSelected(file, id);
    }

    // Your custom JS function
    function onVariantImageSelected(file, id) {
        // console.log('Selected file:', file.name, 'for variant ID:', id);

        // Create FormData and append file + variant ID
        const formData = new FormData();
        formData.append('image', file);
        formData.append('variant_id', id);

        fetch('{{ route("variant.upload_image") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData,
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to upload image');
            }
            return response.json();
        })
        .then(data => {
            const message = '{{ __('auth._uploaded') }}';
            console.log(message, data);

            // Optionally update the image preview (if backend returns the uploaded path)
            const imgPreview = document.getElementById(`variantImagePreview_${id}`);
            if (data.image_url) {
                imgPreview.src = data.image_url;
            }

            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: message,
            });
        })
        .catch(error => {
            const message = '{{ __('An Error Occurred') }}';
            console.error(message, error);
            Swal.fire({
                icon: 'error',
                title: 'Oops!',
                text: message,
            });
        });
    }

</script>



<script>
    
    // Function to preview and upload the selected profile image with validation
    function productPhoto(event, product_id) {
        const image = document.getElementById('product-img-preview');
        const file = event.target.files[0];
        
        // Validate file type (accept only images)
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        const allowedError = '{{__('auth.allowed_files')}}'
        if (!allowedTypes.includes(file.type)) {
            alert(allowedError);
            return;
        }

        // Validate file size (e.g., limit to 2MB)
        const maxSize = 5 * 1024 * 1024; // 5MB in bytes
        const allowedSize = '{{__('auth.file_large')}}'
        if (file.size > maxSize) {
            alert(allowed_size);
            return;
        }

        // If validation passes, show image preview and upload file
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                image.src = e.target.result;
            }
            reader.readAsDataURL(file);

            // Automatically upload the file
            uploadProductImage(file, product_id);
        }
    }

    // Function to upload the image via AJAX
    function uploadProductImage(file, product_id) {
        const formData = new FormData();
        formData.append('photo', file); 
        formData.append('product_id', product_id); 

        fetch('{{ route("product.upload_image") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData,
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to upload image');
            }
            return response.json();
        })
        .then(data => {
            const message = '{{__('auth._uploaded')}}'
            console.log(message, data);
            // Optionally handle success or update image path here
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: message,
            }).then(() => {
                // Refresh the page after success
                // location.reload();
            });
        })
        .catch(error => {
            const message = '{{__('An Error Occurred')}}'
            console.error(message, error);
            Swal.fire({
                icon: 'error',
                title: 'Oops!',
                text: message,
            });
        });
    }
</script>