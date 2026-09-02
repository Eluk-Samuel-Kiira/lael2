<x-app-layout>
    @section('title', __('accounting.user_performance_report'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                    {{ __('accounting.user_performance_report') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.user-performance-report') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.basic_accounting') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.user_performance_report') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-lg-auto">
                <form method="GET" action="{{ route('accounting.user-performance-report') }}" class="w-100" id="filterForm">
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
                        
                        <!-- Limit -->
                        <div class="d-flex align-items-center">
                            <label class="fw-semibold fs-7 text-gray-600 me-2">{{ __('accounting.show') }}</label>
                            <select name="limit" class="form-select form-select-sm form-select-solid w-80px" onchange="this.form.submit()">
                                <option value="5" {{ $limit == 5 ? 'selected' : '' }}>5</option>
                                <option value="10" {{ $limit == 10 ? 'selected' : '' }}>10</option>
                                <option value="20" {{ $limit == 20 ? 'selected' : '' }}>20</option>
                                <option value="50" {{ $limit == 50 ? 'selected' : '' }}>50</option>
                            </select>
                        </div>
                        
                        <!-- Buttons -->
                        <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2"
                            style="height: 42px; padding: 0 20px;">
                            <i class="ki-duotone ki-filter fs-3"></i>
                            <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                            <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                        </button>
                        <a href="{{ route('accounting.user-performance-report') }}" class="btn btn-light d-flex align-items-center justify-content-center gap-2"
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
                
               <!-- Summary Stats with smaller currency text -->
                <div class="row g-5 g-xl-8 mb-8">
                    <div class="col-sm-6 col-xl-2">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $summary['total_users'] }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_users') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-2">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-info">{{ number_format($summary['total_transactions']) }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_transactions') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-2">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-1 fw-bold text-gray-800">{{ number_format($summary['total_volume'], 2) }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_volume') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-2">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-1 fw-bold text-gray-800">{{ number_format($summary['average_per_user'], 2) }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.avg_per_user') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-2">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-1 fw-bold text-success">{{ number_format($summary['total_profit'], 2) }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_profit') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-2">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    @if($summary['best_user'])
                                        <span class="fs-4 fw-bold text-primary text-truncate" style="max-width: 120px;">{{ Str::limit($summary['best_user']->user_name, 12) }}</span>
                                        <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.best_performer') }}</span>
                                    @else
                                        <span class="fs-2hx fw-bold text-gray-400">-</span>
                                        <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.best_performer') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top Profit Makers -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-arrow-up fs-2 me-2 text-success">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.top_profit_makers') }}
                            <span class="badge badge-light-success ms-2 py-2 px-3">{{ $topProfitMakers->count() }} {{ __('accounting.users') }}</span>
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.highest_net_positive') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if($topProfitMakers->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-50px">#</th>
                                        <th class="min-w-200px">{{ __('accounting.user') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.transactions') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.deposits') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.withdrawals') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.net_profit') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.avg_transaction') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.largest_txn') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.performance') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($topProfitMakers as $index => $user)
                                    @php
                                        $netAmount = $user->net_amount_display;
                                        $totalAmount = $user->total_amount_display;
                                        $performancePercent = $totalAmount > 0 ? ($netAmount / $totalAmount) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-light-success py-2 px-3 fs-6">{{ $index + 1 }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fs-6 fw-bold text-gray-800">{{ $user->user_name }}</span>
                                                <span class="fs-7 text-gray-500">{{ $user->user_email }}</span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-light-secondary py-2 px-3">{{ number_format($user->total_transactions) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-success">{{ number_format($user->total_deposits_display, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-danger">{{ number_format($user->total_withdrawals_display, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-5 fw-bold text-success">{{ number_format($netAmount, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ number_format($user->average_transaction_display, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ number_format($user->largest_transaction_display, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress h-8px w-100 me-3">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: {{ min($performancePercent, 100) }}%">
                                                    </div>
                                                </div>
                                                <span class="fs-7 fw-bold text-success">{{ number_format($performancePercent, 1) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-10">
                            <div class="text-muted fs-6">{{ __('accounting.no_profit_makers') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                
                <!-- Top Loss Makers -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-arrow-down fs-2 me-2 text-danger">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.top_loss_makers') }}
                            <span class="badge badge-light-danger ms-2 py-2 px-3">{{ $topLossMakers->count() }} {{ __('accounting.users') }}</span>
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.highest_net_negative') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if($topLossMakers->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-50px">#</th>
                                        <th class="min-w-200px">{{ __('accounting.user') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.transactions') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.deposits') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.withdrawals') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.net_loss') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.avg_transaction') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.largest_txn') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.performance') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($topLossMakers as $index => $user)
                                    @php
                                        $netAmount = $user->net_amount_display;
                                        $totalAmount = $user->total_amount_display;
                                        $performancePercent = $totalAmount > 0 ? (abs($netAmount) / $totalAmount) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-light-danger py-2 px-3 fs-6">{{ $index + 1 }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fs-6 fw-bold text-gray-800">{{ $user->user_name }}</span>
                                                <span class="fs-7 text-gray-500">{{ $user->user_email }}</span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-light-secondary py-2 px-3">{{ number_format($user->total_transactions) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-success">{{ number_format($user->total_deposits_display, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-danger">{{ number_format($user->total_withdrawals_display, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-5 fw-bold text-danger">{{ number_format(abs($netAmount), 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ number_format($user->average_transaction_display, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ number_format($user->largest_transaction_display, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress h-8px w-100 me-3">
                                                    <div class="progress-bar bg-danger" role="progressbar" 
                                                         style="width: {{ min($performancePercent, 100) }}%">
                                                    </div>
                                                </div>
                                                <span class="fs-7 fw-bold text-danger">{{ number_format($performancePercent, 1) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-10">
                            <div class="text-muted fs-6">{{ __('accounting.no_loss_makers') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                
                <!-- Recent Transactions for Top Users -->
                @if($recentTransactions->count() > 0)
                <div class="card card-flush">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-clock fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.recent_transactions_top_users') }}
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
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="recentTransactionsTable">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-150px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.user') }}</th>
                                        <th class="min-w-200px">{{ __('accounting.description') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($recentTransactions as $transaction)
                                    <tr>
                                        <td>
                                            <span class="fs-6 fw-bold text-gray-800">{{ $transaction->transaction_date->format('M d, Y') }}</span>
                                            <span class="fs-7 text-gray-500 d-block">{{ $transaction->transaction_date->format('H:i:s') }}</span>
                                        </td>
                                        <td>
                                            <span class="fs-6 fw-bold text-gray-800">{{ $transaction->user->name ?? 'System' }}</span>
                                        </td>
                                        <td>
                                            <span class="fs-6 text-gray-800">{{ Str::limit($transaction->description, 40) }}</span>
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
                                {{ __('accounting.showing') }} {{ $recentTransactions->firstItem() ?? 0 }} 
                                {{ __('accounting.to') }} {{ $recentTransactions->lastItem() ?? 0 }} 
                                {{ __('accounting.of') }} {{ $recentTransactions->total() }} 
                                {{ __('accounting.entries') }}
                            </div>
                            <div>
                                {{ $recentTransactions->appends(request()->query())->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        function printReport() {
            window.print();
        }

        // Search functionality for Recent Transactions
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchTransactions');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#recentTransactionsTable tbody tr');
                    
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
            }
        });
    </script>
    @endpush
    
    @endsection
</x-app-layout>