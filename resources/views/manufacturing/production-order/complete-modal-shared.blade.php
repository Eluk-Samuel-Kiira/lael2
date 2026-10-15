{{-- resources/views/manufacturing/production-order/complete-modal-shared.blade.php --}}

<div class="modal fade"
     id="completeProductionModalShared"
     tabindex="-1"
     aria-hidden="true"
     data-bs-backdrop="static"
     data-order-id=""
     data-is-single-shop="{{ $isSingleShop ? '1' : '0' }}">

    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"
         style="max-width: 1100px;">

        <div class="modal-content">

            {{-- ─── HEADER ────────────────────────────────────────── --}}
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white d-flex align-items-center flex-wrap gap-2">
                    <i class="bi bi-check-circle me-2"></i>
                    <span>
                        {{ __('passwords.complete_production') }}
                        <span class="text-white-50 mx-1">•</span>
                        <span class="fw-bold po-complete-number">—</span>
                    </span>
                    <span class="badge badge-light fw-bold po-complete-location"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- ─── BODY ──────────────────────────────────────────── --}}
            <div class="modal-body scroll-y mx-5 my-7"
                 style="max-height: 65vh; overflow-y: auto;">

                <div class="alert alert-info d-flex align-items-center mb-5">
                    <i class="bi bi-info-circle fs-2 me-3"></i>
                    <div>{{ __('passwords.complete_production_instruction') }}</div>
                </div>

                <form class="po-complete-form">
                    @csrf

                    {{-- ─── BATCH & EXPIRY ─────────────────────────── --}}
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
                                    <input type="text"
                                           name="batch_number"
                                           class="form-control po-complete-batch">
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
                                    <input type="date"
                                           name="expiry_date"
                                           class="form-control po-complete-expiry">
                                    <div class="form-text text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        {{ __('passwords.expiry_date_optional') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── ALLOCATION (multi-shop only, toggled by JS) ── --}}
                    <div class="card card-flush bg-light-info mb-6 po-complete-alloc-card d-none">
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
                                            class="form-select po-complete-location-select">
                                        <option value="">—</option>
                                        @foreach($locations ?? [] as $loc)
                                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">{{ __('auth._department') }}</label>
                                    <select name="output_department_id"
                                            class="form-select po-complete-department-select">
                                        <option value="">—</option>
                                        {{-- populated by JS based on location --}}
                                    </select>
                                </div>
                            </div>

                            {{-- Preload every department so JS can filter without a fetch --}}
                            <script type="application/json" class="po-complete-dept-catalog">
                                {!! json_encode(($departments ?? collect())->map(fn($d) => [
                                    'id' => $d->id,
                                    'name' => $d->name,
                                    'location_id' => $d->location_id,
                                ])->values()) !!}
                            </script>
                        </div>
                    </div>

                    {{-- ─── OUTPUT PRODUCTS ────────────────────────────── --}}
                    <div class="card card-flush mb-6">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-box-arrow-out me-2 text-success"></i>
                                {{ __('passwords.output_products') }}
                            </h3>
                            <div class="card-toolbar">
                                <button type="button"
                                        class="btn btn-sm btn-light-primary po-complete-add-row">
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
                                    <tbody class="po-complete-rows">
                                        {{-- Populated by JS --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- ─── SUMMARY ────────────────────────────────────── --}}
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
                                        <span class="fw-bold fs-2 text-success po-complete-total-produced">0.00</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <span class="text-muted d-block">{{ __('passwords.total_defective') }}</span>
                                        <span class="fw-bold fs-2 text-danger po-complete-total-defective">0.00</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <span class="text-muted d-block">{{ __('passwords.total_cost') }}</span>
                                        <span class="fw-bold fs-2 text-primary po-complete-total-cost">0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">{{ __('passwords.production_notes') }}</label>
                        <textarea name="notes"
                                  class="form-control"
                                  rows="2"
                                  placeholder="{{ __('passwords.enter_production_notes') }}"></textarea>
                    </div>
                </form>

            </div>

            {{-- ─── FOOTER ────────────────────────────────────────── --}}
            <div class="modal-footer d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-2"></i>{{ __('auth._cancel') }}
                </button>
                <button type="button" class="btn btn-success po-complete-submit">
                    <i class="bi bi-check-circle me-2"></i>
                    <span class="indicator-label">{{ __('passwords.complete_production') }}</span>
                    <span class="indicator-progress" style="display: none;">
                        {{ __('passwords.processing') }}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>

        </div>
    </div>
</div>

{{-- Row template cloned by JS when "Add Output" is clicked --}}
<template id="poCompleteOutputRowTemplate">
    <tr class="output-row" data-key="__KEY__">
        <td class="ps-4">
            <input type="text"
                   class="form-control output-variant-search"
                   placeholder="{{ __('passwords.type_or_select_product') }}"
                   autocomplete="off"
                   list="poCompleteVariantList">
            <input type="hidden"
                   name="outputs[__KEY__][product_variant_id]"
                   class="output-variant-id"
                   value="">
            <div class="mt-1">
                <span class="fw-bold text-gray-800 output-name-display d-none"></span>
            </div>
        </td>
        <td class="text-center"><span class="text-muted">—</span></td>
        <td class="text-center"><span class="text-muted">0.00</span></td>
        <td>
            <input type="number"
                   name="outputs[__KEY__][actual_quantity]"
                   class="form-control actual-quantity-input text-center"
                   min="0" step="0.01"
                   value=""
                   placeholder="0.00">
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
                @foreach($uoms ?? [] as $uom)
                    <option value="{{ $uom->id }}">{{ $uom->name }}@if($uom->symbol) ({{ $uom->symbol }})@endif</option>
                @endforeach
            </select>
        </td>
        <td class="text-center">
            <select name="outputs[__KEY__][inventory_strategy]"
                    class="form-select form-select-sm output-strategy-select">
                <option value="quantity">{{ __('passwords.quantity_tracking') }}</option>
                <option value="batch">{{ __('passwords.batch_tracking') }}</option>
                <option value="serial">{{ __('passwords.serial_tracking') }}</option>
            </select>
        </td>
        <td class="text-end">
            <button type="button"
                    class="btn btn-sm btn-icon btn-light-danger po-complete-remove-row">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

{{-- Variant list for the "new row" autocomplete --}}
<datalist id="poCompleteVariantList">
    @foreach($variants ?? [] as $variant)
        <option value="{{ $variant->name }}"
                data-id="{{ $variant->id }}"
                data-uom-id="{{ $variant->weight_unit }}"
                data-inventory-strategy="{{ $variant->product?->resolvedInventoryStrategy() ?? 'quantity' }}">
        </option>
    @endforeach
</datalist>