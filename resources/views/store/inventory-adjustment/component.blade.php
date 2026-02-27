@can('update stock levels')
<div class="card-body py-4" id="reloadStockComponent">
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
                    <th class="min-w-125px">{{__('passwords.overall_quantity_at_hand')}}</th>   
                    <th class="min-w-125px">{{__('pagination.quantity_allocated')}}</th>
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
                                <div class="badge badge-light fw-bold">{{__('payments._id')}}{{ $item->variant->sku ?? __('pagination._none')}}</div>
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
                            <td class="fw-bold text-success ms-3">{{ $item->variant->overal_quantity_at_hand ?? __('pagination._none')}}</td>
                            <td class="fw-bold text-warning ms-3">{{ $item->quantity_allocated ?? __('pagination._none')}}</td>

                            <td>
                                <div class="d-flex gap-2">
                                    @can('update stock levels')
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#editItem{{$item->id}}">
                                            <i class="bi bi-dash-square me-1 fs-5"></i> <span>{{ __('passwords._adjust') }}</span>
                                        </button>
                                    @endcan
                                    @can('transfer stock')
                                    <button 
                                        class="btn btn-sm btn-light btn-active-color-success d-flex align-items-center px-3 py-2" 
                                        data-bs-toggle="modal"
                                        data-bs-target="#stockTransfer{{$item->id}}">
                                        <i class="bi bi-arrow-left-right me-1 fs-5"></i> <span>{{ __('passwords._transfer') }}</span>
                                    </button>
                                    @endcan
                                </div>
                                @include('store.inventory-adjustment.adjust-stock')
                                @include('store.inventory-adjustment.transfer')
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
@endcan
