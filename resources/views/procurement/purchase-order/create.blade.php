<!-- Add Purchase Order Modal -->
<div class="modal fade" id="kt_modal_add_purchase_order" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-950px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_purchase_order_header">
                <h2 class="fw-bold">{{ __('passwords._create') }} {{ __('passwords.purchase_order') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_purchase_order_form" class="form">
                    @csrf
                    <div class="text-center pt-10">
                        <!-- Basic Information -->
                        <div class="row g-9 mb-8">
                            <!-- Supplier (Typable) -->
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{ __('passwords.supplier') }}</span>
                                </label>
                                <x-typable-select 
                                    name="supplier_id"
                                    :options="$suppliers"
                                    selected="{{ old('supplier_id', $purchaseOrder->supplier_id ?? '') }}"
                                    placeholder="Type or select supplier..."
                                />
                                <div id="supplier_id"></div>
                            </div>

                            <!-- Location (Typable) -->
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{ __('passwords.location') }}</span>
                                </label>
                                <x-typable-select 
                                    name="location_id"
                                    :options="$locations"
                                    selected="{{ old('location_id', $purchaseOrder->location_id ?? '') }}"
                                    placeholder="Type or select location..."
                                />
                                <div id="location_id"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{ __('passwords.expected_delivery_date') }}</span>
                                </label>
                                <input type="date" class="form-control form-control-solid" name="expected_delivery_date" min="{{ date('Y-m-d') }}" />
                                <div id="expected_delivery_date"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.notes') }}</span>
                                </label>
                                <textarea class="form-control form-control-solid" name="notes" rows="2" placeholder="{{ __('passwords.optional_notes') }}"></textarea>
                                <div id="notes"></div>
                            </div>
                        </div>

                        <!-- Order Items Section -->
                        <div class="border rounded p-4 mb-8">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="fw-bold">{{ __('passwords.order_items') }}</h4>
                                <button type="button" class="btn btn-sm btn-light-primary" onclick="addPurchaseOrderItem()">
                                    <i class="ki-duotone ki-plus fs-3"></i>
                                    {{ __('passwords._add_item') }}
                                </button>
                            </div>

                            <div id="purchase_order_items_container">
                                <div class="row g-4 mb-4 purchase-order-item" id="item_0">
                                    <div class="col-md-4">
                                        <label class="form-label required">{{ __('passwords.product') }}</label>
                                        <div class="position-relative">
                                            <input type="text" 
                                                id="product_search_0"
                                                class="form-control product-typable-input"
                                                list="product_list_0"
                                                placeholder="Type or select product..."
                                                autocomplete="off"
                                                data-item-index="0">
                                            <input type="hidden" 
                                                name="items[0][product_variant_id]" 
                                                id="product_id_0">
                                            <datalist id="product_list_0">
                                                <option value="">Select product</option>
                                                @foreach($variants as $variant)
                                                    <option value="{{ $variant->name }}" 
                                                            data-id="{{ $variant->id }}"
                                                            data-cost-price="{{ $variant->supplier_cost_price }}">
                                                    </option>
                                                @endforeach
                                            </datalist>
                                        </div>
                                        <div id="items.0.product_variant_id"></div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label required">{{ __('passwords.quantity') }}</label>
                                        <input type="number" name="items[0][quantity]" class="form-control item-quantity" min="1" value="1">
                                        <div id="items.0.quantity"></div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label required">{{ __('passwords.unit_cost') }}</label>
                                        <input type="number" name="items[0][unit_cost]" class="form-control item-unit-cost" min="0.01" step="0.01" value="0.00">
                                        <div id="items.0.unit_cost"></div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('passwords.total') }}</label>
                                        <input type="text" class="form-control bg-light item-total" value="0.00" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="button" class="btn btn-sm btn-danger w-100" onclick="removePurchaseOrderItem(this)" disabled>
                                            <i class="bi bi-trash fs-5"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Summary -->
                            <div class="row mt-6 pt-4 border-top">
                                <div class="col-md-6"></div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-semibold">{{ __('passwords.subtotal') }}:</span>
                                        <span id="order_subtotal">0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-semibold">{{ __('passwords.tax_total') }}:</span>
                                        <span id="order_tax_total">0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 fs-5 fw-bold">
                                        <span>{{ __('passwords.grand_total') }}:</span>
                                        <span id="order_grand_total">0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <button type="reset" class="btn btn-light me-3" id="discardPurchaseOrderButton" data-bs-dismiss="modal">
                            {{ __('passwords._discard') }}
                        </button>
                        
                        <button 
                            id="submitPurchaseOrderButton" 
                            type="button" 
                            class="btn btn-primary"
                            onclick="submitPurchaseOrderForm(
                                'kt_modal_add_purchase_order_form', 
                                'submitPurchaseOrderButton',
                                '{{ route('purchase-order.store') }}',
                                'POST',
                                'discardPurchaseOrderButton'
                            )">
                            
                            <span class="indicator-label">{{ __('passwords.create_purchase_order') }}</span>
                            <span class="indicator-progress">{{ __('passwords.please_wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


