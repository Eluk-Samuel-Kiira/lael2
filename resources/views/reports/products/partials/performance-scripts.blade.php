<script>
window.ProductPerformance = (function () {
    'use strict';

    const ROUTES = window.PRODUCTS_ROUTES;
    const BOOT   = window.PERF_BOOTSTRAP || {};
    let CURRENCY = '';
    let metaCache = null;
    let activeController = null;
    let inFlight = false;
    const charts = {};

    const state = {
        category_id:  BOOT.category_id  || '',
        product_type: BOOT.product_type || 'all',
        per_page:     Number(BOOT.per_page) || 15,
        page:         Number(BOOT.page) || 1,
    };

    document.addEventListener('DOMContentLoaded', () => {
        seedForm();
        bind();
        loadMeta().then(() => loadData());
    });

    function bind() {
        document.getElementById('perfFilterForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            readForm();
            state.page = 1;
            loadData();
        });

        document.getElementById('perfClear')?.addEventListener('click', () => {
            state.category_id  = '';
            state.product_type = 'all';
            state.per_page     = 15;
            state.page         = 1;
            seedForm();
            loadData();
        });

        document.getElementById('perfPerPage')?.addEventListener('change', function () {
            state.per_page = parseInt(this.value, 10) || 15;
            state.page     = 1;
            loadData();
        });

        document.querySelectorAll('[data-export-table]').forEach(el => {
            el.addEventListener('click', () => {
                const tableId  = el.dataset.exportTable;
                const filename = (el.dataset.exportFilename || 'report') + '_' + new Date().toISOString().slice(0, 10);
                const format   = el.dataset.exportFormat || 'xlsx';
                const sheet    = el.dataset.exportSheet || 'Sheet1';
                if (typeof window.exportCurrentPage === 'function') {
                    window.exportCurrentPage({ tableId, filename, sheetName: sheet, format });
                }
            });
        });
    }

    function seedForm() {
        const form = document.getElementById('perfFilterForm');
        if (!form) return;
        Object.entries(state).forEach(([k, v]) => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) el.value = v == null ? '' : String(v);
        });
        const perPageEl = document.getElementById('perfPerPage');
        if (perPageEl) perPageEl.value = String(state.per_page);
    }
    function readForm() {
        const form = document.getElementById('perfFilterForm');
        if (!form) return;
        Object.keys(state).forEach(k => {
            if (k === 'page' || k === 'per_page') return;
            const el = form.querySelector(`[name="${k}"]`);
            if (el) state[k] = el.value;
        });
    }

    async function loadMeta() {
        if (metaCache) return metaCache;
        try {
            const res = await fetch(ROUTES.performanceMeta, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Meta failed');
            metaCache = json;
            CURRENCY = json.currency || '';

            const catSel = document.querySelector('#perfFilterForm [name="category_id"]');
            if (catSel) {
                const ph = catSel.querySelector('option[value=""]');
                catSel.innerHTML = '';
                if (ph) catSel.appendChild(ph);
                (json.categories || []).forEach(c => {
                    const o = document.createElement('option');
                    o.value = c.id; o.textContent = c.name;
                    catSel.appendChild(o);
                });
                catSel.value = state.category_id || '';
            }
            return json;
        } catch (err) {
            console.error('[products-performance] meta failed:', err);
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
        params.set('page', state.page);
        params.set('per_page', state.per_page);

        try {
            const res = await fetch(`${ROUTES.performanceData}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            renderSummary(json.summary || {}, json.currency || CURRENCY);
            renderTable(json.products || []);
            renderPagination(json.pagination || {});
            renderCharts(json.charts || {});
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[products-performance] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load product performance.');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function showState(which) {
        document.getElementById('perfLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('perfWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('perfEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function renderSummary(s, currency) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('perfStatProducts', fmt(s.total_products || 0));
        set('perfStatStock',    fmt(s.total_stock || 0));
        set('perfStatRevenue',  `${currency} ${fmt(s.total_revenue_value || 0)}`);
        set('perfStatMargin',   `${currency} ${fmt(s.total_margin || 0)} (${Number(s.avg_margin || 0).toFixed(1)}%)`);
    }

    function renderTable(rows) {
        const tbody = document.getElementById('perfBody');
        if (!tbody) return;
        if (!rows.length) { showState('empty'); return; }

        const TYPE_COLORS = { physical: 'primary', digital: 'success', service: 'warning', composite: 'info' };

        tbody.innerHTML = rows.map(p => {
            const tColor = TYPE_COLORS[p.type] || 'secondary';
            const mp     = p.margin_pct || 0;
            const perf   = mp >= 50 ? { label: '{{ __('auth.excellent') }}', c: 'success' }
                        : mp >= 30 ? { label: '{{ __('auth.good') }}', c: 'primary' }
                        : mp >= 10 ? { label: '{{ __('auth.average') }}', c: 'warning' }
                        :            { label: '{{ __('auth.needs_improvement') }}', c: 'danger' };

            const img = p.image_url
                ? `<img src="${esc(p.image_url)}" class="rounded" style="width:40px;height:40px;object-fit:cover;">`
                : '';

            return `
                <tr data-product-id="${p.id}" style="cursor:pointer;" tabindex="0">
                    <td class="ps-4 fw-semibold">${esc(p.sku || '')}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${img ? `<div class="symbol symbol-50px me-3">${img}</div>` : ''}
                            <div class="fw-bold text-gray-800">${esc(p.name || '')}</div>
                        </div>
                    </td>
                    <td>${p.category_name ? `<span class="badge badge-light-info">${esc(p.category_name)}</span>` : `<span class="text-muted">-</span>`}</td>
                    <td><span class="badge badge-light-${tColor}">${esc((p.type || '').charAt(0).toUpperCase() + (p.type || '').slice(1))}</span></td>
                    <td class="text-center"><span class="badge badge-light-primary">${fmt(p.total_stock)}</span></td>
                    <td class="text-end text-gray-600">${esc(CURRENCY)} ${fmt(p.total_cost_value)}</td>
                    <td class="text-end text-gray-800 fw-semibold">${esc(CURRENCY)} ${fmt(p.total_revenue_value)}</td>
                    <td class="text-end ${p.total_margin >= 0 ? 'text-success' : 'text-danger'} fw-bold">${esc(CURRENCY)} ${fmt(p.total_margin)}</td>
                    <td class="text-center"><span class="badge badge-light-${perf.c}">${Number(mp).toFixed(1)}%</span></td>
                    <td class="text-center"><span class="badge badge-${perf.c}">${perf.label}</span></td>
                </tr>`;
        }).join('');

        // Row → ProductDrawer
        tbody.querySelectorAll('tr[data-product-id]').forEach(tr => {
            tr.addEventListener('click', (e) => {
                if (e.target.closest('button, a, input, select')) return;
                const id = parseInt(tr.dataset.productId, 10);
                if (id && window.ProductDrawer) window.ProductDrawer.open(id);
            });
            tr.addEventListener('keydown', (e) => {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                e.preventDefault();
                const id = parseInt(tr.dataset.productId, 10);
                if (id && window.ProductDrawer) window.ProductDrawer.open(id);
            });
        });

        showState('table');
    }

    function renderPagination(p) {
        const info = document.getElementById('perfPageInfo');
        const ul   = document.getElementById('perfPagination');
        if (!info || !ul) return;

        info.textContent = p.total
            ? `{{ __('accounting.showing') }} ${p.from || 0}–${p.to || 0} {{ __('accounting.of') }} ${p.total}`
            : '';
        ul.innerHTML = '';
        if (!p.total || p.last_page <= 1) return;

        const addPage = (page, label, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link'; a.href = '#'; a.textContent = label;
            if (!disabled) a.addEventListener('click', (ev) => {
                ev.preventDefault(); state.page = page; loadData();
            });
            li.appendChild(a); ul.appendChild(li);
        };
        addPage(p.current_page - 1, 'Prev', false, p.current_page <= 1);
        const start = Math.max(1, p.current_page - 2);
        const end   = Math.min(p.last_page, p.current_page + 2);
        for (let i = start; i <= end; i++) addPage(i, String(i), i === p.current_page);
        addPage(p.current_page + 1, 'Next', false, !p.has_more);
    }

    function renderCharts(c) {
        destroyChart('dist');
        destroyChart('top');

        const distEl = document.querySelector('#marginDistributionChart');
        if (distEl && c.distribution) {
            const d = c.distribution;
            charts.dist = new ApexCharts(distEl, {
                series: [d.excellent || 0, d.good || 0, d.average || 0, d.poor || 0],
                chart: { type: 'pie', height: 350, toolbar: { show: true } },
                labels: ['{{ __('auth.excellent') }} (≥50%)', '{{ __('auth.good') }} (30-49%)', '{{ __('auth.average') }} (10-29%)', '{{ __('auth.needs_improvement') }} (<10%)'],
                colors: ['#50CD89', '#3E97FF', '#FFC700', '#F1416C'],
                legend: { position: 'bottom' },
                tooltip: { y: { formatter: v => v + ' {{ __('auth.products') }}' } },
            });
            charts.dist.render();
        }

        const topEl = document.querySelector('#topPerformersChart');
        if (topEl && c.top10) {
            charts.top = new ApexCharts(topEl, {
                series: [{ name: 'Margin %', data: c.top10.map(p => p.margin) }],
                chart: { type: 'bar', height: 350, toolbar: { show: true } },
                plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
                xaxis: { categories: c.top10.map(p => p.name) },
                colors: ['#3E97FF'],
                tooltip: { y: { formatter: v => Number(v).toFixed(1) + '%' } },
                dataLabels: { enabled: true, formatter: v => Number(v).toFixed(1) + '%' },
            });
            charts.top.render();
        }
    }

    function destroyChart(key) {
        if (charts[key]) { try { charts[key].destroy(); } catch (e) {} charts[key] = null; }
    }

    function fmt(n) {
        return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.ProductPerformance = { reload: loadData };
    return window.ProductPerformance;
})();
</script>