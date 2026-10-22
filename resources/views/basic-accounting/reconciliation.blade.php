<x-app-layout>
    @section('title', __('accounting.reconciliation'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                    {{ __('accounting.reconciliation') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted"><a href="{{ route('accounting.reconciliation') }}" class="text-muted text-hover-primary">{{ __('accounting.basic_accounting') }}</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.reconciliation') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-lg-auto">
                <div class="d-flex flex-wrap align-items-center gap-3" id="filterForm">
                    <div class="d-flex align-items-center bg-light-primary rounded-3 px-4 py-2" style="min-width: 200px;">
                        <i class="ki-duotone ki-geolocation fs-2 text-primary me-3"><span class="path1"></span><span class="path2"></span></i>
                        <select class="form-select form-select-sm form-select-solid border-0 bg-transparent ps-0" id="filterLocation" style="min-width: 150px; width: 100%;">
                            <option value="">{{ __('pagination.all_locations') }}</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ ($locationId ?? '') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="d-flex align-items-center bg-light-info rounded-3 px-4 py-2" style="min-width: 200px;">
                        <i class="ki-duotone ki-user fs-2 text-info me-3"><span class="path1"></span><span class="path2"></span></i>
                        <select class="form-select form-select-sm form-select-solid border-0 bg-transparent ps-0" id="filterUser" style="min-width: 150px; width: 100%;">
                            <option value="">{{ __('accounting.all_users') }}</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ ($userId ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="w-100 w-sm-auto">
                        <input type="date" id="filterDate" class="form-control form-control-solid w-100 w-sm-180px" style="height: 42px;" value="{{ $date }}">
                    </div>
                    
                    <button type="button" class="btn btn-primary d-flex align-items-center justify-content-center gap-2" id="applyFiltersBtn" style="height: 42px; padding: 0 20px;">
                        <i class="ki-duotone ki-filter fs-3"></i><span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span><span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                    </button>
                    
                    <button type="button" class="btn btn-sm btn-success flex-grow-1 flex-sm-grow-0" id="reconcileAllBtn">
                        <i class="ki-duotone ki-check fs-2 me-1 me-sm-2"></i><span class="d-none d-sm-inline">{{ __('accounting.reconcile_all') }}</span><span class="d-inline d-sm-none">{{ __('accounting.reconcile') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                <!-- Summary Stats -->
                <div class="row g-5 g-xl-8 mb-8">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-success text-break" id="sumReconciled">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.reconciled_methods') }}</span></div></div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.of') }} <span id="sumTotalMethods">—</span> {{ __('accounting.total') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumReconciledPct">—</span></div>
                                <div class="d-flex flex-stack mt-2"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.unreconciled') }}</span><span class="fw-bold text-danger fs-7" id="sumUnreconciled">—</span></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-success text-break" id="sumDiscrepancy">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_discrepancy') }}</span></div></div>
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.date') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumDate">—</span></div></div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-info text-break" id="sumTotalTx">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_transactions') }}</span></div></div>
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.completed') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumCompleted">—</span></div></div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-gray-800 text-break" id="sumActive">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_methods') }}</span></div></div>
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.active') }}</span><span class="fw-bold text-success fs-7" id="sumActive2">—</span></div></div>
                        </div>
                    </div>
                </div>
                
                <!-- Reconciliation Table -->
                <div class="card card-flush">
                    <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-check-circle fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.account_reconciliation') }}</h3></div>
                    <div class="card-body pt-0">
                        <div id="methodLoading" class="d-none"><div class="d-flex align-items-center gap-4 py-4 border-bottom"><div class="skeleton skeleton-text" style="width:30px;height:30px;border-radius:50%;"></div><div class="flex-grow-1"><div class="skeleton skeleton-text" style="width:200px;"></div></div></div></div>
                        
                        <div id="methodContainer" class="d-none table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th>{{ __('accounting.payment_method') }}</th>
                                        <th class="text-end">{{ __('accounting.starting_balance') }}</th>
                                        <th class="text-end">{{ __('accounting.net_change') }}</th>
                                        <th class="text-end">{{ __('accounting.expected_balance') }}</th>
                                        <th class="text-end">{{ __('accounting.current_balance') }}</th>
                                        <th class="text-end">{{ __('accounting.discrepancy') }}</th>
                                        <th class="text-end">{{ __('accounting.transactions') }}</th>
                                        <th class="text-center">{{ __('accounting.status') }}</th>
                                        <th class="text-center">{{ __('accounting.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="methodBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
                        </div>
                        
                        <div id="methodEmpty" class="d-none text-center py-10"><div class="text-muted fs-6">{{ __('accounting.no_payment_methods_found') }}</div></div>
                    </div>
                </div>
                
                <!-- Unreconciled Transactions -->
                <div class="card card-flush mt-8 d-none" id="unreconciledCard">
                    <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-flag fs-2 me-2 text-warning"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.unreconciled_transactions') }} <span class="badge badge-light-warning ms-2 py-2 px-3" id="unreconciledBadge">—</span></h3></div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th>{{ __('accounting.date') }}</th>
                                        <th class="min-w-200px">{{ __('accounting.description') }}</th>
                                        <th>{{ __('accounting.payment_method') }}</th>
                                        <th>{{ __('accounting.type') }}</th>
                                        <th>{{ __('accounting.category') }}</th>
                                        <th class="text-end">{{ __('accounting.amount') }}</th>
                                        <th class="text-center">{{ __('accounting.processed_by') }}</th>
                                        <th class="text-center">{{ __('accounting.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="unreconciledBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
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
        window.Reconciliation = (function () {
            'use strict';

            const URL_DATA = '{{ route("accounting.reconciliation.data") }}';
            const CURRENCY = '{{ currency_symbol() }}';
            let inFlight = false;

            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('applyFiltersBtn').addEventListener('click', load);
                document.getElementById('reconcileAllBtn').addEventListener('click', handleReconcileAll);
                load();
            });

            async function load() {
                if (inFlight) return;
                inFlight = true;
                showState('loading');

                const params = new URLSearchParams({
                    location_id: document.getElementById('filterLocation').value,
                    user_id: document.getElementById('filterUser').value,
                    date: document.getElementById('filterDate').value,
                });

                try {
                    const res = await fetch(`${URL_DATA}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const json = await res.json();
                    
                    if (!json.success) throw new Error('Failed to load');
                    
                    renderSummary(json.summary);
                    renderMethods(json.methods);
                    renderUnreconciled(json.unreconciled);
                    showState('table');

                } catch (err) {
                    console.error('[reconciliation] load failed:', err);
                    window.toastr?.error('Failed to load data.');
                    showState('empty');
                } finally {
                    inFlight = false;
                }
            }

            function renderSummary(s) {
                const fmt = (n) => CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                document.getElementById('sumReconciled').textContent = s.reconciled_methods;
                document.getElementById('sumTotalMethods').textContent = s.total_methods;
                document.getElementById('sumReconciledPct').textContent = s.total_methods > 0 ? ((s.reconciled_methods / s.total_methods) * 100).toFixed(1) + '%' : '0%';
                document.getElementById('sumUnreconciled').textContent = s.unreconciled_methods;
                
                document.getElementById('sumDiscrepancy').textContent = fmt(s.total_discrepancy);
                document.getElementById('sumDiscrepancy').className = s.total_discrepancy === 0 ? 'fs-2 fw-bold text-success text-break' : 'fs-2 fw-bold text-danger text-break';
                document.getElementById('sumDate').textContent = s.date;
                
                document.getElementById('sumTotalTx').textContent = s.total_transactions;
                document.getElementById('sumCompleted').textContent = s.total_transactions;
                
                document.getElementById('sumActive').textContent = s.total_methods;
                document.getElementById('sumActive2').textContent = s.total_methods;
            }

            function renderMethods(methods) {
                const container = document.getElementById('methodContainer');
                const empty = document.getElementById('methodEmpty');
                const tbody = document.getElementById('methodBody');

                if (!methods || methods.length === 0) {
                    container.classList.add('d-none');
                    empty.classList.remove('d-none');
                    return;
                }

                container.classList.remove('d-none');
                empty.classList.add('d-none');

                const typeIcons = { 'cash': 'ki-dollar', 'bank_account': 'ki-bank', 'card': 'ki-credit-cart', 'mobile_money': 'ki-phone' };

                tbody.innerHTML = methods.map(m => {
                    const activeClass = m.is_reconciled ? 'success' : 'danger';
                    const icon = typeIcons[m.type] || 'ki-wallet';
                    const netCls = m.net_change >= 0 ? 'text-success' : 'text-danger';

                    return `
                        <tr class="method-row">
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-45px me-3"><span class="symbol-label bg-light-${activeClass}"><i class="ki-duotone ${icon} fs-2x text-${activeClass}"><span class="path1"></span><span class="path2"></span></i></span></div>
                                    <div class="d-flex flex-column"><span class="fs-6 fw-bold text-gray-800">${esc(m.name)}</span><span class="fs-7 text-gray-500">${esc(m.type_label)}</span></div>
                                </div>
                            </td>
                            <td class="text-end"><span class="fs-6 text-gray-700">${fmt(m.starting_balance)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold ${netCls}">${m.net_change >= 0 ? '+' : ''}${fmt(m.net_change)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-gray-800">${fmt(m.expected_balance)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-gray-800">${fmt(m.current_balance)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold ${m.is_reconciled ? 'text-success' : 'text-danger'}">${fmt(m.discrepancy)}</span></td>
                            <td class="text-end"><span class="badge badge-light-secondary py-2 px-3">${m.transaction_count}</span></td>
                            <td class="text-center">
                                ${m.is_reconciled 
                                    ? `<span class="badge badge-light-success py-2 px-3"><i class="ki-duotone ki-check-circle fs-1 me-1"><span class="path1"></span><span class="path2"></span></i> {{ __("accounting.reconciled") }}</span>`
                                    : `<span class="badge badge-light-danger py-2 px-3"><i class="ki-duotone ki-close-circle fs-1 me-1"><span class="path1"></span><span class="path2"></span></i> {{ __("accounting.unreconciled") }}</span>`
                                }
                            </td>
                            <td class="text-center">
                                ${!m.is_reconciled 
                                    ? `<button class="btn btn-sm btn-success" onclick="window.Reconciliation.handleReconcileMethod(${m.id})"><i class="ki-duotone ki-check fs-2"><span class="path1"></span><span class="path2"></span></i> {{ __("accounting.reconcile") }}</button>`
                                    : `<button class="btn btn-sm btn-light btn-active-light-primary" onclick="window.Reconciliation.handleViewMethod(${m.id})"><i class="ki-duotone ki-eye fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i> {{ __("accounting.view") }}</button>`
                                }
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            function renderUnreconciled(data) {
                const card = document.getElementById('unreconciledCard');
                const tbody = document.getElementById('unreconciledBody');
                const badge = document.getElementById('unreconciledBadge');

                if (!data || data.length === 0) {
                    card.classList.add('d-none');
                    return;
                }

                card.classList.remove('d-none');
                badge.textContent = data.length;

                const colors = { 'DEPOSIT': 'success', 'WITHDRAWAL': 'danger', 'TRANSFER_IN': 'info', 'TRANSFER_OUT': 'warning', 'FEE': 'secondary', 'REFUND': 'primary', 'ADJUSTMENT': 'info', 'RECONCILIATION': 'dark' };

                tbody.innerHTML = data.map(tx => {
                    const color = colors[tx.type] || 'secondary';
                    const isCredit = ['DEPOSIT', 'TRANSFER_IN', 'REFUND'].includes(tx.type);

                    return `
                        <tr>
                            <td><span class="fs-6 fw-bold text-gray-800">${esc(tx.date)}</span><span class="fs-7 text-gray-500 d-block">${esc(tx.time)}</span></td>
                            <td><span class="fs-6 text-gray-800" title="${esc(tx.description)}">${esc(tx.description || '—')}</span>${tx.notes ? `<span class="fs-7 text-gray-500 d-block">${esc(tx.notes)}</span>` : ''}</td>
                            <td><div class="d-flex flex-column"><span class="fs-6 fw-bold text-gray-800">${esc(tx.payment_method)}</span><span class="fs-7 text-gray-500">${esc(tx.payment_method_type)}</span></div></td>
                            <td><span class="badge badge-light-${color} py-2 px-3">${esc(tx.type)}</span></td>
                            <td><span class="badge badge-light-secondary py-2 px-3">${esc(tx.category)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold ${isCredit ? 'text-success' : 'text-danger'}">${fmt(tx.amount)}</span></td>
                            <td class="text-center"><span class="fw-bold text-gray-700 fs-7">${esc(tx.user)}</span></td>
                            <td class="text-center"><button class="btn btn-sm btn-primary" onclick="window.Reconciliation.handleCompleteTransaction(${tx.id})"><i class="ki-duotone ki-check-circle fs-2"><span class="path1"></span><span class="path2"></span></i> {{ __("accounting.complete") }}</button></td>
                        </tr>
                    `;
                }).join('');
            }

            // ── Actions ─────────────────────────────────────────────
            function handleReconcileAll() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '{{ __("accounting.reconcile_all") }}',
                        text: '{{ __("reconciliation.confirm_reconcile_all") }}',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#50cd89',
                        cancelButtonColor: '#f1416c',
                        confirmButtonText: 'Yes, Reconcile All',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.toastr?.info('{{ __("reconciliation.reconcile_all_message") }}');
                            // Implement API call here
                        }
                    });
                } else {
                    if (confirm('{{ __("reconciliation.confirm_reconcile_all") }}')) {
                        window.toastr?.info('{{ __("reconciliation.reconcile_all_message") }}');
                    }
                }
            }

            function handleReconcileMethod(methodId) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '{{ __("accounting.reconcile") }}',
                        text: '{{ __("reconciliation.confirm_reconcile_method") }}',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#50cd89',
                        cancelButtonColor: '#f1416c',
                        confirmButtonText: 'Yes, Reconcile',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.toastr?.info('{{ __("reconciliation.reconcile_method_message") }}');
                            // Implement API call here
                        }
                    });
                } else {
                    if (confirm('{{ __("reconciliation.confirm_reconcile_method") }}')) {
                        window.toastr?.info('{{ __("reconciliation.reconcile_method_message") }}');
                    }
                }
            }

            function handleViewMethod(methodId) {
                window.toastr?.info('{{ __("reconciliation.view_details_message") }}');
                // Implement view details here
            }

            function handleCompleteTransaction(txId) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '{{ __("accounting.complete") }}',
                        text: '{{ __("reconciliation.confirm_complete_transaction") }}',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#009ef7',
                        cancelButtonColor: '#f1416c',
                        confirmButtonText: 'Yes, Complete',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.toastr?.info('{{ __("reconciliation.complete_transaction_message") }}');
                            // Implement API call here
                        }
                    });
                } else {
                    if (confirm('{{ __("reconciliation.confirm_complete_transaction") }}')) {
                        window.toastr?.info('{{ __("reconciliation.complete_transaction_message") }}');
                    }
                }
            }

            function showState(which) {
                document.getElementById('methodLoading')?.classList.toggle('d-none', which !== 'loading');
                document.getElementById('methodContainer')?.classList.toggle('d-none', which !== 'table');
                document.getElementById('methodEmpty')?.classList.toggle('d-none', which !== 'empty');
            }

            function esc(t) { const d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; }
            function fmt(n) { return CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

            return { load, handleReconcileAll, handleReconcileMethod, handleViewMethod, handleCompleteTransaction };
        })();
    </script>
    @endpush
    @endsection
</x-app-layout>