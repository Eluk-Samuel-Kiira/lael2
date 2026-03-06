{{-- resources/views/reports/expenses/by-employee.blade.php --}}
@extends('layouts.app')

@section('title', __('accounting.expenses_by_employee'))

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
                                {{ __('accounting.expenses_by_employee') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('accounting.employee_analysis') }}</li>
                            </ul>
                        </div>

                        <!-- Right side - Actions -->
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($employeeBreakdown->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'employeeTable', filename: 'expenses_by_employee_{{ date('Y_m_d') }}', sheetName: 'Employee Breakdown'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'employeeTable', filename: 'expenses_by_employee_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.expenses.by-employee') }}" id="filterForm">
                                    {{-- First Row --}}
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 mb-4">
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
                                        
                                        {{-- Employee Selection --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('accounting.employee') }}</label>
                                            <div class="input-group w-100">
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
                                                                ({{ $employee->employee_id }})
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Second Row --}}
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 mb-4">
                                        {{-- Requires Approval --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('accounting.requires_approval') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-shield-tick fs-2"></i>
                                                </span>
                                                <select class="form-select" name="requires_approval">
                                                    <option value="">{{ __('accounting.all_statuses') }}</option>
                                                    <option value="1" {{ $requiresApproval == '1' ? 'selected' : '' }}>
                                                        {{ __('accounting.yes') }}
                                                    </option>
                                                    <option value="0" {{ $requiresApproval == '0' ? 'selected' : '' }}>
                                                        {{ __('accounting.no') }}
                                                    </option>
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
                                                <a href="{{ route('reports.expenses.by-employee') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
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
                @if($employeeBreakdown->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.employee_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $totalExpenses = $employeeBreakdown->sum('grand_total');
                                        $totalEmployees = $employeeBreakdown->count();
                                        $totalTransactions = $employeeBreakdown->sum('expense_count');
                                        $totalTax = $employeeBreakdown->sum('total_tax');
                                        $avgPerEmployee = $totalEmployees > 0 ? $totalExpenses / $totalEmployees : 0;
                                    @endphp
                                    @foreach([
                                        ['key' => 'total_employees', 'color' => 'primary', 'icon' => 'ki-user', 'label' => 'total_employees', 'value' => $totalEmployees],
                                        ['key' => 'total_transactions', 'color' => 'success', 'icon' => 'ki-receipt', 'label' => 'total_transactions', 'value' => $totalTransactions],
                                        ['key' => 'total_amount', 'color' => 'info', 'icon' => 'ki-chart-simple', 'label' => 'grand_total', 'value' => '$' . number_format($totalExpenses, 2)],
                                        ['key' => 'total_tax', 'color' => 'warning', 'icon' => 'ki-receipt-tax', 'label' => 'total_tax', 'value' => '$' . number_format($totalTax, 2)],
                                        ['key' => 'avg_per_employee', 'color' => 'danger', 'icon' => 'ki-calculator', 'label' => 'average_per_employee', 'value' => '$' . number_format($avgPerEmployee, 2)],
                                        ['key' => 'top_employee', 'color' => 'secondary', 'icon' => 'ki-ranking', 'label' => 'top_employee', 'value' => $employeeBreakdown->first()->employee_name ?? 'N/A']
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

                {{-- Employee Breakdown Table --}}
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
                                        <h3 class="fw-bold m-0">{{ __('accounting.employee_breakdown') }}</h3>
                                    </div>
                                    @if($employeeBreakdown->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $employeeBreakdown->count() }} {{ __('accounting.employees') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($employeeBreakdown->count() > 0)
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="employeeTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="min-w-50px ps-4">{{ __('accounting.rank') }}</th>
                                                    <th class="min-w-200px">{{ __('accounting.employee') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.department') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.expenses_count') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.total_amount') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.grand_total') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.average') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.max_expense') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.payment_status') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.distribution') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($employeeBreakdown as $index => $employee)
                                                @php
                                                    $percentage = $totalExpenses > 0 ? ($employee->grand_total / $totalExpenses) * 100 : 0;
                                                    $categories = $employeeCategories[$employee->employee_name] ?? collect();
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
                                                                <div class="symbol-label bg-light-{{ ['primary', 'success', 'info', 'warning', 'danger'][$index % 5] }}">
                                                                    <i class="ki-duotone ki-user fs-2"></i>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-start flex-column">
                                                                <span class="text-gray-800 fw-bold">{{ $employee->employee_name }}</span>
                                                                @if($employee->employee_id)
                                                                <small class="text-muted">
                                                                    {{ __('accounting.id') }}: {{ $employee->employee_id }}
                                                                </small>
                                                                @endif
                                                                @if($categories->isNotEmpty())
                                                                <div class="mt-1">
                                                                    @foreach($categories->take(2) as $category)
                                                                    <span class="badge badge-light-info badge-sm me-1">
                                                                        {{ $category->category_name }} ({{ $category->count }})
                                                                    </span>
                                                                    @endforeach
                                                                    @if($categories->count() > 2)
                                                                    <span class="badge badge-light-secondary badge-sm">
                                                                        +{{ $categories->count() - 2 }}
                                                                    </span>
                                                                    @endif
                                                                </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if($employee->department)
                                                        <span class="badge badge-light-primary">{{ $employee->department }}</span>
                                                        @else
                                                        <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-primary">{{ $employee->expense_count }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-800 fw-semibold">${{ number_format($employee->total_amount, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">${{ number_format($employee->grand_total, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-600">${{ number_format($employee->average_expense, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-danger">${{ number_format($employee->max_expense, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column gap-1">
                                                            @if($employee->pending_count > 0)
                                                            <span class="badge badge-light-warning d-inline-flex align-items-center gap-1">
                                                                <i class="ki-duotone ki-time fs-4"></i>
                                                                {{ __('accounting.pending') }}: {{ $employee->pending_count }}
                                                            </span>
                                                            @endif
                                                            @if($employee->paid_count > 0)
                                                            <span class="badge badge-light-success d-inline-flex align-items-center gap-1">
                                                                <i class="ki-duotone ki-check fs-4"></i>
                                                                {{ __('accounting.paid') }}: {{ $employee->paid_count }}
                                                            </span>
                                                            @endif
                                                            @if($employee->reimbursed_count > 0)
                                                            <span class="badge badge-light-info d-inline-flex align-items-center gap-1">
                                                                <i class="ki-duotone ki-refresh fs-4"></i>
                                                                {{ __('accounting.reimbursed') }}: {{ $employee->reimbursed_count }}
                                                            </span>
                                                            @endif
                                                        </div>
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
                                        <p class="text-muted fs-6">{{ __('accounting.no_expenses_found_for_employees') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'employee_id', 'requires_approval']))
                                        <a href="{{ route('reports.expenses.by-employee') }}" class="btn btn-light-primary">
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
                {{-- END Employee Breakdown Table --}}
                
                {{-- Monthly Employee Spending --}}
                @if($monthlySpending->count() > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.monthly_employee_spending') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="monthlySpendingChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Top 5 Employees Monthly Spending --}}
                <div class="row mt-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-table fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.top_employees_monthly') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('accounting.month_year') }}</th>
                                                <th>{{ __('accounting.employee') }}</th>
                                                <th>{{ __('accounting.transactions') }}</th>
                                                <th>{{ __('accounting.monthly_total') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                // Get top employees by month
                                                $allMonthlyData = [];
                                                foreach($monthlySpending as $employeeName => $months) {
                                                    foreach($months as $month) {
                                                        $allMonthlyData[] = $month;
                                                    }
                                                }
                                                
                                                // Group by month and get top employee per month
                                                $groupedByMonth = collect($allMonthlyData)->groupBy(function($item) {
                                                    return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                                                });
                                                
                                                // Sort months descending
                                                $sortedMonths = $groupedByMonth->sortKeysDesc()->take(12);
                                            @endphp
                                            
                                            @foreach($sortedMonths as $monthKey => $employees)
                                            @php
                                                // Get top employee for this month
                                                $topEmployee = $employees->sortByDesc('monthly_total')->first();
                                                $date = DateTime::createFromFormat('Y-m', $monthKey);
                                            @endphp
                                            <tr>
                                                <td class="ps-4 fw-semibold">{{ $date ? $date->format('M Y') : '' }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-30px symbol-circle me-3">
                                                            <div class="symbol-label bg-light-primary">
                                                                <i class="ki-duotone ki-user fs-2"></i>
                                                            </div>
                                                        </div>
                                                        <span class="text-gray-800 fw-bold">{{ $topEmployee->first_name }} {{ $topEmployee->last_name }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ $topEmployee->transaction_count }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">${{ number_format($topEmployee->monthly_total, 2) }}</span>
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
                {{-- END Monthly Employee Spending --}}
                
            </div>
        </div>
    </div>
</div>
{{-- END Main Content --}}

@push('scripts')
@if($monthlySpending->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get top 5 employees
        const topEmployees = @json($employeeBreakdown->take(5)->pluck('employee_name'));
        const monthlyData = @json(collect($allMonthlyData ?? []));
        
        // Create series data for top 5 employees
        const seriesData = [];
        const monthSet = new Set();
        
        // Get all unique months
        monthlyData.forEach(item => {
            const monthKey = `${item.year}-${String(item.month).padStart(2, '0')}`;
            monthSet.add(monthKey);
        });
        
        const sortedMonths = Array.from(monthSet).sort();
        
        // Prepare data for each top employee
        topEmployees.forEach((employeeName, empIndex) => {
            const employeeData = [];
            
            sortedMonths.forEach(monthKey => {
                const [year, month] = monthKey.split('-');
                const monthlyItem = monthlyData.find(item => {
                    const fullName = `${item.first_name} ${item.last_name}`;
                    return fullName === employeeName && 
                           item.year == year && 
                           item.month == month;
                });
                
                employeeData.push(monthlyItem ? monthlyItem.monthly_total : 0);
            });
            
            seriesData.push({
                name: employeeName,
                data: employeeData,
                type: 'line',
                color: getEmployeeColor(empIndex)
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
        
        const chart = new ApexCharts(document.querySelector("#monthlySpendingChart"), chartOptions);
        chart.render();
        
        // Function to get employee color
        function getEmployeeColor(index) {
            const colors = ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C'];
            return colors[index % colors.length];
        }
    });
</script>
@endif
@endpush

@endsection