<x-app-layout>
    @section('title', __('accounting.account_balances'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    {{ __('accounting.account_balances') }}
                </h1>
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
                    <li class="breadcrumb-item text-muted">{{ __('accounting.account_balances') }}</li>
                </ul>
            </div>
            
            <div class="d-flex align-items-center gap-2 gap-lg-3 flex-wrap">
                <!-- Location Filter -->
                <div class="d-flex align-items-center bg-light-primary rounded-3 px-3 py-2">
                    <i class="ki-duotone ki-geolocation fs-2 text-primary me-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <select class="form-select form-select-sm form-select-solid border-0 bg-transparent ps-0 w-150px w-md-200px" 
                            name="location_id" 
                            data-control="select2" 
                            id="locationFilter" 
                            onchange="applyLocationFilter()"
                            data-placeholder="{{ __('pagination.all_locations') }}">
                        <option value="">{{ __('pagination.all_locations') }}</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ ($locationId ?? '') == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Export Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle d-flex align-items-center gap-2" 
                            type="button" 
                            data-bs-toggle="dropdown" 
                            aria-expanded="false">
                        <i class="ki-duotone ki-file-down fs-2"></i>
                        <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
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
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ number_format($summary['total_current'], 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_balance') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.available_balance') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">{{ number_format($summary['total_available'], 2) }} {{ currency_symbol() }}</span>
                                </div>
                                <div class="d-flex flex-stack mt-2">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.pending_balance') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">{{ number_format($summary['total_pending'], 2) }} {{ currency_symbol() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Accounts Count Card -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $summary['accounts_count'] }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_accounts') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <div class="d-flex align-items-center">
                                        <span class="bullet bullet-dot bg-success h-10px w-10px me-2"></span>
                                        <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.active_accounts') }}</span>
                                    </div>
                                    <span class="fs-6 fw-bold text-success">{{ $accounts->where('is_active', true)->count() }}</span>
                                </div>
                                <div class="d-flex flex-stack mt-2">
                                    <div class="d-flex align-items-center">
                                        <span class="bullet bullet-dot bg-danger h-10px w-10px me-2"></span>
                                        <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.inactive_accounts') }}</span>
                                    </div>
                                    <span class="fs-6 fw-bold text-danger">{{ $accounts->where('is_active', false)->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Balance Distribution Card -->
                    <div class="col-xl-6">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <h3 class="card-title fw-bold text-gray-800">{{ __('accounting.balance_distribution') }}</h3>
                            </div>
                            <div class="card-body pt-0">
                                @if($summary['total_current'] > 0)
                                    <div class="d-flex flex-wrap gap-4">
                                        @foreach($accounts->sortByDesc('current_balance')->take(5) as $account)
                                        <div class="d-flex flex-column flex-grow-1 min-w-120px">
                                            <div class="d-flex align-items-center">
                                                <span class="fw-bold text-gray-800 fs-6">{{ Str::limit($account->name, 20) }}</span>
                                            </div>
                                            <span class="fs-7 fw-semibold text-gray-500">{{ number_format($account->current_balance, 2) }} {{ currency_symbol() }}</span>
                                            <div class="progress h-6px mt-2">
                                                <div class="progress-bar bg-primary" role="progressbar" 
                                                     style="width: {{ ($account->current_balance / $summary['total_current']) * 100 }}%">
                                                </div>
                                            </div>
                                            <span class="fs-7 fw-bold text-gray-600 mt-1">{{ number_format(($account->current_balance / $summary['total_current']) * 100, 1) }}%</span>
                                        </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-5">
                                        <span class="text-gray-500">{{ __('accounting.no_balances') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Accounts Table -->
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">{{ __('accounting.account_details') }}</h3>
                        <div class="card-toolbar">
                            <div class="d-flex align-items-center position-relative">
                                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <input type="text" id="searchAccounts" 
                                       class="form-control form-control-solid w-250px ps-10" 
                                       placeholder="{{ __('accounting.search_accounts') }}">
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="accountsTable">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-200px">{{ __('accounting.account_name') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.current_balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.available_balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.pending_balance') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.status') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.last_transaction') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @forelse($accounts as $account)
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
                                            <span class="badge badge-light-{{ $typeColors[$account->type] ?? 'secondary' }} py-2 px-3">
                                                {{ ucfirst(str_replace('_', ' ', $account->type)) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ $account->current_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($account->current_balance, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-700">
                                                {{ number_format($account->available_balance, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-500">
                                                {{ number_format($account->pending_balance, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $account->is_active ? 'success' : 'danger' }} py-2 px-3">
                                                {{ $account->is_active ? __('accounting.active') : __('accounting.inactive') }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if($account->last_transaction_at)
                                                <span class="fs-7 text-gray-500">
                                                    <i class="ki-duotone ki-calendar-8 fs-1 me-1"></i>
                                                    {{ $account->last_transaction_at->format('M d, Y') }}
                                                </span>
                                            @else
                                                <span class="fs-7 text-gray-400">{{ __('accounting.no_transactions') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('accounting.transaction-ledger', ['payment_method_id' => $account->id]) }}" 
                                               class="btn btn-sm btn-light btn-active-light-primary">
                                                <i class="ki-duotone ki-eye fs-2 me-1"></i>
                                                {{ __('accounting.view_transactions') }}
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-10">
                                            <div class="text-muted fs-6">{{ __('accounting.no_accounts_found') }}</div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Transactions -->
                @if($recentTransactions->count() > 0)
                <div class="card mt-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-clock fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.recent_transactions') }}
                        </h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-150px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.account') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.balance') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($recentTransactions as $transaction)
                                    <tr>
                                        <td>
                                            <span class="fs-7 text-gray-600">
                                                <i class="ki-duotone ki-calendar-8 fs-1 me-1"></i>
                                                {{ $transaction->transaction_date->format('M d, Y H:i') }}
                                            </span>
                                        </td>
                                        <td>{{ $transaction->paymentMethod->name ?? 'N/A' }}</td>
                                        <td>
                                            @php
                                                $typeBadgeColors = [
                                                    'DEPOSIT' => 'success',
                                                    'WITHDRAWAL' => 'danger',
                                                    'TRANSFER_IN' => 'info',
                                                    'TRANSFER_OUT' => 'warning',
                                                    'FEE' => 'secondary',
                                                    'REFUND' => 'primary',
                                                    'ADJUSTMENT' => 'dark'
                                                ];
                                            @endphp
                                            <span class="badge badge-light-{{ $typeBadgeColors[$transaction->transaction_type] ?? 'secondary' }} py-2 px-3">
                                                {{ $transaction->transaction_type }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-secondary py-2 px-3">
                                                {{ $transaction->transaction_category }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ in_array($transaction->transaction_type, ['DEPOSIT', 'TRANSFER_IN', 'REFUND']) ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($transaction->amount, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-7 text-gray-500">
                                                {{ number_format($transaction->balance_after, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $statusColors = [
                                                    'COMPLETED' => 'success',
                                                    'PENDING' => 'warning',
                                                    'FAILED' => 'danger',
                                                    'CANCELLED' => 'secondary',
                                                    'REVERSED' => 'info'
                                                ];
                                            @endphp
                                            <span class="badge badge-light-{{ $statusColors[$transaction->status] ?? 'secondary' }} py-2 px-3">
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
        // Location filter functionality
        function applyLocationFilter() {
            const locationId = document.getElementById('locationFilter').value;
            const currentUrl = new URL(window.location.href);
            
            if (locationId) {
                currentUrl.searchParams.set('location_id', locationId);
            } else {
                currentUrl.searchParams.delete('location_id');
            }
            
            window.location.href = currentUrl.toString();
        }
        
        // Search functionality
        document.getElementById('searchAccounts').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#accountsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Initialize Select2 for location filter
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('#locationFilter').select2({
                    placeholder: "{{ __('pagination.all_locations') }}",
                    allowClear: true,
                    width: '200px'
                });
            }
        });
    </script>
    @endpush
    
    @endsection
</x-app-layout>