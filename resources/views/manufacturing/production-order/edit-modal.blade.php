{{-- resources/views/manufacturing/production-order/edit-modal.blade.php --}}
@php
    $isSingleShop = tenant_is_single_shop($order->tenant_id);
    $uomsById = $uoms->keyBy('id');
@endphp

<!-- Edit Production Order Modal -->
<div class="modal fade" id="editProductionModal{{ $order->id }}" 
     tabindex="-1" 
     aria-hidden="true" 
     data-bs-backdrop="static"
     data-location-id="{{ $order->location_id }}">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_edit_production_order_header">
                <h2 class="fw-bold">
                    <i class="ki-duotone ki-pencil fs-2 me-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    {{ __('passwords.edit_production_order') }} - {{ $order->production_number }}
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_edit_production_order_form_{{ $order->id }}" class="form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="order_id" value="{{ $order->id }}">

                    {{-- ─── BASIC INFORMATION ──────────────────────────────── --}}
                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-4">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">{{ __('passwords.location') }}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" 
                                   value="{{ $order->location->name ?? 'N/A' }}" disabled>
                        </div>

                        <div class="d-flex flex-column mb-8 fv-row col-md-4">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{ __('passwords.scheduled_date') }}</span>
                            </label>
                            <input type="datetime-local" 
                                   class="form-control form-control-solid" 
                                   name="scheduled_date"
                                   value="{{ $order->scheduled_date?->format('Y-m-d\TH:i') }}">
                        </div>

                        <div class="d-flex flex-column mb-8 fv-row col-md-4">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{ __('auth._status') }}</span>
                            </label>
                            <div class="d-flex align-items-center h-100">
                                <span class="badge badge-{{ $order->status_badge }} fw-bold fs-6">
                                    {{ $order->status_label }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-12">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{ __('passwords.notes') }}</span>
                            </label>
                            <textarea class="form-control form-control-solid" name="notes" rows="2"
                                      placeholder="{{ __('passwords.optional_notes') }}">{{ $order->notes }}</textarea>
                        </div>
                    </div>

                    {{-- ─── INPUT MATERIALS SECTION ────────────────────────── --}}
                    <div class="border rounded p-4 mb-8">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="fw-bold">
                                <i class="bi bi-box-arrow-in-down me-2"></i>
                                {{ __('passwords.input_materials') }}
                            </h4>
                            <button type="button" class="btn btn-sm btn-light-primary" 
                                    onclick="addEditProductionInput({{ $order->id }})">
                                <i class="ki-duotone ki-plus fs-3"></i>
                                {{ __('passwords._add_material') }}
                            </button>
                        </div>

                        <div id="edit_production_inputs_container_{{ $order->id }}">
                            @foreach($order->inputs as $input)
                                @php
                                    $variant    = $input->productVariant;
                                    $isConsumed = $input->actual_quantity > 0;
                                    $rowKey     = $input->id;
                                    $resolvedUnitId = $input->unit
                                        ?? optional($variant)->weight_unit
                                        ?? null;
                                @endphp
                                <div class="row g-4 mb-4 production-input-item" 
                                     id="edit_input_{{ $order->id }}_{{ $rowKey }}"
                                     data-input-id="{{ $input->id }}"
                                     data-consumed="{{ $isConsumed ? 'true' : 'false' }}"
                                     data-original-batch-id="{{ $input->purchase_receipt_item_id }}"
                                     data-original-consumed="{{ $input->actual_quantity }}">

                                    <div class="col-md-3">
                                        <label class="form-label required d-flex justify-content-between">
                                            <span>{{ __('passwords.material') }}</span>
                                            @if($isConsumed)
                                                <span class="badge badge-warning fs-8">
                                                    <i class="bi bi-lock-fill me-1"></i>
                                                    {{ __('passwords.consumed') }}: {{ number_format($input->actual_quantity, 2) }}
                                                </span>
                                            @endif
                                        </label>
                                        <div class="position-relative">
                                            <input type="text" 
                                                   id="edit_material_search_input_{{ $order->id }}_{{ $rowKey }}"
                                                   class="form-control edit-material-typable-input"
                                                   list="edit_material_list_input_{{ $order->id }}_{{ $rowKey }}"
                                                   placeholder="{{ __('passwords.type_or_select_material') }}"
                                                   autocomplete="off"
                                                   data-item-index="{{ $rowKey }}"
                                                   data-order-id="{{ $order->id }}"
                                                   data-type="input"
                                                   value="{{ $variant->name ?? '' }}">
                                            <input type="hidden" 
                                                   name="inputs[{{ $rowKey }}][id]"
                                                   value="{{ $input->id }}">
                                            <input type="hidden" 
                                                   name="inputs[{{ $rowKey }}][product_variant_id]" 
                                                   id="edit_material_id_input_{{ $order->id }}_{{ $rowKey }}"
                                                   value="{{ $input->product_variant_id }}">
                                            <datalist id="edit_material_list_input_{{ $order->id }}_{{ $rowKey }}">
                                                @foreach($variants as $v)
                                                    <option value="{{ $v->name }}" 
                                                            data-id="{{ $v->id }}"
                                                            data-uom-id="{{ $v->weight_unit }}"
                                                            data-cost-price="{{ $v->supplier_cost_price }}">
                                                    </option>
                                                @endforeach
                                            </datalist>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label required">{{ __('passwords.quantity') }}</label>
                                        <input type="number" 
                                               name="inputs[{{ $rowKey }}][planned_quantity]" 
                                               class="form-control input-quantity" 
                                               min="0.01" step="0.01"
                                               value="{{ $input->planned_quantity }}">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('passwords.unit') }}</label>
                                        <select name="inputs[{{ $rowKey }}][unit]" 
                                                class="form-select edit-input-unit-select"
                                                data-control="select2"
                                                data-placeholder="{{ __('passwords.select_unit') }}"
                                                data-selected-unit="{{ $resolvedUnitId }}">
                                            <option value=""></option>
                                            @forelse($uoms as $u)
                                                <option value="{{ $u->id }}"
                                                        data-symbol="{{ $u->symbol ?? $u->name }}"
                                                        @selected((string) $u->id === (string) $resolvedUnitId)>
                                                    {{ $u->name }}@if($u->symbol) ({{ $u->symbol }})@endif
                                                </option>
                                            @empty
                                                <option value="" disabled>{{ __('passwords.no_units_configured') }}</option>
                                            @endforelse
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('passwords.estimated_cost') }}</label>
                                        <input type="number" 
                                               name="inputs[{{ $rowKey }}][estimated_cost]" 
                                               class="form-control input-cost" 
                                               min="0" step="0.01"
                                               value="{{ $input->estimated_cost }}">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('passwords.batch_source') }}</label>
                                        <select name="inputs[{{ $rowKey }}][purchase_receipt_item_id]" 
                                                class="form-select batch-source-select"
                                                data-input-index="{{ $rowKey }}"
                                                data-order-id="{{ $order->id }}"
                                                data-selected-batch="{{ $input->purchase_receipt_item_id }}">
                                            <option value="">{{ __('passwords.no_batch') }}</option>
                                            @if($input->purchase_receipt_item_id && $input->purchaseReceiptItem)
                                                <option value="{{ $input->purchase_receipt_item_id }}" selected>
                                                    {{ $input->purchaseReceiptItem->batch_number }}
                                                    ({{ number_format($input->purchaseReceiptItem->quantity_remaining ?? 0, 2) }} {{ __('passwords.left') }})
                                                </option>
                                            @endif
                                        </select>
                                        <button type="button" 
                                                class="btn btn-sm btn-info mt-1 load-batches-btn"
                                                data-input-index="{{ $rowKey }}"
                                                data-order-id="{{ $order->id }}"
                                                onclick="loadEditAvailableBatches({{ $order->id }}, '{{ $rowKey }}')">
                                            <i class="bi bi-arrow-repeat me-1"></i> {{ __('passwords.load_batches') }}
                                        </button>
                                    </div>

                                    <div class="col-md-1">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger w-100" 
                                                onclick="removeEditProductionInput(this, {{ $order->id }}, '{{ $rowKey }}')">
                                            <i class="bi bi-trash fs-5"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Input Summary -->
                        <div class="row mt-4 pt-3 border-top">
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-semibold">{{ __('passwords.total_input_cost') }}:</span>
                                    <span id="edit_total_input_cost_{{ $order->id }}">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── OUTPUT PRODUCTS SECTION ────────────────────────── --}}
                    <div class="border rounded p-4 mb-8">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="fw-bold">
                                <i class="bi bi-box-arrow-out me-2"></i>
                                {{ __('passwords.output_products') }}
                            </h4>
                            <button type="button" class="btn btn-sm btn-light-primary" 
                                    onclick="addEditProductionOutput({{ $order->id }})">
                                <i class="ki-duotone ki-plus fs-3"></i>
                                {{ __('passwords._add_product') }}
                            </button>
                        </div>

                        <div id="edit_production_outputs_container_{{ $order->id }}">
                            @foreach($order->outputs as $output)
                                @php
                                    $variant = $output->productVariant;
                                    $rowKey  = $output->id;
                                    $resolvedUnitId = $output->unit
                                        ?? optional($variant)->weight_unit
                                        ?? null;
                                @endphp
                                <div class="row g-4 mb-4 production-output-item" 
                                     id="edit_output_{{ $order->id }}_{{ $rowKey }}"
                                     data-output-id="{{ $output->id }}">

                                    <div class="col-md-3">
                                        <label class="form-label required">{{ __('passwords.product') }}</label>
                                        <div class="position-relative">
                                            <input type="text" 
                                                   id="edit_product_search_output_{{ $order->id }}_{{ $rowKey }}"
                                                   class="form-control edit-product-typable-input"
                                                   list="edit_product_list_output_{{ $order->id }}_{{ $rowKey }}"
                                                   placeholder="{{ __('passwords.type_or_select_product') }}"
                                                   autocomplete="off"
                                                   data-item-index="{{ $rowKey }}"
                                                   data-order-id="{{ $order->id }}"
                                                   data-type="output"
                                                   value="{{ $variant->name ?? '' }}">
                                            <input type="hidden" 
                                                   name="outputs[{{ $rowKey }}][id]"
                                                   value="{{ $output->id }}">
                                            <input type="hidden" 
                                                   name="outputs[{{ $rowKey }}][product_variant_id]" 
                                                   id="edit_product_id_output_{{ $order->id }}_{{ $rowKey }}"
                                                   value="{{ $output->product_variant_id }}">
                                            <datalist id="edit_product_list_output_{{ $order->id }}_{{ $rowKey }}">
                                                @foreach($variants as $v)
                                                    <option value="{{ $v->name }}" 
                                                            data-id="{{ $v->id }}"
                                                            data-uom-id="{{ $v->weight_unit }}"
                                                            data-selling-price="{{ $v->selling_price }}"
                                                            data-inventory-strategy="{{ $v->product?->resolvedInventoryStrategy() ?? 'quantity' }}">
                                                    </option>
                                                @endforeach
                                            </datalist>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label required">{{ __('passwords.planned') }}</label>
                                        <input type="number" 
                                               name="outputs[{{ $rowKey }}][planned_quantity]" 
                                               class="form-control output-quantity" 
                                               min="0.01" step="0.01"
                                               value="{{ $output->planned_quantity }}">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('passwords.actual') }}</label>
                                        <input type="number" 
                                               name="outputs[{{ $rowKey }}][actual_quantity]" 
                                               class="form-control output-actual-quantity" 
                                               min="0" step="0.01"
                                               value="{{ $output->actual_quantity }}"
                                               data-old-value="{{ $output->actual_quantity }}"
                                               data-output-id="{{ $output->id }}">
                                    </div>

                                    <div class="col-md-1">
                                        <label class="form-label">{{ __('passwords.defective') }}</label>
                                        <input type="number" 
                                               name="outputs[{{ $rowKey }}][defective_quantity]" 
                                               class="form-control output-defective-quantity" 
                                               min="0" step="0.01"
                                               value="{{ $output->defective_quantity }}">
                                    </div>

                                    <div class="col-md-1">
                                        <label class="form-label">{{ __('passwords.unit') }}</label>
                                        <select name="outputs[{{ $rowKey }}][unit]" 
                                                class="form-select edit-output-unit-select"
                                                data-control="select2"
                                                data-selected-unit="{{ $resolvedUnitId }}">
                                            <option value=""></option>
                                            @forelse($uoms as $u)
                                                <option value="{{ $u->id }}" @selected((string) $u->id === (string) $resolvedUnitId)>
                                                    {{ $u->name }}
                                                </option>
                                            @empty
                                                <option value="" disabled>—</option>
                                            @endforelse
                                        </select>
                                    </div>

                                    <div class="col-md-1">
                                        <label class="form-label">{{ __('passwords.strategy') }}</label>
                                        <select name="outputs[{{ $rowKey }}][inventory_strategy]" 
                                                class="form-select output-strategy-select">
                                            <option value="quantity" @selected($output->inventory_strategy === 'quantity')>{{ __('passwords.quantity_tracking') }}</option>
                                            <option value="batch" @selected($output->inventory_strategy === 'batch')>{{ __('passwords.batch_tracking') }}</option>
                                            <option value="serial" @selected($output->inventory_strategy === 'serial')>{{ __('passwords.serial_tracking') }}</option>
                                        </select>
                                    </div>

                                    <div class="col-md-1">
                                        <label class="form-label">{{ __('passwords.production_cost') }}</label>
                                        <input type="number" 
                                               name="outputs[{{ $rowKey }}][production_cost]" 
                                               class="form-control output-cost" 
                                               min="0" step="0.01"
                                               value="{{ $output->production_cost }}">
                                    </div>

                                    <div class="col-md-1">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger w-100" 
                                                onclick="removeEditProductionOutput(this, {{ $order->id }}, '{{ $rowKey }}')">
                                            <i class="bi bi-trash fs-5"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Output Summary -->
                        <div class="row mt-4 pt-3 border-top">
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-semibold">{{ __('passwords.total_output_cost') }}:</span>
                                    <span id="edit_total_output_cost_{{ $order->id }}">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── GRAND SUMMARY ──────────────────────────────────── --}}
                    <div class="card card-flush bg-light-primary mb-6">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <span class="text-muted d-block">{{ __('passwords.total_input_cost') }}</span>
                                        <span class="fw-bold fs-3 text-danger" id="edit_grand_total_input_{{ $order->id }}">0.00</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <span class="text-muted d-block">{{ __('passwords.total_output_cost') }}</span>
                                        <span class="fw-bold fs-3 text-success" id="edit_grand_total_output_{{ $order->id }}">0.00</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <span class="text-muted d-block">{{ __('passwords.total_cost') }}</span>
                                        <span class="fw-bold fs-3 text-primary" id="edit_grand_total_cost_{{ $order->id }}">0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── WARNING BANNER ─────────────────────────────────── --}}
                    @if($order->status === 'in_progress')
                        <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4">
                            <i class="ki-duotone ki-information-5 fs-2tx text-warning me-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            <div class="d-flex flex-stack flex-grow-1">
                                <div class="fw-semibold">
                                    <h6 class="text-gray-900 fw-bold mb-1">{{ __('passwords.warning') }}</h6>
                                    <div class="fs-7 text-gray-700">
                                        {{ __('passwords.edit_in_progress_warning') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ─── ACTION BUTTONS ─────────────────────────────────── --}}
                    <div class="text-center pt-10">
                        <button type="reset" 
                                class="btn btn-light me-3" 
                                id="discardEditProductionOrderButton_{{ $order->id }}" 
                                data-bs-dismiss="modal">
                            {{ __('passwords._discard') }}
                        </button>
                        
                        <button id="submitEditProductionOrderButton_{{ $order->id }}" 
                                type="button" 
                                class="btn btn-primary"
                                onclick="submitEditProductionOrderForm({{ $order->id }})">
                            <span class="indicator-label">
                                <i class="ki-duotone ki-check fs-2 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ __('passwords.save_changes') }}
                            </span>
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

{{-- ─── HIDDEN TEMPLATES FOR NEW ROWS ──────────────────────────── --}}

<template id="editInputRowTemplate{{ $order->id }}">
    <div class="row g-4 mb-4 production-input-item" 
         id="edit_input_{{ $order->id }}____IDX__"
         data-input-id="___IDX__"
         data-consumed="false"
         data-original-batch-id=""
         data-original-consumed="0">
        <div class="col-md-3">
            <label class="form-label required">{{ __('passwords.material') }}</label>
            <div class="position-relative">
                <input type="text" 
                       id="edit_material_search_input_{{ $order->id }}____IDX__"
                       class="form-control edit-material-typable-input"
                       list="edit_material_list_input_{{ $order->id }}____IDX__"
                       placeholder="{{ __('passwords.type_or_select_material') }}"
                       autocomplete="off"
                       data-item-index="___IDX__"
                       data-order-id="{{ $order->id }}"
                       data-type="input">
                <input type="hidden" 
                       name="inputs[___IDX__][product_variant_id]" 
                       id="edit_material_id_input_{{ $order->id }}____IDX__">
                <datalist id="edit_material_list_input_{{ $order->id }}____IDX__">
                    @foreach($variants as $v)
                        <option value="{{ $v->name }}" 
                                data-id="{{ $v->id }}"
                                data-uom-id="{{ $v->weight_unit }}"
                                data-cost-price="{{ $v->supplier_cost_price }}">
                        </option>
                    @endforeach
                </datalist>
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label required">{{ __('passwords.quantity') }}</label>
            <input type="number" name="inputs[___IDX__][planned_quantity]" 
                   class="form-control input-quantity" min="0.01" step="0.01" value="1">
        </div>
        <div class="col-md-2">
            <label class="form-label">{{ __('passwords.unit') }}</label>
            <select name="inputs[___IDX__][unit]" 
                    class="form-select edit-input-unit-select"
                    data-control="select2"
                    data-placeholder="{{ __('passwords.select_unit') }}">
                <option value=""></option>
                @forelse($uoms as $u)
                    <option value="{{ $u->id }}" data-symbol="{{ $u->symbol ?? $u->name }}">
                        {{ $u->name }}@if($u->symbol) ({{ $u->symbol }})@endif
                    </option>
                @empty
                    <option value="" disabled>{{ __('passwords.no_units_configured') }}</option>
                @endforelse
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">{{ __('passwords.estimated_cost') }}</label>
            <input type="number" name="inputs[___IDX__][estimated_cost]" 
                   class="form-control input-cost" min="0" step="0.01" value="0">
        </div>
        <div class="col-md-2">
            <label class="form-label">{{ __('passwords.batch_source') }}</label>
            <select name="inputs[___IDX__][purchase_receipt_item_id]" 
                    class="form-select batch-source-select"
                    data-input-index="___IDX__"
                    data-order-id="{{ $order->id }}">
                <option value="">{{ __('passwords.no_batch') }}</option>
            </select>
            <button type="button" class="btn btn-sm btn-info mt-1 load-batches-btn"
                    data-input-index="___IDX__"
                    data-order-id="{{ $order->id }}"
                    onclick="loadEditAvailableBatches({{ $order->id }}, '___IDX__')">
                <i class="bi bi-arrow-repeat me-1"></i> {{ __('passwords.load_batches') }}
            </button>
        </div>
        <div class="col-md-1">
            <label class="form-label">&nbsp;</label>
            <button type="button" class="btn btn-sm btn-danger w-100" 
                    onclick="removeEditProductionInput(this, {{ $order->id }}, '___IDX__')">
                <i class="bi bi-trash fs-5"></i>
            </button>
        </div>
    </div>
</template>

<template id="editOutputRowTemplate{{ $order->id }}">
    <div class="row g-4 mb-4 production-output-item" 
         id="edit_output_{{ $order->id }}____IDX__"
         data-output-id="___IDX__">
        <div class="col-md-3">
            <label class="form-label required">{{ __('passwords.product') }}</label>
            <div class="position-relative">
                <input type="text" 
                       id="edit_product_search_output_{{ $order->id }}____IDX__"
                       class="form-control edit-product-typable-input"
                       list="edit_product_list_output_{{ $order->id }}____IDX__"
                       placeholder="{{ __('passwords.type_or_select_product') }}"
                       autocomplete="off"
                       data-item-index="___IDX__"
                       data-order-id="{{ $order->id }}"
                       data-type="output">
                <input type="hidden" 
                       name="outputs[___IDX__][product_variant_id]" 
                       id="edit_product_id_output_{{ $order->id }}____IDX__">
                <datalist id="edit_product_list_output_{{ $order->id }}____IDX__">
                    @foreach($variants as $v)
                        <option value="{{ $v->name }}" 
                                data-id="{{ $v->id }}"
                                data-uom-id="{{ $v->weight_unit }}"
                                data-selling-price="{{ $v->selling_price }}"
                                data-inventory-strategy="{{ $v->product?->resolvedInventoryStrategy() ?? 'quantity' }}">
                        </option>
                    @endforeach
                </datalist>
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label required">{{ __('passwords.planned') }}</label>
            <input type="number" name="outputs[___IDX__][planned_quantity]" 
                   class="form-control output-quantity" min="0.01" step="0.01" value="1">
        </div>
        <div class="col-md-2">
            <label class="form-label">{{ __('passwords.actual') }}</label>
            <input type="number" name="outputs[___IDX__][actual_quantity]" 
                   class="form-control output-actual-quantity" min="0" step="0.01" 
                   value="0" data-old-value="0">
        </div>
        <div class="col-md-1">
            <label class="form-label">{{ __('passwords.defective') }}</label>
            <input type="number" name="outputs[___IDX__][defective_quantity]" 
                   class="form-control output-defective-quantity" min="0" step="0.01" value="0">
        </div>
        <div class="col-md-1">
            <label class="form-label">{{ __('passwords.unit') }}</label>
            <select name="outputs[___IDX__][unit]" 
                    class="form-select edit-output-unit-select"
                    data-control="select2">
                <option value=""></option>
                @forelse($uoms as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @empty
                    <option value="" disabled>—</option>
                @endforelse
            </select>
        </div>
        <div class="col-md-1">
            <label class="form-label">{{ __('passwords.strategy') }}</label>
            <select name="outputs[___IDX__][inventory_strategy]" 
                    class="form-select output-strategy-select">
                <option value="quantity">{{ __('passwords.quantity_tracking') }}</option>
                <option value="batch">{{ __('passwords.batch_tracking') }}</option>
                <option value="serial">{{ __('passwords.serial_tracking') }}</option>
            </select>
        </div>
        <div class="col-md-1">
            <label class="form-label">{{ __('passwords.production_cost') }}</label>
            <input type="number" name="outputs[___IDX__][production_cost]" 
                   class="form-control output-cost" min="0" step="0.01" value="0">
        </div>
        <div class="col-md-1">
            <label class="form-label">&nbsp;</label>
            <button type="button" class="btn btn-sm btn-danger w-100" 
                    onclick="removeEditProductionOutput(this, {{ $order->id }}, '___IDX__')">
                <i class="bi bi-trash fs-5"></i>
            </button>
        </div>
    </div>
</template>

@include('manufacturing.production-order.edit-modal-scripts')