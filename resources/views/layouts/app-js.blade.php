<!-- important imports for laravel liveblades -->
<!-- <script src="{{ asset('blade-live/forms/forms.min.js') }}" type="module"></script> -->
<!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->

<style>
    /* ── Google/YouTube-style top progress bar ── */
    #spa-topbar {
        position: fixed;
        top: 0; left: 0;
        width: 0%;
        height: 3px;
        background: linear-gradient(90deg, #4f46e5, #0ea5e9, #4f46e5);
        background-size: 200% 100%;
        z-index: 999999;
        display: none;
        animation: spa-shimmer 1.4s linear infinite;
        border-radius: 0 2px 2px 0;
        box-shadow: 0 0 8px rgba(79, 70, 229, 0.6);
        transition: width 0.25s ease;
    }
    #spa-topbar.active { display: block; }
    #spa-topbar::after {
        content: '';
        position: absolute;
        right: 0; top: 50%;
        transform: translateY(-50%);
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #0ea5e9;
        box-shadow: 0 0 10px 3px rgba(14, 165, 233, 0.7);
    }
    @keyframes spa-shimmer {
        0%   { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* ── Circular loader with message (only shows on slow loads > 700ms) ── */
    #spa-circular {
        position: fixed;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        z-index: 999998;
        display: none;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        padding: 20px 28px;
        border-radius: 14px;
        box-shadow: 0 20px 40px -8px rgba(0,0,0,0.12), 0 0 0 1px rgba(0,0,0,0.04);
        align-items: center;
        gap: 14px;
        pointer-events: none;
    }
    #spa-circular.active { display: flex; }
    .spa-spinner {
        flex-shrink: 0;
        width: 22px; height: 22px;
        border: 2.5px solid #e2e8f0;
        border-top-color: #4f46e5;
        border-radius: 50%;
        animation: spa-spin 0.75s linear infinite;
    }
    @keyframes spa-spin { to { transform: rotate(360deg); } }
    #spa-loader-text {
        color: #334155;
        font-size: 13.5px;
        font-weight: 500;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        white-space: nowrap;
    }

    /* ── Page blur overlay ── */
    #spa-blur-overlay {
        position: fixed;
        inset: 0;
        z-index: 999997;
        display: none;
        cursor: not-allowed;
        background: rgba(15, 23, 42, 0.08);
    }
    #spa-blur-overlay.active {
        display: block;
    }

    /* Blur + dim the actual page content, NOT the sidebar/topbar */
    #kt_app_main {
        transition: filter 0.2s ease, opacity 0.2s ease;
    }
    #kt_app_main.spa-loading {
        filter: blur(3px) brightness(0.92);
        opacity: 0.7;
        pointer-events: none;
        user-select: none;
    }
</style>

<!-- Loaders -->
<div id="spa-topbar"></div>
<div id="spa-circular">
    <div class="spa-spinner"></div>
    <span id="spa-loader-text">{{ __('payments.loading') }}</span>
</div>
<!-- Prevents clicks through to blurred content -->
<div id="spa-blur-overlay"></div>

<script>
    const loaderMessages = [
        "{{ __('pagination.loading_amazing_things') }}",
        "{{ __('pagination.preparing_dashboard') }}",
        "{{ __('pagination.fetching_data') }}",
        "{{ __('pagination.almost_there') }}",
        "{{ __('pagination.making_it_pretty') }}",
        "{{ __('pagination.loading_magic') }}",
        "{{ __('pagination.please_wait') }}",
        "{{ __('pagination.getting_ready') }}"
    ];

    let _barInterval = null, _barWidth = 0, _circularTimer = null;

    function showLoaderApp() {
        // Lock the page content
        const main = document.getElementById('kt_app_main');
        const overlay = document.getElementById('spa-blur-overlay');
        if (main)    main.classList.add('spa-loading');
        if (overlay) overlay.classList.add('active');

        const bar = document.getElementById('spa-topbar');
        if (bar) {
            if (_barInterval) clearInterval(_barInterval);
            bar.style.transition = 'none';
            bar.style.width = '0%';
            bar.classList.add('active');
            _barWidth = 0;
            requestAnimationFrame(() => {
                bar.style.transition = 'width 0.25s ease';
                bar.style.width = '15%';
                _barWidth = 15;
                _barInterval = setInterval(() => {
                    if (_barWidth < 85) {
                        _barWidth += (85 - _barWidth) * 0.035;
                        bar.style.width = _barWidth + '%';
                    }
                }, 250);
            });
        }

        if (_circularTimer) clearTimeout(_circularTimer);
        _circularTimer = setTimeout(() => {
            const circular = document.getElementById('spa-circular');
            const textEl   = document.getElementById('spa-loader-text');
            if (circular) {
                if (textEl) textEl.textContent = loaderMessages[Math.floor(Math.random() * loaderMessages.length)];
                circular.classList.add('active');
            }
        }, 700);
    }

    function hideLoaderApp() {
        // Unlock the page content
        const main = document.getElementById('kt_app_main');
        const overlay = document.getElementById('spa-blur-overlay');
        if (main)    main.classList.remove('spa-loading');
        if (overlay) overlay.classList.remove('active');

        const bar = document.getElementById('spa-topbar');
        if (bar) {
            if (_barInterval) { clearInterval(_barInterval); _barInterval = null; }
            bar.style.transition = 'width 0.15s ease';
            bar.style.width = '100%';
            setTimeout(() => {
                bar.style.transition = 'opacity 0.25s ease';
                bar.style.opacity = '0';
                setTimeout(() => {
                    bar.classList.remove('active');
                    bar.style.opacity = '';
                    bar.style.width = '0%';
                }, 250);
            }, 150);
        }

        if (_circularTimer) { clearTimeout(_circularTimer); _circularTimer = null; }
        const circular = document.getElementById('spa-circular');
        if (circular) circular.classList.remove('active');
    }

    /**
     * Highlights the sidebar link for the current page (reads the real href).
     * Exact match wins; otherwise the longest parent-path match wins, so
     * /products/create or /products/5/edit still highlight "Products".
     */
    function updateActiveMenuLink(url = window.location.href) {
        const norm = u => new URL(u, window.location.origin).pathname.replace(/\/+$/, '') || '/';
        const current = norm(url);
        const root = document.querySelector('.app-sidebar-menu');
        if (!root) return;

        // Reset
        root.querySelectorAll('.menu-link.active').forEach(l => l.classList.remove('active'));
        root.querySelectorAll('.menu-item.show, .menu-item.here').forEach(i => i.classList.remove('show', 'here'));

        // Find best match
        let best = null, bestScore = -1;
        root.querySelectorAll('a.menu-link[href]').forEach(a => {
            const href = a.getAttribute('href');
            if (!href || href.startsWith('javascript') || href === '#') return;

            const p = norm(a.href);
            let score = -1;
            if (p === current) score = Infinity;
            else if (p !== '/' && current.startsWith(p + '/')) score = p.length;

            if (score > bestScore) { best = a; bestScore = score; }
        });

        if (!best) return;

        best.classList.add('active');

        // Open and mark every parent accordion
        let item = best.closest('.menu-item')?.parentElement?.closest('.menu-item');
        while (item) {
            item.classList.add('show', 'here');
            item = item.parentElement?.closest('.menu-item');
        }
    }

    // Show the loader when a sidebar link is clicked (normal navigation follows)
    document.addEventListener('click', e => {
        const a = e.target.closest('.app-sidebar-menu a.menu-link[href]');
        if (!a || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0 || a.target === '_blank') return;
        const href = a.getAttribute('href');
        if (href && !href.startsWith('javascript') && href !== '#') showLoaderApp();
    });

    // Back/forward cache restores the page with the loader still showing
    window.addEventListener('pageshow', e => { if (e.persisted) hideLoaderApp(); });

    document.addEventListener('DOMContentLoaded', () => updateActiveMenuLink(window.location.href));
    window.addEventListener('load', () => updateActiveMenuLink(window.location.href));
</script>























<!-- Order Management -->
<script>
    function printOrder(orderId) {
        const content = document.getElementById('printableOrder' + orderId).innerHTML;
        const printWindow = window.open('', '', 'height=800,width=1000');
        printWindow.document.write('<html><head><title>Order Invoice</title>');
        printWindow.document.write('<style>body{font-family: Arial,sans-serif;} table{border-collapse: collapse;} th, td{padding: 5px;}</style>');
        printWindow.document.write('</head><body >');
        printWindow.document.write(content);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }

</script>










<script>


    document.addEventListener('DOMContentLoaded', function() {
        const variantSearch = document.getElementById('variant_search');
        const variantHidden = document.getElementById('variant_id');
        const quantityInput = document.getElementById('quantity_on_hand');
        const allocatedInput = document.getElementById('quantity_allocated');
        const variantList = document.getElementById('variant_list');
        
        if (!variantSearch || !variantHidden || !quantityInput || !allocatedInput) return;
        
        // Store variant data for quick lookup
        const variants = [];
        if (variantList) {
            const options = variantList.querySelectorAll('option');
            options.forEach(opt => {
                if (opt.value && opt.value !== 'Select product variant') {
                    variants.push({
                        id: opt.getAttribute('data-id'),
                        name: opt.value,
                        quantity: parseInt(opt.getAttribute('data-quantity')) || 0
                    });
                }
            });
        }
        
        function updateQuantity() {
            const searchValue = variantSearch.value.trim();
            const allocated = parseInt(allocatedInput.value) || 0;
            
            // Find matching variant
            let matchedVariant = null;
            for (let i = 0; i < variants.length; i++) {
                if (variants[i].name === searchValue) {
                    matchedVariant = variants[i];
                    break;
                }
            }
            
            if (matchedVariant) {
                // Set hidden input value
                variantHidden.value = matchedVariant.id;
                // Calculate available quantity
                const available = Math.max(0, matchedVariant.quantity - allocated);
                quantityInput.value = available;
            } else {
                variantHidden.value = '';
                quantityInput.value = 0;
            }
        }
        
        // Update when variant is selected from datalist
        variantSearch.addEventListener('change', function() {
            setTimeout(updateQuantity, 10);
        });
        
        // Update when typing (with debounce)
        let debounceTimer;
        variantSearch.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                updateQuantity();
            }, 300);
        });
        
        // Update when allocated quantity changes
        allocatedInput.addEventListener('input', function() {
            updateQuantity();
        });
        
        // Set initial value if editing
        const initialVariantId = variantHidden.value;
        if (initialVariantId) {
            const initialVariant = variants.find(v => v.id == initialVariantId);
            if (initialVariant) {
                variantSearch.value = initialVariant.name;
                updateQuantity();
            }
        }
        
        // Initialize
        updateQuantity();
        
        // Also handle blur to ensure value is correct
        variantSearch.addEventListener('blur', function() {
            const currentValue = this.value;
            let isValid = false;
            for (let i = 0; i < variants.length; i++) {
                if (variants[i].name === currentValue) {
                    isValid = true;
                    break;
                }
            }
            if (!isValid && currentValue !== '') {
                this.value = '';
                variantHidden.value = '';
                quantityInput.value = 0;
            }
        });
    });
</script>


<script>
    function calculateAllocation(input, itemId, availableStock, currentAllocated) {
        const newQuantity = parseInt(input.value) || 0;
        const errorElement = document.getElementById(`new_quantity_error_${itemId}`);
        const updateBtn = document.getElementById(`updateItemButton${itemId}`);
        
        // Calculate the difference
        const difference = newQuantity - currentAllocated;
        const remainingAfterAllocation = availableStock - difference;
        
        // Update display elements
        document.getElementById(`display_new_allocated_${itemId}`).textContent = newQuantity;
        document.getElementById(`display_remaining_${itemId}`).textContent = remainingAfterAllocation;
        
        // ✅ Update the hidden quantity_allocated field
        const quantityAllocatedInput = document.getElementById(`quantity_allocated_${itemId}`);
        if (quantityAllocatedInput) {
            quantityAllocatedInput.value = newQuantity;
        }
        
        // Validate: Cannot allocate more than available stock
        if (difference > availableStock) {
            errorElement.textContent = `Cannot allocate more than available stock. Available: ${availableStock}`;
            errorElement.style.display = 'block';
            input.classList.add('is-invalid');
            updateBtn.disabled = true;
            return;
        }
        
        // Validate: Cannot allocate negative
        if (newQuantity < 0) {
            errorElement.textContent = 'Quantity cannot be negative';
            errorElement.style.display = 'block';
            input.classList.add('is-invalid');
            updateBtn.disabled = true;
            return;
        }
        
        // All validations passed
        errorElement.style.display = 'none';
        input.classList.remove('is-invalid');
        updateBtn.disabled = false;
        
        // Color code the remaining stock indicator
        const remainingEl = document.getElementById(`display_remaining_${itemId}`);
        if (remainingAfterAllocation < 5) {
            remainingEl.className = 'badge badge-light-danger fs-6 ms-2';
        } else if (remainingAfterAllocation < 10) {
            remainingEl.className = 'badge badge-light-warning fs-6 ms-2';
        } else {
            remainingEl.className = 'badge badge-light-info fs-6 ms-2';
        }
    }

    // ── Edit inventory item using LiveBlade ─────────────────────
    function editItemInstanceLoop(uniqueId) {
        const submitButton = document.getElementById('updateItemButton' + uniqueId);
        
        // ✅ Get the form
        const form = document.getElementById('kt_modal_item_form' + uniqueId);
        const formData = new FormData(form);
        
        // ✅ Get the new quantity from the input
        const newQuantityInput = document.getElementById(`new_quantity_${uniqueId}`);
        const newQuantity = parseInt(newQuantityInput?.value) || 0;
        
        // ✅ Get current allocated
        const currentAllocatedInput = document.getElementById(`quantity_allocated_${uniqueId}`);
        const currentAllocated = parseInt(currentAllocatedInput?.value) || 0;
        
        // ✅ Get available stock
        const availableStockInput = document.getElementById(`available_stock_${uniqueId}`);
        const availableStock = parseInt(availableStockInput?.value) || 0;
        
        // ✅ Calculate difference
        const difference = newQuantity - currentAllocated;
        
        // ✅ Validate before submitting
        if (difference > availableStock) {
            toastr.error(`Cannot allocate more than available stock. Available: ${availableStock}`);
            return;
        }
        
        if (newQuantity < 0) {
            toastr.error('Quantity cannot be negative');
            return;
        }
        
        // ✅ Convert FormData to object and override quantity_allocated
        const data = Object.fromEntries(formData.entries());
        
        // ✅ IMPORTANT: Override quantity_allocated with new_quantity value
        data.quantity_allocated = newQuantity;
        
        // ✅ Remove fields that shouldn't be sent
        delete data.new_quantity;
        delete data.available_stock;
        
        // ✅ Set up the URL dynamically
        const updateUrl = '{{ route('items.update', ['item' => ':id']) }}'.replace(':id', uniqueId);
        
        // ✅ Show loading state
        LiveBlade.toggleButtonLoading(submitButton, true);
        
        // ✅ Use the LiveBlade helper to handle the response
        handleEditResponse(data, updateUrl, uniqueId, submitButton);
    }

    // ── Reset form when modal is closed ──────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[id^="editItem"]').forEach(modal => {
            modal.addEventListener('hidden.bs.modal', function() {
                const itemId = this.id.replace('editItem', '');
                const errorEl = document.getElementById(`new_quantity_error_${itemId}`);
                if (errorEl) errorEl.style.display = 'none';
                
                const input = document.getElementById(`new_quantity_${itemId}`);
                if (input) input.classList.remove('is-invalid');
                
                const btn = document.getElementById(`updateItemButton${itemId}`);
                if (btn) btn.disabled = false;
            });
        });
    });

</script>

{{-- ============================================================
     Inventory Pricing — works alongside LiveBlade refreshes
     No IIFE, no captured nodes. Uses document-level delegation
     so re-rendered DOM doesn't lose its listeners.
     ============================================================ --}}
<script>
    // ── Route templates (constant; safe to redefine on each render) ──
    window.INV_PRICING_GET_URL    = @json(route('items.pricing.get',    ['id' => '__ID__']));
    window.INV_PRICING_UPDATE_URL = @json(route('items.pricing.update', ['id' => '__ID__']));
    window.INV_PRICING_CSRF       = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    window.INV_PRICING_CURRENCY   = @json(currency_symbol());

    // ── Helpers ─────────────────────────────────────────────────
    window.invPricingUrlFor = function (template, id) {
        return template.replace('__ID__', encodeURIComponent(id));
    };

    window.invPricingFmt = function (v) {
        return window.INV_PRICING_CURRENCY + Number(v ?? 0).toFixed(2);
    };

    window.invPricingNumberOrNull = function (id) {
        const el = document.getElementById(id);
        if (!el) return null;
        const raw = el.value;
        if (raw === '' || raw === null) return null;
        const n = parseFloat(raw);
        return isNaN(n) ? null : n;
    };

    // ── Toggle sections ─────────────────────────────────────────
    window.invPricingToggleSections = function (on) {
        document.getElementById('customPricingSection')
            ?.classList.toggle('d-none', !on);
        document.getElementById('variantFallbackSection')
            ?.classList.toggle('d-none', on);
    };

    // ── Live preview ────────────────────────────────────────────
    window.invPricingRecalcPreview = function () {
        const supplierEl = document.getElementById('pricingSupplierCost');
        const otherEl    = document.getElementById('pricingOtherCosts');
        const sellingEl  = document.getElementById('pricingSellingPrice');
        const discEl     = document.getElementById('pricingDiscountPercent');
        if (!supplierEl || !otherEl || !sellingEl || !discEl) return;

        const supplier = parseFloat(supplierEl.value) || 0;
        const other    = parseFloat(otherEl.value)    || 0;
        const selling  = parseFloat(sellingEl.value)  || 0;
        const discount = parseFloat(discEl.value)     || 0;

        const grandCost = supplier + other;
        const effective = discount > 0 ? selling - (selling * discount / 100) : selling;
        const profit    = effective - grandCost;
        const margin    = effective > 0 ? (profit / effective) * 100 : 0;

        const costEl    = document.getElementById('previewCost');
        const sellEl    = document.getElementById('previewSelling');
        const profitEl  = document.getElementById('previewProfit');

        if (costEl)   costEl.textContent   = window.invPricingFmt(grandCost);
        if (sellEl)   sellEl.textContent   = window.invPricingFmt(effective);
        if (profitEl) {
            profitEl.textContent = window.invPricingFmt(profit) + ' (' + margin.toFixed(1) + '%)';
            profitEl.className   = 'col-6 text-end fw-bold text-' + (profit >= 0 ? 'success' : 'danger');
        }
    };

    // ── Open the modal (called from the row button) ─────────────
    window.openInventoryPricingModal = async function (itemId) {
        const modalEl = document.getElementById('inventoryPricingModal');
        if (!modalEl) {
            console.error('[pricing] modal not found in DOM');
            return;
        }

        document.getElementById('pricingItemId').value = itemId;
        document.getElementById('pricingItemLabel').textContent = '…';

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        try {
            const res = await fetch(
                window.invPricingUrlFor(window.INV_PRICING_GET_URL, itemId),
                {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                }
            );
            const json = await res.json();

            if (!json.success) {
                toastr.error(json.message || 'Failed to load pricing');
                modal.hide();
                return;
            }

            window.invPricingHydrate(json.data);
        } catch (err) {
            console.error('[pricing] fetch failed', err);
            toastr.error('Failed to load pricing');
            modal.hide();
        }
    };

    // ── Populate the modal ──────────────────────────────────────
    window.invPricingHydrate = function (data) {
        const label = data.item?.variant?.name
            ? `${data.item.variant.name} — ${data.item.department ?? ''} (${data.item.location ?? ''})`
            : `Item #${data.item.id}`;

        const labelEl = document.getElementById('pricingItemLabel');
        if (labelEl) labelEl.textContent = '- ' + label;

        const vCost = document.getElementById('variantCostPrice');
        const vSell = document.getElementById('variantSellingPrice');
        const vDisc = document.getElementById('variantDiscountPrice');
        if (vCost) vCost.textContent = window.invPricingFmt(data.variant.grand_total_cost_price);
        if (vSell) vSell.textContent = window.invPricingFmt(data.variant.selling_price);
        if (vDisc) vDisc.textContent = window.invPricingFmt(data.variant.discount_selling_price);

        const toggle = document.getElementById('hasCustomPricingToggle');
        if (toggle) {
            toggle.checked = !!data.has_custom_pricing;
            window.invPricingToggleSections(toggle.checked);
        }

        const sCost = document.getElementById('pricingSupplierCost');
        const oCost = document.getElementById('pricingOtherCosts');
        const sSell = document.getElementById('pricingSellingPrice');
        const sDisc = document.getElementById('pricingDiscountPercent');

        if (sCost) sCost.value = data.raw.supplier_cost_price     ?? '';
        if (oCost) oCost.value = data.raw.total_shipping_cost     ?? '';
        if (sSell) sSell.value = data.raw.selling_price           ?? '';
        if (sDisc) sDisc.value = data.raw.discount_percentage     ?? '';

        // Seed the selling price from the variant when there's no override yet
        if (!data.has_custom_pricing && sSell && !sSell.value && data.variant.selling_price > 0) {
            sSell.value = data.variant.selling_price;
        }

        window.invPricingRecalcPreview();
    };

    // ── Save ────────────────────────────────────────────────────
    window.invPricingSave = async function () {
        const itemId = document.getElementById('pricingItemId')?.value;
        if (!itemId) return;

        const btn      = document.getElementById('saveInventoryPricingBtn');
        const label    = btn?.querySelector('.indicator-label');
        const progress = btn?.querySelector('.indicator-progress');

        if (btn)      btn.disabled              = true;
        if (label)    label.style.display       = 'none';
        if (progress) progress.style.display    = 'inline-flex';

        const hasCustom = document.getElementById('hasCustomPricingToggle')?.checked ?? false;

        const payload = {
            has_custom_pricing:  hasCustom ? 1 : 0,
            supplier_cost_price: hasCustom ? window.invPricingNumberOrNull('pricingSupplierCost')   : null,
            total_shipping_cost: hasCustom ? window.invPricingNumberOrNull('pricingOtherCosts')     : null,
            ura_taxes_applied:   0,
            additional_expenses: 0,
            selling_price:       hasCustom ? window.invPricingNumberOrNull('pricingSellingPrice')   : null,
            discount_percentage: hasCustom ? (window.invPricingNumberOrNull('pricingDiscountPercent') ?? 0) : null,
        };

        try {
            const res = await fetch(
                window.invPricingUrlFor(window.INV_PRICING_UPDATE_URL, itemId),
                {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.INV_PRICING_CSRF,
                    },
                    body: JSON.stringify(payload),
                }
            );
            const json = await res.json();

            if (json.success) {
                toastr.success(json.message);
                bootstrap.Modal.getInstance(document.getElementById('inventoryPricingModal'))?.hide();

                // Refresh the items table (LiveBlade-safe)
                const target = document.getElementById('reloadItemComponent');
                if (target) {
                    fetch(`{{ route('items.index') }}?bladeFileToReload=reloadItemComponent`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    })
                        .then(r => r.text())
                        .then(html => {
                            const fresh = document.getElementById('reloadItemComponent');
                            if (fresh) fresh.outerHTML = html;
                        })
                        .catch(() => location.reload());
                } else {
                    location.reload();
                }
            } else {
                toastr.error(json.message || 'Failed to save pricing');
            }
        } catch (err) {
            console.error('[pricing] save failed', err);
            toastr.error('Failed to save pricing');
        } finally {
            if (btn)      btn.disabled           = false;
            if (label)    label.style.display    = 'inline-flex';
            if (progress) progress.style.display = 'none';
        }
    };

    // ── One-time wiring (only runs if not already done) ────────
    if (!window.__invPricingWired) {
        window.__invPricingWired = true;

        // Toggle switch → show/hide sections
        document.addEventListener('change', function (e) {
            if (e.target && e.target.id === 'hasCustomPricingToggle') {
                window.invPricingToggleSections(e.target.checked);
            }
        });

        // Live preview on any input in the override form
        document.addEventListener('input', function (e) {
            if (!e.target) return;
            if (['pricingSupplierCost', 'pricingOtherCosts',
                 'pricingSellingPrice', 'pricingDiscountPercent'].includes(e.target.id)) {
                window.invPricingRecalcPreview();
            }
        });

        // Save button (delegated)
        document.addEventListener('click', function (e) {
            if (e.target && e.target.closest('#saveInventoryPricingBtn')) {
                window.invPricingSave();
            }
        });
        
    }
</script>




<script>

    function editProductVariantInstanceLoop(uniqueId) {
        const submitButton = document.getElementById('editUOMButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Select the form and create FormData from it
        var form = document.getElementById('kt_modal_product_form' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);

        // Set up the URL dynamically
        var updateUrl = '{{ route('variants.update', ['variant' => ':id']) }}'.replace(':id', uniqueId);
        
        // Submit form data asynchronously
        handleEditResponse(data, updateUrl, uniqueId, submitButton);

    }

    function updateVariantTaxStatus(productId, status) {
        // Update label instantly
        const label = document.getElementById('tax-label-' + productId);
        if (label) {
            label.innerText = status == 1 ? '{{ __("pagination._yes") }}' : '{{ __("pagination._no") }}';
        }

        // Toggle checkboxes / alert
        const taxOptions = document.querySelector('#variant-assign-tax-' + productId + ' .tax-options');
        const notTaxable = document.querySelector('#variant-assign-tax-' + productId + ' .not-taxable-msg');

        if (taxOptions && notTaxable) {
            if (status == 1) {
                taxOptions.style.display = '';
                notTaxable.style.display = 'none';
            } else {
                taxOptions.style.display = 'none';
                notTaxable.style.display = '';
            }
        }

        // Send update to backend
        const updateRoute = '/variant-tax-status/' + productId;
        LiveBlade.loopUpdateStatus(updateRoute, status);
    }



    function updateVariantStatus(uniqueId, selectedStatus) {
        // console.log(" ID:", uniqueId, "Selected status:", selectedStatus);
        const updateRoute = '/variant-status/' + uniqueId;
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
    }
    

    function updateProductTaxStatus(productId, status) {
        // Update label instantly
        const label = document.getElementById('tax-label-' + productId);
        if (label) {
            label.innerText = status == 1 ? '{{ __("pagination._yes") }}' : '{{ __("pagination._no") }}';
        }

        // Toggle checkboxes / alert
        const taxOptions = document.querySelector('#product-assign-tax-' + productId + ' .tax-options');
        const notTaxable = document.querySelector('#product-assign-tax-' + productId + ' .not-taxable-msg');

        if (taxOptions && notTaxable) {
            if (status == 1) {
                taxOptions.style.display = '';
                notTaxable.style.display = 'none';
            } else {
                taxOptions.style.display = 'none';
                notTaxable.style.display = '';
            }
        }

        // Send update to backend
        const updateRoute = '/product-tax-status/' + productId;
        LiveBlade.loopUpdateStatus(updateRoute, status);
    }

    
    // Bridge the old liveblade helper name to the current global.
    function setModalLoading(button, isLoading) {
        if (!window.LiveBlade?.toggleButtonLoading) {
            // Fallback if the liveblade bundle isn't loaded
            if (button) button.disabled = !!isLoading;
            return;
        }
        window.LiveBlade.toggleButtonLoading(button, isLoading);
    }

    // ── Clear previous validation errors ─────────────────────
    function clearFormErrors(form) {
        if (!form) return;

        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    }

    // ── Show a single field error ────────────────────────────
    function showFieldError(form, fieldName, message) {
        if (!form) return;

        const field = form.querySelector(`[name="${fieldName}"]`);
        if (!field) return;

        field.classList.add('is-invalid');

        // Try to find the existing `<div id="fieldName"></div>` error holder
        let holder = form.querySelector(`#${fieldName}`);

        // If not found (or the field name isn't a valid id), create one after the field
        if (!holder) {
            holder = document.createElement('div');
            holder.id = fieldName;
            field.parentNode.appendChild(holder);
        }

        holder.classList.add('invalid-feedback', 'd-block');
        holder.textContent = message;
    }

</script>







<!-- Roles -->
<script>
    function submitRoleForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
        const form = document.getElementById(formId);
        const submitButton = document.getElementById(submitButtonId);

        // Collect selected permissions
        let permissions = [];
        document.querySelectorAll('.permission-checkbox:checked').forEach((checkbox) => {
            permissions.push(checkbox.value);
        });

        const formDataObj = Object.fromEntries(new FormData(form).entries());

        // Add extra fields
        formDataObj._method = method;
        formDataObj.routeName = url;
        formDataObj.permissions = permissions;

        LiveBlade.toggleButtonLoading(submitButton, true);

        handleFormSubmission(formDataObj, submitButton, discardButtonId);
    }


    function editInstanceLoopRole(uniqueId) {
        const submitButton = document.getElementById('submitButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Select the form and create FormData from it
        var form = document.getElementById('edit_role_form' + uniqueId);
        var formData = new FormData(form);

        // Get the checked permission values as an array
        var permissions = getCheckedValues(uniqueId);
        permissions.forEach(permissionId => formData.append('permissions[]', permissionId));
        var data = Object.fromEntries(formData.entries());

        // Confirm permissions as an array in final data object
        data.permissions = permissions;

        // Set up the URL dynamically
        var updateUrl = '{{ route('role.update', ['role' => ':id']) }}'.replace(':id', uniqueId);

        // Submit form data asynchronously
        handleEditResponse(data, updateUrl, uniqueId, submitButton);


    }


</script>


<!-- Human Resource -->


<script>
    
    function syncUserToEmployee() {
        Swal.fire({
            title: '{{ __("auth.confirm_sync") }}',
            text: '{{ __("auth.sync_warning_message") }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '{{ __("auth.yes_sync") }}',
            cancelButtonText: '{{ __("auth._discard") }}',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch('{{ route("sync.users.to.employees") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || '{{ __("auth.sync_failed") }}');
                    }
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(
                        `{{ __("auth.sync_error") }}: ${error.message}`
                    );
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                Swal.fire({
                    icon: 'success',
                    title: '{{ __("auth.sync_completed") }}',
                    text: `{{ __("auth.sync_results") }}: ${result.value.stats.created} {{ __("auth.created") }}, ${result.value.stats.updated} {{ __("auth.updated") }}`,
                    timer: 3000,
                    showConfirmButton: true,
                    confirmButtonText: '{{ __("auth.ok") }}'
                }).then(() => {
                    location.reload();
                });
            }
        });
    }



    function uploadEmployeeDocument(employeeId) {
        const form = document.getElementById('uploadDocumentForm' + employeeId);
        const uploadBtn = document.getElementById('uploadBtn' + employeeId);
        
        if (!form || !uploadBtn) return;
        
        // Create FormData
        const formData = new FormData(form);
        
        // Show loading state
        uploadBtn.disabled = true;
        uploadBtn.querySelector('.indicator-label').style.display = 'none';
        uploadBtn.querySelector('.indicator-progress').style.display = 'inline-block';
        
        // Upload document via AJAX
        fetch('{{ route("employee.documents.upload") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message || 'Document uploaded successfully',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Reload the documents list
                    location.reload();
                });
            } else {
                // Show validation errors
                let errorMessage = '';
                if (data.errors) {
                    Object.keys(data.errors).forEach(key => {
                        errorMessage += `<p class="mb-1 text-danger">${data.errors[key][0]}</p>`;
                    });
                } else {
                    errorMessage = data.message || 'Upload failed';
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Upload Failed',
                    html: errorMessage
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred during upload'
            });
        })
        .finally(() => {
            // Reset button state
            uploadBtn.disabled = false;
            uploadBtn.querySelector('.indicator-label').style.display = 'inline-block';
            uploadBtn.querySelector('.indicator-progress').style.display = 'none';
        });
    }

    function deleteEmployeeDocument(employeeId, documentIndex) {
        Swal.fire({
            title: 'Confirm Delete',
            text: 'Are you sure you want to delete this document?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Send delete request
                fetch('{{ route("employee.documents.delete") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        employee_id: employeeId,
                        document_index: documentIndex
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: data.message || 'Document deleted successfully',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message || 'Delete failed'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred'
                    });
                });
            }
        });
    }
    
</script>







<!-- User Profile  -->
<script>
    // ✅ Ensure function is in global scope
    function previewAndUploadProfileImage(event) {
        const image = document.getElementById('profile-img-preview');
        const file = event.target.files[0];

        if (!file) return;

        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        const allowedError = '{{ __('auth.allowed_files') }}';
        if (!allowedTypes.includes(file.type)) {
            alert(allowedError);
            return;
        }

        const maxSize = 5 * 1024 * 1024;
        const sizeError = '{{ __('auth.file_large') }}';
        if (file.size > maxSize) {
            alert(sizeError);
            return;
        }


        const reader = new FileReader();
        reader.onload = function(e) {
            image.src = e.target.result;
        };
        reader.readAsDataURL(file);

        const formData = new FormData();
        formData.append('profile_image', file);

        uploadFile(formData, "{{ route('profile.upload_image') }}");
    }
    
    function uploadFile(formData, uploadUrl) {
        const type = formData.get("type") || "profile_image";
        const file = formData.get("profile_image") || formData.get("file");

        if (!uploadUrl) {
            console.error("Upload URL required");
            return;
        }

        LiveBlade.uploadImage(file, uploadUrl, type);
    }


</script>




<!-- Settings -->
<script>

    
    function previewAndUploadLogoOrFavicon(event) {
        const input = event.target; // Get the file input element
        const fileType = input.dataset.type; // Get the data-type (logo or favicon)
        const previewSelector = `[data-preview="${fileType}-preview"]`; // Match the correct preview container

        if (input.files && input.files[0]) {
            const file = input.files[0]; // Get the selected file
            const reader = new FileReader(); // Create a FileReader object

            reader.onload = function (e) {
                // Find the preview container based on the data-type
                const previewElement = document.querySelector(previewSelector);
                if (previewElement) {
                    // Set the background image
                    previewElement.style.backgroundImage = `url(${e.target.result})`;
                }
            };

            const formData = new FormData();
            formData.append("file", file); // Append the file
            formData.append("type", fileType); // Append the file type (logo or favicon)

            // Call the function to upload the file to the server
            uploadLogoOrFavicon(formData);

            // Read the file as a Data URL
            reader.readAsDataURL(file);

        }
    }

    function uploadLogoOrFavicon(formData) {
        const type = formData.get("type"); // Extract the type (logo or favicon)
        const file = formData.get("file"); // Extract the file itself

        // Determine the URL based on the type (logo or favicon)
        let uploadUrl = "";
        if (type === "logo_image") {
            uploadUrl = '{{ route("logo.upload") }}'; // Set URL for logo upload
        } else if (type === "favicon_image") {
            uploadUrl = '{{ route("favicon.upload") }}'; // Set URL for favicon upload
        } else {
            alert("Invalid file type");
            return; // Exit if the type is invalid
        }

        // console.log("Uploading to:", uploadUrl);
        // console.log("File Type:", type);

        // Pass the file to LiveBlade.uploadImage
        LiveBlade.uploadImage(file, uploadUrl, type);
    }

    function submitSettingFormEntities(formId, submitButtonId, url, method = 'POST', discardButtonId = '') {
        const form = document.getElementById(formId);
        const submitButton = document.getElementById(submitButtonId);

        // Collect form data
        const formData = Object.fromEntries(new FormData(form));
        formData._method = method;
        formData.routeName = url;
        // console.log(formData);

        // Start loading
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Pass handling + data to reusable handler
        handleFormSubmission(formData, submitButton, discardButtonId);
    }


    function changeLocale(locale) {
        window.location.href = "/change-locale/" + locale;
    }
</script>





<!-- General Reusable Functions -->
<script>
    
    // for general table searches
    // Auto Table search
    function searchTable(query, tableId) {
        const table = document.getElementById(tableId);
        if (!table) {
            console.error('Table not found:', tableId);
            return;
        }

        const rows = table.getElementsByTagName('tr');
        query = query.toLowerCase();

        for (let i = 1; i < rows.length; i++) { // skip header row
            const cells = rows[i].getElementsByTagName('td');
            let match = false;

            for (let j = 0; j < cells.length; j++) {
                if (cells[j].innerText.toLowerCase().includes(query)) {
                    match = true;
                    break;
                }
            }

            rows[i].style.display = match ? '' : 'none';
        }
    }


    // Reintialize the datatables after submission
    function initializeComponentScripts() {
        // initialize stock adjustment inputs
        // initializeStockInputs();
        
        // Initialize pagination and search capibilities
        if (typeof window.LiveBladeRefresh === 'function') {
            window.LiveBladeRefresh();
        }
        

        // const tableId = '#kt_table_users';

        // // Destroy the existing DataTable instance if it exists
        // if ($.fn.DataTable.isDataTable(tableId)) {
        //     $(tableId).DataTable().destroy();
        // }

        // // Reinitialize the DataTable
        // $(tableId).DataTable({
        //     paging: false,
        //     searching: true,
        //     ordering: true,
        //     responsive: false, // Disable responsive behavior
        //     autoWidth: false, // Prevent automatic column resizing
        //     language: {
        //         emptyTable: "No data available",
        //     },
        //     columnDefs: [
        //         // Specify widths for specific columns (optional)
        //         { targets: 0, width: "10%" }, // Example for first column
        //         { targets: 1, width: "15%" },
        //     ],
        // });

        
        @if ( @json_decode(request()->routeIs('role.*')) )
            // filterRole();
        @endif
        @if ( @json_decode(request()->routeIs('permission.*')) )
            window.showAllPermissions = showAllPermissions;
        @endif
    }
    

    // General General form submitter
    function handleFormSubmission(formData, submitButton, discardButtonId = 'discardButton') {
        LiveBlade.submitFormItems(formData)
            .then(noErrors => {
                console.log(noErrors);

                if (noErrors) {
                    const closeModal = document.getElementById(discardButtonId);
                    if (closeModal) closeModal.click();
                }
            })
            .catch(error => {
                console.error('An unexpected error occurred:', error);
            })
            .finally(() => {
                LiveBlade.toggleButtonLoading(submitButton, false);
            });
    }


    // General Update or Edit Function 
    function handleEditResponse(data, updateUrl, uniqueId, submitButton) {
        LiveBlade.editLoopForms(data, updateUrl)
        .then(noErrorStatus => {
            if (noErrorStatus) {
                const closeButton = document.getElementById(`closeModalEditButton${uniqueId}`);
                if (closeButton) closeButton.click();
            }
        })
        .catch(error => {
            console.error('An unexpected error occurred:', error);
        })
        .finally(() => {
            LiveBlade.toggleButtonLoading(submitButton, false);
        });
    }


    // General Delete function
    function deleteItem(button) {
        const itemId = button.getAttribute('data-item-id');
        const deleteUrl = button.getAttribute('data-item-url');

        const deleteButton = document.getElementById('deleteButton' + itemId);
        LiveBlade.toggleButtonLoading(deleteButton, true);
        
        // Call the delete function to handle the deletion
        LiveBlade.deleteItemInLoop(deleteUrl)
            .then(noErrorStatus => {
                console.log(noErrorStatus)
                if (noErrorStatus) {
                    var closeButton = document.getElementById('closeDeleteModal' + itemId);
                    if (closeButton) {
                        closeButton.click();
                    }
                }
            })
            .catch(error => {
                console.error('An unexpected error occurred:', error);
                // Handle error gracefully
            })
            .finally(() => {
                // End loading state using reusable function
                LiveBlade.toggleButtonLoading(deleteButton, false);
            });
    }
</script>


<!-- Reusable for modal search -->
 <script>
    $(document).ready(function() {
        // Initialize Select2 dropdowns with proper modal configuration
        function initSelect2ForModal(modalSelector) {
            $(modalSelector).find('select[data-control="select2"]').each(function() {
                // Check if Select2 is already initialized
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
                
                $(this).select2({
                    dropdownParent: $(modalSelector),
                    placeholder: $(this).data('placeholder') || "Select an option",
                    allowClear: $(this).data('allow-clear') || false,
                    closeOnSelect: $(this).data('close-on-select') !== false
                });
            });
        }
        
        // Initialize for all modals when shown
        $('.modal').on('shown.bs.modal', function() {
            initSelect2ForModal('#' + $(this).attr('id'));
        });
        
        // Initialize for any already open modals on page load
        $('.modal.show').each(function() {
            initSelect2ForModal('#' + $(this).attr('id'));
        });
        
        // Additional z-index fix for dropdown containers
        $(document).on('select2:open', function(e) {
            // Find the parent modal of the opened select2
            const $select = $(e.target);
            const $modal = $select.closest('.modal');
            
            if ($modal.length) {
                $(".select2-container--open").css('z-index', 999999);
            }
        });
        
        // Cleanup Select2 when modal is hidden to prevent memory leaks
        $('.modal').on('hidden.bs.modal', function() {
            $(this).find('select[data-control="select2"]').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });
        });
    });
</script>