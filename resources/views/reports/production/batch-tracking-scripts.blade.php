<script>
window.BatchTracking = (function () {
    'use strict';

    const ROUTE        = window.BT_ROUTES.data;
    const DETAIL_ROUTE = window.BT_ROUTES.detail;   // ← add this line
    const CURRENCY     = window.REPORT_CURRENCY || '';
    const BOOT         = window.BT_BOOTSTRAP || {};

    const state = {
        startDate: BOOT.startDate || '',
        endDate:   BOOT.endDate   || '',
        locationId: BOOT.locationId || '',
        variantId: BOOT.variantId  || '',
        batchType: BOOT.batchType  || 'all',
        search:    BOOT.search     || '',
        perPage:   Number(BOOT.perPage || 15),
        page:      1,
    };

    let trendChart   = null;
    let variantChart = null;
    let inFlight     = false;
    let searchTimer  = null;
    let drawerBs     = null; 

    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('btApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('btClearBtn')?.addEventListener('click', resetFilters);
        $('btExportBtn')?.addEventListener('click', exportCsv);
        $('btSearch')?.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                state.search = this.value.trim();
                state.page = 1;
                load();
            }, 400);
        });
        $('btLogsBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-batch-id]');
            if (!row) return;
            openDrawer(row.dataset.batchId);
        });

        document.getElementById('btDrawer')?.addEventListener('hidden.bs.offcanvas', () => {
            $('btDrawerContent').innerHTML = '';
            $('btDrawerSubtitle').textContent = '';
        });

        load();
    });

    function readFilters() {
        state.startDate  = $('btStartDate')?.value || '';
        state.endDate    = $('btEndDate')?.value   || '';
        state.locationId = $('btLocation')?.value  || '';
        state.variantId  = $('btVariant')?.value   || '';
        state.batchType  = $('btBatchType')?.value || 'all';
        state.search     = $('btSearch')?.value?.trim() || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.variantId = '';
        state.batchType = 'all';
        state.search = '';
        state.page = 1;
        ['btStartDate','btEndDate','btSearch'].forEach(id => { if ($(id)) $(id).value = ''; });
        if ($('btBatchType')) $('btBatchType').value = 'all';
        if (window.jQuery) jQuery('#btLocation, #btVariant').val(null).trigger('change.select2');
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)  params.set('start_date',  state.startDate);
        if (state.endDate)    params.set('end_date',    state.endDate);
        if (state.locationId) params.set('location_id', state.locationId);
        if (state.variantId)  params.set('variant_id',  state.variantId);
        if (state.batchType)  params.set('batch_type',  state.batchType);
        if (state.search)     params.set('search',      state.search);

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
            console.error('[batch-tracking] load failed:', err);
            window.toastr?.error('Failed to load batch tracking.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('btSkeleton')?.classList.toggle('d-none', !v);
        $('btContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.batchSummary);
        renderStatus(p.batchStatus, p.batchSummary);
        renderTrend(p.batchByMonth);
        renderVariantChart(p.batchByVariant);
        renderTop(p.topProduced, p.topConsumed);
        renderVariantTable(p.batchByVariant);
        renderMonthTable(p.batchByMonth);
        renderLogs(p.batchLogs, p.pagination, p.pageTotals);
    }

    function renderSummary(s) {
        $('btTotalLogs').textContent = fmtNum(s.total_batches, 0);
        $('btUniqueBatches').textContent = `${fmtNum(s.unique_batch_numbers, 0)} unique`;
        $('btProdKg').textContent = fmtNum(s.total_produced_kg, 0);
        $('btProdKgSub').textContent = `${fmtNum(s.total_produced_quantity, 0)} units · ${s.produced_batches} logs`;
        $('btConsKg').textContent = fmtNum(s.total_consumed_kg, 0);
        $('btConsKgSub').textContent = `${fmtNum(s.total_consumed_quantity, 0)} units · ${s.consumed_batches} logs`;

        const netCls = s.net_batch_kg >= 0 ? 'success' : 'warning';
        $('btNetKg').textContent = (s.net_batch_kg >= 0 ? '+' : '') + fmtNum(s.net_batch_kg, 0);
        $('btNetKg').className = `fs-2 fw-bold text-${netCls}`;
        applyColor('btNetCard', 'btNetIcon', netCls);

        $('btProdCost').textContent = money(s.total_produced_cost);
        $('btProdCostSub').textContent = `Avg: ${money(s.avg_unit_cost_per_kg)}/kg`;
        $('btConsCost').textContent = money(s.total_consumed_cost);
        $('btConsCostSub').textContent = `${s.unique_purchase_orders} POs`;
    }

    function renderStatus(st, s) {
        $('btActive').textContent   = st.active;
        $('btDepleted').textContent = st.depleted;
        $('btExpired').textContent  = st.expired;
        $('btSuppliers').textContent = s.unique_suppliers;
        $('btSuppliersSub').textContent = `${s.unique_variants} variants`;
    }

    function applyColor(cardId, iconId, color) {
        const card = $(cardId);
        if (card) {
            card.classList.remove('bg-light-success','bg-light-warning','bg-light-danger',
                                  'border-success','border-warning','border-danger');
            card.classList.add(`bg-light-${color}`, `border-${color}`, 'border-dashed');
        }
        const icon = $(iconId);
        if (icon) {
            icon.classList.remove('text-success','text-warning','text-danger');
            icon.classList.add('text-' + color);
        }
    }

    function renderTrend(rows) {
        const el = $('btTrendChart');
        const empty = $('btTrendEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (trendChart) { trendChart.destroy(); trendChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [
                { name: 'Produced (kg)', data: rows.map(r => r.produced_kg), type: 'bar'  },
                { name: 'Consumed (kg)', data: rows.map(r => r.consumed_kg), type: 'bar'  },
                { name: 'Net (kg)',      data: rows.map(r => r.net_kg),      type: 'line' },
            ],
            chart: { type: 'bar', height: 350, toolbar: { show: true }, stacked: false, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: false, columnWidth: '40%' } },
            stroke: { width: [0, 0, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: { categories: rows.map(r => r.month),
                     labels: { rotate: -45, trim: true, style: { fontSize: '11px' } } },
            yaxis: [
                { title: { text: 'Quantity (kg)' }, labels: { formatter: v => Number(v).toFixed(0) } },
                { opposite: true, title: { text: 'Net (kg)' }, labels: { formatter: v => Number(v).toFixed(0) } },
            ],
            colors: ['#50CD89', '#F1416C', '#3E97FF'],
            tooltip: { shared: true, intersect: false, y: { formatter: v => Number(v).toFixed(1) + ' kg' } },
            legend: { position: 'top', horizontalAlign: 'center' },
        };

        if (trendChart) trendChart.updateOptions(options, false, true);
        else { trendChart = new ApexCharts(el, options); trendChart.render(); }
    }

    function renderVariantChart(rows) {
        const el = $('btVariantChart');
        const empty = $('btVariantEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (variantChart) { variantChart.destroy(); variantChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');
        const top = rows.slice(0, 10);

        const options = {
            series: [
                { name: 'Produced (kg)', data: top.map(r => r.produced_kg), type: 'bar' },
                { name: 'Consumed (kg)', data: top.map(r => r.consumed_kg), type: 'bar' },
            ],
            chart: { type: 'bar', height: 350, toolbar: { show: true }, stacked: false, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: true, columnWidth: '50%' } },
            dataLabels: { enabled: false },
            xaxis: { categories: top.map(r => r.variant_name), labels: { style: { fontSize: '11px' } } },
            yaxis: { title: { text: 'Quantity (kg)' }, labels: { formatter: v => Number(v).toFixed(0) } },
            colors: ['#50CD89', '#F1416C'],
            tooltip: { shared: true, intersect: false, y: { formatter: v => Number(v).toFixed(1) + ' kg' } },
            legend: { position: 'bottom', horizontalAlign: 'center' },
        };

        if (variantChart) variantChart.updateOptions(options, false, true);
        else { variantChart = new ApexCharts(el, options); variantChart.render(); }
    }

    function renderTop(produced, consumed) {
        renderTopTable('btTopProdBody', 'btTopProdEmpty', produced, 'success');
        renderTopTable('btTopConsBody', 'btTopConsEmpty', consumed, 'danger');
    }

    function renderTopTable(bodyId, emptyId, rows, color) {
        const body = $(bodyId);
        const empty = $(emptyId);
        if (!body) return;
        if (!rows || rows.length === 0) {
            body.innerHTML = '';
            empty?.classList.remove('d-none');
            return;
        }
        empty?.classList.add('d-none');

        body.innerHTML = rows.map((r, i) => {
            const qty = Math.abs(r.quantity_change);
            const kg  = Math.abs(r.quantity_change_kg);
            return `
                <tr>
                    <td>${i + 1}</td>
                    <td><span class="badge badge-light-${color}">${esc(r.batch_number)}</span></td>
                    <td>
                        <div class="fw-bold">${esc(r.variant_name || 'N/A')}</div>
                        <div class="text-muted fs-8">
                            ${esc(r.variant_sku || '')}${r.weight > 0 ? ' · ' + fmtNum(r.weight, 2) + ' kg' : ''}
                        </div>
                    </td>
                    <td class="text-center ${color === 'danger' ? 'text-danger fw-bold' : ''}">${fmtNum(qty, 0)}</td>
                    <td class="text-center fw-bold text-${color}">${fmtNum(kg, 0)}</td>
                    <td class="text-center">${money(r.total_cost)}</td>
                </tr>
            `;
        }).join('');
    }

    function renderVariantTable(rows) {
        const card = $('btVariantCard');
        const body = $('btVariantBody');
        if (!body) return;
        if (!rows || rows.length === 0) { card?.classList.add('d-none'); return; }
        card?.classList.remove('d-none');
        $('btVariantCount').textContent = `${rows.length} variants`;

        body.innerHTML = rows.map(r => {
            const netCls = r.net_kg >= 0 ? 'success' : 'warning';
            const costCls = r.net_cost >= 0 ? 'success' : 'danger';
            return `
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-gray-800">${esc(r.variant_name)}</div>
                        ${r.variant_sku ? `<small class="text-muted">SKU: ${esc(r.variant_sku)}</small>` : ''}
                    </td>
                    <td class="text-center text-muted">${r.weight > 0 ? fmtNum(r.weight, 2) + ' kg' : '—'}</td>
                    <td class="text-center"><span class="badge badge-light-primary">${r.unique_batches}</span></td>
                    <td class="text-center"><span class="badge badge-light-success">${r.produced_count}</span></td>
                    <td class="text-center"><span class="badge badge-light-danger">${r.consumed_count}</span></td>
                    <td class="text-end fw-bold text-success">+${fmtNum(r.produced_kg, 0)}</td>
                    <td class="text-end fw-bold text-danger">-${fmtNum(r.consumed_kg, 0)}</td>
                    <td class="text-end fw-bold text-${netCls}">
                        ${r.net_kg >= 0 ? '+' : ''}${fmtNum(r.net_kg, 0)}
                    </td>
                    <td class="text-end text-muted">${money(r.avg_unit_cost_per_kg)}</td>
                    <td class="text-end pe-4 fw-bold text-${costCls}">${money(r.net_cost)}</td>
                </tr>
            `;
        }).join('');
    }

    function renderMonthTable(rows) {
        const card = $('btMonthCard');
        const body = $('btMonthBody');
        if (!body) return;
        if (!rows || rows.length === 0) { card?.classList.add('d-none'); return; }
        card?.classList.remove('d-none');

        body.innerHTML = rows.map(r => {
            const netCls = r.net_kg >= 0 ? 'success' : 'warning';
            return `
                <tr>
                    <td class="ps-4 fw-semibold">${esc(r.month)}</td>
                    <td class="text-center"><span class="badge badge-light-success">${r.produced_count}</span></td>
                    <td class="text-center"><span class="badge badge-light-danger">${r.consumed_count}</span></td>
                    <td class="text-end text-success fw-bold">+${fmtNum(r.produced_kg, 0)}</td>
                    <td class="text-end text-danger fw-bold">-${fmtNum(r.consumed_kg, 0)}</td>
                    <td class="text-end fw-bold text-${netCls}">
                        ${r.net_kg >= 0 ? '+' : ''}${fmtNum(r.net_kg, 0)}
                    </td>
                    <td class="text-end text-success">${money(r.produced_cost)}</td>
                    <td class="text-end pe-4 text-danger">${money(r.consumed_cost)}</td>
                </tr>
            `;
        }).join('');
    }

    function renderLogs(rows, pagination, totals) {
        const body = $('btLogsBody');
        const empty = $('btLogsEmpty');
        const wrap = $('btPaginationWrap');
        const badge = $('btLogsBadge');
        const footer = $('btLogsFooter');
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

        body.innerHTML = rows.map((r, i) => {
            const idx = (pagination.from || 1) + i;
            const isPos = r.quantity_change > 0;
            const isNeg = r.quantity_change < 0;
            const rowAccent = r.type === 'produced' ? 'success'
                            : r.type === 'depleted' ? 'danger'
                            : r.type === 'received' ? 'info' : 'secondary';

            const expired = r.expiry_date && new Date(r.expiry_date) < new Date();
            const expiringSoon = !expired && r.expiry_date
                && ((new Date(r.expiry_date) - new Date()) / (1000 * 60 * 60 * 24)) <= 30;

            let sourceHtml = '<span class="text-muted">-</span>';
            if (r.source_type === 'purchase_order') {
                sourceHtml = `<div class="d-flex flex-column">
                    <span class="badge badge-light-info badge-sm align-self-start">
                        <i class="ki-duotone ki-shop fs-5 me-1"></i>${esc(r.source_label)}
                    </span>
                    ${r.source_sub ? `<small class="text-muted mt-1">${esc(r.source_sub)}</small>` : ''}
                </div>`;
            } else if (r.source_type === 'production_order') {
                sourceHtml = `<div class="d-flex flex-column">
                    <span class="badge badge-light-primary badge-sm align-self-start">
                        <i class="ki-duotone ki-industry fs-5 me-1"></i>${esc(r.source_label)}
                    </span>
                    ${r.source_sub ? `<small class="text-muted mt-1">${esc(r.source_sub)}</small>` : ''}
                </div>`;
            }

            let expiryHtml = '<span class="text-muted">-</span>';
            if (r.expiry_date) {
                const cls = expired ? 'danger' : (expiringSoon ? 'warning' : 'success');
                expiryHtml = `
                    <span class="badge badge-light-${cls}">${esc(r.expiry_date)}</span>
                    ${expired ? '<small class="text-danger d-block mt-1">Expired</small>'
                              : expiringSoon ? '<small class="text-warning d-block mt-1">Expiring soon</small>' : ''}
                `;
            }

            const perfHtml = r.performed_by
                ? `<div class="d-flex align-items-center gap-2">
                        <div class="symbol symbol-25px symbol-circle">
                            <div class="symbol-label bg-light-primary">
                                <span class="fs-8 fw-bold text-primary">${esc(r.performed_by.charAt(0).toUpperCase())}</span>
                            </div>
                        </div>
                        <span class="text-gray-700 fs-7">${esc(r.performed_by)}</span>
                   </div>`
                : '<span class="text-muted">System</span>';

            const evtDate = r.event_date
                ? `<div class="fw-semibold text-gray-800">${esc(r.event_date.split(' ')[0])}</div>
                   <small class="text-muted">${esc(r.event_date.split(' ')[1] || '')}</small>`
                : '-';

            return `
                <tr data-batch-id="${r.id}" style="cursor:pointer">
                    <td class="ps-4 text-muted fw-semibold">${idx}</td>
                    <td>${evtDate}</td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-bold text-gray-800">${esc(r.variant_name || 'N/A')}</span>
                            ${r.variant_sku ? `<small class="text-muted">SKU: ${esc(r.variant_sku)}</small>` : ''}
                            ${r.weight > 0 ? `<small class="text-muted">${fmtNum(r.weight, 2)} kg/unit</small>` : ''}
                            <span class="badge badge-light-${rowAccent} badge-sm mt-1 align-self-start">${esc(r.batch_number)}</span>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-light-${esc(r.type_color)} d-inline-flex align-items-center gap-1">
                            <i class="ki-duotone ${esc(r.type_icon)} fs-5"></i>
                            ${esc(r.type_label)}
                        </span>
                    </td>
                    <td>${sourceHtml}</td>
                    <td class="text-center">
                        <div class="d-flex flex-column align-items-center">
                            <span class="fw-bold text-${isPos ? 'success' : (isNeg ? 'danger' : 'muted')}">
                                ${isPos ? '+' : ''}${fmtNum(r.quantity_change, 0)} <small class="text-muted">units</small>
                            </span>
                            <span class="text-${isPos ? 'success' : 'danger'} fs-7">
                                ${isPos ? '+' : ''}${fmtNum(r.quantity_change_kg, 0)} kg
                            </span>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-${r.quantity_after > 0 ? 'success' : 'danger'}">
                            ${fmtNum(r.quantity_after_kg, 0)} kg
                        </span>
                        ${r.weight > 0 && r.quantity_after != r.quantity_after_kg
                            ? `<div class="text-muted fs-8">(${fmtNum(r.quantity_after, 0)} units)</div>` : ''}
                    </td>
                    <td class="text-end">
                        <span class="text-gray-800">${money(r.unit_cost_per_kg)}</span>
                    </td>
                    <td class="text-end fw-bold">
                        <span class="text-${r.type === 'produced' ? 'success' : 'danger'}">${money(r.total_cost)}</span>
                    </td>
                    <td>${expiryHtml}</td>
                    <td class="pe-4">${perfHtml}</td>
                </tr>
            `;
        }).join('');

        // Footer totals for this page
        $('btLogsFooterMovement').innerHTML = `
            <span class="text-success">+${fmtNum(totals.positive_kg, 0)} kg</span>
            <span class="text-muted mx-1">/</span>
            <span class="text-danger">${fmtNum(totals.negative_kg, 0)} kg</span>
        `;
        $('btLogsFooterCost').textContent = money(totals.total_cost);

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('btPaginationInfo');
        const ul   = $('btPagination');
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
        if (state.startDate)  params.set('start_date',  state.startDate);
        if (state.endDate)    params.set('end_date',    state.endDate);
        if (state.locationId) params.set('location_id', state.locationId);
        if (state.variantId)  params.set('variant_id',  state.variantId);
        if (state.batchType)  params.set('batch_type',  state.batchType);
        if (state.search)     params.set('search',      state.search);
        window.location.href = `${window.BT_ROUTES.export}?${params.toString()}`;
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

    // ─── Drawer ──────────────────────────────────────────────
    function getDrawer() {
        const el = document.getElementById('btDrawer');
        if (!el) return null;
        drawerBs = drawerBs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return drawerBs;
    }

    async function openDrawer(id) {
        const inst = getDrawer();
        if (!inst) return;

        if (!DETAIL_ROUTE) {
            window.toastr?.error('Detail endpoint not configured.');
            return;
        }

        $('btDrawerSkeleton')?.classList.remove('d-none');
        const content = $('btDrawerContent');
        if (content) { content.classList.add('d-none'); content.innerHTML = ''; }
        $('btDrawerSubtitle').textContent = '';
        inst.show();

        try {
            const res = await fetch(DETAIL_ROUTE.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            renderDrawer(json.data);
        } catch (err) {
            console.error('[bt-drawer] load failed:', err);
            $('btDrawerSkeleton')?.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Failed to load')}</div>`;
            }
        }
    }

    function renderDrawer(d) {
        $('btDrawerSkeleton')?.classList.add('d-none');
        $('btDrawerSubtitle').textContent = `${d.batch_number || '#' + d.id} · ${d.event_date || ''}`;

        const content = $('btDrawerContent');
        if (!content) return;
        content.classList.remove('d-none');

        const changeCls = d.quantity_change > 0 ? 'success' : d.quantity_change < 0 ? 'danger' : 'secondary';
        const changeSign = d.quantity_change > 0 ? '+' : '';
        const profitCls = d.gross_profit >= 0 ? 'success' : 'danger';

        const imgHtml = d.variant.image_url
            ? `<div class="symbol symbol-60px me-3"><img src="${esc(d.variant.image_url)}" class="rounded" alt="${esc(d.variant.name)}"></div>`
            : '';

        // Reference links
        let referenceHtml = '<em class="text-muted">No reference</em>';
        const refParts = [];
        if (d.purchase_order_number || d.purchase_order_id) {
            refParts.push(`<span class="badge badge-light-info"><i class="bi bi-shop me-1"></i>PO: ${esc(d.purchase_order_number || '#' + d.purchase_order_id)}</span>`);
        }
        if (d.supplier_name) {
            refParts.push(`<span class="badge badge-light-primary"><i class="bi bi-person me-1"></i>${esc(d.supplier_name)}</span>`);
        }
        if (d.purchase_receipt_id) {
            refParts.push(`<span class="badge badge-light-dark">Receipt #${d.purchase_receipt_id}</span>`);
        }
        if (d.production_number || d.production_order_id) {
            refParts.push(`<span class="badge badge-light-primary"><i class="bi bi-gear me-1"></i>${esc(d.production_number || 'PRD-#' + d.production_order_id)}</span>`);
        }
        if (refParts.length > 0) referenceHtml = `<div class="d-flex flex-wrap gap-2">${refParts.join('')}</div>`;

        // Metadata rendering
        let metadataHtml = '<em class="text-muted">No metadata</em>';
        if (d.metadata && Object.keys(d.metadata).length > 0) {
            const rows = Object.entries(d.metadata).map(([k, v]) => {
                const val = typeof v === 'object' ? JSON.stringify(v) : String(v ?? '—');
                return `<tr><th class="text-muted w-40">${esc(k)}</th><td class="text-break">${esc(val)}</td></tr>`;
            }).join('');
            metadataHtml = `<table class="table table-sm table-bordered mb-0"><tbody>${rows}</tbody></table>`;
        }

        // Expiry
        let expiryHtml = '<em class="text-muted">No expiry date</em>';
        if (d.expiry_date) {
            const cls = d.is_expired ? 'danger' : (d.days_to_expiry !== null && d.days_to_expiry <= 30 ? 'warning' : 'success');
            const label = d.is_expired ? 'Expired' : (d.days_to_expiry !== null && d.days_to_expiry <= 30 ? 'Expiring soon' : 'Valid');
            expiryHtml = `
                <div class="d-flex align-items-center gap-3">
                    <span class="badge badge-light-${cls} fs-6 py-2 px-3">${esc(d.expiry_date)}</span>
                    <span class="text-${cls} fw-semibold">${label}</span>
                    ${d.days_to_expiry !== null && !d.is_expired
                        ? `<span class="text-muted fs-7">(${d.days_to_expiry} days left)</span>`
                        : ''}
                </div>
            `;
        }

        content.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center">
                    ${imgHtml}
                    <div>
                        <h4 class="fw-bold mb-1">${esc(d.variant.name)}</h4>
                        <div class="text-muted fs-7">${esc(d.variant.sku)} · ${esc(d.variant.product_name)}</div>
                        ${d.variant.weight > 0 ? `<div class="text-muted fs-8">${fmtNum(d.variant.weight, 2)} kg / unit</div>` : ''}
                    </div>
                </div>
                <span class="badge badge-light-${d.type_color} fs-6 py-2 px-3">${esc(d.type_label)}</span>
            </div>

            {{-- Quantity cards --}}
            <div class="row g-3 mb-4">
                <div class="col-4">
                    <div class="card card-flush bg-light-secondary border border-secondary border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">Before</span>
                            <div class="fs-4 fw-bold">${fmtNum(d.quantity_before, 0)}</div>
                            <div class="text-muted fs-8">${fmtNum(d.quantity_before_kg, 0)} kg</div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card card-flush bg-light-${changeCls} border border-${changeCls} border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">Change</span>
                            <div class="fs-4 fw-bold text-${changeCls}">${changeSign}${fmtNum(d.quantity_change, 0)}</div>
                            <div class="text-${changeCls} fs-8">${changeSign}${fmtNum(d.quantity_change_kg, 0)} kg</div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card card-flush bg-light-primary border border-primary border-dashed">
                        <div class="card-body text-center py-4">
                            <span class="text-muted fs-8">After</span>
                            <div class="fs-4 fw-bold">${fmtNum(d.quantity_after, 0)}</div>
                            <div class="text-muted fs-8">${fmtNum(d.quantity_after_kg, 0)} kg</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pricing snapshot --}}
            <h6 class="fw-bold mb-3"><i class="bi bi-currency-dollar me-2 text-primary"></i>Pricing Snapshot (Frozen at Event)</h6>
            <div class="table-responsive mb-4">
                <table class="table table-sm table-bordered">
                    <tbody>
                        <tr><th class="w-50">Unit cost</th><td class="text-end">${money(d.unit_cost)}</td></tr>
                        <tr><th>Unit cost / kg</th><td class="text-end">${money(d.unit_cost_per_kg)}</td></tr>
                        <tr><th>Unit selling price</th><td class="text-end">${money(d.unit_selling_price)}</td></tr>
                        <tr><th>Total cost</th><td class="text-end">${money(d.total_cost)}</td></tr>
                        <tr><th>Total selling value</th><td class="text-end">${money(d.total_selling_value)}</td></tr>
                        <tr class="bg-light"><th>Gross profit</th><td class="text-end fw-bold text-${profitCls}">${money(d.gross_profit)}</td></tr>
                        ${d.pricing_source ? `<tr><th>Pricing source</th><td class="text-end"><span class="badge badge-light-dark">${esc(d.pricing_source)}</span></td></tr>` : ''}
                    </tbody>
                </table>
            </div>

            {{-- Reference --}}
            <h6 class="fw-bold mb-3"><i class="bi bi-link-45deg me-2 text-primary"></i>Reference</h6>
            <div class="border rounded p-3 mb-4">${referenceHtml}</div>

            {{-- Expiry --}}
            <h6 class="fw-bold mb-3"><i class="bi bi-calendar-x me-2 text-primary"></i>Expiry</h6>
            <div class="border rounded p-3 mb-4">${expiryHtml}</div>

            {{-- Metadata --}}
            <h6 class="fw-bold mb-3"><i class="bi bi-code-square me-2 text-primary"></i>Metadata</h6>
            <div class="mb-4">${metadataHtml}</div>

            {{-- Context --}}
            <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt me-2 text-primary"></i>Context</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <tbody>
                        <tr><th class="w-50">Batch number</th><td>${esc(d.batch_number || '—')}</td></tr>
                        <tr><th>Event date</th><td>${esc(d.event_date || '—')}</td></tr>
                        <tr><th>Performed by</th><td>${esc(d.performed_by)}</td></tr>
                        <tr><th>Location ID</th><td>${d.location_id ?? '—'}</td></tr>
                        <tr><th>Department ID</th><td>${d.department_id ?? '—'}</td></tr>
                    </tbody>
                </table>
            </div>
        `;
    }

    return { load };
})();
</script>