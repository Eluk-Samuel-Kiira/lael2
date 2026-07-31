{{-- resources/views/reports/orders/by-customer.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.sales_by_customer'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                
                {{-- ============================================================ --}}
                {{-- TOOLBAR SECTION --}}
                {{-- ============================================================ --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('auth.sales_by_customer') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('auth.sales_by_customer') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if(isset($customerSales) && $customerSales->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportTableToExcel('customerSalesTable', 'customer_sales')">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportTableToCSV('customerSalesTable', 'customer_sales')">
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

                {{-- ============================================================ --}}
                {{-- FILTER SECTION --}}
                {{-- ============================================================ --}}
                <div class="card mb-6">
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
                        <form method="GET" action="{{ route('reports.orders.by-customer') }}" id="filterForm">
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
                                        <select class="form-select" name="location_id" data-control="select2">
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
                                        <select class="form-select" name="department_id" data-control="select2">
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
                                
                                {{-- Customer Type --}}
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold">{{ __('auth.customer_type') }}</label>
                                    <div class="input-group w-100">
                                        <select class="form-select" name="customer_type">
                                            <option value="all" {{ $customerType == 'all' ? 'selected' : '' }}>{{ __('auth.all_customers') }}</option>
                                            <option value="registered" {{ $customerType == 'registered' ? 'selected' : '' }}>{{ __('auth.registered') }}</option>
                                            <option value="guest" {{ $customerType == 'guest' ? 'selected' : '' }}>{{ __('auth.guest') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap">
                                {{-- Customer Segment --}}
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold">{{ __('auth.customer_segment') }}</label>
                                    <div class="input-group w-100">
                                        <select class="form-select" name="segment_filter">
                                            <option value="">{{ __('auth.all_segments') }}</option>
                                            <option value="New" {{ $segmentFilter == 'New' ? 'selected' : '' }}>{{ __('auth.new') }}</option>
                                            <option value="Returning" {{ $segmentFilter == 'Returning' ? 'selected' : '' }}>{{ __('auth.returning') }}</option>
                                            <option value="Regular" {{ $segmentFilter == 'Regular' ? 'selected' : '' }}>{{ __('auth.regular') }}</option>
                                            <option value="VIP" {{ $segmentFilter == 'VIP' ? 'selected' : '' }}>{{ __('auth.vip') }}</option>
                                        </select>
                                    </div>
                                </div>
                                
                                {{-- Min Spent --}}
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold">{{ __('auth.min_spent') }}</label>
                                    <div class="input-group w-100">
                                        <span class="input-group-text">
                                            <i class="ki-duotone ki-dollar fs-2"></i>
                                        </span>
                                        <input type="number" step="0.01" class="form-control" name="min_spent" 
                                            value="{{ $minSpent }}" placeholder="0.00">
                                    </div>
                                </div>
                                
                                {{-- Max Spent --}}
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold">{{ __('auth.max_spent') }}</label>
                                    <div class="input-group w-100">
                                        <span class="input-group-text">
                                            <i class="ki-duotone ki-dollar fs-2"></i>
                                        </span>
                                        <input type="number" step="0.01" class="form-control" name="max_spent" 
                                            value="{{ $maxSpent }}" placeholder="999999.00">
                                    </div>
                                </div>
                                
                                {{-- Min Orders --}}
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold">{{ __('auth.min_orders') }}</label>
                                    <div class="input-group w-100">
                                        <span class="input-group-text">
                                            <i class="ki-duotone ki-basket fs-2"></i>
                                        </span>
                                        <input type="number" class="form-control" name="min_orders" 
                                            value="{{ $minOrders }}" placeholder="0">
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
                                        <a href="{{ route('reports.orders.by-customer') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
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

                {{-- ============================================================ --}}
                {{-- NO DATA MESSAGE --}}
                {{-- ============================================================ --}}
                @if(!isset($summary) || $summary->total_customers == 0)
                <div class="card">
                    <div class="card-body text-center py-10">
                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_customers_found_for_period') }}</p>
                        @if(request()->hasAny(['start_date', 'end_date', 'location_id', 'department_id', 'min_spent', 'max_spent', 'min_orders', 'max_orders']))
                        <a href="{{ route('reports.orders.by-customer') }}" class="btn btn-light-primary">
                            <i class="ki-duotone ki-cross fs-2 me-2"></i>
                            {{ __('accounting.clear_filters_view_all') }}
                        </a>
                        @endif
                    </div>
                </div>
                @else

                {{-- ============================================================ --}}
                {{-- KPI CARDS - Key Performance Indicators --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    {{-- Total Customers --}}
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-primary h-100">
                            <div class="card-body text-center">
                                <div class="symbol symbol-50px bg-primary d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                                    <i class="ki-duotone ki-users fs-2x text-white"></i>
                                </div>
                                <div class="fs-1 fw-bold text-primary">{{ number_format($summary->total_customers) }}</div>
                                <div class="text-muted">{{ __('auth.total_customers') }}</div>
                                <small class="text-muted">
                                    {{ $summary->registered_customers }} {{ __('auth.registered') }} | {{ $summary->guest_customers }} {{ __('auth.guest') }}
                                </small>
                            </div>
                        </div>
                    </div>

                    {{-- Total Revenue --}}
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-success h-100">
                            <div class="card-body text-center">
                                <div class="symbol symbol-50px bg-success d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                                    <i class="ki-duotone ki-dollar fs-2x text-white"></i>
                                </div>
                                <div class="fs-1 fw-bold text-success">{{ currency_symbol() }}{{ number_format($summary->total_revenue, 2) }}</div>
                                <div class="text-muted">{{ __('auth.total_sales') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Total Orders --}}
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-info h-100">
                            <div class="card-body text-center">
                                <div class="symbol symbol-50px bg-info d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                                    <i class="ki-duotone ki-basket fs-2x text-white"></i>
                                </div>
                                <div class="fs-1 fw-bold text-info">{{ number_format($summary->total_orders) }}</div>
                                <div class="text-muted">{{ __('auth.total_orders') }}</div>
                                <small class="text-muted">{{ number_format($summary->average_orders_per_customer, 1) }} avg/customer</small>
                            </div>
                        </div>
                    </div>

                    {{-- Average Per Customer --}}
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-warning h-100">
                            <div class="card-body text-center">
                                <div class="symbol symbol-50px bg-warning d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                                    <i class="ki-duotone ki-chart-line fs-2x text-white"></i>
                                </div>
                                <div class="fs-1 fw-bold text-warning">{{ currency_symbol() }}{{ number_format($summary->average_per_customer, 2) }}</div>
                                <div class="text-muted">{{ __('auth.average_per_customer') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Average Order Value --}}
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-danger h-100">
                            <div class="card-body text-center">
                                <div class="symbol symbol-50px bg-danger d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                                    <i class="ki-duotone ki-cart fs-2x text-white"></i>
                                </div>
                                <div class="fs-1 fw-bold text-danger">{{ currency_symbol() }}{{ number_format($summary->average_order_value, 2) }}</div>
                                <div class="text-muted">{{ __('auth.average_order_value') }}</div>
                            </div>
                        </div>
                    </div>

                   {{-- Tax & Discount --}}
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body text-center">
                                <div>
                                    <div class="fs-5 fw-bold text-info">{{ currency_symbol() }}{{ number_format($summary->total_tax, 2) }}</div>
                                    <div class="text-muted fs-8">{{ __('auth.total_tax') }}</div>
                                </div>
                                <hr class="my-2">
                                <div>
                                    <div class="fs-5 fw-bold text-danger">{{ currency_symbol() }}{{ number_format($summary->total_discount, 2) }}</div>
                                    <div class="text-muted fs-8">{{ __('auth.total_discount') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- CUSTOMER SEGMENT BREAKDOWN --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-3">
                        <div class="card bg-light-info border border-info">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-1 fw-bold text-info">{{ number_format($customerSegments->new ?? 0) }}</div>
                                    <div class="text-muted">{{ __('auth.new_customers') }} <small class="text-muted">(1 {{ __('auth.order') }})</small></div>
                                </div>
                                <div class="badge badge-light-info fs-7">{{ $summary->total_customers > 0 ? number_format(($customerSegments->new / $summary->total_customers) * 100, 1) : 0 }}%</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-primary border border-primary">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-1 fw-bold text-primary">{{ number_format($customerSegments->returning ?? 0) }}</div>
                                    <div class="text-muted">{{ __('auth.returning_customers') }} <small class="text-muted">(2-5 {{ __('auth.orders') }})</small></div>
                                </div>
                                <div class="badge badge-light-primary fs-7">{{ $summary->total_customers > 0 ? number_format(($customerSegments->returning / $summary->total_customers) * 100, 1) : 0 }}%</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-success border border-success">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-1 fw-bold text-success">{{ number_format($customerSegments->regular ?? 0) }}</div>
                                    <div class="text-muted">{{ __('auth.regular_customers') }} <small class="text-muted">(6-20 {{ __('auth.orders') }})</small></div>
                                </div>
                                <div class="badge badge-light-success fs-7">{{ $summary->total_customers > 0 ? number_format(($customerSegments->regular / $summary->total_customers) * 100, 1) : 0 }}%</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-warning border border-warning">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-1 fw-bold text-warning">{{ number_format($customerSegments->vip ?? 0) }}</div>
                                    <div class="text-muted">{{ __('auth.vip_customers') }} <small class="text-muted">(20+ {{ __('auth.orders') }})</small></div>
                                </div>
                                <div class="badge badge-light-warning fs-7">{{ $summary->total_customers > 0 ? number_format(($customerSegments->vip / $summary->total_customers) * 100, 1) : 0 }}%</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- TOP CUSTOMERS CHART --}}
                {{-- ============================================================ --}}
                @if(isset($topCustomers) && $topCustomers->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_customers') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="topCustomersChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- CUSTOMER SALES TABLE --}}
                {{-- ============================================================ --}}
                <div class="card">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center justify-content-between w-100">
                            <div class="d-flex align-items-center">
                                <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <h3 class="fw-bold m-0">{{ __('auth.customer_sales_report') }}</h3>
                            </div>
                            @if($customerSales->count() > 0)
                            <span class="badge badge-light-primary fs-7">
                                {{ __('accounting.showing') }} {{ $customerSales->count() }} {{ __('auth.customers') }}
                            </span>
                            @endif
                        </div>
                    </div>
                    
                    @if($customerSales->count() > 0)
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="customerSalesTable">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                        <th class="min-w-50px ps-4">{{ __('accounting.rank') }}</th>
                                        <th class="min-w-200px">{{ __('accounting.customer') }}</th>
                                        <th class="min-w-100px">{{ __('auth.segment') }}</th>
                                        <th class="min-w-100px">{{ __('auth.order_count') }}</th>
                                        <th class="min-w-120px">{{ __('auth.total_spent') }}</th>
                                        <th class="min-w-120px">{{ __('accounting.average_amount') }}</th>
                                        <th class="min-w-120px">{{ __('auth.min_order') }}</th>
                                        <th class="min-w-120px">{{ __('auth.max_order') }}</th>
                                        <th class="min-w-120px">{{ __('auth.total_tax') }}</th>
                                        <th class="min-w-120px">{{ __('auth.total_discount') }}</th>
                                        <th class="min-w-150px">{{ __('auth.last_order') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.percentage') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customerSalesPaginated ?? $customerSales as $index => $customer)
                                    @php
                                        $rank = $index + 1;
                                        $rowClass = '';
                                        if ($rank <= 3) $rowClass = 'table-success';
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        <td class="ps-4">
                                            <span class="fw-bold text-gray-800">{{ $rank }}</span>
                                            @if($rank < 3)
                                            <div class="mt-1">
                                                <span class="badge badge-light-{{ $rank == 1 ? 'danger' : ($rank == 2 ? 'warning' : 'info') }}">
                                                    <i class="ki-duotone ki-{{ $rank == 1 ? 'medal' : ($rank == 2 ? 'ranking' : 'ranking-2') }} fs-4 me-1"></i>
                                                    {{ __('accounting.top') }} {{ $rank }}
                                                </span>
                                            </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-40px symbol-circle me-3">
                                                    <div class="symbol-label bg-light-{{ $customer->segment_color ?? 'secondary' }}">
                                                        <i class="ki-duotone ki-user fs-2"></i>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-start flex-column">
                                                    <span class="text-gray-800 fw-bold">{{ $customer->full_name ?? 'Guest' }}</span>
                                                    @if(!$customer->is_guest)
                                                    <span class="text-muted">{{ $customer->email ?? '' }}</span>
                                                    <span class="badge badge-light-success badge-sm mt-1">{{ __('auth.registered') }}</span>
                                                    @else
                                                    <span class="badge badge-light-secondary badge-sm mt-1">{{ __('auth.guest') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $customer->segment_color ?? 'secondary' }}">
                                                {{ $customer->segment_icon ?? '' }} {{ $customer->segment ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-primary">{{ number_format($customer->order_count) }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success">{{ currency_symbol() }}{{ number_format($customer->total_spent, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-gray-600">{{ currency_symbol() }}{{ number_format($customer->average_order_value, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-danger">{{ currency_symbol() }}{{ number_format($customer->min_order_value, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-success">{{ currency_symbol() }}{{ number_format($customer->max_order_value, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-info">{{ currency_symbol() }}{{ number_format($customer->total_tax, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-warning">{{ currency_symbol() }}{{ number_format($customer->total_discount, 2) }}</span>
                                        </td>
                                        <td>
                                            @if($customer->last_order_date)
                                            <span class="text-gray-700">{{ \Carbon\Carbon::parse($customer->last_order_date)->format('M d, Y') }}</span>
                                            <br>
                                            <small class="text-muted">
                                                {{ currency_symbol() }}{{ number_format($customer->last_order_amount ?? 0, 2) }}
                                            </small>
                                            @else
                                            <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress w-100 me-3" style="height: 8px;">
                                                    <div class="progress-bar bg-{{ $customer->segment_color ?? 'primary' }}" 
                                                        role="progressbar" 
                                                        style="width: {{ min($customer->percentage, 100) }}%;" 
                                                        aria-valuenow="{{ $customer->percentage }}" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <span class="fw-bold text-gray-700 min-w-60px text-end">
                                                    {{ number_format($customer->percentage, 1) }}%
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    {{-- ✅ PAGINATION --}}
                    @if(isset($customerSalesPaginated) && $customerSalesPaginated->hasPages())
                    <div class="card-footer">
                        @include('partials.pagination', [
                            'paginator' => $customerSalesPaginated,
                            'pageName' => 'page',
                            'perPageName' => 'per_page',
                            'showPerPage' => true
                        ])
                    </div>
                    @endif
                    
                    @else
                    <div class="card-body">
                        <div class="text-center py-10">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('auth.no_customers_found_for_period') }}</p>
                            @if(request()->hasAny(['start_date', 'end_date', 'location_id', 'department_id']))
                            <a href="{{ route('reports.orders.by-customer') }}" class="btn btn-light-primary">
                                <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                {{ __('accounting.clear_filters_view_all') }}
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                {{-- ============================================================ --}}
                {{-- TOP VS BOTTOM CUSTOMERS --}}
                {{-- ============================================================ --}}
                @if($customerSales->count() > 0)
                <div class="row mt-6">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-crown fs-2 me-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_5_customers') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-success">
                                                <th class="ps-4">{{ __('accounting.rank') }}</th>
                                                <th>{{ __('accounting.customer') }}</th>
                                                <th>{{ __('auth.total_spent') }}</th>
                                                <th>{{ __('auth.order_count') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($customerSales->take(5) as $index => $customer)
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold text-gray-800">{{ $index + 1 }}</span>
                                                </td>
                                                <td>{{ $customer->full_name ?? 'Guest' }}</td>
                                                <td><span class="fw-bold text-success">{{ currency_symbol() }}{{ number_format($customer->total_spent, 2) }}</span></td>
                                                <td><span class="badge badge-light-primary">{{ number_format($customer->order_count) }}</span></td>
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
                                    <i class="ki-duotone ki-clock fs-2 me-2 text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.bottom_5_customers') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-danger">
                                                <th class="ps-4">{{ __('accounting.rank') }}</th>
                                                <th>{{ __('accounting.customer') }}</th>
                                                <th>{{ __('auth.total_spent') }}</th>
                                                <th>{{ __('auth.order_count') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($customerSales->slice(-5)->reverse() as $index => $customer)
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold text-gray-800">{{ $customerSales->count() - $index }}</span>
                                                </td>
                                                <td>{{ $customer->full_name ?? 'Guest' }}</td>
                                                <td><span class="fw-bold text-danger">{{ currency_symbol() }}{{ number_format($customer->total_spent, 2) }}</span></td>
                                                <td><span class="badge badge-light-primary">{{ number_format($customer->order_count) }}</span></td>
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

                {{-- ============================================================ --}}
                {{-- METADATA FOOTER --}}
                {{-- ============================================================ --}}
                <div class="mt-6 text-muted text-center fs-7">
                    <hr>
                    <p>
                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                        {{ __('accounting.report_generated_on') }} {{ now()->format('F d, Y H:i:s') }} 
                        | {{ __('accounting.period') }}: {{ $startDate ?? 'N/A' }} {{ __('accounting.to') }} {{ $endDate ?? 'N/A' }}
                        @if(isset($locationId) && $locationId)
                            | {{ __('auth.location') }}: {{ $locations->where('id', $locationId)->first()->name ?? 'N/A' }}
                        @endif
                        @if(isset($departmentId) && $departmentId)
                            | {{ __('auth.department') }}: {{ $departments->where('id', $departmentId)->first()->name ?? 'N/A' }}
                        @endif
                        | {{ $summary->total_customers ?? 0 }} {{ __('auth.customers_analyzed') }}
                    </p>
                </div>

                @endif {{-- End of data check --}}
                
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- JAVASCRIPT - Charts & Export --}}
{{-- ============================================================ --}}
@push('scripts')
@if(isset($topCustomers) && $topCustomers->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Top Customers Chart
    const topCustomersData = @json($topCustomers);
    const customerNames = topCustomersData.map(customer => {
        let name = customer.full_name || 'Guest';
        return name.length > 15 ? name.substring(0, 15) + '...' : name;
    });
    const customerRevenue = topCustomersData.map(customer => parseFloat(customer.total_spent || 0));
    const customerOrders = topCustomersData.map(customer => parseFloat(customer.order_count || 0));
    
    const options = {
        series: [{
            name: '{{ __("auth.revenue") }}',
            data: customerRevenue,
            type: 'bar'
        }, {
            name: '{{ __("auth.orders") }}',
            data: customerOrders,
            type: 'line'
        }],
        chart: {
            type: 'bar',
            height: 400,
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    selection: true,
                    zoom: true,
                    zoomin: true,
                    zoomout: true,
                    pan: true,
                    reset: true
                }
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                borderRadius: 4,
                borderRadiusApplication: 'end'
            }
        },
        stroke: {
            width: [0, 3],
            curve: 'smooth'
        },
        xaxis: {
            categories: customerNames,
            labels: {
                rotate: -45,
                style: {
                    fontSize: '11px'
                }
            }
        },
        yaxis: [{
            title: {
                text: '{{ __("auth.revenue") }} ({{ currency_symbol() }})',
                style: {
                    fontSize: '12px'
                }
            },
            labels: {
                formatter: function(val) {
                    return '{{ currency_symbol() }}' + val.toLocaleString();
                }
            }
        }, {
            opposite: true,
            title: {
                text: '{{ __("auth.orders") }}',
                style: {
                    fontSize: '12px'
                }
            }
        }],
        colors: ['#3E97FF', '#50CD89'],
        fill: {
            opacity: [0.85, 1]
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function(val, { seriesIndex }) {
                    if (seriesIndex === 0) {
                        return '{{ currency_symbol() }}' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                    return val.toLocaleString();
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'center'
        },
        grid: {
            borderColor: '#f1f1f1',
            row: {
                colors: ['#f8f9fa', 'transparent'],
                opacity: 0.5
            }
        },
        dataLabels: {
            enabled: false
        }
    };
    
    const chart = new ApexCharts(document.querySelector("#topCustomersChart"), options);
    chart.render();
});
</script>
@endif

<script>
// ============================================================
// Export Functions
// ============================================================

function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert('{{ __("accounting.table_not_found") }}');
        return;
    }
    
    try {
        if (typeof XLSX === 'undefined') {
            alert('{{ __("accounting.export_library_missing") }}');
            return;
        }
        
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table);
        XLSX.utils.book_append_sheet(wb, ws, 'Customer Sales');
        XLSX.writeFile(wb, filename + '.xlsx');
    } catch (e) {
        console.error('Export error:', e);
        alert('{{ __("accounting.export_error") }}: ' + e.message);
    }
}

function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert('{{ __("accounting.table_not_found") }}');
        return;
    }
    
    try {
        const rows = table.querySelectorAll('tr');
        let csv = [];
        rows.forEach(row => {
            const cols = row.querySelectorAll('td, th');
            const rowData = Array.from(cols).map(col => {
                let text = col.innerText.trim();
                text = text.replace(/[🥇🥈🥉🌟🔄⭐👑👋]/g, '').trim();
                if (text.includes(',')) {
                    text = '"' + text + '"';
                }
                return text;
            });
            csv.push(rowData.join(','));
        });
        
        const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    } catch (e) {
        console.error('Export error:', e);
        alert('{{ __("accounting.export_error") }}: ' + e.message);
    }
}

// ============================================================
// Form Validation
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            const startDate = new Date(document.querySelector('[name="start_date"]').value);
            const endDate = new Date(document.querySelector('[name="end_date"]').value);
            const minSpent = parseFloat(document.querySelector('[name="min_spent"]').value) || 0;
            const maxSpent = parseFloat(document.querySelector('[name="max_spent"]').value) || 0;
            const minOrders = parseInt(document.querySelector('[name="min_orders"]').value) || 0;
            
            if (startDate > endDate) {
                e.preventDefault();
                alert('{{ __("auth.start_date_cannot_be_after_end_date") }}');
                return false;
            }
            
            if (minSpent > 0 && maxSpent > 0 && minSpent > maxSpent) {
                e.preventDefault();
                alert('{{ __("auth.min_spent_cannot_exceed_max_spent") }}');
                return false;
            }
            
            if (minOrders < 0) {
                e.preventDefault();
                alert('{{ __("auth.min_orders_cannot_be_negative") }}');
                return false;
            }
            
            return true;
        });
    }
    
    // Print styles
    const style = document.createElement('style');
    style.innerHTML = `
        @media print {
            .app-toolbar .dropdown,
            #filterForm,
            .btn,
            .kt_app_toolbar .d-flex.gap-2,
            .card-header:has(.card-title:contains("Filter Report")) {
                display: none !important;
            }
            .card {
                border: 1px solid #ddd !important;
                break-inside: avoid;
            }
            .table {
                font-size: 10px !important;
            }
            .badge {
                border: 1px solid #ddd !important;
            }
            .progress {
                display: none !important;
            }
            .symbol {
                display: none !important;
            }
        }
    `;
    document.head.appendChild(style);
});
</script>
@endpush

@endsection