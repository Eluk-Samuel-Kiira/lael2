<x-app-layout>
    @section('title', __('auth._users'))
    @section('content')


    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <!-- Left side - Title and Breadcrumb -->
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{__('auth._users_table')}}
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
                    <li class="breadcrumb-item text-muted">{{__('auth._users_table')}}</li>
                </ul>
            </div>
                
            <div class="d-flex gap-3">
                {{-- Reusable search component --}}
                <div class="w-100 w-sm-250px">
                    <x-liveblade-search 
                        id="employeeSearchInput"
                        componentId="reloadEmployeeComponent"
                        route="{{ route('employee.index') }}"
                        placeholder="{{__('auth._search')}} {{__('auth._users')}}"
                    /> 
                </div>
                
                @can('create user')
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_employee">
                    <i class="ki-duotone ki-plus"></i> {{__('auth.new_user')}}
                </button>
                @endcan
            </div>
        </div>
    </div>

    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                <!--begin::Content-->
                <div class="card">
                    @include('human-resource.partial.user-componenet')
                </div>
            </div>
        </div>
    </div>
    
    @endsection
</x-app-layout>