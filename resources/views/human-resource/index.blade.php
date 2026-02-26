<x-app-layout>
    @section('title', __('auth._users_index'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('auth._users_table')}}</h1>
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
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <!-- Search Bar -->
                <div class="px-7 py-5">
                    <input type="text" id="searchInput" class="form-control" placeholder="{{__('auth._search')}} {{__('auth._users')}}"
                        onkeyup="searchTable(this.value, 'kt_table_users')">
                </div>
                @can('create user')
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_employee">
                    <i class="ki-duotone ki-plus fs-2"></i>{{__('auth.new_user')}}</button>
                @endcan

                @include('human-resource.partial.create-user')    
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                <div class="card">
                    @include('human-resource.partial.user-componenet')
                </div>
            </div>
        </div>
    </div>
 
        
    @endsection
</x-app-layout>