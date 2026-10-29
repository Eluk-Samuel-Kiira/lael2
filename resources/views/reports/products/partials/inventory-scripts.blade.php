<script>
window.InventoryValuation = (function () {
    'use strict';

    const ROUTES = window.PRODUCTS_ROUTES;
    const BOOT   = window.INV_BOOTSTRAP || {};
    let CURRENCY = '';
    let metaCache = null;
    let activeController = null;
    let inFlight = false;
    const charts = {};

    const state = {
        category_id:  BOOT.category_id  || '',
        stock_status: BOOT.stock_status || '',
        per_page:     Number(BOOT.per_page) || 15,
        page:         Number(BOOT.page) || 1,
    };

    document.addEventListener('DOMContentLoaded', () => {
        seedForm();
        bind();
        loadMeta().then(() => loadData());
    });

    function bind() {
        document.getElementById('invFilterForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            readForm();
            state.page = 1;
            loadData();
        });

        document.getElementById('invClear')?.addEventListener('click', () => {
            state.category_id  = '';
            state.stock_status = '';
            state.per_page     = 15;
            state.page         = 1;
            seedForm();
            loadData();
        });

        document.getElementById('invPerPage')?.addEventListener('change', function () {
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
        const form = document.getElementById('invFilterForm');
        if (!form) return;
        Object.entries(state).forEach(([k, v]) => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) el.value = v == null ? '' : String(v);
        });
        const perPageEl = document.getElementById('invPerPage');
        if (perPageEl) perPageEl.value = String(state.per_page);
    }
    function readForm() {
        const form = document.getElementById('invFilterForm');
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
            const res = await fetch(ROUTES.inventoryMeta, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Meta failed');
            metaCache = json;
            CURRENCY = json.currency || '';
            const catSel = document.querySelector('#invFilterForm [name="category_id"]');
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
            console.error('[inventory] meta failed:', err);
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
            const res = await fetch(`${ROUTES.inventoryData}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            renderSummary(json.summary || {}, json.currency || CURRENCY);
            renderTable(json.variants || []);
            renderPagination(json.pagination || {});
            renderCharts(json.charts || {});
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[inventory] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load inventory valuation.');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function showState(which) {
        document.getElementById('invLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('invWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('invEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function renderSummary(s, currency) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('invStatItems',   fmt(s.total_items || 0));
        set('invStatQty',     fmt(s.total_quantity || 0));
        set('invStatCost',    `${currency} ${fmt(s.total_cost || 0)}`);
        set('invStatRevenue', `${currency} ${fmt(s.total_revenue || 0)}`);
        set('invStatProfit',  `${currency} ${fmt(s.total_profit || 0)}`);
        set('invStatMargin',  `${Number(s.avg_margin || 0).toFixed(1)}%`);
    }

    function renderTable(rows) {
        const tbody = document.getElementById('invBody');
        const badge = document.getElementById('invCountBadge');
        if (!tbody) return;
        if (!rows.length) { showState('empty'); return; }

        const HEALTH = {
            healthy:   { color: 'success', label: '{{ __('auth.in_stock') }}' },
            low:       { color: 'warning', label: '{{ __('auth.low_stock') }}' },
            out:       { color: 'danger',  label: '{{ __('auth.out_of_stock') }}' },
            overstock: { color: 'info',    label: '{{ __('auth.overstock') }}' },
        };

        tbody.innerHTML = rows.map(v => {
            const hp = HEALTH[v.health] || HEALTH.healthy;
            const img = v.image_url
                ? `<img src="${esc(v.image_url)}" alt="" class="rounded" style="width:40px;height:40px;object-fit:cover;">`
                : '';
            const marginBadge = v.margin_pct >= 30 ? 'success' : v.margin_pct >= 10 ? 'warning' : 'danger';

            return `
                <tr data-variant-id="${v.id}" style="cursor:pointer;" tabindex="0">
                    <td class="ps-4 fw-semibold">${esc(v.sku || '')}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${img ? `<div class="symbol symbol-40px me-3">${img}</div>` : ''}
                            <div>
                                <div class="fw-bold text-gray-800">${esc(v.name || '')}</div>
                                ${v.product_name && v.product_name !== v.name ? `<div class="text-muted fs-7">${esc(v.product_name)}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>${v.category_name ? `<span class="badge badge-light-info">${esc(v.category_name)}</span>` : `<span class="text-muted">—</span>`}</td>
                    <td class="text-center"><span class="badge badge-light-${hp.color}">${fmt(v.quantity)}</span></td>
                    <td class="text-end"><span class="fw-semibold text-gray-800">${esc(CURRENCY)} ${fmt(v.selling_price)}</span></td>
                    <td class="text-end"><span class="text-gray-600">${esc(CURRENCY)} ${fmt(v.cost_price)}</span></td>
                    <td class="text-end">
                        <span class="fw-bold text-${v.profit >= 0 ? 'success' : 'danger'}">
                            ${esc(CURRENCY)} ${fmt(v.profit)}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-${marginBadge}">${Number(v.margin_pct).toFixed(1)}%</span>
                    </td>
                    <td class="text-center"><span class="badge badge-${hp.color}">${hp.label}</span></td>
                    <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
                </tr>`;
        }).join('');

        if (badge) badge.textContent = `${rows.length} {{ __('auth.items') }}`;

        // Bind row clicks → VariantDrawer (variant IS the row)
        tbody.querySelectorAll('tr[data-variant-id]').forEach(tr => {
            tr.addEventListener('click', (e) => {
                if (e.target.closest('button, a, input, select')) return;
                const id = parseInt(tr.dataset.variantId, 10);
                if (id && window.VariantDrawer) window.VariantDrawer.open(id);
            });
            tr.addEventListener('keydown', (e) => {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                e.preventDefault();
                const id = parseInt(tr.dataset.variantId, 10);
                if (id && window.VariantDrawer) window.VariantDrawer.open(id);
            });
        });

        showState('table');
    }

    function renderPagination(p) {
        const info = document.getElementById('invPageInfo');
        const ul   = document.getElementById('invPagination');
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
        destroyChart('health');
        destroyChart('value');

        const hc = c.health_counts || {};
        const hv = c.health_values || {};

        const healthEl = document.querySelector('#stockHealthChart');
        if (healthEl) {
            charts.health = new ApexCharts(healthEl, {
                series: [hc.healthy || 0, hc.low || 0, hc.out || 0, hc.overstock || 0],
                chart: { type: 'pie', height: 300, toolbar: { show: true } },
                labels: ['{{ __('auth.in_stock') }}', '{{ __('auth.low_stock') }}', '{{ __('auth.out_of_stock') }}', '{{ __('auth.overstock') }}'],
                colors: ['#50CD89', '#FFC700', '#F1416C', '#3E97FF'],
                legend: { position: 'bottom' },
                tooltip: { y: { formatter: v => v + ' {{ __('auth.items') }}' } },
                dataLabels: { enabled: true },
            });
            charts.health.render();
        }

        const valueEl = document.querySelector('#valueDistributionChart');
        if (valueEl) {
            charts.value = new ApexCharts(valueEl, {
                series: [{
                    name: '{{ __('auth.stock_value') }}',
                    data: [hv.healthy || 0, hv.low || 0, hv.out || 0, hv.overstock || 0],
                }],
                chart: { type: 'bar', height: 300, toolbar: { show: true } },
                plotOptions: { bar: { horizontal: false, borderRadius: 4, columnWidth: '55%' } },
                xaxis: { categories: ['{{ __('auth.in_stock') }}', '{{ __('auth.low_stock') }}', '{{ __('auth.out_of_stock') }}', '{{ __('auth.overstock') }}'] },
                yaxis: { labels: { formatter: v => CURRENCY + Number(v).toLocaleString() } },
                colors: ['#3E97FF'],
                tooltip: { y: { formatter: v => CURRENCY + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) } },
            });
            charts.value.render();
        }
    }

    function destroyChart(key) {
        if (charts[key]) { try { charts[key].destroy(); } catch (e) {} charts[key] = null; }
    }

    function fmt(n) { return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 }); }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.InventoryValuation = { reload: loadData };
    return window.InventoryValuation;
})();
</script>