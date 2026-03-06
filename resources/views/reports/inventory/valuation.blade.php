{{-- resources/views/reports/inventory/valuation.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.inventory_valuation_report'))

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
                                {{ __('pagination.inventory_valuation_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_valuation') }}</li>
                            </ul>
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
                                <form method="GET" action="{{ route('reports.inventory.valuation') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap">
                                        {{-- Department --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('pagination.department') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-building fs-2"></i>
                                                </span>
                                                <select class="form-select" name="department_id">
                                                    <option value="">{{ __('pagination.all_departments') }}</option>
                                                    @foreach($departments as $department)
                                                        <option value="{{ $department->id }}" 
                                                                {{ $departmentId == $department->id ? 'selected' : '' }}>
                                                            {{ $department->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Location --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-location fs-2"></i>
                                                </span>
                                                <select class="form-select" name="location_id">
                                                    <option value="">{{ __('pagination.all_locations') }}</option>
                                                    @foreach($locations as $location)
                                                        <option value="{{ $location->id }}" 
                                                                {{ $locationId == $location->id ? 'selected' : '' }}>
                                                            {{ $location->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Valuation Method --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('pagination.valuation_method') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-calculator fs-2"></i>
                                                </span>
                                                <select class="form-select" name="valuation_method">
                                                    <option value="cost" {{ $valuationMethod == 'cost' ? 'selected' : '' }}>
                                                        {{ __('pagination.cost_method') }}
                                                    </option>
                                                    <option value="fifo" {{ $valuationMethod == 'fifo' ? 'selected' : '' }}>
                                                        {{ __('pagination.fifo_method') }}
                                                    </option>
                                                    <option value="lifo" {{ $valuationMethod == 'lifo' ? 'selected' : '' }}>
                                                        {{ __('pagination.lifo_method') }}
                                                    </option>
                                                    <option value="weighted_average" {{ $valuationMethod == 'weighted_average' ? 'selected' : '' }}>
                                                        {{ __('pagination.weighted_average_method') }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Action Buttons --}}
                                        <div class="d-flex flex-column justify-content-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <button type="submit" class="btn btn-primary flex-grow-1" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('pagination.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('pagination.apply') }}</span>
                                                </button>
                                                <a href="{{ route('reports.inventory.valuation') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
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

                {{-- Valuation Summary --}}
                @if($inventoryItems->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.valuation_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $summaryStats = [
                                            [
                                                'key' => 'total_items', 
                                                'color' => 'primary', 
                                                'icon' => 'ki-box', 
                                                'label' => 'total_items',
                                                'value' => number_format($valuationSummary['total_items']),
                                                'subtitle' => __('pagination.items_in_stock')
                                            ],
                                            [
                                                'key' => 'total_quantity', 
                                                'color' => 'info', 
                                                'icon' => 'ki-barcode', 
                                                'label' => 'total_quantity',
                                                'value' => number_format($valuationSummary['total_quantity']),
                                                'subtitle' => __('pagination.units_in_stock')
                                            ],
                                            [
                                                'key' => 'total_value', 
                                                'color' => 'success', 
                                                'icon' => 'ki-dollar', 
                                                'label' => 'total_value',
                                                'value' =>  number_format($valuationSummary['total_value'], 2),
                                                'subtitle' => __('pagination.current_inventory_value')
                                            ],
                                            [
                                                'key' => 'avg_unit_cost', 
                                                'color' => 'warning', 
                                                'icon' => 'ki-tag', 
                                                'label' => 'avg_unit_cost',
                                                'value' =>  number_format($valuationSummary['avg_unit_cost'], 2),
                                                'subtitle' => __('pagination.average_cost_per_unit')
                                            ],
                                            [
                                                'key' => 'potential_profit', 
                                                'color' => 'danger', 
                                                'icon' => 'ki-trend-up', 
                                                'label' => 'potential_profit',
                                                'value' =>  number_format($valuationSummary['potential_profit'], 2),
                                                'subtitle' => __('pagination.potential_profit_margin')
                                            ],
                                            [
                                                'key' => 'valuation_method', 
                                                'color' => 'secondary', 
                                                'icon' => 'ki-calculator', 
                                                'label' => 'valuation_method',
                                                'value' => __('pagination.' . $valuationMethod . '_method'),
                                                'subtitle' => __('pagination.current_valuation_method')
                                            ]
                                        ];
                                    @endphp
                                    
                                    @foreach($summaryStats as $stat)
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush border border-{{ $stat['color'] }} border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone {{ $stat['icon'] }} fs-2tx text-{{ $stat['color'] }}">
                                                        @for($i = 1; $i <= 2; $i++)
                                                        <span class="path{{ $i }}"></span>
                                                        @endfor
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">
                                                        {{ $stat['value'] }}
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold mb-2">
                                                    {{ __('pagination.' . $stat['label']) }}
                                                </div>
                                                <div class="fs-8 text-muted">
                                                    {{ $stat['subtitle'] }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Charts Section --}}
                @if($inventoryItems->count() > 0)
                <div class="row mb-6">
                    {{-- Value by Department --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.value_by_department') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th>{{ __('pagination.department') }}</th>
                                                <th class="text-end">{{ __('pagination.value') }}</th>
                                                <th class="text-end">{{ __('pagination.percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $totalValue = $valuationSummary['total_value'];
                                            @endphp
                                            @foreach($valueByDepartment as $deptData)
                                            @php
                                                $percentage = $totalValue > 0 ? ($deptData['value'] / $totalValue) * 100 : 0;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ $deptData['name'] ?? __('pagination.unspecified') }}</span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-bold">₦{{ number_format($deptData['value'], 2) }}</span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge badge-light-info">{{ number_format($percentage, 1) }}%</span>
                                                    <div class="progress mt-1" style="height: 5px;">
                                                        <div class="progress-bar bg-info" style="width: {{ $percentage }}%"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Value by Location --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.value_by_location') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th>{{ __('pagination.location') }}</th>
                                                <th class="text-end">{{ __('pagination.value') }}</th>
                                                <th class="text-end">{{ __('pagination.percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($valueByLocation as $locData)
                                            @php
                                                $percentage = $totalValue > 0 ? ($locData['value'] / $totalValue) * 100 : 0;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="badge badge-light-info">{{ $locData['name'] ?? __('pagination.unspecified') }}</span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-bold">₦{{ number_format($locData['value'], 2) }}</span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge badge-light-success">{{ number_format($percentage, 1) }}%</span>
                                                    <div class="progress mt-1" style="height: 5px;">
                                                        <div class="progress-bar bg-success" style="width: {{ $percentage }}%"></div>
                                                    </div>
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

                {{-- Valuation Details Table --}}
                @if($inventoryItems->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.inventory_valuation_details') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $inventoryItems->count() }} {{ __('pagination.of') }} {{ $inventoryItems->total() }} {{ __('pagination.items') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('pagination.sku') }}</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th>{{ __('pagination.department') }}</th>
                                                <th>{{ __('pagination.location') }}</th>
                                                <th>{{ __('pagination.quantity') }}</th>
                                                <th>{{ __('pagination.unit_cost') }}</th>
                                                <th>{{ __('pagination.unit_price') }}</th>
                                                <th>{{ __('pagination.valuation_value') }}</th>
                                                <th>{{ __('pagination.potential_profit') }}</th>
                                                <th>{{ __('pagination.profit_margin') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($inventoryItems as $item)
                                            @php
                                                $costPrice = $item->variant->cost_price ?? 0;
                                                $sellingPrice = $item->variant->price ?? 0;
                                                $quantity = $item->quantity_on_hand;
                                                
                                                // Calculate profit margin percentage
                                                $profitMargin = 0;
                                                if ($costPrice > 0 && $sellingPrice > 0) {
                                                    $profitMargin = (($sellingPrice - $costPrice) / $costPrice) * 100;
                                                }
                                                
                                                $marginColor = 'success';
                                                if ($profitMargin < 20) {
                                                    $marginColor = 'warning';
                                                }
                                                if ($profitMargin < 0) {
                                                    $marginColor = 'danger';
                                                }
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-semibold">{{ $item->variant->sku ?? '-' }}</div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold">{{ $item->variant->name ?? '-' }}</div>
                                                    <div class="text-muted">{{ $item->variant->product->name ?? '' }}</div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ $item->departmentItem->name ?? '-' }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info">{{ $item->itemLocation->name ?? '-' }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">{{ number_format($quantity) }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">{{ number_format($costPrice, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">{{ number_format($sellingPrice, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">{{ number_format($item->valuation_value ?? 0, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">{{ number_format($item->potential_profit ?? 0, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $marginColor }}">
                                                        {{ number_format($profitMargin, 1) }}%
                                                    </span>
                                                    <div class="progress mt-1" style="height: 5px; width: 80px;">
                                                        <div class="progress-bar bg-{{ $marginColor }}" 
                                                             style="width: {{ min(100, $profitMargin) }}%"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                @if($inventoryItems->hasPages())
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted">
                                            {{ __('pagination.showing') }} {{ $inventoryItems->firstItem() }} - {{ $inventoryItems->lastItem() }} {{ __('pagination.of') }} {{ $inventoryItems->total() }}
                                        </div>
                                        <div>
                                            {{ $inventoryItems->links() }}
                                        </div>
                                    </div>
                                </div>
                                @endif
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
                                        <p class="text-muted fs-6">{{ __('pagination.no_valuation_data_found') }}</p>
                                        @if(request()->hasAny(['department_id', 'location_id', 'valuation_method']))
                                        <a href="{{ route('reports.inventory.valuation') }}" class="btn btn-light-primary">
                                            <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                            {{ __('pagination.clear_filters') }}
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
@endsection