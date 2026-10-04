<div class="modal fade" id="inventoryPricingModal" tabindex="-1" aria-hidden="true" dir="ltr" style="direction: ltr; text-align: left;">
    <div class="modal-dialog modal-dialog-centered mw-750px">
        <div class="modal-content">

            <div class="modal-header">
                <h2 class="fw-bold">
                    <i class="bi bi-currency-exchange me-2 text-primary"></i>
                    {{ __('passwords.custom_pricing') }}
                    <span id="pricingItemLabel" class="fs-6 text-muted ms-2"></span>
                </h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>

            <div class="modal-body px-5 my-7">
                <input type="hidden" id="pricingItemId">

                <div class="alert alert-light-info d-flex align-items-center mb-6">
                    <i class="ki-duotone ki-information-5 fs-2 me-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <div class="fs-7">
                        {{ __('passwords.pricing_help_text') }}
                    </div>
                </div>

                {{-- Custom pricing toggle --}}
                <div class="form-check form-switch form-check-custom form-check-solid mb-6">
                    <input class="form-check-input" type="checkbox" id="hasCustomPricingToggle">
                    <label class="form-check-label fw-semibold" for="hasCustomPricingToggle">
                        {{ __('passwords.enable_custom_pricing') }}
                    </label>
                </div>

                {{-- Variant fallback (shown when toggle is off) --}}
                <div id="variantFallbackSection" class="p-4 rounded bg-light mb-6">
                    <div class="text-muted fs-8 mb-3">
                        {{ __('passwords.inherited_from_variant') }}
                    </div>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <span class="text-muted fs-8 d-block">{{ __('passwords.cost_price') }}</span>
                            <span class="fw-bold" id="variantCostPrice">—</span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted fs-8 d-block">{{ __('passwords.selling_price') }}</span>
                            <span class="fw-bold text-primary" id="variantSellingPrice">—</span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted fs-8 d-block">{{ __('passwords.discount_price') }}</span>
                            <span class="fw-bold text-success" id="variantDiscountPrice">—</span>
                        </div>
                    </div>
                </div>

                {{-- Override form (shown when toggle is on) --}}
                <div id="customPricingSection" class="d-none">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('passwords.supplier_cost') }}</label>
                            <input type="number" step="0.01" min="0"
                                   class="form-control" id="pricingSupplierCost"
                                   placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('passwords.other_costs') }}</label>
                            <input type="number" step="0.01" min="0"
                                   class="form-control" id="pricingOtherCosts"
                                   placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('passwords.selling_price') }}</label>
                            <input type="number" step="0.01" min="0"
                                   class="form-control" id="pricingSellingPrice"
                                   placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('passwords.discount') }} (%)</label>
                            <input type="number" step="0.01" min="0" max="100"
                                   class="form-control" id="pricingDiscountPercent"
                                   placeholder="0">
                        </div>
                    </div>

                    <div class="separator my-5"></div>

                    <div class="row g-3 fs-7">
                        <div class="col-6 text-muted">{{ __('passwords.grand_total_cost') }}:</div>
                        <div class="col-6 text-end fw-bold" id="previewCost">—</div>

                        <div class="col-6 text-muted">{{ __('passwords.effective_selling_price') }}:</div>
                        <div class="col-6 text-end fw-bold text-primary" id="previewSelling">—</div>

                        <div class="col-6 text-muted">{{ __('passwords.profit_per_unit') }}:</div>
                        <div class="col-6 text-end fw-bold" id="previewProfit">—</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    {{ __('auth._discard') }}
                </button>
                <button type="button" id="saveInventoryPricingBtn" class="btn btn-primary">
                    <span class="indicator-label">
                        <i class="bi bi-check2 me-1"></i> {{ __('auth.save') }}
                    </span>
                    <span class="indicator-progress" style="display: none;">
                        {{ __('auth.please_wait') }}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>

        </div>
    </div>
</div>



