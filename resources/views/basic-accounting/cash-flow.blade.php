<x-app-layout>
    @section('title', __('accounting.cash_flow'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{ __('accounting.cash_flow') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted"><a href="{{ route('accounting.cash-flow') }}" class="text-muted text-hover-primary">{{ __('accounting.basic_accounting') }}</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.cash_flow') }}</li>
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
                    <button type="button" class="btn btn-light d-flex align-items-center justify-content-center gap-2" onclick="window.print()" style="height: 42px; padding: 0 24px;">
                        <i class="ki-duotone ki-printer fs-3"></i><span>{{ __('accounting.print') }}</span>
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
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2 fw-bold text-success text-break" id="sumCashIn">—</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_cash_in') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.period') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7" id="sumPeriod">—</span>
                                </div>
                                <div class="d-flex flex-stack mt-2">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.transactions') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7" id="sumTotalTx">—</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2 fw-bold text-danger text-break" id="sumCashOut">—</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_cash_out') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.period') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7" id="sumPeriod2">—</span>
                                </div>
                                <div class="d-flex flex-stack mt-2">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.average_daily') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7" id="sumAvgDaily">—</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2 fw-bold text-success text-break" id="sumNetFlow">—</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.net_cash_flow') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.net_movement') }}</span>
                                    <span class="fw-bold fs-7" id="sumNetMovement">—</span>
                                </div>
                                <div class="d-flex flex-stack mt-2">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.cash_in_out_ratio') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7" id="sumRatio">—</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Daily Cash Flow Table -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-calendar-8 fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                            {{ __('accounting.daily_cash_flow') }}
                        </h3>
                    </div>
                    <div class="card-body pt-0">
                        <div id="dailyLoading" class="d-none">
                            <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                                <div class="skeleton skeleton-text" style="width:100px;"></div>
                                <div class="flex-grow-1"><div class="skeleton skeleton-text" style="width:200px;"></div></div>
                                <div class="skeleton skeleton-text" style="width:80px;"></div>
                            </div>
                        </div>
                        <div id="dailyContainer" class="d-none table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th>{{ __('accounting.date') }}</th>
                                        <th class="text-end">{{ __('accounting.cash_in') }}</th>
                                        <th class="text-end">{{ __('accounting.cash_out') }}</th>
                                        <th class="text-end">{{ __('accounting.net_flow') }}</th>
                                        <th class="text-end">{{ __('accounting.transactions') }}</th>
                                        <th>{{ __('accounting.trend') }}</th>
                                        <th class="text-end">{{ __('accounting.daily_balance') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="dailyBody" class="text-gray-600 fw-semibold"></tbody>
                                <tfoot id="dailyFoot" class="fw-bold text-gray-700 fs-6"></tfoot>
                            </table>
                        </div>
                        <div id="dailyEmpty" class="d-none text-center py-10">
                            <div class="text-muted fs-6">{{ __('accounting.no_cash_flow_data') }}</div>
                        </div>
                    </div>
                </div>
                
                <!-- Cash Flow by Payment Method -->
                <div class="card card-flush">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-wallet fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                            {{ __('accounting.cash_flow_by_payment_method') }}
                        </h3>
                    </div>
                    <div class="card-body pt-0">
                        <div id="methodContainer" class="d-none table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-200px">{{ __('accounting.payment_method') }}</th>
                                        <th class="text-end">{{ __('accounting.cash_in') }}</th>
                                        <th class="text-end">{{ __('accounting.cash_out') }}</th>
                                        <th class="text-end">{{ __('accounting.net_flow') }}</th>
                                        <th class="text-end">{{ __('accounting.transactions') }}</th>
                                        <th>{{ __('accounting.in_out_ratio') }}</th>
                                        <th class="text-end">{{ __('accounting.average_transaction') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="methodBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
                        </div>
                        <div id="methodEmpty" class="d-none text-center py-10">
                            <div class="text-muted fs-6">{{ __('accounting.no_payment_method_data') }}</div>
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
        window.CashFlowList = (function () {
            'use strict';

            const URL_DATA = '{{ route("accounting.cash-flow.data") }}';
            const CURRENCY = '{{ currency_symbol() }}';
            let inFlight = false;

            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('applyFiltersBtn').addEventListener('click', load);
                load();
            });

            async function load() {
                if (inFlight) return;
                inFlight = true;
                showState('loading');

                const params = new URLSearchParams({
                    location_id: document.getElementById('filterLocation').value,
                    user_id: document.getElementById('filterUser').value,
                    start_date: document.getElementById('filterStartDate').value,
                    end_date: document.getElementById('filterEndDate').value,
                });

                try {
                    const res = await fetch(`${URL_DATA}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const json = await res.json();
                    
                    if (!json.success) throw new Error(json.message || 'Failed to load');
                    
                    renderSummary(json.summary);
                    renderDailyFlow(json.daily_flow, json.summary);
                    renderByMethod(json.by_method);
                    showState('table');

                } catch (err) {
                    console.error('[cash-flow] load failed:', err);
                    window.toastr?.error('Failed to load data.');
                    showState('empty');
                } finally {
                    inFlight = false;
                }
            }

            function renderSummary(s) {
                const fmt = (n) => CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                document.getElementById('sumCashIn').textContent = fmt(s.total_cash_in);
                document.getElementById('sumCashOut').textContent = fmt(s.total_cash_out);
                document.getElementById('sumPeriod').textContent = `${s.start_date} - ${s.end_date}`;
                document.getElementById('sumPeriod2').textContent = `${s.start_date} - ${s.end_date}`;
                document.getElementById('sumTotalTx').textContent = s.total_transactions;
                document.getElementById('sumAvgDaily').textContent = fmt(s.total_cash_out / s.days_count);

                const netEl = document.getElementById('sumNetFlow');
                const moveEl = document.getElementById('sumNetMovement');
                
                netEl.textContent = fmt(s.net_cash_flow);
                moveEl.textContent = (s.net_cash_flow >= 0 ? '+' : '') + fmt(s.net_cash_flow);
                
                if (s.net_cash_flow >= 0) {
                    netEl.className = 'fs-2 fw-bold text-success text-break';
                    moveEl.className = 'fw-bold text-success fs-7';
                } else {
                    netEl.className = 'fs-2 fw-bold text-danger text-break';
                    moveEl.className = 'fw-bold text-danger fs-7';
                }

                document.getElementById('sumRatio').textContent = s.total_cash_out > 0 
                    ? (s.total_cash_in / s.total_cash_out).toFixed(2) + ':1' 
                    : 'N/A';
            }

            function renderDailyFlow(days, summary) {
                const container = document.getElementById('dailyContainer');
                const empty = document.getElementById('dailyEmpty');
                const tbody = document.getElementById('dailyBody');
                const tfoot = document.getElementById('dailyFoot');

                if (!days || days.length === 0) {
                    container.classList.add('d-none');
                    empty.classList.remove('d-none');
                    return;
                }

                container.classList.remove('d-none');
                empty.classList.add('d-none');
                
                let runningBalance = 0;
                let totalIn = 0;
                let totalOut = 0;
                let totalTx = 0;

                tbody.innerHTML = days.map(day => {
                    const netFlow = day.cash_in - day.cash_out;
                    runningBalance += netFlow;
                    totalIn += day.cash_in;
                    totalOut += day.cash_out;
                    totalTx += day.transaction_count;

                    const netClass = netFlow >= 0 ? 'text-success' : 'text-danger';
                    
                    let trendHtml = '<span class="fs-7 text-gray-500">{{ __("accounting.no_activity") }}</span>';
                    if (day.cash_in > 0 || day.cash_out > 0) {
                        const total = day.cash_in + day.cash_out;
                        const inPct = day.cash_in > 0 ? (day.cash_in / total) * 100 : 0;
                        const outPct = day.cash_out > 0 ? (day.cash_out / total) * 100 : 0;
                        
                        trendHtml = `
                            <div class="d-flex align-items-center">
                                <div class="progress h-8px w-100 me-3">
                                    ${day.cash_in > 0 ? `<div class="progress-bar bg-success" style="width: ${inPct}%"></div>` : ''}
                                    ${day.cash_out > 0 ? `<div class="progress-bar bg-danger" style="width: ${outPct}%"></div>` : ''}
                                </div>
                                <span class="fs-7 fw-bold">${inPct.toFixed(0)}%</span>
                            </div>
                        `;
                    }

                    return `
                        <tr>
                            <td>
                                <span class="fs-6 fw-bold text-gray-800">${esc(day.date)}</span>
                                <span class="fs-7 text-gray-500 d-block">${esc(day.day_name)}</span>
                            </td>
                            <td class="text-end"><span class="fs-6 fw-bold text-success">${fmt(day.cash_in)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-danger">${fmt(day.cash_out)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold ${netClass}">${fmt(netFlow)}</span></td>
                            <td class="text-end"><span class="badge badge-light-secondary py-2 px-3">${day.transaction_count}</span></td>
                            <td>${trendHtml}</td>
                            <td class="text-end"><span class="fs-6 fw-bold text-gray-800">${fmt(runningBalance)}</span></td>
                        </tr>
                    `;
                }).join('');

                tfoot.innerHTML = `
                    <tr>
                        <td>{{ __('accounting.total') }}</td>
                        <td class="text-end text-success">${fmt(totalIn)}</td>
                        <td class="text-end text-danger">${fmt(totalOut)}</td>
                        <td class="text-end ${summary.net_cash_flow >= 0 ? 'text-success' : 'text-danger'}">${fmt(summary.net_cash_flow)}</td>
                        <td class="text-end">${totalTx}</td>
                        <td></td>
                        <td class="text-end text-gray-800">${fmt(runningBalance)}</td>
                    </tr>
                `;
            }

            function renderByMethod(methods) {
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

                tbody.innerHTML = methods.map(m => {
                    const netFlow = m.cash_in - m.cash_out;
                    const netClass = netFlow >= 0 ? 'text-success' : 'text-danger';
                    const avg = m.transaction_count > 0 ? (m.cash_in + m.cash_out) / m.transaction_count : 0;
                    
                    let ratioHtml = '<span class="fs-7 text-gray-500">{{ __("accounting.no_activity") }}</span>';
                    if (m.cash_in > 0 || m.cash_out > 0) {
                        const total = m.cash_in + m.cash_out;
                        const inPct = (m.cash_in / total) * 100;
                        ratioHtml = `
                            <div class="d-flex align-items-center">
                                <div class="progress h-8px w-100 me-3">
                                    ${m.cash_in > 0 ? `<div class="progress-bar bg-success" style="width: ${inPct}%"></div>` : ''}
                                    ${m.cash_out > 0 ? `<div class="progress-bar bg-danger" style="width: ${100 - inPct}%"></div>` : ''}
                                </div>
                                <span class="fs-7 fw-bold">${inPct.toFixed(0)}%</span>
                            </div>
                        `;
                    }

                    return `
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-45px me-3">
                                        <span class="symbol-label bg-light-primary">
                                            <i class="ki-duotone ki-wallet fs-2x text-primary"><span class="path1"></span><span class="path2"></span></i>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fs-6 fw-bold text-gray-800">${esc(m.name)}</span>
                                        <span class="fs-7 text-gray-500">${esc(m.type)}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end"><span class="fs-6 fw-bold text-success">${fmt(m.cash_in)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-danger">${fmt(m.cash_out)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold ${netClass}">${fmt(netFlow)}</span></td>
                            <td class="text-end"><span class="badge badge-light-secondary py-2 px-3">${m.transaction_count}</span></td>
                            <td>${ratioHtml}</td>
                            <td class="text-end"><span class="fs-6 text-gray-700">${m.transaction_count > 0 ? fmt(avg) : '-'}</span></td>
                        </tr>
                    `;
                }).join('');
            }

            function showState(which) {
                document.getElementById('dailyLoading')?.classList.toggle('d-none', which !== 'loading');
                document.getElementById('dailyContainer')?.classList.toggle('d-none', which !== 'table');
                document.getElementById('dailyEmpty')?.classList.toggle('d-none', which !== 'empty');
                document.getElementById('methodContainer')?.classList.toggle('d-none', which !== 'table');
                document.getElementById('methodEmpty')?.classList.toggle('d-none', which !== 'empty');
            }

            function esc(t) { const d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; }
            function fmt(n) { return CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

            return { load };
        })();
    </script>
    @endpush
    @endsection
</x-app-layout>