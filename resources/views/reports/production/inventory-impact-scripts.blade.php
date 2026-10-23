<script>
window.InventoryImpact = (function () {
    'use strict';

    const ROUTE    = window.IMP_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.IMP_BOOTSTRAP || {};

    const state = {
        startDate:  BOOT.startDate  || '',
        endDate:    BOOT.endDate    || '',
        locationId: BOOT.locationId || '',
        variantId:  BOOT.variantId  || '',
        status:     BOOT.status     || 'completed',
        perPage:    Number(BOOT.perPage || 15),
        page:       1,
    };

    let monthlyChart  = null;
    let categoryChart = null;
    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('impApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('impClearBtn')?.addEventListener('click', resetFilters);
        $('exportImpactBtn')?.addEventListener('click', exportCsv);
        load();
    });

    function readFilters() {
        state.startDate  = $('impStartDate')?.value || '';
        state.endDate    = $('impEndDate')?.value   || '';
        state.locationId = $('impLocation')?.value  || '';
        state.variantId  = $('impVariant')?.value   || '';
        state.status     = $('impStatus')?.value    || 'completed';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.variantId = '';
        state.status = 'completed';
        state.page = 1;
        ['impStartDate','impEndDate','impLocation','impVariant'].forEach(id => { if ($(id)) $(id).value = ''; });
        if ($('impStatus')) $('impStatus').value = 'completed';
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
            console.error('[inventory-impact] load failed:', err);
            window.toastr?.error('Failed to load inventory impact.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('impSkeleton')?.classList.toggle('d-none', !v);
        $('impContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.impactSummary);
        renderMonthly(p.monthlyImpact);
        renderCategory(p.categoryImpact);
        renderTopConsumed(p.topConsumed);
        renderTopProduced(p.topProduced);
        renderNetImpact(p.netImpactSorted);
        renderOrders(p.impactByOrder, p.pagination);
    }

    function renderSummary(s) {
        const netKg = Number(s.net_inventory_change);
        const kgCls = netKg > 0 ? 'success' : netKg < 0 ? 'danger' : 'secondary';
        $('kpiNetKg').textContent = (netKg >= 0 ? '+' : '') + fmtNum(netKg, 0) + ' kg';
        $('kpiNetKg').className = `fs-2 fw-bold text-${kgCls}`;
        $('kpiNetKgSub').textContent = `Out ${fmtNum(s.total_output_quantity, 0)} kg · In ${fmtNum(s.total_input_quantity, 0)} kg`;
        applyColor('kpiNetKgCard', 'kpiNetKgIcon', kgCls);

        const netVal = Number(s.net_value_change);
        const valCls = netVal > 0 ? 'success' : netVal < 0 ? 'danger' : 'secondary';
        $('kpiNetValue').textContent = money(netVal);
        $('kpiNetValue').className = `fs-2 fw-bold text-${valCls}`;
        $('kpiNetValueSub').textContent =
            `Revenue ${money(s.total_output_value)} · Batch ${money(s.total_input_value)}`;
        applyColor('kpiNetValueCard', 'kpiNetValueIcon', valCls);

        const yldCls = s.overall_yield >= 90 ? 'success' : s.overall_yield >= 70 ? 'warning' : 'danger';
        $('kpiYield').textContent = fmtNum(s.overall_yield, 1) + '%';
        $('kpiYield').className = `fs-2 fw-bold text-${yldCls}`;
        $('kpiYieldLoss').textContent = `Loss: ${fmtNum(s.loss_kg, 0)} kg (${fmtNum(s.loss_rate, 1)}%)`;
        applyColor('kpiYieldCard', 'kpiYieldIcon', yldCls);

        $('kpiTurnover').textContent = fmtNum(s.inventory_turnover, 2);
        $('kpiTurnoverSub').textContent = `${s.total_orders} orders`;

        $('kpiDefective').textContent = fmtNum(s.total_defective, 0) + ' kg';
        $('kpiWaste').textContent = fmtNum(s.total_waste, 0) + ' kg';
        $('kpiCompletion').textContent = `${s.completed_orders} / ${s.total_orders}`;
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

    function renderMonthly(rows) {
        const el = $('monthlyImpactChart');
        const empty = $('monthlyImpactEmpty');
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
                { name: 'Net (kg)',    data: rows.map(r => r.net_quantity),    type: 'line' },
            ],
            chart: { type: 'bar', height: 350, toolbar: { show: true }, stacked: false, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: false, columnWidth: '35%' } },
            stroke: { width: [0, 0, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: { categories: rows.map(r => r.month),
                     labels: { rotate: -45, trim: true, style: { fontSize: '11px' } } },
            yaxis: [
                { title: { text: 'Quantity (kg)' }, labels: { formatter: v => Number(v).toFixed(0) } },
                { opposite: true, title: { text: 'Net (kg)' }, labels: { formatter: v => Number(v).toFixed(0) } },
            ],
            colors: ['#F1416C', '#50CD89', '#3E97FF'],
            tooltip: { shared: true, intersect: false,
                       y: { formatter: v => Number(v).toFixed(1) + ' kg' } },
        };

        if (monthlyChart) monthlyChart.updateOptions(options, false, true);
        else { monthlyChart = new ApexCharts(el, options); monthlyChart.render(); }
    }

    function renderCategory(rows) {
        const el = $('categoryImpactChart');
        const empty = $('categoryImpactEmpty');
        if (!el) return;

        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (categoryChart) { categoryChart.destroy(); categoryChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [
                { name: 'Consumed (kg)', data: rows.map(r => r.consumed_quantity), type: 'bar' },
                { name: 'Produced (kg)', data: rows.map(r => r.produced_quantity), type: 'bar' },
            ],
            chart: { type: 'bar', height: 350, toolbar: { show: true }, stacked: false, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: true, columnWidth: '50%' } },
            dataLabels: { enabled: false },
            xaxis: { categories: rows.map(r => r.category),
                     labels: { style: { fontSize: '11px' } } },
            yaxis: { title: { text: 'Quantity (kg)' },
                     labels: { formatter: v => Number(v).toFixed(0) } },
            colors: ['#F1416C', '#50CD89'],
            tooltip: { shared: true, intersect: false,
                       y: { formatter: v => Number(v).toFixed(1) + ' kg' } },
            legend: { position: 'bottom', horizontalAlign: 'center' },
        };

        if (categoryChart) categoryChart.updateOptions(options, false, true);
        else { categoryChart = new ApexCharts(el, options); categoryChart.render(); }
    }

    function renderTopConsumed(rows) {
        const body = $('consumedBody');
        const empty = $('consumedEmpty');
        if (!body) return;
        if (!rows || rows.length === 0) { body.innerHTML = ''; empty?.classList.remove('d-none'); return; }
        empty?.classList.add('d-none');

        body.innerHTML = rows.map((r, i) => `
            <tr>
                <td>${i + 1}</td>
                <td>
                    <div class="fw-bold">${esc(r.variant_name)}</div>
                    <div class="text-muted fs-8">${esc(r.variant_sku || '')}${r.weight > 0 ? ' · ' + fmtNum(r.weight, 2) + ' kg' : ''}</div>
                </td>
                <td class="text-center">${fmtNum(r.total_quantity, 0)}</td>
                <td class="text-center fw-bold text-danger">${fmtNum(r.total_kg, 0)}</td>
                <td class="text-center">${money(r.total_cost)}</td>
                <td class="text-center">${r.order_count}</td>
            </tr>
        `).join('');
    }

    function renderTopProduced(rows) {
        const body = $('producedBody');
        const empty = $('producedEmpty');
        if (!body) return;
        if (!rows || rows.length === 0) { body.innerHTML = ''; empty?.classList.remove('d-none'); return; }
        empty?.classList.add('d-none');

        body.innerHTML = rows.map((r, i) => `
            <tr>
                <td>${i + 1}</td>
                <td>
                    <div class="fw-bold">${esc(r.variant_name)}</div>
                    <div class="text-muted fs-8">${esc(r.variant_sku || '')}${r.weight > 0 ? ' · ' + fmtNum(r.weight, 2) + ' kg' : ''}</div>
                </td>
                <td class="text-center">${fmtNum(r.total_quantity, 0)}</td>
                <td class="text-center fw-bold text-success">${fmtNum(r.total_kg, 0)}</td>
                <td class="text-center">${money(r.total_revenue)}</td>
                <td class="text-center">${r.order_count}</td>
            </tr>
        `).join('');
    }

    function renderNetImpact(rows) {
        const card = $('netImpactCard');
        const body = $('netImpactBody');
        if (!body) return;
        if (!rows || rows.length === 0) { card?.classList.add('d-none'); return; }
        card?.classList.remove('d-none');

        body.innerHTML = rows.map((r, i) => {
            const netCls = r.net_quantity > 0 ? 'success' : r.net_quantity < 0 ? 'danger' : 'secondary';
            const typeLabel = r.impact_type === 'net_producer'
                ? `<i class="ki-duotone ki-arrow-up fs-2 me-1"></i>Net Producer`
                : r.impact_type === 'net_consumer'
                    ? `<i class="ki-duotone ki-arrow-down fs-2 me-1"></i>Net Consumer`
                    : 'Neutral';
            return `
                <tr>
                    <td>${i + 1}</td>
                    <td>
                        <div class="fw-bold">${esc(r.variant_name)}</div>
                        <div class="text-muted fs-8">${esc(r.variant_sku || '')}</div>
                    </td>
                    <td><span class="badge badge-light-primary">${esc(r.category)}</span></td>
                    <td class="text-center text-danger">${fmtNum(r.consumed_quantity, 0)}</td>
                    <td class="text-center text-success">${fmtNum(r.produced_quantity, 0)}</td>
                    <td class="text-center fw-bold text-${netCls}">
                        ${r.net_quantity >= 0 ? '+' : ''}${fmtNum(r.net_quantity, 0)}
                    </td>
                    <td class="text-center text-${r.net_value >= 0 ? 'success' : 'danger'}">${money(r.net_value)}</td>
                    <td class="text-center">
                        <span class="badge badge-light-${r.impact_color}">${typeLabel}</span>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderOrders(rows, pagination) {
        const body = $('impactOrderBody');
        const empty = $('impactOrderEmpty');
        const wrap = $('impPaginationWrap');
        const badge = $('impactOrderBadge');
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
            const netCls = r.net_quantity >= 0 ? 'success' : 'danger';
            const valCls = r.net_value >= 0 ? 'success' : 'danger';
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
                    <td class="text-center fw-bold text-${netCls}">
                        ${r.net_quantity >= 0 ? '+' : ''}${fmtNum(r.net_quantity, 0)}
                    </td>
                    <td class="text-center">${money(r.input_value)}</td>
                    <td class="text-center">${money(r.output_value)}</td>
                    <td class="text-center text-${valCls}">${money(r.net_value)}</td>
                    <td class="text-center text-danger">${fmtNum(r.defective, 0)}</td>
                    <td class="text-center text-warning">${fmtNum(r.waste, 0)}</td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('impPaginationInfo');
        const ul   = $('impPagination');
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
        window.location.href = `${window.IMP_ROUTES.export}?${params.toString()}`;
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