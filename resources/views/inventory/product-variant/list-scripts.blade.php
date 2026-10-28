<script>
window.VariantList = (function () {
    'use strict';

    const URL_DATA = window.VARIANT_ROUTES.data;
    const BOOT     = window.VARIANT_BOOTSTRAP || {};

    const state = {
        page:      1,
        perPage:   15,
        search:    '',
        productId: BOOT.productId || null,
    };

    let searchDebounce;
    let inFlight = false;

    document.addEventListener('DOMContentLoaded', () => {
        bind();
        load();
    });

    function bind() {
        document.getElementById('variantSearchInput')?.addEventListener('input', function () {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                state.search = this.value.trim();
                state.page = 1;
                load();
            }, 400);
        });

        document.getElementById('variantPerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15;
            state.page = 1;
            load();
        });

        document.getElementById('varTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.var-row[data-variant-id]');
            if (!row) return;
            if (e.target.closest('button, a, select, input')) return;
            const id = parseInt(row.dataset.variantId, 10);
            if (id && window.VariantDrawer) window.VariantDrawer.open(id);
        });
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showState('loading');

        const params = new URLSearchParams({
            product_id: state.productId,
            page: state.page,
            per_page: state.perPage,
        });
        if (state.search) params.set('search', state.search);

        try {
            const res = await fetch(`${URL_DATA}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) { showState('empty'); return; }

            if (!json.data || json.data.length === 0) { showState('empty'); return; }

            renderTable(json.data);
            renderPagination(json.pagination);
            showState('table');
        } catch (err) {
            console.error('[variants] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load variants.');
        } finally {
            inFlight = false;
        }
    }

    function renderTable(rows) {
        const tbody = document.getElementById('varTableBody');
        tbody.innerHTML = rows.map(row => `
            <tr class="var-row" data-variant-id="${row.id}">
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <div class="symbol symbol-40px">
                            <img src="${esc(row.image_url || '')}" class="symbol-label" style="object-fit:cover;">
                        </div>
                        <span class="fw-bold text-gray-800">${esc(row.name)}</span>
                    </div>
                </td>
                <td><span class="badge badge-light fw-bold">${esc(row.sku || '—')}</span></td>
                <td><code class="text-muted">${esc(row.barcode || '—')}</code></td>
                <td class="text-end fw-bold text-warning">${num(row.overall_quantity)}</td>
                <td class="text-end fw-bold text-primary">${money(row.selling_price)}</td>
                <td class="text-end">
                    <span class="badge badge-light-success fw-bold">${money(row.grand_total_cost_price)}</span>
                </td>
                <td class="text-end">
                    <span class="badge badge-light-info fw-bold">${money(row.discount_selling_price)}</span>
                </td>
                <td class="text-center">
                    <span class="badge badge-light-${row.is_taxable ? 'success' : 'secondary'}">
                        ${row.is_taxable ? 'Yes' : 'No'}
                    </span>
                </td>
                <td>
                    <span class="badge badge-light-${row.is_active ? 'success' : 'warning'}">
                        ${row.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(p) {
        const ul   = document.getElementById('varPagination');
        const info = document.getElementById('varPaginationInfo');
        const wrap = document.getElementById('varPaginationContainer');
        if (!ul || !info || !wrap) return;

        ul.innerHTML = '';
        info.textContent = `Showing ${p.from || 0}–${p.to || 0} of ${p.total}`;
        wrap.classList.remove('d-none');

        const add = (page, label, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = label;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                if (disabled || active) return;
                state.page = page;
                load();
            });
            li.appendChild(a);
            ul.appendChild(li);
        };

        add(p.current_page - 1, 'Prev', false, p.current_page <= 1);
        const start = Math.max(1, p.current_page - 2);
        const end   = Math.min(p.last_page, p.current_page + 2);
        for (let i = start; i <= end; i++) add(i, i, i === p.current_page);
        add(p.current_page + 1, 'Next', false, p.current_page >= p.last_page);
    }

    function showState(which) {
        document.getElementById('varLoadingSpinner')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('varTableContainer')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('varNoDataMessage')?.classList.toggle('d-none', which !== 'empty');
    }

    function resetFilters() {
        state.search = ''; state.page = 1;
        const s = document.getElementById('variantSearchInput'); if (s) s.value = '';
        load();
    }

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }
    function num(n) { return Number(n ?? 0).toLocaleString('en-US'); }
    function money(n) { return (window.VARIANT_CURRENCY || '') + Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    window.VariantList = { load, reload: load, resetFilters };
    return window.VariantList;
})();
</script>