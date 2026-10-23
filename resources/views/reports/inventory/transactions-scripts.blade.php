<script>
window.InventoryTransactions = (function () {
    'use strict';

    const ROUTE       = window.TX_ROUTES.data;
    const DETAIL_ROUTE= window.TX_ROUTES.detail;
    const CURRENCY    = window.REPORT_CURRENCY || '';
    const BOOT        = window.TX_BOOTSTRAP || {};

    const state = {
        startDate:    BOOT.startDate    || '',
        endDate:      BOOT.endDate      || '',
        type:         BOOT.type         || 'all',
        departmentId: BOOT.departmentId || '',
        locationId:   BOOT.locationId   || '',
        variantId:    BOOT.variantId    || '',
        perPage:      Number(BOOT.perPage || 15),
        page:         1,
    };

    const TYPE_COLORS = {
        purchase:     'success',
        sale:         'danger',
        return:       'info',
        adjustment:   'warning',
        transfer_in:  'primary',
        transfer_out: 'secondary',
    };
    const TYPE_HEX = {
        purchase:     '#50CD89',
        sale:         '#F1416C',
        return:       '#3E97FF',
        adjustment:   '#FFC700',
        transfer_in:  '#7239EA',
        transfer_out: '#7E8299',
    };
    const TYPE_ICONS = {
        purchase:     'bi-basket',
        sale:         'bi-cart',
        return:       'bi-arrow-return-left',
        adjustment:   'bi-sliders',
        transfer_in:  'bi-box-arrow-in-down',
        transfer_out: 'bi-box-arrow-up',
    };

    let typeChart  = null;
    let dailyChart = null;
    let inFlight   = false;
    let drawerBs   = null;

    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('txApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('txClearBtn')?.addEventListener('click', resetFilters);
        $('txExportBtn')?.addEventListener('click', exportCsv);
        $('txTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.tx-row[data-id]');
            if (!row) return;
            openDrawer(row.dataset.id);
        });

        document.getElementById('txDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('txDrawerContent').innerHTML = '';
            $('txDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate    = $('txStartDate')?.value   || '';
        state.endDate      = $('txEndDate')?.value     || '';
        state.type         = $('txType')?.value        || 'all';
        state.variantId    = $('txVariant')?.value     || '';
        state.locationId   = $('txLocation')?.value    || '';
        // departmentId set from wherever it lives in the shell
    }

    function resetFilters() {
        state.startDate = state.endDate = state.variantId = state.locationId = state.departmentId = '';
        state.type = 'all';
        state.page = 1;
        if ($('txStartDate')) $('txStartDate').value = '';
        if ($('txEndDate'))   $('txEndDate').value   = '';
        if ($('txType'))      $('txType').value      = 'all';
        if (window.jQuery) jQuery('#txVariant, #txLocation').val(null).trigger('change.select2');
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)    params.set('start_date',    state.startDate);
        if (state.endDate)      params.set('end_date',      state.endDate);
        if (state.type)         params.set('type',          state.type);
        if (state.variantId)    params.set('variant_id',    state.variantId);
        if (state.locationId)   params.set('location_id',   state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);

        try {
            const res = await fetch(`${ROUTE}?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            render(json.data);
            showSkeleton(false);
        } catch (err) {
            console.error('[transactions] load failed:', err);
            window.toastr?.error('Failed to load transactions.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('txSkeleton')?.classList.toggle('d-none', !v);
        $('txContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderTypeChart(p.type_summary);
        renderDailyChart(p.daily_trend);
        renderTypeTable(p.type_summary);
        renderTable(p.items, p.pagination);
    }

    function renderSummary(s) {
        $('sumTotalTx').textContent  = fmtNum(s.total_transactions, 0);
        $('sumTotalQty').textContent = fmtNum(s.total_quantity_moved, 0);
        $('sumPositive').textContent = '+' + fmtNum(s.positive_movements, 0);
        $('sumNegative').textContent = '-' + fmtNum(s.negative_movements, 0);
        $('sumNet').textContent = (s.net_change >= 0 ? '+' : '') + fmtNum(s.net_change, 0);
        $('sumAvgDaily').textContent = fmtNum(s.avg_daily, 1);
    }

    function renderTypeChart(rows) {
        const el = $('txTypeChart');
        const empty = $('txTypeEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (typeChart) { typeChart.destroy(); typeChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: rows.map(r => r.count),
            chart:  { type: 'donut', height: 320, fontFamily: 'inherit' },
            labels: rows.map(r => labelType(r.type)),
            colors: rows.map(r => TYPE_HEX[r.type] || '#A8A8A8'),
            legend: { position: 'bottom', fontSize: '12px' },
            plotOptions: { pie: { donut: {
                size: '60%',
                labels: {
                    show: true,
                    total: {
                        show: true, label: 'Total',
                        formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0),
                    },
                },
            }}},
            tooltip: { y: { formatter: (v, { seriesIndex }) => {
                const total = rows.reduce((s, r) => s + r.count, 0);
                const pct = total > 0 ? ((v / total) * 100).toFixed(1) : 0;
                return `${v} transactions (${pct}%)`;
            }}},
        };

        if (typeChart) typeChart.updateOptions(options, false, true);
        else { typeChart = new ApexCharts(el, options); typeChart.render(); }
    }

    function renderDailyChart(rows) {
        const el = $('txDailyChart');
        const empty = $('txDailyEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (dailyChart) { dailyChart.destroy(); dailyChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const labels = rows.map(r => {
            const d = new Date(r.date);
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        const counts = rows.map(r => r.count);

        // 7-day MA
        const ma = counts.map((_, i) => {
            let sum = 0, n = 0;
            for (let j = Math.max(0, i - 6); j <= i; j++) { sum += counts[j]; n++; }
            return n > 0 ? sum / n : 0;
        });

        const options = {
            series: [
                { name: 'Transactions', data: counts, type: 'bar'  },
                { name: '7-day avg',    data: ma,     type: 'line' },
            ],
            chart:  { type: 'line', height: 320, toolbar: { show: true }, zoom: { enabled: true }, fontFamily: 'inherit' },
            plotOptions: { bar: { columnWidth: '60%', borderRadius: 4 } },
            stroke: { width: [0, 3], curve: 'smooth' },
            xaxis: { categories: labels, labels: { rotate: -45, style: { fontSize: '10px' } } },
            yaxis: { min: 0, tickAmount: 5, title: { text: 'Transactions' } },
            colors: ['#3E97FF', '#F1416C'],
            markers: { size: [0, 3] },
            tooltip: { shared: true, intersect: false,
                       y: { formatter: (v, { seriesIndex }) =>
                            seriesIndex === 1 ? v.toFixed(1) + ' avg' : v + ' tx' } },
            legend: { position: 'top', horizontalAlign: 'center' },
        };

        if (dailyChart) dailyChart.updateOptions(options, false, true);
        else { dailyChart = new ApexCharts(el, options); dailyChart.render(); }
    }

    function renderTypeTable(rows) {
        const card = $('txTypeTableCard');
        const body = $('txTypeBody');
        if (!body) return;
        if (!rows || rows.length === 0) { card?.classList.add('d-none'); return; }
        card?.classList.remove('d-none');

        const totalCount = rows.reduce((s, r) => s + r.count, 0);
        const totalQty = rows.reduce((s, r) => s + Number(r.total_quantity), 0);

        body.innerHTML = rows.map(r => {
            const color = TYPE_COLORS[r.type] || 'dark';
            const pct = totalCount > 0 ? (r.count / totalCount) * 100 : 0;
            return `
                <tr>
                    <td><span class="badge badge-light-${color} fs-6 py-2 px-3">
                        <i class="bi ${TYPE_ICONS[r.type] || 'bi-circle'} me-1"></i>${labelType(r.type)}
                    </span></td>
                    <td class="text-center"><span class="fw-bold fs-5">${fmtNum(r.count, 0)}</span></td>
                    <td class="text-end">
                        <span class="fw-bold ${Number(r.total_quantity) >= 0 ? 'text-success' : 'text-danger'}">
                            ${Number(r.total_quantity) >= 0 ? '+' : ''}${fmtNum(r.total_quantity, 0)}
                        </span>
                    </td>
                    <td class="text-center"><span class="badge badge-light-secondary">${fmtNum(r.avg_quantity, 1)}</span></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height: 10px;">
                                <div class="progress-bar bg-${color}" style="width:${pct}%"></div>
                            </div>
                            <span class="fw-bold text-gray-700">${fmtNum(pct, 1)}%</span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        body.innerHTML += `
            <tr class="bg-light fw-bold">
                <td>Total</td>
                <td class="text-center">${fmtNum(totalCount, 0)}</td>
                <td class="text-end ${totalQty >= 0 ? 'text-success' : 'text-danger'}">
                    ${totalQty >= 0 ? '+' : ''}${fmtNum(totalQty, 0)}
                </td>
                <td class="text-center">-</td>
                <td class="text-end">100%</td>
            </tr>
        `;
    }

    function renderTable(rows, pagination) {
        const body = $('txTableBody');
        const empty = $('txEmpty');
        const wrap = $('txPaginationWrap');
        const badge = $('txBadge');
        const footer = $('txTableFooter');
        if (!body) return;

        if (!rows || rows.length === 0) {
            body.innerHTML = ''; empty?.classList.remove('d-none'); wrap?.classList.add('d-none');
            footer?.classList.add('d-none');
            if (badge) badge.textContent = '';
            return;
        }
        empty?.classList.add('d-none');
        wrap?.classList.remove('d-none');
        footer?.classList.remove('d-none');
        if (badge) badge.textContent = `Showing ${pagination.from} of ${pagination.total}`;

        body.innerHTML = rows.map(r => {
            const color = TYPE_COLORS[r.type] || 'dark';
            const icon  = TYPE_ICONS[r.type]  || 'bi-circle';
            const qty   = Number(r.quantity);
            const qtyCls = qty >= 0 ? 'success' : 'danger';
            const qtySign = qty >= 0 ? '+' : '';
            const imgHtml = r.image_url
                ? `<div class="symbol symbol-40px me-3"><img src="${esc(r.image_url)}" class="rounded" alt="${esc(r.variant_name)}"></div>`
                : '';

            const refHtml = r.reference_id && r.reference_type
                ? `<span class="badge badge-light-dark"><i class="bi bi-file-earmark-text me-1"></i>${esc((r.reference_type || '').toUpperCase().replace(/_/g, ' '))} #${r.reference_id}</span>`
                : '<span class="text-muted">-</span>';

            const notesHtml = r.notes
                ? `<span class="text-muted fs-7" title="${esc(r.notes)}">${esc((r.notes || '').substring(0, 40))}${(r.notes || '').length > 40 ? '…' : ''}</span>`
                : '<span class="text-muted">-</span>';

            return `
                <tr class="tx-row" data-id="${r.id}">
                    <td class="ps-4">
                        <div class="fw-semibold">${esc((r.created_at || '').split(' ')[0])}</div>
                        <small class="text-muted">${esc((r.created_at || '').split(' ')[1] || '')}</small>
                    </td>
                    <td><span class="fw-semibold text-primary">${esc(r.sku)}</span></td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${imgHtml}
                            <div>
                                <div class="fw-bold">${esc(r.variant_name)}</div>
                                ${r.product_name ? `<div class="text-muted fs-7">${esc(r.product_name)}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-${color} py-2 px-3">
                            <i class="bi ${icon} me-1"></i>${labelType(r.type)}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="fw-bold fs-5 text-${qtyCls}">${qtySign}${fmtNum(qty, 0)}</span>
                    </td>
                    <td><span class="badge badge-light-primary"><i class="bi bi-building me-1"></i>${esc(r.department_name)}</span></td>
                    <td><span class="badge badge-light-info"><i class="bi bi-geo-alt me-1"></i>${esc(r.location_name)}</span></td>
                    <td>${refHtml}</td>
                    <td>${notesHtml}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-icon btn-light-primary"><i class="bi bi-eye fs-4"></i></button>
                    </td>
                </tr>
            `;
        }).join('');

        const pageQty = rows.reduce((s, r) => s + Number(r.quantity), 0);
        $('ftPageQty').innerHTML = `<span class="text-${pageQty >= 0 ? 'success' : 'danger'}">${pageQty >= 0 ? '+' : ''}${fmtNum(pageQty, 0)}</span>`;

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('txPaginationInfo');
        const ul   = $('txPagination');
        if (!ul || !info) return;

        info.textContent = `Showing ${p.from} to ${p.to} of ${p.total}`;
        ul.innerHTML = '';
        const add = (page, label, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link'; a.href = '#'; a.textContent = label;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                if (disabled || active) return;
                state.page = page; load();
            });
            li.appendChild(a); ul.appendChild(li);
        };
        add(p.current_page - 1, 'Prev', false, p.current_page <= 1);
        for (let i = Math.max(1, p.current_page - 2); i <= Math.min(p.last_page, p.current_page + 2); i++) {
            add(i, i, i === p.current_page);
        }
        add(p.current_page + 1, 'Next', false, p.current_page >= p.last_page);
    }

    // ─── Drawer ──────────────────────────────────────────────
    function getDrawer() {
        const el = document.getElementById('txDrawer');
        if (!el) return null;
        drawerBs = drawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return drawerBs;
    }

    async function openDrawer(id) {
        const inst = getDrawer();
        if (!inst) return;
        $('txDrawerSkeleton')?.classList.remove('d-none');
        const content = $('txDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        $('txDrawerSubtitle').textContent = '';
        inst.show();

        try {
            const res = await fetch(DETAIL_ROUTE.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            renderDrawer(json.data);
        } catch (err) {
            console.error('[tx-drawer] load failed:', err);
            $('txDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderDrawer(d) {
        $('txDrawerSkeleton')?.classList.add('d-none');
        $('txDrawerSubtitle').textContent = `#${d.id} · ${d.created_at || ''}`;

        const content = $('txDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        const qty = Number(d.quantity);
        const qtyCls = qty >= 0 ? 'success' : 'danger';
        const qtySign = qty >= 0 ? '+' : '';
        const color = TYPE_COLORS[d.type] || 'dark';
        const icon  = TYPE_ICONS[d.type]  || 'bi-circle';
        const profitCls = Number(d.gross_profit) >= 0 ? 'success' : 'danger';

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">${esc(d.variant.name)}</h4>
                    <div class="text-muted fs-7">${esc(d.variant.sku)} · ${esc(d.variant.product_name)}</div>
                </div>
                <span class="badge badge-light-${color} fs-6 py-2 px-3">
                    <i class="bi ${icon} me-1"></i>${esc(d.type_label)}
                </span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="card card-flush bg-light-${qtyCls} border border-${qtyCls} border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">Quantity</span>
                            <div class="fs-2 fw-bold text-${qtyCls}">${qtySign}${fmtNum(qty, 0)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card card-flush bg-light-secondary border border-secondary border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">Adjusted by</span>
                            <div class="fs-6 fw-bold">${esc(d.created_by)}</div>
                        </div>
                    </div>
                </div>
            </div>

            ${d.reference_label ? `
            <h6 class="fw-bold mb-3"><i class="bi bi-link-45deg me-2 text-primary"></i>Reference</h6>
            <div class="border rounded p-3 mb-4">
                <span class="badge badge-light-dark fs-6"><i class="bi bi-file-earmark-text me-1"></i>${esc(d.reference_label)}</span>
            </div>
            ` : ''}

            <h6 class="fw-bold mb-3"><i class="bi bi-chat-left-text me-2 text-primary"></i>Notes</h6>
            <div class="border rounded p-3 mb-4">
                ${d.notes ? `<div class="text-gray-800">${esc(d.notes)}</div>` : '<em class="text-muted">No notes recorded</em>'}
            </div>

            <h6 class="fw-bold mb-3"><i class="bi bi-currency-dollar me-2 text-primary"></i>Pricing Snapshot (Frozen)</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <tbody>
                        <tr><th class="w-50">Unit cost</th><td class="text-end">${money(d.unit_cost_price)}</td></tr>
                        <tr><th>Unit selling price</th><td class="text-end">${money(d.unit_selling_price)}</td></tr>
                        <tr><th>Total cost value</th><td class="text-end">${money(d.total_cost_value)}</td></tr>
                        <tr><th>Total selling value</th><td class="text-end">${money(d.total_selling_value)}</td></tr>
                        <tr class="bg-light">
                            <th>Gross profit (frozen)</th>
                            <td class="text-end fw-bold text-${profitCls}">${money(d.gross_profit)}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h6 class="fw-bold mb-3 mt-4"><i class="bi bi-geo-alt me-2 text-primary"></i>Context</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <tbody>
                        <tr><th class="w-50">Department</th><td>${esc(d.department)}</td></tr>
                        <tr><th>Location</th><td>${esc(d.location)}</td></tr>
                        <tr><th>Date</th><td>${esc(d.created_at)}</td></tr>
                    </tbody>
                </table>
            </div>
        `;
    }

    function exportCsv() {
        const params = new URLSearchParams();
        if (state.startDate)    params.set('start_date',    state.startDate);
        if (state.endDate)      params.set('end_date',      state.endDate);
        if (state.type)         params.set('type',          state.type);
        if (state.variantId)    params.set('variant_id',    state.variantId);
        if (state.locationId)   params.set('location_id',   state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);
        window.location.href = `/reports/inventory/transactions?${params.toString()}&export=1`;
    }

    function labelType(t) {
        return (t || '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t); return d.innerHTML;
    }
    function fmtNum(n, d = 0) {
        return Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: d, maximumFractionDigits: d,
        });
    }
    function money(n) { return CURRENCY + fmtNum(n, 2); }

    return { load };
})();
</script>