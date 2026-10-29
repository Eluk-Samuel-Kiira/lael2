<script>
window.ProductSummary = (function () {
    'use strict';

    const ROUTES = window.PRODUCTS_ROUTES;
    const BOOT   = window.PRODUCTS_SUMMARY_BOOTSTRAP || {};
    let CURRENCY = '';
    let metaCache = null;
    let activeController = null;
    let inFlight = false;
    const charts = {};

    const state = {
        category_id:        BOOT.category_id        || '',
        product_type:       BOOT.product_type       || 'all',
        inventory_strategy: BOOT.inventory_strategy || 'all',
        is_active:          BOOT.is_active          || '',
        is_taxable:         BOOT.is_taxable         || '',
        per_page:           Number(BOOT.per_page)   || 15,
        page:               Number(BOOT.page)       || 1,
    };

    document.addEventListener('DOMContentLoaded', () => {
        seedForm();
        bind();
        loadMeta().then(() => loadData());
    });

    function bind() {
        const form = document.getElementById('productSummaryFilterForm');
        form?.addEventListener('submit', (e) => {
            e.preventDefault();
            readForm();
            state.page = 1;
            loadData();
        });

        document.getElementById('productSummaryClear')?.addEventListener('click', () => {
            state.category_id        = '';
            state.product_type       = 'all';
            state.inventory_strategy = 'all';
            state.is_active          = '';
            state.is_taxable         = '';
            state.per_page           = 15;
            state.page               = 1;
            seedForm();
            loadData();
        });

        document.getElementById('productSummaryPerPage')?.addEventListener('change', function () {
            state.per_page = parseInt(this.value, 10) || 15;
            state.page     = 1;
            loadData();
        });
    }

    function seedForm() {
        const form = document.getElementById('productSummaryFilterForm');
        if (!form) return;
        Object.entries(state).forEach(([k, v]) => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) el.value = v == null ? '' : String(v);
        });
        const perPageEl = document.getElementById('productSummaryPerPage');
        if (perPageEl) perPageEl.value = String(state.per_page);
    }
    function readForm() {
        const form = document.getElementById('productSummaryFilterForm');
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
            const res = await fetch(ROUTES.summaryMeta, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Meta failed');
            metaCache = json;
            CURRENCY = json.currency || '';
            const catSel = document.querySelector('#productSummaryFilterForm [name="category_id"]');
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
            console.error('[products-summary] meta failed:', err);
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
        params.set('page',     state.page);
        params.set('per_page', state.per_page);

        try {
            const res = await fetch(`${ROUTES.summaryData}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            renderSummary(json.summary || {}, json.currency || CURRENCY);
            renderTable(json.products || []);
            renderPagination(json.pagination || {});
            renderCharts(json.breakdowns || {});
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[products-summary] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load product summary.');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function showState(which) {
        document.getElementById('psLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('psWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('psEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function renderSummary(s, currency) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('statTotalProducts', fmt(s.total_products || 0));
        set('statTotalVariants', fmt(s.total_variants || 0));
        set('statTotalStock',    fmt(s.total_stock || 0));
        set('statAvgSell',       `${currency} ${fmt(s.avg_selling_price || 0)}`);
        set('statAvgCost',       `${currency} ${fmt(s.avg_cost_price || 0)}`);
        set('statAvgMargin',     `${Number(s.avg_margin || 0).toFixed(1)}%`);
    }

    function renderTable(rows) {
        const tbody = document.getElementById('psBody');
        if (!tbody) return;
        if (!rows.length) { showState('empty'); return; }

        const TYPE_COLORS  = { physical: 'primary', digital: 'success', service: 'warning', composite: 'info' };
        const STRAT_COLORS = { quantity: 'primary', batch: 'info', serial: 'warning', recipe: 'success' };
        const STRAT_LABELS = { quantity: 'Quantity', batch: 'Batch', serial: 'Serial', recipe: 'Recipe' };

        tbody.innerHTML = rows.map(p => {
            const tColor = TYPE_COLORS[p.type] || 'secondary';
            const sKey   = p.inventory_strategy || 'quantity';
            const sColor = STRAT_COLORS[sKey] || 'secondary';
            const sLabel = STRAT_LABELS[sKey] || sKey;
            const img = p.image_url
                ? `<img src="${esc(p.image_url)}" class="rounded" style="width:40px;height:40px;object-fit:cover;">`
                : '';
            return `
                <tr data-product-id="${p.id}" style="cursor:pointer;" tabindex="0">
                    <td class="ps-4 fw-semibold">${esc(p.sku || '')}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${img ? `<div class="symbol symbol-50px me-3">${img}</div>` : ''}
                            <div>
                                <div class="fw-bold text-gray-800">${esc(p.name || '')}</div>
                                ${p.description ? `<div class="text-muted fs-7">${esc((p.description || '').slice(0, 50))}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>${p.category_name ? `<span class="badge badge-light-info">${esc(p.category_name)}</span>` : `<span class="text-muted">-</span>`}</td>
                    <td><span class="badge badge-light-${tColor}">${esc((p.type || '').charAt(0).toUpperCase() + (p.type || '').slice(1))}</span></td>
                    <td><span class="badge badge-light-${sColor}"><i class="ki-duotone ki-boxes fs-5 me-1"></i>${esc(sLabel)}</span></td>
                    <td><span class="badge badge-light-${p.is_active ? 'success' : 'danger'}">${p.is_active ? '{{ __('auth.active') }}' : '{{ __('auth.inactive') }}'}</span></td>
                    <td><span class="badge badge-light-${p.is_taxable ? 'primary' : 'secondary'}">${p.is_taxable ? '{{ __('auth.taxable') }}' : '{{ __('auth.non_taxable') }}'}</span></td>
                    <td class="text-center"><span class="badge badge-light-primary">${p.variant_count}</span></td>
                    <td><span class="badge badge-light-info">${fmt(p.total_stock)}</span></td>
                    <td><span class="text-muted">${esc(p.created_at || '')}</span></td>
                </tr>`;
        }).join('');

        // Bind row → ProductDrawer
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
        const info = document.getElementById('psPageInfo');
        const ul   = document.getElementById('psPagination');
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

        addPage((p.current_page || 1) - 1, 'Prev', false, (p.current_page || 1) <= 1);
        const start = Math.max(1, (p.current_page || 1) - 2);
        const end   = Math.min(p.last_page || 1, (p.current_page || 1) + 2);
        for (let i = start; i <= end; i++) addPage(i, String(i), i === p.current_page);
        addPage((p.current_page || 1) + 1, 'Next', false, !p.has_more);
    }

    // ── Charts ─────────────────────────────────────────────
    function renderCharts(b) {
        renderCategoryChart(b.category || []);
        renderTypeChart(b.type || []);
        renderStrategyChart(b.strategy || []);
        renderStatusChart(b.status || []);
    }

    function destroyChart(key) {
        if (charts[key]) { try { charts[key].destroy(); } catch(e) {} charts[key] = null; }
    }

    function renderCategoryChart(rows) {
        const el = document.querySelector('#categoryChart');
        if (!el) return;
        destroyChart('category');
        const labels = rows.map(r => r.name);
        const series = rows.map(r => r.product_count);
        if (!labels.length) return;
        charts.category = new ApexCharts(el, {
            series, chart: { type: 'pie', height: 280, toolbar: { show: false } },
            labels,
            colors: ['#3E97FF','#50CD89','#7239EA','#FFC700','#F1416C','#A1A5B7'],
            legend: { position: 'bottom' },
            tooltip: { y: { formatter: v => v + ' {{ __('auth.products') }}' } },
        });
        charts.category.render();
    }

    function renderTypeChart(rows) {
        const el = document.querySelector('#typeChart');
        if (!el) return;
        destroyChart('type');
        if (!rows.length) return;
        charts.type = new ApexCharts(el, {
            series: [{ data: rows.map(r => r.count) }],
            chart: { type: 'bar', height: 280, toolbar: { show: false } },
            plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
            xaxis: { categories: rows.map(r => r.type) },
            colors: ['#3E97FF'],
            tooltip: { y: { formatter: v => v + ' {{ __('auth.products') }}' } },
        });
        charts.type.render();
    }

    function renderStrategyChart(rows) {
        const el = document.querySelector('#strategyChart');
        if (!el) return;
        destroyChart('strategy');
        if (!rows.length) return;
        const colorMap = { quantity: '#3E97FF', batch: '#50CD89', serial: '#FFC700', recipe: '#7239EA' };
        charts.strategy = new ApexCharts(el, {
            series: rows.map(r => r.count),
            chart: { type: 'donut', height: 280, toolbar: { show: false } },
            labels: rows.map(r => r.strategy),
            colors: rows.map(r => colorMap[r.key] || '#A1A5B7'),
            legend: { position: 'bottom' },
            tooltip: { y: { formatter: v => v + ' {{ __('auth.products') }}' } },
            plotOptions: { pie: { donut: { size: '65%', labels: { show: true, name: { show: true }, value: { show: true } } } } },
        });
        charts.strategy.render();
    }

    function renderStatusChart(rows) {
        const el = document.querySelector('#statusChart');
        if (!el) return;
        destroyChart('status');
        if (!rows.length) return;
        charts.status = new ApexCharts(el, {
            series: rows.map(r => r.count),
            chart: { type: 'donut', height: 280, toolbar: { show: false } },
            labels: rows.map(r => r.status),
            colors: rows.map(r => r.color === 'success' ? '#50CD89' : '#F1416C'),
            legend: { position: 'bottom' },
            tooltip: { y: { formatter: v => v + ' {{ __('auth.products') }}' } },
        });
        charts.status.render();
    }

    function fmt(n) {
        return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.ProductSummary = { reload: loadData };
    return window.ProductSummary;
})();
</script>