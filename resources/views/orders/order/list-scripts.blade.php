<script>
window.OrderList = (function () {
    'use strict';

    const URL_DATA = '{{ route("orders.data") }}';

    const state = {
        page:       {{ (int) request('page', 1) }},
        perPage:    {{ (int) $initialFilters['per_page'] }},
        search:     @json($initialFilters['search']),
        status:     @json($initialFilters['status']),
        source:     @json($initialFilters['source']),
        locationId: @json($initialFilters['location_id']),
        dateFrom:   @json($initialFilters['date_from']),
        dateTo:     @json($initialFilters['date_to']),
    };

    let searchDebounce, dateDebounce;
    let inFlight = false;

    document.addEventListener('DOMContentLoaded', () => {
        bindFilters();
        bindRowActions();
        load();
    });

    function bindFilters() {
        document.getElementById('orderSearchInput')?.addEventListener('input', function () {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => { state.search = this.value.trim(); state.page = 1; load(); }, 400);
        });

        document.getElementById('orderStatusFilter')?.addEventListener('change', function () {
            state.status = this.value; state.page = 1; load();
        });
        document.getElementById('orderSourceFilter')?.addEventListener('change', function () {
            state.source = this.value; state.page = 1; load();
        });
        document.getElementById('orderLocationFilter')?.addEventListener('change', function () {
            state.locationId = this.value; state.page = 1; load();
        });
        document.getElementById('orderPerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15; state.page = 1; load();
        });

        const onDate = () => {
            clearTimeout(dateDebounce);
            dateDebounce = setTimeout(() => {
                state.dateFrom = document.getElementById('orderDateFrom')?.value || '';
                state.dateTo   = document.getElementById('orderDateTo')?.value || '';
                state.page = 1; load();
            }, 300);
        };
        document.getElementById('orderDateFrom')?.addEventListener('change', onDate);
        document.getElementById('orderDateTo')?.addEventListener('change', onDate);
    }

    function bindRowActions() {
        document.getElementById('orderTableBody')?.addEventListener('click', function (e) {
            const numberLink = e.target.closest('.order-number-link[data-order-id]');
            if (numberLink) {
                e.preventDefault();
                const id = parseInt(numberLink.getAttribute('data-order-id'), 10);
                if (id && window.OrderDrawer) window.OrderDrawer.open(id);
            }
        });
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showState('loading');

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.search)     params.set('search', state.search);
        if (state.status)     params.set('status', state.status);
        if (state.source)     params.set('source', state.source);
        if (state.locationId) params.set('location_id', state.locationId);
        if (state.dateFrom)   params.set('date_from', state.dateFrom);
        if (state.dateTo)     params.set('date_to', state.dateTo);

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
            console.error('[orders] load failed:', err);
            showState('empty');
            window.toastr?.error?.('{{ __("passwords.failed_to_load_records") ?? "Failed to load records" }}');
        } finally {
            inFlight = false;
        }
    }

    function renderSummary(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('summaryTotal',   s.total ?? 0);
        set('summaryValue',   '{{ currency_symbol() }}' + fmt(s.total_value ?? 0, 2));
        set('summaryPaid',    '{{ currency_symbol() }}' + fmt(s.total_paid ?? 0, 2));
        set('summaryBalance', '{{ currency_symbol() }}' + fmt(s.total_balance ?? 0, 2));
        set('summaryBreakdown',
            `${s.pending ?? 0} pending · ${s.confirmed ?? 0} confirmed · ${s.completed ?? 0} completed · ${s.cancelled ?? 0} cancelled`);
    }

    function renderTable(rows) {
        const tbody = document.getElementById('orderTableBody');
        tbody.innerHTML = rows.map(row => {
            const balance = row.balance_due > 0
                ? `<small class="d-block text-danger fs-8">↳ ${esc(row.balance_due_formatted)}</small>`
                : '';
            return `
                <tr data-order-id="${row.id}">
                    <td>
                        <a href="javascript:void(0)" class="order-number-link fw-bold text-gray-800"
                           data-order-id="${row.id}">${esc(row.order_number)}</a>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-35px me-3">
                                <div class="symbol-label bg-light-primary text-primary fw-bold">
                                    ${esc(row.customer_initial || '?')}
                                </div>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-gray-800">${esc(row.customer_name || '—')}</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-light-warning fw-bold">${esc(row.location_name || '—')}</span></td>
                    <td><span class="badge badge-light fw-bold">${esc(row.created_by_name || '—')}</span></td>
                    <td>
                        <span class="badge badge-light-${esc(row.status_color)} fw-bold">
                            ${esc(row.status_label)}
                        </span>
                    </td>
                    <td><span class="text-gray-800 fw-semibold">${esc(row.source_label || '—')}</span></td>
                    <td>
                        <span class="fw-bold text-gray-800">${esc(row.total_formatted)}</span>
                        ${balance}
                    </td>
                    <td>
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-sm btn-icon btn-light btn-active-light-info"
                                    onclick="window.OrderDrawer?.open(${row.id})"
                                    title="{{ __('passwords.view_details') }}">
                                <i class="ki-duotone ki-information fs-4">
                                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                </i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderPagination(p) {
        const ul = document.getElementById('orderPagination');
        const info = document.getElementById('orderPaginationInfo');
        const wrap = document.getElementById('orderPaginationContainer');
        if (!ul || !info || !wrap) return;

        ul.innerHTML = '';
        info.textContent = `Showing ${p.from || 0} to ${p.to || 0} of ${p.total} entries`;
        wrap.classList.remove('d-none');

        const add = (page, text, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = text;
            a.addEventListener('click', e => {
                e.preventDefault();
                if (!disabled && !active) gotoPage(page);
            });
            li.appendChild(a);
            ul.appendChild(li);
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
        document.getElementById('orderTableContainer')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function showState(which) {
        document.getElementById('orderLoadingSpinner')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('orderTableContainer')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('orderNoDataMessage')?.classList.toggle('d-none', which !== 'empty');
        document.getElementById('orderPaginationContainer')?.classList.toggle('d-none', which !== 'table');
    }

    function resetFilters() {
        state.search = state.status = state.source = state.locationId = '';
        state.dateFrom = state.dateTo = '';
        state.page = 1;
        ['orderSearchInput','orderStatusFilter','orderSourceFilter','orderLocationFilter','orderDateFrom','orderDateTo']
            .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        load();
    }

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }
    function fmt(n, d = 0) {
        return Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
    }

    window.OrderList = { load, reload: load, resetFilters };
    return window.OrderList;
})();
</script>