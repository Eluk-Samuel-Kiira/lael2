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
        /* ─── Header metrics ───────────────────────────────────── */
        const metrics = `
            <div class="row g-3 mb-6">
                <div class="col-6 col-md-3">
                    <div class="bg-light-secondary rounded-3 p-3 text-center h-100">
                        <div class="text-muted fs-8 text-uppercase mb-1">PO Total</div>
                        <div class="fw-bold text-gray-800 fs-4">${esc(order.total_formatted || '—')}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-light-info rounded-3 p-3 text-center h-100">
                        <div class="text-muted fs-8 text-uppercase mb-1">Received</div>
                        <div class="fw-bold text-info fs-4">${esc(order.received_total_formatted || '—')}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-light-success rounded-3 p-3 text-center h-100">
                        <div class="text-muted fs-8 text-uppercase mb-1">Paid</div>
                        <div class="fw-bold text-success fs-4">${esc(order.total_paid_formatted || '—')}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-light-${order.balance > 0 ? 'warning' : 'success'} rounded-3 p-3 text-center h-100">
                        <div class="text-muted fs-8 text-uppercase mb-1">Balance</div>
                        <div class="fw-bold text-${order.balance > 0 ? 'warning' : 'success'} fs-4">${esc(order.balance_formatted || '—')}</div>
                    </div>
                </div>
            </div>
        `;

        /* ─── PO + Payment overview ────────────────────────────── */
        const overview = `
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-4">
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('passwords.supplier') }}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(order.supplier_name || '—')}</div>
                        ${order.supplier_contact ? `<div class="text-muted fs-8">${esc(order.supplier_contact)}</div>` : ''}
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
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Payment status</div>
                        <span class="badge badge-light-${esc(order.payment_status_color || 'secondary')}">
                            ${esc(order.payment_status_label || 'Pending')}
                        </span>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Payment method</div>
                        <div class="fw-semibold text-gray-800 fs-7">
                            ${esc(order.payment_method || 'Not specified')}
                            ${order.payment_date ? `<span class="text-muted fs-8 d-block">${esc(order.payment_date)}</span>` : ''}
                        </div>
                    </div>
                </div>
                ${order.payment_reference ? `
                    <div class="text-muted fs-8 mt-3">
                        <i class="bi bi-link-45deg me-1"></i>${esc(order.payment_reference)}
                    </div>
                ` : ''}
            </div>
        `;

        /* ─── Items table ──────────────────────────────────────── */
        const itemsTable = `
            <h6 class="fw-bold text-gray-800 mb-3">Items</h6>
            <div class="table-responsive border rounded-3 mb-6">
                <table class="table table-row-dashed align-middle mb-0">
                    <thead>
                        <tr class="fw-bold text-muted bg-light fs-8">
                            <th class="ps-3">Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-center">Received</th>
                            <th class="text-end">Unit Cost</th>
                            <th class="text-end pe-3">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${(order.items || []).map(item => {
                            const statusColor = item.payment_status === 'paid' ? 'success'
                                            : item.payment_status === 'partial' ? 'info' : 'warning';
                            return `
                            <tr>
                                <td class="ps-3 fs-7">
                                    <div class="fw-bold text-gray-800">${esc(item.product_name || '—')}</div>
                                    <div class="text-muted fs-8">SKU: ${esc(item.sku || '—')}</div>
                                </td>
                                <td class="text-center fs-7">
                                    ${fmt(item.quantity, 0)}
                                    ${item.balance_quantity > 0 ? `<div class="text-warning fs-8">${fmt(item.balance_quantity, 0)} pending</div>` : ''}
                                </td>
                                <td class="text-center fs-7">
                                    ${item.received_quantity > 0
                                        ? `<span class="text-success fw-bold">${fmt(item.received_quantity, 0)}</span>`
                                        : `<span class="text-muted">0</span>`}
                                </td>
                                <td class="text-end fs-7">${esc(CURRENCY)}${fmt(item.unit_cost, 2)}</td>
                                <td class="text-end pe-3 fs-7 fw-bold text-gray-800">
                                    ${esc(CURRENCY)}${fmt(item.total_cost, 2)}
                                    <div class="badge badge-light-${statusColor} fs-8 mt-1">${esc(item.payment_status)}</div>
                                </td>
                            </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;

        /* ─── Receipts ─────────────────────────────────────────── */
        const receiptsBlock = (order.receipts && order.receipts.length > 0)
            ? `
            <h6 class="fw-bold text-gray-800 mb-3">Receipts (${order.receipts.length})</h6>
            <div class="mb-6">
                ${order.receipts.map(r => `
                    <div class="border rounded-3 p-3 mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <div>
                                <div class="fw-bold fs-7">Receipt #${r.id}</div>
                                <div class="text-muted fs-8">
                                    ${esc(r.received_at || '—')} · by ${esc(r.received_by || '—')}
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge badge-light-primary">${fmt(r.total_quantity, 2)} units</span>
                                ${r.batch_number ? `<div class="text-muted fs-8 mt-1">Batch: ${esc(r.batch_number)}</div>` : ''}
                            </div>
                        </div>
                        ${(r.lines || []).length > 0 ? `
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                    ${r.lines.map(l => `
                                        <tr class="fs-8">
                                            <td class="ps-0">${esc(l.product_name || '—')}</td>
                                            <td class="text-end">${fmt(l.quantity, 0)}</td>
                                            <td class="text-end">${esc(CURRENCY)}${fmt(l.unit_cost, 2)}</td>
                                            <td class="text-end">
                                                <span class="badge badge-light-${l.payment_status === 'paid' ? 'success' : l.payment_status === 'partial' ? 'info' : 'warning'}">
                                                    ${esc(l.payment_status)}
                                                </span>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        ` : ''}
                    </div>
                `).join('')}
            </div>
            ` : '';

        /* ─── Tax liabilities ──────────────────────────────────── */
        const taxBlock = (order.tax_liabilities && order.tax_liabilities.length > 0)
            ? `
            <h6 class="fw-bold text-gray-800 mb-3">Tax Liabilities</h6>
            <div class="table-responsive border rounded-3 mb-6">
                <table class="table table-row-dashed align-middle mb-0">
                    <thead>
                        <tr class="fw-bold text-muted bg-light fs-8">
                            <th class="ps-3">Tax</th>
                            <th class="text-end">Taxable</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-3">Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${order.tax_liabilities.map(t => `
                            <tr class="fs-8">
                                <td class="ps-3">
                                    <div class="fw-bold">${esc(t.tax_name)}</div>
                                    <div class="text-muted">${t.tax_rate}%${t.is_withholding ? ' (withholding)' : ''}</div>
                                </td>
                                <td class="text-end">${esc(CURRENCY)}${fmt(t.taxable_amount, 2)}</td>
                                <td class="text-end fw-bold">${esc(CURRENCY)}${fmt(t.tax_amount, 2)}</td>
                                <td class="text-center">
                                    <span class="badge badge-light-${t.status === 'paid' ? 'success' : t.status === 'pending' ? 'warning' : 'secondary'}">
                                        ${esc(t.status)}
                                    </span>
                                </td>
                                <td class="text-end pe-3">${esc(t.due_date || '—')}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            ` : '';

        /* ─── Notes as a timeline ──────────────────────────────── */
        const notesBlock = order.notes
            ? `
            <h6 class="fw-bold text-gray-800 mb-3">Activity</h6>
            <div class="border rounded-3 p-3 mb-6" style="max-height: 340px; overflow-y: auto;">
                ${renderNotes(order.notes)}
            </div>
            `
            : '';

        return metrics + overview + itemsTable + receiptsBlock + taxBlock + notesBlock;
    }

    /* ─── Notes parser — safe against nested brackets ───────────── */
    function renderNotes(raw) {
        const lines = String(raw).split('\n');

        return lines
            .filter(line => line.trim() !== '')
            .map(line => {
                // Each note starts with "[YYYY-MM-DD HH:MM:SS] ..."
                const match = line.match(/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s*(.*)$/);
                if (match) {
                    return `
                        <div class="d-flex align-items-start gap-2 mb-3 fs-8">
                            <div class="text-muted text-nowrap" style="min-width: 130px;">
                                ${esc(match[1])}
                            </div>
                            <div class="text-gray-800 flex-grow-1">${esc(match[2])}</div>
                        </div>
                    `;
                }
                return `
                    <div class="text-gray-800 fs-8 mb-2">${esc(line)}</div>
                `;
            })
            .join('');
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