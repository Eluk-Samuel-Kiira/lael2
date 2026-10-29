<script>
window.ExpenseRecurring = (function () {
    'use strict';

    const ROUTES = window.EXP_REC_ROUTES;
    const BOOT   = window.EXP_REC_BOOTSTRAP || {};
    let CURRENCY = '';
    let metaCache = null;
    let activeController = null;
    let inFlight = false;
    let chartInstance = null;

    const state = {
        frequency:   BOOT.frequency   || '',
        category_id: BOOT.category_id || '',
        location_id: BOOT.location_id || '',
        status:      BOOT.status      || 'active',
    };

    const FREQ_COLORS = {
        weekly:    'primary',
        monthly:   'success',
        quarterly: 'info',
        annually:  'warning',
    };

    document.addEventListener('DOMContentLoaded', () => {
        seedForm();
        bind();
        loadMeta().then(() => loadData());
    });

    function bind() {
        const form = document.getElementById('expRecFilterForm');
        form?.addEventListener('submit', (e) => {
            e.preventDefault();
            readForm();
            loadData();
        });

        document.getElementById('expRecClear')?.addEventListener('click', () => {
            state.frequency   = '';
            state.category_id = '';
            state.location_id = '';
            state.status      = 'active';
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
        const form = document.getElementById('expRecFilterForm');
        if (!form) return;
        Object.entries(state).forEach(([k, v]) => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) el.value = v == null ? '' : String(v);
        });
    }
    function readForm() {
        const form = document.getElementById('expRecFilterForm');
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
            fillSelect('frequency', json.frequencies, Object.keys(json.frequencies));
            fillSelect('category_id', json.categories, 'id', c => c.name + (c.code ? ' (' + c.code + ')' : ''));
            fillSelect('location_id', json.locations, 'id', 'name');
            return json;
        } catch (err) {
            console.error('[exp-recurring] meta failed:', err);
            throw err;
        }
    }

    function fillSelect(name, rows, valKey, labelFnOrKey) {
        const sel = document.querySelector(`#expRecFilterForm [name="${name}"]`);
        if (!sel || !rows) return;
        const label = typeof labelFnOrKey === 'function'
            ? labelFnOrKey
            : (typeof valKey === 'string' && typeof labelFnOrKey === 'string'
                ? (row) => row[labelFnOrKey]
                : (row) => row);

        const placeholder = sel.querySelector('option[value=""]');
        sel.innerHTML = '';
        if (placeholder) sel.appendChild(placeholder);

        if (Array.isArray(rows)) {
            rows.forEach(row => {
                const o = document.createElement('option');
                o.value = row[valKey];
                o.textContent = label(row);
                sel.appendChild(o);
            });
        } else {
            // Object form (frequencies)
            Object.entries(rows).forEach(([key, val]) => {
                const o = document.createElement('option');
                o.value = key;
                o.textContent = val;
                sel.appendChild(o);
            });
        }
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
            renderTable(json.recurring || []);
            renderChart(json.monthly_projection || []);
            renderFrequencyCards(json.by_frequency || {});
            renderUpcoming(json.upcoming || []);
            renderActiveFilters();
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[exp-recurring] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load recurring expenses.');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function showState(which) {
        document.getElementById('recLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('recWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('recEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function renderStats(s, currency) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        const money = (n) => `${currency} ${fmt(n)}`;
        set('statTotalRecurring', fmt(s.total_recurring || 0));
        set('statMonthlyCost',    money(s.total_monthly_cost || 0));
        set('statAnnual',         money(s.total_annual_cost || 0));
        set('statUpcoming',       fmt(s.upcoming_30_days || 0));
        set('statUpcomingAmount', money(s.upcoming_amount || 0));
        set('statAvg',            money(s.avg_per_expense || 0));
    }

    function renderTable(rows) {
        const tbody = document.getElementById('recBody');
        const badge = document.getElementById('recCountBadge');
        if (!tbody) return;

        if (!rows.length) { showState('empty'); return; }

        const today = new Date(); today.setHours(0, 0, 0, 0);

        tbody.innerHTML = rows.map(e => {
            const next = e.next_recurring_date ? new Date(e.next_recurring_date) : null;
            const daysUntil = next ? Math.round((next - today) / 86400000) : null;
            const freqColor = FREQ_COLORS[e.frequency] || 'secondary';

            let statusColor, statusText, statusIcon = '';
            if (!next) {
                statusColor = 'secondary'; statusText = '{{ __('accounting.no_date') }}';
            } else if (daysUntil < 0) {
                statusColor = 'danger'; statusText = '{{ __('accounting.overdue') }}';
                statusIcon = '<i class="ki-duotone ki-warning fs-4 ms-1"></i>';
            } else if (daysUntil <= 7) {
                statusColor = 'warning'; statusText = '{{ __('accounting.upcoming') }}';
                statusIcon = '<i class="ki-duotone ki-time fs-4 ms-1"></i>';
            } else {
                statusColor = 'success'; statusText = '{{ __('accounting.active') }}';
            }

            const multiplier = { weekly: 52, monthly: 12, quarterly: 4, annually: 1 }[e.frequency] || 12;
            const annualTotal = e.total_amount * multiplier;

            let daysCell = '<span class="text-muted">-</span>';
            if (daysUntil !== null) {
                if (daysUntil < 0) {
                    daysCell = `<span class="text-danger fw-bold">${Math.abs(daysUntil)} {{ __('accounting.days_overdue') }}</span>`;
                } else if (daysUntil === 0) {
                    daysCell = `<span class="text-warning fw-bold">{{ __('accounting.due_today') }}</span>`;
                } else {
                    daysCell = `<span class="text-gray-700 fw-semibold">${daysUntil} {{ __('accounting.days') }}</span>`;
                }
            }

            return `
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-40px symbol-circle me-3">
                                <div class="symbol-label bg-light-primary">
                                    <i class="ki-duotone ki-repeat fs-2"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                            </div>
                            <div class="d-flex justify-content-start flex-column">
                                <span class="text-gray-800 fw-bold">${esc(e.description)}</span>
                                ${e.vendor_name ? `<small class="text-muted">{{ __('accounting.vendor') }}: ${esc(e.vendor_name)}</small>` : ''}
                                <small class="text-muted">{{ __('accounting.expense_number') }}: ${esc(e.expense_number || '')}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-light-info">${esc(e.category_name)}</span></td>
                    <td><span class="badge badge-light-${freqColor}">${esc(e.frequency)}</span></td>
                    <td>
                        ${next
                            ? `<span class="fw-semibold text-gray-800">${esc(e.next_recurring_date)}</span>
                               <div class="mt-1"><small class="text-muted">${next.toLocaleDateString('en-GB', { weekday: 'short', month: 'short', day: 'numeric' })}</small></div>`
                            : `<span class="text-muted">{{ __('accounting.not_set') }}</span>`}
                    </td>
                    <td><span class="fw-bold text-success">${esc(CURRENCY)} ${fmt(e.total_amount)}</span></td>
                    <td><span class="fw-bold text-info">${esc(CURRENCY)} ${fmt(annualTotal)}</span></td>
                    <td>
                        ${e.payment_method_name
                            ? `<span class="badge badge-light-primary">${esc(e.payment_method_name)}</span>`
                            : `<span class="badge badge-light-secondary">{{ __('accounting.no_payment_method') }}</span>`}
                    </td>
                    <td><span class="badge badge-light-${statusColor}">${statusText}${statusIcon}</span></td>
                    <td>${daysCell}</td>
                </tr>`;
        }).join('');

        if (badge) badge.textContent = `${rows.length} {{ __('accounting.expenses') }}`;
        showState('table');
    }

    function renderChart(projection) {
        const card = document.getElementById('chartCard');
        if (!card) return;
        if (!projection || !projection.length) { card.style.display = 'none'; return; }
        card.style.display = '';

        const el = document.querySelector('#monthlyProjectionChart');
        if (!el || typeof ApexCharts === 'undefined') return;
        if (chartInstance) { try { chartInstance.destroy(); } catch(e) {} }

        const opts = {
            series: [
                { name: '{{ __('accounting.projected_amount') }}', data: projection.map(r => r.projected_total), type: 'column' },
                { name: '{{ __('accounting.expense_count') }}',    data: projection.map(r => r.expense_count),   type: 'line' },
            ],
            chart: { type: 'line', height: 400, toolbar: { show: true } },
            stroke: { width: [0, 3], curve: 'smooth' },
            colors: ['#3E97FF', '#50CD89'],
            plotOptions: { bar: { columnWidth: '50%' } },
            xaxis: {
                categories: projection.map(r => r.month),
                labels: { style: { fontSize: '11px' } },
            },
            yaxis: [
                {
                    title: { text: '{{ __('accounting.amount') }} (' + CURRENCY + ')' },
                    labels: { formatter: (v) => CURRENCY + Number(v).toLocaleString(undefined, { minimumFractionDigits: 0 }) },
                },
                {
                    opposite: true,
                    title: { text: '{{ __('accounting.expense_count') }}' },
                    labels: { formatter: (v) => Number(v).toFixed(0) },
                },
            ],
            tooltip: {
                y: [
                    { formatter: (v) => CURRENCY + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) },
                    { formatter: (v) => v + ' {{ __('accounting.expenses') }}' },
                ],
            },
            legend: { position: 'top', horizontalAlign: 'center' },
            dataLabels: { enabled: false },
        };

        chartInstance = new ApexCharts(el, opts);
        chartInstance.render();
    }

    function renderFrequencyCards(byFreq) {
        const row = document.getElementById('freqCardRow');
        const body = document.getElementById('freqCardBody');
        if (!row || !body) return;

        const entries = Object.entries(byFreq || {});
        if (!entries.length) { row.style.display = 'none'; return; }
        row.style.display = '';

        body.innerHTML = entries.map(([freq, data]) => {
            const color = FREQ_COLORS[freq] || 'secondary';
            return `
                <div class="col-md-6 col-lg-3">
                    <div class="card card-flush bg-light-${color} border border-${color} border-dashed h-100">
                        <div class="card-body d-flex flex-column justify-content-center text-center">
                            <div class="mb-3">
                                <i class="ki-duotone ki-repeat fs-2tx text-${color}"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                            <div class="mb-2">
                                <span class="fs-2 fw-bold text-gray-800 d-block">${data.count}</span>
                                <small class="text-gray-600 fw-semibold d-block mt-1">${esc(freq)} {{ __('accounting.expenses') }}</small>
                            </div>
                            <div class="border-top border-gray-300 pt-2 mt-2">
                                <span class="fs-4 fw-bold text-gray-700 d-block">${esc(CURRENCY)} ${fmt(data.total_monthly)}</span>
                                <small class="text-gray-600 fw-semibold d-block mt-1">{{ __('accounting.monthly_total') }}</small>
                            </div>
                            <div class="border-top border-gray-300 pt-2 mt-2">
                                <span class="fs-4 fw-bold text-success d-block">${esc(CURRENCY)} ${fmt(data.total_annual)}</span>
                                <small class="text-gray-600 fw-semibold d-block mt-1">{{ __('accounting.annual_projection') }}</small>
                            </div>
                        </div>
                    </div>
                </div>`;
        }).join('');
    }

    function renderUpcoming(rows) {
        const card = document.getElementById('upcomingCard');
        const tbody = document.getElementById('upcomingBody');
        if (!card || !tbody) return;
        if (!rows.length) { card.style.display = 'none'; return; }
        card.style.display = '';

        const today = new Date(); today.setHours(0, 0, 0, 0);

        tbody.innerHTML = rows.map(e => {
            const next = e.next_recurring_date ? new Date(e.next_recurring_date) : null;
            const daysUntil = next ? Math.round((next - today) / 86400000) : null;

            let statusColor, statusText, statusIcon = '';
            if (daysUntil !== null && daysUntil < 0) {
                statusColor = 'danger'; statusText = '{{ __('accounting.overdue') }}';
            } else if (daysUntil === 0) {
                statusColor = 'warning'; statusText = '{{ __('accounting.due_today') }}';
                statusIcon = '<i class="ki-duotone ki-warning fs-4 ms-1"></i>';
            } else if (daysUntil !== null && daysUntil <= 3) {
                statusColor = 'warning'; statusText = '{{ __('accounting.due_soon') }}';
                statusIcon = '<i class="ki-duotone ki-warning fs-4 ms-1"></i>';
            } else {
                statusColor = 'success'; statusText = '{{ __('accounting.scheduled') }}';
            }

            return `
                <tr>
                    <td class="ps-4 fw-semibold">
                        ${esc(e.next_recurring_date)}
                        <div class="mt-1"><small class="text-muted">${next ? next.toLocaleDateString('en-GB', { weekday: 'short' }) : ''}${daysUntil === 0 ? ' <span class="badge badge-light-warning badge-sm ms-1">{{ __('accounting.today') }}</span>' : ''}</small></div>
                    </td>
                    <td>
                        <span class="text-gray-800 fw-bold">${esc((e.description || '').slice(0, 40))}</span>
                        ${e.vendor_name ? `<div class="mt-1"><small class="text-muted">${esc(e.vendor_name)}</small></div>` : ''}
                    </td>
                    <td><span class="fw-bold text-success">${esc(CURRENCY)} ${fmt(e.total_amount)}</span></td>
                    <td><span class="badge badge-light-primary">${esc(e.frequency)}</span></td>
                    <td>${e.payment_method_name ? `<span class="badge badge-light-info">${esc(e.payment_method_name)}</span>` : `<span class="text-muted">N/A</span>`}</td>
                    <td><span class="badge badge-light-${statusColor}">${statusText}${statusIcon}</span></td>
                </tr>`;
        }).join('');
    }

    function renderActiveFilters() {
        const bar = document.getElementById('activeFiltersBar');
        const list = document.getElementById('activeFiltersList');
        if (!bar || !list) return;

        const meta = metaCache || {};
        const chips = [];

        if (state.frequency) chips.push(['{{ __('accounting.frequency') }}', meta.frequencies?.[state.frequency] || state.frequency]);
        if (state.category_id) {
            const c = (meta.categories || []).find(x => String(x.id) === String(state.category_id));
            if (c) chips.push(['{{ __('accounting.category') }}', c.name]);
        }
        if (state.location_id) {
            const l = (meta.locations || []).find(x => String(x.id) === String(state.location_id));
            if (l) chips.push(['{{ __('accounting.location') }}', l.name]);
        }
        if (state.status && state.status !== 'active') {
            chips.push(['{{ __('accounting.status') }}', state.status]);
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
            document.getElementById('expRecClear')?.click();
        });
    }

    function fmt(n) {
        return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.ExpenseRecurring = { reload: loadData };
    return window.ExpenseRecurring;
})();
</script>