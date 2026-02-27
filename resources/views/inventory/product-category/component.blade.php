@can('view subcategory')
<div class="card-body py-4" id="reloadProductCategoryComponent">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_users .form-check-input" value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('pagination.category_id')}}</th>
                    <th class="min-w-125px">{{__('pagination.parent_category')}}</th> 
                    <th class="min-w-125px">{{__('auth._name')}}</th> 
                    <th class="min-w-125px">{{__('auth._creater')}}</th> 
                    <th class="min-w-125px">{{__('auth.created_at')}}</th>
                    <th class="min-w-125px">{{__('auth._status')}}</th>
                    <th class="min-w-100px text-end">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($products_categories) && $products_categories->count() > 0)
                    @foreach ($products_categories as $category)
                        <tr data-role="{{ strtolower($category->name) }}">
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{__('ID-')}}{{ $category->id }}</div>
                            </td>
                            <td>{{ $category->parentCategory->name ?? __('pagination._none')}}</td>
                            <td class="d-flex align-items-center">
                                <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                    <a href="#">
                                        <div class="symbol-label">
                                            <img src="{{ productCategoryImage($category->image_url) }}" alt="{{ $category->name }}" class="w-100" />
                                        </div>
                                    </a>
                                </div>
                                <div class="d-flex flex-column">
                                    <a href="#" class="text-gray-800 text-hover-primary mb-1">{{ $category->name }}</a>
                                    <!-- <span>{{ $category->name }}</span>  -->
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $category->productCategoryCreater->name ?? __('pagination._none')}}</div>
                            </td>
                            <td>{{ $category->created_at->format('d M Y, h:i a') }}</td>
                            <td>
                                @if($category->is_active == 1)
                                    {{ __('auth._active') }}
                                @elseif($category->is_active == 0)
                                    {{ __('auth._inactive') }}
                                @else
                                    {{ __('pagination._unknown') }}
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('edit subcategory')
                                        <a data-link href="javascript:void(0);" 
                                        onclick="reloadToApp('{{ route('product-category.edit', $category->id) }}')"
                                        class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2">
                                        <i class="bi bi-pencil-square me-1 fs-5"></i> 
                                        <span>{{ __('auth._edit') }}</span>
                                        </a>
                                    @endcan

                                    @can('delete subcategory')
                                        <button type="button" 
                                            class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deletecategoryModal{{$category->id}}">
                                            <i class="bi bi-trash me-1 fs-5"></i> 
                                            <span>{{ __('auth._delete') }}</span>
                                        </button>
                                    @endcan
                                </div>


                                <!-- Delete User Modal -->
                                <div class="modal fade" id="deletecategoryModal{{$category->id}}" tabindex="-1" aria-hidden="true">
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
                                                <button type="button" id="closeDeleteModal{{$category->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                                                <!-- Confirm Button -->
                                                <button type="button" id="deleteButton{{$category->id}}" class="btn btn-danger" 
                                                    data-item-url="{{ route('product-category.destroy', $category->id) }}" 
                                                    data-item-id="{{ $category->id }}"
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
                                @include('inventory.category.edit-category')
                                
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
@endcan

