<x-app-layout>
    @section('title', __('accounting.income_statement'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{ __('accounting.income_statement') }}
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
                    <li class="breadcrumb-item text-muted">{{ __('accounting.income_statement') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                <form method="GET" action="{{ route('accounting.income-statement') }}" class="w-100" id="filterForm">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <!-- Location Filter -->
                        <div class="d-flex align-items-center bg-light-primary rounded-3 px-4 py-2" style="min-width: 220px;">
                            <i class="ki-duotone ki-geolocation fs-2 text-primary me-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <select class="form-select form-select-sm form-select-solid border-0 bg-transparent ps-0" 
                                    name="location_id" 
                                    data-control="select2" 
                                    id="locationFilter" 
                                    onchange="this.form.submit()"
                                    data-placeholder="{{ __('pagination.all_locations') }}"
                                    style="min-width: 160px; width: 100%;">
                                <option value="">{{ __('pagination.all_locations') }}</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ ($locationId ?? '') == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- User Filter -->
                        <div class="d-flex align-items-center bg-light-info rounded-3 px-4 py-2" style="min-width: 220px;">
                            <i class="ki-duotone ki-user fs-2 text-info me-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <select class="form-select form-select-sm form-select-solid border-0 bg-transparent ps-0" 
                                    name="user_id" 
                                    data-control="select2" 
                                    id="userFilter" 
                                    onchange="this.form.submit()"
                                    data-placeholder="{{ __('accounting.all_users') }}"
                                    style="min-width: 160px; width: 100%;">
                                <option value="">{{ __('accounting.all_users') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ ($userId ?? '') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
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
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-success">{{ number_format($revenue, 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_revenue') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.period') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">{{ \Carbon\Carbon::parse($displayStartDate)->format('M d') }} - {{ \Carbon\Carbon::parse($displayEndDate)->format('M d, Y') }}</span>
                                </div>
                                @if($revenueByCategory->count() > 0)
                                    <div class="mt-3">
                                        <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.top_categories') }}</span>
                                        @foreach($revenueByCategory->take(3) as $category)
                                        <div class="d-flex align-items-center mt-1">
                                            <span class="bullet bullet-dot bg-success h-8px w-8px me-2"></span>
                                            <span class="text-gray-700 fs-7">{{ $category->transaction_category }}</span>
                                            <span class="text-gray-500 fs-7 ms-auto">{{ number_format($category->total, 2) }} {{ currency_symbol() }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Expenses Card -->
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-danger">{{ number_format($expenses, 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_expenses') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.period') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">{{ \Carbon\Carbon::parse($displayStartDate)->format('M d') }} - {{ \Carbon\Carbon::parse($displayEndDate)->format('M d, Y') }}</span>
                                </div>
                                @if($expensesByCategory->count() > 0)
                                    <div class="mt-3">
                                        <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.top_categories') }}</span>
                                        @foreach($expensesByCategory->take(3) as $category)
                                        <div class="d-flex align-items-center mt-1">
                                            <span class="bullet bullet-dot bg-danger h-8px w-8px me-2"></span>
                                            <span class="text-gray-700 fs-7">{{ $category->transaction_category }}</span>
                                            <span class="text-gray-500 fs-7 ms-auto">{{ number_format($category->total, 2) }} {{ currency_symbol() }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Net Income Card -->
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold {{ $netIncome >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format(abs($netIncome), 2) }} {{ currency_symbol() }}
                                    </span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.net_income') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.profit_margin') }}</span>
                                    @if($revenue > 0)
                                        <span class="fw-bold {{ $netIncome >= 0 ? 'text-success' : 'text-danger' }} fs-7">
                                            {{ number_format(($netIncome / $revenue) * 100, 1) }}%
                                        </span>
                                    @else
                                        <span class="fw-bold text-gray-500 fs-7">N/A</span>
                                    @endif
                                </div>
                                <div class="d-flex flex-stack mt-2">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.expense_ratio') }}</span>
                                    @if($revenue > 0)
                                        <span class="fw-bold text-gray-700 fs-7">
                                            {{ number_format(($expenses / $revenue) * 100, 1) }}%
                                        </span>
                                    @else
                                        <span class="fw-bold text-gray-500 fs-7">N/A</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Monthly Trends Chart -->
                @if(isset($monthlyTrends) && $monthlyTrends->count() > 0)
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            {{ __('accounting.monthly_trends') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.revenue_vs_expenses') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-150px">{{ __('accounting.month') }}</th>
                                        <th class="min-w-150px text-end">{{ __('accounting.revenue') }}</th>
                                        <th class="min-w-150px text-end">{{ __('accounting.expenses') }}</th>
                                        <th class="min-w-150px text-end">{{ __('accounting.net_income') }}</th>
                                        <th class="min-w-150px text-end">{{ __('accounting.profit_margin') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($monthlyTrends as $trend)
                                    @php
                                        $netMonthly = $trend->revenue - $trend->expenses;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fs-6 fw-bold text-gray-800">{{ \Carbon\Carbon::parse($trend->month)->format('M Y') }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="text-success fw-bold">{{ number_format($trend->revenue, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="text-danger fw-bold">{{ number_format($trend->expenses, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="{{ $netMonthly >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                                {{ number_format($netMonthly, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if($trend->revenue > 0)
                                                <span class="fw-bold {{ $netMonthly >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ number_format(($netMonthly / $trend->revenue) * 100, 1) }}%
                                                </span>
                                            @else
                                                <span class="text-gray-500">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
                
                <!-- Detailed Breakdown -->
                <div class="row g-5 g-xl-8">
                    <!-- Revenue Breakdown -->
                    <div class="col-xl-6">
                        <div class="card card-flush h-md-100">
                            <div class="card-header border-0 pt-6">
                                <h3 class="card-title fw-bold text-gray-800">
                                    <i class="ki-duotone ki-arrow-up fs-2 me-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    {{ __('accounting.revenue_breakdown') }}
                                </h3>
                                <div class="card-toolbar">
                                    <span class="badge badge-light-success py-2 px-3">{{ $revenueByCategory->count() }} {{ __('accounting.categories') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                @if($revenueByCategory->count() > 0)
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                                        <thead>
                                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                                <th class="min-w-200px">{{ __('accounting.category') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.percentage') }}</th>
                                                <th class="min-w-150px">{{ __('accounting.distribution') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-gray-600 fw-semibold">
                                            @foreach($revenueByCategory as $category)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="bullet bullet-dot bg-success h-10px w-10px me-2"></span>
                                                        <span class="fs-6 fw-bold text-gray-800">{{ $category->transaction_category }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fs-6 fw-bold text-success">
                                                        {{ number_format($category->total, 2) }} {{ currency_symbol() }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    @if($revenue > 0)
                                                        <span class="fs-6 fw-bold text-gray-700">
                                                            {{ number_format(($category->total / $revenue) * 100, 1) }}%
                                                        </span>
                                                    @else
                                                        <span class="fs-6 text-gray-500">0%</span>
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
                                            <tr class="fw-bold text-gray-700 fs-6">
                                                <td>{{ __('accounting.total_revenue') }}</td>
                                                <td class="text-end text-success">{{ number_format($revenue, 2) }} {{ currency_symbol() }}</td>
                                                <td class="text-end">100%</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                @else
                                <div class="text-center py-10">
                                    <div class="text-muted fs-6">{{ __('accounting.no_revenue_data') }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Expenses Breakdown -->
                    <div class="col-xl-6">
                        <div class="card card-flush h-md-100">
                            <div class="card-header border-0 pt-6">
                                <h3 class="card-title fw-bold text-gray-800">
                                    <i class="ki-duotone ki-arrow-down fs-2 me-2 text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    {{ __('accounting.expenses_breakdown') }}
                                </h3>
                                <div class="card-toolbar">
                                    <span class="badge badge-light-danger py-2 px-3">{{ $expensesByCategory->count() }} {{ __('accounting.categories') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                @if($expensesByCategory->count() > 0)
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                                        <thead>
                                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                                <th class="min-w-200px">{{ __('accounting.category') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.percentage') }}</th>
                                                <th class="min-w-150px">{{ __('accounting.distribution') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-gray-600 fw-semibold">
                                            @foreach($expensesByCategory as $category)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="bullet bullet-dot bg-danger h-10px w-10px me-2"></span>
                                                        <span class="fs-6 fw-bold text-gray-800">{{ $category->transaction_category }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fs-6 fw-bold text-danger">
                                                        {{ number_format($category->total, 2) }} {{ currency_symbol() }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    @if($expenses > 0)
                                                        <span class="fs-6 fw-bold text-gray-700">
                                                            {{ number_format(($category->total / $expenses) * 100, 1) }}%
                                                        </span>
                                                    @else
                                                        <span class="fs-6 text-gray-500">0%</span>
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
                                            <tr class="fw-bold text-gray-700 fs-6">
                                                <td>{{ __('accounting.total_expenses') }}</td>
                                                <td class="text-end text-danger">{{ number_format($expenses, 2) }} {{ currency_symbol() }}</td>
                                                <td class="text-end">100%</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                @else
                                <div class="text-center py-10">
                                    <div class="text-muted fs-6">{{ __('accounting.no_expense_data') }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Profitability Analysis -->
                <div class="card card-flush mt-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.profitability_analysis') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ $displayStartDate }} - {{ $displayEndDate }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-5 g-xl-10">
                            <div class="col-md-3">
                                <div class="d-flex flex-column text-center p-5 bg-light-success rounded-3">
                                    <span class="fs-2x fw-bold text-success">{{ number_format($revenue, 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fs-6">{{ __('accounting.total_revenue') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex flex-column text-center p-5 bg-light-danger rounded-3">
                                    <span class="fs-2x fw-bold text-danger">{{ number_format($expenses, 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fs-6">{{ __('accounting.total_expenses') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex flex-column text-center p-5 {{ $netIncome >= 0 ? 'bg-light-success' : 'bg-light-danger' }} rounded-3">
                                    <span class="fs-2x fw-bold {{ $netIncome >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format(abs($netIncome), 2) }} {{ currency_symbol() }}
                                    </span>
                                    <span class="text-gray-500 fs-6">{{ __('accounting.net_income') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex flex-column text-center p-5 bg-light-info rounded-3">
                                    <span class="fs-2x fw-bold text-info">
                                        @if($revenue > 0)
                                            {{ number_format(($netIncome / $revenue) * 100, 1) }}%
                                        @else
                                            N/A
                                        @endif
                                    </span>
                                    <span class="text-gray-500 fs-6">{{ __('accounting.profit_margin') }}</span>
                                </div>
                            </div>
                        </div>
                        
                        @if($revenue > 0)
                        <div class="row mt-8">
                            <div class="col-12">
                                <div class="d-flex flex-stack mb-3">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.expense_to_revenue_ratio') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">{{ number_format(($expenses / $revenue) * 100, 1) }}%</span>
                                </div>
                                <div class="progress h-10px">
                                    <div class="progress-bar bg-danger" role="progressbar" 
                                         style="width: {{ min(100, ($expenses / $revenue) * 100) }}%">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        // Initialize Select2 for filters
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('#locationFilter').select2({
                    placeholder: "{{ __('pagination.all_locations') }}",
                    allowClear: true,
                    width: '150px'
                });
                
                $('#userFilter').select2({
                    placeholder: "{{ __('accounting.all_users') }}",
                    allowClear: true,
                    width: '150px'
                });
            }
        });
    </script>
    @endpush
    
    @endsection
</x-app-layout>