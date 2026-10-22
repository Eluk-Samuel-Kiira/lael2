<x-app-layout>
    @section('title', __('accounting.weekly_report'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                    {{ __('accounting.weekly_report') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted"><a href="{{ route('accounting.weekly-report') }}" class="text-muted text-hover-primary">{{ __('accounting.basic_accounting') }}</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.weekly_report') }}</li>
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
                    
                    <div class="d-flex flex-row gap-2">
                        <select id="filterWeek" class="form-select form-select-solid w-120px">
                            @for($i = 1; $i <= 53; $i++)
                                <option value="{{ $i }}" {{ (int)$week == $i ? 'selected' : '' }}>Week {{ $i }}</option>
                            @endfor
                        </select>
                        <select id="filterYear" class="form-select form-select-solid w-100px">
                            @for($i = date('Y') - 2; $i <= date('Y'); $i++)
                                <option value="{{ $i }}" {{ (int)$year == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
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
                
                <!-- Week Info -->
                <div class="alert alert-primary d-flex align-items-center p-5 mb-8" id="weekInfoBanner">
                    <i class="ki-duotone ki-calendar-8 fs-2x me-3 text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                    <div class="d-flex flex-column">
                        <h4 class="mb-1" id="weekRangeText">—</h4>
                        <span class="text-muted" id="weekNumberText">—</span>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="row g-5 g-xl-8 mb-8">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-gray-800 text-break" id="sumTxCount">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_transactions') }}</span></div></div>
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.week') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumWeekNum">—</span></div></div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-gray-800 text-break" id="sumAmount">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_amount') }}</span></div></div>
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.period') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumPeriod">—</span></div></div>
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
                
                <!-- Net Cash Flow -->
                <div class="row g-5 g-xl-8 mb-8">
                    <div class="col-sm-6 col-xl-3 offset-xl-9">
                        <div class="card card-flush h-md-100" id="netFlowCard">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-success text-break" id="sumNetFlow">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.net_cash_flow') }}</span></div></div>
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.weekly_result') }}</span><span class="badge badge-light-success py-2 px-3" id="sumNetBadge">—</span></div></div>
                        </div>
                    </div>
                </div>
                
                <!-- 6-Month Chart -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.six_month_comparison') }}</h3></div>
                    <div class="card-body"><div class="d-flex justify-content-center"><div class="w-100" style="max-width: 1000px; height: 400px;"><canvas id="sixMonthChart"></canvas></div></div></div>
                </div>
                
                <!-- Daily Breakdown -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-calendar-8 fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>{{ __('accounting.daily_breakdown') }}</h3></div>
                    <div class="card-body pt-0">
                        <div id="dailyContainer" class="d-none table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('accounting.date') }}</th><th class="text-end">{{ __('accounting.transactions') }}</th><th class="text-end">{{ __('accounting.deposits') }}</th><th class="text-end">{{ __('accounting.withdrawals') }}</th><th class="text-end">{{ __('accounting.daily_total') }}</th><th>{{ __('accounting.trend') }}</th></tr></thead>
                                <tbody id="dailyBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
                        </div>
                        <div id="dailyEmpty" class="d-none text-center py-10"><div class="text-muted fs-6">{{ __('accounting.no_transactions_for_this_week') }}</div></div>
                    </div>
                </div>
                
                <!-- Weekly Transactions -->
                <div class="card card-flush">
                    <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-table fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.weekly_transactions') }}</h3></div>
                    <div class="card-body pt-0">
                        <div id="txContainer" class="d-none table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('accounting.date') }}</th><th class="min-w-200px">{{ __('accounting.description') }}</th><th>{{ __('accounting.payment_method') }}</th><th>{{ __('accounting.type') }}</th><th>{{ __('accounting.category') }}</th><th class="text-end">{{ __('accounting.amount') }}</th><th class="text-center">{{ __('accounting.processed_by') }}</th><th class="text-center">{{ __('accounting.status') }}</th></tr></thead>
                                <tbody id="txBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
                            <div id="txPagination" class="mt-6 d-flex justify-content-between align-items-center flex-wrap gap-3"></div>
                        </div>
                        <div id="txEmpty" class="d-none text-center py-10"><div class="text-muted fs-6">{{ __('accounting.no_transactions_for_this_week') }}</div></div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.WeeklyReport = (function () {
            'use strict';

            const URL_DATA = '{{ route("accounting.weekly-report.data") }}';
            const CURRENCY = '{{ currency_symbol() }}';
            let inFlight = false;
            let currentPage = 1;
            let chartInstance = null;

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
                    week: document.getElementById('filterWeek').value,
                    year: document.getElementById('filterYear').value,
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
                    renderChart(json.six_months_data);
                    renderDaily(json.daily_breakdown);
                    renderTransactions(json.transactions, json.pagination);

                } catch (err) {
                    console.error('[weekly-report] load failed:', err);
                    window.toastr?.error('Failed to load data.');
                } finally {
                    inFlight = false;
                }
            }

            function renderSummary(s) {
                const fmt = (n) => CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                document.getElementById('weekRangeText').textContent = `${s.week_start} - ${s.week_end}`;
                document.getElementById('weekNumberText').textContent = `Week ${s.week_number}, ${s.year}`;
                
                document.getElementById('sumTxCount').textContent = s.total_transactions;
                document.getElementById('sumWeekNum').textContent = `Week ${s.week_number}`;
                document.getElementById('sumAmount').textContent = fmt(s.total_amount);
                document.getElementById('sumPeriod').textContent = `${s.week_start} - ${s.week_end}`;
                document.getElementById('sumDeposits').textContent = fmt(s.deposit_total);
                document.getElementById('sumMoneyIn').textContent = fmt(s.deposit_total);
                document.getElementById('sumWithdrawals').textContent = fmt(s.withdrawal_total);
                document.getElementById('sumMoneyOut').textContent = fmt(s.withdrawal_total);

                const netEl = document.getElementById('sumNetFlow');
                const badgeEl = document.getElementById('sumNetBadge');
                netEl.textContent = fmt(s.net_cash_flow);
                
                if (s.net_cash_flow >= 0) {
                    netEl.className = 'fs-2 fw-bold text-success text-break';
                    badgeEl.className = 'badge badge-light-success py-2 px-3';
                    badgeEl.textContent = '{{ __("accounting.profit") }}';
                } else {
                    netEl.className = 'fs-2 fw-bold text-danger text-break';
                    badgeEl.className = 'badge badge-light-danger py-2 px-3';
                    badgeEl.textContent = '{{ __("accounting.loss") }}';
                }
            }

            function renderChart(data) {
                const ctx = document.getElementById('sixMonthChart');
                if (!ctx) return;
                if (chartInstance) chartInstance.destroy();

                chartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.map(d => d.month),
                        datasets: [
                            { label: '{{ __("accounting.deposits") }}', data: data.map(d => d.deposits), backgroundColor: 'rgba(40, 199, 111, 0.8)', borderColor: 'rgb(40, 199, 111)', borderWidth: 2, borderRadius: 4, order: 2 },
                            { label: '{{ __("accounting.withdrawals") }}', data: data.map(d => d.withdrawals), backgroundColor: 'rgba(245, 101, 101, 0.8)', borderColor: 'rgb(245, 101, 101)', borderWidth: 2, borderRadius: 4, order: 3 },
                            {
                                label: '{{ __("accounting.net_profit_loss") }}', data: data.map(d => d.net), type: 'line',
                                backgroundColor: 'rgba(54, 153, 255, 0.1)', borderColor: 'rgb(54, 153, 255)', borderWidth: 4,
                                pointBackgroundColor: data.map(d => d.net >= 0 ? 'rgb(40, 199, 111)' : 'rgb(245, 101, 101)'),
                                pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 7, pointHoverRadius: 10, tension: 0.3, fill: true, order: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top', labels: { padding: 20, usePointStyle: true, pointStyle: 'circle', font: { size: 13, weight: 'bold' } } },
                            tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + CURRENCY + ctx.parsed.y.toFixed(2) } }
                        },
                        scales: {
                            y: { beginAtZero: true, ticks: { callback: (v) => CURRENCY + v.toFixed(0), font: { size: 12 } }, grid: { color: 'rgba(0,0,0,0.05)' } },
                            x: { grid: { display: false }, ticks: { font: { size: 13, weight: 'bold' } } }
                        },
                        animation: { duration: 1000, easing: 'easeInOutQuart' }
                    }
                });
            }

            function renderDaily(data) {
                const container = document.getElementById('dailyContainer');
                const empty = document.getElementById('dailyEmpty');
                const tbody = document.getElementById('dailyBody');

                if (!data || data.length === 0) {
                    container.classList.add('d-none');
                    empty.classList.remove('d-none');
                    return;
                }

                container.classList.remove('d-none');
                empty.classList.add('d-none');

                tbody.innerHTML = data.map(day => {
                    const net = day.deposits - day.withdrawals;
                    const netCls = net >= 0 ? 'text-success' : 'text-danger';
                    
                    let trendHtml = '<span class="fs-7 text-gray-500">{{ __("accounting.no_activity") }}</span>';
                    if (day.total > 0) {
                        const depPct = (day.deposits / day.total) * 100;
                        const wdPct = (day.withdrawals / day.total) * 100;
                        trendHtml = `
                            <div class="d-flex align-items-center">
                                <div class="progress h-8px w-100 me-3">
                                    <div class="progress-bar bg-success" style="width: ${depPct}%"></div>
                                    <div class="progress-bar bg-danger" style="width: ${wdPct}%"></div>
                                </div>
                                <span class="fs-7 fw-bold">${depPct.toFixed(0)}%</span>
                            </div>
                        `;
                    }

                    return `
                        <tr>
                            <td><span class="fs-6 fw-bold text-gray-800">${esc(day.date)}</span><span class="fs-7 text-gray-500 d-block">${esc(day.day_name)}</span></td>
                            <td class="text-end"><span class="badge badge-light-secondary py-2 px-3">${day.count}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-success">${fmt(day.deposits)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-danger">${fmt(day.withdrawals)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold ${netCls}">${fmt(net)}</span></td>
                            <td>${trendHtml}</td>
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
                            <td><span class="fs-6 fw-bold text-gray-800">${esc(tx.date)}</span><span class="fs-7 text-gray-500 d-block">${esc(tx.time)}</span></td>
                            <td><span class="fs-6 text-gray-800" title="${esc(tx.description)}">${esc(tx.description || '—')}</span>${tx.notes ? `<span class="fs-7 text-gray-500 d-block">${esc(tx.notes)}</span>` : ''}</td>
                            <td><div class="d-flex flex-column"><span class="fs-6 fw-bold text-gray-800">${esc(tx.payment_method)}</span><span class="fs-7 text-gray-500">${esc(tx.payment_method_type)}</span></div></td>
                            <td><span class="badge badge-light-${color} py-2 px-3">${esc(tx.type)}</span></td>
                            <td><span class="badge badge-light-secondary py-2 px-3">${esc(tx.category)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold ${isCredit ? 'text-success' : 'text-danger'}">${fmt(tx.amount)}</span></td>
                            <td class="text-center"><span class="fw-bold text-gray-700 fs-7">${esc(tx.user)}</span></td>
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

            function esc(t) { const d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; }
            function fmt(n) { return CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

            return { load };
        })();
    </script>
    @endpush
    @endsection
</x-app-layout>