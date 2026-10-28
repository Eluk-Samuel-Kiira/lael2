@extends('layouts.app')

@section('title', __('passwords.recipes'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            {{-- Toolbar --}}
            <div class="app-toolbar py-3 py-lg-6">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 w-100">
                    <div class="page-title d-flex flex-column">
                        <h1 class="page-heading text-gray-900 fw-bold fs-2hx my-0">
                            {{ __('passwords.recipes') }}
                        </h1>
                        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                            <li class="breadcrumb-item text-muted">
                                <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                    {{ __('pagination.dashboard') }}
                                </a>
                            </li>
                            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                            <li class="breadcrumb-item text-muted">{{ __('passwords.recipes') }}</li>
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
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Variants</span>
                            <span class="fs-2x fw-bold text-info" id="statVariants">—</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl">
                    <div class="card bg-light-success">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">With Recipe</span>
                            <span class="fs-2x fw-bold text-success" id="statWithRecipe">—</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl">
                    <div class="card bg-light-warning">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Ingredients</span>
                            <span class="fs-2x fw-bold text-warning" id="statIngredients">—</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl">
                    <div class="card bg-light-danger">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Avg Recipe Cost</span>
                            <span class="fs-2x fw-bold text-danger" id="statCost">—</span>
                            <span class="text-muted fs-8 d-block" id="statProfit"></span>
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
                                <input type="text" id="recipesSearchInput"
                                       class="form-control form-control-solid pe-10"
                                       placeholder="{{ __('pagination.search_recipes_placeholder') }}">
                                <i class="bi bi-search fs-4 position-absolute end-0 me-3 top-50 translate-middle-y text-muted"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold fs-7">{{ __('pagination.product') }}</label>
                            <select id="recipesProductFilter" class="form-select form-select-solid">
                                <option value="">{{ __('pagination.all_products') }}</option>
                                @foreach($filterProducts as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} — {{ $product->sku }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">{{ __('pagination.state') }}</label>
                            <select id="recipesStateFilter" class="form-select form-select-solid">
                                @foreach($states as $s)
                                    <option value="{{ $s['value'] }}">{{ $s['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if(! $isSingleShop)
                            <div class="col-md-2">
                                <label class="form-label fw-semibold fs-7">{{ __('pagination._location') }}</label>
                                <select id="recipesLocationSelect" class="form-select form-select-solid">
                                    <option value="">{{ __('pagination.all_locations') }}</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold fs-7">{{ __('auth._department') }}</label>
                                <select id="recipesDepartmentSelect" class="form-select form-select-solid" disabled>
                                    <option value="">{{ __('pagination.all_departments') }}</option>
                                </select>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tree --}}
            <div id="recipesLoading" class="text-center py-10 d-none">
                <div class="spinner-border text-primary"></div>
            </div>
            <div id="recipesTreeContainer"></div>
            <div id="recipesEmpty" class="card d-none">
                <div class="card-body text-center py-15">
                    <i class="bi bi-journal-x fs-5tx text-muted mb-4 d-block"></i>
                    <div class="fs-5 fw-semibold text-gray-800">{{ __('pagination.no_recipes_found') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('inventory.product-variant.recipe-ingredients-modal')
@include('store.recipes.drawer')
@endsection

@push('scripts')
@include('store.recipes.drawer-scripts')

<script>
window.RecipeList = (function () {
    'use strict';

    const URL_DATA = @json(route('recipes.data'));
    const DEPT_URL = @json(route('get.departments.by.location', ['locationId' => '__LOC__']));
    const CURRENCY = @json(currency_symbol());

    const state = { search: '', productId: '', stateFilter: '', locationId: '', departmentId: '' };
    let debounce, inFlight = false;

    document.addEventListener('DOMContentLoaded', () => {
        bind();
        load();
    });

    function bind() {
        document.getElementById('recipesSearchInput')?.addEventListener('input', function () {
            clearTimeout(debounce);
            debounce = setTimeout(() => { state.search = this.value.trim(); load(); }, 400);
        });
        document.getElementById('recipesProductFilter')?.addEventListener('change', function () {
            state.productId = this.value; load();
        });
        document.getElementById('recipesStateFilter')?.addEventListener('change', function () {
            state.stateFilter = this.value; load();
        });
        document.getElementById('recipesLocationSelect')?.addEventListener('change', async function () {
            state.locationId = this.value; state.departmentId = '';
            await loadDepartments(this.value); load();
        });
        document.getElementById('recipesDepartmentSelect')?.addEventListener('change', function () {
            state.departmentId = this.value; load();
        });

        // Variant click → drawer; Edit click → modal
        document.addEventListener('click', (e) => {
            const editBtn = e.target.closest('[data-recipe-edit]');
            if (editBtn) {
                const id = parseInt(editBtn.dataset.recipeEdit, 10);
                const name = editBtn.dataset.recipeEditName;
                if (id && typeof window.openRecipeIngredientsModal === 'function') {
                    window.openRecipeIngredientsModal(id, name);
                }
                return;
            }

            const openVariant = e.target.closest('[data-recipe-variant-open]');
            if (openVariant) {
                const id = parseInt(openVariant.dataset.recipeVariantOpen, 10);
                if (id && window.RecipeVariantDrawer) window.RecipeVariantDrawer.open(id);
            }
        });
    }

    async function loadDepartments(locationId) {
        const dept = document.getElementById('recipesDepartmentSelect');
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
                opt.value = d.id; opt.textContent = d.name;
                dept.appendChild(opt);
            });
            dept.disabled = list.length === 0;
        } catch (err) {
            console.error('[recipes] dept fetch failed:', err);
            dept.disabled = false;
        }
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;

        document.getElementById('recipesLoading')?.classList.remove('d-none');
        document.getElementById('recipesTreeContainer').innerHTML = '';
        document.getElementById('recipesEmpty')?.classList.add('d-none');

        const params = new URLSearchParams();
        if (state.search)       params.set('search',        state.search);
        if (state.productId)    params.set('product_id',    state.productId);
        if (state.stateFilter)  params.set('state',         state.stateFilter);
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
            console.error('[recipes] load failed:', err);
            window.toastr?.error?.('Failed to load recipes.');
            document.getElementById('recipesEmpty')?.classList.remove('d-none');
        } finally {
            document.getElementById('recipesLoading')?.classList.add('d-none');
            inFlight = false;
        }
    }

    function renderStats(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        const withRecipe = s.with_recipe ?? 0;
        const avgCost = withRecipe > 0 ? (s.total_cost / withRecipe) : 0;

        set('statProducts',    s.products          ?? 0);
        set('statVariants',    s.variants          ?? 0);
        set('statWithRecipe',  withRecipe);
        set('statIngredients', s.total_ingredients ?? 0);
        set('statCost',        money(avgCost));

        const profit = s.profit ?? 0;
        const profitEl = document.getElementById('statProfit');
        if (profitEl) {
            profitEl.textContent = 'Total profit: ' + money(profit);
            profitEl.className = 'text-muted fs-8 d-block ' + (profit >= 0 ? 'text-success' : 'text-danger');
        }
    }

    function renderTree(tree) {
        const container = document.getElementById('recipesTreeContainer');
        if (!tree || tree.length === 0) {
            document.getElementById('recipesEmpty')?.classList.remove('d-none');
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
        const r = vn.recipe;

        const badge = vn.inherits_from_product
            ? `<span class="badge badge-light-info fs-8">Inherits</span>`
            : (r
                ? `<span class="badge badge-light-success fs-8">Recipe</span>`
                : `<span class="badge badge-light-warning fs-8">No recipe</span>`);

        const customBadge = r && r.computed_custom_count > 0
            ? `<span class="badge badge-light-warning fs-8">${r.computed_custom_count} custom</span>`
            : '';

        const ingCount = r && r.ingredients ? r.ingredients.length : 0;
        const totalCost = r ? Number(r.computed_total_cost) : 0;

        return `
            <div class="col-md-6 col-xl-4">
                <div class="border border-gray-300 border-dashed rounded-3 p-4 h-100"
                    style="cursor:pointer; transition: all .15s ease;"
                    data-recipe-variant-open="${v.id}"
                    data-recipe-variant-name="${esc(v.name)}"
                    onmouseover="this.classList.add('bg-light')"
                    onmouseout="this.classList.remove('bg-light')">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <div class="d-flex flex-column min-w-0">
                            <span class="fw-bold text-gray-800 text-truncate">${esc(v.name)}</span>
                            <code class="text-muted fs-8 text-truncate">${esc(v.sku)}</code>
                        </div>
                        ${badge}
                    </div>
                    <div class="text-muted fs-8 mb-2">
                        <span>Selling:</span>
                        <span class="text-primary fw-bold">${money(v.selling_price)}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2 fs-8">
                        <span class="badge badge-light-primary">${ingCount} ingredients</span>
                        ${totalCost > 0 ? `<span class="badge badge-light-warning">Cost: ${money(totalCost)}</span>` : ''}
                        ${customBadge}
                    </div>
                </div>
            </div>
        `;
    }

    function renderIngredientsTable(recipe) {
        return `
            <div class="table-responsive">
                <table class="table table-sm table-row-dashed fs-7 gy-3 mb-0">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-8 text-uppercase gs-0">
                            <th class="min-w-200px">Ingredient</th>
                            <th class="text-center min-w-90px">Required</th>
                            <th class="min-w-80px">Unit</th>
                            <th class="text-end min-w-110px">Cost</th>
                            <th class="text-end min-w-110px">Sell</th>
                            <th class="text-end min-w-110px">Line Cost</th>
                            <th class="text-center min-w-90px">Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${recipe.ingredients.map(ing => `
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-gray-800">${esc(ing.name || '—')}</span>
                                        <span class="text-muted fs-8">
                                            ${esc(ing.sku || '—')}${ing.product_name ? ' · ' + esc(ing.product_name) : ''}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center fw-bold">${Number(ing.quantity_required).toFixed(4)}</td>
                                <td>${esc(ing.unit_name || '—')}</td>
                                <td class="text-end">${money(ing.unit_cost)}</td>
                                <td class="text-end text-primary">${money(ing.unit_sell)}</td>
                                <td class="text-end fw-bold">${money(ing.line_cost)}</td>
                                <td class="text-center">
                                    <span class="badge badge-light-${ing.source === 'ingredient' ? 'success' : ing.source === 'item' ? 'warning' : 'secondary'} fs-8">
                                        ${ing.source}
                                    </span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                    <tfoot class="border-top">
                        <tr>
                            <td colspan="5" class="text-end fw-bold">Total ingredient cost:</td>
                            <td class="text-end fw-bolder text-gray-900">${money(recipe.computed_total_cost)}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }
    function money(n) { return CURRENCY + Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    window.RecipeList = { load, reload: load };
    return window.RecipeList;
})();
</script>
@endpush