<x-app-layout>
    @section('title', __('accounting.transaction_analysis'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{ __('accounting.transaction_analysis') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.transaction-analysis') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.basic_accounting') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.transaction_analysis') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                <form method="GET" action="{{ route('accounting.transaction-analysis') }}" class="w-100" id="filterForm">
                    <div class="d-flex flex-wrap align-items-center gap-3">
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
                        
                        <!-- Date Range -->
                        <div class="d-flex align-items-center gap-2">
                            <input type="date" name="start_date" class="form-control form-control-solid" 
                                style="min-width: 150px; height: 42px;"
                                value="{{ request('start_date', $startDate) }}">
                            <span class="text-gray-500 px-1">{{ __('accounting.to') }}</span>
                            <input type="date" name="end_date" class="form-control form-control-solid" 
                                style="min-width: 150px; height: 42px;"
                                value="{{ request('end_date', $endDate) }}">
                        </div>
                        
                        <!-- Buttons -->
                        <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2"
                            style="height: 42px; padding: 0 24px;">
                            <i class="ki-duotone ki-filter fs-3"></i>
                            <span>{{ __('accounting.apply_filters') }}</span>
                        </button>
                        <a href="{{ route('accounting.transaction-analysis') }}" class="btn btn-light d-flex align-items-center justify-content-center gap-2"
                            style="height: 42px; padding: 0 24px;">
                            <i class="ki-duotone ki-arrow-rotate-left fs-3"></i>
                            <span>{{ __('accounting.reset') }}</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                @if($volumeByType->isEmpty() && $volumeByCategory->isEmpty())
                <div class="alert alert-info d-flex align-items-center p-5">
                    <i class="ki-duotone ki-information-5 fs-2x me-3 text-info">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    <div class="d-flex flex-column">
                        <h4 class="mb-1">{{ __('accounting.no_data_found') }}</h4>
                        <span class="text-muted">{{ __('accounting.no_transactions_found_for_period') }}</span>
                    </div>
                </div>
                @else
                
                <!-- Summary Statistics Cards -->
                <div class="row g-5 g-xl-8 mb-8">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $volumeByType->sum('count') }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_transactions') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.period') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">
                                        {{ \Carbon\Carbon::parse($startDate)->format('M d') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-success">{{ number_format($volumeByType->sum('total_amount'), 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_volume') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.transaction_types') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">{{ $volumeByType->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    @php
                                        $avgTransaction = $volumeByType->sum('count') > 0 ? $volumeByType->sum('total_amount') / $volumeByType->sum('count') : 0;
                                    @endphp
                                    <span class="fs-2hx fw-bold text-info">{{ number_format($avgTransaction, 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.average_transaction') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.per_transaction') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">{{ number_format($avgTransaction, 2) }} {{ currency_symbol() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    @php
                                        $avgDaily = $dailyTrends->count() > 0 ? $volumeByType->sum('total_amount') / $dailyTrends->count() : 0;
                                    @endphp
                                    <span class="fs-2hx fw-bold text-warning">{{ number_format($avgDaily, 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.average_daily') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.days') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">{{ $dailyTrends->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transaction Volume by Type & Category Row -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Transaction Volume by Type -->
                    <div class="col-xl-6">
                        <div class="card card-flush h-md-100">
                            <div class="card-header border-0 pt-6">
                                <h3 class="card-title fw-bold text-gray-800">
                                    <i class="ki-duotone ki-tag fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    {{ __('accounting.transaction_volume_by_type') }}
                                </h3>
                                <div class="card-toolbar">
                                    <span class="badge badge-light-primary py-2 px-3">{{ $volumeByType->count() }} {{ __('accounting.types') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                @if($volumeByType->count() > 0)
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                                        <thead>
                                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                                <th class="min-w-150px">{{ __('accounting.transaction_type') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.count') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.total_amount') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.average_amount') }}</th>
                                                <th class="min-w-100px">{{ __('accounting.percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-gray-600 fw-semibold">
                                            @php
                                                $totalCount = $volumeByType->sum('count') ?: 1;
                                                $totalAmount = $volumeByType->sum('total_amount');
                                            @endphp
                                            @foreach($volumeByType as $item)
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
                                                        $color = $typeColors[$item->transaction_type] ?? 'secondary';
                                                    @endphp
                                                    <span class="badge badge-light-{{ $color }} py-2 px-3">
                                                        {{ $item->transaction_type }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge badge-light-secondary py-2 px-3">{{ number_format($item->count) }}</span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fs-6 fw-bold text-gray-800">
                                                        {{ number_format($item->total_amount, 2) }} {{ currency_symbol() }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    @if($item->count > 0)
                                                        {{ number_format($item->average_amount, 2) }} {{ currency_symbol() }}
                                                    @else
                                                        <span class="text-gray-500">0.00</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress h-8px w-100 me-3">
                                                            <div class="progress-bar bg-{{ $color }}" role="progressbar" 
                                                                 style="width: {{ ($item->count / $totalCount) * 100 }}%">
                                                            </div>
                                                        </div>
                                                        <span class="fs-7 fw-bold">{{ number_format(($item->count / $totalCount) * 100, 1) }}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold text-gray-700 fs-6">
                                                <td>{{ __('accounting.total') }}</td>
                                                <td class="text-end">{{ number_format($totalCount) }}</td>
                                                <td class="text-end">{{ number_format($totalAmount, 2) }} {{ currency_symbol() }}</td>
                                                <td class="text-end">
                                                    @if($totalCount > 0)
                                                        {{ number_format($totalAmount / $totalCount, 2) }} {{ currency_symbol() }}
                                                    @else
                                                        0.00
                                                    @endif
                                                </td>
                                                <td>100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                @else
                                <div class="text-center py-10">
                                    <div class="text-muted fs-6">{{ __('accounting.no_transaction_types') }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Transaction Volume by Category -->
                    <div class="col-xl-6">
                        <div class="card card-flush h-md-100">
                            <div class="card-header border-0 pt-6">
                                <h3 class="card-title fw-bold text-gray-800">
                                    <i class="ki-duotone ki-category fs-2 me-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    {{ __('accounting.transaction_volume_by_category') }}
                                </h3>
                                <div class="card-toolbar">
                                    <span class="badge badge-light-success py-2 px-3">{{ $volumeByCategory->count() }} {{ __('accounting.categories') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                @if($volumeByCategory->count() > 0)
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                                        <thead>
                                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                                <th class="min-w-150px">{{ __('accounting.category') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.count') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.total_amount') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.average_amount') }}</th>
                                                <th class="min-w-100px">{{ __('accounting.share') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-gray-600 fw-semibold">
                                            @php
                                                $categoryTotalAmount = $volumeByCategory->sum('total_amount') ?: 1;
                                            @endphp
                                            @foreach($volumeByCategory as $item)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="bullet bullet-dot bg-success h-10px w-10px me-2"></span>
                                                        <span class="fs-6 fw-bold text-gray-800">{{ $item->transaction_category }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge badge-light-secondary py-2 px-3">{{ number_format($item->count) }}</span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fs-6 fw-bold text-gray-800">
                                                        {{ number_format($item->total_amount, 2) }} {{ currency_symbol() }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    @if($item->count > 0)
                                                        {{ number_format($item->average_amount, 2) }} {{ currency_symbol() }}
                                                    @else
                                                        <span class="text-gray-500">0.00</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress h-8px w-100 me-3">
                                                            <div class="progress-bar bg-success" role="progressbar" 
                                                                 style="width: {{ ($item->total_amount / $categoryTotalAmount) * 100 }}%">
                                                            </div>
                                                        </div>
                                                        <span class="fs-7 fw-bold">{{ number_format(($item->total_amount / $categoryTotalAmount) * 100, 1) }}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold text-gray-700 fs-6">
                                                <td>{{ __('accounting.total') }}</td>
                                                <td class="text-end">{{ $volumeByCategory->sum('count') }}</td>
                                                <td class="text-end">{{ number_format($categoryTotalAmount, 2) }} {{ currency_symbol() }}</td>
                                                <td class="text-end">{{ number_format($volumeByCategory->avg('average_amount'), 2) }} {{ currency_symbol() }}</td>
                                                <td>100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                @else
                                <div class="text-center py-10">
                                    <div class="text-muted fs-6">{{ __('accounting.no_categories') }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Daily Trends -->
                @if($dailyTrends->isNotEmpty())
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.daily_transaction_trends') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.daily_analysis') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-120px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.transaction_count') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.daily_total') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.trend') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.daily_average') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @php $previousTotal = null; @endphp
                                    @foreach($dailyTrends as $trend)
                                    <tr>
                                        <td>
                                            <span class="fs-6 fw-bold text-gray-800">{{ \Carbon\Carbon::parse($trend->date)->format('M d, Y') }}</span>
                                            <span class="fs-7 text-gray-500 d-block">{{ \Carbon\Carbon::parse($trend->date)->format('l') }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-light-secondary py-2 px-3">{{ number_format($trend->transaction_count) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-800">
                                                {{ number_format($trend->daily_total, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($previousTotal !== null)
                                                @php
                                                    $change = $trend->daily_total - $previousTotal;
                                                    $percentage = $previousTotal > 0 ? ($change / $previousTotal) * 100 : 0;
                                                @endphp
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fs-7 fw-bold {{ $change >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ $change >= 0 ? '+' : '' }}{{ number_format(abs($change), 2) }} {{ currency_symbol() }}
                                                    </span>
                                                    <span class="badge badge-light-{{ $change >= 0 ? 'success' : 'danger' }} py-2 px-3">
                                                        {{ $change >= 0 ? '↑' : '↓' }} {{ number_format(abs($percentage), 1) }}%
                                                    </span>
                                                </div>
                                            @else
                                                <span class="fs-7 text-gray-500">{{ __('accounting.first_day') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($trend->transaction_count > 0)
                                                {{ number_format($trend->daily_total / $trend->transaction_count, 2) }} {{ currency_symbol() }}
                                            @else
                                                <span class="text-gray-500">0.00</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @php $previousTotal = $trend->daily_total; @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
                
                <!-- Top Transactions with Pagination -->
                <div class="card card-flush">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-dollar fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.top_transactions') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.largest_transactions') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if($topTransactions->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-50px">#</th>
                                        <th class="min-w-150px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-200px">{{ __('accounting.description') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.balance_after') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($topTransactions as $index => $transaction)
                                    <tr>
                                        <td>
                                            <span class="badge badge-light-secondary py-2 px-3">
                                                {{ $index + 1 + (($topTransactions->currentPage() - 1) * $topTransactions->perPage()) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fs-6 fw-bold text-gray-800">{{ $transaction->transaction_date->format('M d, Y') }}</span>
                                            <span class="fs-7 text-gray-500 d-block">{{ $transaction->transaction_date->format('H:i:s') }}</span>
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
                                                @if($transaction->user)
                                                    <span class="fs-7 text-gray-500">
                                                        <i class="ki-duotone ki-user fs-1 me-1"></i>
                                                        {{ $transaction->user->name }}
                                                    </span>
                                                @endif
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
                                            <span class="fs-5 fw-bold {{ in_array($transaction->transaction_type, ['DEPOSIT', 'TRANSFER_IN', 'REFUND']) ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($transaction->amount, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">
                                                {{ number_format($transaction->balance_after, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-6 d-flex justify-content-center">
                            {{ $topTransactions->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                        @else
                        <div class="text-center py-10">
                            <div class="text-muted fs-6">{{ __('accounting.no_transactions_found') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                
                @endif
                
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