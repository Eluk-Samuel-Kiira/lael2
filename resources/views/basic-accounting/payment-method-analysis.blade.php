<x-app-layout>
    @section('title', __('accounting.payment_method_analysis'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{ __('accounting.payment_method_analysis') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.payment-method-analysis') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.basic_accounting') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.payment_method_analysis') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                <form method="GET" action="{{ route('accounting.payment-method-analysis') }}" class="w-100" id="filterForm">
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
                        <a href="{{ route('accounting.payment-method-analysis') }}" class="btn btn-light d-flex align-items-center justify-content-center gap-2"
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
                
                <!-- Summary Stats -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Total Balance -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ number_format($stats['total_balance'], 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_balance') }}</span>
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
                    
                    <!-- Total Transactions -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-info">{{ number_format($stats['total_transactions']) }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_transactions') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.total_amount') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">{{ number_format($stats['total_transaction_amount'], 2) }} {{ currency_symbol() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Most Active Method -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    @if($stats['most_active_method'])
                                        <span class="fs-4 fw-bold text-primary">{{ $stats['most_active_method']->name }}</span>
                                        <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.most_active_method') }}</span>
                                    @else
                                        <span class="fs-2hx fw-bold text-gray-400">-</span>
                                        <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.no_activity') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                @if($stats['most_active_method'])
                                    <div class="d-flex flex-stack">
                                        <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.transactions') }}</span>
                                        <span class="fw-bold text-gray-700 fs-7">{{ number_format($stats['most_active_method']->transaction_stats['total_transactions'] ?? 0) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Highest Balance Method -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    @if($stats['highest_balance_method'])
                                        <span class="fs-4 fw-bold text-success">{{ $stats['highest_balance_method']->name }}</span>
                                        <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.highest_balance') }}</span>
                                    @else
                                        <span class="fs-2hx fw-bold text-gray-400">-</span>
                                        <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.no_balance') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                @if($stats['highest_balance_method'])
                                    <div class="d-flex flex-stack">
                                        <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.balance') }}</span>
                                        <span class="fw-bold text-gray-700 fs-7">{{ number_format($stats['highest_balance_method']->current_balance, 2) }} {{ currency_symbol() }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Methods Analysis Table -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-wallet fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.payment_methods_analysis') }}
                        </h3>
                        <div class="card-toolbar">
                            <div class="d-flex align-items-center position-relative">
                                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <input type="text" id="searchMethods" class="form-control form-control-solid w-250px ps-10" placeholder="{{ __('accounting.search_methods') }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if($paymentMethods->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="methodsTable">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-200px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.current_balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.total_transactions') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.deposits') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.withdrawals') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.net_flow') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.average_transaction') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.activity_rate') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($paymentMethods as $method)
                                    @php
                                        $stats = $method->transaction_stats;
                                        $netFlow = $stats['deposit_total'] - $stats['withdrawal_total'];
                                        $activityRate = $stats['total_transactions'] > 0 ? min(100, ($stats['total_transactions'] / max($stats['total_transactions'], 1)) * 100) : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-45px me-3">
                                                    <span class="symbol-label bg-light-{{ $method->is_active ? 'primary' : 'danger' }}">
                                                        @switch($method->type)
                                                            @case('cash')
                                                                <i class="ki-duotone ki-dollar fs-2x text-{{ $method->is_active ? 'primary' : 'danger' }}">
                                                                    <span class="path1"></span>
                                                                    <span class="path2"></span>
                                                                </i>
                                                                @break
                                                            @case('bank_account')
                                                                <i class="ki-duotone ki-bank fs-2x text-{{ $method->is_active ? 'primary' : 'danger' }}">
                                                                    <span class="path1"></span>
                                                                    <span class="path2"></span>
                                                                </i>
                                                                @break
                                                            @case('card')
                                                                <i class="ki-duotone ki-credit-cart fs-2x text-{{ $method->is_active ? 'primary' : 'danger' }}">
                                                                    <span class="path1"></span>
                                                                    <span class="path2"></span>
                                                                </i>
                                                                @break
                                                            @default
                                                                <i class="ki-duotone ki-wallet fs-2x text-{{ $method->is_active ? 'primary' : 'danger' }}">
                                                                    <span class="path1"></span>
                                                                    <span class="path2"></span>
                                                                </i>
                                                        @endswitch
                                                    </span>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fs-6 fw-bold text-gray-800">{{ $method->name }}</span>
                                                    <span class="fs-7 text-gray-500">{{ $method->code }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $typeColors = [
                                                    'cash' => 'warning',
                                                    'bank_account' => 'info',
                                                    'card' => 'primary',
                                                    'digital_wallet' => 'success',
                                                    'mobile_money' => 'danger',
                                                    'check' => 'secondary',
                                                    'other' => 'dark'
                                                ];
                                            @endphp
                                            <span class="badge badge-light-{{ $typeColors[$method->type] ?? 'secondary' }} py-2 px-3">
                                                {{ $method->getTypeLabel() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ $method->current_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($method->current_balance, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-light-secondary py-2 px-3">{{ number_format($stats['total_transactions']) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-success">
                                                {{ number_format($stats['deposit_total'], 2) }} {{ currency_symbol() }}
                                            </span>
                                            <span class="fs-8 text-gray-500 d-block">{{ number_format($stats['deposit_count']) }} {{ __('accounting.txns') }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-danger">
                                                {{ number_format($stats['withdrawal_total'], 2) }} {{ currency_symbol() }}
                                            </span>
                                            <span class="fs-8 text-gray-500 d-block">{{ number_format($stats['withdrawal_count']) }} {{ __('accounting.txns') }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ $netFlow >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($netFlow, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if($stats['total_transactions'] > 0)
                                                <span class="fs-6 text-gray-700">
                                                    {{ number_format($stats['total_amount'] / $stats['total_transactions'], 2) }} {{ currency_symbol() }}
                                                </span>
                                            @else
                                                <span class="fs-7 text-gray-500">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress h-8px w-100 me-3">
                                                    <div class="progress-bar bg-primary" role="progressbar" 
                                                         style="width: {{ $activityRate }}%">
                                                    </div>
                                                </div>
                                                <span class="fs-7 fw-bold">{{ number_format($activityRate, 1) }}%</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $method->is_active ? 'success' : 'danger' }} py-2 px-3">
                                                {{ $method->is_active ? __('accounting.active') : __('accounting.inactive') }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-10">
                            <div class="text-muted fs-6">{{ __('accounting.no_payment_methods_found') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                
                <!-- Performance Comparison Chart -->
                <div class="card card-flush">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.performance_comparison') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.comparison_chart') }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($paymentMethods->count() > 0)
                        <!-- Method Cards -->
                        <div class="row g-5 g-xl-8 mb-8">
                            @foreach($paymentMethods->take(4) as $method)
                            @php
                                $methodStats = $method->transaction_stats;
                                $netFlow = $methodStats['deposit_total'] - $methodStats['withdrawal_total'];
                            @endphp
                            <div class="col-sm-6 col-xl-3">
                                <div class="card card-flush h-md-100">
                                    <div class="card-header pt-5">
                                        <div class="card-title">
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-35px me-3">
                                                    <span class="symbol-label bg-light-primary">
                                                        <i class="ki-duotone ki-wallet fs-2 text-primary">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                    </span>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fs-6 fw-bold text-gray-800">{{ $method->name }}</span>
                                                    <span class="fs-7 text-gray-500">{{ $method->getTypeLabel() }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div class="d-flex flex-stack mb-2">
                                            <span class="text-gray-500 fs-7">{{ __('accounting.balance') }}</span>
                                            <span class="fs-6 fw-bold {{ $method->current_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($method->current_balance, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </div>
                                        <div class="d-flex flex-stack mb-2">
                                            <span class="text-gray-500 fs-7">{{ __('accounting.transactions') }}</span>
                                            <span class="fs-6 fw-bold text-gray-700">
                                                {{ number_format($methodStats['total_transactions']) }}
                                            </span>
                                        </div>
                                        <div class="d-flex flex-stack mb-2">
                                            <span class="text-gray-500 fs-7">{{ __('accounting.deposits') }}</span>
                                            <span class="fs-6 fw-bold text-success">
                                                {{ number_format($methodStats['deposit_total'], 2) }} {{ currency_symbol() }}
                                            </span>
                                        </div>
                                        <div class="d-flex flex-stack mb-2">
                                            <span class="text-gray-500 fs-7">{{ __('accounting.withdrawals') }}</span>
                                            <span class="fs-6 fw-bold text-danger">
                                                {{ number_format($methodStats['withdrawal_total'], 2) }} {{ currency_symbol() }}
                                            </span>
                                        </div>
                                        <div class="d-flex flex-stack">
                                            <span class="text-gray-500 fs-7">{{ __('accounting.net_flow') }}</span>
                                            <span class="fs-6 fw-bold {{ $netFlow >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($netFlow, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        <!-- Chart -->
                        <div class="d-flex justify-content-center">
                            <div class="w-100" style="max-width: 900px; height: 350px;">
                                <canvas id="performanceChart"></canvas>
                            </div>
                        </div>
                        @else
                        <div class="text-center py-10">
                            <div class="text-muted fs-6">{{ __('accounting.no_payment_methods_found') }}</div>
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
        // Search functionality
        document.getElementById('searchMethods').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#methodsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
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
        
        // Initialize performance chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('performanceChart');
            if (ctx) {
                const methods = @json($paymentMethods->take(5)->values());
                const labels = methods.map(m => m.name);
                const depositData = methods.map(m => m.transaction_stats.deposit_total || 0);
                const withdrawalData = methods.map(m => m.transaction_stats.withdrawal_total || 0);
                const balanceData = methods.map(m => m.current_balance || 0);
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: '{{ __('accounting.deposits') }}',
                                data: depositData,
                                backgroundColor: 'rgba(40, 199, 111, 0.8)',
                                borderColor: 'rgb(40, 199, 111)',
                                borderWidth: 2,
                                borderRadius: 4,
                            },
                            {
                                label: '{{ __('accounting.withdrawals') }}',
                                data: withdrawalData,
                                backgroundColor: 'rgba(245, 101, 101, 0.8)',
                                borderColor: 'rgb(245, 101, 101)',
                                borderWidth: 2,
                                borderRadius: 4,
                            },
                            {
                                label: '{{ __('accounting.balance') }}',
                                data: balanceData,
                                type: 'line',
                                backgroundColor: 'rgba(54, 153, 255, 0.1)',
                                borderColor: 'rgb(54, 153, 255)',
                                borderWidth: 3,
                                pointBackgroundColor: 'rgb(54, 153, 255)',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 5,
                                tension: 0.3,
                                fill: true,
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
                                        size: 12,
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
                                        size: 11
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
                                        size: 12,
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