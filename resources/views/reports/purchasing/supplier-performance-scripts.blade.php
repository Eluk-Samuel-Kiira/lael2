<script>
window.SupplierPerformance = (function () {
    'use strict';

    const ROUTE    = window.SP_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.SP_BOOTSTRAP || {};

    const state = {
        startDate:  BOOT.startDate  || '',
        endDate:    BOOT.endDate    || '',
        supplierId: BOOT.supplierId || '',
        perPage:    Number(BOOT.perPage || 15),
        page:       1,
    };

    let abcChart      = null;
    let topChart      = null;
    let deliveryChart = null;
    let valueChart    = null;
    let inFlight      = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('spApplyBtn')?.addEventListener('click', () => { readFilters(); state.page = 1; load(); });
        $('spClearBtn')?.addEventListener('click', resetFilters);
        $('spExportBtn')?.addEventListener('click', exportCsv);
        load();
    });

    function readFilters() {
        state.startDate  = $('spStartDate')?.value  || '';
        state.endDate    = $('spEndDate')?.value    || '';
        state.supplierId = $('spSupplier')?.value   || '';
    }

    function resetFilters() {
        state.startDate = state.endDate = state.supplierId = '';
        state.page = 1;
        if ($('spStartDate')) $('spStartDate').value = '';
        if ($('spEndDate'))   $('spEndDate').value   = '';
        if (window.jQuery) jQuery('#spSupplier').val(null).trigger('change.select2');
        load();
    }

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.startDate)  params.set('start_date',  state.startDate);
        if (state.endDate)    params.set('end_date',    state.endDate);
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
            console.error('[supplier-performance] load failed:', err);
            window.toastr?.error('Failed to load supplier performance.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('spSkeleton')?.classList.toggle('d-none', !v);
        $('spContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderAbcChart(p.abc_counts, p.pagination);
        renderTopChart(p.top_supplier_names, p.top_supplier_spends);
        renderDeliveryChart(p.delivery_ranges);
        renderValueChart(p.order_value_ranges);
        renderTable(p.suppliers, p.pagination);
    }

    function renderSummary(s) {
        $('sumTotalSuppliers').textContent = fmtNum(s.total_suppliers, 0);
        $('sumTotalSpent').textContent     = money(s.total_spent);
        $('sumAvgOrder').textContent       = money(s.avg_order_value);

        if (s.top_supplier) {
            $('sumTopSupplier').textContent      = s.top_supplier.name;
            $('sumTopSupplierSpent').textContent = money(s.top_supplier.total_spent);
        } else {
            $('sumTopSupplier').textContent      = '—';
            $('sumTopSupplierSpent').textContent = '';
        }
    }

    function renderAbcChart(counts, pagination) {
        const el = $('spAbcChart');
        const empty = $('spAbcEmpty');
        if (!el) return;

        const series = [
            Number(counts.A || 0),
            Number(counts.B || 0),
            Number(counts.C || 0),
        ];
        const total = series.reduce((a, b) => a + b, 0);

        if (total === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (abcChart) { abcChart.destroy(); abcChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series,
            chart:  { type: 'donut', height: 320, fontFamily: 'inherit' },
            labels: ['A (Strategic)', 'B (Tactical)', 'C (Transactional)'],
            colors: ['#F1416C', '#FFC700', '#50CD89'],
            legend: { position: 'bottom', fontSize: '12px' },
            plotOptions: { pie: { donut: {
                size: '65%',
                labels: {
                    show: true,
                    total: {
                        show: true, label: 'Suppliers',
                        formatter: () => total,
                    },
                },
            }}},
            tooltip: { y: { formatter: v => v + ' suppliers' } },
        };

        if (abcChart) abcChart.updateOptions(options, false, true);
        else { abcChart = new ApexCharts(el, options); abcChart.render(); }
    }

    function renderTopChart(names, spends) {
        const el = $('spTopChart');
        const empty = $('spTopEmpty');
        if (!el) return;

        if (!names || names.length === 0) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (topChart) { topChart.destroy(); topChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: [{ name: 'Spent', data: spends.map(Number) }],
            chart:  { type: 'bar', height: 320, toolbar: { show: true }, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '70%' } },
            dataLabels: {
                enabled: true,
                formatter: v => CURRENCY + Number(v).toLocaleString(),
                style: { fontSize: '11px', fontWeight: 'bold' },
            },
            xaxis: {
                categories: names,
                labels: { formatter: v => CURRENCY + Number(v).toLocaleString() },
            },
            yaxis: { title: { text: 'Supplier' } },
            colors: ['#3E97FF'],
            tooltip: { y: { formatter: v => money(v) } },
        };

        if (topChart) topChart.updateOptions(options, false, true);
        else { topChart = new ApexCharts(el, options); topChart.render(); }
    }

    function renderDeliveryChart(ranges) {
        const el = $('spDeliveryChart');
        const empty = $('spDeliveryEmpty');
        if (!el) return;

        const data = [
            Number(ranges.excellent || 0),
            Number(ranges.good || 0),
            Number(ranges.fair || 0),
            Number(ranges.poor || 0),
        ];
        if (data.every(v => v === 0)) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (deliveryChart) { deliveryChart.destroy(); deliveryChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const total = data.reduce((a, b) => a + b, 0);
        const excellentPct = total > 0 ? Math.round((data[0] / total) * 100) : 0;

        const options = {
            series: [{ name: 'Suppliers', data }],
            chart:  { type: 'bar', height: 320, toolbar: { show: true }, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 4 } },
            dataLabels: { enabled: true, formatter: v => v + ' sup.', style: { fontSize: '11px' } },
            xaxis: { categories: ['≥ 90%', '75–89%', '60–74%', '< 60%'] },
            yaxis: { title: { text: 'Suppliers' } },
            colors: ['#50CD89', '#FFC700', '#FF9F1C', '#F1416C'],
            tooltip: { y: { formatter: v => v + ' suppliers' } },
        };

        if (deliveryChart) deliveryChart.updateOptions(options, false, true);
        else { deliveryChart = new ApexCharts(el, options); deliveryChart.render(); }
    }

    function renderValueChart(ranges) {
        const el = $('spValueChart');
        const empty = $('spValueEmpty');
        if (!el) return;

        const data = [
            Number(ranges.high || 0),
            Number(ranges.medium || 0),
            Number(ranges.low || 0),
            Number(ranges.very_low || 0),
        ];
        if (data.every(v => v === 0)) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (valueChart) { valueChart.destroy(); valueChart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: data,
            chart:  { type: 'donut', height: 320, fontFamily: 'inherit' },
            labels: ['≥ ' + CURRENCY + '1,000', CURRENCY + '500–999', CURRENCY + '100–499', '< ' + CURRENCY + '100'],
            colors: ['#F1416C', '#FFC700', '#3E97FF', '#50CD89'],
            legend: { position: 'bottom', fontSize: '12px' },
            plotOptions: { pie: { donut: { size: '60%' } } },
            tooltip: { y: { formatter: v => v + ' suppliers' } },
        };

        if (valueChart) valueChart.updateOptions(options, false, true);
        else { valueChart = new ApexCharts(el, options); valueChart.render(); }
    }

    function renderTable(rows, pagination) {
        const body = $('spTableBody');
        const empty = $('spEmpty');
        const wrap = $('spPaginationWrap');
        const badge = $('spBadge');
        if (!body) return;

        if (!rows || rows.length === 0) {
            body.innerHTML = ''; empty?.classList.remove('d-none'); wrap?.classList.add('d-none');
            if (badge) badge.textContent = '';
            return;
        }
        empty?.classList.add('d-none');
        wrap?.classList.remove('d-none');
        if (badge) badge.textContent = `Showing ${pagination.from} of ${pagination.total}`;

        const CLASS_COLOR = { A: 'danger', B: 'warning', C: 'success' };
        const CLASS_LABEL = { A: 'Category A (Strategic)', B: 'Category B (Tactical)', C: 'Category C (Transactional)' };

        body.innerHTML = rows.map((r, i) => {
            const idx = (pagination.from || 1) + i;
            const cls     = r.classification || 'C';
            const clsColor= CLASS_COLOR[cls] || 'dark';
            const perf    = Number(r.performance_score);
            const perfColor = perf >= 80 ? 'success' : perf >= 60 ? 'info' : perf >= 40 ? 'warning' : 'danger';
            const perfLabel = perf >= 80 ? 'Excellent' : perf >= 60 ? 'Good' : perf >= 40 ? 'Fair' : 'Poor';
            const rate    = Number(r.on_time_delivery_rate);
            const rateColor = rate >= 90 ? 'success' : rate >= 75 ? 'warning' : 'danger';
            const days    = Number(r.avg_delivery_days);
            const daysCls = days <= 7 ? 'text-success' : days <= 14 ? 'text-warning' : 'text-danger';
            const initials = (r.name || '?').substring(0, 2).toUpperCase();

            return `
                <tr>
                    <td class="ps-4 fw-bold">${idx}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-45px me-3">
                                <div class="symbol-label bg-light-${clsColor} text-${clsColor} fw-bold">${esc(initials)}</div>
                            </div>
                            <div>
                                <div class="fw-bold">${esc(r.name)}</div>
                                <small class="text-muted">${esc(r.contact_person || '—')}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-light-${clsColor}">${CLASS_LABEL[cls]}</span></td>
                    <td><span class="fw-bold">${fmtNum(r.total_orders, 0)}</span></td>
                    <td><span class="fw-bold text-primary">${money(r.total_spent)}</span></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="fw-bold me-2">${fmtNum(r.spend_percentage, 1)}%</span>
                            <div class="progress w-100px" style="height:6px;">
                                <div class="progress-bar bg-${clsColor}" style="width:${Math.min(100, Number(r.spend_percentage))}%"></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="fw-semibold">${money(r.avg_order_value)}</span></td>
                    <td><span class="badge badge-light-${rateColor}">${fmtNum(rate, 1)}%</span></td>
                    <td><span class="fw-semibold ${daysCls}">${fmtNum(days, 1)} days</span></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress w-100px me-2" style="height:6px;">
                                <div class="progress-bar bg-${perfColor}" style="width:${perf}%"></div>
                            </div>
                            <span class="badge badge-light-${perfColor}">${perfLabel}</span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);
    }

    function renderPagination(p) {
        const info = $('spPaginationInfo');
        const ul   = $('spPagination');
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
        if (state.supplierId) params.set('supplier_id', state.supplierId);
        window.location.href = `/reports/purchasing/supplier-performance?${params.toString()}&export=1`;
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