<script>
window.PODrawer = (function () {
    'use strict';

    const URL_DETAILS = '{{ route("purchase_orders.details", ["id" => "__ID__"]) }}';
    const URL_MODALS  = '{{ route("purchase_orders.modals", ["id" => "__ID__"]) }}'; // <-- NEW
    const CURRENCY = '{{ currency_symbol() }}';

    let currentPayload = null;
    let bs = null;

    function getBs() {
        const el = document.getElementById('poDrawer');
        if (!el) return null;
        bs = bs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return bs;
    }

    async function open(id) {
        const inst = getBs();
        if (!inst) return;

        showSkeleton();
        inst.show();

        try {
            const res = await fetch(URL_DETAILS.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed to load');
            currentPayload = json;
            render(json);
        } catch (err) {
            console.error('[po-drawer] load failed:', err);
            renderError(err.message);
        }
    }

    async function reload(id) {
        if (!id) return;
        try {
            const res = await fetch(URL_DETAILS.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const json = await res.json();
            if (!json.success) return;
            currentPayload = json;
            render(json);
        } catch (err) {
            console.error('[po-drawer] reload failed:', err);
        }
    }

    function close() { getBs()?.hide(); }

    function showSkeleton() {
        document.getElementById('poDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('poDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('poDrawerFooter')?.classList.add('d-none');
        const v = document.getElementById('poDrawerNumber');
        if (v) v.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('poDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('poDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(payload) {
        const order = payload.order;
        
        document.getElementById('poDrawerNumber').textContent = order.po_number;
        document.getElementById('poDrawerSkeleton')?.classList.add('d-none');
        
        const body = document.getElementById('poDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = renderView(order);

        const footer = document.getElementById('poDrawerFooter');
        const actions = document.getElementById('poDrawerActions');
        
        actions.innerHTML = renderActionBar(order, payload.permissions || {});
        footer.classList.remove('d-none');

        bindActions(order.id); // <-- Bind actions with the order ID
    }

    /* ────────────────────────────────────────────────────────────
       ACTION HANDLERS
       ──────────────────────────────────────────────────────────── */
    function bindActions(orderId) {
        const actions = document.getElementById('poDrawerActions');
        if (!actions) return;
        
        actions.querySelectorAll('[data-po-action]').forEach(el => {
            el.addEventListener('click', async () => {
                const action = el.getAttribute('data-po-action');
                
                if (action === 'view') {
                    await openViewPanel(orderId);
                    return;
                }

                if (action === 'send' || action === 'receive' || action === 'delete') {
                    await openPOModal(orderId, action);
                    return;
                }

                // Handle simple status updates (Submit, Approve, Cancel)
                if (action === 'submit' || action === 'approve' || action === 'cancel') {
                    
                    let url = '';
                    let status = '';
                    let confirmColor = '#6c757d';
                    
                    if (action === 'submit') {
                        url = `/purchase-status/${orderId}`;
                        status = 'pending_approval';
                        confirmColor = '#ffc107';
                    } else if (action === 'approve') {
                        url = `/purchase-approve/${orderId}`;
                        status = 'approved';
                        confirmColor = '#25ae07ff';
                    } else if (action === 'cancel') {
                        url = `/purchase-cancel/${orderId}`;
                        status = 'cancelled';
                        confirmColor = '#dc3545';
                    }

                    // Use SweetAlert if available, otherwise fallback to native confirm
                    if (typeof Swal !== 'undefined') {
                        const result = await Swal.fire({
                            title: `Confirm ${action}?`,
                            text: `Are you sure you want to ${action} this Purchase Order?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: confirmColor,
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Yes, proceed',
                            cancelButtonText: 'Cancel',
                            showLoaderOnConfirm: true,
                            preConfirm: async () => {
                                return await updatePOStatus(orderId, url, status);
                            }
                        });
                        
                        if (result.isConfirmed && result.value?.success) {
                            window.toastr?.success(result.value.message);
                            window.POList?.reload?.();
                            window.PODrawer?.reload?.(orderId);
                        } else if (result.value?.message) {
                            window.toastr?.error(result.value.message);
                        }
                    } else {
                        // Fallback
                        if (confirm(`Are you sure you want to ${action} this PO?`)) {
                            const res = await updatePOStatus(orderId, url, status);
                            if (res.success) {
                                window.toastr?.success(res.message);
                                window.POList?.reload?.();
                                window.PODrawer?.reload?.(orderId);
                            } else {
                                window.toastr?.error(res.message);
                            }
                        }
                    }
                    
                    return; 
                }
            });
        });
    }

    // Helper to handle the fetch request
    async function updatePOStatus(orderId, url, status) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ status: status })
            });
            
            const json = await res.json().catch(() => ({}));
            
            if (!res.ok || json.success === false) {
                throw new Error(json.message || 'Action failed');
            }
            
            return json; // Return the success response
        } catch (err) {
            console.error(`[po-drawer] ${status} failed:`, err);
            return { success: false, message: err.message };
        }
    }

    async function openViewPanel(orderId) {
        const body = document.getElementById('poDrawerContent');
        const footer = document.getElementById('poDrawerFooter');
        
        // Show loading state
        body.innerHTML = '<div class="text-center py-8"><span class="spinner-border text-primary"></span></div>';
        footer.classList.add('d-none');

        try {
            const res = await fetch(URL_MODALS.replace('__ID__', orderId), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            if (!res.ok) throw new Error('Failed to load view');
            const html = await res.text();
            
            // Extract ONLY the view modal content from the fetched HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            // Find the viewPurchase modal
            const viewModal = tempDiv.querySelector(`#viewPurchase${orderId}`);
            if (!viewModal) throw new Error('View modal not found in HTML');

            // Extract the modal-body content
            const modalBody = viewModal.querySelector('.modal-body');
            
            // Inject into the drawer body
            body.innerHTML = `
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h5 class="fw-bold text-gray-800 mb-0">Purchase Order Details</h5>
                    <button type="button" class="btn btn-sm btn-light" id="backToPOView">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </button>
                </div>
                ${modalBody.innerHTML}
            `;

            // Hide the original footer since the view is now the main content
            footer.classList.add('d-none');

            // Bind the Back button
            document.getElementById('backToPOView')?.addEventListener('click', () => {
                window.PODrawer.reload(orderId);
            });

        } catch (err) {
            console.error('[po-drawer] View load failed:', err);
            body.innerHTML = `<div class="alert alert-danger">Could not load details.</div>`;
        }
    }

    async function openPOModal(orderId, action) {
        const container = document.getElementById('poModalsContainer');
        
        try {
            // 1. Fetch modals HTML if not already loaded for this order
            if (container.dataset.loadedOrder !== String(orderId)) {
                container.innerHTML = '<div class="text-center py-5"><span class="spinner-border text-primary"></span></div>';
                
                const res = await fetch(URL_MODALS.replace('__ID__', orderId), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                if (!res.ok) throw new Error('Failed to load modals');
                const html = await res.text();
                
                container.innerHTML = html;
                container.dataset.loadedOrder = String(orderId);
            }

            // 2. Map action to the actual modal ID prefix
            let modalPrefix = '';
            if (action === 'send') modalPrefix = 'sendToSupplierModal';
            if (action === 'receive') modalPrefix = 'receiveItemsModal';
            if (action === 'delete') modalPrefix = 'deletePurchaseModal';

            // 3. Find the modal element
            const modalEl = document.getElementById(`${modalPrefix}${orderId}`);
            if (!modalEl) {
                console.error(`[po-drawer] Modal element #${modalPrefix}${orderId} not found. Check that the modal ID matches.`);
                return;
            }

            // 4. Move to body (to prevent z-index stacking issues with the drawer) and show
            if (modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }

            const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInstance.show();

        } catch (err) {
            console.error('[po-drawer] Failed to open modal:', err);
            window.toastr?.error?.('Could not load the requested form.');
        }
    }


    function renderView(order) {
        return `
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-4">
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('passwords.supplier') }}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(order.supplier_name || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('passwords.location') }}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(order.location_name || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('auth._status') }}</div>
                        <span class="badge badge-light-${esc(order.status_badge)}">${esc(order.status_label)}</span>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('passwords.expected_delivery') }}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(order.expected_delivery_date || '—')}</div>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold text-gray-800 mb-3">{{ __('passwords.items') }}</h6>
            <div class="table-responsive border rounded-3 mb-6">
                <table class="table table-row-dashed align-middle mb-0">
                    <thead>
                        <tr class="fw-bold text-muted bg-light fs-8">
                            <th class="ps-3">{{ __('pagination._products') }}</th>
                            <th class="text-center">{{ __('pagination.quantity') }}</th>
                            <th class="text-end pe-3">{{ __('pagination._total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${order.items.map(item => `
                            <tr>
                                <td class="ps-3 fs-7">
                                    <div class="fw-bold text-gray-800">${esc(item.product_name || '—')}</div>
                                    <div class="text-muted fs-8">SKU: ${esc(item.sku || '—')}</div>
                                </td>
                                <td class="text-center fs-7">
                                    ${fmt(item.quantity, 0)}
                                    ${item.received_quantity > 0 ? `<div class="text-success fs-8">${fmt(item.received_quantity, 0)} rcvd</div>` : ''}
                                </td>
                                <td class="text-end fs-7 fw-bold text-gray-800 pe-3">${CURRENCY}${fmt(item.total_cost, 2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>

            <div class="bg-light-success rounded-3 p-4 mb-6">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-gray-800">{{ __('pagination._total') }}</span>
                    <span class="fs-4 fw-bold text-success">${esc(order.total_formatted)}</span>
                </div>
            </div>
            
            ${order.notes ? `
            <h6 class="fw-bold text-gray-800 mb-3">Notes</h6>
            <div class="text-muted fs-7 p-3 bg-light rounded-3">
                ${String(order.notes)
                    .split('[')
                    .filter(Boolean)
                    .map(note => `[${esc(note.trim())}`)
                    .join('<br><br>')}
            </div>
            ` : ''}
        `;
    }

    function renderActionBar(order, perms) {
        const btns = [];
        const mk = (action, icon, label, tone) => `
            <button type="button" class="btn btn-sm btn-light-${tone} d-inline-flex align-items-center gap-1" data-po-action="${action}">
                <i class="bi ${icon} fs-6"></i>
                <span class="fs-7 fw-semibold">${label}</span>
            </button>
        `;
        

        // 1. View Details Modal
        btns.push(mk('view', 'bi-eye', '{{ __("auth._view") }}', 'primary'));

        // 2. Status Actions
        if (perms.can_submit)  btns.push(mk('submit', 'bi-send', '{{ __("passwords.submit_approval") }}', 'warning'));
        if (perms.can_approve) btns.push(mk('approve', 'bi-check-circle', '{{ __("passwords.approve") }}', 'success'));
        if (perms.can_send)    btns.push(mk('send', 'bi-envelope', '{{ __("passwords.send_supplier") }}', 'primary'));
        if (perms.can_receive) btns.push(mk('receive', 'bi-box-seam', '{{ __("passwords.receive_items") }}', 'info'));
        if (perms.can_cancel)  btns.push(mk('cancel', 'bi-x-circle', '{{ __("passwords.cancel") }}', 'danger'));
        if (perms.can_delete)  btns.push(mk('delete', 'bi-trash', '{{ __("auth._delete") }}', 'danger'));

        if (!btns.length) return '';
        return `<div class="d-flex align-items-center gap-2">${btns.join('')}</div>`;
    }

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML;
    }
    function fmt(n, d = 0) {
        return Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
    }

    window.PODrawer = { open, close, reload };
    return window.PODrawer;
})();
</script>