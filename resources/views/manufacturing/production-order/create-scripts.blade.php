{{-- ═══════════════════════════════════════════════════════════════
     CREATE MODAL — vanilla JS, no LiveBlade
     ═══════════════════════════════════════════════════════════════ --}}
<script>
window.ProductionCreate = (function () {
    'use strict';

    // ── Config ───────────────────────────────────────────────────
    const ROUTES = {
        store:   '{{ route("production-orders.store") }}',
        batches: '{{ route("production-orders.available-batches") }}',
    };
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const MODAL_ID = 'kt_modal_add_production_order';
    const FORM_ID  = 'kt_modal_add_production_order_form';
    const BTN_ID   = 'submitProductionOrderButton';

    // ── Row counters ─────────────────────────────────────────────
    let inputIndex  = 1;
    let outputIndex = 1;

    // ── Concurrency guards ───────────────────────────────────────
    const variantDebounces   = {};   // per-row setTimeout handle
    const batchLoadInFlight  = {};   // per-row in-flight flag

    // ── Select2 helpers ──────────────────────────────────────────
    function resetSelect2InRow(row) {
        row.querySelectorAll('.select2-container').forEach(el => el.remove());

        row.querySelectorAll('select').forEach(sel => {
            sel.classList.remove('select2-hidden-accessible');
            sel.removeAttribute('data-select2-id');
            sel.removeAttribute('aria-hidden');
            sel.removeAttribute('tabindex');
            sel.value = '';
        });

        row.querySelectorAll('[data-select2-id]').forEach(el => {
            el.removeAttribute('data-select2-id');
        });
    }

    function initSelect2InRow(row) {
        if (typeof $ === 'undefined' || !$.fn || !$.fn.select2) return;

        $(row).find('select[data-control="select2"]').each(function () {
            if ($(this).hasClass('select2-hidden-accessible')) {
                try { $(this).select2('destroy'); } catch (e) {}
            }
            $(this).select2({
                dropdownParent: $('#' + MODAL_ID),
                width: '100%',
                placeholder: $(this).data('placeholder') || '{{ __("passwords.select_unit") }}',
                allowClear: true,
            });
        });
    }

    // ── Read location from x-typable-select's hidden input ───────
    function getModalLocationId() {
        const modal = document.getElementById(MODAL_ID);
        if (!modal) return '';

        let el = modal.querySelector('input[type="hidden"][name="location_id"]');
        if (el && el.value) return el.value;

        el = modal.querySelector('input[type="hidden"]#location_id');
        if (el && el.value) return el.value;

        el = modal.querySelector('select[name="location_id"]');
        if (el && el.value) return el.value;

        el = modal.querySelector('.fv-row input[name="location_id"]');
        if (el && el.value) return el.value;

        return '';
    }

    // ── Add / remove rows ────────────────────────────────────────
    function addInput() {
        const container = document.getElementById('production_inputs_container');
        const template  = document.getElementById('input_0');
        if (!container || !template) return;

        const newRow = template.cloneNode(true);
        newRow.id = `input_${inputIndex}`;

        newRow.querySelectorAll('[name]').forEach(el => {
            const name = el.getAttribute('name');
            if (name) el.setAttribute('name', name.replace('[0]', `[${inputIndex}]`));
        });

        const searchInput = newRow.querySelector('.material-typable-input');
        if (searchInput) {
            searchInput.id = `material_search_input_${inputIndex}`;
            searchInput.setAttribute('data-item-index', inputIndex);
            searchInput.setAttribute('data-type', 'input');
            searchInput.setAttribute('list', `material_list_input_${inputIndex}`);
            searchInput.value = '';
        }

        const datalist = newRow.querySelector('datalist');
        if (datalist) datalist.id = `material_list_input_${inputIndex}`;

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
            loadBtn.setAttribute('onclick', `ProductionCreate.loadAvailableBatches(${inputIndex})`);
        }

        const removeBtn = newRow.querySelector('.btn-danger');
        if (removeBtn) {
            removeBtn.disabled = false;
            removeBtn.setAttribute('onclick', 'ProductionCreate.removeInput(this)');
        }

        newRow.querySelectorAll('.input-quantity, .input-cost').forEach(el => {
            el.value = el.type === 'number' ? '0' : '';
        });

        resetSelect2InRow(newRow);
        container.appendChild(newRow);
        initSelect2InRow(newRow);

        inputIndex++;
        updateTotals();
    }

    function addOutput() {
        const container = document.getElementById('production_outputs_container');
        const template  = document.getElementById('output_0');
        if (!container || !template) return;

        const newRow = template.cloneNode(true);
        newRow.id = `output_${outputIndex}`;

        newRow.querySelectorAll('[name]').forEach(el => {
            const name = el.getAttribute('name');
            if (name) el.setAttribute('name', name.replace('[0]', `[${outputIndex}]`));
        });

        const searchInput = newRow.querySelector('.product-typable-input');
        if (searchInput) {
            searchInput.id = `product_search_output_${outputIndex}`;
            searchInput.setAttribute('data-item-index', outputIndex);
            searchInput.setAttribute('data-type', 'output');
            searchInput.setAttribute('list', `product_list_output_${outputIndex}`);
            searchInput.value = '';
        }

        const datalist = newRow.querySelector('datalist');
        if (datalist) datalist.id = `product_list_output_${outputIndex}`;

        const hiddenId = newRow.querySelector('input[name*="[product_variant_id]"]');
        if (hiddenId) {
            hiddenId.id = `product_id_output_${outputIndex}`;
            hiddenId.value = '';
        }

        const removeBtn = newRow.querySelector('.btn-danger');
        if (removeBtn) {
            removeBtn.disabled = false;
            removeBtn.setAttribute('onclick', 'ProductionCreate.removeOutput(this)');
        }

        newRow.querySelectorAll('.output-quantity, .output-cost').forEach(el => {
            el.value = el.type === 'number' ? '0' : '';
        });

        const invStrategy = newRow.querySelector('select[name*="[inventory_strategy]"]');
        if (invStrategy) invStrategy.value = 'quantity';

        resetSelect2InRow(newRow);
        container.appendChild(newRow);
        initSelect2InRow(newRow);

        outputIndex++;
        updateTotals();
    }

    function removeInput(btn) {
        const row = btn.closest('.production-input-item');
        if (row && document.querySelectorAll('.production-input-item').length > 1) {
            row.remove();
            updateTotals();
        }
    }

    function removeOutput(btn) {
        const row = btn.closest('.production-output-item');
        if (row && document.querySelectorAll('.production-output-item').length > 1) {
            row.remove();
            updateTotals();
        }
    }

    // ── Batches ──────────────────────────────────────────────────
    function handleBatchSelected(selectEl) {
        const row = selectEl.closest('.production-input-item');
        if (!row) return;

        const selectedOption = selectEl.options[selectEl.selectedIndex];
        if (!selectedOption || !selectedOption.value) return;

        const qty = parseFloat(selectedOption.dataset.quantityRemaining) || 0;
        const qtyInput = row.querySelector('.input-quantity');

        if (qtyInput && qty > 0) {
            qtyInput.value = qty;
            qtyInput.dispatchEvent(new Event('input',  { bubbles: true }));
            qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        updateTotals();
    }

    async function loadAvailableBatches(index) {
        const row = document.getElementById(`input_${index}`);
        if (!row) return;

        // ── Concurrency guard: one fetch per row at a time ──────
        if (batchLoadInFlight[index]) {
            return;
        }

        const hiddenInput = document.getElementById(`material_id_input_${index}`);
        const variantId   = hiddenInput ? hiddenInput.value : null;

        if (!variantId) {
            Swal.fire({
                title: '{{ __("passwords.info") }}',
                text: '{{ __("passwords.select_material_first") }}',
                icon: 'info',
                confirmButtonColor: '#0d6efd',
            });
            return;
        }

        const locationId = getModalLocationId();

        batchLoadInFlight[index] = true;

        Swal.fire({
            title: '{{ __("passwords.loading_batches") }}',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
        });

        try {
            const url = new URL(ROUTES.batches, window.location.origin);
            url.searchParams.set('variant_id', variantId);
            if (locationId) url.searchParams.set('location_id', locationId);

            const res  = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();

            Swal.close();

            if (data.success && Array.isArray(data.batches) && data.batches.length > 0) {
                const select = row.querySelector('.batch-source-select');
                if (!select) return;

                select.innerHTML = '<option value="">{{ __("passwords.no_batch") }}</option>';

                data.batches.forEach(batch => {
                    const option = document.createElement('option');
                    option.value = batch.id;

                    const quantity = batch.quantity_remaining || 0;
                    option.textContent = `${batch.batch_number} (${quantity} units)`;
                    if (batch.expiry_date) {
                        option.textContent += ` - Expires: ${batch.expiry_date}`;
                    }

                    option.dataset.quantityRemaining = quantity;
                    option.dataset.unitCost          = batch.unit_cost ?? '';

                    select.appendChild(option);
                });

                handleBatchSelected(select);
            } else {
                Swal.fire({
                    title: '{{ __("passwords.info") }}',
                    text: '{{ __("passwords.no_batches_available") }}',
                    icon: 'info',
                    confirmButtonColor: '#0d6efd',
                });
            }
        } catch (err) {
            Swal.close();
            console.error('[production-create] batch fetch failed:', err);
            Swal.fire({
                title: '{{ __("passwords.error") }}',
                text: '{{ __("passwords.failed_to_load_batches") }}',
                icon: 'error',
                confirmButtonColor: '#0d6efd',
            });
        } finally {
            batchLoadInFlight[index] = false;
        }
    }

    // ── Variant auto-select ──────────────────────────────────────
    function handleVariantSelected(inputEl) {
        const index   = inputEl.getAttribute('data-item-index');
        const type    = inputEl.getAttribute('data-type') || 'material';
        const isInput = (type === 'input' || type === 'material');

        const datalistId = isInput ? `material_list_input_${index}`  : `product_list_output_${index}`;
        const hiddenId   = isInput ? `material_id_input_${index}`    : `product_id_output_${index}`;
        const rowId      = isInput ? `input_${index}`                : `output_${index}`;

        const datalist = document.getElementById(datalistId);
        const hidden   = document.getElementById(hiddenId);
        const row      = document.getElementById(rowId);

        if (!datalist || !hidden || !row) return;

        // Clear any pending debounce for this row
        if (variantDebounces[rowId]) {
            clearTimeout(variantDebounces[rowId]);
            variantDebounces[rowId] = null;
        }

        // Only commit when the typed value EXACTLY matches an option
        let matched = null;
        datalist.querySelectorAll('option').forEach(opt => {
            if (opt.value === inputEl.value) matched = opt;
        });

        if (!matched) {
            hidden.value = '';
            return;
        }

        const newId = matched.getAttribute('data-id');

        // If the same variant is already committed, don't re-process
        if (hidden.value && hidden.value === newId) {
            return;
        }

        hidden.value = newId;

        // Auto-select UOM
        const uomId = matched.getAttribute('data-uom-id');
        const uomSelect = row.querySelector('select[name*="[unit]"]');

        if (uomSelect && uomId) {
            const found = Array.from(uomSelect.options).some(
                opt => String(opt.value) === String(uomId)
            );

            if (found) {
                uomSelect.value = uomId;
                if (typeof $ !== 'undefined' && $.fn.select2 && $(uomSelect).hasClass('select2-hidden-accessible')) {
                    $(uomSelect).val(uomId).trigger('change.select2');
                } else {
                    uomSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        }

        // Auto-fill inventory strategy (outputs only)
        if (!isInput) {
            const strategy = matched.getAttribute('data-inventory-strategy');
            const strategySelect = row.querySelector('select[name*="[inventory_strategy]"]');
            if (strategySelect && strategy) {
                strategySelect.value = strategy;
                strategySelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // Inside handleVariantSelected(inputEl), after setting strategy
        if (isInput) {
            const strategy = matched.getAttribute('data-inventory-strategy');
            const batchSelect = row.querySelector('.batch-source-select');
            const batchLabel  = batchSelect?.closest('.col-md-2')?.querySelector('.form-label');

            if (strategy === 'batch' && batchLabel) {
                // Add a "required" indicator
                if (!batchLabel.querySelector('.required')) {
                    batchLabel.insertAdjacentHTML('beforeend',
                        ' <span class="required text-danger">*</span>');
                }
            } else if (batchLabel) {
                // Remove the indicator if strategy isn't batch
                batchLabel.querySelectorAll('.required').forEach(el => el.remove());
            }

            loadAvailableBatches(parseInt(index, 10));
        }

        // Auto-load batches for inputs — debounced
        if (isInput) {
            variantDebounces[rowId] = setTimeout(() => {
                variantDebounces[rowId] = null;
                loadAvailableBatches(parseInt(index, 10));
            }, 250);
        }
    }

    // ── Totals ───────────────────────────────────────────────────
    function updateTotals() {
        let totalInputCost  = 0;
        let totalOutputCost = 0;

        document.querySelectorAll('.production-input-item').forEach(row => {
            const qty  = parseFloat(row.querySelector('.input-quantity')?.value) || 0;
            const cost = parseFloat(row.querySelector('.input-cost')?.value)     || 0;
            totalInputCost += qty * cost;
        });

        document.querySelectorAll('.production-output-item').forEach(row => {
            const qty  = parseFloat(row.querySelector('.output-quantity')?.value) || 0;
            const cost = parseFloat(row.querySelector('.output-cost')?.value)     || 0;
            totalOutputCost += qty * cost;
        });

        const setText = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val.toFixed(2);
        };

        setText('total_input_cost',   totalInputCost);
        setText('total_output_cost',  totalOutputCost);
        setText('grand_total_input',  totalInputCost);
        setText('grand_total_output', totalOutputCost);
        setText('grand_total_cost',   totalInputCost + totalOutputCost);
    }

    // ── Submit ───────────────────────────────────────────────────
    function toggleBusy(btn, busy) {
        const label    = btn.querySelector('.indicator-label');
        const progress = btn.querySelector('.indicator-progress');
        btn.disabled = busy;
        if (label)    label.style.display    = busy ? 'none' : 'inline';
        if (progress) progress.style.display = busy ? 'inline' : 'none';
    }

    // ── Batch consumption preview ────────────────────────────────
    //
    // Builds a per-row summary of what each input will consume and what
    // the batch(es) will be left with. Returns { rows, warnings }.
    //
    function buildBatchPreview(data) {
        const rows     = [];
        const warnings = [];

        data.inputs.forEach((input, i) => {
            const variantId  = input.product_variant_id;
            const enteredQty = parseFloat(input.planned_quantity) || 0;
            if (!variantId) return;

            const rowEl = document.getElementById(`input_${i}`);
            if (!rowEl) return;

            const typable    = rowEl.querySelector('.material-typable-input');
            const datalistId = typable?.getAttribute('list');
            const datalist   = datalistId ? document.getElementById(datalistId) : null;

            let variantName = 'Unknown material';
            let strategy    = null;

            datalist?.querySelectorAll('option').forEach(opt => {
                if (String(opt.dataset.id) === String(variantId)) {
                    variantName = opt.value || variantName;
                    strategy    = opt.dataset.inventoryStrategy || null;
                }
            });

            if (strategy !== 'batch') return;

            const batchSelect = rowEl.querySelector('.batch-source-select');
            const opt         = batchSelect?.options[batchSelect.selectedIndex];

            if (!opt || !opt.value) return;   // already caught by missing-batch check

            const batchNumber = (opt.textContent || '').split('(')[0].trim();
            const batchQty    = parseFloat(opt.dataset.quantityRemaining) || 0;
            const afterQty    = batchQty - enteredQty;

            const consumedRatio = batchQty > 0 ? (enteredQty / batchQty) : 0;
            const isFull        = Math.abs(afterQty) < 0.0001;
            const isPartial     = !isFull && afterQty > 0;
            const isTiny        = batchQty > 0 && enteredQty > 0 && consumedRatio < 0.05;

            rows.push({
                index:       i + 1,
                variantName,
                enteredQty,
                batchNumber,
                batchBefore: batchQty,
                batchAfter:  afterQty,
                isFull,
                isPartial,
                isTiny,
            });

            if (isTiny) {
                warnings.push(
                    `Row ${i + 1} (${variantName}): you are consuming only ` +
                    `${enteredQty.toLocaleString()} out of ${batchQty.toLocaleString()} ` +
                    `in batch <code>${batchNumber}</code> ` +
                    `(${(consumedRatio * 100).toFixed(2)}%). ` +
                    `Double-check this is intentional.`
                );
            }
        });

        return { rows, warnings };
    }

    async function submit() {
        const form = document.getElementById(FORM_ID);
        const btn  = document.getElementById(BTN_ID);
        if (!form || !btn) {
            console.error('[production-create] form or button not found');
            return;
        }

        const formData = new FormData(form);
        const data = {};

        for (const [key, value] of formData.entries()) {
            if (key.startsWith('inputs[')) {
                const m = key.match(/inputs\[(\d+)\]\[(.+)\]/);
                if (m) {
                    const [, idx, field] = m;
                    if (!data.inputs)      data.inputs = [];
                    if (!data.inputs[idx]) data.inputs[idx] = {};
                    data.inputs[idx][field] = value;
                }
            } else if (key.startsWith('outputs[')) {
                const m = key.match(/outputs\[(\d+)\]\[(.+)\]/);
                if (m) {
                    const [, idx, field] = m;
                    if (!data.outputs)      data.outputs = [];
                    if (!data.outputs[idx]) data.outputs[idx] = {};
                    data.outputs[idx][field] = value;
                }
            } else {
                data[key] = value;
            }
        }

        if (data.inputs) {
            data.inputs = Object.values(data.inputs).filter(i =>
                i.product_variant_id &&
                i.planned_quantity &&
                parseFloat(i.planned_quantity) > 0
            );
        }
        if (data.outputs) {
            data.outputs = Object.values(data.outputs).filter(o =>
                o.product_variant_id &&
                o.planned_quantity &&
                parseFloat(o.planned_quantity) > 0
            );
        }

        if (!data.inputs || data.inputs.length === 0) {
            Swal.fire({
                title: '{{ __("passwords.validation_error") }}',
                text:  '{{ __("passwords.add_at_least_one_input") }}',
                icon: 'warning',
                confirmButtonColor: '#0d6efd',
            });
            return;
        }

        if (!data.outputs || data.outputs.length === 0) {
            Swal.fire({
                title: '{{ __("passwords.validation_error") }}',
                text:  '{{ __("passwords.add_at_least_one_output") }}',
                icon: 'warning',
                confirmButtonColor: '#0d6efd',
            });
            return;
        }

        // ═══════════════════════════════════════════════════════════
        // ★ BATCH STRATEGY CHECK
        //
        // Only inputs whose product uses the 'batch' strategy must have
        // a purchase_receipt_item_id selected. Inputs using 'quantity'
        // or 'serial' don't need a batch, and 'recipe' isn't valid here.
        // ═══════════════════════════════════════════════════════════
        // ── Missing-batch check (unchanged logic) ─────────────────────
        const missingBatches = [];

        data.inputs.forEach((input, i) => {
            const variantId = input.product_variant_id;
            if (!variantId) return;

            const rowEl = document.getElementById(`input_${i}`);
            if (!rowEl) return;

            const typable    = rowEl.querySelector('.material-typable-input');
            const datalistId = typable?.getAttribute('list');
            if (!datalistId) return;

            const datalist = document.getElementById(datalistId);
            if (!datalist) return;

            let strategy    = null;
            let variantName = 'Unknown material';

            datalist.querySelectorAll('option').forEach(opt => {
                if (String(opt.dataset.id) === String(variantId)) {
                    strategy    = opt.dataset.inventoryStrategy || null;
                    variantName = opt.value || variantName;
                }
            });

            if (strategy !== 'batch') return;

            const hasBatch = input.purchase_receipt_item_id
                        && input.purchase_receipt_item_id !== ''
                        && input.purchase_receipt_item_id !== 'null';

            if (!hasBatch) {
                missingBatches.push({ index: i + 1, variantName });
            }
        });

        if (missingBatches.length > 0) {
            const list = missingBatches
                .map(m => `<li>Row ${m.index}: <strong>${m.variantName}</strong></li>`)
                .join('');

            Swal.fire({
                title: '{{ __("passwords.validation_error") }}',
                html: `
                    <p class="mb-3">The following materials use the <strong>batch</strong> tracking strategy
                    and require a specific batch to be selected:</p>
                    <ul class="text-start">${list}</ul>
                    <p class="text-muted fs-7 mt-3 mb-0">
                        Every batch-tracked input must point to exactly one batch.
                    </p>
                `,
                icon: 'warning',
                confirmButtonColor: '#0d6efd',
            });
            return;
        }

        // ── Consumption preview + confirmation ─────────────────────────
        //
        // Server-side strict mode already rejects over-consumption, so this
        // dialog only needs to surface two things the server cannot:
        //   1. What the batch balances will look like after the draw
        //   2. A loud warning when a tiny fraction of a batch is being drawn
        //      (the "typed 1 against a 35,349 batch" mistake)
        //
        const preview = buildBatchPreview(data);

        if (preview.rows.length > 0) {
            const tableRows = preview.rows.map(r => {
                const afterFmt  = r.batchAfter.toLocaleString(undefined, { maximumFractionDigits: 2 });
                const beforeFmt = r.batchBefore.toLocaleString(undefined, { maximumFractionDigits: 2 });
                const qtyFmt    = r.enteredQty.toLocaleString(undefined, { maximumFractionDigits: 2 });

                let statusCell;
                if (r.isFull)          statusCell = `<span class="badge bg-success text-nowrap">Full batch</span>`;
                else if (r.isTiny)     statusCell = `<span class="badge bg-warning text-dark text-nowrap">Tiny draw — verify</span>`;
                else if (r.isPartial)  statusCell = `<span class="badge bg-info text-dark text-nowrap">Partial draw</span>`;
                else                   statusCell = `<span class="badge bg-secondary">—</span>`;

                return `
                    <tr>
                        <td class="text-start">${r.index}</td>
                        <td class="text-start">${r.variantName}</td>
                        <td class="text-start">
                            <code class="text-break">${r.batchNumber}</code>
                        </td>
                        <td class="text-end text-nowrap">${qtyFmt}</td>
                        <td class="text-end text-nowrap">${beforeFmt}</td>
                        <td class="text-end text-nowrap"><strong>${afterFmt}</strong></td>
                        <td class="text-center">${statusCell}</td>
                    </tr>
                `;
            }).join('');

            const warningHtml = preview.warnings.length > 0
                ? `
                    <div class="alert alert-warning text-start mt-3 mb-0">
                        <strong>Please review:</strong>
                        <ul class="mb-0 ps-3">${preview.warnings.map(w => `<li>${w}</li>`).join('')}</ul>
                    </div>
                `
                : '';

            const confirm = await Swal.fire({
                title: '{{ __("passwords.confirm_consumption") ?? "Confirm consumption" }}',
                width: '820px',
                html: `
                    <p class="text-muted mb-3">
                        Review what will be drawn from each batch before the order is created.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-start text-nowrap"  style="width: 6%;">Row</th>
                                    <th class="text-start text-nowrap"  style="width: 26%;">Material</th>
                                    <th class="text-start text-nowrap"  style="width: 26%;">Batch</th>
                                    <th class="text-end text-nowrap"    style="width: 12%;">Consuming</th>
                                    <th class="text-end text-nowrap"    style="width: 12%;">Before</th>
                                    <th class="text-end text-nowrap"    style="width: 12%;">After</th>
                                    <th class="text-center text-nowrap" style="width: 6%;">Status</th>
                                </tr>
                            </thead>
                            <tbody>${tableRows}</tbody>
                        </table>
                    </div>
                    ${warningHtml}
                `,
                icon: preview.warnings.length > 0 ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: '{{ __("passwords.confirm_and_create") ?? "Confirm and create" }}',
                cancelButtonText:  '{{ __("passwords.go_back") ?? "Go back" }}',
                confirmButtonColor: '#0d6efd',
                cancelButtonColor:  '#6c757d',
                reverseButtons: true,
                focusCancel: preview.warnings.length > 0,
            });

            if (!confirm.isConfirmed) return;
        }

        delete data._token;

        // ★ Read CSRF here, not at module load
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        || '{{ csrf_token() }}';

        if (!csrfToken) {
            window.toastr?.error?.('CSRF token missing — page needs a hard refresh.');
            return;
        }

        toggleBusy(btn, true);

        try {
            const res = await fetch(ROUTES.store, {
                method: 'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':     csrfToken,
                },
                body: JSON.stringify(data),
            });

            const json = await res.json();

            if (json.success) {
                window.toastr?.success?.(
                    json.message || '{{ __("passwords.production_order_created") }}'
                );

                bootstrap.Modal
                    .getInstance(document.getElementById(MODAL_ID))
                    ?.hide();

                window.ProductionPage?.reload?.();
            } else {
                if (json.errors && typeof json.errors === 'object') {
                    const firstError = Object.values(json.errors)[0]?.[0];
                    window.toastr?.error?.(
                        firstError || json.message || '{{ __("passwords.error") }}'
                    );
                } else {
                    window.toastr?.error?.(json.message || '{{ __("passwords.error") }}');
                }
            }
        } catch (err) {
            console.error('[production-create] submit failed:', err);
            window.toastr?.error?.('{{ __("passwords.error") }}');
        } finally {
            toggleBusy(btn, false);
        }
    }

    // ── Init ─────────────────────────────────────────────────────
    function init() {
        // Single delegated listener for both 'input' and 'change'.
        // Browsers that fire both get two calls, but handleVariantSelected
        // is idempotent (guarded by the same-value check).
        const onTypableEvent = (e) => {
            if (e.target.classList.contains('material-typable-input') ||
                e.target.classList.contains('product-typable-input')) {
                handleVariantSelected(e.target);
            }
        };

        document.addEventListener('input',  onTypableEvent);
        document.addEventListener('change', onTypableEvent);

        // Numeric totals
        document.addEventListener('input', function (e) {
            if (e.target.classList.contains('input-quantity') ||
                e.target.classList.contains('input-cost') ||
                e.target.classList.contains('output-quantity') ||
                e.target.classList.contains('output-cost')) {
                updateTotals();
            }
        });

        // Batch selection
        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('batch-source-select')) {
                handleBatchSelected(e.target);
            }
        });

        // Reset modal on open
        const modal = document.getElementById(MODAL_ID);
        modal?.addEventListener('show.bs.modal', function () {
            inputIndex  = 1;
            outputIndex = 1;

            // Clear pending debounces
            Object.keys(variantDebounces).forEach(k => {
                clearTimeout(variantDebounces[k]);
                delete variantDebounces[k];
            });
            Object.keys(batchLoadInFlight).forEach(k => {
                delete batchLoadInFlight[k];
            });

            document.querySelectorAll('#production_inputs_container .production-input-item')
                .forEach((row, i) => { if (i > 0) row.remove(); });

            document.querySelectorAll('#production_outputs_container .production-output-item')
                .forEach((row, i) => { if (i > 0) row.remove(); });

            ['input_0', 'output_0'].forEach(id => {
                const row = document.getElementById(id);
                if (!row) return;
                row.querySelectorAll('select').forEach(sel => {
                    if (typeof $ !== 'undefined' && $.fn.select2 && $(sel).hasClass('select2-hidden-accessible')) {
                        $(sel).val('').trigger('change.select2');
                    } else {
                        sel.value = '';
                    }
                });
            });

            updateTotals();
        });

        const initialInput  = document.getElementById('input_0');
        const initialOutput = document.getElementById('output_0');
        if (initialInput)  initSelect2InRow(initialInput);
        if (initialOutput) initSelect2InRow(initialOutput);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    return {
        addInput,
        addOutput,
        removeInput,
        removeOutput,
        loadAvailableBatches,
        submit,
        getModalLocationId,
    };
})();
</script>