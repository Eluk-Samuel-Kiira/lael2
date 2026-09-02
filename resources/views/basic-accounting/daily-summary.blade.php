<x-app-layout>
    @section('title', __('accounting.daily_summary'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                    {{ __('accounting.daily_summary') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.daily-summary') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.basic_accounting') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.daily_summary') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-lg-auto">
                <form method="GET" action="{{ route('accounting.daily-summary') }}" class="w-100" id="filterForm">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <!-- Location Filter -->
                        <div class="d-flex align-items-center bg-light-primary rounded-3 px-4 py-2" style="min-width: 200px;">
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
                                    style="min-width: 150px; width: 100%;">
                                <option value="">{{ __('pagination.all_locations') }}</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ ($locationId ?? '') == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- User Filter -->
                        <div class="d-flex align-items-center bg-light-info rounded-3 px-4 py-2" style="min-width: 200px;">
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
                                    style="min-width: 150px; width: 100%;">
                                <option value="">{{ __('accounting.all_users') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ ($userId ?? '') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Date Selector -->
                        <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto">
                            <input type="date" id="dateSelector" class="form-control form-control-solid w-100 w-sm-180px" 
                                name="date"
                                value="{{ request('date', $date) }}" onchange="this.form.submit()">
                        </div>
                        
                        <!-- Buttons -->
                        <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2"
                            style="height: 42px; padding: 0 20px;">
                            <i class="ki-duotone ki-filter fs-3"></i>
                            <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                            <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                        </button>
                        <a href="{{ route('accounting.daily-summary') }}" class="btn btn-light d-flex align-items-center justify-content-center gap-2"
                            style="height: 42px; padding: 0 20px;">
                            <i class="ki-duotone ki-arrow-rotate-left fs-3"></i>
                            <span class="d-none d-sm-inline">{{ __('accounting.reset') }}</span>
                            <span class="d-inline d-sm-none">{{ __('accounting.reset') }}</span>
                        </a>
                        
                        <!-- Export Dropdown -->
                        <div class="dropdown flex-grow-1 flex-sm-grow-0">
                            <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0)" 
                                    onclick="exportCurrentPage({tableId: 'dailyTransactionsTable', filename: 'daily_summary_{{ $date }}', sheetName: 'Daily Transactions'})">
                                        <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                        {{ __('accounting.export_to_excel') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0)" 
                                    onclick="exportCurrentPage({tableId: 'dailyTransactionsTable', filename: 'daily_summary_{{ $date }}', format: 'csv'})">
                                        <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                        {{ __('accounting.export_to_csv') }}
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0)" 
                                    onclick="exportCurrentPage({tableId: 'balanceChangesTable', filename: 'balance_changes_{{ $date }}', sheetName: 'Balance Changes'})">
                                        <i class="ki-duotone ki-wallet fs-2 me-2 text-info"></i>
                                        {{ __('Export Balance Changes') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0)" 
                                    onclick="exportCurrentPage({tableId: 'transactionsByTypeTable', filename: 'transactions_by_type_{{ $date }}', sheetName: 'By Type'})">
                                        <i class="ki-duotone ki-category fs-2 me-2 text-warning"></i>
                                        {{ __('Export by Type') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                        
                        <!-- Print Button -->
                        <button class="btn btn-sm btn-light flex-shrink-0" onclick="printReport()">
                            <i class="ki-duotone ki-printer fs-2 me-1 me-sm-2"></i>
                            <span class="d-none d-sm-inline">{{ __('accounting.print') }}</span>
                            <span class="d-inline d-sm-none">{{ __('accounting.print') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                <!-- Daily Summary Cards -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Total Transactions -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $summary['total_transactions'] }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_transactions') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.date') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">
                                        {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Amount -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ number_format($summary['total_amount'], 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_amount') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.day_of_week') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">
                                        {{ \Carbon\Carbon::parse($date)->format('l') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Deposits -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-success">{{ number_format($summary['deposit_total'], 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.deposits') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.money_in') }}</span>
                                    <span class="fw-bold text-success fs-7">{{ number_format($summary['deposit_total'], 2) }} {{ currency_symbol() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Withdrawals -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-danger">{{ number_format($summary['withdrawal_total'], 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.withdrawals') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.money_out') }}</span>
                                    <span class="fw-bold text-danger fs-7">{{ number_format($summary['withdrawal_total'], 2) }} {{ currency_symbol() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Net Cash Flow Card -->
                <div class="row g-5 g-xl-8 mb-8">
                    <div class="col-sm-6 col-xl-3 offset-xl-9">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold {{ $summary['net_cash_flow'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($summary['net_cash_flow'], 2) }} {{ currency_symbol() }}
                                    </span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.net_cash_flow') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.daily_result') }}</span>
                                    <span class="badge badge-light-{{ $summary['net_cash_flow'] >= 0 ? 'success' : 'danger' }} py-2 px-3">
                                        {{ $summary['net_cash_flow'] >= 0 ? __('accounting.positive') : __('accounting.negative') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transactions by Type -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-tag fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.transactions_by_type') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.type_breakdown') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if($byType->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="transactionsByTypeTable">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-150px">{{ __('accounting.transaction_type') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.count') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.total_amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.average_amount') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.percentage') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.trend') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($byType as $type => $data)
                                    <tr>
                                        <td>
                                            @php
                                                $typeColors = [
                                                    'DEPOSIT' => 'success',
                                                    'WITHDRAWAL' => 'danger',
                                                    'TRANSFER_IN' => 'info',
                                                    'TRANSFER_OUT' => 'warning',
                                                    'FEE' => 'secondary',
                                                    'REFUND' => 'primary',
                                                    'ADJUSTMENT' => 'info',
                                                    'RECONCILIATION' => 'dark'
                                                ];
                                                $color = $typeColors[$type] ?? 'secondary';
                                            @endphp
                                            <span class="badge badge-light-{{ $color }} py-2 px-3">
                                                {{ $type }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-light-secondary py-2 px-3">{{ number_format($data['count']) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ $type === 'DEPOSIT' || $type === 'TRANSFER_IN' || $type === 'REFUND' ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($data['total'], 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ number_format($data['average'], 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress h-8px w-100 me-3">
                                                    <div class="progress-bar bg-{{ $color }}" role="progressbar" 
                                                         style="width: {{ $summary['total_transactions'] > 0 ? ($data['count'] / $summary['total_transactions']) * 100 : 0 }}%">
                                                    </div>
                                                </div>
                                                <span class="fs-7 fw-bold">{{ $summary['total_transactions'] > 0 ? number_format(($data['count'] / $summary['total_transactions']) * 100, 1) : 0 }}%</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($data['count'] > 0)
                                                @php
                                                    $avgValue = $data['average'];
                                                    $trendColor = $avgValue > 1000 ? 'danger' : ($avgValue > 500 ? 'warning' : 'success');
                                                    $trendLabel = $avgValue > 1000 ? __('accounting.high') : ($avgValue > 500 ? __('accounting.medium') : __('accounting.low'));
                                                @endphp
                                                <span class="badge badge-light-{{ $trendColor }} py-2 px-3">
                                                    {{ $trendLabel }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-10">
                            <div class="text-muted fs-6">{{ __('accounting.no_transactions_found') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                
                <!-- Transactions by Category -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-category fs-2 me-2 text-success">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.transactions_by_category') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.category_breakdown') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if($byCategory->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="transactionsByCategoryTable">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-200px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.count') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.total_amount') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.distribution') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.percentage') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($byCategory as $category => $data)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="bullet bullet-dot bg-success h-10px w-10px me-2"></span>
                                                <span class="fs-6 fw-bold text-gray-800">{{ $category }}</span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-light-secondary py-2 px-3">{{ number_format($data['count']) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-800">{{ number_format($data['total'], 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress h-8px w-100 me-3">
                                                    <div class="progress-bar bg-primary" role="progressbar" 
                                                         style="width: {{ $summary['total_amount'] > 0 ? ($data['total'] / $summary['total_amount']) * 100 : 0 }}%">
                                                    </div>
                                                </div>
                                                <span class="fs-7 fw-bold">{{ $summary['total_amount'] > 0 ? number_format(($data['total'] / $summary['total_amount']) * 100, 1) : 0 }}%</span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ $summary['total_transactions'] > 0 ? number_format(($data['count'] / $summary['total_transactions']) * 100, 1) : 0 }}%</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-10">
                            <div class="text-muted fs-6">{{ __('accounting.no_categories_found') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                
                <!-- Balance Changes -->
                @if(count($balanceChanges) > 0)
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-wallet fs-2 me-2 text-info">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.balance_changes') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.account_balances') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="balanceChangesTable">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-200px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.starting_balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.ending_balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.net_change') }}</th>
                                        <th class="min-w-150px text-center">{{ __('accounting.change_type') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.change_percentage') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($balanceChanges as $change)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-45px me-3">
                                                    <span class="symbol-label bg-light-primary">
                                                        <i class="ki-duotone ki-wallet fs-2x text-primary">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                    </span>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fs-6 fw-bold text-gray-800">{{ $change['method']->name ?? 'N/A' }}</span>
                                                    <span class="fs-7 text-gray-500">{{ $change['method']->type ?? '' }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ number_format($change['starting_balance'], 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-800">{{ number_format($change['ending_balance'], 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ $change['net_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($change['net_change'], 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $change['net_change'] >= 0 ? 'success' : 'danger' }} py-2 px-3">
                                                {{ $change['net_change'] >= 0 ? __('accounting.increase') : __('accounting.decrease') }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if($change['starting_balance'] != 0)
                                                @php
                                                    $percentage = ($change['net_change'] / abs($change['starting_balance'])) * 100;
                                                @endphp
                                                <span class="fs-6 fw-bold {{ $percentage >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ number_format($percentage, 2) }}%
                                                </span>
                                            @else
                                                <span class="fs-7 text-gray-500">-</span>
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
                
                <!-- Daily Transactions -->
                <div class="card card-flush">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-calendar-8 fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                            {{ __('accounting.daily_transactions') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.all_transactions_for') }} {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if($transactions->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="dailyTransactionsTable">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-100px">{{ __('accounting.time') }}</th>
                                        <th class="min-w-200px">{{ __('accounting.description') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.balance') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.processed_by') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($transactions as $transaction)
                                    <tr>
                                        <td>
                                            <span class="fs-6 fw-bold text-gray-800">{{ $transaction->transaction_date->format('H:i:s') }}</span>
                                        </td>
                                        <td>
                                            <span class="fs-6 text-gray-800">{{ Str::limit($transaction->description, 50) }}</span>
                                            @if($transaction->notes)
                                                <span class="fs-7 text-gray-500 d-block">{{ Str::limit($transaction->notes, 30) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fs-6 fw-bold text-gray-800">{{ $transaction->paymentMethod->name ?? 'N/A' }}</span>
                                                <span class="fs-7 text-gray-500">{{ $transaction->paymentMethod->type ?? '' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $typeColors = [
                                                    'DEPOSIT' => 'success',
                                                    'WITHDRAWAL' => 'danger',
                                                    'TRANSFER_IN' => 'info',
                                                    'TRANSFER_OUT' => 'warning',
                                                    'FEE' => 'secondary',
                                                    'REFUND' => 'primary',
                                                    'ADJUSTMENT' => 'info',
                                                    'RECONCILIATION' => 'dark'
                                                ];
                                                $color = $typeColors[$transaction->transaction_type] ?? 'secondary';
                                            @endphp
                                            <span class="badge badge-light-{{ $color }} py-2 px-3">
                                                {{ $transaction->transaction_type }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-secondary py-2 px-3">{{ $transaction->transaction_category }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ in_array($transaction->transaction_type, ['DEPOSIT', 'TRANSFER_IN', 'REFUND']) ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($transaction->amount, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ number_format($transaction->balance_after, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($transaction->user)
                                                <span class="fw-bold text-gray-700 fs-7">{{ $transaction->user->name }}</span>
                                            @else
                                                <span class="text-gray-500 fs-7">System</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $statusColors = [
                                                    'COMPLETED' => 'success',
                                                    'PENDING' => 'warning',
                                                    'FAILED' => 'danger',
                                                    'CANCELLED' => 'secondary',
                                                    'REVERSED' => 'dark'
                                                ];
                                                $statusColor = $statusColors[$transaction->status] ?? 'secondary';
                                            @endphp
                                            <span class="badge badge-light-{{ $statusColor }} py-2 px-3">
                                                {{ $transaction->status }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        @if($transactions->hasPages())
                        <div class="mt-6 d-flex justify-content-center">
                            {{ $transactions->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                        @endif
                        @else
                        <div class="text-center py-10">
                            <div class="text-center">
                                <i class="ki-duotone ki-inbox fs-4tx text-gray-400 mb-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <h5 class="text-gray-600 fw-semibold">{{ __('accounting.no_transactions_for_this_day') }}</h5>
                                <p class="text-muted fs-6">{{ __('accounting.try_selecting_a_different_date') }}</p>
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
        function printReport() {
            window.print();
        }
        
        // Initialize Select2 for filters
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('#locationFilter').select2({
                    placeholder: "{{ __('pagination.all_locations') }}",
                    allowClear: true,
                    width: '180px'
                });
                
                $('#userFilter').select2({
                    placeholder: "{{ __('accounting.all_users') }}",
                    allowClear: true,
                    width: '180px'
                });
            }
        });
    </script>
    @endpush
    
    @endsection
</x-app-layout>