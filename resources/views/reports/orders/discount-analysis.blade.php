{{-- resources/views/reports/orders/discount-analysis.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.discount_analysis_report'))

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
                                {{ __('auth.discount_analysis_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('auth.discount_analysis_report') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if($discountedOrders->count() > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'discountedOrdersTable', filename: 'discount_analysis_{{ date('Y_m_d') }}', sheetName: 'Discount Analysis'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'discountedOrdersTable', filename: 'discount_analysis_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ request()->url() }}" id="filterForm">
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
                                        
                                        {{-- Action Buttons --}}
                                        <div class="col-md-6 col-lg-8 d-flex align-items-end justify-content-end">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-2"></i>
                                                    {{ __('accounting.apply_filters') }}
                                                </button>
                                                <a href="{{ request()->url() }}" class="btn btn-light btn-active-light-primary">
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

                {{-- Summary Cards --}}
                <div class="row mb-6">
                    @php
                        $discountEffectiveness = $ordersWithoutDiscount && $ordersWithoutDiscount->order_count > 0 ? 
                            (($ordersWithDiscount->order_count ?? 0) / $ordersWithoutDiscount->order_count) * 100 : 0;
                    @endphp
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-percentage fs-2tx text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        {{ $discountSummary['total_discounted_orders'] }}
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.discounted_orders') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-primary">
                                        {{ number_format($discountSummary['discount_rate'], 2) }}% {{ __('auth.discount_rate') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-dollar-circle fs-2tx text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        ${{ number_format($discountSummary['total_discount_amount'], 2) }}
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.total_discount_given') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-success">
                                        ${{ number_format($discountSummary['average_discount_per_order'], 2) }} {{ __('auth.average') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-chart-line fs-2tx text-info">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        {{ number_format($discountEffectiveness, 1) }}%
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.discount_effectiveness') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-info">
                                        {{ $ordersWithDiscount->order_count ?? 0 }} / {{ $ordersWithoutDiscount->order_count ?? 0 }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-user-tick fs-2tx text-warning">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        {{ $discountByEmployee->count() }}
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.employees_giving_discounts') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-warning">
                                        @if($discountByEmployee->count() > 0)
                                            {{ $discountByEmployee->first()->first_name }}: ${{ number_format($discountByEmployee->first()->total_discount_given, 2) }}
                                        @else
                                            {{ __('auth.none') }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Discount Effectiveness Comparison --}}
                @if($ordersWithDiscount && $ordersWithoutDiscount)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.discount_effectiveness_comparison') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    <div class="col-md-4">
                                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone ki-cart-tick fs-2tx text-success">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">
                                                        {{ $ordersWithDiscount->order_count ?? 0 }}
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">
                                                    {{ __('auth.orders_with_discount') }}
                                                </div>
                                                <div class="mt-2">
                                                    <span class="badge badge-light-success">
                                                        ${{ number_format($ordersWithDiscount->average_order_value ?? 0, 2) }} {{ __('auth.avg_order_value') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone ki-cart fs-2tx text-primary">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">
                                                        {{ $ordersWithoutDiscount->order_count ?? 0 }}
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">
                                                    {{ __('auth.orders_without_discount') }}
                                                </div>
                                                <div class="mt-2">
                                                    <span class="badge badge-light-primary">
                                                        ${{ number_format($ordersWithoutDiscount->average_order_value ?? 0, 2) }} {{ __('auth.avg_order_value') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone ki-arrow-up-right fs-2tx text-info">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    @php
                                                        $avgDiff = ($ordersWithDiscount->average_order_value ?? 0) - ($ordersWithoutDiscount->average_order_value ?? 0);
                                                        $percentageDiff = $ordersWithoutDiscount->average_order_value > 0 ? 
                                                            (($ordersWithDiscount->average_order_value - $ordersWithoutDiscount->average_order_value) / $ordersWithoutDiscount->average_order_value) * 100 : 0;
                                                    @endphp
                                                    <span class="fs-1 fw-bold text-gray-800 {{ $percentageDiff >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ number_format($percentageDiff, 1) }}%
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">
                                                    {{ __('auth.value_difference') }}
                                                </div>
                                                <div class="mt-2">
                                                    <span class="badge badge-light-info">
                                                        ${{ number_format($avgDiff, 2) }} {{ __('auth.per_order') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Discount Patterns by Time --}}
                @if($discountPatterns->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.discount_patterns_by_time') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="discountPatternsChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Discount by Employee --}}
                @if($discountByEmployee->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-user-square fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.discount_by_employee') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('auth.employee') }}</th>
                                                <th>{{ __('auth.discounted_orders') }}</th>
                                                <th>{{ __('auth.total_discount_given') }}</th>
                                                <th>{{ __('auth.average_discount') }}</th>
                                                <th>{{ __('auth.max_discount') }}</th>
                                                <th>{{ __('auth.discount_per_order') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($discountByEmployee as $employee)
                                            @php
                                                $discountPerOrder = $employee->order_count > 0 ? $employee->total_discount_given / $employee->order_count : 0;
                                                $percentage = $discountSummary['total_discount_amount'] > 0 ? 
                                                    ($employee->total_discount_given / $discountSummary['total_discount_amount']) * 100 : 0;
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-40px symbol-circle me-3">
                                                            <div class="symbol-label bg-light-primary">
                                                                <span class="text-primary fw-bold">
                                                                    {{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex justify-content-start flex-column">
                                                            <span class="text-gray-800 fw-bold">{{ $employee->first_name }} {{ $employee->last_name }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ $employee->order_count }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">${{ number_format($employee->total_discount_given, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-info">${{ number_format($employee->average_discount, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-warning">${{ number_format($employee->max_discount_given, 2) }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100 me-3" style="height: 8px;">
                                                            <div class="progress-bar bg-primary" 
                                                                role="progressbar" 
                                                                style="width: {{ min($percentage, 100) }}%;" 
                                                                aria-valuenow="{{ $percentage }}" 
                                                                aria-valuemin="0" 
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                        <span class="fw-bold text-gray-700 min-w-60px text-end">
                                                            ${{ number_format($discountPerOrder, 2) }}
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
                @endif

                {{-- Discounted Orders Table --}}
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
                                        <h3 class="fw-bold m-0">{{ __('auth.discounted_orders_list') }}</h3>
                                    </div>
                                    @if($discountedOrders->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $discountedOrders->count() }} {{ __('auth.orders') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($discountedOrders->count() > 0)
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="discountedOrdersTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="min-w-100px">{{ __('auth.order_number') }}</th>
                                                    <th class="min-w-150px">{{ __('auth.customer') }}</th>
                                                    <th class="min-w-120px">{{ __('auth.order_total') }}</th>
                                                    <th class="min-w-120px">{{ __('auth.discount_amount') }}</th>
                                                    <th class="min-w-120px">{{ __('auth.discount_percentage') }}</th>
                                                    <th class="min-w-120px">{{ __('auth.final_amount') }}</th>
                                                    <th class="min-w-150px">{{ __('auth.processed_by') }}</th>
                                                    <th class="min-w-100px">{{ __('auth.order_date') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.status') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($discountedOrders as $order)
                                                @php
                                                    $discountPercentage = $order->total > 0 ? ($order->discount_total / $order->total) * 100 : 0;
                                                    $statusColors = [
                                                        'completed' => 'success',
                                                        'pending' => 'warning',
                                                        'processing' => 'info',
                                                        'cancelled' => 'danger',
                                                        'refunded' => 'secondary'
                                                    ];
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('orders.show', $order->id) }}" class="text-primary fw-bold">
                                                            {{ $order->order_number }}
                                                        </a>
                                                    </td>
                                                    <td>
                                                        @if($order->customer)
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-40px symbol-circle me-3">
                                                                <div class="symbol-label bg-light-primary">
                                                                    <span class="text-primary fw-bold">{{ substr($order->customer->name, 0, 1) }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-start flex-column">
                                                                <span class="text-gray-800 fw-bold">{{ $order->customer->name }}</span>
                                                                <span class="text-muted">{{ $order->customer->email }}</span>
                                                            </div>
                                                        </div>
                                                        @else
                                                        <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-600">${{ number_format($order->total, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-danger">${{ number_format($order->discount_total, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress w-100 me-3" style="height: 6px;">
                                                                <div class="progress-bar bg-danger" 
                                                                    role="progressbar" 
                                                                    style="width: {{ min($discountPercentage, 100) }}%;" 
                                                                    aria-valuenow="{{ $discountPercentage }}" 
                                                                    aria-valuemin="0" 
                                                                    aria-valuemax="100">
                                                                </div>
                                                            </div>
                                                            <span class="fw-bold text-gray-700 min-w-50px text-end">
                                                                {{ number_format($discountPercentage, 1) }}%
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">${{ number_format($order->total - $order->discount_total, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        @if($order->orderCreater)
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-40px symbol-circle me-3">
                                                                <div class="symbol-label bg-light-success">
                                                                    <span class="text-success fw-bold">{{ substr($order->orderCreater->name, 0, 1) }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-start flex-column">
                                                                <span class="text-gray-800 fw-bold">{{ $order->orderCreater->name }}</span>
                                                            </div>
                                                        </div>
                                                        @else
                                                        <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y H:i') }}</td>
                                                    <td>
                                                        <span class="badge badge-light-{{ $statusColors[$order->status] ?? 'secondary' }}">
                                                            {{ ucfirst($order->status) }}
                                                        </span>
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
                                        <i class="ki-duotone ki-percentage fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('auth.no_discounted_orders_found_for_period') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date']))
                                        <a href="{{ request()->url() }}" class="btn btn-light-primary">
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

                {{-- Discount Range Analysis --}}
                @if($discountedOrders->count() > 0)
                <div class="row mt-6">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-arrow-up-right fs-2 me-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.highest_discounts') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-success">
                                                <th class="ps-4">{{ __('auth.order') }}</th>
                                                <th>{{ __('auth.customer') }}</th>
                                                <th>{{ __('auth.discount_amount') }}</th>
                                                <th>{{ __('auth.discount_percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($discountedOrders->take(5) as $order)
                                            @php
                                                $discountPercentage = $order->total > 0 ? ($order->discount_total / $order->total) * 100 : 0;
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <a href="{{ route('orders.show', $order->id) }}" class="text-primary fw-bold">
                                                        {{ $order->order_number }}
                                                    </a>
                                                </td>
                                                <td>{{ $order->customer ? substr($order->customer->name, 0, 20) : 'Guest' }}{{ $order->customer && strlen($order->customer->name) > 20 ? '...' : '' }}</td>
                                                <td><span class="fw-bold text-success">${{ number_format($order->discount_total, 2) }}</span></td>
                                                <td><span class="badge badge-light-danger">{{ number_format($discountPercentage, 1) }}%</span></td>
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
                                    <i class="ki-duotone ki-arrow-down-left fs-2 me-2 text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.lowest_discounts') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-danger">
                                                <th class="ps-4">{{ __('auth.order') }}</th>
                                                <th>{{ __('auth.customer') }}</th>
                                                <th>{{ __('auth.discount_amount') }}</th>
                                                <th>{{ __('auth.discount_percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($discountedOrders->sortBy('discount_total')->take(5) as $order)
                                            @php
                                                $discountPercentage = $order->total > 0 ? ($order->discount_total / $order->total) * 100 : 0;
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <a href="{{ route('orders.show', $order->id) }}" class="text-primary fw-bold">
                                                        {{ $order->order_number }}
                                                    </a>
                                                </td>
                                                <td>{{ $order->customer ? substr($order->customer->name, 0, 20) : 'Guest' }}{{ $order->customer && strlen($order->customer->name) > 20 ? '...' : '' }}</td>
                                                <td><span class="fw-bold text-danger">${{ number_format($order->discount_total, 2) }}</span></td>
                                                <td><span class="badge badge-light-warning">{{ number_format($discountPercentage, 1) }}%</span></td>
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
@if($discountPatterns->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Discount Patterns Chart
        const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const patternsData = @json($discountPatterns);
        
        // Group by day of week
        const groupedByDay = {};
        patternsData.forEach(pattern => {
            const day = daysOfWeek[pattern.day_of_week - 1];
            if (!groupedByDay[day]) {
                groupedByDay[day] = {
                    discount_count: 0,
                    total_amount: 0
                };
            }
            groupedByDay[day].discount_count += pattern.discount_count;
            groupedByDay[day].total_amount += pattern.total_discount_amount;
        });
        
        const dayLabels = Object.keys(groupedByDay);
        const discountCounts = dayLabels.map(day => groupedByDay[day].discount_count);
        const discountAmounts = dayLabels.map(day => groupedByDay[day].total_amount);
        
        const discountPatternsChart = new ApexCharts(document.querySelector("#discountPatternsChart"), {
            series: [{
                name: 'Discount Count',
                data: discountCounts,
                type: 'bar'
            }, {
                name: 'Total Amount',
                data: discountAmounts,
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
                categories: dayLabels,
                labels: {
                    rotate: -45
                }
            },
            yaxis: [{
                title: {
                    text: 'Number of Discounts'
                },
                labels: {
                    formatter: function(val) {
                        return Math.round(val);
                    }
                }
            }, {
                opposite: true,
                title: {
                    text: 'Amount ($)'
                },
                labels: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 0});
                    }
                }
            }],
            colors: ['#3E97FF', '#50CD89'],
            tooltip: {
                y: {
                    formatter: function(val, { seriesIndex }) {
                        if (seriesIndex === 0) {
                            return val + ' discounts';
                        }
                        return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                }
            }
        });
        discountPatternsChart.render();
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