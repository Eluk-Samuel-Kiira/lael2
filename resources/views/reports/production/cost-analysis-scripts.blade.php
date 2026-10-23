<script>
window.CostAnalysis = (function () {
    'use strict';

    const ROUTE    = window.COST_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.COST_BOOTSTRAP || {};

    const state = {
        startDate:  BOOT.startDate  || '',
        endDate:    BOOT.endDate    || '',
        locationId: BOOT.locationId || '',
        variantId:  BOOT.variantId  || '',
        status:     BOOT.status     || 'all',
        costType:   BOOT.costType   || 'all',
        perPage:    Number(BOOT.perPage || 15),
        page:       1,
    };

    let monthlyChart = null;
    let statusChart  = null;
    let inFlight     = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('costApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('costClearBtn')?.addEventListener('click', resetFilters);
        $('exportCostAnalysisBtn')?.addEventListener('click', exportCsv);
        load();
    });

    function readFilters() {
        state.startDate  = $('costStartDate')?.value || '';
        state.endDate    = $('costEndDate')?.value   || '';
        state.locationId = $('costLocation')?.value  || '';
        state.status     = $('costStatus')?.value    || 'all';
        state.costType   = $('costCostType')?.value  || 'all';
        state.variantId  = $('costVariant')?.value   || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.variantId = '';
        state.status = 'all';
        state.costType = 'all';
        state.page = 1;
        if ($('costStartDate')) $('costStartDate').value = '';
        if ($('costEndDate'))   $('costEndDate').value   = '';
        if ($('costLocation'))  $('costLocation').value  = '';
        if ($('costStatus'))    $('costStatus').value    = 'all';
        if ($('costCostType'))  $('costCostType').value  = 'all';
        if ($('costVariant'))   $('costVariant').value   = '';
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)  params.set('start_date',  state.startDate);
        if (state.endDate)    params.set('end_date',    state.endDate);
        if (state.locationId) params.set('location_id', state.locationId);
        if (state.variantId)  params.set('variant_id',  state.variantId);
        if (state.status)     params.set('status',      state.status);
        if (state.costType)   params.set('cost_type',   state.costType);

        try {
            const res = await fetch(`${ROUTE}?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            render(json.data);
        } catch (err) {
            console.error('[cost-analysis] load failed:', err);
            window.toastr?.error('Failed to load cost analysis.');
        } finally {
            inFlight = false;
        }
    }

    function render(p) {
        renderSummary(p.costSummary);
        renderMonthly(p.monthlyCostTrends);
        renderStatus(p.costByStatus);
        renderCategoryTable('inputCategoryBody', 'inputCategoryEmpty', p.categoryInputCosts, p.costSummary.total_input_cost, 'danger', 'cost');
        renderCategoryTable('outputCategoryBody', 'outputCategoryEmpty', p.categoryOutputCosts, p.costSummary.total_output_cost, 'success', 'revenue');
        renderCostPerUnit(p.costPerUnit, p.pagination);
    }

    // ─── Summary ────────────────────────────────────────────────
    function renderSummary(s) {
        $('kpiInputCost').textContent   = money(s.total_input_cost);
        $('kpiInputCostSub').textContent = `${fmtNum(s.total_input_kg, 0)} kg · ${money(s.cost_per_kg_input)}/kg`;

        $('kpiOutputCost').textContent   = money(s.total_output_cost);
        $('kpiOutputCostSub').textContent = `${fmtNum(s.total_output_kg, 0)} kg · ${money(s.revenue_per_kg)}/kg`;

        const profit = Number(s.total_profit || 0);
        const cls    = profit > 0 ? 'success' : profit < 0 ? 'danger' : 'secondary';
        const profitEl = $('kpiProfit');
        profitEl.textContent = money(profit);
        profitEl.classList.remove('text-success', 'text-danger', 'text-secondary');
        profitEl.classList.add('text-' + cls);

        const card = $('kpiProfitCard');
        card.classList.remove('bg-light-success', 'bg-light-danger', 'bg-light-secondary',
                              'border-success', 'border-danger', 'border-secondary');
        card.classList.add(`bg-light-${cls}`, `border-${cls}`);

        const icon = $('kpiProfitIcon');
        icon.classList.remove('text-success', 'text-danger', 'text-secondary');
        icon.classList.add('text-' + cls);

        $('kpiProfitSub').textContent = `Margin: ${fmtNum(s.profit_margin, 1)}%`;

        $('kpiYield').textContent = fmtNum(s.overall_yield, 1) + '%';
        $('kpiLossSub').textContent = `Loss: ${fmtNum(s.total_loss_kg, 0)} kg (${fmtNum(s.loss_rate, 1)}%)`;

        $('kpiCostEfficiency').textContent = fmtNum(s.cost_efficiency, 1) + '%';

        const margin = Number(s.profit_margin || 0);
        const mCls   = margin > 0 ? 'success' : margin < 0 ? 'danger' : 'secondary';
        const mEl    = $('kpiProfitMargin');
        mEl.textContent = fmtNum(margin, 1) + '%';
        mEl.classList.remove('text-success', 'text-danger', 'text-secondary');
        mEl.classList.add('text-' + mCls);

        const mCard = $('kpiProfitMarginCard');
        mCard.classList.remove('bg-light-success', 'bg-light-danger', 'bg-light-secondary',
                               'border-success', 'border-danger', 'border-secondary');
        mCard.classList.add(`bg-light-${mCls}`, `border-${mCls}`);

        const mIcon = $('kpiProfitMarginIcon');
        mIcon.classList.remove('text-success', 'text-danger', 'text-secondary');
        mIcon.classList.add('text-' + mCls);

        const ratio = Number(s.total_input_cost) > 0
            ? Number(s.total_output_cost) / Number(s.total_input_cost)
            : 0;
        $('kpiRatio').textContent = ratio.toFixed(2);
    }

    // ─── Charts ─────────────────────────────────────────────────
    function renderMonthly(rows) {
        const el = $('monthlyCostChart');
        const empty = $('monthlyCostEmpty');
        if (!el) return;

        if (!rows || rows.length === 0) {
            el.classList.add('d-none');
            empty?.classList.remove('d-none');
            if (monthlyChart) { monthlyChart.destroy(); monthlyChart = null; }
            return;
        }
        el.classList.remove('d-none');
        empty?.classList.add('d-none');

        const options = {
            series: [
                { name: 'Batch Cost', data: rows.map(r => r.input_cost), type: 'bar'  },
                { name: 'Revenue',    data: rows.map(r => r.output_cost), type: 'bar'  },
                { name: 'Profit',     data: rows.map(r => r.profit),      type: 'line' },
                { name: 'Yield %',    data: rows.map(r => r.yield),       type: 'line' },
            ],
            chart: { type: 'bar', height: 350, toolbar: { show: true }, stacked: false, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: false, columnWidth: '35%' } },
            stroke: { width: [0, 0, 3, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: { categories: rows.map(r => r.month),
                     labels: { rotate: -45, trim: true, style: { fontSize: '11px' } } },
            yaxis: [
                { title: { text: 'Amount' },
                  labels: { formatter: v => CURRENCY + ' ' + Number(v).toFixed(0) } },
                { opposite: true, title: { text: 'Profit / Yield' },
                  labels: { formatter: v => Number(v).toFixed(0) } },
            ],
            colors: ['#F1416C', '#50CD89', '#3E97FF', '#FFC700'],
            tooltip: {
                shared: true, intersect: false,
                y: { formatter: (val, { seriesIndex }) =>
                    seriesIndex === 3 ? Number(val).toFixed(2) + '%'
                                      : CURRENCY + ' ' + Number(val).toFixed(2) },
            },
        };

        if (monthlyChart) monthlyChart.updateOptions(options, false, true);
        else {
            monthlyChart = new ApexCharts(el, options);
            monthlyChart.render();
        }
    }

    function renderStatus(rows) {
        const el = $('costByStatusChart');
        const empty = $('costByStatusEmpty');
        if (!el) return;

        if (!rows || rows.length === 0) {
            el.classList.add('d-none');
            empty?.classList.remove('d-none');
            if (statusChart) { statusChart.destroy(); statusChart = null; }
            return;
        }
        el.classList.remove('d-none');
        empty?.classList.add('d-none');

        const colorMap = {
            secondary: '#A1A5B7', warning: '#FFC700',
            success: '#50CD89', danger: '#F1416C',
        };

        const options = {
            series: rows.map(r => r.total_cost),
            chart:  { type: 'donut', height: 350, fontFamily: 'inherit' },
            labels: rows.map(r => r.status),
            colors: rows.map(r => colorMap[r.color] || '#A1A5B7'),
            legend: { position: 'bottom', fontSize: '12px' },
            plotOptions: { pie: { donut: {
                size: '65%',
                labels: {
                    show: true,
                    total: {
                        show: true, label: 'Batch Cost',
                        formatter: w => CURRENCY + ' ' + w.globals.seriesTotals.reduce((a, b) => a + b, 0).toFixed(0),
                    },
                },
            }}},
            tooltip: { y: { formatter: v => CURRENCY + ' ' + Number(v).toFixed(2) } },
        };

        if (statusChart) statusChart.updateOptions(options, false, true);
        else {
            statusChart = new ApexCharts(el, options);
            statusChart.render();
        }
    }

    // ─── Category tables ────────────────────────────────────────
    function renderCategoryTable(bodyId, emptyId, rows, total, color, valueKey) {
        const body = $(bodyId);
        const empty = $(emptyId);
        if (!body) return;

        if (!rows || rows.length === 0) {
            body.innerHTML = '';
            empty?.classList.remove('d-none');
            return;
        }
        empty?.classList.add('d-none');

        body.innerHTML = rows.map(r => {
            const pct = total > 0 ? (r.total_cost / total) * 100 : 0;
            const kg  = Number(r.total_kg);
            const perKg = kg > 0 ? Number(r.total_cost) / kg : 0;
            return `
                <tr>
                    <td class="fw-bold">${esc(r.category)}</td>
                    <td class="text-center">${fmtNum(kg, 0)}</td>
                    <td class="text-center text-${color}">${money(r.total_cost)}</td>
                    <td class="text-center">${money(perKg)}</td>
                    <td class="text-center">
                        <div class="d-flex align-items-center justify-content-center">
                            <span class="badge badge-light-${color}">${fmtNum(pct, 1)}%</span>
                            <div class="progress ms-2" style="width: 60px; height: 5px;">
                                <div class="progress-bar bg-${color}" style="width:${pct}%"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // ─── Cost per unit table ────────────────────────────────────
    function renderCostPerUnit(rows, pagination) {
        const body  = $('costPerUnitBody');
        const empty = $('costPerUnitEmpty');
        const wrap  = $('costPerUnitPaginationWrap');
        const badge = $('costPerUnitBadge');

        if (!rows || rows.length === 0) {
            body.innerHTML = '';
            empty?.classList.remove('d-none');
            wrap?.classList.add('d-none');
            if (badge) badge.textContent = '';
            return;
        }
        empty?.classList.add('d-none');
        wrap?.classList.remove('d-none');
        wrap.style.display = 'flex';
        if (badge) {
            badge.textContent = `Showing ${pagination.from} of ${pagination.total}`;
        }

        body.innerHTML = rows.map((r, i) => {
            const idx = (pagination.from || 1) + i;
            const margin = Number(r.avg_profit_margin);
            const marginCls = margin >= 30 ? 'success' : margin >= 10 ? 'warning' : 'danger';
            return `
                <tr>
                    <td>${idx}</td>
                    <td>
                        <div class="fw-bold">${esc(r.variant_name)}</div>
                        <div class="text-muted fs-8">
                            ${esc(r.variant_sku || '')}
                            ${r.weight > 0 ? ' · ' + fmtNum(r.weight, 2) + ' kg/bag' : ''}
                        </div>
                    </td>
                    <td class="text-center">${fmtNum(r.total_quantity, 0)}</td>
                    <td class="text-center fw-bold">${fmtNum(r.total_kg, 0)}</td>
                    <td class="text-center">${money(r.avg_cost_per_unit)}</td>
                    <td class="text-center">${money(r.avg_cost_per_kg)}</td>
                    <td class="text-center text-primary">${money(r.avg_selling_price)}</td>
                    <td class="text-center text-${r.avg_profit_per_unit >= 0 ? 'success' : 'danger'}">
                        ${money(r.avg_profit_per_unit)}
                    </td>
                    <td class="text-center text-${r.avg_profit_per_kg >= 0 ? 'success' : 'danger'}">
                        ${money(r.avg_profit_per_kg)}
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-${marginCls}">${fmtNum(margin, 1)}%</span>
                    </td>
                    <td class="text-center">${r.order_count}</td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('costPerUnitPaginationInfo');
        const ul   = $('costPerUnitPagination');
        if (!ul || !info) return;

        info.textContent = `Showing ${p.from} to ${p.to} of ${p.total}`;
        ul.innerHTML = '';

        const add = (page, label, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = label;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                if (disabled || active) return;
                state.page = page;
                load();
            });
            li.appendChild(a);
            ul.appendChild(li);
        };

        add(p.current_page - 1, 'Prev', false, p.current_page <= 1);
        for (let i = Math.max(1, p.current_page - 2); i <= Math.min(p.last_page, p.current_page + 2); i++) {
            add(i, i, i === p.current_page);
        }
        add(p.current_page + 1, 'Next', false, p.current_page >= p.last_page);
    }

    // ─── Export ─────────────────────────────────────────────────
    function exportCsv() {
        const params = new URLSearchParams();
        if (state.startDate)  params.set('start_date',  state.startDate);
        if (state.endDate)    params.set('end_date',    state.endDate);
        if (state.locationId) params.set('location_id', state.locationId);
        if (state.variantId)  params.set('variant_id',  state.variantId);
        if (state.status)     params.set('status',      state.status);
        window.location.href = `${window.COST_ROUTES.export}?${params.toString()}`;
    }

    // ─── Helpers ────────────────────────────────────────────────
    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
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