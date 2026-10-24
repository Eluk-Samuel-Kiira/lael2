<script>
window.SupplierSpendAnalysis = (function () {
    'use strict';

    const ROUTE        = window.SS_ROUTES.data;
    const DETAIL_ROUTE = window.SS_ROUTES.detail;
    const CURRENCY     = window.REPORT_CURRENCY || '';
    const BOOT         = window.SS_BOOTSTRAP || {};

    const state = {
        startDate:  BOOT.startDate  || '',
        endDate:    BOOT.endDate    || '',
        supplierId: BOOT.supplierId || '',
        period:     BOOT.period     || 'monthly',
        perPage:    Number(BOOT.perPage || 15),
        page:       1,
    };

    let trendChart = null;
    let drawerBs   = null;
    let inFlight   = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        if (!DETAIL_ROUTE) console.error('[supplier-spend] REPORT_ROUTES.detail is undefined');

        $('ssApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('ssClearBtn')?.addEventListener('click', resetFilters);
        $('ssExportBtn')?.addEventListener('click', exportCsv);

        $('ssTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.ss-row[data-id]');
            if (!row) return;
            openDrawer(row.dataset.id);
        });

        document.getElementById('ssDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('ssDrawerContent').innerHTML = '';
            $('ssDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate  = $('ssStartDate')?.value  || '';
        state.endDate    = $('ssEndDate')?.value    || '';
        state.supplierId = $('ssSupplier')?.value   || '';
        state.period     = $('ssPeriod')?.value     || 'monthly';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.supplierId = '';
        state.period = 'monthly';
        state.page = 1;
        if ($('ssStartDate')) $('ssStartDate').value = '';
        if ($('ssEndDate'))   $('ssEndDate').value   = '';
        if ($('ssPeriod'))    $('ssPeriod').value    = 'monthly';
        if (window.jQuery) jQuery('#ssSupplier').val(null).trigger('change.select2');
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)  params.set('start_date',  state.startDate);
        if (state.endDate)    params.set('end_date',    state.endDate);
        if (state.supplierId) params.set('supplier_id', state.supplierId);
        if (state.period)     params.set('period',      state.period);

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
            console.error('[supplier-spend] load failed:', err);
            window.toastr?.error('Failed to load supplier spend analysis.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('ssSkeleton')?.classList.toggle('d-none', !v);
        $('ssContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderTrend(p.spend_trend);
        renderTable(p.suppliers, p.pagination);
    }

    function renderSummary(s) {
        $('sumSuppliers').textContent = fmtNum(s.total_suppliers, 0);
        $('sumSpent').textContent     = money(s.total_spent);
        $('sumAvgOrder').textContent  = money(s.avg_order_value);
        $('sumOrderCount').textContent = `${fmtNum(s.total_orders, 0)} orders`;
        if (s.top_supplier) {
            $('sumTopName').textContent  = s.top_supplier.name;
            $('sumTopSpent').textContent = money(s.top_supplier.total_spent);
        } else {
            $('sumTopName').textContent  = '—';
            $('sumTopSpent').textContent = '';
        }
    }

    function renderTrend(rows) {
        const el = $('ssTrendChart');
        const empty = $('ssTrendEmpty');
        if (!el) return;

        const hasData = rows && rows.some(r => Number(r.value) > 0);
        if (!hasData) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (trendChart) { trendChart.destroy(); trendChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        $('ssPeriodBadge').textContent = `${state.startDate} → ${state.endDate}`;

        const options = {
            series: [{ name: 'Spent', data: rows.map(r => Number(r.value)) }],
            chart:  { type: 'area', height: 400, toolbar: { show: true }, zoom: { enabled: true }, fontFamily: 'inherit' },
            colors: ['#3E97FF'],
            dataLabels: {
                enabled: true,
                formatter: v => Number(v) === 0 ? '0' : CURRENCY + Number(v).toLocaleString(),
                offsetY: -10,
                style: { fontSize: '10px', fontWeight: 'bold' },
            },
            stroke: { curve: 'smooth', width: 3 },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.2, stops: [0, 90, 100] },
            },
            xaxis: {
                categories: rows.map(r => r.label),
                labels: { rotate: -45, style: { fontSize: '11px' }, trim: true },
                title: { text: 'Period', style: { fontSize: '12px', fontWeight: 'bold' } },
            },
            yaxis: {
                title: { text: 'Total Spent (' + CURRENCY + ')' },
                labels: { formatter: v => CURRENCY + Number(v).toLocaleString() },
            },
            tooltip: { y: { formatter: v => money(v) } },
            legend: { position: 'top', horizontalAlign: 'center' },
            markers: { size: 4, hover: { size: 6 } },
        };

        if (trendChart) trendChart.updateOptions(options, false, true);
        else { trendChart = new ApexCharts(el, options); trendChart.render(); }
    }

    function renderTable(rows, pagination) {
        const body = $('ssTableBody');
        const empty = $('ssEmpty');
        const wrap = $('ssPaginationWrap');
        const badge = $('ssBadge');
        const footer = $('ssTableFooter');
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

        body.innerHTML = rows.map((r, i) => {
            const idx       = (pagination.from || 1) + i;
            const cls       = r.classification || 'C';
            const clsColor  = r.class_color || 'success';
            const pct       = Number(r.spend_percentage);
            const initials  = (r.name || '?').substring(0, 2).toUpperCase();

            // Days since last order
            let lastOrderHtml = '<span class="text-muted">Never</span>';
            if (r.last_order_date) {
                const last = new Date(r.last_order_date);
                const diffDays = Math.floor((Date.now() - last.getTime()) / 86400000);
                const col = diffDays > 180 ? 'danger' : diffDays > 90 ? 'warning' : 'success';
                lastOrderHtml = `
                    <span class="text-${col} fw-semibold">${esc(r.last_order_date)}</span>
                    ${diffDays > 30 ? `<div class="text-muted fs-8">${diffDays} days ago</div>` : ''}
                `;
            }

            return `
                <tr class="ss-row" data-id="${r.id}">
                    <td class="ps-4 fw-bold">${idx}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-45px me-3">
                                <div class="symbol-label bg-light-${clsColor} text-${clsColor} fw-bold">${esc(initials)}</div>
                            </div>
                            <div>
                                <div class="fw-bold">${esc(r.name)}</div>
                                <small class="text-muted">${esc(r.contact_person || '—')}</small>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="fw-bold fs-5">${fmtNum(r.order_count, 0)}</span>
                        <div class="text-muted fs-8">orders</div>
                    </td>
                    <td class="text-end">
                        <span class="fw-bold text-primary fs-5">${money(r.total_spent)}</span>
                    </td>
                    <td class="text-center">
                        <div class="d-flex align-items-center justify-content-end">
                            <span class="fw-bold me-2">${fmtNum(pct, 1)}%</span>
                            <div class="progress w-80px" style="height:6px;">
                                <div class="progress-bar bg-${clsColor}" style="width:${Math.min(100, pct)}%"></div>
                            </div>
                        </div>
                        <small class="text-muted fs-8">Class: <span class="fw-bold text-${clsColor}">${cls}</span></small>
                    </td>
                    <td class="text-end"><span class="fw-semibold">${money(r.avg_order_value)}</span></td>
                    <td class="text-end"><span class="text-muted">${money(r.min_order_value)}</span></td>
                    <td class="text-end"><span class="text-success fw-semibold">${money(r.max_order_value)}</span></td>
                    <td>${lastOrderHtml}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-icon btn-light-primary">
                            <i class="bi bi-eye fs-5"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        // Footer totals for this page
        const pageSpent  = rows.reduce((s, r) => s + Number(r.total_spent || 0), 0);
        const pageOrders = rows.reduce((s, r) => s + Number(r.order_count || 0), 0);
        $('ftPageSpent').textContent  = money(pageSpent);
        $('ftPageOrders').textContent = fmtNum(pageOrders, 0);

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('ssPaginationInfo');
        const ul   = $('ssPagination');
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
        const el = document.getElementById('ssDrawer');
        if (!el) return null;
        drawerBs = drawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return drawerBs;
    }

    async function openDrawer(id) {
        const inst = getDrawer();
        if (!inst) return;

        if (!DETAIL_ROUTE) {
            window.toastr?.error('Detail endpoint not configured.');
            return;
        }

        $('ssDrawerSkeleton')?.classList.remove('d-none');
        const content = $('ssDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        $('ssDrawerSubtitle').textContent = '';
        inst.show();

        const params = new URLSearchParams({
            start_date: state.startDate || '',
            end_date:   state.endDate   || '',
        });

        try {
            const res = await fetch(`${DETAIL_ROUTE.replace('__ID__', id)}?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            renderDrawer(json.data);
        } catch (err) {
            console.error('[ss-drawer] load failed:', err);
            $('ssDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderDrawer(d) {
        $('ssDrawerSkeleton')?.classList.add('d-none');
        $('ssDrawerSubtitle').textContent = `${d.supplier.name} · ${d.summary.order_count} orders`;

        const content = $('ssDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        const initial = (d.supplier.name || '?').substring(0, 2).toUpperCase();
        const ordersHtml = (d.orders || []).map(o => `
            <tr>
                <td class="fw-semibold">${esc(o.po_number || '—')}</td>
                <td>${esc(o.created_at || '—')}</td>
                <td><span class="badge badge-light-info">${esc(o.location || '—')}</span></td>
                <td><span class="badge badge-light-${o.status_color}">${esc(o.status_label)}</span></td>
                <td class="text-end fw-bold text-primary">${money(o.total)}</td>
            </tr>
        `).join('');

        content.innerHTML = `
            <div class="d-flex align-items-center mb-4">
                <div class="symbol symbol-60px me-4">
                    <div class="symbol-label bg-light-primary text-primary fw-bold fs-2">${esc(initial)}</div>
                </div>
                <div>
                    <h4 class="fw-bold mb-1">${esc(d.supplier.name)}</h4>
                    <div class="text-muted fs-7">${esc(d.meta.start_date)} → ${esc(d.meta.end_date)}</div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card card-flush bg-light-info"><div class="card-body">
                        <div class="text-muted fs-7">Contact</div>
                        <div class="fw-bold">${esc(d.supplier.contact_person || '—')}</div>
                    </div></div>
                </div>
                <div class="col-md-4">
                    <div class="card card-flush bg-light-success"><div class="card-body">
                        <div class="text-muted fs-7">Email</div>
                        <div class="fw-bold">${esc(d.supplier.email || '—')}</div>
                    </div></div>
                </div>
                <div class="col-md-4">
                    <div class="card card-flush bg-light-warning"><div class="card-body">
                        <div class="text-muted fs-7">Phone</div>
                        <div class="fw-bold">${esc(d.supplier.phone || '—')}</div>
                    </div></div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card card-flush bg-light-secondary"><div class="card-body text-center">
                        <div class="text-muted fs-7">Orders</div>
                        <div class="fs-3 fw-bold">${fmtNum(d.summary.order_count, 0)}</div>
                    </div></div>
                </div>
                <div class="col-md-3">
                    <div class="card card-flush bg-light-primary"><div class="card-body text-center">
                        <div class="text-muted fs-7">Total Spent</div>
                        <div class="fs-3 fw-bold text-primary">${money(d.summary.total_spent)}</div>
                    </div></div>
                </div>
                <div class="col-md-3">
                    <div class="card card-flush bg-light-info"><div class="card-body text-center">
                        <div class="text-muted fs-7">Avg Order</div>
                        <div class="fs-3 fw-bold">${money(d.summary.avg_order_value)}</div>
                    </div></div>
                </div>
                <div class="col-md-3">
                    <div class="card card-flush bg-light-success"><div class="card-body text-center">
                        <div class="text-muted fs-7">Max Order</div>
                        <div class="fs-3 fw-bold text-success">${money(d.summary.max_order_value)}</div>
                    </div></div>
                </div>
            </div>

            <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-2 text-primary"></i>Purchase Orders</h6>
            <div class="table-responsive">
                <table class="table table-row-bordered table-sm align-middle">
                    <thead><tr class="fw-bold fs-8 text-gray-800 bg-light">
                        <th>PO Number</th><th>Order Date</th><th>Location</th>
                        <th>Status</th><th class="text-end">Amount</th>
                    </tr></thead>
                    <tbody>${ordersHtml || '<tr><td colspan="5" class="text-center text-muted py-4">No purchase orders</td></tr>'}</tbody>
                </table>
            </div>
        `;
    }

    function exportCsv() {
        const params = new URLSearchParams();
        if (state.startDate)  params.set('start_date',  state.startDate);
        if (state.endDate)    params.set('end_date',    state.endDate);
        if (state.supplierId) params.set('supplier_id', state.supplierId);
        if (state.period)     params.set('period',      state.period);
        window.location.href = `/reports/purchasing/supplier-spend-analysis?${params.toString()}&export=1`;
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