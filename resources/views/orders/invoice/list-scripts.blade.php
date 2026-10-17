<script>
window.InvoiceList = (function () {
    'use strict';

    const URL_DATA = '{{ route("invoices.data") }}';

    const state = {
        page:          {{ (int) request('page', 1) }},
        perPage:       {{ (int) $initialFilters['per_page'] }},
        search:        @json($initialFilters['search']),
        status:        @json($initialFilters['status']),
        locationId:    @json($initialFilters['location_id']),
        issueDateFrom: @json($initialFilters['issue_date_from']),
        issueDateTo:   @json($initialFilters['issue_date_to']),
    };

    let searchDebounce, dateDebounce;
    let inFlight = false;

    document.addEventListener('DOMContentLoaded', () => {
        bindFilters();
        bindRowActions();
        load();
    });

    // ── Filter wiring ─────────────────────────────────────────
    function bindFilters() {
        document.getElementById('invoiceSearchInput')?.addEventListener('input', function () {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                state.search = this.value.trim();
                state.page = 1;
                load();
            }, 400);
        });

        document.getElementById('invoiceStatusFilter')?.addEventListener('change', function () {
            state.status = this.value;
            state.page = 1;
            load();
        });

        document.getElementById('invoiceLocationFilter')?.addEventListener('change', function () {
            state.locationId = this.value;
            state.page = 1;
            load();
        });

        document.getElementById('invoicePerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15;
            state.page = 1;
            load();
        });

        const onDate = function () {
            clearTimeout(dateDebounce);
            dateDebounce = setTimeout(() => {
                state.issueDateFrom = document.getElementById('invoiceIssueFrom')?.value || '';
                state.issueDateTo   = document.getElementById('invoiceIssueTo')?.value || '';
                state.page = 1;
                load();
            }, 300);
        };
        document.getElementById('invoiceIssueFrom')?.addEventListener('change', onDate);
        document.getElementById('invoiceIssueTo')?.addEventListener('change', onDate);
    }

    // ── Row-level delegation: invoice number click + download ─
    function bindRowActions() {
        document.getElementById('invoiceTableBody')?.addEventListener('click', function (e) {
            const numberLink = e.target.closest('.invoice-number-link[data-invoice-id]');
            if (numberLink) {
                e.preventDefault();
                const id = parseInt(numberLink.getAttribute('data-invoice-id'), 10);
                if (id && window.InvoiceDrawer) window.InvoiceDrawer.open(id);
                return;
            }
        });
    }

    // ── Fetch + render ────────────────────────────────────────
    async function load() {
        if (inFlight) return;
        inFlight = true;

        showState('loading');

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.search)        params.set('search', state.search);
        if (state.status)        params.set('status', state.status);
        if (state.locationId)    params.set('location_id', state.locationId);
        if (state.issueDateFrom) params.set('issue_date_from', state.issueDateFrom);
        if (state.issueDateTo)   params.set('issue_date_to', state.issueDateTo);

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
            console.error('[invoices] load failed:', err);
            showState('empty');
            window.toastr?.error?.('{{ __("passwords.failed_to_load_records") ?? "Failed to load records" }}');
        } finally {
            inFlight = false;
        }
    }

    function renderSummary(s) {
        const setText = (id, v) => {
            const el = document.getElementById(id);
            if (el) el.textContent = v;
        };
        setText('summaryTotal', `${s.total ?? 0}`);
        setText('summaryPaid',        '{{ currency_symbol() }}' + fmt(s.total_paid ?? 0, 2));
        setText('summaryOutstanding', '{{ currency_symbol() }}' + fmt(s.total_outstanding ?? 0, 2));
        setText('summaryAmount',      '{{ currency_symbol() }}' + fmt(s.total_amount ?? 0, 2));
        setText('summaryBreakdown',
            `${s.draft ?? 0} draft · ${s.sent ?? 0} sent · ${s.partially_paid ?? 0} partial · ${s.paid ?? 0} paid · ${s.overdue ?? 0} overdue`
        );
    }

    function renderTable(rows) {
        const tbody = document.getElementById('invoiceTableBody');
        tbody.innerHTML = rows.map(row => {
            const dueClass = row.is_overdue ? 'badge-light-danger' : 'badge-light';
            const balance = row.balance_due > 0
                ? `<small class="d-block text-danger fs-8">↳ ${esc(row.balance_due_formatted)}</small>`
                : '';

            return `
                <tr data-invoice-id="${row.id}">
                    <td>
                        <div class="d-flex flex-column">
                            <a href="javascript:void(0)" class="invoice-number-link fw-bold text-gray-800"
                               data-invoice-id="${row.id}">
                                ${esc(row.invoice_number)}
                            </a>
                            <span class="text-muted fs-7">${esc(row.order_number || '')}</span>
                        </div>
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
                                <span class="text-muted fs-7">${esc(row.customer_email || '—')}</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-light-warning fw-bold">${esc(row.location_name || '—')}</span></td>
                    <td><span class="badge badge-light-primary fw-bold">${esc(row.issue_date_human || '—')}</span></td>
                    <td><span class="badge ${dueClass} fw-bold">${esc(row.due_date_human || '—')}</span></td>
                    <td>
                        <span class="fw-bold text-gray-800">${esc(row.total_formatted)}</span>
                        ${balance}
                    </td>
                    <td><span class="badge badge-light fw-bold">${esc(row.created_by_name || '—')}</span></td>
                    <td>
                        <span class="badge badge-light-${esc(row.status_color)} fw-bold">
                            ${esc(row.status_label)}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="/invoices/${row.id}/pdf" target="_blank"
                               class="btn btn-sm btn-icon btn-light-secondary"
                               title="{{ __('payments.download_pdf') }}">
                                <i class="bi bi-file-pdf fs-5"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderPagination(p) {
        const ul = document.getElementById('invoicePagination');
        const info = document.getElementById('invoicePaginationInfo');
        const wrap = document.getElementById('invoicePaginationContainer');
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
        document.getElementById('invoiceTableContainer')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function showState(which) {
        document.getElementById('invoiceLoadingSpinner')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('invoiceTableContainer')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('invoiceNoDataMessage')?.classList.toggle('d-none', which !== 'empty');
        document.getElementById('invoicePaginationContainer')?.classList.toggle('d-none', which !== 'table');
    }

    function resetFilters() {
        state.search = state.status = state.locationId = '';
        state.issueDateFrom = state.issueDateTo = '';
        state.page = 1;

        const s = document.getElementById('invoiceSearchInput'); if (s) s.value = '';
        const st = document.getElementById('invoiceStatusFilter'); if (st) st.value = '';
        const loc = document.getElementById('invoiceLocationFilter'); if (loc) loc.value = '';
        const f = document.getElementById('invoiceIssueFrom'); if (f) f.value = '';
        const t = document.getElementById('invoiceIssueTo'); if (t) t.value = '';

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

    window.InvoiceList = {
        load,
        reload: load,
        resetFilters,
    };

    return window.InvoiceList;
})();
</script>