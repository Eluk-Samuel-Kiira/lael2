{{--
    Bulk allocate products to departments in one submission.
    Assumes $products, $departments and $locations are available in scope.
--}}
<div class="modal fade" id="kt_modal_bulk_allocate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-1000px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    <i class="ki-duotone ki-building fs-2 me-2 text-primary">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    {{ __('pagination.bulk_allocate_products') }}
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            <div class="modal-body px-5 my-5">
                <div class="alert alert-light-info d-flex align-items-center mb-6">
                    <i class="bi bi-info-circle fs-2 me-3"></i>
                    <div>
                        {{ __('pagination.bulk_allocate_info') }}
                    </div>
                </div>

                <form id="kt_modal_bulk_allocate_form" action="{{ route('products.bulk-allocate') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-6">

                        {{-- ── PRODUCTS ─────────────────────────────────────────── --}}
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="fs-6 fw-semibold">{{ __('pagination._products') }}</label>
                                <div class="d-flex gap-3 align-items-center">
                                    <span id="selectedProductsCount" class="badge badge-primary">0</span>
                                    <div class="form-check form-check-sm form-check-custom">
                                        <input class="form-check-input" type="checkbox" id="select_all_products">
                                        <label class="form-check-label fs-8 text-muted" for="select_all_products">
                                            {{ __('auth._select_all') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <input type="text"
                                id="batch_product_search"
                                class="form-control form-control-solid mb-3"
                                placeholder="{{ __('auth._search') }} SKU / {{ __('auth._name') }}..."
                                autocomplete="off">

                            <div class="border rounded p-3 batch-scroll-list" style="max-height: 360px; overflow-y: auto;">
                                @forelse ($products_allocate as $product)
                                    @if ($product->variants->count() > 0)
                                    <div class="form-check form-check-sm form-check-custom mb-2 product-row"
                                         data-search="{{ strtolower(($product->sku ?? '') . ' ' . $product->name) }}">
                                        <input class="form-check-input product-checkbox"
                                               type="checkbox"
                                               value="{{ $product->id }}"
                                               id="prod_{{ $product->id }}">
                                        <label class="form-check-label" for="prod_{{ $product->id }}">
                                            <span class="badge badge-light-secondary fs-8 me-1">{{ $product->sku }}</span>
                                            {{ $product->name }}
                                            <span class="badge badge-light-primary fs-8 ms-1">{{ $product->variants->count() }} vars</span>
                                        </label>
                                    </div>
                                    @endif
                                @empty
                                    <div class="text-muted fs-7">{{ __('pagination.no_items_found') }}</div>
                                @endforelse
                            </div>
                        </div>

                        {{-- ── DEPARTMENTS (grouped by location) ───────────────── --}}
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="fs-6 fw-semibold">{{ __('auth._department') }}</label>
                            </div>
                            <input type="text"
                                id="batch_department_search"
                                class="form-control form-control-solid mb-3"
                                placeholder="{{ __('auth._search') }} {{ __('auth._department') }} / {{ __('pagination._location') }}..."
                                autocomplete="off">

                            <div class="border rounded p-3 batch-scroll-list" style="max-height: 360px; overflow-y: auto;">
                                @php $departmentsByLocation = $departments->groupBy('location_id'); @endphp

                                @forelse ($locations as $location)
                                    @php $deptsForLocation = $departmentsByLocation->get($location->id, collect()); @endphp

                                    @if ($deptsForLocation->count())
                                        <div class="d-flex align-items-center justify-content-between mt-3 mb-1">
                                            <span class="fw-bold fs-7 text-gray-700">{{ $location->name }}</span>
                                            <div class="form-check form-check-sm form-check-custom">
                                                <input class="form-check-input select-location"
                                                       type="checkbox"
                                                       data-location="{{ $location->id }}"
                                                       id="loc_all_{{ $location->id }}">
                                                <label class="form-check-label fs-8 text-muted" for="loc_all_{{ $location->id }}">
                                                    {{ __('auth._select_all') }}
                                                </label>
                                            </div>
                                        </div>

                                        @foreach ($deptsForLocation as $dept)
                                            <div class="form-check form-check-sm form-check-custom mb-2 dept-row"
                                                 data-location="{{ $location->id }}"
                                                 data-search="{{ strtolower($dept->name . ' ' . $location->name) }}">
                                                <input class="form-check-input dept-checkbox"
                                                       type="checkbox"
                                                       value="{{ $dept->id }}"
                                                       id="dept_{{ $dept->id }}">
                                                <label class="form-check-label" for="dept_{{ $dept->id }}">
                                                    {{ $dept->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    @endif
                                @empty
                                    <div class="text-muted fs-7">{{ __('pagination.no_items_found') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="separator my-6"></div>

                    <div class="d-flex align-items-center justify-content-between">
                        <span id="batch_summary" class="fs-6 fw-semibold text-gray-700">
                            0 {{ __('pagination.products') }} × 0 {{ __('auth._department') }} = 0 {{ __('pagination.assignments') }}
                        </span>

                        <div>
                            <button type="button" id="batchDiscardButton" class="btn btn-light me-3" data-bs-dismiss="modal">
                                {{ __('auth._discard') }}
                            </button>
                            <button type="button" id="batchSubmitButton" class="btn btn-primary" disabled>
                                <span class="indicator-label">{{ __('pagination.allocate_selected') }}</span>
                                <span class="indicator-progress" style="display: none;">
                                    {{ __('auth.please_wait') }}
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Hidden input for selected product IDs -->
                    <input type="hidden" name="product_ids" id="bulkProductIds" value="">
                </form>
            </div>
        </div>
    </div>
</div>

