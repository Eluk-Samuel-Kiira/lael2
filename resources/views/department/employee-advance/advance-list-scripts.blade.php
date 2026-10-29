<script>
window.AdvanceList = (function () {
    'use strict';

    if (!window.ADVANCE_ROUTES) {
        console.error('[advance-list] ADVANCE_ROUTES undefined — routes <script> must render first.');
        return { load: () => {}, reload: () => {} };
    }

    const URL_DATA = window.ADVANCE_ROUTES.data;
    const BOOT     = window.ADVANCE_BOOTSTRAP || {};

    const state = {
        page:     1,
        perPage:  Number(BOOT.perPage) || 15,
        search:   BOOT.search || '',
        status:   '',
        dateFrom: '',
        dateTo:   '',
    };

    let debounce, inFlight = false, activeController = null;

    document.addEventListener('DOMContentLoaded', () => { bind(); load(); });

    function bind() {
        const doSearch = function () {
            clearTimeout(debounce);
            debounce = setTimeout(() => {
                state.search = this.value.trim();
                state.page = 1;
                load();
            }, 400);
        };
        document.getElementById('advanceSearchInput')?.addEventListener('input', doSearch);

        document.getElementById('advanceStatusFilter')?.addEventListener('change', function () {
            state.status = this.value; state.page = 1; load();
        });
        document.getElementById('advanceDateFrom')?.addEventListener('change', function () {
            state.dateFrom = this.value; state.page = 1; load();
        });
        document.getElementById('advanceDateTo')?.addEventListener('change', function () {
            state.dateTo = this.value; state.page = 1; load();
        });
        document.getElementById('advancePerPage')?.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 15; state.page = 1; load();
        });

        document.getElementById('advTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-adv-id]');
            if (!row) return;
            if (e.target.closest('button, a, select, input, .form-check')) return;
            const id = parseInt(row.dataset.advId, 10);
            if (id && window.AdvanceDrawer) window.AdvanceDrawer.open(id);
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
        if (state.search)        params.set('search', state.search);
        if (state.status !== '') params.set('status', state.status);
        if (state.dateFrom)      params.set('date_from', state.dateFrom);
        if (state.dateTo)        params.set('date_to', state.dateTo);

        try {
            const res  = await fetch(`${URL_DATA}?${params}`, {
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
            console.error('[advances] load failed:', err);
            window.toastr?.error?.('Failed to load advances.');
            showState('empty');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function renderStats(s) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('statOutstanding', formatNumber(s.outstanding) + ' ' + (window.ADVANCE_BOOTSTRAP?.currency || ''));
        set('statPending',     s.pending ?? 0);
        set('statRecovery',    (s.recovery_rate ?? 0) + '%');
        set('statActive',      s.active ?? 0);
    }

    function renderTable(rows) {
        const tbody = document.getElementById('advTableBody');
        if (!tbody) return;

        const statusColors = {
            pending: 'warning', approved: 'info', partially_paid: 'primary',
            fully_paid: 'success', rejected: 'danger', cancelled: 'secondary',
        };

        tbody.innerHTML = rows.map(a => `
            <tr data-adv-id="${a.id}" style="cursor:pointer;">
                <td>
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-circle symbol-40px me-3">
                            <div class="symbol-label bg-light-primary text-primary fw-bold">${esc(a.employee_initials)}</div>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="text-gray-800 fw-bold">${esc(a.employee_name)}</span>
                            <small class="text-muted">${esc(a.department_name)}</small>
                        </div>
                    </div>
                </td>
                <td class="text-end fw-bold">${esc(formatNumber(a.advance_amount))} ${esc(a.currency_symbol)}</td>
                <td class="text-end fw-bold ${a.remaining_amount > 0 ? 'text-warning' : 'text-success'}">
                    ${esc(formatNumber(a.remaining_amount))}
                </td>
                <td>
                    <span class="text-gray-800">${esc(a.advance_date)}</span>
                    <small class="text-muted d-block">${esc(a.advance_date_human)}</small>
                </td>
                <td>
                    <span class="badge badge-light-info">${esc(a.deduction_frequency_label)}</span>
                    ${a.deduction_day ? `<small class="text-muted d-block">Day: ${a.deduction_day}</small>` : ''}
                </td>
                <td class="text-center">
                    ${a.installments > 1
                        ? `<span class="fw-bold">${a.installments_paid}/${a.installments}</span>
                           <div class="progress h-5px mt-2 w-50 mx-auto">
                               <div class="progress-bar bg-info" style="width:${a.progress}%"></div>
                           </div>`
                        : `<span class="badge badge-light-secondary">N/A</span>`}
                </td>
                <td class="text-center">
                    <span class="badge badge-light-${statusColors[a.status] || 'secondary'} fw-bold py-3 px-4">
                        ${esc(a.status)}
                    </span>
                </td>
                <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
            </tr>
        `).join('');
    }

    function renderPagination(p) {
        const info = document.getElementById('advPageInfo');
        const ul   = document.getElementById('advPagination');
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
        document.getElementById('advLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('advWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('advEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function formatNumber(n) {
        const num = Number(n) || 0;
        return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.AdvanceList = { load, reload: load };
    return window.AdvanceList;
})();
</script>