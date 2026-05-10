<x-app-layout>
    @section('title', __('pagination._inventory_item'))
    @section('content')
    
    @unless(tenant_is_single_shop(auth()->user()->tenant_id))
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
            <!-- Left side - Title and Breadcrumb -->
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                    {{__('pagination.products_table')}}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ url()->previous() }}" class="text-muted text-hover-primary">
                            {{ __('auth._back') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('pagination.product_index')}}</li>
                </ul>
            </div>

            <!-- Right side - Actions -->
            <div class="d-flex flex-wrap align-items-center gap-3">
                <!-- Search Component -->
                <x-liveblade-search 
                    id="inventorySearchInput"
                    componentId="reloadItemComponent"
                    route="{{ route('items.index') }}"
                    placeholder="{{__('auth._search')}} {{__('pagination._products')}}"
                />

                <!-- Reusable Filter Component -->
                <x-liveblade-filter 
                    componentId="reloadItemComponent"
                    route="{{ route('items.index') }}"
                    :filters="[
                        [
                            'name' => 'location_id', 
                            'label' => __('pagination._location'), 
                            'options' => $locations,
                            'searchable' => true 
                        ],
                        [
                            'name' => 'department_id', 
                            'label' => __('auth._department'), 
                            'options' => $departments,
                            'depends_on' => 'location_id',  // ← Only update department filter when location changes
                            'parent_id_field' => 'location_id',  // ← The field in department that links to location
                            'searchable' => true 
                        ]
                    ]"
                />

                @can('create inventory record')
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_inventory">
                    <i class="ki-duotone ki-plus fs-2 me-1"></i>
                    <span class="d-none d-sm-inline">{{__('auth._create')}} {{__('pagination.inventory_item')}}</span>
                    <span class="d-inline d-sm-none">{{__('auth._add')}}</span>
                </button>
                @endcan

                @include('store.inventory-items.create')
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                <div class="card">
                    @include('store.inventory-items.component')
                </div>
            </div>
        </div>
    </div>
    
    @else
    <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle me-2"></i>
        <strong>{{ __('auth.single_shop_plan') }}:</strong>
        {{ __('auth.upgrade_for_multiple_shops') }}
        <a href="/" class="btn btn-sm btn-outline-primary ms-2">
            {{ __('auth.upgrade_plan') }}
        </a>
    </div>
    @endunless
        
    @endsection
</x-app-layout>