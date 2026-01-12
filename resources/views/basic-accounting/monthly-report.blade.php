<x-app-layout>
    @section('title', __('accounting.monthly_report'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('accounting.monthly_report')}}</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.monthly-report') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.basic_accounting') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('accounting.monthly_report')}}</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <!-- Month Selector -->
                <div class="d-flex align-items-center">
                    <select id="monthSelect" class="form-select form-select-solid w-150px me-2" onchange="changeMonth()">
                        @php
                            $currentMonth = (int)$month;
                        @endphp
                        @for($i = 1; $i <= 12; $i++)
                            @php
                                $monthNames = [
                                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                ];
                            @endphp
                            <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ $currentMonth == $i ? 'selected' : '' }}>
                                {{ $monthNames[$i] }}
                            </option>
                        @endfor
                    </select>
                    <select id="yearSelect" class="form-select form-select-solid w-120px" onchange="changeMonth()">
                        @for($i = date('Y') - 2; $i <= date('Y'); $i++)
                            <option value="{{ $i }}" {{ (int)$year == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                
                <!-- Export Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" 
                            onclick="exportCurrentPage({tableId: 'dailyBreakdownTable', filename: 'monthly_report_{{ $year }}_{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}', sheetName: 'Daily Breakdown'})">
                                <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                {{ __('accounting.export_to_excel') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" 
                            onclick="exportCurrentPage({tableId: 'dailyBreakdownTable', filename: 'monthly_report_{{ $year }}_{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}', format: 'csv'})">
                                <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                {{ __('accounting.export_to_csv') }}
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" 
                            onclick="exportCurrentPage({tableId: 'categoryBreakdownTable', filename: 'monthly_categories_{{ $year }}_{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}', sheetName: 'Category Breakdown'})">
                                <i class="ki-duotone ki-category fs-2 me-2 text-info"></i>
                                {{ __('Export Category Breakdown') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" 
                            onclick="exportCurrentPage({tableId: 'methodBreakdownTable', filename: 'monthly_methods_{{ $year }}_{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}', sheetName: 'Payment Methods'})">
                                <i class="ki-duotone ki-wallet fs-2 me-2 text-warning"></i>
                                {{ __('Export Payment Methods') }}
                            </a>
                        </li>
                    </ul>
                </div>
                
                <button class="btn btn-sm btn-light" onclick="printReport()">
                    <i class="ki-duotone ki-printer fs-2"></i> {{ __('accounting.print') }}
                </button>
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                <!-- Monthly Summary Cards -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Total Transactions -->
                    <div class="col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.total_transactions') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1">{{ $summary['total_transactions'] }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.transactions') }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.month') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">
                                        @php
                                            $monthNames = [
                                                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                            ];
                                            $monthInt = (int)$month;
                                            $monthName = $monthNames[$monthInt] ?? 'Unknown';
                                        @endphp
                                        {{ $monthName }} {{ $year }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Amount -->
                    <div class="col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.total_amount') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1">${{ number_format($summary['total_amount'], 2) }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.total_value') }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.days_in_month') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">
                                        {{ date('t', strtotime($startDate)) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Deposits vs Withdrawals -->
                    <div class="col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.deposits') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-success me-2 lh-1">${{ number_format($summary['deposit_total'], 2) }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.money_in') }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.withdrawals') }}</span>
                                    <span class="fs-6 fw-bold text-danger">${{ number_format($summary['withdrawal_total'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Net Cash Flow -->
                    <div class="col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.net_cash_flow') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold {{ $summary['net_cash_flow'] >= 0 ? 'text-success' : 'text-danger' }} me-2 lh-1">
                                        ${{ number_format($summary['net_cash_flow'], 2) }}
                                    </span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.net_movement') }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.monthly_result') }}</span>
                                    <span class="badge badge-light-{{ $summary['net_cash_flow'] >= 0 ? 'success' : 'danger' }}">
                                        {{ $summary['net_cash_flow'] >= 0 ? __('accounting.profit') : __('accounting.loss') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Show message if no transactions -->
                @if($summary['total_transactions'] == 0)
                <div class="alert alert-info mb-8">
                    <i class="ki-duotone ki-information fs-2 me-2"></i>
                    {{ __('No transactions found for') }} {{ $monthName }} {{ $year }}.
                </div>
                @else
                
                <!-- Daily Breakdown -->
                <div class="card mb-8">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.daily_breakdown') }}</h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.day_by_day_activity') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="dailyBreakdownTable">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-120px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.transactions') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.deposits') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.withdrawals') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.daily_total') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.trend') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.daily_average') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dailyBreakdown as $day)
                                    <tr>
                                        <td>
                                            <span class="fs-7 fw-bold text-gray-800">{{ date('M d, Y', strtotime($day->date)) }}</span>
                                            <span class="fs-8 text-gray-500 d-block">{{ date('D', strtotime($day->date)) }}</span>
                                        </td>
                                        <td class="text-end">{{ number_format($day->transaction_count) }}</td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-success">${{ number_format($day->deposits, 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-danger">${{ number_format($day->withdrawals, 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            @php $dailyNet = $day->deposits - $day->withdrawals; @endphp
                                            <span class="fs-6 fw-bold {{ $dailyNet >= 0 ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($dailyNet, 2) }}
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
                                        <td class="text-end">
                                            @if($day->transaction_count > 0)
                                                ${{ number_format($day->daily_total / $day->transaction_count, 2) }}
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
                
                <!-- Category Breakdown -->
                <div class="card mb-8">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.category_breakdown') }}</h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.transactions_by_category') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="categoryBreakdownTable>
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-200px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.count') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.total_amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.average_amount') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.distribution') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.percentage') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categoryBreakdown as $category)
                                    <tr>
                                        <td>{{ $category->transaction_category }}</td>
                                        <td class="text-end">{{ number_format($category->count) }}</td>
                                        <td class="text-end">${{ number_format($category->total, 2) }}</td>
                                        <td class="text-end">${{ number_format($category->average, 2) }}</td>
                                        <td>
                                            @if($summary['total_amount'] > 0)
                                            <div class="d-flex align-items-center">
                                                <div class="progress h-8px w-100 me-3">
                                                    <div class="progress-bar bg-primary" 
                                                         style="width: {{ ($category->total / $summary['total_amount']) * 100 }}%"></div>
                                                </div>
                                                <span class="fs-7 fw-bold">{{ number_format(($category->total / $summary['total_amount']) * 100, 1) }}%</span>
                                            </div>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($summary['total_transactions'] > 0)
                                                {{ number_format(($category->count / $summary['total_transactions']) * 100, 1) }}%
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Method Breakdown -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.payment_method_breakdown') }}</h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.transactions_by_method') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="methodBreakdownTable">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-200px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.transaction_count') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.total_amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.average_transaction') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.usage_distribution') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.most_active_day') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($methodBreakdown as $method)
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
                                                    <span class="fs-6 fw-bold text-gray-800">{{ $method->paymentMethod->name ?? 'N/A' }}</span>
                                                    <span class="fs-7 text-gray-500">{{ $method->paymentMethod->type ?? '' }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">{{ number_format($method->transaction_count) }}</td>
                                        <td class="text-end">${{ number_format($method->total_amount, 2) }}</td>
                                        <td class="text-end">
                                            @if($method->transaction_count > 0)
                                                ${{ number_format($method->total_amount / $method->transaction_count, 2) }}
                                            @else
                                                <span class="fs-7 text-gray-500">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($summary['total_amount'] > 0)
                                            <div class="d-flex align-items-center">
                                                <div class="progress h-8px w-100 me-3">
                                                    <div class="progress-bar bg-primary" 
                                                         style="width: {{ ($method->total_amount / $summary['total_amount']) * 100 }}%"></div>
                                                </div>
                                                <span class="fs-7 fw-bold">{{ number_format(($method->total_amount / $summary['total_amount']) * 100, 1) }}%</span>
                                            </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($method->transaction_count > 0)
                                                <span class="fs-7 text-gray-600">{{ __('accounting.not_applicable') }}</span>
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
                
                @endif {{-- End of if transactions exist --}}
                
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        function changeMonth() {
            const month = document.getElementById('monthSelect').value;
            const year = document.getElementById('yearSelect').value;
            window.location.href = '{{ route("accounting.monthly-report") }}?month=' + month + '&year=' + year;
        }
        
        function printReport() {
            window.print();
        }
        
        function exportToExcel() {
            alert('Export to Excel functionality would be implemented here');
        }
    </script>
    @endpush
    
    @endsection
</x-app-layout>