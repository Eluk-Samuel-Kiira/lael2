<x-app-layout>
    @section('title', __('accounting.expense_analysis'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{ __('accounting.expense_analysis') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted"><a href="{{ route('accounting.expense-analysis') }}" class="text-muted text-hover-primary">{{ __('accounting.basic_accounting') }}</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.expense_analysis') }}</li>
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
                
                <!-- Summary Cards -->
                <div class="row g-5 g-xl-8 mb-8">
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-danger text-break" id="sumTotal">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_expenses') }}</span></div></div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.period') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumPeriod">—</span></div>
                                <div class="d-flex flex-stack mt-2"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.expense_count') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumCount">—</span></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-gray-800 text-break" id="sumAvg">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.average_expense') }}</span></div></div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.per_transaction') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumAvg2">—</span></div>
                                <div class="d-flex flex-stack mt-2"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.total_categories') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumCategories">—</span></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7"><div class="card-title d-flex flex-column"><span class="fs-2 fw-bold text-danger text-break" id="sumLargest">—</span><span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.largest_expense') }}</span></div></div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.single_expense') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumLargest2">—</span></div>
                                <div class="d-flex flex-stack mt-2"><span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.date_range') }}</span><span class="fw-bold text-gray-700 fs-7" id="sumDays">—</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- By Category -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-category fs-2 me-2 text-danger"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.expenses_by_category') }}</h3></div>
                    <div class="card-body pt-0">
                        <div id="catLoading" class="d-none"><div class="d-flex align-items-center gap-4 py-4 border-bottom"><div class="skeleton skeleton-text" style="width:100px;"></div><div class="flex-grow-1"><div class="skeleton skeleton-text" style="width:200px;"></div></div><div class="skeleton skeleton-text" style="width:80px;"></div></div></div>
                        <div id="catContainer" class="d-none table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-200px">{{ __('accounting.category') }}</th>
                                        <th class="text-end">{{ __('accounting.expense_count') }}</th>
                                        <th class="text-end">{{ __('accounting.total_amount') }}</th>
                                        <th class="text-end">{{ __('accounting.average_amount') }}</th>
                                        <th class="text-end">{{ __('accounting.max_amount') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.percentage') }}</th>
                                        <th class="text-center">{{ __('accounting.trend') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="catBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
                        </div>
                        <div id="catEmpty" class="d-none text-center py-10"><div class="text-muted fs-6">{{ __('accounting.no_expense_categories') }}</div></div>
                    </div>
                </div>
                
                <!-- Top Expenses -->
                <div class="card card-flush">
                    <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold text-gray-800"><i class="ki-duotone ki-dollar fs-2 me-2 text-danger"><span class="path1"></span><span class="path2"></span></i>{{ __('accounting.top_expenses') }}</h3></div>
                    <div class="card-body pt-0">
                        <div id="topLoading" class="d-none"><div class="d-flex align-items-center gap-4 py-4 border-bottom"><div class="skeleton skeleton-text" style="width:40px;"></div><div class="skeleton skeleton-text" style="width:100px;"></div><div class="flex-grow-1"><div class="skeleton skeleton-text" style="width:200px;"></div></div><div class="skeleton skeleton-text" style="width:80px;"></div></div></div>
                        <div id="topContainer" class="d-none table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-50px">#</th>
                                        <th class="min-w-150px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-200px">{{ __('accounting.description') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.category') }}</th>
                                        <th class="text-end">{{ __('accounting.amount') }}</th>
                                        <th class="text-end">{{ __('accounting.balance_after') }}</th>
                                        <th class="text-center">{{ __('accounting.processed_by') }}</th>
                                        <th class="text-center">{{ __('accounting.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="topBody" class="text-gray-600 fw-semibold"></tbody>
                            </table>
                            <div id="topPagination" class="mt-6 d-flex justify-content-center"></div>
                        </div>
                        <div id="topEmpty" class="d-none text-center py-10"><div class="text-muted fs-6">{{ __('accounting.no_expenses_found') }}</div></div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .desc-hover {
            cursor: help;
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .skeleton { display: inline-block; background: linear-gradient(90deg, #f1f1f4 0%, #e9e9ed 50%, #f1f1f4 100%); background-size: 200% 100%; animation: skeleton-shimmer 1.4s ease-in-out infinite; border-radius: 6px; }
        .skeleton-text { height: 12px; margin: 4px 0; display: block; }
        @keyframes skeleton-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    </style>
    @endpush

    @push('scripts')
    <script>
        window.ExpenseAnalysis = (function () {
            'use strict';

            const URL_DATA = '{{ route("accounting.expense-analysis.data") }}';
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
                showState('loading');

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
                    
                    renderSummary(json.summary, json.by_category.length);
                    renderCategories(json.by_category);
                    renderTopExpenses(json.top_expenses, json.pagination);
                    showState('table');

                } catch (err) {
                    console.error('[expense-analysis] load failed:', err);
                    window.toastr?.error('Failed to load data.');
                    showState('empty');
                } finally {
                    inFlight = false;
                }
            }

            function renderSummary(s, catCount) {
                const fmt = (n) => CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                document.getElementById('sumTotal').textContent = fmt(s.total_expenses);
                document.getElementById('sumPeriod').textContent = `${s.start_date} - ${s.end_date}`;
                document.getElementById('sumCount').textContent = s.expense_count;

                document.getElementById('sumAvg').textContent = fmt(s.average_expense);
                document.getElementById('sumAvg2').textContent = fmt(s.average_expense);
                document.getElementById('sumCategories').textContent = catCount;

                document.getElementById('sumLargest').textContent = fmt(s.largest_expense);
                document.getElementById('sumLargest2').textContent = fmt(s.largest_expense);
                document.getElementById('sumDays').textContent = s.days + ' {{ __("accounting.days") }}';
            }

            function renderCategories(data) {
                const tbody = document.getElementById('catBody');
                
                if (!data || data.length === 0) {
                    document.getElementById('catContainer').classList.add('d-none');
                    document.getElementById('catEmpty').classList.remove('d-none');
                    return;
                }

                document.getElementById('catContainer').classList.remove('d-none');
                document.getElementById('catEmpty').classList.add('d-none');

                tbody.innerHTML = data.map(cat => {
                    return `
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-45px me-3">
                                        <span class="symbol-label bg-light-danger">
                                            <i class="ki-duotone ki-arrow-down fs-2x text-danger"><span class="path1"></span><span class="path2"></span></i>
                                        </span>
                                    </div>
                                    <span class="fs-6 fw-bold text-gray-800">${esc(cat.category)}</span>
                                </div>
                            </td>
                            <td class="text-end"><span class="badge badge-light-secondary py-2 px-3">${cat.count}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-danger">${fmt(cat.total)}</span></td>
                            <td class="text-end"><span class="fs-6 text-gray-700">${fmt(cat.avg)}</span></td>
                            <td class="text-end"><span class="fs-6 fw-bold text-gray-800">${fmt(cat.max)}</span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress h-8px w-100 me-3"><div class="progress-bar bg-danger" style="width: ${cat.percentage}%"></div></div>
                                    <span class="fs-7 fw-bold">${cat.percentage.toFixed(1)}%</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-light-${cat.trend_color} py-2 px-3">${esc(cat.trend_label)}</span>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            
            function renderTopExpenses(data, pagination) {
                const tbody = document.getElementById('topBody');
                
                if (!data || data.length === 0) {
                    document.getElementById('topContainer').classList.add('d-none');
                    document.getElementById('topEmpty').classList.remove('d-none');
                    return;
                }

                document.getElementById('topContainer').classList.remove('d-none');
                document.getElementById('topEmpty').classList.add('d-none');
                const startIdx = (pagination.current_page - 1) * 10;

                const truncate = (str, n) => {
                    return (str && str.length > n) ? str.substr(0, n - 1) + '…' : str;
                };

                tbody.innerHTML = data.map((tx, idx) => {
                    const statusColors = { 'COMPLETED': 'success', 'PENDING': 'warning', 'FAILED': 'danger', 'CANCELLED': 'secondary', 'REVERSED': 'dark' };
                    const sColor = statusColors[tx.status] || 'secondary';

                    // Changed to data-bs-toggle="tooltip" with custom html
                    const descHtml = `
                        <div class="desc-hover" 
                             data-bs-toggle="tooltip" 
                             data-bs-placement="top" 
                             data-bs-html="true" 
                             title="<div class='text-start' style='max-width: 300px;'>${esc(tx.description)}</div>">
                            <span class="fs-6 text-gray-800">${esc(truncate(tx.description, 50))}</span>
                        </div>
                        ${tx.notes ? `
                        <div class="desc-hover mt-1" 
                             data-bs-toggle="tooltip" 
                             data-bs-placement="top" 
                             data-bs-html="true" 
                             title="<div class='text-start' style='max-width: 300px;'>${esc(tx.notes)}</div>">
                            <span class="fs-7 text-gray-500">
                                <i class="ki-duotone ki-information fs-1 me-1"><span class="path1"></span><span class="path2"></span></i>
                                ${esc(truncate(tx.notes, 40))}
                            </span>
                        </div>` : ''}
                    `;

                    return `
                        <tr>
                            <td><span class="badge badge-light-secondary py-2 px-3">${startIdx + idx + 1}</span></td>
                            <td><span class="fs-6 fw-bold text-gray-800">${esc(tx.date)}</span><span class="fs-7 text-gray-500 d-block">${esc(tx.time)}</span></td>
                            <td>${descHtml}</td>
                            <td><div class="d-flex flex-column"><span class="fs-6 fw-bold text-gray-800">${esc(tx.payment_method)}</span><span class="fs-7 text-gray-500">${esc(tx.payment_method_type)}</span></div></td>
                            <td><span class="badge badge-light-danger py-2 px-3">${esc(tx.category)}</span></td>
                            <td class="text-end"><span class="fs-5 fw-bold text-danger">${fmt(tx.amount)}</span></td>
                            <td class="text-end"><span class="fs-6 text-gray-700">${fmt(tx.balance_after)}</span></td>
                            <td class="text-center"><span class="fw-bold text-gray-700 fs-7">${esc(tx.user)}</span></td>
                            <td class="text-center"><span class="badge badge-light-${sColor} py-2 px-3">${esc(tx.status)}</span></td>
                        </tr>
                    `;
                }).join('');

                // Initialize Bootstrap tooltips
                initTooltips(tbody);

                renderPagination(pagination);
            }

            // Add this helper function inside the IIFE
            function initTooltips(container) {
                if (!container) return;
                
                // Dispose existing tooltips to prevent memory leaks
                container.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                    const existing = bootstrap.Tooltip.getInstance(el);
                    if (existing) existing.dispose();
                });

                // Initialize new ones
                container.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                    new bootstrap.Tooltip(el, {
                        trigger: 'hover',
                        boundary: 'window',
                        delay: { show: 100, hide: 100 } // Small delay so it doesn't flicker
                    });
                });
            }

            function renderPagination(p) {
                const container = document.getElementById('topPagination');
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

            function showState(which) {
                document.getElementById('catLoading')?.classList.toggle('d-none', which !== 'loading');
                document.getElementById('catContainer')?.classList.toggle('d-none', which !== 'table');
                document.getElementById('catEmpty')?.classList.toggle('d-none', which !== 'empty');
                document.getElementById('topLoading')?.classList.toggle('d-none', which !== 'loading');
                document.getElementById('topContainer')?.classList.toggle('d-none', which !== 'table');
                document.getElementById('topEmpty')?.classList.toggle('d-none', which !== 'empty');
            }

            function esc(t) { const d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; }
            function fmt(n) { return CURRENCY + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

            return { load };
        })();
    </script>
    @endpush
    @endsection
</x-app-layout>