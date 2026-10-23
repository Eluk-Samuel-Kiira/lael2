<script>
window.Efficiency = (function () {
    'use strict';

    const ROUTE    = window.EFF_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.EFF_BOOTSTRAP || {};

    const state = {
        startDate:  BOOT.startDate  || '',
        endDate:    BOOT.endDate    || '',
        locationId: BOOT.locationId || '',
        variantId:  BOOT.variantId  || '',
        status:     BOOT.status     || 'completed',
        perPage:    Number(BOOT.perPage || 15),
        page:       1,
    };

    let trendChart    = null;
    let locationChart = null;
    let inFlight      = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('effApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('effClearBtn')?.addEventListener('click', resetFilters);
        $('exportEfficiencyBtn')?.addEventListener('click', exportCsv);
        load();
    });

    function readFilters() {
        state.startDate  = $('effStartDate')?.value || '';
        state.endDate    = $('effEndDate')?.value   || '';
        state.locationId = $('effLocation')?.value  || '';
        state.variantId  = $('effVariant')?.value   || '';
        state.status     = $('effStatus')?.value    || 'completed';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.variantId = '';
        state.status = 'completed';
        state.page = 1;
        ['effStartDate','effEndDate','effLocation','effVariant'].forEach(id => { if ($(id)) $(id).value = ''; });
        if ($('effStatus')) $('effStatus').value = 'completed';
        load();
    }

    // ─── Load ────────────────────────────────────────────────────
    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)  params.set('start_date',  state.startDate);
        if (state.endDate)    params.set('end_date',    state.endDate);
        if (state.locationId) params.set('location_id', state.locationId);
        if (state.variantId)  params.set('variant_id',  state.variantId);
        if (state.status)     params.set('status',      state.status);

        try {
            const res = await fetch(`${ROUTE}?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            render(json.data);
            showSkeleton(false);
        } catch (err) {
            console.error('[efficiency] load failed:', err);
            window.toastr?.error('Failed to load efficiency data.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    /**
     * Toggle skeleton vs content. Skeleton replaces the whole content area
     * with shimmer placeholders so there is zero layout shift between
     * load → render (Facebook / YouTube pattern).
     */
    function showSkeleton(visible) {
        $('effSkeleton')?.classList.toggle('d-none', !visible);
        $('effContent')?.classList.toggle('d-none', visible);
    }

    // ─── Render ─────────────────────────────────────────────────
    function render(p) {
        renderSummary(p.efficiencySummary);
        renderMonthlyTrends(p.monthlyEfficiency);
        renderLocationChart(p.efficiencyByLocation);
        renderTopProducts(p.productEfficiencySummary);
        renderEfficiencyOrders(p.efficiencyByOrder, p.pagination);
    }

    function renderSummary(s) {
        // Yield
        const yldColor = s.overall_yield >= 90 ? 'success' : s.overall_yield >= 70 ? 'warning' : 'danger';
        $('kpiYield').textContent = fmtNum(s.overall_yield, 1) + '%';
        $('kpiYield').className = `fs-1 fw-bold text-${yldColor}`;
        $('kpiYieldSub').textContent = `${fmtNum(s.total_output_kg, 0)} / ${fmtNum(s.total_input_kg, 0)} kg`;
        $('kpiYieldLoss').textContent = `Loss: ${fmtNum(s.total_loss_kg, 0)} kg (${fmtNum(s.loss_rate, 1)}%)`;
        applyColorClasses('kpiYieldCard', 'kpiYieldIcon', yldColor);

        // Cost efficiency
        const ceColor = s.overall_cost_efficiency >= 120 ? 'success'
                      : s.overall_cost_efficiency >= 100 ? 'warning' : 'danger';
        $('kpiCostEff').textContent = fmtNum(s.overall_cost_efficiency, 1) + '%';
        $('kpiCostEff').className = `fs-1 fw-bold text-${ceColor}`;
        $('kpiCostEffSub').textContent =
            `${money(s.total_output_cost)} revenue / ${money(s.total_cost)} cost`;
        applyColorClasses('kpiCostEffCard', 'kpiCostEffIcon', ceColor);

        // Quality
        const qColor = s.quality_rate >= 95 ? 'success' : s.quality_rate >= 80 ? 'warning' : 'danger';
        $('kpiQuality').textContent = fmtNum(s.quality_rate, 1) + '%';
        $('kpiQuality').className = `fs-1 fw-bold text-${qColor}`;
        $('kpiQualitySub').textContent = `Defective: ${fmtNum(s.total_defective_kg, 0)} kg`;
        applyColorClasses('kpiQualityCard', 'kpiQualityIcon', qColor);

        // Profit margin
        const pmColor = s.profit_margin >= 20 ? 'success' : s.profit_margin >= 10 ? 'warning' : 'danger';
        $('kpiProfit').textContent = fmtNum(s.profit_margin, 1) + '%';
        $('kpiProfit').className = `fs-1 fw-bold text-${pmColor}`;
        $('kpiProfitSub').textContent = money(s.total_profit);
        applyColorClasses('kpiProfitCard', 'kpiProfitIcon', pmColor);

        // Secondary metrics
        const wColor = s.waste_rate <= 5 ? 'success' : s.waste_rate <= 15 ? 'warning' : 'danger';
        $('kpiWasteRate').textContent = fmtNum(s.waste_rate, 1) + '%';
        $('kpiWasteRate').className = `fs-2 fw-bold text-${wColor}`;
        $('kpiWasteKg').textContent = `${fmtNum(s.total_waste_kg, 0)} kg`;
        $('kpiWasteIcon').className = `ki-duotone ki-trash fs-2tx text-${wColor}`;

        $('kpiDuration').textContent = fmtNum(s.avg_duration_hours, 2) + 'h';
        $('kpiCompletion').textContent = `${s.completed_orders} / ${s.total_orders}`;
    }

    function applyColorClasses(cardId, iconId, color) {
        const card = $(cardId);
        if (card) {
            card.classList.remove('bg-light-success','bg-light-danger','bg-light-warning',
                                  'border-success','border-danger','border-warning');
            card.classList.add(`bg-light-${color}`, `border-${color}`, 'border-dashed');
        }
        const icon = $(iconId);
        if (icon) {
            icon.classList.remove('text-success','text-danger','text-warning');
            icon.classList.add('text-' + color);
        }
    }

    // ─── Charts ────────────────────────────────────────────────
    function renderMonthlyTrends(rows) {
        const el = $('efficiencyTrendChart');
        const empty = $('efficiencyTrendEmpty');
        if (!el) return;

        if (!rows || rows.length === 0) {
            el.classList.add('d-none');
            empty?.classList.remove('d-none');
            if (trendChart) { trendChart.destroy(); trendChart = null; }
            return;
        }
        el.classList.remove('d-none');
        empty?.classList.add('d-none');

        const options = {
            series: [
                { name: 'Yield',           data: rows.map(r => r.yield),           type: 'line' },
                { name: 'Cost Efficiency', data: rows.map(r => r.cost_efficiency), type: 'line' },
                { name: 'Quality Rate',    data: rows.map(r => r.quality_rate),    type: 'line' },
                { name: 'Profit Margin',   data: rows.map(r => r.profit_margin),   type: 'line' },
            ],
            chart: { type: 'line', height: 350, toolbar: { show: true }, zoom: { enabled: true }, fontFamily: 'inherit' },
            stroke: { width: [3, 3, 3, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: { categories: rows.map(r => r.month),
                     labels: { rotate: -45, trim: true, style: { fontSize: '11px' } } },
            yaxis: { title: { text: 'Percentage (%)' },
                     labels: { formatter: v => Number(v).toFixed(0) + '%' } },
            colors: ['#3E97FF', '#50CD89', '#FFC700', '#F1416C'],
            tooltip: { shared: true, intersect: false,
                       y: { formatter: v => Number(v).toFixed(1) + '%' } },
            legend: { position: 'top', horizontalAlign: 'center' },
        };

        if (trendChart) trendChart.updateOptions(options, false, true);
        else {
            trendChart = new ApexCharts(el, options);
            trendChart.render();
        }
    }

    function renderLocationChart(rows) {
        const el = $('locationEfficiencyChart');
        const empty = $('locationEfficiencyEmpty');
        if (!el) return;

        if (!rows || rows.length === 0) {
            el.classList.add('d-none');
            empty?.classList.remove('d-none');
            if (locationChart) { locationChart.destroy(); locationChart = null; }
            return;
        }
        el.classList.remove('d-none');
        empty?.classList.add('d-none');

        const options = {
            series: [
                { name: 'Yield',         data: rows.map(r => r.yield),         type: 'bar'  },
                { name: 'Quality Rate',  data: rows.map(r => r.quality_rate),  type: 'bar'  },
                { name: 'Profit Margin', data: rows.map(r => r.profit_margin), type: 'line' },
            ],
            chart: { type: 'bar', height: 350, toolbar: { show: true }, stacked: false, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: true, columnWidth: '50%' } },
            stroke: { width: [0, 0, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: { categories: rows.map(r => r.location_name),
                     labels: { style: { fontSize: '11px' } } },
            yaxis: { title: { text: 'Percentage (%)' },
                     labels: { formatter: v => Number(v).toFixed(0) + '%' } },
            colors: ['#3E97FF', '#50CD89', '#F1416C'],
            tooltip: { shared: true, intersect: false,
                       y: { formatter: v => Number(v).toFixed(1) + '%' } },
            legend: { position: 'bottom', horizontalAlign: 'center' },
        };

        if (locationChart) locationChart.updateOptions(options, false, true);
        else {
            locationChart = new ApexCharts(el, options);
            locationChart.render();
        }
    }

    // ─── Tables ────────────────────────────────────────────────
    function renderTopProducts(rows) {
        const body = $('topProductsBody');
        const card = $('topProductsCard');
        if (!body) return;

        if (!rows || rows.length === 0) {
            card?.classList.add('d-none');
            return;
        }
        card?.classList.remove('d-none');

        body.innerHTML = rows.map((p, i) => {
            const marginColor  = p.profit_margin >= 30 ? 'success' : p.profit_margin >= 10 ? 'warning' : 'danger';
            const qualityColor = p.quality_rate >= 95 ? 'success' : p.quality_rate >= 80 ? 'warning' : 'danger';
            return `
                <tr>
                    <td>${i + 1}</td>
                    <td>
                        <div class="fw-bold">${esc(p.variant_name)}</div>
                        <div class="text-muted fs-8">${esc(p.variant_sku || '')}</div>
                    </td>
                    <td><span class="badge badge-light-primary">${esc(p.category)}</span></td>
                    <td class="text-center">${fmtNum(p.total_quantity, 0)}</td>
                    <td class="text-center fw-bold">${fmtNum(p.total_kg, 0)}</td>
                    <td class="text-center">
                        <span class="badge badge-light-${marginColor}">${fmtNum(p.profit_margin, 1)}%</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-${qualityColor}">${fmtNum(p.quality_rate, 1)}%</span>
                    </td>
                    <td class="text-center">${money(p.cost_per_kg)}</td>
                    <td class="text-center text-${p.profit_per_kg >= 0 ? 'success' : 'danger'}">${money(p.profit_per_kg)}</td>
                </tr>
            `;
        }).join('');
    }

    function renderEfficiencyOrders(rows, pagination) {
        const body = $('efficiencyOrderBody');
        const empty = $('efficiencyOrderEmpty');
        const wrap = $('effPaginationWrap');
        const badge = $('effOrderBadge');
        if (!body) return;

        if (!rows || rows.length === 0) {
            body.innerHTML = '';
            empty?.classList.remove('d-none');
            wrap?.classList.add('d-none');
            if (badge) badge.textContent = '';
            return;
        }
        empty?.classList.add('d-none');
        wrap?.classList.remove('d-none');
        if (badge) badge.textContent = `Showing ${pagination.from} of ${pagination.total}`;

        body.innerHTML = rows.map((r, i) => {
            const idx = (pagination.from || 1) + i;
            const yieldColor   = r.yield           >= 90 ? 'success' : r.yield           >= 70 ? 'warning' : 'danger';
            const costColor    = r.cost_efficiency >= 120 ? 'success' : r.cost_efficiency >= 100 ? 'warning' : 'danger';
            const qualityColor = r.quality_rate    >= 95 ? 'success' : r.quality_rate    >= 80 ? 'warning' : 'danger';
            const wasteColor   = r.waste_rate      <= 5 ? 'success' : r.waste_rate      <= 15 ? 'warning' : 'danger';
            return `
                <tr>
                    <td>${idx}</td>
                    <td>
                        <div class="fw-bold">${esc(r.production_number)}</div>
                        <div class="text-muted fs-8">
                            ${esc(r.created_at)} · ${fmtNum(r.input_kg, 0)} → ${fmtNum(r.output_kg, 0)} kg
                        </div>
                    </td>
                    <td><span class="badge badge-light-${esc(r.status_badge)}">${esc(r.status_label)}</span></td>
                    <td>${esc(r.location)}</td>
                    <td class="text-center"><span class="badge badge-light-${yieldColor}">${fmtNum(r.yield, 1)}%</span></td>
                    <td class="text-center"><span class="badge badge-light-${costColor}">${fmtNum(r.cost_efficiency, 1)}%</span></td>
                    <td class="text-center"><span class="badge badge-light-${qualityColor}">${fmtNum(r.quality_rate, 1)}%</span></td>
                    <td class="text-center"><span class="badge badge-light-${wasteColor}">${fmtNum(r.waste_rate, 1)}%</span></td>
                    <td class="text-center">${fmtNum(r.duration_hours, 2)}h</td>
                    <td class="text-center text-${r.profit >= 0 ? 'success' : 'danger'}">${money(r.profit)}</td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('effPaginationInfo');
        const ul   = $('effPagination');
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

    // ─── Export ────────────────────────────────────────────────
    function exportCsv() {
        const params = new URLSearchParams();
        if (state.startDate)  params.set('start_date',  state.startDate);
        if (state.endDate)    params.set('end_date',    state.endDate);
        if (state.locationId) params.set('location_id', state.locationId);
        if (state.variantId)  params.set('variant_id',  state.variantId);
        if (state.status)     params.set('status',      state.status);
        window.location.href = `${window.EFF_ROUTES.export}?${params.toString()}`;
    }

    // ─── Helpers ───────────────────────────────────────────────
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