{{--
  pos-scripts.blade.php  —  FULL REPLACEMENT
  Replaces ALL script blocks in your POS view.
  
  Contains:
    1. Cart functions (addToCart, clearCart, etc.)
    2. Search & department filter
    3. processPayment()       ← resumedOrderId NOT cleared here
    4. Payment modal logic    ← openPaymentModal recalculates live total
                              ← processSplitPayments sends cart_updated + explicit payload
    5. Receipt generator
    6. Payment type helpers
    7. cancelPOSOrder()
--}}

{{-- pos-js.blade.php --}}
@php
    $user = auth()->user();
    $tenantId = $user?->tenant_id ?? 0;
    $isSingleShop = $tenantId ? tenant_is_single_shop($tenantId) : false;
    $locationId = $user?->location_id ?? 1;
    $currencySymbol = currency_symbol();
    $currencyCode = currency_code();
@endphp

<script>
    // ✅ Pass PHP variables to JavaScript safely
    window.userData = {
        tenantId: {{ $tenantId }},
        isSingleShop: {{ $isSingleShop ? 'true' : 'false' }},
        locationId: {{ $locationId }},
        currencySymbol: '{{ $currencySymbol }}',
        currencyCode: '{{ $currencyCode }}'
    };
    
    // ✅ Use this for the old functions that expect these variables
    var POS_CURRENCY_SYM = window.userData.currencySymbol;
    var isSingleShop = window.userData.isSingleShop;
    
    // ✅ Override tenant_is_single_shop for JavaScript
    function tenant_is_single_shop(tenantId) {
        return window.userData.isSingleShop;
    }
</script>

<script>
    // ============================================================
    // DEPARTMENT FILTER — the ONLY handler for this element. Do not
    // add a second one anywhere else in the page; two listeners on
    // the same <select> both calling window.location.href = ... is
    // what caused the previous "first selection sometimes does
    // nothing" bug — whichever listener's setup ran last could wipe
    // out the other's binding via node cloning, mid-race, right as
    // the user's first click tried to use it.
    // ============================================================
    (function() {
        function init() {
            const departmentFilter = document.getElementById('departmentFilter');
            if (!departmentFilter) {
                setTimeout(init, 100);
                return;
            }
 
            departmentFilter.addEventListener('change', function() {
                const selectedDept = this.value;
                const url = new URL(window.location.href);
 
                if (selectedDept) {
                    url.searchParams.set('department', selectedDept);
                } else {
                    url.searchParams.delete('department');
                }
 
                window.location.href = url.toString();
            });
        }
 
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>


{{-- ═══════════════════════════════════════════════
     1. CART FUNCTIONS
════════════════════════════════════════════════ --}}
<script>
function formatCurrency(value) {
    return Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

let cart = [];

function getCartTbody() {
    return document.getElementById('pos-cart-tbody');
}

// ============================================================
// GET CART ITEM KEY - Unique per item including batch and serial
// ============================================================
function getCartItemKey(variantId, departmentId, inventoryId, batchId = null, serialId = null) {
    const variantIdStr = String(variantId || '');
    
    // ✅ CHECK SERIAL FIRST - This is the most specific
    if (serialId) {
        return variantIdStr + '_serial_' + String(serialId);
    }
    
    // ✅ CHECK BATCH SECOND
    if (batchId) {
        return variantIdStr + '_batch_' + String(batchId);
    }
    
    // ✅ QUANTITY PRODUCTS - Least specific
    const deptStr = String(departmentId || '');
    const invStr = String(inventoryId || '');
    
    if (!window.userData.isSingleShop) {
        return variantIdStr + '_' + deptStr + '_' + invStr;
    } else {
        return variantIdStr;
    }
}


// ============================================================
// DEPARTMENT SELECTION HELPERS
// ============================================================

// ✅ Check if department is selected (for multi-shop)
function isDepartmentSelected() {
    @if(!$isSingleShop)
        const deptFilter = document.getElementById('departmentFilter');
        if (!deptFilter || !deptFilter.value) {
            return false;
        }
        return true;
    @else
        return true; // Single shop doesn't need department
    @endif
}

// ✅ Show department selection warning
function showDepartmentWarning() {
    if (typeof toastr !== 'undefined') {
        toastr.warning('Please select a department first');
    }
    // Highlight the department filter
    const deptFilter = document.getElementById('departmentFilter');
    if (deptFilter) {
        deptFilter.classList.add('border-danger');
        setTimeout(() => {
            deptFilter.classList.remove('border-danger');
        }, 3000);
    }
}


// ============================================================
// BATCH SELECTION FUNCTIONS
// ============================================================
let pendingBatchProduct = null;

function showBatchSelectionModal(variantId, variantName, batches, productData) {
    pendingBatchProduct = productData;
    document.getElementById('batchVariantName').textContent = variantName;
    
    const body = document.getElementById('batchSelectionBody');
    body.innerHTML = batches.map(batch => `
        <div class="batch-option card card-dashed mb-3 p-3" 
            style="cursor: pointer;"
            onclick="selectBatch(${batch.id}, ${batch.quantity_remaining}, '${batch.batch_number}')">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold">${batch.batch_number}</span>
                    <br>
                    <small class="text-muted">${batch.expiry_date ? 'Expires: ' + batch.expiry_date : 'No expiry'}</small>
                </div>
                <div>
                    <span class="badge badge-light-primary fs-6">
                        ${batch.quantity_remaining} available
                    </span>
                </div>
            </div>
        </div>
    `).join('');
    
    new bootstrap.Modal(document.getElementById('batchSelectionModal')).show();
}

function selectBatch(batchId, batchQuantity, batchNumber) {
    if (!pendingBatchProduct) return;
    
    // ✅ Create unique cart key WITH batch_id for batch products
    const itemKey = getCartItemKey(
        pendingBatchProduct.id, 
        pendingBatchProduct.department_id, 
        pendingBatchProduct.inventory_id
    ) + '_' + batchId;  // ✅ Include batch_id in key
    
    // Check if this specific batch is already in cart
    const existingItem = cart.find(i => i.cartKey === itemKey);
    if (existingItem && existingItem.quantity >= batchQuantity) {
        toastr.warning('Maximum quantity for this batch reached');
        return;
    }
    
    addToCart({
        id: pendingBatchProduct.id,
        name: pendingBatchProduct.name,
        price: pendingBatchProduct.price,
        image: pendingBatchProduct.image,
        quantity_available: batchQuantity,
        taxes: pendingBatchProduct.taxes,
        promotions: pendingBatchProduct.promotions,
        inventory_id: pendingBatchProduct.inventory_id,
        department_id: pendingBatchProduct.department_id,
        strategy: 'batch',
        is_recipe: false,
        batch_id: batchId,
        batch_number: batchNumber
    });
    
    pendingBatchProduct = null;
    bootstrap.Modal.getInstance(document.getElementById('batchSelectionModal')).hide();
}

// ============================================================
// GET CART ITEM KEY - Unique per item including batch
// ============================================================
function getCartItemKey(variantId, departmentId, inventoryId, batchId = null, serialId = null) {
    const variantIdStr = String(variantId || '');
    const deptStr = String(departmentId || '');
    const invStr = String(inventoryId || '');
    
    // ✅ CHECK SERIAL FIRST - This is the most specific
    if (serialId) {
        const serialIdStr = String(serialId);
        // ✅ Ensure we include ALL parts to make it truly unique
        return variantIdStr + '_' + deptStr + '_' + invStr + '_serial_' + serialIdStr;
    }
    
    // ✅ CHECK BATCH SECOND
    if (batchId) {
        const batchIdStr = String(batchId);
        return variantIdStr + '_' + deptStr + '_' + invStr + '_batch_' + batchIdStr;
    }
    
    // ✅ QUANTITY PRODUCTS - Least specific
    if (!window.userData.isSingleShop) {
        return variantIdStr + '_' + deptStr + '_' + invStr;
    } else {
        return variantIdStr;
    }
}


function addToCart(variant) {
    // ✅ For recipe products, always allow adding
    if (!variant.is_recipe && variant.quantity_available <= 0) {
        toastr['error']('{{ __("pagination.out_of_stock") }}');
        return;
    }
    
    // ✅ CRITICAL: If this is a serial product, we MUST have a unique cartKey
    let itemKey = variant.cartKey;
    
    // If no cartKey provided, generate one
    if (!itemKey) {
        // For serial products, generate a unique key with serial_id
        if (variant.strategy === 'serial' && variant.serial_id) {
            const variantIdStr = String(variant.id || '');
            const deptStr = String(variant.department_id || '');
            const invStr = String(variant.inventory_id || '');
            const serialIdStr = String(variant.serial_id);
            itemKey = variantIdStr + '_' + deptStr + '_' + invStr + '_serial_' + serialIdStr;
        } else {
            // For other products, use the standard key generation
            itemKey = getCartItemKey(
                variant.id, 
                variant.department_id, 
                variant.inventory_id,
                variant.batch_id || null,
                variant.serial_id || null
            );
        }
    }
    
    // console.log('🛒 addToCart - final itemKey:', itemKey);
    // console.log('🛒 addToCart - variant data:', {
    //     id: variant.id,
    //     strategy: variant.strategy,
    //     serial_id: variant.serial_id,
    //     serial_number: variant.serial_number,
    //     cartKey: variant.cartKey
    // });
    
    // ✅ Check if this item is already in cart using the unique key
    const idx = cart.findIndex(i => i.cartKey === itemKey);
    
    // ✅ For serial products, if it's already in cart, prevent adding
    if (variant.strategy === 'serial' && idx > -1) {
        toastr['warning']('This serial number is already in the cart');
        return;
    }
    
    // ✅ Include all relevant data in cart item
    const cartItem = {
        id: variant.id,
        cartKey: itemKey,
        name: variant.name,
        price: variant.price,
        image: variant.image || '/images/default-product.png',
        quantity: 1,
        quantity_available: variant.quantity_available,
        taxes: variant.taxes || [],
        promotions: variant.promotions || [],
        inventory_id: variant.inventory_id || null,
        department_id: variant.department_id || null,
        strategy: variant.strategy || 'quantity',
        is_recipe: variant.is_recipe || false,
        batch_id: variant.batch_id || null,
        batch_number: variant.batch_number || null,
        serial_id: variant.serial_id || null,
        serial_number: variant.serial_number || null
    };
    
    if (idx > -1) {
        // ✅ For serial products, this shouldn't happen (we already returned)
        // For batch or quantity, increment quantity
        if (variant.strategy === 'serial') {
            toastr['warning']('This serial number is already in the cart');
            return;
        }
        
        if (cart[idx].quantity < cart[idx].quantity_available || cart[idx].is_recipe) {
            cart[idx].quantity += 1;
            updateCartItem(idx);
            toastr['success']('{{ __("pagination.item_added") }}');
        } else {
            toastr['warning']('{{ __("pagination.max_quantity_reached") }}');
        }
    } else {
        cart.push(cartItem);
        renderCartItem(cartItem);
        toastr['success']('{{ __("pagination.item_added") }}');
    }
}



// ============================================================
// RENDER CART ITEM - Show batch number for batch products
// ============================================================
function renderCartItem(item) {
    const cartTbody = getCartTbody();
    if (!cartTbody) { console.error('[POS] #pos-cart-tbody not found'); return; }
    const lineSubtotal = item.price * item.quantity;
    const newRow = document.createElement('tr');
    newRow.setAttribute('data-item-id', item.id);
    newRow.setAttribute('data-cart-key', item.cartKey);
    newRow.setAttribute('data-inventory-id', item.inventory_id || '');
    newRow.setAttribute('data-department-id', item.department_id || '');
    newRow.setAttribute('data-strategy', item.strategy || 'quantity');
    newRow.setAttribute('data-is-recipe', item.is_recipe ? 'true' : 'false');
    newRow.setAttribute('data-batch-id', item.batch_id || '');
    newRow.setAttribute('data-serial-id', item.serial_id || '');
    
    // ✅ Build display name with batch or serial info
    let displayName = item.name;
    if (item.strategy === 'batch' && item.batch_number) {
        displayName = `${item.name} (${item.batch_number})`;
    } else if (item.strategy === 'serial' && item.serial_number) {
        // ✅ Show serial number clearly in the cart
        displayName = `${item.name}`;
        // The serial number is already in the name from selectSerial
    }
    
    newRow.innerHTML = `
        <td class="pe-0">
            <div class="d-flex align-items-center gap-3">
                <img src="${item.image}" class="w-50px h-50px rounded-3 object-fit-cover border" alt="${item.name}" />
                <div class="d-flex flex-column">
                    <span class="fw-bold text-gray-800 text-hover-primary fs-6">${displayName}</span>
                    ${item.serial_number ? `<small class="text-muted">SN: ${item.serial_number}</small>` : ''}
                    <div class="d-flex gap-1 mt-1">
                        ${item.is_recipe ? `<span class="badge badge-success fs-8">${getStrategyLabel('recipe')}</span>` : ''}
                        ${!item.is_recipe && item.strategy === 'batch' ? `<span class="badge badge-info fs-8">${getStrategyLabel('batch')}</span>` : ''}
                        ${!item.is_recipe && item.strategy === 'serial' ? `<span class="badge badge-warning fs-8">${getStrategyLabel('serial')}</span>` : ''}
                    </div>
                </div>
            </div>
        </td>
        <td class="pe-0">
            <div class="d-flex align-items-center gap-1">
                ${item.strategy === 'serial' ? `
                    <span class="fw-bold fs-5 text-center w-35px">1</span>
                ` : `
                    <button type="button" class="btn btn-icon btn-sm btn-light btn-icon-gray-500 w-30px h-30px" onclick="decreaseQuantity('${item.cartKey}')">
                        <i class="ki-duotone ki-minus fs-4"></i>
                    </button>
                    <input type="text" class="form-control border-0 text-center px-0 fs-5 fw-bold text-gray-800 w-35px quantity-input"
                           name="quantity_${item.cartKey}" value="${item.quantity}" onchange="updateQuantity('${item.cartKey}', this.value)" />
                    <button type="button" class="btn btn-icon btn-sm btn-light btn-icon-gray-500 w-30px h-30px" onclick="increaseQuantity('${item.cartKey}')">
                        <i class="ki-duotone ki-plus fs-4"></i>
                    </button>
                `}
            </div>
        </td>
        <td class="text-end">
            <div class="d-flex align-items-center justify-content-end gap-2">
                <div class="d-flex flex-column text-end">
                    <span class="fw-bold text-primary fs-4 item-total">${formatCurrency(lineSubtotal)}</span>
                    <small class="text-muted item-tax-line" style="display:none;"></small>
                    <small class="text-success item-discount-line" style="display:none;"></small>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-light-danger" onclick="removeFromCart('${item.cartKey}')">
                    <i class="bi bi-trash fs-5"></i>
                </button>
            </div>
        </td>`;
    cartTbody.appendChild(newRow);
    updateItemExtraLines(item.cartKey);
}



// ============================================================
// UPDATE HANDLE VARIANT CLICK - Fix for multiple batches and serials
// ============================================================
function handleVariantClick(el) {
    @if(!$isSingleShop)
        if (!isDepartmentSelected()) {
            showDepartmentWarning();
            return;
        }
    @endif

    const variantId = parseInt(el.dataset.variantId);
    const name = el.dataset.name;
    const price = parseFloat(el.dataset.price);
    const image = el.dataset.image;
    const taxes = JSON.parse(el.dataset.taxes || '[]');
    const promotions = JSON.parse(el.dataset.promotions || '[]');
    const strategy = el.dataset.strategy || 'quantity';
    const isRecipe = el.dataset.isRecipe === 'true';
    const batches = JSON.parse(el.dataset.batches || '[]');
    const serials = JSON.parse(el.dataset.serials || '[]');  
    
    let quantityAvailable = parseInt(el.dataset.quantityAvailable) || 0;
    let inventoryId = null;
    let departmentId = null;

    @if(!$isSingleShop)
        const selectedDept = document.getElementById('departmentFilter').value;
        const inventoryData = JSON.parse(el.dataset.inventory || '{}');
        
        if (inventoryData[selectedDept]) {
            if (strategy === 'batch') {
                // quantityAvailable already set from batches
            } else if (strategy === 'serial') {
                // quantityAvailable already set from serials
            } else {
                quantityAvailable = inventoryData[selectedDept].quantity || 0;
            }
            inventoryId = inventoryData[selectedDept].inventory_id;
            departmentId = parseInt(selectedDept);
        } else {
            const deptKeys = Object.keys(inventoryData);
            const foundKey = deptKeys.find(key => parseInt(key) === parseInt(selectedDept));
            if (foundKey !== undefined) {
                if (strategy === 'batch' || strategy === 'serial') {
                    // quantityAvailable already set from batches/serials
                } else {
                    quantityAvailable = inventoryData[foundKey].quantity || 0;
                }
                inventoryId = inventoryData[foundKey].inventory_id;
                departmentId = parseInt(selectedDept);
            } else {
                toastr.warning('This item is not available in the selected department');
                return;
            }
        }
    @else
        if (quantityAvailable === 0) {
            const qtySpan = el.querySelector('.variant-qty');
            if (qtySpan) {
                const qtyText = qtySpan.textContent.trim();
                const qtyMatch = qtyText.match(/(\d+)/);
                quantityAvailable = qtyMatch ? parseInt(qtyMatch[1]) : 0;
            }
        }
    @endif

    // ✅ FOR SERIAL PRODUCTS: Show serial selection
    if (strategy === 'serial') {
        if (!serials || serials.length === 0) {
            toastr.error('No serial numbers available for this product');
            return;
        }
        
        // ✅ Filter to ONLY available serials (not sold, not reserved)
        const availableSerials = serials.filter(s => s.status === 'available');
        if (availableSerials.length === 0) {
            toastr.error('No available serial numbers for this product');
            return;
        }
        
        // console.log('📋 Available serials for modal:', availableSerials);
        
        // ✅ Show serial selection modal with available serials
        showSerialSelectionModal(variantId, name, availableSerials, {
            id: variantId,
            name: name,
            price: price,
            image: image,
            taxes: taxes,
            promotions: promotions,
            inventory_id: inventoryId,
            department_id: departmentId,
            strategy: strategy,
            is_recipe: isRecipe
        });
        return;
    }


    // ✅ FOR BATCH PRODUCTS: Show batch selection
    if (strategy === 'batch') {
        if (!batches || batches.length === 0) {
            toastr.error('No batches available for this product');
            return;
        }
        
        const totalBatchQty = batches.reduce((sum, b) => sum + b.quantity_remaining, 0);
        if (totalBatchQty <= 0) {
            toastr.error('No batches available for this product');
            return;
        }
        
        // ✅ If only one batch, use it directly
        if (batches.length === 1) {
            const batch = batches[0];
            // ✅ Check if this specific batch is already in cart
            const itemKey = getCartItemKey(variantId, departmentId, inventoryId, batch.id);
            const existingItem = cart.find(i => i.cartKey === itemKey);
            if (existingItem && existingItem.quantity >= batch.quantity_remaining) {
                toastr.warning('Maximum quantity for this batch reached');
                return;
            }
            
            addToCart({
                id: variantId,
                name: name,
                price: price,
                image: image,
                quantity_available: batch.quantity_remaining,
                taxes: taxes,
                promotions: promotions,
                inventory_id: inventoryId,
                department_id: departmentId,
                strategy: strategy,
                is_recipe: isRecipe,
                batch_id: batch.id,
                batch_number: batch.batch_number
            });
            return;
        }
        
        // ✅ Show batch selection modal for multiple batches
        showBatchSelectionModal(variantId, name, batches, {
            id: variantId,
            name: name,
            price: price,
            image: image,
            taxes: taxes,
            promotions: promotions,
            inventory_id: inventoryId,
            department_id: departmentId,
            strategy: strategy,
            is_recipe: isRecipe
        });
        return;
    }

    // ✅ For recipe products
    if (isRecipe) {
        toastr.info('{{ __("passwords.recipe_product_info") }}');
        quantityAvailable = 9999;
    }

    // ✅ Check quantity for non-recipe products
    if (!isRecipe && quantityAvailable <= 0) {
        toastr.error('{{ __("pagination.out_of_stock") }}');
        return;
    }

    // ✅ Check if item is already in cart
    // ✅ NOTE: For non-serial items, serial_id is null, so getCartItemKey works correctly
    const itemKey = getCartItemKey(variantId, departmentId, inventoryId, null, null);
    const existingItem = cart.find(i => i.cartKey === itemKey);
    if (existingItem && existingItem.quantity >= quantityAvailable) {
        toastr.warning('{{ __("pagination.max_quantity_reached") }}');
        return;
    }

    addToCart({
        id: variantId,
        name: name,
        price: price,
        image: image,
        quantity_available: quantityAvailable,
        taxes: taxes,
        promotions: promotions,
        inventory_id: inventoryId,
        department_id: departmentId,
        strategy: strategy,
        is_recipe: isRecipe,
        batch_id: null,
        batch_number: null,
        serial_id: null,
        serial_number: null
    });
}

// ============================================================
// SERIAL SELECTION FUNCTIONS
// ============================================================
let pendingSerialProduct = null;

function showSerialSelectionModal(variantId, variantName, serials, productData) {
    pendingSerialProduct = productData;
    document.getElementById('serialVariantName').textContent = variantName;
    
    const body = document.getElementById('serialSelectionBody');
    body.innerHTML = serials.map(serial => `
        <div class="serial-option card card-dashed mb-3 p-3" 
            style="cursor: pointer; border-left: 4px solid #009ef7;"
            onclick="selectSerial(${serial.id}, '${serial.serial_number}')">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold">${serial.serial_number}</span>
                    <br>
                    <small class="text-muted">
                        ${serial.location_name !== 'N/A' ? '📍 ' + serial.location_name : ''}
                        ${serial.department_name !== 'N/A' ? ' | 🏢 ' + serial.department_name : ''}
                    </small>
                </div>
                <div>
                    <span class="badge badge-light-success fs-6">
                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                        Available
                    </span>
                </div>
            </div>
        </div>
    `).join('');
    
    new bootstrap.Modal(document.getElementById('serialSelectionModal')).show();
}

function selectSerial(serialId, serialNumber) {
    if (!pendingSerialProduct) return;
    
    // ✅ IMPORTANT: Convert to string to ensure consistent comparison
    const serialIdStr = String(serialId);
    const variantIdStr = String(pendingSerialProduct.id);
    
    // ✅ Generate UNIQUE key for this serial
    // Include ALL parts to make it truly unique
    const itemKey = variantIdStr + '_' + 
                   (pendingSerialProduct.department_id || '') + '_' + 
                   (pendingSerialProduct.inventory_id || '') + '_serial_' + 
                   serialIdStr;
    
    // console.log('🔑 Generated serial key:', itemKey);
    // console.log('📦 Serial data:', {
    //     serialId: serialIdStr,
    //     variantId: variantIdStr,
    //     department: pendingSerialProduct.department_id,
    //     inventory: pendingSerialProduct.inventory_id,
    //     serialNumber: serialNumber
    // });
    
    // Check if this serial is already in cart
    const existingItem = cart.find(i => i.cartKey === itemKey);
    if (existingItem) {
        toastr.warning('This serial number is already in the cart');
        return;
    }
    
    // ✅ Create the variant data with ALL required fields
    const variantData = {
        id: pendingSerialProduct.id,
        name: pendingSerialProduct.name + ' (SN: ' + serialNumber + ')',
        price: pendingSerialProduct.price,
        image: pendingSerialProduct.image,
        quantity_available: 1,
        taxes: pendingSerialProduct.taxes || [],
        promotions: pendingSerialProduct.promotions || [],
        inventory_id: pendingSerialProduct.inventory_id,
        department_id: pendingSerialProduct.department_id,
        strategy: 'serial',
        is_recipe: false,
        batch_id: null,
        batch_number: null,
        serial_id: serialIdStr,      // ✅ MUST be set as string
        serial_number: serialNumber,
        cartKey: itemKey              // ✅ MUST be set
    };
    
    // console.log('📤 Calling addToCart with:', variantData);
    addToCart(variantData);
    
    pendingSerialProduct = null;
    bootstrap.Modal.getInstance(document.getElementById('serialSelectionModal')).hide();
}

function getStrategyLabel(strategy) {
    const labels = {
        'quantity': '{{ __("passwords.quantity_tracking") }}',
        'batch': '{{ __("passwords.batch_tracking") }}',
        'serial': '{{ __("passwords.serial_tracking") }}',
        'recipe': '{{ __("passwords.recipe_product") }}'
    };
    return labels[strategy] || strategy;
}

function updateItemExtraLines(cartKey) {
    const item  = cart.find(i => i.cartKey === cartKey);
    const row   = document.querySelector(`tr[data-cart-key="${cartKey}"]`);
    if (!item || !row) return;
    const tax      = computeItemTax(item);
    const discount = computeItemDiscount(item);
    const taxEl    = row.querySelector('.item-tax-line');
    const discEl   = row.querySelector('.item-discount-line');
    const totalEl  = row.querySelector('.item-total');
    totalEl.textContent = formatCurrency(item.price * item.quantity);
    if (tax > 0)      { taxEl.style.display  = ''; taxEl.textContent  = `+ {{ __("pagination._tax") }} ${formatCurrency(tax)}`; }
    else              { taxEl.style.display  = 'none'; taxEl.textContent  = ''; }
    if (discount > 0) { discEl.style.display = ''; discEl.textContent = `- {{ __("pagination._disc") }} ${formatCurrency(discount)}`; }
    else              { discEl.style.display = 'none'; discEl.textContent = ''; }
    calculateCartSummary();
}

function computeItemTax(item) {
    const base = item.price * item.quantity;
    let total  = 0;
    if (!item.taxes || !item.taxes.length) return 0;
    item.taxes.forEach(t => {
        const rate = parseFloat(t.rate || 0);
        total += (t.type === 'percentage') ? base * (rate / 100) : rate * item.quantity;
    });
    return total;
}

function computeItemDiscount(item) {
    let total = 0;
    const subtotal = item.price * item.quantity;
    (item.promotions || []).forEach(promo => {
        if (promo.type === 'percentage')        total += subtotal * (promo.value / 100);
        else if (promo.type === 'fixed_amount') total += promo.value * item.quantity;
    });
    return total;
}

function updateCartItem(itemIndex) {
    const item = cart[itemIndex];
    const row  = document.querySelector(`tr[data-cart-key="${item.cartKey}"]`);
    if (!row) return;
    row.querySelector('.quantity-input').value = item.quantity;
    updateItemExtraLines(item.cartKey);
}

// ✅ Plus button - works on cart items only using cartKey
function increaseQuantity(cartKey) {
    const idx = cart.findIndex(i => i.cartKey === cartKey);
    if (idx > -1) {
        if (cart[idx].quantity < cart[idx].quantity_available) { 
            cart[idx].quantity += 1; 
            updateCartItem(idx); 
            calculateCartSummary();
        } else {
            toastr['warning']('{{ __("pagination.max_quantity_reached") }}');
        }
    } else {
        console.warn('Item not found in cart with key:', cartKey);
        toastr['error']('Item not found in cart.');
    }
}

// ✅ Minus button - works on cart items only using cartKey
function decreaseQuantity(cartKey) {
    const idx = cart.findIndex(i => i.cartKey === cartKey);
    if (idx > -1) {
        if (cart[idx].quantity > 1) { 
            cart[idx].quantity -= 1; 
            updateCartItem(idx); 
            calculateCartSummary();
        } else {
            // If quantity is 1, remove the item
            removeFromCart(cartKey);
        }
    } else {
        console.warn('Item not found in cart with key:', cartKey);
        toastr['error']('Item not found in cart.');
    }
}

function updateQuantity(cartKey, newQuantity) {
    const qty = parseInt(newQuantity);
    const idx = cart.findIndex(i => i.cartKey === cartKey);
    if (idx === -1 || isNaN(qty)) return;
    if (qty > 0 && qty <= cart[idx].quantity_available) { 
        cart[idx].quantity = qty; 
        updateCartItem(idx);
        calculateCartSummary();
    } else if (qty > cart[idx].quantity_available) { 
        toastr['warning']('{{ __("pagination.max_quantity_reached") }}'); 
        cart[idx].quantity = cart[idx].quantity_available; 
        updateCartItem(idx);
        calculateCartSummary();
    }
}

// ✅ Trash button - works on cart items only using cartKey
function removeFromCart(cartKey) {
    const idx = cart.findIndex(i => i.cartKey === cartKey);
    if (idx > -1) {
        cart.splice(idx, 1);
        const row = document.querySelector(`tr[data-cart-key="${cartKey}"]`);
        if (row) row.remove();
        calculateCartSummary();
        toastr['success']('{{ __("pagination.item_removed") }}');
    } else {
        console.warn('Item not found in cart with key:', cartKey);
        toastr['error']('Item not found in cart.');
    }
}

function clearCart() {
    cart = [];
    const tbody = getCartTbody();
    if (tbody) tbody.innerHTML = '';
    calculateCartSummary();
    toastr['success']('{{ __("pagination.cart_cleared") }}');
    window.resumedOrderId     = null;
    window.resumedOrderNumber = null;
    bargainDiscount            = 0;
}

function calculateCartSummary() {
    const subtotal   = cart.reduce((s, i) => s + i.price * i.quantity, 0);
    const tax        = cart.reduce((s, i) => s + computeItemTax(i), 0);
    const discount   = cart.reduce((s, i) => s + computeItemDiscount(i), 0);
    const grandTotal = subtotal - discount + tax;
    document.querySelector('[data-kt-pos-element="total"]').textContent       = formatCurrency(subtotal);
    document.querySelector('[data-kt-pos-element="discount"]').textContent    = `-${formatCurrency(discount)}`;
    document.querySelector('[data-kt-pos-element="tax"]').textContent         = formatCurrency(tax);
    document.querySelector('[data-kt-pos-element="grant-total"]').textContent = formatCurrency(grandTotal);
}

document.addEventListener('DOMContentLoaded', () => calculateCartSummary());
</script>

{{-- ═══════════════════════════════════════════════
     3. PROCESS PAYMENT (creates/resumes order)
     
     KEY RULE: Do NOT clear window.resumedOrderId here.
     It must survive into openPaymentModal() and
     processSplitPayments() so they can recalculate
     the live cart total and send cart_updated=true.
     It is cleared by processSplitPayments() on success
     and by clearCart() as a safety net.
════════════════════════════════════════════════ --}}
<script>

function processPayment() {
    const submitButton = document.getElementById('processBill');

    if (cart.length === 0) { toastr['warning']('{{ __("pagination.cart_empty") }}'); return; }

    // ✅ Check if department is selected (for multi-shop)
    @if(!$isSingleShop)
        if (!isDepartmentSelected()) {
            showDepartmentWarning();
            return;
        }
    @endif

    const radioExisting   = document.getElementById('cust-mode-existing');
    const radioNew        = document.getElementById('cust-mode-new');
    const custExistSelect = document.getElementById('cust-existing-select');
    const custNewInput    = document.getElementById('cust-new-input');
    let customerData = null;

    if (!radioExisting.checked && !radioNew.checked) { toastr['warning']('{{ __("pagination.please_select_customer_type") }}'); return; }
    if (radioExisting.checked) {
        if (!custExistSelect.value) { toastr['warning']('{{ __("pagination.please_select_existing_customer") }}'); return; }
        customerData = { type: 'existing', id: custExistSelect.value };
    } else {
        if (!custNewInput.value.trim()) { toastr['warning']('{{ __("pagination.please_enter_customer") }}'); return; }
        customerData = { type: 'new', name: custNewInput.value.trim() };
    }

    const cartData = {
        items: cart.map(item => {
            const itemSubtotal = item.price * item.quantity;
            const itemTaxes = (item.taxes || []).map(tax => {
                const rate = parseFloat(tax.rate || 0);
                const amt  = tax.type === 'percentage' ? itemSubtotal * (rate / 100) : rate * item.quantity;
                return { id: tax.id, name: tax.name, type: tax.type, rate: tax.rate, amount: amt };
            });
            const itemTaxTotal = itemTaxes.reduce((s, t) => s + t.amount, 0);
            let discountTotal  = 0;
            const appliedPromotions = [];
            (item.promotions || []).forEach(promo => {
                let d = 0;
                if (promo.type === 'percentage')   d = itemSubtotal * (promo.value / 100);
                if (promo.type === 'fixed_amount') d = promo.value * item.quantity;
                if (d > 0) { discountTotal += d; appliedPromotions.push({ id: promo.id, name: promo.name, type: promo.type, value: promo.value, discount: d }); }
            });
            return { 
                variant_id: item.id, 
                quantity: item.quantity, 
                price: item.price, 
                name: item.name,
                subtotal: itemSubtotal, 
                taxes: itemTaxes, 
                tax_total: itemTaxTotal,
                discount: discountTotal, 
                promotions: appliedPromotions,
                total: itemSubtotal - discountTotal + itemTaxTotal,
                inventory_id: item.inventory_id || null,
                department_id: item.department_id || null,
                batch_id: item.batch_id || null,     
                batch_number: item.batch_number || null, 
                serial_id: item.serial_id || null,
                serial_number: item.serial_number || null
            };
        }),
        customer: customerData,
        subtotal: cart.reduce((s, i) => s + i.price * i.quantity, 0),
        discount: cart.reduce((s, i) => s + computeItemDiscount(i), 0),
        tax:      cart.reduce((s, i) => s + computeItemTax(i), 0),
        total:    0,
    };
    cartData.total = cartData.subtotal - cartData.discount + cartData.tax;

    const formData = new FormData();
    formData.append('cart_data', JSON.stringify(cartData));
    if (window.resumedOrderId) {
        formData.append('resumed_order_id', window.resumedOrderId);
    }

    LiveBlade.toggleButtonLoading(submitButton, true);

    fetch('{{ route("orders.process-payment") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: formData,
    })
    .then(r => { if (!r.ok) { toastr['error']('{{ __("pagination.network_error") }}'); throw new Error('Not ok'); } return r.json(); })
    .then(data => {
        LiveBlade.toggleButtonLoading(submitButton, false);
        if (data.success) {
            toastr['success'](data.message);
            const enrichedCart       = JSON.parse(formData.get('cart_data'));
            enrichedCart.order_number = data.order_number;
            enrichedCart.customerName = data.customerName;
            enrichedCart.order_id     = data.order_id;

            if (typeof window.openPaymentModal === 'function') {
                window.openPaymentModal(enrichedCart);
            } else {
                console.error('openPaymentModal not found');
            }
        } else {
            toastr['error'](data.message || '{{ __("pagination.order_failed") }}');
        }
    })
    .catch(err => {
        LiveBlade.toggleButtonLoading(submitButton, false);
        console.error('Payment error:', err);
        toastr['error']('{{ __("pagination.payment_error") }}');
    });
}
</script>

<script>
function generateInvoice() {
    const submitButton = document.getElementById('generateInvoiceBtn');

    if (cart.length === 0) { toastr['warning']('{{ __("pagination.cart_empty") }}'); return; }

    // ✅ Check if department is selected (for multi-shop)
    @if(!$isSingleShop)
        if (!isDepartmentSelected()) {
            showDepartmentWarning();
            return;
        }
    @endif

    const radioExisting   = document.getElementById('cust-mode-existing');
    const radioNew        = document.getElementById('cust-mode-new');
    const custExistSelect = document.getElementById('cust-existing-select');
    const custNewInput    = document.getElementById('cust-new-input');
    let customerData = null;

    if (!radioExisting.checked && !radioNew.checked) { toastr['warning']('{{ __("pagination.please_select_customer_type") }}'); return; }
    if (radioExisting.checked) {
        if (!custExistSelect.value) { toastr['warning']('{{ __("pagination.please_select_existing_customer") }}'); return; }
        customerData = { type: 'existing', id: custExistSelect.value };
    } else {
        if (!custNewInput.value.trim()) { toastr['warning']('{{ __("pagination.please_enter_customer") }}'); return; }
        customerData = { type: 'new', name: custNewInput.value.trim() };
    }

    const cartData = {
        items: cart.map(item => {
            const itemSubtotal = item.price * item.quantity;
            const itemTaxes = (item.taxes || []).map(tax => {
                const rate = parseFloat(tax.rate || 0);
                const amt  = tax.type === 'percentage' ? itemSubtotal * (rate / 100) : rate * item.quantity;
                return { id: tax.id, name: tax.name, type: tax.type, rate: tax.rate, amount: amt };
            });
            const itemTaxTotal = itemTaxes.reduce((s, t) => s + t.amount, 0);
            let discountTotal  = 0;
            const appliedPromotions = [];
            (item.promotions || []).forEach(promo => {
                let d = 0;
                if (promo.type === 'percentage')   d = itemSubtotal * (promo.value / 100);
                if (promo.type === 'fixed_amount') d = promo.value * item.quantity;
                if (d > 0) { discountTotal += d; appliedPromotions.push({ id: promo.id, name: promo.name, type: promo.type, value: promo.value, discount: d }); }
            });
            return { 
                variant_id: item.id, 
                quantity: item.quantity, 
                price: item.price, 
                name: item.name,
                subtotal: itemSubtotal, 
                taxes: itemTaxes, 
                tax_total: itemTaxTotal,
                discount: discountTotal, 
                promotions: appliedPromotions,
                total: itemSubtotal - discountTotal + itemTaxTotal,
                inventory_id: item.inventory_id || null,
                department_id: item.department_id || null,
                batch_id: item.batch_id || null,     
                batch_number: item.batch_number || null,
            };
        }),
        customer: customerData,
        subtotal: cart.reduce((s, i) => s + i.price * i.quantity, 0),
        discount: cart.reduce((s, i) => s + computeItemDiscount(i), 0),
        tax:      cart.reduce((s, i) => s + computeItemTax(i), 0),
        total:    0,
    };
    cartData.total = cartData.subtotal - cartData.discount + cartData.tax;

    const formData = new FormData();
    formData.append('cart_data', JSON.stringify(cartData));

    LiveBlade.toggleButtonLoading(submitButton, true);

    fetch('{{ route("orders.generate-invoice") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: formData,
    })
    .then(r => { if (!r.ok) { toastr['error']('{{ __("pagination.network_error") }}'); throw new Error('Not ok'); } return r.json(); })
    .then(data => {
        LiveBlade.toggleButtonLoading(submitButton, false);
        if (data.success) {
            toastr['success'](data.message);
            Swal.fire({
                icon: 'success',
                title: '{{ __("pagination.invoice_generated") }}',
                html: '{{ __("pagination.invoice_number") }}: <strong>' + data.invoice_number + '</strong>',
                confirmButtonText: 'OK',
            });
            if (typeof clearCart === 'function') clearCart();
        } else {
            toastr['error'](data.message || '{{ __("pagination.invoice_generation_failed") }}');
        }
    })
    .catch(err => {
        LiveBlade.toggleButtonLoading(submitButton, false);
        console.error('Invoice error:', err);
        toastr['error']('{{ __("pagination.invoice_generation_failed") }}');
    });
}
</script>


{{-- ═══════════════════════════════════════════════
     4. CURRENCY + RECEIPT GENERATOR + PAYMENT MODAL
════════════════════════════════════════════════ --}}
<script>
var POS_CURRENCY_SYM = '{{ currency_symbol() }}';

function posFmt(n) {
    return POS_CURRENCY_SYM + parseFloat(n || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// ── Receipt ───────────────────────────────────────────────
window.generateMultiPaymentReceipt = function (order) {
    var g  = id => document.getElementById(id);
    var now = new Date(), p2 = n => String(n).padStart(2,'0');
    var date = p2(now.getDate())+'/'+p2(now.getMonth()+1)+'/'+now.getFullYear();
    var time = p2(now.getHours())+':'+p2(now.getMinutes())+':'+p2(now.getSeconds());
    var orderNo = order.order_number || order.ref || order.id || Math.floor(Math.random()*900000+100000);

    g('rcpt-order-no').textContent = '#' + orderNo;
    g('rcpt-date').textContent     = date;
    g('rcpt-time').textContent     = time;

    var custName = order.customer_name || (order.customer && order.customer.name) || 'GUEST';
    g('rcpt-customer-banner').textContent = '👤 ' + String(custName).toUpperCase();
    g('rcpt-order-type').textContent = String(order.order_type || order.type || 'SALE').toUpperCase();

    if (order.table) { g('rcpt-table-row').classList.remove('d-none'); g('rcpt-table').textContent = order.table; }
    else             { g('rcpt-table-row').classList.add('d-none'); }

    var items = order.items || order.cart_items || [], itemCount = 0;
    var itemBody = g('rcpt-items-body');
    if (items.length) {
        itemBody.innerHTML = items.map(item => {
            var qty   = parseInt(item.quantity || item.qty || 1);
            var total = parseFloat(item.total || item.subtotal || ((item.price || item.unit_price || 0) * qty));
            itemCount += qty;
            return '<tr><td class="rcpt-item-qty">'+qty+'x</td><td class="rcpt-item-name">'+(item.name||item.item_name||'Item')+'</td><td class="rcpt-item-price">'+posFmt(total)+'</td></tr>'+
                   (item.note ? '<tr><td></td><td colspan="2" class="rcpt-item-sub">↳ '+item.note+'</td></tr>' : '');
        }).join('');
    } else {
        itemBody.innerHTML = '<tr><td colspan="3" style="color:#999;font-size:11px;text-align:center;">—</td></tr>';
    }
    g('rcpt-item-count').textContent = itemCount;

    var subtotal = parseFloat(order.subtotal||0), discount = parseFloat(order.discount||0);
    var tax = parseFloat(order.tax||0), grandTotal = parseFloat(order.total||0);
    g('rcpt-subtotal').textContent    = posFmt(subtotal);
    g('rcpt-grand-total').textContent = posFmt(grandTotal);
    if (discount > 0) { g('rcpt-discount-row').classList.remove('d-none'); g('rcpt-discount').textContent = '-'+posFmt(discount); }
    else              { g('rcpt-discount-row').classList.add('d-none'); }
    if (tax > 0)      { g('rcpt-tax-row').classList.remove('d-none');      g('rcpt-tax').textContent = posFmt(tax); }
    else              { g('rcpt-tax-row').classList.add('d-none'); }

    var payments = order.payments || [], payBody = g('rcpt-payments-body');
    var totalChange = parseFloat(order.total_change || 0);
    if (payments.length) {
        payBody.innerHTML = payments.map(p => {
            var label = (p.method_name||p.type||'Payment').toUpperCase();
            var acct  = p.account_number ? ' (****'+String(p.account_number).slice(-4)+')' : '';
            var rows  = '<tr><td class="rcpt-pay-label">'+label+acct+'</td><td class="rcpt-pay-value">'+posFmt(p.tendered||p.amount)+'</td></tr>';
            if (parseFloat(p.change||0) > 0.005) rows += '<tr><td class="rcpt-pay-label" style="padding-left:12px;color:#888;">↳ Applied</td><td class="rcpt-pay-value" style="color:#888;">'+posFmt(p.amount)+'</td></tr>';
            if (p.transaction_reference) rows += '<tr><td colspan="2" style="font-size:10px;color:#888;padding-left:12px;">Ref: '+p.transaction_reference+'</td></tr>';
            return rows;
        }).join('');
    } else {
        payBody.innerHTML = '<tr><td class="rcpt-pay-label">{{ strtoupper(__("pagination.paid")) }}</td><td class="rcpt-pay-value">'+posFmt(grandTotal)+'</td></tr>';
    }

    var changeBox = g('rcpt-change-box');
    if (totalChange > 0.005) { changeBox.classList.remove('d-none'); g('rcpt-change-value').textContent = posFmt(totalChange); }
    else                     { changeBox.classList.add('d-none'); }

    var barcodeVal = String(orderNo).replace(/\D/g,'').padEnd(8,'0').slice(0,12);
    g('rcpt-barcode').textContent = g('rcpt-barcode-num').textContent = barcodeVal;

    var receiptEl = document.getElementById('receiptModal');
    if (!receiptEl) { console.error('[Receipt] #receiptModal not found'); return; }
    bootstrap.Modal.getOrCreateInstance(receiptEl).show();
};

document.addEventListener('click', e => { if (e.target.closest('#rcpt-print-btn')) window.print(); });

// ── Payment modal IIFE ────────────────────────────────────
(function () {

    @if(isset($active_payment_methods))
        window.activePaymentMethods = @json($globalPaymentMethods ?? []);
    @endif

    var TYPES_WITH_REF = ['card','bank_account','mobile_money','digital_wallet','check','credit'];
    var CASH_TYPES     = ['cash'];
    var splitPayments  = [];
    var currentOrder   = null;
    var bargainDiscount = 0;

    var g  = id  => document.getElementById(id);
    var qs = sel => document.querySelector(sel);

    function getRemainingRaw() {
        var el = g('pm-remaining');
        return el ? parseFloat(el.textContent.replace(/[^0-9.-]+/g,'')) || 0 : 0;
    }
    function updateRemainingHint(type) {
        var hint = g('pm-remaining-hint-'+type);
        if (hint) hint.textContent = '{{ __("pagination.remaining") }}: ' + posFmt(getRemainingRaw());
    }
    function buildQuickAmounts(type) {
        var container = g('pm-quick-'+type); if (!container) return;
        var remaining = getRemainingRaw();
        if (remaining <= 0) { container.innerHTML = '<span class="text-success fw-semibold fs-7"><i class="ki-duotone ki-check-circle fs-4 me-1 text-success"><span class="path1"></span><span class="path2"></span></i>{{ __("pagination.fully_paid") }}</span>'; return; }
        var presets = [parseFloat(remaining.toFixed(2))];
        var rounds  = [1,2,5,10,20,50,100,200,500,1000,2000,5000,10000];
        for (var i = 0; i < rounds.length && presets.length < 7; i++) {
            var r = Math.ceil(remaining/rounds[i])*rounds[i];
            if (r > remaining && r <= remaining*5 && presets.indexOf(r)===-1) presets.push(r);
        }
        presets.sort((a,b) => a-b);
        container.innerHTML = presets.map(v => {
            var isExact = Math.abs(v-remaining) < 0.005;
            return '<button type="button" class="pm-quick-btn'+(isExact?' pm-exact':'')+'" data-payment-type="'+type+'" data-quick-amount="'+v+'">'+(isExact?'<span style="font-size:.7rem">✓ EXACT</span><br>':'')+posFmt(v)+'</button>';
        }).join('');
    }
    function updateCashCalc(type) {
        var amountEl = g('pm-amount-'+type), calcWrap = g('pm-cash-calc-'+type);
        if (!amountEl || !calcWrap) return;
        var isCash = CASH_TYPES.indexOf(type) !== -1, tendered = parseFloat(amountEl.value)||0, remaining = getRemainingRaw();
        if (!isCash || tendered <= 0) { calcWrap.classList.add('d-none'); return; }
        calcWrap.classList.remove('d-none');
        var change = Math.max(0,tendered-remaining), isUnderpaid = tendered < remaining-0.005;
        var banner = g('pm-change-banner-'+type), tenderedEl = g('pm-tendered-'+type), changeEl = g('pm-change-'+type);
        if (tenderedEl) tenderedEl.textContent = posFmt(tendered);
        if (banner)     banner.classList.toggle('pm-underpaid', isUnderpaid);
        if (changeEl)   changeEl.textContent   = isUnderpaid ? 'Short '+posFmt(remaining-tendered) : posFmt(change);
    }
    function validateBtn(type) {
        var account = g('pm-account-'+type), amount = g('pm-amount-'+type), btn = g('pm-add-btn-'+type);
        if (!account||!amount||!btn) return;
        var tendered = parseFloat(amount.value)||0, remaining = getRemainingRaw(), isCash = CASH_TYPES.indexOf(type)!==-1;
        var ok = account.value!=='' && tendered>0 && (isCash ? remaining>0 : tendered<=remaining+0.005);
        btn.disabled = !ok; btn.classList.toggle('btn-primary',ok); btn.classList.toggle('btn-secondary',!ok);
    }
    function toggleRef(type) {
        var row = g('pm-ref-row-'+type); if (!row) return;
        var show = TYPES_WITH_REF.indexOf(type)!==-1;
        row.classList.toggle('d-none',!show);
        if (!show) { var inp = g('pm-ref-'+type); if (inp) inp.value=''; }
    }
    function resetTab(type) {
        [g('pm-account-'+type),g('pm-amount-'+type),g('pm-ref-'+type)].forEach(el => { if (el) el.value=''; });
        var calc = g('pm-cash-calc-'+type); if (calc) calc.classList.add('d-none');
        validateBtn(type); buildQuickAmounts(type); updateRemainingHint(type);
    }

    function updateBargainUI() {
        var input      = g('pm-bargain-input');
        var applyBtn   = g('pm-bargain-apply-btn');
        var removeBtn  = g('pm-bargain-remove-btn');
        var appliedRow = g('pm-bargain-applied-row');
        var appliedTxt = g('pm-bargain-applied-text');
        var help       = g('pm-bargain-help');
        var locked     = splitPayments.length > 0; // can't change discount once payments exist

        if (input)    input.disabled    = locked || bargainDiscount > 0;
        if (applyBtn) applyBtn.disabled = locked || bargainDiscount > 0;

        if (bargainDiscount > 0) {
            appliedRow.classList.remove('d-none');
            appliedTxt.textContent = '{{ __("pagination.discount_applied") }}: ' + posFmt(bargainDiscount);
            if (removeBtn) removeBtn.classList.toggle('d-none', locked);
        } else {
            appliedRow.classList.add('d-none');
        }

        if (help) {
            help.textContent = locked
                ? '{{ __("pagination.remove_payments_to_change_discount") }}'
                : '{{ __("pagination.negotiated_discount_help") }}';
        }
    }

    function applyBargainDiscount() {
        var input = g('pm-bargain-input');
        if (!input || !currentOrder) return;

        var value = parseFloat(input.value);
        if (!value || value <= 0) { toastr.warning('{{ __("pagination.please_enter_valid_amount") }}'); return; }

        var payableBeforeDiscount = currentOrder.original_total ?? currentOrder.total;
        if (value >= payableBeforeDiscount) { toastr.warning('{{ __("pagination.discount_exceeds_total") }}'); return; }

        if (!currentOrder.original_total) currentOrder.original_total = currentOrder.total;

        bargainDiscount    = parseFloat(value.toFixed(2));
        currentOrder.total = parseFloat((currentOrder.original_total - bargainDiscount).toFixed(2));

        g('pm-order-total').textContent = posFmt(currentOrder.total);
        updateSummary();
        updateBargainUI();
        toastr.success('{{ __("pagination.discount_applied_successfully") }}');
    }

    function removeBargainDiscount() {
        if (!currentOrder) return;
        currentOrder.total = currentOrder.original_total ?? currentOrder.total;
        bargainDiscount = 0;
        var input = g('pm-bargain-input'); if (input) input.value = '';
        g('pm-order-total').textContent = posFmt(currentOrder.total);
        updateSummary();
        updateBargainUI();
    }
    function renderTable() {
        var tbody = g('pm-splits-body'); if (!tbody) return;
        if (!splitPayments.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-muted"><i class="ki-duotone ki-wallet fs-3x mb-3 d-block opacity-20"><span class="path1"></span><span class="path2"></span></i><div class="fs-6 fw-semibold">{{ __("pagination.no_payments_added") }}</div></td></tr>';
            return;
        }
        tbody.innerHTML = splitPayments.map((p,i) =>
            '<tr><td class="ps-6"><div class="d-flex align-items-center gap-3"><span class="symbol symbol-35px symbol-circle"><span class="symbol-label bg-light-primary"><i class="ki-duotone '+getPaymentTypeIcon(p.type)+' fs-3 text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></span></span>'+
            '<div><div class="fw-bold text-gray-800">'+formatPaymentType(p.type)+'</div><small class="text-muted">'+p.method_name+'</small></div></div></td>'+
            '<td><span class="badge badge-light-primary fs-8">'+(p.account_number||'N/A')+'</span>'+(p.transaction_ref?'<small class="d-block text-muted mt-1">Ref: '+p.transaction_ref+'</small>':'')+'</td>'+
            '<td class="text-end fw-bold text-gray-600">'+posFmt(p.tendered)+'</td>'+
            '<td class="text-end fw-bolder fs-5 text-gray-900">'+posFmt(p.amount)+'</td>'+
            '<td class="text-end">'+(p.change>0.005?'<span class="badge badge-light-success fw-bold fs-7">'+posFmt(p.change)+'</span>':'<span class="text-muted fs-7">—</span>')+'</td>'+
            '<td class="text-end pe-6"><button type="button" class="btn btn-sm btn-icon btn-light-danger pm-remove-btn" data-index="'+i+'"><i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i></button></td></tr>'
        ).join('');
        var count = g('pm-splits-count');
        if (count) count.textContent = splitPayments.length + ' {{ __("pagination.payments") }}';
    }
    function updateSummary() {
        if (!currentOrder) return;
        var totalApplied  = splitPayments.reduce((s,p) => s+p.amount,  0);
        var totalTendered = splitPayments.reduce((s,p) => s+p.tendered,0);
        var totalChange   = splitPayments.reduce((s,p) => s+p.change,  0);
        var remaining     = Math.max(0, currentOrder.total - totalApplied);
        g('pm-paid-amount').textContent = posFmt(totalApplied);
        g('pm-remaining').textContent   = posFmt(remaining);
        var wrap = g('pm-remaining-wrap'), remEl = g('pm-remaining');
        if (wrap)  { wrap.classList.toggle('bg-light-danger', remaining>0.005);  wrap.classList.toggle('bg-light-success', remaining<=0.005); }
        if (remEl) { remEl.classList.toggle('text-danger',    remaining>0.005);  remEl.classList.toggle('text-success',    remaining<=0.005); }
        var totTen=g('pm-total-tendered'),totApp=g('pm-splits-total'),totChg=g('pm-total-change');
        if (totTen) totTen.textContent = totalTendered>0 ? posFmt(totalTendered) : '—';
        if (totApp) totApp.textContent = posFmt(totalApplied);
        if (totChg) totChg.textContent = totalChange>0.005 ? posFmt(totalChange) : '—';
        var pb = g('pm-process-btn');
        if (pb) pb.disabled = !(splitPayments.length>0 && remaining<=0.005);
        var activePane = qs('.tab-pane.active[data-payment-type]');
        if (activePane) { var t=activePane.dataset.paymentType; validateBtn(t); buildQuickAmounts(t); updateRemainingHint(t); }
        updateBargainUI();
    }
    function addPayment(type) {
        var accountEl=g('pm-account-'+type),amountEl=g('pm-amount-'+type),refEl=g('pm-ref-'+type);
        if (!accountEl||!amountEl) return;
        var accountId=accountEl.value, tendered=parseFloat(amountEl.value), ref=refEl?refEl.value.trim():'', remaining=getRemainingRaw(), isCash=CASH_TYPES.indexOf(type)!==-1;
        if (!accountId)                         { toastr.warning('{{ __("pagination.please_select_account") }}');      return; }
        if (!tendered||tendered<=0)             { toastr.warning('{{ __("pagination.please_enter_valid_amount") }}'); return; }
        if (!isCash&&tendered>remaining+0.005)  { toastr.warning('{{ __("pagination.amount_exceeds_remaining") }}');  return; }
        if (remaining<=0)                       { toastr.warning('{{ __("pagination.order_already_paid") }}');         return; }
        var applied=isCash?Math.min(tendered,remaining):tendered, change=isCash?Math.max(0,tendered-remaining):0;
        var opt=accountEl.options[accountEl.selectedIndex], accountData={};
        try { accountData=JSON.parse(opt.dataset.account||'{}'); } catch(e) {}
        splitPayments.push({ id:Date.now()+Math.random(), type, method_id:accountId, method_name:accountData.name||'Unknown',
            account_number:accountData.account_number||'', tendered, amount:applied, change, transaction_ref:ref, account_data:accountData });
        renderTable(); updateSummary(); resetTab(type);
        if (change>0.005) toastr.info('<strong>{{ __("pagination.change_due") }}: '+posFmt(change)+'</strong>','{{ __("pagination.give_change_to_customer") }}',{timeOut:6000});
        if (getRemainingRaw()<=0.005) toastr.success('{{ __("pagination.payment_complete") }}');
    }
    function removePayment(index) { splitPayments.splice(index,1); renderTable(); updateSummary(); }

    // ── openPaymentModal ──────────────────────────────────────
    window.openPaymentModal = function (cartData) {

        // ── RESUME: recalculate total from LIVE cart ──────────
        // cartData came from the server with the OLD order total.
        // If items changed, live cart total is different.
        // Recalculate so modal shows correct amount and
        // processSplitPayments() client-check passes.
        if (window.resumedOrderId && typeof cart !== 'undefined' && cart.length > 0) {
            var liveSubtotal = cart.reduce((s,i) => s + i.price*i.quantity, 0);
            var liveDiscount = cart.reduce((s,i) => s + computeItemDiscount(i), 0);
            var liveTax      = cart.reduce((s,i) => s + computeItemTax(i), 0);
            cartData.subtotal = liveSubtotal;
            cartData.discount = liveDiscount;
            cartData.tax      = liveTax;
            cartData.total    = parseFloat((liveSubtotal - liveDiscount + liveTax).toFixed(2));
            console.log('[Resume] live total:', cartData.total);
        }

        currentOrder = cartData; window.currentOrder = cartData; splitPayments = [];

        bargainDiscount = 0;
        currentOrder.original_total = cartData.total; // baseline before any discount
        var bargainInput = g('pm-bargain-input'); if (bargainInput) bargainInput.value = '';

        g('pm-order-total').textContent = posFmt(cartData.total);
        g('pm-paid-amount').textContent = posFmt(0);
        g('pm-remaining').textContent   = posFmt(cartData.total);

        var refEl = g('pm-order-ref');
        if (refEl) refEl.textContent = cartData.order_number ? '#'+cartData.order_number : (cartData.ref ? '#'+cartData.ref : '—');

        document.querySelectorAll('.pm-account-select').forEach(s => s.value='');
        document.querySelectorAll('.pm-amount-input').forEach(i => i.value='');
        document.querySelectorAll('.pm-ref-input').forEach(i => i.value='');
        document.querySelectorAll('.pm-add-btn').forEach(b => { b.disabled=true; b.classList.remove('btn-primary'); b.classList.add('btn-secondary'); });
        document.querySelectorAll('[id^="pm-cash-calc-"]').forEach(el => el.classList.add('d-none'));

        var pb=g('pm-process-btn'); if (pb) pb.disabled=true;
        var wrap=g('pm-remaining-wrap'),remEl=g('pm-remaining');
        if (wrap)  { wrap.classList.add('bg-light-danger');  wrap.classList.remove('bg-light-success'); }
        if (remEl) { remEl.classList.add('text-danger');     remEl.classList.remove('text-success'); }
        renderTable();

        setTimeout(function () {
            const cashTabButton = document.querySelector('#pm-tab-cash');
            if (cashTabButton) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Tab) { new bootstrap.Tab(cashTabButton).show(); }
                else { cashTabButton.classList.add('active'); const cp = document.getElementById('pm-pane-cash'); if (cp) cp.classList.add('show','active'); }
                setTimeout(function () { var ai=g('pm-amount-cash'); if (ai) { ai.dispatchEvent(new Event('input',{bubbles:true})); ai.focus(); ai.select(); } }, 300);
            }
            var ap = qs('.tab-pane.active[data-payment-type]');
            if (ap) { var t=ap.dataset.paymentType; toggleRef(t); buildQuickAmounts(t); updateRemainingHint(t); }
        }, 150);

        bootstrap.Modal.getOrCreateInstance(g('paymentModal')).show();
    };

    // ── processSplitPayments ──────────────────────────────────
    window.processSplitPayments = function () {
        if (!currentOrder)         { toastr.error('{{ __("pagination.no_order_found") }}');      return; }
        if (!splitPayments.length) { toastr.warning('{{ __("pagination.no_payments_added") }}'); return; }

        var totalApplied = splitPayments.reduce((s,p) => s+p.amount, 0);
        if (Math.abs(currentOrder.total - totalApplied) > 0.01) {
            toastr.warning('{{ __("pagination.payment_total_mismatch") }}');
            return;
        }

        var btn = g('pm-process-btn');
        btn.setAttribute('data-kt-indicator','on'); btn.disabled=true;

        // ── Build cart snapshot for resumed orders ────────────
        var isResumed    = !! window.resumedOrderId;
        var cartSnapshot = null;

        if (isResumed && typeof cart !== 'undefined' && cart.length > 0) {
            cartSnapshot = {
                items: cart.map(item => {
                    var itemSubtotal = item.price * item.quantity;
                    var itemTaxes = (item.taxes||[]).map(tax => {
                        var rate = parseFloat(tax.rate||0);
                        var amt  = tax.type==='percentage' ? itemSubtotal*(rate/100) : rate*item.quantity;
                        return { id:tax.id, name:tax.name, type:tax.type, rate:tax.rate, amount:amt };
                    });
                    var taxTotal  = itemTaxes.reduce((s,t) => s+t.amount, 0);
                    var discTotal = 0; var promos = [];
                    (item.promotions||[]).forEach(promo => {
                        var d = 0;
                        if (promo.type==='percentage')   d = itemSubtotal*(promo.value/100);
                        if (promo.type==='fixed_amount') d = promo.value*item.quantity;
                        if (d>0) { discTotal+=d; promos.push({id:promo.id,name:promo.name,type:promo.type,value:promo.value,discount:d}); }
                    });
                    return { 
                        variant_id: item.id, 
                        name: item.name, 
                        price: item.price, 
                        quantity: item.quantity,
                        subtotal: itemSubtotal, 
                        taxes: itemTaxes, 
                        tax_total: taxTotal,
                        discount: discTotal, 
                        promotions: promos,
                        total: parseFloat((itemSubtotal-discTotal+taxTotal).toFixed(2)),
                        // ✅ Include inventory_id and department_id
                        inventory_id: item.inventory_id || null,
                        department_id: item.department_id || null
                    };
                })
            };
        }

        // ── Explicit payload ────────────────────────────────────
        var payload = {
            order_id:       currentOrder.order_id || currentOrder.id,
            total_tendered: splitPayments.reduce((s,p) => s+p.tendered, 0),
            total_change:   splitPayments.reduce((s,p) => s+p.change,   0),
            cart_updated:   isResumed,
            updated_cart:   cartSnapshot,
            bargain_discount: bargainDiscount || 0,
            payments: splitPayments.map(p => ({
                payment_method_id:     p.method_id,
                amount:                p.amount,
                tendered:              p.tendered,
                change:                p.change,
                transaction_reference: p.transaction_ref || null,
                type:                  p.type,
                method_name:           p.method_name,
                account_number:        p.account_number,
            })),
        };

        // console.log('[POS] processSplitPayments payload:', JSON.stringify({
        //     order_id: payload.order_id, order_total: currentOrder.total,
        //     total_applied: totalApplied, cart_updated: payload.cart_updated, payment_count: payload.payments.length,
        // }));

        fetch('/orders/process-split-payment', {
            method:'POST',
            headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify(payload),
        })
        .then(r => { if (!r.ok) return r.json().then(body => { throw new Error(body.message||'{{ __("pagination.payment_error") }}'); }); return r.json(); })
        .then(data => {
            btn.removeAttribute('data-kt-indicator'); btn.disabled=false;
            if (data.success) {
                toastr.success(data.message||'{{ __("pagination.payment_completed") }}');

                // ── Clear resume state now that payment is done ───
                window.resumedOrderId     = null;
                window.resumedOrderNumber = null;
                bargainDiscount            = 0;

                var payModalEl = g('paymentModal');
                payModalEl.addEventListener('hidden.bs.modal', function onHidden() {
                    payModalEl.removeEventListener('hidden.bs.modal', onHidden);
                    window.generateMultiPaymentReceipt(data.order);
                });
                bootstrap.Modal.getInstance(payModalEl).hide();
                if (typeof clearCart === 'function') clearCart();
            } else {
                toastr.error(data.message||'{{ __("pagination.payment_failed") }}');
            }
        })
        .catch(err => {
            btn.removeAttribute('data-kt-indicator'); btn.disabled=false;
            toastr.error(err.message||'{{ __("pagination.payment_error") }}');
            console.error('[processSplitPayments] error:', err);
        });
    };

    document.addEventListener('click', function (e) {
        if (e.target.closest('#pm-bargain-apply-btn'))  { applyBargainDiscount();  return; }
        if (e.target.closest('#pm-bargain-remove-btn')) { removeBargainDiscount(); return; }
        var qb=e.target.closest('.pm-quick-btn');
        if (qb) { var inp=g('pm-amount-'+qb.dataset.paymentType); if (inp) { inp.value=qb.dataset.quickAmount; inp.dispatchEvent(new Event('input',{bubbles:true})); inp.focus(); } return; }
        var addBtn=e.target.closest('.pm-add-btn'); if (addBtn&&!addBtn.disabled) { addPayment(addBtn.dataset.paymentType); return; }
        var remBtn=e.target.closest('.pm-remove-btn'); if (remBtn) { removePayment(parseInt(remBtn.dataset.index,10)); return; }
        if (e.target.closest('#pm-process-btn')) window.processSplitPayments();
    });
    document.addEventListener('change', e => { var sel=e.target.closest('.pm-account-select'); if (sel) validateBtn(sel.dataset.paymentType); });
    document.addEventListener('input',  e => { var inp=e.target.closest('.pm-amount-input'); if (!inp) return; validateBtn(inp.dataset.paymentType); updateCashCalc(inp.dataset.paymentType); });
    document.addEventListener('shown.bs.tab', e => { var type=e.target.dataset.paymentType; if (!type) return; toggleRef(type); buildQuickAmounts(type); validateBtn(type); updateRemainingHint(type); });

})();
</script>


{{-- ═══════════════════════════════════════════════
     5. PAYMENT TYPE HELPERS
════════════════════════════════════════════════ --}}
<script>
function getPaymentTypeColor(type) {
    return ({cash:'success',card:'primary',bank_account:'info',mobile_money:'warning',digital_wallet:'danger',check:'dark',credit:'secondary'})[type]||'primary';
}
function getPaymentTypeIcon(type) {
    return ({cash:'ki-wallet',card:'ki-credit-cart',bank_account:'ki-bank',mobile_money:'ki-phone',digital_wallet:'ki-wallet',check:'ki-document',credit:'ki-time',other:'ki-add-files'})[type]||'ki-wallet';
}
function formatPaymentType(type) {
    if (!type) return '';
    return type.split('_').map(w => w.charAt(0).toUpperCase()+w.slice(1)).join(' ');
}
</script>


{{-- ═══════════════════════════════════════════════
     6. CANCEL POS ORDER
════════════════════════════════════════════════ --}}
<script>
    function cancelPOSOrder(orderId) {
        Swal.fire({
            title: '{{ __("passwords.cancel_title") }}', text: '{{ __("passwords.cancel_confirmation") }}',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __("passwords.cancel_order") }}', cancelButtonText: '{{ __("passwords.keep_order") }}',
            reverseButtons: true, showLoaderOnConfirm: true,
            preConfirm: () => $.ajax({ url: '/pos-cancel/'+orderId, method:'POST',
                data: { _token:'{{ csrf_token() }}', status:'cancelled' }, dataType:'json' })
                .then(r => { if (r.success===false) throw new Error(r.message||'Failed'); return r; })
                .catch(e => Swal.showValidationMessage(`Request failed: ${e.statusText||e.message}`))
        }).then(result => {
            if (result.isConfirmed && result.value?.success) {
                Swal.fire({ title:'{{ __("passwords.success") }}', text:result.value.message, icon:'success', confirmButtonColor:'#3085d6' })
                    .then(() => location.reload());
            }
        });
    }
</script>

<script>
    // Update invoice status — 'paid' and 'partially_paid' are intercepted
    // and redirected to the Record Payment modal, since the backend now
    // rejects those two values on this endpoint (they need an amount +
    // payment method, not a bare status string).
    function updateInvoiceStatus(invoiceId, selectedStatus) {
        const statusSelect = document.querySelector(`select[onchange*="updateInvoiceStatus(${invoiceId},"]`);

        if (selectedStatus === 'paid' || selectedStatus === 'partially_paid') {
            if (statusSelect) {
                statusSelect.value = statusSelect.getAttribute('data-current-status') || 'draft';
            }
            const modalEl = document.getElementById('recordPaymentModal' + invoiceId);
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else {
                toastr.warning('{{ __("payments.use_record_payment_button") }}');
            }
            return;
        }

        if (statusSelect) statusSelect.value = selectedStatus;

        const updateRoute = '/invoices/' + invoiceId + '/status';
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
    }

    // Update invoice (edit)
    function updateInvoice(invoiceId) {
        const submitButton = document.getElementById('editInvoiceButton' + invoiceId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        const form = document.getElementById('editInvoiceForm' + invoiceId);
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        const updateUrl = '/invoices/' + invoiceId;
        handleEditResponse(data, updateUrl, invoiceId, submitButton);
    }

    function toggleSendChannel(invoiceId) {
        const form = document.getElementById('sendInvoiceForm' + invoiceId);
        const channel = form.querySelector('input[name="channel"]:checked').value;

        const isEmail    = channel === 'email';
        const isPhone    = channel === 'whatsapp' || channel === 'sms';
        const isDownload = channel === 'print';

        const emailWrap = document.getElementById('email-field-wrap' + invoiceId);
        const phoneWrap = document.getElementById('phone-field-wrap' + invoiceId);
        const downloadNote = document.getElementById('download-note' + invoiceId);

        emailWrap.classList.toggle('d-none', !isEmail);
        phoneWrap.classList.toggle('d-none', !isPhone);
        downloadNote.classList.toggle('d-none', !isDownload);

        emailWrap.querySelector('input[name="email"]').required = isEmail;
        phoneWrap.querySelector('input[name="phone"]').required  = isPhone;
    }

    // Send invoice — posts channel (+ email if provided). On success, if the
    // response includes a download_url (channel = 'download'), open the PDF
    // in a new tab immediately.
    function sendInvoice(invoiceId) {
        const submitButton = document.getElementById('sendInvoiceButton' + invoiceId);
        if (submitButton.disabled) return; // already in flight

        const form    = document.getElementById('sendInvoiceForm' + invoiceId);
        const payload = Object.fromEntries(new FormData(form).entries());

        if (!payload.channel) {
            toastr.error('{{ __("payments.select_a_channel") }}');
            return;
        }

        if (payload.channel === 'whatsapp' || payload.channel === 'sms') {
            const result = validateE164Phone(payload.phone);
            const phoneInput = form.querySelector('input[name="phone"]');
            const errorEl = document.getElementById('phone-error' + invoiceId);

            if (!result.valid) {
                errorEl.textContent = result.error;
                errorEl.classList.remove('d-none');
                phoneInput.focus();
                return;
            }

            errorEl.classList.add('d-none');
            payload.phone = result.formatted;
        }

        if (payload.channel === 'email' && !payload.email) {
            const emailInput = form.querySelector('input[name="email"]');
            emailInput.classList.add('is-invalid');
            emailInput.focus();
            return;
        }

        LiveBlade.toggleButtonLoading(submitButton, true);
        submitButton.disabled = true; // belt and suspenders — don't rely solely on toggleButtonLoading

        fetch('/invoices/' + invoiceId + '/send', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(r => r.json().then(body => ({ ok: r.ok, body })))
        .then(({ ok, body }) => {
            LiveBlade.toggleButtonLoading(submitButton, false);
            submitButton.disabled = false;
            if (ok && body.success) {
                toastr.success(body.message);
                bootstrap.Modal.getInstance(document.getElementById('sendInvoiceModal' + invoiceId))?.hide();
                if (body.download_url) {
                    window.open(body.download_url, '_blank');
                }
                setTimeout(() => location.reload(), 400);
            } else {
                toastr.error(body.message || '{{ __("payments.invoice_send_failed") }}');
            }
        })
        .catch(err => {
            LiveBlade.toggleButtonLoading(submitButton, false);
            submitButton.disabled = false;
            toastr.error('{{ __("payments.invoice_send_failed") }}');
            console.error('[sendInvoice] error:', err);
        });
    }

    /**
     * Normalizes a phone number toward E.164 and validates it.
     * Strips spaces, dashes, parens, dots. Requires a leading + and 8-15 digits after it.
     * Returns { valid: true, formatted: '+2547...' } or { valid: false, error: '...' }
     */
    function validateE164Phone(rawValue) {
        if (!rawValue || !rawValue.trim()) {
            return { valid: false, error: '{{ __("payments.phone_required") }}' };
        }

        // Strip everything except digits and a leading +
        let cleaned = rawValue.trim().replace(/[\s\-\.\(\)]/g, '');

        // Allow users to type 00 instead of + (common in many regions)
        if (cleaned.startsWith('00')) {
            cleaned = '+' + cleaned.slice(2);
        }

        // Add + if missing but looks like digits only
        if (!cleaned.startsWith('+')) {
            cleaned = '+' + cleaned;
        }

        // E.164: + followed by 8 to 15 digits, first digit after + can't be 0
        const e164Pattern = /^\+[1-9]\d{7,14}$/;

        if (!e164Pattern.test(cleaned)) {
            return {
                valid: false,
                error: '{{ __("payments.invalid_phone_format") }}'
            };
        }

        return { valid: true, formatted: cleaned };
    }

    // Record a manual payment against an invoice — full or partial.
    function submitInvoicePayment(invoiceId) {
        const submitButton = document.getElementById('recordPaymentButton' + invoiceId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        const form    = document.getElementById('recordPaymentForm' + invoiceId);
        const payload = Object.fromEntries(new FormData(form).entries());

        if (!payload.payment_method_id) {
            LiveBlade.toggleButtonLoading(submitButton, false);
            toastr.warning('{{ __("payments.select_payment_method") }}');
            return;
        }

        fetch('/invoices/' + invoiceId + '/record-payment', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(r => r.json().then(body => ({ ok: r.ok, body })))
        .then(({ ok, body }) => {
            LiveBlade.toggleButtonLoading(submitButton, false);
            if (ok && body.success) {
                toastr.success(body.message);
                bootstrap.Modal.getInstance(document.getElementById('recordPaymentModal' + invoiceId))?.hide();
                setTimeout(() => location.reload(), 400);
            } else {
                toastr.error(body.message || '{{ __("payments.payment_record_failed") }}');
            }
        })
        .catch(err => {
            LiveBlade.toggleButtonLoading(submitButton, false);
            toastr.error('{{ __("payments.payment_record_failed") }}');
            console.error('[submitInvoicePayment] error:', err);
        });
    }


    function voidInvoice(invoiceId) {
        const submitButton = document.getElementById('voidInvoiceButton' + invoiceId);
        const form = document.getElementById('voidInvoiceForm' + invoiceId);
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Show loading state
        LiveBlade.toggleButtonLoading(submitButton, true);
        
        // Send to backend - LiveBlade will handle the response
        const updateRoute = '/invoices/' + invoiceId + '/void';
        
        // Use LiveBlade's built-in method
        LiveBlade.loopUpdateStatus(updateRoute, data);
        
        // Note: LiveBlade.loopUpdateStatus will handle:
        // - The response
        // - Showing messages
        // - Reloading the component
        // - Everything!
    }

    // ── Discount Preview ───────────────────────────────────────────
    function calculateDiscountPreview(invoiceId) {
        const discountInput = document.getElementById(`discountAmount${invoiceId}`);
        const previewDiscount = document.getElementById(`previewDiscount${invoiceId}`);
        const previewTotal = document.getElementById(`previewTotal${invoiceId}`);
        
        if (!discountInput) return;
        
        const subtotal = parseFloat(document.getElementById(`displaySubtotal${invoiceId}`).textContent.replace(/,/g, '')) || 0;
        const tax = parseFloat(document.getElementById(`displayTax${invoiceId}`).textContent.replace(/,/g, '')) || 0;
        const discount = parseFloat(discountInput.value) || 0;
        
        // Ensure discount doesn't exceed subtotal
        const maxDiscount = subtotal;
        let validDiscount = discount;
        
        if (discount > maxDiscount) {
            validDiscount = maxDiscount;
            discountInput.value = maxDiscount.toFixed(2);
            toastr.warning('{{ __("payments.discount_exceeds_subtotal") }}');
        }
        
        const newTotal = subtotal - validDiscount + tax;
        
        previewDiscount.textContent = validDiscount.toFixed(2);
        previewTotal.textContent = newTotal.toFixed(2);
        
        // Update max discount display
        document.getElementById(`maxDiscount${invoiceId}`).textContent = maxDiscount.toFixed(2);
    }

    // ── Apply Discount ─────────────────────────────────────────────
    function applyInvoiceDiscount(invoiceId) {
        const submitButton = document.getElementById(`discountInvoiceButton${invoiceId}`);
        const form = document.getElementById(`discountInvoiceForm${invoiceId}`);
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Validate
        const discountAmount = parseFloat(data.discount_amount);
        const subtotal = parseFloat(document.getElementById(`displaySubtotal${invoiceId}`).textContent.replace(/,/g, '')) || 0;
        
        if (isNaN(discountAmount) || discountAmount < 0) {
            toastr.error('{{ __("payments.enter_valid_discount") }}');
            return;
        }
        
        if (discountAmount === 0) {
            toastr.warning('{{ __("payments.discount_cannot_be_zero") }}');
            return;
        }
        
        if (discountAmount > subtotal) {
            toastr.error('{{ __("payments.discount_exceeds_subtotal") }}');
            return;
        }
        
        const label = submitButton.querySelector('.indicator-label');
        const progress = submitButton.querySelector('.indicator-progress');
        
        submitButton.disabled = true;
        if (label) label.style.display = 'none';
        if (progress) progress.style.display = 'inline-flex';
        
        fetch(`/invoices/${invoiceId}/apply-discount`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                discount_amount: discountAmount,
                discount_notes: data.discount_notes || null,
            }),
        })
        .then(res => res.json())
        .then(data => {
            submitButton.disabled = false;
            if (label) label.style.display = 'inline-flex';
            if (progress) progress.style.display = 'none';
            
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById(`discountInvoiceModal${invoiceId}`));
                if (modal) modal.hide();
                
                toastr.success(data.message);
                
                // Reload the component
                if (typeof LiveBlade !== 'undefined') {
                    LiveBlade.reloadComponent('reloadInvoiceComponent');
                } else {
                    location.reload();
                }
            } else {
                toastr.error(data.message);
            }
        })
        .catch(() => {
            submitButton.disabled = false;
            if (label) label.style.display = 'inline-flex';
            if (progress) progress.style.display = 'none';
            toastr.error('{{ __("payments.discount_apply_failed") }}');
        });
    }

    // ── Remove Discount ────────────────────────────────────────────
    function removeDiscount(invoiceId) {
        if (!confirm('{{ __("payments.confirm_remove_discount") }}')) return;
        
        const modal = bootstrap.Modal.getInstance(document.getElementById(`discountInvoiceModal${invoiceId}`));
        
        fetch(`/invoices/${invoiceId}/remove-discount`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (modal) modal.hide();
                toastr.success(data.message);
                if (typeof LiveBlade !== 'undefined') {
                    LiveBlade.reloadComponent('reloadInvoiceComponent');
                } else {
                    location.reload();
                }
            } else {
                toastr.error(data.message);
            }
        })
        .catch(() => {
            toastr.error('{{ __("payments.discount_remove_failed") }}');
        });
    }

</script>