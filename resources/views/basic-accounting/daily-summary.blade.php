<x-app-layout>
    @section('title', __('accounting.daily_summary'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                    {{ __('accounting.daily_summary') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted"><a href="{{ route('accounting.daily-summary') }}" class="text-muted text-hover-primary">{{ __('accounting.basic_accounting') }}</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.daily_summary') }}</li>
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
                    
                    <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto">
                        <input type="date" id="filterDate" class="form-control form-control-solid w-100 w-sm-180px" style="height: 42px;" value="{{ $date }}">
                    </div>
                    
                    <button type="button" class="btn btn-primary d-flex align-items-center justify-content-center gap-2" id="applyFiltersBtn" style="height: 42px; padding: 0 20px;">
                        <i class="ki-duotone ki-filter fs-3"></i><span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span><span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                <!-- Summary Cards -->
                <div class="row g-5 g-xl-8 mb-8">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-gray-800 text-break" id="sumTotalTx">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_transactions') }}</span></div></div>
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.date') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumDate">—</span></div></div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-gray-800 text-break" id="sumTotalAmount">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_amount') }}</span></div></div>
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.day_of_week') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumDayOfWeek">—</span></div></div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-success text-break" id="sumDeposits">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.deposits') }}</span></div></div>
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.money_in') }}</span><span class="fw-bold text-success fs-7" id="sumMoneyIn">—</span></div></div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-danger text-break" id="sumWithdrawals">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.withdrawals') }}</span></div></div>
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.money_out') }}</span><span class="fw-bold text-danger fs-7" id="sumMoneyOut">—</span></div></div>
                        </div>
                    </div>
                </div>
                
                <!-- Net Cash Flow Card -->
                <div class="row g-5 g-xl-8 mb-8">
                    <div class="col-sm-6 col-xl-3 offset-xl-9">
                        <div class="card card-flush h-md-100" id="netFlowCard">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-success text-break" id="sumNetFlow">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.net_cash_flow') }}</span></div></div>
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.daily_result') }}</span><span class="badge badge-light-success py-2 px-3" id="sumNetBadge">—</span></div></div>
                        </div>
                    </div>
                </div>
                
                <!-- By Type -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-tag fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.transactions_by_type') }}</h3></div>
                    <div class="card-body pt-0">
                        <div id="typeContainer" class="d-none table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('accounting.transaction_type') }}</th><th class="text-end">{{ __('accounting.count') }}</th><th class="text-end">{{ __('accounting.total_amount') }}</th><th class="text-end">{{ __('accounting.average_amount') }}</th><th class="min-w-150px">{{ __('accounting.percentage') }}</th><th class="text-center">{{ __('accounting.trend') }}</th></tr></thead>
                                <tbody id="typeBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
                        </div>
                        <div id="typeEmpty" class="d-none text-center py-10"><div class="text-muted fs-6">{{ __('accounting.no_transactions_found') }}</div></div>
                    </div>
                </div>
                
                <!-- By Category -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-category fs-2 me-2 text-success"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.transactions_by_category') }}</h3></div>
                    <div class="card-body pt-0">
                        <div id="catContainer" class="d-none table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('accounting.category') }}</th><th class="text-end">{{ __('accounting.count') }}</th><th class="text-end">{{ __('accounting.total_amount') }}</th><th class="min-w-150px">{{ __('accounting.distribution') }}</th><th class="text-end">{{ __('accounting.percentage') }}</th></tr></thead>
                                <tbody id="catBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
                        </div>
                        <div id="catEmpty" class="d-none text-center py-10"><div class="text-muted fs-6">{{ __('accounting.no_categories_found') }}</div></div>
                    </div>
                </div>
                
                <!-- Balance Changes -->
                <div class="card card-flush mb-8 d-none" id="balanceCard">
                    <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-wallet fs-2 me-2 text-info"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.balance_changes') }}</h3></div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('accounting.payment_method') }}</th><th class="text-end">{{ __('accounting.starting_balance') }}</th><th class="text-end">{{ __('accounting.ending_balance') }}</th><th class="text-end">{{ __('accounting.net_change') }}</th><th class="text-center">{{ __('accounting.change_type') }}</th><th class="text-end">{{ __('accounting.change_percentage') }}</th></tr></thead>
                                <tbody id="balanceBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Daily Transactions -->
                <div class="card card-flush">
                    <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-calendar-8 fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>{{ __('accounting.daily_transactions') }}</h3></div>
                    <div class="card-body pt-0">
                        <div id="txContainer" class="d-none table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('accounting.time') }}</th><th class="min-w-200px">{{ __('accounting.description') }}</th><th>{{ __('accounting.payment_method') }}</th><th>{{ __('accounting.type') }}</th><th>{{ __('accounting.category') }}</th><th class="text-end">{{ __('accounting.amount') }}</th><th class="text-end">{{ __('accounting.balance') }}</th><th class="text-center">{{ __('accounting.processed_by') }}</th><th class="text-center">{{ __('accounting.status') }}</th></tr></thead>
                                <tbody id="txBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
                            <div id="txPagination" class="mt-6 d-flex justify-content-center"></div>
                        </div>
                        <div id="txEmpty" class="d-none text-center py-10"><div class="text-muted fs-6">{{ __('accounting.no_transactions_for_this_day') }}</div></div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        window.DailySummary = (function () {
            'use strict';

            const URL_DATA = '{{ route("accounting.daily-summary.data") }}';
            const CURRENCY = '{{ currency_symbol() }}';
            let inFlight = false;
            let currentPage = 1;

            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('applyFiltersBtn').addEventListener('click', () => load(1));
                load(1);
            });

            async function load(page = 1) {
                if (inFlight) return;
                inFlight = true;
                currentPage = page;

                const params = new URLSearchParams({
                    location_id: document.getElementById('filterLocation').value,
                    user_id: document.getElementById('filterUser').value,
                    date: document.getElementById('filterDate').value,
                    page: page
                });

                try {
                    const res = await fetch(`${URL_DATA}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const json = await res.json();
                    
                    if (!json.success) throw new Error('Failed to load');
                    
                    renderSummary(json.summary);
                    renderByType(json.by_type, json.summary.total_transactions);
                    renderByCategory(json.by_category, json.summary.total_amount, json.summary.total_transactions);
                    renderBalanceChanges(json.balance_changes);
                    renderTransactions(json.transactions, json.pagination);

                } catch (err) {
                    console.error('[daily-summary] load failed:', err);
                    window.toastr?.error('Failed to load data.');
                } finally {
                    inFlight = false;
                }
            }

            function renderSummary(s) {
                const fmt = (n) => CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                document.getElementById('sumTotalTx').textContent = s.total_transactions;
                document.getElementById('sumDate').textContent = s.date;
                document.getElementById('sumTotalAmount').textContent = fmt(s.total_amount);
                document.getElementById('sumDayOfWeek').textContent = s.day_of_week;
                document.getElementById('sumDeposits').textContent = fmt(s.deposit_total);
                document.getElementById('sumMoneyIn').textContent = fmt(s.deposit_total);
                document.getElementById('sumWithdrawals').textContent = fmt(s.withdrawal_total);
                document.getElementById('sumMoneyOut').textContent = fmt(s.withdrawal_total);

                const netEl = document.getElementById('sumNetFlow');
                const badgeEl = document.getElementById('sumNetBadge');
                const cardEl = document.getElementById('netFlowCard');

                netEl.textContent = fmt(s.net_cash_flow);
                if (s.net_cash_flow >= 0) {
                    netEl.className = 'fs-2 fw-bold text-success text-break';
                    badgeEl.className = 'badge badge-light-success py-2 px-3';
                    badgeEl.textContent = '{{ __("accounting.positive") }}';
                    cardEl.className = 'card card-flush h-md-100';
                } else {
                    netEl.className = 'fs-2 fw-bold text-danger text-break';
                    badgeEl.className = 'badge badge-light-danger py-2 px-3';
                    badgeEl.textContent = '{{ __("accounting.negative") }}';
                    cardEl.className = 'card card-flush h-md-100';
                }
            }

            function renderByType(data, totalTx) {
                const container = document.getElementById('typeContainer');
                const empty = document.getElementById('typeEmpty');
                const tbody = document.getElementById('typeBody');

                if (!data || data.length === 0) {
                    container.classList.add('d-none');
                    empty.classList.remove('d-none');
                    return;
                }

                container.classList.remove('d-none');
                empty.classList.add('d-none');

                const colors = { 'DEPOSIT': 'success', 'WITHDRAWAL': 'danger', 'TRANSFER_IN': 'info', 'TRANSFER_OUT': 'warning', 'FEE': 'secondary', 'REFUND': 'primary', 'ADJUSTMENT': 'info', 'RECONCILIATION': 'dark' };

                tbody.innerHTML = data.map(item => {
                    const color = colors[item.type] || 'secondary';
                    const pct = totalTx > 0 ? (item.count / totalTx) * 100 : 0;
                    const isCredit = ['DEPOSIT', 'TRANSFER_IN', 'REFUND'].includes(item.type);
                    
                    let trendBadge = '';
                    if (item.count > 0) {
                        const tColor = item.average > 1000 ? 'danger' : (item.average > 500 ? 'warning' : 'success');
                        const tLabel = item.average > 1000 ? 'High' : (item.average > 500 ? 'Medium' : 'Low');
                        trendBadge = `<span class="badge badge-light-${tColor} py-2 px-3">${tLabel}</span>`;
                    }

                    return `
                        <tr>
                            <td><span class="badge badge-light-${color} py-2 px-3">${esc(item.type)}</span></td>
                            <td class="text-end"><span class="badge badge-light-secondary py-2 px-3">${item.count}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold ${isCredit ? 'text-success' : 'text-danger'}">${fmt(item.total)}</span></td>
                            <td class="text-end"><span class="fs-6 text-gray-700">${fmt(item.average)}</span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress h-8px w-100 me-3"><div class="progress-bar bg-${color}" style="width: ${pct}%"></div></div>
                                    <span class="fs-7 fw-bold">${pct.toFixed(1)}%</span>
                                </div>
                            </td>
                            <td class="text-center">${trendBadge}</td>
                        </tr>
                    `;
                }).join('');
            }

            function renderByCategory(data, totalAmount, totalTx) {
                const container = document.getElementById('catContainer');
                const empty = document.getElementById('catEmpty');
                const tbody = document.getElementById('catBody');

                if (!data || data.length === 0) {
                    container.classList.add('d-none');
                    empty.classList.remove('d-none');
                    return;
                }

                container.classList.remove('d-none');
                empty.classList.add('d-none');

                tbody.innerHTML = data.map(item => {
                    const amtPct = totalAmount > 0 ? (item.total / totalAmount) * 100 : 0;
                    const txPct = totalTx > 0 ? (item.count / totalTx) * 100 : 0;

                    return `
                        <tr>
                            <td><div class="d-flex align-items-center"><span class="bullet bullet-dot bg-success h-10px w-10px me-2"></span><span class="fs-6 fw-bold text-gray-800">${esc(item.category)}</span></div></td>
                            <td class="text-end"><span class="badge badge-light-secondary py-2 px-3">${item.count}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-gray-800">${fmt(item.total)}</span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress h-8px w-100 me-3"><div class="progress-bar bg-primary" style="width: ${amtPct}%"></div></div>
                                    <span class="fs-7 fw-bold">${amtPct.toFixed(1)}%</span>
                                </div>
                            </td>
                            <td class="text-end"><span class="fs-6 text-gray-700">${txPct.toFixed(1)}%</span></td>
                        </tr>
                    `;
                }).join('');
            }

            function renderBalanceChanges(data) {
                const card = document.getElementById('balanceCard');
                const tbody = document.getElementById('balanceBody');

                if (!data || data.length === 0) {
                    card.classList.add('d-none');
                    return;
                }

                card.classList.remove('d-none');
                tbody.innerHTML = data.map(change => {
                    const pct = change.starting_balance !== 0 ? (change.net_change / Math.abs(change.starting_balance)) * 100 : null;
                    const pctHtml = pct !== null ? `<span class="fs-6 fw-bold ${pct >= 0 ? 'text-success' : 'text-danger'}">${pct.toFixed(2)}%</span>` : '<span class="fs-7 text-gray-500">-</span>';
                    const badgeCls = change.net_change >= 0 ? 'success' : 'danger';
                    const badgeTxt = change.net_change >= 0 ? '{{ __("accounting.increase") }}' : '{{ __("accounting.decrease") }}';

                    return `
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-45px me-3"><span class="symbol-label bg-light-primary"><i class="ki-duotone ki-wallet fs-2x text-primary"><span class="path1"></span><span class="path2"></span></i></span></div>
                                    <div class="d-flex flex-column"><span class="fs-6 fw-bold text-gray-800">${esc(change.method_name)}</span><span class="fs-7 text-gray-500">${esc(change.method_type)}</span></div>
                                </div>
                            </td>
                            <td class="text-end"><span class="fs-6 text-gray-700">${fmt(change.starting_balance)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-gray-800">${fmt(change.ending_balance)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold ${change.net_change >= 0 ? 'text-success' : 'text-danger'}">${fmt(change.net_change)}</span></td>
                            <td class="text-center"><span class="badge badge-light-${badgeCls} py-2 px-3">${badgeTxt}</span></td>
                            <td class="text-end">${pctHtml}</td>
                        </tr>
                    `;
                }).join('');
            }

            function renderTransactions(data, pagination) {
                const container = document.getElementById('txContainer');
                const empty = document.getElementById('txEmpty');
                const tbody = document.getElementById('txBody');

                if (!data || data.length === 0) {
                    container.classList.add('d-none');
                    empty.classList.remove('d-none');
                    return;
                }

                container.classList.remove('d-none');
                empty.classList.add('d-none');

                const colors = { 'DEPOSIT': 'success', 'WITHDRAWAL': 'danger', 'TRANSFER_IN': 'info', 'TRANSFER_OUT': 'warning', 'FEE': 'secondary', 'REFUND': 'primary', 'ADJUSTMENT': 'info', 'RECONCILIATION': 'dark' };
                const statusColors = { 'COMPLETED': 'success', 'PENDING': 'warning', 'FAILED': 'danger', 'CANCELLED': 'secondary', 'REVERSED': 'dark' };

                tbody.innerHTML = data.map(tx => {
                    const color = colors[tx.type] || 'secondary';
                    const sColor = statusColors[tx.status] || 'secondary';
                    const isCredit = ['DEPOSIT', 'TRANSFER_IN', 'REFUND'].includes(tx.type);

                    return `
                        <tr>
                            <td><span class="fs-6 fw-bold text-gray-800">${esc(tx.time)}</span></td>
                            <td><span class="fs-6 text-gray-800" title="${esc(tx.description)}">${esc(tx.description || '—')}</span>${tx.notes ? `<span class="fs-7 text-gray-500 d-block">${esc(tx.notes)}</span>` : ''}</td>
                            <td><div class="d-flex flex-column"><span class="fs-6 fw-bold text-gray-800">${esc(tx.payment_method)}</span><span class="fs-7 text-gray-500">${esc(tx.payment_method_type)}</span></div></td>
                            <td><span class="badge badge-light-${color} py-2 px-3">${esc(tx.type)}</span></td>
                            <td><span class="badge badge-light-secondary py-2 px-3">${esc(tx.category)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold ${isCredit ? 'text-success' : 'text-danger'}">${fmt(tx.amount)}</span></td>
                            <td class="text-end"><span class="fs-6 text-gray-700">${fmt(tx.balance_after)}</span></td>
                            <td class="text-center"><span class="fw-bold text-gray-700 fs-7">${esc(tx.user)}</span></td>
                            <td class="text-center"><span class="badge badge-light-${sColor} py-2 px-3">${esc(tx.status)}</span></td>
                        </tr>
                    `;
                }).join('');

                renderPagination(pagination);
            }

            function renderPagination(p) {
                const container = document.getElementById('txPagination');
                if (p.last_page <= 1) { container.innerHTML = ''; return; }

                let html = '<ul class="pagination m-0">';
                html += `<li class="page-item ${p.current_page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${p.current_page - 1}">Prev</a></li>`;
                const start = Math.max(1, p.current_page - 2);
                const end = Math.min(p.last_page, p.current_page + 2);
                for (let i = start; i <= end; i++) {
                    html += `<li class="page-item ${i === p.current_page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                }
                html += `<li class="page-item ${p.current_page === p.last_page ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${p.current_page + 1}">Next</a></li>`;
                html += '</ul>';

                container.innerHTML = html;
                container.querySelectorAll('a[data-page]').forEach(a => {
                    a.addEventListener('click', (e) => {
                        e.preventDefault();
                        const pg = parseInt(e.target.dataset.page, 10);
                        if (pg && pg !== currentPage) load(pg);
                    });
                });
            }

            function esc(t) { const d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; }
            function fmt(n) { return CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

            return { load };
        })();
    </script>
    @endpush
    @endsection
</x-app-layout>