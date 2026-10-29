<script>
window.ByCategory = (function () {
    'use strict';

    const ROUTES = window.PRODUCTS_ROUTES;
    const BOOT   = window.BC_BOOTSTRAP || {};
    let CURRENCY = '';
    let metaCache = null;
    let activeController = null;
    let inFlight = false;
    const charts = {};

    // Global ranking map — captured from the full sorted set so pagination
    // doesn't reset ranks on page 2+.
    let globalRanking = {};

    const state = {
        per_page: Number(BOOT.per_page) || 15,
        page:     Number(BOOT.page)     || 1,
    };

    document.addEventListener('DOMContentLoaded', () => {
        bind();
        loadMeta().then(() => loadData()).catch(() => loadData());
    });

    function bind() {
        document.getElementById('bcPerPage')?.addEventListener('change', function () {
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

    async function loadMeta() {
        if (metaCache) return metaCache;
        try {
            const res = await fetch(ROUTES.byCategoryMeta, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Meta failed');
            metaCache = json;
            CURRENCY = json.currency || '';
            return json;
        } catch (err) {
            console.error('[by-category] meta failed:', err);
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
        params.set('page', state.page);
        params.set('per_page', state.per_page);

        try {
            const res = await fetch(`${ROUTES.byCategoryData}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            CURRENCY = json.currency || CURRENCY;

            // Rebuild global ranking from charts.top_by_products only on page 1
            // (server returns full sorted set there); on subsequent pages we
            // rely on the first call having cached it.
            if (state.page === 1 || Object.keys(globalRanking).length === 0) {
                globalRanking = buildRanking(json);
            }

            renderSummary(json.summary || {});
            renderTable(json.categories || [], json.page_totals || {}, json.summary || {});
            renderPagination(json.pagination || {});
            renderCharts(json.charts || {});
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[by-category] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load category report.');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    /**
     * The server returns the full ranked list inside charts.top_by_products
     * but that's only the top 10. For true global ranking on ALL pages,
     * each row already carries its rank if the server includes it — else
     * we approximate with (page-1)*per_page + index + 1. To keep it exact,
     * the server should include `rank` per row. Until then, we compute
     * page-local rank only when the full set isn't known.
     */
    function buildRanking(json) {
        const map = {};
        const offset = ((json.pagination?.current_page || 1) - 1) * (json.pagination?.per_page || 15);
        (json.categories || []).forEach((c, i) => {
            map[c.id] = offset + i + 1;
        });
        return map;
    }

    function showState(which) {
        document.getElementById('bcLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('bcWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('bcEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function renderSummary(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('bcStatCategories', fmt(s.total_categories || 0));
        set('bcStatProducts',   fmt(s.total_products   || 0));
        set('bcStatVariants',   fmt(s.total_variants   || 0));
        set('bcStatStock',      fmt(s.total_stock      || 0));
        set('bcStatValue',      `${CURRENCY} ${fmt(s.total_value || 0)}`);
        set('bcStatMargin',     `${Number(s.average_margin || 0).toFixed(1)}%`);
    }

    function performanceOf(marginPct) {
        if (marginPct >= 50) return { key: 'excellent', label: '{{ __('auth.excellent') }}',        color: 'success' };
        if (marginPct >= 30) return { key: 'good',      label: '{{ __('auth.good') }}',             color: 'primary' };
        if (marginPct >= 10) return { key: 'average',   label: '{{ __('auth.average') }}',          color: 'warning' };
        return                     { key: 'poor',      label: '{{ __('auth.needs_improvement') }}', color: 'danger' };
    }

    function rankingBadge(rank) {
        if (rank === 1) return `<div class="d-flex align-items-center justify-content-center"><i class="ki-duotone ki-crown fs-2 me-1 text-warning"><span class="path1"></span><span class="path2"></span></i><span class="fw-bold text-warning">#1</span></div>`;
        if (rank === 2) return `<div class="d-flex align-items-center justify-content-center"><i class="ki-duotone ki-medal-star fs-2 me-1 text-info"><span class="path1"></span><span class="path2"></span></i><span class="fw-bold text-info">#2</span></div>`;
        if (rank === 3) return `<div class="d-flex align-items-center justify-content-center"><i class="ki-duotone ki-medal fs-2 me-1 text-danger"><span class="path1"></span><span class="path2"></span></i><span class="fw-bold text-danger">#3</span></div>`;
        return `<span class="badge badge-light-secondary">#${rank}</span>`;
    }

    function renderTable(rows, pageTotals, summary) {
        const tbody = document.getElementById('bcBody');
        const tfoot = document.getElementById('bcFoot');
        const badge = document.getElementById('bcCountBadge');
        if (!tbody) return;

        if (!rows.length) { showState('empty'); return; }

        tbody.innerHTML = rows.map(c => {
            const perf = performanceOf(c.margin_percentage);
            const rank = globalRanking[c.id] || 0;
            const img = c.image_url
                ? `<img src="${esc(c.image_url)}" class="rounded" style="width:40px;height:40px;object-fit:cover;">`
                : '';

            return `
                <tr data-category-id="${c.id}" style="cursor:pointer;" tabindex="0">
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            ${img ? `<div class="symbol symbol-50px me-3">${img}</div>` : ''}
                            <div>
                                <span class="fw-bold text-gray-800">${esc(c.name || '—')}</span>
                                ${c.description ? `<div class="text-muted fs-7">${esc((c.description || '').slice(0, 50))}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td class="text-center"><span class="badge badge-light-primary">${fmt(c.product_count)}</span></td>
                    <td class="text-center"><span class="badge badge-light-info">${fmt(c.variant_count)}</span></td>
                    <td class="text-center"><span class="badge badge-light-success">${fmt(c.total_stock)}</span></td>
                    <td class="text-end"><span class="text-warning fw-semibold">${CURRENCY} ${fmt(c.total_cost_value)}</span></td>
                    <td class="text-end"><span class="text-info fw-semibold">${CURRENCY} ${fmt(c.total_revenue_value)}</span></td>
                    <td class="text-end"><span class="text-${c.total_margin >= 0 ? 'success' : 'danger'} fw-bold">${CURRENCY} ${fmt(c.total_margin)}</span></td>
                    <td class="text-center"><span class="badge badge-light-${perf.color}">${Number(c.margin_percentage).toFixed(1)}%</span></td>
                    <td class="text-center"><span class="badge badge-${perf.color}">${perf.label}</span></td>
                    <td class="text-center">${rank ? rankingBadge(rank) : '—'}</td>
                </tr>`;
        }).join('');

        // after tbody.innerHTML = rows.map(…).join('');
        tbody.querySelectorAll('tr[data-category-id]').forEach(tr => {
            const go = (e) => {
                if (e && e.target.closest('button, a, input, select')) return;
                const id = parseInt(tr.dataset.categoryId, 10);
                if (id && window.CategoryDrawer) window.CategoryDrawer.open(id);
            };
            tr.addEventListener('click', go);
            tr.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); }
            });
        });

        if (badge) badge.textContent = `${rows.length} {{ __('auth.items') }}`;

        // Footer — page totals + grand total
        const pt = pageTotals || {};
        const s  = summary || {};
        const currentPageRow = `
            <tr>
                <td class="text-end fw-bold">{{ __('accounting.current_page') }}:</td>
                <td class="fw-bold text-center">${fmt(pt.product_count || 0)}</td>
                <td class="fw-bold text-center">${fmt(pt.variant_count || 0)}</td>
                <td class="fw-bold text-center">${fmt(pt.total_stock || 0)}</td>
                <td class="fw-bold text-end text-warning">${CURRENCY} ${fmt(pt.total_cost_value || 0)}</td>
                <td class="fw-bold text-end text-info">${CURRENCY} ${fmt(pt.total_revenue_value || 0)}</td>
                <td class="fw-bold text-end text-${(pt.total_margin || 0) >= 0 ? 'success' : 'danger'}">${CURRENCY} ${fmt(pt.total_margin || 0)}</td>
                <td colspan="3"></td>
            </tr>`;

        const grandRow = (s.total_categories || 0) > (pt.product_count || 0) ? `
            <tr class="border-top">
                <td class="text-end fw-bold">{{ __('auth.grand_total') }}:</td>
                <td class="fw-bold text-center">${fmt(s.total_products || 0)}</td>
                <td class="fw-bold text-center">${fmt(s.total_variants || 0)}</td>
                <td class="fw-bold text-center">${fmt(s.total_stock || 0)}</td>
                <td class="fw-bold text-end text-warning">${CURRENCY} ${fmt(s.total_cost_value || 0)}</td>
                <td class="fw-bold text-end text-info">${CURRENCY} ${fmt(s.total_value || 0)}</td>
                <td class="fw-bold text-end text-${(s.total_margin || 0) >= 0 ? 'success' : 'danger'}">${CURRENCY} ${fmt(s.total_margin || 0)}</td>
                <td colspan="3"></td>
            </tr>` : '';

        if (tfoot) tfoot.innerHTML = currentPageRow + grandRow;

        showState('table');
    }

    function renderPagination(p) {
        const info = document.getElementById('bcPageInfo');
        const ul   = document.getElementById('bcPagination');
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
        renderTopByProductsChart(c.top_by_products || []);
        renderValueDistributionChart(c.top_by_value || []);
    }

    function destroyChart(key) {
        if (charts[key]) { try { charts[key].destroy(); } catch (e) {} charts[key] = null; }
    }

    function renderTopByProductsChart(rows) {
        const el = document.querySelector('#topCategoriesChart');
        if (!el) return;
        destroyChart('topProducts');
        if (!rows.length) {
            el.innerHTML = `<div class="text-center text-muted py-5">{{ __('accounting.no_data_available') }}</div>`;
            return;
        }

        charts.topProducts = new ApexCharts(el, {
            series: [{ name: '{{ __('auth.product_count') }}', data: rows.map(r => r.product_count) }],
            chart: { type: 'bar', height: 300, toolbar: { show: true } },
            plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
            xaxis: { categories: rows.map(r => r.name), labels: { style: { fontSize: '11px' } } },
            yaxis: { title: { text: '{{ __('auth.number_of_products') }}' } },
            colors: ['#3E97FF'],
            tooltip: {
                y: {
                    formatter: (v, { dataPointIndex }) => {
                        const c = rows[dataPointIndex] || {};
                        return `${v} {{ __('auth.products') }}<br>` +
                               `{{ __('auth.variants') }}: ${c.variant_count || 0}<br>` +
                               `{{ __('auth.total_stock') }}: ${fmt(c.total_stock || 0)}`;
                    },
                },
            },
            dataLabels: { enabled: false },
        });
        charts.topProducts.render();
    }

    function renderValueDistributionChart(rows) {
        const el = document.querySelector('#valueDistributionChart');
        if (!el) return;
        destroyChart('valueDist');
        if (!rows.length) {
            el.innerHTML = `<div class="text-center text-muted py-5">{{ __('accounting.no_data_available') }}</div>`;
            return;
        }

        charts.valueDist = new ApexCharts(el, {
            series: rows.map(r => r.total_revenue_value),
            chart: { type: 'pie', height: 300, toolbar: { show: true } },
            labels: rows.map(r => r.name),
            colors: ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C', '#A8A8A8', '#009EF7', '#FFA800', '#181C32', '#6D6D6D'],
            legend: { position: 'bottom', fontSize: '11px' },
            tooltip: {
                y: {
                    formatter: (v, { dataPointIndex }) => {
                        const c = rows[dataPointIndex] || {};
                        return `${CURRENCY} ${fmt(v)}<br>` +
                               `{{ __('auth.margin') }}: ${Number(c.margin_percentage || 0).toFixed(1)}%<br>` +
                               `{{ __('auth.products') }}: ${c.product_count || 0}`;
                    },
                },
            },
            dataLabels: {
                enabled: true,
                formatter: (val, opts) => opts.w.config.labels[opts.seriesIndex],
            },
        });
        charts.valueDist.render();
    }

    function fmt(n) {
        return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.ByCategory = { reload: loadData };
    return window.ByCategory;
})();
</script>