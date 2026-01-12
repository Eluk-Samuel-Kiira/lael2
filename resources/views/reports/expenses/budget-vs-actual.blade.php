{{-- resources/views/reports/expenses/budget-vs-actual.blade.php --}}
@extends('layouts.app')

@section('title', __('accounting.budget_vs_actual'))

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
                                {{ __('accounting.budget_vs_actual') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('accounting.budget_analysis') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if(count($budgetData) > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'budgetTable', filename: 'budget_vs_actual_{{ date('Y_m_d') }}', sheetName: 'Budget vs Actual'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'budgetTable', filename: 'budget_vs_actual_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.expenses.budget-vs-actual') }}" id="filterForm">
                                    <div class="row g-6">
                                        {{-- Year Selection --}}
                                        <div class="col-md-6 col-lg-3">
                                            <label class="form-label required fw-semibold">{{ __('accounting.year') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                </span>
                                                <select class="form-select" name="year" required>
                                                    @foreach($years as $y)
                                                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                                            {{ $y }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Month Selection --}}
                                        <div class="col-md-6 col-lg-3">
                                            <label class="form-label required fw-semibold">{{ __('accounting.month') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-calendar fs-2"></i>
                                                </span>
                                                <select class="form-select" name="month" required>
                                                    @foreach($months as $key => $name)
                                                        <option value="{{ $key }}" {{ $month == $key ? 'selected' : '' }}>
                                                            {{ __($name) }}
                                                        </option>
                                                    @endforeach
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
                                                <a href="{{ route('reports.expenses.budget-vs-actual') }}" class="btn btn-light btn-active-light-primary">
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
                @if(count($budgetData) > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.budget_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $totalVarianceMonthly = $summary['total_budget_monthly'] - $summary['total_actual_monthly'];
                                        $totalVarianceAnnual = $summary['total_budget_annual'] - $summary['total_actual_annual'];
                                        $variancePercentageMonthly = $summary['total_budget_monthly'] > 0 ? ($totalVarianceMonthly / $summary['total_budget_monthly']) * 100 : 0;
                                        $variancePercentageAnnual = $summary['total_budget_annual'] > 0 ? ($totalVarianceAnnual / $summary['total_budget_annual']) * 100 : 0;
                                    @endphp
                                    
                                    @foreach([
                                        ['key' => 'total_budget', 'color' => 'primary', 'icon' => 'ki-dollar', 'label' => 'total_budget', 
                                        'monthly_value' => '$' . number_format($summary['total_budget_monthly'], 2),
                                        'annual_value' => '$' . number_format($summary['total_budget_annual'], 2)],
                                        
                                        ['key' => 'total_actual', 'color' => 'info', 'icon' => 'ki-chart-simple', 'label' => 'total_actual',
                                        'monthly_value' => '$' . number_format($summary['total_actual_monthly'], 2),
                                        'annual_value' => '$' . number_format($summary['total_actual_annual'], 2)],
                                        
                                        ['key' => 'total_variance', 'color' => $totalVarianceMonthly >= 0 ? 'success' : 'danger', 
                                        'icon' => 'ki-arrow-up', 'label' => 'total_variance',
                                        'monthly_value' => '$' . number_format(abs($totalVarianceMonthly), 2) . ' ' . ($totalVarianceMonthly >= 0 ? __('accounting.under_budget') : __('accounting.over_budget')),
                                        'annual_value' => '$' . number_format(abs($totalVarianceAnnual), 2) . ' ' . ($totalVarianceAnnual >= 0 ? __('accounting.under_budget') : __('accounting.over_budget'))],
                                        
                                        ['key' => 'variance_percentage', 'color' => $variancePercentageMonthly >= 0 ? 'success' : 'danger', 
                                        'icon' => 'ki-percentage', 'label' => 'variance_percentage',
                                        'monthly_value' => number_format(abs($variancePercentageMonthly), 1) . '% ' . ($variancePercentageMonthly >= 0 ? __('accounting.under') : __('accounting.over')),
                                        'annual_value' => number_format(abs($variancePercentageAnnual), 1) . '% ' . ($variancePercentageAnnual >= 0 ? __('accounting.under') : __('accounting.over'))],
                                        
                                        ['key' => 'categories_under', 'color' => 'success', 'icon' => 'ki-check-circle', 'label' => 'categories_under_budget',
                                        'monthly_value' => $summary['under_budget_count'],
                                        'annual_value' => 'N/A'],
                                        
                                        ['key' => 'categories_over', 'color' => 'danger', 'icon' => 'ki-warning-circle', 'label' => 'categories_over_budget',
                                        'monthly_value' => $summary['over_budget_count'],
                                        'annual_value' => 'N/A']
                                    ] as $index => $stat)
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-{{ $stat['color'] }} border border-{{ $stat['color'] }} border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-3">
                                                    <i class="ki-duotone {{ $stat['icon'] }} fs-2tx text-{{ $stat['color'] }}">
                                                        @for($i = 1; $i <= 2; $i++)
                                                        <span class="path{{ $i }}"></span>
                                                        @endfor
                                                    </i>
                                                </div>
                                                <div class="mb-2">
                                                    <span class="fs-2 fw-bold text-gray-800 d-block">
                                                        {{ $stat['monthly_value'] }}
                                                    </span>
                                                    <small class="text-gray-600 fw-semibold d-block mt-1">
                                                        {{ __('accounting.monthly') }}
                                                    </small>
                                                </div>
                                                @if($stat['annual_value'] != 'N/A')
                                                <div class="border-top border-gray-300 pt-2 mt-2">
                                                    <span class="fs-4 fw-bold text-gray-700 d-block">
                                                        {{ $stat['annual_value'] }}
                                                    </span>
                                                    <small class="text-gray-600 fw-semibold d-block mt-1">
                                                        {{ __('accounting.annual') }}
                                                    </small>
                                                </div>
                                                @endif
                                                <div class="text-gray-600 fw-semibold mt-3">
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

                {{-- Budget vs Actual Table --}}
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
                                        <h3 class="fw-bold m-0">{{ __('accounting.budget_vs_actual_details') }}</h3>
                                    </div>
                                    @if(count($budgetData) > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ count($budgetData) }} {{ __('accounting.categories') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if(count($budgetData) > 0)
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="budgetTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="min-w-200px ps-4">{{ __('accounting.category') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.budget') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.actual') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.variance') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.variance_percentage') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.status') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.progress') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($budgetData as $data)
                                                @php
                                                    $category = $data['category'];
                                                    $varianceMonthly = $data['variance_monthly'];
                                                    $variancePercentageMonthly = $data['variance_percentage_monthly'];
                                                    $isOverBudget = $varianceMonthly < 0;
                                                    $statusColor = $varianceMonthly > 0 ? 'success' : ($varianceMonthly < 0 ? 'danger' : 'warning');
                                                    $statusText = $varianceMonthly > 0 ? __('accounting.under_budget') : ($varianceMonthly < 0 ? __('accounting.over_budget') : __('accounting.on_budget'));
                                                    $progressPercentage = $data['budget_monthly'] > 0 ? min(($data['actual_monthly'] / $data['budget_monthly']) * 100, 100) : 0;
                                                @endphp
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-40px symbol-circle me-3">
                                                                <div class="symbol-label bg-light-info">
                                                                    <i class="ki-duotone ki-category fs-2"></i>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-start flex-column">
                                                                <span class="text-gray-800 fw-bold">{{ $category->name }}</span>
                                                                @if($category->code)
                                                                <small class="text-muted">
                                                                    {{ __('accounting.code') }}: {{ $category->code }}
                                                                </small>
                                                                @endif
                                                                @if($category->description)
                                                                <small class="text-muted">
                                                                    {{ Str::limit($category->description, 50) }}
                                                                </small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="text-primary fw-bold">${{ number_format($data['budget_monthly'], 2) }}</span>
                                                        <div class="mt-1">
                                                            <small class="text-gray-600">
                                                                {{ __('accounting.annual') }}: ${{ number_format($data['budget_annual'], 2) }}
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="text-info fw-bold">${{ number_format($data['actual_monthly'], 2) }}</span>
                                                        <div class="mt-1">
                                                            <small class="text-gray-600">
                                                                {{ __('accounting.annual') }}: ${{ number_format($data['actual_annual'], 2) }}
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-{{ $statusColor }}">
                                                            ${{ number_format(abs($varianceMonthly), 2) }}
                                                            @if($varianceMonthly > 0)
                                                            <i class="ki-duotone ki-arrow-down text-success fs-4 ms-1"></i>
                                                            @elseif($varianceMonthly < 0)
                                                            <i class="ki-duotone ki-arrow-up text-danger fs-4 ms-1"></i>
                                                            @endif
                                                        </span>
                                                        <div class="mt-1">
                                                            <small class="text-gray-600">
                                                                {{ __('accounting.annual') }}: ${{ number_format(abs($data['variance_annual']), 2) }}
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-{{ $statusColor }}">
                                                            {{ number_format(abs($variancePercentageMonthly), 1) }}%
                                                        </span>
                                                        <div class="mt-1">
                                                            <small class="text-gray-600">
                                                                {{ __('accounting.annual') }}: {{ number_format(abs($data['variance_percentage_annual']), 1) }}%
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-{{ $statusColor }} d-inline-flex align-items-center gap-1">
                                                            <i class="ki-duotone ki-{{ $varianceMonthly > 0 ? 'check' : ($varianceMonthly < 0 ? 'warning' : 'information') }} fs-4"></i>
                                                            {{ $statusText }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress w-100 me-3" style="height: 10px;">
                                                                <div class="progress-bar bg-{{ $progressPercentage > 100 ? 'danger' : ($progressPercentage > 80 ? 'warning' : 'success') }}" 
                                                                    role="progressbar" 
                                                                    style="width: {{ min($progressPercentage, 100) }}%;" 
                                                                    aria-valuenow="{{ $progressPercentage }}" 
                                                                    aria-valuemin="0" 
                                                                    aria-valuemax="100">
                                                                </div>
                                                            </div>
                                                            <span class="fw-bold text-gray-700 min-w-60px text-end">
                                                                {{ number_format($progressPercentage, 1) }}%
                                                            </span>
                                                        </div>
                                                        <div class="mt-1 text-center">
                                                            <small class="text-muted">
                                                                {{ __('accounting.utilization') }}
                                                            </small>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="fw-bold bg-light">
                                                <tr>
                                                    <td class="ps-4">{{ __('accounting.total') }}</td>
                                                    <td class="text-primary">${{ number_format($summary['total_budget_monthly'], 2) }}</td>
                                                    <td class="text-info">${{ number_format($summary['total_actual_monthly'], 2) }}</td>
                                                    <td class="text-{{ $totalVarianceMonthly >= 0 ? 'success' : 'danger' }}">
                                                        ${{ number_format(abs($totalVarianceMonthly), 2) }}
                                                        @if($totalVarianceMonthly > 0)
                                                        <i class="ki-duotone ki-arrow-down text-success fs-4 ms-1"></i>
                                                        @elseif($totalVarianceMonthly < 0)
                                                        <i class="ki-duotone ki-arrow-up text-danger fs-4 ms-1"></i>
                                                        @endif
                                                    </td>
                                                    <td class="text-{{ $variancePercentageMonthly >= 0 ? 'success' : 'danger' }}">
                                                        {{ number_format(abs($variancePercentageMonthly), 1) }}%
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-{{ $totalVarianceMonthly >= 0 ? 'success' : 'danger' }}">
                                                            {{ $totalVarianceMonthly >= 0 ? __('accounting.under_budget') : __('accounting.over_budget') }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $totalProgress = $summary['total_budget_monthly'] > 0 ? 
                                                                min(($summary['total_actual_monthly'] / $summary['total_budget_monthly']) * 100, 100) : 0;
                                                        @endphp
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress w-100 me-3" style="height: 10px;">
                                                                <div class="progress-bar bg-{{ $totalProgress > 100 ? 'danger' : ($totalProgress > 80 ? 'warning' : 'success') }}" 
                                                                    role="progressbar" 
                                                                    style="width: {{ $totalProgress }}%;" 
                                                                    aria-valuenow="{{ $totalProgress }}" 
                                                                    aria-valuemin="0" 
                                                                    aria-valuemax="100">
                                                                </div>
                                                            </div>
                                                            <span class="fw-bold text-gray-700 min-w-60px text-end">
                                                                {{ number_format($totalProgress, 1) }}%
                                                            </span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tfoot>
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
                                        <p class="text-muted fs-6">{{ __('accounting.no_budget_categories_found') }}</p>
                                        <p class="text-muted fs-7 mb-4">
                                            {{ __('accounting.set_up_budgets_message') }}
                                        </p>
                                        @if(request()->hasAny(['year', 'month']))
                                        <a href="{{ route('reports.expenses.budget-vs-actual') }}" class="btn btn-light-primary">
                                            <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                            {{ __('accounting.clear_filters') }}
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- Monthly Trends Chart --}}
                @if(count($monthlyTrends) > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.monthly_budget_trends') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="budgetTrendsChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Top Categories Performance --}}
                <div class="row mt-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-ranking fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.top_performing_categories') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('accounting.category') }}</th>
                                                <th>{{ __('accounting.budget_utilization') }}</th>
                                                <th>{{ __('accounting.variance') }}</th>
                                                <th>{{ __('accounting.trend') }}</th>
                                                <th>{{ __('accounting.recommendation') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $sortedCategories = collect($budgetData)->sortBy('variance_percentage_monthly');
                                            @endphp
                                            
                                            @foreach($sortedCategories->take(5) as $data)
                                            @php
                                                $category = $data['category'];
                                                $varianceMonthly = $data['variance_monthly'];
                                                $variancePercentageMonthly = $data['variance_percentage_monthly'];
                                                $progressPercentage = $data['budget_monthly'] > 0 ? min(($data['actual_monthly'] / $data['budget_monthly']) * 100, 100) : 0;
                                                $isOverBudget = $varianceMonthly < 0;
                                                $statusColor = $varianceMonthly > 0 ? 'success' : ($varianceMonthly < 0 ? 'danger' : 'warning');
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-30px symbol-circle me-3">
                                                            <div class="symbol-label bg-light-info">
                                                                <i class="ki-duotone ki-category fs-2"></i>
                                                            </div>
                                                        </div>
                                                        <span class="text-gray-800 fw-bold">{{ $category->name }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100 me-3" style="height: 8px;">
                                                            <div class="progress-bar bg-{{ $progressPercentage > 100 ? 'danger' : ($progressPercentage > 80 ? 'warning' : 'success') }}" 
                                                                style="width: {{ min($progressPercentage, 100) }}%;">
                                                            </div>
                                                        </div>
                                                        <span class="fw-bold text-gray-700">
                                                            {{ number_format($progressPercentage, 1) }}%
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $statusColor }}">
                                                        {{ number_format(abs($variancePercentageMonthly), 1) }}%
                                                        {{ $varianceMonthly > 0 ? __('accounting.under') : __('accounting.over') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($variancePercentageMonthly > 10)
                                                    <span class="badge badge-light-success d-inline-flex align-items-center gap-1">
                                                        <i class="ki-duotone ki-arrow-down fs-4"></i>
                                                        {{ __('accounting.under_spending') }}
                                                    </span>
                                                    @elseif($variancePercentageMonthly < -10)
                                                    <span class="badge badge-light-danger d-inline-flex align-items-center gap-1">
                                                        <i class="ki-duotone ki-arrow-up fs-4"></i>
                                                        {{ __('accounting.over_spending') }}
                                                    </span>
                                                    @else
                                                    <span class="badge badge-light-warning d-inline-flex align-items-center gap-1">
                                                        <i class="ki-duotone ki-check fs-4"></i>
                                                        {{ __('accounting.on_track') }}
                                                    </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($variancePercentageMonthly < -20)
                                                    <span class="text-danger fw-semibold">
                                                        {{ __('accounting.reduce_spending') }}
                                                    </span>
                                                    @elseif($variancePercentageMonthly > 20)
                                                    <span class="text-success fw-semibold">
                                                        {{ __('accounting.budget_surplus') }}
                                                    </span>
                                                    @else
                                                    <span class="text-info fw-semibold">
                                                        {{ __('accounting.maintain_current') }}
                                                    </span>
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
                
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if(count($monthlyTrends) > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Prepare data for top 5 categories
        const topCategories = @json(collect($budgetData)->take(5));
        const monthlyTrends = @json($monthlyTrends);
        
        // Create series for budget and actual
        const budgetSeries = [];
        const actualSeries = [];
        const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        // Prepare data for each top category
        topCategories.forEach((data, index) => {
            const category = data.category;
            const trend = monthlyTrends[category.id];
            
            if (trend) {
                const budgetData = [];
                const actualData = [];
                
                for (let month = 1; month <= 12; month++) {
                    budgetData.push(trend[month] ? trend[month].budget : 0);
                    actualData.push(trend[month] ? trend[month].actual : 0);
                }
                
                budgetSeries.push({
                    name: `${category.name} - ${'{{ __("accounting.budget") }}'}`,
                    data: budgetData,
                    type: 'line',
                    color: getCategoryColor(index)
                });
                
                actualSeries.push({
                    name: `${category.name} - ${'{{ __("accounting.actual") }}'}`,
                    data: actualData,
                    type: 'line',
                    color: getCategoryColor(index, true)
                });
            }
        });
        
        // Combine series
        const allSeries = [...budgetSeries, ...actualSeries];
        
        // Initialize chart
        const chartOptions = {
            series: allSeries,
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
                width: [3, 2, 2],
                curve: 'smooth',
                dashArray: [0, 5, 5] // Solid for budget, dashed for actual
            },
            xaxis: {
                categories: monthLabels,
                labels: {
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
                fontSize: '12px',
                fontFamily: 'Helvetica, Arial',
                itemMargin: {
                    horizontal: 10,
                    vertical: 5
                }
            },
            markers: {
                size: 4
            },
            grid: {
                borderColor: '#f1f1f1',
            },
            dataLabels: {
                enabled: false
            }
        };
        
        const chart = new ApexCharts(document.querySelector("#budgetTrendsChart"), chartOptions);
        chart.render();
        
        // Function to get category colors
        function getCategoryColor(index, isActual = false) {
            const colors = ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C'];
            const color = colors[index % colors.length];
            
            if (isActual) {
                // Make actual lines slightly lighter or with opacity
                return color + '80'; // Add 50% opacity
            }
            return color;
        }
    });
</script>
@endif
@endpush

@endsection