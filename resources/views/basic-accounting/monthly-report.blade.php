<x-app-layout>
    @section('title', __('accounting.monthly_report'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                    {{ __('accounting.monthly_report') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted"><a href="{{ route('accounting.monthly-report') }}" class="text-muted text-hover-primary">{{ __('accounting.basic_accounting') }}</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.monthly_report') }}</li>
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
                        <select id="filterMonth" class="form-select form-select-solid w-140px">
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ (int)$month == $i ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $i, 10)) }}</option>
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
                
                <!-- Summary Cards -->
                <div class="row g-5 g-xl-8 mb-8">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-gray-800 text-break" id="sumTxCount">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_transactions') }}</span></div></div>
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.month') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumMonth">—</span></div></div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-gray-800 text-break" id="sumAmount">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_amount') }}</span></div></div>
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.days_in_month') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumDays">—</span></div></div>
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
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.monthly_result') }}</span><span class="badge badge-light-success py-2 px-3" id="sumNetBadge">—</span></div></div>
                        </div>
                    </div>
                </div>
                
                <div id="noDataAlert" class="alert alert-info d-flex align-items-center p-5 mb-8 d-none">
                    <i class="ki-duotone ki-information fs-2x me-3 text-info"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <div class="d-flex flex-column"><h4 class="mb-1">{{ __('accounting.no_transactions_found') }}</h4><span class="text-muted" id="noDataText"></span></div>
                </div>
                
                <div id="dataContainer" class="d-none">
                    <!-- Daily Breakdown -->
                    <div class="card card-flush mb-8">
                        <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-calendar-8 fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>{{ __('accounting.daily_breakdown') }}</h3></div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('accounting.date') }}</th><th class="text-end">{{ __('accounting.transactions') }}</th><th class="text-end">{{ __('accounting.deposits') }}</th><th class="text-end">{{ __('accounting.withdrawals') }}</th><th class="text-end">{{ __('accounting.daily_total') }}</th><th>{{ __('accounting.trend') }}</th><th class="text-end">{{ __('accounting.daily_average') }}</th></tr></thead>
                                    <tbody id="dailyBody" class="text-gray-600 fw-semibold"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Category Breakdown -->
                    <div class="card card-flush mb-8">
                        <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-category fs-2 me-2 text-success"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.category_breakdown') }}</h3></div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('accounting.category') }}</th><th class="text-end">{{ __('accounting.count') }}</th><th class="text-end">{{ __('accounting.total_amount') }}</th><th class="text-end">{{ __('accounting.average_amount') }}</th><th class="min-w-150px">{{ __('accounting.distribution') }}</th><th class="text-end">{{ __('accounting.percentage') }}</th></tr></thead>
                                    <tbody id="catBody" class="text-gray-600 fw-semibold"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Method Breakdown -->
                    <div class="card card-flush">
                        <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-wallet fs-2 me-2 text-info"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.payment_method_breakdown') }}</h3></div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('accounting.payment_method') }}</th><th class="text-end">{{ __('accounting.transaction_count') }}</th><th class="text-end">{{ __('accounting.total_amount') }}</th><th class="text-end">{{ __('accounting.average_transaction') }}</th><th class="min-w-150px">{{ __('accounting.usage_distribution') }}</th></tr></thead>
                                    <tbody id="methodBody" class="text-gray-600 fw-semibold"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        window.MonthlyReport = (function () {
            'use strict';

            const URL_DATA = '{{ route("accounting.monthly-report.data") }}';
            const CURRENCY = '{{ currency_symbol() }}';
            let inFlight = false;

            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('applyFiltersBtn').addEventListener('click', load);
                load();
            });

            async function load() {
                if (inFlight) return;
                inFlight = true;

                const params = new URLSearchParams({
                    location_id: document.getElementById('filterLocation').value,
                    user_id: document.getElementById('filterUser').value,
                    month: document.getElementById('filterMonth').value,
                    year: document.getElementById('filterYear').value,
                });

                try {
                    const res = await fetch(`${URL_DATA}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const json = await res.json();
                    
                    if (!json.success) throw new Error('Failed to load');
                    
                    renderSummary(json.summary);
                    
                    if (json.summary.total_transactions === 0) {
                        document.getElementById('dataContainer').classList.add('d-none');
                        document.getElementById('noDataAlert').classList.remove('d-none');
                        document.getElementById('noDataText').textContent = `No transactions for ${json.summary.month_name} ${json.summary.year}`;
                        return;
                    }

                    document.getElementById('noDataAlert').classList.add('d-none');
                    document.getElementById('dataContainer').classList.remove('d-none');
                    
                    renderDaily(json.daily_breakdown);
                    renderCategories(json.category_breakdown, json.summary.total_amount, json.summary.total_transactions);
                    renderMethods(json.method_breakdown, json.summary.total_amount);

                } catch (err) {
                    console.error('[monthly-report] load failed:', err);
                    window.toastr?.error('Failed to load data.');
                } finally {
                    inFlight = false;
                }
            }

            function renderSummary(s) {
                const fmt = (n) => CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                document.getElementById('sumTxCount').textContent = s.total_transactions;
                document.getElementById('sumMonth').textContent = `${s.month_name} ${s.year}`;
                document.getElementById('sumAmount').textContent = fmt(s.total_amount);
                document.getElementById('sumDays').textContent = s.days_in_month;
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
                    badgeEl.textContent = '{{ __("accounting.profit") }}';
                } else {
                    netEl.className = 'fs-2 fw-bold text-danger text-break';
                    badgeEl.className = 'badge badge-light-danger py-2 px-3';
                    badgeEl.textContent = '{{ __("accounting.loss") }}';
                }
            }

            function renderDaily(data) {
                const tbody = document.getElementById('dailyBody');
                if (!data || data.length === 0) {
                    tbody.innerHTML = '';
                    return;
                }

                tbody.innerHTML = data.map(day => {
                    const net = day.deposits - day.withdrawals;
                    const netCls = net >= 0 ? 'text-success' : 'text-danger';
                    
                    let trendHtml = '<span class="fs-7 text-gray-500">{{ __("accounting.no_activity") }}</span>';
                    if (day.total > 0) {
                        const depPct = day.deposits > 0 ? (day.deposits / day.total) * 100 : 0;
                        const wdPct = day.withdrawals > 0 ? (day.withdrawals / day.total) * 100 : 0;
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
                            <td class="text-end">${day.count > 0 ? fmt(day.avg) : '-'}</td>
                        </tr>
                    `;
                }).join('');
            }

            function renderCategories(data, totalAmount, totalTx) {
                const tbody = document.getElementById('catBody');
                if (!data || data.length === 0) {
                    tbody.innerHTML = '';
                    return;
                }

                tbody.innerHTML = data.map(cat => {
                    const amtPct = totalAmount > 0 ? (cat.total / totalAmount) * 100 : 0;
                    const txPct = totalTx > 0 ? (cat.count / totalTx) * 100 : 0;

                    return `
                        <tr>
                            <td><div class="d-flex align-items-center"><span class="bullet bullet-dot bg-success h-10px w-10px me-2"></span><span class="fs-6 fw-bold text-gray-800">${esc(cat.category)}</span></div></td>
                            <td class="text-end"><span class="badge badge-light-secondary py-2 px-3">${cat.count}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-gray-800">${fmt(cat.total)}</span></td>
                            <td class="text-end"><span class="fs-6 text-gray-700">${fmt(cat.avg)}</span></td>
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

            function renderMethods(data, totalAmount) {
                const tbody = document.getElementById('methodBody');
                if (!data || data.length === 0) {
                    tbody.innerHTML = '';
                    return;
                }

                tbody.innerHTML = data.map(m => {
                    const pct = totalAmount > 0 ? (m.total / totalAmount) * 100 : 0;
                    return `
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-45px me-3"><span class="symbol-label bg-light-primary"><i class="ki-duotone ki-wallet fs-2x text-primary"><span class="path1"></span><span class="path2"></span></i></span></div>
                                    <div class="d-flex flex-column"><span class="fs-6 fw-bold text-gray-800">${esc(m.name)}</span><span class="fs-7 text-gray-500">${esc(m.type)}</span></div>
                                </div>
                            </td>
                            <td class="text-end"><span class="badge badge-light-secondary py-2 px-3">${m.count}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-gray-800">${fmt(m.total)}</span></td>
                            <td class="text-end"><span class="fs-6 text-gray-700">${m.count > 0 ? fmt(m.avg) : '-'}</span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress h-8px w-100 me-3"><div class="progress-bar bg-primary" style="width: ${pct}%"></div></div>
                                    <span class="fs-7 fw-bold">${pct.toFixed(1)}%</span>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            function esc(t) { const d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; }
            function fmt(n) { return CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

            return { load };
        })();
    </script>
    @endpush
    @endsection
</x-app-layout>