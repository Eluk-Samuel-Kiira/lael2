{{-- resources/views/reports/expenses/by-payment-method.blade.php --}}
@extends('layouts.app')

@section('title', __('accounting.expenses_by_payment_method'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                {{-- Toolbar Section --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <!-- Left side - Title and Breadcrumb -->
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('accounting.expenses_by_payment_method') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('accounting.payment_method_analysis') }}</li>
                            </ul>
                        </div>

                        <!-- Right side - Actions -->
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($methodBreakdown->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'paymentMethodTable', filename: 'expenses_by_payment_method_{{ date('Y_m_d') }}', sheetName: 'Payment Method Breakdown'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'paymentMethodTable', filename: 'expenses_by_payment_method_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.expenses.by-payment-method') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6">
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
                                                        title="{{ __('accounting.start_date') }}">
                                                </div>
                                                <span class="d-none d-sm-flex align-items-center text-gray-500 px-2">{{ __('accounting.to') }}</span>
                                                <span class="d-flex d-sm-none text-gray-500 text-center">{{ __('accounting.to') }}</span>
                                                <div class="input-group w-100">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control" name="end_date" 
                                                        value="{{ $endDate }}" required
                                                        title="{{ __('accounting.end_date') }}">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        {{-- Payment Method --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('accounting.payment_method') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-credit-card fs-2"></i>
                                                </span>
                                                <select class="form-select" name="payment_method_id">
                                                    <option value="">{{ __('accounting.all_payment_methods') }}</option>
                                                    @foreach($paymentMethods as $method)
                                                        <option value="{{ $method->id }}" 
                                                                {{ $paymentMethodId == $method->id ? 'selected' : '' }}
                                                                data-type="{{ $method->type }}">
                                                            {{ $method->name }}
                                                            @if($method->is_default)
                                                                ({{ __('accounting.default') }})
                                                            @endif
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
                                                <a href="{{ route('reports.expenses.by-payment-method') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
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

                {{-- Summary Statistics --}}
                @if($methodBreakdown->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.payment_method_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $totalTransactions = $methodBreakdown->sum('transaction_count');
                                        $totalAmount = $methodBreakdown->sum('grand_total');
                                        $totalTax = $methodBreakdown->sum('total_tax');
                                        $totalCategories = $methodBreakdown->sum('categories_used');
                                        $totalVendors = $methodBreakdown->sum('vendors_used');
                                        $avgTransaction = $totalTransactions > 0 ? $totalAmount / $totalTransactions : 0;
                                    @endphp
                                    @foreach([
                                        ['key' => 'total_methods', 'color' => 'primary', 'icon' => 'ki-credit-card', 'label' => 'total_payment_methods', 'value' => $methodBreakdown->count()],
                                        ['key' => 'total_transactions', 'color' => 'success', 'icon' => 'ki-receipt', 'label' => 'total_transactions', 'value' => $totalTransactions],
                                        ['key' => 'total_amount', 'color' => 'info', 'icon' => 'ki-chart-simple', 'label' => 'grand_total', 'value' => '$' . number_format($totalAmount, 2)],
                                        ['key' => 'total_tax', 'color' => 'warning', 'icon' => 'ki-receipt-tax', 'label' => 'total_tax', 'value' => '$' . number_format($totalTax, 2)],
                                        ['key' => 'avg_transaction', 'color' => 'danger', 'icon' => 'ki-calculator', 'label' => 'average_transaction', 'value' => '$' . number_format($avgTransaction, 2)],
                                        ['key' => 'top_method', 'color' => 'secondary', 'icon' => 'ki-ranking', 'label' => 'top_payment_method', 'value' => $methodBreakdown->first()->method_name ?? 'N/A']
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
                                                    {{ __('accounting.' . $stat['label']) }}
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

                {{-- Payment Method Breakdown Table --}}
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
                                        <h3 class="fw-bold m-0">{{ __('accounting.payment_method_breakdown') }}</h3>
                                    </div>
                                    @if($methodBreakdown->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $methodBreakdown->count() }} {{ __('accounting.payment_methods') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($methodBreakdown->count() > 0)
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="paymentMethodTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="min-w-50px ps-4">{{ __('accounting.rank') }}</th>
                                                    <th class="min-w-200px">{{ __('accounting.payment_method') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.transactions') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.total_amount') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.grand_total') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.average') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.max') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.categories') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.vendors') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.distribution') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($methodBreakdown as $index => $method)
                                                @php
                                                    $percentage = $totalAmount > 0 ? ($method->grand_total / $totalAmount) * 100 : 0;
                                                    $methodCategories = $methodByCategory[$method->method_name] ?? collect();
                                                    
                                                    // Define method type colors - FIXED ARRAY SYNTAX
                                                    $methodTypeColors = [
                                                        'cash' => 'success',
                                                        'card' => 'primary', 
                                                        'bank_transfer' => 'info',
                                                        'digital_wallet' => 'warning',
                                                        'check' => 'secondary',
                                                        'other' => 'dark'
                                                    ];
                                                    
                                                    $methodTypeColor = $methodTypeColors[$method->method_type] ?? 'secondary';
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
                                                                <div class="symbol-label bg-light-{{ $methodTypeColor }}">
                                                                    <i class="ki-duotone ki-credit-card fs-2"></i>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-start flex-column">
                                                                <span class="text-gray-800 fw-bold">{{ $method->method_name }}</span>
                                                                <div class="mt-1">
                                                                    @if($method->is_active)
                                                                    <span class="badge badge-light-success badge-sm">
                                                                        {{ __('accounting.active') }}
                                                                    </span>
                                                                    @else
                                                                    <span class="badge badge-light-danger badge-sm">
                                                                        {{ __('accounting.inactive') }}
                                                                    </span>
                                                                    @endif
                                                                    @if($methodCategories->isNotEmpty())
                                                                    <span class="badge badge-light-info badge-sm ms-1">
                                                                        {{ $methodCategories->count() }} {{ __('accounting.categories') }}
                                                                    </span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-{{ $methodTypeColor }}">
                                                            {{ __('accounting.' . $method->method_type) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-primary">{{ $method->transaction_count }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-800 fw-semibold">${{ number_format($method->total_amount, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">${{ number_format($method->grand_total, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-600">${{ number_format($method->average_transaction, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-danger">${{ number_format($method->max_transaction, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-info">{{ $method->categories_used }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-warning">{{ $method->vendors_used }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress w-100 me-3" style="height: 8px;">
                                                                <div class="progress-bar bg-{{ ['primary', 'success', 'info', 'warning', 'danger'][$index % 5] }}" 
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
                                        <p class="text-muted fs-6">{{ __('accounting.no_expenses_found_for_payment_methods') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'payment_method_id']))
                                        <a href="{{ route('reports.expenses.by-payment-method') }}" class="btn btn-light-primary">
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
                {{-- END Payment Method Breakdown Table --}}
                
                {{-- Monthly Payment Method Trends --}}
                @if($monthlyTrend->count() > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.monthly_payment_method_trends') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="monthlyTrendChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Payment Method by Category --}}
                <div class="row mt-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-category fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.payment_method_by_category') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @foreach($methodByCategory as $methodName => $categories)
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                            <div class="card-header border-0">
                                                <div class="card-title d-flex align-items-center">
                                                    <i class="ki-duotone ki-credit-card fs-2 me-2 text-primary"></i>
                                                    <h3 class="fw-bold m-0">{{ $methodName }}</h3>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="table-responsive">
                                                    <table class="table table-row-bordered table-row-dashed gy-2 align-middle">
                                                        <thead>
                                                            <tr class="fw-bold fs-7 text-gray-800 border-bottom">
                                                                <th>{{ __('accounting.category') }}</th>
                                                                <th class="text-end">{{ __('accounting.transactions') }}</th>
                                                                <th class="text-end">{{ __('accounting.amount') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($categories->take(5) as $category)
                                                            <tr>
                                                                <td class="fs-7">{{ Str::limit($category->category_name, 20) }}</td>
                                                                <td class="text-end">
                                                                    <span class="badge badge-light-primary">{{ $category->transaction_count }}</span>
                                                                </td>
                                                                <td class="text-end fw-semibold">${{ number_format($category->total_amount, 2) }}</td>
                                                            </tr>
                                                            @endforeach
                                                            @if($categories->count() > 5)
                                                            <tr>
                                                                <td colspan="3" class="text-center pt-3">
                                                                    <small class="text-muted">
                                                                        +{{ $categories->count() - 5 }} {{ __('accounting.more_categories') }}
                                                                    </small>
                                                                </td>
                                                            </tr>
                                                            @endif
                                                        </tbody>
                                                        <tfoot>
                                                            <tr class="fw-bold text-gray-800 border-top">
                                                                <td>{{ __('accounting.total') }}</td>
                                                                <td class="text-end">
                                                                    <span class="badge badge-light-primary">{{ $categories->sum('transaction_count') }}</span>
                                                                </td>
                                                                <td class="text-end text-success fw-bold">
                                                                    ${{ number_format($categories->sum('total_amount'), 2) }}
                                                                </td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
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
                {{-- END Monthly Payment Method Trends --}}
                
            </div>
        </div>
    </div>
</div>

@if($monthlyTrend->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get top 5 payment methods
        const topMethods = @json($methodBreakdown->take(5)->pluck('method_name'));
        const monthlyTrend = @json($monthlyTrend);
        
        // Create series data for top 5 payment methods
        const seriesData = [];
        const monthSet = new Set();
        
        // Flatten the monthlyTrend data into a single array
        const monthlyData = [];
        Object.keys(monthlyTrend).forEach(methodName => {
            monthlyTrend[methodName].forEach(item => {
                monthlyData.push({
                    method_name: methodName,
                    year: item.year,
                    month: item.month,
                    monthly_total: item.monthly_total
                });
                
                // Add to monthSet
                const monthKey = `${item.year}-${String(item.month).padStart(2, '0')}`;
                monthSet.add(monthKey);
            });
        });
        
        const sortedMonths = Array.from(monthSet).sort();
        
        // Prepare data for each top payment method
        topMethods.forEach((methodName, methodIndex) => {
            const methodData = [];
            
            sortedMonths.forEach(monthKey => {
                const [year, month] = monthKey.split('-');
                const monthlyItem = monthlyData.find(item => 
                    item.method_name === methodName && 
                    parseInt(item.year) === parseInt(year) && 
                    parseInt(item.month) === parseInt(month)
                );
                
                methodData.push(monthlyItem ? parseFloat(monthlyItem.monthly_total) : 0);
            });
            
            seriesData.push({
                name: methodName,
                data: methodData,
                type: 'line',
                color: getMethodColor(methodIndex)
            });
        });
        
        // Format month labels
        const monthLabels = sortedMonths.map(monthKey => {
            const [year, month] = monthKey.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                year: '2-digit' 
            });
        });
        
        // Initialize chart
        const chartOptions = {
            series: seriesData,
            chart: {
                type: 'line',
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
                }
            },
            stroke: {
                width: 3,
                curve: 'smooth'
            },
            xaxis: {
                categories: monthLabels,
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Amount ($)'
                },
                labels: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString();
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'center',
                fontSize: '14px',
                fontFamily: 'Helvetica, Arial',
                itemMargin: {
                    horizontal: 10,
                    vertical: 5
                }
            },
            markers: {
                size: 5
            },
            grid: {
                borderColor: '#f1f1f1',
            },
            dataLabels: {
                enabled: false
            }
        };
        
        const chart = new ApexCharts(document.querySelector("#monthlyTrendChart"), chartOptions);
        chart.render();
        
        // Function to get payment method color
        function getMethodColor(index) {
            const colors = ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C'];
            return colors[index % colors.length];
        }
    });
</script>
@endif

@endsection