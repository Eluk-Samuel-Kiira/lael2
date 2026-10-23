<script>
window.InputOutput = (function () {
    'use strict';

    const ROUTE    = window.IO_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.IO_BOOTSTRAP || {};

    const state = {
        startDate:      BOOT.startDate      || '',
        endDate:        BOOT.endDate        || '',
        locationId:     BOOT.locationId     || '',
        variantId:      BOOT.variantId      || '',
        status:         BOOT.status         || 'completed',
        comparisonType: BOOT.comparisonType || 'quantity',
        perPage:        Number(BOOT.perPage || 15),
        page:           1,
    };

    let monthlyChart  = null;
    let categoryChart = null;
    let inFlight      = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('ioApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('ioClearBtn')?.addEventListener('click', resetFilters);
        $('ioExportBtn')?.addEventListener('click', exportCsv);
        load();
    });

    function readFilters() {
        state.startDate      = $('ioStartDate')?.value || '';
        state.endDate        = $('ioEndDate')?.value   || '';
        state.locationId     = $('ioLocation')?.value  || '';
        state.status         = $('ioStatus')?.value    || 'completed';
        state.comparisonType = $('ioComparisonType')?.value || 'quantity';
        state.variantId      = $('ioVariant')?.value   || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.variantId = '';
        state.status = 'completed';
        state.comparisonType = 'quantity';
        state.page = 1;
        if ($('ioStartDate')) $('ioStartDate').value = '';
        if ($('ioEndDate'))   $('ioEndDate').value   = '';
        if ($('ioStatus'))    $('ioStatus').value    = 'completed';
        if ($('ioComparisonType')) $('ioComparisonType').value = 'quantity';
        if (window.jQuery) jQuery('#ioLocation, #ioVariant').val(null).trigger('change.select2');
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)      params.set('start_date',      state.startDate);
        if (state.endDate)        params.set('end_date',        state.endDate);
        if (state.locationId)     params.set('location_id',     state.locationId);
        if (state.variantId)      params.set('variant_id',      state.variantId);
        if (state.status)         params.set('status',          state.status);
        if (state.comparisonType) params.set('comparison_type', state.comparisonType);

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
            console.error('[input-output] load failed:', err);
            window.toastr?.error('Failed to load input/output comparison.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('ioSkeleton')?.classList.toggle('d-none', !v);
        $('ioContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.inputOutputSummary);
        renderMonthly(p.monthlyComparison);
        renderCategory(p.categoryComparison);
        renderTopProducts(p.productSummary);
        renderOrders(p.comparisonByOrder, p.pagination);
    }

    function renderSummary(s) {
        $('ioInKg').textContent  = fmtNum(s.total_input_kg, 0);
        $('ioOutKg').textContent = fmtNum(s.total_output_kg, 0);
        $('ioQtyRatio').textContent = `Ratio: ${fmtNum(s.quantity_ratio, 3)}`;
        $('ioLossKg').textContent = `Loss: ${fmtNum(s.loss_kg, 0)} kg (${fmtNum(s.loss_rate, 1)}%)`;

        $('ioInCost').textContent = money(s.total_input_cost);
        $('ioRev').textContent    = money(s.total_revenue);
        $('ioCostRatio').textContent = `Ratio: ${fmtNum(s.cost_ratio, 3)}`;
        const netEl = $('ioNetValue');
        netEl.textContent = `Net: ${money(s.net_value)}`;
        netEl.className = `fs-8 text-${s.net_value >= 0 ? 'success' : 'danger'}`;

        // Yield
        const yColor = s.quantity_efficiency >= 90 ? 'success'
                     : s.quantity_efficiency >= 70 ? 'warning' : 'danger';
        $('ioYield').textContent = fmtNum(s.quantity_efficiency, 1) + '%';
        $('ioYield').className = `fs-1 fw-bold text-${yColor}`;
        $('ioYieldSub').textContent = s.quantity_ratio >= 1 ? 'Efficient' : 'Lossy';
        setCardColor('ioYieldCard', 'ioYieldIcon', yColor);

        // Cost efficiency
        const ceColor = s.cost_efficiency >= 120 ? 'success'
                      : s.cost_efficiency >= 100 ? 'warning' : 'danger';
        $('ioCostEff').textContent = fmtNum(s.cost_efficiency, 1) + '%';
        $('ioCostEff').className = `fs-1 fw-bold text-${ceColor}`;
        $('ioCostEffSub').textContent = s.cost_ratio >= 1 ? 'Profitable' : 'Loss-making';
        setCardColor('ioCostEffCard', 'ioCostEffIcon', ceColor);

        $('ioTotalOrders').textContent     = s.total_orders;
        $('ioCompletedOrders').textContent = `${s.completed_orders} completed`;
        $('ioAvgIn').textContent           = fmtNum(s.avg_input_qty_per_order, 0) + ' kg';
        $('ioAvgOut').textContent          = fmtNum(s.avg_output_qty_per_order, 0) + ' kg';
        $('ioRevPerKg').textContent        = money(s.revenue_per_kg);
        $('ioCostPerKgIn').textContent     = `Batch cost / kg: ${money(s.cost_per_kg_input)}`;
    }

    function setCardColor(cardId, iconId, color) {
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

    function renderMonthly(rows) {
        const el = $('ioMonthlyChart');
        const empty = $('ioMonthlyEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (monthlyChart) { monthlyChart.destroy(); monthlyChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [
                { name: 'Input (kg)',  data: rows.map(r => r.input_quantity),  type: 'bar'  },
                { name: 'Output (kg)', data: rows.map(r => r.output_quantity), type: 'bar'  },
                { name: 'Yield %',     data: rows.map(r => r.yield),           type: 'line' },
            ],
            chart: { type: 'bar', height: 350, toolbar: { show: true }, stacked: false, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: false, columnWidth: '35%' } },
            stroke: { width: [0, 0, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: { categories: rows.map(r => r.month),
                     labels: { rotate: -45, trim: true, style: { fontSize: '11px' } } },
            yaxis: [
                { title: { text: 'Quantity (kg)' }, labels: { formatter: v => Number(v).toFixed(0) } },
                { opposite: true, title: { text: 'Yield %' }, labels: { formatter: v => Number(v).toFixed(0) + '%' } },
            ],
            colors: ['#F1416C', '#50CD89', '#3E97FF'],
            tooltip: { shared: true, intersect: false,
                       y: { formatter: (val, { seriesIndex }) =>
                           seriesIndex === 2 ? Number(val).toFixed(2) + '%'
                                             : Number(val).toFixed(1) + ' kg' } },
        };

        if (monthlyChart) monthlyChart.updateOptions(options, false, true);
        else { monthlyChart = new ApexCharts(el, options); monthlyChart.render(); }
    }

    function renderCategory(rows) {
        const el = $('ioCategoryChart');
        const empty = $('ioCategoryEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (categoryChart) { categoryChart.destroy(); categoryChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [
                { name: 'Input (kg)',  data: rows.map(r => r.input_kg),  type: 'bar' },
                { name: 'Output (kg)', data: rows.map(r => r.output_kg), type: 'bar' },
            ],
            chart: { type: 'bar', height: 350, toolbar: { show: true }, stacked: false, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: true, columnWidth: '50%' } },
            dataLabels: { enabled: false },
            xaxis: { categories: rows.map(r => r.category), labels: { style: { fontSize: '11px' } } },
            yaxis: { title: { text: 'Quantity (kg)' }, labels: { formatter: v => Number(v).toFixed(0) } },
            colors: ['#F1416C', '#50CD89'],
            tooltip: { shared: true, intersect: false, y: { formatter: v => Number(v).toFixed(1) + ' kg' } },
            legend: { position: 'bottom', horizontalAlign: 'center' },
        };

        if (categoryChart) categoryChart.updateOptions(options, false, true);
        else { categoryChart = new ApexCharts(el, options); categoryChart.render(); }
    }

    function renderTopProducts(rows) {
        const card = $('ioTopCard');
        const body = $('ioTopBody');
        if (!body) return;
        if (!rows || rows.length === 0) { card?.classList.add('d-none'); return; }
        card?.classList.remove('d-none');

        body.innerHTML = rows.map((r, i) => {
            const typeColor = r.type === 'net_producer' ? 'success'
                            : r.type === 'net_consumer' ? 'danger' : 'secondary';
            const typeIcon = r.type === 'net_producer' ? 'arrow-up'
                           : r.type === 'net_consumer' ? 'arrow-down' : 'minus';
            const typeLabel = r.type === 'net_producer' ? 'Net Producer'
                            : r.type === 'net_consumer' ? 'Net Consumer' : 'Neutral';
            return `
                <tr>
                    <td>${i + 1}</td>
                    <td>
                        <div class="fw-bold">${esc(r.variant_name)}</div>
                        <div class="text-muted fs-8">${esc(r.variant_sku || '')}</div>
                    </td>
                    <td><span class="badge badge-light-primary">${esc(r.category)}</span></td>
                    <td class="text-center text-danger">${fmtNum(r.input_kg, 0)}</td>
                    <td class="text-center text-success">${fmtNum(r.output_kg, 0)}</td>
                    <td class="text-center fw-bold text-${r.qty_difference >= 0 ? 'success' : 'danger'}">
                        ${r.qty_difference >= 0 ? '+' : ''}${fmtNum(r.qty_difference, 0)}
                    </td>
                    <td class="text-center text-${r.cost_difference >= 0 ? 'success' : 'danger'}">
                        ${money(r.cost_difference)}
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-${typeColor}">
                            <i class="ki-duotone ki-${typeIcon} fs-2 me-1"></i>${typeLabel}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderOrders(rows, pagination) {
        const body = $('ioOrderBody');
        const empty = $('ioOrderEmpty');
        const wrap = $('ioPaginationWrap');
        const badge = $('ioOrderBadge');
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
            const yColor = r.qty_efficiency >= 90 ? 'success' : r.qty_efficiency >= 70 ? 'warning' : 'danger';
            const cColor = r.cost_efficiency >= 120 ? 'success' : r.cost_efficiency >= 100 ? 'warning' : 'danger';
            return `
                <tr>
                    <td>${idx}</td>
                    <td>
                        <div class="fw-bold">${esc(r.production_number)}</div>
                        <div class="text-muted fs-8">${esc(r.created_at)}</div>
                    </td>
                    <td><span class="badge badge-light-${esc(r.status_badge)}">${esc(r.status)}</span></td>
                    <td>${esc(r.location)}</td>
                    <td class="text-center text-danger">${fmtNum(r.input_quantity, 0)}</td>
                    <td class="text-center text-success">${fmtNum(r.output_quantity, 0)}</td>
                    <td class="text-center text-${r.loss_kg > 0 ? 'danger' : 'muted'}">${fmtNum(r.loss_kg, 0)}</td>
                    <td class="text-center"><span class="badge badge-light-${yColor}">${fmtNum(r.qty_efficiency, 1)}%</span></td>
                    <td class="text-center">${money(r.input_cost)}</td>
                    <td class="text-center">${money(r.output_cost)}</td>
                    <td class="text-center text-${r.cost_difference >= 0 ? 'success' : 'danger'}">${money(r.cost_difference)}</td>
                    <td class="text-center"><span class="badge badge-light-${cColor}">${fmtNum(r.cost_efficiency, 1)}%</span></td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('ioPaginationInfo');
        const ul   = $('ioPagination');
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
        if (state.startDate)      params.set('start_date',      state.startDate);
        if (state.endDate)        params.set('end_date',        state.endDate);
        if (state.locationId)     params.set('location_id',     state.locationId);
        if (state.variantId)      params.set('variant_id',      state.variantId);
        if (state.status)         params.set('status',          state.status);
        if (state.comparisonType) params.set('comparison_type', state.comparisonType);
        window.location.href = `${window.IO_ROUTES.export}?${params.toString()}`;
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