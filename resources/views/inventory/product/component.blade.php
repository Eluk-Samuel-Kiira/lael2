@can('view product')
<div class="card-body py-4" id="reloadProductComponent">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input"
                                type="checkbox"
                                data-kt-check="true"
                                data-kt-check-target="#kt_table_users .row-checkbox"
                                value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('pagination.product_id')}}</th>
                    <th class="min-w-125px">{{__('auth._name')}}</th> 
                    <th class="min-w-125px">{{__('pagination._sku')}}</th> 
                    <th class="min-w-125px">{{__('pagination._category')}}</th> 
                    <th class="min-w-125px">{{__('pagination._type')}}</th>
                    <th class="min-w-125px">{{__('pagination.is_taxable')}}</th> 
                    <th class="min-w-125px">{{__('auth._creater')}}</th> 
                    <th class="min-w-125px">{{__('auth.created_at')}}</th>
                    <th class="min-w-125px">{{__('auth._status')}}</th>
                    <th class="min-w-100px text-end">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($all_products) && $all_products->count() > 0)
                    @foreach ($all_products as $product)
                        <tr data-role="{{ strtolower($product->name) }}">
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input row-checkbox" type="checkbox" value="{{ $product->id }}" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{__('ID-')}}{{ $product->id }}</div>
                            </td>

                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="#" class="symbol symbol-50px">
                                        <img src="{{ productImage($product->image_url) }}" alt="" class="symbol-label">
                                    </a>
                                    <div class="ms-5">
                                        <a href="#" class="text-gray-800 text-hover-primary fs-5 fw-bold" data-kt-ecommerce-product-filter="product_name">{{ $product->name }}</a>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="badge badge-light fw-bold">{{ $product->sku }}</div>
                            </td>
                            <td>{{ $product->category->name }}</td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $product->type }}</div>
                            </td>
                            <td>
                                <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input type="checkbox"
                                        class="form-check-input tax-switch"
                                        onchange="updateProductTaxStatus({{ $product->id }}, this.checked ? 1 : 0)"
                                        {{ $product->is_taxable ? 'checked' : '' }}
                                        @cannot('update product') disabled @endcannot>

                                    <span id="tax-label-{{ $product->id }}"
                                        class="form-check-label ms-2 fw-bold fs-6 text-gray-700">
                                        {{ $product->is_taxable ? __('pagination._yes') : __('pagination._no') }}
                                    </span>
                                </label>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $product->productCreater->name ?? __('pagination._none')}}</div>
                            </td>
                            <td>{{ $product->created_at->format('d M Y, h:i a') }}</td>
                            <td>
                                <select name="status" class="form-select form-select-solid form-select-sm" onchange="updateProductStatus({{ $product->id }}, this.value)"
                                    @cannot('update product') disabled @endcannot>
                                    <option value="1" {{ $product->is_active == 1 ? 'selected' : '' }}>{{__('auth._active')}}</option>
                                    <option value="0" {{ $product->is_active == 0 ? 'selected' : '' }}>{{__('auth._inactive')}}</option>
                                </select>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('update product')
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#updateProductLoc{{$product->id}}">
                                            <i class="bi bi-building me-1 fs-5"></i> <span>{{ __('pagination._allocation') }}</span>
                                        </button>
                                    @endcan
                                    @can('view variant')
                                        <a href="{{ route('products.show', $product->id) }}" 
                                            class="btn btn-sm btn-light btn-active-color-success d-flex align-items-center px-3 py-2">
                                            <i class="bi bi-eye me-1 fs-5"></i> <span>{{ __('pagination._variantions') }}</span>
                                        </a>
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
                                                    data-item-url="{{ route('products.destroy', $product->id) }}" 
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
                                @include('inventory.product.edit')
                                @include('inventory.product.product-dept')
                                
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
@endcan


