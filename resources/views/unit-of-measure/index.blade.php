<x-app-layout>
    @section('title', __('auth._uoms'))
    @section('content')
        
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-wrap flex-lg-nowrap gap-4 gap-lg-0">
            <!-- Left side - Title and Breadcrumb -->
            <div class="page-title d-flex flex-column justify-content-center me-0 me-lg-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2 fs-sm-1 flex-column justify-content-center my-0">
                    {{__('auth._uoms')}}
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
                    <li class="breadcrumb-item text-muted">{{__('auth._uom')}}</li>
                </ul>
            </div>

            <!-- Right side - Actions -->
            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 gap-sm-2 ms-auto w-100 w-lg-auto">
                <!-- Search Bar -->
                <div class="w-100 w-sm-200px">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="ki-duotone ki-magnifier fs-3 text-gray-500"></i>
                        </span>
                        <input type="text" 
                               id="searchInput" 
                               class="form-control border-start-0 ps-0" 
                               placeholder="{{__('auth._search')}} {{__('auth._uom')}}"
                               onkeyup="searchTable(this.value, 'kt_table_users')">
                    </div>
                </div>

                <!-- Add Button -->
                @can('create uom')
                    <button type="button" class="btn btn-sm btn-primary flex-shrink-0" data-bs-toggle="modal" data-bs-target="#kt_modal_add_department">
                        <i class="ki-duotone ki-plus fs-2 me-1"></i>
                        <span class="d-none d-sm-inline">{{__('auth._add')}} {{__('auth._uom')}}</span>
                        <span class="d-inline d-sm-none">{{__('auth._add')}}</span>
                    </button>
                @endcan
                @include('unit-of-measure.partials.create')
                
            </div>
        </div>
    </div>

    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                
                <!--begin::Content-->
                <div class="card">
                    @include('unit-of-measure.partials.uom-component')
                </div>
                
            </div>
        </div>
    </div>
    
    @endsection
</x-app-layout>