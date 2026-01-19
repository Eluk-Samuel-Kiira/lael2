{{-- resources/views/reports/orders/summary.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.order_summary_report'))

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
                                {{ __('auth.order_summary_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('accounting.dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.summary') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if($summary['total_orders'] > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'orderSummaryTable', filename: 'order_summary_{{ date('Y_m_d') }}', sheetName: 'Order Summary'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'orderSummaryTable', filename: 'order_summary_{{ date('Y_m_d') }}', format: 'csv'})">
                                            <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                            {{ __('accounting.export_to_csv') }}
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
                                    <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form method="GET" action="{{ route('reports.orders.summary') }}" id="filterForm">
                                    <div class="row g-6 mb-6">
                                        {{-- Date Range --}}
                                        <div class="col-md-6 col-lg-4">
                                            <label class="form-label required fw-semibold">{{ __('accounting.date_range') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                </span>
                                                <input type="date" class="form-control" name="start_date" 
                                                    value="{{ $startDate }}" required
                                                    title="{{ __('auth.start_date') }}">
                                                <span class="input-group-text bg-light">{{ __('accounting.to') }}</span>
                                                <input type="date" class="form-control" name="end_date" 
                                                    value="{{ $endDate }}" required
                                                    title="{{ __('auth.end_date') }}">
                                            </div>
                                        </div>
                                        
                                        {{-- Location --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-location fs-2"></i>
                                                </span>
                                                <select class="form-select" name="location_id">
                                                    <option value="">{{ __('auth.all_locations') }}</option>
                                                    @foreach($locations as $location)
                                                        <option value="{{ $location->id }}" 
                                                                {{ $locationId == $location->id ? 'selected' : '' }}>
                                                            {{ $location->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Department --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('auth.department') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-building fs-2"></i>
                                                </span>
                                                <select class="form-select" name="department_id">
                                                    <option value="">{{ __('auth.all_departments') }}</option>
                                                    @foreach($departments as $department)
                                                        <option value="{{ $department->id }}" 
                                                                {{ $departmentId == $department->id ? 'selected' : '' }}>
                                                            {{ $department->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Order Type --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('auth.order_type') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-bag fs-2"></i>
                                                </span>
                                                <select class="form-select" name="order_type">
                                                    <option value="all">{{ __('auth.all_types') }}</option>
                                                    <option value="sale" {{ $orderType == 'sale' ? 'selected' : '' }}>{{ __('auth.sale') }}</option>
                                                    <option value="return" {{ $orderType == 'return' ? 'selected' : '' }}>{{ __('auth.return') }}</option>
                                                    <option value="quote" {{ $orderType == 'quote' ? 'selected' : '' }}>{{ __('auth.quote') }}</option>
                                                    <option value="layby" {{ $orderType == 'layby' ? 'selected' : '' }}>{{ __('auth.layby') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Order Status --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('auth.order_status') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-status fs-2"></i>
                                                </span>
                                                <select class="form-select" name="order_status">
                                                    <option value="all">{{ __('auth.all_statuses') }}</option>
                                                    <option value="draft" {{ $orderStatus == 'draft' ? 'selected' : '' }}>{{ __('auth.draft') }}</option>
                                                    <option value="confirmed" {{ $orderStatus == 'confirmed' ? 'selected' : '' }}>{{ __('auth.confirmed') }}</option>
                                                    <option value="processing" {{ $orderStatus == 'processing' ? 'selected' : '' }}>{{ __('auth.processing') }}</option>
                                                    <option value="completed" {{ $orderStatus == 'completed' ? 'selected' : '' }}>{{ __('auth.completed') }}</option>
                                                    <option value="cancelled" {{ $orderStatus == 'cancelled' ? 'selected' : '' }}>{{ __('auth.cancelled') }}</option>
                                                    <option value="refunded" {{ $orderStatus == 'refunded' ? 'selected' : '' }}>{{ __('auth.refunded') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Action Buttons --}}
                                    <div class="row">
                                        <div class="col-12 d-flex justify-content-end">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-2"></i>
                                                    {{ __('accounting.apply_filters') }}
                                                </button>
                                                <a href="{{ route('reports.orders.summary') }}" class="btn btn-light btn-active-light-primary">
                                                    <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                                    {{ __('accounting.clear_filters') }}
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
                @if($summary['total_orders'] > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.summary_statistics') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @foreach([
                                        ['key' => 'total_orders', 'color' => 'primary', 'icon' => 'ki-bag', 'label' => 'total_orders', 'value' => $summary['total_orders']],
                                        ['key' => 'total_sales', 'color' => 'success', 'icon' => 'ki-dollar', 'label' => 'total_sales', 'value' => '$' . number_format($summary['total_sales'], 2)],
                                        ['key' => 'total_tax', 'color' => 'info', 'icon' => 'ki-receipt-tax', 'label' => 'total_tax', 'value' => '$' . number_format($summary['total_tax'], 2)],
                                        ['key' => 'total_discount', 'color' => 'warning', 'icon' => 'ki-percentage', 'label' => 'total_discount', 'value' => '$' . number_format($summary['total_discount'], 2)],
                                        ['key' => 'average_order_value', 'color' => 'danger', 'icon' => 'ki-calculator', 'label' => 'average_order_value', 'value' => '$' . number_format($summary['average_order_value'], 2)],
                                        ['key' => 'max_order_value', 'color' => 'secondary', 'icon' => 'ki-arrow-up', 'label' => 'max_order_value', 'value' => '$' . number_format($summary['max_order_value'], 2)]
                                    ] as $stat)
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-{{ $stat['color'] }} border border-{{ $stat['color'] }} border-dashed h-100">
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
                                                    {{ __('auth.' . $stat['label']) }}
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
                @if($dailyBreakdown->count() > 0)
                <div class="row mb-6">
                    {{-- Daily Sales Chart --}}
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.daily_sales_trend') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="dailySalesChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Order Type Distribution --}}
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.order_type_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="orderTypeChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Hourly Analysis Chart --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-clock fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.hourly_sales_analysis') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="hourlySalesChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Detailed Breakdown Tables --}}
                <div class="row">
                    {{-- Order Status Breakdown --}}
                    <div class="col-lg-6 mb-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-status fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.order_status_breakdown') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th>{{ __('accounting.status') }}</th>
                                                <th>{{ __('auth.order_count') }}</th>
                                                <th>{{ __('accounting.total_amount') }}</th>
                                                <th>{{ __('accounting.average_amount') }}</th>
                                                <th>{{ __('accounting.percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($statusBreakdown as $status)
                                            @php
                                                $percentage = $summary['total_orders'] > 0 ? ($status->count / $summary['total_orders']) * 100 : 0;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="badge badge-light-{{ getOrderStatusColor($status->status) }}">
                                                        {{ ucfirst($status->status) }}
                                                    </span>
                                                </td>
                                                <td>{{ $status->count }}</td>
                                                <td>${{ number_format($status->total_amount, 2) }}</td>
                                                <td>${{ number_format($status->average_amount, 2) }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100 me-3" style="height: 8px;">
                                                            <div class="progress-bar bg-{{ getOrderStatusColor($status->status) }}" 
                                                                style="width: {{ $percentage }}%"></div>
                                                        </div>
                                                        <span class="fw-bold text-gray-700 min-w-60px text-end">
                                                            {{ number_format($percentage, 1) }}%
                                                        </span>
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
                    
                    {{-- Order Type Breakdown --}}
                    <div class="col-lg-6 mb-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-bag fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.order_type_breakdown') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th>{{ __('accounting.type') }}</th>
                                                <th>{{ __('auth.order_count') }}</th>
                                                <th>{{ __('accounting.total_amount') }}</th>
                                                <th>{{ __('accounting.average_amount') }}</th>
                                                <th>{{ __('accounting.percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($typeBreakdown as $type)
                                            @php
                                                $percentage = $summary['total_orders'] > 0 ? ($type->count / $summary['total_orders']) * 100 : 0;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="badge badge-light-{{ getOrderTypeColor($type->type) }}">
                                                        {{ ucfirst($type->type) }}
                                                    </span>
                                                </td>
                                                <td>{{ $type->count }}</td>
                                                <td>${{ number_format($type->total_amount, 2) }}</td>
                                                <td>${{ number_format($type->average_amount, 2) }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100 me-3" style="height: 8px;">
                                                            <div class="progress-bar bg-{{ getOrderTypeColor($type->type) }}" 
                                                                style="width: {{ $percentage }}%"></div>
                                                        </div>
                                                        <span class="fw-bold text-gray-700 min-w-60px text-end">
                                                            {{ number_format($percentage, 1) }}%
                                                        </span>
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

                {{-- Daily Breakdown Table --}}
                @if($dailyBreakdown->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('auth.daily_breakdown') }}</h3>
                                    </div>
                                    @if($dailyBreakdown->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $dailyBreakdown->count() }} {{ __('accounting.days') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="orderSummaryTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('accounting.date') }}</th>
                                                <th>{{ __('accounting.day') }}</th>
                                                <th>{{ __('auth.order_count') }}</th>
                                                <th>{{ __('auth.daily_total') }}</th>
                                                <th>{{ __('auth.daily_average') }}</th>
                                                <th>{{ __('accounting.trend') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($dailyBreakdown as $day)
                                            @php
                                                $dayName = \Carbon\Carbon::parse($day->date)->format('l');
                                                $isWeekend = in_array($dayName, ['Saturday', 'Sunday']);
                                            @endphp
                                            <tr>
                                                <td class="ps-4 fw-semibold">{{ $day->date }}</td>
                                                <td>
                                                    <span class="badge badge-light-{{ $isWeekend ? 'danger' : 'primary' }}">
                                                        {{ $dayName }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ $day->order_count }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">${{ number_format($day->daily_total, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-gray-600">${{ number_format($day->daily_average, 2) }}</span>
                                                </td>
                                                <td>
                                                    @if($loop->index > 0)
                                                        @php
                                                            $prevDay = $dailyBreakdown[$loop->index - 1];
                                                            $trend = $day->daily_total - $prevDay->daily_total;
                                                            $trendPercent = $prevDay->daily_total > 0 ? ($trend / $prevDay->daily_total) * 100 : 0;
                                                        @endphp
                                                        <div class="d-flex align-items-center">
                                                            @if($trend > 0)
                                                                <i class="ki-duotone ki-arrow-up-right fs-2 text-success me-1"></i>
                                                                <span class="text-success fw-bold">+{{ number_format($trendPercent, 1) }}%</span>
                                                            @elseif($trend < 0)
                                                                <i class="ki-duotone ki-arrow-down-right fs-2 text-danger me-1"></i>
                                                                <span class="text-danger fw-bold">{{ number_format($trendPercent, 1) }}%</span>
                                                            @else
                                                                <i class="ki-duotone ki-minus fs-2 text-gray-400 me-1"></i>
                                                                <span class="text-gray-600">{{ number_format($trendPercent, 1) }}%</span>
                                                            @endif
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
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('auth.no_orders_found_for_period') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'location_id', 'department_id', 'order_type', 'order_status']))
                                        <a href="{{ route('reports.orders.summary') }}" class="btn btn-light-primary">
                                            <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                            {{ __('accounting.clear_filters_view_all') }}
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
@if($dailyBreakdown->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Daily Sales Chart
        const dailySalesData = {
            dates: @json($dailyBreakdown->pluck('date')),
            totals: @json($dailyBreakdown->pluck('daily_total')),
            counts: @json($dailyBreakdown->pluck('order_count'))
        };
        
        const dailySalesChart = new ApexCharts(document.querySelector("#dailySalesChart"), {
            series: [{
                name: 'Sales Amount',
                data: dailySalesData.totals,
                type: 'line'
            }, {
                name: 'Order Count',
                data: dailySalesData.counts,
                type: 'column'
            }],
            chart: {
                type: 'line',
                height: 350,
                toolbar: {
                    show: true
                }
            },
            stroke: {
                width: [3, 1],
                curve: 'smooth'
            },
            xaxis: {
                categories: dailySalesData.dates,
                labels: {
                    rotate: -45
                }
            },
            yaxis: [{
                title: {
                    text: 'Sales Amount ($)'
                },
                labels: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString();
                    }
                }
            }, {
                opposite: true,
                title: {
                    text: 'Order Count'
                }
            }],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val, { seriesIndex }) {
                        if (seriesIndex === 0) {
                            return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                        }
                        return val;
                    }
                }
            },
            colors: ['#3E97FF', '#50CD89']
        });
        dailySalesChart.render();
        
        // Order Type Pie Chart
        const orderTypeData = @json($typeBreakdown);
        const orderTypeChart = new ApexCharts(document.querySelector("#orderTypeChart"), {
            series: orderTypeData.map(item => item.total_amount),
            chart: {
                type: 'pie',
                height: 350
            },
            labels: orderTypeData.map(item => item.type.toUpperCase()),
            colors: ['#3E97FF', '#50CD89', '#7239EA', '#FFC700'],
            legend: {
                position: 'bottom'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                }
            }
        });
        orderTypeChart.render();
        
        // Hourly Sales Chart
        const hourlyData = @json($hourlyBreakdown);
        const hourlySalesChart = new ApexCharts(document.querySelector("#hourlySalesChart"), {
            series: [{
                name: 'Sales Amount',
                data: hourlyData.map(item => item.hourly_total)
            }],
            chart: {
                type: 'bar',
                height: 300
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '60%'
                }
            },
            xaxis: {
                categories: hourlyData.map(item => {
                    const hour = parseInt(item.hour);
                    const period = hour >= 12 ? 'PM' : 'AM';
                    const displayHour = hour % 12 || 12;
                    return `${displayHour} ${period}`;
                })
            },
            yaxis: {
                title: {
                    text: 'Sales Amount ($)'
                },
                labels: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString();
                    }
                }
            },
            colors: ['#3E97FF'],
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                }
            }
        });
        hourlySalesChart.render();
    });
    
</script>
@endif

<script>
    // Form validation
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        const startDate = new Date(document.querySelector('[name="start_date"]').value);
        const endDate = new Date(document.querySelector('[name="end_date"]').value);
        
        if (startDate > endDate) {
            e.preventDefault();
            alert('{{ __("auth.start_date_cannot_be_after_end_date") }}');
            return false;
        }
    });
</script>
@endpush

@endsection