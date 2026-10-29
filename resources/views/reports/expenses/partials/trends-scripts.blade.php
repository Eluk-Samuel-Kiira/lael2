<script>
window.ExpenseTrends = (function () {
    'use strict';

    const ROUTES = window.EXP_TRENDS_ROUTES;
    const BOOT   = window.EXP_TRENDS_BOOTSTRAP || {};
    let CURRENCY = '';
    let metaCache = null;
    let activeController = null;
    let inFlight = false;
    let chartInstance = null;

    const state = {
        period:      BOOT.period      || 'monthly',
        year:        String(BOOT.year || new Date().getFullYear()),
        category_id: BOOT.category_id || '',
        location_id: BOOT.location_id || '',
    };

    const MONTH_NAMES = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const MONTH_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    document.addEventListener('DOMContentLoaded', () => {
        seedForm();
        bind();
        loadMeta().then(() => loadData());
    });

    function bind() {
        const form = document.getElementById('expTrendsFilterForm');
        form?.addEventListener('submit', (e) => {
            e.preventDefault();
            readForm();
            loadData();
        });

        document.getElementById('expTrendsClear')?.addEventListener('click', () => {
            state.period      = 'monthly';
            state.year        = String(new Date().getFullYear());
            state.category_id = '';
            state.location_id = '';
            seedForm();
            toggleYearField();
            loadData();
        });

        // Period change → toggle year visibility (doesn't auto-apply)
        document.getElementById('trendsPeriodSelect')?.addEventListener('change', toggleYearField);

        document.querySelectorAll('[data-export-table]').forEach(el => {
            el.addEventListener('click', () => {
                const tableId  = el.dataset.exportTable;
                const filename = (el.dataset.exportFilename || 'expenses') + '_' + new Date().toISOString().slice(0, 10);
                const format   = el.dataset.exportFormat || 'xlsx';
                const sheet    = el.dataset.exportSheet || 'Sheet1';
                if (typeof window.exportCurrentPage === 'function') {
                    window.exportCurrentPage({ tableId, filename, sheetName: sheet, format });
                }
            });
        });
    }

    function toggleYearField() {
        const sel = document.getElementById('trendsPeriodSelect');
        const yearField = document.getElementById('trendsYearField');
        if (!sel || !yearField) return;
        yearField.style.display = sel.value === 'yearly' ? 'none' : '';
    }

    function seedForm() {
        const form = document.getElementById('expTrendsFilterForm');
        if (!form) return;
        Object.entries(state).forEach(([k, v]) => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) el.value = v == null ? '' : String(v);
        });
        toggleYearField();
    }
    function readForm() {
        const form = document.getElementById('expTrendsFilterForm');
        if (!form) return;
        Object.keys(state).forEach(k => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) state[k] = el.value;
        });
    }

    async function loadMeta() {
        if (metaCache) return metaCache;
        try {
            const res = await fetch(ROUTES.meta, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Meta failed');
            metaCache = json;
            CURRENCY = json.currency || '';

            const yearSel = document.querySelector('#expTrendsFilterForm [name="year"]');
            if (yearSel) {
                yearSel.innerHTML = '';
                (json.years || []).forEach(y => {
                    const o = document.createElement('option');
                    o.value = y; o.textContent = y;
                    yearSel.appendChild(o);
                });
                yearSel.value = state.year;
            }

            const catSel = document.querySelector('#expTrendsFilterForm [name="category_id"]');
            if (catSel) {
                const placeholder = catSel.querySelector('option[value=""]');
                catSel.innerHTML = '';
                if (placeholder) catSel.appendChild(placeholder);
                (json.categories || []).forEach(c => {
                    const o = document.createElement('option');
                    o.value = c.id;
                    o.textContent = c.name + (c.code ? ' (' + c.code + ')' : '');
                    catSel.appendChild(o);
                });
                catSel.value = state.category_id || '';
            }

            const locSel = document.querySelector('#expTrendsFilterForm [name="location_id"]');
            if (locSel) {
                const placeholder = locSel.querySelector('option[value=""]');
                locSel.innerHTML = '';
                if (placeholder) locSel.appendChild(placeholder);
                (json.locations || []).forEach(l => {
                    const o = document.createElement('option');
                    o.value = l.id; o.textContent = l.name;
                    locSel.appendChild(o);
                });
                locSel.value = state.location_id || '';
            }

            return json;
        } catch (err) {
            console.error('[exp-trends] meta failed:', err);
            throw err;
        }
    }

    async function loadData() {
        if (inFlight) return;
        inFlight = true;
        if (activeController) activeController.abort();
        const controller = new AbortController();
        activeController = controller;
        showState('loading');

        const params = new URLSearchParams();
        Object.entries(state).forEach(([k, v]) => { if (v !== '' && v != null) params.set(k, v); });

        try {
            const res = await fetch(`${ROUTES.data}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            renderTitles(json.period, json.year);
            renderStats(json.summary, json.currency || CURRENCY);
            renderChart(json.period, json.trend, json.year);
            renderTable(json.period, json.trend, json.moving_averages || {}, json.mom_growth || {});
            renderCategoryMatrix(json.period, json.category_trends || [], json.year);
            renderActiveFilters();
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[exp-trends] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load trends.');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function showState(which) {
        document.getElementById('trendsLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('trendsWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('trendsEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function renderTitles(period, year) {
        const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        const summaryTitle = period === 'monthly'
            ? `{{ __('accounting.monthly_trends_summary') }} ${year}`
            : (period === 'quarterly'
                ? `{{ __('accounting.quarterly_trends_summary') }} ${year}`
                : `{{ __('accounting.yearly_trends_summary') }}`);
        setText('trendsSummaryTitle', summaryTitle);

        const chartTitle = period === 'monthly'
            ? `{{ __('accounting.monthly_expense_trends') }} ${year}`
            : (period === 'quarterly'
                ? `{{ __('accounting.quarterly_expense_trends') }} ${year}`
                : `{{ __('accounting.yearly_expense_trends') }}`);
        setText('trendsChartTitle', chartTitle);

        const tableTitle = period === 'monthly'
            ? `{{ __('accounting.monthly_trend_data') }}`
            : (period === 'quarterly'
                ? `{{ __('accounting.quarterly_trend_data') }}`
                : `{{ __('accounting.yearly_trend_data') }}`);
        setText('trendsTableTitle', tableTitle);

        setText('categoryMatrixTitle', `{{ __('accounting.category_trends') }} ${year}`);
    }

    function renderStats(s, currency) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        const money = (n) => `${currency} ${fmt(n)}`;

        set('statTotalExpenses', money(s.total_expenses || 0));
        set('statExpenseCount',  fmt(s.expense_count || 0));
        set('statAverage',       money(s.average_amount || 0));
        set('statPrevYear',      money(s.previous_year_total || 0));
        set('statVariance',      money(s.variance || 0));

        const growth = s.growth_rate || 0;
        const growthUp = growth >= 0;
        set('statGrowthRate', `${Math.abs(growth).toFixed(1)}% ${growthUp ? '' : ''}`);
        const gCard = document.getElementById('statGrowthCard');
        const gIcon = document.getElementById('statGrowthIcon');
        if (gCard) gCard.className = `card card-flush border border-dashed h-100 bg-light-${growthUp ? 'danger' : 'success'} border-${growthUp ? 'danger' : 'success'}`;
        if (gIcon) gIcon.className = `ki-duotone ki-arrow-${growthUp ? 'up' : 'down'} fs-2tx text-${growthUp ? 'danger' : 'success'}`;

        const varUp = s.variance_direction === 'up';
        const vCard = document.getElementById('statVarianceCard');
        const vIcon = document.getElementById('statVarianceIcon');
        if (vCard) vCard.className = `card card-flush border border-dashed h-100 bg-light-${varUp ? 'danger' : 'success'} border-${varUp ? 'danger' : 'success'}`;
        if (vIcon) vIcon.className = `ki-duotone ki-chart fs-2tx text-${varUp ? 'danger' : 'success'}`;
    }

    function renderChart(period, trend, year) {
        const card = document.getElementById('chartCard');
        if (!card) return;
        if (!trend || !trend.length) { card.style.display = 'none'; return; }
        card.style.display = '';

        const el = document.querySelector('#trendsChart');
        if (!el || typeof ApexCharts === 'undefined') return;
        if (chartInstance) { try { chartInstance.destroy(); } catch(e) {} }

        let opts;

        if (period === 'monthly') {
            const current  = trend.map(r => r.current_year  || 0);
            const previous = trend.map(r => r.previous_year || 0);

            opts = {
                series: [
                    { name: '{{ __('accounting.current_year') }}',  data: current,  color: '#3E97FF' },
                    { name: '{{ __('accounting.previous_year') }}', data: previous, color: '#F1416C' },
                ],
                chart: { type: 'line', height: 400, toolbar: { show: true } },
                stroke: { width: 3, curve: 'smooth' },
                markers: { size: 5 },
                xaxis: { categories: MONTH_SHORT, title: { text: '{{ __('accounting.month') }}' } },
                yaxis: {
                    title: { text: '{{ __('accounting.amount') }} (' + CURRENCY + ')' },
                    labels: { formatter: (v) => CURRENCY + Number(v).toLocaleString() },
                },
                tooltip: { y: { formatter: (v) => CURRENCY + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) } },
                legend: { position: 'top' },
            };
        } else if (period === 'quarterly') {
            opts = {
                series: [{
                    name: '{{ __('accounting.quarterly_total') }}',
                    data: trend.map(r => r.total || 0),
                    color: '#50CD89',
                }],
                chart: { type: 'bar', height: 400, toolbar: { show: true } },
                plotOptions: { bar: { columnWidth: '50%' } },
                xaxis: { categories: ['Q1', 'Q2', 'Q3', 'Q4'], title: { text: '{{ __('accounting.quarter') }}' } },
                yaxis: {
                    title: { text: '{{ __('accounting.amount') }} (' + CURRENCY + ')' },
                    labels: { formatter: (v) => CURRENCY + Number(v).toLocaleString() },
                },
                tooltip: { y: { formatter: (v) => CURRENCY + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) } },
            };
        } else {
            opts = {
                series: [{
                    name: '{{ __('accounting.yearly_total') }}',
                    data: trend.map(r => r.total || 0),
                    color: '#7239EA',
                }],
                chart: { type: 'area', height: 400, toolbar: { show: true } },
                stroke: { width: 3, curve: 'smooth' },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.3, stops: [0, 90, 100] } },
                xaxis: { categories: trend.map(r => r.year), title: { text: '{{ __('accounting.year') }}' } },
                yaxis: {
                    title: { text: '{{ __('accounting.amount') }} (' + CURRENCY + ')' },
                    labels: { formatter: (v) => CURRENCY + Number(v).toLocaleString() },
                },
                tooltip: { y: { formatter: (v) => CURRENCY + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) } },
            };
        }

        chartInstance = new ApexCharts(el, opts);
        chartInstance.render();
    }

    function renderTable(period, trend, movingAverages, momGrowth) {
        const head = document.getElementById('trendsTableHead');
        const body = document.getElementById('trendsTableBody');
        const badge = document.getElementById('trendsCountBadge');
        if (!head || !body) return;

        if (!trend || !trend.length) { showState('empty'); return; }

        let headHtml = '';
        let bodyHtml = '';

        if (period === 'monthly') {
            headHtml = `
                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                    <th class="min-w-150px ps-4">{{ __('accounting.month') }}</th>
                    <th class="min-w-150px">{{ __('accounting.current_year') }}</th>
                    <th class="min-w-150px">{{ __('accounting.previous_year') }}</th>
                    <th class="min-w-150px">{{ __('accounting.growth') }}</th>
                    <th class="min-w-150px">{{ __('accounting.expense_count') }}</th>
                    <th class="min-w-150px">{{ __('accounting.moving_average') }}</th>
                    <th class="min-w-150px">{{ __('accounting.month_over_month') }}</th>
                </tr>`;

            bodyHtml = trend.map(r => {
                const growthUp = (r.growth || 0) >= 0;
                const mom = momGrowth[r.month];
                const momUp = mom !== undefined ? mom >= 0 : false;
                const mov = movingAverages[r.month];

                return `
                    <tr>
                        <td class="ps-4 fw-semibold">${esc(r.month_name)}</td>
                        <td><span class="fw-bold text-success">${esc(CURRENCY)} ${fmt(r.current_year)}</span></td>
                        <td><span class="fw-semibold text-gray-600">${esc(CURRENCY)} ${fmt(r.previous_year)}</span></td>
                        <td>
                            <span class="fw-bold text-${growthUp ? 'danger' : 'success'}">
                                <i class="ki-duotone ki-arrow-${growthUp ? 'up' : 'down'} fs-3 me-1"></i>
                                ${Math.abs(r.growth || 0).toFixed(1)}%
                            </span>
                        </td>
                        <td><span class="badge badge-light-info">${r.expense_count}</span></td>
                        <td>${mov !== undefined ? `<span class="fw-semibold text-primary">${esc(CURRENCY)} ${fmt(mov)}</span>` : '<span class="text-muted">—</span>'}</td>
                        <td>
                            ${mom !== undefined
                                ? `<span class="fw-bold text-${momUp ? 'danger' : 'success'}">
                                     <i class="ki-duotone ki-arrow-${momUp ? 'up' : 'down'} fs-3 me-1"></i>
                                     ${Math.abs(mom).toFixed(1)}%
                                   </span>`
                                : '<span class="text-muted">—</span>'}
                        </td>
                    </tr>`;
            }).join('');
        } else if (period === 'quarterly') {
            headHtml = `
                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                    <th class="min-w-150px ps-4">{{ __('accounting.quarter') }}</th>
                    <th class="min-w-150px">{{ __('accounting.period') }}</th>
                    <th class="min-w-150px">{{ __('accounting.total_amount') }}</th>
                    <th class="min-w-150px">{{ __('accounting.quarterly_growth') }}</th>
                </tr>`;

            bodyHtml = trend.map((r, i) => {
                const prev = trend[i - 1];
                const growth = prev && prev.total > 0 ? ((r.total - prev.total) / prev.total) * 100 : 0;
                const growthUp = growth >= 0;
                const startM = MONTH_NAMES[r.start_month - 1];
                const endM   = MONTH_NAMES[r.end_month - 1];

                return `
                    <tr>
                        <td class="ps-4 fw-semibold">${esc(r.quarter_name)}</td>
                        <td>${esc(startM)} - ${esc(endM)}</td>
                        <td><span class="fw-bold text-success">${esc(CURRENCY)} ${fmt(r.total)}</span></td>
                        <td>
                            <span class="fw-bold text-${growthUp ? 'danger' : 'success'}">
                                <i class="ki-duotone ki-arrow-${growthUp ? 'up' : 'down'} fs-3 me-1"></i>
                                ${Math.abs(growth).toFixed(1)}%
                            </span>
                        </td>
                    </tr>`;
            }).join('');
        } else {
            headHtml = `
                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                    <th class="min-w-150px ps-4">{{ __('accounting.year') }}</th>
                    <th class="min-w-150px">{{ __('accounting.total_amount') }}</th>
                    <th class="min-w-150px">{{ __('accounting.expense_count') }}</th>
                    <th class="min-w-150px">{{ __('accounting.average_amount') }}</th>
                    <th class="min-w-150px">{{ __('accounting.yearly_growth') }}</th>
                </tr>`;

            let prevTotal = null;
            bodyHtml = trend.map(r => {
                const growth = prevTotal && prevTotal > 0 ? ((r.total - prevTotal) / prevTotal) * 100 : 0;
                prevTotal = r.total;
                const growthUp = growth >= 0;

                return `
                    <tr>
                        <td class="ps-4 fw-semibold">${esc(r.year)}</td>
                        <td><span class="fw-bold text-success">${esc(CURRENCY)} ${fmt(r.total)}</span></td>
                        <td><span class="badge badge-light-info">${r.expense_count}</span></td>
                        <td><span class="fw-semibold text-gray-600">${esc(CURRENCY)} ${fmt(r.average)}</span></td>
                        <td>
                            <span class="fw-bold text-${growthUp ? 'danger' : 'success'}">
                                <i class="ki-duotone ki-arrow-${growthUp ? 'up' : 'down'} fs-3 me-1"></i>
                                ${Math.abs(growth).toFixed(1)}%
                            </span>
                        </td>
                    </tr>`;
            }).join('');
        }

        head.innerHTML = headHtml;
        body.innerHTML = bodyHtml;
        if (badge) badge.textContent = `${trend.length} {{ __('accounting.periods') }}`;
        showState('table');
    }

    function renderCategoryMatrix(period, categories, year) {
        const card = document.getElementById('categoryMatrixCard');
        const head = document.getElementById('categoryMatrixHead');
        const body = document.getElementById('categoryMatrixBody');
        if (!card || !head || !body) return;

        if (period !== 'monthly' || !categories.length) {
            card.style.display = 'none';
            return;
        }
        card.style.display = '';

        const headerCols = MONTH_SHORT.map(m => `<th class="text-center">${m}</th>`).join('');

        head.innerHTML = `
            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                <th class="ps-4">{{ __('accounting.category') }}</th>
                ${headerCols}
                <th class="text-end">{{ __('accounting.total') }}</th>
            </tr>`;

        body.innerHTML = categories.map(c => {
            const cells = Array.from({ length: 12 }, (_, i) => {
                const amount = (c.monthly_data && c.monthly_data[i + 1]) || 0;
                return `<td class="text-center">${amount > 0
                    ? `<span class="fw-semibold text-gray-700">${esc(CURRENCY)} ${Number(amount).toLocaleString()}</span>`
                    : '<span class="text-muted">-</span>'}</td>`;
            }).join('');

            return `
                <tr>
                    <td class="ps-4 fw-semibold"><span class="badge badge-light-primary">${esc(c.category_name)}</span></td>
                    ${cells}
                    <td class="text-end fw-bold text-success">${esc(CURRENCY)} ${fmt(c.total)}</td>
                </tr>`;
        }).join('');
    }

    function renderActiveFilters() {
        const bar = document.getElementById('activeFiltersBar');
        const list = document.getElementById('activeFiltersList');
        if (!bar || !list) return;

        const meta = metaCache || {};
        const chips = [];

        if (state.period !== 'monthly') {
            chips.push(['{{ __('accounting.period') }}', state.period]);
        }
        if (state.year && state.period !== 'yearly' && state.year !== String(new Date().getFullYear())) {
            chips.push(['{{ __('accounting.year') }}', state.year]);
        }
        if (state.category_id) {
            const c = (meta.categories || []).find(x => String(x.id) === String(state.category_id));
            if (c) chips.push(['{{ __('accounting.category') }}', c.name]);
        }
        if (state.location_id) {
            const l = (meta.locations || []).find(x => String(x.id) === String(state.location_id));
            if (l) chips.push(['{{ __('accounting.location') }}', l.name]);
        }

        if (!chips.length) { bar.classList.add('d-none'); return; }

        bar.classList.remove('d-none');
        list.innerHTML = `
            <span class="text-muted fw-semibold me-2"><i class="ki-duotone ki-filter fs-5 me-1"></i>{{ __('accounting.active_filters') }}:</span>
            ${chips.map(([l, v]) => `<span class="badge badge-light-primary fs-7"><strong>${esc(l)}:</strong>&nbsp;${esc(v)}</span>`).join('')}
            <button type="button" id="clearAllFilters" class="btn btn-sm btn-link text-danger fs-7 p-0 ms-2">
                <i class="ki-duotone ki-cross fs-5 me-1"></i>{{ __('accounting.clear_all') }}
            </button>`;
        document.getElementById('clearAllFilters')?.addEventListener('click', () => {
            document.getElementById('expTrendsClear')?.click();
        });
    }

    function fmt(n) {
        return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.ExpenseTrends = { reload: loadData };
    return window.ExpenseTrends;
})();
</script>