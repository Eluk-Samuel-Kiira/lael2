<script>
window.MovementAnalysis = (function () {
    'use strict';

    const ROUTE    = window.MOV_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.MOV_BOOTSTRAP || {};

    const state = {
        startDate:    BOOT.startDate    || '',
        endDate:      BOOT.endDate      || '',
        movementType: BOOT.movementType || 'all',
        departmentId: BOOT.departmentId || '',
        locationId:   BOOT.locationId   || '',
        variantId:    BOOT.variantId    || '',
        perPage:      Number(BOOT.perPage || 15),
        page:         1,
    };

    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    const CAT_COLORS = {
        'fast':       'success',
        'slow':       'warning',
        'non-moving': 'danger',
    };
    const CAT_ICONS = {
        'fast':       'bi-rocket-takeoff',
        'slow':       'bi-speedometer2',
        'non-moving': 'bi-pause-circle',
    };

    document.addEventListener('DOMContentLoaded', () => {
        $('movApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('movClearBtn')?.addEventListener('click', resetFilters);
        $('movExportBtn')?.addEventListener('click', exportCsv);
        load();
    });

    function readFilters() {
        state.startDate    = $('movStartDate')?.value   || '';
        state.endDate      = $('movEndDate')?.value     || '';
        state.movementType = $('movType')?.value        || 'all';
        state.variantId    = $('movVariant')?.value     || '';
        state.locationId   = $('movLocation')?.value    || '';
        state.departmentId = $('movDepartment')?.value  || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.variantId = state.locationId = state.departmentId = '';
        state.movementType = 'all';
        state.page = 1;
        if ($('movStartDate')) $('movStartDate').value = '';
        if ($('movEndDate'))   $('movEndDate').value   = '';
        if ($('movType'))      $('movType').value      = 'all';
        if (window.jQuery) jQuery('#movVariant, #movLocation, #movDepartment').val(null).trigger('change.select2');
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)    params.set('start_date',    state.startDate);
        if (state.endDate)      params.set('end_date',      state.endDate);
        if (state.movementType) params.set('movement_type', state.movementType);
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
            console.error('[movement] load failed:', err);
            window.toastr?.error('Failed to load movement analysis.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('movSkeleton')?.classList.toggle('d-none', !v);
        $('movContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderStats(p.stats, p.meta, p.pagination);
        renderTable(p.items, p.pagination);
    }

    function renderStats(s, meta, pag) {
        $('statFast').textContent  = fmtNum(s.fast_moving, 0);
        $('statSlow').textContent  = fmtNum(s.slow_moving, 0);
        $('statNon').textContent   = fmtNum(s.non_moving, 0);
        $('statTotal').textContent = fmtNum(s.total_items, 0);
        $('statPeriod').textContent = `${meta.start_date} → ${meta.end_date}`;
    }

    function renderTable(rows, pagination) {
        const body = $('movTableBody');
        const empty = $('movEmpty');
        const wrap = $('movPaginationWrap');
        const badge = $('movBadge');
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
            const catColor = CAT_COLORS[r.movement_category] || 'secondary';
            const catIcon  = CAT_ICONS[r.movement_category]  || 'bi-circle';
            const imgHtml = r.image_url
                ? `<div class="symbol symbol-40px me-2"><img src="${esc(r.image_url)}" class="rounded" alt="${esc(r.variant_name)}"></div>`
                : '';

            const daysSince = Number(r.days_since_last_movement);
            const daysCls = daysSince > 90 ? 'danger' : daysSince > 30 ? 'warning' : 'success';
            const progressWidth = Math.min(100, (Number(r.avg_daily_movement) / 20) * 100);

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
                    <td class="text-center">
                        <span class="fw-bold text-primary">${fmtNum(r.total_movement, 0)}</span>
                        <div class="progress mt-1 mx-auto" style="height:4px; width:60px;">
                            <div class="progress-bar bg-${catColor}" style="width:${progressWidth}%"></div>
                        </div>
                    </td>
                    <td class="text-center"><span class="badge badge-light-info">${fmtNum(r.transaction_count, 0)}</span></td>
                    <td class="text-center"><span class="fw-bold">${fmtNum(r.avg_daily_movement, 1)}</span></td>
                    <td><span class="text-muted">${esc(r.first_movement || '-')}</span></td>
                    <td><span class="text-muted">${esc(r.last_movement || '-')}</span></td>
                    <td class="text-center">
                        <span class="fw-bold text-${daysCls}">
                            ${fmtNum(r.days_since_last_movement, 0)}
                            <small class="d-block text-muted">days</small>
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-${catColor} py-2 px-3">
                            <i class="bi ${catIcon} me-1"></i>${esc(r.movement_label)}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('movPaginationInfo');
        const ul   = $('movPagination');
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
        if (state.movementType) params.set('movement_type', state.movementType);
        if (state.variantId)    params.set('variant_id',    state.variantId);
        if (state.locationId)   params.set('location_id',   state.locationId);
        if (state.departmentId) params.set('department_id', state.departmentId);
        window.location.href = `/reports/inventory/movement-analysis?${params.toString()}&export=1`;
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