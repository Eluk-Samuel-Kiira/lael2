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

    /* ── Page blur overlay — covers kt_app_main only ── */
    #spa-blur-overlay {
        position: fixed;
        inset: 0;
        z-index: 999997;
        display: none;
        cursor: not-allowed;
        /* subtle dark tint so user notices the lock */
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

<!-- Loaders — outside #kt_app_main so SPA swaps never touch them -->
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

    // ── Everything below is YOUR exact original code, word for word ──

    function navigateToAppPages(url) {
        showLoaderApp();

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(data => {
                history.pushState({ url: url }, null, url);

                const parser = new DOMParser();
                const doc = parser.parseFromString(data, 'text/html');
                const ktAppMain = doc.getElementById('kt_app_main');

                if (ktAppMain) {
                    const titleMatch = data.match(/<title>(.*?)<\/title>/i);
                    document.title = titleMatch ? titleMatch[1] : 'Default Title';
                    document.getElementById('kt_app_main').innerHTML = ktAppMain.innerHTML;
                    updateActiveMenuLink(url);

                    
                    // console.log('Auu Falls here');
                    if (typeof window.LiveBladeRefresh === 'function') {
                        window.LiveBladeRefresh();
                    }

                } else {
                    console.error('Error: #kt_app_main not found in the fetched content.');
                }

                hideLoaderApp();
            })
            .catch(error => {
                console.error('Error fetching content:', error);
                document.getElementById('kt_app_main').innerHTML = '404 Page Not Found.';
                hideLoaderApp();
            });
    }

    function renderAppPage(url) {
        const pageContent = document.getElementById('kt_app_main');
        if (!pageContent) {
            console.error('Error: Element #kt_app_main not found.');
            return;
        }

        showLoaderApp();

        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then(data => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(data, 'text/html');
                const ktAppMain = doc.getElementById('kt_app_main');
                if (ktAppMain) {
                    const titleMatch = data.match(/<title>(.*?)<\/title>/i);
                    document.title = titleMatch ? titleMatch[1] : 'Default Title';
                    pageContent.innerHTML = ktAppMain.innerHTML;
                    updateActiveMenuLink(url); // ← was missing here
                }
                hideLoaderApp();
            })
            .catch(error => {
                console.error('Error fetching content:', error);
                pageContent.innerHTML = '404 Page Not Found.';
                hideLoaderApp();
            });
    }

    window.addEventListener('popstate', (event) => {
        if (event.state && event.state.url) {
            renderAppPage(event.state.url);
        } else {
            renderAppPage(window.location.pathname);
        }
    });

    function reloadToApp(url) {
        window.location.href = url;
    }

    function updateActiveMenuLink(url) {
        // Remove all active states first
        document.querySelectorAll('.menu-link').forEach(link => {
            link.classList.remove('active');
        });
        document.querySelectorAll('.menu-item').forEach(item => {
            item.classList.remove('show');
        });

        // Normalize URL for comparison (strip trailing slash, query params)
        const normalizeUrl = (u) => u.split('?')[0].replace(/\/$/, '');
        const currentUrl = normalizeUrl(url);

        document.querySelectorAll('.menu-link[onclick]').forEach(link => {
            const onclick = link.getAttribute('onclick');

            // Extract URL from onclick="navigateToAppPages('...')" or reloadToApp('...')
            const match = onclick.match(/(?:navigateToAppPages|reloadToApp)\(['"]([^'"]+)['"]\)/);
            if (!match) return;

            const linkUrl = normalizeUrl(match[1]);

            if (linkUrl === currentUrl) {
                link.classList.add('active');

                // Walk up the DOM and open all parent accordion menu-items
                let parent = link.closest('.menu-item');
                while (parent) {
                    parent.classList.add('show');

                    // Also mark the direct parent menu-link (accordion trigger) as active-parent
                    const parentLink = parent.querySelector(':scope > .menu-link');
                    if (parentLink && !parentLink.getAttribute('onclick')) {
                        parentLink.classList.add('active');
                    }

                    // Move up to the next ancestor menu-item
                    parent = parent.parentElement?.closest('.menu-item');
                }
            }
        });
    }


    
    document.addEventListener('DOMContentLoaded', () => {
        history.replaceState({ url: window.location.pathname }, null, window.location.pathname);
        updateActiveMenuLink(window.location.href); // use full href to match query params too
    });

    window.addEventListener('load', () => {
        updateActiveMenuLink(window.location.href);
    });
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
 {{-- ── JAVASCRIPT ──────────────────────────────────────────────────────────── --}}
<script>
    {{-- Expose translations to JS --}}
    window._importLang = {
        selectFile      : '{{ __('pagination.import_js_select_file') }}',
        invalidType     : '{{ __('pagination.import_js_invalid_type') }}',
        networkError    : '{{ __('pagination.import_js_network_error') }}',
        importFailed    : '{{ __('pagination.import_js_import_failed') }}',
        filePrefix      : '{{ __('pagination.import_js_file_prefix') }}',
        statCreated     : '{{ __('pagination.import_js_created') }}',
        statSkipped     : '{{ __('pagination.import_js_skipped') }}',
        statErrors      : '{{ __('pagination.import_js_errors') }}',
        issuesSuffix    : '{{ __('pagination.import_js_issues_suffix') }}',
        sections: {
            categories    : '{{ __('pagination.import_section_categories') }}',
            sub_categories: '{{ __('pagination.import_section_sub_categories') }}',
            products      : '{{ __('pagination.import_section_products') }}',
            variants      : '{{ __('pagination.import_section_variants') }}',
        },
    };

    (function () {
        'use strict';

        const lang = window._importLang;

        /* ── File preview ─────────────────────────────────────────────────────── */
        window.previewImportFile = function (input) {
            const file = input.files[0];
            if (!file) return;
            showImportFileName(file.name);
        };

        window.handleImportDrop = function (event) {
            event.preventDefault();
            const zone = document.getElementById('importDropZone');
            zone.classList.remove('border-primary', 'bg-light-primary');

            const file = event.dataTransfer.files[0];
            if (!file) return;

            const input = document.getElementById('importFileInput');
            const dt    = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;

            showImportFileName(file.name);
        };

        function showImportFileName (name) {
            const label = document.getElementById('importFileName');
            label.textContent = lang.filePrefix + ' ' + name;
            label.classList.remove('d-none');
            hideError();
            resetResultPanel();
        }

        /* ── Submit ───────────────────────────────────────────────────────────── */
        window.submitCatalogImport = async function () {
            const input = document.getElementById('importFileInput');
            if (!input.files.length) {
                showError(lang.selectFile);
                return;
            }

            const ext = input.files[0].name.split('.').pop().toLowerCase();
            if (!['xlsx', 'xls'].includes(ext)) {
                showError(lang.invalidType);
                return;
            }

            setLoading(true);
            resetResultPanel();

            const form     = document.getElementById('importCatalogForm');
            const formData = new FormData(form);

            try {
                animateProgress();

                const response = await fetch('{{ route('catalog.import.store') }}', {
                    method : 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept'      : 'application/json',
                    },
                    body: formData,
                });

                const data = await response.json();
                stopProgress();

                if (data.success) {
                    renderReport(data.report, data.message);
                } else {
                    renderError(data.message || lang.importFailed);
                }
            } catch (err) {
                stopProgress();
                renderError(lang.networkError + ': ' + err.message);
            } finally {
                setLoading(false);
            }
        };

        /* ── Progress animation ───────────────────────────────────────────────── */
        let _progressInterval = null;
        let _progress = 0;

        function animateProgress () {
            _progress = 0;
            document.getElementById('importProgress').classList.remove('d-none');
            _progressInterval = setInterval(() => {
                _progress = Math.min(_progress + Math.random() * 8, 88);
                setProgressBar(_progress);
            }, 300);
        }

        function stopProgress () {
            clearInterval(_progressInterval);
            setProgressBar(100);
            setTimeout(() => {
                document.getElementById('importProgress').classList.add('d-none');
                setProgressBar(0);
            }, 600);
        }

        function setProgressBar (val) {
            const pct = Math.round(val);
            document.getElementById('importProgressBar').style.width = pct + '%';
            document.getElementById('importProgressPct').textContent  = pct + '%';
        }

        /* ── Report renderer ──────────────────────────────────────────────────── */
        function renderReport (report, message) {
            const panel   = document.getElementById('importResultPanel');
            const content = document.getElementById('importResultContent');

            let html = `<div class="alert alert-success d-flex align-items-center mb-4">
                <i class="ki-duotone ki-check-circle fs-2x text-success me-3">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <div class="fw-semibold">${escHtml(message)}</div>
            </div>`;

            const sections = [
                { key: 'categories',     color: 'primary' },
                { key: 'sub_categories', color: 'success' },
                { key: 'products',       color: 'warning' },
                { key: 'variants',       color: 'info'    },
            ];

            html += '<div class="row g-3 mb-4">';
            sections.forEach(s => {
                const stat  = report[s.key] || { created: 0, skipped: 0, errors: [] };
                const label = lang.sections[s.key] || s.key;
                html += `
                <div class="col-sm-6 col-xl-3">
                    <div class="card border h-100">
                        <div class="card-body p-4">
                            <div class="fw-bold text-gray-700 mb-2 fs-7">${escHtml(label)}</div>
                            <div class="d-flex gap-4">
                                <div>
                                    <div class="fw-bolder fs-2 text-success">${stat.created}</div>
                                    <div class="text-muted fs-8">${escHtml(lang.statCreated)}</div>
                                </div>
                                <div>
                                    <div class="fw-bolder fs-2 text-warning">${stat.skipped}</div>
                                    <div class="text-muted fs-8">${escHtml(lang.statSkipped)}</div>
                                </div>
                                <div>
                                    <div class="fw-bolder fs-2 text-danger">${stat.errors.length}</div>
                                    <div class="text-muted fs-8">${escHtml(lang.statErrors)}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            });
            html += '</div>';

            sections.forEach(s => {
                const errors = (report[s.key] || {}).errors || [];
                if (!errors.length) return;
                const label = lang.sections[s.key] || s.key;
                html += `
                <div class="mb-3">
                    <div class="fw-bold text-danger mb-2">⚠ ${escHtml(label)} — ${errors.length} ${escHtml(lang.issuesSuffix)}</div>
                    <ul class="text-danger fs-7 mb-0 ps-4">
                        ${errors.map(e => `<li>${escHtml(e)}</li>`).join('')}
                    </ul>
                </div>`;
            });

            content.innerHTML = html;
            panel.classList.remove('d-none');

            const totalCreated = Object.values(report).reduce((sum, s) => sum + (s.created || 0), 0);
            if (totalCreated > 0) {
                setTimeout(() => {
                    reloadComponent('reloadProductComponent', '{{ route('products.index') }}');
                }, 1500);
            }
        }

        function renderError (msg) {
            const panel   = document.getElementById('importResultPanel');
            const content = document.getElementById('importResultContent');
            content.innerHTML = `
                <div class="alert alert-danger d-flex align-items-center">
                    <i class="ki-duotone ki-cross-circle fs-2x text-danger me-3">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <div class="fw-semibold">${escHtml(msg)}</div>
                </div>`;
            panel.classList.remove('d-none');
        }

        /* ── Helpers ──────────────────────────────────────────────────────────── */
        function setLoading (loading) {
            document.getElementById('importBtnLabel').classList.toggle('d-none',  loading);
            document.getElementById('importBtnSpinner').classList.toggle('d-none', !loading);
            document.getElementById('importSubmitBtn').disabled = loading;
            document.getElementById('importCancelBtn').disabled = loading;
        }

        function showError (msg) {
            const el = document.getElementById('importFileError');
            el.textContent = msg;
            el.classList.remove('d-none');
        }

        function hideError () {
            document.getElementById('importFileError').classList.add('d-none');
        }

        function resetResultPanel () {
            document.getElementById('importResultPanel').classList.add('d-none');
            document.getElementById('importResultContent').innerHTML = '';
        }

        function escHtml (str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        document.getElementById('kt_modal_catalog_import')
                .addEventListener('hidden.bs.modal', function () {
                    document.getElementById('importCatalogForm').reset();
                    document.getElementById('importFileName').classList.add('d-none');
                    resetResultPanel();
                    hideError();
                    setLoading(false);
                });
    })();
</script>
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

<!-- Product Category  -->
 <script>
    function submitProductCategoryForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
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

    function editProductCategoryInstanceLoop(uniqueId) {
        const submitButton = document.getElementById('editProductCategoryButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        // Select the form and create FormData from it
        var form = document.getElementById('kt_ecommerce_edit_product_category_form' + uniqueId);
        var formData = new FormData(form);

        var data = Object.fromEntries(formData.entries());
        // console.log(data);

        // Set up the URL dynamically
        var updateUrl = '{{ route('product-category.update', ['product_category' => ':id']) }}'.replace(':id', uniqueId);
        
        // Submit form data asynchronously
        handleEditResponse(data, updateUrl, uniqueId, submitButton);

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

<script>

    
    function editInstanceLoopAdvance(uniqueId) {
        const submitButton = document.getElementById('editAdvanceButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        var form = document.getElementById('editAdvanceForm' + uniqueId);
        var formData = new FormData(form);

        // Ensure _method is set
        formData.set('_method', 'PUT');
        
        // Handle checkboxes
        const checkedTypes = [];
        document.querySelectorAll(`#editAdvanceForm${uniqueId} input[name="applicable_salary_types[]"]:checked`)
            .forEach(cb => {
                formData.append('applicable_salary_types[]', cb.value);
                checkedTypes.push(cb.value);
            });

        // Convert FormData to object with proper array handling
        var data = {};
        for (let [key, value] of formData.entries()) {
            if (key.endsWith('[]')) {
                const arrayKey = key.slice(0, -2);
                if (!data[arrayKey]) {
                    data[arrayKey] = [];
                }
                data[arrayKey].push(value);
            } else {
                data[key] = value;
            }
        }

        // Ensure applicable_salary_types exists
        if (!data.applicable_salary_types) {
            data.applicable_salary_types = [];
        }

        // console.log('Data being sent:', data);

        var updateUrl = '{{ route("employee-advance.update", ["employee_advance" => ":id"]) }}'.replace(':id', uniqueId);

        
        handleEditResponse(data, updateUrl, uniqueId, submitButton);
    }


    // Approve advance with payment method
    function confirmApproveAdvance(id) {
        const paymentMethodId = document.getElementById('approve_payment_method_' + id).value;
        const errorElement = document.getElementById('payment_method_error_' + id);
        
        // Validate payment method
        if (!paymentMethodId) {
            errorElement.textContent = '{{ __("payments.payment_method_required") }}';
            errorElement.classList.remove('d-none');
            return;
        } else {
            errorElement.classList.add('d-none');
        }
        
        const button = document.getElementById('approveAdvanceButton' + id);
        
        button.disabled = true;
        button.querySelector('.indicator-label').style.display = 'none';
        button.querySelector('.indicator-progress').style.display = 'inline-block';
        
        fetch('/employee-advances/' + id + '/approve', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                payment_method_id: paymentMethodId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#approveAdvanceModal' + id).modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'An error occurred', 'error');
        })
        .finally(() => {
            button.disabled = false;
            button.querySelector('.indicator-label').style.display = 'inline-block';
            button.querySelector('.indicator-progress').style.display = 'none';
        });
    }

    // Reject advance
    function confirmRejectAdvance(id) {
        const reason = document.getElementById('rejection_reason_' + id).value;
        if (!reason) {
            Swal.fire('Error', 'Please provide a rejection reason', 'error');
            return;
        }
        
        const button = document.getElementById('rejectAdvanceButton' + id);
        
        button.disabled = true;
        button.querySelector('.indicator-label').style.display = 'none';
        button.querySelector('.indicator-progress').style.display = 'inline-block';
        
        fetch('/employee-advances/' + id + '/reject', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ rejection_reason: reason })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#rejectAdvanceModal' + id).modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'An error occurred', 'error');
        })
        .finally(() => {
            button.disabled = false;
            button.querySelector('.indicator-label').style.display = 'inline-block';
            button.querySelector('.indicator-progress').style.display = 'none';
        });
    }

    // Cancel advance
    function confirmCancelAdvance(id) {
        const button = document.getElementById('cancelAdvanceButton' + id);
        
        button.disabled = true;
        button.querySelector('.indicator-label').style.display = 'none';
        button.querySelector('.indicator-progress').style.display = 'inline-block';
        
        fetch('/employee-advances/' + id + '/cancel', {
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
                $('#cancelAdvanceModal' + id).modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'An error occurred', 'error');
        })
        .finally(() => {
            button.disabled = false;
            button.querySelector('.indicator-label').style.display = 'inline-block';
            button.querySelector('.indicator-progress').style.display = 'none';
        });
    }

    // Delete advance - Alternative with POST + _method
    function confirmDeleteAdvance(id) {
        const button = document.getElementById('deleteAdvanceButton' + id);
        
        button.disabled = true;
        button.querySelector('.indicator-label').style.display = 'none';
        button.querySelector('.indicator-progress').style.display = 'inline-block';
        
        // Create form data for method spoofing
        const formData = new FormData();
        formData.append('_method', 'DELETE');
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        
        var deleteUrl = '{{ route("employee-advance.destroy", ["employee_advance" => ":id"]) }}'.replace(':id', id);
        
        fetch(deleteUrl, {
            method: 'POST', // Use POST
            headers: {
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#deleteAdvanceModal' + id).modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'An error occurred', 'error');
        })
        .finally(() => {
            button.disabled = false;
            button.querySelector('.indicator-label').style.display = 'inline-block';
            button.querySelector('.indicator-progress').style.display = 'none';
        });
    }

    // Toggle installment fields based on frequency selection
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[id^="deduction_frequency"]').forEach(select => {
            select.addEventListener('change', function() {
                const id = this.id.replace('deduction_frequency', '');
                const installmentsField = document.querySelector('.installments-field-' + id);
                const deductionDayField = document.querySelector('.deduction-day-field-' + id);
                
                if (this.value === 'weekly' || this.value === 'monthly' || this.value === 'yearly') {
                    if (installmentsField) installmentsField.style.display = 'block';
                    if (deductionDayField) deductionDayField.style.display = 'block';
                } else {
                    if (installmentsField) installmentsField.style.display = 'none';
                    if (deductionDayField) deductionDayField.style.display = 'none';
                }
            });
        });
    });
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