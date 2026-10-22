<x-app-layout>
    @section('title', __('accounting.income_statement'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{ __('accounting.income_statement') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.income-statement') }}" class="text-muted text-hover-primary">{{ __('accounting.basic_accounting') }}</a>
                    </li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.income_statement') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                <div class="d-flex flex-wrap align-items-center gap-2" id="filterForm">
                    <!-- Location Filter -->
                    <div class="d-flex align-items-center bg-light-primary rounded-3 px-4 py-2" style="min-width: 220px;">
                        <i class="ki-duotone ki-geolocation fs-2 text-primary me-3"><span class="path1"></span><span class="path2"></span></i>
                        <select class="form-select form-select-sm form-select-solid border-0 bg-transparent ps-0" id="filterLocation" style="min-width: 160px; width: 100%;">
                            <option value="">{{ __('pagination.all_locations') }}</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ ($locationId ?? '') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- User Filter -->
                    <div class="d-flex align-items-center bg-light-info rounded-3 px-4 py-2" style="min-width: 220px;">
                        <i class="ki-duotone ki-user fs-2 text-info me-3"><span class="path1"></span><span class="path2"></span></i>
                        <select class="form-select form-select-sm form-select-solid border-0 bg-transparent ps-0" id="filterUser" style="min-width: 160px; width: 100%;">
                            <option value="">{{ __('accounting.all_users') }}</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ ($userId ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Period Selector -->
                    <select id="filterPeriod" class="form-select form-select-solid w-100 w-sm-auto" style="min-width: 140px;">
                        <option value="month" {{ $period === 'month' ? 'selected' : '' }}>{{ __('accounting.this_month') }}</option>
                        <option value="quarter" {{ $period === 'quarter' ? 'selected' : '' }}>{{ __('accounting.this_quarter') }}</option>
                        <option value="year" {{ $period === 'year' ? 'selected' : '' }}>{{ __('accounting.this_year') }}</option>
                        <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>{{ __('accounting.custom') }}</option>
                    </select>
                    
                    <!-- Custom Date Range -->
                    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2" id="customDateWrap" style="display: {{ $period === 'custom' ? 'flex' : 'none' }} !important;">
                        <input type="date" id="filterStartDate" class="form-control form-control-solid w-100 w-sm-auto" style="min-width: 140px;">
                        <input type="date" id="filterEndDate" class="form-control form-control-solid w-100 w-sm-auto" style="min-width: 140px;">
                        <button type="button" class="btn btn-sm btn-primary w-100 w-sm-auto" id="applyCustomBtn">{{ __('accounting.apply') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                <!-- Summary Cards -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Revenue -->
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2 fw-bold text-success text-break" id="sumRevenue">—</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_revenue') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.period') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7" id="sumPeriodRange">—</span>
                                </div>
                                <div class="mt-3" id="revenueTopCategories"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Expenses -->
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2 fw-bold text-danger text-break" id="sumExpenses">—</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_expenses') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.period') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7" id="sumPeriodRange2">—</span>
                                </div>
                                <div class="mt-3" id="expenseTopCategories"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Net Income -->
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100" id="netIncomeCard">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2 fw-bold text-success text-break" id="sumNetIncome">—</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.net_income') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.profit_margin') }}</span>
                                    <span class="fw-bold fs-7" id="sumProfitMargin">—</span>
                                </div>
                                <div class="d-flex flex-stack mt-2">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.expense_ratio') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7" id="sumExpenseRatio">—</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Monthly Trends -->
                <div class="card card-flush mb-8 d-none" id="trendsCard">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            {{ __('accounting.monthly_trends') }}
                        </h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th>{{ __('accounting.month') }}</th>
                                        <th class="text-end">{{ __('accounting.revenue') }}</th>
                                        <th class="text-end">{{ __('accounting.expenses') }}</th>
                                        <th class="text-end">{{ __('accounting.net_income') }}</th>
                                        <th class="text-end">{{ __('accounting.profit_margin') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="trendsBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Breakdown -->
                <div class="row g-5 g-xl-8">
                    <div class="col-xl-6">
                        <div class="card card-flush h-md-100">
                            <div class="card-header border-0 pt-6">
                                <h3 class="card-title fw-bold text-gray-800">
                                    <i class="ki-duotone ki-arrow-up fs-2 me-2 text-success"><span class="path1"></span><span class="path2"></span></i>
                                    {{ __('accounting.revenue_breakdown') }}
                                </h3>
                            </div>
                            <div class="card-body pt-0" id="revenueBreakdownContainer"></div>
                        </div>
                    </div>
                    
                    <div class="col-xl-6">
                        <div class="card card-flush h-md-100">
                            <div class="card-header border-0 pt-6">
                                <h3 class="card-title fw-bold text-gray-800">
                                    <i class="ki-duotone ki-arrow-down fs-2 me-2 text-danger"><span class="path1"></span><span class="path2"></span></i>
                                    {{ __('accounting.expenses_breakdown') }}
                                </h3>
                            </div>
                            <div class="card-body pt-0" id="expenseBreakdownContainer"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Profitability Analysis -->
                <div class="card card-flush mt-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                            {{ __('accounting.profitability_analysis') }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-5 g-xl-10">
                            <div class="col-md-3">
                                <div class="d-flex flex-column text-center p-5 bg-light-success rounded-3">
                                    <span class="fs-3 fw-bold text-success" id="profRevenue">—</span>
                                    <span class="text-gray-500 fs-6">{{ __('accounting.total_revenue') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex flex-column text-center p-5 bg-light-danger rounded-3">
                                    <span class="fs-3 fw-bold text-danger" id="profExpenses">—</span>
                                    <span class="text-gray-500 fs-6">{{ __('accounting.total_expenses') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex flex-column text-center p-5 rounded-3" id="profNetCard">
                                    <span class="fs-3 fw-bold" id="profNetIncome">—</span>
                                    <span class="text-gray-500 fs-6">{{ __('accounting.net_income') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex flex-column text-center p-5 bg-light-info rounded-3">
                                    <span class="fs-3 fw-bold text-info" id="profMargin">—</span>
                                    <span class="text-gray-500 fs-6">{{ __('accounting.profit_margin') }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-8" id="expenseRatioBarWrap">
                            <div class="col-12">
                                <div class="d-flex flex-stack mb-3">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.expense_to_revenue_ratio') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700" id="profRatioLabel">0%</span>
                                </div>
                                <div class="progress h-10px">
                                    <div class="progress-bar bg-danger" id="profRatioBar" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        window.IncomeStatementList = (function () {
            'use strict';

            const URL_DATA = '{{ route("accounting.income-statement.data") }}';
            const CURRENCY = '{{ currency_symbol() }}';
            let inFlight = false;

            document.addEventListener('DOMContentLoaded', () => {
                bindFilters();
                load();
            });

            function bindFilters() {
                document.getElementById('filterLocation').addEventListener('change', load);
                document.getElementById('filterUser').addEventListener('change', load);
                
                document.getElementById('filterPeriod').addEventListener('change', function() {
                    const isCustom = this.value === 'custom';
                    document.getElementById('customDateWrap').style.display = isCustom ? 'flex' : 'none';
                    if (!isCustom) load();
                });

                document.getElementById('applyCustomBtn').addEventListener('click', load);
            }

            async function load() {
                if (inFlight) return;
                inFlight = true;

                const period = document.getElementById('filterPeriod').value;
                const params = new URLSearchParams({
                    period: period,
                    location_id: document.getElementById('filterLocation').value,
                    user_id: document.getElementById('filterUser').value
                });

                if (period === 'custom') {
                    params.set('start_date', document.getElementById('filterStartDate').value);
                    params.set('end_date', document.getElementById('filterEndDate').value);
                }

                try {
                    const res = await fetch(`${URL_DATA}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const json = await res.json();
                    
                    if (json.success) {
                        renderSummary(json.summary);
                        renderTrends(json.monthly_trends);
                        renderBreakdown('revenueBreakdownContainer', json.revenue_breakdown, 'success');
                        renderBreakdown('expenseBreakdownContainer', json.expenses_breakdown, 'danger');
                        renderProfitability(json.summary);
                    }
                } catch (err) {
                    console.error('[income-statement] load failed:', err);
                    window.toastr?.error('Failed to load data.');
                } finally {
                    inFlight = false;
                }
            }

            function renderSummary(s) {
                const fmt = (n) => CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                document.getElementById('sumRevenue').textContent = fmt(s.revenue);
                document.getElementById('sumExpenses').textContent = fmt(s.expenses);
                document.getElementById('sumPeriodRange').textContent = `${s.start_date} - ${s.end_date}`;
                document.getElementById('sumPeriodRange2').textContent = `${s.start_date} - ${s.end_date}`;

                const netEl = document.getElementById('sumNetIncome');
                const cardEl = document.getElementById('netIncomeCard');
                
                netEl.textContent = fmt(s.net_income);
                if (s.net_income >= 0) {
                    netEl.className = 'fs-2 fw-bold text-success text-break';
                } else {
                    netEl.className = 'fs-2 fw-bold text-danger text-break';
                }

                const marginEl = document.getElementById('sumProfitMargin');
                marginEl.textContent = s.revenue > 0 ? s.profit_margin.toFixed(1) + '%' : 'N/A';
                marginEl.className = `fw-bold fs-7 ${s.net_income >= 0 ? 'text-success' : 'text-danger'}`;

                document.getElementById('sumExpenseRatio').textContent = s.revenue > 0 ? s.expense_ratio.toFixed(1) + '%' : 'N/A';
            }

            function renderTrends(trends) {
                const card = document.getElementById('trendsCard');
                const tbody = document.getElementById('trendsBody');

                if (!trends || trends.length === 0) {
                    card.classList.add('d-none');
                    return;
                }

                card.classList.remove('d-none');
                tbody.innerHTML = trends.map(t => {
                    const netClass = t.net_income >= 0 ? 'text-success' : 'text-danger';
                    const marginTxt = t.revenue > 0 ? t.margin.toFixed(1) + '%' : 'N/A';
                    
                    return `
                        <tr>
                            <td><span class="fs-6 fw-bold text-gray-800">${t.month}</span></td>
                            <td class="text-end"><span class="text-success fw-bold">${fmt(t.revenue)}</span></td>
                            <td class="text-end"><span class="text-danger fw-bold">${fmt(t.expenses)}</span></td>
                            <td class="text-end"><span class="${netClass} fw-bold">${fmt(t.net_income)}</span></td>
                            <td class="text-end"><span class="${netClass} fw-bold">${marginTxt}</span></td>
                        </tr>
                    `;
                }).join('');
            }

            function renderBreakdown(containerId, data, color) {
                const container = document.getElementById(containerId);
                if (!data || data.length === 0) {
                    container.innerHTML = `<div class="text-center py-10"><div class="text-muted fs-6">{{ __("accounting.no_data") }}</div></div>`;
                    return;
                }

                container.innerHTML = `
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-200px">{{ __('accounting.category') }}</th>
                                    <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                    <th class="min-w-100px text-end">{{ __('accounting.percentage') }}</th>
                                    <th class="min-w-150px">{{ __('accounting.distribution') }}</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 fw-semibold">
                                ${data.map(item => `
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="bullet bullet-dot bg-${color} h-10px w-10px me-2"></span>
                                                <span class="fs-6 fw-bold text-gray-800">${item.category}</span>
                                            </div>
                                        </td>
                                        <td class="text-end"><span class="fs-6 fw-bold text-${color}">${fmt(item.total)}</span></td>
                                        <td class="text-end"><span class="fs-6 fw-bold text-gray-700">${item.percentage.toFixed(1)}%</span></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress h-8px w-100 me-3">
                                                    <div class="progress-bar bg-${color}" role="progressbar" style="width: ${item.percentage}%"></div>
                                                </div>
                                                <span class="fs-7 fw-bold">${item.percentage.toFixed(1)}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            function renderProfitability(s) {
                const fmt = (n) => CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                document.getElementById('profRevenue').textContent = fmt(s.revenue);
                document.getElementById('profExpenses').textContent = fmt(s.expenses);
                
                const netEl = document.getElementById('profNetIncome');
                const netCard = document.getElementById('profNetCard');
                netEl.textContent = fmt(s.net_income);
                
                if (s.net_income >= 0) {
                    netEl.className = 'fs-3 fw-bold text-success';
                    netCard.className = 'd-flex flex-column text-center p-5 bg-light-success rounded-3';
                } else {
                    netEl.className = 'fs-3 fw-bold text-danger';
                    netCard.className = 'd-flex flex-column text-center p-5 bg-light-danger rounded-3';
                }

                document.getElementById('profMargin').textContent = s.revenue > 0 ? s.profit_margin.toFixed(1) + '%' : 'N/A';
                
                const ratio = Math.min(100, s.expense_ratio);
                document.getElementById('profRatioLabel').textContent = s.expense_ratio.toFixed(1) + '%';
                document.getElementById('profRatioBar').style.width = ratio + '%';
            }

            function fmt(n) {
                return CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            return { load };
        })();
    </script>
    @endpush
    @endsection
</x-app-layout>