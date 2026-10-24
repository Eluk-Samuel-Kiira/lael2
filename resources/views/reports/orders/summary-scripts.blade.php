<script>
window.OrderSummary = (function () {
    'use strict';

    const ROUTE    = window.OS_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.OS_BOOTSTRAP || {};
    const I18N     = window.OS_I18N || {};

    const state = {
        startDate:    BOOT.startDate    || '',
        endDate:      BOOT.endDate      || '',
        locationId:   BOOT.locationId   || '',
        departmentId: BOOT.departmentId || '',
        orderType:    BOOT.orderType    || '',
        orderStatus:  BOOT.orderStatus  || '',
        perPage:      Number(BOOT.perPage || 15),
        page:         1,
    };

    let inFlight = false;
    let lastPayload = null;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('osApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('osClearBtn')?.addEventListener('click', resetFilters);
        $('osExportBtn')?.addEventListener('click', exportExcel);
        $('osPrintBtn')?.addEventListener('click', () => window.print());
        load();
    });

    function readFilters() {
        state.startDate    = $('osStartDate')?.value     || '';
        state.endDate      = $('osEndDate')?.value       || '';
        state.locationId   = $('osLocation')?.value      || '';
        state.departmentId = $('osDepartment')?.value    || '';
        state.orderType    = $('osType')?.value          || '';
        state.orderStatus  = $('osStatus')?.value        || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.departmentId = '';
        state.orderType = state.orderStatus = '';
        state.page = 1;
        if ($('osStartDate'))  $('osStartDate').value = '';
        if ($('osEndDate'))    $('osEndDate').value   = '';
        if ($('osType'))       $('osType').value      = '';
        if ($('osStatus'))     $('osStatus').value    = '';
        if (window.jQuery) {
            jQuery('#osLocation').val(null).trigger('change.select2');
            jQuery('#osDepartment').val(null).trigger('change.select2');
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
        if (state.orderType)    params.set('order_type', state.orderType);
        if (state.orderStatus)  params.set('order_status', state.orderStatus);

        try {
            const res = await fetch(`${ROUTE}?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            lastPayload = json.data;
            render(json.data);
            showSkeleton(false);
        } catch (err) {
            console.error('[order-summary] load failed:', err);
            window.toastr?.error('Failed to load order summary.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('osSkeleton')?.classList.toggle('d-none', !v);
        $('osContent')?.classList.toggle('d-none', v);
    }

    function render(d) {
        if (!d || !d.summary || d.summary.total_orders === 0) {
            $('osEmpty')?.classList.remove('d-none');
            $('osKpiCards').innerHTML = '';
            $('osPaymentCards').innerHTML = '';
            $('osStatusCard')?.classList.add('d-none');
            $('osTypeCard')?.classList.add('d-none');
            $('osDailyCard')?.classList.add('d-none');
            $('osHourlyCard')?.classList.add('d-none');
            return;
        }
        $('osEmpty')?.classList.add('d-none');
        renderKpis(d.summary);
        renderPaymentCards(d.summary, d.derived);
        renderStatus(d.status_breakdown, d.summary);
        renderType(d.type_breakdown, d.summary);
        renderDaily(d.daily_breakdown, d.daily_pagination, d.daily_totals);
        renderHourly(d.hourly_breakdown, d.hourly_totals);
    }

    // ─── KPI cards ───────────────────────────────────────────
    function renderKpis(s) {
        const cards = `
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-primary h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px me-3">
                                <span class="symbol-label bg-primary">
                                    <i class="bi bi-basket fs-2x text-white"></i>
                                </span>
                            </div>
                            <div>
                                <div class="fs-2 fw-bold">${fmtNum(s.total_orders, 0)}</div>
                                <div class="text-muted fs-6">${esc(I18N.totalOrders)}</div>
                            </div>
                        </div>
                        <div class="mt-3 small text-muted">
                            <span class="badge badge-light-success me-1">${fmtNum(s.completed_orders, 0)}</span> ${esc(I18N.completed)}
                            <span class="badge badge-light-warning ms-2 me-1">${fmtNum(s.confirmed_orders, 0)}</span> ${esc(I18N.confirmed)}
                            <span class="badge badge-light-danger ms-2 me-1">${fmtNum(s.cancelled_orders, 0)}</span> ${esc(I18N.cancelled)}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-success h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px me-3">
                                <span class="symbol-label bg-success">
                                    <i class="bi bi-currency-dollar fs-2x text-white"></i>
                                </span>
                            </div>
                            <div>
                                <div class="fs-2 fw-bold">${money(s.total_sales)}</div>
                                <div class="text-muted fs-6">${esc(I18N.totalSales)}</div>
                            </div>
                        </div>
                        <div class="mt-3 small text-muted">
                            ${esc(I18N.avgOrderValue)}: ${money(s.average_order_value)}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-warning h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px me-3">
                                <span class="symbol-label bg-warning">
                                    <i class="bi bi-calculator fs-2x text-white"></i>
                                </span>
                            </div>
                            <div>
                                <div class="fs-4 fw-bold text-success">${money(s.total_tax)}</div>
                                <div class="text-muted fs-6">${esc(I18N.totalTax)}</div>
                                <div class="fs-4 fw-bold text-danger mt-1">${money(s.total_discount)}</div>
                                <div class="text-muted fs-6">${esc(I18N.totalDiscount)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-secondary h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px me-3">
                                <span class="symbol-label bg-secondary">
                                    <i class="bi bi-graph-up fs-2x text-white"></i>
                                </span>
                            </div>
                            <div>
                                <div class="fs-4 fw-bold text-success">${money(s.max_order_value)}</div>
                                <div class="text-muted fs-6">${esc(I18N.highestOrder)}</div>
                                <div class="fs-4 fw-bold text-danger mt-1">${money(s.min_order_value)}</div>
                                <div class="text-muted fs-6">${esc(I18N.lowestOrder)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('osKpiCards').innerHTML = cards;
    }

    // ─── Payment / progress cards ────────────────────────────
    function renderPaymentCards(s, d) {
        const html = `
            <div class="col-md-4">
                <div class="card bg-light-info h-100">
                    <div class="card-body text-center">
                        <div class="fs-2 fw-bold text-info">${money(s.total_paid)}</div>
                        <div class="text-muted">${esc(I18N.totalPaid)}</div>
                        <div class="progress mt-2" style="height:6px;">
                            <div class="progress-bar bg-info" style="width:${Math.min(d.paid_percent, 100)}%;"></div>
                        </div>
                        <small class="text-muted">${fmtNum(d.paid_percent, 1)}% ${esc(I18N.ofTotalSales)}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light-danger h-100">
                    <div class="card-body text-center">
                        <div class="fs-2 fw-bold text-danger">${money(s.total_balance)}</div>
                        <div class="text-muted">${esc(I18N.outstandingBalance)}</div>
                        <div class="progress mt-2" style="height:6px;">
                            <div class="progress-bar bg-danger" style="width:${Math.min(d.balance_percent, 100)}%;"></div>
                        </div>
                        <small class="text-muted">${fmtNum(d.balance_percent, 1)}% ${esc(I18N.ofTotalSales)}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light-success h-100">
                    <div class="card-body text-center">
                        <div class="fs-2 fw-bold text-success">${fmtNum(s.completed_orders, 0)}</div>
                        <div class="text-muted">${esc(I18N.completedOrders)}</div>
                        <div class="progress mt-2" style="height:6px;">
                            <div class="progress-bar bg-success" style="width:${Math.min(d.completed_percent, 100)}%;"></div>
                        </div>
                        <small class="text-muted">${fmtNum(d.completed_percent, 1)}% ${esc(I18N.completionRate)}</small>
                    </div>
                </div>
            </div>
        `;
        $('osPaymentCards').innerHTML = html;
    }

    // ─── Status breakdown ────────────────────────────────────
    function renderStatus(rows, s) {
        if (!rows || rows.length === 0) {
            $('osStatusCard')?.classList.add('d-none');
            return;
        }
        $('osStatusCard')?.classList.remove('d-none');
        $('osStatusBadge').textContent = `${fmtNum(s.total_orders, 0)} ${I18N.totalOrders}`;

        $('osStatusBody').innerHTML = rows.map(r => {
            const pct = s.total_orders > 0 ? (r.count / s.total_orders) * 100 : 0;
            return `
                <tr>
                    <td class="ps-4">
                        <span class="badge badge-light-${esc(r.color || 'secondary')} fs-7">
                            ${esc(capitalize(r.status || 'unknown'))}
                        </span>
                    </td>
                    <td class="text-center fw-bold">${fmtNum(r.count, 0)}</td>
                    <td class="text-end">${money(r.total_amount)}</td>
                    <td class="text-end">${money(r.average_amount)}</td>
                    <td class="pe-4">
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:8px;">
                                <div class="progress-bar bg-${esc(r.color || 'secondary')}" style="width:${pct}%;"></div>
                            </div>
                            <span class="fw-bold">${fmtNum(pct, 1)}%</span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // ─── Type breakdown ──────────────────────────────────────
    function renderType(rows, s) {
        if (!rows || rows.length === 0) {
            $('osTypeCard')?.classList.add('d-none');
            return;
        }
        $('osTypeCard')?.classList.remove('d-none');
        $('osTypeBadge').textContent = `${fmtNum(s.total_orders, 0)} ${I18N.totalOrders}`;

        $('osTypeBody').innerHTML = rows.map(r => {
            const pct = s.total_orders > 0 ? (r.count / s.total_orders) * 100 : 0;
            return `
                <tr>
                    <td class="ps-4">
                        <span class="badge badge-light-${esc(r.color || 'primary')} fs-7">
                            ${esc(capitalize(r.type || 'unknown'))}
                        </span>
                    </td>
                    <td class="text-center fw-bold">${fmtNum(r.count, 0)}</td>
                    <td class="text-end">${money(r.total_amount)}</td>
                    <td class="text-end">${money(r.average_amount)}</td>
                    <td class="pe-4">
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:8px;">
                                <div class="progress-bar bg-${esc(r.color || 'primary')}" style="width:${pct}%;"></div>
                            </div>
                            <span class="fw-bold">${fmtNum(pct, 1)}%</span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // ─── Daily breakdown (paginated) ─────────────────────────
    function renderDaily(rows, pagination, totals) {
        if (!rows || rows.length === 0) {
            $('osDailyCard')?.classList.add('d-none');
            return;
        }
        $('osDailyCard')?.classList.remove('d-none');
        $('osDailyBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.days}`;

        $('osDailyBody').innerHTML = rows.map(d => `
            <tr>
                <td class="ps-4"><span class="fw-bold">${esc(d.formatted_date || d.date)}</span></td>
                <td>
                    <span class="badge badge-light-${d.is_weekend ? 'danger' : 'secondary'}">${esc(d.day_name || '')}</span>
                </td>
                <td class="text-center"><span class="badge badge-light-primary fs-7">${fmtNum(d.order_count, 0)}</span></td>
                <td class="text-end fw-bold">${money(d.daily_total)}</td>
                <td class="text-end">${money(d.daily_tax)}</td>
                <td class="text-end text-danger">${money(d.daily_discount)}</td>
                <td class="text-end pe-4">${money(d.daily_average)}</td>
            </tr>
        `).join('');

        $('osDailyFoot').innerHTML = `
            <tr>
                <th colspan="2">${esc(I18N.totalAverage)}</th>
                <th class="text-center fw-bold">${fmtNum(totals.order_count, 0)}</th>
                <th class="text-end fw-bold">${money(totals.daily_total)}</th>
                <th class="text-end">${money(totals.daily_tax)}</th>
                <th class="text-end">${money(totals.daily_discount)}</th>
                <th class="text-end pe-4">${money(totals.daily_average)}</th>
            </tr>
        `;

        renderPagination(pagination);
    }

    // ─── Hourly breakdown ────────────────────────────────────
    function renderHourly(rows, totals) {
        if (!rows || rows.length === 0) {
            $('osHourlyCard')?.classList.add('d-none');
            return;
        }
        $('osHourlyCard')?.classList.remove('d-none');
        $('osHourlyBadge').textContent = `${fmtNum(rows.length, 0)} ${I18N.hours}`;

        const periodColors = { Morning: 'success', Afternoon: 'primary', Evening: 'warning', Night: 'secondary' };

        $('osHourlyBody').innerHTML = rows.map(h => `
            <tr>
                <td class="ps-4 fw-bold">${esc(h.hour_formatted || h.hour)}</td>
                <td>
                    <span class="badge badge-light-${periodColors[h.period] || 'secondary'}">${esc(h.period || 'N/A')}</span>
                </td>
                <td class="text-center"><span class="badge badge-light-primary fs-7">${fmtNum(h.order_count, 0)}</span></td>
                <td class="text-end">${money(h.hourly_total)}</td>
                <td class="text-end pe-4">${money(h.hourly_average)}</td>
            </tr>
        `).join('');

        $('osHourlyFoot').innerHTML = `
            <tr>
                <th colspan="2">${esc(I18N.totalAverage)}</th>
                <th class="text-center fw-bold">${fmtNum(totals.order_count, 0)}</th>
                <th class="text-end fw-bold">${money(totals.hourly_total)}</th>
                <th class="text-end pe-4">${money(totals.hourly_average)}</th>
            </tr>
        `;
    }

    // ─── Pagination ──────────────────────────────────────────
    function renderPagination(p) {
        const info = $('osPaginationInfo');
        const ul   = $('osPagination');
        const wrap = $('osPaginationWrap');
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

    // ─── Export ──────────────────────────────────────────────
    function exportExcel() {
        const table = $('osDailyTable');
        if (!table || typeof XLSX === 'undefined') {
            window.toastr?.error('Excel export unavailable.');
            return;
        }
        try {
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, 'Order Summary');
            XLSX.writeFile(wb, 'order_summary_daily.xlsx');
        } catch (e) {
            window.toastr?.error('Export failed: ' + e.message);
        }
    }

    // ─── Helpers ─────────────────────────────────────────────
    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t); return d.innerHTML;
    }
    function capitalize(s) {
        return String(s || '').charAt(0).toUpperCase() + String(s || '').slice(1);
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