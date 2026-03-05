<x-app-layout>
    @section('title', __('pagination.locations_index'))
    @section('content')
        
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <!-- Left side - Title and Breadcrumb -->
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{__('pagination.locations_table')}}
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
                    <li class="breadcrumb-item text-muted">{{__('pagination._location')}}</li>
                </ul>
            </div>

            <!-- Right side - Actions -->
            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                <!-- Search Bar -->
                <div class="w-100 w-sm-250px">
                    <div class="input-group input-group-solid">
                        <span class="input-group-text bg-body border-0">
                            <i class="ki-duotone ki-magnifier fs-3 text-gray-500"></i>
                        </span>
                        <input type="text" 
                               id="searchInput" 
                               class="form-control form-control-solid border-0 ps-0" 
                               placeholder="{{__('auth._search')}} {{__('pagination._location')}}"
                               onkeyup="searchTable(this.value, 'kt_table_users')">
                    </div>
                </div>

                @can('create location')
                    <button type="button" class="btn btn-primary flex-shrink-0" data-bs-toggle="modal" data-bs-target="#kt_modal_add_location">
                        <i class="ki-duotone ki-plus fs-2 me-2 me-sm-3"></i>
                        <span class="d-none d-sm-inline">{{__('auth._add')}} {{__('pagination.locations_new')}}</span>
                        <span class="d-inline d-sm-none">{{__('auth._add')}}</span>
                    </button>
                @endcan

                @include('unit-of-measure.location.create')
            </div>
        </div>
    </div>

    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                
                <!--begin::Content-->
                <div class="card">
                    @include('unit-of-measure.location.component')
                </div>
                
            </div>
        </div>
    </div>
    
    @endsection
</x-app-layout>