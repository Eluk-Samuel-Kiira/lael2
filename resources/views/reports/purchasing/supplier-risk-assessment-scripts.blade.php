<script>
window.SupplierRisk = (function () {
    'use strict';

    const ROUTE    = window.SR_ROUTES.data;
    const CURRENCY = window.REPORT_CURRENCY || '';
    const BOOT     = window.SR_BOOTSTRAP || {};
    const I18N     = window.SR_I18N || {};

    const state = {
        perPage:         Number(BOOT.perPage         || 15),
        criticalPerPage: Number(BOOT.criticalPerPage || 10),
        page:            1,
        criticalPage:    1,
    };

    let chart = null;
    let inFlight = false;
    const $ = (id) => document.getElementById(id);

    document.addEventListener('DOMContentLoaded', () => {
        $('srExportExcel')?.addEventListener('click', () => {
            window.location.href = `/reports/purchasing/supplier-risk-assessment?export=excel`;
        });
        load();
    });

    async function load() {
        if (inFlight) return;
        inFlight = true;
        showSkeleton(true);

        const params = new URLSearchParams({
            page:            state.page,
            per_page:        state.perPage,
            critical_page:   state.criticalPage,
            critical_per_page: state.criticalPerPage,
        });

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
            console.error('[supplier-risk] load failed:', err);
            window.toastr?.error('Failed to load supplier risk assessment.');
            showSkeleton(false);
        } finally {
            inFlight = false;
        }
    }

    function showSkeleton(v) {
        $('srSkeleton')?.classList.toggle('d-none', !v);
        $('srContent')?.classList.toggle('d-none', v);
    }

    function render(p) {
        renderSummary(p.summary);
        renderChart(p.chart, p.summary.total_suppliers);
        renderCritical(p.critical);
        renderTable(p.suppliers);
    }

    // ─── Summary cards ───────────────────────────────────────
    function renderSummary(s) {
        const cards = [
            { title: I18N.totalLabel,      value: fmtNum(s.total_suppliers, 0), color: 'primary', icon: 'bi-people',              desc: I18N.activeAnalyzed },
            { title: I18N.highRiskLabel,   value: fmtNum(s.high, 0),            color: 'danger',  icon: 'bi-exclamation-triangle', desc: s.high_pct + '% ' + I18N.ofTotal },
            { title: I18N.mediumRiskLabel, value: fmtNum(s.medium, 0),          color: 'warning', icon: 'bi-info-circle',          desc: s.medium_pct + '% ' + I18N.ofTotal },
            { title: I18N.lowRiskLabel,    value: fmtNum(s.low, 0),             color: 'success', icon: 'bi-check-circle',         desc: s.low_pct + '% ' + I18N.ofTotal },
        ];

        $('srSummaryCards').innerHTML = cards.map(c => `
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

    // ─── Chart ───────────────────────────────────────────────
    function renderChart(chartData, total) {
        const el = $('srChart');
        const empty = $('srChartEmpty');
        if (!el) return;

        const hasData = chartData.values.some(v => v > 0);
        if (!hasData) {
            el.classList.add('d-none'); empty?.classList.remove('d-none');
            if (chart) { chart.destroy(); chart = null; }
            return;
        }
        el.classList.remove('d-none'); empty?.classList.add('d-none');

        const options = {
            series: chartData.values,
            chart:  { type: 'donut', height: 350, toolbar: { show: false } },
            labels: chartData.labels,
            colors: chartData.colors,
            legend: { position: 'bottom' },
            plotOptions: { pie: { donut: {
                size: '65%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total',
                        formatter: () => String(total),
                    },
                },
            }}},
            dataLabels: { enabled: false },
            tooltip: {
                y: { formatter: (v) => v + ' suppliers' },
            },
        };

        if (chart) chart.updateOptions(options, false, true);
        else { chart = new ApexCharts(el, options); chart.render(); }
    }

    // ─── Critical suppliers table ────────────────────────────
    function renderCritical(critical) {
        const wrap  = $('srCriticalWrap');
        const body  = $('srCriticalBody');
        const badge = $('srCriticalBadge');

        if (!critical.items || critical.items.length === 0) {
            wrap?.classList.add('d-none');
            return;
        }
        wrap?.classList.remove('d-none');
        if (badge) badge.textContent = `${critical.pagination.total} suppliers need attention`;

        body.innerHTML = critical.items.map(s => {
            const factors = (s.risk_factors || []).slice(0, 3);
            const more = (s.risk_factors || []).length - factors.length;
            const paymentColor = s.payment_terms_days > 60 ? 'danger' : (s.payment_terms_days > 30 ? 'warning' : 'success');
            const deliveryColor = s.avg_delivery_days > 14 ? 'danger' : (s.avg_delivery_days > 7 ? 'warning' : 'success');

            return `
                <tr class="table-danger">
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="symbol symbol-35px flex-shrink-0">
                                <div class="symbol-label bg-light-danger text-danger fw-bold fs-6">${esc(initials(s.name))}</div>
                            </div>
                            <div>
                                <div class="fw-bold">${esc(s.name)}</div>
                                <small class="text-muted">${esc(s.contact_person || I18N.noContact)}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress w-100px me-3" style="height:8px;">
                                <div class="progress-bar bg-danger" style="width:${Math.min(100, s.risk_score)}%"></div>
                            </div>
                            <span class="fw-bold text-danger">${fmtNum(s.risk_score, 1)}</span>
                        </div>
                    </td>
                    <td>
                        <span class="fw-bold text-primary">${money(s.total_spent)}</span>
                        <div class="text-muted fs-8">${fmtNum(s.total_orders, 0)} ${esc(I18N.orders)}</div>
                    </td>
                    <td>
                        <span class="badge badge-light-${paymentColor}">${s.payment_terms_days} ${esc(I18N.days)}</span>
                    </td>
                    <td>
                        <span class="fw-semibold text-${deliveryColor}">${fmtNum(s.avg_delivery_days, 1)} ${esc(I18N.days)}</span>
                        <div class="text-muted fs-8">${fmtNum(s.delivered_orders, 0)} ${esc(I18N.deliveredOrders)}</div>
                    </td>
                    <td class="pe-4">
                        <div class="d-flex flex-wrap gap-1">
                            ${factors.map(f => `<span class="badge badge-light-danger fs-8">${esc(I18N[f] || f)}</span>`).join('')}
                            ${more > 0 ? `<span class="badge badge-light-dark fs-8">+${more}</span>` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        renderPagination(critical.pagination, $('srCriticalPaginationInfo'), $('srCriticalPagination'), (p) => {
            state.criticalPage = p; load();
        });
        $('srCriticalPaginationWrap')?.classList.remove('d-none');
    }

    // ─── All suppliers table ─────────────────────────────────
    function renderTable(suppliers) {
        const body  = $('srTableBody');
        const empty = $('srEmpty');
        const wrap  = $('srPaginationWrap');
        const badge = $('srBadge');

        if (!suppliers.items || suppliers.items.length === 0) {
            body.innerHTML = ''; empty?.classList.remove('d-none'); wrap?.classList.add('d-none');
            if (badge) badge.textContent = '';
            return;
        }
        empty?.classList.add('d-none');
        wrap?.classList.remove('d-none');
        if (badge) badge.textContent = `Showing ${suppliers.pagination.from} of ${suppliers.pagination.total}`;

        body.innerHTML = suppliers.items.map((s, i) => {
            const idx = (suppliers.pagination.from || 1) + i;
            const riskColor   = s.risk_level === 'high' ? 'danger' : (s.risk_level === 'medium' ? 'warning' : 'success');
            const riskIcon    = s.risk_level === 'high' ? 'bi-exclamation-triangle' : (s.risk_level === 'medium' ? 'bi-info-circle' : 'bi-check-circle');

            const impactKey   = s.total_spent > 50000 ? 'critical_impact' : (s.total_spent > 10000 ? 'high_impact' : (s.total_spent > 1000 ? 'medium_impact' : 'low_impact'));
            const impactColor = s.total_spent > 50000 ? 'danger' : (s.total_spent > 10000 ? 'warning' : (s.total_spent > 1000 ? 'info' : 'success'));

            const dAvg = s.avg_delivery_days;
            const deliveryKey   = dAvg > 21 ? 'poor_delivery' : (dAvg > 14 ? 'average_delivery' : (dAvg > 7 ? 'moderate_delivery' : 'good_delivery'));
            const deliveryColor = dAvg > 21 ? 'danger' : (dAvg > 14 ? 'warning' : (dAvg > 7 ? 'info' : 'success'));

            const termKey   = s.payment_terms_days > 60 ? 'unfavorable_terms' : (s.payment_terms_days > 45 ? 'extended_terms' : (s.payment_terms_days > 30 ? 'standard_terms' : 'favorable_terms'));
            const termColor = s.payment_terms_days > 60 ? 'danger' : (s.payment_terms_days > 45 ? 'warning' : (s.payment_terms_days > 30 ? 'info' : 'success'));

            const factors = (s.risk_factors || []).slice(0, 2);
            const more    = (s.risk_factors || []).length - factors.length;

            return `
                <tr>
                    <td class="ps-4 fw-bold">${idx}</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="symbol symbol-40px flex-shrink-0">
                                <div class="symbol-label bg-light-${riskColor} text-${riskColor} fw-bold fs-5">${esc(initials(s.name))}</div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">${esc(s.name)}</div>
                                <small class="text-muted">${esc(s.contact_person || I18N.noContact)}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="bi ${riskIcon} fs-2 text-${riskColor} me-2"></i>
                            <span class="badge badge-light-${riskColor}">${esc(I18N[s.risk_level + '_risk'] || s.risk_level)}</span>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress w-100px me-3" style="height:8px;">
                                <div class="progress-bar bg-${riskColor}" style="width:${Math.min(100, s.risk_score)}%"></div>
                            </div>
                            <span class="fw-bold text-${riskColor}">${fmtNum(s.risk_score, 1)}</span>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-light-${impactColor} mb-1">${esc(I18N[impactKey])}</span>
                        <div class="text-muted fs-8">${money(s.total_spent)}</div>
                    </td>
                    <td>
                        <span class="badge badge-light-${deliveryColor} mb-1">${esc(I18N[deliveryKey])}</span>
                        <div class="text-muted fs-8">${fmtNum(dAvg, 1)} ${esc(I18N.daysAvg)}</div>
                    </td>
                    <td>
                        <span class="badge badge-light-${termColor} mb-1">${esc(I18N[termKey])}</span>
                        <div class="text-muted fs-8">${s.payment_terms_days} ${esc(I18N.days)}</div>
                    </td>
                    <td class="pe-4">
                        <div class="d-flex flex-wrap gap-1">
                            ${factors.map(f => `<span class="badge badge-light-${riskColor} fs-8">${esc(I18N[f] || f)}</span>`).join('')}
                            ${more > 0 ? `<span class="badge badge-light-dark fs-8">+${more}</span>` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        renderPagination(suppliers.pagination, $('srPaginationInfo'), $('srPagination'), (p) => {
            state.page = p; load();
        });
    }

    // ─── Shared pagination ───────────────────────────────────
    function renderPagination(p, infoEl, ulEl, onPage) {
        if (!ulEl || !infoEl || !p) return;
        infoEl.textContent = `Showing ${p.from} to ${p.to} of ${p.total}`;
        ulEl.innerHTML = '';

        const add = (page, label, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link'; a.href = '#'; a.textContent = label;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                if (disabled || active) return;
                onPage(page);
            });
            li.appendChild(a); ulEl.appendChild(li);
        };
        add(p.current_page - 1, 'Prev', false, p.current_page <= 1);
        for (let i = Math.max(1, p.current_page - 2); i <= Math.min(p.last_page, p.current_page + 2); i++) {
            add(i, i, i === p.current_page);
        }
        add(p.current_page + 1, 'Next', false, p.current_page >= p.last_page);
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