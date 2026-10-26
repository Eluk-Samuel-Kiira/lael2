<script>
window.ProductList = (function () {
    'use strict';

    const URL_DATA = window.PRODUCT_ROUTES.data;
    const BOOT     = window.PRODUCT_BOOTSTRAP || {};

    const state = {
        page:    1,
        perPage: Number(BOOT.perPage || 15),
        search:  BOOT.search || '',
    };

    let searchDebounce;
    let inFlight = false;

    document.addEventListener('DOMContentLoaded', () => {
        bindFilters();
        bindRowClicks();
        load();
    });

    function bindFilters() {
        document.getElementById('productSearchInput')?.addEventListener('input', function () {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                state.search = this.value.trim();
                state.page = 1;
                load();
            }, 400);
        });

        document.getElementById('productPerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15;
            state.page = 1;
            load();
        });
    }

    function bindRowClicks() {
        const tbody = document.getElementById('prdTableBody');
        if (!tbody) return;
        tbody.addEventListener('click', (e) => {
            const row = e.target.closest('tr.prd-row[data-product-id]');
            if (!row) return;
            if (e.target.closest('button, a, select, input')) return;
            const id = parseInt(row.dataset.productId, 10);
            if (id && window.ProductDrawer) window.ProductDrawer.open(id);
        });
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showState('loading');

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.search) params.set('search', state.search);

        try {
            const res = await fetch(`${URL_DATA}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) { showState('empty'); return; }

            renderSummary(json.summary);
            if (!json.data || json.data.length === 0) { showState('empty'); return; }

            renderTable(json.data);
            renderPagination(json.pagination);
            showState('table');
        } catch (err) {
            console.error('[products] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load products.');
        } finally {
            inFlight = false;
        }
    }

    function renderSummary(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('prdSummaryTotal',    `${s.total ?? 0}`);
        set('prdSummaryActive',   `${s.active ?? 0}`);
        set('prdSummaryInactive', `${s.inactive ?? 0}`);
        set('prdSummaryTaxable',  `${s.taxable ?? 0}`);
    }

    function renderTable(rows) {
        const tbody = document.getElementById('prdTableBody');
        tbody.innerHTML = rows.map(row => `
            <tr class="prd-row" data-product-id="${row.id}">
                <td><div class="badge badge-light fw-bold">${esc(row.sku)}</div></td>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <div class="symbol symbol-40px">
                            <img src="${esc(row.image_url || '{{ asset('assets/media/stock/ecommerce/2.png') }}')}"
                                 alt="" class="symbol-label" style="object-fit:cover;">
                        </div>
                        <span class="fw-bold text-gray-800">${esc(row.name)}</span>
                    </div>
                </td>
                <td class="text-center"><span class="badge badge-light-primary">${row.variants_count}</span></td>
                <td>${esc(row.category_name)}</td>
                <td><span class="badge badge-light fw-bold">${esc(row.inventory_strategy)}</span></td>
                <td><span class="badge badge-light fw-bold">${esc(row.type)}</span></td>
                <td>
                    <span class="badge badge-light-${row.is_taxable ? 'success' : 'secondary'}">
                        ${row.is_taxable ? 'Yes' : 'No'}
                    </span>
                </td>
                <td><span class="badge badge-light fw-bold">${esc(row.creator)}</span></td>
                <td><span class="text-muted">${esc(row.created_at || '—')}</span></td>
                <td>
                    <span class="badge badge-light-${row.is_active ? 'success' : 'warning'}">
                        ${row.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(p) {
        const ul = document.getElementById('prdPagination');
        const info = document.getElementById('prdPaginationInfo');
        const wrap = document.getElementById('prdPaginationContainer');
        if (!ul || !info || !wrap) return;

        ul.innerHTML = '';
        info.textContent = `Showing ${p.from || 0} to ${p.to || 0} of ${p.total} entries`;
        wrap.classList.remove('d-none');

        const add = (page, text, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link'; a.href = '#';
            a.textContent = text;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                if (disabled || active) return;
                state.page = page; load();
            });
            li.appendChild(a); ul.appendChild(li);
        };

        add(p.current_page - 1, 'Prev', false, p.current_page <= 1);
        const start = Math.max(1, p.current_page - 2);
        const end   = Math.min(p.last_page, p.current_page + 2);
        if (start > 1) add(1, '1');
        if (start > 2) {
            const li = document.createElement('li');
            li.className = 'page-item disabled';
            li.innerHTML = '<span class="page-link">…</span>';
            ul.appendChild(li);
        }
        for (let i = start; i <= end; i++) add(i, i, i === p.current_page);
        if (end < p.last_page - 1) {
            const li = document.createElement('li');
            li.className = 'page-item disabled';
            li.innerHTML = '<span class="page-link">…</span>';
            ul.appendChild(li);
        }
        if (end < p.last_page) add(p.last_page, p.last_page);
        add(p.current_page + 1, 'Next', false, p.current_page >= p.last_page);
    }

    function showState(which) {
        document.getElementById('prdLoadingSpinner')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('prdTableContainer')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('prdNoDataMessage')?.classList.toggle('d-none', which !== 'empty');
        document.getElementById('prdPaginationContainer')?.classList.toggle('d-none', which !== 'table');
    }

    function resetFilters() {
        state.search = ''; state.page = 1;
        const s = document.getElementById('productSearchInput'); if (s) s.value = '';
        load();
    }

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t); return d.innerHTML;
    }

    window.ProductList = { load, reload: load, resetFilters };
    return window.ProductList;
})();
</script>