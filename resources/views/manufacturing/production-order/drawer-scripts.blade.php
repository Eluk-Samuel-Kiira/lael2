<script>
window.ProductionDrawer = (function () {
    'use strict';

    const ROUTE_DETAIL = '{{ url("reports/production/detail") }}/__ID__';

    let currentId = null;
    let currentPayload = null;
    let bs = null;

    function getBs() {
        const el = document.getElementById('productionDrawer');
        if (!el) return null;
        bs = bs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return bs;
    }

    // ── open / reload / close ──────────────────────────────────
    async function open(id) {
        currentId = id;
        const inst = getBs();
        if (!inst) return;

        showSkeleton();
        inst.show();

        try {
            const res = await fetch(ROUTE_DETAIL.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();
            currentPayload = data;
            render(data);
        } catch (err) {
            console.error('[production-drawer] load failed:', err);
            renderError(err.message);
        }
    }

    async function reload(id) {
        if (!id) return;
        try {
            const res = await fetch(ROUTE_DETAIL.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const data = await res.json();
            currentPayload = data;
            render(data);
        } catch (err) {
            console.error('[production-drawer] reload failed:', err);
        }
    }

    function close() { getBs()?.hide(); }
    function isOpenFor(id) { return String(currentId) === String(id); }

    // ── states ─────────────────────────────────────────────────
    function showSkeleton() {
        document.getElementById('productionDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('productionDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('productionDrawerFooter')?.classList.add('d-none');
        const sub = document.getElementById('productionDrawerSubtitle');
        if (sub) sub.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('productionDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('productionDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>${escapeHtml(msg)}
            </div>
        `;
    }

    // ── main render ───────────────────────────────────────────
    function render(data) {
        const order = data.order;
        currentId = order.id;

        const sub = document.getElementById('productionDrawerSubtitle');
        if (sub) sub.textContent = order.production_number;

        document.getElementById('productionDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('productionDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = renderPayload(data);

        // Recompute output totals after DOM insertion
        setTimeout(recomputeOutputTotals, 0);

        // Footer actions
        const footer  = document.getElementById('productionDrawerFooter');
        const actions = document.getElementById('productionDrawerActions');
        actions.innerHTML = renderActionCluster(order);
        footer.classList.remove('d-none');
        bindActionCluster(order);
    }

    // ── action cluster (start / complete / cancel / edit) ────
    function renderActionCluster(order) {
        const btns = [];
        const btn = (action, icon, label, tone = 'secondary') => `
            <button type="button"
                    class="btn btn-sm btn-${tone} d-flex align-items-center"
                    data-drawer-action="${action}">
                <i class="bi ${icon} me-2"></i>${label}
            </button>
        `;

        if (order.status === 'draft') {
            btns.push(btn('start', 'bi-play-fill', @json(__('passwords.start_production')), 'warning'));
        }
        if (order.status === 'in_progress') {
            btns.push(btn('complete', 'bi-check-circle', @json(__('passwords.complete_production')), 'success'));
        }
        if (['draft', 'in_progress'].includes(order.status)) {
            btns.push(btn('cancel', 'bi-x-circle', @json(__('passwords.cancel')), 'danger'));
            btns.push(btn('edit',   'bi-pencil',   @json(__('auth._edit')),       'primary'));
        }

        if (!btns.length) return '';
        return `<div class="d-flex align-items-center gap-2">${btns.join('')}</div>`;
    }

    function bindActionCluster(order) {
        const actions = document.getElementById('productionDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                const action = el.getAttribute('data-drawer-action');

                // Delegate to existing modals — they are already wired
                const fn = window.ProductionActions?.[action];
                if (typeof fn === 'function') {
                    close();   // close drawer first; modal opens on top
                    fn(order.id);
                } else {
                    window.toastr?.info?.(`${action} is not wired up.`);
                }
            });
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // BELOW: everything from the old view-modal-scripts
    // (renderPayload, renderPurchaseOrderItems, renderBatchLogs,
    //  renderInputs, renderOutputs, renderInventoryLogs, renderStory,
    //  toggleOutputRow, toggleAllOutputRows, recomputeOutputTotals,
    //  computeOutputBreakdown, renderOutputBreakdown, escapeHtml,
    //  fmtNum, fmtMoney)
    // ═══════════════════════════════════════════════════════════════

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    function fmtNum(n, decimals = 2) {
        return Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    }

    function fmtMoney(n) {
        return '{{ currency_symbol() }}' + fmtNum(n, 2);
    }

    function computeOutputBreakdown(metrics, order) {
        const rows = (metrics.output_breakdown || []).map((r, i) => ({
            id:           `po-drawer-output-${i}`,
            variant_name: r.variant_name,
            sku:          r.sku,
            bags:         Number(r.bags)      || 0,
            weight:       Number(r.weight)    || 0,
            total_kg:     Number(r.total_kg)  || 0,
            revenue:      Number(r.revenue)   || 0,
            included:     true,
        }));

        const sumAllOutputKg = rows.reduce((s, r) => s + r.total_kg, 0);
        const declaredInputKg = Number(metrics.total_input_kg || 0);

        let inputKg, inputSource;
        if (Number.isFinite(declaredInputKg) && declaredInputKg > 0 && declaredInputKg >= sumAllOutputKg) {
            inputKg     = declaredInputKg;
            inputSource = 'declared';
        } else if (sumAllOutputKg > 0) {
            inputKg     = sumAllOutputKg;
            inputSource = 'derived';
        } else {
            inputKg     = null;
            inputSource = 'unavailable';
        }

        return { rows, inputKg, inputSource };
    }

    function renderOutputBreakdown(rows, inputKg) {
        if (!rows.length) {
            return `<div class="alert alert-light-info">No output breakdown available.</div>`;
        }

        const inputIsSane = inputKg !== null && inputKg > 0;

        const bodyRows = rows.map(r => {
            const share = inputIsSane
                ? ((r.total_kg / inputKg) * 100).toFixed(2) + '%'
                : '—';

            return `
                <tr id="${r.id}" class="output-row" style="cursor:pointer;"
                    onclick="ProductionDrawer.toggleOutputRow('${r.id}')">
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input output-row-check" checked
                               onclick="event.stopPropagation(); ProductionDrawer.toggleOutputRow('${r.id}')">
                    </td>
                    <td>
                        <div class="fw-bold">${escapeHtml(r.variant_name)}</div>
                        ${r.sku ? `<small class="text-muted">${escapeHtml(r.sku)}</small>` : ''}
                    </td>
                    <td class="text-end">${fmtNum(r.bags, 0)}</td>
                    <td class="text-end">${fmtNum(r.weight, 2)} kg</td>
                    <td class="text-end fw-bold">${fmtNum(r.total_kg, 2)} kg</td>
                    <td class="text-end output-row-share">${share}</td>
                    <td class="text-end output-row-revenue">${fmtMoney(r.revenue)}</td>
                </tr>
            `;
        }).join('');

        return `
            <div class="d-flex gap-2 mb-2">
                <button type="button" class="btn btn-xs btn-light-primary" onclick="ProductionDrawer.toggleAllOutputRows(true)">Include All</button>
                <button type="button" class="btn btn-xs btn-light" onclick="ProductionDrawer.toggleAllOutputRows(false)">Exclude All</button>
            </div>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped align-middle" id="poDrawerOutputTable">
                    <thead>
                        <tr class="bg-light">
                            <th style="width:40px;"></th>
                            <th>Variant</th>
                            <th class="text-end">Bags / Units</th>
                            <th class="text-end">Weight per Unit</th>
                            <th class="text-end">Total KG</th>
                            <th class="text-end">% of Input</th>
                            <th class="text-end">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${bodyRows}
                        <tr class="bg-light fw-bold">
                            <td colspan="4" class="text-end">Total Output (included)</td>
                            <td class="text-end" id="poDrawerOutputTotalKg">0 kg</td>
                            <td class="text-end" id="poDrawerOutputTotalShare">0%</td>
                            <td class="text-end" id="poDrawerOutputTotalRevenue">${fmtMoney(0)}</td>
                        </tr>
                        <tr class="bg-light-danger">
                            <td colspan="4" class="text-end text-danger fw-bold">Loss (dust / spillage / shrinkage)</td>
                            <td class="text-end text-danger fw-bold" id="poDrawerOutputLossKg">0 kg</td>
                            <td class="text-end text-danger fw-bold" id="poDrawerOutputLossPct">0%</td>
                            <td class="text-end text-muted">—</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `;
    }

    function renderPurchaseOrderItems(poItems) {
        if (!poItems || poItems.length === 0) {
            return `<div class="alert alert-light-info d-flex align-items-center mb-4">
                <i class="bi bi-info-circle fs-2 me-3"></i>
                <div class="fs-7">No purchase order data linked to this production run.</div>
            </div>`;
        }

        const total = poItems.reduce((s, r) => s + (Number(r.subtotal) || 0), 0);

        const rows = poItems.map(row => {
            const chips = (row.batches || []).map(b => `
                <span class="badge badge-light-dark me-1 mb-1"
                      title="${fmtNum(b.quantity_consumed, 2)} consumed @ ${fmtMoney(b.unit_cost)}">
                    ${escapeHtml(b.batch_number)} × ${fmtNum(b.quantity_consumed, 2)}
                </span>
            `).join('');

            return `
                <tr>
                    <td><span class="badge badge-light-success">${escapeHtml(row.po_number || '-')}</span></td>
                    <td>${escapeHtml(row.supplier_name || '-')}</td>
                    <td>
                        <div class="fw-bold text-gray-800">${escapeHtml(row.product_name || '-')}</div>
                        ${row.sku ? `<small class="text-muted">SKU: ${escapeHtml(row.sku)}</small>` : ''}
                    </td>
                    <td class="text-end">${fmtNum(row.ordered_quantity, 0)}</td>
                    <td class="text-end">${fmtNum(row.received_quantity, 0)}</td>
                    <td class="text-end fw-bold text-danger">${fmtNum(row.consumed_quantity, 2)}</td>
                    <td class="text-end">${fmtMoney(row.unit_cost)}</td>
                    <td class="text-end fw-bold">${fmtMoney(row.subtotal)}</td>
                    <td>${chips || '<span class="text-muted">-</span>'}</td>
                </tr>
            `;
        }).join('');

        return `
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped align-middle">
                    <thead><tr>
                        <th>PO Number</th><th>Supplier</th><th>Product</th>
                        <th class="text-end">Ordered</th>
                        <th class="text-end">Received</th>
                        <th class="text-end">Consumed</th>
                        <th class="text-end">Unit Cost</th>
                        <th class="text-end">Subtotal</th>
                        <th>Batches</th>
                    </tr></thead>
                    <tbody>${rows}</tbody>
                    <tfoot class="bg-light fw-bold"><tr>
                        <td colspan="7" class="text-end">Total Cost of PO Items</td>
                        <td class="text-end text-success">${fmtMoney(total)}</td>
                        <td></td>
                    </tr></tfoot>
                </table>
            </div>
        `;
    }

    function renderBatchLogs(batchLogs) {
        if (!batchLogs || batchLogs.length === 0) {
            return `<div class="alert alert-light-info">No batch logs found.</div>`;
        }

        const typeColors = {
            received: 'success', depleted: 'danger', produced: 'info',
            expired: 'warning', adjusted: 'secondary', transferred: 'primary',
            assigned: 'primary', unassigned: 'secondary',
        };

        const rows = batchLogs.map(log => {
            let source = '-';
            if (log.type === 'produced' && log.production_order_id) {
                source = `<span class="badge badge-light-info">Production</span>`;
            } else if (log.purchase_order_number) {
                source = `<span class="badge badge-light-success">${escapeHtml(log.purchase_order_number)}</span>
                          ${log.supplier_name ? `<div class="text-muted fs-8">${escapeHtml(log.supplier_name)}</div>` : ''}`;
            } else if (log.production_order_id) {
                source = `<span class="badge badge-light-secondary">Production #${log.production_order_id}</span>`;
            }

            const change = Number(log.quantity_change || 0);

            return `
                <tr>
                    <td><span class="badge badge-light-dark">${escapeHtml(log.batch_number)}</span></td>
                    <td>${escapeHtml(log.variant?.name || log.variant_name || 'N/A')}</td>
                    <td><span class="badge badge-light-${typeColors[log.type] || 'secondary'}">${escapeHtml(log.type)}</span></td>
                    <td class="text-end ${change >= 0 ? 'text-success' : 'text-danger'} fw-bold">
                        ${change >= 0 ? '+' : ''}${fmtNum(change, 2)}
                    </td>
                    <td class="text-end">${fmtNum(log.quantity_before, 2)}</td>
                    <td class="text-end">${fmtNum(log.quantity_after, 2)}</td>
                    <td class="text-end">${fmtMoney(log.unit_cost)}</td>
                    <td class="text-end fw-bold">${fmtMoney(log.total_cost)}</td>
                    <td>${source}</td>
                    <td class="text-muted fs-7">${escapeHtml(log.event_date || '-')}</td>
                </tr>
            `;
        }).join('');

        return `
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped align-middle">
                    <thead><tr>
                        <th>Batch Number</th><th>Variant</th><th>Type</th>
                        <th class="text-end">Change</th>
                        <th class="text-end">Before</th>
                        <th class="text-end">After</th>
                        <th class="text-end">Unit Cost</th>
                        <th class="text-end">Total Cost</th>
                        <th>Source</th><th>Date</th>
                    </tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    function renderInventoryLogs(logs) {
        if (!logs || logs.length === 0) {
            return `<div class="alert alert-light-info">No inventory logs found.</div>`;
        }

        const rows = logs.map(log => {
            const change = Number(log.quantity_change || 0);
            return `
                <tr>
                    <td>${escapeHtml(log.variant?.name || 'N/A')}</td>
                    <td class="text-end ${change >= 0 ? 'text-success' : 'text-danger'} fw-bold">
                        ${change >= 0 ? '+' : ''}${fmtNum(change, 2)}
                    </td>
                    <td class="text-end">${fmtNum(log.quantity_before, 2)}</td>
                    <td class="text-end">${fmtNum(log.quantity_after, 2)}</td>
                    <td><span class="badge badge-light-info">${escapeHtml(log.reason || '-')}</span></td>
                    <td class="text-muted fs-7">${escapeHtml(log.created_at || '-')}</td>
                </tr>
            `;
        }).join('');

        return `
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped align-middle">
                    <thead><tr>
                        <th>Variant</th><th class="text-end">Change</th>
                        <th class="text-end">Before</th><th class="text-end">After</th>
                        <th>Reason</th><th>Date</th>
                    </tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    function renderInputs(order) {
        if (!order.inputs || order.inputs.length === 0) {
            return `<div class="alert alert-light-info">No inputs recorded.</div>`;
        }

        const qualityColors = { pending: 'secondary', accepted: 'success', rejected: 'danger' };

        const rows = order.inputs.map(input => {
            const yieldPct = Number(input.planned_quantity) > 0
                ? (Number(input.actual_quantity) / Number(input.planned_quantity) * 100)
                : 0;

            return `
                <tr>
                    <td>${escapeHtml(input.product_variant?.name || 'N/A')}</td>
                    <td class="text-end">${fmtNum(input.planned_quantity, 2)}</td>
                    <td class="text-end">${fmtNum(input.actual_quantity, 2)}</td>
                    <td class="text-end">${fmtNum(input.waste_quantity, 2)}</td>
                    <td class="text-end">${yieldPct.toFixed(1)}%</td>
                    <td class="text-end">${fmtMoney(input.actual_cost)}</td>
                    <td><span class="badge badge-light-${qualityColors[input.quality_status] || 'secondary'}">${escapeHtml(input.quality_status || '-')}</span></td>
                </tr>
            `;
        }).join('');

        return `
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped align-middle">
                    <thead><tr>
                        <th>Variant</th><th class="text-end">Planned</th>
                        <th class="text-end">Actual</th><th class="text-end">Waste</th>
                        <th class="text-end">Yield</th><th class="text-end">Cost</th>
                        <th>Quality</th>
                    </tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    function renderOutputs(order) {
        if (!order.outputs || order.outputs.length === 0) {
            return `<div class="alert alert-light-info">No outputs recorded.</div>`;
        }

        const qualityColors = { pending: 'secondary', approved: 'success', rejected: 'danger' };

        const rows = order.outputs.map(output => {
            const variant    = output.product_variant || {};
            const actual     = Number(output.actual_quantity || 0);
            const unitCost   = Number(output.frozen_unit_cost    ?? output.production_cost ?? 0);
            const totalCost  = Number(output.frozen_total_cost   ?? output.total_cost      ?? 0);
            const unitSell   = Number(output.frozen_selling_price?? output.selling_price   ?? 0);
            const sellingValue = actual * unitSell;
            const profit       = sellingValue - totalCost;
            const marginPct    = sellingValue > 0 ? (profit / sellingValue) * 100 : 0;
            const profitColor  = profit >= 0 ? 'success' : 'danger';

            const yieldPct = Number(output.planned_quantity) > 0
                ? (actual / Number(output.planned_quantity) * 100) : 0;

            const stratColor = output.inventory_strategy === 'batch' ? 'info'
                            : output.inventory_strategy === 'serial' ? 'warning' : 'primary';

            return `
                <tr>
                    <td>${escapeHtml(variant.name || 'N/A')}</td>
                    <td><span class="badge badge-light-${stratColor}">${escapeHtml(output.inventory_strategy)}</span></td>
                    <td class="text-end">${fmtNum(output.planned_quantity, 2)}</td>
                    <td class="text-end text-success fw-bold">${fmtNum(actual, 2)}</td>
                    <td class="text-end text-danger">${fmtNum(output.defective_quantity, 2)}</td>
                    <td class="text-end">${yieldPct.toFixed(1)}%</td>
                    <td class="text-end">${fmtMoney(unitCost)}</td>
                    <td class="text-end fw-bold">${fmtMoney(totalCost)}</td>
                    <td class="text-end">${fmtMoney(unitSell)}</td>
                    <td class="text-end">${fmtMoney(sellingValue)}</td>
                    <td class="text-end fw-bold text-${profitColor}">
                        ${fmtMoney(profit)}
                        <small class="d-block text-muted fs-8">${marginPct.toFixed(1)}%</small>
                    </td>
                    <td>${escapeHtml(output.batch_number || '-')}</td>
                    <td><span class="badge badge-light-${qualityColors[output.quality_status] || 'secondary'}">${escapeHtml(output.quality_status || '-')}</span></td>
                </tr>
            `;
        }).join('');

        const totalCost   = order.outputs.reduce((s, o) => s + Number(o.frozen_total_cost   ?? o.total_cost     ?? 0), 0);
        const totalSell   = order.outputs.reduce((s, o) => s + Number(o.actual_quantity || 0) * Number(o.frozen_selling_price ?? o.selling_price ?? 0), 0);
        const totalProfit = totalSell - totalCost;
        const totalMargin = totalSell > 0 ? (totalProfit / totalSell) * 100 : 0;
        const totalColor  = totalProfit >= 0 ? 'success' : 'danger';

        return `
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped align-middle">
                    <thead><tr>
                        <th>Variant</th><th>Strategy</th>
                        <th class="text-end">Planned</th><th class="text-end">Actual</th>
                        <th class="text-end">Defective</th><th class="text-end">Yield</th>
                        <th class="text-end">Packaging &amp; Milling Cost <small class="d-block text-muted fw-normal fs-8">per unit</small></th>
                        <th class="text-end">Packaging &amp; Milling Cost <small class="d-block text-muted fw-normal fs-8">Total Cost</th>
                        <th class="text-end">Unit Sell</th><th class="text-end">Selling Value</th>
                        <th class="text-end">Potential Profit</th>
                        <th>Batch</th><th>Quality</th>
                    </tr></thead>
                    <tbody>${rows}</tbody>
                    <tfoot class="bg-light fw-bold"><tr>
                        <td colspan="6" class="text-end">Totals</td>
                        <td class="text-end text-muted">—</td>
                        <td class="text-end">${fmtMoney(totalCost)}</td>
                        <td class="text-end text-muted">—</td>
                        <td class="text-end">${fmtMoney(totalSell)}</td>
                        <td class="text-end text-${totalColor}">
                            ${fmtMoney(totalProfit)}
                            <small class="d-block fs-8">${totalMargin.toFixed(1)}%</small>
                        </td>
                        <td colspan="2"></td>
                    </tr></tfoot>
                </table>
            </div>
        `;
    }

    // ── payload assembly ──────────────────────────────────────
    function renderPayload(data) {
        const order   = data.order;
        const metrics = data.metrics;

        let html = '';

        // Header
        html += `
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold mb-1">${escapeHtml(order.production_number)}</h4>
                    <div class="text-muted fs-7">
                        ${escapeHtml(order.location?.name || '-')} · ${escapeHtml(order.created_at || '')}
                    </div>
                </div>
                <div>
                    <span class="badge badge-light-${order.status_badge} fs-6 py-2 px-4">${escapeHtml(order.status_label)}</span>
                    ${order.payment_method_id ? `<span class="badge badge-light-success fs-6 py-2 px-4 ms-2">Paid</span>` : ''}
                </div>
            </div>
        `;

        // KPI cards
        html += `
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card bg-light-primary border border-primary border-dashed">
                    <div class="card-body text-center">
                        <span class="text-muted">Duration</span>
                        <div class="fs-2 fw-bold">${fmtNum(metrics.duration_hours, 2)}h</div>
                    </div>
                </div></div>
                <div class="col-md-4"><div class="card bg-light-success border border-success border-dashed">
                    <div class="card-body text-center">
                        <span class="text-muted">True Yield (kg/kg)</span>
                        <div class="fs-2 fw-bold text-success">${fmtNum(metrics.input_yield, 1)}%</div>
                        <div class="text-muted fs-8">${fmtNum(metrics.total_output_kg, 2)} / ${fmtNum(metrics.total_input_kg, 2)} kg</div>
                    </div>
                </div></div>
                <div class="col-md-4"><div class="card bg-light-info border border-info border-dashed">
                    <div class="card-body text-center">
                        <span class="text-muted">Cost Efficiency</span>
                        <div class="fs-2 fw-bold text-info">${fmtNum(metrics.cost_efficiency, 1)}%</div>
                        <div class="text-muted fs-8">revenue / total cost</div>
                    </div>
                </div></div>
            </div>
        `;

        // Profit band
        html += `
            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="card bg-light-warning border border-warning border-dashed">
                    <div class="card-body">
                        <span class="text-muted fw-semibold">Raw Materials (PO Cost)</span>
                        <div class="fs-2 fw-bold text-warning">${fmtMoney(metrics.po_cost)}</div>
                    </div>
                </div></div>
                <div class="col-md-3"><div class="card bg-light-danger border border-danger border-dashed">
                    <div class="card-body">
                        <span class="text-muted fw-semibold">Production Overhead</span>
                        <div class="fs-2 fw-bold text-danger">${fmtMoney(metrics.overhead_cost)}</div>
                    </div>
                </div></div>
                <div class="col-md-3"><div class="card bg-light-primary border border-primary border-dashed">
                    <div class="card-body">
                        <span class="text-muted fw-semibold">Output Selling Value</span>
                        <div class="fs-2 fw-bold text-primary">${fmtMoney(metrics.total_revenue)}</div>
                    </div>
                </div></div>
                <div class="col-md-3"><div class="card bg-light-${metrics.net_profit >= 0 ? 'success' : 'danger'} border border-${metrics.net_profit >= 0 ? 'success' : 'danger'} border-dashed">
                    <div class="card-body">
                        <span class="text-muted fw-semibold">Net Production Profit</span>
                        <div class="fs-2 fw-bold text-${metrics.net_profit >= 0 ? 'success' : 'danger'}">${fmtMoney(metrics.net_profit)}</div>
                        <div class="text-muted fs-8">revenue − raw materials − overhead</div>
                    </div>
                </div></div>
            </div>
        `;

        // PO items
        html += `<h6 class="fw-bold mb-3 mt-4"><i class="bi bi-shop me-2 text-success"></i>Purchase Order Items — Raw Materials Sourced From</h6>`;
        html += renderPurchaseOrderItems(data.purchase_order_items);

        // Batch logs
        html += `<h6 class="fw-bold mb-3 mt-4"><i class="bi bi-archive me-2 text-primary"></i>Batch Logs</h6>`;
        html += renderBatchLogs(data.batch_logs);

        // Inputs
        html += `<h6 class="fw-bold mb-3 mt-4"><i class="bi bi-box-arrow-in-down me-2 text-danger"></i>Inputs</h6>`;
        html += renderInputs(order);

        // Outputs
        html += `<h6 class="fw-bold mb-3 mt-4"><i class="bi bi-box-arrow-up me-2 text-success"></i>Outputs</h6>`;
        html += renderOutputs(order);

        // Weight breakdown
        if (metrics.output_breakdown && metrics.output_breakdown.length > 0) {
            const { rows, inputKg } = computeOutputBreakdown(metrics, order);

            html += `<h6 class="fw-bold mb-3 mt-4"><i class="bi bi-pie-chart me-2 text-success"></i>Output Breakdown (Weight-Based)</h6>`;
            html += renderOutputBreakdown(rows, inputKg);

            window.__poDrawerOutputRows    = rows;
            window.__poDrawerOutputInputKg = inputKg;
        }

        // Inventory logs
        html += `<h6 class="fw-bold mb-3 mt-4"><i class="bi bi-graph-up me-2 text-warning"></i>Inventory Logs</h6>`;
        html += renderInventoryLogs(data.inventory_logs);

        // Activity story
        html += `<h6 class="fw-bold mb-3 mt-4"><i class="bi bi-clock-history me-2 text-info"></i>Activity Story</h6>`;
        html += renderStory(data);

        return html;
    }

    function renderStory(data) {
        const events = [];

        (data.batch_logs || []).forEach(log => {
            events.push({
                when:      log.event_date || log.created_at,
                category:  'Batch',
                color:     'primary',
                narrative: log.metadata?.narrative || log.notes
                    || (log.variant?.name + ' — ' + log.type + ' ' + log.quantity_change),
            });
        });
        (data.inventory_logs || []).forEach(log => {
            events.push({
                when:      log.created_at,
                category:  'Inventory',
                color:     'warning',
                narrative: log.notes || log.metadata?.narrative || (log.variant?.name + ' — ' + log.reason),
            });
        });
        (data.inventory_adjustments || []).forEach(log => {
            events.push({ when: log.created_at, category: 'Adjustment', color: 'info', narrative: log.notes || log.reason });
        });
        (data.inventory_transactions || []).forEach(log => {
            events.push({ when: log.created_at, category: 'Transaction', color: 'secondary', narrative: log.notes || (log.type + ' ' + log.quantity) });
        });
        (data.payment_transactions || []).forEach(tx => {
            events.push({ when: tx.created_at, category: 'Payment', color: 'success', narrative: tx.description || tx.notes || (tx.transaction_type + ' ' + tx.amount) });
        });
        if (data.order_notes) {
            events.push({ when: data.order.created_at, category: 'Order', color: 'dark', narrative: data.order_notes });
        }

        events.sort((a, b) => new Date(a.when) - new Date(b.when));

        if (events.length === 0) {
            return `<div class="alert alert-light-info">No narrative events recorded.</div>`;
        }

        return `
            <div class="border rounded p-4 mb-4" style="max-height: 500px; overflow-y: auto; background: #fafafa;">
                ${events.map(e => `
                    <div class="d-flex align-items-start mb-3">
                        <span class="badge badge-light-${e.color} fw-bold me-3">${escapeHtml(e.category)}</span>
                        <div class="flex-grow-1">
                            <div class="text-gray-800">${escapeHtml(e.narrative)}</div>
                            <div class="text-muted fs-8 mt-1">${escapeHtml(e.when || '')}</div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    // ── toggle handlers ────────────────────────────────────────
    function toggleOutputRow(rowId) {
        const rows = window.__poDrawerOutputRows || [];
        const row = rows.find(r => r.id === rowId);
        if (!row) return;
        row.included = !row.included;
        const el = document.getElementById(rowId);
        if (el) {
            el.classList.toggle('table-secondary', !row.included);
            el.style.opacity = row.included ? '1' : '0.5';
            const cb = el.querySelector('.output-row-check');
            if (cb) cb.checked = row.included;
        }
        recomputeOutputTotals();
    }

    function toggleAllOutputRows(include) {
        const rows = window.__poDrawerOutputRows || [];
        rows.forEach(r => {
            r.included = include;
            const el = document.getElementById(r.id);
            if (el) {
                el.classList.toggle('table-secondary', !include);
                el.style.opacity = include ? '1' : '0.5';
                const cb = el.querySelector('.output-row-check');
                if (cb) cb.checked = include;
            }
        });
        recomputeOutputTotals();
    }

    function recomputeOutputTotals() {
        const rows      = window.__poDrawerOutputRows || [];
        const inputKg   = Number(window.__poDrawerOutputInputKg);
        const inputSane = Number.isFinite(inputKg) && inputKg > 0;

        const included = rows.filter(r => r.included);
        const totalKg  = included.reduce((s, r) => s + r.total_kg, 0);
        const totalRev = included.reduce((s, r) => s + r.revenue, 0);

        rows.forEach(r => {
            const shareEl = document.querySelector(`#${r.id} .output-row-share`);
            if (shareEl) {
                shareEl.textContent = inputSane ? ((r.total_kg / inputKg) * 100).toFixed(2) + '%' : '—';
                shareEl.style.opacity = r.included ? '1' : '0.4';
            }
            const revEl = document.querySelector(`#${r.id} .output-row-revenue`);
            if (revEl) revEl.style.opacity = r.included ? '1' : '0.4';
        });

        const lossKg     = inputSane ? Math.max(0, inputKg - totalKg) : null;
        const lossPct    = inputSane ? (lossKg / inputKg) * 100 : null;
        const totalShare = inputSane ? (totalKg / inputKg) * 100 : null;

        const setText = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
        setText('poDrawerOutputTotalKg',      fmtNum(totalKg, 2) + ' kg');
        setText('poDrawerOutputTotalShare',   inputSane ? fmtNum(totalShare, 2) + '%' : '—');
        setText('poDrawerOutputTotalRevenue', fmtMoney(totalRev));
        setText('poDrawerOutputLossKg',       inputSane ? fmtNum(lossKg, 2) + ' kg' : '—');
        setText('poDrawerOutputLossPct',      inputSane ? fmtNum(lossPct, 2) + '%' : '—');
    }

    window.ProductionDrawer = {
        open, reload, close, isOpenFor,
        toggleOutputRow, toggleAllOutputRows, recomputeOutputTotals,
    };
    return window.ProductionDrawer;
})();
</script>