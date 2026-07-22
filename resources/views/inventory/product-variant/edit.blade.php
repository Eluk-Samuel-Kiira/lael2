<div class="modal fade" id="editProduct{{ $product->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_currency{{ $product->id }}">
                <h2 class="fw-bold">{{__('auth._edit')}} {{__('pagination._variations_of')}} - {{ $product_variants->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_product_form{{ $product->id }}" class="form" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <!-- ✅ CRITICAL: Parent Product ID (for validation) -->
                    <input type="hidden" name="product_id" value="{{ $product_variants->id }}" />
                    
                    <!-- ✅ Variant ID (for reference) -->
                    <input type="hidden" name="variant_id" value="{{ $product->id }}" />
                    
                    <div class="d-flex justify-content-center">
                        <div class="image-input image-input-outline image-input-placeholder mb-3" data-kt-image-input="true">
                            <div 
                                class="image-input-wrapper w-200px h-200px"
                                style="background-image: url({{ productVariantImage($product->image_url) }})"
                            ></div>
                        </div>
                    </div>

                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._product')}} </span>
                                </label>
                                <input type="text" value="{{ $product->name }}" class="form-control form-control-solid" name="name" />
                                <div id="name{{ $product->id }}"></div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._sku')}} </span>
                                </label>
                                <input type="text" value="{{ $product->sku }}" class="form-control form-control-solid" name="sku" />
                                <div id="sku{{ $product->id }}"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._barcode')}} </span>
                                </label>
                                <input type="text" value="{{ $product->barcode }}" class="form-control form-control-solid" name="barcode" />
                                <div id="barcode{{ $product->id }}"></div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.overall_quantity')}} </span>
                                </label>
                                <input type="number" value="{{ $product->overal_quantity_at_hand }}" class="form-control form-control-solid" name="overal_quantity_at_hand" />
                                <div id="overal_quantity_at_hand{{ $product->id }}"></div>
                            </div>
                        </div>
                        
                        {{-- COST BREAKDOWN SECTION --}}
                        <div class="row g-9 mb-8">
                            <div class="col-md-12">
                                <div class="card card-dashed bg-light-primary">
                                    <div class="card-header py-3">
                                        <h5 class="card-title fw-bold">{{ __('pagination.cost_breakdown') }}</h5>
                                    </div>
                                    <div class="card-body py-4">
                                        <div class="row g-9">
                                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                                    <span class="required">{{__('pagination.supplier_cost_price')}} {{ currency_code() }}</span>
                                                </label>
                                                <input type="number" value="{{ $product->supplier_cost_price ?? 0 }}" class="form-control form-control-solid" name="supplier_cost_price" step="0.01" />
                                                <div id="supplier_cost_price{{ $product->id }}"></div>
                                            </div>
                                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                                    {{__('pagination.total_shipping_cost')}} {{ currency_code() }}
                                                </label>
                                                <input type="number" value="{{ $product->total_shipping_cost ?? 0 }}" class="form-control form-control-solid" name="total_shipping_cost" step="0.01" />
                                                <div id="total_shipping_cost{{ $product->id }}"></div>
                                            </div>
                                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                                    {{__('pagination.ura_taxes_applied')}} {{ currency_code() }}
                                                </label>
                                                <input type="number" value="{{ $product->ura_taxes_applied ?? 0 }}" class="form-control form-control-solid" name="ura_taxes_applied" step="0.01" />
                                                <div id="ura_taxes_applied{{ $product->id }}"></div>
                                            </div>
                                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                                    {{__('pagination.additional_expenses')}} {{ currency_code() }}
                                                </label>
                                                <input type="number" value="{{ $product->additional_expenses ?? 0 }}" class="form-control form-control-solid" name="additional_expenses" step="0.01" />
                                                <div id="additional_expenses{{ $product->id }}"></div>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <div class="alert alert-info py-2 mb-0">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="fw-bold">{{ __('pagination.grand_total_cost_price') }}:</span>
                                                        <span class="fw-bold fs-4 text-primary" id="grand_total_display_edit_{{ $product->id }}">
                                                            {{ number_format($product->grand_total_cost_price ?? 0, 2) }} {{ currency_code() }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="grand_total_cost_price" id="grand_total_input_edit_{{ $product->id }}" value="{{ $product->grand_total_cost_price ?? 0 }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- PRICING SECTION --}}
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.selling_price')}} {{ currency_code() }}</span>
                                </label>
                                <input type="number" value="{{ $product->selling_price ?? 0 }}" class="form-control form-control-solid" name="selling_price" step="0.01" />
                                <div id="selling_price{{ $product->id }}"></div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    {{__('pagination.discount_selling_price')}} {{ currency_code() }}
                                </label>
                                <input type="number" value="{{ $product->discount_selling_price ?? 0 }}" class="form-control form-control-solid" name="discount_selling_price" step="0.01" placeholder="Price after discount" />
                                <div class="form-text text-muted">{{ __('pagination.discount_selling_price_hint') }}</div>
                                <div id="discount_selling_price{{ $product->id }}"></div>
                            </div>
                        </div>

                        {{-- DISCOUNT & MARKUP --}}
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    {{__('pagination.discount_percentage')}} (%)
                                </label>
                                <input type="number" value="{{ $product->discount_percentage ?? 0 }}" class="form-control form-control-solid" name="discount_percentage" step="0.01" min="0" max="100" />
                                <div class="form-text text-muted">{{ __('pagination.discount_percentage_hint') }}</div>
                                <div id="discount_percentage{{ $product->id }}"></div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    {{__('pagination.markup_percentage')}} (%)
                                </label>
                                <input type="number" value="{{ $product->markup_percentage ?? 0 }}" class="form-control form-control-solid" name="markup_percentage" step="0.01" min="0" />
                                <div class="form-text text-muted">{{ __('pagination.markup_percentage_hint') }}</div>
                                <div id="markup_percentage{{ $product->id }}"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._weight')}} </span>
                                </label>
                                <input type="number" value="{{ $product->weight }}" class="form-control form-control-solid" name="weight" step="0.01" />
                                <div id="weight{{ $product->id }}"></div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.weight_unit')}} </span>
                                </label>
                                <select name="weight_unit" class="form-select" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                    <option></option>
                                    @foreach ($uoms as $umo)
                                        <option value="{{ $umo->id }}" {{ $product->weight_unit == $umo->id ? 'selected' : '' }}>{{ $umo->name }}</option>
                                    @endforeach
                                </select>
                                <div id="weight_unit{{ $product->id }}"></div>
                            </div>
                        </div>

                        <button type="button" id="closeModalEditButton{{$product->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button onclick="editProductVariantInstanceLoop({{$product->id}})" id="editUOMButton{{ $product->id }}" type="button" class="btn btn-primary">
                            <span class="indicator-label">{{__('auth._update')}}</span>
                            <span class="indicator-progress" style="display: none;">{{__('auth.please_wait') }}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{--
    ── Grand-total real-time calculation (variant edit modals) ──────────────
    Registered ONCE here, not inside the per-row edit.blade.php partial.

    Both listeners are pure event delegation on `document`, which is why
    this is safe to register exactly once regardless of:
      - how many variants/modals exist on the page,
      - variants being added later via AJAX pagination/search/filter into
        #reloadVariantComponent (delegation checks the event target at fire
        time, so it doesn't matter that those rows didn't exist yet when
        this script ran),
      - the table starting out empty on first load.

    No DOMContentLoaded wrapper needed either — `document` already exists
    the instant this script tag runs, and addEventListener doesn't require
    its target elements to exist yet.

    The idempotency guard (window.__grandTotalCalcInit) is just cheap
    insurance in case this script block itself ever ends up included more
    than once on a page.
--}}
<script>
    if (!window.__grandTotalCalcInit) {
        window.__grandTotalCalcInit = true;

        window.calculateGrandTotalEdit = function (productId) {
            const form = document.getElementById(`kt_modal_product_form${productId}`);
            if (!form) return;

            const supplierCost = parseFloat(form.querySelector('input[name="supplier_cost_price"]')?.value) || 0;
            const shipping = parseFloat(form.querySelector('input[name="total_shipping_cost"]')?.value) || 0;
            const taxes = parseFloat(form.querySelector('input[name="ura_taxes_applied"]')?.value) || 0;
            const expenses = parseFloat(form.querySelector('input[name="additional_expenses"]')?.value) || 0;

            const grandTotal = supplierCost + shipping + taxes + expenses;
            const display = document.getElementById(`grand_total_display_edit_${productId}`);
            const input = document.getElementById(`grand_total_input_edit_${productId}`);

            if (display) {
                display.textContent = grandTotal.toFixed(2) + ' {{ currency_code() }}';
            }
            if (input) {
                input.value = grandTotal.toFixed(2);
            }
        };

        // Recalculate as the user types
        document.addEventListener('input', function (e) {
            const target = e.target;
            const form = target.closest('form[id^="kt_modal_product_form"]');
            if (!form) return;

            const watchedFields = ['supplier_cost_price', 'total_shipping_cost', 'ura_taxes_applied', 'additional_expenses'];
            if (watchedFields.includes(target.name)) {
                // Get the variant ID from the form
                const variantId = form.querySelector('input[name="variant_id"]')?.value;
                if (variantId) {
                    window.calculateGrandTotalEdit(variantId);
                }
            }
        });

        // Recalculate when modal opens
        document.addEventListener('shown.bs.modal', function (e) {
            if (e.target.id && e.target.id.startsWith('editProduct')) {
                const variantId = e.target.id.replace('editProduct', '');
                window.calculateGrandTotalEdit(variantId);
            }
        });
    }
</script>