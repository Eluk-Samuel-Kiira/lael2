<script>
window.ReportList = (function () {
    'use strict';

    const state = {
        page:        1,
        perPage:     {{ (int) $perPage }},
        startDate:   @json($startDate),
        endDate:     @json($endDate),
        status:      @json($status),
        locationId:  @json($locationId),
        variantId:   @json($variantId),
        categoryId:  @json($categoryId),
        hasPayment:  @json($hasPayment),
        search:      @json($search),
        minCost:     @json($minCost),
        maxCost:     @json($maxCost),
    };

    let searchDebounce;
    let inFlight = false;

    document.addEventListener('DOMContentLoaded', () => {
        bindFilters();
        bindRowClick();
        load();
    });

    function bindFilters() {
        document.getElementById('applyFiltersBtn')?.addEventListener('click', () => {
            readFilters();
            state.page = 1;
            load();
        });

        document.getElementById('clearFiltersBtn')?.addEventListener('click', resetFilters);

        document.getElementById('reportsPerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15;
            state.page = 1;
            load();
        });

        document.getElementById('filterSearch')?.addEventListener('input', function () {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                state.search = this.value.trim();
                state.page = 1;
                load();
            }, 400);
        });
    }

    function readFilters() {
        state.startDate  = document.getElementById('filterStartDate')?.value || '';
        state.endDate    = document.getElementById('filterEndDate')?.value   || '';
        state.status     = document.getElementById('filterStatus')?.value    || 'all';
        state.locationId = document.getElementById('filterLocation')?.value  || '';
        state.variantId  = document.getElementById('filterVariant')?.value   || '';
        state.categoryId = document.getElementById('filterCategory')?.value  || '';
        state.hasPayment = document.getElementById('filterPayment')?.value   || 'all';
        state.search     = document.getElementById('filterSearch')?.value?.trim() || '';
        state.minCost    = document.getElementById('filterMinCost')?.value   || '';
        state.maxCost    = document.getElementById('filterMaxCost')?.value   || '';
    }

    function bindRowClick() {
        document.getElementById('reportTableBody')?.addEventListener('click', function (e) {
            const row = e.target.closest('.report-row[data-order-id]');
            if (!row) return;
            const id = parseInt(row.getAttribute('data-order-id'), 10);
            if (!id) return;

            if (window.ReportDrawer?.open) {
                window.ReportDrawer.open(id);
            } else {
                console.log('[report] row clicked:', id, '— drawer not wired');
            }
        });
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showState('loading');

        const params = new URLSearchParams({
            page:     state.page,
            per_page: state.perPage,
        });
        if (state.startDate)  params.set('start_date',  state.startDate);
        if (state.endDate)    params.set('end_date',    state.endDate);
        if (state.status)     params.set('status',      state.status);
        if (state.locationId) params.set('location_id', state.locationId);
        if (state.variantId)  params.set('variant_id',  state.variantId);
        if (state.categoryId) params.set('category_id', state.categoryId);
        if (state.hasPayment) params.set('has_payment', state.hasPayment);
        if (state.search)     params.set('search',      state.search);
        if (state.minCost)    params.set('min_cost',    state.minCost);
        if (state.maxCost)    params.set('max_cost',    state.maxCost);

        try {
            const res = await fetch(`${window.REPORT_ROUTES.data}?${params.toString()}`, {
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
            console.error('[report] load failed:', err);
            showState('empty');
            window.toastr?.error?.('{{ __("passwords.failed_to_load_records") }}');
        } finally {
            inFlight = false;
        }
    }

    function renderSummary(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

        set('summaryTotalOrders', s.total_orders);

        const statusHtml = [
            { label: '{{ __("pagination.draft") }}',         count: s.draft_count,        color: 'secondary' },
            { label: '{{ __("pagination.in_progress") }}',   count: s.in_progress_count,  color: 'warning' },
            { label: '{{ __("pagination.completed") }}',     count: s.completed_count,    color: 'success' },
            { label: '{{ __("pagination.cancelled") }}',     count: s.cancelled_count,    color: 'danger' },
        ].filter(x => x.count > 0)
         .map(x => `<span class="badge badge-light-${x.color} fs-6 py-2 px-3">${x.label}: ${x.count}</span>`)
         .join('');
        document.getElementById('summaryStatusBreakdown').innerHTML = statusHtml || '<span class="text-muted fs-7">—</span>';

        const costsHtml = `
            <span class="badge badge-light-info fs-6 py-2 px-3">Input: ${fmtMoney(s.total_input_cost)}</span>
            <span class="badge badge-light-success fs-6 py-2 px-3">Output: ${fmtMoney(s.total_output_cost)}</span>
            <span class="badge badge-light-danger fs-6 py-2 px-3">Total: ${fmtMoney(s.total_cost)}</span>
        `;
        document.getElementById('summaryCosts').innerHTML = costsHtml;

        const profitEl = document.getElementById('summaryTotalProfit');
        profitEl.textContent = fmtMoney(s.total_profit);
        const card = document.getElementById('summaryProfitCard');
        card.classList.remove('bg-light-success', 'bg-light-danger', 'border-success', 'border-danger', 'border-dashed');
        card.classList.add('border-dashed', s.total_profit >= 0 ? 'bg-light-success' : 'bg-light-danger');
        card.classList.add(s.total_profit >= 0 ? 'border-success' : 'border-danger');
        card.querySelector('.ki-duotone').classList.remove('text-success', 'text-danger');
        card.querySelector('.ki-duotone').classList.add(s.total_profit >= 0 ? 'text-success' : 'text-danger');
    }

    function renderTable(rows) {
        const tbody = document.getElementById('reportTableBody');
        tbody.innerHTML = rows.map(row => {
            const profit       = Number(row.total_output_cost || 0) - Number(row.total_input_cost || 0);
            const profitColor  = profit >= 0 ? 'success' : 'danger';
            const paidBadge    = row.payment_method_id
                ? `<span class="badge badge-light-success fs-8"><i class="ki-duotone ki-wallet fs-2 me-1"><span class="path1"></span><span class="path2"></span></i> {{ __("pagination.paid") }}</span>`
                : '';

            return `
                <tr class="report-row" data-order-id="${row.id}">
                    <td class="ps-4">
                        <div class="fw-bold">${esc(row.production_number)}</div>
                        <div class="text-muted fs-7">${esc(row.created_at)}</div>
                    </td>
                    <td><span class="badge badge-light-${esc(row.status_badge)} fs-7 py-2 px-3">${esc(row.status_label)}</span></td>
                    <td><span class="badge badge-light-info">${esc(row.location_name || '—')}</span></td>
                    <td class="text-center"><span class="fw-bold">${fmtNum(row.total_input_quantity, 2)}</span></td>
                    <td class="text-center"><span class="fw-bold text-success">${fmtNum(row.total_output_quantity, 2)}</span></td>
                    <td class="text-center"><span class="text-muted">${fmtMoney(row.total_input_cost)}</span></td>
                    <td class="text-center"><span class="text-primary">${fmtMoney(row.total_output_cost)}</span></td>
                    <td class="text-center"><span class="fw-bold text-${profitColor}">${fmtMoney(profit)}</span></td>
                    <td>
                        <div>${esc(row.created_by_name || '—')}</div>
                        ${paidBadge}
                    </td>
                    <td class="text-center">
                        <button type="button"
                                class="btn btn-sm btn-light-primary"
                                onclick="event.stopPropagation(); window.ReportDrawer?.open(${row.id})">
                            <i class="ki-duotone ki-eye fs-2"><span class="path1"></span><span class="path2"></span></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderPagination(p) {
        const ul    = document.getElementById('reportPagination');
        const info  = document.getElementById('reportPaginationInfo');
        const wrap  = document.getElementById('reportPaginationContainer');
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
        const start = Math.max(1, p.current_page - 2), end = Math.min(p.last_page, p.current_page + 2);
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
        document.getElementById('reportTableContainer')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function showState(which) {
        document.getElementById('reportLoadingSpinner')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('reportTableContainer')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('reportNoDataMessage')?.classList.toggle('d-none', which !== 'empty');
        document.getElementById('reportPaginationContainer')?.classList.toggle('d-none', which !== 'table');
    }

    function resetFilters() {
        state.startDate = state.endDate = state.search = '';
        state.status = 'all';
        state.locationId = state.variantId = state.categoryId = '';
        state.hasPayment = 'all';
        state.minCost = state.maxCost = '';
        state.page = 1;

        ['filterStartDate', 'filterEndDate', 'filterSearch', 'filterMinCost', 'filterMaxCost'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        document.getElementById('filterStatus').value = 'all';
        ['filterLocation', 'filterVariant', 'filterCategory'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        document.getElementById('filterPayment').value = 'all';

        load();
    }

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML;
    }
    function fmtNum(n, d = 0) {
        return Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
    }
    function fmtMoney(n) {
        return window.REPORT_CURRENCY + fmtNum(n, 2);
    }

    window.ReportList = { load, reload: load, resetFilters };
    return window.ReportList;
})();
</script>