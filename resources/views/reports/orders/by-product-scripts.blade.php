<script>
window.ByProduct = (function () {
    'use strict';

    const ROUTE    = window.BP_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const CSRF     = window.CSRF_TOKEN || '';
    const BOOT     = window.BP_BOOTSTRAP || {};
    const I18N     = window.BP_I18N || {};

    const state = {
        startDate:      BOOT.startDate      || '',
        endDate:        BOOT.endDate        || '',
        locationId:     BOOT.locationId     || '',
        departmentId:   BOOT.departmentId   || '',
        minQuantity:    BOOT.minQuantity    ?? '',
        maxQuantity:    BOOT.maxQuantity    ?? '',
        minRevenue:     BOOT.minRevenue     ?? '',
        maxRevenue:     BOOT.maxRevenue     ?? '',
        minProfit:      BOOT.minProfit      ?? '',
        velocityFilter: BOOT.velocityFilter || '',
        perPage:        Number(BOOT.perPage || 25),
        page:           1,
    };

    let chart             = null;
    let recipeDrawerBs    = null;
    let serialDrawerBs    = null;
    let inFlight          = false;
    let currentSerials    = [];
    let currentVariantId  = null;
    let currentSerialsData = null;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('bpApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('bpClearBtn')?.addEventListener('click', resetFilters);
        $('bpExportBtn')?.addEventListener('click', exportCsv);

        // Row action handlers
        $('bpProductsBody')?.addEventListener('click', (e) => {
            const recipeBtn = e.target.closest('button[data-action="recipe"]');
            if (recipeBtn) { openRecipeDrawer(recipeBtn.dataset.variantId, recipeBtn.dataset.variantName); return; }
            const serialBtn = e.target.closest('button[data-action="serials"]');
            if (serialBtn) { openSerialDrawer(serialBtn.dataset.variantId, serialBtn.dataset.variantName); return; }
        });

        // Serial drawer internal handlers
        $('bpSerialStatusFilter')?.addEventListener('change', filterSerials);
        $('bpSerialSearchInput')?.addEventListener('input', filterSerials);
        $('bpSerialRefreshBtn')?.addEventListener('click', refreshSerials);
        $('bpSerialBody')?.addEventListener('click', (e) => {
            const statusBtn = e.target.closest('button[data-serial-status]');
            if (statusBtn) { updateSerialStatus(statusBtn.dataset.serialId, statusBtn.dataset.serialStatus); return; }
            const delBtn = e.target.closest('button[data-serial-delete]');
            if (delBtn) { deleteSerial(delBtn.dataset.serialDelete); return; }
        });

        // Drawer cleanup
        document.getElementById('bpRecipeDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('bpRecipeContent').innerHTML = '';
            $('bpRecipeSubtitle').textContent = '';
        });
        document.getElementById('bpSerialDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            currentSerials = []; currentVariantId = null; currentSerialsData = null;
            $('bpSerialBody').innerHTML = '';
        });

        load();
    });

    function readFilters() {
        state.startDate      = $('bpStartDate')?.value   || '';
        state.endDate        = $('bpEndDate')?.value     || '';
        state.locationId     = $('bpLocation')?.value    || '';
        state.departmentId   = $('bpDepartment')?.value  || '';
        state.minQuantity    = $('bpMinQty')?.value      ?? '';
        state.maxQuantity    = $('bpMaxQty')?.value      ?? '';
        state.minRevenue     = $('bpMinRevenue')?.value  ?? '';
        state.maxRevenue     = $('bpMaxRevenue')?.value  ?? '';
        state.minProfit      = $('bpMinProfit')?.value   ?? '';
        state.velocityFilter = $('bpVelocity')?.value    || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.departmentId = '';
        state.minQuantity = state.maxQuantity = state.minRevenue = state.maxRevenue = state.minProfit = '';
        state.velocityFilter = '';
        state.page = 1;
        ['bpStartDate','bpEndDate','bpMinQty','bpMaxQty','bpMinRevenue','bpMaxRevenue','bpMinProfit','bpVelocity'].forEach(id => {
            if ($(id)) $(id).value = '';
        });
        if (window.jQuery) {
            jQuery('#bpLocation').val(null).trigger('change.select2');
            jQuery('#bpDepartment').val(null).trigger('change.select2');
        }
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)      params.set('start_date', state.startDate);
        if (state.endDate)        params.set('end_date', state.endDate);
        if (state.locationId)     params.set('location_id', state.locationId);
        if (state.departmentId)   params.set('department_id', state.departmentId);
        if (state.velocityFilter) params.set('velocity_filter', state.velocityFilter);
        if (state.minQuantity !== '' && state.minQuantity !== null) params.set('min_quantity', state.minQuantity);
        if (state.maxQuantity !== '' && state.maxQuantity !== null) params.set('max_quantity', state.maxQuantity);
        if (state.minRevenue  !== '' && state.minRevenue  !== null) params.set('min_revenue',  state.minRevenue);
        if (state.maxRevenue  !== '' && state.maxRevenue  !== null) params.set('max_revenue',  state.maxRevenue);
        if (state.minProfit   !== '' && state.minProfit   !== null) params.set('min_profit',   state.minProfit);

        try {
            const res = await fetch(`${ROUTE}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            render(json.data);
            showSkeleton(false);
        } catch (err) {
            console.error('[by-product] load failed:', err);
            window.toastr?.error('Failed to load product report.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('bpSkeleton')?.classList.toggle('d-none', !v);
        $('bpContent')?.classList.toggle('d-none', v);
    }

    function render(d) {
        if (!d || !d.summary || d.summary.total_products === 0) {
            $('bpEmpty')?.classList.remove('d-none');
            $('bpSummaryStats').innerHTML = '';
            ['bpChartCard','bpTableCard','bpTop5Card','bpBottom5Card'].forEach(id => $(id)?.classList.add('d-none'));
            if (chart) { chart.destroy(); chart = null; }
            return;
        }
        $('bpEmpty')?.classList.add('d-none');
        renderSummaryStats(d.summary);
        renderChart(d.top_products);
        renderTable(d.products, d.pagination);
        renderTop5(d.top_five);
        renderBottom5(d.bottom_five);
    }

    // ─── Summary stats ───────────────────────────────────────
    function renderSummaryStats(s) {
        const items = [
            { color: 'primary',   icon: 'bi-box',              value: fmtNum(s.total_products, 0),     label: I18N.totalProducts },
            { color: 'success',   icon: 'bi-currency-dollar',  value: money(s.total_revenue),          label: I18N.totalRevenue },
            { color: 'info',      icon: 'bi-box-seam',         value: fmtNum(s.total_quantity, 0),     label: I18N.totalQuantitySold },
            { color: 'warning',   icon: 'bi-graph-up',         value: fmtNum(s.avg_daily_sales, 1),    label: I18N.averageDailySales },
            { color: 'danger',    icon: 'bi-trophy',           value: truncate(s.top_product, 15),     label: I18N.topProduct },
            { color: 'secondary', icon: 'bi-cash-coin',        value: money(s.top_revenue),            label: I18N.topProductRevenue },
        ];
        $('bpSummaryStats').innerHTML = items.map(it => `
            <div class="col-md-6 col-lg-2">
                <div class="card card-flush bg-light-${it.color} border border-${it.color} border-dashed h-100">
                    <div class="card-body text-center">
                        <i class="bi ${it.icon} fs-2tx text-${it.color} mb-3 d-block"></i>
                        <div class="fs-1 fw-bold text-gray-800">${esc(it.value)}</div>
                        <div class="text-gray-600 fw-semibold">${esc(it.label)}</div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // ─── Chart ───────────────────────────────────────────────
    function renderChart(topProducts) {
        const el = $('bpChart');
        if (!el) return;
        if (!topProducts || topProducts.length === 0) {
            $('bpChartCard')?.classList.add('d-none');
            if (chart) { chart.destroy(); chart = null; }
            return;
        }
        $('bpChartCard')?.classList.remove('d-none');

        const names    = topProducts.map(p => truncate(p.variant_name || 'Unknown', 15));
        const revenue  = topProducts.map(p => Number(p.total_revenue || 0));
        const quantity = topProducts.map(p => Number(p.total_quantity_sold || 0));

        const options = {
            series: [
                { name: I18N.totalRevenue,     data: revenue,  type: 'bar' },
                { name: I18N.totalQuantitySold, data: quantity, type: 'line' },
            ],
            chart: { type: 'bar', height: 400, fontFamily: 'inherit', toolbar: { show: true, tools: { download: true } } },
            plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 4, borderRadiusApplication: 'end' } },
            stroke: { width: [0, 3], curve: 'smooth' },
            xaxis: { categories: names, labels: { rotate: -45, style: { fontSize: '11px' } } },
            yaxis: [
                { title: { text: `${I18N.totalRevenue} (${CURRENCY})`, style: { fontSize: '12px' } },
                  labels: { formatter: v => CURRENCY + Number(v).toLocaleString() } },
                { opposite: true, title: { text: I18N.totalQuantitySold, style: { fontSize: '12px' } } },
            ],
            colors: ['#3E97FF', '#50CD89'],
            fill: { opacity: [1, 1], gradient: { enabled: true, shade: 'light', type: 'horizontal' } },
            tooltip: {
                shared: true, intersect: false,
                y: {
                    formatter: (v, { seriesIndex }) => seriesIndex === 0
                        ? CURRENCY + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 })
                        : Number(v).toLocaleString(),
                },
            },
            legend: { position: 'top', horizontalAlign: 'center' },
            grid: { borderColor: '#f1f1f1', row: { colors: ['#f8f9fa', 'transparent'], opacity: 0.5 } },
            dataLabels: { enabled: false },
        };

        if (chart) chart.updateOptions(options, false, true);
        else { chart = new ApexCharts(el, options); chart.render(); }
    }

    // ─── Products table ──────────────────────────────────────
    function renderTable(rows, pagination) {
        if (!rows || rows.length === 0) {
            $('bpTableCard')?.classList.add('d-none');
            return;
        }
        $('bpTableCard')?.classList.remove('d-none');
        $('bpTableBadge').textContent = `${fmtNum(pagination.total, 0)} ${I18N.products}`;

        $('bpProductsBody').innerHTML = rows.map((p, i) => {
            const rank = (pagination.from || 1) + i;
            const medal = rank <= 3
                ? `<div class="mt-1"><span class="badge badge-light-${rank === 1 ? 'danger' : (rank === 2 ? 'warning' : 'info')}">
                   <i class="bi bi-${rank === 1 ? 'trophy' : 'award'} me-1"></i>${esc(I18N.top)} ${rank}
                   </span></div>`
                : '';

            const imageHtml = p.image_url
                ? `<img src="${esc(p.image_url)}" class="symbol-label" style="object-fit:cover;" onerror="this.style.display='none'">`
                : `<div class="symbol-label bg-light-primary text-primary fw-bold">${esc(initials(p.variant_name))}</div>`;

            const recipeBtn = p.has_recipe
                ? `<button type="button" class="btn btn-sm btn-light-primary btn-icon ms-2"
                           data-action="recipe"
                           data-variant-id="${p.variant_id}"
                           data-variant-name="${esc(p.variant_name)}"
                           title="${esc(I18N.recipeIngredients)}">
                       <i class="bi bi-book fs-3"></i>
                   </button>`
                : '';

            const serialCell = p.has_serials
                ? `<button type="button" class="btn btn-sm btn-light-warning"
                           data-action="serials"
                           data-variant-id="${p.variant_id}"
                           data-variant-name="${esc(p.variant_name)}">
                       <i class="bi bi-upc-scan fs-3 me-1"></i>
                       <span class="badge badge-light-info">${esc(I18N.serialNumbers)}</span>
                   </button>`
                : `<span class="text-muted">—</span>`;

            const velocityIcon = p.daily_sales_rate > 5
                ? 'bi-arrow-up-right text-success'
                : (p.daily_sales_rate < 1 ? 'bi-arrow-down-right text-danger' : 'bi-dash text-gray-400');

            return `
                <tr>
                    <td class="ps-4">
                        <span class="fw-bold">${rank}</span>
                        ${medal}
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-40px symbol-circle me-3">
                                ${imageHtml}
                            </div>
                            <div class="d-flex flex-column">
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold">${esc(p.variant_name)}</span>
                                    ${recipeBtn}
                                </div>
                                <span class="badge badge-light-${esc(p.velocity_color || 'secondary')} badge-sm mt-1">
                                    ${esc(p.velocity_category || '—')}
                                </span>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-light-primary">${esc(p.sku)}</span></td>
                    <td class="text-end"><span class="fw-bold text-primary">${fmtNum(p.total_quantity_sold, 0)}</span></td>
                    <td class="text-end"><span class="fw-bold text-success">${money(p.total_revenue)}</span></td>
                    <td class="text-end"><span class="text-info">${money(p.total_tax)}</span></td>
                    <td class="text-end"><span class="text-warning">${money(p.total_discount)}</span></td>
                    <td class="text-end"><span class="text-gray-600">${money(p.average_selling_price)}</span></td>
                    <td class="text-center"><span class="badge badge-light-info">${fmtNum(p.order_count, 0)}</span></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-light-${esc(p.velocity_color || 'secondary')} me-2">
                                ${fmtNum(p.daily_sales_rate, 1)}/day
                            </span>
                            <i class="bi ${velocityIcon}"></i>
                        </div>
                    </td>
                    <td>${p.last_sold_date ? `<span class="text-gray-700">${esc(formatDate(p.last_sold_date))}</span>` : `<span class="text-muted">—</span>`}</td>
                    <td class="text-center">${serialCell}</td>
                    <td class="pe-4">
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:8px;">
                                <div class="progress-bar bg-${esc(p.velocity_color || 'primary')}" style="width:${Math.min(Number(p.percentage), 100)}%;"></div>
                            </div>
                            <span class="fw-bold min-w-60px text-end">${fmtNum(p.percentage, 1)}%</span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);
    }

    // ─── Top 5 / Bottom 5 ────────────────────────────────────
    function renderTop5(rows) {
        if (!rows || rows.length === 0) { $('bpTop5Card')?.classList.add('d-none'); return; }
        $('bpTop5Card')?.classList.remove('d-none');
        $('bpTop5Body').innerHTML = rows.map((p, i) => `
            <tr>
                <td class="ps-4"><span class="fw-bold">${i + 1}</span></td>
                <td>${esc(truncate(p.variant_name, 25))}</td>
                <td class="text-end"><span class="fw-bold text-success">${money(p.total_revenue)}</span></td>
                <td class="text-end pe-4"><span class="badge badge-light-primary">${fmtNum(p.total_quantity_sold, 0)}</span></td>
            </tr>
        `).join('');
    }

    function renderBottom5(rows) {
        if (!rows || rows.length === 0) { $('bpBottom5Card')?.classList.add('d-none'); return; }
        $('bpBottom5Card')?.classList.remove('d-none');
        $('bpBottom5Body').innerHTML = rows.map((p, i) => `
            <tr>
                <td class="ps-4"><span class="fw-bold">${i + 1}</span></td>
                <td>${esc(truncate(p.variant_name, 25))}</td>
                <td class="text-end"><span class="fw-bold text-danger">${money(p.total_revenue)}</span></td>
                <td class="text-end pe-4"><span class="badge badge-light-primary">${fmtNum(p.total_quantity_sold, 0)}</span></td>
            </tr>
        `).join('');
    }

    // ─── Pagination ──────────────────────────────────────────
    function renderPagination(p) {
        const info = $('bpPaginationInfo');
        const ul   = $('bpPagination');
        const wrap = $('bpPaginationWrap');
        if (!ul || !info) return;
        if (p.total <= p.per_page) { wrap?.classList.add('d-none'); return; }
        wrap?.classList.remove('d-none');
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

    // ─── Drawer 1: Recipe ────────────────────────────────────
    function getRecipeDrawer() {
        const el = document.getElementById('bpRecipeDrawer');
        if (!el) return null;
        recipeDrawerBs = recipeDrawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return recipeDrawerBs;
    }

    async function openRecipeDrawer(variantId, variantName) {
        const inst = getRecipeDrawer();
        if (!inst) return;

        $('bpRecipeSubtitle').textContent = variantName;
        $('bpRecipeSkeleton')?.classList.remove('d-none');
        const content = $('bpRecipeContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        inst.show();

        try {
            const url = window.BP_ROUTES.recipe.replace('__ID__', variantId);
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');
            renderRecipeDrawer(json.ingredients || []);
        } catch (err) {
            console.error('[recipe-drawer] failed:', err);
            $('bpRecipeSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderRecipeDrawer(ingredients) {
        $('bpRecipeSkeleton')?.classList.add('d-none');
        const content = $('bpRecipeContent');
        if (!content) return;
        content.classList.remove('d-none');

        if (!ingredients || ingredients.length === 0) {
            content.innerHTML = `
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle me-2"></i>${esc(I18N.noIngredients)}
                </div>`;
            return;
        }

        const rows = ingredients.map(i => `
            <tr>
                <td class="ps-4"><span class="fw-bold">${esc(i.variant_name)}</span></td>
                <td class="text-end"><span class="fw-bold text-primary">${fmtNum(i.quantity_required, 2)}</span></td>
                <td class="text-end"><span class="badge badge-light-primary">${esc(i.unit)}</span></td>
            </tr>
        `).join('');

        content.innerHTML = `
            <div class="table-responsive">
                <table class="table table-row-bordered table-row-dashed gy-4 align-middle">
                    <thead>
                        <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                            <th class="ps-4">${esc(I18N.ingredient)}</th>
                            <th class="text-end">${esc(I18N.quantityRequired)}</th>
                            <th class="text-end">${esc(I18N.unit)}</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    // ─── Drawer 2: Serials ───────────────────────────────────
    function getSerialDrawer() {
        const el = document.getElementById('bpSerialDrawer');
        if (!el) return null;
        serialDrawerBs = serialDrawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return serialDrawerBs;
    }

    function openSerialDrawer(variantId, variantName) {
        const inst = getSerialDrawer();
        if (!inst) return;

        currentVariantId = variantId;
        $('bpSerialSubtitle').textContent = variantName;
        $('bpSerialSkeleton')?.classList.remove('d-none');
        $('bpSerialContent')?.classList.add('d-none');
        $('bpSerialBody').innerHTML = '';
        inst.show();
        refreshSerials();
    }

    async function refreshSerials() {
        if (!currentVariantId) return;
        $('bpSerialSkeleton')?.classList.remove('d-none');
        $('bpSerialContent')?.classList.add('d-none');

        try {
            const url = window.BP_ROUTES.serials.replace('__ID__', currentVariantId);
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            currentSerialsData = json.data;
            currentSerials = json.data.serials || [];
            renderSerialSummary(json.data.summary || {});
            filterSerials();
            $('bpSerialSkeleton')?.classList.add('d-none');
            $('bpSerialContent')?.classList.remove('d-none');
        } catch (err) {
            console.error('[serial-drawer] failed:', err);
            window.toastr?.error('Failed to load serials.');
            $('bpSerialSkeleton')?.classList.add('d-none');
        }
    }

    function renderSerialSummary(s) {
        $('bpTotalSerials').textContent    = fmtNum(s.total     || 0, 0);
        $('bpAvailableSerials').textContent= fmtNum(s.available || 0, 0);
        $('bpSoldSerials').textContent     = fmtNum(s.sold      || 0, 0);
        $('bpReservedSerials').textContent = fmtNum(s.reserved  || 0, 0);
        $('bpReturnedSerials').textContent = fmtNum(s.returned  || 0, 0);
        $('bpDamagedSerials').textContent  = fmtNum(s.damaged   || 0, 0);
    }

    function filterSerials() {
        const status = $('bpSerialStatusFilter')?.value || 'all';
        const search = ($('bpSerialSearchInput')?.value || '').toLowerCase().trim();

        let rows = currentSerials.slice();
        if (status !== 'all') rows = rows.filter(s => s.status === status);
        if (search) rows = rows.filter(s =>
            (s.serial_number || '').toLowerCase().includes(search) ||
            (s.location_name || '').toLowerCase().includes(search) ||
            (s.department_name || '').toLowerCase().includes(search)
        );
        renderSerialsTable(rows);
    }

    function renderSerialsTable(serials) {
        const empty = $('bpSerialEmpty');
        if (!serials || serials.length === 0) {
            $('bpSerialBody').innerHTML = '';
            empty?.classList.remove('d-none');
            return;
        }
        empty?.classList.add('d-none');

        const colors = { available:'success', sold:'danger', reserved:'warning', returned:'info', lost:'secondary', damaged:'dark' };
        const icons  = { available:'bi-check-circle', sold:'bi-cart-check', reserved:'bi-bookmark', returned:'bi-arrow-counterclockwise', lost:'bi-question-circle', damaged:'bi-exclamation-triangle' };

        $('bpSerialBody').innerHTML = serials.map((s, i) => {
            const color = colors[s.status] || 'secondary';
            const icon  = icons[s.status]  || 'bi-circle';

            const actions = [];
            if (s.status === 'available') {
                actions.push(`<button class="btn btn-sm btn-icon btn-light-success" data-serial-id="${s.id}" data-serial-status="sold" title="${esc(I18N.sold)}"><i class="bi bi-cart-check fs-5"></i></button>`);
                actions.push(`<button class="btn btn-sm btn-icon btn-light-warning" data-serial-id="${s.id}" data-serial-status="reserved" title="${esc(I18N.reserved)}"><i class="bi bi-bookmark fs-5"></i></button>`);
            }
            if (s.status === 'sold') {
                actions.push(`<button class="btn btn-sm btn-icon btn-light-info" data-serial-id="${s.id}" data-serial-status="returned" title="${esc(I18N.returned)}"><i class="bi bi-arrow-counterclockwise fs-5"></i></button>`);
            }
            if (s.status !== 'sold' && s.status !== 'damaged') {
                actions.push(`<button class="btn btn-sm btn-icon btn-light-danger" data-serial-delete="${s.id}" title="Delete"><i class="bi bi-trash fs-5"></i></button>`);
            }

            return `
                <tr>
                    <td>${i + 1}</td>
                    <td>
                        <span class="fw-bold">${esc(s.serial_number)}</span>
                        ${s.notes ? `<br><small class="text-muted">${esc(truncate(s.notes, 30))}</small>` : ''}
                    </td>
                    <td>
                        <span class="badge badge-light-${color}">
                            <i class="bi ${icon} me-1"></i>${esc(s.status_label || s.status)}
                        </span>
                    </td>
                    <td>${s.location_name && s.location_name !== 'N/A'
                        ? `<span class="badge badge-light-info">${esc(s.location_name)}</span>`
                        : '<span class="text-muted">—</span>'}</td>
                    <td>${s.department_name && s.department_name !== 'N/A'
                        ? `<span class="badge badge-light-primary">${esc(s.department_name)}</span>`
                        : '<span class="text-muted">—</span>'}</td>
                    <td>${s.order_id ? `<span class="badge badge-light-dark">#${s.order_id}</span>` : '<span class="text-muted">—</span>'}</td>
                    <td>${s.sold_at ? esc(formatDate(s.sold_at)) : '—'}</td>
                    <td class="text-end"><div class="d-flex gap-1 justify-content-end">${actions.join('')}</div></td>
                </tr>
            `;
        }).join('');
    }

    async function updateSerialStatus(serialId, status) {
        if (!confirm(`Change status to "${status}"?`)) return;
        try {
            const url = window.BP_ROUTES.serialStatus.replace('__ID__', serialId);
            const res = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ status }),
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');
            window.toastr?.success(json.message || 'Updated.');
            refreshSerials();
        } catch (err) {
            console.error('[serial-update] failed:', err);
            window.toastr?.error(err.message || 'Failed to update.');
        }
    }

    async function deleteSerial(serialId) {
        if (!confirm('Delete this serial?')) return;
        try {
            const url = window.BP_ROUTES.serialDelete.replace('__ID__', serialId);
            const res = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');
            window.toastr?.success(json.message || 'Deleted.');
            refreshSerials();
        } catch (err) {
            console.error('[serial-delete] failed:', err);
            window.toastr?.error(err.message || 'Failed to delete.');
        }
    }

    // ─── Export ──────────────────────────────────────────────
    function exportCsv() {
        const table = $('bpProductsTable');
        if (!table) { window.toastr?.error('Table not found.'); return; }
        try {
            const rows = table.querySelectorAll('tr');
            const csv = [];
            rows.forEach(row => {
                const cells = row.querySelectorAll('td, th');
                const line = Array.from(cells).map(c => {
                    let t = c.innerText.trim().replace(/[🥇🥈🥉🚀🚚⏰🏆📉]/g, '').trim();
                    if (t.includes(',')) t = '"' + t + '"';
                    return t;
                });
                csv.push(line.join(','));
            });
            const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'sales_by_product.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(link.href);
        } catch (e) {
            window.toastr?.error('Export failed: ' + e.message);
        }
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
    function truncate(s, len) {
        if (!s) return '';
        return s.length > len ? s.substring(0, len) + '…' : s;
    }
    function initials(name) {
        if (!name) return '?';
        return String(name).trim().split(/\s+/).slice(0, 2).map(p => p[0]).join('').toUpperCase();
    }
    function formatDate(d) {
        if (!d) return '—';
        const dt = new Date(d.includes('T') ? d : d + 'T00:00:00');
        if (isNaN(dt)) return d;
        return dt.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    }

    return { load };
})();
</script>