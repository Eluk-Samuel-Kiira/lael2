<script>
(function () {
    'use strict';

    // 1. Toggle between Email and WhatsApp
    document.addEventListener('change', function(e) {
        if (e.target.name === 'channel') {
            const form = e.target.closest('form');
            const orderId = form.id.replace('sendToSupplierForm', '');
            const channel = e.target.value;
            
            const emailWrap = document.getElementById(`send-email-field-wrap${orderId}`);
            const phoneWrap = document.getElementById(`send-phone-field-wrap${orderId}`);
            
            if (channel === 'email') {
                emailWrap.classList.remove('d-none');
                phoneWrap.classList.add('d-none');
                form.querySelector('input[name="supplier_email"]').required = true;
                form.querySelector('input[name="supplier_phone"]').required = false;
            } else {
                emailWrap.classList.add('d-none');
                phoneWrap.classList.remove('d-none');
                form.querySelector('input[name="supplier_email"]').required = false;
                form.querySelector('input[name="supplier_phone"]').required = true;
            }
        }
    });

    // 2. Submit to Supplier
    document.addEventListener('click', async function(e) {
        const btn = e.target.closest('[data-send-action]');
        if (!btn) return;
        
        e.preventDefault();
        const orderId = btn.getAttribute('data-send-action');
        const form = document.getElementById(`sendToSupplierForm${orderId}`);
        
        if (!form) return;

        const payload = Object.fromEntries(new FormData(form).entries());
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

        // Basic Validation
        if (payload.channel === 'email' && !payload.supplier_email) {
            window.toastr?.error('{{ __("payments.customer_email_required") }}');
            return;
        }
        if (payload.channel === 'whatsapp' && !payload.supplier_phone) {
            window.toastr?.error('{{ __("payments.customer_phone_required") }}');
            return;
        }

        // Toggle Loading State
        const label = btn.querySelector('.indicator-label');
        const progress = btn.querySelector('.indicator-progress');
        if (label) label.style.visibility = 'hidden';
        if (progress) { progress.classList.remove('d-none'); progress.style.display = 'inline-flex'; }
        btn.disabled = true;

        try {
            const res = await fetch(`/purchase-send/${orderId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });

            const json = await res.json().catch(() => ({}));

            if (res.status === 422) {
                window.toastr?.error(json.message || 'Validation error.');
                return;
            }

            if (!res.ok || json.success === false) {
                throw new Error(json.message || '{{ __("passwords.purchase_order_send_failed") }}');
            }

            window.toastr?.success(json.message || '{{ __("passwords.purchase_order_sent") }}');
            
            // Close the modal
            bootstrap.Modal.getInstance(document.getElementById(`sendToSupplierModal${orderId}`))?.hide();
            
            // Refresh both the table and the drawer
            window.POList?.reload?.();
            window.PODrawer?.reload?.(orderId);

        } catch (err) {
            console.error('[send-supplier] failed:', err);
            window.toastr?.error(err.message);
        } finally {
            if (label) label.style.visibility = '';
            if (progress) { progress.classList.add('d-none'); progress.style.display = 'none'; }
            btn.disabled = false;
        }
    });

})();
</script>


<script>
(function () {
    'use strict';

    // 1. Handle Quantity/Cost changes (using event delegation)
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('receiving-quantity') || e.target.classList.contains('receiving-actual-cost') || e.target.id.startsWith('payment_amount_')) {
            const form = e.target.closest('form');
            if (!form || !form.id.startsWith('receiveItemsForm')) return;
            
            const orderId = form.id.replace('receiveItemsForm', '');
            updateItemsTotal(orderId, form);
        }
    });

    // 2. Handle Tax Preview Click
    document.addEventListener('click', async function(e) {
        const btn = e.target.closest('[data-tax-preview]');
        if (!btn) return;
        
        const orderId = btn.getAttribute('data-tax-preview');
        const form = document.getElementById(`receiveItemsForm${orderId}`);
        const taxableAmount = parseFloat(document.getElementById(`taxable_amount_hidden_${orderId}`).value) || 0;
        const selectedTaxes = Array.from(form.querySelectorAll('.tax-checkbox:checked')).map(cb => cb.value);

        if (selectedTaxes.length === 0 || taxableAmount === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Missing Info',
                text: 'Please select taxes and enter quantities first.',
                confirmButtonColor: '#009ef7'
            });
            return;
        }

        setLoading(btn, true);

        try {
            const res = await fetch('/purchase-orders/calculate-tax-preview', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ taxable_amount: taxableAmount, selected_taxes: selectedTaxes })
            });

            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Calculation failed');

            // Update preview UI
            document.getElementById(`tax_preview_${orderId}`).classList.remove('d-none');
            document.getElementById(`preview_taxable_${orderId}`).textContent = json.data.taxable_amount.toFixed(2);
            document.getElementById(`preview_total_tax_${orderId}`).textContent = json.data.total_tax.toFixed(2);
            document.getElementById(`preview_net_payable_${orderId}`).textContent = json.data.net_payable.toFixed(2);

            document.getElementById(`total_tax_display_${orderId}`).textContent = json.data.total_tax.toFixed(2);
            document.getElementById(`total_payable_display_${orderId}`).textContent = json.data.net_payable.toFixed(2);
            
            document.getElementById(`total_tax_amount_${orderId}`).value = json.data.total_tax;
            document.getElementById(`net_amount_${orderId}`).value = json.data.net_payable;

            updatePaymentMax(orderId, json.data.net_payable);

            // Build breakdown
            const tbody = document.getElementById(`tax_breakdown_body_${orderId}`);
            tbody.innerHTML = json.data.tax_breakdown.map(tax => `
                <tr>
                    <td><strong>${tax.name}</strong><br><small>${tax.code || ''}</small></td>
                    <td>${tax.formatted_rate}</td>
                    <td class="text-end">${tax.amount.toFixed(2)}</td>
                    <td class="text-center">
                        <span class="badge ${tax.is_withholding_tax ? 'badge-light-danger' : 'badge-light-primary'}">
                            ${tax.is_withholding_tax ? 'Deducted (-)' : 'Added (+)'}
                        </span>
                    </td>
                </tr>
            `).join('');

            Swal.fire({
                icon: 'success',
                title: 'Calculated',
                text: 'Tax preview updated successfully.',
                timer: 1500,
                showConfirmButton: false
            });

        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Calculation Failed',
                text: err.message,
                confirmButtonColor: '#f1416c'
            });
        } finally {
            setLoading(btn, false);
        }
    });

    // 3. Submit Receiving
    document.addEventListener('click', async function(e) {
        const btn = e.target.closest('[data-receive-submit]');
        if (!btn) return;

        const orderId = btn.getAttribute('data-receive-submit');
        const status = btn.getAttribute('data-status');
        const form = document.getElementById(`receiveItemsForm${orderId}`);
        
        // Validation
        const hasQuantity = Array.from(form.querySelectorAll('.receiving-quantity')).some(input => parseFloat(input.value) > 0);
        if (!hasQuantity) {
            Swal.fire({
                icon: 'warning',
                title: 'No Items Selected',
                text: 'Please enter a quantity for at least one item.',
                confirmButtonColor: '#009ef7'
            });
            return;
        }

        const paymentAmount = parseFloat(document.getElementById(`payment_amount_${orderId}`)?.value) || 0;
        const paymentMethod = document.getElementById(`payment_method_${orderId}`)?.value;

        if (paymentAmount > 0 && !paymentMethod) {
            Swal.fire({
                icon: 'warning',
                title: 'Payment Method Required',
                text: 'Please select a payment method for the amount entered.',
                confirmButtonColor: '#009ef7'
            });
            return;
        }

        // Build Summary for Swal
        const grossAmount = parseFloat(document.getElementById(`gross_amount_display_${orderId}`).textContent.replace(/,/g, '')) || 0;
        const totalTax = parseFloat(document.getElementById(`total_tax_display_${orderId}`).textContent.replace(/,/g, '')) || 0;
        const totalPayable = parseFloat(document.getElementById(`total_payable_display_${orderId}`).textContent.replace(/,/g, '')) || 0;
        const currency = '{{ currency_symbol() }}';
        const statusLabel = status === 'received' ? 'Mark as Fully Received' : 'Mark as Partially Received';
        
        const summaryHtml = `
            <div class="text-start mt-3">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Gross Amount:</span>
                    <span class="fw-bold">${currency}${grossAmount.toFixed(2)}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Tax:</span>
                    <span class="fw-bold text-danger">${currency}${totalTax.toFixed(2)}</span>
                </div>
                <div class="d-flex justify-content-between border-top pt-2 mb-3">
                    <span class="fw-bold fs-6">Total Payable:</span>
                    <span class="fw-bold fs-5 text-primary">${currency}${totalPayable.toFixed(2)}</span>
                </div>
                ${paymentAmount > 0 ? `
                <div class="alert alert-light-success py-2 mb-0 fs-7">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    Recording payment of ${currency}${paymentAmount.toFixed(2)}
                </div>
                ` : ''}
            </div>
        `;

        Swal.fire({
            title: 'Confirm Receipt',
            html: summaryHtml,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: status === 'received' ? '#50cd89' : '#ffc700',
            cancelButtonColor: '#f1416c',
            confirmButtonText: statusLabel,
            cancelButtonText: 'Cancel',
            showLoaderOnConfirm: true,
            preConfirm: async () => {
                const formData = new FormData(form);
                formData.append('status', status);
                formData.set('payment_amount', String(Math.max(0, paymentAmount)));
                
                if (paymentAmount <= 0) {
                    formData.delete('payment_method_id');
                    formData.delete('payment_date');
                }

                try {
                    const res = await fetch(`/purchase-orders/${orderId}/receive-items`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: formData
                    });

                    const json = await res.json();

                    if (!res.ok || json.success === false) {
                        if (json.requires_confirm) {
                            // Handle over-receipt logic via Swal inside preConfirm
                            const confirmResult = await Swal.fire({
                                title: 'Over-Receipt Warning',
                                html: buildOverReceiptHtml(json.over_receipts),
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#f1416c',
                                cancelButtonColor: '#7e8299',
                                confirmButtonText: 'Yes, Receive More',
                                cancelButtonText: 'Cancel'
                            });

                            if (!confirmResult.isConfirmed) {
                                throw new Error('Cancelled by user');
                            }

                            // Retry with allow flag
                            formData.append('allow_over_receipt', '1');
                            const retry = await fetch(`/purchase-orders/${orderId}/receive-items`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                                body: formData
                            });
                            const retryJson = await retry.json();
                            if (!retry.ok || retryJson.success === false) throw new Error(retryJson.message);
                            return retryJson;
                        }
                        throw new Error(json.message || 'Error receiving items');
                    }
                    return json;
                } catch (err) {
                    return Swal.showValidationMessage(`Request failed: ${err.message}`);
                }
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: result.value.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    bootstrap.Modal.getInstance(document.getElementById(`receiveItemsModal${orderId}`))?.hide();
                    window.POList?.reload?.();
                    window.PODrawer?.reload?.(orderId);
                });
            }
        });
    });

    // --- Helpers ---
    function updateItemsTotal(orderId, form) {
        let itemsTotal = 0;
        
        form.querySelectorAll('.receiving-quantity').forEach(input => {
            const qty = parseFloat(input.value) || 0;
            const row = input.closest('.receive-item-row');
            const actualCostInput = row.querySelector('.receiving-actual-cost');
            const fallbackCost = parseFloat(input.dataset.unitCost) || 0;
            const actualCost = actualCostInput && actualCostInput.value !== '' ? (parseFloat(actualCostInput.value) || 0) : fallbackCost;

            itemsTotal += qty * actualCost;

            if (row) row.style.backgroundColor = qty > 0 ? '#e8f5e9' : '';
            if (actualCostInput) actualCostInput.disabled = qty <= 0;
        });

        document.getElementById(`gross_amount_display_${orderId}`).textContent = itemsTotal.toFixed(2);
        document.getElementById(`taxable_amount_display_${orderId}`).textContent = itemsTotal.toFixed(2);
        document.getElementById(`taxable_amount_hidden_${orderId}`).value = itemsTotal;
        document.getElementById(`total_tax_display_${orderId}`).textContent = '0.00';
        document.getElementById(`total_payable_display_${orderId}`).textContent = itemsTotal.toFixed(2);

        document.getElementById(`total_tax_amount_${orderId}`).value = '0';
        document.getElementById(`net_amount_${orderId}`).value = itemsTotal;

        document.getElementById(`tax_preview_${orderId}`)?.classList.add('d-none');
        updatePaymentMax(orderId, itemsTotal);
    }

    function updatePaymentMax(orderId, payableAmount) {
        const paymentInput = document.getElementById(`payment_amount_${orderId}`);
        if (!paymentInput) return;
        const rounded = Math.max(0, payableAmount || 0);
        paymentInput.setAttribute('max', rounded.toFixed(2));
        const maxDisplay = document.getElementById(`payment_max_display_${orderId}`);
        if (maxDisplay) maxDisplay.textContent = rounded.toFixed(2);
        if ((parseFloat(paymentInput.value) || 0) > rounded) paymentInput.value = rounded.toFixed(2);
    }

    function buildOverReceiptHtml(overReceipts) {
        if (!Array.isArray(overReceipts) || overReceipts.length === 0) return '<p>No details available.</p>';

        const rows = overReceipts.map(r => `
            <tr>
                <td class="text-start">${r.product_name ?? '—'}</td>
                <td class="text-end">${Number(r.ordered).toLocaleString()}</td>
                <td class="text-end">${Number(r.previously_received).toLocaleString()}</td>
                <td class="text-end">${Number(r.receiving_now).toLocaleString()}</td>
                <td class="text-end text-danger fw-bold">+${Number(r.over_by).toLocaleString()}</td>
            </tr>
        `).join('');

        return `
            <div class="text-start mb-3 fs-7">
                <p>The following items exceed the ordered quantity:</p>
                <div class="table-responsive border rounded">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Ordered</th>
                                <th class="text-end">Prev Received</th>
                                <th class="text-end">Now</th>
                                <th class="text-end">Over By</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
                <p class="text-danger mt-3 mb-0 fw-bold">
                    <i class="bi bi-exclamation-triangle me-1"></i> Are you sure you want to proceed?
                </p>
            </div>
        `;
    }

    function setLoading(btn, busy) {
        if (!btn) return;
        const label = btn.querySelector('.indicator-label') || btn;
        const progress = btn.querySelector('.indicator-progress');
        
        if (busy) {
            if (label) label.style.visibility = 'hidden';
            if (progress) { progress.classList.remove('d-none'); progress.style.display = 'inline-flex'; }
            btn.disabled = true;
        } else {
            btn.disabled = false;
            if (label) label.style.visibility = '';
            if (progress) { progress.classList.add('d-none'); progress.style.display = 'none'; }
        }
    }

})();
</script>


<script>
    // 4. Delete Purchase Order
    document.addEventListener('click', async function(e) {
        const btn = e.target.closest('[data-delete-action]');
        if (!btn) return;

        const orderId = btn.getAttribute('data-delete-action');
        const url = btn.getAttribute('data-url');
        
        // Button Loading State
        const label = btn.querySelector('.indicator-label');
        const progress = btn.querySelector('.indicator-progress');
        if (label) label.style.visibility = 'hidden';
        if (progress) { progress.classList.remove('d-none'); progress.style.display = 'inline-flex'; }
        btn.disabled = true;

        try {
            const res = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const json = await res.json().catch(() => ({}));

            if (!res.ok || json.success === false) {
                throw new Error(json.message || 'Failed to delete purchase order.');
            }

            // Success
            window.toastr?.success(json.message || 'Purchase order deleted.');
            
            // Close Modal
            bootstrap.Modal.getInstance(document.getElementById(`deletePurchaseModal${orderId}`))?.hide();
            
            // Close Drawer
            window.PODrawer?.close?.();
            
            // Refresh List
            window.POList?.reload?.();

        } catch (err) {
            console.error('[delete-po] failed:', err);
            window.toastr?.error(err.message);
            
            // Restore button if there's an error so they can try again
            btn.disabled = false;
            if (label) label.style.visibility = '';
            if (progress) { progress.classList.add('d-none'); progress.style.display = 'none'; }
        }
    });
</script>