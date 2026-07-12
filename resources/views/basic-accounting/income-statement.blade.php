<x-app-layout>
    @section('title', __('accounting.income_statement'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <!-- Left side - Title and Breadcrumb -->
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{__('accounting.income_statement')}}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.income-statement') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.basic_accounting') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('accounting.income_statement')}}</li>
                </ul>
            </div>

            <!-- Right side - Actions -->
            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                <form method="GET" action="{{ route('accounting.income-statement') }}" class="w-100">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <!-- Period Selector -->
                        <select name="period" class="form-select form-select-solid w-100 w-sm-auto" style="min-width: 140px;" onchange="this.form.submit()">
                            <option value="month" {{ $period === 'month' ? 'selected' : '' }}>{{ __('accounting.this_month') }}</option>
                            <option value="quarter" {{ $period === 'quarter' ? 'selected' : '' }}>{{ __('accounting.this_quarter') }}</option>
                            <option value="year" {{ $period === 'year' ? 'selected' : '' }}>{{ __('accounting.this_year') }}</option>
                            <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>{{ __('accounting.custom') }}</option>
                        </select>
                        
                        <!-- Custom Date Range -->
                        <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
                            <input type="date" name="start_date" class="form-control form-control-solid w-100 w-sm-auto" 
                                style="min-width: 140px;"
                                value="{{ request('start_date', $displayStartDate) }}"
                                {{ $period !== 'custom' ? 'disabled' : '' }}>
                            <input type="date" name="end_date" class="form-control form-control-solid w-100 w-sm-auto" 
                                style="min-width: 140px;"
                                value="{{ request('end_date', $displayEndDate) }}"
                                {{ $period !== 'custom' ? 'disabled' : '' }}>
                            <button type="submit" class="btn btn-sm btn-primary w-100 w-sm-auto" {{ $period !== 'custom' ? 'disabled' : '' }}>
                                <i class="ki-duotone ki-filter fs-2 me-1"></i>
                                {{ __('accounting.apply') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                <!-- Summary Cards -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Revenue Card -->
                    <div class="col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.total_revenue') }}</h2>
                                </div>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div class="d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-success me-2 lh-1">{{ number_format($revenue, 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">
                                        {{ __('accounting.period') }}: {{ \Carbon\Carbon::parse($displayStartDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($displayEndDate)->format('M d, Y') }}
                                    </span>
                                </div>
                                <div class="mt-5">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.revenue_categories') }}:</span>
                                    <div class="mt-2">
                                        @foreach($revenueByCategory as $category)
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="bullet bullet-vertical bg-success me-3"></span>
                                            <span class="text-gray-800 fw-semibold fs-7">{{ $category->transaction_category }}</span>
                                            <span class="text-gray-500 fs-7 ms-auto">{{ number_format($category->total, 2) }} {{ currency_symbol() }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Expenses Card -->
                    <div class="col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.total_expenses') }}</h2>
                                </div>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div class="d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-danger me-2 lh-1">{{ number_format($expenses, 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">
                                        {{ __('accounting.period') }}: {{ \Carbon\Carbon::parse($displayStartDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($displayEndDate)->format('M d, Y') }}
                                    </span>
                                </div>
                                <div class="mt-5">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.expense_categories') }}:</span>
                                    <div class="mt-2">
                                        @foreach($expensesByCategory as $category)
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="bullet bullet-vertical bg-danger me-3"></span>
                                            <span class="text-gray-800 fw-semibold fs-7">{{ $category->transaction_category }}</span>
                                            <span class="text-gray-500 fs-7 ms-auto">{{ number_format($category->total, 2) }} {{ currency_symbol() }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Net Income Card -->
                    <div class="col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.net_income') }}</h2>
                                </div>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-7">
                                    <span class="fs-2hx fw-bold {{ $netIncome >= 0 ? 'text-success' : 'text-danger' }} me-2 lh-1">
                                        {{ number_format(abs($netIncome), 2) }} {{ currency_symbol() }}
                                    </span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.profit_loss') }}</span>
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.calculation') }}:</span>
                                    <div class="d-flex align-items-center justify-content-center mt-2">
                                        <span class="text-success fs-5 fw-bold">{{ number_format($revenue, 2) }} {{ currency_symbol() }}</span>
                                        <span class="mx-2 fs-4">-</span>
                                        <span class="text-danger fs-5 fw-bold">{{ number_format($expenses, 2) }} {{ currency_symbol() }}</span>
                                        <span class="mx-2 fs-4">=</span>
                                        <span class="{{ $netIncome >= 0 ? 'text-success' : 'text-danger' }} fs-3 fw-bold">
                                            {{ number_format($netIncome, 2) }} {{ currency_symbol() }}
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-7">
                                    @if($revenue > 0)
                                        @php
                                            $profitMargin = ($netIncome / $revenue) * 100;
                                        @endphp
                                        <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.profit_margin') }}:</span>
                                        <span class="fs-2 fw-bold {{ $profitMargin >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($profitMargin, 1) }}%
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Detailed Breakdown -->
                <div class="row g-5 g-xl-8">
                    <!-- Revenue Breakdown -->
                    <div class="col-xl-6">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('accounting.revenue_breakdown') }}</h3>
                                <div class="card-toolbar">
                                    <span class="text-gray-500 fs-7">{{ __('accounting.by_category') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                                <th class="min-w-200px">{{ __('accounting.category') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.percentage') }}</th>
                                                <th class="min-w-150px">{{ __('accounting.distribution') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($revenueByCategory as $category)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-35px me-3">
                                                            <span class="symbol-label bg-light-success">
                                                                <i class="ki-duotone ki-arrow-up fs-2x text-success">
                                                                    <span class="path1"></span>
                                                                    <span class="path2"></span>
                                                                </i>
                                                            </span>
                                                        </div>
                                                        <span class="fs-6 fw-bold text-gray-800">{{ $category->transaction_category }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fs-5 fw-bold text-success">
                                                        {{ number_format($category->total, 2) }} {{ currency_symbol() }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    @if($revenue > 0)
                                                        <span class="fs-6 fw-bold text-gray-700">
                                                            {{ number_format(($category->total / $revenue) * 100, 1) }}%
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress h-8px w-100 me-3">
                                                            <div class="progress-bar bg-success" role="progressbar" 
                                                                 style="width: {{ $revenue > 0 ? ($category->total / $revenue) * 100 : 0 }}%">
                                                            </div>
                                                        </div>
                                                        <span class="fs-7 fw-bold">{{ $revenue > 0 ? number_format(($category->total / $revenue) * 100, 1) : 0 }}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold text-gray-700">
                                                <td>{{ __('accounting.total_revenue') }}</td>
                                                <td class="text-end">{{ number_format($revenue, 2) }} {{ currency_symbol() }}</td>
                                                <td class="text-end">100%</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Expenses Breakdown -->
                    <div class="col-xl-6">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('accounting.expenses_breakdown') }}</h3>
                                <div class="card-toolbar">
                                    <span class="text-gray-500 fs-7">{{ __('accounting.by_category') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                                <th class="min-w-200px">{{ __('accounting.category') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.percentage') }}</th>
                                                <th class="min-w-150px">{{ __('accounting.distribution') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($expensesByCategory as $category)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-35px me-3">
                                                            <span class="symbol-label bg-light-danger">
                                                                <i class="ki-duotone ki-arrow-down fs-2x text-danger">
                                                                    <span class="path1"></span>
                                                                    <span class="path2"></span>
                                                                </i>
                                                            </span>
                                                        </div>
                                                        <span class="fs-6 fw-bold text-gray-800">{{ $category->transaction_category }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fs-5 fw-bold text-danger">
                                                        {{ number_format($category->total, 2) }} {{ currency_symbol() }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    @if($expenses > 0)
                                                        <span class="fs-6 fw-bold text-gray-700">
                                                            {{ number_format(($category->total / $expenses) * 100, 1) }}%
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress h-8px w-100 me-3">
                                                            <div class="progress-bar bg-danger" role="progressbar" 
                                                                 style="width: {{ $expenses > 0 ? ($category->total / $expenses) * 100 : 0 }}%">
                                                            </div>
                                                        </div>
                                                        <span class="fs-7 fw-bold">{{ $expenses > 0 ? number_format(($category->total / $expenses) * 100, 1) : 0 }}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold text-gray-700">
                                                <td>{{ __('accounting.total_expenses') }}</td>
                                                <td class="text-end">{{ number_format($expenses, 2) }} {{ currency_symbol() }}</td>
                                                <td class="text-end">100%</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Profitability Analysis -->
                <div class="card mt-8">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.profitability_analysis') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="d-flex flex-column text-center mb-5">
                                    <span class="fs-2x fw-bold text-gray-800">{{ number_format($revenue, 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fs-6">{{ __('accounting.total_revenue') }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex flex-column text-center mb-5">
                                    <span class="fs-2x fw-bold text-danger">{{ number_format($expenses, 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fs-6">{{ __('accounting.total_expenses') }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex flex-column text-center mb-5">
                                    <span class="fs-2x fw-bold {{ $netIncome >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($netIncome, 2) }} {{ currency_symbol() }}
                                    </span>
                                    <span class="text-gray-500 fs-6">{{ __('accounting.net_income') }}</span>
                                </div>
                            </div>
                        </div>
                        
                        @if($revenue > 0)
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex flex-stack mb-5">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.expense_to_revenue_ratio') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">
                                        {{ number_format(($expenses / $revenue) * 100, 1) }}%
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex flex-stack mb-5">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.profit_margin') }}</span>
                                    <span class="fs-6 fw-bold {{ $netIncome >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format(($netIncome / $revenue) * 100, 1) }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- CSS-Based Bar Chart -->
                        <div class="mt-8">
                            <div class="d-flex justify-content-center">
                                <div class="w-100" style="max-width: 600px;">
                                    @php
                                        $maxValue = max($revenue, $expenses, abs($netIncome), 1);
                                        $revenuePercent = ($revenue / $maxValue) * 100;
                                        $expensesPercent = ($expenses / $maxValue) * 100;
                                        $netIncomePercent = (abs($netIncome) / $maxValue) * 100;
                                    @endphp
                                    
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="fw-semibold text-success">{{ __('accounting.revenue') }}</span>
                                            <span class="fw-bold">{{ number_format($revenue, 2) }} {{ currency_symbol() }}</span>
                                        </div>
                                        <div class="progress h-30px" style="height: 30px;">
                                            <div class="progress-bar bg-success d-flex align-items-center justify-content-end pe-3" 
                                                role="progressbar" 
                                                style="width: {{ min(100, $revenuePercent) }}%; font-size: 14px; font-weight: bold;">
                                                {{ number_format(($revenue / $maxValue) * 100, 1) }}%
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="fw-semibold text-danger">{{ __('accounting.expenses') }}</span>
                                            <span class="fw-bold">{{ number_format($expenses, 2) }} {{ currency_symbol() }}</span>
                                        </div>
                                        <div class="progress h-30px" style="height: 30px;">
                                            <div class="progress-bar bg-danger d-flex align-items-center justify-content-end pe-3" 
                                                role="progressbar" 
                                                style="width: {{ min(100, $expensesPercent) }}%; font-size: 14px; font-weight: bold;">
                                                {{ number_format(($expenses / $maxValue) * 100, 1) }}%
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="fw-semibold {{ $netIncome >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ __('accounting.net_income') }}
                                            </span>
                                            <span class="fw-bold {{ $netIncome >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($netIncome, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </div>
                                        <div class="progress h-30px" style="height: 30px;">
                                            <div class="progress-bar {{ $netIncome >= 0 ? 'bg-success' : 'bg-danger' }} d-flex align-items-center justify-content-end pe-3" 
                                                role="progressbar" 
                                                style="width: {{ min(100, $netIncomePercent) }}%; font-size: 14px; font-weight: bold;">
                                                {{ number_format((abs($netIncome) / $maxValue) * 100, 1) }}%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    @endsection
</x-app-layout>