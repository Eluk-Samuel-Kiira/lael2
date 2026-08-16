{{-- resources/views/manufacturing/production-index.blade.php --}}
<x-app-layout>
    @section('title', __('passwords.production_orders'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{__('passwords.production_orders')}}
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
                    <li class="breadcrumb-item text-muted">{{__('passwords.production_orders')}}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                <div class="w-100 w-sm-250px">
                    <x-liveblade-search 
                        id="productionOrderSearchInput"
                        componentId="reloadProductionComponent"
                        route="{{ route('production-orders.index') }}"
                        placeholder="{{__('auth._search')}} {{__('passwords.production_orders')}}"
                    />
                </div>

                @can('create production_orders')
                <button type="button" class="btn btn-primary flex-shrink-0" data-bs-toggle="modal" data-bs-target="#kt_modal_add_production_order">
                    <i class="ki-duotone ki-plus fs-2 me-2 me-sm-3"></i>
                    <span class="d-none d-sm-inline">{{__('passwords.new_production_order')}}</span>
                    <span class="d-inline d-sm-none">{{__('auth._add')}}</span>
                </button>
                @endcan

                @include('manufacturing.production-order.create')
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                <div class="card">
                    @include('manufacturing.production-order.component')
                </div>
            </div>
        </div>
    </div>
 
    @endsection
</x-app-layout>