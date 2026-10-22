<script>
window.POList = (function () {
    'use strict';

    const URL_DATA = '{{ route("purchase_orders.data") }}';

    const state = {
        page: {{ (int) request('page', 1) }},
        perPage: {{ (int) ($initialFilters['per_page'] ?? 15) }},
        search: @json($initialFilters['search'] ?? ''),
        status: @json($initialFilters['status'] ?? ''),
    };

    let searchDebounce;
    let inFlight = false;

    document.addEventListener('DOMContentLoaded', () => {
        bindFilters();
        bindRowClick();
        load();
    });

    function bindFilters() {
        document.getElementById('poSearchInput')?.addEventListener('input', function () {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => { state.search = this.value.trim(); state.page = 1; load(); }, 400);
        });

        document.getElementById('poStatusFilter')?.addEventListener('change', function () {
            state.status = this.value; state.page = 1; load();
        });

        document.getElementById('poPerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15; state.page = 1; load();
        });
    }

    function bindRowClick() {
        document.getElementById('poTableBody')?.addEventListener('click', function (e) {
            const row = e.target.closest('.po-row[data-po-id]');
            if (!row) return;
            const id = parseInt(row.getAttribute('data-po-id'), 10);
            if (!id) return;

            if (window.PODrawer?.open) {
                window.PODrawer.open(id);
            } else {
                console.log('[po-list] row clicked:', id, '— drawer not wired');
            }
        });
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showState('loading');

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.search) params.set('search', state.search);
        if (state.status) params.set('status', state.status);

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
            console.error('[po-list] load failed:', err);
            showState('empty');
            window.toastr?.error?.('{{ __("passwords.failed_to_load_records") ?? "Failed to load records" }}');
        } finally {
            inFlight = false;
        }
    }

    function renderSummary(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('summaryTotal', s.total ?? 0);
        set('summaryValue', '{{ currency_symbol() }}' + fmt(s.total_value ?? 0, 2));
    }

    function renderTable(rows) {
        const tbody = document.getElementById('poTableBody');
        tbody.innerHTML = rows.map(row => {
            return `
                <tr class="po-row" data-po-id="${row.id}">
                    <td><span class="fw-bold text-primary">${esc(row.po_number)}</span></td>
                    <td>${esc(row.supplier_name)}</td>
                    <td><span class="badge badge-light fw-bold">${esc(row.location_name)}</span></td>
                    <td><span class="badge badge-light-${esc(row.status_badge)}">${esc(row.status_label)}</span></td>
                    <td><span class="fw-bold text-gray-800">${esc(row.total_formatted)}</span></td>
                    <td>${esc(row.expected_delivery || '—')}</td>
                    <td><span class="badge badge-light fw-bold">${esc(row.created_by_name)}</span></td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-icon btn-light btn-active-light-info"
                                onclick="event.stopPropagation(); window.PODrawer?.open(${row.id})"
                                title="{{ __('auth._view') }}">
                            <i class="bi bi-eye fs-5"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderPagination(p) {
        const ul = document.getElementById('poPagination');
        const info = document.getElementById('poPaginationInfo');
        const wrap = document.getElementById('poPaginationContainer');
        if (!ul || !info || !wrap) return;

        ul.innerHTML = '';
        info.textContent = `Showing ${p.from || 0} to ${p.to || 0} of ${p.total} entries`;
        wrap.classList.remove('d-none');

        const add = (page, text, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link'; a.href = '#'; a.textContent = text;
            a.addEventListener('click', e => { e.preventDefault(); if (!disabled && !active) gotoPage(page); });
            li.appendChild(a); ul.appendChild(li);
        };

        add(p.current_page - 1, 'Prev', false, p.current_page <= 1);
        const start = Math.max(1, p.current_page - 2), end = Math.min(p.last_page, p.current_page + 2);
        if (start > 1) add(1, '1');
        if (start > 2) { const li = document.createElement('li'); li.className = 'page-item disabled'; li.innerHTML = '<span class="page-link">…</span>'; ul.appendChild(li); }
        for (let i = start; i <= end; i++) add(i, i, i === p.current_page);
        if (end < p.last_page - 1) { const li = document.createElement('li'); li.className = 'page-item disabled'; li.innerHTML = '<span class="page-link">…</span>'; ul.appendChild(li); }
        if (end < p.last_page) add(p.last_page, p.last_page);
        add(p.current_page + 1, 'Next', false, p.current_page >= p.last_page);
    }

    function gotoPage(page) {
        if (page === state.page || page < 1) return;
        state.page = page; load();
        document.getElementById('poTableContainer')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function showState(which) {
        document.getElementById('poLoadingSpinner')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('poTableContainer')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('poNoDataMessage')?.classList.toggle('d-none', which !== 'empty');
        document.getElementById('poPaginationContainer')?.classList.toggle('d-none', which !== 'table');
    }

    function resetFilters() {
        state.search = state.status = ''; state.page = 1;
        const s = document.getElementById('poSearchInput'); if (s) s.value = '';
        const st = document.getElementById('poStatusFilter'); if (st) st.value = '';
        load();
    }

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML;
    }
    function fmt(n, d = 0) {
        return Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
    }

    window.POList = { load, reload: load, resetFilters };
    return window.POList;
})();
</script>