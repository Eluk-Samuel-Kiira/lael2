<script>
window.PromoList = (function () {
    'use strict';

    const URL_DATA = window.PROMO_ROUTES.data;
    const BOOT     = window.PROMO_BOOTSTRAP || {};

    const state = {
        page:        1,
        perPage:     Number(BOOT.perPage) || 15,
        search:      BOOT.search || '',
        status:      '',
        discountType:'',
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
        document.getElementById('promoSearchInput')?.addEventListener('input', doSearch);
        document.getElementById('promoSearchTop')?.addEventListener('input', function () {
            const top = document.getElementById('promoSearchInput');
            if (top) top.value = this.value;
            doSearch.call(this);
        });

        document.getElementById('promoStatusFilter')?.addEventListener('change', function () {
            state.status = this.value; state.page = 1; load();
        });
        document.getElementById('promoTypeFilter')?.addEventListener('change', function () {
            state.discountType = this.value; state.page = 1; load();
        });
        document.getElementById('promoPerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15; state.page = 1; load();
        });

        document.getElementById('promoTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-promo-id]');
            if (!row) return;
            if (e.target.closest('button, a, select, input')) return;
            const id = parseInt(row.dataset.promoId, 10);
            if (id && window.PromoDrawer) window.PromoDrawer.open(id);
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
        if (state.discountType) params.set('discount_type', state.discountType);

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
            console.error('[promo] load failed:', err);
            window.toastr?.error?.('Failed to load promotions.');
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
        set('statExpired',  s.expired  ?? 0);
    }

    function renderTable(rows) {
        const tbody = document.getElementById('promoTableBody');
        if (!tbody) return;

        const typeLabel = {
            percentage:     'Percentage',
            fixed_amount:   'Fixed Amount',
            buy_x_get_y:    'Buy X Get Y',
        };

        const typeColor = {
            percentage:     'primary',
            fixed_amount:   'info',
            buy_x_get_y:    'warning',
        };

        tbody.innerHTML = rows.map(p => `
            <tr data-promo-id="${p.id}" style="cursor:pointer;">
                <td>
                    <div class="fw-bold text-gray-800">${esc(p.name)}</div>
                    ${p.is_expired ? `<span class="badge badge-light-danger fs-8 mt-1">Expired</span>` : ''}
                </td>
                <td>
                    <span class="badge badge-light-${typeColor[p.discount_type] || 'secondary'} fs-8">
                        ${esc(typeLabel[p.discount_type] || p.discount_type)}
                    </span>
                </td>
                <td class="text-end fw-bold">
                    ${p.discount_type === 'percentage'
                        ? `${Number(p.discount_value).toFixed(2)}%`
                        : (p.discount_type === 'fixed_amount'
                            ? money(p.discount_value)
                            : esc(p.discount_value))}
                </td>
                <td class="text-muted fs-8">
                    ${esc(p.start_date_human || '—')} → ${esc(p.end_date_human || '—')}
                </td>
                <td><span class="badge badge-light fw-bold">${esc(p.creator_name || '—')}</span></td>
                <td>
                    <span class="badge badge-light-${p.is_active ? 'success' : 'warning'}">
                        ${p.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
            </tr>
        `).join('');
    }

    function renderPagination(p) {
        const info = document.getElementById('promoPageInfo');
        const ul   = document.getElementById('promoPagination');
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
        document.getElementById('promoLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('promoWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('promoEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }
    function money(n) {
        return '{{ currency_symbol() }}' + Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    window.PromoList = { load, reload: load };
    return window.PromoList;
})();
</script>