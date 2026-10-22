<x-app-layout>
    @section('title', __('accounting.transaction_ledger'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    {{ __('accounting.transaction_ledger') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.transaction-ledger') }}" class="text-muted text-hover-primary">{{ __('accounting.basic_accounting') }}</a>
                    </li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.transaction_ledger') }}</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                <!-- Filters Card (Single Card) -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-filter fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                            {{ __('accounting.filter_transactions') }}
                        </h3>
                    </div>
                    <div class="card-body pt-0">
                        <form id="filterForm" class="row g-5" onsubmit="event.preventDefault(); window.TransactionLedgerList.applyFilters();">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('accounting.start_date') }}</label>
                                <input type="date" name="start_date" id="filterStartDate" class="form-control form-control-solid" value="{{ $filters['start_date'] }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('accounting.end_date') }}</label>
                                <input type="date" name="end_date" id="filterEndDate" class="form-control form-control-solid" value="{{ $filters['end_date'] }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">{{ __('accounting.payment_method') }}</label>
                                <select name="payment_method_id" id="filterPaymentMethod" class="form-select form-select-solid">
                                    <option value="">{{ __('accounting.all_methods') }}</option>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method->id }}" {{ ($filters['payment_method_id'] ?? '') == $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">{{ __('accounting.status') }}</label>
                                <select name="status" id="filterStatus" class="form-select form-select-solid">
                                    <option value="">{{ __('accounting.all_statuses') }}</option>
                                    <option value="COMPLETED" {{ ($filters['status'] ?? '') == 'COMPLETED' ? 'selected' : '' }}>COMPLETED</option>
                                    <option value="PENDING" {{ ($filters['status'] ?? '') == 'PENDING' ? 'selected' : '' }}>PENDING</option>
                                    <option value="FAILED" {{ ($filters['status'] ?? '') == 'FAILED' ? 'selected' : '' }}>FAILED</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('accounting.apply_filters') }}
                                </button>
                            </div>
                            <!-- Hidden dropdowns for type, location, user to keep the card clean but functional -->
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('accounting.transaction_type') }}</label>
                                <select name="transaction_type" id="filterTransactionType" class="form-select form-select-solid">
                                    <option value="">{{ __('accounting.all_types') }}</option>
                                    @foreach($transactionTypes as $type)
                                        <option value="{{ $type }}" {{ ($filters['transaction_type'] ?? '') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('pagination.all_locations') }}</label>
                                <select name="location_id" id="filterLocation" class="form-select form-select-solid">
                                    <option value="">{{ __('pagination.all_locations') }}</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" {{ ($filters['location_id'] ?? '') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('accounting.processed_by') }}</label>
                                <select name="user_id" id="filterUser" class="form-select form-select-solid">
                                    <option value="">{{ __('accounting.all_users') }}</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}" {{ ($filters['user_id'] ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('auth._search') }}</label>
                                <input type="text" id="filterSearch" name="search" class="form-control form-control-solid" placeholder="Search ref, receipt, desc...">
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="row g-3 mb-8" id="summaryCards">
                    <!-- Total Tx -->
                    <div class="col-xl-3 col-sm-6">
                        <div class="card card-flush h-100">
                            <div class="card-header pt-5"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-gray-800 text-break" id="sumTotalTx">—</span><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.total_transactions') }}</span></div></div>
                            <div class="card-body pt-0"><span class="text-gray-500 fs-7"><span class="text-success fw-bold" id="sumInCount">—</span> In / <span class="text-danger fw-bold" id="sumOutCount">—</span> Out</span></div>
                        </div>
                    </div>
                    <!-- Inflow -->
                    <div class="col-xl-3 col-sm-6">
                        <div class="card card-flush h-100 bg-light-success">
                            <div class="card-header pt-5"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-success text-break" id="sumInflow">—</span><span class="text-gray-600 fw-semibold fs-7">{{ __('accounting.total_inflow') }}</span></div></div>
                        </div>
                    </div>
                    <!-- Outflow -->
                    <div class="col-xl-3 col-sm-6">
                        <div class="card card-flush h-100 bg-light-danger">
                            <div class="card-header pt-5"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-danger text-break" id="sumOutflow">—</span><span class="text-gray-600 fw-semibold fs-7">{{ __('accounting.total_outflow') }}</span></div></div>
                        </div>
                    </div>
                    <!-- Net Change -->
                    <div class="col-xl-3 col-sm-6">
                        <div class="card card-flush h-100 bg-light-primary" id="sumNetCard">
                            <div class="card-header pt-5"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-primary text-break" id="sumNetChange">—</span><span class="text-gray-600 fw-semibold fs-7">{{ __('accounting.net_change') }}</span></div></div>
                        </div>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="card card-flush">
                    <div class="card-body pt-0">
                        <!-- Skeleton -->
                        <div id="txLoading" class="d-none pt-5">
                            @for ($i = 0; $i < 5; $i++)
                                <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                                    <div class="skeleton skeleton-text" style="width:80px;"></div>
                                    <div class="skeleton skeleton-text" style="width:150px;"></div>
                                    <div class="flex-grow-1"><div class="skeleton skeleton-text" style="width:200px;"></div></div>
                                    <div class="skeleton skeleton-text" style="width:80px;"></div>
                                </div>
                            @endfor
                        </div>

                        <!-- Table -->
                        <div id="txContainer" class="d-none table-responsive pt-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="transactionsTable">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-100px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.transaction_ref') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.description') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.balance_after') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.status') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="txBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
                            <div id="txPagination" class="d-flex justify-content-between align-items-center mt-5"></div>
                        </div>

                        <!-- Empty -->
                        <div id="txEmpty" class="d-none text-center py-10">
                            <div class="text-muted fs-6">{{ __('accounting.no_transactions_found') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @include('basic-accounting.partials.transaction-modal')

    @push('styles')
    <style>
        .skeleton { display: inline-block; background: linear-gradient(90deg, #f1f1f4 0%, #e9e9ed 50%, #f1f1f4 100%); background-size: 200% 100%; animation: skeleton-shimmer 1.4s ease-in-out infinite; border-radius: 6px; }
        .skeleton-text { height: 12px; margin: 4px 0; display: block; }
        @keyframes skeleton-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .text-purple { color: #7239ea !important; }
        .bg-light-purple { background-color: rgba(114, 57, 234, 0.10) !important; }
        .badge-light-purple { background-color: rgba(114, 57, 234, 0.10) !important; color: #7239ea !important; }
    </style>
    @endpush

    @push('scripts')
    <script>
        window.TransactionLedgerList = (function () {
            'use strict';

            const URL_DATA = '{{ route("accounting.transaction-ledger.data") }}';
            const CURRENCY = '{{ currency_symbol() }}';
            let inFlight = false;
            let currentPage = 1;

            document.addEventListener('DOMContentLoaded', () => {
                load(1);
            });

            function applyFilters() {
                load(1);
            }

            function load(page = 1) {
                if (inFlight) return;
                inFlight = true;
                currentPage = page;
                showState('loading');

                const form = document.getElementById('filterForm');
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);
                params.set('page', page);

                fetch(`${URL_DATA}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                })
                .then(res => {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(json => {
                    if (!json.success) { showState('empty'); return; }
                    renderSummary(json.summary);
                    
                    if (!json.data || json.data.length === 0) { showState('empty'); return; }
                    
                    renderTable(json.data);
                    renderPagination(json.pagination);
                    showState('table');
                })
                .catch(err => {
                    console.error('[ledger] load failed:', err);
                    showState('empty');
                    window.toastr?.error('Failed to load records.');
                })
                .finally(() => { inFlight = false; });
            }

            function renderSummary(s) {
                document.getElementById('sumTotalTx').textContent = s.total_count;
                document.getElementById('sumInflow').textContent = '+' + fmt(s.inflow_total);
                document.getElementById('sumOutflow').textContent = '-' + fmt(s.outflow_total);
                
                const netEl = document.getElementById('sumNetChange');
                const netCard = document.getElementById('sumNetCard');
                const isPositive = s.net_change >= 0;
                
                netEl.textContent = (isPositive ? '+' : '-') + fmt(Math.abs(s.net_change));
                netEl.className = `fs-2 fw-bold text-break ${isPositive ? 'text-primary' : 'text-warning'}`;
                netCard.className = `card card-flush h-100 ${isPositive ? 'bg-light-primary' : 'bg-light-warning'}`;
                
                document.getElementById('sumInCount').textContent = s.inflow_count;
                document.getElementById('sumOutCount').textContent = s.outflow_count;
            }

            function renderTable(rows) {
                const tbody = document.getElementById('txBody');
                
                const typeColors = { 'DEPOSIT': 'success', 'WITHDRAWAL': 'danger', 'TRANSFER_IN': 'info', 'TRANSFER_OUT': 'warning', 'FEE': 'secondary', 'REFUND': 'primary', 'ADJUSTMENT': 'purple', 'RECONCILIATION': 'dark' };
                const amountClasses = { 'DEPOSIT': 'text-success', 'TRANSFER_IN': 'text-success', 'REFUND': 'text-success', 'WITHDRAWAL': 'text-danger', 'TRANSFER_OUT': 'text-danger', 'FEE': 'text-danger', 'ADJUSTMENT': 'text-purple', 'RECONCILIATION': 'text-dark' };
                const statusColors = { 'COMPLETED': 'success', 'PENDING': 'warning', 'FAILED': 'danger', 'CANCELLED': 'secondary', 'REVERSED': 'dark' };

                tbody.innerHTML = rows.map(row => {
                    const tColor = typeColors[row.type] || 'secondary';
                    const aClass = amountClasses[row.type] || 'text-dark';
                    const sColor = statusColors[row.status] || 'secondary';

                    return `
                        <tr>
                            <td>
                                <span class="fs-7 fw-bold text-gray-800">${esc(row.date)}</span>
                                <span class="fs-8 text-gray-500 d-block">${esc(row.time)}</span>
                            </td>
                            <td>
                                <span class="fs-7 text-gray-600 fw-bold">${esc(row.transaction_ref)}</span>
                                ${row.receipt_number ? `<span class="fs-8 text-gray-500 d-block"><i class="ki-duotone ki-receipt fs-1 me-1"><span class="path1"></span><span class="path2"></span></i>${esc(row.receipt_number)}</span>` : ''}
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fs-7 text-gray-800 fw-bold">${esc(row.payment_method_name)}</span>
                                    ${row.user_name ? `<span class="fs-8 text-gray-500"><i class="ki-duotone ki-user fs-1 me-1"><span class="path1"></span><span class="path2"></span></i>${esc(row.user_name)}</span>` : ''}
                                </div>
                            </td>
                            <td><span class="badge badge-light-${tColor} py-2 px-3">${esc(row.type)}</span></td>
                            <td>
                                <span class="fs-7 text-gray-800">${esc(row.description)}</span>
                                ${row.notes ? `<span class="fs-8 text-gray-500 d-block"><i class="ki-duotone ki-information fs-1 me-1"><span class="path1"></span><span class="path2"></span></i>${esc(row.notes)}</span>` : ''}
                            </td>
                            <td class="text-end"><span class="fs-6 fw-bold ${aClass}">${fmt(row.amount)}</span></td>
                            <td class="text-end"><span class="fs-7 text-gray-800 fw-bold">${fmt(row.balance_after)}</span></td>
                            <td class="text-center"><span class="badge badge-light-${sColor} py-2 px-3">${esc(row.status)}</span></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-light btn-active-light-primary" onclick="window.TransactionLedgerList.openDetails(${row.id})">
                                    <i class="ki-duotone ki-eye fs-2 me-1"><span class="path1"></span><span class="path2"></span></i> View
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            function renderPagination(p) {
                const container = document.getElementById('txPagination');
                if (p.last_page <= 1) { container.innerHTML = ''; return; }

                let html = `<div class="text-muted fs-7">Showing ${p.from} to ${p.to} of ${p.total} entries</div><ul class="pagination m-0">`;
                
                // Prev
                html += `<li class="page-item ${p.current_page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${p.current_page - 1}">Prev</a></li>`;
                
                // Pages (simplified)
                const start = Math.max(1, p.current_page - 2);
                const end = Math.min(p.last_page, p.current_page + 2);
                for (let i = start; i <= end; i++) {
                    html += `<li class="page-item ${i === p.current_page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                }
                
                // Next
                html += `<li class="page-item ${p.current_page === p.last_page ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${p.current_page + 1}">Next</a></li>`;
                html += `</ul>`;

                container.innerHTML = html;
                container.querySelectorAll('a[data-page]').forEach(a => {
                    a.addEventListener('click', (e) => {
                        e.preventDefault();
                        const pg = parseInt(e.target.dataset.page, 10);
                        if (pg && pg !== currentPage) load(pg);
                    });
                });
            }

            function showState(which) {
                document.getElementById('txLoading')?.classList.toggle('d-none', which !== 'loading');
                document.getElementById('txContainer')?.classList.toggle('d-none', which !== 'table');
                document.getElementById('txEmpty')?.classList.toggle('d-none', which !== 'empty');
            }

            // Details Modal
            async function openDetails(id) {
                const modalEl = document.getElementById('transactionModal');
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
                document.getElementById('modalTransactionRef').textContent = 'Loading...';
                
                try {
                    const res = await fetch(`/accounting/transaction-ledger/details/${id}`);
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    populateModal(data);
                } catch (err) {
                    document.getElementById('modalTransactionRef').textContent = 'Error';
                    console.error('[ledger] details failed:', err);
                }
            }

            function populateModal(data) {
                const tx = data.transaction;
                const cust = data.customer;
                const pm = data.payment_method;

                document.getElementById('modalTransactionRef').textContent = tx.transaction_ref || '-';
                document.getElementById('modalReceiptNumber').textContent = tx.receipt_number || '-';
                document.getElementById('modalDate').textContent = tx.transaction_date ? new Date(tx.transaction_date).toLocaleString() : '-';
                
                const isCredit = ['DEPOSIT', 'TRANSFER_IN', 'REFUND'].includes(tx.transaction_type);
                const isPurple = ['ADJUSTMENT', 'RECONCILIATION'].includes(tx.transaction_type);
                
                let amtClass = isCredit ? 'text-success' : (isPurple ? 'text-purple' : 'text-danger');
                let bgClass = isCredit ? 'bg-light-success' : (isPurple ? 'bg-light-purple' : 'bg-light-danger');
                let sign = isCredit ? '+' : (isPurple ? '' : '-');

                document.getElementById('modalAmount').innerHTML = `<span class="${amtClass}">${sign}${fmt(tx.amount)}</span>`;
                document.getElementById('modalAmountCard').className = `card card-flush h-100 ${bgClass}`;
                
                document.getElementById('modalBalanceBefore').textContent = fmt(tx.balance_before);
                document.getElementById('modalBalanceAfter').textContent = fmt(tx.balance_after);
                document.getElementById('modalPaymentMethod').textContent = pm?.name || '-';
                document.getElementById('modalCategory').textContent = tx.transaction_category || '-';
                document.getElementById('modalDescription').textContent = tx.description || '-';
                document.getElementById('modalProcessedBy').textContent = tx.user?.name || 'System';
                document.getElementById('modalCustomer').textContent = cust ? `${cust.first_name} ${cust.last_name}`.trim() : '-';

                document.getElementById('modalReferenceTable').textContent = tx.reference_table || '-';
                document.getElementById('modalReferenceId').textContent = tx.reference_id || '-';
                
                document.getElementById('rawMetadata').textContent = JSON.stringify(tx.metadata || {}, null, 2);
                
                // Format metadata table
                const tbody = document.getElementById('formattedMetadataTable').getElementsByTagName('tbody')[0];
                tbody.innerHTML = '';
                if (tx.metadata && typeof tx.metadata === 'object') {
                    for (const [k, v] of Object.entries(tx.metadata)) {
                        const tr = tbody.insertRow();
                        tr.insertCell().innerHTML = `<strong>${k}</strong>`;
                        tr.insertCell().textContent = typeof v === 'object' ? JSON.stringify(v) : v;
                    }
                }
            }

            function esc(t) { const d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; }
            function fmt(n) { return CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

            return { applyFilters, openDetails };
        })();
    </script>
    @endpush
    @endsection
</x-app-layout>