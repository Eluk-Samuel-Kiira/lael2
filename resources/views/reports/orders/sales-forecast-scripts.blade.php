<script>
window.SalesForecast = (function () {
    'use strict';

    const ROUTE    = window.SF_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.SF_BOOTSTRAP || {};
    const I18N     = window.SF_I18N || {};

    const state = {
        startDate:           BOOT.startDate           || '',
        endDate:             BOOT.endDate             || '',
        forecastDays:        Number(BOOT.forecastDays || 30),
        locationId:          BOOT.locationId          || '',
        departmentId:        BOOT.departmentId        || '',
        historicalPerPage:   Number(BOOT.historicalPerPage || 15),
        forecastPerPage:     Number(BOOT.forecastPerPage   || 15),
        historicalPage:      1,
        forecastPage:        1,
    };

    let forecastChart   = null;
    let seasonalityChart= null;
    let inFlight        = false;
    let lastPayload     = null;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('sfApplyBtn')?.addEventListener('click', () => { readFilters(); state.historicalPage = state.forecastPage = 1; load(); });
        $('sfClearBtn')?.addEventListener('click', resetFilters);
        $('sfExportBtn')?.addEventListener('click', exportCsv);
        $('sfPrintBtn')?.addEventListener('click', () => window.print());

        document.querySelectorAll('.sf-quick-range').forEach(btn => {
            btn.addEventListener('click', () => applyQuickRange(btn.dataset.days));
        });

        load();
    });

    function readFilters() {
        state.startDate    = $('sfStartDate')?.value    || '';
        state.endDate      = $('sfEndDate')?.value      || '';
        state.forecastDays = Number($('sfForecastDays')?.value || 30);
        state.locationId   = $('sfLocation')?.value     || '';
        state.departmentId = $('sfDepartment')?.value   || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.departmentId = '';
        state.forecastDays = 30;
        state.historicalPage = state.forecastPage = 1;
        if ($('sfStartDate'))     $('sfStartDate').value = '';
        if ($('sfEndDate'))       $('sfEndDate').value = '';
        if ($('sfForecastDays'))  $('sfForecastDays').value = '30';
        if (window.jQuery) {
            jQuery('#sfLocation').val(null).trigger('change.select2');
            jQuery('#sfDepartment').val(null).trigger('change.select2');
        }
        load();
    }

    function applyQuickRange(days) {
        const today = new Date();
        let startDate;

        if (days === 'this_year') {
            startDate = new Date(today.getFullYear(), 0, 1);
        } else {
            startDate = new Date(today);
            startDate.setDate(today.getDate() - Number(days));
        }

        if ($('sfStartDate')) $('sfStartDate').value = isoDate(startDate);
        if ($('sfEndDate'))   $('sfEndDate').value   = isoDate(today);

        readFilters();
        state.historicalPage = state.forecastPage = 1;
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({
            historical_page:      state.historicalPage,
            forecast_page:        state.forecastPage,
            historical_per_page:  state.historicalPerPage,
            forecast_per_page:    state.forecastPerPage,
            forecast_days:        state.forecastDays,
        });
        if (state.startDate)    params.set('start_date', state.startDate);
        if (state.endDate)      params.set('end_date', state.endDate);
        if (state.locationId)   params.set('location_id', state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);

        try {
            const res = await fetch(`${ROUTE}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            lastPayload = json.data;
            render(json.data);
            showSkeleton(false);
        } catch (err) {
            console.error('[sales-forecast] load failed:', err);
            window.toastr?.error('Failed to load sales forecast.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('sfSkeleton')?.classList.toggle('d-none', !v);
        $('sfContent')?.classList.toggle('d-none', v);
    }

    function render(d) {
        if (!d || d.empty) {
            $('sfEmpty')?.classList.remove('d-none');
            $('sfSummaryCards').innerHTML = '';
            ['sfForecastCard','sfForecastTableCard','sfSeasonalityCard','sfHistoricalCard']
                .forEach(id => $(id)?.classList.add('d-none'));
            [forecastChart, seasonalityChart].forEach(c => c && c.destroy());
            forecastChart = seasonalityChart = null;
            return;
        }
        $('sfEmpty')?.classList.add('d-none');
        renderSummary(d.summary);
        renderForecastChart(d.chart);
        renderForecastTable(d.forecast, d.forecast_pagination);
        renderSeasonalityChart(d.seasonality);
        renderHistorical(d.historical, d.historical_pagination, d.summary.historical_avg);
    }

    // ─── Summary ────────────────────────────────────────────
    function renderSummary(s) {
        const growthColor = s.growth_rate_pct >= 0 ? 'success' : 'danger';
        const growthLabel = s.growth_direction === 'up' ? I18N.increasing : I18N.decreasing;
        const confColor   = s.confidence_level === 'high' ? 'success'
                            : (s.confidence_level === 'medium' ? 'warning' : 'danger');
        const confLabel   = s.confidence_level === 'high' ? I18N.highConfidence
                            : (s.confidence_level === 'medium' ? I18N.mediumConfidence : I18N.lowConfidence);

        $('sfSummaryCards').innerHTML = `
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-primary border border-primary border-dashed h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-primary">${money(s.forecast_sum)}</div>
                    <div class="text-muted">${esc(I18N.totalForecastSales)}</div>
                    <span class="badge badge-light-primary mt-2">${fmtNum(s.forecast_days, 0)} ${esc(I18N.days)}</span>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-${growthColor} border border-${growthColor} border-dashed h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-${growthColor}">${fmtNum(s.growth_rate_pct, 1)}%</div>
                    <div class="text-muted">${esc(I18N.dailyGrowthRate)}</div>
                    <span class="badge badge-light-${growthColor} mt-2">
                        <i class="bi bi-arrow-${s.growth_direction === 'up' ? 'up' : 'down'} me-1"></i>${esc(growthLabel)}
                    </span>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-${confColor} border border-${confColor} border-dashed h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-${confColor}">${fmtNum(s.confidence_score, 0)}%</div>
                    <div class="text-muted">${esc(I18N.confidenceScore)}</div>
                    <span class="badge badge-light-${confColor} mt-2">${esc(confLabel)}</span>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light-warning border border-warning border-dashed h-100"><div class="card-body text-center">
                    <div class="fs-1 fw-bold text-warning">${money(s.forecast_avg)}</div>
                    <div class="text-muted">${esc(I18N.dailyForecastAverage)}</div>
                    <span class="badge badge-light-warning mt-2">${money(s.forecast_min)} – ${money(s.forecast_max)}</span>
                </div></div>
            </div>
        `;
    }

    // ─── Forecast chart ─────────────────────────────────────
    function renderForecastChart(chartData) {
        const el = $('sfForecastChart');
        if (!el || !chartData) {
            $('sfForecastCard')?.classList.add('d-none');
            if (forecastChart) { forecastChart.destroy(); forecastChart = null; }
            return;
        }
        $('sfForecastCard')?.classList.remove('d-none');

        const histSeries = (chartData.historical || []).map(p => [new Date(p.date).getTime(), Number(p.sales)]);
        const fcastSeries = (chartData.forecast || []).map(p => [new Date(p.date).getTime(), Number(p.sales)]);
        const fcastHigh    = (chartData.forecast || []).map(p => [new Date(p.date).getTime(), Number(p.confidence_high)]);
        const fcastLow     = (chartData.forecast || []).map(p => [new Date(p.date).getTime(), Number(p.confidence_low)]);

        const options = {
            series: [
                { name: I18N.historicalSales,   type: 'line', data: histSeries },
                { name: I18N.forecastSales,     type: 'line', data: fcastSeries },
                { name: I18N.confidenceInterval,type: 'area', data: fcastHigh },
            ],
            chart:  { height: 450, type: 'line', fontFamily: 'inherit', toolbar: { show: true, tools: { download: true } } },
            dataLabels: { enabled: false },
            stroke: { width: [3, 3, 0], curve: 'smooth', dashArray: [0, 5, 0] },
            colors: ['#009EF7', '#50CD89', 'rgba(80,205,137,0.18)'],
            fill:   { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.05, stops: [0, 100] } },
            markers:{ size: [4, 4, 0] },
            xaxis:  {
                type: 'datetime',
                labels: { style: { fontSize: '11px' }, datetimeFormatter: { day: 'dd MMM' } },
            },
            yaxis:  {
                title: { text: `${I18N.totalSales} (${CURRENCY})` },
                labels: { formatter: v => money(v) },
            },
            tooltip: {
                shared: true, intersect: false,
                y: { formatter: v => money(v) },
            },
            legend: { position: 'top', horizontalAlign: 'left' },
            grid:   { borderColor: '#E5E7EB', strokeDashArray: 4 },
        };

        if (forecastChart) forecastChart.updateOptions(options, false, true);
        else { forecastChart = new ApexCharts(el, options); forecastChart.render(); }
    }

    // ─── Forecast table ────────────────────────────────────
    function renderForecastTable(rows, pagination) {
        if (!rows || rows.length === 0) {
            $('sfForecastTableCard')?.classList.add('d-none');
            return;
        }
        $('sfForecastTableCard')?.classList.remove('d-none');

        $('sfForecastBody').innerHTML = rows.map(f => {
            const d = new Date(f.date + 'T00:00:00');
            const dayName = d.toLocaleDateString('en-US', { weekday: 'long' });
            const isWeekend = dayName === 'Saturday' || dayName === 'Sunday';
            const confColor = f.confidence === 'high' ? 'success'
                            : (f.confidence === 'medium' ? 'warning' : 'danger');
            const trendDir  = f.trend > 0 ? 'success' : (f.trend < 0 ? 'danger' : 'primary');
            const trendIcon = f.trend > 0 ? 'arrow-up' : (f.trend < 0 ? 'arrow-down' : 'dash');

            return `
                <tr>
                    <td class="ps-4 fw-bold">${esc(d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }))}</td>
                    <td><span class="badge badge-light-${isWeekend ? 'danger' : 'primary'}">${esc(dayName)}</span></td>
                    <td class="text-end">
                        <span class="fw-bold text-success">${money(f.forecast_sales)}</span>
                        <br><small class="text-muted">${money(f.confidence_low)} – ${money(f.confidence_high)}</small>
                    </td>
                    <td class="text-center"><span class="badge badge-light-info">${fmtNum(f.forecast_orders, 0)}</span></td>
                    <td class="text-end">${money(f.average_order_value)}</td>
                    <td class="text-center"><span class="badge badge-light-${confColor}">${esc(capitalize(f.confidence))}</span></td>
                    <td class="pe-4 text-center">
                        <span class="badge badge-light-${trendDir}">
                            <i class="bi bi-${trendIcon} me-1"></i>${fmtNum(Math.abs(f.trend || 0), 1)}%
                        </span>
                    </td>
                </tr>
            `;
        }).join('');

        renderPagination(
            pagination,
            $('sfForecastPaginationInfo'),
            $('sfForecastPagination'),
            $('sfForecastPaginationWrap'),
            (p) => { state.forecastPage = p; load(); }
        );
    }

    // ─── Seasonality ───────────────────────────────────────
    function renderSeasonalityChart(rows) {
        const el = $('sfSeasonalityChart');
        if (!el || !rows || rows.length === 0) {
            $('sfSeasonalityCard')?.classList.add('d-none');
            if (seasonalityChart) { seasonalityChart.destroy(); seasonalityChart = null; }
            return;
        }
        $('sfSeasonalityCard')?.classList.remove('d-none');

        const dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const byDay = {};
        rows.forEach(r => { byDay[r.day_of_week] = Number(r.average_sales || 0); });
        const series = dayLabels.map((_, i) => byDay[i] ?? 0);

        const options = {
            series: [{ name: I18N.averageSales, data: series }],
            chart:  { type: 'bar', height: 300, fontFamily: 'inherit', toolbar: { show: false } },
            colors: ['#009EF7', '#50CD89', '#7239EA', '#FFA800', '#F64E60', '#3E97FF', '#A1A5B7'],
            plotOptions: { bar: { distributed: true, borderRadius: 4, columnWidth: '70%' } },
            dataLabels: { enabled: false },
            xaxis:  { categories: dayLabels },
            yaxis:  { title: { text: `${I18N.averageSales} (${CURRENCY})` }, labels: { formatter: v => money(v) } },
            tooltip:{ y: { formatter: v => money(v) } },
        };

        if (seasonalityChart) seasonalityChart.updateOptions(options, false, true);
        else { seasonalityChart = new ApexCharts(el, options); seasonalityChart.render(); }
    }

    // ─── Historical table ──────────────────────────────────
    function renderHistorical(rows, pagination, historicalAvg) {
        if (!rows || rows.length === 0) {
            $('sfHistoricalCard')?.classList.add('d-none');
            return;
        }
        $('sfHistoricalCard')?.classList.remove('d-none');
        $('sfHistoricalBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.daysLabel || 'days'}`;

        $('sfHistoricalBody').innerHTML = rows.map(r => {
            const d = new Date(r.date + 'T00:00:00');
            const dayName = d.toLocaleDateString('en-US', { weekday: 'long' });
            const isWeekend = dayName === 'Saturday' || dayName === 'Sunday';

            const trendBadge = r.trend_pct !== null
                ? `<span class="badge badge-light-${r.trend_dir === 'up' ? 'success' : (r.trend_dir === 'down' ? 'danger' : 'primary')}">
                     ${r.trend_pct >= 0 ? '+' : ''}${fmtNum(r.trend_pct, 1)}%
                   </span>`
                : `<span class="text-muted">—</span>`;

            const devColor = Math.abs(r.deviation_pct) >= 10
                ? (r.deviation_pct > 0 ? 'success' : 'danger')
                : 'primary';

            return `
                <tr>
                    <td class="ps-4 fw-bold">${esc(d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }))}</td>
                    <td><span class="badge badge-light-${isWeekend ? 'danger' : 'primary'}">${esc(dayName)}</span></td>
                    <td class="text-end fw-bold text-success">${money(r.daily_sales)}</td>
                    <td class="text-center"><span class="badge badge-light-info">${fmtNum(r.order_count, 0)}</span></td>
                    <td class="text-end">${money(r.average_order_value)}</td>
                    <td class="text-center">${trendBadge}</td>
                    <td class="pe-4 text-center"><span class="badge badge-light-${devColor}">${fmtNum(r.deviation_pct, 1)}%</span></td>
                </tr>
            `;
        }).join('');

        renderPagination(
            pagination,
            $('sfHistoricalPaginationInfo'),
            $('sfHistoricalPagination'),
            $('sfHistoricalPaginationWrap'),
            (p) => { state.historicalPage = p; load(); }
        );
    }

    // ─── Shared pagination ─────────────────────────────────
    function renderPagination(p, infoEl, ulEl, wrapEl, onPage) {
        if (!ulEl || !infoEl || !p) return;
        if (p.total <= p.per_page) { wrapEl?.classList.add('d-none'); return; }
        wrapEl?.classList.remove('d-none');
        infoEl.textContent = `Showing ${p.from} to ${p.to} of ${p.total}`;
        ulEl.innerHTML = '';

        const add = (page, label, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link'; a.href = '#'; a.textContent = label;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                if (disabled || active) return;
                onPage(page);
            });
            li.appendChild(a); ulEl.appendChild(li);
        };
        add(p.current_page - 1, 'Prev', false, p.current_page <= 1);
        for (let i = Math.max(1, p.current_page - 2); i <= Math.min(p.last_page, p.current_page + 2); i++) {
            add(i, i, i === p.current_page);
        }
        add(p.current_page + 1, 'Next', false, p.current_page >= p.last_page);
    }

    // ─── Export ────────────────────────────────────────────
    function exportCsv() {
        if (!lastPayload || !lastPayload.chart) {
            window.toastr?.error('Nothing to export yet.');
            return;
        }
        const { historical, forecast } = lastPayload.chart;
        const header = ['Date', 'Type', 'Sales', 'Confidence Low', 'Confidence High'];
        const rows = [header];

        (historical || []).forEach(r => rows.push([r.date, 'historical', r.sales, '', '']));
        (forecast   || []).forEach(r => rows.push([r.date, 'forecast',  r.sales, r.confidence_low, r.confidence_high]));

        const csv = rows.map(r => r.join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `sales_forecast_${isoDate(new Date())}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
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
    function isoDate(d) { return d.toISOString().split('T')[0]; }

    return { load };
})();
</script>