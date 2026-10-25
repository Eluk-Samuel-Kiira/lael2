<script>
window.ReportDrawer = (function () {
    'use strict';

    const CURRENCY = window.REPORT_CURRENCY;
    const OFF_CANVAS_ID = 'reportDrawer';
    let bs = null;

    function getBs() {
        const el = document.getElementById(OFF_CANVAS_ID);
        if (!el) return null;
        bs = bs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return bs;
    }

    // ── Formatters ──────────────────────────────────────────────
    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }
    function fmtNum(n, d = 2) {
        return Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: d, maximumFractionDigits: d,
        });
    }
    function fmtMoney(n) {
        return CURRENCY + fmtNum(n, 2);
    }

    // ── Open / close ────────────────────────────────────────────
    async function open(id) {
        const inst = getBs();
        if (!inst) return;

        showSkeleton();
        inst.show();

        try {
            const res = await fetch(window.REPORT_ROUTES.detail.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'Failed to load');
            if (!json.order) throw new Error(json.message || 'Missing order data in response');
            render(json);
        } catch (err) {
            console.error('[report-drawer] load failed:', err);
            renderError(err.message);
        }
    }

    function close() { getBs()?.hide(); }

    function showSkeleton() {
        document.getElementById('reportDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('reportDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        const s = document.getElementById('reportDrawerSubtitle');
        if (s) s.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('reportDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('reportDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="ki-duotone ki-cross-circle fs-2 me-2"></i>${esc(msg)}</div>`;
    }

    function render(payload) {
        const order = payload.order;

        const subtitleEl = document.getElementById('reportDrawerSubtitle');
        if (subtitleEl) {
            subtitleEl.textContent = `${order.production_number} · ${order.location?.name || ''}`;
        }

        document.getElementById('reportDrawerSkeleton')?.classList.add('d-none');

        const body = document.getElementById('reportDrawerContent');
        if (!body) { console.error('[report-drawer] #reportDrawerContent not found'); return; }

        body.classList.remove('d-none');
        body.innerHTML = renderBody(payload);
    }

    // ── Output Breakdown (weight-based) ────────────────────────
    function computeOutputBreakdown(metrics) {
        const rows = (metrics.output_breakdown || []).map((r, i) => ({
            id:           `dr-output-${i}`,
            variant_name: r.variant_name,
            sku:          r.sku,
            bags:         Number(r.bags)     || 0,
            weight:       Number(r.weight)   || 0,
            total_kg:     Number(r.total_kg) || 0,
            revenue:      Number(r.revenue)  || 0,
            included:     true,
        }));

        const sumAllOutputKg  = rows.reduce((s, r) => s + r.total_kg, 0);
        const declaredInputKg = Number(metrics.total_input_kg || 0);

        let inputKg;
        if (Number.isFinite(declaredInputKg) && declaredInputKg > 0 && declaredInputKg >= sumAllOutputKg) {
            inputKg = declaredInputKg;
        } else if (sumAllOutputKg > 0) {
            inputKg = sumAllOutputKg;
        } else {
            inputKg = null;
        }
        return { rows, inputKg };
    }

    function renderOutputBreakdown(rows, inputKg) {
        if (!rows.length) {
            return `<div class="alert alert-light-info">No output breakdown available.</div>`;
        }
        const inputSane = inputKg !== null && inputKg > 0;

        const bodyRows = rows.map(r => {
            const share = inputSane ? ((r.total_kg / inputKg) * 100).toFixed(2) + '%' : '—';
            return `
                <tr id="${r.id}" class="output-row" style="cursor:pointer;"
                    onclick="ReportDrawer.toggleOutputRow('${r.id}')">
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input output-row-check" checked
                               onclick="event.stopPropagation(); ReportDrawer.toggleOutputRow('${r.id}')">
                    </td>
                    <td>
                        <div class="fw-bold">${esc(r.variant_name)}</div>
                        ${r.sku ? `<small class="text-muted">${esc(r.sku)}</small>` : ''}
                    </td>
                    <td class="text-end">${fmtNum(r.bags, 0)}</td>
                    <td class="text-end">${fmtNum(r.weight, 2)} kg</td>
                    <td class="text-end fw-bold">${fmtNum(r.total_kg, 2)} kg</td>
                    <td class="text-end output-row-share">${share}</td>
                    <td class="text-end output-row-revenue">${fmtMoney(r.revenue)}</td>
                </tr>`;
        }).join('');

        return `
            <div class="d-flex gap-2 mb-2">
                <button type="button" class="btn btn-xs btn-light-primary"
                        onclick="ReportDrawer.toggleAllOutputRows(true)">Include All</button>
                <button type="button" class="btn btn-xs btn-light"
                        onclick="ReportDrawer.toggleAllOutputRows(false)">Exclude All</button>
            </div>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped align-middle">
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
                            <td class="text-end" id="drOutTotalKg">0 kg</td>
                            <td class="text-end" id="drOutTotalShare">0%</td>
                            <td class="text-end" id="drOutTotalRevenue">${fmtMoney(0)}</td>
                        </tr>
                        <tr class="bg-light-danger">
                            <td colspan="4" class="text-end text-danger fw-bold">Loss (dust / spillage / shrinkage)</td>
                            <td class="text-end text-danger fw-bold" id="drOutLossKg">0 kg</td>
                            <td class="text-end text-danger fw-bold" id="drOutLossPct">0%</td>
                            <td class="text-muted text-end">—</td>
                        </tr>
                    </tbody>
                </table>
            </div>`;
    }

    // ── PO Items ────────────────────────────────────────────────
    function renderPurchaseOrderItems(poItems) {
        if (!poItems || poItems.length === 0) {
            return `<div class="alert alert-light-info d-flex align-items-center mb-4">
                <i class="ki-duotone ki-information fs-2 me-3"></i>
                <div class="fs-7">No purchase order data linked to this production run.</div>
            </div>`;
        }

        const total = poItems.reduce((s, r) => s + (Number(r.subtotal) || 0), 0);

        const rows = poItems.map(row => {
            const chips = (row.batches || []).map(b => `
                <span class="badge badge-light-dark me-1 mb-1"
                      title="${fmtNum(b.quantity_consumed, 2)} consumed @ ${fmtMoney(b.unit_cost)}">
                    ${esc(b.batch_number)} × ${fmtNum(b.quantity_consumed, 2)}
                </span>`).join('');
            return `
                <tr>
                    <td><span class="badge badge-light-success">${esc(row.po_number || '-')}</span></td>
                    <td>${esc(row.supplier_name || '-')}</td>
                    <td>
                        <div class="fw-bold text-gray-800">${esc(row.product_name || '-')}</div>
                        ${row.sku ? `<small class="text-muted">SKU: ${esc(row.sku)}</small>` : ''}
                    </td>
                    <td class="text-end">${fmtNum(row.ordered_quantity, 0)}</td>
                    <td class="text-end">${fmtNum(row.received_quantity, 0)}</td>
                    <td class="text-end fw-bold text-danger">${fmtNum(row.consumed_quantity, 2)}</td>
                    <td class="text-end">${fmtMoney(row.unit_cost)}</td>
                    <td class="text-end fw-bold">${fmtMoney(row.subtotal)}</td>
                    <td>${chips || '<span class="text-muted">-</span>'}</td>
                </tr>`;
        }).join('');

        return `
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>PO Number</th><th>Supplier</th><th>Product</th>
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
            </div>`;
    }

    // ── Batch Logs ──────────────────────────────────────────────
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
            const change = Number(log.quantity_change || 0);
            let source = '-';
            if (log.type === 'produced' && log.production_order_id) {
                source = `<span class="badge badge-light-info"><i class="ki-duotone ki-industry fs-5 me-1"></i>Production</span>`;
            } else if (log.purchase_order_number) {
                source = `<span class="badge badge-light-success"><i class="ki-duotone ki-shop fs-5 me-1"></i>${esc(log.purchase_order_number)}</span>`;
            } else if (log.production_order_id) {
                source = `<span class="badge badge-light-secondary">Production #${log.production_order_id}</span>`;
            }
            return `
                <tr>
                    <td><span class="badge badge-light-dark">${esc(log.batch_number)}</span></td>
                    <td>${esc(log.variant?.name || log.variant_name || 'N/A')}</td>
                    <td><span class="badge badge-light-${typeColors[log.type] || 'secondary'}">${esc(log.type)}</span></td>
                    <td class="text-end ${change >= 0 ? 'text-success' : 'text-danger'} fw-bold">
                        ${change >= 0 ? '+' : ''}${fmtNum(change, 2)}
                    </td>
                    <td class="text-end">${fmtNum(log.quantity_before, 2)}</td>
                    <td class="text-end">${fmtNum(log.quantity_after, 2)}</td>
                    <td class="text-end">${fmtMoney(log.unit_cost)}</td>
                    <td class="text-end fw-bold">${fmtMoney(log.total_cost)}</td>
                    <td>${source}</td>
                    <td class="text-muted fs-7">${esc(log.event_date || '-')}</td>
                </tr>`;
        }).join('');

        return `
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Batch Number</th><th>Variant</th><th>Type</th>
                            <th class="text-end">Change</th>
                            <th class="text-end">Before</th>
                            <th class="text-end">After</th>
                            <th class="text-end">Unit Cost</th>
                            <th class="text-end">Total Cost</th>
                            <th>Source</th><th>Date</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>`;
    }

    // ── Inventory Logs ──────────────────────────────────────────
    function renderInventoryLogs(logs) {
        if (!logs || logs.length === 0) {
            return `<div class="alert alert-light-info">No inventory logs found.</div>`;
        }
        const rows = logs.map(log => {
            const change = Number(log.quantity_change || 0);
            return `
                <tr>
                    <td>${esc(log.variant?.name || 'N/A')}</td>
                    <td class="text-end ${change >= 0 ? 'text-success' : 'text-danger'} fw-bold">
                        ${change >= 0 ? '+' : ''}${fmtNum(change, 2)}
                    </td>
                    <td class="text-end">${fmtNum(log.quantity_before, 2)}</td>
                    <td class="text-end">${fmtNum(log.quantity_after, 2)}</td>
                    <td><span class="badge badge-light-info">${esc(log.reason || '-')}</span></td>
                    <td class="text-muted fs-7">${esc(log.created_at || '-')}</td>
                </tr>`;
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
                            <th>Reason</th><th>Date</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>`;
    }

    // ── Inputs ──────────────────────────────────────────────────
    function renderInputs(order) {
        if (!order.inputs || order.inputs.length === 0) {
            return `<div class="alert alert-light-info">No inputs recorded.</div>`;
        }
        const qualityColors = { pending: 'secondary', accepted: 'success', rejected: 'danger' };
        const rows = order.inputs.map(input => {
            const yieldPct = Number(input.planned_quantity) > 0
                ? (Number(input.actual_quantity) / Number(input.planned_quantity) * 100) : 0;
            return `
                <tr>
                    <td>${esc(input.product_variant?.name || 'N/A')}</td>
                    <td class="text-end">${fmtNum(input.planned_quantity, 2)}</td>
                    <td class="text-end">${fmtNum(input.actual_quantity, 2)}</td>
                    <td class="text-end">${fmtNum(input.waste_quantity, 2)}</td>
                    <td class="text-end">${yieldPct.toFixed(1)}%</td>
                    <td class="text-end">${fmtMoney(input.actual_cost)}</td>
                    <td><span class="badge badge-light-${qualityColors[input.quality_status] || 'secondary'}">${esc(input.quality_status || '-')}</span></td>
                </tr>`;
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
            </div>`;
    }

    // ── Outputs (uses FROZEN snapshot columns) ─────────────────
    function renderOutputs(order) {
        if (!order.outputs || order.outputs.length === 0) {
            return `<div class="alert alert-light-info">No outputs recorded.</div>`;
        }
        const qualityColors = { pending: 'secondary', approved: 'success', rejected: 'danger' };

        const rows = order.outputs.map(o => {
            const variant = o.product_variant || {};
            const actual  = Number(o.actual_quantity || 0);

            // ★ FROZEN columns — no more variant.selling_price
            const unitCost = Number(o.frozen_unit_cost    ?? o.production_cost ?? 0);
            const totalCost= Number(o.frozen_total_cost   ?? o.total_cost      ?? 0);
            const unitSell = Number(o.frozen_selling_price?? o.selling_price   ?? 0);

            const sellValue  = Number(o.frozen_selling_value ?? (actual * unitSell));
            const profit     = Number(o.frozen_profit        ?? (sellValue - totalCost));
            const marginPct  = Number(o.frozen_profit_margin ?? (sellValue > 0 ? (profit / sellValue) * 100 : 0));
            const profitColor = profit >= 0 ? 'success' : 'danger';

            const yieldPct = Number(o.planned_quantity) > 0
                ? (actual / Number(o.planned_quantity) * 100) : 0;

            const stratColor = o.inventory_strategy === 'batch' ? 'info'
                             : o.inventory_strategy === 'serial' ? 'warning' : 'primary';

            return `
                <tr>
                    <td>${esc(variant.name || 'N/A')}</td>
                    <td><span class="badge badge-light-${stratColor}">${esc(o.inventory_strategy)}</span></td>
                    <td class="text-end">${fmtNum(o.planned_quantity, 2)}</td>
                    <td class="text-end text-success fw-bold">${fmtNum(actual, 2)}</td>
                    <td class="text-end text-danger">${fmtNum(o.defective_quantity, 2)}</td>
                    <td class="text-end">${yieldPct.toFixed(1)}%</td>
                    <td class="text-end">${fmtMoney(unitCost)}</td>
                    <td class="text-end fw-bold">${fmtMoney(totalCost)}</td>
                    <td class="text-end">${fmtMoney(unitSell)}</td>
                    <td class="text-end">${fmtMoney(sellValue)}</td>
                    <td class="text-end fw-bold text-${profitColor}">
                        ${fmtMoney(profit)}
                        <small class="d-block text-muted fs-8">${marginPct.toFixed(1)}%</small>
                    </td>
                    <td>${esc(o.batch_number || '-')}</td>
                    <td><span class="badge badge-light-${qualityColors[o.quality_status] || 'secondary'}">${esc(o.quality_status || '-')}</span></td>
                </tr>`;
        }).join('');

        const totalCost   = order.outputs.reduce(
            (s, o) => s + Number(o.frozen_total_cost   ?? o.total_cost      ?? 0), 0);
        const totalSell   = order.outputs.reduce(
            (s, o) => s + Number(o.frozen_selling_value?? 0), 0);
        const totalProfit = order.outputs.reduce(
            (s, o) => s + Number(o.frozen_profit        ?? 0), 0);
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
                            <th class="text-end">Packaging &amp; Milling Cost <small class="d-block text-muted fw-normal fs-8">per unit</small></th>
                            <th class="text-end">Total Cost</th>
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
                            <td class="text-end text-muted">—</td>
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
            </div>`;
    }

    // ── Activity Story ──────────────────────────────────────────
    function renderStory(data) {
        const events = [];

        (data.batch_logs || []).forEach(log => {
            const narrative = log.metadata?.narrative || log.notes
                || (log.variant?.name + ' — ' + log.type + ' ' + log.quantity_change);
            events.push({ when: log.event_date || log.created_at, category: 'Batch',     color: 'primary',   narrative });
        });
        (data.inventory_logs || []).forEach(log => {
            const narrative = log.notes || log.metadata?.narrative
                || (log.variant?.name + ' — ' + log.reason);
            events.push({ when: log.created_at, category: 'Inventory', color: 'warning',   narrative });
        });
        (data.inventory_adjustments || []).forEach(log => {
            events.push({ when: log.created_at, category: 'Adjustment', color: 'info',     narrative: log.notes || log.reason });
        });
        (data.inventory_transactions || []).forEach(log => {
            events.push({ when: log.created_at, category: 'Transaction', color: 'secondary', narrative: log.notes || (log.type + ' ' + log.quantity) });
        });
        (data.payment_transactions || []).forEach(tx => {
            events.push({ when: tx.created_at, category: 'Payment', color: 'success',
                narrative: tx.description || tx.notes || (tx.transaction_type + ' ' + tx.amount) });
        });
        if (data.order_notes) {
            events.push({ when: data.order.created_at, category: 'Order', color: 'dark', narrative: data.order_notes });
        }

        events.sort((a, b) => new Date(a.when) - new Date(b.when));

        if (events.length === 0) {
            return `<div class="alert alert-light-info">No narrative events recorded.</div>`;
        }

        const rows = events.map(e => `
            <div class="d-flex align-items-start mb-3">
                <div class="me-3"><span class="badge badge-light-${e.color} fw-bold">${esc(e.category)}</span></div>
                <div class="flex-grow-1">
                    <div class="text-gray-800">${esc(e.narrative)}</div>
                    <div class="text-muted fs-8 mt-1">${esc(e.when || '')}</div>
                </div>
            </div>`).join('');

        return `<div class="border rounded p-4" style="max-height: 400px; overflow-y: auto; background: #fafafa;">${rows}</div>`;
    }

    // ── Main body ───────────────────────────────────────────────
    function renderBody(payload) {
        const order   = payload.order;
        const metrics = payload.metrics;

        const revenue    = Number(metrics.total_revenue || 0);
        const poCost     = Number(metrics.po_cost || 0);
        const overhd     = Number(metrics.overhead_cost || 0);
        const netProf    = Number(metrics.net_profit ?? (revenue - poCost - overhd));
        const margin     = Number(metrics.net_margin ?? (revenue > 0 ? (netProf / revenue) * 100 : 0));
        const profitColor= netProf >= 0 ? 'success' : 'danger';

        let html = '';

        // Header
        html += `
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold mb-1">${esc(order.production_number)}</h4>
                    <div class="text-muted fs-7">${esc(order.location?.name || '-')} · ${esc(order.created_at || '')}</div>
                </div>
                <div>
                    <span class="badge badge-light-${esc(order.status_badge || 'secondary')} fs-6 py-2 px-4">
                        ${esc(order.status_label || order.status)}
                    </span>
                    ${order.payment_method_id ? `
                        <span class="badge badge-light-success fs-6 py-2 px-4 ms-2">
                            <i class="ki-duotone ki-wallet fs-2 me-1"></i>Paid
                        </span>` : ''}
                </div>
            </div>`;

        // Metric row (top strip)
        html += `
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <div class="card card-flush bg-light-primary border border-primary border-dashed">
                        <div class="card-body text-center">
                            <span class="text-muted fs-8">Duration</span>
                            <div class="fs-4 fw-bold">${fmtNum(metrics.duration_hours, 2)}h</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card card-flush bg-light-success border border-success border-dashed">
                        <div class="card-body text-center">
                            <span class="text-muted fs-8">True Yield (kg/kg)</span>
                            <div class="fs-4 fw-bold text-success">${fmtNum(metrics.input_yield, 1)}%</div>
                            <div class="text-muted fs-8">${fmtNum(metrics.total_output_kg, 2)} / ${fmtNum(metrics.total_input_kg, 2)} kg</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card card-flush bg-light-info border border-info border-dashed">
                        <div class="card-body text-center">
                            <span class="text-muted fs-8">Cost Efficiency</span>
                            <div class="fs-4 fw-bold text-info">${fmtNum(metrics.cost_efficiency, 1)}%</div>
                            <div class="text-muted fs-8">revenue / total cost</div>
                        </div>
                    </div>
                </div>
            </div>`;

        // Profit band (4 cards)
        html += `
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-warning border border-warning border-dashed">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted fw-semibold fs-8">Raw Materials (PO Cost)</span>
                                <i class="ki-duotone ki-shop fs-2x text-warning opacity-50"></i>
                            </div>
                            <div class="fs-4 fw-bold text-warning">${fmtMoney(poCost)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-danger border border-danger border-dashed">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted fw-semibold fs-8">Production Overhead</span>
                                <i class="ki-duotone ki-wallet fs-2x text-danger opacity-50"></i>
                            </div>
                            <div class="fs-4 fw-bold text-danger">${fmtMoney(overhd)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-primary border border-primary border-dashed">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted fw-semibold fs-8">Output Selling Value</span>
                                <i class="ki-duotone ki-dollar fs-2x text-primary opacity-50"></i>
                            </div>
                            <div class="fs-4 fw-bold text-primary">${fmtMoney(revenue)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-${profitColor} border border-${profitColor} border-dashed">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted fw-semibold fs-8" title="Revenue − Raw Materials − Overhead">Net Production Profit</span>
                                <i class="ki-duotone ki-chart-line-up fs-2x text-${profitColor} opacity-50"></i>
                            </div>
                            <div class="fs-4 fw-bold text-${profitColor}">${fmtMoney(netProf)}</div>
                            <div class="text-muted fs-8">margin ${fmtNum(margin, 1)}%</div>
                        </div>
                    </div>
                </div>
            </div>`;

        // ─── PO Items ──────────────────────────────────────────
        html += `<h6 class="fw-bold mb-3 mt-4"><i class="ki-duotone ki-shop fs-3 me-2 text-success"></i>Purchase Order Items — Raw Materials Sourced From</h6>`;
        html += renderPurchaseOrderItems(payload.purchase_order_items);

        // ─── Batch Logs ────────────────────────────────────────
        html += `<h6 class="fw-bold mb-3 mt-4"><i class="ki-duotone ki-archive fs-3 me-2 text-primary"></i>Batch Logs — Batches Consumed and Remaining Balances</h6>`;
        html += renderBatchLogs(payload.batch_logs);

        // ─── Inputs ────────────────────────────────────────────
        html += `<h6 class="fw-bold mb-3 mt-4"><i class="ki-duotone ki-box-arrow-in-down fs-3 me-2 text-danger"></i>Inputs</h6>`;
        html += renderInputs(order);

        // ─── Outputs ───────────────────────────────────────────
        html += `<h6 class="fw-bold mb-3 mt-4"><i class="ki-duotone ki-box-arrow-out fs-3 me-2 text-success"></i>Outputs</h6>`;
        html += renderOutputs(order);

        // ─── Output Breakdown (weight-based) ───────────────────
        if (metrics.output_breakdown && metrics.output_breakdown.length > 0) {
            const { rows, inputKg } = computeOutputBreakdown(metrics);

            html += `<h6 class="fw-bold mb-3 mt-4">
                <i class="ki-duotone ki-chart-pie fs-3 me-2 text-success"></i>
                Output Breakdown (Weight-Based)
                <span class="text-muted fs-7 ms-2">— click any row to include / exclude it from the totals</span>
            </h6>`;
            html += renderOutputBreakdown(rows, inputKg);

            window.__drOutputRows    = rows;
            window.__drOutputInputKg = inputKg;
        }

        // ─── Inventory Logs ────────────────────────────────────
        html += `<h6 class="fw-bold mb-3 mt-4"><i class="ki-duotone ki-chart-simple fs-3 me-2 text-warning"></i>Inventory Logs</h6>`;
        html += renderInventoryLogs(payload.inventory_logs);

        // ─── Activity Story ────────────────────────────────────
        html += `<h6 class="fw-bold mb-3 mt-4"><i class="ki-duotone ki-time fs-3 me-2 text-info"></i>Activity Story</h6>`;
        html += renderStory(payload);

        return html;
    }

    // ── Toggle handlers (weight-based breakdown) ───────────────
    function toggleOutputRow(rowId) {
        const rows = window.__drOutputRows || [];
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
        const rows = window.__drOutputRows || [];
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
        const rows     = window.__drOutputRows || [];
        const inputKg  = Number(window.__drOutputInputKg);
        const inputSane= Number.isFinite(inputKg) && inputKg > 0;

        const included  = rows.filter(r => r.included);
        const totalKg   = included.reduce((s, r) => s + r.total_kg, 0);
        const totalRev  = included.reduce((s, r) => s + r.revenue,  0);

        rows.forEach(r => {
            const shareEl = document.querySelector(`#${r.id} .output-row-share`);
            if (shareEl) {
                shareEl.textContent = inputSane
                    ? ((r.total_kg / inputKg) * 100).toFixed(2) + '%' : '—';
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
        setText('drOutTotalKg',       fmtNum(totalKg, 2) + ' kg');
        setText('drOutTotalShare',    inputSane ? fmtNum(totalShare, 2) + '%' : '—');
        setText('drOutTotalRevenue',  fmtMoney(totalRev));
        setText('drOutLossKg',        inputSane ? fmtNum(lossKg, 2) + ' kg' : '—');
        setText('drOutLossPct',       inputSane ? fmtNum(lossPct, 2) + '%' : '—');
    }

    // First render of totals after the drawer content mounts
    function postRender() {
        setTimeout(recomputeOutputTotals, 0);
    }

    // Wrap render() so postRender fires after content is in the DOM
    const _render = render;
    render = function (payload) {
        _render(payload);
        postRender();
    };

    // Reset on close so reopening is fresh
    document.addEventListener('hidden.bs.offcanvas', function (e) {
        if (e.target.id !== OFF_CANVAS_ID) return;
        window.__drOutputRows    = [];
        window.__drOutputInputKg = null;
    });

    window.ReportDrawer = {
        open,
        close,
        toggleOutputRow,
        toggleAllOutputRows,
        recomputeOutputTotals,
    };
    return window.ReportDrawer;
})();
</script>