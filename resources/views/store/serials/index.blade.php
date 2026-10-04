@extends('layouts.app')

@section('title', __('passwords.serial_numbers'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            {{-- ─── Toolbar ─────────────────────────────────────── --}}
            <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                <div id="kt_app_toolbar_container"
                     class="app-container container-fluid d-flex flex-stack">
                    <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                            {{ __('passwords.serial_numbers') }}
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
                            <li class="breadcrumb-item text-muted">{{ __('passwords.serial_numbers') }}</li>
                        </ul>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="badge badge-light-primary fs-7 py-2 px-3">
                            {{ $displayTree->count() }} {{ __('pagination.products') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- ─── Filters ─────────────────────────────────────── --}}
            <div class="card mb-6">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title">
                        <span class="card-label fw-bold fs-3">{{ __('auth._filter') }}</span>
                    </h3>
                </div>
                <div class="card-body pt-0">
                    <form method="GET" action="{{ route('serials.index') }}" id="serialsFilterForm">
                        <div class="row g-3 align-items-end">

                            {{-- Search --}}
                            <div class="col-md-3">
                                <label class="form-label fw-semibold fs-7">{{ __('auth._search') }}</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">
                                        <i class="ki-duotone ki-magnifier fs-4">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <input type="text" name="search" value="{{ $search }}"
                                           class="form-control form-control-solid"
                                           placeholder="{{ __('pagination.search_serials_placeholder') }}">
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

                            {{-- Status --}}
                            <div class="col-md-2">
                                <label class="form-label fw-semibold fs-7">{{ __('pagination.status') }}</label>
                                <select name="status" class="form-select form-select-sm form-select-solid">
                                    @foreach($statuses as $s)
                                        <option value="{{ $s['value'] }}" @selected($status === $s['value'])>
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
                                            id="serialsLocationSelect"
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
                                            id="serialsDepartmentSelect"
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
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    {{ __('auth._filter') }}
                                </button>
                                <a href="{{ route('serials.index') }}"
                                   class="btn btn-sm btn-light d-flex align-items-center justify-content-center"
                                   style="width: 34px; min-width: 34px;"
                                   title="{{ __('pagination.clear') }}">
                                    <i class="ki-duotone ki-cross fs-3">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ─── Product → Variant → Serials ─────────────────── --}}
            @forelse($displayTree as $node)
                @php $product = $node->product; @endphp

                <div class="card mb-6">
                    <div class="card-header border-0 pt-5">
                        <div class="d-flex align-items-center flex-grow-1">

                            {{-- Toggle button — clickable area that controls the collapse --}}
                            <button type="button"
                                    class="btn btn-icon btn-sm btn-active-light-primary me-3"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#serialProductBlock{{ $product->id }}"
                                    aria-expanded="false"
                                    aria-controls="serialProductBlock{{ $product->id }}">
                                <i class="ki-duotone ki-down fs-4 product-toggle-icon">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </button>

                            <div class="d-flex flex-column">
                                <span class="fw-bold fs-3 text-gray-900">{{ $product->name }}</span>
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

                    <div class="collapse" id="serialProductBlock{{ $product->id }}">
                        <div class="card-body pt-0">

                            @foreach($node->variants as $variantNode)
                                @php
                                    $variant  = $variantNode->variant;
                                    $serials  = $variantNode->serials;
                                @endphp

                                <div class="border border-gray-200 rounded p-5 mb-5">

                                    {{-- Variant header with summary --}}
                                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold fs-4 text-gray-800">{{ $variant->name }}</span>
                                            <span class="text-muted fs-8">
                                                {{ $variant->sku }}
                                                · {{ __('passwords.cost_price') }}:
                                                <span class="fw-bold">
                                                    {{ currency_symbol() }}{{ number_format($variantNode->effective_cost, 2) }}
                                                </span>
                                                · {{ __('passwords.selling_price') }}:
                                                <span class="text-primary fw-bold">
                                                    {{ currency_symbol() }}{{ number_format($variantNode->effective_sell, 2) }}
                                                </span>
                                            </span>
                                        </div>

                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            @if($variantNode->has_custom_pricing)
                                                <span class="badge badge-light-warning fs-8">
                                                    <i class="bi bi-pencil-square me-1"></i>
                                                    {{ __('passwords.custom_pricing') }}
                                                </span>
                                            @else
                                                <span class="badge badge-light-secondary fs-8">
                                                    {{ __('passwords.variant_pricing') }}
                                                </span>
                                            @endif

                                            <span class="badge badge-light-primary fs-8">
                                                {{ $variantNode->total }} {{ __('pagination.total') }}
                                            </span>
                                            @if($variantNode->available > 0)
                                                <span class="badge badge-light-success fs-8">
                                                    {{ $variantNode->available }} {{ __('passwords.available') }}
                                                </span>
                                            @endif
                                            @if($variantNode->reserved > 0)
                                                <span class="badge badge-light-warning fs-8">
                                                    {{ $variantNode->reserved }} {{ __('passwords.reserved') }}
                                                </span>
                                            @endif
                                            @if($variantNode->sold > 0)
                                                <span class="badge badge-light-danger fs-8">
                                                    {{ $variantNode->sold }} {{ __('passwords.sold') }}
                                                </span>
                                            @endif
                                            @if($variantNode->returned > 0)
                                                <span class="badge badge-light-info fs-8">
                                                    {{ $variantNode->returned }} {{ __('passwords.returned') }}
                                                </span>
                                            @endif
                                            @if($variantNode->lost > 0)
                                                <span class="badge badge-light-dark fs-8">
                                                    {{ $variantNode->lost }} {{ __('passwords.lost') }}
                                                </span>
                                            @endif
                                            @if($variantNode->damaged > 0)
                                                <span class="badge badge-light-dark fs-8">
                                                    {{ $variantNode->damaged }} {{ __('passwords.damaged') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Serials table --}}
                                    @if($serials->isEmpty())
                                        <div class="alert alert-light-warning border border-warning border-dashed d-flex align-items-center p-4 mb-0">
                                            <i class="ki-duotone ki-information-5 fs-2hx text-warning me-3"></i>
                                            <div class="fs-7">{{ __('pagination.no_serials_found') }}</div>
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-sm table-row-dashed fs-7 gy-3 mb-0">
                                                <thead>
                                                    <tr class="text-start text-muted fw-bold fs-8 text-uppercase gs-0">
                                                        <th class="min-w-200px">{{ __('passwords.serial_number') }}</th>
                                                        <th class="min-w-100px text-center">{{ __('passwords.status') }}</th>
                                                        <th class="min-w-150px">{{ __('passwords.location') }}</th>
                                                        <th class="min-w-150px">{{ __('passwords.department') }}</th>
                                                        <th class="min-w-120px text-end">{{ __('passwords.sold_price') }}</th>
                                                        <th class="min-w-150px">{{ __('passwords.order') }}</th>
                                                        <th class="min-w-120px">{{ __('auth.created_at') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="text-gray-600 fw-semibold">
                                                    @foreach($serials as $serial)
                                                        <tr>
                                                            <td>
                                                                <span class="fw-bold text-gray-800">
                                                                    {{ $serial->serial_number }}
                                                                </span>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge badge-light-{{ $serial->status_color }} fs-8">
                                                                    {{ $serial->status_label }}
                                                                </span>
                                                            </td>
                                                            <td class="text-gray-700">
                                                                {{ $serial->location->name ?? '—' }}
                                                            </td>
                                                            <td class="text-gray-700">
                                                                {{ $serial->department->name ?? '—' }}
                                                            </td>
                                                            <td class="text-end">
                                                                @if($serial->sold_unit_price !== null)
                                                                    <span class="fw-bold text-success">
                                                                        {{ currency_symbol() }}{{ number_format($serial->sold_unit_price, 2) }}
                                                                    </span>
                                                                @else
                                                                    <span class="text-muted">—</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if($serial->order_id)
                                                                    <span class="badge badge-light-info fs-8">
                                                                        #{{ $serial->order->order_number ?? $serial->order_id }}
                                                                    </span>
                                                                @else
                                                                    <span class="text-muted">—</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-muted fs-8">
                                                                {{ $serial->created_at?->format('d M Y, H:i') ?? '—' }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
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
                            {{ __('pagination.no_serials_found') }}
                        </div>
                        <div class="fs-7 text-muted">
                            {{ __('pagination.serials_are_created_from_variant_page') }}
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
(function () {
    'use strict';

    // ── Location → Department cascade ────────────────────────
    const DEPT_URL_TEMPLATE = @json(route('get.departments.by.location', ['locationId' => '__LOC__']));

    async function loadDepartments(locationId) {
        const deptSelect = document.getElementById('serialsDepartmentSelect');
        if (!deptSelect) return;

        deptSelect.innerHTML = `<option value="">{{ __('pagination.all_departments') }}</option>`;
        deptSelect.disabled  = true;
        if (!locationId) return;

        try {
            const res = await fetch(DEPT_URL_TEMPLATE.replace('__LOC__', encodeURIComponent(locationId)), {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();
            const list = Array.isArray(data) ? data : (data.departments ?? []);

            list.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = d.name;
                deptSelect.appendChild(opt);
            });

            deptSelect.disabled = list.length === 0;
        } catch (err) {
            console.error('[serials] dept fetch failed:', err);
            deptSelect.disabled = false;
        }
    }

    document.addEventListener('change', function (e) {
        if (e.target && e.target.id === 'serialsLocationSelect') {
            loadDepartments(e.target.value);
        }
    });

    // ── Product block toggle ─────────────────────────────────
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function (trigger) {
        const targetId = trigger.getAttribute('data-bs-target');
        const target   = document.querySelector(targetId);
        const icon     = trigger.querySelector('.product-toggle-icon');
        if (!target) return;

        target.addEventListener('show.bs.collapse', function () {
            if (icon) icon.classList.add('rotate-180');
        });
        target.addEventListener('hide.bs.collapse', function () {
            if (icon) icon.classList.remove('rotate-180');
        });
    });
})();
</script>

<style>
    .product-toggle-icon { transition: transform 0.15s ease; }
    .product-toggle-icon.rotate-180 { transform: rotate(180deg); }
</style>
@endpush