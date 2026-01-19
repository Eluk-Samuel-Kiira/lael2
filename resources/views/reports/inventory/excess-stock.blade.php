{{-- resources/views/reports/inventory/excess-stock.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.excess_stock_report'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                {{-- Toolbar Section --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
                        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                {{ __('pagination.excess_stock_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.excess_stock') }}</li>
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
                                <form method="GET" action="{{ route('reports.inventory.excess-stock') }}" id="filterForm">
                                    <div class="row g-4 mb-4">
                                        {{-- Department --}}
                                        <div class="col-md-6 col-lg-4">
                                            <label class="form-label fw-semibold">{{ __('pagination.department') }}</label>
                                            <div class="input-group">
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
                                        <div class="col-md-6 col-lg-4">
                                            <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
                                            <div class="input-group">
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
                                        
                                        {{-- Excess Threshold --}}
                                        <div class="col-md-6 col-lg-4">
                                            <label class="form-label fw-semibold">{{ __('pagination.excess_threshold') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-percentage fs-2"></i>
                                                </span>
                                                <select class="form-select" name="excess_threshold">
                                                    <option value="1.2" {{ $excessThreshold == 1.2 ? 'selected' : '' }}>
                                                        120% (20% Excess)
                                                    </option>
                                                    <option value="1.3" {{ $excessThreshold == 1.3 ? 'selected' : '' }}>
                                                        130% (30% Excess)
                                                    </option>
                                                    <option value="1.5" {{ $excessThreshold == 1.5 ? 'selected' : '' }}>
                                                        150% (50% Excess)
                                                    </option>
                                                    <option value="2.0" {{ $excessThreshold == 2.0 ? 'selected' : '' }}>
                                                        200% (100% Excess)
                                                    </option>
                                                    <option value="2.5" {{ $excessThreshold == 2.5 ? 'selected' : '' }}>
                                                        250% (150% Excess)
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="form-text">{{ __('pagination.threshold_description') }}</div>
                                        </div>
                                    </div>
                                    
                                    {{-- Action Buttons --}}
                                    <div class="row">
                                        <div class="col-12 d-flex justify-content-end">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ki-duotone ki-filter fs-2 me-2"></i>
                                                    {{ __('pagination.apply_filters') }}
                                                </button>
                                                <a href="{{ route('reports.inventory.excess-stock') }}" class="btn btn-light btn-active-light-primary">
                                                    <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                                    {{ __('pagination.clear_filters') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Excess Stock Summary --}}
                @if($excessStockItems->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.excess_stock_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $summaryStats = [
                                            [
                                                'key' => 'total_items', 
                                                'color' => 'warning', 
                                                'icon' => 'ki-box', 
                                                'label' => 'excess_items_count',
                                                'value' => number_format($summary['total_items']),
                                                'subtitle' => __('pagination.items_exceeding_threshold')
                                            ],
                                            [
                                                'key' => 'total_excess_quantity', 
                                                'color' => 'danger', 
                                                'icon' => 'ki-barcode', 
                                                'label' => 'excess_quantity',
                                                'value' => number_format($summary['total_excess_quantity']),
                                                'subtitle' => __('pagination.extra_units_above_threshold')
                                            ],
                                            [
                                                'key' => 'total_excess_value', 
                                                'color' => 'danger', 
                                                'icon' => 'ki-dollar', 
                                                'label' => 'excess_value',
                                                'value' => '₦' . number_format($summary['total_excess_value'], 2),
                                                'subtitle' => __('pagination.value_of_excess_stock')
                                            ],
                                            [
                                                'key' => 'avg_excess_percentage', 
                                                'color' => 'warning', 
                                                'icon' => 'ki-percentage', 
                                                'label' => 'avg_excess_percentage',
                                                'value' => number_format($summary['avg_excess_percentage'], 1) . '%',
                                                'subtitle' => __('pagination.average_excess_per_item')
                                            ],
                                            [
                                                'key' => 'excess_threshold', 
                                                'color' => 'info', 
                                                'icon' => 'ki-target', 
                                                'label' => 'current_threshold',
                                                'value' => ($excessThreshold * 100) . '%',
                                                'subtitle' => __('pagination.current_excess_threshold')
                                            ],
                                            [
                                                'key' => 'excess_categories', 
                                                'color' => 'primary', 
                                                'icon' => 'ki-category', 
                                                'label' => 'excess_categories',
                                                'value' => count($excessCategories),
                                                'subtitle' => __('pagination.excess_severity_levels')
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

                {{-- Excess Categories --}}
                @if($excessStockItems->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-category fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.excess_severity_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $severityStats = [
                                            [
                                                'key' => '50_100', 
                                                'color' => 'warning', 
                                                'icon' => 'ki-warning-2', 
                                                'label' => 'moderate_excess',
                                                'value' => number_format($excessCategories['50_100']),
                                                'subtitle' => __('pagination.50_100_excess'),
                                                'description' => __('pagination.moderate_excess_description')
                                            ],
                                            [
                                                'key' => '100_200', 
                                                'color' => 'danger', 
                                                'icon' => 'ki-danger', 
                                                'label' => 'high_excess',
                                                'value' => number_format($excessCategories['100_200']),
                                                'subtitle' => __('pagination.100_200_excess'),
                                                'description' => __('pagination.high_excess_description')
                                            ],
                                            [
                                                'key' => 'over_200', 
                                                'color' => 'danger', 
                                                'icon' => 'ki-cross', 
                                                'label' => 'critical_excess',
                                                'value' => number_format($excessCategories['over_200']),
                                                'subtitle' => __('pagination.over_200_excess'),
                                                'description' => __('pagination.critical_excess_description')
                                            ]
                                        ];
                                    @endphp
                                    
                                    @foreach($severityStats as $stat)
                                    <div class="col-md-4">
                                        <div class="card card-flush border border-{{ $stat['color'] }} border-2 h-100">
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
                                                <div class="fs-7 text-{{ $stat['color'] }} fw-bold mb-2">
                                                    {{ $stat['subtitle'] }}
                                                </div>
                                                <div class="fs-8 text-muted">
                                                    {{ $stat['description'] }}
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

                {{-- Excess Stock Table --}}
                @if($excessStockItems->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.excess_stock_details') }}</h3>
                                    </div>
                                    <span class="badge badge-light-warning fs-7">
                                        {{ __('pagination.showing') }} {{ $excessStockItems->count() }} {{ __('pagination.of') }} {{ $excessStockItems->total() }} {{ __('pagination.excess_items') }}
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
                                                <th>{{ __('pagination.current_stock') }}</th>
                                                <th>{{ __('pagination.preferred_stock') }}</th>
                                                <th>{{ __('pagination.excess_quantity') }}</th>
                                                <th>{{ __('pagination.excess_percentage') }}</th>
                                                <th>{{ __('pagination.excess_severity') }}</th>
                                                <th>{{ __('pagination.excess_value') }}</th>
                                                <th>{{ __('pagination.actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($excessStockItems as $item)
                                            @php
                                                // Calculate excess metrics
                                                $currentStock = $item->quantity_on_hand;
                                                $preferredStock = $item->preferred_stock_level;
                                                $excessQuantity = max(0, $currentStock - ($preferredStock * $excessThreshold));
                                                $excessPercentage = $preferredStock > 0 ? (($currentStock / $preferredStock) - 1) * 100 : 0;
                                                
                                                // Determine severity
                                                $severityColor = 'warning';
                                                $severityIcon = 'ki-warning-2';
                                                $severityText = __('pagination.moderate');
                                                
                                                if ($excessPercentage >= 100 && $excessPercentage < 200) {
                                                    $severityColor = 'danger';
                                                    $severityIcon = 'ki-danger';
                                                    $severityText = __('pagination.high');
                                                } elseif ($excessPercentage >= 200) {
                                                    $severityColor = 'danger';
                                                    $severityIcon = 'ki-cross';
                                                    $severityText = __('pagination.critical');
                                                }
                                                
                                                // Calculate excess value
                                                $costPrice = $item->variant->cost_price ?? 0;
                                                $excessValue = $excessQuantity * $costPrice;
                                            @endphp
                                            <tr class="{{ $severityColor == 'danger' ? 'table-light-' . $severityColor : '' }}">
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
                                                    <span class="fw-bold">{{ number_format($currentStock) }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">{{ number_format($preferredStock) }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-{{ $severityColor }}">
                                                        +{{ number_format($excessQuantity) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-{{ $severityColor }}">
                                                        {{ number_format($excessPercentage, 1) }}%
                                                    </span>
                                                    <div class="progress mt-1" style="height: 5px; width: 80px;">
                                                        <div class="progress-bar bg-{{ $severityColor }}" 
                                                             style="width: {{ min(100, $excessPercentage) }}%"></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $severityColor }}">
                                                        <i class="ki-duotone {{ $severityIcon }} fs-2 me-1">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                        {{ $severityText }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-{{ $severityColor }}">
                                                        ₦{{ number_format($excessValue, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-icon btn-light-warning" 
                                                            data-bs-toggle="tooltip" 
                                                            title="{{ __('pagination.adjust_stock') }}"
                                                            onclick="adjustStock({{ $item->id }})">
                                                        <i class="ki-duotone ki-switch fs-2"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-icon btn-light-info" 
                                                            data-bs-toggle="tooltip" 
                                                            title="{{ __('pagination.transfer_stock') }}"
                                                            onclick="transferStock({{ $item->id }})">
                                                        <i class="ki-duotone ki-move fs-2"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                @if($excessStockItems->hasPages())
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted">
                                            {{ __('pagination.showing') }} {{ $excessStockItems->firstItem() }} - {{ $excessStockItems->lastItem() }} {{ __('pagination.of') }} {{ $excessStockItems->total() }}
                                        </div>
                                        <div>
                                            {{ $excessStockItems->links() }}
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
                                        <i class="ki-duotone ki-check-circle fs-4tx text-success mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_excess_stock_found') }}</h4>
                                        <p class="text-muted fs-6">{{ __('pagination.all_stock_within_limits') }}</p>
                                        <div class="alert alert-success d-inline-block">
                                            <i class="ki-duotone ki-check fs-2 me-2"></i>
                                            {{ __('pagination.excess_threshold_applied', ['threshold' => ($excessThreshold * 100) . '%']) }}
                                        </div>
                                        @if(request()->hasAny(['department_id', 'location_id', 'excess_threshold']))
                                        <a href="{{ route('reports.inventory.excess-stock') }}" class="btn btn-light-primary mt-3">
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

@push('scripts')
<script>
function adjustStock(itemId) {
    // Implement stock adjustment functionality
    alert('Stock adjustment functionality would be implemented here');
}

function transferStock(itemId) {
    // Implement stock transfer functionality
    alert('Stock transfer functionality would be implemented here');
}
</script>
@endpush