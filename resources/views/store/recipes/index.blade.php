@extends('layouts.app')

@section('title', __('passwords.recipes'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            {{-- ─── Toolbar ──────────────────────────────────────────── --}}
            <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                <div id="kt_app_toolbar_container"
                     class="app-container container-fluid d-flex flex-stack">
                    <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                            {{ __('passwords.recipes') }}
                        </h1>
                        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                            <li class="breadcrumb-item text-muted">
                                <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                    {{ __('pagination.dashboard') }}
                                </a>
                            </li>
                            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                            <li class="breadcrumb-item text-muted">{{ __('pagination.store_inventory') }}</li>
                            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                            <li class="breadcrumb-item text-muted">{{ __('passwords.recipes') }}</li>
                        </ul>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="badge badge-light-primary fs-7 py-2 px-3">
                            {{ $displayTree->count() }} {{ __('pagination.products') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- ─── Filters ──────────────────────────────────────────── --}}
            <div class="card mb-6">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold fs-3 mb-1">{{ __('auth._filter') }}</span>
                    </h3>
                </div>
                <div class="card-body pt-0">
                    <form method="GET" action="{{ route('recipes.index') }}">
                        <div class="row g-3 align-items-end">

                            {{-- Search --}}
                            <div class="col-md-3">
                                <label class="form-label fw-semibold fs-7">{{ __('auth._search') }}</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">
                                        <i class="ki-duotone ki-magnifier fs-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </span>
                                    <input type="text" name="search" value="{{ $search }}"
                                        class="form-control form-control-solid"
                                        placeholder="{{ __('pagination.search_recipes_placeholder') }}">
                                </div>
                            </div>

                            {{-- Product --}}
                            <div class="col-md-2">
                                <label class="form-label fw-semibold fs-7">{{ __('pagination.product') }}</label>
                                <select name="product_id"
                                        class="form-select form-select-sm form-select-solid"
                                        data-control="select2"
                                        data-placeholder="{{ __('pagination.all_products') }}">
                                    <option value="">{{ __('pagination.all_products') }}</option>
                                    @foreach($filterProducts as $product)
                                        <option value="{{ $product->id }}" @selected($productId == $product->id)>
                                            {{ $product->name }} ({{ $product->sku }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- State --}}
                            <div class="col-md-2">
                                <label class="form-label fw-semibold fs-7">{{ __('pagination.state') }}</label>
                                <select name="state" class="form-select form-select-sm form-select-solid">
                                    @foreach($states as $s)
                                        <option value="{{ $s['value'] }}" @selected($state === $s['value'])>
                                            {{ $s['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Location + Department (multi-shop only) --}}
                            @if(! $isSingleShop)
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold fs-7">{{ __('pagination._location') }}</label>
                                    <select name="location_id"
                                            id="recipesLocationSelect"
                                            class="form-select form-select-sm form-select-solid">
                                        <option value="">{{ __('pagination.all_locations') }}</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" @selected($locationId == $location->id)>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label fw-semibold fs-7">{{ __('auth._department') }}</label>
                                    <select name="department_id"
                                            id="recipesDepartmentSelect"
                                            class="form-select form-select-sm form-select-solid"
                                            @if(! $locationId) disabled @endif>
                                        <option value="">{{ __('pagination.all_departments') }}</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" @selected($departmentId == $department->id)>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- Actions --}}
                            <div class="{{ $isSingleShop ? 'col-md-2' : 'col-md-1' }} d-flex align-items-end gap-2">
                                <button type="submit"
                                        class="btn btn-sm btn-primary flex-grow-1 d-flex align-items-center justify-content-center">
                                    <i class="ki-duotone ki-filter fs-3 me-1">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    {{ __('auth._filter') }}
                                </button>
                                <a href="{{ route('recipes.index') }}"
                                class="btn btn-sm btn-light d-flex align-items-center justify-content-center"
                                style="width: 34px; min-width: 34px;"
                                title="{{ __('pagination.clear') }}">
                                    <i class="ki-duotone ki-cross fs-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ─── Product → Variant → Ingredients ──────────────────── --}}
            @forelse($displayTree as $node)
                @php $product = $node->product; @endphp

                <div class="card mb-6">

                    {{-- Product header (collapsible) --}}
                    <div class="card-header border-0 pt-5">
                        <div class="d-flex align-items-center flex-grow-1"
                            data-bs-toggle="collapse"
                            data-bs-target="#productBlock{{ $product->id }}"
                            aria-expanded="false"
                            style="cursor: pointer;">
                            <i class="ki-duotone ki-down fs-4 me-3 product-toggle-icon">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <span class="fw-bold fs-3 text-gray-900">
                                    {{ $product->name }}
                                </span>
                                <span class="text-muted fs-8">
                                    {{ $product->sku }}
                                    · {{ $node->variants->count() }} {{ __('pagination.variants') }}
                                </span>
                            </div>
                        </div>

                        <div class="card-toolbar">
                            <a href="{{ route('products.show', $product->id) }}"
                            target="_blank"
                            class="btn btn-sm btn-light btn-active-color-primary">
                                <i class="ki-duotone ki-pencil fs-4"></i>
                                {{ __('auth._edit') }}
                            </a>
                        </div>
                    </div>

                    {{-- Expandable content: one panel per variant --}}
                    <div class="collapse" id="productBlock{{ $product->id }}">
                        <div class="card-body pt-0">

                            @foreach($node->variants as $variantNode)
                                @php
                                    $variant = $variantNode->variant;
                                    $recipe  = $variantNode->recipe;
                                    $inherits = $variantNode->inherits_from_product;
                                @endphp

                                <div class="border border-gray-200 rounded p-5 mb-5">

                                    {{-- Variant header --}}
                                    <div class="d-flex align-items-center justify-content-between mb-4">
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold fs-4 text-gray-800">
                                                {{ $variant->name }}
                                            </span>
                                            <span class="text-muted fs-8">
                                                {{ $variant->sku }}
                                                · {{ __('passwords.selling_price') }}:
                                                <span class="text-primary fw-bold">
                                                    {{ currency_symbol() }}{{ number_format($variant->selling_price, 2) }}
                                                </span>
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($inherits)
                                                <span class="badge badge-light-info fs-8">
                                                    {{ __('pagination.inherits_product_recipe') }}
                                                </span>
                                            @elseif($recipe)
                                                <span class="badge badge-light-success fs-8">
                                                    {{ __('pagination.variant_recipe') }}
                                                </span>
                                            @else
                                                <span class="badge badge-light-warning fs-8">
                                                    {{ __('pagination.no_recipe') }}
                                                </span>
                                            @endif

                                            @if($recipe && $recipe->computed_custom_count > 0)
                                                <span class="badge badge-light-warning fs-8">
                                                    {{ $recipe->computed_custom_count }} {{ __('pagination.custom') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Ingredients table --}}
                                    @if(! $recipe)
                                        <div class="alert alert-light-warning border border-warning border-dashed d-flex align-items-center p-4 mb-0">
                                            <i class="ki-duotone ki-information-5 fs-2hx text-warning me-3"></i>
                                            <div class="fs-7">
                                                {{ __('pagination.no_ingredients_defined') }}
                                            </div>
                                        </div>
                                    @elseif($recipe->ingredients->isEmpty())
                                        <div class="alert alert-light-warning border border-warning border-dashed d-flex align-items-center p-4 mb-0">
                                            <i class="ki-duotone ki-information-5 fs-2hx text-warning me-3"></i>
                                            <div class="fs-7">
                                                {{ __('pagination.no_ingredients_defined') }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-sm table-row-dashed fs-7 gy-3 mb-0">
                                                <thead>
                                                    <tr class="text-start text-muted fw-bold fs-8 text-uppercase gs-0">
                                                        <th class="min-w-250px">{{ __('pagination.ingredient') }}</th>
                                                        <th class="min-w-90px text-center">{{ __('pagination.required') }}</th>
                                                        <th class="min-w-80px">{{ __('pagination.unit') }}</th>
                                                        <th class="min-w-110px text-end">{{ __('passwords.cost_price') }}</th>
                                                        <th class="min-w-110px text-end">{{ __('passwords.selling_price') }}</th>
                                                        <th class="min-w-110px text-end">{{ __('pagination.line_cost') }}</th>
                                                        <th class="min-w-90px text-center">{{ __('pagination.source') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="text-gray-600 fw-semibold">
                                                    @foreach($recipe->ingredients as $ing)
                                                        @php
                                                            $ingVar = $ing->ingredientVariant;
                                                            $source = $ing->resolved_source ?? 'variant';
                                                        @endphp
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-gray-800 fw-bold">
                                                                        {{ $ingVar->name ?? __('pagination._none') }}
                                                                    </span>
                                                                    <span class="text-muted fs-8">
                                                                        {{ $ingVar->sku ?? '—' }}
                                                                        @if($ingVar?->product)
                                                                            · {{ $ingVar->product->name }}
                                                                        @endif
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td class="text-center fw-bold">
                                                                {{ number_format((float) $ing->quantity_required, 4) }}
                                                            </td>
                                                            <td class="text-gray-700">
                                                                {{ $ing->unit->name ?? '—' }}
                                                            </td>
                                                            <td class="text-end">
                                                                {{ currency_symbol() }}{{ number_format($ing->resolved_unit_cost ?? 0, 2) }}
                                                            </td>
                                                            <td class="text-end text-primary">
                                                                {{ currency_symbol() }}{{ number_format($ing->resolved_unit_sell ?? 0, 2) }}
                                                            </td>
                                                            <td class="text-end fw-bold text-gray-800">
                                                                {{ currency_symbol() }}{{ number_format($ing->resolved_line_cost ?? 0, 2) }}
                                                            </td>
                                                            <td class="text-center">
                                                                @switch($source)
                                                                    @case('ingredient')
                                                                        <span class="badge badge-light-success fs-8">
                                                                            {{ __('pagination.ingredient') }}
                                                                        </span>
                                                                        @break
                                                                    @case('item')
                                                                        <span class="badge badge-light-warning fs-8">
                                                                            {{ __('pagination.custom') }}
                                                                        </span>
                                                                        @break
                                                                    @default
                                                                        <span class="badge badge-light-secondary fs-8">
                                                                            {{ __('pagination.variant') }}
                                                                        </span>
                                                                @endswitch
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="border-top">
                                                    <tr>
                                                        <td colspan="5" class="text-end fw-bold text-gray-700 pt-4">
                                                            {{ __('pagination.total_ingredient_cost') }}:
                                                        </td>
                                                        <td class="text-end fw-bolder text-gray-900 fs-6 pt-4">
                                                            {{ currency_symbol() }}{{ number_format($recipe->computed_total_cost, 2) }}
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                        </div>
                    </div>
                </div>
            @empty
                <div class="card">
                    <div class="card-body text-center py-15">
                        <i class="ki-duotone ki-file-deleted fs-3x text-gray-400 mb-5">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <div class="fs-5 fw-semibold text-gray-800 mb-2">
                            {{ __('pagination.no_recipes_found') }}
                        </div>
                        <div class="fs-7 text-muted">
                            {{ __('pagination.recipes_are_created_on_variant_pages') }}
                        </div>
                    </div>
                </div>
            @endforelse

        </div>
    </div>
</div>
@endsection

@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function (trigger) {
        const targetSelector = trigger.getAttribute('data-bs-target');
        const target = document.querySelector(targetSelector);
        const icon   = trigger.querySelector('.product-toggle-icon');
        if (!target) return;

        target.addEventListener('show.bs.collapse', function () {
            if (icon) icon.classList.add('rotate-180');
        });
        target.addEventListener('hide.bs.collapse', function () {
            if (icon) icon.classList.remove('rotate-180');
        });
    });
});
</script>

<style>
    .product-toggle-icon { transition: transform 0.15s ease; }
    .product-toggle-icon.rotate-180 { transform: rotate(180deg); }
</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';

    // ── Route template, safely serialized for the JS string ──
    const DEPT_URL_TEMPLATE = @json(route('get.departments.by.location', ['locationId' => '__LOC__']));

    function departmentsUrl(locationId) {
        return DEPT_URL_TEMPLATE.replace('__LOC__', encodeURIComponent(locationId));
    }

    // ── Rebuild the department select for a location ──────────
    async function loadDepartments(locationId) {
        const deptSelect = document.getElementById('recipesDepartmentSelect');
        const hint       = null; // optional — add a hint if you want one

        if (!deptSelect) return;

        // Reset
        deptSelect.innerHTML = `<option value="">{{ __('pagination.all_departments') }}</option>`;
        deptSelect.disabled  = true;

        if (!locationId) return;

        try {
            const res = await fetch(departmentsUrl(locationId), {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();

            // Accept either a plain array or { departments: [...] }
            const list = Array.isArray(data) ? data : (data.departments ?? []);

            list.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = d.name;
                opt.setAttribute('data-location-id', d.location_id ?? locationId);
                deptSelect.appendChild(opt);
            });

            deptSelect.disabled = list.length === 0;
        } catch (err) {
            console.error('[recipes] department fetch failed:', err);
            deptSelect.disabled = false; // leave it enabled but empty so user sees nothing
        }
    }

    // ── Wire the change event (delegated, safe across re-renders) ──
    document.addEventListener('change', function (e) {
        if (e.target && e.target.id === 'recipesLocationSelect') {
            loadDepartments(e.target.value);
        }
    });

    // ── Bootstrap: if the page loaded with a location preselected
    //    but no departments rendered (single-item deep-link case),
    //    fetch them now so the UI is consistent.
    document.addEventListener('DOMContentLoaded', function () {
        const loc = document.getElementById('recipesLocationSelect');
        const dep = document.getElementById('recipesDepartmentSelect');
        if (loc && loc.value && dep && dep.options.length <= 1) {
            loadDepartments(loc.value);
        }
    });
})();
</script>
@endpush

