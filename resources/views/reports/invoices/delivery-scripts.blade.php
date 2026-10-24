<script>
window.InvoiceDelivery = (function () {
    'use strict';

    const DATA_ROUTE   = window.DL_ROUTES.data;
    const DETAIL_ROUTE = window.DL_ROUTES.detail;
    const CURRENCY     = window.REPORT_CURRENCY || '';
    const BOOT         = window.DL_BOOTSTRAP || {};
    const I18N         = window.DL_I18N || {};

    const state = {
        startDate:    BOOT.startDate    || '',
        endDate:      BOOT.endDate      || '',
        locationId:   BOOT.locationId   || '',
        departmentId: BOOT.departmentId || '',
        channel:      BOOT.channel      || 'all',
        sendStatus:   BOOT.sendStatus   || 'all',
        perPage:      Number(BOOT.perPage || 25),
        page:         1,
    };

    let drawerBs = null;
    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('dlApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('dlClearBtn')?.addEventListener('click', resetFilters);
        $('dlExportBtn')?.addEventListener('click', exportExcel);
        $('dlPrintBtn')?.addEventListener('click', () => window.print());

        $('dlSendsBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.dl-row[data-send-id]');
            if (!row) return;
            openDrawer(row.dataset.sendId, row.dataset.invoiceNumber);
        });

        document.getElementById('dlDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('dlDrawerContent').innerHTML = '';
            $('dlDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate    = $('dlStartDate')?.value    || '';
        state.endDate      = $('dlEndDate')?.value      || '';
        state.locationId   = $('dlLocation')?.value     || '';
        state.departmentId = $('dlDepartment')?.value   || '';
        state.channel      = $('dlChannel')?.value      || 'all';
        state.sendStatus   = $('dlStatus')?.value       || 'all';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.departmentId = '';
        state.channel = 'all';
        state.sendStatus = 'all';
        state.page = 1;
        ['dlStartDate','dlEndDate'].forEach(id => { if ($(id)) $(id).value = ''; });
        if ($('dlChannel')) $('dlChannel').value = 'all';
        if ($('dlStatus'))  $('dlStatus').value = 'all';
        if (window.jQuery) {
            jQuery('#dlLocation').val(null).trigger('change.select2');
            jQuery('#dlDepartment').val(null).trigger('change.select2');
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
        if (state.channel)      params.set('channel', state.channel);
        if (state.sendStatus)   params.set('send_status', state.sendStatus);

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
            console.error('[invoice-delivery] load failed:', err);
            window.toastr?.error('Failed to load delivery report.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('dlSkeleton')?.classList.toggle('d-none', !v);
        $('dlContent')?.classList.toggle('d-none', v);
    }

    function render(d) {
        if (!d || !d.summary || d.summary.total_sends === 0) {
            $('dlEmpty')?.classList.remove('d-none');
            $('dlKpiCards').innerHTML = '';
            ['dlByChannelCard','dlFailuresCard','dlTrendCard','dlTableCard']
                .forEach(id => $(id)?.classList.add('d-none'));
            return;
        }
        $('dlEmpty')?.classList.add('d-none');
        renderKpis(d.summary);
        renderByChannel(d.by_channel);
        renderFailures(d.failure_reasons);
        renderTrend(d.daily_trend);
        renderTable(d.sends, d.pagination);
    }

    // ─── KPI cards ──────────────────────────────────────────
    function renderKpis(s) {
        $('dlKpiCards').innerHTML = `
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-primary h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-primary">
                            <i class="bi bi-send fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-3 fw-bold text-primary">${fmtNum(s.total_sends, 0)}</div>
                            <div class="text-muted fs-6">${esc(I18N.totalSendAttempts)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">${fmtNum(s.unique_invoices, 0)} ${esc(I18N.uniqueInvoices)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-success h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-success">
                            <i class="bi bi-check-circle fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-3 fw-bold text-success">${fmtNum(s.delivery_rate, 1)}%</div>
                            <div class="text-muted fs-6">${esc(I18N.deliveryRate)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">${fmtNum(s.delivered_count, 0)} ${esc(I18N.delivered)} · ${fmtNum(s.sent_count, 0)} ${esc(I18N.sent)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-danger h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-danger">
                            <i class="bi bi-x-circle fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-3 fw-bold text-danger">${fmtNum(s.failure_rate, 1)}%</div>
                            <div class="text-muted fs-6">${esc(I18N.failureRate)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">${fmtNum(s.failed_count, 0)} ${esc(I18N.failed)} · ${fmtNum(s.bounced_count, 0)} ${esc(I18N.bounced)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-warning h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-warning">
                            <i class="bi bi-hourglass-split fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-3 fw-bold text-warning">${fmtNum(s.pending_count, 0)}</div>
                            <div class="text-muted fs-6">${esc(I18N.pendingDelivery)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">${esc(I18N.awaitingProvider)}</div>
                </div></div>
            </div>
        `;
    }

    // ─── By channel ────────────────────────────────────────
    function renderByChannel(rows) {
        if (!rows || rows.length === 0) { $('dlByChannelCard')?.classList.add('d-none'); return; }
        $('dlByChannelCard')?.classList.remove('d-none');

        const channelColors = { email: 'primary', sms: 'info', whatsapp: 'success', portal_link: 'warning', print: 'secondary' };

        $('dlByChannelBody').innerHTML = rows.map(r => {
            const color = channelColors[r.channel] || 'secondary';
            const successColor = r.success_rate >= 90 ? 'success' : (r.success_rate >= 70 ? 'warning' : 'danger');
            return `
                <tr>
                    <td class="ps-4"><span class="badge badge-light-${color} fs-7 py-2 px-3">${esc(capitalize((r.channel || '').replace(/_/g, ' ')))}</span></td>
                    <td class="text-center fw-bold">${fmtNum(r.total, 0)}</td>
                    <td class="text-center">${fmtNum(r.sent, 0)}</td>
                    <td class="text-center text-success">${fmtNum(r.delivered, 0)}</td>
                    <td class="text-center text-danger">${fmtNum(r.failed, 0)}</td>
                    <td class="text-center text-warning">${fmtNum(r.bounced, 0)}</td>
                    <td class="text-center text-muted">${fmtNum(r.pending, 0)}</td>
                    <td class="pe-4 text-end"><span class="badge badge-light-${successColor}">${fmtNum(r.success_rate, 1)}%</span></td>
                </tr>
            `;
        }).join('');

        $('dlByChannelFoot').innerHTML = `
            <tr>
                <th class="ps-4">${esc(I18N.totalAverage)}</th>
                <th class="text-center">${fmtNum(rows.reduce((s, r) => s + Number(r.total || 0), 0), 0)}</th>
                <th class="text-center">${fmtNum(rows.reduce((s, r) => s + Number(r.sent || 0), 0), 0)}</th>
                <th class="text-center text-success">${fmtNum(rows.reduce((s, r) => s + Number(r.delivered || 0), 0), 0)}</th>
                <th class="text-center text-danger">${fmtNum(rows.reduce((s, r) => s + Number(r.failed || 0), 0), 0)}</th>
                <th class="text-center text-warning">${fmtNum(rows.reduce((s, r) => s + Number(r.bounced || 0), 0), 0)}</th>
                <th class="text-center">${fmtNum(rows.reduce((s, r) => s + Number(r.pending || 0), 0), 0)}</th>
                <th class="pe-4 text-end">—</th>
            </tr>
        `;
    }

    // ─── Failures ──────────────────────────────────────────
    function renderFailures(rows) {
        if (!rows || rows.length === 0) { $('dlFailuresCard')?.classList.add('d-none'); return; }
        $('dlFailuresCard')?.classList.remove('d-none');

        $('dlFailuresBody').innerHTML = rows.map((r, idx) => `
            <tr>
                <td class="ps-4 text-muted">${idx + 1}</td>
                <td><span class="text-muted">${esc(r.reason)}</span></td>
                <td class="pe-4 text-center"><span class="badge badge-light-danger">${fmtNum(r.count, 0)}</span></td>
            </tr>
        `).join('');
    }

    // ─── Daily trend ───────────────────────────────────────
    function renderTrend(rows) {
        if (!rows || rows.length === 0) { $('dlTrendCard')?.classList.add('d-none'); return; }
        $('dlTrendCard')?.classList.remove('d-none');

        $('dlTrendBody').innerHTML = rows.map(r => `
            <tr>
                <td class="ps-4 fw-bold">${esc(formatDate(r.date))}</td>
                <td class="text-center text-primary">${fmtNum(r.sent, 0)}</td>
                <td class="text-center text-success">${fmtNum(r.delivered, 0)}</td>
                <td class="pe-4 text-center text-danger">${fmtNum(r.failed, 0)}</td>
            </tr>
        `).join('');

        $('dlTrendFoot').innerHTML = `
            <tr>
                <th class="ps-4">${esc(I18N.totalAverage)}</th>
                <th class="text-center fw-bold">${fmtNum(rows.reduce((s, r) => s + Number(r.sent || 0), 0), 0)}</th>
                <th class="text-center fw-bold">${fmtNum(rows.reduce((s, r) => s + Number(r.delivered || 0), 0), 0)}</th>
                <th class="pe-4 text-center fw-bold">${fmtNum(rows.reduce((s, r) => s + Number(r.failed || 0), 0), 0)}</th>
            </tr>
        `;
    }

    // ─── Sends table ───────────────────────────────────────
    function renderTable(rows, pagination) {
        if (!rows || rows.length === 0) { $('dlTableCard')?.classList.add('d-none'); return; }
        $('dlTableCard')?.classList.remove('d-none');
        $('dlTableBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.attempts}`;

        $('dlSendsBody').innerHTML = rows.map(s => `
            <tr class="dl-row" data-send-id="${s.id}" data-invoice-number="${esc(s.invoice_number)}">
                <td class="ps-4">
                    <span class="fw-bold text-primary">${esc(s.invoice_number)}</span>
                    ${s.billing_name ? `<br><small class="text-muted">${esc(s.billing_name)}</small>` : ''}
                </td>
                <td><span class="fw-semibold">${esc(s.recipient || '—')}</span></td>
                <td><span class="badge badge-light-${esc(s.channel_color)} fs-8">${esc(s.channel_label)}</span></td>
                <td><span class="badge badge-light-${esc(s.status_color)} fs-7 py-2 px-3">${esc(s.status_label)}</span></td>
                <td>
                    ${s.sent_at
                        ? `<span class="text-muted">${esc(s.sent_at_human)}</span><br><small class="text-muted">${esc(s.sent_at_time)}</small>`
                        : `<span class="text-muted">—</span>`}
                </td>
                <td>
                    ${s.delivered_at
                        ? `<span class="text-success">${esc(s.delivered_at_human)}</span><br><small class="text-muted">${esc(s.delivered_at_time)}</small>`
                        : `<span class="text-muted">—</span>`}
                </td>
                <td><span class="text-muted fs-7">${esc(s.provider || '—')}</span></td>
                <td><span class="text-muted fs-7">${esc(s.sent_by || 'System')}</span></td>
                <td class="pe-4">
                    ${s.error_message
                        ? `<span class="text-danger fs-7" title="${esc(s.error_message)}">${esc(s.error_preview)}</span>`
                        : `<span class="text-muted">—</span>`}
                </td>
            </tr>
        `).join('');

        renderPagination(pagination);
    }

    // ─── Pagination ────────────────────────────────────────
    function renderPagination(p) {
        const info = $('dlPaginationInfo');
        const ul   = $('dlPagination');
        const wrap = $('dlPaginationWrap');
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
        const el = document.getElementById('dlDrawer');
        if (!el) return null;
        drawerBs = drawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return drawerBs;
    }

    async function openDrawer(sendId, invoiceNumber) {
        const inst = getDrawer();
        if (!inst) return;

        $('dlDrawerSubtitle').textContent = invoiceNumber;
        $('dlDrawerSkeleton')?.classList.remove('d-none');
        const content = $('dlDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        inst.show();

        try {
            const url = DETAIL_ROUTE.replace('__ID__', sendId);
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            renderDrawer(json.data);
        } catch (err) {
            console.error('[dl-drawer] failed:', err);
            $('dlDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderDrawer(d) {
        $('dlDrawerSkeleton')?.classList.add('d-none');
        const content = $('dlDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        const statusCls = d.status === 'delivered' ? 'success'
                        : d.status === 'sent'      ? 'info'
                        : d.status === 'failed'    ? 'danger'
                        : d.status === 'bounced'   ? 'dark' : 'warning';

        const historyRows = (d.history || []).map(h => `
            <tr class="${h.is_current ? 'table-warning' : ''}">
                <td><span class="badge badge-light-secondary">${esc(capitalize((h.channel || '').replace(/_/g, ' ')))}</span></td>
                <td>${esc(h.recipient || '—')}</td>
                <td><span class="badge badge-light-${h.status === 'delivered' ? 'success' : (h.status === 'failed' ? 'danger' : (h.status === 'bounced' ? 'dark' : (h.status === 'sent' ? 'info' : 'warning')))}">${esc(capitalize(h.status))}</span></td>
                <td>${esc(h.sent_at || '—')}</td>
                <td>${h.is_current ? `<span class="badge badge-light-warning">${esc(I18N.currentAttempt)}</span>` : ''}</td>
            </tr>
            ${h.error ? `<tr><td colspan="5"><pre class="dl-pre text-danger">${esc(h.error)}</pre></td></tr>` : ''}
        `).join('');

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">${esc(d.invoice?.invoice_number || '—')}</h4>
                    <div class="text-muted fs-7">${esc(I18N.sendAttempt)} · ${esc(d.sent_at || '')}</div>
                </div>
                <span class="badge badge-light-${statusCls} fs-6 py-2 px-3">${esc(d.status_label)}</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card card-flush bg-light-primary"><div class="card-body py-3">
                    <span class="text-muted fs-8">${esc(I18N.channel)}</span>
                    <div class="fs-5 fw-bold text-primary">${esc(d.channel_label)}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card card-flush bg-light-info"><div class="card-body py-3">
                    <span class="text-muted fs-8">${esc(I18N.recipient)}</span>
                    <div class="fs-6 fw-bold text-info">${esc(d.recipient || '—')}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card card-flush bg-light-secondary"><div class="card-body py-3">
                    <span class="text-muted fs-8">${esc(I18N.provider)}</span>
                    <div class="fs-6 fw-bold">${esc(d.provider || '—')}</div>
                </div></div></div>
            </div>

            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>${esc(I18N.sendAttempt)}</h6>
            <table class="table table-sm table-bordered mb-4">
                <tbody>
                    <tr><th class="w-50">${esc(I18N.status)}</th><td><span class="badge badge-light-${statusCls}">${esc(d.status_label)}</span></td></tr>
                    <tr><th>${esc(I18N.sentAt)}</th><td>${esc(d.sent_at || '—')}</td></tr>
                    <tr><th>${esc(I18N.deliveredAt)}</th><td>${esc(d.delivered_at || '—')}</td></tr>
                    <tr><th>${esc(I18N.providerMessageId)}</th><td>${esc(d.provider_message_id || '—')}</td></tr>
                    <tr><th>${esc(I18N.sentBy)}</th><td>
                        <div class="fw-bold">${esc(d.sent_by?.name || 'System')}</div>
                        ${d.sent_by?.email ? `<small class="text-muted">${esc(d.sent_by.email)}</small>` : ''}
                    </td></tr>
                </tbody>
            </table>

            ${d.error_message ? `
                <h6 class="fw-bold mb-3"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>${esc(I18N.errorMessage)}</h6>
                <pre class="dl-pre text-danger border rounded p-3 mb-4">${esc(d.error_message)}</pre>
            ` : ''}

            ${d.invoice ? `
                <h6 class="fw-bold mb-3"><i class="bi bi-receipt me-2 text-primary"></i>${esc(I18N.invoice)}</h6>
                <table class="table table-sm table-bordered mb-4">
                    <tbody>
                        <tr><th class="w-50">${esc(I18N.invoiceNumber)}</th><td>${esc(d.invoice.invoice_number || '—')}</td></tr>
                        ${d.invoice.order_number ? `<tr><th>${esc(I18N.orderNumber)}</th><td>${esc(d.invoice.order_number)}</td></tr>` : ''}
                        <tr><th>${esc(I18N.billingName)}</th><td>${esc(d.invoice.billing_name || '—')}</td></tr>
                        ${d.invoice.billing_email ? `<tr><th>${esc(I18N.billingEmail)}</th><td>${esc(d.invoice.billing_email)}</td></tr>` : ''}
                        ${d.invoice.billing_phone ? `<tr><th>${esc(I18N.billingPhone)}</th><td>${esc(d.invoice.billing_phone)}</td></tr>` : ''}
                        <tr><th>${esc(I18N.issueDate)}</th><td>${esc(d.invoice.issue_date || '—')}</td></tr>
                        <tr><th>${esc(I18N.dueDate)}</th><td>${esc(d.invoice.due_date || '—')}</td></tr>
                        <tr><th>${esc(I18N.totalAmount)}</th><td>${money(d.invoice.total || 0)}</td></tr>
                        <tr><th>${esc(I18N.amountPaid)}</th><td class="text-success">${money(d.invoice.amount_paid || 0)}</td></tr>
                        <tr><th>${esc(I18N.balanceDue)}</th><td class="text-danger">${money(d.invoice.balance_due || 0)}</td></tr>
                    </tbody>
                </table>
            ` : ''}

            ${(d.history || []).length > 0 ? `
                <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>${esc(I18N.history)}</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="bg-light"><tr>
                            <th>${esc(I18N.channel)}</th>
                            <th>${esc(I18N.recipient)}</th>
                            <th>${esc(I18N.status)}</th>
                            <th>${esc(I18N.sentAt)}</th>
                            <th></th>
                        </tr></thead>
                        <tbody>${historyRows}</tbody>
                    </table>
                </div>
            ` : ''}
        `;
    }

    // ─── Export ────────────────────────────────────────────
    function exportExcel() {
        const table = $('dlSendsTable');
        if (!table || typeof XLSX === 'undefined') {
            window.toastr?.error('Excel export unavailable.');
            return;
        }
        try {
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, 'Invoice Delivery');
            XLSX.writeFile(wb, 'invoice_delivery.xlsx');
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