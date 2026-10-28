<script>
window.SupplierList = (function () {
    'use strict';

    const URL_DATA = window.SUPPLIER_ROUTES.data;
    const BOOT     = window.SUPPLIER_BOOTSTRAP || {};

    const state = {
        page:     1,
        perPage:  Number(BOOT.perPage) || 15,
        search:   BOOT.search || '',
        status:   '',
        type:     '',
    };

    let debounce, inFlight = false, activeController = null;

    document.addEventListener('DOMContentLoaded', () => {
        bind();
        load();
    });

    function bind() {
        const onSearch = function () {
            clearTimeout(debounce);
            debounce = setTimeout(() => {
                state.search = this.value.trim();
                state.page = 1;
                load();
            }, 400);
        };
        document.getElementById('supplierSearchInput')?.addEventListener('input', onSearch);
        document.getElementById('supplierSearchTop')?.addEventListener('input', function () {
            const top = document.getElementById('supplierSearchInput');
            if (top) top.value = this.value;
            onSearch.call(this);
        });

        document.getElementById('supplierStatusFilter')?.addEventListener('change', function () {
            state.status = this.value;
            state.page = 1;
            load();
        });

        document.getElementById('supplierTypeFilter')?.addEventListener('change', function () {
            state.type = this.value;
            state.page = 1;
            load();
        });

        document.getElementById('supplierPerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15;
            state.page = 1;
            load();
        });

        // Row click → drawer
        document.getElementById('supplierTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-supplier-id]');
            if (!row) return;
            if (e.target.closest('button, a, select, input')) return;
            const id = parseInt(row.dataset.supplierId, 10);
            if (id && window.SupplierDrawer) window.SupplierDrawer.open(id);
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
        if (state.type)   params.set('supplier_type', state.type);

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
            console.error('[suppliers] load failed:', err);
            window.toastr?.error?.('Failed to load suppliers.');
            showState('empty');
        } finally {
            if (activeController === controller) {
                inFlight = false;
                activeController = null;
            }
        }
    }

    function renderStats(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('statTotal',    s.total    ?? 0);
        set('statActive',   s.active   ?? 0);
        set('statInactive', s.inactive ?? 0);
        set('statWht',      s.wht      ?? 0);
    }

    function renderTable(rows) {
        const tbody = document.getElementById('supplierTableBody');
        if (!tbody) return;

        tbody.innerHTML = rows.map(s => `
            <tr data-supplier-id="${s.id}" style="cursor:pointer;">
                <td>
                    <div class="fw-bold text-gray-800">${esc(s.name)}</div>
                    ${s.trading_name ? `<small class="text-muted">${esc(s.trading_name)}</small>` : ''}
                    ${s.supplier_code ? `<small class="badge badge-light-dark ms-2">${esc(s.supplier_code)}</small>` : ''}
                </td>
                <td>${esc(s.contact_person || '—')}</td>
                <td>${esc(s.email || '—')}</td>
                <td>${esc(s.phone || '—')}</td>
                <td>
                    <span class="badge badge-light-${typeColor(s.supplier_type)}">
                        ${esc(s.supplier_type)}
                    </span>
                </td>
                <td><span class="badge badge-light fw-bold">${esc(s.creator_name || '—')}</span></td>
                <td class="text-muted fs-7">${esc(s.created_at || '—')}</td>
                <td>
                    <span class="badge badge-light-${s.is_active ? 'success' : 'warning'}">
                        ${s.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
            </tr>
        `).join('');
    }

    function typeColor(t) {
        return { company: 'primary', individual: 'info', government: 'warning', ngo: 'success', foreign: 'danger' }[t] || 'secondary';
    }

    function renderPagination(p) {
        const info = document.getElementById('supplierPageInfo');
        const ul   = document.getElementById('supplierPagination');
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
                    state.page = page;
                    load();
                });
            }
            li.appendChild(a);
            ul.appendChild(li);
        };

        addPage(p.current_page - 1, 'Prev', false, p.current_page <= 1);
        for (let i = Math.max(1, p.current_page - 2); i <= Math.min(p.last_page, p.current_page + 2); i++) {
            addPage(i, String(i), i === p.current_page);
        }
        addPage(p.current_page + 1, 'Next', false, !p.has_more);
    }

    function showState(which) {
        document.getElementById('supplierLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('supplierWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('supplierEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.SupplierList = { load, reload: load };
    return window.SupplierList;
})();
</script>