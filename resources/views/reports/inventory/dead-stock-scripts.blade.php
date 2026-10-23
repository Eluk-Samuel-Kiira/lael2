<script>
window.DeadStock = (function () {
    'use strict';

    const ROUTE    = window.DS_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.DS_BOOTSTRAP || {};

    const state = {
        departmentId:   BOOT.departmentId   || '',
        locationId:     BOOT.locationId     || '',
        variantId:      BOOT.variantId      || '',
        daysThreshold:  Number(BOOT.daysThreshold || 180),
        includeExpired: Boolean(BOOT.includeExpired),
        perPage:        Number(BOOT.perPage || 15),
        page:           1,
    };

    let idleChart = null;
    let deptChart = null;
    let daysChart = null;
    let inFlight  = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('dsApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('dsClearBtn')?.addEventListener('click', resetFilters);
        $('dsExportBtn')?.addEventListener('click', exportCsv);
        load();
    });

    function readFilters() {
        state.daysThreshold  = Number($('dsDaysThreshold')?.value || 180);
        state.variantId      = $('dsVariant')?.value    || '';
        state.locationId     = $('dsLocation')?.value   || '';
        state.departmentId   = $('dsDepartment')?.value || '';
        state.includeExpired = Boolean($('dsIncludeExpired')?.checked);
    }

    function resetFilters() {
        state.variantId = state.locationId = state.departmentId = '';
        state.daysThreshold = 180;
        state.includeExpired = false;
        state.page = 1;
        if ($('dsDaysThreshold'))  $('dsDaysThreshold').value = 180;
        if ($('dsIncludeExpired')) $('dsIncludeExpired').checked = false;
        if (window.jQuery) jQuery('#dsVariant, #dsLocation, #dsDepartment').val(null).trigger('change.select2');
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({
            page: state.page,
            per_page: state.perPage,
            days_threshold: state.daysThreshold,
        });
        if (state.variantId)      params.set('variant_id',      state.variantId);
        if (state.locationId)     params.set('location_id',     state.locationId);
        if (state.departmentId)   params.set('department_id',   state.departmentId);
        if (state.includeExpired) params.set('include_expired', '1');

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
            console.error('[dead-stock] load failed:', err);
            window.toastr?.error('Failed to load dead stock.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('dsSkeleton')?.classList.toggle('d-none', !v);
        $('dsContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderIdleChart(p.idle);
        renderDeptChart(p.by_dept);
        renderDaysChart(p.items);
        renderTable(p.items, p.pagination);
    }

    function renderSummary(s) {
        $('sumItems').textContent   = fmtNum(s.total_items, 0);
        $('sumQty').textContent     = fmtNum(s.total_quantity, 0);
        $('sumValue').textContent   = money(s.total_value);
        $('sumAvgDays').textContent = fmtNum(s.avg_days_idle, 0);
        $('sumExpired').textContent = fmtNum(s.expired_items, 0);
    }

    function renderIdleChart(idle) {
        const el = $('dsIdleChart');
        const empty = $('dsIdleEmpty');
        if (!el) return;

        const rows = [
            { name: '180–365 days', value: Number(idle['180_365']  || 0), color: '#FFC700' },
            { name: '365–730 days', value: Number(idle['365_730']  || 0), color: '#F1416C' },
            { name: 'Over 730 days',value: Number(idle['over_730'] || 0), color: '#7E8299' },
        ].filter(r => r.value > 0);

        if (rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (idleChart) { idleChart.destroy(); idleChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const total = rows.reduce((s, r) => s + r.value, 0);
        const options = {
            series: rows.map(r => r.value),
            chart:  { type: 'donut', height: 320, fontFamily: 'inherit' },
            labels: rows.map(r => r.name),
            colors: rows.map(r => r.color),
            legend: { position: 'bottom', fontSize: '12px' },
            plotOptions: { pie: { donut: {
                size: '60%',
                labels: {
                    show: true,
                    total: {
                        show: true, label: 'Total units',
                        formatter: () => total.toLocaleString(),
                    },
                },
            }}},
            tooltip: { y: { formatter: v => v.toLocaleString() + ' units' } },
        };

        if (idleChart) idleChart.updateOptions(options, false, true);
        else { idleChart = new ApexCharts(el, options); idleChart.render(); }
    }

    function renderDeptChart(rows) {
        const el = $('dsDeptChart');
        const empty = $('dsDeptEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (deptChart) { deptChart.destroy(); deptChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [{ name: 'Dead Qty', data: rows.map(r => r.quantity) }],
            chart:  { type: 'bar', height: 320, toolbar: { show: true }, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: true, barHeight: '50%', borderRadius: 4 } },
            dataLabels: { enabled: true, formatter: v => Number(v).toLocaleString(), style: { fontSize: '10px' } },
            xaxis: { categories: rows.map(r => r.name), labels: { style: { fontSize: '11px' } } },
            yaxis: { title: { text: 'Department' } },
            colors: ['#F1416C'],
            tooltip: { y: { formatter: v => Number(v).toLocaleString() + ' units' } },
        };

        if (deptChart) deptChart.updateOptions(options, false, true);
        else { deptChart = new ApexCharts(el, options); deptChart.render(); }
    }

    function renderDaysChart(items) {
        const card = $('dsIdleAnalysisCard');
        const el = $('dsDaysChart');
        const empty = $('dsDaysEmpty');
        if (!el) return;
        const top = (items || []).slice(0, 15);
        if (top.length === 0) {
            card?.classList.add('d-none');
            if (daysChart) { daysChart.destroy(); daysChart = null; }
            return;
        }
        card?.classList.remove('d-none');
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [{ name: 'Days Idle', data: top.map(r => Number(r.days_idle)) }],
            chart:  { type: 'bar', height: 320, toolbar: { show: true }, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: true, barHeight: '70%', borderRadius: 4 } },
            dataLabels: { enabled: true, formatter: v => Number(v).toFixed(0) + ' d', style: { fontSize: '10px' } },
            xaxis: { categories: top.map(r => mbStr(r.variant_name)), labels: { style: { fontSize: '11px' } } },
            yaxis: { title: { text: 'Product' } },
            colors: ['#7239EA'],
            tooltip: { y: { formatter: v => Number(v).toFixed(0) + ' days idle' } },
        };

        if (daysChart) daysChart.updateOptions(options, false, true);
        else { daysChart = new ApexCharts(el, options); daysChart.render(); }
    }

    function renderTable(rows, pagination) {
        const body = $('dsTableBody');
        const empty = $('dsEmpty');
        const wrap = $('dsPaginationWrap');
        const badge = $('dsBadge');
        const footer = $('dsTableFooter');
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
            const days = Number(r.days_idle);
            const idleColor = days >= 730 ? 'danger' : days >= 365 ? 'warning' : days >= 180 ? 'info' : 'success';
            const idleText  = days >= 730 ? 'Critical' : days >= 365 ? 'High' : days >= 180 ? 'Medium' : 'Low';
            const expired   = r.is_expired;
            const imgHtml = r.image_url
                ? `<div class="symbol symbol-40px me-2"><img src="${esc(r.image_url)}" class="rounded" alt="${esc(r.variant_name)}"></div>`
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
                                ${r.product_name ? `<div class="text-muted fs-7">${esc(r.product_name)}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-light-primary">${esc(r.department_name)}</span></td>
                    <td><span class="badge badge-light-info">${esc(r.location_name)}</span></td>
                    <td class="text-center"><span class="fw-bold ${expired ? 'text-danger' : 'text-dark'}">${fmtNum(r.quantity_on_hand, 0)}</span></td>
                    <td><span class="text-muted fs-7">${esc(r.last_movement_date || '-')}</span></td>
                    <td class="text-center">
                        <span class="badge badge-light-${idleColor}">
                            ${days} days
                            <small class="d-block">${idleText}</small>
                        </span>
                    </td>
                    <td>
                        ${expired
                            ? `<span class="badge badge-light-danger">Expired</span>`
                            : r.expiry_date
                                ? `<span class="badge badge-light-success">${esc(r.expiry_date)}</span>`
                                : '<span class="text-muted">-</span>'}
                    </td>
                    <td class="text-end">
                        <span class="fw-bold ${expired ? 'text-danger' : 'text-dark'}">${money(r.inventory_value)}</span>
                    </td>
                    <td class="text-center"><span class="badge badge-light-dark">${fmtNum(r.total_movement, 0)}</span></td>
                </tr>
            `;
        }).join('');

        const pageValue = rows.reduce((s, r) => s + Number(r.inventory_value || 0), 0);
        $('ftPageValue').textContent = money(pageValue);

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('dsPaginationInfo');
        const ul   = $('dsPagination');
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
        params.set('days_threshold', state.daysThreshold);
        if (state.variantId)      params.set('variant_id',      state.variantId);
        if (state.locationId)     params.set('location_id',     state.locationId);
        if (state.departmentId)   params.set('department_id',   state.departmentId);
        if (state.includeExpired) params.set('include_expired', '1');
        window.location.href = `/reports/inventory/dead-stock?${params.toString()}&export=1`;
    }

    function mbStr(s) {
        s = String(s || '');
        return s.length > 25 ? s.slice(0, 22) + '…' : s;
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