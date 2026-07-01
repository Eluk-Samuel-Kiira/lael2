<x-app-layout>
    @section('title', __('pagination.employee_index'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <!-- Left side - Title and Breadcrumb -->
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{__('pagination.employee_table')}}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        @php
                            $previousUrl = url()->previous();
                            $previousRouteName = optional(app('router')->getRoutes()->match(request()->create($previousUrl)))->getName();
                            $formattedRouteName = $previousRouteName 
                                ? Str::of($previousRouteName)->replace('.', ' ')->title() 
                                : __('pagination.back');
                        @endphp
                        <a href="{{ $previousUrl }}" class="text-muted text-hover-primary">
                            {{ $formattedRouteName }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('pagination.employee_index')}}</li>
                </ul>
            </div>

            <!-- Right side - Actions -->
            <div class="d-flex align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                <!-- Search Bar -->
                <div class="w-100 w-sm-250px">
                    <x-liveblade-search 
                        id="employeeSearchInput"
                        componentId="employeeUserIndexTable"
                        route="{{ route('user.index') }}"
                        placeholder="{{__('auth._search')}} {{__('pagination.employee')}}"
                    />
                </div>

                @can('edit employee')
                    <button type="button" 
                            class="btn btn-sm btn-light-primary flex-shrink-0" 
                            onclick="syncUserToEmployee()">
                        <i class="ki-duotone ki-arrows-circle fs-5 me-1"></i>
                        <span class="d-none d-sm-inline">{{ __('auth.sync_users') }}</span>
                        <span class="d-inline d-sm-none">{{ __('auth.sync') }}</span>
                    </button>
                @endcan
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                <div class="card">
                    @include('department.employee.component')
                </div>
            </div>
        </div>
    </div>  
        
    @endsection
</x-app-layout>