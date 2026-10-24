<script>
window.TimeAnalysis = (function () {
    'use strict';

    const ROUTE    = window.TA_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.TA_BOOTSTRAP || {};
    const I18N     = window.TA_I18N || {};

    const state = {
        startDate:    BOOT.startDate    || '',
        endDate:      BOOT.endDate      || '',
        locationId:   BOOT.locationId   || '',
        departmentId: BOOT.departmentId || '',
        orderType:    BOOT.orderType    || 'all',
        groupBy:      BOOT.groupBy      || 'daily',
        perPage:      Number(BOOT.perPage || 25),
        page:         1,
    };

    let chart      = null;
    let peakChart  = null;
    let inFlight   = false;
    let lastGroup  = state.groupBy;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('taApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('taClearBtn')?.addEventListener('click', resetFilters);
        $('taExportBtn')?.addEventListener('click', exportExcel);
        load();
    });

    function readFilters() {
        state.startDate    = $('taStartDate')?.value   || '';
        state.endDate      = $('taEndDate')?.value     || '';
        state.locationId   = $('taLocation')?.value    || '';
        state.departmentId = $('taDepartment')?.value  || '';
        state.orderType    = $('taType')?.value        || 'all';
        state.groupBy      = $('taGroupBy')?.value     || 'daily';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.departmentId = '';
        state.orderType = 'all';
        state.groupBy = 'daily';
        state.page = 1;
        ['taStartDate','taEndDate','taType','taGroupBy'].forEach(id => { if ($(id)) $(id).value = ''; });
        if ($('taType'))    $('taType').value = 'all';
        if ($('taGroupBy')) $('taGroupBy').value = 'daily';
        if (window.jQuery) {
            jQuery('#taLocation').val(null).trigger('change.select2');
            jQuery('#taDepartment').val(null).trigger('change.select2');
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
        if (state.groupBy)      params.set('group_by', state.groupBy);

        try {
            const res = await fetch(`${ROUTE}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            render(json.data);
            showSkeleton(false);
        } catch (err) {
            console.error('[time-analysis] load failed:', err);
            window.toastr?.error('Failed to load time analysis.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('taSkeleton')?.classList.toggle('d-none', !v);
        $('taContent')?.classList.toggle('d-none', v);
    }

    function render(d) {
        if (!d || !d.summary || d.summary.total_periods === 0) {
            $('taEmpty')?.classList.remove('d-none');
            ['taSummaryCards','taGrowthCards'].forEach(id => $(id) && ($(id).innerHTML = ''));
            ['taChartCard','taPeakCard','taPeakHoursCard','taTableCard'].forEach(id => $(id)?.classList.add('d-none'));
            if (chart)     { chart.destroy();     chart = null; }
            if (peakChart) { peakChart.destroy(); peakChart = null; }
            return;
        }
        $('taEmpty')?.classList.add('d-none');
        renderSummary(d.summary);
        renderGrowth(d.growth, d.group_by);
        renderChart(d.chart, d.group_by);
        renderPeakDays(d.peak_days);
        renderPeakHours(d.peak_hours, d.group_by);
        renderTable(d.periods, d.pagination, d.page_totals, d.grand_totals, d.group_by);
    }

    // ─── Summary ────────────────────────────────────────────
    function renderSummary(s) {
        $('taSummaryCards').innerHTML = `
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-primary h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-primary">${fmtNum(s.total_periods, 0)}</div>
                    <div class="text-muted">${esc(I18N.totalPeriods)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-info h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-info">${fmtNum(s.total_orders, 0)}</div>
                    <div class="text-muted">${esc(I18N.totalOrders)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-success h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-success">${money(s.total_sales)}</div>
                    <div class="text-muted">${esc(I18N.totalSales)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-warning h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-warning">${money(s.total_tax)}</div>
                    <div class="text-muted">${esc(I18N.totalTax)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-danger h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-danger">${money(s.total_discount)}</div>
                    <div class="text-muted">${esc(I18N.totalDiscount)}</div>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card bg-light-secondary h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-gray-800">${money(s.average_sale)}</div>
                    <div class="text-muted">${esc(I18N.averageSale)}</div>
                </div></div>
            </div>
        `;
    }

    // ─── Growth ─────────────────────────────────────────────
    function renderGrowth(g, groupBy) {
        if (!g || g.trend === undefined) {
            $('taGrowthCards').innerHTML = '';
            return;
        }
        const trend = g.trend || 'stable';
        const trendColor = trend === 'upward' ? 'success' : (trend === 'downward' ? 'danger' : 'warning');
        const trendLabel = trend === 'upward' ? I18N.trendUpward : (trend === 'downward' ? I18N.trendDownward : I18N.trendStable);

        $('taGrowthCards').innerHTML = `
            <div class="col-md-4">
                <div class="card bg-light-${trendColor} border border-${trendColor} border-dashed">
                    <div class="card-body text-center">
                        <div class="fs-1 fw-bold text-${trendColor}">${fmtNum(g.daily_growth || 0, 1)}%</div>
                        <div class="text-gray-600">${esc(I18N.dailyGrowth)}</div>
                        <span class="badge badge-light-${trendColor} mt-2">${esc(trendLabel)}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light-info border border-info border-dashed">
                    <div class="card-body text-center">
                        <div class="fs-1 fw-bold text-info">${fmtNum(g.weekly_growth || 0, 1)}%</div>
                        <div class="text-gray-600">${esc(I18N.weeklyGrowth)}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light-secondary border border-secondary border-dashed">
                    <div class="card-body text-center">
                        <div class="fs-1 fw-bold text-gray-800">${money(g.current_average || 0)}</div>
                        <div class="text-gray-600">${esc(I18N.currentAverage)}</div>
                    </div>
                </div>
            </div>
        `;
    }

    // ─── Chart ──────────────────────────────────────────────
    function renderChart(rows, groupBy) {
        const el = $('taChart');
        if (!el || !rows || rows.length === 0) {
            $('taChartCard')?.classList.add('d-none');
            if (chart) { chart.destroy(); chart = null; }
            return;
        }
        $('taChartCard')?.classList.remove('d-none');
        $('taChartTitle').textContent = titleForAnalysis(groupBy);

        const categories = rows.map(r => r.label);
        const sales      = rows.map(r => Number(r.sales));
        const orders     = rows.map(r => Number(r.orders));

        const options = {
            series: [
                { name: I18N.totalSalesLbl, type: 'line',   data: sales },
                { name: I18N.orderCount,    type: 'column', data: orders },
            ],
            chart:  { height: 400, type: 'line', fontFamily: 'inherit', toolbar: { show: true, tools: { download: true } } },
            dataLabels: { enabled: false },
            stroke: { width: [3, 1], curve: 'smooth' },
            colors: ['#50CD89', '#009EF7'],
            fill:   { opacity: [0.85, 0.25] },
            markers:{ size: 4 },
            xaxis:  { categories, labels: { rotate: -45, style: { fontSize: '11px' } } },
            yaxis: [
                { title: { text: `${I18N.totalSalesLbl} (${CURRENCY})`, style: { color: '#50CD89' } },
                  labels: { formatter: v => CURRENCY + Number(v).toFixed(2) } },
                { opposite: true, title: { text: I18N.orderCount, style: { color: '#009EF7' } } },
            ],
            tooltip: {
                shared: true, intersect: false,
                y: { formatter: (v, { seriesIndex }) => seriesIndex === 0 ? money(v) : fmtNum(v, 0) },
            },
            legend: { position: 'top', horizontalAlign: 'left' },
            grid:   { borderColor: '#E5E7EB', strokeDashArray: 4 },
        };

        if (chart) chart.updateOptions(options, false, true);
        else { chart = new ApexCharts(el, options); chart.render(); }
    }

    // ─── Peak days ──────────────────────────────────────────
    function renderPeakDays(rows) {
        if (!rows || rows.length === 0) {
            $('taPeakCard')?.classList.add('d-none');
            return;
        }
        $('taPeakCard')?.classList.remove('d-none');
        $('taPeakBody').innerHTML = rows.map(p => `
            <tr>
                <td class="ps-4 fw-bold">${esc(p.formatted)}</td>
                <td><span class="badge badge-light-${p.is_weekend ? 'danger' : 'primary'}">${esc(p.day_name)}</span></td>
                <td><span class="badge badge-light-primary">${fmtNum(p.order_count, 0)}</span></td>
                <td class="text-end pe-4 fw-bold text-success">${money(p.total_sales)}</td>
            </tr>
        `).join('');
    }

    // ─── Peak hours (hourly only) ───────────────────────────
    function renderPeakHours(rows, groupBy) {
        if (groupBy !== 'hourly' || !rows || rows.length === 0) {
            $('taPeakHoursCard')?.classList.add('d-none');
            if (peakChart) { peakChart.destroy(); peakChart = null; }
            return;
        }
        $('taPeakHoursCard')?.classList.remove('d-none');

        const el = $('taPeakHoursChart');
        if (!el) return;

        const categories = rows.map(p => p.display);
        const values     = rows.map(p => Number(p.hourly_total));

        const options = {
            series: [{ name: I18N.totalSalesLbl, data: values }],
            chart:  { type: 'bar', height: 250, fontFamily: 'inherit', toolbar: { show: false } },
            plotOptions: { bar: { horizontal: false, columnWidth: '60%' } },
            colors: ['#50CD89'],
            xaxis:  { categories },
            yaxis:  { labels: { formatter: v => money(v) } },
            tooltip:{ y: { formatter: v => money(v) } },
        };

        if (peakChart) peakChart.updateOptions(options, false, true);
        else { peakChart = new ApexCharts(el, options); peakChart.render(); }
    }

    // ─── Table ──────────────────────────────────────────────
    function renderTable(rows, pagination, pageTotals, grandTotals, groupBy) {
        if (!rows || rows.length === 0) {
            $('taTableCard')?.classList.add('d-none');
            return;
        }
        $('taTableCard')?.classList.remove('d-none');
        $('taTableTitle').textContent = titleForReport(groupBy);
        $('taTableBadge').textContent = `${fmtNum(pagination.total, 0)} ${labelForPeriods(groupBy)}`;

        $('taTableHead').innerHTML = headForGroup(groupBy);
        $('taTableBody').innerHTML  = rows.map((p, i) => rowForGroup(p, groupBy, i, pagination)).join('');
        $('taTableFoot').innerHTML  = footForGroup(pageTotals, grandTotals, pagination, groupBy);

        renderPagination(pagination);
    }

    function headForGroup(groupBy) {
        const base = `class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light"`;
        if (groupBy === 'hourly') {
            return `<tr ${base}>
                <th class="ps-4">${esc(I18N.hour)}</th>
                <th>${esc(I18N.timePeriod)}</th>
                <th>${esc(I18N.orderCount)}</th>
                <th>${esc(I18N.totalSalesLbl)}</th>
                <th>${esc(I18N.avgAmount)}</th>
                <th>${esc(I18N.totalTaxLbl)}</th>
                <th>${esc(I18N.totalDiscLbl)}</th>
                <th class="pe-4">${esc(I18N.trend)}</th>
            </tr>`;
        }
        if (groupBy === 'weekly') {
            return `<tr ${base}>
                <th class="ps-4">${esc(I18N.week)}</th>
                <th>${esc(I18N.dateRange)}</th>
                <th>${esc(I18N.orderCount)}</th>
                <th>${esc(I18N.totalSalesLbl)}</th>
                <th>${esc(I18N.avgAmount)}</th>
                <th>${esc(I18N.totalTaxLbl)}</th>
                <th>${esc(I18N.totalDiscLbl)}</th>
                <th class="pe-4">${esc(I18N.trend)}</th>
            </tr>`;
        }
        if (groupBy === 'monthly') {
            return `<tr ${base}>
                <th class="ps-4">${esc(I18N.month)}</th>
                <th>${esc(I18N.year)}</th>
                <th>${esc(I18N.orderCount)}</th>
                <th>${esc(I18N.totalSalesLbl)}</th>
                <th>${esc(I18N.avgAmount)}</th>
                <th>${esc(I18N.totalTaxLbl)}</th>
                <th>${esc(I18N.totalDiscLbl)}</th>
                <th class="pe-4">${esc(I18N.trend)}</th>
            </tr>`;
        }
        return `<tr ${base}>
            <th class="ps-4">${esc(I18N.date)}</th>
            <th>${esc(I18N.day)}</th>
            <th>${esc(I18N.orderCount)}</th>
            <th>${esc(I18N.totalSalesLbl)}</th>
            <th>${esc(I18N.avgAmount)}</th>
            <th>${esc(I18N.totalTaxLbl)}</th>
            <th>${esc(I18N.totalDiscLbl)}</th>
            <th class="pe-4">${esc(I18N.trend)}</th>
        </tr>`;
    }

    function rowForGroup(p, groupBy, i, pagination) {
        let c1 = '', c2 = '';
        if (groupBy === 'hourly') {
            c1 = `<td class="ps-4 fw-bold">${esc(p.hour_display)}</td>`;
            c2 = `<td><span class="badge badge-light-info">${esc(p.period_label)}</span></td>`;
        } else if (groupBy === 'weekly') {
            c1 = `<td class="ps-4 fw-bold">Week ${fmtNum(p.week_number, 0)}</td>`;
            c2 = `<td><span class="text-gray-700">${esc(p.date_range)}</span> <span class="badge badge-light-primary ms-1">${fmtNum(p.year, 0)}</span></td>`;
        } else if (groupBy === 'monthly') {
            c1 = `<td class="ps-4 fw-bold">${esc(p.month_name)}</td>`;
            c2 = `<td><span class="badge badge-light-primary">${fmtNum(p.year, 0)}</span></td>`;
        } else {
            c1 = `<td class="ps-4 fw-bold">${esc(p.formatted_date)}</td>`;
            c2 = `<td><span class="badge badge-light-${p.is_weekend ? 'danger' : 'primary'}">${esc(p.day_name)}</span></td>`;
        }

        let trendHtml = `<span class="text-muted">—</span>`;
        if (p.trend_percent !== null && p.trend_percent !== undefined) {
            const dir = p.trend_dir;
            const cls = dir === 'up' ? 'success' : (dir === 'down' ? 'danger' : 'secondary');
            const icon= dir === 'up' ? 'bi-arrow-up-right' : (dir === 'down' ? 'bi-arrow-down-right' : 'bi-dash');
            const sign= p.trend_percent > 0 ? '+' : '';
            trendHtml = `<div class="d-flex align-items-center text-${cls}">
                <i class="bi ${icon} me-1"></i>
                <span class="fw-bold">${sign}${fmtNum(p.trend_percent, 1)}%</span>
            </div>`;
        }

        return `
            <tr>
                ${c1}
                ${c2}
                <td><span class="badge badge-light-primary">${fmtNum(p.order_count, 0)}</span></td>
                <td class="fw-bold text-success">${money(p.total_sales)}</td>
                <td class="text-gray-600">${money(p.average_sale)}</td>
                <td class="text-info">${money(p.total_tax)}</td>
                <td class="text-warning">${money(p.total_discount)}</td>
                <td class="pe-4">${trendHtml}</td>
            </tr>
        `;
    }

    function footForGroup(pageTotals, grandTotals, pagination, groupBy) {
        const showGrand = pagination.total > (pagination.to - pagination.from + 1);

        const pageRow = `
            <tr>
                <th colspan="2" class="text-end">${esc(I18N.totals)} (page)</th>
                <th>${fmtNum(pageTotals.order_count, 0)}</th>
                <th class="text-success">${money(pageTotals.total_sales)}</th>
                <th>${money(pageTotals.average_sale)}</th>
                <th class="text-info">${money(pageTotals.total_tax)}</th>
                <th class="text-warning">${money(pageTotals.total_discount)}</th>
                <th></th>
            </tr>`;

        const grandRow = showGrand ? `
            <tr>
                <th colspan="2" class="text-end text-muted">${esc(I18N.totals)} (all)</th>
                <th class="text-muted">${fmtNum(grandTotals.order_count, 0)}</th>
                <th class="text-success">${money(grandTotals.total_sales)}</th>
                <th class="text-muted">${money(grandTotals.average_sale)}</th>
                <th class="text-info">${money(grandTotals.total_tax)}</th>
                <th class="text-warning">${money(grandTotals.total_discount)}</th>
                <th></th>
            </tr>` : '';

        return pageRow + grandRow;
    }

    function titleForAnalysis(groupBy) {
        if (groupBy === 'hourly')  return I18N.hourlyAnalysis;
        if (groupBy === 'weekly')  return I18N.weeklyAnalysis;
        if (groupBy === 'monthly') return I18N.monthlyAnalysis;
        return I18N.dailyAnalysis;
    }
    function titleForReport(groupBy) {
        if (groupBy === 'hourly')  return I18N.hourlyReport;
        if (groupBy === 'weekly')  return I18N.weeklyReport;
        if (groupBy === 'monthly') return I18N.monthlyReport;
        return I18N.dailyReport;
    }
    function labelForPeriods(groupBy) {
        if (groupBy === 'hourly')  return I18N.hours;
        if (groupBy === 'weekly')  return I18N.weeks;
        if (groupBy === 'monthly') return I18N.months;
        return I18N.days;
    }

    // ─── Pagination ─────────────────────────────────────────
    function renderPagination(p) {
        const info = $('taPaginationInfo');
        const ul   = $('taPagination');
        const wrap = $('taPaginationWrap');
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

    // ─── Export ─────────────────────────────────────────────
    function exportExcel() {
        const table = $('taTable');
        if (!table || typeof XLSX === 'undefined') {
            window.toastr?.error('Excel export unavailable.');
            return;
        }
        try {
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, 'Time Analysis');
            XLSX.writeFile(wb, 'time_analysis.xlsx');
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
    function fmtNum(n, d = 0) {
        return Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: d, maximumFractionDigits: d,
        });
    }
    function money(n) { return CURRENCY + fmtNum(n, 2); }

    return { load };
})();
</script>