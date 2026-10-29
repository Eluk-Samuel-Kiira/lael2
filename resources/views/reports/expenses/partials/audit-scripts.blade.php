<script>
window.ExpenseAudit = (function () {
    'use strict';

    const ROUTES = window.EXP_AUDIT_ROUTES;
    const BOOT   = window.EXP_AUDIT_BOOTSTRAP || {};
    let CURRENCY = '';
    let metaCache = null;
    let activeController = null;
    let inFlight = false;

    const state = {
        start_date:  BOOT.start_date  || '',
        end_date:    BOOT.end_date    || '',
        audit_type:  BOOT.audit_type  || 'all',
        employee_id: BOOT.employee_id || '',
        threshold:   BOOT.threshold   != null ? String(BOOT.threshold) : '1000',
        location_id: BOOT.location_id || '',
        per_page:    15,
        page:        1,
    };

    document.addEventListener('DOMContentLoaded', () => {
        seedForm();
        bind();
        loadMeta().then(() => loadData());
    });

    function bind() {
        const form = document.getElementById('auditFilterForm');
        form?.addEventListener('submit', (e) => {
            e.preventDefault();
            readForm();
            state.page = 1;
            loadData();
        });

        document.getElementById('auditClear')?.addEventListener('click', () => {
            state.start_date  = BOOT.start_date;
            state.end_date    = BOOT.end_date;
            state.audit_type  = 'all';
            state.employee_id = '';
            state.threshold   = '1000';
            state.location_id = '';
            state.per_page    = 15;
            state.page        = 1;
            seedForm();
            toggleThresholdField();
            loadData();
        });

        document.getElementById('auditTypeSelect')?.addEventListener('change', () => {
            toggleThresholdField();
            state.page = 1;
        });

        document.getElementById('auditPerPage')?.addEventListener('change', function () {
            state.per_page = parseInt(this.value, 10) || 15;
            state.page     = 1;
            loadData();
        });

        document.getElementById('btnAuditPDF')?.addEventListener('click', () => {
            window.toastr?.info?.('{{ __('accounting.pdf_export_coming_soon') }}');
        });

        document.querySelectorAll('[data-export-table]').forEach(el => {
            el.addEventListener('click', () => {
                const tableId  = el.dataset.exportTable;
                const filename = (el.dataset.exportFilename || 'audit') + '_' + new Date().toISOString().slice(0, 10);
                const format   = el.dataset.exportFormat || 'xlsx';
                const sheet    = el.dataset.exportSheet || 'Sheet1';
                if (typeof window.exportCurrentPage === 'function') {
                    window.exportCurrentPage({ tableId, filename, sheetName: sheet, format });
                }
            });
        });
    }

    function toggleThresholdField() {
        const sel = document.getElementById('auditTypeSelect');
        const f   = document.getElementById('thresholdField');
        if (!sel || !f) return;
        f.style.display = sel.value === 'high_value' ? '' : 'none';
    }

    function seedForm() {
        const form = document.getElementById('auditFilterForm');
        if (!form) return;
        Object.entries(state).forEach(([k, v]) => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) el.value = v == null ? '' : String(v);
        });
        const perPageEl = document.getElementById('auditPerPage');
        if (perPageEl) perPageEl.value = String(state.per_page);
        toggleThresholdField();
    }
    function readForm() {
        const form = document.getElementById('auditFilterForm');
        if (!form) return;
        Object.keys(state).forEach(k => {
            if (k === 'per_page' || k === 'page') return;
            const el = form.querySelector(`[name="${k}"]`);
            if (el) state[k] = el.value;
        });
    }

    async function loadMeta() {
        if (metaCache) return metaCache;
        try {
            const res = await fetch(ROUTES.meta, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Meta failed');
            metaCache = json;
            CURRENCY = json.currency || '';

            const typeSel = document.getElementById('auditTypeSelect');
            if (typeSel) {
                typeSel.innerHTML = '';
                Object.entries(json.audit_types || {}).forEach(([k, v]) => {
                    const o = document.createElement('option');
                    o.value = k; o.textContent = v;
                    typeSel.appendChild(o);
                });
                typeSel.value = state.audit_type;
            }

            const currEl = document.getElementById('thresholdCurrency');
            if (currEl) currEl.textContent = CURRENCY;

            const empSel = document.querySelector('#auditFilterForm [name="employee_id"]');
            if (empSel) {
                const ph = empSel.querySelector('option[value=""]');
                empSel.innerHTML = '';
                if (ph) empSel.appendChild(ph);
                (json.employees || []).forEach(e => {
                    const o = document.createElement('option');
                    o.value = e.id;
                    o.textContent = `${e.first_name} ${e.last_name}`.trim();
                    empSel.appendChild(o);
                });
                empSel.value = state.employee_id || '';
            }

            const locSel = document.querySelector('#auditFilterForm [name="location_id"]');
            if (locSel) {
                const ph = locSel.querySelector('option[value=""]');
                locSel.innerHTML = '';
                if (ph) locSel.appendChild(ph);
                (json.locations || []).forEach(l => {
                    const o = document.createElement('option');
                    o.value = l.id; o.textContent = l.name;
                    locSel.appendChild(o);
                });
                locSel.value = state.location_id || '';
            }

            return json;
        } catch (err) {
            console.error('[exp-audit] meta failed:', err);
            throw err;
        }
    }

    async function loadData() {
        if (inFlight) return;
        inFlight = true;
        if (activeController) activeController.abort();
        const controller = new AbortController();
        activeController = controller;
        showState('loading');

        const params = new URLSearchParams();
        Object.entries(state).forEach(([k, v]) => { if (v !== '' && v != null) params.set(k, v); });
        params.set('page',     state.page);
        params.set('per_page', state.per_page);

        try {
            const res = await fetch(`${ROUTES.data}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            renderStats(json.summary || {}, json.currency || CURRENCY);
            renderItemsTitle(json.meta.audit_type);
            renderItems(json.items || []);
            renderPagination(json.pagination || {});
            renderByCategory(json.by_category || []);
            renderEmployeeCompliance(json.employee_compliance || []);
            renderMonthly(json.monthly || []);
            renderDateBadge(json.meta.start_date, json.meta.end_date);
            renderActiveFilters();
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[exp-audit] load failed:', err);
            showState('empty');
            window.toastr?.error?.('Failed to load audit.');
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function showState(which) {
        document.getElementById('auditLoading')?.classList.toggle('d-none', which !== 'loading');
        document.getElementById('auditWrapper')?.classList.toggle('d-none', which !== 'table');
        document.getElementById('auditEmpty')?.classList.toggle('d-none',   which !== 'empty');
    }

    function renderStats(s, currency) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        const money = (n) => `${currency} ${fmt(n)}`;
        set('statTotalItems',      fmt(s.total_items || 0));
        set('statTotalAmount',     money(s.total_amount || 0));
        set('statMissingReceipts', fmt(s.missing_receipts || 0));
        set('statUnapproved',      fmt(s.unapproved || 0));
        set('statHighValue',       fmt(s.high_value || 0));
        set('statAvgAge',          `${Number(s.average_age_days || 0).toFixed(1)} {{ __('accounting.days') }}`);
        set('statComplianceRate',  `${Number(s.compliance_rate || 0).toFixed(1)}%`);
        set('statAvgItemAmount',   money(s.avg_item_amount || 0));
    }

    function renderItemsTitle(auditType) {
        const el = document.getElementById('auditItemsTitle');
        if (!el) return;
        const label = (metaCache?.audit_types || {})[auditType] || '{{ __('accounting.all_audit_items') }}';
        el.textContent = label;
    }

    function renderItems(items) {
        const tbody = document.getElementById('auditBody');
        const badge = document.getElementById('auditItemsBadge');
        if (!tbody) return;

        if (!items.length) { showState('empty'); return; }

        tbody.innerHTML = items.map(e => {
            const receiptCell = e.has_receipt
                ? `<a href="${esc(e.receipt_url)}" target="_blank" class="btn btn-sm btn-light-success"><i class="ki-duotone ki-eye fs-2 me-1"></i>{{ __('accounting.view_receipt') }}</a>`
                : (e.requires_receipt
                    ? `<span class="badge badge-light-danger"><i class="ki-duotone ki-cross fs-2 me-1"></i>{{ __('accounting.missing') }}</span>`
                    : `<span class="badge badge-light-secondary">{{ __('accounting.not_required') }}</span>`);

            const approvalCell = e.is_approved
                ? `<span class="badge badge-light-success"><i class="ki-duotone ki-check fs-2 me-1"></i>{{ __('accounting.approved') }}</span>${e.approver_name ? `<div class="mt-1"><small class="text-muted">${esc(e.approver_name)}</small></div>` : ''}`
                : (e.requires_approval
                    ? `<span class="badge badge-light-warning"><i class="ki-duotone ki-clock fs-2 me-1"></i>{{ __('accounting.pending') }}</span>`
                    : `<span class="badge badge-light-secondary">{{ __('accounting.not_required') }}</span>`);

            const ageColor = e.age_days > 30 ? 'danger' : (e.age_days > 7 ? 'warning' : 'success');

            const statusBadge = e.payment_status === 'paid'
                ? `<span class="badge badge-light-success">{{ __('accounting.paid') }}</span>`
                : e.payment_status === 'pending'
                    ? `<span class="badge badge-light-warning">{{ __('accounting.pending') }}</span>`
                    : `<span class="badge badge-light-info">${esc(e.payment_status || '')}</span>`;

            const flagsHtml = (e.flags || []).length
                ? e.flags.map(f => `<span class="badge badge-light-${f.color} mb-1 d-block">${esc(f.text)}</span>`).join('')
                : `<span class="badge badge-light-success">{{ __('accounting.compliant') }}</span>`;

            return `
                <tr>
                    <td class="ps-4">
                        <span class="fw-semibold">${esc(e.date)}</span>
                        <div class="mt-1"><small class="text-muted">${esc(e.date_day || '')}</small></div>
                    </td>
                    <td>
                        <div class="d-flex justify-content-start flex-column">
                            <span class="text-gray-800 fw-bold">${esc((e.description || '').slice(0, 40))}</span>
                            ${e.vendor_name ? `<small class="text-muted">{{ __('accounting.vendor') }}: ${esc(e.vendor_name)}</small>` : ''}
                            <small class="text-muted">{{ __('accounting.expense_number') }}: ${esc(e.expense_number || '')}</small>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-light-primary">${esc(e.category_name)}</span>
                        <div class="mt-1">
                            ${e.requires_receipt ? `<small class="badge badge-light-success badge-sm">{{ __('accounting.requires_receipt') }}</small>` : ''}
                            ${e.requires_approval ? `<small class="badge badge-light-warning badge-sm">{{ __('accounting.requires_approval') }}</small>` : ''}
                        </div>
                    </td>
                    <td>
                        ${e.employee_name
                            ? `<div class="d-flex align-items-center">
                                 <div class="symbol symbol-30px symbol-circle me-3">
                                     <div class="symbol-label bg-light-primary"><span class="fw-bold">${esc((e.employee_name || '').charAt(0))}</span></div>
                                 </div>
                                 <div class="d-flex justify-content-start flex-column">
                                     <span class="fw-semibold">${esc(e.employee_name)}</span>
                                     <small class="text-muted">ID: ${esc(e.employee_id)}</small>
                                 </div>
                               </div>`
                            : `<span class="text-muted">{{ __('accounting.not_assigned') }}</span>`}
                    </td>
                    <td>
                        <span class="fw-bold text-success">${esc(CURRENCY)} ${fmt(e.amount)}</span>
                        ${e.tax_amount > 0 ? `<div class="mt-1"><small class="text-muted">{{ __('accounting.tax') }}: ${esc(CURRENCY)} ${fmt(e.tax_amount)}</small></div>` : ''}
                    </td>
                    <td>${statusBadge}</td>
                    <td>${receiptCell}</td>
                    <td>${approvalCell}</td>
                    <td><span class="fw-bold text-${ageColor}">${e.age_days} {{ __('accounting.days') }}</span></td>
                    <td>${flagsHtml}</td>
                </tr>`;
        }).join('');

        if (badge) badge.textContent = `${items.length} {{ __('accounting.items') }}`;
        showState('table');
    }

    function renderPagination(p) {
        const info = document.getElementById('auditPageInfo');
        const ul   = document.getElementById('auditPagination');
        if (!info || !ul) return;

        info.textContent = p.total
            ? `{{ __('accounting.showing') }} ${p.from || 0}–${p.to || 0} {{ __('accounting.of') }} ${p.total}`
            : '';
        ul.innerHTML = '';

        if (!p.total || p.last_page <= 1) return;

        const addPage = (page, label, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = label;
            if (!disabled) {
                a.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    state.page = page;
                    loadData();
                });
            }
            li.appendChild(a);
            ul.appendChild(li);
        };

        addPage((p.current_page || 1) - 1, 'Prev', false, (p.current_page || 1) <= 1);
        const start = Math.max(1, (p.current_page || 1) - 2);
        const end   = Math.min(p.last_page || 1, (p.current_page || 1) + 2);
        for (let i = start; i <= end; i++) {
            addPage(i, String(i), i === p.current_page);
        }
        addPage((p.current_page || 1) + 1, 'Next', false, !p.has_more);
    }

    function renderByCategory(rows) {
        const card  = document.getElementById('catCard');
        const tbody = document.getElementById('catBody');
        if (!card || !tbody) return;
        if (!rows.length) { card.style.display = 'none'; return; }
        card.style.display = '';

        tbody.innerHTML = rows.map(c => {
            const compliance = c.count > 0
                ? ((c.count - c.missing_receipts - c.unapproved) / c.count) * 100
                : 100;
            const compColor = compliance >= 90 ? 'success' : (compliance >= 70 ? 'warning' : 'danger');
            return `
                <tr>
                    <td class="ps-4 fw-semibold"><span class="badge badge-light-primary">${esc(c.category)}</span></td>
                    <td><span class="badge badge-light-info">${c.count}</span></td>
                    <td><span class="fw-bold text-success">${esc(CURRENCY)} ${fmt(c.total_amount)}</span></td>
                    <td><span class="fw-semibold text-gray-700">${esc(CURRENCY)} ${fmt(c.avg_amount)}</span></td>
                    <td><span class="fw-bold ${c.missing_receipts > 0 ? 'text-danger' : 'text-success'}">${c.missing_receipts}</span></td>
                    <td><span class="fw-bold ${c.unapproved > 0 ? 'text-warning' : 'text-success'}">${c.unapproved}</span></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:20px;">
                                <div class="progress-bar bg-${compColor}" style="width:${Math.min(compliance, 100)}%"></div>
                            </div>
                            <span class="fw-bold text-${compColor}">${compliance.toFixed(1)}%</span>
                        </div>
                    </td>
                </tr>`;
        }).join('');
    }

    function renderEmployeeCompliance(rows) {
        const card  = document.getElementById('empCard');
        const tbody = document.getElementById('empBody');
        if (!card || !tbody) return;
        if (!rows.length) { card.style.display = 'none'; return; }
        card.style.display = '';

        tbody.innerHTML = rows.map(e => {
            const missingRate    = e.total_expenses > 0 ? (e.missing_receipts / e.total_expenses) * 100 : 0;
            const unapprovedRate = e.total_expenses > 0 ? (e.unapproved / e.total_expenses) * 100 : 0;
            const compliance     = 100 - missingRate - unapprovedRate;
            const riskColor = compliance >= 90 ? 'success' : (compliance >= 70 ? 'warning' : 'danger');
            const riskText  = compliance >= 90
                ? '{{ __('accounting.low_risk') }}'
                : (compliance >= 70 ? '{{ __('accounting.medium_risk') }}' : '{{ __('accounting.high_risk') }}');

            return `
                <tr>
                    <td class="ps-4 fw-semibold">${esc(e.employee_name)}</td>
                    <td><span class="badge badge-light-info">ID: ${e.id}</span></td>
                    <td><span class="fw-semibold">${e.total_expenses}</span></td>
                    <td><span class="fw-semibold text-gray-700">${esc(CURRENCY)} ${fmt(e.avg_expense)}</span></td>
                    <td>
                        <span class="fw-bold ${e.missing_receipts > 0 ? 'text-danger' : 'text-success'}">${e.missing_receipts}</span>
                        <small class="text-muted d-block">(${missingRate.toFixed(1)}%)</small>
                    </td>
                    <td>
                        <span class="fw-bold ${e.unapproved > 0 ? 'text-warning' : 'text-success'}">${e.unapproved}</span>
                        <small class="text-muted d-block">(${unapprovedRate.toFixed(1)}%)</small>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:20px;">
                                <div class="progress-bar bg-${riskColor}" style="width:${Math.min(compliance, 100)}%"></div>
                            </div>
                            <span class="fw-bold text-${riskColor}">${compliance.toFixed(1)}%</span>
                        </div>
                    </td>
                    <td><span class="badge badge-light-${riskColor}">${riskText}</span></td>
                </tr>`;
        }).join('');
    }

    function renderMonthly(rows) {
        const card  = document.getElementById('monthlyCard');
        const tbody = document.getElementById('monthlyBody');
        if (!card || !tbody) return;
        if (!rows.length) { card.style.display = 'none'; return; }
        card.style.display = '';

        tbody.innerHTML = rows.map(m => {
            const missingRate    = m.total_expenses > 0 ? (m.missing_receipts / m.total_expenses) * 100 : 0;
            const unapprovedRate = m.total_expenses > 0 ? (m.unapproved / m.total_expenses) * 100 : 0;
            const compliance     = 100 - missingRate - unapprovedRate;
            const compColor       = compliance >= 90 ? 'success' : (compliance >= 70 ? 'warning' : 'danger');
            const missingColor    = missingRate > 10 ? 'danger' : (missingRate > 5 ? 'warning' : 'success');
            const unapprovedColor = unapprovedRate > 10 ? 'danger' : (unapprovedRate > 5 ? 'warning' : 'success');

            return `
                <tr>
                    <td class="ps-4 fw-semibold">${esc(m.label)}</td>
                    <td><span class="badge badge-light-info">${m.total_expenses}</span></td>
                    <td><span class="fw-bold text-success">${esc(CURRENCY)} ${fmt(m.monthly_total)}</span></td>
                    <td><span class="fw-bold ${m.missing_receipts > 0 ? 'text-danger' : 'text-success'}">${m.missing_receipts}</span></td>
                    <td><span class="fw-bold ${m.unapproved > 0 ? 'text-warning' : 'text-success'}">${m.unapproved}</span></td>
                    <td><span class="fw-bold text-${missingColor}">${missingRate.toFixed(1)}%</span></td>
                    <td><span class="fw-bold text-${unapprovedColor}">${unapprovedRate.toFixed(1)}%</span></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress w-100 me-3" style="height:15px;">
                                <div class="progress-bar bg-${compColor}" style="width:${Math.min(compliance, 100)}%"></div>
                            </div>
                            <span class="fw-bold text-${compColor}">${compliance.toFixed(1)}%</span>
                        </div>
                    </td>
                </tr>`;
        }).join('');
    }

    function renderDateBadge(start, end) {
        const f = (s) => {
            if (!s) return '—';
            const [y, m, d] = s.split('-');
            const names = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return `${d} ${names[(+m) - 1]} ${y}`;
        };
        const el = document.getElementById('auditDateBadgeText');
        if (el) el.textContent = `${f(start)} — ${f(end)}`;
    }

    function renderActiveFilters() {
        const bar  = document.getElementById('activeFiltersBar');
        const list = document.getElementById('activeFiltersList');
        if (!bar || !list) return;

        const meta  = metaCache || {};
        const chips = [];

        if (state.audit_type && state.audit_type !== 'all') {
            chips.push(['{{ __('accounting.audit_type') }}', meta.audit_types?.[state.audit_type] || state.audit_type]);
        }
        if (state.audit_type === 'high_value') {
            chips.push(['{{ __('accounting.threshold_amount') }}', `${CURRENCY} ${fmt(state.threshold)}`]);
        }
        if (state.employee_id) {
            const e = (meta.employees || []).find(x => String(x.id) === String(state.employee_id));
            if (e) chips.push(['{{ __('accounting.employee') }}', `${e.first_name} ${e.last_name}`.trim()]);
        }
        if (state.location_id) {
            const l = (meta.locations || []).find(x => String(x.id) === String(state.location_id));
            if (l) chips.push(['{{ __('accounting.location') }}', l.name]);
        }

        if (!chips.length) { bar.classList.add('d-none'); return; }

        bar.classList.remove('d-none');
        list.innerHTML = `
            <span class="text-muted fw-semibold me-2"><i class="ki-duotone ki-filter fs-5 me-1"></i>{{ __('accounting.active_filters') }}:</span>
            ${chips.map(([l, v]) => `<span class="badge badge-light-primary fs-7"><strong>${esc(l)}:</strong>&nbsp;${esc(v)}</span>`).join('')}
            <button type="button" id="clearAllFilters" class="btn btn-sm btn-link text-danger fs-7 p-0 ms-2">
                <i class="ki-duotone ki-cross fs-5 me-1"></i>{{ __('accounting.clear_all') }}
            </button>`;
        document.getElementById('clearAllFilters')?.addEventListener('click', () => {
            document.getElementById('auditClear')?.click();
        });
    }

    function fmt(n) {
        return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.ExpenseAudit = { reload: loadData };
    return window.ExpenseAudit;
})();
</script>