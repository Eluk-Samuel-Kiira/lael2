@extends('layouts.app')

@section('title', __('passwords.serial_numbers'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            {{-- Toolbar --}}
            <div class="app-toolbar py-3 py-lg-6">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 w-100">
                    <div class="page-title d-flex flex-column">
                        <h1 class="page-heading text-gray-900 fw-bold fs-2hx my-0">
                            {{ __('passwords.serial_numbers') }}
                        </h1>
                        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                            <li class="breadcrumb-item text-muted">
                                <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                    {{ __('pagination.dashboard') }}
                                </a>
                            </li>
                            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                            <li class="breadcrumb-item text-muted">{{ __('passwords.serial_numbers') }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Stat cards --}}
            <div class="row g-4 mb-6">
                <div class="col-6 col-md-3 col-xl">
                    <div class="card bg-light-primary">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Products</span>
                            <span class="fs-2x fw-bold text-primary" id="statProducts">—</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl">
                    <div class="card bg-light-info">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Total Serials</span>
                            <span class="fs-2x fw-bold text-info" id="statTotal">—</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl">
                    <div class="card bg-light-success">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Available</span>
                            <span class="fs-2x fw-bold text-success" id="statAvailable">—</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl">
                    <div class="card bg-light-warning">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Reserved</span>
                            <span class="fs-2x fw-bold text-warning" id="statReserved">—</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl">
                    <div class="card bg-light-danger">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Sold</span>
                            <span class="fs-2x fw-bold text-danger" id="statSold">—</span>
                            <span class="text-muted fs-8 d-block" id="statSoldValue"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card mb-6">
                <div class="card-body py-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold fs-7">{{ __('auth._search') }}</label>
                            <div class="position-relative">
                                <input type="text" id="serialsSearchInput"
                                       class="form-control form-control-solid pe-10"
                                       placeholder="{{ __('pagination.search_serials_placeholder') }}">
                                <i class="bi bi-search fs-4 position-absolute end-0 me-3 top-50 translate-middle-y text-muted"></i>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold fs-7">{{ __('pagination.product') }}</label>
                            <select id="serialsProductFilter" class="form-select form-select-solid">
                                <option value="">{{ __('pagination.all_products') }}</option>
                                @foreach($filterProducts as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }} — {{ $product->sku }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">{{ __('pagination.status') }}</label>
                            <select id="serialsStatusFilter" class="form-select form-select-solid">
                                @foreach($statuses as $s)
                                    <option value="{{ $s['value'] }}">{{ $s['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if(! $isSingleShop)
                            <div class="col-md-2">
                                <label class="form-label fw-semibold fs-7">{{ __('pagination._location') }}</label>
                                <select id="serialsLocationSelect" class="form-select form-select-solid">
                                    <option value="">{{ __('pagination.all_locations') }}</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold fs-7">{{ __('auth._department') }}</label>
                                <select id="serialsDepartmentSelect" class="form-select form-select-solid" disabled>
                                    <option value="">{{ __('pagination.all_departments') }}</option>
                                </select>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tree --}}
            <div id="serialsLoading" class="text-center py-10 d-none">
                <div class="spinner-border text-primary"></div>
            </div>

            <div id="serialsTreeContainer"></div>

            <div id="serialsEmpty" class="card d-none">
                <div class="card-body text-center py-15">
                    <i class="bi bi-file-earmark fs-5tx text-muted mb-4 d-block"></i>
                    <div class="fs-5 fw-semibold text-gray-800">
                        {{ __('pagination.no_serials_found') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('inventory.product-variant.serials-modal')
@include('store.serials.drawer')
@endsection

@push('scripts')
@include('store.serials.drawer-scripts')
<script>
window.SerialList = (function () {
    'use strict';

    const URL_DATA = @json(route('serials.data'));
    const DEPT_URL = @json(route('get.departments.by.location', ['locationId' => '__LOC__']));
    const CURRENCY = @json(currency_symbol());

    const state = {
        search:       '',
        productId:    '',
        status:       '',
        locationId:   '',
        departmentId: '',
    };

    let debounce, inFlight = false;

    document.addEventListener('DOMContentLoaded', () => {
        bind();
        load();
    });

    function bind() {
        document.getElementById('serialsSearchInput')?.addEventListener('input', function () {
            clearTimeout(debounce);
            debounce = setTimeout(() => {
                state.search = this.value.trim();
                load();
            }, 400);
        });

        document.getElementById('serialsProductFilter')?.addEventListener('change', function () {
            state.productId = this.value;
            load();
        });

        document.getElementById('serialsStatusFilter')?.addEventListener('change', function () {
            state.status = this.value;
            load();
        });

        document.getElementById('serialsLocationSelect')?.addEventListener('change', async function () {
            state.locationId   = this.value;
            state.departmentId = '';
            await loadDepartments(this.value);
            load();
        });

        document.getElementById('serialsDepartmentSelect')?.addEventListener('change', function () {
            state.departmentId = this.value;
            load();
        });

        // Row/card click delegates to drawer
        document.getElementById('serialsTreeContainer')?.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-serial-variant-open]');
            if (!trigger) return;
            if (e.target.closest('button, a, input, select')) return;
            const id = parseInt(trigger.dataset.serialVariantOpen, 10);
            if (id && window.SerialVariantDrawer) window.SerialVariantDrawer.open(id);
        });
    }

    async function loadDepartments(locationId) {
        const dept = document.getElementById('serialsDepartmentSelect');
        if (!dept) return;

        dept.innerHTML = `<option value="">{{ __('pagination.all_departments') }}</option>`;
        dept.disabled  = true;
        if (!locationId) return;

        try {
            const res = await fetch(DEPT_URL.replace('__LOC__', encodeURIComponent(locationId)), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            const list = Array.isArray(data) ? data : (data.departments ?? []);
            list.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = d.name;
                dept.appendChild(opt);
            });
            dept.disabled = list.length === 0;
        } catch (err) {
            console.error('[serials] dept fetch failed:', err);
            dept.disabled = false;
        }
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;

        document.getElementById('serialsLoading')?.classList.remove('d-none');
        document.getElementById('serialsTreeContainer').innerHTML = '';
        document.getElementById('serialsEmpty')?.classList.add('d-none');

        const params = new URLSearchParams();
        if (state.search)       params.set('search',        state.search);
        if (state.productId)    params.set('product_id',    state.productId);
        if (state.status)       params.set('status',        state.status);
        if (state.locationId)   params.set('location_id',   state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);

        try {
            const res = await fetch(`${URL_DATA}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            renderStats(json.stats);
            renderTree(json.tree);

        } catch (err) {
            console.error('[serials] load failed:', err);
            window.toastr?.error?.('Failed to load serials.');
            document.getElementById('serialsEmpty')?.classList.remove('d-none');
        } finally {
            document.getElementById('serialsLoading')?.classList.add('d-none');
            inFlight = false;
        }
    }

    function renderStats(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('statProducts',   s.products  ?? 0);
        set('statTotal',      s.total     ?? 0);
        set('statAvailable',  s.available ?? 0);
        set('statReserved',   s.reserved  ?? 0);
        set('statSold',       s.sold      ?? 0);
        set('statSoldValue',  s.sold_value > 0 ? money(s.sold_value) + ' in sales' : '');
    }

    function renderTree(tree) {
        const container = document.getElementById('serialsTreeContainer');

        if (!tree || tree.length === 0) {
            document.getElementById('serialsEmpty')?.classList.remove('d-none');
            return;
        }

        container.innerHTML = tree.map(node => renderProductCard(node)).join('');
    }

    function renderProductCard(node) {
        const p = node.product;

        return `
            <div class="card mb-4">
                <div class="card-header border-0 py-4">
                    <div class="d-flex align-items-center flex-grow-1 gap-3">
                        <div class="d-flex flex-column">
                            <span class="fw-bold fs-4 text-gray-900">${esc(p.name)}</span>
                            <span class="text-muted fs-8">
                                ${esc(p.sku)} · ${p.variants_count} variants
                            </span>
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <a href="/products/${p.id}" target="_blank"
                        class="btn btn-sm btn-light btn-active-color-primary">
                            <i class="bi bi-box-arrow-up-right me-1"></i>
                            Edit Product
                        </a>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-3">
                        ${node.variants.map(vn => renderVariantBlock(vn)).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    function renderVariantBlock(vn) {
        const v = vn.variant;
        const c = vn.counts || {};

        const badges = [];
        if (c.total > 0)     badges.push(`<span class="badge badge-light-primary fs-8">${c.total} total</span>`);
        if (c.available > 0) badges.push(`<span class="badge badge-light-success fs-8">${c.available} available</span>`);
        if (c.reserved > 0)  badges.push(`<span class="badge badge-light-warning fs-8">${c.reserved} reserved</span>`);
        if (c.sold > 0)      badges.push(`<span class="badge badge-light-danger  fs-8">${c.sold} sold</span>`);

        return `
            <div class="col-md-6 col-xl-4">
                <div class="border border-gray-300 border-dashed rounded-3 p-4 h-100"
                    style="cursor:pointer; transition: all .15s ease;"
                    data-serial-variant-open="${v.id}"
                    onmouseover="this.classList.add('bg-light')"
                    onmouseout="this.classList.remove('bg-light')">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <div class="d-flex flex-column min-w-0">
                            <span class="fw-bold text-gray-800 text-truncate">${esc(v.name)}</span>
                            <code class="text-muted fs-8 text-truncate">${esc(v.sku)}</code>
                        </div>
                        ${v.has_custom_pricing
                            ? `<span class="badge badge-light-warning fs-8">Custom</span>`
                            : `<span class="badge badge-light-secondary fs-8">Variant</span>`}
                    </div>
                    <div class="text-muted fs-8 mb-2">
                        <span>Cost:</span>
                        <span class="fw-bold text-gray-800">${money(v.effective_cost)}</span>
                        <span class="mx-2">·</span>
                        <span>Sell:</span>
                        <span class="text-primary fw-bold">${money(v.effective_sell)}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2 fs-8">
                        ${badges.length ? badges.join('') : `<span class="text-muted">No serials</span>`}
                    </div>
                </div>
            </div>
        `;
    }

    function renderSerialsTable(serials) {
        return `
            <div class="table-responsive">
                <table class="table table-row-dashed align-middle fs-7 gy-3 mb-0">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-8 text-uppercase gs-0">
                            <th>Serial Number</th>
                            <th class="text-center">Status</th>
                            <th>Location</th>
                            <th>Department</th>
                            <th class="text-end">Sold Price</th>
                            <th>Order</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${serials.map(s => `
                            <tr>
                                <td class="fw-bold">${esc(s.serial_number)}</td>
                                <td class="text-center">
                                    <span class="badge badge-light-${s.status_color} fs-8">
                                        ${esc(s.status_label)}
                                    </span>
                                </td>
                                <td>${esc(s.location_name   || '—')}</td>
                                <td>${esc(s.department_name || '—')}</td>
                                <td class="text-end">
                                    ${s.sold_unit_price !== null
                                        ? `<span class="fw-bold text-success">${money(s.sold_unit_price)}</span>`
                                        : `<span class="text-muted">—</span>`}
                                </td>
                                <td>
                                    ${s.order_number
                                        ? `<span class="badge badge-light-info">#${esc(s.order_number)}</span>`
                                        : `<span class="text-muted">—</span>`}
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }
    function money(n) { return CURRENCY + Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }


    window.SerialList = { load, reload: load };
    return window.SerialList;
})();
</script>
@endpush