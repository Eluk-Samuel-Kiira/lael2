<x-app-layout>
    @section('title', __('pagination.product_variant'))
    @section('content')

    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading text-gray-900 fw-bold fs-2hx my-0">
                    {{ __('pagination._product') }}: {{ $product->name }} — ({{ $product->sku }})
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ url()->previous() }}" class="text-muted text-hover-primary">{{ __('auth._back') }}</a>
                    </li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('pagination.product_variant') }}</li>
                </ul>
            </div>

            <div class="d-flex align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 240px; max-width: 320px;">
                    <div class="d-flex align-items-center position-relative w-100">
                        <input type="text" id="variantSearchInput" class="form-control form-control-solid w-100 pe-12"
                               placeholder="{{ __('auth._search') }}…"
                               value="{{ $initialFilters['search'] ?? '' }}">
                        <i class="bi bi-search fs-3 position-absolute end-0 me-4 top-50 translate-middle-y text-muted"></i>
                    </div>
                </div>

                @can('create variant')
                    <a href="{{ route('variants.show', $product->id) }}" class="btn btn-primary flex-shrink-0">
                        <i class="ki-duotone ki-plus fs-2 me-2"></i>
                        <span class="d-none d-sm-inline">{{ __('pagination.product_variant_new') }}</span>
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                @include('inventory.product-variant.component')
            </div>
        </div>
    </div>

    @include('inventory.product-variant.drawer')

    @endsection

    @push('styles')
    <style>
        .var-sk { display:inline-block; background:linear-gradient(90deg,#f1f1f4 0%,#e9e9ed 50%,#f1f1f4 100%); background-size:200% 100%; animation:var-shimmer 1.4s infinite; border-radius:6px; }
        .var-sk-text { height:12px; margin:4px 0; display:block; }
        @keyframes var-shimmer { 0%{background-position:200% 0;} 100%{background-position:-200% 0;} }
        .var-row { cursor:pointer; }
        .var-row:hover { background-color:#f9f9f9 !important; }
    </style>
    
    @endpush

    @push('scripts')
        <script>
            window.VARIANT_ROUTES = {
                data:          '{{ route('variants.data') }}',
                show:          '{{ route('variants.drawer-data', ['variant' => '__ID__']) }}',
                status:        '{{ route('variant.status',       ['id' => '__ID__']) }}',
                taxable:       '{{ route('product.taxable',      ['id' => '__ID__']) }}',
                update:        '{{ route('variants.update',      ['variant' => '__ID__']) }}',
                destroy:       '{{ route('variants.destroy',     ['variant' => '__ID__']) }}',
                upload_image:  '{{ route('variant.upload_image') }}',
            };
            window.VARIANT_BOOTSTRAP = {
                productId: {{ $product->id }},
                isSingleShop: {{ tenant_is_single_shop(auth()->user()->tenant_id) ? 'true' : 'false' }},
            };
            window.VARIANT_CURRENCY = '{{ currency_symbol() }}';
        </script>
        @include('inventory.product-variant.list-scripts')
        @include('inventory.product-variant.drawer-scripts')
    @endpush
</x-app-layout>