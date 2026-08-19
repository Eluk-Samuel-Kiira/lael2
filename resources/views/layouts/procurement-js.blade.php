





<!-- Purchases -->
<!-- Create Purchases -->
 
<script>
    
    // Function to handle form submission (optional - for testing)
    function submitPurchaseOrrForm() {
        console.log('Submitting purchase order form...');
        
        // Validate form
        if (!validatePurchaseOrderForm()) {
            return;
        }
        
        
        // Here you would typically send the data to your server
        // For now, just log the form data
        const formData = new FormData(document.getElementById('kt_modal_add_purchase_order_form'));
        console.log('Form data:', Object.fromEntries(formData));
        submitSupplierForm('', 'submitSupplierButton', '{{ route('suppliers.store') }}', 'POST', 'submitPurchaseOrderButton');
    }
</script>



<script>
    // Function to print purchase order as receipt
    function printPurchaseOrder(orderId) {
        const modalElement = document.getElementById('viewPurchase' + orderId);
        if (!modalElement) {
            console.error('Modal not found');
            return;
        }

        // ── Extract Data from Modal ──────────────────────────────────────────────
        
        // PO Number and Status
        const poNumber = modalElement.querySelector('.badge-light-primary.py-2.px-3.fs-7')?.innerText?.trim() || 'PO-';
        const statusBadge = modalElement.querySelector('.badge.badge-light')?.innerText?.trim() || '';
        
        // Supplier
        let supplierName = '—';
        let supplierEmail = '';
        let supplierPhone = '';
        const supplierCard = modalElement.querySelector('.col-md-4 .fw-bold.fs-5.text-gray-800');
        if (supplierCard) {
            supplierName = supplierCard.innerText.trim();
            const supplierDetails = modalElement.querySelectorAll('.col-md-4 .text-muted.fs-7');
            if (supplierDetails.length >= 2) {
                supplierEmail = supplierDetails[0]?.innerText?.trim() || '';
                supplierPhone = supplierDetails[1]?.innerText?.trim() || '';
            }
        }
        
        // Expected Delivery
        let expectedDelivery = '—';
        const deliveryEl = modalElement.querySelector('.col-md-4 .fw-bold.fs-5.text-gray-800');
        if (deliveryEl) {
            const text = deliveryEl.innerText.trim();
            if (text.match(/\d{4}/)) {
                expectedDelivery = text;
            }
        }
        
        // Location
        let location = '—';
        const locationEl = modalElement.querySelector('.col-md-4 .text-muted.fs-7:last-child');
        if (locationEl) {
            const text = locationEl.innerText.trim();
            if (text && !text.includes('TIN:') && !text.includes('@')) {
                location = text;
            }
        }
        
        // Created Date
        let createdDate = '';
        const createdEl = modalElement.querySelector('.text-muted.fs-7 i.bi-clock')?.parentElement;
        if (createdEl) {
            createdDate = createdEl.innerText.replace('Created:', '').trim();
        }
        if (!createdDate) {
            createdDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        }
        
        // ── Items ──────────────────────────────────────────────────────────────────
        const items = [];
        const tableRows = modalElement.querySelectorAll('.table tbody tr');
        tableRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 8) {
                const productName = cells[0]?.querySelector('.fw-bold.text-gray-800')?.innerText?.trim() || '—';
                const sku = cells[0]?.querySelector('.text-muted.fs-7')?.innerText?.replace('SKU:', '').trim() || '';
                
                const orderedQty = parseFloat(cells[1]?.innerText?.replace(/,/g, '')) || 0;
                const unitCost = parseFloat(cells[2]?.innerText?.replace(/[^0-9.]/g, '')) || 0;
                const receivedQty = parseFloat(cells[3]?.innerText?.replace(/,/g, '')) || 0;
                const pendingQty = parseFloat(cells[4]?.innerText?.replace(/,/g, '')) || 0;
                const orderedValue = parseFloat(cells[5]?.innerText?.replace(/[^0-9.]/g, '')) || 0;
                const receivedValue = parseFloat(cells[6]?.innerText?.replace(/[^0-9.]/g, '')) || 0;
                const pendingValue = parseFloat(cells[7]?.innerText?.replace(/[^0-9.]/g, '')) || 0;
                const taxAmount = parseFloat(cells[8]?.innerText?.replace(/[^0-9.]/g, '')) || 0;
                
                items.push({
                    product: productName,
                    sku: sku,
                    orderedQty: orderedQty,
                    unitCost: unitCost,
                    receivedQty: receivedQty,
                    pendingQty: pendingQty,
                    orderedValue: orderedValue,
                    receivedValue: receivedValue,
                    pendingValue: pendingValue,
                    taxAmount: taxAmount
                });
            }
        });
        
        // ── Totals ─────────────────────────────────────────────────────────────────
        let subtotal = 0;
        let receivedSubtotal = 0;
        let pendingSubtotal = 0;
        let taxTotal = 0;
        
        const tfootRow = modalElement.querySelector('.table tfoot tr');
        if (tfootRow) {
            const cells = tfootRow.querySelectorAll('td');
            if (cells.length >= 5) {
                subtotal = parseFloat(cells[1]?.innerText?.replace(/[^0-9.]/g, '')) || 0;
                receivedSubtotal = parseFloat(cells[2]?.innerText?.replace(/[^0-9.]/g, '')) || 0;
                pendingSubtotal = parseFloat(cells[3]?.innerText?.replace(/[^0-9.]/g, '')) || 0;
                taxTotal = parseFloat(cells[4]?.innerText?.replace(/[^0-9.]/g, '')) || 0;
            }
        }
        
        // ── Notes ──────────────────────────────────────────────────────────────────
        let notes = '';
        const notesEl = modalElement.querySelector('.card-body p.mb-0.text-gray-700');
        if (notesEl) {
            notes = notesEl.innerText;
        }
        
        // ── Helper: Format number with commas ─────────────────────────────────────
        function formatNumber(value) {
            return Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // ── Create Print HTML ─────────────────────────────────────────────────────
        const printWindow = window.open('', '_blank', 'width=1000,height=800');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Purchase Order - ${poNumber}</title>
                <meta charset="UTF-8">
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body { 
                        font-family: Arial, Helvetica, sans-serif;
                        font-size: 12px; 
                        line-height: 1.4; 
                        color: #1e293b; 
                        padding: 15px;
                        background: white;
                    }
                    .print-container {
                        max-width: 100%;
                        background: white;
                    }
                    
                    .print-header {
                        border-bottom: 2px solid #0f172a;
                        padding: 10px 0 15px 0;
                        margin-bottom: 15px;
                    }
                    .print-header .top-row {
                        display: flex;
                        justify-content: space-between;
                        align-items: flex-start;
                    }
                    .print-header h1 { 
                        font-size: 20px; 
                        font-weight: 700; 
                        color: #0f172a;
                    }
                    .print-header .po-number { 
                        font-size: 14px; 
                        font-weight: 600; 
                        color: #334155;
                        margin-top: 2px;
                    }
                    .print-header .date { 
                        font-size: 11px; 
                        color: #64748b; 
                    }
                    .print-header .status { 
                        display: inline-block; 
                        padding: 2px 12px; 
                        border-radius: 12px; 
                        font-size: 11px; 
                        font-weight: 600; 
                        margin-top: 4px;
                        background: #e2e8f0; 
                        color: #0f172a;
                        border: 1px solid #cbd5e1;
                    }
                    .print-header .right-text {
                        text-align: right;
                        font-size: 11px;
                        color: #64748b;
                    }
                    
                    .info-grid {
                        display: grid; 
                        grid-template-columns: repeat(3, 1fr); 
                        gap: 10px;
                        margin-bottom: 15px; 
                        padding-bottom: 12px; 
                        border-bottom: 1px solid #e2e8f0;
                    }
                    .info-card { 
                        padding: 8px 12px; 
                        border: 1px solid #e2e8f0; 
                        border-radius: 4px; 
                    }
                    .info-label { 
                        font-size: 9px; 
                        font-weight: 700; 
                        text-transform: uppercase; 
                        letter-spacing: 0.5px; 
                        color: #64748b; 
                    }
                    .info-value { font-size: 13px; font-weight: 600; color: #0f172a; }
                    .info-sub { font-size: 11px; color: #64748b; }
                    
                    .items-table { 
                        width: 100%; 
                        border-collapse: collapse; 
                        margin: 10px 0; 
                        font-size: 11px; 
                    }
                    .items-table th {
                        text-align: left; 
                        padding: 5px 6px; 
                        font-weight: 700; 
                        font-size: 9px; 
                        text-transform: uppercase; 
                        color: #475569; 
                        border-bottom: 2px solid #e2e8f0;
                        background: #f8fafc;
                    }
                    .items-table td { 
                        padding: 5px 6px; 
                        border-bottom: 1px solid #f1f5f9; 
                        color: #334155; 
                    }
                    .items-table .text-right { text-align: right; }
                    .items-table .text-center { text-align: center; }
                    .items-table .fw-bold { font-weight: 600; }
                    .items-table .text-muted { color: #94a3b8; font-size: 10px; }
                    
                    .badge { 
                        display: inline-block; 
                        padding: 1px 6px; 
                        font-size: 9px; 
                        font-weight: 600; 
                        border-radius: 3px; 
                    }
                    .badge-success { background: #dcfce7; color: #166534; }
                    .badge-warning { background: #fef9c3; color: #854d0e; }
                    
                    .totals-section {
                        margin-top: 10px; 
                        padding-top: 10px; 
                        border-top: 2px solid #e2e8f0;
                        display: flex; 
                        justify-content: flex-end;
                    }
                    .totals-card { 
                        width: 280px; 
                        padding: 10px 14px; 
                        border: 1px solid #e2e8f0; 
                        border-radius: 4px; 
                    }
                    .total-row {
                        display: flex; 
                        justify-content: space-between; 
                        padding: 2px 0; 
                        font-size: 11px;
                    }
                    .total-row .label { color: #64748b; }
                    .total-row .value { font-weight: 500; }
                    .total-row.grand-total {
                        margin-top: 4px; 
                        padding-top: 6px; 
                        border-top: 2px solid #e2e8f0;
                        font-size: 14px; 
                        font-weight: 700; 
                        color: #0f172a;
                    }
                    
                    .notes-section { 
                        margin-top: 12px; 
                        padding: 8px 12px; 
                        background: #fefce8; 
                        border-radius: 4px; 
                        border-left: 3px solid #eab308; 
                    }
                    .notes-section strong { font-size: 10px; }
                    .notes-section .content { font-size: 11px; color: #334155; }
                    
                    .footer { 
                        margin-top: 15px; 
                        padding-top: 10px; 
                        border-top: 1px solid #e2e8f0; 
                        text-align: center; 
                        font-size: 10px; 
                        color: #94a3b8; 
                    }
                    
                    .no-print { 
                        text-align: center; 
                        padding: 10px; 
                        background: #f8fafc; 
                        border-top: 1px solid #e2e8f0; 
                    }
                    .btn { 
                        padding: 5px 14px; 
                        font-size: 12px; 
                        font-weight: 500; 
                        border-radius: 4px; 
                        border: none; 
                        cursor: pointer; 
                        margin: 0 4px; 
                    }
                    .btn-primary { background: #3b82f6; color: white; }
                    .btn-secondary { background: #64748b; color: white; }
                    
                    @media print {
                        body { padding: 0.2in; margin: 0; }
                        .print-container { box-shadow: none; border-radius: 0; }
                        .no-print { display: none !important; }
                        .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    }
                    
                    @page {
                        margin: 8mm;
                        size: A4 portrait;
                    }
                </style>
            </head>
            <body>
                <div class="print-container">
                    <!-- Header -->
                    <div class="print-header">
                        <div class="top-row">
                            <div>
                                <h1>PURCHASE ORDER</h1>
                                <div class="po-number">${poNumber}</div>
                                <div class="date">${createdDate}</div>
                                <div class="status">${statusBadge}</div>
                            </div>
                            <div class="right-text">
                                <div>Generated: ${new Date().toLocaleDateString()}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Information Grid -->
                    <div class="info-grid">
                        <div class="info-card">
                            <div class="info-label">SUPPLIER</div>
                            <div class="info-value">${supplierName}</div>
                            ${supplierEmail ? `<div class="info-sub">${supplierEmail}</div>` : ''}
                            ${supplierPhone ? `<div class="info-sub">${supplierPhone}</div>` : ''}
                        </div>
                        <div class="info-card">
                            <div class="info-label">EXPECTED DELIVERY</div>
                            <div class="info-value">${expectedDelivery}</div>
                        </div>
                        <div class="info-card">
                            <div class="info-label">LOCATION</div>
                            <div class="info-value">${location}</div>
                        </div>
                    </div>
                    
                    <!-- Items Table -->
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th style="width:22%;">PRODUCT</th>
                                <th style="width:8%;" class="text-center">QTY</th>
                                <th style="width:12%;" class="text-right">UNIT COST</th>
                                <th style="width:10%;" class="text-center">RECEIVED</th>
                                <th style="width:10%;" class="text-center">PENDING</th>
                                <th style="width:12%;" class="text-right">ORDERED</th>
                                <th style="width:12%;" class="text-right">RECEIVED</th>
                                <th style="width:12%;" class="text-right">PENDING</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${items.map(item => `
                                <tr>
                                    <td>
                                        <div class="fw-bold">${item.product}</div>
                                        ${item.sku ? `<div class="text-muted">SKU: ${item.sku}</div>` : ''}
                                    </td>
                                    <td class="text-center">${item.orderedQty}</td>
                                    <td class="text-right">${formatNumber(item.unitCost)}</td>
                                    <td class="text-center"><span class="badge badge-success">${item.receivedQty}</span></td>
                                    <td class="text-center"><span class="badge badge-warning">${item.pendingQty}</span></td>
                                    <td class="text-right">${formatNumber(item.orderedValue)}</td>
                                    <td class="text-right">${formatNumber(item.receivedValue)}</td>
                                    <td class="text-right">${formatNumber(item.pendingValue)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                        <tfoot style="background: #f8fafc; font-weight: 600; border-top: 2px solid #e2e8f0;">
                            <tr>
                                <td colspan="5" class="text-right">TOTALS:</td>
                                <td class="text-right">${formatNumber(subtotal)}</td>
                                <td class="text-right">${formatNumber(receivedSubtotal)}</td>
                                <td class="text-right">${formatNumber(pendingSubtotal)}</td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <!-- Totals Section -->
                    <div class="totals-section">
                        <div class="totals-card">
                            <div class="total-row">
                                <span class="label">Ordered Value:</span>
                                <span class="value">${formatNumber(subtotal)}</span>
                            </div>
                            <div class="total-row">
                                <span class="label">Received Value:</span>
                                <span class="value">${formatNumber(receivedSubtotal)}</span>
                            </div>
                            <div class="total-row">
                                <span class="label">Pending Value:</span>
                                <span class="value">${formatNumber(pendingSubtotal)}</span>
                            </div>
                            <div class="total-row">
                                <span class="label">Tax Total:</span>
                                <span class="value">${formatNumber(taxTotal)}</span>
                            </div>
                            <div class="total-row grand-total">
                                <span>TOTAL ORDER VALUE:</span>
                                <span>${formatNumber(subtotal + taxTotal)}</span>
                            </div>
                        </div>
                    </div>
                    
                    ${notes ? `
                    <div class="notes-section">
                        <strong>NOTES</strong>
                        <div class="content">${notes}</div>
                    </div>
                    ` : ''}
                    
                    <div class="footer">
                        <div>Thank you for your business!</div>
                        <div style="margin-top: 2px;">Generated on ${new Date().toLocaleString()}</div>
                    </div>
                    
                    <div class="no-print">
                        <button onclick="window.print()" class="btn btn-primary">Print</button>
                        <button onclick="window.close()" class="btn btn-secondary">Close</button>
                    </div>
                </div>
                
                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            window.print();
                        }, 600);
                    };
                <\/script>
            </body>
            </html>
        `);
        
        printWindow.document.close();
    }

    // Function to download as PDF
    function downloadPurchaseOrder(orderId) {
        printPurchaseOrder(orderId);
    }
</script>






<script>
    // Global variable to track item count
    let purchaseOrderItemCount = 0;

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Purchase order form initialized');
        
        // Set the first item ID
        const firstItem = document.querySelector('.purchase-order-item');
        if (firstItem) {
            firstItem.id = 'item_0';
            purchaseOrderItemCount = 0;
            console.log('First item ID set to:', firstItem.id);
        }
        
        // Initialize product search for existing items
        initProductSearch();
        
        // Enable remove button if needed
        updateRemoveButtons();
        
        // Initialize order summary
        updateOrderSummary();
        
        // Add event listeners to existing quantity and unit cost inputs
        initializeExistingInputs();
    });

    // Initialize product search for all typable inputs
    function initProductSearch() {
        document.querySelectorAll('.product-typable-input').forEach(input => {
            if (input.dataset.initialized === 'true') return;
            input.dataset.initialized = 'true';
            
            const itemIndex = input.dataset.itemIndex;
            const hiddenInput = document.getElementById(`product_id_${itemIndex}`);
            const unitCostInput = document.querySelector(`input[name="items[${itemIndex}][unit_cost]"]`);
            const quantityInput = document.querySelector(`input[name="items[${itemIndex}][quantity]"]`);
            
            function updateProduct() {
                const selectedValue = input.value;
                const datalist = document.getElementById(input.getAttribute('list'));
                let selectedId = '';
                let costPrice = 0;
                
                if (datalist) {
                    const options = datalist.querySelectorAll('option');
                    for (let opt of options) {
                        if (opt.value === selectedValue) {
                            selectedId = opt.dataset.id;
                            costPrice = parseFloat(opt.dataset.costPrice) || 0;
                            break;
                        }
                    }
                }
                
                hiddenInput.value = selectedId;
                if (unitCostInput && costPrice > 0) {
                    unitCostInput.value = costPrice.toFixed(2);
                    if (quantityInput) {
                        calculateItemTotal(parseInt(itemIndex));
                    }
                } else if (!selectedValue) {
                    hiddenInput.value = '';
                    if (unitCostInput) unitCostInput.value = '0.00';
                    if (quantityInput) calculateItemTotal(parseInt(itemIndex));
                }
            }
            
            input.addEventListener('change', updateProduct);
            input.addEventListener('blur', updateProduct);
            
            // Initial sync if there's a value
            if (input.value) {
                updateProduct();
            }
        });
    }

    // Initialize event listeners for existing inputs
    function initializeExistingInputs() {
        console.log('Initializing existing inputs...');
        
        // Add input event listeners to quantity and unit cost fields
        document.querySelectorAll('input[name^="items["][name$="[quantity]"]').forEach(input => {
            console.log('Found quantity input:', input.name);
            
            input.removeEventListener('input', input._quantityHandler);
            input._quantityHandler = function() {
                try {
                    const matches = this.name.match(/\[(\d+)\]/);
                    if (matches && matches[1]) {
                        const itemIndex = parseInt(matches[1]);
                        calculateItemTotal(itemIndex);
                    }
                } catch (error) {
                    console.error('Error in quantity input listener:', error);
                }
            };
            input.addEventListener('input', input._quantityHandler);
        });
        
        document.querySelectorAll('input[name^="items["][name$="[unit_cost]"]').forEach(input => {
            console.log('Found unit cost input:', input.name);
            
            input.removeEventListener('input', input._costHandler);
            input._costHandler = function() {
                try {
                    const matches = this.name.match(/\[(\d+)\]/);
                    if (matches && matches[1]) {
                        const itemIndex = parseInt(matches[1]);
                        calculateItemTotal(itemIndex);
                    }
                } catch (error) {
                    console.error('Error in unit cost input listener:', error);
                }
            };
            input.addEventListener('input', input._costHandler);
        });
    }

    // Function to add new purchase order item row
    function addPurchaseOrderItem() {
        purchaseOrderItemCount++;
        
        const container = document.getElementById('purchase_order_items_container');
        if (!container) {
            console.error('Purchase order items container not found');
            return;
        }
        
        // Build variant options for datalist
        let variantOptions = '';
        @foreach($variants as $variant)
            variantOptions += `<option value="{{ $variant->name }}" 
                                    data-id="{{ $variant->id }}"
                                    data-cost-price="{{ $variant->supplier_cost_price }}">
                                </option>`;
        @endforeach
        
        const newItemHtml = `
            <div class="row g-4 mb-4 purchase-order-item" id="item_${purchaseOrderItemCount}">
                <div class="col-md-4">
                    <label class="form-label required">{{ __('passwords.product') }}</label>
                    <div class="position-relative">
                        <input type="text" 
                            id="product_search_${purchaseOrderItemCount}"
                            class="form-control product-typable-input"
                            list="product_list_${purchaseOrderItemCount}"
                            placeholder="Type or select product..."
                            autocomplete="off"
                            data-item-index="${purchaseOrderItemCount}">
                        <input type="hidden" 
                            name="items[${purchaseOrderItemCount}][product_variant_id]" 
                            id="product_id_${purchaseOrderItemCount}">
                        <datalist id="product_list_${purchaseOrderItemCount}">
                            <option value="">Select product</option>
                            ${variantOptions}
                        </datalist>
                    </div>
                    <div id="items.${purchaseOrderItemCount}.product_variant_id"></div>
                </div>
                <div class="col-md-2">
                    <label class="form-label required">{{ __('passwords.quantity') }}</label>
                    <input type="number" name="items[${purchaseOrderItemCount}][quantity]" class="form-control item-quantity" min="1" value="1">
                    <div id="items.${purchaseOrderItemCount}.quantity"></div>
                </div>
                <div class="col-md-2">
                    <label class="form-label required">{{ __('passwords.unit_cost') }}</label>
                    <input type="number" name="items[${purchaseOrderItemCount}][unit_cost]" class="form-control item-unit-cost" min="0.01" step="0.01" value="0.00">
                    <div id="items.${purchaseOrderItemCount}.unit_cost"></div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('passwords.total') }}</label>
                    <input type="text" class="form-control bg-light item-total" value="0.00" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-sm btn-danger w-100" onclick="removePurchaseOrderItem(this)">
                        <i class="bi bi-trash fs-5"></i>
                    </button>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', newItemHtml);
        
        // Initialize product search for the new row
        initProductSearch();
        
        // Add event listeners to the new inputs
        const newQuantityInput = document.querySelector(`input[name="items[${purchaseOrderItemCount}][quantity]"]`);
        const newUnitCostInput = document.querySelector(`input[name="items[${purchaseOrderItemCount}][unit_cost]"]`);
        
        if (newQuantityInput) {
            newQuantityInput.removeEventListener('input', newQuantityInput._handler);
            newQuantityInput._handler = function() { calculateItemTotal(purchaseOrderItemCount); };
            newQuantityInput.addEventListener('input', newQuantityInput._handler);
        }
        
        if (newUnitCostInput) {
            newUnitCostInput.removeEventListener('input', newUnitCostInput._handler);
            newUnitCostInput._handler = function() { calculateItemTotal(purchaseOrderItemCount); };
            newUnitCostInput.addEventListener('input', newUnitCostInput._handler);
        }
        
        // Enable remove buttons if there's more than one item
        updateRemoveButtons();
        
        console.log('Added new item row:', purchaseOrderItemCount);
    }

    // Function to remove purchase order item
    function removePurchaseOrderItem(button) {
        if (!button) return;
        
        const item = button.closest('.purchase-order-item');
        const items = document.querySelectorAll('.purchase-order-item');
        
        if (items.length > 1 && item) {
            item.remove();
            updateRemoveButtons();
            updateOrderSummary();
            console.log('Removed item row');
        }
    }

    // Function to update remove buttons state
    function updateRemoveButtons() {
        const items = document.querySelectorAll('.purchase-order-item');
        const removeButtons = document.querySelectorAll('.purchase-order-item .btn-danger');
        
        removeButtons.forEach((button, index) => {
            if (button) {
                button.disabled = items.length <= 1;
            }
        });
    }

    // Function to calculate item total
    function calculateItemTotal(itemIndex) {
        const itemContainer = document.getElementById(`item_${itemIndex}`);
        if (!itemContainer) {
            console.error('Item container not found:', itemIndex);
            return;
        }
        
        const quantityInput = itemContainer.querySelector(`input[name="items[${itemIndex}][quantity]"]`);
        const unitCostInput = itemContainer.querySelector(`input[name="items[${itemIndex}][unit_cost]"]`);
        const totalInput = itemContainer.querySelector('.item-total');
        
        if (!quantityInput || !unitCostInput || !totalInput) {
            console.error('Required inputs not found for item:', itemIndex);
            return;
        }
        
        const quantity = parseFloat(quantityInput.value) || 0;
        const unitCost = parseFloat(unitCostInput.value) || 0;
        const total = quantity * unitCost;
        
        totalInput.value = total.toFixed(2);
        
        // Update order summary
        updateOrderSummary();
    }

    // Function to update the order summary
    function updateOrderSummary() {
        const subtotalElement = document.getElementById('order_subtotal');
        const taxTotalElement = document.getElementById('order_tax_total');
        const grandTotalElement = document.getElementById('order_grand_total');
        
        if (!subtotalElement || !taxTotalElement || !grandTotalElement) {
            console.warn('Order summary elements not found');
            return;
        }
        
        let subtotal = 0;
        
        // Calculate subtotal from all items
        document.querySelectorAll('.purchase-order-item').forEach((item) => {
            const totalInput = item.querySelector('.item-total');
            if (totalInput && totalInput.value) {
                subtotal += parseFloat(totalInput.value) || 0;
            }
        });
        
        const taxTotal = 0;
        const grandTotal = subtotal + taxTotal;
        
        subtotalElement.textContent = subtotal.toFixed(2);
        taxTotalElement.textContent = taxTotal.toFixed(2);
        grandTotalElement.textContent = grandTotal.toFixed(2);
    }

    // Function to validate purchase order form
    function validatePurchaseOrderForm() {
        let isValid = true;
        
        // Clear previous errors
        document.querySelectorAll('.is-invalid').forEach(element => {
            element.classList.remove('is-invalid');
        });
        document.querySelectorAll('.invalid-feedback').forEach(element => {
            element.remove();
        });
        
        // Validate supplier
        const supplierSelect = document.querySelector('select[name="supplier_id"]');
        if (!supplierSelect || !supplierSelect.value) {
            showFieldError(supplierSelect, "Supplier is required");
            isValid = false;
        }
        
        // Validate location
        const locationSelect = document.querySelector('select[name="location_id"]');
        if (!locationSelect || !locationSelect.value) {
            showFieldError(locationSelect, "Location is required");
            isValid = false;
        }
        
        // Validate expected delivery date
        const deliveryDateInput = document.querySelector('input[name="expected_delivery_date"]');
        if (!deliveryDateInput || !deliveryDateInput.value) {
            showFieldError(deliveryDateInput, "Expected delivery date is required");
            isValid = false;
        }
        
        // Validate items
        const items = document.querySelectorAll('.purchase-order-item');
        let hasValidItems = false;
        
        items.forEach((item) => {
            const itemId = item.id.replace('item_', '');
            const productHidden = document.getElementById(`product_id_${itemId}`);
            const quantityInput = item.querySelector(`input[name="items[${itemId}][quantity]"]`);
            const unitCostInput = item.querySelector(`input[name="items[${itemId}][unit_cost]"]`);
            
            if (productHidden && productHidden.value) {
                hasValidItems = true;
                
                if (!quantityInput || !quantityInput.value || parseFloat(quantityInput.value) <= 0) {
                    showFieldError(quantityInput, "Valid quantity is required");
                    isValid = false;
                }
                
                if (!unitCostInput || !unitCostInput.value || parseFloat(unitCostInput.value) <= 0) {
                    showFieldError(unitCostInput, "Valid unit cost is required");
                    isValid = false;
                }
            }
        });
        
        if (!hasValidItems) {
            alert("At least one valid item is required");
            isValid = false;
        }
        
        return isValid;
    }

    // Function to show field error
    function showFieldError(field, message) {
        if (!field) return;
        
        field.classList.add('is-invalid');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }


</script>


<script>
    
    function submitPurchaseOrderForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
        const form = document.getElementById(formId);
        const submitButton = document.getElementById(submitButtonId);

        if (!form || !submitButton) {
            console.error('Form or button not found:', formId, submitButtonId);
            return;
        }

        // Collect form data using FormData directly
        const formData = new FormData(form);

        // Convert FormData to proper nested structure
        const data = {};
        for (let [key, value] of formData.entries()) {
            if (key.startsWith('items[')) {
                // Parse the nested structure
                const matches = key.match(/items\[(\d+)\]\[(.+)\]/);
                if (matches) {
                    const index = matches[1];
                    const field = matches[2];
                    if (!data.items) data.items = [];
                    if (!data.items[index]) data.items[index] = {};
                    data.items[index][field] = value;
                }
            } else {
                data[key] = value;
            }
        }

        // Add method and routeName
        data._method = method;
        data.routeName = url

        // Start loading
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Pass handling + data to reusable handler
        handleFormSubmission(data, submitButton, discardButtonId);
    }


    function updateSupplierInstance(uniqueId) {
        const submitButton = document.getElementById('editSupplierButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Select the form and create FormData from it
        var form = document.getElementById('kt_modal_supplier_form' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);

        // Set up the URL dynamically
        var updateUrl = '{{ route('suppliers.update', ['supplier' => ':id']) }}'.replace(':id', uniqueId);
        
        // Submit form data asynchronously
        handleEditResponse(data, updateUrl, uniqueId, submitButton);

    }
    
    function updateSupplierStatus(uniqueId, selectedStatus) {
        // Update label instantly
        const label = document.getElementById('supplier-label-' + uniqueId);
        if (label) {
            label.innerText = selectedStatus == 1 ? '{{ __("auth._active") }}' : '{{ __("auth._inactive") }}';
        }

        // Send update to backend
        const updateRoute = '/supplier-status/' + uniqueId;
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
    }


    // Individual action functions
    function submitForApproval(orderId) {
        const selectedStatus = 'pending_approval';
        const updateRoute = '/purchase-status/' + orderId;
        
        Swal.fire({
            title: '{{ __("passwords.submit_approval_title") }}',
            text: '{{ __("passwords.submit_approval_confirmation") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __("passwords.submit_approval") }}',
            cancelButtonText: '{{ __("passwords.cancel") }}',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return new Promise((resolve) => {
                    // Use LiveBlade to update status
                    LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
                    resolve();
                });
            }
        })
    }

    function approvePurchaseOrder(orderId) {
        
        const selectedStatus = 'approved';
        const updateRoute = '/purchase-approve/' + orderId;
        
        Swal.fire({
            title: '{{ __("passwords.approve_title") }}',
            text: '{{ __("passwords.approve_confirmation") }}',
            icon: 'success',
            showCancelButton: true,
            confirmButtonColor: '#25ae07ff',
            cancelButtonColor: '#202b1fff',
            confirmButtonText: '{{ __("passwords.approve") }}',
            cancelButtonText: '{{ __("passwords.cancel") }}',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return new Promise((resolve) => {
                    // Use LiveBlade to update status
                    LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
                    resolve();
                });
            }
        })
    }

    // Update payment summary when amount changes
    function updatePaymentSummary(orderId) {
        const amountInput = document.getElementById(`payment_amount_${orderId}`);
        const maxAmount = parseFloat(amountInput?.getAttribute('max')) || 0;
        const paymentAmount = parseFloat(amountInput?.value) || 0;
        
        const totalAmount = parseFloat(document.getElementById(`total_amount_${orderId}`)?.value) || 0;
        const totalPaid = parseFloat(document.getElementById(`total_paid_${orderId}`)?.value) || 0;
        
        // Calculate new balance
        const newBalance = maxAmount - paymentAmount;
        const isFullyPaid = newBalance <= 0;
        const status = isFullyPaid ? 'Fully Paid' : 'Partial Payment';
        const statusClass = isFullyPaid ? 'text-success' : 'text-warning';
        
        document.getElementById(`amount_paying_display_${orderId}`).textContent = paymentAmount.toFixed(2) + ' {{ currency_symbol() }}';
        document.getElementById(`new_balance_display_${orderId}`).textContent = newBalance.toFixed(2) + ' {{ currency_symbol() }}';
        document.getElementById(`payment_status_display_${orderId}`).textContent = status;
        document.getElementById(`payment_status_display_${orderId}`).className = `fw-bold ${statusClass}`;
        
        // Update the payment status select
        const statusSelect = document.getElementById(`payment_status_${orderId}`);
        if (statusSelect) {
            statusSelect.value = isFullyPaid ? 'paid' : 'partial';
        }
    }

    // Send to Supplier with Payment
    function sendToSupplierWithPayment(orderId) {
        const form = document.getElementById(`sendToSupplierForm${orderId}`);
        const submitButton = form.querySelector('button[type="button"]:last-child');
        
        if (!form) {
            toastr.error('Form not found');
            return;
        }

        // Collect form data
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Validate payment amount
        const paymentAmount = parseFloat(data.payment_amount) || 0;
        const totalAmount = parseFloat(document.getElementById(`payment_amount_${orderId}`)?.getAttribute('max')) || 0;
        
        if (paymentAmount <= 0) {
            toastr.error('Please enter a valid payment amount');
            return;
        }
        
        if (paymentAmount > totalAmount) {
            toastr.error('Payment amount cannot exceed the total amount');
            return;
        }

        // Show loading
        LiveBlade.toggleButtonLoading(submitButton, true);

        fetch(`/purchase-send/${orderId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            LiveBlade.toggleButtonLoading(submitButton, false);
            
            if (data.success) {
                toastr.success(data.message);
                const modal = bootstrap.Modal.getInstance(document.getElementById(`sendToSupplierModal${orderId}`));
                if (modal) modal.hide();
                
                // Refresh the page
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                toastr.error(data.message);
            }
        })
        .catch(error => {
            LiveBlade.toggleButtonLoading(submitButton, false);
            console.error('Error:', error);
            toastr.error('An error occurred while sending the order');
        });
    }

    function showPaymentRequired(orderId, balanceRemaining) {
        Swal.fire({
            title: '{{ __("passwords.payment_required") }}',
            html: `
                <div class="text-start">
                    <p>{{ __("passwords.cannot_receive_items_until_paid") }}</p>
                    <div class="alert alert-warning mt-3">
                        <strong>{{ __("passwords.balance_remaining") }}:</strong>
                        ${balanceRemaining.toFixed(2)} {{ currency_symbol() }}
                    </div>
                    <p class="mt-2 text-muted small">
                        <i class="bi bi-info-circle me-1"></i>
                        {{ __("passwords.pay_balance_to_receive") }}
                    </p>
                </div>
            `,
            icon: 'warning',
            confirmButtonColor: '#ffc107',
            confirmButtonText: '{{ __("passwords.pay_now") }}',
            showCancelButton: true,
            cancelButtonText: '{{ __("passwords.cancel") }}',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                // Open the send to supplier modal with balance amount
                const modal = new bootstrap.Modal(document.getElementById(`sendToSupplierModal${orderId}`));
                if (modal) {
                    // Pre-fill the payment amount with the balance
                    const paymentInput = document.getElementById(`payment_amount_${orderId}`);
                    if (paymentInput) {
                        paymentInput.value = balanceRemaining.toFixed(2);
                        updatePaymentSummary(orderId);
                    }
                    modal.show();
                }
            }
        });
    }

    // Submit receiving form
    function submitReceiving(orderId, status) {
        const form = document.getElementById(`receiveItemsForm${orderId}`);
        const formData = new FormData(form);
        formData.append('status', status);
        
        // Validate that at least one item has quantity > 0
        let hasQuantity = false;
        const receivingInputs = document.querySelectorAll(`#receiveItemsForm${orderId} .receiving-quantity`);
        receivingInputs.forEach(input => {
            if (parseInt(input.value) > 0) {
                hasQuantity = true;
            }
        });
        
        if (!hasQuantity) {
            Swal.fire({
                title: '{{ __("passwords.validation_error") }}',
                text: '{{ __("passwords.enter_quantity_for_at_least_one_item") }}',
                icon: 'warning',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }
        
        Swal.fire({
            title: status === 'received' ? '{{ __("passwords.mark_fully_received_title") }}' : '{{ __("passwords.mark_partially_received_title") }}',
            text: status === 'received' ? '{{ __("passwords.mark_fully_received_confirmation") }}' : '{{ __("passwords.mark_partially_received_confirmation") }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: status === 'received' ? '#198754' : '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: status === 'received' ? '{{ __("passwords.mark_received") }}' : '{{ __("passwords.mark_partial") }}',
            cancelButtonText: '{{ __("passwords.cancel") }}',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch(`/purchase-orders/${orderId}/receive-items`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message);
                    }
                    return data;
                });
            }
        })// In your submitReceiving function
        .then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: '{{ __("passwords.success") }}',
                    text: result.value.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            }
            }).catch(error => {
                Swal.fire({
                    title: '{{ __("passwords.error") }}',
                    text: error.message,
                    icon: 'error',
                    confirmButtonColor: '#0d6efd'
                });
            });
    }

    function cancelPurchaseOrder(orderId) {
        const selectedStatus = 'cancelled';
        const updateRoute = '/purchase-cancel/' + orderId;
        
        Swal.fire({
            title: '{{ __("passwords.cancel_title") }}',
            text: '{{ __("passwords.cancel_confirmation") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __("passwords.cancel_order") }}',
            cancelButtonText: '{{ __("passwords.keep_order") }}',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return new Promise((resolve) => {
                    // Use LiveBlade to update status
                    LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
                    resolve();
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: '{{ __("passwords.success") }}',
                    text: '{{ __("passwords.cancel_success") }}',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            }
        });
    }

    

</script>




<!-- Suppliers and Purchases -->
<script>
    
    function submitSupplierForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
        const form = document.getElementById(formId);
        const submitButton = document.getElementById(submitButtonId);

        if (!form || !submitButton) {
            console.error('Form or button not found:', formId, submitButtonId);
            return;
        }

        // Collect form data
        const formData = Object.fromEntries(new FormData(form));
        formData._method = method;
        formData.routeName = url;

        // Start loading
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Pass handling + data to reusable handler
        handleFormSubmission(formData, submitButton, discardButtonId);
    }


    function updateSupplierInstance(uniqueId) {
        const submitButton = document.getElementById('editSupplierButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Select the form and create FormData from it
        var form = document.getElementById('kt_modal_supplier_form' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);

        // Set up the URL dynamically
        var updateUrl = '{{ route('suppliers.update', ['supplier' => ':id']) }}'.replace(':id', uniqueId);
        
        // Submit form data asynchronously
        handleEditResponse(data, updateUrl, uniqueId, submitButton);

    }
    
    function updateSupplierStatus(uniqueId, selectedStatus) {
        // Update label instantly
        const label = document.getElementById('supplier-label-' + uniqueId);
        if (label) {
            label.innerText = selectedStatus == 1 ? '{{ __("auth._active") }}' : '{{ __("auth._inactive") }}';
        }

        // Send update to backend
        const updateRoute = '/supplier-status/' + uniqueId;
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
    }

</script>






<script>
// ── START PRODUCTION WITH PAYMENT ─────────────────────────────────────────────
function startProductionWithPayment(orderId) {
    // ✅ Find the form (it should always exist now)
    const form = document.getElementById(`startProductionForm${orderId}`);
    
    if (!form) {
        console.error('Form not found for order:', orderId);
        Swal.fire({
            title: '{{ __("passwords.error") }}',
            text: 'Could not find production form. Please refresh the page and try again.',
            icon: 'error',
            confirmButtonColor: '#0d6efd'
        });
        return;
    }

    // Get the submit button
    const submitButton = form.closest('.modal-body').querySelector('.btn-warning');
    
    // Show loading
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> {{ __("passwords.processing") }}...';
    }

    const formData = new FormData(form);
    const data = {
        order_id: formData.get('order_id'),
        payment_method_id: formData.get('payment_method_id'),
        withdrawal_amount: formData.get('withdrawal_amount'),
        notes: formData.get('notes') || null,
        estimated_cost: formData.get('estimated_cost'),
        _token: document.querySelector('meta[name="csrf-token"]').content
    };

    // ✅ Check if payment is required (estimated_cost > 0)
    const estimatedCost = parseFloat(data.estimated_cost) || 0;
    const isPaymentRequired = estimatedCost > 0;

    // ✅ If payment is required, validate payment method
    if (isPaymentRequired) {
        if (!data.payment_method_id) {
            Swal.fire({
                title: '{{ __("passwords.validation_error") }}',
                text: '{{ __("payments.select_payment_method") }}',
                icon: 'warning',
                confirmButtonColor: '#0d6efd'
            });
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="bi bi-play-fill me-2"></i> {{ __("passwords.start_production") }}';
            }
            return;
        }

        // Validate amount
        const amount = parseFloat(data.withdrawal_amount);
        if (!amount || amount <= 0) {
            Swal.fire({
                title: '{{ __("passwords.validation_error") }}',
                text: '{{ __("passwords.please_enter_valid_amount") }}',
                icon: 'warning',
                confirmButtonColor: '#0d6efd'
            });
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="bi bi-play-fill me-2"></i> {{ __("passwords.start_production") }}';
            }
            return;
        }
    }

    // Get payment method name for display (if payment is required)
    let paymentMethodName = 'N/A';
    if (isPaymentRequired) {
        const paymentMethodSelect = form.querySelector('select[name="payment_method_id"]');
        paymentMethodName = paymentMethodSelect.options[paymentMethodSelect.selectedIndex]?.text || 'N/A';
    }

    // Build confirmation message
    let confirmationHtml = `{{ __("passwords.start_production_confirmation") }}<br><br>`;
    if (isPaymentRequired) {
        confirmationHtml += `
            <strong>{{ __("passwords.production_cost") }}:</strong> {{ currency_symbol() }}${parseFloat(data.withdrawal_amount).toFixed(2)}<br>
            <strong>{{ __("payments.payment_method") }}:</strong> ${paymentMethodName}
        `;
    } else {
        confirmationHtml += `
            <strong>{{ __("passwords.production_cost") }}:</strong> {{ currency_symbol() }}0.00<br>
            <strong>{{ __("passwords.payment_status") }}:</strong> {{ __("passwords.no_payment_required") }}
        `;
    }

    // Show confirmation
    Swal.fire({
        title: '{{ __("passwords.start_production") }}',
        html: confirmationHtml,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '{{ __("passwords.start") }}',
        cancelButtonText: '{{ __("passwords.cancel") }}',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`/production-orders/${orderId}/start-with-payment`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message);
                }
                return data;
            })
            .catch(error => {
                throw new Error(error.message);
            });
        }
    })
    .then((result) => {
        if (result.isConfirmed && result.value) {
            const modal = bootstrap.Modal.getInstance(document.getElementById(`startProductionModal${orderId}`));
            if (modal) modal.hide();

            Swal.fire({
                title: '{{ __("passwords.success") }}',
                text: result.value.message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                if (result.value.redirect) {
                    window.location.href = result.value.redirect;
                } else {
                    location.reload();
                }
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: '{{ __("passwords.error") }}',
            text: error.message || '{{ __("passwords.production_start_failed") }}',
            icon: 'error',
            confirmButtonColor: '#0d6efd'
        });
    })
    .finally(() => {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="bi bi-play-fill me-2"></i> {{ __("passwords.start_production") }}';
        }
    });
}



// ── COMPLETE PRODUCTION WITH OUTPUTS ──────────────────────────────────────────
function completeProductionWithOutputs(orderId) {
    const form = document.getElementById(`completeProductionForm${orderId}`);
    if (!form) {
        console.error('Form not found for order:', orderId);
        Swal.fire({
            title: '{{ __("passwords.error") }}',
            text: 'Could not find production form. Please refresh the page and try again.',
            icon: 'error',
            confirmButtonColor: '#0d6efd'
        });
        return;
    }

    const submitButton = form.querySelector('button.btn-success');
    
    // Show loading
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> {{ __("passwords.processing") }}...';
    }

    // ✅ Get form data including batch and expiry
    const formData = new FormData(form);
    
    const data = {
        outputs: [],
        complete: true,
        batch_number: formData.get('batch_number') || null,
        expiry_date: formData.get('expiry_date') || null,
        notes: formData.get('notes') || null
    };

    // ✅ Collect output data - allow some to be 0
    let hasQuantity = false;
    const errors = [];
    let outputCount = 0;

    document.querySelectorAll(`#completeProductionForm${orderId} .actual-quantity-input`).forEach(input => {
        outputCount++;
        const outputId = input.dataset.outputId;
        const actualQuantity = parseFloat(input.value) || 0;
        
        // ✅ Get defective quantity from the same row
        const row = input.closest('.card-dashed');
        const defectiveInput = row ? row.querySelector('.defective-quantity-input') : null;
        const defectiveQuantity = defectiveInput ? parseFloat(defectiveInput.value) || 0 : 0;

        // ✅ Get product name for error messages
        const nameEl = row ? row.querySelector('.fw-bold.mb-0') : null;
        const productName = nameEl ? nameEl.textContent : 'Product';

        // ✅ Track if any output has quantity > 0
        if (actualQuantity > 0) {
            hasQuantity = true;
        }

        // ✅ Validate: Actual quantity cannot be negative
        if (actualQuantity < 0) {
            errors.push(`"${productName}" - Actual quantity cannot be negative`);
        }

        // ✅ Validate: Defective quantity cannot exceed actual quantity
        if (defectiveQuantity > actualQuantity) {
            errors.push(`"${productName}" - Defective quantity (${defectiveQuantity}) cannot exceed actual quantity (${actualQuantity})`);
        }

        data.outputs.push({
            output_id: outputId,
            actual_quantity: actualQuantity,
            defective_quantity: defectiveQuantity
        });
    });

    // ✅ Show specific validation errors
    if (errors.length > 0) {
        Swal.fire({
            title: '{{ __("passwords.validation_error") }}',
            html: errors.join('<br>'),
            icon: 'warning',
            confirmButtonColor: '#0d6efd'
        });
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="bi bi-check-circle me-2"></i> {{ __("passwords.complete_production") }}';
        }
        return;
    }

    // ✅ Validate: At least one output must have quantity > 0
    if (!hasQuantity) {
        Swal.fire({
            title: '{{ __("passwords.validation_error") }}',
            text: '{{ __("passwords.enter_at_least_one_actual_quantity") }}',
            icon: 'warning',
            confirmButtonColor: '#0d6efd'
        });
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="bi bi-check-circle me-2"></i> {{ __("passwords.complete_production") }}';
        }
        return;
    }

    // ✅ Show confirmation with summary including batch info
    let summaryHtml = '{{ __("passwords.complete_production_confirmation") }}<br><br>';
    
    document.querySelectorAll(`#completeProductionForm${orderId} .actual-quantity-input`).forEach(input => {
        const actualQuantity = parseFloat(input.value) || 0;
        
        const row = input.closest('.card-dashed');
        const defectiveInput = row ? row.querySelector('.defective-quantity-input') : null;
        const defectiveQuantity = defectiveInput ? parseFloat(defectiveInput.value) || 0 : 0;
        const nameEl = row ? row.querySelector('.fw-bold.mb-0') : null;
        const productName = nameEl ? nameEl.textContent : 'Product';
        
        if (actualQuantity > 0) {
            summaryHtml += `<strong>${productName}:</strong> ${actualQuantity} units`;
            if (defectiveQuantity > 0) {
                summaryHtml += ` (${defectiveQuantity} defective)`;
            }
            summaryHtml += `<br>`;
        }
    });

    // ✅ Add batch and expiry info to confirmation
    if (data.batch_number) {
        summaryHtml += `<br><strong>{{ __("passwords.batch_number") }}:</strong> ${data.batch_number}`;
    }
    if (data.expiry_date) {
        summaryHtml += `<br><strong>{{ __("passwords.expiry_date") }}:</strong> ${data.expiry_date}`;
    }
    if (data.notes) {
        summaryHtml += `<br><strong>{{ __("passwords.notes") }}:</strong> ${data.notes}`;
    }

    Swal.fire({
        title: '{{ __("passwords.complete_production") }}',
        html: summaryHtml,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '{{ __("passwords.complete") }}',
        cancelButtonText: '{{ __("passwords.cancel") }}',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`/production-orders/${orderId}/complete-with-outputs`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message);
                }
                return data;
            })
            .catch(error => {
                throw new Error(error.message);
            });
        }
    })
    .then((result) => {
        if (result.isConfirmed && result.value) {
            const modal = bootstrap.Modal.getInstance(document.getElementById(`completeProductionModal${orderId}`));
            if (modal) modal.hide();

            Swal.fire({
                title: '{{ __("passwords.success") }}',
                text: result.value.message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                if (result.value.redirect) {
                    window.location.href = result.value.redirect;
                } else {
                    location.reload();
                }
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: '{{ __("passwords.error") }}',
            text: error.message || '{{ __("passwords.production_complete_failed") }}',
            icon: 'error',
            confirmButtonColor: '#0d6efd'
        });
    })
    .finally(() => {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="bi bi-check-circle me-2"></i> {{ __("passwords.complete_production") }}';
        }
    });
}

// ── REAL-TIME SUMMARY UPDATE ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('input', function(e) {
        const target = e.target;
        if (target.classList.contains('actual-quantity-input') || target.classList.contains('defective-quantity-input')) {
            updateProductionSummary();
        }
    });
});

function updateProductionSummary() {
    let totalProduced = 0;
    let totalDefective = 0;
    
    document.querySelectorAll('.actual-quantity-input').forEach(input => {
        totalProduced += parseFloat(input.value) || 0;
    });
    
    document.querySelectorAll('.defective-quantity-input').forEach(input => {
        totalDefective += parseFloat(input.value) || 0;
    });
    
    // Update displays if they exist
    const producedDisplay = document.getElementById('total_produced_display');
    const defectiveDisplay = document.getElementById('total_defective_display');
    
    if (producedDisplay) producedDisplay.textContent = totalProduced.toFixed(2);
    if (defectiveDisplay) defectiveDisplay.textContent = totalDefective.toFixed(2);
}

// ── CANCEL PRODUCTION ────────────────────────────────────────────────────────
function cancelProduction(orderId) {
    Swal.fire({
        title: '{{ __("passwords.cancel_production") }}',
        text: '{{ __("passwords.cancel_production_confirmation") }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '{{ __("passwords.cancel") }}',
        cancelButtonText: '{{ __("passwords.keep") }}',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`/production-orders/${orderId}/cancel`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message);
                }
                return data;
            });
        }
    })
    .then((result) => {
        if (result.isConfirmed && result.value) {
            Swal.fire({
                title: '{{ __("passwords.success") }}',
                text: result.value.message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: '{{ __("passwords.error") }}',
            text: error.message,
            icon: 'error',
            confirmButtonColor: '#0d6efd'
        });
    });
}
</script>






<!-- Expense -->
<script>

    function updateExpenseCategory(uniqueId) {
        const submitButton = document.getElementById('editCategoryButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Select the form and create FormData from it
        var form = document.getElementById('kt_modal_edit_expense_category_form' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);

        // Set up the URL dynamically
        var updateUrl = '{{ route('expense-category.update', ['expense_category' => ':id']) }}'.replace(':id', uniqueId);
        
        // Submit form data asynchronously
        handleEditResponse(data, updateUrl, uniqueId, submitButton);

    }

    function submitExpenseCategoryForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
        const form = document.getElementById(formId);
        const submitButton = document.getElementById(submitButtonId);

        if (!form || !submitButton) {
            console.error('Form or button not found:', formId, submitButtonId);
            return;
        }

        // Collect form data
        const formData = Object.fromEntries(new FormData(form));
        formData._method = method;
        formData.routeName = url;

        // Start loading
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Pass handling + data to reusable handler
        handleFormSubmission(formData, submitButton, discardButtonId);
    }

    // Expense
    function createExpense(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
        const form = document.getElementById(formId);
        const submitButton = document.getElementById(submitButtonId);

        if (!form || !submitButton) {
            console.error('Form or button not found:', formId, submitButtonId);
            return;
        }

        // Collect form data using FormData to properly handle arrays
        const formDataObj = new FormData(form);
        
        // Remove existing selected_taxes entries to avoid duplication
        formDataObj.delete('selected_taxes[]');
        
        // Get all checked tax checkboxes
        const taxCheckboxes = document.querySelectorAll('.expense-tax-checkbox:checked');
        
        // Append each checked checkbox value
        taxCheckboxes.forEach(checkbox => {
            formDataObj.append('selected_taxes[]', checkbox.value);
        });
        
        // If no taxes selected, append an empty value to ensure the field exists
        if (taxCheckboxes.length === 0) {
            formDataObj.append('selected_taxes[]', '');
        }
        
        // Get total tax and net amount from hidden fields (calculated by preview)
        const totalTaxAmount = document.getElementById('expense_total_tax_amount')?.value;
        const netAmount = document.getElementById('expense_net_amount')?.value;
        
        // Add tax amounts if they exist and are greater than 0
        if (totalTaxAmount && parseFloat(totalTaxAmount) > 0) {
            formDataObj.append('total_tax_amount', totalTaxAmount);
            formDataObj.append('net_amount', netAmount);
        }
        
        // Convert FormData to object for the existing handler
        const formData = {};
        for (let [key, value] of formDataObj.entries()) {
            // Handle array values specially
            if (key.endsWith('[]')) {
                const arrayKey = key.slice(0, -2);
                if (!formData[arrayKey]) {
                    formData[arrayKey] = [];
                }
                if (value !== '') { // Don't push empty values
                    formData[arrayKey].push(value);
                }
            } else {
                formData[key] = value;
            }
        }
        
        // Add method and route
        formData._method = method;
        formData.routeName = url;

        // Debug log
        console.log('Form data being sent:', formData);

        // Start loading
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Pass handling + data to reusable handler
        handleFormSubmission(formData, submitButton, discardButtonId);
    }

    function updateExpenseStatus(uniqueId, selectedStatus) {
        // Update label instantly
        const label = document.getElementById('expense-label-' + uniqueId);
        if (label) {
            label.innerText = selectedStatus == 'paid' ? 'Paid' : 
                            selectedStatus == 'reimbursed' ? 'Reimbursed' : 'Pending';
        }

        // Send update to backend - just the status value
        // console.log(selectedStatus)
        const updateRoute = '/expense-status/' + uniqueId;
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
    }

    
    function updateExpense(uniqueId) {
        const submitButton = document.getElementById('editExpenseButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Select the form and create FormData from it
        var form = document.getElementById('editExpenseForm' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);

        // Set up the URL dynamically
        var updateUrl = '{{ route('expense.update', ['expense' => ':id']) }}'.replace(':id', uniqueId);
        
        // Submit form data asynchronously
        handleEditResponse(data, updateUrl, uniqueId, submitButton);

    }

    /**
     * Approve expense
     */
    function approveExpense(uniqueId, selectedStatus) {
        // Update label instantly
        const label = document.getElementById('approve-label-' + uniqueId);
        if (label) {
            label.innerText = selectedStatus == 1 ? '{{ __("pagination.approved") }}' : '{{ __("pagination.pending") }}';
        }
        
        // Also update the switch element if needed
        const switchEl = document.getElementById('approve-switch-' + uniqueId);
        if (switchEl) {
            switchEl.checked = selectedStatus == 1;
        }

        // Send update to backend
        const updateRoute = '/expenses/' + uniqueId + '/approve';
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
    }

   
</script>

<!-- Expenses continue  -->
<script>
    // Calculate tax preview for expense
    function calculateExpenseTaxPreview() {
        const amount = parseFloat(document.getElementById('expense_amount').value) || 0;
        
        if (amount === 0) {
            Swal.fire('{{ __("passwords.info") }}', '{{ __("passwords.please_enter_amount_first") }}', 'info');
            return;
        }
        
        // Get selected taxes
        const selectedTaxes = [];
        document.querySelectorAll('.expense-tax-checkbox:checked').forEach(checkbox => {
            selectedTaxes.push({
                id: checkbox.value,
                rate: parseFloat(checkbox.dataset.rate),
                type: checkbox.dataset.type,
                name: checkbox.dataset.name,
                is_withholding: checkbox.dataset.is_withholding === 'true'
            });
        });
        
        if (selectedTaxes.length === 0) {
            Swal.fire('{{ __("passwords.info") }}', '{{ __("passwords.please_select_at_least_one_tax") }}', 'info');
            return;
        }
        
        // Calculate taxes
        let additiveTax = 0;
        let withholdingTax = 0;
        let taxBreakdown = [];
        
        selectedTaxes.forEach(tax => {
            let taxAmount = 0;
            if (tax.type === 'percentage') {
                taxAmount = amount * (tax.rate / 100);
            } else {
                taxAmount = tax.rate; // Fixed amount
            }
            
            if (tax.is_withholding) {
                withholdingTax += taxAmount;
            } else {
                additiveTax += taxAmount;
            }
            
            taxBreakdown.push({
                name: tax.name,
                rate: tax.rate,
                type: tax.type,
                amount: taxAmount,
                is_withholding: tax.is_withholding
            });
        });
        
        const totalTax = additiveTax + withholdingTax;
        const totalAmount = amount + additiveTax - withholdingTax;
        
        // Update preview section
        document.getElementById('expense_preview_taxable').innerText = amount.toFixed(2);
        document.getElementById('expense_preview_tax').innerText = totalTax.toFixed(2);
        document.getElementById('expense_preview_total').innerText = totalAmount.toFixed(2);
        
        // UPDATE HIDDEN FIELDS - THESE WILL BE SENT TO BACKEND
        document.getElementById('expense_total_tax_amount').value = totalTax;
        document.getElementById('expense_net_amount').value = totalAmount;
        
        // Also update the amount field if needed (optional)
        // document.getElementById('expense_amount').value = totalAmount;
        
        // Build breakdown table
        const tbody = document.getElementById('expense_tax_breakdown_body');
        tbody.innerHTML = '';
        
        taxBreakdown.forEach(tax => {
            const effect = tax.is_withholding ? 
                '<span class="badge badge-light-danger">{{ __("passwords.deducted") }}</span>' : 
                '<span class="badge badge-light-primary">{{ __("passwords.added") }}</span>';
            
            const typeLabel = tax.type === 'percentage' ? `${tax.rate}%` : `{{ __("passwords.fixed") }} ${tax.rate}`;
            
            const row = `
                <tr>
                    <td><strong>${tax.name}</strong></td>
                    <td><span class="badge badge-light-info">${typeLabel}</span></td>
                    <td>${typeLabel}</td>
                    <td class="text-end">${tax.amount.toFixed(2)}</td>
                    <td class="text-center">${effect}</td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
        
        // Show preview
        document.getElementById('expense_tax_preview').classList.remove('d-none');
        
        Swal.fire('{{ __("passwords.success") }}', '{{ __("passwords.tax_calculation_completed") }}', 'success');
    }
        

    function calculateEditTotal(expenseId) {
        const grossAmount = parseFloat(document.getElementById('editGrossAmount' + expenseId).value) || 0;
        const taxAmount = parseFloat(document.getElementById('editTaxAmount' + expenseId).value) || 0;
        const totalAmount = grossAmount + taxAmount;
        
        document.getElementById('editTotalAmount' + expenseId).value = totalAmount.toFixed(2);
    }
    
</script>









<!-- Employee Leave  -->

<!-- BEGIN: PAGE SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
    let calendar;
    let currentView = 'calendar';

    document.addEventListener('DOMContentLoaded', function() {
        initializeCalendar();
        initializeTooltips();
    });

    function initializeCalendar() {
        const calendarEl = document.getElementById('kt_calendar');
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            initialView: 'dayGridMonth',
            locale: 'en',
            height: 'auto',
            editable: false,
            selectable: true,
            selectMirror: true,
            dayMaxEvents: true,
            weekends: true,
            
            events: {
                url: '{{ route("leave.calendar.events") }}',
                method: 'GET',
                failure: function() {
                    console.error('Error loading calendar events');
                }
            },
            
            eventClick: function(info) {
                viewLeave(info.event.id);
            },
            
            eventDidMount: function(info) {
                // Add tooltip to events
                const tooltip = new bootstrap.Tooltip(info.el, {
                    title: `${info.event.title} (${info.event.extendedProps.leave_type})`,
                    placement: 'top',
                    trigger: 'hover',
                    container: 'body'
                });
            },
            
            loading: function(isLoading) {
                if (!isLoading) {
                    // Calendar loaded
                }
            }
        });
        
        calendar.render();
    }

    function toggleView(view) {
        currentView = view;
        
        if (view === 'calendar') {
            document.getElementById('calendarView').style.display = 'block';
            document.getElementById('listView').style.display = 'none';
            document.getElementById('viewCalendarBtn').classList.add('active');
            document.getElementById('viewListBtn').classList.remove('active');
            calendar.render();
        } else {
            document.getElementById('calendarView').style.display = 'none';
            document.getElementById('listView').style.display = 'block';
            document.getElementById('viewListBtn').classList.add('active');
            document.getElementById('viewCalendarBtn').classList.remove('active');
        }
    }

    function applyFilters() {
        const status = document.getElementById('status_filter').value;
        const employee = document.getElementById('employee_filter').value;
        const type = document.getElementById('type_filter').value;
        const dateFrom = document.getElementById('date_from').value;
        const dateTo = document.getElementById('date_to').value;
        
        // Build URL with filters
        const params = new URLSearchParams();
        if (status) params.append('status', status);
        if (employee) params.append('employee_id', employee);
        if (type) params.append('leave_type', type);
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        
        window.location.href = window.location.pathname + '?' + params.toString();
    }

    function resetFilters() {
        window.location.href = window.location.pathname;
    }



    function approveLeave(id) {
        Swal.fire({
            title: '{{ __("payments.confirm_approve") }}',
            text: '{{ __("payments.confirm_approve_message") }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '{{ __("payments.yes_approve") }}',
            cancelButtonText: '{{ __("auth._cancel") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('/leave/' + id + '/approve', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Success', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }


    function cancelLeave(id) {
        Swal.fire({
            title: '{{ __("payments.confirm_cancel") }}',
            text: '{{ __("payments.confirm_cancel_message") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '{{ __("payments.yes_cancel") }}',
            cancelButtonText: '{{ __("auth._cancel") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('/leave/' + id + '/cancel', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Success', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }

    function deleteLeave(id) {
        Swal.fire({
            title: '{{ __("payments.confirm_delete") }}',
            text: '{{ __("payments.confirm_delete_message") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '{{ __("payments.yes_delete") }}',
            cancelButtonText: '{{ __("auth._cancel") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('/leave/' + id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Success', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }

    function initializeTooltips() {
        var tooltips = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltips.map(function(el) {
            return new bootstrap.Tooltip(el);
        });
    }
</script>
<!-- END: PAGE SCRIPTS -->




<!-- Employee Payment -->
<script>
    
    function submitFormEmployeePayment(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
        const form = document.getElementById(formId);
        const submitButton = document.getElementById(submitButtonId);

        if (!form || !submitButton) {
            console.error('Form or button not found:', formId, submitButtonId);
            return;
        }

        // Use FormData instead of Object.fromEntries to properly handle array inputs
        const formData = new FormData(form);
        
        // IMPORTANT FIX: Handle selected_taxes[] properly
        // Remove any existing selected_taxes entries to avoid duplication
        formData.delete('selected_taxes[]');
        
        // Get all checked tax checkboxes
        // Note: You'll need to adjust the selector based on your form's structure
        const taxCheckboxes = form.querySelectorAll('input[name="selected_taxes[]"]:checked');
        console.log('Found checked tax checkboxes:', taxCheckboxes.length);
        
        if (taxCheckboxes.length > 0) {
            // Append each checked checkbox value
            taxCheckboxes.forEach(checkbox => {
                formData.append('selected_taxes[]', checkbox.value);
                console.log('Appending tax ID:', checkbox.value);
            });
        } else {
            // If no taxes selected, append an empty value to ensure the field exists
            // This ensures the array is sent even if empty
            formData.append('selected_taxes[]', '');
        }

        // Add method override
        formData.append('_method', method);
        
        // Add route name if needed
        formData.append('routeName', url);

        // Debug: Log all form entries being sent
        console.log('Form entries being sent:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        // Start loading
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Convert FormData to object for the existing handler
        // This maintains compatibility with your handleFormSubmission function
        const data = {};
        for (let pair of formData.entries()) {
            // Handle array values specially
            if (pair[0].endsWith('[]')) {
                const key = pair[0].slice(0, -2);
                if (!data[key]) {
                    data[key] = [];
                }
                if (pair[1] !== '') { // Don't push empty values
                    data[key].push(pair[1]);
                }
            } else {
                data[pair[0]] = pair[1];
            }
        }

        // Pass handling + data to reusable handler
        handleFormSubmission(data, submitButton, discardButtonId);
    }


    function editEmployeePayment(uniqueId) {
        const submitButton = document.getElementById('editEmployeePaymentButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Select the form and create FormData from it
        var form = document.getElementById('editPaymentForm' + uniqueId);
        var formData = new FormData(form);

        // CRITICAL FIX: Remove hidden tax fields so they don't override server calculations
        // These should be calculated on the server, not submitted from the form
        formData.delete('total_tax_amount');
        formData.delete('net_amount');
        
        // Also remove the display fields if they're in the form
        formData.delete('edit_total_tax_' + uniqueId);
        formData.delete('edit_net_amount_' + uniqueId);

        // IMPORTANT FIX: Handle selected_taxes[] properly
        // Remove any existing selected_taxes entries
        formData.delete('selected_taxes[]');
        formData.delete('selected_taxes');
        
        // Get all checked tax checkboxes for this payment
        const taxCheckboxes = document.querySelectorAll('.edit-tax-checkbox-' + uniqueId + ':checked');
        console.log('Found checked tax checkboxes:', taxCheckboxes.length);
        
        if (taxCheckboxes.length > 0) {
            // For multiple checkboxes, append each one with the same key
            taxCheckboxes.forEach(checkbox => {
                formData.append('selected_taxes[]', checkbox.value);
                console.log('Appending tax ID:', checkbox.value);
            });
        } else {
            // If no taxes are selected, we need to send an empty array
            // This ensures the field exists in the request
            formData.append('selected_taxes[]', '');
        }

        // Debug: Log all form entries being sent
        console.log('Form entries being sent:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        // Set up the URL dynamically
        var updateUrl = '/payment/' + uniqueId;
        
        // Submit form data asynchronously
        fetch(updateUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(data => {
            LiveBlade.toggleButtonLoading(submitButton, false);
            console.log('Response data:', data);
            
            if (data.success) {
                if (typeof toastr !== 'undefined') {
                    toastr.success(data.message);
                } else {
                    alert(data.message);
                }
                
                $('#editPaymentModal' + uniqueId).modal('hide');
                
                if (data.reload) {
                    location.reload();
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } else {
                // Handle validation errors
                if (data.errors) {
                    Object.keys(data.errors).forEach(key => {
                        const errorElement = document.getElementById(key + uniqueId);
                        if (errorElement) {
                            errorElement.innerHTML = '<span class="text-danger">' + data.errors[key][0] + '</span>';
                        }
                    });
                }
                
                if (typeof toastr !== 'undefined') {
                    toastr.error(data.message || 'Error updating payment');
                } else {
                    alert(data.message || 'Error updating payment');
                }
            }
        })
        .catch(error => {
            LiveBlade.toggleButtonLoading(submitButton, false);
            console.error('Error:', error);
            
            if (error.errors) {
                // Display validation errors
                Object.keys(error.errors).forEach(key => {
                    const errorElement = document.getElementById(key + uniqueId);
                    if (errorElement) {
                        errorElement.innerHTML = '<span class="text-danger">' + error.errors[key][0] + '</span>';
                    }
                });
            }
            
            alert('An error occurred while updating the payment');
        });
    }
        
    function updatePaymentStatus(uniqueId, selectedStatus) {
        // Get the current status from the select element
        const selectElement = event.target;
        const currentStatus = selectElement.dataset.currentStatus || 
                            document.querySelector(`select[name="status"][data-payment-id="${uniqueId}"]`)?.dataset.currentStatus;
        
        // Prevent changing from completed status
        if (currentStatus === 'completed') {
            // Reset to completed
            selectElement.value = 'completed';
            
            // Show error message
            showErrorNotification('{{ __("auth.cannot_change_completed_payment") }}');
            return;
        }

        // Update label instantly
        const label = document.getElementById('supplier-label-' + uniqueId);
        if (label) {
            // You might want to adjust this based on your actual label logic
            label.innerText = selectedStatus === 'completed' ? '{{ __("payments.completed") }}' : 
                            selectedStatus === 'failed' ? '{{ __("payments.failed") }}' : 
                            selectedStatus === 'cancelled' ? '{{ __("payments.cancelled") }}' : 
                            '{{ __("payments.pending") }}';
        }

        // Send update to backend
        const updateRoute = '/payment-status/' + uniqueId;
        
        // You'll need to adjust this based on your actual LiveBlade implementation
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
    }

    // Function to load employee advances for create modal
    function loadEmployeeAdvances() {
        const employeeId = $('#employee_select').val();
        const paymentType = $('#payment_type').val();
        
        if (!employeeId) {
            $('#advance_deductions_container').html(`
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-arrow-up fs-2 mb-3 d-block"></i>
                    <span>{{ __('payments.select_employee_to_view_advances') }}</span>
                </div>
            `);
            $('#advance_deduction_preview').addClass('d-none');
            return;
        }

        $('#advance_deductions_container').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-warning" role="status">
                    <span class="visually-hidden">{{ __('payments.loading') }}</span>
                </div>
                <p class="mt-2 text-muted">{{ __('payments.loading_advances') }}</p>
            </div>
        `);

        // No payment_id for create mode
        fetch(`/employee-advances/${employeeId}/active?payment_type=${paymentType}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.advances.length > 0) {
                renderAdvancesTable(data.advances, 'create', null);
            } else {
                $('#advance_deductions_container').html(`
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-check-circle fs-2 mb-3 text-success d-block"></i>
                        <span>${data.message || '{{ __("payments.no_active_advances") }}'}</span>
                    </div>
                `);
            }
        })
        .catch(error => {
            console.error('Error loading advances:', error);
            $('#advance_deductions_container').html(`
                <div class="text-center py-5 text-danger">
                    <i class="fas fa-exclamation-triangle fs-2 mb-3 d-block"></i>
                    <span>{{ __('payments.error_loading_advances') }}</span>
                </div>
            `);
        });
    }

    // Function to load employee advances for edit modal
    function editLoadEmployeeAdvances(paymentId) {
        const employeeId = document.getElementById('edit_employee_select_' + paymentId).value;
        const paymentType = document.getElementById('edit_payment_type_' + paymentId).value;
        
        if (!employeeId) {
            document.getElementById('edit_advance_deductions_container_' + paymentId).innerHTML = `
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-arrow-up fs-2 mb-3 d-block"></i>
                    <span>{{ __('payments.select_employee_to_view_advances') }}</span>
                </div>
            `;
            return;
        }

        document.getElementById('edit_advance_deductions_container_' + paymentId).innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-warning" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">{{ __('payments.loading_advances') }}</p>
            </div>
        `;

        // Pass payment_id for edit mode to include previously selected advances
        fetch(`/employee-advances/${employeeId}/active?payment_type=${paymentType}&payment_id=${paymentId}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.advances.length > 0) {
                renderAdvancesTable(data.advances, 'edit', paymentId);
            } else {
                document.getElementById('edit_advance_deductions_container_' + paymentId).innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-check-circle fs-2 mb-3 text-success d-block"></i>
                        <span>${data.message || '{{ __("payments.no_active_advances") }}'}</span>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading advances:', error);
            document.getElementById('edit_advance_deductions_container_' + paymentId).innerHTML = `
                <div class="text-center py-5 text-danger">
                    <i class="fas fa-exclamation-triangle fs-2 mb-3 d-block"></i>
                    <span>{{ __('payments.error_loading_advances') }}</span>
                </div>
            `;
        });
    }

    // Shared function to render advances table
    function renderAdvancesTable(advances, mode, paymentId) {
        // Get existing deductions if in edit mode
        let existingAdvanceIds = [];
        let existingDeductions = {};
        
        if (mode === 'edit' && paymentId) {
            const existingDeductionsInput = document.getElementById('edit_advance_deductions_' + paymentId).value;
            if (existingDeductionsInput && existingDeductionsInput !== '[]') {
                const deductions = JSON.parse(existingDeductionsInput);
                existingAdvanceIds = deductions.map(d => d.advance_id.toString());
                deductions.forEach(d => {
                    existingDeductions[d.advance_id] = d.deduction_amount;
                });
            }
        }

        let html = `
            <div class="table-responsive">
                <table class="table table-row-bordered table-row-gray-100 align-middle">
                    <thead>
                        <tr class="fw-bold text-muted bg-light">
                            <th class="w-50px">
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" id="${mode}_select_all_advances_${mode === 'edit' ? paymentId : ''}" onchange="${mode === 'edit' ? 'edit' : ''}ToggleAllAdvances${mode === 'edit' ? '(' + paymentId + ', this.checked)' : '(this)'}">
                                </div>
                            </th>
                            <th>{{ __('payments.advance_date') }}</th>
                            <th>{{ __('payments.advance_amount') }}</th>
                            <th>{{ __('payments.remaining') }}</th>
                            <th>{{ __('payments.deduction_frequency') }}</th>
                            <th>{{ __('payments.status') }}</th>
                            <th class="text-end">{{ __('payments.to_deduct') }}</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        advances.forEach(advance => {
            const isChecked = mode === 'edit' ? existingAdvanceIds.includes(advance.id.toString()) : false;
            const deductionAmount = (mode === 'edit' && existingDeductions[advance.id]) 
                ? existingDeductions[advance.id] 
                : (advance.installment_amount || advance.remaining_amount);
            
            const checkboxClass = mode === 'edit' ? `edit-advance-checkbox-${paymentId}` : 'advance-checkbox';
            const inputClass = mode === 'edit' ? `edit-deduction-amount-input-${paymentId}` : 'deduction-amount-input';
            const disabled = mode === 'edit' ? (!isChecked ? 'disabled' : '') : 'disabled';
            
            // Add visual indicator for fully paid advances that were previously selected
            const statusBadge = advance.is_fully_paid 
                ? '<span class="badge badge-light-secondary ms-2">{{ __("payments.fully_paid") }}</span>' 
                : '';
            
            html += `
                <tr ${advance.is_fully_paid && !isChecked ? 'class="bg-light-secondary text-muted"' : ''}>
                    <td>
                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                            <input class="form-check-input ${checkboxClass}" 
                                type="checkbox" 
                                value="${advance.id}"
                                data-remaining="${advance.remaining_amount}"
                                data-installment="${advance.installment_amount || advance.remaining_amount}"
                                data-frequency="${advance.deduction_frequency}"
                                onchange="${mode === 'edit' ? 'edit' : ''}UpdateAdvanceDeductionPreview${mode === 'edit' ? '(' + paymentId + ')' : '()'}"
                                ${isChecked ? 'checked' : ''}
                                ${advance.is_fully_paid && !isChecked ? 'disabled' : ''}>
                        </div>
                    </td>
                    <td>${advance.advance_date}</td>
                    <td class="fw-bold">{{ currency_symbol() }}${formatCurrency(advance.advance_amount)}</td>
                    <td class="${advance.remaining_amount > 0 ? 'text-warning' : 'text-success'} fw-bold">
                        {{ currency_symbol() }}${formatCurrency(advance.remaining_amount)}
                    </td>
                    <td><span class="badge badge-light-info">${advance.deduction_frequency_label}</span></td>
                    <td>
                        <span class="badge badge-light-${advance.status === 'fully_paid' ? 'success' : 'primary'}">
                            ${advance.status.replace('_', ' ')}
                        </span>
                    </td>
                    <td class="text-end">
                        <div class="input-group input-group-sm w-200px ms-auto">
                            <input type="number" 
                                class="form-control form-control-sm ${inputClass}" 
                                data-advance-id="${advance.id}"
                                min="0.01" 
                                max="${advance.remaining_amount}"
                                step="0.01"
                                value="${deductionAmount}"
                                ${disabled}>
                            <span class="input-group-text">{{ currency_symbol() }}</span>
                        </div>
                    </td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-muted fs-7">
                <i class="fas fa-info-circle me-1"></i>
                {{ __('payments.advance_deduction_note_edit') }}
            </div>
        `;

        if (mode === 'edit') {
            document.getElementById('edit_advance_deductions_container_' + paymentId).innerHTML = html;
            
            // Add event listeners to enable/disable inputs based on checkbox
            document.querySelectorAll(`.edit-advance-checkbox-${paymentId}`).forEach(cb => {
                cb.addEventListener('change', function() {
                    const advanceId = this.value;
                    const input = document.querySelector(`.edit-deduction-amount-input-${paymentId}[data-advance-id="${advanceId}"]`);
                    if (input) {
                        input.disabled = !this.checked;
                    }
                    editUpdateAdvanceDeductionPreview(paymentId);
                });
            });
            
            editUpdateAdvanceDeductionPreview(paymentId);
        } else {
            $('#advance_deductions_container').html(html);
            
            // Add event listeners for create mode
            $('.advance-checkbox').on('change', function() {
                const advanceId = $(this).val();
                const input = $(`.deduction-amount-input[data-advance-id="${advanceId}"]`);
                if (input.length) {
                    input.prop('disabled', !$(this).is(':checked'));
                }
                updateAdvanceDeductionPreview();
            });
            
            updateAdvanceDeductionPreview();
        }
    }


    // Toggle all advances
    function toggleAllAdvances(checkbox) {
        $('.advance-checkbox').prop('checked', checkbox.checked);
        updateAdvanceDeductionPreview();
    }

    // Update advance deduction preview
    function updateAdvanceDeductionPreview() {
        let grossAmount = parseFloat($('#gross_amount').val()) || 0;
        let totalDeduction = 0;
        let selectedAdvances = [];
        
        $('.advance-checkbox:checked').each(function() {
            let advanceId = $(this).val();
            let remaining = parseFloat($(this).data('remaining'));
            let installmentAmount = parseFloat($(this).data('installment'));
            
            // For recurring advances, use installment amount; for one-time, use remaining
            let deductionAmount = Math.min(installmentAmount, remaining);
            totalDeduction += deductionAmount;
            
            selectedAdvances.push({
                advance_id: advanceId,
                deduction_amount: deductionAmount
            });
        });
        
        // Update preview
        let afterDeduction = Math.max(0, grossAmount - totalDeduction);
        
        $('#preview_advance_count').text($('.advance-checkbox:checked').length);
        $('#preview_advance_deduction').text(formatCurrency(totalDeduction));
        $('#preview_after_advance').text(formatCurrency(afterDeduction));
        
        // Store in hidden fields
        $('#total_advance_deduction').val(totalDeduction.toFixed(2));
        $('#advance_deductions').val(JSON.stringify(selectedAdvances));
        
        // Show preview if any advances selected
        if ($('.advance-checkbox:checked').length > 0) {
            $('#advance_deduction_preview').removeClass('d-none');
        } else {
            $('#advance_deduction_preview').addClass('d-none');
        }
    }

    // Trigger advance loading when employee or payment type changes
    $(document).ready(function() {
        $('#employee_select, #payment_type').on('change', function() {
            loadEmployeeAdvances();
        });
        
        $('#gross_amount').on('input', function() {
            updateAdvanceDeductionPreview();
        });
    });




    // Modify editEmployeePayment function to handle advance deductions
    function editEmployeePayment(uniqueId) {
        const submitButton = document.getElementById('editEmployeePaymentButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        var form = document.getElementById('editPaymentForm' + uniqueId);
        var formData = new FormData(form);

        // Handle selected taxes
        formData.delete('selected_taxes[]');
        document.querySelectorAll(`.edit-tax-checkbox-${uniqueId}:checked`).forEach(cb => {
            formData.append('selected_taxes[]', cb.value);
        });

        // Handle advance deductions
        const advanceDeductions = document.getElementById('edit_advance_deductions_' + uniqueId).value;
        if (advanceDeductions && advanceDeductions !== '[]') {
            const deductions = JSON.parse(advanceDeductions);
            formData.delete('advance_deductions');
            formData.append('advance_deductions', JSON.stringify(deductions));
            
            const totalDeduction = document.getElementById('edit_total_advance_deduction_' + uniqueId).value;
            formData.delete('total_advance_deduction');
            formData.append('total_advance_deduction', totalDeduction);
        }

        // Set up the URL dynamically
        var updateUrl = '/payment/' + uniqueId;
        
        // Submit form data asynchronously
        fetch(updateUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(data => {
            LiveBlade.toggleButtonLoading(submitButton, false);
            console.log('Response data:', data);
            
            if (data.success) {
                if (typeof toastr !== 'undefined') {
                    toastr.success(data.message);
                } else {
                    alert(data.message);
                }
                
                $('#editPaymentModal' + uniqueId).modal('hide');
                
                if (data.reload) {
                    location.reload();
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } else {
                // Handle validation errors
                if (data.errors) {
                    Object.keys(data.errors).forEach(key => {
                        const errorElement = document.getElementById(key + uniqueId);
                        if (errorElement) {
                            errorElement.innerHTML = '<span class="text-danger">' + data.errors[key][0] + '</span>';
                        }
                    });
                }
                
                if (typeof toastr !== 'undefined') {
                    toastr.error(data.message || 'Error updating payment');
                } else {
                    alert(data.message || 'Error updating payment');
                }
            }
        })
        .catch(error => {
            LiveBlade.toggleButtonLoading(submitButton, false);
            console.error('Error:', error);
            
            if (error.errors) {
                Object.keys(error.errors).forEach(key => {
                    const errorElement = document.getElementById(key + uniqueId);
                    if (errorElement) {
                        errorElement.innerHTML = '<span class="text-danger">' + error.errors[key][0] + '</span>';
                    }
                });
            }
            
        });
    }

    // Load advances when modal is opened
    $('[id^="editPaymentModal"]').on('shown.bs.modal', function() {
        const modalId = $(this).attr('id');
        const paymentId = modalId.replace('editPaymentModal', '');
        
        // Load advances if employee is already selected
        const employeeSelect = document.getElementById('edit_employee_select_' + paymentId);
        if (employeeSelect && employeeSelect.value) {
            editLoadEmployeeAdvances(paymentId);
        }
    });

</script>

<!-- JavaScript for Tax Calculation -->
<script>
    // Make sure all variable names are consistent
    let selectedPayments = []; // Define the variable if needed

    function calculateTaxPreview() {
        const grossAmount = document.getElementById('gross_amount')?.value;
        const selectedTaxes = Array.from(document.querySelectorAll('.tax-checkbox:checked')).map(cb => cb.value);
        const employeeId = document.getElementById('employee_select')?.value;
        
        if (!grossAmount || grossAmount <= 0) {
            if (typeof toastr !== 'undefined') {
                toastr.warning('Please enter gross amount first');
            } else {
                alert('Please enter gross amount first');
            }
            return;
        }
        
        if (selectedTaxes.length === 0) {
            document.getElementById('tax_preview')?.classList.add('d-none');
            return;
        }
        
        // Show loading state
        const previewDiv = document.getElementById('tax_preview');
        if (previewDiv) {
            previewDiv.classList.remove('d-none');
        }
        
        document.getElementById('preview_gross') && (document.getElementById('preview_gross').textContent = '$0.00');
        document.getElementById('preview_tax') && (document.getElementById('preview_tax').textContent = '$0.00');
        document.getElementById('preview_net') && (document.getElementById('preview_net').textContent = '$0.00');
        
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                        document.querySelector('input[name="_token"]')?.value;
        
        fetch('{{ route("payment.calculate-tax-preview") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                gross_amount: grossAmount,
                selected_taxes: selectedTaxes,
                employee_id: employeeId
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update summary cards
                document.getElementById('preview_gross') && (document.getElementById('preview_gross').textContent = formatCurrency(data.calculation.gross_amount));
                document.getElementById('preview_tax') && (document.getElementById('preview_tax').textContent = formatCurrency(data.calculation.total_tax_amount));
                document.getElementById('preview_net') && (document.getElementById('preview_net').textContent = formatCurrency(data.calculation.net_amount));
                
                // Update tax breakdown table
                const tbody = document.getElementById('tax_breakdown_body');
                if (tbody) {
                    tbody.innerHTML = '';
                    
                    if (data.calculation.tax_breakdown && data.calculation.tax_breakdown.length > 0) {
                        data.calculation.tax_breakdown.forEach(tax => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>
                                    <span class="fw-bold">${tax.label || 'Tax'}</span>
                                </td>
                                <td>
                                    <span class="badge badge-light-info">${tax.rate || '0%'}</span>
                                </td>
                                <td class="text-end fw-bold">${tax.amount || '$0.00'}</td>
                            `;
                            tbody.appendChild(row);
                        });
                    }
                }
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error(data.message || 'Failed to calculate taxes');
                } else {
                    alert('Failed to calculate taxes');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('Failed to calculate taxes');
            } else {
                alert('Failed to calculate taxes');
            }
        });
    }

    function formatCurrency(value) {
        return '$' + parseFloat(value).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    // Auto-calculate for overtime - FIXED: Wrap in DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        const paymentType = document.getElementById('payment_type');
        if (paymentType) {
            paymentType.addEventListener('change', function() {
                const overtimeFields = document.querySelectorAll('.overtime-fields');
                if (this.value === 'overtime') {
                    overtimeFields.forEach(field => field.style.display = 'block');
                } else {
                    overtimeFields.forEach(field => field.style.display = 'none');
                }
            });
        }

        // Auto-calculate gross amount for overtime
        const hoursWorked = document.getElementById('hours_worked');
        const hourlyRate = document.getElementById('hourly_rate');
        
        if (hoursWorked) {
            hoursWorked.addEventListener('input', calculateOvertimeGross);
        }
        
        if (hourlyRate) {
            hourlyRate.addEventListener('input', calculateOvertimeGross);
        }

        // Auto-preview when taxes are selected/deselected
        document.querySelectorAll('.tax-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (document.getElementById('gross_amount')?.value) {
                    calculateTaxPreview();
                }
            });
        });
    });

    function calculateOvertimeGross() {
        const hours = document.getElementById('hours_worked')?.value;
        const rate = document.getElementById('hourly_rate')?.value;
        const paymentType = document.getElementById('payment_type')?.value;
        const grossAmount = document.getElementById('gross_amount');
        
        if (hours && rate && paymentType === 'overtime' && grossAmount) {
            grossAmount.value = (parseFloat(hours) * parseFloat(rate)).toFixed(2);
        }
    }

    function editCalculateTaxPreview(paymentId) {
        const grossAmount = document.getElementById(`edit_gross_amount_${paymentId}`)?.value;
        const selectedTaxes = Array.from(document.querySelectorAll(`.edit-tax-checkbox-${paymentId}:checked`)).map(cb => cb.value);
        const employeeId = document.getElementById(`edit_employee_select_${paymentId}`)?.value;
        
        if (!grossAmount || grossAmount <= 0) {
            if (typeof toastr !== 'undefined') {
                toastr.warning('Please enter gross amount first');
            }
            return;
        }
        
        if (selectedTaxes.length === 0) {
            const previewDiv = document.getElementById(`edit_tax_preview_${paymentId}`);
            if (previewDiv) {
                previewDiv.classList.add('d-none');
            }
            return;
        }
        
        // Show loading state
        const previewDiv = document.getElementById(`edit_tax_preview_${paymentId}`);
        if (previewDiv) {
            previewDiv.classList.remove('d-none');
        }
        
        // Safely set preview values with null checks
        const grossEl = document.getElementById(`edit_preview_gross_${paymentId}`);
        const taxEl = document.getElementById(`edit_preview_tax_${paymentId}`);
        const netEl = document.getElementById(`edit_preview_net_${paymentId}`);
        
        if (grossEl) grossEl.textContent = '$0.00';
        if (taxEl) taxEl.textContent = '$0.00';
        if (netEl) netEl.textContent = '$0.00';
        
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                        document.querySelector('input[name="_token"]')?.value;
        
        fetch('{{ route("payment.calculate-tax-preview") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                gross_amount: grossAmount,
                selected_taxes: selectedTaxes,
                employee_id: employeeId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update summary cards with null checks
                if (grossEl) grossEl.textContent = formatCurrency(data.calculation.gross_amount);
                if (taxEl) taxEl.textContent = formatCurrency(data.calculation.total_tax_amount);
                if (netEl) netEl.textContent = formatCurrency(data.calculation.net_amount);
                
                // Update tax breakdown table
                const tbody = document.getElementById(`edit_tax_breakdown_body_${paymentId}`);
                if (tbody) {
                    tbody.innerHTML = '';
                    
                    if (data.calculation.tax_breakdown && data.calculation.tax_breakdown.length > 0) {
                        data.calculation.tax_breakdown.forEach(tax => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>
                                    <span class="fw-bold">${tax.label || 'Tax'}</span>
                                </td>
                                <td>
                                    <span class="badge badge-light-info">${tax.rate || '0%'}</span>
                                </td>
                                <td class="text-end fw-bold">${tax.amount || '$0.00'}</td>
                            `;
                            tbody.appendChild(row);
                        });
                    }
                }
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error(data.message || 'Failed to calculate taxes');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('Failed to calculate taxes');
            }
        });
    }

    // Auto-calculate for overtime in edit modal
    document.addEventListener('DOMContentLoaded', function() {
        @foreach($payments ?? [] as $payment)
            @if($payment->status !== 'completed')
            const paymentTypeSelect{{ $payment->id }} = document.getElementById('edit_payment_type_{{ $payment->id }}');
            if (paymentTypeSelect{{ $payment->id }}) {
                paymentTypeSelect{{ $payment->id }}.addEventListener('change', function() {
                    const overtimeFields = document.querySelectorAll('.edit-overtime-fields-{{ $payment->id }}');
                    if (this.value === 'overtime') {
                        overtimeFields.forEach(field => field.style.display = 'block');
                    } else {
                        overtimeFields.forEach(field => field.style.display = 'none');
                    }
                });
            }

            // Auto-calculate gross amount for overtime in edit
            const hoursWorked{{ $payment->id }} = document.getElementById('edit_hours_worked_{{ $payment->id }}');
            const hourlyRate{{ $payment->id }} = document.getElementById('edit_hourly_rate_{{ $payment->id }}');
            
            if (hoursWorked{{ $payment->id }}) {
                hoursWorked{{ $payment->id }}.addEventListener('input', function() {
                    calculateEditOvertimeGross({{ $payment->id }});
                });
            }
            
            if (hourlyRate{{ $payment->id }}) {
                hourlyRate{{ $payment->id }}.addEventListener('input', function() {
                    calculateEditOvertimeGross({{ $payment->id }});
                });
            }

            // Auto-preview when taxes are selected/deselected in edit - FIXED: Corrected string interpolation
            document.querySelectorAll('.edit-tax-checkbox-{{ $payment->id }}').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const grossAmount = document.getElementById(`edit_gross_amount_{{ $payment->id }}`)?.value;
                    if (grossAmount) {
                        editCalculateTaxPreview({{ $payment->id }});
                    }
                });
            });
            @endif
        @endforeach
    });

    function calculateEditOvertimeGross(paymentId) {
        const hours = document.getElementById(`edit_hours_worked_${paymentId}`)?.value;
        const rate = document.getElementById(`edit_hourly_rate_${paymentId}`)?.value;
        const paymentType = document.getElementById(`edit_payment_type_${paymentId}`)?.value;
        const grossAmount = document.getElementById(`edit_gross_amount_${paymentId}`);
        
        if (hours && rate && paymentType === 'overtime' && grossAmount) {
            grossAmount.value = (parseFloat(hours) * parseFloat(rate)).toFixed(2);
        }
    }

    function recalculateTaxes() {
        const grossAmount = parseFloat(document.getElementById('gross_amount').value) || 0;
        const selectedTaxes = document.querySelectorAll('input[name="selected_taxes[]"]:checked');
        
        let totalTax = 0;
        let netAmount = grossAmount;
        
        // If you have tax rates available client-side
        selectedTaxes.forEach(checkbox => {
            const taxRate = parseFloat(checkbox.dataset.rate) || 0;
            const taxType = checkbox.dataset.type || 'percentage';
            
            if (taxType === 'percentage') {
                totalTax += grossAmount * (taxRate / 100);
            } else {
                totalTax += taxRate; // Fixed amount
            }
        });
        
        netAmount = grossAmount - totalTax;
        
        // Update hidden fields
        document.getElementById('net_amount').value = netAmount.toFixed(2);
        document.getElementById('total_tax_amount').value = totalTax.toFixed(2);
        
        // Optionally display these values to the user
        document.getElementById('display_net_amount').textContent = netAmount.toFixed(2);
        document.getElementById('display_tax_amount').textContent = totalTax.toFixed(2);
    }
</script>






<script>
    function togglePaymentMethodFields() {
        const type = document.getElementById('paymentTypeSelect').value;
        const bankFields = document.getElementById('bankFields');
        const providerField = document.getElementById('providerField');
        
        // Show/hide bank fields
        if (type === 'bank_account' || type === 'mobile_money') {
            bankFields.style.display = 'flex';
        } else {
            bankFields.style.display = 'none';
        }
        
        // Set placeholder for provider based on type
        if (type === 'bank_account') {
            providerField.placeholder = "{{__('payments.e.g._bank_name')}}";
        } else if (type === 'digital_wallet') {
            providerField.placeholder = "{{__('payments.e.g._paypal_stripe')}}";
        } else if (type === 'mobile_money') {
            providerField.placeholder = "{{__('payments.e.g._mtn_airtel')}}";
        } else {
            providerField.placeholder = "{{__('payments._provider')}}";
        }
    }

    function submitPaymentMethodForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
        const form = document.getElementById(formId);
        const submitButton = document.getElementById(submitButtonId);

        if (!form || !submitButton) {
            console.error('Form or button not found:', formId, submitButtonId);
            return;
        }

        // Collect form data
        const formData = Object.fromEntries(new FormData(form));
        formData._method = method;
        formData.routeName = url;

        // Start loading
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Pass handling + data to reusable handler
        handleFormSubmission(formData, submitButton, discardButtonId);
    }

    function toggleEditPaymentMethodFields(id) {
        const type = document.getElementById('paymentTypeSelect' + id).value;
        const bankFields = document.getElementById('bankFields' + id);
        const providerField = document.getElementById('providerField' + id);
        
        // Show/hide bank fields
        if (type === 'bank_account' || type === 'mobile_money') {
            bankFields.style.display = 'flex';
        } else {
            bankFields.style.display = 'none';
        }
    }

    function editPaymentMethodInstance(uniqueId) {
        const submitButton = document.getElementById('editPaymentMethodButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Select the form and create FormData from it
        var form = document.getElementById('kt_modal_edit_payment_method_form' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);

        // Set up the URL dynamically
        var updateUrl = '{{ route('paymentmethod.update', ['paymentmethod' => ':id']) }}'.replace(':id', uniqueId);
        
        // Submit form data asynchronously
        handleEditResponse(data, updateUrl, uniqueId, submitButton);

    }

    function updatePaymentMethodStatus(uniqueId, selectedStatus) {
        // console.log(" ID:", uniqueId, "Selected status:", selectedStatus);
        const updateRoute = '/payment-methods-status/' + uniqueId;
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
    }
</script>




<!-- Excel functions  -->
 <script>
    // ONE REUSABLE FUNCTION FOR ALL EXPORTS
    function exportCurrentPage(config = {}) {
        // Default configuration that can be overridden
        const defaultConfig = {
            tableId: document.querySelector('table')?.id || '', // Auto-detect table if not specified
            filename: 'export',     // Base filename
            format: 'excel',        // 'excel' or 'csv'
            sheetName: 'Sheet1',    // Sheet name for Excel
            excludeColumns: [],     // Column indices to exclude (0-based)
            includeHidden: false,   // Include hidden columns/rows
            addTimestamp: true      // Add timestamp to filename
        };
        
        // Merge defaults with provided config
        const finalConfig = { ...defaultConfig, ...config };
        
        // Get table element
        const table = document.getElementById(finalConfig.tableId);
        if (!table) {
            console.error(`Table with ID "${finalConfig.tableId}" not found`);
            alert('Table not found for export');
            return;
        }
        
        // Get all rows
        const rows = Array.from(table.querySelectorAll('tr'));
        if (rows.length === 0) {
            alert('No data to export');
            return;
        }
        
        // Prepare data array
        let data = [];
        
        rows.forEach((row) => {
            // Skip hidden rows if not including hidden
            if (!finalConfig.includeHidden && row.style.display === 'none') {
                return;
            }
            
            const rowData = [];
            const cells = Array.from(row.querySelectorAll('th, td'));
            
            cells.forEach((cell, colIndex) => {
                // Skip excluded columns
                if (finalConfig.excludeColumns.includes(colIndex)) {
                    return;
                }
                
                // Skip hidden cells if not including hidden
                if (!finalConfig.includeHidden && cell.style.display === 'none') {
                    return;
                }
                
                let cellValue = '';
                
                // Handle different types of content
                if (cell.querySelector('.badge')) {
                    cellValue = cell.querySelector('.badge').textContent.trim();
                } else if (cell.querySelector('span')) {
                    const spans = Array.from(cell.querySelectorAll('span'));
                    cellValue = spans.map(span => span.textContent.trim()).join(' ');
                } else if (cell.querySelector('input[type="checkbox"]')) {
                    const checkbox = cell.querySelector('input[type="checkbox"]');
                    cellValue = checkbox.checked ? 'Yes' : 'No';
                } else if (cell.querySelector('select')) {
                    const select = cell.querySelector('select');
                    cellValue = select.options[select.selectedIndex]?.text || '';
                } else {
                    cellValue = cell.textContent.trim();
                }
                
                // Clean up the value
                cellValue = cellValue.replace(/\s+/g, ' ').trim();
                rowData.push(cellValue);
            });
            
            // Only add row if it has data
            if (rowData.length > 0) {
                data.push(rowData);
            }
        });
        
        // Generate filename with timestamp
        const timestamp = finalConfig.addTimestamp ? '_' + new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-') : '';
        const fullFilename = `${finalConfig.filename}${timestamp}.${finalConfig.format === 'excel' ? 'xlsx' : 'csv'}`;
        
        // Export based on format
        if (finalConfig.format === 'excel') {
            exportToExcelFormat(data, fullFilename, finalConfig.sheetName);
        } else {
            exportToCSVFormat(data, fullFilename);
        }
    }

    // Helper functions (keep these as they are)
    function exportToExcelFormat(data, filename, sheetName) {
        try {
            if (typeof XLSX === 'undefined') {
                console.warn('SheetJS not loaded, falling back to CSV');
                exportToCSVFormat(data, filename.replace('.xlsx', '.csv'));
                return;
            }
            
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(data);
            
            const maxWidths = [];
            data.forEach(row => {
                row.forEach((cell, colIndex) => {
                    const cellLength = cell ? cell.toString().length : 0;
                    if (!maxWidths[colIndex] || cellLength > maxWidths[colIndex]) {
                        maxWidths[colIndex] = cellLength;
                    }
                });
            });
            
            ws['!cols'] = maxWidths.map(width => ({ wch: Math.min(width + 2, 50) }));
            XLSX.utils.book_append_sheet(wb, ws, sheetName);
            XLSX.writeFile(wb, filename);
            
        } catch (error) {
            console.error('Excel export error:', error);
            alert('Error exporting to Excel. Falling back to CSV.');
            exportToCSVFormat(data, filename.replace('.xlsx', '.csv'));
        }
    }

    function exportToCSVFormat(data, filename) {
        try {
            const csvContent = data.map(row => 
                row.map(cell => {
                    if (typeof cell === 'string' && (cell.includes(',') || cell.includes('"') || cell.includes('\n'))) {
                        return '"' + cell.replace(/"/g, '""') + '"';
                    }
                    return cell;
                }).join(',')
            ).join('\n');
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            } else {
                alert('Your browser does not support automatic downloads. Please copy the data manually.');
            }
            
        } catch (error) {
            console.error('CSV export error:', error);
            alert('Error exporting to CSV. Please try again.');
        }
    }
 </script>