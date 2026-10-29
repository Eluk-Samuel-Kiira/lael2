<script>
window.ProductVariantList = (function () {
    'use strict';

    /**
     * Build a list controller for a specific report page.
     * @param {Object} options
     * @param {string} options.dataUrl       — route to ProductsController@variantsData
     * @param {string} options.tbodyId
     * @param {string} options.infoId
     * @param {string} options.paginationId
     * @param {string} options.loadingId
     * @param {string} options.wrapperId
     * @param {string} options.emptyId
     * @param {Function} options.mapRow      — (json, row) => `<tr>…</tr>`
     * @param {Function} [options.extraState] — () => extra query params
     */
    return function createList(options) {
        const state = { page: 1, per_page: 15, ...(options.initialState || {}) };

        async function load() {
            showState('loading');
            const params = new URLSearchParams();
            Object.entries(state).forEach(([k, v]) => {
                if (v !== '' && v != null) params.set(k, v);
            });
            try {
                const res = await fetch(`${options.dataUrl}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const json = await res.json();
                if (!json.success) throw new Error(json.message || 'Failed');
                const tbody = document.getElementById(options.tbodyId);
                if (!tbody) return;

                if (!json.variants || !json.variants.length) {
                    tbody.innerHTML = '';
                    showState('empty');
                    return;
                }

                tbody.innerHTML = json.variants.map(row => options.mapRow(json, row)).join('');
                renderPagination(json.pagination, json.currency);
                showState('table');
                bindRowClicks();
            } catch (err) {
                console.error('[variants-list] load failed:', err);
                window.toastr?.error?.(err.message || 'Failed to load.');
                showState('empty');
            }
        }

        function bindRowClicks() {
            const tbody = document.getElementById(options.tbodyId);
            if (!tbody) return;
            tbody.querySelectorAll('tr[data-variant-id]').forEach(tr => {
                tr.addEventListener('click', (e) => {
                    if (e.target.closest('button, a, input, select')) return;
                    const id = parseInt(tr.dataset.variantId, 10);
                    if (id && window.VariantDrawer) window.VariantDrawer.open(id);
                });
            });
        }

        function renderPagination(p, currency) {
            const info = document.getElementById(options.infoId);
            const ul   = document.getElementById(options.paginationId);
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
                    ev.preventDefault(); state.page = page; load();
                });
                li.appendChild(a); ul.appendChild(li);
            };
            addPage(p.current_page - 1, 'Prev', false, p.current_page <= 1);
            const start = Math.max(1, p.current_page - 2);
            const end   = Math.min(p.last_page, p.current_page + 2);
            for (let i = start; i <= end; i++) addPage(i, String(i), i === p.current_page);
            addPage(p.current_page + 1, 'Next', false, !p.has_more);
        }

        function showState(which) {
            document.getElementById(options.loadingId)?.classList.toggle('d-none', which !== 'loading');
            document.getElementById(options.wrapperId)?.classList.toggle('d-none', which !== 'table');
            document.getElementById(options.emptyId)?.classList.toggle('d-none',   which !== 'empty');
        }

        function setState(partial) {
            Object.assign(state, partial);
            state.page = 1;
            load();
        }

        function setPerPage(n) {
            state.per_page = parseInt(n, 10) || 15;
            state.page     = 1;
            load();
        }

        return { load, setState, setPerPage, state };
    };
})();
</script>