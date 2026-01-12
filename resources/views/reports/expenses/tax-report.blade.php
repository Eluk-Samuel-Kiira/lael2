{{-- resources/views/reports/expenses/tax-report.blade.php --}}
@extends('layouts.app')

@section('title', __('accounting.tax_report'))

@section('content')
<div class="container-fluid">
    {{-- Toolbar Section --}}
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    {{ __('accounting.tax_report') }}
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
                    <li class="breadcrumb-item text-muted">{{ __('accounting.tax_analysis') }}</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                @if($taxSummary['total_expenses'] > 0)
                <div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" 
                               onclick="exportCurrentPage({tableId: 'taxSummaryTable', filename: 'tax_report_{{ date('Y_m_d') }}', sheetName: 'Tax Summary'})">
                                <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                {{ __('accounting.export_to_excel') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" 
                               onclick="exportCurrentPage({tableId: 'taxSummaryTable', filename: 'tax_report_{{ date('Y_m_d') }}', format: 'csv'})">
                                <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                {{ __('accounting.export_to_csv') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="generateTaxReportPDF()">
                                <i class="ki-duotone ki-file-pdf fs-2 me-2 text-danger"></i>
                                {{ __('accounting.export_to_pdf') }}
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
                    <form method="GET" action="{{ route('reports.expenses.tax-report') }}" id="filterForm">
                        <div class="row g-6">
                            {{-- Date Range --}}
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label fw-semibold">{{ __('accounting.start_date') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="ki-duotone ki-calendar fs-2"></i>
                                    </span>
                                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                                </div>
                            </div>
                            
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label fw-semibold">{{ __('accounting.end_date') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="ki-duotone ki-calendar fs-2"></i>
                                    </span>
                                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
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
                                            <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            {{-- Tax Type --}}
                            <div class="col-md-6 col-lg-2">
                                <label class="form-label fw-semibold">{{ __('accounting.tax_type') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="ki-duotone ki-dollar fs-2"></i>
                                    </span>
                                    <select class="form-select" name="tax_type">
                                        <option value="all" {{ $taxType == 'all' ? 'selected' : '' }}>
                                            {{ __('accounting.all_tax_types') }}
                                        </option>
                                        <option value="taxable" {{ $taxType == 'taxable' ? 'selected' : '' }}>
                                            {{ __('accounting.taxable_only') }}
                                        </option>
                                        <option value="non-taxable" {{ $taxType == 'non-taxable' ? 'selected' : '' }}>
                                            {{ __('accounting.non_taxable_only') }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            
                            {{-- Action Buttons --}}
                            <div class="col-md-12 col-lg-2 d-flex align-items-end">
                                <div class="d-flex gap-2 w-100">
                                    <button type="submit" class="btn btn-primary flex-fill" id="applyFilters">
                                        <i class="ki-duotone ki-filter fs-2 me-2"></i>
                                        {{ __('accounting.apply_filters') }}
                                    </button>
                                    <a href="{{ route('reports.expenses.tax-report') }}" class="btn btn-light btn-active-light-primary">
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

    {{-- Tax Summary --}}
    @if($taxSummary['total_expenses'] > 0)
    <div class="row mb-6">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="card-title d-flex align-items-center">
                        <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <h3 class="fw-bold m-0">{{ __('accounting.tax_summary') }}</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-6" id="taxSummaryTable">
                        @foreach([
                            ['key' => 'total_expenses', 'color' => 'primary', 'icon' => 'ki-receipt', 'label' => 'total_expenses', 'value' => $taxSummary['total_expenses']],
                            ['key' => 'total_amount', 'color' => 'success', 'icon' => 'ki-dollar', 'label' => 'subtotal', 'value' => '$' . number_format($taxSummary['total_amount'], 2)],
                            ['key' => 'total_tax', 'color' => 'danger', 'icon' => 'ki-dollar-circle', 'label' => 'total_tax', 'value' => '$' . number_format($taxSummary['total_tax'], 2)],
                            ['key' => 'total_with_tax', 'color' => 'info', 'icon' => 'ki-dollar-up', 'label' => 'total_with_tax', 'value' => '$' . number_format($taxSummary['total_with_tax'], 2)],
                            ['key' => 'taxable_expenses', 'color' => 'warning', 'icon' => 'ki-dollar', 'label' => 'taxable_expenses', 'value' => $taxSummary['taxable_expenses']],
                            ['key' => 'non_taxable_expenses', 'color' => 'secondary', 'icon' => 'ki-dollar', 'label' => 'non_taxable_expenses', 'value' => $taxSummary['non_taxable_expenses']],
                            ['key' => 'avg_tax_rate', 'color' => 'dark', 'icon' => 'ki-percentage', 'label' => 'average_tax_rate', 'value' => number_format($taxSummary['avg_tax_rate'] ?? 0, 2) . '%'],
                            ['key' => 'tax_percentage', 'color' => 'primary', 'icon' => 'ki-chart', 'label' => 'tax_percentage_of_total', 'value' => $taxSummary['total_amount'] > 0 ? number_format(($taxSummary['total_tax'] / $taxSummary['total_amount']) * 100, 2) . '%' : '0%']
                        ] as $stat)
                        <div class="col-md-6 col-lg-3">
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

    {{-- Tax by Category --}}
    @if($taxByCategory->count() > 0)
    <div class="row mb-6">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="card-title d-flex align-items-center">
                        <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <h3 class="fw-bold m-0">{{ __('accounting.tax_by_category') }}</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                            <thead>
                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('accounting.category') }}</th>
                                    <th>{{ __('accounting.expense_count') }}</th>
                                    <th>{{ __('accounting.subtotal') }}</th>
                                    <th>{{ __('accounting.tax_amount') }}</th>
                                    <th>{{ __('accounting.total_amount') }}</th>
                                    <th>{{ __('accounting.average_tax_rate') }}</th>
                                    <th>{{ __('accounting.taxable_count') }}</th>
                                    <th>{{ __('accounting.non_taxable_count') }}</th>
                                    <th>{{ __('accounting.tax_percentage') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($taxByCategory as $category)
                                @php
                                    $taxPercentage = $category->subtotal > 0 ? ($category->tax_total / $category->subtotal) * 100 : 0;
                                @endphp
                                <tr>
                                    <td class="ps-4 fw-semibold">
                                        <span class="badge badge-light-primary">{{ $category->category_name }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-info">{{ $category->expense_count }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-gray-700">${{ number_format($category->subtotal, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-danger">${{ number_format($category->tax_total, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">${{ number_format($category->grand_total, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold {{ $category->avg_tax_rate > 0 ? 'text-warning' : 'text-muted' }}">
                                            {{ number_format($category->avg_tax_rate ?? 0, 2) }}%
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-warning">{{ $category->taxable_count }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-secondary">{{ $category->non_taxable_count }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress w-100 me-3" style="height: 20px;">
                                                <div class="progress-bar bg-danger" role="progressbar" 
                                                     style="width: {{ min($taxPercentage, 100) }}%" 
                                                     aria-valuenow="{{ $taxPercentage }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                            <span class="fw-bold">{{ number_format($taxPercentage, 1) }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold fs-6 text-gray-800 border-top border-gray-200">
                                    <td class="ps-4">{{ __('accounting.total') }}</td>
                                    <td>{{ $taxByCategory->sum('expense_count') }}</td>
                                    <td>${{ number_format($taxByCategory->sum('subtotal'), 2) }}</td>
                                    <td class="text-danger">${{ number_format($taxByCategory->sum('tax_total'), 2) }}</td>
                                    <td class="text-success">${{ number_format($taxByCategory->sum('grand_total'), 2) }}</td>
                                    <td>
                                        @php
                                            $totalSubtotal = $taxByCategory->sum('subtotal');
                                            $totalTax = $taxByCategory->sum('tax_total');
                                            $avgRate = $totalSubtotal > 0 ? ($totalTax / $totalSubtotal) * 100 : 0;
                                        @endphp
                                        {{ number_format($avgRate, 2) }}%
                                    </td>
                                    <td>{{ $taxByCategory->sum('taxable_count') }}</td>
                                    <td>{{ $taxByCategory->sum('non_taxable_count') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Monthly Tax Breakdown --}}
    @if($monthlyTax->count() > 0)
    <div class="row mb-6">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="card-title d-flex align-items-center">
                        <i class="ki-duotone ki-calendar-8 fs-2 me-2 text-primary">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <h3 class="fw-bold m-0">{{ __('accounting.monthly_tax_breakdown') }}</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                            <thead>
                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('accounting.period') }}</th>
                                    <th>{{ __('accounting.expense_count') }}</th>
                                    <th>{{ __('accounting.subtotal') }}</th>
                                    <th>{{ __('accounting.tax_amount') }}</th>
                                    <th>{{ __('accounting.total_amount') }}</th>
                                    <th>{{ __('accounting.average_tax_rate') }}</th>
                                    <th>{{ __('accounting.tax_percentage') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($monthlyTax as $month)
                                @php
                                    $monthName = \Carbon\Carbon::create($month->year, $month->month, 1)->format('M Y');
                                    $taxPercentage = $month->monthly_subtotal > 0 ? ($month->monthly_tax / $month->monthly_subtotal) * 100 : 0;
                                @endphp
                                <tr>
                                    <td class="ps-4 fw-semibold">
                                        {{ $monthName }}
                                    </td>
                                    <td>
                                        <span class="badge badge-light-info">{{ $month->expense_count }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-gray-700">${{ number_format($month->monthly_subtotal, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-danger">${{ number_format($month->monthly_tax, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">${{ number_format($month->monthly_total, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold {{ $month->avg_monthly_tax_rate > 0 ? 'text-warning' : 'text-muted' }}">
                                            {{ number_format($month->avg_monthly_tax_rate ?? 0, 2) }}%
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress w-100 me-3" style="height: 15px;">
                                                <div class="progress-bar bg-danger" role="progressbar" 
                                                     style="width: {{ min($taxPercentage, 100) }}%" 
                                                     aria-valuenow="{{ $taxPercentage }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                            <span class="fw-bold fs-7">{{ number_format($taxPercentage, 1) }}%</span>
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

    {{-- Top Tax Expenses --}}
    @if($topTaxExpenses->count() > 0)
    <div class="row mb-6">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="card-title d-flex align-items-center">
                        <i class="ki-duotone ki-chart-line-down fs-2 me-2 text-primary">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <h3 class="fw-bold m-0">{{ __('accounting.top_tax_expenses') }}</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                            <thead>
                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('accounting.date') }}</th>
                                    <th>{{ __('accounting.description') }}</th>
                                    <th>{{ __('accounting.category') }}</th>
                                    <th>{{ __('accounting.vendor') }}</th>
                                    <th>{{ __('accounting.amount') }}</th>
                                    <th>{{ __('accounting.tax_amount') }}</th>
                                    <th>{{ __('accounting.total_amount') }}</th>
                                    <th>{{ __('accounting.tax_rate') }}</th>
                                    <th>{{ __('accounting.payment_method') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topTaxExpenses as $expense)
                                @php
                                    $taxRate = $expense->amount > 0 ? ($expense->tax_amount / $expense->amount) * 100 : 0;
                                @endphp
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-semibold">{{ $expense->date->format('Y-m-d') }}</span>
                                        <div class="mt-1">
                                            <small class="text-muted">{{ $expense->date->format('D') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-gray-800 fw-bold">{{ Str::limit($expense->description, 50) }}</span>
                                        @if($expense->expense_number)
                                        <div class="mt-1">
                                            <small class="text-muted">#{{ $expense->expense_number }}</small>
                                        </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-light-primary">
                                            {{ $expense->category->name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-gray-700">{{ $expense->vendor_name ?: '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-gray-700">${{ number_format($expense->amount, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-danger">${{ number_format($expense->tax_amount, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">${{ number_format($expense->total_amount, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-warning">{{ number_format($taxRate, 2) }}%</span>
                                    </td>
                                    <td>
                                        @if($expense->paymentMethod)
                                            <span class="badge badge-light-info">
                                                {{ $expense->paymentMethod->name }}
                                            </span>
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
            </div>
        </div>
    </div>
    @endif

    {{-- Tax Rate Distribution --}}
    @if($taxRateDistribution->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="card-title d-flex align-items-center">
                        <i class="ki-duotone ki-chart-pie-simple fs-2 me-2 text-primary">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <h3 class="fw-bold m-0">{{ __('accounting.tax_rate_distribution') }}</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row">
                        <div class="col-md-8">
                            <div id="taxRateChart" style="height: 300px;"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="table-responsive">
                                <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead>
                                        <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                            <th class="ps-4">{{ __('accounting.tax_rate') }}</th>
                                            <th>{{ __('accounting.expense_count') }}</th>
                                            <th>{{ __('accounting.total_tax') }}</th>
                                            <th>{{ __('accounting.percentage') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($taxRateDistribution as $distribution)
                                        @php
                                            $percentage = $taxRateDistribution->sum('expense_count') > 0 ? 
                                                ($distribution->expense_count / $taxRateDistribution->sum('expense_count')) * 100 : 0;
                                        @endphp
                                        <tr>
                                            <td class="ps-4 fw-semibold">
                                                <span class="badge badge-light-primary">{{ $distribution->tax_rate_percent }}%</span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold">{{ $distribution->expense_count }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-danger">${{ number_format($distribution->total_tax, 2) }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-info">{{ number_format($percentage, 1) }}%</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold fs-6 text-gray-800 border-top border-gray-200">
                                            <td class="ps-4">{{ __('accounting.total') }}</td>
                                            <td>{{ $taxRateDistribution->sum('expense_count') }}</td>
                                            <td class="text-danger">${{ number_format($taxRateDistribution->sum('total_tax'), 2) }}</td>
                                            <td>100%</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- No Data Message --}}
    @if($taxSummary['total_expenses'] == 0)
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
                        <p class="text-muted fs-6">{{ __('accounting.no_tax_data_found') }}</p>
                        @if(request()->hasAny(['start_date', 'end_date', 'category_id', 'tax_type']))
                        <a href="{{ route('reports.expenses.tax-report') }}" class="btn btn-light-primary">
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

@push('scripts')
@if($taxRateDistribution->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tax Rate Distribution Chart
        const taxRateData = @json($taxRateDistribution);
        
        const taxRates = taxRateData.map(item => item.tax_rate_percent + '%');
        const expenseCounts = taxRateData.map(item => item.expense_count);
        const taxAmounts = taxRateData.map(item => parseFloat(item.total_tax));
        
        const chartOptions = {
            series: expenseCounts,
            chart: {
                type: 'donut',
                height: 300
            },
            labels: taxRates,
            colors: ['#3E97FF', '#50CD89', '#F1416C', '#7239EA', '#FFC700', '#00B2FF', '#00E396', '#FF4560'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '60%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Expenses',
                                formatter: function() {
                                    return expenseCounts.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            },
            legend: {
                position: 'bottom'
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return value + ' expenses';
                    }
                }
            }
        };
        
        const chart = new ApexCharts(document.querySelector("#taxRateChart"), chartOptions);
        chart.render();
    });
    
    // PDF Export Function
    function generateTaxReportPDF() {
        // You can implement PDF generation using a library like jsPDF
        // or make an AJAX call to a backend PDF generation endpoint
        alert('PDF export feature would be implemented here. This could use jsPDF or a server-side library.');
        
        // Example: window.open('/reports/expenses/tax-report/pdf?start_date={{ $startDate }}&end_date={{ $endDate }}');
    }
</script>
@endif
@endpush

@endsection