<script>
window.InvoiceOutstanding = (function () {
    'use strict';

    const DATA_ROUTE   = window.OU_ROUTES.data;
    const DETAIL_ROUTE = window.OU_ROUTES.detail;
    const CURRENCY     = window.REPORT_CURRENCY || '';
    const BOOT         = window.OU_BOOTSTRAP || {};
    const I18N         = window.OU_I18N || {};

    const state = {
        startDate:    BOOT.startDate    || '',
        endDate:      BOOT.endDate      || '',
        locationId:   BOOT.locationId   || '',
        departmentId: BOOT.departmentId || '',
        customerId:   BOOT.customerId   || '',
        agingBucket:  BOOT.agingBucket  || 'all',
        onlyOverdue:  !!BOOT.onlyOverdue,
        perPage:      Number(BOOT.perPage || 25),
        page:         1,
    };

    let drawerBs = null;
    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('ouApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('ouClearBtn')?.addEventListener('click', resetFilters);
        $('ouExportBtn')?.addEventListener('click', exportExcel);
        $('ouPrintBtn')?.addEventListener('click', () => window.print());

        $('ouInvoicesBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.ou-row[data-invoice-id]');
            if (!row) return;
            openDrawer(row.dataset.invoiceId, row.dataset.invoiceNumber);
        });

        document.getElementById('ouDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('ouDrawerContent').innerHTML = '';
            $('ouDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate    = $('ouStartDate')?.value    || '';
        state.endDate      = $('ouEndDate')?.value      || '';
        state.locationId   = $('ouLocation')?.value     || '';
        state.departmentId = $('ouDepartment')?.value   || '';
        state.customerId   = $('ouCustomer')?.value     || '';
        state.agingBucket  = $('ouAgingBucket')?.value  || 'all';
        state.onlyOverdue  = !!$('ouOnlyOverdue')?.checked;
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.departmentId = state.customerId = '';
        state.agingBucket = 'all';
        state.onlyOverdue = false;
        state.page = 1;
        ['ouStartDate','ouEndDate'].forEach(id => { if ($(id)) $(id).value = ''; });
        if ($('ouAgingBucket')) $('ouAgingBucket').value = 'all';
        if ($('ouOnlyOverdue')) $('ouOnlyOverdue').checked = false;
        if (window.jQuery) {
            jQuery('#ouLocation').val(null).trigger('change.select2');
            jQuery('#ouDepartment').val(null).trigger('change.select2');
            jQuery('#ouCustomer').val(null).trigger('change.select2');
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
        if (state.customerId)   params.set('customer_id', state.customerId);
        if (state.agingBucket)  params.set('aging_bucket', state.agingBucket);
        if (state.onlyOverdue)  params.set('only_overdue', '1');

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
            console.error('[invoice-outstanding] load failed:', err);
            window.toastr?.error('Failed to load outstanding report.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('ouSkeleton')?.classList.toggle('d-none', !v);
        $('ouContent')?.classList.toggle('d-none', v);
    }

    function render(d) {
        if (!d || !d.summary || d.summary.total_invoices === 0) {
            $('ouEmpty')?.classList.remove('d-none');
            $('ouKpiCards').innerHTML = '';
            ['ouAgingCard','ouDebtorsCard','ouByLocationCard','ouTableCard']
                .forEach(id => $(id)?.classList.add('d-none'));
            return;
        }
        $('ouEmpty')?.classList.add('d-none');
        renderKpis(d.summary);
        renderAging(d.aging_buckets);
        renderTopDebtors(d.top_debtors);
        renderByLocation(d.by_location);
        renderTable(d.invoices, d.pagination, d.page_totals);
    }

    // ─── KPI cards ──────────────────────────────────────────
    function renderKpis(s) {
        $('ouKpiCards').innerHTML = `
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-danger h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-danger">
                            <i class="bi bi-currency-dollar fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-3 fw-bold text-danger">${money(s.total_outstanding)}</div>
                            <div class="text-muted fs-6">${esc(I18N.totalOutstanding)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">${fmtNum(s.total_invoices, 0)} ${esc(I18N.invoices)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-warning h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-warning">
                            <i class="bi bi-clock-history fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-3 fw-bold text-warning">${money(s.overdue_amount)}</div>
                            <div class="text-muted fs-6">${esc(I18N.overdueAmount)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">
                        ${fmtNum(s.overdue_invoices, 0)} ${esc(I18N.invoices)} · ${esc(I18N.avg)} ${fmtNum(s.average_days_overdue, 1)} ${esc(I18N.days)}
                    </div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-success h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-success">
                            <i class="bi bi-check-circle fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-3 fw-bold text-success">${money(s.current_amount)}</div>
                            <div class="text-muted fs-6">${esc(I18N.currentAmount)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">${fmtNum(s.current_invoices, 0)} ${esc(I18N.invoices)} · ${esc(I18N.notYetDue)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-primary h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-primary">
                            <i class="bi bi-graph-up fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-3 fw-bold text-primary">${fmtNum(s.collection_rate, 1)}%</div>
                            <div class="text-muted fs-6">${esc(I18N.collectionRate)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">${esc(I18N.collected)}: ${money(s.total_collected)}</div>
                </div></div>
            </div>
        `;
    }

    // ─── Aging buckets ─────────────────────────────────────
    function renderAging(buckets) {
        if (!buckets) { $('ouAgingCard')?.classList.add('d-none'); return; }
        $('ouAgingCard')?.classList.remove('d-none');

        const labels = {
            current:  I18N.current,
            '1_30':   I18N.days1_30,
            '31_60':  I18N.days31_60,
            '61_90':  I18N.days61_90,
            '90_plus':I18N.days90plus,
        };
        const icons = {
            current:  'bi-check-circle',
            '1_30':   'bi-info-circle',
            '31_60':  'bi-exclamation-circle',
            '61_90':  'bi-exclamation-triangle',
            '90_plus':'bi-x-circle',
        };
        const subs = {
            current:  I18N.notYetDue,
            '1_30':   I18N.daysOverdue,
            '31_60':  I18N.daysOverdue,
            '61_90':  I18N.daysOverdue,
            '90_plus':I18N.daysOverdue,
        };

        $('ouAgingBuckets').innerHTML = Object.keys(labels).map(key => {
            const b = buckets[key] || { count: 0, amount: 0, color: 'secondary' };
            return `
                <div class="col-md-6 col-lg">
                    <div class="card bg-light-${esc(b.color)} h-100"><div class="card-body text-center">
                        <i class="bi ${icons[key]} fs-2tx text-${esc(b.color)} mb-2"></i>
                        <div class="fs-6 text-muted">${esc(labels[key])}</div>
                        <div class="fs-2 fw-bold text-${esc(b.color)}">${fmtNum(b.count, 0)}</div>
                        <div class="fs-7 fw-semibold">${money(b.amount)}</div>
                        <div class="fs-8 text-muted">${esc(subs[key])}</div>
                    </div></div>
                </div>
            `;
        }).join('');
    }

    // ─── Top debtors ───────────────────────────────────────
    function renderTopDebtors(rows) {
        if (!rows || rows.length === 0) { $('ouDebtorsCard')?.classList.add('d-none'); return; }
        $('ouDebtorsCard')?.classList.remove('d-none');

        $('ouDebtorsBody').innerHTML = rows.map(d => `
            <tr>
                <td class="ps-4">
                    <span class="fw-bold">${esc(d.customer_name)}</span>
                    ${!d.customer_id ? `<span class="badge badge-light-secondary ms-1">${esc(I18N.guest)}</span>` : ''}
                </td>
                <td class="text-center">${fmtNum(d.invoice_count, 0)}</td>
                <td class="text-end fw-bold text-danger">${money(d.total_outstanding)}</td>
                <td class="text-center">
                    ${d.overdue_count > 0
                        ? `<span class="badge badge-light-danger">${fmtNum(d.overdue_count, 0)}</span>`
                        : `<span class="badge badge-light-secondary">0</span>`}
                </td>
                <td class="text-end text-warning">${money(d.overdue_amount)}</td>
                <td class="pe-4 text-center">
                    ${d.oldest_days_overdue > 0
                        ? `<span class="badge badge-light-${d.oldest_days_overdue > 90 ? 'dark' : (d.oldest_days_overdue > 60 ? 'danger' : (d.oldest_days_overdue > 30 ? 'warning' : 'info'))}">
                             ${fmtNum(d.oldest_days_overdue, 0)} ${esc(I18N.days)}
                           </span>`
                        : `<span class="text-muted">—</span>`}
                </td>
            </tr>
        `).join('');
    }

    // ─── By location ───────────────────────────────────────
    function renderByLocation(rows) {
        if (!rows || rows.length === 0) { $('ouByLocationCard')?.classList.add('d-none'); return; }
        $('ouByLocationCard')?.classList.remove('d-none');

        $('ouByLocationBody').innerHTML = rows.map(r => `
            <tr>
                <td class="ps-4"><span class="fw-bold">${esc(r.location)}</span></td>
                <td class="text-center">${fmtNum(r.invoice_count, 0)}</td>
                <td class="text-end fw-bold text-danger">${money(r.outstanding)}</td>
                <td class="pe-4 text-end text-warning">${money(r.overdue_amount)}</td>
            </tr>
        `).join('');

        $('ouByLocationFoot').innerHTML = `
            <tr>
                <th class="ps-4">${esc(I18N.totalAverage)}</th>
                <th class="text-center fw-bold">${fmtNum(rows.reduce((s, r) => s + Number(r.invoice_count || 0), 0), 0)}</th>
                <th class="text-end fw-bold">${money(rows.reduce((s, r) => s + Number(r.outstanding || 0), 0))}</th>
                <th class="pe-4 text-end fw-bold">${money(rows.reduce((s, r) => s + Number(r.overdue_amount || 0), 0))}</th>
            </tr>
        `;
    }

    // ─── Invoices table ────────────────────────────────────
    function renderTable(rows, pagination, pageTotals) {
        if (!rows || rows.length === 0) { $('ouTableCard')?.classList.add('d-none'); return; }
        $('ouTableCard')?.classList.remove('d-none');
        $('ouTableBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.invoices}`;

        $('ouInvoicesBody').innerHTML = rows.map(inv => `
            <tr class="ou-row" data-invoice-id="${inv.id}" data-invoice-number="${esc(inv.invoice_number)}">
                <td class="ps-4"><span class="fw-bold text-primary">${esc(inv.invoice_number)}</span></td>
                <td>
                    <span class="fw-semibold">${esc(inv.billing_name || '—')}</span>
                    ${inv.billing_phone ? `<br><small class="text-muted">${esc(inv.billing_phone)}</small>` : ''}
                </td>
                <td><span class="text-muted">${esc(inv.issue_human || '—')}</span></td>
                <td>${inv.due_date
                    ? `<span class="${inv.is_due_past ? 'text-danger fw-bold' : 'text-muted'}">${esc(inv.due_human)}</span>`
                    : `<span class="text-muted">—</span>`}</td>
                <td class="text-center">
                    ${inv.days_past_due > 0
                        ? `<span class="badge badge-light-${inv.days_past_due > 90 ? 'dark' : (inv.days_past_due > 60 ? 'danger' : (inv.days_past_due > 30 ? 'warning' : 'info'))}">
                             ${fmtNum(inv.days_past_due, 0)}
                           </span>`
                        : `<span class="text-muted">—</span>`}
                </td>
                <td><span class="badge badge-light-${esc(inv.bucket_color)}">${esc(inv.bucket_label)}</span></td>
                <td class="text-end">${money(inv.total)}</td>
                <td class="text-end text-success">${money(inv.amount_paid)}</td>
                <td class="text-end fw-bold text-danger">${money(inv.balance_due)}</td>
                <td class="pe-4"><span class="text-muted fs-7">${esc(inv.location_name)}</span></td>
            </tr>
        `).join('');

        $('ouInvoicesFoot').innerHTML = `
            <tr>
                <th colspan="6" class="ps-4">${esc(I18N.totalAverage)}</th>
                <th class="text-end fw-bold">${money(pageTotals.total)}</th>
                <th class="text-end fw-bold">${money(pageTotals.amount_paid)}</th>
                <th class="text-end fw-bold text-danger">${money(pageTotals.balance_due)}</th>
                <th class="pe-4"></th>
            </tr>
        `;

        renderPagination(pagination);
    }

    // ─── Pagination ────────────────────────────────────────
    function renderPagination(p) {
        const info = $('ouPaginationInfo');
        const ul   = $('ouPagination');
        const wrap = $('ouPaginationWrap');
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
        const el = document.getElementById('ouDrawer');
        if (!el) return null;
        drawerBs = drawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return drawerBs;
    }

    async function openDrawer(invoiceId, invoiceNumber) {
        const inst = getDrawer();
        if (!inst) return;

        $('ouDrawerSubtitle').textContent = invoiceNumber;
        $('ouDrawerSkeleton')?.classList.remove('d-none');
        const content = $('ouDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        inst.show();

        try {
            const url = DETAIL_ROUTE.replace('__ID__', invoiceId);
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            renderDrawer(json.data);
        } catch (err) {
            console.error('[ou-drawer] failed:', err);
            $('ouDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderDrawer(d) {
        $('ouDrawerSkeleton')?.classList.add('d-none');
        const content = $('ouDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        const itemRows = (d.items || []).map(i => `
            <tr>
                <td><span class="fw-bold">${esc(i.name)}</span><br><small class="text-muted">${esc(i.sku)}</small></td>
                <td class="text-center">${fmtNum(i.quantity, 0)}</td>
                <td class="text-end">${money(i.unit_price)}</td>
                <td class="text-end text-danger">${money(i.discount)}</td>
                <td class="text-end text-info">${money(i.tax_amount)}</td>
                <td class="text-end fw-bold">${money(i.total_price)}</td>
            </tr>
        `).join('');

        const paymentRows = (d.payments || []).map(p => `
            <tr>
                <td class="text-end fw-bold text-success">${money(p.amount)}</td>
                <td><span class="badge badge-light-info">${esc(p.method_name)}</span></td>
                <td><span class="badge badge-light-${esc(p.status_color)}">${esc(p.status_label)}</span></td>
                <td>${esc(p.processed_by || '—')}</td>
                <td>${esc(p.payment_date || '—')}</td>
                <td>${esc(p.transaction_id || '—')}</td>
            </tr>
        `).join('');

        const sendRows = (d.sends || []).map(s => `
            <tr>
                <td><span class="badge badge-light-secondary">${esc(s.channel)}</span></td>
                <td>${esc(s.recipient || '—')}</td>
                <td><span class="badge badge-light-${s.status === 'sent' || s.status === 'delivered' ? 'success' : (s.status === 'failed' ? 'danger' : 'secondary')}">${esc(s.status)}</span></td>
                <td>${esc(s.sent_at || '—')}</td>
            </tr>
            ${s.error ? `<tr><td colspan="4"><pre class="ou-notes text-danger">${esc(s.error)}</pre></td></tr>` : ''}
        `).join('');

        const overdueBadge = d.is_overdue
            ? `<span class="badge badge-light-danger ms-2">${fmtNum(d.days_past_due, 0)} ${esc(I18N.days)}</span>`
            : '';

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">${esc(d.invoice_number)}${overdueBadge}</h4>
                    <div class="text-muted fs-7">${esc(d.billing?.name || '—')} · ${esc(d.issue_date || '')}</div>
                </div>
                <span class="badge badge-light-${esc(d.status_color)} fs-6 py-2 px-3">${esc(d.status_label)}</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3"><div class="card card-flush bg-light-secondary"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.subtotal)}</span>
                    <div class="fs-5 fw-bold">${money(d.subtotal)}</div>
                </div></div></div>
                <div class="col-6 col-md-3"><div class="card card-flush bg-light-warning"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.discount)}</span>
                    <div class="fs-5 fw-bold text-warning">${money(d.discount)}</div>
                </div></div></div>
                <div class="col-6 col-md-3"><div class="card card-flush bg-light-info"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.tax)}</span>
                    <div class="fs-5 fw-bold text-info">${money(d.tax)}</div>
                </div></div></div>
                <div class="col-6 col-md-3"><div class="card card-flush bg-light-primary"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.total)}</span>
                    <div class="fs-5 fw-bold text-primary">${money(d.total)}</div>
                </div></div></div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6"><div class="card card-flush bg-light-success"><div class="card-body py-3">
                    <span class="text-muted fs-8">${esc(I18N.paid)}</span>
                    <div class="fs-5 fw-bold text-success">${money(d.amount_paid)}</div>
                </div></div></div>
                <div class="col-md-6"><div class="card card-flush bg-light-danger"><div class="card-body py-3">
                    <span class="text-muted fs-8">${esc(I18N.balanceDue)}</span>
                    <div class="fs-5 fw-bold text-danger">${money(d.balance_due)}</div>
                </div></div></div>
            </div>

            ${d.order ? `
                <h6 class="fw-bold mb-3"><i class="bi bi-bag-check me-2 text-primary"></i>${esc(I18N.orderInfo)}</h6>
                <table class="table table-sm table-bordered mb-4">
                    <tbody>
                        <tr><th class="w-50">${esc(I18N.orderNumber)}</th><td>${esc(d.order.order_number || '—')}</td></tr>
                        <tr><th>${esc(I18N.location)}</th><td>${esc(d.order.location_name || '—')}</td></tr>
                        <tr><th>${esc(I18N.department)}</th><td>${esc(d.order.department_name || '—')}</td></tr>
                        <tr><th>${esc(I18N.createdBy)}</th><td>${esc(d.created_by || '—')}</td></tr>
                    </tbody>
                </table>
            ` : ''}

            ${(d.items || []).length > 0 ? `
                <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-2 text-primary"></i>${esc(I18N.items)}</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered">
                        <thead class="bg-light"><tr>
                            <th>${esc(I18N.items)}</th>
                            <th class="text-center">${esc(I18N.qty)}</th>
                            <th class="text-end">${esc(I18N.unitPrice)}</th>
                            <th class="text-end">${esc(I18N.discount)}</th>
                            <th class="text-end">${esc(I18N.tax)}</th>
                            <th class="text-end">${esc(I18N.lineTotal)}</th>
                        </tr></thead>
                        <tbody>${itemRows}</tbody>
                    </table>
                </div>
            ` : ''}

            ${(d.payments || []).length > 0 ? `
                <h6 class="fw-bold mb-3"><i class="bi bi-cash-coin me-2 text-success"></i>${esc(I18N.payments)}</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered">
                        <thead class="bg-light"><tr>
                            <th class="text-end">${esc(I18N.amount)}</th>
                            <th>${esc(I18N.method)}</th>
                            <th>${esc(I18N.status)}</th>
                            <th>${esc(I18N.processedBy)}</th>
                            <th>${esc(I18N.paymentDate)}</th>
                            <th>${esc(I18N.transactionId)}</th>
                        </tr></thead>
                        <tbody>${paymentRows}</tbody>
                    </table>
                </div>
            ` : ''}

            ${(d.sends || []).length > 0 ? `
                <h6 class="fw-bold mb-3"><i class="bi bi-envelope-paper me-2 text-info"></i>${esc(I18N.sends)}</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered">
                        <thead class="bg-light"><tr>
                            <th>${esc(I18N.channel)}</th>
                            <th>${esc(I18N.recipient)}</th>
                            <th>${esc(I18N.status)}</th>
                            <th>${esc(I18N.sentAt)}</th>
                        </tr></thead>
                        <tbody>${sendRows}</tbody>
                    </table>
                </div>
            ` : ''}

            ${d.notes ? `
                <h6 class="fw-bold mt-4 mb-3"><i class="bi bi-chat-left-text me-2 text-primary"></i>${esc(I18N.notes)}</h6>
                <div class="border rounded p-3 text-gray-800 ou-notes">${esc(d.notes)}</div>
            ` : ''}
        `;
    }

    // ─── Export ────────────────────────────────────────────
    function exportExcel() {
        const table = $('ouInvoicesTable');
        if (!table || typeof XLSX === 'undefined') {
            window.toastr?.error('Excel export unavailable.');
            return;
        }
        try {
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, 'Outstanding');
            XLSX.writeFile(wb, 'outstanding_aging.xlsx');
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
    function fmtNum(n, d = 0) {
        return Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: d, maximumFractionDigits: d,
        });
    }
    function money(n) { return CURRENCY + fmtNum(n, 2); }

    return { load };
})();
</script>