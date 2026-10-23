<script>
window.WasteReport = (function () {
    'use strict';

    const ROUTE    = window.W_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.W_BOOTSTRAP || {};

    const state = {
        startDate:  BOOT.startDate  || '',
        endDate:    BOOT.endDate    || '',
        locationId: BOOT.locationId || '',
        variantId:  BOOT.variantId  || '',
        status:     BOOT.status     || 'completed',
        wasteType:  BOOT.wasteType  || 'all',
        perPage:    Number(BOOT.perPage || 15),
        page:       1,
    };

    let trendChart = null;
    let catChart   = null;
    let inFlight   = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('wApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('wClearBtn')?.addEventListener('click', resetFilters);
        $('wExportBtn')?.addEventListener('click', exportCsv);
        load();
    });

    function readFilters() {
        state.startDate  = $('wStartDate')?.value || '';
        state.endDate    = $('wEndDate')?.value   || '';
        state.locationId = $('wLocation')?.value  || '';
        state.status     = $('wStatus')?.value    || 'completed';
        state.wasteType  = $('wWasteType')?.value || 'all';
        state.variantId  = $('wVariant')?.value   || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.variantId = '';
        state.status = 'completed';
        state.wasteType = 'all';
        state.page = 1;
        if ($('wStartDate')) $('wStartDate').value = '';
        if ($('wEndDate'))   $('wEndDate').value   = '';
        if ($('wStatus'))    $('wStatus').value    = 'completed';
        if ($('wWasteType')) $('wWasteType').value = 'all';
        if (window.jQuery) jQuery('#wLocation, #wVariant').val(null).trigger('change.select2');
        load();
    }

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
        if (state.wasteType)  params.set('waste_type',  state.wasteType);

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
            console.error('[waste] load failed:', err);
            window.toastr?.error('Failed to load waste report.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('wSkeleton')?.classList.toggle('d-none', !v);
        $('wContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.wasteSummary);
        renderSeverity(p.severityDistribution, p.wasteSummary.total_orders);
        renderTrend(p.monthlyWaste);
        renderCategory(p.categoryWasteSummary);
        renderTop(p.productWasteSummary);
        renderOrders(p.wasteByOrder, p.pagination);
    }

    function renderSummary(s) {
        const rateColor = s.total_waste_rate <= 5 ? 'success'
                        : s.total_waste_rate <= 15 ? 'warning' : 'danger';
        $('wRate').textContent = fmtNum(s.total_waste_rate, 1) + '%';
        $('wRate').className = `fs-1 fw-bold text-${rateColor}`;
        $('wRateSub').textContent = `${fmtNum(s.total_waste_kg, 0)} kg wasted`;
        applyColor('wRateCard', 'wRateIcon', rateColor);

        $('wInRate').textContent = fmtNum(s.input_waste_rate, 1) + '%';
        $('wInRateSub').textContent =
            `${fmtNum(s.total_input_waste_kg, 0)} kg of ${fmtNum(s.total_input_kg, 0)} kg`;
        $('wDefRate').textContent = fmtNum(s.output_defective_rate, 1) + '%';
        $('wDefRateSub').textContent =
            `${fmtNum(s.total_defective_kg, 0)} kg of ${fmtNum(s.total_output_kg, 0)} kg`;

        const costColor = s.waste_cost_percentage <= 5 ? 'success'
                        : s.waste_cost_percentage <= 15 ? 'warning' : 'danger';
        $('wCost').textContent = money(s.waste_cost);
        $('wCost').className = `fs-2 fw-bold text-${costColor}`;
        $('wCostSub').textContent = `${fmtNum(s.waste_cost_percentage, 1)}% of batch cost`;
        applyColor('wCostCard', 'wCostIcon', costColor);

        $('wGood').textContent = fmtNum(s.good_output, 0) + ' kg';
        $('wGoodSub').textContent = `${fmtNum(s.good_rate, 1)}% good rate`;
        $('wOrders').textContent = s.total_orders;
        $('wOrdersSub').textContent = `${s.completed_orders} completed`;
        $('wInCost').textContent = money(s.input_waste_cost);
        $('wDefCost').textContent = money(s.defective_cost);
    }

    function applyColor(cardId, iconId, color) {
        const card = $(cardId);
        if (card) {
            card.classList.remove('bg-light-success','bg-light-danger','bg-light-warning','bg-light-secondary',
                                  'border-success','border-danger','border-warning','border-secondary');
            card.classList.add(`bg-light-${color}`, `border-${color}`, 'border-dashed');
        }
        const icon = $(iconId);
        if (icon) {
            icon.classList.remove('text-success','text-danger','text-warning','text-secondary');
            icon.classList.add('text-' + color);
        }
    }

    function renderSeverity(dist, total) {
        const row = $('wSeverityRow');
        if (!row) return;
        const colors = { low: 'success', medium: 'warning', high: 'danger', critical: 'dark' };
        const labels = {
            low:      'Low (≤ 5%)',
            medium:   'Medium (5–15%)',
            high:     'High (15–30%)',
            critical: 'Critical (> 30%)',
        };
        row.innerHTML = Object.entries(dist).map(([sev, count]) => {
            const pct = total > 0 ? (count / total) * 100 : 0;
            return `
                <div class="col-md-3">
                    <div class="card card-flush bg-light-${colors[sev]} border border-${colors[sev]} border-dashed">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted">${labels[sev]}</span>
                                <div class="fs-2 fw-bold text-${colors[sev]}">${count}</div>
                            </div>
                            <span class="badge badge-light-${colors[sev]} fs-6">${fmtNum(pct, 1)}%</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderTrend(rows) {
        const el = $('wTrendChart');
        const empty = $('wTrendEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (trendChart) { trendChart.destroy(); trendChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [
                { name: 'Input Waste %', data: rows.map(r => r.input_waste_rate), type: 'line' },
                { name: 'Defective %',   data: rows.map(r => r.defective_rate),   type: 'line' },
                { name: 'Total Waste %', data: rows.map(r => r.total_rate),       type: 'line' },
                { name: 'Waste Cost',    data: rows.map(r => r.waste_cost),       type: 'column' },
            ],
            chart: { type: 'line', height: 350, toolbar: { show: true }, zoom: { enabled: true }, fontFamily: 'inherit' },
            stroke: { width: [3, 3, 4, 0], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: { categories: rows.map(r => r.month),
                     labels: { rotate: -45, trim: true, style: { fontSize: '11px' } } },
            yaxis: [
                { title: { text: 'Rate (%)' }, labels: { formatter: v => Number(v).toFixed(0) + '%' } },
                { opposite: true, title: { text: 'Waste Cost' },
                  labels: { formatter: v => CURRENCY + ' ' + Number(v).toFixed(0) } },
            ],
            colors: ['#FFC700', '#F1416C', '#3E97FF', '#A1A5B7'],
            tooltip: { shared: true, intersect: false,
                       y: { formatter: (val, { seriesIndex }) =>
                           seriesIndex === 3 ? CURRENCY + ' ' + Number(val).toFixed(2)
                                             : Number(val).toFixed(2) + '%' } },
            legend: { position: 'top', horizontalAlign: 'center' },
        };

        if (trendChart) trendChart.updateOptions(options, false, true);
        else { trendChart = new ApexCharts(el, options); trendChart.render(); }
    }

    function renderCategory(rows) {
        const el = $('wCatChart');
        const empty = $('wCatEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (catChart) { catChart.destroy(); catChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [
                { name: 'Input Waste %', data: rows.map(r => r.input_waste_rate), type: 'bar'  },
                { name: 'Defective %',   data: rows.map(r => r.defective_rate),   type: 'bar'  },
                { name: 'Total Rate %',  data: rows.map(r => r.total_rate),       type: 'line' },
            ],
            chart: { type: 'bar', height: 350, toolbar: { show: true }, stacked: false, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: true, columnWidth: '50%' } },
            stroke: { width: [0, 0, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: { categories: rows.map(r => r.category), labels: { style: { fontSize: '11px' } } },
            yaxis: { title: { text: 'Rate (%)' }, labels: { formatter: v => Number(v).toFixed(0) + '%' } },
            colors: ['#FFC700', '#F1416C', '#3E97FF'],
            tooltip: { shared: true, intersect: false,
                       y: { formatter: v => Number(v).toFixed(1) + '%' } },
            legend: { position: 'bottom', horizontalAlign: 'center' },
        };

        if (catChart) catChart.updateOptions(options, false, true);
        else { catChart = new ApexCharts(el, options); catChart.render(); }
    }

    function renderTop(rows) {
        const card = $('wTopCard');
        const body = $('wTopBody');
        if (!body) return;
        if (!rows || rows.length === 0) { card?.classList.add('d-none'); return; }
        card?.classList.remove('d-none');

        body.innerHTML = rows.map((r, i) => {
            const rColor = r.total_rate <= 5 ? 'success'
                         : r.total_rate <= 15 ? 'warning' : 'danger';
            return `
                <tr>
                    <td>${i + 1}</td>
                    <td>
                        <div class="fw-bold">${esc(r.variant_name)}</div>
                        <div class="text-muted fs-8">${esc(r.variant_sku || '')}</div>
                    </td>
                    <td><span class="badge badge-light-primary">${esc(r.category)}</span></td>
                    <td class="text-center text-warning">${fmtNum(r.input_waste, 0)}</td>
                    <td class="text-center text-danger">${fmtNum(r.output_waste, 0)}</td>
                    <td class="text-center fw-bold">${fmtNum(r.total_waste, 0)}</td>
                    <td class="text-center"><span class="badge badge-light-${rColor}">${fmtNum(r.total_rate, 1)}%</span></td>
                    <td class="text-center">${money(r.waste_cost)}</td>
                </tr>
            `;
        }).join('');
    }

    function renderOrders(rows, pagination) {
        const body = $('wOrderBody');
        const empty = $('wOrderEmpty');
        const wrap = $('wPaginationWrap');
        const badge = $('wOrderBadge');
        if (!body) return;

        if (!rows || rows.length === 0) {
            body.innerHTML = ''; empty?.classList.remove('d-none'); wrap?.classList.add('d-none');
            if (badge) badge.textContent = '';
            return;
        }
        empty?.classList.add('d-none');
        wrap?.classList.remove('d-none');
        if (badge) badge.textContent = `Showing ${pagination.from} of ${pagination.total}`;

        body.innerHTML = rows.map((r, i) => {
            const idx = (pagination.from || 1) + i;
            const rColor = r.total_waste_rate <= 5 ? 'success'
                         : r.total_waste_rate <= 15 ? 'warning' : 'danger';
            return `
                <tr>
                    <td>${idx}</td>
                    <td>
                        <div class="fw-bold">${esc(r.production_number)}</div>
                        <div class="text-muted fs-8">${esc(r.created_at)}</div>
                    </td>
                    <td><span class="badge badge-light-${esc(r.status_badge)}">${esc(r.status)}</span></td>
                    <td>${esc(r.location)}</td>
                    <td class="text-center text-warning">${fmtNum(r.input_waste, 0)}</td>
                    <td class="text-center">${fmtNum(r.input_waste_rate, 1)}%</td>
                    <td class="text-center text-danger">${fmtNum(r.defective, 0)}</td>
                    <td class="text-center">${fmtNum(r.defective_rate, 1)}%</td>
                    <td class="text-center fw-bold">${fmtNum(r.total_waste, 0)}</td>
                    <td class="text-center"><span class="badge badge-light-${rColor}">${fmtNum(r.total_waste_rate, 1)}%</span></td>
                    <td class="text-center">${money(r.waste_cost)}</td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('wPaginationInfo');
        const ul   = $('wPagination');
        if (!ul || !info) return;

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

    function exportCsv() {
        const params = new URLSearchParams();
        if (state.startDate)  params.set('start_date',  state.startDate);
        if (state.endDate)    params.set('end_date',    state.endDate);
        if (state.locationId) params.set('location_id', state.locationId);
        if (state.variantId)  params.set('variant_id',  state.variantId);
        if (state.status)     params.set('status',      state.status);
        if (state.wasteType)  params.set('waste_type',  state.wasteType);
        window.location.href = `${window.W_ROUTES.export}?${params.toString()}`;
    }

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