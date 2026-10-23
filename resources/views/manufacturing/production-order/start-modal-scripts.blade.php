{{-- resources/views/manufacturing/production-order/start-modal-scripts.blade.php --}}
@once
@push('scripts')
<script>
window.POStart = (function () {
    'use strict';

    const MODAL_ID = 'startProductionModalShared';
    const ROUTES = {
        edit:   (id) => `/production-orders/${id}/edit`,
        start:  (id) => `/production-orders/${id}/start-with-payment`,
    };

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || '{{ csrf_token() }}';
    }

    function getModalEl() {
        return document.getElementById(MODAL_ID);
    }

    function currentOrderId() {
        return parseInt(getModalEl()?.dataset?.orderId || '0', 10);
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    function fmtMoney(n) {
        return '{{ currency_symbol() }}' + Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function fmtNum(n, decimals = 2) {
        return Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    }

    function toggleBusy(btn, busy) {
        if (!btn) return;
        btn.disabled = busy;
        const label    = btn.querySelector('.indicator-label');
        const progress = btn.querySelector('.indicator-progress');
        if (label)    label.style.display    = busy ? 'none' : 'inline';
        if (progress) progress.style.display = busy ? 'inline' : 'none';
    }

    // ── Render inputs preview ───────────────────────────────────
    function renderInputsPreview(inputs) {
        if (!inputs || inputs.length === 0) {
            return '<div class="text-muted fs-7">No inputs on this order.</div>';
        }

        const rows = inputs.map(input => `
            <div class="d-flex align-items-start justify-content-between py-3 border-bottom border-gray-300 border-dashed">
                <div class="d-flex flex-column me-4">
                    <span class="fw-bold text-gray-800">${escapeHtml(input.product_variant_name || 'N/A')}</span>
                    ${input.product_variant_sku ? `<small class="text-muted">SKU: ${escapeHtml(input.product_variant_sku)}</small>` : ''}
                </div>
                <div class="d-flex flex-column text-end">
                    <span class="fw-bold">${fmtNum(input.planned_quantity, 2)}</span>
                    <small class="text-muted">${escapeHtml(input.unit || '')}</small>
                </div>
            </div>
        `).join('');

        return rows;
    }

    // ── Populate and show ───────────────────────────────────────
    async function openForOrder(orderId) {
        if (!orderId) return;

        const modalEl = getModalEl();
        if (!modalEl) {
            console.warn('[POStart] shared start modal not found');
            return;
        }

        modalEl.dataset.orderId = orderId;

        // Loading state in the modal body
        modalEl.querySelector('.po-start-number').textContent    = '…';
        modalEl.querySelector('.po-start-location').textContent  = '';
        modalEl.querySelector('.po-start-inputs-preview').innerHTML = '<div class="text-muted fs-7">Loading inputs…</div>';
        modalEl.querySelector('.po-start-input-count').textContent = '0';

        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        try {
            const res = await fetch(ROUTES.edit(orderId), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();
            if (!data.success || !data.order) {
                window.toastr?.error?.(data.message || '{{ __("passwords.failed_to_load_order") }}');
                bootstrap.Modal.getInstance(modalEl)?.hide();
                return;
            }

            const order = data.order;

            // Header
            modalEl.querySelector('.po-start-number').textContent   = order.production_number || '';
            modalEl.querySelector('.po-start-location').textContent = order.location_name || '';

            // Inputs preview
            modalEl.querySelector('.po-start-inputs-preview').innerHTML = renderInputsPreview(order.inputs || []);
            modalEl.querySelector('.po-start-input-count').textContent  = (order.inputs || []).length;

            // Estimated cost
            const estimatedCost = parseFloat(order.estimated_cost || 0);
            modalEl.querySelector('.po-start-estimated').textContent       = fmtMoney(estimatedCost);
            modalEl.querySelector('.po-start-estimated-hidden').value      = estimatedCost;
            modalEl.querySelector('.po-start-submit-cost').textContent     = estimatedCost > 0 ? fmtMoney(estimatedCost) : '';

            // Toggle payment block visibility
            const paymentBlock = modalEl.querySelector('.po-start-payment-block');
            const paymentNotice = modalEl.querySelector('.po-start-payment-notice');
            const paymentTitle = modalEl.querySelector('.po-start-payment-title');

            if (estimatedCost > 0) {
                paymentBlock.classList.remove('d-none');
                paymentTitle.innerHTML = '<i class="bi bi-credit-card text-primary me-2 fs-4"></i>{{ __("payments.payment_information") }}';
                paymentNotice.innerHTML = `
                    <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed mb-5 p-3">
                        <i class="bi bi-exclamation-triangle fs-3 text-warning me-3"></i>
                        <div class="fs-7 text-gray-700 fw-semibold">
                            {{ __('passwords.start_production_payment_info') }}
                        </div>
                    </div>
                `;
                modalEl.querySelector('.po-start-withdrawal').value = estimatedCost.toFixed(2);
            } else {
                paymentBlock.classList.add('d-none');
                paymentTitle.innerHTML = '<i class="bi bi-check-circle text-success me-2 fs-4"></i>{{ __("passwords.production_information") }}';
                paymentNotice.innerHTML = `
                    <div class="notice d-flex bg-light-success rounded border-success border border-dashed mb-5 p-3">
                        <i class="bi bi-check-circle fs-3 text-success me-3"></i>
                        <div class="fs-7 text-gray-700 fw-semibold">
                            {{ __('passwords.start_production_no_payment_info') }}
                        </div>
                    </div>
                `;
                modalEl.querySelector('.po-start-withdrawal').value = 0;
            }

            // Reset notes
            modalEl.querySelector('textarea[name="notes"]').value = '';
            modalEl.querySelector('select[name="payment_method_id"]').value = '';

        } catch (err) {
            console.error('[POStart] load failed:', err);
            window.toastr?.error?.('{{ __("passwords.failed_to_load_order") }}');
            bootstrap.Modal.getInstance(modalEl)?.hide();
        }
    }

    // ── Submit ──────────────────────────────────────────────────

    async function submit() {
        const modalEl = getModalEl();
        if (!modalEl) return;

        const orderId = currentOrderId();
        if (!orderId) return;

        const btn  = modalEl.querySelector('.po-start-submit');
        const form = modalEl.querySelector('.po-start-form');
        const notes = form.querySelector('textarea[name="notes"]')?.value || null;

        toggleBusy(btn, true);

        try {
            const res = await fetch(ROUTES.start(orderId), {
                method: 'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':     csrfToken(),
                },
                body: JSON.stringify({ notes }),
            });

            const json = await res.json();

            if (json.success) {
                window.toastr?.success?.(json.message || '{{ __("passwords.production_started") }}');
                bootstrap.Modal.getInstance(modalEl)?.hide();
                window.ProductionPage?.reload?.();
            } else {
                const first = json.errors && Object.values(json.errors)[0]?.[0];
                window.toastr?.error?.(first || json.message || '{{ __("passwords.error") }}');
            }
        } catch (err) {
            console.error('[POStart] submit failed:', err);
            window.toastr?.error?.('{{ __("passwords.error") }}');
        } finally {
            toggleBusy(btn, false);
        }
    }

    // ── Delegation ──────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        if (e.target.closest('.po-start-submit')) {
            e.preventDefault();
            submit();
        }
    });

    document.addEventListener('hidden.bs.modal', function (e) {
        if (e.target.id !== MODAL_ID) return;
        e.target.querySelector('.po-start-inputs-preview').innerHTML = '';
        e.target.querySelector('textarea[name="notes"]').value = '';
    });

    return { openForOrder, submit, currentOrderId };
})();

// Register with ProductionActions
window.ProductionActions = window.ProductionActions || {};
window.ProductionActions.start = function (orderId) {
    window.POStart.openForOrder(orderId);
};
</script>
@endpush
@endonce