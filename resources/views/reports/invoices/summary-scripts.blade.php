<script>
window.InvoiceSummary = (function () {
    'use strict';

    const DATA_ROUTE   = window.IS_ROUTES.data;
    const DETAIL_ROUTE = window.IS_ROUTES.detail;
    const CURRENCY     = window.REPORT_CURRENCY || '';
    const BOOT         = window.IS_BOOTSTRAP || {};
    const I18N         = window.IS_I18N || {};
    const STATUS_META  = window.IS_STATUS_META || { labels: {}, colors: {} };

    const state = {
        startDate:    BOOT.startDate    || '',
        endDate:      BOOT.endDate      || '',
        locationId:   BOOT.locationId   || '',
        departmentId: BOOT.departmentId || '',
        statusFilter: BOOT.statusFilter || 'all',
        customerId:   BOOT.customerId   || '',
        search:       BOOT.search       || '',
        perPage:      Number(BOOT.perPage || 25),
        page:         1,
    };

    let drawerBs = null;
    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('isApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('isClearBtn')?.addEventListener('click', resetFilters);
        $('isExportBtn')?.addEventListener('click', exportExcel);
        $('isPrintBtn')?.addEventListener('click', () => window.print());

        $('isSearch')?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); readFilters(); state.page = 1; load(); }
        });

        $('isInvoicesBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.is-row[data-invoice-id]');
            if (!row) return;
            openDrawer(row.dataset.invoiceId, row.dataset.invoiceNumber);
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
        state.statusFilter = $('isStatus')?.value       || 'all';
        state.customerId   = $('isCustomer')?.value     || '';
        state.search       = $('isSearch')?.value       || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.departmentId = '';
        state.statusFilter = 'all';
        state.customerId = state.search = '';
        state.page = 1;
        ['isStartDate','isEndDate','isSearch'].forEach(id => { if ($(id)) $(id).value = ''; });
        if ($('isStatus')) $('isStatus').value = 'all';
        if (window.jQuery) {
            jQuery('#isLocation').val(null).trigger('change.select2');
            jQuery('#isDepartment').val(null).trigger('change.select2');
            jQuery('#isCustomer').val(null).trigger('change.select2');
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
        if (state.statusFilter) params.set('status_filter', state.statusFilter);
        if (state.customerId)   params.set('customer_id', state.customerId);
        if (state.search)       params.set('search', state.search);

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
            console.error('[invoice-summary] load failed:', err);
            window.toastr?.error('Failed to load invoice summary.');
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
        if (!d || !d.summary || d.summary.total_invoices === 0) {
            $('isEmpty')?.classList.remove('d-none');
            ['isKpiCards','isStatRow'].forEach(id => $(id) && ($(id).innerHTML = ''));
            ['isStatusCard','isAgingCard','isMonthlyCard','isTopCustomersCard','isTableCard']
                .forEach(id => $(id)?.classList.add('d-none'));
            return;
        }
        $('isEmpty')?.classList.add('d-none');
        renderKpis(d.summary);
        renderStats(d.summary);
        renderStatus(d.status_breakdown);
        renderAging(d.aging_buckets);
        renderMonthly(d.monthly_trend);
        renderTopCustomers(d.top_customers);
        renderTable(d.invoices, d.pagination, d.page_totals);
    }

    // ─── KPI cards ──────────────────────────────────────────
    function renderKpis(s) {
        $('isKpiCards').innerHTML = `
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-primary h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-primary">
                            <i class="bi bi-receipt fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-2 fw-bold">${fmtNum(s.total_invoices, 0)}</div>
                            <div class="text-muted fs-6">${esc(I18N.totalInvoices)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">
                        <span class="badge badge-light-success me-1">${fmtNum(s.paid_count, 0)}</span> ${esc(I18N.paid)}
                        <span class="badge badge-light-warning ms-2 me-1">${fmtNum(s.partially_paid_count, 0)}</span> ${esc(I18N.partial)}
                        <span class="badge badge-light-danger ms-2 me-1">${fmtNum(s.overdue_count, 0)}</span> ${esc(I18N.overdue)}
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
                            <div class="fs-4 fw-bold">${money(s.total_invoiced)}</div>
                            <div class="text-muted fs-6">${esc(I18N.totalInvoiced)}</div>
                            <div class="fs-4 fw-bold text-success mt-1">${money(s.total_paid)}</div>
                            <div class="text-muted fs-6">${esc(I18N.totalCollected)}</div>
                        </div>
                    </div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-warning h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-warning">
                            <i class="bi bi-clock-history fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-4 fw-bold text-danger">${money(s.total_outstanding)}</div>
                            <div class="text-muted fs-6">${esc(I18N.outstanding)}</div>
                            <div class="fs-4 fw-bold text-primary mt-1">${fmtNum(s.collection_rate, 1)}%</div>
                            <div class="text-muted fs-6">${esc(I18N.collectionRate)}</div>
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
                            <div class="fs-4 fw-bold">${money(s.average_invoice_value)}</div>
                            <div class="text-muted fs-6">${esc(I18N.averageInvoice)}</div>
                            <div class="fs-4 fw-bold text-primary mt-1">${money(s.largest_invoice)}</div>
                            <div class="text-muted fs-6">${esc(I18N.largestInvoice)}</div>
                        </div>
                    </div>
                </div></div>
            </div>
        `;
    }

    // ─── Stat row ───────────────────────────────────────────
    function renderStats(s) {
        const paidRate = s.total_invoices > 0 ? (s.paid_count / s.total_invoices) * 100 : 0;
        $('isStatRow').innerHTML = `
            <div class="col-md-3"><div class="card bg-light-info"><div class="card-body text-center">
                <div class="fs-6 text-muted">${esc(I18N.totalTax)}</div>
                <div class="fs-3 fw-bold text-info">${money(s.total_tax)}</div>
            </div></div></div>
            <div class="col-md-3"><div class="card bg-light-warning"><div class="card-body text-center">
                <div class="fs-6 text-muted">${esc(I18N.totalDiscount)}</div>
                <div class="fs-3 fw-bold text-warning">${money(s.total_discount)}</div>
            </div></div></div>
            <div class="col-md-3"><div class="card bg-light-danger"><div class="card-body text-center">
                <div class="fs-6 text-muted">${esc(I18N.overdueInvoices)}</div>
                <div class="fs-3 fw-bold text-danger">${fmtNum(s.overdue_count, 0)}</div>
                <div class="fs-7 text-muted">${fmtNum(s.outstanding_count, 0)} ${esc(I18N.outstanding)}</div>
            </div></div></div>
            <div class="col-md-3"><div class="card bg-light-success"><div class="card-body text-center">
                <div class="fs-6 text-muted">${esc(I18N.fullyPaid)}</div>
                <div class="fs-3 fw-bold text-success">${fmtNum(paidRate, 1)}%</div>
                <div class="fs-7 text-muted">${fmtNum(s.paid_count, 0)} / ${fmtNum(s.total_invoices, 0)} ${esc(I18N.invoices)}</div>
            </div></div></div>
        `;
    }

    // ─── Status breakdown ───────────────────────────────────
    function renderStatus(rows) {
        if (!rows || rows.length === 0) { $('isStatusCard')?.classList.add('d-none'); return; }
        $('isStatusCard')?.classList.remove('d-none');

        $('isStatusBody').innerHTML = rows.map(r => `
            <tr>
                <td class="ps-4">
                    <span class="badge badge-light-${esc(r.color)} fs-7 py-2 px-3">${esc(r.label)}</span>
                </td>
                <td class="text-center fw-bold">${fmtNum(r.count, 0)}</td>
                <td class="text-end">${money(r.total_amount)}</td>
                <td class="text-end">${money(r.balance_due)}</td>
                <td class="pe-4 text-end">
                    <span class="badge badge-light-secondary">${fmtNum(r.percentage, 1)}%</span>
                </td>
            </tr>
        `).join('');

        const sumCount   = rows.reduce((s, r) => s + Number(r.count || 0), 0);
        const sumTotal   = rows.reduce((s, r) => s + Number(r.total_amount || 0), 0);
        const sumBalance = rows.reduce((s, r) => s + Number(r.balance_due || 0), 0);

        $('isStatusFoot').innerHTML = `
            <tr>
                <th class="ps-4">${esc(I18N.totalAverage)}</th>
                <th class="text-center fw-bold">${fmtNum(sumCount, 0)}</th>
                <th class="text-end fw-bold">${money(sumTotal)}</th>
                <th class="text-end fw-bold">${money(sumBalance)}</th>
                <th class="pe-4 text-end">100%</th>
            </tr>
        `;
    }

    // ─── Aging buckets ─────────────────────────────────────
    function renderAging(buckets) {
        if (!buckets) { $('isAgingCard')?.classList.add('d-none'); return; }
        $('isAgingCard')?.classList.remove('d-none');

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

        $('isAgingBuckets').innerHTML = Object.keys(labels).map(key => {
            const b = buckets[key] || { count: 0, amount: 0, color: 'secondary' };
            return `
                <div class="col-md-6 col-lg">
                    <div class="card bg-light-${esc(b.color)} h-100"><div class="card-body text-center">
                        <i class="bi ${icons[key]} fs-2tx text-${esc(b.color)} mb-2"></i>
                        <div class="fs-6 text-muted">${esc(labels[key])}</div>
                        <div class="fs-2 fw-bold text-${esc(b.color)}">${fmtNum(b.count, 0)}</div>
                        <div class="fs-6 text-muted">${money(b.amount)}</div>
                    </div></div>
                </div>
            `;
        }).join('');
    }

    // ─── Monthly trend ─────────────────────────────────────
    function renderMonthly(rows) {
        if (!rows || rows.length === 0) { $('isMonthlyCard')?.classList.add('d-none'); return; }
        $('isMonthlyCard')?.classList.remove('d-none');

        $('isMonthlyBody').innerHTML = rows.map(r => `
            <tr>
                <td class="ps-4"><span class="fw-bold">${esc(r.month_label)}</span></td>
                <td class="text-center">${fmtNum(r.invoice_count, 0)}</td>
                <td class="text-end">${money(r.total_amount)}</td>
                <td class="text-end text-success">${money(r.paid_amount)}</td>
                <td class="pe-4 text-end fw-bold ${r.outstanding > 0 ? 'text-danger' : 'text-muted'}">${money(r.outstanding)}</td>
            </tr>
        `).join('');

        const sum = rows.reduce((acc, r) => ({
            count:       acc.count + Number(r.invoice_count || 0),
            total:       acc.total + Number(r.total_amount || 0),
            paid:        acc.paid + Number(r.paid_amount || 0),
            outstanding: acc.outstanding + Number(r.outstanding || 0),
        }), { count: 0, total: 0, paid: 0, outstanding: 0 });

        $('isMonthlyFoot').innerHTML = `
            <tr>
                <th class="ps-4">${esc(I18N.totalAverage)}</th>
                <th class="text-center fw-bold">${fmtNum(sum.count, 0)}</th>
                <th class="text-end fw-bold">${money(sum.total)}</th>
                <th class="text-end fw-bold">${money(sum.paid)}</th>
                <th class="pe-4 text-end fw-bold">${money(sum.outstanding)}</th>
            </tr>
        `;
    }

    // ─── Top customers ─────────────────────────────────────
    function renderTopCustomers(rows) {
        if (!rows || rows.length === 0) { $('isTopCustomersCard')?.classList.add('d-none'); return; }
        $('isTopCustomersCard')?.classList.remove('d-none');

        $('isTopCustomersBody').innerHTML = rows.map(c => `
            <tr>
                <td class="ps-4">
                    <span class="fw-bold">${esc(c.customer_name)}</span>
                    ${!c.customer_id ? `<span class="badge badge-light-secondary ms-1">${esc(I18N.guest)}</span>` : ''}
                </td>
                <td class="text-center">${fmtNum(c.invoice_count, 0)}</td>
                <td class="text-end">${money(c.total_billed)}</td>
                <td class="text-end text-success">${money(c.total_paid)}</td>
                <td class="pe-4 text-end fw-bold ${c.outstanding > 0 ? 'text-danger' : 'text-muted'}">${money(c.outstanding)}</td>
            </tr>
        `).join('');
    }

    // ─── Invoices table ────────────────────────────────────
    function renderTable(rows, pagination, pageTotals) {
        if (!rows || rows.length === 0) { $('isTableCard')?.classList.add('d-none'); return; }
        $('isTableCard')?.classList.remove('d-none');
        $('isTableBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.invoices}`;

        $('isInvoicesBody').innerHTML = rows.map(inv => `
            <tr class="is-row" data-invoice-id="${inv.id}" data-invoice-number="${esc(inv.invoice_number)}">
                <td class="ps-4"><span class="fw-bold text-primary">${esc(inv.invoice_number)}</span></td>
                <td><span class="text-muted">${esc(inv.issue_human || '—')}</span></td>
                <td>${inv.due_date
                    ? `<span class="${inv.is_overdue_due ? 'text-danger fw-bold' : 'text-muted'}">${esc(inv.due_human)}</span>`
                    : `<span class="text-muted">—</span>`}</td>
                <td>
                    <span class="fw-bold">${esc(inv.billing_name || '—')}</span>
                    ${inv.billing_email ? `<br><small class="text-muted">${esc(inv.billing_email)}</small>` : ''}
                </td>
                <td><span class="badge badge-light-${esc(inv.status_color)} fs-7 py-2 px-3">${esc(inv.status_label)}</span></td>
                <td class="text-end">${money(inv.total)}</td>
                <td class="text-end text-success">${money(inv.amount_paid)}</td>
                <td class="text-end fw-bold ${inv.balance_due > 0 ? 'text-danger' : 'text-muted'}">${money(inv.balance_due)}</td>
                <td class="pe-4"><span class="text-muted fs-7">${esc(inv.location_name)}</span></td>
            </tr>
        `).join('');

        $('isInvoicesFoot').innerHTML = `
            <tr>
                <th colspan="5" class="ps-4">${esc(I18N.totalAverage)}</th>
                <th class="text-end fw-bold">${money(pageTotals.total)}</th>
                <th class="text-end fw-bold">${money(pageTotals.amount_paid)}</th>
                <th class="text-end fw-bold">${money(pageTotals.balance_due)}</th>
                <th class="pe-4"></th>
            </tr>
        `;

        renderPagination(pagination);
    }

    // ─── Pagination ────────────────────────────────────────
    function renderPagination(p) {
        const info = $('isPaginationInfo');
        const ul   = $('isPagination');
        const wrap = $('isPaginationWrap');
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
        const el = document.getElementById('isDrawer');
        if (!el) return null;
        drawerBs = drawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return drawerBs;
    }

    async function openDrawer(invoiceId, invoiceNumber) {
        const inst = getDrawer();
        if (!inst) return;

        $('isDrawerSubtitle').textContent = invoiceNumber;
        $('isDrawerSkeleton')?.classList.remove('d-none');
        const content = $('isDrawerContent');
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

        // Items
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

        // Payments
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

        // Sends
        const sendRows = (d.sends || []).map(s => `
            <tr>
                <td><span class="badge badge-light-secondary">${esc(s.channel)}</span></td>
                <td>${esc(s.recipient || '—')}</td>
                <td><span class="badge badge-light-${s.status === 'sent' || s.status === 'delivered' ? 'success' : (s.status === 'failed' ? 'danger' : 'secondary')}">${esc(s.status)}</span></td>
                <td>${esc(s.sent_at || '—')}</td>
            </tr>
            ${s.error ? `<tr><td colspan="4"><pre class="is-notes text-danger">${esc(s.error)}</pre></td></tr>` : ''}
        `).join('');

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">${esc(d.invoice_number)}</h4>
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
                    <span class="text-muted fs-8">${esc(I18N.totalPaidLbl)}</span>
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
                <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-2 text-primary"></i>${esc(I18N.item)}s</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered">
                        <thead class="bg-light"><tr>
                            <th>${esc(I18N.item)}</th>
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
                <div class="border rounded p-3 text-gray-800 is-notes">${esc(d.notes)}</div>
            ` : ''}
        `;
    }

    // ─── Export ────────────────────────────────────────────
    function exportExcel() {
        const table = $('isInvoicesTable');
        if (!table || typeof XLSX === 'undefined') {
            window.toastr?.error('Excel export unavailable.');
            return;
        }
        try {
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, 'Invoice Summary');
            XLSX.writeFile(wb, 'invoice_summary.xlsx');
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