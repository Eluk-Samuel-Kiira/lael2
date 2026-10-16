{{-- resources/views/manufacturing/production-order/create.blade.php --}}
<!-- Add Production Order Modal -->
<div class="modal fade" id="kt_modal_add_production_order" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_production_order_header">
                <h2 class="fw-bold">
                    <i class="ki-duotone ki-cubes fs-2 me-2"></i>
                    {{ __('passwords.create_production_order') }}
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_production_order_form" class="form">
                    @csrf
                    <div class="text-center pt-10">
                        <!-- Basic Information -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{ __('passwords.location') }}</span>
                                </label>
                                <x-typable-select 
                                    name="location_id"
                                    :options="$locations"
                                    selected="{{ old('location_id', $productionOrder->location_id ?? '') }}"
                                    placeholder="Type or select location..."
                                />
                                <div id="location_id"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.scheduled_date') }}</span>
                                </label>
                                <input type="datetime-local" class="form-control form-control-solid" name="scheduled_date" />
                                <div id="scheduled_date"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.notes') }}</span>
                                </label>
                                <textarea class="form-control form-control-solid" name="notes" rows="2" placeholder="{{ __('passwords.optional_notes') }}"></textarea>
                                <div id="notes"></div>
                            </div>
                        </div>

                        <!-- Input Materials Section -->
                        <div class="border rounded p-4 mb-8">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="fw-bold">
                                    <i class="bi bi-box-arrow-in-down me-2"></i>
                                    {{ __('passwords.input_materials') }}
                                </h4>
                                <button type="button" class="btn btn-sm btn-light-primary" onclick="ProductionCreate.addInput()">
                                    <i class="ki-duotone ki-plus fs-3"></i>
                                    {{ __('passwords._add_material') }}
                                </button>
                            </div>

                            <div id="production_inputs_container">
                                <div class="row g-4 mb-4 production-input-item" id="input_0">
                                    <div class="col-md-3">
                                        <label class="form-label required">{{ __('passwords.material') }}</label>
                                        <div class="position-relative">
                                            <input type="text" 
                                                id="material_search_input_0"
                                                class="form-control material-typable-input"
                                                list="material_list_input_0"
                                                placeholder="Type or select material..."
                                                autocomplete="off"
                                                data-item-index="0"
                                                data-type="input">
                                            <input type="hidden" 
                                                name="inputs[0][product_variant_id]" 
                                                id="material_id_input_0">
                                            <datalist id="material_list_input_0">
                                                @foreach($variants as $variant)
                                                    <option value="{{ $variant->name }}"
                                                            data-id="{{ $variant->id }}"
                                                            data-uom-id="{{ $variant->weight_unit }}"
                                                            data-cost-price="{{ $variant->supplier_cost_price }}"
                                                            data-inventory-strategy="{{ $variant->product?->resolvedInventoryStrategy() ?? 'quantity' }}">
                                                    </option>
                                                @endforeach
                                            </datalist>
                                        </div>
                                        <div id="inputs.0.product_variant_id"></div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label required">{{ __('passwords.quantity') }}</label>
                                        <input type="number" name="inputs[0][planned_quantity]" class="form-control input-quantity" min="0.01" step="0.01" value="1">
                                        <div id="inputs.0.planned_quantity"></div>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('passwords.unit') }}</label>
                                        <select name="inputs[0][unit]" class="form-select" data-control="select2" data-placeholder="{{ __('passwords.select_unit') }}">
                                            <option value=""></option>
                                            @forelse($uoms as $uom)
                                                <option value="{{ $uom->id }}"
                                                        data-symbol="{{ $uom->symbol ?? $uom->name }}">
                                                    {{ $uom->name }}@if($uom->symbol) ({{ $uom->symbol }})@endif
                                                </option>
                                            @empty
                                                <option value="" disabled>{{ __('passwords.no_units_configured') }}</option>
                                            @endforelse
                                        </select>
                                        <div id="inputs.0.unit"></div>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('passwords.estimated_cost') }}</label>
                                        <input type="number" name="inputs[0][estimated_cost]" class="form-control input-cost" min="0" step="0.01" value="0">
                                        <div id="inputs.0.estimated_cost"></div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('passwords.batch_source') }}</label>
                                        <select name="inputs[0][purchase_receipt_item_id]" class="form-select batch-source-select" data-input-index="0">
                                            <option value="">{{ __('passwords.no_batch') }}</option>
                                        </select>
                                        <button type="button" class="btn btn-sm btn-info mt-1 load-batches-btn"
                                            data-input-index="0"
                                            onclick="ProductionCreate.loadAvailableBatches(0)">
                                            <i class="bi bi-arrow-repeat me-1"></i> {{ __('passwords.load_batches') }}
                                        </button>
                                        <div id="inputs.0.purchase_receipt_item_id"></div>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="button" class="btn btn-sm btn-danger w-100"
                                                onclick="ProductionCreate.removeInput(this)" disabled>
                                            <i class="bi bi-trash fs-5"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Input Summary -->
                            <div class="row mt-4 pt-3 border-top">
                                <div class="col-md-8"></div>
                                <div class="col-md-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-semibold">{{ __('passwords.total_input_cost') }}:</span>
                                        <span id="total_input_cost">0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Output Products Section -->
                        <div class="border rounded p-4 mb-8">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="fw-bold">
                                    <i class="bi bi-box-arrow-out me-2"></i>
                                    {{ __('passwords.output_products') }}
                                </h4>
                                <button type="button" class="btn btn-sm btn-light-primary" onclick="ProductionCreate.addOutput()">
                                    <i class="ki-duotone ki-plus fs-3"></i>
                                    {{ __('passwords._add_product') }}
                                </button>
                            </div>

                            <div id="production_outputs_container">
                                <div class="row g-4 mb-4 production-output-item" id="output_0">
                                    <div class="col-md-3">
                                        <label class="form-label required">{{ __('passwords.product') }}</label>
                                        <div class="position-relative">
                                            <input type="text" 
                                                id="product_search_output_0"
                                                class="form-control product-typable-input"
                                                list="product_list_output_0"
                                                placeholder="Type or select product..."
                                                autocomplete="off"
                                                data-item-index="0"
                                                data-type="output">
                                            <input type="hidden" 
                                                name="outputs[0][product_variant_id]" 
                                                id="product_id_output_0">
                                            <datalist id="product_list_output_0">
                                                <option value="">{{ __('passwords.select_material_first') }}</option>
                                                @foreach($variants as $variant)
                                                    <option value="{{ $variant->name }}" 
                                                            data-id="{{ $variant->id }}"
                                                            data-uom-id="{{ $variant->weight_unit }}"
                                                            data-selling-price="{{ $variant->selling_price }}"
                                                            data-inventory-strategy="{{ $variant->product?->resolvedInventoryStrategy() ?? 'quantity' }}">
                                                    </option>
                                                @endforeach
                                            </datalist>
                                        </div>
                                        <div id="outputs.0.product_variant_id"></div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label required">{{ __('passwords.quantity') }}</label>
                                        <input type="number" name="outputs[0][planned_quantity]" class="form-control output-quantity" min="0.01" step="0.01" value="1">
                                        <div id="outputs.0.planned_quantity"></div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('passwords.unit') }}</label>
                                        <select name="outputs[0][unit]" class="form-select" data-control="select2" data-placeholder="{{ __('passwords.select_unit') }}">
                                            <option value=""></option>
                                            @forelse($uoms as $uom)
                                                <option value="{{ $uom->id }}"
                                                        data-symbol="{{ $uom->symbol ?? $uom->name }}">
                                                    {{ $uom->name }}@if($uom->symbol) ({{ $uom->symbol }})@endif
                                                </option>
                                            @empty
                                                <option value="" disabled>{{ __('passwords.no_units_configured') }}</option>
                                            @endforelse
                                        </select>
                                        <div id="outputs.0.unit"></div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('passwords.inventory_strategy') }}</label>
                                        <select name="outputs[0][inventory_strategy]" class="form-select">
                                            <option value="quantity">{{ __('passwords.quantity_tracking') }}</option>
                                            <option value="batch">{{ __('passwords.batch_tracking') }}</option>
                                            <option value="serial">{{ __('passwords.serial_tracking') }}</option>
                                        </select>
                                        <div id="outputs.0.inventory_strategy"></div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('passwords.production_cost') }}</label>
                                        <input type="number" name="outputs[0][production_cost]" class="form-control output-cost" min="0" step="0.01" value="0">
                                        <div id="outputs.0.production_cost"></div>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="button" class="btn btn-sm btn-danger w-100"
                                                onclick="ProductionCreate.removeOutput(this)" disabled>
                                            <i class="bi bi-trash fs-5"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Output Summary -->
                            <div class="row mt-4 pt-3 border-top">
                                <div class="col-md-8"></div>
                                <div class="col-md-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-semibold">{{ __('passwords.total_output_cost') }}:</span>
                                        <span id="total_output_cost">0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Grand Summary -->
                        <div class="card card-flush bg-light-primary mb-6">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <span class="text-muted d-block">{{ __('passwords.total_input_cost') }}</span>
                                            <span class="fw-bold fs-3 text-danger" id="grand_total_input">0.00</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <span class="text-muted d-block">{{ __('passwords.total_output_cost') }}</span>
                                            <span class="fw-bold fs-3 text-success" id="grand_total_output">0.00</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <span class="text-muted d-block">{{ __('passwords.total_cost') }}</span>
                                            <span class="fw-bold fs-3 text-primary" id="grand_total_cost">0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <button type="reset" class="btn btn-light me-3" id="discardProductionOrderButton" data-bs-dismiss="modal">
                            {{ __('passwords._discard') }}
                        </button>
                        
                        <button id="submitProductionOrderButton"
                                type="button"
                                class="btn btn-primary"
                                onclick="ProductionCreate.submit()">
                            <span class="indicator-label">{{ __('passwords.create_production_order') }}</span>
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


@include('manufacturing.production-order.create-scripts')
