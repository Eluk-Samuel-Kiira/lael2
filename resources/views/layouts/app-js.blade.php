<!-- important imports for laravel liveblades -->
<script src="{{ asset('blade-live/forms/forms.min.js') }}" type="module"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>



<style>
    body {
        font-family: Arial, sans-serif;
    }
    nav {
        margin-bottom: 10px;
    }
    a {
        margin-right: 10px;
        text-decoration: none;
        color: blue;
    }
    a:hover {
        text-decoration: underline;
    }
    #content {
        padding: 20px;
        border: 1px solid #ddd;
        margin-top: 10px;
        min-height: 100px;
        position: relative;
    }
    #loader {
        display: none;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 40px;
        height: 40px;
        border: 4px solid rgba(0, 0, 0, 0.1);
        border-top: 4px solid blue;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        z-index: 10;
    }
    @keyframes spin {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }
</style>


<!-- Page Navigator functions -->
<script>
    // Function to show the loader
    function showLoaderApp() {
        const loader = document.getElementById('loader');
        if (loader) {
            loader.style.display = 'block';
        }
    }

    // Function to hide the loader
    function hideLoaderApp() {
        const loader = document.getElementById('loader');
        if (loader) {
            loader.style.display = 'none';
        }
    }


    // Function to handle navigation
    function navigateToAppPages(url) {
        showLoaderApp(); // Show loader during content loading

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(data => {
                history.pushState({ url: url }, null, url); // Store state in history

                // Extract the content within #kt_app_main
                const parser = new DOMParser();
                const doc = parser.parseFromString(data, 'text/html');
                const ktAppMain = doc.getElementById('kt_app_main');

                if (ktAppMain) {
                    // Update the title and content
                    const titleMatch = data.match(/<title>(.*?)<\/title>/i);
                    document.title = titleMatch ? titleMatch[1] : 'Default Title';

                    // Replace the content of #kt_app_main in the current page
                    document.getElementById('kt_app_main').innerHTML = ktAppMain.innerHTML;

                    // Update menu active state
                    updateActiveMenuLink(url);
                } else {
                    console.error('Error: #kt_app_main not found in the fetched content.');
                }

                setTimeout(hideLoader, 300);
            })
            .catch(error => {
                console.error('Error fetching content:', error);
                document.getElementById('kt_app_main').innerHTML = '404 Page Not Found.';
                hideLoaderApp();
            });
    }

    // Function to load content based on URL
    function renderAppPage(url) {
        const pageContent = document.getElementById('kt_app_main');

        if (!pageContent) {
            console.error('Error: Element #kt_app_main not found.');
            return; // Stop execution if the element is missing
        }

        showLoaderApp(); // Show loader during content loading

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text(); // Get the HTML content
            })
            .then(data => {
                // Parse the fetched HTML
                const parser = new DOMParser();
                const doc = parser.parseFromString(data, 'text/html');
                const ktAppMain = doc.getElementById('kt_app_main');

                if (ktAppMain) {
                    // Extract the title from the fetched content
                    const titleMatch = data.match(/<title>(.*?)<\/title>/i);
                    document.title = titleMatch ? titleMatch[1] : 'Default Title'; // Update the document title

                    // Insert the fetched content into the page
                    pageContent.innerHTML = ktAppMain.innerHTML;
                } 
                // else {
                //     console.error('Error: #kt_app_main not found in the fetched content.');
                // }

                // Hide the loader after a small delay (optional)
                setTimeout(hideLoader, 300);
            })
            .catch(error => {
                console.error('Error fetching content:', error);

                if (pageContent) {
                    pageContent.innerHTML = '404 Page Not Found.'; // Fallback content
                }

                hideLoaderApp();
            });
    }


    // Handle back/forward navigation
    window.addEventListener('popstate', (event) => {
        if (event.state && event.state.url) {
            renderAppPage(event.state.url); // Load the correct content
        } else {
            renderAppPage(window.location.pathname); // Default behavior
        }
    });

    // Reload page especially when going to the database
    function reloadToApp(url) {
        window.location.href = url; // Redirect on success
    }

    function updateActiveMenuLink(url) {
        // Remove 'active' class from all menu links
        document.querySelectorAll('.menu-link').forEach(link => {
            link.classList.remove('active');
        });

        // Add 'active' class to the matching menu link
        document.querySelectorAll('.menu-link').forEach(link => {
            const onclickAttr = link.getAttribute('onclick');
            if (onclickAttr && onclickAttr.includes(url)) {
                link.classList.add('active');
            }
        });
    }

    // Initial load
    renderAppPage(window.location.pathname);
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



<!-- Taxes and Promotions -->
<script>

    function editPromotionInstanceLoop(uniqueId) {
        const submitButton = document.getElementById('editPromotionButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Select the form and create FormData from it
        var form = document.getElementById('kt_modal_promotion_form' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);

        // Set up the URL dynamically
        var updateUrl = '{{ route('promotion.update', ['promotion' => ':id']) }}'.replace(':id', uniqueId);
        
        // Submit form data asynchronously
        handleEditResponse(data, updateUrl, uniqueId, submitButton);

    }

    function updatePromotionStatus(uniqueId, selectedStatus) {
        // Update text instantly
        const label = document.getElementById('promotion-label-' + uniqueId);
        if (label) {
            label.innerText = selectedStatus == 1 ? 'Active' : 'Inactive';
        }

        // console.log(selectedStatus)
        // Send update to backend
        const updateRoute = '/promotion-status/' + uniqueId;
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
    }


    function submitPromotionForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
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

    function editTaxInstanceLoop(uniqueId) {
        const submitButton = document.getElementById('editTaxButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Select the form and create FormData from it
        var form = document.getElementById('kt_modal_tax_form' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);

        // Set up the URL dynamically
        var updateUrl = '{{ route('tax.update', ['tax' => ':id']) }}'.replace(':id', uniqueId);
        
        // Submit form data asynchronously
        handleEditResponse(data, updateUrl, uniqueId, submitButton);

    }

    
    function submitTaxForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
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


    function updateTaxesStatus(uniqueId, selectedStatus) {
        // Update label instantly
        const label = document.getElementById('tax-label-' + uniqueId);
        if (label) {
            label.innerText = selectedStatus == 1 ? '{{ __("auth._active") }}' : '{{ __("auth._inactive") }}';
        }

        // Send update to backend
        const updateRoute = '/tax-status/' + uniqueId;
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
    }


</script>


<!-- Inventory Mgt -->
<script>
    
    function updateInventoryTransfer(uniqueId) {
        const submitButton = document.getElementById('editInvTransferButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Select the form and create FormData from it
        var form = document.getElementById('stockItemTransfer' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);

        // Set up the URL dynamically
        var updateUrl = '/transfer-stock/' + uniqueId;
        // console.log(updateUrl);
        handleEditResponse(data, updateUrl, uniqueId, submitButton);
    }
    
    function initializeStockInputs() {
        // Select all quantity inputs
        document.querySelectorAll(".quantity-input").forEach(input => {
            // Remove any existing listener first (optional, safer)
            input.replaceWith(input.cloneNode(true));
        });

        // Re-select cloned inputs
        document.querySelectorAll(".quantity-input").forEach(input => {
            input.addEventListener("input", function () {
                let itemId      = this.dataset.itemId;
                let overallEl   = document.getElementById("overallQty" + itemId);
                let newStockEl  = document.getElementById("newStock" + itemId);

                let overallInit = parseInt(this.dataset.overall) || 0;
                let current     = parseInt(this.dataset.current) || 0;
                let adjust      = parseInt(this.value) || 0;

                let newStock    = current;
                let newOverall  = overallInit;

                if (adjust > 0) {
                    if (adjust > overallInit) {
                        this.value = overallInit;
                        adjust     = overallInit;
                        toastr['warning']('{{ __("pagination.max_quantity_reached") }}');
                    }
                    newStock   = current + adjust;
                    newOverall = overallInit - adjust;
                } else if (adjust < 0) {
                    let absAdjust = Math.abs(adjust);
                    if (absAdjust > current) {
                        this.value = -current;
                        adjust     = -current;
                        toastr['warning']('{{ __("pagination.max_quantity_reached") }}');
                    }
                    newStock   = current + adjust;
                    newOverall = overallInit + Math.abs(adjust);
                }

                newStockEl.value  = newStock;
                overallEl.value   = newOverall;
            });
        });
    }

    
    function updateInventoryAdjustment(uniqueId) {
        const submitButton = document.getElementById('editInvAdjustButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Select the form and create FormData from it
        var form = document.getElementById('adjustStockForm' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);

        // Set up the URL dynamically
        var updateUrl = '{{ route('stocks.update', ['stock' => ':id']) }}'.replace(':id', uniqueId);

        // console.log(updateUrl);
        handleEditResponse(data, updateUrl, uniqueId, submitButton);
    }

    
    $(document).ready(function() {
        // ========== REUSABLE FUNCTIONS ==========
        
        // Generic function to load departments based on location
        function loadDepartments(locationId, targetSelect, options = {}) {
            const {
                modal = null,
                selectedDeptId = null,
                placeholder = "{{__('auth._department')}}",
                isFilter = false
            } = options;
            
            // Show loading
            targetSelect.html(`<option value="">${isFilter ? '{{ __("auth._department") }}' : ''}{{ __("auth._loading") }}</option>`);
            if (targetSelect.data('select2')) targetSelect.trigger('change');
            
            if (!locationId) {
                // No location - reset to default state
                if (isFilter) {
                    // Reset to all departments for filter
                    let options = '<option value="">{{ __("auth._department") }}</option>';
                    @foreach ($departments as $department)
                        options += '<option value="{{ $department->id }}">{{ $department->name }}</option>';
                    @endforeach
                    targetSelect.html(options).prop('disabled', false);
                } else {
                    // Clear for modals
                    targetSelect.html('<option value=""></option>').prop('disabled', false);
                }
                
                if (targetSelect.data('select2')) targetSelect.trigger('change');
                return;
            }
            
            // Fetch departments via AJAX
            $.ajax({
                url: '{{ route("get.departments.by.location", "") }}/' + locationId,
                type: 'GET',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(response) {
                    let options = isFilter ? '<option value="">{{ __("auth._department") }}</option>' : '<option value=""></option>';
                    
                    if (response.success && response.departments.length > 0) {
                        // Sort alphabetically
                        const sortedDepts = response.departments.sort((a, b) => a.name.localeCompare(b.name));
                        
                        sortedDepts.forEach(dept => {
                            const selected = (selectedDeptId && dept.id == selectedDeptId) ? 'selected' : '';
                            options += `<option value="${dept.id}" ${selected}>${dept.name}</option>`;
                        });
                        targetSelect.html(options).prop('disabled', false);
                    } else {
                        options += '<option value="" disabled>{{ __("auth.no_departments_found") }}</option>';
                        targetSelect.html(options).prop('disabled', true);
                    }
                    
                    if (targetSelect.data('select2')) targetSelect.trigger('change');
                },
                error: function() {
                    targetSelect.html('<option value="">{{ __("auth.error_loading_departments") }}</option>').prop('disabled', true);
                    if (targetSelect.data('select2')) targetSelect.trigger('change');
                }
            });
        }
        
        // Generic function to initialize modal selects
        function initModalSelects(modal, locationSelect, departmentSelect, originalDeptId = null) {
            // Store original department ID
            modal.data('department-id', originalDeptId || departmentSelect.val());
            
            // Initialize Select2
            [locationSelect, departmentSelect].forEach(select => {
                if (select.data('select2')) select.select2('destroy');
                select.select2({
                    placeholder: "{{__('auth._select')}}",
                    allowClear: true,
                    dropdownParent: modal
                });
            });
            
            // Load departments if location is selected
            const locationId = locationSelect.val();
            if (locationId) {
                setTimeout(() => loadDepartments(locationId, departmentSelect, {
                    modal,
                    selectedDeptId: modal.data('department-id')
                }), 200);
            }
        }
        
        // ========== CREATE MODAL ==========
        $('#kt_modal_add_inventory').on('shown.bs.modal', function() {
            const modal = $(this);
            const locationSelect = modal.find('select[name="location_id"]');
            const departmentSelect = modal.find('select[name="department_id"]');
            
            modal.find('form')[0]?.reset();
            departmentSelect.html('<option value=""></option>');
            initModalSelects(modal, locationSelect, departmentSelect);
        });
        
        // ========== EDIT MODALS ==========
        $(document).on('show.bs.modal', '[id^="editItem"]', function() {
            const modal = $(this);
            initModalSelects(
                modal,
                modal.find('select[name="location_id"]'),
                modal.find('select[name="department_id"]')
            );
        });
        
        // ========== STOCK TRANSFER MODALS ==========
        $(document).on('show.bs.modal', '[id^="stockTransfer"]', function() {
            const modal = $(this);
            const locationSelect = modal.find('select[name="location_id"]');
            const departmentSelect = modal.find('select[name="department_id"]');
            
            initModalSelects(modal, locationSelect, departmentSelect);
        });
        
        // ========== LOCATION CHANGE HANDLER (ALL MODALS) ==========
        $(document).on('change', '[id^="editItem"] select[name="location_id"], [id^="stockTransfer"] select[name="location_id"], #kt_modal_add_inventory select[name="location_id"]', function() {
            const locationId = $(this).val();
            const modal = $(this).closest('.modal');
            const departmentSelect = modal.find('select[name="department_id"]');
            
            loadDepartments(locationId, departmentSelect, {
                modal,
                selectedDeptId: modal.data('department-id')
            });
        });
        
        // ========== FILTER DROPDOWNS ==========
        $('#locationFilter').on('change', function() {
            const locationId = $(this).val();
            const departmentFilter = $('#departmentFilter');
            const currentDeptId = departmentFilter.val();
            
            loadDepartments(locationId, departmentFilter, {
                selectedDeptId: currentDeptId,
                isFilter: true
            });
        });
        
        // Initialize filter on page load
        const selectedLocation = $('#locationFilter').val();
        if (selectedLocation) {
            loadDepartments(selectedLocation, $('#departmentFilter'), {
                selectedDeptId: $('#departmentFilter').val(),
                isFilter: true
            });
        }
        
        // Initialize Select2 for filters
        $('#locationFilter, #departmentFilter').each(function() {
            if ($(this).data('select2')) {
                $(this).select2({
                    placeholder: $(this).is('#locationFilter') ? "{{ __('pagination._location') }}" : "{{ __('auth._department') }}",
                    allowClear: true
                });
            }
        });
    });

</script>


<script>
    
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for Select2 to initialize
        setTimeout(function() {
            const variantSelect = document.getElementById('variant-select');
            const quantityInput = document.querySelector('input[name="quantity_on_hand"]');
            const allocatedInput = document.querySelector('input[name="quantity_allocated"]');
            
            if (!variantSelect || !quantityInput || !allocatedInput) return;
            
            function updateAvailableQuantity() {
                const selectedOption = variantSelect.options[variantSelect.selectedIndex];
                if (selectedOption && selectedOption.value !== '') {
                    const variantQuantity = selectedOption.getAttribute('data-quantity') || 0;
                    const allocated = parseInt(allocatedInput.value) || 0;
                    const available = Math.max(0, parseInt(variantQuantity) - allocated);
                    
                    quantityInput.value = available;
                } else {
                    quantityInput.value = 0;
                }
            }
            
            // Update when variant changes
            if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                jQuery(variantSelect).on('change', updateAvailableQuantity);
            } else {
                variantSelect.addEventListener('change', updateAvailableQuantity);
            }
            
            // Update when allocated quantity changes
            allocatedInput.addEventListener('input', updateAvailableQuantity);
            
            // Initialize on page load
            updateAvailableQuantity();
        }, 300);
    });


    function editItemInstanceLoop(uniqueId) {
        const submitButton = document.getElementById('updateItemButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Select the form and create FormData from it
        var form = document.getElementById('kt_modal_item_form' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);

        // Set up the URL dynamically
        var updateUrl = '{{ route('items.update', ['item' => ':id']) }}'.replace(':id', uniqueId);

        // console.log(updateUrl);
        handleEditResponse(data, updateUrl, uniqueId, submitButton);
    }

    function submitItemForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
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


    document.addEventListener("change", function (e) {
        if (e.target.id === "departmentFilter" || e.target.id === "locationFilter") {
            const departmentFilter = document.getElementById("departmentFilter");
            const locationFilter = document.getElementById("locationFilter");
            const rows = document.querySelectorAll("#kt_table_users tbody tr");

            const selectedDepartment = departmentFilter ? departmentFilter.value : "";
            const selectedLocation = locationFilter ? locationFilter.value : "";

            rows.forEach(row => {
                const rowDepartment = row.getAttribute("data-department");
                const rowLocation = row.getAttribute("data-location");

                let showRow = true;

                if (selectedDepartment && rowDepartment !== selectedDepartment) {
                    showRow = false;
                }
                if (selectedLocation && rowLocation !== selectedLocation) {
                    showRow = false;
                }

                row.style.display = showRow ? "" : "none";
            });
        }
    });

</script>


<!-- Product Catalog -->
<script>
    function submitProductForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
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
    
    function editProductInstanceLoop(uniqueId) {
        
        const submitButton = document.getElementById('editProductButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Select the form and create FormData from it
        var form = document.getElementById('kt_modal_product_form' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);

        // Set up the URL dynamically
        var updateUrl = '{{ route('products.update', ['product' => ':id']) }}'.replace(':id', uniqueId);
        
        // Submit form data asynchronously
        handleEditResponse(data, updateUrl, uniqueId, submitButton);

    }

    function updateProductStatus(uniqueId, selectedStatus) {
        // console.log(" ID:", uniqueId, "Selected status:", selectedStatus);
        const updateRoute = '/product-status/' + uniqueId;
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

    
    function submitCategoryForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
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

    function editCategoryInstanceLoop(uniqueId) {
        const submitButton = document.getElementById('editUOMButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Select the form and create FormData from it
        var form = document.getElementById('kt_modal_edit_uom_form' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);

        // Set up the URL dynamically
        var updateUrl = '{{ route('category.update', ['category' => ':id']) }}'.replace(':id', uniqueId);
        
        // Submit form data asynchronously
        handleEditResponse(data, updateUrl, uniqueId, submitButton);

    }
    
    function updateCategoryStatus(uniqueId, selectedStatus) {
        // console.log(" ID:", uniqueId, "Selected status:", selectedStatus);
        const updateRoute = '/category-status/' + uniqueId;
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
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
    
    function editUserInstanceLoop(uniqueId) {
        const submitButton = document.getElementById('submitEmplButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        var form = document.getElementById('edit_user_form' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);
        var updateUrl = '{{ route('user.update', ['user' => ':id']) }}'.replace(':id', uniqueId);

        handleEditResponse(data, updateUrl, uniqueId, submitButton);
    }

    function updateUserStatus(uniqueId, selectedStatus) {
        // console.log(" ID:", uniqueId, "Selected status:", selectedStatus);
        const updateRoute = '/user-status/' + uniqueId;
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
    }
</script>
<script>

    function submitEmployeeForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
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

    function editEmployeeInstanceLoop(uniqueId) {
        const submitButton = document.getElementById('submitEmplButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        var form = document.getElementById('edit_user_form' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);
        var updateUrl = '{{ route('employee.update', ['employee' => ':id']) }}'.replace(':id', uniqueId);

        handleEditResponse(data, updateUrl, uniqueId, submitButton);
    }

    function updateEmployeeStatus(uniqueId, selectedStatus) {
        // console.log(" ID:", uniqueId, "Selected status:", selectedStatus);
        const updateRoute = '/employee-status/' + uniqueId;
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
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


<!-- Currency -->
<script>

    function updateStatusCurrency(uniqueId, selectedStatus) {
        // console.log(" ID:", uniqueId, "Selected status:", selectedStatus);
        const updateRoute = '/currency-status/' + uniqueId;
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
    }

    function editInstanceLoopCurrency(uniqueId) {
        const submitButton = document.getElementById('editCurrencyButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        var form = document.getElementById('kt_modal_edit_currency_form' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);
        var updateUrl = '{{ route('currency.update', ['currency' => ':id']) }}'.replace(':id', uniqueId);
        handleEditResponse(data, updateUrl, uniqueId, submitButton);
    }

    function submitCurrencyForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
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

</script>


<!-- Location -->
<script>

    function updatePrimaryStatus(uniqueId, selectedStatus) {
        // console.log(" ID:", uniqueId, "Selected status:", selectedStatus);
        const updateRoute = '/location-primary/' + uniqueId;
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
    }

    function updateLocationStatus(uniqueId, selectedStatus) {
        // console.log(" ID:", uniqueId, "Selected status:", selectedStatus);
        const updateRoute = '/location-status/' + uniqueId;
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
    }

    function editInstanceLoopLocation(uniqueId) {
        const submitButton = document.getElementById('editLocationButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        var form = document.getElementById('kt_modal_edit_location_form' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);
        var updateUrl = '{{ route('locations.update', ['location' => ':id']) }}'.replace(':id', uniqueId);
        handleEditResponse(data, updateUrl, uniqueId, submitButton);
    }

    function submitLocationForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
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


<!-- Unit Of Measure -->
<script> 
    function submitFormUOM(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
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

    function editInstanceLoopUOM(uniqueId) {
        
        const submitButton = document.getElementById('editUOMButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);
        
        var form = document.getElementById('kt_modal_edit_uom_form' + uniqueId);
        var formData = new FormData(form);
        var data = Object.fromEntries(formData.entries());
        // console.log(data);

        var updateUrl = '{{ route('uom.update', ['uom' => ':id']) }}'.replace(':id', uniqueId);
        handleEditResponse(data, updateUrl, uniqueId, submitButton);
    }


    function updateStatusUOM(uniqueId, selectedStatus) {
        // console.log(" ID:", uniqueId, "Selected status:", selectedStatus);
        const updateRoute = '/uom-status/' + uniqueId;
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
    }

</script>


<!-- Department -->
<script>
    
    function submitFormDept(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
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

    function editInstanceLoopDept(uniqueId) {
        const submitButton = document.getElementById('editDepartmentButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        const form = document.getElementById('kt_modal_edit_department_form' + uniqueId);
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        // console.log(data)

        // Set up the URL dynamically
        var updateUrl = '{{ route('department.update', ['department' => ':id']) }}'.replace(':id', uniqueId);
        handleEditResponse(data, updateUrl, uniqueId, submitButton);
    }

    function updateStatusDept(uniqueId, selectedStatus) {
        // console.log(" ID:", uniqueId, "Selected status:", selectedStatus);
        const updateRoute = '/department-status/' + uniqueId;
        LiveBlade.loopUpdateStatus(updateRoute, selectedStatus);
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
        initializeStockInputs();

        const tableId = '#kt_table_users';

        // Destroy the existing DataTable instance if it exists
        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().destroy();
        }

        // Reinitialize the DataTable
        $(tableId).DataTable({
            paging: false,
            searching: true,
            ordering: true,
            responsive: false, // Disable responsive behavior
            autoWidth: false, // Prevent automatic column resizing
            language: {
                emptyTable: "No data available",
            },
            columnDefs: [
                // Specify widths for specific columns (optional)
                { targets: 0, width: "10%" }, // Example for first column
                { targets: 1, width: "15%" },
            ],
        });

        
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