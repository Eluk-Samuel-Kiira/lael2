<script>
window.StockMovement = (function () {
    'use strict';

    const ROUTES = window.PRODUCTS_ROUTES;
    const BOOT   = window.SM_BOOTSTRAP || {};
    let CURRENCY = '';
    let metaCache = null;
    let activeController = null;
    let inFlight = false;
    const charts = {};

    const state = {
        category_id: BOOT.category_id || '',
        days:        Number(BOOT.days) || 30,
        per_page:    Number(BOOT.per_page) || 15,
        page:        Number(BOOT.page) || 1,
    };

    document.addEventListener('DOMContentLoaded', () => {
        seedForm();
        bind();
        loadMeta().then(() => loadData()).catch(() => loadData());
    });

    function bind() {
        document.getElementById('smFilterForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            readForm();
            state.page = 1;
            loadData();
        });

        document.getElementById('smClear')?.addEventListener('click', () => {
            state.category_id = '';
            state.days        = 30;
            state.per_page    = 15;
            state.page        = 1;
            seedForm();
            loadData();
        });

        document.getElementById('smDays')?.addEventListener('change', function () {
            state.days = parseInt(this.value, 10) || 30;
            state.page = 1;
            loadData();
        });

        document.getElementById('smPerPage')?.addEventListener('change', function () {
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
        const form = document.getElementById('smFilterForm');
        if (!form) return;
        Object.entries(state).forEach(([k, v]) => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) el.value = v == null ? '' : String(v);
        });
        const perPageEl = document.getElementById('smPerPage');
        if (perPageEl) perPageEl.value = String(state.per_page);
    }
    function readForm() {
        const form = document.getElementById('smFilterForm');
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
            const res = await fetch(ROUTES.stockMovementMeta, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Meta failed');
            metaCache = json;
            CURRENCY = json.currency || '';
            const catSel = document.querySelector('#smFilterForm [name="category_id"]');
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
            console.error('[stock-movement] meta failed:', err);
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
            const res = await fetch(`${ROUTES.stockMovementData}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            CURRENCY = json.currency || CURRENCY;
            renderSummary(json.summary || {});
            renderTable(json.variants || []);
            renderPagination(json.pagination || {});
            renderCharts(json.charts || {}, json.summary || {});
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[stock-movement] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load stock movement.');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function showState(which) {
        document.getElementById('smLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('smWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('smEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function renderSummary(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        // IDs must match the HTML summary cards
        set('statRecent',     fmt(s.recent_count       || 0));
        set('statActive',     fmt(s.active_count       || 0));
        set('statStale',      fmt(s.stale_count        || 0));
        set('statNewMonth',   fmt(s.new_this_month     || 0));
        set('statUpdatedWeek',fmt(s.updated_this_week  || 0));
        set('statTotalItems', fmt(s.total_items        || 0));
    }

    function renderTable(rows) {
        const tbody = document.getElementById('smBody');
        const badge = document.getElementById('smCountBadge');
        if (!tbody) return;
        if (!rows.length) { showState('empty'); return; }

        tbody.innerHTML = rows.map(v => {
            const duColor = v.days_since_update <= 7 ? 'success' : (v.days_since_update <= 30 ? 'warning' : 'danger');
            const img = v.image_url
                ? `<img src="${esc(v.image_url)}" class="rounded" style="width:40px;height:40px;object-fit:cover;">`
                : '';
            return `
                <tr data-variant-id="${v.id}" style="cursor:pointer;" tabindex="0">
                    <td class="ps-4 fw-semibold">${esc(v.sku || '')}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${img ? `<div class="symbol symbol-50px me-3">${img}</div>` : ''}
                            <div>
                                <div class="fw-bold text-gray-800">${esc(v.product_name || v.name)}</div>
                                ${v.name && v.name !== v.product_name ? `<div class="text-muted fs-7">${esc(v.name)}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>${v.category_name ? `<span class="badge badge-light-info">${esc(v.category_name)}</span>` : `<span class="text-muted">-</span>`}</td>
                    <td class="text-center"><span class="badge badge-light-primary">${fmt(v.quantity)}</span></td>
                    <td><span class="text-muted">${esc(v.created_at || '')}</span></td>
                    <td><span class="text-gray-800 fw-semibold">${esc(v.updated_at || '')}</span></td>
                    <td class="text-center"><span class="badge badge-light-${duColor}">${v.days_since_update} {{ __('auth.days') }}</span></td>
                    <td class="text-center"><span class="badge badge-light-info">${v.days_since_creation} {{ __('auth.days') }}</span></td>
                    <td class="text-center"><span class="badge badge-${v.movement_color || 'secondary'}">${esc(v.movement_label || '')}</span></td>
                    <td class="text-end">
                        <i class="ki-duotone ki-${v.activity_icon || 'check-circle'} fs-2 text-${v.movement_color || 'secondary'}">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </td>
                </tr>`;
        }).join('');

        if (badge) badge.textContent = `${rows.length} {{ __('auth.items') }}`;

        // Row click → VariantDrawer
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
        const info = document.getElementById('smPageInfo');
        const ul   = document.getElementById('smPagination');
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
    function renderCharts(c, s) {
        renderMovementStatusChart(s);
        renderActivityTimelineChart(c.timeline || {});
    }

    function destroyChart(key) {
        if (charts[key]) { try { charts[key].destroy(); } catch (e) {} charts[key] = null; }
    }

    function renderMovementStatusChart(s) {
        const el = document.querySelector('#movementStatusChart');
        if (!el) return;
        destroyChart('movementStatus');

        const recent = Number(s.recent_count || 0);
        const active = Number(s.active_count || 0);
        const stale  = Number(s.stale_count  || 0);

        if (recent + active + stale === 0) {
            el.innerHTML = `<div class="text-center text-muted py-5">{{ __('accounting.no_data_available') }}</div>`;
            return;
        }

        charts.movementStatus = new ApexCharts(el, {
            series: [recent, active, stale],
            chart: { type: 'pie', height: 300, toolbar: { show: true } },
            labels: [
                '{{ __('auth.recently_updated') }} (≤7 {{ __('auth.days') }})',
                '{{ __('auth.active') }} (8-30 {{ __('auth.days') }})',
                '{{ __('auth.stale') }} (>30 {{ __('auth.days') }})',
            ],
            colors: ['#50CD89', '#3E97FF', '#FFC700'],
            legend: { position: 'bottom', fontSize: '12px' },
            tooltip: { y: { formatter: v => v + ' {{ __('auth.items') }}' } },
            dataLabels: {
                enabled: true,
                formatter: (val, opts) => {
                    const total = recent + active + stale;
                    const pct = total > 0 ? ((opts.w.config.series[opts.seriesIndex] / total) * 100).toFixed(1) : '0.0';
                    return `${opts.w.config.series[opts.seriesIndex]} (${pct}%)`;
                },
            },
        });
        charts.movementStatus.render();
    }

    function renderActivityTimelineChart(timeline) {
        const el = document.querySelector('#activityTimelineChart');
        if (!el) return;
        destroyChart('timeline');

        const labels = timeline.labels || [];
        const data   = timeline.data   || [];

        if (!labels.length || !data.some(v => v > 0)) {
            el.innerHTML = `<div class="text-center text-muted py-5">{{ __('accounting.no_data_available') }}</div>`;
            return;
        }

        charts.timeline = new ApexCharts(el, {
            series: [{ name: '{{ __('auth.updates') }}', data }],
            chart: { type: 'line', height: 300, toolbar: { show: true }, zoom: { enabled: true } },
            stroke: { curve: 'smooth', width: 3 },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light', type: 'vertical', shadeIntensity: 0.3,
                    gradientToColors: ['#3E97FF'], inverseColors: false,
                    opacityFrom: 0.8, opacityTo: 0.2, stops: [0, 100],
                },
            },
            xaxis: {
                categories: labels,
                labels: { rotate: -45, style: { fontSize: '10px' } },
            },
            yaxis: { title: { text: '{{ __('auth.number_of_updates') }}' }, min: 0 },
            colors: ['#3E97FF'],
            tooltip: {
                y: { formatter: v => `${v} {{ __('auth.update') }}${v !== 1 ? 's' : ''}` },
            },
            markers: {
                size: 5, colors: ['#3E97FF'], strokeColors: '#fff',
                strokeWidth: 2, hover: { size: 7 },
            },
            grid: {
                borderColor: '#e7e7e7',
                row: { colors: ['#f3f3f3', 'transparent'], opacity: 0.5 },
            },
        });
        charts.timeline.render();
    }

    function fmt(n) {
        return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.StockMovement = { reload: loadData };
    return window.StockMovement;
})();
</script>