@can('view inventory')
<div class="card-body py-4" id="reloadItemComponent">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5">
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
                    <!-- <th class="min-w-125px">{{__('pagination.batch_number')}}</th>  -->
                    <th class="min-w-125px">{{__('pagination.overall_quantity')}}</th>
                    <th class="min-w-125px">{{__('pagination.quantity_allocated')}}</th> 
                    <!-- <th class="min-w-125px">{{__('pagination.quantity_on_order')}}</th> -->
                    <th class="min-w-125px">{{__('pagination.preferred_stock_level')}}</th>
                    <th class="min-w-125px">{{__('pagination.expiry_date')}}</th> 
                    <th class="min-w-125px">{{__('auth._creater')}}</th> 
                    <th class="min-w-125px">{{__('auth.created_at')}}</th>
                    <th class="min-w-100px text-end">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @forelse ($items as $item)
                    <tr data-role="{{ strtolower($item->name ?? '') }}" 
                        data-department="{{ $item->department_id }}" 
                        data-location="{{ $item->location_id }}">
                        
                        <td class="w-25px">
                            <div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="1" />
                            </div>
                        </td>
                        
                        <td>
                            <div class="badge badge-light fw-bold">{{ $item->variant->sku ?? __('pagination._none') }}</div>
                        </td>
                        
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-40px me-3">
                                    <img src="{{ productImage($item->variant->image_url ?? '') }}" alt="" class="symbol-label">
                                </div>
                                <div>
                                    <span class="text-gray-800 text-hover-primary fw-bold">{{ $item->variant->name ?? __('pagination._none') }}</span>
                                </div>
                            </div>
                        </td>
                        
                        <td>
                            <div class="badge badge-light fw-bold">{{ ucwords(str_replace('_', ' ', $item->departmentItem->name ?? '')) }}</div>
                        </td>
                        
                        <td>
                            <div class="badge badge-light fw-bold">{{ $item->itemLocation->name ?? __('pagination._none') }}</div>
                        </td>
                        
                        <!-- <td>
                            <span class="badge badge-light-info">{{ $item->batch_number ?? __('pagination._none') }}</span>
                        </td> -->
                        
                        <td>
                            {{-- Pulled from product_variants.overal_quantity_at_hand (via the $item->variant
                                 relation) rather than inventory_items.quantity_on_hand — the variant row is
                                 the source of truth for stock on hand. --}}
                            <span class="badge badge-light-success">{{ $item->variant->overal_quantity_at_hand ?? 0 }}</span>
                        </td>
                        
                        <td>
                            <span class="badge badge-light-warning">{{ $item->quantity_allocated ?? 0 }}</span>
                        </td>
                        
                        <!-- <td>
                            {{ $item->quantity_on_order ?? 0 }}
                        </td> -->
                        
                        <td>
                            <span class="badge badge-light-primary">{{ $item->preferred_stock_level ?? __('pagination._none') }}</span>
                        </td>
                        
                        <td>
                            {{ $item->expiry_date ?? __('pagination._none') }}
                        </td>
                        
                        <td>
                            <div class="badge badge-light fw-bold">{{ $item->itemCreater->name ?? __('pagination._none') }}</div>
                        </td>
                        
                        <td>
                            {{ $item->created_at ? $item->created_at->format('d M Y, h:i a') : __('pagination._none') }}
                        </td>
                        
                        <td class="text-end">
                            <div class="d-flex gap-2 justify-content-end">
                                @can('update stock levels')
                                    <button class="btn btn-sm btn-light btn-active-color-primary" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#editItem{{$item->id}}"
                                            title="{{ __('auth._update') }}">
                                        <i class="bi bi-pencil-square fs-5"></i><span>{{ __('auth._edit') }}</span>
                                    </button>
                                @endcan
                            </div>
                            @include('store.inventory-items.edit')
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-box-seam fs-2"></i>
                                <p class="mt-2">{{ __('pagination.no_items_found') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="mt-4">
        <x-liveblade-pagination
            :paginator="$items"
            id="inventoryPagination"
            route="{{ route('items.index') }}"
            search-input-id="inventorySearchInput"
            :show-info="true"
            :show-per-page="true"
            :per-page-options="[15, 25, 50, 100]"
            data-lb-component="reloadItemComponent"
        />
    </div>
</div>
@endcan