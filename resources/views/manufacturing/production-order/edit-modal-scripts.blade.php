{{-- resources/views/manufacturing/production-order/edit-modal-scripts.blade.php --}}
@once
@push('scripts')
<script>
/* ══════════════════════════════════════════════════════════════════
   EDIT PRODUCTION ORDER — JS (single global block, guarded by once)
   ══════════════════════════════════════════════════════════════════ */

window.POEdit = window.POEdit || {};
window.POEdit.counters = window.POEdit.counters || {};

function getEditCounters(orderId) {
    if (!window.POEdit.counters[orderId]) {
        window.POEdit.counters[orderId] = { input: 0, output: 0 };
    }
    return window.POEdit.counters[orderId];
}

function addEditProductionInput(orderId) {
    const counters = getEditCounters(orderId);
    const key = 'new_' + (counters.input++);   // e.g. "new_0"

    const template = document.getElementById(`editInputRowTemplate${orderId}`);
    if (!template) return;

    const html = template.innerHTML.replace(/___IDX__/g, key);
    const container = document.getElementById(`edit_production_inputs_container_${orderId}`);
    container.insertAdjacentHTML('beforeend', html);

    const newRow = container.lastElementChild;
    if (newRow) {
        resetEditSelect2InRow(newRow);
        initEditSelect2InRow(newRow, orderId);
    }
    updateEditTotals(orderId);
}

function addEditProductionOutput(orderId) {
    const counters = getEditCounters(orderId);
    const key = 'new_' + (counters.output++);   // e.g. "new_0"

    const template = document.getElementById(`editOutputRowTemplate${orderId}`);
    if (!template) return;

    const html = template.innerHTML.replace(/___IDX__/g, key);
    const container = document.getElementById(`edit_production_outputs_container_${orderId}`);
    container.insertAdjacentHTML('beforeend', html);

    const newRow = container.lastElementChild;
    if (newRow) {
        resetEditSelect2InRow(newRow);
        initEditSelect2InRow(newRow, orderId);
    }
    updateEditTotals(orderId);
}

function initEditSelect2InRow(row, orderId) {
    if (typeof window.jQuery === 'undefined') return;
    const $ = window.jQuery;
    if (!$ || !$.fn || !$.fn.select2) return;

    const $modal = $(`#editProductionModal${orderId}`);
    const $parent = $modal.length ? $modal : $('body');

    $(row).find('select[data-control="select2"]').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) {
            try { $(this).select2('destroy'); } catch (e) {}
        }
        $(this).select2({
            dropdownParent: $parent,
            width: '100%',
            placeholder: $(this).data('placeholder') || '{{ __("passwords.select_unit") }}',
            allowClear: true,
        });
    });

    restoreEditSelect2Values(row);
}

function resetEditSelect2InRow(row) {
    row.querySelectorAll('.select2-container').forEach(el => el.remove());
    row.querySelectorAll('select').forEach(sel => {
        sel.classList.remove('select2-hidden-accessible');
        sel.removeAttribute('data-select2-id');
        sel.removeAttribute('aria-hidden');
        sel.removeAttribute('tabindex');
    });
}

function restoreEditSelect2Values(row) {
    if (typeof window.jQuery === 'undefined') return;
    const $ = window.jQuery;
    if (!$ || !$.fn || !$.fn.select2) return;

    row.querySelectorAll('select.edit-input-unit-select, select.edit-output-unit-select').forEach(sel => {
        let desired = sel.dataset.selectedUnit;
        if (!desired) {
            const nativeSelected = Array.from(sel.options).find(o => o.selected && o.value);
            if (nativeSelected) desired = nativeSelected.value;
        }
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

function getEditModalLocationId(orderId) {
    const modal = document.getElementById(`editProductionModal${orderId}`);
    return modal?.dataset?.locationId || '';
}



function removeEditProductionInput(btn, orderId, rowKey) {
    const container = document.getElementById(`edit_production_inputs_container_${orderId}`);
    const rows = container.querySelectorAll('.production-input-item');
    if (rows.length <= 1) {
        Swal.fire({
            title: '{{ __("passwords.validation_error") }}',
            text: '{{ __("passwords.add_at_least_one_input") }}',
            icon: 'warning', confirmButtonColor: '#0d6efd',
        });
        return;
    }

    const row = btn.closest('.production-input-item');
    if (row.dataset.consumed === 'true') {
        Swal.fire({
            title: '{{ __("passwords.confirm_deletion") }}',
            html: '{{ __("passwords.consumed_input_delete_warning") }}',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __("passwords.yes_delete_and_restore") }}',
            cancelButtonText: '{{ __("auth._cancel") }}',
        }).then(r => { if (r.isConfirmed) { row.remove(); updateEditTotals(orderId); } });
    } else {
        row.remove();
        updateEditTotals(orderId);
    }
}



function removeEditProductionOutput(btn, orderId, rowKey) {
    const container = document.getElementById(`edit_production_outputs_container_${orderId}`);
    const rows = container.querySelectorAll('.production-output-item');
    if (rows.length <= 1) {
        Swal.fire({
            title: '{{ __("passwords.validation_error") }}',
            text: '{{ __("passwords.add_at_least_one_output") }}',
            icon: 'warning', confirmButtonColor: '#0d6efd',
        });
        return;
    }

    const row = btn.closest('.production-output-item');
    const actualQty = parseFloat(row.querySelector('.output-actual-quantity')?.value) || 0;
    if (actualQty > 0) {
        Swal.fire({
            title: '{{ __("passwords.confirm_deletion") }}',
            html: '{{ __("passwords.produced_output_delete_warning") }}',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __("passwords.yes_delete_and_reverse") }}',
            cancelButtonText: '{{ __("auth._cancel") }}',
        }).then(r => { if (r.isConfirmed) { row.remove(); updateEditTotals(orderId); } });
    } else {
        row.remove();
        updateEditTotals(orderId);
    }
}

function handleEditVariantSelected(inputEl) {
    const orderId = inputEl.dataset.orderId;
    const index   = inputEl.dataset.itemIndex;   // "new_0" or "21"
    const type    = inputEl.dataset.type || 'input';
    const isInput = type === 'input';

    const rowId = isInput
        ? `edit_input_${orderId}_${index}`
        : `edit_output_${orderId}_${index}`;
    const datalistId = isInput
        ? `edit_material_list_input_${orderId}_${index}`
        : `edit_product_list_output_${orderId}_${index}`;
    const hiddenId = isInput
        ? `edit_material_id_input_${orderId}_${index}`
        : `edit_product_id_output_${orderId}_${index}`;

    const datalist = document.getElementById(datalistId);
    const hidden   = document.getElementById(hiddenId);
    const row      = document.getElementById(rowId);

    if (!datalist || !hidden || !row) {
        console.warn('[handleEditVariantSelected] Node missing', {
            orderId, index, isInput, rowId, datalistId, hiddenId,
        });
        return;
    }

    // ── Match by trimmed lowercase value ─────────────────────
    const typed = (inputEl.value || '').trim().toLowerCase();
    let matched = null;

    datalist.querySelectorAll('option').forEach(opt => {
        const candidate = (opt.value || '').trim().toLowerCase();
        if (candidate === typed && typed !== '') {
            matched = opt;
        }
    });

    if (!matched) {
        hidden.value = '';
        return;
    }

    hidden.value = matched.getAttribute('data-id');

    // ── Auto-select UOM ──────────────────────────────────────
    const uomId = matched.getAttribute('data-uom-id');
    const uomSelect = row.querySelector('select[name*="[unit]"]');

    if (uomSelect && uomId) {
        const hasOption = Array.from(uomSelect.options).some(
            o => String(o.value) === String(uomId)
        );

        if (hasOption) {
            uomSelect.value = String(uomId);
            uomSelect.dataset.selectedUnit = String(uomId);

            // Trigger change so any listeners react
            uomSelect.dispatchEvent(new Event('change', { bubbles: true }));

            // Sync Select2 wrapper if present
            if (typeof window.jQuery !== 'undefined'
                && window.jQuery.fn.select2
                && window.jQuery(uomSelect).hasClass('select2-hidden-accessible')) {
                window.jQuery(uomSelect).val(String(uomId)).trigger('change.select2');
            }
        } else {
            console.warn('[handleEditVariantSelected] UOM option missing', {
                uomId, rowId, optionsInSelect: uomSelect.options.length,
            });
        }
    }

    // ── Auto-fill cost for inputs ────────────────────────────
    if (isInput) {
        const costPrice = matched.getAttribute('data-cost-price');
        const costInput = row.querySelector('.input-cost');
        if (costInput && costPrice) {
            costInput.value = parseFloat(costPrice).toFixed(2);
        }
    }

    // ── Auto-fill strategy for outputs ───────────────────────
    if (!isInput) {
        const strategy = matched.getAttribute('data-inventory-strategy');
        const strategySelect = row.querySelector('.output-strategy-select');
        if (strategySelect && strategy) {
            strategySelect.value = strategy;
            strategySelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    updateEditTotals(orderId);
}

function loadEditAvailableBatches(orderId, inputIndex) {
    const row = document.getElementById(`edit_input_${orderId}_${inputIndex}`);
    if (!row) return;

    const variantId = document.getElementById(`edit_material_id_input_${orderId}_${inputIndex}`)?.value;
    if (!variantId) {
        Swal.fire({
            title: '{{ __("passwords.info") }}',
            text: '{{ __("passwords.select_material_first") }}',
            icon: 'info', confirmButtonColor: '#0d6efd',
        });
        return;
    }

    const locationId = getEditModalLocationId(orderId);

    Swal.fire({
        title: '{{ __("passwords.loading_batches") }}',
        text: '{{ __("passwords.please_wait") }}',
        allowOutsideClick: false, showConfirmButton: false,
        didOpen: () => Swal.showLoading(),
    });

    const url = new URL('/production-orders/available-batches', window.location.origin);
    url.searchParams.set('variant_id', variantId);
    if (locationId) url.searchParams.set('location_id', locationId);

    fetch(url)
        .then(r => r.json())
        .then(data => {
            Swal.close();
            const select = row.querySelector('.batch-source-select');
            if (!select) return;

            const currentValue = select.dataset.selectedBatch || select.value;
            select.innerHTML = '<option value="">{{ __("passwords.no_batch") }}</option>';

            if (data.success && Array.isArray(data.batches) && data.batches.length > 0) {
                data.batches.forEach(batch => {
                    const option = document.createElement('option');
                    option.value = batch.id;
                    const quantity = batch.quantity_remaining || 0;
                    option.textContent = `${batch.batch_number} (${quantity} units)`;
                    if (batch.expiry_date) option.textContent += ` - Exp: ${batch.expiry_date}`;
                    option.dataset.quantityRemaining = quantity;
                    option.dataset.unitCost = batch.unit_cost ?? '';
                    select.appendChild(option);
                });
                if (currentValue) select.value = currentValue;
                Swal.fire({
                    title: '{{ __("passwords.success") }}',
                    text: `${data.batches.length} {{ __("passwords.batches_loaded") }}`,
                    icon: 'success', timer: 1500, showConfirmButton: false,
                });
            } else {
                Swal.fire({
                    title: '{{ __("passwords.info") }}',
                    text: '{{ __("passwords.no_batches_available") }}',
                    icon: 'info', confirmButtonColor: '#0d6efd',
                });
            }
        })
        .catch(err => {
            Swal.close();
            console.error('Error loading batches:', err);
            Swal.fire({
                title: '{{ __("passwords.error") }}',
                text: '{{ __("passwords.failed_to_load_batches") }}',
                icon: 'error', confirmButtonColor: '#0d6efd',
            });
        });
}

function updateEditTotals(orderId) {
    let totalInputCost = 0;
    let totalOutputCost = 0;

    document.getElementById(`edit_production_inputs_container_${orderId}`)
        ?.querySelectorAll('.production-input-item').forEach(row => {
            const qty  = parseFloat(row.querySelector('.input-quantity')?.value) || 0;
            const cost = parseFloat(row.querySelector('.input-cost')?.value) || 0;
            totalInputCost += qty * cost;
        });

    document.getElementById(`edit_production_outputs_container_${orderId}`)
        ?.querySelectorAll('.production-output-item').forEach(row => {
            const qty  = parseFloat(row.querySelector('.output-quantity')?.value) || 0;
            const cost = parseFloat(row.querySelector('.output-cost')?.value) || 0;
            totalOutputCost += qty * cost;
        });

    const setText = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val.toFixed(2);
    };
    setText(`edit_total_input_cost_${orderId}`,    totalInputCost);
    setText(`edit_total_output_cost_${orderId}`,   totalOutputCost);
    setText(`edit_grand_total_input_${orderId}`,   totalInputCost);
    setText(`edit_grand_total_output_${orderId}`,  totalOutputCost);
    setText(`edit_grand_total_cost_${orderId}`,    totalInputCost + totalOutputCost);
}

function submitEditProductionOrderForm(orderId) {
    const form = document.getElementById(`kt_modal_edit_production_order_form_${orderId}`);
    const submitBtn = document.getElementById(`submitEditProductionOrderButton_${orderId}`);
    if (!form || !submitBtn) return;

    /* ─────────────────────────────────────────────────────────
     |  Resolve unit with fallback chain
     ───────────────────────────────────────────────────────── */
    function resolveUnit(row, variantId, isInput) {
        let unit = row.querySelector('select[name*="[unit]"]')?.value;
        let source = 'select';

        if (!unit) {
            const sel = row.querySelector('select[name*="[unit]"]');
            unit = sel?.dataset?.selectedUnit || null;
            source = 'data-selected-unit';
        }
        if (!unit && variantId) {
            const inputClass = isInput ? '.edit-material-typable-input' : '.edit-product-typable-input';
            const listId = row.querySelector(inputClass)?.getAttribute('list');
            if (listId) {
                const dl = document.getElementById(listId);
                dl?.querySelectorAll('option').forEach(opt => {
                    if (String(opt.dataset.id) === String(variantId)) {
                        unit = opt.dataset.uomId || null;
                        source = 'datalist';
                    }
                });
            }
        }

        if (!unit) {
            console.warn('[resolveUnit] No unit found', {
                rowId: row.id,
                variantId,
                isInput,
                selectValue: row.querySelector('select[name*="[unit]"]')?.value,
                datasetUnit: row.querySelector('select[name*="[unit]"]')?.dataset?.selectedUnit,
            });
        } else {
            console.log('[resolveUnit]', { rowId: row.id, variantId, unit, source });
        }

        return unit ? String(unit) : null;
    }

    /* ─────────────────────────────────────────────────────────
     |  Collect INPUTS
     ───────────────────────────────────────────────────────── */
    const inputs = [];
    document.getElementById(`edit_production_inputs_container_${orderId}`)
        ?.querySelectorAll('.production-input-item').forEach(row => {
            const rowKey = row.dataset.inputId;
            const isNew = typeof rowKey === 'string' && rowKey.startsWith('new_');
            const hiddenId = isNew ? null : parseInt(rowKey);
            const variantId = row.querySelector('input[name*="[product_variant_id]"]')?.value;
            const quantity = parseFloat(row.querySelector('.input-quantity')?.value) || 0;
            const cost = parseFloat(row.querySelector('.input-cost')?.value) || 0;
            const batchId = row.querySelector('.batch-source-select')?.value || null;
            const unit = resolveUnit(row, variantId, true);

            console.log('[collect input]', {
                rowId: row.id,
                rowKey,
                isNew,
                variantId,
                quantity,
                unit,
                willInclude: !!(variantId && quantity > 0 && unit),
            });

            if (variantId && quantity > 0 && unit) {
                inputs.push({
                    id: hiddenId,
                    product_variant_id: parseInt(variantId),
                    planned_quantity: quantity,
                    unit,
                    estimated_cost: cost,
                    purchase_receipt_item_id: batchId ? parseInt(batchId) : null,
                });
            } else {
                console.warn('[submit] Skipping input row', {
                    rowKey, variantId, quantity, unit,
                    reason: !variantId ? 'no variant' : (quantity <= 0 ? 'qty<=0' : 'no unit'),
                });
            }
        });

    /* ─────────────────────────────────────────────────────────
     |  Collect OUTPUTS
     ───────────────────────────────────────────────────────── */
    const outputs = [];
    document.getElementById(`edit_production_outputs_container_${orderId}`)
        ?.querySelectorAll('.production-output-item').forEach(row => {
            const rowKey = row.dataset.outputId;
            const isNew = typeof rowKey === 'string' && rowKey.startsWith('new_');
            const hiddenId = isNew ? null : parseInt(rowKey);
            const variantId = row.querySelector('input[name*="[product_variant_id]"]')?.value;
            const planned = parseFloat(row.querySelector('.output-quantity')?.value) || 0;
            const actual = parseFloat(row.querySelector('.output-actual-quantity')?.value) || 0;
            const defective = parseFloat(row.querySelector('.output-defective-quantity')?.value) || 0;
            const strategy = row.querySelector('.output-strategy-select')?.value;
            const cost = parseFloat(row.querySelector('.output-cost')?.value) || 0;
            const unit = resolveUnit(row, variantId, false);

            console.log('[collect output]', {
                rowId: row.id,
                rowKey,
                isNew,
                variantId,
                planned,
                unit,
                willInclude: !!(variantId && planned > 0 && unit),
            });

            if (variantId && planned > 0 && unit) {
                outputs.push({
                    id: hiddenId,
                    product_variant_id: parseInt(variantId),
                    planned_quantity: planned,
                    actual_quantity: actual,
                    defective_quantity: defective,
                    unit,
                    inventory_strategy: strategy,
                    production_cost: cost,
                });
            } else {
                console.warn('[submit] Skipping output row', {
                    rowKey, variantId, planned, unit,
                    reason: !variantId ? 'no variant' : (planned <= 0 ? 'planned<=0' : 'no unit'),
                });
            }
        });

    /* ─────────────────────────────────────────────────────────
     |  Log the FULL payload ONCE, after both loops complete
     ───────────────────────────────────────────────────────── */
    console.log('[submit] Final payload', {
        inputs_count: inputs.length,
        outputs_count: outputs.length,
        inputs,
        outputs,
    });

    /* ─────────────────────────────────────────────────────────
     |  Validation
     ───────────────────────────────────────────────────────── */
    if (inputs.length === 0) {
        Swal.fire({
            title: '{{ __("passwords.validation_error") }}',
            text: '{{ __("passwords.add_at_least_one_input") }}',
            icon: 'warning', confirmButtonColor: '#0d6efd',
        });
        return;
    }
    if (outputs.length === 0) {
        Swal.fire({
            title: '{{ __("passwords.validation_error") }}',
            text: '{{ __("passwords.add_at_least_one_output") }}',
            icon: 'warning', confirmButtonColor: '#0d6efd',
        });
        return;
    }

    /* ─────────────────────────────────────────────────────────
     |  Build payload + submit
     ───────────────────────────────────────────────────────── */
    const payload = {
        _method: 'PUT',
        scheduled_date: form.querySelector('input[name="scheduled_date"]')?.value || null,
        notes: form.querySelector('textarea[name="notes"]')?.value || null,
        inputs,
        outputs,
    };

    submitBtn.setAttribute('data-kt-indicator', 'on');
    submitBtn.disabled = true;

    fetch(`/production-orders/${orderId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        submitBtn.removeAttribute('data-kt-indicator');
        submitBtn.disabled = false;

        if (data.success) {
            const modalEl = document.getElementById(`editProductionModal${orderId}`);
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            Swal.fire({
                title: '{{ __("passwords.success") }}',
                text: data.message,
                icon: 'success', timer: 2000, showConfirmButton: false,
            }).then(() => {
                window.location.href = data.redirect || window.location.href;
            });
        } else {
            Swal.fire({
                title: '{{ __("passwords.error") }}',
                text: data.message || '{{ __("passwords.failed_to_update") }}',
                icon: 'error', confirmButtonColor: '#0d6efd',
            });
        }
    })
    .catch(err => {
        submitBtn.removeAttribute('data-kt-indicator');
        submitBtn.disabled = false;
        console.error('Edit submit error:', err);
        Swal.fire({
            title: '{{ __("passwords.error") }}',
            text: '{{ __("passwords.failed_to_update") }}',
            icon: 'error', confirmButtonColor: '#0d6efd',
        });
    });
}


if (!window.POEdit.delegationBound) {
    window.POEdit.delegationBound = true;

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('edit-material-typable-input') ||
            e.target.classList.contains('edit-product-typable-input')) {
            handleEditVariantSelected(e.target);
            return;
        }
        if (e.target.classList.contains('input-quantity') ||
            e.target.classList.contains('input-cost') ||
            e.target.classList.contains('output-quantity') ||
            e.target.classList.contains('output-cost')) {
            const modal = e.target.closest('[id^="editProductionModal"]');
            if (modal) updateEditTotals(modal.id.replace('editProductionModal', ''));
        }
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('edit-material-typable-input') ||
            e.target.classList.contains('edit-product-typable-input')) {
            handleEditVariantSelected(e.target);   // ✅ fire on blur
            return;
        }

        if (e.target.classList.contains('edit-material-typable-input') ||
            e.target.classList.contains('edit-product-typable-input')) {
            e.target.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (e.target.classList.contains('batch-source-select')) {
            const selectedOpt = e.target.options[e.target.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                const row = e.target.closest('.production-input-item');
                const qtyInput = row?.querySelector('.input-quantity');
                if (qtyInput && !qtyInput.value) {
                    qtyInput.value = selectedOpt.dataset.quantityRemaining || '';
                    qtyInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[id^="editProductionModal"]').forEach(modalEl => {
            modalEl.addEventListener('shown.bs.modal', function () {
                const orderId = modalEl.id.replace('editProductionModal', '');
                document.getElementById(`edit_production_inputs_container_${orderId}`)
                    ?.querySelectorAll('.production-input-item').forEach(row => {
                        resetEditSelect2InRow(row);
                        initEditSelect2InRow(row, orderId);
                    });
                document.getElementById(`edit_production_outputs_container_${orderId}`)
                    ?.querySelectorAll('.production-output-item').forEach(row => {
                        resetEditSelect2InRow(row);
                        initEditSelect2InRow(row, orderId);
                    });
                updateEditTotals(orderId);
            });
        });
    });
}
</script>
@endpush
@endonce