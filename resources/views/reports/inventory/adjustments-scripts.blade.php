<script>
window.AdjustmentsReport = (function () {
    'use strict';

    const ROUTE       = window.ADJ_ROUTES.data;
    const DETAIL_ROUTE= window.ADJ_ROUTES.detail;
    const CURRENCY    = window.REPORT_CURRENCY || '';
    const BOOT        = window.ADJ_BOOTSTRAP || {};

    const state = {
        startDate:    BOOT.startDate    || '',
        endDate:      BOOT.endDate      || '',
        departmentId: BOOT.departmentId || '',
        locationId:   BOOT.locationId   || '',
        variantId:    BOOT.variantId    || '',
        perPage:      Number(BOOT.perPage || 15),
        page:         1,
    };

    let typeChart  = null;
    let dailyChart = null;
    let inFlight   = false;
    let drawerBs   = null;

    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('adjApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('adjClearBtn')?.addEventListener('click', resetFilters);
        $('adjExportBtn')?.addEventListener('click', exportCsv);

        // Row click → open drawer
        $('adjTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr.adj-row[data-id]');
            if (!row) return;
            openDrawer(row.dataset.id);
        });

        // Clear drawer on close
        document.getElementById('adjDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('adjDrawerContent').innerHTML = '';
            $('adjDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate    = $('adjStartDate')?.value   || '';
        state.endDate      = $('adjEndDate')?.value     || '';
        state.variantId    = $('adjVariant')?.value     || '';
        state.locationId   = $('adjLocation')?.value    || '';
        state.departmentId = $('adjDepartment')?.value  || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.variantId = state.locationId = state.departmentId = '';
        state.page = 1;
        if ($('adjStartDate')) $('adjStartDate').value = '';
        if ($('adjEndDate'))   $('adjEndDate').value   = '';
        if (window.jQuery) jQuery('#adjVariant, #adjLocation, #adjDepartment').val(null).trigger('change.select2');
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
            console.error('[adjustments] load failed:', err);
            window.toastr?.error('Failed to load adjustments.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('adjSkeleton')?.classList.toggle('d-none', !v);
        $('adjContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderTypeChart(p.type_dist);
        renderDailyChart(p.daily_trend);
        renderTable(p.items, p.pagination);
    }

    function renderSummary(s) {
        $('sumTotal').textContent      = fmtNum(s.total_adjustments, 0);
        $('sumQtyChanged').textContent = fmtNum(s.total_quantity_changed, 0);
        $('sumIncrease').textContent   = fmtNum(s.increase_count, 0);
        $('sumDecrease').textContent   = fmtNum(s.decrease_count, 0);

        const net = Number(s.net_change);
        const cls = net > 0 ? 'success' : net < 0 ? 'danger' : 'secondary';
        $('sumNet').textContent = (net > 0 ? '+' : '') + fmtNum(net, 0);
        $('sumNet').className = `fs-1 fw-bold text-${cls}`;
        $('sumNetIcon').className = `bi bi-graph-up fs-2tx text-${cls}`;
        const card = $('sumNetCard');
        card.classList.remove('bg-light-success','bg-light-danger','bg-light-secondary','border-success','border-danger','border-secondary');
        card.classList.add(`bg-light-${cls}`, `border-${cls}`, 'border-dashed');
    }

    function renderTypeChart(rows) {
        const el = $('adjTypeChart');
        const empty = $('adjTypeEmpty');
        if (!el) return;
        const filtered = (rows || []).filter(r => Number(r.value) > 0);
        if (filtered.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (typeChart) { typeChart.destroy(); typeChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const colorMap = { success: '#50CD89', danger: '#F1416C', secondary: '#A8A8A8' };

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
                        formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0),
                    },
                },
            }}},
            tooltip: { y: { formatter: v => v + ' adjustments' } },
        };

        if (typeChart) typeChart.updateOptions(options, false, true);
        else { typeChart = new ApexCharts(el, options); typeChart.render(); }
    }

    function renderDailyChart(rows) {
        const el = $('adjDailyChart');
        const empty = $('adjDailyEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (dailyChart) { dailyChart.destroy(); dailyChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const categories = rows.map(r => {
            const d = new Date(r.date);
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });

        const options = {
            series: [{ name: 'Adjustments', data: rows.map(r => r.count), type: 'bar' }],
            chart:  { type: 'line', height: 320, toolbar: { show: true }, zoom: { enabled: true }, fontFamily: 'inherit' },
            plotOptions: { bar: { columnWidth: '60%', borderRadius: 4 } },
            xaxis: { categories, labels: { rotate: -45, style: { fontSize: '10px' } } },
            yaxis: { min: 0, tickAmount: 5, title: { text: 'Adjustments' } },
            colors: ['#3E97FF'],
            tooltip: {
                y: {
                    formatter: (val, { dataPointIndex }) => {
                        const item = rows[dataPointIndex];
                        let tip = `${val} adjustments`;
                        if (item && item.net !== 0) {
                            tip += `<br>Net: ${item.net >= 0 ? '+' : ''}${Math.abs(item.net)}`;
                        }
                        return tip;
                    },
                },
            },
        };

        if (dailyChart) dailyChart.updateOptions(options, false, true);
        else { dailyChart = new ApexCharts(el, options); dailyChart.render(); }
    }

    function renderTable(rows, pagination) {
        const body = $('adjTableBody');
        const empty = $('adjEmpty');
        const wrap = $('adjPaginationWrap');
        const badge = $('adjBadge');
        const footer = $('adjTableFooter');
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
            const change = Number(r.change);
            const changeCls = change > 0 ? 'success' : change < 0 ? 'danger' : 'info';
            const changeIcon = change > 0 ? 'bi-arrow-up' : change < 0 ? 'bi-arrow-down' : 'bi-dash';
            const changeSign = change > 0 ? '+' : '';
            const imgHtml = r.image_url
                ? `<div class="symbol symbol-40px me-2"><img src="${esc(r.image_url)}" class="rounded" alt="${esc(r.variant_name)}"></div>`
                : '';

            return `
                <tr class="adj-row" data-id="${r.id}">
                    <td class="ps-4">
                        <div class="fw-semibold">${esc((r.created_at || '').split(' ')[0])}</div>
                        <small class="text-muted">${esc((r.created_at || '').split(' ')[1] || '')}</small>
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
                    <td><span class="badge badge-light-secondary">${esc(r.sku)}</span></td>
                    <td><span class="badge badge-light-primary"><i class="bi bi-building fs-3 me-1"></i>${esc(r.department_name)}</span></td>
                    <td><span class="badge badge-light-info"><i class="bi bi-geo-alt fs-3 me-1"></i>${esc(r.location_name)}</span></td>
                    <td class="text-center"><span class="fw-bold text-gray-600">${fmtNum(r.quantity_before, 0)}</span></td>
                    <td class="text-center"><span class="fw-bold text-gray-600">${fmtNum(r.quantity_after, 0)}</span></td>
                    <td class="text-center">
                        <span class="fw-bold text-${changeCls}">
                            <i class="bi ${changeIcon} fs-3 me-1"></i>
                            ${changeSign}${fmtNum(Math.abs(change), 0)}
                        </span>
                    </td>
                    <td><span class="badge badge-light-dark">${esc(r.reason_label)}</span></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-30px symbol-circle me-2">
                                <div class="symbol-label bg-light-primary">
                                    <span class="fs-7 fw-bold text-primary">${esc((r.adjusted_by || 'U').charAt(0).toUpperCase())}</span>
                                </div>
                            </div>
                            <span class="text-muted">${esc(r.adjusted_by)}</span>
                        </div>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-icon btn-light-primary" title="View">
                            <i class="bi bi-eye fs-4"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        // Footer totals
        $('ftBefore').textContent = fmtNum(rows.reduce((s, r) => s + Number(r.quantity_before || 0), 0), 0);
        $('ftAfter').textContent  = fmtNum(rows.reduce((s, r) => s + Number(r.quantity_after  || 0), 0), 0);
        const net = rows.reduce((s, r) => s + Number(r.change || 0), 0);
        $('ftNet').innerHTML = `<span class="text-${net >= 0 ? 'success' : 'danger'}">${net >= 0 ? '+' : ''}${fmtNum(net, 0)}</span>`;

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('adjPaginationInfo');
        const ul   = $('adjPagination');
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

    // ─── Drawer ─────────────────────────────────────────────
    function getDrawer() {
        const el = document.getElementById('adjDrawer');
        if (!el) return null;
        drawerBs = drawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return drawerBs;
    }

    async function openDrawer(id) {
        const inst = getDrawer();
        if (!inst) return;

        // Show skeleton
        $('adjDrawerSkeleton')?.classList.remove('d-none');
        const content = $('adjDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        $('adjDrawerSubtitle').textContent = '';
        inst.show();

        try {
            const res = await fetch(DETAIL_ROUTE.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');

            renderDrawer(json.data);
        } catch (err) {
            console.error('[adj-drawer] load failed:', err);
            $('adjDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderDrawer(d) {
        $('adjDrawerSkeleton')?.classList.add('d-none');
        $('adjDrawerSubtitle').textContent = `#${d.id} · ${d.created_at || ''}`;

        const content = $('adjDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        const change = Number(d.change);
        const changeCls = change > 0 ? 'success' : change < 0 ? 'danger' : 'info';
        const changeSign = change > 0 ? '+' : '';
        const valueCls = Number(d.total_value_change) >= 0 ? 'success' : 'danger';

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">${esc(d.variant.name)}</h4>
                    <div class="text-muted fs-7">${esc(d.variant.sku)} · ${esc(d.variant.product_name)}</div>
                </div>
                <span class="badge badge-light-dark fs-6 py-2 px-3">${esc(d.reason_label)}</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-4">
                    <div class="card card-flush bg-light-secondary border border-secondary border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">Before</span>
                            <div class="fs-3 fw-bold">${fmtNum(d.quantity_before, 0)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card card-flush bg-light-${changeCls} border border-${changeCls} border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">Change</span>
                            <div class="fs-3 fw-bold text-${changeCls}">${changeSign}${fmtNum(Math.abs(change), 0)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card card-flush bg-light-primary border border-primary border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">After</span>
                            <div class="fs-3 fw-bold">${fmtNum(d.quantity_after, 0)}</div>
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold mb-3"><i class="bi bi-chat-left-text me-2 text-primary"></i>Reason &amp; Notes</h6>
            <div class="border rounded p-3 mb-4">
                <div class="mb-3">
                    <span class="text-muted fs-8 d-block mb-1">Reason</span>
                    <div class="fw-semibold">${esc(d.reason_label)}</div>
                </div>
                <div>
                    <span class="text-muted fs-8 d-block mb-1">Notes</span>
                    <div class="text-gray-800">${d.notes ? esc(d.notes) : '<em class="text-muted">No notes recorded</em>'}</div>
                </div>
            </div>

            <h6 class="fw-bold mb-3"><i class="bi bi-currency-dollar me-2 text-primary"></i>Pricing Snapshot</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <tbody>
                        <tr><th class="w-50">Unit cost</th><td class="text-end">${money(d.unit_cost_price)}</td></tr>
                        <tr><th>Unit selling price</th><td class="text-end">${money(d.unit_selling_price)}</td></tr>
                        <tr><th>Total value change</th><td class="text-end fw-bold text-${valueCls}">${money(d.total_value_change)}</td></tr>
                    </tbody>
                </table>
            </div>

            <h6 class="fw-bold mb-3 mt-4"><i class="bi bi-geo-alt me-2 text-primary"></i>Context</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <tbody>
                        <tr><th class="w-50">Department</th><td>${esc(d.department)}</td></tr>
                        <tr><th>Location</th><td>${esc(d.location)}</td></tr>
                        <tr><th>Adjusted by</th><td>${esc(d.adjusted_by)}</td></tr>
                        <tr><th>Date</th><td>${esc(d.created_at)}</td></tr>
                    </tbody>
                </table>
            </div>
        `;
    }

    function exportCsv() {
        const params = new URLSearchParams();
        if (state.startDate)    params.set('start_date',    state.startDate);
        if (state.endDate)      params.set('end_date',      state.endDate);
        if (state.variantId)    params.set('variant_id',    state.variantId);
        if (state.locationId)   params.set('location_id',   state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);
        window.location.href = `/reports/inventory/adjustments?${params.toString()}&export=1`;
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