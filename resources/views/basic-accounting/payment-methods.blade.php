<x-app-layout>
    @section('title', __('accounting.payment_methods'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('accounting.payment_methods')}}</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.payment-methods.index') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.basic_accounting') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('accounting.payment_methods')}}</li>
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
                            onclick="exportCurrentPage({tableId: 'methodsTable', filename: 'payment_methods', sheetName: 'Payment Methods', excludeColumns: [8]})">
                                <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                {{ __('accounting.export_to_excel') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" 
                            onclick="exportCurrentPage({tableId: 'methodsTable', filename: 'payment_methods', format: 'csv', excludeColumns: [8]})">
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
                
                <!-- Summary Stats -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Total Methods -->
                    <div class="col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.total_methods') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1">{{ $stats['total_payment_methods'] }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.payment_methods') }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.active') }}</span>
                                    <span class="fs-6 fw-bold text-success">{{ $stats['active_methods'] }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.inactive') }}</span>
                                    <span class="fs-6 fw-bold text-danger">{{ $stats['inactive_methods'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Balance -->
                    <div class="col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.total_balance') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1">${{ number_format($stats['total_balance'], 2) }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.overall_balance') }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.average_balance') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">${{ number_format($stats['average_balance'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Method Types -->
                    <div class="col-xl-6">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.method_types') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-wrap">
                                    @php
                                        $types = $paymentMethods->groupBy('type');
                                    @endphp
                                    @foreach($types as $type => $methods)
                                    <div class="d-flex flex-column me-7 mb-5">
                                        <span class="fs-4 fw-bold text-gray-800">{{ ucfirst($type) }}</span>
                                        <span class="fs-6 fw-semibold text-gray-500">{{ $methods->count() }} {{ __('accounting.methods') }}</span>
                                        <div class="d-flex align-items-center mt-2">
                                            <div class="progress h-6px w-100 me-3">
                                                <div class="progress-bar bg-primary" role="progressbar" 
                                                     style="width: {{ ($methods->count() / $stats['total_payment_methods']) * 100 }}%">
                                                </div>
                                            </div>
                                            <span class="fs-7 fw-bold">{{ number_format(($methods->count() / $stats['total_payment_methods']) * 100, 1) }}%</span>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Methods Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.payment_methods_list') }}</h3>
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
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="methodsTable">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-200px">{{ __('accounting.name') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.account_details') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.current_balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.available_balance') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.status') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.default') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.last_transaction') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentMethods as $method)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-45px me-3">
                                                    <span class="symbol-label bg-light-{{ $method->is_active ? 'success' : 'danger' }}">
                                                        @switch($method->type)
                                                            @case('cash')
                                                                <i class="ki-duotone ki-dollar fs-2x text-{{ $method->is_active ? 'success' : 'danger' }}">
                                                                    <span class="path1"></span>
                                                                    <span class="path2"></span>
                                                                </i>
                                                                @break
                                                            @case('bank_account')
                                                                <i class="ki-duotone ki-bank fs-2x text-{{ $method->is_active ? 'success' : 'danger' }}">
                                                                    <span class="path1"></span>
                                                                    <span class="path2"></span>
                                                                </i>
                                                                @break
                                                            @case('card')
                                                                <i class="ki-duotone ki-credit-cart fs-2x text-{{ $method->is_active ? 'success' : 'danger' }}">
                                                                    <span class="path1"></span>
                                                                    <span class="path2"></span>
                                                                </i>
                                                                @break
                                                            @default
                                                                <i class="ki-duotone ki-wallet fs-2x text-{{ $method->is_active ? 'success' : 'danger' }}">
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
                                            <span class="badge badge-light-{{ $method->type === 'cash' ? 'warning' : ($method->type === 'bank_account' ? 'info' : 'primary') }}">
                                                {{ $method->getTypeLabel() }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($method->type === 'bank_account')
                                                <span class="fs-7 text-gray-600">{{ $method->provider }} - ****{{ substr($method->account_number, -4) }}</span>
                                            @elseif($method->type === 'card')
                                                <span class="fs-7 text-gray-600">{{ $method->card_type }} ****{{ $method->card_last_four }}</span>
                                            @elseif($method->type === 'digital_wallet')
                                                <span class="fs-7 text-gray-600">{{ $method->wallet_email }}</span>
                                            @else
                                                <span class="fs-7 text-gray-500">{{ __('accounting.not_applicable') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ $method->current_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $method->currency->symbol ?? '$' }}{{ number_format($method->current_balance, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-700">
                                                {{ $method->currency->symbol ?? '$' }}{{ number_format($method->available_balance, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $method->is_active ? 'success' : 'danger' }}">
                                                {{ $method->is_active ? __('accounting.active') : __('accounting.inactive') }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($method->is_default)
                                                <span class="badge badge-light-primary">{{ __('accounting.yes') }}</span>
                                            @else
                                                <span class="badge badge-light-secondary">{{ __('accounting.no') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($method->last_transaction_at)
                                                <span class="fs-7 text-gray-500">{{ $method->last_transaction_at->format('M d, Y') }}</span>
                                            @else
                                                <span class="fs-7 text-gray-400">{{ __('accounting.never') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('accounting.transaction-ledger', ['payment_method_id' => $method->id]) }}" 
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
                
            </div>
        </div>
    </div>
    
    @push('scripts')
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
    </script>
    @endpush
    
    @endsection
</x-app-layout>