<script>
window.PMList = (function () {
    'use strict';

    const URL_DATA = window.PM_ROUTES.data;
    const BOOT     = window.PM_BOOTSTRAP || {};

    const state = {
        page:       1,
        perPage:    Number(BOOT.perPage) || 15,
        search:     BOOT.search || '',
        status:     '',
        locationId: BOOT.locationId || '',
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
        document.getElementById('pmSearchInput')?.addEventListener('input', doSearch);
        document.getElementById('pmSearchTop')?.addEventListener('input', function () {
            const top = document.getElementById('pmSearchInput');
            if (top) top.value = this.value;
            doSearch.call(this);
        });

        document.getElementById('pmStatusFilter')?.addEventListener('change', function () {
            state.status = this.value; state.page = 1; load();
        });
        document.getElementById('pmLocationFilter')?.addEventListener('change', function () {
            state.locationId = this.value; state.page = 1; load();
        });
        document.getElementById('pmPerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15; state.page = 1; load();
        });

        document.getElementById('pmTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-pm-id]');
            if (!row) return;
            if (e.target.closest('button, a, select, input')) return;
            const id = parseInt(row.dataset.pmId, 10);
            if (id && window.PMDrawer) window.PMDrawer.open(id);
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
        if (state.locationId) params.set('location_id', state.locationId);

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
            console.error('[payment-method] load failed:', err);
            window.toastr?.error?.('Failed to load payment methods.');
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
        set('statBalance',  money(s.total_balance ?? 0));
    }

    function renderTable(rows) {
        const tbody = document.getElementById('pmTableBody');
        if (!tbody) return;

        const typeColor = {
            bank_account:   'info',
            mobile_money:   'warning',
            cash:           'success',
            card:           'primary',
            digital_wallet: 'info',
            check:          'secondary',
            other:          'dark',
        };

        tbody.innerHTML = rows.map(pm => `
            <tr data-pm-id="${pm.id}" style="cursor:pointer;">
                <td>
                    <div class="fw-bold text-gray-800">${esc(pm.name)}</div>
                    ${pm.is_default ? `<span class="badge badge-light-success fs-8 mt-1">Default</span>` : ''}
                </td>
                <td>
                    <span class="badge badge-light-${typeColor[pm.type] || 'secondary'} fs-8">
                        ${esc(pm.type_label)}
                    </span>
                </td>
                <td>${esc(pm.provider || '—')}</td>
                <td>
                    ${pm.account_number
                        ? `<span class="text-muted">****${esc(pm.account_number.slice(-4))}</span>`
                        : `<span class="text-muted">—</span>`}
                </td>
                <td class="text-end fw-bold text-primary">${money(pm.current_balance)}</td>
                <td><span class="badge badge-light fw-bold">${esc(pm.creator_name || '—')}</span></td>
                <td>
                    <span class="badge badge-light-${pm.is_active ? 'success' : 'warning'}">
                        ${pm.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
            </tr>
        `).join('');
    }

    function renderPagination(p) {
        const info = document.getElementById('pmPageInfo');
        const ul   = document.getElementById('pmPagination');
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
        document.getElementById('pmLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('pmWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('pmEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }
    function money(n) {
        return '{{ currency_symbol() }}' + Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    window.PMList = { load, reload: load };
    return window.PMList;
})();
</script>