<script>
window.ReportDrawer = (function () {
    'use strict';

    const CURRENCY = window.REPORT_CURRENCY;
    let bs = null;

    function getBs() {
        const el = document.getElementById('reportDrawer');
        if (!el) return null;
        bs = bs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return bs;
    }

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

    function render(payload) {
        const order   = payload.order;
        const metrics = payload.metrics;

        // Safely update subtitle if it exists
        const subtitleEl = document.getElementById('reportDrawerSubtitle');
        if (subtitleEl) {
            subtitleEl.textContent = `${order.production_number} · ${order.location?.name || ''}`;
        }

        document.getElementById('reportDrawerSkeleton')?.classList.add('d-none');
        
        const body = document.getElementById('reportDrawerContent');
        if (!body) {
            console.error('[report-drawer] #reportDrawerContent not found in DOM');
            return;
        }
        
        body.classList.remove('d-none');
        body.innerHTML = renderBody(order, metrics, payload);
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
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function renderBody(order, metrics, payload) {
        const revenue = Number(metrics.total_revenue || 0);
        const poCost  = Number(metrics.po_cost || 0);
        const overhd  = Number(metrics.overhead_cost || 0);
        const netProf = revenue - poCost - overhd;
        const margin  = revenue > 0 ? (netProf / revenue) * 100 : 0;
        const profitColor = netProf >= 0 ? 'success' : 'danger';

        let html = '';

        // Header
        html += `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">${esc(order.production_number)}</h4>
                    <div class="text-muted fs-7">${esc(order.location?.name || '')} · ${esc(order.created_at)}</div>
                </div>
                <div>
                    <span class="badge badge-light-${esc(order.status_badge || 'secondary')} fs-6 py-2 px-4">
                        ${esc(order.status_label || order.status)}
                    </span>
                </div>
            </div>
        `;

        // Metrics row
        html += `
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-primary border border-primary border-dashed">
                        <div class="card-body text-center">
                            <span class="text-muted fs-8">Duration</span>
                            <div class="fs-4 fw-bold">${fmtNum(metrics.duration_hours, 2)}h</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-success border border-success border-dashed">
                        <div class="card-body text-center">
                            <span class="text-muted fs-8">True Yield</span>
                            <div class="fs-4 fw-bold text-success">${fmtNum(metrics.input_yield, 1)}%</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-info border border-info border-dashed">
                        <div class="card-body text-center">
                            <span class="text-muted fs-8">Cost Efficiency</span>
                            <div class="fs-4 fw-bold text-info">${fmtNum(metrics.cost_efficiency, 1)}%</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-flush bg-light-${profitColor} border border-${profitColor} border-dashed">
                        <div class="card-body text-center">
                            <span class="text-muted fs-8">Net Profit</span>
                            <div class="fs-4 fw-bold text-${profitColor}">${fmtMoney(netProf)}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Financial breakdown
        html += `
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card card-flush bg-light-warning border border-warning border-dashed">
                        <div class="card-body">
                            <div class="text-muted fw-semibold fs-8 mb-1">Raw Materials (PO Cost)</div>
                            <div class="fs-4 fw-bold text-warning">${fmtMoney(poCost)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-flush bg-light-danger border border-danger border-dashed">
                        <div class="card-body">
                            <div class="text-muted fw-semibold fs-8 mb-1">Production Overhead</div>
                            <div class="fs-4 fw-bold text-danger">${fmtMoney(overhd)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-flush bg-light-primary border border-primary border-dashed">
                        <div class="card-body">
                            <div class="text-muted fw-semibold fs-8 mb-1">Output Selling Value</div>
                            <div class="fs-4 fw-bold text-primary">${fmtMoney(revenue)}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-light-${profitColor}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold text-${profitColor} fs-5">
                            Net Production Profit: ${fmtMoney(netProf)}
                        </div>
                        <div class="text-muted fs-8">
                            revenue − raw materials − overhead · margin ${fmtNum(margin, 1)}%
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Outputs table
        html += `
            <h6 class="fw-bold mb-3 mt-4">
                <i class="ki-duotone ki-box-arrow-out fs-3 me-2 text-success"></i>
                Outputs
            </h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Variant</th>
                            <th class="text-end">Planned</th>
                            <th class="text-end">Actual</th>
                            <th class="text-end">Defective</th>
                            <th class="text-end">Unit Cost</th>
                            <th class="text-end">Selling Price</th>
                            <th class="text-end">Profit</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        (order.outputs || []).forEach(o => {
            const qty      = Number(o.actual_quantity || 0);
            const unitCost = Number(o.unit_cost || 0);
            const sell     = Number(o.product_variant?.selling_price || 0);
            const rowProf  = (sell - unitCost) * qty;
            const profitCl = rowProf >= 0 ? 'success' : 'danger';

            html += `
                <tr>
                    <td>${esc(o.product_variant?.name || 'N/A')}</td>
                    <td class="text-end">${fmtNum(o.planned_quantity, 2)}</td>
                    <td class="text-end">${fmtNum(qty, 2)}</td>
                    <td class="text-end text-danger">${fmtNum(o.defective_quantity, 2)}</td>
                    <td class="text-end">${fmtMoney(unitCost)}</td>
                    <td class="text-end">${fmtMoney(sell)}</td>
                    <td class="text-end fw-bold text-${profitCl}">${fmtMoney(rowProf)}</td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        // Activity Story
        if (payload.batch_logs && payload.batch_logs.length > 0) {
            html += `
                <h6 class="fw-bold mb-3 mt-4">
                    <i class="ki-duotone ki-time fs-3 me-2 text-info"></i>
                    Activity Story
                </h6>
                <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
            `;
            payload.batch_logs.forEach(log => {
                const narrative = log.metadata?.narrative || log.notes || (log.type + ' ' + log.quantity_change);
                html += `
                    <div class="d-flex align-items-start mb-2">
                        <span class="badge badge-light-primary me-2">${esc(log.type)}</span>
                        <div class="flex-grow-1 fs-7">${esc(narrative)}</div>
                    </div>
                `;
            });
            html += `</div>`;
        }

        return html;
    }

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML;
    }
    function fmtNum(n, d = 0) {
        return Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
    }
    function fmtMoney(n) {
        return CURRENCY + fmtNum(n, 2);
    }

    window.ReportDrawer = { open, close };
    return window.ReportDrawer;
})();
</script>