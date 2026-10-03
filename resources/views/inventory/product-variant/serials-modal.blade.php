{{-- ============================================================
     Serial Number Management Modal
     - Server-fetched departments on location change
     - Per-serial pricing: view + edit
     - Search + status filter + per-page + pagination
     ============================================================ --}}

{{-- ─── Serial Management Modal ───────────────────────────── --}}
<div class="modal fade" id="serialManagementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    <i class="bi bi-upc-scan me-2 text-primary"></i>
                    {{ __('pagination.serial_numbers') }}
                    <span id="serialVariantName" class="fs-6 text-muted ms-2"></span>
                </h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>

            <div class="modal-body px-5 my-7">

                {{-- Summary Cards --}}
                <div class="row g-4 mb-6" id="serialSummary">
                    <div class="col-6 col-md-2">
                        <div class="card bg-light-primary">
                            <div class="card-body text-center">
                                <div class="fs-4 fw-bold text-primary" id="totalSerials">0</div>
                                <div class="text-muted fs-7">{{ __('passwords.total') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card bg-light-success">
                            <div class="card-body text-center">
                                <div class="fs-4 fw-bold text-success" id="availableSerials">0</div>
                                <div class="text-muted fs-7">{{ __('passwords.available') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card bg-light-danger">
                            <div class="card-body text-center">
                                <div class="fs-4 fw-bold text-danger" id="soldSerials">0</div>
                                <div class="text-muted fs-7">{{ __('passwords.sold') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card bg-light-warning">
                            <div class="card-body text-center">
                                <div class="fs-4 fw-bold text-warning" id="reservedSerials">0</div>
                                <div class="text-muted fs-7">{{ __('passwords.reserved') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card bg-light-info">
                            <div class="card-body text-center">
                                <div class="fs-4 fw-bold text-info" id="returnedSerials">0</div>
                                <div class="text-muted fs-7">{{ __('passwords.returned') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card bg-light-secondary">
                            <div class="card-body text-center">
                                <div class="fs-4 fw-bold text-secondary" id="damagedSerials">0</div>
                                <div class="text-muted fs-7">{{ __('passwords.damaged') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Generate / Import / Assign --}}
                <div class="row g-3 mb-6">
                    <div class="col-md-4">
                        <div class="card card-dashed p-3 h-100">
                            <h6 class="fw-bold mb-2">{{ __('passwords.generate_serials') }}</h6>
                            <div class="d-flex gap-2">
                                <input type="number" id="generateSerialQuantity"
                                       class="form-control form-control-sm"
                                       placeholder="{{ __('passwords.quantity') }}"
                                       min="1" max="1000" value="1">
                                <input type="text" id="generateSerialPrefix"
                                       class="form-control form-control-sm"
                                       placeholder="{{ __('passwords.prefix') }}"
                                       maxlength="10" style="max-width: 120px;">
                                <button type="button" class="btn btn-sm btn-primary"
                                        onclick="SerialModal.generateSerials()">
                                    <i class="bi bi-plus-circle me-1"></i> {{ __('passwords.generate') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card card-dashed p-3 h-100">
                            <h6 class="fw-bold mb-2">{{ __('passwords.import_serials') }}</h6>
                            <div class="d-flex gap-2">
                                <textarea id="importSerialInput"
                                          class="form-control form-control-sm"
                                          placeholder="{{ __('passwords.enter_serials_one_per_line') }}"
                                          rows="2"
                                          style="resize: vertical; min-height: 50px;"></textarea>
                                <button type="button" class="btn btn-sm btn-success"
                                        onclick="SerialModal.importSerials()"
                                        style="align-self: flex-end;">
                                    <i class="bi bi-upload me-1"></i> {{ __('passwords.import') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card card-dashed p-3 h-100">
                            <h6 class="fw-bold mb-2">{{ __('passwords.assign_serials') }}</h6>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label fs-8 fw-semibold text-muted mb-1">
                                        {{ __('passwords.select_location') }}
                                    </label>
                                    <select id="assignLocationId" class="form-select form-select-sm">
                                        <option value="">{{ __('passwords.select_location') }}</option>
                                        @foreach($locations ?? [] as $location)
                                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fs-8 fw-semibold text-muted mb-1">
                                        {{ __('passwords.select_department') }}
                                    </label>
                                    <select id="assignDepartmentId" class="form-select form-select-sm">
                                        <option value="">{{ __('passwords.select_department') }}</option>
                                        @foreach($departments ?? [] as $department)
                                            <option value="{{ $department->id }}"
                                                    data-location-id="{{ $department->location_id ?? '' }}">
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div id="assignDepartmentEmptyHint"
                                 class="text-muted fs-8 mb-2 d-none">
                                {{ __('passwords.no_departments_for_location') }}
                            </div>

                            <button type="button" class="btn btn-sm btn-warning w-100"
                                    onclick="SerialModal.assignSelectedSerials()">
                                <i class="bi bi-tags me-1"></i>
                                {{ __('passwords.assign_serials') }}
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Search / status / per-page --}}
                <div class="row g-3 mb-4 align-items-center">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">
                                <i class="ki-duotone ki-magnifier fs-4">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <input type="text"
                                   id="serialSearchInput"
                                   class="form-control"
                                   placeholder="{{ __('passwords.search_serials_placeholder') }}"
                                   autocomplete="off">
                            <button class="btn btn-light" type="button"
                                    id="serialSearchClear"
                                    title="{{ __('passwords.clear') }}">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select id="serialStatusFilter" class="form-select form-select-sm">
                            <option value="all">{{ __('passwords.all_status') }}</option>
                            <option value="available">{{ __('passwords.available') }}</option>
                            <option value="reserved">{{ __('passwords.reserved') }}</option>
                            <option value="sold">{{ __('passwords.sold') }}</option>
                            <option value="returned">{{ __('passwords.returned') }}</option>
                            <option value="lost">{{ __('passwords.lost') }}</option>
                            <option value="damaged">{{ __('passwords.damaged') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="serialPerPage" class="form-select form-select-sm">
                            <option value="15" selected>15 / {{ __('passwords.page') }}</option>
                            <option value="25">25 / {{ __('passwords.page') }}</option>
                            <option value="50">50 / {{ __('passwords.page') }}</option>
                            <option value="100">100 / {{ __('passwords.page') }}</option>
                        </select>
                    </div>
                </div>

                {{-- Select-all row --}}
                <div class="mb-3">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="selectAllSerials"
                                   onchange="SerialModal.toggleAllSerials(this.checked)">
                            <label class="form-check-label" for="selectAllSerials">
                                {{ __('passwords.select_all') }}
                            </label>
                        </div>
                        <span class="badge badge-light-primary">
                            <span id="selectedSerialsCount">0</span>
                            {{ __('passwords.selected') }}
                        </span>
                    </div>
                </div>

                {{-- Serials table --}}
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                        <thead>
                            <tr class="fw-bold fs-7 text-gray-500 border-bottom-0">
                                <th class="min-w-50px">#</th>
                                <th class="min-w-140px">{{ __('passwords.serial_number') }}</th>
                                <th class="min-w-100px">{{ __('passwords.status') }}</th>
                                <th class="min-w-120px">{{ __('passwords.location') }}</th>
                                <th class="min-w-120px">{{ __('passwords.department') }}</th>
                                <th class="min-w-100px text-end">{{ __('passwords.cost_price') }}</th>
                                <th class="min-w-100px text-end">{{ __('passwords.selling_price') }}</th>
                                <th class="min-w-100px text-end">{{ __('passwords.profit') }}</th>
                                <th class="min-w-100px text-end">{{ __('auth._actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="serialTableBody">
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    {{ __('passwords.no_serials_found') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Pagination footer --}}
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-4">
                    <div class="text-muted fs-7" id="serialPageInfo"></div>
                    <div class="d-flex gap-2">
                        <button type="button"
                                class="btn btn-sm btn-light"
                                id="serialPrevBtn"
                                onclick="SerialModal.goToPage(SerialModal.state.currentPage - 1)">
                            <i class="bi bi-chevron-left"></i>
                            {{ __('pagination.previous') }}
                        </button>
                        <button type="button"
                                class="btn btn-sm btn-light"
                                id="serialNextBtn"
                                onclick="SerialModal.goToPage(SerialModal.state.currentPage + 1)">
                            {{ __('pagination.next') }}
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    {{ __('auth._discard') }}
                </button>
                <button type="button" class="btn btn-primary"
                        onclick="SerialModal.refresh()">
                    <i class="bi bi-arrow-clockwise me-1"></i> {{ __('passwords.refresh') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ─── Edit Serial Pricing Modal ─────────────────────────── --}}
<div class="modal fade" id="serialPricingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="bi bi-currency-exchange me-2 text-primary"></i>
                    {{ __('passwords.edit_pricing') }}
                    <span id="pricingSerialNumber" class="fs-6 text-muted ms-2"></span>
                </h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>

            <form id="serialPricingForm" onsubmit="SerialModal.savePricing(event)">
                <div class="modal-body">
                    <input type="hidden" id="pricingSerialId">

                    <div class="alert alert-light-info d-flex align-items-center mb-4">
                        <i class="ki-duotone ki-information-5 fs-2 me-3">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <div class="fs-7">
                            {{ __('passwords.pricing_help_text') }}
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('passwords.supplier_cost') }}</label>
                            <input type="number" step="0.01" min="0"
                                   class="form-control" id="pricingSupplierCost"
                                   placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('passwords.other_costs') }}</label>
                            <input type="number" step="0.01" min="0"
                                   class="form-control" id="pricingOtherCosts"
                                   placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('passwords.selling_price') }}</label>
                            <input type="number" step="0.01" min="0"
                                   class="form-control" id="pricingSellingPrice"
                                   placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('passwords.discount') }} (%)</label>
                            <input type="number" step="0.01" min="0" max="100"
                                   class="form-control" id="pricingDiscountPercent"
                                   placeholder="0">
                        </div>
                    </div>

                    <div class="separator my-5"></div>

                    <div class="row g-3 fs-7">
                        <div class="col-6 text-muted">{{ __('passwords.grand_total_cost') }}:</div>
                        <div class="col-6 text-end fw-bold" id="pricingPreviewCost">—</div>

                        <div class="col-6 text-muted">{{ __('passwords.effective_selling_price') }}:</div>
                        <div class="col-6 text-end fw-bold text-primary" id="pricingPreviewSelling">—</div>

                        <div class="col-6 text-muted">{{ __('passwords.profit_per_unit') }}:</div>
                        <div class="col-6 text-end fw-bold" id="pricingPreviewProfit">—</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        {{ __('auth._discard') }}
                    </button>
                    <button type="submit" class="btn btn-primary" id="pricingSaveBtn">
                        <i class="bi bi-check2 me-1"></i> {{ __('passwords.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ────────────────────────────────────────────────────────
     JavaScript — single namespace `window.SerialModal`
     ──────────────────────────────────────────────────────── --}}
<script>
(function () {
    'use strict';

    // ───────────────────────────────────────────────────────
    // Public namespace — every method the HTML calls lives here
    // ───────────────────────────────────────────────────────
    const SerialModal = window.SerialModal = window.SerialModal || {};

    // ─── Module state ──────────────────────────────────────
    let currentVariantId = null;
    let currentSerialMap = {};
    let searchDebounceTimer = null;

    SerialModal.state = {
        search:      '',
        status:      'all',
        perPage:     15,
        currentPage: 1,
        lastPage:    1,
    };

    const csrfToken = () =>
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    // Run a callback once the DOM is ready — works whether the
    // script executes before or after DOMContentLoaded.
    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    // ─── Utilities ─────────────────────────────────────────
    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g,  '&amp;')
            .replace(/</g,  '&lt;')
            .replace(/>/g,  '&gt;')
            .replace(/"/g,  '&quot;')
            .replace(/'/g,  '&#039;');
    }

    function numberOrNull(id) {
        const el = document.getElementById(id);
        if (!el) return null;
        const raw = el.value;
        if (raw === '' || raw === null) return null;
        const n = parseFloat(raw);
        return isNaN(n) ? null : n;
    }

    // ─── Modal open ────────────────────────────────────────
    SerialModal.open = function (variantId, variantName) {
        currentVariantId = variantId;

        const nameEl = document.getElementById('serialVariantName');
        if (nameEl) nameEl.textContent = '- ' + variantName;

        // Reset state
        SerialModal.state = {
            search:      '',
            status:      'all',
            perPage:     15,
            currentPage: 1,
            lastPage:    1,
        };

        const searchInput  = document.getElementById('serialSearchInput');
        const statusFilter = document.getElementById('serialStatusFilter');
        const perPageSel   = document.getElementById('serialPerPage');
        if (searchInput)  searchInput.value = '';
        if (statusFilter) statusFilter.value = 'all';
        if (perPageSel)   perPageSel.value = '15';

        // Loading spinner
        const tbody = document.getElementById('serialTableBody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">{{ __('passwords.loading_serials') }}</p>
                    </td>
                </tr>`;
        }

        const modalEl = document.getElementById('serialManagementModal');
        if (modalEl && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        SerialModal.resetAssignFilters();
        SerialModal.refresh();
    };

    // ─── Refresh (fetch + render) ──────────────────────────
    SerialModal.refresh = function () {
        if (!currentVariantId) return;

        const s = SerialModal.state;

        const params = new URLSearchParams({
            page:     String(s.currentPage),
            per_page: String(s.perPage),
        });
        if (s.search)                          params.set('search', s.search);
        if (s.status && s.status !== 'all')    params.set('status', s.status);

        const url = `/serials/variant/${currentVariantId}?${params.toString()}`;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    renderTable(data.data);
                    renderPagination(data.data.pagination);
                } else {
                    toastr.error(data.message || '{{ __("passwords.error_loading_serials") }}');
                }
            })
            .catch(err => {
                console.error('[serials] refresh failed', err);
                toastr.error('{{ __("passwords.error_loading_serials") }}');
            });
    };

    // ─── Pagination ────────────────────────────────────────
    function renderPagination(pagination) {
        if (!pagination) return;

        SerialModal.state.currentPage = pagination.current_page;
        SerialModal.state.lastPage    = pagination.last_page;

        const info = document.getElementById('serialPageInfo');
        const prev = document.getElementById('serialPrevBtn');
        const next = document.getElementById('serialNextBtn');

        if (pagination.total === 0) {
            if (info) info.textContent = '';
            if (prev) prev.disabled = true;
            if (next) next.disabled = true;
            return;
        }

        if (info) {
            info.textContent =
                `{{ __('passwords.showing') }} ${pagination.from}–${pagination.to} ` +
                `{{ __('passwords.of') }} ${pagination.total}`;
        }

        if (prev) prev.disabled = !pagination.has_prev;
        if (next) next.disabled = !pagination.has_next;
    }

    SerialModal.goToPage = function (page) {
        const s = SerialModal.state;
        if (page < 1 || page > s.lastPage || page === s.currentPage) return;
        s.currentPage = page;
        SerialModal.refresh();
    };

    // ─── Render table + summary ────────────────────────────
    function renderTable(data) {
        const summary = data.summary || {};
        const serials = data.serials || [];

        currentSerialMap = {};
        serials.forEach(s => { currentSerialMap[s.id] = s; });

        setText('totalSerials',     summary.total     ?? 0);
        setText('availableSerials', summary.available ?? 0);
        setText('soldSerials',      summary.sold      ?? 0);
        setText('reservedSerials',  summary.reserved  ?? 0);
        setText('returnedSerials',  summary.returned  ?? 0);
        setText('damagedSerials',   summary.damaged   ?? 0);

        const tbody = document.getElementById('serialTableBody');
        if (!tbody) return;

        if (serials.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        {{ __('passwords.no_serials_found') }}
                    </td>
                </tr>`;
            setText('selectedSerialsCount', '0');
            return;
        }

        const statusColors = {
            available: 'success',
            sold:      'danger',
            reserved:  'warning',
            returned:  'info',
            lost:      'secondary',
            damaged:   'dark',
        };

        const currency = @json(currency_symbol());

        tbody.innerHTML = serials.map((serial, index) => {
            const statusColor = statusColors[serial.status] || 'secondary';

            const costPrice     = Number(serial.unit_cost_price ?? 0);
            const sellingPrice  = Number(serial.selling_price   ?? 0);
            const profitUnit    = Number(serial.profit_per_unit ?? 0);
            const profitMargin  = Number(serial.profit_margin   ?? 0);
            const soldUnitPrice = (serial.sold_unit_price !== null && serial.sold_unit_price !== undefined)
                ? Number(serial.sold_unit_price) : null;

            const locationName   = serial.location_name   || 'N/A';
            const departmentName = serial.department_name || 'N/A';

            const profitClass = profitUnit >= 0 ? 'success' : 'danger';
            const customBadge = serial.has_custom_pricing
                ? `<span class="badge badge-light-warning ms-1" title="{{ __('passwords.custom_pricing') }}">
                       <i class="bi bi-pencil-square"></i>
                   </span>`
                : '';

            const priceCell = soldUnitPrice !== null
                ? `<span class="badge badge-light-danger">${currency}${soldUnitPrice.toFixed(2)}</span>
                   <br><small class="text-muted">{{ __('passwords.was') }} ${currency}${sellingPrice.toFixed(2)}</small>`
                : `${currency}${sellingPrice.toFixed(2)}`;

            const actions = [];

            if (serial.status === 'available') {
                actions.push(`
                    <input type="checkbox" class="form-check-input serial-checkbox"
                           value="${serial.id}"
                           onchange="SerialModal.updateSelectedCount()">`);
                actions.push(`
                    <button class="btn btn-sm btn-icon btn-light-primary"
                            onclick="SerialModal.updateStatus(${serial.id}, 'sold')"
                            title="{{ __('passwords.mark_sold') }}">
                        <i class="bi bi-cart-check fs-5"></i>
                    </button>`);
                actions.push(`
                    <button class="btn btn-sm btn-icon btn-light-warning"
                            onclick="SerialModal.updateStatus(${serial.id}, 'reserved')"
                            title="{{ __('passwords.mark_reserved') }}">
                        <i class="bi bi-bookmark fs-5"></i>
                    </button>`);
            }

            if (serial.status === 'sold') {
                actions.push(`
                    <button class="btn btn-sm btn-icon btn-light-info"
                            onclick="SerialModal.updateStatus(${serial.id}, 'returned')"
                            title="{{ __('passwords.mark_returned') }}">
                        <i class="bi bi-arrow-counterclockwise fs-5"></i>
                    </button>`);
            }

            if (serial.status !== 'sold') {
                actions.push(`
                    <button class="btn btn-sm btn-icon btn-light-success"
                            onclick="SerialModal.openPricing(${serial.id})"
                            title="{{ __('passwords.edit_pricing') }}">
                        <i class="bi bi-currency-exchange fs-5"></i>
                    </button>`);
                actions.push(`
                    <button class="btn btn-sm btn-icon btn-light-danger"
                            onclick="SerialModal.delete(${serial.id})"
                            title="{{ __('passwords.delete') }}">
                        <i class="bi bi-trash fs-5"></i>
                    </button>`);
            }

            return `
                <tr>
                    <td>${index + 1}</td>
                    <td>
                        <span class="fw-bold">${escapeHtml(serial.serial_number)}</span>
                        ${customBadge}
                        ${serial.notes ? `<br><small class="text-muted">${escapeHtml(serial.notes).substring(0, 40)}</small>` : ''}
                    </td>
                    <td>
                        <span class="badge badge-light-${statusColor}">
                            ${escapeHtml(serial.status_label || serial.status)}
                        </span>
                    </td>
                    <td>
                        ${locationName !== 'N/A'
                            ? `<span class="badge badge-light-info">${escapeHtml(locationName)}</span>`
                            : '<span class="text-muted">N/A</span>'}
                    </td>
                    <td>
                        ${departmentName !== 'N/A'
                            ? `<span class="badge badge-light-primary">${escapeHtml(departmentName)}</span>`
                            : '<span class="text-muted">N/A</span>'}
                    </td>
                    <td class="text-end">${currency}${costPrice.toFixed(2)}</td>
                    <td class="text-end">${priceCell}</td>
                    <td class="text-end text-${profitClass}">
                        ${currency}${profitUnit.toFixed(2)}
                        <br><small class="text-muted">${profitMargin.toFixed(1)}%</small>
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end align-items-center">
                            ${actions.join('')}
                        </div>
                    </td>
                </tr>`;
        }).join('');

        setText('selectedSerialsCount', '0');
    }

    // ─── Checkbox helpers ──────────────────────────────────
    function getSelectedSerials() {
        return Array.from(document.querySelectorAll('.serial-checkbox:checked'))
            .map(cb => parseInt(cb.value));
    }

    SerialModal.updateSelectedCount = function () {
        setText('selectedSerialsCount', String(getSelectedSerials().length));
    };

    SerialModal.toggleAllSerials = function (checked) {
        document.querySelectorAll('.serial-checkbox').forEach(cb => cb.checked = checked);
        SerialModal.updateSelectedCount();
    };

    // ─── Assign filters (server-fetched departments) ───────
    SerialModal.resetAssignFilters = function () {
        const locSelect  = document.getElementById('assignLocationId');
        const deptSelect = document.getElementById('assignDepartmentId');
        const hint       = document.getElementById('assignDepartmentEmptyHint');

        if (locSelect)  locSelect.value = '';
        if (deptSelect) deptSelect.value = '';
        if (hint)       hint.classList.add('d-none');
    };

    function loadDepartments(locationId) {
        const deptSelect = document.getElementById('assignDepartmentId');
        const hint       = document.getElementById('assignDepartmentEmptyHint');
        if (!deptSelect) return;

        deptSelect.innerHTML = `<option value="">{{ __('passwords.select_department') }}</option>`;

        if (!locationId) {
            if (hint) hint.classList.add('d-none');
            if (window.jQuery && jQuery(deptSelect).data('select2')) {
                jQuery(deptSelect).trigger('change.select2');
            }
            return;
        }

        // ★ Named-route URL with the locationId path segment
        const baseUrl = @json(route('get.departments.by.location', ['locationId' => '__ID__']));
        const url     = baseUrl.replace('__ID__', encodeURIComponent(locationId));

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then(r => r.json())
            .then(data => {
                // Your controller returns { success, departments: [...] }
                const list = Array.isArray(data)
                    ? data
                    : (data.departments ?? []);

                list.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.name;
                    opt.setAttribute('data-location-id', d.location_id ?? locationId);
                    deptSelect.appendChild(opt);
                });

                if (hint) hint.classList.toggle('d-none', list.length > 0);

                // Notify Select2 (if active)
                if (window.jQuery && jQuery(deptSelect).data('select2')) {
                    jQuery(deptSelect).trigger('change.select2');
                }
                deptSelect.dispatchEvent(new Event('change', { bubbles: true }));
            })
            .catch(err => {
                console.error('[serials] dept fetch failed', err);
                if (hint) hint.classList.remove('d-none');
            });
    }

    // ─── Bulk assign ───────────────────────────────────────
    SerialModal.assignSelectedSerials = function () {
        if (!currentVariantId) return;

        const ids = getSelectedSerials();
        if (ids.length === 0) {
            toastr.warning('{{ __("passwords.select_serials_to_assign") }}');
            return;
        }

        const locationId   = document.getElementById('assignLocationId')?.value ?? '';
        const departmentId = document.getElementById('assignDepartmentId')?.value ?? '';

        if (!locationId && !departmentId) {
            toastr.warning('{{ __("passwords.select_location_or_department") }}');
            return;
        }

        if (!confirm(`{{ __("passwords.confirm_assign_selected_serials") }} (${ids.length})`)) return;

        fetch('/serials/assign-selected', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                serial_ids:    ids,
                location_id:   locationId   || null,
                department_id: departmentId || null,
            }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    toastr.success(data.message);
                    SerialModal.refresh();
                    SerialModal.resetAssignFilters();
                } else {
                    toastr.error(data.message);
                }
            })
            .catch(err => {
                console.error(err);
                toastr.error('{{ __("passwords.error_assigning_serials") }}');
            });
    };

    // ─── Generate ──────────────────────────────────────────
    SerialModal.generateSerials = function () {
        if (!currentVariantId) return;

        const quantity = parseInt(document.getElementById('generateSerialQuantity')?.value || '1', 10);
        const prefix   = (document.getElementById('generateSerialPrefix')?.value || '').trim();

        if (quantity < 1 || quantity > 1000) {
            toastr.warning('{{ __("passwords.enter_valid_quantity") }}');
            return;
        }

        fetch('/serials/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ variant_id: currentVariantId, quantity, prefix }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    toastr.success(data.message);
                    SerialModal.state.currentPage = 1;
                    SerialModal.refresh();

                    const qtyEl = document.getElementById('generateSerialQuantity');
                    const preEl = document.getElementById('generateSerialPrefix');
                    if (qtyEl) qtyEl.value = '1';
                    if (preEl) preEl.value = '';
                } else {
                    toastr.error(data.message);
                }
            })
            .catch(err => {
                console.error(err);
                toastr.error('{{ __("passwords.error_generating_serials") }}');
            });
    };

    // ─── Import ────────────────────────────────────────────
    SerialModal.importSerials = function () {
        if (!currentVariantId) return;

        const input = document.getElementById('importSerialInput');
        const serialNumbers = (input?.value || '').trim();

        if (!serialNumbers) {
            toastr.warning('{{ __("passwords.enter_serial_numbers") }}');
            return;
        }

        fetch('/serials/import', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ variant_id: currentVariantId, serial_numbers: serialNumbers }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    toastr.success(data.message);
                    SerialModal.state.currentPage = 1;
                    SerialModal.refresh();
                    if (input) input.value = '';
                } else {
                    toastr.error(data.message);
                }
            })
            .catch(err => {
                console.error(err);
                toastr.error('{{ __("passwords.error_importing_serials") }}');
            });
    };

    // ─── Status update ─────────────────────────────────────
    SerialModal.updateStatus = function (serialId, status) {
        const labels = {
            sold:      '{{ __("passwords.sold") }}',
            reserved:  '{{ __("passwords.reserved") }}',
            returned:  '{{ __("passwords.returned") }}',
            available: '{{ __("passwords.available") }}',
        };

        if (!confirm(`{{ __("passwords.confirm_change_status") }} ${labels[status] || status}?`)) {
            return;
        }

        fetch(`/serials/${serialId}/status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ status }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    toastr.success(data.message);
                    SerialModal.refresh();
                } else {
                    toastr.error(data.message);
                }
            })
            .catch(err => {
                console.error(err);
                toastr.error('{{ __("passwords.error_updating_serial") }}');
            });
    };

    // ─── Delete ────────────────────────────────────────────
    SerialModal.delete = function (serialId) {
        if (!confirm('{{ __("passwords.confirm_delete_serial") }}')) return;

        fetch(`/serials/${serialId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    toastr.success(data.message);

                    // If we just emptied the last page, step back
                    const rowsOnPage = document.querySelectorAll('#serialTableBody tr.serial-row').length;
                    const s = SerialModal.state;
                    if (s.currentPage > 1 && rowsOnPage <= 1) {
                        s.currentPage--;
                    }

                    SerialModal.refresh();
                } else {
                    toastr.error(data.message);
                }
            })
            .catch(err => {
                console.error(err);
                toastr.error('{{ __("passwords.error_deleting_serial") }}');
            });
    };

    // ─── Pricing edit ──────────────────────────────────────
    SerialModal.openPricing = function (serialId) {
        const serial = currentSerialMap[serialId];
        if (!serial) {
            toastr.error('{{ __("passwords.serial_not_found") }}');
            return;
        }

        const idEl   = document.getElementById('pricingSerialId');
        const nameEl = document.getElementById('pricingSerialNumber');
        if (idEl)   idEl.value = serialId;
        if (nameEl) nameEl.textContent = '- ' + serial.serial_number;

        const setVal = (id, v) => {
            const el = document.getElementById(id);
            if (el) el.value = (v === null || v === undefined) ? '' : v;
        };

        setVal('pricingSupplierCost',    serial.supplier_cost_price);
        setVal('pricingOtherCosts',      serial.total_shipping_cost);
        setVal('pricingSellingPrice',    serial.selling_price);
        setVal('pricingDiscountPercent', serial.discount_percentage);

        recalcPreview();

        const modalEl = document.getElementById('serialPricingModal');
        if (modalEl && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    };

    function recalcPreview() {
        const currency = @json(currency_symbol());

        const supplier = parseFloat(document.getElementById('pricingSupplierCost')?.value || '0') || 0;
        const other    = parseFloat(document.getElementById('pricingOtherCosts')?.value   || '0') || 0;
        const selling  = parseFloat(document.getElementById('pricingSellingPrice')?.value || '0') || 0;
        const discount = parseFloat(document.getElementById('pricingDiscountPercent')?.value || '0') || 0;

        const grandCost = supplier + other;
        const effective = discount > 0 ? selling - (selling * discount / 100) : selling;
        const profit    = effective - grandCost;
        const margin    = effective > 0 ? (profit / effective) * 100 : 0;

        setText('pricingPreviewCost',    currency + grandCost.toFixed(2));
        setText('pricingPreviewSelling', currency + effective.toFixed(2));

        const profitEl = document.getElementById('pricingPreviewProfit');
        if (profitEl) {
            profitEl.textContent = currency + profit.toFixed(2) + ' (' + margin.toFixed(1) + '%)';
            profitEl.className   = 'col-6 text-end fw-bold text-' + (profit >= 0 ? 'success' : 'danger');
        }
    }

    // ─── Save pricing ──────────────────────────────────────
    SerialModal.savePricing = function (event) {
        if (event) event.preventDefault();

        const serialId = document.getElementById('pricingSerialId')?.value;
        if (!serialId) return;

        const btn = document.getElementById('pricingSaveBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> {{ __("passwords.saving") }}';
        }

        const payload = {
            supplier_cost_price: numberOrNull('pricingSupplierCost'),
            total_shipping_cost: numberOrNull('pricingOtherCosts'),
            ura_taxes_applied:   0,
            additional_expenses: 0,
            selling_price:       numberOrNull('pricingSellingPrice'),
            discount_percentage: numberOrNull('pricingDiscountPercent') ?? 0,
        };

        fetch(`/serials/${serialId}/pricing`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    toastr.success(data.message);
                    const modalEl = document.getElementById('serialPricingModal');
                    if (modalEl && window.bootstrap) {
                        bootstrap.Modal.getInstance(modalEl)?.hide();
                    }
                    SerialModal.refresh();
                } else {
                    toastr.error(data.message);
                }
            })
            .catch(err => {
                console.error(err);
                toastr.error('{{ __("passwords.error_saving_pricing") }}');
            })
            .finally(() => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check2 me-1"></i> {{ __("passwords.save") }}';
                }
            });
    };

    // ─── Search / status / per-page bindings ───────────────
    ready(function () {
        const searchInput  = document.getElementById('serialSearchInput');
        const searchClear  = document.getElementById('serialSearchClear');
        const statusFilter = document.getElementById('serialStatusFilter');
        const perPageSel   = document.getElementById('serialPerPage');

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(() => {
                    SerialModal.state.search = this.value.trim();
                    SerialModal.state.currentPage = 1;
                    SerialModal.refresh();
                }, 350);
            });

            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchDebounceTimer);
                    SerialModal.state.search = this.value.trim();
                    SerialModal.state.currentPage = 1;
                    SerialModal.refresh();
                }
            });
        }

        if (searchClear) {
            searchClear.addEventListener('click', function () {
                if (searchInput) searchInput.value = '';
                SerialModal.state.search = '';
                SerialModal.state.currentPage = 1;
                SerialModal.refresh();
            });
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', function () {
                SerialModal.state.status = this.value;
                SerialModal.state.currentPage = 1;
                SerialModal.refresh();
            });
        }

        if (perPageSel) {
            perPageSel.addEventListener('change', function () {
                SerialModal.state.perPage = parseInt(this.value, 10) || 15;
                SerialModal.state.currentPage = 1;
                SerialModal.refresh();
            });
        }

        // Location → department cascade (delegated)
        document.addEventListener('change', function (e) {
            if (e.target && e.target.id === 'assignLocationId') {
                loadDepartments(e.target.value);
            }
        });

        // Live recalc on pricing inputs
        document.addEventListener('input', function (e) {
            if (['pricingSupplierCost', 'pricingOtherCosts',
                 'pricingSellingPrice', 'pricingDiscountPercent'].includes(e.target.id)) {
                recalcPreview();
            }
        });
    });

    // ─── Public API for opening the modal from the outside ─
    // (backward compatible with `onclick="openSerialManagementModal(...)"`)
    window.openSerialManagementModal = function (variantId, variantName) {
        SerialModal.open(variantId, variantName);
    };
})();
</script>