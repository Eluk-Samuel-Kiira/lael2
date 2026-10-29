<script>
window.TransactionDrawer = (function () {
    'use strict';

    const URL_DETAILS = (id) => `/accounting/transaction-ledger/details/${id}`;
    let currentId = null, currentPayload = null, bs = null;

    function getBs() {
        const el = document.getElementById('transactionDrawer');
        if (!el) { console.error('[transaction-drawer] #transactionDrawer missing'); return null; }
        if (!window.bootstrap?.Offcanvas) { console.error('[transaction-drawer] bootstrap missing'); return null; }
        bs = bs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return bs;
    }
    function close()       { getBs()?.hide(); }
    function isOpenFor(id) { return String(currentId) === String(id); }

    async function open(id) {
        currentId = id;
        const inst = getBs(); if (!inst) return;
        showSkeleton(); inst.show();
        try {
            const res = await fetch(URL_DETAILS(id), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');
            currentPayload = json;
            render(json);
        } catch (err) {
            console.error('[transaction-drawer] load failed:', err);
            renderError(err.message);
        }
    }

    function showSkeleton() {
        document.getElementById('transactionDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('transactionDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('transactionDrawerFooter')?.classList.add('d-none');
        const ref = document.getElementById('transactionDrawerRef');
        if (ref) ref.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('transactionDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('transactionDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    // ── Main render ──────────────────────────────────────────
    function render(json) {
        const tx   = json.transaction || {};
        const cust = json.customer;
        const pm   = json.payment_method || {};
        const curr = json.currency || {};

        currentId = tx.id;

        const refEl = document.getElementById('transactionDrawerRef');
        if (refEl) refEl.textContent = tx.transaction_ref || '';

        document.getElementById('transactionDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('transactionDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeaderBlock(tx, pm)}
            ${renderAmountBlock(tx)}
            ${renderInfoBlock(tx, pm)}
            ${renderUserBlock(tx, cust)}
            ${renderDescriptionBlock(tx)}
            ${renderReferenceBlock(tx)}
            ${renderMetadataBlock(tx)}
        `;

        document.getElementById('transactionDrawerActions').innerHTML = renderActionCluster(tx);
        document.getElementById('transactionDrawerFooter').classList.remove('d-none');
        bindActions(tx);
    }

    function renderHeaderBlock(tx, pm) {
        const isCredit = ['DEPOSIT', 'TRANSFER_IN', 'REFUND'].includes(tx.transaction_type);
        const isPurple = ['ADJUSTMENT', 'RECONCILIATION'].includes(tx.transaction_type);
        const sign     = isCredit ? '+' : (isPurple ? '' : '-');
        const amtClass = isCredit ? 'text-success' : (isPurple ? 'text-purple' : 'text-danger');

        return `
            <div class="d-flex align-items-center justify-content-between gap-4 mb-6">
                <div>
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('accounting.transaction_ref') }}</div>
                    <div class="fs-4 fw-bold text-gray-800">${esc(tx.transaction_ref || '-')}</div>
                    <div class="text-muted fs-7 mt-1">
                        ${esc(tx.receipt_number ? '· ' + tx.receipt_number : '')}
                        ${esc(tx.transaction_date ? ' · ' + new Date(tx.transaction_date).toLocaleString() : '')}
                    </div>
                </div>
                <div class="text-end">
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('accounting.amount') }}</div>
                    <div class="fs-2 fw-bolder ${amtClass}">${sign}${fmt(tx.amount)}</div>
                </div>
            </div>
        `;
    }

    function renderAmountBlock(tx) {
        return `
            <div class="row g-3 mb-5">
                <div class="col-md-4">
                    <div class="card card-dashed bg-light-info">
                        <div class="card-body text-center py-4">
                            <div class="text-muted fs-8 text-uppercase mb-1">{{ __('accounting.balance_before') }}</div>
                            <div class="fs-4 fw-bold">${fmt(tx.balance_before)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-dashed bg-light-primary">
                        <div class="card-body text-center py-4">
                            <div class="text-muted fs-8 text-uppercase mb-1">{{ __('accounting.balance_after') }}</div>
                            <div class="fs-4 fw-bold">${fmt(tx.balance_after)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-dashed bg-light">
                        <div class="card-body text-center py-4">
                            <div class="text-muted fs-8 text-uppercase mb-1">{{ __('accounting.status') }}</div>
                            <div class="fs-6 fw-bold">${badgeStatus(tx.status)}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function kv(rows) {
        return `<div class="row g-3">${rows.map(([l, v]) => `
            <div class="col-6 col-md-3">
                <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">${esc(l)}</div>
                <div class="fw-semibold text-gray-800 fs-7">${esc(v || '—')}</div>
            </div>`).join('')}</div>`;
    }

    function section(title, icon, body) {
        return `<h6 class="fw-bold text-gray-800 mb-3"><i class="bi ${icon} me-2 text-primary"></i>${esc(title)}</h6>
                <div class="bg-light rounded-3 p-4 mb-5">${body}</div>`;
    }

    function renderInfoBlock(tx, pm) {
        return section('Transaction Info', 'bi-info-circle', kv([
            ['Payment Method', pm.name || '—'],
            ['Type', tx.transaction_type],
            ['Category', tx.transaction_category],
        ]));
    }

    function renderUserBlock(tx, cust) {
        const custName = cust ? `${cust.first_name || ''} ${cust.last_name || ''}`.trim() : '—';
        return section('People', 'bi-person', kv([
            ['Processed by', tx.user?.name || 'System'],
            ['Customer', custName],
        ]));
    }

    function renderDescriptionBlock(tx) {
        if (!tx.description && !tx.notes) return '';
        return section('Description', 'bi-chat-left-text', `
            <div class="text-gray-800 fs-7 mb-2">${esc(tx.description || '—')}</div>
            ${tx.notes ? `<div class="text-muted fs-7"><i class="bi bi-info-circle me-1"></i>${esc(tx.notes)}</div>` : ''}
        `);
    }

    function renderReferenceBlock(tx) {
        const rows = [
            ['Reference table', tx.reference_table],
            ['Reference id', tx.reference_id],
        ];
        if (tx.external_reference) rows.push(['External reference', tx.external_reference]);
        if (tx.bank_reference)     rows.push(['Bank reference', tx.bank_reference]);
        return section('Reference Information', 'bi-link-45deg', kv(rows));
    }

    function renderMetadataBlock(tx) {
        const meta = tx.metadata || {};
        const hasMeta = typeof meta === 'object' && Object.keys(meta).length > 0;
        if (!hasMeta) return '';

        const rows = Object.entries(meta).map(([k, v]) => `
            <tr>
                <td class="fw-bold text-muted fs-8">${esc(k)}</td>
                <td class="fs-7">${esc(typeof v === 'object' ? JSON.stringify(v) : v)}</td>
            </tr>`).join('');

        return section('Metadata', 'bi-braces', `
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle fs-7 mb-0">
                    <thead class="border-bottom border-gray-200 fs-8 fw-bold text-uppercase">
                        <tr><th>Field</th><th>Value</th></tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `);
    }

    function renderActionCluster(tx) {
        return `
            <button type="button" class="btn btn-sm btn-primary d-flex align-items-center" data-drawer-action="print">
                <i class="bi bi-printer me-2"></i>{{ __('accounting.print') }}
            </button>
        `;
    }

    function bindActions(tx) {
        const actions = document.getElementById('transactionDrawerActions');
        if (!actions) return;
        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', () => {
                const a = el.getAttribute('data-drawer-action');
                if (a === 'print') window.print();
            });
        });
    }

    function badgeStatus(status) {
        const colors = {
            COMPLETED: 'success', PENDING: 'warning', FAILED: 'danger',
            CANCELLED: 'secondary', REVERSED: 'dark',
        };
        return `<span class="badge badge-light-${colors[status] || 'secondary'} py-2 px-3">${esc(status)}</span>`;
    }

    function fmt(n) {
        const sym = '{{ currency_symbol() }}';
        return sym + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.TransactionDrawer = {
        open, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.TransactionDrawer;
})();
</script>