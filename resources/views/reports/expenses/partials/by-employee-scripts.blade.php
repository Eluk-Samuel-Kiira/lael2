<script>
window.ExpenseByEmployee = (function () {
    'use strict';

    const ROUTES = window.EXP_EMP_ROUTES;
    const BOOT   = window.EXP_EMP_BOOTSTRAP || {};
    let CURRENCY = '';
    let metaCache = null;
    let activeController = null;
    let inFlight = false;
    let chartInstance = null;

    const state = {
        start_date:        BOOT.start_date        || '',
        end_date:          BOOT.end_date          || '',
        employee_id:       BOOT.employee_id       || '',
        location_id:       BOOT.location_id       || '',
        requires_approval: BOOT.requires_approval || '',
    };

    document.addEventListener('DOMContentLoaded', () => {
        seedForm();
        bind();
        loadMeta().then(() => loadData());
    });

    function bind() {
        const form = document.getElementById('expEmpFilterForm');
        form?.addEventListener('submit', (e) => {
            e.preventDefault();
            readForm();
            loadData();
        });

        document.getElementById('expEmpClear')?.addEventListener('click', () => {
            state.start_date        = BOOT.start_date;
            state.end_date          = BOOT.end_date;
            state.employee_id       = '';
            state.location_id       = '';
            state.requires_approval = '';
            seedForm();
            loadData();
        });

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

    function seedForm() {
        const form = document.getElementById('expEmpFilterForm');
        if (!form) return;
        Object.entries(state).forEach(([k, v]) => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) el.value = v == null ? '' : String(v);
        });
    }
    function readForm() {
        const form = document.getElementById('expEmpFilterForm');
        if (!form) return;
        Object.keys(state).forEach(k => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) state[k] = el.value;
        });
    }

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
            fillSelect('employee_id', json.employees, 'id', e => `${e.first_name} ${e.last_name}`.trim());
            fillSelect('location_id', json.locations, 'id', 'name');
            return json;
        } catch (err) {
            console.error('[exp-by-emp] meta failed:', err);
            throw err;
        }
    }

    function fillSelect(name, rows, valKey, labelFnOrKey) {
        const sel = document.querySelector(`#expEmpFilterForm [name="${name}"]`);
        if (!sel || !Array.isArray(rows)) return;
        const label = typeof labelFnOrKey === 'function' ? labelFnOrKey : (row) => row[labelFnOrKey];
        const placeholder = sel.querySelector('option[value=""]');
        sel.innerHTML = '';
        if (placeholder) sel.appendChild(placeholder);
        rows.forEach(row => {
            const o = document.createElement('option');
            o.value = row[valKey];
            o.textContent = label(row);
            sel.appendChild(o);
        });
        sel.value = state[name] || '';
    }

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
            renderTable(json.employees || [], json.totals || {});
            renderChart(json.chart);
            renderDateBadge(json.meta.start_date, json.meta.end_date);
            renderActiveFilters();
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[exp-by-emp] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load employee expenses.');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function showState(which) {
        document.getElementById('empLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('empWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('empEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function renderStats(s, currency) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        const money = (n) => `${currency} ${fmt(n)}`;
        set('statTotalEmployees',    fmt(s.total_employees || 0));
        set('statTotalTransactions', fmt(s.total_expenses || 0));
        set('statGrandTotal',        money(s.total_amount || 0));
        set('statTotalTax',          money(s.total_tax || 0));
        set('statAvgPerEmployee',    money(s.avg_per_employee || 0));
        set('statTopEmployee',       s.top_employee || '—');
    }

    function renderTable(rows, totals) {
        const tbody = document.getElementById('empBody');
        const tfoot = document.getElementById('empFoot');
        const badge = document.getElementById('employeeCountBadge');
        if (!tbody) return;

        if (!rows.length) { showState('empty'); if (badge) badge.textContent = '0'; return; }

        const totalGrand = (totals.grand_total) || 0;

        tbody.innerHTML = rows.map((e, i) => {
            const pct = totalGrand > 0 ? (e.grand_total / totalGrand) * 100 : 0;

            const statusBadges = [];
            if (e.pending_count > 0) statusBadges.push(`<span class="badge badge-light-warning">Pending: ${e.pending_count}</span>`);
            if (e.paid_count > 0) statusBadges.push(`<span class="badge badge-light-success">Paid: ${e.paid_count}</span>`);
            if (e.reimbursed_count > 0) statusBadges.push(`<span class="badge badge-light-info">Reimbursed: ${e.reimbursed_count}</span>`);

            return `
                <tr>
                    <td class="text-center fw-bold">${i + 1}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-35px symbol-circle me-3">
                                <div class="symbol-label bg-light-primary">
                                    <i class="ki-duotone ki-user fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                </div>
                            </div>
                            <div class="d-flex justify-content-start flex-column">
                                <span class="text-gray-800 fw-bold">${esc(e.employee_name)}</span>
                                ${e.department ? `<small class="text-muted"><i class="ki-duotone ki-briefcase fs-5 me-1"></i>${esc(e.department)}</small>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>${esc(e.department || 'N/A')}</td>
                    <td class="text-center"><span class="badge badge-light-primary">${e.expense_count}</span></td>
                    <td class="text-end fw-bold text-success">${esc(CURRENCY)} ${fmt(e.grand_total)}</td>
                    <td class="text-end text-gray-600">${esc(CURRENCY)} ${fmt(e.average_expense)}</td>
                    <td class="text-end text-danger">${esc(CURRENCY)} ${fmt(e.max_expense)}</td>
                    <td><div class="d-flex gap-1 flex-wrap">${statusBadges.join('')}</div></td>
                    <td class="text-center">
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:6px;">
                                <div class="progress-bar bg-primary" style="width:${pct}%"></div>
                            </div>
                            <span class="fw-bold min-w-45px">${pct.toFixed(1)}%</span>
                        </div>
                    </td>
                </tr>`;
        }).join('');

        tfoot.innerHTML = `
            <tr class="fw-bold">
                <td colspan="4" class="text-end">{{ __('accounting.total') }}:</td>
                <td class="text-end text-success">${esc(CURRENCY)} ${fmt(totals.grand_total)}</td>
                <td class="text-end">${esc(CURRENCY)} ${fmt(totals.average)}</td>
                <td class="text-end text-danger">${esc(CURRENCY)} ${fmt(totals.max_expense)}</td>
                <td colspan="2"></td>
            </tr>`;

        if (badge) badge.textContent = `${rows.length} {{ __('accounting.employees') }}`;
        showState('table');
    }

    function renderChart(chart) {
        const card = document.getElementById('chartCard');
        if (!card) return;
        const has = chart && chart.employees?.length && chart.months?.length;
        if (!has) { card.style.display = 'none'; return; }
        card.style.display = '';

        const el = document.querySelector('#monthlySpendingChart');
        if (!el || typeof ApexCharts === 'undefined') return;
        if (chartInstance) { try { chartInstance.destroy(); } catch(e) {} }

        const series = chart.employees.map(name => ({
            name,
            type: 'line',
            data: chart.months.map(m => {
                const key = m.key;
                return (chart.matrix[key] && chart.matrix[key][name]) || 0;
            }),
        }));

        const opts = {
            series,
            chart: { type: 'line', height: 400, toolbar: { show: true } },
            stroke: { width: 3, curve: 'smooth' },
            colors: ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C'],
            xaxis: {
                categories: chart.months.map(m => m.label),
                labels: { rotate: -45 },
            },
            yaxis: {
                title: { text: '{{ __('accounting.amount') }} (' + CURRENCY + ')' },
                labels: { formatter: (val) => CURRENCY + Number(val).toLocaleString() },
            },
            tooltip: {
                y: { formatter: (val) => CURRENCY + Number(val).toLocaleString(undefined, {
                    minimumFractionDigits: 2, maximumFractionDigits: 2,
                })},
            },
            legend: { position: 'top', horizontalAlign: 'center' },
            markers: { size: 5 },
            grid: { borderColor: '#f1f1f1' },
        };

        chartInstance = new ApexCharts(el, opts);
        chartInstance.render();
    }

    function renderDateBadge(start, end) {
        const f = (s) => {
            if (!s) return '—';
            const [y, m, d] = s.split('-');
            const names = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return `${d} ${names[(+m) - 1]} ${y}`;
        };
        const el = document.getElementById('expDateBadgeText');
        if (el) el.textContent = `${f(start)} — ${f(end)}`;
    }

    function renderActiveFilters() {
        const bar = document.getElementById('activeFiltersBar');
        const list = document.getElementById('activeFiltersList');
        if (!bar || !list) return;

        const meta = metaCache || {};
        const chips = [];

        if (state.employee_id) {
            const e = (meta.employees || []).find(x => String(x.id) === String(state.employee_id));
            if (e) chips.push(['{{ __('accounting.employee') }}', `${e.first_name} ${e.last_name}`.trim()]);
        }
        if (state.location_id) {
            const l = (meta.locations || []).find(x => String(x.id) === String(state.location_id));
            if (l) chips.push(['{{ __('accounting.location') }}', l.name]);
        }
        if (state.requires_approval !== '' && state.requires_approval != null) {
            chips.push(['{{ __('accounting.requires_approval') }}', state.requires_approval === '1' ? '{{ __('accounting.yes') }}' : '{{ __('accounting.no') }}']);
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
            </button>`;
        document.getElementById('clearAllFilters')?.addEventListener('click', () => {
            document.getElementById('expEmpClear')?.click();
        });
    }

    function fmt(n) {
        return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.ExpenseByEmployee = { reload: loadData };
    return window.ExpenseByEmployee;
})();
</script>