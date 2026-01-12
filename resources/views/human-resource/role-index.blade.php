<x-app-layout>
    @section('title', __('auth.role_index'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('auth.role_table')}} - ( {{$roles->count()}} )</h1>
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
                    <li class="breadcrumb-item text-muted">{{__('auth.role_table')}}</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-3">
                <!-- Search Bar -->
                <input type="text" id="searchInput" class="form-control w-auto" 
                    placeholder="{{ __('auth._search') }} {{ __('auth._role') }}"
                    id="roleSearchBar">

                <!-- Role Filter -->
                <select class="form-select form-select-solid fw-bold w-auto" id="roleFilter">
                    <option value="">{{ __('auth.reset_role') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}">{{ ucwords(str_replace('_', ' ', $role->name)) }}</option>
                    @endforeach
                </select>

                <!-- New Role Button -->
                @can('create role')
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_role">
                        <i class="ki-duotone ki-plus fs-2"></i> {{ __('auth.new_role') }}
                    </button>
                @endcan

                @include('human-resource.role.create-role')    
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                @include('human-resource.role.role-component')
            </div>
        </div>
    </div>


        
    @endsection
</x-app-layout>