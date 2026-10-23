<script>
window.QualityAnalysis = (function () {
    'use strict';

    const ROUTE    = window.Q_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.Q_BOOTSTRAP || {};

    const state = {
        startDate:     BOOT.startDate     || '',
        endDate:       BOOT.endDate       || '',
        locationId:    BOOT.locationId    || '',
        variantId:     BOOT.variantId     || '',
        status:        BOOT.status        || 'completed',
        qualityStatus: BOOT.qualityStatus || 'all',
        perPage:       Number(BOOT.perPage || 15),
        page:          1,
    };

    let trendChart    = null;
    let categoryChart = null;
    let inFlight      = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('qApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('qClearBtn')?.addEventListener('click', resetFilters);
        $('exportQualityBtn')?.addEventListener('click', exportCsv);
        load();
    });

    function readFilters() {
        state.startDate     = $('qStartDate')?.value || '';
        state.endDate       = $('qEndDate')?.value   || '';
        state.locationId    = $('qLocation')?.value  || '';
        state.status        = $('qStatus')?.value    || 'completed';
        state.qualityStatus = $('qQuality')?.value   || 'all';
        state.variantId     = $('qVariant')?.value   || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.locationId = state.variantId = '';
        state.status = 'completed';
        state.qualityStatus = 'all';
        state.page = 1;
        ['qStartDate','qEndDate'].forEach(id => { if ($(id)) $(id).value = ''; });
        if ($('qStatus'))  $('qStatus').value  = 'completed';
        if ($('qQuality')) $('qQuality').value = 'all';
        if (window.jQuery) jQuery('#qLocation, #qVariant').val(null).trigger('change.select2');
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)     params.set('start_date',     state.startDate);
        if (state.endDate)       params.set('end_date',       state.endDate);
        if (state.locationId)    params.set('location_id',    state.locationId);
        if (state.variantId)     params.set('variant_id',     state.variantId);
        if (state.status)        params.set('status',         state.status);
        if (state.qualityStatus) params.set('quality_status', state.qualityStatus);

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
            console.error('[quality-analysis] load failed:', err);
            window.toastr?.error('Failed to load quality analysis.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('qSkeleton')?.classList.toggle('d-none', !v);
        $('qContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.qualitySummary);
        renderTrend(p.monthlyQuality);
        renderCategory(p.categoryQualitySummary);
        renderTopProducts(p.productQualitySummary);
        renderOrders(p.qualityByOrder, p.pagination);
    }

    function renderSummary(s) {
        const color = s.quality_color || 'secondary';
        const card  = $('overallCard');
        if (card) {
            card.classList.remove('bg-light-success','bg-light-info','bg-light-warning','bg-light-danger','bg-light-dark',
                                  'border-success','border-info','border-warning','border-danger','border-dark');
            card.classList.add(`bg-light-${color}`, `border-${color}`, 'border-dashed');
        }
        const badge = $('overallBadge');
        if (badge) {
            badge.textContent = fmtNum(s.overall_quality_score, 1) + '%';
            badge.className = `symbol-label bg-${color} text-white fs-1 fw-bold`;
        }
        $('overallSubtitle').textContent =
            `${s.quality_rating} · ${s.total_orders} orders analyzed · KG-weighted`;

        $('overallInAcc').textContent = fmtNum(s.input_acceptance_rate, 1) + '%';
        $('overallInAccSub').textContent = `${fmtNum(s.accepted_input_kg, 0)} / ${fmtNum(s.total_input_kg, 0)} kg`;
        $('overallOutApp').textContent = fmtNum(s.output_approval_rate, 1) + '%';
        $('overallOutAppSub').textContent = `${fmtNum(s.approved_output_kg, 0)} / ${fmtNum(s.total_output_kg, 0)} kg`;
        $('overallDef').textContent = fmtNum(s.defective_rate, 1) + '%';
        $('overallDefSub').textContent = `${fmtNum(s.total_defective_kg, 0)} kg`;
        $('overallWaste').textContent = fmtNum(s.waste_rate, 1) + '%';
        $('overallWasteSub').textContent = `${fmtNum(s.total_input_waste_kg, 0)} kg`;

        $('bdInAcc').textContent  = fmtNum(s.accepted_inputs, 0);
        $('bdInRej').textContent  = fmtNum(s.rejected_inputs, 0);
        $('bdInPend').textContent = fmtNum(s.pending_inputs, 0);
        $('bdInKg').textContent   = `${fmtNum(s.total_input_kg, 0)} kg total`;

        $('bdOutAcc').textContent  = fmtNum(s.approved_outputs, 0);
        $('bdOutRej').textContent  = fmtNum(s.rejected_outputs, 0);
        $('bdOutPend').textContent = fmtNum(s.pending_outputs, 0);
        $('bdOutKg').textContent   = `${fmtNum(s.total_output_kg, 0)} kg total`;

        $('bdDefKg').textContent  = fmtNum(s.total_defective_kg, 0);
        $('bdWasteKg').textContent = fmtNum(s.total_input_waste_kg, 0);
        $('bdLossSub').textContent = `${fmtNum(s.total_defective_kg + s.total_input_waste_kg, 0)} kg combined`;

        $('bdInTotKg').textContent  = fmtNum(s.total_input_kg, 0);
        $('bdOutTotKg').textContent = fmtNum(s.total_output_kg, 0);
        const massLoss = Math.max(0, s.total_input_kg - s.total_output_kg);
        $('bdMassLoss').textContent = `Loss: ${fmtNum(massLoss, 0)} kg`;
    }

    function renderTrend(rows) {
        const el = $('qualityTrendChart');
        const empty = $('qualityTrendEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (trendChart) { trendChart.destroy(); trendChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [
                { name: 'Input Acceptance', data: rows.map(r => r.input_acceptance), type: 'line' },
                { name: 'Output Approval',  data: rows.map(r => r.output_approval),  type: 'line' },
                { name: 'Overall Score',    data: rows.map(r => r.overall_score),    type: 'line' },
            ],
            chart: { type: 'line', height: 350, toolbar: { show: true }, zoom: { enabled: true }, fontFamily: 'inherit' },
            stroke: { width: [3, 3, 4], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: { categories: rows.map(r => r.month),
                     labels: { rotate: -45, trim: true, style: { fontSize: '11px' } } },
            yaxis: { title: { text: 'Percentage (%)' }, min: 0, max: 100,
                     labels: { formatter: v => Number(v).toFixed(0) + '%' } },
            colors: ['#F1416C', '#50CD89', '#3E97FF'],
            tooltip: { shared: true, intersect: false,
                       y: { formatter: v => Number(v).toFixed(1) + '%' } },
            legend: { position: 'top', horizontalAlign: 'center' },
        };

        if (trendChart) trendChart.updateOptions(options, false, true);
        else { trendChart = new ApexCharts(el, options); trendChart.render(); }
    }

    function renderCategory(rows) {
        const el = $('categoryQualityChart');
        const empty = $('categoryQualityEmpty');
        if (!el) return;
        if (!rows || rows.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (categoryChart) { categoryChart.destroy(); categoryChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [
                { name: 'Defective Rate', data: rows.map(r => r.defective_rate), type: 'bar'  },
                { name: 'Approval Rate',  data: rows.map(r => r.approval_rate),  type: 'bar'  },
                { name: 'Quality Score',  data: rows.map(r => r.quality_score),  type: 'line' },
            ],
            chart: { type: 'bar', height: 350, toolbar: { show: true }, stacked: false, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: true, columnWidth: '50%' } },
            stroke: { width: [0, 0, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: { categories: rows.map(r => r.category),
                     labels: { style: { fontSize: '11px' } } },
            yaxis: { title: { text: 'Percentage (%)' },
                     labels: { formatter: v => Number(v).toFixed(0) + '%' } },
            colors: ['#F1416C', '#50CD89', '#3E97FF'],
            tooltip: { shared: true, intersect: false,
                       y: { formatter: v => Number(v).toFixed(1) + '%' } },
            legend: { position: 'bottom', horizontalAlign: 'center' },
        };

        if (categoryChart) categoryChart.updateOptions(options, false, true);
        else { categoryChart = new ApexCharts(el, options); categoryChart.render(); }
    }

    function renderTopProducts(rows) {
        const card = $('topQualityCard');
        const body = $('topQualityBody');
        if (!body) return;
        if (!rows || rows.length === 0) { card?.classList.add('d-none'); return; }
        card?.classList.remove('d-none');

        body.innerHTML = rows.map((r, i) => {
            const scoreColor = r.quality_score >= 90 ? 'success'
                             : r.quality_score >= 75 ? 'info'
                             : r.quality_score >= 60 ? 'warning' : 'danger';
            const defColor = r.defective_rate <= 5 ? 'success'
                           : r.defective_rate <= 15 ? 'warning' : 'danger';
            return `
                <tr>
                    <td>${i + 1}</td>
                    <td>
                        <div class="fw-bold">${esc(r.variant_name)}</div>
                        <div class="text-muted fs-8">
                            ${esc(r.variant_sku || '')}${r.weight > 0 ? ' · ' + fmtNum(r.weight, 2) + ' kg/bag' : ''}
                        </div>
                    </td>
                    <td><span class="badge badge-light-primary">${esc(r.category)}</span></td>
                    <td class="text-center">${fmtNum(r.total_quantity, 0)}</td>
                    <td class="text-center fw-bold">${fmtNum(r.total_kg, 0)}</td>
                    <td class="text-center text-danger">${fmtNum(r.defective_kg, 0)}</td>
                    <td class="text-center"><span class="badge badge-light-${defColor}">${fmtNum(r.defective_rate, 1)}%</span></td>
                    <td class="text-center"><span class="badge badge-light-${scoreColor}">${fmtNum(r.quality_score, 1)}%</span></td>
                    <td class="text-center">${r.order_count}</td>
                </tr>
            `;
        }).join('');
    }

    function renderOrders(rows, pagination) {
        const body = $('qOrderBody');
        const empty = $('qOrderEmpty');
        const wrap = $('qPaginationWrap');
        const badge = $('qOrderBadge');
        if (!body) return;

        if (!rows || rows.length === 0) {
            body.innerHTML = ''; empty?.classList.remove('d-none'); wrap?.classList.add('d-none');
            if (badge) badge.textContent = '';
            return;
        }
        empty?.classList.add('d-none');
        wrap?.classList.remove('d-none');
        if (badge) badge.textContent = `Showing ${pagination.from} of ${pagination.total}`;

        body.innerHTML = rows.map((r, i) => {
            const idx = (pagination.from || 1) + i;
            const inAccColor = r.input_acceptance_rate >= 90 ? 'success' : r.input_acceptance_rate >= 70 ? 'warning' : 'danger';
            const outAppColor = r.output_approval_rate >= 90 ? 'success' : r.output_approval_rate >= 70 ? 'warning' : 'danger';
            const defColor = r.defective_rate <= 5 ? 'success' : r.defective_rate <= 15 ? 'warning' : 'danger';
            const wasteColor = r.waste_rate <= 5 ? 'success' : r.waste_rate <= 15 ? 'warning' : 'danger';
            return `
                <tr>
                    <td>${idx}</td>
                    <td>
                        <div class="fw-bold">${esc(r.production_number)}</div>
                        <div class="text-muted fs-8">${esc(r.created_at)}</div>
                    </td>
                    <td><span class="badge badge-light-${esc(r.status_badge)}">${esc(r.status)}</span></td>
                    <td>${esc(r.location)}</td>
                    <td class="text-center">${fmtNum(r.input_kg_total, 0)}</td>
                    <td class="text-center">${fmtNum(r.output_kg_total, 0)}</td>
                    <td class="text-center"><span class="badge badge-light-${inAccColor}">${fmtNum(r.input_acceptance_rate, 1)}%</span></td>
                    <td class="text-center"><span class="badge badge-light-${outAppColor}">${fmtNum(r.output_approval_rate, 1)}%</span></td>
                    <td class="text-center"><span class="badge badge-light-${defColor}">${fmtNum(r.defective_rate, 1)}%</span></td>
                    <td class="text-center"><span class="badge badge-light-${wasteColor}">${fmtNum(r.waste_rate, 1)}%</span></td>
                    <td class="text-center"><span class="badge badge-light-${esc(r.quality_color)}">${fmtNum(r.overall_quality_score, 1)}%</span></td>
                    <td class="text-center"><span class="badge badge-light-${esc(r.quality_color)}">${esc(r.quality_rating)}</span></td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('qPaginationInfo');
        const ul   = $('qPagination');
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
        if (state.startDate)     params.set('start_date',     state.startDate);
        if (state.endDate)       params.set('end_date',       state.endDate);
        if (state.locationId)    params.set('location_id',    state.locationId);
        if (state.variantId)     params.set('variant_id',     state.variantId);
        if (state.status)        params.set('status',         state.status);
        if (state.qualityStatus) params.set('quality_status', state.qualityStatus);
        window.location.href = `${window.Q_ROUTES.export}?${params.toString()}`;
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