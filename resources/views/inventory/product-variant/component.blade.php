<div class="card card-flush">
    <div class="card-header align-items-center py-5">
        <h3 class="card-title fw-bold">{{ __('pagination._variations_of') }} {{ $product->name }}</h3>
        <div class="card-toolbar">
            <select id="variantPerPage" class="form-select form-select-solid form-select-sm w-75px">
                @foreach([15, 25, 50, 100] as $pp)
                    <option value="{{ $pp }}">{{ $pp }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="card-body pt-0">
        <div id="varLoadingSpinner" class="d-none">
            @for($i = 0; $i < 6; $i++)
                <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                    <div class="var-sk var-sk-text" style="width:18px;"></div>
                    <div class="var-sk var-sk-text" style="width:140px;"></div>
                    <div class="flex-grow-1"><div class="var-sk var-sk-text" style="width:180px;"></div></div>
                </div>
            @endfor
        </div>
        <div id="varTableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed table-row-gray-300 fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-150px">{{ __('auth._name') }}</th>
                            <th class="min-w-100px">{{ __('pagination._sku') }}</th>
                            <th class="min-w-100px">{{ __('pagination._barcode') }}</th>
                            <th class="min-w-100px text-end">{{ __('pagination.overall_quantity') }}</th>
                            <th class="min-w-100px text-end">{{ __('pagination.selling_price') }}</th>
                            <th class="min-w-100px text-end">{{ __('pagination.grand_total_cost_price') }}</th>
                            <th class="min-w-100px text-end">{{ __('pagination.discount_selling_price') }}</th>
                            <th class="min-w-100px text-center">{{ __('pagination.is_taxable') }}</th>
                            <th class="min-w-100px">{{ __('auth._status') }}</th>
                        </tr>
                    </thead>
                    <tbody id="varTableBody"></tbody>
                </table>
            </div>
            <div id="varPaginationContainer" class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 d-none">
                <div id="varPaginationInfo" class="text-muted fs-7"></div>
                <nav><ul class="pagination pagination-outline m-0" id="varPagination"></ul></nav>
            </div>
        </div>
        <div id="varNoDataMessage" class="text-center py-15 d-none">
            <i class="bi bi-file-earmark fs-5tx text-muted mb-4 d-block"></i>
            <h4 class="fw-bold text-gray-800 mb-2">{{ __('pagination.no_variants_found') }}</h4>
            <button type="button" class="btn btn-sm btn-primary" onclick="window.VariantList?.resetFilters()">
                {{ __('pagination.reset_filters') }}
            </button>
        </div>
    </div>
</div>

@include('inventory.product-variant.serials-modal')
@include('inventory.product-variant.recipe-ingredients-modal')