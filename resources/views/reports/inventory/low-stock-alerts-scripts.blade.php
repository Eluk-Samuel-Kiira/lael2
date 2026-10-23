<script>
window.LowStockAlerts = (function () {
    'use strict';

    const ROUTE    = window.LS_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.LS_BOOTSTRAP || {};

    const state = {
        departmentId: BOOT.departmentId || '',
        locationId:   BOOT.locationId   || '',
        variantId:    BOOT.variantId    || '',
        severity:     BOOT.severity     || 'all',
        perPage:      Number(BOOT.perPage || 15),
        page:         1,
    };

    let severityChart = null;
    let deptChart     = null;
    let inFlight      = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('lsApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('lsClearBtn')?.addEventListener('click', resetFilters);
        $('lsExportBtn')?.addEventListener('click', exportCsv);
        load();
    });

    function readFilters() {
        state.departmentId = $('lsDepartment')?.value || '';
        state.locationId   = $('lsLocation')?.value   || '';
        state.variantId    = $('lsVariant')?.value    || '';
        state.severity     = $('lsSeverity')?.value   || 'all';
    }

    function resetFilters() {
        state.departmentId = state.locationId = state.variantId = '';
        state.severity = 'all';
        state.page = 1;
        if ($('lsSeverity')) $('lsSeverity').value = 'all';
        if (window.jQuery) jQuery('#lsLocation, #lsDepartment, #lsVariant').val(null).trigger('change.select2');
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.departmentId) params.set('department_id', state.departmentId);
        if (state.locationId)   params.set('location_id',   state.locationId);
        if (state.variantId)    params.set('variant_id',    state.variantId);
        if (state.severity)     params.set('severity',      state.severity);

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
            console.error('[low-stock] load failed:', err);
            window.toastr?.error('Failed to load low stock alerts.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('lsSkeleton')?.classList.toggle('d-none', !v);
        $('lsContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderSeverityChart(p.severity_dist);
        renderDeptChart(p.dept_dist);
        renderTable(p.items, p.pagination);
    }

    function renderSummary(s) {
        $('lsCritical').textContent = fmtNum(s.critical, 0);
        $('lsWarning').textContent  = fmtNum(s.warning, 0);
        $('lsTotal').textContent    = fmtNum(s.total_items, 0);
        $('lsValueAtRisk').textContent = money(s.total_value_at_risk);
    }

    function renderSeverityChart(rows) {
        const el = $('lsSeverityChart');
        const empty = $('lsSeverityEmpty');
        if (!el) return;
        const filtered = (rows || []).filter(r => Number(r.value) > 0);
        if (filtered.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (severityChart) { severityChart.destroy(); severityChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const colorMap = { critical: '#F1416C', warning: '#FFC700', low: '#3E97FF' };
        const total = filtered.reduce((s, r) => s + Number(r.value), 0);

        const options = {
            series: filtered.map(r => Number(r.value)),
            chart:  { type: 'donut', height: 320, fontFamily: 'inherit' },
            labels: filtered.map(r => r.name),
            colors: filtered.map(r => colorMap[r.color] || '#A1A5B7'),
            legend: { position: 'bottom', fontSize: '12px' },
            plotOptions: { pie: { donut: {
                size: '60%',
                labels: {
                    show: true,
                    total: {
                        show: true, label: 'Total',
                        formatter: () => total,
                    },
                },
            }}},
            tooltip: { y: { formatter: v => v + ' items' } },
        };

        if (severityChart) severityChart.updateOptions(options, false, true);
        else { severityChart = new ApexCharts(el, options); severityChart.render(); }
    }

    function renderDeptChart(rows) {
        const el = $('lsDeptChart');
        const empty = $('lsDeptEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (deptChart) { deptChart.destroy(); deptChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [{ name: 'Low Stock Items', data: rows.map(r => r.value) }],
            chart:  { type: 'bar', height: 320, toolbar: { show: true }, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: true, barHeight: '50%', borderRadius: 4 } },
            dataLabels: { enabled: true, formatter: v => v + ' items', style: { fontSize: '11px' } },
            xaxis: { categories: rows.map(r => r.name), labels: { style: { fontSize: '11px' } } },
            yaxis: { title: { text: 'Department' } },
            colors: ['#3E97FF'],
            tooltip: { y: { formatter: v => v + ' items' } },
        };

        if (deptChart) deptChart.updateOptions(options, false, true);
        else { deptChart = new ApexCharts(el, options); deptChart.render(); }
    }

    function renderTable(rows, pagination) {
        const body = $('lsTableBody');
        const empty = $('lsEmpty');
        const wrap = $('lsPaginationWrap');
        const badge = $('lsBadge');
        if (!body) return;

        if (!rows || rows.length === 0) {
            body.innerHTML = ''; empty?.classList.remove('d-none'); wrap?.classList.add('d-none');
            if (badge) badge.textContent = '';
            return;
        }
        empty?.classList.add('d-none');
        wrap?.classList.remove('d-none');
        if (badge) badge.textContent = `Showing ${pagination.from} of ${pagination.total}`;

        body.innerHTML = rows.map(r => {
            const imgHtml = r.image_url
                ? `<div class="symbol symbol-40px me-2"><img src="${esc(r.image_url)}" class="img-fluid rounded" alt="${esc(r.variant_name)}"></div>`
                : '';

            const pct = Math.min(100, Number(r.reorder_percentage || 0));

            return `
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold">${esc(r.sku)}</div>
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
                    <td class="text-center"><span class="fw-bold text-${r.severity_color}">${fmtNum(r.quantity_on_hand, 0)}</span></td>
                    <td class="text-center"><span class="badge badge-light-${r.stock_source === 'Global' ? 'primary' : 'info'}">${fmtNum(r.threshold, 0)}</span></td>
                    <td class="text-center">
                        <span class="fw-bold text-danger">-${fmtNum(r.deficit, 0)}</span>
                        <div class="text-muted fs-8">${money(r.deficit_value)}</div>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-${r.severity_color}">
                            <i class="ki-duotone ${r.severity_icon} fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>
                            ${esc(r.severity_text)}
                        </span>
                        <div class="progress mt-1 mx-auto" style="height:4px; width:60px;">
                            <div class="progress-bar bg-${r.severity_color}" style="width:${pct}%;"></div>
                        </div>
                    </td>
                    <td class="text-center"><span class="badge badge-light-${r.urgency_color}">${esc(r.urgency_text)}</span></td>
                    <td class="text-center"><span class="badge badge-light-${r.stock_source === 'Global' ? 'primary' : 'info'}">${esc(r.stock_source)}</span></td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('lsPaginationInfo');
        const ul   = $('lsPagination');
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
        if (state.departmentId) params.set('department_id', state.departmentId);
        if (state.locationId)   params.set('location_id',   state.locationId);
        if (state.variantId)    params.set('variant_id',    state.variantId);
        if (state.severity)     params.set('severity',      state.severity);
        window.location.href = `/reports/inventory/low-stock-alerts?${params.toString()}&export=1`;
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