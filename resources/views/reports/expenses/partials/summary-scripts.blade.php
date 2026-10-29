<script>
window.ExpenseSummary = (function () {
    'use strict';

    const ROUTES = window.EXP_SUMMARY_ROUTES;
    const BOOT   = window.EXP_SUMMARY_BOOTSTRAP || {};
    let CURRENCY = '';
    let metaCache = null;
    let activeController = null;
    let inFlight = false;
    let currentRowsById = {};

    const state = {
        start_date:        BOOT.start_date        || '',
        end_date:          BOOT.end_date          || '',
        category_id:       BOOT.category_id       || '',
        vendor_name:       BOOT.vendor_name       || '',
        payment_method_id: BOOT.payment_method_id || '',
        payment_status:    BOOT.payment_status    || '',
        employee_id:       BOOT.employee_id       || '',
        requires_receipt:  BOOT.requires_receipt  || '',
        is_recurring:      BOOT.is_recurring      || '',
        location_id:       BOOT.location_id       || '',
    };

    document.addEventListener('DOMContentLoaded', () => {
        seedFormFromState();
        bind();
        loadMeta().then(() => loadData());
    });

    function bind() {
        const form = document.getElementById('expSummaryFilterForm');
        form?.addEventListener('submit', (e) => {
            e.preventDefault();
            readFormIntoState();
            loadData();
        });

        document.getElementById('expSummaryClear')?.addEventListener('click', () => {
            Object.keys(state).forEach(k => {
                if (k === 'start_date') state[k] = BOOT.start_date;
                else if (k === 'end_date') state[k] = BOOT.end_date;
                else state[k] = '';
            });
            seedFormFromState();
            loadData();
        });

        // Delegated click for detail
        document.getElementById('topBody')?.addEventListener('click', (e) => {
            const a = e.target.closest('[data-expense-id]');
            if (!a) return;
            const id = parseInt(a.dataset.expenseId, 10);
            const row = currentRowsById[id];
            if (row) window.openExpenseDetail(row);
        });

        // Export button
        document.querySelectorAll('[data-export-table]').forEach(el => {
            el.addEventListener('click', () => {
                const tableId  = el.dataset.exportTable;
                const filename = (el.dataset.exportFilename || 'expenses') + '_' + new Date().toISOString().slice(0, 10);
                const format   = el.dataset.exportFormat || 'xlsx';
                const sheet    = el.dataset.exportSheet || 'Sheet1';
                if (typeof window.exportCurrentPage === 'function') {
                    window.exportCurrentPage({ tableId, filename, sheetName: sheet, format });
                }
            });
        });
    }

    function seedFormFromState() {
        const form = document.getElementById('expSummaryFilterForm');
        if (!form) return;
        Object.entries(state).forEach(([k, v]) => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) el.value = v == null ? '' : String(v);
        });
    }

    function readFormIntoState() {
        const form = document.getElementById('expSummaryFilterForm');
        if (!form) return;
        Object.keys(state).forEach(k => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) state[k] = el.value;
        });
    }

    // ── Meta (dropdown options) ─────────────────────────────
    async function loadMeta() {
        if (metaCache) return metaCache;
        try {
            const res = await fetch(ROUTES.meta, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Meta failed');
            metaCache = json;
            CURRENCY = json.currency || '';
            fillSelect('category_id',       json.categories,      'id', 'name');
            fillSelect('payment_method_id', json.payment_methods, 'id', c => c.name + (c.is_default ? ' (default)' : ''));
            fillSelect('employee_id',       json.employees,       'id', e => `${e.first_name} ${e.last_name}`.trim());
            fillSelect('location_id',       json.locations,       'id', 'name');
            return json;
        } catch (err) {
            console.error('[exp-summary] meta failed:', err);
            window.toastr?.error?.('Failed to load filter options.');
            throw err;
        }
    }

    function fillSelect(name, rows, valKey, labelFnOrKey) {
        const sel = document.querySelector(`#expSummaryFilterForm [name="${name}"]`);
        if (!sel || !Array.isArray(rows)) return;
        const label = typeof labelFnOrKey === 'function' ? labelFnOrKey : (row) => row[labelFnOrKey];
        const current = sel.value;
        // preserve the first placeholder option
        const placeholder = sel.querySelector('option[value=""]');
        sel.innerHTML = '';
        if (placeholder) sel.appendChild(placeholder);
        rows.forEach(row => {
            const o = document.createElement('option');
            o.value = row[valKey];
            o.textContent = label(row);
            sel.appendChild(o);
        });
        sel.value = current || state[name] || '';
    }

    // ── Data ────────────────────────────────────────────────
    async function loadData() {
        if (inFlight) return;
        inFlight = true;
        if (activeController) activeController.abort();
        const controller = new AbortController();
        activeController = controller;
        showState('loading');

        const params = new URLSearchParams();
        Object.entries(state).forEach(([k, v]) => { if (v !== '' && v != null) params.set(k, v); });

        try {
            const res = await fetch(`${ROUTES.data}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            renderStats(json.summary, json.currency || CURRENCY);
            renderTop(json.top || []);
            renderDaily(json.daily || [], json.summary);
            renderDateBadge(json.meta.start_date, json.meta.end_date);
            renderActiveFilters();
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[exp-summary] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load expenses.');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function showState(which) {
        document.getElementById('topLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('topWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('topEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function renderStats(s, currency) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        const money = (n) => `${currency} ${fmt(n)}`;
        set('statTotalExpenses', fmt(s.total_expenses || 0));
        set('statTotalAmount',   money(s.total_amount || 0));
        set('statTotalTax',      money(s.total_tax || 0));
        set('statAvgExpense',    money(s.avg_expense || 0));
        set('statMaxExpense',    money(s.max_expense || 0));
        set('statMinExpense',    money(s.min_expense || 0));
    }

    function renderTop(rows) {
        const tbody = document.getElementById('topBody');
        if (!tbody) return;

        currentRowsById = {};
        rows.forEach(r => { currentRowsById[r.id] = r; });

        if (!rows.length) { showState('empty'); return; }

        const statusColors = { pending: 'warning', paid: 'success', reimbursed: 'info' };

        tbody.innerHTML = rows.map(r => {
            const sColor = statusColors[r.status] || 'secondary';
            return `
                <tr>
                    <td class="ps-4">
                        <span class="fw-semibold text-gray-800">${esc(r.date || '')}</span>
                        ${r.is_recurring ? `<div class="mt-1"><span class="badge badge-light-warning badge-sm"><i class="ki-duotone ki-repeat fs-4 me-1"></i>{{ __('accounting.recurring') }}</span></div>` : ''}
                    </td>
                    <td>
                        <a href="javascript:void(0)" data-expense-id="${r.id}" class="text-primary fw-bold text-hover-primary">
                            ${esc(r.expense_number || '')}
                        </a>
                    </td>
                    <td>
                        <span class="text-gray-800 fw-semibold">${esc((r.description || '').slice(0, 50))}</span>
                        ${r.tax_amount > 0 ? `<small class="d-block text-muted">{{ __('accounting.tax') }}: ${esc(CURRENCY)} ${fmt(r.tax_amount)}</small>` : ''}
                    </td>
                    <td><span class="text-gray-600">${esc(r.vendor_name || 'N/A')}</span></td>
                    <td><span class="badge badge-light-info">${esc(r.category || 'N/A')}</span></td>
                    <td class="text-end"><span class="fw-bold text-success">${esc(CURRENCY)} ${fmt(r.amount)}</span></td>
                    <td>
                        <span class="badge badge-light-${sColor}">
                            ${esc((r.status || '').charAt(0).toUpperCase() + (r.status || '').slice(1))}
                        </span>
                    </td>
                    <td>
                        ${r.payment_method
                            ? `<span class="badge badge-light-primary">${esc(r.payment_method)}</span>`
                            : `<span class="badge badge-light-secondary">{{ __('accounting.no_payment_method') }}</span>`}
                    </td>
                    <td class="pe-4">
                        ${r.employee
                            ? `<div class="d-flex flex-column"><span class="fw-semibold text-gray-800">${esc(r.employee)}</span>${r.employee_code ? `<small class="text-muted">${esc(r.employee_code)}</small>` : ''}</div>`
                            : `<span class="text-muted">N/A</span>`}
                    </td>
                </tr>`;
        }).join('');

        const badge = document.getElementById('topExpensesBadge');
        if (badge) badge.textContent = `${{ __('accounting.total') }}: ${rows.length}`;
        showState('table');
    }

    function renderDaily(rows, summary) {
        const card = document.getElementById('dailyCard');
        const tbody = document.getElementById('dailyBody');
        const badge = document.getElementById('dailyBadge');
        if (!card || !tbody) return;

        if (!rows.length) { card.style.display = 'none'; return; }
        card.style.display = '';

        const grand = (summary && summary.total_amount) || 0;

        tbody.innerHTML = rows.map(r => {
            const pct = grand > 0 ? (r.total / grand) * 100 : 0;
            return `
                <tr>
                    <td class="ps-4 fw-semibold">${esc(r.date)}</td>
                    <td class="text-center"><span class="badge badge-light-primary">${r.count}</span></td>
                    <td class="text-end fw-bold text-success">${esc(CURRENCY)} ${fmt(r.total)}</td>
                    <td class="text-end text-info">${esc(CURRENCY)} ${fmt(r.tax)}</td>
                    <td class="text-end text-gray-600">${esc(CURRENCY)} ${fmt(r.average)}</td>
                    <td class="pe-4">
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:6px;">
                                <div class="progress-bar bg-primary" style="width:${Math.min(pct, 100)}%"></div>
                            </div>
                            <span class="fw-semibold text-gray-700" style="min-width:50px;">${pct.toFixed(1)}%</span>
                        </div>
                    </td>
                </tr>`;
        }).join('');

        if (badge) badge.textContent = `${rows.length} {{ __('accounting.days') }}`;
    }

    function renderDateBadge(start, end) {
        const fmtDate = (s) => {
            if (!s) return '—';
            const [y, m, d] = s.split('-');
            const names = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return `${d} ${names[(+m) - 1]} ${y}`;
        };
        const el = document.getElementById('expDateBadgeText');
        if (el) el.textContent = `${fmtDate(start)} — ${fmtDate(end)}`;
    }

    function renderActiveFilters() {
        const bar = document.getElementById('activeFiltersBar');
        const list = document.getElementById('activeFiltersList');
        if (!bar || !list) return;

        const chips = [];
        const label = (v, rows, keyFn) => rows.find(r => String(r.id) === String(v));
        const meta = metaCache || {};

        if (state.category_id) {
            const c = label(state.category_id, meta.categories || []);
            if (c) chips.push(['{{ __('accounting.category') }}', c.name]);
        }
        if (state.payment_method_id) {
            const c = label(state.payment_method_id, meta.payment_methods || []);
            if (c) chips.push(['{{ __('accounting.payment_method') }}', c.name]);
        }
        if (state.payment_status) {
            chips.push(['{{ __('accounting.payment_status') }}', state.payment_status]);
        }
        if (state.employee_id) {
            const c = label(state.employee_id, meta.employees || []);
            if (c) chips.push(['{{ __('accounting.employee') }}', `${c.first_name} ${c.last_name}`.trim()]);
        }
        if (state.vendor_name) {
            chips.push(['{{ __('accounting.vendor_name') }}', state.vendor_name]);
        }
        if (state.requires_receipt !== '' && state.requires_receipt != null) {
            chips.push(['{{ __('accounting.requires_receipt_filter') }}', state.requires_receipt === '1' ? '{{ __('accounting.yes') }}' : '{{ __('accounting.no') }}']);
        }
        if (state.is_recurring !== '' && state.is_recurring != null) {
            chips.push(['{{ __('accounting.recurring_filter') }}', state.is_recurring === '1' ? '{{ __('accounting.yes') }}' : '{{ __('accounting.no') }}']);
        }

        if (!chips.length) { bar.classList.add('d-none'); return; }

        bar.classList.remove('d-none');
        list.innerHTML = `
            <span class="text-muted fw-semibold me-2">
                <i class="ki-duotone ki-filter fs-5 me-1"></i>{{ __('accounting.active_filters') }}:
            </span>
            ${chips.map(([l, v]) => `<span class="badge badge-light-primary fs-7"><strong>${esc(l)}:</strong>&nbsp;${esc(v)}</span>`).join('')}
            <button type="button" id="clearAllFilters" class="btn btn-sm btn-link text-danger fs-7 p-0 ms-2">
                <i class="ki-duotone ki-cross fs-5 me-1"></i>{{ __('accounting.clear_all') }}
            </button>
        `;
        document.getElementById('clearAllFilters')?.addEventListener('click', () => {
            document.getElementById('expSummaryClear')?.click();
        });
    }

    function fmt(n) {
        return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.ExpenseSummary = { reload: loadData };
    return window.ExpenseSummary;
})();
</script>