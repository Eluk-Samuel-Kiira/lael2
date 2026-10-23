{{-- resources/views/manufacturing/production-order/edit-modal-scripts.blade.php --}}
@once
@push('scripts')
<script>
(function () {
    'use strict';

    // ── Config ───────────────────────────────────────────────────
    const MODAL_ID = 'editProductionModalShared';
    const ROUTES = {
        update:  (id) => `/production-orders/${id}`,
        edit:    (id) => `/production-orders/${id}/edit`,
        batches: '{{ route("production-orders.available-batches") }}',
    };

    // ── Module state ────────────────────────────────────────────
    const counters      = {};
    const debounces     = {};
    const batchInFlight = {};
    let   delegationBound = false;

    // ── Helpers ─────────────────────────────────────────────────
    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || '{{ csrf_token() }}';
    }

    function getModalEl() {
        return document.getElementById(MODAL_ID);
    }

    function currentOrderId() {
        return parseInt(getModalEl()?.dataset?.orderId || '0', 10);
    }

    function currentLocationId() {
        return getModalEl()?.dataset?.locationId || '';
    }

    function getCounters(orderId) {
        if (!counters[orderId]) counters[orderId] = { input: 0, output: 0 };
        return counters[orderId];
    }

    function toggleBusy(btn, busy) {
        if (!btn) return;
        btn.disabled = busy;
        const label    = btn.querySelector('.indicator-label');
        const progress = btn.querySelector('.indicator-progress');
        if (label)    label.style.display    = busy ? 'none' : 'inline';
        if (progress) progress.style.display = busy ? 'inline' : 'none';
    }

    function escapeAttr(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    // ── Select2 ─────────────────────────────────────────────────
    function initSelect2(row) {
        if (typeof window.jQuery === 'undefined') return;
        const $ = window.jQuery;
        if (!$ || !$.fn || !$.fn.select2) return;

        const $modal = $(`#${MODAL_ID}`);

        $(row).find('select[data-control="select2"]').each(function () {
            if ($(this).hasClass('select2-hidden-accessible')) {
                try { $(this).select2('destroy'); } catch (e) {}
            }
            $(this).select2({
                dropdownParent: $modal.length ? $modal : $('body'),
                width: '100%',
                placeholder: $(this).data('placeholder') || '{{ __("passwords.select_unit") }}',
                allowClear: true,
            });
        });

        row.querySelectorAll('select.edit-input-unit-select, select.edit-output-unit-select').forEach(sel => {
            const desired = sel.dataset.selectedUnit;
            if (desired) {
                $(sel).val(String(desired)).trigger('change.select2');
                sel.value = String(desired);
            }
        });
        row.querySelectorAll('select.batch-source-select').forEach(sel => {
            const desired = sel.dataset.selectedBatch;
            if (desired) {
                $(sel).val(String(desired)).trigger('change.select2');
                sel.value = String(desired);
            }
        });
    }

    // ── Row HTML ────────────────────────────────────────────────
    function inputRowHtml(orderId, rowKey, input) {
        return `
            <div class="row g-4 mb-4 production-input-item"
                 id="edit_input_${orderId}_${rowKey}"
                 data-input-id="${input.id ?? rowKey}"
                 data-consumed="${input.actual_quantity > 0 ? 'true' : 'false'}"
                 data-original-batch-id="${input.purchase_receipt_item_id || ''}"
                 data-original-consumed="${input.actual_quantity || 0}">
                <div class="col-md-3">
                    <label class="form-label required d-flex justify-content-between">
                        <span>{{ __('passwords.material') }}</span>
                        ${input.actual_quantity > 0
                            ? `<span class="badge badge-warning fs-8"><i class="bi bi-lock-fill me-1"></i>{{ __('passwords.consumed') }}: ${input.actual_quantity}</span>`
                            : ''}
                    </label>
                    <div class="position-relative">
                        <input type="text"
                               id="edit_material_search_input_${orderId}_${rowKey}"
                               class="form-control edit-material-typable-input"
                               list="edit_material_list_input_${orderId}_${rowKey}"
                               placeholder="{{ __('passwords.type_or_select_material') }}"
                               autocomplete="off"
                               data-item-index="${rowKey}"
                               data-order-id="${orderId}"
                               data-type="input"
                               value="${escapeAttr(input.product_variant_name || '')}">
                        <input type="hidden" name="inputs[${rowKey}][id]" value="${input.id ?? ''}">
                        <input type="hidden"
                               name="inputs[${rowKey}][product_variant_id]"
                               id="edit_material_id_input_${orderId}_${rowKey}"
                               value="${input.product_variant_id || ''}">
                        <datalist id="edit_material_list_input_${orderId}_${rowKey}">
                            ${window.__VARIANT_OPTIONS__ || ''}
                        </datalist>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label required">{{ __('passwords.quantity') }}</label>
                    <input type="number" name="inputs[${rowKey}][planned_quantity]"
                           class="form-control input-quantity" min="0.01" step="0.01"
                           value="${input.planned_quantity ?? 1}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('passwords.unit') }}</label>
                    <select name="inputs[${rowKey}][unit]"
                            class="form-select edit-input-unit-select"
                            data-control="select2"
                            data-selected-unit="${input.unit || ''}">
                        <option value=""></option>
                        ${window.__UOM_OPTIONS__ || ''}
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('passwords.estimated_cost') }}</label>
                    <input type="number" name="inputs[${rowKey}][estimated_cost]"
                           class="form-control input-cost" min="0" step="0.01"
                           value="${input.estimated_cost ?? 0}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('passwords.batch_source') }}</label>
                    <select name="inputs[${rowKey}][purchase_receipt_item_id]"
                            class="form-select batch-source-select"
                            data-input-index="${rowKey}"
                            data-order-id="${orderId}"
                            data-selected-batch="${input.purchase_receipt_item_id || ''}">
                        <option value="">{{ __('passwords.no_batch') }}</option>
                        ${input.batch_number
                            ? `<option value="${input.purchase_receipt_item_id}" selected>${escapeAttr(input.batch_number)} (${input.batch_quantity_remaining ?? 0} {{ __('passwords.left') }})</option>`
                            : ''}
                    </select>
                    <button type="button"
                            class="btn btn-sm btn-info mt-1 load-batches-btn"
                            data-input-index="${rowKey}"
                            data-order-id="${orderId}"
                            onclick="POEdit.loadBatches(${orderId}, '${rowKey}')">
                        <i class="bi bi-arrow-repeat me-1"></i> {{ __('passwords.load_batches') }}
                    </button>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-sm btn-danger w-100"
                            onclick="POEdit.removeInput(this, ${orderId})">
                        <i class="bi bi-trash fs-5"></i>
                    </button>
                </div>
            </div>
        `;
    }

    function outputRowHtml(orderId, rowKey, output) {
        const strategy = output.inventory_strategy || 'quantity';
        // ★ Track the original stored cost so auto-fill doesn't clobber it
        const originalCost = parseFloat(output.production_cost) || 0;

        return `
            <div class="row g-4 mb-4 production-output-item"
                 id="edit_output_${orderId}_${rowKey}"
                 data-output-id="${output.id ?? rowKey}"
                 data-original-cost="${originalCost}">
                <div class="col-md-3">
                    <label class="form-label required">{{ __('passwords.product') }}</label>
                    <div class="position-relative">
                        <input type="text"
                               id="edit_product_search_output_${orderId}_${rowKey}"
                               class="form-control edit-product-typable-input"
                               list="edit_product_list_output_${orderId}_${rowKey}"
                               placeholder="{{ __('passwords.type_or_select_product') }}"
                               autocomplete="off"
                               data-item-index="${rowKey}"
                               data-order-id="${orderId}"
                               data-type="output"
                               value="${escapeAttr(output.product_variant_name || '')}">
                        <input type="hidden" name="outputs[${rowKey}][id]" value="${output.id ?? ''}">
                        <input type="hidden"
                               name="outputs[${rowKey}][product_variant_id]"
                               id="edit_product_id_output_${orderId}_${rowKey}"
                               value="${output.product_variant_id || ''}">
                        <datalist id="edit_product_list_output_${orderId}_${rowKey}">
                            ${window.__VARIANT_OUTPUT_OPTIONS__ || ''}
                        </datalist>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label required">{{ __('passwords.planned') }}</label>
                    <input type="number" name="outputs[${rowKey}][planned_quantity]"
                           class="form-control output-quantity" min="0.01" step="0.01"
                           value="${output.planned_quantity ?? 1}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('passwords.actual') }}</label>
                    <input type="number" name="outputs[${rowKey}][actual_quantity]"
                           class="form-control output-actual-quantity" min="0" step="0.01"
                           value="${output.actual_quantity ?? 0}"
                           data-old-value="${output.actual_quantity ?? 0}"
                           data-output-id="${output.id ?? ''}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">{{ __('passwords.defective') }}</label>
                    <input type="number" name="outputs[${rowKey}][defective_quantity]"
                           class="form-control output-defective-quantity" min="0" step="0.01"
                           value="${output.defective_quantity ?? 0}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">{{ __('passwords.unit') }}</label>
                    <select name="outputs[${rowKey}][unit]"
                            class="form-select edit-output-unit-select"
                            data-control="select2"
                            data-selected-unit="${output.unit || ''}">
                        <option value=""></option>
                        ${window.__UOM_OPTIONS__ || ''}
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">{{ __('passwords.strategy') }}</label>
                    <select name="outputs[${rowKey}][inventory_strategy]"
                            class="form-select output-strategy-select">
                        <option value="quantity" ${strategy === 'quantity' ? 'selected' : ''}>{{ __('passwords.quantity_tracking') }}</option>
                        <option value="batch"    ${strategy === 'batch'    ? 'selected' : ''}>{{ __('passwords.batch_tracking') }}</option>
                        <option value="serial"   ${strategy === 'serial'   ? 'selected' : ''}>{{ __('passwords.serial_tracking') }}</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">{{ __('passwords.production_cost') }}</label>
                    <input type="number" name="outputs[${rowKey}][production_cost]"
                           class="form-control output-cost" min="0" step="0.01"
                           value="${output.production_cost ?? 0}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-sm btn-danger w-100"
                            onclick="POEdit.removeOutput(this, ${orderId})">
                        <i class="bi bi-trash fs-5"></i>
                    </button>
                </div>
            </div>
        `;
    }

    // ── Populate modal ──────────────────────────────────────────
    function mountEditModal(payload) {
        const order = payload.order;
        const modalEl = getModalEl();
        if (!modalEl) {
            console.warn('[POEdit] shared modal not found');
            return;
        }

        modalEl.dataset.orderId    = order.id;
        modalEl.dataset.locationId = order.location_id ?? '';

        modalEl.querySelector('.po-edit-title').textContent =
            '{{ __("passwords.edit_production_order") }} — ' + (order.production_number || '');

        const locInput = modalEl.querySelector('.po-edit-location');
        if (locInput) locInput.value = order.location_name || 'N/A';

        const sched = modalEl.querySelector('input[name="scheduled_date"]');
        if (sched) sched.value = order.scheduled_date || '';

        const notes = modalEl.querySelector('textarea[name="notes"]');
        if (notes) notes.value = order.notes || '';

        const status = modalEl.querySelector('.po-edit-status');
        if (status) {
            status.className = 'badge po-edit-status badge-' + (order.status_badge || 'secondary') + ' fw-bold fs-6';
            status.textContent = order.status_label || order.status || '';
        }

        // Toggle the in-progress warning banner
        const warning = modalEl.querySelector('[data-po-in-progress-warning]');
        if (warning) {
            warning.classList.toggle('d-none', order.status !== 'in_progress');
        }

        counters[order.id] = { input: 0, output: 0 };

        const inputsContainer = modalEl.querySelector('.po-edit-inputs-container');
        inputsContainer.innerHTML = (order.inputs || [])
            .map(i => inputRowHtml(order.id, i.id, i))
            .join('');

        const outputsContainer = modalEl.querySelector('.po-edit-outputs-container');
        outputsContainer.innerHTML = (order.outputs || [])
            .map(o => outputRowHtml(order.id, o.id, o))
            .join('');

        inputsContainer.querySelectorAll('.production-input-item').forEach(initSelect2);
        outputsContainer.querySelectorAll('.production-output-item').forEach(initSelect2);

        updateTotals(order.id);

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    // ── Add / remove ────────────────────────────────────────────
    function addInput(orderId) {
        const modalEl = getModalEl();
        if (!modalEl) return;
        const c = getCounters(orderId);
        const key = 'new_' + (c.input++);

        const container = modalEl.querySelector('.po-edit-inputs-container');
        if (!container) return;

        container.insertAdjacentHTML('beforeend', inputRowHtml(orderId, key, {
            id: null, product_variant_id: '', product_variant_name: '',
            planned_quantity: 1, unit: '', estimated_cost: 0,
            purchase_receipt_item_id: '', actual_quantity: 0,
        }));

        const row = container.lastElementChild;
        if (row) initSelect2(row);
        updateTotals(orderId);
    }

    function addOutput(orderId) {
        const modalEl = getModalEl();
        if (!modalEl) return;
        const c = getCounters(orderId);
        const key = 'new_' + (c.output++);

        const container = modalEl.querySelector('.po-edit-outputs-container');
        if (!container) return;

        container.insertAdjacentHTML('beforeend', outputRowHtml(orderId, key, {
            id: null, product_variant_id: '', product_variant_name: '',
            planned_quantity: 1, actual_quantity: 0, defective_quantity: 0,
            unit: '', inventory_strategy: 'quantity', production_cost: 0,
        }));

        const row = container.lastElementChild;
        if (row) initSelect2(row);
        updateTotals(orderId);
    }

    function removeInput(btn, orderId) {
        const container = getModalEl()?.querySelector('.po-edit-inputs-container');
        if (!container) return;
        if (container.querySelectorAll('.production-input-item').length <= 1) {
            Swal.fire({ title: '{{ __("passwords.validation_error") }}', text: '{{ __("passwords.add_at_least_one_input") }}', icon: 'warning', confirmButtonColor: '#0d6efd' });
            return;
        }
        const row = btn.closest('.production-input-item');
        if (!row) return;
        if (row.dataset.consumed === 'true') {
            Swal.fire({
                title: '{{ __("passwords.confirm_deletion") }}',
                html: '{{ __("passwords.consumed_input_delete_warning") }}',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ __("passwords.yes_delete_and_restore") }}',
                cancelButtonText: '{{ __("auth._cancel") }}',
            }).then(r => { if (r.isConfirmed) { row.remove(); updateTotals(orderId); } });
        } else {
            row.remove();
            updateTotals(orderId);
        }
    }

    function removeOutput(btn, orderId) {
        const container = getModalEl()?.querySelector('.po-edit-outputs-container');
        if (!container) return;
        if (container.querySelectorAll('.production-output-item').length <= 1) {
            Swal.fire({ title: '{{ __("passwords.validation_error") }}', text: '{{ __("passwords.add_at_least_one_output") }}', icon: 'warning', confirmButtonColor: '#0d6efd' });
            return;
        }
        const row = btn.closest('.production-output-item');
        if (!row) return;
        const actualQty = parseFloat(row.querySelector('.output-actual-quantity')?.value) || 0;
        if (actualQty > 0) {
            Swal.fire({
                title: '{{ __("passwords.confirm_deletion") }}',
                html: '{{ __("passwords.produced_output_delete_warning") }}',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ __("passwords.yes_delete_and_reverse") }}',
                cancelButtonText: '{{ __("auth._cancel") }}',
            }).then(r => { if (r.isConfirmed) { row.remove(); updateTotals(orderId); } });
        } else {
            row.remove();
            updateTotals(orderId);
        }
    }

    // ── Variant auto-select ─────────────────────────────────────
    function handleVariantSelected(inputEl) {
        const orderId = inputEl.dataset.orderId;
        const index   = inputEl.dataset.itemIndex;
        const isInput = (inputEl.dataset.type || 'input') === 'input';

        const rowId      = isInput ? `edit_input_${orderId}_${index}`  : `edit_output_${orderId}_${index}`;
        const datalistId = isInput ? `edit_material_list_input_${orderId}_${index}`  : `edit_product_list_output_${orderId}_${index}`;
        const hiddenId   = isInput ? `edit_material_id_input_${orderId}_${index}`    : `edit_product_id_output_${orderId}_${index}`;

        const datalist = document.getElementById(datalistId);
        const hidden   = document.getElementById(hiddenId);
        const row      = document.getElementById(rowId);
        if (!datalist || !hidden || !row) return;

        if (debounces[rowId]) { clearTimeout(debounces[rowId]); debounces[rowId] = null; }

        const typed = (inputEl.value || '').trim().toLowerCase();
        let matched = null;
        datalist.querySelectorAll('option').forEach(opt => {
            if ((opt.value || '').trim().toLowerCase() === typed && typed !== '') matched = opt;
        });

        if (!matched) { hidden.value = ''; return; }

        const newId = matched.getAttribute('data-id');
        if (hidden.value && hidden.value === newId) return;
        hidden.value = newId;

        const uomId = matched.getAttribute('data-uom-id');
        const uomSelect = row.querySelector('select[name*="[unit]"]');
        if (uomSelect && uomId) {
            const hasOption = Array.from(uomSelect.options).some(o => String(o.value) === String(uomId));
            if (hasOption) {
                uomSelect.value = String(uomId);
                uomSelect.dataset.selectedUnit = String(uomId);
                if (typeof window.jQuery !== 'undefined' && window.jQuery.fn.select2
                    && window.jQuery(uomSelect).hasClass('select2-hidden-accessible')) {
                    window.jQuery(uomSelect).val(String(uomId)).trigger('change.select2');
                }
            }
        }

        if (isInput) {
            const cost = matched.getAttribute('data-cost-price');
            const costInput = row.querySelector('.input-cost');
            if (costInput && cost) costInput.value = parseFloat(cost).toFixed(2);
        } else {
            // ── OUTPUTS ─────────────────────────────────────────
            // Strategy auto-fill
            const strategy = matched.getAttribute('data-inventory-strategy');
            const sel = row.querySelector('.output-strategy-select');
            if (sel && strategy) {
                sel.value = strategy;
                sel.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // ★ Auto-fill production cost from variant's grand_total_cost_price
            const grandTotalCost = parseFloat(matched.getAttribute('data-grand-total-cost')) || 0;
            const costInput = row.querySelector('.output-cost');
            if (costInput) {
                costInput.value = grandTotalCost.toFixed(2);
                costInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            // ★ Auto-fill selling price (optional, usually helpful)
            const sellingPrice = parseFloat(matched.getAttribute('data-selling-price')) || 0;
            const priceInput = row.querySelector('input[name*="[selling_price]"]');
            if (priceInput && sellingPrice > 0) {
                priceInput.value = sellingPrice.toFixed(2);
            }
        }

        if (isInput) {
            debounces[rowId] = setTimeout(() => {
                debounces[rowId] = null;
                loadBatches(orderId, index);
            }, 300);
        }

        updateTotals(orderId);
    }

    // ── Batches ─────────────────────────────────────────────────
    async function loadBatches(orderId, inputIndex) {
        const row = document.getElementById(`edit_input_${orderId}_${inputIndex}`);
        if (!row) return;

        const flightKey = `edit_input_${orderId}_${inputIndex}`;
        if (batchInFlight[flightKey]) return;

        const variantId = document.getElementById(`edit_material_id_input_${orderId}_${inputIndex}`)?.value;
        if (!variantId) {
            Swal.fire({ title: '{{ __("passwords.info") }}', text: '{{ __("passwords.select_material_first") }}', icon: 'info', confirmButtonColor: '#0d6efd' });
            return;
        }

        const locationId = currentLocationId();
        batchInFlight[flightKey] = true;

        Swal.fire({
            title: '{{ __("passwords.loading_batches") }}',
            allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
        });

        try {
            const url = new URL(ROUTES.batches, window.location.origin);
            url.searchParams.set('variant_id', variantId);
            if (locationId) url.searchParams.set('location_id', locationId);

            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();
            Swal.close();

            const select = row.querySelector('.batch-source-select');
            if (!select) return;

            const currentValue = select.dataset.selectedBatch || select.value;
            select.innerHTML = '<option value="">{{ __("passwords.no_batch") }}</option>';

            if (data.success && Array.isArray(data.batches) && data.batches.length > 0) {
                data.batches.forEach(batch => {
                    const option = document.createElement('option');
                    option.value = batch.id;
                    const qty = batch.quantity_remaining || 0;
                    option.textContent = `${batch.batch_number} (${qty} units)`;
                    if (batch.expiry_date) option.textContent += ` - Exp: ${batch.expiry_date}`;
                    option.dataset.quantityRemaining = qty;
                    option.dataset.unitCost = batch.unit_cost ?? '';
                    select.appendChild(option);
                });
                if (currentValue) select.value = currentValue;
            } else {
                Swal.fire({ title: '{{ __("passwords.info") }}', text: '{{ __("passwords.no_batches_available") }}', icon: 'info', confirmButtonColor: '#0d6efd' });
            }
        } catch (err) {
            Swal.close();
            console.error('[POEdit] batch fetch failed:', err);
            Swal.fire({ title: '{{ __("passwords.error") }}', text: '{{ __("passwords.failed_to_load_batches") }}', icon: 'error', confirmButtonColor: '#0d6efd' });
        } finally {
            batchInFlight[flightKey] = false;
        }
    }

    // ── Totals ──────────────────────────────────────────────────
    function updateTotals(orderId) {
        const modalEl = getModalEl();
        if (!modalEl) return;

        let totalIn = 0, totalOut = 0;

        modalEl.querySelectorAll('.po-edit-inputs-container .production-input-item').forEach(row => {
            const q = parseFloat(row.querySelector('.input-quantity')?.value) || 0;
            const c = parseFloat(row.querySelector('.input-cost')?.value) || 0;
            totalIn += q * c;
        });

        modalEl.querySelectorAll('.po-edit-outputs-container .production-output-item').forEach(row => {
            const q = parseFloat(row.querySelector('.output-quantity')?.value) || 0;
            const c = parseFloat(row.querySelector('.output-cost')?.value) || 0;
            totalOut += q * c;
        });

        modalEl.querySelector('.po-edit-input-total').textContent   = totalIn.toFixed(2);
        modalEl.querySelector('.po-edit-output-total').textContent  = totalOut.toFixed(2);
        modalEl.querySelector('.po-edit-grand-input').textContent   = totalIn.toFixed(2);
        modalEl.querySelector('.po-edit-grand-output').textContent  = totalOut.toFixed(2);
        modalEl.querySelector('.po-edit-grand-cost').textContent    = (totalIn + totalOut).toFixed(2);
    }

    // ── Submit ──────────────────────────────────────────────────
    function resolveUnit(row, variantId, isInput) {
        let unit = row.querySelector('select[name*="[unit]"]')?.value;
        if (!unit) unit = row.querySelector('select[name*="[unit]"]')?.dataset?.selectedUnit || null;
        if (!unit && variantId) {
            const cls = isInput ? '.edit-material-typable-input' : '.edit-product-typable-input';
            const listId = row.querySelector(cls)?.getAttribute('list');
            if (listId) {
                const dl = document.getElementById(listId);
                dl?.querySelectorAll('option').forEach(opt => {
                    if (String(opt.dataset.id) === String(variantId)) unit = opt.dataset.uomId || null;
                });
            }
        }
        return unit ? String(unit) : null;
    }

    async function submit() {
        const modalEl = getModalEl();
        if (!modalEl) return;

        const orderId = currentOrderId();
        if (!orderId) return;

        const submitBtn = modalEl.querySelector('.po-edit-submit');
        const formEl = modalEl.querySelector('.po-edit-form');

        const inputs = [];
        modalEl.querySelectorAll('.po-edit-inputs-container .production-input-item').forEach(row => {
            const key = row.dataset.inputId;
            const isNew = typeof key === 'string' && key.startsWith('new_');
            const variantId = row.querySelector('input[name*="[product_variant_id]"]')?.value;
            const quantity = parseFloat(row.querySelector('.input-quantity')?.value) || 0;
            const cost = parseFloat(row.querySelector('.input-cost')?.value) || 0;
            const batchId = row.querySelector('.batch-source-select')?.value || null;
            const unit = resolveUnit(row, variantId, true);

            if (variantId && quantity > 0 && unit) {
                inputs.push({
                    id: isNew ? null : parseInt(key),
                    product_variant_id: parseInt(variantId),
                    planned_quantity: quantity,
                    unit, estimated_cost: cost,
                    purchase_receipt_item_id: batchId ? parseInt(batchId) : null,
                });
            }
        });

        const outputs = [];
        modalEl.querySelectorAll('.po-edit-outputs-container .production-output-item').forEach(row => {
            const key = row.dataset.outputId;
            const isNew = typeof key === 'string' && key.startsWith('new_');
            const variantId = row.querySelector('input[name*="[product_variant_id]"]')?.value;
            const planned = parseFloat(row.querySelector('.output-quantity')?.value) || 0;
            const actual = parseFloat(row.querySelector('.output-actual-quantity')?.value) || 0;
            const defective = parseFloat(row.querySelector('.output-defective-quantity')?.value) || 0;
            const strategy = row.querySelector('.output-strategy-select')?.value;
            const cost = parseFloat(row.querySelector('.output-cost')?.value) || 0;
            const unit = resolveUnit(row, variantId, false);

            if (variantId && planned > 0 && unit) {
                outputs.push({
                    id: isNew ? null : parseInt(key),
                    product_variant_id: parseInt(variantId),
                    planned_quantity: planned,
                    actual_quantity: actual,
                    defective_quantity: defective,
                    unit, inventory_strategy: strategy,
                    production_cost: cost,
                });
            }
        });

        if (inputs.length === 0) {
            Swal.fire({ title: '{{ __("passwords.validation_error") }}', text: '{{ __("passwords.add_at_least_one_input") }}', icon: 'warning', confirmButtonColor: '#0d6efd' });
            return;
        }
        if (outputs.length === 0) {
            Swal.fire({ title: '{{ __("passwords.validation_error") }}', text: '{{ __("passwords.add_at_least_one_output") }}', icon: 'warning', confirmButtonColor: '#0d6efd' });
            return;
        }

        const payload = {
            _method: 'PUT',
            scheduled_date: formEl.querySelector('input[name="scheduled_date"]')?.value || null,
            notes: formEl.querySelector('textarea[name="notes"]')?.value || null,
            inputs, outputs,
        };

        toggleBusy(submitBtn, true);

        try {
            const res = await fetch(ROUTES.update(orderId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify(payload),
            });

            const json = await res.json();

            if (json.success) {
                window.toastr?.success?.(json.message || '{{ __("passwords.production_order_updated") }}');
                bootstrap.Modal.getInstance(modalEl)?.hide();
                window.ProductionPage?.reload?.();
            } else {
                const err = json.errors ? Object.values(json.errors)[0]?.[0] : null;
                window.toastr?.error?.(err || json.message || '{{ __("passwords.error") }}');
            }
        } catch (err) {
            console.error('[POEdit] submit failed:', err);
            window.toastr?.error?.('{{ __("passwords.error") }}');
        } finally {
            toggleBusy(submitBtn, false);
        }
    }

    // ── Open ────────────────────────────────────────────────────
    async function openForOrder(orderId) {
        if (!orderId) return;

        try {
            const res = await fetch(ROUTES.edit(orderId), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();
            if (!data.success || !data.order) {
                window.toastr?.error?.(data.message || '{{ __("passwords.failed_to_load_order") }}');
                return;
            }

            mountEditModal(data);
        } catch (err) {
            console.error('[POEdit] edit fetch failed:', err);
            window.toastr?.error?.('{{ __("passwords.failed_to_load_order") }}');
        }
    }

    // ── Delegation ──────────────────────────────────────────────
    function bindDelegation() {
        if (delegationBound) return;
        delegationBound = true;

        const onTypable = (e) => {
            if (e.target.classList.contains('edit-material-typable-input') ||
                e.target.classList.contains('edit-product-typable-input')) {
                handleVariantSelected(e.target);
            }
        };
        document.addEventListener('input',  onTypable);
        document.addEventListener('change', onTypable);

        document.addEventListener('input', function (e) {
            if (e.target.classList.contains('input-quantity') ||
                e.target.classList.contains('input-cost') ||
                e.target.classList.contains('output-quantity') ||
                e.target.classList.contains('output-cost')) {
                const modal = e.target.closest(`#${MODAL_ID}`);
                if (modal) updateTotals(currentOrderId());
            }
        });

        document.addEventListener('change', function (e) {
            if (!e.target.classList.contains('batch-source-select')) return;
            const opt = e.target.options[e.target.selectedIndex];
            if (!opt || !opt.value) return;
            const row = e.target.closest('.production-input-item');
            const qtyInput = row?.querySelector('.input-quantity');
            if (qtyInput && !qtyInput.value) {
                qtyInput.value = opt.dataset.quantityRemaining || '';
                qtyInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });

        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-po-add-input]')) { e.preventDefault(); addInput(currentOrderId()); }
            if (e.target.closest('[data-po-add-output]')) { e.preventDefault(); addOutput(currentOrderId()); }
            if (e.target.closest('.po-edit-submit')) { e.preventDefault(); submit(); }
        });

        document.addEventListener('hidden.bs.modal', function (e) {
            if (e.target.id !== MODAL_ID) return;
            e.target.querySelector('.po-edit-inputs-container').innerHTML = '';
            e.target.querySelector('.po-edit-outputs-container').innerHTML = '';
        });
    }

    // ── Public API ──────────────────────────────────────────────
    window.POEdit = {
        openForOrder,
        addInput,
        addOutput,
        removeInput,
        removeOutput,
        loadBatches,
        submit,
        updateTotals,
        bindDelegation,
        currentOrderId,
    };

    window.ProductionActions = window.ProductionActions || {};
    window.ProductionActions.edit = function (orderId) {
        window.POEdit.openForOrder(orderId);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => window.POEdit.bindDelegation());
    } else {
        window.POEdit.bindDelegation();
    }

    /**
     * Cancel a production order.
     *
     * Shows a confirmation dialog, POSTs to /production-orders/{id}/cancel,
     * then reloads the list on success.
     */
    window.ProductionActions.cancel = async function (orderId) {
        if (!orderId) return;

        // ── Confirmation ────────────────────────────────────────────
        const confirmed = await Swal.fire({
            title: '{{ __("passwords.confirm_cancel") }}',
            html: '{{ __("passwords.cancel_production_warning") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __("passwords.yes_cancel_it") }}',
            cancelButtonText: '{{ __("auth._cancel") }}',
        });

        if (!confirmed.isConfirmed) return;

        // ── Loading indicator ───────────────────────────────────────
        Swal.fire({
            title: '{{ __("passwords.cancelling") }}',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
        });

        // ── POST ────────────────────────────────────────────────────
        try {
            const url = `/production-orders/${orderId}/cancel`;

            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':     csrfToken(),
                },
                body: JSON.stringify({
                    status: 'cancelled',
                }),
            });

            const json = await res.json();

            Swal.close();

            if (json.success) {
                window.toastr?.success?.(
                    json.message || '{{ __("passwords.production_cancelled") }}'
                );

                // Refresh the list in place
                window.ProductionPage?.reload?.();
            } else {
                window.toastr?.error?.(
                    json.message || '{{ __("passwords.error") }}'
                );
            }
        } catch (err) {
            Swal.close();
            console.error('[POEdit] cancel failed:', err);
            window.toastr?.error?.('{{ __("passwords.error") }}');
        }
    };

})();
</script>
@endpush
@endonce