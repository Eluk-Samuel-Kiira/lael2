<x-app-layout>
    @section('title', __('accounting.weekly_report'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                    {{ __('accounting.weekly_report') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.weekly-report') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.basic_accounting') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.weekly_report') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-lg-auto">
                <form method="GET" action="{{ route('accounting.weekly-report') }}" class="w-100" id="filterForm">
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
                        
                        <!-- Week/Year Selectors -->
                        <div class="d-flex flex-row gap-2">
                            <select id="weekSelect" class="form-select form-select-solid w-120px" onchange="this.form.submit()" name="week">
                                @for($i = 1; $i <= 53; $i++)
                                    <option value="{{ $i }}" {{ (int)$week == $i ? 'selected' : '' }}>Week {{ $i }}</option>
                                @endfor
                            </select>
                            <select id="yearSelect" class="form-select form-select-solid w-100px" onchange="this.form.submit()" name="year">
                                @for($i = date('Y') - 2; $i <= date('Y'); $i++)
                                    <option value="{{ $i }}" {{ (int)$year == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        
                        <!-- Buttons -->
                        <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2"
                            style="height: 42px; padding: 0 20px;">
                            <i class="ki-duotone ki-filter fs-3"></i>
                            <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                            <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                        </button>
                        <a href="{{ route('accounting.weekly-report') }}" class="btn btn-light d-flex align-items-center justify-content-center gap-2"
                            style="height: 42px; padding: 0 20px;">
                            <i class="ki-duotone ki-arrow-rotate-left fs-3"></i>
                            <span class="d-none d-sm-inline">{{ __('accounting.reset') }}</span>
                            <span class="d-inline d-sm-none">{{ __('accounting.reset') }}</span>
                        </a>
                        
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
                
                <!-- Week Info -->
                <div class="alert alert-primary d-flex align-items-center p-5 mb-8">
                    <i class="ki-duotone ki-calendar-8 fs-2x me-3 text-primary">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                    </i>
                    <div class="d-flex flex-column">
                        <h4 class="mb-1">{{ __('accounting.week_of') }} {{ $weekStartDisplay }} - {{ $weekEndDisplay }}</h4>
                        <span class="text-muted">{{ __('accounting.week') }} {{ $week }}, {{ $year }}</span>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="row g-5 g-xl-8 mb-8">
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
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.week') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">Week {{ $week }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
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
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.period') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">{{ $weekStartDisplay }} - {{ $weekEndDisplay }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
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
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.weekly_result') }}</span>
                                    <span class="badge badge-light-{{ $summary['net_cash_flow'] >= 0 ? 'success' : 'danger' }} py-2 px-3">
                                        {{ $summary['net_cash_flow'] >= 0 ? __('accounting.profit') : __('accounting.loss') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 6-Month Comparison Chart -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.six_month_comparison') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.profit_loss_trend') }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-center">
                            <div class="w-100" style="max-width: 1000px; height: 400px;">
                                <canvas id="sixMonthChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Daily Breakdown -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-calendar-8 fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                            {{ __('accounting.daily_breakdown') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.day_by_day_activity') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if($dailyBreakdown->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="dailyBreakdownTable">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-120px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.transactions') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.deposits') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.withdrawals') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.daily_total') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.trend') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($dailyBreakdown as $day)
                                    @php
                                        $dailyNet = $day->deposits - $day->withdrawals;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fs-6 fw-bold text-gray-800">{{ date('M d, Y', strtotime($day->date)) }}</span>
                                            <span class="fs-7 text-gray-500 d-block">{{ date('l', strtotime($day->date)) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-light-secondary py-2 px-3">{{ number_format($day->transaction_count) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-success">{{ number_format($day->deposits, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-danger">{{ number_format($day->withdrawals, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ $dailyNet >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($dailyNet, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($day->daily_total > 0)
                                                <div class="d-flex align-items-center">
                                                    <div class="progress h-8px w-100 me-3">
                                                        @php
                                                            $depositPercent = $day->deposits > 0 ? ($day->deposits / $day->daily_total) * 100 : 0;
                                                            $withdrawalPercent = $day->withdrawals > 0 ? ($day->withdrawals / $day->daily_total) * 100 : 0;
                                                        @endphp
                                                        <div class="progress-bar bg-success" style="width: {{ $depositPercent }}%"></div>
                                                        <div class="progress-bar bg-danger" style="width: {{ $withdrawalPercent }}%"></div>
                                                    </div>
                                                    <span class="fs-7 fw-bold">{{ number_format($depositPercent, 0) }}%</span>
                                                </div>
                                            @else
                                                <span class="fs-7 text-gray-500">{{ __('accounting.no_activity') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-10">
                            <div class="text-muted fs-6">{{ __('accounting.no_transactions_for_this_week') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                
                <!-- Weekly Transactions -->
                <div class="card card-flush">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-table fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.weekly_transactions') }}
                        </h3>
                        <div class="card-toolbar">
                            <div class="d-flex align-items-center position-relative">
                                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <input type="text" id="searchTransactions" class="form-control form-control-solid w-250px ps-10" placeholder="{{ __('accounting.search_transactions') }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if($transactions->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="weeklyTransactionsTable">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-150px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-200px">{{ __('accounting.description') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.processed_by') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($transactions as $transaction)
                                    <tr>
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
                        <div class="mt-6 d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="text-gray-500 fs-7">
                                {{ __('accounting.showing') }} {{ $transactions->firstItem() ?? 0 }} 
                                {{ __('accounting.to') }} {{ $transactions->lastItem() ?? 0 }} 
                                {{ __('accounting.of') }} {{ $transactions->total() }} 
                                {{ __('accounting.entries') }}
                            </div>
                            <div>
                                {{ $transactions->appends(request()->query())->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                        @else
                        <div class="text-center py-10">
                            <div class="text-muted fs-6">{{ __('accounting.no_transactions_for_this_week') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                                
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function printReport() {
            window.print();
        }

        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchTransactions');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#weeklyTransactionsTable tbody tr');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                });
            }
        });
        
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
        
        // 6-Month Comparison Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('sixMonthChart');
            if (ctx) {
                const data = @json($sixMonthsData);
                const labels = data.map(d => d.month);
                const deposits = data.map(d => d.deposits);
                const withdrawals = data.map(d => d.withdrawals);
                const net = data.map(d => d.net);
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: '{{ __('accounting.deposits') }}',
                                data: deposits,
                                backgroundColor: 'rgba(40, 199, 111, 0.8)',
                                borderColor: 'rgb(40, 199, 111)',
                                borderWidth: 2,
                                borderRadius: 4,
                                order: 2
                            },
                            {
                                label: '{{ __('accounting.withdrawals') }}',
                                data: withdrawals,
                                backgroundColor: 'rgba(245, 101, 101, 0.8)',
                                borderColor: 'rgb(245, 101, 101)',
                                borderWidth: 2,
                                borderRadius: 4,
                                order: 3
                            },
                            {
                                label: '{{ __('accounting.net_profit_loss') }}',
                                data: net,
                                type: 'line',
                                backgroundColor: 'rgba(54, 153, 255, 0.1)',
                                borderColor: 'rgb(54, 153, 255)',
                                borderWidth: 4,
                                pointBackgroundColor: net.map(n => n >= 0 ? 'rgb(40, 199, 111)' : 'rgb(245, 101, 101)'),
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 7,
                                pointHoverRadius: 10,
                                tension: 0.3,
                                fill: true,
                                order: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    font: {
                                        size: 13,
                                        weight: 'bold'
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': $' + context.parsed.y.toFixed(2);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toFixed(0);
                                    },
                                    font: {
                                        size: 12
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 13,
                                        weight: 'bold'
                                    }
                                }
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        }
                    }
                });
            }
        });
    </script>
    @endpush
    
    @endsection
</x-app-layout>