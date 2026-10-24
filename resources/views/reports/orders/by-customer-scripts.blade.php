<script>
window.ByCustomer = (function () {
    'use strict';

    const DATA_ROUTE   = window.BC_ROUTES.data;
    const ORDERS_ROUTE = window.BC_ROUTES.orders;
    const CURRENCY     = window.REPORT_CURRENCY || '';
    const BOOT         = window.BC_BOOTSTRAP || {};
    const I18N         = window.BC_I18N || {};

    const state = {
        startDate:     BOOT.startDate     || '',
        endDate:       BOOT.endDate       || '',
        locationId:    BOOT.locationId    || '',
        departmentId:  BOOT.departmentId  || '',
        minSpent:      BOOT.minSpent      ?? '',
        maxSpent:      BOOT.maxSpent      ?? '',
        minOrders:     BOOT.minOrders     ?? '',
        maxOrders:     BOOT.maxOrders     ?? '',
        customerType:  BOOT.customerType  || 'all',
        segmentFilter: BOOT.segmentFilter || '',
        perPage:       Number(BOOT.perPage || 25),
        page:          1,
    };

    let chart    = null;
    let drawerBs = null;
    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('bcApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('bcClearBtn')?.addEventListener('click', resetFilters);
        $('bcExportBtn')?.addEventListener('click', exportCsv);

        $('bcCustomersBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.bc-row[data-customer-key]');
            if (!row) return;
            openDrawer(row.dataset.customerKey, row.dataset.customerName);
        });

        document.getElementById('bcDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('bcDrawerContent').innerHTML = '';
            $('bcDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate     = $('bcStartDate')?.value     || '';
        state.endDate       = $('bcEndDate')?.value       || '';
        state.locationId    = $('bcLocation')?.value      || '';
        state.departmentId  = $('bcDepartment')?.value    || '';
        state.minSpent      = $('bcMinSpent')?.value      ?? '';
        state.maxSpent      = $('bcMaxSpent')?.value      ?? '';
        state.minOrders     = $('bcMinOrders')?.value     ?? '';
        state.maxOrders     = $('bcMaxOrders')?.value     ?? '';
        state.customerType  = $('bcCustomerType')?.value  || 'all';
        state.segmentFilter = $('bcSegment')?.value       || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.departmentId = '';
        state.minSpent = state.maxSpent = state.minOrders = state.maxOrders = '';
        state.customerType = 'all';
        state.segmentFilter = '';
        state.page = 1;
        ['bcStartDate','bcEndDate','bcMinSpent','bcMaxSpent','bcMinOrders','bcMaxOrders','bcSegment'].forEach(id => {
            if ($(id)) $(id).value = '';
        });
        if ($('bcCustomerType')) $('bcCustomerType').value = 'all';
        if (window.jQuery) {
            jQuery('#bcLocation').val(null).trigger('change.select2');
            jQuery('#bcDepartment').val(null).trigger('change.select2');
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
        if (state.customerType)  params.set('customer_type', state.customerType);
        if (state.segmentFilter) params.set('segment_filter', state.segmentFilter);
        if (state.minSpent !== '' && state.minSpent !== null) params.set('min_spent', state.minSpent);
        if (state.maxSpent !== '' && state.maxSpent !== null) params.set('max_spent', state.maxSpent);
        if (state.minOrders !== '' && state.minOrders !== null) params.set('min_orders', state.minOrders);
        if (state.maxOrders !== '' && state.maxOrders !== null) params.set('max_orders', state.maxOrders);

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
            console.error('[by-customer] load failed:', err);
            window.toastr?.error('Failed to load customer report.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('bcSkeleton')?.classList.toggle('d-none', !v);
        $('bcContent')?.classList.toggle('d-none', v);
    }

    function render(d) {
        if (!d || !d.summary || d.summary.total_customers === 0) {
            $('bcEmpty')?.classList.remove('d-none');
            ['bcKpiCards','bcSegments'].forEach(id => $(id) && ($(id).innerHTML = ''));
            ['bcChartCard','bcTableCard','bcTop5Card','bcBottom5Card'].forEach(id => $(id)?.classList.add('d-none'));
            if (chart) { chart.destroy(); chart = null; }
            return;
        }
        $('bcEmpty')?.classList.add('d-none');
        renderKpis(d.summary);
        renderSegments(d.segments, d.summary);
        renderChart(d.top_customers);
        renderTable(d.customers, d.pagination);
        renderTop5(d.top_five);
        renderBottom5(d.bottom_five);
    }

    // ─── KPI cards ───────────────────────────────────────────
    function renderKpis(s) {
        $('bcKpiCards').innerHTML = `
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-primary h-100"><div class="card-body text-center">
                    <div class="symbol symbol-50px bg-primary d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                        <i class="bi bi-people fs-2x text-white"></i>
                    </div>
                    <div class="fs-1 fw-bold text-primary">${fmtNum(s.total_customers, 0)}</div>
                    <div class="text-muted">${esc(I18N.totalCustomers)}</div>
                    <small class="text-muted">${fmtNum(s.registered_customers, 0)} ${esc(I18N.registered)} | ${fmtNum(s.guest_customers, 0)} ${esc(I18N.guest)}</small>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-success h-100"><div class="card-body text-center">
                    <div class="symbol symbol-50px bg-success d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                        <i class="bi bi-currency-dollar fs-2x text-white"></i>
                    </div>
                    <div class="fs-1 fw-bold text-success">${money(s.total_revenue)}</div>
                    <div class="text-muted">${esc(I18N.totalSales)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-info h-100"><div class="card-body text-center">
                    <div class="symbol symbol-50px bg-info d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                        <i class="bi bi-basket fs-2x text-white"></i>
                    </div>
                    <div class="fs-1 fw-bold text-info">${fmtNum(s.total_orders, 0)}</div>
                    <div class="text-muted">${esc(I18N.totalOrders)}</div>
                    <small class="text-muted">${fmtNum(s.average_orders_per_customer, 1)} avg/customer</small>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-warning h-100"><div class="card-body text-center">
                    <div class="symbol symbol-50px bg-warning d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                        <i class="bi bi-graph-up fs-2x text-white"></i>
                    </div>
                    <div class="fs-1 fw-bold text-warning">${money(s.average_per_customer)}</div>
                    <div class="text-muted">${esc(I18N.averagePerCustomer)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-danger h-100"><div class="card-body text-center">
                    <div class="symbol symbol-50px bg-danger d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                        <i class="bi bi-cart fs-2x text-white"></i>
                    </div>
                    <div class="fs-1 fw-bold text-danger">${money(s.average_order_value)}</div>
                    <div class="text-muted">${esc(I18N.averageOrderValue)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-secondary h-100"><div class="card-body text-center">
                    <div class="fs-5 fw-bold text-info">${money(s.total_tax)}</div>
                    <div class="text-muted fs-8">${esc(I18N.totalTax)}</div>
                    <hr class="my-2">
                    <div class="fs-5 fw-bold text-danger">${money(s.total_discount)}</div>
                    <div class="text-muted fs-8">${esc(I18N.totalDiscount)}</div>
                </div></div>
            </div>
        `;
    }

    // ─── Segments ────────────────────────────────────────────
    function renderSegments(seg, s) {
        const total = s.total_customers || 0;
        const pct = (n) => total > 0 ? ((n / total) * 100).toFixed(1) : '0.0';
        const items = [
            { key: 'new',       label: I18N.newCustomers,       sub: I18N.oneOrder,          color: 'info'    },
            { key: 'returning', label: I18N.returningCustomers, sub: I18N.twoToFiveOrders,   color: 'primary' },
            { key: 'regular',   label: I18N.regularCustomers,   sub: I18N.sixToTwentyOrders, color: 'success' },
            { key: 'vip',       label: I18N.vipCustomers,       sub: I18N.overTwentyOrders,  color: 'warning' },
        ];
        $('bcSegments').innerHTML = items.map(it => `
            <div class="col-md-3">
                <div class="card bg-light-${it.color} border border-${it.color}">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-1 fw-bold text-${it.color}">${fmtNum(seg[it.key] || 0, 0)}</div>
                            <div class="text-muted">${esc(it.label)} <small class="text-muted">(${esc(it.sub)})</small></div>
                        </div>
                        <div class="badge badge-light-${it.color} fs-7">${pct(seg[it.key] || 0)}%</div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // ─── Chart ───────────────────────────────────────────────
    function renderChart(topCustomers) {
        const el = $('bcChart');
        if (!el) return;

        if (!topCustomers || topCustomers.length === 0) {
            $('bcChartCard')?.classList.add('d-none');
            if (chart) { chart.destroy(); chart = null; }
            return;
        }
        $('bcChartCard')?.classList.remove('d-none');

        const names  = topCustomers.map(c => {
            const n = c.full_name || 'Guest';
            return n.length > 15 ? n.substring(0, 15) + '…' : n;
        });
        const revenue = topCustomers.map(c => Number(c.total_spent || 0));
        const orders  = topCustomers.map(c => Number(c.order_count || 0));

        const options = {
            series: [
                { name: I18N.totalSales, data: revenue, type: 'bar' },
                { name: I18N.totalOrders, data: orders, type: 'line' },
            ],
            chart: { type: 'bar', height: 400, fontFamily: 'inherit', toolbar: { show: true, tools: { download: true } } },
            plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 4, borderRadiusApplication: 'end' } },
            stroke: { width: [0, 3], curve: 'smooth' },
            xaxis: { categories: names, labels: { rotate: -45, style: { fontSize: '11px' } } },
            yaxis: [
                { title: { text: `${I18N.totalSales} (${CURRENCY})`, style: { fontSize: '12px' } },
                  labels: { formatter: v => CURRENCY + Number(v).toLocaleString() } },
                { opposite: true, title: { text: I18N.totalOrders, style: { fontSize: '12px' } } },
            ],
            colors: ['#3E97FF', '#50CD89'],
            fill: { opacity: [0.85, 1] },
            tooltip: {
                shared: true, intersect: false,
                y: {
                    formatter: (v, { seriesIndex }) => seriesIndex === 0
                        ? CURRENCY + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 })
                        : Number(v).toLocaleString(),
                },
            },
            legend: { position: 'top', horizontalAlign: 'center' },
            grid: { borderColor: '#f1f1f1', row: { colors: ['#f8f9fa', 'transparent'], opacity: 0.5 } },
            dataLabels: { enabled: false },
        };

        if (chart) chart.updateOptions(options, false, true);
        else { chart = new ApexCharts(el, options); chart.render(); }
    }

    // ─── Customer table ─────────────────────────────────────
    function renderTable(rows, pagination) {
        if (!rows || rows.length === 0) {
            $('bcTableCard')?.classList.add('d-none');
            return;
        }
        $('bcTableCard')?.classList.remove('d-none');
        $('bcTableBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.customers}`;

        $('bcCustomersBody').innerHTML = rows.map((c, i) => {
            const rank = (pagination.from || 1) + i;
            const top3Cls = rank <= 3 ? 'bc-row-top3' : '';
            const medalIcon = rank === 1 ? 'trophy' : (rank === 2 ? 'award' : (rank === 3 ? 'award' : null));

            return `
                <tr class="bc-row ${top3Cls}" data-customer-key="${esc(c.key)}" data-customer-name="${esc(c.full_name)}">
                    <td class="ps-4">
                        <span class="fw-bold">${rank}</span>
                        ${medalIcon ? `<div class="mt-1"><span class="badge badge-light-${rank === 1 ? 'danger' : (rank === 2 ? 'warning' : 'info')}">
                            <i class="bi bi-${medalIcon} me-1"></i>${esc(I18N.top)} ${rank}
                        </span></div>` : ''}
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-40px symbol-circle me-3">
                                <div class="symbol-label bg-light-${esc(c.segment_color || 'secondary')}">
                                    <i class="bi bi-person fs-2"></i>
                                </div>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold">${esc(c.full_name || 'Guest')}</span>
                                ${!c.is_guest
                                    ? `<small class="text-muted">${esc(c.email || '')}</small>
                                       <span class="badge badge-light-success mt-1">${esc(I18N.registered)}</span>`
                                    : `<span class="badge badge-light-secondary mt-1">${esc(I18N.guest)}</span>`}
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-light-${esc(c.segment_color || 'secondary')}">
                            ${esc(c.segment_icon || '')} ${esc(c.segment || '—')}
                        </span>
                    </td>
                    <td class="text-center"><span class="badge badge-light-primary">${fmtNum(c.order_count, 0)}</span></td>
                    <td class="text-end"><span class="fw-bold text-success">${money(c.total_spent)}</span></td>
                    <td class="text-end"><span class="text-gray-600">${money(c.average_order_value)}</span></td>
                    <td class="text-end"><span class="text-danger">${money(c.min_order_value)}</span></td>
                    <td class="text-end"><span class="text-success">${money(c.max_order_value)}</span></td>
                    <td class="text-end"><span class="text-info">${money(c.total_tax)}</span></td>
                    <td class="text-end"><span class="text-warning">${money(c.total_discount)}</span></td>
                    <td>
                        ${c.last_order_date
                            ? `<span>${esc(formatDate(c.last_order_date))}</span><br><small class="text-muted">${money(c.last_order_amount)}</small>`
                            : `<span class="text-muted">—</span>`}
                    </td>
                    <td class="pe-4">
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:8px;">
                                <div class="progress-bar bg-${esc(c.segment_color || 'primary')}" style="width:${Math.min(Number(c.percentage), 100)}%;"></div>
                            </div>
                            <span class="fw-bold min-w-60px text-end">${fmtNum(c.percentage, 1)}%</span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);
    }

    // ─── Top 5 / Bottom 5 ───────────────────────────────────
    function renderTop5(rows) {
        if (!rows || rows.length === 0) { $('bcTop5Card')?.classList.add('d-none'); return; }
        $('bcTop5Card')?.classList.remove('d-none');
        $('bcTop5Body').innerHTML = rows.map((c, i) => `
            <tr>
                <td class="ps-4"><span class="fw-bold">${i + 1}</span></td>
                <td>${esc(c.full_name || 'Guest')}</td>
                <td class="text-end"><span class="fw-bold text-success">${money(c.total_spent)}</span></td>
                <td class="text-end pe-4"><span class="badge badge-light-primary">${fmtNum(c.order_count, 0)}</span></td>
            </tr>
        `).join('');
    }

    function renderBottom5(rows) {
        if (!rows || rows.length === 0) { $('bcBottom5Card')?.classList.add('d-none'); return; }
        $('bcBottom5Card')?.classList.remove('d-none');
        $('bcBottom5Body').innerHTML = rows.map((c, i) => `
            <tr>
                <td class="ps-4"><span class="fw-bold">${i + 1}</span></td>
                <td>${esc(c.full_name || 'Guest')}</td>
                <td class="text-end"><span class="fw-bold text-danger">${money(c.total_spent)}</span></td>
                <td class="text-end pe-4"><span class="badge badge-light-primary">${fmtNum(c.order_count, 0)}</span></td>
            </tr>
        `).join('');
    }

    // ─── Pagination ──────────────────────────────────────────
    function renderPagination(p) {
        const info = $('bcPaginationInfo');
        const ul   = $('bcPagination');
        const wrap = $('bcPaginationWrap');
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

    // ─── Drawer ──────────────────────────────────────────────
    function getDrawer() {
        const el = document.getElementById('bcDrawer');
        if (!el) return null;
        drawerBs = drawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return drawerBs;
    }

    async function openDrawer(customerKey, customerName) {
        const inst = getDrawer();
        if (!inst) return;

        $('bcDrawerSubtitle').textContent = customerName || '';
        $('bcDrawerSkeleton')?.classList.remove('d-none');
        const content = $('bcDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        inst.show();

        const params = new URLSearchParams();
        if (state.startDate)    params.set('start_date', state.startDate);
        if (state.endDate)      params.set('end_date', state.endDate);
        if (state.locationId)   params.set('location_id', state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);

        try {
            const url = ORDERS_ROUTE.replace('__KEY__', encodeURIComponent(customerKey));
            const res = await fetch(`${url}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            renderDrawer(json.data);
        } catch (err) {
            console.error('[by-customer-drawer] failed:', err);
            $('bcDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderDrawer(d) {
        $('bcDrawerSkeleton')?.classList.add('d-none');
        const content = $('bcDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        if (!d.orders || d.orders.length === 0) {
            content.innerHTML = `<div class="alert alert-light text-center">${esc(I18N.noOrderHistory)}</div>`;
            return;
        }

        const rows = d.orders.map(o => `
            <tr>
                <td>
                    <span class="fw-bold">${esc(o.order_number)}</span>
                    <br><small class="text-muted">${esc(o.created_time || '')}</small>
                </td>
                <td><span class="text-muted">${esc(o.created_at)}</span></td>
                <td><span class="badge badge-light-${esc(o.status_color)}">${esc(capitalize(o.status))}</span></td>
                <td>${esc(o.location)}</td>
                <td>${esc(o.department)}</td>
                <td class="text-center">${fmtNum(o.items_count, 0)}</td>
                <td class="text-end">${money(o.total)}</td>
                <td class="text-end text-success">${money(o.paid)}</td>
                <td class="text-end text-${o.balance > 0 ? 'danger' : 'muted'}">${money(o.balance)}</td>
            </tr>
        `).join('');

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">${esc($('bcDrawerSubtitle').textContent || '')}</h4>
                    <div class="text-muted fs-7">${esc(I18N.orderHistory)} · ${fmtNum(d.order_count, 0)} ${esc(I18N.orders)}</div>
                </div>
                <div class="text-end">
                    <div class="fs-3 fw-bold text-success">${money(d.total_spent)}</div>
                    <div class="text-muted fs-8">${esc(I18N.totalSpent)}</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>${esc(I18N.orderNumber)}</th>
                            <th>${esc(I18N.date)}</th>
                            <th>${esc(I18N.status)}</th>
                            <th>${esc(I18N.location)}</th>
                            <th>${esc(I18N.department)}</th>
                            <th class="text-center">${esc(I18N.items)}</th>
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

    // ─── Export ──────────────────────────────────────────────
    function exportCsv() {
        const table = $('bcCustomersTable');
        if (!table) {
            window.toastr?.error('Table not found.');
            return;
        }
        try {
            const rows = table.querySelectorAll('tr');
            let csv = [];
            rows.forEach(row => {
                const cols = row.querySelectorAll('td, th');
                const rowData = Array.from(cols).map(col => {
                    let text = col.innerText.trim().replace(/[🥇🥈🥉🌟🔄⭐👑👋]/g, '').trim();
                    if (text.includes(',')) text = '"' + text + '"';
                    return text;
                });
                csv.push(rowData.join(','));
            });
            const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'customer_sales.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(link.href);
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
    function formatDate(d) {
        if (!d) return '—';
        const dt = new Date(d + 'T00:00:00');
        if (isNaN(dt)) return d;
        return dt.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    }

    return { load };
})();
</script>