<x-app-layout>
    @section('title', __('payments.leave_management'))
    @section('content')

    <!-- BEGIN: TOOLBAR -->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{ __('payments.leave_management') }}
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
                    <li class="breadcrumb-item text-muted">{{__('payments.leave_management')}}</li>
                </ul>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Calendar/List Toggle -->
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-light btn-active-primary active" id="viewCalendarBtn" onclick="toggleView('calendar')">
                        <i class="ki-duotone ki-calendar fs-2 me-2"></i>
                        <span class="d-none d-sm-inline">{{ __('payments.calendar') }}</span>
                    </button>
                    <button type="button" class="btn btn-light btn-active-primary" id="viewListBtn" onclick="toggleView('list')">
                        <i class="ki-duotone ki-table fs-2 me-2"></i>
                        <span class="d-none d-sm-inline">{{ __('payments.list') }}</span>
                    </button>
                </div>

                @can('create leave')
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_leave">
                    <i class="ki-duotone ki-plus fs-2 me-2"></i>
                    {{ __('payments.request_leave') }}
                </button>
                @endcan
                @include('department.leave.create')
            </div>
        </div>
    </div>

    <!-- BEGIN: CONTENT -->
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                @include('department.leave.component')
            </div>
        </div>
    </div>

    @endsection
</x-app-layout>
