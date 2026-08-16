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
                                <button type="button" class="btn btn-sm btn-light-primary" onclick="addProductionInput()">
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
                                                <option value="">Select material</option>
                                                @foreach($variants as $variant)
                                                    <option value="{{ $variant->name }}" 
                                                            data-id="{{ $variant->id }}"
                                                            data-cost-price="{{ $variant->supplier_cost_price }}">
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
                                        <select name="inputs[0][unit]" class="form-select">
                                            <option value="kg">kg</option>
                                            <option value="g">g</option>
                                            <option value="l">l</option>
                                            <option value="ml">ml</option>
                                            <option value="pcs">pcs</option>
                                            <option value="units">units</option>
                                            <option value="litres">litres</option>
                                            <option value="grams">grams</option>
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
                                        <button type="button" class="btn btn-sm btn-info mt-1 load-batches-btn" data-input-index="0" onclick="loadAvailableBatches(0)">
                                            <i class="bi bi-arrow-repeat me-1"></i> {{ __('passwords.load_batches') }}
                                        </button>
                                        <div id="inputs.0.purchase_receipt_item_id"></div>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="button" class="btn btn-sm btn-danger w-100" onclick="removeProductionInput(this)" disabled>
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
                                <button type="button" class="btn btn-sm btn-light-primary" onclick="addProductionOutput()">
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
                                                <option value="">Select product</option>
                                                @foreach($variants as $variant)
                                                    <option value="{{ $variant->name }}" 
                                                            data-id="{{ $variant->id }}"
                                                            data-selling-price="{{ $variant->selling_price }}">
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
                                        <select name="outputs[0][unit]" class="form-select">
                                            <option value="kg">kg</option>
                                            <option value="g">g</option>
                                            <option value="l">l</option>
                                            <option value="ml">ml</option>
                                            <option value="pcs">pcs</option>
                                            <option value="units">units</option>
                                            <option value="litres">litres</option>
                                            <option value="grams">grams</option>
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
                                        <button type="button" class="btn btn-sm btn-danger w-100" onclick="removeProductionOutput(this)" disabled>
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
                        
                        <button 
                            id="submitProductionOrderButton" 
                            type="button" 
                            class="btn btn-primary"
                            onclick="submitProductionOrderForm(
                                'kt_modal_add_production_order_form', 
                                'submitProductionOrderButton',
                                '{{ route('production-orders.store') }}',
                                'POST',
                                'discardProductionOrderButton'
                            )">
                            
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

@push('scripts')
<script>
    let inputIndex = 1;
    let outputIndex = 1;

    function addProductionInput() {
        const container = document.getElementById('production_inputs_container');
        const template = document.getElementById('input_0');
        const newRow = template.cloneNode(true);
        
        const newId = `input_${inputIndex}`;
        newRow.id = newId;
        
        newRow.querySelectorAll('[name]').forEach(el => {
            const name = el.getAttribute('name');
            if (name) {
                el.setAttribute('name', name.replace('[0]', `[${inputIndex}]`));
            }
        });
        
        const searchInput = newRow.querySelector('.material-typable-input');
        if (searchInput) {
            searchInput.id = `material_search_input_${inputIndex}`;
            searchInput.setAttribute('data-item-index', inputIndex);
            searchInput.setAttribute('data-type', 'input');
            searchInput.value = '';
        }
        
        const datalist = newRow.querySelector('datalist');
        if (datalist) {
            datalist.id = `material_list_input_${inputIndex}`;
        }
        
        const hiddenId = newRow.querySelector('input[name*="[product_variant_id]"]');
        if (hiddenId) {
            hiddenId.id = `material_id_input_${inputIndex}`;
            hiddenId.value = '';
        }
        
        const batchSelect = newRow.querySelector('.batch-source-select');
        if (batchSelect) {
            batchSelect.setAttribute('data-input-index', inputIndex);
            batchSelect.innerHTML = '<option value="">{{ __("passwords.no_batch") }}</option>';
        }
        
        const loadBtn = newRow.querySelector('.load-batches-btn');
        if (loadBtn) {
            loadBtn.setAttribute('data-input-index', inputIndex);
            loadBtn.setAttribute('onclick', `loadAvailableBatches(${inputIndex})`);
        }
        
        const removeBtn = newRow.querySelector('.btn-danger');
        if (removeBtn) {
            removeBtn.disabled = false;
        }
        
        newRow.querySelectorAll('.input-quantity, .input-cost').forEach(el => {
            el.value = el.type === 'number' ? '0' : '';
        });
        
        container.appendChild(newRow);
        inputIndex++;
        updateTotals();
    }

    function addProductionOutput() {
        const container = document.getElementById('production_outputs_container');
        const template = document.getElementById('output_0');
        const newRow = template.cloneNode(true);
        
        const newId = `output_${outputIndex}`;
        newRow.id = newId;
        
        newRow.querySelectorAll('[name]').forEach(el => {
            const name = el.getAttribute('name');
            if (name) {
                el.setAttribute('name', name.replace('[0]', `[${outputIndex}]`));
            }
        });
        
        const searchInput = newRow.querySelector('.product-typable-input');
        if (searchInput) {
            searchInput.id = `product_search_output_${outputIndex}`;
            searchInput.setAttribute('data-item-index', outputIndex);
            searchInput.setAttribute('data-type', 'output');
            searchInput.value = '';
        }
        
        const datalist = newRow.querySelector('datalist');
        if (datalist) {
            datalist.id = `product_list_output_${outputIndex}`;
        }
        
        const hiddenId = newRow.querySelector('input[name*="[product_variant_id]"]');
        if (hiddenId) {
            hiddenId.id = `product_id_output_${outputIndex}`;
            hiddenId.value = '';
        }
        
        const removeBtn = newRow.querySelector('.btn-danger');
        if (removeBtn) {
            removeBtn.disabled = false;
        }
        
        newRow.querySelectorAll('.output-quantity, .output-cost').forEach(el => {
            el.value = el.type === 'number' ? '0' : '';
        });
        
        container.appendChild(newRow);
        outputIndex++;
        updateTotals();
    }

    function removeProductionInput(btn) {
        const row = btn.closest('.production-input-item');
        if (row && document.querySelectorAll('.production-input-item').length > 1) {
            row.remove();
            updateTotals();
        }
    }

    function removeProductionOutput(btn) {
        const row = btn.closest('.production-output-item');
        if (row && document.querySelectorAll('.production-output-item').length > 1) {
            row.remove();
            updateTotals();
        }
    }

    // ✅ FIXED: loadAvailableBatches - uses the correct hidden input ID
    function loadAvailableBatches(index) {
        const row = document.getElementById(`input_${index}`);
        if (!row) {
            console.error('Row not found for index:', index);
            return;
        }
        
        // ✅ Get the hidden input by its ID pattern
        const hiddenInput = document.getElementById(`material_id_input_${index}`);
        const variantId = hiddenInput ? hiddenInput.value : null;
        
        if (!variantId) {
            Swal.fire({
                title: '{{ __("passwords.info") }}',
                text: '{{ __("passwords.select_material_first") }}',
                icon: 'info',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }
        
        const locationId = document.querySelector('select[name="location_id"]')?.value || '';
        
        Swal.fire({
            title: '{{ __("passwords.loading_batches") }}',
            text: '{{ __("passwords.please_wait") }}',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        
        fetch(`/production-orders/available-batches?variant_id=${variantId}&location_id=${locationId}`)
            .then(response => response.json())
            .then(data => {
                Swal.close();
                
                if (data.success && data.batches.length > 0) {
                    const select = row.querySelector('.batch-source-select');
                    if (select) {
                        select.innerHTML = '<option value="">{{ __("passwords.no_batch") }}</option>';
                        data.batches.forEach(batch => {
                            const option = document.createElement('option');
                            option.value = batch.id;
                            const quantity = batch.quantity_remaining || 0;
                            option.textContent = `${batch.batch_number} (${quantity} units)`;
                            if (batch.expiry_date) {
                                option.textContent += ` - Expires: ${batch.expiry_date}`;
                            }
                            select.appendChild(option);
                        });
                        
                        Swal.fire({
                            title: '{{ __("passwords.success") }}',
                            text: `{{ __("passwords.batches_loaded") }} ${data.batches.length}`,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                } else {
                    Swal.fire({
                        title: '{{ __("passwords.info") }}',
                        text: '{{ __("passwords.no_batches_available") }}',
                        icon: 'info',
                        confirmButtonColor: '#0d6efd'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error loading batches:', error);
                Swal.fire({
                    title: '{{ __("passwords.error") }}',
                    text: '{{ __("passwords.failed_to_load_batches") }}',
                    icon: 'error',
                    confirmButtonColor: '#0d6efd'
                });
            });
    }


    document.addEventListener('DOMContentLoaded', function() {
        // Setup for material inputs
        document.addEventListener('input', function(e) {
            const target = e.target;
            
            // ✅ Fix: Check for both input and output typable fields
            if (target.classList.contains('material-typable-input') || target.classList.contains('product-typable-input')) {
                const input = target;
                const index = input.getAttribute('data-item-index');
                const type = input.getAttribute('data-type') || 'material';
                
                // ✅ Use the correct ID pattern based on type
                let datalistId, hiddenId;
                if (type === 'input' || type === 'material') {
                    datalistId = `material_list_input_${index}`;
                    hiddenId = `material_id_input_${index}`;
                } else {
                    datalistId = `product_list_output_${index}`;
                    hiddenId = `product_id_output_${index}`;
                }
                
                const datalist = document.getElementById(datalistId);
                const hidden = document.getElementById(hiddenId);
                
                if (datalist && hidden) {
                    const options = datalist.querySelectorAll('option');
                    let found = false;
                    options.forEach(opt => {
                        if (opt.value === input.value) {
                            hidden.value = opt.getAttribute('data-id');
                            found = true;
                            
                            // Load batches if material input
                            if (type === 'input' || type === 'material') {
                                const row = document.getElementById(`input_${index}`);
                                if (row) {
                                    const loadBtn = row.querySelector('.load-batches-btn');
                                    if (loadBtn) {
                                        loadAvailableBatches(parseInt(index));
                                    }
                                }
                            }
                        }
                    });
                    if (!found) {
                        hidden.value = '';
                    }
                }
            }
        });

        // Calculate totals on input change
        document.addEventListener('change', function(e) {
            const target = e.target;
            if (target.classList.contains('input-quantity') || 
                target.classList.contains('input-cost') ||
                target.classList.contains('output-quantity') || 
                target.classList.contains('output-cost')) {
                updateTotals();
            }
        });

        document.addEventListener('input', function(e) {
            const target = e.target;
            if (target.classList.contains('input-quantity') || 
                target.classList.contains('input-cost') ||
                target.classList.contains('output-quantity') || 
                target.classList.contains('output-cost')) {
                updateTotals();
            }
        });
    });

    function updateTotals() {
        let totalInputCost = 0;
        let totalOutputCost = 0;
        
        document.querySelectorAll('.production-input-item').forEach(row => {
            const quantity = parseFloat(row.querySelector('.input-quantity')?.value) || 0;
            const cost = parseFloat(row.querySelector('.input-cost')?.value) || 0;
            totalInputCost += quantity * cost;
        });
        
        document.querySelectorAll('.production-output-item').forEach(row => {
            const quantity = parseFloat(row.querySelector('.output-quantity')?.value) || 0;
            const cost = parseFloat(row.querySelector('.output-cost')?.value) || 0;
            totalOutputCost += quantity * cost;
        });
        
        document.getElementById('total_input_cost').textContent = totalInputCost.toFixed(2);
        document.getElementById('total_output_cost').textContent = totalOutputCost.toFixed(2);
        document.getElementById('grand_total_input').textContent = totalInputCost.toFixed(2);
        document.getElementById('grand_total_output').textContent = totalOutputCost.toFixed(2);
        document.getElementById('grand_total_cost').textContent = (totalInputCost + totalOutputCost).toFixed(2);
    }

    // ── SUBMIT PRODUCTION ORDER FORM ──────────────────────────────────────
    function submitProductionOrderForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardProductionOrderButton') {
        const form = document.getElementById(formId);
        const submitButton = document.getElementById(submitButtonId);

        if (!form || !submitButton) {
            console.error('Form or button not found:', formId, submitButtonId);
            return;
        }

        // Collect form data using FormData directly
        const formData = new FormData(form);

        // Convert FormData to proper nested structure
        const data = {};
        for (let [key, value] of formData.entries()) {
            // Handle inputs nested structure
            if (key.startsWith('inputs[')) {
                const matches = key.match(/inputs\[(\d+)\]\[(.+)\]/);
                if (matches) {
                    const index = matches[1];
                    const field = matches[2];
                    if (!data.inputs) data.inputs = [];
                    if (!data.inputs[index]) data.inputs[index] = {};
                    data.inputs[index][field] = value;
                }
            }
            // Handle outputs nested structure
            else if (key.startsWith('outputs[')) {
                const matches = key.match(/outputs\[(\d+)\]\[(.+)\]/);
                if (matches) {
                    const index = matches[1];
                    const field = matches[2];
                    if (!data.outputs) data.outputs = [];
                    if (!data.outputs[index]) data.outputs[index] = {};
                    data.outputs[index][field] = value;
                }
            }
            // Handle simple fields
            else {
                data[key] = value;
            }
        }

        // ✅ Filter out empty/invalid inputs
        if (data.inputs) {
            data.inputs = data.inputs.filter(input => 
                input.product_variant_id && 
                input.planned_quantity && 
                parseFloat(input.planned_quantity) > 0
            );
        }

        // ✅ Filter out empty/invalid outputs
        if (data.outputs) {
            data.outputs = data.outputs.filter(output => 
                output.product_variant_id && 
                output.planned_quantity && 
                parseFloat(output.planned_quantity) > 0
            );
        }

        // ✅ Validation
        if (!data.inputs || data.inputs.length === 0) {
            Swal.fire({
                title: '{{ __("passwords.validation_error") }}',
                text: '{{ __("passwords.add_at_least_one_input") }}',
                icon: 'warning',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }

        if (!data.outputs || data.outputs.length === 0) {
            Swal.fire({
                title: '{{ __("passwords.validation_error") }}',
                text: '{{ __("passwords.add_at_least_one_output") }}',
                icon: 'warning',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }

        // Add method and routeName
        data._method = method;
        data.routeName = url;

        // Start loading
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Pass handling + data to reusable handler
        handleFormSubmission(data, submitButton, discardButtonId);
    }
</script>
@endpush