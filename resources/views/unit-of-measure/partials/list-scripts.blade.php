<script>
window.UOMList = (function () {
    'use strict';

    const URL_DATA = window.UOM_ROUTES.data;
    const BOOT     = window.UOM_BOOTSTRAP || {};

    const state = {
        page:    1,
        perPage: Number(BOOT.perPage) || 15,
        search:  BOOT.search || '',
        status:  '',
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
        document.getElementById('uomSearchInput')?.addEventListener('input', doSearch);
        document.getElementById('uomSearchTop')?.addEventListener('input', function () {
            const top = document.getElementById('uomSearchInput');
            if (top) top.value = this.value;
            doSearch.call(this);
        });

        document.getElementById('uomStatusFilter')?.addEventListener('change', function () {
            state.status = this.value;
            state.page = 1;
            load();
        });

        document.getElementById('uomPerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15;
            state.page = 1;
            load();
        });

        // Row click → drawer
        document.getElementById('uomTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-uom-id]');
            if (!row) return;
            if (e.target.closest('button, a, select, input')) return;
            const id = parseInt(row.dataset.uomId, 10);
            if (id && window.UOMDrawer) window.UOMDrawer.open(id);
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
            console.error('[uom] load failed:', err);
            window.toastr?.error?.('Failed to load UOMs.');
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
    }

    function renderTable(rows) {
        const tbody = document.getElementById('uomTableBody');
        if (!tbody) return;

        tbody.innerHTML = rows.map(u => `
            <tr data-uom-id="${u.id}" style="cursor:pointer;">
                <td><span class="badge badge-light fw-bold">#${u.id}</span></td>
                <td><div class="fw-bold text-gray-800">${esc(u.name)}</div></td>
                <td><span class="badge badge-light-primary fw-bold">${esc(u.symbol || '—')}</span></td>
                <td><span class="badge badge-light fw-bold">${esc(u.creator || '—')}</span></td>
                <td class="text-muted fs-7">${esc(u.created_at || '—')}</td>
                <td>
                    <span class="badge badge-light-${u.is_active ? 'success' : 'warning'}">
                        ${u.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
            </tr>
        `).join('');
    }

    function renderPagination(p) {
        const info = document.getElementById('uomPageInfo');
        const ul   = document.getElementById('uomPagination');
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
        document.getElementById('uomLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('uomWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('uomEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.UOMList = { load, reload: load };
    return window.UOMList;
})();
</script>