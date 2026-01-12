<x-app-layout>
    @section('title', __('accounting.account_balances'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('accounting.account_balances')}}</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        @php
                            $previousUrl = url()->previous();
                            $previousRouteName = optional(app('router')->getRoutes()->match(request()->create($previousUrl)))->getName();
                            $formattedRouteName = $previousRouteName 
                                ? Str::of($previousRouteName)->replace('.', ' ')->title() 
                                : __('auth._back');
                        @endphp
                        <a href="{{ $previousUrl }}" class="text-muted text-hover-primary">
                            {{ $formattedRouteName }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('accounting.account_balances')}}</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" 
                            onclick="exportCurrentPage({tableId: 'accountsTable', filename: 'account_balances', sheetName: 'Account Balances', excludeColumns: [7]})">
                                <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                {{ __('accounting.export_to_excel') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" 
                            onclick="exportCurrentPage({tableId: 'accountsTable', filename: 'account_balances', format: 'csv', excludeColumns: [7]})">
                                <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                {{ __('accounting.export_to_csv') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                
                <!-- Summary Cards -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Total Balance Card -->
                    <div class="col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.total_balance') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1">{{ number_format($summary['total_current'], 2) }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.current_balance') }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.available_balance') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">{{ number_format($summary['total_available'], 2) }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.pending_balance') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">{{ number_format($summary['total_pending'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Accounts Count Card -->
                    <div class="col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.accounts_summary') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1">{{ $summary['accounts_count'] }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.total_accounts') }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.active_accounts') }}</span>
                                    <span class="fs-6 fw-bold text-success">{{ $accounts->where('is_active', true)->count() }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.inactive_accounts') }}</span>
                                    <span class="fs-6 fw-bold text-danger">{{ $accounts->where('is_active', false)->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Balance Distribution Card -->
                    <div class="col-xl-6">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.balance_distribution') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-wrap">
                                    @foreach($accounts->sortByDesc('current_balance')->take(5) as $account)
                                    <div class="d-flex flex-column me-7 mb-5">
                                        <span class="fs-4 fw-bold text-gray-800">{{ $account->name }}</span>
                                        <span class="fs-6 fw-semibold text-gray-500">{{ $account->currency->code ?? 'USD' }} {{ number_format($account->current_balance, 2) }}</span>
                                        <div class="d-flex align-items-center mt-2">
                                            <div class="progress h-6px w-100 me-3">
                                                <div class="progress-bar bg-primary" role="progressbar" 
                                                     style="width: {{ ($account->current_balance / $summary['total_current']) * 100 }}%">
                                                </div>
                                            </div>
                                            <span class="fs-7 fw-bold">{{ number_format(($account->current_balance / $summary['total_current']) * 100, 1) }}%</span>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Accounts Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.account_details') }}</h3>
                        <div class="card-toolbar">
                            <div class="d-flex align-items-center position-relative">
                                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <input type="text" id="searchAccounts" class="form-control form-control-solid w-250px ps-10" placeholder="{{ __('accounting.search_accounts') }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="accountsTable">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-200px">{{ __('accounting.account_name') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.current_balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.available_balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.pending_balance') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.status') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.last_transaction') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($accounts as $account)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-45px me-3">
                                                    <span class="symbol-label bg-light-{{ $account->is_active ? 'success' : 'danger' }}">
                                                        <i class="ki-duotone ki-wallet fs-2x text-{{ $account->is_active ? 'success' : 'danger' }}">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                    </span>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fs-6 fw-bold text-gray-800">{{ $account->name }}</span>
                                                    <span class="fs-7 text-gray-500">{{ $account->account_number ? '****' . substr($account->account_number, -4) : 'N/A' }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $account->type === 'cash' ? 'warning' : ($account->type === 'bank_account' ? 'info' : 'primary') }}">
                                                {{ ucfirst(str_replace('_', ' ', $account->type)) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ $account->current_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $account->currency->symbol ?? '$' }}{{ number_format($account->current_balance, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-700">
                                                {{ $account->currency->symbol ?? '$' }}{{ number_format($account->available_balance, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-500">
                                                {{ $account->currency->symbol ?? '$' }}{{ number_format($account->pending_balance, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $account->is_active ? 'success' : 'danger' }}">
                                                {{ $account->is_active ? __('accounting.active') : __('accounting.inactive') }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if($account->last_transaction_at)
                                                <span class="fs-7 text-gray-500">{{ $account->last_transaction_at->format('M d, Y') }}</span>
                                            @else
                                                <span class="fs-7 text-gray-400">{{ __('accounting.no_transactions') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('accounting.transaction-ledger', ['payment_method_id' => $account->id]) }}" 
                                               class="btn btn-sm btn-light btn-active-light-primary">
                                                {{ __('accounting.view_transactions') }}
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Transactions -->
                @if($recentTransactions->count() > 0)
                <div class="card mt-8">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.recent_transactions') }}</h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-150px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.account') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentTransactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->transaction_date->format('M d, Y H:i') }}</td>
                                        <td>{{ $transaction->paymentMethod->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge badge-light-{{ $transaction->transaction_type === 'DEPOSIT' ? 'success' : ($transaction->transaction_type === 'WITHDRAWAL' ? 'danger' : 'info') }}">
                                                {{ $transaction->transaction_type }}
                                            </span>
                                        </td>
                                        <td>{{ $transaction->transaction_category }}</td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ in_array($transaction->transaction_type, ['DEPOSIT', 'TRANSFER_IN', 'REFUND']) ? 'text-success' : 'text-danger' }}">
                                                {{ $transaction->paymentMethod->currency->symbol ?? '$' }}{{ number_format($transaction->amount, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-7 text-gray-500">
                                                {{ $transaction->paymentMethod->currency->symbol ?? '$' }}{{ number_format($transaction->balance_after, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
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
                @endif
                
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        
        // Search functionality
        document.getElementById('searchAccounts').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#accountsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
    @endpush
    
    @endsection
</x-app-layout>