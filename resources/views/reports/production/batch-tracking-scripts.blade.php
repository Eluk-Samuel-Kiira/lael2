<script>
window.BatchTracking = (function () {
    'use strict';

    const ROUTE    = window.BT_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.BT_BOOTSTRAP || {};

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
                <tr>
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

    return { load };
})();
</script>