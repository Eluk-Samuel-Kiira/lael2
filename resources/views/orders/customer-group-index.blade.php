<x-app-layout>
    @section('title', __('passwords.customer_group_index'))
    @section('content')

    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{ __('passwords.customer_group_table') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ url()->previous() }}" class="text-muted text-hover-primary">
                            {{ __('auth._back') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{ __('passwords.customer_group_table') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                <div class="w-100 w-sm-250px">
                    <x-liveblade-search
                        id="customerGroupSearchInput"
                        componentId="reloadCustomerGroupComponent"
                        route="{{ route('customer-group.index') }}"
                        placeholder="{{ __('auth._search') }} {{ __('passwords.customer_group') }}"
                    />
                </div>

                @can('create customer-group')
                <button type="button" class="btn btn-primary flex-shrink-0"
                        data-bs-toggle="modal"
                        data-bs-target="#kt_modal_add_customer_group">
                    <i class="ki-duotone ki-plus fs-2 me-2 me-sm-3"></i>
                    <span class="d-none d-sm-inline">{{ __('passwords.customer_group_new') }}</span>
                    <span class="d-inline d-sm-none">{{ __('auth._add') }}</span>
                </button>
                @endcan

                @include('orders.customer-group.create-customer-group')
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                <div class="card">
                    @include('orders.customer-group.customer-group-component')
                </div>
            </div>
        </div>
    </div>

    @endsection
</x-app-layout>