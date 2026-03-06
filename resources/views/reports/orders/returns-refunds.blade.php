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
                            @if($returnOrders->count() > 0 || $refundPayments->count() > 0)
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
                                <form method="GET" action="{{ request()->url() }}" id="filterForm">
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
                                        
                                        {{-- Action Buttons --}}
                                        <div class="d-flex flex-column justify-content-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <button type="submit" class="btn btn-primary flex-grow-1" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                                                </button>
                                                <a href="{{ request()->url() }}" class="btn btn-light btn-active-light-primary flex-grow-1">
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
                <div class="row mb-6">
                    @php
                        $totalReturnValue = $returnOrders->sum('total');
                        $totalRefundAmount = $refundPayments->sum('amount');
                        $avgReturnValue = $returnOrders->count() > 0 ? $totalReturnValue / $returnOrders->count() : 0;
                    @endphp
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-arrow-left-right fs-2tx text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        {{ $returnOrders->count() }}
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.total_returns') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-danger">
                                        {{ number_format($returnRate, 2) }}% {{ __('auth.return_rate') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-dollar-circle fs-2tx text-warning">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        ${{ number_format($totalReturnValue, 2) }}
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.total_return_value') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-warning">
                                        ${{ number_format($avgReturnValue, 2) }} {{ __('auth.average') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-money fs-2tx text-info">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        {{ $refundPayments->count() }}
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.total_refunds') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-info">
                                        ${{ number_format($totalRefundAmount, 2) }} {{ __('auth.total_amount') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-box fs-2tx text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        {{ $topReturnedProducts->sum('return_quantity') }}
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.items_returned') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-primary">
                                        {{ $topReturnedProducts->count() }} {{ __('auth.unique_products') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Return Reasons Analysis --}}
                @if($returnReasons->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie-3 fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.return_reasons_analysis') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div id="returnReasonsChart" style="height: 300px;"></div>
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
                                                    @foreach($returnReasons as $reason)
                                                    @php
                                                        $percentage = $returnOrders->count() > 0 ? ($reason->count / $returnOrders->count()) * 100 : 0;
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
                                                        <td>{{ $reason->count }}</td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                                    <div class="progress-bar bg-{{ $colorClass }}" 
                                                                        role="progressbar" 
                                                                        style="width: {{ $percentage }}%;" 
                                                                        aria-valuenow="{{ $percentage }}" 
                                                                        aria-valuemin="0" 
                                                                        aria-valuemax="100">
                                                                    </div>
                                                                </div>
                                                                <span class="fw-bold text-gray-700">
                                                                    {{ number_format($percentage, 1) }}%
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td>${{ number_format($reason->total_amount ?? 0, 2) }}</td>
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

                {{-- Top Returned Products --}}
                @if($topReturnedProducts->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_returned_products') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
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
                                                <td>{{ $product->name }}</td>
                                                <td><span class="badge badge-light-primary">{{ $product->sku }}</span></td>
                                                <td><span class="fw-bold text-danger">{{ $product->return_quantity }}</span></td>
                                                <td><span class="text-warning">${{ number_format($product->return_value, 2) }}</span></td>
                                                <td><span class="badge badge-light-info">{{ $product->return_count }}</span></td>
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

                {{-- Returns Table --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('auth.return_orders') }}</h3>
                                    </div>
                                    @if($returnOrders->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $returnOrders->count() }} {{ __('auth.returns') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($returnOrders->count() > 0)
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="returnsTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="min-w-100px">{{ __('auth.order_number') }}</th>
                                                    <th class="min-w-150px">{{ __('auth.customer') }}</th>
                                                    <th class="min-w-100px">{{ __('auth.type') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.status') }}</th>
                                                    <th class="min-w-120px">{{ __('auth.return_amount') }}</th>
                                                    <th class="min-w-150px">{{ __('auth.return_reason') }}</th>
                                                    <th class="min-w-150px">{{ __('auth.processed_by') }}</th>
                                                    <th class="min-w-100px">{{ __('auth.created_date') }}</th>
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
                                                        @php
                                                            $reason = $order->notes ? explode(':', $order->notes)[0] : 'Unknown';
                                                        @endphp
                                                        <span class="text-gray-700">{{ $reason }}</span>
                                                    </td>
                                                    <td>
                                                        @if($order->createdByUser)
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-40px symbol-circle me-3">
                                                                <div class="symbol-label bg-light-success">
                                                                    <span class="text-success fw-bold">{{ substr($order->createdByUser->name, 0, 1) }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-start flex-column">
                                                                <span class="text-gray-800 fw-bold">{{ $order->createdByUser->name }}</span>
                                                            </div>
                                                        </div>
                                                        @else
                                                        <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y H:i') }}</td>
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
                                        <p class="text-muted fs-6">{{ __('auth.no_returns_found_for_period') }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Refunds Table --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-money fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('auth.refund_payments') }}</h3>
                                    </div>
                                    @if($refundPayments->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $refundPayments->count() }} {{ __('auth.refunds') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($refundPayments->count() > 0)
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="min-w-100px">{{ __('auth.payment_id') }}</th>
                                                    <th class="min-w-100px">{{ __('auth.order_number') }}</th>
                                                    <th class="min-w-150px">{{ __('auth.payment_method') }}</th>
                                                    <th class="min-w-120px">{{ __('auth.refund_amount') }}</th>
                                                    <th class="min-w-120px">{{ __('auth.order_total') }}</th>
                                                    <th class="min-w-150px">{{ __('auth.refund_percentage') }}</th>
                                                    <th class="min-w-150px">{{ __('auth.processed_by') }}</th>
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
                                                    <td>
                                                        <span class="text-gray-800 fw-bold">{{ $payment->transaction_id ?: $payment->id }}</span>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('orders.show', $payment->order_id) }}" class="text-primary fw-bold">
                                                            {{ $payment->order_number }}
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-{{ $methodColors[$payment->payment_method] ?? 'secondary' }}">
                                                            {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
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
                                                                <div class="progress-bar bg-warning" 
                                                                    role="progressbar" 
                                                                    style="width: {{ min($refundPercentage, 100) }}%;" 
                                                                    aria-valuenow="{{ $refundPercentage }}" 
                                                                    aria-valuemin="0" 
                                                                    aria-valuemax="100">
                                                                </div>
                                                            </div>
                                                            <span class="fw-bold text-gray-700 min-w-60px text-end">
                                                                {{ number_format($refundPercentage, 1) }}%
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-700">{{ $payment->processed_by_name ?? '-' }}</span>
                                                    </td>
                                                    <td>{{ \Carbon\Carbon::parse($payment->processed_at)->format('M d, Y H:i') }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="card-body">
                                    <div class="text-center py-10">
                                        <i class="ki-duotone ki-money fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('auth.no_refunds_found_for_period') }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($returnReasons->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Return Reasons Chart
        const returnReasonsData = @json($returnReasons);
        const reasonLabels = returnReasonsData.map(reason => reason.reason ? reason.reason.substring(0, 15) : 'Unknown');
        const reasonCounts = returnReasonsData.map(reason => reason.count);
        const reasonColors = returnReasonsData.map(reason => {
            switch(reason.reason) {
                case 'Damaged': return '#F64E60';
                case 'Wrong Item': return '#FFA800';
                case 'Defective': return '#7239EA';
                case 'Size Issue': return '#3E97FF';
                case 'Color Issue': return '#50CD89';
                default: return '#A1A5B7';
            }
        });
        
        const returnReasonsChart = new ApexCharts(document.querySelector("#returnReasonsChart"), {
            series: reasonCounts,
            chart: {
                type: 'donut',
                height: 300
            },
            labels: reasonLabels,
            colors: reasonColors,
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Returns',
                                color: '#7E8299',
                                fontSize: '16px',
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                }
                            }
                        }
                    }
                }
            },
            legend: {
                position: 'right',
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