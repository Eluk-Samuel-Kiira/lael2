<x-app-layout>
    @section('title', __('pagination.pos_index'))
    @section('content')
        
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('pagination._pos')}}</h1>
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
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2 gap-lg-3">
                
                <div class="px-3 px-md-7 py-3 py-md-5 w-100 w-md-auto">
                    <h3 class="card-title fw-bold text-gray-800 fs-2qx">{{ currency_code() }} ({{ currency_symbol() }})</h3>
                </div>

                <!-- Search Bar -->
                <div class="px-3 px-md-7 py-3 py-md-5 w-100 w-md-auto">
                    <input type="text" id="variantSearchInput" class="form-control" 
                        placeholder="{{__('auth._search')}} {{__('pagination._variants')}}"
                        onkeyup="filterProductsAndVariants(this.value)">
                </div>

                <!-- Department Filter -->
                @if(!tenant_is_single_shop(auth()->user()->tenant_id))
                <select class="form-select form-select-solid fw-bold w-100 w-md-auto" id="departmentFilter">
                    <option value="">{{ __('auth._department') }}</option>
                    @foreach ($user_departments as $department)
                        <option value="{{ $department->id }}">{{ ucwords($department->name) }}</option>
                    @endforeach
                </select>
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
