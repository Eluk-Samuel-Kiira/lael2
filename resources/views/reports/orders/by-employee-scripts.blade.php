<script>
window.ByEmployee = (function () {
    'use strict';

    const DATA_ROUTE   = window.EP_ROUTES.data;
    const ORDERS_ROUTE = window.EP_ROUTES.orders;
    const CURRENCY     = window.REPORT_CURRENCY || '';
    const BOOT         = window.EP_BOOTSTRAP || {};
    const I18N         = window.EP_I18N || {};

    const state = {
        startDate:    BOOT.startDate    || '',
        endDate:      BOOT.endDate      || '',
        locationId:   BOOT.locationId   || '',
        departmentId: BOOT.departmentId || '',
        employeeId:   BOOT.employeeId   || '',
        minSales:     BOOT.minSales     ?? '',
        maxSales:     BOOT.maxSales     ?? '',
        perPage:      Number(BOOT.perPage || 15),
        page:         1,
    };

    let chart    = null;
    let drawerBs = null;
    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('epApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('epClearBtn')?.addEventListener('click', resetFilters);
        $('epExportBtn')?.addEventListener('click', exportCsv);

        $('epEmployeesBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.ep-row[data-employee-id]');
            if (!row) return;
            openDrawer(row.dataset.employeeId, row.dataset.employeeName);
        });

        document.getElementById('epDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('epDrawerContent').innerHTML = '';
            $('epDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate    = $('epStartDate')?.value    || '';
        state.endDate      = $('epEndDate')?.value      || '';
        state.locationId   = $('epLocation')?.value     || '';
        state.departmentId = $('epDepartment')?.value   || '';
        state.employeeId   = $('epEmployee')?.value     || '';
        state.minSales     = $('epMinSales')?.value     ?? '';
        state.maxSales     = $('epMaxSales')?.value     ?? '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.departmentId = state.employeeId = '';
        state.minSales = state.maxSales = '';
        state.page = 1;
        ['epStartDate','epEndDate','epMinSales','epMaxSales'].forEach(id => { if ($(id)) $(id).value = ''; });
        if (window.jQuery) {
            jQuery('#epLocation').val(null).trigger('change.select2');
            jQuery('#epDepartment').val(null).trigger('change.select2');
            jQuery('#epEmployee').val(null).trigger('change.select2');
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
        if (state.minSales !== '' && state.minSales !== null) params.set('min_sales', state.minSales);
        if (state.maxSales !== '' && state.maxSales !== null) params.set('max_sales', state.maxSales);

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
            console.error('[by-employee] load failed:', err);
            window.toastr?.error('Failed to load employee report.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('epSkeleton')?.classList.toggle('d-none', !v);
        $('epContent')?.classList.toggle('d-none', v);
    }

    function render(d) {
        if (!d || !d.summary || d.summary.total_employees === 0) {
            $('epEmpty')?.classList.remove('d-none');
            ['epSummaryCards','epRatingCards'].forEach(id => $(id) && ($(id).innerHTML = ''));
            ['epChartCard','epTableCard','epTop5Card','epBottom5Card'].forEach(id => $(id)?.classList.add('d-none'));
            if (chart) { chart.destroy(); chart = null; }
            return;
        }
        $('epEmpty')?.classList.add('d-none');
        renderSummary(d.summary);
        renderRatingCards(d.rating_counts, d.summary.total_employees);
        renderChart(d.chart);
        renderTable(d.employees, d.pagination, d.summary);
        renderTop5(d.top_five);
        renderBottom5(d.bottom_five);
    }

    // ─── Summary cards ───────────────────────────────────────
    function renderSummary(s) {
        $('epSummaryCards').innerHTML = `
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-primary h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-primary">${fmtNum(s.total_employees, 0)}</div>
                    <div class="text-muted">${esc(I18N.totalEmployees)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-success h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-success">${money(s.total_sales)}</div>
                    <div class="text-muted">${esc(I18N.totalSales)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-info h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-info">${fmtNum(s.total_orders, 0)}</div>
                    <div class="text-muted">${esc(I18N.totalOrders)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-warning h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-warning">${money(s.avg_sales_per_day)}</div>
                    <div class="text-muted">${esc(I18N.avgSalesPerDay)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-danger h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-danger">${money(s.avg_order_value)}</div>
                    <div class="text-muted">${esc(I18N.avgOrderValue)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-secondary h-100"><div class="card-body text-center">
                    <div class="fs-6 fw-bold text-gray-800">${esc(truncate(s.top_employee, 15))}</div>
                    <div class="text-muted">${money(s.top_employee_sales)}</div>
                    <div class="text-muted small">${esc(I18N.topEmployee)}</div>
                </div></div>
            </div>
        `;
    }

    // ─── Rating cards ────────────────────────────────────────
    function renderRatingCards(ratingCounts, total) {
        const config = [
            { key: 'Excellent',          color: 'success', label: I18N.excellent },
            { key: 'Good',               color: 'primary', label: I18N.good },
            { key: 'Average',            color: 'warning', label: I18N.average },
            { key: 'Needs Improvement',  color: 'danger',  label: I18N.needsImprovement },
        ];

        $('epRatingCards').innerHTML = config.map(c => {
            const count = ratingCounts[c.key] || 0;
            const pct   = total > 0 ? (count / total) * 100 : 0;
            return `
                <div class="col-md-6 col-lg-3">
                    <div class="card bg-light-${c.color} border border-${c.color} border-dashed">
                        <div class="card-body text-center">
                            <div class="fs-1 fw-bold text-${c.color}">${fmtNum(count, 0)}</div>
                            <div class="text-gray-600">${esc(c.label)}</div>
                            <div class="progress mt-2" style="height:6px;">
                                <div class="progress-bar bg-${c.color}" style="width:${pct}%;"></div>
                            </div>
                            <span class="badge badge-light-${c.color} mt-2">${fmtNum(pct, 1)}%</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // ─── Chart ───────────────────────────────────────────────
    function renderChart(rows) {
        const el = $('epChart');
        if (!el || !rows || rows.length === 0) {
            $('epChartCard')?.classList.add('d-none');
            if (chart) { chart.destroy(); chart = null; }
            return;
        }
        $('epChartCard')?.classList.remove('d-none');

        const categories = rows.map(r => r.name);
        const sales      = rows.map(r => Number(r.sales));
        const orders     = rows.map(r => Number(r.orders));

        const options = {
            series: [
                { name: I18N.totalSales,  data: sales,  type: 'bar' },
                { name: I18N.totalOrders, data: orders, type: 'line' },
            ],
            chart:  { type: 'bar', height: 400, fontFamily: 'inherit', toolbar: { show: true, tools: { download: true } } },
            plotOptions: { bar: { horizontal: false, columnWidth: '60%', borderRadius: 4 } },
            stroke: { width: [0, 3] },
            xaxis:  { categories, labels: { rotate: -45 } },
            yaxis: [
                { title: { text: `${I18N.totalSales} (${CURRENCY})` }, labels: { formatter: v => CURRENCY + Number(v).toLocaleString() } },
                { opposite: true, title: { text: I18N.totalOrders } },
            ],
            colors: ['#3E97FF', '#50CD89'],
            tooltip: {
                shared: true, intersect: false,
                y: {
                    formatter: (v, { seriesIndex }) => seriesIndex === 0
                        ? CURRENCY + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 })
                        : Number(v).toLocaleString(),
                },
            },
            legend: { position: 'top', horizontalAlign: 'center' },
        };

        if (chart) chart.updateOptions(options, false, true);
        else { chart = new ApexCharts(el, options); chart.render(); }
    }

    // ─── Employees table ────────────────────────────────────
    function renderTable(rows, pagination, summary) {
        if (!rows || rows.length === 0) {
            $('epTableCard')?.classList.add('d-none');
            return;
        }
        $('epTableCard')?.classList.remove('d-none');
        $('epTableBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.employee}`;

        $('epEmployeesBody').innerHTML = rows.map((e, i) => {
            const rank = (pagination.from || 1) + i;
            const medal = rank <= 3
                ? `<span class="badge badge-light-${rank === 1 ? 'danger' : (rank === 2 ? 'warning' : 'info')}">
                     <i class="bi bi-${rank === 1 ? 'trophy' : 'award'} me-1"></i>${rank}
                   </span>`
                : '';

            return `
                <tr class="ep-row" data-employee-id="${e.id}" data-employee-name="${esc(e.first_name + ' ' + e.last_name)}">
                    <td class="ps-4 fw-bold">${rank} ${medal}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-40px symbol-circle me-3">
                                <div class="symbol-label bg-light-${esc(e.rating_color)}">
                                    <i class="bi bi-person fs-2"></i>
                                </div>
                            </div>
                            <div>
                                <div class="fw-bold">${esc(e.first_name + ' ' + e.last_name)}</div>
                                <small class="text-muted">${esc(e.email || '')}</small>
                                <div class="mt-1"><span class="badge badge-light-${esc(e.rating_color)} badge-sm">${esc(e.performance_rating)}</span></div>
                            </div>
                        </div>
                    </td>
                    <td class="text-center"><span class="badge badge-light-primary">${fmtNum(e.order_count, 0)}</span></td>
                    <td class="text-end fw-bold text-success">${money(e.total_sales)}</td>
                    <td class="text-end text-info">${money(e.total_tax_collected)}</td>
                    <td class="text-end text-warning">${money(e.total_discount_given)}</td>
                    <td class="text-end">${money(e.average_order_value)}</td>
                    <td class="text-end text-danger">${money(e.largest_sale)}</td>
                    <td class="text-end text-secondary">${money(e.smallest_sale)}</td>
                    <td class="text-center"><span class="badge badge-light-info">${fmtNum(e.unique_customers, 0)}</span></td>
                    <td class="text-center">
                        <span class="badge badge-light-${e.orders_per_day >= 5 ? 'success' : (e.orders_per_day >= 2 ? 'warning' : 'danger')}">
                            ${fmtNum(e.orders_per_day, 1)}
                        </span>
                    </td>
                    <td class="text-end">
                        <span class="badge badge-light-${e.sales_per_day >= 1000 ? 'success' : (e.sales_per_day >= 500 ? 'warning' : 'danger')}">
                            ${money(e.sales_per_day)}
                        </span>
                    </td>
                    <td><span class="badge badge-light-${esc(e.rating_color)}">${esc(e.performance_rating)}</span></td>
                    <td>${e.last_sale_date ? esc(formatDate(e.last_sale_date)) : `<span class="text-muted">—</span>`}</td>
                    <td class="pe-4">
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:8px;">
                                <div class="progress-bar bg-${esc(e.rating_color)}" style="width:${Math.min(Number(e.percentage), 100)}%;"></div>
                            </div>
                            <span class="fw-bold min-w-60px text-end">${fmtNum(e.percentage, 1)}%</span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        $('epEmployeesFoot').innerHTML = `
            <tr>
                <th colspan="3" class="text-end">${esc(I18N.totals)}</th>
                <th class="text-end text-success">${money(summary.total_sales)}</th>
                <th class="text-end text-info">${money(summary.total_tax)}</th>
                <th class="text-end text-warning">${money(summary.total_discount)}</th>
                <th colspan="9"></th>
            </tr>
        `;

        renderPagination(pagination);
    }

    // ─── Top / Bottom 5 ──────────────────────────────────────
    function renderTop5(rows) {
        if (!rows || rows.length === 0) { $('epTop5Card')?.classList.add('d-none'); return; }
        $('epTop5Card')?.classList.remove('d-none');
        $('epTop5Body').innerHTML = rows.map((e, i) => `
            <tr>
                <td class="ps-4 fw-bold">${i + 1}</td>
                <td>${esc(e.first_name + ' ' + e.last_name)}</td>
                <td class="text-end pe-4 text-success fw-bold">${money(e.total_sales)}</td>
            </tr>
        `).join('');
    }

    function renderBottom5(rows) {
        if (!rows || rows.length === 0) { $('epBottom5Card')?.classList.add('d-none'); return; }
        $('epBottom5Card')?.classList.remove('d-none');
        $('epBottom5Body').innerHTML = rows.map((e, i) => `
            <tr>
                <td class="ps-4 fw-bold text-danger">${i + 1}</td>
                <td>${esc(e.first_name + ' ' + e.last_name)}</td>
                <td class="text-end pe-4 text-danger fw-bold">${money(e.total_sales)}</td>
            </tr>
        `).join('');
    }

    // ─── Pagination ──────────────────────────────────────────
    function renderPagination(p) {
        const info = $('epPaginationInfo');
        const ul   = $('epPagination');
        const wrap = $('epPaginationWrap');
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
        const el = document.getElementById('epDrawer');
        if (!el) return null;
        drawerBs = drawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return drawerBs;
    }

    async function openDrawer(employeeId, employeeName) {
        const inst = getDrawer();
        if (!inst) return;

        $('epDrawerSubtitle').textContent = employeeName || '';
        $('epDrawerSkeleton')?.classList.remove('d-none');
        const content = $('epDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        inst.show();

        const params = new URLSearchParams();
        if (state.startDate)    params.set('start_date', state.startDate);
        if (state.endDate)      params.set('end_date', state.endDate);
        if (state.locationId)   params.set('location_id', state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);

        try {
            const url = ORDERS_ROUTE.replace('__ID__', employeeId);
            const res = await fetch(`${url}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            renderDrawer(json.data);
        } catch (err) {
            console.error('[ep-drawer] failed:', err);
            $('epDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderDrawer(d) {
        $('epDrawerSkeleton')?.classList.add('d-none');
        const content = $('epDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        if (!d.orders || d.orders.length === 0) {
            content.innerHTML = `<div class="alert alert-light text-center">${esc(I18N.noOrders)}</div>`;
            return;
        }

        const rows = d.orders.map(o => `
            <tr>
                <td>
                    <span class="fw-bold">${esc(o.order_number)}</span>
                    <br><small class="text-muted">${esc(o.created_time || '')}</small>
                </td>
                <td>${esc(o.created_at)}</td>
                <td><span class="badge badge-light-${esc(o.status_color)}">${esc(capitalize(o.status))}</span></td>
                <td>${esc(o.customer)}</td>
                <td>${esc(o.location)}</td>
                <td>${esc(o.department)}</td>
                <td class="text-end">${money(o.total)}</td>
                <td class="text-end text-success">${money(o.paid)}</td>
                <td class="text-end text-${o.balance > 0 ? 'danger' : 'muted'}">${money(o.balance)}</td>
            </tr>
        `).join('');

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">${esc($('epDrawerSubtitle').textContent || '')}</h4>
                    <div class="text-muted fs-7">${fmtNum(d.order_count, 0)} ${esc(I18N.totalOrders)}</div>
                </div>
                <div class="text-end">
                    <div class="fs-3 fw-bold text-success">${money(d.total_sales)}</div>
                    <div class="text-muted fs-8">${esc(I18N.totalSales)}</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>${esc(I18N.orderNumber)}</th>
                            <th>${esc(I18N.date)}</th>
                            <th>${esc(I18N.status)}</th>
                            <th>${esc(I18N.customer)}</th>
                            <th>${esc(I18N.location)}</th>
                            <th>${esc(I18N.department)}</th>
                            <th class="text-end">${esc(I18N.total)}</th>
                            <th class="text-end">${esc(I18N.paid)}</th>
                            <th class="text-end">${esc(I18N.balance)}</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    // ─── Export ─────────────────────────────────────────────
    function exportCsv() {
        const table = $('epEmployeesTable');
        if (!table) { window.toastr?.error('Table not found.'); return; }
        try {
            const rows = table.querySelectorAll('tr');
            const csv = [];
            rows.forEach(row => {
                const cells = row.querySelectorAll('td, th');
                const line = Array.from(cells).map(c => {
                    let t = c.innerText.trim().replace(/[🥇🥈🥉]/g, '').trim();
                    if (t.includes(',')) t = '"' + t + '"';
                    return t;
                });
                csv.push(line.join(','));
            });
            const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'employee_performance.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(link.href);
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