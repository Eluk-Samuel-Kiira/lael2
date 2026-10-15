{{-- resources/views/manufacturing/production-order/complete-modal-scripts.blade.php --}}
@once
@push('scripts')
<script>
window.POComplete = (function () {
    'use strict';

    const MODAL_ID = 'completeProductionModalShared';
    const ROUTES = {
        edit:     (id) => `/production-orders/${id}/edit`,
        complete: (id) => `/production-orders/${id}/complete-with-outputs`,
    };

    let newRowCounter = 0;

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

    function isSingleShop() {
        return getModalEl()?.dataset?.isSingleShop === '1';
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    function fmtNum(n, decimals = 2) {
        return Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    }

    function fmtMoney(n) {
        return '{{ currency_symbol() }}' + fmtNum(n, 2);
    }

    function toggleBusy(btn, busy) {
        if (!btn) return;
        btn.disabled = busy;
        const label    = btn.querySelector('.indicator-label');
        const progress = btn.querySelector('.indicator-progress');
        if (label)    label.style.display    = busy ? 'none' : 'inline';
        if (progress) progress.style.display = busy ? 'inline' : 'none';
    }

    // ── Render an existing output as a form row ────────────────
    function existingOutputRowHtml(orderId, output, uomLabel, strategyLabel) {
        const actual    = Number(output.actual_quantity ?? 0);
        const defective = Number(output.defective_quantity ?? 0);
        const planned   = Number(output.planned_quantity ?? 0);
        const remaining = Math.max(0, planned - actual);

        const stratBadge = output.inventory_strategy === 'batch' ? 'info'
                         : output.inventory_strategy === 'serial' ? 'warning' : 'primary';

        return `
            <tr class="output-row" data-key="${output.id}">
                <td class="ps-4">
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-gray-800 output-name-display">
                            ${escapeHtml(output.product_variant_name || '—')}
                        </span>
                        <span class="text-muted fs-7">SKU: ${escapeHtml(output.product_variant_sku || 'N/A')}</span>
                    </div>
                    <input type="hidden"
                           name="outputs[${output.id}][product_variant_id]"
                           class="output-variant-id"
                           value="${output.product_variant_id}">
                </td>
                <td class="text-center">
                    <span class="fw-bold">${fmtNum(planned, 2)}</span>
                </td>
                <td class="text-center">
                    ${actual > 0
                        ? `<span class="badge badge-success">${fmtNum(actual, 2)}</span>`
                        : `<span class="text-muted">0.00</span>`}
                </td>
                <td>
                    <input type="number"
                           name="outputs[${output.id}][actual_quantity]"
                           class="form-control actual-quantity-input text-center"
                           min="0" step="0.01"
                           value="${actual > 0 ? actual : ''}"
                           placeholder="0.00">
                    <small class="text-muted fs-7">
                        {{ __('passwords.max') }}: ${fmtNum(planned, 2)}
                        ${remaining > 0 ? `<span class="text-warning">({{ __('passwords.remaining') }}: ${fmtNum(remaining, 2)})</span>` : ''}
                    </small>
                </td>
                <td>
                    <input type="number"
                           name="outputs[${output.id}][defective_quantity]"
                           class="form-control defective-quantity-input text-center"
                           min="0" step="0.01"
                           value="${defective > 0 ? defective : ''}"
                           placeholder="0.00">
                </td>
                <td class="text-center">
                    <span class="badge badge-light-primary">${escapeHtml(uomLabel || output.unit || '')}</span>
                    <input type="hidden"
                           name="outputs[${output.id}][unit]"
                           class="output-unit-select"
                           value="${output.unit || ''}">
                </td>
                <td class="text-center">
                    <span class="badge badge-light-${stratBadge}">${escapeHtml(strategyLabel || output.inventory_strategy || '')}</span>
                    <input type="hidden"
                           name="outputs[${output.id}][inventory_strategy]"
                           class="output-strategy-select"
                           value="${output.inventory_strategy || 'quantity'}">
                </td>
                <td class="text-end">
                    <button type="button"
                            class="btn btn-sm btn-icon btn-light-danger po-complete-remove-row">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    // ── Recompute the summary totals ───────────────────────────
    function updateSummary() {
        const modalEl = getModalEl();
        if (!modalEl) return;

        let totalProduced  = 0;
        let totalDefective = 0;

        modalEl.querySelectorAll('.actual-quantity-input').forEach(input => {
            totalProduced += parseFloat(input.value) || 0;
        });
        modalEl.querySelectorAll('.defective-quantity-input').forEach(input => {
            totalDefective += parseFloat(input.value) || 0;
        });

        const setText = (sel, val) => {
            const el = modalEl.querySelector(sel);
            if (el) el.textContent = val;
        };

        setText('.po-complete-total-produced',  totalProduced.toFixed(2));
        setText('.po-complete-total-defective', totalDefective.toFixed(2));
    }

    // ── Populate and show ───────────────────────────────────────
    async function openForOrder(orderId) {
        if (!orderId) return;

        const modalEl = getModalEl();
        if (!modalEl) {
            console.warn('[POComplete] shared complete modal not found');
            return;
        }

        modalEl.dataset.orderId = orderId;
        newRowCounter = 0;

        // Clear previous state
        modalEl.querySelector('.po-complete-rows').innerHTML = '';
        modalEl.querySelector('textarea[name="notes"]').value = '';
        modalEl.querySelector('.po-complete-location-select').value = '';
        modalEl.querySelector('.po-complete-department-select').innerHTML = '<option value="">—</option>';

        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        try {
            const res = await fetch(ROUTES.edit(orderId), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();
            if (!data.success || !data.order) {
                window.toastr?.error?.(data.message || '{{ __("passwords.failed_to_load_order") }}');
                bootstrap.Modal.getInstance(modalEl)?.hide();
                return;
            }

            const order = data.order;

            // Header
            modalEl.querySelector('.po-complete-number').textContent   = order.production_number || '';
            modalEl.querySelector('.po-complete-location').textContent = order.location_name || '';

            // Auto-batch number default
            const todayStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
            modalEl.querySelector('.po-complete-batch').value =
                (order.production_number || 'PRD') + '-' + todayStr;

            // Expiry: reset to blank
            modalEl.querySelector('.po-complete-expiry').value = '';

            // Total cost
            const totalCost = Number(order.total_output_cost ?? order.total_cost ?? 0);
            modalEl.querySelector('.po-complete-total-cost').textContent = fmtMoney(totalCost);

            // Multi-shop allocation block
            if (!isSingleShop()) {
                modalEl.querySelector('.po-complete-alloc-card').classList.remove('d-none');

                // Pre-select the order's current location if it's in the list
                const locSel = modalEl.querySelector('.po-complete-location-select');
                if (order.location_id) {
                    Array.from(locSel.options).forEach(o => {
                        o.selected = String(o.value) === String(order.location_id);
                    });
                }

                // Populate departments filtered by the selected location
                populateDepartments(order.location_id, order.location_id);

                // Also populate from the JSON catalog for cross-filtering
                wireLocationCascade();
            } else {
                modalEl.querySelector('.po-complete-alloc-card').classList.add('d-none');
            }

            // Render output rows
            const tbody = modalEl.querySelector('.po-complete-rows');
            const uoms = @json($uoms ?? []);
            const uomsById = {};
            uoms.forEach(u => { uomsById[u.id] = u.name; });

            const strategyLabels = {
                quantity: '{{ __("passwords.quantity_tracking") }}',
                batch:    '{{ __("passwords.batch_tracking") }}',
                serial:   '{{ __("passwords.serial_tracking") }}',
            };

            (order.outputs || []).forEach(output => {
                const uomLabel = uomsById[output.unit] || output.unit || '';
                const strategyLabel = strategyLabels[output.inventory_strategy] || output.inventory_strategy || '';
                tbody.insertAdjacentHTML('beforeend',
                    existingOutputRowHtml(order.id, output, uomLabel, strategyLabel));
            });

            updateSummary();

        } catch (err) {
            console.error('[POComplete] load failed:', err);
            window.toastr?.error?.('{{ __("passwords.failed_to_load_order") }}');
            bootstrap.Modal.getInstance(modalEl)?.hide();
        }
    }

    // ── Location → Department cascade ───────────────────────────
    function getDepartmentCatalog() {
        const el = getModalEl()?.querySelector('.po-complete-dept-catalog');
        if (!el) return [];
        try {
            return JSON.parse(el.textContent || '[]');
        } catch (e) {
            return [];
        }
    }

    function populateDepartments(locationId, preselectId) {
        const sel = getModalEl()?.querySelector('.po-complete-department-select');
        if (!sel) return;

        sel.innerHTML = '<option value="">—</option>';

        const catalog = getDepartmentCatalog();
        const matching = locationId
            ? catalog.filter(d => String(d.location_id) === String(locationId))
            : catalog;

        matching.forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.name;
            if (preselectId && String(d.id) === String(preselectId)) {
                opt.selected = true;
            }
            sel.appendChild(opt);
        });
    }

    function wireLocationCascade() {
        const modalEl = getModalEl();
        if (!modalEl || modalEl.dataset.cascadeBound === '1') return;
        modalEl.dataset.cascadeBound = '1';

        modalEl.querySelector('.po-complete-location-select')
            ?.addEventListener('change', function () {
                populateDepartments(this.value, null);
            });
    }

    // ── Add / remove output rows ────────────────────────────────
    function addNewRow() {
        const modalEl = getModalEl();
        if (!modalEl) return;

        const tpl = document.getElementById('poCompleteOutputRowTemplate');
        if (!tpl) return;

        const key = 'new_' + (newRowCounter++);
        const html = tpl.innerHTML.replace(/__KEY__/g, key);

        modalEl.querySelector('.po-complete-rows').insertAdjacentHTML('beforeend', html);
        updateSummary();
    }

    function removeRow(btn) {
        const modalEl = getModalEl();
        if (!modalEl) return;

        const rows = modalEl.querySelectorAll('.po-complete-rows .output-row');
        if (rows.length <= 1) {
            Swal.fire({
                title: '{{ __("passwords.validation_error") }}',
                text: '{{ __("passwords.at_least_one_output_required") }}',
                icon: 'warning',
                confirmButtonColor: '#0d6efd',
            });
            return;
        }

        btn.closest('.output-row').remove();
        updateSummary();
    }

    // ── Variant autofill for new rows ───────────────────────────
    function wireVariantAutofill(e) {
        if (!e.target.classList.contains('output-variant-search')) return;

        const row = e.target.closest('.output-row');
        if (!row) return;

        const datalist = document.getElementById('poCompleteVariantList');
        if (!datalist) return;

        let matched = null;
        datalist.querySelectorAll('option').forEach(o => {
            if (o.value === e.target.value) matched = o;
        });

        const hidden   = row.querySelector('.output-variant-id');
        const display  = row.querySelector('.output-name-display');
        const unitSel  = row.querySelector('.output-unit-select');
        const stratSel = row.querySelector('.output-strategy-select');

        if (!matched) {
            if (hidden) hidden.value = '';
            return;
        }

        if (hidden) hidden.value = matched.dataset.id;

        const uomId = matched.dataset.uomId;
        if (unitSel && uomId) {
            const hasOption = Array.from(unitSel.options).some(o => String(o.value) === String(uomId));
            if (hasOption) unitSel.value = uomId;
        }

        const strategy = matched.dataset.inventoryStrategy;
        if (stratSel && strategy) stratSel.value = strategy;

        if (display) {
            display.textContent = matched.value;
            display.classList.remove('d-none');
        }
    }

    // ── Submit ──────────────────────────────────────────────────
    async function submit() {
        const modalEl = getModalEl();
        if (!modalEl) return;

        const orderId = currentOrderId();
        if (!orderId) return;

        const btn  = modalEl.querySelector('.po-complete-submit');
        const form = modalEl.querySelector('.po-complete-form');

        // Gather outputs
        // ✅ Send outputs as an OBJECT keyed by output_id (or "new_N" for new rows).
        //    The controller iterates by key: integer keys = existing rows,
        //    "new_*" keys = rows to create.
        const outputs = {};

        modalEl.querySelectorAll('.po-complete-rows .output-row').forEach(row => {
            const key = row.dataset.key;
            if (!key) return;

            const isNew = typeof key === 'string' && key.startsWith('new_');
            const variantId = row.querySelector('.output-variant-id')?.value;
            if (!variantId) return;

            const actual    = parseFloat(row.querySelector('.actual-quantity-input')?.value) || 0;
            const defective = parseFloat(row.querySelector('.defective-quantity-input')?.value) || 0;
            const unit      = row.querySelector('.output-unit-select')?.value
                        || row.querySelector('.output-unit-select')?.dataset?.selectedUnit
                        || null;
            const strategy  = row.querySelector('.output-strategy-select')?.value || 'quantity';

            if (!unit) return;

            // Key must be the integer id (as a string is fine) for existing rows,
            // or "new_N" for rows the user just added.
            outputs[key] = {
                product_variant_id: parseInt(variantId, 10),
                actual_quantity:    actual,
                defective_quantity: defective,
                unit:               unit,
                inventory_strategy: strategy,
            };
        });

        // Must have at least one with qty > 0
        const hasQuantity = Object.values(outputs).some(o => o.actual_quantity > 0);
        if (!hasQuantity) {
            Swal.fire({
                title: '{{ __("passwords.validation_error") }}',
                text: '{{ __("passwords.enter_at_least_one_actual_quantity") }}',
                icon: 'warning',
                confirmButtonColor: '#0d6efd',
            });
            return;
        }

        // Multi-shop requires location + department
        let locationId = null, departmentId = null;
        if (!isSingleShop()) {
            locationId   = modalEl.querySelector('.po-complete-location-select')?.value || null;
            departmentId = modalEl.querySelector('.po-complete-department-select')?.value || null;

            if (!locationId || !departmentId) {
                Swal.fire({
                    title: '{{ __("passwords.validation_error") }}',
                    text: '{{ __("passwords.output_allocation_required") }}',
                    icon: 'warning',
                    confirmButtonColor: '#0d6efd',
                });
                return;
            }
        }

        const payload = {
            outputs,                              // ← now an object, not an array
            batch_number: modalEl.querySelector('.po-complete-batch')?.value || null,
            expiry_date:  modalEl.querySelector('.po-complete-expiry')?.value || null,
            notes:        modalEl.querySelector('textarea[name="notes"]')?.value || null,
            complete:     true,
        };

        if (!isSingleShop()) {
            payload.output_location_id   = parseInt(locationId, 10);
            payload.output_department_id = parseInt(departmentId, 10);
        }

        toggleBusy(btn, true);

        try {
            const res = await fetch(ROUTES.complete(orderId), {
                method: 'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':     csrfToken(),
                },
                body: JSON.stringify(payload),
            });

            const json = await res.json();

            if (json.success) {
                window.toastr?.success?.(
                    json.message || '{{ __("passwords.production_completed") }}'
                );
                bootstrap.Modal.getInstance(modalEl)?.hide();
                window.ProductionPage?.reload?.();
            } else {
                if (json.errors && typeof json.errors === 'object') {
                    const first = Object.values(json.errors)[0]?.[0];
                    window.toastr?.error?.(first || json.message || '{{ __("passwords.error") }}');
                } else {
                    window.toastr?.error?.(json.message || '{{ __("passwords.error") }}');
                }
            }
        } catch (err) {
            console.error('[POComplete] submit failed:', err);
            window.toastr?.error?.('{{ __("passwords.error") }}');
        } finally {
            toggleBusy(btn, false);
        }
    }

    // ── Delegation ──────────────────────────────────────────────
    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('actual-quantity-input') ||
            e.target.classList.contains('defective-quantity-input')) {
            const modal = e.target.closest(`#${MODAL_ID}`);
            if (modal) updateSummary();
        }
        wireVariantAutofill(e);
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('.po-complete-add-row')) {
            e.preventDefault();
            addNewRow();
        }
        if (e.target.closest('.po-complete-remove-row')) {
            e.preventDefault();
            removeRow(e.target.closest('.po-complete-remove-row'));
        }
        if (e.target.closest('.po-complete-submit')) {
            e.preventDefault();
            submit();
        }
    });

    document.addEventListener('hidden.bs.modal', function (e) {
        if (e.target.id !== MODAL_ID) return;
        e.target.querySelector('.po-complete-rows').innerHTML = '';
    });

    return { openForOrder, submit, currentOrderId };
})();

window.ProductionActions = window.ProductionActions || {};
window.ProductionActions.complete = function (orderId) {
    window.POComplete.openForOrder(orderId);
};
</script>
@endpush
@endonce