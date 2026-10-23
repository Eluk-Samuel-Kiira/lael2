<script>
window.AbсAnalysis = (function () {
    'use strict';

    const ROUTE    = window.ABC_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.ABC_BOOTSTRAP || {};

    const state = {
        startDate:    BOOT.startDate    || '',
        endDate:      BOOT.endDate      || '',
        departmentId: BOOT.departmentId || '',
        locationId:   BOOT.locationId   || '',
        variantId:    BOOT.variantId    || '',
        perPage:      Number(BOOT.perPage || 15),
        page:         1,
    };

    const CAT_HEX = { A: '#F1416C', B: '#FFC700', C: '#50CD89' };
    const CAT_BG  = { A: 'danger', B: 'warning', C: 'success' };
    const CAT_LABEL = { A: 'Category A', B: 'Category B', C: 'Category C' };
    const CAT_REC = {
        A: 'High priority — review frequently',
        B: 'Medium priority — monitor',
        C: 'Low priority — occasional review',
    };

    let valueChart  = null;
    let countChart  = null;
    let paretoChart = null;
    let inFlight    = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('abcApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('abcClearBtn')?.addEventListener('click', resetFilters);
        $('abcExportBtn')?.addEventListener('click', exportCsv);
        load();
    });

    function readFilters() {
        state.startDate    = $('abcStartDate')?.value   || '';
        state.endDate      = $('abcEndDate')?.value     || '';
        state.variantId    = $('abcVariant')?.value     || '';
        state.locationId   = $('abcLocation')?.value    || '';
        state.departmentId = $('abcDepartment')?.value  || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.variantId = state.locationId = state.departmentId = '';
        state.page = 1;
        if ($('abcStartDate')) $('abcStartDate').value = '';
        if ($('abcEndDate'))   $('abcEndDate').value   = '';
        if (window.jQuery) jQuery('#abcVariant, #abcLocation, #abcDepartment').val(null).trigger('change.select2');
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)    params.set('start_date',    state.startDate);
        if (state.endDate)      params.set('end_date',      state.endDate);
        if (state.variantId)    params.set('variant_id',    state.variantId);
        if (state.locationId)   params.set('location_id',   state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);

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
            console.error('[abc-analysis] load failed:', err);
            window.toastr?.error('Failed to load ABC analysis.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('abcSkeleton')?.classList.toggle('d-none', !v);
        $('abcContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderCategoryCards(p.summary);
        renderValueChart(p.value_chart);
        renderCountChart(p.count_chart);
        renderPareto(p.pareto);
        renderTable(p.items, p.pagination);
    }

    function renderCategoryCards(s) {
        const a = s.categories.A;
        const b = s.categories.B;
        const c = s.categories.C;

        $('catAValue').textContent = money(a.value);
        $('catACount').textContent = `${a.count} items · ${fmtNum(a.percentage, 1)}% of total`;
        $('catABar').style.width = a.percentage + '%';

        $('catBValue').textContent = money(b.value);
        $('catBCount').textContent = `${b.count} items · ${fmtNum(b.percentage, 1)}% of total`;
        $('catBBar').style.width = b.percentage + '%';

        $('catCValue').textContent = money(c.value);
        $('catCCount').textContent = `${c.count} items · ${fmtNum(c.percentage, 1)}% of total`;
        $('catCBar').style.width = c.percentage + '%';
    }

    function renderValueChart(rows) {
        const el = $('abcValueChart');
        const empty = $('abcValueEmpty');
        if (!el) return;
        const filtered = (rows || []).filter(r => Number(r.value) > 0);
        if (filtered.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (valueChart) { valueChart.destroy(); valueChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: filtered.map(r => Number(r.value)),
            chart:  { type: 'donut', height: 320, fontFamily: 'inherit' },
            labels: filtered.map(r => `${r.name} (${r.count} items)`),
            colors: filtered.map(r => CAT_HEX[r.name.slice(-1)] || '#A8A8A8'),
            legend: { position: 'bottom', fontSize: '12px' },
            plotOptions: { pie: { donut: {
                size: '60%',
                labels: {
                    show: true,
                    total: {
                        show: true, label: 'Total',
                        formatter: w => CURRENCY + ' ' + w.globals.seriesTotals.reduce((a, b) => a + b, 0).toFixed(0),
                    },
                },
            }}},
            tooltip: { y: { formatter: v => money(v) } },
        };

        if (valueChart) valueChart.updateOptions(options, false, true);
        else { valueChart = new ApexCharts(el, options); valueChart.render(); }
    }

    function renderCountChart(rows) {
        const el = $('abcCountChart');
        const empty = $('abcCountEmpty');
        if (!el) return;
        const filtered = (rows || []).filter(r => Number(r.value) > 0);
        if (filtered.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (countChart) { countChart.destroy(); countChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [{ name: 'Items', data: filtered.map(r => Number(r.value)) }],
            chart:  { type: 'bar', height: 320, toolbar: { show: true }, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: false, columnWidth: '60%', borderRadius: 4 } },
            dataLabels: { enabled: true, formatter: v => v + ' items', style: { fontSize: '11px', fontWeight: 'bold' } },
            xaxis: { categories: filtered.map(r => r.name), labels: { style: { fontSize: '12px' } } },
            yaxis: { title: { text: 'Number of Items' } },
            colors: ['#3E97FF'],
            tooltip: { y: { formatter: v => v + ' items' } },
        };

        if (countChart) countChart.updateOptions(options, false, true);
        else { countChart = new ApexCharts(el, options); countChart.render(); }
    }

    function renderPareto(rows) {
        const card = $('abcParetoCard');
        const el = $('abcParetoChart');
        const empty = $('abcParetoEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            card?.classList.add('d-none');
            if (paretoChart) { paretoChart.destroy(); paretoChart = null; }
            return;
        }
        card?.classList.remove('d-none');
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [
                { name: 'Inventory Value', data: rows.map(r => r.value),   type: 'column' },
                { name: 'Cumulative %',    data: rows.map(r => r.cum_pct), type: 'line'   },
            ],
            chart: { height: 350, type: 'line', toolbar: { show: true }, zoom: { enabled: true }, fontFamily: 'inherit' },
            stroke: { width: [0, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: {
                categories: rows.map(r => r.name),
                labels: { rotate: -45, style: { fontSize: '10px' }, trim: true },
            },
            yaxis: [
                { title: { text: 'Value' }, labels: { formatter: v => CURRENCY + Number(v).toFixed(0) } },
                { opposite: true, title: { text: 'Cumulative %' }, max: 100, labels: { formatter: v => Number(v).toFixed(0) + '%' } },
            ],
            colors: ['#3E97FF', '#F1416C'],
            tooltip: { shared: true, intersect: false,
                       y: { formatter: (v, { seriesIndex }) =>
                           seriesIndex === 0 ? money(v) : Number(v).toFixed(1) + '%' } },
        };

        if (paretoChart) paretoChart.updateOptions(options, false, true);
        else { paretoChart = new ApexCharts(el, options); paretoChart.render(); }
    }

    function renderTable(rows, pagination) {
        const body = $('abcTableBody');
        const empty = $('abcEmpty');
        const wrap = $('abcPaginationWrap');
        const badge = $('abcBadge');
        const footer = $('abcTableFooter');
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
            const color = CAT_BG[r.abc_category] || 'dark';
            const rec   = CAT_REC[r.abc_category] || '';
            const imgHtml = r.image_url
                ? `<div class="symbol symbol-40px me-2"><img src="${esc(r.image_url)}" class="rounded" alt="${esc(r.variant_name)}"></div>`
                : '';
            const cumCls = r.cumulative_percentage <= 80 ? 'success'
                        : r.cumulative_percentage <= 95 ? 'warning' : 'info';

            return `
                <tr>
                    <td class="ps-4"><span class="fw-bold">${r.rank}</span></td>
                    <td class="text-center">
                        <span class="badge badge-light-${color} fs-7 py-2 px-3">${CAT_LABEL[r.abc_category]}</span>
                    </td>
                    <td>
                        <div class="fw-semibold">${esc(r.sku)}</div>
                        ${r.barcode ? `<small class="text-muted">${esc(r.barcode)}</small>` : ''}
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${imgHtml}
                            <div>
                                <div class="fw-bold">${esc(r.variant_name)}</div>
                                ${r.product_name ? `<div class="text-muted fs-7">${esc(r.product_name)}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-light-primary">${esc(r.department_name)}</span></td>
                    <td class="text-end"><span class="fw-bold text-${color}">${money(r.inventory_value)}</span></td>
                    <td class="text-center"><span class="badge badge-light-dark">${fmtNum(r.value_percentage, 1)}%</span></td>
                    <td class="text-center"><span class="fw-bold text-${cumCls}">${fmtNum(r.cumulative_percentage, 1)}%</span></td>
                    <td><span class="text-muted fs-7">${rec}</span></td>
                </tr>
            `;
        }).join('');

        const pageValue = rows.reduce((s, r) => s + Number(r.inventory_value || 0), 0);
        $('ftPageValue').textContent = money(pageValue);

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('abcPaginationInfo');
        const ul   = $('abcPagination');
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
        if (state.startDate)    params.set('start_date',    state.startDate);
        if (state.endDate)      params.set('end_date',      state.endDate);
        if (state.variantId)    params.set('variant_id',    state.variantId);
        if (state.locationId)   params.set('location_id',   state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);
        window.location.href = `/reports/inventory/abc-analysis?${params.toString()}&export=1`;
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