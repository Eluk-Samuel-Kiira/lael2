<script>
window.InventorySummary = (function () {
    'use strict';

    const ROUTE    = window.INV_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.INV_BOOTSTRAP || {};

    const state = {
        startDate:    BOOT.startDate    || '',
        endDate:      BOOT.endDate      || '',
        locationId:   BOOT.locationId   || '',
        departmentId: BOOT.departmentId || '',
        variantId:    BOOT.variantId    || '',
        isActive:     BOOT.isActive     || 'all',
        perPage:      Number(BOOT.perPage || 15),
        page:         1,
    };

    let stockChart = null;
    let deptChart  = null;
    let inFlight   = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('invApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        load();
    });

    function readFilters() {
        state.startDate    = $('invStartDate')?.value   || '';
        state.endDate      = $('invEndDate')?.value     || '';
        state.locationId   = $('invLocation')?.value    || '';
        state.departmentId = $('invDepartment')?.value  || '';
        state.variantId    = $('invVariant')?.value     || '';
        state.isActive     = $('invStatus')?.value      || 'all';
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)    params.set('start_date',    state.startDate);
        if (state.endDate)      params.set('end_date',      state.endDate);
        if (state.locationId)   params.set('location_id',   state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);
        if (state.variantId)    params.set('variant_id',    state.variantId);
        if (state.isActive)     params.set('is_active',     state.isActive);

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
            console.error('[inv-summary] load failed:', err);
            window.toastr?.error('Failed to load inventory summary.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('invSkeleton')?.classList.toggle('d-none', !v);
        $('invContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderStockChart(p.stock_dist);
        renderDeptChart(p.dept_dist);
        renderTable(p.items, p.pagination, p.summary);
    }

    function renderSummary(s) {
        $('sumTotalItems').textContent = fmtNum(s.total_items, 0);
        $('sumTotalQty').textContent   = fmtNum(s.total_quantity_allocated, 0);
        $('sumTotalValue').textContent = money(s.total_value);
        $('sumAvgStock').textContent   = fmtNum(s.average_stock_level, 1);
        $('sumBelowReorder').textContent = s.items_below_reorder;
        $('sumOutOfStock').textContent   = s.out_of_stock;
    }

    function renderStockChart(rows) {
        const el = $('stockChart');
        const empty = $('stockChartEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (stockChart) { stockChart.destroy(); stockChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [{ name: 'Stock', data: rows.map(r => r.value) }],
            chart:  { type: 'bar', height: 350, toolbar: { show: true }, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: false, columnWidth: '60%' } },
            dataLabels: { enabled: false },
            xaxis: { categories: rows.map(r => r.name), labels: { rotate: -45, trim: true, style: { fontSize: '11px' } } },
            yaxis: { title: { text: 'Quantity' }, labels: { formatter: v => Number(v).toFixed(0) } },
            colors: ['#3E97FF'],
            tooltip: { y: { formatter: v => Number(v).toFixed(0) } },
        };

        if (stockChart) stockChart.updateOptions(options, false, true);
        else { stockChart = new ApexCharts(el, options); stockChart.render(); }
    }

    function renderDeptChart(rows) {
        const el = $('deptChart');
        const empty = $('deptChartEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (deptChart) { deptChart.destroy(); deptChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: rows.map(r => r.value),
            chart:  { type: 'donut', height: 350, fontFamily: 'inherit' },
            labels: rows.map(r => r.name),
            colors: ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C', '#A1A5B7'],
            legend: { position: 'bottom', fontSize: '12px' },
            tooltip: { y: { formatter: v => v + ' items' } },
        };

        if (deptChart) deptChart.updateOptions(options, false, true);
        else { deptChart = new ApexCharts(el, options); deptChart.render(); }
    }

    function renderTable(rows, pagination, summary) {
        const body = $('invTableBody');
        const empty = $('invEmpty');
        const wrap = $('invPaginationWrap');
        const badge = $('invBadge');
        const footer = $('invTableFooter');
        if (!body) return;

        if (!rows || rows.length === 0) {
            body.innerHTML = ''; empty?.classList.remove('d-none'); wrap?.classList.add('d-none');
            footer?.classList.add('d-none');
            if (badge) badge.textContent = '';
            return;
        }
        empty?.classList.add('d-none');
        wrap?.classList.remove('d-none');
        footer?.classList.remove('d-none');
        if (badge) badge.textContent = `Showing ${pagination.from} of ${pagination.total}`;

        body.innerHTML = rows.map(r => {
            const ratio = r.stock_ratio;
            const progressHtml = (ratio !== null && ratio > 0 && r.quantity_on_hand > 0)
                ? `<div class="progress mt-1" style="height:5px; width:80px; margin:0 auto;">
                      <div class="progress-bar bg-${r.status_color}" style="width:${(ratio * 100).toFixed(0)}%"></div>
                   </div>`
                : '';

            const imgHtml = r.image_url
                ? `<div class="symbol symbol-50px me-3"><img src="${esc(r.image_url)}" class="img-fluid rounded" alt="${esc(r.variant_name)}"></div>`
                : '';

            const productLine = r.product_name
                ? `<div class="text-muted fs-7">${esc(r.product_name)}</div>`
                : '';

            return `
                <tr>
                    <td class="ps-4">
                        <div class="fw-semibold">${esc(r.sku)}</div>
                        ${r.barcode ? `<small class="text-muted">${esc(r.barcode)}</small>` : ''}
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${imgHtml}
                            <div>
                                <div class="fw-bold">${esc(r.variant_name)}</div>
                                ${productLine}
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-light-primary">${esc(r.department_name)}</span></td>
                    <td><span class="badge badge-light-info">${esc(r.location_name)}</span></td>
                    <td class="text-center">
                        <span class="fw-bold ${r.quantity_on_hand == 0 ? 'text-danger' : 'text-success'}">
                            ${fmtNum(r.quantity_on_hand, 0)}
                        </span>
                    </td>
                    <td class="text-center"><span class="text-muted">${fmtNum(r.quantity_allocated, 0)}</span></td>
                    <td class="text-center"><span class="badge badge-light-secondary">${fmtNum(r.preferred_stock, 0)}</span></td>
                    <td class="text-center"><span class="badge badge-light-warning">${fmtNum(r.reorder_point, 0)}</span></td>
                    <td class="text-center">
                        <span class="badge badge-light-${r.status_color}">${esc(r.status_text)}</span>
                        ${progressHtml}
                    </td>
                    <td>${esc(r.last_updated)}</td>
                </tr>
            `;
        }).join('');

        // Footer totals for this page
        $('ftQty').textContent       = fmtNum(rows.reduce((s, r) => s + r.quantity_on_hand, 0), 0);
        $('ftAllocated').textContent = fmtNum(rows.reduce((s, r) => s + r.quantity_allocated, 0), 0);
        $('ftPreferred').textContent = fmtNum(rows.reduce((s, r) => s + r.preferred_stock, 0), 0);
        $('ftReorder').textContent   = fmtNum(rows.reduce((s, r) => s + r.reorder_point, 0), 0);

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('invPaginationInfo');
        const ul   = $('invPagination');
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