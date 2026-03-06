{{-- resources/views/reports/purchasing/received-inventory.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.received_inventory_report'))

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
                                {{ __('pagination.received_inventory_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchasing_reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.received_inventory_report') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($receivedItems->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('pagination.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('pagination.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}">
                                            <i class="ki-duotone ki-file-pdf fs-2 me-2 text-danger"></i>
                                            {{ __('pagination.export_to_pdf') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">
                                            <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                            {{ __('pagination.export_to_csv') }}
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
                                <form method="GET" action="{{ route('reports.purchasing.received-inventory') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-4">
                                        {{-- Start Date --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label required fw-semibold">{{ __('pagination.start_date') }}</label>
                                            <input type="date" class="form-control w-100" name="start_date" 
                                                value="{{ $startDate }}" required>
                                        </div>
                                        
                                        {{-- End Date --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label required fw-semibold">{{ __('pagination.end_date') }}</label>
                                            <input type="date" class="form-control w-100" name="end_date" 
                                                value="{{ $endDate }}" required>
                                        </div>
                                        
                                        {{-- Supplier --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('passwords.supplier') }}</label>
                                            <select class="form-select w-100" name="supplier_id">
                                                <option value="">{{ __('passwords.all_suppliers') }}</option>
                                                @foreach($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}" 
                                                            {{ $supplierId == $supplier->id ? 'selected' : '' }}>
                                                        {{ $supplier->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        {{-- Product Variant --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('passwords.product_variant') }}</label>
                                            <select class="form-select w-100" name="variant_id">
                                                <option value="">{{ __('passwords.all_products') }}</option>
                                                @foreach($variants as $variant)
                                                    <option value="{{ $variant->id }}" 
                                                            {{ $productVariantId == $variant->id ? 'selected' : '' }}>
                                                        {{ $variant->name }} 
                                                        @if($variant->product)
                                                            ({{ $variant->product->name }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-4">
                                        {{-- Expiring Soon --}}
                                        <div class="flex-grow-1">
                                            <div class="form-check form-check-custom form-check-solid">
                                                <input class="form-check-input" type="checkbox" 
                                                    name="include_expiring" 
                                                    id="include_expiring"
                                                    value="1"
                                                    {{ $includeExpiring ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="include_expiring">
                                                    {{ __('passwords.show_only_expiring_soon') }}
                                                </label>
                                                <div class="text-muted fs-8">
                                                    {{ __('passwords.items_expiring_within_30_days') }}
                                                </div>
                                            </div>
                                        </div>
                                        
                                        {{-- Action Buttons --}}
                                        <div class="d-flex flex-column justify-content-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <button type="submit" class="btn btn-primary flex-grow-1 flex-sm-grow-0" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('pagination.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('pagination.apply') }}</span>
                                                </button>
                                                <a href="{{ route('reports.purchasing.received-inventory') }}" class="btn btn-light btn-active-light-primary flex-grow-1 flex-sm-grow-0">
                                                    <i class="ki-duotone ki-cross fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('pagination.clear_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('pagination.clear') }}</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Summary Cards --}}
                <div class="row mb-6">
                    @php
                        $summaryCards = [
                            [
                                'title' => __('passwords.total_received_items'),
                                'value' => number_format($summary['total_items']),
                                'color' => 'primary',
                                'icon' => 'ki-element-plus',
                                'description' => __('passwords.items_received')
                            ],
                            [
                                'title' => __('passwords.total_quantity'),
                                'value' => number_format($summary['total_quantity']),
                                'color' => 'success',
                                'icon' => 'ki-arrow-up',
                                'description' => __('passwords.units_received')
                            ],
                            [
                                'title' => __('passwords.total_value'),
                                'value' => '$' . number_format($summary['total_value'], 2),
                                'color' => 'info',
                                'icon' => 'ki-dollar',
                                'description' => __('passwords.total_received_value')
                            ],
                            [
                                'title' => __('passwords.expiring_soon'),
                                'value' => number_format($summary['expiring_soon']),
                                'color' => 'warning',
                                'icon' => 'ki-clock',
                                'description' => __('passwords.items_expiring_30_days')
                            ]
                        ];
                    @endphp
                    
                    @foreach($summaryCards as $card)
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card card-flush bg-light-{{ $card['color'] }} border border-{{ $card['color'] }} border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone {{ $card['icon'] }} fs-2tx text-{{ $card['color'] }} me-3">
                                        @for($i = 1; $i <= 2; $i++)
                                        <span class="path{{ $i }}"></span>
                                        @endfor
                                    </i>
                                    <div>
                                        <div class="fs-4 fw-bold text-gray-800">{{ $card['title'] }}</div>
                                        <div class="fs-2 fw-bold text-{{ $card['color'] }}">{{ $card['value'] }}</div>
                                        @if($card['description'])
                                        <div class="text-muted fs-7">{{ $card['description'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Additional Stats --}}
                <div class="row mb-6">
                    <div class="col-md-4 mb-4">
                        <div class="card card-flush bg-light-dark h-100">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-shield-tick fs-2tx text-dark me-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <div>
                                        <div class="fs-4 fw-bold text-gray-800">{{ __('passwords.unique_products') }}</div>
                                        <div class="fs-2 fw-bold text-dark">{{ number_format($summary['unique_products']) }}</div>
                                        <div class="text-muted fs-7">{{ __('passwords.different_products_received') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card card-flush bg-light-danger h-100">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-profile-user fs-2tx text-danger me-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <div>
                                        <div class="fs-4 fw-bold text-gray-800">{{ __('passwords.unique_suppliers') }}</div>
                                        <div class="fs-2 fw-bold text-danger">{{ number_format($summary['unique_suppliers']) }}</div>
                                        <div class="text-muted fs-7">{{ __('passwords.suppliers_received_from') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card card-flush bg-light-primary h-100">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-calendar-8 fs-2tx text-primary me-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <div>
                                        <div class="fs-4 fw-bold text-gray-800">{{ __('pagination.period') }}</div>
                                        <div class="fs-2 fw-bold text-primary">{{ $startDate }} - {{ $endDate }}</div>
                                        <div class="text-muted fs-7">{{ __('passwords.receiving_period') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Batch Analysis --}}
                @if($batchAnalysis->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-layer fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.batch_analysis') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ $batchAnalysis->count() }} {{ __('passwords.batches') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('passwords.batch_number') }}</th>
                                                <th>{{ __('passwords.expiry_date') }}</th>
                                                <th>{{ __('passwords.days_to_expiry') }}</th>
                                                <th>{{ __('passwords.total_quantity') }}</th>
                                                <th>{{ __('passwords.total_value') }}</th>
                                                <th>{{ __('passwords.status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($batchAnalysis as $batch)
                                            @php
                                                // Determine expiry status
                                                $daysToExpiry = $batch['days_to_expiry'];
                                                $expiryColor = 'success';
                                                $expiryText = __('passwords.not_expiring');
                                                
                                                if ($daysToExpiry !== null) {
                                                    if ($daysToExpiry <= 0) {
                                                        $expiryColor = 'danger';
                                                        $expiryText = __('passwords.expired');
                                                    } elseif ($daysToExpiry <= 7) {
                                                        $expiryColor = 'danger';
                                                        $expiryText = __('passwords.expiring_soon');
                                                    } elseif ($daysToExpiry <= 30) {
                                                        $expiryColor = 'warning';
                                                        $expiryText = __('passwords.expiring_soon');
                                                    } else {
                                                        $expiryColor = 'success';
                                                        $expiryText = __('passwords.not_expiring_soon');
                                                    }
                                                }
                                                
                                                // Row class for expired/expiring soon
                                                $rowClass = '';
                                                if ($daysToExpiry !== null && $daysToExpiry <= 30) {
                                                    $rowClass = $daysToExpiry <= 0 ? 'table-danger' : 'table-warning';
                                                }
                                            @endphp
                                            <tr class="{{ $rowClass }}">
                                                <td class="ps-4">
                                                    <div class="fw-bold">{{ $batch['batch_number'] ?? __('passwords.no_batch') }}</div>
                                                </td>
                                                <td>
                                                    @if($batch['expiry_date'])
                                                        <span class="fw-semibold">
                                                            {{ \Carbon\Carbon::parse($batch['expiry_date'])->format('M d, Y') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">{{ __('passwords.no_expiry') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($batch['days_to_expiry'] !== null)
                                                        <span class="badge badge-light-{{ $expiryColor }}">
                                                            {{ $batch['days_to_expiry'] }} {{ __('passwords.days') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">
                                                        {{ number_format($batch['total_quantity']) }}
                                                    </span>
                                                    <div class="text-muted fs-8">{{ __('passwords.units') }}</div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">
                                                        ${{ number_format($batch['total_value'], 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $expiryColor }}">
                                                        {{ $expiryText }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Received Inventory Table --}}
                @if($receivedItems->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('passwords.received_inventory') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $receivedItems->count() }} {{ __('pagination.of') }} {{ $receivedItems->total() }} {{ __('passwords.items') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="receivedInventoryTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">#</th>
                                                <th>{{ __('passwords.po_number') }}</th>
                                                <th>{{ __('passwords.product') }}</th>
                                                <th>{{ __('passwords.supplier') }}</th>
                                                <th>{{ __('passwords.quantity_received') }}</th>
                                                <th>{{ __('passwords.unit_cost') }}</th>
                                                <th>{{ __('passwords.total_cost') }}</th>
                                                <th>{{ __('passwords.batch_number') }}</th>
                                                <th>{{ __('passwords.expiry_date') }}</th>
                                                <th>{{ __('passwords.received_by') }}</th>
                                                <th>{{ __('passwords.received_date') }}</th>
                                                <th class="text-end pe-4">{{ __('passwords.expiry_status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($receivedItems as $index => $item)
                                            @php
                                                $po = $item->purchaseOrder;
                                                $expiryDate = $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date) : null;
                                                $daysToExpiry = $expiryDate ? $expiryDate->diffInDays(now()) : null;
                                                
                                                // Determine expiry status
                                                $expiryColor = 'success';
                                                $expiryText = __('passwords.not_expiring');
                                                
                                                if ($daysToExpiry !== null) {
                                                    if ($daysToExpiry <= 0) {
                                                        $expiryColor = 'danger';
                                                        $expiryText = __('passwords.expired');
                                                    } elseif ($daysToExpiry <= 7) {
                                                        $expiryColor = 'danger';
                                                        $expiryText = __('passwords.expiring_soon');
                                                    } elseif ($daysToExpiry <= 30) {
                                                        $expiryColor = 'warning';
                                                        $expiryText = __('passwords.expiring_soon');
                                                    } else {
                                                        $expiryColor = 'success';
                                                        $expiryText = __('passwords.not_expiring_soon');
                                                    }
                                                }
                                                
                                                // Row class for expired/expiring soon
                                                $rowClass = '';
                                                if ($daysToExpiry !== null && $daysToExpiry <= 30) {
                                                    $rowClass = $daysToExpiry <= 0 ? 'table-danger' : 'table-warning';
                                                }
                                            @endphp
                                            <tr class="{{ $rowClass }}">
                                                <td class="ps-4">
                                                    <span class="fw-bold">{{ $loop->iteration }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-40px me-3">
                                                            <div class="symbol-label bg-light-primary text-primary fw-bold">
                                                                PO
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold">{{ $po->po_number ?? 'N/A' }}</div>
                                                            <small class="text-muted">
                                                                {{ $item->created_at->format('M d, Y') }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($item->productVariant && $item->productVariant->product)
                                                        <div class="symbol symbol-40px me-3">
                                                            <div class="symbol-label bg-light-info text-info fw-bold">
                                                                {{ substr($item->productVariant->product->name, 0, 2) }}
                                                            </div>
                                                        </div>
                                                        @endif
                                                        <div>
                                                            <div class="fw-bold">{{ $item->productVariant->name ?? 'N/A' }}</div>
                                                            <small class="text-muted">
                                                                {{ $item->productVariant->sku ?? 'N/A' }}
                                                                @if($item->productVariant && $item->productVariant->product)
                                                                    <br>{{ $item->productVariant->product->name }}
                                                                @endif
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold">{{ $po && $po->supplier ? $po->supplier->name : 'N/A' }}</div>
                                                    <small class="text-muted">
                                                        {{ $po && $po->supplier && $po->supplier->contact_person ? $po->supplier->contact_person : 'N/A' }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">{{ number_format($item->quantity_received) }}</span>
                                                    <div class="text-muted fs-8">{{ __('passwords.units') }}</div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">
                                                        ${{ number_format($item->unit_cost, 2) }}
                                                    </span>
                                                    <div class="text-muted fs-8">{{ __('passwords.per_unit') }}</div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">
                                                        ${{ number_format($item->total_cost, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($item->batch_number)
                                                        <span class="badge badge-light-info">
                                                            {{ $item->batch_number }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">{{ __('passwords.no_batch') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($expiryDate)
                                                        <span class="fw-semibold {{ $expiryColor != 'success' ? 'text-' . $expiryColor : '' }}">
                                                            {{ $expiryDate->format('M d, Y') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">{{ __('passwords.no_expiry') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item->receivedBy)
                                                        <div class="fw-bold">{{ $item->receivedBy->name ?? 'N/A' }}</div>
                                                        <small class="text-muted">
                                                            {{ $item->receivedBy->email ?? 'N/A' }}
                                                        </small>
                                                    @else
                                                        <span class="text-muted">{{ __('pagination.n_a') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="text-muted">
                                                        {{ $item->created_at->format('M d, Y') }}
                                                    </span>
                                                    <div class="text-muted fs-8">
                                                        {{ $item->created_at->format('h:i A') }}
                                                    </div>
                                                </td>
                                                <td class="text-end pe-4">
                                                    @if($daysToExpiry !== null)
                                                    <div class="d-flex flex-column align-items-end">
                                                        <span class="badge badge-{{ $expiryColor }}">
                                                            {{ $expiryText }}
                                                        </span>
                                                        <small class="text-muted fs-8 mt-1">
                                                            {{ $daysToExpiry }} {{ __('passwords.days') }}
                                                        </small>
                                                    </div>
                                                    @else
                                                    <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="card-footer d-flex justify-content-between align-items-center">
                                    <div class="text-muted">
                                        {{ __('pagination.showing') }} {{ $receivedItems->firstItem() }} - {{ $receivedItems->lastItem() }} {{ __('pagination.of') }} {{ $receivedItems->total() }} {{ __('passwords.items') }}
                                    </div>
                                    <div>
                                        {{ $receivedItems->appends(request()->query())->links() }}
                                    </div>
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
                                        @if($includeExpiring)
                                        <i class="ki-duotone ki-clock fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_expiring_items') }}</h4>
                                        <p class="text-muted fs-6">{{ __('passwords.no_items_expiring_within_30_days') }}</p>
                                        @else
                                        <i class="ki-duotone ki-inbox fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_received_items') }}</h4>
                                        <p class="text-muted fs-6">{{ __('passwords.no_received_inventory_for_period') }}</p>
                                        @endif
                                        @if(request()->hasAny(['start_date', 'end_date', 'supplier_id', 'variant_id', 'include_expiring']))
                                        <a href="{{ route('reports.purchasing.received-inventory') }}" class="btn btn-light-primary">
                                            <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                            {{ __('passwords.clear_filters_view_all') }}
                                        </a>
                                        @endif
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            const startDate = new Date(document.querySelector('[name="start_date"]').value);
            const endDate = new Date(document.querySelector('[name="end_date"]').value);
            
            if (startDate > endDate) {
                e.preventDefault();
                alert('{{ __("pagination.start_date_cannot_be_after_end_date") }}');
                return false;
            }
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
@endpush
@endsection