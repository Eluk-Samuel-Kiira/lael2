{{--
    Bulk allocate products to departments in one submission.
    Assumes $products, $departments and $locations are available in scope.
--}}
{{-- resources/views/inventory/product/bulk-allocate.blade.php --}}
<div class="modal fade" id="kt_modal_bulk_allocate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable mw-1000px">
        <div class="modal-content">

            {{-- ─── GREEN HEADER ─────────────────────────────────────── --}}
            <div class="modal-header bg-success py-4">
                <div class="d-flex align-items-center gap-3">
                    <i class="ki-duotone ki-building fs-2 text-white">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <div>
                        <h2 class="modal-title text-white fw-bold mb-0">
                            {{ __('pagination.bulk_allocate_products') }}
                        </h2>
                        <span class="text-white opacity-75 fs-7">
                            {{ __('pagination.bulk_allocate_info') }}
                        </span>
                    </div>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-white"
                     data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1 text-white">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </div>
            </div>

            {{-- ─── BODY ────────────────────────────────────────────── --}}
            <div class="modal-body px-7 py-6">
                <form id="kt_modal_bulk_allocate_form"
                      action="{{ route('products.bulk-allocate') }}"
                      method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-6">

                        {{-- ── PRODUCTS ─────────────────────────────────── --}}
                        <div class="col-md-6">
                            <div class="card card-flush bg-light rounded-3 h-100">
                                <div class="card-header pt-4 pb-2 border-0 bg-transparent">
                                    <h3 class="card-title fs-6 fw-bold text-gray-800 mb-0">
                                        <i class="ki-duotone ki-parcel fs-4 me-2 text-success">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        {{ __('pagination._products') }}
                                    </h3>
                                    <div class="card-toolbar d-flex align-items-center gap-3">
                                        <span id="selectedProductsCount"
                                              class="badge badge-success fs-7 fw-bold">0</span>
                                        <label class="form-check form-check-sm form-check-custom form-check-solid mb-0">
                                            <input class="form-check-input" type="checkbox" id="select_all_products">
                                            <span class="form-check-label fs-8 text-muted ms-2">
                                                {{ __('auth._select_all') }}
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="position-relative mb-3">
                                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4 top-50 translate-middle-y">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        <input type="text"
                                               id="batch_product_search"
                                               class="form-control form-control-solid ps-12"
                                               placeholder="{{ __('auth._search') }} SKU / {{ __('auth._name') }}..."
                                               autocomplete="off">
                                    </div>

                                    <div class="border border-gray-300 border-dashed rounded p-3"
                                         style="max-height: 340px; overflow-y: auto;">
                                        @forelse ($products_allocate as $product)
                                            @if ($product->variants->count() > 0)
                                                <label class="form-check form-check-sm form-check-custom form-check-solid product-row d-flex align-items-center gap-2 py-2 mb-1 rounded px-2"
                                                       data-search="{{ strtolower(($product->sku ?? '') . ' ' . $product->name) }}">
                                                    <input class="form-check-input product-checkbox"
                                                           type="checkbox"
                                                           value="{{ $product->id }}"
                                                           id="prod_{{ $product->id }}">
                                                    <span class="form-check-label flex-grow-1">
                                                        <span class="badge badge-light-secondary fs-8 me-1">{{ $product->sku }}</span>
                                                        <span class="text-gray-800 fw-semibold">{{ $product->name }}</span>
                                                        <span class="badge badge-light-primary fs-8 ms-1">
                                                            {{ $product->variants->count() }} vars
                                                        </span>
                                                    </span>
                                                </label>
                                            @endif
                                        @empty
                                            <div class="text-muted fs-7 text-center py-6">
                                                {{ __('pagination.no_items_found') }}
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ── DEPARTMENTS ──────────────────────────────── --}}
                        <div class="col-md-6">
                            <div class="card card-flush bg-light rounded-3 h-100">
                                <div class="card-header pt-4 pb-2 border-0 bg-transparent">
                                    <h3 class="card-title fs-6 fw-bold text-gray-800 mb-0">
                                        <i class="ki-duotone ki-office-building fs-4 me-2 text-primary">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        {{ __('auth._department') }}
                                    </h3>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="position-relative mb-3">
                                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4 top-50 translate-middle-y">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        <input type="text"
                                               id="batch_department_search"
                                               class="form-control form-control-solid ps-12"
                                               placeholder="{{ __('auth._search') }} {{ __('auth._department') }} / {{ __('pagination._location') }}..."
                                               autocomplete="off">
                                    </div>

                                    <div class="border border-gray-300 border-dashed rounded p-3"
                                         style="max-height: 340px; overflow-y: auto;">
                                        @php $departmentsByLocation = $departments->groupBy('location_id'); @endphp

                                        @forelse ($locations as $location)
                                            @php $deptsForLocation = $departmentsByLocation->get($location->id, collect()); @endphp

                                            @if ($deptsForLocation->count())
                                                <div class="d-flex align-items-center justify-content-between mt-2 mb-1 px-2">
                                                    <span class="fw-bold fs-7 text-gray-700 text-uppercase">
                                                        <i class="ki-duotone ki-geolocation fs-6 me-1 text-primary">
                                                            <span class="path1"></span><span class="path2"></span>
                                                        </i>
                                                        {{ $location->name }}
                                                    </span>
                                                    <label class="form-check form-check-sm form-check-custom form-check-solid mb-0">
                                                        <input class="form-check-input select-location"
                                                               type="checkbox"
                                                               data-location="{{ $location->id }}"
                                                               id="loc_all_{{ $location->id }}">
                                                        <span class="form-check-label fs-8 text-muted ms-2">
                                                            {{ __('auth._select_all') }}
                                                        </span>
                                                    </label>
                                                </div>

                                                @foreach ($deptsForLocation as $dept)
                                                    <label class="form-check form-check-sm form-check-custom form-check-solid dept-row d-flex align-items-center gap-2 py-2 mb-1 rounded px-2 ms-4"
                                                           data-location="{{ $location->id }}"
                                                           data-search="{{ strtolower($dept->name . ' ' . $location->name) }}">
                                                        <input class="form-check-input dept-checkbox"
                                                               type="checkbox"
                                                               value="{{ $dept->id }}"
                                                               id="dept_{{ $dept->id }}">
                                                        <span class="form-check-label flex-grow-1 text-gray-800 fw-semibold">
                                                            {{ $dept->name }}
                                                        </span>
                                                    </label>
                                                @endforeach
                                            @endif
                                        @empty
                                            <div class="text-muted fs-7 text-center py-6">
                                                {{ __('pagination.no_items_found') }}
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-6"></div>

                    {{-- ── SUMMARY + ACTIONS ─────────────────────────────── --}}
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <span id="batch_summary"
                              class="badge badge-light-success fs-7 fw-bold py-3 px-4">
                            0 {{ __('pagination.products') }} × 0 {{ __('auth._department') }} = 0 {{ __('pagination.assignments') }}
                        </span>

                        <div class="d-flex align-items-center gap-3">
                            <button type="button"
                                    id="batchDiscardButton"
                                    class="btn btn-light"
                                    data-bs-dismiss="modal">
                                {{ __('auth._discard') }}
                            </button>
                            <button type="button"
                                    id="batchSubmitButton"
                                    class="btn btn-success"
                                    disabled>
                                <span class="indicator-label">
                                    <i class="ki-duotone ki-check-circle fs-3 me-2">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    {{ __('pagination.allocate_selected') }}
                                </span>
                                <span class="indicator-progress" style="display: none;">
                                    {{ __('auth.please_wait') }}
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <input type="hidden" name="product_ids" id="bulkProductIds" value="">
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('kt_modal_bulk_allocate');
    if (!modal) return;

    // ── Element handles ─────────────────────────────────────
    const productSearch     = modal.querySelector('#batch_product_search');
    const deptSearch        = modal.querySelector('#batch_department_search');
    const selectAllProducts = modal.querySelector('#select_all_products');
    const summary           = modal.querySelector('#batch_summary');
    const submitBtn         = modal.querySelector('#batchSubmitButton');
    const form              = modal.querySelector('#kt_modal_bulk_allocate_form');
    const productIdsInput   = modal.querySelector('#bulkProductIds');
    const countBadge        = modal.querySelector('#selectedProductsCount');

    // ── Helper: get current selections ──────────────────────
    const selectedProductIds = () =>
        Array.from(modal.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value);

    const selectedDepartmentIds = () =>
        Array.from(modal.querySelectorAll('.dept-checkbox:checked')).map(cb => cb.value);

    // ── Summary + enable/disable submit ─────────────────────
    function updateSummary() {
        const pIds = selectedProductIds();
        const dIds = selectedDepartmentIds();

        summary.textContent = `${pIds.length} {{ __('pagination.products') }} × ${dIds.length} {{ __('auth._department') }} = ${pIds.length * dIds.length} {{ __('pagination.assignments') }}`;
        submitBtn.disabled = pIds.length === 0 || dIds.length === 0;

        if (countBadge) countBadge.textContent = pIds.length;
        if (productIdsInput) productIdsInput.value = pIds.join(',');
    }

    // ── Reactive bindings ───────────────────────────────────
    modal.addEventListener('change', function (e) {
        if (e.target.classList.contains('product-checkbox') || e.target.classList.contains('dept-checkbox')) {
            updateSummary();
        }

        if (e.target.classList.contains('select-location')) {
            const locId = e.target.dataset.location;
            modal.querySelectorAll(`.dept-row[data-location="${locId}"]`).forEach(row => {
                if (row.style.display !== 'none') {
                    row.querySelector('.dept-checkbox').checked = e.target.checked;
                }
            });
            updateSummary();
        }
    });

    selectAllProducts.addEventListener('change', function () {
        modal.querySelectorAll('.product-row').forEach(row => {
            if (row.style.display !== 'none') {
                row.querySelector('.product-checkbox').checked = selectAllProducts.checked;
            }
        });
        updateSummary();
    });

    productSearch.addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        modal.querySelectorAll('.product-row').forEach(row => {
            row.style.display = row.dataset.search.includes(term) ? '' : 'none';
        });
    });

    deptSearch.addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        modal.querySelectorAll('.dept-row').forEach(row => {
            row.style.display = row.dataset.search.includes(term) ? '' : 'none';
        });
    });

    // ── Reset state when modal closes ──────────────────────
    modal.addEventListener('hidden.bs.modal', function () {
        form.reset();
        modal.querySelectorAll('.product-checkbox, .dept-checkbox, .select-location')
            .forEach(cb => { cb.checked = false; });
        if (selectAllProducts) selectAllProducts.checked = false;
        if (productSearch) productSearch.value = '';
        if (deptSearch) deptSearch.value = '';
        modal.querySelectorAll('.product-row, .dept-row').forEach(r => { r.style.display = ''; });
        updateSummary();
    });

    // ── Submit ──────────────────────────────────────────────
    submitBtn.addEventListener('click', async function () {
        const pIds = selectedProductIds();
        const dIds = selectedDepartmentIds();
        if (!pIds.length || !dIds.length) return;

        const label    = submitBtn.querySelector('.indicator-label');
        const progress = submitBtn.querySelector('.indicator-progress');

        submitBtn.disabled = true;
        if (label)    label.style.display    = 'none';
        if (progress) progress.style.display = 'inline-flex';

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'Accept':           'application/json',
                    'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    product_ids: pIds,
                    departments: dIds,
                }),
            });

            const json = await res.json().catch(() => ({}));

            if (res.status === 422) {
                window.toastr?.error?.(json.message || 'Please check your selections.');
                return;
            }

            if (!res.ok || json.success === false) {
                throw new Error(json.message || '{{ __('pagination.bulk_allocation_failed') }}');
            }

            // ── Success ────────────────────────────────────
            window.toastr?.success?.(json.message || '{{ __('pagination.products_allocated_success', ['count' => 0]) }}');

            bootstrap.Modal.getInstance(modal)?.hide();

            // Refresh the products list — no more LiveBlade
            window.ProductList?.reload?.();

        } catch (err) {
            console.error('[bulk-allocate] failed:', err);
            window.toastr?.error?.(err.message || '{{ __('pagination.bulk_allocation_failed') }}');
        } finally {
            submitBtn.disabled = false;
            if (label)    label.style.display    = 'inline-flex';
            if (progress) progress.style.display = 'none';
            updateSummary();  // re-evaluate to keep disabled state correct
        }
    });
});
</script>