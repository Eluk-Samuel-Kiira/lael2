<script>
window.ReturnsRefunds = (function () {
    'use strict';

    const DATA_ROUTE    = window.RR_ROUTES.data;
    const ORDER_ROUTE   = window.RR_ROUTES.order;
    const PAYMENT_ROUTE = window.RR_ROUTES.payment;
    const CURRENCY      = window.REPORT_CURRENCY || '';
    const BOOT          = window.RR_BOOTSTRAP || {};
    const I18N          = window.RR_I18N || {};

    const state = {
        startDate:    BOOT.startDate    || '',
        endDate:      BOOT.endDate      || '',
        locationId:   BOOT.locationId   || '',
        departmentId: BOOT.departmentId || '',
        statusFilter: BOOT.statusFilter || 'all',
        perPage:      Number(BOOT.perPage || 25),
        returnsPage:  1,
        refundsPage:  1,
    };

    let reasonsChart  = null;
    let monthlyChart  = null;
    let orderDrawerBs = null;
    let payDrawerBs   = null;
    let inFlight      = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('rrApplyBtn')?.addEventListener('click', () => { readFilters(); state.returnsPage = state.refundsPage = 1; load(); });
        $('rrClearBtn')?.addEventListener('click', resetFilters);
        $('rrExportBtn')?.addEventListener('click', exportExcel);
        $('rrPrintBtn')?.addEventListener('click', () => window.print());

        $('rrReturnOrdersBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.rr-row[data-order-id]');
            if (!row) return;
            openOrderDrawer(row.dataset.orderId, row.dataset.orderNumber);
        });
        $('rrRefundsBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.rr-row[data-payment-id]');
            if (!row) return;
            openPaymentDrawer(row.dataset.paymentId, row.dataset.orderNumber);
        });

        document.getElementById('rrOrderDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('rrOrderDrawerContent').innerHTML = '';
            $('rrOrderDrawerSubtitle').textContent = '';
        });
        document.getElementById('rrPaymentDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('rrPaymentDrawerContent').innerHTML = '';
            $('rrPaymentDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate    = $('rrStartDate')?.value    || '';
        state.endDate      = $('rrEndDate')?.value      || '';
        state.locationId   = $('rrLocation')?.value     || '';
        state.departmentId = $('rrDepartment')?.value   || '';
        state.statusFilter = $('rrStatus')?.value       || 'all';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.departmentId = '';
        state.statusFilter = 'all';
        state.returnsPage = state.refundsPage = 1;
        ['rrStartDate','rrEndDate','rrStatus'].forEach(id => { if ($(id)) $(id).value = ''; });
        if ($('rrStatus')) $('rrStatus').value = 'all';
        if (window.jQuery) {
            jQuery('#rrLocation').val(null).trigger('change.select2');
            jQuery('#rrDepartment').val(null).trigger('change.select2');
        }
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({
            returns_page: state.returnsPage,
            refunds_page: state.refundsPage,
            per_page:     state.perPage,
        });
        if (state.startDate)    params.set('start_date', state.startDate);
        if (state.endDate)      params.set('end_date', state.endDate);
        if (state.locationId)   params.set('location_id', state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);
        if (state.statusFilter) params.set('status_filter', state.statusFilter);

        try {
            const res = await fetch(`${DATA_ROUTE}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            render(json.data);
            showSkeleton(false);
        } catch (err) {
            console.error('[returns-refunds] load failed:', err);
            window.toastr?.error('Failed to load returns report.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('rrSkeleton')?.classList.toggle('d-none', !v);
        $('rrContent')?.classList.toggle('d-none', v);
    }

    function render(d) {
        const hasAny = (d.summary?.total_return_orders ?? 0) > 0 || (d.refund_payments?.length ?? 0) > 0;
        if (!hasAny) {
            $('rrEmpty')?.classList.remove('d-none');
            $('rrSummaryCards').innerHTML = '';
            ['rrReasonsCard','rrReasonsTableCard','rrMonthlyCard','rrByMethodCard','rrTopProductsCard','rrReturnOrdersCard','rrRefundsCard']
                .forEach(id => $(id)?.classList.add('d-none'));
            if (reasonsChart) { reasonsChart.destroy(); reasonsChart = null; }
            if (monthlyChart) { monthlyChart.destroy(); monthlyChart = null; }
            return;
        }
        $('rrEmpty')?.classList.add('d-none');
        renderSummary(d.summary);
        renderReasons(d.reasons);
        renderMonthly(d.monthly_trends);
        renderByMethod(d.by_payment_method);
        renderTopReturned(d.top_returned);
        renderReturnOrders(d.return_orders, d.return_orders_pagination);
        renderRefundPayments(d.refund_payments, d.refund_payments_pagination);
    }

    // ─── Summary ────────────────────────────────────────────
    function renderSummary(s) {
        $('rrSummaryCards').innerHTML = `
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-danger border border-danger border-dashed h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-danger">${fmtNum(s.total_return_orders, 0)}</div>
                    <div class="text-muted">${esc(I18N.totalReturns)}</div>
                    <span class="badge badge-light-danger mt-2">${fmtNum(s.return_rate, 2)}% ${esc(I18N.returnRate)}</span>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-warning border border-warning border-dashed h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-warning">${money(s.total_refund_amount)}</div>
                    <div class="text-muted">${esc(I18N.totalRefundedAmount)}</div>
                    <span class="badge badge-light-warning mt-2">${fmtNum(s.refund_rate, 2)}% ${esc(I18N.refundRate)}</span>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-info border border-info border-dashed h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-info">${money(s.average_return_value)}</div>
                    <div class="text-muted">${esc(I18N.averageReturnValue)}</div>
                    <span class="badge badge-light-info mt-2">${fmtNum(s.total_return_orders, 0)} ${esc(I18N.returns)}</span>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-primary border border-primary border-dashed h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-primary">${fmtNum(s.items_returned, 0)}</div>
                    <div class="text-muted">${esc(I18N.itemsReturned)}</div>
                    <span class="badge badge-light-primary mt-2">${fmtNum(s.unique_products, 0)} ${esc(I18N.uniqueProducts)}</span>
                </div></div>
            </div>
        `;
    }

    // ─── Reasons ────────────────────────────────────────────
    function renderReasons(rows) {
        if (!rows || rows.length === 0) {
            $('rrReasonsCard')?.classList.add('d-none');
            $('rrReasonsTableCard')?.classList.add('d-none');
            if (reasonsChart) { reasonsChart.destroy(); reasonsChart = null; }
            return;
        }
        $('rrReasonsCard')?.classList.remove('d-none');
        $('rrReasonsTableCard')?.classList.remove('d-none');

        const el = $('rrReasonsChart');
        if (el) {
            const labels = rows.map(r => (r.reason || I18N.unspecified || 'Unknown').substring(0, 20));
            const counts = rows.map(r => Number(r.count || 0));
            const colors = ['#F64E60', '#FFA800', '#7239EA', '#3E97FF', '#50CD89', '#A1A5B7'];

            const options = {
                series: counts,
                chart:  { type: 'donut', height: 350, fontFamily: 'inherit', toolbar: { show: false } },
                labels,
                colors,
                plotOptions: { pie: { donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true, label: 'Total', fontSize: '14px',
                            formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0),
                        },
                    },
                }}},
                legend: { position: 'bottom', horizontalAlign: 'center' },
                dataLabels: { enabled: true, formatter: v => v.toFixed(1) + '%' },
                tooltip: { y: { formatter: v => v + ' returns' } },
            };
            if (reasonsChart) reasonsChart.updateOptions(options, false, true);
            else { reasonsChart = new ApexCharts(el, options); reasonsChart.render(); }
        }

        $('rrReasonsBody').innerHTML = rows.map(r => {
            const label = r.reason || I18N.unspecified || 'Unspecified';
            return `
                <tr>
                    <td class="ps-4"><span class="badge badge-light-danger">${esc(truncate(label, 20))}</span></td>
                    <td>${fmtNum(r.count, 0)}</td>
                    <td class="text-end pe-4 text-danger fw-bold">${money(r.total_amount)}</td>
                </tr>
            `;
        }).join('');
    }

    // ─── Monthly trends ────────────────────────────────────
    function renderMonthly(rows) {
        if (!rows || rows.length === 0) {
            $('rrMonthlyCard')?.classList.add('d-none');
            if (monthlyChart) { monthlyChart.destroy(); monthlyChart = null; }
            return;
        }
        $('rrMonthlyCard')?.classList.remove('d-none');

        const el = $('rrMonthlyChart');
        if (!el) return;

        const labels = rows.map(r => r.month);
        const values = rows.map(r => Number(r.return_value || 0));
        const counts = rows.map(r => Number(r.return_count || 0));

        const options = {
            series: [
                { name: I18N.returnValue, type: 'line',   data: values },
                { name: I18N.returnCount, type: 'column', data: counts },
            ],
            chart:  { height: 350, type: 'line', fontFamily: 'inherit', toolbar: { show: true, tools: { download: true } } },
            colors: ['#F64E60', '#3E97FF'],
            stroke: { width: [3, 1], curve: 'smooth' },
            xaxis:  { categories: labels, labels: { rotate: -45 } },
            yaxis: [
                { title: { text: `${I18N.returnValue} (${CURRENCY})` }, labels: { formatter: v => money(v) } },
                { opposite: true, title: { text: I18N.returnCount } },
            ],
            tooltip: { shared: true, intersect: false },
            legend: { position: 'top', horizontalAlign: 'center' },
        };
        if (monthlyChart) monthlyChart.updateOptions(options, false, true);
        else { monthlyChart = new ApexCharts(el, options); monthlyChart.render(); }
    }

    // ─── By payment method ────────────────────────────────
    function renderByMethod(rows) {
        if (!rows || rows.length === 0) { $('rrByMethodCard')?.classList.add('d-none'); return; }
        $('rrByMethodCard')?.classList.remove('d-none');

        const total = rows.reduce((s, r) => s + Number(r.return_value || 0), 0);
        $('rrByMethodBody').innerHTML = rows.map(r => {
            const pct = total > 0 ? (r.return_value / total) * 100 : 0;
            return `
                <tr>
                    <td class="ps-4"><i class="bi bi-credit-card me-2 text-warning"></i>${esc(r.payment_method)}</td>
                    <td><span class="badge badge-light-primary">${fmtNum(r.return_count, 0)}</span></td>
                    <td class="text-end text-danger fw-bold">${money(r.return_value)}</td>
                    <td class="pe-4">
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:6px;">
                                <div class="progress-bar bg-warning" style="width:${pct}%;"></div>
                            </div>
                            <span class="fw-bold min-w-45px text-end">${fmtNum(pct, 1)}%</span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // ─── Top returned products ─────────────────────────────
    function renderTopReturned(rows) {
        if (!rows || rows.length === 0) { $('rrTopProductsCard')?.classList.add('d-none'); return; }
        $('rrTopProductsCard')?.classList.remove('d-none');

        $('rrTopBody').innerHTML = rows.map((p, i) => {
            const avg = p.return_count > 0 ? p.return_value / p.return_count : 0;
            const medal = i < 3 ? `<span class="badge badge-light-${i === 0 ? 'danger' : (i === 1 ? 'warning' : 'info')} ms-1">
                <i class="bi bi-${i === 0 ? 'trophy' : 'award'}"></i>
            </span>` : '';
            return `
                <tr>
                    <td class="ps-4 fw-bold">${i + 1}${medal}</td>
                    <td>${esc(truncate(p.name, 30))}</td>
                    <td><span class="badge badge-light-primary">${esc(p.sku)}</span></td>
                    <td class="text-center fw-bold text-danger">${fmtNum(p.return_quantity, 0)}</td>
                    <td class="text-end text-warning fw-bold">${money(p.return_value)}</td>
                    <td class="text-center"><span class="badge badge-light-info">${fmtNum(p.return_count, 0)}</span></td>
                    <td class="text-end pe-4">${money(avg)}</td>
                </tr>
            `;
        }).join('');
    }

    // ─── Refunded orders table ─────────────────────────────
    function renderReturnOrders(rows, pagination) {
        if (!rows || rows.length === 0) { $('rrReturnOrdersCard')?.classList.add('d-none'); return; }
        $('rrReturnOrdersCard')?.classList.remove('d-none');
        $('rrReturnOrdersBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.returns}`;

        $('rrReturnOrdersBody').innerHTML = rows.map(o => `
            <tr class="rr-row" data-order-id="${o.id}" data-order-number="${esc(o.order_number)}">
                <td class="ps-4"><span class="text-primary fw-bold">#${esc(o.order_number)}</span></td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-35px symbol-circle me-3">
                            <div class="symbol-label bg-light-primary"><span class="text-primary fw-bold">${esc(initials(o.customer?.name))}</span></div>
                        </div>
                        <div>
                            <div class="fw-bold">${esc(o.customer?.name || '—')}</div>
                            ${o.customer?.email ? `<small class="text-muted">${esc(o.customer.email)}</small>` : ''}
                        </div>
                    </div>
                </td>
                <td><span class="badge badge-light-${esc(o.status_color)}">${esc(capitalize(o.status))}</span></td>
                <td class="text-end text-gray-600">${money(o.order_total)}</td>
                <td class="text-end fw-bold text-danger">${money(o.return_amount)}</td>
                <td class="text-center">
                    <span class="badge badge-light-info">${fmtNum(o.refunded_lines, 0)} / ${fmtNum(o.total_lines, 0)}</span>
                </td>
                <td><span class="badge badge-light-warning">${esc(truncate(o.reason || '—', 30))}</span></td>
                <td class="pe-4">${esc(o.created_at || '—')}</td>
            </tr>
        `).join('');

        renderPagination(
            pagination,
            $('rrReturnsPaginationInfo'),
            $('rrReturnsPagination'),
            $('rrReturnsPaginationWrap'),
            (p) => { state.returnsPage = p; load(); }
        );
    }

    // ─── Refund payments table ─────────────────────────────
    function renderRefundPayments(rows, pagination) {
        if (!rows || rows.length === 0) { $('rrRefundsCard')?.classList.add('d-none'); return; }
        $('rrRefundsCard')?.classList.remove('d-none');
        $('rrRefundsBadge').textContent = `${fmtNum(pagination.total, 0)} refunds`;

        const methodColors = {
            credit_card: 'success', cash: 'primary', bank_transfer: 'info',
            check: 'warning', digital_wallet: 'danger',
        };

        $('rrRefundsBody').innerHTML = rows.map(p => {
            const c = methodColors[p.payment_method_type] || 'secondary';
            return `
                <tr class="rr-row" data-payment-id="${p.id}" data-order-number="${esc(p.order_number)}">
                    <td class="ps-4"><span class="text-primary fw-bold">#${esc(p.order_number)}</span></td>
                    <td><span class="badge badge-light-${c}">${esc(p.payment_method)}</span></td>
                    <td class="text-end fw-bold text-danger">${money(p.amount)}</td>
                    <td class="text-end text-gray-600">${money(p.original_amount)}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:6px;">
                                <div class="progress-bar bg-warning" style="width:${Math.min(p.refund_percentage, 100)}%;"></div>
                            </div>
                            <span class="fw-bold min-w-45px text-end">${fmtNum(p.refund_percentage, 1)}%</span>
                        </div>
                    </td>
                    <td class="pe-4">${esc(p.processed_at || '—')}</td>
                </tr>
            `;
        }).join('');

        renderPagination(
            pagination,
            $('rrRefundsPaginationInfo'),
            $('rrRefundsPagination'),
            $('rrRefundsPaginationWrap'),
            (p) => { state.refundsPage = p; load(); }
        );
    }

    // ─── Shared pagination ─────────────────────────────────
    function renderPagination(p, infoEl, ulEl, wrapEl, onPage) {
        if (!ulEl || !infoEl || !p) return;
        if (p.total <= p.per_page) { wrapEl?.classList.add('d-none'); return; }
        wrapEl?.classList.remove('d-none');

        infoEl.textContent = `Showing ${p.from} to ${p.to} of ${p.total}`;
        ulEl.innerHTML = '';

        const add = (page, label, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link'; a.href = '#'; a.textContent = label;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                if (disabled || active) return;
                onPage(page);
            });
            li.appendChild(a); ulEl.appendChild(li);
        };
        add(p.current_page - 1, 'Prev', false, p.current_page <= 1);
        for (let i = Math.max(1, p.current_page - 2); i <= Math.min(p.last_page, p.current_page + 2); i++) {
            add(i, i, i === p.current_page);
        }
        add(p.current_page + 1, 'Next', false, p.current_page >= p.last_page);
    }

    // ─── Drawer 1: Refunded order detail ───────────────────
    function getOrderDrawer() {
        const el = document.getElementById('rrOrderDrawer');
        if (!el) return null;
        orderDrawerBs = orderDrawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return orderDrawerBs;
    }

    async function openOrderDrawer(orderId, orderNumber) {
        const inst = getOrderDrawer();
        if (!inst) return;

        $('rrOrderDrawerSubtitle').textContent = `#${orderNumber}`;
        $('rrOrderDrawerSkeleton')?.classList.remove('d-none');
        const content = $('rrOrderDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        inst.show();

        try {
            const url = ORDER_ROUTE.replace('__ID__', orderId);
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            renderOrderDrawer(json.data);
        } catch (err) {
            console.error('[rr-order-drawer] failed:', err);
            $('rrOrderDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderOrderDrawer(d) {
        $('rrOrderDrawerSkeleton')?.classList.add('d-none');
        const content = $('rrOrderDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        // ─── Items ────────────────────────────────────────────
        const itemRows = (d.items || []).map(i => {
            const badgeCls = i.refund_color || 'secondary';
            const badgeTxt = i.refunded_qty > 0
                ? (i.refundable_qty <= 0.0001
                    ? `${esc(I18N.fullyRefunded)} (${money(i.refunded_value)})`
                    : `${fmtNum(i.refunded_qty, 0)} / ${fmtNum(i.quantity, 0)}`)
                : '—';
            return `
                <tr>
                    <td><span class="fw-bold">${esc(i.name)}</span><br><small class="text-muted">${esc(i.sku)}</small></td>
                    <td class="text-center">${fmtNum(i.quantity, 0)}</td>
                    <td class="text-end">${money(i.unit_price)}</td>
                    <td class="text-center">
                        <span class="badge badge-light-${esc(badgeCls)}">${badgeTxt}</span>
                    </td>
                    <td class="text-end fw-bold">${money(i.total_price)}</td>
                </tr>
            `;
        }).join('');

        // ─── Payments that were (partially) refunded ──────────
        const paymentRows = (d.payments || []).map(p => {
            const partial = p.refunded_amount > 0 && p.refundable_amount > 0;
            const full    = p.refunded_amount > 0 && p.refundable_amount <= 0.0001;
            const cls     = full ? 'dark' : (partial ? 'warning' : 'success');
            return `
                <tr>
                    <td class="text-end fw-bold text-success">${money(p.amount)}</td>
                    <td class="text-end text-danger fw-bold">${money(p.refunded_amount)}</td>
                    <td class="text-end text-info">${money(p.refundable_amount)}</td>
                    <td><span class="badge badge-light-info">${esc(p.method)}</span></td>
                    <td><span class="badge badge-light-${cls}">${esc(capitalize(p.status))}</span></td>
                    <td>${esc(p.processed_at || '—')}</td>
                </tr>
            `;
        }).join('');

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">#${esc(d.order_number)}</h4>
                    <div class="text-muted fs-7">${esc(d.customer?.name || '—')} · ${esc(d.created_at || '')}</div>
                </div>
                <span class="badge badge-light-danger fs-6 py-2 px-3">${esc(capitalize(d.status))}</span>
            </div>

            {{-- Order totals --}}
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3"><div class="card card-flush bg-light-secondary"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.subTotal)}</span>
                    <div class="fs-6 fw-bold">${money(d.subtotal)}</div>
                </div></div></div>
                <div class="col-6 col-md-3"><div class="card card-flush bg-light-warning"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.discount)}</span>
                    <div class="fs-6 fw-bold text-warning">${money(d.discount)}</div>
                </div></div></div>
                <div class="col-6 col-md-3"><div class="card card-flush bg-light-info"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.tax)}</span>
                    <div class="fs-6 fw-bold text-info">${money(d.tax)}</div>
                </div></div></div>
                <div class="col-6 col-md-3"><div class="card card-flush bg-light-danger"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.total)}</span>
                    <div class="fs-6 fw-bold text-danger">${money(d.total)}</div>
                </div></div></div>
            </div>

            {{-- Refund summary — mirrors the live refund UI --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card card-flush bg-light-warning border border-warning border-dashed"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.alreadyRefunded)}</span>
                    <div class="fs-5 fw-bold text-warning">${money(d.refunded_total)}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card card-flush bg-light-info border border-info border-dashed"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.maxRefundable)}</span>
                    <div class="fs-5 fw-bold text-info">${money(d.refundable_cap)}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card card-flush bg-light-primary border border-primary border-dashed"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.itemsFullyRefunded)}</span>
                    <div class="fs-5 fw-bold text-primary">${fmtNum(d.fully_refunded, 0)} / ${fmtNum(d.total_items, 0)}</div>
                </div></div></div>
            </div>

            {{-- Items --}}
            <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-2 text-primary"></i>${esc(I18N.items)}</h6>
            <div class="table-responsive mb-4">
                <table class="table table-sm table-bordered">
                    <thead class="bg-light"><tr>
                        <th>${esc(I18N.product)}</th>
                        <th class="text-center">${esc(I18N.qty)}</th>
                        <th class="text-end">${esc(I18N.unitPrice)}</th>
                        <th class="text-center">${esc(I18N.refundedQty)}</th>
                        <th class="text-end">${esc(I18N.lineTotal)}</th>
                    </tr></thead>
                    <tbody>${itemRows || `<tr><td colspan="5" class="text-center text-muted py-3">${esc(I18N.noData)}</td></tr>`}</tbody>
                </table>
            </div>

            {{-- Payments --}}
            ${(d.payments || []).length > 0 ? `
                <h6 class="fw-bold mb-3"><i class="bi bi-cash-coin me-2 text-warning"></i>${esc(I18N.refundPayments)}</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="bg-light"><tr>
                            <th class="text-end">${esc(I18N.originalAmount)}</th>
                            <th class="text-end">${esc(I18N.refundAmount)}</th>
                            <th class="text-end">${esc(I18N.stillRefundable)}</th>
                            <th>${esc(I18N.paymentMethod)}</th>
                            <th>${esc(I18N.status)}</th>
                            <th>${esc(I18N.processedDate)}</th>
                        </tr></thead>
                        <tbody>${paymentRows}</tbody>
                    </table>
                </div>
            ` : ''}

            ${d.notes ? `
                <h6 class="fw-bold mt-4 mb-3"><i class="bi bi-chat-left-text me-2 text-primary"></i>${esc(I18N.notes)}</h6>
                <div class="border rounded p-3 text-gray-800 rr-notes">${esc(d.notes)}</div>
            ` : ''}
        `;
    }

    // ─── Drawer 2: Refund payment detail ───────────────────
    function getPaymentDrawer() {
        const el = document.getElementById('rrPaymentDrawer');
        if (!el) return null;
        payDrawerBs = payDrawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return payDrawerBs;
    }

    async function openPaymentDrawer(paymentId, orderNumber) {
        const inst = getPaymentDrawer();
        if (!inst) return;

        $('rrPaymentDrawerSubtitle').textContent = `#${orderNumber}`;
        $('rrPaymentDrawerSkeleton')?.classList.remove('d-none');
        const content = $('rrPaymentDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        inst.show();

        try {
            const url = PAYMENT_ROUTE.replace('__ID__', paymentId);
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            renderPaymentDrawer(json.data);
        } catch (err) {
            console.error('[rr-payment-drawer] failed:', err);
            $('rrPaymentDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderPaymentDrawer(d) {
        $('rrPaymentDrawerSkeleton')?.classList.add('d-none');
        const content = $('rrPaymentDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        const trailRows = (d.trail || []).map(t => {
            const statusCls = t.status === 'refunded' ? 'dark'
                            : (t.status === 'completed' ? 'success'
                            : (t.status === 'failed' ? 'danger' : 'warning'));
            return `
                <tr class="${t.id == d.id ? 'table-warning' : ''}">
                    <td class="text-end fw-bold text-success">${money(t.amount)}</td>
                    <td class="text-end text-danger">${money(t.refunded_amount)}</td>
                    <td><span class="badge badge-light-${statusCls}">${esc(capitalize(t.status))}</span></td>
                    <td><span class="badge badge-light-info">${esc(t.method)}</span></td>
                    <td>${esc(t.processed_at || '—')}</td>
                    <td>${esc(t.reference || '—')}</td>
                </tr>
            `;
        }).join('');

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">${money(d.amount)}</h4>
                    <div class="text-muted fs-7">#${esc(d.order?.order_number || '—')} · ${esc(d.processed_at || '')}</div>
                </div>
                <span class="badge badge-light-warning fs-6 py-2 px-3">${esc(capitalize(d.status))}</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card card-flush bg-light-primary"><div class="card-body py-3">
                    <div class="text-muted fs-8 mb-1">${esc(I18N.originalAmount)}</div>
                    <div class="fs-5 fw-bold text-primary">${money(d.amount)}</div>
                    <small class="text-muted">${esc(d.payment_method)}</small>
                </div></div></div>
                <div class="col-md-4"><div class="card card-flush bg-light-danger"><div class="card-body py-3">
                    <div class="text-muted fs-8 mb-1">${esc(I18N.refundAmount)}</div>
                    <div class="fs-5 fw-bold text-danger">${money(d.refunded_amount)}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card card-flush bg-light-success"><div class="card-body py-3">
                    <div class="text-muted fs-8 mb-1">${esc(I18N.stillRefundable)}</div>
                    <div class="fs-5 fw-bold text-success">${money(d.refundable_amount)}</div>
                </div></div></div>
            </div>

            <table class="table table-sm table-bordered mb-4">
                <tbody>
                    <tr><th class="w-50">${esc(I18N.orderTotal)}</th><td>${money(d.order?.total || 0)}</td></tr>
                    <tr><th>${esc(I18N.paymentMethod)}</th><td><span class="badge badge-light-info">${esc(d.payment_method)}</span></td></tr>
                    <tr><th>${esc(I18N.processedDate)}</th><td>${esc(d.processed_at || '—')}</td></tr>
                    <tr><th>${esc(I18N.reference)}</th><td>${esc(d.reference || '—')}</td></tr>
                    <tr><th>${esc(I18N.processor)}</th><td>${esc(d.processed_by || '—')}</td></tr>
                </tbody>
            </table>

            <h6 class="fw-bold mb-3"><i class="bi bi-list-ol me-2 text-primary"></i>${esc(I18N.paymentTrail)}</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="bg-light"><tr>
                        <th class="text-end">${esc(I18N.originalAmount)}</th>
                        <th class="text-end">${esc(I18N.refundAmount)}</th>
                        <th>${esc(I18N.status)}</th>
                        <th>${esc(I18N.paymentMethod)}</th>
                        <th>${esc(I18N.processedDate)}</th>
                        <th>${esc(I18N.reference)}</th>
                    </tr></thead>
                    <tbody>${trailRows || `<tr><td colspan="6" class="text-center text-muted py-3">${esc(I18N.noData)}</td></tr>`}</tbody>
                </table>
            </div>

            ${d.notes ? `
                <h6 class="fw-bold mt-4 mb-3"><i class="bi bi-chat-left-text me-2 text-primary"></i>${esc(I18N.notes)}</h6>
                <div class="border rounded p-3 text-gray-800 rr-notes">${esc(d.notes)}</div>
            ` : ''}
        `;
    }

    // ─── Export ─────────────────────────────────────────────
    function exportExcel() {
        const table = $('rrTopTable');
        if (!table || typeof XLSX === 'undefined') {
            window.toastr?.error('Excel export unavailable.');
            return;
        }
        try {
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, 'Returns');
            XLSX.writeFile(wb, 'returns_refunds.xlsx');
        } catch (e) {
            window.toastr?.error('Export failed: ' + e.message);
        }
    }

    // ─── Helpers ────────────────────────────────────────────
    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t); return d.innerHTML;
    }
    function capitalize(s) { return String(s || '').charAt(0).toUpperCase() + String(s || '').slice(1); }
    function truncate(s, len) { return !s ? '' : (s.length > len ? s.substring(0, len) + '…' : s); }
    function initials(name) {
        if (!name) return '?';
        return String(name).trim().split(/\s+/).slice(0, 2).map(p => p[0]).join('').toUpperCase();
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