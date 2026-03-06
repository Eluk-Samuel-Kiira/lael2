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
                    <li class="breadcrumb-item text-muted">{{__('pagination.product_index')}}</li>
                </ul>
            </div>

            <!-- Right side - Actions -->
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-3 w-100 w-lg-auto">
                <!-- Search Bar -->
                <div class="w-100 w-lg-auto">
                    <div class="input-group input-group-solid">
                        <span class="input-group-text bg-body border-0">
                            <i class="ki-duotone ki-magnifier fs-3 text-gray-500"></i>
                        </span>
                        <input type="text" 
                               id="searchInput" 
                               class="form-control form-control-solid border-0 ps-0" 
                               placeholder="{{__('auth._search')}} {{__('pagination._products')}}"
                               onkeyup="searchTable(this.value, 'kt_table_users')">
                    </div>
                </div>

                <!-- Location Filter -->
                <div class="w-100 w-lg-auto">
                    <select class="form-select form-select-solid fw-bold w-100" id="locationFilter">
                        <option value="">{{ __('pagination._location') }}</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ ucwords(str_replace('_', ' ', $location->name)) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Department Filter -->
                <div class="w-100 w-lg-auto">
                    <select class="form-select form-select-solid fw-bold w-100" id="departmentFilter">
                        <option value="">{{ __('auth._department') }}</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ ucwords($department->name) }}</option>
                        @endforeach
                    </select>
                </div>

                @can('create inventory record')
                <div class="w-100 w-lg-auto">
                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#kt_modal_add_inventory">
                        <i class="ki-duotone ki-plus fs-2 me-2 me-sm-3"></i>
                        <span class="d-none d-sm-inline">{{__('auth._create')}} {{__('pagination.inventory_item')}}</span>
                        <span class="d-inline d-sm-none">{{__('auth._add')}}</span>
                    </button>
                </div>
                @endcan

                <!-- Modal include - KEPT INSIDE the actions div for functionality -->
                @include('store.inventory-items.create')
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                <div class="card">
                    <div class="card">
                        @include('store.inventory-items.component')
                    </div>
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