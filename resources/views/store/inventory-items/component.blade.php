<div class="card-body py-4" id="reloadItemComponent">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_users .form-check-input" value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('pagination._sku')}}</th>
                    <th class="min-w-125px">{{__('auth._name')}}</th> 
                    <th class="min-w-125px">{{__('auth._department')}}</th> 
                    <th class="min-w-125px">{{__('pagination._location')}}</th>  
                    <th class="min-w-125px">{{__('pagination.batch_number')}}</th> 
                    <th class="min-w-125px">{{__('pagination.quantity_on_hand')}}</th>
                    <th class="min-w-125px">{{__('pagination.quantity_allocated')}}</th> 
                    <th class="min-w-125px">{{__('pagination.quantity_on_order')}}</th>
                    {{-- <th class="min-w-125px">{{__('pagination.reorder_point')}}</th> --}}
                    <th class="min-w-125px">{{__('pagination.preferred_stock_level')}}</th>
                    <th class="min-w-125px">{{__('pagination.expiry_date')}}</th> 
                    <th class="min-w-125px">{{__('auth._creater')}}</th> 
                    <th class="min-w-125px">{{__('auth.created_at')}}</th>
                    <th class="min-w-100px text-end">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($items) && $items->count() > 0)
                    @foreach ($items as $item)
                        <tr data-role="{{ strtolower($item->name) }}" 
                            data-department="{{ $item->department_id }}" 
                            data-location="{{ $item->location_id }}">

                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{__('ID-')}}{{ $item->variant->sku ?? __('pagination._none')}}</div>
                            </td>

                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="#" class="symbol symbol-50px">
                                        <img src="{{ productImage($item->variant->image_url ?? '') }}" alt="" class="symbol-label">
                                    </a>
                                    <div class="ms-5">
                                        <a href="#" class="text-gray-800 text-hover-primary fs-5 fw-bold" data-kt-ecommerce-product-filter="product_name">{{ $item->variant->name ?? __('pagination._none')}}</a>
                                    </div>
                                </div>
                            </td>
                            
                            <td>
                                <div class="badge badge-light fw-bold">{{ ucwords(str_replace('_', ' ', $item->departmentItem->name)) ?? __('pagination._none') }}</div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $item->itemLocation->name ?? __('pagination._none')}}</div>
                            </td>
                            <td class="fw-bold text-primary ms-3">{{ $item->batch_number ?? __('pagination._none')}}</td>
                            <td class="fw-bold text-success ms-3">{{ $item->quantity_on_hand ?? __('pagination._none')}}</td>
                            <td class="fw-bold text-warning ms-3">{{ $item->quantity_allocated ?? __('pagination._none')}}</td>
                            <td>{{ $item->quantity_on_order ?? __('pagination._none')}}</td>
                            {{-- <td> <div class="badge badge-light fw-bold">{{ $item->reorder_point ?? __('pagination._none')}} </div></td> --}}
                            <td class="fw-bold text-primary ms-3">{{ $item->preferred_stock_level ?? __('pagination._none')}}</td>
                            <td>{{ $item->expiry_date ?? __('pagination._none') }}</td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $item->itemCreater->name ?? __('pagination._none')}}</div>
                            </td>
                            <td>{{ $item->created_at->format('d M Y, h:i a') ?? __('pagination._none') }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('update stock levels')
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#editItem{{$item->id}}">
                                            <i class="bi bi-pencil-square me-1 fs-5"></i> <span>{{ __('auth._update') }}</span>
                                        </button>
                                    @endcan
                                    {{--
                                    @can('delete inventory')
                                        <button type="button" 
                                        class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2" 
                                        data-bs-toggle="modal" 
                                            data-bs-target="#deleteItemModal{{$item->id}}">
                                            <i class="bi bi-trash me-1 fs-5"></i> <span>{{ __('auth._delete') }}</span>                                
                                        </button>
                                    @endcan
                                    --}} 
                                </div>


                                <!-- Delete User Modal -->
                                <div class="modal fade" id="deleteItemModal{{$item->id}}" tabindex="-1" aria-hidden="true">
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
                                                <button type="button" id="closeDeleteModal{{$item->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                                                <!-- Confirm Button -->
                                                <button type="button" id="deleteButton{{$item->id}}" class="btn btn-danger" 
                                                    data-item-url="{{ route('items.destroy', $item->id) }}" 
                                                    data-item-id="{{ $item->id }}"
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

                                @include('store.inventory-items.edit')
                                
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>



