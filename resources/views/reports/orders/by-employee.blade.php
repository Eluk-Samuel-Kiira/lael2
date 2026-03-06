{{-- resources/views/reports/orders/by-employee.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.employee_performance'))

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
                                {{ __('auth.employee_performance') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('auth.employee_performance') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($employeePerformance->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'employeePerformanceTable', filename: 'employee_performance_{{ date('Y_m_d') }}', sheetName: 'Employee Performance'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'employeePerformanceTable', filename: 'employee_performance_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.orders.by-employee') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-6">
                                        {{-- Date Range --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label required fw-semibold">{{ __('accounting.date_range') }}</label>
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <div class="input-group w-100">
                                                    <span class="input-group-text">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control" name="start_date" 
                                                        value="{{ $startDate }}" required
                                                        title="{{ __('auth.start_date') }}">
                                                </div>
                                                <span class="d-none d-sm-flex align-items-center text-gray-500 px-2">{{ __('accounting.to') }}</span>
                                                <span class="d-flex d-sm-none text-gray-500 text-center">{{ __('accounting.to') }}</span>
                                                <div class="input-group w-100">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control" name="end_date" 
                                                        value="{{ $endDate }}" required
                                                        title="{{ __('auth.end_date') }}">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        {{-- Location --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                            <div class="input-group w-100">
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
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('auth.department') }}</label>
                                            <div class="input-group w-100">
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
                                        
                                        {{-- Employee --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('accounting.employee') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-user fs-2"></i>
                                                </span>
                                                <select class="form-select" name="employee_id">
                                                    <option value="">{{ __('auth.all_employees') }}</option>
                                                    @foreach($employees as $employee)
                                                        <option value="{{ $employee->id }}" 
                                                                {{ $employeeId == $employee->id ? 'selected' : '' }}>
                                                            {{ $employee->first_name }} {{ $employee->last_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap">
                                        {{-- Min Sales --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('auth.min_sales') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-dollar fs-2"></i>
                                                </span>
                                                <input type="number" class="form-control" name="min_sales" 
                                                    value="{{ $minSales }}" 
                                                    placeholder="0.00"
                                                    step="0.01" min="0">
                                            </div>
                                        </div>
                                        
                                        {{-- Max Sales --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('auth.max_sales') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-dollar fs-2"></i>
                                                </span>
                                                <input type="number" class="form-control" name="max_sales" 
                                                    value="{{ $maxSales }}" 
                                                    placeholder="1000000.00"
                                                    step="0.01" min="0">
                                            </div>
                                        </div>
                                        
                                        {{-- Action Buttons --}}
                                        <div class="d-flex flex-column justify-content-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <button type="submit" class="btn btn-primary flex-grow-1" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                                                </button>
                                                <a href="{{ route('reports.orders.by-employee') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
                                                    <i class="ki-duotone ki-cross fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.clear_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.clear') }}</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Performance Summary --}}
                @if($employeePerformance->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.performance_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $totalSales = $employeePerformance->sum('total_sales');
                                        $totalOrders = $employeePerformance->sum('order_count');
                                        $avgSalesPerDay = $totalSales / max($daysInPeriod, 1);
                                        $topEmployee = $employeePerformance->first();
                                        $performanceRatings = $employeePerformance->groupBy('performance_rating');
                                    @endphp
                                    
                                    @foreach([
                                        ['key' => 'total_employees', 'color' => 'primary', 'icon' => 'ki-users', 'label' => 'total_employees', 'value' => $employeePerformance->count()],
                                        ['key' => 'total_sales', 'color' => 'success', 'icon' => 'ki-dollar', 'label' => 'total_sales', 'value' => '$' . number_format($totalSales, 2)],
                                        ['key' => 'total_orders', 'color' => 'info', 'icon' => 'ki-bag', 'label' => 'total_orders', 'value' => number_format($totalOrders)],
                                        ['key' => 'avg_sales_per_day', 'color' => 'warning', 'icon' => 'ki-chart-line', 'label' => 'average_sales_per_day', 'value' => '$' . number_format($avgSalesPerDay, 2)],
                                        ['key' => 'top_employee', 'color' => 'danger', 'icon' => 'ki-crown', 'label' => 'top_employee', 'value' => $topEmployee ? $topEmployee->first_name . ' ' . $topEmployee->last_name : 'N/A'],
                                        ['key' => 'top_sales', 'color' => 'secondary', 'icon' => 'ki-dollar-circle', 'label' => 'top_employee_sales', 'value' => $topEmployee ? '$' . number_format($topEmployee->total_sales, 2) : '$0.00']
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

                {{-- Performance Distribution --}}
                @if($employeePerformance->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.performance_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $ratingColors = [
                                            'Excellent' => 'success',
                                            'Good' => 'primary',
                                            'Average' => 'warning',
                                            'Needs Improvement' => 'danger'
                                        ];
                                        $ratingIcons = [
                                            'Excellent' => 'ki-medal',
                                            'Good' => 'ki-like',
                                            'Average' => 'ki-profile-circle',
                                            'Needs Improvement' => 'ki-profile-tick'
                                        ];
                                        
                                        // Initialize counts for all ratings
                                        $ratingCounts = [
                                            'Excellent' => 0,
                                            'Good' => 0,
                                            'Average' => 0,
                                            'Needs Improvement' => 0
                                        ];
                                        
                                        // Count actual ratings
                                        foreach($employeePerformance as $employee) {
                                            if (isset($ratingCounts[$employee->performance_rating])) {
                                                $ratingCounts[$employee->performance_rating]++;
                                            }
                                        }
                                    @endphp
                                    
                                    @foreach($ratingColors as $rating => $color)
                                    @php
                                        $count = $ratingCounts[$rating] ?? 0;
                                        $percentage = $employeePerformance->count() > 0 ? ($count / $employeePerformance->count()) * 100 : 0;
                                    @endphp
                                    <div class="col-md-6 col-lg-3">
                                        <div class="card card-flush bg-light-{{ $color }} border border-{{ $color }} border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone {{ $ratingIcons[$rating] ?? 'ki-user' }} fs-2tx text-{{ $color }}">
                                                        @for($i = 1; $i <= 2; $i++)
                                                        <span class="path{{ $i }}"></span>
                                                        @endfor
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">
                                                        {{ $count }}
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">
                                                    {{ $rating }}
                                                </div>
                                                <div class="mt-2">
                                                    <span class="badge badge-light-{{ $color }}">
                                                        {{ number_format($percentage, 1) }}%
                                                    </span>
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

                {{-- Top Performers Chart --}}
                @if($employeePerformance->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_performers') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="topPerformersChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Employee Performance Table --}}
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
                                        <h3 class="fw-bold m-0">{{ __('auth.employee_performance_report') }}</h3>
                                    </div>
                                    @if($employeePerformance->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $employeePerformance->count() }} {{ __('accounting.employees') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($employeePerformance->count() > 0)
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
                                                @foreach($employeePerformance as $index => $employee)
                                                @php
                                                    $totalSalesAll = $employeePerformance->sum('total_sales');
                                                    $percentage = $totalSalesAll > 0 ? ($employee->total_sales / $totalSalesAll) * 100 : 0;
                                                @endphp
                                                <tr>
                                                    <td class="ps-4">
                                                        <span class="fw-bold text-gray-800">{{ $index + 1 }}</span>
                                                        @if($index < 3)
                                                        <div class="mt-1">
                                                            <span class="badge badge-light-{{ $index == 0 ? 'danger' : ($index == 1 ? 'warning' : 'info') }}">
                                                                <i class="ki-duotone ki-{{ $index == 0 ? 'medal' : ($index == 1 ? 'ranking' : 'ranking-2') }} fs-4 me-1"></i>
                                                                {{ __('accounting.top') }} {{ $index + 1 }}
                                                            </span>
                                                        </div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-40px symbol-circle me-3">
                                                                <div class="symbol-label bg-light-{{ $employee->rating_color }}">
                                                                    <i class="ki-duotone ki-user fs-2"></i>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-start flex-column">
                                                                <span class="text-gray-800 fw-bold">{{ $employee->first_name }} {{ $employee->last_name }}</span>
                                                                <small class="text-muted">{{ $employee->email }}</small>
                                                                <span class="badge badge-light-{{ $employee->rating_color }} badge-sm mt-1">
                                                                    {{ $employee->performance_rating }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-primary">{{ $employee->order_count }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">${{ number_format($employee->total_sales, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-info">${{ number_format($employee->total_tax_collected, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-warning">${{ number_format($employee->total_discount_given, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-600">${{ number_format($employee->average_order_value, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-danger">${{ number_format($employee->largest_sale, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-secondary">${{ number_format($employee->smallest_sale, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-info">{{ $employee->unique_customers }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge badge-light-primary me-2">
                                                                {{ number_format($employee->orders_per_day, 1) }}/day
                                                            </span>
                                                            @if($employee->orders_per_day >= 10)
                                                            <i class="ki-duotone ki-arrow-up-right fs-2 text-success"></i>
                                                            @elseif($employee->orders_per_day < 3)
                                                            <i class="ki-duotone ki-arrow-down-right fs-2 text-danger"></i>
                                                            @else
                                                            <i class="ki-duotone ki-minus fs-2 text-gray-400"></i>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge badge-light-success me-2">
                                                                ${{ number_format($employee->sales_per_day, 0) }}/day
                                                            </span>
                                                            @if($employee->sales_per_day >= 1000)
                                                            <i class="ki-duotone ki-arrow-up-right fs-2 text-success"></i>
                                                            @elseif($employee->sales_per_day < 200)
                                                            <i class="ki-duotone ki-arrow-down-right fs-2 text-danger"></i>
                                                            @else
                                                            <i class="ki-duotone ki-minus fs-2 text-gray-400"></i>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-{{ $employee->rating_color }}">
                                                            {{ $employee->performance_rating }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($employee->last_sale_date)
                                                        <span class="text-gray-700">{{ \Carbon\Carbon::parse($employee->last_sale_date)->format('M d, Y H:i') }}</span>
                                                        @else
                                                        <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress w-100 me-3" style="height: 8px;">
                                                                <div class="progress-bar bg-{{ $employee->rating_color }}" 
                                                                    role="progressbar" 
                                                                    style="width: {{ min($percentage, 100) }}%;" 
                                                                    aria-valuenow="{{ $percentage }}" 
                                                                    aria-valuemin="0" 
                                                                    aria-valuemax="100">
                                                                </div>
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
                            @else
                                <div class="card-body">
                                    <div class="text-center py-10">
                                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('auth.no_employees_found_for_period') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'location_id', 'department_id', 'employee_id', 'min_sales', 'max_sales']))
                                        <a href="{{ route('reports.orders.by-employee') }}" class="btn btn-light-primary">
                                            <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                            {{ __('accounting.clear_filters_view_all') }}
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- Performance Leaderboard --}}
                @if($employeePerformance->count() > 0)
                <div class="row mt-6">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-crown fs-2 me-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_5_performers') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-success">
                                                <th class="ps-4">{{ __('accounting.rank') }}</th>
                                                <th>{{ __('accounting.employee') }}</th>
                                                <th>{{ __('accounting.total_sales') }}</th>
                                                <th>{{ __('auth.orders_per_day') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($employeePerformance->take(5) as $index => $employee)
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="symbol symbol-30px symbol-circle me-3">
                                                        <div class="symbol-label bg-light-success">
                                                            <span class="fw-bold text-success">{{ $index + 1 }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-gray-800 fw-bold">{{ $employee->first_name }} {{ $employee->last_name }}</span>
                                                </td>
                                                <td><span class="fw-bold text-success">${{ number_format($employee->total_sales, 2) }}</span></td>
                                                <td><span class="badge badge-light-primary">{{ number_format($employee->orders_per_day, 1) }}/day</span></td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.bottom_5_performers') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-danger">
                                                <th class="ps-4">{{ __('accounting.rank') }}</th>
                                                <th>{{ __('accounting.employee') }}</th>
                                                <th>{{ __('accounting.total_sales') }}</th>
                                                <th>{{ __('auth.orders_per_day') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($employeePerformance->sortBy('total_sales')->take(5) as $index => $employee)
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="symbol symbol-30px symbol-circle me-3">
                                                        <div class="symbol-label bg-light-danger">
                                                            <span class="fw-bold text-danger">{{ $employeePerformance->count() - $index }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-gray-800 fw-bold">{{ $employee->first_name }} {{ $employee->last_name }}</span>
                                                </td>
                                                <td><span class="fw-bold text-danger">${{ number_format($employee->total_sales, 2) }}</span></td>
                                                <td><span class="badge badge-light-primary">{{ number_format($employee->orders_per_day, 1) }}/day</span></td>
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

@push('scripts')
@if($employeePerformance->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Top Performers Chart
        const topPerformersData = @json($employeePerformance->take(10));
        const employeeNames = topPerformersData.map(emp => `${emp.first_name} ${emp.last_name[0]}.`);
        const employeeSales = topPerformersData.map(emp => parseFloat(emp.total_sales));
        const employeeOrders = topPerformersData.map(emp => parseFloat(emp.order_count));
        
        const topPerformersChart = new ApexCharts(document.querySelector("#topPerformersChart"), {
            series: [{
                name: 'Sales Amount',
                data: employeeSales,
                type: 'bar'
            }, {
                name: 'Order Count',
                data: employeeOrders,
                type: 'line'
            }],
            chart: {
                type: 'bar',
                height: 400,
                toolbar: {
                    show: true
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '60%'
                }
            },
            stroke: {
                width: [0, 3]
            },
            xaxis: {
                categories: employeeNames,
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
            colors: ['#3E97FF', '#50CD89'],
            tooltip: {
                y: {
                    formatter: function(val, { seriesIndex }) {
                        if (seriesIndex === 0) {
                            return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                        }
                        return val.toLocaleString();
                    }
                }
            }
        });
        topPerformersChart.render();
    });
</script>
@endif

<script>
    // Form validation
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        const startDate = new Date(document.querySelector('[name="start_date"]').value);
        const endDate = new Date(document.querySelector('[name="end_date"]').value);
        const minSales = parseFloat(document.querySelector('[name="min_sales"]').value) || 0;
        const maxSales = parseFloat(document.querySelector('[name="max_sales"]').value) || 0;
        
        if (startDate > endDate) {
            e.preventDefault();
            alert('{{ __("auth.start_date_cannot_be_after_end_date") }}');
            return false;
        }
        
        if (minSales > 0 && maxSales > 0 && minSales > maxSales) {
            e.preventDefault();
            alert('{{ __("auth.min_sales_cannot_exceed_max_sales") }}');
            return false;
        }
    });
</script>
@endpush

@endsection