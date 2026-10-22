<x-app-layout>
    @section('title', __('accounting.transaction_analysis'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{ __('accounting.transaction_analysis') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted"><a href="{{ route('accounting.transaction-analysis') }}" class="text-muted text-hover-primary">{{ __('accounting.basic_accounting') }}</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.transaction_analysis') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                <div class="d-flex flex-wrap align-items-center gap-3" id="filterForm">
                    <div class="d-flex align-items-center bg-light-primary rounded-3 px-4 py-2" style="min-width: 220px;">
                        <i class="ki-duotone ki-geolocation fs-2 text-primary me-3"><span class="path1"></span><span class="path2"></span></i>
                        <select class="form-select form-select-sm form-select-solid border-0 bg-transparent ps-0" id="filterLocation" style="min-width: 160px; width: 100%;">
                            <option value="">{{ __('pagination.all_locations') }}</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ ($locationId ?? '') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="d-flex align-items-center bg-light-info rounded-3 px-4 py-2" style="min-width: 220px;">
                        <i class="ki-duotone ki-user fs-2 text-info me-3"><span class="path1"></span><span class="path2"></span></i>
                        <select class="form-select form-select-sm form-select-solid border-0 bg-transparent ps-0" id="filterUser" style="min-width: 160px; width: 100%;">
                            <option value="">{{ __('accounting.all_users') }}</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ ($userId ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="d-flex align-items-center gap-2">
                        <input type="date" id="filterStartDate" class="form-control form-control-solid" style="min-width: 150px; height: 42px;" value="{{ $startDate }}">
                        <span class="text-gray-500 px-1">{{ __('accounting.to') }}</span>
                        <input type="date" id="filterEndDate" class="form-control form-control-solid" style="min-width: 150px; height: 42px;" value="{{ $endDate }}">
                    </div>
                    
                    <button type="button" class="btn btn-primary d-flex align-items-center justify-content-center gap-2" id="applyFiltersBtn" style="height: 42px; padding: 0 24px;">
                        <i class="ki-duotone ki-filter fs-3"></i><span>{{ __('accounting.apply_filters') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                <!-- Empty State -->
                <div id="globalEmpty" class="d-none alert alert-info d-flex align-items-center p-5">
                    <i class="ki-duotone ki-information-5 fs-2x me-3 text-info"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <div class="d-flex flex-column"><h4 class="mb-1">{{ __('accounting.no_data_found') }}</h4><span class="text-muted">{{ __('accounting.no_transactions_found_for_period') }}</span></div>
                </div>

                <div id="mainContent" class="d-none">
                    <!-- Summary Cards -->
                    <div class="row g-5 g-xl-8 mb-8">
                        <div class="col-sm-6 col-xl-3">
                            <div class="card card-flush h-md-100">
                                <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-gray-800 text-break" id="sumTotalTx">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_transactions') }}</span></div></div>
                                <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.period') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumPeriod">—</span></div></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="card card-flush h-md-100">
                                <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-success text-break" id="sumVolume">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_volume') }}</span></div></div>
                                <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.transaction_types') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumTypesCount">—</span></div></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="card card-flush h-md-100">
                                <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-info text-break" id="sumAvgTx">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.average_transaction') }}</span></div></div>
                                <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.per_transaction') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumAvgTx2">—</span></div></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="card card-flush h-md-100">
                                <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-warning text-break" id="sumAvgDaily">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.average_daily') }}</span></div></div>
                                <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.days') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumDays">—</span></div></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- By Type & Category -->
                    <div class="row g-5 g-xl-8 mb-8">
                        <div class="col-xl-6">
                            <div class="card card-flush h-md-100">
                                <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-tag fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.transaction_volume_by_type') }}</h3></div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                                            <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('accounting.transaction_type') }}</th><th class="text-end">{{ __('accounting.count') }}</th><th class="text-end">{{ __('accounting.total_amount') }}</th><th class="text-end">{{ __('accounting.average_amount') }}</th><th>{{ __('accounting.percentage') }}</th></tr></thead>
                                            <tbody id="byTypeBody" class="text-gray-600 fw-semibold"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card card-flush h-md-100">
                                <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-category fs-2 me-2 text-success"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.transaction_volume_by_category') }}</h3></div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                                            <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('accounting.category') }}</th><th class="text-end">{{ __('accounting.count') }}</th><th class="text-end">{{ __('accounting.total_amount') }}</th><th class="text-end">{{ __('accounting.average_amount') }}</th><th>{{ __('accounting.share') }}</th></tr></thead>
                                            <tbody id="byCategoryBody" class="text-gray-600 fw-semibold"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Daily Trends -->
                    <div class="card card-flush mb-8 d-none" id="trendsCard">
                        <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-chart-line fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.daily_transaction_trends') }}</h3></div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('accounting.date') }}</th><th class="text-end">{{ __('accounting.transaction_count') }}</th><th class="text-end">{{ __('accounting.daily_total') }}</th><th>{{ __('accounting.trend') }}</th><th class="text-end">{{ __('accounting.daily_average') }}</th></tr></thead>
                                    <tbody id="dailyTrendsBody" class="text-gray-600 fw-semibold"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Top Transactions -->
                    <div class="card card-flush">
                        <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-dollar fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.top_transactions') }}</h3></div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>#</th><th>{{ __('accounting.date') }}</th><th>{{ __('accounting.description') }}</th><th>{{ __('accounting.payment_method') }}</th><th>{{ __('accounting.type') }}</th><th>{{ __('accounting.category') }}</th><th class="text-end">{{ __('accounting.amount') }}</th><th class="text-end">{{ __('accounting.balance_after') }}</th></tr></thead>
                                    <tbody id="topTxBody" class="text-gray-600 fw-semibold"></tbody>
                                </table>
                            </div>
                            <div id="topTxPagination" class="mt-6 d-flex justify-content-center"></div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        window.TransactionAnalysis = (function () {
            'use strict';

            const URL_DATA = '{{ route("accounting.transaction-analysis.data") }}';
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
                    start_date: document.getElementById('filterStartDate').value,
                    end_date: document.getElementById('filterEndDate').value,
                    page: page
                });

                try {
                    const res = await fetch(`${URL_DATA}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const json = await res.json();
                    
                    if (!json.success) throw new Error('Failed to load');
                    
                    const hasData = json.by_type.length > 0 || json.by_category.length > 0;
                    
                    if (!hasData) {
                        document.getElementById('mainContent').classList.add('d-none');
                        document.getElementById('globalEmpty').classList.remove('d-none');
                    } else {
                        document.getElementById('globalEmpty').classList.add('d-none');
                        document.getElementById('mainContent').classList.remove('d-none');
                        
                        renderSummary(json.summary, json.by_type.length);
                        renderByType(json.by_type);
                        renderByCategory(json.by_category);
                        renderTrends(json.daily_trends);
                        renderTopTransactions(json.top_transactions, json.pagination);
                    }
                } catch (err) {
                    console.error('[transaction-analysis] load failed:', err);
                    window.toastr?.error('Failed to load data.');
                } finally {
                    inFlight = false;
                }
            }

            function renderSummary(s, typeCount) {
                const fmt = (n) => CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                document.getElementById('sumTotalTx').textContent = s.total_count;
                document.getElementById('sumVolume').textContent = fmt(s.total_volume);
                document.getElementById('sumPeriod').textContent = `${s.start_date} - ${s.end_date}`;
                document.getElementById('sumTypesCount').textContent = typeCount;
                
                document.getElementById('sumAvgTx').textContent = fmt(s.avg_transaction);
                document.getElementById('sumAvgTx2').textContent = fmt(s.avg_transaction);
                
                document.getElementById('sumAvgDaily').textContent = fmt(s.avg_daily);
                document.getElementById('sumDays').textContent = s.days_count;
            }

            function renderByType(data) {
                const tbody = document.getElementById('byTypeBody');
                const totalCount = data.reduce((sum, item) => sum + item.count, 0) || 1;
                
                const colors = { 'DEPOSIT': 'success', 'WITHDRAWAL': 'danger', 'TRANSFER_IN': 'info', 'TRANSFER_OUT': 'warning', 'FEE': 'secondary', 'REFUND': 'primary', 'ADJUSTMENT': 'info', 'RECONCILIATION': 'dark' };

                tbody.innerHTML = data.map(item => {
                    const color = colors[item.type] || 'secondary';
                    const pct = (item.count / totalCount) * 100;
                    
                    return `
                        <tr>
                            <td><span class="badge badge-light-${color} py-2 px-3">${esc(item.type)}</span></td>
                            <td class="text-end"><span class="badge badge-light-secondary py-2 px-3">${item.count}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-gray-800">${fmt(item.total)}</span></td>
                            <td class="text-end"><span class="fs-7">${item.count > 0 ? fmt(item.avg) : '-'}</span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress h-8px w-100 me-3"><div class="progress-bar bg-${color}" style="width: ${pct}%"></div></div>
                                    <span class="fs-7 fw-bold">${pct.toFixed(1)}%</span>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            function renderByCategory(data) {
                const tbody = document.getElementById('byCategoryBody');
                const totalAmount = data.reduce((sum, item) => sum + item.total, 0) || 1;

                tbody.innerHTML = data.map(item => {
                    const pct = (item.total / totalAmount) * 100;
                    return `
                        <tr>
                            <td><div class="d-flex align-items-center"><span class="bullet bullet-dot bg-success h-10px w-10px me-2"></span><span class="fs-6 fw-bold text-gray-800">${esc(item.category)}</span></div></td>
                            <td class="text-end"><span class="badge badge-light-secondary py-2 px-3">${item.count}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-gray-800">${fmt(item.total)}</span></td>
                            <td class="text-end"><span class="fs-7">${item.count > 0 ? fmt(item.avg) : '-'}</span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress h-8px w-100 me-3"><div class="progress-bar bg-success" style="width: ${pct}%"></div></div>
                                    <span class="fs-7 fw-bold">${pct.toFixed(1)}%</span>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            function renderTrends(data) {
                const card = document.getElementById('trendsCard');
                const tbody = document.getElementById('dailyTrendsBody');

                if (!data || data.length === 0) {
                    card.classList.add('d-none');
                    return;
                }

                card.classList.remove('d-none');
                let previousTotal = null;

                tbody.innerHTML = data.map(item => {
                    let trendHtml = '<span class="fs-7 text-gray-500">{{ __("accounting.first_day") }}</span>';
                    
                    if (previousTotal !== null) {
                        const diff = item.total - previousTotal;
                        const pct = previousTotal > 0 ? (diff / previousTotal) * 100 : 0;
                        const cls = diff >= 0 ? 'success' : 'danger';
                        const arrow = diff >= 0 ? '↑' : '↓';
                        trendHtml = `
                            <div class="d-flex align-items-center gap-2">
                                <span class="fs-7 fw-bold text-${cls}">${diff >= 0 ? '+' : ''}${fmt(diff)}</span>
                                <span class="badge badge-light-${cls} py-2 px-3">${arrow} ${Math.abs(pct).toFixed(1)}%</span>
                            </div>
                        `;
                    }
                    previousTotal = item.total;

                    return `
                        <tr>
                            <td><span class="fs-6 fw-bold text-gray-800">${esc(item.date)}</span><span class="fs-7 text-gray-500 d-block">${esc(item.day_name)}</span></td>
                            <td class="text-end"><span class="badge badge-light-secondary py-2 px-3">${item.count}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-gray-800">${fmt(item.total)}</span></td>
                            <td>${trendHtml}</td>
                            <td class="text-end"><span class="fs-7">${item.count > 0 ? fmt(item.total / item.count) : '-'}</span></td>
                        </tr>
                    `;
                }).join('');
            }

            function renderTopTransactions(data, pagination) {
                const tbody = document.getElementById('topTxBody');
                const colors = { 'DEPOSIT': 'success', 'WITHDRAWAL': 'danger', 'TRANSFER_IN': 'info', 'TRANSFER_OUT': 'warning', 'FEE': 'secondary', 'REFUND': 'primary', 'ADJUSTMENT': 'info', 'RECONCILIATION': 'dark' };
                
                const startIdx = (pagination.current_page - 1) * 15;

                tbody.innerHTML = data.map((tx, idx) => {
                    const color = colors[tx.type] || 'secondary';
                    const isCredit = ['DEPOSIT', 'TRANSFER_IN', 'REFUND'].includes(tx.type);
                    const amtClass = isCredit ? 'text-success' : 'text-danger';

                    return `
                        <tr>
                            <td><span class="badge badge-light-secondary py-2 px-3">${startIdx + idx + 1}</span></td>
                            <td><span class="fs-6 fw-bold text-gray-800">${esc(tx.date)}</span><span class="fs-7 text-gray-500 d-block">${esc(tx.time)}</span></td>
                            <td><span class="fs-6 text-gray-800">${esc(tx.description)}</span>${tx.notes ? `<span class="fs-7 text-gray-500 d-block">${esc(tx.notes)}</span>` : ''}</td>
                            <td><div class="d-flex flex-column"><span class="fs-6 fw-bold text-gray-800">${esc(tx.payment_method)}</span>${tx.user ? `<span class="fs-7 text-gray-500"><i class="ki-duotone ki-user fs-1 me-1"><span class="path1"></span><span class="path2"></span></i>${esc(tx.user)}</span>` : ''}</div></td>
                            <td><span class="badge badge-light-${color} py-2 px-3">${esc(tx.type)}</span></td>
                            <td><span class="badge badge-light-secondary py-2 px-3">${esc(tx.category)}</span></td>
                            <td class="text-end"><span class="fs-5 fw-bold ${amtClass}">${fmt(tx.amount)}</span></td>
                            <td class="text-end"><span class="fs-6 text-gray-700">${fmt(tx.balance_after)}</span></td>
                        </tr>
                    `;
                }).join('');

                renderPagination(pagination);
            }

            function renderPagination(p) {
                const container = document.getElementById('topTxPagination');
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