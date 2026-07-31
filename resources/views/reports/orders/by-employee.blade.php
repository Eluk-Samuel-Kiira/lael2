{{-- resources/views/reports/orders/by-employee.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.employee_performance'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                
                {{-- ============================================================ --}}
                {{-- TOOLBAR --}}
                {{-- ============================================================ --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('auth.employee_performance') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('accounting.dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.employee_performance') }}</li>
                            </ul>
                        </div>
                        @if($employeePerformance->count() > 0)
                        <div class="dropdown">
                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0)" 
                                       onclick="exportTableToExcel('employeePerformanceTable', 'employee_performance')">
                                        <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                        {{ __('accounting.export_to_excel') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0)" 
                                       onclick="exportTableToCSV('employeePerformanceTable', 'employee_performance')">
                                        <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                        {{ __('accounting.export_to_csv') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- FILTER SECTION --}}
                {{-- ============================================================ --}}
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <form method="GET" action="{{ route('reports.orders.by-employee') }}" id="filterForm">
                            <div class="row g-3">
                                {{-- Date Range --}}
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('accounting.start_date') }}</label>
                                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('accounting.end_date') }}</label>
                                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                                </div>
                                
                                {{-- Location --}}
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                    <select class="form-select" name="location_id" data-control="select2">
                                        <option value="">{{ __('auth.all_locations') }}</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" {{ $locationId == $location->id ? 'selected' : '' }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                {{-- Department --}}
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">{{ __('auth.department') }}</label>
                                    <select class="form-select" name="department_id" data-control="select2">
                                        <option value="">{{ __('auth.all_departments') }}</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" {{ $departmentId == $department->id ? 'selected' : '' }}>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                {{-- Employee --}}
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">{{ __('accounting.employee') }}</label>
                                    <select class="form-select" name="employee_id" data-control="select2">
                                        <option value="">{{ __('auth.all_employees') }}</option>
                                        @foreach($employeesList as $emp)
                                            <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>
                                                {{ $emp->first_name }} {{ $emp->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                {{-- Min Sales --}}
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('auth.min_sales') }}</label>
                                    <input type="number" class="form-control" name="min_sales" value="{{ $minSales }}" placeholder="0.00" step="0.01">
                                </div>
                                
                                {{-- Max Sales --}}
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('auth.max_sales') }}</label>
                                    <input type="number" class="form-control" name="max_sales" value="{{ $maxSales }}" placeholder="1000000.00" step="0.01">
                                </div>
                                
                                {{-- Actions --}}
                                <div class="col-md-6 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('accounting.apply_filters') }}
                                    </button>
                                    <a href="{{ route('reports.orders.by-employee') }}" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('accounting.clear_filters') }}
                                    </a>
                                    <span class="text-muted ms-3 small">
                                        <i class="ki-duotone ki-information-4 fs-2"></i>
                                        {{ __('accounting.showing') }} {{ $employeePerformance->count() }} {{ __('accounting.employees') }}
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- NO DATA --}}
                {{-- ============================================================ --}}
                @if($employeePerformance->count() == 0)
                <div class="card">
                    <div class="card-body text-center py-10">
                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_employees_found_for_period') }}</p>
                    </div>
                </div>
                @else

                {{-- ============================================================ --}}
                {{-- SUMMARY CARDS --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    @php
                        $totalSales = $employeePerformance->sum('total_sales');
                        $totalOrders = $employeePerformance->sum('order_count');
                        $avgSalesPerDay = $daysInPeriod > 0 ? $totalSales / $daysInPeriod : 0;
                        $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
                        $topEmployee = $employeePerformance->first();
                    @endphp
                    
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-primary h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-primary">{{ $employeePerformance->count() }}</div>
                                <div class="text-muted">{{ __('auth.total_employees') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-success h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-success">{{ currency_symbol() }}{{ number_format($totalSales, 2) }}</div>
                                <div class="text-muted">{{ __('accounting.total_sales') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-info h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-info">{{ number_format($totalOrders) }}</div>
                                <div class="text-muted">{{ __('auth.total_orders') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-warning h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-warning">{{ currency_symbol() }}{{ number_format($avgSalesPerDay, 2) }}</div>
                                <div class="text-muted">{{ __('auth.avg_sales_per_day') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-danger h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-danger">{{ currency_symbol() }}{{ number_format($avgOrderValue, 2) }}</div>
                                <div class="text-muted">{{ __('auth.avg_order_value') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body text-center">
                                <div class="fs-6 fw-bold text-gray-800">{{ Str::limit($topEmployee->first_name . ' ' . $topEmployee->last_name, 15) }}</div>
                                <div class="text-muted">{{ currency_symbol() }}{{ number_format($topEmployee->total_sales, 2) }}</div>
                                <div class="text-muted small">{{ __('auth.top_employee') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- PERFORMANCE DISTRIBUTION --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    @php
                        $ratingColors = ['Excellent' => 'success', 'Good' => 'primary', 'Average' => 'warning', 'Needs Improvement' => 'danger'];
                        $ratingCounts = ['Excellent' => 0, 'Good' => 0, 'Average' => 0, 'Needs Improvement' => 0];
                        foreach($employeePerformance as $emp) {
                            if (isset($ratingCounts[$emp->performance_rating])) $ratingCounts[$emp->performance_rating]++;
                        }
                    @endphp
                    
                    @foreach($ratingColors as $rating => $color)
                    @php $count = $ratingCounts[$rating] ?? 0; $pct = $employeePerformance->count() > 0 ? ($count / $employeePerformance->count()) * 100 : 0; @endphp
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-{{ $color }} border border-{{ $color }} border-dashed">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-{{ $color }}">{{ $count }}</div>
                                <div class="text-gray-600">{{ $rating }}</div>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar bg-{{ $color }}" style="width: {{ $pct }}%;"></div>
                                </div>
                                <span class="badge badge-light-{{ $color }} mt-2">{{ number_format($pct, 1) }}%</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- ============================================================ --}}
                {{-- TOP PERFORMERS CHART --}}
                {{-- ============================================================ --}}
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('auth.top_performers') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div id="topPerformersChart" style="height: 400px;"></div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- EMPLOYEE PERFORMANCE TABLE --}}
                {{-- ============================================================ --}}
                <div class="card">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('auth.employee_performance_report') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">
                                {{ $employeePerformance->count() }} {{ __('accounting.employees') }}
                            </span>
                            <span class="badge badge-light-secondary ms-2 fs-7">
                                {{ $daysInPeriod }} {{ __('auth.days') }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="employeePerformanceTable">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                        <th class="min-w-50px ps-4">{{ __('accounting.rank') }}</th>
                                        <th class="min-w-200px">{{ __('accounting.employee') }}</th>
                                        <th class="min-w-100px">{{ __('auth.order_count') }}</th>
                                        <th class="min-w-120px">{{ __('accounting.total_sales') }}</th>
                                        <th class="min-w-120px">{{ __('accounting.total_tax') }}</th>
                                        <th class="min-w-120px">{{ __('accounting.total_discount') }}</th>
                                        <th class="min-w-120px">{{ __('accounting.average_amount') }}</th>
                                        <th class="min-w-120px">{{ __('auth.largest_sale') }}</th>
                                        <th class="min-w-120px">{{ __('auth.smallest_sale') }}</th>
                                        <th class="min-w-100px">{{ __('auth.unique_customers') }}</th>
                                        <th class="min-w-120px">{{ __('auth.orders_per_day') }}</th>
                                        <th class="min-w-120px">{{ __('auth.sales_per_day') }}</th>
                                        <th class="min-w-100px">{{ __('auth.performance_rating') }}</th>
                                        <th class="min-w-150px">{{ __('auth.last_sale_date') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.percentage') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($employees as $index => $employee)
                                    @php
                                        $empData = $employeePerformance->firstWhere('id', $employee->id);
                                        if (!$empData) continue;
                                        $totalSalesAll = $employeePerformance->sum('total_sales');
                                        $percentage = $totalSalesAll > 0 ? ($empData->total_sales / $totalSalesAll) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold">{{ $index + 1 }}</span>
                                            @if($index < 3)
                                            <span class="badge badge-light-{{ $index == 0 ? 'danger' : ($index == 1 ? 'warning' : 'info') }}">
                                                {{ $index == 0 ? '🥇' : ($index == 1 ? '🥈' : '🥉') }}
                                            </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-40px symbol-circle me-3">
                                                    <div class="symbol-label bg-light-{{ $empData->rating_color }}">
                                                        <i class="ki-duotone ki-user fs-2"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $empData->first_name }} {{ $empData->last_name }}</div>
                                                    <small class="text-muted">{{ $empData->email }}</small>
                                                    <span class="badge badge-light-{{ $empData->rating_color }} badge-sm mt-1 d-block">
                                                        {{ $empData->performance_rating }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge badge-light-primary">{{ $empData->order_count }}</span></td>
                                        <td class="fw-bold text-success">{{ currency_symbol() }}{{ number_format($empData->total_sales, 2) }}</td>
                                        <td class="text-info">{{ currency_symbol() }}{{ number_format($empData->total_tax_collected, 2) }}</td>
                                        <td class="text-warning">{{ currency_symbol() }}{{ number_format($empData->total_discount_given, 2) }}</td>
                                        <td>{{ currency_symbol() }}{{ number_format($empData->average_order_value, 2) }}</td>
                                        <td class="text-danger">{{ currency_symbol() }}{{ number_format($empData->largest_sale, 2) }}</td>
                                        <td class="text-secondary">{{ currency_symbol() }}{{ number_format($empData->smallest_sale, 2) }}</td>
                                        <td><span class="badge badge-light-info">{{ $empData->unique_customers }}</span></td>
                                        <td>
                                            <span class="badge badge-light-{{ $empData->orders_per_day >= 5 ? 'success' : ($empData->orders_per_day >= 2 ? 'warning' : 'danger') }}">
                                                {{ number_format($empData->orders_per_day, 1) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $empData->sales_per_day >= 1000 ? 'success' : ($empData->sales_per_day >= 500 ? 'warning' : 'danger') }}">
                                                {{ currency_symbol() }}{{ number_format($empData->sales_per_day, 0) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $empData->rating_color }}">
                                                {{ $empData->performance_rating }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($empData->last_sale_date)
                                                {{ optional($empData->last_sale_date)->format('M d, Y') ?? '-' }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress w-100 me-3" style="height: 8px;">
                                                    <div class="progress-bar bg-{{ $empData->rating_color }}" style="width: {{ min($percentage, 100) }}%;"></div>
                                                </div>
                                                <span class="fw-bold min-w-60px text-end">{{ number_format($percentage, 1) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <th colspan="3" class="text-end fw-bold">{{ __('accounting.totals') }}</th>
                                        <th class="fw-bold text-success">{{ currency_symbol() }}{{ number_format($employeePerformance->sum('total_sales'), 2) }}</th>
                                        <th class="fw-bold text-info">{{ currency_symbol() }}{{ number_format($employeePerformance->sum('total_tax_collected'), 2) }}</th>
                                        <th class="fw-bold text-warning">{{ currency_symbol() }}{{ number_format($employeePerformance->sum('total_discount_given'), 2) }}</th>
                                        <th colspan="9"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- ============================================================ --}}
                    {{-- PAGINATION --}}
                    {{-- ============================================================ --}}
                    @if($employees->hasPages())
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                {{ $employees->appends(request()->query())->links() }}
                            </div>
                            <div class="text-muted fs-7">
                                {{ __('accounting.showing') }} {{ $employees->firstItem() ?? 0 }} 
                                {{ __('accounting.to') }} {{ $employees->lastItem() ?? 0 }} 
                                {{ __('accounting.of') }} {{ $employees->total() }} 
                                {{ __('accounting.employees') }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- ============================================================ --}}
                {{-- TOP VS BOTTOM PERFORMERS --}}
                {{-- ============================================================ --}}
                <div class="row mt-6">
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header bg-light-success">
                                <h3 class="card-title">
                                    <i class="ki-duotone ki-crown fs-2 me-2 text-success"></i>
                                    {{ __('auth.top_5_performers') }}
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                                <th class="ps-4">{{ __('accounting.rank') }}</th>
                                                <th>{{ __('accounting.employee') }}</th>
                                                <th class="text-end">{{ __('accounting.total_sales') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($employeePerformance->take(5) as $index => $emp)
                                            <tr>
                                                <td class="ps-4 fw-bold">{{ $index + 1 }}</td>
                                                <td>{{ $emp->first_name }} {{ $emp->last_name }}</td>
                                                <td class="text-end fw-bold text-success">{{ currency_symbol() }}{{ number_format($emp->total_sales, 2) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header bg-light-danger">
                                <h3 class="card-title">
                                    <i class="ki-duotone ki-clock fs-2 me-2 text-danger"></i>
                                    {{ __('auth.bottom_5_performers') }}
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                                <th class="ps-4">{{ __('accounting.rank') }}</th>
                                                <th>{{ __('accounting.employee') }}</th>
                                                <th class="text-end">{{ __('accounting.total_sales') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($employeePerformance->sortBy('total_sales')->take(5) as $index => $emp)
                                            <tr>
                                                <td class="ps-4 fw-bold text-danger">{{ $employeePerformance->count() - $index }}</td>
                                                <td>{{ $emp->first_name }} {{ $emp->last_name }}</td>
                                                <td class="text-end text-danger">{{ currency_symbol() }}{{ number_format($emp->total_sales, 2) }}</td>
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
                
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- METADATA FOOTER --}}
{{-- ============================================================ --}}
<div class="mt-6 text-muted text-center fs-7">
    <hr>
    <p>
        <i class="ki-duotone ki-calendar-8 fs-2"></i>
        {{ __('auth.report_generated_on') }} {{ now()->format('F d, Y H:i:s') }} 
        | {{ __('accounting.period') }}: {{ $startDate ?? 'N/A' }} {{ __('accounting.to') }} {{ $endDate ?? 'N/A' }}
        @if(isset($locationId) && $locationId)
            | {{ __('auth.location') }}: {{ $locations->where('id', $locationId)->first()->name ?? 'N/A' }}
        @endif
        @if(isset($departmentId) && $departmentId)
            | {{ __('auth.department') }}: {{ $departments->where('id', $departmentId)->first()->name ?? 'N/A' }}
        @endif
        | {{ $employeePerformance->count() ?? 0 }} {{ __('auth.employees') }}
        | {{ $daysInPeriod ?? 0 }} {{ __('auth.days_analyzed') }}
        @if(isset($totalSales))
            | {{ __('accounting.total_sales') }}: {{ currency_symbol() }}{{ number_format($totalSales, 2) }}
        @endif
    </p>
</div>

{{-- ============================================================ --}}
{{-- SCRIPTS --}}
{{-- ============================================================ --}}
@push('scripts')
@if($employeePerformance->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const data = @json($employeePerformance->take(10));
    const names = data.map(e => e.first_name + ' ' + e.last_name.substring(0, 1) + '.');
    const sales = data.map(e => parseFloat(e.total_sales));
    const orders = data.map(e => parseFloat(e.order_count));
    
    new ApexCharts(document.querySelector("#topPerformersChart"), {
        series: [
            { name: 'Sales', data: sales, type: 'bar' },
            { name: 'Orders', data: orders, type: 'line' }
        ],
        chart: { type: 'bar', height: 400, toolbar: { show: true } },
        plotOptions: { bar: { horizontal: false, columnWidth: '60%' } },
        stroke: { width: [0, 3] },
        xaxis: { categories: names, labels: { rotate: -45 } },
        yaxis: [
            { title: { text: 'Sales ($)' }, labels: { formatter: v => '$' + v.toLocaleString() } },
            { opposite: true, title: { text: 'Orders' } }
        ],
        colors: ['#3E97FF', '#50CD89'],
        tooltip: {
            y: {
                formatter: function(val, { seriesIndex }) {
                    return seriesIndex === 0 ? '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2}) : val.toLocaleString();
                }
            }
        }
    }).render();
});
</script>
@endif

<script>
function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return alert('Table not found!');
    try {
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, XLSX.utils.table_to_sheet(table), 'Employees');
        XLSX.writeFile(wb, filename + '.xlsx');
    } catch(e) { alert('Error: ' + e.message); }
}

function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return alert('Table not found!');
    try {
        const rows = table.querySelectorAll('tr');
        let csv = [];
        rows.forEach(row => {
            const cols = row.querySelectorAll('td, th');
            csv.push(Array.from(cols).map(c => c.innerText.trim()).join(','));
        });
        const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename + '.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    } catch(e) { alert('Error: ' + e.message); }
}

document.getElementById('filterForm')?.addEventListener('submit', function(e) {
    const start = new Date(document.querySelector('[name="start_date"]').value);
    const end = new Date(document.querySelector('[name="end_date"]').value);
    if (start > end) { e.preventDefault(); alert('{{ __("auth.start_date_cannot_be_after_end_date") }}'); }
});
</script>
@endpush

@endsection