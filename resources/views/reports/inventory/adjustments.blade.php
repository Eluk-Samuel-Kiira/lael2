{{-- resources/views/reports/inventory/adjustments.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.inventory_adjustments_report'))

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
                                {{ __('pagination.inventory_adjustments_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_adjustments') }}</li>
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
                                <form method="GET" action="{{ route('reports.inventory.adjustments') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-4">
                                        {{-- Date Range --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('pagination.date_range') }}</label>
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <div class="input-group w-100">
                                                    <span class="input-group-text">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control" name="start_date" 
                                                        value="{{ $startDate }}">
                                                </div>
                                                <span class="d-none d-sm-flex align-items-center text-gray-500 px-2">{{ __('pagination.to') }}</span>
                                                <span class="d-flex d-sm-none text-gray-500 text-center">{{ __('pagination.to') }}</span>
                                                <div class="input-group w-100">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control" name="end_date" 
                                                        value="{{ $endDate }}">
                                                </div>
                                            </div>
                                        </div>
                                        
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
                                        
                                        {{-- Action Buttons --}}
                                        <div class="d-flex flex-column justify-content-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <button type="submit" class="btn btn-primary flex-grow-1" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('pagination.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('pagination.apply') }}</span>
                                                </button>
                                                <a href="{{ route('reports.inventory.adjustments') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
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

                {{-- Summary Statistics --}}
                @if($adjustments->count() > 0)
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
                                    @php
                                        $stats = [
                                            [
                                                'key' => 'total_adjustments', 
                                                'color' => 'primary', 
                                                'icon' => 'ki-switch', 
                                                'label' => 'total_adjustments',
                                                'value' => number_format($summary['total_adjustments'])
                                            ],
                                            [
                                                'key' => 'total_quantity_changed', 
                                                'color' => 'info', 
                                                'icon' => 'ki-arrow-change', 
                                                'label' => 'total_quantity_changed',
                                                'value' => number_format($summary['total_quantity_changed'])
                                            ],
                                            [
                                                'key' => 'net_change', 
                                                'color' => $summary['net_change'] >= 0 ? 'success' : 'danger', 
                                                'icon' => 'ki-trend-up', 
                                                'label' => 'net_change',
                                                'value' => ($summary['net_change'] >= 0 ? '+' : '') . number_format($summary['net_change'])
                                            ],
                                            [
                                                'key' => 'increase_count', 
                                                'color' => 'success', 
                                                'icon' => 'ki-arrow-up', 
                                                'label' => 'increase_count',
                                                'value' => number_format($summary['increase_count'])
                                            ],
                                            [
                                                'key' => 'decrease_count', 
                                                'color' => 'danger', 
                                                'icon' => 'ki-arrow-down', 
                                                'label' => 'decrease_count',
                                                'value' => number_format($summary['decrease_count'])
                                            ]
                                        ];
                                    @endphp
                                    
                                    @foreach($stats as $stat)
                                    <div class="col-md-6 col-lg">
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
                                                <div class="text-gray-600 fw-semibold">
                                                    {{ __('pagination.' . $stat['label']) }}
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

                {{-- Adjustments Table --}}
                @if($adjustments->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.adjustment_history') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $adjustments->count() }} {{ __('pagination.of') }} {{ $adjustments->total() }} {{ __('pagination.adjustments') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('pagination.date_time') }}</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th>{{ __('pagination.department') }}</th>
                                                <th>{{ __('pagination.location') }}</th>
                                                <th>{{ __('pagination.before') }}</th>
                                                <th>{{ __('pagination.after') }}</th>
                                                <th>{{ __('pagination.change') }}</th>
                                                <th>{{ __('pagination.reason') }}</th>
                                                <th>{{ __('pagination.adjusted_by') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($adjustments as $adjustment)
                                            @php
                                                $change = $adjustment->quantity_after - $adjustment->quantity_before;
                                                $changeColor = $change > 0 ? 'success' : ($change < 0 ? 'danger' : 'info');
                                                $changeIcon = $change > 0 ? 'ki-arrow-up' : ($change < 0 ? 'ki-arrow-down' : 'ki-minus');
                                                $changeSign = $change > 0 ? '+' : '';
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    {{ $adjustment->created_at->format('Y-m-d H:i') }}
                                                </td>
                                                <td>
                                                    <div class="fw-bold">{{ $adjustment->inventoryItems->variant->name ?? '-' }}</div>
                                                    <div class="text-muted">{{ $adjustment->inventoryItems->variant->sku ?? '' }}</div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ $adjustment->inventoryItems->departmentItem->name ?? '-' }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info">{{ $adjustment->inventoryItems->itemLocation->name ?? '-' }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">{{ number_format($adjustment->quantity_before) }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">{{ number_format($adjustment->quantity_after) }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-{{ $changeColor }}">
                                                        <i class="ki-duotone {{ $changeIcon }} fs-2 me-1">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                        {{ $changeSign }}{{ number_format(abs($change)) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-dark">{{ $adjustment->reason }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-muted">{{ $adjustment->createdBy->name ?? '-' }}</span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                @if($adjustments->hasPages())
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted">
                                            {{ __('pagination.showing') }} {{ $adjustments->firstItem() }} - {{ $adjustments->lastItem() }} {{ __('pagination.of') }} {{ $adjustments->total() }}
                                        </div>
                                        <div>
                                            {{ $adjustments->links() }}
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
                                        <p class="text-muted fs-6">{{ __('pagination.no_adjustments_found') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'department_id', 'location_id']))
                                        <a href="{{ route('reports.inventory.adjustments') }}" class="btn btn-light-primary">
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