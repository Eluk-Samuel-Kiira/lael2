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
                                    <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form method="GET" action="{{ route('reports.expenses.by-employee') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 mb-4">
                                        <div class="flex-grow-1">
                                            <label class="form-label required fw-semibold">{{ __('accounting.date_range') }}</label>
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <div class="input-group w-100">
                                                    <span class="input-group-text"><i class="ki-duotone ki-calendar-8 fs-2"></i></span>
                                                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}" required>
                                                </div>
                                                <span class="d-none d-sm-flex align-items-center text-gray-500 px-2">{{ __('accounting.to') }}</span>
                                                <div class="input-group w-100">
                                                    <span class="input-group-text bg-light"><i class="ki-duotone ki-calendar-8 fs-2"></i></span>
                                                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('accounting.employee') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text"><i class="ki-duotone ki-user fs-2"></i></span>
                                                <select class="form-select" name="employee_id">
                                                    <option value="">{{ __('accounting.all_employees') }}</option>
                                                    @foreach($employees as $employee)
                                                        <option value="{{ $employee->id }}" {{ $employeeId == $employee->id ? 'selected' : '' }}>
                                                            {{ $employee->first_name }} {{ $employee->last_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 mb-4">
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('accounting.requires_approval') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text"><i class="ki-duotone ki-shield-tick fs-2"></i></span>
                                                <select class="form-select" name="requires_approval">
                                                    <option value="">{{ __('accounting.all_statuses') }}</option>
                                                    <option value="1" {{ $requiresApproval == '1' ? 'selected' : '' }}>{{ __('accounting.yes') }}</option>
                                                    <option value="0" {{ $requiresApproval == '0' ? 'selected' : '' }}>{{ __('accounting.no') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex flex-column justify-content-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <button type="submit" class="btn btn-primary flex-grow-1">
                                                    <i class="ki-duotone ki-filter fs-2 me-1"></i>
                                                    <span>{{ __('accounting.apply_filters') }}</span>
                                                </button>
                                                <a href="{{ route('reports.expenses.by-employee') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
                                                    <i class="ki-duotone ki-cross fs-2 me-1"></i>
                                                    <span>{{ __('accounting.clear_filters') }}</span>
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
                @php
                    $totalExpenses = $employeeBreakdown->sum('grand_total');
                    $totalEmployees = $employeeBreakdown->count();
                    $totalTransactions = $employeeBreakdown->sum('expense_count');
                    $totalTax = $employeeBreakdown->sum('total_tax');
                    $avgPerEmployee = $totalEmployees > 0 ? $totalExpenses / $totalEmployees : 0;
                    $topEmployee = $employeeBreakdown->first();
                @endphp
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.employee_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-user fs-2tx text-primary"></i></div>
                                                <div class="mb-1"><span class="fs-1 fw-bold text-gray-800">{{ $totalEmployees }}</span></div>
                                                <div class="text-gray-600 fw-semibold">{{ __('accounting.total_employees') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-receipt fs-2tx text-success"></i></div>
                                                <div class="mb-1"><span class="fs-1 fw-bold text-gray-800">{{ number_format($totalTransactions) }}</span></div>
                                                <div class="text-gray-600 fw-semibold">{{ __('accounting.total_transactions') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-chart-simple fs-2tx text-info"></i></div>
                                                <div class="mb-1"><span class="fs-1 fw-bold text-gray-800">${{ number_format($totalExpenses, 2) }}</span></div>
                                                <div class="text-gray-600 fw-semibold">{{ __('accounting.grand_total') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-receipt-tax fs-2tx text-warning"></i></div>
                                                <div class="mb-1"><span class="fs-1 fw-bold text-gray-800">${{ number_format($totalTax, 2) }}</span></div>
                                                <div class="text-gray-600 fw-semibold">{{ __('accounting.total_tax') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-calculator fs-2tx text-danger"></i></div>
                                                <div class="mb-1"><span class="fs-1 fw-bold text-gray-800">${{ number_format($avgPerEmployee, 2) }}</span></div>
                                                <div class="text-gray-600 fw-semibold">{{ __('accounting.average_per_employee') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-secondary border border-secondary border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-ranking fs-2tx text-secondary"></i></div>
                                                <div class="mb-1"><span class="fs-1 fw-bold text-gray-800">{{ $topEmployee->employee_name ?? 'N/A' }}</span></div>
                                                <div class="text-gray-600 fw-semibold">{{ __('accounting.top_employee') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Employee Breakdown Table --}}
                <div class="card mt-6">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.employee_breakdown') }}</h3>
                        @if($employeeBreakdown->count() > 0)
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">{{ __('accounting.showing') }} {{ $employeeBreakdown->count() }} {{ __('accounting.employees') }}</span>
                        </div>
                        @endif
                    </div>
                    
                    @if($employeeBreakdown->count() > 0)
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0" id="employeeTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50" class="text-center">#</th>
                                        <th>{{ __('accounting.employee') }}</th>
                                        <th width="150">{{ __('accounting.department') }}</th>
                                        <th width="120" class="text-center">{{ __('accounting.expenses_count') }}</th>
                                        <th width="150" class="text-end">{{ __('accounting.grand_total') }}</th>
                                        <th width="120" class="text-end">{{ __('accounting.average') }}</th>
                                        <th width="120" class="text-end">{{ __('accounting.max_expense') }}</th>
                                        <th width="180">{{ __('accounting.payment_status') }}</th>
                                        <th width="150" class="text-center">{{ __('accounting.distribution') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($employeeBreakdown as $index => $employee)
                                    @php
                                        $percentage = $totalExpenses > 0 ? round(($employee->grand_total / $totalExpenses) * 100, 1) : 0;
                                    @endphp
                                    <tr>
                                        <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-35px symbol-circle me-3">
                                                    <div class="symbol-label bg-light-primary">
                                                        <i class="ki-duotone ki-user fs-2"></i>
                                                    </div>
                                                </div>
                                                <span class="fw-bold">{{ $employee->employee_name }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $employee->department ?? 'N/A' }}</td>
                                        <td class="text-center"><span class="badge badge-light-primary">{{ $employee->expense_count }}</span></td>
                                        <td class="text-end fw-bold text-success">{{ currency_symbol() }}{{ number_format($employee->grand_total, 2) }}</td>
                                        <td class="text-end text-gray-600">{{ currency_symbol() }}{{ number_format($employee->average_expense, 2) }}</td>
                                        <td class="text-end text-danger">{{ currency_symbol() }}{{ number_format($employee->max_expense, 2) }}</td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                @if($employee->pending_count > 0)
                                                <span class="badge badge-light-warning">Pending: {{ $employee->pending_count }}</span>
                                                @endif
                                                @if($employee->paid_count > 0)
                                                <span class="badge badge-light-success">Paid: {{ $employee->paid_count }}</span>
                                                @endif
                                                @if($employee->reimbursed_count > 0)
                                                <span class="badge badge-light-info">Reimbursed: {{ $employee->reimbursed_count }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px;">
                                                    <div class="progress-bar bg-primary" style="width: {{ $percentage }}%;"></div>
                                                </div>
                                                <span class="fw-bold min-w-45px">{{ $percentage }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <td colspan="4" class="text-end">{{ __('accounting.total') }}:</td>
                                        <td class="text-end text-success">{{ currency_symbol() }}{{ number_format($employeeBreakdown->sum('grand_total'), 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($employeeBreakdown->avg('average_expense'), 2) }}</td>
                                        <td class="text-end text-danger">{{ currency_symbol() }}{{ number_format($employeeBreakdown->max('max_expense'), 2) }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    @else
                    <div class="card-body">
                        <div class="text-center py-10">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('accounting.no_expenses_found_for_employees') }}</p>
                        </div>
                    </div>
                    @endif
                </div>
                                
                {{-- Monthly Employee Spending Chart --}}
                @if($monthlySpending->count() > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.monthly_employee_spending') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="monthlySpendingChart" style="height: 400px;"></div>
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
@if($monthlySpending->count() > 0 && isset($allMonthlyData) && count($allMonthlyData) > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const topEmployees = @json($employeeBreakdown->take(5)->pluck('employee_name'));
        const monthlyData = @json($allMonthlyData ?? []);
        
        const seriesData = [];
        const monthSet = new Set();
        
        monthlyData.forEach(item => {
            const monthKey = `${item.year}-${String(item.month).padStart(2, '0')}`;
            monthSet.add(monthKey);
        });
        
        const sortedMonths = Array.from(monthSet).sort();
        
        topEmployees.forEach((employeeName, empIndex) => {
            const employeeData = [];
            sortedMonths.forEach(monthKey => {
                const [year, month] = monthKey.split('-');
                const monthlyItem = monthlyData.find(item => 
                    item.employee_name === employeeName && 
                    item.year == year && 
                    item.month == month
                );
                employeeData.push(monthlyItem ? monthlyItem.monthly_total : 0);
            });
            
            const colors = ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C'];
            seriesData.push({
                name: employeeName,
                data: employeeData,
                type: 'line',
                color: colors[empIndex % colors.length]
            });
        });
        
        const monthLabels = sortedMonths.map(monthKey => {
            const [year, month] = monthKey.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
        });
        
        const chartOptions = {
            series: seriesData,
            chart: { type: 'line', height: 400, toolbar: { show: true } },
            stroke: { width: 3, curve: 'smooth' },
            xaxis: { categories: monthLabels, labels: { rotate: -45 } },
            yaxis: { title: { text: 'Amount ($)' }, labels: { formatter: (val) => '$' + val.toLocaleString() } },
            tooltip: { y: { formatter: (val) => '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2}) } },
            legend: { position: 'top', horizontalAlign: 'center' },
            markers: { size: 5 },
            grid: { borderColor: '#f1f1f1' }
        };
        
        const chart = new ApexCharts(document.querySelector("#monthlySpendingChart"), chartOptions);
        chart.render();
    });
</script>
@endif
@endpush

@endsection