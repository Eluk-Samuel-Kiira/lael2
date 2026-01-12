{{-- resources/views/reports/expenses/summary.blade.php --}}
@extends('layouts.app')

@section('title', __('accounting.expense_report'))

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
                                {{ __('accounting.expense_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('accounting.expense_summary') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if($topExpenses->count() > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'expensesTable', filename: 'expenses_report_{{ date('Y_m_d') }}', sheetName: 'Expenses Report'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'expensesTable', filename: 'expenses_report_{{ date('Y_m_d') }}', format: 'csv'})">
                                            <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                            {{ __('accounting.export_to_csv') }}
                                        </a>
                                    </li>
                                    @if($dailyBreakdown->count() > 0)
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'dailyTable', filename: 'daily_expenses_{{ date('Y_m_d') }}', sheetName: 'Daily Breakdown'})">
                                            <i class="ki-duotone ki-file-sheet fs-2 me-2 text-warning"></i>
                                            {{ __('accounting.export_daily_data') }}
                                        </a>
                                    </li>
                                    @endif
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
                                <form method="GET" action="{{ route('reports.expenses.summary') }}" id="filterForm">
                                    <div class="row g-6 mb-6">
                                        {{-- Date Range --}}
                                        <div class="col-md-12 col-lg-4">
                                            <label class="form-label required fw-semibold">{{ __('accounting.date_range') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                </span>
                                                <input type="date" class="form-control" name="start_date" 
                                                    value="{{ $startDate }}" required
                                                    title="{{ __('accounting.start_date') }}">
                                                <span class="input-group-text bg-light">{{ __('accounting.to') }}</span>
                                                <input type="date" class="form-control" name="end_date" 
                                                    value="{{ $endDate }}" required
                                                    title="{{ __('accounting.end_date') }}">
                                            </div>
                                        </div>
                                        
                                        {{-- Category --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-category fs-2"></i>
                                                </span>
                                                <select class="form-select" name="category_id">
                                                    <option value="">{{ __('accounting.all_categories') }}</option>
                                                    @foreach($categories as $category)
                                                        <option value="{{ $category->id }}" 
                                                                {{ $categoryId == $category->id ? 'selected' : '' }}
                                                                data-requires-receipt="{{ $category->requires_receipt }}">
                                                            {{ $category->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Payment Method --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('accounting.payment_method') }}</label>
                                            <div class="input-group">
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
                                                                <small class="text-muted">({{ __('accounting.default') }})</small>
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Payment Status --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('accounting.payment_status') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-status fs-2"></i>
                                                </span>
                                                <select class="form-select" name="payment_status">
                                                    <option value="">{{ __('accounting.all_statuses') }}</option>
                                                    <option value="pending" {{ $paymentStatus == 'pending' ? 'selected' : '' }}>
                                                        {{ __('accounting.pending') }}
                                                    </option>
                                                    <option value="paid" {{ $paymentStatus == 'paid' ? 'selected' : '' }}>
                                                        {{ __('accounting.paid') }}
                                                    </option>
                                                    <option value="reimbursed" {{ $paymentStatus == 'reimbursed' ? 'selected' : '' }}>
                                                        {{ __('accounting.reimbursed') }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Vendor Name --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('accounting.vendor_name') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-shop fs-2"></i>
                                                </span>
                                                <input type="text" class="form-control" name="vendor_name" 
                                                    value="{{ $vendorName }}" 
                                                    placeholder="{{ __('accounting.enter_vendor_name') }}"
                                                    maxlength="200">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row g-6 mb-6">
                                        {{-- Employee --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('accounting.employee') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-user fs-2"></i>
                                                </span>
                                                <select class="form-select" name="employee_id">
                                                    <option value="">{{ __('accounting.all_employees') }}</option>
                                                    @foreach($employees as $employee)
                                                        <option value="{{ $employee->id }}" 
                                                                {{ $employeeId == $employee->id ? 'selected' : '' }}>
                                                            {{ $employee->first_name }} {{ $employee->last_name }}
                                                            @if($employee->employee_id)
                                                                <small class="text-muted">({{ $employee->employee_id }})</small>
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Requires Receipt --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('accounting.requires_receipt_filter') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-receipt fs-2"></i>
                                                </span>
                                                <select class="form-select" name="requires_receipt">
                                                    <option value="">{{ __('accounting.all_statuses') }}</option>
                                                    <option value="1" {{ $requiresReceipt == '1' ? 'selected' : '' }}>
                                                        {{ __('accounting.yes') }}
                                                    </option>
                                                    <option value="0" {{ $requiresReceipt == '0' ? 'selected' : '' }}>
                                                        {{ __('accounting.no') }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Recurring Expenses --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('accounting.recurring_filter') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-repeat fs-2"></i>
                                                </span>
                                                <select class="form-select" name="is_recurring">
                                                    <option value="">{{ __('accounting.all_statuses') }}</option>
                                                    <option value="1" {{ $isRecurring == '1' ? 'selected' : '' }}>
                                                        {{ __('accounting.yes') }}
                                                    </option>
                                                    <option value="0" {{ $isRecurring == '0' ? 'selected' : '' }}>
                                                        {{ __('accounting.no') }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Action Buttons --}}
                                        <div class="col-md-6 col-lg-6 d-flex align-items-end justify-content-end">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-2"></i>
                                                    {{ __('accounting.apply_filters') }}
                                                </button>
                                                <a href="{{ route('reports.expenses.summary') }}" class="btn btn-light btn-active-light-primary">
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
                @if($summary['total_expenses'] > 0)
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
                                        ['key' => 'total_expenses', 'color' => 'primary', 'icon' => 'ki-receipt', 'label' => 'total_expenses'],
                                        ['key' => 'total_amount', 'color' => 'success', 'icon' => 'ki-dollar', 'label' => 'total_amount'],
                                        ['key' => 'total_tax', 'color' => 'info', 'icon' => 'ki-chart-simple', 'label' => 'total_tax'],
                                        ['key' => 'avg_expense', 'color' => 'warning', 'icon' => 'ki-calculator', 'label' => 'average_expense'],
                                        ['key' => 'max_expense', 'color' => 'danger', 'icon' => 'ki-arrow-up', 'label' => 'largest_expense'],
                                        ['key' => 'min_expense', 'color' => 'secondary', 'icon' => 'ki-arrow-down', 'label' => 'smallest_expense']
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
                                                    @if(in_array($stat['key'], ['total_amount', 'total_tax', 'avg_expense', 'max_expense', 'min_expense']))
                                                    <span class="fs-1 fw-bold text-gray-800">
                                                        ${{ number_format($summary[$stat['key']], 2) }}
                                                    </span>
                                                    @else
                                                    <span class="fs-1 fw-bold text-gray-800">
                                                        {{ number_format($summary[$stat['key']]) }}
                                                    </span>
                                                    @endif
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

                {{-- Top Expenses Table --}}
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
                                        <h3 class="fw-bold m-0">{{ __('accounting.top_expenses') }}</h3>
                                    </div>
                                    @if($topExpenses->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.total') }}: {{ $summary['total_expenses'] }} {{ __('accounting.expenses') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($topExpenses->count() > 0)
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="expensesTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="min-w-100px ps-4">{{ __('accounting.date') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.expense_number') }}</th>
                                                    <th class="min-w-200px">{{ __('accounting.description') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.vendor') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.category') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.amount') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.status') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.payment_method_name') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.employee') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($topExpenses as $expense)
                                                <tr>
                                                    <td class="ps-4">
                                                        <span class="fw-semibold text-gray-800">{{ \Carbon\Carbon::parse($expense->date)->format('Y-m-d') }}</span>
                                                        @if($expense->is_recurring)
                                                        <div class="mt-1">
                                                            <span class="badge badge-light-warning badge-sm">
                                                                <i class="ki-duotone ki-repeat fs-4 me-1"></i>
                                                                {{ __('accounting.recurring') }}
                                                            </span>
                                                        </div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <a href="javascript:void(0)" 
                                                        onclick="showExpenseDetail({{ json_encode($expense) }})" 
                                                        class="text-primary fw-bold text-hover-primary">
                                                            {{ $expense->expense_number }}
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="d-flex justify-content-start flex-column">
                                                                <span class="text-gray-800 fw-semibold">{{ Str::limit($expense->description, 50) }}</span>
                                                                @if($expense->tax_amount > 0)
                                                                <small class="text-muted">
                                                                    {{ __('accounting.tax') }}: ${{ number_format($expense->tax_amount, 2) }}
                                                                </small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-600">{{ $expense->vendor_name ?? 'N/A' }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-info">
                                                            {{ $expense->category->name ?? 'N/A' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">${{ number_format($expense->total_amount, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $statusConfig = [
                                                                'pending' => ['color' => 'warning', 'icon' => 'ki-time'],
                                                                'paid' => ['color' => 'success', 'icon' => 'ki-check'],
                                                                'reimbursed' => ['color' => 'info', 'icon' => 'ki-refresh']
                                                            ];
                                                            $config = $statusConfig[$expense->payment_status] ?? ['color' => 'secondary', 'icon' => 'ki-question'];
                                                        @endphp
                                                        <span class="badge badge-light-{{ $config['color'] }} d-inline-flex align-items-center gap-1">
                                                            <i class="ki-duotone {{ $config['icon'] }} fs-4"></i>
                                                            {{ __('accounting.' . $expense->payment_status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($expense->paymentMethod)
                                                            @if($expense->paymentMethod->is_default)
                                                                <span class="badge badge-light-success d-inline-flex align-items-center gap-1">
                                                                    <i class="ki-duotone ki-star fs-4"></i>
                                                                    {{ $expense->paymentMethod->name }}
                                                                </span>
                                                            @else
                                                                <span class="badge badge-light-primary">
                                                                    {{ $expense->paymentMethod->name }}
                                                                </span>
                                                            @endif
                                                        @else
                                                            <span class="badge badge-light-secondary">
                                                                {{ __('accounting.no_payment_method') }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($expense->employee)
                                                            <div class="d-flex flex-column">
                                                                <span class="fw-semibold text-gray-800">
                                                                    {{ $expense->employee->first_name }} {{ $expense->employee->last_name }}
                                                                </span>
                                                                @if($expense->employee->employee_id)
                                                                    <small class="text-muted">{{ $expense->employee->employee_id }}</small>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
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
                                        <p class="text-muted fs-6">{{ __('accounting.no_expenses_found') }}</p>
                                        @if($summary['total_expenses'] == 0 && request()->hasAny(['category_id', 'payment_method_id', 'payment_status', 'employee_id']))
                                        <a href="{{ route('reports.expenses.summary') }}" class="btn btn-light-primary">
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
                
                {{-- Daily Breakdown --}}
                @if($dailyBreakdown->count() > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-calendar-8 fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.daily_breakdown') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="dailyTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('accounting.date') }}</th>
                                                <th>{{ __('accounting.expense_count') }}</th>
                                                <th>{{ __('accounting.daily_total') }}</th>
                                                <th>{{ __('accounting.daily_tax') }}</th>
                                                <th>{{ __('accounting.daily_average') }}</th>
                                                <th>{{ __('accounting.daily_percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($dailyBreakdown as $daily)
                                            @php
                                                $percentage = $summary['total_amount'] > 0 ? ($daily->total / $summary['total_amount']) * 100 : 0;
                                            @endphp
                                            <tr>
                                                <td class="ps-4 fw-semibold">{{ $daily->date }}</td>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ $daily->count }}</span>
                                                </td>
                                                <td class="fw-bold text-success">${{ number_format($daily->total, 2) }}</td>
                                                <td class="text-info">${{ number_format($daily->tax, 2) }}</td>
                                                <td class="text-gray-600">${{ number_format($daily->average, 2) }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100 me-3" style="height: 6px;">
                                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                                style="width: {{ min($percentage, 100) }}%;" 
                                                                aria-valuenow="{{ $percentage }}" 
                                                                aria-valuemin="0" 
                                                                aria-valuemax="100"></div>
                                                        </div>
                                                        <span class="fw-semibold text-gray-700">{{ number_format($percentage, 1) }}%</span>
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
            </div>
        </div>
    </div>
</div>
@endsection