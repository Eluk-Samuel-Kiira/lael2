<script>
window.ProductCategoryList = (function () {
    'use strict';

    const URL_DATA = window.PCATEGORY_ROUTES.data;
    const BOOT     = window.PCATEGORY_BOOTSTRAP || {};

    const state = {
        page:    1,
        perPage: Number(BOOT.perPage || 15),
        search:  BOOT.search || '',
    };

    let searchDebounce;
    let inFlight = false;

    document.addEventListener('DOMContentLoaded', () => {
        bindFilters();
        bindRowActions();
        load();
    });

    function bindFilters() {
        document.getElementById('subcategorySearchInput')?.addEventListener('input', function () {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => { state.search = this.value.trim(); state.page = 1; load(); }, 400);
        });
        document.getElementById('pcatPerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15; state.page = 1; load();
        });
    }

    function bindRowActions() {
        const tbody = document.getElementById('pcatTableBody');
        if (!tbody) return;

        tbody.addEventListener('click', (e) => {
            // Look for the closest row with a data-category-id
            const row = e.target.closest('tr[data-category-id]');
            if (!row) return;

            e.preventDefault();
            const id = parseInt(row.dataset.categoryId, 10);
            
            if (id && window.ProductCategoryDrawer) {
                window.ProductCategoryDrawer.open(id);
            }
        });
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showState('loading');

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.search) params.set('search', state.search);

        try {
            const res = await fetch(`${URL_DATA}?${params.toString()}`, {
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
            console.error('[pcategories] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load records');
        } finally {
            inFlight = false;
        }
    }

    function renderSummary(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('pcatSummaryTotal', `${s.total ?? 0}`);
        set('pcatSummaryActive', `${s.active ?? 0}`);
        set('pcatSummaryInactive', `${s.inactive ?? 0}`);
    }

    function renderTable(rows) {
        const tbody = document.getElementById('pcatTableBody');
        tbody.innerHTML = rows.map(row => `
            <tr data-category-id="${row.id}" class="pcat-row" style="cursor: pointer;">
                <td><div class="badge badge-light fw-bold">ID-${row.id}</div></td>
                <td>${esc(row.parent_name)}</td>
                <td>
                    <a href="javascript:void(0)" class="pcat-name-link fw-bold text-gray-800" data-category-id="${row.id}">
                        ${esc(row.name)}
                    </a>
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

    function renderPagination(p) { /* Copy the pagination function from your CategoryList */ }
    function gotoPage(page) { state.page = page; load(); document.getElementById('pcatTableContainer')?.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    function showState(which) {
        document.getElementById('pcatLoadingSpinner')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('pcatTableContainer')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('pcatNoDataMessage')?.classList.toggle('d-none', which !== 'empty');
        document.getElementById('pcatPaginationContainer')?.classList.toggle('d-none', which !== 'table');
    }
    function resetFilters() { state.search = ''; state.page = 1; const s = document.getElementById('subcategorySearchInput'); if (s) s.value = ''; load(); }
    function esc(t) { if (t === null || t === undefined) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.ProductCategoryList = { load, reload: load, resetFilters };
    return window.ProductCategoryList;
})();
</script>