<script>
window.PurchaseOrderItems = (function () {
    'use strict';

    const ROUTE        = window.POI_ROUTES.data;
    const DETAIL_ROUTE = window.POI_ROUTES.detail;
    const CURRENCY     = window.REPORT_CURRENCY || '';
    const BOOT         = window.POI_BOOTSTRAP || {};

    const state = {
        startDate:        BOOT.startDate        || '',
        endDate:          BOOT.endDate          || '',
        supplierId:       BOOT.supplierId       || '',
        productVariantId: BOOT.productVariantId || '',
        perPage:          Number(BOOT.perPage || 15),
        page:             1,
    };

    let topChart = null;
    let drawerBs = null;
    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        if (!DETAIL_ROUTE) console.error('[poi] REPORT_ROUTES.detail is undefined');

        $('poiApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('poiClearBtn')?.addEventListener('click', resetFilters);
        $('poiExportBtn')?.addEventListener('click', exportCsv);

        $('poiTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.poi-row[data-id]');
            if (!row) return;
            openDrawer(row.dataset.id);
        });

        document.getElementById('poiDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('poiDrawerContent').innerHTML = '';
            $('poiDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate        = $('poiStartDate')?.value || '';
        state.endDate          = $('poiEndDate')?.value   || '';
        state.supplierId       = $('poiSupplier')?.value  || '';
        state.productVariantId = $('poiVariant')?.value   || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.supplierId = state.productVariantId = '';
        state.page = 1;
        if ($('poiStartDate')) $('poiStartDate').value = '';
        if ($('poiEndDate'))   $('poiEndDate').value   = '';
        if (window.jQuery) jQuery('#poiSupplier, #poiVariant').val(null).trigger('change.select2');
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)        params.set('start_date',  state.startDate);
        if (state.endDate)          params.set('end_date',    state.endDate);
        if (state.supplierId)       params.set('supplier_id', state.supplierId);
        if (state.productVariantId) params.set('variant_id',  state.productVariantId);

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
            console.error('[poi] load failed:', err);
            window.toastr?.error('Failed to load purchase order items.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('poiSkeleton')?.classList.toggle('d-none', !v);
        $('poiContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderTopChart(p.top_items);
        renderTable(p.items, p.pagination);
    }

    function renderSummary(s) {
        $('sumTotalItems').textContent      = fmtNum(s.total_items, 0);
        $('sumTotalQty').textContent        = fmtNum(s.total_quantity, 0);
        $('sumTotalValue').textContent      = money(s.total_value);
        $('sumAvgCost').textContent         = money(s.avg_unit_cost);
        $('sumUniqueProducts').textContent  = fmtNum(s.unique_products, 0);
        $('sumUniqueSuppliers').textContent = fmtNum(s.unique_suppliers, 0);
    }

    function renderTopChart(rows) {
        const card = $('poiTopCard');
        const el = $('poiTopChart');
        const empty = $('poiTopEmpty');
        if (!el) return;

        if (!rows || rows.length === 0) {
            card?.classList.add('d-none');
            if (topChart) { topChart.destroy(); topChart = null; }
            return;
        }
        card?.classList.remove('d-none');
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [
                { name: 'Quantity',    type: 'column', data: rows.map(r => Number(r.quantity)) },
                { name: 'Total Value', type: 'line',   data: rows.map(r => Number(r.total_value)) },
            ],
            chart: { height: 400, type: 'line', toolbar: { show: true }, fontFamily: 'inherit' },
            stroke: { width: [0, 3] },
            dataLabels: {
                enabled: true,
                enabledOnSeries: [1],
                formatter: v => CURRENCY + Number(v).toLocaleString(),
                style: { fontSize: '10px' },
            },
            colors: ['#3E97FF', '#F1416C'],
            xaxis: {
                categories: rows.map(r => r.label),
                labels: { rotate: -30, style: { fontSize: '11px' }, trim: true },
            },
            yaxis: [
                { title: { text: 'Quantity' }, labels: { formatter: v => Number(v).toLocaleString() } },
                { opposite: true, title: { text: 'Value (' + CURRENCY + ')' },
                  labels: { formatter: v => CURRENCY + Number(v).toLocaleString() } },
            ],
            tooltip: {
                shared: true, intersect: false,
                y: { formatter: (v, { seriesIndex }) =>
                    seriesIndex === 0 ? Number(v).toLocaleString() + ' units' : money(v) },
            },
            legend: { position: 'top', horizontalAlign: 'center' },
            plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
        };

        if (topChart) topChart.updateOptions(options, false, true);
        else { topChart = new ApexCharts(el, options); topChart.render(); }
    }

    function renderTable(rows, pagination) {
        const body = $('poiTableBody');
        const empty = $('poiEmpty');
        const wrap = $('poiPaginationWrap');
        const badge = $('poiBadge');
        if (!body) return;

        if (!rows || rows.length === 0) {
            body.innerHTML = ''; empty?.classList.remove('d-none'); wrap?.classList.add('d-none');
            if (badge) badge.textContent = '';
            return;
        }
        empty?.classList.add('d-none');
        wrap?.classList.remove('d-none');
        if (badge) badge.textContent = `Showing ${pagination.from} of ${pagination.total}`;

        body.innerHTML = rows.map((r, i) => {
            const idx      = (pagination.from || 1) + i;
            const recvPct  = Number(r.received_pct);
            const recvCls  = recvPct >= 100 ? 'success' : (recvPct > 0 ? 'warning' : 'danger');
            const balCls   = Number(r.balance_quantity) === 0 ? 'success'
                           : (Number(r.balance_quantity) > 0 ? 'warning' : 'danger');
            const initials = (r.product_name || '?').substring(0, 2).toUpperCase();

            return `
                <tr class="poi-row" data-id="${r.id}">
                    <td class="ps-4 fw-bold">${idx}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-35px me-2">
                                <div class="symbol-label bg-light-primary text-primary fw-bold">PO</div>
                            </div>
                            <div>
                                <div class="fw-bold">${esc(r.po_number || '—')}</div>
                                ${r.po_status ? `<small class="text-muted">${esc(r.po_status)}</small>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-35px me-2">
                                <div class="symbol-label bg-light-info text-info fw-bold">${esc(initials)}</div>
                            </div>
                            <div class="overflow-hidden">
                                <div class="fw-bold text-truncate" style="max-width:180px;" title="${esc(r.product_name)}">
                                    ${esc(r.product_name || '—')}
                                </div>
                                <small class="text-muted">${esc(r.sku || '')}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="fw-bold text-truncate" style="max-width:150px;">${esc(r.supplier_name || '—')}</div>
                        ${r.supplier_contact ? `<small class="text-muted text-truncate d-block" style="max-width:150px;">${esc(r.supplier_contact)}</small>` : ''}
                    </td>
                    <td class="text-end">
                        <span class="fw-bold">${fmtNum(r.quantity, 0)}</span>
                        <div class="text-muted fs-8">units</div>
                    </td>
                    <td class="text-end">
                        <span class="fw-bold text-primary">${money(r.unit_cost)}</span>
                        <div class="text-muted fs-8">per unit</div>
                    </td>
                    <td class="text-end">
                        <span class="fw-bold text-success">${money(r.total_cost)}</span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center flex-wrap">
                            <span class="fw-bold me-2 text-${recvCls}">${fmtNum(r.received_quantity, 0)}</span>
                            <div class="progress w-100px" style="height:6px;">
                                <div class="progress-bar bg-${recvCls}" style="width:${Math.min(100, recvPct)}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-${balCls}">${fmtNum(r.balance_quantity, 0)}</span>
                    </td>
                    <td>
                        <span class="text-muted text-nowrap">${esc(r.order_date || '—')}</span>
                        <div class="text-muted fs-8 text-nowrap">${esc(r.order_time || '')}</div>
                    </td>
                    <td class="text-end pe-4">
                        <span class="badge badge-light-${r.status_color}">${esc(r.status_text)}</span>
                    </td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('poiPaginationInfo');
        const ul   = $('poiPagination');
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
        const el = document.getElementById('poiDrawer');
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

        $('poiDrawerSkeleton')?.classList.remove('d-none');
        const content = $('poiDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        $('poiDrawerSubtitle').textContent = '';
        inst.show();

        try {
            const res = await fetch(DETAIL_ROUTE.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            renderDrawer(json.data);
        } catch (err) {
            console.error('[poi-drawer] load failed:', err);
            $('poiDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderDrawer(d) {
        $('poiDrawerSkeleton')?.classList.add('d-none');
        $('poiDrawerSubtitle').textContent = `${d.po?.po_number || '—'} · ${d.product_name || ''}`;

        const content = $('poiDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        const ordered  = Number(d.quantity);
        const received = Number(d.received_quantity);
        const balance  = Number(d.balance_quantity);
        const pct      = ordered > 0 ? Math.round((received / ordered) * 100) : 0;
        const recvCls  = pct >= 100 ? 'success' : (pct > 0 ? 'warning' : 'danger');
        const balCls   = balance === 0 ? 'success' : (balance > 0 ? 'warning' : 'danger');

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">${esc(d.product_name || '—')}</h4>
                    <div class="text-muted fs-7">
                        ${esc(d.sku || '')} · ${esc(d.variant_product || '')}
                    </div>
                </div>
                ${d.po ? `<span class="badge badge-light-${d.po.status_color} fs-6 py-2 px-3">${esc(d.po.status_label)}</span>` : ''}
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-secondary border border-secondary border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">Ordered</span>
                            <div class="fs-3 fw-bold">${fmtNum(ordered, 0)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-${recvCls} border border-${recvCls} border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">Received</span>
                            <div class="fs-3 fw-bold text-${recvCls}">${fmtNum(received, 0)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-${balCls} border border-${balCls} border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">Balance</span>
                            <div class="fs-3 fw-bold text-${balCls}">${fmtNum(balance, 0)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-primary border border-primary border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">Progress</span>
                            <div class="fs-3 fw-bold text-primary">${pct}%</div>
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold mb-3"><i class="bi bi-currency-dollar me-2 text-primary"></i>Pricing</h6>
            <table class="table table-sm table-bordered mb-4">
                <tbody>
                    <tr><th class="w-50">Unit cost</th><td class="text-end">${money(d.unit_cost)}</td></tr>
                    <tr class="bg-light"><th>Total cost</th><td class="text-end fw-bold text-success">${money(d.total_cost)}</td></tr>
                </tbody>
            </table>

            ${d.po ? `
                <h6 class="fw-bold mb-3"><i class="bi bi-clipboard me-2 text-primary"></i>Purchase Order</h6>
                <table class="table table-sm table-bordered mb-4">
                    <tbody>
                        <tr><th class="w-50">PO number</th><td>${esc(d.po.po_number || '—')}</td></tr>
                        <tr><th>Status</th><td><span class="badge badge-light-${d.po.status_color}">${esc(d.po.status_label)}</span></td></tr>
                        <tr><th>Location</th><td>${esc(d.po.location_name)}</td></tr>
                        <tr><th>Created at</th><td>${esc(d.po.created_at || '—')}</td></tr>
                        <tr><th>Created by</th><td>${esc(d.po.created_by)}</td></tr>
                    </tbody>
                </table>

                <h6 class="fw-bold mb-3"><i class="bi bi-shop me-2 text-primary"></i>Supplier</h6>
                <div class="border rounded p-3 mb-4">
                    <div class="fw-bold">${esc(d.po.supplier.name || '—')}</div>
                    ${d.po.supplier.contact_person ? `<div class="text-muted fs-7">${esc(d.po.supplier.contact_person)}</div>` : ''}
                    ${d.po.supplier.email ? `<div class="text-muted fs-7"><i class="bi bi-envelope me-1"></i>${esc(d.po.supplier.email)}</div>` : ''}
                    ${d.po.supplier.phone ? `<div class="text-muted fs-7"><i class="bi bi-telephone me-1"></i>${esc(d.po.supplier.phone)}</div>` : ''}
                </div>
            ` : ''}

            ${d.payment_status || d.payment_method || d.payment_date ? `
                <h6 class="fw-bold mb-3"><i class="bi bi-credit-card me-2 text-primary"></i>Payment</h6>
                <table class="table table-sm table-bordered mb-4">
                    <tbody>
                        ${d.payment_status ? `<tr><th class="w-50">Status</th><td>${esc(d.payment_status)}</td></tr>` : ''}
                        ${d.payment_method ? `<tr><th>Method</th><td>${esc(d.payment_method)}</td></tr>` : ''}
                        ${d.payment_date ? `<tr><th>Date</th><td>${esc(d.payment_date)}</td></tr>` : ''}
                    </tbody>
                </table>
            ` : ''}

            ${d.notes ? `
                <h6 class="fw-bold mb-3"><i class="bi bi-chat-left-text me-2 text-primary"></i>Notes</h6>
                <div class="border rounded p-3 mb-4 text-gray-800">${esc(d.notes)}</div>
            ` : ''}
        `;
    }

    function exportCsv() {
        const params = new URLSearchParams();
        if (state.startDate)        params.set('start_date',  state.startDate);
        if (state.endDate)          params.set('end_date',    state.endDate);
        if (state.supplierId)       params.set('supplier_id', state.supplierId);
        if (state.productVariantId) params.set('variant_id',  state.productVariantId);
        window.location.href = `/reports/purchasing/purchase-order-items?${params.toString()}&export=1`;
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