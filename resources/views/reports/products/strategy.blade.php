{{-- resources/views/reports/products/strategy.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.inventory_strategy_report'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                {{-- Toolbar Section --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('pagination.inventory_strategy_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('pagination.dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.product_reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_strategy') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($paginatedData->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('pagination.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'strategyTable', filename: 'inventory_strategy_{{ date('Y_m_d') }}', sheetName: '{{ __('pagination.inventory_strategy') }}'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('pagination.export_to_excel') }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Filter Section --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form method="GET" action="{{ route('reports.products.strategy') }}" id="filterForm">
                                    <div class="row g-3">
                                        {{-- Strategy Filter --}}
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('pagination.inventory_strategy') }}</label>
                                            <select class="form-select" name="strategy" data-control="select2">
                                                @foreach($strategies as $s)
                                                    <option value="{{ $s['value'] }}" {{ $strategy == $s['value'] ? 'selected' : '' }}>
                                                        {{ $s['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        {{-- Category Filter --}}
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('pagination.category') }}</label>
                                            <select class="form-select" name="category_id" data-control="select2">
                                                <option value="">{{ __('pagination.all_categories') }}</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        {{-- Search --}}
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">{{ __('pagination.search') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-magnifier fs-2"></i>
                                                </span>
                                                <input type="text" class="form-control" name="search" 
                                                    value="{{ $search }}" placeholder="{{ __('pagination.search_products') }}">
                                            </div>
                                        </div>
                                        
                                        {{-- Actions --}}
                                        <div class="col-md-2 d-flex align-items-end">
                                            <div class="d-flex gap-2 w-100">
                                                <button type="submit" class="btn btn-primary flex-grow-1">
                                                    <i class="ki-duotone ki-filter fs-2 me-1"></i>
                                                    {{ __('pagination.apply') }}
                                                </button>
                                                <a href="{{ route('reports.products.strategy') }}" class="btn btn-light">
                                                    <i class="ki-duotone ki-cross fs-2"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Summary Statistics --}}
                @if($paginatedData->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.summary_statistics') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    {{-- Total Products --}}
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-2">
                                                    <i class="ki-duotone ki-box fs-2tx text-primary">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <span class="fs-1 fw-bold text-gray-800">{{ number_format($summary['total_products']) }}</span>
                                                <span class="text-gray-600 fw-semibold">{{ __('pagination.total_products') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Strategy Breakdown --}}
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card card-flush h-100">
                                            <div class="card-body d-flex flex-column justify-content-center">
                                                <div class="text-gray-600 fw-semibold mb-2">{{ __('pagination.by_strategy') }}</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach(['quantity', 'batch', 'serial', 'recipe'] as $s)
                                                        @php
                                                            $count = $summary[$s . '_strategy'] ?? 0;
                                                            $color = $s == 'quantity' ? 'primary' : ($s == 'batch' ? 'info' : ($s == 'serial' ? 'warning' : 'success'));
                                                        @endphp
                                                        @if($count > 0)
                                                            <span class="badge badge-light-{{ $color }} fs-6 py-2 px-3">
                                                                {{ ucfirst($s) }}: {{ $count }}
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Stock Status --}}
                                    <div class="col-md-6 col-lg-3">
                                        <div class="card card-flush h-100">
                                            <div class="card-body d-flex flex-column justify-content-center">
                                                <div class="text-gray-600 fw-semibold mb-2">{{ __('pagination.stock_status') }}</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <span class="badge badge-light-success fs-6 py-2 px-3">
                                                        {{ __('pagination.in_stock') }}: {{ $summary['in_stock'] }}
                                                    </span>
                                                    <span class="badge badge-light-warning fs-6 py-2 px-3">
                                                        {{ __('pagination.low_stock') }}: {{ $summary['low_stock'] }}
                                                    </span>
                                                    <span class="badge badge-light-danger fs-6 py-2 px-3">
                                                        {{ __('pagination.out_of_stock') }}: {{ $summary['out_of_stock'] }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Total Value --}}
                                    <div class="col-md-6 col-lg-3">
                                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-2">
                                                    <i class="ki-duotone ki-dollar fs-2tx text-success">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <span class="fs-1 fw-bold text-gray-800">
                                                    {{ currency_symbol() }}{{ number_format($summary['total_value'], 2) }}
                                                </span>
                                                <span class="text-gray-600 fw-semibold">{{ __('pagination.total_inventory_value') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Strategy Table --}}
                @if($paginatedData->count() > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('pagination.products_by_strategy') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $paginatedData->count() }} {{ __('pagination.of') }} {{ $paginatedData->total() }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="strategyTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4 min-w-50px">#</th>
                                                <th class="min-w-200px">{{ __('pagination.product') }}</th>
                                                <th class="min-w-150px">{{ __('pagination.category') }}</th>
                                                <th class="min-w-120px">{{ __('pagination.strategy') }}</th>
                                                <th class="min-w-100px text-center">{{ __('pagination.variants') }}</th>
                                                <th class="min-w-120px text-center">{{ __('pagination.total_stock') }}</th>
                                                <th class="min-w-150px text-center">{{ __('pagination.total_value') }}</th>
                                                <th class="min-w-120px text-center">{{ __('pagination.status') }}</th>
                                                <th class="min-w-100px text-center">{{ __('pagination.actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($paginatedData as $index => $item)
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="text-muted">{{ $paginatedData->firstItem() + $index }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($item->product->image_url)
                                                        <div class="symbol symbol-40px me-3">
                                                            <img src="{{ asset($item->product->image_url) }}" class="img-fluid rounded" alt="{{ $item->product->name }}">
                                                        </div>
                                                        @endif
                                                        <div>
                                                            <div class="fw-bold">{{ $item->product->name }}</div>
                                                            <div class="text-muted fs-7">{{ $item->product->sku }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-primary">
                                                        {{ $item->product->category->name ?? '-' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $item->strategy_color }} fs-7 py-2 px-3">
                                                        <i class="ki-duotone {{ $item->strategy_icon }} fs-2 me-1">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                        {{ $item->strategy_label }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="fw-bold">{{ $item->variant_count }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="fw-bold {{ $item->total_stock == 0 ? 'text-danger' : 'text-success' }}">
                                                        {{ number_format($item->total_stock) }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="fw-bold">{{ currency_symbol() }}{{ number_format($item->total_value, 2) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $item->status_color }} fs-7 py-2 px-3">
                                                        {{ $item->status_label }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-light-primary" 
                                                        onclick="viewDetails({{ $item->product->id }})">
                                                        <i class="ki-duotone ki-eye fs-2">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                <div class="card-footer">
                                    @include('partials.pagination', [
                                        'paginator' => $paginatedData,
                                        'pageName' => 'page',
                                        'perPageName' => 'per_page',
                                        'showPerPage' => true
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                    {{-- No Data Message --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="text-center py-10">
                                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('pagination.no_products_found') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- View Details Modal --}}
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="detailsModalLabel">
                    <i class="ki-duotone ki-information-5 fs-2 me-2 text-primary">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    {{ __('pagination.product_details') }}
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <div class="text-center py-10">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('pagination.close') }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ─── View Details ──────────────────────────────────────────────
function viewDetails(productId) {
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    const body = document.getElementById('detailsModalBody');
    
    body.innerHTML = `
        <div class="text-center py-10">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // ✅ Build URL with product ID
    const url = `/reports/products/strategy/detail/${productId}`;
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            let html = '';
            
            // ─── Product Info ──────────────────────────────────────────
            html += `
                <div class="d-flex align-items-center mb-4">
                    ${data.product.image_url ? `
                        <div class="symbol symbol-80px me-4">
                            <img src="${data.product.image_url}" class="img-fluid rounded" alt="${data.product.name}">
                        </div>
                    ` : ''}
                    <div>
                        <h4 class="fw-bold mb-1">${data.product_name}</h4>
                        <div class="text-muted">SKU: ${data.product_sku}</div>
                        <div class="mt-2">
                            <span class="badge badge-light-${data.status_color || 'secondary'} fs-7 py-2 px-3">
                                ${data.status_label || 'Unknown'}
                            </span>
                            <span class="badge badge-light-${data.strategy_color} ms-2">
                                <i class="ki-duotone ${data.strategy_icon} fs-2 me-1"></i>
                                ${data.strategy_label}
                            </span>
                            <span class="badge badge-light-${data.is_single_shop ? 'primary' : 'info'} ms-2">
                                ${data.is_single_shop ? 'Single Shop' : 'Multi-Shop'}
                            </span>
                        </div>
                    </div>
                </div>
            `;
            
            // ─── Summary Cards ──────────────────────────────────────────
            html += `
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed">
                            <div class="card-body text-center">
                                <span class="text-muted">Total Variants</span>
                                <div class="fs-2 fw-bold">${data.variants.length}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-success border border-success border-dashed">
                            <div class="card-body text-center">
                                <span class="text-muted">Total Stock</span>
                                <div class="fs-2 fw-bold">${data.total_stock.toLocaleString()}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-info border border-info border-dashed">
                            <div class="card-body text-center">
                                <span class="text-muted">Total Value</span>
                                <div class="fs-2 fw-bold text-success">${data.total_value ? data.total_value.toFixed(2) : '0.00'}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // ─── Strategy-Specific Details ─────────────────────────────
            if (data.strategy === 'quantity') {
                html += generateQuantityVariantDetails(data.variants);
            } else if (data.strategy === 'batch') {
                html += generateBatchVariantDetails(data.variants);
            } else if (data.strategy === 'serial') {
                html += generateSerialVariantDetails(data.variants);
            } else if (data.strategy === 'recipe') {
                html += generateRecipeDetails(data.variants);
            }
            
            body.innerHTML = html;
        })
        .catch(error => {
            body.innerHTML = `
                <div class="text-center py-10 text-danger">
                    <i class="ki-duotone ki-cross-circle fs-4tx mb-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <h4>Error Loading Details</h4>
                    <p class="text-muted">${error.message}</p>
                </div>
            `;
        });
}

// ─── Generate Quantity Variant Details ──────────────────────
function generateQuantityVariantDetails(variants) {
    let html = `
        <h6 class="fw-bold mb-3">Variant Stock Details</h6>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Variant</th>
                        <th>SKU</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Cost Price</th>
                        <th class="text-center">Selling Price</th>
                        <th class="text-center">Value</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    variants.forEach(v => {
        const stockClass = v.stock == 0 ? 'text-danger' : 'text-success';
        html += `
            <tr>
                <td><strong>${v.variant_name}</strong></td>
                <td>${v.variant_sku}</td>
                <td class="text-center ${stockClass}">${v.stock.toLocaleString()}</td>
                <td class="text-center">${v.cost_price ? v.cost_price.toFixed(2) : '0.00'}</td>
                <td class="text-center">${v.selling_price ? v.selling_price.toFixed(2) : '0.00'}</td>
                <td class="text-center">${v.value ? v.value.toFixed(2) : '0.00'}</td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    return html;
}

// ─── Generate Batch Variant Details ────────────────────────
function generateBatchVariantDetails(variants) {
    let html = `
        <h6 class="fw-bold mb-3">Batch Details</h6>
    `;
    
    variants.forEach(v => {
        if (v.details && v.details.length > 0) {
            html += `
                <div class="card card-flush bg-light-info border border-info border-dashed mb-3">
                    <div class="card-header">
                        <div class="card-title">
                            <strong>${v.variant_name}</strong>
                            <span class="badge badge-light-dark ms-2">SKU: ${v.variant_sku}</span>
                            <span class="badge badge-light-success ms-2">Total: ${v.stock.toLocaleString()}</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Batch Number</th>
                                        <th class="text-center">Quantity</th>
                                        <th>Expiry Date</th>
                                        <th class="text-center">Value</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;
            
            v.details.forEach(batch => {
                const isExpired = batch.expiry_date && new Date(batch.expiry_date) < new Date();
                const expiryClass = isExpired ? 'text-danger' : '';
                html += `
                    <tr>
                        <td><span class="badge badge-light-dark">${batch.batch_number}</span></td>
                        <td class="text-center">${batch.quantity.toLocaleString()}</td>
                        <td class="${expiryClass}">${batch.expiry_date || '-'}</td>
                        <td class="text-center">${batch.value ? batch.value.toFixed(2) : '0.00'}</td>
                    </tr>
                `;
            });
            
            html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        } else {
            html += `
                <div class="alert alert-light text-center">
                    No batches available for <strong>${v.variant_name}</strong>
                </div>
            `;
        }
    });
    
    return html;
}

// ─── Generate Serial Variant Details ──────────────────────
function generateSerialVariantDetails(variants) {
    let html = `
        <h6 class="fw-bold mb-3">Serial Numbers</h6>
    `;
    
    variants.forEach(v => {
        if (v.details && v.details.length > 0) {
            html += `
                <div class="card card-flush bg-light-warning border border-warning border-dashed mb-3">
                    <div class="card-header">
                        <div class="card-title">
                            <strong>${v.variant_name}</strong>
                            <span class="badge badge-light-dark ms-2">SKU: ${v.variant_sku}</span>
                            <span class="badge badge-light-success ms-2">Total: ${v.stock.toLocaleString()}</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Serial Number</th>
                                        <th>Location</th>
                                        <th>Expiry Date</th>
                                        <th class="text-center">Value</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;
            
            v.details.forEach(serial => {
                html += `
                    <tr>
                        <td><span class="badge badge-light-dark">${serial.serial_number}</span></td>
                        <td>${serial.location || '-'}</td>
                        <td>${serial.expiry_date || '-'}</td>
                        <td class="text-center">${serial.value ? serial.value.toFixed(2) : '0.00'}</td>
                    </tr>
                `;
            });
            
            html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        } else {
            html += `
                <div class="alert alert-light text-center">
                    No serial numbers available for <strong>${v.variant_name}</strong>
                </div>
            `;
        }
    });
    
    return html;
}

// ─── Generate Recipe Details ──────────────────────────────
function generateRecipeDetails(variants) {
    let html = `
        <h6 class="fw-bold mb-3">Recipe Ingredients</h6>
    `;
    
    // For recipe products, the first variant contains the recipe data
    const recipeData = variants.length > 0 ? variants[0].details : null;
    
    if (recipeData && recipeData.ingredients && recipeData.ingredients.length > 0) {
        html += `
            <div class="card card-flush bg-light-success border border-success border-dashed mb-3">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <span class="text-muted">Can Produce</span>
                            <div class="fs-3 fw-bold ${recipeData.can_produce ? 'text-success' : 'text-warning'}">
                                ${recipeData.can_produce ? 'Yes' : 'No'}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted">Max Producible</span>
                            <div class="fs-3 fw-bold text-primary">
                                ${recipeData.max_producible.toLocaleString()} units
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Ingredient</th>
                                    <th>SKU</th>
                                    <th class="text-center">Required Per Unit</th>
                                    <th class="text-center">Available Stock</th>
                                    <th class="text-center">Can Produce</th>
                                    <th class="text-center">Cost</th>
                                </tr>
                            </thead>
                            <tbody>
        `;
        
        recipeData.ingredients.forEach(ing => {
            const stockClass = ing.available_stock == 0 ? 'text-danger' : 'text-success';
            const produceClass = ing.producible > 0 ? 'text-success' : 'text-danger';
            html += `
                <tr>
                    <td><strong>${ing.ingredient_name}</strong></td>
                    <td>${ing.ingredient_sku}</td>
                    <td class="text-center">${ing.quantity_required}</td>
                    <td class="text-center ${stockClass}">
                        ${ing.available_stock.toLocaleString()}
                    </td>
                    <td class="text-center ${produceClass}">
                        ${ing.producible.toLocaleString()}
                    </td>
                    <td class="text-center">${ing.cost_price ? ing.cost_price.toFixed(2) : '0.00'}</td>
                </tr>
            `;
        });
        
        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    } else {
        html += `
            <div class="alert alert-light text-center">
                No recipe ingredients found
            </div>
        `;
    }
    
    return html;
}

// ─── Export Function ──────────────────────────────────────────────
function exportCurrentPage(config) {
    const { tableId, filename, sheetName = 'Sheet1', format = 'excel' } = config;
    const table = document.getElementById(tableId);
    
    if (!table) {
        alert('Table not found');
        return;
    }
    
    console.log(`Exporting ${tableId} to ${format} as ${filename}`);
}
</script>
@endpush

@endsection