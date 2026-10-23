<script>
window.ReportSummary = (function () {
    'use strict';

    const URL_DATA = '{{ route("reports.production.summary-data") }}';
    const URL_EXPORT = '{{ route("reports.production.export") }}';
    const CURRENCY = '{{ currency_symbol() }}';
    const BOOT     = window.REPORT_BOOTSTRAP || {};

    const state = {
        startDate:  BOOT.startDate  || '',
        endDate:    BOOT.endDate    || '',
        locationId: BOOT.locationId || '',
        status:     BOOT.status     || 'all',
    };

    let trendChart = null;
    let statusChart = null;
    let inFlight = false;

    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('summaryApplyBtn')?.addEventListener('click', () => { readFilters(); load(); });
        $('summaryClearBtn')?.addEventListener('click', () => { resetFilters(); load(); });
        $('exportSummaryBtn')?.addEventListener('click', exportCsv);
        load();
    });

    function readFilters() {
        state.startDate  = $('summaryStartDate')?.value  || '';
        state.endDate    = $('summaryEndDate')?.value    || '';
        state.locationId = $('summaryLocation')?.value   || '';
        state.status     = $('summaryStatus')?.value     || 'all';
    }

    function resetFilters() {
        if ($('summaryStartDate')) $('summaryStartDate').value = '';
        if ($('summaryEndDate'))   $('summaryEndDate').value   = '';
        if ($('summaryLocation'))  $('summaryLocation').value  = '';
        if ($('summaryStatus'))    $('summaryStatus').value    = 'all';
        state.startDate = state.endDate = state.locationId = '';
        state.status = 'all';
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showState('loading');

        const params = new URLSearchParams();
        if (state.startDate)  params.set('start_date',  state.startDate);
        if (state.endDate)    params.set('end_date',    state.endDate);
        if (state.locationId) params.set('location_id', state.locationId);
        if (state.status)     params.set('status',      state.status);

        try {
            const res = await fetch(`${URL_DATA}?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed to load');

            render(json.data);
            showState('content');
        } catch (err) {
            console.error('[summary] load failed:', err);
            window.toastr?.error('Failed to load data.');
            showState('empty');
        } finally {
            inFlight = false;
        }
    }

    function render(p) {
        renderKpis(p.summary);
        renderMonthly(p.monthlyTrends);
        renderStatus(p.statusBreakdown);
        renderTopProducts(p.topProducts);
        renderTopMaterials(p.topMaterials);
        renderLocationBreakdown(p.locationBreakdown);
    }

    function renderKpis(s) {
        const set = (id, v) => { const el = $(id); if (el) el.textContent = v; };

        set('kpiTotalOrders', fmtNum(s.total_orders, 0));
        set('kpiCompletionRate', fmtNum(s.completion_rate, 1) + '% completed');

        set('kpiInputKg',  fmtNum(s.total_input_quantity, 0)  + ' kg');
        set('kpiOutputKg', fmtNum(s.total_output_quantity, 0) + ' kg');
        set('kpiYield',    fmtNum(s.overall_yield, 1) + '%');
        set('kpiLoss',     'Loss: ' + fmtNum(s.total_loss_quantity, 0) + ' kg (' + fmtNum(s.loss_rate, 1) + '%)');

        set('kpiRevenue',   fmt(s.total_output_cost));
        set('kpiBatchCost', fmt(s.total_input_cost));
        set('kpiAvgCost',   fmt(s.avg_cost_per_order) + ' per order');
        set('kpiCostEfficiency', 'Cost efficiency: ' + fmtNum(s.cost_efficiency, 1) + '%');

        set('kpiProfit',    fmt(s.total_profit));
        set('kpiAvgProfit', fmt(s.avg_profit_per_order) + ' per order');
        set('kpiMargin',    'Margin: ' + fmtNum(s.profit_margin, 1) + '%');

        set('kpiQualityRate', fmtNum(s.quality_acceptance_rate, 1) + '%');
        set('kpiAccepted', fmtNum(s.total_quality_accepted, 0));
        set('kpiRejected', fmtNum(s.total_quality_rejected, 0));
        set('kpiDefective', fmtNum(s.total_defective, 1));
        set('kpiWaste',     fmtNum(s.total_waste, 1));
        set('kpiWithPayment', fmtNum(s.orders_with_payment, 0));
        set('kpiWithoutPayment', fmtNum(s.orders_without_payment, 0));
        set('kpiAvgDuration', fmtNum(s.avg_duration_hours, 1) + 'h');

        const profit    = Number(s.total_profit || 0);
        const profitCls = profit > 0 ? 'success' : profit < 0 ? 'danger' : 'secondary';

        ['kpiProfit', 'kpiProfitIcon'].forEach(id => {
            const el = $(id); if (!el) return;
            el.classList.remove('text-success', 'text-danger', 'text-secondary');
            el.classList.add('text-' + profitCls);
        });
        const card = $('kpiProfitCard');
        if (card) {
            card.classList.remove('bg-light-success', 'bg-light-danger', 'bg-light-secondary',
                                  'border-success', 'border-danger', 'border-secondary');
            card.classList.add('bg-light-' + profitCls, 'border-' + profitCls);
        }
    }

    function renderMonthly(rows) {
        const chartEl = $('monthlyTrendChart');
        const emptyEl = $('monthlyTrendEmpty');
        if (!chartEl) return;

        if (!rows || rows.length === 0) {
            chartEl.classList.add('d-none');
            emptyEl?.classList.remove('d-none');
            if (trendChart) { trendChart.destroy(); trendChart = null; }
            return;
        }
        chartEl.classList.remove('d-none');
        emptyEl?.classList.add('d-none');

        const options = {
            series: [
                { name: 'Total Orders', data: rows.map(r => r.orders),    type: 'bar'  },
                { name: 'Completed',    data: rows.map(r => r.completed), type: 'bar'  },
                { name: 'Profit',       data: rows.map(r => Number(r.profit || 0)), type: 'line' },
                { name: 'Yield %',      data: rows.map(r => Number(r.yield  || 0)), type: 'line' },
            ],
            chart: { type: 'bar', height: 350, toolbar: { show: true }, stacked: false, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: false, columnWidth: '40%' } },
            stroke: { width: [0, 0, 3, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: {
                categories: rows.map(r => r.month),
                labels: { rotate: -45, trim: true, style: { fontSize: '11px' } },
            },
            yaxis: [
                { title: { text: 'Orders' } },
                { opposite: true, title: { text: 'Profit / Yield' },
                  labels: { formatter: v => Number(v).toFixed(0) } },
            ],
            colors: ['#3E97FF', '#50CD89', '#F1416C', '#FFC700'],
            tooltip: {
                shared: true, intersect: false,
                y: {
                    formatter: (val, { seriesIndex }) => {
                        if (seriesIndex === 2) return CURRENCY + ' ' + Number(val).toFixed(2);
                        if (seriesIndex === 3) return Number(val).toFixed(2) + '%';
                        return val + ' orders';
                    },
                },
            },
        };

        if (trendChart) {
            trendChart.updateOptions(options, false, true);
        } else {
            trendChart = new ApexCharts(chartEl, options);
            trendChart.render();
        }
    }

    function renderStatus(rows) {
        const chartEl = $('statusChart');
        const emptyEl = $('statusChartEmpty');
        if (!chartEl) return;

        if (!rows || rows.length === 0) {
            chartEl.classList.add('d-none');
            emptyEl?.classList.remove('d-none');
            if (statusChart) { statusChart.destroy(); statusChart = null; }
            return;
        }
        chartEl.classList.remove('d-none');
        emptyEl?.classList.add('d-none');

        const colorMap = {
            secondary: '#A1A5B7', warning: '#FFC700',
            success:   '#50CD89', danger:  '#F1416C',
            primary:   '#3E97FF', info:    '#7239EA',
        };

        const options = {
            series: rows.map(r => r.count),
            chart:  { type: 'donut', height: 350, fontFamily: 'inherit' },
            labels: rows.map(r => r.label),
            colors: rows.map(r => colorMap[r.color] || '#A1A5B7'),
            legend: { position: 'bottom', fontSize: '12px' },
            plotOptions: {
                pie: { donut: {
                    size: '65%',
                    labels: { show: true, total: {
                        show: true, label: 'Total',
                        formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0),
                    }},
                }},
            },
            tooltip: { y: { formatter: v => v + ' orders' } },
        };

        if (statusChart) {
            statusChart.updateOptions(options, false, true);
        } else {
            statusChart = new ApexCharts(chartEl, options);
            statusChart.render();
        }
    }

    function renderTopProducts(rows) {
        const body = $('topProductsBody');
        const empty = $('topProductsEmpty');
        if (!body) return;

        if (!rows || rows.length === 0) {
            body.innerHTML = '';
            empty?.classList.remove('d-none');
            return;
        }
        empty?.classList.add('d-none');

        body.innerHTML = rows.map((p, i) => `
            <tr>
                <td>${i + 1}</td>
                <td>
                    <div class="fw-bold">${esc(p.variant_name)}</div>
                    <div class="text-muted fs-8">${esc(p.sku || '')}</div>
                </td>
                <td class="text-center fw-bold text-success">
                    ${fmtNum(p.total_kg, 0)} kg
                    <div class="text-muted fs-8">${fmtNum(p.total_quantity, 0)} bags</div>
                </td>
                <td class="text-center">${fmt(p.total_revenue)}</td>
                <td class="text-center">${p.order_count}</td>
            </tr>
        `).join('');
    }

    function renderTopMaterials(rows) {
        const body = $('topMaterialsBody');
        const empty = $('topMaterialsEmpty');
        if (!body) return;

        if (!rows || rows.length === 0) {
            body.innerHTML = '';
            empty?.classList.remove('d-none');
            return;
        }
        empty?.classList.add('d-none');

        body.innerHTML = rows.map((m, i) => `
            <tr>
                <td>${i + 1}</td>
                <td>
                    <div class="fw-bold">${esc(m.variant_name)}</div>
                    <div class="text-muted fs-8">${esc(m.sku || '')}</div>
                </td>
                <td class="text-center fw-bold text-danger">${fmtNum(m.total_kg, 0)} kg</td>
                <td class="text-center">${fmt(m.total_cost)}</td>
                <td class="text-center">${m.order_count}</td>
            </tr>
        `).join('');
    }

    function renderLocationBreakdown(rows) {
        const wrap = $('locationBreakdownCard');
        const body = $('locationBreakdownBody');
        if (!wrap || !body) return;

        if (!rows || rows.length <= 1) {
            wrap.classList.add('d-none');
            return;
        }
        wrap.classList.remove('d-none');

        body.innerHTML = rows.map(loc => {
            const rate = loc.orders > 0 ? (loc.completed / loc.orders) * 100 : 0;
            const yieldBadge = loc.yield >= 90 ? 'success' : loc.yield >= 70 ? 'warning' : 'danger';
            const rateBadge  = rate >= 70 ? 'success' : rate >= 40 ? 'warning' : 'danger';
            const profitCls  = loc.profit >= 0 ? 'success' : 'danger';

            return `
                <tr>
                    <td class="fw-bold">${esc(loc.location_name)}</td>
                    <td class="text-center">${loc.orders}</td>
                    <td class="text-center text-success">${loc.completed}</td>
                    <td class="text-center"><span class="badge badge-light-${yieldBadge}">${fmtNum(loc.yield, 1)}%</span></td>
                    <td class="text-center">${fmt(loc.revenue)}</td>
                    <td class="text-center">${fmt(loc.total_cost)}</td>
                    <td class="text-center text-${profitCls}">${fmt(loc.profit)}</td>
                    <td class="text-center"><span class="badge badge-light-${rateBadge}">${fmtNum(rate, 1)}%</span></td>
                </tr>
            `;
        }).join('');
    }

    function showState(which) {
        $('summaryLoading')?.classList.toggle('d-none', which !== 'loading');
        $('summaryContent')?.classList.toggle('d-none', which !== 'content');
        if (which === 'empty') $('summaryContent')?.classList.remove('d-none');
    }

    async function exportCsv() {
        const params = new URLSearchParams();
        if (state.startDate)  params.set('start_date',  state.startDate);
        if (state.endDate)    params.set('end_date',    state.endDate);
        if (state.locationId) params.set('location_id', state.locationId);
        if (state.status)     params.set('status',      state.status);

        try {
            const res = await fetch(`${URL_EXPORT}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Export failed');

            const rows = json.data || [];
            if (rows.length === 0) { window.toastr?.info('No data'); return; }

            const headers = Object.keys(rows[0]);
            const csv = [
                headers.join(','),
                ...rows.map(r => headers.map(h => csvEscape(r[h])).join(',')),
            ].join('\n');

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url  = URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.href = url; a.download = json.filename || 'production_summary.csv';
            document.body.appendChild(a); a.click(); a.remove();
            URL.revokeObjectURL(url);
        } catch (err) {
            console.error('[summary] export failed:', err);
            window.toastr?.error(err.message);
        }
    }

    function csvEscape(v) {
        const s = String(v ?? '');
        return /[",\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
    }

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }
    function fmtNum(n, d) {
        d = d === undefined ? 0 : d;
        return Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: d, maximumFractionDigits: d,
        });
    }
    function fmt(n) { return CURRENCY + fmtNum(n, 2); }

    return { load };
})();
</script>