<x-app-layout>
    @section('title', __('accounting.payment_methods'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    {{ __('accounting.payment_methods') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.payment-methods.index') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.basic_accounting') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.payment_methods') }}</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3 flex-wrap">
            <!-- Location Filter -->
            <div class="d-flex align-items-center">
                <label class="fw-semibold fs-6 me-2 text-gray-700 d-none d-md-block">{{ __('pagination.location') }}:</label>
                <select class="form-select form-select-sm form-select-solid w-150px w-md-200px" name="location_id" data-control="select2" id="locationFilter" onchange="applyLocationFilter()">
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
                
                <!-- Summary Statistics Cards -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Total Methods -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $stats['total_payment_methods'] }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_methods') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <div class="d-flex align-items-center">
                                        <span class="bullet bullet-dot bg-success h-10px w-10px me-2"></span>
                                        <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.active') }}</span>
                                    </div>
                                    <span class="fw-bold text-gray-700">{{ $stats['active_methods'] }}</span>
                                </div>
                                <div class="d-flex flex-stack mt-2">
                                    <div class="d-flex align-items-center">
                                        <span class="bullet bullet-dot bg-danger h-10px w-10px me-2"></span>
                                        <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.inactive') }}</span>
                                    </div>
                                    <span class="fw-bold text-gray-700">{{ $stats['inactive_methods'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
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
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.average_balance') }}</span>
                                    <span class="fw-bold text-gray-700">{{ number_format($stats['average_balance'], 2) }} {{ currency_symbol() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Method Types Distribution -->
                    <div class="col-sm-12 col-xl-6">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <h3 class="card-title fw-bold text-gray-800">{{ __('accounting.method_types') }}</h3>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-wrap gap-4">
                                    @php
                                        $types = $paymentMethods->groupBy('type');
                                        $colors = ['bank_account' => 'info', 'card' => 'primary', 'cash' => 'warning', 'digital_wallet' => 'success', 'mobile_money' => 'danger', 'check' => 'secondary', 'other' => 'dark'];
                                    @endphp
                                    @foreach($types as $type => $methods)
                                        <div class="d-flex flex-column flex-grow-1 min-w-100px">
                                            <div class="d-flex align-items-center">
                                                <span class="bullet bullet-dot bg-{{ $colors[$type] ?? 'secondary' }} h-12px w-12px me-2"></span>
                                                <span class="fw-bold text-gray-800 fs-6">{{ ucfirst(str_replace('_', ' ', $type)) }}</span>
                                                <span class="badge badge-light-{{ $colors[$type] ?? 'secondary' }} ms-2">{{ $methods->count() }}</span>
                                            </div>
                                            <div class="progress h-6px mt-2">
                                                <div class="progress-bar bg-{{ $colors[$type] ?? 'secondary' }}" 
                                                     role="progressbar" 
                                                     style="width: {{ ($methods->count() / $stats['total_payment_methods']) * 100 }}%">
                                                </div>
                                            </div>
                                            <span class="text-gray-500 fs-7 mt-1">{{ number_format(($methods->count() / $stats['total_payment_methods']) * 100, 1) }}%</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Methods Table -->
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">{{ __('accounting.payment_methods_list') }}</h3>
                        <div class="card-toolbar">
                            <div class="d-flex align-items-center position-relative">
                                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <input type="text" id="searchMethods" 
                                       class="form-control form-control-solid w-250px ps-10" 
                                       placeholder="{{ __('accounting.search_methods') }}">
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="methodsTable">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-200px">{{ __('accounting.name') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.account_details') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.current_balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.available_balance') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.status') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.default') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.last_transaction') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @forelse($paymentMethods as $method)
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
                                                            @case('digital_wallet')
                                                                <i class="ki-duotone ki-wallet fs-2x text-{{ $method->is_active ? 'success' : 'danger' }}">
                                                                    <span class="path1"></span>
                                                                    <span class="path2"></span>
                                                                </i>
                                                                @break
                                                            @default
                                                                <i class="ki-duotone ki-abstract-26 fs-2x text-{{ $method->is_active ? 'success' : 'danger' }}">
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
                                        <td>
                                            @if($method->type === 'bank_account')
                                                <span class="fs-7 text-gray-600">
                                                    <i class="ki-duotone ki-bank fs-1 me-1"></i>
                                                    {{ $method->provider }} - ****{{ substr($method->account_number, -4) }}
                                                </span>
                                            @elseif($method->type === 'card')
                                                <span class="fs-7 text-gray-600">
                                                    <i class="ki-duotone ki-credit-cart fs-1 me-1"></i>
                                                    {{ ucfirst($method->card_type) }} ****{{ $method->card_last_four }}
                                                </span>
                                            @elseif($method->type === 'digital_wallet')
                                                <span class="fs-7 text-gray-600">
                                                    <i class="ki-duotone ki-wallet fs-1 me-1"></i>
                                                    {{ $method->wallet_email }}
                                                </span>
                                            @else
                                                <span class="fs-7 text-gray-500">{{ __('accounting.not_applicable') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ $method->current_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($method->current_balance, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-700">
                                                {{ number_format($method->available_balance, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $method->is_active ? 'success' : 'danger' }} py-2 px-3">
                                                {{ $method->is_active ? __('accounting.active') : __('accounting.inactive') }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($method->is_default)
                                                <span class="badge badge-light-primary py-2 px-3">
                                                    <i class="ki-duotone ki-star fs-1 me-1"></i>
                                                    {{ __('accounting.yes') }}
                                                </span>
                                            @else
                                                <span class="badge badge-light-secondary py-2 px-3">{{ __('accounting.no') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($method->last_transaction_at)
                                                <span class="fs-7 text-gray-500">
                                                    <i class="ki-duotone ki-calendar-8 fs-1 me-1"></i>
                                                    {{ $method->last_transaction_at->format('M d, Y') }}
                                                </span>
                                            @else
                                                <span class="fs-7 text-gray-400">{{ __('accounting.never') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('accounting.transaction-ledger', ['payment_method_id' => $method->id]) }}" 
                                               class="btn btn-sm btn-light btn-active-light-primary">
                                                <i class="ki-duotone ki-eye fs-2 me-1"></i>
                                                {{ __('accounting.view_transactions') }}
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-10">
                                            <div class="text-muted fs-6">{{ __('accounting.no_payment_methods_found') }}</div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Optional: Add pagination if needed -->
                    @if(isset($paymentMethods) && method_exists($paymentMethods, 'links'))
                    <div class="card-footer d-flex justify-content-end py-3">
                        {{ $paymentMethods->links() }}
                    </div>
                    @endif
                </div>
                
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
        document.getElementById('searchMethods').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#methodsTable tbody tr');
            
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