<script>
window.StrategyReport = (function () {
    'use strict';

    const ROUTES = window.PRODUCTS_ROUTES;
    const BOOT   = window.ST_BOOTSTRAP || {};
    let CURRENCY = '';
    let metaCache = null;
    let activeController = null;
    let inFlight = false;

    const state = {
        strategy:      BOOT.strategy      || 'all',
        category_id:   BOOT.category_id   || '',
        search:        BOOT.search        || '',
        location_id:   BOOT.location_id   || '',
        department_id: BOOT.department_id || '',
        per_page:      Number(BOOT.per_page) || 15,
        page:          Number(BOOT.page)     || 1,
    };

    document.addEventListener('DOMContentLoaded', () => {
        seedForm();
        bind();
        loadMeta().then(() => loadData()).catch(() => loadData());
    });

    function bind() {
        document.getElementById('stFilterForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            readForm();
            state.page = 1;
            loadData();
        });

        document.getElementById('stClear')?.addEventListener('click', () => {
            state.strategy      = 'all';
            state.category_id   = '';
            state.search        = '';
            state.location_id   = '';
            state.department_id = '';
            state.per_page      = 15;
            state.page          = 1;
            seedForm();
            loadData();
        });

        document.getElementById('stPerPage')?.addEventListener('change', function () {
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
        const form = document.getElementById('stFilterForm');
        if (!form) return;
        Object.entries(state).forEach(([k, v]) => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) el.value = v == null ? '' : String(v);
        });
        const perPageEl = document.getElementById('stPerPage');
        if (perPageEl) perPageEl.value = String(state.per_page);
    }
    function readForm() {
        const form = document.getElementById('stFilterForm');
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
            const res = await fetch(ROUTES.strategyMeta, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Meta failed');
            metaCache = json;
            CURRENCY = json.currency || '';
            fillSelect('strategy',      json.strategies,  state.strategy);
            fillSelect('category_id',   json.categories,  state.category_id);
            fillSelect('location_id',   json.locations,   state.location_id);
            fillSelect('department_id', json.departments, state.department_id);
            return json;
        } catch (err) {
            console.error('[strategy] meta failed:', err);
            throw err;
        }
    }

    function fillSelect(name, items, selected) {
        const sel = document.querySelector(`#stFilterForm [name="${name}"]`);
        if (!sel) return;
        const ph = sel.querySelector('option[value=""]') || sel.querySelector('option[value="all"]');
        sel.innerHTML = '';
        if (ph) sel.appendChild(ph);
        (items || []).forEach(it => {
            const o = document.createElement('option');
            o.value = it.value ?? it.id;
            o.textContent = it.label ?? it.name;
            sel.appendChild(o);
        });
        sel.value = selected == null ? '' : String(selected);
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
            const res = await fetch(`${ROUTES.strategyData}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            CURRENCY = json.currency || CURRENCY;
            renderSummary(json.summary || {});
            renderTable(json.products || [], json.pagination || {});
            renderPagination(json.pagination || {});
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[strategy] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load strategy report.');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function showState(which) {
        document.getElementById('stLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('stWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('stEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function renderSummary(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('stStatProducts', fmt(s.total_products || 0));
        set('stStatValue',    `${CURRENCY} ${fmt(s.total_value || 0)}`);
        set('stStatInStock',  fmt(s.in_stock     || 0));
        set('stStatLowStock', fmt(s.low_stock    || 0));
        set('stStatOutStock', fmt(s.out_of_stock || 0));

        const badges = document.getElementById('stStrategyBadges');
        if (badges) {
            const colors = { quantity: 'primary', batch: 'info', serial: 'warning', recipe: 'success' };
            badges.innerHTML = ['quantity', 'batch', 'serial', 'recipe']
                .filter(k => (s[`${k}_strategy`] || 0) > 0)
                .map(k => `<span class="badge badge-light-${colors[k]} fs-7 py-2 px-3">${k.charAt(0).toUpperCase() + k.slice(1)}: ${fmt(s[`${k}_strategy`])}</span>`)
                .join('');
        }
    }

    function renderTable(rows, p) {
        const tbody = document.getElementById('stBody');
        const badge = document.getElementById('stCountBadge');
        if (!tbody) return;
        if (!rows.length) { showState('empty'); return; }

        const offset = ((p.current_page || 1) - 1) * (p.per_page || 15);

        tbody.innerHTML = rows.map((r, i) => {
            const img = r.image_url
                ? `<img src="${esc(r.image_url)}" class="rounded" style="width:40px;height:40px;object-fit:cover;">`
                : '';
            return `
                <tr data-product-id="${r.product_id}" style="cursor:pointer;" tabindex="0">
                    <td class="ps-4 text-muted">${offset + i + 1}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${img ? `<div class="symbol symbol-40px me-3">${img}</div>` : ''}
                            <div>
                                <div class="fw-bold text-gray-800">${esc(r.product_name || '')}</div>
                                <div class="text-muted fs-7">${esc(r.product_sku || '')}</div>
                            </div>
                        </div>
                    </td>
                    <td>${r.category_name ? `<span class="badge badge-light-primary">${esc(r.category_name)}</span>` : `<span class="text-muted">—</span>`}</td>
                    <td>
                        <span class="badge badge-light-${r.strategy_color} fs-7 py-2 px-3">
                            <i class="ki-duotone ${r.strategy_icon} fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                            ${esc(r.strategy_label)}
                        </span>
                    </td>
                    <td class="text-center"><span class="fw-bold">${fmt(r.variant_count)}</span></td>
                    <td class="text-center"><span class="fw-bold ${r.total_stock == 0 ? 'text-danger' : 'text-success'}">${fmt(r.total_stock)}</span></td>
                    <td class="text-center"><span class="fw-bold">${CURRENCY} ${fmt(r.total_value)}</span></td>
                    <td class="text-center"><span class="badge badge-light-${r.status_color} fs-7 py-2 px-3">${esc(r.status_label)}</span></td>
                    <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
                </tr>`;
        }).join('');

        if (badge) badge.textContent = `${rows.length} {{ __('pagination.items') }}`;

        tbody.querySelectorAll('tr[data-product-id]').forEach(tr => {
            const go = (e) => {
                if (e && e.target.closest('button, a, input, select')) return;
                const id = parseInt(tr.dataset.productId, 10);
                if (id && window.StrategyDrawer) window.StrategyDrawer.open(id);
            };
            tr.addEventListener('click', go);
            tr.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); }
            });
        });

        showState('table');
    }

    function renderPagination(p) {
        const info = document.getElementById('stPageInfo');
        const ul   = document.getElementById('stPagination');
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

    function fmt(n) { return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 }); }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.StrategyReport = { reload: loadData };
    return window.StrategyReport;
})();
</script>