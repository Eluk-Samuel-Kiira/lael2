<script>
window.InvoicePayments = (function () {
    'use strict';

    const DATA_ROUTE   = window.IP_ROUTES.data;
    const DETAIL_ROUTE = window.IP_ROUTES.detail;
    const CURRENCY     = window.REPORT_CURRENCY || '';
    const BOOT         = window.IP_BOOTSTRAP || {};
    const I18N         = window.IP_I18N || {};

    const state = {
        startDate:    BOOT.startDate    || '',
        endDate:      BOOT.endDate      || '',
        locationId:   BOOT.locationId   || '',
        departmentId: BOOT.departmentId || '',
        paymentMethod:BOOT.paymentMethod|| '',
        recordedVia:  BOOT.recordedVia  || 'all',
        statusFilter: BOOT.statusFilter || 'all',
        perPage:      Number(BOOT.perPage || 25),
        page:         1,
    };

    let drawerBs = null;
    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('ipApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('ipClearBtn')?.addEventListener('click', resetFilters);
        $('ipExportBtn')?.addEventListener('click', exportExcel);
        $('ipPrintBtn')?.addEventListener('click', () => window.print());

        $('ipPaymentsBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.ip-row[data-payment-id]');
            if (!row) return;
            openDrawer(row.dataset.paymentId, row.dataset.invoiceNumber);
        });

        document.getElementById('ipDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('ipDrawerContent').innerHTML = '';
            $('ipDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate     = $('ipStartDate')?.value    || '';
        state.endDate       = $('ipEndDate')?.value      || '';
        state.locationId    = $('ipLocation')?.value     || '';
        state.departmentId  = $('ipDepartment')?.value   || '';
        state.paymentMethod = $('ipMethod')?.value       || '';
        state.recordedVia   = $('ipChannel')?.value      || 'all';
        state.statusFilter  = $('ipStatus')?.value       || 'all';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.departmentId = state.paymentMethod = '';
        state.recordedVia = 'all';
        state.statusFilter = 'all';
        state.page = 1;
        ['ipStartDate','ipEndDate'].forEach(id => { if ($(id)) $(id).value = ''; });
        if ($('ipChannel')) $('ipChannel').value = 'all';
        if ($('ipStatus'))  $('ipStatus').value = 'all';
        if (window.jQuery) {
            jQuery('#ipLocation').val(null).trigger('change.select2');
            jQuery('#ipDepartment').val(null).trigger('change.select2');
            jQuery('#ipMethod').val(null).trigger('change.select2');
        }
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)     params.set('start_date', state.startDate);
        if (state.endDate)       params.set('end_date', state.endDate);
        if (state.locationId)    params.set('location_id', state.locationId);
        if (state.departmentId)  params.set('department_id', state.departmentId);
        if (state.paymentMethod) params.set('payment_method_id', state.paymentMethod);
        if (state.recordedVia)   params.set('recorded_via', state.recordedVia);
        if (state.statusFilter)  params.set('status', state.statusFilter);

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
            console.error('[invoice-payments] load failed:', err);
            window.toastr?.error('Failed to load payments report.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('ipSkeleton')?.classList.toggle('d-none', !v);
        $('ipContent')?.classList.toggle('d-none', v);
    }

    function render(d) {
        if (!d || !d.summary || d.summary.total_payments === 0) {
            $('ipEmpty')?.classList.remove('d-none');
            $('ipKpiCards').innerHTML = '';
            ['ipMethodCard','ipChannelCard','ipTrendCard','ipTableCard']
                .forEach(id => $(id)?.classList.add('d-none'));
            return;
        }
        $('ipEmpty')?.classList.add('d-none');
        renderKpis(d.summary);
        renderByMethod(d.by_method);
        renderByChannel(d.by_channel);
        renderTrend(d.daily_trend);
        renderTable(d.payments, d.pagination, d.page_totals);
    }

    // ─── KPI cards ──────────────────────────────────────────
    function renderKpis(s) {
        $('ipKpiCards').innerHTML = `
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-success h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-success">
                            <i class="bi bi-currency-dollar fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-3 fw-bold text-success">${money(s.completed_amount)}</div>
                            <div class="text-muted fs-6">${esc(I18N.totalCollected)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">${fmtNum(s.completed_count, 0)} ${esc(I18N.completedPayments)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-primary h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-primary">
                            <i class="bi bi-basket fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-3 fw-bold text-primary">${fmtNum(s.total_payments, 0)}</div>
                            <div class="text-muted fs-6">${esc(I18N.totalPayments)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">${esc(I18N.avg)}: ${money(s.average_payment)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-warning h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-warning">
                            <i class="bi bi-arrow-up fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-3 fw-bold text-warning">${money(s.largest_payment)}</div>
                            <div class="text-muted fs-6">${esc(I18N.largestPayment)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">${esc(I18N.avg)}: ${money(s.average_payment)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-danger h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-danger">
                            <i class="bi bi-x-circle fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-3 fw-bold text-danger">${fmtNum(s.failed_count + s.refunded_count, 0)}</div>
                            <div class="text-muted fs-6">${esc(I18N.failedRefunded)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">${money(s.failed_amount + s.refunded_amount)}</div>
                </div></div>
            </div>
        `;
    }

    // ─── By method ─────────────────────────────────────────
    function renderByMethod(rows) {
        if (!rows || rows.length === 0) { $('ipMethodCard')?.classList.add('d-none'); return; }
        $('ipMethodCard')?.classList.remove('d-none');

        $('ipMethodBody').innerHTML = rows.map(r => `
            <tr>
                <td class="ps-4">
                    <span class="fw-bold">${esc(r.method)}</span>
                    <br><small class="text-muted text-capitalize">${esc(r.type)}</small>
                </td>
                <td class="text-center">${fmtNum(r.count, 0)}</td>
                <td class="text-end fw-bold text-success">${money(r.total_amount)}</td>
                <td class="pe-4 text-end">${money(r.average_amount)}</td>
            </tr>
        `).join('');

        $('ipMethodFoot').innerHTML = `
            <tr>
                <th class="ps-4">${esc(I18N.totalAverage)}</th>
                <th class="text-center">${fmtNum(rows.reduce((s, r) => s + Number(r.count || 0), 0), 0)}</th>
                <th class="text-end fw-bold">${money(rows.reduce((s, r) => s + Number(r.total_amount || 0), 0))}</th>
                <th class="pe-4"></th>
            </tr>
        `;
    }

    // ─── By channel ─────────────────────────────────────────
    function renderByChannel(rows) {
        if (!rows || rows.length === 0) { $('ipChannelCard')?.classList.add('d-none'); return; }
        $('ipChannelCard')?.classList.remove('d-none');

        const total = rows.reduce((s, r) => s + Number(r.total_amount || 0), 0);
        const channelColors = { pos: 'primary', manual: 'warning', webhook: 'info' };

        $('ipChannelBody').innerHTML = rows.map(r => {
            const pct = total > 0 ? (r.total_amount / total) * 100 : 0;
            const color = channelColors[r.channel] || 'secondary';
            return `
                <tr>
                    <td class="ps-4"><span class="badge badge-light-${color} fs-7 py-2 px-3">${esc(capitalize(r.channel))}</span></td>
                    <td class="text-center">${fmtNum(r.count, 0)}</td>
                    <td class="text-end fw-bold text-success">${money(r.total_amount)}</td>
                    <td class="pe-4 text-end"><span class="badge badge-light-secondary">${fmtNum(pct, 1)}%</span></td>
                </tr>
            `;
        }).join('');

        $('ipChannelFoot').innerHTML = `
            <tr>
                <th class="ps-4">${esc(I18N.totalAverage)}</th>
                <th class="text-center">${fmtNum(rows.reduce((s, r) => s + Number(r.count || 0), 0), 0)}</th>
                <th class="text-end fw-bold">${money(total)}</th>
                <th class="pe-4 text-end">100%</th>
            </tr>
        `;
    }

    // ─── Daily trend ────────────────────────────────────────
    function renderTrend(rows) {
        if (!rows || rows.length === 0) { $('ipTrendCard')?.classList.add('d-none'); return; }
        $('ipTrendCard')?.classList.remove('d-none');

        $('ipTrendBody').innerHTML = rows.map(r => `
            <tr>
                <td class="ps-4 fw-bold">${esc(formatDate(r.date))}</td>
                <td class="text-center">${fmtNum(r.payment_count, 0)}</td>
                <td class="pe-4 text-end fw-semibold text-success">${money(r.total_amount)}</td>
            </tr>
        `).join('');

        $('ipTrendFoot').innerHTML = `
            <tr>
                <th class="ps-4">${esc(I18N.totalAverage)}</th>
                <th class="text-center fw-bold">${fmtNum(rows.reduce((s, r) => s + Number(r.payment_count || 0), 0), 0)}</th>
                <th class="pe-4 text-end fw-bold">${money(rows.reduce((s, r) => s + Number(r.total_amount || 0), 0))}</th>
            </tr>
        `;
    }

    // ─── Payments table ────────────────────────────────────
    function renderTable(rows, pagination, pageTotals) {
        if (!rows || rows.length === 0) { $('ipTableCard')?.classList.add('d-none'); return; }
        $('ipTableCard')?.classList.remove('d-none');
        $('ipTableBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.payments}`;

        const channelColors = { pos: 'primary', manual: 'warning', webhook: 'info' };

        $('ipPaymentsBody').innerHTML = rows.map(p => {
            const statusCls = p.status === 'completed' ? 'success'
                            : p.status === 'failed'    ? 'danger'
                            : p.status === 'refunded'  ? 'secondary'
                            : p.status === 'pending'   ? 'warning' : 'info';
            const chanColor = channelColors[p.recorded_via] || 'secondary';

            return `
                <tr class="ip-row" data-payment-id="${p.id}" data-invoice-number="${esc(p.invoice_number)}">
                    <td class="ps-4">
                        <span class="fw-bold text-primary">${esc(p.invoice_number)}</span>
                        ${p.order_number !== 'N/A' ? `<br><small class="text-muted">${esc(p.order_number)}</small>` : ''}
                    </td>
                    <td><span class="fw-semibold">${esc(p.customer_name)}</span></td>
                    <td>
                        <span class="text-muted">${esc(p.processed_at_human || '—')}</span>
                        ${p.processed_at_time ? `<br><small class="text-muted">${esc(p.processed_at_time)}</small>` : ''}
                    </td>
                    <td>
                        <span class="fw-semibold">${esc(p.payment_method)}</span>
                        <br><small class="text-muted text-capitalize">${esc(p.payment_method_type)}</small>
                    </td>
                    <td><span class="badge badge-light-${chanColor} fs-8">${esc(capitalize(p.recorded_via))}</span></td>
                    <td><span class="text-muted fs-7">${esc(p.reference)}</span></td>
                    <td class="text-end fw-bold text-success">${money(p.amount)}</td>
                    <td><span class="badge badge-light-${statusCls} fs-7 py-2 px-3">${esc(p.status_label || capitalize(p.status))}</span></td>
                    <td class="pe-4"><span class="text-muted fs-7">${esc(p.processed_by)}</span></td>
                </tr>
            `;
        }).join('');

        $('ipPaymentsFoot').innerHTML = `
            <tr>
                <th colspan="6" class="ps-4">${esc(I18N.totalAverage)}</th>
                <th class="text-end fw-bold text-success">${money(pageTotals.amount)}</th>
                <th colspan="2"></th>
            </tr>
        `;

        renderPagination(pagination);
    }

    // ─── Pagination ────────────────────────────────────────
    function renderPagination(p) {
        const info = $('ipPaginationInfo');
        const ul   = $('ipPagination');
        const wrap = $('ipPaginationWrap');
        if (!ul || !info) return;
        if (p.total <= p.per_page) { wrap?.classList.add('d-none'); return; }
        wrap?.classList.remove('d-none');
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

    // ─── Drawer ────────────────────────────────────────────
    function getDrawer() {
        const el = document.getElementById('ipDrawer');
        if (!el) return null;
        drawerBs = drawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return drawerBs;
    }

    async function openDrawer(paymentId, invoiceNumber) {
        const inst = getDrawer();
        if (!inst) return;

        $('ipDrawerSubtitle').textContent = invoiceNumber;
        $('ipDrawerSkeleton')?.classList.remove('d-none');
        const content = $('ipDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        inst.show();

        try {
            const url = DETAIL_ROUTE.replace('__ID__', paymentId);
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            renderDrawer(json.data);
        } catch (err) {
            console.error('[ip-drawer] failed:', err);
            $('ipDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderDrawer(d) {
        $('ipDrawerSkeleton')?.classList.add('d-none');
        const content = $('ipDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        const chanColors = { pos: 'primary', manual: 'warning', webhook: 'info' };
        const chanColor  = chanColors[d.recorded_via] || 'secondary';

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">${money(d.amount)}</h4>
                    <div class="text-muted fs-7">${esc(d.invoice?.invoice_number || '—')} · ${esc(d.payment_date_human || '')}</div>
                </div>
                <span class="badge badge-light-${esc(d.status_color)} fs-6 py-2 px-3">${esc(d.status_label)}</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6"><div class="card card-flush bg-light-success"><div class="card-body py-3">
                    <span class="text-muted fs-8">${esc(I18N.amount)}</span>
                    <div class="fs-5 fw-bold text-success">${money(d.amount)}</div>
                </div></div></div>
                <div class="col-md-6"><div class="card card-flush bg-light-${chanColor}"><div class="card-body py-3">
                    <span class="text-muted fs-8">${esc(I18N.recordedVia)}</span>
                    <div class="fs-5 fw-bold text-${chanColor}">${esc(capitalize(d.recorded_via))}</div>
                </div></div></div>
            </div>

            <h6 class="fw-bold mb-3"><i class="bi bi-credit-card me-2 text-primary"></i>${esc(I18N.paymentDetails)}</h6>
            <table class="table table-sm table-bordered mb-4">
                <tbody>
                    <tr><th class="w-50">${esc(I18N.paymentMethod)}</th><td>
                        <span class="badge badge-light-info">${esc(d.payment_method?.name || '—')}</span>
                        <small class="text-muted ms-2 text-capitalize">${esc(d.payment_method?.type || '')}</small>
                    </td></tr>
                    <tr><th>${esc(I18N.currentBalance)}</th><td>${money(d.payment_method?.current_balance || 0)}</td></tr>
                    <tr><th>${esc(I18N.type)}</th><td>${esc(d.type_label || '—')}</td></tr>
                    <tr><th>${esc(I18N.status)}</th><td><span class="badge badge-light-${esc(d.status_color)}">${esc(d.status_label)}</span></td></tr>
                    <tr><th>${esc(I18N.transactionId)}</th><td>${esc(d.transaction_id || '—')}</td></tr>
                    <tr><th>${esc(I18N.referenceNumber)}</th><td>${esc(d.reference_number || '—')}</td></tr>
                    <tr><th>${esc(I18N.paymentDate)}</th><td>${esc(d.payment_date_human || '—')}</td></tr>
                    ${d.verified_at ? `<tr><th>${esc(I18N.verifiedAt)}</th><td>${esc(d.verified_at)}</td></tr>` : ''}
                    ${d.refunded_at ? `<tr><th>${esc(I18N.refundedAt)}</th><td>${esc(d.refunded_at)}</td></tr>` : ''}
                </tbody>
            </table>

            <h6 class="fw-bold mb-3"><i class="bi bi-person-badge me-2 text-primary"></i>${esc(I18N.processedBy)}</h6>
            <div class="border rounded p-3 mb-4">
                <div class="fw-bold">${esc(d.processed_by?.name || 'System')}</div>
                ${d.processed_by?.email ? `<small class="text-muted">${esc(d.processed_by.email)}</small>` : ''}
            </div>

            ${d.invoice ? `
                <h6 class="fw-bold mb-3"><i class="bi bi-receipt me-2 text-primary"></i>${esc(I18N.invoice)}</h6>
                <table class="table table-sm table-bordered mb-4">
                    <tbody>
                        <tr><th class="w-50">${esc(I18N.invoiceNumber)}</th><td>${esc(d.invoice.invoice_number || '—')}</td></tr>
                        <tr><th>${esc(I18N.invoiceIssueDate)}</th><td>${esc(d.invoice.issue_date || '—')}</td></tr>
                        <tr><th>${esc(I18N.invoiceDueDate)}</th><td>${esc(d.invoice.due_date || '—')}</td></tr>
                        <tr><th>${esc(I18N.invoiceTotal)}</th><td>${money(d.invoice.total || 0)}</td></tr>
                        <tr><th>${esc(I18N.invoicePaid)}</th><td class="text-success">${money(d.invoice.amount_paid || 0)}</td></tr>
                        <tr><th>${esc(I18N.invoiceBalance)}</th><td class="text-danger">${money(d.invoice.balance_due || 0)}</td></tr>
                        ${d.invoice.order_number ? `<tr><th>${esc(I18N.orderNumber)}</th><td>${esc(d.invoice.order_number)}</td></tr>` : ''}
                    </tbody>
                </table>
            ` : ''}

            ${d.notes ? `
                <h6 class="fw-bold mt-4 mb-3"><i class="bi bi-chat-left-text me-2 text-primary"></i>${esc(I18N.notes)}</h6>
                <div class="border rounded p-3 text-gray-800 ip-notes">${esc(d.notes)}</div>
            ` : ''}
        `;
    }

    // ─── Export ────────────────────────────────────────────
    function exportExcel() {
        const table = $('ipPaymentsTable');
        if (!table || typeof XLSX === 'undefined') {
            window.toastr?.error('Excel export unavailable.');
            return;
        }
        try {
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, 'Invoice Payments');
            XLSX.writeFile(wb, 'invoice_payments.xlsx');
        } catch (e) {
            window.toastr?.error('Export failed: ' + e.message);
        }
    }

    // ─── Helpers ───────────────────────────────────────────
    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t); return d.innerHTML;
    }
    function capitalize(s) { return String(s || '').charAt(0).toUpperCase() + String(s || '').slice(1); }
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