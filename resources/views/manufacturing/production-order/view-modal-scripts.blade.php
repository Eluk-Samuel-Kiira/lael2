{{-- resources/views/manufacturing/production-order/view-modal-scripts.blade.php --}}
@once
@push('scripts')
<script>
window.POView = (function () {
    'use strict';

    const MODAL_ID = 'viewProductionModalShared';
    const ROUTES = {
        detail: (id) => `/reports/production/detail/${id}`,
    };

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function getModalEl() {
        return document.getElementById(MODAL_ID);
    }

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

    // ── Weight-based output breakdown ───────────────────────────
    // Same algorithm the report page uses.
    function computeOutputBreakdown(metrics, order) {
        const rows = (metrics.output_breakdown || []).map((r, i) => ({
            id:           `po-view-output-${i}`,
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
                <tr id="${r.id}"
                    class="output-row"
                    style="cursor:pointer;"
                    onclick="POView.toggleOutputRow('${r.id}')">
                    <td class="text-center">
                        <input type="checkbox"
                               class="form-check-input output-row-check"
                               checked
                               onclick="event.stopPropagation(); POView.toggleOutputRow('${r.id}')">
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
                <button type="button" class="btn btn-xs btn-light-primary"
                        onclick="POView.toggleAllOutputRows(true)">
                    Include All
                </button>
                <button type="button" class="btn btn-xs btn-light"
                        onclick="POView.toggleAllOutputRows(false)">
                    Exclude All
                </button>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped align-middle" id="poViewOutputTable">
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
                            <td class="text-end" id="poViewOutputTotalKg">0 kg</td>
                            <td class="text-end" id="poViewOutputTotalShare">0%</td>
                            <td class="text-end" id="poViewOutputTotalRevenue">${fmtMoney(0)}</td>
                        </tr>
                        <tr class="bg-light-danger">
                            <td colspan="4" class="text-end text-danger fw-bold">Loss (dust / spillage / shrinkage)</td>
                            <td class="text-end text-danger fw-bold" id="poViewOutputLossKg">0 kg</td>
                            <td class="text-end text-danger fw-bold" id="poViewOutputLossPct">0%</td>
                            <td class="text-end text-muted">—</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `;
    }

    // ── PO Items table ──────────────────────────────────────────
    function renderPurchaseOrderItems(poItems) {
        if (!poItems || poItems.length === 0) {
            return `
                <div class="alert alert-light-info d-flex align-items-center mb-4">
                    <i class="ki-duotone ki-information fs-2 me-3"></i>
                    <div class="fs-7">No purchase order data linked to this production run.</div>
                </div>
            `;
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
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Supplier</th>
                            <th>Product</th>
                            <th class="text-end">Ordered</th>
                            <th class="text-end">Received</th>
                            <th class="text-end">Consumed</th>
                            <th class="text-end">Unit Cost</th>
                            <th class="text-end">Subtotal</th>
                            <th>Batches</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td colspan="7" class="text-end">Total Cost of PO Items</td>
                            <td class="text-end text-success">${fmtMoney(total)}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
    }

    // ── Batch logs table ────────────────────────────────────────
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
                source = `<span class="badge badge-light-info"><i class="ki-duotone ki-industry fs-5 me-1"></i>Production</span>`;
            } else if (log.purchase_order_number) {
                source = `
                    <span class="badge badge-light-success">
                        <i class="ki-duotone ki-shop fs-5 me-1"></i>${escapeHtml(log.purchase_order_number)}
                    </span>
                    ${log.supplier_name ? `<div class="text-muted fs-8">${escapeHtml(log.supplier_name)}</div>` : ''}
                `;
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
                    <thead>
                        <tr>
                            <th>Batch Number</th>
                            <th>Variant</th>
                            <th>Type</th>
                            <th class="text-end">Change</th>
                            <th class="text-end">Before</th>
                            <th class="text-end">After</th>
                            <th class="text-end">Unit Cost</th>
                            <th class="text-end">Total Cost</th>
                            <th>Source</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    // ── Inventory logs table ────────────────────────────────────
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
                    <thead>
                        <tr>
                            <th>Variant</th>
                            <th class="text-end">Change</th>
                            <th class="text-end">Before</th>
                            <th class="text-end">After</th>
                            <th>Reason</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    // ── Inputs table ────────────────────────────────────────────
    function renderInputs(order) {
        if (!order.inputs || order.inputs.length === 0) {
            return `<div class="alert alert-light-info">No inputs recorded.</div>`;
        }

        const qualityColors = {
            pending: 'secondary', accepted: 'success', rejected: 'danger',
        };

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
                    <thead>
                        <tr>
                            <th>Variant</th>
                            <th class="text-end">Planned</th>
                            <th class="text-end">Actual</th>
                            <th class="text-end">Waste</th>
                            <th class="text-end">Yield</th>
                            <th class="text-end">Cost</th>
                            <th>Quality</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    // ── Outputs table ───────────────────────────────────────────
    function renderOutputs(order) {
        if (!order.outputs || order.outputs.length === 0) {
            return `<div class="alert alert-light-info">No outputs recorded.</div>`;
        }

        const qualityColors = {
            pending: 'secondary', approved: 'success', rejected: 'danger',
        };

        // ── Compute per-row potential profit ────────────────────────
        //   selling value = actual_quantity × selling_price
        //   (prefer discount_selling_price when present, fall back to selling_price)
        //   cost value    = production_cost (already stored as TOTAL on the row)
        //   profit        = selling value − cost value
        const rows = order.outputs.map(output => {
            const variant = output.product_variant || {};

            const actual    = Number(output.actual_quantity || 0);
            const listSell  = Number(variant.discount_selling_price ?? variant.selling_price ?? 0);
            const rowCost   = Number(output.production_cost || 0);

            const sellingValue = actual * listSell;
            const profit       = sellingValue - rowCost;
            const marginPct    = sellingValue > 0 ? (profit / sellingValue) * 100 : 0;
            const profitColor  = profit >= 0 ? 'success' : 'danger';

            const yieldPct = Number(output.planned_quantity) > 0
                ? (actual / Number(output.planned_quantity) * 100)
                : 0;

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
                    <td class="text-end">${fmtMoney(rowCost)}</td>
                    <td class="text-end">${fmtMoney(listSell)}</td>
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

        // ── Totals row ──────────────────────────────────────────────
        const totalCost   = order.outputs.reduce((s, o) => s + Number(o.production_cost || 0), 0);
        const totalSell   = order.outputs.reduce((s, o) => {
            const v = o.product_variant || {};
            return s + Number(o.actual_quantity || 0) * Number(v.discount_selling_price ?? v.selling_price ?? 0);
        }, 0);
        const totalProfit = totalSell - totalCost;
        const totalMargin = totalSell > 0 ? (totalProfit / totalSell) * 100 : 0;
        const totalColor  = totalProfit >= 0 ? 'success' : 'danger';

        return `
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Variant</th>
                            <th>Strategy</th>
                            <th class="text-end">Planned</th>
                            <th class="text-end">Actual</th>
                            <th class="text-end">Defective</th>
                            <th class="text-end">Yield</th>
                            <th class="text-end">Cost</th>
                            <th class="text-end">Unit Sell Price</th>
                            <th class="text-end">Selling Value</th>
                            <th class="text-end">Potential Profit</th>
                            <th>Batch</th>
                            <th>Quality</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td colspan="6" class="text-end">Totals</td>
                            <td class="text-end">${fmtMoney(totalCost)}</td>
                            <td class="text-end text-muted">—</td>
                            <td class="text-end">${fmtMoney(totalSell)}</td>
                            <td class="text-end text-${totalColor}">
                                ${fmtMoney(totalProfit)}
                                <small class="d-block fs-8">${totalMargin.toFixed(1)}%</small>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
    }

    // ── Main render ─────────────────────────────────────────────
    function renderPayload(data) {
        const order    = data.order;
        const metrics  = data.metrics;
        const currency = '{{ currency_symbol() }}';

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
                    <span class="badge badge-light-${order.status_badge} fs-6 py-2 px-4">
                        ${escapeHtml(order.status_label)}
                    </span>
                    ${order.payment_method_id ? `
                        <span class="badge badge-light-success fs-6 py-2 px-4 ms-2">
                            <i class="ki-duotone ki-wallet fs-2 me-1"></i>Paid
                        </span>
                    ` : ''}
                </div>
            </div>
        `;

        // Metric cards
        html += `
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card card-flush bg-light-primary border border-primary border-dashed">
                        <div class="card-body text-center">
                            <span class="text-muted">Duration</span>
                            <div class="fs-2 fw-bold">${fmtNum(metrics.duration_hours, 2)}h</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-flush bg-light-success border border-success border-dashed">
                        <div class="card-body text-center">
                            <span class="text-muted">True Yield (kg/kg)</span>
                            <div class="fs-2 fw-bold text-success">${fmtNum(metrics.input_yield, 1)}%</div>
                            <div class="text-muted fs-8">
                                ${fmtNum(metrics.total_output_kg, 2)} / ${fmtNum(metrics.total_input_kg, 2)} kg
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-flush bg-light-info border border-info border-dashed">
                        <div class="card-body text-center">
                            <span class="text-muted">Cost Efficiency</span>
                            <div class="fs-2 fw-bold text-info">${fmtNum(metrics.cost_efficiency, 1)}%</div>
                            <div class="text-muted fs-8">revenue / batch cost</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-flush bg-light-${metrics.profit >= 0 ? 'success' : 'danger'} border border-${metrics.profit >= 0 ? 'success' : 'danger'} border-dashed">
                        <div class="card-body text-center">
                            <span class="text-muted">Net Profit</span>
                            <div class="fs-2 fw-bold text-${metrics.profit >= 0 ? 'success' : 'danger'}">
                                ${fmtMoney(metrics.profit)}
                            </div>
                            <div class="text-muted fs-8">revenue ${fmtMoney(metrics.total_revenue)}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // ─── POTENTIAL PROFIT BAND ─────────────────────────────────
        // PO cost vs selling value of outputs
        const poCostTotal = (data.purchase_order_items || []).reduce(
            (s, r) => s + Number(r.subtotal || 0), 0
        );

        // If no PO items exist, fall back to the controller's batch cost
        const effectiveCost = poCostTotal > 0 ? poCostTotal : Number(order.total_input_cost || 0);

        const outputSellTotal = (order.outputs || []).reduce((s, o) => {
            const v = o.product_variant || {};
            const price = Number(v.discount_selling_price ?? v.selling_price ?? 0);
            return s + Number(o.actual_quantity || 0) * price;
        }, 0);

        const potentialProfit = outputSellTotal - effectiveCost;
        const potentialMargin = outputSellTotal > 0 ? (potentialProfit / outputSellTotal) * 100 : 0;

        html += `
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card card-flush bg-light-warning border border-warning border-dashed">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted fw-semibold">PO Cost (Raw Materials)</span>
                                <i class="ki-duotone ki-shop fs-2x text-warning opacity-50">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="fs-2 fw-bold text-warning">${fmtMoney(effectiveCost)}</div>
                            ${poCostTotal === 0 ? '<div class="text-muted fs-8">Derived from batch cost (no PO linked)</div>' : ''}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-flush bg-light-primary border border-primary border-dashed">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted fw-semibold">Output Selling Value</span>
                                <i class="ki-duotone ki-dollar fs-2x text-primary opacity-50">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="fs-2 fw-bold text-primary">${fmtMoney(outputSellTotal)}</div>
                            <div class="text-muted fs-8">at list / discount selling price</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-flush bg-light-${potentialProfit >= 0 ? 'success' : 'danger'} border border-${potentialProfit >= 0 ? 'success' : 'danger'} border-dashed">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted fw-semibold">Potential Profit</span>
                                <i class="ki-duotone ki-chart-line-up fs-2x text-${potentialProfit >= 0 ? 'success' : 'danger'} opacity-50">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="fs-2 fw-bold text-${potentialProfit >= 0 ? 'success' : 'danger'}">
                                ${fmtMoney(potentialProfit)}
                            </div>
                            <div class="text-muted fs-8">margin ${potentialMargin.toFixed(1)}%</div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // ── PO ITEMS ───────────────────────────────────────────────
        html += `
            <h6 class="fw-bold mb-3 mt-4">
                <i class="ki-duotone ki-shop fs-3 me-2 text-success"></i>
                Purchase Order Items — Raw Materials Sourced From
            </h6>
        `;
        html += renderPurchaseOrderItems(data.purchase_order_items);

        // ── BATCH LOGS ─────────────────────────────────────────────
        html += `
            <h6 class="fw-bold mb-3 mt-4">
                <i class="ki-duotone ki-archive fs-3 me-2 text-primary"></i>
                Batch Logs — Batches Consumed and Remaining Balances
            </h6>
        `;
        html += renderBatchLogs(data.batch_logs);

        // ── INPUTS ─────────────────────────────────────────────────
        html += `
            <h6 class="fw-bold mb-3 mt-4">
                <i class="ki-duotone ki-box-arrow-in-down fs-3 me-2 text-danger"></i>
                Inputs
            </h6>
        `;
        html += renderInputs(order);

        // ── OUTPUTS ────────────────────────────────────────────────
        html += `
            <h6 class="fw-bold mb-3 mt-4">
                <i class="ki-duotone ki-box-arrow-out fs-3 me-2 text-success"></i>
                Outputs
            </h6>
        `;
        html += renderOutputs(order);

        // ── WEIGHT-BASED BREAKDOWN (the interesting part) ─────────
        if (metrics.output_breakdown && metrics.output_breakdown.length > 0) {
            const { rows, inputKg } = computeOutputBreakdown(metrics, order);

            html += `
                <h6 class="fw-bold mb-3 mt-4">
                    <i class="ki-duotone ki-chart-pie fs-3 me-2 text-success"></i>
                    Output Breakdown (Weight-Based)
                    <span class="text-muted fs-7 ms-2">— click any row to include / exclude it from the totals</span>
                </h6>
            `;
            html += renderOutputBreakdown(rows, inputKg);

            // Attach state for the toggle functions
            window.__poViewOutputRows     = rows;
            window.__poViewOutputInputKg  = inputKg;
        }

        // ── INVENTORY LOGS ─────────────────────────────────────────
        html += `
            <h6 class="fw-bold mb-3 mt-4">
                <i class="ki-duotone ki-chart-simple fs-3 me-2 text-warning"></i>
                Inventory Logs
            </h6>
        `;

        html += renderInventoryLogs(data.inventory_logs);


        // ── ACTIVITY STORY ─────────────────────────────────────────
        html += `
            <h6 class="fw-bold mb-3 mt-4">
                <i class="ki-duotone ki-time fs-3 me-2 text-info"></i>
                Activity Story
                <span class="text-muted fs-7 ms-2">— every event recorded about this order, in order</span>
            </h6>
        `;
        html += renderStory(data);

        return html;
    }

    // ── Activity Story ─────────────────────────────────────────
    // Aggregates every narrative written about this production order
    // into a single chronological feed.
    function renderStory(data) {
        const events = [];

        // Batch logs → metadata.narrative
        (data.batch_logs || []).forEach(log => {
            const narrative = log.metadata?.narrative
                || log.notes
                || (log.variant?.name + ' — ' + log.type + ' ' + log.quantity_change);

            events.push({
                when:      log.event_date || log.created_at,
                category:  'Batch',
                color:     'primary',
                narrative: narrative,
            });
        });

        // Single-shop inventory logs → notes
        (data.inventory_logs || []).forEach(log => {
            const narrative = log.notes
                || log.metadata?.narrative
                || (log.variant?.name + ' — ' + log.reason);

            events.push({
                when:      log.created_at,
                category:  'Inventory',
                color:     'warning',
                narrative: narrative,
            });
        });

        // Multi-shop adjustments → notes
        (data.inventory_adjustments || []).forEach(log => {
            events.push({
                when:      log.created_at,
                category:  'Adjustment',
                color:     'info',
                narrative: log.notes || log.reason,
            });
        });

        // Multi-shop transactions → notes
        (data.inventory_transactions || []).forEach(log => {
            events.push({
                when:      log.created_at,
                category:  'Transaction',
                color:     'secondary',
                narrative: log.notes || (log.type + ' ' + log.quantity),
            });
        });

        // Payment transactions → description
        (data.payment_transactions || []).forEach(tx => {
            events.push({
                when:      tx.created_at,
                category:  'Payment',
                color:     'success',
                narrative: tx.description || tx.notes || (tx.transaction_type + ' ' + tx.amount),
            });
        });

        // Order creation / cancellation notes
        if (data.order_notes) {
            events.push({
                when:      data.order.created_at,
                category:  'Order',
                color:     'dark',
                narrative: data.order_notes,
            });
        }

        // Sort chronologically, oldest first
        events.sort((a, b) => new Date(a.when) - new Date(b.when));

        if (events.length === 0) {
            return `<div class="alert alert-light-info">No narrative events recorded.</div>`;
        }

        const rows = events.map(e => `
            <div class="d-flex align-items-start mb-3">
                <div class="me-3">
                    <span class="badge badge-light-${e.color} fw-bold">${escapeHtml(e.category)}</span>
                </div>
                <div class="flex-grow-1">
                    <div class="text-gray-800">${escapeHtml(e.narrative)}</div>
                    <div class="text-muted fs-8 mt-1">${escapeHtml(e.when || '')}</div>
                </div>
            </div>
        `).join('');

        return `
            <div class="border rounded p-4" style="max-height: 500px; overflow-y: auto; background: #fafafa;">
                ${rows}
            </div>
        `;
    }

    // ── Toggle functions for output breakdown ──────────────────
    function toggleOutputRow(rowId) {
        const rows = window.__poViewOutputRows || [];
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
        const rows = window.__poViewOutputRows || [];
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
        const rows     = window.__poViewOutputRows || [];
        const inputKg  = Number(window.__poViewOutputInputKg);
        const inputSane = Number.isFinite(inputKg) && inputKg > 0;

        const included = rows.filter(r => r.included);
        const totalKg  = included.reduce((s, r) => s + r.total_kg, 0);
        const totalRev = included.reduce((s, r) => s + r.revenue,  0);

        // Per-row %
        rows.forEach(r => {
            const shareEl = document.querySelector(`#${r.id} .output-row-share`);
            if (shareEl) {
                shareEl.textContent = inputSane
                    ? ((r.total_kg / inputKg) * 100).toFixed(2) + '%'
                    : '—';
                shareEl.style.opacity = r.included ? '1' : '0.4';
            }
            const revEl = document.querySelector(`#${r.id} .output-row-revenue`);
            if (revEl) revEl.style.opacity = r.included ? '1' : '0.4';
        });

        const lossKg     = inputSane ? Math.max(0, inputKg - totalKg) : null;
        const lossPct    = inputSane ? (lossKg / inputKg) * 100 : null;
        const totalShare = inputSane ? (totalKg / inputKg) * 100 : null;

        const setText = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };

        setText('poViewOutputTotalKg',      fmtNum(totalKg, 2) + ' kg');
        setText('poViewOutputTotalShare',   inputSane ? fmtNum(totalShare, 2) + '%' : '—');
        setText('poViewOutputTotalRevenue', fmtMoney(totalRev));
        setText('poViewOutputLossKg',       inputSane ? fmtNum(lossKg, 2) + ' kg' : '—');
        setText('poViewOutputLossPct',      inputSane ? fmtNum(lossPct, 2) + '%' : '—');
    }

    // ── Open ────────────────────────────────────────────────────
    async function openForOrder(orderId) {
        if (!orderId) return;

        const modalEl = getModalEl();
        if (!modalEl) {
            console.warn('[POView] shared view modal not found');
            return;
        }

        modalEl.dataset.orderId = orderId;

        const title = modalEl.querySelector('.po-view-title');
        if (title) title.textContent = '{{ __("passwords.production_order_details") }} — #' + orderId;

        const content = modalEl.querySelector('.po-view-content');
        content.innerHTML = `
            <div class="text-center py-10">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        `;

        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        try {
            const res = await fetch(ROUTES.detail(orderId), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();

            // Update the title with the actual production number
            if (title && data.order?.production_number) {
                title.textContent =
                    '{{ __("passwords.production_order_details") }} — ' + data.order.production_number;
            }

            content.innerHTML = renderPayload(data);

            // First render of totals
            setTimeout(recomputeOutputTotals, 0);

        } catch (err) {
            console.error('[POView] load failed:', err);
            content.innerHTML = `
                <div class="text-center py-10 text-danger">
                    <i class="ki-duotone ki-cross-circle fs-4tx mb-4"></i>
                    <h4>Error Loading Details</h4>
                    <p class="text-muted">${escapeHtml(err.message)}</p>
                </div>
            `;
        }
    }

    // Clear on close so re-opening doesn't show stale content
    document.addEventListener('hidden.bs.modal', function (e) {
        if (e.target.id !== MODAL_ID) return;
        e.target.querySelector('.po-view-content').innerHTML = `
            <div class="text-center py-10">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        `;
        window.__poViewOutputRows    = [];
        window.__poViewOutputInputKg = null;
    });

    return {
        openForOrder,
        toggleOutputRow,
        toggleAllOutputRows,
        recomputeOutputTotals,
    };
})();

// Register with ProductionActions
window.ProductionActions = window.ProductionActions || {};
window.ProductionActions.view = function (orderId) {
    window.POView.openForOrder(orderId);
};
</script>
@endpush
@endonce