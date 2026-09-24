@php
    $isSingleShop = tenant_is_single_shop($order->tenant_id);
    $uomsById     = $uoms->keyBy('id');
@endphp

<div class="modal fade" id="completeProductionModal{{ $order->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ __('passwords.complete_production') }} - {{ $order->production_number }} -
                    <div class="badge badge-light fw-bold">
                        {{ $order->location->name ?? __('pagination._none') }}
                    </div>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body scroll-y mx-5 my-7" style="max-height: 60vh; overflow-y: auto;">

                <div class="alert alert-info d-flex align-items-center mb-5">
                    <i class="bi bi-info-circle fs-2 me-3"></i>
                    <div>{{ __('passwords.complete_production_instruction') }}</div>
                </div>

                <form id="completeProductionForm{{ $order->id }}">
                    @csrf

                    {{-- ─── BATCH & EXPIRY ─────────────────────────────────── --}}
                    <div class="card card-flush bg-light mb-6">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-upc-scan me-2 text-primary"></i>
                                {{ __('passwords.batch_information') }}
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        {{ __('passwords.batch_number') }}
                                        <span class="text-muted fs-7">({{ __('passwords.auto_generated') }})</span>
                                    </label>
                                    <input type="text" name="batch_number" class="form-control"
                                           value="{{ $order->production_number }}-{{ date('Ymd') }}">
                                    <div class="form-text text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        {{ __('passwords.batch_number_generated_from_production') }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        {{ __('passwords.expiry_date') }}
                                        <span class="text-muted fs-7">({{ __('passwords.optional') }})</span>
                                    </label>
                                    <input type="date" name="expiry_date" class="form-control"
                                           min="{{ date('Y-m-d') }}">
                                    <div class="form-text text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        {{ __('passwords.expiry_date_optional') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── ALLOCATION (multi-shop only) ───────────────────── --}}
                    @if(!$isSingleShop)
                        <div class="card card-flush bg-light-info mb-6">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="bi bi-geo-alt me-2 text-info"></i>
                                    {{ __('passwords.production_allocation') }}
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info d-flex align-items-center mb-4">
                                    <i class="bi bi-info-circle fs-2 me-3"></i>
                                    <div>{{ __('passwords.production_allocation_instruction') }}</div>
                                </div>

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label required">{{ __('passwords.location') }}</label>
                                        <select name="output_location_id"
                                                id="output_location_{{ $order->id }}"
                                                class="form-select" required>
                                            <option value="">—</option>
                                            @foreach($locations as $loc)
                                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label required">{{ __('auth._department') }}</label>
                                        <select name="output_department_id"
                                                id="output_department_{{ $order->id }}"
                                                class="form-select" required>
                                            <option value="">—</option>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}"
                                                        data-location-id="{{ $dept->location_id }}">
                                                    {{ $dept->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ─── OUTPUT PRODUCTS ────────────────────────────────── --}}
                    <div class="card card-flush mb-6">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-box-arrow-out me-2 text-success"></i>
                                {{ __('passwords.output_products') }}
                            </h3>
                            <div class="card-toolbar">
                                <button type="button"
                                        class="btn btn-sm btn-light-primary"
                                        onclick="addOutputRow({{ $order->id }})">
                                    <i class="bi bi-plus-lg me-1"></i>
                                    {{ __('passwords.add_output') }}
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead>
                                        <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                            <th class="ps-4 min-w-200px">{{ __('passwords.product') }}</th>
                                            <th class="min-w-100px text-center">{{ __('passwords.planned') }}</th>
                                            <th class="min-w-100px text-center">{{ __('passwords.produced_so_far') }}</th>
                                            <th class="min-w-150px text-center text-primary">{{ __('passwords.actual_quantity') }}</th>
                                            <th class="min-w-120px text-center">{{ __('passwords.defective_quantity') }}</th>
                                            <th class="min-w-100px text-center">{{ __('passwords.unit') }}</th>
                                            <th class="min-w-100px text-center">{{ __('passwords.strategy') }}</th>
                                            <th class="min-w-50px text-end"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="outputRowsContainer{{ $order->id }}">
                                        @foreach($order->outputs as $output)
                                            @php
                                                $variant   = $output->productVariant;
                                                $uom       = $uomsById[$output->unit] ?? null;
                                                $planned   = $output->planned_quantity;
                                                $actual    = $output->actual_quantity;
                                                $defective = $output->defective_quantity;
                                                $remaining = max(0, $planned - $actual);
                                            @endphp
                                            <tr class="output-row" data-key="{{ $output->id }}">
                                                <td class="ps-4">
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold text-gray-800 output-name-display">
                                                            {{ $variant->name ?? '—' }}
                                                        </span>
                                                        <span class="text-muted fs-7">SKU: {{ $variant->sku ?? 'N/A' }}</span>
                                                    </div>
                                                    <input type="hidden"
                                                           name="outputs[{{ $output->id }}][product_variant_id]"
                                                           class="output-variant-id"
                                                           value="{{ $output->product_variant_id }}">
                                                </td>
                                                <td class="text-center">
                                                    <span class="fw-bold">{{ number_format($planned, 2) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if($actual > 0)
                                                        <span class="badge badge-success">{{ number_format($actual, 2) }}</span>
                                                    @else
                                                        <span class="text-muted">0.00</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           name="outputs[{{ $output->id }}][actual_quantity]"
                                                           class="form-control actual-quantity-input text-center"
                                                           min="0" step="0.01"
                                                           value="{{ $actual > 0 ? $actual : '' }}"
                                                           placeholder="0.00"
                                                           data-output-id="{{ $output->id }}"
                                                           data-planned="{{ $planned }}"
                                                           data-remaining="{{ $remaining }}">
                                                    <small class="text-muted fs-7">
                                                        {{ __('passwords.max') }}: {{ number_format($planned, 2) }}
                                                        @if($remaining > 0)
                                                            <span class="text-warning">({{ __('passwords.remaining') }}: {{ number_format($remaining, 2) }})</span>
                                                        @endif
                                                    </small>
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           name="outputs[{{ $output->id }}][defective_quantity]"
                                                           class="form-control defective-quantity-input text-center"
                                                           min="0" step="0.01"
                                                           value="{{ $defective > 0 ? $defective : '' }}"
                                                           placeholder="0.00"
                                                           data-output-id="{{ $output->id }}">
                                                    <small class="text-muted fs-7">{{ __('passwords.defective') }}</small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-primary">
                                                        {{ $uom->name ?? $output->unit }}
                                                        @if($uom?->symbol) ({{ $uom->symbol }}) @endif
                                                    </span>
                                                    <input type="hidden"
                                                           name="outputs[{{ $output->id }}][unit]"
                                                           class="output-unit-select"
                                                           value="{{ $output->unit }}">
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $output->inventory_strategy == 'batch' ? 'info' : ($output->inventory_strategy == 'serial' ? 'warning' : 'primary') }}">
                                                        {{ ucfirst($output->inventory_strategy) }}
                                                    </span>
                                                    <input type="hidden"
                                                           name="outputs[{{ $output->id }}][inventory_strategy]"
                                                           class="output-strategy-select"
                                                           value="{{ $output->inventory_strategy }}">
                                                </td>
                                                <td class="text-end">
                                                    <button type="button"
                                                            class="btn btn-sm btn-icon btn-light-danger"
                                                            onclick="removeOutputRow(this, {{ $order->id }})">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- ─── PRODUCTION SUMMARY ─────────────────────────────── --}}
                    <div class="card card-flush bg-light-primary mb-6">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-calculator me-2"></i>
                                {{ __('passwords.production_summary') }}
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <span class="text-muted d-block">{{ __('passwords.total_produced') }}</span>
                                        <span class="fw-bold fs-2 text-success"
                                              id="total_produced_display_{{ $order->id }}">0.00</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <span class="text-muted d-block">{{ __('passwords.total_defective') }}</span>
                                        <span class="fw-bold fs-2 text-danger"
                                              id="total_defective_display_{{ $order->id }}">0.00</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <span class="text-muted d-block">{{ __('passwords.total_cost') }}</span>
                                        <span class="fw-bold fs-2 text-primary">
                                            {{ number_format($order->total_output_cost ?? 0, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">{{ __('passwords.production_notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="{{ __('passwords.enter_production_notes') }}"></textarea>
                    </div>
                </form>
            </div>

            <div class="modal-footer d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-2"></i>{{ __('auth._cancel') }}
                </button>
                <button type="button" class="btn btn-success" onclick="completeProductionWithOutputs({{ $order->id }})">
                    <i class="bi bi-check-circle me-2"></i>
                    <span class="indicator-label">{{ __('passwords.complete_production') }}</span>
                    <span class="indicator-progress">{{ __('passwords.processing') }}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Row template used by addOutputRow() --}}
<template id="outputRowTemplate{{ $order->id }}">
    <tr class="output-row" data-key="__KEY__">
        <td class="ps-4">
            <input type="text"
                   class="form-control output-variant-search"
                   list="output_variant_list_{{ $order->id }}"
                   placeholder="{{ __('passwords.type_or_select_product') }}"
                   autocomplete="off">
            <input type="hidden"
                   name="outputs[__KEY__][product_variant_id]"
                   class="output-variant-id"
                   value="">
            <div class="mt-1">
                <span class="fw-bold text-gray-800 output-name-display d-none"></span>
            </div>
        </td>
        <td class="text-center">
            <span class="text-muted">—</span>
        </td>
        <td class="text-center">
            <span class="text-muted">0.00</span>
        </td>
        <td>
            <input type="number"
                   name="outputs[__KEY__][actual_quantity]"
                   class="form-control actual-quantity-input text-center"
                   min="0" step="0.01"
                   value=""
                   placeholder="0.00"
                   data-planned="0"
                   data-remaining="0">
        </td>
        <td>
            <input type="number"
                   name="outputs[__KEY__][defective_quantity]"
                   class="form-control defective-quantity-input text-center"
                   min="0" step="0.01"
                   value=""
                   placeholder="0.00">
        </td>
        <td class="text-center">
            <select name="outputs[__KEY__][unit]" class="form-select form-select-sm output-unit-select">
                @foreach($uoms as $uom)
                    <option value="{{ $uom->id }}">
                        {{ $uom->name }}@if($uom->symbol) ({{ $uom->symbol }})@endif
                    </option>
                @endforeach
            </select>
        </td>
        <td class="text-center">
            <select name="outputs[__KEY__][inventory_strategy]"
                    class="form-select form-select-sm output-strategy-select">
                <option value="quantity">Quantity</option>
                <option value="batch">Batch</option>
                <option value="serial">Serial</option>
            </select>
        </td>
        <td class="text-end">
            <button type="button"
                    class="btn btn-sm btn-icon btn-light-danger"
                    onclick="removeOutputRow(this, {{ $order->id }})">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

{{-- Datalist for variant autocomplete --}}
<datalist id="output_variant_list_{{ $order->id }}">
    @foreach($variants as $variant)
        <option value="{{ $variant->name }}"
                data-id="{{ $variant->id }}"
                data-uom-id="{{ $variant->weight_unit }}"
                data-inventory-strategy="{{ $variant->product?->resolvedInventoryStrategy() ?? 'quantity' }}">
        </option>
    @endforeach
</datalist>

<script>
// ── Real-time summary update ────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('input', function (e) {
        const t = e.target;
        if (t.classList.contains('actual-quantity-input') ||
            t.classList.contains('defective-quantity-input')) {
            const form = t.closest('form[id^="completeProductionForm"]');
            if (!form) return;
            const orderId = form.id.replace('completeProductionForm', '');
            updateProductionSummary(orderId);
        }
    });

    // Seed summary on load for each visible form
    document.querySelectorAll('form[id^="completeProductionForm"]').forEach(form => {
        updateProductionSummary(form.id.replace('completeProductionForm', ''));
    });
});

function updateProductionSummary(orderId) {
    let totalProduced  = 0;
    let totalDefective = 0;

    document.querySelectorAll(`#completeProductionForm${orderId} .actual-quantity-input`).forEach(input => {
        totalProduced += parseFloat(input.value) || 0;
    });
    document.querySelectorAll(`#completeProductionForm${orderId} .defective-quantity-input`).forEach(input => {
        totalDefective += parseFloat(input.value) || 0;
    });

    const producedDisplay  = document.getElementById(`total_produced_display_${orderId}`);
    const defectiveDisplay = document.getElementById(`total_defective_display_${orderId}`);

    if (producedDisplay)  producedDisplay.textContent  = totalProduced.toFixed(2);
    if (defectiveDisplay) defectiveDisplay.textContent = totalDefective.toFixed(2);
}

// ── Add / remove output rows ────────────────────────────────
let outputRowCounter = {};

function addOutputRow(orderId) {
    if (!outputRowCounter[orderId]) outputRowCounter[orderId] = 0;
    const key = `new_${outputRowCounter[orderId]++}`;

    const tpl = document.getElementById(`outputRowTemplate${orderId}`);
    if (!tpl) return;

    const html  = tpl.innerHTML.replace(/__KEY__/g, key);
    const tbody = document.getElementById(`outputRowsContainer${orderId}`);
    tbody.insertAdjacentHTML('beforeend', html);
}

function removeOutputRow(btn, orderId) {
    const tbody = document.getElementById(`outputRowsContainer${orderId}`);
    if (!tbody) return;

    const rows = tbody.querySelectorAll('.output-row');
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
    updateProductionSummary(orderId);
}

// ── Variant autofill for newly added rows ───────────────────
document.addEventListener('input', function (e) {
    if (!e.target.classList.contains('output-variant-search')) return;

    const row = e.target.closest('.output-row');
    if (!row) return;

    const form    = row.closest('form');
    const orderId = form.id.replace('completeProductionForm', '');
    const datalist = document.getElementById(`output_variant_list_${orderId}`);
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
        const found = Array.from(unitSel.options).some(o => String(o.value) === String(uomId));
        if (found) unitSel.value = uomId;
    }

    const strategy = matched.dataset.inventoryStrategy;
    if (stratSel && strategy) stratSel.value = strategy;

    if (display) {
        display.textContent = matched.value;
        display.classList.remove('d-none');
    }
});

// ── Department filter by location (multi-shop) ──────────────
document.addEventListener('change', function (e) {
    const loc = e.target.closest('select[id^="output_location_"]');
    if (!loc) return;

    const orderId = loc.id.replace('output_location_', '');
    const deptSel = document.getElementById(`output_department_${orderId}`);
    if (!deptSel) return;

    const locId = loc.value;

    Array.from(deptSel.options).forEach(opt => {
        if (!opt.value) return;
        opt.hidden   = locId && String(opt.dataset.locationId) !== String(locId);
        opt.disabled = opt.hidden;
    });

    const current = deptSel.options[deptSel.selectedIndex];
    if (current && current.hidden) deptSel.value = '';
});

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}
</script>