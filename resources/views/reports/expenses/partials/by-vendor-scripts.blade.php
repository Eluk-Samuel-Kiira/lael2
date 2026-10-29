<script>
window.ExpenseByVendor = (function () {
    'use strict';

    const ROUTES = window.EXP_VENDOR_ROUTES;
    const BOOT   = window.EXP_VENDOR_BOOTSTRAP || {};
    let CURRENCY = '';
    let metaCache = null;
    let activeController = null;
    let inFlight = false;
    let chartInstance = null;

    const state = {
        start_date:        BOOT.start_date        || '',
        end_date:          BOOT.end_date          || '',
        vendor_name:       BOOT.vendor_name       || '',
        category_id:       BOOT.category_id       || '',
        payment_method_id: BOOT.payment_method_id || '',
        location_id:       BOOT.location_id       || '',
        min_amount:        BOOT.min_amount        || '',
        max_amount:        BOOT.max_amount        || '',
    };

    document.addEventListener('DOMContentLoaded', () => {
        seedForm();
        bind();
        loadMeta().then(() => loadData());
    });

    function bind() {
        const form = document.getElementById('expVendorFilterForm');
        form?.addEventListener('submit', (e) => {
            e.preventDefault();
            // min <= max guard
            const minV = parseFloat(state.min_amount || form.querySelector('[name="min_amount"]').value || 0) || 0;
            const maxV = parseFloat(state.max_amount || form.querySelector('[name="max_amount"]').value || 0) || 0;
            readForm();
            if (minV > 0 && maxV > 0 && minV > maxV) {
                window.toastr?.warning?.('{{ __("accounting.min_amount_cannot_exceed_max") }}');
                form.querySelector('[name="min_amount"]')?.focus();
                return;
            }
            loadData();
        });

        document.getElementById('expVendorClear')?.addEventListener('click', () => {
            state.start_date  = BOOT.start_date;
            state.end_date    = BOOT.end_date;
            Object.keys(state).forEach(k => {
                if (k === 'start_date' || k === 'end_date') return;
                state[k] = '';
            });
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
        const form = document.getElementById('expVendorFilterForm');
        if (!form) return;
        Object.entries(state).forEach(([k, v]) => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) el.value = v == null ? '' : String(v);
        });
    }
    function readForm() {
        const form = document.getElementById('expVendorFilterForm');
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
            fillSelect('category_id', json.categories, 'id', 'name');
            fillSelect('payment_method_id', json.payment_methods, 'id', m => m.name + (m.is_default ? ' (default)' : ''));
            fillSelect('location_id', json.locations, 'id', 'name');
            return json;
        } catch (err) {
            console.error('[exp-by-vendor] meta failed:', err);
            throw err;
        }
    }

    function fillSelect(name, rows, valKey, labelFnOrKey) {
        const sel = document.querySelector(`#expVendorFilterForm [name="${name}"]`);
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
            renderVendorTable(json.vendors || [], json.vendor_payment_methods || [], json.summary);
            renderChart(json.chart);
            renderMonthlyTop(json.monthly_top || []);
            renderDateBadge(json.meta.start_date, json.meta.end_date);
            renderActiveFilters();
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[exp-by-vendor] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load vendor expenses.');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function showState(which) {
        document.getElementById('vendorLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('vendorWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('vendorEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function renderStats(s, currency) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        const money = (n) => `${currency} ${fmt(n)}`;
        set('statVendorCount', fmt(s.total_vendors || 0));
        set('statTotalTransactions', fmt(s.total_transactions || 0));
        set('statGrandTotal', money(s.total_amount || 0));
        set('statTotalTax', money(s.total_tax || 0));
        set('statAvgTransaction', money(s.avg_transaction || 0));
        set('statLargest', money(s.largest_single || 0));
    }

    function renderVendorTable(vendors, paymentMethods, summary) {
        const tbody = document.getElementById('vendorBody');
        const badge = document.getElementById('vendorCountBadge');
        if (!tbody) return;

        if (!vendors.length) { showState('empty'); return; }

        const palette = ['primary', 'success', 'info', 'warning', 'danger'];
        const pmByVendor = {};
        paymentMethods.forEach(m => {
            pmByVendor[m.vendor_name] = pmByVendor[m.vendor_name] || [];
            pmByVendor[m.vendor_name].push(m);
        });

        const totalAmount = (summary && summary.total_amount) || 0;

        tbody.innerHTML = vendors.map((v, i) => {
            const pct = totalAmount > 0 ? (v.grand_total / totalAmount) * 100 : 0;
            const color = palette[i % palette.length];
            const pmList = pmByVendor[v.vendor_name] || [];
            const topPM = pmList.slice(0, 2).map(m =>
                `<span class="badge badge-light-primary badge-sm me-1">${esc(m.payment_method)} (${m.count})</span>`
            ).join('');
            const extraPM = pmList.length > 2
                ? `<span class="badge badge-light-secondary badge-sm">+${pmList.length - 2}</span>`
                : '';
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
                                    <i class="ki-duotone ki-shop fs-2"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                            </div>
                            <div class="d-flex justify-content-start flex-column">
                                <span class="text-gray-800 fw-bold">${esc(v.vendor_name)}</span>
                                ${pmList.length ? `<div class="mt-1">${topPM}${extraPM}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-light-primary">${v.transaction_count}</span></td>
                    <td><span class="text-gray-800 fw-semibold">${esc(CURRENCY)} ${fmt(v.total_amount)}</span></td>
                    <td><span class="text-info">${esc(CURRENCY)} ${fmt(v.total_tax)}</span></td>
                    <td><span class="fw-bold text-success">${esc(CURRENCY)} ${fmt(v.grand_total)}</span></td>
                    <td><span class="text-gray-600">${esc(CURRENCY)} ${fmt(v.average_transaction)}</span></td>
                    <td><span class="text-danger">${esc(CURRENCY)} ${fmt(v.largest_transaction)}</span></td>
                    <td><span class="text-warning">${esc(CURRENCY)} ${fmt(v.smallest_transaction)}</span></td>
                    <td><span class="badge badge-light-info">${v.categories_used}</span></td>
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

        if (badge) badge.textContent = `${vendors.length} {{ __('accounting.vendors') }}`;
        showState('table');
    }

    function renderChart(chart) {
        const card = document.getElementById('chartCard');
        if (!card) return;
        const has = chart && chart.vendors?.length && chart.months?.length;
        if (!has) { card.style.display = 'none'; return; }
        card.style.display = '';

        const el = document.querySelector('#monthlyVendorChart');
        if (!el || typeof ApexCharts === 'undefined') return;
        if (chartInstance) { try { chartInstance.destroy(); } catch(e) {} }

        const series = chart.vendors.map(vendor => ({
            name: vendor,
            type: 'line',
            data: chart.months.map(m => {
                const key = m.key;
                return (chart.matrix[key] && chart.matrix[key][vendor]) || 0;
            }),
        }));

        const opts = {
            series,
            chart: {
                type: 'line', height: 400,
                toolbar: {
                    show: true,
                    tools: { download: true, selection: true, zoom: true, zoomin: true, zoomout: true, pan: true, reset: true },
                },
            },
            stroke: { width: 3, curve: 'smooth' },
            colors: ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C'],
            xaxis: {
                categories: chart.months.map(m => m.label),
                labels: { rotate: -45, style: { fontSize: '12px' } },
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
            legend: { position: 'top', horizontalAlign: 'center', fontSize: '14px' },
            markers: { size: 5 },
            grid: { borderColor: '#f1f1f1' },
            dataLabels: { enabled: false },
        };

        chartInstance = new ApexCharts(el, opts);
        chartInstance.render();
    }

    function renderMonthlyTop(rows) {
        const card = document.getElementById('monthlyTopCard');
        const tbody = document.getElementById('monthlyTopBody');
        if (!card || !tbody) return;
        if (!rows.length) { card.style.display = 'none'; return; }
        card.style.display = '';

        tbody.innerHTML = rows.map(r => `
            <tr>
                <td class="ps-4 fw-semibold">${esc(r.label)}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-30px symbol-circle me-3">
                            <div class="symbol-label bg-light-primary">
                                <i class="ki-duotone ki-shop fs-2"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                        </div>
                        <span class="text-gray-800 fw-bold">${esc(r.vendor_name)}</span>
                    </div>
                </td>
                <td><span class="badge badge-light-primary">${r.transaction_count}</span></td>
                <td><span class="fw-bold text-success">${esc(CURRENCY)} ${fmt(r.monthly_total)}</span></td>
                <td><span class="text-gray-600">${esc(CURRENCY)} ${fmt(r.monthly_average)}</span></td>
            </tr>
        `).join('');
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

        if (state.vendor_name) chips.push(['{{ __('accounting.vendor_name') }}', state.vendor_name]);
        if (state.category_id) {
            const c = (meta.categories || []).find(x => String(x.id) === String(state.category_id));
            if (c) chips.push(['{{ __('accounting.category') }}', c.name]);
        }
        if (state.payment_method_id) {
            const c = (meta.payment_methods || []).find(x => String(x.id) === String(state.payment_method_id));
            if (c) chips.push(['{{ __('accounting.payment_method') }}', c.name]);
        }
        if (state.location_id) {
            const l = (meta.locations || []).find(x => String(x.id) === String(state.location_id));
            if (l) chips.push(['{{ __('accounting.location') }}', l.name]);
        }
        if (state.min_amount !== '' && state.min_amount != null) {
            chips.push(['{{ __('accounting.min_amount') }}', `${CURRENCY} ${fmt(state.min_amount)}`]);
        }
        if (state.max_amount !== '' && state.max_amount != null) {
            chips.push(['{{ __('accounting.max_amount') }}', `${CURRENCY} ${fmt(state.max_amount)}`]);
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
            document.getElementById('expVendorClear')?.click();
        });
    }

    function fmt(n) {
        return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.ExpenseByVendor = { reload: loadData };
    return window.ExpenseByVendor;
})();
</script>