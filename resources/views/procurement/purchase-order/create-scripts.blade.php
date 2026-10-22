<script>
(function () {
    'use strict';

    /* ────────────────────────────────────────────────────────────
       1. DYNAMIC ROW MANAGEMENT
       ──────────────────────────────────────────────────────────── */
    let itemCount = 1;

    // Add Item
    document.getElementById('addPoItemBtn')?.addEventListener('click', () => {
        const container = document.getElementById('purchase_order_items_container');
        const template = container.querySelector('.purchase-order-item');
        const clone = template.cloneNode(true);

        // Update IDs and Names for the clone
        clone.id = `item_${itemCount}`;
        
        const searchInput = clone.querySelector('.product-typable-input');
        searchInput.setAttribute('data-item-index', itemCount);
        searchInput.setAttribute('list', `product_list_${itemCount}`);
        searchInput.value = ''; // clear value
        
        const hiddenInput = clone.querySelector('input[type="hidden"]');
        hiddenInput.id = `product_id_${itemCount}`;
        hiddenInput.setAttribute('name', `items[${itemCount}][product_variant_id]`);
        hiddenInput.value = '';

        const datalist = clone.querySelector('datalist');
        datalist.id = `product_list_${itemCount}`;

        const qtyInput = clone.querySelector('.item-quantity');
        qtyInput.setAttribute('name', `items[${itemCount}][quantity]`);
        qtyInput.value = '1';

        const costInput = clone.querySelector('.item-unit-cost');
        costInput.setAttribute('name', `items[${itemCount}][unit_cost]`);
        costInput.value = '0.00';

        clone.querySelector('.item-total').value = '0.00';

        // Enable remove buttons
        container.querySelectorAll('.remove-item-btn').forEach(btn => btn.disabled = false);
        clone.querySelector('.remove-item-btn').disabled = false;

        container.appendChild(clone);
        itemCount++;
    });

    // Remove Item
    document.getElementById('purchase_order_items_container')?.addEventListener('click', (e) => {
        if (e.target.closest('.remove-item-btn')) {
            const rows = document.querySelectorAll('.purchase-order-item');
            if (rows.length > 1) {
                e.target.closest('.purchase-order-item').remove();
                updateTotals();
            }
        }
    });

    /* ────────────────────────────────────────────────────────────
       2. CALCULATIONS & AUTO-FILL
       ──────────────────────────────────────────────────────────── */
    document.getElementById('purchase_order_items_container')?.addEventListener('input', (e) => {
        // Product Selection
        if (e.target.classList.contains('product-typable-input')) {
            const val = e.target.value;
            const list = document.getElementById(e.target.getAttribute('list'));
            const option = Array.from(list.options).find(o => o.value === val);
            const row = e.target.closest('.purchase-order-item');
            
            if (option && option.dataset.id) {
                row.querySelector('input[type="hidden"]').value = option.dataset.id;
                const cost = parseFloat(option.dataset.costPrice) || 0;
                if (cost > 0) row.querySelector('.item-unit-cost').value = cost.toFixed(2);
            } else {
                row.querySelector('input[type="hidden"]').value = '';
            }
            updateTotals();
        }
        
        // Quantity or Cost change
        if (e.target.classList.contains('item-quantity') || e.target.classList.contains('item-unit-cost')) {
            updateTotals();
        }
    });

    function updateTotals() {
        let subtotal = 0;
        document.querySelectorAll('.purchase-order-item').forEach(row => {
            const qty = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const cost = parseFloat(row.querySelector('.item-unit-cost').value) || 0;
            const total = qty * cost;
            
            row.querySelector('.item-total').value = total.toFixed(2);
            subtotal += total;
        });

        const curr = '{{ currency_symbol() }}';
        document.getElementById('order_subtotal').textContent = curr + subtotal.toFixed(2);
        document.getElementById('order_tax_total').textContent = curr + '0.00';
        document.getElementById('order_grand_total').textContent = curr + subtotal.toFixed(2);
    }

    /* ────────────────────────────────────────────────────────────
       3. SUBMISSION
       ──────────────────────────────────────────────────────────── */
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-po-submit]');
        if (!btn) return;

        e.preventDefault();
        const form = document.getElementById('kt_modal_add_purchase_order_form');
        const url = btn.getAttribute('data-url');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('input[name="_token"]').value;

        // Build payload
        const formData = new FormData(form);
        const payload = { _method: 'POST', items: [] };

        for (let [key, value] of formData.entries()) {
            if (key.startsWith('items[')) {
                const matches = key.match(/items\[(\d+)\]\[(.+)\]/);
                if (matches) {
                    const idx = matches[1];
                    const field = matches[2];
                    if (!payload['items'][idx]) payload['items'][idx] = {};
                    payload['items'][idx][field] = value;
                }
            } else {
                payload[key] = value;
            }
        }

        // Filter out empty item rows
        payload['items'] = payload['items'].filter(item => item && item.product_variant_id);

        if (payload['items'].length === 0) {
            window.toastr?.error('Please add at least one valid product.');
            return;
        }

        setLoading(btn, true);

        try {
            const res = await fetch(url, {
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

            if (!res.ok || json.success === false) throw new Error(json.message || 'Error');

            window.toastr?.success(json.message || '{{ __("auth._created") }}');
            
            // Close and Reset
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_purchase_order'))?.hide();
            form.reset();
            
            // Reset rows
            const container = document.getElementById('purchase_order_items_container');
            if (container) {
                const firstRow = container.querySelector('.purchase-order-item');
                container.innerHTML = '';
                container.appendChild(firstRow);
                itemCount = 1;
                updateTotals();
            }

            // Refresh list
            window.POList?.reload?.();

        } catch (err) {
            console.error('[create-po] failed:', err);
            window.toastr?.error(err.message);
        } finally {
            setLoading(btn, false);
        }
    });

    /* ────────────────────────────────────────────────────────────
       4. HELPERS
       ──────────────────────────────────────────────────────────── */
    function setLoading(btn, busy) {
        if (!btn) return;
        if (getComputedStyle(btn).position === 'static') btn.style.position = 'relative';

        const label = btn.querySelector('.indicator-label');
        const progress = btn.querySelector('.indicator-progress');

        if (progress && !progress.dataset.overlayInit) {
            Object.assign(progress.style, {
                position: 'absolute', top: '50%', left: '50%',
                transform: 'translate(-50%, -50%)',
                alignItems: 'center', gap: '0.5rem',
                whiteSpace: 'nowrap', pointerEvents: 'none',
            });
            progress.dataset.overlayInit = '1';
        }

        if (busy) {
            btn.style.minWidth = (btn.offsetWidth + 40) + 'px';
            if (label) label.style.visibility = 'hidden';
            if (progress) { progress.classList.remove('d-none'); progress.style.display = 'inline-flex'; }
            btn.disabled = true;
        } else {
            btn.disabled = false;
            btn.style.minWidth = '';
            if (label) label.style.visibility = '';
            if (progress) { progress.classList.add('d-none'); progress.style.display = 'none'; }
        }
    }
})();
</script>