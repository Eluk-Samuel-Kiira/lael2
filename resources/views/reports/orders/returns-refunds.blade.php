{{-- resources/views/reports/orders/returns-refunds.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.returns_refunds_report'))

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
                                {{ __('auth.returns_refunds_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('auth.returns_refunds_report') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if(($returnOrders && $returnOrders->count() > 0) || ($refundPayments && $refundPayments->count() > 0))
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'returnsTable', filename: 'returns_refunds_{{ date('Y_m_d') }}', sheetName: 'Returns & Refunds'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'returnsTable', filename: 'returns_refunds_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.orders.returns-refunds') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap">
                                        {{-- Date Range --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label required fw-semibold">{{ __('accounting.date_range') }}</label>
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <div class="input-group w-100">
                                                    <span class="input-group-text">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control" name="start_date" 
                                                        value="{{ $startDate }}" required>
                                                </div>
                                                <span class="d-none d-sm-flex align-items-center text-gray-500 px-2">{{ __('accounting.to') }}</span>
                                                <span class="d-flex d-sm-none text-gray-500 text-center">{{ __('accounting.to') }}</span>
                                                <div class="input-group w-100">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control" name="end_date" 
                                                        value="{{ $endDate }}" required>
                                                </div>
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
                                                <a href="{{ route('reports.orders.returns-refunds') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
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

                {{-- Summary Cards --}}
                @if(isset($metrics))
                <div class="row mb-6">
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-arrow-left-right fs-2tx text-danger"></i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        {{ $metrics->total_return_orders }}
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.total_returns') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-danger">
                                        {{ number_format($metrics->return_rate, 2) }}% {{ __('auth.return_rate') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-dollar-circle fs-2tx text-warning"></i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        ${{ number_format($metrics->total_refund_amount, 2) }}
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.total_refunded_amount') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-warning">
                                        {{ number_format($metrics->refund_rate, 2) }}% {{ __('auth.refund_rate') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-calculator fs-2tx text-info"></i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        ${{ number_format($metrics->average_return_value, 2) }}
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.average_return_value') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-info">
                                        {{ $metrics->total_return_orders }} {{ __('auth.returns') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-box fs-2tx text-primary"></i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        {{ $topReturnedProducts->sum('return_quantity') ?? 0 }}
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.items_returned') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-primary">
                                        {{ $topReturnedProducts->count() ?? 0 }} {{ __('auth.unique_products') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Return Reasons Analysis --}}
                @if(isset($returnReasonsCollection) && $returnReasonsCollection->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie-3 fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.return_reasons_analysis') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div id="returnReasonsChart" style="height: 350px;"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="table-responsive">
                                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                                <thead>
                                                    <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                        <th>{{ __('auth.return_reason') }}</th>
                                                        <th>{{ __('auth.count') }}</th>
                                                        <th>{{ __('auth.percentage') }}</th>
                                                        <th>{{ __('auth.amount') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($returnReasonsCollection as $reason)
                                                    @php
                                                        $colorClass = match($reason->reason) {
                                                            'Damaged' => 'danger',
                                                            'Wrong Item' => 'warning',
                                                            'Defective' => 'info',
                                                            'Size Issue' => 'primary',
                                                            'Color Issue' => 'success',
                                                            default => 'secondary'
                                                        };
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <span class="badge badge-light-{{ $colorClass }}">
                                                                {{ $reason->reason ?: 'Unknown' }}
                                                            </span>
                                                        </td>
                                                        <td>{{ number_format($reason->count) }}</td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                                    <div class="progress-bar bg-{{ $colorClass }}" 
                                                                        style="width: {{ $reason->percentage }}%;">
                                                                    </div>
                                                                </div>
                                                                <span class="fw-bold text-gray-700">
                                                                    {{ number_format($reason->percentage, 1) }}%
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td class="text-danger fw-bold">
                                                            ${{ number_format($reason->total_amount, 2) }}
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
                    </div>
                </div>
                @endif

                {{-- Monthly Return Trends --}}
                @if(isset($monthlyReturnTrends) && $monthlyReturnTrends->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-info"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.monthly_return_trends') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="monthlyTrendsChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Returns by Payment Method --}}
                @if(isset($returnsByPaymentMethod) && $returnsByPaymentMethod->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-credit-card fs-2 me-2 text-warning"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.returns_by_payment_method') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-warning">
                                                <th class="ps-4">{{ __('accounting.payment_method') }}</th>
                                                <th>{{ __('auth.return_count') }}</th>
                                                <th>{{ __('auth.return_value') }}</th>
                                                <th>{{ __('accounting.percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $totalReturnValue = $returnsByPaymentMethod->sum('return_value');
                                            @endphp
                                            @foreach($returnsByPaymentMethod as $method)
                                            @php
                                                $percentage = $totalReturnValue > 0 ? ($method->return_value / $totalReturnValue) * 100 : 0;
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <i class="ki-duotone ki-credit-card fs-2 text-warning me-2"></i>
                                                    {{ $method->payment_method }}
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ number_format($method->return_count) }}</span>
                                                </td>
                                                <td class="text-danger fw-bold">
                                                    ${{ number_format($method->return_value, 2) }}
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100 me-3" style="height: 6px;">
                                                            <div class="progress-bar bg-warning" style="width: {{ $percentage }}%;"></div>
                                                        </div>
                                                        <span class="fw-bold text-gray-700">
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
                @endif

                {{-- Top Returned Products --}}
                @if(isset($topReturnedProducts) && $topReturnedProducts->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-danger"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_returned_products') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="returnsTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-danger">
                                                <th class="ps-4">{{ __('accounting.rank') }}</th>
                                                <th>{{ __('auth.product') }}</th>
                                                <th>{{ __('accounting.sku') }}</th>
                                                <th>{{ __('auth.quantity_returned') }}</th>
                                                <th>{{ __('auth.return_value') }}</th>
                                                <th>{{ __('auth.return_count') }}</th>
                                                <th>{{ __('auth.average_return') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topReturnedProducts as $index => $product)
                                            @php
                                                $avgReturn = $product->return_count > 0 ? $product->return_value / $product->return_count : 0;
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
                                                <td class="fw-bold text-gray-800">{{ $product->name }}</td>
                                                <td><span class="badge badge-light-primary">{{ $product->sku }}</span></td>
                                                <td><span class="fw-bold text-danger">{{ number_format($product->return_quantity) }}</span></td>
                                                <td><span class="text-warning fw-bold">${{ number_format($product->return_value, 2) }}</span></td>
                                                <td><span class="badge badge-light-info">{{ number_format($product->return_count) }}</span></td>
                                                <td><span class="text-gray-600">${{ number_format($avgReturn, 2) }}</span></td>
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

                {{-- Return Orders Table --}}
                @if(isset($returnOrders) && $returnOrders->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                                        <h3 class="fw-bold m-0">{{ __('auth.return_orders') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $returnOrders->count() }} {{ __('auth.returns') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="min-w-100px ps-4">{{ __('auth.order_number') }}</th>
                                                <th class="min-w-150px">{{ __('auth.customer') }}</th>
                                                <th class="min-w-100px">{{ __('auth.type') }}</th>
                                                <th class="min-w-100px">{{ __('accounting.status') }}</th>
                                                <th class="min-w-120px">{{ __('auth.return_amount') }}</th>
                                                <th class="min-w-150px">{{ __('auth.return_reason') }}</th>
                                                <th class="min-w-150px">{{ __('auth.created_date') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($returnOrders as $order)
                                            @php
                                                $statusColors = [
                                                    'pending' => 'warning',
                                                    'refunded' => 'success',
                                                    'rejected' => 'danger',
                                                    'processing' => 'info'
                                                ];
                                                $typeColors = [
                                                    'return' => 'danger',
                                                    'exchange' => 'info',
                                                    'refund' => 'warning'
                                                ];
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <a href="{{ route('orders.show', $order->id) }}" class="text-primary fw-bold">
                                                        #{{ $order->order_number }}
                                                    </a>
                                                </td>
                                                <td>
                                                    @if($order->customer)
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-40px symbol-circle me-3">
                                                            <div class="symbol-label bg-light-primary">
                                                                <span class="text-primary fw-bold">{{ substr($order->customer->name ?? 'U', 0, 1) }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex justify-content-start flex-column">
                                                            <span class="text-gray-800 fw-bold">{{ $order->customer->name ?? 'N/A' }}</span>
                                                            <span class="text-muted">{{ $order->customer->email ?? '' }}</span>
                                                        </div>
                                                    </div>
                                                    @else
                                                    <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $typeColors[$order->type] ?? 'secondary' }}">
                                                        {{ ucfirst($order->type) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $statusColors[$order->status] ?? 'secondary' }}">
                                                        {{ ucfirst($order->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-danger">${{ number_format($order->total, 2) }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="badge badge-light-warning badge-sm mb-1">{{ $order->return_reason }}</span>
                                                        @if($order->return_reason_description)
                                                        <small class="text-muted">{{ Str::limit($order->return_reason_description, 50) }}</small>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
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

                {{-- Refunds Table --}}
                @if(isset($refundPayments) && $refundPayments->count() > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-money fs-2 me-2 text-primary"></i>
                                        <h3 class="fw-bold m-0">{{ __('auth.refund_payments') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $refundPayments->count() }} {{ __('auth.refunds') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="min-w-100px ps-4">{{ __('auth.payment_id') }}</th>
                                                <th class="min-w-100px">{{ __('auth.order_number') }}</th>
                                                <th class="min-w-150px">{{ __('auth.payment_method') }}</th>
                                                <th class="min-w-120px">{{ __('auth.refund_amount') }}</th>
                                                <th class="min-w-120px">{{ __('auth.order_total') }}</th>
                                                <th class="min-w-150px">{{ __('auth.refund_percentage') }}</th>
                                                <th class="min-w-100px">{{ __('auth.processed_date') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($refundPayments as $payment)
                                            @php
                                                $refundPercentage = $payment->order_total > 0 ? ($payment->amount / $payment->order_total) * 100 : 0;
                                                $methodColors = [
                                                    'credit_card' => 'success',
                                                    'cash' => 'primary',
                                                    'bank_transfer' => 'info',
                                                    'check' => 'warning',
                                                    'other' => 'secondary'
                                                ];
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="text-gray-800 fw-bold">{{ $payment->reference ?? $payment->id }}</span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('orders.show', $payment->order_id) }}" class="text-primary fw-bold">
                                                        #{{ $payment->order_number }}
                                                    </a>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $methodColors[strtolower(str_replace(' ', '_', $payment->payment_method))] ?? 'secondary' }}">
                                                        {{ $payment->payment_method }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-danger">${{ number_format($payment->amount, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-gray-600">${{ number_format($payment->order_total, 2) }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100 me-3" style="height: 8px;">
                                                            <div class="progress-bar bg-warning" style="width: {{ min($refundPercentage, 100) }}%;"></div>
                                                        </div>
                                                        <span class="fw-bold text-gray-700 min-w-60px text-end">
                                                            {{ number_format($refundPercentage, 1) }}%
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>{{ \Carbon\Carbon::parse($payment->processed_at)->format('M d, Y H:i') }}</td>
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

                {{-- No Data Message --}}
                @if((!isset($returnOrders) || $returnOrders->count() == 0) && (!isset($refundPayments) || $refundPayments->count() == 0))
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="text-center py-10">
                                    <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                                    <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                    <p class="text-muted fs-6">{{ __('auth.no_returns_found_for_period') }}</p>
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
@if(isset($returnReasonsCollection) && $returnReasonsCollection->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Return Reasons Chart
        const returnReasonsData = @json($returnReasonsCollection);
        const reasonLabels = returnReasonsData.map(reason => reason.reason ? reason.reason.substring(0, 20) : 'Unknown');
        const reasonCounts = returnReasonsData.map(reason => reason.count);
        
        const returnReasonsChart = new ApexCharts(document.querySelector("#returnReasonsChart"), {
            series: reasonCounts,
            chart: {
                type: 'donut',
                height: 350
            },
            labels: reasonLabels,
            colors: ['#F64E60', '#FFA800', '#7239EA', '#3E97FF', '#50CD89', '#A1A5B7'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Returns',
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                }
                            }
                        }
                    }
                }
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center'
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toFixed(1) + '%';
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' returns';
                    }
                }
            }
        });
        returnReasonsChart.render();
        
        @if(isset($monthlyReturnTrends) && $monthlyReturnTrends->count() > 0)
        // Monthly Trends Chart
        const monthlyTrendsData = @json($monthlyReturnTrends);
        const months = monthlyTrendsData.map(item => item.month);
        const returnValues = monthlyTrendsData.map(item => parseFloat(item.return_value));
        const returnCounts = monthlyTrendsData.map(item => item.return_count);
        
        const monthlyTrendsChart = new ApexCharts(document.querySelector("#monthlyTrendsChart"), {
            series: [{
                name: 'Return Value ($)',
                type: 'line',
                data: returnValues
            }, {
                name: 'Return Count',
                type: 'column',
                data: returnCounts
            }],
            chart: {
                height: 350,
                type: 'line',
                stacked: false
            },
            colors: ['#F64E60', '#3E97FF'],
            stroke: {
                width: [3, 1],
                curve: 'smooth'
            },
            xaxis: {
                categories: months,
                labels: {
                    rotate: -45
                }
            },
            yaxis: [{
                title: {
                    text: 'Return Value ($)'
                },
                labels: {
                    formatter: function(val) {
                        return '$' + val.toFixed(2);
                    }
                }
            }, {
                opposite: true,
                title: {
                    text: 'Return Count'
                }
            }],
            tooltip: {
                shared: true,
                intersect: false
            }
        });
        monthlyTrendsChart.render();
        @endif
    });
</script>
@endif

<script>
    // Form validation
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            const startDate = new Date(document.querySelector('[name="start_date"]').value);
            const endDate = new Date(document.querySelector('[name="end_date"]').value);
            
            if (startDate > endDate) {
                e.preventDefault();
                alert('{{ __("auth.start_date_cannot_be_after_end_date") }}');
                return false;
            }
        });
    }
    
    // Export function
    function exportCurrentPage(options = {}) {
        const {
            tableId = 'returnsTable',
            filename = 'returns_refunds_' + new Date().toISOString().split('T')[0],
            format = 'xlsx',
            sheetName = 'Returns & Refunds'
        } = options;
        
        const table = document.getElementById(tableId);
        if (!table) {
            // If specific table not found, export both tables or show error
            console.warn('Table not found:', tableId);
            return;
        }
        
        // Get table data
        const data = [];
        const headers = [];
        
        // Get headers
        table.querySelectorAll('thead th').forEach(th => {
            headers.push(th.textContent.trim());
        });
        
        // Get rows
        table.querySelectorAll('tbody tr').forEach(row => {
            const rowData = [];
            row.querySelectorAll('td').forEach((td, index) => {
                const tdClone = td.cloneNode(true);
                tdClone.querySelectorAll('.ki-duotone, .badge, i, .progress, .symbol').forEach(el => el.remove());
                rowData.push(tdClone.textContent.trim());
            });
            data.push(rowData);
        });
        
        // Create CSV or Excel export
        let csvContent = headers.join(',') + '\n';
        data.forEach(row => {
            csvContent += row.map(cell => `"${cell.replace(/"/g, '""')}"`).join(',') + '\n';
        });
        
        if (format === 'csv') {
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.href = url;
            link.setAttribute('download', `${filename}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        } else {
            // For Excel, use CSV as fallback or implement XLSX library
            const blob = new Blob([csvContent], { type: 'application/vnd.ms-excel' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.href = url;
            link.setAttribute('download', `${filename}.xls`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }
    }
</script>
@endpush

@endsection