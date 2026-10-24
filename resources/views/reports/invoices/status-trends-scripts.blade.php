
<script>
window.InvoiceStatusTrends = (function () {
    'use strict';

    const DATA_ROUTE   = window.ST_ROUTES.data;
    const DETAIL_ROUTE = window.ST_ROUTES.detail;
    const CURRENCY     = window.REPORT_CURRENCY || '';
    const BOOT         = window.ST_BOOTSTRAP || {};
    const I18N         = window.ST_I18N || {};
    const STATUS_COLORS= window.ST_STATUS_COLORS || {};

    const state = {
        startDate:    BOOT.startDate    || '',
        endDate:      BOOT.endDate      || '',
        locationId:   BOOT.locationId   || '',
        departmentId: BOOT.departmentId || '',
        groupBy:      BOOT.groupBy      || 'monthly',
        perPage:      Number(BOOT.perPage || 25),
        page:         1,
    };

    let drawerBs = null;
    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('stApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('stClearBtn')?.addEventListener('click', resetFilters);
        $('stExportBtn')?.addEventListener('click', exportExcel);
        $('stPrintBtn')?.addEventListener('click', () => window.print());

        $('stInvoicesBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.st-row[data-invoice-id]');
            if (!row) return;
            openDrawer(row.dataset.invoiceId, row.dataset.invoiceNumber);
        });

        document.getElementById('stDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('stDrawerContent').innerHTML = '';
            $('stDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate    = $('stStartDate')?.value    || '';
        state.endDate      = $('stEndDate')?.value      || '';
        state.locationId   = $('stLocation')?.value     || '';
        state.departmentId = $('stDepartment')?.value   || '';
        state.groupBy      = $('stGroupBy')?.value      || 'monthly';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.departmentId = '';
        state.groupBy = 'monthly';
        state.page = 1;
        ['stStartDate','stEndDate'].forEach(id => { if ($(id)) $(id).value = ''; });
        if ($('stGroupBy')) $('stGroupBy').value = 'monthly';
        if (window.jQuery) {
            jQuery('#stLocation').val(null).trigger('change.select2');
            jQuery('#stDepartment').val(null).trigger('change.select2');
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
        if (state.groupBy)      params.set('group_by', state.groupBy);

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
            console.error('[status-trends] load failed:', err);
            window.toastr?.error('Failed to load status trends report.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('stSkeleton')?.classList.toggle('d-none', !v);
        $('stContent')?.classList.toggle('d-none', v);
    }

    function render(d) {
        if (!d || !d.summary || d.summary.total_invoices === 0) {
            $('stEmpty')?.classList.remove('d-none');
            $('stKpiCards').innerHTML = '';
            ['stSpeedCard','stFunnelCard','stTimeMetricsCard','stTrendCard','stTableCard']
                .forEach(id => $(id)?.classList.add('d-none'));
            return;
        }
        $('stEmpty')?.classList.add('d-none');
        renderKpis(d.summary);
        renderSpeedBuckets(d.speed_buckets);
        renderFunnel(d.status_funnel);
        renderTimeMetrics(d.summary);
        renderTrend(d.trend, d.trend_totals, d.meta.group_by);
        renderTable(d.invoices, d.pagination);
    }

    // ─── KPI cards ──────────────────────────────────────────
    function renderKpis(s) {
        const totalWithTerms = s.paid_within_terms + s.paid_late;
        const onTimeRate = totalWithTerms > 0 ? (s.paid_within_terms / totalWithTerms) * 100 : 0;

        $('stKpiCards').innerHTML = `
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-primary h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-primary">
                            <i class="bi bi-file-earmark-text fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-3 fw-bold text-primary">${fmtNum(s.total_invoices, 0)}</div>
                            <div class="text-muted fs-6">${esc(I18N.totalInvoices)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">
                        <span class="badge badge-light-success me-1">${fmtNum(s.paid_count, 0)}</span> ${esc(I18N.paid)}
                        <span class="badge badge-light-warning ms-2 me-1">${fmtNum(s.unpaid_count, 0)}</span> ${esc(I18N.unpaid)}
                    </div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-success h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-success">
                            <i class="bi bi-clock-history fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-3 fw-bold text-success">${fmtNum(s.avg_days_to_pay, 1)}</div>
                            <div class="text-muted fs-6">${esc(I18N.avgDaysToPay)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">${esc(I18N.median)}: ${fmtNum(s.median_days_to_pay, 1)} ${esc(I18N.days)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-info h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-info">
                            <i class="bi bi-send fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-3 fw-bold text-info">${fmtNum(s.avg_days_to_send, 1)}</div>
                            <div class="text-muted fs-6">${esc(I18N.avgDaysToSend)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">${esc(I18N.avgDaysToView)}: ${fmtNum(s.avg_days_to_view, 1)} ${esc(I18N.days)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-warning h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3"><span class="symbol-label bg-warning">
                            <i class="bi bi-check-circle fs-2x text-white"></i>
                        </span></div>
                        <div>
                            <div class="fs-3 fw-bold text-warning">${fmtNum(onTimeRate, 1)}%</div>
                            <div class="text-muted fs-6">${esc(I18N.paidWithinTerms)}</div>
                        </div>
                    </div>
                    <div class="mt-3 small text-muted">${fmtNum(s.paid_late, 0)} ${esc(I18N.paidLate)}</div>
                </div></div>
            </div>
        `;
    }

    // ─── Speed buckets ─────────────────────────────────────
    function renderSpeedBuckets(buckets) {
        if (!buckets) { $('stSpeedCard')?.classList.add('d-none'); return; }
        $('stSpeedCard')?.classList.remove('d-none');

        const meta = [
            { key: 'same_day',   label: I18N.sameDay,    color: 'success', icon: 'bi-lightning-charge' },
            { key: '1_7_days',   label: I18N.days1_7,    color: 'info',    icon: 'bi-clock' },
            { key: '8_14_days',  label: I18N.days8_14,   color: 'primary', icon: 'bi-calendar3' },
            { key: '15_30_days', label: I18N.days15_30,  color: 'warning', icon: 'bi-calendar-check' },
            { key: '31_60_days', label: I18N.days31_60,  color: 'danger',  icon: 'bi-exclamation-circle' },
            { key: '60_plus',    label: I18N.days60plus, color: 'dark',    icon: 'bi-hourglass-split' },
        ];

        const total = Object.values(buckets).reduce((s, v) => s + Number(v || 0), 0);

        $('stSpeedBuckets').innerHTML = meta.map(m => {
            const count = Number(buckets[m.key] || 0);
            const pct = total > 0 ? (count / total) * 100 : 0;
            return `
                <div class="col-md-6 col-lg-2">
                    <div class="card bg-light-${m.color} h-100"><div class="card-body text-center">
                        <i class="bi ${m.icon} fs-2tx text-${m.color} mb-2"></i>
                        <div class="fs-6 text-muted">${esc(m.label)}</div>
                        <div class="fs-2 fw-bold text-${m.color}">${fmtNum(count, 0)}</div>
                        <div class="fs-8 text-muted">${fmtNum(pct, 1)}%</div>
                    </div></div>
                </div>
            `;
        }).join('');
    }

    // ─── Status funnel ─────────────────────────────────────
    function renderFunnel(rows) {
        if (!rows || rows.length === 0) { $('stFunnelCard')?.classList.add('d-none'); return; }
        $('stFunnelCard')?.classList.remove('d-none');

        const total = rows.reduce((s, r) => s + Number(r.count || 0), 0);

        $('stFunnelBody').innerHTML = rows.map(r => {
            const pct = total > 0 ? (r.count / total) * 100 : 0;
            const color = r.color || STATUS_COLORS[r.status] || 'secondary';
            return `
                <tr>
                    <td class="ps-4"><span class="badge badge-light-${color} fs-7 py-2 px-3">${esc(r.label)}</span></td>
                    <td class="text-center fw-bold">${fmtNum(r.count, 0)}</td>
                    <td class="text-end"><span class="badge badge-light-secondary">${fmtNum(pct, 1)}%</span></td>
                    <td class="pe-4">
                        <div class="progress h-8px w-100 bg-light-${color}">
                            <div class="progress-bar bg-${color}" style="width:${pct}%"></div>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        $('stFunnelFoot').innerHTML = `
            <tr>
                <th class="ps-4">${esc(I18N.totalAverage)}</th>
                <th class="text-center fw-bold">${fmtNum(total, 0)}</th>
                <th class="text-end">100%</th>
                <th class="pe-4"></th>
            </tr>
        `;
    }

    // ─── Time-to-payment metrics ───────────────────────────
    function renderTimeMetrics(s) {
        $('stTimeMetricsCard')?.classList.remove('d-none');

        $('stTimeMetrics').innerHTML = `
            <div class="col-md-3"><div class="border border-dashed border-gray-300 rounded p-4 text-center h-100">
                <div class="fs-7 text-muted text-uppercase mb-2">${esc(I18N.avgDaysToSend)}</div>
                <div class="fs-1 fw-bold text-info">${fmtNum(s.avg_days_to_send, 1)}</div>
                <div class="fs-7 text-muted">${esc(I18N.issueToSend)}</div>
            </div></div>
            <div class="col-md-3"><div class="border border-dashed border-gray-300 rounded p-4 text-center h-100">
                <div class="fs-7 text-muted text-uppercase mb-2">${esc(I18N.avgDaysToView)}</div>
                <div class="fs-1 fw-bold text-primary">${fmtNum(s.avg_days_to_view, 1)}</div>
                <div class="fs-7 text-muted">${esc(I18N.sendToView)}</div>
            </div></div>
            <div class="col-md-3"><div class="border border-dashed border-gray-300 rounded p-4 text-center h-100">
                <div class="fs-7 text-muted text-uppercase mb-2">${esc(I18N.avgDaysToPay)}</div>
                <div class="fs-1 fw-bold text-success">${fmtNum(s.avg_days_to_pay, 1)}</div>
                <div class="fs-7 text-muted">${esc(I18N.issueToPay)}</div>
            </div></div>
            <div class="col-md-3"><div class="border border-dashed border-gray-300 rounded p-4 text-center h-100">
                <div class="fs-7 text-muted text-uppercase mb-2">${esc(I18N.median)} ${esc(I18N.avgDaysToPay)}</div>
                <div class="fs-1 fw-bold text-secondary">${fmtNum(s.median_days_to_pay, 1)}</div>
                <div class="fs-7 text-muted">${esc(I18N.issueToPay)}</div>
            </div></div>
        `;

        $('stTimeAlerts').innerHTML = `
            <div class="col-md-6">
                <div class="alert alert-success d-flex align-items-center">
                    <i class="bi bi-rocket fs-2tx text-success me-3"></i>
                    <div>
                        <div class="fs-6 fw-bold">${esc(I18N.fastestPayment)}</div>
                        <div class="fs-4 fw-bold text-success">${fmtNum(s.fastest_payment_days, 0)} ${esc(I18N.days)}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="alert alert-danger d-flex align-items-center">
                    <i class="bi bi-clock fs-2tx text-danger me-3"></i>
                    <div>
                        <div class="fs-6 fw-bold">${esc(I18N.slowestPayment)}</div>
                        <div class="fs-4 fw-bold text-danger">${fmtNum(s.slowest_payment_days, 0)} ${esc(I18N.days)}</div>
                    </div>
                </div>
            </div>
        `;
    }

    // ─── Trend table ───────────────────────────────────────
    function renderTrend(rows, totals, groupBy) {
        if (!rows || rows.length === 0) { $('stTrendCard')?.classList.add('d-none'); return; }
        $('stTrendCard')?.classList.remove('d-none');
        $('stTrendGroupBadge').textContent = capitalize(groupBy || 'monthly');

        $('stTrendBody').innerHTML = rows.map(r => `
            <tr>
                <td class="ps-4"><span class="fw-bold">${esc(r.period)}</span></td>
                <td class="text-center">${fmtNum(r.invoice_count, 0)}</td>
                <td class="text-center text-success">${fmtNum(r.paid_count, 0)}</td>
                <td class="text-end">${money(r.total_value)}</td>
                <td class="text-end text-success">${money(r.paid_value)}</td>
                <td class="text-end text-danger">${money(r.outstanding_value)}</td>
                <td class="text-end">
                    ${r.avg_days_to_pay > 0
                        ? `<span class="badge badge-light-${r.avg_days_to_pay <= 14 ? 'success' : (r.avg_days_to_pay <= 30 ? 'warning' : 'danger')}">${fmtNum(r.avg_days_to_pay, 1)} ${esc(I18N.days)}</span>`
                        : `<span class="text-muted">—</span>`}
                </td>
                <td class="pe-4 text-end">
                    <span class="badge badge-light-${r.collection_rate >= 90 ? 'success' : (r.collection_rate >= 70 ? 'warning' : 'danger')}">${fmtNum(r.collection_rate, 1)}%</span>
                </td>
            </tr>
        `).join('');

        $('stTrendFoot').innerHTML = `
            <tr>
                <th class="ps-4">${esc(I18N.totalAverage)}</th>
                <th class="text-center">${fmtNum(totals.invoice_count, 0)}</th>
                <th class="text-center">${fmtNum(totals.paid_count, 0)}</th>
                <th class="text-end">${money(totals.total_value)}</th>
                <th class="text-end">${money(totals.paid_value)}</th>
                <th class="text-end">${money(totals.outstanding_value)}</th>
                <th class="text-end">${fmtNum(totals.avg_days_to_pay, 1)} ${esc(I18N.days)}</th>
                <th class="pe-4 text-end">${fmtNum(totals.collection_rate, 1)}%</th>
            </tr>
        `;
    }

    // ─── Detail table ──────────────────────────────────────
    function renderTable(rows, pagination) {
        if (!rows || rows.length === 0) { $('stTableCard')?.classList.add('d-none'); return; }
        $('stTableCard')?.classList.remove('d-none');
        $('stTableBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.invoices}`;

        $('stInvoicesBody').innerHTML = rows.map(inv => `
            <tr class="st-row" data-invoice-id="${inv.id}" data-invoice-number="${esc(inv.invoice_number)}">
                <td class="ps-4"><span class="fw-bold text-primary">${esc(inv.invoice_number)}</span></td>
                <td>
                    <span class="fw-semibold">${esc(inv.billing_name || '—')}</span>
                    ${!inv.customer_id ? `<span class="badge badge-light-secondary ms-1">${esc(I18N.guest)}</span>` : ''}
                </td>
                <td><span class="text-muted">${esc(inv.issue_human || '—')}</span></td>
                <td><span class="badge badge-light-${esc(inv.status_color)} fs-7 py-2 px-3">${esc(inv.status_label)}</span></td>
                <td class="text-center">${inv.days_to_send !== null
                    ? `<span class="fw-semibold text-info">${fmtNum(inv.days_to_send, 0)}</span>`
                    : `<span class="text-muted">—</span>`}</td>
                <td class="text-center">${inv.days_to_view !== null
                    ? `<span class="fw-semibold text-primary">${fmtNum(inv.days_to_view, 0)}</span>`
                    : `<span class="text-muted">—</span>`}</td>
                <td class="text-center">${inv.days_to_pay !== null
                    ? `<span class="fw-semibold ${inv.paid_late ? 'text-danger' : 'text-success'}">${fmtNum(inv.days_to_pay, 0)}</span>
                       ${inv.paid_late ? `<br><small class="text-danger">${esc(I18N.late)}</small>` : ''}`
                    : `<span class="text-muted">—</span>`}</td>
                <td class="text-end">${money(inv.total)}</td>
                <td class="pe-4 text-end fw-bold ${inv.balance_due > 0 ? 'text-danger' : 'text-muted'}">${money(inv.balance_due)}</td>
            </tr>
        `).join('');

        renderPagination(pagination);
    }

    // ─── Pagination ────────────────────────────────────────
    function renderPagination(p) {
        const info = $('stPaginationInfo');
        const ul   = $('stPagination');
        const wrap = $('stPaginationWrap');
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
        const el = document.getElementById('stDrawer');
        if (!el) return null;
        drawerBs = drawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return drawerBs;
    }

    async function openDrawer(invoiceId, invoiceNumber) {
        const inst = getDrawer();
        if (!inst) return;

        $('stDrawerSubtitle').textContent = invoiceNumber;
        $('stDrawerSkeleton')?.classList.remove('d-none');
        const content = $('stDrawerContent');
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
            console.error('[st-drawer] failed:', err);
            $('stDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderDrawer(d) {
        $('stDrawerSkeleton')?.classList.add('d-none');
        const content = $('stDrawerContent');
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
            ${s.error ? `<tr><td colspan="4"><pre class="st-notes text-danger">${esc(s.error)}</pre></td></tr>` : ''}
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
                <div class="col-md-3"><div class="card card-flush bg-light-info"><div class="card-body py-3 text-center">
                    <span class="text-muted fs-8">${esc(I18N.sendDays)}</span>
                    <div class="fs-5 fw-bold text-info">${d.days_to_send !== null ? fmtNum(d.days_to_send, 0) : '—'}</div>
                </div></div></div>
                <div class="col-md-3"><div class="card card-flush bg-light-primary"><div class="card-body py-3 text-center">
                    <span class="text-muted fs-8">${esc(I18N.viewDays)}</span>
                    <div class="fs-5 fw-bold text-primary">${d.days_to_view !== null ? fmtNum(d.days_to_view, 0) : '—'}</div>
                </div></div></div>
                <div class="col-md-3"><div class="card card-flush bg-light-${d.paid_late ? 'danger' : 'success'}"><div class="card-body py-3 text-center">
                    <span class="text-muted fs-8">${esc(I18N.payDays)}</span>
                    <div class="fs-5 fw-bold text-${d.paid_late ? 'danger' : 'success'}">${d.days_to_pay !== null ? fmtNum(d.days_to_pay, 0) : '—'}</div>
                </div></div></div>
                <div class="col-md-3"><div class="card card-flush bg-light-danger"><div class="card-body py-3 text-center">
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
                <div class="border rounded p-3 text-gray-800 st-notes">${esc(d.notes)}</div>
            ` : ''}
        `;
    }

    // ─── Export ────────────────────────────────────────────
    function exportExcel() {
        const table = $('stInvoicesTable');
        if (!table || typeof XLSX === 'undefined') {
            window.toastr?.error('Excel export unavailable.');
            return;
        }
        try {
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, 'Status Trends');
            XLSX.writeFile(wb, 'invoice_status_trends.xlsx');
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

    return { load };
})();
</script>