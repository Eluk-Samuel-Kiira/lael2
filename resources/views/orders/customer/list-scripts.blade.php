<script>
window.CustomerList = (function () {
    'use strict';

    const URL_DATA = window.CUSTOMER_ROUTES.data;
    const BOOT     = window.CUSTOMER_BOOTSTRAP || {};

    const state = {
        page:    1,
        perPage: Number(BOOT.perPage) || 15,
        search:  BOOT.search || '',
        groupId: BOOT.groupId || '',
        status:  BOOT.status || '',
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
        document.getElementById('customerSearchInput')?.addEventListener('input', doSearch);
        document.getElementById('customerSearchTop')?.addEventListener('input', function () {
            const top = document.getElementById('customerSearchInput');
            if (top) top.value = this.value;
            doSearch.call(this);
        });

        document.getElementById('customerGroupFilter')?.addEventListener('change', function () {
            state.groupId = this.value; state.page = 1; load();
        });
        document.getElementById('customerStatusFilter')?.addEventListener('change', function () {
            state.status = this.value; state.page = 1; load();
        });
        document.getElementById('customerPerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15; state.page = 1; load();
        });

        document.getElementById('customerTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-customer-id]');
            if (!row) return;
            if (e.target.closest('button, a, select, input')) return;
            const id = parseInt(row.dataset.customerId, 10);
            if (id && window.CustomerDrawer) window.CustomerDrawer.open(id);
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
        if (state.groupId) params.set('group_id', state.groupId);
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
            console.error('[customer] load failed:', err);
            window.toastr?.error?.('Failed to load customers.');
            showState('empty');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function renderStats(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('statTotal',    s.total    ?? 0);
        set('statActive',   s.active   ?? 0);
        set('statInactive', s.inactive ?? 0);
    }

    function renderTable(rows) {
        const tbody = document.getElementById('customerTableBody');
        if (!tbody) return;

        tbody.innerHTML = rows.map(c => {
            const initials = ((c.first_name || 'C')[0] + (c.last_name || '')[0] || '').toUpperCase();

            return `
                <tr data-customer-id="${c.id}" style="cursor:pointer;">
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-40px symbol-circle me-3">
                                <div class="symbol-label bg-light-primary">
                                    <span class="fw-bold text-primary">${esc(initials)}</span>
                                </div>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-gray-800">${esc(c.full_name)}</span>
                                ${c.tax_number ? `<small class="text-muted">TIN: ${esc(c.tax_number)}</small>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex flex-column fs-7">
                            ${c.email ? `<span><i class="bi bi-envelope me-1"></i>${esc(c.email)}</span>` : ''}
                            ${c.phone ? `<span><i class="bi bi-phone me-1"></i>${esc(c.phone)}</span>` : ''}
                            ${(c.city || c.country_code)
                                ? `<small class="text-muted">${esc(c.city || '')}${c.city && c.country_code ? ', ' : ''}${esc(c.country_code || '')}</small>`
                                : ''}
                        </div>
                    </td>
                    <td>
                        ${c.group_name
                            ? `<span class="badge badge-light-info fw-bold">
                                   ${esc(c.group_name)}${c.group_discount > 0 ? ' · ' + Number(c.group_discount).toFixed(2) + '%' : ''}
                               </span>`
                            : `<span class="badge badge-light-secondary">—</span>`}
                    </td>
                    <td><span class="badge badge-light fw-bold">${esc(c.creator_name || '—')}</span></td>
                    <td>
                        <span class="badge badge-light-${c.is_active ? 'success' : 'warning'}">
                            ${c.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
                </tr>
            `;
        }).join('');
    }

    function renderPagination(p) {
        const info = document.getElementById('customerPageInfo');
        const ul   = document.getElementById('customerPagination');
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
        document.getElementById('customerLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('customerWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('customerEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.CustomerList = { load, reload: load };
    return window.CustomerList;
})();
</script>