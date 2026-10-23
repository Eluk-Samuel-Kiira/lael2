<script>
window.InventoryValuation = (function () {
    'use strict';

    const ROUTE    = window.VAL_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.VAL_BOOTSTRAP || {};

    const state = {
        departmentId:    BOOT.departmentId    || '',
        locationId:      BOOT.locationId      || '',
        variantId:       BOOT.variantId       || '',
        valuationMethod: BOOT.valuationMethod || 'cost',
        perPage:         Number(BOOT.perPage || 15),
        page:            1,
    };

    let deptChart = null;
    let locChart  = null;
    let inFlight  = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('valApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('valClearBtn')?.addEventListener('click', resetFilters);
        $('valExportBtn')?.addEventListener('click', exportCsv);
        load();
    });

    function readFilters() {
        state.valuationMethod = $('valMethod')?.value     || 'cost';
        state.variantId       = $('valVariant')?.value    || '';
        state.locationId      = $('valLocation')?.value   || '';
        state.departmentId    = $('valDepartment')?.value || '';
    }

    function resetFilters() {
        state.variantId = state.locationId = state.departmentId = '';
        state.valuationMethod = 'cost';
        state.page = 1;
        if ($('valMethod')) $('valMethod').value = 'cost';
        if (window.jQuery) jQuery('#valVariant, #valLocation, #valDepartment').val(null).trigger('change.select2');
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.valuationMethod) params.set('valuation_method', state.valuationMethod);
        if (state.variantId)       params.set('variant_id',       state.variantId);
        if (state.locationId)      params.set('location_id',      state.locationId);
        if (state.departmentId)    params.set('department_id',    state.departmentId);

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
            console.error('[valuation] load failed:', err);
            window.toastr?.error('Failed to load valuation.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('valSkeleton')?.classList.toggle('d-none', !v);
        $('valContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderDeptChart(p.by_dept);
        renderLocChart(p.by_loc);
        renderTable(p.items, p.pagination);
    }

    function renderSummary(s) {
        $('sumTotalItems').textContent      = fmtNum(s.total_items, 0);
        $('sumTotalQty').textContent        = fmtNum(s.total_quantity, 0);
        $('sumTotalValue').textContent      = money(s.total_value);
        $('sumPotentialProfit').textContent = money(s.potential_profit);
    }

    function renderDeptChart(rows) {
        const el = $('valDeptChart');
        const empty = $('valDeptEmpty');
        if (!el) return;
        const filtered = (rows || []).filter(r => Number(r.value) > 0);
        if (filtered.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (deptChart) { deptChart.destroy(); deptChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: filtered.map(r => Number(r.value)),
            chart:  { type: 'pie', height: 320, fontFamily: 'inherit' },
            labels: filtered.map(r => r.name),
            colors: ['#3E97FF', '#50CD89', '#FFC700', '#F1416C', '#7239EA', '#7E8299'],
            legend: { position: 'bottom', fontSize: '11px' },
            tooltip: { y: { formatter: v => money(v) } },
        };

        if (deptChart) deptChart.updateOptions(options, false, true);
        else { deptChart = new ApexCharts(el, options); deptChart.render(); }
    }

    function renderLocChart(rows) {
        const el = $('valLocChart');
        const empty = $('valLocEmpty');
        if (!el) return;
        const filtered = (rows || []).filter(r => Number(r.value) > 0);
        if (filtered.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (locChart) { locChart.destroy(); locChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [{ name: 'Value', data: filtered.map(r => Number(r.value)) }],
            chart:  { type: 'bar', height: 320, toolbar: { show: true }, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: true, barHeight: '50%', borderRadius: 4 } },
            dataLabels: {
                enabled: true,
                formatter: v => CURRENCY + Number(v).toFixed(0),
                style: { fontSize: '10px' },
            },
            xaxis: { categories: filtered.map(r => r.name), labels: { style: { fontSize: '11px' } } },
            yaxis: { title: { text: 'Location' } },
            colors: ['#50CD89'],
            tooltip: { y: { formatter: v => money(v) } },
        };

        if (locChart) locChart.updateOptions(options, false, true);
        else { locChart = new ApexCharts(el, options); locChart.render(); }
    }

    function renderTable(rows, pagination) {
        const body = $('valTableBody');
        const empty = $('valEmpty');
        const wrap = $('valPaginationWrap');
        const badge = $('valBadge');
        const footer = $('valTableFooter');
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
            const margin = Number(r.profit_margin);
            const marginColor = margin < 0 ? 'danger' : margin < 20 ? 'warning' : 'success';
            const imgHtml = r.image_url
                ? `<div class="symbol symbol-40px me-2"><img src="${esc(r.image_url)}" class="rounded" alt="${esc(r.variant_name)}"></div>`
                : '';
            const progress = Math.min(100, Math.max(0, margin));

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
                                ${r.product_name ? `<div class="text-muted fs-7">${esc(r.product_name)}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-light-primary">${esc(r.department_name)}</span></td>
                    <td><span class="badge badge-light-info">${esc(r.location_name)}</span></td>
                    <td class="text-center"><span class="fw-bold">${fmtNum(r.quantity_allocated, 0)}</span></td>
                    <td class="text-end"><span class="fw-bold text-gray-700">${money(r.cost_price)}</span></td>
                    <td class="text-end"><span class="fw-bold text-gray-700">${money(r.selling_price)}</span></td>
                    <td class="text-end"><span class="fw-bold text-success">${money(r.valuation_value)}</span></td>
                    <td class="text-end"><span class="fw-bold text-primary">${money(r.potential_profit)}</span></td>
                    <td class="text-center">
                        <span class="badge badge-light-${marginColor}">${fmtNum(margin, 1)}%</span>
                        <div class="progress mt-1 mx-auto" style="height:4px; width:50px;">
                            <div class="progress-bar bg-${marginColor}" style="width:${progress}%"></div>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        const pageValue = rows.reduce((s, r) => s + Number(r.valuation_value || 0), 0);
        $('ftPageValue').textContent = money(pageValue);

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('valPaginationInfo');
        const ul   = $('valPagination');
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
        if (state.valuationMethod) params.set('valuation_method', state.valuationMethod);
        if (state.variantId)       params.set('variant_id',       state.variantId);
        if (state.locationId)      params.set('location_id',      state.locationId);
        if (state.departmentId)    params.set('department_id',    state.departmentId);
        window.location.href = `/reports/inventory/valuation?${params.toString()}&export=1`;
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