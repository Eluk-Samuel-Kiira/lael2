<script>
window.POStatus = (function () {
    'use strict';

    const ROUTE        = window.PS_ROUTES.data;
    const DETAIL_ROUTE = window.PS_ROUTES.detail;
    const CURRENCY     = window.REPORT_CURRENCY || '';
    const BOOT         = window.PS_BOOTSTRAP || {};

    const state = {
        startDate:   BOOT.startDate   || '',
        endDate:     BOOT.endDate     || '',
        supplierId:  BOOT.supplierId  || '',
        statusGroup: BOOT.statusGroup || 'all',
        perPage:     Number(BOOT.perPage || 15),
        page:        1,
    };

    let chart = null;
    let drawerBs = null;
    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    const STATUS_COLORS = {
        draft:            '#A1A5B7',
        sent:             '#17a2b8',
        pending_approval: '#FFC700',
        approved:         '#3E97FF',
        partially_received: '#50CD89',
        received:         '#50CD89',
        completed:        '#3699FF',
        cancelled:        '#F1416C',
    };

    document.addEventListener('DOMContentLoaded', () => {
        if (!DETAIL_ROUTE) console.error('[po-status] REPORT_ROUTES.detail is undefined');

        $('psApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('psClearBtn')?.addEventListener('click', resetFilters);
        $('psExportBtn')?.addEventListener('click', exportCsv);

        $('psTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.ps-row[data-id]');
            if (!row) return;
            openDrawer(row.dataset.id);
        });

        document.getElementById('psDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('psDrawerContent').innerHTML = '';
            $('psDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate   = $('psStartDate')?.value    || '';
        state.endDate     = $('psEndDate')?.value      || '';
        state.supplierId  = $('psSupplier')?.value     || '';
        state.statusGroup = $('psStatusGroup')?.value  || 'all';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.supplierId = '';
        state.statusGroup = 'all';
        state.page = 1;
        if ($('psStartDate'))   $('psStartDate').value   = '';
        if ($('psEndDate'))     $('psEndDate').value     = '';
        if ($('psStatusGroup')) $('psStatusGroup').value = 'all';
        if (window.jQuery) jQuery('#psSupplier').val(null).trigger('change.select2');
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)   params.set('start_date',   state.startDate);
        if (state.endDate)     params.set('end_date',     state.endDate);
        if (state.supplierId)  params.set('supplier_id',  state.supplierId);
        if (state.statusGroup) params.set('status_group', state.statusGroup);

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
            console.error('[po-status] load failed:', err);
            window.toastr?.error('Failed to load purchase order status.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('psSkeleton')?.classList.toggle('d-none', !v);
        $('psContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderChart(p.distribution, p.meta);
        renderTable(p.items, p.pagination, p.meta);
    }

    function renderSummary(s) {
        $('sumTotalOrders').textContent = fmtNum(s.total_orders, 0);
        $('sumTotalValue').textContent  = money(s.total_value);
        $('sumPending').textContent     = fmtNum(s.pending_orders, 0);
        $('sumOverdue').textContent     = fmtNum(s.overdue_orders, 0);
        $('sumCompleted').textContent   = fmtNum(s.completed_orders, 0);
        $('sumCompletedValue').textContent = money(s.completed_value);
    }

    function renderChart(distribution, meta) {
        const el = $('psChart');
        const empty = $('psChartEmpty');
        if (!el) return;

        $('psPeriodBadge').textContent = `${meta.start_date} → ${meta.end_date}`;

        if (!distribution || distribution.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (chart) { chart.destroy(); chart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const sorted = distribution.slice().sort((a, b) => b.count - a.count);
        const labels = sorted.map(r => labelStatus(r.status));
        const counts = sorted.map(r => Number(r.count));
        const values = sorted.map(r => Number(r.total_value));
        const colors = sorted.map(r => STATUS_COLORS[r.status] || '#A1A5B7');

        const options = {
            series: [
                { name: 'Orders',      data: counts, type: 'column' },
                { name: 'Total Value', data: values, type: 'line'   },
            ],
            chart: { height: 350, type: 'line', toolbar: { show: true }, fontFamily: 'inherit' },
            stroke: { width: [0, 3] },
            dataLabels: { enabled: true, enabledOnSeries: [0] },
            labels,
            colors,
            xaxis: { type: 'category' },
            yaxis: [
                { title: { text: 'Orders' }, labels: { formatter: v => Number(v).toFixed(0) } },
                { opposite: true, title: { text: 'Value (' + CURRENCY + ')' },
                  labels: { formatter: v => CURRENCY + Number(v).toLocaleString() } },
            ],
            tooltip: {
                shared: true, intersect: false,
                y: { formatter: (v, { seriesIndex }) =>
                    seriesIndex === 0 ? v + ' orders' : money(v) },
            },
            legend: { position: 'top' },
        };

        if (chart) chart.updateOptions(options, false, true);
        else { chart = new ApexCharts(el, options); chart.render(); }
    }

    function renderTable(rows, pagination, meta) {
        const body = $('psTableBody');
        const empty = $('psEmpty');
        const wrap = $('psPaginationWrap');
        const badge = $('psBadge');
        const title = $('psTableTitle');
        if (!body) return;

        // Toggle table title based on the filter
        if (title) {
            title.textContent = meta.status_group === 'pending'
                ? 'Pending Orders'
                : meta.status_group === 'overdue'
                    ? 'Overdue Orders'
                    : 'Purchase Orders';
        }

        if (!rows || rows.length === 0) {
            body.innerHTML = ''; empty?.classList.remove('d-none'); wrap?.classList.add('d-none');
            if (badge) badge.textContent = '';
            return;
        }
        empty?.classList.add('d-none');
        wrap?.classList.remove('d-none');
        if (badge) badge.textContent = `Showing ${pagination.from} of ${pagination.total}`;

        body.innerHTML = rows.map((r, i) => {
            const idx = (pagination.from || 1) + i;
            const overdueBadge = Number(r.days_overdue) > 0
                ? `<span class="badge badge-danger">${r.days_overdue} days</span>`
                : '<span class="text-muted">—</span>';
            const rowClass = Number(r.days_overdue) > 7 ? 'table-danger' : '';

            return `
                <tr class="ps-row ${rowClass}" data-id="${r.id}">
                    <td class="ps-4 fw-bold">${idx}</td>
                    <td class="fw-semibold">${esc(r.po_number)}</td>
                    <td>
                        <div class="fw-bold">${esc(r.supplier_name)}</div>
                        ${r.supplier_contact ? `<small class="text-muted">${esc(r.supplier_contact)}</small>` : ''}
                    </td>
                    <td>${esc(r.location_name)}</td>
                    <td class="text-end fw-bold text-primary">${money(r.total)}</td>
                    <td class="text-center"><span class="badge badge-light-${r.status_color}">${esc(r.status_label)}</span></td>
                    <td>${r.expected_delivery_date ? esc(r.expected_delivery_date) : '<span class="text-muted">Not set</span>'}</td>
                    <td class="text-center">${overdueBadge}</td>
                    <td>${esc(r.created_at)}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-light-primary">
                            <i class="bi bi-eye fs-5"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('psPaginationInfo');
        const ul   = $('psPagination');
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
        const el = document.getElementById('psDrawer');
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

        $('psDrawerSkeleton')?.classList.remove('d-none');
        const content = $('psDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        $('psDrawerSubtitle').textContent = '';
        inst.show();

        try {
            const res = await fetch(DETAIL_ROUTE.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            renderDrawer(json.data);
        } catch (err) {
            console.error('[ps-drawer] load failed:', err);
            $('psDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderDrawer(d) {
        $('psDrawerSkeleton')?.classList.add('d-none');
        $('psDrawerSubtitle').textContent = `${d.po_number} · ${d.created_at || ''}`;

        const content = $('psDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        const itemsHtml = (d.items || []).map((item, i) => {
            const recvCls = item.is_fully_received ? 'success'
                          : item.received_quantity > 0 ? 'warning' : 'secondary';
            return `
                <tr>
                    <td>${i + 1}</td>
                    <td>
                        <div class="fw-bold">${esc(item.product_name)}</div>
                        ${item.variant_name ? `<small class="text-muted">${esc(item.variant_product || '')} · ${esc(item.variant_name)}</small>` : ''}
                    </td>
                    <td><small>${esc(item.sku || '-')}</small></td>
                    <td class="text-end">${fmtNum(item.quantity, 0)}</td>
                    <td class="text-end">${money(item.unit_cost)}</td>
                    <td class="text-end fw-bold">${money(item.total_cost)}</td>
                    <td class="text-center">
                        <span class="badge badge-light-${recvCls}">
                            ${fmtNum(item.received_quantity, 0)} / ${fmtNum(item.quantity, 0)}
                        </span>
                    </td>
                    <td class="text-end">${fmtNum(item.pending_quantity, 0)}</td>
                </tr>
            `;
        }).join('');

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">${esc(d.po_number)}</h4>
                    <div class="text-muted fs-7">${esc(d.supplier.name)} · ${esc(d.location)}</div>
                </div>
                <span class="badge badge-light-${d.status_color} fs-6 py-2 px-3">${esc(d.status_label)}</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <h6 class="fw-bold mb-2"><i class="bi bi-shop me-2 text-primary"></i>Supplier</h6>
                    <div class="border rounded p-3">
                        <div class="fw-bold">${esc(d.supplier.name)}</div>
                        ${d.supplier.contact_person ? `<div class="text-muted fs-7">${esc(d.supplier.contact_person)}</div>` : ''}
                        ${d.supplier.email ? `<div class="text-muted fs-7"><i class="bi bi-envelope me-1"></i>${esc(d.supplier.email)}</div>` : ''}
                        ${d.supplier.phone ? `<div class="text-muted fs-7"><i class="bi bi-telephone me-1"></i>${esc(d.supplier.phone)}</div>` : ''}
                        ${d.supplier.address ? `<div class="text-muted fs-7"><i class="bi bi-geo-alt me-1"></i>${esc(d.supplier.address)}</div>` : ''}
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2 text-primary"></i>Order Info</h6>
                    <table class="table table-sm table-bordered mb-0">
                        <tbody>
                            <tr><th class="w-50">Status</th><td><span class="badge badge-light-${d.status_color}">${esc(d.status_label)}</span></td></tr>
                            <tr><th>Location</th><td>${esc(d.location)}</td></tr>
                            <tr><th>Order date</th><td>${esc(d.created_at || '-')}</td></tr>
                            <tr><th>Expected delivery</th><td>${esc(d.expected_delivery_date || '-')}</td></tr>
                            <tr><th>Created by</th><td>${esc(d.created_by)}</td></tr>
                            <tr><th>Last updated</th><td>${esc(d.updated_at || '-')}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-2 text-primary"></i>Order Items</h6>
            <div class="table-responsive mb-4">
                <table class="table table-row-bordered table-sm align-middle">
                    <thead><tr class="fw-bold fs-8 text-gray-800 bg-light">
                        <th>#</th><th>Product</th><th>SKU</th>
                        <th class="text-end">Ordered</th><th class="text-end">Unit</th>
                        <th class="text-end">Total</th><th class="text-center">Received</th>
                        <th class="text-end">Pending</th>
                    </tr></thead>
                    <tbody>${itemsHtml || '<tr><td colspan="8" class="text-center text-muted py-4">No items</td></tr>'}</tbody>
                </table>
            </div>

            <h6 class="fw-bold mb-3"><i class="bi bi-currency-dollar me-2 text-primary"></i>Totals</h6>
            <table class="table table-sm table-bordered mb-4">
                <tbody>
                    <tr><th class="w-50">Subtotal</th><td class="text-end">${money(d.subtotal)}</td></tr>
                    <tr><th>Tax</th><td class="text-end">${money(d.tax_total)}</td></tr>
                    <tr class="bg-light"><th>Total</th><td class="text-end fw-bold text-primary">${money(d.total)}</td></tr>
                </tbody>
            </table>

            ${d.notes ? `
                <h6 class="fw-bold mb-3"><i class="bi bi-chat-left-text me-2 text-primary"></i>Notes</h6>
                <div class="border rounded p-3 text-gray-800">${esc(d.notes)}</div>
            ` : ''}
        `;
    }

    function labelStatus(s) {
        return (s || '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function exportCsv() {
        const params = new URLSearchParams();
        if (state.startDate)   params.set('start_date',   state.startDate);
        if (state.endDate)     params.set('end_date',     state.endDate);
        if (state.supplierId)  params.set('supplier_id',  state.supplierId);
        if (state.statusGroup) params.set('status_group', state.statusGroup);
        window.location.href = `/reports/purchasing/purchase-order-status?${params.toString()}&export=1`;
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