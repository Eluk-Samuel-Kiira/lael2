<script>
window.ProductMargin = (function () {
    'use strict';

    const ROUTES = window.PRODUCTS_ROUTES;
    const BOOT   = window.MG_BOOTSTRAP || {};
    let CURRENCY = '';
    let metaCache = null;
    let activeController = null;
    let inFlight = false;
    const charts = {};

    const state = {
        category_id: BOOT.category_id || '',
        min_margin:  BOOT.min_margin  != null ? Number(BOOT.min_margin)  : 0,
        max_margin:  BOOT.max_margin  != null ? Number(BOOT.max_margin)  : 100,
        per_page:    Number(BOOT.per_page) || 15,
        page:        Number(BOOT.page)     || 1,
    };

    document.addEventListener('DOMContentLoaded', () => {
        seedForm();
        bind();
        loadMeta().then(() => loadData()).catch(() => loadData());
    });

    function bind() {
        document.getElementById('mgFilterForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            readForm();
            state.page = 1;
            loadData();
        });

        document.getElementById('mgClear')?.addEventListener('click', () => {
            state.category_id = '';
            state.min_margin  = 0;
            state.max_margin  = 100;
            state.per_page    = 15;
            state.page        = 1;
            seedForm();
            loadData();
        });

        document.getElementById('mgPerPage')?.addEventListener('change', function () {
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
        const form = document.getElementById('mgFilterForm');
        if (!form) return;
        Object.entries(state).forEach(([k, v]) => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) el.value = v == null ? '' : String(v);
        });
        const perPageEl = document.getElementById('mgPerPage');
        if (perPageEl) perPageEl.value = String(state.per_page);
    }

    function readForm() {
        const form = document.getElementById('mgFilterForm');
        if (!form) return;
        Object.keys(state).forEach(k => {
            if (k === 'page' || k === 'per_page') return;
            const el = form.querySelector(`[name="${k}"]`);
            if (el) state[k] = el.value;
        });
        state.min_margin = state.min_margin === '' ? 0   : Number(state.min_margin);
        state.max_margin = state.max_margin === '' ? 100 : Number(state.max_margin);
    }

    async function loadMeta() {
        if (metaCache) return metaCache;
        try {
            const res = await fetch(ROUTES.marginMeta, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Meta failed');
            metaCache = json;
            CURRENCY = json.currency || '';
            const catSel = document.querySelector('#mgFilterForm [name="category_id"]');
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
            console.error('[margin] meta failed:', err);
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
            const res = await fetch(`${ROUTES.marginData}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            CURRENCY = json.currency || CURRENCY;
            renderSummary(json.summary || {});
            renderTable(json.variants || [], json.page_totals || {});
            renderPagination(json.pagination || {});
            renderCharts(json.charts || {});
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[margin] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load margin report.');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function showState(which) {
        document.getElementById('mgLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('mgWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('mgEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function renderSummary(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('mgStatTotal',  fmt(s.total_variants     || 0));
        set('mgStatAvg',    `${Number(s.average_margin || 0).toFixed(1)}%`);
        set('mgStatHigh',   fmt(s.high_margin_count  || 0));
        set('mgStatMedium', fmt(s.medium_margin_count|| 0));
        set('mgStatLow',    fmt(s.low_margin_count   || 0));
        set('mgStatValue',  `${CURRENCY} ${fmt(s.total_margin_value || 0)}`);
    }

    function renderTable(rows, pageTotals) {
        const tbody = document.getElementById('mgBody');
        const tfoot = document.getElementById('mgFoot');
        const badge = document.getElementById('mgCountBadge');
        if (!tbody) return;
        if (!rows.length) { showState('empty'); return; }

        tbody.innerHTML = rows.map(v => {
            const img = v.image_url
                ? `<img src="${esc(v.image_url)}" class="rounded" style="width:40px;height:40px;object-fit:cover;">`
                : '';
            return `
                <tr data-variant-id="${v.id}" style="cursor:pointer;" tabindex="0">
                    <td class="ps-4 fw-semibold">${esc(v.sku || '')}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${img ? `<div class="symbol symbol-40px me-3">${img}</div>` : ''}
                            <div>
                                <div class="fw-bold text-gray-800">${esc(v.product_name || v.name || '')}</div>
                                ${v.name && v.name !== v.product_name ? `<div class="text-muted fs-7">${esc(v.name)}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>${v.category_name ? `<span class="badge badge-light-info">${esc(v.category_name)}</span>` : `<span class="text-muted">—</span>`}</td>
                    <td class="text-center"><span class="badge badge-light-primary">${fmt(v.quantity)}</span></td>
                    <td class="text-end"><span class="text-gray-600">${CURRENCY} ${fmt(v.cost_price)}</span></td>
                    <td class="text-end"><span class="text-gray-800 fw-semibold">${CURRENCY} ${fmt(v.selling_price)}</span></td>
                    <td class="text-end"><span class="text-${v.margin_amount >= 0 ? 'success' : 'danger'} fw-semibold">${CURRENCY} ${fmt(v.margin_amount)}</span></td>
                    <td class="text-center">
                        <span class="badge badge-${v.margin_color}">${Number(v.margin_percentage).toFixed(1)}%</span>
                    </td>
                    <td class="text-end">
                        <span class="text-${v.margin_amount >= 0 ? 'success' : 'danger'} fw-bold">${CURRENCY} ${fmt(v.total_margin_value)}</span>
                    </td>
                    <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
                </tr>`;
        }).join('');

        if (badge) badge.textContent = `${rows.length} {{ __('auth.items') }}`;

        // Footer — page totals
        const pt = pageTotals || {};
        if (tfoot) {
            tfoot.innerHTML = `
                <tr>
                    <td colspan="3" class="text-end fw-bold">{{ __('accounting.current_page') }}:</td>
                    <td class="fw-bold text-center">${fmt(pt.quantity || 0)}</td>
                    <td colspan="4"></td>
                    <td class="fw-bold text-end text-${(pt.total_margin_value || 0) >= 0 ? 'success' : 'danger'}">${CURRENCY} ${fmt(pt.total_margin_value || 0)}</td>
                    <td></td>
                </tr>`;
        }

        // Row click → VariantDrawer
        tbody.querySelectorAll('tr[data-variant-id]').forEach(tr => {
            const go = (e) => {
                if (e && e.target.closest('button, a, input, select')) return;
                const id = parseInt(tr.dataset.variantId, 10);
                if (id && window.VariantDrawer) window.VariantDrawer.open(id);
            };
            tr.addEventListener('click', go);
            tr.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); }
            });
        });

        showState('table');
    }

    function renderPagination(p) {
        const info = document.getElementById('mgPageInfo');
        const ul   = document.getElementById('mgPagination');
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

    function renderCharts(c) {
        renderDistributionChart(c.distribution || {});
        renderTop10Chart(c.top10 || []);
    }

    function destroyChart(key) {
        if (charts[key]) { try { charts[key].destroy(); } catch (e) {} charts[key] = null; }
    }

    function renderDistributionChart(dist) {
        const el = document.querySelector('#marginDistributionChart');
        if (!el) return;
        destroyChart('dist');

        const data = [
            dist.high     || 0,
            dist.medium   || 0,
            dist.low      || 0,
            dist.very_low || 0,
        ];
        if (!data.some(v => v > 0)) {
            el.innerHTML = `<div class="text-center text-muted py-5">{{ __('accounting.no_data_available') }}</div>`;
            return;
        }

        charts.dist = new ApexCharts(el, {
            series: data,
            chart: { type: 'donut', height: 300, toolbar: { show: true } },
            labels: [
                '{{ __('auth.high_margin') }} (≥50%)',
                '{{ __('auth.medium_margin') }} (30–49%)',
                '{{ __('auth.low_margin') }} (10–29%)',
                '{{ __('auth.very_low_margin') }} (<10%)',
            ],
            colors: ['#50CD89', '#3E97FF', '#FFC700', '#F1416C'],
            legend: { position: 'bottom', fontSize: '11px' },
            tooltip: { y: { formatter: v => v + ' {{ __('auth.variants') }}' } },
            plotOptions: { pie: { donut: { size: '65%' } } },
        });
        charts.dist.render();
    }

    function renderTop10Chart(rows) {
        const el = document.querySelector('#topMarginChart');
        if (!el) return;
        destroyChart('top10');
        if (!rows.length) {
            el.innerHTML = `<div class="text-center text-muted py-5">{{ __('accounting.no_data_available') }}</div>`;
            return;
        }

        charts.top10 = new ApexCharts(el, {
            series: [{ name: '{{ __('auth.margin') }} %', data: rows.map(r => Number(r.margin).toFixed(1)) }],
            chart: { type: 'bar', height: 300, toolbar: { show: true } },
            plotOptions: { bar: { horizontal: true, borderRadius: 4, distributed: true } },
            xaxis: { categories: rows.map(r => r.name) },
            yaxis: { max: 100, labels: { formatter: v => v + '%' } },
            colors: rows.map(r => {
                if (r.color === 'success') return '#50CD89';
                if (r.color === 'primary') return '#3E97FF';
                if (r.color === 'warning') return '#FFC700';
                return '#F1416C';
            }),
            dataLabels: { enabled: true, formatter: v => v + '%' },
            legend: { show: false },
        });
        charts.top10.render();
    }

    function fmt(n) { return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.ProductMargin = { reload: loadData };
    return window.ProductMargin;
})();
</script>