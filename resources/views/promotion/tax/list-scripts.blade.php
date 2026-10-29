<script>
window.TaxList = (function () {
    'use strict';

    const URL_DATA = window.TAX_ROUTES.data;
    const BOOT     = window.TAX_BOOTSTRAP || {};

    const state = {
        page:    1,
        perPage: Number(BOOT.perPage) || 15,
        search:  BOOT.search || '',
        status:  '',
        type:    '',
    };

    let debounce, inFlight = false, activeController = null;

    document.addEventListener('DOMContentLoaded', () => {
        bind();
        load();
    });

    function bind() {
        const doSearch = function () {
            clearTimeout(debounce);
            debounce = setTimeout(() => {
                state.search = this.value.trim();
                state.page = 1;
                load();
            }, 400);
        };
        document.getElementById('taxSearchInput')?.addEventListener('input', doSearch);
        document.getElementById('taxSearchTop')?.addEventListener('input', function () {
            const top = document.getElementById('taxSearchInput');
            if (top) top.value = this.value;
            doSearch.call(this);
        });

        document.getElementById('taxStatusFilter')?.addEventListener('change', function () {
            state.status = this.value; state.page = 1; load();
        });
        document.getElementById('taxTypeFilter')?.addEventListener('change', function () {
            state.type = this.value; state.page = 1; load();
        });
        document.getElementById('taxPerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15; state.page = 1; load();
        });

        document.getElementById('taxTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-tax-id]');
            if (!row) return;
            if (e.target.closest('button, a, select, input')) return;
            const id = parseInt(row.dataset.taxId, 10);
            if (id && window.TaxDrawer) window.TaxDrawer.open(id);
        });
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;

        if (activeController) activeController.abort();
        const controller = new AbortController();
        activeController = controller;

        showState('loading');

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.search) params.set('search', state.search);
        if (state.status !== '') params.set('status', state.status);
        if (state.type) params.set('type', state.type);

        try {
            const res = await fetch(`${URL_DATA}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            renderStats(json.summary);
            renderTable(json.data);
            renderPagination(json.pagination);
            showState(json.data.length ? 'table' : 'empty');
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[tax] load failed:', err);
            window.toastr?.error?.('Failed to load taxes.');
            showState('empty');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function renderStats(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('statTotal',    s.total       ?? 0);
        set('statActive',   s.active      ?? 0);
        set('statInactive', s.inactive    ?? 0);
        set('statWht',      s.withholding ?? 0);
    }

    function renderTable(rows) {
        const tbody = document.getElementById('taxTableBody');
        if (!tbody) return;

        tbody.innerHTML = rows.map(t => `
            <tr data-tax-id="${t.id}" style="cursor:pointer;">
                <td>
                    <div class="fw-bold text-gray-800">${esc(t.name)}</div>
                    ${t.is_withholding_tax ? `<span class="badge badge-light-danger fs-8 mt-1">Withholding</span>` : ''}
                </td>
                <td><span class="badge badge-light-dark fw-bold">${esc(t.code)}</span></td>
                <td class="text-end fw-bold">
                    ${t.type === 'percentage'
                        ? `${Number(t.rate).toFixed(2)}%`
                        : money(t.rate)}
                </td>
                <td><span class="badge badge-light-${t.type === 'percentage' ? 'primary' : 'info'} fs-8">${esc(t.type)}</span></td>
                <td class="text-center">
                    ${t.is_withholding_tax
                        ? `<i class="bi bi-check-circle-fill text-danger"></i>`
                        : `<span class="text-muted">—</span>`}
                </td>
                <td><span class="badge badge-light fw-bold">${esc(t.creator_name || '—')}</span></td>
                <td>
                    <span class="badge badge-light-${t.is_active ? 'success' : 'warning'}">
                        ${t.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
            </tr>
        `).join('');
    }

    function renderPagination(p) {
        const info = document.getElementById('taxPageInfo');
        const ul   = document.getElementById('taxPagination');
        if (!info || !ul) return;

        info.textContent = `Showing ${p.from || 0}–${p.to || 0} of ${p.total || 0}`;
        ul.innerHTML = '';

        const addPage = (page, label, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = label;
            if (!disabled) {
                a.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    state.page = page; load();
                });
            }
            li.appendChild(a); ul.appendChild(li);
        };

        addPage(p.current_page - 1, 'Prev', false, p.current_page <= 1);
        for (let i = Math.max(1, p.current_page - 2); i <= Math.min(p.last_page, p.current_page + 2); i++) {
            addPage(i, String(i), i === p.current_page);
        }
        addPage(p.current_page + 1, 'Next', false, !p.has_more);
    }

    function showState(which) {
        document.getElementById('taxLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('taxWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('taxEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }
    function money(n) {
        return '{{ currency_symbol() }}' + Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    window.TaxList = { load, reload: load };
    return window.TaxList;
})();
</script>