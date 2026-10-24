<script>
window.InventorySales = (function () {
    'use strict';

    const DATA_ROUTE    = window.IS_ROUTES.data;
    const PRODUCT_ROUTE = window.IS_ROUTES.product;
    const CURRENCY      = window.REPORT_CURRENCY || '';
    const BOOT          = window.IS_BOOTSTRAP || {};
    const I18N          = window.IS_I18N || {};

    const state = {
        startDate:        BOOT.startDate        || '',
        endDate:          BOOT.endDate          || '',
        locationId:       BOOT.locationId       || '',
        departmentId:     BOOT.departmentId     || '',
        soldPerPage:      Number(BOOT.soldPerPage      || 15),
        unsoldPerPage:    Number(BOOT.unsoldPerPage    || 15),
        deadStockPerPage: Number(BOOT.deadStockPerPage || 10),
        soldPage:         1,
        unsoldPage:       1,
        deadStockPage:    1,
    };

    let drawerBs = null;
    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('isApplyBtn')?.addEventListener('click', () => {
            readFilters();
            state.soldPage = state.unsoldPage = state.deadStockPage = 1;
            load();
        });
        $('isClearBtn')?.addEventListener('click', resetFilters);
        $('isExportBtn')?.addEventListener('click', exportExcel);
        $('isPrintBtn')?.addEventListener('click', () => window.print());

        $('isSoldBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.is-row[data-variant-id]');
            if (!row) return;
            openDrawer(row.dataset.variantId, row.dataset.variantName);
        });

        document.getElementById('isDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('isDrawerContent').innerHTML = '';
            $('isDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate    = $('isStartDate')?.value    || '';
        state.endDate      = $('isEndDate')?.value      || '';
        state.locationId   = $('isLocation')?.value     || '';
        state.departmentId = $('isDepartment')?.value   || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.departmentId = '';
        state.soldPage = state.unsoldPage = state.deadStockPage = 1;
        ['isStartDate','isEndDate'].forEach(id => { if ($(id)) $(id).value = ''; });
        if (window.jQuery) {
            jQuery('#isLocation').val(null).trigger('change.select2');
            jQuery('#isDepartment').val(null).trigger('change.select2');
        }
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({
            sold_page:              state.soldPage,
            unsold_page:            state.unsoldPage,
            dead_stock_page:        state.deadStockPage,
            sold_per_page:          state.soldPerPage,
            unsold_per_page:        state.unsoldPerPage,
            dead_stock_per_page:    state.deadStockPerPage,
        });
        if (state.startDate)    params.set('start_date', state.startDate);
        if (state.endDate)      params.set('end_date', state.endDate);
        if (state.locationId)   params.set('location_id', state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);

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
            console.error('[inventory-sales] load failed:', err);
            window.toastr?.error('Failed to load inventory report.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('isSkeleton')?.classList.toggle('d-none', !v);
        $('isContent')?.classList.toggle('d-none', v);
    }

    function render(d) {
        const hasAny = (d.summary?.total_products ?? 0) > 0;
        if (!hasAny) {
            $('isEmpty')?.classList.remove('d-none');
            $('isSummaryCards').innerHTML = '';
            $('isMovementCards').innerHTML = '';
            ['isDeadCard','isSoldCard','isUnsoldCard'].forEach(id => $(id)?.classList.add('d-none'));
            return;
        }
        $('isEmpty')?.classList.add('d-none');
        renderSummary(d.summary);
        renderMovement(d.movement);
        renderDeadStock(d.dead_stock, d.dead_stock_pagination);
        renderSold(d.sold_products, d.sold_pagination);
        renderUnsold(d.unsold_products, d.unsold_pagination);
    }

    // ─── Summary ────────────────────────────────────────────
    function renderSummary(s) {
        $('isSummaryCards').innerHTML = `
            <div class="col">
                <div class="card bg-light-primary border border-primary border-dashed h-100">
                    <div class="card-body text-center py-4">
                        <div class="fs-2 fw-bold text-primary">${fmtNum(s.total_products, 0)}</div>
                        <div class="text-muted fs-7">${esc(I18N.totalProducts)}</div>
                        <span class="badge badge-light-primary mt-2 fs-8">${fmtNum(s.sold_count, 0)} / ${fmtNum(s.unsold_count, 0)}</span>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card bg-light-success border border-success border-dashed h-100">
                    <div class="card-body text-center py-4">
                        <div class="fs-2 fw-bold text-success">${fmtNum(s.sell_through_rate, 1)}%</div>
                        <div class="text-muted fs-7">${esc(I18N.sellThroughRate)}</div>
                        <span class="badge badge-light-success mt-2 fs-8">${fmtNum(s.turnover_rate, 2)}% ${esc(I18N.turnover)}</span>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card bg-light-warning border border-warning border-dashed h-100">
                    <div class="card-body text-center py-4">
                        <div class="fs-2 fw-bold text-warning">${fmtNum(s.total_units_sold, 0)}</div>
                        <div class="text-muted fs-7">${esc(I18N.totalUnits)}</div>
                        <span class="badge badge-light-warning mt-2 fs-8">${fmtNum(s.avg_daily_sales, 1)}/${esc(I18N.day)}</span>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card bg-light-info border border-info border-dashed h-100">
                    <div class="card-body text-center py-4">
                        <div class="fs-3 fw-bold text-info">${money(s.sold_revenue)}</div>
                        <div class="text-muted fs-7">${esc(I18N.soldInventoryValue)}</div>
                        <span class="badge badge-light-info mt-2 fs-8">${money(s.total_inventory_value)}</span>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card bg-light-secondary border border-secondary border-dashed h-100">
                    <div class="card-body text-center py-4">
                        <div class="fs-3 fw-bold text-gray-800">${money(s.sold_cost)}</div>
                        <div class="text-muted fs-7">${esc(I18N.cost)}</div>
                        <span class="badge badge-light-secondary mt-2 fs-8">${esc(I18N.costOfGoodsSold || 'COGS')}</span>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card bg-light-${s.sold_profit >= 0 ? 'success' : 'danger'} border border-${s.sold_profit >= 0 ? 'success' : 'danger'} border-dashed h-100">
                    <div class="card-body text-center py-4">
                        <div class="fs-3 fw-bold text-${s.sold_profit >= 0 ? 'success' : 'danger'}">${money(s.sold_profit)}</div>
                        <div class="text-muted fs-7">${esc(I18N.soldInventoryProfit)}</div>
                        <span class="badge badge-light-${s.sold_profit >= 0 ? 'success' : 'danger'} mt-2 fs-8">${fmtNum(s.sold_margin, 1)}% ${esc(I18N.margin)}</span>
                    </div>
                </div>
            </div>
        `;
    }

    // ─── Movement ───────────────────────────────────────────
    function renderMovement(m) {
        if (!m || m.total === 0) { $('isMovementCards').innerHTML = ''; return; }
        $('isMovementCards').innerHTML = `
            <div class="col-md-4"><div class="card bg-light-success border border-success border-dashed h-100">
                <div class="card-body text-center">
                    <div class="fs-1 fw-bold text-success">${fmtNum(m.fast_movers, 0)}</div>
                    <div class="text-muted">🚀 ${esc(I18N.fastMovers)} <small class="text-muted">(≥1/day)</small></div>
                    <span class="badge badge-light-success mt-2">${fmtNum(m.fast_pct, 1)}%</span>
                </div>
            </div></div>
            <div class="col-md-4"><div class="card bg-light-warning border border-warning border-dashed h-100">
                <div class="card-body text-center">
                    <div class="fs-1 fw-bold text-warning">${fmtNum(m.medium_movers, 0)}</div>
                    <div class="text-muted">🚚 ${esc(I18N.mediumMovers)} <small class="text-muted">(0.1–1/day)</small></div>
                    <span class="badge badge-light-warning mt-2">${fmtNum(m.medium_pct, 1)}%</span>
                </div>
            </div></div>
            <div class="col-md-4"><div class="card bg-light-danger border border-danger border-dashed h-100">
                <div class="card-body text-center">
                    <div class="fs-1 fw-bold text-danger">${fmtNum(m.slow_movers, 0)}</div>
                    <div class="text-muted">⏰ ${esc(I18N.slowMovers)} <small class="text-muted">(&lt;0.1/day)</small></div>
                    <span class="badge badge-light-danger mt-2">${fmtNum(m.slow_pct, 1)}%</span>
                </div>
            </div></div>
        `;
    }

    // ─── Dead stock ────────────────────────────────────────
    function renderDeadStock(rows, pagination) {
        if (!rows || rows.length === 0) { $('isDeadCard')?.classList.add('d-none'); return; }
        $('isDeadCard')?.classList.remove('d-none');
        $('isDeadBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.items}`;

        $('isDeadBody').innerHTML = rows.map(p => `
            <tr>
                <td class="ps-4">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-35px symbol-circle me-2">
                            <div class="symbol-label bg-light-danger"><i class="bi bi-box fs-2 text-danger"></i></div>
                        </div>
                        <span class="fw-bold">${esc(truncate(p.name, 25))}</span>
                    </div>
                </td>
                <td><span class="badge badge-light-primary">${esc(p.sku)}</span></td>
                <td class="text-center"><span class="badge badge-light-danger">${fmtNum(p.current_stock, 0)}</span></td>
                <td class="text-end">${money(p.price)}</td>
                <td class="text-end pe-4 text-warning fw-bold">${money(p.stock_value)}</td>
            </tr>
        `).join('');

        renderPagination(pagination, $('isDeadPaginationInfo'), $('isDeadPagination'), $('isDeadPaginationWrap'),
            (p) => { state.deadStockPage = p; load(); });
    }

    // ─── Sold ───────────────────────────────────────────────
    function renderSold(rows, pagination) {
        if (!rows || rows.length === 0) { $('isSoldCard')?.classList.add('d-none'); return; }
        $('isSoldCard')?.classList.remove('d-none');
        $('isSoldBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.products}`;

        $('isSoldBody').innerHTML = rows.map(p => {
            const stockClass = p.current_stock <= 5 ? 'danger' : (p.current_stock <= 10 ? 'warning' : 'success');
            const medal = p.rank <= 3
                ? `<span class="badge badge-light-${p.rank === 1 ? 'danger' : (p.rank === 2 ? 'warning' : 'info')} ms-1">
                     <i class="bi bi-${p.rank === 1 ? 'trophy' : 'award'}"></i>
                   </span>`
                : '';

            return `
                <tr class="is-row" data-variant-id="${p.id}" data-variant-name="${esc(p.name)}">
                    <td class="ps-4 fw-bold">${p.rank} ${medal}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-35px symbol-circle me-2">
                                <div class="symbol-label bg-light-${esc(p.movement_color)}"><i class="bi bi-box fs-2"></i></div>
                            </div>
                            <div>
                                <div class="fw-bold">${esc(truncate(p.name, 25))}</div>
                                <span class="badge badge-light-${esc(p.movement_color)} badge-sm">${esc(p.movement_category)}</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-light-primary">${esc(p.sku)}</span></td>
                    <td class="text-center fw-bold text-primary">${fmtNum(p.quantity_sold, 0)}</td>
                    <td class="text-end text-success fw-bold">${money(p.revenue)}</td>
                    <td class="text-end text-muted">${money(p.cost)}</td>
                    <td class="text-end ${p.profit >= 0 ? 'text-success' : 'text-danger'} fw-bold">
                        ${money(p.profit)}
                        <span class="badge badge-light-${p.profit >= 0 ? 'success' : 'danger'} ms-1">${fmtNum(p.margin, 1)}%</span>
                    </td>
                    <td class="text-center"><span class="badge badge-light-${stockClass}">${fmtNum(p.current_stock, 0)}</span></td>
                    <td class="text-end text-info">${money(p.stock_value)}</td>
                    <td class="text-center"><span class="badge badge-light-info">${fmtNum(p.times_ordered, 0)}</span></td>
                    <td class="text-center"><span class="badge badge-light-${esc(p.movement_color)}">${fmtNum(p.daily_sales_rate, 2)}</span></td>
                    <td class="text-center"><span class="badge badge-light-${esc(p.movement_color)}">${esc(p.movement_category)}</span></td>
                    <td class="pe-4">${p.last_sold_date ? esc(formatDate(p.last_sold_date)) : '<span class="text-muted">—</span>'}</td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination, $('isSoldPaginationInfo'), $('isSoldPagination'), $('isSoldPaginationWrap'),
            (p) => { state.soldPage = p; load(); });
    }

    // ─── Unsold ────────────────────────────────────────────
    function renderUnsold(rows, pagination) {
        if (!rows || rows.length === 0) { $('isUnsoldCard')?.classList.add('d-none'); return; }
        $('isUnsoldCard')?.classList.remove('d-none');
        $('isUnsoldBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.products}`;

        $('isUnsoldBody').innerHTML = rows.map(p => `
            <tr>
                <td class="ps-4">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-35px symbol-circle me-2">
                            <div class="symbol-label bg-light-${p.is_dead_stock ? 'danger' : esc(p.stock_class)}">
                                <i class="bi bi-box fs-2"></i>
                            </div>
                        </div>
                        <div>
                            <span class="fw-bold">${esc(truncate(p.name, 30))}</span>
                            ${p.is_dead_stock ? `<span class="badge badge-light-danger badge-sm d-block">${esc(I18N.deadStock)}</span>` : ''}
                        </div>
                    </div>
                </td>
                <td><span class="badge badge-light-primary">${esc(p.sku)}</span></td>
                <td class="text-center"><span class="badge badge-light-${esc(p.stock_class)}">${fmtNum(p.current_stock, 0)}</span></td>
                <td class="text-end">${money(p.price)}</td>
                <td class="text-end pe-4 text-warning fw-bold">${money(p.stock_value)}</td>
            </tr>
        `).join('');

        renderPagination(pagination, $('isUnsoldPaginationInfo'), $('isUnsoldPagination'), $('isUnsoldPaginationWrap'),
            (p) => { state.unsoldPage = p; load(); });
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

    // ─── Drawer ─────────────────────────────────────────────
    function getDrawer() {
        const el = document.getElementById('isDrawer');
        if (!el) return null;
        drawerBs = drawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return drawerBs;
    }

    async function openDrawer(variantId, variantName) {
        const inst = getDrawer();
        if (!inst) return;

        $('isDrawerSubtitle').textContent = variantName;
        $('isDrawerSkeleton')?.classList.remove('d-none');
        const content = $('isDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        inst.show();

        const params = new URLSearchParams();
        if (state.startDate)    params.set('start_date', state.startDate);
        if (state.endDate)      params.set('end_date', state.endDate);
        if (state.locationId)   params.set('location_id', state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);

        try {
            const url = PRODUCT_ROUTE.replace('__ID__', variantId);
            const res = await fetch(`${url}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            renderDrawer(json.data);
        } catch (err) {
            console.error('[is-drawer] failed:', err);
            $('isDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderDrawer(d) {
        $('isDrawerSkeleton')?.classList.add('d-none');
        const content = $('isDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        const saleRows = (d.recent_sales || []).map(s => `
            <tr>
                <td>
                    <span class="fw-bold">#${esc(s.order_number)}</span>
                    <br><small class="text-muted">${esc(s.order_date || '')}</small>
                </td>
                <td>${esc(s.customer_name)}</td>
                <td class="text-center">${fmtNum(s.quantity, 0)}</td>
                <td class="text-end">${money(s.unit_price)}</td>
                <td class="text-end fw-bold">${money(s.total_price)}</td>
                <td class="text-end text-danger">${money(s.total_cost)}</td>
                <td class="text-end ${s.profit >= 0 ? 'text-success' : 'text-danger'} fw-bold">${money(s.profit)}</td>
            </tr>
        `).join('');

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">${esc(d.name)}</h4>
                    <div class="text-muted fs-7">${esc(d.sku)} · ${esc(d.product_name || '—')}</div>
                </div>
                <span class="badge badge-light-primary fs-6 py-2 px-3">${fmtNum(d.summary.margin, 1)}%</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3"><div class="card card-flush bg-light-primary"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.totalUnitsSold)}</span>
                    <div class="fs-5 fw-bold text-primary">${fmtNum(d.summary.total_units_sold, 0)}</div>
                </div></div></div>
                <div class="col-6 col-md-3"><div class="card card-flush bg-light-success"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.totalRevenue)}</span>
                    <div class="fs-5 fw-bold text-success">${money(d.summary.total_revenue)}</div>
                </div></div></div>
                <div class="col-6 col-md-3"><div class="card card-flush bg-light-danger"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.totalCost)}</span>
                    <div class="fs-5 fw-bold text-danger">${money(d.summary.total_cost)}</div>
                </div></div></div>
                <div class="col-6 col-md-3"><div class="card card-flush bg-light-${d.summary.total_profit >= 0 ? 'success' : 'danger'}"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.profit)}</span>
                    <div class="fs-5 fw-bold text-${d.summary.total_profit >= 0 ? 'success' : 'danger'}">${money(d.summary.total_profit)}</div>
                </div></div></div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6"><div class="card card-flush bg-light-info"><div class="card-body py-3">
                    <span class="text-muted fs-8">${esc(I18N.currentStock)}</span>
                    <div class="fs-5 fw-bold text-info">${fmtNum(d.current_stock, 0)}</div>
                </div></div></div>
                <div class="col-md-6"><div class="card card-flush bg-light-warning"><div class="card-body py-3">
                    <span class="text-muted fs-8">${esc(I18N.stockValue)}</span>
                    <div class="fs-5 fw-bold text-warning">${money(d.stock_value)}</div>
                </div></div></div>
            </div>

            <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>${esc(I18N.recentSales || 'Recent sales')} (${fmtNum(d.summary.sale_count, 0)})</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="bg-light"><tr>
                        <th>${esc(I18N.orderNumber)}</th>
                        <th>${esc(I18N.customer)}</th>
                        <th class="text-center">${esc(I18N.qty)}</th>
                        <th class="text-end">${esc(I18N.unitPrice)}</th>
                        <th class="text-end">${esc(I18N.totalPrice)}</th>
                        <th class="text-end">${esc(I18N.totalCost)}</th>
                        <th class="text-end">${esc(I18N.profit)}</th>
                    </tr></thead>
                    <tbody>${saleRows || `<tr><td colspan="7" class="text-center text-muted py-3">${esc(I18N.noData)}</td></tr>`}</tbody>
                </table>
            </div>
        `;
    }

    // ─── Export ─────────────────────────────────────────────
    function exportExcel() {
        const table = $('isSoldTable');
        if (!table || typeof XLSX === 'undefined') {
            window.toastr?.error('Excel export unavailable.');
            return;
        }
        try {
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, 'Inventory Sales');
            XLSX.writeFile(wb, 'inventory_sales.xlsx');
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
    function truncate(s, len) { return !s ? '' : (s.length > len ? s.substring(0, len) + '…' : s); }
    function fmtNum(n, d = 0) {
        return Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: d, maximumFractionDigits: d,
        });
    }
    function money(n) { return CURRENCY + fmtNum(n, 2); }
    function formatDate(d) {
        if (!d) return '—';
        const dt = new Date(d + 'T00:00:00');
        if (isNaN(dt)) return d;
        return dt.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    }

    return { load };
})();
</script>