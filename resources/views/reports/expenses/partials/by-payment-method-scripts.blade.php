<script>
window.ExpenseByPaymentMethod = (function () {
    'use strict';

    const ROUTES = window.EXP_PM_ROUTES;
    const BOOT   = window.EXP_PM_BOOTSTRAP || {};
    let CURRENCY = '';
    let metaCache = null;
    let activeController = null;
    let inFlight = false;
    let chartInstance = null;

    const state = {
        start_date:        BOOT.start_date        || '',
        end_date:          BOOT.end_date          || '',
        payment_method_id: BOOT.payment_method_id || '',
        location_id:       BOOT.location_id       || '',
    };

    document.addEventListener('DOMContentLoaded', () => {
        seedForm();
        bind();
        loadMeta().then(() => loadData());
    });

    function bind() {
        const form = document.getElementById('expPmFilterForm');
        form?.addEventListener('submit', (e) => {
            e.preventDefault();
            readForm();
            loadData();
        });

        document.getElementById('expPmClear')?.addEventListener('click', () => {
            state.start_date        = BOOT.start_date;
            state.end_date          = BOOT.end_date;
            state.payment_method_id = '';
            state.location_id       = '';
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
        const form = document.getElementById('expPmFilterForm');
        if (!form) return;
        Object.entries(state).forEach(([k, v]) => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) el.value = v == null ? '' : String(v);
        });
    }
    function readForm() {
        const form = document.getElementById('expPmFilterForm');
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
            fillSelect('payment_method_id', json.payment_methods, 'id', m => m.name + (m.is_default ? ' (default)' : ''));
            fillSelect('location_id', json.locations, 'id', 'name');
            return json;
        } catch (err) {
            console.error('[exp-by-pm] meta failed:', err);
            throw err;
        }
    }

    function fillSelect(name, rows, valKey, labelFnOrKey) {
        const sel = document.querySelector(`#expPmFilterForm [name="${name}"]`);
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
            renderTable(json.methods || [], json.summary.total_amount || 0);
            renderChart(json.chart);
            renderCategoryCards(json.method_by_category || {});
            renderDateBadge(json.meta.start_date, json.meta.end_date);
            renderActiveFilters();
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[exp-by-pm] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load payment method expenses.');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function showState(which) {
        document.getElementById('pmLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('pmWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('pmEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function renderStats(s, currency) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        const money = (n) => `${currency} ${fmt(n)}`;
        set('statMethodCount',       fmt(s.total_methods || 0));
        set('statTotalTransactions', fmt(s.total_transactions || 0));
        set('statGrandTotal',        money(s.total_amount || 0));
        set('statTotalTax',          money(s.total_tax || 0));
        set('statAvgTransaction',    money(s.avg_transaction || 0));
        set('statTopMethod',         s.most_used_method || '—');
    }

    const METHOD_TYPE_COLORS = {
        cash:            'success',
        card:            'primary',
        bank_transfer:   'info',
        bank_account:    'info',
        digital_wallet:  'warning',
        mobile_money:    'warning',
        check:           'secondary',
        other:           'dark',
    };

    function renderTable(rows, totalAmount) {
        const tbody = document.getElementById('pmBody');
        const badge = document.getElementById('pmCountBadge');
        if (!tbody) return;

        if (!rows.length) { showState('empty'); return; }

        const palette = ['primary', 'success', 'info', 'warning', 'danger'];

        tbody.innerHTML = rows.map((m, i) => {
            const pct = totalAmount > 0 ? (m.grand_total / totalAmount) * 100 : 0;
            const typeColor = METHOD_TYPE_COLORS[m.method_type] || 'secondary';
            const barColor = palette[i % palette.length];
            const medal = i < 3
                ? `<div class="mt-1"><span class="badge badge-light-${i === 0 ? 'danger' : (i === 1 ? 'warning' : 'info')}">{{ __('accounting.top') }} ${i + 1}</span></div>`
                : '';

            return `
                <tr>
                    <td class="ps-4"><span class="fw-bold text-gray-800">${i + 1}</span>${medal}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-40px symbol-circle me-3">
                                <div class="symbol-label bg-light-${typeColor}">
                                    <i class="ki-duotone ki-credit-card fs-2"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                            </div>
                            <div class="d-flex justify-content-start flex-column">
                                <span class="text-gray-800 fw-bold">${esc(m.method_name)}</span>
                                <div class="mt-1">
                                    <span class="badge badge-light-${m.is_active ? 'success' : 'danger'} badge-sm">
                                        ${m.is_active ? '{{ __('accounting.active') }}' : '{{ __('accounting.inactive') }}'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-light-${typeColor}">${esc(m.method_type)}</span></td>
                    <td><span class="badge badge-light-primary">${m.transaction_count}</span></td>
                    <td><span class="text-gray-800 fw-semibold">${esc(CURRENCY)} ${fmt(m.total_amount)}</span></td>
                    <td><span class="fw-bold text-success">${esc(CURRENCY)} ${fmt(m.grand_total)}</span></td>
                    <td><span class="text-gray-600">${esc(CURRENCY)} ${fmt(m.average_transaction)}</span></td>
                    <td><span class="text-danger">${esc(CURRENCY)} ${fmt(m.max_transaction)}</span></td>
                    <td><span class="badge badge-light-info">${m.categories_used}</span></td>
                    <td><span class="badge badge-light-warning">${m.vendors_used}</span></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:8px;">
                                <div class="progress-bar bg-${barColor}" style="width:${Math.min(pct, 100)}%"></div>
                            </div>
                            <span class="fw-bold text-gray-700 min-w-60px text-end">${pct.toFixed(1)}%</span>
                        </div>
                    </td>
                </tr>`;
        }).join('');

        if (badge) badge.textContent = `${rows.length} {{ __('accounting.payment_methods') }}`;
        showState('table');
    }

    function renderChart(chart) {
        const card = document.getElementById('chartCard');
        if (!card) return;
        const has = chart && chart.methods?.length && chart.months?.length;
        if (!has) { card.style.display = 'none'; return; }
        card.style.display = '';

        const el = document.querySelector('#monthlyTrendChart');
        if (!el || typeof ApexCharts === 'undefined') return;
        if (chartInstance) { try { chartInstance.destroy(); } catch(e) {} }

        const series = chart.methods.map(name => ({
            name,
            type: 'line',
            data: chart.months.map(m => {
                const key = m.key;
                return (chart.matrix[key] && chart.matrix[key][name]) || 0;
            }),
        }));

        const opts = {
            series,
            chart: { type: 'line', height: 400, toolbar: { show: true } },
            stroke: { width: 3, curve: 'smooth' },
            colors: ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C'],
            xaxis: {
                categories: chart.months.map(m => m.label),
                labels: { rotate: -45 },
            },
            yaxis: {
                title: { text: '{{ __('accounting.amount') }} (' + CURRENCY + ')' },
                labels: { formatter: (val) => CURRENCY + Number(val).toLocaleString() },
            },
            tooltip: {
                y: { formatter: (val) => CURRENCY + Number(val).toLocaleString(undefined, {
                    minimumFractionDigits: 2, maximumFractionDigits: 2,
                })},
            },
            legend: { position: 'top', horizontalAlign: 'center' },
            markers: { size: 5 },
            grid: { borderColor: '#f1f1f1' },
            dataLabels: { enabled: false },
        };

        chartInstance = new ApexCharts(el, opts);
        chartInstance.render();
    }

    function renderCategoryCards(methodByCategory) {
        const row = document.getElementById('categoryCardsRow');
        const body = document.getElementById('categoryCardsBody');
        if (!row || !body) return;

        const entries = Object.entries(methodByCategory || {});
        if (!entries.length) { row.style.display = 'none'; return; }
        row.style.display = '';

        body.innerHTML = entries.map(([methodName, categories]) => {
            const shown = categories.slice(0, 5);
            const more = categories.length - shown.length;

            const catRows = shown.map(c => `
                <tr>
                    <td class="fs-7">${esc((c.category_name || '').slice(0, 20))}</td>
                    <td class="text-end"><span class="badge badge-light-primary">${c.transaction_count}</span></td>
                    <td class="text-end fw-semibold">${esc(CURRENCY)} ${fmt(c.total_amount)}</td>
                </tr>`).join('');

            const moreRow = more > 0
                ? `<tr><td colspan="3" class="text-center pt-3"><small class="text-muted">+${more} {{ __('accounting.more_categories') }}</small></td></tr>`
                : '';

            const totalTx = categories.reduce((s, c) => s + (Number(c.transaction_count) || 0), 0);
            const totalAmt = categories.reduce((s, c) => s + (Number(c.total_amount) || 0), 0);

            return `
                <div class="col-md-6 col-lg-4">
                    <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="ki-duotone ki-credit-card fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                <h3 class="fw-bold m-0">${esc(methodName)}</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table table-row-bordered table-row-dashed gy-2 align-middle">
                                    <thead>
                                        <tr class="fw-bold fs-7 text-gray-800 border-bottom">
                                            <th>{{ __('accounting.category') }}</th>
                                            <th class="text-end">{{ __('accounting.transactions') }}</th>
                                            <th class="text-end">{{ __('accounting.amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${catRows}
                                        ${moreRow}
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold text-gray-800 border-top">
                                            <td>{{ __('accounting.total') }}</td>
                                            <td class="text-end"><span class="badge badge-light-primary">${totalTx}</span></td>
                                            <td class="text-end text-success fw-bold">${esc(CURRENCY)} ${fmt(totalAmt)}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>`;
        }).join('');
    }

    function renderDateBadge(start, end) {
        const f = (s) => {
            if (!s) return '—';
            const [y, m, d] = s.split('-');
            const names = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return `${d} ${names[(+m) - 1]} ${y}`;
        };
        const el = document.getElementById('expDateBadgeText');
        if (el) el.textContent = `${f(start)} — ${f(end)}`;
    }

    function renderActiveFilters() {
        const bar = document.getElementById('activeFiltersBar');
        const list = document.getElementById('activeFiltersList');
        if (!bar || !list) return;

        const meta = metaCache || {};
        const chips = [];

        if (state.payment_method_id) {
            const m = (meta.payment_methods || []).find(x => String(x.id) === String(state.payment_method_id));
            if (m) chips.push(['{{ __('accounting.payment_method') }}', m.name]);
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
            document.getElementById('expPmClear')?.click();
        });
    }

    function fmt(n) {
        return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.ExpenseByPaymentMethod = { reload: loadData };
    return window.ExpenseByPaymentMethod;
})();
</script>