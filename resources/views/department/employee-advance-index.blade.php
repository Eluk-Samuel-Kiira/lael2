{{-- resources/views/tenant/employee-advances/index.blade.php --}}
<x-app-layout>
    @section('title', __('payments.employee_advance'))
    @section('content')

    <!-- BEGIN: TOOLBAR -->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <!-- Left side - Title and Breadcrumb -->
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{__('payments.advance_table')}}
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
                    <li class="breadcrumb-item text-muted">{{__('payments.advance_index')}}</li>
                </ul>
            </div>

            <!-- Right side - Actions -->
            <div class="d-flex align-items-stretch align-items-sm-center gap-3">
                <!-- <button type="button" class="btn btn-light-primary flex-shrink-0" onclick="exportAdvances()">
                    <i class="ki-duotone ki-file-down fs-2 me-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <span class="d-none d-sm-inline">{{ __('payments.export') }}</span>
                </button> -->

                <!-- New Advance Button -->
                @can('create employee')
                <button type="button" class="btn btn-warning flex-shrink-0" data-bs-toggle="modal" data-bs-target="#kt_modal_add_advance">
                    <i class="ki-duotone ki-hand-holding-usd fs-2 me-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <span class="d-none d-sm-inline">{{ __('payments.new_advance') }}</span>
                    <!-- <span class="d-inline d-sm-none">{{ __('payments.add') }}</span> -->
                </button>
                @endcan

                @include('department.employee-advance.create')
            </div>
        </div>
    </div>
    <!-- END: TOOLBAR -->

    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div class="card">
                    @include('department.employee-advance.component')
                </div>
            </div>
        </div>
    </div> 

    @endsection
</x-app-layout>

