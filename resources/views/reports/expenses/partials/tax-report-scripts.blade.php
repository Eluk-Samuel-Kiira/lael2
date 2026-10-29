<script>
window.ExpenseTaxReport = (function () {
    'use strict';

    const ROUTES = window.EXP_TAX_ROUTES;
    const BOOT   = window.EXP_TAX_BOOTSTRAP || {};
    let CURRENCY = '';
    let metaCache = null;
    let activeController = null;
    let inFlight = false;

    const state = {
        start_date:  BOOT.start_date  || '',
        end_date:    BOOT.end_date    || '',
        category_id: BOOT.category_id || '',
        tax_type:    BOOT.tax_type    || 'all',
        location_id: BOOT.location_id || '',
    };

    document.addEventListener('DOMContentLoaded', () => {
        seedForm();
        bind();
        loadMeta().then(() => loadData());
    });

    function bind() {
        const form = document.getElementById('taxFilterForm');
        form?.addEventListener('submit', (e) => {
            e.preventDefault();
            readForm();
            loadData();
        });

        document.getElementById('taxClear')?.addEventListener('click', () => {
            state.start_date  = BOOT.start_date;
            state.end_date    = BOOT.end_date;
            state.category_id = '';
            state.tax_type    = 'all';
            state.location_id = '';
            seedForm();
            loadData();
        });
    }

    function seedForm() {
        const form = document.getElementById('taxFilterForm');
        if (!form) return;
        Object.entries(state).forEach(([k, v]) => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el) el.value = v == null ? '' : String(v);
        });
    }
    function readForm() {
        const form = document.getElementById('taxFilterForm');
        if (!form) return;
        Object.keys(state).forEach(k => {
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

            const catSel = document.querySelector('#taxFilterForm [name="category_id"]');
            if (catSel) {
                const ph = catSel.querySelector('option[value=""]');
                catSel.innerHTML = '';
                if (ph) catSel.appendChild(ph);
                (json.categories || []).forEach(c => {
                    const o = document.createElement('option');
                    o.value = c.id;
                    o.textContent = c.name + (c.code ? ' (' + c.code + ')' : '');
                    catSel.appendChild(o);
                });
                catSel.value = state.category_id || '';
            }

            const locSel = document.querySelector('#taxFilterForm [name="location_id"]');
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
            console.error('[exp-tax] meta failed:', err);
            throw err;
        }
    }

    async function loadData() {
        if (inFlight) return;
        inFlight = true;
        if (activeController) activeController.abort();
        const controller = new AbortController();
        activeController = controller;

        const params = new URLSearchParams();
        Object.entries(state).forEach(([k, v]) => { if (v !== '' && v != null) params.set(k, v); });

        try {
            const res = await fetch(`${ROUTES.data}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            renderStats(json.summary || {}, json.currency || CURRENCY);
            renderCategoryTable(json.by_category || []);
            renderMonthlyTable(json.monthly || []);
            renderDistTable(json.rate_distribution || []);
            renderDateBadge(json.meta.start_date, json.meta.end_date);
            renderActiveFilters();
            toggleEmptyState(json.summary || {});
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[exp-tax] load failed:', err);
            window.toastr?.error?.('Failed to load tax report.');
            toggleEmptyState({ total_expenses: 0 });
        } finally {
            if (activeController === controller) { inFlight = false; activeController = null; }
        }
    }

    function toggleEmptyState(summary) {
        const empty = (summary.total_expenses || 0) === 0;
        document.getElementById('emptyCard')?.style && (document.getElementById('emptyCard').style.display = empty ? '' : 'none');
        document.getElementById('summaryCardRow')?.style && (document.getElementById('summaryCardRow').style.display = empty ? 'none' : '');
    }

    function renderStats(s, currency) {
        const money = (n) => `${currency} ${fmt(n)}`;
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('statTotalExpenses', fmt(s.total_expenses || 0));
        set('statGross',         money(s.total_gross || 0));
        set('statTax',           money(s.total_tax || 0));
        set('statAvgTax',        `${(s.avg_tax_rate || 0).toFixed(2)}%`);
    }

    function renderCategoryTable(rows) {
        const card = document.getElementById('catCard');
        const tbody = document.getElementById('catBody');
        const tfoot = document.getElementById('catFoot');
        if (!card || !tbody) return;

        if (!rows.length) { card.style.display = 'none'; return; }
        card.style.display = '';

        tbody.innerHTML = rows.map(r => `
            <tr>
                <td>${esc(r.category_name)}</td>
                <td class="text-center">${r.expense_count}</td>
                <td class="text-end">${esc(CURRENCY)} ${fmt(r.gross_amount)}</td>
                <td class="text-end text-warning">${esc(CURRENCY)} ${fmt(r.tax_amount)}</td>
                <td class="text-end text-success">${esc(CURRENCY)} ${fmt(r.net_amount)}</td>
                <td class="text-center">${Number(r.avg_tax_rate || 0).toFixed(2)}%</td>
            </tr>`).join('');

        const totalGross = rows.reduce((s, r) => s + r.gross_amount, 0);
        const totalTax   = rows.reduce((s, r) => s + r.tax_amount, 0);
        const totalNet   = rows.reduce((s, r) => s + r.net_amount, 0);
        const totalCount = rows.reduce((s, r) => s + r.expense_count, 0);
        const rate       = totalGross > 0 ? (totalTax / totalGross) * 100 : 0;

        if (tfoot) tfoot.innerHTML = `
            <tr>
                <td>{{ __('accounting.total') }}</td>
                <td class="text-center">${totalCount}</td>
                <td class="text-end">${esc(CURRENCY)} ${fmt(totalGross)}</td>
                <td class="text-end">${esc(CURRENCY)} ${fmt(totalTax)}</td>
                <td class="text-end">${esc(CURRENCY)} ${fmt(totalNet)}</td>
                <td class="text-center">${rate.toFixed(2)}%</td>
            </tr>`;
    }

    function renderMonthlyTable(rows) {
        const card = document.getElementById('monthlyCard');
        const tbody = document.getElementById('monthlyBody');
        if (!card || !tbody) return;

        if (!rows.length) { card.style.display = 'none'; return; }
        card.style.display = '';

        tbody.innerHTML = rows.map(r => `
            <tr>
                <td>${esc(r.month_name)}</td>
                <td class="text-center">${r.expense_count}</td>
                <td class="text-end">${esc(CURRENCY)} ${fmt(r.gross_amount)}</td>
                <td class="text-end text-warning">${esc(CURRENCY)} ${fmt(r.tax_amount)}</td>
                <td class="text-end text-success">${esc(CURRENCY)} ${fmt(r.net_amount)}</td>
                <td class="text-center">${Number(r.avg_tax_rate || 0).toFixed(2)}%</td>
            </tr>`).join('');
    }

    function renderDistTable(rows) {
        const card = document.getElementById('distCard');
        const tbody = document.getElementById('distBody');
        const tfoot = document.getElementById('distFoot');
        if (!card || !tbody) return;

        if (!rows.length) { card.style.display = 'none'; return; }
        card.style.display = '';

        const totalCount = rows.reduce((s, r) => s + r.expense_count, 0);
        const totalTax   = rows.reduce((s, r) => s + r.total_tax, 0);

        tbody.innerHTML = rows.map(r => {
            const pct = totalCount > 0 ? (r.expense_count / totalCount) * 100 : 0;
            return `
                <tr>
                    <td class="text-center fw-bold">${r.tax_rate_percent}%</td>
                    <td class="text-center">${r.expense_count}</td>
                    <td class="text-end text-warning">${esc(CURRENCY)} ${fmt(r.total_tax)}</td>
                    <td class="text-center">${pct.toFixed(1)}%</td>
                </tr>`;
        }).join('');

        if (tfoot) tfoot.innerHTML = `
            <tr>
                <td class="text-center">{{ __('accounting.total') }}</td>
                <td class="text-center">${totalCount}</td>
                <td class="text-end">${esc(CURRENCY)} ${fmt(totalTax)}</td>
                <td class="text-center">100.0%</td>
            </tr>`;
    }

    function renderDateBadge(start, end) {
        const f = (s) => {
            if (!s) return '—';
            const [y, m, d] = s.split('-');
            const names = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return `${d} ${names[(+m) - 1]} ${y}`;
        };
        const el = document.getElementById('taxDateBadgeText');
        if (el) el.textContent = `${f(start)} — ${f(end)}`;
    }

    function renderActiveFilters() {
        const bar = document.getElementById('activeFiltersBar');
        const list = document.getElementById('activeFiltersList');
        if (!bar || !list) return;

        const meta = metaCache || {};
        const chips = [];

        if (state.category_id) {
            const c = (meta.categories || []).find(x => String(x.id) === String(state.category_id));
            if (c) chips.push(['{{ __('accounting.category') }}', c.name]);
        }
        if (state.tax_type && state.tax_type !== 'all') {
            const label = state.tax_type === 'taxable' ? '{{ __('accounting.taxable_only') }}' : '{{ __('accounting.non_taxable_only') }}';
            chips.push(['{{ __('accounting.tax_type') }}', label]);
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
            document.getElementById('taxClear')?.click();
        });
    }

    function fmt(n) {
        return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.ExpenseTaxReport = { reload: loadData };
    return window.ExpenseTaxReport;
})();
</script>