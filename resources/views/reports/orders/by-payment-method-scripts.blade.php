<script>
window.ByPaymentMethod = (function () {
    'use strict';

    const DATA_ROUTE       = window.PM_ROUTES.data;
    const TXNS_ROUTE       = window.PM_ROUTES.txns;
    const FAILED_TXNS_ROUTE= window.PM_ROUTES.failedTxns;
    const CURRENCY         = window.REPORT_CURRENCY || '';
    const BOOT             = window.PM_BOOTSTRAP || {};
    const I18N             = window.PM_I18N || {};

    const state = {
        startDate:           BOOT.startDate           || '',
        endDate:             BOOT.endDate             || '',
        locationId:          BOOT.locationId          || '',
        departmentId:        BOOT.departmentId        || '',
        paymentType:         BOOT.paymentType         || '',
        paymentStatus:       BOOT.paymentStatus       || 'all',
        paymentMethodStatus: BOOT.paymentMethodStatus || 'all',
        minAmount:           BOOT.minAmount           ?? '',
        maxAmount:           BOOT.maxAmount           ?? '',
        perPage:             Number(BOOT.perPage      || 25),
        page:                1,
    };

    let distChart   = null;
    let trendChart  = null;
    let drawerBs    = null;
    let inFlight    = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('pmApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('pmClearBtn')?.addEventListener('click', resetFilters);
        $('pmExportBtn')?.addEventListener('click', exportExcel);
        $('pmPrintBtn')?.addEventListener('click', () => window.print());

        // Method row → successful transactions drawer
        $('pmMethodsBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.pm-row[data-method-id]');
            if (!row) return;
            openDrawer(row.dataset.methodId, row.dataset.methodName, 'success');
        });

        // Failed row → failed transactions drawer
        $('pmFailedBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.pm-row[data-method-id]');
            if (!row) return;
            openDrawer(row.dataset.methodId, row.dataset.methodName, 'failed');
        });

        document.getElementById('pmDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('pmDrawerContent').innerHTML = '';
            $('pmDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate           = $('pmStartDate')?.value   || '';
        state.endDate             = $('pmEndDate')?.value     || '';
        state.locationId          = $('pmLocation')?.value    || '';
        state.departmentId        = $('pmDepartment')?.value  || '';
        state.paymentType         = $('pmType')?.value        || '';
        state.paymentStatus       = $('pmStatus')?.value      || 'all';
        state.paymentMethodStatus = $('pmMethodStatus')?.value|| 'all';
        state.minAmount           = $('pmMinAmount')?.value   ?? '';
        state.maxAmount           = $('pmMaxAmount')?.value   ?? '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.departmentId = '';
        state.paymentType = '';
        state.paymentStatus = 'all';
        state.paymentMethodStatus = 'all';
        state.minAmount = state.maxAmount = '';
        state.page = 1;
        ['pmStartDate','pmEndDate','pmMinAmount','pmMaxAmount'].forEach(id => { if ($(id)) $(id).value = ''; });
        if ($('pmStatus'))       $('pmStatus').value = 'all';
        if ($('pmMethodStatus')) $('pmMethodStatus').value = 'all';
        if ($('pmType'))         $('pmType').value = 'all';
        if (window.jQuery) {
            jQuery('#pmLocation').val(null).trigger('change.select2');
            jQuery('#pmDepartment').val(null).trigger('change.select2');
        }
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)           params.set('start_date', state.startDate);
        if (state.endDate)             params.set('end_date', state.endDate);
        if (state.locationId)          params.set('location_id', state.locationId);
        if (state.departmentId)        params.set('department_id', state.departmentId);
        if (state.paymentType)         params.set('payment_type', state.paymentType);
        if (state.paymentStatus)       params.set('payment_status', state.paymentStatus);
        if (state.paymentMethodStatus) params.set('payment_method_status', state.paymentMethodStatus);
        if (state.minAmount !== '' && state.minAmount !== null) params.set('min_amount', state.minAmount);
        if (state.maxAmount !== '' && state.maxAmount !== null) params.set('max_amount', state.maxAmount);

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
            console.error('[by-payment-method] load failed:', err);
            window.toastr?.error('Failed to load payment analysis.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('pmSkeleton')?.classList.toggle('d-none', !v);
        $('pmContent')?.classList.toggle('d-none', v);
    }

    function render(d) {
        if (!d || !d.summary || d.summary.total_methods === 0) {
            $('pmEmpty')?.classList.remove('d-none');
            $('pmKpiCards').innerHTML = '';
            ['pmDistChartCard','pmTrendsChartCard','pmFailedCard','pmMethodsCard'].forEach(id => $(id)?.classList.add('d-none'));
            if (distChart)  { distChart.destroy();  distChart  = null; }
            if (trendChart) { trendChart.destroy(); trendChart = null; }
            return;
        }
        $('pmEmpty')?.classList.add('d-none');
        renderKpis(d.summary);
        renderDistChart(d.methods);
        renderTrendsChart(d.trends);
        renderFailed(d.failed_transactions, d.summary);
        renderMethods(d.methods, d.pagination, d.summary);
    }

    // ─── KPI ────────────────────────────────────────────────
    function renderKpis(s) {
        const successColor = s.success_rate >= 95 ? 'success' : (s.success_rate >= 80 ? 'warning' : 'danger');
        $('pmKpiCards').innerHTML = `
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-primary h-100"><div class="card-body text-center">
                    <div class="symbol symbol-50px bg-primary d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                        <i class="bi bi-credit-card fs-2x text-white"></i>
                    </div>
                    <div class="fs-1 fw-bold text-primary">${fmtNum(s.total_methods, 0)}</div>
                    <div class="text-muted">${esc(I18N.paymentMethods)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-info h-100"><div class="card-body text-center">
                    <div class="symbol symbol-50px bg-info d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                        <i class="bi bi-receipt fs-2x text-white"></i>
                    </div>
                    <div class="fs-1 fw-bold text-info">${fmtNum(s.total_transactions, 0)}</div>
                    <div class="text-muted">${esc(I18N.totalTransactions)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-success h-100"><div class="card-body text-center">
                    <div class="symbol symbol-50px bg-success d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                        <i class="bi bi-currency-dollar fs-2x text-white"></i>
                    </div>
                    <div class="fs-1 fw-bold text-success">${money(s.total_amount)}</div>
                    <div class="text-muted">${esc(I18N.totalProcessed)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-warning h-100"><div class="card-body text-center">
                    <div class="symbol symbol-50px bg-warning d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                        <i class="bi bi-calculator fs-2x text-white"></i>
                    </div>
                    <div class="fs-1 fw-bold text-warning">${money(s.avg_transaction)}</div>
                    <div class="text-muted">${esc(I18N.avgTransaction)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-${successColor} h-100"><div class="card-body text-center">
                    <div class="symbol symbol-50px bg-${successColor} d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                        <i class="bi bi-check-circle fs-2x text-white"></i>
                    </div>
                    <div class="fs-1 fw-bold">${fmtNum(s.success_rate, 1)}%</div>
                    <div class="text-muted">${esc(I18N.successRate)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-secondary h-100"><div class="card-body text-center">
                    <div class="symbol symbol-50px bg-secondary d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                        <i class="bi bi-trophy fs-2x text-white"></i>
                    </div>
                    <div class="fs-6 fw-bold text-gray-800">${esc(truncate(s.most_used_method, 20))}</div>
                    <div class="text-muted">${money(s.most_used_amount)}</div>
                </div></div>
            </div>
        `;
    }

    // ─── Distribution chart ─────────────────────────────────
    function renderDistChart(methods) {
        const el = $('pmDistChart');
        if (!el || !methods || methods.length === 0) {
            $('pmDistChartCard')?.classList.add('d-none');
            if (distChart) { distChart.destroy(); distChart = null; }
            return;
        }
        $('pmDistChartCard')?.classList.remove('d-none');

        const colors = ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C', '#A1A5B7', '#17C653', '#FE7A0A', '#4E7B8A', '#9D4EDD'];
        const total  = methods.reduce((s, m) => s + Number(m.total_amount), 0);

        const options = {
            series: methods.map(m => Number(m.total_amount)),
            chart:  { type: 'donut', height: 400, fontFamily: 'inherit', toolbar: { show: true } },
            labels: methods.map(m => m.method_name),
            colors,
            legend: {
                position: 'bottom', horizontalAlign: 'center', fontSize: '12px',
                formatter: (name, opts) => {
                    const total = opts.w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                    const pct = total > 0 ? ((opts.w.globals.series[opts.seriesIndex] / total) * 100).toFixed(1) : 0;
                    return `${name} – ${pct}%`;
                },
            },
            tooltip: {
                y: { formatter: v => CURRENCY + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) },
            },
            plotOptions: { pie: { donut: {
                size: '60%',
                labels: {
                    show: true,
                    total: {
                        show: true, label: I18N.totalAmountLbl, fontSize: '14px', fontWeight: 600,
                        formatter: () => CURRENCY + total.toLocaleString(undefined, { minimumFractionDigits: 2 }),
                    },
                    value: {
                        formatter: v => CURRENCY + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }),
                    },
                },
            }}},
        };

        if (distChart) distChart.updateOptions(options, false, true);
        else { distChart = new ApexCharts(el, options); distChart.render(); }
    }

    // ─── Trends chart ───────────────────────────────────────
    function renderTrendsChart(trends) {
        const el = $('pmTrendsChart');
        if (!el || !trends || Object.keys(trends).length === 0) {
            $('pmTrendsChartCard')?.classList.add('d-none');
            if (trendChart) { trendChart.destroy(); trendChart = null; }
            return;
        }

        const typeKeys = Object.keys(trends);
        const firstSeries = trends[typeKeys[0]] || [];
        if (firstSeries.length === 0) {
            $('pmTrendsChartCard')?.classList.add('d-none');
            return;
        }
        $('pmTrendsChartCard')?.classList.remove('d-none');

        const categories = firstSeries.map(t => formatDate(t.date));
        const colors = ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C', '#A1A5B7'];

        const series = typeKeys.map((type, i) => ({
            name: String(type).replace(/_/g, ' ').toUpperCase(),
            data: (trends[type] || []).map(t => Number(t.daily_total || 0)),
            type: 'line',
        }));

        const options = {
            series,
            chart:  { type: 'line', height: 400, fontFamily: 'inherit', toolbar: { show: true, tools: { download: true } } },
            stroke: { width: 3, curve: 'smooth' },
            xaxis:  { categories, labels: { rotate: -45, style: { fontSize: '11px' } } },
            yaxis:  {
                title: { text: `${I18N.dailyAmount} (${CURRENCY})`, style: { fontSize: '12px' } },
                labels: { formatter: v => CURRENCY + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) },
            },
            colors,
            tooltip: {
                shared: true, intersect: false,
                y: { formatter: v => CURRENCY + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) },
            },
            legend: { position: 'top', horizontalAlign: 'center', fontSize: '12px' },
            grid:   { borderColor: '#e7e7e7', row: { colors: ['#f8f9fa', 'transparent'], opacity: 0.5 } },
            markers:{ size: 4, hover: { size: 6 } },
        };

        if (trendChart) trendChart.updateOptions(options, false, true);
        else { trendChart = new ApexCharts(el, options); trendChart.render(); }
    }

    // ─── Failed table ───────────────────────────────────────
    function renderFailed(rows, summary) {
        if (!rows || rows.length === 0) {
            $('pmFailedCard')?.classList.add('d-none');
            return;
        }
        $('pmFailedCard')?.classList.remove('d-none');
        $('pmFailedBadge').textContent =
            `${fmtNum(rows.reduce((s, r) => s + Number(r.failed_count), 0), 0)} failed · ${money(rows.reduce((s, r) => s + Number(r.failed_amount), 0))}`;

        const typeColors = {
            cash: 'success', card: 'primary', bank_transfer: 'info', bank_account: 'info',
            digital_wallet: 'warning', mobile_money: 'warning', credit: 'danger',
            check: 'secondary', crypto: 'dark', other: 'secondary',
        };

        $('pmFailedBody').innerHTML = rows.map(r => {
            const rateColor = r.failure_rate > 10 ? 'danger' : (r.failure_rate > 5 ? 'warning' : 'success');
            return `
                <tr class="pm-row" data-method-id="${r.id}" data-method-name="${esc(r.name)}">
                    <td class="ps-4"><span class="fw-bold">${esc(r.name)}</span></td>
                    <td class="text-center">
                        <span class="badge badge-light-${typeColors[r.type] || 'secondary'}">${esc(capitalize((r.type || 'unknown').replace(/_/g, ' ')))}</span>
                    </td>
                    <td class="text-center"><span class="badge badge-light-danger fs-6">${fmtNum(r.failed_count, 0)}</span></td>
                    <td class="text-end text-danger fw-bold">${money(r.failed_amount)}</td>
                    <td class="pe-4">
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:8px;">
                                <div class="progress-bar bg-${rateColor}" style="width:${Math.min(r.failure_rate, 100)}%;"></div>
                            </div>
                            <span class="fw-bold min-w-60px text-end">${fmtNum(r.failure_rate, 1)}%</span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        $('pmFailedFoot').innerHTML = `
            <tr>
                <th colspan="2" class="text-end">Total</th>
                <th class="text-center text-danger">${fmtNum(rows.reduce((s, r) => s + Number(r.failed_count), 0), 0)}</th>
                <th class="text-end text-danger">${money(rows.reduce((s, r) => s + Number(r.failed_amount), 0))}</th>
                <th class="text-center pe-4">${fmtNum(summary.overall_failure_rate, 1)}%</th>
            </tr>
        `;
    }

    // ─── Methods table ──────────────────────────────────────
    function renderMethods(rows, pagination, summary) {
        if (!rows || rows.length === 0) {
            $('pmMethodsCard')?.classList.add('d-none');
            return;
        }
        $('pmMethodsCard')?.classList.remove('d-none');
        $('pmMethodsBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.paymentMethods}`;

        const typeColors = {
            cash: 'success', card: 'primary', bank_transfer: 'info', bank_account: 'info',
            digital_wallet: 'warning', mobile_money: 'warning', credit: 'danger',
            check: 'secondary', crypto: 'dark', other: 'secondary',
        };

        $('pmMethodsBody').innerHTML = rows.map((m, i) => {
            const rank = (pagination.from || 1) + i;
            const medal = rank <= 3 ? `<span class="badge badge-light-${rank === 1 ? 'danger' : (rank === 2 ? 'warning' : 'info')} ms-1">
                <i class="bi bi-${rank === 1 ? 'trophy' : 'award'}"></i>
            </span>` : '';
            const successColor = m.success_rate >= 95 ? 'success' : (m.success_rate >= 80 ? 'warning' : 'danger');

            return `
                <tr class="pm-row" data-method-id="${m.id}" data-method-name="${esc(m.method_name)}">
                    <td class="ps-4 text-center fw-bold">${rank}${medal}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-40px symbol-circle me-3">
                                <div class="symbol-label bg-light-${typeColors[m.method_type] || 'secondary'}">
                                    <i class="bi bi-credit-card fs-2"></i>
                                </div>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold">${esc(m.method_name)}</span>
                                <span class="badge badge-light-${typeColors[m.method_type] || 'secondary'} badge-sm mt-1" style="width:fit-content;">
                                    ${esc(capitalize((m.method_type || 'unknown').replace(/_/g, ' ')))}
                                </span>
                            </div>
                        </div>
                    </td>
                    <td class="text-center"><span class="badge badge-light-${typeColors[m.method_type] || 'secondary'}">${esc(capitalize((m.method_type || 'unknown').replace(/_/g, ' ')))}</span></td>
                    <td class="text-center"><span class="badge badge-light-${m.is_active ? 'success' : 'danger'}">${esc(m.is_active ? I18N.active : I18N.inactive)}</span></td>
                    <td class="text-center"><span class="badge badge-light-primary">${fmtNum(m.transaction_count, 0)}</span></td>
                    <td class="text-end fw-bold text-success">${money(m.total_amount)}</td>
                    <td class="text-end">${money(m.average_transaction)}</td>
                    <td class="text-end text-danger">${money(m.largest_transaction)}</td>
                    <td class="text-center">${m.failed_count > 0
                        ? `<span class="badge badge-light-danger">${fmtNum(m.failed_count, 0)}</span>`
                        : `<span class="badge badge-light-success">0</span>`}</td>
                    <td class="text-center">
                        <div class="d-flex flex-column align-items-center">
                            <span class="badge badge-light-${successColor} fs-7">${fmtNum(m.success_rate, 1)}%</span>
                            <div class="progress w-100 mt-1" style="height:4px;">
                                <div class="progress-bar bg-${successColor}" style="width:${m.success_rate}%;"></div>
                            </div>
                        </div>
                    </td>
                    <td>${m.last_transaction_date ? `<span class="text-muted">${esc(m.last_transaction_date)}</span>` : `<span class="text-muted">—</span>`}</td>
                    <td class="pe-4">
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-2" style="height:6px;">
                                <div class="progress-bar bg-${typeColors[m.method_type] || 'secondary'}" style="width:${Math.min(m.share, 100)}%;"></div>
                            </div>
                            <span class="fw-bold min-w-45px text-end">${fmtNum(m.share, 1)}%</span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        $('pmMethodsFoot').innerHTML = `
            <tr>
                <th colspan="5" class="text-end">Totals</th>
                <th class="text-end">${money(summary.total_amount)}</th>
                <th class="text-end">${money(summary.avg_transaction)}</th>
                <th colspan="2"></th>
                <th class="text-center">${fmtNum(summary.success_rate, 1)}%</th>
                <th colspan="2"></th>
            </tr>
        `;

        renderPagination(pagination);
    }

    // ─── Pagination ─────────────────────────────────────────
    function renderPagination(p) {
        const info = $('pmPaginationInfo');
        const ul   = $('pmPagination');
        const wrap = $('pmPaginationWrap');
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
        const el = document.getElementById('pmDrawer');
        if (!el) return null;
        drawerBs = drawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return drawerBs;
    }

    async function openDrawer(methodId, methodName, kind) {
        const inst = getDrawer();
        if (!inst) return;

        $('pmDrawerSubtitle').textContent = `${methodName} · ${kind === 'failed' ? I18N.failedAnalysis : I18N.transactions}`;
        $('pmDrawerSkeleton')?.classList.remove('d-none');
        const content = $('pmDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        inst.show();

        const params = new URLSearchParams();
        if (state.startDate) params.set('start_date', state.startDate);
        if (state.endDate)   params.set('end_date', state.endDate);

        try {
            const route = kind === 'failed' ? FAILED_TXNS_ROUTE : TXNS_ROUTE;
            const url   = route.replace('__ID__', methodId);
            const res   = await fetch(`${url}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            renderDrawer(json.data, kind);
        } catch (err) {
            console.error('[pm-drawer] failed:', err);
            $('pmDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderDrawer(d, kind) {
        $('pmDrawerSkeleton')?.classList.add('d-none');
        const content = $('pmDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        if (!d.transactions || d.transactions.length === 0) {
            content.innerHTML = `<div class="alert alert-light text-center">${esc(I18N.noTransactions)}</div>`;
            return;
        }

        const isFailed = kind === 'failed';
        const rows = d.transactions.map(t => `
            <tr>
                <td>
                    <span class="fw-bold">${esc(t.order_number || '—')}</span>
                    ${t.transaction_id ? `<br><small class="text-muted">${esc(t.transaction_id)}</small>` : ''}
                </td>
                <td class="text-end ${isFailed ? 'text-danger' : 'text-success'} fw-bold">${money(t.amount)}</td>
                <td>${esc(t.processed_at || '—')}</td>
                ${isFailed
                    ? `<td>${esc(t.notes || '—')}</td>`
                    : `<td>${esc(t.processor || '—')}</td>
                       <td>${t.recorded_via ? `<span class="badge badge-light-info">${esc(t.recorded_via)}</span>` : '—'}</td>`}
            </tr>
        `).join('');

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">${esc($('pmDrawerSubtitle').textContent.split('·')[0].trim())}</h4>
                    <div class="text-muted fs-7">${fmtNum(d.count, 0)} transactions</div>
                </div>
                <div class="text-end">
                    <div class="fs-3 fw-bold ${isFailed ? 'text-danger' : 'text-success'}">${money(d.total_amount)}</div>
                    <div class="text-muted fs-8">${isFailed ? esc(I18N.failedAmount) : esc(I18N.totalAmount)}</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>${esc(I18N.orderNumber)}</th>
                            <th class="text-end">${esc(I18N.totalAmount)}</th>
                            <th>${esc(I18N.processedAt)}</th>
                            ${isFailed
                                ? `<th>${esc(I18N.notes)}</th>`
                                : `<th>${esc(I18N.processor)}</th><th>${esc(I18N.recordedVia)}</th>`}
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    // ─── Export ─────────────────────────────────────────────
    function exportExcel() {
        const table = $('pmMethodsTable');
        if (!table || typeof XLSX === 'undefined') {
            window.toastr?.error('Excel export unavailable.');
            return;
        }
        try {
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, 'Payment Methods');
            XLSX.writeFile(wb, 'payment_method_analysis.xlsx');
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
        return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    return { load };
})();
</script>