<x-app-layout>
    @section('title', __('accounting.daily_summary'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
            <!-- Left side - Title and Breadcrumb -->
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                    {{__('accounting.daily_summary')}}
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
                    <li class="breadcrumb-item text-muted">{{__('accounting.daily_summary')}}</li>
                </ul>
            </div>

            <!-- Right side - Actions -->
            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-lg-auto">
                <!-- Date Selectors - Fixed duplicate ID issue -->
                <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto">
                    <input type="date" id="dateSelector" class="form-control form-control-solid w-100 w-sm-200px" 
                        value="{{ $date }}" onchange="changeDate()">
                </div>
                
                <!-- Action Buttons Group -->
                <div class="d-flex flex-row gap-2 w-100 w-sm-auto">
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
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                <!-- Daily Summary Cards -->
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
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.date') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">
                                        {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
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
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.day_of_week') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">
                                        {{ \Carbon\Carbon::parse($date)->format('l') }}
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
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.daily_result') }}</span>
                                    <span class="badge badge-light-{{ $summary['net_cash_flow'] >= 0 ? 'success' : 'danger' }}">
                                        {{ $summary['net_cash_flow'] >= 0 ? __('accounting.positive') : __('accounting.negative') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transactions by Type -->
                <div class="card mb-8">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.transactions_by_type') }}</h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.type_breakdown') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="transactionsByTypeTable">                               
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-150px">{{ __('accounting.transaction_type') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.count') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.total_amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.average_amount') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.percentage') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.trend') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($byType as $type => $data)
                                    <tr>
                                        <td>
                                            <span class="badge badge-light-{{ $type === 'DEPOSIT' ? 'success' : ($type === 'WITHDRAWAL' ? 'danger' : 'info') }}">
                                                {{ $type }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ number_format($data['count']) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ $type === 'DEPOSIT' ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($data['total'], 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">${{ number_format($data['average'], 2) }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress h-8px w-100 me-3">
                                                    <div class="progress-bar bg-{{ $type === 'DEPOSIT' ? 'success' : 'danger' }}" role="progressbar" 
                                                         style="width: {{ ($data['count'] / $summary['total_transactions']) * 100 }}%">
                                                    </div>
                                                </div>
                                                <span class="fs-7 fw-bold">{{ number_format(($data['count'] / $summary['total_transactions']) * 100, 1) }}%</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if($data['count'] > 0)
                                                <span class="badge badge-light-{{ $data['average'] > 100 ? ($type === 'DEPOSIT' ? 'success' : 'danger') : 'secondary' }}">
                                                    @if($data['average'] > 100)
                                                        {{ __('accounting.high_value') }}
                                                    @elseif($data['average'] > 50)
                                                        {{ __('accounting.medium_value') }}
                                                    @else
                                                        {{ __('accounting.low_value') }}
                                                    @endif
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Transactions by Category -->
                <div class="card mb-8">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.transactions_by_category') }}</h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.category_breakdown') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="transactionsByCategoryTable">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-200px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.count') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.total_amount') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.distribution') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.percentage') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($byCategory as $category => $data)
                                    <tr>
                                        <td>
                                            <span class="fs-6 fw-bold text-gray-800">{{ $category }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ number_format($data['count']) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-800">${{ number_format($data['total'], 2) }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress h-8px w-100 me-3">
                                                    <div class="progress-bar bg-primary" role="progressbar" 
                                                         style="width: {{ ($data['total'] / $summary['total_amount']) * 100 }}%">
                                                    </div>
                                                </div>
                                                <span class="fs-7 fw-bold">{{ number_format(($data['total'] / $summary['total_amount']) * 100, 1) }}%</span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ number_format(($data['count'] / $summary['total_transactions']) * 100, 1) }}%</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Balance Changes -->
                <div class="card mb-8">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.balance_changes') }}</h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.account_balances') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="balanceChangesTable">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-200px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.starting_balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.ending_balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.net_change') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.change_type') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.change_percentage') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
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
                                            <span class="fs-6 text-gray-700">${{ number_format($change['starting_balance'], 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-800">${{ number_format($change['ending_balance'], 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ $change['net_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($change['net_change'], 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $change['net_change'] >= 0 ? 'success' : 'danger' }}">
                                                {{ $change['net_change'] >= 0 ? __('accounting.increase') : __('accounting.decrease') }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if($change['starting_balance'] != 0)
                                                @php
                                                    $percentage = ($change['net_change'] / $change['starting_balance']) * 100;
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
                
                <!-- Daily Transactions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.daily_transactions') }}</h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.all_transactions_for') }} {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="dailyTransactionsTable">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-150px">{{ __('accounting.time') }}</th>
                                        <th class="min-w-200px">{{ __('accounting.description') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.balance') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transactions as $transaction)
                                    <tr>
                                        <td>
                                            <span class="fs-7 fw-bold text-gray-800">{{ $transaction->transaction_date->format('H:i') }}</span>
                                        </td>
                                        <td>
                                            <span class="fs-7 text-gray-800">{{ Str::limit($transaction->description, 50) }}</span>
                                            @if($transaction->notes)
                                                <span class="fs-8 text-gray-500 d-block">{{ Str::limit($transaction->notes, 30) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fs-7 text-gray-800">{{ $transaction->paymentMethod->name ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $transaction->transaction_type === 'DEPOSIT' ? 'success' : ($transaction->transaction_type === 'WITHDRAWAL' ? 'danger' : 'info') }}">
                                                {{ $transaction->transaction_type }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fs-7 text-gray-600">{{ $transaction->transaction_category }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ in_array($transaction->transaction_type, ['DEPOSIT', 'TRANSFER_IN', 'REFUND']) ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($transaction->amount, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-7 text-gray-700">${{ number_format($transaction->balance_after, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $transaction->status === 'COMPLETED' ? 'success' : ($transaction->status === 'PENDING' ? 'warning' : 'danger') }}">
                                                {{ $transaction->status }}
                                            </span>
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
    </div>
    
    @push('scripts')
    <script>
        function changeDate() {
            const date = document.getElementById('dateSelector').value;
            window.location.href = '{{ route("accounting.daily-summary") }}?date=' + date;
        }
        
        function printReport() {
            window.print();
        }
        
        
        // Initialize date selector with today's date if empty
        document.addEventListener('DOMContentLoaded', function() {
            if (!document.getElementById('dateSelector').value) {
                document.getElementById('dateSelector').value = new Date().toISOString().split('T')[0];
            }
        });
    </script>
    @endpush
    
    @endsection
</x-app-layout>