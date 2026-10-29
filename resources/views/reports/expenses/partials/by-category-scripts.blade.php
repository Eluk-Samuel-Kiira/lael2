<script>
window.ExpenseByCategory = (function () {
    'use strict';

    const ROUTES = window.EXP_CAT_ROUTES;
    const BOOT   = window.EXP_CAT_BOOTSTRAP || {};
    let CURRENCY = '';
    let metaCache = null;
    let activeController = null;
    let inFlight = false;
    let chartInstance = null;

    const state = {
        start_date:  BOOT.start_date  || '',
        end_date:    BOOT.end_date    || '',
        category_id: BOOT.category_id || '',
        location_id: BOOT.location_id || '',
    };

    document.addEventListener('DOMContentLoaded', () => {
        seedForm();
        bind();
        loadMeta().then(() => loadData());
    });

    function bind() {
        const form = document.getElementById('expCatFilterForm');
        form?.addEventListener('submit', (e) => {
            e.preventDefault();
            readForm();
            loadData();
        });

        document.getElementById('expCatClear')?.addEventListener('click', () => {
            state.start_date  = BOOT.start_date;
            state.end_date    = BOOT.end_date;
            state.category_id = '';
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
        const form = document.getElementById('expCatFilterForm');
        if (!form) return;
        Object.entries(state).forEach(([k, v]) => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) el.value = v == null ? '' : String(v);
        });
    }

    function readForm() {
        const form = document.getElementById('expCatFilterForm');
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
            fillSelect('category_id', json.categories, 'id', c => c.name + (c.code ? ' (' + c.code + ')' : ''));
            fillSelect('location_id', json.locations, 'id', 'name');
            return json;
        } catch (err) {
            console.error('[exp-by-cat] meta failed:', err);
            throw err;
        }
    }

    function fillSelect(name, rows, valKey, labelFnOrKey) {
        const sel = document.querySelector(`#expCatFilterForm [name="${name}"]`);
        if (!sel || !Array.isArray(rows)) return;
        const label = typeof labelFnOrKey === 'function' ? labelFnOrKey : (row) => row[labelFnOrKey];
        const placeholder = sel.querySelector('option[value=""]');
        sel.innerHTML = '';
        if (placeholder) sel.appendChild(placeholder);
        rows.forEach(row => {
            const o = document.createElement('option');
            o.value = row[valKey];
            o.textContent = label(row);
            sel.appendChild(o);
        });
        sel.value = state[name] || '';
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
            renderTable(json.categories || [], json.summary.grand_total || 0);
            renderChart(json.chart);
            renderMatrix(json.chart);
            renderDateBadge(json.meta.start_date, json.meta.end_date);
            renderActiveFilters();
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[exp-by-cat] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load category expenses.');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function showState(which) {
        document.getElementById('catLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('catWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('catEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function renderStats(s, currency) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        const money = (n) => `${currency} ${fmt(n)}`;
        set('statCategoryCount', fmt(s.category_count || 0));
        set('statTotalExpenses', fmt(s.total_expenses || 0));
        set('statGrandTotal',    money(s.grand_total || 0));
        set('statTotalTax',      money(s.total_tax || 0));
        set('statAvg',           money(s.avg_amount || 0));
        set('statTopCategory',   s.top_category || '—');
    }

    function renderTable(rows, total) {
        const tbody = document.getElementById('catBody');
        const badge = document.getElementById('catCountBadge');
        if (!tbody) return;

        if (!rows.length) { showState('empty'); return; }

        const palette = ['primary', 'success', 'info', 'warning', 'danger'];

        tbody.innerHTML = rows.map((c, i) => {
            const pct = total > 0 ? (c.grand_total / total) * 100 : 0;
            const color = palette[i % palette.length];
            const medalBadge = i < 3
                ? `<div class="mt-1"><span class="badge badge-light-${i === 0 ? 'danger' : (i === 1 ? 'warning' : 'info')}">{{ __('accounting.top') }} ${i + 1}</span></div>`
                : '';
            return `
                <tr>
                    <td class="ps-4"><span class="fw-bold text-gray-800">${i + 1}</span>${medalBadge}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-40px symbol-circle me-3">
                                <div class="symbol-label bg-light-${color}">
                                    <i class="ki-duotone ki-category fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                </div>
                            </div>
                            <div class="d-flex justify-content-start flex-column">
                                <span class="text-gray-800 fw-bold">${esc(c.category_name)}</span>
                                ${c.category_code && c.category_code !== 'N/A'
                                    ? `<small class="text-muted">{{ __('accounting.code') }}: ${esc(c.category_code)}</small>`
                                    : ''}
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-light-primary">${c.expense_count}</span></td>
                    <td><span class="text-gray-800 fw-semibold">${esc(CURRENCY)} ${fmt(c.total_amount)}</span></td>
                    <td><span class="text-info">${esc(CURRENCY)} ${fmt(c.total_tax)}</span></td>
                    <td><span class="fw-bold text-success">${esc(CURRENCY)} ${fmt(c.grand_total)}</span></td>
                    <td><span class="text-gray-600">${esc(CURRENCY)} ${fmt(c.average_amount)}</span></td>
                    <td><span class="text-danger">${esc(CURRENCY)} ${fmt(c.max_amount)}</span></td>
                    <td><span class="text-secondary">${esc(CURRENCY)} ${fmt(c.min_amount)}</span></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:8px;">
                                <div class="progress-bar bg-${color}" style="width:${Math.min(pct, 100)}%"></div>
                            </div>
                            <span class="fw-bold text-gray-700 min-w-60px text-end">${pct.toFixed(1)}%</span>
                        </div>
                    </td>
                </tr>`;
        }).join('');

        if (badge) badge.textContent = `${rows.length} {{ __('accounting.categories') }}`;
        showState('table');
    }

    function renderChart(chart) {
        const card = document.getElementById('chartCard');
        if (!card) return;
        const hasData = chart && chart.category_names && chart.category_names.length > 0
                        && chart.months && chart.months.length > 0;
        if (!hasData) { card.style.display = 'none'; return; }
        card.style.display = '';

        const el = document.querySelector('#monthlyTrendChart');
        if (!el || typeof ApexCharts === 'undefined') return;

        // Destroy previous chart
        if (chartInstance) { try { chartInstance.destroy(); } catch(e) {} }

        const series = chart.category_names.map(cat => ({
            name: cat,
            type: 'line',
            data: chart.months.map(m => {
                const key = m.key;
                return (chart.matrix[key] && chart.matrix[key][cat]) || 0;
            }),
        }));

        const opts = {
            series,
            chart: {
                type: 'line',
                height: 400,
                toolbar: { show: true },
                zoom: { enabled: true },
            },
            stroke: { width: 3, curve: 'smooth' },
            colors: ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C', '#14B8A6', '#F97316', '#8B5CF6'],
            xaxis: {
                categories: chart.months.map(m => m.label),
                labels: { rotate: -45, style: { fontSize: '11px' } },
            },
            yaxis: {
                title: { text: '{{ __('accounting.amount') }} (' + CURRENCY + ')' },
                labels: { formatter: (val) => CURRENCY + Number(val).toLocaleString() },
            },
            tooltip: {
                y: {
                    formatter: (val) => CURRENCY + Number(val).toLocaleString(undefined, {
                        minimumFractionDigits: 2, maximumFractionDigits: 2,
                    }),
                },
            },
            legend: { position: 'top', horizontalAlign: 'center', fontSize: '12px' },
            markers: { size: 4 },
            grid: { borderColor: '#f1f1f1' },
        };

        chartInstance = new ApexCharts(el, opts);
        chartInstance.render();
    }

    function renderMatrix(chart) {
        const card = document.getElementById('matrixCard');
        const head = document.getElementById('matrixHead');
        const body = document.getElementById('matrixBody');
        const foot = document.getElementById('matrixFoot');
        if (!card || !head || !body || !foot) return;

        const cats = (chart && chart.category_names) || [];
        const months = (chart && chart.months) || [];
        if (!cats.length || !months.length) { card.style.display = 'none'; return; }
        card.style.display = '';

        head.innerHTML = `
            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                <th class="ps-4">{{ __('accounting.month_year') }}</th>
                ${cats.map(c => `<th class="text-end">${esc(c)}</th>`).join('')}
            </tr>`;

        body.innerHTML = months.map(m => {
            const cells = cats.map(c => {
                const v = (chart.matrix[m.key] && chart.matrix[m.key][c]) || 0;
                return `<td class="text-end"><span class="text-gray-800 fw-semibold">${esc(CURRENCY)} ${fmt(v)}</span></td>`;
            }).join('');
            return `<tr><td class="ps-4 fw-semibold">${esc(m.label)}</td>${cells}</tr>`;
        }).join('');

        foot.innerHTML = `
            <tr class="fw-bold text-gray-800 bg-light">
                <td class="ps-4">{{ __('accounting.total') }}</td>
                ${cats.map(c => {
                    const total = chart.totals[c] || 0;
                    return `<td class="text-end"><span class="text-success fw-bold">${esc(CURRENCY)} ${fmt(total)}</span></td>`;
                }).join('')}
            </tr>`;
    }

    function renderDateBadge(start, end) {
        const fmtDate = (s) => {
            if (!s) return '—';
            const [y, m, d] = s.split('-');
            const names = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return `${d} ${names[(+m) - 1]} ${y}`;
        };
        const el = document.getElementById('expDateBadgeText');
        if (el) el.textContent = `${fmtDate(start)} — ${fmtDate(end)}`;
    }

    function renderActiveFilters() {
        const bar = document.getElementById('activeFiltersBar');
        const list = document.getElementById('activeFiltersList');
        if (!bar || !list) return;

        const meta = metaCache || {};
        const chips = [];

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
            <span class="text-muted fw-semibold me-2">
                <i class="ki-duotone ki-filter fs-5 me-1"></i>{{ __('accounting.active_filters') }}:
            </span>
            ${chips.map(([l, v]) => `<span class="badge badge-light-primary fs-7"><strong>${esc(l)}:</strong>&nbsp;${esc(v)}</span>`).join('')}
            <button type="button" id="clearAllFilters" class="btn btn-sm btn-link text-danger fs-7 p-0 ms-2">
                <i class="ki-duotone ki-cross fs-5 me-1"></i>{{ __('accounting.clear_all') }}
            </button>`;
        document.getElementById('clearAllFilters')?.addEventListener('click', () => {
            document.getElementById('expCatClear')?.click();
        });
    }

    function fmt(n) {
        return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.ExpenseByCategory = { reload: loadData };
    return window.ExpenseByCategory;
})();
</script>