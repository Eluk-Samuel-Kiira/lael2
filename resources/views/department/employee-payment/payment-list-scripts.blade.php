<script>
window.PaymentList = (function () {
    'use strict';

    if (!window.PAYMENT_ROUTES) {
        console.error('[payment-list] PAYMENT_ROUTES undefined — routes <script> must render first.');
        return { load: () => {}, reload: () => {} };
    }

    const URL_DATA   = window.PAYMENT_ROUTES.data;
    const URL_EXPORT = window.PAYMENT_ROUTES.export;
    const BOOT       = window.PAYMENT_BOOTSTRAP || {};

    const state = {
        page: 1, perPage: Number(BOOT.perPage) || 15,
        search: BOOT.search || '',
        status: '', paymentType: '', dateFrom: '', dateTo: '',
    };

    let debounce, inFlight = false, activeController = null;

    document.addEventListener('DOMContentLoaded', () => { bind(); load(); });

    function bind() {
        const doSearch = function () {
            clearTimeout(debounce);
            debounce = setTimeout(() => {
                state.search = this.value.trim(); state.page = 1; load();
            }, 400);
        };
        document.getElementById('paymentSearchInput')?.addEventListener('input', doSearch);
        document.getElementById('paymentStatusFilter')?.addEventListener('change', function () {
            state.status = this.value; state.page = 1; load();
        });
        document.getElementById('paymentTypeFilter')?.addEventListener('change', function () {
            state.paymentType = this.value; state.page = 1; load();
        });
        document.getElementById('paymentDateFrom')?.addEventListener('change', function () {
            state.dateFrom = this.value; state.page = 1; load();
        });
        document.getElementById('paymentDateTo')?.addEventListener('change', function () {
            state.dateTo = this.value; state.page = 1; load();
        });
        document.getElementById('paymentPerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15; state.page = 1; load();
        });
        document.getElementById('btnResetPaymentFilters')?.addEventListener('click', () => {
            Object.assign(state, { page: 1, status: '', paymentType: '', dateFrom: '', dateTo: '' });
            ['paymentStatusFilter','paymentTypeFilter','paymentDateFrom','paymentDateTo'].forEach(id => {
                const el = document.getElementById(id); if (el) el.value = '';
            });
            load();
        });
        document.getElementById('btnExportPayments')?.addEventListener('click', () => {
            const params = new URLSearchParams({
                search: state.search, status: state.status,
                payment_type: state.paymentType, date_from: state.dateFrom, date_to: state.dateTo,
            });
            window.open(`${URL_EXPORT}?${params}`, '_blank');
        });

        document.getElementById('payTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-pay-id]');
            if (!row) return;
            if (e.target.closest('button, a, select, input, .form-check')) return;
            const id = parseInt(row.dataset.payId, 10);
            if (id && window.PaymentDrawer) window.PaymentDrawer.open(id);
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
        if (state.search)      params.set('search', state.search);
        if (state.status)      params.set('status', state.status);
        if (state.paymentType) params.set('payment_type', state.paymentType);
        if (state.dateFrom)    params.set('date_from', state.dateFrom);
        if (state.dateTo)      params.set('date_to', state.dateTo);

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
            console.error('[payments] load failed:', err);
            window.toastr?.error?.('Failed to load payments.');
            showState('empty');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function renderStats(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('statTotalPayments', s.total_payments ?? 0);
        set('statTotalAmount',   formatNumber(s.total_amount));
        set('statThisMonth',     `${s.this_month_count ?? 0} · ${formatNumber(s.this_month_amount)}`);
        set('statPending',       s.pending_count ?? 0);
    }

    function renderTable(rows) {
        const tbody = document.getElementById('payTableBody');
        if (!tbody) return;

        const statusColors = { pending: 'warning', completed: 'success', failed: 'danger', cancelled: 'secondary' };
        const typeColors   = { cash: 'warning', bank_account: 'info', digital_wallet: 'primary', card: 'success', check: 'secondary', mobile_money: 'danger', other: 'dark' };

        tbody.innerHTML = rows.map(p => `
            <tr data-pay-id="${p.id}" style="cursor:pointer;">
                <td>
                    <div class="d-flex flex-column">
                        <span class="text-gray-800 fw-bold">${esc(p.employee_name)}</span>
                        <small class="text-muted">${esc(p.payment_type_label)}</small>
                    </div>
                </td>
                <td><span class="badge badge-light-primary">${esc(p.payment_type_label)}</span></td>
                <td>
                    <span class="badge badge-light-${typeColors[p.payment_method_type] || 'dark'}">${esc(p.payment_method_name)}</span>
                </td>
                <td class="text-end fw-bold">${esc(formatNumber(p.gross_amount))} ${esc(p.currency_symbol)}</td>
                <td class="text-end fw-bold text-success">${esc(formatNumber(p.net_amount))}</td>
                <td class="text-muted fs-7">${esc(p.payment_date)}</td>
                <td class="text-center">
                    <span class="badge badge-light-${statusColors[p.status] || 'secondary'}">${esc(p.status)}</span>
                </td>
                <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
            </tr>
        `).join('');
    }

    function renderPagination(p) {
        const info = document.getElementById('payPageInfo');
        const ul   = document.getElementById('payPagination');
        if (!info || !ul) return;
        info.textContent = `Showing ${p.from || 0}–${p.to || 0} of ${p.total || 0}`;
        ul.innerHTML = '';

        const addPage = (page, label, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link'; a.href = '#'; a.textContent = label;
            if (!disabled) a.addEventListener('click', (ev) => {
                ev.preventDefault(); state.page = page; load();
            });
            li.appendChild(a); ul.appendChild(li);
        };
        addPage(p.current_page - 1, 'Prev', false, p.current_page <= 1);
        for (let i = Math.max(1, p.current_page - 2); i <= Math.min(p.last_page, p.current_page + 2); i++) {
            addPage(i, String(i), i === p.current_page);
        }
        addPage(p.current_page + 1, 'Next', false, !p.has_more);
    }

    function showState(which) {
        document.getElementById('payLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('payWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('payEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function formatNumber(n) {
        const num = Number(n) || 0;
        return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.PaymentList = { load, reload: load };
    return window.PaymentList;
})();
</script>