<script>
window.ExpenseBudgetVsActual = (function () {
    'use strict';

    const ROUTES = window.EXP_BVA_ROUTES;
    const BOOT   = window.EXP_BVA_BOOTSTRAP || {};
    let CURRENCY = '';
    let metaCache = null;
    let activeController = null;
    let inFlight = false;
    let chartInstance = null;

    const state = {
        year:        BOOT.year        || String(new Date().getFullYear()),
        month:       BOOT.month       || String(new Date().getMonth() + 1),
        location_id: BOOT.location_id || '',
    };

    document.addEventListener('DOMContentLoaded', () => {
        seedForm();
        bind();
        loadMeta().then(() => loadData());
    });

    function bind() {
        const form = document.getElementById('expBvAFilterForm');
        form?.addEventListener('submit', (e) => {
            e.preventDefault();
            readForm();
            loadData();
        });

        document.getElementById('expBvAClear')?.addEventListener('click', () => {
            state.year        = String(new Date().getFullYear());
            state.month       = String(new Date().getMonth() + 1);
            state.location_id = '';
            seedForm();
            loadData();
        });

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

    function seedForm() {
        const form = document.getElementById('expBvAFilterForm');
        if (!form) return;
        Object.entries(state).forEach(([k, v]) => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) el.value = v == null ? '' : String(v);
        });
    }
    function readForm() {
        const form = document.getElementById('expBvAFilterForm');
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

            // Years
            const yearSel = document.querySelector('#expBvAFilterForm [name="year"]');
            if (yearSel) {
                yearSel.innerHTML = '';
                (json.years || []).forEach(y => {
                    const o = document.createElement('option');
                    o.value = y;
                    o.textContent = y;
                    yearSel.appendChild(o);
                });
                yearSel.value = state.year;
            }

            // Months
            const monthSel = document.querySelector('#expBvAFilterForm [name="month"]');
            if (monthSel) {
                monthSel.innerHTML = '';
                Object.entries(json.months || {}).forEach(([k, v]) => {
                    const o = document.createElement('option');
                    o.value = k;
                    o.textContent = v;
                    monthSel.appendChild(o);
                });
                monthSel.value = state.month;
            }

            // Locations
            const locSel = document.querySelector('#expBvAFilterForm [name="location_id"]');
            if (locSel) {
                const placeholder = locSel.querySelector('option[value=""]');
                locSel.innerHTML = '';
                if (placeholder) locSel.appendChild(placeholder);
                (json.locations || []).forEach(l => {
                    const o = document.createElement('option');
                    o.value = l.id;
                    o.textContent = l.name;
                    locSel.appendChild(o);
                });
                locSel.value = state.location_id || '';
            }

            return json;
        } catch (err) {
            console.error('[exp-bva] meta failed:', err);
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

            renderStats(json.summary, json.currency || CURRENCY);
            renderTable(json.budget || [], json.summary);
            renderChart(json.chart || []);
            renderTop(json.top || []);
            renderActiveFilters(json);
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[exp-bva] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load budget vs actual.');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function showState(which) {
        document.getElementById('bvALoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('bvAWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('bvAEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function renderStats(s, currency) {
        const money = (n) => `${currency} ${fmt(n)}`;
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

        set('statTotalBudgetMonthly', money(s.total_budget_monthly || 0));
        set('statTotalBudgetAnnual',  money(s.total_budget_annual  || 0));
        set('statTotalActualMonthly', money(s.total_actual_monthly || 0));
        set('statTotalActualAnnual',  money(s.total_actual_annual  || 0));

        // Variance card — dynamic color
        const varM = s.total_variance_monthly || 0;
        const varA = s.total_variance_annual  || 0;
        const varPositive = varM >= 0;

        set('statVarianceMonthly', `${money(Math.abs(varM))} ${varPositive ? '{{ __('accounting.under_budget') }}' : '{{ __('accounting.over_budget') }}'}`);
        set('statVarianceAnnual',  `${money(Math.abs(varA))} ${varA >= 0 ? '{{ __('accounting.under_budget') }}' : '{{ __('accounting.over_budget') }}'}`);

        const vCard = document.getElementById('statVarianceCard');
        const vIcon = document.getElementById('statVarianceIcon');
        if (vCard) vCard.className = `card card-flush border border-dashed h-100 bg-light-${varPositive ? 'success' : 'danger'} border-${varPositive ? 'success' : 'danger'}`;
        if (vIcon) vIcon.className = `ki-duotone ki-arrow-${varPositive ? 'down' : 'up'} fs-2tx text-${varPositive ? 'success' : 'danger'}`;

        // Percentage card
        const pctM = s.variance_pct_monthly || 0;
        const pctA = s.variance_pct_annual  || 0;
        const pctPositive = pctM >= 0;

        set('statPctMonthly', `${Math.abs(pctM).toFixed(1)}% ${pctPositive ? '{{ __('accounting.under') }}' : '{{ __('accounting.over') }}'}`);
        set('statPctAnnual',  `${Math.abs(pctA).toFixed(1)}% ${pctA >= 0 ? '{{ __('accounting.under') }}' : '{{ __('accounting.over') }}'}`);

        const pCard = document.getElementById('statPctCard');
        const pIcon = document.getElementById('statPctIcon');
        if (pCard) pCard.className = `card card-flush border border-dashed h-100 bg-light-${pctPositive ? 'success' : 'danger'} border-${pctPositive ? 'success' : 'danger'}`;
        if (pIcon) pIcon.className = `ki-duotone ki-percentage fs-2tx text-${pctPositive ? 'success' : 'danger'}`;

        set('statUnderBudget', fmt(s.under_budget_count || 0));
        set('statOverBudget',  fmt(s.over_budget_count  || 0));
    }

    function renderTable(rows, summary) {
        const tbody = document.getElementById('bvABody');
        const tfoot = document.getElementById('bvAFoot');
        const badge = document.getElementById('budgetCountBadge');
        if (!tbody) return;

        if (!rows.length) { showState('empty'); return; }

        tbody.innerHTML = rows.map(r => {
            const varM = r.variance_monthly || 0;
            const varPctM = r.variance_pct_monthly || 0;
            const isOver = varM < 0;
            const statusColor = varM > 0 ? 'success' : (varM < 0 ? 'danger' : 'warning');
            const statusText = varM > 0 ? '{{ __('accounting.under_budget') }}' : (varM < 0 ? '{{ __('accounting.over_budget') }}' : '{{ __('accounting.on_budget') }}');
            const progressPct = r.progress_pct || 0;
            const barColor = progressPct > 100 ? 'danger' : (progressPct > 80 ? 'warning' : 'success');

            return `
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-40px symbol-circle me-3">
                                <div class="symbol-label bg-light-info"><i class="ki-duotone ki-category fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i></div>
                            </div>
                            <div class="d-flex justify-content-start flex-column">
                                <span class="text-gray-800 fw-bold">${esc(r.category_name)}</span>
                                ${r.category_code ? `<small class="text-muted">{{ __('accounting.code') }}: ${esc(r.category_code)}</small>` : ''}
                                ${r.category_description ? `<small class="text-muted">${esc((r.category_description || '').slice(0, 50))}</small>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="text-primary fw-bold">${esc(CURRENCY)} ${fmt(r.budget_monthly)}</span>
                        <div class="mt-1"><small class="text-gray-600">{{ __('accounting.annual') }}: ${esc(CURRENCY)} ${fmt(r.budget_annual)}</small></div>
                    </td>
                    <td>
                        <span class="text-info fw-bold">${esc(CURRENCY)} ${fmt(r.actual_monthly)}</span>
                        <div class="mt-1"><small class="text-gray-600">{{ __('accounting.annual') }}: ${esc(CURRENCY)} ${fmt(r.actual_annual)}</small></div>
                    </td>
                    <td>
                        <span class="fw-bold text-${statusColor}">
                            ${esc(CURRENCY)} ${fmt(Math.abs(varM))}
                            ${varM > 0 ? '<i class="ki-duotone ki-arrow-down text-success fs-4 ms-1"></i>' : (varM < 0 ? '<i class="ki-duotone ki-arrow-up text-danger fs-4 ms-1"></i>' : '')}
                        </span>
                        <div class="mt-1"><small class="text-gray-600">{{ __('accounting.annual') }}: ${esc(CURRENCY)} ${fmt(Math.abs(r.variance_annual))}</small></div>
                    </td>
                    <td>
                        <span class="fw-bold text-${statusColor}">${Math.abs(varPctM).toFixed(1)}%</span>
                        <div class="mt-1"><small class="text-gray-600">{{ __('accounting.annual') }}: ${Math.abs(r.variance_pct_annual).toFixed(1)}%</small></div>
                    </td>
                    <td>
                        <span class="badge badge-light-${statusColor}">
                            ${statusText}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:10px;">
                                <div class="progress-bar bg-${barColor}" style="width:${Math.min(progressPct, 100)}%"></div>
                            </div>
                            <span class="fw-bold text-gray-700 min-w-60px text-end">${progressPct.toFixed(1)}%</span>
                        </div>
                        <div class="mt-1 text-center"><small class="text-muted">{{ __('accounting.utilization') }}</small></div>
                    </td>
                </tr>`;
        }).join('');

        // Footer
        const totalBudgetMonthly = summary.total_budget_monthly || 0;
        const totalActualMonthly = summary.total_actual_monthly || 0;
        const totalVarM = summary.total_variance_monthly || 0;
        const totalPctM = summary.variance_pct_monthly || 0;
        const totalProgress = totalBudgetMonthly > 0 ? Math.min((totalActualMonthly / totalBudgetMonthly) * 100, 100) : 0;
        const totalBarColor = totalProgress > 100 ? 'danger' : (totalProgress > 80 ? 'warning' : 'success');

        if (tfoot) tfoot.innerHTML = `
            <tr>
                <td class="ps-4">{{ __('accounting.total') }}</td>
                <td class="text-primary">${esc(CURRENCY)} ${fmt(totalBudgetMonthly)}</td>
                <td class="text-info">${esc(CURRENCY)} ${fmt(totalActualMonthly)}</td>
                <td class="text-${totalVarM >= 0 ? 'success' : 'danger'}">${esc(CURRENCY)} ${fmt(Math.abs(totalVarM))}</td>
                <td class="text-${totalPctM >= 0 ? 'success' : 'danger'}">${Math.abs(totalPctM).toFixed(1)}%</td>
                <td>
                    <span class="badge badge-light-${totalVarM >= 0 ? 'success' : 'danger'}">${totalVarM >= 0 ? '{{ __('accounting.under_budget') }}' : '{{ __('accounting.over_budget') }}'}</span>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="progress w-100 me-3" style="height:10px;">
                            <div class="progress-bar bg-${totalBarColor}" style="width:${totalProgress}%"></div>
                        </div>
                        <span class="fw-bold text-gray-700 min-w-60px text-end">${totalProgress.toFixed(1)}%</span>
                    </div>
                </td>
            </tr>`;

        if (badge) badge.textContent = `${rows.length} {{ __('accounting.categories') }}`;
        showState('table');
    }

    function renderChart(series) {
        const card = document.getElementById('chartCard');
        if (!card) return;
        if (!series || !series.length) { card.style.display = 'none'; return; }
        card.style.display = '';

        const el = document.querySelector('#budgetTrendsChart');
        if (!el || typeof ApexCharts === 'undefined') return;
        if (chartInstance) { try { chartInstance.destroy(); } catch(e) {} }

        const colors = ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C'];
        const allSeries = [];

        series.forEach((cat, i) => {
            allSeries.push({
                name: `${cat.name} — {{ __('accounting.budget') }}`,
                data: cat.budget,
                type: 'line',
                color: colors[i % colors.length],
            });
            allSeries.push({
                name: `${cat.name} — {{ __('accounting.actual') }}`,
                data: cat.actual,
                type: 'line',
                color: colors[i % colors.length] + '80',
            });
        });

        const opts = {
            series: allSeries,
            chart: { type: 'line', height: 400, toolbar: { show: true } },
            stroke: {
                width: 3,
                curve: 'smooth',
                dashArray: allSeries.map((_, i) => i % 2 === 0 ? 0 : 5), // solid for budget, dashed for actual
            },
            colors: allSeries.map(s => s.color),
            xaxis: {
                categories: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
                labels: { style: { fontSize: '12px' } },
            },
            yaxis: {
                title: { text: '{{ __('accounting.amount') }} (' + CURRENCY + ')' },
                labels: { formatter: (v) => CURRENCY + Number(v).toLocaleString() },
            },
            tooltip: {
                y: { formatter: (v) => CURRENCY + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
            },
            legend: { position: 'top', horizontalAlign: 'center', fontSize: '12px' },
            markers: { size: 4 },
            grid: { borderColor: '#f1f1f1' },
            dataLabels: { enabled: false },
        };

        chartInstance = new ApexCharts(el, opts);
        chartInstance.render();
    }

    function renderTop(rows) {
        const card = document.getElementById('topCard');
        const tbody = document.getElementById('topBody');
        if (!card || !tbody) return;
        if (!rows.length) { card.style.display = 'none'; return; }
        card.style.display = '';

        tbody.innerHTML = rows.map(r => {
            const varM = r.variance_monthly || 0;
            const varPctM = r.variance_pct_monthly || 0;
            const progressPct = r.progress_pct || 0;
            const barColor = progressPct > 100 ? 'danger' : (progressPct > 80 ? 'warning' : 'success');
            const statusColor = varM > 0 ? 'success' : (varM < 0 ? 'danger' : 'warning');

            let trendBadge, recoText;
            if (varPctM > 10) {
                trendBadge = `<span class="badge badge-light-success"><i class="ki-duotone ki-arrow-down fs-4 me-1"></i>{{ __('accounting.under_spending') }}</span>`;
                recoText   = '<span class="text-success fw-semibold">{{ __('accounting.budget_surplus') }}</span>';
            } else if (varPctM < -10) {
                trendBadge = `<span class="badge badge-light-danger"><i class="ki-duotone ki-arrow-up fs-4 me-1"></i>{{ __('accounting.over_spending') }}</span>`;
                recoText   = '<span class="text-danger fw-semibold">{{ __('accounting.reduce_spending') }}</span>';
            } else {
                trendBadge = `<span class="badge badge-light-warning"><i class="ki-duotone ki-check fs-4 me-1"></i>{{ __('accounting.on_track') }}</span>`;
                recoText   = '<span class="text-info fw-semibold">{{ __('accounting.maintain_current') }}</span>';
            }

            return `
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-30px symbol-circle me-3">
                                <div class="symbol-label bg-light-info"><i class="ki-duotone ki-category fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i></div>
                            </div>
                            <span class="text-gray-800 fw-bold">${esc(r.category_name)}</span>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:8px;">
                                <div class="progress-bar bg-${barColor}" style="width:${Math.min(progressPct, 100)}%"></div>
                            </div>
                            <span class="fw-bold text-gray-700">${progressPct.toFixed(1)}%</span>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-light-${statusColor}">${Math.abs(varPctM).toFixed(1)}% ${varM > 0 ? '{{ __('accounting.under') }}' : '{{ __('accounting.over') }}'}</span>
                    </td>
                    <td>${trendBadge}</td>
                    <td>${recoText}</td>
                </tr>`;
        }).join('');
    }

    function renderActiveFilters(json) {
        const bar = document.getElementById('activeFiltersBar');
        const list = document.getElementById('activeFiltersList');
        if (!bar || !list) return;

        const meta = metaCache || {};
        const chips = [];

        const defaultYear  = String(new Date().getFullYear());
        const defaultMonth = String(new Date().getMonth() + 1);

        if (state.year && state.year !== defaultYear) {
            chips.push(['{{ __('accounting.year') }}', state.year]);
        }
        if (state.month && state.month !== defaultMonth) {
            chips.push(['{{ __('accounting.month') }}', meta.months?.[state.month] || state.month]);
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
            document.getElementById('expBvAClear')?.click();
        });
    }

    function fmt(n) {
        return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.ExpenseBudgetVsActual = { reload: loadData };
    return window.ExpenseBudgetVsActual;
})();
</script>