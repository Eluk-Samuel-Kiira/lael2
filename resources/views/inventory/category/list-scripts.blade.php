<script>
window.CategoryList = (function () {
    'use strict';

    const URL_DATA = window.CATEGORY_ROUTES.data;
    const BOOT     = window.CATEGORY_BOOTSTRAP || {};

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

    // ── Filter wiring ─────────────────────────────────────────
    function bindFilters() {
        document.getElementById('categorySearchInput')?.addEventListener('input', function () {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                state.search = this.value.trim();
                state.page = 1;
                load();
            }, 400);
        });

        document.getElementById('categoryPerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15;
            state.page = 1;
            load();
        });
    }

    // ── Row delegation ────────────────────────────────────────
    function bindRowActions() {
        const tbody = document.getElementById('categoryTableBody');
        if (!tbody) return;

        // Target the entire row
        tbody.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-category-id]');
            if (!row) return;

            // Ignore clicks on interactive elements inside the row (if you ever add them back)
            if (e.target.closest('button, a, select, input')) return;

            e.preventDefault();
            const id = parseInt(row.dataset.categoryId, 10);
            if (id && window.CategoryDrawer) {
                window.CategoryDrawer.open(id);
            }
        });
    }

    // ── Fetch + render ────────────────────────────────────────
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
            console.error('[categories] load failed:', err);
            showState('empty');
            window.toastr?.error?.('{{ __("passwords.failed_to_load_records") ?? "Failed to load records" }}');
        } finally {
            inFlight = false;
        }
    }

    function renderSummary(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('categorySummaryTotal',    `${s.total ?? 0}`);
        set('categorySummaryActive',   `${s.active ?? 0}`);
        set('categorySummaryInactive', `${s.inactive ?? 0}`);
    }

    function renderTable(rows) {
        const tbody = document.getElementById('categoryTableBody');
        tbody.innerHTML = rows.map(row => `
            <tr data-category-id="${row.id}" class="category-row" style="cursor: pointer;">
                <td>
                    <div class="badge badge-light fw-bold">ID-${row.id}</div>
                </td>
                <td>
                    <a href="javascript:void(0)" class="category-name-link fw-bold text-gray-800">
                        ${esc(row.name)}
                    </a>
                    ${row.description ? `<div class="text-muted fs-8">${esc(truncate(row.description, 60))}</div>` : ''}
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
        const ul = document.getElementById('categoryPagination');
        const info = document.getElementById('categoryPaginationInfo');
        const wrap = document.getElementById('categoryPaginationContainer');
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
            a.addEventListener('click', e => {
                e.preventDefault();
                if (!disabled && !active) gotoPage(page);
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

    function gotoPage(page) {
        if (page === state.page || page < 1) return;
        state.page = page;
        load();
        document.getElementById('categoryTableContainer')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function showState(which) {
        document.getElementById('categoryLoadingSpinner')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('categoryTableContainer')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('categoryNoDataMessage')?.classList.toggle('d-none', which !== 'empty');
        document.getElementById('categoryPaginationContainer')?.classList.toggle('d-none', which !== 'table');
    }

    function resetFilters() {
        state.search = '';
        state.page = 1;
        const s = document.getElementById('categorySearchInput'); if (s) s.value = '';
        load();
    }

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    function truncate(s, len) {
        return !s ? '' : (s.length > len ? s.substring(0, len) + '…' : s);
    }

    window.CategoryList = { load, reload: load, resetFilters };
    return window.CategoryList;
})();
</script>