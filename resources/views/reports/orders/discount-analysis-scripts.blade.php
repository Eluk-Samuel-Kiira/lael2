<script>
window.DiscountAnalysis = (function () {
    'use strict';

    const DATA_ROUTE  = window.DA_ROUTES.data;
    const ORDER_ROUTE = window.DA_ROUTES.order;
    const CURRENCY    = window.REPORT_CURRENCY || '';
    const BOOT        = window.DA_BOOTSTRAP || {};
    const I18N        = window.DA_I18N || {};

    const state = {
        startDate:    BOOT.startDate    || '',
        endDate:      BOOT.endDate      || '',
        locationId:   BOOT.locationId   || '',
        departmentId: BOOT.departmentId || '',
        employeeId:   BOOT.employeeId   || '',
        perPage:      Number(BOOT.perPage || 25),
        page:         1,
    };

    let dayChart    = null;
    let hourChart   = null;
    let rangeChart  = null;
    let drawerBs    = null;
    let inFlight    = false;
    let lastPayload = null;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('daApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('daClearBtn')?.addEventListener('click', resetFilters);
        $('daExportBtn')?.addEventListener('click', exportExcel);
        $('daPrintBtn')?.addEventListener('click', () => window.print());

        $('daOrdersBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.da-row[data-order-id]');
            if (!row) return;
            openDrawer(row.dataset.orderId, row.dataset.orderNumber);
        });

        document.getElementById('daDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('daDrawerContent').innerHTML = '';
            $('daDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate    = $('daStartDate')?.value    || '';
        state.endDate      = $('daEndDate')?.value      || '';
        state.locationId   = $('daLocation')?.value     || '';
        state.departmentId = $('daDepartment')?.value   || '';
        state.employeeId   = $('daEmployee')?.value     || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.departmentId = state.employeeId = '';
        state.page = 1;
        ['daStartDate','daEndDate'].forEach(id => { if ($(id)) $(id).value = ''; });
        if (window.jQuery) {
            jQuery('#daLocation').val(null).trigger('change.select2');
            jQuery('#daDepartment').val(null).trigger('change.select2');
            jQuery('#daEmployee').val(null).trigger('change.select2');
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
        if (state.employeeId)   params.set('employee_id', state.employeeId);

        try {
            const res = await fetch(`${DATA_ROUTE}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            lastPayload = json.data;
            render(json.data);
            showSkeleton(false);
        } catch (err) {
            console.error('[discount-analysis] load failed:', err);
            window.toastr?.error('Failed to load discount analysis.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('daSkeleton')?.classList.toggle('d-none', !v);
        $('daContent')?.classList.toggle('d-none', v);
    }

    function render(d) {
        if (!d || !d.summary || d.summary.total_discounted_orders === 0) {
            $('daEmpty')?.classList.remove('d-none');
            ['daSummaryCards','daEffectiveness'].forEach(id => $(id) && ($(id).innerHTML = ''));
            ['daDayCard','daHourCard','daRangeCard','daRangeTableCard','daByEmployeeCard','daOrdersCard']
                .forEach(id => $(id)?.classList.add('d-none'));
            [dayChart, hourChart, rangeChart].forEach(c => c && c.destroy());
            dayChart = hourChart = rangeChart = null;
            return;
        }
        $('daEmpty')?.classList.add('d-none');
        renderSummary(d.summary, d.by_employee);
        renderEffectiveness(d.discount_effectiveness);
        renderDayChart(d.by_day);
        renderHourChart(d.by_hour);
        renderRangeChart(d.ranges, d.summary.total_discounted_orders);
        renderRangeTable(d.ranges, d.summary.total_discounted_orders);
        renderByEmployee(d.by_employee, d.summary.total_discount_amount);
        renderOrders(d.orders, d.pagination, d.page_totals, d.grand_totals);
    }

    // ─── Summary ────────────────────────────────────────────
    function renderSummary(s, byEmp) {
        const topEmp = byEmp && byEmp.length > 0 ? byEmp[0] : null;
        const topLabel = topEmp ? `${topEmp.name}: ${money(topEmp.total_discount_given)}` : I18N.none;
        $('daSummaryCards').innerHTML = `
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-primary border border-primary border-dashed h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-primary">${fmtNum(s.total_discounted_orders, 0)}</div>
                    <div class="text-muted">${esc(I18N.discountedOrders)}</div>
                    <span class="badge badge-light-primary mt-2">${fmtNum(s.discount_rate, 2)}% ${esc(I18N.discountRate)}</span>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-success border border-success border-dashed h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-success">${money(s.total_discount_amount)}</div>
                    <div class="text-muted">${esc(I18N.totalDiscountGiven)}</div>
                    <span class="badge badge-light-success mt-2">${money(s.average_discount_per_order)} ${esc(I18N.average)}</span>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-info border border-info border-dashed h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-info">${fmtNum(s.max_discount, 2)}</div>
                    <div class="text-muted">${esc(I18N.discountAmount)} (max)</div>
                    <span class="badge badge-light-info mt-2">${money(s.min_discount)} (min)</span>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-warning border border-warning border-dashed h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-warning">${fmtNum(byEmp?.length || 0, 0)}</div>
                    <div class="text-muted">${esc(I18N.employeesGivingDiscounts)}</div>
                    <span class="badge badge-light-warning mt-2">${esc(truncate(topLabel, 32))}</span>
                </div></div>
            </div>
        `;
    }

    // ─── Effectiveness comparison ───────────────────────────
    function renderEffectiveness(e) {
        const positive = e.percentage_difference >= 0;
        const color = positive ? 'success' : 'danger';
        $('daEffectiveness').innerHTML = `
            <div class="col-md-4"><div class="card bg-light-success border border-success border-dashed h-100"><div class="card-body text-center">
                <div class="fs-1 fw-bold text-success">${fmtNum(e.with_discount_count, 0)}</div>
                <div class="text-muted">${esc(I18N.ordersWithDiscount)}</div>
                <span class="badge badge-light-success mt-2">${money(e.with_discount_avg)} ${esc(I18N.avgOrderValue)}</span>
            </div></div></div>
            <div class="col-md-4"><div class="card bg-light-primary border border-primary border-dashed h-100"><div class="card-body text-center">
                <div class="fs-1 fw-bold text-primary">${fmtNum(e.without_discount_count, 0)}</div>
                <div class="text-muted">${esc(I18N.ordersWithoutDiscount)}</div>
                <span class="badge badge-light-primary mt-2">${money(e.without_discount_avg)} ${esc(I18N.avgOrderValue)}</span>
            </div></div></div>
            <div class="col-md-4"><div class="card bg-light-${color} border border-${color} border-dashed h-100"><div class="card-body text-center">
                <div class="fs-1 fw-bold text-${color}">${fmtNum(e.percentage_difference, 1)}%</div>
                <div class="text-muted">${esc(I18N.valueDifference)}</div>
                <span class="badge badge-light-${color} mt-2">${money(e.difference)} ${esc(I18N.perOrder)}</span>
            </div></div></div>
        `;
    }

    // ─── Day chart ──────────────────────────────────────────
    function renderDayChart(rows) {
        if (!rows || rows.length === 0) {
            $('daDayCard')?.classList.add('d-none');
            if (dayChart) { dayChart.destroy(); dayChart = null; }
            return;
        }
        $('daDayCard')?.classList.remove('d-none');
        const el = $('daDayChart');
        if (!el) return;

        const options = {
            series: [
                { name: I18N.discountCount, data: rows.map(r => Number(r.discount_count)), type: 'bar' },
                { name: I18N.discountAmount, data: rows.map(r => Number(r.total_amount)), type: 'line' },
            ],
            chart: { type: 'bar', height: 300, fontFamily: 'inherit', toolbar: { show: false } },
            plotOptions: { bar: { horizontal: false, columnWidth: '60%' } },
            stroke: { width: [0, 3], curve: 'smooth' },
            xaxis: { categories: rows.map(r => r.day), labels: { rotate: -45 } },
            yaxis: [
                { title: { text: I18N.discountCount } },
                { opposite: true, title: { text: `${I18N.discountAmount} (${CURRENCY})` }, labels: { formatter: v => money(v) } },
            ],
            colors: ['#3E97FF', '#50CD89'],
            tooltip: {
                shared: true, intersect: false,
                y: { formatter: (v, { seriesIndex }) => seriesIndex === 0 ? `${v} ${I18N.orders}` : money(v) },
            },
            legend: { position: 'top', horizontalAlign: 'center' },
        };
        if (dayChart) dayChart.updateOptions(options, false, true);
        else { dayChart = new ApexCharts(el, options); dayChart.render(); }
    }

    // ─── Hour chart ─────────────────────────────────────────
    function renderHourChart(rows) {
        if (!rows || rows.length === 0) {
            $('daHourCard')?.classList.add('d-none');
            if (hourChart) { hourChart.destroy(); hourChart = null; }
            return;
        }
        $('daHourCard')?.classList.remove('d-none');
        const el = $('daHourChart');
        if (!el) return;

        const options = {
            series: [
                { name: I18N.discountCount, data: rows.map(r => Number(r.discount_count)), type: 'bar' },
                { name: I18N.discountAmount, data: rows.map(r => Number(r.total_amount)), type: 'line' },
            ],
            chart: { type: 'bar', height: 300, fontFamily: 'inherit', toolbar: { show: false } },
            plotOptions: { bar: { horizontal: false, columnWidth: '60%' } },
            stroke: { width: [0, 3], curve: 'smooth' },
            xaxis: { categories: rows.map(r => r.hour_formatted), labels: { rotate: -45 } },
            yaxis: [
                { title: { text: I18N.discountCount } },
                { opposite: true, title: { text: `${I18N.discountAmount} (${CURRENCY})` }, labels: { formatter: v => money(v) } },
            ],
            colors: ['#7239EA', '#FFC700'],
            tooltip: {
                shared: true, intersect: false,
                y: { formatter: (v, { seriesIndex }) => seriesIndex === 0 ? `${v} ${I18N.orders}` : money(v) },
            },
            legend: { position: 'top', horizontalAlign: 'center' },
        };
        if (hourChart) hourChart.updateOptions(options, false, true);
        else { hourChart = new ApexCharts(el, options); hourChart.render(); }
    }

    // ─── Range chart ────────────────────────────────────────
    function renderRangeChart(ranges, total) {
        if (!ranges || total === 0) {
            $('daRangeCard')?.classList.add('d-none');
            if (rangeChart) { rangeChart.destroy(); rangeChart = null; }
            return;
        }
        $('daRangeCard')?.classList.remove('d-none');
        const el = $('daRangeChart');
        if (!el) return;

        const labels = Object.keys(ranges);
        const values = Object.values(ranges);

        const options = {
            series: values,
            chart: { type: 'donut', height: 300, fontFamily: 'inherit', toolbar: { show: false } },
            labels,
            colors: ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C', '#A1A5B7'],
            plotOptions: { pie: { donut: {
                size: '65%',
                labels: {
                    show: true,
                    total: {
                        show: true, label: I18N.orders,
                        formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0),
                    },
                },
            }}},
            legend: { position: 'bottom', horizontalAlign: 'center' },
            dataLabels: { enabled: true, formatter: (v) => Number(v).toFixed(1) + '%' },
            tooltip: { y: { formatter: v => `${v} ${I18N.orders}` } },
        };
        if (rangeChart) rangeChart.updateOptions(options, false, true);
        else { rangeChart = new ApexCharts(el, options); rangeChart.render(); }
    }

    function renderRangeTable(ranges, total) {
        if (!ranges || total === 0) {
            $('daRangeTableCard')?.classList.add('d-none');
            return;
        }
        $('daRangeTableCard')?.classList.remove('d-none');
        $('daRangeBody').innerHTML = Object.entries(ranges).map(([range, count]) => {
            const pct = total > 0 ? (count / total) * 100 : 0;
            return `
                <tr>
                    <td class="ps-4"><span class="badge badge-light-primary">${esc(range)}</span></td>
                    <td>${fmtNum(count, 0)}</td>
                    <td class="pe-4">
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:6px;">
                                <div class="progress-bar bg-primary" style="width:${pct}%;"></div>
                            </div>
                            <span class="fw-bold min-w-45px text-end">${fmtNum(pct, 1)}%</span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // ─── By employee ────────────────────────────────────────
    function renderByEmployee(rows, totalDiscount) {
        if (!rows || rows.length === 0) { $('daByEmployeeCard')?.classList.add('d-none'); return; }
        $('daByEmployeeCard')?.classList.remove('d-none');
        $('daByEmployeeBadge').textContent = `${fmtNum(rows.length, 0)} ${I18N.employee}`;

        $('daByEmployeeBody').innerHTML = rows.map(e => {
            const pct = totalDiscount > 0 ? (e.total_discount_given / totalDiscount) * 100 : 0;
            return `
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-35px symbol-circle me-3">
                                <div class="symbol-label bg-light-primary">
                                    <span class="text-primary fw-bold">${esc(initials(e.name))}</span>
                                </div>
                            </div>
                            <div>
                                <div class="fw-bold">${esc(e.name)}</div>
                                ${e.email ? `<small class="text-muted">${esc(e.email)}</small>` : ''}
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-light-primary">${fmtNum(e.order_count, 0)}</span></td>
                    <td class="text-end text-success fw-bold">${money(e.total_discount_given)}</td>
                    <td class="text-end text-info">${money(e.average_discount)}</td>
                    <td class="text-end text-warning">${money(e.discount_per_order)}</td>
                    <td class="pe-4">
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:6px;">
                                <div class="progress-bar bg-primary" style="width:${Math.min(pct, 100)}%;"></div>
                            </div>
                            <span class="fw-bold min-w-45px text-end">${fmtNum(pct, 1)}%</span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // ─── Orders table ───────────────────────────────────────
    function renderOrders(rows, pagination, pageTotals, grandTotals) {
        if (!rows || rows.length === 0) { $('daOrdersCard')?.classList.add('d-none'); return; }
        $('daOrdersCard')?.classList.remove('d-none');
        $('daOrdersBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.orders}`;

        $('daOrdersBody').innerHTML = rows.map(o => `
            <tr class="da-row" data-order-id="${o.id}" data-order-number="${esc(o.order_number)}">
                <td class="ps-4"><span class="text-primary fw-bold">#${esc(o.order_number)}</span></td>
                <td>
                    <div class="fw-bold">${esc(o.customer_name)}</div>
                    ${o.customer_email ? `<small class="text-muted">${esc(o.customer_email)}</small>` : ''}
                </td>
                <td class="text-end text-gray-600">${money(o.pre_discount)}</td>
                <td class="text-end text-danger fw-bold">${money(o.discount_total)}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="progress w-100 me-3" style="height:6px;">
                            <div class="progress-bar bg-danger" style="width:${Math.min(o.discount_pct, 100)}%;"></div>
                        </div>
                        <span class="fw-bold min-w-45px text-end">${fmtNum(o.discount_pct, 1)}%</span>
                    </div>
                </td>
                <td class="text-end text-success fw-bold">${money(o.order_total)}</td>
                <td>${esc(o.employee)}</td>
                <td>${esc(o.created_at || '—')}</td>
                <td class="pe-4"><span class="badge badge-light-${esc(o.status_color)}">${esc(capitalize(o.status))}</span></td>
            </tr>
        `).join('');

        $('daOrdersFoot').innerHTML = `
            <tr>
                <th colspan="2" class="text-end">${esc(I18N.totals)} (page)</th>
                <th class="text-end">${money(pageTotals.order_total)}</th>
                <th class="text-end">${money(pageTotals.discount_total)}</th>
                <th colspan="5"></th>
            </tr>
            ${pagination.total > (pagination.to - pagination.from + 1) ? `
            <tr>
                <th colspan="2" class="text-end text-muted">${esc(I18N.totals)} (all)</th>
                <th class="text-end text-muted">${money(grandTotals.order_total)}</th>
                <th class="text-end text-danger">${money(grandTotals.discount_total)}</th>
                <th colspan="5"></th>
            </tr>` : ''}
        `;

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('daPaginationInfo');
        const ul   = $('daPagination');
        const wrap = $('daPaginationWrap');
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

    // ─── Drawer ─────────────────────────────────────────────
    function getDrawer() {
        const el = document.getElementById('daDrawer');
        if (!el) return null;
        drawerBs = drawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return drawerBs;
    }

    async function openDrawer(orderId, orderNumber) {
        const inst = getDrawer();
        if (!inst) return;

        $('daDrawerSubtitle').textContent = `#${orderNumber}`;
        $('daDrawerSkeleton')?.classList.remove('d-none');
        const content = $('daDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        inst.show();

        try {
            const url = ORDER_ROUTE.replace('__ID__', orderId);
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            renderDrawer(json.data);
        } catch (err) {
            console.error('[da-drawer] failed:', err);
            $('daDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderDrawer(d) {
        $('daDrawerSkeleton')?.classList.add('d-none');
        const content = $('daDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        const itemRows = (d.items || []).map(i => `
            <tr>
                <td><span class="fw-bold">${esc(i.name)}</span><br><small class="text-muted">${esc(i.sku)}</small></td>
                <td class="text-center">${fmtNum(i.quantity, 0)}</td>
                <td class="text-end">${money(i.unit_price)}</td>
                <td class="text-end text-danger">${money(i.line_discount)}</td>
                <td class="text-end text-info">${money(i.tax_amount)}</td>
                <td class="text-end fw-bold">${money(i.total_price)}</td>
            </tr>
        `).join('');

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">#${esc(d.order_number)}</h4>
                    <div class="text-muted fs-7">${esc(d.customer?.name || '—')} · ${esc(d.created_at || '')}</div>
                </div>
                <span class="badge badge-light-primary fs-6 py-2 px-3">${fmtNum(d.discount_pct, 1)}%</span>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3"><div class="card card-flush bg-light-secondary"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.subtotalBeforeBargain)}</span>
                    <div class="fs-6 fw-bold">${money(d.subtotal_before_bargain)}</div>
                </div></div></div>
                <div class="col-6 col-md-3"><div class="card card-flush bg-light-danger"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.totalDiscount)}</span>
                    <div class="fs-6 fw-bold text-danger">${money(d.discount_total)}</div>
                </div></div></div>
                <div class="col-6 col-md-3"><div class="card card-flush bg-light-info"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.tax)}</span>
                    <div class="fs-6 fw-bold text-info">${money(d.tax)}</div>
                </div></div></div>
                <div class="col-6 col-md-3"><div class="card card-flush bg-light-success"><div class="card-body text-center py-3">
                    <span class="text-muted fs-8">${esc(I18N.total)}</span>
                    <div class="fs-6 fw-bold text-success">${money(d.order_total || d.total)}</div>
                </div></div></div>
            </div>

            {{-- Breakdown of how the discount was constructed --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card card-flush bg-light-warning border border-warning border-dashed"><div class="card-body py-3">
                    <span class="text-muted fs-8">${esc(I18N.lineDiscount)}</span>
                    <div class="fs-6 fw-bold text-warning">${money(d.line_discount_total)}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card card-flush bg-light-danger border border-danger border-dashed"><div class="card-body py-3">
                    <span class="text-muted fs-8">${esc(I18N.bargainDiscount)}</span>
                    <div class="fs-6 fw-bold text-danger">${money(d.bargain_discount)}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card card-flush bg-light-primary border border-primary border-dashed"><div class="card-body py-3">
                    <span class="text-muted fs-8">${esc(I18N.discountPercentage)}</span>
                    <div class="fs-6 fw-bold text-primary">${fmtNum(d.discount_pct, 2)}%</div>
                </div></div></div>
            </div>

            <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-2 text-primary"></i>${esc(I18N.items)}</h6>
            <div class="table-responsive mb-4">
                <table class="table table-sm table-bordered">
                    <thead class="bg-light"><tr>
                        <th>${esc(I18N.product)}</th>
                        <th class="text-center">${esc(I18N.qty)}</th>
                        <th class="text-end">${esc(I18N.unitPrice)}</th>
                        <th class="text-end">${esc(I18N.lineDiscount)}</th>
                        <th class="text-end">${esc(I18N.tax)}</th>
                        <th class="text-end">${esc(I18N.lineTotal)}</th>
                    </tr></thead>
                    <tbody>${itemRows || `<tr><td colspan="6" class="text-center text-muted py-3">${esc(I18N.noData)}</td></tr>`}</tbody>
                </table>
            </div>
        `;
    }

    // ─── Export ─────────────────────────────────────────────
    function exportExcel() {
        const table = $('daOrdersTable');
        if (!table || typeof XLSX === 'undefined') {
            window.toastr?.error('Excel export unavailable.');
            return;
        }
        try {
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, 'Discounts');
            XLSX.writeFile(wb, 'discount_analysis.xlsx');
        } catch (e) {
            window.toastr?.error('Export failed: ' + e.message);
        }
    }

    // ─── Helpers ────────────────────────────────────────────
    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t); return d.innerHTML;
    }
    function capitalize(s) { return String(s || '').charAt(0).toUpperCase() + String(s || '').slice(1); }
    function truncate(s, len) { return !s ? '' : (s.length > len ? s.substring(0, len) + '…' : s); }
    function initials(name) {
        if (!name) return '?';
        return String(name).trim().split(/\s+/).slice(0, 2).map(p => p[0]).join('').toUpperCase();
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