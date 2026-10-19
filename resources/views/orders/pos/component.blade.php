@can('view order')
<div class="d-flex flex-column flex-xl-row">
    <div class="d-flex flex-row-fluid me-xl-9 mb-10 mb-xl-0">
        <div class="card card-flush card-p-0 bg-transparent border-0">
            <div class="card-body">

                {{-- ═══════════════════════════════════════════════════════════
                     PRODUCT PILLS — hydrated by PosList after /pos/search
                     ═══════════════════════════════════════════════════════════ --}}
                <ul class="nav nav-pills nav-pills-custom gap-3 mb-6 d-flex flex-nowrap"
                    id="productList"
                    style="overflow-x: auto; white-space: nowrap; list-style: none; padding: 0;">
                    {{-- populated by PosList.renderSearchResults() --}}
                </ul>
                <!--end::Nav-->

                <!-- Batch Selection Modal -->
                <div class="modal fade" id="batchSelectionModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="ki-duotone ki-boxes fs-2 me-2"></i>
                                    Select Batch for <span id="batchVariantName"></span>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="batchSelectionBody">
                                <!-- Batches will be listed here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Serial Selection Modal -->
                <div class="modal fade" id="serialSelectionModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header flex-column align-items-stretch">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h5 class="modal-title mb-0">
                                        <i class="bi bi-upc-scan fs-2 me-2 text-primary"></i>
                                        {{ __('passwords.select_serial') }}
                                        <span id="serialVariantName" class="fs-6 text-muted ms-2"></span>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text"
                                        id="serialSelectionSearch"
                                        class="form-control"
                                        placeholder="{{ __('passwords.search_serials_placeholder') }}"
                                        autocomplete="off">
                                    <button class="btn btn-light" type="button" id="serialSelectionSearchClear">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <div class="text-muted fs-8 mt-2" id="serialSelectionCount"></div>
                            </div>
                            <div class="modal-body" id="serialSelectionBody">
                                <!-- Serials will be listed here -->
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                    .product-item .product-card {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        width: 100%;
                        height: 180px;
                        padding: 14px 10px 10px;
                        overflow: hidden;
                        box-sizing: border-box;
                    }

                    .product-item .product-icon {
                        flex: 0 0 auto;
                        margin-bottom: 8px;
                    }

                    .product-item .product-name-wrap {
                        flex: 1 1 auto;
                        min-height: 0;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        width: 100%;
                        overflow: hidden;
                    }

                    .product-item .product-name {
                        display: -webkit-box;
                        -webkit-line-clamp: 2;
                        -webkit-box-orient: vertical;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: normal;
                        word-break: break-word;
                        text-align: center;
                        line-height: 1.2;
                        font-weight: 700;
                        color: #3f4254;
                        font-size: clamp(0.75rem, 0.9vw + 0.4rem, 0.95rem);
                    }

                    .product-item .product-options {
                        flex: 0 0 auto;
                        font-size: 0.75rem;
                        font-weight: 600;
                        color: #99a1b7;
                        margin-top: 4px;
                        white-space: nowrap;
                    }

                    .variant-item .variant-name-wrap {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        width: 100%;
                        height: 48px;
                        min-height: 48px;
                        max-height: 48px;
                        overflow: hidden;
                        margin: 0 auto;
                    }

                    .variant-item .variant-name {
                        display: -webkit-box;
                        -webkit-line-clamp: 2;
                        -webkit-box-orient: vertical;
                        overflow: hidden;
                        white-space: normal;
                        word-break: break-word;
                        text-align: center;
                        line-height: 1.2;
                        font-weight: 700;
                        color: #3f4254;
                        width: 100%;
                        cursor: pointer;
                        font-size: 1rem;
                    }

                    @media (max-width: 575.98px) {
                        .variant-item .variant-name-wrap {
                            height: 42px;
                            min-height: 42px;
                            max-height: 42px;
                        }
                        .variant-item .variant-name {
                            font-size: 0.9rem;
                        }
                    }

                    /* Skeleton shimmer (shown before PosList's first fetch resolves) */
                    @keyframes pos-skeleton-shimmer {
                        0%   { background-position: 200% 0; }
                        100% { background-position: -200% 0; }
                    }
                    .skeleton-pill,
                    .skeleton-block,
                    .skeleton-text {
                        display: inline-block;
                        background: linear-gradient(90deg, #f1f1f4 0%, #e9e9ed 50%, #f1f1f4 100%);
                        background-size: 200% 100%;
                        animation: pos-skeleton-shimmer 1.4s ease-in-out infinite;
                        border-radius: 6px;
                    }
                    .skeleton-pill { border-radius: 20px; }
                </style>

                {{-- ═══════════════════════════════════════════════════════════
                     SKELETON — shown on first load, before PosList fetches.
                     Hidden as soon as the grid renders.
                     ═══════════════════════════════════════════════════════════ --}}
                <div id="variantTabSkeleton">
                    <div class="d-flex flex-wrap d-grid gap-5 gap-xxl-9 variant-container">
                        @for ($i = 0; $i < 6; $i++)
                            <div class="card card-flush flex-row-fluid p-6 pb-5 mw-100 variant-item position-relative" style="opacity: 0.7;">
                                <span class="skeleton-pill position-absolute top-0 start-50 translate-middle-x mt-2" style="width: 60px; height: 20px;"></span>
                                <div class="card-body text-center">
                                    <div class="skeleton-block mb-4 mx-auto" style="width: 150px; height: 150px; border-radius: 8px;"></div>
                                    <div class="skeleton-text mx-auto mb-2" style="width: 80%; height: 14px;"></div>
                                    <div class="skeleton-text mx-auto mb-2" style="width: 60%; height: 14px;"></div>
                                    <div class="skeleton-text mx-auto mb-2" style="width: 40%; height: 12px;"></div>
                                    <div class="skeleton-text mx-auto mt-3" style="width: 50%; height: 20px;"></div>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════════════════════
                     VARIANT GRID — mount point. PosList writes tab panes here.
                     ═══════════════════════════════════════════════════════════ --}}
                <div class="tab-content d-none" id="variantTabContent">
                    {{-- populated by PosList.renderSearchResults() --}}
                </div>
                <!--end::Tab Content-->

                {{-- ═══════════════════════════════════════════════════════════
                     VARIANT NAME AUTO-FIT — unchanged
                     ═══════════════════════════════════════════════════════════ --}}
                <script>
                    function fitVariantName(el) {
                        if (!el) return;
                        const wrap = el.closest('.variant-name-wrap');
                        if (!wrap) return;

                        const maxHeight = wrap.clientHeight;
                        if (!maxHeight) return;

                        const isMobile = window.innerWidth < 576;
                        const baseFontSize = isMobile ? 14.4 : 16;
                        const minFontSize = 8.5;

                        let fontSize = baseFontSize;
                        el.style.fontSize = fontSize + 'px';

                        let guard = 0;
                        while (el.scrollHeight > maxHeight && fontSize > minFontSize && guard < 40) {
                            fontSize -= 0.5;
                            el.style.fontSize = fontSize + 'px';
                            guard++;
                        }
                    }

                    function fitAllVariantNames(scope) {
                        (scope || document).querySelectorAll('.variant-name').forEach(fitVariantName);
                    }

                    document.addEventListener('DOMContentLoaded', function () {
                        fitAllVariantNames();
                    });

                    let variantNameResizeTimeout = null;
                    window.addEventListener('resize', function () {
                        clearTimeout(variantNameResizeTimeout);
                        variantNameResizeTimeout = setTimeout(function () {
                            fitAllVariantNames();
                        }, 200);
                    });

                    document.addEventListener('DOMContentLoaded', function () {
                        document.querySelectorAll('[data-bs-toggle="tab"], [data-bs-toggle="pill"]').forEach(function (trigger) {
                            trigger.addEventListener('shown.bs.tab', function () {
                                const targetSelector = trigger.getAttribute('href') || trigger.getAttribute('data-bs-target');
                                const pane = targetSelector ? document.querySelector(targetSelector) : null;
                                fitAllVariantNames(pane || document);
                            });
                        });
                    });
                </script>

            </div>
        </div>
    </div>
    <!--end::Content-->

    <!--begin::Body-->
    <div class="flex-row-auto w-xl-450px">
        <!--begin::Pos order-->
        <div id="kt_pos_form" class="card card-flush bg-body">
            <div class="card-header pt-5">
                <h3 class="card-title fw-bold text-gray-800 fs-2qx">{{__('pagination._current_order')}}</h3>
                <div class="card-toolbar">
                    <a href="javascript:void(0);" class="btn btn-light-primary fs-4 fw-bold py-4" onclick="clearCart()">{{__('pagination.clear_all')}}</a>
                </div>
            </div>

            <!--begin::Body-->
            <div class="card-body pt-0">
                <div class="table-responsive mb-8">
                    <table class="table align-middle gs-0 gy-4 my-0">
                        <thead>
                            <tr>
                                <th class="min-w-175px">{{__('pagination._item')}}</th>
                                <th class="w-125px">{{__('pagination._quantity')}}</th>
                                <th class="w-60px">{{__('pagination._total')}}</th>
                            </tr>
                        </thead>
                        <tbody id="pos-cart-tbody">
                            <!-- Cart items will be dynamically inserted here -->
                        </tbody>
                    </table>
                </div>

                <!--begin::Summary-->
                <div class="d-flex flex-stack bg-success rounded-3 p-6 mb-11">
                    <div class="fs-6 fw-bold text-white">
                        <span class="d-block lh-1 mb-2">{{__('pagination.sub_total')}}</span>
                        <span class="d-block mb-2">{{__('pagination._discount')}}</span>
                        <span class="d-block mb-9">{{__('pagination._tax')}}</span>
                        <span class="d-block fs-2qx lh-1">{{__('pagination.grant_total')}}</span>
                    </div>
                    <div class="fs-6 fw-bold text-white text-end">
                        <span class="d-block lh-1 mb-2" data-kt-pos-element="total">0.00</span>
                        <span class="d-block mb-2" data-kt-pos-element="discount">-0.00</span>
                        <span class="d-block mb-9" data-kt-pos-element="tax">0.20</span>
                        <span class="d-block fs-2qx lh-1" data-kt-pos-element="grant-total">0.00</span>
                    </div>
                </div>

                <!--begin::Customer-->
                <div class="m-0">
                    <div class="mb-8">
                        <h3 class="fw-bold text-gray-800 mb-5">{{ __('pagination.select_customer') }}</h3>

                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <div class="btn btn-outline btn-outline-dashed btn-outline-default d-flex flex-column align-items-center justify-content-center gap-2 py-4 w-100 cursor-pointer"
                                    id="btn-pick-existing">
                                    <i class="ki-duotone ki-profile-circle fs-2x text-primary">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                    </i>
                                    <span class="fw-bold fs-6 text-gray-800">{{ __('pagination.existing_customer') }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="btn btn-outline btn-outline-dashed btn-outline-default d-flex flex-column align-items-center justify-content-center gap-2 py-4 w-100 cursor-pointer"
                                    id="btn-pick-new">
                                    <i class="ki-duotone ki-user-edit fs-2x text-primary">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                    </i>
                                    <span class="fw-bold fs-6 text-gray-800">{{ __('pagination.new_customer') }}</span>
                                </div>
                            </div>
                        </div>

                        <input type="radio" name="cust_mode" id="cust-mode-existing" value="existing" class="d-none">
                        <input type="radio" name="cust_mode" id="cust-mode-new" value="new" class="d-none">

                        <div id="panel-existing-cust" class="d-none">
                            <label class="form-label fw-semibold text-gray-700">{{ __('pagination.choose_customer') }}</label>
                            <select class="form-select form-select-lg" id="cust-existing-select" style="width: 100%">
                                <option value="">— {{ __('pagination.choose_customer') }} —</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}">
                                        {{ $customer->first_name }} {{ $customer->last_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div id="panel-new-cust" class="d-none">
                            <label class="form-label fw-semibold text-gray-700">{{ __('pagination.customer_name_placeholder') }}</label>
                            <input type="text"
                                class="form-control form-control-lg"
                                id="cust-new-input"
                                placeholder="{{ __('pagination.customer_name_placeholder') }}">
                        </div>
                    </div>
                </div>

                <script>
                    // ──────────────────────────────────────────────────────
                    // CUSTOMER SELECTION — unchanged
                    // ──────────────────────────────────────────────────────
                    document.addEventListener('click', function (e) {
                        const btnExisting = document.getElementById('btn-pick-existing');
                        const btnNew      = document.getElementById('btn-pick-new');

                        const clickedExisting = btnExisting && (e.target === btnExisting || btnExisting.contains(e.target));
                        const clickedNew      = btnNew      && (e.target === btnNew      || btnNew.contains(e.target));

                        if (!clickedExisting && !clickedNew) return;

                        const mode = clickedExisting ? 'existing' : 'new';

                        const radioExisting  = document.getElementById('cust-mode-existing');
                        const radioNew       = document.getElementById('cust-mode-new');
                        const panelExisting  = document.getElementById('panel-existing-cust');
                        const panelNew       = document.getElementById('panel-new-cust');
                        const selectExisting = document.getElementById('cust-existing-select');
                        const inputNew       = document.getElementById('cust-new-input');

                        [btnExisting, btnNew].forEach(btn => {
                            btn.classList.remove('btn-light-primary', 'border-primary');
                            btn.classList.add('btn-outline-default');
                            btn.querySelector('i').classList.remove('text-primary');
                            btn.querySelector('i').classList.add('text-muted');
                            btn.querySelector('span').classList.remove('text-primary');
                            btn.querySelector('span').classList.add('text-gray-800');
                        });

                        panelExisting.classList.add('d-none');
                        panelNew.classList.add('d-none');

                        const activeBtn = mode === 'existing' ? btnExisting : btnNew;
                        activeBtn.classList.remove('btn-outline-default');
                        activeBtn.classList.add('btn-light-primary', 'border-primary');
                        activeBtn.querySelector('i').classList.remove('text-muted');
                        activeBtn.querySelector('i').classList.add('text-primary');
                        activeBtn.querySelector('span').classList.remove('text-gray-800');
                        activeBtn.querySelector('span').classList.add('text-primary');

                        if (mode === 'existing') {
                            radioExisting.checked = true;
                            panelExisting.classList.remove('d-none');
                            setTimeout(() => selectExisting.focus(), 100);
                        } else {
                            radioNew.checked = true;
                            panelNew.classList.remove('d-none');
                            setTimeout(() => { inputNew.focus(); inputNew.select(); }, 100);
                        }
                    });

                    document.addEventListener('DOMContentLoaded', function () {
                        if (typeof $ === 'undefined' || !$.fn?.select2) return;
                        $('#cust-existing-select').select2({
                            placeholder: '— {{ __("pagination.choose_customer") }} —',
                            allowClear: true,
                            width: '100%',
                        });
                    });
                </script>

                <div class="m-0">
                    @can('create order')
                    <div class="mt-8">
                        <button
                            id="processBill"
                            type="button"
                            class="btn btn-primary w-100 py-4 d-flex align-items-center justify-content-center gap-3"
                            style="font-size: 1.15rem; font-weight: 600;"
                            onclick="processPayment()">
                            <i class="ki-duotone ki-printer fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                            </i>
                            <span class="indicator-label">{{__('pagination.print_bill')}}</span>
                            <span class="indicator-progress">{{__('pagination.processing_payments')}}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                    <div class="mt-3">
                        <button
                            id="generateInvoiceBtn"
                            type="button"
                            class="btn btn-light-primary w-100 py-4 d-flex align-items-center justify-content-center gap-3"
                            style="font-size: 1.15rem; font-weight: 600;"
                            onclick="generateInvoice()">
                            <i class="ki-duotone ki-bill fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                            </i>
                            <span class="indicator-label">{{__('pagination.generate_invoice')}}</span>
                            <span class="indicator-progress">{{__('pagination.generating_invoice')}}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                    @endcan
                </div>

            </div>
        </div>
    </div>
    <!--end::Sidebar-->
</div>

<style>
    .variant-item {
        cursor: pointer !important;
        opacity: 1 !important;
        pointer-events: auto !important;
    }

    .variant-item.out-of-stock {
        cursor: not-allowed !important;
        opacity: 0.5 !important;
    }
</style>

@include('orders.pos.pause-buy')
@include('orders.pos.payment-mode')
@endcan