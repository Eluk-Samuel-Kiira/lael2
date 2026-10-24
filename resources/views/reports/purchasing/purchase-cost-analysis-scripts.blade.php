<script>
window.PurchaseCost = (function () {
    'use strict';

    const ROUTE    = window.PCA_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.PCA_BOOTSTRAP || {};
    const I18N     = window.PCA_I18N || {};

    const state = {
        startDate:  BOOT.startDate  || '',
        endDate:    BOOT.endDate    || '',
        variantId:  BOOT.variantId  || '',
        supplierId: BOOT.supplierId || '',
        perPage:    Number(BOOT.perPage || 15),
        page:       1,
    };

    let chart = null;
    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('pcaApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('pcaClearBtn')?.addEventListener('click', resetFilters);
        $('pcaExportExcel')?.addEventListener('click', () => exportFile('excel'));
        $('pcaExportPdf')?.addEventListener('click', () => exportFile('pdf'));
        load();
    });

    function readFilters() {
        state.startDate  = $('pcaStartDate')?.value || '';
        state.endDate    = $('pcaEndDate')?.value   || '';
        state.variantId  = $('pcaVariant')?.value   || '';
        state.supplierId = $('pcaSupplier')?.value  || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.variantId = state.supplierId = '';
        state.page = 1;
        if ($('pcaStartDate')) $('pcaStartDate').value = '';
        if ($('pcaEndDate'))   $('pcaEndDate').value   = '';
        if (window.jQuery) {
            jQuery('#pcaVariant').val(null).trigger('change.select2');
            jQuery('#pcaSupplier').val(null).trigger('change.select2');
        }
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)  params.set('start_date', state.startDate);
        if (state.endDate)    params.set('end_date', state.endDate);
        if (state.variantId)  params.set('variant_id', state.variantId);
        if (state.supplierId) params.set('supplier_id', state.supplierId);

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
            console.error('[purchase-cost] load failed:', err);
            window.toastr?.error('Failed to load purchase cost analysis.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('pcaSkeleton')?.classList.toggle('d-none', !v);
        $('pcaContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderMovement(p.summary);
        renderTrend(p.price_trends, p.meta);
        renderTop(p.top_products);
        renderTable(p.products, p.pagination, p.page_totals, p.summary);
    }

    // ─── Summary cards ───────────────────────────────────────
    function renderSummary(s) {
        const cards = [
            { title: I18N.totalProductsAnalyzed, value: fmtNum(s.total_products, 0), color: 'primary', icon: 'bi-box-seam',        desc: I18N.uniqueProducts },
            { title: I18N.totalQuantity,         value: fmtNum(s.total_quantity, 0), color: 'success', icon: 'bi-arrow-up',        desc: I18N.totalUnitsPurchased },
            { title: I18N.totalCost,             value: money(s.total_cost),         color: 'info',    icon: 'bi-currency-dollar', desc: I18N.totalSpend },
            { title: I18N.avgPriceChange,        value: fmtNum(s.avg_price_increase, 2) + '%', color: s.avg_price_increase > 0 ? 'danger' : 'success', icon: 'bi-graph-up', desc: I18N.averagePriceMovement },
        ];

        $('pcaSummaryCards').innerHTML = cards.map(c => `
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush bg-light-${c.color} border border-${c.color} border-dashed h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-4">
                            <div class="symbol symbol-60px flex-shrink-0">
                                <div class="symbol-label bg-${c.color} bg-opacity-10">
                                    <i class="bi ${c.icon} fs-2x text-${c.color}"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="text-gray-600 fw-semibold fs-7">${esc(c.title)}</div>
                                <div class="fs-2 fw-bold text-${c.color} lh-1 mt-1" style="word-break:break-word;">${esc(c.value)}</div>
                                <div class="text-muted fs-8 mt-1">${esc(c.desc)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function renderMovement(s) {
        const cards = [
            { title: I18N.productsWithPriceIncrease, value: fmtNum(s.products_with_price_increase, 0), color: 'danger',  icon: 'bi-arrow-up-right',   desc: I18N.priceIncreased },
            { title: I18N.productsWithPriceDecrease, value: fmtNum(s.products_with_price_decrease, 0), color: 'success', icon: 'bi-arrow-down-right', desc: I18N.priceDecreased },
            { title: I18N.priceStableProducts,       value: fmtNum(s.products_stable, 0),              color: 'warning', icon: 'bi-dash-circle',     desc: I18N.priceRemainedStable },
        ];

        $('pcaMovementCards').innerHTML = cards.map(c => `
            <div class="col-md-4">
                <div class="card card-flush bg-light-${c.color} border border-${c.color} border-dashed h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-4">
                            <div class="symbol symbol-60px flex-shrink-0">
                                <div class="symbol-label bg-${c.color} bg-opacity-10">
                                    <i class="bi ${c.icon} fs-2x text-${c.color}"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="text-gray-600 fw-semibold fs-7">${esc(c.title)}</div>
                                <div class="fs-2 fw-bold text-${c.color} lh-1 mt-1">${esc(c.value)}</div>
                                <div class="text-muted fs-8 mt-1">${esc(c.desc)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // ─── Chart ───────────────────────────────────────────────
    function renderTrend(trends, meta) {
        const wrap  = $('pcaTrendWrap');
        const el    = $('pcaChart');
        const empty = $('pcaChartEmpty');
        const badge = $('pcaPeriodBadge');

        if (!trends || trends.length === 0) {
            wrap?.classList.add('d-none');
            if (chart) { chart.destroy(); chart = null; }
            return;
        }
        wrap?.classList.remove('d-none');
        if (badge) badge.textContent = `${meta.start_date} → ${meta.end_date}`;

        const dates    = trends.map(t => t.date);
        const prices   = trends.map(t => t.avg_unit_cost);
        const quantity = trends.map(t => t.total_quantity);

        const options = {
            series: [
                { name: I18N.avgUnitCost, type: 'line', data: prices },
                { name: I18N.totalQuantity, type: 'bar', data: quantity },
            ],
            chart:  { height: 400, type: 'line', toolbar: { show: true, tools: { download: true } }, fontFamily: 'inherit' },
            colors: ['#3E97FF', '#50CD89'],
            stroke: { width: [3, 0] },
            dataLabels: {
                enabled: true, enabledOnSeries: [0],
                formatter: (val) => CURRENCY + Number(val).toFixed(2),
                offsetY: -10,
            },
            xaxis: {
                categories: dates,
                labels: { rotate: -45, style: { fontSize: '11px' } },
            },
            yaxis: [
                { title: { text: I18N.avgUnitCost + ' (' + CURRENCY + ')' }, labels: { formatter: v => CURRENCY + Number(v).toFixed(2) } },
                { opposite: true, title: { text: I18N.totalQuantity }, labels: { formatter: v => Number(v).toFixed(0) } },
            ],
            tooltip: {
                shared: true, intersect: false,
                y: [
                    { formatter: v => CURRENCY + Number(v).toFixed(2) },
                    { formatter: v => Number(v).toFixed(0) + ' ' + I18N.units },
                ],
            },
            legend: { position: 'top', horizontalAlign: 'center' },
            plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
            grid: { borderColor: '#e7e7e7', row: { colors: ['#f3f3f3', 'transparent'], opacity: 0.3 } },
        };

        if (chart) chart.updateOptions(options, false, true);
        else { chart = new ApexCharts(el, options); chart.render(); }
        empty?.classList.add('d-none');
        el.classList.remove('d-none');
    }

    // ─── Top products ────────────────────────────────────────
    function renderTop(top) {
        const wrap  = $('pcaTopWrap');
        const body  = $('pcaTopBody');
        const badge = $('pcaTopBadge');

        if (!top || top.length === 0) {
            wrap?.classList.add('d-none');
            return;
        }
        wrap?.classList.remove('d-none');
        if (badge) badge.textContent = `Top ${top.length}`;

        body.innerHTML = top.map(p => {
            const trendText = I18N[p.trend_key] || p.trend_key;
            return `
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="symbol symbol-40px flex-shrink-0">
                                <div class="symbol-label bg-light-primary text-primary fw-bold fs-6">${esc(initials(p.product_name))}</div>
                            </div>
                            <div>
                                <div class="fw-bold">${esc(p.product_name || '—')}</div>
                                <small class="text-muted">${esc(p.sku || 'N/A')}${p.product_name_parent ? ' · ' + esc(p.product_name_parent) : ''}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="fw-bold">${fmtNum(p.total_quantity, 0)}</span><div class="text-muted fs-8">${esc(I18N.units)}</div></td>
                    <td><span class="fw-bold text-primary">${money(p.avg_unit_cost)}</span></td>
                    <td><span class="fw-bold text-success">${money(p.total_cost)}</span></td>
                    <td><span class="fw-bold text-${p.trend_color}">${money(p.price_change)}</span></td>
                    <td>
                        <span class="fw-bold text-${p.trend_color}">${fmtNum(p.price_change_percentage, 2)}%</span>
                        ${p.price_change_percentage !== 0
                            ? `<i class="bi ${p.price_change_percentage > 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right'} text-${p.trend_color} ms-1"></i>`
                            : ''}
                    </td>
                    <td><span class="badge badge-light-info">${fmtNum(p.purchase_count, 0)} ${esc(I18N.purchases)}</span></td>
                    <td class="text-end pe-4"><span class="badge badge-${p.trend_color}">${esc(trendText)}</span></td>
                </tr>
            `;
        }).join('');
    }

    // ─── Full table ──────────────────────────────────────────
    function renderTable(rows, pagination, pageTotals, summary) {
        const body  = $('pcaTableBody');
        const foot  = $('pcaTableFoot');
        const empty = $('pcaEmpty');
        const wrap  = $('pcaPaginationWrap');
        const badge = $('pcaBadge');

        if (!rows || rows.length === 0) {
            body.innerHTML = ''; foot.innerHTML = '';
            empty?.classList.remove('d-none'); wrap?.classList.add('d-none');
            if (badge) badge.textContent = '';
            return;
        }
        empty?.classList.add('d-none');
        wrap?.classList.remove('d-none');
        if (badge) badge.textContent = `Showing ${pagination.from} of ${pagination.total}`;

        body.innerHTML = rows.map((p, i) => {
            const idx = (pagination.from || 1) + i;
            return `
                <tr>
                    <td class="ps-4 text-center fw-bold">${idx}</td>
                    <td>
                        <div class="fw-bold">${esc(p.product_name || '—')}</div>
                        <small class="text-muted">${esc(p.sku || 'N/A')}</small>
                    </td>
                    <td>${p.category_name
                        ? `<span class="badge badge-light-info">${esc(p.category_name)}</span>`
                        : `<span class="text-muted">-</span>`}</td>
                    <td class="text-end">
                        <span class="fw-bold">${fmtNum(p.total_quantity, 0)}</span>
                        <div class="text-muted fs-8">${esc(I18N.units)}</div>
                    </td>
                    <td class="text-end"><span class="fw-bold text-primary">${money(p.avg_unit_cost)}</span></td>
                    <td class="text-end"><span class="fw-bold text-success">${money(p.total_cost)}</span></td>
                    <td class="text-end">
                        <div class="d-flex align-items-center justify-content-end">
                            <span class="fw-bold text-${p.trend_color} me-1">${money(p.price_change)}</span>
                            ${p.price_change !== 0
                                ? `<i class="bi ${p.price_change > 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right'} text-${p.trend_color}"></i>`
                                : ''}
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-${p.trend_color}">${fmtNum(p.price_change_percentage, 2)}%</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-${p.frequency_color}">${esc(I18N[p.frequency_key + '_frequency'] || p.frequency_key)}</span>
                        <div class="text-muted fs-8">${fmtNum(p.purchase_count, 0)} ${esc(I18N.orders)}</div>
                    </td>
                    <td class="text-center pe-4">
                        <span class="badge badge-${p.efficiency_color}">${esc(I18N[p.efficiency_key + '_efficiency'] || p.efficiency_key)}</span>
                    </td>
                </tr>
            `;
        }).join('');

        // Footer
        const grandQty  = summary.total_quantity;
        const grandCost = summary.total_cost;
        const showGrand = pagination.total > (pagination.to - pagination.from + 1);

        foot.innerHTML = `
            <tr>
                <td colspan="3" class="text-end fw-bold">${esc(I18N.currentPage)} (${esc(I18N.totalQuantity)}):</td>
                <td class="text-end fw-bold">${fmtNum(pageTotals.quantity, 0)}</td>
                <td class="text-end fw-bold">${esc(I18N.currentPage)} (${esc(I18N.totalCost)}):</td>
                <td class="text-end fw-bold text-primary">${money(pageTotals.cost)}</td>
                <td colspan="4"></td>
            </tr>
            ${showGrand ? `
            <tr>
                <td colspan="3" class="text-end fw-bold text-muted">${esc(I18N.grandTotal)} (${esc(I18N.totalQuantity)}):</td>
                <td class="text-end fw-bold text-muted">${fmtNum(grandQty, 0)}</td>
                <td class="text-end fw-bold text-muted">${esc(I18N.grandTotal)} (${esc(I18N.totalCost)}):</td>
                <td class="text-end fw-bold text-primary">${money(grandCost)}</td>
                <td colspan="4"></td>
            </tr>` : ''}
        `;

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('pcaPaginationInfo');
        const ul   = $('pcaPagination');
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

    // ─── Export ──────────────────────────────────────────────
    function exportFile(format) {
        const params = new URLSearchParams({ export: format });
        if (state.startDate)  params.set('start_date', state.startDate);
        if (state.endDate)    params.set('end_date', state.endDate);
        if (state.variantId)  params.set('variant_id', state.variantId);
        if (state.supplierId) params.set('supplier_id', state.supplierId);
        window.location.href = `/reports/purchasing/purchase-cost-analysis?${params.toString()}`;
    }

    // ─── Helpers ─────────────────────────────────────────────
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
    function initials(name) {
        if (!name) return '?';
        return String(name).trim().split(/\s+/).slice(0, 2).map(p => p[0]).join('').toUpperCase();
    }

    return { load };
})();
</script>