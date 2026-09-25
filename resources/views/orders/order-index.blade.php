<x-app-layout>
    @section('title', __('passwords.order_index'))
    @section('content')
        
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <!-- Left side - Title and Breadcrumb -->
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{__('passwords.order_table')}}
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
                    <li class="breadcrumb-item text-muted">{{__('passwords.orders')}}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">

                <!-- Search -->
                <div class="w-100 w-sm-250px">
                    <x-liveblade-search
                        id="orderSearchInput"
                        componentId="ordersIndexTable"
                        route="{{ route('orders.index') }}"
                        placeholder="{{ __('auth._search') }} {{ __('passwords.orders') }}"
                    />
                </div>

                <!-- Location filter -->
                @if(isset($locations) && $locations->count() > 0)
                    <div class="w-100 w-sm-200px">
                        <select id="orderLocationFilter"
                                class="form-select form-select-solid"
                                data-control="select2"
                                data-placeholder="{{ __('passwords.all_locations') }}"
                                data-allow-clear="true"
                                onchange="filterOrdersByLocation(this.value)">
                            <option value="">{{ __('passwords.all_locations') }}</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}"
                                        @selected((int) request('location_id') === (int) $loc->id)>
                                    {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    @if (tenant_can('pos'))
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                
                <!--begin::Content-->
                @include('orders.order.component')
                <!--end::Content-->
                
            </div>
        </div>
    </div>
    <script>
        function filterOrdersByLocation(locationId) {
            const url = new URL(window.location.href);

            if (locationId) {
                url.searchParams.set('location_id', locationId);
            } else {
                url.searchParams.delete('location_id');
            }

            url.searchParams.delete('page');

            const searchInput = document.getElementById('orderSearchInput');
            if (searchInput && searchInput.value) {
                url.searchParams.set('search', searchInput.value);
            } else {
                url.searchParams.delete('search');
            }

            window.location.href = url.toString();
        }
    </script>
    @endif
    
    @endsection
</x-app-layout>