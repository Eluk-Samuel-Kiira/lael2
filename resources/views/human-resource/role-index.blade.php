<x-app-layout>
    @section('title', __('auth.role_index'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
            <!-- Left side - Title and Breadcrumb -->
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                    {{__('auth.role_table')}} - ( {{$roles->count()}} )
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
                    <li class="breadcrumb-item text-muted">{{__('auth.role_table')}}</li>
                </ul>
            </div>

            <!-- Right side - Actions -->
            <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-3 w-100 w-lg-auto">
                <!-- Search Bar -->
                <div class="w-100 w-md-auto">
                    <div class="input-group input-group-solid">
                        <span class="input-group-text bg-body border-0">
                            <i class="ki-duotone ki-magnifier fs-3 text-gray-500"></i>
                        </span>
                        <input type="text" 
                               id="searchInput" 
                               class="form-control form-control-solid border-0 ps-0" 
                               placeholder="{{ __('auth._search') }} {{ __('auth._role') }}"
                               id="roleSearchBar">
                    </div>
                </div>

                <!-- Role Filter -->
                <div class="w-100 w-md-auto">
                    <select class="form-select form-select-solid fw-bold w-100" id="roleFilter">
                        <option value="">{{ __('auth.reset_role') }}</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}">{{ ucwords(str_replace('_', ' ', $role->name)) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- New Role Button -->
                @can('create role')
                <div class="w-100 w-md-auto">
                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#kt_modal_add_role">
                        <i class="ki-duotone ki-plus fs-2 me-2"></i>
                        <span class="d-none d-sm-inline">{{ __('auth.new_role') }}</span>
                        <span class="d-inline d-sm-none">{{ __('auth._add') }}</span>
                    </button>
                </div>
                @endcan

                <!-- Modal include - KEPT INSIDE the actions div for functionality -->
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