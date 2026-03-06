{{-- resources/views/reports/orders/by-payment-method.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.payment_method_analysis'))

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
                                {{ __('auth.payment_method_analysis') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('auth.payment_method_analysis') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($paymentMethodAnalysis->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'paymentMethodTable', filename: 'payment_method_analysis_{{ date('Y_m_d') }}', sheetName: 'Payment Method Analysis'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'paymentMethodTable', filename: 'payment_method_analysis_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.orders.by-payment-method') }}" id="filterForm">
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
                                        
                                        {{-- Payment Type --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('auth.payment_type') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-credit-card fs-2"></i>
                                                </span>
                                                <select class="form-select" name="payment_type">
                                                    <option value="all">{{ __('auth.all_types') }}</option>
                                                    @foreach($paymentTypes as $type)
                                                        <option value="{{ $type }}" 
                                                                {{ $paymentType == $type ? 'selected' : '' }}>
                                                            {{ ucfirst(str_replace('_', ' ', $type)) }}
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
                                                    <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                                                </button>
                                                <a href="{{ route('reports.orders.by-payment-method') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
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

                {{-- Payment Method Summary --}}
                @if($paymentMethodAnalysis->count() > 0)
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
                                    @php
                                        $totalTransactions = $paymentMethodAnalysis->sum('transaction_count');
                                        $totalAmount = $paymentMethodAnalysis->sum('total_amount');
                                        $avgTransaction = $paymentMethodAnalysis->avg('average_transaction') ?? 0;
                                        $topMethod = $paymentMethodAnalysis->first();
                                    @endphp
                                    
                                    @foreach([
                                        ['key' => 'total_methods', 'color' => 'primary', 'icon' => 'ki-credit-card', 'label' => 'total_payment_methods', 'value' => $paymentMethodAnalysis->count()],
                                        ['key' => 'total_transactions', 'color' => 'info', 'icon' => 'ki-receipt', 'label' => 'total_transactions', 'value' => number_format($totalTransactions)],
                                        ['key' => 'total_amount', 'color' => 'success', 'icon' => 'ki-dollar', 'label' => 'total_amount_processed', 'value' => '$' . number_format($totalAmount, 2)],
                                        ['key' => 'avg_transaction', 'color' => 'warning', 'icon' => 'ki-calculator', 'label' => 'average_transaction', 'value' => '$' . number_format($avgTransaction, 2)],
                                        ['key' => 'top_method', 'color' => 'danger', 'icon' => 'ki-crown', 'label' => 'top_payment_method', 'value' => $topMethod ? $topMethod->method_name : 'N/A'],
                                        ['key' => 'top_amount', 'color' => 'secondary', 'icon' => 'ki-dollar-circle', 'label' => 'top_method_amount', 'value' => $topMethod ? '$' . number_format($topMethod->total_amount, 2) : '$0.00']
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

                {{-- Payment Method Distribution Chart --}}
                @if($paymentMethodAnalysis->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.payment_method_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="paymentMethodChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Payment Method Trends --}}
                @if($paymentTrends->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.payment_method_trends') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="paymentTrendsChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Failed Transactions --}}
                @if($failedTransactions->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-cross-circle fs-2 me-2 text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.failed_transactions') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-danger">
                                                <th class="ps-4">{{ __('accounting.payment_method') }}</th>
                                                <th>{{ __('auth.failed_count') }}</th>
                                                <th>{{ __('auth.failed_amount') }}</th>
                                                <th>{{ __('accounting.failure_rate') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($failedTransactions as $failed)
                                            @php
                                                $methodTransactions = $paymentMethodAnalysis->firstWhere('method_name', $failed->name);
                                                $totalForMethod = $methodTransactions ? $methodTransactions->transaction_count : 0;
                                                $failureRate = $totalForMethod > 0 ? ($failed->failed_count / $totalForMethod) * 100 : 100;
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="badge badge-light-danger">{{ $failed->name }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-danger">{{ $failed->failed_count }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-danger">${{ number_format($failed->failed_amount, 2) }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100 me-3" style="height: 8px;">
                                                            <div class="progress-bar bg-danger" 
                                                                style="width: {{ min($failureRate, 100) }}%"></div>
                                                        </div>
                                                        <span class="fw-bold text-gray-700 min-w-60px text-end">
                                                            {{ number_format($failureRate, 1) }}%
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

                {{-- Payment Method Analysis Table --}}
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
                                        <h3 class="fw-bold m-0">{{ __('auth.payment_method_analysis') }}</h3>
                                    </div>
                                    @if($paymentMethodAnalysis->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $paymentMethodAnalysis->count() }} {{ __('accounting.payment_methods') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($paymentMethodAnalysis->count() > 0)
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="paymentMethodTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="min-w-50px ps-4">{{ __('accounting.rank') }}</th>
                                                    <th class="min-w-200px">{{ __('accounting.payment_method') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                                    <th class="min-w-100px">{{ __('auth.transaction_count') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.total_amount') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.average_amount') }}</th>
                                                    <th class="min-w-120px">{{ __('auth.largest_transaction') }}</th>
                                                    <th class="min-w-120px">{{ __('auth.smallest_transaction') }}</th>
                                                    <th class="min-w-150px">{{ __('auth.last_transaction_date') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.market_share') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.percentage') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($paymentMethodAnalysis as $index => $method)
                                                @php
                                                    $totalAmountAll = $paymentMethodAnalysis->sum('total_amount');
                                                    $percentage = $totalAmountAll > 0 ? ($method->total_amount / $totalAmountAll) * 100 : 0;
                                                    $typeColors = [
                                                        'cash' => 'success',
                                                        'card' => 'primary',
                                                        'bank_transfer' => 'info',
                                                        'digital_wallet' => 'warning',
                                                        'credit' => 'danger',
                                                        'other' => 'secondary'
                                                    ];
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
                                                                <div class="symbol-label bg-light-{{ $typeColors[$method->method_type] ?? 'secondary' }}">
                                                                    <i class="ki-duotone ki-credit-card fs-2"></i>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-start flex-column">
                                                                <span class="text-gray-800 fw-bold">{{ $method->method_name }}</span>
                                                                <span class="badge badge-light-{{ $typeColors[$method->method_type] ?? 'secondary' }} badge-sm mt-1">
                                                                    {{ ucfirst(str_replace('_', ' ', $method->method_type)) }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-{{ $typeColors[$method->method_type] ?? 'secondary' }}">
                                                            {{ ucfirst(str_replace('_', ' ', $method->method_type)) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-primary">{{ $method->transaction_count }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">${{ number_format($method->total_amount, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-600">${{ number_format($method->average_transaction, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-danger">${{ number_format($method->largest_transaction, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-secondary">${{ number_format($method->smallest_transaction, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        @if($method->last_transaction_date)
                                                        <span class="text-gray-700">{{ \Carbon\Carbon::parse($method->last_transaction_date)->format('M d, Y H:i') }}</span>
                                                        @else
                                                        <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-gray-700">{{ number_format($method->transaction_count / max($totalTransactions, 1) * 100, 1) }}%</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress w-100 me-3" style="height: 8px;">
                                                                <div class="progress-bar bg-{{ $typeColors[$method->method_type] ?? 'secondary' }}" 
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
                                        <p class="text-muted fs-6">{{ __('auth.no_payment_methods_found_for_period') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'location_id', 'department_id']))
                                        <a href="{{ route('reports.orders.by-payment-method') }}" class="btn btn-light-primary">
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
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($paymentMethodAnalysis->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Payment Method Distribution Chart
        const paymentData = @json($paymentMethodAnalysis);
        const methodNames = paymentData.map(method => method.method_name);
        const methodAmounts = paymentData.map(method => parseFloat(method.total_amount));
        const methodTransactions = paymentData.map(method => parseFloat(method.transaction_count));
        
        const paymentMethodChart = new ApexCharts(document.querySelector("#paymentMethodChart"), {
            series: methodAmounts,
            chart: {
                type: 'donut',
                height: 400
            },
            labels: methodNames,
            colors: ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C', '#A1A5B7'],
            legend: {
                position: 'bottom'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Amount',
                                formatter: function(w) {
                                    return '$' + w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString(undefined, {minimumFractionDigits: 2});
                                }
                            }
                        }
                    }
                }
            }
        });
        paymentMethodChart.render();
        
        @if($paymentTrends->count() > 0)
        // Payment Trends Chart
        const paymentTrendsData = @json($paymentTrends);
        const trendSeries = [];
        const trendCategories = [];
        
        // Prepare data for trends chart
        Object.keys(paymentTrendsData).forEach((methodType, index) => {
            const methodData = paymentTrendsData[methodType];
            const trendValues = methodData.map(item => parseFloat(item.daily_total));
            
            if (trendCategories.length === 0) {
                trendCategories = methodData.map(item => new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
            }
            
            trendSeries.push({
                name: methodType.replace('_', ' ').toUpperCase(),
                data: trendValues,
                type: 'line'
            });
        });
        
        const paymentTrendsChart = new ApexCharts(document.querySelector("#paymentTrendsChart"), {
            series: trendSeries,
            chart: {
                type: 'line',
                height: 400,
                toolbar: {
                    show: true
                }
            },
            stroke: {
                width: 3,
                curve: 'smooth'
            },
            xaxis: {
                categories: trendCategories,
                labels: {
                    rotate: -45
                }
            },
            yaxis: {
                title: {
                    text: 'Daily Amount ($)'
                },
                labels: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString();
                    }
                }
            },
            colors: ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C'],
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                }
            },
            legend: {
                position: 'top'
            }
        });
        paymentTrendsChart.render();
        @endif
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