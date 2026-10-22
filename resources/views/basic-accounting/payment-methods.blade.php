<x-app-layout>
    @section('title', __('accounting.payment_methods'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    {{ __('accounting.payment_methods') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ url()->previous() }}" class="text-muted text-hover-primary">{{ __('auth._back') }}</a>
                    </li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.payment_methods') }}</li>
                </ul>
            </div>
            
            <div class="d-flex align-items-center gap-2 gap-lg-3 flex-wrap">
                <!-- Location Filter -->
                <div class="d-flex align-items-center bg-light-primary rounded-3 px-3 py-2">
                    <i class="ki-duotone ki-geolocation fs-2 text-primary me-2">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <select class="form-select form-select-sm form-select-solid border-0 bg-transparent ps-0 w-150px w-md-200px" 
                            id="locationFilter">
                        <option value="">{{ __('pagination.all_locations') }}</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ ($locationId ?? '') == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Export Dropdown (using existing logic) -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle d-flex align-items-center gap-2" 
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ki-duotone ki-file-down fs-2"></i>
                        <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" 
                            onclick="exportCurrentPage({tableId: 'accountsTable', filename: 'payment_methods', sheetName: 'Payment Methods', excludeColumns: [7]})">
                                <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                {{ __('accounting.export_to_excel') }}
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
                
                <!-- Summary Cards -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Total Balance Card -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column w-100">
                                    <!-- Added text-break, fs-2 instead of fs-2hx, and lh-1 -->
                                    <span class="fs-2 fw-bold text-gray-800 text-break lh-1 mb-1" id="sumTotalBalance">—</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_balance') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <!-- Also added text-break to the sub-balances just in case -->
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.available_balance') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700 text-break" id="sumAvailableBalance">—</span>
                                </div>
                                <div class="d-flex flex-stack mt-2">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.pending_balance') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700 text-break" id="sumPendingBalance">—</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Accounts Count Card -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-gray-800" id="sumTotalMethods">—</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_accounts') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <div class="d-flex align-items-center">
                                        <span class="bullet bullet-dot bg-success h-10px w-10px me-2"></span>
                                        <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.active_accounts') }}</span>
                                    </div>
                                    <span class="fs-6 fw-bold text-success" id="sumActiveMethods">—</span>
                                </div>
                                <div class="d-flex flex-stack mt-2">
                                    <div class="d-flex align-items-center">
                                        <span class="bullet bullet-dot bg-danger h-10px w-10px me-2"></span>
                                        <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.inactive_accounts') }}</span>
                                    </div>
                                    <span class="fs-6 fw-bold text-danger" id="sumInactiveMethods">—</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Table Card -->
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">{{ __('accounting.account_details') }}</h3>
                        <div class="card-toolbar">
                            <div class="d-flex align-items-center position-relative">
                                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-3">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                                <input type="text" id="searchInput" 
                                       class="form-control form-control-solid w-250px ps-10" 
                                       placeholder="{{ __('accounting.search_accounts') }}">
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body pt-0">
                        <!-- Skeleton Loader -->
                        <div id="loadingSpinner" class="d-none">
                            @for ($i = 0; $i < 5; $i++)
                                <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                                    <div class="skeleton skeleton-text" style="width:30px;height:30px;border-radius:50%;"></div>
                                    <div class="flex-grow-1">
                                        <div class="skeleton skeleton-text" style="width:200px;"></div>
                                        <div class="skeleton skeleton-text" style="width:120px;"></div>
                                    </div>
                                    <div class="skeleton skeleton-text" style="width:100px;"></div>
                                </div>
                            @endfor
                        </div>

                        <!-- Table -->
                        <div id="tableContainer" class="d-none table-responsive">
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
                                <tbody id="tableBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div id="emptyState" class="d-none text-center py-10">
                            <i class="ki-duotone ki-wallet fs-3x text-muted mb-3"></i>
                            <div class="text-muted fs-6">{{ __('accounting.no_accounts_found') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @push('styles')
    <style>
        .skeleton { display: inline-block; background: linear-gradient(90deg, #f1f1f4 0%, #e9e9ed 50%, #f1f1f4 100%); background-size: 200% 100%; animation: skeleton-shimmer 1.4s ease-in-out infinite; border-radius: 6px; }
        .skeleton-text { height: 12px; margin: 4px 0; display: block; }
        @keyframes skeleton-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    </style>
    @endpush

    @push('scripts')
    <script>
        window.PaymentMethodsList = (function () {
            'use strict';

            const URL_DATA = '{{ route("accounting.payment-methods.data") }}';
            const CURRENCY = '{{ currency_symbol() }}';

            const state = {
                search: '',
                locationId: @json($locationId ?? ''),
            };

            let searchDebounce;
            let inFlight = false;

            document.addEventListener('DOMContentLoaded', () => {
                bindFilters();
                load();
            });

            function bindFilters() {
                document.getElementById('searchInput')?.addEventListener('input', function () {
                    clearTimeout(searchDebounce);
                    searchDebounce = setTimeout(() => {
                        state.search = this.value.trim();
                        load();
                    }, 400);
                });

                document.getElementById('locationFilter')?.addEventListener('change', function () {
                    state.locationId = this.value;
                    load();
                });
            }

            async function load() {
                if (inFlight) return;
                inFlight = true;
                showState('loading');

                const params = new URLSearchParams();
                if (state.search) params.set('search', state.search);
                if (state.locationId) params.set('location_id', state.locationId);

                try {
                    const res = await fetch(`${URL_DATA}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const json = await res.json();
                    
                    if (!json.success) { showState('empty'); return; }

                    renderSummary(json.stats);
                    
                    if (!json.data || json.data.length === 0) { showState('empty'); return; }
                    
                    renderTable(json.data);
                    showState('table');
                } catch (err) {
                    console.error('[payment-methods] load failed:', err);
                    showState('empty');
                    window.toastr?.error('Failed to load records.');
                } finally {
                    inFlight = false;
                }
            }

            function renderSummary(stats) {
                const fmt = (n) => CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                document.getElementById('sumTotalBalance').textContent = fmt(stats.total_balance);
                document.getElementById('sumAvailableBalance').textContent = fmt(stats.total_available);
                document.getElementById('sumPendingBalance').textContent = fmt(stats.total_pending);
                
                document.getElementById('sumTotalMethods').textContent = stats.total_payment_methods;
                document.getElementById('sumActiveMethods').textContent = stats.active_methods;
                document.getElementById('sumInactiveMethods').textContent = stats.inactive_methods;
            }

            function renderTable(rows) {
                const tbody = document.getElementById('tableBody');
                
                // Icon mapping helper
                const getTypeIcon = (type) => {
                    switch (type) {
                        case 'bank_account': 
                            return { class: 'ki-bank', paths: '<span class="path1"></span><span class="path2"></span>' };
                        case 'card': 
                            return { class: 'ki-credit-cart', paths: '<span class="path1"></span><span class="path2"></span>' };
                        case 'digital_wallet': 
                            return { class: 'ki-wallet', paths: '<span class="path1"></span><span class="path2"></span>' };
                        case 'cash': 
                            return { class: 'ki-bill', paths: '<span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span>' };
                        case 'mobile_money': 
                            return { class: 'ki-phone', paths: '<span class="path1"></span><span class="path2"></span><span class="path3"></span>' };
                        case 'check': 
                            return { class: 'ki-note-2', paths: '<span class="path1"></span><span class="path2"></span>' };
                        default: 
                            return { class: 'ki-wallet', paths: '<span class="path1"></span><span class="path2"></span>' };
                    }
                };

                tbody.innerHTML = rows.map(row => {
                    const activeClass = row.is_active ? 'success' : 'danger';
                    const activeText = row.is_active ? '{{ __("accounting.active") }}' : '{{ __("accounting.inactive") }}';
                    const balanceClass = row.current_balance >= 0 ? 'text-success' : 'text-danger';
                    const lastTxn = row.last_transaction_at 
                        ? `<i class="ki-duotone ki-calendar-8 fs-1 me-1"><span class="path1"></span><span class="path2"></span></i>${row.last_transaction_at}` 
                        : `<span class="text-gray-400">{{ __("accounting.no_transactions") }}</span>`;

                    // Get the correct icon based on the type
                    // We use the original type passed from the controller (e.g. 'cash', 'bank_account')
                    // If your controller passes the formatted name (e.g. 'Cash'), you'll need to lowercase it here.
                    // Assuming your controller returns the raw type key in a property called 'raw_type' or similar.
                    // For this example, I'll extract it from the formatted type string to match our switch case.
                    const rawType = row.type.toLowerCase().replace(/ /g, '_'); 
                    const icon = getTypeIcon(rawType);

                    return `
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-45px me-3">
                                        <span class="symbol-label bg-light-${activeClass}">
                                            <i class="ki-duotone ${icon.class} fs-2x text-${activeClass}">
                                                ${icon.paths}
                                            </i>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fs-6 fw-bold text-gray-800">${esc(row.name)}</span>
                                        <span class="fs-7 text-gray-500">${esc(row.account_number)}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-light-${row.type_color} py-2 px-3">${esc(row.type)}</span>
                            </td>
                            <td class="text-end">
                                <span class="fs-6 fw-bold ${balanceClass}">${fmt(row.current_balance)}</span>
                            </td>
                            <td class="text-end">
                                <span class="fs-6 fw-bold text-gray-700">${fmt(row.available_balance)}</span>
                            </td>
                            <td class="text-end">
                                <span class="fs-6 fw-bold text-gray-500">${fmt(row.pending_balance)}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-light-${activeClass} py-2 px-3">${activeText}</span>
                            </td>
                            <td class="text-end">
                                <span class="fs-7 text-gray-500">${lastTxn}</span>
                            </td>
                            <td class="text-end">
                                <a href="${row.transactions_url}" class="btn btn-sm btn-light btn-active-light-primary">
                                    <i class="ki-duotone ki-eye fs-2 me-1">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                    </i>
                                    {{ __('accounting.view_transactions') }}
                                </a>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            function showState(which) {
                document.getElementById('loadingSpinner')?.classList.toggle('d-none', which !== 'loading');
                document.getElementById('tableContainer')?.classList.toggle('d-none', which !== 'table');
                document.getElementById('emptyState')?.classList.toggle('d-none', which !== 'empty');
            }

            function esc(t) {
                if (t === null || t === undefined) return '';
                const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML;
            }
            function fmt(n) {
                return CURRENCY + Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            return { load };
        })();
    </script>
    @endpush
    @endsection
</x-app-layout>