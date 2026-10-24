<script>
window.ProfitAnalysis = (function () {
    'use strict';

    const ROUTE    = window.PA_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.PA_BOOTSTRAP || {};
    const I18N     = window.PA_I18N || {};

    const state = {
        startDate:    BOOT.startDate    || '',
        endDate:      BOOT.endDate      || '',
        locationId:   BOOT.locationId   || '',
        departmentId: BOOT.departmentId || '',
        orderType:    BOOT.orderType    || '',
        minProfit:    BOOT.minProfit    || '',
        maxProfit:    BOOT.maxProfit    || '',
        perPage:      Number(BOOT.perPage || 25),
        page:         1,
    };

    let inFlight = false;
    let drawerBs = null;
    let lastPayload = null;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('paApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('paClearBtn')?.addEventListener('click', resetFilters);
        $('paExportBtn')?.addEventListener('click', exportExcel);
        $('paPrintBtn')?.addEventListener('click', () => window.print());

        // Row → drawer
        $('paOrdersBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.pa-row[data-order-id]');
            if (!row) return;
            openDrawer(row.dataset.orderId);
        });

        document.getElementById('paDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('paDrawerBody').innerHTML = '';
            $('paDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate    = $('paStartDate')?.value    || '';
        state.endDate      = $('paEndDate')?.value      || '';
        state.locationId   = $('paLocation')?.value     || '';
        state.departmentId = $('paDepartment')?.value   || '';
        state.orderType    = $('paType')?.value         || '';
        state.minProfit    = $('paMinProfit')?.value    || '';
        state.maxProfit    = $('paMaxProfit')?.value    || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.departmentId = '';
        state.orderType = state.minProfit = state.maxProfit = '';
        state.page = 1;
        ['paStartDate','paEndDate','paType','paMinProfit','paMaxProfit'].forEach(id => {
            if ($(id)) $(id).value = '';
        });
        if (window.jQuery) {
            jQuery('#paLocation').val(null).trigger('change.select2');
            jQuery('#paDepartment').val(null).trigger('change.select2');
        }
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)    params.set('start_date', state.startDate);
        if (state.endDate)      params.set('end_date', state.endDate);
        if (state.locationId)   params.set('location_id', state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);
        if (state.orderType)    params.set('order_type', state.orderType);
        if (state.minProfit !== '') params.set('min_profit', state.minProfit);
        if (state.maxProfit !== '') params.set('max_profit', state.maxProfit);

        try {
            const res = await fetch(`${ROUTE}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            lastPayload = json.data;
            render(json.data);
            showSkeleton(false);
        } catch (err) {
            console.error('[profit-analysis] load failed:', err);
            window.toastr?.error('Failed to load profit analysis.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('paSkeleton')?.classList.toggle('d-none', !v);
        $('paContent')?.classList.toggle('d-none', v);
    }

    function render(d) {
        if (!d || !d.summary || d.summary.total_orders === 0) {
            $('paEmpty')?.classList.remove('d-none');
            ['paKpiCards','paSummaryStats'].forEach(id => $(id) && ($(id).innerHTML = ''));
            ['paLocationCard','paTopCard','paWorstCard','paTrendCard','paOrdersCard'].forEach(id => $(id)?.classList.add('d-none'));
            return;
        }
        $('paEmpty')?.classList.add('d-none');
        renderKpis(d.summary);
        renderSummaryStats(d.summary, d.profitability_rate);
        renderByLocation(d.by_location);
        renderTopWorst(d.top_products, d.worst_products);
        renderDailyTrend(d.daily_trend, d.daily_pagination, d.daily_totals);
        renderOrders(d.orders, d.orders_pagination, d.orders_totals);
    }

    // ─── KPI cards ───────────────────────────────────────────
    function renderKpis(s) {
        $('paKpiCards').innerHTML = `
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-primary h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-primary">
                            <i class="bi bi-basket fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-2 fw-bold">${fmtNum(s.total_orders, 0)}</div>
                            <div class="text-muted fs-6">${esc(I18N.totalOrders)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">
                        <span class="badge badge-light-success me-1">${fmtNum(s.profitable_orders, 0)}</span> ${esc(I18N.profitable)}
                        <span class="badge badge-light-danger ms-2 me-1">${fmtNum(s.loss_making_orders, 0)}</span> ${esc(I18N.loss)}
                        <span class="badge badge-light-secondary ms-2 me-1">${fmtNum(s.break_even_orders, 0)}</span> ${esc(I18N.breakEven)}
                    </div>
                </div></div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-success h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-success">
                            <i class="bi bi-currency-dollar fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-4 fw-bold">${money(s.total_revenue)}</div>
                            <div class="text-muted fs-6">${esc(I18N.totalRevenue)}</div>
                            <div class="fs-4 fw-bold text-danger mt-1">${money(s.total_cost)}</div>
                            <div class="text-muted fs-6">${esc(I18N.totalCost)}</div>
                        </div>
                    </div>
                </div></div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-warning h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-warning">
                            <i class="bi bi-calculator fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-4 fw-bold text-success">${money(s.total_gross_profit)}</div>
                            <div class="text-muted fs-6">${esc(I18N.grossProfit)}</div>
                            <div class="fs-4 fw-bold text-primary mt-1">${money(s.total_net_profit)}</div>
                            <div class="text-muted fs-6">${esc(I18N.netProfit)}</div>
                        </div>
                    </div>
                </div></div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-secondary h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-secondary">
                            <i class="bi bi-graph-up fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-4 fw-bold text-success">${fmtNum(s.average_profit_margin, 1)}%</div>
                            <div class="text-muted fs-6">${esc(I18N.avgProfitMargin)}</div>
                            <div class="fs-4 fw-bold text-primary mt-1">${money(s.average_net_profit)}</div>
                            <div class="text-muted fs-6">${esc(I18N.avgNetProfit)}</div>
                        </div>
                    </div>
                </div></div>
            </div>
        `;
    }

    // ─── Summary stats row ───────────────────────────────────
    function renderSummaryStats(s, profitabilityRate) {
        $('paSummaryStats').innerHTML = `
            <div class="col-md-3"><div class="card bg-light-success h-100"><div class="card-body text-center">
                <div class="fs-6 text-muted">${esc(I18N.highestProfitOrder)}</div>
                <div class="fs-3 fw-bold text-success">${esc(s.max_profit_order)}</div>
                <div class="fs-5 text-success">${money(s.max_profit_amount)}</div>
            </div></div></div>
            <div class="col-md-3"><div class="card bg-light-info h-100"><div class="card-body text-center">
                <div class="fs-6 text-muted">${esc(I18N.totalDiscount)}</div>
                <div class="fs-3 fw-bold text-info">${money(s.total_discount)}</div>
                <div class="fs-6 text-muted">${esc(I18N.discountReducedProfit)}</div>
            </div></div></div>
            <div class="col-md-3"><div class="card bg-light-danger h-100"><div class="card-body text-center">
                <div class="fs-6 text-muted">${esc(I18N.lossMakingOrders)}</div>
                <div class="fs-3 fw-bold text-danger">${fmtNum(s.loss_making_orders, 0)}</div>
                <div class="fs-6 text-muted">${esc(I18N.ordersWithNegativeProfit)}</div>
            </div></div></div>
            <div class="col-md-3"><div class="card bg-light-primary h-100"><div class="card-body text-center">
                <div class="fs-6 text-muted">${esc(I18N.profitabilityRate)}</div>
                <div class="fs-3 fw-bold text-primary">${fmtNum(profitabilityRate, 1)}%</div>
                <div class="fs-6 text-muted">${fmtNum(s.profitable_orders, 0)} / ${fmtNum(s.total_orders, 0)} ${esc(I18N.orders)}</div>
            </div></div></div>
        `;
    }

    // ─── By location ─────────────────────────────────────────
    function renderByLocation(rows) {
        if (!rows || rows.length === 0) {
            $('paLocationCard')?.classList.add('d-none');
            return;
        }
        $('paLocationCard')?.classList.remove('d-none');
        $('paLocationBody').innerHTML = rows.map(r => `
            <tr>
                <td class="ps-4"><span class="fw-bold">${esc(r.location)}</span></td>
                <td class="text-center">${fmtNum(r.order_count, 0)}</td>
                <td class="text-end">${money(r.total_revenue)}</td>
                <td class="text-end">${money(r.total_cost)}</td>
                <td class="text-end fw-bold ${r.total_net_profit >= 0 ? 'text-success' : 'text-danger'}">${money(r.total_net_profit)}</td>
                <td class="text-end pe-4">
                    <span class="badge badge-light-${marginColor(r.average_margin)}">${fmtNum(r.average_margin, 1)}%</span>
                </td>
            </tr>
        `).join('');
    }

    // ─── Top / worst products ────────────────────────────────
    function renderTopWorst(top, worst) {
        if (top && top.length) {
            $('paTopCard')?.classList.remove('d-none');
            $('paTopBody').innerHTML = top.map(p => `
                <tr>
                    <td class="ps-4">
                        <span class="fw-bold">${esc(p.variant_name)}</span>
                        <br><small class="text-muted">${esc(p.sku)}</small>
                    </td>
                    <td class="text-center">${fmtNum(p.quantity_sold, 0)}</td>
                    <td class="text-end text-success">${money(p.total_profit)}</td>
                    <td class="text-end pe-4"><span class="badge badge-light-success">${fmtNum(p.profit_margin, 1)}%</span></td>
                </tr>
            `).join('');
        } else {
            $('paTopCard')?.classList.add('d-none');
        }

        if (worst && worst.length) {
            $('paWorstCard')?.classList.remove('d-none');
            $('paWorstBody').innerHTML = worst.map(p => `
                <tr>
                    <td class="ps-4">
                        <span class="fw-bold">${esc(p.variant_name)}</span>
                        <br><small class="text-muted">${esc(p.sku)}</small>
                    </td>
                    <td class="text-center">${fmtNum(p.quantity_sold, 0)}</td>
                    <td class="text-end ${p.total_profit >= 0 ? 'text-success' : 'text-danger'}">${money(p.total_profit)}</td>
                    <td class="text-end pe-4">
                        <span class="badge badge-light-${marginColor(p.profit_margin)}">${fmtNum(p.profit_margin, 1)}%</span>
                    </td>
                </tr>
            `).join('');
        } else {
            $('paWorstCard')?.classList.add('d-none');
        }
    }

    // ─── Daily trend ─────────────────────────────────────────
    function renderDailyTrend(rows, pagination, totals) {
        if (!rows || rows.length === 0) {
            $('paTrendCard')?.classList.add('d-none');
            return;
        }
        $('paTrendCard')?.classList.remove('d-none');
        $('paTrendBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.days || 'days'}`;

        $('paTrendBody').innerHTML = rows.map(d => `
            <tr>
                <td class="ps-4"><span class="fw-bold">${esc(formatDate(d.date))}</span></td>
                <td class="text-center">${fmtNum(d.order_count, 0)}</td>
                <td class="text-end">${money(d.total_revenue)}</td>
                <td class="text-end">${money(d.total_cost)}</td>
                <td class="text-end fw-bold ${d.total_net_profit >= 0 ? 'text-success' : 'text-danger'}">${money(d.total_net_profit)}</td>
                <td class="text-end pe-4">
                    <span class="badge badge-light-${marginColor(d.average_margin)}">${fmtNum(d.average_margin, 1)}%</span>
                </td>
            </tr>
        `).join('');

        $('paTrendFoot').innerHTML = `
            <tr>
                <th class="ps-4">${esc(I18N.totalAverage)}</th>
                <th class="text-center fw-bold">${fmtNum(totals.order_count, 0)}</th>
                <th class="text-end fw-bold">${money(totals.total_revenue)}</th>
                <th class="text-end fw-bold">${money(totals.total_cost)}</th>
                <th class="text-end fw-bold">${money(totals.total_net_profit)}</th>
                <th class="text-end pe-4">${fmtNum(totals.average_margin, 1)}%</th>
            </tr>
        `;

        renderPagination(
            pagination,
            $('paTrendPaginationInfo'),
            $('paTrendPagination'),
            $('paTrendPaginationWrap'),
            (p) => { state.page = p; load(); }
        );
    }

    // ─── Orders table ────────────────────────────────────────
    function renderOrders(rows, pagination, totals) {
        if (!rows || rows.length === 0) {
            $('paOrdersCard')?.classList.add('d-none');
            return;
        }
        $('paOrdersCard')?.classList.remove('d-none');
        $('paOrdersBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.orders}`;

        $('paOrdersBody').innerHTML = rows.map(o => `
            <tr class="pa-row" data-order-id="${o.order_id}">
                <td class="ps-4">
                    <span class="fw-bold">${esc(o.order_number)}</span>
                    <br><small class="text-muted">${esc(capitalize(o.order_type))}</small>
                </td>
                <td>
                    <span class="text-muted">${esc(o.created_at)}</span>
                    <br><small class="text-muted">${esc(o.created_time)}</small>
                </td>
                <td>${esc(o.location)}</td>
                <td>${esc(o.department)}</td>
                <td class="text-end">${money(o.total_revenue)}</td>
                <td class="text-end">${money(o.total_cost)}</td>
                <td class="text-end text-danger">${money(o.total_discount)}</td>
                <td class="text-end ${o.gross_profit >= 0 ? 'text-success' : 'text-danger'}">${money(o.gross_profit)}</td>
                <td class="text-end fw-bold ${o.net_profit >= 0 ? 'text-success' : 'text-danger'}">${money(o.net_profit)}</td>
                <td class="text-end">
                    <span class="badge badge-light-${marginColor(o.profit_margin)}">${fmtNum(o.profit_margin, 1)}%</span>
                </td>
                <td class="text-end pe-4">
                    <span class="badge badge-light-primary">
                        <i class="bi bi-eye me-1"></i>${fmtNum(o.item_count, 0)} ${esc(I18N.items)}
                    </span>
                </td>
            </tr>
        `).join('');

        $('paOrdersFoot').innerHTML = `
            <tr>
                <th colspan="4" class="ps-4">${esc(I18N.totalAverage)}</th>
                <th class="text-end fw-bold">${money(totals.total_revenue)}</th>
                <th class="text-end fw-bold">${money(totals.total_cost)}</th>
                <th class="text-end fw-bold">${money(totals.total_discount)}</th>
                <th class="text-end fw-bold">${money(totals.gross_profit)}</th>
                <th class="text-end fw-bold">${money(totals.net_profit)}</th>
                <th class="text-end fw-bold">${fmtNum(totals.profit_margin, 1)}%</th>
                <th></th>
            </tr>
        `;

        renderPagination(
            pagination,
            $('paOrdersPaginationInfo'),
            $('paOrdersPagination'),
            $('paOrdersPaginationWrap'),
            (p) => { state.page = p; load(); }
        );
    }

    // ─── Drawer ──────────────────────────────────────────────
    function getDrawer() {
        const el = document.getElementById('paDrawer');
        if (!el) return null;
        drawerBs = drawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return drawerBs;
    }

    function openDrawer(orderId) {
        if (!lastPayload) return;
        const order = lastPayload.orders.find(o => String(o.order_id) === String(orderId));
        if (!order) return;

        $('paDrawerSubtitle').textContent = `${order.order_number} · ${order.customer}`;
        $('paDrawerBody').innerHTML = renderDrawerContent(order);

        getDrawer()?.show();
    }

    function renderDrawerContent(o) {
        const itemRows = o.item_profits.map(it => `
            <tr>
                <td>
                    <span class="fw-bold">${esc(it.variant_name)}</span>
                    <br><small class="text-muted">${esc(it.sku)}</small>
                </td>
                <td class="text-center">${fmtNum(it.quantity, 0)}</td>
                <td class="text-end">${money(it.unit_cost)}</td>
                <td class="text-end">${money(it.unit_price)}</td>
                <td class="text-end">${money(it.total_cost)}</td>
                <td class="text-end">${money(it.total_revenue)}</td>
                <td class="text-end ${it.profit >= 0 ? 'text-success' : 'text-danger'} fw-bold">${money(it.profit)}</td>
                <td class="text-end">
                    <span class="badge badge-light-${marginColor(it.profit_margin)}">${fmtNum(it.profit_margin, 1)}%</span>
                </td>
                <td class="text-end">
                    <span class="badge badge-light-secondary">${esc(it.pricing_source || '—')}</span>
                </td>
            </tr>
        `).join('');

        return `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">${esc(o.order_number)}</h4>
                    <div class="text-muted fs-7">${esc(o.customer)} · ${esc(o.location)} · ${esc(o.department)}</div>
                </div>
                <span class="badge badge-light-${marginColor(o.profit_margin)} fs-6 py-2 px-3">${fmtNum(o.profit_margin, 1)}%</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-primary border border-primary border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">${esc(I18N.totalRevenue)}</span>
                            <div class="fs-4 fw-bold">${money(o.total_revenue)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-danger border border-danger border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">${esc(I18N.totalCost)}</span>
                            <div class="fs-4 fw-bold text-danger">${money(o.total_cost)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-warning border border-warning border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">${esc(I18N.totalDiscount)}</span>
                            <div class="fs-4 fw-bold text-warning">${money(o.total_discount)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-${o.net_profit >= 0 ? 'success' : 'danger'} border border-${o.net_profit >= 0 ? 'success' : 'danger'} border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">${esc(I18N.netProfit)}</span>
                            <div class="fs-4 fw-bold text-${o.net_profit >= 0 ? 'success' : 'danger'}">${money(o.net_profit)}</div>
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-2 text-primary"></i>${esc(I18N.itemBreakdown)}</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>${esc(I18N.product)}</th>
                            <th class="text-center">${esc(I18N.qty)}</th>
                            <th class="text-end">${esc(I18N.unitCost)}</th>
                            <th class="text-end">${esc(I18N.unitPrice)}</th>
                            <th class="text-end">${esc(I18N.cost)}</th>
                            <th class="text-end">${esc(I18N.revenue)}</th>
                            <th class="text-end">${esc(I18N.profit)}</th>
                            <th class="text-end">${esc(I18N.margin)}</th>
                            <th class="text-end">${esc(I18N.pricingSource)}</th>
                        </tr>
                    </thead>
                    <tbody>${itemRows}</tbody>
                </table>
            </div>
        `;
    }

    // ─── Shared pagination ───────────────────────────────────
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

    // ─── Export ──────────────────────────────────────────────
    function exportExcel() {
        const table = $('paOrdersTable');
        if (!table || typeof XLSX === 'undefined') {
            window.toastr?.error('Excel export unavailable.');
            return;
        }
        try {
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, 'Profit Analysis');
            XLSX.writeFile(wb, 'profit_analysis.xlsx');
        } catch (e) {
            window.toastr?.error('Export failed: ' + e.message);
        }
    }

    // ─── Helpers ─────────────────────────────────────────────
    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t); return d.innerHTML;
    }
    function capitalize(s) {
        return String(s || '').charAt(0).toUpperCase() + String(s || '').slice(1);
    }
    function fmtNum(n, d = 0) {
        return Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: d, maximumFractionDigits: d,
        });
    }
    function money(n) { return CURRENCY + fmtNum(n, 2); }
    function marginColor(m) {
        if (m >= 10) return 'success';
        if (m >= 0)  return 'warning';
        return 'danger';
    }
    function formatDate(d) {
        if (!d) return '—';
        const dt = new Date(d + 'T00:00:00');
        if (isNaN(dt)) return d;
        return dt.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    }

    return { load };
})();
</script>