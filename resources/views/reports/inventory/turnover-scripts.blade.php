<script>
window.InventoryTurnover = (function () {
    'use strict';

    const ROUTE    = window.TOV_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.TOV_BOOTSTRAP || {};

    const state = {
        startDate:    BOOT.startDate    || '',
        endDate:      BOOT.endDate      || '',
        locationId:   BOOT.locationId   || '',
        departmentId: BOOT.departmentId || '',
        variantId:    BOOT.variantId    || '',
        perPage:      Number(BOOT.perPage || 15),
        page:         1,
    };

    let rateChart = null;
    let categoryChart = null;
    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('tovApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('tovClearBtn')?.addEventListener('click', resetFilters);
        $('tovExportBtn')?.addEventListener('click', exportCsv);
        load();
    });

    function readFilters() {
        state.startDate    = $('tovStartDate')?.value   || '';
        state.endDate      = $('tovEndDate')?.value     || '';
        state.locationId   = $('tovLocation')?.value    || '';
        state.departmentId = $('tovDepartment')?.value  || '';
        state.variantId    = $('tovVariant')?.value     || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.departmentId = state.variantId = '';
        state.page = 1;
        if ($('tovStartDate')) $('tovStartDate').value = '';
        if ($('tovEndDate'))   $('tovEndDate').value   = '';
        if (window.jQuery) jQuery('#tovLocation, #tovDepartment, #tovVariant').val(null).trigger('change.select2');
        load();
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
            console.error('[turnover] load failed:', err);
            window.toastr?.error('Failed to load turnover report.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('tovSkeleton')?.classList.toggle('d-none', !v);
        $('tovContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderRateChart(p.top_turnover);
        renderCategoryChart(p.categories, p.summary);
        renderTable(p.items, p.pagination);
    }

    function renderSummary(s) {
        $('sumAvgRate').textContent   = fmtNum(s.avg_turnover_rate, 2);
        $('sumAvgDays').textContent   = fmtNum(s.avg_days_held, 1);
        $('sumTotalMove').textContent = fmtNum(s.total_movement, 0);
        $('sumTotalTx').textContent   = fmtNum(s.total_transactions, 0);
        $('sumFast').textContent      = s.fast_moving;
        $('sumSlow').textContent      = s.slow_moving;
        $('sumNon').textContent       = s.non_moving;
    }

    function renderRateChart(rows) {
        const el = $('tovRateChart');
        const empty = $('tovRateEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (rateChart) { rateChart.destroy(); rateChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [{ name: 'Turnover Rate', data: rows.map(r => r.value) }],
            chart: { type: 'bar', height: 350, toolbar: { show: true }, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: false, columnWidth: '60%' } },
            dataLabels: { enabled: false },
            xaxis: {
                categories: rows.map(r => r.name),
                labels: { rotate: -45, trim: true, style: { fontSize: '11px' } },
            },
            yaxis: { title: { text: 'Turnover Rate' }, labels: { formatter: v => Number(v).toFixed(0) } },
            colors: ['#3E97FF'],
            tooltip: { y: { formatter: v => Number(v).toFixed(2) } },
        };

        if (rateChart) rateChart.updateOptions(options, false, true);
        else { rateChart = new ApexCharts(el, options); rateChart.render(); }
    }

    function renderCategoryChart(rows, summary) {
        const el = $('tovCategoryChart');
        const empty = $('tovCategoryEmpty');
        if (!el) return;
        const total = (summary.fast_moving || 0) + (summary.slow_moving || 0) + (summary.non_moving || 0);
        if (total === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (categoryChart) { categoryChart.destroy(); categoryChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: rows.map(r => r.value),
            chart: { type: 'donut', height: 350, fontFamily: 'inherit' },
            labels: rows.map(r => r.name),
            colors: ['#F1416C', '#FFC700', '#7E8299'],
            legend: { position: 'bottom', fontSize: '12px' },
            tooltip: { y: { formatter: v => v + ' items' } },
        };

        if (categoryChart) categoryChart.updateOptions(options, false, true);
        else { categoryChart = new ApexCharts(el, options); categoryChart.render(); }
    }

    function renderTable(rows, pagination) {
        const body = $('tovTableBody');
        const empty = $('tovEmpty');
        const wrap = $('tovPaginationWrap');
        const badge = $('tovBadge');
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
            const rate = Number(r.turnover_rate);
            const turnoverColor = rate < 1 ? 'danger' : rate < 5 ? 'warning' : 'success';
            const dayColor = r.days_inventory_held > 90 ? 'text-danger' : 'text-success';
            const imgHtml = r.variant_image
                ? `<div class="symbol symbol-50px me-3"><img src="${esc(r.variant_image)}" class="img-fluid rounded" alt="${esc(r.variant_name)}"></div>`
                : '';

            return `
                <tr>
                    <td class="ps-4">
                        <div class="fw-semibold">${esc(r.variant_sku || '-')}</div>
                        ${r.variant_barcode ? `<small class="text-muted">${esc(r.variant_barcode)}</small>` : ''}
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
                    <td class="text-center"><span class="fw-bold text-primary">${fmtNum(r.total_movement, 0)}</span></td>
                    <td class="text-center"><span class="badge badge-light-info">${fmtNum(r.transaction_count, 0)}</span></td>
                    <td class="text-center">${fmtNum(r.avg_stock_level, 1)}</td>
                    <td class="text-center"><span class="badge badge-light-${turnoverColor}">${fmtNum(r.turnover_rate, 2)}</span></td>
                    <td class="text-center"><span class="fw-bold ${dayColor}">${fmtNum(r.days_inventory_held, 1)}</span></td>
                    <td class="text-center"><span class="badge badge-light-${r.movement_color}">${esc(r.movement_label)}</span></td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('tovPaginationInfo');
        const ul   = $('tovPagination');
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
        if (state.locationId)   params.set('location_id',   state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);
        if (state.variantId)    params.set('variant_id',    state.variantId);
        window.location.href = `/reports/inventory/turnover?${params.toString()}&export=1`;
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

    return { load };
})();
</script>