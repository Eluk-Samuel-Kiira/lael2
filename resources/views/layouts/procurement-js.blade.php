





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

    // Global variable to track item count
    let purchaseOrderItemCount = 0;

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Purchase order form initialized');
        
        // Set the first item ID - FIXED: Ensure it has the ID
        const firstItem = document.querySelector('.purchase-order-item');
        if (firstItem) {
            firstItem.id = 'item_0';
            purchaseOrderItemCount = 0;
            console.log('First item ID set to:', firstItem.id);
        }
        
        // Enable remove button if needed
        updateRemoveButtons();
        
        // Initialize order summary
        updateOrderSummary();
        
        // Add event listeners to existing quantity and unit cost inputs
        initializeExistingInputs();
    });

    

    // Initialize event listeners for existing inputs - FIXED
    function initializeExistingInputs() {
        console.log('Initializing existing inputs...');
        
        // Add input event listeners to quantity and unit cost fields - WITH ERROR HANDLING
        document.querySelectorAll('input[name^="items["][name$="[quantity]"]').forEach(input => {
            console.log('Found quantity input:', input.name);
            
            input.addEventListener('input', function() {
                try {
                    const matches = this.name.match(/\[(\d+)\]/);
                    if (matches && matches[1]) {
                        const itemIndex = parseInt(matches[1]);
                        console.log('Quantity input changed for item:', itemIndex);
                        calculateItemTotal(itemIndex);
                    } else {
                        console.error('Could not parse item index from:', this.name);
                        // Fallback: try to calculate for first item (index 0)
                        calculateItemTotal(0);
                    }
                } catch (error) {
                    console.error('Error in quantity input listener:', error);
                    calculateItemTotal(0); // Fallback to first item
                }
            });
        });
        
        document.querySelectorAll('input[name^="items["][name$="[unit_cost]"]').forEach(input => {
            console.log('Found unit cost input:', input.name);
            
            input.addEventListener('input', function() {
                try {
                    const matches = this.name.match(/\[(\d+)\]/);
                    if (matches && matches[1]) {
                        const itemIndex = parseInt(matches[1]);
                        console.log('Unit cost input changed for item:', itemIndex);
                        calculateItemTotal(itemIndex);
                    } else {
                        console.error('Could not parse item index from:', this.name);
                        // Fallback: try to calculate for first item (index 0)
                        calculateItemTotal(0);
                    }
                } catch (error) {
                    console.error('Error in unit cost input listener:', error);
                    calculateItemTotal(0); // Fallback to first item
                }
            });
        });
        
        // Also add event listeners to product selects
        document.querySelectorAll('select[name^="items["][name$="[product_variant_id]"]').forEach(select => {
            console.log('Found product select:', select.name);
            
            select.addEventListener('change', function() {
                try {
                    const matches = this.name.match(/\[(\d+)\]/);
                    if (matches && matches[1]) {
                        const itemIndex = parseInt(matches[1]);
                        console.log('Product select changed for item:', itemIndex);
                        updateProductDetails(this, itemIndex);
                    } else {
                        console.error('Could not parse item index from:', this.name);
                        updateProductDetails(this, 0); // Fallback to first item
                    }
                } catch (error) {
                    console.error('Error in product select listener:', error);
                    updateProductDetails(this, 0); // Fallback to first item
                }
            });
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
        
        const newItemHtml = `
            <div class="row g-4 mb-4 purchase-order-item" id="item_${purchaseOrderItemCount}">
                <div class="col-md-4">
                    <label class="form-label required">{{ __('passwords.product') }}</label>
                    <select name="items[${purchaseOrderItemCount}][product_variant_id]" class="form-select product-select" onchange="updateProductDetails(this, ${purchaseOrderItemCount})">
                        <option value=""></option>
                        @foreach($variants as $variant)
                            <option value="{{ $variant->id }}" 
                                    data-sku="{{ $variant->sku }}" 
                                    data-name="{{ $variant->name }}"
                                    data-cost-price="{{ $variant->cost_price }}"
                                    data-is-taxable="{{ $variant->is_taxable ? '1' : '0' }}">
                                {{ $variant->name }} ({{ $variant->sku }})
                            </option>
                        @endforeach
                    </select>
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
        
        // Add event listeners to the new inputs
        const newQuantityInput = document.querySelector(`input[name="items[${purchaseOrderItemCount}][quantity]"]`);
        const newUnitCostInput = document.querySelector(`input[name="items[${purchaseOrderItemCount}][unit_cost]"]`);
        
        if (newQuantityInput) {
            newQuantityInput.addEventListener('input', function() {
                calculateItemTotal(purchaseOrderItemCount);
            });
        }
        
        if (newUnitCostInput) {
            newUnitCostInput.addEventListener('input', function() {
                calculateItemTotal(purchaseOrderItemCount);
            });
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
        
        removeButtons.forEach(button => {
            if (button) {
                button.disabled = items.length <= 1;
            }
        });
    }

    // Function to update product details when variant is selected
    function updateProductDetails(selectElement, itemIndex) {
        if (!selectElement) {
            console.error('Select element not found');
            return;
        }
        
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            console.log('No product selected');
            return;
        }
        
        console.log('Product selected for item', itemIndex, ':', selectedOption.value);
        
        // Get product data from data attributes
        const costPrice = parseFloat(selectedOption.getAttribute('data-cost-price')) || 0;
        const sku = selectedOption.getAttribute('data-sku') || '';
        const productName = selectedOption.getAttribute('data-name') || '';
        
        console.log('Product details:', { costPrice, sku, productName });
        
        // Update unit cost field
        const unitCostInput = document.querySelector(`input[name="items[${itemIndex}][unit_cost]"]`);
        if (unitCostInput) {
            unitCostInput.value = costPrice;
            console.log('Updated unit cost to:', costPrice);
            
            // Calculate total immediately after updating unit cost
            calculateItemTotal(itemIndex);
        } else {
            console.error('Unit cost input not found for item:', itemIndex);
        }
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
        console.log('Calculated total for item', itemIndex, ':', quantity, '×', unitCost, '=', total.toFixed(2));
        
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
        
        // For now, set tax to 0 - we can add tax calculation later
        const taxTotal = 0;
        const grandTotal = subtotal + taxTotal;
        
        subtotalElement.textContent = subtotal.toFixed(2);
        taxTotalElement.textContent = taxTotal.toFixed(2);
        grandTotalElement.textContent = grandTotal.toFixed(2);
        
        console.log('Order summary updated - Subtotal:', subtotal.toFixed(2), 'Grand Total:', grandTotal.toFixed(2));
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
            const productSelect = item.querySelector(`select[name="items[${itemId}][product_variant_id]"]`);
            const quantityInput = item.querySelector(`input[name="items[${itemId}][quantity]"]`);
            const unitCostInput = item.querySelector(`input[name="items[${itemId}][unit_cost]"]`);
            
            if (productSelect && productSelect.value) {
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

    function sendToSupplier(orderId) {
        const selectedStatus = 'sent';
        const updateRoute = '/purchase-send/' + orderId;
        
        Swal.fire({
            title: '{{ __("passwords.send_supplier_title") }}',
            text: '{{ __("passwords.send_supplier_confirmation") }}',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#0dcaf0',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __("passwords.send_supplier") }}',
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


    // Update receiving total
    function updateReceivingTotal(orderId) {
        let total = 0;
        let totalPending = 0;
        
        const receivingInputs = document.querySelectorAll(`#receiveItemsForm${orderId} .receiving-quantity`);
        const orderedFields = document.querySelectorAll(`#receiveItemsForm${orderId} .ordered-quantity`);
        const receivedFields = document.querySelectorAll(`#receiveItemsForm${orderId} .received-quantity`);
        
        // Calculate total receiving
        receivingInputs.forEach(input => {
            total += parseInt(input.value) || 0;
        });
        
        // Calculate total pending (ordered - received)
        for (let i = 0; i < orderedFields.length; i++) {
            const ordered = parseInt(orderedFields[i].textContent) || 0;
            const received = parseInt(receivedFields[i].textContent) || 0;
            totalPending += (ordered - received);
        }
        
        document.getElementById(`receivingTotal${orderId}`).textContent = total;
        document.getElementById(`remainingAfter${orderId}`).textContent = totalPending - total;
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
                    // Reset receiving quantities to zero
                    const receivingInputs = document.querySelectorAll(`#receiveItemsForm${orderId} .receiving-quantity`);
                    receivingInputs.forEach(input => {
                        input.value = 0;
                    });
                    
                    // Update receiving total display
                    updateReceivingTotal(orderId);
                    
                    // Close the modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById(`receiveItemsModal${orderId}`));
                    if (modal) {
                        modal.hide();
                    }
                    
                    // Reload the page to show updated status and quantities
                    location.reload();
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

        // Collect form data
        const formData = Object.fromEntries(new FormData(form));
        formData._method = method;
        formData.routeName = url;

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