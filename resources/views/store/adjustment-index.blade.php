<x-app-layout>
    @section('title', __('passwords.inv_adjustments'))
    @section('content')

    @unless(tenant_is_single_shop(auth()->user()->tenant_id))
    
        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
            <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                <!-- Left side - Title and Breadcrumb -->
                <div class="page-title d-flex flex-column">
                    <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                        {{__('passwords.inv_adjustments')}}
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
                        <li class="breadcrumb-item text-muted">{{__('passwords.inv_adjustments')}}</li>
                    </ul>
                </div>

                <!-- Right side - Actions with Search and Filters -->
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <!-- Search Component -->
                    <x-liveblade-search 
                        id="stockSearchInput"
                        componentId="reloadStockComponent"
                        route="{{ route('stocks.index') }}"
                        placeholder="{{__('auth._search')}} {{__('pagination._products')}}"
                    />

                    <!-- Reusable Filter Component -->
                    <x-liveblade-filter 
                        componentId="reloadStockComponent"
                        route="{{ route('stocks.index') }}"
                        :filters="[
                            [
                                'name' => 'location_id', 
                                'label' => __('pagination._location'), 
                                'options' => $locations,
                                'value_key' => 'id',
                                'label_key' => 'name'
                            ],
                            [
                                'name' => 'department_id', 
                                'label' => __('auth._department'), 
                                'options' => $departments,
                                'value_key' => 'id',
                                'label_key' => 'name'
                            ]
                        ]"
                    />
                </div>
            </div>
        </div>
        
        <div class="d-flex flex-column flex-column-fluid">
            <div id="kt_app_content" class="app-content flex-column-fluid">
                <div id="kt_app_content_container" class="app-container container-xxl">
                    <div id="status"></div>
                    <div class="card">
                        @include('store.inventory-adjustment.component')
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
