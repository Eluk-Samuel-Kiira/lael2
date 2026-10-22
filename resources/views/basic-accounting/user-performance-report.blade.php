<x-app-layout>
    @section('title', __('accounting.user_performance_report'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                    {{ __('accounting.user_performance_report') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted"><a href="{{ route('accounting.user-performance-report') }}" class="text-muted text-hover-primary">{{ __('accounting.basic_accounting') }}</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.user_performance_report') }}</li>
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
                    
                    <div class="d-flex align-items-center gap-2">
                        <input type="date" id="filterStartDate" class="form-control form-control-solid" style="min-width: 150px; height: 42px;" value="{{ $startDate }}">
                        <span class="text-gray-500 px-1">{{ __('accounting.to') }}</span>
                        <input type="date" id="filterEndDate" class="form-control form-control-solid" style="min-width: 150px; height: 42px;" value="{{ $endDate }}">
                    </div>
                    
                    <div class="d-flex align-items-center">
                        <label class="fw-semibold fs-7 text-gray-600 me-2">{{ __('accounting.show') }}</label>
                        <select id="filterLimit" class="form-select form-select-sm form-select-solid w-80px">
                            <option value="5" {{ $limit == 5 ? 'selected' : '' }}>5</option>
                            <option value="10" {{ $limit == 10 ? 'selected' : '' }}>10</option>
                            <option value="20" {{ $limit == 20 ? 'selected' : '' }}>20</option>
                            <option value="50" {{ $limit == 50 ? 'selected' : '' }}>50</option>
                        </select>
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
                
                <!-- Summary Stats -->
                <div class="row g-5 g-xl-8 mb-8">
                    <div class="col-sm-6 col-xl-2">
                        <div class="card card-flush h-md-100"><div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-gray-800 text-break" id="sumTotalUsers">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_users') }}</span></div></div></div>
                    </div>
                    <div class="col-sm-6 col-xl-2">
                        <div class="card card-flush h-md-100"><div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-info text-break" id="sumTotalTx">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_transactions') }}</span></div></div></div>
                    </div>
                    <div class="col-sm-6 col-xl-2">
                        <div class="card card-flush h-md-100"><div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-gray-800 text-break" id="sumVolume">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_volume') }}</span></div></div></div>
                    </div>
                    <div class="col-sm-6 col-xl-2">
                        <div class="card card-flush h-md-100"><div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-gray-800 text-break" id="sumAvgUser">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.avg_per_user') }}</span></div></div></div>
                    </div>
                    <div class="col-sm-6 col-xl-2">
                        <div class="card card-flush h-md-100"><div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-success text-break" id="sumProfit">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_profit') }}</span></div></div></div>
                    </div>
                    <div class="col-sm-6 col-xl-2">
                        <div class="card card-flush h-md-100"><div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-4 fw-bold text-primary text-truncate" id="sumBestUser" style="max-width: 120px;">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.best_performer') }}</span></div></div></div>
                    </div>
                </div>
                
                <!-- Top Profit Makers -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-arrow-up fs-2 me-2 text-success"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.top_profit_makers') }}<span class="badge badge-light-success ms-2 py-2 px-3" id="profitCountBadge">—</span></h3>
                    </div>
                    <div class="card-body pt-0">
                        <div id="profitContainer" class="d-none table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>#</th><th>{{ __('accounting.user') }}</th><th class="text-end">{{ __('accounting.transactions') }}</th><th class="text-end">{{ __('accounting.deposits') }}</th><th class="text-end">{{ __('accounting.withdrawals') }}</th><th class="text-end">{{ __('accounting.net_profit') }}</th><th class="text-end">{{ __('accounting.avg_transaction') }}</th><th class="text-end">{{ __('accounting.largest_txn') }}</th><th class="min-w-150px">{{ __('accounting.performance') }}</th></tr></thead>
                                <tbody id="profitBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
                        </div>
                        <div id="profitEmpty" class="d-none text-center py-10"><div class="text-muted fs-6">{{ __('accounting.no_profit_makers') }}</div></div>
                    </div>
                </div>
                
                <!-- Top Loss Makers -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-arrow-down fs-2 me-2 text-danger"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.top_loss_makers') }}<span class="badge badge-light-danger ms-2 py-2 px-3" id="lossCountBadge">—</span></h3>
                    </div>
                    <div class="card-body pt-0">
                        <div id="lossContainer" class="d-none table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>#</th><th>{{ __('accounting.user') }}</th><th class="text-end">{{ __('accounting.transactions') }}</th><th class="text-end">{{ __('accounting.deposits') }}</th><th class="text-end">{{ __('accounting.withdrawals') }}</th><th class="text-end">{{ __('accounting.net_loss') }}</th><th class="text-end">{{ __('accounting.avg_transaction') }}</th><th class="text-end">{{ __('accounting.largest_txn') }}</th><th class="min-w-150px">{{ __('accounting.performance') }}</th></tr></thead>
                                <tbody id="lossBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
                        </div>
                        <div id="lossEmpty" class="d-none text-center py-10"><div class="text-muted fs-6">{{ __('accounting.no_loss_makers') }}</div></div>
                    </div>
                </div>
                
                <!-- Recent Transactions -->
                <div class="card card-flush">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-clock fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.recent_transactions_top_users') }}</h3>
                        <div class="card-toolbar">
                            <div class="d-flex align-items-center position-relative">
                                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-3"><span class="path1"></span><span class="path2"></span></i>
                                <input type="text" id="searchTransactions" class="form-control form-control-solid w-250px ps-10" placeholder="{{ __('accounting.search_transactions') }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div id="txContainer" class="d-none table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="recentTransactionsTable">
                                <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('accounting.date') }}</th><th>{{ __('accounting.user') }}</th><th class="min-w-200px">{{ __('accounting.description') }}</th><th>{{ __('accounting.payment_method') }}</th><th>{{ __('accounting.type') }}</th><th>{{ __('accounting.category') }}</th><th class="text-end">{{ __('accounting.amount') }}</th><th class="text-center">{{ __('accounting.status') }}</th></tr></thead>
                                <tbody id="txBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
                            <div id="txPagination" class="mt-6 d-flex justify-content-between align-items-center flex-wrap gap-3"></div>
                        </div>
                        <div id="txEmpty" class="d-none text-center py-10"><div class="text-muted fs-6">{{ __('accounting.no_transactions_found') }}</div></div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        window.UserPerformance = (function () {
            'use strict';

            const URL_DATA = '{{ route("accounting.user-performance-report.data") }}';
            const CURRENCY = '{{ currency_symbol() }}';
            let inFlight = false;
            let currentPage = 1;

            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('applyFiltersBtn').addEventListener('click', () => load(1));
                document.getElementById('searchTransactions').addEventListener('input', filterTable);
                load(1);
            });

            async function load(page = 1) {
                if (inFlight) return;
                inFlight = true;
                currentPage = page;

                const params = new URLSearchParams({
                    location_id: document.getElementById('filterLocation').value,
                    start_date: document.getElementById('filterStartDate').value,
                    end_date: document.getElementById('filterEndDate').value,
                    limit: document.getElementById('filterLimit').value,
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
                    renderRankings(json.top_profit, json.top_loss);
                    renderTransactions(json.transactions, json.pagination);

                } catch (err) {
                    console.error('[user-perf] load failed:', err);
                    window.toastr?.error('Failed to load data.');
                } finally {
                    inFlight = false;
                }
            }

            function renderSummary(s) {
                const fmt = (n) => CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                document.getElementById('sumTotalUsers').textContent = s.total_users;
                document.getElementById('sumTotalTx').textContent = s.total_transactions;
                document.getElementById('sumVolume').textContent = fmt(s.total_volume);
                document.getElementById('sumAvgUser').textContent = fmt(s.average_per_user);
                document.getElementById('sumProfit').textContent = fmt(s.total_profit);
                
                if (s.best_user) {
                    document.getElementById('sumBestUser').textContent = s.best_user.user_name.substring(0, 12);
                } else {
                    document.getElementById('sumBestUser').textContent = '-';
                }
            }

            function renderRankings(profit, loss) {
                document.getElementById('profitCountBadge').textContent = `${profit.length} {{ __('accounting.users') }}`;
                document.getElementById('lossCountBadge').textContent = `${loss.length} {{ __('accounting.users') }}`;
                
                const profitContainer = document.getElementById('profitContainer');
                const profitEmpty = document.getElementById('profitEmpty');
                const profitBody = document.getElementById('profitBody');
                
                if (profit.length === 0) {
                    profitContainer.classList.add('d-none');
                    profitEmpty.classList.remove('d-none');
                } else {
                    profitContainer.classList.remove('d-none');
                    profitEmpty.classList.add('d-none');
                    
                    profitBody.innerHTML = profit.map((u, idx) => `
                        <tr>
                            <td><span class="badge badge-light-success py-2 px-3 fs-6">${idx + 1}</span></td>
                            <td><div class="d-flex flex-column"><span class="fs-6 fw-bold text-gray-800">${esc(u.user_name)}</span><span class="fs-7 text-gray-500">${esc(u.user_email)}</span></div></td>
                            <td class="text-end"><span class="badge badge-light-secondary py-2 px-3">${u.total_transactions}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-success">${fmt(u.total_deposits)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-danger">${fmt(u.total_withdrawals)}</span></td>
                            <td class="text-end"><span class="fs-5 fw-bold text-success">${fmt(u.net_amount)}</span></td>
                            <td class="text-end"><span class="fs-6 text-gray-700">${fmt(u.average_transaction)}</span></td>
                            <td class="text-end"><span class="fs-6 text-gray-700">${fmt(u.largest_transaction)}</span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress h-8px w-100 me-3"><div class="progress-bar bg-success" style="width: ${Math.min(u.performance_percent, 100)}%"></div></div>
                                    <span class="fs-7 fw-bold text-success">${u.performance_percent}%</span>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                }

                const lossContainer = document.getElementById('lossContainer');
                const lossEmpty = document.getElementById('lossEmpty');
                const lossBody = document.getElementById('lossBody');
                
                if (loss.length === 0) {
                    lossContainer.classList.add('d-none');
                    lossEmpty.classList.remove('d-none');
                } else {
                    lossContainer.classList.remove('d-none');
                    lossEmpty.classList.add('d-none');
                    
                    lossBody.innerHTML = loss.map((u, idx) => `
                        <tr>
                            <td><span class="badge badge-light-danger py-2 px-3 fs-6">${idx + 1}</span></td>
                            <td><div class="d-flex flex-column"><span class="fs-6 fw-bold text-gray-800">${esc(u.user_name)}</span><span class="fs-7 text-gray-500">${esc(u.user_email)}</span></div></td>
                            <td class="text-end"><span class="badge badge-light-secondary py-2 px-3">${u.total_transactions}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-success">${fmt(u.total_deposits)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-danger">${fmt(u.total_withdrawals)}</span></td>
                            <td class="text-end"><span class="fs-5 fw-bold text-danger">${fmt(Math.abs(u.net_amount))}</span></td>
                            <td class="text-end"><span class="fs-6 text-gray-700">${fmt(u.average_transaction)}</span></td>
                            <td class="text-end"><span class="fs-6 text-gray-700">${fmt(u.largest_transaction)}</span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress h-8px w-100 me-3"><div class="progress-bar bg-danger" style="width: ${Math.min(u.performance_percent, 100)}%"></div></div>
                                    <span class="fs-7 fw-bold text-danger">${u.performance_percent}%</span>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                }
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
                        <tr class="tx-row">
                            <td><span class="fs-6 fw-bold text-gray-800">${esc(tx.date)}</span><span class="fs-7 text-gray-500 d-block">${esc(tx.time)}</span></td>
                            <td><span class="fs-6 fw-bold text-gray-800">${esc(tx.user)}</span></td>
                            <td><span class="fs-6 text-gray-800" title="${esc(tx.description)}">${esc(tx.description || '—')}</span>${tx.notes ? `<span class="fs-7 text-gray-500 d-block">${esc(tx.notes)}</span>` : ''}</td>
                            <td><div class="d-flex flex-column"><span class="fs-6 fw-bold text-gray-800">${esc(tx.payment_method)}</span><span class="fs-7 text-gray-500">${esc(tx.payment_method_type)}</span></div></td>
                            <td><span class="badge badge-light-${color} py-2 px-3">${esc(tx.type)}</span></td>
                            <td><span class="badge badge-light-secondary py-2 px-3">${esc(tx.category)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold ${isCredit ? 'text-success' : 'text-danger'}">${fmt(tx.amount)}</span></td>
                            <td class="text-center"><span class="badge badge-light-${sColor} py-2 px-3">${esc(tx.status)}</span></td>
                        </tr>
                    `;
                }).join('');

                renderPagination(pagination);
            }

            function renderPagination(p) {
                const container = document.getElementById('txPagination');
                if (p.last_page <= 1) {
                    container.innerHTML = `<div class="text-gray-500 fs-7">Showing ${p.from} to ${p.to} of ${p.total} entries</div>`;
                    return;
                }

                let html = `<div class="text-gray-500 fs-7">Showing ${p.from} to ${p.to} of ${p.total} entries</div><ul class="pagination m-0">`;
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

            function filterTable() {
                const term = document.getElementById('searchTransactions').value.toLowerCase();
                document.querySelectorAll('.tx-row').forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
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