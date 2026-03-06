<x-app-layout>
    @section('title', __('pagination.pos_index'))
    @section('content')
        
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
            <!-- Left side - Title and Breadcrumb -->
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                    {{__('pagination._pos')}}
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
                    <li class="breadcrumb-item text-muted">{{__('pagination.pos_index')}}</li>
                </ul>
            </div>

            <!-- Right side - Actions -->
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-3 w-100 w-lg-auto">
                
                <!-- Currency Display -->
                <div class="px-3 px-lg-5 py-3 py-lg-5 bg-light rounded-3">
                    <h3 class="fw-bold text-gray-800 fs-2qx mb-0">{{ currency_code() }} ({{ currency_symbol() }})</h3>
                </div>

                <!-- Search Bar -->
                <div class="w-100 w-lg-250px">
                    <div class="input-group input-group-solid">
                        <span class="input-group-text bg-body border-0">
                            <i class="ki-duotone ki-magnifier fs-3 text-gray-500"></i>
                        </span>
                        <input type="text" 
                               id="variantSearchInput" 
                               class="form-control form-control-solid border-0 ps-0" 
                               placeholder="{{__('auth._search')}} {{__('pagination._variants')}}"
                               onkeyup="filterProductsAndVariants(this.value)">
                    </div>
                </div>

                <!-- Department Filter -->
                @if(!tenant_is_single_shop(auth()->user()->tenant_id))
                <div class="w-100 w-lg-auto">
                    <select class="form-select form-select-solid fw-bold w-100" id="departmentFilter">
                        <option value="">{{ __('auth._department') }}</option>
                        @foreach ($user_departments as $department)
                            <option value="{{ $department->id }}">{{ ucwords($department->name) }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                
                <!--begin::Content-->
                @include('orders.pos.component')
                <!--end::Content-->
                
            </div>
        </div>
    </div>
    
    @endsection
</x-app-layout>