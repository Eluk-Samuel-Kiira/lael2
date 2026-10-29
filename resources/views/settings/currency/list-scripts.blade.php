<script>
window.CurrencyList = (function () {
    'use strict';

    const URL_DATA = window.CURRENCY_ROUTES.data;
    const BOOT     = window.CURRENCY_BOOTSTRAP || {};

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
        document.getElementById('currencySearchInput')?.addEventListener('input', doSearch);
        document.getElementById('currencySearchTop')?.addEventListener('input', function () {
            const top = document.getElementById('currencySearchInput');
            if (top) top.value = this.value;
            doSearch.call(this);
        });

        document.getElementById('currencyStatusFilter')?.addEventListener('change', function () {
            state.status = this.value; state.page = 1; load();
        });
        document.getElementById('currencyPerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15; state.page = 1; load();
        });

        document.getElementById('currencyTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-currency-id]');
            if (!row) return;
            if (e.target.closest('button, a, select, input')) return;
            const id = parseInt(row.dataset.currencyId, 10);
            if (id && window.CurrencyDrawer) window.CurrencyDrawer.open(id);
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
            console.error('[currency] load failed:', err);
            window.toastr?.error?.('Failed to load currencies.');
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
        set('statBase',     s.base     ?? 0);
    }

    function renderTable(rows) {
        const tbody = document.getElementById('currencyTableBody');
        if (!tbody) return;

        tbody.innerHTML = rows.map(c => `
            <tr data-currency-id="${c.id}" style="cursor:pointer;">
                <td>
                    <span class="badge badge-light-primary fw-bold">${esc(c.code)}</span>
                    ${c.is_base_currency ? `<span class="badge badge-success ms-2">Base</span>` : ''}
                </td>
                <td><div class="fw-bold text-gray-800">${esc(c.name)}</div></td>
                <td class="text-center"><span class="badge badge-light-dark fw-bold fs-6">${esc(c.symbol)}</span></td>
                <td class="text-end fw-bold text-success">${Number(c.exchange_rate).toFixed(6)}</td>
                <td><span class="badge badge-light fw-bold">${esc(c.creator_name || '—')}</span></td>
                <td>
                    <span class="badge badge-light-${c.is_active ? 'success' : 'warning'}">
                        ${c.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
            </tr>
        `).join('');
    }

    function renderPagination(p) {
        const info = document.getElementById('currencyPageInfo');
        const ul   = document.getElementById('currencyPagination');
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
        document.getElementById('currencyLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('currencyWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('currencyEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.CurrencyList = { load, reload: load };
    return window.CurrencyList;
})();
</script>