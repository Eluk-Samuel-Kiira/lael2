<script>
window.StockAging = (function () {
    'use strict';

    const ROUTE    = window.SA_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.SA_BOOTSTRAP || {};

    const state = {
        departmentId: BOOT.departmentId || '',
        locationId:   BOOT.locationId   || '',
        variantId:    BOOT.variantId    || '',
        category:     BOOT.category     || '',
        perPage:      Number(BOOT.perPage || 15),
        page:         1,
    };

    let distChart = null;
    let catChart  = null;
    let inFlight  = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('saApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('saClearBtn')?.addEventListener('click', resetFilters);
        $('saExportBtn')?.addEventListener('click', exportCsv);
        load();
    });

    function readFilters() {
        state.departmentId = $('saDepartment')?.value || '';
        state.locationId   = $('saLocation')?.value   || '';
        state.variantId    = $('saVariant')?.value    || '';
        state.category     = $('saCategory')?.value   || '';
    }

    function resetFilters() {
        state.departmentId = state.locationId = state.variantId = state.category = '';
        state.page = 1;
        if ($('saCategory')) $('saCategory').value = '';
        if (window.jQuery) jQuery('#saLocation, #saDepartment, #saVariant').val(null).trigger('change.select2');
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
        if (state.category)     params.set('category',      state.category);

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
            console.error('[stock-aging] load failed:', err);
            window.toastr?.error('Failed to load stock aging report.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('saSkeleton')?.classList.toggle('d-none', !v);
        $('saContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderDistChart(p.dist_chart);
        renderCatChart(p.cat_chart);
        renderTable(p.items, p.pagination);
    }

    function renderSummary(s) {
        $('sumExpired').textContent  = fmtNum(s.expired, 0);
        $('sumWeek').textContent     = fmtNum(s['1_week'], 0);
        $('sumMonth').textContent    = fmtNum(s['1_month'], 0);
        $('sum3Months').textContent  = fmtNum(s['3_months'], 0);
        $('sum6Months').textContent  = fmtNum(s['6_months'], 0);
        $('sumOver6').textContent    = fmtNum(s['over_6_months'], 0);

        $('sumTotalItems').textContent     = fmtNum(s.total_items, 0);
        $('sumValueAtRisk').textContent    = money(s.total_value_at_risk);
        $('sumTotalInventory').textContent = money(s.total_inventory_value);
        $('sumBatchTracked').textContent   = fmtNum(s.batch_tracked_items, 0);
    }

    function renderDistChart(rows) {
        const el = $('saDistChart');
        const empty = $('saDistEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (distChart) { distChart.destroy(); distChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [
                { name: 'Days to Expiry', data: rows.map(r => r.days),     type: 'bar'  },
                { name: 'Quantity',       data: rows.map(r => r.quantity), type: 'line' },
            ],
            chart: { type: 'bar', height: 350, toolbar: { show: true }, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: false, columnWidth: '55%' } },
            stroke: { width: [0, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: {
                categories: rows.map(r => r.name),
                labels: { rotate: -45, trim: true, style: { fontSize: '11px' } },
            },
            yaxis: [
                { title: { text: 'Days to Expiry' }, labels: { formatter: v => Number(v).toFixed(0) + ' d' } },
                { opposite: true, title: { text: 'Quantity' }, labels: { formatter: v => Number(v).toFixed(0) } },
            ],
            colors: ['#3E97FF', '#50CD89'],
            tooltip: {
                shared: true, intersect: false,
                y: { formatter: (val, { seriesIndex }) =>
                    seriesIndex === 0 ? Number(val).toFixed(0) + ' days'
                                      : Number(val).toFixed(0) + ' units' },
            },
        };

        if (distChart) distChart.updateOptions(options, false, true);
        else { distChart = new ApexCharts(el, options); distChart.render(); }
    }

    function renderCatChart(rows) {
        const el = $('saCatChart');
        const empty = $('saCatEmpty');
        if (!el) return;
        const filtered = (rows || []).filter(r => Number(r.value) > 0);
        if (filtered.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (catChart) { catChart.destroy(); catChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: filtered.map(r => Number(r.value)),
            chart:  { type: 'donut', height: 350, fontFamily: 'inherit' },
            labels: filtered.map(r => r.name),
            colors: ['#F1416C', '#FFC700', '#FFA800', '#3E97FF', '#50CD89', '#7239EA'],
            legend: { position: 'bottom', fontSize: '12px' },
            plotOptions: { pie: { donut: {
                size: '65%',
                labels: {
                    show: true,
                    total: {
                        show: true, label: 'Total',
                        formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString(),
                    },
                },
            }}},
            tooltip: { y: { formatter: v => Number(v).toFixed(0) + ' units' } },
        };

        if (catChart) catChart.updateOptions(options, false, true);
        else { catChart = new ApexCharts(el, options); catChart.render(); }
    }

    function renderTable(rows, pagination) {
        const body = $('saTableBody');
        const empty = $('saEmpty');
        const wrap = $('saPaginationWrap');
        const badge = $('saBadge');
        const footer = $('saTableFooter');
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
            const marginColor = margin > 30 ? 'success' : margin > 15 ? 'warning' : 'danger';
            const imgHtml = r.image_url
                ? `<div class="symbol symbol-50px me-3"><img src="${esc(r.image_url)}" class="img-fluid rounded" alt="${esc(r.variant_name)}"></div>`
                : '';

            const batchHtml = r.is_batch_tracked
                ? `<div class="d-flex flex-column">
                       <span class="badge badge-light-success">${esc(r.batch_number)}</span>
                       ${r.last_batch_event ? `<div class="text-muted fs-8 mt-1">${esc(r.last_batch_event)}</div>` : ''}
                   </div>`
                : `<span class="badge badge-light-secondary">{{ __('pagination.not_batch_tracked') }}</span>`;

            const expiryHtml = r.expiry_date
                ? `<span class="fw-bold ${Number(r.days_to_expiry) < 0 ? 'text-danger' : 'text-success'}">${esc(r.expiry_date)}</span>`
                : '<span class="text-muted">-</span>';

            const daysText = Number(r.days_to_expiry) < 0
                ? 'Expired'
                : Number(r.days_to_expiry) > 999
                    ? '999+ days'
                    : `${fmtNum(r.days_to_expiry, 0)} days`;

            const daysColor = Number(r.days_to_expiry) < 0
                ? 'text-danger'
                : Number(r.days_to_expiry) <= 30 ? 'text-warning' : 'text-success';

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
                    <td>${batchHtml}</td>
                    <td>${expiryHtml}</td>
                    <td class="text-center"><span class="fw-bold ${daysColor}">${daysText}</span></td>
                    <td class="text-center"><span class="fw-bold">${fmtNum(r.quantity_allocated, 0)}</span></td>
                    <td>
                        <div class="d-flex flex-column">
                            <div class="d-flex justify-content-between"><span class="text-muted fs-8">Cost:</span><span class="fw-bold">${money(r.cost_price)}</span></div>
                            <div class="d-flex justify-content-between"><span class="text-muted fs-8">Sell:</span><span class="fw-bold text-primary">${money(r.selling_price)}</span></div>
                            <div class="d-flex justify-content-between"><span class="text-muted fs-8">Margin:</span><span class="badge badge-light-${marginColor}">${fmtNum(margin, 1)}%</span></div>
                            <div class="d-flex justify-content-between mt-1"><span class="text-muted fs-8">Value:</span><span class="fw-bold text-success">${money(r.inventory_value)}</span></div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-${r.status_color} fs-7 py-2 px-3">${esc(r.status_text)}</span>
                        ${Number(r.value_at_risk) > 0
                            ? `<div class="text-danger fs-8 mt-1">At Risk: ${money(r.value_at_risk)}</div>` : ''}
                    </td>
                </tr>
            `;
        }).join('');

        $('ftQuantity').textContent = fmtNum(rows.reduce((s, r) => s + Number(r.quantity_allocated || 0), 0), 0);

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('saPaginationInfo');
        const ul   = $('saPagination');
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
        if (state.category)     params.set('category',      state.category);
        window.location.href = `/reports/inventory/stock-aging?${params.toString()}&export=1`;
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