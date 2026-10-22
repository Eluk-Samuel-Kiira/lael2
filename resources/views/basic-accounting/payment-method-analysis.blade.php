<x-app-layout>
    @section('title', __('accounting.payment_method_analysis'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{ __('accounting.payment_method_analysis') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted"><a href="{{ route('accounting.payment-method-analysis') }}" class="text-muted text-hover-primary">{{ __('accounting.basic_accounting') }}</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.payment_method_analysis') }}</li>
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
                
                <!-- Summary Stats -->
                <div class="row g-5 g-xl-8 mb-8">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-gray-800 text-break" id="statBalance">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_balance') }}</span></div></div>
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.period') }}</span><span class="fw-bold text-gray-700 fs-7" id="statPeriod">—</span></div></div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-info" id="statTxCount">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_transactions') }}</span></div></div>
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.total_amount') }}</span><span class="fw-bold text-gray-700 fs-7" id="statTxAmount">—</span></div></div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-4 fw-bold text-primary" id="statMostActive">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.most_active_method') }}</span></div></div>
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.transactions') }}</span><span class="fw-bold text-gray-700 fs-7" id="statMostActiveTx">—</span></div></div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-4 fw-bold text-success" id="statHighestBalance">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.highest_balance') }}</span></div></div>
                            <div class="card-body pt-0"><div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.balance') }}</span><span class="fw-bold text-gray-700 fs-7" id="statHighestBalanceAmt">—</span></div></div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Methods Table -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-wallet fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                            {{ __('accounting.payment_methods_analysis') }}
                        </h3>
                        <div class="card-toolbar">
                            <div class="d-flex align-items-center position-relative">
                                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-3"><span class="path1"></span><span class="path2"></span></i>
                                <input type="text" id="searchMethods" class="form-control form-control-solid w-250px ps-10" placeholder="{{ __('accounting.search_methods') }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div id="tableLoading" class="d-none">
                            <div class="d-flex align-items-center gap-4 py-4 border-bottom"><div class="skeleton skeleton-text" style="width:100px;"></div><div class="flex-grow-1"><div class="skeleton skeleton-text" style="width:200px;"></div></div><div class="skeleton skeleton-text" style="width:80px;"></div></div>
                        </div>
                        
                        <div id="tableContainer" class="d-none table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="methodsTable">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-200px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="text-end">{{ __('accounting.current_balance') }}</th>
                                        <th class="text-end">{{ __('accounting.total_transactions') }}</th>
                                        <th class="text-end">{{ __('accounting.deposits') }}</th>
                                        <th class="text-end">{{ __('accounting.withdrawals') }}</th>
                                        <th class="text-end">{{ __('accounting.net_flow') }}</th>
                                        <th class="text-end">{{ __('accounting.average_transaction') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.activity_rate') }}</th>
                                        <th class="text-center">{{ __('accounting.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
                        </div>
                        
                        <div id="tableEmpty" class="d-none text-center py-10"><div class="text-muted fs-6">{{ __('accounting.no_payment_methods_found') }}</div></div>
                    </div>
                </div>
                
                <!-- Chart -->
                <div class="card card-flush">
                    <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.performance_comparison') }}</h3></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-center">
                            <div class="w-100" style="max-width: 900px; height: 350px;">
                                <canvas id="performanceChart"></canvas>
                            </div>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.PaymentMethodAnalysis = (function () {
            'use strict';

            const URL_DATA = '{{ route("accounting.payment-method-analysis.data") }}';
            const CURRENCY = '{{ currency_symbol() }}';
            let inFlight = false;
            let chartInstance = null;

            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('applyFiltersBtn').addEventListener('click', load);
                document.getElementById('searchMethods').addEventListener('input', filterTable);
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
                    
                    if (!json.success) throw new Error('Failed to load');
                    
                    renderStats(json.stats, json.start_date, json.end_date);
                    
                    if (!json.methods || json.methods.length === 0) {
                        showState('empty');
                        return;
                    }

                    renderTable(json.methods);
                    renderChart(json.methods);
                    showState('table');

                } catch (err) {
                    console.error('[pm-analysis] load failed:', err);
                    window.toastr?.error('Failed to load data.');
                    showState('empty');
                } finally {
                    inFlight = false;
                }
            }

            function renderStats(s, start, end) {
                const fmt = (n) => CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                document.getElementById('statBalance').textContent = fmt(s.total_balance);
                document.getElementById('statPeriod').textContent = `${start} - ${end}`;
                document.getElementById('statTxCount').textContent = s.total_transactions;
                document.getElementById('statTxAmount').textContent = fmt(s.total_transaction_amount);
                
                if (s.most_active_method) {
                    document.getElementById('statMostActive').textContent = s.most_active_method.name;
                    document.getElementById('statMostActiveTx').textContent = s.most_active_method.total_transactions;
                } else {
                    document.getElementById('statMostActive').textContent = '-';
                    document.getElementById('statMostActiveTx').textContent = '-';
                }
                
                if (s.highest_balance_method) {
                    document.getElementById('statHighestBalance').textContent = s.highest_balance_method.name;
                    document.getElementById('statHighestBalanceAmt').textContent = fmt(s.highest_balance_method.balance);
                } else {
                    document.getElementById('statHighestBalance').textContent = '-';
                    document.getElementById('statHighestBalanceAmt').textContent = '-';
                }
            }

            function renderTable(methods) {
                const tbody = document.getElementById('tableBody');
                
                const typeColors = { 'cash': 'warning', 'bank_account': 'info', 'card': 'primary', 'digital_wallet': 'success', 'mobile_money': 'danger', 'check': 'secondary', 'other': 'dark' };
                const typeIcons = { 'cash': 'ki-dollar', 'bank_account': 'ki-bank', 'card': 'ki-credit-cart', 'mobile_money': 'ki-phone' };

                tbody.innerHTML = methods.map(m => {
                    const color = typeColors[m.type] || 'secondary';
                    const icon = typeIcons[m.type] || 'ki-wallet';
                    const activeClass = m.is_active ? 'primary' : 'danger';
                    const netCls = m.net_flow >= 0 ? 'text-success' : 'text-danger';
                    
                    // Activity rate calculation (relative to max in the list)
                    const maxTx = Math.max(...methods.map(x => x.total_transactions)) || 1;
                    const activityRate = (m.total_transactions / maxTx) * 100;

                    return `
                        <tr class="method-row">
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-45px me-3">
                                        <span class="symbol-label bg-light-${activeClass}">
                                            <i class="ki-duotone ${icon} fs-2x text-${activeClass}"><span class="path1"></span><span class="path2"></span></i>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fs-6 fw-bold text-gray-800">${esc(m.name)}</span>
                                        <span class="fs-7 text-gray-500">${esc(m.code)}</span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-light-${color} py-2 px-3">${esc(m.type_label)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold ${m.current_balance >= 0 ? 'text-success' : 'text-danger'}">${fmt(m.current_balance)}</span></td>
                            <td class="text-end"><span class="badge badge-light-secondary py-2 px-3">${m.total_transactions}</span></td>
                            <td class="text-end">
                                <span class="fs-6 fw-bold text-success">${fmt(m.deposit_total)}</span>
                                <span class="fs-8 text-gray-500 d-block">${m.deposit_count} {{ __('accounting.txns') }}</span>
                            </td>
                            <td class="text-end">
                                <span class="fs-6 fw-bold text-danger">${fmt(m.withdrawal_total)}</span>
                                <span class="fs-8 text-gray-500 d-block">${m.withdrawal_count} {{ __('accounting.txns') }}</span>
                            </td>
                            <td class="text-end"><span class="fs-6 fw-bold ${netCls}">${fmt(m.net_flow)}</span></td>
                            <td class="text-end"><span class="fs-6 text-gray-700">${m.total_transactions > 0 ? fmt(m.avg_transaction) : '-'}</span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress h-8px w-100 me-3"><div class="progress-bar bg-primary" style="width: ${activityRate}%"></div></div>
                                    <span class="fs-7 fw-bold">${activityRate.toFixed(1)}%</span>
                                </div>
                            </td>
                            <td class="text-center"><span class="badge badge-light-${m.is_active ? 'success' : 'danger'} py-2 px-3">${m.is_active ? '{{ __("accounting.active") }}' : '{{ __("accounting.inactive") }}'}</span></td>
                        </tr>
                    `;
                }).join('');
            }

            function renderChart(methods) {
                const ctx = document.getElementById('performanceChart');
                if (!ctx) return;

                if (chartInstance) {
                    chartInstance.destroy();
                }

                const topMethods = methods.slice(0, 5);

                chartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: topMethods.map(m => m.name),
                        datasets: [
                            {
                                label: '{{ __('accounting.deposits') }}',
                                data: topMethods.map(m => m.deposit_total),
                                backgroundColor: 'rgba(40, 199, 111, 0.8)',
                                borderColor: 'rgb(40, 199, 111)',
                                borderWidth: 2,
                                borderRadius: 4,
                            },
                            {
                                label: '{{ __('accounting.withdrawals') }}',
                                data: topMethods.map(m => m.withdrawal_total),
                                backgroundColor: 'rgba(245, 101, 101, 0.8)',
                                borderColor: 'rgb(245, 101, 101)',
                                borderWidth: 2,
                                borderRadius: 4,
                            },
                            {
                                label: '{{ __('accounting.balance') }}',
                                data: topMethods.map(m => m.current_balance),
                                type: 'line',
                                backgroundColor: 'rgba(54, 153, 255, 0.1)',
                                borderColor: 'rgb(54, 153, 255)',
                                borderWidth: 3,
                                pointBackgroundColor: 'rgb(54, 153, 255)',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 5,
                                tension: 0.3,
                                fill: true,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top', labels: { padding: 20, usePointStyle: true, pointStyle: 'circle', font: { size: 12, weight: 'bold' } } },
                            tooltip: { callbacks: { label: function(context) { return context.dataset.label + ': ' + CURRENCY + context.parsed.y.toFixed(2); } } }
                        },
                        scales: {
                            y: { beginAtZero: true, ticks: { callback: function(value) { return CURRENCY + value.toFixed(0); }, font: { size: 11 } }, grid: { color: 'rgba(0, 0, 0, 0.05)' } },
                            x: { grid: { display: false }, ticks: { font: { size: 12, weight: 'bold' } } }
                        },
                        animation: { duration: 1000, easing: 'easeInOutQuart' }
                    }
                });
            }

            function filterTable() {
                const term = document.getElementById('searchMethods').value.toLowerCase();
                document.querySelectorAll('.method-row').forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
                });
            }

            function showState(which) {
                document.getElementById('tableLoading')?.classList.toggle('d-none', which !== 'loading');
                document.getElementById('tableContainer')?.classList.toggle('d-none', which !== 'table');
                document.getElementById('tableEmpty')?.classList.toggle('d-none', which !== 'empty');
            }

            function esc(t) { const d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; }
            function fmt(n) { return CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

            return { load };
        })();
    </script>
    @endpush
    @endsection
</x-app-layout>